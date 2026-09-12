<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->string('rt', 5)->nullable()->after('alamat');
            $table->string('rw', 5)->nullable()->after('rt');
            $table->string('nama_rt', 150)->nullable()->after('rw');
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->dropColumn(['rt', 'rw', 'nama_rt']);
        });
    }
};
