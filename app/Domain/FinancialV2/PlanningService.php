<?php

namespace App\Domain\FinancialV2;

use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Planning;
use App\Models\FinancialV2\PlanningFunding;
use App\Models\FinancialV2\Program;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Planning is a non-financial commitment layer above the existing Allocation lifecycle. */
final class PlanningService
{
    public function __construct(
        private readonly FinancialReportService $reports,
        private readonly AllocationHistoryReadService $allocationHistory,
        private readonly BudgetAllocationService $allocations,
        private readonly AuditTrailService $auditTrail,
    ) {}

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $fundings */
    public function createDraft(string $entityId, array $input, array $fundings, ?int $actorUserId = null): Planning
    {
        return DB::transaction(function () use ($entityId, $input, $fundings, $actorUserId): Planning {
            $data = $this->normalizeInput($entityId, $input);
            $normalizedFundings = $this->normalizeFundings($entityId, $fundings, $data['total_amount']);
            $this->validateFundCapacity($entityId, $normalizedFundings);
            $planning = Planning::create($data + [
                'accounting_entity_id' => $entityId,
                'planning_number' => $input['planning_number'] ?? $this->nextNumber(),
                'status' => 'draft',
                'correlation_id' => (string) Str::uuid(),
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->replaceFundings($planning, $normalizedFundings, $actorUserId);
            $this->auditTrail->record($entityId, 'planning_created', 'planning', $planning->id, $planning->correlation_id, $actorUserId, null, $this->summary($planning->fresh('fundings')));

            return $planning->fresh(['program', 'fundings.fund']);
        }, 3);
    }

    /** @param array<string, mixed> $input @param array<int, array<string, mixed>> $fundings */
    public function updateDraft(string $planningId, array $input, array $fundings, ?int $actorUserId = null): Planning
    {
        return DB::transaction(function () use ($planningId, $input, $fundings, $actorUserId): Planning {
            $planning = Planning::query()->with('fundings')->lockForUpdate()->findOrFail($planningId);
            $this->assertStatus($planning, ['draft']);
            $data = $this->normalizeInput($planning->accounting_entity_id, $input);
            $normalizedFundings = $this->normalizeFundings($planning->accounting_entity_id, $fundings, $data['total_amount']);
            $this->validateFundCapacity($planning->accounting_entity_id, $normalizedFundings, $planning->id);
            $before = $this->summary($planning);
            $planning->update($data + ['updated_by_user_id' => $actorUserId]);
            $planning->fundings()->get()->each->delete();
            $this->replaceFundings($planning, $normalizedFundings, $actorUserId);
            $after = $this->summary($planning->fresh('fundings'));
            $this->auditTrail->record($planning->accounting_entity_id, 'planning_draft_updated', 'planning', $planning->id, $planning->correlation_id, $actorUserId, $before, $after);

            return $planning->fresh(['program', 'fundings.fund']);
        }, 3);
    }

    public function approve(string $planningId, ?int $actorUserId = null): Planning
    {
        return DB::transaction(function () use ($planningId, $actorUserId): Planning {
            $planning = Planning::query()->with('fundings')->lockForUpdate()->findOrFail($planningId);
            $this->assertStatus($planning, ['draft']);
            $this->lockFunds($planning->fundings->pluck('fund_id'));
            $this->validatePlanning($planning, true);
            $planning->update(['status' => 'approved', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($planning->accounting_entity_id, 'planning_approved', 'planning', $planning->id, $planning->correlation_id, $actorUserId, ['status' => 'draft'], ['status' => 'approved']);

            return $planning->fresh(['program', 'fundings.fund', 'approvedBy']);
        }, 3);
    }

    public function cancel(string $planningId, string $reason, ?int $actorUserId = null): Planning
    {
        if (blank($reason)) {
            throw new FinancialDomainException('E-PLANNING-CANCELLATION-REASON', 'Alasan pembatalan Planning wajib diisi.');
        }

        return DB::transaction(function () use ($planningId, $reason, $actorUserId): Planning {
            $planning = Planning::query()->lockForUpdate()->findOrFail($planningId);
            $this->assertStatus($planning, ['draft', 'approved']);
            $before = ['status' => $planning->status];
            $planning->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $actorUserId,
                'cancellation_reason' => trim($reason),
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($planning->accounting_entity_id, 'planning_cancelled', 'planning', $planning->id, $planning->correlation_id, $actorUserId, $before, ['status' => 'cancelled', 'reason' => trim($reason)]);

            return $planning->fresh(['fundings.fund', 'cancelledBy']);
        }, 3);
    }

    public function convertToAllocation(string $planningId, ?int $actorUserId = null): BudgetAllocation
    {
        return DB::transaction(function () use ($planningId, $actorUserId): BudgetAllocation {
            $planning = Planning::query()->with(['fundings', 'allocation'])->lockForUpdate()->findOrFail($planningId);
            if ($planning->status === 'converted' && $planning->allocation) {
                return $planning->allocation->fresh('versions.fundings.fund');
            }
            $this->assertStatus($planning, ['approved']);
            $this->preventDuplicateConversion($planning);
            $this->lockFunds($planning->fundings->pluck('fund_id'));
            $this->validatePlanning($planning, true);
            $period = AccountingPeriod::query()
                ->where('accounting_entity_id', $planning->accounting_entity_id)
                ->where('status', 'open')
                ->where('start_date', '<=', $planning->period_start)
                ->where('end_date', '>=', $planning->period_start)
                ->first();
            if (! $period) {
                throw new FinancialDomainException('E-PERIOD-CLOSED', 'Tanggal mulai Planning tidak berada dalam periode akuntansi terbuka.');
            }

            $allocation = $this->allocations->create([
                'accounting_entity_id' => $planning->accounting_entity_id,
                'planning_id' => $planning->id,
                'accounting_period_id' => $period->id,
                'fundings' => $planning->fundings->map(fn (PlanningFunding $funding): array => [
                    'fund_id' => $funding->fund_id,
                    'amount' => $funding->amount,
                    'note' => $funding->notes,
                    'source_reference' => 'Planning '.$planning->planning_number,
                ])->all(),
                'program_id' => $planning->program_id,
                'allocation_reference' => 'PLN-ALC-'.$planning->planning_number,
                'idempotency_key' => 'planning-conversion:'.$planning->id,
                'allocated_amount' => $planning->total_amount,
                'effective_from' => $planning->period_start->toDateString(),
                'effective_to' => $planning->period_end->toDateString(),
                'reason' => $planning->name,
            ], $actorUserId);
            $planning->update(['status' => 'converted', 'converted_at' => now(), 'converted_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($planning->accounting_entity_id, 'planning_converted', 'planning', $planning->id, $planning->correlation_id, $actorUserId, ['status' => 'approved'], ['status' => 'converted', 'allocation_id' => $allocation->id]);

            return $allocation;
        }, 3);
    }

    /** @return array<string, string|bool> */
    public function calculateBalanceImpact(string $entityId, string $fundId, string|int $requested = '0.00', ?string $excludePlanningId = null, ?string $asOf = null): array
    {
        $asOf ??= now()->toDateString();
        $report = $this->reports->report('fund-balance', $entityId, '1900-01-01', $asOf, ['fund_id' => $fundId]);
        $fundRow = collect($report['data']['rows'])->firstWhere('fund_id', $fundId);
        $actual = $fundRow['fund_balance'] ?? '0.00';
        $outstanding = $this->allocationHistory->summary($entityId, $fundId)['remaining'];
        $approved = PlanningFunding::query()
            ->join('financial_v2_plannings as planning', 'planning.id', '=', 'financial_v2_planning_fundings.planning_id')
            ->where('financial_v2_planning_fundings.accounting_entity_id', $entityId)
            ->where('financial_v2_planning_fundings.fund_id', $fundId)
            ->where('planning.status', 'approved')
            ->when($excludePlanningId, fn ($query, string $id) => $query->where('planning.id', '<>', $id))
            ->sum('financial_v2_planning_fundings.amount');
        $actual = DecimalAmount::normalize($actual);
        $outstanding = DecimalAmount::normalize($outstanding);
        $approved = DecimalAmount::normalize($approved);
        $requested = DecimalAmount::normalize($requested);
        $available = DecimalAmount::subtract(DecimalAmount::subtract($actual, $outstanding), $approved);
        $projected = DecimalAmount::subtract($available, $requested);
        $shortfall = DecimalAmount::compare($projected, '0.00') < 0 ? DecimalAmount::negate($projected) : '0.00';

        return compact('actual', 'outstanding', 'approved', 'available', 'requested', 'projected', 'shortfall') + ['sufficient' => DecimalAmount::compare($projected, '0.00') >= 0];
    }

    /** @param Collection<int, array<string, mixed>> $fundings */
    public function validateFundCapacity(string $entityId, Collection $fundings, ?string $excludePlanningId = null, ?string $asOf = null): void
    {
        foreach ($fundings as $funding) {
            $impact = $this->calculateBalanceImpact($entityId, $funding['fund_id'], $funding['amount'], $excludePlanningId, $asOf);
            if (! $impact['sufficient']) {
                throw new FinancialDomainException('E-PLANNING-FUND-CAPACITY', sprintf(
                    'Dana tidak mencukupi. Saldo aktual %s; allocation berjalan %s; planning disetujui %s; permintaan %s; kekurangan %s.',
                    DecimalAmount::formatIndonesian($impact['actual'], true),
                    DecimalAmount::formatIndonesian($impact['outstanding'], true),
                    DecimalAmount::formatIndonesian($impact['approved'], true),
                    DecimalAmount::formatIndonesian($impact['requested'], true),
                    DecimalAmount::formatIndonesian($impact['shortfall'], true),
                ));
            }
        }
    }

    /** @return array{total:string,lines:int} */
    public function calculateFundingSummary(Planning $planning): array
    {
        $planning->loadMissing('fundings');

        return ['total' => DecimalAmount::sum($planning->fundings->pluck('amount')), 'lines' => $planning->fundings->count()];
    }

    public function preventDuplicateConversion(Planning $planning): void
    {
        if ($planning->allocation()->exists()) {
            throw new FinancialDomainException('E-PLANNING-DUPLICATE-CONVERSION', 'Planning sudah memiliki Allocation.');
        }
    }

    /** @param array<int, array<string, mixed>> $fundings */
    public function validateFundingTotal(string $entityId, array $fundings, string|int $total): Collection
    {
        return $this->normalizeFundings($entityId, $fundings, DecimalAmount::normalize($total));
    }

    private function validatePlanning(Planning $planning, bool $capacity): void
    {
        $planning->loadMissing('fundings');
        $normalized = $this->normalizeFundings($planning->accounting_entity_id, $planning->fundings->map(fn (PlanningFunding $line): array => ['fund_id' => $line->fund_id, 'amount' => $line->amount, 'notes' => $line->notes])->all(), $planning->total_amount);
        $this->normalizeInput($planning->accounting_entity_id, $planning->only(['name', 'period_start', 'period_end', 'program_id', 'target_recipient_count', 'amount_per_recipient', 'total_amount', 'notes']));
        if ($capacity) {
            $this->validateFundCapacity($planning->accounting_entity_id, $normalized, $planning->id);
        }
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function normalizeInput(string $entityId, array $input): array
    {
        foreach (['name', 'period_start', 'period_end', 'total_amount'] as $field) {
            if (blank($input[$field] ?? null)) {
                throw new FinancialDomainException('E-PLANNING-INPUT', $field.' wajib diisi.');
            }
        }
        $start = \Carbon\CarbonImmutable::parse($input['period_start'])->startOfDay();
        $end = \Carbon\CarbonImmutable::parse($input['period_end'])->startOfDay();
        if ($end->lt($start)) {
            throw new FinancialDomainException('E-PLANNING-PERIOD', 'Tanggal akhir Planning tidak boleh sebelum tanggal mulai.');
        }
        $programId = filled($input['program_id'] ?? null) ? (string) $input['program_id'] : null;
        if ($programId && ! Program::query()->whereKey($programId)->where('accounting_entity_id', $entityId)->where('status', 'active')->where(fn ($query) => $query->whereNull('start_date')->orWhere('start_date', '<=', $start->toDateString()))->where(fn ($query) => $query->whereNull('end_date')->orWhere('end_date', '>=', $end->toDateString()))->exists()) {
            throw new FinancialDomainException('E-PLANNING-PROGRAM', 'Program harus aktif, satu entitas, dan berlaku selama periode Planning.');
        }
        $total = DecimalAmount::normalize($input['total_amount']);
        if (DecimalAmount::compare($total, '0.00') <= 0) {
            throw new FinancialDomainException('E-PLANNING-AMOUNT', 'Total Planning harus lebih besar dari nol.');
        }
        $count = filled($input['target_recipient_count'] ?? null) ? (int) $input['target_recipient_count'] : null;
        if ($count !== null && $count < 0) {
            throw new FinancialDomainException('E-PLANNING-RECIPIENTS', 'Target penerima tidak boleh negatif.');
        }
        $perRecipient = filled($input['amount_per_recipient'] ?? null) ? DecimalAmount::normalize($input['amount_per_recipient']) : null;
        if ($perRecipient !== null && DecimalAmount::compare($perRecipient, '0.00') < 0) {
            throw new FinancialDomainException('E-PLANNING-AMOUNT', 'Nominal per penerima tidak boleh negatif.');
        }
        if ($count !== null && $perRecipient !== null && ! DecimalAmount::equals(DecimalAmount::multiplyByInteger($perRecipient, $count), $total)) {
            throw new FinancialDomainException('E-PLANNING-RECIPIENT-TOTAL', 'Total Planning harus sama dengan target penerima dikali nominal per penerima.');
        }

        return [
            'name' => trim((string) $input['name']),
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'program_id' => $programId,
            'target_recipient_count' => $count,
            'amount_per_recipient' => $perRecipient,
            'total_amount' => $total,
            'notes' => filled($input['notes'] ?? null) ? trim((string) $input['notes']) : null,
        ];
    }

    /** @param array<int, array<string, mixed>> $fundings @return Collection<int, array{fund_id:string,amount:string,notes:?string}> */
    private function normalizeFundings(string $entityId, array $fundings, string $total): Collection
    {
        $normalized = collect($fundings)->filter(fn ($line): bool => is_array($line) && (filled($line['fund_id'] ?? null) || filled($line['amount'] ?? null)))->values()->map(function (array $line) use ($entityId): array {
            $fund = Fund::query()->whereKey($line['fund_id'] ?? null)->where('accounting_entity_id', $entityId)->where('status', 'active')->first();
            if (! $fund) {
                throw new FinancialDomainException('E-PLANNING-FUND', 'Setiap sumber Dana harus aktif dan berada dalam AccountingEntity yang sama.');
            }
            $amount = DecimalAmount::normalize($line['amount'] ?? '');
            if (DecimalAmount::compare($amount, '0.00') <= 0) {
                throw new FinancialDomainException('E-PLANNING-FUNDING-AMOUNT', 'Nominal setiap sumber Dana harus lebih besar dari nol.');
            }

            return ['fund_id' => $fund->id, 'amount' => $amount, 'notes' => filled($line['notes'] ?? null) ? trim((string) $line['notes']) : null];
        });
        if ($normalized->isEmpty()) {
            throw new FinancialDomainException('E-PLANNING-FUNDING-REQUIRED', 'Minimal satu sumber Dana wajib diisi.');
        }
        if ($normalized->pluck('fund_id')->duplicates()->isNotEmpty()) {
            throw new FinancialDomainException('E-PLANNING-FUNDING-DUPLICATE', 'Satu Dana hanya boleh muncul satu kali dalam Planning.');
        }
        if (! DecimalAmount::equals(DecimalAmount::sum($normalized->pluck('amount')), $total)) {
            throw new FinancialDomainException('E-PLANNING-FUNDING-MISMATCH', 'Total sumber Dana harus sama dengan Total Planning.');
        }

        return $normalized;
    }

    /** @param Collection<int, array{fund_id:string,amount:string,notes:?string}> $fundings */
    private function replaceFundings(Planning $planning, Collection $fundings, ?int $actorUserId): void
    {
        $fundings->each(fn (array $line, int $index) => PlanningFunding::create($line + [
            'accounting_entity_id' => $planning->accounting_entity_id,
            'planning_id' => $planning->id,
            'line_no' => $index + 1,
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
        ]));
    }

    /** @param Collection<int, string> $fundIds */
    private function lockFunds(Collection $fundIds): void
    {
        Fund::query()->whereIn('id', $fundIds->sort()->values())->orderBy('id')->lockForUpdate()->get();
    }

    /** @param array<int, string> $allowed */
    private function assertStatus(Planning $planning, array $allowed): void
    {
        if (! in_array($planning->status, $allowed, true)) {
            throw new FinancialDomainException('E-PLANNING-STATE', 'Planning tidak berada pada status yang diizinkan untuk aksi ini.');
        }
    }

    private function nextNumber(): string
    {
        return 'PLN-'.now()->format('Ym').'-'.Str::upper(substr((string) Str::uuid(), 0, 12));
    }

    /** @return array<string, mixed> */
    private function summary(Planning $planning): array
    {
        return ['status' => $planning->status, 'name' => $planning->name, 'period_start' => $planning->period_start?->toDateString(), 'period_end' => $planning->period_end?->toDateString(), 'program_id' => $planning->program_id, 'total_amount' => $planning->total_amount, 'fundings' => $planning->fundings->map(fn (PlanningFunding $line): array => ['fund_id' => $line->fund_id, 'amount' => $line->amount])->all()];
    }
}
