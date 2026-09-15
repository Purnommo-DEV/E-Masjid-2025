<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Corrects one cutover-derived Program lifecycle value without touching facts or policy. */
final class CorrectMrjLegacyProgramLifecycleService
{
    public const ORIGIN_ADMIN = 'ADMIN_CONFIGURATION_PROVISION';

    public const ORIGIN_SEEDER = 'SEEDER_CONFIGURATION_PROVISION';

    public const ENTITY_CODE = 'MRJ-ACTUAL';

    public const PROGRAM_CODE = 'SANTUNAN-YATIM-BULANAN';

    public const TRANSACTION_DATE = '2026-07-11';

    private const ERRONEOUS_CUTOVER_DATE = '2026-08-15';

    private const PROVISIONING_REFERENCE = 'Konfigurasi operasional MRJ Phase 12';

    private const FACT_TABLES = [
        'transactions' => 'financial_v2_transactions',
        'journals' => 'financial_v2_journals',
        'journal_lines' => 'financial_v2_journal_lines',
        'ledger_entries' => 'financial_v2_ledger_entries',
        'vouchers' => 'financial_v2_vouchers',
        'allocations' => 'financial_v2_budget_allocations',
    ];

    public function __construct(
        private readonly FinancialTransactionConfigurationResolver $resolver,
        private readonly AuditTrailService $auditTrail,
    ) {}

    /** @return array<string, mixed> */
    public function correct(?int $actorUserId = null, string $origin = self::ORIGIN_SEEDER): array
    {
        $factsBefore = $this->factCounts();
        $changed = false;

        DB::transaction(function () use ($actorUserId, $origin, $factsBefore, &$changed): void {
            $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->firstOrFail();
            $program = Program::query()
                ->where('accounting_entity_id', $entity->id)
                ->where('code', self::PROGRAM_CODE)
                ->lockForUpdate()
                ->firstOrFail();

            if ($program->start_date === null) {
                return;
            }
            if ($program->start_date->toDateString() !== self::ERRONEOUS_CUTOVER_DATE
                || $program->end_date !== null
                || $program->program_owner_reference !== self::PROVISIONING_REFERENCE) {
                throw new RuntimeException('Lifecycle Program memiliki nilai bisnis lain; koreksi otomatis dibatalkan.');
            }

            $program->update(['start_date' => null, 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record(
                $entity->id,
                'legacy_program_lifecycle_corrected',
                'program',
                $program->id,
                (string) Str::uuid(),
                $actorUserId,
                ['start_date' => self::ERRONEOUS_CUTOVER_DATE, 'meaning' => 'Financial V2 cutover incorrectly stored as business lifecycle'],
                ['start_date' => null, 'meaning' => 'Legacy business start unknown/unbounded', 'origin' => $origin],
            );
            $changed = true;

            if ($this->factCounts() !== $factsBefore) {
                throw new RuntimeException('Koreksi lifecycle mencoba mengubah financial fact dan dibatalkan.');
            }
        }, 3);

        return ['changed' => $changed, 'facts_before' => $factsBefore, 'facts_after' => $this->factCounts()] + $this->status();
    }

    /** @return array<string, mixed> */
    public function status(): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->first();
        $program = $entity ? Program::query()->where('accounting_entity_id', $entity->id)->where('code', self::PROGRAM_CODE)->first() : null;

        return [
            'ready' => $program?->isBusinessActiveOn(self::TRANSACTION_DATE) === true,
            'conflict' => $program !== null && $program->start_date !== null && (
                $program->start_date->toDateString() !== self::ERRONEOUS_CUTOVER_DATE
                || $program->end_date !== null
                || $program->program_owner_reference !== self::PROVISIONING_REFERENCE
            ),
            'program_start_date' => $program?->start_date?->toDateString(),
            'program_business_active' => $program?->isBusinessActiveOn(self::TRANSACTION_DATE) === true,
            'resolver' => $this->resolverStatus($entity, $program),
        ];
    }

    /** @return array<string, mixed> */
    private function resolverStatus(?AccountingEntity $entity, ?Program $program): array
    {
        if (! $entity || ! $program) {
            return ['status' => 'MISSING', 'message' => 'Entity atau Program belum tersedia.'];
        }

        try {
            $resolved = $this->resolver->resolve([
                'accounting_entity_id' => $entity->id,
                'transaction_type_id' => TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->value('id'),
                'date' => self::TRANSACTION_DATE,
                'financial_account_id' => FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->value('id'),
                'fund_id' => Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->value('id'),
                'category_id' => Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-DONASI')->value('id'),
                'program_id' => $program->id,
            ]);

            return [
                'status' => 'READY',
                'posting_rule_version' => $resolved->postingRuleVersion->version_no,
                'fund_policy_version' => $resolved->fundPolicies->first()?->version_no,
            ];
        } catch (Throwable $exception) {
            return ['status' => 'MISSING', 'message' => $exception->getMessage()];
        }
    }

    /** @return array<string, int> */
    private function factCounts(): array
    {
        return collect(self::FACT_TABLES)->mapWithKeys(fn (string $table, string $name): array => [$name => DB::table($table)->count()])->all();
    }
}
