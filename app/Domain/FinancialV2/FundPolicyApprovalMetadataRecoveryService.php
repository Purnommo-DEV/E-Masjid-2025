<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\FundPolicyVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Recovers missing approval metadata only from a verified immutable approval audit event. */
final class FundPolicyApprovalMetadataRecoveryService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    public function recover(string $entityId, string $versionId, ?int $actorUserId = null): FundPolicyVersion
    {
        return DB::transaction(function () use ($entityId, $versionId, $actorUserId): FundPolicyVersion {
            $version = FundPolicyVersion::query()
                ->where('accounting_entity_id', $entityId)
                ->lockForUpdate()
                ->findOrFail($versionId);

            if ($version->status !== 'effective') {
                throw new FinancialDomainException('E-FUND-POLICY-APPROVAL-RECOVERY-STATUS', 'Pemulihan metadata approval hanya tersedia untuk Fund Policy berstatus effective.');
            }
            if ($version->approved_at) {
                return $version;
            }

            $event = AuditEvent::query()
                ->where('accounting_entity_id', $entityId)
                ->where('target_type', 'fund_policy_version')
                ->where('target_id', $version->id)
                ->where('event_type', 'fund_policy_version_effective')
                ->orderByDesc('event_at')
                ->lockForUpdate()
                ->first();
            if (! $event || ! $this->hasValidIntegrityHash($event)) {
                throw new FinancialDomainException('E-FUND-POLICY-APPROVAL-RECOVERY-EVIDENCE', 'Bukti audit aktivasi policy tidak tersedia atau integritasnya tidak valid. Pulihkan dokumen persetujuan dan audit event melalui proses governance sebelum melanjutkan.');
            }

            $after = json_decode((string) $event->after_summary, true);
            $approvedAt = is_array($after) ? ($after['approved_at'] ?? null) : null;
            if (($after['status'] ?? null) !== 'effective' || ! is_string($approvedAt) || blank($approvedAt)) {
                throw new FinancialDomainException('E-FUND-POLICY-APPROVAL-RECOVERY-EVIDENCE', 'Audit event tidak membuktikan approval effective yang lengkap. Metadata tidak diubah.');
            }

            $before = ['approved_at' => $version->approved_at, 'approved_by_user_id' => $version->approved_by_user_id];
            $version->update([
                'approved_at' => CarbonImmutable::parse($approvedAt),
                'approved_by_user_id' => $event->actor_user_id,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record(
                $entityId,
                'fund_policy_approval_metadata_recovered',
                'fund_policy_version',
                $version->id,
                (string) Str::uuid(),
                $actorUserId,
                $before,
                [
                    'approved_at' => $version->fresh()->approved_at?->toAtomString(),
                    'approved_by_user_id' => $event->actor_user_id,
                    'source_audit_event_id' => $event->id,
                ],
            );

            return $version->fresh();
        }, 3);
    }

    private function hasValidIntegrityHash(AuditEvent $event): bool
    {
        if (blank($event->integrity_hash)) {
            return false;
        }

        $expected = hash('sha256', implode('|', [
            $event->accounting_entity_id,
            $event->event_type,
            $event->target_type,
            $event->target_id,
            $event->correlation_id,
            $event->before_summary,
            $event->after_summary,
        ]));

        return hash_equals($expected, (string) $event->integrity_hash);
    }
}
