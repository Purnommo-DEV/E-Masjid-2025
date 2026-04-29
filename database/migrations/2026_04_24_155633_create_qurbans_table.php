<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('qurbans', function (Blueprint $table) {
            $table->id();
            $table->string('masjid_code', 50);
            $table->enum('jenis_hewan', ['sapi', 'kambing', 'kerbau'])->default('kambing');
            $table->string('jenis_icon', 10)->default('🐐');
            $table->bigInteger('harga')->default(0);
            $table->bigInteger('harga_full')->nullable();
            $table->integer('max_share')->default(1);
            $table->integer('stok')->default(0);
            $table->integer('berat_min')->nullable();
            $table->integer('berat_max')->nullable();
            $table->string('deskripsi_singkat')->nullable();
            $table->text('deskripsi_lengkap')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('urutan')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index(['masjid_code', 'is_active', 'urutan']);
            $table->index('jenis_hewan');
            $table->index('stok');
        });
    }

    public function down()
    {
        Schema::dropIfExists('qurbans');
    }
};