<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\HistoricalFundHistoryService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\HistoricalFundHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin correction UI for source-only pre-V2 Fund history.
 *
 * It has no dependency on lifecycle posting and never writes financial facts.
 */
final class HistoricalFundHistoryController
{
    public function __construct(private readonly HistoricalFundHistoryService $history) {}

    public function create(Request $request, Fund $fund): View
    {
        [$entity, $fund] = $this->context($request, $fund);

        return $this->form($entity, $fund, null);
    }

    public function store(Request $request, Fund $fund): RedirectResponse
    {
        [$entity, $fund] = $this->context($request, $fund);
        $data = $this->validatedInput($request, $entity);

        try {
            $record = $this->history->createCorrection($entity->id, $data['fund_id'], $data, $request->user()?->id);

            return redirect()->route('financial-v2.funds.show', ['fund' => $record->fund_id, 'entity' => $entity->id])
                ->with('status', 'Koreksi riwayat sumber Dana disimpan. Tidak ada Journal atau Ledger yang dibuat.');
        } catch (FinancialDomainException $exception) {
            return back()->withInput()->withErrors(['history' => $exception->getMessage()]);
        }
    }

    public function edit(Request $request, Fund $fund, HistoricalFundHistory $history): View
    {
        [$entity, $fund] = $this->context($request, $fund);
        $this->ensureRecordScope($entity, $fund, $history);

        return $this->form($entity, $fund, $history);
    }

    public function update(Request $request, Fund $fund, HistoricalFundHistory $history): RedirectResponse
    {
        [$entity, $fund] = $this->context($request, $fund);
        $this->ensureRecordScope($entity, $fund, $history);
        $data = $this->validatedInput($request, $entity);

        try {
            $record = $this->history->correct($entity->id, $history->id, $data, $request->user()?->id);

            return redirect()->route('financial-v2.funds.show', ['fund' => $record->fund_id, 'entity' => $entity->id])
                ->with('status', 'Koreksi riwayat sumber Dana disimpan beserta audit trail. Journal dan Ledger tidak diubah.');
        } catch (FinancialDomainException $exception) {
            return back()->withInput()->withErrors(['history' => $exception->getMessage()]);
        }
    }

    /** @return array{0: AccountingEntity, 1: Fund} */
    private function context(Request $request, Fund $fund): array
    {
        $entity = AccountingEntity::query()->where('status', 'active')->findOrFail($fund->accounting_entity_id);
        if ($request->filled('entity') && $request->string('entity')->toString() !== $entity->id) {
            abort(404);
        }

        return [$entity, $fund];
    }

    private function ensureRecordScope(AccountingEntity $entity, Fund $fund, HistoricalFundHistory $history): void
    {
        abort_unless($history->accounting_entity_id === $entity->id && $history->fund_id === $fund->id, 404);
    }

    private function form(AccountingEntity $entity, Fund $fund, ?HistoricalFundHistory $history): View
    {
        $auditEvents = $history
            ? AuditEvent::query()
                ->where('accounting_entity_id', $entity->id)
                ->where('target_type', 'historical_fund_history')
                ->where('target_id', $history->id)
                ->with('actor:id,name')
                ->orderByDesc('event_at')
                ->get()
            : collect();

        return view('masjid.mrj.admin.financial-v2.funds.history-form', [
            'entity' => $entity,
            'fund' => $fund,
            'history' => $history?->load(['fund', 'createdBy', 'updatedBy']),
            'funds' => Fund::query()->where('accounting_entity_id', $entity->id)->orderBy('name')->get(),
            'auditEvents' => $auditEvents,
            'entryKinds' => [
                'opening' => 'Saldo awal sumber',
                'receipt' => 'Pemasukan sumber',
                'usage' => 'Penggunaan sumber',
                'adjustment_in' => 'Koreksi tambah',
                'adjustment_out' => 'Koreksi kurang',
                'account_position' => 'Komponen rekening/kas',
                'closing' => 'Saldo sumber penutup',
            ],
        ]);
    }

    /** @return array<string, mixed> */
    private function validatedInput(Request $request, AccountingEntity $entity): array
    {
        return $request->validate([
            'fund_id' => [
                'required',
                'uuid',
                Rule::exists('financial_v2_funds', 'id')->where('accounting_entity_id', $entity->id),
            ],
            'effective_date' => ['nullable', 'date'],
            'date_label' => ['required', 'string', 'max:100'],
            'entry_kind' => ['required', Rule::in(['opening', 'receipt', 'usage', 'adjustment_in', 'adjustment_out', 'account_position', 'closing'])],
            'description' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'amount' => ['required', 'regex:/^\d+(?:\.\d{1,2})?$/'],
            'source_reference' => ['required', 'string', 'max:400'],
            'correction_reason' => ['required', 'string', 'max:2000'],
        ], [
            'fund_id.required' => 'Dana wajib dipilih.',
            'amount.regex' => 'Nominal harus berupa angka dengan maksimal dua angka desimal.',
            'correction_reason.required' => 'Alasan koreksi wajib diisi agar perubahan dapat diaudit.',
        ]);
    }
}
