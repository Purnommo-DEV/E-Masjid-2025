<?php

return [
    'site_name' => 'Masjid Raudhotul Jannah Taman Cipulir Estate',

    'title' => [
        'suffix' => '',
        'homepage_title' => 'Masjid Raudhotul Jannah - Informasi Kegiatan & Kajian',
    ],

    'description' => [
        'fallback' => 'Website resmi Masjid Raudhotul Jannah Taman Cipulir Estate. Informasi kegiatan, kajian, pengumuman, jadwal sholat, dan layanan jamaah di Bandung.',
    ],

    'image' => [
        'fallback' => env('APP_URL') . '/images/default-share.jpg',
    ],

    'author' => [
        'fallback' => 'Tim Masjid Raudhotul Jannah',
    ],

    'twitter' => [
        'card' => 'summary_large_image',
        '@username' => null, // isi kalau punya Twitter/X resmi
    ],

    'canonical_link' => true,
    'robots' => [
        'default' => 'index,follow',
    ],
];