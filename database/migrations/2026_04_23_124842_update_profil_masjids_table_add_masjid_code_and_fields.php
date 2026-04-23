<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('profil_masjids', function (Blueprint $table) {
            // Tambah masjid_code
            if (!Schema::hasColumn('profil_masjids', 'masjid_code')) {
                $table->string('masjid_code', 50)->default('mrj')->after('id');
                $table->index('masjid_code');
            }

            // Tambah kolom singkatan jika belum ada
            if (!Schema::hasColumn('profil_masjids', 'singkatan')) {
                $table->string('singkatan')->nullable()->after('nama');
            }

            // Tambah kolom untuk menyimpan path gambar (tanpa Spatie)
            if (!Schema::hasColumn('profil_masjids', 'logo_path')) {
                $table->string('logo_path')->nullable()->after('singkatan');
            }

            if (!Schema::hasColumn('profil_masjids', 'struktur_path')) {
                $table->string('struktur_path')->nullable()->after('logo_path');
            }

            if (!Schema::hasColumn('profil_masjids', 'qris_path')) {
                $table->string('qris_path')->nullable()->after('struktur_path');
            }

            // Tambah kolom donasi jika belum ada
            if (!Schema::hasColumn('profil_masjids', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('wa_konfirmasi');
            }
            if (!Schema::hasColumn('profil_masjids', 'bank_code')) {
                $table->string('bank_code')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('profil_masjids', 'rekening')) {
                $table->string('rekening')->nullable()->after('bank_code');
            }
            if (!Schema::hasColumn('profil_masjids', 'atas_nama')) {
                $table->string('atas_nama')->nullable()->after('rekening');
            }
            if (!Schema::hasColumn('profil_masjids', 'wa_konfirmasi')) {
                $table->string('wa_konfirmasi')->nullable()->after('atas_nama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('profil_masjids', function (Blueprint $table) {
            $table->dropColumn([
                'masjid_code',
                'singkatan',
                'logo_path',
                'struktur_path',
                'qris_path',
                'bank_name',
                'bank_code',
                'rekening',
                'atas_nama',
                'wa_konfirmasi'
            ]);
        });
    }
};