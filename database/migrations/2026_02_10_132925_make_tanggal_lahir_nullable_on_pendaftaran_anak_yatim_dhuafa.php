<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->date('tanggal_lahir')->nullable(false)->change();
        });
    }
};
