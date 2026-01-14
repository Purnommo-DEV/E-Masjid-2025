<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MasjidFullDummySeeder extends Seeder
{
    public function run(): void
    {
        $userId = 1;
        $now    = Carbon::now();

        if (!DB::table('users')->where('id', $userId)->exists()) {
            $this->command->error("User ID 1 tidak ditemukan!");
            return;
        }

        $this->command->info("Mengisi data dummy masjid – 12 data per tabel + kategori unik");

        // ===================================================================
        // 1. KATEGORI – SLUG UNIK: nama-tipe (contoh: ramadhan-berita)
        // ===================================================================
        $kategoriList = [
            ['Pengumuman', 'berita'],
            ['Ramadhan', 'berita'],
            ['Ziswaf', 'berita'],
            ['Kajian Rutin', 'berita'],
            ['Kebersihan Masjid', 'berita'],
            ['Renovasi', 'berita'],

            ['Kegiatan Ramadhan', 'galeri'],
            ['Santunan Anak Yatim', 'galeri'],
            ['Hari Besar Islam', 'galeri'],
            ['Lomba', 'galeri'],
            ['Jumatan', 'galeri'],

            ['Acara Rutin', 'acara'],
            ['Ramadhan', 'acara'],
            ['Hari Besar Islam', 'acara'],
            ['Pelatihan', 'acara'],
        ];

        $kategoriIds = ['berita' => [], 'galeri' => [], 'acara' => []];

        foreach ($kategoriList as $k) {
            $nama = $k[0];
            $tipe = $k[1];
            $slug = Str::slug($nama . '-' . $tipe); // <-- UNIK SELAMANYA!

            $exists = DB::table('kategori')->where('slug', $slug)->exists();

            if (!$exists) {
                $id = DB::table('kategori')->insertGetId([
                    'nama'       => $nama,
                    'slug'       => $slug,
                    'warna'      => '#' . substr(md5($nama . $tipe), 0, 6),
                    'tipe'       => $tipe,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $id = DB::table('kategori')->where('slug', $slug)->value('id');
            }

            $kategoriIds[$tipe][$nama] = $id;
        }

        // ===================================================================
        // 2–5. SEMUA DATA (Berita, Galeri, Acara, Pengumuman)
        // ===================================================================
        $this->seedBerita($kategoriIds, $userId, $now);
        $this->seedGaleri($kategoriIds, $userId, $now);
        $this->seedAcara($kategoriIds, $userId, $now);
        $this->seedPengumuman($now);

        // ===================================================================
        // 6. BANNERS (BARU)
        // ===================================================================
        $this->seedBanners($now);

        $this->command->info("SELESAI 100%! Semua data masuk tanpa error slug duplicate!");
        $this->command->info("Kategori 'Ramadhan' bisa dipakai di berita, galeri, dan acara → slug tetap unik!");
    }

    private function seedBerita($kategoriIds, $userId, $now)
    {
        $beritas = [
            "Pengumuman Tarawih Perdana Ramadhan 1447 H"     => ['Pengumuman', 'Ramadhan'],
            "Jadwal Kajian Rutin Bulan Desember 2025"        => ['Kajian Rutin'],
            "Laporan Zakat Fitrah 1446 H Tersalurkan 100%"   => ['Ziswaf'],
            "Gotong Royong Bersih Masjid Minggu Ini"         => ['Kebersihan Masjid'],
            "Renovasi Ruang Wudhu Putri Tahap 2"             => ['Renovasi'],
            "Penerimaan Santri Baru TPQ 2026"                => ['Pengumuman'],
            "Lomba Adzan & Tahfidz Anak"                     => ['Pengumuman'],
            "Infaq Jumat Berkah: Rp 28 Juta"                 => ['Ziswaf'],
            "Kajian Bulanan Ustadz Ahmad Zaini"              => ['Kajian Rutin'],
            "Pengumuman Sholat Ied di Lapangan"              => ['Ramadhan'],
            "Pembukaan Kelas Tahsin Dewasa"                  => ['Pengumuman'],
            "[DRAFT] Rencana Pembangunan Menara Baru"        => ['Renovasi'],
        ];

        foreach ($beritas as $judul => $kats) {
            $published = !str_contains($judul, '[DRAFT]');
            $beritaId = DB::table('beritas')->insertGetId([
                'judul'        => $judul,
                'slug'         => Str::slug($judul),
                'isi'          => "<p><strong>$judul</strong><br>Assalamu'alaikum Wr. Wb.<br>Konten dummy untuk website masjid.</p>",
                'excerpt'      => Str::limit($judul, 160),
                'is_published' => $published,
                'published_at' => $published ? $now->clone()->subDays(rand(1, 90)) : null,
                'created_by'   => $userId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            foreach ($kats as $kat) {
                if (isset($kategoriIds['berita'][$kat])) {
                    DB::table('berita_kategori')->insertOrIgnore([
                        'berita_id'   => $beritaId,
                        'kategori_id' => $kategoriIds['berita'][$kat],
                    ]);
                }
            }
        }
    }

    private function seedGaleri($kategoriIds, $userId, $now)
    {
        $galeris = [
            "Tarawih Malam 27 Ramadhan 1446 H"    => ['Kegiatan Ramadhan', 'Hari Besar Islam'],
            "Santunan 300 Anak Yatim"             => ['Santunan Anak Yatim'],
            "Maulid Nabi Bersama Habib Syech"     => ['Hari Besar Islam'],
            "Gotong Royong Bersih Masjid"         => ['Kebersihan Masjid'],
            "Sholat Ied di Lapangan"              => ['Kegiatan Ramadhan'],
            "Khutbah Jumat Pertama Ramadhan"      => ['Jumatan'],
            "Lomba Kaligrafi Islami"              => ['Lomba'],
            "Pemasangan Keramik Wudhu"            => ['Renovasi'],
            "Kajian Subuh Rutin"                  => ['Kegiatan Ramadhan'],
            "Pembinaan Remaja Masjid"             => ['Lomba'],
            "Hari Santri Nasional"                => ['Hari Besar Islam'],
            "Khotmil Quran Akbar"                 => ['Hari Besar Islam'],
        ];

        foreach ($galeris as $judul => $kats) {
            $galeriId = DB::table('galeris')->insertGetId([
                'judul'        => $judul,
                'keterangan'   => "Dokumentasi $judul – Masjid Al-Hidayah",
                'tipe'         => str_contains($judul, 'Habib') || str_contains($judul, 'Khutbah') ? 'video' : 'foto',
                'url_video'    => str_contains($judul, 'video') ? 'https://www.youtube.com/embed/dQw4w9WgXcQ' : null,
                'is_published' => true,
                'published_at' => $now->clone()->subDays(rand(10, 300)),
                'created_by'   => $userId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            foreach ($kats as $kat) {
                if (isset($kategoriIds['galeri'][$kat])) {
                    DB::table('galeri_kategori')->insertOrIgnore([
                        'galeri_id'   => $galeriId,
                        'kategori_id' => $kategoriIds['galeri'][$kat],
                    ]);
                }
            }
        }
    }

    private function seedAcara($kategoriIds, $userId, $now)
    {
        $acaras = [
            "Buka Puasa Bersama Anak Yatim"     => ['Ramadhan', 'Santunan Anak Yatim'],
            "Seminar Parenting Islami"          => ['Pelatihan'],
            "Isra Mi'raj 1447 H"                => ['Hari Besar Islam'],
            "Lomba Adzan & Tahfidz"             => ['Lomba'],
            "Kajian Kitab Riyadhus Shalihin"    => ['Acara Rutin'],
            "Pengajian Akbar Maulid Nabi"       => ['Hari Besar Islam'],
            "Pelatihan Dai Muda"                => ['Pelatihan'],
            "Khotmil Quran Akbar"               => ['Hari Besar Islam'],
            "Hari Santri Nasional"              => ['Hari Besar Islam'],
            "Tahun Baru Islam 1447 H"           => ['Hari Besar Islam'],
            "Kajian Fiqih Wanita"               => ['Acara Rutin'],
            "[DRAFT] Musabaqah Hifdzil Quran"   => ['Lomba'],
        ];

        foreach ($acaras as $judul => $kats) {
            $published = !str_contains($judul, '[DRAFT]');
            $acaraId = DB::table('acaras')->insertGetId([
                'judul'        => $judul,
                'slug'         => Str::slug($judul) . '-' . Str::random(4),
                'deskripsi'    => "<p>Acara <strong>$judul</strong> – jamaah diundang hadir.</p>",
                'mulai'        => $now->clone()->addDays(rand(1, 180))->setTime(rand(7, 19), 0),
                'selesai'      => $now->clone()->addDays(rand(1, 180))->setTime(rand(20, 22), 0),
                'lokasi'       => 'Masjid Al-Hidayah',
                'penyelenggara'=> 'Takmir Masjid',
                'is_published' => $published,
                'published_at' => $published ? $now->clone()->subDays(rand(1, 60)) : null,
                'created_by'   => $userId,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);

            foreach ($kats as $kat) {
                if (isset($kategoriIds['acara'][$kat])) {
                    DB::table('acara_kategori')->insertOrIgnore([
                        'acara_id'    => $acaraId,
                        'kategori_id' => $kategoriIds['acara'][$kat],
                    ]);
                }
            }
        }
    }

    private function seedPengumuman($now)
    {
        $list = [
            "Ramadhan 1447 H Sebentar Lagi!",
            "Tarawih Perdana Besok!",
            "Infaq Jumat Berkah",
            "WiFi Masjid",
            "Sholat Ied di Lapangan",
            "Donasi Kurban",
            "Pendaftaran TPQ",
            "Jadwal Kajian",
            "Sound System Baru",
            "CCTV Aktif",
            "Parkir Tertib",
            "Ayo Ramaikan Masjid!"
        ];

        foreach ($list as $i => $judul) {
            $tipe = $i < 4 ? 'banner' : ($i < 7 ? 'popup' : 'notif');
            DB::table('pengumumans')->insert([
                'judul'     => $judul,
                'isi'       => "Pengumuman: $judul",
                'tipe'      => $tipe,
                'mulai'     => $now->clone()->subDays(rand(0, 30)),
                'selesai'   => $now->clone()->addDays(rand(10, 180)),
                'is_active' => true,
                'created_at'=> $now,
                'updated_at'=> $now,
            ]);
        }
    }

    /**
     * 6. BANNERS (baru) – konten nyambung dengan slider di frontend
     */
    private function seedBanners($now)
    {
        $banners = [
            [
                'judul'        => 'Kajian Spesial Akhir Pekan',
                'subjudul'     => 'Ikuti kajian tematik setiap Sabtu malam bersama ustadz pilihan.',
                'note'         => 'Terbuka untuk umum • Jamaah putra & putri',
                'button_label' => 'Lihat Jadwal',
                'button_url'   => '/acara',
                'is_active'    => true,
                'urutan'       => 1,
                'deskripsi'    => "<p><strong>Kajian Spesial Akhir Pekan</strong></p>
<p>Kajian tematik dengan pembahasan isu-isu keumatan dan fiqih praktis, insyaAllah dilaksanakan setiap Sabtu malam.</p>
<ul>
<li>Terbuka untuk umum (ikhwan & akhwat)</li>
<li>Disertai sesi tanya jawab</li>
<li>Disiarkan juga melalui live streaming</li>
</ul>",
            ],
            [
                'judul'        => 'Program Infaq Operasional',
                'subjudul'     => 'Bantu kelancaran listrik, kebersihan, dan kegiatan harian masjid.',
                'note'         => 'Bisa infaq via transfer & QRIS',
                'button_label' => 'Donasi Sekarang',
                'button_url'   => '/donasi',
                'is_active'    => true,
                'urutan'       => 2,
                'deskripsi'    => "<p><strong>Program Infaq Operasional</strong></p>
<p>Dukungan Anda membantu listrik, air, kebersihan, keamanan, serta sarana kegiatan harian di masjid.</p>
<ul>
<li>Transparan dan tercatat dalam laporan keuangan</li>
<li>Bisa donasi rutin bulanan</li>
<li>Tersedia QRIS dan transfer bank</li>
</ul>",
            ],
            [
                'judul'        => 'TPA & Rumah Tahfidz',
                'subjudul'     => 'Pendaftaran santri baru untuk belajar Al-Qur’an & hafalan.',
                'note'         => 'Kuota terbatas • Usia 6–15 tahun',
                'button_label' => 'Daftar Santri',
                'button_url'   => '/tpa',
                'is_active'    => true,
                'urutan'       => 3,
                'deskripsi'    => "<p><strong>TPA &amp; Rumah Tahfidz</strong></p>
<p>Fasilitas pembinaan anak-anak untuk belajar membaca, menulis, dan menghafal Al-Qur’an dengan metode yang menyenangkan.</p>
<ul>
<li>Program reguler sore hari</li>
<li>Target hafalan juz 30 dan surat pilihan</li>
<li>Pembimbing ustadz/ustadzah berpengalaman</li>
</ul>",
            ],
            [
                'judul'        => 'Kegiatan Sosial & Santunan',
                'subjudul'     => 'Program rutin santunan yatim dan dhuafa di lingkungan masjid.',
                'note'         => 'Jadwal dan penyaluran diumumkan berkala',
                'button_label' => 'Lihat Program',
                'button_url'   => '/program-sosial',
                'is_active'    => true,
                'urutan'       => 4,
                'deskripsi'    => "<p><strong>Kegiatan Sosial &amp; Santunan</strong></p>
<p>Masjid menjadi pusat kepedulian melalui program santunan anak yatim, dhuafa, dan bantuan kemanusiaan lainnya.</p>
<ul>
<li>Penyaluran rutin tiap bulan</li>
<li>Data penerima manfaat terverifikasi</li>
<li>Donatur dapat mengikuti laporan penyaluran</li>
</ul>",
            ],
        ];

        foreach ($banners as $b) {
            DB::table('banners')->insert([
                'judul'        => $b['judul'],
                'subjudul'     => $b['subjudul'],
                'note'         => $b['note'],
                'button_label' => $b['button_label'],
                'button_url'   => $b['button_url'],
                'is_active'    => $b['is_active'],
                'urutan'       => $b['urutan'],
                'deskripsi'    => $b['deskripsi'],
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }

        $this->command->info('Dummy banner (slider) berhasil ditambahkan.');
    }
}
