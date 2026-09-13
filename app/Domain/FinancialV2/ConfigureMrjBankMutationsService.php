<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/** Provisions configuration only; this service never creates financial facts. */
final class ConfigureMrjBankMutationsService
{
    public const ENTITY_CODE = 'MRJ-ACTUAL';

    public const FINANCIAL_ACCOUNT_CODE = 'BNI-ZISWAF';

    public const FUND_CODE = 'INFAQ-TROMOL';

    public const EFFECTIVE_FROM = '2026-06-30';

    public const CATEGORY_CODES = [
        'BANK_INTEREST',
        'BANK_WHT_PPH',
        'BANK_ACCOUNT_FEE',
        'BANK_CARD_FEE',
        'BANK_TRANSFER_FEE',
    ];

    private const FACT_TABLES = [
        'transactions' => 'financial_v2_transactions',
        'journals' => 'financial_v2_journals',
        'journal_lines' => 'financial_v2_journal_lines',
        'ledger_entries' => 'financial_v2_ledger_entries',
        'vouchers' => 'financial_v2_vouchers',
        'reconciliations' => 'financial_v2_reconciliations',
        'opening_batches' => 'financial_v2_opening_balance_batches',
        'opening_lines' => 'financial_v2_opening_balance_lines',
    ];

    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @return array<string, mixed> */
    public function status(): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->first();
        $account = $entity ? FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', self::FINANCIAL_ACCOUNT_CODE)->first() : null;
        $fund = $entity ? Fund::query()->where('accounting_entity_id', $entity->id)->where('code', self::FUND_CODE)->first() : null;
        $missing = collect();
        if (! $entity || $entity->status !== 'active') {
            $missing->push('Entity MRJ-ACTUAL aktif');
        }
        if (! $account || $account->status !== 'active') {
            $missing->push('Financial Account BNI-ZISWAF aktif');
        }
        if (! $fund || $fund->status !== 'active') {
            $missing->push('Fund INFAQ-TROMOL aktif');
        }

        $policies = collect();
        if ($entity && $account && $fund) {
            $policies = BankMutationPolicy::query()
                ->with(['category', 'transactionType', 'postingRuleVersion.rule', 'postingRuleVersion.lines', 'postingRuleVersion.evidenceRequirements'])
                ->where('accounting_entity_id', $entity->id)
                ->where('financial_account_id', $account->id)
                ->where('fund_id', $fund->id)
                ->whereIn('category_id', Category::query()->where('accounting_entity_id', $entity->id)->whereIn('code', self::CATEGORY_CODES)->select('id'))
                ->get();

            foreach ($this->definitions() as $categoryCode => $definition) {
                $policy = $policies->first(fn (BankMutationPolicy $candidate): bool => $candidate->category?->code === $categoryCode);
                if (! $this->policyIsComplete($policy, $definition['type'])) {
                    $missing->push($categoryCode);
                }
            }
        } else {
            $missing->push(...self::CATEGORY_CODES);
        }

        return [
            'active' => $missing->isEmpty(),
            'entity_id' => $entity?->id,
            'entity_code' => self::ENTITY_CODE,
            'financial_account' => self::FINANCIAL_ACCOUNT_CODE,
            'fund' => self::FUND_CODE,
            'effective_from' => self::EFFECTIVE_FROM,
            'categories' => self::CATEGORY_CODES,
            'evidence' => 'statement (minimum 1)',
            'approval' => 'Maker + 1 checker/approver decision',
            'missing' => $missing->unique()->values()->all(),
            'policy_count' => $policies->count(),
        ];
    }

    /** @return array<string, mixed> */
    public function configure(?int $actorUserId = null): array
    {
        $beforeStatus = $this->status();
        $factsBefore = $this->factCounts();
        if ($beforeStatus['active']) {
            return $beforeStatus + ['changed' => false, 'facts_before' => $factsBefore, 'facts_after' => $factsBefore];
        }

        $changed = false;
        DB::transaction(function () use ($actorUserId, $factsBefore, &$changed): void {
            [$entity, $required] = $this->prerequisites();
            $bank = Counterparty::query()->firstOrCreate(
                ['accounting_entity_id' => $entity->id, 'code' => 'BANK-BNI'],
                ['party_type' => 'bank', 'display_name' => 'Bank BNI', 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
            );
            $changed = $bank->wasRecentlyCreated || $changed;
            if ($bank->party_type !== 'bank') {
                throw new RuntimeException('Master BANK-BNI sudah ada dengan tipe yang tidak sesuai.');
            }
            $changed = $this->activate($bank, $actorUserId) || $changed;

            $businessAccounts = [
                'REV-BANK-INTEREST' => [$required['REV-MRJ'], 'Pendapatan Jasa Giro/Bunga Bank', 'revenue', 'credit'],
                'EXP-BANK-WHT' => [$required['EXP-MRJ'], 'Beban Pajak Bunga Bank', 'expense', 'debit'],
                'EXP-BANK-FEE' => [$required['EXP-MRJ'], 'Beban Administrasi Bank', 'expense', 'debit'],
            ];
            foreach ($businessAccounts as $code => [$template, $name, $class, $normal]) {
                $account = Account::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'code' => $code],
                    ['account_group_id' => $template->account_group_id, 'name' => $name, 'account_class' => $class, 'normal_balance' => $normal, 'is_posting_account' => true, 'is_liquidity_account' => false, 'is_control_account' => false, 'allow_manual_posting' => false, 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
                );
                $changed = $account->wasRecentlyCreated || $changed;
                if ($account->account_class !== $class || $account->normal_balance !== $normal || ! $account->is_posting_account || $account->is_liquidity_account) {
                    throw new RuntimeException("Account {$code} sudah ada dengan konfigurasi yang tidak sesuai.");
                }
                $changed = $this->activate($account, $actorUserId) || $changed;
                $businessAccounts[$code] = $account;
            }

            foreach ($this->definitions() as $categoryCode => $definition) {
                $type = $required[$definition['type']];
                $businessAccount = $businessAccounts[$definition['business_account']];
                $rule = PostingRule::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'code' => $definition['rule_code']],
                    ['transaction_type_id' => $type->id, 'name' => $definition['name'], 'rule_family' => $definition['family'], 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
                );
                $changed = $rule->wasRecentlyCreated || $changed;
                if ($rule->transaction_type_id !== $type->id || $rule->rule_family !== $definition['family']) {
                    throw new RuntimeException("Posting rule {$definition['rule_code']} sudah ada dengan mapping berbeda.");
                }
                $changed = $this->activate($rule, $actorUserId) || $changed;

                $version = PostingRuleVersion::query()->firstOrCreate(
                    ['posting_rule_id' => $rule->id, 'version_no' => 1],
                    ['accounting_entity_id' => $entity->id, 'effective_from' => self::EFFECTIVE_FROM, 'input_contract_ref' => 'BANK-MUTATION-INPUT-V1', 'journal_template_ref' => $definition['rule_code'], 'business_rule_refs' => 'Approved bank mutation policy; RCV/PAY only; BNI-ZISWAF; INFAQ-TROMOL', 'status' => 'effective', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
                );
                $changed = $version->wasRecentlyCreated || $changed;
                if ($version->accounting_entity_id !== $entity->id || $version->effective_from?->toDateString() !== self::EFFECTIVE_FROM) {
                    throw new RuntimeException("Versi posting rule {$definition['rule_code']} tidak valid untuk 30/06/2026.");
                }
                if ($version->status === 'draft') {
                    $version->update(['status' => 'effective', 'approved_at' => now(), 'approved_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId]);
                    $changed = true;
                } elseif (! in_array($version->status, ['effective', 'superseded'], true) || ($version->status === 'superseded' && ! $version->approved_at)) {
                    throw new RuntimeException("Versi posting rule {$definition['rule_code']} tidak dapat diaktifkan secara otomatis.");
                }

                $category = Category::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'code' => $categoryCode],
                    ['transaction_type_id' => $type->id, 'default_posting_rule_id' => $rule->id, 'name' => $definition['name'], 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
                );
                $changed = $category->wasRecentlyCreated || $changed;
                if ($category->transaction_type_id !== $type->id || $category->default_posting_rule_id !== $rule->id) {
                    throw new RuntimeException("Category {$categoryCode} sudah ada dengan mapping berbeda.");
                }
                $changed = $this->activate($category, $actorUserId) || $changed;

                $isReceipt = $type->code === 'RCV';
                $lineDefinitions = $isReceipt
                    ? [[$required['LIQ-ZIS'], 'debit', 'transaction_primary'], [$businessAccount, 'credit', 'none']]
                    : [[$businessAccount, 'debit', 'none'], [$required['LIQ-ZIS'], 'credit', 'transaction_primary']];
                foreach ($lineDefinitions as $index => [$lineAccount, $side, $financialSource]) {
                    $attributes = ['accounting_entity_id' => $entity->id, 'account_id' => $lineAccount->id, 'entry_side' => $side, 'amount_source' => 'split_amount', 'financial_account_source' => $financialSource, 'fund_source' => 'split', 'program_source' => 'none', 'cost_center_source' => 'none', 'counterparty_source' => $isReceipt ? 'none' : 'split', 'category_source' => 'split'];
                    $line = PostingRuleLine::query()->firstOrCreate(
                        ['posting_rule_version_id' => $version->id, 'line_no' => $index + 1],
                        $attributes + ['created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
                    );
                    $changed = $line->wasRecentlyCreated || $changed;
                    foreach ($attributes as $field => $expected) {
                        if ((string) $line->{$field} !== (string) $expected) {
                            throw new RuntimeException("Baris posting rule {$definition['rule_code']} tidak sesuai.");
                        }
                    }
                }

                $evidence = EvidenceRequirement::query()->firstOrCreate(
                    ['posting_rule_version_id' => $version->id, 'evidence_type' => 'statement'],
                    ['accounting_entity_id' => $entity->id, 'minimum_count' => 1],
                );
                $changed = $evidence->wasRecentlyCreated || $changed;
                if ((int) $evidence->minimum_count < 1) {
                    $evidence->update(['minimum_count' => 1]);
                    $changed = true;
                }

                $policy = BankMutationPolicy::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'financial_account_id' => $required[self::FINANCIAL_ACCOUNT_CODE]->id, 'fund_id' => $required[self::FUND_CODE]->id, 'category_id' => $category->id, 'effective_from' => self::EFFECTIVE_FROM],
                    ['transaction_type_id' => $type->id, 'posting_rule_version_id' => $version->id, 'policy_document_ref' => 'BNI-ZISWAF-BANK-MUTATION-POLICY-2026-06-30', 'evidence_type' => 'statement', 'required_approval_steps' => 1, 'status' => 'active', 'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId],
                );
                $changed = $policy->wasRecentlyCreated || $changed;
                if ($policy->transaction_type_id !== $type->id || $policy->posting_rule_version_id !== $version->id || $policy->evidence_type !== 'statement') {
                    throw new RuntimeException("Policy {$categoryCode} sudah ada dengan mapping berbeda.");
                }
                if ($policy->status !== 'active' || (int) $policy->required_approval_steps < 1) {
                    $policy->update(['status' => 'active', 'required_approval_steps' => max(1, (int) $policy->required_approval_steps), 'updated_by_user_id' => $actorUserId]);
                    $changed = true;
                }
            }

            if ($this->factCounts() !== $factsBefore) {
                throw new RuntimeException('Konfigurasi mencoba mengubah financial fact dan dibatalkan.');
            }

            $afterStatus = $this->status();
            if (! $afterStatus['active']) {
                throw new RuntimeException('Konfigurasi Mutasi Bank belum lengkap setelah aktivasi.');
            }
            if ($changed) {
                $this->auditTrail->record(
                    $entity->id,
                    'bank_mutation_configuration_activated',
                    'bank_mutation_configuration',
                    $entity->id,
                    (string) Str::uuid(),
                    $actorUserId,
                    ['active' => false],
                    ['active' => true, 'financial_account' => self::FINANCIAL_ACCOUNT_CODE, 'fund' => self::FUND_CODE, 'categories' => self::CATEGORY_CODES, 'effective_from' => self::EFFECTIVE_FROM],
                );
            }
        }, 3);

        $afterStatus = $this->status();
        $factsAfter = $this->factCounts();

        return $afterStatus + ['changed' => $changed, 'facts_before' => $factsBefore, 'facts_after' => $factsAfter];
    }

    /** @return array{0:AccountingEntity,1:array<string,mixed>} */
    private function prerequisites(): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->first();
        if (! $entity) {
            throw new RuntimeException('Accounting Entity MRJ-ACTUAL aktif tidak ditemukan.');
        }
        $required = [
            self::FINANCIAL_ACCOUNT_CODE => FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', self::FINANCIAL_ACCOUNT_CODE)->where('status', 'active')->first(),
            self::FUND_CODE => Fund::query()->where('accounting_entity_id', $entity->id)->where('code', self::FUND_CODE)->where('status', 'active')->first(),
            'LIQ-ZIS' => Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'LIQ-ZIS')->where('status', 'active')->first(),
            'REV-MRJ' => Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'REV-MRJ')->where('status', 'active')->first(),
            'EXP-MRJ' => Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'EXP-MRJ')->where('status', 'active')->first(),
            'RCV' => TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->where('status', 'active')->first(),
            'PAY' => TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->where('status', 'active')->first(),
        ];
        $missing = collect($required)->filter(fn ($value) => ! $value)->keys();
        if ($missing->isNotEmpty()) {
            throw new RuntimeException('Prerequisite master aktif belum tersedia: '.$missing->join(', '));
        }

        return [$entity, $required];
    }

    /** @return array<string, array{name:string,type:string,rule_code:string,family:string,business_account:string}> */
    private function definitions(): array
    {
        return [
            'BANK_INTEREST' => ['name' => 'Jasa Giro/Bunga', 'type' => 'RCV', 'rule_code' => 'MRJ-RCV-BANK-INTEREST', 'family' => 'bank-interest', 'business_account' => 'REV-BANK-INTEREST'],
            'BANK_WHT_PPH' => ['name' => 'PPH Bunga Bank', 'type' => 'PAY', 'rule_code' => 'MRJ-PAY-BANK-WHT', 'family' => 'bank-withholding-tax', 'business_account' => 'EXP-BANK-WHT'],
            'BANK_ACCOUNT_FEE' => ['name' => 'Biaya Administrasi Rekening', 'type' => 'PAY', 'rule_code' => 'MRJ-PAY-BANK-FEE', 'family' => 'bank-account-fee', 'business_account' => 'EXP-BANK-FEE'],
            'BANK_CARD_FEE' => ['name' => 'Biaya Administrasi Kartu', 'type' => 'PAY', 'rule_code' => 'MRJ-PAY-BANK-CARD-FEE', 'family' => 'bank-card-fee', 'business_account' => 'EXP-BANK-FEE'],
            'BANK_TRANSFER_FEE' => ['name' => 'Biaya Transfer Bank', 'type' => 'PAY', 'rule_code' => 'MRJ-PAY-BANK-TRANSFER-FEE', 'family' => 'bank-transfer-fee', 'business_account' => 'EXP-BANK-FEE'],
        ];
    }

    /** @param array{name:string,type:string,rule_code:string,family:string,business_account:string} $definition */
    private function policyIsComplete(?BankMutationPolicy $policy, string $expectedType): bool
    {
        if (! $policy || $policy->status !== 'active' || $policy->effective_from?->toDateString() > self::EFFECTIVE_FROM || ($policy->effective_to && $policy->effective_to->toDateString() < self::EFFECTIVE_FROM)) {
            return false;
        }
        $version = $policy->postingRuleVersion;
        $versionIsHistorical = $version && ($version->status === 'effective' || ($version->status === 'superseded' && $version->approved_at));

        return $policy->category?->status === 'active'
            && $policy->transactionType?->code === $expectedType
            && $policy->category?->transaction_type_id === $policy->transaction_type_id
            && $policy->category?->default_posting_rule_id === $version?->posting_rule_id
            && $policy->evidence_type === 'statement'
            && (int) $policy->required_approval_steps >= 1
            && $versionIsHistorical
            && $version->effective_from?->toDateString() <= self::EFFECTIVE_FROM
            && (! $version->effective_to || $version->effective_to->toDateString() >= self::EFFECTIVE_FROM)
            && $version->rule?->status === 'active'
            && $version->rule?->transaction_type_id === $policy->transaction_type_id
            && $version->lines->count() === 2
            && $version->evidenceRequirements->contains(fn (EvidenceRequirement $requirement): bool => $requirement->evidence_type === 'statement' && (int) $requirement->minimum_count >= 1);
    }

    private function activate(object $model, ?int $actorUserId): bool
    {
        if ($model->status === 'active') {
            return false;
        }
        $model->update(['status' => 'active', 'updated_by_user_id' => $actorUserId]);

        return true;
    }

    /** @return array<string, int> */
    private function factCounts(): array
    {
        return collect(self::FACT_TABLES)->mapWithKeys(fn (string $table, string $name): array => [$name => DB::table($table)->count()])->all();
    }
}
