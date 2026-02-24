<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\QuoteHarian;
use App\Models\SlideMotivasi;
use App\Models\KhutbahJumat;
use Carbon\Carbon;

class KontenMasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // =====================================
        // Quote Harian Ramadhan (BANYAK DATA)
        // =====================================
        $quotes = [

        ['title'=>'QS. Al-Baqarah: 183','text'=>'Hai orang-orang yang beriman, diwajibkan atas kamu berpuasa sebagaimana diwajibkan atas orang-orang sebelum kamu agar kamu bertakwa.'],
        ['title'=>'QS. Al-Baqarah: 185','text'=>'Bulan Ramadhan adalah bulan yang di dalamnya diturunkan Al-Qur’an sebagai petunjuk bagi manusia.'],
        ['title'=>'HR. Bukhari & Muslim','text'=>'Barangsiapa berpuasa Ramadhan karena iman dan mengharap pahala, diampuni dosa-dosanya yang telah lalu.'],
        ['title'=>'HR. Bukhari','text'=>'Puasa adalah perisai.'],
        ['title'=>'HR. Tirmidzi','text'=>'Orang yang berpuasa memiliki dua kebahagiaan: ketika berbuka dan ketika bertemu Rabbnya.'],

        ['title'=>'Renungan Ramadhan','text'=>'Ramadhan bukan sekadar menahan lapar, tapi melatih hati agar lebih dekat kepada Allah.'],
        ['title'=>'Renungan Ramadhan','text'=>'Jika Ramadhan berlalu dan kita masih sama, berarti kita melewatkannya.'],
        ['title'=>'Renungan Ramadhan','text'=>'Lapar dan haus hanya sementara, pahala Ramadhan abadi selamanya.'],
        ['title'=>'Renungan Ramadhan','text'=>'Ramadhan adalah madrasah kesabaran dan sekolah ketakwaan.'],
        ['title'=>'Renungan Ramadhan','text'=>'Perbaiki shalatmu di Ramadhan, maka hidupmu akan diperbaiki setelahnya.'],

        ['title'=>'QS. Az-Zumar: 53','text'=>'Janganlah kamu berputus asa dari rahmat Allah.'],
        ['title'=>'QS. Ar-Ra’d: 28','text'=>'Ingatlah, hanya dengan mengingat Allah hati menjadi tenteram.'],
        ['title'=>'QS. Al-Insyirah: 5-6','text'=>'Sesungguhnya bersama kesulitan ada kemudahan.'],
        ['title'=>'QS. Al-Qadr: 1','text'=>'Sesungguhnya Kami telah menurunkannya (Al-Qur’an) pada malam kemuliaan.'],
        ['title'=>'QS. Al-Qadr: 3','text'=>'Malam kemuliaan itu lebih baik dari seribu bulan.'],

        ['title'=>'HR. Muslim','text'=>'Sedekah tidak akan mengurangi harta.'],
        ['title'=>'HR. Tirmidzi','text'=>'Senyummu kepada saudaramu adalah sedekah.'],
        ['title'=>'HR. Bukhari','text'=>'Sebaik-baik kalian adalah yang paling baik akhlaknya.'],
        ['title'=>'HR. Muslim','text'=>'Doa orang yang berpuasa tidak tertolak.'],
        ['title'=>'HR. Ahmad','text'=>'Orang yang memberi makan orang berbuka mendapatkan pahala seperti orang yang berpuasa.'],

        ['title'=>'Hikmah Ramadhan','text'=>'Ramadhan datang untuk membersihkan hati, bukan hanya mengosongkan perut.'],
        ['title'=>'Hikmah Ramadhan','text'=>'Perbanyak istighfar di Ramadhan, karena kita tidak tahu Ramadhan mana yang terakhir.'],
        ['title'=>'Hikmah Ramadhan','text'=>'Ramadhan adalah kesempatan memperbaiki hubungan dengan Allah dan manusia.'],
        ['title'=>'Hikmah Ramadhan','text'=>'Jangan hanya berbuka dari lapar, berbukalah dari dosa.'],
        ['title'=>'Hikmah Ramadhan','text'=>'Al-Qur’an diturunkan di Ramadhan, maka dekatilah ia di bulan ini.'],

        ['title'=>'Renungan','text'=>'Orang beriman merindukan Ramadhan, bukan sekadar liburnya.'],
        ['title'=>'Renungan','text'=>'Ramadhan mengajarkan kita bahwa kita mampu menahan diri.'],
        ['title'=>'Renungan','text'=>'Setiap malam Ramadhan adalah peluang pengampunan.'],
        ['title'=>'Renungan','text'=>'Shalat malam di Ramadhan adalah cahaya di kubur kelak.'],
        ['title'=>'Renungan','text'=>'Lailatul Qadar bisa jadi hanya sekali dalam hidupmu. Jangan sia-siakan.'],

        ['title'=>'QS. Al-Baqarah: 186','text'=>'Aku mengabulkan permohonan orang yang berdoa apabila ia berdoa kepada-Ku.'],
        ['title'=>'QS. At-Tahrim: 8','text'=>'Wahai orang beriman, bertaubatlah kepada Allah dengan taubat yang sebenar-benarnya.'],
        ['title'=>'QS. Al-Ahzab: 41','text'=>'Wahai orang beriman, berdzikirlah kepada Allah dengan zikir yang banyak.'],
        ['title'=>'QS. Al-Hasyr: 18','text'=>'Hendaklah setiap jiwa memperhatikan apa yang telah diperbuatnya untuk hari esok.'],
        ['title'=>'QS. Al-Mulk: 2','text'=>'Dia menguji kamu siapa di antara kamu yang terbaik amalnya.'],

        ['title'=>'Hadits','text'=>'Barangsiapa berdiri (shalat malam) pada malam Lailatul Qadar karena iman dan berharap pahala, diampuni dosa-dosanya yang telah lalu.'],
        ['title'=>'Hadits','text'=>'Allah membebaskan banyak hamba dari neraka pada setiap malam Ramadhan.'],
        ['title'=>'Hadits','text'=>'Pintu surga dibuka dan pintu neraka ditutup pada bulan Ramadhan.'],
        ['title'=>'Hadits','text'=>'Setan-setan dibelenggu pada bulan Ramadhan.'],
        ['title'=>'Hadits','text'=>'Puasa dan Al-Qur’an akan memberi syafaat bagi seorang hamba pada hari kiamat.'],

        ['title'=>'Motivasi','text'=>'Ramadhan adalah waktu terbaik memulai hidup baru.'],
        ['title'=>'Motivasi','text'=>'Jika ingin berubah, Ramadhan adalah awalnya.'],
        ['title'=>'Motivasi','text'=>'Jadikan Ramadhan titik balik, bukan hanya rutinitas tahunan.'],
        ['title'=>'Motivasi','text'=>'Orang yang paling rugi adalah yang keluar dari Ramadhan tanpa ampunan.'],
        ['title'=>'Motivasi','text'=>'Hari ini mungkin Ramadhanmu, belum tentu tahun depan.'],

        ['title'=>'Nasihat','text'=>'Perbanyak doa sebelum berbuka, itu waktu mustajab.'],
        ['title'=>'Nasihat','text'=>'Bangun sahur bukan hanya makan, tapi juga keberkahan.'],
        ['title'=>'Nasihat','text'=>'Jaga lisan saat puasa, karena pahala bisa hilang karenanya.'],
        ['title'=>'Nasihat','text'=>'Ramadhan mengajarkan empati kepada orang yang kekurangan.'],
        ['title'=>'Nasihat','text'=>'Istiqamah setelah Ramadhan adalah tanda Ramadhan diterima.'],

        ];

        // kosongkan dulu supaya tidak numpuk tiap seed
        QuoteHarian::truncate();

        // acak urutan quote SEKALI
        shuffle($quotes);

        $total = 100;                 // jumlah data yang mau dibuat
        $countQuotes = count($quotes);

        for ($i = 1; $i <= $total; $i++) {

            // ambil quote berurutan (bukan random liar)
            $base = $quotes[($i - 1) % $countQuotes];

            QuoteHarian::create([
                'title'     => $base['title'],
                'text'      => $base['text'],
                'order'     => $i,
                'is_active' => $i <= 30 ? 1 : 0,
            ]);
        }

        $this->command->info("Quote Harian: $total data berhasil dibuat!");

        // =====================================
        // 2. Slider Motivasi (10 data – tema Ramadhan 2026)
        // =====================================
        $ramadhanSliders = [
            [
                'title'       => 'Selamat Datang Ramadhan 1447 H',
                'subtitle'    => 'Bulan penuh ampunan, sedekah, dan pahala berlipat ganda. Mari kita sambut dengan hati yang bersih!',
                'button_text' => 'Yuk Mulai Puasa & Sedekah',
                'button_link' => '#donasi',
                'gradient'    => 'from-emerald-700 via-teal-600 to-cyan-600',
                'border_color'=> 'border-emerald-500/30',
                'order'       => 1,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Sedekah di Bulan Ramadhan...',
                'subtitle'    => 'Pahala dilipatgandakan hingga <span class="text-yellow-300">700 kali</span>. Satu kebaikan kecil bisa jadi jalan menuju surga.',
                'button_text' => 'Sedekah Sekarang',
                'button_link' => '#qris',
                'gradient'    => 'from-teal-700 via-emerald-600 to-cyan-700',
                'border_color'=> 'border-teal-500/30',
                'order'       => 2,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Malam Lailatul Qadar...',
                'subtitle'    => 'Lebih baik dari seribu bulan. Jangan lewatkan ibadah malam di 10 hari terakhir Ramadhan!',
                'button_text' => 'Siapkan Diri',
                'button_link' => '#kajian',
                'gradient'    => 'from-cyan-700 via-teal-600 to-emerald-700',
                'border_color'=> 'border-cyan-500/30',
                'order'       => 3,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Puasa Bukan Hanya Menahan Lapar',
                'subtitle'    => 'Tapi juga menahan lisan, mata, dan hati dari segala yang sia-sia. Mari jaga diri di bulan suci ini.',
                'button_text' => 'Mulai dari Hati',
                'button_link' => '#rekening',
                'gradient'    => 'from-emerald-600 via-teal-700 to-cyan-600',
                'border_color'=> 'border-emerald-500/30',
                'order'       => 4,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Sedekah di Ramadhan...',
                'subtitle'    => 'Seperti menabur benih di tanah subur. Hasilnya? <span class="text-yellow-300">Berlipat ganda</span> di dunia & akhirat.',
                'button_text' => 'Tabur Kebaikan',
                'button_link' => '#donasi',
                'gradient'    => 'from-teal-600 via-emerald-700 to-cyan-700',
                'border_color'=> 'border-teal-500/30',
                'order'       => 5,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Doa Orang Berpuasa Tidak Ditolak',
                'subtitle'    => 'Saat berbuka, saat sahur, dan sepanjang hari puasa. Manfaatkan momen ini untuk bermunajat kepada-Nya.',
                'button_text' => 'Berdoa Sekarang',
                'button_link' => '#doa',
                'gradient'    => 'from-cyan-600 via-teal-700 to-emerald-600',
                'border_color'=> 'border-cyan-500/30',
                'order'       => 6,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Ramadhan Bulan Al-Qur’an',
                'subtitle'    => 'Mari tingkatkan tilawah, tadarus, dan tadabbur. Satu huruf dibaca = 10 kebaikan!',
                'button_text' => 'Mulai Tadarus',
                'button_link' => '#quran',
                'gradient'    => 'from-emerald-700 via-cyan-600 to-teal-700',
                'border_color'=> 'border-emerald-500/30',
                'order'       => 7,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Bersedekah di Bulan Puasa',
                'subtitle'    => 'Menghapus dosa seperti api menghapus ranting kering. Satu sedekah kecil bisa jadi penolong di akhirat.',
                'button_text' => 'Sedekah Ramadhan',
                'button_link' => '#qris',
                'gradient'    => 'from-teal-700 via-cyan-600 to-emerald-700',
                'border_color'=> 'border-teal-500/30',
                'order'       => 8,
                'is_active'   => 1,
            ],
            [
                'title'       => 'I’tikaf di 10 Hari Terakhir',
                'subtitle'    => 'Mencari Lailatul Qadar. Sisihkan waktu untuk berdiam di masjid, berzikir, dan berdoa.',
                'button_text' => 'Rencanakan I’tikaf',
                'button_link' => '#kajian',
                'gradient'    => 'from-cyan-700 via-emerald-600 to-teal-700',
                'border_color'=> 'border-cyan-500/30',
                'order'       => 9,
                'is_active'   => 1,
            ],
            [
                'title'       => 'Ramadhan Bulan Maghfirah',
                'subtitle'    => 'Allah membuka pintu ampunan seluas-luasnya. Beristighfar sebanyak mungkin sebelum bulan ini berakhir.',
                'button_text' => 'Istighfar Sekarang',
                'button_link' => '#doa',
                'gradient'    => 'from-emerald-600 via-teal-700 to-cyan-600',
                'border_color'=> 'border-emerald-500/30',
                'order'       => 10,
                'is_active'   => 1,
            ],
        ];

        foreach ($ramadhanSliders as $slider) {
            SlideMotivasi::create($slider);
        }

        $this->command->info('Slider Motivasi Ramadhan: 10 data berhasil dibuat!');

        // =====================================
        // 3. Khutbah Jumat (mulai 27 Februari 2026)
        // =====================================
        $startDate = Carbon::parse('2026-02-27'); // Jumat pertama yang kamu minta

        $khutbahs = [
            [
                'khatib'       => 'Ust. Dr. Ahmad Zaky',
                'tema'         => 'Puasa Ramadhan: Membangun Ketakwaan di Era Digital',
                'tanggal'      => 'Jum’at, 27 Februari 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Khatib tamu dari luar kota',
                'is_active'    => 1,
            ],
            [
                'khatib'       => 'Ust. Abdul Somad',
                'tema'         => 'Sedekah di Bulan Ramadhan: Kunci Pembuka Rezeki',
                'tanggal'      => 'Jum’at, 6 Maret 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Tema spesial Ramadhan',
                'is_active'    => 1,
            ],
            [
                'khatib'       => 'Ust. Hanan Attaki',
                'tema'         => 'Menjaga Hati di 10 Hari Terakhir Ramadhan',
                'tanggal'      => 'Jum’at, 13 Maret 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Persiapan Lailatul Qadar',
                'is_active'    => 1,
            ],
            // Tambah 5 lagi contoh
            [
                'khatib'       => 'Ust. Derry Sulaiman',
                'tema'         => 'Tadabbur Al-Qur’an di Bulan Suci',
                'tanggal'      => 'Jum’at, 20 Maret 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Fokus tilawah & tadabbur',
                'is_active'    => 1,
            ],
            [
                'khatib'       => 'Ust. Felix Siauw',
                'tema'         => 'Keutamaan Itikaf dan Doa di Ramadhan',
                'tanggal'      => 'Jum’at, 27 Maret 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Ibadah penutup Ramadhan',
                'is_active'    => 1,
            ],
            [
                'khatib'       => 'Ust. Khalid Basalamah',
                'tema'         => 'Menyiapkan Diri Menyambut Idul Fitri',
                'tanggal'      => 'Jum’at, 3 April 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Transisi Ramadhan ke Syawal',
                'is_active'    => 1,
            ],
            [
                'khatib'       => 'Ust. Miftah el-Banjary',
                'tema'         => 'Zakat Fitrah: Kewajiban & Hikmahnya',
                'tanggal'      => 'Jum’at, 10 April 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Persiapan Idul Fitri',
                'is_active'    => 1,
            ],
            [
                'khatib'       => 'Ust. Adi Hidayat',
                'tema'         => 'Refleksi Ramadhan: Apa yang Kita Bawa Pulang?',
                'tanggal'      => 'Jum’at, 17 April 2026',
                'jam'          => '11:45 - 13:00',
                'tanggal_asli' => $startDate->addWeek()->toDateString(),
                'status'       => 'coming_soon',
                'keterangan'   => 'Evaluasi ibadah Ramadhan',
                'is_active'    => 1,
            ],
        ];

        foreach ($khutbahs as $khutbah) {
            KhutbahJumat::create($khutbah);
        }

        $this->command->info('Khutbah Jumat (mulai 27 Feb 2026): 8 jadwal contoh berhasil dibuat!');
    }
}