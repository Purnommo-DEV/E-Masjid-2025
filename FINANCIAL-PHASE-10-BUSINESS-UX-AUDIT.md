# Financial Phase 10 — Final Business & UX Audit

**Audit date:** 2026-08-10  
**Scope:** Financial V2 business terminology and operational UX only.  
**Data safety:** No migration, seed, cutover, production access, legacy-table mutation, or dual-write was performed.

## 1. Executive Summary

The Financial V2 operational screens were polished so mosque officers work with **penerimaan, pengeluaran, rekening, dana, program, alokasi, realisasi, saldo,** and **laporan**. Accounting internals remain available only through progressive disclosure.

The Financial V2 changes are statically sound, but final dynamic validation is blocked by an unrelated application bootstrap fault: `routes/web.php` imports `App\Http\Controllers\User\DokumentasiEvaluasiController`, while Composer expects a missing `DokumentasiEvaluasiController.php`. The existing file is named `DokumentasiEvaluasigGuestController.php`. This audit did not alter that non-Financial blocker.

## 2. Business Model Validation

The UI preserves the approved distinctions:

- **Rekening** is where money is held (bank or cash).
- **Dana** is its source or permitted purpose.
- **Program** is the activity it supports, not a bank account or balance.
- **Akun/COA** remains an internal translation dimension.

The new Dana page explicitly communicates these distinctions. No dimension was merged, inferred, or reinterpreted.

## 3. Terminology Audit

Primary wording now uses Indonesian business terms: `Rekening`, `Dana`, `Alokasi Dana`, `Realisasi Dana`, `Riwayat Transaksi`, `Tutup Periode`, and `Rekonsiliasi Rekening`.

`Journal`, `Ledger`, identifiers, debit, and credit are no longer dominant in normal operational screens. They are retained in `Detail Akuntansi` and the specialized `Neraca Saldo` detail, where they are legitimately needed for accountability.

## 4. Navigation Audit

The primary navigation is now:

`Dashboard · Penerimaan · Pengeluaran · Transfer · Dana · Alokasi Dana · Riwayat Transaksi · Laporan · Kontrol`

Saldo Awal is no longer a primary navigation item. It is available from Kontrol as an advanced, governed rehearsal/migration activity. Active navigation for each transaction operation is explicitly selected from the route parameter.

## 5. Dashboard Audit

The dashboard prioritizes cash/rekening balance, fund balance, period receipts, period payments, transfers, recent transactions, period status, and outstanding reconciliation count. It describes balances as derived from completed official records rather than exposing ledger mechanics.

No “fund almost exhausted” indicator was added because the approved design has no governance-approved warning threshold. Inventing one would be a business-policy change.

## 6. Receipt UX

The receipt flow retains clear operational inputs for source, amount, destination rekening, dana, category, optional program, date, description, and supporting evidence. Users do not select debit or credit accounts. The Posting Engine remains the sole accounting translator.

## 7. Payment UX

The payment flow remains centred on recipient, amount, source rekening, dana, category, optional program, date, description, and `Lampiran bukti`. Existing domain validation returns an Indonesian, actionable restricted-fund message instead of an internal policy exception.

## 8. Transfer UX

The transfer flow continues to state that a transfer moves money between rekening and is not income or expenditure. The approved transfer accounting remains unchanged and preserves the selected fund.

## 9. Fund UX

A new top-level **Dana** page lists active funds with canonical liquidity-derived balances. It introduces Rekening, Dana, and Program in plain language, and does not fabricate balances for unavailable data.

## 10. Allocation UX

The page makes the governed workflow explicit:

`Ajukan → Setujui → Realisasi`

It explains that allocation is a plan and does not reduce cash/rekening balance. Statuses are displayed as `Draft`, `Diajukan`, and `Disetujui`, with realization progress derived from the actual approved version rather than a new state model.

## 11. Realization UX

For an approved allocation, the realization selector and preview show total allocation, realized amount, and remaining amount. Client-side feedback warns: `Nominal realisasi melebihi sisa dana yang tersedia.` Server-side availability validation remains authoritative and rejects an overrun before a financial fact is posted.

## 12. Transaction History

The history screen now presents business statuses (`Dicatat resmi`, `Dikirim`, `Dalam pemeriksaan`, `Disetujui`, `Ditolak`, `Dibatalkan`, `Dibalik`) and uses `Referensi` instead of implying that every source reference is a voucher. It keeps filters for rekening, dana, program, category, date, type, and status.

## 13. Transaction Detail

The default detail remains a transaction summary with business dimensions, evidence, and status. Journal ID, posting-rule version, debit, credit, and book references are behind the collapsed `Detail Akuntansi` section.

## 14. Reporting UX

Report labels use `Saldo Dana`, `Mutasi Dana`, `Neraca Saldo`, and `Laporan Keuangan`. Report data is explicitly read-only and sourced from official recorded facts. Technical JSON, journal IDs, line counts, and debit/credit detail are placed behind accounting-detail disclosure rather than the primary report reading path.

## 15. Friday Report

The Friday operational report presents `Saldo awal`, `Penerimaan`, `Pengeluaran`, and `Saldo akhir`. Its read model remains based on the Posted General Ledger. The exact Friday classification definition is still configurable governance, as documented in the existing reporting foundation; this audit did not infer a new classification policy.

## 16. ZISWAF UX

ZISWAF reporting remains fund-by-fund, not a single automatically inferred aggregate. Users select governed funds and see separate rows for the funds configured by MRJ, such as Zakat Maal, Infaq/Tromol, Fidyah, Dhuafa, or Santunan Yatim. The system deliberately does not infer ZISWAF from a fund or program name.

## 17. Account vs Fund vs Program

The Dana landing page and fund detail explain:

- `Di mana uangnya?` → Rekening.
- `Uang ini milik/peruntukan apa?` → Dana.
- `Uang digunakan untuk kegiatan apa?` → Program.

The fund detail also displays the rekening distribution for the selected fund and program-specific allocation intent, preserving distinct dimensions.

## 18. Closing UX

Kontrol uses `Tutup Periode`, `Penutupan awal`, and `Penutupan final`, with human-readable statuses `Terbuka`, `Dalam penutupan`, and `Ditutup`. It explains that final closure follows reconciliation without creating new transactions from the control screen.

## 19. Reconciliation UX

The control screen uses `Saldo menurut rekening atau kas`, `Saldo menurut sistem`, and `Selisih`. Reconciliation results use `Sesuai`, `Ada selisih`, and `Perlu tindak lanjut`, instead of book/statement-balance terminology.

## 20. Evidence UX

Operational forms use `Lampiran bukti` and explain that evidence required by a transaction rule must be supplied before official recording. Transaction evidence remains linked to the transaction audit trail.

Direct attachment to a standalone allocation was not invented: the approved attachment taxonomy has no `budget_allocation` target and allocation is not a posted financial fact. The UI clearly directs officers to attach payment proof to the realization where money is actually paid.

## 21. Mobile UX

Static markup inspection confirms responsive grids, mobile transaction cards, wrapping actions, responsive navigation, and `overflow-x-auto` only around data tables. Forms use full-width controls and decimal input modes.

Pixel checks at 320, 375, 390, and 430px could not be completed. The in-app browser could not connect to a local listener (`ERR_CONNECTION_REFUSED`), and `php artisan serve` exited immediately in this environment. This is recorded as an environment limitation pending the bootstrap repair; it is not classified as a Financial V2 design defect.

## 22. Error UX

Existing Financial V2 domain errors remain translated into short Indonesian operational messages, including restricted-fund rejection, closed period, unavailable allocation, and invalid program selection. The new realization preview supplements—rather than replaces—the server-side overrun error.

## 23. Empty State

Zero-data reporting now says `Belum ada data pada periode ini.` Operational lists use user-facing empty messages and do not reveal table names or journal-line implementation details.

## 24. Consistency

The audited screens consistently use Indonesian labels, alert styles, badges, buttons, filters, cards, and progressive disclosure. `Dicatat resmi` is used in place of the raw `posted` state in normal-facing transaction screens.

## 25. Business Scenario Review

| Scenario | Workflow and accounting assessment |
| --- | --- |
| Penerimaan Jumat | Receipt form and Friday report use business labels; official posting remains canonical. |
| Donasi ke rekening ZISWAF | Rekening destination and Dana remain separate inputs/dimensions. |
| Zakat Maal diterima | Governed fund selection and fund-by-fund ZISWAF reporting remain intact. |
| Dana Zakat Maal digunakan | Existing restricted-fund rule check rejects prohibited use before posting. |
| Dana Fidyah diterima | Handled as a separate governed fund, never inferred from its name. |
| Dana Dhuafa disalurkan | Payment/realisasi keeps dana, category, optional program, and proof separate. |
| Santunan Yatim | Program can identify activity without becoming the fund or rekening. |
| Pembelian operasional | Payment flow keeps source rekening, Dana, and category clear. |
| Pembayaran kegiatan | Optional Program remains available for activity attribution. |
| Sewa Aula | A dedicated governed fund can remain separate in the Dana screen. |
| Transfer antar rekening | Explicitly not income or expenditure; preserves fund dimension. |
| Alokasi dana program | Allocation is a plan and has no Journal/Ledger effect. |
| Realisasi alokasi | Shows balance of allocation; approved server rule rejects overspend. |
| Closing bulanan | Kontrol exposes human-readable period status and governed close actions. |
| Rekonsiliasi rekening | Officer compares account/cash amount to system amount and records proof. |

Dynamic re-execution of these scenarios is pending the unrelated bootstrap repair described below. Previous Phase 9 validation remains the latest executed evidence.

## 26. Issues Found

1. Dana did not have a dedicated primary page, and the navigation exposed Saldo Awal too prominently.
2. Several normal screens exposed internal terms such as Fund, Journal, Ledger, Posted, Trial Balance, and Tie-out.
3. Allocation status showed internal codes and realization selection lacked total/actual/remaining guidance.
4. The dashboard did not surface period/reconciliation attention indicators.
5. The application bootstrap currently fails due to a non-Financial controller filename/class autoload mismatch. This blocks Laravel route, test, and browser verification.

## 27. Issues Fixed

1. Added Dana navigation, fund list, and fund detail using read-only canonical balance/report queries.
2. Reworded navigation, dashboard, history, forms, controls, reporting, and opening-balance headings in business Indonesian.
3. Moved technical transaction/report details behind `Detail Akuntansi` disclosure.
4. Added allocation availability summaries and realization overrun preview while retaining authoritative server rejection.
5. Added period status and unresolved reconciliation attention cards to the dashboard.
6. Added focused UX regression assertions for navigation, dimension separation, fund pages, allocation availability, evidence wording, and new report/controls wording.

## 28. Remaining Limitations

- **Critical verification blocker:** Laravel cannot bootstrap because `DokumentasiEvaluasiController` resolves to a missing filename. This is outside Financial V2 scope and requires a separate, narrow maintenance decision.
- Browser localhost access is unavailable because the local server exits; responsive pixel QA is therefore pending after the bootstrap repair.
- No fund-low-balance threshold is configured in approved policy, so no warning threshold is shown.
- Standalone allocation evidence is not supported by the approved attachment taxonomy; realization evidence remains the supported audit proof.
- Friday report classifications remain governance-configurable, not auto-inferred.

## 29. Final Recommendation

**FINANCIAL V2 BUSINESS & UX READINESS = NOT READY** for a final release gate **only because the current application cannot bootstrap and therefore cannot execute the required Financial V2/full regression suite or live visual QA**.

This is not an accounting-integrity, fund-leakage, balance, reporting, posting, legacy-isolation, or Financial V2 architecture defect. Once a separately approved narrow repair restores the unrelated controller autoload contract, rerun:

1. `php artisan test tests/Feature/FinancialV2/OperationalUxTest.php`
2. `php artisan test`
3. Financial V2 direct suite and concurrency rehearsal
4. Browser desktop/tablet/mobile checks at 320, 375, 390, and 430px

If those checks pass, the Financial V2 Business & UX readiness should be reassessed without changing the approved accounting architecture.

## Validation Evidence Recorded in This Audit

- PHP lint passed for the changed controller, report service, routes, control controller, and focused UX test.
- `vendor/bin/pint --test` passed for the changed PHP files.
- `git diff --check` passed.
- Canonical writer scan found `Journal::create`, `JournalLine::create`, and `LedgerEntry::create` only in `app/Domain/FinancialV2/PostingEngine.php`.
- Legacy runtime access scan found no legacy-table/model access in Financial V2 runtime namespaces, aside from the intentional string markers in the read-only preflight scanner.
- `php artisan route:list --name=financial-v2.funds` failed before route evaluation with the unrelated controller autoload error.
- Targeted `php artisan test tests/Feature/FinancialV2/OperationalUxTest.php` did not produce test results within 64 seconds and was stopped; the route bootstrap failure is the known blocker requiring separate repair.
- In-app browser check to `http://127.0.0.1:8018/admin/keuangan-v2` returned `ERR_CONNECTION_REFUSED`; the temporary local server exited and was not left running.
