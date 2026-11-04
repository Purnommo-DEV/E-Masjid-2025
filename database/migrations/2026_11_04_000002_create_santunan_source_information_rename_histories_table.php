<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santunan_source_information_rename_histories', function (Blueprint $table): void {
            $table->id();
            $table->text('nama_lama');
            $table->text('nama_baru');
            $table->year('tahun_program')->nullable();
            $table->boolean('semua_tahun')->default(false);
            $table->unsignedInteger('jumlah_terdampak');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_label', 150)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('changed_at');
            $table->index(['tahun_program', 'changed_at'], 'santunan_source_rename_year_changed_ix');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('santunan_source_information_rename_histories');
    }
};
