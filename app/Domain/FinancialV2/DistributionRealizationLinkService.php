<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Governed reference-only link between an operational distribution and an existing realization. */
final class DistributionRealizationLinkService
{
    private const VALID_REALIZATION_STATUSES = ['draft', 'recorded'];

    private const VALID_TRANSACTION_STATUSES = ['draft', 'submitted', 'verified', 'approved', 'posting', 'posted'];

    public function __construct(private readonly AuditTrailService $audit) {}

    /** @return Collection<int, FundRealization> */
    public function candidates(Distribution $distribution): Collection
    {
        $distribution->loadMissing('items.beneficiary');
        $usedIds = Distribution::query()->whereNotNull('realization_id')->pluck('realization_id');

        return FundRealization::forEntity($distribution->accounting_entity_id)
            ->whereIn('status', self::VALID_REALIZATION_STATUSES)
            ->whereNotIn('id', $usedIds)
            ->whereHas('transaction', fn ($query) => $query
                ->whereIn('status', self::VALID_TRANSACTION_STATUSES)
                ->whereHas('type', fn ($type) => $type->where('code', TransactionTypeCode::Payment->value)))
            ->with([
                'transaction.type', 'transaction.splits.account', 'transaction.splits.fund',
                'budgetAllocationVersion.allocation.program', 'budgetAllocationVersion.allocation.fund',
                'budgetAllocationVersion.fundings.fund',
            ])
            ->get()
            ->each(function (FundRealization $realization) use ($distribution): void {
                $errors = $this->compatibilityErrors($distribution, $realization);
                $warnings = $this->compatibilityWarnings($distribution, $realization);
                $realization->setAttribute('link_validation_errors', $errors);
                $realization->setAttribute('link_validation_warnings', $warnings);
                $realization->setAttribute('link_compatible', $errors === []);
                $realization->setAttribute('link_funds', $realization->transaction->splits->pluck('fund.name')->filter()->unique()->values()->implode(', '));
                $realization->setAttribute('link_program', $realization->budgetAllocationVersion?->allocation?->program);
            })
            ->sort(function (FundRealization $left, FundRealization $right) use ($distribution): int {
                $compatibility = ((int) ! $left->link_compatible) <=> ((int) ! $right->link_compatible);
                if ($compatibility !== 0) {
                    return $compatibility;
                }

                return abs((int) $left->transaction->business_date->diffInDays($distribution->starts_on, false))
                    <=> abs((int) $right->transaction->business_date->diffInDays($distribution->starts_on, false));
            })->values();
    }

    public function link(string $entityId, string $distributionId, string $realizationId, int $revision, ?int $actor): Distribution
    {
        return DB::transaction(function () use ($entityId, $distributionId, $realizationId, $revision, $actor): Distribution {
            $distribution = Distribution::forEntity($entityId)->with(['program', 'items.beneficiary'])->lockForUpdate()->findOrFail($distributionId);
            if ($distribution->realization_id === $realizationId) {
                return $distribution->fresh(['realization.transaction']);
            }
            $this->require($distribution->status === 'draft' && $distribution->realization_id === null, 'Penyaluran sudah ditautkan ke Realisasi lain atau sudah final.');
            $this->require((int) $distribution->revision === $revision, 'Data Penyaluran telah berubah. Muat ulang sebelum menautkan.');

            $realization = FundRealization::query()->lockForUpdate()->findOrFail($realizationId);
            $this->require($realization->accounting_entity_id === $entityId, 'Realisasi tidak dapat ditautkan karena Entity berbeda.');
            $transaction = FinancialTransaction::forEntity($entityId)
                ->with(['type', 'splits.account', 'splits.fund'])
                ->lockForUpdate()
                ->findOrFail($realization->transaction_id);
            $realization->setRelation('transaction', $transaction);
            if ($realization->budget_allocation_version_id) {
                $allocationVersion = BudgetAllocationVersion::forEntity($entityId)
                    ->with(['allocation.program', 'fundings'])
                    ->lockForUpdate()
                    ->findOrFail($realization->budget_allocation_version_id);
                $realization->setRelation('budgetAllocationVersion', $allocationVersion);
            }

            $usedBy = Distribution::query()->where('realization_id', $realizationId)->lockForUpdate()->first();
            $this->require(! $usedBy, $usedBy ? 'Realisasi ini sudah ditautkan ke Penyaluran lain: '.$usedBy->title.'.' : 'Realisasi ini sudah ditautkan ke Penyaluran lain.');

            $errors = $this->compatibilityErrors($distribution, $realization);
            if ($errors !== []) {
                throw ValidationException::withMessages(['realization_id' => $errors]);
            }

            $total = DecimalAmount::sum($distribution->items->pluck('amount'));
            $distribution->update([
                'realization_id' => $realization->id,
                'status' => 'finalized',
                'finalized_at' => now(),
                'finalized_by_user_id' => $actor,
                'updated_by_user_id' => $actor,
                'revision' => $revision + 1,
            ]);
            $this->audit->record(
                $entityId,
                'distribution.realization_linked',
                'ziswaf_distribution',
                $distribution->id,
                (string) Str::uuid(),
                $actor,
                ['realization_id' => null, 'status' => 'draft'],
                ['realization_id' => $realization->id, 'status' => 'finalized', 'operational_total' => $total],
            );

            return $distribution->fresh(['realization.transaction']);
        }, 3);
    }

    /** @return list<string> */
    private function compatibilityErrors(Distribution $distribution, FundRealization $realization): array
    {
        $errors = [];
        $transaction = $realization->transaction;
        $allocationVersion = $realization->budgetAllocationVersion;
        $allocation = $allocationVersion?->allocation;
        $splits = $transaction?->splits ?? collect();
        $items = $distribution->items;

        if ($realization->accounting_entity_id !== $distribution->accounting_entity_id) {
            $errors[] = 'Realisasi tidak dapat ditautkan karena Entity berbeda.';
        }
        if (! in_array($realization->status, self::VALID_REALIZATION_STATUSES, true)
            || ! $transaction || ! in_array($transaction->status, self::VALID_TRANSACTION_STATUSES, true)) {
            $errors[] = 'Status Realisasi atau transaksi tidak valid untuk ditautkan.';
        }
        if ($transaction?->type?->code !== TransactionTypeCode::Payment->value) {
            $errors[] = 'Realisasi harus berasal dari transaksi PAY.';
        }
        if (! $allocationVersion || ! in_array($allocationVersion->status, ['approved', 'superseded'], true) || ! $allocation) {
            $errors[] = 'Realisasi tidak memiliki Allocation Version approved yang valid.';
        }
        if ($allocation && $allocation->program_id !== $distribution->program_id) {
            $errors[] = sprintf(
                'Program berbeda: Penyaluran menggunakan %s, sedangkan Allocation/Realisasi menggunakan %s.',
                $distribution->program?->code ?? $distribution->program_id,
                $allocation->program?->code ?? $allocation->program_id,
            );
        }
        if ($splits->isEmpty() || $splits->contains(fn ($split): bool => $split->program_id !== $distribution->program_id)) {
            $errors[] = 'Program pada rincian transaksi Realisasi tidak sama dengan Program Penyaluran.';
        }
        if ($splits->contains(fn ($split): bool => $split->account?->account_class !== 'expense')) {
            $errors[] = 'Rincian Realisasi bukan pengeluaran program.';
        }

        $transactionFundIds = $splits->pluck('fund_id')->filter()->unique()->sort()->values();
        $allocationFundIds = $allocationVersion?->fundings?->pluck('fund_id')->filter()->unique()->sort()->values() ?? collect();
        if ($allocationFundIds->isEmpty() && $allocation?->fund_id) {
            $allocationFundIds = collect([$allocation->fund_id]);
        }
        if ($transactionFundIds->isEmpty() || $transactionFundIds->all() !== $allocationFundIds->all()) {
            $errors[] = 'Fund Realisasi tidak konsisten dengan sumber Fund pada Allocation.';
        }
        if ($items->isEmpty()) {
            $errors[] = 'Penyaluran belum memiliki penerima.';
        }
        if ($items->contains(fn ($item): bool => $item->beneficiary_id !== null && $item->beneficiary?->status !== 'active')) {
            $errors[] = 'Seluruh penerima yang tertaut ke master harus masih aktif saat Penyaluran ditautkan.';
        }
        $total = DecimalAmount::sum($items->pluck('amount'));
        if (! $transaction || ! DecimalAmount::equals($total, $transaction->gross_amount)
            || ! DecimalAmount::equals($total, DecimalAmount::sum($splits->pluck('split_amount')))) {
            $errors[] = 'Nominal Realisasi berbeda dengan total rincian penerima Penyaluran.';
        }
        return $errors;
    }

    /** @return list<string> */
    private function compatibilityWarnings(Distribution $distribution, FundRealization $realization): array
    {
        $transaction = $realization->transaction;
        if ($transaction && ($transaction->business_date->lt($distribution->starts_on) || $transaction->business_date->gt($distribution->ends_on))) {
            return ['Tanggal Realisasi berada di luar periode operasional Penyaluran; periksa kembali sebelum menautkan.'];
        }

        return [];
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw ValidationException::withMessages(['realization_id' => $message]);
        }
    }
}
