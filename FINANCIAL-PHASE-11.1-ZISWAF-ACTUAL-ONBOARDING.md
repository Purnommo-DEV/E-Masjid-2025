# Phase 11.1C — Final ZISWAF Source Resolution & Actual MRJ Onboarding

**Gate status: ZISWAF ACTUAL ONBOARDING = PASS**

**Position date:** 27 June 2026  
**Opening-balance reference:** `MRJ-ZISWAF-OPENING-2026-06-27-V1`  
**Accounting entity:** `MRJ-ACTUAL` — Masjid Raudhotul Jannah  
**Source:** `C:\Users\Kelascom\Downloads\ZISWAF UPDATE 3.xlsx`  
**Source SHA-256:** `404FC8CD54ECD3E35E17C30FFE6A3D88DF6656260CBEAC8F614EF99689A02F9C`

This is an actual local-development Financial V2 opening-position onboarding. It does not migrate historical transactions, read or write any legacy financial table, make a cutover decision, or create a dual-write path.

## 1. Final Source

The final workbook has seven worksheets: the daily recap, two recap details, `Rekonsil 27 Juni 2026`, `Ringkasan Laporan`, `Buku Kas Detail`, and `Sisa Alokasi Dana`. A full cell-value/formula error scan found no material `#REF!`, `#VALUE!`, `#DIV/0!`, or broken formula result.

`Sisa Alokasi Dana!A5:D12` is the authoritative opening-position source. `Ringkasan Laporan!B11:C14` confirms the BNI reconciliation. The source is a position as of 27 June 2026, so Opening Balance V2 is the correct intake boundary; it is not a sufficiently complete transaction ledger for historical transaction migration.

The source workbook is preserved read-only in Laravel local storage at `financial-v2/source-archive/mrj-ziswaf/2026-06-27/<sha256>.xlsx`. Its evidence PDF is separately retained under `financial-v2/opening-evidence/mrj-ziswaf/2026-06-27/` and linked to every opening-balance line.

## 2. Cash Tromol Resolution

| Item | Amount | Treatment |
| --- | ---: | --- |
| Opening Cash Tromol | Rp3.853.000 | Historical starting cash count |
| Exchange paid from Cash Tromol | (Rp1.200.000) | Historical Treasury Transfer / internal liquidity movement |
| Ending Cash Tromol | **Rp2.653.000** | Actual V2 opening Cash position |
| BNI closing | **Rp123.077.312** | Already includes the Rp1.200.000 incoming exchange |

The Rp1.200.000 is documented in the evidence package as a historical internal-liquidity movement only. It was **not posted again** in V2: doing so would double count the confirmed BNI closing balance. It has zero Fund, income, and expense effect.

## 3. BNI Reconciliation

The V2 `BNI-ZISWAF` Financial Account is posted at Rp123.077.312. This agrees with `Ringkasan Laporan` and the BNI column of the authoritative source position. Difference: **Rp0**.

## 4. Fund Mapping

| Source Fund | V2 Fund code | V2 Fund balance | Source lineage |
| --- | --- | ---: | --- |
| Zakat Maal | `ZAKAT-MAAL` | Rp75.745.386 | `Sisa Alokasi Dana!A5:D5` |
| Infaq & Tromol | `INFAQ-TROMOL` | Rp19.319.949 | `Sisa Alokasi Dana!A6:D7` |
| Sodaqoh | `SODAQOH` | Rp6.906.000 | `Sisa Alokasi Dana!A8:D8` |
| Santunan Anak Yatim | `SANTUNAN-YATIM` | Rp6.600.000 | `Sisa Alokasi Dana!A9:D9` |
| Fidyah | `FIDYAH` | Rp7.500.000 | `Sisa Alokasi Dana!A10:D10` |
| Dhuafa | `DHUAFA` | Rp9.658.977 | `Sisa Alokasi Dana!A11:D11` |
| **Total** |  | **Rp125.730.312** |  |

Each Fund is a separate restricted Fund. The source has no Program attribution, so no Program was invented and no Program was mapped as a Fund.

## 5. Financial Account Mapping

| Source location | V2 Financial Account | Balance |
| --- | --- | ---: |
| BNI ZISWAF | `BNI-ZISWAF` — BNI ZISWAF | Rp123.077.312 |
| Cash Tromol Yatim | `CASH-ZISWAF` — Cash Tromol Yatim | Rp2.653.000 |
| **Total liquidity** |  | **Rp125.730.312** |

Both Financial Accounts use the V2 liquidity Account `LIQ-ZIS`; Fund position is represented independently by the net-asset Account `NET-ZIS`. Neither financial account is a Fund.

## 6. SAMPLE/QA Cleanup

Before onboarding, cleanup inventory was restricted exclusively to the entity whose exact code was `MRJ-SAMPLE-QA`: 9 Financial Accounts, 11 Funds, 7 Programs, 7 Categories, 22 Transactions, 22 Journals, 44 JournalLines, 44 Ledger entries, 22 attachments, and 302 audit events, with their associated governed configuration.

After the actual opening batch posted and reconciled, only that exact entity and its scoped Financial V2 dependents were removed. The cleanup checked the schema's two entity-less child tables (`financial_v2_bank_account_details` and `financial_v2_cash_account_details`) before proceeding. It did not use `migrate:fresh`, truncate, reset, broad deletes, migration rollback, or a destructive seeder. `MRJ-SAMPLE-QA` no longer exists; the actual `MRJ-ACTUAL` fact counts were unchanged by cleanup.

## 7. Opening Balance

The command `financial-v2:onboard-mrj-ziswaf` created the approved mapping set, 33 explicit cell/dimension mappings, and one 13-line opening-balance batch. The batch is posted with cutover/position date **2026-06-27** and has 13 active PDF evidence links. Its evidence package records source filename, hash, source archive, worksheet/cell references, as-of date, amount, Fund, Financial Account, and the cash-resolution explanation.

The command is guarded as follows:

- Actual execution is allowed only in `APP_ENV=local` on `mrj_prod_db`.
- Test execution is allowed only in `APP_ENV=testing` on `mrj_test_db` with `--allow-testing`.
- A second execution finds the same batch and validates it; it cannot create a second opening batch, voucher, Journal, JournalLine, or Ledger entry.

## 8. Journal / Ledger Result

The Opening Balance was posted through `OpeningBalanceService` and the canonical Posting Engine only. The resulting posted Journal is `a281ac1c-9168-486f-a7eb-223c379632f5`.

| Control | Result |
| --- | --- |
| Journal status | Posted |
| Debit | Rp125.730.312 |
| Credit | Rp125.730.312 |
| JournalLines | 13 |
| Immutable Posted Ledger entries | 13 |
| Financial transactions | 1 (`OPB`) |
| Orphan Ledger entries | 0 |

No onboarding code directly creates or updates a Journal, JournalLine, or Ledger entry. The command supplies source-backed Opening Balance inputs; the Posting Engine is the canonical fact writer.

## 9. Fund Reconciliation

Every V2 Fund-balance report row agrees to its source amount at 27 June 2026. Each difference is **Rp0**. The report source is `financial_v2_posted_general_ledger`.

## 10. Account Reconciliation

| Financial Account | Source | V2 Posted Ledger | Difference |
| --- | ---: | ---: | ---: |
| BNI ZISWAF | Rp123.077.312 | Rp123.077.312 | Rp0 |
| Cash Tromol Yatim | Rp2.653.000 | Rp2.653.000 | Rp0 |

## 11. Total Reconciliation

| Measure | Amount |
| --- | ---: |
| Total Fund balance | Rp125.730.312 |
| Total Financial Account liquidity | Rp125.730.312 |
| Difference | **Rp0** |

The V2 Trial Balance is balanced. The equality is a reconciliation result, not a model that equates Fund to Financial Account.

## 12. Santunan Anak Yatim Workflow

`SANTUNAN-YATIM` is onboarded with a Fund opening balance of **Rp6.600.000**. The source does not supply a governed Program, beneficiary list, allocation, approval, or realization, so no actual Santunan disbursement was invented.

The existing V2 allocation → submit → approve → realization workflow remains available and is regression-tested: allocation and approval create no Journal; one approved total realization creates one payment effect through the Posting Engine, rather than one Journal per recipient. A future Bank-to-Cash Treasury Transfer will preserve the Fund balance; only an approved actual realization can reduce it.

## 13. Reporting Result

Dashboard/read models, Fund Detail, Account Report, and ZISWAF Report read `financial_v2_posted_general_ledger`. Actual read-only verification returned:

- ZISWAF Report: data available from Posted V2 Ledger.
- Fund Report: six separate Fund balances listed in section 4.
- Account Report: BNI Rp123.077.312 and Cash Rp2.653.000.
- Trial Balance: balanced.

No report reads legacy financial transactions or uses SAMPLE/QA data.

## 14. Idempotency

The actual command was replayed after posting. It retained exactly one opening batch, one transaction, one Journal, 13 JournalLines, 13 Ledger entries, and 13 evidence links. The isolated onboarding regression test also replays the command and verifies the same no-duplicate result.

## 15. Test Result

| Validation | Result |
| --- | --- |
| MRJ ZISWAF isolated onboarding regression | PASS — 1 test, 22 assertions |
| Financial V2 suite | PASS — 86 tests, 629 assertions |
| Full `php artisan test` | PASS — 112 tests, 698 assertions; 1 pre-existing risky `ExampleTest`, 0 failed, 0 skipped |
| PHP lint (new/changed PHP) | PASS |
| Pint (new/changed PHP) | PASS |
| Blade cache | PASS |
| `git diff --check` | PASS |
| Opening schema / FK / index coverage | PASS through Financial V2 Opening Balance regression test |

All automated tests use the guarded `mrj_test_db`; no `RefreshDatabase` operation was run against `mrj_prod_db`.

## 16. Browser QA

The local application was reachable at `http://localhost:8888`. The Financial V2 route `/admin/keuangan-v2` correctly redirected an unauthenticated session to `/login`, and the Financial V2 authenticated page/navigation behavior is covered by the Feature test suite.

Direct browser authentication remains intentionally unexecuted at this point because entering the local QA password in a browser is a sensitive-data transmission requiring action-time confirmation. No browser form, transaction form, master form, or evidence upload was submitted during this onboarding.

## 17. Remaining Gaps

There is no source or accounting mismatch remaining for the approved 27 June 2026 ZISWAF opening position.

The source workbook does not define operational Program eligibility, categories, beneficiary evidence, or an approved permitted-use matrix for the six restricted Funds. The onboarding therefore permits **only** the `OPB` transaction type for each Fund and remains fail-closed for operational receipts, payments, transfers, and realizations until governed policy/configuration is entered. This is intentional: it prevents an invented policy or unauthorized use of restricted money.

Authenticated browser QA can be completed after action-time approval to submit the specified local QA credentials to the local login form. This is a QA-surface control, not an accounting, source, migration, or reconciliation blocker.

## Final Controls

- Actual source data was preserved in immutable archive/evidence form.
- No actual record was deleted.
- No legacy financial table was accessed by the onboarding command.
- No dual-write path exists.
- No historical cash exchange was double counted.
- The Posted V2 General Ledger is the sole source for reported V2 balances.
