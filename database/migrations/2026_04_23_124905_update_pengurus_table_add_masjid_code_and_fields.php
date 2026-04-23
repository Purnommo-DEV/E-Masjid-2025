<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penguruses', function (Blueprint $table) {
            // Tambah masjid_code jika belum ada
            if (!Schema::hasColumn('penguruses', 'masjid_code')) {
                $table->string('masjid_code', 50)->default('mrj')->after('id');
                $table->index('masjid_code');
            }

            // Tambah foto_path jika belum ada (ganti dari foto Spatie)
            if (!Schema::hasColumn('penguruses', 'foto_path')) {
                $table->string('foto_path')->nullable()->after('keterangan');
            }

            // Tambah urutan jika belum ada
            if (!Schema::hasColumn('penguruses', 'urutan')) {
                $table->integer('urutan')->default(0)->after('foto_path');
            }

            // Tambah created_by dan updated_by jika belum ada
            if (!Schema::hasColumn('penguruses', 'created_by')) {
                $table->unsignedBigInteger('created_by')->nullable()->after('urutan');
            }
            if (!Schema::hasColumn('penguruses', 'updated_by')) {
                $table->unsignedBigInteger('updated_by')->nullable()->after('created_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('penguruses', function (Blueprint $table) {
            $table->dropColumn([
                'masjid_code',
                'foto_path',
                'urutan',
                'created_by',
                'updated_by'
            ]);
        });
    }
};