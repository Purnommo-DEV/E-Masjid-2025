<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AuditEvent;

/** Emits the immutable, application-level financial audit contract. */
final class AuditTrailService
{
    /** @param array<string, mixed>|null $before @param array<string, mixed>|null $after */
    public function record(string $entityId, string $eventType, string $targetType, string $targetId, string $correlationId, ?int $actorUserId = null, ?array $before = null, ?array $after = null): void
    {
        $beforeSummary = $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR);
        $afterSummary = $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR);

        AuditEvent::create([
            'accounting_entity_id' => $entityId,
            'event_at' => now(),
            'event_type' => $eventType,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'actor_user_id' => $actorUserId,
            'correlation_id' => $correlationId,
            'before_summary' => $beforeSummary,
            'after_summary' => $afterSummary,
            'integrity_hash' => hash('sha256', implode('|', [$entityId, $eventType, $targetType, $targetId, $correlationId, $beforeSummary, $afterSummary])),
            'created_at' => now(),
        ]);
    }
}
