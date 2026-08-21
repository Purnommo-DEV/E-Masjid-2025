<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\HistoricalFundHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Governed source-history persistence for pre-V2 Fund explanations.
 *
 * This service must never create FinancialTransaction, Journal, JournalLine,
 * LedgerEntry, Voucher, Allocation, or Realization records. Those facts remain
 * the exclusive concern of the lifecycle and Posting Engine.
 */
final class HistoricalFundHistoryService
{
    public const MRJ_IMPORT_BATCH = 'MRJ-ZISWAF-SOURCE-HISTORY-V1';

    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @return array{created:int,existing:int,total:int} */
    public function syncMrjZiswafSource(AccountingEntity $entity, ?int $actorUserId = null): array
    {
        if ($entity->code !== 'MRJ-ACTUAL') {
            throw new FinancialDomainException('E-HISTORY-ENTITY', 'Sinkronisasi riwayat sumber hanya tersedia untuk entitas MRJ aktual.');
        }

        $funds = Fund::query()->where('accounting_entity_id', $entity->id)->get()->keyBy('code');
        $sourceHash = (string) MrjZiswafOpeningPosition::allocationSourceAudit()['source_sha256'];
        $created = 0;
        $existing = 0;
        $correlationId = (string) Str::uuid();

        foreach (MrjZiswafOpeningPosition::funds() as $sourceFund) {
            $fund = $funds->get($sourceFund['code']);
            if (! $fund) {
                throw new FinancialDomainException('E-HISTORY-FUND-MAPPING', "Pemetaan Dana sumber {$sourceFund['code']} belum tersedia.");
            }

            foreach (MrjZiswafOpeningPosition::fundSourceHistory($sourceFund['code']) as $sequence => $entry) {
                $sourceKey = $this->sourceKey($sourceHash, $sourceFund['code'], $entry);
                $record = HistoricalFundHistory::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'source_key' => $sourceKey],
                    $this->sourceRecord($entity, $fund, $sourceFund['code'], $sourceHash, $entry, $sequence + 1, $actorUserId),
                );

                if (! $record->wasRecentlyCreated) {
                    $existing++;

                    continue;
                }

                $created++;
                $this->auditTrail->record(
                    $entity->id,
                    'historical_fund_history_imported',
                    'historical_fund_history',
                    $record->id,
                    $correlationId,
                    $actorUserId,
                    null,
                    $this->summary($record),
                );
            }
        }

        return ['created' => $created, 'existing' => $existing, 'total' => $created + $existing];
    }

    /** @param array<string, mixed> $data */
    public function createCorrection(string $entityId, string $fundId, array $data, ?int $actorUserId = null): HistoricalFundHistory
    {
        return DB::transaction(function () use ($entityId, $fundId, $data, $actorUserId): HistoricalFundHistory {
            $fund = $this->fund($entityId, $fundId);
            $record = HistoricalFundHistory::create([
                'accounting_entity_id' => $entityId,
                'fund_id' => $fund->id,
                'source_key' => 'MANUAL-'.strtoupper(Str::random(32)),
                'source_fund_code' => $fund->code,
                'source_filename' => 'Koreksi admin',
                'source_worksheet' => null,
                'source_reference' => $data['source_reference'],
                'source_hash' => null,
                'import_batch_reference' => 'MANUAL-HISTORY-CORRECTION',
                'imported_at' => now(),
                'source_payload' => [
                    'origin' => 'admin_correction',
                    'source_reference' => $data['source_reference'],
                    'created_from' => 'fund_history_correction_form',
                ],
                'source_sequence' => $this->nextSequence($entityId, $fund->id),
                'entry_kind' => $data['entry_kind'],
                'effective_date' => $data['effective_date'] ?? null,
                'date_label' => $data['date_label'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'amount' => DecimalAmount::normalize($data['amount']),
                'status' => 'corrected',
                'correction_reason' => $data['correction_reason'],
                'corrected_at' => now(),
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record(
                $entityId,
                'historical_fund_history_correction_added',
                'historical_fund_history',
                $record->id,
                (string) Str::uuid(),
                $actorUserId,
                null,
                $this->summary($record),
            );

            return $record->fresh(['fund', 'createdBy', 'updatedBy']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function correct(string $entityId, string $historyId, array $data, ?int $actorUserId = null): HistoricalFundHistory
    {
        return DB::transaction(function () use ($entityId, $historyId, $data, $actorUserId): HistoricalFundHistory {
            $record = HistoricalFundHistory::query()
                ->where('accounting_entity_id', $entityId)
                ->lockForUpdate()
                ->find($historyId);
            if (! $record) {
                throw new FinancialDomainException('E-HISTORY-NOT-FOUND', 'Riwayat sumber Dana tidak ditemukan untuk entitas ini.');
            }

            $fund = $this->fund($entityId, $data['fund_id']);
            $before = $this->summary($record);
            $update = [
                'fund_id' => $fund->id,
                'entry_kind' => $data['entry_kind'],
                'effective_date' => $data['effective_date'] ?? null,
                'date_label' => $data['date_label'],
                'description' => $data['description'],
                'notes' => $data['notes'] ?? null,
                'amount' => DecimalAmount::normalize($data['amount']),
                'status' => 'corrected',
                'correction_reason' => $data['correction_reason'],
                'corrected_at' => now(),
                'updated_by_user_id' => $actorUserId,
            ];
            $changedFields = collect($update)
                ->reject(fn (mixed $value, string $field): bool => in_array($field, ['status', 'correction_reason', 'corrected_at', 'updated_by_user_id'], true))
                ->filter(fn (mixed $value, string $field): bool => (string) ($record->{$field} ?? '') !== (string) ($value ?? ''))
                ->keys()
                ->values()
                ->all();
            if ($changedFields === []) {
                throw new FinancialDomainException('E-HISTORY-NO-CHANGE', 'Ubah setidaknya satu nilai riwayat sebelum menyimpan koreksi.');
            }

            $record->update($update);
            $after = $this->summary($record->fresh());
            $after['changed_fields'] = $changedFields;
            $after['correction_reason'] = $data['correction_reason'];
            $this->auditTrail->record(
                $entityId,
                'historical_fund_history_corrected',
                'historical_fund_history',
                $record->id,
                (string) Str::uuid(),
                $actorUserId,
                $before,
                $after,
            );

            return $record->fresh(['fund', 'createdBy', 'updatedBy']);
        }, 3);
    }

    /** @param array<string, string> $entry */
    private function sourceKey(string $sourceHash, string $fundCode, array $entry): string
    {
        return hash('sha256', implode('|', [
            $sourceHash,
            $fundCode,
            $entry['kind'],
            $entry['source_reference'],
            $entry['description'],
            $entry['amount'],
        ]));
    }

    /** @param array<string, string> $entry @return array<string, mixed> */
    private function sourceRecord(AccountingEntity $entity, Fund $fund, string $sourceFundCode, string $sourceHash, array $entry, int $sequence, ?int $actorUserId): array
    {
        return [
            'fund_id' => $fund->id,
            'source_fund_code' => $sourceFundCode,
            'source_filename' => MrjZiswafOpeningPosition::SOURCE_FILENAME,
            'source_worksheet' => Str::before($entry['source_reference'], '!'),
            'source_reference' => $entry['source_reference'],
            'source_hash' => $sourceHash,
            'import_batch_reference' => self::MRJ_IMPORT_BATCH,
            'imported_at' => now(),
            'source_payload' => $entry,
            'source_sequence' => $sequence,
            'entry_kind' => $entry['kind'],
            'effective_date' => null,
            'date_label' => $entry['date_label'],
            'description' => $entry['description'],
            'notes' => $entry['notes'] ?: null,
            'amount' => DecimalAmount::normalize($entry['amount']),
            'status' => 'active',
            'created_by_user_id' => $actorUserId,
            'updated_by_user_id' => $actorUserId,
        ];
    }

    private function fund(string $entityId, string $fundId): Fund
    {
        $fund = Fund::query()->where('accounting_entity_id', $entityId)->find($fundId);
        if (! $fund) {
            throw new FinancialDomainException('E-HISTORY-FUND-MAPPING', 'Pemetaan Dana koreksi tidak tersedia untuk entitas ini.');
        }

        return $fund;
    }

    private function nextSequence(string $entityId, string $fundId): int
    {
        return ((int) HistoricalFundHistory::query()
            ->where('accounting_entity_id', $entityId)
            ->where('fund_id', $fundId)
            ->max('source_sequence')) + 1;
    }

    /** @return array<string, mixed> */
    private function summary(HistoricalFundHistory $record): array
    {
        return [
            'fund_id' => $record->fund_id,
            'source_fund_code' => $record->source_fund_code,
            'source_filename' => $record->source_filename,
            'source_worksheet' => $record->source_worksheet,
            'source_reference' => $record->source_reference,
            'source_hash' => $record->source_hash,
            'entry_kind' => $record->entry_kind,
            'effective_date' => $record->effective_date?->toDateString(),
            'date_label' => $record->date_label,
            'description' => $record->description,
            'notes' => $record->notes,
            'amount' => DecimalAmount::normalize((string) $record->amount),
            'status' => $record->status,
            'correction_reason' => $record->correction_reason,
        ];
    }
}
