<?php

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