<?php
// database/seeders/QurbanReportSeeder.php

namespace Database\Seeders;

use App\Models\QurbanReport;
use Illuminate\Database\Seeder;

class QurbanReportSeeder extends Seeder
{
    public function run()
    {
        // Hapus semua data lama (opsional, komentar jika tidak ingin menghapus)
        // QurbanReport::where('masjid_code', 'mrj')->delete();
        
        // ========== LAPORAN TAHUN 1447H (AKTIF) ==========
        // QurbanReport::updateOrCreate(
        //     ['masjid_code' => 'mrj', 'tahun_hijriah' => '1447H'],
        //     [
        //         'masjid_code' => 'mrj',
        //         'tahun_hijriah' => '1447H',
        //         'tahun_masehi' => '2026',
        //         'is_active' => true,
        //         'is_published' => true,
        //         'hero_title' => 'Laporan Idul Adha',
        //         'hero_subtitle' => '1447 H',
        //         'hero_badge' => '✦ 1447 H · DZULHIJJAH 1447 ✦',
        //         'hero_masjid' => 'Masjid Raudhatul Jannah TCE',
        //         'hero_tagline' => '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2',
        //         'stat_hewan_sapi' => 8,
        //         'stat_hewan_kambing' => 44,
        //         'stat_paket_daging' => 1127,
        //         'stat_mustahik' => 1127,
        //         'stat_daging_kg' => 0,
        //         'stat_jamaah' => '0 Jamaah',
        //         'pelaksanaan_ketua_nama' => 'Hamdi',
        //         'pelaksanaan_ketua_jabatan' => 'Ketua Panitia',
        //         'pelaksanaan_masjid_nama' => 'Masjid Raudhotul Jannah',
        //         'pelaksanaan_masjid_sub' => 'Panitia Idul Adha 1447 H',
        //         'pelaksanaan_lokasi_sholat' => 'Lapangan Tenis TCE',
        //         'pelaksanaan_lokasi_qurban' => 'Parkiran Masjid Raudhotul Jannah TCE',
        //         'pelaksanaan_deskripsi' => 'Alhamdulillah, atas izin Allah Subhanahu wa Ta\'ala, seluruh rangkaian kegiatan Idul Adha 1447 H telah terlaksana dengan baik, lancar, dan penuh khidmat. Semoga ibadah qurban yang kita laksanakan menjadi wujud ketakwaan dan keikhlasan dalam mendekatkan diri kepada Allah SWT.
                
        //         Mulai dari pelaksanaan Shalat Idul Adha di Lapangan Tenis TCE, hingga proses penyembelihan 8 ekor sapi dan 44 ekor kambing, serta pendistribusian sekitar 1.127 paket daging qurban, semuanya dapat berjalan dengan tertib. Semua ini berkat kebersamaan, gotong royong, dan partisipasi seluruh pihak yang terlibat, baik dari panitia, relawan, shohibul qurban, maupun jamaah sekalian.',
        //         'pelaksanaan_gambar1' => null,
        //         'pelaksanaan_gambar2' => null,
        //         'pelaksanaan_gambar3' => null,
        //         'pelaksanaan_gambar4' => null,
        //         'pelaksanaan_caption1' => 'Sholat Idul Adha',
        //         'pelaksanaan_caption2' => 'Khotib Khatibah',
        //         'pelaksanaan_caption3' => 'Penyembelihan Qurban',
        //         'pelaksanaan_caption4' => 'Distribusi Daging Qurban',
        //         'dramatis1_title' => 'Kebersamaan Idul Adha 1447 H',
        //         'dramatis1_quote' => '"Suasana khusyuk dan penuh kebersamaan mewarnai pelaksanaan Idul Adha 1447 H di lingkungan TCE."',
        //         'dramatis1_stat' => null,
        //         'dramatis1_image' => null,
        //         'dramatis2_title' => 'Pelaksanaan Qurban',
        //         'dramatis2_quote' => '"Melalui qurban, nilai keikhlasan dan semangat berbagi diwujudkan dalam tindakan nyata"',
        //         'dramatis2_stat' => null,
        //         'dramatis2_image' => '/storage/mrj/qurban-report/1447H/images/1781227118_s7SVgdJE8q.webp',
        //         'dramatis3_title' => 'Distribusi Daging Qurban',
        //         'dramatis3_quote' => 'Alhamdulillah, sekitar 1.127 paket daging qurban berhasil disalurkan kepada keluarga penerima manfaat, baik di lingkungan TCE maupun masyarakat umum, secara tertib dan merata, InsyaAllah.',
        //         'dramatis3_stat' => null,
        //         'dramatis3_image' => '/storage/mrj/qurban-report/1447H/images/1781226359_x7BFp8hGXN.webp',
        //         'pemotongan_sapi_berat_kg' => '350',
        //         'pemotongan_kambing_berat_kg' => '33-35',
        //         'keuangan_penerimaan_peserta' => [
        //             ['label' => 'Uang qurban dari peserta qurban untuk 8 ekor sapi', 'amount' => 210304000],
        //             ['label' => 'Uang qurban dari peserta qurban untuk 31 ekor kambing', 'amount' => 117800000],
        //             ['label' => 'Uang pemotongan hewan qurban untuk 13 ekor kambing yang dibeli sendiri oleh peserta', 'amount' => 3900000],
        //             ['label' => 'Infak dari pengqurban', 'amount' => 150000],
        //         ],
        //         'keuangan_penerimaan_infaq' => [
        //             ['label' => 'Penerimaan melalui tromol', 'amount' => 20829000],
        //             ['label' => 'Penerimaan melalui QRIS BSI', 'amount' => 2782023],
        //         ],
        //         'keuangan_pengeluaran' => [
        //             ['label' => 'Pembayaran ke penjual sapi sebanyak 8 ekor', 'amount' => 184424000],
        //             ['label' => 'Pembayaran ke penjual kambing sebanyak 31 ekor', 'amount' => 108500000],
        //             ['label' => 'Biaya pemotongan dan pencacahan hewan qurban (8 ekor sapi dan 41 ekor kambing)', 'amount' => 22400000],
        //             ['label' => 'Penyewaan sound system untuk sholat ied', 'amount' => 4000000],
        //             ['label' => 'Pembelian alat perlengkapan pencacahan dan pendistribusian daging hewan qurban', 'amount' => 2669500],
        //             ['label' => 'Honor untuk pekerja kebersihan, penjaga hewan qurban dan angkut daging', 'amount' => 1400000],
        //             ['label' => 'Biaya pembuatan spanduk, flyer dan banner untuk pengadaan hewan qurban', 'amount' => 250000],
        //             ['label' => 'Biaya pembuatan spanduk untuk kegiatan sholat iedul adha', 'amount' => 229000],
        //             ['label' => 'Biaya konsumsi untuk pemotongan dan pendistribusian daging hewan qurban', 'amount' => 8055000],
        //             ['label' => 'Biaya konsumsi untuk kegiatan sholat iedul adha', 'amount' => 497000],
        //             ['label' => 'Biaya penyelenggaraan sholat ied (honor petugas penyiapan sholat dan khotib & imam)', 'amount' => 3400000],
        //         ],
        //         'keuangan_catatan' => null,
        //         'rings' => [
        //             [
        //                 'icon' => 'fa-building',
        //                 'color' => 'emerald',
        //                 'title' => 'RING I — Warga TCE & Perangkat',
        //                 'total' => '361 penerima',
        //                 'items' => [
        //                     ['label' => 'Warga RT 01-11 Taman Cipulir Estate', 'value' => '180 orang'],
        //                     ['label' => 'Satpam, Tukang Sampah & Tukang Taman', 'value' => '22 orang'],
        //                     ['label' => 'Guru & OB SMP Al Madina', 'value' => '9 orang'],
        //                     ['label' => 'Perangkat Masjid, Panitia & Relawan', 'value' => '80 orang'],
        //                     ['label' => 'Tukang Jagal / Pemotong', 'value' => '45 orang'],
        //                     ['label' => 'Lainnya ( Ibu-ibu Pejuang Shubuh, Ibu-ibu Guru TPA MRJ, Tuna Netra, Tukang Parkir dll )', 'value' => '25 orang'],
        //                 ],
        //             ],
        //             [
        //                 'icon' => 'fa-globe',
        //                 'color' => 'teal',
        //                 'title' => 'RING II — Luar TCE & Umum',
        //                 'total' => '766 penerima',
        //                 'items' => [
        //                     ['label' => 'RT03/06, RT03/04, RT04/06, RT01/06, RT02/06, RT06/07, dan RT05/04.', 'value' => '562 orang'],
        //                     ['label' => 'Pesanggrahan (Ibu Hadi)', 'value' => '20 orang'],
        //                     ['label' => 'Panti Asuhan', 'value' => '14 orang'],
        //                     ['label' => 'Masyarakat Umum (tanpa kupon)', 'value' => '170 orang'],
        //                 ],
        //             ],
        //         ],
        //         'distribusi' => null,
        //         'gallery_images' => [],
        //         'additional_images' => [],
        //         'qr_image' => null,
        //         'qr_link' => null,
        //         'thankyou_title' => 'Jazakumullahu Khairan Katsiran',
        //         'thankyou_message' => 'Kepada seluruh shahibul qurban, jamaah, panitia, relawan, serta semua pihak yang telah berpartisipasi dalam pelaksanaan Idul Adha 1447 H, kami mengucapkan terima kasih yang sebesar-besarnya.

        //         Semoga Allah SWT menerima amal ibadah dan qurban kita, melimpahkan keberkahan dalam kehidupan, serta menjadikan kita hamba-Nya yang senantiasa ikhlas dalam beribadah dan berbagi kepada sesama.

        //         Apabila terdapat kekurangan dalam pelaksanaan kegiatan ini, kami memohon maaf yang sebesar-besarnya.

        //         Taqabbalallahu minna wa minkum. Aamiin Ya Rabbal \'Alamiin.',
        //         'thankyou_hadits' => '"Dan tolong-menolonglah kamu dalam kebajikan dan takwa." (QS. Al-Ma\'idah: 2)',
        //         'footer_instagram' => 'https://www.instagram.com/masjidrj.tce/',
        //         'footer_whatsapp' => 'https://whatsapp.com/channel/0029VbCGqiT60eBgsDDNIV2s',
        //         'footer_email' => 'dkmmrjtce@gmail.com',
        //         'footer_quote' => '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162',
        //         'catatan_keterangan' => 'Laporan resmi Idul Adha 1447 H dengan peningkatan jumlah qurban dan jamaah.',
        //     ]
        // );

        // ========== LAPORAN TAHUN 1446 H ==========
        QurbanReport::updateOrCreate(
            ['masjid_code' => 'mrj', 'tahun_hijriah' => '1446 H'],
            [
                'masjid_code' => 'mrj',
                'tahun_hijriah' => '1446 H',
                'tahun_masehi' => '2025',
                'is_active' => false,
                'is_published' => true,
                'hero_title' => 'Laporan Idul Adha',
                'hero_subtitle' => '1446 H',
                'hero_badge' => '✦ 1446 H · DZULHIJJAH 1446 ✦',
                'hero_masjid' => 'Masjid Raudhatul Jannah TCE',
                'hero_tagline' => '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2',
                
                // Statistik
                'stat_hewan_sapi' => 11,
                'stat_hewan_kambing' => 41,
                'stat_paket_daging' => 1172,
                'stat_mustahik' => 1172,
                'stat_daging_kg' => 0,
                'stat_jamaah' => '0 Jamaah',
                
                // Pelaksanaan
                'pelaksanaan_ketua_nama' => 'Imam Syahyudi',
                'pelaksanaan_ketua_jabatan' => 'Ketua Panitia',
                'pelaksanaan_masjid_nama' => 'Masjid Raudhotul Jannah',
                'pelaksanaan_masjid_sub' => 'Panitia Idul Adha 1446 H',
                'pelaksanaan_lokasi_sholat' => 'Lapangan Tenis TCE',
                'pelaksanaan_lokasi_qurban' => 'Masjid',
                'pelaksanaan_deskripsi' => 'Pada pelaksanaan tahun ini, pemotongan dilakukan pada hari Sabtu, 11 Dzulhijjah 1446 H bertepatan dengan 7 Juni 2025. Panitia menerima amanah 11 ekor sapi dan 41 ekor kambing dari para Shohibul Qurban. Seluruh hewan telah diperiksa dan dinyatakan layak sesuai syariat Islam.',
                
                'pelaksanaan_gambar1' => null,
                'pelaksanaan_gambar2' => null,
                'pelaksanaan_gambar3' => null,
                'pelaksanaan_gambar4' => null,
                'pelaksanaan_caption1' => 'Sholat Idul Adha 1446 H',
                'pelaksanaan_caption2' => 'Khotib Khatibah',
                'pelaksanaan_caption3' => 'Penyembelihan Hewan Qurban',
                'pelaksanaan_caption4' => 'Distribusi Daging Qurban',
                
                // Dramatis
                'dramatis1_title' => 'Kebersamaan Idul Adha 1446 H',
                'dramatis1_quote' => 'Suasana khusyuk dan penuh kebersamaan mewarnai pelaksanaan Idul Adha 1446 H di lingkungan TCE.',
                'dramatis1_stat' => null,
                'dramatis1_image' => null,
                'dramatis2_title' => 'Pelaksanaan Qurban',
                'dramatis2_quote' => '11 ekor sapi dan 41 ekor kambing disembelih dengan penuh ketulusan.',
                'dramatis2_stat' => null,
                'dramatis2_image' => null,
                'dramatis3_title' => 'Distribusi Daging Qurban',
                'dramatis3_quote' => 'Alhamdulillah, ribuan paket daging qurban berhasil disalurkan kepada keluarga penerima manfaat.',
                'dramatis3_stat' => null,
                'dramatis3_image' => null,
                
                // Pemotongan
                'pemotongan_sapi_berat_kg' => '350',
                'pemotongan_kambing_berat_kg' => '35',
                
                // Keuangan berdasarkan data yang Anda berikan
                'keuangan_penerimaan_peserta' => [
                    ['label' => 'Uang qurban dari peserta qurban untuk 8 ekor sapi', 'amount' => 196680000],
                    ['label' => 'Uang qurban dari peserta qurban untuk 31 ekor kambing', 'amount' => 113200000],
                    ['label' => 'Uang pemotongan hewan qurban untuk 3 ekor sapi yang dibeli sendiri oleh peserta', 'amount' => 5400000],
                    ['label' => 'Uang pemotongan hewan qurban untuk 9 ekor kambing yang dibeli sendiri oleh peserta', 'amount' => 2700000],
                ],
                'keuangan_penerimaan_infaq' => [
                    ['label' => 'Penerimaan uang infaq Sholat Ied', 'amount' => 24219000],
                ],
                'keuangan_pengeluaran' => [
                    ['label' => 'Pembayaran ke penjual sapi (8 ekor sapi dengan berat total ± 2.804 kg)', 'amount' => 176652000],
                    ['label' => 'Pembayaran ke penjual kambing (31 ekor dengan berat rata-rata 35 kg per ekor)', 'amount' => 106950000],
                    ['label' => 'Biaya pemotongan hewan qurban', 'amount' => 16963000],
                    ['label' => 'Penyewaan sound system untuk Sholat Ied', 'amount' => 3500000],
                    ['label' => 'Biaya penyelenggaraan Sholat Ied (honor petugas penyiapan tempat sholat dan keamanan, khotib dan imam)', 'amount' => 3700000],
                    ['label' => 'Pembelian perlengkapan pencacahan dan pendistribusian daging hewan qurban', 'amount' => 1708228],
                    ['label' => 'Biaya survei pengadaan hewan qurban', 'amount' => 625000],
                    ['label' => 'Biaya pembuatan spanduk, flyer dan banner untuk pengadaan hewan qurban', 'amount' => 523000],
                    ['label' => 'Biaya pembuatan spanduk untuk kegiatan Sholat Idul Adha', 'amount' => 355000],
                    ['label' => 'Biaya konsumsi untuk pemotongan dan pendistribusian hewan qurban serta makan bersama jamaah', 'amount' => 4266000],
                    ['label' => 'Biaya konsumsi untuk kegiatan Sholat Idul Adha', 'amount' => 1321000],
                ],
                'keuangan_catatan' => 'Saldo kas dimasukkan ke kas DKM sebesar Rp 25.635.772',
                
                // Rings
                'rings' => [
                    [
                        'icon' => 'fa-building',
                        'color' => 'emerald',
                        'title' => 'RING I — Warga TCE & Perangkat',
                        'total' => '170+ penerima',
                        'items' => [
                            ['label' => 'Satpam & Tukang Taman TCE', 'value' => '10 orang'],
                            ['label' => 'Guru TPA, SD & SMP Al Madinah', 'value' => '23 orang'],
                            ['label' => 'Perangkat Masjid dan Panitia & Relawan', 'value' => '40 orang'],
                            ['label' => 'Tukang jagal & pemotongan (Jurus Berkah)', 'value' => '57 orang'],
                            ['label' => 'RT-RT Wilayah Taman Cipulir Estate (TCE)', 'value' => '40 orang'],
                        ],
                    ],
                    [
                        'icon' => 'fa-globe',
                        'color' => 'teal',
                        'title' => 'RING II — Luar TCE & Umum',
                        'total' => '1.142+ penerima',
                        'items' => [
                            ['label' => 'RT-RT diluar Perumahan TCE', 'value' => '792 orang'],
                            ['label' => 'Pesanggrahan (Bu Hadi)', 'value' => '40 orang'],
                            ['label' => 'Titipan', 'value' => '46 orang'],
                            ['label' => 'Panti Asuhan', 'value' => '20 orang'],
                            ['label' => 'Masyarakat Umum (tanpa kupon)', 'value' => '244 orang'],
                        ],
                    ],
                ],
                
                'distribusi' => null,
                'gallery_images' => [],
                'additional_images' => [],
                'qr_image' => null,
                'qr_link' => null,
                
                // Thank You
                'thankyou_title' => 'Jazakallah Khairan',
                'thankyou_message' => 'Semoga Allah menerima amal ibadah kita semua, melipatgandakan pahala, dan menjadikan qurban ini sebagai penebus dosa-dosa kita. Aamiin Ya Rabbal Alamin.',
                'thankyou_hadits' => '"Dan tolong-menolonglah kamu dalam kebajikan dan takwa." (QS. Al-Ma\'idah: 2)',
                
                // Footer
                'footer_instagram' => 'https://www.instagram.com/masjidrj.tce/',
                'footer_whatsapp' => 'https://whatsapp.com/channel/0029VbCGqiT60eBgsDDNIV2s',
                'footer_email' => 'dkmmrjtce@gmail.com',
                'footer_quote' => '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162',
                'catatan_keterangan' => 'Laporan resmi Idul Adha 1446 H. Saldo kas dimasukkan ke kas DKM sebesar Rp 25.635.772',
            ]
        );
    }
}