<?php

return [
    // Tangerang Selatan & sekitar (valid dari list API kamu)
    'tangerang selatan'     => '1108',
    'tangsel'               => '1108',
    'kota tangerang selatan'=> '1108',
    'tangerang'             => '1107',  // KOTA TANGERANG (jika user bilang "Tangerang" umum)
    'kota tangerang'        => '1107',
    'kabupaten tangerang'   => '1104',

    // Jakarta (dari list: 1301 untuk KOTA JAKARTA, tapi kalau spesifik wilayah, pakai fallback ke 1301)
    'jakarta'               => '1301',
    'jakarta selatan'       => '1301',  // fallback ke kota jakarta utama, karena API tidak pisah detail kecamatan
    'jakarta timur'         => '1301',
    'jakarta barat'         => '1301',
    'jakarta pusat'         => '1301',
    'jakarta utara'         => '1301',

    // Bandung & sekitar (prioritas utama karena user di Bandung)
    'bandung'               => '1219',  // KOTA BANDUNG (valid & paling pas)
    'kota bandung'          => '1219',
    'kab. bandung'          => '1201',  // KAB. BANDUNG (kabupaten)
    'bandung barat'         => '1202',  // KAB. BANDUNG BARAT
    'kab. bandung barat'    => '1202',

    // Kota-kota besar lain (disesuaikan dengan list API kamu, pakai ID 4 digit)
    'surabaya'              => '1638',  // KOTA SURABAYA
    'semarang'              => '1433',  // KOTA SEMARANG
    'yogyakarta'            => '1505',  // KOTA YOGYAKARTA
    'makassar'              => '2622',  // KOTA MAKASSAR
    'medan'                 => '0228',  // KOTA MEDAN
    'palembang'             => '1671',  // KOTA PALEMBANG (asumsi valid, kalau tidak match cek list)
    'denpasar'              => '1709',  // KOTA DENPASAR
    'balikpapan'            => '2308',  // KOTA BALIKPAPAN
    'samarinda'             => '2310',  // KOTA SAMARINDA
    'pontianak'             => '2013',  // KOTA PONTIANAK
    'banjarmasin'           => '2113',  // KOTA BANJARMASIN
    'manado'                => '2914',  // KOTA MANADO
    'jayapura'              => '3329',  // KOTA JAYAPURA

    // Tambahan kota populer lain dari list API (bisa ditambah sesuai kebutuhan)
    'bekasi'                => '1221',  // KOTA BEKASI
    'bogor'                 => '1222',  // KOTA BOGOR
    'depok'                 => '1225',  // KOTA DEPOK
    'bandar lampung'        => '1014',  // KOTA BANDAR LAMPUNG
];