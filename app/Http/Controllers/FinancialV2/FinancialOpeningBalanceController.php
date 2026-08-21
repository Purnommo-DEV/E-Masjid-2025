<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\OpeningBalanceService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\Program;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Plain-language Saldo Awal UX. It delegates all state and facts handling to
 * OpeningBalanceService/EvidenceService; it never writes Journal or Ledger.
 */
final class FinancialOpeningBalanceController
{
    public function __construct(private readonly OpeningBalanceService $openingBalances, private readonly EvidenceService $evidence) {}

    public function index(Request $request)
    {
        $context = $this->context($request->query('entity'));
        $entity = $context['entity'];
        $batches = $entity ? OpeningBalanceBatch::query()->with(['period', 'mappingSet', 'lines'])->where('accounting_entity_id', $entity->id)->orderByDesc('created_at')->get() : collect();
        $masters = $entity ? $this->masters($entity) : ['periods' => collect(), 'mappingSets' => collect(), 'accounts' => collect(), 'financialAccounts' => collect(), 'funds' => collect(), 'programs' => collect()];

        return view('masjid.mrj.admin.financial-v2.opening-balances.index', compact('entity', 'batches', 'masters') + ['entities' => $context['entities']]);
    }

    public function show(Request $request, OpeningBalanceBatch $batch)
    {
        $entity = $this->activeEntity($request->query('entity'));
        abort_unless($batch->accounting_entity_id === $entity->id, 404);
        $batch->load(['period', 'mappingSet', 'lines.account', 'lines.financialAccount', 'lines.fund', 'lines.program']);
        $summary = $this->openingBalances->summary($batch);

        return view('masjid.mrj.admin.financial-v2.opening-balances.show', compact('entity', 'batch', 'summary') + $this->masters($entity));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'entity' => ['required', 'uuid'], 'accounting_period_id' => ['required', 'uuid'], 'mapping_set_id' => ['required', 'uuid'],
            'position_date' => ['required', 'date'], 'rehearsal_reference' => ['required', 'string', 'max:120'], 'evidence_package_ref' => ['required', 'string', 'max:700'],
        ]);
        $entity = $this->activeEntity($data['entity']);

        try {
            $batch = $this->openingBalances->createDraft($data + ['accounting_entity_id' => $entity->id], $request->user()?->id);

            return redirect()->route('financial-v2.opening-balances.show', ['openingBalanceBatch' => $batch->id, 'entity' => $entity->id])->with('success', 'Draft Saldo Awal dibuat. Ini belum menjadi transaksi atau saldo resmi.');
        } catch (FinancialDomainException $exception) {
            return back()->withInput()->withErrors(['financial' => $this->message($exception)]);
        }
    }

    public function storeLine(Request $request, OpeningBalanceBatch $batch)
    {
        $data = $request->validate([
            'entity' => ['required', 'uuid'], 'account_id' => ['required', 'uuid'], 'financial_account_id' => ['nullable', 'uuid'], 'fund_id' => ['nullable', 'uuid'], 'program_id' => ['nullable', 'uuid'],
            'source_reference' => ['required', 'string', 'max:240'], 'evidence_ref' => ['required', 'string', 'max:700'], 'line_description' => ['nullable', 'string', 'max:4000'],
            'debit_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'], 'credit_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'source_debit_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'], 'source_credit_amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], ['evidence.required' => 'Bukti sumber wajib dilampirkan sebelum Saldo Awal dapat diverifikasi.']);
        $entity = $this->activeEntity($data['entity']);
        abort_unless($batch->accounting_entity_id === $entity->id, 404);

        try {
            $line = $this->openingBalances->addLine($batch->id, $data, $request->user()?->id);
            $this->attachEvidence($request->file('evidence'), $entity, $line->id, $request->user()?->id);

            return redirect()->route('financial-v2.opening-balances.show', ['openingBalanceBatch' => $batch->id, 'entity' => $entity->id])->with('success', 'Posisi sumber dan bukti ditambahkan. Selisih akan ditampilkan saat verifikasi.');
        } catch (FinancialDomainException $exception) {
            return back()->withInput()->withErrors(['financial' => $this->message($exception)]);
        }
    }

    public function review(Request $request, OpeningBalanceBatch $batch)
    {
        return $this->transition($request, $batch, fn () => $this->openingBalances->review($batch->id, $request->user()?->id), 'Saldo Awal telah diverifikasi.');
    }

    public function approve(Request $request, OpeningBalanceBatch $batch)
    {
        return $this->transition($request, $batch, fn () => $this->openingBalances->approve($batch->id, $request->user()?->id), 'Saldo Awal telah disetujui dan siap dicatat melalui Posting Engine.');
    }

    public function post(Request $request, OpeningBalanceBatch $batch)
    {
        return $this->transition($request, $batch, fn () => $this->openingBalances->post($batch->id, $request->user()?->id), 'Saldo Awal telah dicatat melalui Posting Engine dan Ledger V2.');
    }

    private function transition(Request $request, OpeningBalanceBatch $batch, \Closure $action, string $success)
    {
        $data = $request->validate(['entity' => ['required', 'uuid']]);
        $entity = $this->activeEntity($data['entity']);
        abort_unless($batch->accounting_entity_id === $entity->id, 404);
        try {
            $action();

            return redirect()->route('financial-v2.opening-balances.show', ['openingBalanceBatch' => $batch->id, 'entity' => $entity->id])->with('success', $success);
        } catch (FinancialDomainException $exception) {
            return back()->withErrors(['financial' => $this->message($exception)]);
        }
    }

    /** @return array{entities:\Illuminate\Support\Collection<int,AccountingEntity>,entity:?AccountingEntity} */
    private function context(?string $entityId): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $entity = $entityId ? $entities->firstWhere('id', $entityId) : ($entities->count() === 1 ? $entities->first() : null);

        return compact('entities', 'entity');
    }

    private function activeEntity(?string $entityId): AccountingEntity
    {
        $entity = $entityId ? AccountingEntity::query()->where('status', 'active')->find($entityId) : null;
        if (! $entity) {
            abort(404);
        }

        return $entity;
    }

    /** @return array<string,\Illuminate\Support\Collection> */
    private function masters(AccountingEntity $entity): array
    {
        return [
            'periods' => AccountingPeriod::query()->where('accounting_entity_id', $entity->id)->orderByDesc('end_date')->get(),
            'mappingSets' => MappingSet::query()->where('accounting_entity_id', $entity->id)->orderBy('code')->get(),
            'accounts' => Account::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->orderBy('code')->get(),
            'financialAccounts' => FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->orderBy('name')->get(),
            'funds' => Fund::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->orderBy('code')->get(),
            'programs' => Program::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->orderBy('code')->get(),
        ];
    }

    private function attachEvidence(UploadedFile $file, AccountingEntity $entity, string $lineId, ?int $actorUserId): void
    {
        $temporaryPath = $file->getRealPath();
        if (! $temporaryPath) {
            throw new FinancialDomainException('E-ATTACHMENT-INVALID', 'Bukti Saldo Awal tidak dapat dibaca.');
        }
        $hash = hash_file('sha256', $temporaryPath);
        $stored = 'financial-v2/opening-balance/'.$entity->id.'/evidence/'.$hash.'.'.$file->getClientOriginalExtension();
        if (! Storage::disk('local')->exists($stored)) {
            Storage::disk('local')->put($stored, file_get_contents($temporaryPath));
        }
        $this->evidence->attachToOpeningBalanceLine($entity->id, $lineId, $file->getClientOriginalName(), $file->getMimeType() ?: 'application/octet-stream', $file->getSize(), $hash, $stored, 'statement', $actorUserId);
    }

    private function message(FinancialDomainException $exception): string
    {
        return match ($exception->failureCode) {
            'E-OPENING-MAPPING-UNRESOLVED', 'E-OPENING-MAPPING' => 'Pemetaan sumber belum lengkap atau masih ambigu. Tidak ada pemetaan yang dipilih otomatis.',
            'E-OPENING-RECONCILIATION' => 'Masih ada selisih antara Saldo Awal dan sumber yang disetujui.',
            'E-OPENING-EVIDENCE' => 'Setiap posisi harus memiliki bukti sumber aktif sebelum dapat disetujui.',
            'E-OPENING-DUPLICATE' => 'Referensi impor Saldo Awal ini sudah pernah digunakan.',
            default => 'Saldo Awal belum dapat diproses. Periksa sumber data, bukti, pemetaan, dan verifikasi.',
        };
    }
}
