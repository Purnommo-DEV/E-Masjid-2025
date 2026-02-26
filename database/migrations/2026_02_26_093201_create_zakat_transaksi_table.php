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
            $table->string('nomor_kwitansi')->unique();
            $table->foreignId('muzakki_id')->constrained('muzakki')->onDelete('restrict');
            $table->enum('tipe', ['penerimaan', 'penyaluran']);
            $table->date('tanggal');
            $table->decimal('total_nominal', 18, 0);
            $table->integer('total_jiwa')->default(0);
            $table->string('metode_bayar')->default('transfer'); // transfer, cash, beras, barang
            $table->text('keterangan')->nullable();
            $table->foreignId('akun_id')->constrained('akun_keuangan');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
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
