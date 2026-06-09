<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('jamaah', function (Blueprint $table) {
            $table->id();
            
            $table->string('nama_lengkap', 150);
            $table->string('nomor_whatsapp', 20)->nullable();
            $table->string('alamat_singkat', 255)->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->enum('jenis_kelamin', ['L', 'P'])->nullable();
            $table->date('tanggal_lahir')->nullable();
            
            $table->boolean('bersedia_info_wa')->default(true);
            
            $table->boolean('minat_kajian_rutin')->default(false);
            $table->boolean('minat_tpa_tpq')->default(false);
            $table->boolean('minat_remaja_masjid')->default(false);
            $table->boolean('minat_kegiatan_sosial')->default(false);
            $table->boolean('minat_zakat_sedekah')->default(false);
            $table->boolean('minat_qurban')->default(false);
            $table->boolean('minat_kerelawanan')->default(false);
            $table->boolean('minat_lainnya')->default(false);
            $table->string('minat_lainnya_text', 255)->nullable();
            
            $table->text('aspirasi')->nullable();
            
            $table->string('ip_address', 45)->nullable();
            $table->year('tahun_pendataan')->default(now()->year);
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('tahun_pendataan');
            $table->index('nomor_whatsapp');
            $table->index('nama_lengkap');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jamaah');
    }
};