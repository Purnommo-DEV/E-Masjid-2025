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
        Schema::table('profil_masjids', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('email');          // Nama bank (misal BCA)
            $table->string('bank_code')->nullable()->after('bank_name');      // Kode bank (misal 014)
            $table->string('rekening')->nullable()->after('bank_code');       // Nomor rekening (tanpa spasi)
            $table->string('atas_nama')->nullable()->after('rekening');       // a/n ...
            $table->string('qris')->nullable()->after('atas_nama');           // Path file QRIS
            $table->string('wa_konfirmasi')->nullable()->after('qris');       // WA untuk konfirmasi donasi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profil_masjids', function (Blueprint $table) {
            //
        });
    }
};
