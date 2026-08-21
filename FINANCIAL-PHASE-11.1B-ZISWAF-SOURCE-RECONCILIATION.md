# Financial Phase 11.1B — Final ZISWAF Source Reconciliation

**Mode:** Read-only source reconciliation
**Scope date in source:** 27 June 2026
**Database / Financial V2 impact:** None. No database connection, migration, import, opening balance, Journal, JournalLine, Ledger, master-data change, or cleanup was performed.

## 1. Scope and method

This assessment reconciles the revised source workbook `ZISWAF UPDATE 2.xlsx` against its available original dependency workbook, `Laporan Keuangan Ziswaf DKM MRJ TCE (1).xlsx`. Both were read only. The purpose is to determine source readiness, not to select a financial truth, repair a workbook, or post data to Financial V2.

The assessment reviewed every worksheet, used range, formula chain, error value, date label, subtotal, and supporting source row relevant to the ZISWAF closing balances.

## 2. Revised-workbook inventory

| Worksheet | Used range | Purpose | Formula health |
| --- | --- | --- | --- |
| `Ringkasan Laporan` | `A1:D14` | Book reconciliation and stated BNI/cash balances | Materially broken: totals and reconciliation inherit `#REF!`. |
| `Buku Kas Detail` | `A1:F27` | Receipts, expenditure, and running book balance | Materially broken: two direct missing references cascade to the running balance and receipt total. |
| `Sisa Alokasi Dana` | `A1:F66` | Fund-by-fund allocation balance and total | Materially broken: Sodaqoh, Yatim, and grand-total chain inherit `#REF!`. |

The revised workbook contains only these three sheets. Its formulas are otherwise internal-sheet formulas; there is no surviving external-workbook formula reference that can identify the missing source cells. The two direct breakpoints are:

- `Buku Kas Detail!D11`: `=15106000-#REF!` (Sodaqoh)
- `Buku Kas Detail!D12`: `=#REF!` (Dana Santunan Anak Yatim)

All other `#REF!` findings are downstream results of those breakpoints, including the cash-book total, book balance, reconciliation, Sodaqoh/Yatim fund balances, and grand totals.

## 3. Original dependency workbook inventory

The named original workbook is available and was reviewed in full. It contains 15 sheets:

1. `Rekap Global`
2. `Rekap Histori BNI Ziswaf`
3. `Rekap Harian Ziswaf (Maret 2024`
4. `Rekap Harian Ziswaf 1446 H (Mar`
5. `Rekap Global 2025`
6. `Pengeluaran Maret 2025`
7. `Penerimaan Cash Ziswaf 2025`
8. `Rekap Harian Ziswaf 1447 H (Mar`
9. `Rincian Rekap 18 Maret`
10. `Rincian Rekap 19 Maret`
11. `Rekonsil 27 Juni 2026`
12. `Ringkasan Laporan`
13. `Buku Kas Detail`
14. `Sisa Alokasi Dana`
15. `Yayasan Yatim Usulan`

The missing `#REF!` sources in the revised three-sheet workbook can be located in the original workbook. The original `Buku Kas Detail` used:

- Sodaqoh: `=15106000-'Rekap Harian Ziswaf 1447 H (Mar'!K16`
- Dana Santunan Anak Yatim: `='Rekap Harian Ziswaf 1447 H (Mar'!K16`

The source cells are present in the original daily-recap sheet: `K309 = 15,106,000` and `K16 = 6,600,000`. This explains the intended formula lineage, but does **not** make the revised workbook self-contained or formula-healthy.

## 4. Formula, dependency, and date health

### Formula chain

The two missing direct references cause the following material chains to fail in the revised workbook:

- `Buku Kas Detail!D27` (total receipts) and `F11:F27` (running book balance);
- `Ringkasan Laporan!C7`, `C9`, `C11`, and `C13`;
- `Sisa Alokasi Dana!B8:D9`, `B12:D12`, `C44:E50`, and `C63:E63`.

Therefore the revised workbook cannot recalculate its own total cash movement, book-to-BNI reconciliation, or total ZISWAF allocation.

### Period and date checks

The report period is labelled February–June 2026; BNI is labelled 27 June 2026 and the cash Tromol Yatim amount is labelled 14 June 2026. The cash-book inputs also include four transferred Tromol receipts dated December 2025 through March 2026. That can be valid as a reconciled opening-period population, but it must remain supported by source detail.

There is a non-financial labeling defect in the Infaq/Tromol detail: the descriptions for several different dates repeat “10 Desember 2025.” The amounts and date column remain distinct, but the copied description should be corrected by the source owner during remediation.

## 5. Source values and re-performance

The following table distinguishes a value visible in the revised workbook from a value that is only re-performed against the original dependency. Re-performance is audit evidence only; it is **not** a correction, import instruction, or opening-balance selection.

| Component | Revised source status | Original-source evidence / arithmetic | Result |
| --- | --- | --- | --- |
| BNI balance | `123,077,312` stated in `Ringkasan Laporan` | Original `Rekonsil 27 Juni 2026` states the same BNI balance. Re-performance: `20,378,977 + 140,696,386 - 37,389,000 - 609,051 = 123,077,312`. | Source-supported, but not independently bank-statement verified. |
| Zakat Maal | `75,745,386` | `97,145,386 - 10,700,000 - 10,700,000 = 75,745,386`. | Confirmed. |
| Infaq & Tromol (BNI) | `16,666,949` | Receipts `2,779,000 + 7,030,000 + 2,592,000 + 5,098,000 + 3,446,000 = 20,945,000`; expenses and bank reconciliation fee total `4,278,051`; residual `16,666,949`. | Confirmed. |
| Sodaqoh | `#REF!` | Original source identifies receipt `8,506,000` and expense `1,600,000`, yielding `6,906,000`. | Revised workbook unresolved; dependency value re-performed only. |
| Dana Santunan Anak Yatim | `#REF!` | Original source cell `K16` is `6,600,000`; no matching outflow is shown in this allocation section. | Revised workbook unresolved; dependency value re-performed only. |
| Fidyah | `7,500,000` | Direct source receipt `7,500,000`; no expenditure shown. | Confirmed. |
| Dhuafa | `9,658,977` | `20,378,977 - 5,530,000 - 1,095,000 - 4,095,000 = 9,658,977`. | Confirmed. |

If, and only if, the original dependency values for Sodaqoh and Yatim are formally retained as authoritative, the fund allocation re-performs to the stated BNI amount:

`75,745,386 + 16,666,949 + 6,906,000 + 6,600,000 + 7,500,000 + 9,658,977 = 123,077,312`.

This conditional tie is useful diagnostic evidence. It does not resolve the defective revised workbook or authorize adopting those values into Financial V2.

## 6. Cash Tromol Yatim investigation

| Location | Amount | Status |
| --- | ---: | --- |
| Revised `Ringkasan Laporan` cash row | 2,653,000 | Current workbook fact, stated outside BNI. |
| Revised `Sisa Alokasi Dana` cash row | 2,653,000 | Internal link to the revised summary. |
| Original `Ringkasan Laporan` and `Sisa Alokasi Dana` | 2,653,000 | Historical dependency repeats the same current-style amount. |
| Original `Rekonsil 27 Juni 2026` | 3,853,000 | Conflicting source-dependency amount for the same “Cash Tromol Yatim 14 Juni 2026” description. |

`3,853,000` is **not** present in the revised workbook. It occurs in the original dependency workbook, not as a current verifiable fact in `ZISWAF UPDATE 2.xlsx`. The two cash amounts differ by `1,200,000`; neither workbook supplies a transaction, count sheet, correction entry, or other evidence explaining that difference. No amount was selected, replaced, or posted.

## 7. BNI reconciliation investigation

The stated BNI amount `123,077,312` is consistently repeated in the revised report and original dependency. The original dependency also supports the following full book arithmetic:

| Calculation | Amount |
| --- | ---: |
| Opening book balance | 20,378,977 |
| Receipts excluding opening balance | 140,696,386 |
| Less activity expenditure | (37,389,000) |
| Less admin/transfer fee | (609,051) |
| Re-performed BNI balance | 123,077,312 |

However, the revised workbook cannot perform that reconciliation itself because its receipt total is `#REF!`. The BNI figure is therefore **source-supported but formula-unverified in the revised workbook**. Independent verification to a bank statement remains outside this workbook-only assessment.

## 8. Fund-total reconciliation

The revised `Sisa Alokasi Dana` total row is `#REF!` for BNI and grand total, so it fails as a standalone source. Using the available original dependency solely for diagnostic re-performance, the BNI fund components total `123,077,312` as shown above.

The cash component remains unresolved because `2,653,000` and `3,853,000` are contradictory source values. Consequently, the reported grand total `125,730,312` cannot be accepted as a final reconciled source total.

## 9. Unresolved items

1. **Critical — missing formula dependencies:** the revised workbook has direct `#REF!` formulas for Sodaqoh and Santunan Anak Yatim.
2. **Critical — cash Tromol Yatim conflict:** `2,653,000` versus `3,853,000`, a difference of `1,200,000`, without explanatory source evidence.
3. **Critical — total/source health:** total receipts, ending book balance, reconciliation difference, BNI total allocation, and total ZISWAF allocation cannot calculate in the revised workbook.
4. **Medium — source provenance:** the BNI balance is internally source-supported but not tied to an independent bank statement within the supplied files.
5. **Low — copied narrative labels:** several distinct Tromol dates share the same “10 Desember 2025” description.

## 10. Recommended source-file remediation (not performed)

1. Preserve both supplied workbooks unchanged as source archives.
2. Obtain the authoritative cash-count/receipt evidence for 14 June 2026 and a documented explanation or correction for the `1,200,000` difference before choosing a Cash Tromol Yatim balance.
3. Restore the two missing formula inputs from the authoritative original source, or retain a permanent local source schedule with cell-level provenance. Do not replace `#REF!` with unverified hard-coded figures.
4. Recalculate and independently verify the cash-book total, net book balance, BNI reconciliation, each fund balance, and grand total after the source-owner repair.
5. Correct the duplicated Tromol descriptions without altering the associated dates or amounts unless supported by evidence.
6. Only after the preceding items are resolved should an authorized owner decide whether a signed source reconciliation is suitable for a Financial V2 opening-balance rehearsal. This report makes no such decision.

## 11. Financial V2 and database isolation

No Financial V2 table, legacy financial table, local development database, or test database was read or written. Specifically, no master data, policy, posting rule, transaction, attachment, audit entry, Journal, JournalLine, Ledger, opening balance, or migration was created, modified, deleted, deactivated, imported, or recalculated.

## 12. Final readiness

**ZISWAF SOURCE READINESS = NOT READY**

The original dependency enables diagnostic re-performance of the two missing allocation values and supports the stated BNI amount. Nevertheless, the revised workbook contains material `#REF!` errors and the source set contains an unresolved `1,200,000` Cash Tromol Yatim conflict. The revised workbook therefore is not a reliable, self-contained, auditable source for Financial V2 onboarding, opening-balance work, or operational balance adoption.

## 13. Stop condition

Phase 11.1B stops at this report as required. No source repair, database write, Financial V2 import, or onboarding action has been performed.
