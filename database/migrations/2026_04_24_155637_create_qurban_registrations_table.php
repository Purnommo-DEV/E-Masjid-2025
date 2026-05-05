<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qurban_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code', 50);
            $table->foreignId('qurban_id')->constrained('qurbans')->onDelete('restrict');
            $table->string('kode_registrasi', 50)->unique();
            $table->string('nama_lengkap', 255);
            $table->string('email', 255)->nullable();
            $table->string('telepon', 20);
            $table->text('alamat')->nullable();
            $table->integer('jumlah_share')->default(1);
            $table->bigInteger('total_harga');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->text('catatan')->nullable();
            $table->dateTime('confirmed_at')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamps();
            
            $table->index(['masjid_code', 'status']);
            $table->index('kode_registrasi');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('qurban_registrations');
    }
};