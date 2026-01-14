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
            $table->foreignId('penerima_id')->nullable()->after('program_id')
                      ->constrained('dana_terikat_penerima');
                $table->index(['penerima_id']);
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
