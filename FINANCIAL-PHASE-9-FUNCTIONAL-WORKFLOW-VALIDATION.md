# Financial Phase 9 — Functional Workflow Validation

**Financial V2 Functional Readiness: PASS WITH FIXES**

Validation was performed only with disposable synthetic fixtures in `APP_ENV=testing` and `mrj_test_db`. No production database, legacy financial record, cutover action, dual-write, production opening balance, backup/recovery, RACI, or deployment governance action was performed.

The workflow defect found during this phase was remediated and covered by regression tests: a UI-created allocation remained a draft while a realization could select only an approved allocation, but the UI had no approved lifecycle action. The existing governed allocation lifecycle is now reachable through **Ajukan alokasi** and **Setujui alokasi**. These actions use `BudgetAllocationService` only and do not create financial facts.

## 1. Functional Workflow

The operator-facing workflow is verified as an operational flow: record the event, select the relevant operational dimensions, attach evidence where the configured rule requires it, and post it. The UI does not ask an operator to choose debit or credit. Official effects are produced only after lifecycle posting reaches the canonical Posting Engine.

## 2. Receipt

Receipt creation, attachment retention, edit-before-post, source-key idempotency, repeated post protection, voucher issuance, balanced Journal/JournalLine creation, and immutable Ledger creation passed. The synthetic Friday receipt scenario is covered by `OperationalUxTest` and UAT-001/UAT-002.

## 3. Payment

Payment posts only when Fund policy, fund liquidity, period, configured rule, and evidence/approval conditions are satisfied. Restricted-fund rejection and insufficient-fund controls return Indonesian operator messages and do not create Journal or Ledger facts. Valid payment flow and its report effect are covered by the operational and reporting suites.

## 4. Transfer

Treasury transfer moves value from the source FinancialAccount to the destination FinancialAccount while preserving Fund attribution. It creates no revenue or expense line, remains balanced, and is represented as a posted V2 transaction. The 10-worker rehearsal also confirmed concurrent transfer handling.

## 5. Fund

Fund, FinancialAccount, Program, Account, and JournalLine remain independent dimensions. The suite verifies that Program attribution is optional and distinct from Fund/FinancialAccount, restricted Fund policy is enforced, and ZISWAF fixtures do not mix with the operational fund.

## 6. Allocation

Allocation UI explicitly states that it is a designation of purpose, not an expense. The corrected lifecycle is:

`Draft allocation → Ajukan alokasi → Setujui alokasi → eligible for realization`

The regression test proves allocation submit/approval creates zero Journal and zero Ledger facts.

## 7. Realization

An approved allocation version can be selected for a realization; posting produces the one linked payment effect through the Posting Engine. The test proves recorded realization, allocated/actual/available calculation (`75.00 / 20.00 / 55.00` in the synthetic scenario), and a controlled overspend rejection without additional facts.

## 8. Transaction History

History provides period, type, FinancialAccount, Fund, Program, category, status, and text filters. The default detail is operational and evidence-oriented; Journal, Account, debit, credit, and Ledger references are in the expandable advanced accounting section rather than the main operator view.

## 9. Dashboard

Dashboard balances, period receipts/payments/transfers, financial-account balances, fund balances, and recent activity are sourced from Financial V2 posted Ledger and V2 posted transactions. It does not use a legacy financial table as a fallback.

## 10. Reporting

Account balance, fund balance, account/fund movement, cash flow, trial balance, Friday, ZISWAF, program, summary, and transaction-history reports are covered. Drafts are excluded, reporting does not mutate financial facts, and Trial Balance debit equals credit.

## 11. Closing

Soft/hard closing is invoked through the control service. Closed-period posting is rejected; posted facts remain immutable; hard close requires completed zero-difference reconciliations. The UI uses a human operational message for a period that is not available for recording.

## 12. Reconciliation

Reconciliation derives book balance from posted V2 Ledger. A zero difference can complete with evidence; a non-zero difference remains an explicit exception and does not automatically adjust Ledger. Reconciliation completion leaves Journal, JournalLine, and Ledger counts unchanged.

## 13. Opening Balance

The synthetic OpeningBalanceBatch/OpeningBalanceLine rehearsal passed: explicit mapping, source reference, evidence, review/approval, source reconciliation, canonical posting, idempotent re-run, immutable posted records, correction/reversal, and reporting tie-out. No real opening balance was used.

## 14. UX

Forms use event language such as Penerimaan, Pengeluaran, Transfer, Alokasi, and Realisasi. They resolve accounts server-side from the effective rule. Human messages are returned for restricted Fund, insufficient balance, closed period, missing evidence, duplicate requests, and unavailable allocation; engine exception classes, SQL, and stack traces are not exposed as operator messages.

## 15. Mobile

Authenticated feature rendering verifies responsive operator markup: single-column/mobile-safe controls, `inputmode="decimal"`, full-width file and form controls, responsive grids, and mobile-card/desktop-table breakpoints for dashboard and history. A live in-app-browser check could not bind to the local PHP listener (`127.0.0.1` connection refused), a desktop-environment limitation also seen earlier; it is not an application response failure. No production or external browser was used.

## 16. Accounting Integrity

The writer audit found only these official fact creates:

| Fact | Canonical writer |
| --- | --- |
| Journal | `PostingEngine` |
| JournalLine | `PostingEngine` |
| LedgerEntry | `PostingEngine` |

Controllers added or exercised in this phase call lifecycle/control/allocation services only. The 10-worker rehearsal reconfirmed atomicity, idempotency, balance, unique vouchers, no orphan/missing ledger, and balanced Trial Balance.

## 17. Realistic Masjid Scenarios

All data was synthetic. Coverage includes Friday receipt, operational expense, Treasury transfer, Fund allocation and realization, controlled restricted/ZISWAF fund handling, program attribution, and opening balance rehearsal. The UAT fixture also covers six policy-controlled ZISWAF funds, without using real balances, counterparties, account numbers, or historical records.

## 18. Regression

The Phase 9 regression added to `OperationalUxTest` proves the formerly blocked operator workflow:

1. create allocation with separate Fund, Program, and Category;
2. submit and approve its governed version in UI;
3. prove no allocation Journal/Ledger exists;
4. post receipt and linked realization;
5. prove a single payment effect and availability calculation;
6. reject an allocation overspend with no new facts.

## 19. Test Results

| Validation | Result |
| --- | --- |
| `php artisan test tests/Feature/FinancialV2` | 72 passed, 427 assertions, 0 failed, 0 skipped |
| `php artisan financial-v2:concurrency-rehearsal --workers=10 --format=json` | PASS; 34 journals, 68 journal lines, 68 ledger entries, 34 distinct vouchers, no missing/orphan ledger, balanced Trial Balance |
| `php artisan test` | 98 passed, 496 assertions, 0 failed, 0 skipped; 1 pre-existing risky `ExampleTest` output-buffer warning |
| PHP lint changed PHP files | PASS |
| Pint check for changed Financial V2 files | PASS |
| `git diff --check` | PASS |
| Canonical writer audit | PASS — only `PostingEngine` creates official facts |
| Legacy isolation audit | PASS — no Financial V2 runtime read/write of legacy financial tables; preflight string markers are static audit definitions only |

## 20. Remaining Functional Issues

1. **Allocation attachment target is intentionally unavailable.** The approved AttachmentLink taxonomy has no `budget_allocation` target and allocation is non-journal. The UI states this boundary instead of fabricating a transaction/journal attachment. Supporting an allocation attachment requires a separately approved Foundation taxonomy decision; it was not changed in this phase.
2. **Live local-browser viewport inspection is environment-limited.** The local listener was refused by the desktop environment. Server-rendered authenticated feature tests and responsive markup checks passed, but this is not a substitute for a future on-device exploratory session.

Neither issue is an accounting-integrity defect. No cutover, deployment, production data migration, or legacy shutdown action follows from this report.
