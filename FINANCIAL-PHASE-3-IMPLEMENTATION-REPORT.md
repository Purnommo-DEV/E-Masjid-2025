# Financial Phase 3 - Implementation Report

## Status

**CORE ACCOUNTING FOUNDATION: PASS (local development).**

**OPERATIONAL FOUNDATION: PASS (local development).**

**GOVERNED MASTER DATA ONBOARDING: PASS WITH GOVERNANCE GAPS.** No actual business master was seeded because the project contains only legacy candidates and policy/design requirements, not the approved reference-data package required by the Foundation. Financial V2 remains inactive for cutover and contains no operational master values, opening-balance values, or financial facts. Its local-development schema, canonical posting controls, governed operational domain, and isolated automated-test baseline are verified. The earlier database event is classified as a local development database incident; recovery is explicitly not required.

**OPERATING UX FOUNDATION: PASS (local development / test database).** The Financial V2 operational UX is available under the isolated `/admin/keuangan-v2` namespace. It is a mobile-first, Indonesian-language adapter over the existing Lifecycle Service and canonical PostingEngine; it does not reuse legacy financial controllers, balances, URLs, journals, or tables. No business master, historical balance, opening balance, cutover, or actual local-development transaction was created by this UX implementation.

**PHASE 5 AMENDMENT - CLOSING & RECONCILIATION FOUNDATION: PASS (local development / test database).** The narrowly scoped Program-dimension defect is corrected against the approved Program `start_date` / `end_date` lifecycle fields without changing Program meaning. Financial V2 now has controlled Soft/Hard period close, immutable ClosingRun and reconciliation controls, exact Ledger-only bank/cash reconciliation, evidence/audit, and a minimal isolated control UX. This amendment does not create an Opening Balance, historical migration, cutover, production activation, dual-write, or legacy dependency. Details and validation are recorded in `FINANCIAL-PHASE-5-CLOSING-RECONCILIATION.md`.

## Autonomous decision log

| ID | Category | Decision | Reason / source | Impact | Reversible | Status |
|---|---|---|---|---|---|---|
| D-IMP-001 | APPROVED POLICY | Run Financial V2 migrations only against local development MySQL / `mrj_prod_db`; tests use only `mrj_test_db`. | Explicit resume authorization defines `mrj_prod_db` as local development and preserves the separate test boundary. | Local schema may be recreated; no external production database is in scope. | Yes, while no operational facts exist. | Complete |
| D-IMP-002 | IMPLEMENTATION DECISION | Add versioned rule-line, Fund policy matrix, approval, and evidence requirement tables. | Foundation references were insufficient for deterministic execution. | Enables data-driven rule and restriction evaluation; no legacy link. | Yes, before financial facts. | Complete |
| D-IMP-003 | ASSUMPTION | Restricted, perpetual-restricted, custodial, and syariah Funds fail closed unless an effective `allowed` policy rule matches; a matching `prohibited` rule has precedence. | Accounting Policy BR-017 to BR-030; autonomous instruction. | Blocks uncertain restricted postings. | Yes, through approved policy data. | Complete |
| D-IMP-004 | ARCHITECTURE DERIVED | Posting sequence is serialized by a locked AccountingEntity; voucher sequence is locked per active DocumentSequence. | Accounting Foundation AR-04, AR-07; AC-045, AC-100. | Deterministic sequence and duplicate prevention. | N/A for posted facts. | Complete |
| D-IMP-005 | GOVERNANCE BOUNDARY | No operational master records or opening-balance values are seeded. | Fund, account, financial-account, program, and opening values require governed onboarding; test fixtures are transactional. | V2 is structurally ready but inactive until approved master data is entered. | Yes. | Intentional, non-blocking |
| D-IMP-006 | LOCAL DEVELOPMENT INCIDENT | Historical legacy data is not recovered. | Resume authorization confirms `mrj_prod_db` is a local development database and explicitly prohibits recovery/restore work. | Legacy is architecture reference only; opening balance is a future explicit V2 batch process. | N/A | Closed - no recovery required |
| D-IMP-007 | IMPLEMENTATION DECISION | Add exact decimal arithmetic, canonical-writer guard, journal-per-transaction uniqueness, and failed-posting diagnostics. | Accounting Foundation AC-043 to AC-050, AC-097 to AC-103 require precise, immutable, traceable posting controls. | Eliminates float precision risk, blocks normal application writes outside PostingEngine, and retains failed retry evidence without creating official facts. | Yes before operational posting. | Complete |
| D-IMP-008 | ARCHITECTURE DERIVED | Add typed V2 operational extensions for TreasuryTransfer, InterfundTransfer, BudgetAllocation/Version, and FundRealization. | Financial Architecture V2 sections 6.15–6.16 and 12; Accounting Policy BR-035, BR-045–046, BR-073–075. | Keeps account transfer, interfund ownership change, budget planning, and actual realization semantically distinct. | Yes before operational facts. | Complete |
| D-IMP-009 | IMPLEMENTATION DECISION | Extend rule-line source vocabulary with source/destination FinancialAccount and source/destination Fund roles. | Approved PostingRuleVersion must deterministically resolve all dimensions; TRF and IFT require distinct dimensions. | No hardcoded Account/Fund IDs; the approved rule data still controls the Accounts and dimensions used. | Yes through migration/rule configuration before facts. | Complete |
| D-IMP-010 | IMPLEMENTATION DECISION | Enforce V2 source-transaction idempotency by AccountingEntity plus idempotency key. | BR-052–054; AC-097–101. | A business event cannot be created as duplicate V2 drafts; request-posting idempotency remains a separate control. | Yes before operational data. | Complete |
| D-IMP-011 | GOVERNANCE DECISION | Do not seed any Financial V2 business master from legacy seeders, UI configuration, policy examples, or test fixtures. | Governed Master Data Onboarding inventory; Blueprint requires signed registers, owners, effective dates, and approval evidence. | Preserves Fund/Account/FinancialAccount/Program separation and prevents an assumed operating configuration. | Yes; approved values may be onboarded in a future governed run. | Complete — gaps recorded |
| D-IMP-012 | UX IMPLEMENTATION | Add a separate Financial V2 operational workspace, source-transaction idempotency keys generated by the UI, and server-side resolution of the required split Account from the effective PostingRuleVersion. | Current V2 source-transaction contract requires a split Account, while operational users must not choose debit/credit or an Account. | The user submits only operational dimensions; the controller validates scope and resolves the internal split Account without hardcoded IDs. Official facts still use PostingEngine only. | Yes; configuration remains data driven. | Complete |
| D-IMP-013 | UX BOUNDARY | Display allocation evidence limitation instead of inventing an AttachmentLink target. | Attachment taxonomy currently permits transaction, journal, opening-balance-line, exception, and reconciliation targets only; BudgetAllocation is deliberately non-journal. | Prevents an unapproved Foundation/schema change or a false transaction evidence link. | Yes; can be extended only by a separately approved attachment-taxonomy decision. | Complete with recorded gap |

## Core Accounting Foundation implementation

- Eleven V2 migrations recreate successfully from an empty local development database. Migration 11 adds the unique `Journal.transaction_id` constraint, so one source transaction can create one canonical Journal only.
- UUID Eloquent models cover core masters, transactions, Journal, JournalLine, Ledger, audit, opening balance, rule controls, projections, and closing/reconciliation foundations.
- `PostingEngine` is the sole application writer for V2 Journal, JournalLine, and LedgerEntry. Runtime guard rejects normal Eloquent creation outside the engine; posted facts remain immutable and append-only.
- Amount validation, signed ledger impact, Fund liquidity checks, and balance formatting use fixed two-decimal string arithmetic rather than PHP float.
- The engine validates balanced journals, effective rule versions, active dimensions, FinancialAccount detail compatibility, Fund restrictions, Fund liquidity, approval/evidence, idempotency, voucher sequencing, period eligibility, adjustments, reversals, and approved opening-balance batches.
- Failed normal postings create no Journal, Voucher, JournalLine, LedgerEntry, or balance impact, but retain a failed PostingAttempt, failure code, audit event, and retryable failed idempotency key.
- `BalanceInquiryService` derives Account and FinancialAccount balances plus Fund liquidity distribution from Posted V2 Ledger only. Projections remain rebuildable caches.
- No V2 code reads legacy financial tables, performs dual-write, or selects a cutover date. No opening-balance amount is fabricated.

## Operational foundation implementation

- Governed master services activate Fund, FinancialAccount, Program, PostingRuleVersion, and FundPolicyVersion only after scope, effective-date, compatible-detail, and policy checks. Restricted Fund activation requires an effective policy version and allowed-matrix reference.
- `FinancialTransactionLifecycleService` creates and audits Draft → Submitted → Verified → Approved source transactions, records approval decisions, blocks ordinary direct updates, and delegates every official effect to `PostingEngine`.
- Receipt and Payment inputs are typed through the transaction domain. Their Fund, Rekening, Account, Program/Category, Counterparty, source-reference, and idempotency inputs stay distinct; no magic Account, Fund, Program, or FinancialAccount ID is embedded.
- Treasury Transfer uses `financial_v2_treasury_transfers`, mandates distinct source/destination FinancialAccounts, and posts only a debit to destination liquidity plus a credit to source liquidity for every identical Fund split. It rejects revenue/expense effects.
- Interfund Transfer uses `financial_v2_interfund_transfers`, requires Fund source, Fund destination, reason, and policy basis, posts configured transfer-in/out Accounts without FinancialAccount movement, and evaluates restricted Funds fail-closed through FundPolicyVersion/Rule.
- Budget Allocation uses versioned `financial_v2_budget_allocations` and `financial_v2_budget_allocation_versions`. It has no PostingEngine dependency and creates no Journal, Ledger, or stored Fund balance. Revision creates a new version; approved history is retained.
- Fund Realization is a non-financial link to one Payment transaction. It stores no amount and creates no second Journal; actual/available budget is derived from the linked posted transaction and Journal. The PostingEngine locks the AccountingEntity, allocation version, source transaction, period, and idempotency scope before validating available allocation and Fund liquidity.
- `EvidenceService` accepts only PDF and policy-permitted image MIME types, validates immutable content hashes/storage metadata, retains evidence links, and audits link/supersession actions. Attachments remain evidence, never accounting facts.
- Successful restricted-Fund lines retain the exact effective FundPolicyVersion ID; posted Journal already retains the exact PostingRuleVersion. Reversal now marks source transaction lineage and emits a distinct `reversal_committed` audit event.

## Operational UX and workflow implementation

- `app/Http/Controllers/FinancialV2/OperationalFinancialController.php` exposes an authenticated, standalone `/admin/keuangan-v2` workspace. Its route namespace is deliberately separate from every legacy `/admin/keuangan/*` endpoint. It has no dependency on a legacy balance or journal.
- The dashboard presents Kas/Rekening balance, period movement per Kas/Rekening, Fund balance with unrestricted/restricted classification, monthly receipt/payment/transfer activity, and recent operational transactions. All balance and movement values are read from posted V2 Ledger through `BalanceInquiryService`; transfer is never included as income or expense.
- Receipt, Payment, Treasury Transfer, Interfund Transfer, and Fund Realization forms use operational vocabulary only: date, amount, Kas/Rekening, Fund, Program, category, source/payee, description, policy reference, and evidence. Debit, credit, JournalLine, Ledger, and Account selection are not user inputs.
- The controller scopes every submitted UUID to the active AccountingEntity; checks active master state and category/type compatibility; derives an internal split Account from the effective PostingRuleVersion; and delegates source creation/update/status transitions to `FinancialTransactionLifecycleService`. The only official-post action delegates to that service, which in turn calls canonical `PostingEngine`.
- Draft submission tokens form a server-side source idempotency key. A repeated submit returns the prior transaction rather than creating a second draft. The official-post request uses a stable transaction-scoped idempotency key; a second click after `Posted` returns the existing official result and never creates a second Journal.
- Draft Receipt, Payment, and Realization may be edited. Posted transactions show an immutable explanation and a correction/reversal direction; posted facts are not deleted. Other draft transfers remain cancellable and are intentionally recreated rather than silently mutating their governed source/destination detail.
- The official-post action advances Draft → Submitted → Verified → Approved only where the configured lifecycle permits it. If approval requirements exist, it stops with the human-readable waiting-for-approval state rather than bypassing them. `verified` is shown to users as “Dalam pemeriksaan.”
- Evidence upload accepts JPG/JPEG, PNG, and PDF up to 10 MB, stores a hashed immutable V2 attachment through `EvidenceService`, and exposes inline view/download links. Evidence remains separate from accounting facts. Allocation displays its current attachment-taxonomy boundary rather than fabricating a target.
- History provides date period, type, Kas/Rekening, Fund, Program, category, status, and text filters. Transaction detail defaults to user language and places Journal ID, PostingRuleVersion, Accounts, debit/credit, and Ledger reference behind an advanced collapsible section.
- Forms are single-column on mobile, have large numeric/date controls, inline human-readable validation, and progressive AJAX for form submission/double-click prevention plus server-side Fund-combination preview. The server remains the security and accounting boundary.

## Governed Master Data onboarding

- `GOVERNED-MASTER-DATA-INVENTORY.md` records the audited source boundary, the empty V2-development snapshot, candidate data, duplicate/conflict risks, and the accountable approval package required before a business master can be created.
- `FINANCIAL-GOVERNED-MASTER-DATA.md` records the empty CoA, FinancialAccount, Fund, Program, Category, Posting Rule, Sequence, Approval, and Evidence registers as an explicit `PASS WITH GOVERNANCE GAPS` outcome.
- The legacy `AkunKeuanganSeeder` contains 41 legacy-account candidates, but it mixes account, liquidity-location, and Fund-like labels. It is therefore not a V2 CoA mapping or FinancialAccount/Fund register.
- A Qurban configuration contains a sensitive bank reference, but it does not establish a verified masjid FinancialAccount, custodian, entity owner, status, or effective date. No sensitive number is copied to V2 or this report.
- Legacy `ZISWAF-2026` references, cash-box/category candidates, and counterparties are not reinterpreted. The audited local legacy tables contain no corresponding live records for the reviewed finance masters.
- No Journal, JournalLine, LedgerEntry, FinancialTransaction, Receipt, Payment, Transfer, Allocation actual, Realization actual, OpeningBalanceBatch, or OpeningBalanceLine was created by this onboarding run. No cutover date was selected.

### Master-data register index

| Required register | Onboarding result |
|---|---|
| Master Inventory | Completed in `GOVERNED-MASTER-DATA-INVENTORY.md`. |
| CoA Register / Financial Account Register | Empty; the legacy candidates lack an approved V2 mapping and verified account register. |
| Fund Register / Fund Policy Matrix | Empty; Fund classifications and executable policy decisions require approval. |
| Program Register / Category Register | Empty; source candidates cannot be mapped without owner, effective date, and dimensional classification. |
| Posting Rule Catalogue / Voucher Sequence | Empty; policy examples are not an approved entity-specific catalogue. |
| Approval Matrix / Evidence Matrix | Empty; thresholds, retention, supersession, and policy-owner approval are absent. |
| Governance Gaps | Recorded as GMD-01 through GMD-08 in `FINANCIAL-GOVERNED-MASTER-DATA.md`. |
| Test Results | 39 Financial V2 tests / 182 assertions; full suite 63 passed / 243 assertions. |

Read-only final development-schema verification found 13 V2 migrations, 55 V2 tables, 266 foreign keys, 27 CHECK constraints, all six required master/control indexes, and the four operational CHECK constraints. It also confirmed zero V2 masters and zero V2 official facts in `mrj_prod_db`.

## Test isolation status

- PHPUnit now explicitly targets MySQL database `mrj_test_db` and emits a non-secret preflight line before test execution.
- Bootstrap and Laravel TestCase guards reject `APP_ENV` other than `testing`, a non-MySQL connection, a database other than `mrj_test_db`, and the forbidden `mrj_prod_db` before `RefreshDatabase` can invoke `migrate:fresh`.
- Targeted Core Accounting validation passed: 23 tests, 84 assertions. It covers journal/ledger posting, exact high-value decimals, canonical-writer protection, failed-attempt diagnostics/retry, balance, Fund policy, idempotency, reversal/audit, adjustment, period control, and opening balance.
- Operational Foundation integration validation passed: 9 tests, 44 assertions. It covers master uniqueness/governance, receipt lifecycle/audit/idempotency/voucher uniqueness, inactive dimensions, payment restriction/liquidity/period controls, Treasury Transfer, Interfund Transfer fail-closed policy, Budget Allocation, Fund Realization, attachment MIME/integrity, and immutability.
- Governed Master Data onboarding validation passed: 2 tests, 13 assertions. It proves master-only Fund activation and duplicate sequence rejection do not create financial facts, and overlapping Fund policy effective dates are rejected.
- Operational UX feature validation passed: 5 tests, 41 assertions. It covers idempotent Receipt draft/create/edit/evidence/posting, human-readable restricted-Fund rejection with no financial fact, Treasury Transfer no-income/no-expense effect, non-journal Budget Allocation, and dashboard/history V2-only rendering.
- Combined Financial V2 validation passed: 39 tests, 182 assertions.
- Full suite passed: 63 tests, 243 assertions, 0 failed, 0 skipped, with 1 pre-existing risky ExampleTest output-buffer warning and no new risky tests.
- The current test preflight confirms `APP_ENV=testing`, `DB_CONNECTION=mysql`, and `DB_DATABASE=mrj_test_db` before test execution.
- Test migrations rebuilt the disposable test database with 13 V2 migrations. Local development schema verification confirms 13 V2 migrations, 55 V2 tables, 266 foreign keys, 27 CHECK constraints, 37 operational indexes, Journal transaction uniqueness, and source-transaction idempotency uniqueness.
- Operational migrations 12–13 passed a rollback/reapply rehearsal on local `mrj_prod_db`. The rehearsal drops/recreates only the new V2 operational structures and never touches legacy financial tables.

## Local development database incident

The earlier schema rebuild affected the local development database `mrj_prod_db`.
Per the explicit resume decision, lost legacy data is not recovered, restored,
or reconstructed from binary logs. No backup wait, recovery rehearsal, or
forensic recovery is planned. The development schema was intentionally recreated
for this foundation validation.

## Legacy isolation

Legacy tables are historical architecture reference only. Financial V2 does not
read them for an official balance, does not use them as a posting source, does
not dual-write, and does not depend on them for tests. Any future opening
position must be entered through an explicitly approved OpeningBalanceBatch and
OpeningBalanceLine with evidence, mapping reference, reconciliation, and
canonical journal/ledger impact.

The Operational UX controller and its views/tests do not import legacy financial
controllers or query legacy financial models/tables. The workspace has its own
V2 route namespace and layout so rendering it cannot read a legacy sidebar
balance badge.

## Acceptance criteria coverage

| Area | Verified coverage |
|---|---|
| Master governance | Entity-scoped uniqueness, active/effective lifecycle checks, compatible FinancialAccount custody detail, Fund policy activation, deterministic rule-version line configuration. |
| Master onboarding isolation | Candidate business data is blocked without owner/effective-date/approval evidence; master-only governance cannot create financial facts; Fund policy effective ranges cannot overlap. |
| Transaction lifecycle | Draft/update audit, Submitted, Verified, Approved, rejected/cancelled guards, source idempotency, immutable Posted/Reversed source facts. |
| Receipt and Payment | Canonical posting, source/voucher duplicate prevention, active master validation, Fund policy fail-closed behavior, Fund liquidity/available budget/period controls. |
| Transfer and Interfund | Distinct account vs Fund movement, balanced/deterministic rule lines, no income/expense on TRF, separate IFT policy/reason/evidence path. |
| Allocation and Realization | Allocation is non-posting/versioned; actual is derived from linked posted Payment and blocked if it exceeds available allocation. |
| Evidence and audit | PDF/image MIME guard, hash/storage metadata, append-only audit events for draft mutation, posting request/commit/failure, evidence, and reversal. |
| Accounting truth | Journal/JournalLine/Ledger writes remain only in PostingEngine; ledger stays the only official balance source. |
| Operational UX | Dashboard, receipt, payment, Treasury Transfer, optional Interfund Transfer, allocation, realization, history, detail, evidence, status, mobile layout, AJAX/idempotency, and human error responses are isolated V2 workflows. |

## Risks and assumptions

- No real master data, approval matrix, Fund policy matrix, operational evidence, or opening balance has been seeded. These require governed onboarding from approved business data; test fixtures are disposable only.
- The following governance artefacts are missing: accounting-entity/fiscal-calendar charter; signed V2 CoA and AccountGroup mapping; verified FinancialAccount register; Fund register and policy matrix; Program/CostCenter/Category registers; posting-rule and sequence catalogue; approval matrix; and evidence-retention/supersession matrix.
- `mrj_prod_db` is treated solely as the explicitly authorized local development database. No recovery, restore, legacy migration, production reconciliation, or external production operation was performed.
- Posting serializes the AccountingEntity in the canonical commitment to protect voucher, idempotency, period, and balance-sensitive rules. This is safe for the current operational foundation; throughput profiling with representative production volume remains a later performance gate.
- Cutover date, official-ledger activation, actual opening balance, historical migration, dashboard/reporting, closing/reconciliation UI, and production reconciliation remain out of scope.
- Allocation evidence has a deliberate Foundation taxonomy gap: `AttachmentLink` has no `budget_allocation` target. The UX does not misuse another target type; a future change requires an approved Foundation decision.
- Live browser visual inspection could not bind the local development server in this desktop environment (`127.0.0.1:8018` refused the listener). Blade compilation, Vite production build, and authenticated HTTP Feature Tests passed. This is an environment limitation, not a Financial V2 runtime failure.
- `php artisan route:list` exposes a pre-existing, non-Financial guest-controller PSR-4 filename/namespace mismatch for `DokumentasiEvaluasiController`. It was not changed because it is outside this Financial V2 scope; Financial V2 HTTP routes and the complete test suite load and pass.

## Required next gate

**OPERATING UX FOUNDATION: PASS.** Stop here. The next implementation gate must
be separately authorized and must not start Closing, Reconciliation, final
reports, actual Opening Balance, Historical Migration, or Cutover. Governed
business masters still require the approved reference-data package listed in
`GOVERNED-MASTER-DATA-INVENTORY.md` before real operational use.
