<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\PeriodClosingService;
use App\Domain\FinancialV2\ReconciliationService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ClosingRun;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Reconciliation;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Minimal Financial V2 control UX. It invokes only closing/reconciliation
 * services and never writes Journal, JournalLine, Ledger, or source facts.
 */
final class FinancialControlController
{
    public function __construct(
        private readonly PeriodClosingService $closing,
        private readonly ReconciliationService $reconciliations,
        private readonly EvidenceService $evidence,
    ) {}

    public function index(Request $request)
    {
        $context = $this->context($request->query('entity'));
        $entity = $context['entity'];
        $periods = $entity
            ? AccountingPeriod::query()->where('accounting_entity_id', $entity->id)->orderByDesc('end_date')->get()
            : collect();
        $reconciliations = $entity
            ? Reconciliation::query()->with(['financialAccount', 'period'])->where('accounting_entity_id', $entity->id)->orderByDesc('business_date')->get()
            : collect();
        $accounts = $entity
            ? FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->whereIn('account_type', ['bank', 'cash', 'petty_cash'])->orderBy('name')->get()
            : collect();
        $runs = $entity
            ? ClosingRun::query()->where('accounting_entity_id', $entity->id)->orderByDesc('created_at')->get()->groupBy('accounting_period_id')
            : collect();

        return view('masjid.mrj.admin.financial-v2.controls.index', compact('entity', 'periods', 'reconciliations', 'accounts', 'runs') + ['entities' => $context['entities']]);
    }

    public function close(Request $request, AccountingPeriod $period)
    {
        $data = $request->validate([
            'entity' => ['required', 'uuid'],
            'run_type' => ['required', Rule::in(['soft_close', 'hard_close'])],
            'reference' => ['nullable', 'string', 'max:240'],
        ]);
        $entity = $this->activeEntity($data['entity']);
        abort_unless($period->accounting_entity_id === $entity->id, 404);

        try {
            $result = $this->closing->close($period->id, $data['run_type'], $request->user()?->id, $data['reference'] ?? null);
            $message = $result['closed']
                ? 'Periode berhasil ditutup secara terkendali. Journal dan Ledger posted tidak diubah.'
                : 'Penutupan ditahan. Periksa checklist kontrol pada riwayat periode sebelum mencoba kembali.';

            return $this->success($request, $message, $entity->id, ['closing_run_id' => $result['run']->id, 'closed' => $result['closed']]);
        } catch (FinancialDomainException|InvalidArgumentException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function storeReconciliation(Request $request)
    {
        $data = $request->validate([
            'entity' => ['required', 'uuid'],
            'financial_account_id' => ['required', 'uuid'],
            'accounting_period_id' => ['required', 'uuid'],
            'as_of_date' => ['required', 'date'],
            'statement_balance' => ['required', 'regex:/^-?\d+(?:\.\d{1,2})?$/'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'evidence' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ], [
            'evidence.required' => 'Bukti rekening atau hitung kas wajib dilampirkan.',
            'statement_balance.regex' => 'Saldo eksternal harus menggunakan nominal dengan maksimal dua desimal.',
        ]);
        $entity = $this->activeEntity($data['entity']);

        try {
            $reconciliation = $this->reconciliations->createDraft($data + ['accounting_entity_id' => $entity->id], $request->user()?->id);
            $account = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->findOrFail($data['financial_account_id']);
            $this->attachEvidence($request->file('evidence'), $entity, $reconciliation, $account, $request->user()?->id);

            return $this->success($request, 'Rekonsiliasi draft dibuat. Saldo buku diambil dari Posted Ledger V2.', $entity->id, ['reconciliation_id' => $reconciliation->id]);
        } catch (FinancialDomainException|InvalidArgumentException|QueryException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function review(Request $request, Reconciliation $reconciliation)
    {
        $entity = $this->entityForReconciliation($request, $reconciliation);

        try {
            if ($reconciliation->status === 'draft') {
                $this->reconciliations->startReview($reconciliation->id, $request->user()?->id);
            }
            $this->reconciliations->review($reconciliation->id, $request->user()?->id);

            return $this->success($request, 'Rekonsiliasi telah ditinjau. Selisih dihitung ulang dari Posted Ledger V2.', $entity->id, ['reconciliation_id' => $reconciliation->id]);
        } catch (FinancialDomainException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function complete(Request $request, Reconciliation $reconciliation)
    {
        $entity = $this->entityForReconciliation($request, $reconciliation);

        try {
            $this->reconciliations->complete($reconciliation->id, $request->user()?->id);

            return $this->success($request, 'Rekonsiliasi selesai tanpa selisih dan telah dikunci sebagai catatan kontrol.', $entity->id, ['reconciliation_id' => $reconciliation->id]);
        } catch (FinancialDomainException $exception) {
            return $this->failure($request, $exception);
        }
    }

    public function exception(Request $request, Reconciliation $reconciliation)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:4000']], ['reason.required' => 'Alasan selisih wajib diisi.']);
        $entity = $this->entityForReconciliation($request, $reconciliation);

        try {
            $this->reconciliations->markException($reconciliation->id, $data['reason'], $request->user()?->id);

            return $this->success($request, 'Selisih dicatat sebagai Exception. Tidak ada adjustment atau perubahan Ledger yang dilakukan.', $entity->id, ['reconciliation_id' => $reconciliation->id]);
        } catch (FinancialDomainException $exception) {
            return $this->failure($request, $exception);
        }
    }

    /** @return array{entities: \Illuminate\Support\Collection<int, AccountingEntity>, entity: ?AccountingEntity} */
    private function context(?string $requestedId): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $entity = $requestedId ? $entities->firstWhere('id', $requestedId) : ($entities->count() === 1 ? $entities->first() : null);

        return compact('entities', 'entity');
    }

    private function activeEntity(string $entityId): AccountingEntity
    {
        $entity = AccountingEntity::query()->where('status', 'active')->find($entityId);
        if (! $entity) {
            throw new FinancialDomainException('E-CONTROL-ENTITY', 'Entitas Financial V2 aktif belum tersedia.');
        }

        return $entity;
    }

    private function entityForReconciliation(Request $request, Reconciliation $reconciliation): AccountingEntity
    {
        $data = $request->validate(['entity' => ['required', 'uuid']]);
        $entity = $this->activeEntity($data['entity']);
        abort_unless($reconciliation->accounting_entity_id === $entity->id, 404);

        return $entity;
    }

    private function attachEvidence(UploadedFile $file, AccountingEntity $entity, Reconciliation $reconciliation, FinancialAccount $account, ?int $actorUserId): void
    {
        $temporaryPath = $file->getRealPath();
        if (! $temporaryPath) {
            throw new FinancialDomainException('E-ATTACHMENT-INVALID', 'Bukti rekonsiliasi tidak dapat dibaca.');
        }
        $contentHash = hash_file('sha256', $temporaryPath);
        $stored = 'financial-v2/reconciliation/'.$entity->id.'/evidence/'.$contentHash.'.'.$file->getClientOriginalExtension();
        if (! Storage::disk('local')->exists($stored)) {
            Storage::disk('local')->put($stored, file_get_contents($temporaryPath));
        }
        $path = Storage::disk('local')->path($stored);
        $evidenceType = $account->account_type === 'bank' ? 'statement' : 'cash_count';
        $this->evidence->attachToReconciliation(
            $entity->id,
            $reconciliation->id,
            $file->getClientOriginalName(),
            $file->getMimeType() ?: 'application/octet-stream',
            $file->getSize(),
            $contentHash,
            $stored,
            $evidenceType,
            $actorUserId,
        );
    }

    private function success(Request $request, string $message, string $entityId, array $payload = [])
    {
        $redirect = route('financial-v2.controls.index', ['entity' => $entityId]);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'redirect' => $redirect] + $payload);
        }

        return redirect($redirect)->with('success', $message);
    }

    private function failure(Request $request, \Throwable $exception)
    {
        $code = $exception instanceof FinancialDomainException ? $exception->failureCode : null;
        $message = match ($code) {
            'E-CLOSING-PERIOD-STATE' => 'Status periode tidak memenuhi syarat untuk tindakan penutupan ini.',
            'E-CLOSING-RUN-ACTIVE' => 'Masih ada proses penutupan lain yang harus diselesaikan terlebih dahulu.',
            'E-RECONCILIATION-DIFFERENCE' => 'Rekonsiliasi masih memiliki selisih sehingga belum dapat diselesaikan.',
            'E-RECONCILIATION-EVIDENCE' => 'Bukti rekening atau hitung kas aktif wajib tersedia sebelum rekonsiliasi diselesaikan.',
            'E-RECONCILIATION-STATE' => 'Status rekonsiliasi belum sesuai untuk tindakan ini.',
            'E-RECONCILIATION-PERIOD' => 'Rekonsiliasi hanya dapat dilakukan saat periode masih Open atau Soft Closed.',
            'E-RECONCILIATION-EXISTS' => 'Rekonsiliasi untuk rekening dan periode ini sudah ada.',
            default => 'Kontrol keuangan belum dapat diproses. Periksa data dan checklist yang berlaku.',
        };
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message, 'code' => $code ?? 'E-CONTROL-UNKNOWN'], 422);
        }

        return back()->withInput()->withErrors(['financial' => $message]);
    }
}
