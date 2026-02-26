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
        Schema::create('kadar_zakat', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->string('jenis')->index();
            $table->decimal('nilai_uang', 12, 0);
            $table->decimal('nilai_beras_kg', 8, 3)->nullable();
            $table->decimal('nilai_beras_liter', 8, 3)->nullable();
            $table->string('sumber')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kadar_zakat');
    }
};
