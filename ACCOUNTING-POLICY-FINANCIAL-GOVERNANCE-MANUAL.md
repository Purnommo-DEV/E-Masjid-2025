# Accounting Policy & Financial Governance Manual

## Sistem Informasi Manajemen Masjid

**Nomor dokumen:** APFGM-V2  
**Versi:** 1.0 — Draft untuk pengesahan  
**Tanggal:** 4 Agustus 2026  
**Berlaku sejak:** ditetapkan melalui keputusan pengurus yang berwenang  
**Pemilik kebijakan:** Fungsi Keuangan Masjid  
**Ruang lingkup:** seluruh penerimaan, pengeluaran, kas, bank, dana, program, pencatatan, rekonsiliasi, closing, pelaporan, bukti, dan audit keuangan Masjid

---

## Status, Kedudukan, dan Cara Menggunakan Manual

Manual ini adalah kebijakan bisnis dan accounting untuk Financial Architecture V2. Manual menetapkan keputusan yang harus dipatuhi oleh proses operasional dan sistem informasi. Ia bukan spesifikasi teknis, bukan kode aplikasi, bukan rancangan database, dan bukan nasihat hukum atau fatwa.

Istilah normatif yang digunakan:

| Istilah | Arti |
|---|---|
| **Wajib** | Harus dipenuhi. Sistem atau proses harus menolak pelanggaran. |
| **Dilarang** | Tidak boleh dilakukan. |
| **Dapat** | Diperbolehkan jika ketentuan lain terpenuhi. |
| **Bersyarat** | Hanya boleh setelah syarat, bukti, dan otorisasi yang ditetapkan terpenuhi. |
| **Policy owner** | Pihak pengurus yang menetapkan atau mengubah kebijakan finansial. |

Ketentuan Zakat, Fidyah, Wakaf, Qurban, dan dana syariah lain dalam manual ini adalah pagar operasional minimum. Penetapan detail penerima, nisab, asnaf, porsi amil, peruntukan, dan penanganan kasus khusus wajib dikonfirmasi terhadap kebijakan syariah dan regulasi yang berlaku. UU Pengelolaan Zakat mengatur pengumpulan, pendistribusian, pendayagunaan, serta pelaporan zakat; pengelolaan wakaf harus mengikuti tujuan/peruntukan wakaf dan ketentuan yang berlaku. Rujukan utama tersedia pada [UU No. 23 Tahun 2011 tentang Pengelolaan Zakat](https://peraturan.bpk.go.id/Details/39267/uu-no-23tahun-2011), [PP No. 14 Tahun 2014](https://peraturan.bpk.go.id/Details/5451), dan [Pedoman Pengelolaan Harta Benda Wakaf BWI](https://www.bwi.go.id/10896/2024/11/13/peraturan-bwi-nomor-1-tahun-2020-tentang-pedoman-pengelolaan-dan-pengembangan-harta-benda-wakaf/).

---

# BAB 1 — Visi dan Tujuan Pengelolaan Keuangan Masjid

## 1.1 Visi

Mewujudkan pengelolaan keuangan Masjid yang amanah, tertib, transparan, akuntabel, dapat diaudit, patuh terhadap prinsip syariah, dan mampu menjaga pemisahan setiap Dana sampai ke sumber penerimaan, lokasi Rekening, penggunaan Program, bukti, dan laporan.

## 1.2 Tujuan

1. Menjaga amanah jamaah, donatur, muzakki, wakif, penyewa, dan penerima manfaat.
2. Memastikan setiap rupiah berada pada Dana yang benar dan digunakan sesuai peruntukannya.
3. Menjaga kas dan bank melalui pencatatan double entry, rekonsiliasi, dan closing periodik.
4. Menjadikan General Ledger sebagai satu-satunya sumber saldo resmi.
5. Mencegah dana bercampur, saldo negatif, double posting, manipulasi histori, dan salah klasifikasi.
6. Menyediakan laporan yang konsisten untuk pengurus, jamaah, auditor, dan pihak yang berhak.
7. Memungkinkan pertumbuhan jumlah Rekening, Dana, Program, transaksi, cabang, dan unit tanpa mengubah prinsip dasar.

## 1.3 Prinsip pengelolaan

| Prinsip | Penerapan kebijakan |
|---|---|
| **Amanah** | Dana dikelola hanya untuk tujuan yang sah, terdokumentasi, dan dapat dipertanggungjawabkan. |
| **Transparansi** | Informasi keuangan disajikan tepat waktu, konsisten, dan dapat ditelusuri sampai bukti. |
| **Akuntabilitas** | Setiap transaksi memiliki pihak penanggung jawab, nomor bukti, alasan, dan jejak audit. |
| **Fund Accounting** | Dana dipisahkan berdasarkan pembatasan dan tujuan, bukan hanya berdasarkan rekening bank. |
| **Double Entry Accounting** | Setiap transaksi finansial menghasilkan jurnal dengan debit sama dengan kredit. |
| **Single Source of Truth** | Hanya ledger yang telah posted menjadi sumber saldo resmi. |
| **Audit Trail** | Histori posted transaction tidak dihapus; koreksi dilakukan melalui reversal atau adjustment. |
| **Syariah compliance** | Dana syariah digunakan sesuai ketentuan dan peruntukan yang disahkan. |
| **Kehati-hatian** | Ketidakjelasan Fund, bukti, atau tujuan ditahan sebagai exception, bukan diputuskan otomatis. |
| **Segregation of duties** | Pengajuan, verifikasi, pembayaran, posting, dan review dipisahkan sesuai kapasitas organisasi. |

## 1.4 Prinsip arsitektur yang mengikat kebijakan

- Rekening, Dana, Program, Kategori, Akun, dan Transaksi adalah konsep terpisah.
- Satu Rekening dapat memuat banyak Dana.
- Satu Dana dapat tersebar di beberapa Rekening.
- Program adalah cost center; Program bukan Dana dan tidak memiliki saldo kas sendiri.
- Saldo tidak boleh diketik, diubah, atau dijadikan sumber kebenaran di luar posted ledger.
- Internal transfer tidak boleh dihitung sebagai pendapatan atau pengeluaran.
- Budget bukan saldo kas dan budget allocation bukan otomatis transaksi accounting.

---

# BAB 2 — Definisi Resmi

| Istilah | Definisi resmi | Batasan agar tidak tumpang tindih |
|---|---|---|
| **Rekening** | Tempat fisik atau instrumen tempat kas/aset likuid disimpan: kas, petty cash, rekening bank, atau instrumen pembayaran. | Rekening bukan Dana, bukan Program, dan bukan kategori. |
| **Dana / Fund** | Kumpulan sumber daya dengan pembatasan, tujuan, sumber, atau status amanah tertentu yang wajib dipertanggungjawabkan terpisah. | Dana bukan rekening bank dan bukan akun pendapatan/beban. |
| **Program** | Kegiatan atau cost center yang menerima pembiayaan dan menjadi dasar pengukuran budget serta realisasi. | Program tidak memiliki Fund, Rekening, atau saldo sendiri. |
| **Kategori** | Pengelompokan operasional untuk menjelaskan tujuan transaksi dan membantu pemilihan aturan posting. | Kategori bukan akun GL dan tidak menentukan saldo. |
| **Akun / GL Account** | Klasifikasi accounting dalam Chart of Accounts, misalnya kas, bank, pendapatan, beban, aset, liabilitas, dan net assets. | Akun tidak menggantikan identitas Dana atau Program. |
| **Transaksi** | Peristiwa bisnis yang didukung dokumen, misalnya penerimaan, pembayaran, transfer, distribusi, atau adjustment. | Transaksi belum memengaruhi saldo sebelum diposting. |
| **Jurnal** | Catatan double entry dari transaksi yang siap atau telah diposting. Terdiri dari header dan minimal dua baris. | Jurnal bukan form input dan bukan laporan. |
| **Ledger** | Kumpulan seluruh baris jurnal berstatus posted yang tidak dapat diubah serta menjadi sumber saldo resmi. | Ledger bukan rekap manual atau cache saldo. |
| **Saldo Rekening** | Saldo buku suatu kas/bank yang dihitung dari baris ledger terkait Rekening tersebut. | Bukan total seluruh Dana tanpa analisis dimensi. |
| **Saldo Dana** | Posisi bersih suatu Fund berdasarkan seluruh aktivitas ledger yang dialokasikan ke Fund tersebut. | Bukan saldo satu Rekening tertentu dan bukan budget Program. |
| **Budget** | Rencana jumlah dana yang boleh digunakan untuk kombinasi periode, Fund, Program, dan kategori/account tertentu. | Budget tidak sama dengan kas tersedia dan tidak otomatis menghasilkan jurnal. |
| **Realisasi** | Penggunaan aktual yang sudah terjadi dan dibuktikan oleh transaksi posted. | Realisasi bukan status manual yang boleh diubah tanpa transaksi. |
| **Transfer Rekening** | Pemindahan lokasi aset dari satu Rekening ke Rekening lain dengan Fund tetap sama. | Bukan pendapatan, beban, atau perubahan pemilik Fund. |
| **Interfund Transfer** | Pemindahan atau reklasifikasi kepemilikan dari Fund sumber ke Fund tujuan yang diizinkan policy. | Bukan transfer rekening biasa dan bukan budget allocation. |
| **Budget Allocation** | Penetapan rencana pembiayaan Fund untuk Program/kategori/periode. | Tidak mengubah kas, Fund balance, atau ledger. |
| **Closing** | Proses pengendalian akhir periode untuk memastikan transaksi lengkap, rekonsiliasi selesai, laporan final, dan periode dikunci. | Bukan sekadar menginput saldo awal. |
| **Rekonsiliasi** | Pencocokan saldo buku ledger dengan bukti eksternal seperti bank statement atau cash count. | Bukan penyesuaian saldo tanpa bukti. |
| **Audit Trail** | Jejak immutable tentang siapa, kapan, apa, mengapa, dan bagaimana transaksi/policy berubah atau diposting. | Bukan hanya log teknis aplikasi. |
| **Lampiran** | Bukti pendukung transaksi atau kontrol: kuitansi, invoice, PDF, bukti transfer, statement, berita acara, dan dokumen legal. | Lampiran tidak menggantikan jurnal atau approval. |
| **Nomor Bukti** | Identitas unik dan tetap bagi dokumen bisnis, voucher, transfer, closing, atau rekonsiliasi. | Tidak boleh dipakai ulang atau diubah setelah terbit. |
| **Counterparty** | Pihak eksternal yang berhubungan dengan transaksi: donatur, muzakki, vendor, penerima manfaat, penyewa, atau bank. | Counterparty bukan Fund ataupun Program. |
| **Commitment/Hold** | Pencadangan atas Fund/budget untuk kewajiban yang belum menjadi pengeluaran aktual. | Tidak boleh dihitung dua kali sebagai realisasi. |
| **Adjustment** | Transaksi koreksi yang menambah, mengurangi, atau mereklasifikasi saldo secara transparan. | Tidak mengganti atau menghapus jurnal historis. |
| **Reversal** | Jurnal baru yang membalik seluruh atau sebagian dampak jurnal asli. | Tidak menghapus jurnal asli. |

---

# BAB 3 — Klasifikasi Dana

## 3.1 Prinsip klasifikasi

1. Setiap penerimaan wajib memperoleh Fund sebelum posted.
2. Nama rekening bank tidak pernah menjadi satu-satunya dasar penentuan Fund.
3. Maksud tertulis/tercatat pemberi dana selalu didahulukan dari default sistem.
4. Dana yang tidak jelas tidak boleh otomatis dianggap Dana Operasional; dana tersebut masuk status **Penerimaan Belum Teridentifikasi** sampai diklarifikasi.
5. Dana restricted hanya boleh digunakan sesuai peruntukan dan restriction policy yang berlaku pada saat posting.
6. Jika donor memberikan restriction baru pada Dana yang sudah ada, restriction tersebut dicatat sebagai designation khusus; tidak mengubah histori Dana lain.

## 3.2 Klasifikasi Dana inti

| Nama Dana | Jenis | Status | Tujuan | Penggunaan yang diperbolehkan | Yang dilarang | Saldo minimum | Catatan khusus |
|---|---|---|---|---|---|---|---|
| Dana Operasional | Dana umum | Unrestricted atau internally designated | Menjaga operasi rutin Masjid. | Listrik, air, internet, honor yang disetujui, ATK, kebersihan, keamanan, pemeliharaan rutin, biaya bank, dan kegiatan operasional sah. | Penggunaan yang telah ditentukan khusus untuk Zakat, Fidyah, Wakaf, Qurban, atau dana donor restricted. | Minimal sesuai reserve policy pengurus; default tidak boleh negatif. | Dana umum tidak boleh menjadi tempat parkir dana belum teridentifikasi. |
| Dana Zakat Maal | Dana syariah | Restricted/statutory | Pengelolaan dan penyaluran Zakat Maal sesuai kebijakan syariah dan regulasi yang berlaku. | Penyaluran kepada pihak yang berhak sesuai kebijakan Zakat yang disahkan; biaya amil hanya apabila diperbolehkan dan didokumentasikan. | Listrik, pembangunan umum, honor operasional umum, acara non-zakat, peminjaman ke Dana lain, atau penggunaan di luar kebijakan Zakat. | Tidak boleh negatif. | Identitas muzakki, jenis zakat, restriction, dan bukti penyaluran harus dapat ditelusuri. |
| Dana Zakat Fitrah | Dana syariah musiman | Restricted/statutory | Pengelolaan Zakat Fitrah sesuai kebijakan syariah dan periode yang berlaku. | Penyaluran kepada penerima yang sah, dalam bentuk dan mekanisme yang disahkan policy. | Operasional umum, pembangunan, pembiayaan program non-zakat, atau pengalihan ke Dana lain tanpa dasar sah. | Tidak boleh negatif. | Uang dan barang/in-kind dipisahkan dalam pencatatan namun tetap berada pada Fund Zakat Fitrah. |
| Dana Infaq Umum | Dana sosial umum | Unrestricted kecuali ada pesan donor | Mendukung kemaslahatan umum Masjid dan kegiatan sosial yang disahkan. | Operasional, sosial, pendidikan, dakwah, pemeliharaan, dan program yang disetujui. | Penggunaan yang bertentangan dengan pesan donor atau policy. | Tidak boleh negatif. | Bila donor menyebut tujuan khusus, transaksi menjadi donor-restricted designation. |
| Dana Tromol | Dana pengumpulan | Unrestricted atau restricted sesuai label media pengumpulan | Menampung hasil kotak/tromol. | Mengikuti label tromol dan restriction yang tercantum pada media pengumpulan. | Mengabaikan label “Zakat”, “Wakaf”, “Santunan”, atau tujuan khusus pada tromol. | Tidak boleh negatif. | Dana Tromol adalah channel/source; setelah identifikasi dapat direklasifikasi ke Fund tujuan dengan jejak audit. |
| Dana Sedekah | Dana sosial umum | Unrestricted kecuali ada pesan donor | Kemaslahatan sosial, ibadah, pendidikan, dan operasional yang disetujui. | Program sosial dan kebutuhan Masjid yang tidak dibatasi donor. | Penggunaan yang bertentangan dengan pesan donor atau ketentuan syariah. | Tidak boleh negatif. | Perlakuan mengikuti niat/pesan pemberi bila dinyatakan. |
| Dana Santunan Anak Yatim | Dana sosial khusus | Donor restricted/designated | Santunan, pendidikan, kesehatan, dan kebutuhan kesejahteraan anak yatim sesuai program sah. | Penyaluran langsung, pendidikan, kesehatan, kebutuhan dasar, serta biaya administrasi program yang diperbolehkan policy. | Operasional umum Masjid, pembangunan umum, atau penggunaan untuk penerima di luar tujuan tanpa dasar policy. | Tidak boleh negatif; reserve dapat ditetapkan berdasarkan komitmen bulanan. | Penerima, periode bantuan, dan bukti distribusi wajib dapat ditelusuri. |
| Dana Dhuafa | Dana sosial khusus | Donor restricted/designated | Bantuan kebutuhan dasar, kesehatan, pendidikan, ekonomi produktif, atau bantuan lain bagi dhuafa sesuai policy. | Penyaluran kepada penerima terverifikasi dan program yang sesuai. | Operasional umum, pembangunan yang tidak ditetapkan donor, atau penggunaan di luar tujuan. | Tidak boleh negatif. | Kriteria penerima dan evaluasi manfaat dicatat sesuai kebijakan privasi. |
| Dana Fidyah | Dana syariah | Restricted | Penyaluran Fidyah sesuai ketentuan syariah dan policy yang disahkan. | Penyaluran dalam bentuk/mekanisme kepada penerima yang sah menurut policy. | Listrik, honor umum, pembangunan, peminjaman antar Dana, atau penggunaan non-Fidyah. | Tidak boleh negatif. | Tidak boleh dicampur dengan Zakat atau Santunan umum hanya karena penerimanya serupa. |
| Dana Wakaf | Dana amanah wakaf | Permanently restricted atau restricted sesuai ikrar | Menjaga pokok harta wakaf dan menyalurkan manfaat sesuai ikrar/peruntukan. | Pengelolaan, pengembangan, pemeliharaan, dan pemanfaatan sesuai ikrar serta ketentuan Nazhir/policy. | Menggunakan pokok wakaf untuk operasional rutin; menjual, mengalihkan, atau mengubah peruntukan tanpa dasar yang sah. | Pokok wakaf minimal 100% dari pokok yang diamanahkan, kecuali perubahan yang sah dan terdokumentasi. | Pokok, hasil pengelolaan, cadangan, dan biaya yang sah harus dipisahkan. |
| Dana Qurban | Dana musiman | Restricted | Pengadaan, penyembelihan, pengolahan, distribusi, dan biaya langsung Qurban yang sah. | Belanja hewan, logistik langsung, distribusi, dokumentasi wajib, dan biaya yang diperbolehkan policy. | Operasional umum di luar kegiatan Qurban, peminjaman ke Dana lain, atau penggunaan setelah periode tanpa keputusan penutupan Fund. | Tidak boleh negatif. | Sisa dana mengikuti kebijakan donor dan penutupan program Qurban. |
| Dana Pembangunan | Dana proyek | Donor restricted/designated | Pembangunan, renovasi, pengadaan aset, atau proyek fisik yang ditetapkan. | Perencanaan, pengadaan, pembangunan, renovasi, pengawasan proyek, dan biaya langsung yang disetujui. | Listrik rutin, honor operasional umum, santunan sosial, atau belanja di luar proyek. | Tidak boleh negatif; commitment proyek wajib diperhitungkan. | Perubahan ruang lingkup proyek wajib didukung keputusan dan donor restriction review. |
| Dana Sosial & Kematian | Dana sosial khusus | Restricted/designated | Bantuan sosial, kedukaan, dan keadaan darurat sesuai policy. | Bantuan kedukaan, kebutuhan darurat, transport sosial, dan penyaluran yang didukung bukti. | Operasional umum dan pembiayaan non-sosial. | Tidak boleh negatif. | Kriteria dan batas bantuan ditetapkan dalam policy turunan. |
| Dana Sewa Aula | Dana pendapatan kegiatan | Unrestricted atau internally designated | Menampung hasil sewa fasilitas dan biaya terkait fasilitas. | Operasional fasilitas, pemeliharaan, pengembangan fasilitas, atau transfer sah ke Dana Operasional setelah ditetapkan policy. | Penggunaan untuk Fund restricted tanpa interfund transfer yang sah. | Tidak boleh negatif. | Deposit/refundable deposit tidak menjadi pendapatan; dicatat terpisah sebagai kewajiban. |
| Dana Titipan/Belum Teridentifikasi | Dana sementara | Custodial/suspense | Menampung dana yang belum dapat ditetapkan ownership atau Fund-nya. | Penyimpanan sementara sambil investigasi dan penyelesaian. | Pengeluaran, realisasi Program, atau penyajian sebagai pendapatan/operasional. | Tidak boleh negatif. | Wajib diselesaikan dalam batas waktu policy dan masuk exception report. |

## 3.3 Aturan khusus Dana Wakaf

1. Dana Wakaf dipisahkan sekurang-kurangnya antara pokok wakaf, hasil pengelolaan, cadangan, dan biaya pengelolaan yang diperbolehkan.
2. Pokok wakaf tidak digunakan untuk pengeluaran rutin hanya karena kas operasional kurang.
3. Penggunaan Dana Wakaf mengikuti ikrar/peruntukan dan dokumen yang berlaku.
4. Perubahan status atau peruntukan harta wakaf tidak diperlakukan sebagai koreksi biasa; ia mengikuti proses khusus yang berlaku dan wajib menyimpan dokumen pendukung.
5. Rekening tempat Dana Wakaf berada diberi status khusus dan direkonsiliasi dengan frekuensi yang ditetapkan pengurus.

## 3.4 Aturan khusus Dana Zakat dan Fidyah

1. Dana Zakat Maal, Zakat Fitrah, dan Fidyah adalah Fund terpisah.
2. Penggabungan laporan boleh dilakukan sebagai ringkasan, tetapi ledger dan saldo Fund tetap terpisah.
3. Penggunaan, pihak penerima, bentuk penyaluran, dan biaya yang mungkin diperbolehkan ditentukan oleh kebijakan syariah yang disahkan.
4. Jika kelayakan penerima belum tervalidasi, transaksi penyaluran tidak boleh posted sebagai distribusi final.
5. Dana Zakat dan Fidyah tidak dipakai untuk menutup defisit Dana Operasional.

---

# BAB 4 — Kebijakan Rekening

## 4.1 Prinsip umum

1. Rekening adalah lokasi aset; Fund adalah identitas amanah dana.
2. Setiap Rekening memiliki pemilik, jenis, akun kas/bank terkait, status aktif, dan jadwal rekonsiliasi.
3. Dana yang diizinkan berada pada Rekening ditetapkan dalam matriks Rekening–Fund dan ditinjau berkala.
4. Dana dapat dipindahkan antar Rekening tanpa kehilangan identitas Fund.
5. Rekening tidak boleh dipakai sebagai alasan untuk mengganti Fund pada transaksi.
6. Setiap Rekening bank dan kas fisik direkonsiliasi; Petty Cash juga wajib cash count.

## 4.2 Matriks kebijakan Rekening

| Rekening | Jenis | Fungsi utama | Dana yang boleh berada di Rekening | Dana yang tidak boleh berada di Rekening | Catatan governance |
|---|---|---|---|---|---|
| Kas Operasional | Kas tunai | Pembayaran/penerimaan tunai operasional yang sah. | Dana Operasional, Infaq Umum, Sedekah Umum, dan Fund kegiatan yang secara eksplisit diizinkan cash policy. | Dana Wakaf pokok, Zakat/Fidyah/Qurban bila kebijakan mewajibkan rekening khusus, Dana Titipan di luar masa transit. | Ada batas kas maksimum, cash count, dan setoran berkala ke bank. |
| Rekening BNI ZIS | Bank | Penampungan dan distribusi dana ZIS yang ditetapkan. | Zakat Maal, Zakat Fitrah, Fidyah, Infaq/Sedekah khusus, Santunan Yatim, Dhuafa, dan Dana Sosial yang diizinkan. | Dana Operasional umum dan Dana Sewa Aula, kecuali transfer sementara yang terdokumentasi atau policy memperbolehkan. | Fund harus tetap dipisah secara ledger walaupun berada pada rekening yang sama. |
| Rekening BSI | Bank | Rekening bank umum/syariah sesuai penetapan pengurus. | Dana Operasional, Infaq Umum, Sedekah Umum, Dana Pembangunan, dan Fund lain yang ditetapkan matriks. | Dana Wakaf pokok atau Dana khusus yang memiliki rekening wajib sendiri. | Jika digunakan untuk Fund restricted, matriks Fund–Rekening wajib diperbarui terlebih dahulu. |
| Rekening Mandiri | Bank | Rekening untuk kegiatan/kemitraan yang ditetapkan. | Dana Pembangunan, Dana Sewa Aula, Dana Operasional, atau Fund lain sesuai penetapan tertulis. | Zakat/Fidyah/Wakaf jika matriks tidak mengizinkan. | Fungsi rekening bukan dasar klasifikasi Fund penerimaan. |
| Kas Sosial & Kematian | Kas tunai | Penyaluran sosial/kedukaan skala tunai yang sah. | Dana Sosial & Kematian, Dana Dhuafa, Dana Santunan terkait yang diizinkan policy. | Dana Operasional, Dana Zakat/Fidyah/Wakaf/Qurban kecuali prosedur khusus mengizinkan bentuk penyaluran tunai. | Cash count dilakukan setiap kali tutup kegiatan dan periodik. |
| Rekening Sewa Aula | Bank | Menampung penerimaan sewa dan pembayaran terkait fasilitas. | Dana Sewa Aula, refundable deposit sebagai kewajiban, Dana Operasional setelah transfer sah. | Dana Zakat, Fidyah, Wakaf, Qurban, atau sosial restricted. | Deposit tidak boleh dicampur dengan pendapatan sewa. |
| Petty Cash | Kas tunai terbatas | Pengeluaran kecil yang sah, mendesak, dan sesuai limit imprest. | Dana Operasional dan Fund kegiatan yang disetujui petty cash policy. | Dana Wakaf pokok, Dana Zakat/Fidyah/Qurban, Dana Titipan, dan dana yang mensyaratkan pembayaran bank. | Refill adalah transfer Rekening; pengeluaran petty cash adalah transaksi beban/asset. |
| Rekening Wakaf Khusus | Bank khusus | Penjagaan dan pengelolaan Dana Wakaf apabila ditetapkan. | Dana Wakaf sesuai ikrar/policy, hasil pengelolaan, dan cadangan wakaf yang sah. | Semua Dana lain kecuali ketentuan khusus yang terdokumentasi. | Direkomendasikan rekening khusus untuk memperkuat pemisahan pokok wakaf dan auditability. |

## 4.3 Pembukaan, perubahan, dan penutupan Rekening

- Rekening baru harus memiliki tujuan, pemilik proses, jenis, bank/kas, account mapping, Fund yang diizinkan, dan jadwal rekonsiliasi sebelum digunakan.
- Perubahan daftar Fund yang boleh berada pada Rekening tidak mengubah histori ledger; berlaku efektif ke depan.
- Penutupan Rekening hanya boleh dilakukan setelah saldo nol atau seluruh saldo dipindahkan melalui transfer yang terdokumentasi per Fund.
- Rekening yang ditutup tetap tampil dalam histori dan laporan periode sebelumnya.
- Tidak ada Rekening tanpa account mapping dan tanpa jadwal rekonsiliasi.

---

# BAB 5 — Kebijakan Program

## 5.1 Kedudukan Program

Program adalah tempat penggunaan dana, pusat biaya, atau unit pengukuran kegiatan. Contoh: Ramadhan, Kajian, Santunan, Qurban, Pembangunan, Donor Darah, Operasional, Listrik, atau Air.

Program **tidak memiliki saldo**, **tidak memiliki Rekening**, dan **tidak memiliki Dana**.

## 5.2 Alasan kebijakan

1. Saldo adalah hak/kewajiban accounting yang melekat pada akun dan Fund, bukan kegiatan.
2. Satu Program dapat dibiayai beberapa Fund sehingga menjadikan Program sebagai Fund akan mencampur restriction.
3. Satu Fund dapat membiayai beberapa Program sehingga menjadikan Fund sebagai Program akan menghilangkan accountability sumber dana.
4. Program boleh ditutup ketika kegiatan selesai, sedangkan Fund masih dapat memiliki saldo atau kewajiban.
5. Program dapat memiliki budget dan actual tanpa memiliki rekening bank sendiri.
6. Laporan Program harus menunjukkan sumber Fund yang membiayai kegiatan, bukan menyajikan “saldo Program” yang menyesatkan.

## 5.3 Aturan Program

- Setiap pengeluaran yang berkaitan dengan kegiatan wajib membawa Program jika posting policy mensyaratkannya.
- Program hanya dapat menggunakan Fund yang diizinkan oleh decision matrix.
- Budget Program disusun per Fund, kategori/account, dan periode.
- Actual Program hanya berasal dari transaksi posted.
- Program yang berakhir tidak dapat menerima transaksi baru kecuali melalui adjustment atau keputusan perpanjangan yang terdokumentasi.
- Saldo Fund sisa setelah Program berakhir mengikuti restriction Fund, bukan otomatis menjadi Dana Operasional.

---

# BAB 6 — Kebijakan Penerimaan

## 6.1 Alur umum penerimaan

```text
Dana/barang diterima
        |
        v
Identifikasi sumber, Rekening, Fund, dan maksud pemberi
        |
        v
Validasi bukti, restriction, dan duplikasi
        |
        v
Split jika satu penerimaan membiayai beberapa Fund
        |
        v
Verifikasi dan persetujuan sesuai kebijakan
        |
        v
Posting jurnal, nomor bukti, kuitansi, dan audit trail
        |
        v
Ledger, laporan, dan dashboard diperbarui
```

## 6.2 Urutan identifikasi Fund

Fund penerimaan ditentukan dengan urutan berikut:

1. pernyataan tertulis donor/muzakki/wakif atau dokumen akad/ikrar;
2. label pembayaran, QRIS, kotak, tromol, campaign, atau rekening khusus yang jelas;
3. perjanjian program/kemitraan yang berlaku;
4. kategori penerimaan dan default mapping yang telah disahkan;
5. jika masih tidak jelas, status **Penerimaan Belum Teridentifikasi**.

Default mapping hanya boleh digunakan bila tidak ada maksud donor yang lebih spesifik. Penerimaan Belum Teridentifikasi tidak boleh dibelanjakan dan wajib diselesaikan dalam batas waktu yang ditetapkan policy owner.

## 6.3 Kebijakan per jenis penerimaan

| Jenis penerimaan | Fund awal | Bukti minimum | Kebijakan khusus |
|---|---|---|---|
| Donasi umum | Dana Operasional atau Dana Infaq/Sedekah Umum sesuai pesan donor. | Bukti transfer, tanda terima, atau catatan kas. | Bila donor menyebut tujuan, gunakan Fund restricted/designation tujuan tersebut. |
| Infaq | Dana Infaq Umum atau Fund khusus sesuai label. | Bukti penerimaan/channel collection. | Infaq bertujuan khusus tidak boleh dipindahkan ke Dana Operasional tanpa dasar donor. |
| Sedekah | Dana Sedekah atau Fund khusus sesuai pesan. | Bukti penerimaan/channel collection. | Perlakuan mengikuti maksud pemberi bila dinyatakan. |
| Zakat Maal | Dana Zakat Maal. | Data muzakki sesuai kebutuhan, detail jenis zakat, bukti penerimaan, dan kuitansi. | Tidak boleh tercampur dengan Infaq/Sedekah/Fidyah. Kepatuhan institusional dan penyaluran mengikuti kebijakan syariah yang disahkan. |
| Zakat Fitrah | Dana Zakat Fitrah. | Data penerimaan, jumlah jiwa/unit, bentuk uang/beras/barang, dan kuitansi. | Uang dan in-kind dicatat terpisah secara kuantitas/nilai; Fund tetap khusus. |
| Fidyah | Dana Fidyah. | Bukti penerimaan, jenis/bentuk, dan kuitansi. | Tidak boleh dipetakan menjadi Santunan Umum hanya karena penerima akhirnya sama. |
| Wakaf uang | Dana Wakaf sesuai ikrar. | Ikrar/pernyataan wakif, bukti penerimaan, dan dokumen yang disyaratkan policy/regulasi. | Pisahkan pokok, hasil pengelolaan, cadangan, dan biaya yang sah. |
| Wakaf melalui uang/aset | Dana Wakaf atau proyek wakaf sesuai ikrar. | Dokumen wakaf, bukti transaksi, dan dokumen aset yang relevan. | Pengadaan aset tidak menghapus kewajiban menjaga peruntukan wakaf. |
| Sewa Aula | Dana Sewa Aula untuk pendapatan; kewajiban deposit untuk uang jaminan. | Kontrak/sewa booking, invoice, bukti bayar, dan pembatalan/refund bila ada. | Deposit refundable tidak diakui sebagai pendapatan. |
| Penerimaan sosial/kedukaan | Dana Sosial & Kematian atau Fund tujuan. | Bukti transfer/tunai dan tujuan pengumpulan. | Donasi bertag “untuk keluarga/penerima tertentu” mendapat designation khusus. |
| Penerimaan operasional | Dana Operasional atau sumber pendapatan yang sah. | Bukti penerimaan dan reference. | Tidak boleh menggunakan label operasional untuk menampung Fund restricted. |
| Penerimaan Qurban | Dana Qurban. | Data peserta/donatur, paket/akad, bukti penerimaan. | Dipisah dari donasi umum dan dari Fund Zakat. |
| Pendapatan bank/biaya balik | Fund yang terkait Rekening atau Fund default sesuai policy. | Bank statement/reference. | Diklasifikasi setelah rekonsiliasi; bukan perubahan saldo manual. |

## 6.4 Penerimaan tunai dan non-tunai

- Penerimaan bank wajib memiliki reference bank atau bukti transfer bila tersedia.
- Penerimaan tunai wajib memiliki counter/collector record dan tanda terima sesuai jenis kegiatan.
- Hasil kotak/tromol wajib memiliki berita acara penghitungan atau bukti penghitungan yang disahkan policy.
- Penerimaan in-kind wajib mencatat kuantitas, satuan, kondisi, nilai pengakuan sesuai policy, Fund, dan lokasi penyimpanan.
- Penerimaan tanpa identitas donor tetap dapat dicatat, tetapi Fund dan channel-nya wajib jelas.

## 6.5 Larangan pada penerimaan

- Dilarang mengakui transfer internal sebagai penerimaan baru.
- Dilarang memecah satu penerimaan agar menghindari batas bukti atau review.
- Dilarang mengubah Fund penerimaan setelah posted tanpa reversal/reclassification yang sah.
- Dilarang menggunakan Dana Operasional sebagai default untuk dana yang belum jelas.
- Dilarang menerbitkan kuitansi ganda untuk satu source reference.

---

# BAB 7 — Kebijakan Pengeluaran

## 7.1 Persyaratan umum

Setiap pengeluaran wajib memiliki:

- tujuan bisnis dan kategori/account;
- Fund sumber;
- Program bila relevan;
- Rekening pembayaran;
- payee atau penerima manfaat;
- bukti yang memadai;
- validasi restriction Fund;
- pengecekan saldo Fund dan budget/commitment bila diterapkan;
- nomor bukti;
- posting jurnal yang seimbang.

## 7.2 Pengeluaran Dana Operasional

Dana Operasional dapat digunakan untuk:

- listrik, air, internet, dan utilitas;
- honorarium, gaji, dan jasa yang disahkan;
- ATK, kebersihan, keamanan, dan kebutuhan kantor;
- pemeliharaan rutin;
- biaya bank dan sistem pembayaran;
- logistik kegiatan operasional;
- pengeluaran sah lain yang tidak dibatasi Fund khusus.

Dana Operasional tidak boleh digunakan untuk menggantikan penggunaan dana restricted yang seharusnya memakai Fund asalnya jika tujuan transaksi masih terkait restriction tersebut.

## 7.3 Pengeluaran Dana Zakat

Dana Zakat hanya digunakan sesuai kebijakan Zakat yang disahkan, basis syariah, dan ketentuan yang berlaku. Kebijakan turunan wajib menentukan setidaknya:

- jenis Zakat;
- dasar kelayakan penerima;
- proses verifikasi penerima;
- bentuk penyaluran;
- batas dan dasar biaya amil bila ada;
- bukti distribusi;
- perlakuan sisa dan pembatalan.

Dana Zakat dilarang digunakan untuk listrik, air, internet, pembangunan umum, honor operasional umum, defisit kas, atau kegiatan yang tidak termasuk penggunaan yang disahkan.

## 7.4 Pengeluaran Dana Fidyah

Dana Fidyah hanya digunakan untuk bentuk penyaluran dan penerima yang ditentukan kebijakan syariah. Dana Fidyah dilarang dipakai sebagai pembiayaan umum, dana pembangunan, atau dana cadangan operasional.

## 7.5 Pengeluaran Dana Wakaf

Pengeluaran Dana Wakaf mengikuti ikrar/peruntukan dan policy wakaf. Pokok wakaf harus dipelihara. Penggunaan hasil pengelolaan, cadangan, dan biaya pengelolaan hanya boleh dalam batas yang disahkan. Pengeluaran yang mengubah status harta wakaf atau mengurangi pokok memerlukan jalur governance khusus dan dokumen pendukung yang sesuai.

## 7.6 Pengeluaran Dana Qurban

Dana Qurban hanya digunakan untuk biaya pengadaan, penyembelihan, pengolahan, distribusi, dokumentasi wajib, serta biaya langsung yang diperbolehkan policy. Dana ini tidak membiayai operasional umum di luar kegiatan Qurban.

## 7.7 Pengeluaran Dana Santunan Anak Yatim, Dhuafa, dan Sosial

- Pengeluaran harus mengarah pada penerima/Program yang sesuai dengan tujuan Dana.
- Verifikasi penerima, tujuan bantuan, nominal, dan bukti penyaluran wajib tersedia sesuai privacy policy.
- Biaya operasional distribusi hanya boleh bila restriction policy secara eksplisit mengizinkan.
- Pembayaran ke pihak ketiga untuk pendidikan/kesehatan dapat dilakukan bila manfaat untuk penerima terdokumentasi.

## 7.8 Pengeluaran Dana Pembangunan dan Sewa Aula

- Dana Pembangunan hanya digunakan untuk ruang lingkup proyek yang ditetapkan dan disertai budget/commitment.
- Dana Sewa Aula dapat digunakan untuk pengelolaan dan pemeliharaan fasilitas sesuai policy; hasil bersih dapat dialokasikan ke Dana Operasional hanya melalui kebijakan yang disahkan.
- Deposit penyewa dikembalikan atau direklasifikasi sesuai kontrak; tidak boleh digunakan sebagai pendapatan sebelum menjadi hak Masjid.

## 7.9 Petty Cash

- Petty Cash hanya untuk transaksi kecil, mendesak, dan berada di bawah batas imprest yang disahkan.
- Refill Petty Cash adalah transfer Rekening, bukan pengeluaran.
- Bukti asli atau bukti digital yang sah wajib diserahkan sebelum/bersamaan dengan pertanggungjawaban.
- Pengeluaran Petty Cash harus tetap memiliki Fund, kategori/account, Program bila relevan, dan nomor bukti.
- Petty Cash tidak digunakan sebagai jalur untuk menghindari kontrol pembayaran bank.

---

# BAB 8 — Kebijakan Transfer, Interfund Transfer, Budget Allocation, dan Realisasi

## 8.1 Empat proses yang wajib dibedakan

| Proses | Definisi | Mengubah Rekening | Mengubah Fund | Mengubah budget | Menghasilkan jurnal |
|---|---|---:|---:|---:|---:|
| **Transfer Rekening** | Memindahkan kas/bank dari Rekening sumber ke tujuan. | Ya | Tidak | Tidak | Ya |
| **Interfund Transfer** | Memindahkan ownership/restriction dari Fund sumber ke Fund tujuan. | Tidak harus | Ya | Dapat | Ya |
| **Budget Allocation** | Menetapkan rencana penggunaan Fund untuk Program/kategori/periode. | Tidak | Tidak | Ya | Tidak |
| **Realisasi** | Pengeluaran atau distribusi aktual untuk Program/Fund. | Umumnya ya | Mengurangi available Fund melalui aktivitas aktual | Mengurangi actual budget | Ya |

## 8.2 Transfer Rekening

### Definisi

Transfer Rekening adalah perpindahan lokasi uang. Contoh: Bank BSI ke Petty Cash, Kas Operasional ke Bank BNI, atau Bank BNI ke Kas Sosial.

### Aturan

- Fund pada sisi sumber dan tujuan harus sama untuk setiap bagian transfer.
- Satu transfer dapat memuat beberapa Fund; setiap Fund ditampilkan sebagai split terpisah.
- Total kas organisasi dan total tiap Fund tidak berubah.
- Biaya bank adalah transaksi terpisah.
- Bukti transfer keluar dan konfirmasi masuk disimpan bila tersedia.
- Transfer in-transit ditangani sebagai status/clearing sementara, bukan penerimaan baru.

### Flowchart

```text
Pilih Rekening sumber dan tujuan
        |
        v
Tentukan split Fund yang dipindah
        |
        v
Cek saldo tiap Fund pada Rekening sumber
        |
        v
Eksekusi transfer dan kumpulkan bukti
        |
        v
Posting debit Rekening tujuan / kredit Rekening sumber
        |
        v
Rekonsiliasi kedua Rekening
```

## 8.3 Interfund Transfer

### Definisi

Interfund Transfer adalah perubahan ownership dari Fund sumber ke Fund tujuan. Ia bukan transfer rekening dan bukan cara normal untuk menggunakan Fund restricted.

### Aturan

- Hanya Fund unrestricted atau internally designated yang dapat menjadi sumber interfund transfer rutin.
- Fund restricted hanya dapat direklasifikasi apabila restriction policy, dokumen donor, dan dasar governance mengizinkan.
- Dana Zakat, Fidyah, Wakaf, dan Qurban tidak menjadi sumber interfund transfer rutin ke Dana Operasional.
- Setiap interfund transfer menyimpan alasan, dasar policy, Fund sumber, Fund tujuan, nilai, approval, dan bukti.
- Tidak ada perubahan rekening kecuali transaksi tersebut juga disertai transfer rekening terpisah.
- Interfund transfer menghasilkan audit trail dan laporan khusus.

## 8.4 Budget Allocation

### Definisi

Budget Allocation adalah keputusan rencana: misalnya Dana Operasional membatasi anggaran listrik bulan tertentu atau Dana Santunan dialokasikan untuk Program Santunan.

### Aturan

- Tidak menghasilkan jurnal.
- Tidak mengurangi saldo Rekening atau saldo Fund.
- Dapat membentuk available budget dan commitment jika policy mengizinkan.
- Tidak boleh dipakai untuk menyembunyikan perubahan Fund.
- Revisi budget mencatat versi dan alasan; tidak menulis ulang budget historis.

## 8.5 Realisasi

### Definisi

Realisasi adalah pengeluaran, distribusi, atau penggunaan aktual yang telah terjadi dan didukung transaksi posted.

### Aturan

- Realisasi tidak dicatat dua kali sebagai “realisasi manual” dan pengeluaran terpisah.
- Actual budget diturunkan dari transaksi posted.
- Realisasi harus memakai Fund yang diizinkan, Program yang sesuai, dan bukti yang cukup.
- Bila aktivitas dibatalkan, lakukan reversal/refund sesuai transaksi sumber.
- Sisa budget bukan otomatis saldo Fund yang boleh dipindahkan; pengaturan mengikuti Fund policy.

---

# BAB 9 — Kebijakan Opening Balance

## 9.1 Tujuan

Opening Balance membentuk posisi awal yang dapat direkonsiliasi pada tanggal cutover atau awal tahun buku. Opening Balance bukan sarana koreksi rutin.

## 9.2 Kapan Opening Balance dibuat

- pada cutover dari sistem lama ke Financial Architecture V2;
- pada awal tahun buku bila kebijakan closing memerlukan opening entry;
- pada pembentukan unit accounting baru;
- pada kondisi luar biasa yang disahkan policy owner dan didukung rekonsiliasi lengkap.

## 9.3 Dasar penyusunan

Opening Balance wajib didasarkan pada:

- bank statement dan rekonsiliasi akhir;
- cash count kas dan petty cash;
- daftar aset/liabilitas yang relevan;
- perincian Rekening × Fund;
- daftar commitment/utang bila basis accounting mengharuskannya;
- daftar exception dan adjustment yang belum terselesaikan.

## 9.4 Siapa yang dapat membuat dan mengubah

| Aktivitas | Pihak yang mengajukan | Pihak yang memverifikasi | Pihak yang menetapkan |
|---|---|---|---|
| Menyusun draft opening | Petugas keuangan yang ditunjuk | Finance Controller/Reviewer independen | Otoritas keuangan yang ditetapkan pengurus |
| Mem-posting opening | Fungsi posting yang ditetapkan | Reviewer memeriksa tie-out | Otoritas keuangan menyetujui sebelum posted |
| Koreksi opening belum closed | Fungsi keuangan | Reviewer independen | Otoritas keuangan |
| Koreksi opening setelah posted/closed | Pengaju adjustment | Finance Controller dan reviewer yang relevan | Otoritas keuangan melalui adjustment/reversal |

## 9.5 Larangan

- Opening Balance tidak boleh diubah langsung setelah posted.
- Saldo awal tidak boleh dipakai untuk menghapus selisih yang belum diinvestigasi.
- Tidak boleh ada opening saldo total tanpa detail Rekening dan Fund.
- Tidak boleh membuat opening dari saldo layar aplikasi tanpa rekonsiliasi bukti eksternal.
- Selisih cutover wajib masuk adjustment transparan dengan alasan, bukan disembunyikan dalam Fund umum.

---

# BAB 10 — Kebijakan Closing Bulanan

## 10.1 Tujuan

Closing Bulanan memastikan laporan untuk satu periode stabil, lengkap, dan dapat dipercaya. Closing melindungi histori dari perubahan backdated yang tidak terkendali.

## 10.2 Status periode

| Status | Arti | Transaksi yang diizinkan |
|---|---|---|
| Open | Periode aktif untuk transaksi normal. | Draft, approval, posting normal. |
| Soft Closed | Periode dalam review akhir. | Hanya adjustment yang ditentukan dan terdokumentasi. |
| Hard Closed | Periode final. | Tidak ada posting/edit/delete langsung. |
| Reopened | Hard Closed dibuka sementara dengan scope khusus. | Hanya transaksi/adjustment yang disetujui. |

## 10.3 Checklist pre-close

1. Seluruh transaksi periode sudah posted, rejected, atau memiliki exception yang jelas.
2. Semua journal entry balanced.
3. Tidak ada Fund negatif tanpa exception yang disahkan.
4. Semua Rekening bank sudah direkonsiliasi atau memiliki daftar outstanding yang direview.
5. Kas dan Petty Cash sudah dihitung fisik sesuai jadwal.
6. Transfer antar Rekening sudah matched atau tercatat in-transit dengan bukti.
7. Penerimaan Belum Teridentifikasi dan suspense telah diselesaikan atau masuk exception report.
8. Bukti wajib transaksi material sudah lengkap.
9. Budget versus actual telah direview.
10. Interfund transfer telah direview dan sesuai policy.
11. Reversal/adjustment periode memiliki alasan dan referensi sumber.
12. Nomor bukti gap telah dijelaskan.
13. Trial balance, fund balance, dan saldo Rekening telah tie-out.
14. Laporan wajib periode telah disiapkan.
15. Audit exception period telah ditandatangani/diakui oleh pihak yang berwenang.

## 10.4 Proses closing

```text
Open Period
   -> kumpulkan dan post transaksi
   -> rekonsiliasi bank dan kas
   -> review exception dan tie-out
   -> Soft Close
   -> adjustment final yang sah
   -> closing entries/snapshot sesuai policy
   -> review laporan
   -> Hard Close
```

## 10.5 Reopen dan adjustment

- Reopen hanya dilakukan untuk kesalahan material, kebutuhan audit, kewajiban hukum, atau alasan yang disahkan.
- Permohonan reopen wajib menyebut periode, transaksi terdampak, alasan, dampak laporan, dan rencana koreksi.
- Reopen tidak membolehkan perubahan bebas seluruh periode.
- Koreksi dilakukan dengan adjustment/reversal yang dapat ditelusuri.
- Setelah koreksi, rekonsiliasi dan laporan terdampak ditinjau ulang sebelum hard close kembali.
- Semua reopen dan adjustment masuk laporan audit periodik.

---

# BAB 11 — Kebijakan Rekonsiliasi

## 11.1 Rekonsiliasi bank

Setiap Rekening bank direkonsiliasi terhadap bank statement pada setiap periode statement atau frekuensi yang ditetapkan oleh pengurus. Rekonsiliasi membandingkan saldo buku ledger dengan saldo eksternal setelah memperhitungkan outstanding item yang sah.

### Elemen wajib

- Rekening dan periode statement;
- opening statement balance;
- closing statement balance;
- book balance;
- statement lines;
- journal lines yang dicocokkan;
- deposit in transit;
- outstanding payment;
- bank fee, interest, atau transaksi bank yang belum dicatat;
- selisih akhir;
- bukti statement dan review.

## 11.2 Rekonsiliasi kas

Kas Operasional dan Kas Sosial & Kematian direkonsiliasi melalui cash count. Cash count dilakukan oleh petugas yang ditetapkan, dengan berita acara, denominasi, kas fisik, saldo buku, selisih, alasan, dan tindak lanjut.

## 11.3 Rekonsiliasi Petty Cash

Petty Cash direkonsiliasi sebagai:

```text
Kas fisik
+ bukti pengeluaran yang belum direfill/di-posting sesuai policy
= nilai yang harus sama dengan imprest/saldo buku yang berlaku
```

Selisih Petty Cash tidak boleh ditutup dengan refill baru. Selisih diinvestigasi dan, bila perlu, dicatat sebagai adjustment terpisah.

## 11.4 Penanganan selisih

| Kondisi | Tindakan |
|---|---|
| Bank fee/interest belum tercatat | Buat transaksi adjustment terpisah berdasarkan statement. |
| Transfer masih in-transit | Catat/match sebagai outstanding sampai kedua sisi terlihat. |
| Salah input amount/Fund | Reversal atau adjustment yang terhubung ke transaksi asal. |
| Kas fisik kurang/lebih | Investigasi, berita acara, dan adjustment hanya setelah keputusan yang sah. |
| Statement line tidak dikenal | Jangan otomatis posting; investigasi dengan bank/pihak terkait. |
| Selisih belum terselesaikan | Reconciliation tetap open dan masuk exception report. |

## 11.5 Aturan finalisasi

- Satu statement line tidak boleh direkonsiliasi dua kali pada sesi final.
- Satu journal line yang sudah final reconciled tidak boleh diedit.
- Rekonsiliasi final mensyaratkan selisih nol atau exception tertulis sesuai materiality policy.
- Rekonsiliasi tidak mengubah saldo secara langsung; setiap penyesuaian adalah transaksi baru.
- Laporan closing harus menampilkan Rekening yang belum direkonsiliasi.

---

# BAB 12 — Kebijakan Nomor Bukti

## 12.1 Prinsip

Nomor bukti adalah identitas unik, immutable, dan dapat ditelusuri. Nomor tidak dibuat dari jumlah row transaksi. Nomor yang batal tidak digunakan kembali; status batal dijelaskan dalam audit trail.

## 12.2 Format yang disarankan

Format umum:

```text
<TIPE>-<UNIT>-<YYYYMM>-<SEQUENCE>
```

Contoh konseptual:

| Kode | Nama dokumen | Format contoh | Kapan diterbitkan |
|---|---|---|---|
| RCV | Receipt Voucher | `RCV-MASJID-202608-00001` | Penerimaan posted. |
| PAY | Payment Voucher | `PAY-MASJID-202608-00001` | Pengeluaran posted. |
| TRF | Treasury Transfer Voucher | `TRF-MASJID-202608-00001` | Transfer antar Rekening posted. |
| IFT | Interfund Transfer Voucher | `IFT-MASJID-202608-00001` | Interfund transfer posted. |
| JV | Journal Voucher | `JV-MASJID-202608-00001` | Jurnal khusus/manual yang sah. |
| ADJ | Adjustment Voucher | `ADJ-MASJID-202608-00001` | Adjustment posted. |
| REV | Reversal Voucher | `REV-MASJID-202608-00001` | Reversal posted. |
| OPB | Opening Balance Voucher | `OPB-MASJID-202601-00001` | Opening balance posted. |
| CLS | Closing Voucher | `CLS-MASJID-202608-00001` | Closing entry/snapshot final. |
| REC | Reconciliation Reference | `REC-MASJID-202608-00001` | Sesi rekonsiliasi dibuat. |

## 12.3 Aturan nomor

- Sequence unik paling sedikit dalam ruang lingkup unit accounting, tipe, dan periode yang ditetapkan.
- Nomor diterbitkan sekali pada tahap yang ditentukan policy dan tidak berubah setelah itu.
- Nomor external, misalnya reference bank atau nomor invoice vendor, disimpan terpisah dari nomor bukti internal.
- Dokumen draft dapat memiliki reference sementara; nomor resmi diberikan pada posting atau approval sesuai policy.
- Void/cancelled number tetap tercatat bersama alasan.
- Nomor jurnal dapat sama dengan voucher atau memiliki nomor jurnal terpisah; kebijakan organisasi harus memilih satu pendekatan dan menerapkannya konsisten.

---

# BAB 13 — Kebijakan Lampiran dan Bukti

## 13.1 Jenis file yang diterima

- PDF untuk invoice, statement, kontrak, berita acara, dan dokumen legal.
- JPG, JPEG, PNG, WebP, atau HEIC untuk foto kuitansi/bukti visual sesuai kebijakan media.
- Berkas lain hanya jika ditetapkan policy owner dan dapat diakses dalam jangka panjang.

Ukuran, resolusi, retensi, penamaan, serta keamanan file ditetapkan pada lampiran teknis kebijakan. Sistem harus menjaga checksum, versi, dan hubungan attachment dengan transaksi.

## 13.2 Bukti minimum menurut proses

| Proses | Bukti minimal wajib | Bukti tambahan bila relevan |
|---|---|---|
| Penerimaan bank | Reference bank/bukti transfer atau statement line; kuitansi internal. | Surat/ pesan donor, campaign reference. |
| Penerimaan tunai | Tanda terima atau berita acara penghitungan. | Foto penghitungan, daftar collector. |
| Zakat/Fidyah | Kuitansi, detail penerimaan, Fund, dan data yang diperlukan policy. | Bukti penyaluran, verifikasi penerima, dokumen syariah terkait. |
| Wakaf | Bukti penerimaan dan dokumen ikrar/akad sesuai jenis wakaf. | Dokumen aset, sertifikat, dokumen Nazhir, dan perizinan yang relevan. |
| Pengeluaran bank | Invoice/kuitansi, bukti pembayaran, tujuan/Fund/Program. | Kontrak, purchase order, berita acara penerimaan. |
| Pengeluaran tunai/Petty Cash | Kuitansi/nota asli atau bukti digital yang sah. | Bukti penerimaan barang/jasa. |
| Transfer Rekening | Bukti transfer keluar dan/atau bukti penerimaan masuk. | Reference bank dan rekonsiliasi kedua sisi. |
| Interfund transfer | Dasar policy, alasan, dan approval. | Dokumen donor/keputusan yang mendukung. |
| Opening balance | Worksheet rekonsiliasi dan bukti saldo eksternal. | Daftar aset/liabilitas/exception. |
| Closing | Checklist closing dan laporan review. | Sign-off serta daftar exception. |
| Rekonsiliasi | Bank statement atau berita acara cash count. | Working paper matching dan adjustment reference. |

## 13.3 Wajib, opsional, dan exception

- Bukti wajib untuk semua transaksi material dan seluruh proses yang ditandai wajib pada tabel di atas.
- Bukti opsional hanya untuk transaksi yang policy secara eksplisit mengizinkan, misalnya donasi anonim bernilai kecil melalui channel yang sudah tercatat.
- Jika bukti wajib belum tersedia, transaksi tidak boleh posted kecuali exception terdokumentasi dan disahkan oleh otoritas yang ditetapkan.
- Attachment posted tidak boleh diganti diam-diam. Tambahan/perbaikan bukti dibuat sebagai versi baru dengan alasan.
- Dokumen yang memuat data sensitif penerima manfaat harus dibatasi aksesnya dan dipakai hanya untuk tujuan governance/audit yang sah.

---

# BAB 14 — Kebijakan Audit Trail

## 14.1 Prinsip audit

Audit trail harus memungkinkan pemeriksa memahami:

- siapa melakukan tindakan;
- kapan tindakan dilakukan;
- dokumen/transaksi apa yang terdampak;
- nilai sebelum dan sesudah pada perubahan draft;
- mengapa tindakan dilakukan;
- approval atau dasar policy yang digunakan;
- jurnal dan dampak saldo yang dihasilkan;
- hubungan dengan reversal, adjustment, closing, dan rekonsiliasi.

## 14.2 Kejadian yang wajib dicatat

- pembuatan dan perubahan transaksi draft;
- perubahan Rekening, Fund, Program, kategori, amount, tanggal, dan split;
- validation, rejection, approval, dan posting;
- penambahan/pergantian versi attachment;
- penerbitan/void nomor bukti;
- transfer Rekening dan interfund transfer;
- reversal dan adjustment;
- perubahan Fund restriction policy atau matriks keputusan;
- opening balance;
- soft close, hard close, dan reopen;
- pembuatan/finalisasi/pembatalan rekonsiliasi;
- perubahan master data yang sudah digunakan;
- exception saldo negatif, over-budget, atau bukti belum lengkap.

## 14.3 Koreksi transaksi

| Kondisi | Tindakan yang benar |
|---|---|
| Kesalahan pada draft | Perbarui draft dengan audit event before/after. |
| Kesalahan setelah posted tetapi belum dibayar/ditindaklanjuti | Reversal dan transaksi pengganti sesuai policy. |
| Kesalahan amount/Fund/account pada transaksi posted | Reversal penuh/sebagian atau adjustment terhubung. |
| Kesalahan periode closed | Ajukan reopen atau prior-period adjustment sesuai policy. |
| Bukti kurang setelah posted | Tambah attachment versi baru dan catat alasan; jangan mengubah jurnal bila amount tidak berubah. |

## 14.4 Retensi

Ledger, nomor bukti, attachment material, closing evidence, dan audit trail disimpan sekurang-kurangnya selama masa retensi yang diwajibkan peraturan dan kebijakan Masjid. Penghapusan tidak boleh dilakukan ketika ada audit, sengketa, investigasi, atau kewajiban retensi yang aktif.

---

# BAB 15 — Business Rules

Bagian ini memuat **122 aturan bisnis minimum**. Aturan dapat diperketat melalui policy turunan, tetapi tidak boleh dilonggarkan tanpa perubahan manual yang disahkan.

## 15.1 Master Data — BR-001 s.d. BR-012

1. **BR-001:** Setiap unit accounting wajib memiliki identitas yang membatasi Rekening, Fund, periode, voucher sequence, dan ledger-nya.
2. **BR-002:** Rekening, Fund, Program, Category, dan GL Account wajib memiliki master data yang berbeda.
3. **BR-003:** Master data yang telah dipakai dalam posted ledger tidak boleh dihapus.
4. **BR-004:** Master data yang tidak lagi digunakan hanya boleh dinonaktifkan dengan effective date dan alasan.
5. **BR-005:** Setiap Rekening aktif wajib dipetakan ke satu leaf cash/bank GL Account yang postingable.
6. **BR-006:** Header GL Account tidak boleh menerima posting transaksi.
7. **BR-007:** Setiap Fund wajib memiliki status, restriction policy, effective date, dan aturan saldo negatif.
8. **BR-008:** Setiap Program wajib memiliki status aktif/nonaktif dan periode efektif.
9. **BR-009:** Category wajib memiliki posting profile atau aturan mapping yang disahkan sebelum dipakai transaksi posted.
10. **BR-010:** Perubahan mapping master berlaku prospektif dan tidak mengubah journal historis.
11. **BR-011:** Dana Belum Teridentifikasi wajib memiliki owner tindak lanjut dan batas waktu penyelesaian.
12. **BR-012:** Data master keuangan wajib direview berkala dan setiap perubahan masuk audit trail.

## 15.2 Fund — BR-013 s.d. BR-030

13. **BR-013:** Setiap penerimaan wajib ditetapkan ke satu atau lebih Fund sebelum posted.
14. **BR-014:** Jumlah split Fund wajib sama dengan total penerimaan atau pengeluaran.
15. **BR-015:** Maksud tertulis donor/muzakki/wakif mengalahkan default mapping Fund.
16. **BR-016:** Nama Rekening tidak boleh digunakan sebagai satu-satunya dasar menentukan Fund.
17. **BR-017:** Dana Zakat Maal, Zakat Fitrah, Fidyah, Wakaf, Qurban, dan Titipan wajib dipisahkan dari Dana Operasional.
18. **BR-018:** Dana Zakat tidak boleh digunakan untuk beban operasional umum kecuali komponen yang secara eksplisit disahkan kebijakan Zakat.
19. **BR-019:** Dana Fidyah tidak boleh digunakan untuk dana santunan umum, pembangunan, atau operasional.
20. **BR-020:** Pokok Dana Wakaf tidak boleh digunakan untuk pengeluaran rutin atau menutup defisit kas.
21. **BR-021:** Dana Qurban tidak boleh digunakan untuk aktivitas non-Qurban.
22. **BR-022:** Dana Pembangunan hanya digunakan untuk ruang lingkup proyek yang ditetapkan.
23. **BR-023:** Dana Santunan Anak Yatim, Dhuafa, dan Sosial hanya digunakan untuk tujuan dan penerima yang sesuai policy.
24. **BR-024:** Fund restricted tidak boleh dipinjamkan ke Fund lain sebagai solusi kas sementara.
25. **BR-025:** Saldo Fund tidak boleh negatif kecuali exception policy yang eksplisit dan terdokumentasi.
26. **BR-026:** Saldo suatu Fund pada Rekening sumber tidak boleh negatif akibat transfer atau pembayaran.
27. **BR-027:** Interfund transfer wajib menyebut Fund sumber, Fund tujuan, alasan, dasar policy, dan nomor bukti.
28. **BR-028:** Sisa Fund ketika Program selesai mengikuti restriction Fund, bukan keputusan otomatis sistem.
29. **BR-029:** Fund uncertainty diselesaikan melalui investigasi; tidak boleh direklasifikasi ke Dana Operasional tanpa dasar.
30. **BR-030:** Restriction policy yang berubah tidak berlaku surut kecuali dokumen sumber secara sah mengubah peruntukan.

## 15.3 Rekening — BR-031 s.d. BR-040

31. **BR-031:** Setiap Rekening wajib memiliki jenis, fungsi, daftar Fund yang diizinkan, dan frekuensi rekonsiliasi.
32. **BR-032:** Rekening bank yang aktif wajib direkonsiliasi dengan statement periodik.
33. **BR-033:** Kas fisik dan Petty Cash wajib menjalani cash count sesuai jadwal policy.
34. **BR-034:** Penutupan Rekening hanya dapat dilakukan setelah seluruh saldo dipindahkan per Fund atau direkonsiliasi nol.
35. **BR-035:** Transfer antar Rekening tidak boleh mengubah Fund identity.
36. **BR-036:** Biaya bank atas transfer wajib dicatat terpisah dari nilai transfer pokok.
37. **BR-037:** Rekening Sewa Aula tidak boleh memperlakukan refundable deposit sebagai pendapatan.
38. **BR-038:** Petty Cash tidak boleh dipakai untuk menghindari batas atau proses pembayaran bank.
39. **BR-039:** Dana yang tidak diizinkan oleh matriks Rekening–Fund tidak boleh ditempatkan pada Rekening tersebut tanpa exception terdokumentasi.
40. **BR-040:** Perubahan daftar Fund yang diizinkan pada Rekening tidak boleh mengubah saldo historis atau jurnal historis.

## 15.4 Program dan Budget — BR-041 s.d. BR-050

41. **BR-041:** Program adalah cost center dan tidak memiliki saldo kas/fund balance.
42. **BR-042:** Program tidak dapat dibuat sebagai pengganti Fund atau Rekening.
43. **BR-043:** Program hanya dapat menggunakan Fund yang diizinkan decision matrix.
44. **BR-044:** Budget wajib didefinisikan per periode, Fund, Program, dan kategori/account bila budget control diterapkan.
45. **BR-045:** Budget allocation tidak menghasilkan jurnal.
46. **BR-046:** Actual budget hanya berasal dari transaksi yang telah posted.
47. **BR-047:** Commitment tidak boleh dihitung sebagai actual pengeluaran.
48. **BR-048:** Actual dan commitment tidak boleh dihitung ganda dalam available budget.
49. **BR-049:** Program tidak aktif tidak boleh menerima transaksi normal baru.
50. **BR-050:** Revisi budget wajib menyimpan versi, alasan, tanggal efektif, dan nilai sebelum/sesudah.

## 15.5 Transaksi — BR-051 s.d. BR-065

51. **BR-051:** Setiap transaksi finansial wajib mempunyai jenis transaksi, tanggal dokumen, tanggal accounting, amount, Fund, dan sumber bukti yang sesuai.
52. **BR-052:** Setiap transaksi wajib memiliki idempotency/source reference untuk mencegah duplicate posting.
53. **BR-053:** Satu business event tidak boleh menghasilkan lebih dari satu posted transaction aktif.
54. **BR-054:** Double-click, refresh, retry, atau duplicate request tidak boleh menggandakan transaksi posted.
55. **BR-055:** Transaction splits wajib seimbang dengan total header transaksi.
56. **BR-056:** Nilai transaksi normal harus positif; koreksi negatif ditangani melalui reversal atau adjustment, bukan amount negatif tanpa jenis yang jelas.
57. **BR-057:** Transaksi posted tidak boleh diedit atau dihapus.
58. **BR-058:** Draft yang dibatalkan tetap menyimpan reason dan audit event.
59. **BR-059:** Transfer internal tidak boleh dibuat melalui jenis transaksi penerimaan atau pengeluaran biasa.
60. **BR-060:** Penerimaan tanpa Fund jelas wajib masuk status belum teridentifikasi.
61. **BR-061:** Pengeluaran wajib menyebut payee/beneficiary kecuali policy mengizinkan pengecualian yang terdokumentasi.
62. **BR-062:** Pengeluaran wajib memakai Fund dan Rekening yang valid sebelum pembayaran.
63. **BR-063:** Transaksi in-kind wajib mencatat kuantitas, satuan, Fund, dan basis nilai sesuai policy.
64. **BR-064:** Refund wajib dihubungkan ke transaksi sumber atau alasan exception yang sah.
65. **BR-065:** Transaksi material tanpa bukti wajib tidak dapat posted kecuali melalui exception yang disahkan.

## 15.6 Posting dan Ledger — BR-066 s.d. BR-080

66. **BR-066:** Semua transaksi yang memengaruhi posisi finansial wajib menghasilkan jurnal melalui Posting Engine.
67. **BR-067:** Total debit seluruh journal entry wajib sama dengan total kredit.
68. **BR-068:** Satu journal line hanya boleh memiliki debit atau kredit, tidak keduanya.
69. **BR-069:** Journal line posted wajib immutable.
70. **BR-070:** Setiap journal line yang memengaruhi sumber daya wajib membawa Fund yang benar.
71. **BR-071:** Journal line kas/bank wajib membawa Rekening yang cocok dengan GL Account-nya.
72. **BR-072:** Journal line pengeluaran Program wajib membawa Program jika posting policy mensyaratkannya.
73. **BR-073:** Transfer Rekening wajib debit Rekening tujuan dan kredit Rekening sumber pada Fund yang sama.
74. **BR-074:** Interfund transfer wajib menghasilkan dampak transfer-out pada Fund sumber dan transfer-in pada Fund tujuan yang dapat ditelusuri.
75. **BR-075:** Budget allocation dan rekonsiliasi matching tidak boleh menghasilkan jurnal secara otomatis.
76. **BR-076:** Adjustment wajib memiliki reason code, reference, dan bukti pendukung.
77. **BR-077:** Reversal wajib menunjuk journal/transaksi asal dan tidak boleh menghapus histori asal.
78. **BR-078:** Ledger adalah satu-satunya sumber saldo resmi untuk laporan dan dashboard.
79. **BR-079:** Projection/snapshot saldo hanya boleh menjadi cache yang dapat dibangun ulang dari ledger.
80. **BR-080:** Tidak ada proses yang boleh menulis atau mengubah saldo secara langsung di luar posted journal.

## 15.7 Closing — BR-081 s.d. BR-090

81. **BR-081:** Setiap transaksi posted wajib masuk ke satu Accounting Period.
82. **BR-082:** Transaksi normal hanya dapat diposting pada periode Open.
83. **BR-083:** Periode Soft Closed hanya menerima jenis adjustment yang ditentukan policy.
84. **BR-084:** Periode Hard Closed tidak menerima create, update, delete, atau backdate secara langsung.
85. **BR-085:** Hard close mensyaratkan checklist pre-close yang lengkap atau exception yang disahkan.
86. **BR-086:** Rekonsiliasi bank/kas yang belum selesai harus tampil sebagai exception sebelum closing.
87. **BR-087:** Fund negatif atau suspense aging harus direview sebelum hard close.
88. **BR-088:** Reopen wajib menyebut periode, alasan, transaksi terdampak, dan dampak laporan.
89. **BR-089:** Koreksi periode closed dilakukan melalui reopen terkendali atau prior-period adjustment sesuai policy.
90. **BR-090:** Closing, reopen, dan closing ulang wajib menghasilkan audit event dan bukti review.

## 15.8 Rekonsiliasi — BR-091 s.d. BR-099

91. **BR-091:** Setiap Rekening bank aktif wajib memiliki reconciliation session sesuai frekuensi statement.
92. **BR-092:** Kas dan Petty Cash wajib direkonsiliasi terhadap cash count.
93. **BR-093:** Satu statement line tidak boleh dipakai dalam lebih dari satu final reconciliation.
94. **BR-094:** Satu journal line yang sudah final reconciled tidak boleh diubah.
95. **BR-095:** Rekonsiliasi dapat menggunakan one-to-one, one-to-many, atau many-to-many matching bila didukung bukti.
96. **BR-096:** Bank fee, interest, dan transaksi bank belum tercatat diposting sebagai adjustment terpisah.
97. **BR-097:** Selisih rekonsiliasi tidak boleh ditutup dengan mengubah saldo ledger secara langsung.
98. **BR-098:** Reconciliation final mensyaratkan selisih nol atau exception resmi sesuai materiality policy.
99. **BR-099:** Outstanding item wajib memiliki umur, reason, owner, dan tindak lanjut.

## 15.9 Nomor Bukti — BR-100 s.d. BR-104

100. **BR-100:** Setiap voucher resmi wajib memiliki nomor bukti unik dan immutable.
101. **BR-101:** Sequence nomor wajib diperoleh secara atomik; tidak boleh menggunakan hitungan jumlah transaksi sebagai nomor berikutnya.
102. **BR-102:** Nomor void/cancelled tidak boleh digunakan kembali.
103. **BR-103:** Nomor internal dan reference eksternal wajib dibedakan.
104. **BR-104:** Gap nomor wajib muncul dalam audit report beserta status dan alasan.

## 15.10 Audit dan Lampiran — BR-105 s.d. BR-112

105. **BR-105:** Create, update draft, validate, approve, post, reverse, adjust, close, reopen, dan reconcile wajib tercatat dalam audit trail.
106. **BR-106:** Audit trail wajib menyimpan actor, waktu, objek, nilai before/after bila relevan, reason, dan correlation reference.
107. **BR-107:** Attachment posted tidak boleh diganti tanpa version history dan audit event.
108. **BR-108:** Dokumen wajib disimpan sesuai retensi yang berlaku dan legal hold bila ada.
109. **BR-109:** Koreksi tidak boleh menghapus jurnal atau attachment historis.
110. **BR-110:** Akses dokumen sensitif penerima manfaat dibatasi sesuai kebutuhan tugas dan privacy policy.
111. **BR-111:** Exception bukti wajib memiliki alasan, pemilik tindak lanjut, dan batas penyelesaian.
112. **BR-112:** Audit report wajib dapat menelusuri dari laporan ke journal line, transaksi, dan lampiran.

## 15.11 Dashboard — BR-113 s.d. BR-116

113. **BR-113:** Dashboard hanya membaca ledger atau projection yang dibangun dari ledger.
114. **BR-114:** Setiap nilai dashboard wajib memiliki as-of timestamp dan drill-down ke sumber.
115. **BR-115:** Cash flow dashboard harus mengecualikan transfer internal dari penerimaan/pengeluaran eksternal.
116. **BR-116:** Dashboard wajib menampilkan exception minimal: Fund negatif, Rekening belum rekonsiliasi, bukti belum lengkap, suspense aging, dan periode belum closing.

## 15.12 Laporan — BR-117 s.d. BR-122

117. **BR-117:** Semua laporan finansial resmi wajib diturunkan dari posted ledger atau projection yang tie-out ke ledger.
118. **BR-118:** Laporan Fund wajib menampilkan opening, receipt, expense, transfer-in, transfer-out, adjustment, dan ending balance.
119. **BR-119:** Laporan Rekening wajib dapat menjelaskan komposisi Fund dalam saldo Rekening.
120. **BR-120:** Trial balance wajib tetap balanced pada setiap periode laporan.
121. **BR-121:** Laporan Program wajib membedakan budget, commitment, actual, dan Fund source.
122. **BR-122:** Laporan Jumat wajib memisahkan penerimaan/pengeluaran eksternal dari mutasi transfer internal.

---

# BAB 16 — Decision Matrix Penggunaan Dana

## 16.1 Legenda

| Simbol | Arti |
|---|---|
| ✅ | Boleh sebagai penggunaan normal, tetap membutuhkan bukti dan proses transaksi yang sah. |
| ⚠️ | Bersyarat: hanya bila restriction policy, dokumen sumber, dan approval yang ditentukan terpenuhi. |
| ❌ | Tidak boleh. |

Decision matrix tidak menggantikan policy syariah/donor. Jika terdapat pembatasan donor yang lebih ketat, pembatasan donor berlaku.

## 16.2 Matrix Dana restricted dan berisiko tinggi

| Dana | Listrik/air/internet umum | Honor operasional umum | Santunan penerima sah | Kebutuhan pangan/paket sesuai policy | Pengadaan/pembangunan aset | Biaya distribusi langsung | Investasi/pengelolaan yang sah | Transfer ke Dana Operasional |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Dana Zakat Maal | ❌ | ❌ | ⚠️ | ⚠️ | ❌ | ⚠️ | ⚠️ | ❌ |
| Dana Zakat Fitrah | ❌ | ❌ | ⚠️ | ⚠️ | ❌ | ⚠️ | ❌ | ❌ |
| Dana Fidyah | ❌ | ❌ | ⚠️ | ⚠️ | ❌ | ⚠️ | ❌ | ❌ |
| Dana Wakaf — pokok | ❌ | ❌ | ❌ | ❌ | ⚠️ | ❌ | ⚠️ | ❌ |
| Dana Wakaf — hasil | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ❌ |
| Dana Qurban | ❌ | ❌ | ❌ | ⚠️ | ❌ | ✅ | ❌ | ❌ |
| Dana Santunan Anak Yatim | ❌ | ❌ | ✅ | ✅ | ⚠️ | ⚠️ | ❌ | ❌ |
| Dana Dhuafa | ❌ | ❌ | ✅ | ✅ | ⚠️ | ⚠️ | ⚠️ | ❌ |
| Dana Pembangunan | ❌ | ❌ | ❌ | ❌ | ✅ | ⚠️ | ❌ | ❌ |
| Dana Sosial & Kematian | ❌ | ❌ | ✅ | ✅ | ❌ | ⚠️ | ❌ | ❌ |

### Catatan matrix restricted

- **Dana Zakat:** tanda ⚠️ berarti hanya melalui kebijakan Zakat yang disahkan, kelayakan penerima, dan bukti penyaluran yang sesuai. Manual ini tidak mengotomasi atau menetapkan sendiri rincian asnaf/amil.
- **Dana Fidyah:** tanda ⚠️ berarti hanya untuk bentuk dan penerima yang ditetapkan policy syariah; bukan untuk santunan umum tanpa klasifikasi.
- **Dana Wakaf:** pokok dan hasil wajib dibedakan. Penggunaan hasil pun mengikuti ikrar dan policy wakaf, termasuk otorisasi yang diperlukan.
- **Dana Qurban:** biaya distribusi langsung dapat digunakan hanya jika termasuk kebijakan Qurban yang disahkan dan dapat dibuktikan sebagai biaya langsung.
- **Dana Santunan/Dhuafa/Sosial:** biaya distribusi harus proporsional, diizinkan restriction policy, dan tidak menjadi saluran pembebanan operasional umum.

## 16.3 Matrix Dana umum, designated, dan kegiatan

| Dana | Listrik/air/internet | Honor/ATK/kebersihan | Program dakwah/pendidikan | Santunan sosial | Pemeliharaan rutin | Pembangunan/proyek | Biaya fasilitas sewa | Transfer ke Dana lain |
|---|---:|---:|---:|---:|---:|---:|---:|---:|
| Dana Operasional | ✅ | ✅ | ✅ | ⚠️ | ✅ | ⚠️ | ⚠️ | ⚠️ |
| Dana Infaq Umum | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ | ⚠️ | ⚠️ |
| Dana Tromol tanpa label khusus | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ | ⚠️ | ⚠️ |
| Dana Sedekah tanpa restriction | ✅ | ✅ | ✅ | ✅ | ✅ | ⚠️ | ⚠️ | ⚠️ |
| Dana Sewa Aula | ⚠️ | ⚠️ | ❌ | ❌ | ✅ | ⚠️ | ✅ | ⚠️ |
| Dana Penerimaan Kegiatan Khusus | ⚠️ | ⚠️ | ✅ bila sesuai kegiatan | ⚠️ | ⚠️ | ⚠️ | ⚠️ | ⚠️ |
| Dana Titipan/Belum Teridentifikasi | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |

### Catatan matrix umum

- Tanda ⚠️ pada transfer berarti hanya melalui interfund transfer yang sah; bukan transfer Rekening biasa.
- Dana Operasional dan Infaq/Sedekah umum tetap tidak boleh dipakai untuk tujuan yang bertentangan dengan donor message, legal restriction, atau policy.
- Dana Sewa Aula hanya boleh membiayai fasilitas/peruntukan yang disahkan; transfer hasil bersih ke Dana Operasional harus transparan dan mengikuti policy.
- Dana Tromol berlabel khusus otomatis mengikuti Fund label tersebut, bukan matrix “tanpa label khusus”.

---

# BAB 17 — Future Scalability dan Multi-Entity Governance

## 17.1 Skalabilitas jumlah Rekening, Dana, Program, dan transaksi

Kebijakan ini tetap berlaku untuk 100 Rekening, 100 Dana, 100 Program, dan histori 10 tahun karena pemisahan konsepnya bersifat dimensional:

- Rekening menunjukkan lokasi aset.
- Fund menunjukkan restriction/ownership.
- Program menunjukkan penggunaan.
- GL Account menunjukkan klasifikasi accounting.
- Ledger menyatukan dimensi tersebut tanpa membuat daftar hardcoded per fitur.

Penambahan Rekening, Fund, atau Program hanya menambah master dan policy matrix; tidak mengubah definisi saldo atau rumus laporan.

## 17.2 Multi Cabang

Jika Masjid memiliki cabang/unit kegiatan:

- setiap transaksi wajib memiliki unit/cabang accounting;
- Fund dapat bersifat lokal atau bersama, tetapi kebijakannya harus jelas;
- transfer antar cabang harus diperlakukan sebagai inter-unit transfer, bukan penerimaan/pengeluaran;
- laporan cabang dan konsolidasi harus dapat dieliminasi untuk transfer internal;
- Rekening dan rekonsiliasi dikelola pada unit pemilik Rekening.

## 17.3 Multi Masjid

Jika platform melayani banyak Masjid:

- setiap Masjid adalah batas unit accounting sendiri;
- tidak boleh ada pencampuran ledger, sequence nomor bukti, Fund, atau Rekening antar Masjid;
- laporan konsolidasi antar Masjid hanya dibuat bila ada dasar governance dan ownership yang sah;
- master policy dapat menggunakan template bersama, tetapi effective policy tetap milik masing-masing Masjid.

## 17.4 Multi Yayasan atau legal entity

- Setiap yayasan/legal entity memiliki CoA, Rekening, Fund, periode, closing, dan ledger sendiri.
- Transaksi antar legal entity tidak diperlakukan sebagai transfer internal biasa; harus menggunakan hubungan antar-entitas yang jelas.
- Dana amanah suatu legal entity tidak boleh digunakan oleh entity lain tanpa dasar legal dan policy yang terdokumentasi.
- Konsolidasi, bila diperlukan, dilakukan sebagai proses pelaporan dengan elimination yang dapat diaudit.

## 17.5 Governance perubahan kebijakan

- Setiap perubahan Fund classification, decision matrix, limit, bukti wajib, close calendar, atau materiality harus memiliki version, effective date, alasan, dan approval.
- Perubahan policy tidak menulis ulang transaksi historis.
- Policy owner melakukan review minimal tahunan atau saat ada perubahan regulasi, struktur Masjid, jenis Fund, atau temuan audit material.
- Sistem harus mampu menjalankan policy lama untuk transaksi lama dan policy baru untuk transaksi baru.

## 17.6 Retensi dan performa

- Ledger dan audit evidence harus dapat dipertahankan jangka panjang.
- Saldo dan dashboard dapat memakai projection/snapshot yang dapat dibangun ulang dari ledger.
- Laporan historis harus dapat menggunakan opening snapshot dan period movement tanpa mengorbankan traceability.
- Arsitektur harus menyediakan pencarian berdasarkan nomor bukti, Rekening, Fund, Program, Account, counterparty, dan periode.

---

# Lampiran A — Struktur Governance Keuangan Minimum

Struktur berikut adalah fungsi governance, bukan desain modul manajemen pengguna.

| Fungsi | Tanggung jawab minimum |
|---|---|
| Pengaju transaksi | Menyusun transaksi dan melampirkan bukti. |
| Verifikator | Memeriksa kelengkapan, Fund, Program, bukti, dan kebijakan. |
| Otoritas keuangan | Memberikan persetujuan finansial sesuai mandat pengurus. |
| Pembayar/treasury custodian | Menjalankan pembayaran/transfer dan menyimpan bukti eksekusi. |
| Fungsi posting | Memastikan hanya transaksi sah yang diposting. |
| Finance Controller | Melakukan tie-out, exception review, closing, dan kualitas laporan. |
| Rekonsiliator | Mencocokkan ledger dengan bank statement/cash count. |
| Reviewer independen | Meninjau transaksi material, adjustment, closing, dan exception. |
| Pengawas syariah/penasihat | Menetapkan atau meninjau kebijakan syariah dana khusus sesuai mandat. |

Satu orang dapat menjalankan beberapa fungsi hanya jika ukuran organisasi mengharuskan, tetapi review independen untuk transaksi material, adjustment, cash count, dan closing tetap harus dijaga sejauh praktis.

---

# Lampiran B — Checklist Keputusan Sebelum Implementasi Phase 2

Sebelum implementasi Financial Architecture V2 dimulai, pengurus wajib mengesahkan:

1. daftar Fund resmi beserta restriction policy;
2. matriks Fund–Pengeluaran dan Fund–Program;
3. matriks Rekening–Fund;
4. klasifikasi akun dan perlakuan restricted contribution;
5. basis accounting yang dipilih;
6. batas saldo minimum/reserve dan negative balance exception;
7. limit Petty Cash dan materiality threshold;
8. bukti wajib per jenis transaksi;
9. format nomor bukti dan scope sequence;
10. close calendar dan aturan reopen;
11. jadwal rekonsiliasi;
12. kebijakan Zakat, Fidyah, Wakaf, dan Qurban yang disahkan;
13. definisi transaction type dan posting rule catalogue;
14. cutover date serta metode opening balance;
15. retensi dokumen, privacy, dan audit evidence.

---

# Lampiran C — Definition of Done Kebijakan

Manual ini siap menjadi dasar Phase 2–4 setelah:

- seluruh keputusan pada Lampiran B disahkan;
- policy owner dan effective date ditetapkan;
- tidak ada Fund yang masih ambigu status restriction-nya;
- matrix keputusan memiliki pemilik dan siklus review;
- manual direview oleh fungsi keuangan dan penasihat syariah untuk dana khusus;
- cutover plan menyebut bagaimana saldo legacy direkonsiliasi per Rekening dan Fund;
- test plan Phase 2–4 menggunakan Business Rules BAB 15 sebagai acceptance baseline.

Dokumen ini harus ditinjau ulang minimal setahun sekali dan setiap kali terdapat perubahan regulasi, struktur organisasi, akun/rekening material, jenis Fund, atau temuan audit yang signifikan.
