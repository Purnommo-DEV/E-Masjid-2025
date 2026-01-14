<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zakat_transaksi', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_kwitansi')->unique(); // ZK/2025/00001
            $table->json('jenis_zakat'); // ["zakat_fitrah","zakat_maal",...]
            $table->string('muzakki_utama');
            $table->string('no_hp')->nullable();
            $table->text('daftar_keluarga'); // JSON: [{"nama":"Ahmad", "jiwa":1}, ...]
            $table->integer('total_jiwa')->default(0);
            $table->string('satuan_beras')->nullable(); // Liter / Kg
            $table->decimal('jumlah', 18, 0);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->enum('tipe', ['penerimaan', 'penyaluran']);
            $table->unsignedBigInteger('akun_id');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('akun_id')->references('id')->on('akun_keuangan');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_transaksi');
    }
};
