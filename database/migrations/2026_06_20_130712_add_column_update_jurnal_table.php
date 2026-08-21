<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jurnal', function (Blueprint $table) {
            $table->string('jurnalable_type')->nullable()->change();
            $table->unsignedBigInteger('jurnalable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('jurnal', function (Blueprint $table) {
            $table->string('jurnalable_type')->nullable(false)->change();
            $table->unsignedBigInteger('jurnalable_id')->nullable(false)->change();
        });
    }
};