<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('santunan_ramadhan_age_settings', function (Blueprint $table): void {
            $table->id();
            $table->year('tahun_program')->unique();
            $table->string('period_key', 40)->unique();
            $table->string('periode_ramadhan', 40)->unique();
            $table->date('tanggal_acuan');
            $table->unsignedTinyInteger('batas_tahun')->default(13);
            $table->unsignedTinyInteger('batas_bulan')->default(11);
            $table->timestamps();
        });

        Schema::create('santunan_ramadhan_age_setting_histories', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('setting_id')->constrained('santunan_ramadhan_age_settings')->restrictOnDelete();
            $table->year('tahun_program');
            $table->string('periode_ramadhan', 40);
            $table->date('tanggal_acuan_sebelumnya')->nullable();
            $table->date('tanggal_acuan_baru');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_label', 150)->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('changed_at');
            $table->index(['tahun_program', 'changed_at'], 'santunan_age_history_year_changed_ix');
        });

        DB::table('santunan_ramadhan_age_settings')->insert([
            'tahun_program' => 2027,
            'period_key' => 'ramadhan-1448',
            'periode_ramadhan' => 'Ramadhan 1448 H',
            'tanggal_acuan' => '2027-02-08',
            'batas_tahun' => 13,
            'batas_bulan' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('santunan_ramadhan_age_setting_histories');
        Schema::dropIfExists('santunan_ramadhan_age_settings');
    }
};
