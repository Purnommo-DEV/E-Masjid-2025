<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pendaftaran Santunan Ramadhan
    |--------------------------------------------------------------------------
    |
    | Nilai ini menjadi satu-satunya sumber status untuk halaman formulir,
    | endpoint penyimpanan, dan tombol ajakan pada daftar penerima.
    |
    */
    'registration_open' => env('SANTUNAN_RAMADHAN_REGISTRATION_OPEN', true),

    /*
    | Tahun program yang secara resmi tersedia untuk halaman publik dan
    | konfigurasi tanggal acuan. Tambahkan tahun baru melalui konfigurasi
    | deployment, bukan melalui hidden field dari browser.
    */
    'available_years' => [2026, 2027],
];
