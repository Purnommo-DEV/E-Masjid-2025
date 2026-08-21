# Financial Phase 10.3 — Fund Balance Reporting Fix

## 1. Root Cause

`FinancialReportService::fundBalances()` sebelumnya menjumlahkan semua `ledger.signed_amount` yang memiliki `fund_id` sebagai `closing_net_position`. Karena satu penerimaan menghasilkan baris kas dan pendapatan, serta satu pengeluaran menghasilkan baris beban dan kas, pendekatan tersebut menghitung posisi kas bersama pendapatan/beban dan menggandakan Saldo Dana.

## 2. Existing Formula

Formula lama adalah `SUM(ledger.signed_amount)` untuk seluruh baris posted yang memiliki Fund. Contoh Rp20.000.000 penerimaan dan Rp5.000.000 penggunaan menghasilkan Rp40.000.000 (`kas 15 + pendapatan 20 + beban 5`) alih-alih Rp15.000.000.

## 3. Approved Business Contract

Laporan membedakan:

- **Saldo Dana**: posisi bersih Fund.
- **Likuiditas Tersedia**: uang Fund pada rekening/kas.
- **Komposisi Rekening**: rincian Likuiditas Tersedia menurut Financial Account.

Transfer rekening/kas tidak mengubah Saldo Dana. Inter-Fund Transfer mengurangi Fund asal dan menambah Fund tujuan, tanpa mengubah total seluruh Fund.

## 4. Correct Formula

Saldo Dana dihitung dari Posted V2 Ledger sebagai:

`opening + revenue - expense + inter-fund-in - inter-fund-out + adjustment + other policy-defined components`

Implementasi memakai kontribusi ledger berikut: `revenue` menambah, `expense` mengurangi, `net_asset` mengikuti normal-balance ledger, dan baris akun `transfer` berjenis IFT memakai `debit_amount - credit_amount`. Baris asset/liquidity tidak ditambahkan ke Saldo Dana.

`other_policy_components` mengekspos selisih rekonsiliasi dari komponen Fund lain yang valid, misalnya opening balance yang diposting pada tanggal awal report; nilainya tidak dibuat atau di-cache oleh reporting layer.

## 5. Ledger Source

Semua angka berasal dari `PostedLedgerQuery`, yang hanya membaca `financial_v2_ledger_entries` dengan Journal berstatus `posted`, provenance JournalLine, voucher, dan lineage reversal. Tidak ada pembacaan tabel legacy, transaksi legacy, cache saldo paralel, atau writer accounting fact.

## 6. Fund Balance

Payload Fund row sekarang memiliki `opening_fund_balance`, `fund_balance`, `receipts`, `expenses`, `transfer_in`, `transfer_out`, `adjustments`, dan `other_policy_components`. Alias lama `opening_net_position`, `closing_net_position`, serta `usage` dipertahankan untuk kompatibilitas dan sekarang mengandung semantik yang benar.

## 7. Available Liquidity

`available_liquidity` adalah penjumlahan hanya dari posted liquidity lines yang memiliki Fund dan Financial Account. Nilai ini tidak digunakan untuk membentuk atau menambah Saldo Dana.

## 8. Account Composition

`account_composition` menyajikan Fund-by-Financial-Account dengan `liquidity_balance`. `liquidity_distribution` dipertahankan sebagai alias kompatibilitas. Jumlah komposisi diregresi agar sama dengan `available_liquidity`.

## 9. Transfer Treatment

Transfer treasury Bank–Kas tetap hanya memindahkan likuiditas antar Financial Account. Ia tidak menghasilkan revenue, expense, transfer Fund, maupun perubahan Saldo Dana.

## 10. Interfund Treatment

IFT tidak lagi menentukan arah dari `signed_amount`, karena akun transfer debit dan kredit dapat sama-sama memiliki nilai normal-balance positif. Arah ditentukan dari debit/kredit JournalLine: debit adalah transfer masuk, kredit adalah transfer keluar. Fund movement report memakai kontribusi Saldo Dana yang sama.

## 11. Regression Tests

`FundBalanceReportingTest` menutup:

- Rp20.000.000 penerimaan dan Rp5.000.000 penggunaan = Rp15.000.000 Saldo Dana.
- Transfer treasury tidak mengubah Saldo Dana dan hanya mengubah komposisi rekening.
- IFT Rp20 mengurangi Fund asal, menambah Fund tujuan, dan mempertahankan total Rp100.
- Komposisi rekening sama dengan Likuiditas Tersedia.

Regression tambahan memverifikasi API/UI report, Friday report, ZISWAF, Program report, Trial Balance, immutable Ledger, opening balance, dan adjustment.

## 12. UI Changes

Laporan dan detail Fund memakai label **Saldo Dana**, **Likuiditas tersedia**, dan **Komposisi Rekening**. Card Dana pada dashboard/index yang bersumber dari rekening/kas kini disebut Likuiditas Dana, bukan Saldo Dana. Mutasi Dana menampilkan Dampak Dana dan Saldo Dana berjalan.

## 13. API Changes

Fund report JSON menyediakan `fund_balance`, `available_liquidity`, `receipts`, `expenses`, `transfer_in`, `transfer_out`, `adjustments`, dan `account_composition`. Bagian `compatibility.deprecated_aliases` mendokumentasikan alias payload lama yang masih tersedia.

## 14. Before / After Example

| Scenario | Sebelum | Sesudah |
| --- | ---: | ---: |
| Penerimaan Zakat Maal | Rp20.000.000 | Rp20.000.000 |
| Penggunaan Zakat Maal | Rp5.000.000 | Rp5.000.000 |
| Saldo Dana | Rp40.000.000 (salah) | Rp15.000.000 |
| Likuiditas Tersedia | Rp15.000.000 | Rp15.000.000 |

## 15. Remaining Gaps

Opening balance yang diposting ke `net_asset` telah tercakup. Jika konfigurasi masa depan memakai Fund-attributed `liability` atau `control` sebagai komponen Saldo Dana, report mengembalikan `fund_balance_scope.has_unmapped_policy_component_gap = true` dan kelas terkait. Tidak ada asumsi mapping atau formula baru yang dibuat untuk kelas tersebut; keputusan accounting policy diperlukan sebelum komponen itu dihitung sebagai Saldo Dana.

## Validation

- Financial V2 suite: **87 passed, 605 assertions**.
- Full baseline: **111 passed, 666 assertions**, **1 risky** `ExampleTest` yang sudah ada, **0 failed**, **0 skipped**.
- PHP lint, Pint, Blade cache, and `git diff --check`: passed.
- Tidak ada perubahan pada Posting Engine, Journal, JournalLine, Ledger, Financial Account architecture, Program, legacy financial table, migration legacy, cutover, atau dual-write.

## Gate

**FINANCIAL V2 FUND REPORTING = PASS.**

