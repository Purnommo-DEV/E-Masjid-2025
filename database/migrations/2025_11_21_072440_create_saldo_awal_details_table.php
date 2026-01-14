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
        Schema::create('saldo_awal_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saldo_awal_periode_id')->constrained('saldo_awal_periodes')->onDelete('cascade');
            $table->foreignId('akun_id')->constrained('akun_keuangan')->onDelete('cascade');
            $table->decimal('jumlah', 20, 2)->default(0);
            $table->timestamps();

            $table->unique(['saldo_awal_periode_id', 'akun_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_awal_details');
    }
};
