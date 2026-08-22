# Financial V2 Current-State Seeder

## Purpose and safety boundary

The governed source is the current local `mrj_prod_db` Financial V2 state, exported only by `php artisan financial-v2:export-seed` while the application is in the local environment. The export is read-only against the source database and refuses to run in production.

`php artisan financial-v2:seed-local` is guarded: it refuses production and accepts only the known local source database or the testing target (`mrj_test_db`). Rehearsal and automated validation for this baseline use `mrj_test_db`; no reset, migration, or write was executed against `mrj_prod_db`.

The exporter refuses a baseline containing explicit `SAMPLE` or `QA` markers. It excludes sessions, queues, arbitrary audit-event noise, failed posting attempts, failed idempotency keys, and other ephemeral runtime state.

## Source snapshot

Generated from the current `mrj_prod_db` entity `MRJ-ACTUAL` on 22 August 2026:

| Dataset | Source rows |
| --- | ---: |
| Static master/configuration | 242 |
| Historical Fund History | 33 |
| Opening Balance lines | 13 |
| Posted facts eligible for replay | 3 |
| Allocation baselines | 2 |
| Realization baselines | 4 |

The source-controlled payload is [current_mrj_financial_v2_snapshot.php](database/seeders/FinancialV2/current_mrj_financial_v2_snapshot.php). It contains current master UUIDs, historical source UUIDs and lineage, policy/rule state, allocation and realization lifecycle state, opening-balance mapping/evidence references, and canonical replay instructions.

## Replay design

Direct upsert is limited to master/configuration rows and `financial_v2_historical_fund_histories`. It never targets Journal, JournalLine, Ledger, Voucher, posting attempt, or idempotency-key tables.

| Fact | Governed replay path |
| --- | --- |
| Opening Balance | `OpeningBalanceService` → Posting Engine |
| Posted Interfund Transfer | Lifecycle Service → Posting Engine |
| Allocation | `BudgetAllocationService` |
| Non-posted realization lifecycle | `FinancialTransactionLifecycleService` |

Posted financial facts therefore receive new technical IDs in a clean target database, while preserving stable business identity (cutover reference, source reference, voucher number, idempotency key, correlation, amounts, dimensions, and evidence metadata). This is intentional: copying persisted Journal/JournalLine/Ledger UUIDs would bypass the canonical writer.

During replay, the seeder temporarily makes the source policy version effective for the relevant historical accounting date. Final policy statuses are restored inside the same transaction before the seed completes. This is required because the current policy version is effective later than the historical Opening Balance and Interfund Transfer dates.

User foreign keys are not cloned: all `*_by_user_id` fields are set to `NULL` in a clean test target. This avoids assuming an application user/credential baseline while retaining business timestamps, source lineage, and financial semantics.

## Test-DB rehearsal result

First run on `mrj_test_db`:

| Control | Result |
| --- | ---: |
| Static/configuration + history + operational records created | 281 |
| Financial facts replayed | 3 |
| Journal / JournalLine / Ledger | 3 / 17 / 17 |
| Voucher | 3 |
| Allocation / Realization / Historical Fund History | 2 / 4 / 33 |
| Duplicate creations | 0 |

### Source-to-target semantic comparison

| Baseline concern | `mrj_prod_db` | `mrj_test_db` | Difference |
| --- | ---: | ---: | ---: |
| Funds / Financial Accounts / Programs / Categories | 11 / 10 / 12 / 31 | 11 / 10 / 12 / 31 | 0 |
| Historical Fund History | 33 | 33 | 0 |
| Opening Balance batch / lines | 1 / 13 | 1 / 13 | 0 |
| Transactions | 7 | 7 | 0 |
| Journal / JournalLine / Ledger / Voucher | 3 / 17 / 17 / 3 | 3 / 17 / 17 / 3 | 0 |
| Allocations / allocation versions | 2 / 2 | 2 / 2 | 0 |
| Fund realizations | 4 | 4 | 0 |

The test also verifies every exported master/configuration UUID and every historical Fund History UUID. Financial facts are compared through their governed business identities and report balances, rather than through generated Journal or Ledger UUIDs.

Second run is idempotent: no fact was replayed and no duplicate was created. The rerun reuses 9 existing semantic records (one Opening Balance, two posted Interfund Transfers, two allocations, and four realizations).

Semantic balance tie-out in the target matches the snapshot exactly:

| Fund | Balance |
| --- | ---: |
| Zakat Maal | 75,745,386.00 |
| Infaq & Tromol | 15,466,949.00 |
| Sodaqoh | 6,906,000.00 |
| Santunan Anak Yatim | 6,600,000.00 |
| Fidyah | 7,500,000.00 |
| Dhuafa | 13,511,977.00 |

| Financial account | Balance |
| --- | ---: |
| BNI ZISWAF | 123,077,312.00 |
| Cash ZISWAF | 2,653,000.00 |

## Regression coverage

[FinancialV2SeederTest.php](tests/Feature/FinancialV2/FinancialV2SeederTest.php) verifies a clean test seed, current master/configuration identity, historical Fund History, opening balance, allocation/realization lifecycle semantics, report tie-out, idempotency, voucher uniqueness, and absence of orphan Ledger entries.

The full suite completed with **134 tests, 7,841 assertions, 0 failures, 0 errors, and 0 skipped**. Targeted Pint, PHP lint, Blade cache, `git diff --check`, canonical-writer audit, and legacy-isolation audit also passed.

No legacy financial table is referenced or written by the exporter or seeder. No data in `mrj_prod_db` was deleted, reset, migrated, or seeded during this work.
