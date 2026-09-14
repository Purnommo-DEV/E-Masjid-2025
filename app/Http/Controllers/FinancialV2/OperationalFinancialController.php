<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\AllocationHistoryReadService;
use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\BankMutationService;
use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\DraftTransactionReadService;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionConfigurationResolver;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\RealizationDraftReadService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\FundGroupingReadService;
use App\Domain\FinancialV2\Reporting\FundHistoryReadService;
use App\Domain\FinancialV2\TransactionEvidenceUploadService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\Reconciliation;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Operational adapter for Financial V2.
 *
 * This controller deliberately works only with operational source records and
 * lifecycle services. It never creates or mutates Journal, JournalLine, or
 * LedgerEntry records; official financial facts remain PostingEngine-only.
 */
final class OperationalFinancialController
{
    /** @var array<string, array{code: string, label: string, evidence: string}> */
    private const OPERATIONS = [
        'receipt' => ['code' => 'RCV', 'label' => 'Penerimaan', 'evidence' => 'receipt'],
        'payment' => ['code' => 'PAY', 'label' => 'Pengeluaran', 'evidence' => 'invoice'],
        'transfer' => ['code' => 'TRF', 'label' => 'Transfer Antar Rekening', 'evidence' => 'transfer_proof'],
        'interfund' => ['code' => 'IFT', 'label' => 'Pindah Dana', 'evidence' => 'policy'],
        'realization' => ['code' => 'PAY', 'label' => 'Realisasi Dana', 'evidence' => 'invoice'],
    ];

    public function __construct(
        private readonly FinancialTransactionLifecycleService $lifecycle,
        private readonly BudgetAllocationService $budgetAllocations,
        private readonly AllocationHistoryReadService $allocationHistory,
        private readonly EvidenceService $evidence,
        private readonly TransactionEvidenceUploadService $evidenceUploads,
        private readonly BalanceInquiryService $balances,
        private readonly FinancialReportService $reports,
        private readonly FundGroupingReadService $fundGroups,
        private readonly FundHistoryReadService $fundHistory,
        private readonly RealizationDraftReadService $realizationDrafts,
        private readonly DraftTransactionReadService $draftTransactions,
        private readonly FinancialTransactionConfigurationResolver $configurationResolver,
    ) {}

    public function dashboard(Request $request)
    {
        $context = $this->context($request);
        $entity = $context['entity'];

        return view('masjid.mrj.admin.financial-v2.dashboard', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'summary' => $entity ? $this->dashboardSummary($entity) : $this->emptyDashboardSummary(),
        ]);
    }

    public function create(Request $request, string $operation)
    {
        $this->operation($operation);
        $context = $this->context($request);
        $selectedAllocationVersionId = null;
        if ($operation === 'realization' && $context['entity']) {
            $requestedVersionId = $request->validate(['allocation_version_id' => ['nullable', 'uuid']])['allocation_version_id'] ?? null;
            if ($requestedVersionId) {
                $this->realizationDimensions($context['entity'], $requestedVersionId);
                $existing = $this->realizationDrafts->activeForAllocationVersion($context['entity']->id, $requestedVersionId);
                if ($existing) {
                    return redirect()
                        ->route('financial-v2.transactions.show', $existing)
                        ->with('success', 'Draft Realisasi yang sedang disiapkan sudah dibuka. Tidak ada draft baru yang dibuat.');
                }
                $selectedAllocationVersionId = $requestedVersionId;
            }
        }

        return view('masjid.mrj.admin.financial-v2.form', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'operation' => $operation,
            'definition' => self::OPERATIONS[$operation],
            'options' => $context['entity'] ? $this->formOptions($context['entity'], self::OPERATIONS[$operation]['code'], $operation === 'realization') : $this->emptyOptions(),
            'transaction' => null,
            'submissionKey' => old('submission_key', (string) Str::uuid()),
            'today' => now()->toDateString(),
            'selectedAllocationVersionId' => $selectedAllocationVersionId,
        ]);
    }

    public function store(Request $request, string $operation)
    {
        $definition = $this->operation($operation);
        $entity = $this->requiredEntity($request);
        $input = $this->validatedOperationInput($request, $operation);
        $actorId = $request->user()?->id;
        $sourceKey = $this->sourceKey($operation, $input['submission_key']);

        try {
            if ($operation === 'realization') {
                $existing = $this->realizationDrafts->activeForAllocationVersion($entity->id, $input['budget_allocation_version_id']);
                if ($existing) {
                    return $this->success($request, 'Draft Realisasi untuk alokasi ini sudah tersedia. Draft yang ada dibuka; transaksi baru tidak dibuat.', route('financial-v2.transactions.show', $existing), ['transaction_id' => $existing->id, 'duplicate' => true]);
                }
            }
            $type = $this->transactionType($entity, $definition['code']);
            $existing = FinancialTransaction::query()
                ->where('accounting_entity_id', $entity->id)
                ->where('idempotency_key', $sourceKey)
                ->first();
            if ($existing) {
                return $this->success($request, 'Permintaan sebelumnya sudah diterima. Transaksi tidak dibuat ulang.', route('financial-v2.transactions.show', $existing), ['transaction_id' => $existing->id, 'duplicate' => true]);
            }

            $transaction = match ($operation) {
                'receipt' => $this->createReceipt($entity, $type, $input, $sourceKey, $actorId),
                'payment' => $this->createPayment($entity, $type, $input, $sourceKey, $actorId),
                'transfer' => $this->createTreasuryTransfer($entity, $type, $input, $sourceKey, $actorId),
                'interfund' => $this->createInterfundTransfer($entity, $type, $input, $sourceKey, $actorId),
                'realization' => $this->createRealization($entity, $type, $input, $sourceKey, $actorId),
            };

            $this->attachUploadIfPresent($request, $entity, $transaction, $definition['evidence'], $actorId);

            return $this->success($request, 'Draft tersimpan. '.$definition['label'].' dapat dilanjutkan dari halaman ini.', route('financial-v2.transactions.show', $transaction), ['transaction_id' => $transaction->id]);
        } catch (FinancialDomainException|FinancialPostingException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        } catch (QueryException $exception) {
            return $this->failure($request, new FinancialDomainException('E-UX-DUPLICATE', $exception->getMessage()));
        }
    }

    public function edit(Request $request, FinancialTransaction $transaction)
    {
        try {
            $transaction->load(['type', 'splits', 'primaryFinancialAccount', 'counterparty', 'category', 'realization']);
            $this->ensureDraftIsEditable($transaction);
            $operation = $this->operationForTransaction($transaction);
            if (! in_array($operation, ['receipt', 'payment', 'realization'], true)) {
                return $this->failure($request, new FinancialDomainException('E-UX-DRAFT-RECREATE', 'Draft jenis ini dapat dibatalkan lalu dibuat ulang agar rincian perpindahannya tetap terlacak.'));
            }

            $context = $this->contextForEntity($transaction->accounting_entity_id);
            if ($operation === 'realization' && $transaction->realization?->budget_allocation_version_id) {
                $this->realizationDimensions($context['entity'], $transaction->realization->budget_allocation_version_id);
            }

            return view('masjid.mrj.admin.financial-v2.form', [
                'entities' => $context['entities'],
                'entity' => $context['entity'],
                'operation' => $operation,
                'definition' => self::OPERATIONS[$operation],
                'options' => $this->formOptions($transaction->accounting_entity_id, null, $operation === 'realization', $transaction->accounting_date->toDateString()),
                'transaction' => $transaction,
                'submissionKey' => Str::afterLast($transaction->idempotency_key, ':'),
                'today' => $transaction->accounting_date->toDateString(),
                'selectedAllocationVersionId' => null,
            ]);
        } catch (FinancialDomainException|FinancialPostingException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function update(Request $request, FinancialTransaction $transaction)
    {
        $transaction->load(['type', 'splits', 'counterparty', 'realization']);
        $this->ensureDraftIsEditable($transaction);
        $operation = $this->operationForTransaction($transaction);
        if (! in_array($operation, ['receipt', 'payment', 'realization'], true)) {
            return $this->failure($request, new FinancialDomainException('E-UX-DRAFT-RECREATE', 'Draft jenis ini dapat dibatalkan lalu dibuat ulang agar rincian perpindahannya tetap terlacak.'));
        }

        $entity = $this->activeEntity($transaction->accounting_entity_id);
        $input = $this->validatedOperationInput($request, $operation);
        $actorId = $request->user()?->id;

        try {
            $type = $this->transactionType($entity, self::OPERATIONS[$operation]['code']);
            $fundId = $input['fund_id'] ?? null;
            $programId = $input['program_id'] ?? null;
            $allocationVersionId = null;
            $fundingSources = null;
            if ($operation === 'realization') {
                [$fundId, $programId, $allocationVersionId] = $this->realizationDimensions($entity, $input['budget_allocation_version_id']);
                $fundingSources = $this->realizationFundingSources($entity, $allocationVersionId, $input['funding_sources'] ?? null, $this->amount($input['amount']));
            }
            $financialAccount = $this->financialAccount($entity, $input['financial_account_id']);
            $category = $this->category($entity, $input['category_id'], $type->id);
            $this->fund($entity, $fundId);
            $this->program($entity, $programId);
            $amount = $this->amount($input['amount']);
            $resolved = $this->configurationResolver->resolve([
                'accounting_entity_id' => $entity->id,
                'transaction_type_id' => $type->id,
                'date' => $input['date'],
                'financial_account_id' => $financialAccount->id,
                'fund_ids' => collect($fundingSources ?? [['fund_id' => $fundId]])->pluck('fund_id')->all(),
                'category_id' => $category->id,
                'program_id' => $programId,
            ]);
            $splitAccountId = $resolved->businessAccountId;
            $counterparty = $operation === 'receipt'
                ? null
                : $this->counterpartyFromInput($entity, $input, $actorId, $operation === 'realization' ? 'beneficiary' : 'supplier');

            $this->lifecycle->updateDraft($transaction->id, [
                'business_date' => $input['date'],
                'accounting_date' => $input['date'],
                'gross_amount' => $amount,
                'primary_financial_account_id' => $financialAccount->id,
                'counterparty_id' => $counterparty?->id,
                'category_id' => $category->id,
                'description' => $this->description($operation === 'receipt' ? ($input['source'] ?? null) : null, $input['description'] ?? null),
            ], $actorId);
            $splits = collect($fundingSources ?? [['fund_id' => $fundId, 'amount' => $amount]])
                ->values()
                ->map(fn (array $funding): array => [
                    'account_id' => $splitAccountId,
                    'split_amount' => $funding['amount'],
                    'fund_id' => $funding['fund_id'],
                    'program_id' => $programId,
                    'category_id' => $category->id,
                    'counterparty_id' => $counterparty?->id,
                    'purpose_note' => $funding['note'] ?? null,
                    'source_reference' => $funding['source_reference'] ?? null,
                ])->all();
            $this->lifecycle->replaceDraftSplits($transaction->id, $splits, $actorId);
            $this->attachUploadIfPresent($request, $entity, $transaction->fresh(), self::OPERATIONS[$operation]['evidence'], $actorId);

            return $this->success($request, 'Draft berhasil diperbarui.', route('financial-v2.transactions.show', $transaction), ['transaction_id' => $transaction->id, 'allocation_version_id' => $allocationVersionId]);
        } catch (FinancialDomainException|FinancialPostingException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function post(Request $request, FinancialTransaction $transaction)
    {
        $actorId = $request->user()?->id;

        try {
            if ($transaction->status === 'posted') {
                return $this->success($request, 'Transaksi ini sudah dicatat secara resmi.', route('financial-v2.transactions.show', $transaction), ['transaction_id' => $transaction->id, 'already_posted' => true]);
            }
            $transaction->loadMissing('type', 'realization');
            $isRealization = $this->operationForTransaction($transaction) === 'realization';
            if ($transaction->realization?->budget_allocation_version_id) {
                $this->realizationDimensions($this->activeEntity($transaction->accounting_entity_id), $transaction->realization->budget_allocation_version_id);
            }
            if ($isRealization && $transaction->status !== 'approved') {
                throw new FinancialDomainException('E-REALIZATION-STATE', 'Draft Realisasi harus diajukan, diverifikasi, dan disetujui sebelum dapat dicatat secara resmi.');
            }
            if (! $isRealization && $transaction->status === 'draft') {
                $this->lifecycle->submit($transaction->id, $actorId);
                $transaction->refresh();
            }
            if (! $isRealization && $transaction->status === 'submitted') {
                $this->lifecycle->verify($transaction->id, $actorId);
                $transaction->refresh();
            }
            if (! $isRealization && $transaction->status === 'verified') {
                $this->lifecycle->approve($transaction->id, $actorId);
                $transaction->refresh();
            }
            if ($transaction->status !== 'approved') {
                throw new FinancialDomainException('E-TRANSACTION-STATE', 'Transaksi belum berada pada status yang dapat dicatat secara resmi.');
            }

            $postKey = 'ux-post:'.$transaction->id;
            $result = $this->lifecycle->post($transaction->id, $postKey, hash('sha256', $postKey), $actorId);

            return $this->success($request, 'Transaksi sudah dicatat secara resmi.', route('financial-v2.transactions.show', $transaction), [
                'transaction_id' => $transaction->id,
                'journal_id' => $result->journalId,
                'voucher_id' => $result->voucherId,
            ]);
        } catch (FinancialDomainException|FinancialPostingException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function submitRealization(Request $request, FinancialTransaction $transaction)
    {
        return $this->advanceRealization($request, $transaction, 'submit');
    }

    public function verifyRealization(Request $request, FinancialTransaction $transaction)
    {
        return $this->advanceRealization($request, $transaction, 'verify');
    }

    public function approveRealization(Request $request, FinancialTransaction $transaction)
    {
        return $this->advanceRealization($request, $transaction, 'approve');
    }

    private function advanceRealization(Request $request, FinancialTransaction $transaction, string $action)
    {
        $actorId = $request->user()?->id;

        try {
            $transaction->loadMissing('type', 'realization');
            if ($this->operationForTransaction($transaction) !== 'realization' || ! $transaction->realization?->budget_allocation_version_id) {
                throw new FinancialDomainException('E-REALIZATION-STATE', 'Tahap ini hanya tersedia untuk Draft Realisasi Dana yang aktif.');
            }
            $this->realizationDimensions($this->activeEntity($transaction->accounting_entity_id), $transaction->realization->budget_allocation_version_id);

            $message = match ($action) {
                'submit' => 'Draft Realisasi telah diajukan untuk pemeriksaan.',
                'verify' => 'Realisasi telah diverifikasi dan siap untuk persetujuan.',
                'approve' => 'Realisasi telah disetujui dan siap dicatat secara resmi.',
                default => throw new InvalidArgumentException('Unknown realization lifecycle action.'),
            };
            match ($action) {
                'submit' => $this->lifecycle->submit($transaction->id, $actorId),
                'verify' => $this->lifecycle->verify($transaction->id, $actorId),
                'approve' => $this->lifecycle->approve($transaction->id, $actorId),
            };

            return $this->success($request, $message, route('financial-v2.transactions.show', $transaction), ['transaction_id' => $transaction->id]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function cancel(Request $request, FinancialTransaction $transaction)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ], [
            'reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        try {
            $this->lifecycle->cancel($transaction->id, $data['reason'], $request->user()?->id);

            return $this->success($request, 'Draft dibatalkan. Riwayatnya tetap tersimpan untuk audit.', route('financial-v2.transactions.show', $transaction), ['transaction_id' => $transaction->id]);
        } catch (FinancialDomainException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function submit(Request $request, FinancialTransaction $transaction)
    {
        $data = $request->validate(['entity' => ['required', 'uuid']]);

        try {
            $transaction->loadMissing(['type', 'category', 'realization']);
            $entity = $this->activeEntity($data['entity']);
            abort_unless($transaction->accounting_entity_id === $entity->id, 404);
            if ($transaction->realization || array_key_exists((string) $transaction->category?->code, BankMutationService::CATEGORY_CODES)) {
                throw new FinancialDomainException('E-TRANSACTION-STATE', 'Gunakan alur pengajuan khusus untuk transaksi ini.');
            }

            $this->lifecycle->submit($transaction->id, $request->user()?->id);

            return $this->success($request, 'Draft transaksi telah diajukan untuk pemeriksaan.', route('financial-v2.transactions.show', $transaction), ['transaction_id' => $transaction->id]);
        } catch (FinancialDomainException|FinancialPostingException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function draftTransactions(Request $request)
    {
        $context = $this->context($request);
        $entity = $context['entity'];
        $filters = $request->validate([
            'entity' => ['nullable', 'uuid'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2200'],
            'type' => ['nullable', 'in:receipt,payment,transfer,bank_mutation'],
            'financial_account_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'in:all,draft,submitted,verified,approved,rejected,cancelled'],
            'search' => ['nullable', 'string', 'max:160'],
        ]);
        $filters['status'] = filled($filters['status'] ?? null) ? $filters['status'] : 'draft';
        $filters['year'] = filled($filters['year'] ?? null) ? (int) $filters['year'] : (int) now()->year;
        $years = $entity ? $this->draftTransactions->years($entity->id) : collect([(int) now()->year]);
        $years = $years->push($filters['year'])->unique()->sortDesc()->values();

        return view('masjid.mrj.admin.financial-v2.drafts.index', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'options' => $entity ? $this->formOptions($entity) : $this->emptyOptions(),
            'filters' => $filters,
            'years' => $years,
            'transactions' => $entity ? $this->draftTransactions->page($entity->id, $filters) : null,
        ]);
    }

    public function history(Request $request)
    {
        $context = $this->context($request);
        $entity = $context['entity'];
        $filters = $request->validate([
            'entity' => ['nullable', 'uuid'],
            'period' => ['nullable', 'regex:/^\d{4}-\d{2}$/'],
            'type' => ['nullable', 'in:RCV,PAY,TRF,IFT'],
            'financial_account_id' => ['nullable', 'uuid'],
            'source_financial_account_id' => ['nullable', 'uuid'],
            'destination_financial_account_id' => ['nullable', 'uuid'],
            'fund_id' => ['nullable', 'uuid'],
            'source_fund_id' => ['nullable', 'uuid'],
            'destination_fund_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'in:all,draft,submitted,verified,approved,posted,rejected,cancelled,reversed'],
            'search' => ['nullable', 'string', 'max:160'],
        ]);

        $filters['status'] = filled($filters['status'] ?? null) ? $filters['status'] : 'posted';

        $transactions = $entity
            ? $this->historyQuery($entity, $filters)->paginate(20)->withQueryString()
            : FinancialTransaction::query()->whereRaw('1 = 0')->paginate(20)->withQueryString();

        return view('masjid.mrj.admin.financial-v2.history', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'options' => $entity ? $this->formOptions($entity) : $this->emptyOptions(),
            'filters' => $filters,
            'transactions' => $transactions,
        ]);
    }

    public function show(Request $request, FinancialTransaction $transaction)
    {
        $transaction->load(['type', 'splits.fund', 'primaryFinancialAccount', 'counterparty', 'category', 'treasuryTransfer', 'interfundTransfer', 'realization.budgetAllocationVersion.fundings.fund', 'realization.budgetAllocationVersion.allocation.fund', 'realization.budgetAllocationVersion.allocation.program']);
        $entity = $this->activeEntity($transaction->accounting_entity_id);
        $context = $this->contextForEntity($entity->id);
        $journal = Journal::query()->with('lines')->where('transaction_id', $transaction->id)->where('journal_status', 'posted')->first();
        $voucher = Voucher::query()->where('transaction_id', $transaction->id)->where('status', 'issued')->first();
        $ledgerReferences = $journal
            ? LedgerEntry::query()->whereIn('journal_line_id', $journal->lines->pluck('id'))->pluck('id', 'journal_line_id')->all()
            : [];
        $attachments = AttachmentLink::query()
            ->where('financial_v2_attachment_links.accounting_entity_id', $entity->id)
            ->where('financial_v2_attachment_links.target_type', 'transaction')
            ->where('financial_v2_attachment_links.target_id', $transaction->id)
            ->where('financial_v2_attachment_links.status', 'active')
            ->join('financial_v2_attachments as attachment', 'attachment.id', '=', 'financial_v2_attachment_links.attachment_id')
            ->orderByDesc('financial_v2_attachment_links.created_at')
            ->get([
                'financial_v2_attachment_links.id as link_id',
                'financial_v2_attachment_links.evidence_type',
                'financial_v2_attachment_links.created_at as linked_at',
                'attachment.id as attachment_id',
                'attachment.original_filename',
                'attachment.media_type',
                'attachment.byte_size',
            ]);

        return view('masjid.mrj.admin.financial-v2.show', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'options' => $this->formOptions($entity),
            'transaction' => $transaction,
            'operation' => $this->operationForTransaction($transaction),
            'isBankMutation' => array_key_exists((string) $transaction->category?->code, BankMutationService::CATEGORY_CODES),
            'journal' => $journal,
            'voucher' => $voucher,
            'ledgerReferences' => $ledgerReferences,
            'attachments' => $attachments,
            'realizationAvailability' => $transaction->realization?->budget_allocation_version_id
                ? $this->budgetAllocations->availability($transaction->realization->budget_allocation_version_id)
                : null,
            'labels' => $journal ? $this->journalLabels($journal) : $this->emptyJournalLabels(),
            'configurationStatus' => $this->configurationStatusForTransaction($entity, $transaction),
        ]);
    }

    public function allocationForm(Request $request)
    {
        $context = $this->context($request);
        $allocationHistory = $context['entity']
            ? $this->allocationHistory->page($context['entity']->id, ['per_page' => 20])
            : null;
        if ($allocationHistory && $context['entity']) {
            $activeDrafts = $this->realizationDrafts->activeByAllocationVersions(
                $context['entity']->id,
                $allocationHistory->getCollection()->pluck('version.id')->filter(),
            );
            $allocationHistory->setCollection($allocationHistory->getCollection()->map(function (array $summary) use ($activeDrafts): array {
                $versionId = $summary['version']?->id;
                $summary['active_realization_drafts'] = $versionId ? $activeDrafts->get($versionId, collect()) : collect();

                return $summary;
            }));
        }

        return view('masjid.mrj.admin.financial-v2.allocation-form', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'options' => $context['entity'] ? $this->formOptions($context['entity']) : $this->emptyOptions(),
            'allocationHistory' => $allocationHistory,
            'submissionKey' => old('submission_key', (string) Str::uuid()),
            'today' => now()->toDateString(),
            'editingAllocation' => null,
        ]);
    }

    public function editAllocation(Request $request, BudgetAllocation $allocation)
    {
        $entity = $this->entityForAllocation($request, $allocation);
        abort_unless($allocation->status === 'draft', 409, 'Hanya draft alokasi yang dapat diubah.');
        $context = $this->contextForEntity($entity->id);
        $history = $this->allocationHistory->page($entity->id, ['per_page' => 20]);

        return view('masjid.mrj.admin.financial-v2.allocation-form', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'options' => $this->formOptions($entity),
            'allocationHistory' => $history,
            'submissionKey' => Str::afterLast($allocation->idempotency_key, ':'),
            'today' => now()->toDateString(),
            'editingAllocation' => $allocation->load(['versions.fundings.fund']),
        ]);
    }

    public function allocationHistory(Request $request)
    {
        $context = $this->context($request);
        $filters = $request->validate([
            'entity' => ['nullable', 'uuid'],
            'from' => ['nullable', 'date'],
            'through' => ['nullable', 'date', 'after_or_equal:from'],
            'fund_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'in:draft,submitted,approved,cancelled,superseded'],
        ]);

        return view('masjid.mrj.admin.financial-v2.allocations.history', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'options' => $context['entity'] ? $this->formOptions($context['entity']) : $this->emptyOptions(),
            'filters' => $filters,
            'allocationHistory' => $context['entity'] ? $this->allocationHistory->page($context['entity']->id, $filters) : null,
            'sourceAllocationAudit' => $context['entity']?->code === 'MRJ-ACTUAL' ? MrjZiswafOpeningPosition::allocationSourceAudit() : null,
        ]);
    }

    public function realizationDrafts(Request $request)
    {
        $context = $this->context($request);
        $filters = $request->validate([
            'entity' => ['nullable', 'uuid'],
            'fund_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
        ]);

        return view('masjid.mrj.admin.financial-v2.realizations.drafts', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'options' => $context['entity'] ? $this->formOptions($context['entity']) : $this->emptyOptions(),
            'filters' => $filters,
            'drafts' => $context['entity'] ? $this->realizationDrafts->page($context['entity']->id, $filters) : null,
        ]);
    }

    public function funds(Request $request)
    {
        $context = $this->context($request);
        $funds = $context['entity'] ? $this->fundCards($context['entity']) : collect();

        return view('masjid.mrj.admin.financial-v2.funds.index', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'groups' => $this->fundGroups->groups($funds),
            'asOf' => now()->toDateString(),
        ]);
    }

    /**
     * Operational Fund grouping is a read-only navigation layer. It does not
     * add a financial dimension or mutate Fund, account, Journal, or Ledger
     * data; the selected Fund still opens its existing detail page.
     */
    public function fundGroup(Request $request, string $group)
    {
        $context = $this->context($request);
        $entity = $context['entity'];
        abort_unless($entity, 404);

        $fundGroup = $this->fundGroups->find($this->fundCards($entity), $group);
        abort_unless($fundGroup, 404);

        return view('masjid.mrj.admin.financial-v2.funds.group', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'group' => $fundGroup,
            'asOf' => now()->toDateString(),
        ]);
    }

    public function fundDetail(Request $request, Fund $fund)
    {
        $entity = $this->activeEntity($fund->accounting_entity_id);
        abort_unless($fund->status === 'active', 404);
        if ($request->filled('entity')) {
            abort_unless($request->string('entity')->toString() === $entity->id, 404);
        }
        $context = $this->contextForEntity($entity->id);
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'through' => ['nullable', 'date'],
            'type' => ['nullable', 'in:OPB,RCV,PAY,TRF,IFT,ADJ'],
            'program_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'financial_account_id' => ['nullable', 'uuid'],
            'status' => ['nullable', 'in:posted,reversed'],
        ]);
        $from = $filters['from'] ?? $fund->valid_from?->toDateString() ?? now()->startOfYear()->toDateString();
        $through = $filters['through'] ?? now()->toDateString();
        abort_if($from > $through, 422, 'Tanggal mulai tidak boleh melewati tanggal akhir.');
        $history = $this->fundHistory->history($entity, $fund, [
            'from' => $from,
            'through' => $through,
            'transaction_type_code' => $filters['type'] ?? null,
            'program_id' => $filters['program_id'] ?? null,
            'category_id' => $filters['category_id'] ?? null,
            'financial_account_id' => $filters['financial_account_id'] ?? null,
            'status' => $filters['status'] ?? null,
        ]);
        $report = $this->reports->report('fund-balance', $entity->id, $from, $through, ['fund_id' => $fund->id]);

        return view('masjid.mrj.admin.financial-v2.funds.show', [
            'entities' => $context['entities'],
            'entity' => $entity,
            'fund' => $fund->load(['type', 'restriction']),
            'report' => $report['data'],
            'from' => $from,
            'through' => $through,
            'filters' => $filters,
            'options' => $this->formOptions($entity),
            'fundHistory' => $history,
            'allocationHistory' => $this->allocationHistory->page($entity->id, ['fund_id' => $fund->id]),
            'allocationSummary' => $this->allocationHistory->summary($entity->id, $fund->id),
        ]);
    }

    public function storeAllocation(Request $request)
    {
        $entity = $this->requiredEntity($request);
        $input = $this->validatedAllocationInput($request);
        $idempotencyKey = $this->sourceKey('allocation', $input['submission_key']);

        try {
            $existing = BudgetAllocation::query()->where('accounting_entity_id', $entity->id)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return $this->success($request, 'Permintaan alokasi sebelumnya sudah diterima. Alokasi tidak dibuat ulang.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $existing->id, 'duplicate' => true]);
            }
            $period = AccountingPeriod::query()
                ->where('accounting_entity_id', $entity->id)
                ->where('status', 'open')
                ->where('start_date', '<=', $input['date'])
                ->where('end_date', '>=', $input['date'])
                ->first();
            if (! $period) {
                throw new FinancialDomainException('E-PERIOD-CLOSED', 'Tanggal alokasi tidak berada dalam periode yang terbuka.');
            }
            $program = $this->program($entity, $input['program_id'] ?? null);
            $category = $this->category($entity, $input['category_id'] ?? null);
            $amount = $this->amount($input['amount']);
            $allocation = $this->budgetAllocations->create([
                'accounting_entity_id' => $entity->id,
                'accounting_period_id' => $period->id,
                'fund_id' => $input['fund_id'] ?? null,
                'fundings' => $input['funding_sources'] ?? null,
                'program_id' => $program?->id,
                'category_id' => $category?->id,
                'allocation_reference' => 'UX-ALC-'.$input['submission_key'],
                'idempotency_key' => $idempotencyKey,
                'allocated_amount' => $amount,
                'effective_from' => $input['date'],
                'reason' => $input['reason'],
            ], $request->user()?->id);

            return $this->success($request, 'Draft alokasi dana disimpan. Alokasi ini belum merupakan pengeluaran dan tidak membuat jurnal.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $allocation->id]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function updateAllocation(Request $request, BudgetAllocation $allocation)
    {
        $entity = $this->entityForAllocation($request, $allocation);
        $input = $this->validatedAllocationInput($request, false);

        try {
            $period = AccountingPeriod::query()
                ->where('accounting_entity_id', $entity->id)
                ->where('status', 'open')
                ->where('start_date', '<=', $input['date'])
                ->where('end_date', '>=', $input['date'])
                ->first();
            if (! $period) {
                throw new FinancialDomainException('E-PERIOD-CLOSED', 'Tanggal alokasi tidak berada dalam periode yang terbuka.');
            }
            $program = $this->program($entity, $input['program_id'] ?? null);
            $category = $this->category($entity, $input['category_id'] ?? null);
            $updated = $this->budgetAllocations->updateDraft($allocation->id, [
                'accounting_period_id' => $period->id,
                'program_id' => $program?->id,
                'category_id' => $category?->id,
                'allocated_amount' => $this->amount($input['amount']),
                'effective_from' => $input['date'],
                'reason' => $input['reason'],
            ], $input['funding_sources'] ?? (($input['fund_id'] ?? null) ? [['fund_id' => $input['fund_id'], 'amount' => $input['amount']]] : null), $request->user()?->id);

            return $this->success($request, 'Draft alokasi dan Sumber Dana berhasil diperbarui. Tidak ada Journal atau Ledger yang dibuat.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $updated->id]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function createAllocationAmendment(Request $request, BudgetAllocation $allocation)
    {
        $entity = $this->entityForAllocation($request, $allocation);
        $input = $request->validate([
            'effective_from' => ['required', 'date'],
            'amendment_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'funding_adjustments' => ['required', 'array', 'min:1', 'max:20'],
            'funding_adjustments.*.fund_id' => ['required', 'uuid', 'distinct'],
            'funding_adjustments.*.amount' => ['nullable', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'funding_adjustments.*.note' => ['nullable', 'string', 'max:1000'],
            'reason' => ['required', 'string', 'max:2000'],
        ], $this->validationMessages());

        try {
            $current = BudgetAllocationVersion::query()
                ->with('fundings')
                ->where('budget_allocation_id', $allocation->id)
                ->where('status', 'approved')
                ->firstOrFail();
            $amendmentAmount = $this->amount($input['amendment_amount']);
            $adjustments = collect($input['funding_adjustments'])
                ->filter(fn (array $adjustment): bool => filled($adjustment['amount'] ?? null))
                ->map(function (array $adjustment) use ($entity): array {
                    $fund = $this->fund($entity, $adjustment['fund_id']);

                    return [
                        'fund_id' => $fund->id,
                        'amount' => $this->amount($adjustment['amount']),
                        'note' => filled($adjustment['note'] ?? null) ? trim((string) $adjustment['note']) : null,
                    ];
                });
            if ($adjustments->isEmpty()) {
                throw new FinancialDomainException('E-BUDGET-AMENDMENT-FUNDING', 'Minimal satu Sumber Dana perubahan wajib diisi.');
            }
            if (! DecimalAmount::equals(DecimalAmount::sum($adjustments->pluck('amount')), $amendmentAmount)) {
                throw new FinancialDomainException('E-BUDGET-AMENDMENT-MISMATCH', 'Total tambahan per Sumber Dana harus sama dengan nilai perubahan Alokasi.');
            }

            $fundings = $current->fundings
                ->mapWithKeys(fn ($funding): array => [$funding->fund_id => [
                    'fund_id' => $funding->fund_id,
                    'amount' => DecimalAmount::normalize($funding->amount),
                    'note' => $funding->note,
                    'source_reference' => $funding->source_reference,
                ]]);
            foreach ($adjustments as $adjustment) {
                $existing = $fundings->get($adjustment['fund_id'], [
                    'fund_id' => $adjustment['fund_id'],
                    'amount' => '0.00',
                    'note' => null,
                    'source_reference' => null,
                ]);
                $existing['amount'] = DecimalAmount::add($existing['amount'], $adjustment['amount']);
                $existing['note'] = $adjustment['note'] ?? $existing['note'];
                $fundings->put($adjustment['fund_id'], $existing);
            }

            $version = $this->budgetAllocations->revise(
                $allocation->id,
                DecimalAmount::add($current->allocated_amount, $amendmentAmount),
                $input['effective_from'],
                $input['reason'],
                $request->user()?->id,
                $fundings->values()->all(),
            );

            return $this->success($request, 'Perubahan alokasi disimpan sebagai versi baru dan menunggu persetujuan. Belum ada Journal atau Ledger yang dibuat.', route('financial-v2.allocations.create', ['entity' => $entity->id]), [
                'allocation_id' => $allocation->id,
                'allocation_version_id' => $version->id,
                'status' => $version->status,
            ]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function approveAllocationAmendment(Request $request, BudgetAllocation $allocation, BudgetAllocationVersion $version)
    {
        $entity = $this->entityForAllocation($request, $allocation);
        abort_unless($version->budget_allocation_id === $allocation->id, 404);

        try {
            $approved = $this->budgetAllocations->approveVersion($allocation->id, $version->id, $request->user()?->id);

            return $this->success($request, 'Perubahan alokasi disetujui. Versi sebelumnya digantikan dan draft realisasi lama yang belum dicatat otomatis dibatalkan.', route('financial-v2.allocations.create', ['entity' => $entity->id]), [
                'allocation_id' => $allocation->id,
                'allocation_version_id' => $approved->id,
                'status' => $approved->status,
            ]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function submitAllocation(Request $request, BudgetAllocation $allocation)
    {
        $entity = $this->entityForAllocation($request, $allocation);

        try {
            if ($allocation->status === 'draft') {
                $this->budgetAllocations->submit($allocation->id, $request->user()?->id);
            } elseif (! in_array($allocation->status, ['submitted', 'approved'], true)) {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Status alokasi tidak dapat diajukan.');
            }

            return $this->success($request, 'Alokasi dana telah diajukan untuk persetujuan. Ini tetap bukan pengeluaran dan belum membuat jurnal.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $allocation->id]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function approveAllocation(Request $request, BudgetAllocation $allocation)
    {
        $entity = $this->entityForAllocation($request, $allocation);

        try {
            $allocation->load('versions');
            if ($allocation->status === 'approved') {
                return $this->success($request, 'Alokasi dana ini sudah disetujui dan siap dipilih saat realisasi.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $allocation->id, 'already_approved' => true]);
            }
            if ($allocation->status !== 'submitted') {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Alokasi dana harus diajukan sebelum dapat disetujui.');
            }
            $version = $allocation->versions->where('status', 'draft')->sortByDesc('version_no')->first();
            if (! $version) {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Versi alokasi yang dapat disetujui tidak tersedia.');
            }
            $this->budgetAllocations->approveVersion($allocation->id, $version->id, $request->user()?->id);

            return $this->success($request, 'Alokasi dana telah disetujui dan siap digunakan untuk realisasi. Persetujuan ini tidak membuat jurnal atau Ledger.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $allocation->id, 'allocation_version_id' => $version->id]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function cancelAllocation(Request $request, BudgetAllocation $allocation)
    {
        $entity = $this->entityForAllocation($request, $allocation);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:2000'],
        ], [
            'reason.required' => 'Alasan pembatalan alokasi wajib diisi.',
        ]);

        try {
            $cancelled = $this->budgetAllocations->cancel($allocation->id, $data['reason'], $request->user()?->id);

            return $this->success($request, 'Alokasi dana dibatalkan. Tidak ada Journal, Ledger, atau perubahan saldo yang dibuat.', route('financial-v2.allocations.create', ['entity' => $entity->id]), ['allocation_id' => $cancelled->id, 'status' => $cancelled->status]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function options(Request $request)
    {
        $entity = $this->requiredEntity($request);
        $data = $request->validate([
            'type' => ['nullable', 'string', 'max:10'],
            'date' => ['nullable', 'date'],
            'financial_account_id' => ['nullable', 'uuid'],
            'fund_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
        ]);
        $typeCode = (string) ($data['type'] ?? '');
        $type = $typeCode === '' ? null : $this->transactionType($entity, $typeCode);
        $date = (string) ($data['date'] ?? now()->toDateString());
        $options = $this->formOptions($entity, $typeCode ?: null, false, $date);
        if ($type) {
            $options['categories'] = $options['categories']->filter(fn (Category $category) => ! $category->transaction_type_id || $category->transaction_type_id === $type->id)->values();
        }
        $programs = $options['programs'];
        $configurationFiltered = $type && $this->configurationResolver->supports($type->code)
            && collect(['financial_account_id', 'fund_id', 'category_id'])->every(fn (string $field): bool => filled($data[$field] ?? null));
        if ($configurationFiltered) {
            $programs = $this->configurationResolver->availablePrograms([
                'accounting_entity_id' => $entity->id,
                'transaction_type_id' => $type->id,
                'date' => $date,
                'financial_account_id' => $data['financial_account_id'],
                'fund_id' => $data['fund_id'],
                'category_id' => $data['category_id'],
            ]);
        }

        return response()->json([
            'ok' => true,
            'categories' => $options['categories']->map(fn (Category $category) => ['id' => $category->id, 'name' => $category->name])->values(),
            'programs' => $programs->map(fn (Program $program) => ['id' => $program->id, 'name' => $program->name])->values(),
            'configuration_filtered' => $configurationFiltered,
        ]);
    }

    public function preview(Request $request)
    {
        $entity = $this->requiredEntity($request);
        $data = $request->validate([
            'operation' => ['required', 'in:receipt,payment,transfer,interfund,realization'],
            'date' => ['nullable', 'date'],
            'financial_account_id' => ['nullable', 'uuid'],
            'source_financial_account_id' => ['nullable', 'uuid'],
            'destination_financial_account_id' => ['nullable', 'uuid'],
            'fund_id' => ['nullable', 'uuid'],
            'source_fund_id' => ['nullable', 'uuid'],
            'destination_fund_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
        ], $this->validationMessages());

        if (! $this->hasConfigurationPreviewInput($data)) {
            return response()->json([
                'ok' => true,
                'allowed' => false,
                'state' => 'incomplete',
                'message' => 'Lengkapi data transaksi untuk memeriksa konfigurasi.',
            ]);
        }

        try {
            $definition = $this->operation($data['operation']);
            $type = $this->transactionType($entity, $definition['code']);
            $fundIds = $data['operation'] === 'interfund'
                ? array_values(array_filter([$data['source_fund_id'] ?? null, $data['destination_fund_id'] ?? null]))
                : array_values(array_filter([$data['fund_id'] ?? null]));
            $resolved = $this->configurationResolver->resolve([
                'accounting_entity_id' => $entity->id,
                'transaction_type_id' => $type->id,
                'date' => $data['date'],
                'financial_account_id' => $data['financial_account_id'] ?? null,
                'source_financial_account_id' => $data['source_financial_account_id'] ?? null,
                'destination_financial_account_id' => $data['destination_financial_account_id'] ?? null,
                'fund_ids' => $fundIds,
                'source_fund_id' => $data['source_fund_id'] ?? null,
                'destination_fund_id' => $data['destination_fund_id'] ?? null,
                'program_id' => $data['program_id'] ?? null,
                'category_id' => $data['category_id'] ?? null,
            ]);

            return response()->json([
                'ok' => true,
                'allowed' => true,
                'state' => 'ready',
                'message' => '● Siap digunakan',
                'required_approval_steps' => $resolved->requiredApprovalSteps,
                'required_evidence' => $resolved->evidenceRequirements->pluck('evidence_type')->values(),
            ]);
        } catch (FinancialDomainException|FinancialPostingException|InvalidArgumentException $exception) {
            return response()->json([
                'ok' => false,
                'allowed' => false,
                'state' => 'missing',
                'message' => $this->configurationPreviewMessage($entity, $data),
            ], 422);
        }
    }

    /** @param array<string, mixed> $data */
    private function hasConfigurationPreviewInput(array $data): bool
    {
        $required = match ($data['operation']) {
            'receipt', 'payment' => ['date', 'financial_account_id', 'fund_id', 'category_id'],
            'transfer' => ['date', 'source_financial_account_id', 'destination_financial_account_id', 'fund_id'],
            'interfund' => ['date', 'financial_account_id', 'source_fund_id', 'destination_fund_id'],
            'realization' => ['date', 'financial_account_id', 'category_id'],
        };

        return collect($required)->every(fn (string $field): bool => filled($data[$field] ?? null));
    }

    /** @param array<string, mixed> $data */
    private function configurationPreviewMessage(AccountingEntity $entity, array $data): string
    {
        $accountIds = collect([
            $data['financial_account_id'] ?? null,
            $data['source_financial_account_id'] ?? null,
            $data['destination_financial_account_id'] ?? null,
        ])->filter()->unique()->values();
        $fundIds = collect([
            $data['fund_id'] ?? null,
            $data['source_fund_id'] ?? null,
            $data['destination_fund_id'] ?? null,
        ])->filter()->unique()->values();
        $accounts = FinancialAccount::query()
            ->where('accounting_entity_id', $entity->id)
            ->whereIn('id', $accountIds)
            ->orderBy('name')
            ->pluck('name');
        $funds = Fund::query()
            ->where('accounting_entity_id', $entity->id)
            ->whereIn('id', $fundIds)
            ->orderBy('name')
            ->pluck('name');
        $category = filled($data['category_id'] ?? null)
            ? Category::query()->where('accounting_entity_id', $entity->id)->whereKey($data['category_id'])->value('name')
            : null;
        $program = filled($data['program_id'] ?? null)
            ? Program::query()->where('accounting_entity_id', $entity->id)->whereKey($data['program_id'])->value('name')
            : null;
        $operationLabel = self::OPERATIONS[$data['operation'] ?? '']['label'] ?? ($data['transaction_type_label'] ?? null);

        $details = collect([
            'Tanggal: '.CarbonImmutable::parse((string) $data['date'])->format('d/m/Y'),
            $operationLabel ? 'Jenis transaksi: '.$operationLabel : null,
            $accounts->isNotEmpty() ? 'Rekening: '.$accounts->join(' → ') : null,
            $funds->isNotEmpty() ? 'Dana: '.$funds->join(' → ') : null,
            $category ? 'Kategori: '.$category : null,
            $program ? 'Program: '.$program : null,
        ])->filter()->join(' · ');

        return '○ Konfigurasi pencatatan belum tersedia untuk kombinasi ini. '.$details;
    }

    /** @return array{state: string, message: string} */
    private function configurationStatusForTransaction(AccountingEntity $entity, FinancialTransaction $transaction): array
    {
        try {
            if ($this->configurationResolver->supports($transaction->type?->code)) {
                $this->configurationResolver->resolveTransaction($transaction);
            } else {
                $this->configurationResolver->resolvePostingRuleVersionForTransaction($transaction);
            }

            return ['state' => 'ready', 'message' => '● Siap digunakan'];
        } catch (FinancialDomainException|FinancialPostingException|InvalidArgumentException) {
            $programIds = $transaction->splits->pluck('program_id')->filter()->unique();
            $data = [
                'date' => $transaction->accounting_date->toDateString(),
                'transaction_type_label' => $transaction->type?->name,
                'financial_account_id' => $transaction->primary_financial_account_id,
                'source_financial_account_id' => $transaction->treasuryTransfer?->source_financial_account_id,
                'destination_financial_account_id' => $transaction->treasuryTransfer?->destination_financial_account_id,
                'fund_id' => $transaction->splits->pluck('fund_id')->filter()->unique()->first(),
                'source_fund_id' => $transaction->interfundTransfer?->source_fund_id,
                'destination_fund_id' => $transaction->interfundTransfer?->destination_fund_id,
                'category_id' => $transaction->category_id,
                'program_id' => $programIds->count() === 1 ? $programIds->first() : null,
            ];

            return ['state' => 'missing', 'message' => $this->configurationPreviewMessage($entity, $data)];
        }
    }

    public function downloadAttachment(Request $request, Attachment $attachment)
    {
        $linked = AttachmentLink::query()
            ->where('attachment_id', $attachment->id)
            ->where('target_type', 'transaction')
            ->where('status', 'active')
            ->exists();
        abort_unless($linked && Storage::disk('local')->exists($attachment->storage_reference), 404);

        return Storage::disk('local')->download($attachment->storage_reference, $attachment->original_filename);
    }

    public function viewAttachment(Request $request, Attachment $attachment)
    {
        $linked = AttachmentLink::query()
            ->where('attachment_id', $attachment->id)
            ->where('target_type', 'transaction')
            ->where('status', 'active')
            ->exists();
        abort_unless($linked && Storage::disk('local')->exists($attachment->storage_reference), 404);

        return response()->file(Storage::disk('local')->path($attachment->storage_reference), [
            'Content-Type' => $attachment->media_type,
            'Content-Disposition' => 'inline; filename="'.$attachment->original_filename.'"',
        ]);
    }

    /** @return array{code: string, label: string, evidence: string} */
    private function operation(string $operation): array
    {
        abort_unless(array_key_exists($operation, self::OPERATIONS), 404);

        return self::OPERATIONS[$operation];
    }

    /** @return array{entities: \Illuminate\Support\Collection<int, AccountingEntity>, entity: ?AccountingEntity} */
    private function context(Request $request): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $requestedId = $request->query('entity', $request->input('entity'));
        $entity = $requestedId ? $entities->firstWhere('id', $requestedId) : ($entities->count() === 1 ? $entities->first() : null);

        return compact('entities', 'entity');
    }

    /** @return array{entities: \Illuminate\Support\Collection<int, AccountingEntity>, entity: AccountingEntity} */
    private function contextForEntity(string $entityId): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $entity = $entities->firstWhere('id', $entityId);
        abort_unless($entity, 404);

        return compact('entities', 'entity');
    }

    private function requiredEntity(Request $request): AccountingEntity
    {
        $data = $request->validate(['entity' => ['required', 'uuid']], $this->validationMessages());

        return $this->activeEntity($data['entity']);
    }

    private function activeEntity(string $entityId): AccountingEntity
    {
        $entity = AccountingEntity::query()->where('status', 'active')->find($entityId);
        if (! $entity) {
            throw new FinancialDomainException('E-UX-ENTITY', 'Entitas keuangan aktif belum dipilih atau belum tersedia.');
        }

        return $entity;
    }

    /** @return array<string, mixed> */
    private function formOptions(AccountingEntity|string $entity, ?string $transactionTypeCode = null, bool $includeAllocationVersions = true, ?string $date = null): array
    {
        $entityId = $entity instanceof AccountingEntity ? $entity->id : $entity;
        $date ??= now()->toDateString();
        $transactionTypeId = $transactionTypeCode
            ? TransactionType::query()->where('accounting_entity_id', $entityId)->where('code', $transactionTypeCode)->value('id')
            : null;
        $allocationVersions = collect();
        if ($includeAllocationVersions) {
            $allocationVersions = BudgetAllocationVersion::query()
                ->with(['allocation', 'fundings.fund'])
                ->where('accounting_entity_id', $entityId)
                ->where('status', 'approved')
                ->whereHas('allocation', fn (Builder $query) => $query->where('status', 'approved'))
                ->orderByDesc('effective_from')
                ->get();
            $allocationVersions->each(function (BudgetAllocationVersion $version): void {
                $version->setAttribute('availability', $this->budgetAllocations->availability($version->id));
                $version->setAttribute('funding_availability', $this->budgetAllocations->fundingAvailability($version->id));
            });
        }

        return [
            'financialAccounts' => FinancialAccount::query()
                ->where('accounting_entity_id', $entityId)
                ->where('status', 'active')
                ->where(fn (Builder $query) => $query->whereNull('closing_date')->orWhere('closing_date', '>=', $date))
                ->orderBy('name')->get(),
            'funds' => Fund::query()->where('accounting_entity_id', $entityId)->where('status', 'active')->orderBy('name')->get(),
            'programs' => Program::query()->where('accounting_entity_id', $entityId)->businessActiveOn($date)->orderBy('name')->get(),
            'categories' => Category::query()
                ->where('accounting_entity_id', $entityId)
                ->where('status', 'active')
                ->when($transactionTypeId, fn (Builder $query) => $query->where(fn (Builder $category) => $category->whereNull('transaction_type_id')->orWhere('transaction_type_id', $transactionTypeId)))
                ->orderBy('name')
                ->get(),
            'counterparties' => Counterparty::query()->where('accounting_entity_id', $entityId)->where('status', 'active')->orderBy('display_name')->get(),
            'periods' => AccountingPeriod::query()->where('accounting_entity_id', $entityId)->where('status', 'open')->orderBy('start_date')->get(),
            'allocationVersions' => $allocationVersions,
        ];
    }

    /** @return array<string, \Illuminate\Support\Collection> */
    private function emptyOptions(): array
    {
        return [
            'financialAccounts' => collect(), 'funds' => collect(), 'programs' => collect(), 'categories' => collect(),
            'counterparties' => collect(), 'periods' => collect(), 'allocationVersions' => collect(),
        ];
    }

    /** @return array<string, mixed> */
    private function validatedAllocationInput(Request $request, bool $requireSubmissionKey = true): array
    {
        $rules = [
            'date' => ['required', 'date'],
            'fund_id' => ['nullable', 'uuid', 'required_without:funding_sources'],
            'funding_sources' => ['nullable', 'array', 'min:1', 'max:20', 'required_without:fund_id'],
            'funding_sources.*.fund_id' => ['required_with:funding_sources', 'uuid', 'distinct'],
            'funding_sources.*.amount' => ['required_with:funding_sources', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'funding_sources.*.note' => ['nullable', 'string', 'max:1000'],
            'funding_sources.*.source_reference' => ['nullable', 'string', 'max:500'],
            'program_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
        if ($requireSubmissionKey) {
            $rules['submission_key'] = ['required', 'uuid'];
        }

        return $request->validate($rules, $this->validationMessages());
    }

    /** @return array<string, mixed> */
    private function validatedOperationInput(Request $request, string $operation): array
    {
        $rules = [
            'submission_key' => ['required', 'uuid'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'description' => ['nullable', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'attachments' => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'attachment_types' => ['nullable', 'array', 'max:10'],
            'attachment_types.*' => ['nullable', 'string', Rule::in(['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other'])],
        ];
        $rules += match ($operation) {
            'receipt' => [
                'source' => ['required', 'string', 'max:240'],
                'financial_account_id' => ['required', 'uuid'],
                'fund_id' => ['required', 'uuid'],
                'category_id' => ['required', 'uuid'],
                'program_id' => ['nullable', 'uuid'],
            ],
            'payment' => [
                'counterparty_id' => ['nullable', 'uuid', 'required_without:counterparty_name'],
                'counterparty_name' => ['nullable', 'string', 'max:240', 'required_without:counterparty_id'],
                'financial_account_id' => ['required', 'uuid'],
                'fund_id' => ['required', 'uuid'],
                'category_id' => ['required', 'uuid'],
                'program_id' => ['nullable', 'uuid'],
            ],
            'transfer' => [
                'source_financial_account_id' => ['required', 'uuid'],
                'destination_financial_account_id' => ['required', 'uuid', 'different:source_financial_account_id'],
                'fund_id' => ['required', 'uuid'],
            ],
            'interfund' => [
                'source_fund_id' => ['required', 'uuid'],
                'destination_fund_id' => ['required', 'uuid', 'different:source_fund_id'],
                'financial_account_id' => ['required', 'uuid'],
                'policy_basis_ref' => ['required', 'string', 'max:500'],
                'reason' => ['required', 'string', 'max:2000'],
            ],
            'realization' => [
                'budget_allocation_version_id' => ['required', 'uuid'],
                'counterparty_id' => ['nullable', 'uuid', 'required_without:counterparty_name'],
                'counterparty_name' => ['nullable', 'string', 'max:240', 'required_without:counterparty_id'],
                'financial_account_id' => ['required', 'uuid'],
                'category_id' => ['required', 'uuid'],
                'funding_sources' => ['nullable', 'array', 'min:1', 'max:20'],
                'funding_sources.*.fund_id' => ['required_with:funding_sources', 'uuid', 'distinct'],
                'funding_sources.*.amount' => ['required_with:funding_sources', 'regex:/^\d+(?:\.\d{1,2})?$/'],
                'funding_sources.*.note' => ['nullable', 'string', 'max:1000'],
                'funding_sources.*.source_reference' => ['nullable', 'string', 'max:500'],
            ],
        };

        return $request->validate($rules, $this->validationMessages());
    }

    private function createReceipt(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, ?int $actorId): FinancialTransaction
    {
        $financialAccount = $this->financialAccount($entity, $input['financial_account_id']);
        $fund = $this->fund($entity, $input['fund_id']);
        $program = $this->program($entity, $input['program_id'] ?? null);
        $category = $this->category($entity, $input['category_id'], $type->id);
        $amount = $this->amount($input['amount']);
        $resolved = $this->configurationResolver->resolve([
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $type->id,
            'date' => $input['date'],
            'financial_account_id' => $financialAccount->id,
            'fund_id' => $fund->id,
            'category_id' => $category->id,
            'program_id' => $program?->id,
        ]);
        $splitAccountId = $resolved->businessAccountId;

        return $this->lifecycle->createReceipt(array_merge($this->transactionInput($entity, $type, $input, $sourceKey), [
            'primary_financial_account_id' => $financialAccount->id,
            'category_id' => $category->id,
            'description' => $this->description($input['source'], $input['description'] ?? null),
            'gross_amount' => $amount,
        ]), [[
            'account_id' => $splitAccountId,
            'split_amount' => $amount,
            'fund_id' => $fund->id,
            'program_id' => $program?->id,
            'category_id' => $category->id,
        ]], $actorId);
    }

    private function createPayment(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, ?int $actorId): FinancialTransaction
    {
        return $this->createPaymentLike($entity, $type, $input, $sourceKey, $actorId);
    }

    private function createRealization(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, ?int $actorId): FinancialTransaction
    {
        [$fundId, $programId, $versionId] = $this->realizationDimensions($entity, $input['budget_allocation_version_id']);
        $fundings = $this->realizationFundingSources($entity, $versionId, $input['funding_sources'] ?? null, $this->amount($input['amount']));
        $prepared = $this->paymentInput($entity, $type, $input, $sourceKey, $fundId, $programId, $actorId, 'beneficiary', $fundings);

        return $this->lifecycle->createRealization($prepared['input'], $prepared['splits'], $versionId, $actorId);
    }

    private function createPaymentLike(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, ?int $actorId): FinancialTransaction
    {
        $prepared = $this->paymentInput($entity, $type, $input, $sourceKey, $input['fund_id'], $input['program_id'] ?? null, $actorId, 'supplier');

        return $this->lifecycle->createPayment($prepared['input'], $prepared['splits'], $actorId);
    }

    /** @return array{input: array<string, mixed>, splits: array<int, array<string, mixed>>} */
    private function paymentInput(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, string $fundId, ?string $programId, ?int $actorId, string $counterpartyType, ?array $fundingSources = null): array
    {
        $financialAccount = $this->financialAccount($entity, $input['financial_account_id']);
        $fund = $this->fund($entity, $fundId);
        $program = $this->program($entity, $programId);
        $category = $this->category($entity, $input['category_id'], $type->id);
        $amount = $this->amount($input['amount']);
        $resolved = $this->configurationResolver->resolve([
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $type->id,
            'date' => $input['date'],
            'financial_account_id' => $financialAccount->id,
            'fund_ids' => collect($fundingSources ?? [['fund_id' => $fund->id]])->pluck('fund_id')->all(),
            'category_id' => $category->id,
            'program_id' => $program?->id,
        ]);
        $splitAccountId = $resolved->businessAccountId;
        $counterparty = $this->counterpartyFromInput($entity, $input, $actorId, $counterpartyType);

        return [
            'input' => $this->transactionInput($entity, $type, $input, $sourceKey) + [
                'primary_financial_account_id' => $financialAccount->id,
                'counterparty_id' => $counterparty->id,
                'category_id' => $category->id,
                'gross_amount' => $amount,
            ],
            'splits' => collect($fundingSources ?? [['fund_id' => $fund->id, 'amount' => $amount]])->values()->map(fn (array $funding): array => [
                'account_id' => $splitAccountId,
                'split_amount' => $funding['amount'],
                'fund_id' => $funding['fund_id'],
                'program_id' => $program?->id,
                'category_id' => $category->id,
                'counterparty_id' => $counterparty->id,
                'purpose_note' => $funding['note'] ?? null,
                'source_reference' => $funding['source_reference'] ?? null,
            ])->all(),
        ];
    }

    private function createTreasuryTransfer(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, ?int $actorId): FinancialTransaction
    {
        $source = $this->financialAccount($entity, $input['source_financial_account_id']);
        $destination = $this->financialAccount($entity, $input['destination_financial_account_id']);
        $fund = $this->fund($entity, $input['fund_id']);
        $amount = $this->amount($input['amount']);
        $this->configurationResolver->resolve([
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $type->id,
            'date' => $input['date'],
            'source_financial_account_id' => $source->id,
            'destination_financial_account_id' => $destination->id,
            'fund_id' => $fund->id,
        ]);

        return $this->lifecycle->createTreasuryTransfer($this->transactionInput($entity, $type, $input, $sourceKey) + [
            'source_financial_account_id' => $source->id,
            'destination_financial_account_id' => $destination->id,
            'gross_amount' => $amount,
        ], [[
            'account_id' => $source->account_id,
            'split_amount' => $amount,
            'fund_id' => $fund->id,
        ]], $actorId);
    }

    private function createInterfundTransfer(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey, ?int $actorId): FinancialTransaction
    {
        $source = $this->fund($entity, $input['source_fund_id']);
        $destination = $this->fund($entity, $input['destination_fund_id']);
        $financialAccount = $this->financialAccount($entity, $input['financial_account_id']);
        $this->configurationResolver->resolve([
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $type->id,
            'date' => $input['date'],
            'financial_account_id' => $financialAccount->id,
            'fund_ids' => [$source->id, $destination->id],
            'source_fund_id' => $source->id,
            'destination_fund_id' => $destination->id,
        ]);

        return $this->lifecycle->createInterfundTransfer($this->transactionInput($entity, $type, $input, $sourceKey) + [
            'primary_financial_account_id' => $financialAccount->id,
            'source_fund_id' => $source->id,
            'destination_fund_id' => $destination->id,
            'policy_basis_ref' => $input['policy_basis_ref'],
            'reason' => $input['reason'],
            'gross_amount' => $this->amount($input['amount']),
        ], $actorId);
    }

    /** @return array<string, mixed> */
    private function transactionInput(AccountingEntity $entity, TransactionType $type, array $input, string $sourceKey): array
    {
        return [
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $type->id,
            'business_date' => $input['date'],
            'accounting_date' => $input['date'],
            'source_reference' => 'UX-'.$type->code.'-'.$input['submission_key'],
            'idempotency_key' => $sourceKey,
            'description' => $input['description'] ?? null,
        ];
    }

    private function financialAccount(AccountingEntity $entity, ?string $id): ?FinancialAccount
    {
        if (! $id) {
            return null;
        }
        $financialAccount = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->find($id);
        if (! $financialAccount) {
            throw new FinancialDomainException('E-UX-FINANCIAL-ACCOUNT', 'Kas atau rekening yang dipilih tidak aktif atau tidak tersedia.');
        }

        return $financialAccount;
    }

    private function fund(AccountingEntity $entity, ?string $id): ?Fund
    {
        if (! $id) {
            return null;
        }
        $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->find($id);
        if (! $fund) {
            throw new FinancialDomainException('E-UX-FUND', 'Dana yang dipilih tidak aktif atau tidak tersedia.');
        }

        return $fund;
    }

    private function program(AccountingEntity $entity, ?string $id): ?Program
    {
        if (! $id) {
            return null;
        }
        $program = Program::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->find($id);
        if (! $program) {
            throw new FinancialDomainException('E-UX-PROGRAM', 'Program yang dipilih tidak aktif atau tidak tersedia.');
        }

        return $program;
    }

    private function category(AccountingEntity $entity, ?string $id, ?string $typeId = null): ?Category
    {
        if (! $id) {
            return null;
        }
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->find($id);
        if (! $category || ($typeId && $category->transaction_type_id && $category->transaction_type_id !== $typeId)) {
            throw new FinancialDomainException('E-UX-CATEGORY', 'Kategori yang dipilih tidak dapat digunakan untuk jenis transaksi ini.');
        }

        return $category;
    }

    private function counterparty(AccountingEntity $entity, ?string $id): ?Counterparty
    {
        if (! $id) {
            return null;
        }
        $counterparty = Counterparty::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->find($id);
        if (! $counterparty) {
            throw new FinancialDomainException('E-UX-COUNTERPARTY', 'Pihak yang dipilih tidak aktif atau tidak tersedia.');
        }

        return $counterparty;
    }

    /**
     * Resolves an existing counterparty or creates the explicitly named operational recipient.
     * This only creates a master record; the financial fact still flows through the lifecycle
     * and Posting Engine when the transaction is posted.
     *
     * @param  array<string, mixed>  $input
     */
    private function counterpartyFromInput(AccountingEntity $entity, array $input, ?int $actorId, string $partyType): Counterparty
    {
        if (! empty($input['counterparty_id'])) {
            return $this->counterparty($entity, $input['counterparty_id']);
        }

        $displayName = trim((string) ($input['counterparty_name'] ?? ''));
        if ($displayName === '') {
            throw new FinancialDomainException('E-UX-COUNTERPARTY', 'Nama pihak yang dibayarkan wajib diisi.');
        }

        $normalizedName = Str::lower($displayName);
        $existing = Counterparty::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('status', 'active')
            ->whereRaw('LOWER(display_name) = ?', [$normalizedName])
            ->first();
        if ($existing) {
            return $existing;
        }

        $counterparty = Counterparty::query()->firstOrCreate(
            [
                'accounting_entity_id' => $entity->id,
                'code' => 'MANUAL-'.substr(hash('sha256', $normalizedName), 0, 32),
            ],
            [
                'party_type' => $partyType,
                'display_name' => $displayName,
                'status' => 'active',
                'created_by_user_id' => $actorId,
                'updated_by_user_id' => $actorId,
            ],
        );

        if ($counterparty->status !== 'active') {
            throw new FinancialDomainException('E-UX-COUNTERPARTY', 'Pihak yang dibayarkan tidak aktif atau tidak tersedia.');
        }

        return $counterparty;
    }

    private function transactionType(AccountingEntity $entity, string $code): TransactionType
    {
        $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', $code)->where('status', 'active')->first();
        if (! $type) {
            throw new FinancialDomainException('E-UX-TRANSACTION-TYPE', 'Konfigurasi jenis transaksi belum siap untuk digunakan.');
        }

        return $type;
    }

    /** @return array{0: string, 1: ?string, 2: string} */
    private function realizationDimensions(AccountingEntity $entity, string $versionId): array
    {
        $version = BudgetAllocationVersion::query()->with('allocation')->where('accounting_entity_id', $entity->id)->where('status', 'approved')->find($versionId);
        if (! $version || ! $version->allocation || $version->allocation->status !== 'approved') {
            throw new FinancialDomainException('E-REALIZATION-ALLOCATION', 'Alokasi dana yang dipilih belum disetujui atau tidak tersedia.');
        }

        return [$version->allocation->fund_id, $version->allocation->program_id, $version->id];
    }

    /**
     * Resolve and validate the operational funding split before creating a
     * PAY draft. PostingEngine repeats the authoritative, locked checks when
     * the realization is posted.
     *
     * @param  array<int, array<string, mixed>>|null  $requested
     * @return array<int, array{fund_id:string,amount:string,note:?string,source_reference:?string}>
     */
    private function realizationFundingSources(AccountingEntity $entity, string $versionId, ?array $requested, string $total): array
    {
        $available = collect($this->budgetAllocations->fundingAvailability($versionId))->keyBy('fund_id');
        if ($available->isEmpty()) {
            throw new FinancialDomainException('E-REALIZATION-FUNDING', 'Sumber Dana alokasi belum tersedia.');
        }

        $requested = collect($requested ?? [])
            ->filter(fn (mixed $line): bool => is_array($line) && (filled($line['fund_id'] ?? null) || filled($line['amount'] ?? null)))
            ->values()
            ->all();
        if ($requested === []) {
            if ($available->count() !== 1) {
                throw new FinancialDomainException('E-REALIZATION-FUNDING-REQUIRED', 'Realisasi alokasi multi-Dana wajib merinci seluruh Sumber Dana.');
            }
            $requested = [['fund_id' => $available->keys()->first(), 'amount' => $total]];
        }

        $normalized = collect($requested)->map(function (array $line) use ($entity, $available): array {
            $fundId = (string) ($line['fund_id'] ?? '');
            if (! $available->has($fundId)) {
                throw new FinancialDomainException('E-REALIZATION-FUNDING', 'Sumber Dana realisasi harus berasal dari alokasi yang dipilih.');
            }
            $this->fund($entity, $fundId);
            $amount = $this->amount((string) ($line['amount'] ?? '0'));

            return [
                'fund_id' => $fundId,
                'amount' => $amount,
                'note' => filled($line['note'] ?? null) ? trim((string) $line['note']) : null,
                'source_reference' => filled($line['source_reference'] ?? null) ? trim((string) $line['source_reference']) : null,
            ];
        });
        if ($normalized->pluck('fund_id')->duplicates()->isNotEmpty()) {
            throw new FinancialDomainException('E-REALIZATION-FUNDING-DUPLICATE', 'Satu Dana hanya boleh muncul satu kali pada Sumber Dana realisasi.');
        }
        if (! DecimalAmount::equals(DecimalAmount::sum($normalized->pluck('amount')), $total)) {
            throw new FinancialDomainException('E-REALIZATION-FUNDING-MISMATCH', 'Total Sumber Dana harus sama dengan nominal realisasi.');
        }

        return $normalized->all();
    }

    private function amount(string $rawAmount): string
    {
        $amount = DecimalAmount::normalize($rawAmount);
        if (DecimalAmount::compare($amount, '0.00') <= 0) {
            throw new FinancialDomainException('E-UX-AMOUNT', 'Nominal harus lebih besar dari nol.');
        }

        return $amount;
    }

    private function sourceKey(string $scope, string $submissionKey): string
    {
        return 'ux:'.$scope.':'.$submissionKey;
    }

    private function description(?string $source, ?string $description): ?string
    {
        $source = $source ? 'Sumber: '.$source : null;
        $description = blank($description) ? null : trim((string) $description);

        return implode("\n\n", array_filter([$source, $description]));
    }

    private function attachUploadIfPresent(Request $request, AccountingEntity $entity, FinancialTransaction $transaction, string $evidenceType, ?int $actorId): void
    {
        $uploads = collect($request->file('attachments', []));
        if ($request->file('attachment')) {
            $uploads->prepend($request->file('attachment'));
        }
        $types = collect($request->input('attachment_types', []));
        $uploads->values()->each(function ($upload, int $index) use ($entity, $transaction, $types, $evidenceType, $actorId): void {
            $this->evidenceUploads->attach(
                $entity->id,
                $transaction->id,
                $upload,
                $types->get($index) ?: $evidenceType,
                $actorId,
            );
        });
    }

    public function removeAttachment(Request $request, AttachmentLink $attachmentLink)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $entity = $this->activeEntity($attachmentLink->accounting_entity_id);
        abort_unless($attachmentLink->target_type === 'transaction', 404);

        try {
            $link = $this->evidence->removeDraftTransactionEvidence($attachmentLink->id, $data['reason'], $request->user()?->id);

            return $this->success($request, 'Lampiran dilepas dari draft dan tetap tercatat dalam audit.', route('financial-v2.transactions.show', $link->target_id), ['attachment_link_id' => $link->id, 'entity_id' => $entity->id]);
        } catch (FinancialDomainException $exception) {
            return $this->failure($request, $exception);
        }
    }

    private function ensureDraftIsEditable(FinancialTransaction $transaction): void
    {
        if ($transaction->status !== 'draft') {
            if ($transaction->status === 'cancelled' && $transaction->realization) {
                throw new FinancialDomainException('E-REALIZATION-PARENT-CANCELLED', 'Draft Realisasi sudah dibatalkan dan tidak dapat dilanjutkan.');
            }
            throw new FinancialDomainException('E-UX-POSTED-IMMUTABLE', 'Transaksi yang sudah dicatat tidak dapat diubah langsung. Gunakan koreksi atau reversal sesuai kewenangan.');
        }
    }

    private function entityForAllocation(Request $request, BudgetAllocation $allocation): AccountingEntity
    {
        $data = $request->validate(['entity' => ['required', 'uuid']]);
        $entity = $this->activeEntity($data['entity']);
        abort_unless($allocation->accounting_entity_id === $entity->id, 404);

        return $entity;
    }

    private function operationForTransaction(FinancialTransaction $transaction): string
    {
        return match ($transaction->type?->code) {
            'OPB' => 'opening',
            'RCV' => 'receipt',
            'PAY' => $transaction->realization ? 'realization' : 'payment',
            'TRF' => 'transfer',
            'IFT' => 'interfund',
            default => abort(404),
        };
    }

    private function historyQuery(AccountingEntity $entity, array $filters): Builder
    {
        $query = FinancialTransaction::query()
            ->with([
                'type',
                'primaryFinancialAccount',
                'counterparty',
                'category',
                'splits.fund',
                'interfundTransfer.sourceFund',
                'interfundTransfer.destinationFund',
            ])
            ->where('accounting_entity_id', $entity->id)
            ->latest('accounting_date')->latest('created_at');
        if (! empty($filters['period'])) {
            [$year, $month] = explode('-', $filters['period']);
            $query->whereYear('accounting_date', $year)->whereMonth('accounting_date', $month);
        }
        if (! empty($filters['type'])) {
            $query->whereHas('type', fn (Builder $type) => $type->where('code', $filters['type']));
        }
        if (! empty($filters['financial_account_id'])) {
            $query->where('primary_financial_account_id', $filters['financial_account_id']);
        }
        if (! empty($filters['fund_id'])) {
            $fundId = $filters['fund_id'];
            $query->where(function (Builder $fund) use ($fundId): void {
                $fund->whereHas('splits', fn (Builder $splits) => $splits->where('fund_id', $fundId))
                    ->orWhereHas('interfundTransfer', fn (Builder $transfer) => $transfer
                        ->where('source_fund_id', $fundId)
                        ->orWhere('destination_fund_id', $fundId));
            });
        }
        foreach (['program_id', 'category_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->whereHas('splits', fn (Builder $splits) => $splits->where($field, $filters[$field]));
            }
        }
        if (($filters['status'] ?? 'posted') !== 'all') {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $where) use ($search): void {
                $where->where('source_reference', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('counterparty', fn (Builder $counterparty) => $counterparty->where('display_name', 'like', '%'.$search.'%'));
            });
        }

        return $query;
    }

    /** @return array<string, mixed> */
    private function dashboardSummary(AccountingEntity $entity): array
    {
        $today = now()->toDateString();
        $periodStart = now()->startOfMonth()->toDateString();
        $financialAccounts = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->orderBy('name')->get();
        $accountBalances = $financialAccounts->map(function (FinancialAccount $account) use ($entity, $today, $periodStart): array {
            $balance = $this->balances->financialAccountBalance($entity->id, $account->id, $today);

            return [
                'id' => $account->id,
                'name' => $account->name,
                'type' => $account->account_type,
                'balance' => $balance['balance'],
                'periodChange' => $this->balances->financialAccountMovement($entity->id, $account->id, $periodStart, $today),
            ];
        });
        $fundBalances = $this->fundCards($entity)->map(fn (array $card): array => [
            'id' => $card['fund']->id,
            'name' => $card['fund']->name,
            'classification' => $card['fund']->type?->classification,
            'fundBalance' => $card['fund_balance'],
            'availableLiquidity' => $card['available_liquidity'],
        ]);
        $activity = $this->periodActivity($entity, $today);
        $recent = FinancialTransaction::query()->with(['type', 'primaryFinancialAccount', 'splits.fund'])->where('accounting_entity_id', $entity->id)->where('status', 'posted')->latest('accounting_date')->latest('created_at')->limit(8)->get();
        $currentPeriod = AccountingPeriod::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderByDesc('start_date')
            ->first();
        $unresolvedReconciliations = Reconciliation::query()
            ->where('accounting_entity_id', $entity->id)
            ->whereIn('status', ['draft', 'in_progress', 'reviewed', 'exception'])
            ->count();

        return [
            'totalBalance' => DecimalAmount::sum($accountBalances->pluck('balance')),
            'financialAccounts' => $accountBalances,
            'funds' => $fundBalances,
            'activity' => $activity,
            'recent' => $recent,
            'asOf' => $today,
            'controls' => [
                'periodName' => $currentPeriod?->period_name,
                'periodStatus' => $currentPeriod?->status,
                'unresolvedReconciliations' => $unresolvedReconciliations,
            ],
        ];
    }

    /** @return array{receipts: string, payments: string, transfers: string} */
    private function periodActivity(AccountingEntity $entity, string $today): array
    {
        $base = FinancialTransaction::query()
            ->where('financial_v2_transactions.accounting_entity_id', $entity->id)
            ->where('financial_v2_transactions.status', 'posted')
            ->whereYear('financial_v2_transactions.accounting_date', substr($today, 0, 4))
            ->whereMonth('financial_v2_transactions.accounting_date', substr($today, 5, 2))
            ->join('financial_v2_transaction_types as transaction_type', 'transaction_type.id', '=', 'financial_v2_transactions.transaction_type_id');

        $sum = fn (string $code): string => DecimalAmount::normalize((string) (clone $base)->where('transaction_type.code', $code)->sum('financial_v2_transactions.gross_amount'));

        return ['receipts' => $sum('RCV'), 'payments' => $sum('PAY'), 'transfers' => $sum('TRF')];
    }

    /** @return array<string, mixed> */
    private function emptyDashboardSummary(): array
    {
        return ['totalBalance' => '0.00', 'financialAccounts' => collect(), 'funds' => collect(), 'activity' => ['receipts' => '0.00', 'payments' => '0.00', 'transfers' => '0.00'], 'recent' => collect(), 'asOf' => now()->toDateString(), 'controls' => ['periodName' => null, 'periodStatus' => null, 'unresolvedReconciliations' => 0]];
    }

    /** @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function fundCards(AccountingEntity $entity): \Illuminate\Support\Collection
    {
        $through = now()->toDateString();
        // Fund balance is a net-position measure. It must not be substituted
        // with a liquidity distribution even when the two happen to tie out.
        $report = $this->reports->report('fund-balance', $entity->id, now()->startOfYear()->toDateString(), $through)['data'];
        $rows = collect($report['rows'])
            ->keyBy('fund_id');
        $accountsByFund = collect($report['account_composition'])
            ->filter(fn (array $row): bool => ! DecimalAmount::equals($row['liquidity_balance'], '0.00'))
            ->groupBy('fund_id')
            ->map(fn (\Illuminate\Support\Collection $rows): array => $rows
                ->pluck('financial_account_name')
                ->filter()
                ->unique()
                ->sort()
                ->values()
                ->all());

        return Fund::query()
            ->with(['type', 'restriction'])
            ->where('accounting_entity_id', $entity->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get()
            ->map(function (Fund $fund) use ($rows, $accountsByFund): array {
                $row = $rows->get($fund->id, []);

                return [
                    'fund' => $fund,
                    'fund_balance' => $row['fund_balance'] ?? '0.00',
                    'available_liquidity' => $row['available_liquidity'] ?? '0.00',
                    'financial_accounts' => $accountsByFund->get($fund->id, []),
                ];
            });
    }

    /** @param \Illuminate\Support\Collection<int, BudgetAllocation> $allocations @return \Illuminate\Support\Collection<int, array<string, mixed>> */
    private function allocationSummaries(\Illuminate\Support\Collection $allocations): \Illuminate\Support\Collection
    {
        return $allocations->map(function (BudgetAllocation $allocation): array {
            $version = $allocation->versions->sortByDesc('version_no')->first();
            $availability = $version && $allocation->status === 'approved' && $version->status === 'approved'
                ? $this->budgetAllocations->availability($version->id)
                : null;
            $realizationLabel = match (true) {
                $availability === null => 'Belum dapat direalisasikan',
                DecimalAmount::equals($availability['actual'], '0.00') => 'Belum direalisasikan',
                DecimalAmount::equals($availability['available'], '0.00') => 'Selesai direalisasikan',
                default => 'Sebagian direalisasikan',
            };
            $statusLabel = match ($allocation->status) {
                'draft' => 'Draft',
                'submitted' => 'Diajukan',
                'approved' => 'Disetujui',
                default => ucfirst((string) $allocation->status),
            };

            return compact('allocation', 'version', 'availability', 'realizationLabel', 'statusLabel');
        });
    }

    /** @return array<string, string> */
    private function journalLabels(Journal $journal): array
    {
        $ids = $journal->lines->flatMap(fn ($line) => [$line->account_id, $line->fund_id, $line->financial_account_id, $line->program_id])->filter()->unique();

        return [
            'accounts' => \App\Models\FinancialV2\Account::query()->whereIn('id', $ids)->pluck('name', 'id')->all(),
            'funds' => Fund::query()->whereIn('id', $ids)->pluck('name', 'id')->all(),
            'financialAccounts' => FinancialAccount::query()->whereIn('id', $ids)->pluck('name', 'id')->all(),
            'programs' => Program::query()->whereIn('id', $ids)->pluck('name', 'id')->all(),
        ];
    }

    /** @return array<string, array<string, string>> */
    private function emptyJournalLabels(): array
    {
        return ['accounts' => [], 'funds' => [], 'financialAccounts' => [], 'programs' => []];
    }

    private function success(Request $request, string $message, string $redirect, array $payload = [])
    {
        if ($request->expectsJson()) {
            $request->session()->flash('success', $message);

            return response()->json(['ok' => true, 'message' => $message, 'redirect' => $redirect] + $payload);
        }

        return redirect($redirect)->with('success', $message);
    }

    private function failure(Request $request, \Throwable $exception)
    {
        $message = $this->humanMessage($exception);
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message, 'code' => $exception instanceof FinancialDomainException || $exception instanceof FinancialPostingException ? $exception->failureCode : 'E-UX-UNKNOWN'], 422);
        }

        return back()->withInput()->withErrors(['financial' => $message]);
    }

    private function humanMessage(\Throwable $exception): string
    {
        $code = $exception instanceof FinancialDomainException || $exception instanceof FinancialPostingException ? $exception->failureCode : null;

        return match ($code) {
            'E-FUND-RESTRICTED' => 'Penggunaan dana belum dapat dilakukan karena aturan penggunaan dana belum dikonfigurasi.',
            'E-FUND-INSUFFICIENT' => 'Saldo dana tidak mencukupi untuk transaksi ini.',
            'E-PERIOD-CLOSED', 'E-PERIOD-REOPEN-SCOPE' => 'Periode pada tanggal tersebut belum terbuka atau sudah ditutup sehingga transaksi tidak dapat dicatat.',
            'E-EVIDENCE-REQUIRED' => 'Bukti yang diwajibkan oleh aturan pencatatan belum dilampirkan.',
            'E-APPROVAL-REQUIRED' => 'Transaksi menunggu persetujuan yang dikonfigurasi sebelum dapat dicatat resmi.',
            'E-CONFIGURATION-MISSING' => $exception->getMessage(),
            'E-RULE-NOT-EFFECTIVE', 'E-UX-TRANSACTION-TYPE' => 'Konfigurasi pencatatan belum tersedia untuk kombinasi transaksi ini.',
            'E-UX-POSTED-IMMUTABLE' => 'Transaksi yang sudah dicatat tidak dapat diubah langsung. Gunakan koreksi atau reversal sesuai kewenangan.',
            'E-UX-DUPLICATE', 'E-DUPLICATE-POSTING' => 'Permintaan yang sama sudah diterima. Sistem tidak membuat pencatatan ganda.',
            'E-MASTER-INACTIVE', 'E-UX-FUND', 'E-UX-FINANCIAL-ACCOUNT', 'E-UX-PROGRAM', 'E-UX-COUNTERPARTY', 'E-UX-CATEGORY' => 'Pilihan master tidak aktif, tidak sesuai, atau tidak tersedia untuk transaksi ini.',
            'E-FINANCIAL-ACCOUNT', 'E-FINANCIAL-ACCOUNT-DETAIL' => 'Kas atau rekening tidak siap digunakan untuk pencatatan ini.',
            'E-REALIZATION-ALLOCATION', 'E-BUDGET-INSUFFICIENT' => 'Alokasi dana tidak tersedia atau sisa alokasinya tidak mencukupi untuk realisasi ini.',
            'E-REALIZATION-PARENT-CANCELLED' => 'Draft Realisasi tidak dapat dilanjutkan karena alokasi induknya sudah dibatalkan.',
            'E-TRANSACTION-STATE' => 'Status transaksi belum memenuhi syarat untuk tindakan ini.',
            default => 'Transaksi belum dapat diproses. Periksa data yang diisi dan konfigurasi master yang berlaku.',
        };
    }

    /** @return array<string, string> */
    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'uuid' => ':attribute tidak valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'different' => ':attribute harus berbeda.',
            'regex' => ':attribute harus berupa nominal dengan maksimal dua angka desimal.',
            'attachment.mimes' => 'Bukti hanya dapat berupa JPG, JPEG, PNG, atau PDF.',
            'attachment.max' => 'Ukuran bukti maksimal 10 MB.',
        ];
    }
}
