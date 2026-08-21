<?php

namespace Tests\Support;

use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Str;

/** Isolated, synthetic master and operational data for Phase 7 UAT only. */
final class UatFinancialFixture
{
    /** @return array<string,mixed> */
    public static function context(): array
    {
        $suffix = Str::upper(Str::random(7));
        $today = now()->toDateString();
        $entity = AccountingEntity::create(['code' => "UAT-{$suffix}", 'name' => 'UAT Financial V2', 'legal_name' => 'UAT Financial V2', 'status' => 'active']);
        $calendar = AccountingCalendar::create(['accounting_entity_id' => $entity->id, 'code' => "CAL-{$suffix}", 'name' => 'UAT Calendar', 'fiscal_year_label' => "2099-{$suffix}", 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => 'active']);
        $period = AccountingPeriod::create(['accounting_entity_id' => $entity->id, 'accounting_calendar_id' => $calendar->id, 'period_no' => 1, 'period_name' => 'UAT Period', 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'status' => 'open']);
        $assetGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'AST', 'name' => 'Assets', 'group_class' => 'asset', 'status' => 'active']);
        $revenueGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'REV', 'name' => 'Revenue', 'group_class' => 'revenue', 'status' => 'active']);
        $expenseGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'EXP', 'name' => 'Expense', 'group_class' => 'expense', 'status' => 'active']);
        $transferGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'TRF', 'name' => 'Transfer', 'group_class' => 'transfer', 'status' => 'active']);
        $cashA = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'KAS-A', 'name' => 'Kas Operasional', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
        $cashB = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'KAS-B', 'name' => 'Kas Bank', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
        $revenue = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $revenueGroup->id, 'code' => 'PNR', 'name' => 'Penerimaan', 'account_class' => 'revenue', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);
        $expense = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $expenseGroup->id, 'code' => 'BOP', 'name' => 'Beban Operasional', 'account_class' => 'expense', 'normal_balance' => 'debit', 'is_posting_account' => true, 'status' => 'active']);
        $transferIn = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $transferGroup->id, 'code' => 'DANA-MSK', 'name' => 'Transfer Dana Masuk', 'account_class' => 'transfer', 'normal_balance' => 'debit', 'is_posting_account' => true, 'status' => 'active']);
        $transferOut = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $transferGroup->id, 'code' => 'DANA-KLR', 'name' => 'Transfer Dana Keluar', 'account_class' => 'transfer', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);
        $accountA = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cashA->id, 'code' => 'KAS-OPS', 'name' => 'Kas Operasional', 'account_type' => 'cash', 'opening_date' => now()->subYear()->toDateString(), 'status' => 'active']);
        $accountB = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cashB->id, 'code' => 'BNK-OPS', 'name' => 'Bank Operasional', 'account_type' => 'bank', 'opening_date' => now()->subYear()->toDateString(), 'status' => 'active']);
        // Cash and bank custody details are required by the Posting Engine.
        \App\Models\FinancialV2\CashAccountDetail::create(['financial_account_id' => $accountA->id, 'cash_location' => 'UAT cash box', 'cash_count_frequency' => 'daily']);
        BankAccountDetail::create(['financial_account_id' => $accountB->id, 'bank_name' => 'Bank UAT', 'account_number_masked' => '****7001']);
        $fundType = FundType::create(['accounting_entity_id' => $entity->id, 'code' => 'UMUM', 'name' => 'Unrestricted', 'classification' => 'unrestricted', 'status' => 'active']);
        $restriction = FundRestriction::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'code' => 'UMUM', 'name' => 'Umum', 'severity' => 'low', 'policy_basis' => 'UAT fixture', 'status' => 'active']);
        $fund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'OPERASIONAL', 'name' => 'Dana Operasional', 'purpose_statement' => 'UAT operations', 'status' => 'active']);
        $destinationFund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'PROGRAM', 'name' => 'Dana Program', 'purpose_statement' => 'UAT program', 'status' => 'active']);
        $program = Program::create(['accounting_entity_id' => $entity->id, 'code' => 'JUMAT', 'name' => 'Program Jumat', 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'status' => 'active']);
        $receiptType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'RCV', 'name' => 'Penerimaan', 'voucher_prefix' => 'RCV', 'status' => 'active']);
        $paymentType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'PAY', 'name' => 'Pengeluaran', 'voucher_prefix' => 'PAY', 'status' => 'active']);
        $treasuryType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'TRF', 'name' => 'Transfer Rekening', 'voucher_prefix' => 'TRF', 'status' => 'active']);
        $interfundType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'IFT', 'name' => 'Transfer Dana', 'voucher_prefix' => 'IFT', 'status' => 'active']);
        $receiptCategory = Category::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $receiptType->id, 'code' => 'JUMAT', 'name' => 'Penerimaan Jumat', 'status' => 'active']);
        $paymentCategory = Category::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $paymentType->id, 'code' => 'OPS', 'name' => 'Biaya Operasional', 'status' => 'active']);
        $supplier = Counterparty::create(['accounting_entity_id' => $entity->id, 'code' => 'SUP-UAT', 'party_type' => 'supplier', 'display_name' => 'Pemasok UAT', 'status' => 'active']);
        $receiptVersion = self::rule($entity, $receiptType, 'receipt', [
            ['account_id' => $cashA->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split', 'program_source' => 'split'],
            ['account_id' => $revenue->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'program_source' => 'split', 'category_source' => 'transaction'],
        ]);
        $paymentVersion = self::rule($entity, $paymentType, 'payment', [
            ['account_id' => $expense->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'program_source' => 'split', 'category_source' => 'transaction', 'counterparty_source' => 'transaction'],
            ['account_id' => $cashA->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split', 'program_source' => 'split'],
        ]);
        $treasuryVersion = self::rule($entity, $treasuryType, 'treasury-transfer', [
            ['account_id' => $cashB->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_destination', 'fund_source' => 'split'],
            ['account_id' => $cashA->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_source', 'fund_source' => 'split'],
        ]);
        $interfundVersion = self::rule($entity, $interfundType, 'interfund-transfer', [
            ['account_id' => $transferIn->id, 'entry_side' => 'debit', 'amount_source' => 'transaction_gross_amount', 'fund_source' => 'interfund_destination'],
            ['account_id' => $transferOut->id, 'entry_side' => 'credit', 'amount_source' => 'transaction_gross_amount', 'fund_source' => 'interfund_source'],
        ]);
        foreach ([$receiptType, $paymentType, $treasuryType, $interfundType] as $type) {
            DocumentSequence::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => $type->code, 'name' => $type->name, 'prefix' => $type->voucher_prefix, 'scope_key' => 'uat-'.$type->code, 'status' => 'active']);
        }

        return compact('entity', 'period', 'today', 'cashA', 'cashB', 'revenue', 'expense', 'transferIn', 'transferOut', 'accountA', 'accountB', 'fundType', 'restriction', 'fund', 'destinationFund', 'program', 'receiptType', 'paymentType', 'treasuryType', 'interfundType', 'receiptCategory', 'paymentCategory', 'supplier', 'receiptVersion', 'paymentVersion', 'treasuryVersion', 'interfundVersion');
    }

    /** @param array<int,array{account_id:string,split_amount:string|int,fund_id:string,program_id?:string|null}>|null $splits */
    public static function receipt(array $context, string|int $amount, ?string $fundId = null, ?array $splits = null, ?string $programId = null, ?int $actorUserId = null): FinancialTransaction
    {
        $splits ??= [['account_id' => $context['revenue']->id, 'split_amount' => $amount, 'fund_id' => $fundId ?? $context['fund']->id]];
        if ($programId) {
            $splits = array_map(fn (array $split) => $split + ['program_id' => $programId], $splits);
        }

        return app(FinancialTransactionLifecycleService::class)->createReceipt([
            'accounting_entity_id' => $context['entity']->id,
            'transaction_type_id' => $context['receiptType']->id,
            'business_date' => $context['today'],
            'accounting_date' => $context['today'],
            'gross_amount' => $amount,
            'source_reference' => 'UAT-RCV-'.Str::uuid(),
            'idempotency_key' => 'uat-source-'.Str::uuid(),
            'primary_financial_account_id' => $context['accountA']->id,
            'category_id' => $context['receiptCategory']->id,
            'description' => 'Synthetic UAT receipt',
        ], $splits, $actorUserId);
    }

    public static function payment(array $context, string|int $amount, ?string $fundId = null, ?string $programId = null, ?int $actorUserId = null): FinancialTransaction
    {
        return app(FinancialTransactionLifecycleService::class)->createPayment([
            'accounting_entity_id' => $context['entity']->id,
            'transaction_type_id' => $context['paymentType']->id,
            'business_date' => $context['today'],
            'accounting_date' => $context['today'],
            'gross_amount' => $amount,
            'source_reference' => 'UAT-PAY-'.Str::uuid(),
            'idempotency_key' => 'uat-source-'.Str::uuid(),
            'primary_financial_account_id' => $context['accountA']->id,
            'counterparty_id' => $context['supplier']->id,
            'category_id' => $context['paymentCategory']->id,
            'description' => 'Synthetic UAT payment',
        ], [[
            'account_id' => $context['expense']->id,
            'split_amount' => $amount,
            'fund_id' => $fundId ?? $context['fund']->id,
            'program_id' => $programId,
        ]], $actorUserId);
    }

    public static function advance(FinancialTransaction $transaction, ?int $actorUserId = null): FinancialTransaction
    {
        $service = app(FinancialTransactionLifecycleService::class);
        $service->submit($transaction->id, $actorUserId);
        $service->verify($transaction->id, $actorUserId);

        return $service->approve($transaction->id, $actorUserId);
    }

    public static function post(FinancialTransaction $transaction, string $key, ?int $actorUserId = null): \App\Domain\FinancialV2\PostingResult
    {
        return app(FinancialTransactionLifecycleService::class)->post($transaction->id, $key, hash('sha256', $key), $actorUserId);
    }

    public static function attachReceiptEvidence(array $context, FinancialTransaction $transaction, ?int $actorUserId = null): void
    {
        self::attachTransactionEvidence($context, $transaction, 'receipt', $actorUserId);
    }

    public static function attachTransactionEvidence(array $context, FinancialTransaction $transaction, string $evidenceType, ?int $actorUserId = null): void
    {
        app(EvidenceService::class)->attachToTransaction(
            $context['entity']->id,
            $transaction->id,
            'uat-'.$evidenceType.'.pdf',
            'application/pdf',
            1,
            hash('sha256', $transaction->id.'|'.$evidenceType),
            'test://uat/'.$evidenceType.'/'.$transaction->id,
            $evidenceType,
            $actorUserId,
        );
    }

    public static function restrictedFund(array $context, string $code, string $name, bool $allowReceipt = true): Fund
    {
        $type = FundType::create(['accounting_entity_id' => $context['entity']->id, 'code' => $code, 'name' => $name, 'classification' => 'restricted', 'status' => 'active']);
        $restriction = FundRestriction::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $type->id, 'code' => $code, 'name' => $name, 'severity' => 'high', 'policy_basis' => 'UAT policy fixture', 'status' => 'active']);
        $fund = Fund::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $type->id, 'fund_restriction_id' => $restriction->id, 'code' => $code, 'name' => $name, 'purpose_statement' => 'Synthetic UAT fund', 'status' => 'active']);
        $policy = FundPolicyVersion::create(['accounting_entity_id' => $context['entity']->id, 'fund_id' => $fund->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'policy_document_ref' => 'uat-policy', 'allowed_matrix_ref' => 'uat-matrix', 'exception_approval_level' => 'uat', 'status' => 'effective']);
        if ($allowReceipt) {
            FundPolicyRule::create(['accounting_entity_id' => $context['entity']->id, 'fund_policy_version_id' => $policy->id, 'transaction_type_id' => $context['receiptType']->id, 'decision' => 'allowed']);
        }

        return $fund;
    }

    /** @param array<int,array<string,mixed>> $lines */
    private static function rule(AccountingEntity $entity, TransactionType $type, string $family, array $lines): PostingRuleVersion
    {
        $rule = PostingRule::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => $type->code.'-UAT', 'name' => $type->name.' UAT', 'rule_family' => $family, 'status' => 'active']);
        $version = PostingRuleVersion::create(['accounting_entity_id' => $entity->id, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'input_contract_ref' => 'uat', 'journal_template_ref' => 'uat', 'business_rule_refs' => 'BR-UAT', 'status' => 'effective']);
        foreach ($lines as $index => $line) {
            PostingRuleLine::create(['accounting_entity_id' => $entity->id, 'posting_rule_version_id' => $version->id, 'line_no' => $index + 1] + $line);
        }

        return $version;
    }
}
