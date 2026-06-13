<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_qurbans', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code')->default('mrj');
            $table->string('tahun_hijriah')->default('1447 H');
            $table->string('nama_shohibul');
            $table->enum('jenis_hewan', ['sapi', 'kambing']);
            
            // Sumber informasi
            $table->string('sumber_info')->nullable();
            $table->string('sumber_info_lainnya')->nullable();
            
            // Tempat berkurban
            $table->enum('tempat_qurban', ['masjid', 'pihak_lain', 'keduanya']);
            
            // Rating (1-5)
            $table->integer('rating_pendaftaran')->default(3);
            $table->integer('rating_pelaksanaan')->default(3);
            $table->integer('rating_distribusi')->default(3);
            $table->integer('rating_kualitas_hewan')->default(3);
            
            // Saran & masukan
            $table->text('saran_pendaftaran')->nullable();
            $table->text('saran_pelaksanaan')->nullable();
            $table->text('saran_distribusi')->nullable();
            $table->text('saran_kualitas_hewan')->nullable();
            $table->text('hal_baik')->nullable();
            $table->text('hal_perbaikan')->nullable();
            $table->text('saran_tambahan')->nullable();
            
            // Rencana qurban tahun depan
            $table->enum('rencana_qurban', ['ya', 'mungkin', 'tidak'])->default('mungkin');
            
            $table->timestamps();
            
            // Index
            $table->index('masjid_code');
            $table->index('tahun_hijriah');
            $table->index('jenis_hewan');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_qurbans');
    }
};