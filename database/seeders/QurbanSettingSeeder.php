<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QurbanSettingSeeder extends Seeder
{
    public function run()
    {
        $masjidCode = 'mrj'; // Sesuaikan dengan kode masjid Anda
        
        $settings = [
            // Hero Section
            ['key' => 'hero_title', 'value' => 'Masjid Raudhotul Jannah', 'type' => 'text', 'label' => 'Hero Title', 'urutan' => 1],
            ['key' => 'hero_subtitle', 'value' => 'Menerima & Menyalurkan Hewan Qurban', 'type' => 'text', 'label' => 'Hero Subtitle', 'urutan' => 2],
            ['key' => 'hero_badge_text', 'value' => 'PANITIA IDUL ADHA 1447 H / 2026 M', 'type' => 'text', 'label' => 'Badge Teks', 'urutan' => 3],
            
            // Kontak
            ['key' => 'contact_info_name', 'value' => 'Bapak Joko', 'type' => 'text', 'label' => 'Nama Kontak Informasi', 'urutan' => 4],
            ['key' => 'contact_info_phone', 'value' => '085716503815', 'type' => 'text', 'label' => 'No WA Informasi', 'urutan' => 5],
            ['key' => 'contact_confirmation_name', 'value' => 'Bapak Jazuli', 'type' => 'text', 'label' => 'Nama Kontak Konfirmasi', 'urutan' => 6],
            ['key' => 'contact_confirmation_phone', 'value' => '081310185948', 'type' => 'text', 'label' => 'No WA Konfirmasi', 'urutan' => 7],
            
            // Bank
            ['key' => 'bank_name', 'value' => 'BCA', 'type' => 'text', 'label' => 'Nama Bank', 'urutan' => 8],
            ['key' => 'bank_account_number', 'value' => '1010010947479', 'type' => 'text', 'label' => 'Nomor Rekening', 'urutan' => 9],
            ['key' => 'bank_account_name', 'value' => 'JAZULI', 'type' => 'text', 'label' => 'Atas Nama', 'urutan' => 10],
            
            // Statistik
            ['key' => 'stats_hewan_tersedia', 'value' => '50+', 'type' => 'text', 'label' => 'Hewan Tersedia', 'urutan' => 11],
            ['key' => 'stats_lokasi_distribusi', 'value' => 'TCE', 'type' => 'text', 'label' => 'Lokasi Distribusi', 'urutan' => 12],
            ['key' => 'stats_penerima_manfaat', 'value' => '500+', 'type' => 'text', 'label' => 'Penerima Manfaat', 'urutan' => 13],
            ['key' => 'stats_tersalurkan', 'value' => '100%', 'type' => 'text', 'label' => 'Tersalurkan', 'urutan' => 14],
            
            // Harga Potong
            ['key' => 'potong_sapi_harga', 'value' => '1800000', 'type' => 'number', 'label' => 'Biaya Potong Sapi', 'urutan' => 15],
            ['key' => 'potong_kambing_harga', 'value' => '300000', 'type' => 'number', 'label' => 'Biaya Potong Kambing', 'urutan' => 16],
            
            // Catatan Penting (JSON)
            ['key' => 'important_notes', 'value' => json_encode([
                'Pendaftaran paling lambat H-2 sebelum Idul Adha (8 Dzulhijjah)',
                'Penyerahan hewan sendiri: H-1 sebelum hari pemotongan',
                'Jika patungan 1 ekor sapi tidak mencapai 7 orang, akan dialihkan ke qurban kambing & membayar biaya potong dan distribusi Rp150.000',
                'Harga sudah termasuk biaya potong dan distribusi untuk paket resmi panitia',
            ]), 'type' => 'json', 'label' => 'Catatan Penting', 'urutan' => 17],
            
            // FAQ (JSON)
            ['key' => 'faq_items', 'value' => json_encode([
                ['question' => 'Bolehkah qurban untuk orang yang sudah meninggal?', 'answer' => 'Boleh, asalkan diniatkan untuk mereka yang telah wafat.'],
                ['question' => 'Bagaimana cara pembayaran qurban?', 'answer' => 'Transfer ke rekening BCA 1010010947479 a.n. JAZULI, lalu konfirmasi ke Bapak Jazuli.'],
                ['question' => 'Apakah bisa memilih lokasi distribusi?', 'answer' => 'Distribusi difokuskan ke Taman Cipulir Estate dan sekitarnya.'],
                ['question' => 'Apa yang terjadi jika patungan sapi tidak sampai 7 orang?', 'answer' => 'Akan dialihkan ke qurban kambing dengan biaya tambahan potong Rp150.000.'],
            ]), 'type' => 'json', 'label' => 'FAQ', 'urutan' => 18],
        ];
        
        foreach ($settings as $setting) {
            DB::table('qurban_settings')->updateOrInsert(
                [
                    'masjid_code' => $masjidCode,
                    'key' => $setting['key'],
                ],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                    'urutan' => $setting['urutan'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}