@extends('masjid.master-guest')
@push('head')

    {{-- BASIC SEO --}}
    <title>Pendaftaran Santunan Ditutup | Masjid Raudhotul Jannah</title>
    <meta name="description" content="Pengumuman penutupan pendaftaran santunan anak yatim dan dhuafa Program Ramadhan 1447 H di Masjid Raudhotul Jannah.">

    {{-- OPEN GRAPH (WA & FB) --}}
    <meta property="og:type" content="article">
    <meta property="og:title" content="Pendaftaran Santunan Ramadhan 1447H Telah Ditutup">
    <meta property="og:description" content="Pendaftaran santunan anak yatim & dhuafa Program Ramadhan 1447 H di Masjid Raudhotul Jannah telah ditutup.">
    <meta property="og:image" content="{{ secure_url('images/default-ramadhan.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Masjid Raudhotul Jannah">
    <meta property="og:locale" content="id_ID">

    {{-- FIX WA IMAGE --}}
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- TWITTER --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pendaftaran Santunan Ramadhan 1447H Telah Ditutup">
    <meta name="twitter:description" content="Pengumuman penutupan pendaftaran santunan Ramadhan Masjid Raudhotul Jannah.">
    <meta name="twitter:image" content="{{ secure_url('images/default-ramadhan.jpg') }}">

@endpush
@section('content')
<!-- ENDAFTARAN DITUTUP -->
<section class="yg-sudah-ditutup py-16 md:py-20 bg-gradient-to-br from-emerald-50 via-white to-teal-50">
    <div class="container mx-auto px-4 lg:px-6 max-w-4xl">
        <div class="bg-white rounded-3xl shadow-xl border border-emerald-100/70 overflow-hidden p-8 md:p-12 text-center">
            <!-- Logo di atas -->
            <div class="flex items-center justify-center gap-4 mb-8">
                <img src="{{ asset('mrj-mtrj.png') }}"
                     alt="Logo Masjid Raudhotul Jannah & MTRJ"
                     class="h-20 sm:h-24 md:h-32 drop-shadow-lg">
            </div>

            <div class="mb-8">
                <h2 class="text-3xl md:text-4xl font-bold text-emerald-800 mb-2 font-arabic">
                    بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ
                </h2>
            </div>
            <div class="mb-8">
                <h3 class="text-2xl md:text-3xl font-extrabold text-emerald-700">
                    PENGUMUMAN
                </h3>
            </div>

            <div class="prose prose-lg md:prose-xl max-w-none text-slate-800 leading-relaxed space-y-6">
                <p class="text-xl md:text-2xl font-semibold text-emerald-800">
                    Assalamu’alaikum warahmatullahi wabarakatuh.
                </p>

                <p>
                    Dengan ini kami sampaikan bahwa pendaftaran <strong>Pendataan Santunan Anak Yatim & Anak Dhuafa</strong><br>
                    <strong>Program Ramadhan 1447 H / 2026</strong><br>
                    <strong>Masjid Raudhotul Jannah & Majelis Ta’lim Raudhotul Jannah (MTRJ)</strong> <strong>telah ditutup</strong>.
                </p>

                <p>
                    Kami ucapkan <strong>jazaakumullaahu khairan katsiiran</strong> atas perhatian, kepedulian, dan partisipasi<br>
                    Bapak/Ibu/Saudara/i serta para wali.
                </p>

                <p class="font-medium text-emerald-700 italic">
                    Semoga Allah ﷻ menerima seluruh amal kebaikan kita, melapangkan rezeki, serta memberikan keberkahan<br>
                    kepada kita semua di dunia dan akhirat. Aamiin ya Rabbal ‘aalamiin.
                </p>

                <p class="text-xl md:text-2xl font-semibold text-emerald-800 mt-10">
                    Wassalamu’alaikum warahmatullahi wabarakatuh.
                </p>
            </div>

            <div class="mt-12 pt-8 border-t border-emerald-100">
                <p class="text-lg font-semibold text-emerald-800">
                    Masjid Raudhotul Jannah<br>
                    Majelis Ta’lim Raudhotul Jannah (MTRJ)<br>
                    Ramadhan 1447 H / 2026
                </p>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endpush