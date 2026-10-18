# Accounting Date Correction — Execution Readiness

> Exception G-01 yang disahkan untuk sembilan kandidat memungkinkan satu akun SuperAdmin aktual menjalankan maker/checker/executor. Exception ini scoped, diaudit, dan tidak boleh dilaporkan sebagai pemenuhan `three_distinct`. Seluruh gate finansial lain tetap wajib lulus.

## Status implementasi

Workflow saat ini belum mempunyai executor reversal-and-replacement yang aman. Komponen yang tersedia adalah:

- workflow request, reevaluation, maker-checker approval, cancellation, snapshot, fingerprint, lineage original, dan audit trail;
- planner finansial read-only yang memakai compiler canonical `PostingEngine`;
- simulasi timeline likuiditas, dampak laporan, dan fingerprint plan;
- evaluator kandidat read-only.

`governance_approved` bukan izin posting. `execution_ready` dan `executing` tidak boleh dicapai selama seluruh prasyarat berikut belum tersedia dan tervalidasi ulang dalam satu transaksi database.

## Blocker executor

1. Fondasi workflow replacement `FundRealization` sudah tersedia, tetapi instance approved/materialized untuk kandidat belum ada. Realization original tidak boleh dipindahkan atau ditulis ulang.
2. Fondasi lineage `Distribution` finalized menuju replacement realization sudah tersedia, tetapi overlay kandidat belum dimaterialisasi.
3. Planner masih menandai approval dan evidence replacement sebagai belum materialized.
4. Fondasi workflow koreksi kelompok untuk multi-row bank batch sudah tersedia, tetapi batch kandidat, execution-pair per item, dan kontrak eksekusi atomiknya belum execution-ready.
5. Persetujuan bulat governance telah dikonfirmasi, tetapi pilihan final dan actor matrix `three_distinct` belum terdokumentasi sebagai policy executable. Konfigurasi REV harus tetap aktif melalui workflow resmi dan guard tetap fail-closed sampai metadata tersebut tersedia.
6. Belum ada orchestration atomik yang membuat transaksi reversal dan replacement, memanggil `PostingEngine` dua kali, mengisi lineage write-once, lalu menyelesaikan request tanpa celah partial execution.

Karena blocker tersebut, menambahkan endpoint eksekusi atau mengubah request menjadi `execution_ready` akan memberikan jaminan palsu dan tidak diperbolehkan.

## Kontrak executor yang masih harus dibangun

Executor berikutnya wajib, dalam urutan locking canonical dan satu transaksi koneksi yang sama:

1. Mengunci request, transaksi/jurnal original, periode asal/tujuan, Allocation, Realization, Distribution, rekonsiliasi, batch bank, konfigurasi REV, approval, evidence, dan lineage.
2. Mengulang preview dari snapshot database terkunci dan memverifikasi fingerprint approval terhadap plan terbaru.
3. Memastikan plan menyatakan simulasi lengkap, likuiditas lengkap, net-zero per seluruh dimensi, serta `execution_ready=true`.
4. Membuat transaksi reversal dan replacement melalui lifecycle resmi, bukan insert Journal/Ledger manual.
5. Memanggil `PostingEngine` dengan idempotency key berbeda tetapi diturunkan secara deterministik dari correction request.
6. Memverifikasi hasil JournalLine terhadap plan yang disetujui sebelum commit.
7. Mengisi lineage reversal/replacement satu kali melalui `AccountingDateCorrectionLineageGuard`.
8. Memperbarui replacement Realization dan hubungan Distribution melalui workflow resmi.
9. Menyimpan decision/audit execution dan mengubah `executing` menjadi `completed` dalam transaksi yang sama.

Kegagalan tahap mana pun harus me-rollback transaksi reversal, replacement, voucher, jurnal, ledger, lineage, status request, decision, dan audit execution secara keseluruhan.

## Evaluator sembilan kandidat

`AccountingDateCorrectionCandidateEvaluator` bersifat read-only. Evaluator tidak membuat request, tidak mereservasi voucher, tidak memanggil posting, dan tidak mengubah master. Hasil hanya berlaku untuk database yang diperiksa; ketidakhadiran transaksi menghasilkan `needs_governance_review`, bukan asumsi bahwa transaksi tidak ada pada environment lain.

# Status implementasi executor (15 Oktober 2026)

Executor individual dan batch kini memiliki jalur source canonical melalui
`AccountingDateCorrectionExecutionOrchestrator` dan primitive atomik
`PostingEngine`. Batch mengikat setiap item ke Execution Pair secara immutable,
mem-posting seluruh reversal lalu seluruh replacement dalam satu transaction
boundary, dan mematerialisasi Replacement Realization/Distribution lineage di
boundary yang sama. Endpoint eksekusi hanya mengambil actor dari sesi
SuperAdmin terautentikasi.

Ketersediaan executor **bukan** persetujuan eksekusi. G-04 dan G-05 pada
Governance Decision Register masih menyatakan bahwa pilihan per Allocation/Fund
Policy dan status counterparty belum terdokumentasi. Canonical evaluator tetap
fail-closed dengan `GOVERNANCE_POLICY_NOT_IMPLEMENTABLE`; tidak ada exception
effective-date/counterparty yang boleh dibentuk sampai pilihan tersebut menjadi
fakta approval yang dapat difingerprint. Evidence bank baru juga wajib terdaftar
melalui storage/UI aplikasi sebelum preview operasional dan posting dilakukan.

