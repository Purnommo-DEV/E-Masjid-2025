<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- TITLE --}}
    <title>
        @hasSection('title')
            @yield('title') — E-Masjid
        @else
            E-Masjid — Sistem Informasi Masjid
        @endif
    </title>

    {{-- SEO --}}
    <meta name="description"
          content="@yield('meta_description', 'Website resmi Masjid Raudhotul Jannah Taman Cipulir Estate. Informasi kegiatan, kajian, dan layanan jamaah.')">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#059669">
    <link rel="apple-touch-icon" href="{{ asset('/pwa/icon-128.png') }}">

    {{-- OPEN GRAPH --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="@yield('title', 'Masjid Raudhotul Jannah')">
    <meta property="og:description"
          content="@yield('meta_description', 'Kegiatan dan informasi Masjid Raudhotul Jannah Taman Cipulir Estate.')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/masjid-cover.jpg') }}">
    <meta property="og:locale" content="id_ID">

    {{-- FAVICON --}}
    <link rel="icon" type="image/png" href="{{ asset('/pwa/mrj-logo.png') }}">

    {{-- VITE --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('sweetalert::alert')
    @stack('head')

    <!-- GLOBAL SCROLL FIX -->
    <style>
        html{
            scroll-behavior: smooth;
            scroll-padding-top: 95px;
        }

        /* agar section tidak ketutup navbar */
        section{
            scroll-margin-top: 110px;
        }

        /* footer selalu nempel */
        body{
            display:flex;
            flex-direction:column;
            min-height:100vh;
        }

        main{
            flex:1;
        }
    </style>
</head>

<body class="bg-white text-slate-100 overflow-x-hidden">
    {{-- NAVBAR --}}
    @include(guest_layout('_navbar'))

    {{-- CONTENT --}}
    <main>
        @yield('content')
    </main>

    {{-- FOOTER --}}
    @include(guest_layout('_footer'))

    <!-- SCROLL HASH (#!jadwal dll) -->
    <script>
    document.addEventListener("DOMContentLoaded", function () {

        function scrollToHash(){
            const hash = window.location.hash;

            if(hash.startsWith("#!")){
                const id = hash.replace("#!","");
                const el = document.getElementById(id);

                if(el){
                    setTimeout(()=>{
                        const yOffset = -90;
                        const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                        window.scrollTo({top:y,behavior:"smooth"});
                    },300);
                }
            }
        }

        scrollToHash();
        window.addEventListener("hashchange", scrollToHash);
    });
    </script>

    @stack('scripts')

</body>
</html>