<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_qurban_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code')->default('mrj');
            $table->string('tahun_hijriah');
            $table->json('summary_data');
            $table->boolean('is_active')->default(true);
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();
            
            $table->index('tahun_hijriah');
            $table->index('masjid_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_qurban_summaries');
    }
};