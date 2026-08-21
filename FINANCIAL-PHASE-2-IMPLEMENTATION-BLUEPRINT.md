# Blueprint Implementasi Keuangan — Phase 2

## Accounting Foundation dan General Ledger

**Status:** Draft untuk pengesahan dan perencanaan delivery  
**Versi:** 1.0  
**Tanggal:** 5 Agustus 2026  
**Cakupan:** Blueprint bisnis, accounting, governance, migrasi, quality assurance, dan cutover untuk Phase 2 modul Keuangan  
**Tidak mencakup:** Kode, skema basis data fisik, migration, SQL, controller, model, repository, rancangan API, atau keputusan framework

---

## Ringkasan eksekutif

Phase 2 membangun fondasi accounting yang membuat setiap perubahan keuangan dapat dipertanggungjawabkan dari bukti sampai laporan. Hasil utamanya bukan sekadar form penerimaan atau pengeluaran baru, melainkan **General Ledger posted sebagai satu-satunya sumber saldo resmi**.

Blueprint ini menerjemahkan dua baseline yang telah disusun:

1. [Financial Architecture V2](FINANCIAL-ARCHITECTURE-V2.md), sebagai keputusan arsitektur bisnis dan accounting;
2. [Accounting Policy & Financial Governance Manual](ACCOUNTING-POLICY-FINANCIAL-GOVERNANCE-MANUAL.md), sebagai kebijakan operasional dan governance.

Dokumen ini tidak menggantikan keduanya. Bila ada perbedaan, urutan otoritasnya adalah: kebijakan yang telah disahkan pengurus → manual kebijakan → arsitektur V2 → blueprint ini. Semua ketetapan yang masih berbentuk draft wajib diputuskan sebelum dijadikan aturan operasional.

### Sasaran Phase 2

Pada akhir Phase 2, Masjid harus dapat menjawab secara konsisten dan dapat ditelusuri:

- saldo buku setiap Rekening pada tanggal tertentu;
- saldo setiap Fund, termasuk Fund restricted;
- komposisi Fund yang membentuk saldo sebuah Rekening;
- transaksi, jurnal, dan bukti yang membentuk suatu angka;
- perbedaan antara transfer Rekening, interfund transfer, budget allocation, dan realisasi;
- posisi awal (opening balance) yang disetujui pada cutover;
- apakah ledger selalu balance dan bebas posting ganda;
- laporan dasar yang dapat di-tie-out ke ledger.

### Prinsip hasil

1. Tidak ada saldo resmi yang diketik atau diubah langsung.
2. Tidak ada transaksi keuangan yang memengaruhi laporan sebelum diposting.
3. Tidak ada transaksi posted yang dihapus atau diedit secara langsung.
4. Rekening, Fund, Program, dan Account selalu diperlakukan sebagai konsep yang berbeda.
5. Bukti, nomor voucher, alasan bisnis, dan jejak audit menyertai transaksi sejak awal.
6. Data lama digunakan sebagai sumber migrasi; sesudah cutover tidak boleh menjadi sumber saldo kedua.

---

# 1. Batasan Phase 2

## 1.1 In scope

| Kapabilitas | Hasil bisnis Phase 2 |
|---|---|
| Kebijakan minimum | Keputusan accounting, Fund, dan cutover yang final serta memiliki pemilik. |
| Master keuangan | Rekening, Fund, Program, Chart of Accounts (CoA), kategori, periode, counterparty, dan metadata bukti yang dikelola sebagai master resmi. |
| Transaksi inti | Penerimaan, pengeluaran, transfer fisik antar-Rekening, reversal, adjustment, serta opening balance. |
| Split transaksi | Satu bukti dapat dibagi ke beberapa Fund, Account, Program, atau kombinasi yang sah. |
| Jurnal dan ledger | Jurnal double-entry, posting, ledger immutable, traceability, dan saldo yang dihitung dari ledger. |
| Kontrol minimum | Nomor bukti, idempotency, period eligibility, validasi balancing, attachment, dan audit trail dasar. |
| Laporan dasar | Trial balance, general ledger, saldo Rekening, saldo Fund, detail transaksi, dan exception dasar. |
| Cutover | Mapping legacy, rekonsiliasi cutoff, opening position, parallel verification, dan penonaktifan jalur lama. |
| Quality assurance | Test suite business/accounting, test migration, acceptance evidence, serta sign-off. |

## 1.2 Di luar scope dan ditunda ke Phase 3 atau 4

| Kapabilitas | Fase target | Alasan penundaan |
|---|---|---|
| Versioned Fund restriction policy lengkap dan eligibility matrix otomatis | Phase 3 | Phase 2 hanya memberlakukan validasi minimum yang sudah final. |
| Budget, commitment, budget revision, dan budget-versus-actual | Phase 3 | Budget tidak boleh mengaburkan pembangunan ledger. |
| Interfund transfer rutin/reclassification sebagai proses penuh | Phase 3 | Memerlukan policy restriction dan governance exception yang lebih matang. |
| Rekonsiliasi bank terotomasi, bank statement import, dan assisted matching | Phase 3/4 | Phase 2 menyediakan data ledger yang kelak direkonsiliasi. |
| Soft close, hard close, reopen workflow, dan closing dashboard | Phase 3 | Phase 2 membangun period eligibility dan fondasi auditnya. |
| Dashboard, forecast, analitik anomali, dan projection skala besar | Phase 4 | Angka dashboard harus menunggu ledger stabil. |
| Multi-cabang, konsolidasi multi-Masjid, dan multi-entity | Phase 4 atau proyek tersendiri | Membutuhkan batas entitas dan governance tambahan. |

## 1.3 Guardrails

- Phase 2 tidak boleh mengaktifkan fitur baru dengan mengambil saldo dari tabel ringkasan legacy.
- Phase 2 tidak boleh menyederhanakan Fund restricted menjadi kategori biasa agar mudah diinput.
- Phase 2 tidak boleh menyamakan budget allocation dengan transaksi aktual.
- Phase 2 tidak boleh mengimpor historis yang belum dipetakan sebagai transaksi posted baru secara membabi buta.
- Phase 2 tidak boleh menerima selisih cutover tanpa register exception dan keputusan yang dapat diaudit.
- Tidak ada go-live parsial untuk satu transaksi yang tetap menulis saldo di dua jalur berbeda.

---

# 2. Target operating model

## 2.1 Alur nilai end-to-end

```text
Kebijakan disahkan
        |
        v
Master data dan posting rule disiapkan
        |
        v
Transaksi + bukti dicatat dan diverifikasi
        |
        v
Validasi Fund, Rekening, periode, dan balancing
        |
        v
Posting jurnal resmi + nomor voucher + audit event
        |
        v
General Ledger menjadi sumber saldo resmi
        |
        +------------------------------+
        |                              |
        v                              v
Laporan & inquiry                Cutover / QA / exception review
```

## 2.2 Fungsi governance minimum

Nama berikut menunjukkan fungsi pengendalian, bukan rancangan pengelolaan pengguna aplikasi.

| Fungsi | Tanggung jawab Phase 2 | Tidak boleh menjadi satu-satunya pengendali untuk |
|---|---|---|
| Policy owner | Menetapkan Fund, kebijakan accounting, materi exception, dan effective date. | Menyetujui kebijakan tanpa review finance atau syariah pada dana khusus. |
| Finance Controller | Menjaga kualitas master, posting rule catalogue, tie-out, exception register, dan readiness cutover. | Menghapus bukti atau mengubah histori posted. |
| Transaction preparer | Menyiapkan data transaksi dan bukti. | Melakukan approval sendiri atas transaksi material. |
| Verifikator | Memeriksa kelengkapan, klasifikasi, dan bukti sebelum posting. | Mengesahkan transaksi yang kepentingannya melekat pada dirinya sendiri. |
| Otoritas keuangan | Memberi persetujuan sesuai mandat dan limit. | Menyetujui exception tanpa alasan dan evidence. |
| Treasury / custodian | Menjalankan penerimaan atau pembayaran dan menyimpan bukti pelaksanaan. | Menjadi satu-satunya pihak yang memverifikasi kas fisik. |
| Posting custodian | Memastikan transaksi yang sah menjadi posting resmi. | Mengedit jurnal posted. |
| Migration lead | Menjaga mapping, cutoff evidence, dan log keputusan migrasi. | Menetapkan sendiri klasifikasi Fund yang diperselisihkan. |
| Independent reviewer | Meninjau cutover, adjustment material, dan sign-off. | Menjadi pembuat semua transaksi yang direview. |
| Penasihat syariah | Mereview ketetapan Zakat, Fidyah, Wakaf, Qurban, dan dana khusus sesuai mandatnya. | Menggantikan review accounting atas balance dan evidence. |

## 2.3 RACI ringkas

| Deliverable | Policy owner | Finance Controller | Migration lead | Otoritas keuangan | Reviewer independen | Penasihat syariah |
|---|---|---|---|---|---|---|
| Fund policy dan decision matrix | A | R | C | C | I | C/A untuk dana syariah sesuai mandat |
| CoA dan posting rule catalogue | A | R | C | C | I | C untuk dana khusus |
| Master data opening | I | A/R | R | C | I | C bila relevan |
| Legacy mapping dan exception register | I | A | R | C | R | C bila Fund khusus |
| Opening balance | A | R | R | C | R | C bila relevan |
| Acceptance evidence | I | A/R | R | C | R | C bila relevan |
| Go-live dan penghentian jalur lama | A | R | R | C | C | I |

Keterangan: **R** = menjalankan, **A** = pemegang akuntabilitas akhir, **C** = dikonsultasikan, **I** = diinformasikan.

---

# 3. Keputusan yang wajib final sebelum build dimulai

Keputusan berikut bukan backlog teknis. Ia adalah prasyarat governance. Jika salah satu keputusan material belum ada, pekerjaan terkait hanya boleh berada pada tahap discovery, bukan siap dibangun atau diuji.

| ID | Keputusan | Pemilik keputusan | Evidence minimum | Gate |
|---|---|---|---|---|
| D-01 | Basis accounting dan perlakuan restricted contribution/Fund yang dipilih. | Policy owner | Memo kebijakan disahkan. | G1 |
| D-02 | Daftar Fund resmi, status restriction, dan effective date. | Policy owner | Fund register. | G1 |
| D-03 | Penggunaan Dana Zakat, Fidyah, Wakaf, dan Qurban. | Policy owner + penasihat syariah | Decision matrix dan catatan review. | G1 |
| D-04 | Daftar Rekening, pemilik, jenis, status aktif, dan Fund yang boleh ditampung. | Otoritas keuangan | Rekening register. | G1 |
| D-05 | CoA dan normal balance setiap akun. | Policy owner | CoA disahkan. | G1 |
| D-06 | Akun yang wajib membawa dimensi Fund, Rekening, Program, atau counterparty. | Finance Controller | Dimension requirement matrix. | G1 |
| D-07 | Kategori transaksi dan posting rule catalogue inti. | Finance Controller | Catalogue diberi versi. | G2 |
| D-08 | Format nomor voucher dan scope sequence. | Finance Controller | Nomor bukti policy. | G2 |
| D-09 | Bukti minimum dan retensi dokumen. | Policy owner | Evidence policy. | G2 |
| D-10 | Batas nominal materiality, approval, dan adjustment. | Otoritas keuangan | Approval matrix. | G2 |
| D-11 | Cutover date, cutoff time, dan period awal V2. | Policy owner | Cutover charter. | G3 |
| D-12 | Metode penyusunan opening balance. | Finance Controller | Opening balance method paper. | G3 |
| D-13 | Toleransi selisih dan tata kelola exception cutover. | Policy owner | Exception policy. | G3 |
| D-14 | Sumber bukti bank/kas dan jadwal rekonsiliasi awal. | Otoritas keuangan | Reconciliation baseline plan. | G3 |
| D-15 | Laporan dasar, audiens, periode, dan tie-out wajib. | Finance Controller | Report specification. | G2 |
| D-16 | Kriteria go/no-go dan otoritas sign-off. | Policy owner | Go-live readiness checklist. | G4 |

### Ketetapan khusus yang tidak boleh diasumsikan

- Dana khusus tidak otomatis dapat membiayai biaya administrasi hanya karena praktik terdahulu demikian.
- Label rekening bank tidak otomatis menentukan Fund transaksi.
- Program tidak boleh dijadikan pengganti Fund saat donor atau kebijakan membatasi tujuan dana.
- Rekening dengan saldo eksternal yang sama tidak membuktikan saldo Fund sudah benar.
- Selisih lama tidak boleh dibiarkan tanpa pemilik dan tenggat hanya karena nilainya kecil.

---

# 4. Workstream dan artefak delivery

## 4.1 WS-0 — Policy closure dan decision governance

**Tujuan:** menjadikan kebijakan yang masih draft sebagai aturan yang dapat diuji dan dioperasikan.

| Artefak | Isi minimum | Exit evidence |
|---|---|---|
| Policy decision log | D-01 s.d. D-16, keputusan, alasan, pemilik, tanggal efektif, dan tautan approval. | Tidak ada keputusan high-risk tanpa status. |
| Fund register | Kode, nama, jenis, restriction, tujuan, larangan, status, effective period. | Semua Fund legacy termapping atau masuk exception bucket. |
| Decision matrix | Fund × tipe pengeluaran/program dengan status allowed, prohibited, atau exception. | Direview khusus untuk dana syariah. |
| Limit & approval matrix | Limit transaksi, split, adjustment, reversal, dan opening balance. | Konsisten dengan mandat pengurus. |
| Exception charter | Klasifikasi severity, escalation, SLA, dan otoritas penyelesaian. | Setiap exception punya owner. |

## 4.2 WS-1 — Master financial dimensions

**Tujuan:** menghasilkan master resmi yang cukup untuk mencatat transaksi tanpa mencampurkan makna dimensi.

| Master | Konten wajib | Kontrol kualitas |
|---|---|---|
| Rekening | kode, nama, jenis, institusi, nomor/masked identifier, mata uang bila relevan, status, custodian, tanggal efektif. | Tidak duplikat; rekening tutup tidak dapat dipilih untuk tanggal setelah penutupan. |
| Fund | kode, nama, tipe, restriction, tujuan, status, policy version, tanggal efektif. | Tidak ada Fund tanpa keputusan penggunaan. |
| Program | kode, nama, unit kegiatan, periode, status. | Tidak diberi saldo atau dipakai sebagai Fund. |
| CoA | kode, nama, kelompok, normal balance, requirement dimensi, status. | Akun kas/bank wajib ditautkan ke Rekening; akun pendapatan/beban mengikuti matrix dimensi. |
| Kategori | nama operasional, transaction type, default rule versi. | Tidak menggantikan Account/Fund. |
| Periode | tahun, bulan, status, tanggal mulai/akhir. | Tidak ada dua periode aktif yang tumpang tindih. |
| Counterparty | pihak pemberi/penerima sesuai kebutuhan privacy. | Tidak mengubah histori bila nama master diperbarui. |
| Reason/exception code | reason, severity, workflow, evidence requirement. | Wajib untuk reversal/adjustment/override. |

### Definition of Ready WS-1

- setiap master memiliki pemilik bisnis;
- kode, nama, status, dan tanggal efektif tersedia;
- tidak ada nama legacy yang otomatis dianggap identik tanpa mapping review;
- matriks requirement dimensi telah disetujui;
- sampel transaksi inti dapat diklasifikasikan lengkap menggunakan master tersebut.

## 4.3 WS-2 — Transaction catalogue dan posting rule catalogue

**Tujuan:** setiap fakta bisnis memiliki satu jenis transaksi yang jelas, data minimum, bukti minimum, serta dampak ledger yang dapat diprediksi.

### Transaction catalogue inti

| Kode | Peristiwa bisnis | Dampak utama | Phase 2 | Catatan |
|---|---|---|---|---|
| RCV | Penerimaan kas/nonkas | Aset likuid dan pendapatan/net assets/liabilitas sesuai policy. | Ya | Wajib Fund dan Rekening. |
| PAY | Pembayaran/pengeluaran | Beban/aset/distribusi dan pengurangan aset likuid. | Ya | Wajib purpose, Fund, Rekening, bukti. |
| TRF | Transfer fisik antar-Rekening | Lokasi aset berubah; Fund tetap. | Ya | Bukan pendapatan atau beban. |
| OPB | Opening balance | Membentuk posisi awal terverifikasi. | Ya | Hanya pada cutover/awal periode yang disahkan. |
| REV | Reversal | Membalik dampak transaksi posted sebelumnya. | Ya | Wajib referensi jurnal asal. |
| ADJ | Adjustment | Koreksi transparan atas posisi/accounting. | Ya | Wajib reason dan approval sesuai limit. |
| SPL | Split | Bukan transaksi mandiri; rincian RCV/PAY/TRF yang dibagi ke beberapa dimensi. | Ya | Total split harus sama dengan header. |
| IFT | Interfund transfer/reclassification | Ownership Fund berubah. | Tidak sebagai proses rutin | Hanya discovery/design policy pada Phase 2. |
| BGT | Budget allocation/revision | Rencana dan kontrol budget. | Tidak | Tidak menghasilkan jurnal. |
| REC | Reconciliation adjustment | Penanganan selisih hasil rekonsiliasi. | Tidak sebagai workflow penuh | Dibangun di Phase 3. |

### Minimum data per transaksi

| Elemen | RCV | PAY | TRF | OPB | REV | ADJ |
|---|---:|---:|---:|---:|---:|---:|
| Tanggal transaksi | Wajib | Wajib | Wajib | Wajib | Wajib | Wajib |
| Tanggal accounting | Wajib | Wajib | Wajib | Wajib | Wajib | Wajib |
| Rekening sumber/tujuan | Wajib | Wajib | Wajib dua sisi | Wajib bila aset | Turunan asal | Sesuai dampak |
| Fund | Wajib | Wajib | Wajib dan dipertahankan | Wajib bila material | Turunan asal | Wajib bila dampak Fund |
| Program | Bila policy mewajibkan | Bila policy mewajibkan | Tidak sebagai penentu transfer | Bila diperlukan | Turunan asal | Bila relevan |
| Account | Ditentukan rule | Ditentukan rule | Aset likuid kedua sisi | Wajib | Turunan asal | Wajib |
| Counterparty | Bila tersedia/diwajibkan | Wajib sesuai transaksi | Institusi/tujuan transfer | Tidak selalu | Referensi asal | Wajib bila relevan |
| Bukti | Wajib sesuai policy | Wajib sesuai policy | Wajib | Wajib reconciliation evidence | Wajib alasan | Wajib alasan & evidence |
| Source reference | Wajib | Wajib | Wajib | Wajib | Referensi journal asal | Referensi issue/exception |

### Invariant catalogue

| ID | Invariant yang harus selalu benar | Cara pembuktian |
|---|---|---|
| INV-01 | Total debit jurnal = total kredit jurnal. | Validasi posting dan laporan exception. |
| INV-02 | Journal posted tidak dapat diubah atau dihapus. | Audit test perubahan histori. |
| INV-03 | Satu source reference idempotent hanya menghasilkan satu posting resmi. | Test retry/double submission. |
| INV-04 | Semua saldo resmi dapat dihitung ulang dari ledger. | Rebuild/tie-out test. |
| INV-05 | Transfer fisik tidak mengubah total aset atau total per Fund. | Skenario transfer multi-Fund. |
| INV-06 | Split header = total semua split. | Validation test. |
| INV-07 | Tanggal accounting berada dalam periode yang eligible. | Boundary-date test. |
| INV-08 | Transaksi tidak lengkap tidak dapat posted. | Negative validation test. |
| INV-09 | Nomor voucher unik dalam scope yang ditetapkan. | Concurrency/gap report test. |
| INV-10 | Attachment dan audit event dapat ditelusuri dari transaksi posted. | Evidence trace test. |

## 4.4 WS-3 — Core ledger dan saldo resmi

**Tujuan:** membangun proses bisnis yang menghasilkan jurnal valid dan menjadikan ledger sebagai sumber angka tunggal.

### Aturan desain yang mengikat

- Posting adalah titik ketika transaksi mulai memengaruhi saldo resmi dan laporan.
- Satu transaksi bisnis boleh memiliki beberapa journal line; setiap line membawa dimensi yang diperlukan untuk mempertanggungjawabkan dampaknya.
- Saldo Rekening dihitung dari journal line akun kas/bank yang terkait Rekening tersebut.
- Saldo Fund dihitung dari seluruh dampak Fund, bukan dengan mengurangi saldo satu rekening.
- Saldo Program bukan saldo kas; Program hanya menjadi dimensi penggunaan bila policy mengharuskannya.
- Tampilan ringkasan boleh dioptimalkan setelah ledger stabil, tetapi harus bisa diuji kembali terhadap ledger.

### Dampak bisnis konseptual

| Peristiwa | Rekening | Fund | Program | Pendapatan/beban | Catatan kontrol |
|---|---|---|---|---|---|
| Donasi umum diterima melalui bank | Bertambah pada Rekening penerima. | Bertambah pada Fund yang ditentukan. | Opsional sesuai tujuan. | Diakui sesuai kebijakan. | Bukti transfer dan identifikasi Fund. |
| Pembayaran listrik | Berkurang pada Rekening pembayar. | Berkurang/terpakai pada Fund yang sah. | Dapat diberi Program operasional. | Beban utilitas. | Tidak boleh memakai Fund restricted yang dilarang. |
| Pindah Bank BNI ke Kas Tunai | Berkurang Bank, bertambah Kas. | Tidak berubah per Fund. | Tidak relevan. | Tidak ada pendapatan/beban. | Kedua sisi transfer dan total Fund harus tie-out. |
| Opening balance bank | Menetapkan posisi awal Rekening. | Menetapkan komposisi Fund sesuai evidence. | Tidak otomatis. | Tidak menjadi penerimaan periode berjalan. | Total harus tie-out ke bank statement/cash count. |
| Reversal pembayaran salah | Membalik dampak Rekening asal. | Membalik Fund asal. | Membalik dimensi asal. | Membalik dampak asal. | Tidak mengubah jurnal lama; referensi wajib. |
| Adjustment selisih yang disetujui | Sesuai sifat koreksi. | Sesuai dampak yang dijelaskan. | Jika relevan. | Sesuai policy. | Reason, evidence, dan approval wajib. |

## 4.5 WS-4 — Bukti, nomor voucher, dan audit lineage

**Tujuan:** setiap angka dapat ditelusuri tanpa bergantung pada penjelasan lisan.

| Artefak | Ketetapan Phase 2 |
|---|---|
| Voucher | Gunakan format konseptual RCV, PAY, TRF, OPB, REV, dan ADJ; sequence harus unik dalam scope kebijakan. |
| Source reference | Setiap transaksi menyimpan identitas sumber yang stabil untuk idempotency dan traceability. |
| Attachment | Bukti transaksi ditautkan pada business transaction; bukti tidak diganti diam-diam setelah posting. |
| Audit event | Catat create, submit, approve, reject, post, reverse, adjust, attach, detach, dan perubahan master material. |
| Correction lineage | Reversal/adjustment selalu menunjuk transaksi/jurnal asal atau exception case. |
| Gap report | Nomor yang dibatalkan, gagal, atau void dapat dijelaskan; tidak dianggap transaksi tersembunyi. |

## 4.6 WS-5 — Laporan dasar dan tie-out

| Laporan | Pengguna utama | Pertanyaan yang dijawab | Tie-out wajib |
|---|---|---|---|
| Trial Balance | Finance Controller | Apakah seluruh Account balance dan total debit/kredit seimbang? | Total debit = total kredit. |
| General Ledger | Finance/audit | Jurnal apa yang membentuk saldo Account/Fund/Rekening? | Baris jurnal ↔ transaksi ↔ bukti. |
| Saldo Rekening | Treasury | Berapa saldo buku per kas/bank? | Saldo semua Fund dalam Rekening = saldo Rekening. |
| Saldo Fund | Pengurus/finance | Berapa sumber daya per Fund? | Total Fund tie-out ke posisi keuangan policy basis. |
| Detail transaksi | Operator/reviewer | Apa status dan bukti setiap transaksi? | Transaksi posted memiliki voucher dan journal reference. |
| Exception report | Policy owner | Apa yang belum lengkap, ditolak, bermasalah, atau perlu keputusan? | Setiap exception punya owner/status. |
| Cutover reconciliation report | Steering group | Apakah opening position dapat dibuktikan? | External balance + mapped Fund + adjustment = opening ledger. |

---

# 5. Backlog implementasi berbasis outcome

Backlog ini mengarahkan delivery. Prioritas ditentukan oleh dependency accounting, bukan oleh kemudahan tampilan.

| Epic | Outcome | Prioritas | Dependency | Acceptance ringkas |
|---|---|---|---|---|
| E-01 Policy & decision register | Semua keputusan material mempunyai status dan pemilik. | P0 | Tidak ada | G1 disetujui. |
| E-02 Master dimensions | Transaksi dapat diklasifikasikan konsisten. | P0 | E-01 | Master sample lulus review. |
| E-03 Period & voucher controls | Hanya periode dan nomor yang valid dipakai. | P0 | E-01 | Test duplikasi/boundary lulus. |
| E-04 Transaction intake | Penerimaan, pengeluaran, dan transfer memiliki data/bukti lengkap. | P0 | E-02, E-03 | Field minimum dan split tervalidasi. |
| E-05 Posting & ledger | Semua transaksi posted menghasilkan jurnal balanced dan immutable. | P0 | E-04 | INV-01 s.d. INV-08 lulus. |
| E-06 Inquiry & reports | Ledger dan saldo dapat ditelusuri. | P0 | E-05 | Tie-out utama lulus. |
| E-07 Reversal & adjustment | Koreksi terjadi tanpa mengubah histori. | P0 | E-05 | Lineage koreksi lulus. |
| E-08 Attachments & audit | Bukti dan event audit tersedia. | P0 | E-04 | Trace test lulus. |
| E-09 Legacy mapping | Setiap saldo/record legacy dipetakan atau dijadikan exception. | P0 | E-01, E-02 | Mapping coverage disetujui. |
| E-10 Opening balance | Posisi awal V2 dapat dibuktikan. | P0 | E-05, E-09 | Opening tie-out lulus. |
| E-11 Parallel verification | Laporan V2 dibandingkan dengan baseline independen. | P1 | E-06, E-10 | Variance register terselesaikan. |
| E-12 Training & runbook | Pengelola mampu menjalankan proses baru. | P1 | E-04 s.d. E-08 | Simulation sign-off. |
| E-13 Cutover & hypercare | V2 menjadi sumber saldo resmi tanpa jalur paralel. | P0 | Semua P0/P1 relevan | G4 go-live approved. |

### Urutan delivery wajib

```text
Policy closure
      ↓
Master dimensions + matrix
      ↓
Voucher / period / transaction catalogue
      ↓
Posting + ledger + invariants
      ↓
Reports + audit lineage
      ↓
Legacy mapping + opening balance rehearsal
      ↓
Parallel verification + training
      ↓
Cutover + hypercare
```

Dashboard, budget, dan automated reconciliation tidak boleh mendahului langkah di atas.

---

# 6. Desain proses bisnis inti

## 6.1 Penerimaan

```text
Bukti dana diterima
        ↓
Identifikasi Rekening, Fund, donor/source, dan maksud
        ↓
Validasi bukti, kelengkapan, periode, dan split
        ↓
Verifikasi / approval sesuai kebijakan
        ↓
Posting RCV yang balanced
        ↓
Voucher, journal reference, audit event, dan laporan tersedia
```

### Kontrol penerimaan

- Jika maksud donor tidak dapat ditentukan, dana diberi status belum teridentifikasi sesuai kebijakan; tidak dibelanjakan sebelum klasifikasinya sah.
- Rekening penerima tidak menentukan Fund tanpa bukti atau default mapping yang telah disahkan.
- Satu penerimaan yang mencakup lebih dari satu Fund harus di-split pada saat yang sama; tidak boleh dicatat total dahulu lalu dipisah secara tidak terlacak.
- Penerimaan barang/nonkas tidak boleh diperlakukan seperti kas tanpa ketetapan accounting dan evidence yang sesuai.
- Retry atau penyerahan ulang data tidak boleh menciptakan RCV kedua untuk bukti yang sama.

## 6.2 Pengeluaran

```text
Kebutuhan dan bukti diajukan
        ↓
Pilih Fund, tujuan, Program (jika wajib), dan Rekening pembayar
        ↓
Uji allowed/prohibited, saldo/policy exception, dan kelengkapan
        ↓
Verifikasi / approval / eksekusi pembayaran
        ↓
Posting PAY yang balanced
        ↓
Simpan voucher, bukti eksekusi, dan jejak audit
```

### Kontrol pengeluaran

- Fund harus dipilih berdasarkan sumber pembiayaan yang sah, bukan berdasarkan Rekening dengan saldo terbesar.
- Bukti tidak lengkap hanya dapat diproses sebagai exception yang disetujui dengan reason dan tenggat pelengkapan.
- Pembayaran yang mendanai lebih dari satu Fund wajib menggunakan split yang membuktikan dasar pembagiannya.
- Program membantu pelaporan penggunaan; Program tidak memberikan izin penggunaan Fund yang dilarang.
- Dana restricted hanya dapat digunakan sesuai manual kebijakan dan keputusan khusus yang terdokumentasi.

## 6.3 Transfer fisik antar-Rekening

```text
Kebutuhan pemindahan likuiditas disetujui
        ↓
Pilih Rekening asal dan tujuan
        ↓
Tentukan komposisi Fund yang ikut berpindah
        ↓
Eksekusi dan buktikan kedua sisi
        ↓
Posting TRF balanced
        ↓
Tie-out: total Rekening dan total masing-masing Fund tetap
```

### Kontrol transfer

- Transfer bank ke kas, kas ke bank, atau bank ke bank bukan penerimaan atau pengeluaran.
- Jika satu Rekening menyimpan banyak Fund, transfer wajib mempertahankan komposisi Fund atau menyatakan split Fund dengan jelas.
- Tidak ada perubahan ownership Fund pada TRF; perubahan ownership adalah interfund transfer yang berada di scope Phase 3.
- Transfer tidak boleh digunakan untuk menutupi perbedaan saldo atau membuat Fund restricted tampak tersedia untuk keperluan lain.

## 6.4 Reversal dan adjustment

| Aspek | Reversal | Adjustment |
|---|---|---|
| Tujuan | Membalik transaksi posted tertentu yang salah/harus dibatalkan. | Mengoreksi posisi accounting yang tidak dapat diselesaikan cukup dengan reversal. |
| Referensi | Wajib journal/transaksi asal. | Wajib issue/exception case dan alasan. |
| Dampak | Kebalikan terukur dari jurnal asal. | Sesuai sifat koreksi yang disetujui. |
| Histori | Jurnal asal tetap ada. | Tidak mengubah jurnal asal. |
| Approval | Sesuai limit dan risiko transaksi asal. | Lebih ketat; mengikuti materiality/approval matrix. |
| Bukti | Alasan pembatalan dan pendukung. | Evidence investigasi, dasar accounting, dan approval. |

### Larangan mutlak

- Mengubah nominal, Fund, Rekening, tanggal accounting, atau Account dari jurnal yang sudah posted.
- Menghapus voucher agar saldo tampak benar.
- Menggunakan adjustment umum tanpa reason yang dapat diuji.
- Memakai reversal untuk menutup error tanpa menautkannya ke transaksi asal.

---

# 7. Strategi migrasi dan opening balance

## 7.1 Tujuan migrasi

Migrasi Phase 2 tidak bertujuan memindahkan seluruh bentuk data legacy. Tujuannya adalah membentuk **opening position yang terbukti** dan menjaga data legacy sebagai arsip read-only yang masih dapat dilacak.

## 7.2 Objek inventory

| Objek legacy | Perlakuan target | Evidence yang dicari |
|---|---|---|
| Saldo bank per rekening | Rekonsiliasi ke external statement lalu opening balance. | Statement pada cutoff, bukti rekening, hasil tie-out. |
| Kas fisik/petty cash | Cash count lalu opening balance. | Berita acara cash count dan custodian sign-off. |
| Penerimaan/pengeluaran historis | Diarsipkan; hanya dimigrasi rinci jika nilai audit/operasionalnya melebihi manfaat/risiko. | Bukti, voucher, status, mapping. |
| Dana/program legacy | Dipetakan ke Fund/Program resmi atau exception bucket. | Mapping rationale dan approval. |
| Saldo “program” lama | Tidak diasumsikan saldo Fund. Diurai berdasarkan evidence. | Laporan sumber, donor restriction, dan transaksi pendukung. |
| Attachment | Dipertahankan sebagai archive dengan referensi ke record asal. | Index/katalog evidence. |
| Saldo negatif/anomali | Masuk exception register; tidak disembunyikan. | Investigasi, keputusan, correction plan. |

## 7.3 Tahapan migrasi

### M-1 — Discovery dan freeze candidate

- Inventarisir semua sumber keuangan legacy, rekening, cash box, spreadsheet, jurnal manual, dan laporan.
- Tentukan data owner untuk setiap sumber serta tingkat keandalannya.
- Tandai record yang sudah tidak dapat diubah dan record yang masih aktif.
- Bentuk daftar awal asumsi dan risiko, bukan langsung memetakan nilai.

### M-2 — Mapping dan classification

- Petakan setiap saldo/record ke Account, Fund, Rekening, Program bila relevan, serta source reference.
- Tetapkan status mapping: **confirmed**, **provisional**, **exception**, atau **out of scope archive**.
- Dilarang menggunakan Fund umum sebagai tempat pembuangan untuk seluruh data yang tidak jelas.
- Mapping Fund restricted memerlukan evidence donor/akad/ikrar atau ketetapan policy yang berlaku.

### M-3 — Reconciliation baseline

- Cocokkan rekening bank ke statement eksternal pada tanggal cutoff.
- Lakukan cash count untuk kas fisik dengan berita acara.
- Bandingkan total kas/bank legacy dengan bukti eksternal dan catat seluruh selisih.
- Buat fund composition schedule per Rekening. Jika belum diketahui, masukkan ke exception register dengan batas penyelesaian.

### M-4 — Correction dan exception resolution

| Status | Definisi | Tindakan sebelum cutover |
|---|---|---|
| Confirmed | Account, Fund, Rekening, dan nilai didukung evidence. | Siap untuk opening balance. |
| Provisional | Nilai didukung sebagian tetapi ada informasi nonmaterial yang belum lengkap. | Hanya boleh masuk dengan approval dan tenggat resolusi. |
| Exception — material | Selisih/klasifikasi dapat memengaruhi Fund restriction atau laporan material. | Wajib diselesaikan atau secara eksplisit diputuskan steering group. |
| Exception — nonmaterial | Selisih kecil tetapi tetap memerlukan penjelasan. | Dapat dibawa dengan adjustment/monitoring disetujui. |
| Out of scope archive | Tidak memengaruhi posisi awal atau tidak layak dimigrasikan rinci. | Arsipkan, indekskan, dan jaga lineage. |

### M-5 — Opening balance rehearsal

- Susun draft opening position pada lingkungan uji/simulasi.
- Uji setiap rekonsiliasi Rekening, Fund, Account, dan total debit/kredit.
- Uji saldo setelah transfer contoh, penerimaan contoh, pengeluaran contoh, reversal, dan adjustment.
- Tinjau voucher, attachment, audit event, serta laporan dasar yang dihasilkan.
- Catat seluruh deviasi dalam rehearsal issue log lalu perbaiki kebijakan/mapping/proses sebelum rehearsal berikutnya.

### M-6 — Cutover execution

- Terapkan freeze transaksi legacy sesuai cutoff plan.
- Ambil bukti bank/cash count terakhir dan selesaikan transaksi in-flight sesuai aturan cutoff.
- Jalankan opening balance hanya setelah evidence package disetujui.
- Verifikasi trial balance, saldo Rekening, saldo Fund, dan daftar exception segera setelah opening posting.
- Aktifkan V2 sebagai satu-satunya jalur transaksi resmi pada waktu yang disetujui.

### M-7 — Parallel verification dan archive

- Jalankan laporan V2 berdampingan dengan laporan baseline untuk jangka waktu yang ditetapkan.
- Beda angka harus masuk variance register, bukan diselesaikan lewat pengeditan saldo.
- Legacy menjadi read-only setelah go-live; aksesnya tetap tersedia untuk audit dan investigasi.
- Setelah exit criteria hypercare terpenuhi, tetapkan V2 sebagai sumber laporan resmi.

## 7.4 Opening balance packet

Setiap opening balance harus memiliki paket evidence berikut.

| Isi paket | Wajib |
|---|---:|
| Cutover charter dan tanggal cutoff | Ya |
| Daftar Rekening aktif dan saldo statement/cash count | Ya |
| Schedule saldo per Account–Fund–Rekening | Ya |
| Trial balance opening | Ya |
| Rekonsiliasi total Fund dan total kas/bank | Ya |
| Mapping source dan status evidence | Ya |
| Daftar exception, nilai, owner, dan tenggat | Ya |
| Approval Finance Controller dan otoritas sesuai mandate | Ya |
| Review khusus untuk Fund Zakat/Fidyah/Wakaf/Qurban bila relevan | Ya |
| Catatan adjustment transparan | Bila ada |

## 7.5 Kriteria penerimaan opening position

1. total debit dan kredit opening balance seimbang;
2. saldo setiap Rekening tie-out dengan evidence eksternal atau cash count;
3. total semua Fund dalam sebuah Rekening sama dengan saldo buku Rekening tersebut;
4. Fund restricted tidak digabung tanpa dasar yang disahkan;
5. adjustment dicatat terpisah dari opening amount murni;
6. semua exception material memiliki keputusan go/no-go;
7. paket evidence dapat direview tanpa kembali bergantung pada ingatan operator.

---

# 8. Strategi testing dan assurance

## 8.1 Lapisan pengujian

| Lapisan | Fokus | Pemilik evidence |
|---|---|---|
| Policy walkthrough | Apakah proses mencerminkan kebijakan yang sudah disahkan? | Policy owner / Finance Controller |
| Master data test | Apakah master dapat mendukung klasifikasi sah dan mencegah pilihan yang salah? | Finance Controller |
| Transaction scenario test | Apakah data, split, bukti, approval, dan period control bekerja sesuai aturan? | Process owner |
| Accounting invariant test | Apakah ledger balanced, immutable, idempotent, dan rebuildable? | Delivery team + Finance Controller |
| Report tie-out test | Apakah laporan menghasilkan angka yang sama dengan ledger? | Finance Controller |
| Migration rehearsal | Apakah opening position dan exception register dapat dipertanggungjawabkan? | Migration lead |
| User acceptance test | Apakah pengguna menjalankan skenario nyata tanpa jalur legacy? | Business owner |
| Cutover readiness review | Apakah seluruh sign-off, bukti, dan rencana contingency siap? | Steering group |

## 8.2 Minimum test scenario catalogue

| ID | Skenario | Hasil yang harus dibuktikan | Referensi baseline |
|---|---|---|---|
| T-01 | Penerimaan donasi umum ke bank | Rekening dan Fund yang tepat bertambah; jurnal balance; voucher dan bukti terbentuk. | BR-051, BR-066 s.d. BR-080 |
| T-02 | Penerimaan Zakat Maal | Hanya Fund Zakat bertambah; detail sumber dapat ditelusuri. | BR-013 s.d. BR-030 |
| T-03 | Penerimaan dengan Fund belum jelas | Tidak dapat digunakan sebelum identifikasi sah. | BR-055, BR-056 |
| T-04 | Satu penerimaan untuk dua Fund | Total split sama dengan header dan kedua Fund terlapor benar. | BR-057, INV-06 |
| T-05 | Pembayaran listrik dari Fund Operasional | Kas/bank turun, beban tercatat, Program jika diwajibkan terisi. | BR-058 s.d. BR-060 |
| T-06 | Coba membayar operasional dari Fund Zakat | Ditolak atau masuk exception yang hanya dapat diputuskan policy berwenang; tidak silently posted. | BR-017, BR-063 |
| T-07 | Satu invoice dibiayai dua Fund | Split expense balanced dan evidence pembagian tersedia. | BR-059, BR-060 |
| T-08 | Transfer satu Fund antar bank | Total aset dan Fund tidak berubah; hanya lokasi aset berubah. | BR-064, INV-05 |
| T-09 | Transfer multi-Fund Bank ke Kas | Komposisi tiap Fund tetap dan tie-out ke kas tujuan. | BR-064, INV-05 |
| T-10 | Transfer dipaksa menjadi pendapatan | Sistem/proses menolak classification tersebut. | BR-065 |
| T-11 | Submit ulang bukti yang sama | Hanya ada satu posting resmi. | BR-071, INV-03 |
| T-12 | Jurnal tidak balance | Tidak dapat posted. | BR-066, INV-01 |
| T-13 | Edit transaksi/jurnal posted | Tidak boleh mengubah histori; hanya reversal/adjustment. | BR-067, BR-068 |
| T-14 | Reversal transaksi penerimaan/pembayaran | Jurnal asal tetap ada dan saldo berbalik terukur. | BR-069, BR-070 |
| T-15 | Adjustment tanpa alasan | Tidak dapat posted. | BR-070, BR-107 |
| T-16 | Nomor voucher duplikat | Tidak ada dua voucher dalam scope yang sama. | BR-100 s.d. BR-104 |
| T-17 | Lampiran diganti pasca posting | Perubahan menciptakan audit event dan versi/lineage. | BR-105 s.d. BR-112 |
| T-18 | Tanggal di luar periode eligible | Posting ditolak. | BR-081 s.d. BR-090 |
| T-19 | Saldo Rekening dibandingkan dengan saldo Fund dalam Rekening | Tie-out berhasil pada angka yang sama. | BR-075, BR-076 |
| T-20 | Trial balance setelah rangkaian transaksi | Debit dan kredit tetap seimbang. | BR-066, BR-117 |
| T-21 | Opening bank balance | Tie-out ke statement dan komposisi Fund tersedia. | BR-035, BR-084 |
| T-22 | Opening cash balance | Tie-out ke berita acara cash count. | BR-036, BR-084 |
| T-23 | Saldo legacy ambigu | Muncul di exception register, bukan dipaksa ke Fund umum. | BR-085, BR-108 |
| T-24 | Rekonsiliasi ulang dari ledger | Saldo dapat dibangun ulang tanpa sumber saldo manual. | BR-072 s.d. BR-076 |
| T-25 | Drill-down laporan | Angka laporan menuju journal, transaksi, dan bukti. | BR-117 s.d. BR-122 |

## 8.3 Kriteria kualitas sebelum UAT

- tidak ada defect Severity 1 atau Severity 2 terbuka pada transaksi P0;
- seluruh skenario T-01 s.d. T-25 memiliki evidence hasil;
- semua gap policy diberi keputusan atau dikeluarkan dari go-live scope;
- ledger, saldo Rekening, saldo Fund, dan trial balance tie-out;
- tidak ada jalur legacy yang masih dapat memengaruhi saldo resmi setelah cutover;
- migrasi rehearsal dilakukan minimal sekali sampai berhasil tanpa adjustment yang tidak dapat dijelaskan.

## 8.4 Evidence pack UAT

1. daftar skenario, data uji, pelaksana, tanggal, dan status;
2. screenshot/export laporan dan voucher hasil yang relevan;
3. journal/ledger trace untuk setiap transaksi inti;
4. hasil balancing dan tie-out;
5. daftar defect dan keputusan retest;
6. daftar exception beserta approval;
7. UAT sign-off oleh pemilik proses dan Finance Controller.

---

# 9. Roadmap delivery indikatif

Estimasi ini harus dipakai sebagai urutan dan gate, bukan janji tanggal. Durasi aktual bergantung pada kesiapan keputusan, kualitas data legacy, dan ketersediaan reviewer.

| Gelombang | Fokus | Output utama | Gate |
|---|---|---|---|
| 0 — Mobilisasi | Charter, scope, governance, decision log, data inventory. | Rencana kerja dan owner jelas. | G0: mobilisasi disetujui |
| 1 — Policy closure | D-01 s.d. D-06 dan Fund/CoA/master matrix. | Baseline kebijakan siap diuji. | G1: policy/master ready |
| 2 — Core design | D-07 s.d. D-10, catalogue transaksi, voucher/evidence/period policy. | Skenario dan rule tidak ambigu. | G2: build-ready |
| 3 — Ledger foundation | Transaksi inti, posting, journal, ledger, inquiry, dan audit lineage. | Invariant core lulus. | Internal quality gate |
| 4 — Migration rehearsal | Mapping legacy, exception register, draft opening, dan report tie-out. | Rehearsal position dapat dibuktikan. | G3: cutover-ready candidate |
| 5 — UAT & training | UAT end-to-end, runbook, simulation operator, remediation. | Sign-off UAT. | G4: go/no-go |
| 6 — Cutover & hypercare | Freeze, opening, go-live, daily control, parallel verification. | V2 menjadi sumber saldo resmi. | G5: stabilisasi selesai |

## 9.1 Gate governance

| Gate | Pertanyaan keputusan | Minimum evidence | Keputusan |
|---|---|---|---|
| G0 | Apakah scope, owner, dan metode kerja jelas? | Charter, RACI, issue log. | Mulai discovery. |
| G1 | Apakah policy, Fund, Rekening, CoA, dan dimensi siap menjadi baseline? | D-01 s.d. D-06 approved. | Lanjut core design. |
| G2 | Apakah transaksi inti bisa diposting tanpa asumsi kebijakan? | Catalogue, matrices, test scenarios. | Lanjut build/UAT preparation. |
| G3 | Apakah opening position candidate dapat di-tie-out? | Rehearsal report, exception register, evidence packet. | Siap kandidat cutover. |
| G4 | Apakah kualitas, UAT, training, dan contingency memenuhi go/no-go? | UAT sign-off, defect report, runbook. | Go-live atau tunda. |
| G5 | Apakah V2 stabil dan ledger menjadi satu-satunya sumber saldo? | Hypercare report, variance closure, legacy lock confirmation. | Tutup Phase 2. |

---

# 10. Cutover playbook

## 10.1 Persiapan sebelum cutoff

- Cutover charter memuat tanggal, zona waktu, cutoff, transaksi in-flight, daftar pihak yang dihubungi, serta jalur escalation.
- Semua Rekening dan cash location yang termasuk harus memiliki owner/custodian dan evidence source.
- Seluruh mapping material berstatus confirmed atau memiliki keputusan exception yang disahkan.
- Paket opening balance harus direview tanpa perubahan mendadak pada hari H.
- Runbook menjelaskan urutan, pelaksana, bukti output, dan kriteria berhenti pada setiap langkah.

## 10.2 Hari cutover

```text
Freeze transaksi legacy
        ↓
Ambil statement/cash count cutoff dan daftar transaksi in-flight
        ↓
Finalisasi exception register
        ↓
Persetujuan opening balance packet
        ↓
Posting opening balance V2
        ↓
Tie-out ledger, Rekening, Fund, dan trial balance
        ↓
Go / no-go oleh otoritas yang ditetapkan
        ↓
Aktifkan jalur V2 dan lock jalur legacy
```

## 10.3 Go/no-go criteria

| Area | Go | No-go / eskalasi |
|---|---|---|
| Balancing | Trial balance seimbang. | Debit/kredit tidak seimbang. |
| Rekening | Saldo semua Rekening tie-out dengan evidence. | Ada rekening material tanpa tie-out. |
| Fund | Komposisi Fund per Rekening dan total Fund dapat dijelaskan. | Fund restricted material tidak dapat dipetakan. |
| Exceptions | Tidak ada exception material terbuka tanpa keputusan explicit. | Exception material tidak diketahui pemilik/penyelesaiannya. |
| Transactions | RCV, PAY, TRF, REV, ADJ dapat diuji dan posting sekali. | Ada posting ganda, partial, atau edit histori. |
| Evidence | Paket opening dan runbook lengkap. | Bukti utama hilang atau tidak dapat direview. |
| Operations | Operator telah melakukan simulation dan tahu jalur escalation. | Pengelola belum siap menjalankan proses baru. |
| Legacy | Jalur lama telah siap dikunci dan tidak menulis saldo paralel. | Saldo lama masih akan menjadi sumber resmi. |

## 10.4 Contingency principle

Contingency bukan alasan mengaktifkan dua sumber saldo. Jika go-live tidak memenuhi kriteria, keputusan yang benar adalah **menunda cutover** dan mempertahankan proses lama yang telah ditetapkan hingga masalah diselesaikan. Seluruh kegiatan selama penundaan harus dicatat agar posisi cutoff berikutnya dapat direkonsiliasi kembali.

## 10.5 Hypercare (periode stabilisasi)

| Frekuensi | Kontrol |
|---|---|
| Harian pada minggu awal | Review transaksi posted, voucher gap, exception, saldo kas/bank utama, dan duplicate indicator. |
| Mingguan | Tie-out Rekening × Fund, trial balance, review reversal/adjustment, serta variance legacy baseline bila masih berjalan. |
| Akhir periode pertama | Review laporan dasar, bukti, jurnal, issue log, dan kesiapan masuk Phase 3. |

Exit hypercare hanya terjadi ketika semua issue material diselesaikan atau memiliki remediation plan yang disetujui, dan tidak ada bukti jalur saldo paralel.

---

# 11. Risiko dan kontrol delivery

| ID | Risiko | Dampak | Indikator dini | Mitigasi | Owner |
|---|---|---|---|---|---|
| R-01 | Fund legacy ambigu | Salah penggunaan dana restricted dan salah laporan. | Banyak mapping provisional/exception. | Policy workshop, evidence review, exception register. | Policy owner |
| R-02 | Rekening dianggap sama dengan Fund | Saldo dan transfer salah arah. | Mapping satu Fund-satu Rekening dipaksakan. | Training dimensi + transfer tests. | Finance Controller |
| R-03 | Posting ganda | Saldo/laporan overstated. | Retry dan source reference tidak terkendali. | Idempotency test, daily duplicate review. | Delivery lead |
| R-04 | Jurnal tidak balance/partial | Ledger tidak dapat dipercaya. | Perbedaan debit-kredit atau data transaksi tanpa jurnal. | Atomic posting/invariant test. | Delivery lead |
| R-05 | Saldo legacy dipakai setelah V2 aktif | Dua sumber kebenaran. | Laporan membaca tabel lama. | Legacy lock, report inventory, go-live gate. | Finance Controller |
| R-06 | Cutover tanpa bukti bank/kas | Opening balance tidak dapat diaudit. | Statement/cash count terlambat. | Evidence packet dan no-go rule. | Migration lead |
| R-07 | Adjustment menjadi jalan pintas | Error dan fraud tersamarkan. | Banyak ADJ tanpa reason/evidence. | Approval matrix, adjustment report. | Policy owner |
| R-08 | Dana syariah diperlakukan sebagai dana umum | Pelanggaran amanah/policy. | Default mapping tidak direview. | Penasihat syariah review dan matrix test. | Policy owner |
| R-09 | Pelatihan hanya fokus form | Proses baru dijalankan sebagai proses lama. | Operator tidak mengerti split/reversal/transfer. | Scenario-based simulation dan runbook. | Finance Controller |
| R-10 | Scope creep ke dashboard/budget | Fondasi ledger terlambat atau kompromi. | Backlog P3/P4 masuk P0. | Gate governance dan change control. | Steering group |
| R-11 | Bukti tidak tersedia/terpisah | Traceability lemah. | Transaksi posted tanpa attachment metadata. | Evidence policy and completeness tests. | Process owner |
| R-12 | Tidak ada review independen | Kesalahan cutover tidak tertangkap. | Satu pihak menyiapkan dan menyetujui semua paket. | RACI dan independent sign-off. | Otoritas keuangan |

---

# 12. Definition of Ready dan Definition of Done

## 12.1 Definition of Ready untuk Phase 2

Phase 2 siap dimulai apabila:

- scope P0 sudah disetujui dan backlog Phase 3/4 dipisahkan;
- policy owner, Finance Controller, Migration lead, dan reviewer independen telah ditunjuk;
- daftar keputusan D-01 s.d. D-16 memiliki status dan tenggat;
- sumber data legacy, Rekening, kas fisik, dan owner evidence telah diinventarisir;
- manual kebijakan dan arsitektur V2 dijadikan baseline terkontrol;
- terdapat mekanisme issue, exception, dan change log yang disepakati.

## 12.2 Definition of Done untuk setiap kapabilitas P0

Sebuah kapabilitas P0 dianggap selesai bila:

1. proses bisnis dan data minimum telah ditentukan;
2. kebijakan dan decision matrix yang dipakai jelas versinya;
3. skenario positif, negatif, split, duplicate, period boundary, dan traceability telah diuji jika relevan;
4. output ledger/laporan dapat di-tie-out;
5. error atau exception memiliki pesan, owner, dan jejak audit yang memadai;
6. runbook serta bukti UAT tersedia;
7. tidak ada ketergantungan diam-diam pada saldo/tabel legacy.

## 12.3 Definition of Done Phase 2

Phase 2 dinyatakan selesai apabila seluruh kondisi berikut terpenuhi:

- kebijakan minimum, master data, dan posting rule catalogue telah disahkan serta diberi effective date;
- RCV, PAY, TRF, OPB, REV, dan ADJ telah berjalan dengan bukti, voucher, audit lineage, dan kontrol yang sesuai;
- seluruh perubahan saldo resmi melalui posting dan General Ledger;
- ledger selalu balanced, immutable setelah posting, retry-safe, dan dapat direbuild;
- trial balance, general ledger, saldo Rekening, dan saldo Fund dapat dihasilkan dan tie-out;
- Rekening × Fund tie-out berhasil untuk seluruh Rekening aktif dalam scope;
- opening balance disertai evidence pack, sign-off, dan exception register;
- legacy telah menjadi arsip read-only dan tidak lagi mengubah saldo resmi;
- UAT, cutover, dan hypercare ditutup oleh sign-off yang berwenang;
- daftar pembelajaran, backlog Phase 3, dan control owner telah diserahkan ke operasi keuangan.

---

# Lampiran A — Template decision log

| Field | Isi |
|---|---|
| Decision ID | Contoh: D-03 |
| Judul keputusan | Pernyataan yang harus diputuskan. |
| Status | Draft / In review / Approved / Superseded. |
| Pilihan | Opsi yang dievaluasi dan opsi terpilih. |
| Dasar | Kebijakan, evidence, masukan syariah/accounting, dan alasan. |
| Dampak | Fund, laporan, cutover, test, atau proses yang terpengaruh. |
| Pemilik | Akuntabel atas keputusan. |
| Reviewer | Pihak yang wajib memberi masukan. |
| Effective date | Tanggal keputusan berlaku. |
| Approval reference | Bukti pengesahan. |

# Lampiran B — Template exception register

| Field | Isi |
|---|---|
| Exception ID | Nomor unik. |
| Jenis | Mapping, evidence, Fund restriction, Rekening, journal, cutover, atau operasional. |
| Severity | Critical / High / Medium / Low. |
| Deskripsi | Fakta, bukan asumsi. |
| Nilai terdampak | Nominal, Fund/Rekening, dan periode jika relevan. |
| Risiko | Dampak accounting, amanah, laporan, atau operasional. |
| Owner | Pihak yang menyelesaikan. |
| Keputusan sementara | Hold, provisional, adjustment, atau out-of-scope archive. |
| Evidence | Tautan/index bukti. |
| Tenggat | Tanggal penyelesaian. |
| Approval | Otoritas yang menyetujui keputusan. |
| Status | Open / Monitoring / Resolved / Accepted risk. |

# Lampiran C — Checklist sign-off go-live

- [ ] D-01 s.d. D-16 diputuskan atau dibatasi secara eksplisit dari scope.
- [ ] Fund register, Rekening register, CoA, dan matrix dimensi disahkan.
- [ ] Transaction/posting rule catalogue versi go-live disetujui.
- [ ] Semua skenario P0 lulus dan evidence UAT tersedia.
- [ ] Tidak ada defect Severity 1/2 terbuka pada scope go-live.
- [ ] Opening balance rehearsal terbaru tie-out.
- [ ] Evidence bank/kas cutoff siap dan daftar transaksi in-flight jelas.
- [ ] Exception material telah diselesaikan atau mendapat keputusan go/no-go eksplisit.
- [ ] Runbook, escalation contact, dan training operator tersedia.
- [ ] Laporan dasar dan tie-out telah direview Finance Controller.
- [ ] Jalur legacy yang menulis saldo telah siap dinonaktifkan.
- [ ] Otoritas berwenang memberikan keputusan go-live tertulis.

---

Blueprint ini menjadi dasar perincian functional specification, delivery plan, test case terstruktur, migration workbook, dan runbook operasi. Dokumen tersebut harus selalu mengacu pada manual kebijakan dan arsitektur V2, serta tidak boleh mengubah prinsip bahwa posted General Ledger adalah satu-satunya sumber saldo resmi.
