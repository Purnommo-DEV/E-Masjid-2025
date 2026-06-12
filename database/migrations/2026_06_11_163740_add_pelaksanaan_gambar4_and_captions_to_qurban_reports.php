<?php
// database/migrations/2025_xx_xx_xxxxxx_add_pelaksanaan_gambar4_and_captions_to_qurban_reports.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('qurban_reports', function (Blueprint $table) {
            // Gambar 4 (baru)
            $table->string('pelaksanaan_gambar4')->nullable()->after('pelaksanaan_gambar3');
            
            // Caption untuk masing-masing gambar
            $table->string('pelaksanaan_caption1')->nullable()->after('pelaksanaan_gambar1');
            $table->string('pelaksanaan_caption2')->nullable()->after('pelaksanaan_gambar2');
            $table->string('pelaksanaan_caption3')->nullable()->after('pelaksanaan_gambar3');
            $table->string('pelaksanaan_caption4')->nullable()->after('pelaksanaan_gambar4');
        });
    }

    public function down()
    {
        Schema::table('qurban_reports', function (Blueprint $table) {
            $table->dropColumn([
                'pelaksanaan_gambar4',
                'pelaksanaan_caption1',
                'pelaksanaan_caption2',
                'pelaksanaan_caption3',
                'pelaksanaan_caption4'
            ]);
        });
    }
};