<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dana_terikat_penerimaan', function (Blueprint $table) {
            if (! Schema::hasColumn('dana_terikat_penerimaan', 'jenis_dana')) {
                $table->enum('jenis_dana', [
                    'dana_terikat',
                    'zakat_maal',
                    'zakat_fitrah',
                    'fidyah',
                    'infaq_umum',
                    'shodaqoh',
                    'dana_titipan',
                ])->default('dana_terikat')->after('program_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('dana_terikat_penerimaan', function (Blueprint $table) {
            $table->dropColumn('jenis_dana');
        });
    }
};
