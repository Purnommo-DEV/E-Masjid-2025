<?php

namespace App\Domain\FinancialV2\Reporting;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Canonical Financial V2 reporting source.
 *
 * Every ledger query is constrained to a Posted Journal and keeps the
 * original JournalLine, transaction, voucher, and reversal lineage available
 * for drill-down. It intentionally contains no legacy-table access and no
 * write operation.
 */
final class PostedLedgerQuery
{
    public function ledger(string $entityId, string $throughAccountingDate): Builder
    {
        return DB::table('financial_v2_ledger_entries as ledger')
            ->join('financial_v2_journal_lines as journal_line', 'journal_line.id', '=', 'ledger.journal_line_id')
            ->join('financial_v2_journals as journal', 'journal.id', '=', 'journal_line.journal_id')
            ->join('financial_v2_transactions as financial_transaction', 'financial_transaction.id', '=', 'journal.transaction_id')
            ->join('financial_v2_transaction_types as transaction_type', 'transaction_type.id', '=', 'financial_transaction.transaction_type_id')
            ->leftJoin('financial_v2_journals as original_journal', 'original_journal.id', '=', 'journal.reversal_of_journal_id')
            ->leftJoin('financial_v2_transactions as original_transaction', 'original_transaction.id', '=', 'original_journal.transaction_id')
            ->leftJoin('financial_v2_transaction_types as original_transaction_type', 'original_transaction_type.id', '=', 'original_transaction.transaction_type_id')
            ->leftJoin('financial_v2_vouchers as voucher', 'voucher.transaction_id', '=', 'financial_transaction.id')
            ->where('ledger.accounting_entity_id', $entityId)
            ->where('ledger.accounting_date', '<=', $throughAccountingDate)
            ->where('journal.journal_status', 'posted');
    }

    public function journals(string $entityId, string $throughAccountingDate): Builder
    {
        return DB::table('financial_v2_journals as journal')
            ->join('financial_v2_transactions as financial_transaction', 'financial_transaction.id', '=', 'journal.transaction_id')
            ->join('financial_v2_transaction_types as transaction_type', 'transaction_type.id', '=', 'financial_transaction.transaction_type_id')
            ->leftJoin('financial_v2_journals as original_journal', 'original_journal.id', '=', 'journal.reversal_of_journal_id')
            ->leftJoin('financial_v2_transactions as original_transaction', 'original_transaction.id', '=', 'original_journal.transaction_id')
            ->leftJoin('financial_v2_transaction_types as original_transaction_type', 'original_transaction_type.id', '=', 'original_transaction.transaction_type_id')
            ->leftJoin('financial_v2_vouchers as voucher', 'voucher.transaction_id', '=', 'financial_transaction.id')
            ->where('journal.accounting_entity_id', $entityId)
            ->where('journal.accounting_date', '<=', $throughAccountingDate)
            ->where('journal.journal_status', 'posted');
    }

    public function watermark(string $entityId, string $throughAccountingDate): int
    {
        return (int) $this->ledger($entityId, $throughAccountingDate)->max('ledger.posting_sequence');
    }

    /** @return \Illuminate\Database\Query\Expression */
    public function effectiveTransactionTypeCode()
    {
        return DB::raw('COALESCE(original_transaction_type.code, transaction_type.code)');
    }
}
