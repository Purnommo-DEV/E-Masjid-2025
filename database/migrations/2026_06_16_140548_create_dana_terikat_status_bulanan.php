<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dana_terikat_status_bulanan', function (Blueprint $table) {
            $table->id();
            
            // Relasi
            $table->foreignId('program_id')->constrained('dana_terikat_program')->onDelete('cascade');
            $table->foreignId('penerima_id')->constrained('dana_terikat_penerima')->onDelete('cascade');
            
            // Periode
            $table->year('tahun');
            $table->unsignedTinyInteger('bulan'); // 1-12
            
            // Status kelayakan bulan ini
            $table->boolean('status_dapat')->default(true);
            $table->text('alasan_tidak_dapat')->nullable();
            
            // Data alternatif (jika ada perubahan mendadak)
            $table->string('nama_alternatif')->nullable();
            $table->decimal('nominal_alternatif', 12, 0)->nullable();
            
            // Tracking verifikasi
            $table->string('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            
            // Audit
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            
            // Unique constraint: 1 penerima hanya 1 status per bulan
            $table->unique(['penerima_id', 'tahun', 'bulan'], 'unique_status_bulanan');
            
            // Index untuk performa
            $table->index(['program_id', 'tahun', 'bulan']);
            $table->index(['program_id', 'penerima_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dana_terikat_status_bulanan');
    }
};