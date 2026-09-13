<?php

namespace App\Console\Commands;

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
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ConfigureMrjBankMutationsCommand extends Command
{
    private const EFFECTIVE_FROM = '2026-06-30';

    private const FACT_TABLES = [
        'transactions' => 'financial_v2_transactions',
        'journals' => 'financial_v2_journals',
        'journal_lines' => 'financial_v2_journal_lines',
        'ledger_entries' => 'financial_v2_ledger_entries',
        'vouchers' => 'financial_v2_vouchers',
    ];

    protected $signature = 'financial-v2:configure-mrj-bank-mutations
        {--entity=MRJ-ACTUAL : Accounting Entity code}
        {--actor= : User id recorded as approver/author}
        {--apply : Persist master configuration; without this flag the command is read-only}';

    protected $description = 'Provision governed RCV/PAY bank-mutation categories and historical policy without posting transactions.';

    public function handle(): int
    {
        $entity = AccountingEntity::query()->where('code', $this->option('entity'))->first();
        if (! $entity) {
            $this->error('Accounting Entity tidak ditemukan.');

            return self::FAILURE;
        }
        $required = [
            'BNI-ZISWAF' => FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->first(),
            'INFAQ-TROMOL' => Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->first(),
            'LIQ-ZIS' => Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'LIQ-ZIS')->first(),
            'REV-MRJ' => Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'REV-MRJ')->first(),
            'EXP-MRJ' => Account::query()->where('accounting_entity_id', $entity->id)->where('code', 'EXP-MRJ')->first(),
            'RCV' => TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV')->first(),
            'PAY' => TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->first(),
        ];
        $missing = collect($required)->filter(fn ($value) => ! $value)->keys();
        if ($missing->isNotEmpty()) {
            $this->error('Prerequisite master belum tersedia: '.$missing->join(', '));

            return self::FAILURE;
        }
        $this->table(['Scope', 'Value'], [
            ['Entity', $entity->code],
            ['Rekening', 'BNI-ZISWAF'],
            ['Dana', 'INFAQ-TROMOL'],
            ['Effective from', self::EFFECTIVE_FROM],
            ['Categories', 'BANK_INTEREST, BANK_WHT_PPH, BANK_ACCOUNT_FEE, BANK_CARD_FEE, BANK_TRANSFER_FEE'],
            ['Evidence', 'statement (minimum 1)'],
            ['Approval', 'Maker + 1 checker/approver decision'],
            ['Financial transactions posted', '0'],
        ]);
        if (! $this->option('apply')) {
            $this->warn('Dry-run selesai. Tambahkan --apply untuk menyimpan master/config saja.');

            return self::SUCCESS;
        }

        $actor = $this->option('actor') !== null ? (int) $this->option('actor') : null;
        $factsBefore = $this->factCounts();
        DB::transaction(function () use ($entity, $required, $actor, $factsBefore): void {
            $bank = Counterparty::query()->firstOrCreate(
                ['accounting_entity_id' => $entity->id, 'code' => 'BANK-BNI'],
                ['party_type' => 'bank', 'display_name' => 'Bank BNI', 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
            );
            if ($bank->party_type !== 'bank' || $bank->status !== 'active') {
                throw new \RuntimeException('Master BANK-BNI tidak valid.');
            }

            $businessAccounts = [
                'REV-BANK-INTEREST' => [$required['REV-MRJ'], 'Pendapatan Jasa Giro/Bunga Bank', 'revenue', 'credit'],
                'EXP-BANK-WHT' => [$required['EXP-MRJ'], 'Beban Pajak Bunga Bank', 'expense', 'debit'],
                'EXP-BANK-FEE' => [$required['EXP-MRJ'], 'Beban Administrasi Bank', 'expense', 'debit'],
            ];
            foreach ($businessAccounts as $code => [$template, $name, $class, $normal]) {
                $businessAccounts[$code] = Account::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'code' => $code],
                    ['account_group_id' => $template->account_group_id, 'name' => $name, 'account_class' => $class, 'normal_balance' => $normal, 'is_posting_account' => true, 'is_liquidity_account' => false, 'is_control_account' => false, 'allow_manual_posting' => false, 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                );
            }

            $definitions = [
                'BANK_INTEREST' => ['Jasa Giro/Bunga', $required['RCV'], 'MRJ-RCV-BANK-INTEREST', 'bank-interest', $businessAccounts['REV-BANK-INTEREST']],
                'BANK_WHT_PPH' => ['PPH Bunga Bank', $required['PAY'], 'MRJ-PAY-BANK-WHT', 'bank-withholding-tax', $businessAccounts['EXP-BANK-WHT']],
                'BANK_ACCOUNT_FEE' => ['Biaya Administrasi Rekening', $required['PAY'], 'MRJ-PAY-BANK-FEE', 'bank-account-fee', $businessAccounts['EXP-BANK-FEE']],
                'BANK_CARD_FEE' => ['Biaya Administrasi Kartu', $required['PAY'], 'MRJ-PAY-BANK-CARD-FEE', 'bank-card-fee', $businessAccounts['EXP-BANK-FEE']],
                'BANK_TRANSFER_FEE' => ['Biaya Transfer Bank', $required['PAY'], 'MRJ-PAY-BANK-TRANSFER-FEE', 'bank-transfer-fee', $businessAccounts['EXP-BANK-FEE']],
            ];
            foreach ($definitions as $categoryCode => [$categoryName, $type, $ruleCode, $family, $businessAccount]) {
                $rule = PostingRule::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'code' => $ruleCode],
                    ['transaction_type_id' => $type->id, 'name' => $categoryName, 'rule_family' => $family, 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                );
                $version = PostingRuleVersion::query()->firstOrCreate(
                    ['posting_rule_id' => $rule->id, 'version_no' => 1],
                    ['accounting_entity_id' => $entity->id, 'effective_from' => self::EFFECTIVE_FROM, 'input_contract_ref' => 'BANK-MUTATION-INPUT-V1', 'journal_template_ref' => $ruleCode, 'business_rule_refs' => 'Approved bank mutation policy; RCV/PAY only; BNI-ZISWAF; INFAQ-TROMOL', 'status' => 'effective', 'approved_at' => now(), 'approved_by_user_id' => $actor, 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                );
                $category = Category::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'code' => $categoryCode],
                    ['transaction_type_id' => $type->id, 'default_posting_rule_id' => $rule->id, 'name' => $categoryName, 'status' => 'active', 'valid_from' => self::EFFECTIVE_FROM, 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                );
                if ($category->transaction_type_id !== $type->id || $category->default_posting_rule_id !== $rule->id) {
                    throw new \RuntimeException("Category {$categoryCode} sudah ada dengan mapping berbeda.");
                }

                $isReceipt = $type->code === 'RCV';
                $lineDefinitions = $isReceipt
                    ? [[$required['LIQ-ZIS'], 'debit', 'transaction_primary'], [$businessAccount, 'credit', 'none']]
                    : [[$businessAccount, 'debit', 'none'], [$required['LIQ-ZIS'], 'credit', 'transaction_primary']];
                foreach ($lineDefinitions as $index => [$account, $side, $financialSource]) {
                    PostingRuleLine::query()->firstOrCreate(
                        ['posting_rule_version_id' => $version->id, 'line_no' => $index + 1],
                        ['accounting_entity_id' => $entity->id, 'account_id' => $account->id, 'entry_side' => $side, 'amount_source' => 'split_amount', 'financial_account_source' => $financialSource, 'fund_source' => 'split', 'program_source' => 'none', 'cost_center_source' => 'none', 'counterparty_source' => $isReceipt ? 'none' : 'split', 'category_source' => 'split', 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                    );
                }
                EvidenceRequirement::query()->firstOrCreate(
                    ['posting_rule_version_id' => $version->id, 'evidence_type' => 'statement'],
                    ['accounting_entity_id' => $entity->id, 'minimum_count' => 1],
                );
                BankMutationPolicy::query()->firstOrCreate(
                    ['accounting_entity_id' => $entity->id, 'financial_account_id' => $required['BNI-ZISWAF']->id, 'fund_id' => $required['INFAQ-TROMOL']->id, 'category_id' => $category->id, 'effective_from' => self::EFFECTIVE_FROM],
                    ['transaction_type_id' => $type->id, 'posting_rule_version_id' => $version->id, 'policy_document_ref' => 'BNI-ZISWAF-BANK-MUTATION-POLICY-2026-06-30', 'evidence_type' => 'statement', 'required_approval_steps' => 1, 'status' => 'active', 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                );
            }

            if ($this->factCounts() !== $factsBefore) {
                throw new \RuntimeException('Configuration unexpectedly changed official financial fact counts.');
            }
        }, 3);

        $factsAfter = $this->factCounts();

        $this->info('Master/config Mutasi Bank tersimpan. Tidak ada Transaction, Journal, Ledger, atau Voucher yang dibuat.');
        $this->table(
            ['Financial fact', 'Before', 'After'],
            collect(self::FACT_TABLES)->keys()->map(fn (string $name): array => [$name, $factsBefore[$name], $factsAfter[$name]])->all(),
        );

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function factCounts(): array
    {
        return collect(self::FACT_TABLES)
            ->mapWithKeys(fn (string $table, string $name): array => [$name => DB::table($table)->count()])
            ->all();
    }
}
