<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('berita_media')) {
            Schema::create('berita_media', function (Blueprint $table) {
                $table->id();
                $table->string('masjid_code', 50)->default('mrj');
                $table->foreignId('berita_id')->constrained('beritas')->onDelete('cascade');
                $table->string('image_path'); // mrj/berita/slug-berita/nama-file.webp
                $table->string('file_name')->nullable();
                $table->boolean('is_cover')->default(false);
                $table->integer('urutan')->default(0);
                $table->timestamps();
                
                // Index
                $table->index('masjid_code');
                $table->index(['berita_id', 'is_cover']);
                $table->index(['berita_id', 'urutan']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('berita_media');
    }
};