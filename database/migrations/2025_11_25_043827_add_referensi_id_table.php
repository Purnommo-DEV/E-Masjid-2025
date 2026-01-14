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
            $table->unsignedBigInteger('referensi_id')->nullable()->after('kategori');

            $table->foreign('referensi_id')
                  ->references('id')
                  ->on('dana_terikat_penerima')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dana_terikat_penerima', function (Blueprint $table) {
            //
        });
    }
};
