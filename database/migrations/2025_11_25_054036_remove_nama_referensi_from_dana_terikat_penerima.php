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
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {
            if (Schema::hasColumn('dana_terikat_penerima', 'nama_referensi')) {
                $table->dropColumn('nama_referensi');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {
            $table->string('nama_referensi')->nullable();
        });
    }
};
