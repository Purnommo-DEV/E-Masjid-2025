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
        Schema::create('dana_terikat_referensi', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique();
            $table->string('warna', 20)->nullable(); // buat warna baris (optional)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dana_terikat_referensi');
    }
};
