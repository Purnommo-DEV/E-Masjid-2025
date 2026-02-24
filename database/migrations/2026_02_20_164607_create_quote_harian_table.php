<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_harian', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);                          // Contoh: QS. Al-Baqarah: 186, HR. Bukhari
            $table->text('text');                                  // Isi quote / ayat / hadits
            $table->string('source_type', 50)->nullable();         // Opsional: quran, hadits, dll
            $table->integer('order')->default(0);                  // Untuk sorting jika manual
            $table->boolean('is_active')->default(true);
            $table->date('scheduled_date')->nullable();            // Jika ingin quote spesifik per tanggal
            $table->timestamps();

            $table->index('is_active');
            $table->index('scheduled_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_harian');
    }
};