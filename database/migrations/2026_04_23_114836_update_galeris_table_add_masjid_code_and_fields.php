<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('galeris', function (Blueprint $table) {
            // Tambah masjid_code
            if (!Schema::hasColumn('galeris', 'masjid_code')) {
                $table->string('masjid_code', 50)->default('mrj')->after('id');
                $table->index('masjid_code');
            }

            // Ubah tipe kolom tipe jadi enum (opsional, biar konsisten)
            // if (Schema::hasColumn('galeris', 'tipe')) {
            //     $table->enum('tipe', ['foto', 'video'])->default('foto')->change();
            // }

            // Tambah updated_by
            if (!Schema::hasColumn('galeris', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }

            // Ubah created_by jadi nullable (biar bisa diisi manual)
            if (Schema::hasColumn('galeris', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('galeris', function (Blueprint $table) {
            $table->dropColumn(['masjid_code', 'updated_by']);
            
            // Kembalikan created_by ke not nullable
            if (Schema::hasColumn('galeris', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable(false)->change();
            }
        });
    }
};