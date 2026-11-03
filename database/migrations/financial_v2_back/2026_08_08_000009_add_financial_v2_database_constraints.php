<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Financial V2 Foundation migration.

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE financial_v2_accounting_entities ADD CONSTRAINT fv2_entity_fiscal_month_ck CHECK (fiscal_year_start_month BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE financial_v2_accounting_calendars ADD CONSTRAINT fv2_calendar_dates_ck CHECK (end_date >= start_date)');
        DB::statement('ALTER TABLE financial_v2_accounting_periods ADD CONSTRAINT fv2_period_no_ck CHECK (period_no BETWEEN 1 AND 13)');
        DB::statement('ALTER TABLE financial_v2_accounting_periods ADD CONSTRAINT fv2_period_dates_ck CHECK (end_date >= start_date)');
        DB::statement('ALTER TABLE financial_v2_account_groups ADD CONSTRAINT fv2_acc_group_dates_ck CHECK (valid_to IS NULL OR valid_to >= valid_from)');
        DB::statement('ALTER TABLE financial_v2_accounts ADD CONSTRAINT fv2_account_dates_ck CHECK (valid_to IS NULL OR valid_to >= valid_from)');
        DB::statement('ALTER TABLE financial_v2_account_dimension_rules ADD CONSTRAINT fv2_adr_side_ck CHECK (applies_to_debit = 1 OR applies_to_credit = 1)');
        DB::statement('ALTER TABLE financial_v2_account_dimension_rules ADD CONSTRAINT fv2_adr_dates_ck CHECK (effective_to IS NULL OR effective_to >= effective_from)');
        DB::statement('ALTER TABLE financial_v2_funds ADD CONSTRAINT fv2_fund_min_balance_ck CHECK (minimum_balance_policy IS NULL OR minimum_balance_policy >= 0)');
        DB::statement('ALTER TABLE financial_v2_funds ADD CONSTRAINT fv2_fund_dates_ck CHECK (valid_to IS NULL OR valid_to >= valid_from)');
        DB::statement('ALTER TABLE financial_v2_fund_policy_versions ADD CONSTRAINT fv2_fund_policy_dates_ck CHECK (effective_to IS NULL OR effective_to >= effective_from)');
        DB::statement('ALTER TABLE financial_v2_financial_accounts ADD CONSTRAINT fv2_fin_acc_dates_ck CHECK (closing_date IS NULL OR closing_date >= opening_date)');
        DB::statement('ALTER TABLE financial_v2_cash_account_details ADD CONSTRAINT fv2_cash_limit_ck CHECK (petty_cash_limit IS NULL OR petty_cash_limit >= 0)');
        DB::statement('ALTER TABLE financial_v2_programs ADD CONSTRAINT fv2_program_dates_ck CHECK (end_date IS NULL OR start_date IS NULL OR end_date >= start_date)');
        DB::statement('ALTER TABLE financial_v2_document_sequences ADD CONSTRAINT fv2_doc_seq_next_ck CHECK (next_value >= 1)');
        DB::statement('ALTER TABLE financial_v2_transactions ADD CONSTRAINT fv2_tx_amount_ck CHECK (gross_amount > 0)');
        DB::statement('ALTER TABLE financial_v2_transactions ADD CONSTRAINT fv2_tx_not_self_related_ck CHECK (related_transaction_id IS NULL OR related_transaction_id <> id)');
        DB::statement('ALTER TABLE financial_v2_transaction_splits ADD CONSTRAINT fv2_tx_split_amount_ck CHECK (split_amount > 0)');
        DB::statement('ALTER TABLE financial_v2_attachments ADD CONSTRAINT fv2_attachment_size_ck CHECK (byte_size > 0)');
        DB::statement('ALTER TABLE financial_v2_journals ADD CONSTRAINT fv2_journal_total_ck CHECK (total_debit >= 0 AND total_credit >= 0)');
        DB::statement('ALTER TABLE financial_v2_journal_lines ADD CONSTRAINT fv2_jl_one_side_ck CHECK ((debit_amount > 0 AND credit_amount = 0) OR (credit_amount > 0 AND debit_amount = 0))');
        DB::statement('ALTER TABLE financial_v2_opening_balance_lines ADD CONSTRAINT fv2_open_line_one_side_ck CHECK ((debit_amount > 0 AND credit_amount = 0) OR (credit_amount > 0 AND debit_amount = 0))');
        DB::statement('ALTER TABLE financial_v2_reconciliation_items ADD CONSTRAINT fv2_recon_item_amount_ck CHECK (amount <> 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE financial_v2_reconciliation_items DROP CHECK fv2_recon_item_amount_ck');
        DB::statement('ALTER TABLE financial_v2_opening_balance_lines DROP CHECK fv2_open_line_one_side_ck');
        DB::statement('ALTER TABLE financial_v2_journal_lines DROP CHECK fv2_jl_one_side_ck');
        DB::statement('ALTER TABLE financial_v2_journals DROP CHECK fv2_journal_total_ck');
        DB::statement('ALTER TABLE financial_v2_attachments DROP CHECK fv2_attachment_size_ck');
        DB::statement('ALTER TABLE financial_v2_transaction_splits DROP CHECK fv2_tx_split_amount_ck');
        DB::statement('ALTER TABLE financial_v2_transactions DROP CHECK fv2_tx_not_self_related_ck');
        DB::statement('ALTER TABLE financial_v2_transactions DROP CHECK fv2_tx_amount_ck');
        DB::statement('ALTER TABLE financial_v2_document_sequences DROP CHECK fv2_doc_seq_next_ck');
        DB::statement('ALTER TABLE financial_v2_programs DROP CHECK fv2_program_dates_ck');
        DB::statement('ALTER TABLE financial_v2_cash_account_details DROP CHECK fv2_cash_limit_ck');
        DB::statement('ALTER TABLE financial_v2_financial_accounts DROP CHECK fv2_fin_acc_dates_ck');
        DB::statement('ALTER TABLE financial_v2_fund_policy_versions DROP CHECK fv2_fund_policy_dates_ck');
        DB::statement('ALTER TABLE financial_v2_funds DROP CHECK fv2_fund_dates_ck');
        DB::statement('ALTER TABLE financial_v2_funds DROP CHECK fv2_fund_min_balance_ck');
        DB::statement('ALTER TABLE financial_v2_account_dimension_rules DROP CHECK fv2_adr_dates_ck');
        DB::statement('ALTER TABLE financial_v2_account_dimension_rules DROP CHECK fv2_adr_side_ck');
        DB::statement('ALTER TABLE financial_v2_accounts DROP CHECK fv2_account_dates_ck');
        DB::statement('ALTER TABLE financial_v2_account_groups DROP CHECK fv2_acc_group_dates_ck');
        DB::statement('ALTER TABLE financial_v2_accounting_periods DROP CHECK fv2_period_dates_ck');
        DB::statement('ALTER TABLE financial_v2_accounting_periods DROP CHECK fv2_period_no_ck');
        DB::statement('ALTER TABLE financial_v2_accounting_calendars DROP CHECK fv2_calendar_dates_ck');
        DB::statement('ALTER TABLE financial_v2_accounting_entities DROP CHECK fv2_entity_fiscal_month_ck');
    }
};
