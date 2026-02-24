<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide_motivasi', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);                          // Judul utama
            $table->text('subtitle')->nullable();                  // Subjudul (boleh HTML)
            $table->string('button_text', 100);                    // Teks tombol
            $table->string('button_link', 255);                    // Link tombol (#rekening, #qris, dll)
            $table->string('gradient', 100)->default('from-emerald-700 via-teal-700 to-emerald-600');
            $table->string('border_color', 100)->default('border-emerald-500/30');
            $table->integer('order')->default(1);                  // Urutan tampil
            $table->boolean('is_active')->default(true);           // Status aktif/tidak
            $table->timestamps();
            
            // Index untuk performa sorting & filtering
            $table->index('order');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_motivasi');
    }
};