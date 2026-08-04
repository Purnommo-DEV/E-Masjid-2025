<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dana_alokasi', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_id')
                ->constrained('dana_terikat_program')
                ->onDelete('cascade');

            $table->foreignId('akun_sumber_id')
                ->constrained('akun_keuangan')
                ->onDelete('restrict');

            $table->foreignId('akun_tujuan_id')
                ->constrained('akun_keuangan')
                ->onDelete('restrict');

            $table->date('tanggal');
            $table->decimal('jumlah', 15, 0);
            $table->text('keterangan')->nullable();
            $table->integer('bulan_cakupan')->default(0); // berapa bulan yang dialokasikan

            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['program_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_alokasi');
    }
};
