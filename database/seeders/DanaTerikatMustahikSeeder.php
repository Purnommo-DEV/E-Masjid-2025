<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DanaTerikatPenerima;
use App\Models\DanaTerikatProgram;
use App\Models\DanaTerikatReferensi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DanaTerikatMustahikSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================
        // 1. AMBIL PROGRAM ZISWAF MRJ 2026 YANG SUDAH ADA
        // ============================================
        $program = DanaTerikatProgram::where('kode_program', 'ZISWAF-2026')->first();

        if (!$program) {
            $this->command->error("❌ Program ZISWAF-2026 tidak ditemukan!");
            $this->command->info("Silakan buat program terlebih dahulu dengan kode ZISWAF-2026");
            return;
        }

        $this->command->info("✅ Program ZISWAF MRJ 2026 ditemukan (ID: {$program->id})");

        // ============================================
        // 2. BUAT REFERENSI UNTUK SETIAP KETUA RT
        // ============================================
        $referensiData = [
            [
                'nama' => 'Wakijo (RT 03/06)',
                'warna' => '#fef3c7', // Kuning
            ],
            [
                'nama' => 'Bu Anas (RT 04/06)',
                'warna' => '#dbeafe', // Biru muda
            ],
            [
                'nama' => 'Pak Maman (RT 02/06)',
                'warna' => '#dcfce7', // Hijau muda
            ],
            [
                'nama' => 'Pak Arif (RT 01/06)',
                'warna' => '#fce7f3', // Pink muda
            ],
            [
                'nama' => 'Pak Wawang (RT 03/04)',
                'warna' => '#ffedd5', // Orange muda
            ],
            [
                'nama' => 'Pak Parno (RT 06/07)',
                'warna' => '#e0e7ff', // Indigo muda
            ],
            [
                'nama' => 'Bu Susi (RT 05/04)',
                'warna' => '#fce4ec', // Merah muda
            ],
            [
                'nama' => 'Pak Indra (TCE)',
                'warna' => '#f3e8ff', // Ungu muda
            ],
        ];

        $referensiMap = [];
        foreach ($referensiData as $ref) {
            $referensi = DanaTerikatReferensi::firstOrCreate(
                ['nama' => $ref['nama']],
                ['warna' => $ref['warna']]
            );
            $referensiMap[$ref['nama']] = $referensi->id;
            $this->command->info("✅ Referensi: {$ref['nama']} (ID: {$referensi->id})");
        }

        // ============================================
        // 3. DATA MUSTAHIK (SEMUA DHUAFA)
        // ============================================
        $data = [
            // ===== RT 03/06 - Ketua: Wakijo (Referensi: Wakijo (RT 03/06)) =====
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Kokom',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Saimah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Muanah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Yuyun',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Beno',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Sumani',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Karti',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Rinah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Ranten',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '06',
                'nama_rt' => 'Wakijo Mei 2025',
                'referensi_nama' => 'Wakijo (RT 03/06)',
                'penerima' => 'Tri Sugiawati',
                'status' => 1,
            ],

            // ===== RT 04/06 - Ketua: Bu Anas (Referensi: Bu Anas (RT 04/06)) =====
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Ai Herlina',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Temi',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Saira',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Jamaludin',
                'status' => 0,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Amenih',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Saali',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Purwanti',
                'status' => 0,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Jariyah',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Wakino',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Halimah (Tambahan)',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Hafsah (Tambahan)',
                'status' => 0,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Mus Muslihat',
                'status' => 0,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Subur',
                'status' => 0,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Ajidin',
                'status' => 1,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Saud',
                'status' => 0,
            ],
            [
                'rt' => '04',
                'rw' => '06',
                'nama_rt' => 'Bu Anas',
                'referensi_nama' => 'Bu Anas (RT 04/06)',
                'penerima' => 'Maiyah',
                'status' => 0,
            ],

            // ===== RT 02/06 - Ketua: Pak Maman (Referensi: Pak Maman (RT 02/06)) =====
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Sri Murni',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Mulyati',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Khotati',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Karmina',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Samsudin',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Muhclis',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Hidayah',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Atiqah',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Hj. Mapih',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Abdul Rohman',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Nasik',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Supandi',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Sukarti',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Komarih',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Mariam',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Mimin',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Munhari',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Irmayanati',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Suhermi',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Hawiyah',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Ida K Rihodah',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Pak Agus',
                'status' => 1,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Suhendi',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Lisabela',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Joni',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'By Redi',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Siti Rahayu (Aryo)',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Sri Sisvitri',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Sarjono',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Ahmad',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Yuliandri',
                'status' => 0,
            ],
            [
                'rt' => '02',
                'rw' => '06',
                'nama_rt' => 'Pak Maman',
                'referensi_nama' => 'Pak Maman (RT 02/06)',
                'penerima' => 'Hartoyo',
                'status' => 1,
            ],

            // ===== RT 01/06 - Ketua: Pak Arif (Referensi: Pak Arif (RT 01/06)) =====
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Neneng Asmanih',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Fennya atau Tafiq',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Masilah',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Hayatih',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Faridah',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Anih Nurhayati',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Umu Kulsum',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Sapuroh',
                'status' => 1,
            ],
            [
                'rt' => '01',
                'rw' => '06',
                'nama_rt' => 'Pak Arif',
                'referensi_nama' => 'Pak Arif (RT 01/06)',
                'penerima' => 'Hj. Maswanih',
                'status' => 1,
            ],

            // ===== RT 03/04 - Ketua: Pak Wawang (Referensi: Pak Wawang (RT 03/04)) =====
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Satiyem',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Sumiyah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Sumini',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Mursidi',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Nadiah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Maesaroh',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Nasim',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Sirmunjiati',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Muhartini',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Munayah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Tursinah',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Marjuki',
                'status' => 1,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Asli Very',
                'status' => 0,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Asli Ranti',
                'status' => 0,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Nawiah',
                'status' => 0,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Nopiah',
                'status' => 0,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Asmani',
                'status' => 0,
            ],
            [
                'rt' => '03',
                'rw' => '04',
                'nama_rt' => 'Pak Wawang',
                'referensi_nama' => 'Pak Wawang (RT 03/04)',
                'penerima' => 'Hamdan',
                'status' => 0,
            ],

            // ===== RT 06/07 - Ketua: Pak Parno (Referensi: Pak Parno (RT 06/07)) =====
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Pak Sarlan',
                'status' => 1,
            ],
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Romlah',
                'status' => 1,
            ],
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Reza',
                'status' => 1,
            ],
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Bi Hamdah',
                'status' => 1,
            ],
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Mpo Atik',
                'status' => 1,
            ],
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Mama Mbeng',
                'status' => 1,
            ],
            [
                'rt' => '06',
                'rw' => '07',
                'nama_rt' => 'Pak Parno',
                'referensi_nama' => 'Pak Parno (RT 06/07)',
                'penerima' => 'Bu Irma',
                'status' => 1,
            ],

            // ===== RT 05/04 - Ketua: Bu Susi (Referensi: Bu Susi (RT 05/04)) =====
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Ramsah',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Luswita',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Subur (Bu Susi)',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Rohedah',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Misni',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Abdul Rozak',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Yuli',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Nuryani',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Imsiran',
                'status' => 1,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Bapak Esih',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Siti',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Ahyani',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Endang',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Tasiyem',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Munisah',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Dimeroh',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Hasan',
                'status' => 0,
            ],
            [
                'rt' => '05',
                'rw' => '04',
                'nama_rt' => 'Bu Susi',
                'referensi_nama' => 'Bu Susi (RT 05/04)',
                'penerima' => 'Roisah',
                'status' => 0,
            ],

            // ===== TCE - Ketua: Pak Indra (Referensi: Pak Indra (TCE)) =====
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Tuna Netra Deplu',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Ibu Jamu',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Yanto',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Daus',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Darsono',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Irwan (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Yani (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Sofwan (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Gofur (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Ubay (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'TK Taman (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Pian (TK Taman)',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Ibu Moses',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Bu Sarah',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Sapardi',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Saiful',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Ohi Adam',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Muhajir',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Kurtubi',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Suryadi',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Hakim',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Joko',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Ajis',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Nawiri',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Sagiman',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Suradi',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Wahono',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Pak Sur Marbot',
                'status' => 1,
            ],
            [
                'rt' => '00',
                'rw' => '00',
                'nama_rt' => 'Pak Indra',
                'referensi_nama' => 'Pak Indra (TCE)',
                'penerima' => 'Maman (Ketua RT 02/06)',
                'status' => 1
            ]
        ];

        // ============================================
        // 4. INSERT DATA
        // ============================================
        $this->command->info("📊 Memproses " . count($data) . " data mustahik...");

        $inserted = 0;
        $skipped = 0;

        foreach ($data as $row) {
            // Ambil referensi_id dari map
            $referensiId = $referensiMap[$row['referensi_nama']] ?? null;

            if (!$referensiId) {
                $this->command->warn("⚠️ Referensi tidak ditemukan: {$row['referensi_nama']}");
                continue;
            }

            // Cek apakah data sudah ada (untuk menghindari duplikat)
            $exists = DanaTerikatPenerima::where('program_id', $program->id)
                ->where('nama', $row['penerima'])
                ->where('rt', $row['rt'])
                ->where('rw', $row['rw'])
                ->where('tahun_program', 2026)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            DanaTerikatPenerima::create([
                'program_id' => $program->id,
                'tahun_program' => 2026,
                'nama' => $row['penerima'],
                'no_hp' => null,
                'alamat' => '-',
                'kategori' => 'dhuafa', // SEMUA DHUAFA
                'referensi_id' => $referensiId,
                'tanggal_lahir' => null,
                'status_yatim' => 0,
                'umur' => null,
                'nama_rt' => $row['nama_rt'],
                'rt' => $row['rt'],
                'rw' => $row['rw'],
                'nominal_bulanan' => 100000, // Default 100.000
                'status_aktif' => $row['status'] ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $inserted++;
        }

        // ============================================
        // 5. SUMMARY
        // ============================================
        $this->command->newLine();
        $this->command->info("==========================================");
        $this->command->info("✅ SEEDER ZISWAF MRJ 2026 SELESAI!");
        $this->command->info("==========================================");
        $this->command->info("📊 Total data diproses: " . count($data));
        $this->command->info("✅ Berhasil ditambahkan: {$inserted}");
        $this->command->info("⏭️  Dilewati (duplikat): {$skipped}");
        $this->command->info("📁 Program: {$program->nama_program} (ID: {$program->id})");
        $this->command->info("📁 Kategori: Dhuafa (semua penerima)");
        $this->command->info("📁 Referensi terdaftar: " . count($referensiMap) . " Ketua RT");
        $this->command->info("==========================================");
        
        // Tampilkan detail referensi
        $this->command->newLine();
        $this->command->info("📋 DAFTAR REFERENSI (KETUA RT):");
        foreach ($referensiMap as $nama => $id) {
            $this->command->info("   • {$nama} (ID: {$id})");
        }
        $this->command->info("==========================================");
    }
}