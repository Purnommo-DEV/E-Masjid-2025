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
        Schema::create('alokasi_dana', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('akun_sumber_id')->constrained('akun_keuangan');
            $table->foreignId('akun_tujuan_id')->constrained('akun_keuangan');
            $table->decimal('jumlah', 20, 2);
            $table->text('keterangan');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alokasi_dana');
    }
};
