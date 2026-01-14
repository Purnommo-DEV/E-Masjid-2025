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
        Schema::create('pengeluaran_umums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_beban_id')->constrained('akun_keuangan');
            $table->bigInteger('jumlah');
            $table->text('keterangan');
            $table->date('tanggal')->default(now());
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pengeluaran_umums');
    }
};
