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
        Schema::create('akun_keuangan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 12)->unique();                    // 10001, 30001, dll
            $table->string('nama', 150);
            $table->enum('tipe', ['aset', 'liabilitas', 'ekuitas', 'pendapatan', 'beban']);
            $table->enum('saldo_normal', ['debit', 'kredit']);
            $table->unsignedBigInteger('parent_id')->nullable();     // untuk header → sub akun
            $table->integer('level')->default(1);                    // 1 = header, 2 = detail
            $table->boolean('is_header')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_locked')->default(false);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tipe', 'is_active']);
            $table->index('parent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akun_keuangan');
    }
};
