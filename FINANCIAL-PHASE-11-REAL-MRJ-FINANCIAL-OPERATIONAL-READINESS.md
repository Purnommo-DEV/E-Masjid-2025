# Final Phase 11 — Financial V2 Real MRJ Data & Operational Readiness

**Assessment date:** 15 August 2026  
**Actual entity:** `MRJ-ACTUAL` — Masjid Raudhotul Jannah  
**ZISWAF opening position date:** 27 June 2026  
**Overall status:** **FINANCIAL V2 = NOT READY FOR DAILY OPERATION**

The accounting engine, real ZISWAF onboarding, and ledger/reporting controls pass. Daily-operation readiness is not yet granted because the actual entity has no governed Programs or Categories and every actual restricted Fund is intentionally limited to `OPB`. This is a missing operational-governance configuration, not an accounting integrity defect and not a reason to invent policy or master data.

## 1. Real Data Onboarding

`MRJ-ACTUAL` is the only active Financial V2 accounting entity. It holds one posted Opening Balance batch (`MRJ-ZISWAF-OPENING-2026-06-27-V1`), one posted `OPB` transaction, one Journal, 13 JournalLines, 13 immutable Ledger entries, and 13 active evidence links. `MRJ-SAMPLE-QA` is absent.

The source is `ZISWAF UPDATE 3.xlsx`, SHA-256 `404FC8CD54ECD3E35E17C30FFE6A3D88DF6656260CBEAC8F614EF99689A02F9C`. The workbook and the source-evidence PDF are retained on the local financial-evidence disk. No historical transaction migration was performed.

## 2. ZISWAF Reconciliation

The final source position and V2 posted position reconcile as follows:

| Position | Source | Posted V2 | Difference |
| --- | ---: | ---: | ---: |
| BNI ZISWAF | Rp123.077.312 | Rp123.077.312 | Rp0 |
| Cash Tromol | Rp2.653.000 | Rp2.653.000 | Rp0 |
| Total | Rp125.730.312 | Rp125.730.312 | Rp0 |

The historical Rp1.200.000 cash exchange was not reposted. The final BNI amount already includes it; reposting would double count liquidity.

## 3. Fund Mapping

| Fund | Code | Posted balance |
| --- | --- | ---: |
| Zakat Maal | `ZAKAT-MAAL` | Rp75.745.386 |
| Infaq & Tromol | `INFAQ-TROMOL` | Rp19.319.949 |
| Sodaqoh | `SODAQOH` | Rp6.906.000 |
| Santunan Anak Yatim | `SANTUNAN-YATIM` | Rp6.600.000 |
| Fidyah | `FIDYAH` | Rp7.500.000 |
| Dhuafa | `DHUAFA` | Rp9.658.977 |
| **Total** |  | **Rp125.730.312** |

All six Funds are active and restricted. Fund is not conflated with Account or Program.

## 4. Financial Account Mapping

| Financial Account | Code | Type | Posted liquidity |
| --- | --- | --- | ---: |
| BNI ZISWAF | `BNI-ZISWAF` | Bank | Rp123.077.312 |
| Cash Tromol Yatim | `CASH-ZISWAF` | Cash | Rp2.653.000 |

The account report and Fund account-composition report both read the same Posted V2 Ledger facts while preserving their distinct meanings.

## 5. Program Mapping

There is no actual Program source attribution in the workbook. Accordingly, no Program was created or inferred: actual Program count is **0**. This preserves the rule that a Program is neither a Fund nor a Financial Account.

## 6. Sample Cleanup

Cleanup was restricted to the exact `MRJ-SAMPLE-QA` entity and its scoped V2 dependents only, after successful actual posting. It did not use `migrate:fresh`, truncate, reset, destructive seeding, or a broad delete. Actual opening balance, Journal, Ledger, evidence, audit trail, and masters remain present.

## 7. Opening Balance

The source position entered through Mapping Set → Opening Balance Batch → Opening Balance Service → canonical Posting Engine. There are 33 explicit source/dimension mappings and 13 source-backed opening lines. Every line has an active PDF evidence attachment containing source-file, source range, as-of, Fund, Account, Financial Account, amount, description, and import lineage.

## 8. Journal / Ledger Validation

| Control | Result |
| --- | --- |
| Journal status | Posted |
| Total debit | Rp125.730.312 |
| Total credit | Rp125.730.312 |
| JournalLines | 13 |
| Ledger entries | 13 |
| Orphan Ledger entries | 0 |
| Duplicate vouchers | 0 |
| Replay duplicate facts | 0 |

No controller, report, or onboarding command writes Journal, JournalLine, or Ledger directly. The Posting Engine remains the single canonical writer.

## 9. Fund Balance Validation

Fund balance reports read `financial_v2_posted_general_ledger`. Every Fund in section 3 agrees to its source target at a difference of Rp0.

## 10. Liquidity Validation

Liquidity is reported separately from Fund balance. BNI plus Cash equals Rp125.730.312, and the Fund account-composition distribution agrees to the same total without asserting that a Bank/Cash account equals a Fund.

## 11. Operational Friday Workflow

The V2 lifecycle, Posting Engine, Friday report, and ledger-backed computation are covered by the Financial V2 End-to-End scenario test. No actual BSI/Cash Operational master, Fund, Program, Category, evidence, or approved policy was supplied in this source set; therefore no actual Friday operation was fabricated.

## 12. ZISWAF Workflow

The actual ZISWAF opening position is available in V2 reports. A new receipt, payment, treasury transfer, or realization for a restricted actual Fund is correctly blocked until an effective governing policy permits it. This is the required fail-closed behavior.

## 13. Yatim Workflow

`SANTUNAN-YATIM` opens at Rp6.600.000. The allocation → submit → approve → realization workflow is regression-tested: allocation/approval do not create a financial fact, while one approved total realization produces one payment effect through the Posting Engine. No actual beneficiary program, allocation, approval, realization, or evidence list was supplied, so none was invented.

## 14. Qurban Workflow

The V2 Qurban receipt, Bank-to-Cash transfer, and actual-payment sequence is covered by the end-to-end suite. No actual Qurban source/master/policy was supplied; there is no actual Qurban Fund, Program, or Financial Account in `MRJ-ACTUAL`.

## 15. Ramadhan Workflow

The engine supports the separate models `Dana Ramadhan` and `Dana Operasional + Program Ramadhan`; the workflow suite verifies dimension separation. No actual Ramadhan master/policy was supplied and none was created.

## 16. Social / Kematian Workflow

The architecture supports separate Account, Fund, and Program dimensions. No actual Social/Kematian source/master/policy was supplied, so no actual configuration was inferred.

## 17. Sewa Aula Workflow

The architecture supports separate Account, Fund, and Program dimensions. No actual Sewa Aula source/master/policy was supplied, so no actual configuration was inferred.

## 18. Master Financial Data

| Master | Actual count | Readiness |
| --- | ---: | --- |
| Rekening / Kas | 2 | PASS for actual ZISWAF |
| Fund | 6 | PASS for actual ZISWAF |
| Program | 0 | Missing governed actual data |
| Kategori | 0 | Missing governed actual data |
| Fund policy versions | 6 | Present; `OPB` only |

The authenticated master UI and lifecycle endpoints exist, are audited, and have no hard-delete endpoint for referenced masters. The missing entries are data-governance gaps, not unavailable application features.

## 19. Authenticated Desktop QA

The supplied local QA account authenticated successfully at `http://localhost:8888/login` and redirected to `/admin/keuangan-v2`. Session, top navigation, and the Admin sidebar were verified. The sidebar exposes the intended business navigation—Dashboard, Penerimaan, Pengeluaran, Transfer, Dana, Alokasi Dana, Riwayat Transaksi, Laporan, Kontrol—and the `Master Keuangan` group—Rekening/Kas, Dana, Program, Kategori Transaksi, and Aturan Dana. It does not expose Journal, JournalLine, Ledger, Posting Engine, or CoA.

The following authenticated pages were navigated without a 500 response or broken link: Dashboard, Dana, Rekening/Kas, Program, Kategori, Penerimaan, Pengeluaran, Transfer, Alokasi, Realisasi, Riwayat, Laporan, Kontrol, and Rekonsiliasi. The ordinary receipt, payment, transfer, allocation, and realization forms use Indonesian operational labels; they do not ask the user for debit, credit, Journal, Ledger, or CoA.

The UI showed the actual position correctly:

| Display | Result |
| --- | ---: |
| Dashboard BNI ZISWAF | Rp123.077.312 |
| Dashboard Cash Tromol Yatim | Rp2.653.000 |
| Dashboard total kas & rekening | Rp125.730.312 |
| ZISWAF report—Zakat Maal | Rp75.745.386 |
| ZISWAF report—Infaq & Tromol | Rp19.319.949 |
| ZISWAF report—Sodaqoh | Rp6.906.000 |
| ZISWAF report—Santunan Anak Yatim | Rp6.600.000 |
| ZISWAF report—Fidyah | Rp7.500.000 |
| ZISWAF report—Dhuafa | Rp9.658.977 |

The Fund report explicitly distinguishes Saldo Dana from available liquidity and shows the Bank/Cash composition. It does not equate BNI ZISWAF with Zakat Maal. Program and Category each render a clear empty state—`Belum ada Program yang dikonfigurasi.` and `Belum ada Kategori transaksi yang dikonfigurasi.`—without inventing actual data.

The non-destructive `Periksa kombinasi dana` action was run for actual Dana Zakat Maal and BNI ZISWAF. It returned the required fail-closed message: `Penggunaan dana belum dapat dilakukan karena aturan penggunaan dana belum dikonfigurasi.` No draft, transaction, Journal, JournalLine, Ledger, evidence, or master was created by browser QA.

## 20. Tablet and Mobile QA

Authenticated responsive QA passed at 375 px, 390 px, and 430 px mobile widths, plus 768 px, 820 px, and 1024 px tablet widths. Dashboard, receipt, payment, transfer, Control/Reconciliation, and ZISWAF report were inspected. Tables retain their own intentional horizontal scroll container where needed; no unintended page or grid overflow remained.

The mobile Admin hamburger now opens the actual sidebar, reports an accessible expanded state, and exposes a labelled close action. Financial V2 navigation remains available within it. The Financial V2 top navigation is horizontally scrollable by design at narrow widths; all navigation items remain reachable.

## 21. Issues Found, Fixes, and Regression Coverage

| Finding | Safe correction | Regression coverage |
| --- | --- | --- |
| Global client script registered `/push-sw.js`, but the root public asset was absent, producing a Service Worker 404 console error. | Added `public/push-sw.js`, the concrete push worker expected by the client. Local HTTP verification returned 200 `application/javascript`; a fresh authenticated Financial V2 tab had no console error. | `PushServiceWorkerAssetTest` verifies the registered asset and both push handlers. |
| Long Fund labels caused horizontal page overflow on the narrow Dashboard; the Control/Reconciliation grid could do the same around contained tables. | Added `min-w-0` to the responsive grid cards so text truncates within the card while tables keep their own scroll container. | Operational UX page assertions plus visual checks at 375, 430, 768, 820, and 1024 px. |
| The mobile hamburger changed the outer layout state while the sidebar held a separate state, so the sidebar did not open. The icon-only controls also lacked accessible names. | Removed the duplicate sidebar state; the sidebar now inherits the layout state. Added labelled, stateful open/close controls and `aria-controls`. | Master-navigation test asserts the shared structure and accessibility labels; browser QA confirmed `aria-expanded=true` and visible Financial V2 menu. |
| Fund-usage preview used GET although its endpoint is POST-only; it displayed a method error instead of policy guidance. | Changed the preview request to CSRF-protected POST. For an actual restricted Fund whose operational transaction type is not yet configured, preview now fails closed with the mandated Fund-policy message rather than exposing a technical configuration error. | Operational UX tests cover POST transport, restricted-policy rejection, and the no-operational-type fail-closed case. |

## 22. Test Results and Isolation

| Validation | Result |
| --- | --- |
| Full `php artisan test` | **PASS — 115 passed, 720 assertions, 0 failed, 0 skipped**; 1 pre-existing risky `ExampleTest` output-buffer warning |
| PHP lint | PASS for every changed PHP file |
| Pint | PASS — 4 changed PHP files |
| Blade cache | PASS |
| Vite build | PASS — 4.76 s |
| `git diff --check` | PASS |
| Read-only V2 preflight simulation | Technical schema/service/test-isolation/legacy-isolation checks PASS on `testing` / `mrj_test_db`; empty test-master inventory is expected after the isolated suite |
| Canonical-writer audit | PASS — no direct `Journal::create`, `JournalLine::create`, or `LedgerEntry::create` in the UI controller, actual-onboarding command, or Financial V2 views |
| Legacy isolation audit | PASS — no V2 runtime use of legacy financial tables/models; `LegacyMapping` remains source-lineage metadata only |

Tests printed the existing unrelated `Berita ID ... tidak ditemukan` diagnostic noise and the existing risky `ExampleTest`; neither caused a failure. Automated test preflight confirms `APP_ENV=testing` and `DB_DATABASE=mrj_test_db`. No test refresh, migration, reset, or destructive action ran against `mrj_prod_db`.

## 23. Remaining Governance Gaps

1. The actual entity has **0 Program** and **0 Category** records. No source was supplied to create them.
2. Actual operational Transaction Types and effective Fund-policy rules for `RCV`, `PAY`, `TRF`, and realization are not configured. The six actual restricted ZISWAF Funds correctly remain `OPB`-only.
3. Approved actual operational Financial Accounts, Fund mappings, Programs, Categories, and policy matrices are still required for Friday, Qurban, Ramadhan, Social/Kematian, and Sewa Aula.

**Final determination:** authenticated desktop, tablet, and mobile UI QA now pass; real ZISWAF opening, reporting, and accounting integrity remain reconciled. **FINANCIAL V2 = NOT READY FOR DAILY OPERATION** solely because governed operational master/policy data has not been supplied. No actual data needs deletion or correction.
