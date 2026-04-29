<?php
// database/migrations/2026_04_25_000002_create_qurban_galleries_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qurban_galleries', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code', 50)->default('mrj')->index();
            $table->foreignId('qurban_id')->constrained('qurbans')->onDelete('cascade');
            $table->string('image_path');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_cover')->default(false);
            $table->integer('urutan')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['qurban_id', 'is_cover']);
            $table->index(['qurban_id', 'urutan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qurban_galleries');
    }
};