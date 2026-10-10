<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Governed in-place period correction for an unused effective Fund Policy. */
final class EffectiveFundPolicyPeriodCorrectionService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly UnusedEffectiveFundPolicyReplacementService $usageInspector,
    ) {}

    public function correct(
        string $entityId,
        string $versionId,
        string $effectiveFrom,
        ?string $effectiveTo,
        string $reason,
        ?int $actorUserId = null,
    ): FundPolicyVersion {
        $reason = trim($reason);
        if ($reason === '') {
            throw new FinancialDomainException('E-FUND-POLICY-PERIOD-REASON', 'Alasan audit koreksi periode wajib diisi.');
        }

        $from = CarbonImmutable::createFromFormat('!Y-m-d', $effectiveFrom);
        $to = $effectiveTo ? CarbonImmutable::createFromFormat('!Y-m-d', $effectiveTo) : null;
        if (! $from || $from->format('Y-m-d') !== $effectiveFrom
            || ($effectiveTo && (! $to || $to->format('Y-m-d') !== $effectiveTo))
            || ($to && $to->lt($from))) {
            throw new FinancialDomainException('E-FUND-POLICY-PERIOD-DATE', 'Rentang tanggal koreksi Fund Policy tidak valid.');
        }

        return DB::transaction(function () use ($entityId, $versionId, $from, $to, $reason, $actorUserId): FundPolicyVersion {
            $version = FundPolicyVersion::query()
                ->where('accounting_entity_id', $entityId)
                ->with('rules')
                ->lockForUpdate()
                ->findOrFail($versionId);
            Fund::query()->where('accounting_entity_id', $entityId)->whereKey($version->fund_id)->lockForUpdate()->firstOrFail();

            if ($version->status !== 'effective' || ! $version->approved_at) {
                throw new FinancialDomainException('E-FUND-POLICY-PERIOD-STATUS', 'Koreksi periode hanya tersedia untuk Fund Policy effective dengan metadata approval lengkap.');
            }
            $sameEnd = ($version->effective_to === null && $to === null)
                || ($version->effective_to !== null && $to !== null && $version->effective_to->equalTo($to));
            if ($version->effective_from->equalTo($from) && $sameEnd) {
                throw new FinancialDomainException('E-FUND-POLICY-PERIOD-NOOP', 'Tanggal koreksi sama dengan periode Fund Policy saat ini.');
            }

            $candidateTo = $to?->toDateString() ?? '9999-12-31';
            $overlap = FundPolicyVersion::query()
                ->where('accounting_entity_id', $entityId)
                ->where('fund_id', $version->fund_id)
                ->whereKeyNot($version->id)
                ->where(fn ($query) => $query->where('status', 'effective')->orWhere(fn ($historical) => $historical->where('status', 'superseded')->whereNotNull('approved_at')))
                ->where('effective_from', '<=', $candidateTo)
                ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $from->toDateString()))
                ->lockForUpdate()
                ->first();
            if ($overlap) {
                throw new FinancialDomainException(
                    'E-FUND-POLICY-PERIOD-OVERLAP',
                    "Periode koreksi bertabrakan dengan Fund Policy Version {$overlap->version_no} ({$overlap->effective_from->format('d/m/Y')}–".($overlap->effective_to?->format('d/m/Y') ?? 'tanpa batas').').',
                    ['conflicting_policy_version_id' => $overlap->id, 'conflicting_version_no' => $overlap->version_no],
                );
            }

            $currentFrom = CarbonImmutable::parse($version->effective_from->toDateString());
            $currentTo = $version->effective_to ? CarbonImmutable::parse($version->effective_to->toDateString()) : null;
            $currentUsage = $this->usageInspector->usage($version, true);
            $hasCurrentUsage = $this->hasUsage($currentUsage);
            $preservesCurrentPeriod = $from->lte($currentFrom)
                && ($currentTo === null ? $to === null : ($to === null || $to->gte($currentTo)));
            if ($hasCurrentUsage && ! $preservesCurrentPeriod) {
                throw new FinancialDomainException(
                    'E-FUND-POLICY-PERIOD-USED',
                    'Periode tidak dapat dipersempit karena policy sudah digunakan oleh Allocation atau financial fact. Koreksi hanya boleh mempertahankan seluruh cakupan periode lama.',
                    ['usage' => $currentUsage],
                );
            }

            $addedUsage = [];
            if ($from->lt($currentFrom)) {
                $addedUsage['before'] = $this->usageInspector->usage(
                    $version,
                    true,
                    $from->toDateString(),
                    $currentFrom->subDay()->toDateString(),
                );
            }
            if ($currentTo && ($to === null || $to->gt($currentTo))) {
                $addedUsage['after'] = $this->usageInspector->usage(
                    $version,
                    true,
                    $currentTo->addDay()->toDateString(),
                    $candidateTo,
                );
            }
            if (collect($addedUsage)->contains(fn (array $usage): bool => $this->hasUsage($usage))) {
                throw new FinancialDomainException(
                    'E-FUND-POLICY-PERIOD-ADDED-RANGE-USED',
                    'Rentang tanggal tambahan sudah memiliki Allocation atau financial fact. Koreksi ditolak agar histori tidak memperoleh policy secara retroaktif.',
                    ['usage' => $addedUsage],
                );
            }

            $before = $this->summary($version);
            $version->update([
                'effective_from' => $from->toDateString(),
                'effective_to' => $to?->toDateString(),
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record(
                $entityId,
                'fund_policy_effective_period_corrected',
                'fund_policy_version',
                $version->id,
                (string) Str::uuid(),
                $actorUserId,
                $before,
                $this->summary($version->fresh()) + [
                    'reason' => $reason,
                    'current_period_usage' => $currentUsage,
                    'verified_added_range_zero_usage' => $addedUsage,
                ],
            );

            return $version->fresh('rules');
        }, 3);
    }

    /** @return array<string, mixed> */
    private function summary(FundPolicyVersion $version): array
    {
        return [
            'fund_id' => $version->fund_id,
            'version_no' => $version->version_no,
            'effective_from' => $version->effective_from?->toDateString(),
            'effective_to' => $version->effective_to?->toDateString(),
            'status' => $version->status,
            'approved_at' => $version->approved_at?->toAtomString(),
        ];
    }

    /** @param array<string, int> $usage */
    private function hasUsage(array $usage): bool
    {
        return collect($usage)->contains(fn (int $count): bool => $count > 0);
    }
}
