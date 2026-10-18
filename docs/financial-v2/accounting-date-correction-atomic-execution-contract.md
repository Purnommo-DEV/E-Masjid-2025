# Accounting Date Correction — Atomic Execution Contract

Dokumen ini mendefinisikan prasyarat executor berikutnya. Implementasi execution pair saat ini tidak melakukan posting.

## Batas transaksi

Satu koreksi individual harus memakai satu transaksi database dan satu koneksi untuk urutan berikut:

1. lock Entity, request, approval, source transaction/journal, period, evidence, configuration, pair, Allocation/Realization/Distribution, dan reconciliation;
2. kompilasi ulang plan canonical dan verifikasi fingerprint approval serta execution pair;
3. posting REV melalui `PostingEngine`;
4. posting replacement melalui `PostingEngine`;
5. materialisasi Replacement Realization dan Distribution Correction Lineage;
6. pengisian write-once Accounting Date Correction Lineage;
7. perubahan status pair/request ke completed dan audit event.

Batch bank membungkus kelima urutan individual tersebut dalam transaksi database luar yang sama. Tidak boleh ada commit per item.

Setiap exception pada reservasi idempotency, voucher, reversal, replacement, Realization, Distribution overlay, lineage, atau audit event harus me-rollback seluruh unit. Retry setelah commit wajib mengembalikan hasil berdasarkan idempotency dan lineage yang sama, bukan menerbitkan voucher atau jurnal baru.

## Perubahan minimum yang masih diperlukan

`PostingEngine::post()` saat ini memiliki transaction runner dan pencatatan kegagalan sendiri. Nested transaction Laravel dapat memakai koneksi yang sama, tetapi kontrak publiknya belum membuktikan bahwa dua posting dan seluruh lineage selalu menjadi satu unit. Sebelum executor dibuka diperlukan API canonical, misalnya operasi internal `postWithinCorrectionExecution(...)`, yang:

- mensyaratkan transaksi database luar aktif;
- tidak melakukan commit mandiri;
- memakai compiler, validator, idempotency reservation, voucher, dan `commitPosting()` yang sama;
- mengembalikan result tanpa menulis failure event di luar unit transaksi;
- menerima source journal/fingerprint yang sudah dikunci dan menolak drift;
- tetap tidak mengekspos `commitPosting()` kepada controller atau service umum.

Financial Transaction lifecycle juga memerlukan factory governed untuk menyiapkan REV dan replacement dari approved execution-pair snapshot. Factory harus membuat transaksi/split/evidence/approval melalui lifecycle canonical, mengikat UUID secara write-once, dan tidak mem-posting. Sampai API tersebut dan kebijakan `three_distinct` disahkan, status `staged` maupun `governance_approved` tidak boleh menghasilkan `execution_ready`.

## Invariant

- original Transaction, Journal, JournalLine, LedgerEntry, Realization, Distribution, dan DistributionItem immutable;
- tepat satu reversal journal untuk satu source journal;
- tepat satu active execution pair untuk satu original transaction;
- tepat satu execution pair untuk satu correction request dan satu batch item;
- replacement tidak menggandakan penerima atau nominal pelaporan;
- batch tidak memiliki API eksekusi per-item;
- `execution_ready` hanya boleh diberikan setelah revalidasi terkunci seluruh aggregate dan actor policy yang telah disahkan.
