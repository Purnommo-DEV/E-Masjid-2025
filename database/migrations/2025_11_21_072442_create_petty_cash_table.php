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
        Schema::create('petty_cash', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->decimal('jumlah', 20, 2);
            $table->text('keterangan');
            $table->enum('tipe', ['isi_ulang', 'pengeluaran']);
            $table->foreignId('akun_beban_id')->nullable()->constrained('akun_keuangan');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash');
    }
};
