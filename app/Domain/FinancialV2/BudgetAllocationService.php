<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\BudgetAllocationFunding;
use App\Models\FinancialV2\BudgetAllocationVersion;
use App\Models\FinancialV2\FundRealization;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Budget Allocation is a governed plan. This service intentionally has no
 * PostingEngine dependency and never writes Journal/GL facts.
 */
final class BudgetAllocationService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly FundPolicyCompatibilityService $fundPolicies,
        private readonly FinancialTransactionLifecycleService $transactionLifecycle,
    ) {}

    /** @param array<string, mixed> $input */
    public function create(array $input, ?int $actorUserId = null): BudgetAllocation
    {
        $this->requireFields($input, ['accounting_entity_id', 'accounting_period_id', 'allocation_reference', 'idempotency_key', 'allocated_amount', 'effective_from', 'reason']);

        return DB::transaction(function () use ($input, $actorUserId): BudgetAllocation {
            $entityId = $input['accounting_entity_id'];
            foreach (['accounting_period_id' => 'financial_v2_accounting_periods', 'program_id' => 'financial_v2_programs', 'account_id' => 'financial_v2_accounts', 'category_id' => 'financial_v2_categories'] as $field => $table) {
                if (! empty($input[$field]) && ! DB::table($table)->where('id', $input[$field])->where('accounting_entity_id', $entityId)->exists()) {
                    throw new FinancialDomainException('E-BUDGET-MASTER-SCOPE', 'Budget Allocation dimensions must be in the same AccountingEntity.');
                }
            }
            $amount = DecimalAmount::normalize($input['allocated_amount']);
            if (DecimalAmount::compare($amount, '0.00') <= 0) {
                throw new FinancialDomainException('E-BUDGET-AMOUNT', 'Budget Allocation amount must be positive.');
            }
            $fundings = $this->normalizeFundings($entityId, $amount, $input['fundings'] ?? null, $input['fund_id'] ?? null);
            $this->fundPolicies->assertAllocationCompatible(
                $entityId,
                $fundings->pluck('fund_id'),
                $input['effective_from'],
                $input['account_id'] ?? null,
                $input['category_id'] ?? null,
                $input['program_id'] ?? null,
            );
            $allocation = BudgetAllocation::create([
                'accounting_entity_id' => $entityId,
                'accounting_period_id' => $input['accounting_period_id'],
                // Retained as the primary/legacy Fund pointer. The complete
                // source-of-funding truth belongs to version funding lines.
                'fund_id' => $fundings->first()['fund_id'],
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
            $this->createFundingLines($version, $fundings, $actorUserId);
            $this->auditTrail->record($entityId, 'budget_allocation_created', 'budget_allocation', $allocation->id, $allocation->correlation_id, $actorUserId, null, ['status' => 'draft', 'version_id' => $version->id, 'allocated_amount' => $amount]);

            return $allocation->fresh('versions.fundings.fund');
        }, 3);
    }

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>>|null $fundings */
    public function updateDraft(string $allocationId, array $input, ?array $fundings, ?int $actorUserId = null): BudgetAllocation
    {
        return DB::transaction(function () use ($allocationId, $input, $fundings, $actorUserId): BudgetAllocation {
            $allocation = BudgetAllocation::query()->with('versions.fundings')->lockForUpdate()->findOrFail($allocationId);
            if ($allocation->status !== 'draft') {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Hanya draft alokasi yang dapat diubah.');
            }
            $version = $allocation->versions->sortByDesc('version_no')->first();
            if (! $version || $version->status !== 'draft') {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Versi draft alokasi tidak tersedia.');
            }

            $amount = DecimalAmount::normalize($input['allocated_amount'] ?? $version->allocated_amount);
            if (DecimalAmount::compare($amount, '0.00') <= 0) {
                throw new FinancialDomainException('E-BUDGET-AMOUNT', 'Budget Allocation amount must be positive.');
            }
            $normalized = $this->normalizeFundings($allocation->accounting_entity_id, $amount, $fundings, $allocation->fund_id);
            $effectiveFrom = $input['effective_from'] ?? $version->effective_from->toDateString();
            $this->fundPolicies->assertAllocationCompatible(
                $allocation->accounting_entity_id,
                $normalized->pluck('fund_id'),
                $effectiveFrom,
                $input['account_id'] ?? $allocation->account_id,
                $input['category_id'] ?? $allocation->category_id,
                $input['program_id'] ?? $allocation->program_id,
            );

            $before = $this->allocationSummary($allocation, $allocation->versions);
            $allocation->update([
                'accounting_period_id' => $input['accounting_period_id'] ?? $allocation->accounting_period_id,
                'fund_id' => $normalized->first()['fund_id'],
                'program_id' => array_key_exists('program_id', $input) ? $input['program_id'] : $allocation->program_id,
                'account_id' => array_key_exists('account_id', $input) ? $input['account_id'] : $allocation->account_id,
                'category_id' => array_key_exists('category_id', $input) ? $input['category_id'] : $allocation->category_id,
                'reason' => $input['reason'] ?? $allocation->reason,
                'updated_by_user_id' => $actorUserId,
            ]);
            $version->update([
                'allocated_amount' => $amount,
                'effective_from' => $effectiveFrom,
                'effective_to' => $input['effective_to'] ?? $version->effective_to,
                'reason' => $input['reason'] ?? $version->reason,
                'updated_by_user_id' => $actorUserId,
            ]);
            $version->fundings()->get()->each->delete();
            $this->createFundingLines($version, $normalized, $actorUserId);
            $after = $this->allocationSummary($allocation->fresh(), collect([$version->fresh('fundings')]));
            $this->auditTrail->record($allocation->accounting_entity_id, 'budget_allocation_draft_updated', 'budget_allocation', $allocation->id, $allocation->correlation_id, $actorUserId, $before, $after);

            return $allocation->fresh('versions.fundings.fund');
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
            $fundings = $version->fundings()->lockForUpdate()->get();
            $this->assertFundingTotal($fundings, $version->allocated_amount);
            $this->fundPolicies->assertAllocationCompatible(
                $allocation->accounting_entity_id,
                $fundings->pluck('fund_id'),
                $version->effective_from->toDateString(),
                $allocation->account_id,
                $allocation->category_id,
                $allocation->program_id,
            );
            $previous = BudgetAllocationVersion::query()
                ->where('budget_allocation_id', $allocation->id)
                ->where('status', 'approved')
                ->lockForUpdate()
                ->first();
            $cancelledPreviousRealizations = 0;
            if ($previous) {
                if ($version->effective_from->lte($previous->effective_from)) {
                    throw new FinancialDomainException('E-BUDGET-VERSION', 'Budget Allocation revisions must take effect after the current approved version.');
                }
                $previous->update(['status' => 'superseded', 'effective_to' => $version->effective_from->copy()->subDay(), 'updated_by_user_id' => $actorUserId]);
                $cancelledPreviousRealizations = $this->transactionLifecycle->cancelRealizationDraftsForAllocation(
                    [$previous->id],
                    'Dibatalkan otomatis karena versi Alokasi induk digantikan oleh perubahan yang disetujui.',
                    $actorUserId,
                );
            }
            $version->update(['status' => 'approved', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $allocation->update(['status' => 'approved', 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($allocation->accounting_entity_id, 'budget_allocation_version_approved', 'budget_allocation_version', $version->id, $allocation->correlation_id, $actorUserId, null, [
                'allocation_id' => $allocation->id,
                'version_no' => $version->version_no,
                'allocated_amount' => $version->allocated_amount,
                'superseded_version_id' => $previous?->id,
                'cancelled_superseded_realization_drafts' => $cancelledPreviousRealizations,
            ]);

            return $version->fresh();
        }, 3);
    }

    public function revise(string $allocationId, string|int $allocatedAmount, string $effectiveFrom, string $reason, ?int $actorUserId = null, ?array $fundings = null): BudgetAllocationVersion
    {
        if (blank($reason) || DecimalAmount::compare(DecimalAmount::normalize($allocatedAmount), '0.00') <= 0) {
            throw new FinancialDomainException('E-BUDGET-REVISION', 'Budget revisions require a positive amount and reason.');
        }

        return DB::transaction(function () use ($allocationId, $allocatedAmount, $effectiveFrom, $reason, $actorUserId, $fundings): BudgetAllocationVersion {
            $allocation = BudgetAllocation::query()->lockForUpdate()->findOrFail($allocationId);
            if ($allocation->status !== 'approved') {
                throw new FinancialDomainException('E-BUDGET-STATE', 'Only approved Budget Allocations may receive a governed revision.');
            }
            if (BudgetAllocationVersion::query()->where('budget_allocation_id', $allocation->id)->where('status', 'draft')->lockForUpdate()->exists()) {
                throw new FinancialDomainException('E-BUDGET-REVISION-PENDING', 'Masih ada perubahan Alokasi yang menunggu persetujuan. Selesaikan versi tersebut sebelum membuat perubahan baru.');
            }
            $currentVersion = BudgetAllocationVersion::query()->with('fundings')->where('budget_allocation_id', $allocation->id)->where('status', 'approved')->lockForUpdate()->first();
            if (! $currentVersion) {
                throw new FinancialDomainException('E-BUDGET-VERSION', 'Versi Alokasi yang aktif tidak tersedia.');
            }
            if ($fundings === null && $currentVersion?->fundings->count() > 1) {
                throw new FinancialDomainException('E-BUDGET-FUNDING-REQUIRED', 'Revisi alokasi multi-Dana wajib menetapkan ulang seluruh sumber Dana.');
            }
            $amount = DecimalAmount::normalize($allocatedAmount);
            $normalized = $this->normalizeFundings(
                $allocation->accounting_entity_id,
                $amount,
                $fundings,
                $currentVersion?->fundings->first()?->fund_id ?? $allocation->fund_id,
            );
            $this->fundPolicies->assertAllocationCompatible($allocation->accounting_entity_id, $normalized->pluck('fund_id'), $effectiveFrom, $allocation->account_id, $allocation->category_id, $allocation->program_id);
            $nextVersion = (int) BudgetAllocationVersion::query()->where('budget_allocation_id', $allocation->id)->max('version_no') + 1;
            $version = BudgetAllocationVersion::create([
                'accounting_entity_id' => $allocation->accounting_entity_id,
                'budget_allocation_id' => $allocation->id,
                'version_no' => $nextVersion,
                'allocated_amount' => $amount,
                'effective_from' => $effectiveFrom,
                'status' => 'draft',
                'reason' => $reason,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->createFundingLines($version, $normalized, $actorUserId);
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
            $systemReason = 'Dibatalkan otomatis karena alokasi induk dibatalkan: '.trim($reason);
            $cancelledRealizations = $this->transactionLifecycle->cancelRealizationDraftsForAllocation(
                $versions->pluck('id'),
                $systemReason,
                $actorUserId,
            );
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
            $after['cancelled_realization_drafts'] = $cancelledRealizations;
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

    /** @return array<int, array{fund_id:string,fund_name:string,allocated:string,actual:string,available:string,note:?string,source_reference:?string}> */
    public function fundingAvailability(string $budgetAllocationVersionId): array
    {
        $version = BudgetAllocationVersion::query()->with('fundings.fund')->findOrFail($budgetAllocationVersionId);
        $actualByFund = DB::table('financial_v2_fund_realizations as realization')
            ->join('financial_v2_transactions as transaction', 'transaction.id', '=', 'realization.transaction_id')
            ->join('financial_v2_transaction_splits as split', 'split.transaction_id', '=', 'transaction.id')
            ->join('financial_v2_journals as journal', 'journal.transaction_id', '=', 'transaction.id')
            ->where('realization.budget_allocation_version_id', $version->id)
            ->where('realization.status', 'recorded')
            ->where('journal.journal_status', 'posted')
            ->groupBy('split.fund_id')
            ->select('split.fund_id', DB::raw('SUM(split.split_amount) as actual'))
            ->pluck('actual', 'split.fund_id');

        return $version->fundings->map(function (BudgetAllocationFunding $funding) use ($actualByFund): array {
            $allocated = DecimalAmount::normalize($funding->amount);
            $actual = DecimalAmount::normalize($actualByFund->get($funding->fund_id, '0.00'));

            return [
                'fund_id' => $funding->fund_id,
                'fund_name' => $funding->fund?->name ?? 'Dana',
                'allocated' => $allocated,
                'actual' => $actual,
                'available' => DecimalAmount::subtract($allocated, $actual),
                'note' => $funding->note,
                'source_reference' => $funding->source_reference,
            ];
        })->all();
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
            if ($to === 'submitted') {
                $version = $allocation->versions()->where('status', 'draft')->with('fundings')->latest('version_no')->firstOrFail();
                $this->assertFundingTotal($version->fundings, $version->allocated_amount);
                $this->fundPolicies->assertAllocationCompatible(
                    $allocation->accounting_entity_id,
                    $version->fundings->pluck('fund_id'),
                    $version->effective_from->toDateString(),
                    $allocation->account_id,
                    $allocation->category_id,
                    $allocation->program_id,
                );
            }
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
            'fundings' => $versions->flatMap(fn (BudgetAllocationVersion $version) => $version->fundings()->orderBy('line_no')->get()->map(fn (BudgetAllocationFunding $funding): array => [
                'version_id' => $version->id,
                'fund_id' => $funding->fund_id,
                'amount' => $funding->amount,
                'note' => $funding->note,
                'source_reference' => $funding->source_reference,
            ]))->values()->all(),
        ];
    }

    /** @param array<int, array<string, mixed>>|null $fundings @return Collection<int, array{fund_id:string,amount:string,note:?string,source_reference:?string}> */
    private function normalizeFundings(string $entityId, string $totalAmount, ?array $fundings, ?string $fallbackFundId): Collection
    {
        $fundings = collect($fundings ?? [])
            ->filter(fn (mixed $line): bool => is_array($line) && (filled($line['fund_id'] ?? null) || filled($line['amount'] ?? null)))
            ->values();
        if ($fundings->isEmpty() && $fallbackFundId) {
            $fundings = collect([['fund_id' => $fallbackFundId, 'amount' => $totalAmount]]);
        }
        if ($fundings->isEmpty()) {
            throw new FinancialDomainException('E-BUDGET-FUNDING-REQUIRED', 'Minimal satu Sumber Dana wajib diisi.');
        }

        $normalized = $fundings->map(function (array $line) use ($entityId): array {
            $this->requireFields($line, ['fund_id', 'amount']);
            if (! DB::table('financial_v2_funds')->where('id', $line['fund_id'])->where('accounting_entity_id', $entityId)->exists()) {
                throw new FinancialDomainException('E-BUDGET-MASTER-SCOPE', 'Sumber Dana harus berada dalam AccountingEntity yang sama.');
            }
            $amount = DecimalAmount::normalize($line['amount']);
            if (DecimalAmount::compare($amount, '0.00') <= 0) {
                throw new FinancialDomainException('E-BUDGET-FUNDING-AMOUNT', 'Nominal setiap Sumber Dana harus lebih besar dari nol.');
            }

            return [
                'fund_id' => $line['fund_id'],
                'amount' => $amount,
                'note' => filled($line['note'] ?? null) ? trim((string) $line['note']) : null,
                'source_reference' => filled($line['source_reference'] ?? null) ? trim((string) $line['source_reference']) : null,
            ];
        });
        if ($normalized->pluck('fund_id')->duplicates()->isNotEmpty()) {
            throw new FinancialDomainException('E-BUDGET-FUNDING-DUPLICATE', 'Satu Dana hanya boleh muncul satu kali pada sumber alokasi.');
        }
        $this->assertFundingTotal($normalized, $totalAmount);

        return $normalized;
    }

    /** @param Collection<int, array<string, mixed>|BudgetAllocationFunding> $fundings */
    private function assertFundingTotal(Collection $fundings, string|int $totalAmount): void
    {
        $fundingTotal = DecimalAmount::sum($fundings->map(fn (array|BudgetAllocationFunding $funding): string => DecimalAmount::normalize(is_array($funding) ? $funding['amount'] : $funding->amount)));
        if (! DecimalAmount::equals($fundingTotal, DecimalAmount::normalize($totalAmount))) {
            throw new FinancialDomainException('E-BUDGET-FUNDING-MISMATCH', 'Total Sumber Dana harus sama dengan Nominal Alokasi.');
        }
    }

    /** @param Collection<int, array{fund_id:string,amount:string,note:?string,source_reference:?string}> $fundings */
    private function createFundingLines(BudgetAllocationVersion $version, Collection $fundings, ?int $actorUserId): void
    {
        $fundings->values()->each(function (array $funding, int $index) use ($version, $actorUserId): void {
            BudgetAllocationFunding::create([
                'accounting_entity_id' => $version->accounting_entity_id,
                'budget_allocation_version_id' => $version->id,
                'fund_id' => $funding['fund_id'],
                'line_no' => $index + 1,
                'amount' => $funding['amount'],
                'note' => $funding['note'],
                'source_reference' => $funding['source_reference'],
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
        });
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
