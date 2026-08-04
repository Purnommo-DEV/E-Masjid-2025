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
        Schema::table('saldo_awal_details', function (Blueprint $table) {
            $table->foreignId('lawan_akun_id')->nullable()->constrained('akun_keuangan')->nullOnDelete()->after('akun_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('saldo_awal_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('lawan_akun_id');
        });
    }
};
