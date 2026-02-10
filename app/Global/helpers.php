<?php
use App\Models\AkunKeuangan;
use App\Models\ProfilMasjid;

if (!function_exists('masjid')) {
    /**
     * Ambil kode masjid dari .env
     * @return string
     */
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

        // Jika tidak ada $key, kembalikan seluruh object
        if (is_null($key)) {
            return $profil;
        }

        // Ambil attribute yang diminta
        return $profil->$key ?? null;
    }
}

if (!function_exists('masjid_config')) {
    /**
     * Ambil konfigurasi masjid saat ini
     */
    function masjid_config(string $key, $default = null)
    {
        $kode = masjid();
        return config("masjids.{$kode}.{$key}", config("masjids.default.{$key}", $default));
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

        if (! $id) {
            throw new \RuntimeException("Akun dengan kode {$kode} tidak ditemukan");
        }

        return $id;
    }
}

if (!function_exists('terbilang')) {
    function terbilang($x) {
        $abil = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
        if ($x < 12) return " " . $abil[$x];
        elseif ($x < 20) return terbilang($x - 10) . " belas";
        elseif ($x < 100) return terbilang($x / 10) . " puluh" . terbilang($x % 10);
        elseif ($x < 200) return " seratus" . terbilang($x - 100);
        elseif ($x < 1000) return terbilang($x / 100) . " ratus" . terbilang($x % 100);
        elseif ($x < 2000) return " seribu" . terbilang($x - 1000);
        elseif ($x < 1000000) return terbilang($x / 1000) . " ribu" . terbilang($x % 1000);
        elseif ($x < 1000000000) return terbilang($x / 1000000) . " juta" . terbilang($x % 1000000);
        elseif ($x < 1000000000000) return terbilang($x / 1000000000) . " miliar" . terbilang(fmod($x,1000000000));
    }
}