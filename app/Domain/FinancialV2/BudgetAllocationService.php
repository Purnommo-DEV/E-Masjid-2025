<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\FundRealization;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Budget Allocation is a governed plan. This service intentionally has no
 * PostingEngine dependency and never writes Journal/GL facts.
 */
final class BudgetAllocationService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @param array<string, mixed> $input */
    public function create(array $input, ?int $actorUserId = null): BudgetAllocation
    {
        $this->requireFields($input, ['accounting_entity_id', 'accounting_period_id', 'fund_id', 'allocation_reference', 'idempotency_key', 'allocated_amount', 'effective_from', 'reason']);

        return DB::transaction(function () use ($input, $actorUserId): BudgetAllocation {
            $entityId = $input['accounting_entity_id'];
            foreach (['accounting_period_id' => 'financial_v2_accounting_periods', 'fund_id' => 'financial_v2_funds', 'program_id' => 'financial_v2_programs', 'account_id' => 'financial_v2_accounts', 'category_id' => 'financial_v2_categories'] as $field => $table) {
                if (! empty($input[$field]) && ! DB::table($table)->where('id', $input[$field])->where('accounting_entity_id', $entityId)->exists()) {
                    throw new FinancialDomainException('E-BUDGET-MASTER-SCOPE', 'Budget Allocation dimensions must be in the same AccountingEntity.');
                }
            }
            $amount = DecimalAmount::normalize($input['allocated_amount']);
            if (DecimalAmount::compare($amount, '0.00') <= 0) {
                throw new FinancialDomainException('E-BUDGET-AMOUNT', 'Budget Allocation amount must be positive.');
            }
            $allocation = BudgetAllocation::create([
                'accounting_entity_id' => $entityId,
                'accounting_period_id' => $input['accounting_period_id'],
                'fund_id' => $input['fund_id'],
                'program_id' => $input['program_id'] ?? null,
                'account_id' => $input['account_id'] ?? null,
                'category_id' => $input['category_id'] ?? null,
                'allocation_reference' => $input['allocation_reference'],
                'idempotency_key' => $input['idempotency_key'],
                'correlation_id' => $input['correlation_id'] ?? (string) Str::uuid(),
                'status' => 'draft',
                'reason' => $input['reason'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $version = BudgetAllocationVersion::create([
                'accounting_entity_id' => $entityId,
                'budget_allocation_id' => $allocation->id,
                'version_no' => 1,
                'allocated_amount' => $amount,
                'effective_from' => $input['effective_from'],
                'effective_to' => $input['effective_to'] ?? null,
                'status' => 'draft',
                'reason' => $input['reason'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($entityId, 'budget_allocation_created', 'budget_allocation', $allocation->id, $allocation->correlation_id, $actorUserId, null, ['status' => 'draft', 'version_id' => $version->id, 'allocated_amount' => $amount]);

            return $allocation->fresh('versions');
        }, 3);
    }

    public function submit(string $allocationId, ?int $actorUserId = null): BudgetAllocation
    {
        return $this->changeAllocationStatus($allocationId, ['draft'], 'submitted', 'budget_allocation_submitted', $actorUserId);
    }

    public function approveVersion(string $allocationId, string $versionId, ?int $actorUserId = null): BudgetAllocationVersion
    {
        return DB::transaction(function () use ($allocationId, $versionId, $actorUserId): BudgetAllocationVersion {
            $allocation = BudgetAllocation::query()->lockForUpdate()->findOrFail($allocationId);
            $version = BudgetAllocationVersion::query()->where('budget_allocation_id', $allocation->id)->lockForUpdate()->findOrFail($versionId);
            if (! in_array($allocation->status, ['draft', 'submitted', 'approved'], true) || $version->status !== 'draft') {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Only a draft Budget Allocation Version may be approved.');
            }
            $previous = BudgetAllocationVersion::query()
                ->where('budget_allocation_id', $allocation->id)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->first();
            if ($previous) {
                if ($version->effective_from->lte($previous->effective_from)) {
                    throw new FinancialDomainException('E-BUDGET-VERSION', 'Budget Allocation revisions must take effect after the current approved version.');
                }
                $previous->update(['status' => 'superseded', 'effective_to' => $version->effective_from->copy()->subDay(), 'updated_by_user_id' => $actorUserId]);
            }
            $version->update(['status' => 'approved', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $allocation->update(['status' => 'approved', 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($allocation->accounting_entity_id, 'budget_allocation_version_approved', 'budget_allocation_version', $version->id, $allocation->correlation_id, $actorUserId, null, ['allocation_id' => $allocation->id, 'version_no' => $version->version_no, 'allocated_amount' => $version->allocated_amount]);

            return $version->fresh();
        }, 3);
    }

    public function revise(string $allocationId, string|int $allocatedAmount, string $effectiveFrom, string $reason, ?int $actorUserId = null): BudgetAllocationVersion
    {
        if (blank($reason) || DecimalAmount::compare(DecimalAmount::normalize($allocatedAmount), '0.00') <= 0) {
            throw new FinancialDomainException('E-BUDGET-REVISION', 'Budget revisions require a positive amount and reason.');
        }

        return DB::transaction(function () use ($allocationId, $allocatedAmount, $effectiveFrom, $reason, $actorUserId): BudgetAllocationVersion {
            $allocation = BudgetAllocation::query()->lockForUpdate()->findOrFail($allocationId);
            if ($allocation->status !== 'approved') {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Only approved Budget Allocations may receive a governed revision.');
            }
            $nextVersion = (int) BudgetAllocationVersion::query()->where('budget_allocation_id', $allocation->id)->max('version_no') + 1;
            $version = BudgetAllocationVersion::create([
                'accounting_entity_id' => $allocation->accounting_entity_id,
                'budget_allocation_id' => $allocation->id,
                'version_no' => $nextVersion,
                'allocated_amount' => DecimalAmount::normalize($allocatedAmount),
                'effective_from' => $effectiveFrom,
                'status' => 'draft',
                'reason' => $reason,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($allocation->accounting_entity_id, 'budget_allocation_revision_created', 'budget_allocation_version', $version->id, $allocation->correlation_id, $actorUserId, null, ['version_no' => $nextVersion, 'allocated_amount' => $version->allocated_amount, 'reason' => $reason]);

            return $version;
        }, 3);
    }

    /**
     * Cancels an unfixed plan without creating a financial correction. A
     * recorded realization is an actual payment and therefore prevents this
     * lifecycle action rather than being silently altered.
     */
    public function cancel(string $allocationId, string $reason, ?int $actorUserId = null): BudgetAllocation
    {
        if (blank($reason)) {
            throw new FinancialDomainException('E-BUDGET-CANCELLATION-REASON', 'Alasan pembatalan alokasi wajib diisi.');
        }

        return DB::transaction(function () use ($allocationId, $reason, $actorUserId): BudgetAllocation {
            $allocation = BudgetAllocation::query()->lockForUpdate()->findOrFail($allocationId);
            if ($allocation->status === 'cancelled') {
                return $allocation->fresh(['versions', 'cancelledBy']);
            }
            if (! in_array($allocation->status, ['draft', 'submitted', 'approved'], true)) {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Hanya alokasi Draft, Diajukan, atau Disetujui yang belum direalisasikan dapat dibatalkan.');
            }

            $versions = BudgetAllocationVersion::query()
                ->where('budget_allocation_id', $allocation->id)
                ->lockForUpdate()
                ->get();
            if (FundRealization::query()
                ->whereIn('budget_allocation_version_id', $versions->pluck('id'))
                ->where('status', 'recorded')
                ->exists()) {
                throw new FinancialDomainException('E-BUDGET-REALIZED', 'Alokasi yang sudah memiliki realisasi tercatat tidak dapat dibatalkan. Gunakan koreksi atau reversal pembayaran sesuai kewenangan.');
            }

            $before = $this->allocationSummary($allocation, $versions);
            $cancelledAt = now();
            $allocation->update([
                'status' => 'cancelled',
                'cancellation_reason' => trim($reason),
                'cancelled_at' => $cancelledAt,
                'cancelled_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $versions
                ->whereIn('status', ['draft', 'approved'])
                ->each(fn (BudgetAllocationVersion $version) => $version->update([
                    'status' => 'cancelled',
                    'updated_by_user_id' => $actorUserId,
                ]));

            $after = $this->allocationSummary($allocation->fresh(), $versions->map->fresh());
            $after['cancellation_reason'] = trim($reason);
            $after['cancelled_at'] = $cancelledAt->toISOString();
            $after['cancelled_by_user_id'] = $actorUserId;
            $this->auditTrail->record(
                $allocation->accounting_entity_id,
                'budget_allocation_cancelled',
                'budget_allocation',
                $allocation->id,
                $allocation->correlation_id,
                $actorUserId,
                $before,
                $after,
            );

            return $allocation->fresh(['versions', 'cancelledBy']);
        }, 3);
    }

    /** @return array{allocated: string, actual: string, available: string} */
    public function availability(string $budgetAllocationVersionId): array
    {
        $version = BudgetAllocationVersion::query()->findOrFail($budgetAllocationVersionId);
        $actual = DB::table('financial_v2_fund_realizations as realization')
            ->join('financial_v2_transactions as transaction', 'transaction.id', '=', 'realization.transaction_id')
            ->join('financial_v2_journals as journal', 'journal.transaction_id', '=', 'transaction.id')
            ->where('realization.budget_allocation_version_id', $version->id)
            ->where('realization.status', 'recorded')
            ->where('journal.journal_status', 'posted')
            ->sum('transaction.gross_amount');
        $allocated = DecimalAmount::normalize($version->allocated_amount);
        $actual = DecimalAmount::normalize($actual);

        return ['allocated' => $allocated, 'actual' => $actual, 'available' => DecimalAmount::subtract($allocated, $actual)];
    }

    /** @param array<int, string> $from */
    private function changeAllocationStatus(string $allocationId, array $from, string $to, string $eventType, ?int $actorUserId): BudgetAllocation
    {
        return DB::transaction(function () use ($allocationId, $from, $to, $eventType, $actorUserId): BudgetAllocation {
            $allocation = BudgetAllocation::query()->lockForUpdate()->findOrFail($allocationId);
            if (! in_array($allocation->status, $from, true)) {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Budget Allocation is not in an eligible lifecycle state.');
            }
            $before = ['status' => $allocation->status];
            $allocation->update(['status' => $to, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($allocation->accounting_entity_id, $eventType, 'budget_allocation', $allocation->id, $allocation->correlation_id, $actorUserId, $before, ['status' => $to]);

            return $allocation->fresh();
        }, 3);
    }

    /** @param \Illuminate\Support\Collection<int, BudgetAllocationVersion> $versions @return array<string, mixed> */
    private function allocationSummary(BudgetAllocation $allocation, \Illuminate\Support\Collection $versions): array
    {
        return [
            'status' => $allocation->status,
            'fund_id' => $allocation->fund_id,
            'program_id' => $allocation->program_id,
            'allocation_reference' => $allocation->allocation_reference,
            'version_ids' => $versions->pluck('id')->sort()->values()->all(),
            'version_statuses' => $versions->mapWithKeys(fn (BudgetAllocationVersion $version) => [$version->id => $version->status])->all(),
        ];
    }

    /** @param array<string, mixed> $input @param array<int, string> $fields */
    private function requireFields(array $input, array $fields): void
    {
        foreach ($fields as $field) {
            if (! array_key_exists($field, $input) || blank($input[$field])) {
                throw new FinancialDomainException('E-BUDGET-INPUT', "{$field} is required.");
            }
        }
    }
}
