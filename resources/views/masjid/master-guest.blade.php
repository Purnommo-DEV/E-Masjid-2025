<!DOCTYPE html>
<html lang="id" data-theme="night">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- TITLE DINAMIS --}}
    <title>
        @hasSection('title')
            @yield('title') — E-Masjid
        @else
            E-Masjid — Sistem Informasi Masjid
        @endif
    </title>

    {{-- SEO DESCRIPTION DINAMIS --}}
    <meta name="description"
          content="@yield('meta_description', 'Sistem Informasi Masjid berbasis web untuk pengelolaan kegiatan, keuangan, dan informasi jamaah.')">

    {{-- CSRF TOKEN (butuh untuk form/axios/fetch) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="canonical" href="{{ url()->current() }}">
    
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#059669">
    <meta name="description" content="Sistem Informasi Masjid - Jadwal Sholat, Donasi, Kajian & Komunitas">
    <!-- iOS support -->
    <link rel="apple-touch-icon" href="{{ asset('/pwa/icon-192.png') }}">

    {{-- OG TAGS (OPTIONAL, BAGUS UNTUK SHARE LINK) --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'E-Masjid — Sistem Informasi Masjid')">
    <meta property="og:description"
          content="@yield('meta_description', 'Sistem Informasi Masjid berbasis web untuk pengelolaan kegiatan, keuangan, dan informasi jamaah.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/masjid-cover.jpg') }}"> {{-- ganti jika sudah ada --}}

    {{-- FAVICON (OPTIONAL) --}}
    <link rel="icon" type="image/png" href="{{ asset('/pwa/mrj-logo.png') }}">

    {{-- FONT (OPSIONAL, KALAU MAU PAKAI GOOGLE FONT) --}}
    {{-- <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet"> --}}

    {{-- VITE (TAILWIND + DAISYUI) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- SWEETALERT --}}
    @include('sweetalert::alert')

    {{-- KALAU ADA STYLE TAMBAHAN DI HALAMAN2 TERTENTU --}}
    @stack('head')

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "PlaceOfWorship",
      "name": "Masjid [Nama]",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "{{ $profil->alamat }}",
        "addressLocality": "Kota",
        "addressCountry": "ID"
      },
      "openingHours": "Mo-Su 00:00-23:59",  // atau spesifik sholat
      "telephone": "{{ $profil->telepon }}",
      "url": "{{ url('/') }}"
    }
    </script>
</head>

<body class="bg-slate-950 text-slate-100 overflow-x-hidden min-h-screen">

    {{-- NAVBAR FRONTEND --}}
    @include(guest_layout('_navbar'))

    {{-- KONTEN HALAMAN --}}
    <main class="min-h-[calc(100vh-56px)]">
        @yield('content')
    </main>

    {{-- FOOTER FRONTEND --}}
    @include(guest_layout('_footer'))

    {{-- SCRIPT GLOBAL FRONTEND --}}
    <script>
        // Smooth scroll untuk link anchor (#jadwal, #acara, dll)
        document.addEventListener('DOMContentLoaded', function () {
            const links = document.querySelectorAll('a[href^="#"]');

            links.forEach(link => {
                link.addEventListener('click', function (e) {
                    const targetId = this.getAttribute('href').substring(1);
                    const targetEl = document.getElementById(targetId);

                    if (targetEl) {
                        e.preventDefault();
                        targetEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                });
            });
        });
    </script>

    {{-- SCRIPT TAMBAHAN DARI HALAMAN LAIN --}}
    @stack('scripts')

</body>
</html>
