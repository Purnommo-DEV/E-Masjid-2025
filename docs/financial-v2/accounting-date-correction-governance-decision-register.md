# Governance Decision Register — Accounting Date Correction September 2026

## 1. Status dan aturan penggunaan

| Atribut | Nilai |
|---|---|
| Status register | `APPROVED_UNANIMOUSLY` — G-01 memakai SuperAdmin Override scoped; readiness finansial tetap fail-closed |
| Ruang lingkup | Sembilan kandidat Accounting Date Correction September 2026 |
| Sumber normatif | `accounting-date-correction-governance-decisions.md` dan guard executable Financial V2 |
| Efek register | Tidak membuat approval, konfigurasi REV, Correction Request, batch, lineage, Execution Pair, atau izin posting |

Persetujuan bulat seluruh pengurus atas G-01 sampai G-08 telah dikonfirmasi. Untuk sembilan transaksi September 2026 yang terdaftar, G-01 mempunyai exception eksplisit: satu akun SuperAdmin aktual boleh tercatat sebagai maker, checker, dan executor. Ini adalah penyimpangan segregation of duties, bukan pemenuhan `three_distinct`. Exception hanya berlaku pada sembilan UUID dan correlation ID `147cab75-4255-430e-8bae-6e4c7f869830`, serta wajib mencatat alasan, waktu aktual, fingerprint, dan actor user ID. Metadata administratif yang belum tersedia tidak diisi dengan asumsi. `governance_approved` tetap tidak sama dengan `execution_ready`.

Tabel alternatif pada bagian berikut dipertahankan sebagai inventaris sumber. Pilihan executable yang disahkan dicatat eksplisit pada setiap keputusan; alternatif lain tidak ikut disetujui.

## 2. Register keputusan

### G-01 — Pemisahan aktor `three_distinct`

**Masalah:** definisi segregasi tugas, konflik kepentingan, dan pengganti aktor belum disahkan. Source saat ini memblokir materialisasi prerequisite REV, aktivasi REV, materialisasi Replacement Realization, staging Execution Pair, serta readiness individual dan batch.

| Opsi resmi | Dampak kontrol internal | Dampak workflow |
|---|---|---|
| A. Maker berbeda dari checker; materializer dan executor boleh sama dengan checker | Kontrol dua orang; konsentrasi kewenangan lebih tinggi | Policy dua kelompok identitas |
| B. Maker, checker, dan materializer/executor adalah tiga orang berbeda | Pemisahan kuat; materializer masih sama dengan executor | Validasi tiga identitas pada materialisasi dan execution gate |
| C. Maker, checker, materializer, dan executor seluruhnya berbeda | Kontrol paling ketat; memerlukan minimal empat petugas | Conflict-of-interest matrix dan prosedur pengganti lebih kompleks |
| D. Matriks berbasis peran/jumlah nominal | Fleksibel; threshold dan role mapping harus eksplisit | Policy engine aktor dan snapshot policy per request |

- Otoritas yang telah disebut source: Pimpinan DKM dan otoritas keuangan/audit internal yang ditunjuk.
- Jika identitas jabatan/role aplikasi belum ditetapkan: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: `ReversalPrerequisiteWorkflowService::materialize`, `ReversalConfigurationWorkflowService::activate`, `ReplacementRealizationWorkflowService::materialize`, `AccountingDateCorrectionExecutionPairService::stage`, `AccountingDateCorrectionExecutionOrchestrator`, serta test fail-closed workflow REV/Replacement Realization/Batch.
- Pilihan executable: scoped authenticated SuperAdmin Override untuk sembilan UUID dan satu correlation ID yang diratifikasi; penyimpangan segregation of duties wajib dicatat.
- Status: `APPROVED_UNANIMOUSLY / EXECUTABLE_POLICY_RECORDED`.

### G-02 — Otoritas persetujuan dan eksekusi

**Masalah:** belum ada pemetaan final dari jabatan organisasi ke role aplikasi untuk setiap objek keputusan.

| Objek | Opsi otoritas yang sudah didokumentasikan | Dampak |
|---|---|---|
| Dokumen governance REV | Ketua DKM; bendahara; komite gabungan | Menentukan pihak yang menyatakan dokumen kebijakan sah |
| Allocation/Allocation Version | Pengurus program + bendahara; komite alokasi | Menentukan validitas persetujuan Allocation, bukan sekadar tanggal pencatatan |
| Fund Policy | Otoritas syariah/program + bendahara | Menentukan kombinasi Fund–Program–Account yang diizinkan |
| Batch correction bank | Bendahara + reviewer rekonsiliasi | Menjamin semua baris dan efek neto diperiksa |
| Execution accounting correction | SuperAdmin terpisah; bendahara; eksekutor khusus | Menentukan pihak yang boleh mengeksekusi posting koreksi |

- Harus memuat role aplikasi, jabatan, delegasi/pengganti, dan aturan konflik kepentingan.
- Otoritas final belum ditetapkan: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: governance/prerequisite/configuration decision models, maker-checker guards, execution-pair actor fields, dan atomic execution contract.
- Pilihan executable: akun SuperAdmin aktual dari sesi terautentikasi, terbatas pada scope yang diratifikasi, dengan actor ID, alasan, waktu, dan fingerprint.
- Status: `APPROVED_UNANIMOUSLY / EXECUTABLE_POLICY_RECORDED`.

### G-03 — Evidence, Reason Code, dan dokumentasi REV

**Masalah:** bukti minimum, masa berlaku, Reason Code reversal, dan approval minimum belum disahkan.

| Area | Pilihan/informasi resmi | Dampak |
|---|---|---|
| Bukti tanggal transaksi | Invoice, kuitansi, bukti transfer, berita acara, rekening koran | Membuktikan kejadian; tidak otomatis membuktikan periode akuntansi |
| Bukti Allocation | Dokumen persetujuan program/alokasi bertanggal dan dapat diverifikasi | Membuktikan apakah Allocation sah pada target |
| Bukti batch bank | Rekening koran utuh dan hasil rekonsiliasi | Wajib bagi lima anggota batch |
| Bukti Distribution | Dokumen penyaluran, penerima, total, finalisasi | Melindungi substansi Distribution finalized |
| Status attachment | Hanya `active`; Entity, link, hash, MIME, dan ukuran cocok | Bukti superseded/rejected tidak diterima |
| Masa berlaku | Tidak kedaluwarsa; periode dokumen harus relevan dengan target | Mencegah penggunaan bukti lama/tidak relevan |
| Reason Code | Kode/nama kelas reversal Accounting Date Correction | Memisahkan koreksi ini dari reversal operasional lain |
| Approval minimum | Jumlah tahap dan role approver | Harus konsisten dengan keputusan G-01 |

- Otoritas yang telah disebut: otoritas keuangan, otoritas Allocation, bendahara/reviewer rekonsiliasi, pengurus program, otoritas dokumen, dan Pimpinan DKM sesuai objeknya.
- Identitas final dan nilai konfigurasi: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: Attachment/AttachmentLink validation, governance-document workflow, REV configuration snapshot validation, planner evidence blockers.
- Pilihan executable: Reason Code `ACCOUNTING_DATE_CORRECTION`, evidence aktif dan terikat target, validasi hash/MIME/ukuran, approval snapshot, fingerprint, dan audit trail; statement wajib untuk batch bank.
- Status: `APPROVED_UNANIMOUSLY / EXECUTABLE_POLICY_RECORDED`.

### G-04 — Allocation Version dan Fund Policy

**Masalah:** tiga Allocation Version belum efektif pada target dan tiga kombinasi policy tidak tersedia pada tanggal target.

| Kondisi | Opsi resmi | Dampak |
|---|---|---|
| Allocation approved tetapi efektif setelah target | A. Tetap menolak; B. buktikan approval terdahulu melalui workflow governance resmi; C. perlakuan akuntansi lain yang disahkan | Tidak boleh mengubah langsung tanggal approval/effective original |
| Fund Policy belum tersedia | A. Tetap blocked; B. policy baru prospektif; C. exception resmi bila workflow tersedia | Policy prospektif tidak otomatis mengesahkan September |
| Policy resolver cocok | Lanjutkan validasi REV, counterparty, periode, evidence, planner | Policy cocok tidak menjadikan transaksi eligible |

- Allocation terdampak: `a2eec515…385a`, `a2eef5b0…0f98`, `a2f1ce2d…02cb`.
- Policy terdampak: `a2eec515…385a`, `a2eefd9f…79a6`, `a2f1ce2d…02cb`.
- Pengesah final per Allocation/Policy: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: `FinancialTransactionConfigurationResolver`, Allocation Version immutability, candidate evaluator, canonical planner.
- Pilihan executable: pertahankan Allocation Version/Fund Policy historis melalui exception scoped apabila approval original sah dan seluruh dimensi ekonomi identik; exception tidak mengubah atau memundurkan effective date.
- Status: `APPROVED_UNANIMOUSLY / EXECUTABLE_POLICY_RECORDED`.

### G-05 — Counterparty replacement

**Masalah:** replacement merupakan fakta posting baru, sementara master original archived/inactive/missing pada delapan kandidat.

| Kondisi | Opsi resmi | Dampak |
|---|---|---|
| Active | Gunakan master aktif jika Entity dan snapshot cocok | Tetap divalidasi ulang saat execution |
| Inactive | A. Tolak; B. izinkan ID historis tanpa reaktivasi; C. exception terkontrol | B/C memerlukan resolver dan bukti identitas |
| Archived | A. Tolak; B. snapshot historis dengan approval khusus | Harus diputuskan apakah replacement boleh merujuk master archived |
| Missing | A. Tolak; B. hanya untuk tipe/category tertentu; C. counterparty sistem governed | Tidak boleh mengarang counterparty |
| RCV bunga bank tanpa counterparty | A. Null diizinkan policy Mutasi Bank; B. wajib bank/counterparty sistem | Harus konsisten dengan transaksi bank dan reporting |

- Pengesah kebijakan belum ditentukan final: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: master validation pada canonical compiler/planner dan `REPLACEMENT_COUNTERPARTY_NOT_ACTIVE` pada evaluator.
- Pilihan executable: archived/inactive original dipertahankan sebagai identitas historis melalui exception scoped tanpa reaktivasi; missing ditolak kecuali bunga BNI Rp4.300 yang tervalidasi statement dan memakai master BNI yang sudah ada.
- Status: `APPROVED_UNANIMOUSLY / EXECUTABLE_POLICY_RECORDED`.

### G-06 — Koreksi kelompok batch bank

**Masalah:** lima transaksi dengan correlation ID `147cab75-4255-430e-8bae-6e4c7f869830` harus dikoreksi konsisten dan atomik.

| Keputusan | Opsi resmi | Dampak |
|---|---|---|
| Unit koreksi | A. Semua anggota bersama; B. subset dengan exception | Subset memerlukan pembuktian net effect dan rekonsiliasi |
| Tanggal target | Satu target batch; target per baris | Target per baris menambah risiko mismatch rekening koran |
| Rekonsiliasi | Belum locked; atau pembukaan kembali melalui workflow resmi | Rekonsiliasi completed tidak boleh ditulis ulang |
| Evidence | Satu statement batch + evidence per baris | Menentukan fingerprint evidence batch |
| Approval | Satu approval batch; atau per item + batch | Scope snapshot approval berbeda |
| Failure | Seluruh batch rollback bila satu item gagal | Prasyarat wajib atomic executor |

- Unit koreksi, approval, evidence, dan exception: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: `AccountingDateCorrectionBatchWorkflowService`, batch item/pair lineage, `AccountingDateCorrectionExecutionOrchestrator`, batch integration tests, atomic execution contract.
- Pilihan executable: kelima anggota correlation ID `147cab75-4255-430e-8bae-6e4c7f869830` wajib satu batch atomik, satu target, statement canonical, approval batch, dan rollback seluruh anggota bila satu gagal.
- Status: `APPROVED_UNANIMOUSLY / EXECUTABLE_POLICY_RECORDED`.

### G-07 — Replacement Realization dan Distribution Correction Lineage

**Masalah:** tiga Realization recorded memiliki Distribution finalized yang tidak boleh dipindahkan atau ditulis ulang.

| Prinsip/keputusan | Dampak |
|---|---|
| Realization original immutable; replacement append-only | Original tetap menjadi histori |
| Distribution finalized memakai overlay lineage | `realization_id`, DistributionItem, penerima, dan snapshot tidak berubah |
| Identitas penerima tetap dari snapshot original | Mencegah duplikasi atau perubahan penerima |
| Nominal/fund/program replacement identik kecuali workflow koreksi terpisah | Mencegah perubahan substansi penyaluran |
| Reporting membedakan original dan replacement | Mencegah double count |
| Failure dicatat append-only dan lineage tidak dihapus | Audit trail tetap utuh |

- Pengesahan desain, disclosure reporting, dan otoritas materialisasi: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: `ReplacementRealizationWorkflowService`, `DistributionCorrectionReadService`, immutable lineage models, migration 15, Replacement Realization integration tests.
- Status: `APPROVED_UNANIMOUSLY`; desain prinsip disetujui, tetapi authority/disclosure final belum terdokumentasi.

### G-08 — Kriteria akhir `execution_ready`

**Masalah:** daftar gate final harus disahkan dan seluruhnya dibuktikan dari snapshot database terkunci.

Gate yang telah didokumentasikan meliputi approval/fingerprint terbaru, G-01, source transaction/journal posted dan belum direversal, tepat satu jurnal, period sah, REV aktif, Reason Code/sequence, evidence valid, counterparty policy, resolver policy, Allocation efektif, replacement lineage, batch lengkap, rekonsiliasi, planner simulated dan seimbang, likuiditas lengkap, idempotency, serta atomic executor.

- Tidak ada opsi untuk mengabaikan sebagian gate. Perubahan daftar gate memerlukan keputusan governance baru.
- Otoritas gabungan governance/keuangan/Allocation/Fund Policy/execution: **`GOVERNANCE DECISION REQUIRED`**.
- Referensi: `AccountingDateCorrectionWorkflowService`, canonical preview/planner, execution-pair state guard/service, orchestrator, atomic execution contract.
- Status: `APPROVED_UNANIMOUSLY`; gate disetujui sebagai prinsip, tetapi policy package executable belum lengkap.

## 3. Ruang pengesahan resmi

| ID | Keputusan resmi | Nama/jabatan pengesah | Tanggal | Alasan | Evidence/attachment reference | Status |
|---|---|---|---|---|---|---|
| G-01 | Belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi; detail administratif belum tersedia | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-02 | Belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi; detail administratif belum tersedia | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-03 | Belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi; detail administratif belum tersedia | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-04 | Belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi; detail administratif belum tersedia | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-05 | Belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi; detail administratif belum tersedia | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-06 | Belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi; detail administratif belum tersedia | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-07 | Prinsip append-only/immutable disetujui; rincian authority/disclosure belum terdokumentasi | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |
| G-08 | Gate canonical disetujui; policy package executable belum lengkap | Belum terdokumentasi | Belum terdokumentasi | Persetujuan bulat dikonfirmasi | Belum terdokumentasi | `APPROVED_UNANIMOUSLY / IMPLEMENTATION_BLOCKED` |

## 4. Gap yang harus diputuskan atau diratifikasi

Delapan keputusan telah mencakup topik utama, tetapi rincian berikut belum mempunyai nilai final dan tidak boleh diinferensikan oleh developer:

1. Pemetaan nama jabatan organisasi ke role/permission dan user aplikasi untuk G-01/G-02.
2. Prosedur pengganti ketika jumlah aktor berbeda tidak tersedia, termasuk larangan self-approval saat keadaan darurat.
3. Otoritas dan prosedur membuka kembali rekonsiliasi yang sudah locked/completed; ketiadaan record rekonsiliasi tidak sama dengan clearance.
4. Kebijakan retry dan incident recovery setelah kegagalan eksekusi sebelum/atau sesudah commit, tanpa menerbitkan voucher kedua.
5. Bentuk disclosure laporan untuk original, reversal, replacement, serta Distribution overlay.
6. Ratifikasi API atomic posting dan governed transaction factory yang masih berupa kontrak teknis, bukan jalur executable.

Butir 1–5 memerlukan pengesahan sesuai G-01/G-02/G-06/G-07/G-08. Butir 6 memerlukan review teknis dan persetujuan governance sebelum implementasi dapat diaktifkan.

## 5. Dependency matrix

| Keputusan | Harus disahkan sebelum | Implementasi policy/source sesudah keputusan | Evidence/data tambahan | Test wajib sebelum aktivasi | Dampak readiness |
|---|---|---|---|---|---|
| G-01 | Materialisasi prerequisite/Replacement Realization; staging pair | Actor-separation policy dan conflict matrix | Daftar role/user berwenang | Negative conflict tests; maker/checker/materializer/executor permutations | Membuka evaluasi segregasi; bukan izin posting |
| G-02 | Approval governance, Allocation/Policy, batch, execution | Authorization matrix dan server-side guards | Surat/mandat otoritas | Direct-request authorization dan cross-Entity tests | Menentukan siapa boleh mengambil keputusan |
| G-03 | Aktivasi REV dan pembuatan request | Reason Code/evidence/approval configuration | Attachment governance, bukti transaksi/alokasi/batch | Hash/MIME/size/status/link, superseded, approval-step tests | Memungkinkan validasi evidence canonical |
| G-04 | Reevaluasi tiga Allocation dan tiga policy gap | Workflow revision/exception yang disahkan; tanpa backdating | Bukti approval Allocation dan dasar Fund Policy | Effective-date, resolver, immutable-history tests | Menghapus blocker per transaksi bila valid |
| G-05 | Kompilasi replacement | Counterparty replacement policy/resolver | Bukti identitas dan policy bank | Active/inactive/archived/missing matrix tests | Menghapus blocker master bila diizinkan |
| G-06 | Pembuatan/approval batch | Batch membership, reconciliation, approval, atomicity policy | Statement dan rekonsiliasi lengkap | Membership drift, subset rejection, all-or-nothing rollback | Lima anggota tetap satu unit |
| G-07 | Materialisasi lineage | Replacement/Distribution overlay rules dan reporting resolver | Dokumen Distribution/finalisasi | Immutability, write-once, no-double-count, rollback tests | Menyiapkan lineage; tidak membuat pair ready |
| G-08 | Transisi ke `execution_ready` | Locked revalidation, governed factory, atomic PostingEngine API | Snapshot approval dan plan terbaru | Fingerprint drift, liquidity, idempotency, partial-failure, voucher tests | Satu-satunya gate menuju execution readiness |

Urutan minimum: dokumentasikan `selected_policy` G-01/G-02 → G-03 → G-04/G-05/G-06/G-07 sesuai kandidat → lengkapi package G-08 → implementasi dan test atomic executor → reevaluasi kandidat. Selama pilihan final atau prasyarat executable belum lengkap, materialisasi REV/request/batch/lineage/Execution Pair tidak boleh dilakukan untuk sembilan kandidat.

## 6. Pemetaan kandidat

| Kandidat | Keputusan yang masih diperlukan |
|---|---|
| `a2eec515…385a` | G-01, G-02, G-03, G-04 Allocation+Policy, G-05 archived, G-07, G-08 |
| `a2eef5b0…0f98` | G-01, G-02, G-03, G-04 Allocation, G-05 archived, G-07, G-08 |
| `a2eefd9f…79a6` | G-01, G-02, G-03, G-04 Policy, G-05 inactive, G-08 |
| Lima anggota batch bank | G-01, G-02, G-03, G-05 inactive/missing, G-06, G-08 |
| `a2f1ce2d…02cb` | G-01, G-02, G-03, G-04 Allocation+Policy, G-07, G-08 |

## 7. Referensi

- `docs/financial-v2/accounting-date-correction-governance-decisions.md`
- `docs/financial-v2/accounting-date-correction-execution-readiness.md`
- `docs/financial-v2/accounting-date-correction-atomic-execution-contract.md`
- `docs/financial-v2/reversal-configuration-migration-recovery.md`
- `docs/financial-v2/reversal-prerequisite-migration-recovery.md`
- `docs/financial-v2/accounting-date-correction-batch-migration-recovery.md`
- `docs/financial-v2/accounting-date-correction-execution-pair-migration-recovery.md`
- `app/Domain/FinancialV2/ReversalPrerequisiteWorkflowService.php`
- `app/Domain/FinancialV2/ReversalConfigurationWorkflowService.php`
- `app/Domain/FinancialV2/ReplacementRealizationWorkflowService.php`
- `app/Domain/FinancialV2/AccountingDateCorrectionBatchWorkflowService.php`
- `app/Domain/FinancialV2/AccountingDateCorrectionExecutionPairService.php`
- `app/Domain/FinancialV2/AccountingDateCorrectionExecutionOrchestrator.php`

