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
        Schema::create('saldo_awal_periodes', function (Blueprint $table) {
            $table->id();
            $table->date('periode')->unique();                    // 2025-01-01
            $table->text('keterangan')->nullable();
            $table->enum('status', ['draft', 'locked'])->default('draft');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saldo_awal_periodes');
    }
};
