<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Image Upload Configuration
    |--------------------------------------------------------------------------
    */
    'image' => [
        'max_size' => 2048, // KB
        'mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
        'quality' => 80,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Folder Structure
    |--------------------------------------------------------------------------
    */
    'folders' => [
        'banner' => 'banner',
        'acara' => 'acara',
        'berita' => 'berita',
        'logo' => 'logo',
        'galeri' => 'galeri',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Images
    |--------------------------------------------------------------------------
    */
    'defaults' => [
        'banner' => 'assets/e-masjid/images/masjid-banner.jpg',
        'logo' => 'assets/e-masjid/images/logo-default.png',
        'acara' => 'assets/e-masjid/images/acara-default.jpg',
        'berita' => 'assets/e-masjid/images/berita-default.jpg',
    ],
];