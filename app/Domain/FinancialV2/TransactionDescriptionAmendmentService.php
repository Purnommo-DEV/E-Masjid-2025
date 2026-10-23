<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\FinancialTransaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Maintains presentation-only description amendments without mutating posted
 * transaction, journal, ledger, or balance facts.
 */
final class TransactionDescriptionAmendmentService
{
    public const EVENT_TYPE = 'transaction_description_amended';

    public function __construct(private readonly AuditTrailService $auditTrail) {}

    public function amend(string $entityId, string $transactionId, string $description, int $actorUserId): FinancialTransaction
    {
        return DB::transaction(function () use ($entityId, $transactionId, $description, $actorUserId): FinancialTransaction {
            $transaction = FinancialTransaction::query()
                ->where('accounting_entity_id', $entityId)
                ->lockForUpdate()
                ->findOrFail($transactionId);

            if (! in_array($transaction->status, ['posted', 'reversed'], true)) {
                throw new FinancialDomainException(
                    'E-DESCRIPTION-AMENDMENT-STATE',
                    'Edit Keterangan pada riwayat hanya tersedia untuk transaksi yang sudah dicatat resmi atau dibalik.'
                );
            }

            $latest = $this->latestAmendment($entityId, $transactionId);
            $before = $latest['description'] ?? (string) ($transaction->description ?? '');
            if ($before === $description) {
                throw new FinancialDomainException('E-DESCRIPTION-AMENDMENT-NOOP', 'Keterangan baru harus berbeda dari keterangan saat ini.');
            }

            $this->auditTrail->record(
                $entityId,
                self::EVENT_TYPE,
                'transaction',
                $transaction->id,
                $transaction->correlation_id,
                $actorUserId,
                ['description' => $before],
                [
                    'description' => $description,
                    'amendment_no' => ($latest['amendment_no'] ?? 0) + 1,
                    'original_transaction_unchanged' => true,
                ],
            );

            return $transaction;
        });
    }

    /** @return Collection<string, string> */
    public function latestDescriptions(string $entityId, Collection $transactionIds): Collection
    {
        if ($transactionIds->isEmpty()) {
            return collect();
        }

        return AuditEvent::query()
            ->where('accounting_entity_id', $entityId)
            ->where('event_type', self::EVENT_TYPE)
            ->where('target_type', 'transaction')
            ->whereIn('target_id', $transactionIds)
            ->get(['target_id', 'after_summary'])
            ->groupBy('target_id')
            ->map(function (Collection $events): string {
                $latest = $events
                    ->map(fn (AuditEvent $event): array => $this->decodeAfter($event))
                    ->sortByDesc('amendment_no')
                    ->first();

                return (string) ($latest['description'] ?? '');
            });
    }

    /** @return array{description: string, amendment_no: int}|null */
    private function latestAmendment(string $entityId, string $transactionId): ?array
    {
        return AuditEvent::query()
            ->where('accounting_entity_id', $entityId)
            ->where('event_type', self::EVENT_TYPE)
            ->where('target_type', 'transaction')
            ->where('target_id', $transactionId)
            ->get(['after_summary'])
            ->map(fn (AuditEvent $event): array => $this->decodeAfter($event))
            ->sortByDesc('amendment_no')
            ->first();
    }

    /** @return array{description: string, amendment_no: int} */
    private function decodeAfter(AuditEvent $event): array
    {
        $after = json_decode((string) $event->after_summary, true);

        return [
            'description' => (string) ($after['description'] ?? ''),
            'amendment_no' => (int) ($after['amendment_no'] ?? 0),
        ];
    }
}
