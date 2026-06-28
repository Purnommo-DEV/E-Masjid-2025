<?php

use App\Models\AkunKeuangan;
use App\Models\ProfilMasjid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


if (!function_exists('masjid')) {
    function masjid(): string
    {
        return strtolower(env('APP_MASJID', 'default'));
    }
}

if (!function_exists('profil')) {
    function profil(string $key = null): ?string
    {
        $profil = ProfilMasjid::first();

        if (!$profil) {
            return null;
        }

        if (is_null($key)) {
            return $profil;
        }

        return $profil->$key ?? null;
    }
}

// Helper untuk nomor WA clean (tanpa format)
if (!function_exists('normalizeWaNumber')) {
    function normalizeWaNumber($phone = null, string $default = '62895704043814'): string
    {
        $clean = preg_replace('/[^0-9]/', '', $phone ?: $default);

        if (substr($clean, 0, 1) === '0') {
            return '62' . substr($clean, 1);
        }

        if (substr($clean, 0, 2) !== '62') {
            return '62' . $clean;
        }

        return $clean;
    }
}

if (!function_exists('waNumberClean')) {
    function waNumberClean($default = '62895704043814')
    {
        return normalizeWaNumber(profil('telepon'), $default);
    }
}

// Helper untuk nomor WA dengan format strip (untuk display)
if (!function_exists('waNumberFormatted')) {
    function waNumberFormatted($default = '62895704043814')
    {
        $noWa = profil('telepon');
        
        if (!$noWa) {
            $noWa = $default;
        }
        
        $clean = preg_replace('/[^0-9]/', '', $noWa);
        
        // Format lokal Indonesia (awalan 0)
        if (substr($clean, 0, 2) === '62') {
            $clean = '0' . substr($clean, 2);
        }
        
        // Format dengan strip: 0812-3456-7890
        if (strlen($clean) >= 11) {
            if (strlen($clean) === 11) {
                return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
            } else {
                return substr($clean, 0, 4) . '-' . substr($clean, 4, 4) . '-' . substr($clean, 8);
            }
        }
        
        return $clean;
    }
}

if (!function_exists('waNumberInternational')) {
    function waNumberInternational($default = '62895704043814')
    {
        return normalizeWaNumber(profil('telepon'), $default);
    }
}

if (!function_exists('masjid_config')) {
    function masjid_config(string $key, $default = null)
    {
        $kode = masjid();
        return config("masjids.{$kode}.{$key}", config("masjids.default.{$key}", $default));
    }
}

if (!function_exists('masjid_name')) {
    function masjid_name(): string
    {
        return masjid_config('name', config('app.masjid_name', 'Masjid'));
    }
}

if (!function_exists('seo_page')) {
    function seo_page(string $pageKey, \RalphJSmit\Laravel\SEO\Support\SEOData $fallback): \RalphJSmit\Laravel\SEO\Support\SEOData
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('seo_pages')) {
            return $fallback;
        }

        $seoPage = \App\Models\SeoPage::where('page_key', $pageKey)->first();

        if (!$seoPage) {
            return $fallback;
        }

        $seoData = clone $fallback;
        $seoData->title = $seoPage->title ?: $fallback->title;
        $seoData->description = $seoPage->description ?: $fallback->description;
        $seoData->image = $seoPage->image ?: $fallback->image;
        $seoData->canonical_url = $seoPage->canonical_url ?: $fallback->canonical_url;
        $seoData->robots = $seoPage->robots ?: $fallback->robots;

        return $seoData;
    }
}

if (!function_exists('masjid_address')) {
    function masjid_address(): string
    {
        return masjid_config('address', '');
    }
}

if (!function_exists('admin_layout')) {
    function admin_layout(string $partial = ''): string
    {
        $slug = masjid();
        $base = "masjid.{$slug}.admin.layouts";
        $default = "masjid.default.admin.layouts";

        $path = $base;
        if ($partial) {
            $path .= ".{$partial}";
        }

        return view()->exists($path) ? $path : ($default . ($partial ? ".{$partial}" : ''));
    }
}

if (!function_exists('guest_layout')) {
    function guest_layout(string $partial = ''): string
    {
        $slug = masjid();
        $base = "masjid.{$slug}.guest.layouts";
        $default = "masjid.default.guest.layouts";

        $path = $base;
        if ($partial) {
            $path .= ".{$partial}";
        }

        return view()->exists($path) ? $path : ($default . ($partial ? ".{$partial}" : ''));
    }
}

if (!function_exists('akunIdByKode')) {
    function akunIdByKode(string $kode): int
    {
        $id = AkunKeuangan::where('kode', $kode)->value('id');

        if (!$id) {
            throw new \RuntimeException("Akun dengan kode {$kode} tidak ditemukan");
        }

        return $id;
    }
}

if (!function_exists('terbilang')) {
    function terbilang($x)
    {
        $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($x < 12) return " " . $abil[$x];
        elseif ($x < 20) return terbilang($x - 10) . " belas";
        elseif ($x < 100) return terbilang($x / 10) . " puluh" . terbilang($x % 10);
        elseif ($x < 200) return " seratus" . terbilang($x - 100);
        elseif ($x < 1000) return terbilang($x / 100) . " ratus" . terbilang($x % 100);
        elseif ($x < 2000) return " seribu" . terbilang($x - 1000);
        elseif ($x < 1000000) return terbilang($x / 1000) . " ribu" . terbilang($x % 1000);
        elseif ($x < 1000000000) return terbilang($x / 1000000) . " juta" . terbilang($x % 1000000);
        elseif ($x < 1000000000000) return terbilang($x / 1000000000) . " miliar" . terbilang(fmod($x, 1000000000));
    }
}

if (!function_exists('public_html_path')) {
    function public_html_path($path = '')
    {
        if (!is_dir(base_path('../public_html'))) {
            return public_path($path);
        }

        return base_path('../public_html/' . $path);
    }
}

// ============================================
// IMAGE UPLOAD FUNCTIONS
// ============================================

if (!function_exists('convert_to_webp')) {
    function convert_to_webp($file, ?int $quality = null): string
    {
        $sourcePath = $file->getPathname();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        $extension = $file->getClientOriginalExtension();
        
        // Handle HEIC/HEIF format
        if (strtolower($extension) === 'heic' || $mimeType === 'image/heic' || $mimeType === 'image/heif') {
            // Cek apakah ImageMagick tersedia
            if (extension_loaded('imagick')) {
                try {
                    $imagick = new \Imagick($sourcePath);
                    $imagick->setImageFormat('webp');
                    
                    // Auto quality
                    if ($quality === null) {
                        if ($fileSize > 1.5 * 1024 * 1024) {
                            $quality = 65;
                        } elseif ($fileSize > 1 * 1024 * 1024) {
                            $quality = 75;
                        } else {
                            $quality = 85;
                        }
                    }
                    
                    $imagick->setImageCompressionQuality($quality);
                    $webpContent = $imagick->getImageBlob();
                    $imagick->destroy();
                    
                    Log::info("Converted HEIC to WebP - Quality: {$quality}");
                    return $webpContent;
                    
                } catch (\Exception $e) {
                    Log::error("Imagick HEIC conversion failed: " . $e->getMessage());
                    throw new \Exception("Gagal mengkonversi file HEIC. Pastikan ImageMagick support HEIC.");
                }
            } else {
                throw new \Exception("Format HEIC tidak didukung. Silakan install Imagick atau konversi file ke JPG/PNG terlebih dahulu.");
            }
        }
        
        // Auto adjust quality untuk file < 2MB
        if ($quality === null) {
            if ($fileSize > 1.5 * 1024 * 1024) {
                $quality = 65;
            } elseif ($fileSize > 1 * 1024 * 1024) {
                $quality = 75;
            } elseif ($fileSize > 500 * 1024) {
                $quality = 80;
            } else {
                $quality = 85;
            }
        }

        Log::info("Convert to WebP - Size: " . round($fileSize / 1024, 2) . "KB, Quality: {$quality}, Type: {$mimeType}");

        // Proses file non-HEIC
        $image = null;
        
        try {
            switch ($mimeType) {
                case 'image/jpeg':
                case 'image/jpg':
                    $image = imagecreatefromjpeg($sourcePath);
                    break;
                case 'image/png':
                    $image = imagecreatefrompng($sourcePath);
                    if ($image) {
                        imagepalettetotruecolor($image);
                        imagealphablending($image, true);
                        imagesavealpha($image, true);
                    }
                    break;
                case 'image/gif':
                    $image = imagecreatefromgif($sourcePath);
                    break;
                case 'image/webp':
                    $image = imagecreatefromwebp($sourcePath);
                    break;
                default:
                    // Coba dengan Imagick sebagai fallback
                    if (extension_loaded('imagick')) {
                        $imagick = new \Imagick($sourcePath);
                        $imagick->setImageFormat('webp');
                        $webpContent = $imagick->getImageBlob();
                        $imagick->destroy();
                        return $webpContent;
                    }
                    return file_get_contents($sourcePath);
            }

            if (!$image) {
                return file_get_contents($sourcePath);
            }

            ob_start();
            imagewebp($image, null, $quality);
            $webpContent = ob_get_clean();
            imagedestroy($image);

            return $webpContent;
            
        } catch (\Exception $e) {
            Log::error("Image conversion failed: " . $e->getMessage());
            return file_get_contents($sourcePath);
        }
    }
}

if (!function_exists('upload_image')) {
    function upload_image($file, string $type, ?string $oldPath = null, bool $convertToWebp = true, ?int $quality = null): string
    {
        // Validasi ukuran file dengan pesan error jelas
        $fileSize = $file->getSize();
        $fileSizeMB = round($fileSize / 1024 / 1024, 2);
        $maxSizeMB = 2;
        
        if ($fileSize > $maxSizeMB * 1024 * 1024) {
            throw new \Exception("File {$file->getClientOriginalName()} berukuran {$fileSizeMB}MB melebihi batas maksimal {$maxSizeMB}MB. Silakan kompres file terlebih dahulu.");
        }
        
        // Hapus file lama
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $masjid = masjid();
        $folder = "{$masjid}/{$type}";

        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        if ($convertToWebp && function_exists('imagewebp')) {
            $webpContent = convert_to_webp($file, $quality);
            $filename = time() . '_' . Str::random(10) . '.webp';
            $path = "{$folder}/{$filename}";
            Storage::disk('public')->put($path, $webpContent);
        } else {
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            $path = "{$folder}/{$filename}";
            Storage::disk('public')->put($path, file_get_contents($file));
        }

        Log::info('Upload image: ' . $path);

        return $path;
    }
}

if (!function_exists('optimize_image')) {
    function optimize_image($file, int $maxWidth = 1200, int $maxHeight = 800, int $quality = 80): string
    {
        $sourcePath = $file->getPathname();
        $mimeType = $file->getMimeType();

        switch ($mimeType) {
            case 'image/jpeg':
            case 'image/jpg':
                $image = imagecreatefromjpeg($sourcePath);
                break;
            case 'image/png':
                $image = imagecreatefrompng($sourcePath);
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($sourcePath);
                break;
            case 'image/webp':
                $image = imagecreatefromwebp($sourcePath);
                break;
            default:
                return file_get_contents($sourcePath);
        }

        if (!$image) {
            return file_get_contents($sourcePath);
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        if ($originalWidth > $maxWidth || $originalHeight > $maxHeight) {
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = round($originalWidth * $ratio);
            $newHeight = round($originalHeight * $ratio);

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            if ($mimeType === 'image/png') {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
            imagedestroy($image);
            $image = $resizedImage;
        }

        ob_start();
        imagewebp($image, null, $quality);
        $webpContent = ob_get_clean();
        imagedestroy($image);

        return $webpContent;
    }
}

if (!function_exists('upload_image_optimized')) {
    function upload_image_optimized($file, string $type, ?string $oldPath = null, array $options = []): string
    {
        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $masjid = masjid();
        $folder = "{$masjid}/{$type}";

        if (!Storage::disk('public')->exists($folder)) {
            Storage::disk('public')->makeDirectory($folder);
        }

        $maxWidth = $options['max_width'] ?? 1200;
        $maxHeight = $options['max_height'] ?? 800;
        $quality = $options['quality'] ?? 80;

        $webpContent = optimize_image($file, $maxWidth, $maxHeight, $quality);
        $filename = time() . '_' . Str::random(10) . '.webp';
        $path = "{$folder}/{$filename}";

        Storage::disk('public')->put($path, $webpContent);

        Log::info('Upload optimized image: ' . $path);

        return $path;
    }
}

if (!function_exists('delete_image')) {
    function delete_image(?string $path): bool
    {
        if (empty($path)) {
            return false;
        }
        
        // Bersihkan path dari prefix /storage/ atau storage/
        $cleanPath = $path;
        
        // Hapus prefix /storage/ jika ada
        $cleanPath = preg_replace('#^/storage/#', '', $cleanPath);
        $cleanPath = preg_replace('#^storage/#', '', $cleanPath);
        
        // Hapus domain jika ada (misal http://localhost/storage/...)
        if (filter_var($cleanPath, FILTER_VALIDATE_URL)) {
            $parsed = parse_url($cleanPath);
            $cleanPath = $parsed['path'] ?? '';
            $cleanPath = preg_replace('#^/storage/#', '', $cleanPath);
        }
        
        if (empty($cleanPath)) {
            return false;
        }
        
        // Cek dan hapus file
        if (Storage::disk('public')->exists($cleanPath)) {
            Storage::disk('public')->delete($cleanPath);
            \Illuminate\Support\Facades\Log::info('Image deleted: ' . $cleanPath);
            return true;
        }
        
        \Illuminate\Support\Facades\Log::warning('Image not found: ' . $cleanPath);
        return false;
    }
}

if (!function_exists('get_image_url')) {
    function get_image_url(?string $path): string
    {
        if (!$path) {
            return '';
        }

        if (Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return '';
    }
}
