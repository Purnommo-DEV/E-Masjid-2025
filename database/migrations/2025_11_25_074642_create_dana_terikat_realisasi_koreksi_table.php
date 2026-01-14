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
        Schema::create('dana_terikat_realisasi_koreksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('dana_terikat_program');
            $table->unsignedInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1-12
            $table->bigInteger('jumlah_koreksi')->comment('bisa positif atau negatif');
            $table->text('keterangan');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['program_id', 'tahun', 'bulan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dana_terikat_realisasi_koreksi');
    }
};
