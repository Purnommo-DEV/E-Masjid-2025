<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            // Tambah masjid_code
            if (!Schema::hasColumn('acaras', 'masjid_code')) {
                $table->string('masjid_code', 50)->default('mrj')->after('id');
                $table->index('masjid_code');
            }

            // Ubah kolom gambar jadi image_path (biar konsisten dengan banner)
            if (Schema::hasColumn('acaras', 'gambar')) {
                $table->renameColumn('gambar', 'image_path');
            } else if (!Schema::hasColumn('acaras', 'image_path')) {
                $table->string('image_path')->nullable()->after('penyelenggara');
            }

            // Tambah kolom yang mungkin belum ada
            if (!Schema::hasColumn('acaras', 'pemateri')) {
                $table->string('pemateri')->nullable()->after('penyelenggara');
            }

            if (!Schema::hasColumn('acaras', 'waktu_teks')) {
                $table->string('waktu_teks')->nullable()->after('pemateri');
            }

            if (!Schema::hasColumn('acaras', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            // Ubah foreign key created_by jadi nullable (biar bisa diisi manual)
            if (Schema::hasColumn('acaras', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('acaras', function (Blueprint $table) {
            $table->dropColumn(['masjid_code', 'pemateri', 'waktu_teks', 'updated_by']);
            
            if (Schema::hasColumn('acaras', 'image_path')) {
                $table->renameColumn('image_path', 'gambar');
            }
            
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
    }
};