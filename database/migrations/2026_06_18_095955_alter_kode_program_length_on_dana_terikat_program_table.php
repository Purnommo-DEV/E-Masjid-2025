<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dana_terikat_program', function (Blueprint $table) {
            $table->string('kode_program', 15)->change();
        });
    }

    public function down(): void
    {
        Schema::table('dana_terikat_program', function (Blueprint $table) {
            $table->string('kode_program', 10)->change();
        });
    }
};