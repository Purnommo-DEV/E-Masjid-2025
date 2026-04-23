<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('galeri_media')) {
            Schema::create('galeri_media', function (Blueprint $table) {
                $table->id();
                $table->string('masjid_code', 50)->default('mrj');
                $table->foreignId('galeri_id')->constrained('galeris')->onDelete('cascade');
                $table->string('image_path'); // mrj/galeri/slug-judul/nama-file.webp
                $table->string('file_name')->nullable();
                $table->integer('urutan')->default(0);
                $table->timestamps();
                
                $table->index('masjid_code');
                $table->index(['galeri_id', 'urutan']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('galeri_media');
    }
};