<?php
// database/migrations/2026_01_01_000020_add_bukti_pembayaran_to_qurban_registrations_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('qurban_registrations', function (Blueprint $table) {
            $table->string('bukti_pembayaran')->nullable()->after('catatan');
            $table->text('alasan_batal')->nullable()->after('bukti_pembayaran');
            $table->timestamp('uploaded_at')->nullable()->after('bukti_pembayaran');
            $table->unsignedBigInteger('uploaded_by')->nullable()->after('uploaded_at');
        });
    }

    public function down()
    {
        Schema::table('qurban_registrations', function (Blueprint $table) {
            $table->dropColumn(['bukti_pembayaran', 'alasan_batal', 'uploaded_at', 'uploaded_by']);
        });
    }
};