<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('khutbah_jumat', function (Blueprint $table) {
            $table->id();
            $table->string('khatib', 100);                         // Nama khatib
            $table->string('tema', 255)->nullable();               // Tema khutbah
            $table->string('tanggal', 100);                        // Contoh: Jum’at, 12 Jan 2026
            $table->string('jam', 50);                             // Contoh: 11.45 - 12.30
            $table->date('tanggal_asli')->nullable();              // Tanggal asli untuk sorting/filter
            $table->text('keterangan')->nullable();                   // Catatan tambahan (lokasi, dll)
            $table->boolean('is_active')->default(true);           // Tampilkan atau arsip
            $table->string('status', 50)->default('coming_soon');  // coming_soon, berlangsung, selesai
            $table->timestamps();

            $table->index('tanggal_asli');
            $table->index('status');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('khutbah_jumat');
    }
};