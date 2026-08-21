# Financial Phase 3 — STEP 2 Database Foundation Validation Report

**Status:** Schema implementation complete; STEP 3 blocked by baseline-test gate  
**Date:** 8 August 2026  
**Approved architecture:** Option A — Parallel V2 Foundation with Governed Cutover

---

## 1. Scope executed

STEP 2 created the Accounting Foundation V2 as an additive database namespace. No Financial V2 migration alters, renames, deletes, or adds foreign keys to legacy financial tables.

| Area | Result |
|---|---|
| V2 entity-to-table mapping | Approved in [Database Foundation Design Gate](FINANCIAL-PHASE-3-STEP-2-DATABASE-FOUNDATION-DESIGN.md). |
| Namespace | All target tables use `financial_v2_*`. |
| Identity | All V2 primary keys and V2 financial foreign keys use UUID-compatible values. |
| Legacy boundary | No V2 migration reads, writes, renames, or reinterprets `jurnal`, `akun_keuangan`, `dana_*`, `saldo_*`, or `transaksis`. |
| Cutover date | Not created, seeded, or assumed. |
| Dual write | No application write path was added. |
| Operational modules | No model, repository, service, controller, UI, API, CRUD, or posting feature was created. |

## 2. Migration inventory

Nine migrations were added under `database/migrations/financial_v2`.

| Order | Migration scope |
|---:|---|
| 1 | Accounting entity/calendar/period, CoA, Fund, transaction, and rule masters. |
| 2 | FinancialAccount, bank/cash details, CostCenter, Program, Counterparty. |
| 3 | Posting rules/versions and voucher sequence. |
| 4 | Transaction, split, approval, attachments, idempotency, attempt, Voucher. |
| 5 | Journal, JournalLine, AuditEvent, attempt-to-journal linkage. |
| 6 | LedgerEntry, balance projection, trial-balance snapshot. |
| 7 | Exception, mapping, OpeningBalanceBatch/Line. |
| 8 | Closing and reconciliation schema reservations. |
| 9 | MySQL check constraints. |

All files passed PHP lint. Migration ran only through `--path=database/migrations/financial_v2`; all nine are recorded as run.

## 3. Schema verification

| Verification | Expected | Actual | Result |
|---|---:|---:|---|
| V2 tables | 46 | 46 | Pass |
| Foreign-key constraints on V2 tables | Explicit non-polymorphic relationships | 214 | Pass |
| Indexes | Master, voucher, idempotency, ledger, exception, reporting paths | 294 | Pass |
| Check constraints | Foundational value/date/one-sided-line checks | 23 | Pass |
| V2 data rows | No master/transaction/cutover data in STEP 2 | 0 estimated rows | Pass |
| Legacy table modification by V2 migrations | None | None | Pass |

Verified controls include:

- `fv2_jl_one_side_ck` and `fv2_open_line_one_side_ck` enforce one positive debit or credit side.
- `fv2_voucher_entity_number_uq` enforces V2 voucher uniqueness by AccountingEntity.
- `fv2_idempotency_scope_key_uq` protects one idempotency key per AccountingEntity/scope.
- `fv2_ledger_account_order_ix`, `fv2_ledger_fund_order_ix`, `fv2_ledger_fin_acc_order_ix`, and `fv2_ledger_program_order_ix` provide ordered ledger access by accounting dimension.

A safe live verification attempted an invalid fiscal start month inside a rolled-back transaction. MySQL rejected it and no verification row persisted.

## 4. Migration execution correction

The first V2 attempt exposed MySQL’s 64-character foreign-key-identifier limit. The failure left only partial new V2 tables and no recorded V2 migration row. Those exact V2 tables were inspected and removed; no legacy table was targeted. Explicit short foreign-key names were added.

A second attempt exposed the same limit on the TrialBalanceSnapshot period FK. Again, only three partial V2 tables from the unrecorded migration were removed. The corrected migrations then ran successfully. No approved business decision or legacy boundary changed.

## 5. Isolated rollback rehearsal

Rollback was rehearsed in a temporary database named `financial_v2_rehearsal_20260808`.

```text
Temporary database
  ↓ create minimum users table + all 9 V2 migrations
  ↓ verify: 46 V2 tables and 23 check constraints
  ↓ execute down() in reverse V2 order
  ↓ verify: 0 remaining V2 tables
  ↓ drop temporary database
```

The rehearsal passed. It did not use legacy financial tables and did not run against the shared production database.

## 6. Legacy migration observation

At STEP 1, migration `2026_06_26_143745_add_jenis_dana_to_dana_terikat_penerimaan_table` was reported pending. During STEP 2 validation it was observed as **Ran, batch 1**, and the `jenis_dana` column is now present in the shared legacy database.

This migration is not contained in the V2 migration directory and was not used as a V2 dependency. No rollback was performed, because legacy rollback would be destructive and outside Foundation scope. It must be reviewed as a separate legacy-runtime event; it has no V2 FK, data, or cutover dependency.

## 7. Baseline test result

`php artisan test` was executed after migration. It did not pass.

| Result | Detail |
|---|---|
| Financial test coverage | No financial test files currently exist. |
| Test suite | Stops with `Cannot redeclare console_debug()` in `routes/console.php:18`. |
| Other output | Existing non-financial Berita lookup messages appear before the fatal error. |
| V2 test impact | STEP 2 creates no models/services/tests, so no new financial test can run yet. |

## 8. STEP 2 gate decision

| Gate | Result |
|---|---|
| Design A–J review | Pass |
| V2 migration execution | Pass |
| Schema/FK/index/constraint verification | Pass |
| Isolated rollback rehearsal | Pass |
| Legacy preservation by V2 migration | Pass |
| Baseline test | **Fail — existing test-suite fatal error** |

**STEP 2 schema work is complete, but STEP 3 cannot start while the required baseline test fails.** No Step 3 master data, models, Posting Engine, or operational finance module has been started.

## 9. Required next decision

Before implementation proceeds, either:

1. authorise a narrowly scoped repair of the existing `routes/console.php` test blocker, followed by a repeated STEP 2 baseline test; or
2. explicitly accept this existing non-financial test failure as a temporary baseline exception with owner and remediation deadline.

Option 1 is recommended because every subsequent Financial V2 test needs a runnable test process.
