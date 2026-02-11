<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->enum('umur_satuan', ['tahun', 'bulan', 'hari'])
                  ->after('umur')
                  ->comment('Satuan umur');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->dropColumn(['umur_nilai', 'umur_satuan']);
        });
    }
};
