<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_qurbans', function (Blueprint $table) {
            $table->text('masukan_penyebaran_informasi')->nullable()->after('sumber_info_lainnya');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_qurbans', function (Blueprint $table) {
            $table->dropColumn('masukan_penyebaran_informasi');
        });
    }
};