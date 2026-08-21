# Financial V2 Cutover Runbook

**Status:** controlled procedure only — **NOT EXECUTED**  
**Cutover date/window:** `[TBD — requires separate authorization]`  
**Decision authority:** `[CUTOVER_AUTHORITY]`

This runbook does not authorize or automatically execute any action. It preserves the approved parallel V2 foundation: legacy data is neither deleted nor reinterpreted as V2 facts, no dual-write is permitted, and official post-cutover balances come only from V2 Posting Engine and immutable Posted General Ledger.

## Roles and stop rule

| Role | Named person | Required decision |
|---|---|---|
| Cutover authority | `[TBD]` | GO/NO-GO and rollback decision |
| Finance owner | `[TBD]` | Opening balance/reconciliation acceptance |
| Technical owner | `[TBD]` | build, schema, backup, and operational validation |
| Legacy owner | `[TBD]` | final snapshot and approved legacy freeze |
| Independent verifier | `[TBD]` | Journal/Ledger/report tie-out |

At any failed gate, unexplained difference, ledger imbalance, duplicate-posting indication, missing backup, missing authorization, or unresolved P0/P1 issue: **STOP, declare NO-GO, preserve evidence, and escalate. Do not improvise a workaround.**

## Pre-cutover gates (all required)

1. Confirm written cutover authorization and window `[APPROVAL_REFERENCE]`.
2. Confirm **VERIFIED BACKUP/SNAPSHOT** plus tested restore evidence `[BACKUP_REFERENCE]`.
3. Confirm deployed application version/build `[BUILD_REFERENCE]`.
4. Run the read-only target `financial-v2:preflight` and attach output; all schema/migration/FK/index/constraint/service checks must pass.
5. Confirm approved target master data: Entity, Calendar/Period, CoA, Financial Accounts, Funds/Policies, Programs, Categories, Posting Rules, Voucher Sequences, Evidence configuration.
6. Confirm approved OpeningBalanceBatch/Line dataset, source evidence, frozen mapping set, and independent reconciliation.
7. Confirm every reconciliation lens has `0.00` unexplained difference.
8. Attach technical/UAT/reporting/closing/reconciliation/opening/performance/multi-session results.
9. Confirm legacy-freeze procedure, final snapshot format, read-only access policy, and named Legacy owner.
10. Confirm hypercare roster, P0/P1/P2 contact path, and rollback decision owner.

## Cutover sequence — HOLD until separately authorized

1. **HOLD:** record the approved start time; do not choose a date/window in this document.
2. Legacy owner freezes legacy financial input under the approved procedure; do not delete or alter legacy tables.
3. Capture immutable final legacy snapshot `[LEGACY_SNAPSHOT_REFERENCE]` and extraction hash `[HASH]`.
4. Finance and independent verifier reconcile final legacy position to the approved opening dataset. Any unexplained difference is NO-GO.
5. Technical owner confirms target schema/build again with the read-only preflight; do not run automatic migration.
6. Execute only the separately approved V2 migration/opening procedure. The opening position must be posted through `OpeningBalanceBatch`/`OpeningBalanceLine` and V2 Posting Engine, never by direct Journal/Ledger writes.
7. Verify opening Journal, JournalLine, immutable Posted General Ledger, voucher, and audit trail counts/references.
8. Verify Financial Account, Fund, Account, and Program opening positions; Trial Balance must balance.
9. Verify General Ledger, Trial Balance, Cash Flow, Fund, Financial Account, and operational reports against the approved reconciliation.
10. Run the approved operational smoke test for receipt, payment, transfer, realization, evidence, and reporting. There is no dual-write.
11. Cutover authority decides GO to post-cutover operation only after all evidence is signed.

## Post-cutover

1. Reconcile first V2 transactions and reports to the official V2 Posted General Ledger.
2. Perform scheduled Financial Account/cash reconciliation and Fund-policy exception review.
3. Review failed/retried postings, duplicate submission, voucher collision, ledger imbalance, unexpected balance, and report discrepancies.
4. Confirm legacy remains read-only historical archive according to the approved access policy.
5. Start first-day and first-week hypercare checklist; file evidence and incident records.

## Required evidence attachments

- Authorization, owner roster, and approved window
- Verified backup/snapshot and restore rehearsal
- Target preflight output and migration/schema verification
- Master-data snapshot and approvals
- Legacy final snapshot/hash and freeze record
- Opening balance batch/line/mapping/evidence/reconciliation approvals
- Journal/Ledger/report validation results
- Operational smoke-test and hypercare records
- Signed final GO/NO-GO decision
