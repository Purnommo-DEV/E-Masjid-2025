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
        Schema::table('kotak_infaks', function (Blueprint $table) {
            $table->foreignId('akun_pendapatan_id')->after('jenis_kotak_id')->constrained('akun_keuangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kotak_infaks', function (Blueprint $table) {
            $table->foreignId('akun_pendapatan_id')->after('jenis_kotak_id')->constrained('akun_keuangan');
        });
    }
};
