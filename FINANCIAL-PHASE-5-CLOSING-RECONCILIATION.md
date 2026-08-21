# Financial Phase 5 - Closing & Reconciliation Foundation

## Status

**CLOSING & RECONCILIATION FOUNDATION: PASS (local development / isolated test database).**

Phase 5 activates Financial V2 period controls and reconciliation controls only. It does not create an Opening Balance, historical migration, cutover date, production ledger activation, dual-write, or a dependency on legacy finance records.

## 1. Program Dimension Fix

### Root cause

`PostingEngine::validateOptionalDimensions()` applied `valid_from` / `valid_to` to every optional master dimension. `financial_v2_programs` instead uses its approved lifecycle date fields `start_date` / `end_date`; querying the nonexistent fields could fail Program-attributed posting.

### Narrow correction

For `program_id` only, the validator now checks `status`, `start_date`, and `end_date`. Other optional dimensions continue to use their existing `valid_from` / `valid_to` contract. No Program migration, policy change, or new effective-date interpretation was introduced.

Regression coverage proves that an active in-date Program posts to JournalLine and Ledger, nonexistent and inactive Programs are rejected, optional Program attribution may remain absent, and Program remains separate from Fund and FinancialAccount.

## 2. Closing Architecture and Rules

- `PeriodClosingService` is the controlled writer for `AccountingPeriod` close state and `ClosingRun` records.
- Existing states are retained: `open`, `soft_closed`, `hard_closed`, and `reopened`. This phase implements only Open -> Soft Closed -> Hard Closed. It creates no reopen workflow.
- `PeriodClosingStateGuard` rejects direct Eloquent writes to period closing state and ClosingRun records.
- A completed ClosingRun is immutable and ClosingRun deletion is prohibited.
- `FinancialTransactionLifecycleService` now checks the accounting period before create, edit, split replacement, approval, submit/verify, cancellation/rejection, and operational transfer creation.
- Open permits ordinary work. The existing approved architecture permits `ADJ` only while Soft Closed; ordinary receipt, payment, treasury transfer, interfund transfer, and normal posting are rejected. Hard Closed rejects all such work. Reversal remains governed by the existing PostingEngine period gate and is not given a new exception.
- Closing changes only control records. It never creates, updates, deletes, or rebalances Journal, JournalLine, LedgerEntry, Voucher, or historical balances.

## 3. Pre-close Integrity Checklist

`PeriodClosingService` records a checklist version and a JSON result summary on each run. Soft Close is blocked when any of these fail:

1. posted Journal total/line integrity;
2. one-to-one JournalLine to Ledger integrity, including orphan and dimension/sequence/date alignment;
3. unresolved operational transaction states or a posted/reversed transaction without posted Journal;
4. failed or recovery-required PostingAttempt;
5. Trial Balance equality at the canonical reporting watermark.

Hard Close requires all Soft Close checks plus every active bank/cash/petty-cash FinancialAccount for that period to have a completed zero-difference reconciliation. A blocked closing leaves the period unchanged and retains a `blocked` ClosingRun plus an AuditEvent.

## 4. Reconciliation Architecture and Workflow

Existing `financial_v2_reconciliations` and `financial_v2_reconciliation_items` are reused. The Phase 5 migration adds only missing audit/control fields: `difference`, `notes`, reviewer timestamp/user, and reconciler timestamp/user.

`ReconciliationService` workflow follows the existing state vocabulary:

`draft` -> `in_progress` -> `reviewed` -> `completed`

or, for an unresolved non-zero difference:

`draft|in_progress|reviewed` -> `exception`

The service accepts an external statement/cash-count balance but always recalculates Book Balance from `BalanceInquiryService::financialAccountBalance()`, which reads only posted Financial V2 Ledger joined to posted V2 Journal. Difference is explicit and exact:

`statement_balance - ledger_balance`

A reconciliation cannot become `completed` unless the recalculated difference is exactly `0.00` and the required active evidence exists. A non-zero difference must remain visible as `exception`; it does not trigger an automatic adjustment or any Ledger mutation.

## 5. Evidence and Audit Trail

- Bank reconciliation completion requires active `statement` evidence; cash/petty-cash completion requires active `cash_count` evidence.
- `EvidenceService::attachToReconciliation()` appends immutable AttachmentLink records using the existing `reconciliation` target type. It validates permitted media, content hash, and metadata; it does not overwrite evidence.
- Reconciliation evidence is not an accounting fact.
- Audit events retain draft, review start, review, completion, exception, closing block, period close, and evidence-link actions with actor, target, timestamp, before/after summary, and correlation identifier.
- `ReconciliationStateGuard` prevents direct Eloquent creation/update of reconciliation balance/state records. A completed reconciliation is immutable and cannot be deleted.

## 6. Minimal Control UX

The isolated authenticated route `/admin/keuangan-v2/kontrol` adds plain-language controls:

- **Tutup Periode** for Soft/Hard close actions and ClosingRun history;
- **Rekonsiliasi Rekening** for bank/cash statement input, evidence attachment, review, completion, and exception recording.

It is not a new dashboard. It exposes period status, closing-run status, latest reconciliation status/difference, and the accounts that block Hard Close, so later dashboard/reporting work can consume the control data. The controller only delegates to control services and does not write financial facts.

## 7. Validation Record

| Check | Result |
|---|---|
| Program regression | PASS - valid/inactive/nonexistent/optional Program behavior and Journal/Ledger attribution are covered. |
| Closing controls | PASS - successful Soft/Hard close, immutable fact/report checks, blocked unresolved work, direct-state guard, and audit trail are covered. |
| Reconciliation | PASS - V2 Ledger-only book balance, exact difference, evidence, audit, completion, exception, and unchanged Journal/Ledger facts are covered. |
| Existing soft-close exception | PASS - the pre-existing governed `ADJ` soft-close PostingEngine test remains green. |
| Financial V2 suite | PASS - 45 tests / 275 assertions. |
| Full baseline | PASS - 71 tests / 344 assertions; 0 failed, 0 skipped, 1 pre-existing risky `ExampleTest` output-buffer warning. |
| Test isolation | PASS - preflight confirms `APP_ENV=testing`, MySQL, and `DB_DATABASE=mrj_test_db`. |
| Schema migration | PASS - Phase 5 migration ran in `mrj_test_db`; rollback/reapply rehearsal succeeded only on that disposable database. |
| Schema verification | PASS - reconciliation difference/review/reconcile columns, user FKs, uniqueness, entity/status index, and FK indexes are present. |
| PHP lint / Pint / Blade / Vite / diff | PASS - linted Phase 5 PHP, `pint --test` passed, `view:cache` passed, Vite build passed, and `git diff --check` passed. |

The test command retains pre-existing legacy-seeder console noise (`Berita ID ... tidak ditemukan`) without test failures. It is not used by Financial V2 controls.

## 8. Canonical Writer, Reporting, and Legacy Isolation

- The application-wide canonical-writer audit still finds `Journal::create`, `JournalLine::create`, and `LedgerEntry::create` only in `PostingEngine`.
- Closing and reconciliation do not create financial facts and tests compare Journal/JournalLine/Ledger counts before and after both workflows.
- Trial Balance output remains byte-for-byte reproducible before Soft Close, after reconciliation, and after Hard Close because its source remains Posted V2 Ledger/Journal.
- Phase 5 services, controller, view, migrations, and tests contain no legacy finance-table query. The Ledger-only reconciliation test records its SQL and confirms the read path contains `financial_v2_ledger_entries`, not the legacy `jurnal` table.
- No legacy table was altered, deleted, migrated, dual-written, or used as a balance source.

## 9. Remaining Risks and Next Gate

- Real business master data, bank/cash evidence, reviewer assignment, approval authority, and period-close ownership still require governed onboarding; Phase 5 tests use isolated fixtures only.
- Existing architecture includes `reopened`, but this phase deliberately has no reopen implementation. A governed reopen/correction scope must be approved separately.
- No actual Opening Balance, historical balance mapping, rehearsal migration, cutover date, production activation, or production reconciliation has been performed.

The next permitted gate is **OPENING BALANCE + RECONCILIATION REHEARSAL**. It must separately approve source mapping, evidence, reconciliation tie-out, cutover date, and rollback procedure before any actual opening position is entered.
