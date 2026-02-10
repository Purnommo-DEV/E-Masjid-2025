<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pendaftaran_anak_yatim_dhuafa', function (Blueprint $table) {
            $table->id();
            $table->year('tahun_program')->default(date('Y'));
            $table->text('sumber_informasi')->nullable();
            $table->string('kategori')->comment('yatim_dhuafa atau dhuafa');
            $table->string('nama_lengkap');
            $table->string('nama_panggilan')->nullable();
            $table->integer('umur')->unsigned()->comment('diisi otomatis dari tanggal lahir');
            $table->date('tanggal_lahir');
            $table->string('jenis_kelamin')->comment('L atau P');
            $table->text('alamat');                          // tanpa RT/RW
            $table->string('no_wa')->nullable();             // optional
            $table->string('nama_orang_tua');
            $table->string('pekerjaan_orang_tua')->nullable();
            $table->string('status')->default('baru')->comment('baru, diterima, ditolak, duplikat');
            $table->text('catatan_tambahan')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(
                ['tahun_program', 'kategori', 'status'],
                'penerima_santunan_idx'  // nama custom yang pendek & jelas
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pendaftaran_anak_yatim_dhuafa');
    }
};