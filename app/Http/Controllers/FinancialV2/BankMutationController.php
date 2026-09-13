<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\BankMutationService;
use App\Domain\FinancialV2\ConfigureMrjBankMutationsService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\TransactionEvidenceUploadService;
use App\Http\Controllers\Controller;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

final class BankMutationController extends Controller
{
    public function __construct(
        private readonly BankMutationService $bankMutations,
        private readonly FinancialTransactionLifecycleService $lifecycle,
        private readonly TransactionEvidenceUploadService $uploads,
        private readonly BalanceInquiryService $balances,
        private readonly ConfigureMrjBankMutationsService $configuration,
    ) {}

    public function index(Request $request): View
    {
        [$entities, $entity] = $this->entityContext($request);
        $filters = $request->only(['year', 'month', 'financial_account_id', 'fund_id', 'category_id', 'status']);
        $options = $entity ? $this->options($entity->id) : $this->emptyOptions();
        $configurationStatus = $this->configuration->status();
        $transactions = null;
        $editableBatchIds = collect();
        if ($entity) {
            $categoryIds = $options['categories']->pluck('id');
            $query = FinancialTransaction::query()
                ->with(['type', 'category', 'primaryFinancialAccount', 'splits.fund'])
                ->where('accounting_entity_id', $entity->id)
                ->whereIn('category_id', $categoryIds);
            if ($filters['year'] ?? null) {
                $query->whereYear('accounting_date', (int) $filters['year']);
            }
            if ($filters['month'] ?? null) {
                $query->whereMonth('accounting_date', (int) $filters['month']);
            }
            foreach (['financial_account_id' => 'primary_financial_account_id', 'category_id' => 'category_id'] as $filter => $column) {
                if ($filters[$filter] ?? null) {
                    $query->where($column, $filters[$filter]);
                }
            }
            if ($filters['fund_id'] ?? null) {
                $query->whereHas('splits', fn ($builder) => $builder->where('fund_id', $filters['fund_id']));
            }
            if (($filters['status'] ?? '') === 'all') {
                // Deliberately include every lifecycle state.
            } elseif ($filters['status'] ?? null) {
                $query->where('status', $filters['status']);
            } else {
                $query->where('status', '!=', 'cancelled');
            }
            $transactions = $query->orderByDesc('accounting_date')->orderByDesc('created_at')->paginate(25)->withQueryString();
            $pageBatchIds = $transactions->getCollection()->pluck('correlation_id')->filter()->unique()->values();
            if ($pageBatchIds->isNotEmpty()) {
                $editableBatchIds = FinancialTransaction::query()
                    ->where('accounting_entity_id', $entity->id)
                    ->whereIn('category_id', $categoryIds)
                    ->whereIn('correlation_id', $pageBatchIds)
                    ->where('status', '!=', 'cancelled')
                    ->get(['correlation_id', 'status'])
                    ->groupBy('correlation_id')
                    ->filter(fn ($batch) => $batch->isNotEmpty() && $batch->every(fn (FinancialTransaction $transaction): bool => $transaction->status === 'draft'))
                    ->keys();
            }
        }

        return view('masjid.mrj.admin.financial-v2.bank-mutations.index', compact('entities', 'entity', 'filters', 'options', 'transactions', 'editableBatchIds', 'configurationStatus'));
    }

    public function create(Request $request): View
    {
        [$entities, $entity] = $this->entityContext($request);
        $options = $entity ? $this->options($entity->id) : $this->emptyOptions();

        return view('masjid.mrj.admin.financial-v2.bank-mutations.form', [
            'entities' => $entities,
            'entity' => $entity,
            'options' => $options,
            'transaction' => null,
            'batchTransactions' => collect(),
            'batchId' => (string) Str::uuid(),
            'today' => now()->toDateString(),
            'configurationStatus' => $this->configuration->status(),
        ]);
    }

    public function configure(Request $request): RedirectResponse
    {
        try {
            $result = $this->configuration->configure($request->user()->id);
        } catch (Throwable $exception) {
            report($exception);
            $detail = $exception instanceof RuntimeException ? ' '.$exception->getMessage() : '';

            return redirect()->route('financial-v2.bank-mutations.index', ['entity' => $this->configuration->status()['entity_id']])
                ->withErrors(['configuration' => 'Tidak dapat mengaktifkan konfigurasi Mutasi Bank.'.$detail]);
        }

        return redirect()->route('financial-v2.bank-mutations.index', ['entity' => $result['entity_id']])
            ->with('success', $result['changed'] ? 'Konfigurasi Mutasi Bank berhasil diaktifkan.' : 'Konfigurasi Mutasi Bank sudah aktif.');
    }

    public function store(Request $request): RedirectResponse
    {
        $input = $this->validatedBatch($request, proofRequired: true);
        $result = DB::transaction(function () use ($input, $request): array {
            $result = $this->bankMutations->createBatch(
                $input['common'],
                $input['mutations'],
                $input['batch_id'],
                $request->user()?->id,
            );
            if (! $result['replayed']) {
                $this->uploads->attachToMany(
                    $input['common']['accounting_entity_id'],
                    $result['transactions']->pluck('id')->all(),
                    $request->file('proof'),
                    'statement',
                    $request->user()?->id,
                );
            }

            return $result;
        }, 3);

        $message = $result['replayed']
            ? 'Batch Mutasi Bank sudah pernah disimpan; tidak ada transaksi duplikat.'
            : $result['transactions']->count().' draft Mutasi Bank disimpan dengan satu bukti bersama.';

        return redirect()->route('financial-v2.bank-mutations.index', ['entity' => $input['common']['accounting_entity_id']])->with('success', $message);
    }

    public function edit(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        abort_unless($transaction->status === 'draft', 409, 'Hanya draft Mutasi Bank yang dapat diubah.');

        return redirect()->route('financial-v2.bank-mutations.batches.edit', [
            'batch' => $transaction->correlation_id,
            'entity' => $transaction->accounting_entity_id,
        ]);
    }

    public function editBatch(Request $request, string $batch): View
    {
        abort_unless(Str::isUuid($batch), 404);
        [$entities, $entity] = $this->entityContext($request);
        abort_unless($entity, 404);
        $transactions = $this->bankMutations->batchTransactions($entity->id, $batch);
        abort_if($transactions->isEmpty(), 404);
        abort_if($transactions->contains(fn (FinancialTransaction $transaction): bool => $transaction->status !== 'draft'), 409, 'Seluruh Mutasi Bank dalam batch harus berstatus draft agar dapat diubah.');

        return view('masjid.mrj.admin.financial-v2.bank-mutations.form', [
            'entities' => $entities,
            'entity' => $entity,
            'options' => $this->options($entity->id),
            'transaction' => $transactions->first(),
            'batchTransactions' => $transactions,
            'batchId' => $batch,
            'today' => now()->toDateString(),
            'configurationStatus' => $this->configuration->status(),
        ]);
    }

    public function updateBatch(Request $request, string $batch): RedirectResponse
    {
        abort_unless(Str::isUuid($batch), 404);
        $input = $this->validatedBatch($request, proofRequired: false, expectedBatchId: $batch);
        $transactions = DB::transaction(function () use ($input, $batch, $request) {
            $existingTransactionIds = $this->bankMutations
                ->batchTransactions($input['common']['accounting_entity_id'], $batch)
                ->pluck('id')
                ->all();
            $transactions = $this->bankMutations->updateDraftBatch(
                $input['common']['accounting_entity_id'],
                $batch,
                $input['common'],
                $input['mutations'],
                $request->user()?->id,
            );
            if ($request->hasFile('proof')) {
                $this->uploads->attachToMany(
                    $input['common']['accounting_entity_id'],
                    $transactions->pluck('id')->all(),
                    $request->file('proof'),
                    'statement',
                    $request->user()?->id,
                );
            } else {
                $newTransactionIds = $transactions->pluck('id')->diff($existingTransactionIds)->values()->all();
                if ($newTransactionIds !== []) {
                    $this->uploads->shareExistingToMany(
                        $input['common']['accounting_entity_id'],
                        $existingTransactionIds,
                        $newTransactionIds,
                        'statement',
                        $request->user()?->id,
                    );
                }
            }

            return $transactions;
        }, 3);

        return redirect()->route('financial-v2.bank-mutations.index', ['entity' => $input['common']['accounting_entity_id']])
            ->with('success', $transactions->count().' draft dalam batch Mutasi Bank diperbarui.');
    }

    public function destroyBatch(Request $request, string $batch): RedirectResponse
    {
        abort_unless(Str::isUuid($batch), 404);
        $entityId = (string) $request->input('entity');
        abort_unless($entityId !== '', 404);
        $count = $this->bankMutations->removeDraftBatch($entityId, $batch, $request->user()?->id);

        return back()->with('success', $count.' draft dalam batch Mutasi Bank dihapus; jejak audit tetap disimpan.');
    }

    public function update(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        $input = $this->validated($request, proofRequired: false, transaction: $transaction);
        $updated = $this->bankMutations->updateDraft($transaction->id, $input, $request->user()?->id);
        if ($request->hasFile('proof')) {
            $this->uploads->attach($input['accounting_entity_id'], $updated->id, $request->file('proof'), 'statement', $request->user()?->id);
        }

        return redirect()->route('financial-v2.bank-mutations.index', ['entity' => $input['accounting_entity_id']])->with('success', 'Draft Mutasi Bank diperbarui.');
    }

    public function destroy(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        abort_unless($transaction->status === 'draft', 409, 'Hanya draft Mutasi Bank yang dapat dihapus.');
        $this->bankMutations->removeDraft($transaction->id, $request->user()?->id);

        return back()->with('success', 'Draft Mutasi Bank dihapus dari daftar aktif; jejak audit pembatalannya tetap disimpan.');
    }

    public function submit(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        if (! AttachmentLink::query()->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('evidence_type', 'statement')->where('status', 'active')->exists()) {
            return back()->withErrors(['financial' => 'Rekening koran wajib tersedia sebelum draft diajukan.']);
        }
        $this->lifecycle->submit($transaction->id, $request->user()?->id);

        return back()->with('success', 'Mutasi Bank diajukan untuk pemeriksaan.');
    }

    public function verify(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        $this->lifecycle->verify($transaction->id, $request->user()?->id);

        return back()->with('success', 'Mutasi Bank selesai diperiksa.');
    }

    public function approve(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        $policy = $this->policyFor($transaction);
        $required = $policy->required_approval_steps;
        for ($step = 1; $step <= $required; $step++) {
            if (! DB::table('financial_v2_approval_decisions')->where('transaction_id', $transaction->id)->where('step_no', $step)->where('decision', 'approved')->exists()) {
                $this->lifecycle->recordApprovalDecision($transaction->id, $step, 'approved', $request->user()?->id, 'Persetujuan Mutasi Bank');
            }
        }
        $this->lifecycle->approve($transaction->id, $request->user()?->id);

        return back()->with('success', 'Mutasi Bank disetujui dan siap dicatat resmi.');
    }

    public function post(Request $request, FinancialTransaction $transaction): RedirectResponse
    {
        $this->assertBankMutation($transaction, $request);
        $fingerprint = hash('sha256', implode('|', [$transaction->id, $transaction->source_reference, $transaction->gross_amount, $transaction->accounting_date->toDateString()]));
        $this->lifecycle->post($transaction->id, 'bank-post:'.$transaction->source_reference, $fingerprint, $request->user()?->id);

        return back()->with('success', 'Mutasi Bank dicatat resmi melalui PostingEngine.');
    }

    public function preview(Request $request): JsonResponse
    {
        if ($request->has('mutations')) {
            return $this->previewBatch($request);
        }

        $data = $request->validate([
            'entity' => ['required', 'uuid'],
            'financial_account_id' => ['required', 'uuid'],
            'fund_id' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'category_id' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ]);
        $category = DB::table('financial_v2_categories')->where('accounting_entity_id', $data['entity'])->where('id', $data['category_id'])->whereIn('code', array_keys(BankMutationService::CATEGORY_CODES))->first();
        abort_unless($category, 422, 'Jenis mutasi tidak valid.');
        $policyExists = BankMutationPolicy::query()
            ->where('accounting_entity_id', $data['entity'])
            ->where('financial_account_id', $data['financial_account_id'])
            ->where('fund_id', $data['fund_id'])
            ->where('category_id', $data['category_id'])
            ->where('status', 'active')
            ->where('effective_from', '<=', $data['date'])
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $data['date']))
            ->exists();
        abort_unless($policyExists, 422, 'Kombinasi rekening, Dana, jenis mutasi, dan tanggal belum memiliki policy aktif.');
        $previousDate = CarbonImmutable::parse($data['date'])->subDay()->toDateString();
        $opening = $this->balances->financialAccountBalance($data['entity'], $data['financial_account_id'], $previousDate)['balance'];
        $postedMovement = $this->balances->financialAccountMovement($data['entity'], $data['financial_account_id'], $data['date'], $data['date']);
        $proposed = $category->code === 'BANK_INTEREST' ? DecimalAmount::normalize($data['amount']) : DecimalAmount::subtract('0.00', $data['amount']);
        $net = DecimalAmount::add($postedMovement, $proposed);

        return response()->json([
            'opening' => $opening,
            'posted_movement' => $postedMovement,
            'proposed_movement' => $proposed,
            'net_movement' => $net,
            'closing' => DecimalAmount::add($opening, $net),
        ]);
    }

    private function previewBatch(Request $request): JsonResponse
    {
        $data = $request->validate([
            'entity' => ['required', 'uuid'],
            'financial_account_id' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'mutations' => ['present', 'array', 'max:50'],
            'mutations.*.category_id' => ['required', 'uuid'],
            'mutations.*.fund_id' => ['required', 'uuid'],
            'mutations.*.amount' => ['required', 'numeric', 'min:0'],
        ]);
        abort_unless(DB::table('financial_v2_financial_accounts')->where('accounting_entity_id', $data['entity'])->where('id', $data['financial_account_id'])->where('status', 'active')->exists(), 422, 'Rekening tidak valid.');

        $previousDate = CarbonImmutable::parse($data['date'])->subDay()->toDateString();
        $opening = $this->balances->financialAccountBalance($data['entity'], $data['financial_account_id'], $previousDate)['balance'];
        $postedMovement = $this->balances->financialAccountMovement($data['entity'], $data['financial_account_id'], $data['date'], $data['date']);
        $running = DecimalAmount::add($opening, $postedMovement);
        $totalCredit = '0.00';
        $totalDebit = '0.00';
        $rowPreviews = [];

        foreach ($data['mutations'] as $mutation) {
            $category = DB::table('financial_v2_categories')->where('accounting_entity_id', $data['entity'])->where('id', $mutation['category_id'])->whereIn('code', array_keys(BankMutationService::CATEGORY_CODES))->first();
            abort_unless($category, 422, 'Jenis mutasi tidak valid.');
            abort_unless(DB::table('financial_v2_funds')->where('accounting_entity_id', $data['entity'])->where('id', $mutation['fund_id'])->where('status', 'active')->exists(), 422, 'Dana tidak valid.');
            abort_unless($this->policyExists($data['entity'], $data['financial_account_id'], $mutation['fund_id'], $mutation['category_id'], $data['date']), 422, 'Kombinasi rekening, Dana, jenis mutasi, dan tanggal belum memiliki policy aktif.');

            $amount = DecimalAmount::normalize($mutation['amount']);
            $isCredit = $category->code === 'BANK_INTEREST';
            $signed = $isCredit ? $amount : DecimalAmount::negate($amount);
            if ($isCredit) {
                $totalCredit = DecimalAmount::add($totalCredit, $amount);
            } else {
                $totalDebit = DecimalAmount::add($totalDebit, $amount);
            }
            $running = DecimalAmount::add($running, $signed);
            $rowPreviews[] = ['movement' => $signed, 'balance' => $running];
        }

        $proposedNet = DecimalAmount::subtract($totalCredit, $totalDebit);

        return response()->json([
            'opening' => $opening,
            'posted_movement' => $postedMovement,
            'total_credit' => $totalCredit,
            'total_debit' => $totalDebit,
            'proposed_movement' => $proposedNet,
            'net_movement' => DecimalAmount::add($postedMovement, $proposedNet),
            'closing' => $running,
            'rows' => $rowPreviews,
        ]);
    }

    /** @return array<string, mixed> */
    private function validated(Request $request, bool $proofRequired, ?FinancialTransaction $transaction = null): array
    {
        $entityId = (string) $request->input('entity');
        $sourceRule = Rule::unique('financial_v2_transactions', 'source_reference')->where(fn ($query) => $query->where('accounting_entity_id', $entityId));
        if ($transaction) {
            $sourceRule->ignore($transaction->id);
        }
        $data = $request->validate([
            'entity' => ['required', 'uuid', Rule::exists('financial_v2_accounting_entities', 'id')->where('status', 'active')],
            'financial_account_id' => ['required', 'uuid', Rule::exists('financial_v2_financial_accounts', 'id')->where('accounting_entity_id', $entityId)->where('status', 'active')],
            'date' => ['required', 'date'],
            'category_id' => ['required', 'uuid', Rule::exists('financial_v2_categories', 'id')->where('accounting_entity_id', $entityId)->whereIn('code', array_keys(BankMutationService::CATEGORY_CODES))],
            'fund_id' => ['required', 'uuid', Rule::exists('financial_v2_funds', 'id')->where('accounting_entity_id', $entityId)->where('status', 'active')],
            'amount' => ['required', 'numeric', 'gt:0'],
            'description' => ['required', 'string', 'max:1000'],
            'source_reference' => ['required', 'string', 'max:160', $sourceRule],
            'proof' => [$proofRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ]);
        $data['accounting_entity_id'] = $data['entity'];

        return $data;
    }

    /** @return array{batch_id:string,common:array<string,mixed>,mutations:array<int,array<string,mixed>>} */
    private function validatedBatch(Request $request, bool $proofRequired, ?string $expectedBatchId = null): array
    {
        $entityId = (string) $request->input('entity');
        $data = $request->validate([
            'entity' => ['required', 'uuid', Rule::exists('financial_v2_accounting_entities', 'id')->where('status', 'active')],
            'bank_mutation_batch_id' => ['required', 'uuid'],
            'financial_account_id' => ['required', 'uuid', Rule::exists('financial_v2_financial_accounts', 'id')->where('accounting_entity_id', $entityId)->where('status', 'active')],
            'date' => ['required', 'date'],
            'mutations' => ['required', 'array', 'min:1', 'max:50'],
            'mutations.*.transaction_id' => ['nullable', 'uuid'],
            'mutations.*.category_id' => ['required', 'uuid', Rule::exists('financial_v2_categories', 'id')->where('accounting_entity_id', $entityId)->whereIn('code', array_keys(BankMutationService::CATEGORY_CODES))],
            'mutations.*.fund_id' => ['required', 'uuid', Rule::exists('financial_v2_funds', 'id')->where('accounting_entity_id', $entityId)->where('status', 'active')],
            'mutations.*.amount' => ['required', 'numeric', 'gt:0'],
            'mutations.*.description' => ['required', 'string', 'max:1000'],
            'mutations.*.source_reference' => ['required', 'string', 'max:160', 'distinct:strict'],
            'proof' => [$proofRequired ? 'required' : 'nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,xls,xlsx', 'max:10240'],
        ]);
        if ($expectedBatchId !== null && $data['bank_mutation_batch_id'] !== $expectedBatchId) {
            abort(404);
        }

        return [
            'batch_id' => $data['bank_mutation_batch_id'],
            'common' => [
                'accounting_entity_id' => $data['entity'],
                'financial_account_id' => $data['financial_account_id'],
                'date' => $data['date'],
            ],
            'mutations' => array_values($data['mutations']),
        ];
    }

    private function assertBankMutation(FinancialTransaction $transaction, Request $request): void
    {
        abort_unless($this->bankMutations->isBankMutation($transaction), 404);
        abort_unless($request->filled('entity') && $transaction->accounting_entity_id === $request->input('entity'), 404);
    }

    private function policyFor(FinancialTransaction $transaction): BankMutationPolicy
    {
        $fundId = $transaction->splits()->whereNotNull('fund_id')->value('fund_id');

        return BankMutationPolicy::query()
            ->where('accounting_entity_id', $transaction->accounting_entity_id)
            ->where('financial_account_id', $transaction->primary_financial_account_id)
            ->where('category_id', $transaction->category_id)
            ->where('fund_id', $fundId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $transaction->accounting_date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $transaction->accounting_date))
            ->firstOrFail();
    }

    private function policyExists(string $entityId, string $financialAccountId, string $fundId, string $categoryId, string $date): bool
    {
        return BankMutationPolicy::query()
            ->where('accounting_entity_id', $entityId)
            ->where('financial_account_id', $financialAccountId)
            ->where('fund_id', $fundId)
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->exists();
    }

    /** @return array{0: mixed, 1: ?AccountingEntity} */
    private function entityContext(Request $request, ?string $forcedEntityId = null): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $entityId = $forcedEntityId ?: $request->input('entity') ?: ($entities->count() === 1 ? $entities->first()->id : null);
        $entity = $entityId ? $entities->firstWhere('id', $entityId) : null;

        return [$entities, $entity];
    }

    /** @return array<string, mixed> */
    private function options(string $entityId): array
    {
        $policies = BankMutationPolicy::query()->with(['category', 'financialAccount', 'fund'])->where('accounting_entity_id', $entityId)->where('status', 'active')->get();

        return [
            'policies' => $policies,
            'categories' => $policies->pluck('category')->filter()->unique('id')->sortBy('name')->values(),
            'financialAccounts' => $policies->pluck('financialAccount')->filter()->unique('id')->sortBy('name')->values(),
            'funds' => Fund::query()->where('accounting_entity_id', $entityId)->where('status', 'active')->orderBy('name')->get(),
            'defaultFundId' => Fund::query()->where('accounting_entity_id', $entityId)->where('code', 'INFAQ-TROMOL')->where('status', 'active')->value('id'),
        ];
    }

    /** @return array<string, mixed> */
    private function emptyOptions(): array
    {
        return ['policies' => collect(), 'categories' => collect(), 'financialAccounts' => collect(), 'funds' => collect(), 'defaultFundId' => null];
    }
}
