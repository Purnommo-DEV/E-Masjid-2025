<?php

use App\Models\Acara;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            13 => 'jadwal-sholat-tarawih-ramadhan-1447h-masjid-raudhotul-jannah.png',
            14 => 'tadarus-al-quran-mtrj-ramadhan-1447h.png',
            15 => 'peringatan-nuzulul-quran-ramadhan-1447h.png',
            16 => 'khotmil-quran-ramadhan-1447h-masjid-raudhotul-jannah.png',
            17 => 'program-ifthor-buka-puasa-bersama-ramadhan-1447h.png',
            18 => 'bazaar-murah-ramadhan-1447h-masjid-raudhotul-jannah.png',
            19 => 'penerimaan-penyaluran-ziswaf-ramadhan-1447h.jpeg',
            20 => 'santunan-anak-yatim-dhuafa-ramadhan-1447h.png',
            21 => 'lomba-anak-sholeh-sholehah-ramadhan-1447h.png',
            22 => 'itikaf-10-hari-terakhir-ramadhan-masjid-raudhotul-jannah.png',
            23 => 'pembagian-sembako-kaum-dhuafa-tce.png',
            24 => 'gebyar-ramadhan-masjid-raudhotul-jannah-1447h.png',
            25 => 'sholat-idul-fitri-1447h-taman-cipulir-estate.jpeg',
            27 => '1000267768.png',
            28 => '1000272888.png',
            29 => 'Flyer-MRJ.png',
            30 => 'New-Project(1)(1).jpg',
        ];

        foreach ($updates as $id => $file) {
            Acara::where('id', $id)->update([
                'image_path' => 'mrj/acara/' . $file
            ]);
        }
    }

    public function down(): void
    {
        // Rollback: hapus image_path untuk acara yang diupdate
        Acara::whereIn('id', [
            13,14,15,16,17,18,19,20,21,22,23,24,25,27,28,29,30
        ])->update(['image_path' => null]);
    }
};