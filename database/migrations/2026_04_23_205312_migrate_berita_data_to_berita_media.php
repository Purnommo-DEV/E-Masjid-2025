<?php

use App\Models\Berita;
use App\Models\BeritaMedia;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // Mapping data berita ke file gambar
        // Format path: mrj/berita/{slug}/{nama_file}
        
        $mediaData = [
            // Berita ID 27: tadarus-al-quran-mtrj-khatam-di-pertengahan-ramadhan-1447-h-di-masjid-raudhotul-jannah
            ['berita_id' => 27, 'file' => 'tadarus-quran-mtrj-masjid-raudhotul-jannah-1.webp', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 27, 'file' => 'tadarus-quran-mtrj-masjid-raudhotul-jannah-2.webp', 'is_cover' => false, 'urutan' => 1],
            
            // Berita ID 28: kegiatan-ifthor-ramadhan-di-masjid-raudhotul-jannah-taman-cipulir-estate-diikuti-sekitar-100-jamaah
            ['berita_id' => 28, 'file' => 'ifthor-ramadhan-masjid-raudhotul-jannah-1.webp', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 28, 'file' => 'ifthor-ramadhan-masjid-raudhotul-jannah-2.webp', 'is_cover' => false, 'urutan' => 1],
            ['berita_id' => 28, 'file' => 'ifthor-ramadhan-masjid-raudhotul-jannah-3.webp', 'is_cover' => false, 'urutan' => 2],
            ['berita_id' => 28, 'file' => 'ifthor-ramadhan-masjid-raudhotul-jannah-4.webp', 'is_cover' => false, 'urutan' => 3],
            ['berita_id' => 28, 'file' => 'ifthor-ramadhan-masjid-raudhotul-jannah-5.webp', 'is_cover' => false, 'urutan' => 4],
            
            // Berita ID 29: semarak-ramadhan-1447-h-di-masjid-raudhotul-jannah-taman-cipulir-estate
            ['berita_id' => 29, 'file' => 'semarak-ramadhan-1447h-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 31: jamaah-masjid-raudhotul-jannah-laksanakan-sholat-gerhana-bulan-pada-3-maret-2026
            ['berita_id' => 31, 'file' => 'sholat-gerhana-bulan-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 33: buka-puasa-perdana-ramadhan-1447-h-di-masjid-raudhotul-jannah-taman-cipulir-estate
            ['berita_id' => 33, 'file' => 'buka-puasa-perdana-ramadhan-1447h-masjid-raudhotul-jannah-1.webp', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 33, 'file' => 'buka-puasa-perdana-ramadhan-1447h-masjid-raudhotul-jannah-2.webp', 'is_cover' => false, 'urutan' => 1],
            
            // Berita ID 34: tarawih-perdana-ramadhan-1447-h-di-masjid-raudhotul-jannah-taman-cipulir-estate
            ['berita_id' => 34, 'file' => 'tarawih-perdana-ramadhan-1447h-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 35: pelaksanaan-sholat-tarawih
            ['berita_id' => 35, 'file' => 'sholat-tarawih-ramadhan-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 36: penyediaan-hidangan-berbuka-puasa-dan-santap-sahur
            ['berita_id' => 36, 'file' => 'hidangan-berbuka-puasa-dan-sahur-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 37: kegiatan-tadarus-al-quran-mtrj
            ['berita_id' => 37, 'file' => 'tadarus-al-quran-ramadhan-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 38: kegiatan-khotmil-quran
            ['berita_id' => 38, 'file' => 'khotmil-quran-ramadhan-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 39: penerimaan-dan-penyaluran-ziswaf
            ['berita_id' => 39, 'file' => 'penerimaan-dan-penyaluran-ziswaf-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 40: peringatan-nuzulul-quran
            ['berita_id' => 40, 'file' => 'peringatan-nuzulul-quran-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 41: pembagian-sembako-kaum-dhuafa
            ['berita_id' => 41, 'file' => 'pembagian-sembako-kaum-dhuafa-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 42: santunan-anak-yatim-yang-dhuafa-dan-dhuafa
            ['berita_id' => 42, 'file' => 'santunan-anak-yatim-dan-dhuafa-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 43: lomba-anak-sholeh-dan-sholehah
            ['berita_id' => 43, 'file' => 'lomba-anak-sholeh-dan-sholehah-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 44: baazar-murah-ramadhan
            ['berita_id' => 44, 'file' => 'bazaar-murah-ramadhan-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 45: gebyar-ramadhan
            ['berita_id' => 45, 'file' => 'gebyar-ramadhan-1447h-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 47: itikaf-10-hari-terakhir-ramadhan
            ['berita_id' => 47, 'file' => 'itikaf-10-hari-terakhir-ramadhan-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 48: pelaksanaan-sholat-idul-fitri-1447-h
            ['berita_id' => 48, 'file' => 'sholat-idul-fitri-1447h-masjid-raudhotul-jannah.webp', 'is_cover' => true, 'urutan' => 0],
            
            // Berita ID 62: gebyar-ramadhan-1447-h-di-masjid-raudhotul-jannah-taman-cipulir-estate-penuh-kebersamaan-dan-kepedulian
            ['berita_id' => 62, 'file' => '1.webp', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 62, 'file' => '2.webp', 'is_cover' => false, 'urutan' => 1],
            ['berita_id' => 62, 'file' => '3-(1).webp', 'is_cover' => false, 'urutan' => 2],
            ['berita_id' => 62, 'file' => '4.webp', 'is_cover' => false, 'urutan' => 3],
            ['berita_id' => 62, 'file' => '6.webp', 'is_cover' => false, 'urutan' => 4],
            ['berita_id' => 62, 'file' => '7.webp', 'is_cover' => false, 'urutan' => 5],
            ['berita_id' => 62, 'file' => '8.webp', 'is_cover' => false, 'urutan' => 6],
            ['berita_id' => 62, 'file' => '9.webp', 'is_cover' => false, 'urutan' => 7],
            ['berita_id' => 62, 'file' => '10.webp', 'is_cover' => false, 'urutan' => 8],
            ['berita_id' => 62, 'file' => '11.webp', 'is_cover' => false, 'urutan' => 9],
            ['berita_id' => 62, 'file' => '12.webp', 'is_cover' => false, 'urutan' => 10],
            ['berita_id' => 62, 'file' => '13.webp', 'is_cover' => false, 'urutan' => 11],
            ['berita_id' => 62, 'file' => '15.webp', 'is_cover' => false, 'urutan' => 12],
            ['berita_id' => 62, 'file' => '16.webp', 'is_cover' => false, 'urutan' => 13],
            ['berita_id' => 62, 'file' => '16-(1).webp', 'is_cover' => false, 'urutan' => 14],
            ['berita_id' => 62, 'file' => '17.webp', 'is_cover' => false, 'urutan' => 15],
            ['berita_id' => 62, 'file' => '18.webp', 'is_cover' => false, 'urutan' => 16],
            ['berita_id' => 62, 'file' => '19.webp', 'is_cover' => false, 'urutan' => 17],
            ['berita_id' => 62, 'file' => '20.webp', 'is_cover' => false, 'urutan' => 18],
            
            // Berita ID 65: suasana-khidmat-itikaf-malam-21-ramadhan-di-masjid-raudhotul-jannah-taman-cipulir-estate
            ['berita_id' => 65, 'file' => '1000247960.webp', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 65, 'file' => '1000247940.webp', 'is_cover' => false, 'urutan' => 1],
            ['berita_id' => 65, 'file' => '1000247937.webp', 'is_cover' => false, 'urutan' => 2],
            ['berita_id' => 65, 'file' => '1000247933.webp', 'is_cover' => false, 'urutan' => 3],
            ['berita_id' => 65, 'file' => '1000247929.webp', 'is_cover' => false, 'urutan' => 4],
            ['berita_id' => 65, 'file' => '1000247931.webp', 'is_cover' => false, 'urutan' => 5],
            ['berita_id' => 65, 'file' => '1000247927.webp', 'is_cover' => false, 'urutan' => 6],
            ['berita_id' => 65, 'file' => '1000247941.webp', 'is_cover' => false, 'urutan' => 7],
            ['berita_id' => 65, 'file' => '1000247935.webp', 'is_cover' => false, 'urutan' => 8],
            
            // Berita ID 68: sholat-idul-fitri-1447-h-di-masjid-raudhotul-jannah-berlangsung-khidmat
            ['berita_id' => 68, 'file' => '1000257572.jpg', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 68, 'file' => '1000257584.jpg', 'is_cover' => false, 'urutan' => 1],
            ['berita_id' => 68, 'file' => '1000257578.jpg', 'is_cover' => false, 'urutan' => 2],
            ['berita_id' => 68, 'file' => '1000257587.jpg', 'is_cover' => false, 'urutan' => 3],
            ['berita_id' => 68, 'file' => '1000257575.jpg', 'is_cover' => false, 'urutan' => 4],
            ['berita_id' => 68, 'file' => '1000257609.jpg', 'is_cover' => false, 'urutan' => 5],
            ['berita_id' => 68, 'file' => '1000257999.jpg', 'is_cover' => false, 'urutan' => 6],
            ['berita_id' => 68, 'file' => '1000258014.jpg', 'is_cover' => false, 'urutan' => 7],
            ['berita_id' => 68, 'file' => '1000257599.jpg', 'is_cover' => false, 'urutan' => 8],
            ['berita_id' => 68, 'file' => '1000257590.jpg', 'is_cover' => false, 'urutan' => 9],
            ['berita_id' => 68, 'file' => '1000258002.jpg', 'is_cover' => false, 'urutan' => 10],
            ['berita_id' => 68, 'file' => '1000257996.jpg', 'is_cover' => false, 'urutan' => 11],
            ['berita_id' => 68, 'file' => '1000257789.jpg', 'is_cover' => false, 'urutan' => 12],
            ['berita_id' => 68, 'file' => '1000257786.jpg', 'is_cover' => false, 'urutan' => 13],
            ['berita_id' => 68, 'file' => '1000257792.jpg', 'is_cover' => false, 'urutan' => 14],
            ['berita_id' => 68, 'file' => '1000257795.jpg', 'is_cover' => false, 'urutan' => 15],
            
            // Berita ID 73: kegiatan-donor-darah-di-masjid-raudhotul-jannah-tce-sebagai-upaya-kepedulian-sosial-dan-kesehatan
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-13.34.46.jpeg', 'is_cover' => true, 'urutan' => 0],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-13.34.47.jpeg', 'is_cover' => false, 'urutan' => 1],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.16.jpeg', 'is_cover' => false, 'urutan' => 2],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.17-(1).jpeg', 'is_cover' => false, 'urutan' => 3],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.17-(2).jpeg', 'is_cover' => false, 'urutan' => 4],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.17.jpeg', 'is_cover' => false, 'urutan' => 5],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.18-(1).jpeg', 'is_cover' => false, 'urutan' => 6],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.18-(2).jpeg', 'is_cover' => false, 'urutan' => 7],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.18.jpeg', 'is_cover' => false, 'urutan' => 8],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.19-(1).jpeg', 'is_cover' => false, 'urutan' => 9],
            ['berita_id' => 73, 'file' => 'WhatsApp-Image-2026-04-20-at-12.45.19.jpeg', 'is_cover' => false, 'urutan' => 10],
        ];

        $masjid = 'mrj';

        foreach ($mediaData as $data) {
            $berita = Berita::find($data['berita_id']);
            if (!$berita) {
                echo "❌ Berita ID {$data['berita_id']} tidak ditemukan\n";
                continue;
            }

            $slug = $berita->slug;
            $imagePath = "{$masjid}/berita/{$slug}/{$data['file']}";

            // Cek apakah sudah ada
            $exists = BeritaMedia::where('berita_id', $data['berita_id'])
                ->where('image_path', $imagePath)
                ->exists();

            if ($exists) {
                continue;
            }

            BeritaMedia::create([
                'berita_id'   => $data['berita_id'],
                'masjid_code' => $masjid,
                'image_path'  => $imagePath,
                'file_name'   => $data['file'],
                'is_cover'    => $data['is_cover'],
                'urutan'      => $data['urutan'],
            ]);

            echo "✅ {$berita->judul} - {$data['file']}\n";
        }

        echo "\n✨ Selesai! Total: " . BeritaMedia::count() . " data\n";
    }

    public function down(): void
    {
        BeritaMedia::truncate();
    }
};