<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->string('masjid_code')->default('mrj')->after('id');
            $table->index('masjid_code');
        });
    }

    public function down(): void
    {
        Schema::table('pengumumans', function (Blueprint $table) {
            $table->dropColumn('masjid_code');
        });
    }
};