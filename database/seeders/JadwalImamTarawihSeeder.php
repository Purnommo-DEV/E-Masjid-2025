<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class JadwalImamTarawihSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            [1,'2026-02-18','Ustadz Muchlisin Aziz, MA','Ustadz Muchlisin Aziz, MA','Syukuri Nikmat Ramadhan'],
            [2,'2026-02-19','Ustadz Nur Achmad','Ustadz Nur Achmad','Puasa Menyucikan Jiwa, Menyehatkan Raga'],
            [3,'2026-02-20','Ustadz Rahmat Hidayat Lubis','Ustadz Rahmat Hidayat Lubis','Semangat Ibadah, Ikuti Pola Makan Sehat Rasulullah'],
            [4,'2026-02-21','Ustadz Iwan Santoso','Ustadz Suparlan','Al Quran sebagai Obat Segala Penyakit (Asy-Syifa’), Bagaimana Implementasinya?'],
            [5,'2026-02-22','Ustadz Dr. Fudhoil Rahman, MA','Ustadz Dr. Fudhoil Rahman, MA','Kiat Investasi Dunia Akhirat: Zakat,Infak dan Sedekah'],
            [6,'2026-02-23','Ustadz Arfan Abbas','Ustadz Arfan Abbas','Urgensi dan Hikmah Shalat Berjamaah'],
            [7,'2026-02-24','Ustadz Muntohar','Ustadz Muntohar','Keutamaan Pemuda dalam Memakmurkan Masjid'],
            [8,'2026-02-25','Ustadz Zakaria Lubis','Ustadz Zakaria Lubis','Ramadhan sebagai Madrasah Keluarga Muslim'],
            [9,'2026-02-26','Ustadz. Dr. Canra Krisna Jaya Lubis, MA.Hum','Ustadz. Dr. Canra Krisna Jaya Lubis, MA.Hum','Mendidik Anak Cinta Ramadhan Sejak Dini: Investasi Spiritual Tak Ternilai'],
            [10,'2026-02-27','Ustadz Umar Farouk, Lc, MA','Ustadz Umar Farouk, Lc, MA','Optimalkan Ramadhan sebagai Momentum Hijrah'],
            [11,'2026-02-28','Ustadz Dr Sukron Ma\'mun, MA','Ustadz Dr Sukron Ma\'mun, MA','Dampak Media Sosial terhadap Ibadah Ramadhan'],
            [12,'2026-03-01','Ustadz Dr Rosyidin Efendi, MA','Ustadz Isa Ansori','Maksimalkan Ibadah di Tengah Kesibukan Pekerjaan'],
            [13,'2026-03-02','Ustadz Nurul Fajri','Ustadz Nurul Fajri','Islam dan Lingkungan: Menjaga Amanah Allah di Bumi'],
            [14,'2026-03-03','Ustadz Adhi Susanto','Ustadz Adhi Susanto','Menyiasati Jebakan Setan dan Hawa Nafsu'],
            [15,'2026-03-04','Ustadz Mohamad Taufik, MA','Ustadz Mohamad Taufik, MA','Keutamaan dan Adab-adab I’tikaf'],
            [16,'2026-03-05','Ustadz Agil Pradana, Lc','Ustadz Agil Pradana, Lc','Memaknai Al-Quran sebagai Mujizat Abadi: Penuntun Jalan Menuju Kebahagian Sejati'],
            [17,'2026-03-06','Ustadz Sulaiman, Lc','Ustadz Sulaiman, Lc','Keutamaan Memberi Makan Orang Berpuasa'],
            [18,'2026-03-07','Ustad Hasbi Abi Zaky','Ustad Hasbi Abi Zaky','Menuju Jannah dengan Amalan-amalan Kecil Berkelanjutan'],
            [19,'2026-03-08','Ustadz Ahmad Fathoni, MA','Ustadz Ahmad Fathoni, MA','Zakat Membersihkan Harta dan Jiwa'],
            [20,'2026-03-09','Ustadz Zulfa Hendra','Ustadz Zulfa Hendra','Menjaga Produktivitas Kerja di Bulan Ramadhan'],
            [21,'2026-03-10','Ustadz Abdul Rozik','Ustadz Abdul Rozik','Meraih Cinta Ilahi dengan Banyak Berzikir'],
            [22,'2026-03-11','Ustadz Burhan Ahmad Fauzan','Ustadz Burhan Ahmad Fauzan','Sedekah dan Berbagi: Investasi yang Tidak Pernah Rugi'],
            [23,'2026-03-12','Ustadz Abdul Rohman Rojali, Lc','Ustadz Abdul Rohman Rojali, Lc','Menjaga Kebersihan Lingkungan: Dari Iman Jadi Aksi Nyata'],
            [24,'2026-03-13','Ustad Imam Taufik','Ustad Imam Taufik','Membangun Kedekatan dengan Sang Pencipta dalam Keheningan Malam'],
            [25,'2026-03-14','Ustadz H. Muhammad Shaufi El Mahbub (Gus Musa)','Ustadz Suparlan/Ustadz Isa Ansori','Perbaiki Ibadahmu, Maka Allah Perbaiki Hidupmu'],
            [26,'2026-03-15','Ustadz Muhammad Nuhri, MA','Ustadz Muhammad Nuhri, MA','Ukhuwwah Islamiyah: Dari Perbedaan untuk Persatuan'],
            [27,'2026-03-16','Ustadz Iwan Zawawi','Ustadz Iwan Zawawi','Tanda-tanda Puasa yang Diterima oleh Allah'],
            [28,'2026-03-17','Ustadz Dr. Suheri Mukti','Ustadz Dr. Suheri Mukti','Kiat-kiat Menjaga Istiqomah Amalan Pasca Ramadhan'],
            [29,'2026-03-18','Ustadz Nasrun Sodikun','Ustadz Nasrun Sodikun','Menjaga Hubungan dengan Allah dan Hubungan Sesama Manusia Setelah Ramadhan'],
        ];

        foreach ($data as $d) {
            DB::table('jadwal_imam_tarawih')->insert([
                'malam_ke' => $d[0],
                'tanggal' => $d[1],
                'imam_nama' => $d[2],
                'penceramah_nama' => $d[3],
                'tema_tausiyah' => $d[4],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}