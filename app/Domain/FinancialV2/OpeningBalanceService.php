<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\LegacyMapping;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Governed intake for a V2 opening position. It stores mapping and source
 * evidence metadata only; PostingEngine remains the only facts writer.
 */
final class OpeningBalanceService
{
    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly PostingEngine $posting,
    ) {}

    /** @param array{accounting_entity_id:string,code:string,name:string,source_system_name:string,position_date?:string|null} $input */
    public function createMappingSet(array $input, ?int $actorUserId = null): MappingSet
    {
        foreach (['accounting_entity_id', 'code', 'name', 'source_system_name'] as $field) {
            if (blank($input[$field] ?? null)) {
                throw new FinancialDomainException('E-OPENING-MAPPING-INPUT', "{$field} is required for an opening-balance mapping set.");
            }
        }

        $this->activeEntity($input['accounting_entity_id']);

        try {
            $set = MappingSet::create([
                'accounting_entity_id' => $input['accounting_entity_id'],
                'code' => trim($input['code']),
                'name' => trim($input['name']),
                'source_system_name' => trim($input['source_system_name']),
                // A rehearsal position date is metadata, not a selected production cutover date.
                'cutover_date' => $input['position_date'] ?? null,
                'mapping_status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
        } catch (QueryException $exception) {
            if (str_contains($exception->getMessage(), 'fv2_mapping_set_entity_code_uq')) {
                throw new FinancialDomainException('E-OPENING-MAPPING-DUPLICATE', 'The mapping-set code is already registered for this entity.');
            }

            throw $exception;
        }

        $this->audit($set->accounting_entity_id, 'opening_mapping_set_drafted', 'mapping_set', $set->id, $actorUserId, null, $this->mappingSummary($set));

        return $set;
    }

    /**
     * Records an explicit mapping outcome. Ambiguous or unmapped values never
     * receive a target automatically; they remain a blocking governance state.
     *
     * @param  'account'|'financial_account'|'fund'|'program'  $dimension
     * @param  'mapped'|'unmapped'|'ambiguous'|'rejected'  $outcome
     */
    public function recordMapping(string $mappingSetId, string $sourceReference, string $dimension, string $outcome, ?string $targetId = null, ?string $sourceValue = null, ?string $rationale = null, ?int $actorUserId = null): LegacyMapping
    {
        if (blank($sourceReference) || ! in_array($dimension, ['account', 'financial_account', 'fund', 'program'], true) || ! in_array($outcome, ['mapped', 'unmapped', 'ambiguous', 'rejected'], true)) {
            throw new FinancialDomainException('E-OPENING-MAPPING-INPUT', 'Mapping needs a source reference, supported dimension, and explicit outcome.');
        }
        if (in_array($outcome, ['ambiguous', 'rejected'], true) && blank($rationale)) {
            throw new FinancialDomainException('E-OPENING-MAPPING-RATIONALE', 'Ambiguous or rejected source values require an explanation.');
        }
        if ($outcome === 'mapped' && blank($targetId)) {
            throw new FinancialDomainException('E-OPENING-MAPPING-TARGET', 'A MAPPED outcome requires its approved V2 target.');
        }
        if ($outcome !== 'mapped' && $targetId !== null) {
            throw new FinancialDomainException('E-OPENING-MAPPING-TARGET', 'Only a MAPPED outcome may select a V2 target.');
        }

        return DB::transaction(function () use ($mappingSetId, $sourceReference, $dimension, $outcome, $targetId, $sourceValue, $rationale, $actorUserId): LegacyMapping {
            $set = MappingSet::query()->lockForUpdate()->findOrFail($mappingSetId);
            if (! in_array($set->mapping_status, ['draft', 'reviewed'], true)) {
                throw new FinancialDomainException('E-OPENING-MAPPING-STATE', 'Approved or frozen mapping sets cannot be changed. Create a new governed mapping set.');
            }
            if ($outcome === 'mapped') {
                $this->assertDimensionTarget($set->accounting_entity_id, $dimension, $targetId);
            }
            $recordRef = $this->mappingRecordReference($sourceReference, $dimension);
            if (LegacyMapping::query()->where('mapping_set_id', $set->id)->where('legacy_record_ref', $recordRef)->exists()) {
                throw new FinancialDomainException('E-OPENING-MAPPING-DUPLICATE', 'This source and dimension are already mapped in the selected mapping set.');
            }
            $status = match ($outcome) {
                'mapped' => 'confirmed',
                'unmapped' => 'draft',
                'ambiguous' => 'exception',
                'rejected' => 'out_of_scope_archive',
            };
            $mapping = LegacyMapping::create([
                'accounting_entity_id' => $set->accounting_entity_id,
                'mapping_set_id' => $set->id,
                'legacy_record_ref' => $recordRef,
                'legacy_value' => $sourceValue,
                'target_entity_type' => $outcome === 'mapped' ? $dimension : null,
                'target_entity_id' => $outcome === 'mapped' ? $targetId : null,
                'mapping_status' => $status,
                'rationale' => $rationale ?: 'Explicit mapping outcome: '.strtoupper($outcome),
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->audit($set->accounting_entity_id, 'opening_mapping_recorded', 'legacy_mapping', $mapping->id, $actorUserId, null, ['source_reference' => $sourceReference, 'dimension' => $dimension, 'outcome' => strtoupper($outcome), 'target_id' => $targetId]);

            return $mapping;
        }, 3);
    }

    public function reviewMappingSet(string $mappingSetId, ?int $actorUserId = null): MappingSet
    {
        return $this->transitionMappingSet($mappingSetId, 'draft', 'reviewed', 'opening_mapping_set_reviewed', $actorUserId);
    }

    public function approveMappingSet(string $mappingSetId, ?int $actorUserId = null): MappingSet
    {
        return DB::transaction(function () use ($mappingSetId, $actorUserId): MappingSet {
            $set = MappingSet::query()->lockForUpdate()->findOrFail($mappingSetId);
            if ($set->mapping_status !== 'reviewed') {
                throw new FinancialDomainException('E-OPENING-MAPPING-STATE', 'Only a reviewed mapping set can be approved.');
            }
            if (! LegacyMapping::query()->where('mapping_set_id', $set->id)->exists() || LegacyMapping::query()->where('mapping_set_id', $set->id)->whereIn('mapping_status', ['draft', 'provisional', 'exception'])->exists()) {
                throw new FinancialDomainException('E-OPENING-MAPPING-UNRESOLVED', 'All mappings must be explicit and non-ambiguous before approval.');
            }
            $before = $this->mappingSummary($set);
            $set->update(['mapping_status' => 'approved', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
            $this->audit($set->accounting_entity_id, 'opening_mapping_set_approved', 'mapping_set', $set->id, $actorUserId, $before, $this->mappingSummary($set->fresh()));

            return $set->fresh();
        }, 3);
    }

    /** @param array{accounting_entity_id:string,accounting_period_id:string,mapping_set_id:string,position_date:string,rehearsal_reference:string,evidence_package_ref:string} $input */
    public function createDraft(array $input, ?int $actorUserId = null): OpeningBalanceBatch
    {
        foreach (['accounting_entity_id', 'accounting_period_id', 'mapping_set_id', 'position_date', 'rehearsal_reference', 'evidence_package_ref'] as $field) {
            if (blank($input[$field] ?? null)) {
                throw new FinancialDomainException('E-OPENING-INPUT', "{$field} is required for an opening-balance rehearsal batch.");
            }
        }

        return DB::transaction(function () use ($input, $actorUserId): OpeningBalanceBatch {
            $entity = $this->activeEntity($input['accounting_entity_id']);
            $period = AccountingPeriod::query()->where('accounting_entity_id', $entity->id)->lockForUpdate()->findOrFail($input['accounting_period_id']);
            $this->assertPositionDateInPeriod($period, $input['position_date']);
            $set = MappingSet::query()->where('accounting_entity_id', $entity->id)->lockForUpdate()->findOrFail($input['mapping_set_id']);
            if (OpeningBalanceBatch::query()->where('accounting_entity_id', $entity->id)->where('cutover_reference', $input['rehearsal_reference'])->exists()) {
                throw new FinancialDomainException('E-OPENING-DUPLICATE', 'This opening-position rehearsal reference has already been imported for the entity.');
            }
            $batch = OpeningBalanceStateGuard::withinOpeningBalance(fn () => OpeningBalanceBatch::create([
                'accounting_entity_id' => $entity->id,
                'accounting_period_id' => $period->id,
                'mapping_set_id' => $set->id,
                // Existing Foundation column: populated only as a test/rehearsal position date in Phase 6.
                'cutover_date' => $input['position_date'],
                'cutover_reference' => trim($input['rehearsal_reference']),
                'evidence_package_ref' => trim($input['evidence_package_ref']),
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->audit($entity->id, 'opening_balance_drafted', 'opening_balance_batch', $batch->id, $actorUserId, null, $this->batchSummary($batch));

            return $batch;
        }, 3);
    }

    /** @param array{account_id:string,fund_id?:string|null,financial_account_id?:string|null,program_id?:string|null,debit_amount:int|string,credit_amount:int|string,source_debit_amount:int|string,source_credit_amount:int|string,source_reference:string,evidence_ref:string,line_description?:string|null} $input */
    public function addLine(string $batchId, array $input, ?int $actorUserId = null): OpeningBalanceLine
    {
        foreach (['account_id', 'debit_amount', 'credit_amount', 'source_debit_amount', 'source_credit_amount', 'source_reference', 'evidence_ref'] as $field) {
            if (! array_key_exists($field, $input) || blank($input[$field])) {
                throw new FinancialDomainException('E-OPENING-LINE-INPUT', "{$field} is required for an opening-balance line.");
            }
        }

        return DB::transaction(function () use ($batchId, $input, $actorUserId): OpeningBalanceLine {
            $batch = OpeningBalanceBatch::query()->with('mappingSet')->lockForUpdate()->findOrFail($batchId);
            if ($batch->status !== 'draft') {
                throw new FinancialDomainException('E-OPENING-STATE', 'Only Draft opening balances may receive source lines.');
            }
            $debit = DecimalAmount::normalize($input['debit_amount']);
            $credit = DecimalAmount::normalize($input['credit_amount']);
            $sourceDebit = DecimalAmount::normalize($input['source_debit_amount']);
            $sourceCredit = DecimalAmount::normalize($input['source_credit_amount']);
            $this->assertOneSidedAmount($debit, $credit, 'V2 opening line');
            $this->assertOneSidedAmount($sourceDebit, $sourceCredit, 'approved source position');
            $this->assertDimensionTarget($batch->accounting_entity_id, 'account', $input['account_id']);
            foreach (['fund' => $input['fund_id'] ?? null, 'financial_account' => $input['financial_account_id'] ?? null, 'program' => $input['program_id'] ?? null] as $dimension => $targetId) {
                if ($targetId !== null) {
                    $this->assertDimensionTarget($batch->accounting_entity_id, $dimension, $targetId);
                }
            }
            $source = trim($input['source_reference']);
            $this->assertMappedDimension($batch, $source, 'account', $input['account_id']);
            foreach (['fund' => $input['fund_id'] ?? null, 'financial_account' => $input['financial_account_id'] ?? null, 'program' => $input['program_id'] ?? null] as $dimension => $targetId) {
                if ($targetId !== null) {
                    $this->assertMappedDimension($batch, $source, $dimension, $targetId);
                }
            }
            $lineNo = ((int) OpeningBalanceLine::query()->where('opening_balance_batch_id', $batch->id)->max('line_no')) + 1;
            $line = OpeningBalanceStateGuard::withinOpeningBalance(fn () => OpeningBalanceLine::create([
                'accounting_entity_id' => $batch->accounting_entity_id,
                'opening_balance_batch_id' => $batch->id,
                'line_no' => $lineNo,
                'account_id' => $input['account_id'],
                'fund_id' => $input['fund_id'] ?? null,
                'financial_account_id' => $input['financial_account_id'] ?? null,
                'program_id' => $input['program_id'] ?? null,
                'debit_amount' => $debit,
                'credit_amount' => $credit,
                'evidence_ref' => trim($input['evidence_ref']),
                'mapping_ref' => $source,
                'source_reference' => $source,
                'source_debit_amount' => $sourceDebit,
                'source_credit_amount' => $sourceCredit,
                'reconciliation_difference' => DecimalAmount::subtract(DecimalAmount::subtract($debit, $credit), DecimalAmount::subtract($sourceDebit, $sourceCredit)),
                'reconciliation_status' => 'draft',
                'line_description' => $input['line_description'] ?? null,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]));
            $this->audit($batch->accounting_entity_id, 'opening_balance_line_added', 'opening_balance_line', $line->id, $actorUserId, null, $this->lineSummary($line));

            return $line;
        }, 3);
    }

    /** Recomputes explicit source-to-V2 differences; it never changes accounting facts. */
    public function reconcile(string $batchId, ?int $actorUserId = null): array
    {
        return DB::transaction(function () use ($batchId, $actorUserId): array {
            $batch = OpeningBalanceBatch::query()->with('lines')->lockForUpdate()->findOrFail($batchId);
            if (! in_array($batch->status, ['draft', 'reviewed'], true)) {
                throw new FinancialDomainException('E-OPENING-STATE', 'Only Draft or Reviewed opening balances can be reconciled.');
            }
            $rows = [];
            foreach ($batch->lines as $line) {
                $difference = DecimalAmount::subtract(
                    DecimalAmount::subtract($line->debit_amount, $line->credit_amount),
                    DecimalAmount::subtract($line->source_debit_amount, $line->source_credit_amount),
                );
                $status = DecimalAmount::equals($difference, '0.00') ? 'reconciled' : 'difference';
                OpeningBalanceStateGuard::withinOpeningBalance(fn () => $line->update([
                    'reconciliation_difference' => $difference,
                    'reconciliation_status' => $status,
                    'updated_by_user_id' => $actorUserId,
                ]));
                $rows[] = $this->lineSummary($line->fresh());
            }
            $summary = $this->summary($batch->fresh());
            $this->audit($batch->accounting_entity_id, 'opening_balance_reconciled', 'opening_balance_batch', $batch->id, $actorUserId, null, ['difference' => $summary['totals']['difference'], 'line_count' => count($rows)]);

            return $summary;
        }, 3);
    }

    public function review(string $batchId, ?int $actorUserId = null): OpeningBalanceBatch
    {
        $this->reconcile($batchId, $actorUserId);

        return DB::transaction(function () use ($batchId, $actorUserId): OpeningBalanceBatch {
            $batch = OpeningBalanceBatch::query()->with('lines')->lockForUpdate()->findOrFail($batchId);
            if ($batch->status !== 'draft') {
                throw new FinancialDomainException('E-OPENING-STATE', 'Only Draft opening balances may be reviewed.');
            }
            $this->assertBatchBalancedAndReconciled($batch);
            $before = $this->batchSummary($batch);
            OpeningBalanceStateGuard::withinOpeningBalance(fn () => $batch->update(['status' => 'reviewed', 'reviewed_at' => now(), 'reviewed_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]));
            $this->audit($batch->accounting_entity_id, 'opening_balance_reviewed', 'opening_balance_batch', $batch->id, $actorUserId, $before, $this->batchSummary($batch->fresh()));

            return $batch->fresh();
        }, 3);
    }

    public function approve(string $batchId, ?int $actorUserId = null): OpeningBalanceBatch
    {
        return DB::transaction(function () use ($batchId, $actorUserId): OpeningBalanceBatch {
            $batch = OpeningBalanceBatch::query()->with(['lines', 'mappingSet'])->lockForUpdate()->findOrFail($batchId);
            if ($batch->status !== 'reviewed') {
                throw new FinancialDomainException('E-OPENING-STATE', 'Only Reviewed opening balances may be approved.');
            }
            if (! $batch->mappingSet || ! in_array($batch->mappingSet->mapping_status, ['approved', 'frozen'], true)) {
                throw new FinancialDomainException('E-OPENING-MAPPING', 'Opening balance requires an approved mapping set.');
            }
            $this->assertBatchBalancedAndReconciled($batch);
            $this->assertRequiredEvidence($batch);
            $before = $this->batchSummary($batch);
            OpeningBalanceStateGuard::withinOpeningBalance(fn () => $batch->update(['status' => 'approved', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]));
            $this->audit($batch->accounting_entity_id, 'opening_balance_approved', 'opening_balance_batch', $batch->id, $actorUserId, $before, $this->batchSummary($batch->fresh()));

            return $batch->fresh();
        }, 3);
    }

    public function post(string $batchId, ?int $actorUserId = null): PostingResult
    {
        $batch = OpeningBalanceBatch::query()->with('lines')->findOrFail($batchId);
        if (! in_array($batch->status, ['approved', 'posted'], true)) {
            throw new FinancialDomainException('E-OPENING-STATE', 'Only an approved opening balance may be posted.');
        }
        $type = TransactionType::query()->where('accounting_entity_id', $batch->accounting_entity_id)->where('code', 'OPB')->where('status', 'active')->first();
        if (! $type) {
            throw new FinancialDomainException('E-OPENING-TYPE', 'An active OPB transaction type is required before posting.');
        }
        $fingerprint = hash('sha256', json_encode($batch->lines->sortBy('line_no')->map(fn (OpeningBalanceLine $line) => [
            $line->id, $line->account_id, $line->fund_id, $line->financial_account_id, $line->program_id,
            $line->debit_amount, $line->credit_amount, $line->source_reference, $line->reconciliation_difference,
        ])->values()->all(), JSON_THROW_ON_ERROR));

        return $this->posting->postOpeningBalance($batch->id, $type->id, 'opening-balance:'.$batch->id, $fingerprint, $actorUserId);
    }

    /** @return array{batch:array<string,mixed>, lines:array<int,array<string,mixed>>, by_account:array<int,array<string,mixed>>, by_financial_account:array<int,array<string,mixed>>, by_fund:array<int,array<string,mixed>>, totals:array{debit:string,credit:string,source_debit:string,source_credit:string,difference:string}} */
    public function summary(OpeningBalanceBatch|string $batch): array
    {
        $batch = is_string($batch) ? OpeningBalanceBatch::query()->with('lines')->findOrFail($batch) : $batch->loadMissing('lines');
        $lines = $batch->lines->sortBy('line_no')->map(fn (OpeningBalanceLine $line) => $this->lineSummary($line))->values();

        return [
            'batch' => $this->batchSummary($batch),
            'lines' => $lines->all(),
            'by_account' => $this->groupSummary($lines, 'account_id'),
            'by_financial_account' => $this->groupSummary($lines, 'financial_account_id'),
            'by_fund' => $this->groupSummary($lines, 'fund_id'),
            'totals' => [
                'debit' => DecimalAmount::sum($lines->pluck('debit')),
                'credit' => DecimalAmount::sum($lines->pluck('credit')),
                'source_debit' => DecimalAmount::sum($lines->pluck('source_debit')),
                'source_credit' => DecimalAmount::sum($lines->pluck('source_credit')),
                'difference' => DecimalAmount::sum($lines->pluck('difference')),
            ],
        ];
    }

    private function transitionMappingSet(string $mappingSetId, string $from, string $to, string $eventType, ?int $actorUserId): MappingSet
    {
        return DB::transaction(function () use ($mappingSetId, $from, $to, $eventType, $actorUserId): MappingSet {
            $set = MappingSet::query()->lockForUpdate()->findOrFail($mappingSetId);
            if ($set->mapping_status !== $from) {
                throw new FinancialDomainException('E-OPENING-MAPPING-STATE', "Mapping set must be {$from} before it can be {$to}.");
            }
            $before = $this->mappingSummary($set);
            $set->update(['mapping_status' => $to, 'updated_by_user_id' => $actorUserId]);
            $this->audit($set->accounting_entity_id, $eventType, 'mapping_set', $set->id, $actorUserId, $before, $this->mappingSummary($set->fresh()));

            return $set->fresh();
        }, 3);
    }

    private function assertDimensionTarget(string $entityId, string $dimension, string $targetId): void
    {
        $model = match ($dimension) {
            'account' => Account::class,
            'financial_account' => FinancialAccount::class,
            'fund' => Fund::class,
            'program' => Program::class,
        };
        if (! $model::query()->where('accounting_entity_id', $entityId)->whereKey($targetId)->exists()) {
            throw new FinancialDomainException('E-OPENING-DIMENSION', "The {$dimension} target does not belong to the opening-balance entity.");
        }
    }

    private function assertMappedDimension(OpeningBalanceBatch $batch, string $sourceReference, string $dimension, string $targetId): void
    {
        $mapped = LegacyMapping::query()
            ->where('mapping_set_id', $batch->mapping_set_id)
            ->where('legacy_record_ref', $this->mappingRecordReference($sourceReference, $dimension))
            ->where('target_entity_type', $dimension)
            ->where('target_entity_id', $targetId)
            ->whereIn('mapping_status', ['confirmed', 'frozen'])
            ->exists();
        if (! $mapped) {
            throw new FinancialDomainException('E-OPENING-MAPPING-UNRESOLVED', "The {$dimension} dimension is not explicitly mapped for this approved source position.");
        }
    }

    private function assertBatchBalancedAndReconciled(OpeningBalanceBatch $batch): void
    {
        if ($batch->lines->isEmpty()) {
            throw new FinancialDomainException('E-OPENING-LINES', 'Opening balance requires at least one source line.');
        }
        if (! DecimalAmount::equals(DecimalAmount::sum($batch->lines->pluck('debit_amount')), DecimalAmount::sum($batch->lines->pluck('credit_amount')))) {
            throw new FinancialDomainException('E-JOURNAL-UNBALANCED', 'The opening position must balance before review or approval.');
        }
        if ($batch->lines->contains(fn (OpeningBalanceLine $line) => $line->reconciliation_status !== 'reconciled' || ! DecimalAmount::equals($line->reconciliation_difference, '0.00'))) {
            throw new FinancialDomainException('E-OPENING-RECONCILIATION', 'Every opening-balance line must reconcile exactly to its approved source position.');
        }
    }

    private function assertRequiredEvidence(OpeningBalanceBatch $batch): void
    {
        foreach ($batch->lines as $line) {
            if (blank($line->source_reference) || blank($line->evidence_ref) || blank($line->mapping_ref) || ! AttachmentLink::query()->where('accounting_entity_id', $batch->accounting_entity_id)->where('target_type', 'opening_balance_line')->where('target_id', $line->id)->where('status', 'active')->exists()) {
                throw new FinancialDomainException('E-OPENING-EVIDENCE', 'Every opening-balance line requires active source evidence, a source reference, and mapping traceability.');
            }
        }
    }

    private function assertOneSidedAmount(string $debit, string $credit, string $label): void
    {
        if ((DecimalAmount::compare($debit, '0.00') > 0) === (DecimalAmount::compare($credit, '0.00') > 0)) {
            throw new FinancialDomainException('E-OPENING-LINE-AMOUNT', "{$label} must contain exactly one positive side.");
        }
    }

    private function assertPositionDateInPeriod(AccountingPeriod $period, string $date): void
    {
        if ($date < $period->start_date->toDateString() || $date > $period->end_date->toDateString()) {
            throw new FinancialDomainException('E-OPENING-DATE', 'The rehearsal position date must be inside its Accounting Period.');
        }
    }

    private function activeEntity(string $entityId): AccountingEntity
    {
        $entity = AccountingEntity::query()->where('status', 'active')->find($entityId);
        if (! $entity) {
            throw new FinancialDomainException('E-OPENING-ENTITY', 'An active Financial V2 accounting entity is required.');
        }

        return $entity;
    }

    private function mappingRecordReference(string $sourceReference, string $dimension): string
    {
        return trim($sourceReference).'|'.$dimension;
    }

    /** @param Collection<int,array<string,mixed>> $lines @return array<int,array<string,mixed>> */
    private function groupSummary(Collection $lines, string $key): array
    {
        return $lines->groupBy(fn (array $line) => $line[$key] ?? 'unattributed')->map(function (Collection $group, string $dimensionId) use ($key): array {
            return [
                $key => $dimensionId === 'unattributed' ? null : $dimensionId,
                'debit' => DecimalAmount::sum($group->pluck('debit')),
                'credit' => DecimalAmount::sum($group->pluck('credit')),
                'source_debit' => DecimalAmount::sum($group->pluck('source_debit')),
                'source_credit' => DecimalAmount::sum($group->pluck('source_credit')),
                'difference' => DecimalAmount::sum($group->pluck('difference')),
            ];
        })->values()->all();
    }

    /** @return array<string,mixed> */
    private function lineSummary(OpeningBalanceLine $line): array
    {
        return [
            'id' => $line->id,
            'line_no' => $line->line_no,
            'account_id' => $line->account_id,
            'financial_account_id' => $line->financial_account_id,
            'fund_id' => $line->fund_id,
            'program_id' => $line->program_id,
            'debit' => DecimalAmount::normalize($line->debit_amount),
            'credit' => DecimalAmount::normalize($line->credit_amount),
            'source_debit' => DecimalAmount::normalize($line->source_debit_amount),
            'source_credit' => DecimalAmount::normalize($line->source_credit_amount),
            'difference' => DecimalAmount::normalize($line->reconciliation_difference),
            'reconciliation_status' => $line->reconciliation_status,
            'source_reference' => $line->source_reference,
            'evidence_ref' => $line->evidence_ref,
            'mapping_ref' => $line->mapping_ref,
            'description' => $line->line_description,
        ];
    }

    /** @return array<string,mixed> */
    private function batchSummary(OpeningBalanceBatch $batch): array
    {
        return ['status' => $batch->status, 'period_id' => $batch->accounting_period_id, 'position_date' => $batch->cutover_date?->toDateString(), 'rehearsal_reference' => $batch->cutover_reference, 'evidence_package_ref' => $batch->evidence_package_ref, 'journal_id' => $batch->journal_id];
    }

    /** @return array<string,mixed> */
    private function mappingSummary(MappingSet $set): array
    {
        return ['code' => $set->code, 'source_system_name' => $set->source_system_name, 'mapping_status' => $set->mapping_status];
    }

    /** @param array<string,mixed>|null $before @param array<string,mixed>|null $after */
    private function audit(string $entityId, string $eventType, string $targetType, string $targetId, ?int $actorUserId, ?array $before, ?array $after): void
    {
        $this->auditTrail->record($entityId, $eventType, $targetType, $targetId, (string) Str::uuid(), $actorUserId, $before, $after);
    }
}
