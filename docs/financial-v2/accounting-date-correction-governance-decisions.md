# Dokumen Keputusan Governance — Accounting Date Correction September 2026

## 1. Identitas dan tujuan dokumen

| Atribut | Nilai |
|---|---|
| Ruang lingkup | Sembilan transaksi Financial V2 yang diusulkan berpindah pengakuan akuntansinya ke September 2026 |
| Jenis dokumen | Matriks keputusan untuk ditinjau dan disahkan pihak berwenang |
| Status | **Disetujui bulat oleh seluruh pengurus; pilihan kebijakan final belum terdokumentasi lengkap untuk implementasi** |
| Dampak dokumen | Menentukan prasyarat governance sebelum desain executor reversal-and-replacement boleh dilanjutkan |
| Bukan kewenangan dokumen ini | Mengaktifkan REV, mengubah master, membuat correction request, atau mengotorisasi posting |

Dokumen ini menggunakan audit `AccountingDateCorrectionCandidateEvaluator`, desain Replacement Realization, Distribution Correction Lineage, Accounting Date Correction Batch, dan dokumen [execution readiness](accounting-date-correction-execution-readiness.md) sebagai sumber teknis.

Persetujuan bulat G-01 sampai G-08 telah dikonfirmasi. Namun, lembar pengesahan yang ada tidak mencatat alternatif yang dipilih, role matrix, metadata pengesah, tanggal, atau evidence reference. Seluruh sembilan kandidat tetap `blocked`; dokumen ini tidak menyatakan satu pun transaksi eligible dan tidak membuktikan bahwa pengakuan September benar secara akuntansi.

## 2. Prinsip yang tidak boleh dinegosiasikan

1. Transaksi, jurnal, JournalLine, LedgerEntry, dan timestamp posting original tetap immutable.
2. Koreksi dilakukan melalui reversal-and-replacement canonical, bukan `UPDATE accounting_date`.
3. Approval dan effective date tidak boleh di-backdate hanya agar transaksi lolos validasi.
4. Distribution finalized, DistributionItem, penerima, nominal, dan identity snapshot original tidak ditulis ulang.
5. `governance_approved` tidak sama dengan `execution_ready`.
6. Tidak ada eksekusi sebelum seluruh keputusan dalam dokumen ini disahkan dan diwujudkan dalam konfigurasi/workflow resmi.
7. Seluruh posting harus melalui `PostingEngine`, atomik, idempotent, dan dapat diaudit.

## 3. Daftar kandidat

| No. | Transaction UUID | Nominal | Tanggal aktual efektif | Accounting date kini | Target | Kelompok |
|---:|---|---:|---|---|---|---|
| 1 | `a2eec515-6015-4c9d-8344-2deb3819385a` | Rp6.600.000 | 13/09/2026 | 11/10/2026 | 13/09/2026 | Penyaluran/Realization |
| 2 | `a2eef5b0-6485-40d1-9f03-8e9fd0510f98` | Rp21.500.000 | 26/09/2026 | 08/10/2026 | 26/09/2026 | Penyaluran/Realization |
| 3 | `a2eefd9f-9976-4348-b42f-7b66d50879a6` | Rp375.000 | 27/09/2026 | 01/10/2026 | 27/09/2026 | PAY biasa |
| 4 | `a2ef0da6-1ca3-4216-ac8f-38ac6f3ab571` | Rp2.500 | 30/09/2026 | 11/10/2026 | 30/09/2026 | Batch bank |
| 5 | `a2ef0da6-2662-4b31-a8d9-8b79c2c084d3` | Rp4.300 | 30/09/2026 | 11/10/2026 | 30/09/2026 | Batch bank |
| 6 | `a2ef0da6-2c04-4474-9524-44f5a0358295` | Rp860 | 30/09/2026 | 11/10/2026 | 30/09/2026 | Batch bank |
| 7 | `a2ef0da6-31ad-41d5-a6f4-e4c185175aa0` | Rp11.000 | 30/09/2026 | 11/10/2026 | 30/09/2026 | Batch bank |
| 8 | `a2ef0da6-3756-4bff-9c90-ef786f9e93d2` | Rp7.500 | 30/09/2026 | 11/10/2026 | 30/09/2026 | Batch bank |
| 9 | `a2f1ce2d-e855-44c6-afdc-13a43b5802cb` | Rp400.000 | 30/09/2026 | 10/10/2026 | 30/09/2026 | Penyaluran/Realization |

## 4. Matriks keputusan governance

### G-01 — Pemisahan aktor `three_distinct`

Peran yang perlu dipertimbangkan:

- pembuat dokumen governance;
- pemeriksa/pengesah dokumen governance;
- pembuat proposal prerequisite REV;
- pemeriksa proposal prerequisite;
- materializer master prerequisite;
- pembuat correction request atau batch correction;
- approver koreksi;
- executor koreksi.

| Opsi | Implikasi kontrol internal | Implikasi implementasi |
|---|---|---|
| A. Maker berbeda dari checker; materializer dan executor boleh sama dengan checker | Kontrol dua orang; sederhana, tetapi konsentrasi kewenangan lebih tinggi | Kebijakan identitas dua kelompok aktor |
| B. Maker, checker, dan materializer/executor adalah tiga orang berbeda | Pemisahan kuat pada persetujuan dan eksekusi; tetap menyatukan materializer dengan executor | Validasi tiga identitas pada materialisasi dan execution gate |
| C. Maker, checker, materializer, dan executor seluruhnya berbeda | Kontrol paling ketat; membutuhkan minimal empat petugas tersedia | Matriks conflict-of-interest lebih kompleks dan prosedur pengganti saat petugas tidak tersedia |
| D. Matriks berbasis peran/jumlah nominal | Fleksibel, tetapi memerlukan definisi threshold dan role mapping yang eksplisit | Policy engine aktor dan snapshot kebijakan per request |

| Pihak yang harus mengesahkan | Status | Dampak terhadap kandidat |
|---|---|---|
| Pimpinan DKM dan otoritas keuangan/audit internal yang ditunjuk | **Disetujui bulat; opsi/actor matrix final belum terdokumentasi** | Seluruh kandidat tetap blocked; materialisasi/aktivasi dengan `three_distinct` tetap fail-closed |

### G-02 — Otoritas persetujuan

| Objek keputusan | Opsi otoritas | Implikasi | Status |
|---|---|---|---|
| Dokumen governance REV | Ketua DKM; bendahara; komite gabungan | Menentukan siapa yang menyatakan dokumen kebijakan sah | Menunggu pengesahan |
| Allocation/Allocation Version | Pengurus program + bendahara; komite alokasi | Menentukan apakah kewajiban/alokasi memang telah disetujui, bukan sekadar dicatat belakangan | Menunggu pengesahan |
| Fund Policy | Otoritas syariah/program + bendahara | Menentukan kombinasi Fund–Program–Account yang boleh dipakai | Menunggu pengesahan |
| Batch correction bank | Bendahara + reviewer rekonsiliasi | Menjamin seluruh baris rekening koran dan efek neto diperiksa | Menunggu pengesahan |
| Execution accounting correction | SuperAdmin yang terpisah; bendahara; atau eksekutor khusus | Menentukan siapa yang boleh mengubah fakta akuntansi melalui posting baru | Menunggu pengesahan |

Keputusan wajib menyebut role aplikasi, jabatan organisasi, kewenangan pengganti, dan aturan ketika pengguna yang memenuhi pemisahan aktor tidak tersedia.

### G-03 — Evidence dan Reason Code

| Area | Pilihan/informasi yang harus diputuskan | Implikasi | Pengesah | Status |
|---|---|---|---|---|
| Bukti tanggal transaksi | Invoice, kuitansi, bukti transfer, berita acara, rekening koran | Membuktikan kejadian, bukan otomatis membuktikan periode akuntansi | Otoritas keuangan | Menunggu |
| Bukti Allocation | Dokumen persetujuan program/alokasi yang bertanggal dan dapat diverifikasi | Menentukan apakah Allocation sah pada target | Otoritas Allocation | Menunggu |
| Bukti batch bank | Rekening koran utuh untuk periode terkait dan hasil rekonsiliasi | Wajib untuk lima transaksi batch | Bendahara + reviewer rekonsiliasi | Menunggu |
| Bukti Distribution | Dokumen penyaluran, penerima, total, dan finalisasi | Menjamin replacement tidak mengubah substansi penyaluran | Pengurus program + bendahara | Menunggu |
| Status attachment | Hanya `active`; hash, MIME, ukuran, Entity, dan link harus cocok | Attachment inactive/superseded/rejected tidak boleh dipakai | Otoritas dokumen | Prinsip siap; detail minimum menunggu |
| Masa berlaku | Tidak kedaluwarsa; periode dokumen harus relevan dengan target | Mencegah penggunaan bukti lama/tidak relevan | Otoritas dokumen | Menunggu |
| Reason Code | Kode/nama kelas reversal accounting-date correction | Harus berbeda dari reversal operasional lain jika semantiknya berbeda | Otoritas keuangan | Menunggu |
| Approval minimum | Jumlah tahap dan role approver | Harus konsisten dengan `three_distinct` | Pimpinan DKM | Menunggu |

Reason/note wajib menjelaskan tanggal original, target, alasan keterlambatan, dampak September/Oktober, dan referensi evidence. Reason Code tidak boleh dibuat atau diaktifkan sebelum keputusan disahkan.

### G-04 — Allocation Version dan Fund Policy

| Kondisi | Opsi yang dapat dipertimbangkan | Implikasi | Status |
|---|---|---|---|
| Allocation Version approved tetapi mulai efektif setelah target | A. Tetap menolak koreksi; B. buktikan ada approval terdahulu melalui workflow koreksi governance resmi; C. gunakan perlakuan akuntansi lain yang disahkan | Tidak satu pun opsi boleh mengubah tanggal approval/effective lama secara langsung | Disetujui bulat; pilihan per Allocation belum terdokumentasi |
| Kombinasi Fund–Program–Account belum mempunyai policy | A. Tetap blocked; B. ajukan policy baru prospektif; C. ajukan exception resmi jika mekanisme tersedia | Policy prospektif tidak otomatis mengesahkan transaksi September | Disetujui bulat; pilihan per Fund Policy belum terdokumentasi |
| Policy tersedia dan resolver cocok | Tetap memerlukan pemeriksaan REV, counterparty, periode, evidence, dan planner | Policy cocok bukan bukti transaksi sudah eligible | Prinsip disepakati, approval transaksi tetap menunggu |

Kandidat yang membutuhkan keputusan Allocation:

- `a2eec515…385a`: Allocation Version efektif 29/09, target 13/09.
- `a2eef5b0…0f98`: Allocation Version efektif 08/10, target 26/09.
- `a2f1ce2d…02cb`: Allocation Version efektif 10/10, target 30/09.

Kandidat dengan policy target belum tersedia:

- `a2eec515…385a`: `SANTUNAN-YATIM` + `SAY-MTRJ` + `EXP-MRJ`.
- `a2eefd9f…79a6`: `INFAQ-TROMOL` + `EXP-MRJ`.
- `a2f1ce2d…02cb`: `DHUAFA` + `SAY-001` + `EXP-MRJ`.

### G-05 — Counterparty replacement

| Kondisi | Opsi | Risiko/implikasi | Status |
|---|---|---|---|
| Counterparty `active` | Gunakan master aktif jika Entity dan snapshot cocok | Tetap harus divalidasi ulang pada execution | Prinsip siap |
| Counterparty `inactive` | A. Tolak replacement; B. izinkan referensi ID historis tanpa reaktivasi; C. buat mekanisme exception terkontrol | Opsi B/C memerlukan resolver khusus dan bukti bahwa identitas tidak berubah | Menunggu |
| Counterparty `archived` | A. Tolak; B. izinkan snapshot historis dengan approval khusus | Arsip biasanya menunjukkan tidak boleh dipakai transaksi baru; replacement bukan transaksi bisnis baru tetapi fakta posting baru | Menunggu |
| Counterparty missing | A. Tolak; B. perbolehkan hanya bagi tipe/category tertentu; C. gunakan counterparty sistem khusus melalui master governance | Tidak boleh mengarang counterparty atau menganggap null selalu valid | Menunggu |
| RCV bunga bank tanpa counterparty | A. Null diizinkan berdasarkan Bank Mutation Policy; B. wajib bank/counterparty sistem | Harus konsisten dengan transaksi RCV bank lain dan laporan counterparty | Menunggu |

Dampak kandidat:

- Archived: `a2eec515…385a`, `a2eef5b0…0f98`.
- Inactive: `a2eefd9f…79a6` dan empat PAY pada batch bank.
- Missing: RCV `a2ef0da6…84d3`.
- Active: `a2f1ce2d…02cb`.

Snapshot historis tidak otomatis cukup. Keputusan harus menentukan apakah replacement dianggap transaksi baru untuk validasi master atau kelanjutan fakta original yang boleh memakai identitas historis.

### G-06 — Koreksi batch bank

Correlation ID:

```text
147cab75-4255-430e-8bae-6e4c7f869830
```

Anggota canonical: lima transaksi `a2ef0da6…b571`, `…84d3`, `…8295`, `…5aa0`, dan `…93d2`.

| Keputusan | Opsi | Implikasi | Status |
|---|---|---|---|
| Unit koreksi | A. Seluruh anggota wajib bersama; B. subset dengan exception | Opsi B memerlukan alasan dan bukti bahwa net effect/reconciliation tetap sah | Menunggu; sistem tetap memblokir subset |
| Tanggal target | Satu target batch atau target per baris | Target per baris memperbesar risiko mismatch rekening koran | Menunggu |
| Rekonsiliasi | Harus belum locked; atau pembukaan kembali melalui workflow resmi | Tidak boleh menulis ulang rekonsiliasi completed | Menunggu |
| Evidence | Satu rekening koran batch + evidence per baris | Menentukan fingerprint evidence batch | Menunggu |
| Approval | Satu approval batch atau approval per item ditambah approval batch | Approval batch tunggal lebih sederhana tetapi harus mencakup seluruh snapshot anggota | Menunggu |
| Failure | Seluruh batch rollback bila satu item gagal | Menjadi prasyarat wajib executor atomik | Prinsip wajib |

Fingerprint approval harus mencakup correlation ID, daftar anggota terurut, nominal, PAY/RCV, account, fund, target, jurnal original, evidence, hasil rekonsiliasi, dan plan canonical seluruh batch.

### G-07 — Replacement Realization dan Distribution

| Prinsip | Keputusan yang diperlukan | Status |
|---|---|---|
| Realization original immutable | Setujui model replacement append-only yang menautkan original dan replacement | Menunggu |
| Distribution finalized tidak dipindahkan | Setujui overlay `DistributionCorrectionLineage`, bukan update `realization_id` | Menunggu |
| Identitas penerima | Tetap menggunakan DistributionItem dan identity snapshot original | Prinsip wajib; pengesahan formal menunggu |
| Nominal/fund/program | Replacement harus identik dengan original kecuali ada correction workflow terpisah | Prinsip wajib |
| Pelaporan | Histori menampilkan original dan koreksi; laporan akuntansi memakai reversal/replacement efektif | Menunggu definisi tampilan dan disclosure |
| Cancellation/failure | Lineage tidak boleh dihapus; failure dicatat append-only | Prinsip wajib |

Kandidat terdampak: `a2eec515…385a`, `a2eef5b0…0f98`, dan `a2f1ce2d…02cb`.

### G-08 — Kriteria `execution_ready`

Status `execution_ready` hanya boleh diberikan jika seluruh syarat berikut bernilai benar dari snapshot database terkunci:

- request berstatus `governance_approved` dengan fingerprint approval terbaru;
- maker-checker/`three_distinct` terpenuhi;
- transaksi dan jurnal original masih posted, immutable, dan belum direversal;
- tepat satu jurnal original dan tidak ada correction lineage lain;
- source dan target period sah;
- REV configuration aktif, valid, dan cocok Entity/tanggal;
- Reason Code dan Document Sequence tersedia;
- evidence aktif, lengkap, hash-valid, dan terhubung ke target yang benar;
- counterparty replacement diperbolehkan oleh kebijakan yang disahkan;
- Fund Policy, transaction type, fund, program, account, category, serta financial account berhasil di-resolve pada target;
- Allocation/Allocation Version sah pada target tanpa backdating;
- replacement Realization dan Distribution lineage telah direncanakan dan tervalidasi;
- batch bank lengkap dan fingerprint anggotanya tidak berubah;
- rekonsiliasi tidak mengunci source/target;
- planner canonical berstatus simulated, debit=credit, efek kumulatif net-zero, liquidity lengkap, dan tidak mempunyai blocker;
- seluruh idempotency key belum konflik;
- executor mampu menjalankan reversal, replacement, lineage, decision, dan audit dalam satu transaksi atomik.

Pengesah: kombinasi otoritas governance, keuangan, Allocation/Fund Policy, dan eksekusi sesuai keputusan G-01/G-02. Status: **disetujui bulat; pemetaan authority dan selected policy belum terdokumentasi untuk implementasi**.

## 5. Pemetaan kandidat terhadap keputusan

| Kandidat | Keputusan wajib sebelum evaluasi ulang |
|---|---|
| `a2eec515…385a` | G-01, G-02, G-03, G-04 Allocation dan Fund Policy, G-05 archived, G-07, G-08 |
| `a2eef5b0…0f98` | G-01, G-02, G-03, G-04 Allocation, G-05 archived, G-07, G-08 |
| `a2eefd9f…79a6` | G-01, G-02, G-03, G-04 Fund Policy, G-05 inactive, G-08 |
| Lima transaksi batch bank | G-01, G-02, G-03, G-05 inactive/missing, G-06, G-08 |
| `a2f1ce2d…02cb` | G-01, G-02, G-03, G-04 Allocation dan Fund Policy, G-07, G-08 |

## 6. Lembar pengesahan keputusan

Persetujuan bulat telah dikonfirmasi, tetapi pilihan final dan metadata administratif berikut belum tersedia pada repository. Nilai tersebut tidak boleh diinferensikan dari daftar alternatif.

| ID | Keputusan terpilih | Alasan dan dasar dokumen | Pengesah | Tanggal | Status |
|---|---|---|---|---|---|
| G-01 | Belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | Belum terdokumentasi | Approved; implementation blocked |
| G-02 | Belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | Belum terdokumentasi | Approved; implementation blocked |
| G-03 | Belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | Belum terdokumentasi | Approved; implementation blocked |
| G-04 | Preserve approved historical Allocation/Fund Policy through scoped exception; no backdating or economic change | Persetujuan bulat dikonfirmasi | Scoped SuperAdmin Override | Governance REV attachment dan evidence transaksi | Approved; executable policy recorded |
| G-05 | Preserve archived/inactive original identity; verified existing BNI master only for statement-proven Rp4.300 interest | Persetujuan bulat dikonfirmasi | Scoped SuperAdmin Override | Active evidence; statement wajib untuk bunga BNI | Approved; executable policy recorded |
| G-06 | Belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | Belum terdokumentasi | Approved; implementation blocked |
| G-07 | Prinsip append-only disetujui; rincian belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | Belum terdokumentasi | Approved; implementation blocked |
| G-08 | Gate canonical disetujui; package executable belum lengkap | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | Belum terdokumentasi | Approved; implementation blocked |

## 7. Keputusan yang siap diajukan

1. Pilihan pemisahan aktor dan conflict-of-interest matrix (`G-01`).
2. Penetapan otoritas per jenis keputusan (`G-02`).
3. Matriks evidence, masa berlaku, Reason Code, dan approval minimum (`G-03`).
4. Putusan per Allocation dan kombinasi Fund Policy yang belum tersedia (`G-04`).
5. Kebijakan counterparty inactive, archived, missing, dan RCV bunga bank (`G-05`).
6. Kebijakan koreksi bersama lima anggota batch bank (`G-06`).
7. Persetujuan desain append-only Replacement Realization dan Distribution Correction Lineage (`G-07`).
8. Pengesahan seluruh gate `execution_ready` (`G-08`).

Sesudah keputusan disahkan, hasilnya harus diterjemahkan ke konfigurasi dan workflow aplikasi, diuji pada database terisolasi, lalu sembilan kandidat dievaluasi ulang. Pengesahan dokumen ini sendiri tidak mengeksekusi koreksi dan tidak otomatis menjadikan kandidat eligible.
