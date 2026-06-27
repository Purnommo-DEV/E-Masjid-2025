    @php
        $homeProfil = $profil ?? null;
        $siteName = $homeProfil?->nama ?: 'Masjid Raudhotul Jannah Taman Cipulir Estate';
        $rawDescription = $homeProfil?->deskripsi ?: ($homeProfil?->tagline ?: 'Portal resmi Masjid Raudhotul Jannah untuk jadwal sholat, kajian, agenda kegiatan, berita jamaah, layanan umat, galeri, dan donasi.');
        $seoDescription = Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($rawDescription))), 158, '');
        $canonicalUrl = url()->current();
        $shareImage = $homeProfil?->logo_url ?: asset('/pwa/mrj-logo.png');
        if (! Str::startsWith($shareImage, ['http://', 'https://'])) {
            $shareImage = asset(ltrim($shareImage, '/'));
        }
        $phone = $homeProfil?->telepon ?: ($homeProfil?->no_wa ?: null);
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'Mosque',
            'name' => $siteName,
            'url' => $canonicalUrl,
            'description' => $seoDescription,
            'image' => $shareImage,
            'logo' => $shareImage,
            'telephone' => $phone,
            'email' => $homeProfil?->email,
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $homeProfil?->alamat ?: 'Taman Cipulir Estate',
                'addressCountry' => 'ID',
            ],
            'openingHours' => 'Mo-Su 04:00-22:00',
            'sameAs' => array_values(array_filter([
                $homeProfil?->website ?? null,
                $homeProfil?->instagram ?? null,
                $homeProfil?->facebook ?? null,
            ])),
        ];
    @endphp

    <meta name="description" content="{{ $seoDescription }}">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="keywords" content="{{ $siteName }}, masjid, jadwal sholat, kajian Islam, agenda masjid, infaq, donasi masjid, layanan jamaah, Taman Cipulir Estate">
    <meta name="author" content="{{ $siteName }}">

    <meta property="og:type" content="website">
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $siteName }} - Jadwal Sholat, Agenda & Layanan Jamaah">
    <meta property="og:description" content="{{ $seoDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:image:alt" content="Logo dan informasi {{ $siteName }}">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $siteName }} - Portal Informasi Umat">
    <meta name="twitter:description" content="{{ $seoDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">

    <link rel="preconnect" href="https://www.google.com">
    <link rel="dns-prefetch" href="//www.google.com">

    <script type="application/ld+json">
        {!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) !!}
    </script>

    <script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
    <style>
        @media (max-width: 480px) {
            #jadwal .prayer-grid > div .text-xs,
            #jadwal .prayer-grid > div .text-sm {
                font-size: 0.75rem !important;
            }
            #jadwal .prayer-grid > div .text-lg,
            #jadwal .prayer-grid > div .text-xl {
                font-size: 1.25rem !important;
            }
        }

        @media (max-width: 640px) {
            #donasi .grid.md\:grid-cols-2 {
                gap: 1rem;
            }
            #donasi .bg-gradient-to-br.from-emerald-50\/80 {
                padding: 1rem;
            }
            #donasi .w-14.h-14 {
                width: 3rem;
                height: 3rem;
            }
            #donasi .text-3xl {
                font-size: 1.5rem;
            }
            #donasi [style*="width: 190px"] {
                width: 150px !important;
                height: 150px !important;
            }
        }
    </style>