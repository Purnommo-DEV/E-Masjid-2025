<?php
// database/migrations/2025_01_xx_xxxxxx_update_pemotongan_columns_to_string_in_qurban_reports.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('qurban_reports', function (Blueprint $table) {
            // Ubah dari integer ke string agar bisa diisi range seperti "33-35"
            $table->string('pemotongan_sapi_berat_kg', 50)->default('0')->change();
            $table->string('pemotongan_kambing_berat_kg', 50)->default('0')->change();
        });
    }

    public function down()
    {
        Schema::table('qurban_reports', function (Blueprint $table) {
            $table->integer('pemotongan_sapi_berat_kg')->default(0)->change();
            $table->integer('pemotongan_kambing_berat_kg')->default(0)->change();
        });
    }
};