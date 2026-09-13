<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santunan_persons', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('nama_lengkap', 150);
            $table->string('normalized_name', 150);
            $table->string('nama_panggilan', 60)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->enum('jenis_kelamin', ['L', 'P']);
            $table->timestamps();

            $table->index('normalized_name', 'santunan_person_name_ix');
            $table->index(['tanggal_lahir', 'jenis_kelamin'], 'santunan_person_birth_gender_ix');
        });

        Schema::create('santunan_participations', function (Blueprint $table): void {
            $table->id();
            $table->foreignUuid('person_id')->constrained('santunan_persons')->restrictOnDelete();
            $table->year('tahun_program');
            $table->enum('kategori', ['dhuafa', 'yatim_dhuafa']);
            $table->text('sumber_informasi')->nullable();
            $table->unsignedInteger('umur');
            $table->enum('umur_satuan', ['tahun', 'bulan', 'hari']);
            $table->text('alamat');
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('nama_rt', 150)->nullable();
            $table->string('nama_orang_tua', 255);
            $table->string('pekerjaan_orang_tua', 255)->nullable();
            $table->string('no_wa', 255)->nullable();
            $table->string('status', 255)->default('baru');
            $table->text('catatan_tambahan')->nullable();
            $table->text('catatan_admin')->nullable();
            $table->string('ip_address', 255)->nullable();
            $table->unsignedBigInteger('legacy_registration_id')->nullable();
            $table->timestamps();

            $table->foreign('legacy_registration_id', 'santunan_participation_legacy_fk')
                ->references('id')
                ->on('pendaftaran_anak_yatim_dhuafa')
                ->restrictOnDelete();
            $table->unique(['person_id', 'tahun_program'], 'santunan_person_year_uq');
            $table->unique('legacy_registration_id', 'santunan_legacy_registration_uq');
            $table->index(['tahun_program', 'kategori', 'status'], 'santunan_year_category_status_ix');
            $table->index(['tahun_program', 'rw', 'rt'], 'santunan_year_region_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santunan_participations');
        Schema::dropIfExists('santunan_persons');
    }
};
