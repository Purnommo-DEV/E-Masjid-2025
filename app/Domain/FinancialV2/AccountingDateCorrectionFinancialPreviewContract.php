<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;

/**
 * Canonical, read-only planner contract for a future reversal-and-replacement workflow.
 *
 * An implementation must use the same posting-rule resolution, line compilation,
 * decimal validation, period controls, and liquidity timeline as PostingEngine.
 * It must not persist transactions, journals, journal lines, ledger entries, or
 * idempotency records. The returned snapshot must contain the proposed reversal
 * and replacement lines, source/target period effects, per-account/fund running
 * liquidity, configuration identities/versions, and all blocking reasons.
 *
 * No implementation is registered yet. Its absence is intentionally fail-closed.
 */
interface AccountingDateCorrectionFinancialPreviewContract
{
    public function preview(
        FinancialTransaction $originalTransaction,
        Journal $originalJournal,
        string $targetAccountingDate,
        ?array $policyException = null,
    ): AccountingDateCorrectionPostingPlan;
}
