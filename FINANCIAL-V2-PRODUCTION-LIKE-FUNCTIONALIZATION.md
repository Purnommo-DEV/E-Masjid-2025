# Financial V2 Production-Like Functionalization

**Scope:** local/development Financial V2 usability for Masjid Raudhotul Jannah.
**Environment:** `local`, development database `mrj_prod_db`; automated tests use `mrj_test_db`.
**Status:** **FINANCIAL V2 = READY FOR DAILY OPERATION (LOCAL/DEVELOPMENT)**

## Safety and accounting boundaries

- The provisioning command is explicitly local-only and refuses any database other than `mrj_prod_db`.
- It is additive and idempotent: no `migrate:fresh`, truncate, rollback, mass delete, legacy write, dual-write, or cutover was used.
- Existing Financial V2 data is preserved. An obsolete unreferenced SAMPLE/QA liquidity GL setup is made inactive rather than deleted; it is not used by a financial account or a posted JournalLine.
- Financial facts are created through the transaction lifecycle and then posted by the canonical Posting Engine. Direct Journal, JournalLine, and LedgerEntry creation remains confined to `PostingEngine`.
- Reports and dashboard values use Posted V2 Ledger facts only. Legacy financial tables are not read or written.

## QA access and master data

The local QA account `superadmin@emasjid.com` was verified through the authenticated UI. Its credential is local QA configuration only; no production dependency was introduced.

The command `financial-v2:provision-local-qa --with-sample-transactions` provisions an explicitly labelled `MRJ-SAMPLE-QA` accounting entity. A repeat execution remains at the same fact count.

| Master | SAMPLE/QA count | Included examples |
| --- | ---: | --- |
| Rekening/Kas | 9 | Bank Operasional, BNI ZISWAF, Bank Qurban, Bank Sosial/Kematian, Bank Sewa Aula, Cash Operasional, Cash ZISWAF |
| Dana | 11 | Operasional, Zakat Maal, Infaq/Tromol, Sodaqoh, Fidyah, Dhuafa, Santunan Anak Yatim, Qurban, Ramadhan, Sosial/Kematian, Sewa Aula |
| Program | 7 | Operasional Masjid, Santunan Dhuafa, Santunan Anak Yatim Bulanan, Qurban, Ramadhan, Sosial & Kematian, Sewa Aula |
| Kategori | 7 | Penerimaan Dana, Biaya Operasional, Santunan, Qurban, Ramadhan, Sosial/Kematian, Sewa Aula |
| Posted SAMPLE/QA transactions | 22 | Receipts, payments, transfers, and three monthly Yatim realizations |

Restricted funds have effective local SAMPLE/QA policy versions. Their permitted program matrix is enforced server-side and fails closed. These policies are clearly local sample policy and do not invent a production policy.

## Operational workflows and balances

All amounts below are derived from Posted V2 Ledger through the reporting service.

| Workflow | Receipt | Payment | Closing fund balance |
| --- | ---: | ---: | ---: |
| Operasional Jumat (14 Aug 2026) | Rp3.000.000 | Rp750.000 | Rp2.250.000 |
| Zakat Maal | Rp20.000.000 | Rp5.000.000 | Rp15.000.000 |
| Qurban | Rp30.000.000 | Rp25.000.000 | Rp5.000.000 |
| Ramadhan | Rp5.000.000 | Rp1.500.000 | Rp3.500.000 |
| Sosial/Kematian | Rp3.000.000 | Rp1.000.000 | Rp2.000.000 |
| Sewa Aula | Rp2.000.000 | Rp500.000 | Rp1.500.000 |

The Zakat Bank-to-Cash transfer is voucher `TRF-00000001` for Rp5.000.000. Its two liquidity lines are equal and opposite, each has `fund_balance_delta = Rp0`, and it creates neither income nor expense.

The Yatim workflow has three approved monthly allocations (`202606`, `202607`, and `202608`). Each allocation is Rp10.000.000 and each has one posted realization of Rp10.000.000, representing 100 recipients × Rp100.000 without creating 100 Journals. Allocation and approval themselves create no Journal or Ledger.

Current SAMPLE/QA liquidity totals reconcile to Rp29.250.000: BNI ZISWAF Rp15.000.000, Bank Qurban Rp5.000.000, Bank Operasional Rp3.500.000, Cash Operasional Rp2.250.000, Bank Sosial/Kematian Rp2.000.000, Bank Sewa Aula Rp1.500.000, and Cash ZISWAF Rp0.

The Friday report tied out from Posted V2 Ledger: opening Rp0 + receipts Rp3.000.000 - payments Rp750.000 = closing Rp2.250.000.

## Usability and defect corrections

- Financial V2 sidebar contains Dashboard, Penerimaan, Pengeluaran, Transfer, Dana, Alokasi Dana, Riwayat Transaksi, Laporan, Kontrol, and Master Keuangan. It does not expose Journal, JournalLine, Ledger, CoA, or Posting Engine.
- Operator forms use date, amount, cash/bank account, fund, category, program, description, and evidence. They do not ask for debit, credit, JournalLine, Ledger, or posting accounts.
- Dashboard and Fund list now show **Saldo Dana** independently from **Likuiditas rekening/kas**. This corrects the prior risk of presenting liquidity composition as a fund balance.
- Receipt categories are now constrained to the selected transaction type, preventing payment categories from appearing in a receipt form.
- Friday report rendering no longer attempts to print its structured report definition as a string.

Desktop QA was completed through the authenticated local UI. The dashboard had no horizontal overflow at the available desktop viewport, and the receipt form exposed the expected non-accounting fields. The available in-app browser surface did not provide a supported viewport-resize control, so physical mobile-device rendering remains a manual release check; no mobile defect was observed or inferred.

## Integrity and validation

Direct local V2 checks returned:

- 22 posted Journals, 44 JournalLines, and 44 Ledger entries.
- 0 unbalanced Journals.
- 0 orphan Ledger entries.
- 0 duplicate vouchers.
- Treasury transfer Fund impact: Rp0.
- Friday report tie-out: true.
- Cash flow tie-out: opening Rp0 + cash-in Rp93.000.000 - cash-out Rp63.750.000 + internal transfers Rp0 = closing Rp29.250.000.
- Trial Balance: debit Rp191.750.000 equals credit Rp191.750.000.

Validation completed:

- `php artisan test --stop-on-failure`: **111 passed, 676 assertions, 0 failed, 0 skipped**. One existing unrelated `ExampleTest` remains risky.
- PHP lint: passed for the changed PHP files.
- Pint: passed for the changed PHP files.
- `php artisan view:cache`: passed.
- `git diff --check`: passed.
- Canonical-writer audit: direct financial-fact writes occur only in `PostingEngine`.
- Legacy-isolation audit: no Financial V2 flow reads or writes legacy financial tables.

## Remaining limitations

- All new masters, policy records, evidence metadata, and transactions are explicitly **SAMPLE/QA** local development data. They must be replaced or governed independently before any real operational rollout.
- No production cutover, legacy migration, or production master-data assertion was performed.
- Browser viewport tooling prevented a true narrow-mobile visual run; the responsive mobile experience should be confirmed on a device or browser surface that supports viewport emulation before a production release.
