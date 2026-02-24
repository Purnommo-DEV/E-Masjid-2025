<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_imam_tarawih', function (Blueprint $table) {
            $table->id();
            $table->integer('malam_ke')->unique()->comment('Malam ke-1 sampai ke-30 Ramadhan');
            $table->date('tanggal')->nullable()->comment('Tanggal hijriah/masehi malam tarawih');
            $table->string('imam_nama')->nullable();
            $table->string('penceramah_nama')->nullable();
            $table->string('tema_tausiyah')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_imam_tarawih');
    }
};