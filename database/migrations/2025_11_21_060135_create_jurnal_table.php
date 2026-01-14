<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('jurnal', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('no_jurnal', 30);
            $table->morphs('jurnalable');               // nyambung ke transaksi/kotak/kas kecil
            $table->text('keterangan');
            $table->foreignId('akun_id')->constrained('akun_keuangan')->onDelete('restrict');
            $table->decimal('debit', 20, 2)->default(0);
            $table->decimal('kredit', 20, 2)->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['tanggal', 'akun_id']);
            $table->index('no_jurnal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jurnal');
    }
};
