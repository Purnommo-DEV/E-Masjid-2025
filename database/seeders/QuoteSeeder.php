<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuoteSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('quote_harian')->truncate();

        $quotes = [
            ['title' => 'Hati Menjadi Tenang', 'text' => 'Ingatlah, hanya dengan mengingat Allah hati menjadi tenteram. (QS. Ar-Ra\'d: 28)', 'source_type' => 'Al-Quran'],
            ['title' => 'Allah Tidak Membebani', 'text' => 'Allah tidak membebani seseorang melainkan sesuai dengan kesanggupannya. (QS. Al-Baqarah: 286)', 'source_type' => 'Al-Quran'],
            ['title' => 'Jangan Berputus Asa', 'text' => 'Janganlah kamu berputus asa dari rahmat Allah. Sesungguhnya Allah mengampuni dosa-dosa semuanya. (QS. Az-Zumar: 53)', 'source_type' => 'Al-Quran'],
            ['title' => 'Allah Bersama Orang Sabar', 'text' => 'Sesungguhnya Allah bersama orang-orang yang sabar. (QS. Al-Baqarah: 153)', 'source_type' => 'Al-Quran'],
            ['title' => 'Bersyukur', 'text' => 'Jika kamu bersyukur, niscaya Aku akan menambah nikmat kepadamu. (QS. Ibrahim: 7)', 'source_type' => 'Al-Quran'],
            ['title' => 'Bertawakal', 'text' => 'Barang siapa bertawakal kepada Allah, maka cukuplah Allah baginya. (QS. At-Talaq: 3)', 'source_type' => 'Al-Quran'],
            ['title' => 'Jalan Keluar', 'text' => 'Barang siapa bertakwa kepada Allah, niscaya Dia akan membukakan jalan keluar baginya. (QS. At-Talaq: 2)', 'source_type' => 'Al-Quran'],
            ['title' => 'Rezeki Tak Terduga', 'text' => 'Dan Dia memberinya rezeki dari arah yang tidak disangka-sangkanya. (QS. At-Talaq: 3)', 'source_type' => 'Al-Quran'],
            ['title' => 'Kemudahan', 'text' => 'Sesungguhnya bersama kesulitan ada kemudahan. (QS. Al-Insyirah: 5)', 'source_type' => 'Al-Quran'],
            ['title' => 'Kemudahan Kedua', 'text' => 'Sesungguhnya bersama kesulitan ada kemudahan. (QS. Al-Insyirah: 6)', 'source_type' => 'Al-Quran'],
            ['title' => 'Allah Dekat', 'text' => 'Sesungguhnya Aku dekat. Aku mengabulkan permohonan orang yang berdoa apabila ia berdoa kepada-Ku. (QS. Al-Baqarah: 186)', 'source_type' => 'Al-Quran'],
            ['title' => 'Berdoalah', 'text' => 'Berdoalah kepada-Ku, niscaya akan Aku perkenankan bagimu. (QS. Ghafir: 60)', 'source_type' => 'Al-Quran'],
            ['title' => 'Berbuat Adil', 'text' => 'Sesungguhnya Allah menyuruh berlaku adil, berbuat kebajikan, dan memberi kepada kaum kerabat. (QS. An-Nahl: 90)', 'source_type' => 'Al-Quran'],
            ['title' => 'Jangan Lemah', 'text' => 'Janganlah kamu merasa lemah dan jangan pula bersedih hati. (QS. Ali \'Imran: 139)', 'source_type' => 'Al-Quran'],
            ['title' => 'Allah Maha Pengampun', 'text' => 'Sesungguhnya Allah Maha Pengampun lagi Maha Penyayang. (QS. Az-Zumar: 53)', 'source_type' => 'Al-Quran'],
            ['title' => 'Allah Menyukai Kebaikan', 'text' => 'Sesungguhnya Allah menyukai orang-orang yang berbuat baik. (QS. Al-Baqarah: 195)', 'source_type' => 'Al-Quran'],
            ['title' => 'Shalat', 'text' => 'Dirikanlah shalat untuk mengingat-Ku. (QS. Taha: 14)', 'source_type' => 'Al-Quran'],
            ['title' => 'Tolong Menolong', 'text' => 'Tolong-menolonglah kamu dalam kebajikan dan takwa. (QS. Al-Ma\'idah: 2)', 'source_type' => 'Al-Quran'],
            ['title' => 'Allah Maha Melihat', 'text' => 'Sesungguhnya Allah Maha Melihat apa yang kamu kerjakan. (QS. Al-Hujurat: 18)', 'source_type' => 'Al-Quran'],
            ['title' => 'Rahmat Allah', 'text' => 'Sesungguhnya rahmat Allah amat dekat kepada orang-orang yang berbuat baik. (QS. Al-A\'raf: 56)', 'source_type' => 'Al-Quran'],

            ['title' => 'Amal Bergantung Niat', 'text' => 'Sesungguhnya setiap amal tergantung niatnya. (HR. Bukhari No. 1; Muslim No. 1907)', 'source_type' => 'Hadits'],
            ['title' => 'Agama Itu Mudah', 'text' => 'Sesungguhnya agama ini mudah. (HR. Bukhari No. 39)', 'source_type' => 'Hadits'],
            ['title' => 'Menjaga Lisan', 'text' => 'Barang siapa beriman kepada Allah dan hari akhir hendaklah berkata baik atau diam. (HR. Bukhari No. 6018; Muslim No. 47)', 'source_type' => 'Hadits'],
            ['title' => 'Senyum Sedekah', 'text' => 'Senyummu kepada saudaramu adalah sedekah. (HR. Tirmidzi No. 1956)', 'source_type' => 'Hadits'],
            ['title' => 'Mukmin Kuat', 'text' => 'Mukmin yang kuat lebih dicintai Allah daripada mukmin yang lemah. (HR. Muslim No. 2664)', 'source_type' => 'Hadits'],
            ['title' => 'Jangan Marah', 'text' => 'Jangan marah. (HR. Bukhari No. 6116)', 'source_type' => 'Hadits'],
            ['title' => 'Sedekah', 'text' => 'Sedekah tidaklah mengurangi harta. (HR. Muslim No. 2588)', 'source_type' => 'Hadits'],
            ['title' => 'Kasih Sayang', 'text' => 'Orang yang tidak menyayangi tidak akan disayangi. (HR. Bukhari No. 6013; Muslim No. 2318)', 'source_type' => 'Hadits'],
            ['title' => 'Allah Melihat Hati', 'text' => 'Allah tidak melihat rupa dan harta kalian, tetapi Dia melihat hati dan amal kalian. (HR. Muslim No. 2564)', 'source_type' => 'Hadits'],
            ['title' => 'Silaturahmi', 'text' => 'Barang siapa ingin dilapangkan rezekinya dan dipanjangkan umurnya hendaklah menyambung silaturahmi. (HR. Bukhari No. 5986; Muslim No. 2557)', 'source_type' => 'Hadits'],
            ['title' => 'Menolong Sesama', 'text' => 'Allah akan menolong seorang hamba selama ia menolong saudaranya. (HR. Muslim No. 2699)', 'source_type' => 'Hadits'],
            ['title' => 'Malu Bagian Iman', 'text' => 'Malu adalah bagian dari iman. (HR. Bukhari No. 24; Muslim No. 36)', 'source_type' => 'Hadits'],
            ['title' => 'Doa Adalah Ibadah', 'text' => 'Doa adalah ibadah. (HR. Abu Dawud No. 1479; Tirmidzi No. 2969)', 'source_type' => 'Hadits'],
            ['title' => 'Cinta Sesama Muslim', 'text' => 'Tidak sempurna iman seseorang hingga ia mencintai saudaranya sebagaimana ia mencintai dirinya sendiri. (HR. Bukhari No. 13; Muslim No. 45)', 'source_type' => 'Hadits'],
            ['title' => 'Mencari Ilmu', 'text' => 'Barang siapa menempuh jalan untuk mencari ilmu, Allah mudahkan baginya jalan menuju surga. (HR. Muslim No. 2699)', 'source_type' => 'Hadits'],
            ['title' => 'Akhlak Terbaik', 'text' => 'Mukmin yang paling sempurna imannya adalah yang paling baik akhlaknya. (HR. Tirmidzi No. 1162)', 'source_type' => 'Hadits'],
            ['title' => 'Allah Itu Indah', 'text' => 'Sesungguhnya Allah itu indah dan mencintai keindahan. (HR. Muslim No. 91)', 'source_type' => 'Hadits'],
            ['title' => 'Memudahkan Urusan', 'text' => 'Permudahlah dan jangan mempersulit. (HR. Bukhari No. 69; Muslim No. 1734)', 'source_type' => 'Hadits'],
            ['title' => 'Cinta Allah', 'text' => 'Allah mencintai jika salah seorang di antara kalian melakukan pekerjaan, ia menyempurnakannya. (HR. Thabrani, hasan)', 'source_type' => 'Hadits'],
            ['title' => 'Kebersihan', 'text' => 'Kesucian adalah sebagian dari iman. (HR. Muslim No. 223)', 'source_type' => 'Hadits'],
            ['title' => 'Orang Terbaik', 'text' => 'Sebaik-baik kalian adalah yang paling baik akhlaknya. (HR. Bukhari No. 3559)', 'source_type' => 'Hadits'],
            ['title' => 'Tidak Menipu', 'text' => 'Barang siapa menipu maka ia bukan dari golongan kami. (HR. Muslim No. 102)', 'source_type' => 'Hadits'],
            ['title' => 'Muslim Sejati', 'text' => 'Seorang muslim adalah orang yang kaum muslimin selamat dari lisan dan tangannya. (HR. Bukhari No. 10; Muslim No. 40)', 'source_type' => 'Hadits'],
            ['title' => 'Saling Mencintai', 'text' => 'Orang-orang penyayang akan disayangi oleh Yang Maha Penyayang. (HR. Abu Dawud No. 4941; Tirmidzi No. 1924)', 'source_type' => 'Hadits'],
            ['title' => 'Tahan Emosi', 'text' => 'Orang kuat bukanlah yang pandai bergulat, tetapi yang mampu menahan amarahnya. (HR. Bukhari No. 6114; Muslim No. 2609)', 'source_type' => 'Hadits'],
            ['title' => 'Zuhud', 'text' => 'Bersikap zuhudlah terhadap dunia, niscaya Allah mencintaimu. (HR. Ibnu Majah No. 4102)', 'source_type' => 'Hadits'],
            ['title' => 'Ucapan Baik', 'text' => 'Perkataan yang baik adalah sedekah. (HR. Bukhari No. 2989; Muslim No. 1009)', 'source_type' => 'Hadits'],
            ['title' => 'Meringankan Kesulitan', 'text' => 'Barang siapa meringankan kesulitan seorang mukmin, Allah akan meringankan kesulitannya pada hari kiamat. (HR. Muslim No. 2699)', 'source_type' => 'Hadits'],
            ['title' => 'Istiqamah', 'text' => 'Katakanlah: Aku beriman kepada Allah, kemudian beristiqamahlah. (HR. Muslim No. 38)', 'source_type' => 'Hadits'],
            ['title' => 'Doa Mukmin', 'text' => 'Tidaklah seorang muslim berdoa tanpa mengandung dosa atau memutus silaturahmi melainkan Allah akan mengabulkannya atau menggantinya dengan yang lebih baik. (HR. Ahmad No. 11133)', 'source_type' => 'Hadits'],
        ];

        foreach ($quotes as $index => &$quote) {
            $quote['order'] = $index + 1;
            $quote['is_active'] = true;
            $quote['scheduled_date'] = null;
            $quote['created_at'] = now();
            $quote['updated_at'] = now();
        }

        DB::table('quote_harian')->insert($quotes);
    }
}
