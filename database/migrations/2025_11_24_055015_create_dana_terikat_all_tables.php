<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // database/migrations/xxxx_xx_xx_create_dana_terikat_all_tables.php
    public function up()
    {
        // 1. Master Program (BRK, SAY, dll)
        Schema::create('dana_terikat_program', function (Blueprint $table) {
            $table->id();
            $table->string('kode_program', 10)->unique();        // BRK, SAY, SGN
            $table->string('nama_program');
            $table->foreignId('akun_liabilitas_id')->constrained('akun_keuangan');
            $table->text('keterangan')->nullable();
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // 2. Penerima (yatim, dhuafa, dll) — TERPISAH PER TAHUN!
        Schema::create('dana_terikat_penerima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('dana_terikat_program');
            $table->year('tahun_program')->default(date('Y'));
            $table->string('nama');
            $table->string('no_hp')->nullable();
            $table->text('alamat')->nullable();
            $table->string('kategori')->nullable(); // yatim / dhuafa / lainnya
            $table->string('nama_referensi')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->boolean('status_yatim')->default(0); // 1 = masih dikategorikan anak yatim
            $table->integer('umur')->nullable();
            $table->string('nama_rt')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->decimal('nominal_bulanan', 12, 0);
            $table->boolean('status_aktif')->default(true);
            $table->timestamps();
            $table->index(['program_id', 'tahun_program']);
        });

        // 3. Penerimaan Dana Terikat
        Schema::create('dana_terikat_penerimaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('dana_terikat_program');
            $table->date('tanggal');
            $table->decimal('jumlah', 15, 0);
            $table->string('donatur_nama')->nullable();
            $table->string('donatur_kontak')->nullable();
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // 4. Realisasi Bulanan
        Schema::create('dana_terikat_realisasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('dana_terikat_program');
            $table->foreignId('penerima_id')->constrained('dana_terikat_penerima');
            $table->year('tahun');
            $table->unsignedTinyInteger('bulan'); // 1-12
            $table->decimal('jumlah', 12, 0);
            $table->text('keterangan')->nullable();
            $table->string('bukti_path')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['penerima_id', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dana_terikat_all_tables');
    }
};
