# Phase 12 — Operational Master & Fund Policy

**Status: READY WITH GOVERNANCE GAP — all automated Financial V2 gates pass; the final authenticated browser recheck awaits local credential-entry confirmation.**

## Scope and invariant

Phase 12 adds governed operational configuration and read-only reporting projections. It does not redesign the Posting Engine, create an operational financial fact, or write legacy tables. The canonical path remains:

`Operational transaction → Lifecycle → Posting Engine → Journal → JournalLine → Posted Ledger → Report`

`ALC` is the existing non-financial allocation lifecycle. `REAL` is the existing realized `PAY` transaction linked by `FundRealization`; neither is a second transaction type or journal path.

## Actual ZISWAF preservation

The approved 27 June 2026 opening position is unchanged: one `OPB` transaction, one posted Journal, 13 JournalLines, 13 immutable Ledger entries, BNI ZISWAF Rp123.077.312, Cash Tromol Yatim Rp2.653.000, and six Fund balances totaling Rp125.730.312. The Phase 12 command snapshots these facts before and after master provisioning and rejects completion if they differ.

The source lineage for pre-V2 movements is displayed as a read-only **Riwayat Penggunaan Dana** alongside the V2 opening position. In particular, Sodaqoh exposes the workbook lineage of Rp8.506.000 receipt and Rp1.600.000 Beras 20 Pack usage, ending Rp6.906.000; it does not replay those source movements as new V2 facts.

## Operational master configuration

The idempotent local-only command is:

`php artisan financial-v2:provision-mrj-operational-master`

It is guarded to `APP_ENV=local` and `mrj_prod_db`; tests require `--allow-testing` and `mrj_test_db`. It provisions no balances and no sample transaction.

- Financial accounts: existing BNI ZISWAF and Cash Tromol Yatim are retained; BSI MRJ TCE, Mandiri ZISWAF, BCA Sewa Aula, Bank Qurban, Bank Sosial/Kematian, Cash Operasional, Cash Qurban, and Cash Sosial are active configuration records without invented account numbers or balances.
- Funds: actual Zakat Maal, Infaq & Tromol, Sodaqoh, Santunan Anak Yatim, Fidyah, and Dhuafa are retained; operational, Qurban, Ramadhan, Sosial/Kematian, and Sewa Aula are active zero-fact master records.
- Programs: the 12 Phase 12 operational programs are active.
- Categories: 10 receipt and 21 payment categories are active and linked to the correct standard posting-rule family.
- Voucher sequences and effective RCV, PAY, TRF, and IFT posting rules are configured. `ADJ` remains intentionally fail-closed without an ordinary operational posting rule.

## Fund policy

New Fund-policy versions supersede a dated predecessor without editing it; the predecessor is retained as `superseded`, has an effective-to date the day before the successor, and receives an audit event. This repairs the previously missing governed-successor path while retaining effective-version immutability.

Restricted Funds are fail-closed except for explicit matrix combinations. Examples configured from the Phase 12 mandate are:

- Zakat Maal receipt by Zakat category is allowed; Operational Masjid payment is prohibited.
- Santunan Anak Yatim receipt by donation and monthly Santunan payment are allowed.
- Qurban receipt, purchasing, operational, and distribution combinations are constrained to Program Qurban.
- Treasury transfer is allowed as an internal movement and cannot alter a Fund balance.

Unclear uses remain rejected instead of being inferred.

## Fund and allocation history

`FundHistoryReadService` groups immutable Posted Ledger by Fund and Journal, preventing debit/credit duplicates in a user-facing history. It provides date, type, description, receipts, usage, transfer, running Fund balance, filters, pagination, and a separate read-only source-history projection where an approved opening source exists. It never reads legacy transactions.

`AllocationHistoryReadService` uses allocation/version records plus recorded, posted `FundRealization` links. It reports allocation, realization, and remaining amount without treating allocation as expense or maintaining a second balance.

New UI paths:

- `Keuangan → Dana → Detail Dana`: Fund summary, account composition, read-only historical Fund usage, ledger-backed V2 transaction history, and separate allocation/realization history.
- `Keuangan → Alokasi Dana → Riwayat`: filterable allocation and linked posted-realization history.

## Phase 12.1 — final ZISWAF allocation-source audit

`ZISWAF UPDATE 3.xlsx` was read across all seven worksheets. Its SHA-256 is `404FC8CD54ECD3E35E17C30FFE6A3D88DF6656260CBEAC8F614EF99689A02F9C`.

The phrase **“Sisa Alokasi Dana”** appears in the source as a closing Fund-position report. It is not evidence of an approved budget/peruntukan event, operational allocation, realization, or a Program link. The reviewed source contains receipts, historical usages, reconciliation and closing balances, but no allocation event with the required date, Fund, purpose/Program, amount, and source approval/intent.

Therefore the correct import result is **zero Allocation records**. No historical expense was converted to an allocation, no source movement was replayed through the Posting Engine, and no Journal, JournalLine, Ledger, Voucher, Fund balance, or liquidity balance was changed. The existing Allocation History page now makes this decision visible for MRJ Actual, includes source filename/hash and a date/Fund/Program/status filter, and explicitly states that Fund History, Allocation History, and Realization History are distinct.

The historical source details remain solely as pre-V2 lineage explaining the immutable 27 June 2026 opening position:

- Sodaqoh: receipt Rp8.506.000 → usage Beras 20 Pack Rp1.600.000 → closing Rp6.906.000.
- Zakat Maal: receipt Rp97.145.386 → uses Rp10.700.000 and Rp10.700.000 → closing Rp75.745.386.
- Dhuafa: opening Rp20.378.977 → uses Rp5.530.000, Rp1.095.000, and Rp4.095.000 → closing Rp9.658.977.

Those are source explanations, not additional V2 financial facts. Cash Tromol also remains contained in the opening position: BNI Rp123.077.312 plus Cash Rp2.653.000 equals Rp125.730.312; the historical Rp1.200.000 exchange is not replayed or double counted.

## Correction — Riwayat Penggunaan Dana

The Fund detail page now gives the operational answer to **“saldo Dana ini berasal dari mana dan sudah digunakan untuk apa?”** without treating historical usage as allocation.

`Riwayat Penggunaan Dana` is a separate, read-only table with:

`Tanggal / Periode | Uraian | Keterangan | Pemasukan | Pengeluaran | Saldo Berjalan`

Every row carries `ZISWAF UPDATE 3.xlsx` plus its workbook cell range. The workbook’s source label is retained when it has no exact date (for example, `Sesi Ramadhan`); no calendar date was fabricated.

- **Sodaqoh:** Penerimaan Ramadhan 1447 H - Sodaqoh Rp8.506.000 (`Buku Kas Detail!A9:F9`), Beras 20 Pack Rp1.600.000 (`Buku Kas Detail!A24:F24`), running balance Rp6.906.000.
- **Zakat Maal:** receipt Rp97.145.386 (`A7:F7`), April distribution Rp10.700.000 (`A12:F12`), May distribution Rp10.700.000 (`A13:F13`), ending Rp75.745.386.
- **Dhuafa:** Saldo Awal Buku Rp20.378.977 (`A2:F2`), then the three recorded usages Rp5.530.000, Rp1.095.000, and Rp4.095.000, ending Rp9.658.977.
- **Infaq & Tromol:** historical movement balance Rp16.666.949 reconciles with the separately displayed Cash Tromol Yatim account component Rp2.653.000 to the Fund balance Rp19.319.949. Cash is explicitly labelled account/cash composition, not Fund income or expenditure.

The existing sections remain semantically separate:

1. **Riwayat Penggunaan Dana** — source historical receipt/opening and usage only; it never creates a V2 financial fact.
2. **Riwayat Transaksi V2** — only immutable Posted V2 Ledger-derived activity.
3. **Riwayat Alokasi dan realisasi** — operational allocation plans and their linked posted payments; the final ZISWAF source continues to contribute zero allocation records.

No historical source row was imported as an Allocation, Realization, FinancialTransaction, Journal, JournalLine, Ledger entry, or Voucher.

## Debug — Fund usage history visibility

### Root cause and actual-database inspection

Read-only inspection was performed against `mrj_prod_db`, not the test database. The actual entity is `MRJ-ACTUAL`, and it is the **only active financial entity**. Its actual facts remain exactly one posted opening-balance batch, one `OPB` FinancialTransaction, one Journal, 13 JournalLines, 13 Ledger entries, zero Allocation records, and zero Realization records.

The approved source archive and opening-balance evidence are present on the posted batch. The Fund and liquidity reports reconcile to the approved position: Sodaqoh Rp6.906.000, Zakat Maal Rp75.745.386, Dhuafa Rp9.658.977, Infaq & Tromol Rp19.319.949, BNI ZISWAF Rp123.077.312, and Cash Tromol Yatim Rp2.653.000.

The suspected source-data/service/UI defect was **not reproducible** in the current runtime:

- The final `ZISWAF UPDATE 3.xlsx` workbook contains the expected source rows in `Buku Kas Detail` and closing reconciliation in `Sisa Alokasi Dana`.
- `FundHistoryReadService` returns two source rows for Sodaqoh, three for Zakat Maal, four for Dhuafa, and 13 for Infaq & Tromol; all have workbook cell lineage and zero reconciliation difference.
- An authenticated application request rendered directly against `mrj_prod_db` returns HTTP 200 for each Fund detail route and contains `Riwayat Penggunaan Dana` plus each expected source description and cell reference. Sodaqoh specifically contains `Penerimaan Ramadhan 1447 H - Sodaqoh`, `Beras 20 Pack`, and `Buku Kas Detail!A24:F24`.
- The compiled Blade view contains the source-history rows and their lineage. The original implementation used two mutually-exclusive responsive wrappers: `md:hidden` for cards and `hidden md:block` for the table.

The source projection itself was correct, but the duplicate responsive wrappers caused the actual UI to hide both presentations at the observed viewport. The fix replaces those wrappers with one `overflow-x-auto` table that is visible at every breakpoint; narrow screens use intentional horizontal scrolling. No Financial V2 financial fact was written.

### Regression hardening

The MRJ onboarding feature regression now renders Fund-detail pages, not only service output. It asserts source-history visibility for Sodaqoh, Zakat Maal, Dhuafa, and Infaq & Tromol, including representative source cell references and the always-visible source-table wrapper. This closes the earlier coverage gap in which the source projection was verified but not every actual Fund page was rendered.

The local application cache was safely cleared and the Blade view cache rebuilt; this does not write financial data. A second read-only `mrj_prod_db` inspection after that operation returned the same counts and balances: OPB 1, Journal 1, JournalLine 13, Ledger 13, Allocation 0; BNI Rp123.077.312 and Cash Rp2.653.000; all six approved Fund balances remain unchanged.

Validation after the regression addition:

- Focused source-history suite: **4 passed, 92 assertions** on `mrj_test_db`.
- Full `php artisan test`: **119 passed, 827 assertions, 0 failed, 0 skipped**; the one existing risky `ExampleTest` remains unrelated.
- PHP lint, targeted Pint, Blade cache, and `git diff --check` passed. The repository-wide Pint debt remains out of scope and was not mass-formatted.

## Local development verification

The local provisioning command completed with the following control result:

| Control | Result |
| --- | ---: |
| Active Funds | 11 |
| Active Financial Accounts | 10 |
| Active Programs | 12 |
| Active Categories | 31 |
| Transactions before/after | 1 / 1 |
| Journals before/after | 1 / 1 |
| JournalLines before/after | 13 / 13 |
| Ledger entries before/after | 13 / 13 |

`php artisan view:cache` and PHP lint for changed PHP files passed.

## Validation results

The disposable `mrj_test_db` migration repository was reconciled using process-scoped testing environment variables only. All 15 Financial V2 migrations are now registered as applied in that database. No migration or reset command targeted `mrj_prod_db`.

- Focused historical usage regression: `tests/Unit/FinancialV2/MrjZiswafSourceSemanticTest.php` plus `tests/Feature/FinancialV2/MrjZiswafActualOnboardingCommandTest.php`: **4 passed, 91 assertions**. It protects source date labels/cell lineage, all four rendered Fund-detail histories, Sodaqoh running balance, the Cash Tromol account-position separation, the zero-allocation decision, UI presentation, and idempotent onboarding.
- `tests/Feature/FinancialV2/Phase12OperationalMasterTest.php`: **1 passed, 33 assertions**. It verifies governed master provisioning leaves MRJ Actual with zero allocations and no additional financial facts.
- Full `php artisan test`: **119 passed, 827 assertions, 0 failed, 0 skipped**. The one pre-existing risky `ExampleTest` remains unrelated to Financial V2.
- Local browser layout check of the login surface: 375px, 768px, and 1024px had no horizontal overflow. Authenticated Financial-page browser recheck remains part of the final validation gate.
- PHP lint passed for all Phase 12.1 PHP files, Blade cache passed, route verification passed, and `git diff --check` passed.
- `vendor/bin/pint --test` was executed. It reports pre-existing formatting violations across legacy controllers, repositories, migrations, seeders, and configuration files; none of the Phase 12.1 changed PHP files is listed. No out-of-scope mass-formatting was performed.

Read-only inspection of `mrj_prod_db` after the source audit showed one OPB transaction, one Journal, 13 JournalLines, 13 Ledger entries, and **zero BudgetAllocation records** for MRJ Actual. Ledger-backed reporting still returns all six exact Fund balances, BNI Rp123.077.312, and Cash Rp2.653.000.

## Remaining governance gaps

1. Authenticated Financial-page browser QA was completed in Phase 12.5, including desktop and 390 px responsive validation; repeat it after future UI changes.
2. Address repository-wide legacy Pint debt in a separately scoped maintenance task; mass formatting is outside the Financial V2 change boundary.
3. For policy uses not explicitly covered by the mandate, create a new effective Fund-policy version; do not broaden a current policy or invent a use.

## Phase 12.5 — current Fund attribution position

The earlier Rp19.319.949 Infaq & Tromol and Rp9.658.977 Dhuafa figures in this document describe the approved 27 June opening position before the Phase 12.5 attribution correction. They are not the current Fund balances.

On 16 August 2026, two governed, evidence-backed Inter-Fund Transfers were posted through the canonical Posting Engine:

1. Rp2.653.000 reattributes the full Cash Tromol Yatim component from Infaq & Tromol to Dhuafa & Anak Yatim without moving physical cash.
2. Rp1.200.000 reclassifies Fund balance from Infaq & Tromol to Dhuafa & Anak Yatim using the approved PDF pages 3–4, without creating income, expense, or liquidity.

The existing `DHUAFA` Fund was renamed to **Dana Dhuafa & Anak Yatim** with the same UUID. Current posted-ledger-backed results are:

| Position | Current amount |
| --- | ---: |
| Dana Infaq & Tromol | Rp15.466.949 |
| Dana Dhuafa & Anak Yatim | Rp13.511.977 |
| Combined position | Rp28.978.926 |
| BNI ZISWAF | Rp123.077.312 |
| Cash Tromol Yatim | Rp2.653.000 |
| Total liquidity | Rp125.730.312 |

Infaq & Tromol is composed only of BNI Rp15.466.949. Dhuafa & Anak Yatim is composed of BNI Rp10.858.977 plus Cash Rp2.653.000. Fund totals changed attribution only; account balances and total liquidity remain unchanged.

See `FINANCIAL-PHASE-12.5-FUND-ATTRIBUTION-CORRECTION.md` for source hashes, policy containment, immutable-fact deltas, idempotency evidence, browser QA, and final test results.
