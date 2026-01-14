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
        Schema::table('akun_keuangan', function (Blueprint $table) {
            $table->enum('jenis_beban', ['kecil', 'besar', 'tidak_berlaku'])
              ->default('tidak_berlaku')
              ->after('tipe');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akun_keuangan', function (Blueprint $table) {
            //
        });
    }
};
