<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            // Tambah masjid_code
            if (!Schema::hasColumn('beritas', 'masjid_code')) {
                $table->string('masjid_code', 50)->default('mrj')->after('id');
                $table->index('masjid_code');
            }

            // Ubah atau tambah kolom excerpt jika perlu
            if (!Schema::hasColumn('beritas', 'excerpt')) {
                $table->text('excerpt')->nullable()->after('isi');
            }

            // Tambah updated_by
            if (!Schema::hasColumn('beritas', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            // Hapus kolom gambar jika masih ada (karena pindah ke berita_media)
            if (Schema::hasColumn('beritas', 'gambar')) {
                $table->dropColumn('gambar');
            }
        });
    }

    public function down(): void
    {
        Schema::table('beritas', function (Blueprint $table) {
            $table->dropColumn(['masjid_code', 'excerpt', 'updated_by']);
            $table->string('gambar')->nullable()->after('isi');
        });
    }
};