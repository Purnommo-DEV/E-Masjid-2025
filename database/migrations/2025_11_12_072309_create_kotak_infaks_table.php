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
        Schema::create('kotak_infaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jenis_kotak_id')->constrained('jenis_kotak_infaks');
            $table->date('tanggal');
            $table->decimal('total', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kotak_infaks');
    }
};
