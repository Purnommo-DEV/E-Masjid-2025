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
        Schema::table('akun_keuangan', function (Blueprint $table) {
            $table->string('grup')->nullable()->after('jenis_beban');
            $table->index('grup'); // biar cepat query where('grup', 'kotak_infak')
        });
    }

    public function down()
    {
        Schema::table('akun_keuangan', function (Blueprint $table) {
            $table->dropIndex(['grup']);
            $table->dropColumn('grup');
        });
    }
};
