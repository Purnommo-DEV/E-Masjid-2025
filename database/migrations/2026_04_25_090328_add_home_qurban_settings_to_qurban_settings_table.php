<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        $settings = [
            // Toggle ON/OFF
            ['key' => 'show_qurban_on_home', 'value' => 'true', 'type' => 'boolean', 'label' => 'Tampilkan Qurban di Halaman Home', 'urutan' => 100],
            
            // Hero / Badge
            ['key' => 'home_qurban_badge', 'value' => '✨ HARI RAYA IDUL ADHA 1447 H / 27 MEI 2026 ✨', 'type' => 'text', 'label' => 'Badge Teks', 'urutan' => 101],
            
            // Judul
            ['key' => 'home_qurban_title_line1', 'value' => 'Raih Kemuliaan', 'type' => 'text', 'label' => 'Judul Baris 1', 'urutan' => 102],
            ['key' => 'home_qurban_title_line2', 'value' => 'Ibadah Qurban', 'type' => 'text', 'label' => 'Judul Baris 2 (Highlight)', 'urutan' => 103],
            
            // Subtitle
            ['key' => 'home_qurban_subtitle', 'value' => '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah!" (QS. Al-Kautsar: 2)', 'type' => 'textarea', 'label' => 'Subtitle', 'urutan' => 104],
            
            // Manfaat (JSON array)
            ['key' => 'home_qurban_benefits', 'value' => json_encode([
                'Mendekatkan diri kepada Allah',
                'Berbagi kebahagiaan',
                'Amal yang paling mulia'
            ]), 'type' => 'json', 'label' => 'List Manfaat', 'urutan' => 105],
            
            // Tombol
            ['key' => 'home_qurban_btn_daftar_text', 'value' => 'Daftar Qurban', 'type' => 'text', 'label' => 'Tombol Daftar - Teks', 'urutan' => 106],
            ['key' => 'home_qurban_btn_info_text', 'value' => 'Info Lengkap Qurban', 'type' => 'text', 'label' => 'Tombol Info - Teks', 'urutan' => 107],
            
            // Tanggal
            ['key' => 'home_qurban_tgl_pendaftaran', 'value' => '1 Apr - 20 Mei 2026', 'type' => 'text', 'label' => 'Tanggal Pendaftaran', 'urutan' => 108],
            ['key' => 'home_qurban_tgl_pelaksanaan', 'value' => '27 Mei 2026', 'type' => 'text', 'label' => 'Tanggal Pelaksanaan', 'urutan' => 109],
            ['key' => 'home_qurban_tgl_hijriah', 'value' => '10 Dzulhijjah 1447 H', 'type' => 'text', 'label' => 'Tanggal Hijriah', 'urutan' => 110],
            
            // Harga
            ['key' => 'home_qurban_harga_mulai', 'value' => 'Rp 3.000.000,-', 'type' => 'text', 'label' => 'Label Harga Mulai Dari', 'urutan' => 111],
            
            // Target Link
            ['key' => 'home_qurban_link_daftar', 'value' => '/qurban#form-pendaftaran', 'type' => 'text', 'label' => 'Link Tombol Daftar', 'urutan' => 112],
            ['key' => 'home_qurban_link_info', 'value' => '/qurban#info-qurban', 'type' => 'text', 'label' => 'Link Tombol Info', 'urutan' => 113],
            
            // Gambar
            ['key' => 'home_qurban_image', 'value' => 'storage/qurban-hewan.png', 'type' => 'image', 'label' => 'Gambar Hewan Qurban', 'urutan' => 114],
            
            // Background Gradient
            ['key' => 'home_qurban_bg_start', 'value' => 'from-emerald-900', 'type' => 'text', 'label' => 'Background Gradient Start', 'urutan' => 115],
            ['key' => 'home_qurban_bg_mid', 'value' => 'via-emerald-800', 'type' => 'text', 'label' => 'Background Gradient Mid', 'urutan' => 116],
            ['key' => 'home_qurban_bg_end', 'value' => 'to-emerald-900', 'type' => 'text', 'label' => 'Background Gradient End', 'urutan' => 117],
        ];
        
        foreach ($settings as $setting) {
            DB::table('qurban_settings')->updateOrInsert(
                [
                    'masjid_code' => masjid(),
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

    public function down()
    {
        $keys = [
            'show_qurban_on_home',
            'home_qurban_badge',
            'home_qurban_title_line1',
            'home_qurban_title_line2',
            'home_qurban_subtitle',
            'home_qurban_benefits',
            'home_qurban_btn_daftar_text',
            'home_qurban_btn_info_text',
            'home_qurban_tgl_pendaftaran',
            'home_qurban_tgl_pelaksanaan',
            'home_qurban_tgl_hijriah',
            'home_qurban_harga_mulai',
            'home_qurban_link_daftar',
            'home_qurban_link_info',
            'home_qurban_image',
            'home_qurban_bg_start',
            'home_qurban_bg_mid',
            'home_qurban_bg_end',
        ];
        
        DB::table('qurban_settings')
            ->where('masjid_code', masjid())
            ->whereIn('key', $keys)
            ->delete();
    }
};