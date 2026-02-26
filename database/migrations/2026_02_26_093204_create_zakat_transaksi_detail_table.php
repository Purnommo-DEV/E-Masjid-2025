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
        Schema::create('zakat_transaksi_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('zakat_transaksi_id')->constrained('zakat_transaksi')->cascadeOnDelete();
            $table->string('jenis');
            $table->decimal('nominal', 18, 0);
            $table->integer('jiwa')->nullable();
            $table->decimal('jumlah_beras', 8, 3)->nullable();
            $table->string('satuan_beras')->nullable(); // kg, liter
            $table->foreignId('kadar_zakat_id')->nullable()->constrained('kadar_zakat');
            $table->text('keterangan_detail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zakat_transaksi_detail');
    }
};
