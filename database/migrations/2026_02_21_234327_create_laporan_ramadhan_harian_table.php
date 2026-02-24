<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_ramadhan_harian', function (Blueprint $table) {
            $table->id();
            $table->integer('malam_ke')->unique(); // malam ke-1 sampai 30
            $table->date('tanggal');               // tanggal masehi/hijriah

            // Link ke jadwal imam malam itu (opsional, foreign key)
            $table->foreignId('jadwal_imam_id')->nullable()->constrained('jadwal_imam_tarawih')->onDelete('set null');

            // Infaq Ramadhan
            $table->decimal('infaq_ramadan_saldo_kemarin', 15, 2)->default(0);
            $table->decimal('infaq_ramadan_penerimaan_tromol', 15, 2)->default(0);
            $table->decimal('infaq_ramadan_pengeluaran_operasional', 15, 2)->default(0);
            $table->decimal('infaq_ramadan_saldo_sekarang', 15, 2)->default(0); // bisa dihitung otomatis

            // Infaq Iftor (bisa pakai json untuk detail donor/pengeluaran)
            $table->decimal('ifthor_saldo_kemarin', 15, 2)->default(0);
            $table->json('ifthor_penerimaan_detail')->nullable(); // array [{dari:.., nominal:..}]
            $table->json('ifthor_pengeluaran_detail')->nullable(); // array [{untuk:.., nominal:..}]
            $table->decimal('ifthor_saldo_sekarang', 15, 2)->default(0);

            // Santunan Anak Yatim
            $table->integer('santunan_yatim_target_anak')->default(200);
            $table->decimal('santunan_yatim_target_nominal', 15, 2)->default(70000000);
            $table->decimal('santunan_yatim_terkumpul_kemarin', 15, 2)->default(0);
            $table->json('santunan_yatim_penerimaan_hari_ini')->nullable();

            // Paket Sembako
            $table->integer('paket_sembako_target')->default(75);
            $table->decimal('paket_sembako_target_nominal', 15, 2)->default(15575000);
            $table->decimal('paket_sembako_terkumpul_kemarin', 15, 2)->default(0);
            $table->json('paket_sembako_penerimaan_hari_ini')->nullable();

            // ZISWAF (bisa static atau update tahunan)
            $table->decimal('zakat_fitrah_per_jiwa', 10, 0)->default(47000);
            $table->decimal('fidyah_per_hari', 10, 0)->default(50000);

            // Lomba & Gebyar (tanggal + infaq)
            $table->date('lomba_anak_tanggal')->nullable();
            $table->decimal('lomba_anak_infaq_terkumpul', 15, 2)->default(0);
            $table->date('gebyar_anak_tanggal')->nullable();
            $table->decimal('gebyar_anak_infaq_terkumpul', 15, 2)->default(0);

            // Sumbangan Barang (json array)
            $table->json('sumbangan_barang')->nullable(); // [{barang:.., jumlah:.., dari:..}]

            // Pengingat Adab Sholat (textarea)
            $table->text('pengingat_adab')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_ramadhan_harian');
    }
};