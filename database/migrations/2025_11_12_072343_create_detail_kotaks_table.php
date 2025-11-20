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
        Schema::create('detail_kotaks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kotak_id')->constrained('kotak_infaks')->onDelete('cascade');
            $table->integer('nominal');
            $table->integer('jumlah_lembar');
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('detail_kotak');
    }
};
