<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_qurbans', function (Blueprint $table) {
            $table->text('wish_informasi')->nullable()->after('masukan_penyebaran_informasi');
            $table->text('wish_pendaftaran')->nullable()->after('saran_pendaftaran');
            $table->text('wish_pelaksanaan')->nullable()->after('saran_pelaksanaan');
            $table->text('wish_distribusi')->nullable()->after('saran_distribusi');
            $table->text('wish_kualitas')->nullable()->after('saran_kualitas_hewan');
            $table->text('wish_umum')->nullable()->after('hal_perbaikan');
            
            // Hapus kolom wish lama jika ada
            if (Schema::hasColumn('evaluasi_qurbans', 'wish')) {
                $table->dropColumn('wish');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_qurbans', function (Blueprint $table) {
            $table->dropColumn([
                'wish_informasi', 
                'wish_pendaftaran', 
                'wish_pelaksanaan', 
                'wish_distribusi', 
                'wish_kualitas', 
                'wish_umum'
            ]);
            $table->text('wish')->nullable();
        });
    }
};