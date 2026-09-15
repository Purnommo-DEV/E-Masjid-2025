<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/** Configuration-only repair for the evidence-backed July 2026 DHUAFA receipt window. */
final class ConfigureMrjHistoricalDhuafaReceiptService
{
    public const ORIGIN_ADMIN = 'ADMIN_CONFIGURATION_PROVISION';

    public const ORIGIN_ARTISAN = 'ARTISAN_CONFIGURATION_PROVISION';

    public const ORIGIN_SEEDER = 'SEEDER_CONFIGURATION_PROVISION';

    public const ENTITY_CODE = 'MRJ-ACTUAL';

    public const EFFECTIVE_FROM = '2026-07-11';

    public const EFFECTIVE_TO = '2026-08-14';

    private const HISTORICAL_POLICY_REF = 'HISTORICAL-RCV-DHUAFA|2026-07-11-2026-07-30';

    private const BRIDGE_POLICY_REF = 'OPENING-BALANCE-ONLY|2026-07-31-2026-08-14';

    private const OBSOLETE_POLICY_REF = 'JULI-HISTORI_TRANSAKSI_1789270210045';

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
        private readonly FundPolicyVersionDeletionService $policyDeletion,
    ) {}

    /** @return array<string,mixed> */
    public function configure(?int $actorUserId = null, string $origin = self::ORIGIN_SEEDER): array
    {
        $factsBefore = $this->factCounts();
        $changed = false;

        DB::transaction(function () use ($actorUserId, $origin, $factsBefore, &$changed): void {
            $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->firstOrFail();
            $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->where('status', 'active')->firstOrFail();
            $financialAccount = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->where('status', 'active')->firstOrFail();
            $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->where('status', 'active')->firstOrFail();
            $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-DONASI')->where('status', 'active')->firstOrFail();
            $liquidity = Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'LIQ-ZIS')->where('status', 'active')->firstOrFail();
            $currentRevenue = Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'REV-MRJ')->where('status', 'active')->firstOrFail();
            $rule = PostingRule::query()->where('accounting_entity_id', $entity->id)->where('code', 'MRJ-RCV-STANDARD')->where('status', 'active')->firstOrFail();

            foreach ([[$type, 'valid_from'], [$category, 'valid_from'], [$rule, 'valid_from']] as [$master, $field]) {
                $before = $master->{$field} instanceof \DateTimeInterface ? $master->{$field}->format('Y-m-d') : $master->{$field};
                if (! $before || $before > self::EFFECTIVE_FROM) {
                    $master->update([$field => self::EFFECTIVE_FROM, 'updated_by_user_id' => $actorUserId]);
                    $this->auditTrail->record($entity->id, 'historical_master_validity_extended', class_basename($master), $master->id, (string) Str::uuid(), $actorUserId, [$field => $before], [$field => self::EFFECTIVE_FROM]);
                    $changed = true;
                }
            }

            $historicalRevenue = Account::query()->firstOrCreate(
                ['accounting_entity_id' => $entity->id, 'code' => 'REV-DHUAFA-DONASI'],
                [
                    'account_group_id' => $currentRevenue->account_group_id,
                    'name' => 'Pendapatan Donasi Dhuafa & Anak Yatim',
                    'account_class' => 'revenue',
                    'normal_balance' => 'credit',
                    'is_posting_account' => true,
                    'is_liquidity_account' => false,
                    'is_control_account' => false,
                    'allow_manual_posting' => false,
                    'status' => 'active',
                    'valid_from' => self::EFFECTIVE_FROM,
                    'valid_to' => self::EFFECTIVE_TO,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ],
            );
            $changed = $historicalRevenue->wasRecentlyCreated || $changed;

            $historicalVersion = PostingRuleVersion::query()
                ->where('posting_rule_id', $rule->id)
                ->where('journal_template_ref', 'MRJ-RCV-DHUAFA-HISTORICAL-2026-07')
                ->first();
            if (! $historicalVersion) {
                $historicalVersion = PostingRuleVersion::query()->create([
                    'accounting_entity_id' => $entity->id,
                    'posting_rule_id' => $rule->id,
                    'version_no' => ((int) PostingRuleVersion::query()->where('posting_rule_id', $rule->id)->max('version_no')) + 1,
                    'effective_from' => self::EFFECTIVE_FROM,
                    'effective_to' => self::EFFECTIVE_TO,
                    'input_contract_ref' => 'HISTORICAL-RCV-DHUAFA-2026-07',
                    'journal_template_ref' => 'MRJ-RCV-DHUAFA-HISTORICAL-2026-07',
                    'business_rule_refs' => 'JULI-HISTORI_TRANSAKSI_1789270210045.pdf; RCV-DONASI; no Program',
                    'status' => 'superseded',
                    'approved_at' => now(),
                    'approved_by_user_id' => $actorUserId,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                $changed = true;
            }
            $this->assertHistoricalVersion($historicalVersion, $rule);

            foreach ([[$liquidity, 'debit', 'transaction_primary'], [$historicalRevenue, 'credit', 'none']] as $index => [$account, $side, $financialSource]) {
                $line = PostingRuleLine::query()->firstOrCreate(
                    ['posting_rule_version_id' => $historicalVersion->id, 'line_no' => $index + 1],
                    [
                        'accounting_entity_id' => $entity->id,
                        'account_id' => $account->id,
                        'entry_side' => $side,
                        'amount_source' => 'split_amount',
                        'financial_account_source' => $financialSource,
                        'fund_source' => 'split',
                        'program_source' => 'split',
                        'cost_center_source' => 'none',
                        'counterparty_source' => 'none',
                        'category_source' => 'split',
                        'created_by_user_id' => $actorUserId,
                        'updated_by_user_id' => $actorUserId,
                    ],
                );
                if ($line->account_id !== $account->id || $line->entry_side !== $side || $line->financial_account_source !== $financialSource) {
                    throw new RuntimeException('Baris Posting Rule historis RCV DHUAFA tidak sesuai.');
                }
                $changed = $line->wasRecentlyCreated || $changed;
            }
            $evidence = EvidenceRequirement::query()->firstOrCreate(
                ['posting_rule_version_id' => $historicalVersion->id, 'evidence_type' => 'statement'],
                ['accounting_entity_id' => $entity->id, 'minimum_count' => 1],
            );
            $changed = $evidence->wasRecentlyCreated || $changed;

            $openingPolicy = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('version_no', 1)->firstOrFail();
            if ($openingPolicy->effective_to?->toDateString() !== '2026-07-10') {
                $before = $openingPolicy->effective_to?->toDateString();
                $openingPolicy->update(['effective_to' => '2026-07-10', 'updated_by_user_id' => $actorUserId]);
                $this->auditTrail->record($entity->id, 'fund_policy_overlap_corrected', 'fund_policy_version', $openingPolicy->id, (string) Str::uuid(), $actorUserId, ['effective_to' => $before], ['effective_to' => '2026-07-10']);
                $changed = true;
            }

            $obsoletePolicies = FundPolicyVersion::query()
                ->where('fund_id', $fund->id)
                ->whereKeyNot($openingPolicy->id)
                ->whereDate('effective_from', '2026-07-10')
                ->whereDate('effective_to', '2026-07-10')
                ->where('policy_document_ref', self::OBSOLETE_POLICY_REF)
                ->lockForUpdate()
                ->get();
            if ($obsoletePolicies->count() > 1) {
                throw new RuntimeException('Terdapat lebih dari satu kandidat Fund Policy DHUAFA lama untuk 10/07/2026.');
            }
            if ($obsolete = $obsoletePolicies->first()) {
                $usage = $this->policyDeletion->usage($obsolete);
                if (! $usage['can_delete']) {
                    throw new RuntimeException('Fund Policy DHUAFA lama 10/07/2026 sudah digunakan atau masih diperlukan; cleanup otomatis dibatalkan.');
                }
                $this->policyDeletion->delete($entity->id, $obsolete->id, $actorUserId, $origin);
                $changed = true;
            }

            $julyPolicies = FundPolicyVersion::query()
                ->where('fund_id', $fund->id)
                ->whereDate('effective_from', '2026-07-11')
                ->whereDate('effective_to', '2026-07-30')
                ->lockForUpdate()
                ->get();
            if ($julyPolicies->count() > 1) {
                throw new RuntimeException('Terdapat lebih dari satu Fund Policy DHUAFA untuk 11/07/2026–30/07/2026.');
            }
            $julyPolicy = $julyPolicies->first();
            if (! $julyPolicy) {
                $julyPolicy = FundPolicyVersion::query()->create([
                    'accounting_entity_id' => $entity->id,
                    'fund_id' => $fund->id,
                    'version_no' => ((int) FundPolicyVersion::query()->where('fund_id', $fund->id)->max('version_no')) + 1,
                    'effective_from' => '2026-07-11',
                    'effective_to' => '2026-07-30',
                    'policy_document_ref' => self::HISTORICAL_POLICY_REF,
                    'allowed_matrix_ref' => 'RCV + RCV-DONASI + wildcard Program',
                    'exception_approval_level' => 'financial-governance',
                    'status' => 'superseded',
                    'approved_at' => now(),
                    'approved_by_user_id' => $actorUserId,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                $changed = true;
            }
            if (! in_array($julyPolicy->status, ['effective', 'superseded'], true) || ! $julyPolicy->approved_at) {
                throw new RuntimeException('Fund Policy historis DHUAFA belum disetujui atau tidak dapat digunakan.');
            }
            $allowed = FundPolicyRule::query()
                ->where('fund_policy_version_id', $julyPolicy->id)
                ->where('transaction_type_id', $type->id)
                ->whereNull('account_id')
                ->where('category_id', $category->id)
                ->whereNull('program_id')
                ->whereNull('cost_center_id')
                ->first();
            if ($allowed && $allowed->decision !== 'allowed') {
                throw new RuntimeException('Fund Policy historis DHUAFA melarang RCV-DONASI wildcard Program.');
            }
            if (! $allowed) {
                FundPolicyRule::query()->create([
                    'accounting_entity_id' => $entity->id,
                    'fund_policy_version_id' => $julyPolicy->id,
                    'transaction_type_id' => $type->id,
                    'category_id' => $category->id,
                    'decision' => 'allowed',
                    'rationale' => 'Penerimaan donasi DHUAFA historis tanpa Program pada 11/07/2026–30/07/2026.',
                ]);
                $changed = true;
            }

            $bridge = FundPolicyVersion::query()->where('fund_id', $fund->id)->whereIn('policy_document_ref', [self::BRIDGE_POLICY_REF, 'OPENING-BALANCE-ONLY|2026-07-31—2026-08-14'])->first();
            if (! $bridge) {
                $bridge = FundPolicyVersion::query()->create([
                    'accounting_entity_id' => $entity->id,
                    'fund_id' => $fund->id,
                    'version_no' => ((int) FundPolicyVersion::query()->where('fund_id', $fund->id)->max('version_no')) + 1,
                    'effective_from' => '2026-07-31',
                    'effective_to' => self::EFFECTIVE_TO,
                    'policy_document_ref' => self::BRIDGE_POLICY_REF,
                    'allowed_matrix_ref' => 'OPENING-BALANCE-ONLY; operational transactions remain fail-closed.',
                    'exception_approval_level' => 'financial-governance',
                    'status' => 'superseded',
                    'approved_at' => now(),
                    'approved_by_user_id' => $actorUserId,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ]);
                $changed = true;
            }
            if ($bridge->effective_from?->toDateString() !== '2026-07-31' || $bridge->effective_to?->toDateString() !== self::EFFECTIVE_TO || $bridge->status !== 'superseded' || ! $bridge->approved_at) {
                throw new RuntimeException('Fund Policy bridge DHUAFA 31/07/2026–14/08/2026 tidak valid.');
            }
            $openingRule = FundPolicyRule::query()->where('fund_policy_version_id', $openingPolicy->id)->firstOrFail();
            $bridgeRule = FundPolicyRule::query()
                ->where('fund_policy_version_id', $bridge->id)
                ->where('transaction_type_id', $openingRule->transaction_type_id)
                ->where('decision', $openingRule->decision)
                ->first();
            if (! $bridgeRule) {
                FundPolicyRule::query()->create([
                    'accounting_entity_id' => $entity->id,
                    'fund_policy_version_id' => $bridge->id,
                    'transaction_type_id' => $openingRule->transaction_type_id,
                    'account_id' => $openingRule->account_id,
                    'category_id' => $openingRule->category_id,
                    'program_id' => $openingRule->program_id,
                    'cost_center_id' => $openingRule->cost_center_id,
                    'decision' => $openingRule->decision,
                    'rationale' => 'Melanjutkan policy opening-only setelah periode RCV historis Juli berakhir.',
                ]);
                $changed = true;
            }

            if ($this->factCounts() !== $factsBefore) {
                throw new RuntimeException('Konfigurasi mencoba mengubah financial fact dan dibatalkan.');
            }
            $resolved = $this->resolve($entity, $type, $financialAccount, $fund, $category, '2026-07-11');
            if ($resolved['status'] !== 'READY' || $resolved['fund_policy_version'] !== $julyPolicy->version_no) {
                throw new RuntimeException('Resolver belum memilih Fund Policy historis DHUAFA pada 11/07/2026.');
            }
            if ($this->policyOverlaps($fund)->isNotEmpty()) {
                throw new RuntimeException('Masih terdapat Fund Policy DHUAFA yang overlap; provisioning dibatalkan.');
            }

            if ($changed) {
                $this->auditTrail->record($entity->id, 'historical_dhuafa_configuration_provisioned', 'accounting_entity', $entity->id, (string) Str::uuid(), $actorUserId, ['facts' => $factsBefore], ['origin' => $origin, 'posting_rule_version' => $historicalVersion->version_no, 'fund_policy_version' => $julyPolicy->version_no]);
            }
        }, 3);

        return ['changed' => $changed, 'facts_before' => $factsBefore, 'facts_after' => $this->factCounts()] + $this->status();
    }

    /** @return array<string,mixed> */
    public function status(): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->first();
        if (! $entity) {
            return ['ready' => false, 'dates' => []];
        }
        $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->first();
        $account = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->first();
        $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'DHUAFA')->first();
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-DONASI')->first();
        $overlaps = $fund ? $this->policyOverlaps($fund)->all() : [];
        $dates = collect(['2026-07-11', '2026-07-15', '2026-07-30', '2026-07-31', '2026-08-14', '2026-08-15'])
            ->mapWithKeys(fn (string $date): array => [$date => $this->resolve($entity, $type, $account, $fund, $category, $date)])
            ->all();

        return [
            'ready' => ($dates['2026-07-11']['status'] ?? null) === 'READY' && $overlaps === [],
            'policy_overlaps' => $overlaps,
            'entity_id' => $entity->id,
            'configuration' => [
                'entity' => self::ENTITY_CODE,
                'financial_account' => 'BNI-ZISWAF',
                'fund' => 'DHUAFA',
                'transaction_type' => 'RCV',
                'category' => 'RCV-DONASI',
                'program' => null,
                'date' => self::EFFECTIVE_FROM,
            ],
            'dates' => $dates,
        ];
    }

    /** @return array<string,mixed> */
    private function resolve($entity, $type, $account, $fund, $category, string $date): array
    {
        try {
            $resolved = $this->resolver->resolve([
                'accounting_entity_id' => $entity?->id,
                'transaction_type_id' => $type?->id,
                'date' => $date,
                'financial_account_id' => $account?->id,
                'fund_id' => $fund?->id,
                'category_id' => $category?->id,
                'program_id' => null,
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

    private function assertHistoricalVersion(PostingRuleVersion $version, PostingRule $rule): void
    {
        if ($version->posting_rule_id !== $rule->id || $version->effective_from?->toDateString() !== self::EFFECTIVE_FROM || $version->effective_to?->toDateString() !== self::EFFECTIVE_TO || $version->status !== 'superseded' || ! $version->approved_at) {
            throw new RuntimeException('Posting Rule Version historis RCV DHUAFA tidak valid.');
        }
    }

    /** @return \Illuminate\Support\Collection<int,array{left:int,right:int}> */
    private function policyOverlaps(Fund $fund): \Illuminate\Support\Collection
    {
        $versions = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where(fn ($query) => $query->where('status', 'effective')->orWhere(fn ($historical) => $historical->where('status', 'superseded')->whereNotNull('approved_at')))
            ->orderBy('effective_from')
            ->orderBy('version_no')
            ->get();

        return $versions->crossJoin($versions)
            ->filter(fn (array $pair): bool => $pair[0]->version_no < $pair[1]->version_no
                && $pair[0]->effective_from->lte($pair[1]->effective_to ?? '9999-12-31')
                && $pair[1]->effective_from->lte($pair[0]->effective_to ?? '9999-12-31'))
            ->map(fn (array $pair): array => ['left' => $pair[0]->version_no, 'right' => $pair[1]->version_no])
            ->values();
    }

    /** @return array<string,int> */
    private function factCounts(): array
    {
        return collect(self::FACT_TABLES)->mapWithKeys(fn (string $table, string $name): array => [$name => DB::table($table)->count()])->all();
    }
}
