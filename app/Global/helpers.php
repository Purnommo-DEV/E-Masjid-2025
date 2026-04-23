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

// app/helpers.php

if (!function_exists('convert_to_webp')) {
    function convert_to_webp($file, ?int $quality = null): string
    {
        $sourcePath = $file->getPathname();
        $mimeType = $file->getMimeType();
        $fileSize = $file->getSize();
        
        // Auto adjust quality untuk file < 2MB
        if ($quality === null) {
            if ($fileSize > 1.5 * 1024 * 1024) {  // > 1.5MB
                $quality = 65;
            } elseif ($fileSize > 1 * 1024 * 1024) { // > 1MB
                $quality = 75;
            } elseif ($fileSize > 500 * 1024) {     // > 500KB
                $quality = 80;
            } else {                                 // <= 500KB
                $quality = 85;
            }
        }

        Log::info("Convert to WebP - Size: " . round($fileSize / 1024, 2) . "KB, Quality: {$quality}");

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
                return file_get_contents($sourcePath);
            default:
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
    }
}

// app/helpers.php

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
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
            return true;
        }
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