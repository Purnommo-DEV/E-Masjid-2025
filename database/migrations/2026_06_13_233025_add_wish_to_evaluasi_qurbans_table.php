<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluasi_qurbans', function (Blueprint $table) {
            $table->text('wish')->nullable()->after('saran_tambahan');
        });
    }

    public function down(): void
    {
        Schema::table('evaluasi_qurbans', function (Blueprint $table) {
            $table->dropColumn('wish');
        });
    }
};