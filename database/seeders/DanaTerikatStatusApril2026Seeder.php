<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DanaTerikatStatusBulanan;
use App\Models\DanaTerikatPenerima;
use App\Models\DanaTerikatProgram;
use Carbon\Carbon;

class DanaTerikatStatusApril2026Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ============================================
        // 1. AMBIL PROGRAM ZISWAF MRJ 2026
        // ============================================
        $program = DanaTerikatProgram::where('kode_program', 'ZISWAF-2026')->first();

        if (!$program) {
            $this->command->error("❌ Program ZISWAF-2026 tidak ditemukan!");
            $this->command->info("Silakan buat program terlebih dahulu dengan kode ZISWAF-2026");
            return;
        }

        $this->command->info("✅ Program: {$program->nama_program} (ID: {$program->id})");

        // ============================================
        // 2. DATA STATUS APRIL 2026 (LENGKAP 138 DATA)
        // ============================================
        $dataStatus = [
            // ========================================
            // RT 03/06 - Ketua: Wakijo (10 data)
            // ========================================
            ['penerima' => 'Kokom', 'status' => 1],
            ['penerima' => 'Saimah', 'status' => 1],
            ['penerima' => 'Muanah', 'status' => 1],
            ['penerima' => 'Yuyun', 'status' => 1],
            ['penerima' => 'Beno', 'status' => 1],
            ['penerima' => 'Sumani', 'status' => 1],
            ['penerima' => 'Karti', 'status' => 1],
            ['penerima' => 'Rinah', 'status' => 1],
            ['penerima' => 'Ranten', 'status' => 1],
            ['penerima' => 'Tri Sugiawati', 'status' => 1],

            // ========================================
            // RT 04/06 - Ketua: Bu Anas (16 data)
            // ========================================
            ['penerima' => 'Ai Herlina', 'status' => 1],
            ['penerima' => 'Temi', 'status' => 1],
            ['penerima' => 'Saira', 'status' => 1],
            ['penerima' => 'Jamaludin', 'status' => 0],
            ['penerima' => 'Amenih', 'status' => 1],
            ['penerima' => 'Saali', 'status' => 1],
            ['penerima' => 'Purwanti', 'status' => 0],
            ['penerima' => 'Jariyah', 'status' => 1],
            ['penerima' => 'Wakino', 'status' => 1],
            ['penerima' => 'Halimah (Tambahan)', 'status' => 1],
            ['penerima' => 'Hafsah (Tambahan)', 'status' => 0],
            ['penerima' => 'Mus Muslihat', 'status' => 0],
            ['penerima' => 'Subur', 'status' => 0],          // ✅ FIX: Subur RT 04/06
            ['penerima' => 'Ajidin', 'status' => 1],
            ['penerima' => 'Saud', 'status' => 0],
            ['penerima' => 'Maiyah', 'status' => 0],

            // ========================================
            // RT 02/06 - Ketua: Pak Maman (32 data)
            // ========================================
            ['penerima' => 'Sri Murni', 'status' => 1],
            ['penerima' => 'Mulyati', 'status' => 1],
            ['penerima' => 'Khotati', 'status' => 1],
            ['penerima' => 'Karmina', 'status' => 1],
            ['penerima' => 'Samsudin', 'status' => 1],
            ['penerima' => 'Muhclis', 'status' => 1],
            ['penerima' => 'Hidayah', 'status' => 1],
            ['penerima' => 'Atiqah', 'status' => 1],
            ['penerima' => 'Hj. Mapih', 'status' => 1],
            ['penerima' => 'Abdul Rohman', 'status' => 1],
            ['penerima' => 'Nasik', 'status' => 1],
            ['penerima' => 'Supandi', 'status' => 1],
            ['penerima' => 'Sukarti', 'status' => 1],
            ['penerima' => 'Komarih', 'status' => 1],
            ['penerima' => 'Mariam', 'status' => 1],
            ['penerima' => 'Mimin', 'status' => 1],
            ['penerima' => 'Munhari', 'status' => 1],
            ['penerima' => 'Irmayanati', 'status' => 1],
            ['penerima' => 'Suhermi', 'status' => 1],
            ['penerima' => 'Hawiyah', 'status' => 1],
            ['penerima' => 'Ida K Rihodah', 'status' => 1],
            ['penerima' => 'Pak Agus', 'status' => 1],
            ['penerima' => 'Suhendi', 'status' => 0],
            ['penerima' => 'Lisabela', 'status' => 0],
            ['penerima' => 'Joni', 'status' => 0],
            ['penerima' => 'By Redi', 'status' => 0],
            ['penerima' => 'Siti Rahayu (Aryo)', 'status' => 0],
            ['penerima' => 'Sri Sisvitri', 'status' => 0],
            ['penerima' => 'Sarjono', 'status' => 0],
            ['penerima' => 'Ahmad', 'status' => 0],
            ['penerima' => 'Yuliandri', 'status' => 0],
            ['penerima' => 'Hartoyo', 'status' => 1],

            // ========================================
            // RT 01/06 - Ketua: Pak Arif (9 data)
            // ========================================
            ['penerima' => 'Neneng Asmanih', 'status' => 1],
            ['penerima' => 'Fennya atau Tafiq', 'status' => 1],
            ['penerima' => 'Masilah', 'status' => 1],
            ['penerima' => 'Hayatih', 'status' => 1],
            ['penerima' => 'Faridah', 'status' => 1],
            ['penerima' => 'Anih Nurhayati', 'status' => 1],
            ['penerima' => 'Umu Kulsum', 'status' => 1],
            ['penerima' => 'Sapuroh', 'status' => 1],
            ['penerima' => 'Hj. Maswanih', 'status' => 1],

            // ========================================
            // RT 03/04 - Ketua: Pak Wawang (18 data)
            // ========================================
            ['penerima' => 'Satiyem', 'status' => 1],
            ['penerima' => 'Sumiyah', 'status' => 1],
            ['penerima' => 'Sumini', 'status' => 1],
            ['penerima' => 'Mursidi', 'status' => 1],
            ['penerima' => 'Nadiah', 'status' => 1],
            ['penerima' => 'Maesaroh', 'status' => 1],
            ['penerima' => 'Nasim', 'status' => 1],
            ['penerima' => 'Sirmunjiati', 'status' => 1],
            ['penerima' => 'Muhartini', 'status' => 1],
            ['penerima' => 'Munayah', 'status' => 1],
            ['penerima' => 'Tursinah', 'status' => 1],
            ['penerima' => 'Marjuki', 'status' => 1],
            ['penerima' => 'Asli Very', 'status' => 0],
            ['penerima' => 'Asli Ranti', 'status' => 0],
            ['penerima' => 'Nawiah', 'status' => 0],
            ['penerima' => 'Nopiah', 'status' => 0],
            ['penerima' => 'Asmani', 'status' => 0],
            ['penerima' => 'Hamdan', 'status' => 0],

            // ========================================
            // RT 06/07 - Ketua: Pak Parno (7 data)
            // ========================================
            ['penerima' => 'Pak Sarlan', 'status' => 1],
            ['penerima' => 'Romlah', 'status' => 1],
            ['penerima' => 'Reza', 'status' => 1],
            ['penerima' => 'Bi Hamdah', 'status' => 1],
            ['penerima' => 'Mpo Atik', 'status' => 1],
            ['penerima' => 'Mama Mbeng', 'status' => 1],
            ['penerima' => 'Bu Irma', 'status' => 1],

            // ========================================
            // RT 05/04 - Ketua: Bu Susi (18 data)
            // ========================================
            ['penerima' => 'Ramsah', 'status' => 1],
            ['penerima' => 'Luswita', 'status' => 1],
            ['penerima' => 'Subur (Bu Susi)', 'status' => 1],  // ✅ FIX: Subur RT 05/04
            ['penerima' => 'Rohedah', 'status' => 1],
            ['penerima' => 'Misni', 'status' => 1],
            ['penerima' => 'Abdul Rozak', 'status' => 1],
            ['penerima' => 'Yuli', 'status' => 1],
            ['penerima' => 'Nuryani', 'status' => 1],
            ['penerima' => 'Imsiran', 'status' => 1],
            ['penerima' => 'Bapak Esih', 'status' => 0],
            ['penerima' => 'Siti', 'status' => 0],
            ['penerima' => 'Ahyani', 'status' => 0],
            ['penerima' => 'Endang', 'status' => 0],
            ['penerima' => 'Tasiyem', 'status' => 0],
            ['penerima' => 'Munisah', 'status' => 0],
            ['penerima' => 'Dimeroh', 'status' => 0],
            ['penerima' => 'Hasan', 'status' => 0],
            ['penerima' => 'Roisah', 'status' => 0],

            // ========================================
            // TCE - Ketua: Pak Indra (28 data)
            // ========================================
            ['penerima' => 'Tuna Netra Deplu', 'status' => 1],
            ['penerima' => 'Ibu Jamu', 'status' => 1],
            ['penerima' => 'Yanto', 'status' => 1],
            ['penerima' => 'Daus', 'status' => 1],
            ['penerima' => 'Darsono', 'status' => 1],
            ['penerima' => 'Irwan (TK Taman)', 'status' => 1],
            ['penerima' => 'Yani (TK Taman)', 'status' => 1],
            ['penerima' => 'Sofwan (TK Taman)', 'status' => 1],
            ['penerima' => 'Gofur (TK Taman)', 'status' => 1],
            ['penerima' => 'Ubay (TK Taman)', 'status' => 1],
            ['penerima' => 'TK Taman (TK Taman)', 'status' => 1],
            ['penerima' => 'Pian (TK Taman)', 'status' => 1],
            ['penerima' => 'Ibu Moses', 'status' => 1],
            ['penerima' => 'Bu Sarah', 'status' => 1],
            ['penerima' => 'Sapardi', 'status' => 1],
            ['penerima' => 'Saiful', 'status' => 1],
            ['penerima' => 'Ohi Adam', 'status' => 1],
            ['penerima' => 'Muhajir', 'status' => 1],
            ['penerima' => 'Kurtubi', 'status' => 1],
            ['penerima' => 'Suryadi', 'status' => 1],
            ['penerima' => 'Hakim', 'status' => 1],
            ['penerima' => 'Joko', 'status' => 1],
            ['penerima' => 'Ajis', 'status' => 1],
            ['penerima' => 'Nawiri', 'status' => 1],
            ['penerima' => 'Sagiman', 'status' => 1],
            ['penerima' => 'Suradi', 'status' => 1],
            ['penerima' => 'Wahono', 'status' => 1],
            ['penerima' => 'Pak Sur Marbot', 'status' => 1],
            ['penerima' => 'Maman (Ketua RT 02/06)', 'status' => 1],
        ];

        // ============================================
        // 3. VALIDASI JUMLAH DATA
        // ============================================
        $this->command->info("📊 Total data di seeder: " . count($dataStatus));
        
        // Cek total penerima di database
        $totalPenerima = DanaTerikatPenerima::where('program_id', $program->id)
            ->where('tahun_program', 2026)
            ->count();
        
        $this->command->info("📊 Total penerima di database: {$totalPenerima}");

        // ============================================
        // 4. INSERT STATUS BULANAN
        // ============================================
        $this->command->info("📝 Memproses data status April...");
        $this->command->newLine();

        $inserted = 0;
        $skipped = 0;
        $notFound = [];
        $alreadyExists = [];

        foreach ($dataStatus as $index => $row) {
            // Cari penerima berdasarkan nama dan program
            $penerima = DanaTerikatPenerima::where('program_id', $program->id)
                ->where('nama', $row['penerima'])
                ->where('tahun_program', 2026)
                ->first();

            if (!$penerima) {
                $notFound[] = $row['penerima'];
                $skipped++;
                continue;
            }

            // Cek apakah status April sudah ada
            $exists = DanaTerikatStatusBulanan::where('penerima_id', $penerima->id)
                ->where('bulan', 4)
                ->where('tahun', 2026)
                ->exists();

            if ($exists) {
                $alreadyExists[] = $row['penerima'];
                $skipped++;
                continue;
            }

            // Buat status bulanan
            DanaTerikatStatusBulanan::create([
                'program_id' => $program->id,
                'penerima_id' => $penerima->id,
                'tahun' => 2026,
                'bulan' => 4,
                'status_dapat' => $row['status'] ? 1 : 0,
                'alasan_tidak_dapat' => $row['status'] ? null : 'Tidak dapat di bulan April',
                'verified_by' => 'System Seeder',
                'verified_at' => now(),
                'created_by' => 1,
                'updated_by' => 1,
            ]);

            $inserted++;
        }

        // ============================================
        // 5. SUMMARY
        // ============================================
        $this->command->newLine();
        $this->command->info("==========================================");
        $this->command->info("✅ SEEDER STATUS APRIL 2026 SELESAI!");
        $this->command->info("==========================================");
        $this->command->info("📊 Total data diproses: " . count($dataStatus));
        $this->command->info("✅ Berhasil ditambahkan: {$inserted}");
        $this->command->info("⏭️  Dilewati (total): {$skipped}");
        $this->command->info("📁 Program: {$program->nama_program}");
        $this->command->info("📅 Bulan: April 2026");
        $this->command->info("==========================================");

        // Tampilkan detail yang di-skip
        if (!empty($notFound)) {
            $this->command->newLine();
            $this->command->warn("⚠️ DATA TIDAK DITEMUKAN DI DATABASE (" . count($notFound) . "):");
            foreach ($notFound as $nama) {
                $this->command->warn("   • {$nama}");
            }
        }

        if (!empty($alreadyExists)) {
            $this->command->newLine();
            $this->command->info("ℹ️ DATA SUDAH ADA SEBELUMNYA (" . count($alreadyExists) . "):");
            foreach ($alreadyExists as $nama) {
                $this->command->info("   • {$nama}");
            }
        }

        // Tampilkan total status di database
        $totalStatus = DanaTerikatStatusBulanan::where('program_id', $program->id)
            ->where('bulan', 4)
            ->where('tahun', 2026)
            ->count();

        $this->command->newLine();
        $this->command->info("📊 Total status April di database: {$totalStatus}");
        $this->command->info("==========================================");
    }
}