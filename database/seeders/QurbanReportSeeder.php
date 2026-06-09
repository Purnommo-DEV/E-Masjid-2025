<?php
// database/seeders/QurbanReportSeeder.php

namespace Database\Seeders;

use App\Models\QurbanReport;
use Illuminate\Database\Seeder;

class QurbanReportSeeder extends Seeder
{
    public function run()
    {
        // Default Ring I items
        $ring1Items = [
            ['label' => '👥 Warga RT/RW Taman Cipulir Estate', 'value' => 'Seluruh warga'],
            ['label' => '🛡️ Satpam & Tukang Taman TCE', 'value' => '10 orang'],
            ['label' => '📚 Guru TPA, SD & SMP Al Madinah', 'value' => '23 orang'],
            ['label' => '🕌 Perangkat Masjid & Panitia', 'value' => '40 orang'],
            ['label' => '🥩 Tukang Jagal / Pemotong', 'value' => '57 orang'],
        ];
        
        $ring2Items = [
            ['label' => '🏘️ RT/RW di luar Perumahan TCE', 'value' => '792 penerima'],
            ['label' => '🤲 Pesanggrahan (Bu Hadi) Titipan', 'value' => '40 orang'],
            ['label' => '🧸 Panti Asuhan & Anak Yatim', 'value' => '46 anak'],
            ['label' => '🎟️ Masyarakat Umum (tanpa kupon)', 'value' => '244 paket'],
        ];
        
        // ========== KEUANGAN 3 BAGIAN TERPISAH ==========
        
        // 1. Penerimaan dari Peserta Qurban
        $keuanganPenerimaanPeserta = [
            ['label' => 'Uang qurban untuk 8 ekor sapi', 'amount' => 280000000],
            ['label' => 'Uang qurban untuk 31 ekor kambing', 'amount' => 62000000],
            ['label' => 'Uang pemotongan 3 ekor sapi (beli sendiri)', 'amount' => 1500000],
            ['label' => 'Uang pemotongan 9 ekor kambing (beli sendiri)', 'amount' => 900000],
        ];
        
        // 2. Penerimaan Infaq Sholat Ied
        $keuanganPenerimaanInfaq = [
            ['label' => 'Infaq sholat Ied', 'amount' => 18750000],
        ];
        
        // 3. Pengeluaran
        $keuanganPengeluaran = [
            ['label' => 'Pembayaran ke penjual sapi (8 ekor ~2.804 kg)', 'amount' => 280000000],
            ['label' => 'Pembayaran ke penjual kambing (31 ekor)', 'amount' => 62000000],
            ['label' => 'Biaya pemotongan hewan qurban', 'amount' => 4500000],
            ['label' => 'Penyewaan sound system sholat Ied', 'amount' => 3500000],
            ['label' => 'Biaya penyelenggaraan sholat Ied (honor, khotib, imam, keamanan)', 'amount' => 5500000],
            ['label' => 'Biaya survei pengadaan hewan qurban', 'amount' => 1200000],
            ['label' => 'Biaya spanduk, flyer, banner pengadaan hewan qurban', 'amount' => 1850000],
            ['label' => 'Biaya spanduk kegiatan sholat Ied', 'amount' => 800000],
            ['label' => 'Biaya konsumsi (pemotongan, distribusi, makan bersama)', 'amount' => 3200000],
        ];
        
        // Gallery Images
        $galleryImages = [
            ['url' => '/storage/qurban/gallery/photo1.jpg', 'alt' => 'Suasana Sholat Ied', 'type' => 'landscape'],
            ['url' => '/storage/qurban/gallery/photo2.jpg', 'alt' => 'Jamaah Sholat', 'type' => 'square'],
            ['url' => '/storage/qurban/gallery/photo3.jpg', 'alt' => 'Khatib', 'type' => 'square'],
            ['url' => '/storage/qurban/gallery/photo4.jpg', 'alt' => 'Hewan Qurban', 'type' => 'square'],
            ['url' => '/storage/qurban/gallery/photo5.jpg', 'alt' => 'Pembagian Daging', 'type' => 'landscape'],
            ['url' => '/storage/qurban/gallery/photo6.jpg', 'alt' => 'Penyembelihan', 'type' => 'square'],
            ['url' => '/storage/qurban/gallery/photo7.jpg', 'alt' => 'Senyum Mustahik', 'type' => 'square'],
            ['url' => '/storage/qurban/gallery/photo8.jpg', 'alt' => 'Suasana', 'type' => 'square'],
            ['url' => '/storage/qurban/gallery/photo9.jpg', 'alt' => 'Kebersamaan', 'type' => 'landscape'],
            ['url' => '/storage/qurban/gallery/photo10.jpg', 'alt' => 'Momen Haru', 'type' => 'square'],
        ];
        
        $additionalImages = [
            '/storage/qurban/gallery/photo11.jpg',
            '/storage/qurban/gallery/photo12.jpg',
            '/storage/qurban/gallery/photo13.jpg',
            '/storage/qurban/gallery/photo14.jpg',
            '/storage/qurban/gallery/photo15.jpg',
        ];
        
        // ========== LAPORAN TAHUN 1446 H (ARSIP) ==========
        QurbanReport::updateOrCreate(
            ['masjid_code' => 'mrj', 'tahun_hijriah' => '1446 H'],
            [
                'tahun_masehi' => '2025',
                'is_active' => false,
                'is_published' => true,
                'hero_title' => 'Laporan Idul Adha',
                'hero_subtitle' => '1446 H',
                'hero_badge' => '✦ 1446 H · DZULHIJJAH 1446 ✦',
                'hero_masjid' => 'Masjid Raudhatul Jannah TCE',
                'hero_tagline' => '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2',
                'stat_hewan_sapi' => 6,
                'stat_hewan_kambing' => 35,
                'stat_paket_daging' => 1200,
                'stat_mustahik' => 1050,
                'stat_daging_kg' => 1200,
                'stat_jamaah' => '1.500+ Jamaah',
                'pelaksanaan_ketua_nama' => 'Bapak Hamdi',
                'pelaksanaan_ketua_jabatan' => 'Ketua Panitia',
                'pelaksanaan_masjid_nama' => 'Masjid Raudhotul Jannah',
                'pelaksanaan_masjid_sub' => 'Panitia Idul Adha 1446 H',
                'pelaksanaan_lokasi_sholat' => 'Lapangan Tenis TCE',
                'pelaksanaan_lokasi_qurban' => 'Masjid',
                'dramatis1_title' => 'Takbir Menggema, Hati Bergetar',
                'dramatis1_quote' => '"Ribuan jamaah memenuhi lapangan, suara takbir menggema. Inilah keindahan ukhuwah di pagi Idul Adha."',
                'dramatis1_stat' => '1.500+ Jamaah',
                'dramatis2_title' => 'Keikhlasan yang Tersembelih',
                'dramatis2_quote' => '"6 sapi dan 35 kambing disembelih dengan penuh ketulusan."',
                'dramatis2_stat' => '41 ekor hewan qurban | 1.200+ paket daging',
                'dramatis3_title' => 'Senyum di Balik Paket Daging',
                'dramatis3_quote' => '"Tak terlihat kemewahan, hanya bahagia dari wajah-wajah yang menerima."',
                'dramatis3_stat' => '1.050+ keluarga merasakan kebahagiaan',
                'pemotongan_sapi_berat_kg' => 350,
                'pemotongan_kambing_berat_kg' => 34,
                'keuangan_penerimaan_peserta' => [
                    ['label' => 'Uang qurban untuk 6 ekor sapi', 'amount' => 210000000],
                    ['label' => 'Uang qurban untuk 35 ekor kambing', 'amount' => 70000000],
                    ['label' => 'Uang pemotongan 2 ekor sapi (beli sendiri)', 'amount' => 1000000],
                    ['label' => 'Uang pemotongan 8 ekor kambing (beli sendiri)', 'amount' => 800000],
                ],
                'keuangan_penerimaan_infaq' => [
                    ['label' => 'Infaq sholat Ied', 'amount' => 15000000],
                ],
                'keuangan_pengeluaran' => [
                    ['label' => 'Pembayaran ke penjual sapi (6 ekor)', 'amount' => 210000000],
                    ['label' => 'Pembayaran ke penjual kambing (35 ekor)', 'amount' => 70000000],
                    ['label' => 'Biaya pemotongan hewan qurban', 'amount' => 4000000],
                    ['label' => 'Penyewaan sound system sholat Ied', 'amount' => 3000000],
                    ['label' => 'Biaya penyelenggaraan sholat Ied', 'amount' => 5000000],
                    ['label' => 'Biaya survei', 'amount' => 1000000],
                    ['label' => 'Biaya spanduk dan banner', 'amount' => 2000000],
                    ['label' => 'Biaya konsumsi', 'amount' => 2500000],
                ],
                'rings' => [
                    ['title' => 'RING I — Warga TCE & Perangkat', 'icon' => 'fa-building', 'color' => 'emerald', 'items' => $ring1Items, 'total' => '130+ penerima'],
                    ['title' => 'RING II — Luar TCE & Umum', 'icon' => 'fa-globe', 'color' => 'teal', 'items' => $ring2Items, 'total' => '1.122+ penerima'],
                ],
                'distribusi' => [
                    ['label' => 'Shohibul Qurban', 'value' => 400, 'icon' => 'fa-star-of-life', 'percentage' => 33],
                    ['label' => 'Masyarakat Sekitar', 'value' => 400, 'icon' => 'fa-building', 'percentage' => 33],
                    ['label' => 'Fakir Miskin & Dhuafa', 'value' => 400, 'icon' => 'fa-hands-helping', 'percentage' => 34],
                ],
                'gallery_images' => $galleryImages,
                'additional_images' => $additionalImages,
                'thankyou_title' => 'Jazakallah Khairan',
                'thankyou_message' => 'Semoga Allah menerima amal ibadah kita semua, melipatgandakan pahala, dan menjadikan qurban ini sebagai penebus dosa-dosa kita. Aamiin Ya Rabbal Alamin.',
                'thankyou_hadits' => '"Barangsiapa yang bersedekah, maka ia akan mendapatkan pahala dari Allah." — HR. Bukhari Muslim',
                'footer_instagram' => 'https://instagram.com/raudhatuljannah',
                'footer_whatsapp' => 'https://wa.me/6285716503815',
                'footer_email' => 'info@raudhatuljannah.com',
                'footer_quote' => '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162',
            ]
        );
        
        // ========== LAPORAN TAHUN 1447 H (AKTIF) ==========
        QurbanReport::updateOrCreate(
            ['masjid_code' => 'mrj', 'tahun_hijriah' => '1447 H'],
            [
                'tahun_masehi' => '2026',
                'is_active' => true,
                'is_published' => true,
                'hero_title' => 'Laporan Idul Adha',
                'hero_subtitle' => '1447 H',
                'hero_badge' => '✦ 1447 H · DZULHIJJAH 1447 ✦',
                'hero_masjid' => 'Masjid Raudhatul Jannah TCE',
                'hero_tagline' => '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2',
                'stat_hewan_sapi' => 8,
                'stat_hewan_kambing' => 41,
                'stat_paket_daging' => 1400,
                'stat_mustahik' => 1252,
                'stat_daging_kg' => 1500,
                'stat_jamaah' => '1.800+ Jamaah',
                'pelaksanaan_ketua_nama' => 'Bapak Hamdi',
                'pelaksanaan_ketua_jabatan' => 'Ketua Panitia',
                'pelaksanaan_masjid_nama' => 'Masjid Raudhotul Jannah',
                'pelaksanaan_masjid_sub' => 'Panitia Idul Adha 1447 H',
                'pelaksanaan_lokasi_sholat' => 'Lapangan Tenis TCE',
                'pelaksanaan_lokasi_qurban' => 'Masjid',
                'dramatis1_title' => 'Takbir Menggema, Hati Bergetar',
                'dramatis1_quote' => '"Ribuan jamaah memenuhi lapangan, suara takbir menggema. Inilah keindahan ukhuwah di pagi Idul Adha."',
                'dramatis1_stat' => '1.800+ Jamaah',
                'dramatis2_title' => 'Keikhlasan yang Tersembelih',
                'dramatis2_quote' => '"8 sapi dan 41 kambing disembelih dengan penuh ketulusan."',
                'dramatis2_stat' => '49 ekor hewan qurban | 1.400+ paket daging',
                'dramatis3_title' => 'Senyum di Balik Paket Daging',
                'dramatis3_quote' => '"Tak terlihat kemewahan, hanya bahagia dari wajah-wajah yang menerima."',
                'dramatis3_stat' => '1.200+ keluarga merasakan kebahagiaan',
                'pemotongan_sapi_berat_kg' => 350,
                'pemotongan_kambing_berat_kg' => 34,
                'keuangan_penerimaan_peserta' => $keuanganPenerimaanPeserta,
                'keuangan_penerimaan_infaq' => $keuanganPenerimaanInfaq,
                'keuangan_pengeluaran' => $keuanganPengeluaran,
                'rings' => [
                    ['title' => 'RING I — Warga TCE & Perangkat', 'icon' => 'fa-building', 'color' => 'emerald', 'items' => $ring1Items, 'total' => '130+ penerima'],
                    ['title' => 'RING II — Luar TCE & Umum', 'icon' => 'fa-globe', 'color' => 'teal', 'items' => $ring2Items, 'total' => '1.122+ penerima'],
                ],
                'distribusi' => [
                    ['label' => 'Shohibul Qurban', 'value' => 467, 'icon' => 'fa-star-of-life', 'percentage' => 33, 'color' => 'emerald'],
                    ['label' => 'Masyarakat Sekitar', 'value' => 467, 'icon' => 'fa-building', 'percentage' => 33, 'color' => 'teal'],
                    ['label' => 'Fakir Miskin & Dhuafa', 'value' => 466, 'icon' => 'fa-hands-helping', 'percentage' => 34, 'color' => 'amber'],
                ],
                'gallery_images' => $galleryImages,
                'additional_images' => $additionalImages,
                'thankyou_title' => 'Jazakallah Khairan',
                'thankyou_message' => 'Semoga Allah menerima amal ibadah kita semua, melipatgandakan pahala, dan menjadikan qurban ini sebagai penebus dosa-dosa kita. Aamiin Ya Rabbal Alamin.',
                'thankyou_hadits' => '"Barangsiapa yang bersedekah, maka ia akan mendapatkan pahala dari Allah." — HR. Bukhari Muslim',
                'footer_instagram' => 'https://instagram.com/raudhatuljannah',
                'footer_whatsapp' => 'https://wa.me/6285716503815',
                'footer_email' => 'info@raudhatuljannah.com',
                'footer_quote' => '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162',
            ]
        );
        
        // ========== LAPORAN TAHUN 1448 H (TEMPLATE TAHUN DEPAN) ==========
        QurbanReport::updateOrCreate(
            ['masjid_code' => 'mrj', 'tahun_hijriah' => '1448 H'],
            [
                'tahun_masehi' => '2027',
                'is_active' => false,
                'is_published' => false,
                'hero_title' => 'Laporan Idul Adha',
                'hero_subtitle' => '1448 H',
                'hero_badge' => '✦ 1448 H · DZULHIJJAH 1448 ✦',
                'hero_masjid' => 'Masjid Raudhatul Jannah TCE',
                'hero_tagline' => '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2',
                'stat_hewan_sapi' => 0,
                'stat_hewan_kambing' => 0,
                'stat_paket_daging' => 0,
                'stat_mustahik' => 0,
                'stat_daging_kg' => 0,
                'stat_jamaah' => '0+ Jamaah',
                'pelaksanaan_ketua_nama' => '',
                'pelaksanaan_ketua_jabatan' => 'Ketua Panitia',
                'pelaksanaan_masjid_nama' => 'Masjid Raudhotul Jannah',
                'pelaksanaan_masjid_sub' => 'Panitia Idul Adha 1448 H',
                'pelaksanaan_lokasi_sholat' => 'Lapangan Tenis TCE',
                'pelaksanaan_lokasi_qurban' => 'Masjid',
                'dramatis1_title' => '',
                'dramatis1_quote' => '',
                'dramatis1_stat' => '',
                'dramatis2_title' => '',
                'dramatis2_quote' => '',
                'dramatis2_stat' => '',
                'dramatis3_title' => '',
                'dramatis3_quote' => '',
                'dramatis3_stat' => '',
                'pemotongan_sapi_berat_kg' => 350,
                'pemotongan_kambing_berat_kg' => 34,
                'keuangan_penerimaan_peserta' => [],
                'keuangan_penerimaan_infaq' => [],
                'keuangan_pengeluaran' => [],
                'rings' => [],
                'distribusi' => [],
                'gallery_images' => [],
                'additional_images' => [],
                'thankyou_title' => 'Jazakallah Khairan',
                'thankyou_message' => 'Semoga Allah menerima amal ibadah kita semua.',
                'thankyou_hadits' => '',
                'footer_instagram' => 'https://instagram.com/raudhatuljannah',
                'footer_whatsapp' => 'https://wa.me/6285716503815',
                'footer_email' => 'info@raudhatuljannah.com',
                'footer_quote' => '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162',
            ]
        );
    }
}