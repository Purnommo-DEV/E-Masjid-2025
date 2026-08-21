<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {
            // 1️⃣ HAPUS foreign key dulu (jika ada)
            $table->dropForeign(['penerima_id']);
            
            // 2️⃣ HAPUS kolom penerima_id
            $table->dropColumn('penerima_id');
            
            // 3️⃣ TAMBAH kolom keterangan
            $table->text('keterangan')->nullable()->after('status_aktif');
        });
    }

    public function down(): void
    {
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {
            // 1️⃣ HAPUS kolom keterangan
            $table->dropColumn('keterangan');
            
            // 2️⃣ TAMBAH kembali kolom penerima_id
            $table->unsignedBigInteger('penerima_id')->nullable()->after('program_id');
            
            // 3️⃣ TAMBAH foreign key kembali
            $table->foreign('penerima_id')
                  ->references('id')
                  ->on('dana_terikat_penerima')
                  ->nullOnDelete();
        });
    }
};