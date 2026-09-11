<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Program;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Operational beneficiary records only. No lifecycle/posting writer dependency. */
final class DistributionService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function saveBeneficiary(string $entityId, array $input, ?string $id, ?int $actor): Counterparty
    {
        $data = Validator::make($input, [
            'display_name' => 'required|string|max:240', 'contact_reference' => 'nullable|string|max:500',
            'address' => 'nullable|string|max:2000', 'rt' => 'nullable|string|max:10', 'rw' => 'nullable|string|max:10',
            'rt_coordinator_name' => 'nullable|string|max:160', 'beneficiary_notes' => 'nullable|string|max:2000',
            'beneficiary_type' => 'nullable|in:YATIM,DHUAFA,YATIM_DHUAFA,BELUM_DITENTUKAN',
            'status' => 'required|in:active,inactive,archived',
        ])->validate();

        return DB::transaction(function () use ($entityId, $data, $id, $actor) {
            AccountingEntity::whereKey($entityId)->where('status', 'active')->firstOrFail();
            $person = $id ? Counterparty::forEntity($entityId)->where('party_type', 'beneficiary')->lockForUpdate()->findOrFail($id) : new Counterparty;
            $data['beneficiary_type'] ??= $person->beneficiary_type ?: 'BELUM_DITENTUKAN';
            $person->fill($data + ['accounting_entity_id' => $entityId, 'party_type' => 'beneficiary']);
            if (! $id) {
                $person->code = 'BEN-'.Str::upper(Str::random(20));
                $person->created_by_user_id = $actor;
            }
            $person->updated_by_user_id = $actor;
            $person->save();
            $this->record($entityId, 'beneficiary.saved', $person->id, $actor);

            return $person;
        });
    }

    /**
     * Delete only unused beneficiary identities. Referenced identities remain intact
     * so operational snapshots and all Financial V2 facts keep valid foreign keys.
     *
     * @param  array<int, string>  $ids
     * @return array{deleted: int, protected: int}
     */
    public function deleteBeneficiaries(string $entityId, array $ids, ?int $actor): array
    {
        $ids = array_values(array_unique($ids));
        Validator::make(['beneficiary_ids' => $ids], [
            'beneficiary_ids' => 'required|array|min:1',
            'beneficiary_ids.*' => 'required|uuid|distinct',
        ])->validate();

        return DB::transaction(function () use ($entityId, $ids, $actor): array {
            AccountingEntity::whereKey($entityId)->where('status', 'active')->firstOrFail();
            $people = Counterparty::query()->whereIn('id', $ids)->lockForUpdate()->get();
            $validSelection = $people->count() === count($ids)
                && $people->every(fn (Counterparty $person) => $person->accounting_entity_id === $entityId && $person->party_type === 'beneficiary');
            if (! $validSelection) {
                throw ValidationException::withMessages(['beneficiary_ids' => 'Pilihan penerima tidak valid atau berasal dari entitas lain.']);
            }

            $result = ['deleted' => 0, 'protected' => 0];
            foreach ($people as $person) {
                if ($this->beneficiaryHasReferences($person->id)) {
                    $result['protected']++;

                    continue;
                }
                $before = Arr::only($person->getAttributes(), [
                    'accounting_entity_id', 'code', 'party_type', 'beneficiary_type', 'display_name',
                    'address', 'rt', 'rw', 'rt_coordinator_name', 'contact_reference', 'status',
                ]);
                $this->record($entityId, 'beneficiary.deleted', $person->id, $actor, ['deleted_record' => $before]);
                $person->delete();
                $result['deleted']++;
            }

            return $result;
        });
    }

    public function create(string $entityId, array $input, ?int $actor): Distribution
    {
        $data = Validator::make($input, [
            'program_id' => 'required|uuid', 'title' => 'required|string|max:200',
            'period_label' => 'required|string|max:100', 'starts_on' => 'required|date_format:Y-m-d',
            'ends_on' => 'required|date_format:Y-m-d|after_or_equal:starts_on', 'notes' => 'nullable|string|max:2000',
        ])->validate();

        return DB::transaction(function () use ($entityId, $data, $actor) {
            AccountingEntity::whereKey($entityId)->where('status', 'active')->firstOrFail();
            Program::forEntity($entityId)->where('status', 'active')->lockForUpdate()->findOrFail($data['program_id']);
            $overlap = Distribution::forEntity($entityId)->where('program_id', $data['program_id'])
                ->where('starts_on', '<=', $data['ends_on'])->where('ends_on', '>=', $data['starts_on'])->exists();
            $this->require(! $overlap, 'Periode program bertumpang tindih dengan penyaluran yang sudah ada.');
            $distribution = Distribution::create($data + ['accounting_entity_id' => $entityId, 'status' => 'draft', 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor]);
            $this->record($entityId, 'distribution.created', $distribution->id, $actor);

            return $distribution;
        });
    }

    public function copyPrevious(string $entityId, array $input, ?int $actor): Distribution
    {
        return DB::transaction(function () use ($entityId, $input, $actor) {
            $copy = $this->create($entityId, $input, $actor);
            $previous = Distribution::forEntity($entityId)->where('program_id', $copy->program_id)
                ->where('ends_on', '<', $copy->starts_on->toDateString())->orderByDesc('ends_on')->lockForUpdate()->first();
            $this->require($previous !== null, 'Belum ada penyaluran periode sebelumnya.');
            $copy->update(['copied_from_id' => $previous->id]);
            foreach ($previous->items()->get() as $item) {
                $person = Counterparty::forEntity($entityId)->where('party_type', 'beneficiary')->findOrFail($item->beneficiary_id);
                $copy->items()->create(['beneficiary_id' => $person->id, 'amount' => $item->amount, 'notes' => $item->notes, 'identity_snapshot' => $this->snapshot($person)]);
            }
            $this->record($entityId, 'distribution.copied', $copy->id, $actor, ['copied_from_id' => $previous->id]);

            return $copy;
        });
    }

    public function item(string $entityId, string $id, array $input, ?string $itemId, bool $remove, ?int $actor): Distribution
    {
        return DB::transaction(function () use ($entityId, $id, $input, $itemId, $remove, $actor) {
            $distribution = $this->editable($entityId, $id, $input['revision'] ?? null);
            $item = $itemId ? $distribution->items()->findOrFail($itemId) : null;
            if ($remove) {
                $this->require($item !== null, 'Penerima tidak ditemukan.');
                $item->delete();
            } else {
                $data = Validator::make($input, ['beneficiary_id' => 'required|uuid', 'amount' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/'], 'notes' => 'nullable|string|max:2000'])->validate();
                $person = Counterparty::forEntity($entityId)->where('party_type', 'beneficiary')->where('status', 'active')->lockForUpdate()->findOrFail($data['beneficiary_id']);
                $duplicate = $distribution->items()->where('beneficiary_id', $person->id)->when($item, fn ($q) => $q->where('id', '<>', $item->id))->exists();
                $this->require(! $duplicate, 'Penerima sudah ada dalam penyaluran ini.');
                $data['amount'] = DecimalAmount::normalize($data['amount']);
                // Preserve the historical snapshot when editing amount/notes.
                $data['identity_snapshot'] = $item && $item->beneficiary_id === $person->id ? $item->identity_snapshot : $this->snapshot($person);
                $item ? $item->update($data) : $distribution->items()->create($data);
            }
            $distribution->update(['revision' => $distribution->revision + 1, 'updated_by_user_id' => $actor]);
            $this->record($entityId, $remove ? 'distribution.item_removed' : 'distribution.item_saved', $id, $actor);

            return $distribution;
        });
    }

    public function addItemsToDraft(string $entityId, string $id, array $input, ?int $actor): Distribution
    {
        return DB::transaction(function () use ($entityId, $id, $input, $actor) {
            $distribution = $this->editable($entityId, $id, $input['revision'] ?? null);
            $data = Validator::make($input, [
                'items' => 'required|array|min:1|max:200',
                'items.*.beneficiary_id' => 'required|uuid|distinct',
                'items.*.amount' => ['required', 'regex:/^\d{1,16}(\.\d{1,2})?$/'],
                'items.*.notes' => 'nullable|string|max:2000',
            ])->validate();
            $rows = array_values($data['items']);
            $beneficiaryIds = collect($rows)->pluck('beneficiary_id')->values();
            $people = Counterparty::forEntity($entityId)
                ->where('party_type', 'beneficiary')
                ->where('status', 'active')
                ->whereIn('id', $beneficiaryIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $this->require($people->count() === $beneficiaryIds->count(), 'Pilihan penerima harus aktif dan berasal dari entitas yang sama.');
            $duplicate = $distribution->items()->whereIn('beneficiary_id', $beneficiaryIds)->exists();
            $this->require(! $duplicate, 'Salah satu penerima sudah ada dalam penyaluran ini.');

            foreach ($rows as $row) {
                $person = $people->get($row['beneficiary_id']);
                $distribution->items()->create([
                    'beneficiary_id' => $person->id,
                    'amount' => DecimalAmount::normalize($row['amount']),
                    'notes' => $row['notes'] ?? null,
                    'identity_snapshot' => $this->snapshot($person),
                ]);
            }

            $distribution->update(['revision' => $distribution->revision + 1, 'updated_by_user_id' => $actor]);
            $this->record($entityId, 'distribution.items_added', $id, $actor, ['count' => count($rows)]);

            return $distribution;
        });
    }

    /** Delete an operational draft and its items without touching Financial V2 facts. */
    public function deleteDraft(string $entityId, string $id, ?int $actor): void
    {
        DB::transaction(function () use ($entityId, $id, $actor): void {
            AccountingEntity::whereKey($entityId)->where('status', 'active')->lockForUpdate()->firstOrFail();
            $distribution = Distribution::forEntity($entityId)->lockForUpdate()->findOrFail($id);
            $this->require(
                $distribution->status === 'draft'
                    && $distribution->realization_id === null
                    && $distribution->finalized_at === null,
                'Penyaluran tidak dapat dihapus karena sudah direalisasikan atau tidak lagi berstatus draft.'
            );
            $this->require(
                ! Distribution::query()->where('copied_from_id', $distribution->id)->exists(),
                'Penyaluran tidak dapat dihapus karena masih menjadi sumber salinan periode berikutnya.'
            );

            $items = $distribution->items()->lockForUpdate()->get();
            $before = Arr::only($distribution->getAttributes(), [
                'accounting_entity_id', 'program_id', 'realization_id', 'copied_from_id', 'title',
                'period_label', 'starts_on', 'ends_on', 'status', 'revision', 'finalized_at',
            ]) + [
                'recipient_count' => $items->count(),
                'operational_total' => DecimalAmount::sum($items->pluck('amount')),
            ];

            $this->audit->record(
                $entityId,
                'distribution.deleted',
                'ziswaf_distribution',
                $distribution->id,
                (string) Str::uuid(),
                $actor,
                $before,
                ['deleted' => true],
            );
            $items->each->delete();
            DistributionDeletionGuard::withinDeletion(fn () => $distribution->delete());
        });
    }

    public function finalize(string $entityId, string $id, string $realizationId, int $revision, ?int $actor): Distribution
    {
        return DB::transaction(function () use ($entityId, $id, $realizationId, $revision, $actor) {
            $distribution = $this->editable($entityId, $id, $revision);
            $realization = FundRealization::forEntity($entityId)->lockForUpdate()->findOrFail($realizationId);
            $transaction = FinancialTransaction::forEntity($entityId)->lockForUpdate()->findOrFail($realization->transaction_id);
            $this->require(! in_array($transaction->status, ['cancelled', 'reversed'], true) && ! in_array($realization->status, ['cancelled', 'reversed'], true), 'Realisasi dibatalkan atau dibalik.');
            $this->require(! Distribution::where('realization_id', $realizationId)->exists(), 'Realisasi sudah dikaitkan ke penyaluran lain.');
            $splits = $transaction->splits()->with('account')->get();
            $this->require(in_array($transaction->type->code, app(\App\Domain\FinancialV2\Reporting\FinancialReportDefinitions::class)->cashOutTypes(), true)
                && $splits->every(fn ($s) => $s->account?->account_class === 'expense'), 'Realisasi harus berupa pengeluaran program, bukan penerimaan atau transfer.');
            $this->require($splits->isNotEmpty() && $splits->every(fn ($s) => $s->program_id === $distribution->program_id), 'Seluruh rincian realisasi harus milik Program penyaluran ini.');
            $items = $distribution->items()->get();
            $this->require($items->isNotEmpty(), 'Penyaluran kosong tidak dapat difinalisasi.');
            $active = Counterparty::forEntity($entityId)->where('party_type', 'beneficiary')->where('status', 'active')->whereIn('id', $items->pluck('beneficiary_id'))->lockForUpdate()->get();
            $this->require($active->count() === $items->count(), 'Hapus atau ganti penerima yang sudah tidak aktif.');
            $total = DecimalAmount::sum($items->pluck('amount'));
            $this->require(DecimalAmount::equals($total, $transaction->gross_amount) && DecimalAmount::equals($total, DecimalAmount::sum($splits->pluck('split_amount'))), 'Total penyaluran tidak sama dengan nominal realisasi.');
            $distribution->update(['realization_id' => $realizationId, 'status' => 'finalized', 'finalized_at' => now(), 'finalized_by_user_id' => $actor, 'updated_by_user_id' => $actor, 'revision' => $revision + 1]);
            $this->record($entityId, 'distribution.finalized', $id, $actor, ['realization_id' => $realizationId, 'total' => $total]);

            return $distribution;
        });
    }

    private function editable(string $entityId, string $id, mixed $revision): Distribution
    {
        $distribution = Distribution::forEntity($entityId)->lockForUpdate()->findOrFail($id);
        $this->require($distribution->status === 'draft' && $distribution->realization_id === null, 'Penyaluran final atau terkait realisasi tidak dapat diedit.');
        $this->require(filter_var($revision, FILTER_VALIDATE_INT) !== false && $revision !== null && (int) $revision === (int) $distribution->revision, 'Data telah berubah. Muat ulang sebelum menyimpan.');

        return $distribution;
    }

    private function snapshot(Counterparty $person): array
    {
        return Arr::only($person->getAttributes(), ['display_name', 'address', 'rt', 'rw', 'rt_coordinator_name', 'contact_reference']);
    }

    private function beneficiaryHasReferences(string $id): bool
    {
        foreach ([
            ['financial_v2_distribution_items', 'beneficiary_id'],
            ['financial_v2_transactions', 'counterparty_id'],
            ['financial_v2_transaction_splits', 'counterparty_id'],
            ['financial_v2_journal_lines', 'counterparty_id'],
            ['financial_v2_posting_rule_lines', 'fixed_counterparty_id'],
        ] as [$table, $column]) {
            if (DB::table($table)->where($column, $id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['distribution' => $message]);
        }
    }

    private function record(string $entityId, string $event, string $id, ?int $actor, ?array $after = null): void
    {
        $this->audit->record($entityId, $event, str_starts_with($event, 'beneficiary.') ? 'ziswaf_beneficiary' : 'ziswaf_distribution', $id, (string) Str::uuid(), $actor, null, $after);
    }
}
