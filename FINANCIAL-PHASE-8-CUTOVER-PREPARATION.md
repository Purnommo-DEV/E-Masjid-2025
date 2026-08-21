# Financial Phase 8 — Cutover Preparation & Production Readiness

**Execution date:** 2026-08-09  
**Status:** **CUTOVER PREPARATION = PASS WITH BLOCKERS**  
**Scope:** preparation, disposable testing, read-only preflight implementation, and documentation only.

No production migration, production opening balance, production financial-data mutation, legacy freeze/disablement, dual-write, or cutover date selection was performed. The approved V2 architecture remains a parallel UUID foundation; legacy financial tables remain a migration source and historical archive.

## 1. Readiness summary

| Dimension | Status | Evidence / blocker |
|---|---|---|
| Technical readiness | PASS in disposable test environment | V2 schema, services, test isolation, canonical-writer and legacy-isolation checks; 10-worker rehearsal PASS |
| Data readiness | NOT VERIFIED | No approved production master-data snapshot, opening dataset, or signed reconciliation was supplied |
| Governance readiness | NOT VERIFIED | Cutover authority, responsible owners, and approval record are not yet supplied |
| Operational readiness | NOT VERIFIED | Production deployment version, support roster, and cutover window remain TBD |
| Rollback readiness | NOT VERIFIED | A verified production backup/snapshot and tested restore evidence are required |
| Production cutover | NO-GO | Every required production evidence item must be completed and approved first |

`PASS WITH BLOCKERS` means the preparation artifacts and disposable technical validation are complete. It is **not** a claim that the production target is ready.

## 2. Production preflight

Implemented read-only command:

```powershell
php artisan financial-v2:preflight --format=json
```

The checker verifies environment/database identity, migration records, required V2 tables, indexes, named constraints, boot-time migration/DDL markers, Posting Engine, Reporting, Closing, Reconciliation, Opening Balance service resolution, test isolation, legacy isolation, and observable master-data presence. It never invokes migrations, DDL, posting, opening-balance processing, freeze, or cutover.

Safety gates are fail-closed:

- Outside the expected target environment it refuses before database inspection.
- `--simulate` is permitted only with `APP_ENV=testing` and `DB_DATABASE=mrj_test_db`.
- The production command is for a separately authorized production session only; it must not be run from a local environment merely because its `.env` happens to contain a production database name.

Safe disposable simulation command:

```powershell
$env:APP_ENV='testing'
$env:DB_CONNECTION='mysql'
$env:DB_HOST='127.0.0.1'
$env:DB_PORT='3306'
$env:DB_DATABASE='mrj_test_db'
php artisan financial-v2:preflight --simulate --format=json
```

Simulation result after a clean test-database rebuild: technical schema/service/isolation checks PASS; production master data remains intentionally MISSING/NOT VERIFIED because no production dataset was queried or created.

### Production master-data checklist

This is deliberately a production-evidence checklist, not an inference from synthetic fixtures.

| Item | Status | Required evidence before GO |
|---|---|---|
| Accounting Entity | NOT VERIFIED | Approved entity identifier and active status snapshot |
| Calendar and open Period | NOT VERIFIED | Approved calendar/period snapshot and cutover-period confirmation |
| Chart of Accounts | NOT VERIFIED | Approved CoA/version and mapping sign-off |
| Financial Account / Rekening | NOT VERIFIED | Approved account list, custody/bank detail, and reconciliation owner |
| Fund | NOT VERIFIED | Approved Fund list; no Program-to-Fund assumption |
| Fund Policy | NOT VERIFIED | Effective policy/version and restriction-rule evidence per restricted Fund |
| Program | NOT VERIFIED | Approved independent Program list and effective dates |
| Category | NOT VERIFIED | Approved category/type mapping |
| Posting Rules | NOT VERIFIED | Effective rule versions with controlled approval evidence |
| Voucher Sequence | NOT VERIFIED | Approved sequence scope, prefix, next-value, and collision check |
| Evidence configuration | NOT VERIFIED | Required evidence matrix and retention owner |

If the authorized target preflight finds no eligible record, it will report **MISSING**. If a restricted active Fund lacks an effective policy, it will report **CONFLICT**. No status may be upgraded to READY without target evidence and governance approval.

## 3. Opening Balance and reconciliation procedure

Production opening balance is a future controlled operation only. It must use `OpeningBalanceBatch` and `OpeningBalanceLine` through the V2 Posting Engine; it must not reuse legacy `jurnal` as a V2 Journal or create a parallel balance source.

1. **Source evidence** — register the approved source extract `[SOURCE_REFERENCE]`, extraction timestamp `[TIMESTAMP]`, responsible owner `[OWNER]`, and immutable evidence package `[EVIDENCE_PACKAGE_REF]`.
2. **Mapping** — approve/freeze the legacy-to-V2 mapping set `[MAPPING_SET_ID]`. Map Account, Financial Account, Fund, and Program independently; do not map Program as Fund by assumption.
3. **Import/rehearsal** — load only a disposable rehearsal batch `[REHEARSAL_BATCH_ID]`, validate every source/evidence/mapping reference, and preserve legacy source references.
4. **Reconciliation** — resolve every explicit difference. The required target is `0.00` unexplained difference.
5. **Verification** — independently verify balances, dimensions, Journal/JournalLine/Ledger output, reports, and audit evidence.
6. **Approval** — obtain named finance owner `[FINANCE_OWNER]`, technical owner `[TECHNICAL_OWNER]`, and cutover authority `[CUTOVER_AUTHORITY]` approval.
7. **Future posting** — only an explicitly authorized production cutover may post the approved V2 opening batch. This phase did not do so.

| Reconciliation lens | Source position | V2 book position | Difference | Acceptance |
|---|---|---|---|---|
| Financial Account | `[SOURCE_BALANCE]` | `[V2_BOOK_BALANCE]` | `[DIFFERENCE]` | `0.00` unexplained difference |
| Fund | `[SOURCE_FUND_POSITION]` | `[V2_FUND_POSITION]` | `[DIFFERENCE]` | `0.00` unexplained difference |
| Account | `[SOURCE_ACCOUNT_MAPPING]` | `[V2_ACCOUNT_POSITION]` | `[DIFFERENCE]` | `0.00` unexplained difference |
| Program (where applicable) | `[SOURCE_PROGRAM_POSITION]` | `[V2_PROGRAM_POSITION]` | `[DIFFERENCE]` | explicit approved explanation or `0.00` |

Any unexplained difference is an immediate **NO-GO**.

## 4. Multi-session / concurrency validation

The disposable command `financial-v2:concurrency-rehearsal` starts ten independent Laravel worker processes per scenario against `testing/mrj_test_db`. It refuses any other environment/database and does not run migrations itself.

Final rerun result:

| Scenario | 10-worker result | Integrity verification |
|---|---:|---|
| Simultaneous receipt | 10 posted, 0 error | Unique Journal sequences and vouchers maintained |
| Duplicate post/retry | 10 responses, one official result | Exactly one Journal and one Voucher for the idempotent request |
| Same Fund/account payment race | 7 posted, 3 `E-FUND-INSUFFICIENT` | No negative liquidity race condition |
| Simultaneous treasury transfer | 10 posted, 0 error | Source/destination movement and Fund composition preserved |
| Simultaneous realization | 6 posted, 4 `E-BUDGET-INSUFFICIENT` | Allocation `100.00`, actual `90.00`, available `10.00` |

Final fact checks: 34 Journals, 68 JournalLines, 68 LedgerEntries, 34 distinct Vouchers, no missing/orphan LedgerEntry, no non-posted Journal, Trial Balance balanced, source Financial Account `160.00`, destination Financial Account `250.00`.

### Defect found, correction, and regression

The first multi-session execution correctly stopped on real concurrency defects:

1. a normal `MAX(posting_sequence)` read could use a pre-lock InnoDB snapshot and collide with the entity-local Journal sequence;
2. pre-insert `lockForUpdate` on a missing idempotency key created gap-lock deadlocks among different requests;
3. normal aggregate reads for Fund liquidity and realization availability could use an earlier snapshot, permitting stale-limit decisions;
4. high-contention lifecycle audit writes exposed transient InnoDB deadlocks that required a bounded all-or-nothing retry at the transaction boundary.

Corrections retain the approved data model and isolation rules: current locking reads are used after the existing entity/allocation locks, idempotency reservation is atomic through its existing unique index, and only database-declared transient lock conflicts retry after full rollback with bounded jitter. No schema change, accounting-policy change, direct ledger writer, dual-write, or weakened isolation was introduced. The final 10-worker rerun above is the regression evidence.

## 5. Go / No-Go matrix

| Required gate | Current status | Decision |
|---|---|---|
| Technical UAT and Financial V2 suite | PASS; Phase 8 final validation is recorded below | retain evidence |
| Target production schema verified | NOT VERIFIED | NO-GO until authorized preflight evidence exists |
| Master data verified/approved | NOT VERIFIED | NO-GO |
| Opening balance reconciled | NOT VERIFIED | NO-GO |
| Backup/snapshot verified | NOT VERIFIED | NO-GO |
| Cutover authorization and owners | NOT VERIFIED | NO-GO |
| 10-worker multi-session validation | PASS | technical gate satisfied |
| No unresolved critical defect | PASS in disposable validation | production evidence still required |
| Rollback capability verified | NOT VERIFIED | NO-GO |
| Legacy transition/freeze procedure approved | NOT VERIFIED | NO-GO |

## 6. Hypercare and incident response

### Hypercare checklist

**First day:** monitor failed/retried posting, duplicate submission, voucher collision, Journal/Ledger balance, Financial Account and Fund balance, reconciliation difference, restriction violation, and report discrepancy after every agreed checkpoint. Reconcile against the approved opening evidence and final legacy snapshot; record each exception.

**First week:** perform daily Trial Balance and Financial Account reconciliation, Fund-policy exception review, report tie-out, audit-event sampling, incident trend review, and named owner sign-off. No monitoring infrastructure was added in this phase; this is an operational checklist.

| Priority | Detection and containment | Investigation / correction | Verification and communication |
|---|---|---|---|
| P0 — financial integrity or data corruption | Stop affected financial input, preserve logs/evidence, prohibit manual ledger edits, escalate to cutover authority | Establish fact scope; use approved rollback decision path or governed correcting event only | Independent Journal/Ledger/reconciliation tie-out; notify finance, technical, and governance owners |
| P1 — posting failure or major transaction issue | Quarantine affected workflow/request and retain idempotency/correlation reference | Diagnose rule/master/evidence/lock issue; correct source controls, never patch posted facts | Replay only through approved idempotent/controlled path; confirm reports and audit trail |
| P2 — reporting discrepancy or UX issue | Record report parameters and screenshot/export; do not alter facts | Compare report query period/watermark/dimensions to Posted General Ledger | Re-run report after correction, document result, and communicate to report owner |

## 7. Legacy transition and preservation

Legacy transition is planned as **LEGACY FREEZE**, not deletion or automatic disablement:

- capture final legacy snapshot `[LEGACY_SNAPSHOT_REFERENCE]` only after authorized freeze;
- reconcile it to approved V2 opening evidence;
- preserve all legacy tables and documents as read-only historical archive after cutover;
- restrict legacy financial input according to the separately approved access policy `[LEGACY_ACCESS_POLICY]`;
- do not dual-write; after authorized cutover, only V2 Posting Engine and immutable Posted General Ledger become the official balance source.

No freeze, access change, deletion, or cutover was performed here.

## 8. Risk register and evidence package

| Risk | Current control | Required closure |
|---|---|---|
| Missing production master data | Preflight reports MISSING/NOT VERIFIED without creation | Approved target snapshot and governance sign-off |
| Opening-balance difference | Batch/line reconciliation is fail-closed | `0.00` unexplained difference with evidence package |
| Concurrency collision | 10-worker regression PASS after fixes | Include result in final cutover evidence; monitor hypercare |
| Missing recovery capability | Rollback runbook explicitly does not claim restore | Verified backup/snapshot and restore rehearsal |
| Legacy contamination / dual-write | V2 source scan and policy boundary | Approved freeze/read-only transition and access audit |
| Incorrect cutover authorization/date | No date or execution selected | Named authority, approved plan, and separately authorized execution window |

Required evidence package before GO:

- [ ] Target migration output and schema/FK/index/constraint verification
- [ ] Deployed application version/build reference
- [ ] Approved master-data snapshot and policy evidence
- [ ] Opening-balance dataset, mapping set, line evidence, and signed reconciliation
- [ ] Technical, UAT, reporting, closing, reconciliation, opening, performance, and concurrency results
- [ ] Verified backup/snapshot and restore rehearsal result
- [ ] Signed authorization, owner roster, cutover checklist, and rollback decision owner
- [ ] Final legacy snapshot/freeze record
- [ ] Post-cutover reconciliation and hypercare records

## 9. Safety observation

The local configuration was found to name `mrj_prod_db`. During early checker implementation, an initial metadata/master-read probe used that configured connection; it performed no migration, DDL, posting, opening-balance action, freeze, deletion, or data mutation. The access was stopped immediately. The implemented checker now refuses an unexpected environment before any database query, and all subsequent active validation used only `testing/mrj_test_db`.

## 10. Final validation evidence

All active validation below was run serially with `APP_ENV=testing`, `DB_CONNECTION=mysql`, and `DB_DATABASE=mrj_test_db`.

| Validation | Final result |
|---|---|
| Full application baseline — `php artisan test` | **98 passed, 476 assertions, 0 failed, 0 skipped, 1 pre-existing risky**, 444.06 s |
| Financial V2 suite — `php artisan test tests/Feature/FinancialV2` | **72 passed, 407 assertions, 0 failed, 0 skipped**, 274.35 s |
| Focused Posting/operational/closing/opening/preflight regression | **59 passed, 243 assertions, 0 failed**, 52.2 s |
| UAT performance smoke within the final runs | 1,100 transactions; 12,000 JournalLines and LedgerEntries; Trial Balance balanced; 208.92 s in final full baseline and 218.80 s in V2-only run |
| Multi-session concurrency command | PASS: all five 10-worker scenarios and fact tie-out in section 4 |
| Read-only preflight simulation | Technical PASS; 15 V2 migrations, required tables/indexes/constraints/services/test isolation/legacy isolation PASS; master data MISSING in clean test DB, therefore overall NOT READY as designed |
| PHP lint / Pint | PASS for all Phase 8 PHP files |
| Canonical writer audit | `Journal::create`, `JournalLine::create`, and `LedgerEntry::create` found only in `PostingEngine` under V2 runtime source |
| Legacy isolation audit | PASS: no V2 runtime legacy financial table/model access marker; preflight's inert search-marker literals excluded from the scan |
| Boot migration audit | PASS: no `Artisan::call`, `Schema`, `DB::statement`, or `Migrator` marker in application providers |
| Git whitespace check | `git diff --check` PASS |

The single risky test is pre-existing in the application baseline; Phase 8 introduced no new risky or skipped test. The preflight simulation deliberately finds no eligible master data after the clean test-run lifecycle; it does not invent records or promote a synthetic fixture to production evidence.

## 11. Next gate

**Do not execute cutover.** The next work is an explicitly authorized production-readiness review: run the read-only target preflight in the approved production session, collect the missing evidence, conduct the approved opening-balance rehearsal/reconciliation, verify backup/restore, obtain named approvals, and then request a separate cutover-execution authorization.
