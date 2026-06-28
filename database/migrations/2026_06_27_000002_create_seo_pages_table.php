<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code', 50)->default('mrj');
            $table->string('page_key', 100);
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->nullable();
            $table->timestamps();

            $table->unique(['masjid_code', 'page_key']);
            $table->index('masjid_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
    }
};
