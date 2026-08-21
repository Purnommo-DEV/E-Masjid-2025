# Financial Phase 7 — UAT & Cutover Readiness

**Execution date:** 2026-08-09  
**Environment:** `APP_ENV=testing`, connection `mysql`, database `mrj_test_db`  
**Scope:** synthetic, disposable UAT fixtures only. No production data, legacy data migration, cutover date, legacy disablement, dual-write, or production activation was performed.

**Schema verification:** all Financial V2 migrations through `2026_08_11_000015` are `Ran`; `mrj_test_db` contains 55 `financial_v2_*` tables, 270 V2 foreign-key constraints, and 472 non-primary index metadata rows. Opening-balance schema/FK/index assertions also pass in `OpeningBalanceRehearsalTest`.

## 1. UAT Matrix

| ID | Scenario / precondition / input | Expected operational result | Expected accounting result | Expected ledger result | Expected report result | Expected control result | Status | Evidence |
|---|---|---|---|---|---|---|---|---|
| UAT-001 | Active masters; configured receipt evidence; synthetic Friday receipt Rp2.500.000 | Draft → submit → verify → approve → post succeeds only after evidence is linked | One balanced Journal and one issued voucher | Two immutable entries, traced through JournalLine | Account, fund, Friday, program and Trial Balance read posted V2 facts | Evidence required; actor/timestamp audit events; retry is idempotent | PASS | `UatCutoverReadinessTest` UAT-001/002, 21 assertions |
| UAT-002 | Same fixture with suspended Program | Invalid operational attribution is rejected before financial facts commit | No additional Journal | No additional LedgerEntry | Existing reports stay unchanged | Active Program required; Program remains distinct from Fund and Financial Account | PASS | UAT-001/002; `OperationalUxTest` Program attribution test |
| UAT-003 | Six synthetic restricted funds: Zakat Maal, Infaq/Tromol, Sodaqoh, Fidyah, Dhuafa, Santunan Yatim | Each receipt posts only to its selected fund; prohibited policy is rejected | Each allowed receipt has its own balanced Journal | Each fund has exactly its own two ledger facts; operational fund remains zero | Fund/ZISWAF query can filter a governed selected fund | Restricted Fund requires an effective allowed policy; fail-closed rejection has no facts | PASS | UAT-003, 16 assertions |
| UAT-004 | Fund liquidity seeded by synthetic receipt; valid payment and restricted payment attempt, each with invoice | Valid payment posts; restricted usage is rejected | Payment uses expense/liquidity rule only when valid | Rejected payment adds no Journal or Ledger | Posted results available to reports | Invoice requirement, Fund policy, and fund-liquidity protection enforced | PASS | UAT-004/009; `OperationalFoundationTest` payment controls |
| UAT-005 | Cash A → Cash B with the same Fund and transfer proof | Transfer succeeds | Balanced liquidity-only Journal; no revenue/expense | Source decreases and destination increases with Fund preserved | Account movement/cash flow remain tied to GL | Transfer-proof requirement, source/destination validity, and Fund composition checked | PASS | UAT-004/009; `OperationalFoundationTest` treasury transfer |
| UAT-006 | Fund A → Fund B with policy basis and reason; prohibited restricted-fund attempt | Allowed governed transfer posts; prohibited one is rejected | Transfer accounts only, never income/expense | No Financial Account dimension is moved | Fund report retains separate Fund effects | Fund policy, distinct Fund, reason, and policy-basis checks | PASS | UAT-004/009; `OperationalFoundationTest` inter-fund transfer |
| UAT-007 | Approved Fund → Program allocation | Allocation is recorded and traceable | Allocation is a non-journal plan | No LedgerEntry is created by allocation | Allocation becomes realization basis; no report-side fact mutation | Governed draft/submit/approve lifecycle | PASS | UAT-004/009; `OperationalFoundationTest` allocation test |
| UAT-008 | Approved allocation Rp200; realization Rp60; sequenced overspend contender Rp150 | First realization posts and is linked; second is rejected as excess | Only posted payment becomes actual allocation use | No duplicate / excess Ledger facts | Availability is Rp140 after the first realization | Allocation-version lock and overspend check are exercised; multi-session contention remains a pre-cutover follow-up | PASS (sequenced) | UAT-004/009; `OperationalFoundationTest` realization test |
| UAT-009 | Posted payment corrected by reversal and separate adjustment fixture | Original remains immutable; correction is a new governed event | Reversal offsets original; adjustment uses its effective rule | Immutable line-level lineage retained | Net report effect reflects correction | Reason, approval, evidence, lineage, and period semantics required | PASS | `PostingEngineTest` reversal/adjustment/soft-close tests |
| UAT-010 | Open, soft-closed, and hard-closed period fixtures | Ordinary work allowed only when open; closed work rejected | Historical Journal remains unchanged | Historical Ledger remains unchanged | Reports remain reproducible | Closing checks prevent incomplete/invalid close | PASS | `OperationalFoundationTest` closing tests; `PostingEngineTest` soft-close adjustment |
| UAT-011 | Book balance equals statement and non-zero difference fixtures | Zero difference completes; non-zero becomes exception | Reconciliation does not post an automatic adjustment | No financial facts change on reconciliation | Book balance demonstrably comes from posted V2 Ledger | Evidence required for completion; exception retained with audit trail | PASS | `OperationalFoundationTest` reconciliation zero/difference tests |
| UAT-012 | Controlled OpeningBalanceBatch/Line, mapping, and source evidence | Approved balanced opening posts once; repeated call returns same result | Canonical Opening Balance Journal through Posting Engine | Immutable Ledger facts, no duplicate on replay | Account/fund/financial-account/cash/trial reports tie out | Explicit mappings, evidence and source reconciliation required; Program remains separate | PASS | `OpeningBalanceRehearsalTest`, 21 Phase-6 tests |
| UAT-013 | Posted receipt/payment/transfer/opening fixtures | Operational outcomes tie to financial facts | Journal debit equals credit | Ledger derives from every posted JournalLine | Account balance, Fund balance, movements, cash flow, Trial Balance, Friday, ZISWAF, Program and history are V2-GL sourced | Drafts excluded; report calls do not mutate facts | PASS | `OperationalUxTest` reporting foundation and zero-data tests; `OpeningBalanceRehearsalTest` reporting test |
| UAT-014 | Ordinary mosque administrator HTTP workflows | Receipt, payment, transfer, allocation, history, detail, evidence, controls and reports are available without accounting-jargon input | UI calls lifecycle and canonical posting only | UI does not write Ledger directly | Dashboard/history label their Posted Ledger V2 source | User-facing restricted/closed errors are translated | PASS | `OperationalUxTest` workflow, dashboard/history, report and restricted-fund tests |
| UAT-015 | Tamper/replay/edit/delete attempts | Invalid dimensions and replay are rejected; posted transaction cannot be edited/deleted | No second Journal or unauthorized correction | Ledger remains append-only | Reports expose only posted canonical facts | Idempotency conflict, state guard, immutability and attachment integrity enforced | PASS | `PostingEngineTest` idempotency/failure tests; `OperationalFoundationTest` immutable attachment test |
| UAT-016 | Required evidence/approval missing, bad rule, failed post, retry | Failure is retained diagnostically; corrected same-key retry can proceed | No half-created Journal | No half-created LedgerEntry | Failed work is excluded from reports | Database transaction atomicity and idempotency state retained | PASS | `PostingEngineTest` evidence, approval, unbalanced-rule and failed-retry tests |
| UAT-017 | 100, then 1,000 synthetic receipt transactions; 100 multi-split receipts | All 1,100 transactions complete through lifecycle and canonical post | 1,100 balanced Journals | 12,000 JournalLines and 12,000 LedgerEntries | Summary, account balance, fund balance, history (200-page window) and Trial Balance return V2 data | No direct fact write; Trial Balance remains balanced | PASS | UAT-017, 11 assertions, 209.68 s standalone; 191.00 s in integrated V2 suite |

## 2. Business Scenarios

The Phase 7 fixture creates a unique UUID entity and its own calendar, open period, accounts, financial accounts, funds, Program, categories, posting rules and document sequences. The Rp2.500.000 Friday receipt and every ZISWAF/payment/transfer value are synthetic. `Program`, `Fund`, `FinancialAccount`, `Account`, and `JournalLine` are independently created dimensions; no scenario maps one into another.

## 3. Accounting Verification

- Every operational financial fact is created only after lifecycle approval and `PostingEngine` execution.
- UAT receipt, payment, treasury transfer, inter-fund transfer, realization, reversal, adjustment, and opening balance scenarios produce balanced journals where a journal is architecturally applicable.
- Budget allocation is explicitly tested as a governed non-journal plan.

## 4. Ledger Verification

The UAT assertion follows the actual relationship `Journal → JournalLine → LedgerEntry`; `LedgerEntry` has no fictitious `journal_id` column. The 12,000-line smoke test, immutable-model tests, idempotent replay tests, and failed-post tests confirm that only committed canonical postings create Ledger facts.

## 5. Fund Verification

- The six named ZISWAF fixtures posted only to their selected funds; the operational fund received zero ZISWAF ledger entries.
- Restricted policy absence/prohibition failed closed and did not create financial facts.
- Payment liquidity, transfer Fund composition, inter-fund semantics, allocation availability, and overspend rejection were validated independently of Financial Account and Program.

## 6. Account Verification

Treasury transfer validation proved that the source financial account decreases, destination increases, and neither revenue nor expense account is used. Active/inactive financial-account validation, custody details, account matching, and balance protection are covered by the Financial V2 integration suite.

## 7. Reporting Tie-out

`FinancialReportService` is verified as read-only and sourced as `financial_v2_posted_general_ledger`. The suite covers Account Balance, Fund Balance, Account Movement, Fund Movement, Cash Flow, Trial Balance, Friday, ZISWAF, Program, summary/dashboard, and transaction history. Draft and failed transactions are excluded. Every tested Trial Balance was balanced.

## 8. Closing Verification

Closing tests confirm pre-close integrity checks, blocked close handling, preservation of historical Journal/Ledger facts, rejection of ordinary transactions in closed periods, and governed adjustment semantics for soft close.

## 9. Reconciliation Verification

Zero-difference reconciliation completes only with evidence. A non-zero difference is retained as an exception. Both branches query the posted V2 Ledger and do not mutate Journal, JournalLine, or LedgerEntry.

## 10. Opening Balance Verification

Phase 6 controlled rehearsal was rerun as part of the Financial V2 suite. It validates approved source/mapping/evidence, exact reconciliation, canonical post, reporting, correction through new events, and same-batch idempotency. No production opening balance was created.

## 11. UX Verification

HTTP feature tests cover operator-facing receipt, expense, transfer, allocation, history, detail/evidence, control and reporting paths. User-facing restricted-fund and closed-period outcomes are translated to Indonesian operational messages rather than exposing engine exceptions or stack traces.

The isolated UAT fixture also applies and satisfies a receipt-evidence requirement, an invoice requirement for payment/realization, and a transfer-proof requirement for treasury transfer. Reconciliation and Opening Balance evidence are validated by their established feature suites.

## 12. Security Verification

Server-side entity scope, active-master, Fund-policy, category, Program, financial-account, lifecycle, approval, evidence, idempotency, immutable journal/ledger, append-only audit, attachment-hash, and posted-record deletion guards were exercised. Replay/double-submit returns the existing official result only when its fingerprint matches; a changed fingerprint is rejected.

## 13. Failure Recovery

Failed posting stores diagnostic state without partial facts. The same key can be retried after correction where policy permits; duplicate completed requests remain one official Journal. A test-runner collision occurred during this phase when a terminal timeout left an earlier test process active and a second process attempted `RefreshDatabase` on the same test database. It was an execution-environment isolation incident, not a Financial V2 defect: the identified test processes were stopped, `mrj_test_db` was rebuilt with migrations, and all subsequent validation was run serially. No production connection was used.

## 14. Performance Smoke

| Measure | Result |
|---|---:|
| Transactions posted | 1,100 |
| JournalLines / LedgerEntries | 12,000 / 12,000 |
| 100-transaction checkpoint | 100 Journals / 200 LedgerEntries |
| Standalone UAT duration | 209.68 s |
| Integrated Financial V2 duration | 191.00 s |
| Final full baseline duration | 202.72 s |
| Data access observed | report queries read Posted General Ledger; no direct fact writer outside `PostingEngine` |

No arbitrary latency SLA has been introduced. The measured duration is a capacity baseline for an environment-specific follow-up; it is not a reason to bypass validations or prematurely optimize.

## 15. Defects Found

No Financial V2 accounting-integrity defect was found.

Two test-process issues were found and resolved:

1. The first new UAT assertion incorrectly addressed a non-existent `LedgerEntry.journal_id`; it was corrected to the real JournalLine relationship and rerun successfully.
2. Overlapping test processes caused a test-only `RefreshDatabase` table-drop collision. Serial execution and an explicit disposable-database rebuild resolved it.

## 16. Defects Fixed

- Added isolated UAT fixture and regression suite: `tests/Support/UatFinancialFixture.php` and `tests/Feature/FinancialV2/UatCutoverReadinessTest.php`.
- Corrected the UAT-only ledger relationship assertion.
- No production code, Financial V2 schema, legacy table, Posting Engine rule, or accounting policy was changed to make a test pass.

## 17. Remaining Risks

- Production UAT has not and must not be performed in this phase. Production master-data approval, named operational sign-off, real source mapping, signed opening-balance reconciliation, and cutover execution/rollback authority are still absent.
- The allocation overspend guard is validated with a sequenced overspend contender and the engine's `lockForUpdate` implementation. A separately orchestrated multi-session contention test remains advisable before a production cutover window.
- The 1,100-transaction smoke duration is a baseline only; production capacity targets and monitoring thresholds have not been approved.

## 18. Cutover Readiness Checklist

### Architecture and accounting

- [x] Architecture PASS (existing approved source of design)
- [x] Posting Engine PASS
- [x] Journal balanced
- [x] Ledger balanced and canonical
- [x] Trial Balance balanced

### Master data and controls — synthetic UAT

- [x] Financial Accounts, Funds, Programs, Categories, Posting Rules, and sequences configured in isolated fixture
- [x] Fund restrictions, voucher uniqueness, idempotency, audit trail, evidence, closing, and reconciliation

### Reporting and opening — synthetic UAT

- [x] Account Balance, Fund Balance, Cash Flow, Trial Balance, Friday, ZISWAF, Program reports
- [x] Opening Balance rehearsal and opening reconciliation

### Operator workflow — synthetic UAT

- [x] Receipt, payment, transfer, allocation, realization, history, detail, evidence, reports

### Required before any production cutover

- [ ] Production Financial Accounts configured and approved
- [ ] Production Funds/Programs/Categories/Posting Rules approved
- [ ] Named operator UAT and governance sign-off recorded
- [ ] Production-source mapping and signed opening-balance reconciliation approved
- [ ] Separate cutover date, execution plan, rollback plan, and authority approved
- [ ] Dedicated multi-session concurrency exercise accepted for the production environment

## 19. Final Recommendation

**Technical UAT result: PASS.**

Final validation passed: Financial V2 suite **70 passed / 392 assertions / 0 failed**, and the full application suite **96 passed / 461 assertions / 0 failed / 0 skipped / 1 pre-existing risky**. PHP lint, targeted Pint, schema migration/FK/index checks, canonical-writer audit, legacy-isolation scan, and `git diff --check` passed.

**CUTOVER READINESS = NOT READY.**

The status is intentionally **NOT READY** because the required production governance and source-data approvals have not been supplied and this phase was explicitly prohibited from performing production UAT, migration, opening balance, date selection, activation, legacy disablement, or dual-write. There is no unresolved accounting-integrity, ledger-balance, Fund-leakage, duplicate-posting, reconciliation, or period-bypass defect in the isolated UAT evidence.

Stop here. A separate, explicitly authorized production cutover execution phase is required before any production action.

## 20. Phase 8 multi-session/concurrency update

Phase 7's sequenced overspend result did not substitute for a real multi-process contention exercise. Phase 8 ran ten independent Laravel workers per scenario exclusively against disposable `APP_ENV=testing` / `mrj_test_db` and found three genuine MySQL snapshot/locking defects: stale normal reads for Journal sequence and balance checks, and idempotency gap-lock contention; high-concurrency lifecycle operations also required bounded retry of database-declared transient lock conflicts.

The correction retained the approved architecture: entity/allocation locks and existing unique constraints remain authoritative; sequence, liquidity, and realization reads are now locking/current reads; idempotency reservation is atomic via its existing unique index; retries occur only after a complete database rollback and do not weaken transaction isolation. No Financial V2 migration, accounting-policy change, direct Ledger writer, legacy table change, dual-write, cutover, or production action was introduced.

Final rerun result: receipts **10/10 posted**; duplicate post/retry **10 responses to one official Journal/Voucher**; same Fund/account payment race **7 posted / 3 `E-FUND-INSUFFICIENT`**; treasury transfer **10/10 posted**; realization **6 posted / 4 `E-BUDGET-INSUFFICIENT`**. The entity tied out to **34 Journals, 68 JournalLines, 68 LedgerEntries, 34 distinct Vouchers**, no missing/orphan Ledger facts, no non-posted Journal, a balanced Trial Balance, source Financial Account balance **160.00**, destination **250.00**, and allocation `100.00 / 90.00 actual / 10.00 available`.

This replaces the former "multi-session follow-up" risk with a PASS for disposable technical concurrency validation. It does not remove the production governance, master-data, opening-balance, backup/restore, authorization, or cutover-plan blockers; therefore production cutover remains **NOT READY**.
