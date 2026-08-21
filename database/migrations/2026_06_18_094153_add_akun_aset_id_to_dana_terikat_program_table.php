<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dana_terikat_program', function (Blueprint $table) {
            $table->foreignId('akun_aset_id')
                ->nullable()
                ->after('akun_liabilitas_id')
                ->constrained('akun_keuangan')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dana_terikat_program', function (Blueprint $table) {
            $table->dropConstrainedForeignId('akun_aset_id');
        });
    }
};