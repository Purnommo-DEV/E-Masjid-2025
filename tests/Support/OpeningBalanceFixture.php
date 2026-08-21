<?php

namespace Tests\Support;

use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\OpeningBalanceService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\ReasonCode;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Support\Str;

/** Fixtures deliberately model approved source positions, never legacy facts. */
final class OpeningBalanceFixture
{
    /** @return array<string,mixed> */
    public static function context(string $periodStatus = 'open'): array
    {
        $suffix = Str::upper(Str::random(7));
        $entity = AccountingEntity::create(['code' => "OB-{$suffix}", 'name' => 'Opening Rehearsal', 'legal_name' => 'Opening Rehearsal', 'status' => 'active']);
        $calendar = AccountingCalendar::create(['accounting_entity_id' => $entity->id, 'code' => "CAL-{$suffix}", 'name' => 'Rehearsal Calendar', 'fiscal_year_label' => "2099-{$suffix}", 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => 'active']);
        $period = AccountingPeriod::create(['accounting_entity_id' => $entity->id, 'accounting_calendar_id' => $calendar->id, 'period_no' => 1, 'period_name' => 'Opening Period', 'start_date' => now()->subDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'status' => $periodStatus]);
        $assetGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'AST', 'name' => 'Assets', 'group_class' => 'asset', 'status' => 'active']);
        $equityGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'NET', 'name' => 'Net Assets', 'group_class' => 'net_asset', 'status' => 'active']);
        $cash = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'CASH', 'name' => 'Bank', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
        $equity = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $equityGroup->id, 'code' => 'NET-ASSET', 'name' => 'Saldo Dana', 'account_class' => 'net_asset', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);
        $financialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cash->id, 'code' => 'BANK', 'name' => 'Rekening Utama', 'account_type' => 'bank', 'opening_date' => now()->subYear()->toDateString(), 'status' => 'active']);
        BankAccountDetail::create(['financial_account_id' => $financialAccount->id, 'bank_name' => 'Bank Uji', 'account_number_masked' => '****0001']);
        $fundType = FundType::create(['accounting_entity_id' => $entity->id, 'code' => 'GEN', 'name' => 'General', 'classification' => 'unrestricted']);
        $restriction = FundRestriction::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'code' => 'GEN', 'name' => 'General', 'severity' => 'low', 'policy_basis' => 'fixture', 'status' => 'active']);
        $fund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'UMUM', 'name' => 'Dana Umum', 'purpose_statement' => 'Fixture', 'status' => 'active']);
        self::definition($entity->id, 'OPB', 'opening-balance', false);

        return compact('entity', 'period', 'cash', 'equity', 'financialAccount', 'fund');
    }

    /** @return array{batch:OpeningBalanceBatch,lines:array<int,OpeningBalanceLine>} */
    public static function approvedBatch(array $context, string $reference = 'REHEARSAL-01', bool $withEvidence = true, string $cashSource = '100.00', ?Program $program = null): array
    {
        $service = app(OpeningBalanceService::class);
        $set = $service->createMappingSet(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-'.substr(hash('sha256', $reference), 0, 12), 'name' => 'Approved fixture mappings', 'source_system_name' => 'approved_rehearsal_fixture', 'position_date' => now()->toDateString()]);
        self::map($service, $set, 'SOURCE-CASH', 'account', $context['cash']->id);
        self::map($service, $set, 'SOURCE-CASH', 'financial_account', $context['financialAccount']->id);
        self::map($service, $set, 'SOURCE-CASH', 'fund', $context['fund']->id);
        if ($program) {
            self::map($service, $set, 'SOURCE-CASH', 'program', $program->id);
        }
        self::map($service, $set, 'SOURCE-EQUITY', 'account', $context['equity']->id);
        self::map($service, $set, 'SOURCE-EQUITY', 'fund', $context['fund']->id);
        $service->reviewMappingSet($set->id);
        $service->approveMappingSet($set->id);
        $batch = $service->createDraft(['accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id, 'mapping_set_id' => $set->id, 'position_date' => now()->toDateString(), 'rehearsal_reference' => $reference, 'evidence_package_ref' => 'fixture/'.$reference]);
        $cash = $service->addLine($batch->id, ['account_id' => $context['cash']->id, 'fund_id' => $context['fund']->id, 'financial_account_id' => $context['financialAccount']->id, 'program_id' => $program?->id, 'debit_amount' => '100.00', 'credit_amount' => '0.00', 'source_debit_amount' => $cashSource, 'source_credit_amount' => '0.00', 'source_reference' => 'SOURCE-CASH', 'evidence_ref' => 'statement/source-cash', 'line_description' => 'Saldo rekening dari sumber']);
        $equity = $service->addLine($batch->id, ['account_id' => $context['equity']->id, 'fund_id' => $context['fund']->id, 'debit_amount' => '0.00', 'credit_amount' => '100.00', 'source_debit_amount' => '0.00', 'source_credit_amount' => '100.00', 'source_reference' => 'SOURCE-EQUITY', 'evidence_ref' => 'approval/source-equity', 'line_description' => 'Posisi dana dari sumber']);
        if ($withEvidence) {
            self::attach($context['entity']->id, $cash->id, 'cash');
            self::attach($context['entity']->id, $equity->id, 'equity');
        }
        if ($cashSource === '100.00') {
            $batch = $service->review($batch->id);
            if ($withEvidence) {
                $batch = $service->approve($batch->id);
            }
        }

        return ['batch' => $batch, 'lines' => [$cash, $equity]];
    }

    public static function map(OpeningBalanceService $service, MappingSet $set, string $source, string $dimension, string $targetId): void
    {
        $service->recordMapping($set->id, $source, $dimension, 'mapped', $targetId, $source, 'Approved fixture mapping');
    }

    public static function attach(string $entityId, string $lineId, string $name): void
    {
        app(EvidenceService::class)->attachToOpeningBalanceLine($entityId, $lineId, "{$name}.pdf", 'application/pdf', 1, hash('sha256', $lineId), "test://opening/{$lineId}", 'statement');
    }

    /** @return array{type:TransactionType,version:PostingRuleVersion} */
    public static function definition(string $entityId, string $code, string $family, bool $withLines): array
    {
        $type = TransactionType::firstOrCreate(['accounting_entity_id' => $entityId, 'code' => $code], ['name' => $code, 'voucher_prefix' => $code, 'status' => 'active']);
        $rule = PostingRule::create(['accounting_entity_id' => $entityId, 'transaction_type_id' => $type->id, 'code' => $code, 'name' => $code, 'rule_family' => $family, 'status' => 'active']);
        $version = PostingRuleVersion::create(['accounting_entity_id' => $entityId, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay()->toDateString(), 'input_contract_ref' => 'fixture', 'journal_template_ref' => 'fixture', 'business_rule_refs' => 'BR-FIXTURE', 'status' => 'effective']);
        DocumentSequence::create(['accounting_entity_id' => $entityId, 'transaction_type_id' => $type->id, 'code' => $code, 'name' => $code, 'prefix' => $code, 'scope_key' => 'fixture', 'status' => 'active']);

        return compact('type', 'version');
    }

    public static function correctionEvidenceAndApproval(array $context, FinancialTransaction $transaction): void
    {
        ApprovalDecision::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $transaction->id, 'step_no' => 1, 'decision' => 'approved', 'decision_at' => now(), 'comment' => 'Fixture approval']);
        $attachment = Attachment::create(['accounting_entity_id' => $context['entity']->id, 'original_filename' => 'approval.pdf', 'media_type' => 'application/pdf', 'byte_size' => 1, 'content_hash' => hash('sha256', $transaction->id), 'storage_reference' => 'test://correction/'.$transaction->id, 'status' => 'active', 'received_at' => now()]);
        AttachmentLink::create(['accounting_entity_id' => $context['entity']->id, 'attachment_id' => $attachment->id, 'target_type' => 'transaction', 'target_id' => $transaction->id, 'evidence_type' => 'other', 'status' => 'active']);
    }

    public static function correctionTransaction(array $context, string $code, string $reasonClass, ?string $relatedTransactionId = null): FinancialTransaction
    {
        $definition = self::definition($context['entity']->id, $code, $reasonClass, $code === 'ADJ');
        if ($code === 'ADJ') {
            PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $definition['version']->id, 'line_no' => 1, 'account_id' => $context['cash']->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split']);
            PostingRuleLine::create(['accounting_entity_id' => $context['entity']->id, 'posting_rule_version_id' => $definition['version']->id, 'line_no' => 2, 'account_id' => $context['equity']->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split']);
        }
        $reason = ReasonCode::create(['accounting_entity_id' => $context['entity']->id, 'code' => $code.'-R', 'name' => $code.' reason', 'reason_class' => $reasonClass, 'status' => 'active']);
        $transaction = FinancialTransaction::create(['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $definition['type']->id, 'status' => 'approved', 'source_reference' => "{$code}-FIXTURE-".Str::uuid(), 'business_date' => now()->toDateString(), 'accounting_date' => now()->toDateString(), 'description' => "{$code} fixture correction", 'gross_amount' => '10.00', 'primary_financial_account_id' => $context['financialAccount']->id, 'reason_code_id' => $reason->id, 'related_transaction_id' => $relatedTransactionId, 'idempotency_key' => "source-{$code}-".Str::uuid(), 'correlation_id' => (string) Str::uuid()]);
        TransactionSplit::create(['accounting_entity_id' => $context['entity']->id, 'transaction_id' => $transaction->id, 'line_no' => 1, 'split_amount' => '10.00', 'account_id' => $context['equity']->id, 'fund_id' => $context['fund']->id]);
        self::correctionEvidenceAndApproval($context, $transaction);

        return $transaction;
    }
}
