<?php
// database/seeders/QurbanRegistrationSeeder.php

namespace Database\Seeders;

use App\Models\Qurban;
use App\Models\QurbanRegistration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QurbanRegistrationSeeder extends Seeder
{
    public function run()
    {
        // Data dummy registrasi qurban
        $registrations = [
            [
                'nama_lengkap' => 'Ahmad Fahrurrozi',
                'email' => 'ahmad.fahrurrozi@example.com',
                'telepon' => '081234567890',
                'alamat' => 'Jl. Melati No. 12, Taman Cipulir Estate, Jakarta Selatan',
                'jumlah_share' => 1,
                'catatan' => 'Qurban untuk almarhum ayahanda',
                'status' => 'pending',
                'qurban_jenis' => 'kambing',
            ],
            [
                'nama_lengkap' => 'Siti Nurhaliza',
                'email' => 'siti.nurhaliza@example.com',
                'telepon' => '081298765432',
                'alamat' => 'Jl. Anggrek Blok B No. 5, Taman Cipulir Estate, Jakarta Selatan',
                'jumlah_share' => 2,
                'catatan' => 'Patungan dengan 1 orang teman',
                'status' => 'confirmed',
                'qurban_jenis' => 'sapi',
            ],
            [
                'nama_lengkap' => 'Bambang Supriyadi',
                'email' => 'bambang.supriyadi@example.com',
                'telepon' => '085712345678',
                'alamat' => 'Jl. Mawar Raya No. 8, Taman Cipulir Estate, Jakarta Selatan',
                'jumlah_share' => 1,
                'catatan' => 'Qurban untuk keluarga',
                'status' => 'completed',
                'qurban_jenis' => 'kambing',
            ],
            [
                'nama_lengkap' => 'Dewi Sartika',
                'email' => 'dewi.sartika@example.com',
                'telepon' => '081387654321',
                'alamat' => 'Jl. Kenanga Blok C No. 3, Taman Cipulir Estate, Jakarta Selatan',
                'jumlah_share' => 3,
                'catatan' => 'Patungan dengan 2 orang saudara',
                'status' => 'confirmed',
                'qurban_jenis' => 'sapi',
            ],
            [
                'nama_lengkap' => 'Hendra Wijaya',
                'email' => 'hendra.wijaya@example.com',
                'telepon' => '089876543210',
                'alamat' => 'Jl. Flamboyan No. 15, Taman Cipulir Estate, Jakarta Selatan',
                'jumlah_share' => 1,
                'catatan' => 'Qurban untuk yatim piatu',
                'status' => 'cancelled',
                'qurban_jenis' => 'kambing',
            ],
        ];

        // Ambil data qurban dari database berdasarkan jenis hewan
        foreach ($registrations as $reg) {
            // Cari qurban_id berdasarkan jenis hewan
            $qurban = Qurban::where('jenis_hewan', $reg['qurban_jenis'])
                ->where('stok', '>', 0)
                ->where('is_active', true)
                ->orderBy('id')
                ->first();

            if ($qurban) {
                $totalHarga = $qurban->harga * $reg['jumlah_share'];

                // Kurangi stok jika status sudah confirmed atau completed
                if (in_array($reg['status'], ['confirmed', 'completed'])) {
                    $qurban->decrement('stok', $reg['jumlah_share']);
                }

                QurbanRegistration::create([
                    'masjid_code' => masjid(),
                    'qurban_id' => $qurban->id,
                    'nama_lengkap' => $reg['nama_lengkap'],
                    'email' => $reg['email'],
                    'telepon' => $reg['telepon'],
                    'alamat' => $reg['alamat'],
                    'jumlah_share' => $reg['jumlah_share'],
                    'total_harga' => $totalHarga,
                    'status' => $reg['status'],
                    'catatan' => $reg['catatan'],
                    'confirmed_at' => in_array($reg['status'], ['confirmed', 'completed']) ? now() : null,
                    'confirmed_by' => in_array($reg['status'], ['confirmed', 'completed']) ? 1 : null,
                    'created_at' => now()->subDays(rand(1, 30)),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->command->info('5 data registrasi qurban dummy berhasil ditambahkan!');
    }
}