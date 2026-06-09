<?php
// database/migrations/xxxx_create_qurban_reports_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qurban_reports', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code');
            $table->string('tahun_hijriah')->default('1447 H');
            $table->string('tahun_masehi')->default('2026');
            
            // Status
            $table->boolean('is_active')->default(false);
            $table->boolean('is_published')->default(true);
            
            // ========== DATA HERO ==========
            $table->string('hero_title')->default('Laporan Idul Adha');
            $table->string('hero_subtitle')->nullable();
            $table->string('hero_badge')->default('✦ {TAHUN} H · DZULHIJJAH {TAHUN} ✦');
            $table->string('hero_masjid')->default('Masjid Raudhatul Jannah TCE');
            $table->text('hero_tagline')->nullable();
            
            // ========== STATISTIK UTAMA ==========
            $table->integer('stat_hewan_sapi')->default(0);
            $table->integer('stat_hewan_kambing')->default(0);
            $table->integer('stat_paket_daging')->default(0);
            $table->integer('stat_mustahik')->default(0);
            $table->integer('stat_daging_kg')->default(0);
            $table->string('stat_jamaah')->default('1.800+ Jamaah');
            
            // ========== DATA PELAKSANAAN ==========
            $table->text('pelaksanaan_deskripsi')->nullable();
            $table->string('pelaksanaan_ketua_nama')->nullable();
            $table->string('pelaksanaan_ketua_jabatan')->default('Ketua Panitia');
            $table->string('pelaksanaan_masjid_nama')->nullable();
            $table->string('pelaksanaan_masjid_sub')->nullable();
            $table->string('pelaksanaan_lokasi_sholat')->default('Lapangan Tenis TCE');
            $table->string('pelaksanaan_lokasi_qurban')->default('Masjid');
            $table->string('pelaksanaan_gambar1')->nullable();
            $table->string('pelaksanaan_gambar2')->nullable();
            $table->string('pelaksanaan_gambar3')->nullable();
            
            // ========== DATA DRAMATIS ==========
            $table->text('dramatis1_title')->nullable();
            $table->text('dramatis1_quote')->nullable();
            $table->string('dramatis1_stat')->nullable();
            $table->string('dramatis1_image')->nullable();
            
            $table->text('dramatis2_title')->nullable();
            $table->text('dramatis2_quote')->nullable();
            $table->string('dramatis2_stat')->nullable();
            $table->string('dramatis2_image')->nullable();
            
            $table->text('dramatis3_title')->nullable();
            $table->text('dramatis3_quote')->nullable();
            $table->string('dramatis3_stat')->nullable();
            $table->string('dramatis3_image')->nullable();
            
            // ========== DATA PEMOTONGAN ==========
            $table->integer('pemotongan_sapi_berat_kg')->default(0);
            $table->integer('pemotongan_kambing_berat_kg')->default(0);
            
            // ========== DATA KEUANGAN (3 BAGIAN TERPISAH) ==========
            $table->json('keuangan_penerimaan_peserta')->nullable();
            $table->json('keuangan_penerimaan_infaq')->nullable();
            $table->json('keuangan_pengeluaran')->nullable();
            $table->text('keuangan_catatan')->nullable();
            
            // ========== DATA PENERIMA MANFAAT ==========
            $table->json('rings')->nullable();
            $table->json('distribusi')->nullable();
            
            // ========== DATA GALERI ==========
            $table->json('gallery_images')->nullable();
            $table->json('additional_images')->nullable();
            
            // ========== DATA QR & CARD TERIMA KASIH ==========
            $table->string('qr_image')->nullable();
            $table->string('qr_link')->nullable();
            $table->text('thankyou_title')->nullable();
            $table->text('thankyou_message')->nullable();
            $table->text('thankyou_hadits')->nullable();
            
            // ========== DATA FOOTER ==========
            $table->string('footer_instagram')->nullable();
            $table->string('footer_whatsapp')->nullable();
            $table->string('footer_email')->nullable();
            $table->text('footer_quote')->nullable();
            
            // Catatan umum
            $table->text('catatan_keterangan')->nullable();
            
            $table->timestamps();
            
            // Index
            $table->unique(['masjid_code', 'tahun_hijriah']);
            $table->index('masjid_code');
            $table->index('tahun_hijriah');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('qurban_reports');
    }
};