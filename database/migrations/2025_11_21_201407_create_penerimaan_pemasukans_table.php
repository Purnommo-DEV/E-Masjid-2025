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
        Schema::create('penerimaan_pemasukans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_pendapatan_id')->constrained('akun_keuangan');
            $table->bigInteger('jumlah');
            $table->date('tanggal');
            $table->text('keterangan');
            $table->string('sumber_nama')->nullable();           // Bukan "donatur" lagi
            $table->string('sumber_telepon')->nullable();
            $table->text('keterangan_tambahan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penerimaan_pemasukans');
    }
};
