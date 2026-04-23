<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            // Tambah kolom masjid_code
            $table->string('masjid_code', 50)->default('mrj')->after('id');
            $table->index('masjid_code');
            
            // Tambah kolom image_path (ganti dari media Spatie)
            $table->string('image_path')->nullable()->after('urutan');
            
            // Hapus kolom yang tidak diperlukan (opsional, setelah migrasi data)
            // $table->dropColumn(['starts_at', 'ends_at']); // jika tidak dipakai
        });
    }
    
    public function down()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn(['masjid_code', 'image_path']);
        });
    }
};
