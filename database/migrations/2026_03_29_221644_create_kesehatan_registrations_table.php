<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kesehatan_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('nama_lengkap');
            $table->string('no_hp');
            $table->text('alamat')->nullable();
            $table->date('event_date')->index();
            
            $table->boolean('donor_darah')->default(false);
            $table->json('cek_kesehatan')->nullable();
            $table->boolean('cek_mata_katarak')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kesehatan_registrations');
    }
};