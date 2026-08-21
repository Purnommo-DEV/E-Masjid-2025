<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dana_terikat_penerimaan', function (Blueprint $table) {
            $table->boolean('is_saldo_awal')->default(false)->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('dana_terikat_penerimaan', function (Blueprint $table) {
            $table->dropColumn('is_saldo_awal');
        });
    }
};