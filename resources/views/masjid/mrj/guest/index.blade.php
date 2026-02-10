@extends('masjid.master-guest')
@section('title', 'Home')

@section('content')
    {{-- LOADER FULLSCREEN --}}
    <div id="page-loader"
         class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-gradient-to-b from-emerald-50 via-white to-sky-50 transition-opacity duration-500">
        <div class="flex flex-col items-center gap-4">
            <div class="w-16 h-16 rounded-3xl bg-white shadow-xl border border-emerald-100 flex items-center justify-center">
                <div class="loading loading-ring loading-lg text-emerald-600"></div>
            </div>
            <div class="text-center space-y-1">
                <p class="text-sm font-semibold text-emerald-800">
                    Memuat Sistem Informasi Masjid...
                </p>
                <p class="text-[11px] text-slate-500">
                    Mohon tunggu sebentar, sedang menyiapkan data jamaah & jadwal sholat.
                </p>
            </div>
        </div>
    </div>

    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50 relative overflow-hidden">

        {{-- HERO --}}
        <section class="relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-teal-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
                <div class="absolute top-40 right-0 w-[600px] h-[600px] bg-cyan-200 rounded-full blur-3xl opacity-25 animate-pulse delay-1000"></div>
            </div>

            <div class="container mx-auto px-5 lg:px-8 relative">
                <div class="grid lg:grid-cols-2 gap-12 xl:gap-16 items-center">
                    <!-- Hero Text -->
                    <div class="space-y-8 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/60 backdrop-blur-sm border border-emerald-200/50 shadow-sm text-sm text-emerald-800">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-70"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                            </span>
                            Selamat Datang di {{ $profil->nama ?? 'Masjid Al-Ikhlas' }}
                        </div>

                        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 leading-tight">
                            Masjid yang Hidup,
                            <span class="block bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent mt-2">
                                Pusat Ibadah & Ukhuwah
                            </span>
                        </h1>

                        <p class="text-base sm:text-lg text-slate-700 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                            {{ e($profil->tagline ?? 'Menghidupkan shalat berjamaah, mempererat silaturahmi, dan menebar kebaikan bagi umat.') }}
                        </p>

                        <div class="flex flex-wrap justify-center lg:justify-start gap-4 mt-8">
                            <a href="#donasi" class="btn btn-lg bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:brightness-110 text-white shadow-xl shadow-teal-500/30 px-10 py-4 text-base font-bold rounded-full transition-all">
                                🌟 Donasi Sekarang
                            </a>
                            <a href="#acara" class="btn btn-lg btn-outline border-2 border-emerald-500 text-emerald-700 hover:bg-emerald-50 hover:border-emerald-600">
                                Lihat Agenda
                            </a>
                        </div>

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 pt-6 max-w-md mx-auto lg:mx-0">
                            <div class="bg-white/70 backdrop-blur-md rounded-2xl border border-emerald-100/50 shadow-sm p-4 text-center">
                                <p class="text-xs text-slate-500">Program Rutin</p>
                                <p class="text-2xl font-bold text-emerald-700 mt-1">+{{ $profil->jumlah_program ?? 12 }}</p>
                            </div>
                            <div class="bg-white/70 backdrop-blur-md rounded-2xl border border-teal-100/50 shadow-sm p-4 text-center">
                                <p class="text-xs text-slate-500">Jamaah Aktif</p>
                                <p class="text-2xl font-bold text-teal-700 mt-1">+{{ $profil->jumlah_jamaah ?? 300 }}</p>
                            </div>
                            <div class="bg-white/70 backdrop-blur-md rounded-2xl border border-cyan-100/50 shadow-sm p-4 text-center">
                                <p class="text-xs text-slate-500">Program Sosial</p>
                                <p class="text-2xl font-bold text-cyan-700 mt-1">+{{ $profil->jumlah_program_sosial ?? 8 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- JADWAL SHOLAT – VERSI BARU & RESPONSIF -->
                    <div id="jadwal" class="w-full max-w-3xl mx-auto lg:mx-0 lg:max-w-none">  <!-- lebar lebih fleksibel di desktop -->
                        <div class="bg-white/85 backdrop-blur-2xl rounded-3xl shadow-2xl shadow-teal-200/40 border border-white/30 overflow-hidden">
                            <div class="p-6 lg:p-8">
                                <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-3">
                                    <h2 class="text-xl lg:text-2xl font-bold text-emerald-900">Jadwal Sholat Hari Ini</h2>
                                    <span class="text-sm font-medium bg-emerald-100 text-emerald-800 px-4 py-1.5 rounded-full whitespace-nowrap">
                                        {{ now()->translatedFormat('l, d M Y') }}
                                    </span>
                                </div>

                                @php
                                    $sholat = [
                                        'subuh'    => ['label' => 'Subuh',    'color' => 'emerald'],
                                        'dzuhur'   => ['label' => 'Dzuhur',   'color' => 'teal'],
                                        'ashar'    => ['label' => 'Ashar',    'color' => 'cyan'],
                                        'maghrib'  => ['label' => 'Maghrib',  'color' => 'amber'],
                                        'isya'     => ['label' => 'Isya',     'color' => 'emerald'],
                                    ];
                                @endphp

                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                    @foreach($sholat as $key => $data)
                                        <div class="relative bg-white/90 rounded-2xl p-4 md:p-4 text-center border border-emerald-100/60 hover:border-emerald-400 transition-all group shadow-sm hover:shadow-md flex flex-col items-center justify-center min-h-[100px] md:min-h-[120px]">
                                            <div class="text-xs md:text-sm font-semibold text-{{ $data['color'] }}-700 uppercase uppercase tracking-wide mb-2 md:mb-3">
                                                {{ $data['label'] }}
                                            </div>
                                            <div class="text-2xl font-extrabold text-slate-900 group-hover:text-teal-700 transition-colors whitespace-nowrap">
                                                {{ $jadwalSholat[$key] ?? '--:--' }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="text-center text-xs md:text-sm text-slate-500 mt-6 md:mt-8 italic">
                                    Waktu sholat berdasarkan lokasi masjid • Sumber: {{ $jadwalSholat['sumber'] ?? 'Kemenag' }}
                                    @if(!empty($jadwalSholat['tanggal_hijriah']))
                                        <br>Hijriah: {{ $jadwalSholat['tanggal_hijriah'] }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- INFO CEPAT --}}
        <section class="pb-10 -mt-2">
            <div class="container mx-auto px-4 lg:px-6">
                @php
                    $infoCepat = [
                        ['icon'=>'🕌','text'=>'Kajian Rutin & Pembinaan Umat'],
                        ['icon'=>'📿','text'=>'Shalat 5 Waktu Berjamaah'],
                        ['icon'=>'🤝','text'=>'Layanan Sosial & Umat'],
                    ];
                @endphp

                <div class="grid md:grid-cols-3 gap-3">
                    @foreach($infoCepat as $info)
                        <div class="flex items-center gap-3 rounded-2xl bg-white/80 border border-emerald-100 px-4 py-3 shadow-sm">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50 text-lg">{{ $info['icon'] }}</div>
                            <p class="text-[12px] text-slate-700">{{ $info['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- === SLIDER BANNER 3 KARTU (DB + SERVICE) === --}}
        <section class="pb-12 -mt-6">
            <div class="container mx-auto px-4 lg:px-6">

                @php
                    // supaya kode bawah tetap pakai variabel $pages
                    $pages = $banner ?? [];
                    $totalPages = count($pages);
                @endphp

                @if($totalPages > 0)
                    <div id="bannerCarousel" class="relative">
                        <div class="rounded-[2rem] bg-gradient-to-r from-emerald-100/70 via-teal-50 to-sky-100/70 p-6 lg:p-8 shadow-[0_18px_45px_rgba(15,118,110,0.18)]">
                            <div class="overflow-hidden rounded-[1.7rem]">
                                <div class="banner-track flex transition-transform duration-700 ease-out snap-x snap-mandatory">
                                    @foreach($pages as $pageBanners)
                                        <div class="banner-page w-full shrink-0 snap-start px-4 lg:px-8">
                                            <div class="grid gap-5 lg:gap-8 lg:grid-cols-3">
                                                @foreach($pageBanners as $banner)
                                                    <div class="relative rounded-3xl overflow-hidden bg-emerald-700 text-white shadow-xl shadow-emerald-900/40 h-full min-h-[250px] flex flex-col">
                                                        <img
                                                            src="{{ $banner['image'] }}"
                                                            alt="{{ $banner['title'] }}"
                                                            class="absolute inset-0 w-full h-full object-cover"
                                                        >
                                                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-900/80 via-emerald-900/70 to-teal-900/70"></div>

                                                        <div class="relative p-5 sm:p-7 flex flex-col justify-between h-full">
                                                            <div class="space-y-2">
                                                                <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/10 text-[11px] backdrop-blur-sm border border-white/20">
                                                                    <span class="mr-1.5">🕌</span> Informasi Masjid
                                                                </div>
                                                                <h3 class="text-lg sm:text-xl font-semibold leading-snug">
                                                                    {{ $banner['title'] }}
                                                                </h3>
                                                                @if(!empty($banner['subtitle']))
                                                                    <p class="text-[12px] sm:text-[13px] text-emerald-50/90">
                                                                        {{ $banner['subtitle'] }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                            <div class="mt-4 flex items-center justify-between text-[11px]">
                                                                @if(!empty($banner['note']))
                                                                    <span class="text-emerald-50/90">
                                                                        {{ $banner['note'] }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-emerald-50/70">&nbsp;</span>
                                                                @endif

                                                                @if(!empty($banner['button']) && !empty($banner['url']))
                                                                    <a href="{{ $banner['url'] }}"
                                                                       class="btn btn-xs bg-white/95 text-emerald-800 border-none hover:bg-emerald-50 rounded-full px-4">
                                                                        {{ $banner['button'] }}
                                                                    </a>
                                                                @else
                                                                    <a href="#acara"
                                                                       class="btn btn-xs bg-white/95 text-emerald-800 border-none hover:bg-emerald-50 rounded-full px-4">
                                                                        Lihat Detail
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- indikator titik per halaman --}}
                        <div class="flex justify-center gap-2 mt-4">
                            @foreach($pages as $i => $page)
                                <button
                                    type="button"
                                    class="banner-dot w-2.5 h-2.5 rounded-full bg-emerald-200 transition-all duration-200"
                                    data-index="{{ $i }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

        {{-- SECTION AGENDA --}}
        <section id="acara" class="py-12 sm:py-16 bg-gradient-to-br from-emerald-50 via-white to-teal-50/50 relative overflow-hidden">
            <!-- Optional subtle background pattern atau blob -->
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-teal-200 rounded-full blur-3xl"></div>
            </div>

            <div class="container mx-auto px-4 lg:px-6">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 sm:mb-10 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">AGENDA TERDEKAT</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 flex items-center gap-3">
                            Kegiatan & Kajian Mendatang
                            <span class="hidden sm:block h-1.5 w-16 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"></span>
                        </h2>
                    </div>
                    <a href="{{ route('acara.index') }}" 
                       class="text-sm sm:text-base text-emerald-700 hover:text-emerald-800 font-semibold inline-flex items-center gap-2 hover:underline transition">
                        Lihat Semua Agenda →
                    </a>
                </div>

                <div class="grid lg:grid-cols-[3fr_1fr] gap-6 xl:gap-8">
                    <!-- List Acara Utama -->
                    <div class="space-y-5 sm:space-y-6">
                        @forelse(array_slice($acaras, 0, 3) as $acara)
                            <div class="group bg-white rounded-2xl sm:rounded-3xl border border-emerald-100/60 shadow-md hover:shadow-xl hover:border-emerald-300/70 transition-all duration-300 overflow-hidden hover:-translate-y-1.5">

                                <div class="p-5 sm:p-6 lg:p-7">
                                    <!-- Badge & Tanggal -->
                                    <div class="flex flex-wrap items-center justify-between mb-4 gap-3">
                                        <span class="inline-flex items-center px-4 py-1.5 rounded-full bg-gradient-to-r from-emerald-50 to-teal-50 text-emerald-700 border border-emerald-200/80 text-xs sm:text-sm font-medium shadow-sm">
                                            {{ $acara['kategori'] ?? 'Kajian' }}
                                        </span>
                                        <span class="text-sm font-medium text-slate-600 bg-slate-100 px-3 py-1 rounded-full">
                                            {{ $acara['tanggal_label'] ?? $acara['tanggal'] ?? 'Segera' }}
                                        </span>
                                    </div>

                                    <h3 class="text-lg sm:text-xl lg:text-2xl font-bold text-slate-900 mb-3 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-tight">
                                        {{ $acara['title'] ?? $acara['judul'] ?? 'Judul Acara' }}
                                    </h3>

                                    <p class="text-sm sm:text-base text-slate-600 mb-5 line-clamp-3 leading-relaxed">
                                        {{ Str::limit(strip_tags($acara['excerpt'] ?? $acara['deskripsi'] ?? ''), 140) }}
                                    </p>

                                    <div class="flex flex-wrap gap-6 mb-6 text-sm text-slate-700">
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-xl text-emerald-600">⏰</span>
                                            <span class="font-medium">{{ $acara['waktu_label'] ?? $acara['waktu'] ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2.5">
                                            <span class="text-xl text-rose-600">📍</span>
                                            <span class="font-medium">{{ $acara['lokasi'] ?? 'Masjid' }}</span>
                                        </div>
                                    </div>

                                    <a href="{{ $acara['url'] ?? route('acara.show', $acara['slug'] ?? '#') }}"
                                       class="inline-flex items-center px-6 py-2.5 
                                              border-2 border-emerald-600 text-emerald-700 
                                              hover:bg-emerald-50 hover:text-emerald-800 
                                              font-medium rounded-full transition-all duration-300 text-sm shadow-sm hover:shadow">
                                        Detail Acara →
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="bg-white/80 rounded-2xl border border-dashed border-emerald-200 p-10 text-center text-slate-500">
                                <p class="text-xl font-medium mb-2">Belum ada agenda terdekat</p>
                                <p class="text-sm">Pantau terus untuk update kegiatan terbaru</p>
                            </div>
                        @endforelse

                        @if(count($acaras) > 3)
                            <div class="text-center mt-10 sm:mt-12">
                                <a href="{{ route('acara.index') }}" 
                                   class="group inline-flex items-center gap-3 px-10 sm:px-14 py-4 sm:py-5 
                                          bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 
                                          hover:brightness-110 hover:scale-[1.04] 
                                          text-white font-bold rounded-full 
                                          shadow-xl hover:shadow-2xl transition-all duration-300 text-base sm:text-lg">
                                    <span class="text-xl sm:text-2xl">📅</span>
                                    Lihat Semua Agenda & Kajian →
                                </a>
                            </div>
                        @endif
                    </div>

                    <!-- Sidebar -->
                    <aside class="space-y-6 lg:space-y-8 h-fit">
                        <!-- Shalat Jum'at – Lebih menonjol -->
                        <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-700 to-emerald-800 text-white shadow-2xl overflow-hidden ring-1 ring-emerald-500/30">
                            <div class="p-6 sm:p-7 lg:p-8">
                                <div class="flex items-center justify-between mb-5">
                                    <div>
                                        <p class="text-xs uppercase tracking-widest text-emerald-200/90 font-medium">Shalat Jum’at</p>
                                        <h3 class="text-xl font-bold mt-1">Pekan Ini</h3>
                                    </div>
                                    <span class="inline-flex px-4 py-1.5 bg-white/25 backdrop-blur-md rounded-full text-xs font-semibold shadow-sm">
                                        Segera Hadir
                                    </span>
                                </div>
                                <dl class="space-y-3 text-sm">
                                    <div class="flex items-start gap-4">
                                        <span class="text-2xl">🕌</span>
                                        <div>
                                            <dt class="text-emerald-200/80 text-xs uppercase">Khatib</dt>
                                            <dd class="font-bold">{{ $jumat['khatib'] ?? 'Ust. Dr. Muhammad' }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <span class="text-2xl mt-0.5">📖</span>
                                        <div>
                                            <dt class="text-emerald-200/80 text-xs uppercase">Tema</dt>
                                            <dd class="font-semibold">{{ $jumat['tema'] ?? 'Jaga Hati di Era Digital' }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <span class="text-2xl mt-0.5">📅</span>
                                        <div>
                                            <dt class="text-emerald-200/80 text-xs uppercase">Tanggal</dt>
                                            <dd class="font-semibold">{{ $jumat['tgl'] ?? 'Jum’at, 12 Jan' }}</dd>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-4">
                                        <span class="text-2xl mt-0.5">⏰</span>
                                        <div>
                                            <dt class="text-emerald-200/80 text-xs uppercase">Waktu</dt>
                                            <dd class="font-semibold">{{ $jumat['jam'] ?? '11.45 - 12.30' }}</dd>
                                        </div>
                                    </div>
                                </dl>
                            </div>
                        </div>

                        <!-- Mini Kalender – Lebih hidup -->
                        <div class="rounded-2xl sm:rounded-3xl bg-white border border-emerald-100/60 shadow-md p-6">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-semibold text-slate-900">Kalender Minggu Ini</h3>
                                <span class="text-sm text-slate-500">{{ now()->translatedFormat('F Y') }}</span>
                            </div>
                            <div class="grid grid-cols-7 gap-2 text-center text-sm">
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $date = now()->addDays($i);
                                        $isToday = $date->isToday();
                                    @endphp
                                    <div class="flex flex-col items-center">
                                        <span class="text-slate-400 text-xs font-medium">{{ $date->translatedFormat('D') }}</span>
                                        <span class="mt-1 flex h-10 w-10 items-center justify-center rounded-full text-base font-semibold 
                                                     {{ $isToday ? 'bg-emerald-600 text-white shadow-lg ring-2 ring-emerald-400' : 'bg-slate-50 border border-slate-200 hover:bg-emerald-50' }}">
                                            {{ $date->format('d') }}
                                        </span>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </section>

        {{-- SECTION QUOTE HARI INI - AUTO ROTATE DENGAN ANIMASI --}}
        <section class="py-10">
            <div class="container mx-auto px-4 lg:px-6">
                <div class="rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 text-white px-5 sm:px-6 py-6 sm:py-8 shadow-xl relative overflow-hidden">
                    <p class="text-xs uppercase tracking-widest text-emerald-100/90 mb-3">Pengingat Harian</p>

                    <!-- Container utama quote – tinggi minimal lebih besar di mobile -->
                    <div id="quote-container" class="relative min-h-[160px] sm:min-h-[120px] lg:min-h-[100px] overflow-hidden">
                        <!-- Quote fallback awal -->
                        @php
                            $quotes = [
                                ['title' => 'QS. Al-Baqarah: 186', 'text' => '“Dan apabila hamba-hamba-Ku bertanya kepadamu tentang Aku, maka (jawablah), bahwasanya Aku dekat. Aku mengabulkan permohonan orang yang berdoa apabila ia memohon kepada-Ku.”'],
                                ['title' => 'HR. Muslim', 'text' => '“Shalat yang paling utama setelah shalat fardhu adalah shalat malam.”'],
                                ['title' => 'HR. Tirmidzi', 'text' => '“Senyummu di hadapan saudaramu adalah sedekah.”'],
                                ['title' => 'QS. Ar-Ra’d: 28', 'text' => '“(yaitu) orang-orang yang beriman dan hati mereka menjadi tenteram dengan mengingat Allah. Ingatlah, hanya dengan mengingat Allah-lah hati menjadi tenteram.”'],
                                ['title' => 'HR. Bukhari', 'text' => '“Sebaik-baik manusia adalah yang paling bermanfaat bagi manusia lainnya.”'],
                                ['title' => 'QS. Al-Insyirah: 5-6', 'text' => '“Sesungguhnya bersama kesulitan ada kemudahan. Sesungguhnya bersama kesulitan ada kemudahan.”'],

                                // Tambahan quotes
                                ['title' => 'QS. Az-Zumar: 53', 'text' => '“Wahai hamba-hamba-Ku yang melampaui batas terhadap diri mereka sendiri! Janganlah kamu berputus asa dari rahmat Allah.”'],
                                ['title' => 'HR. Ahmad', 'text' => '“Sesungguhnya Allah itu Maha Lembut dan menyukai kelembutan dalam segala urusan.”'],
                                ['title' => 'QS. Al-Ankabut: 69', 'text' => '“Dan orang-orang yang berjihad untuk (mencari keridaan) Kami, benar-benar akan Kami tunjukkan kepada mereka jalan-jalan Kami.”'],
                                ['title' => 'HR. Bukhari & Muslim', 'text' => '“Barang siapa yang beriman kepada Allah dan hari akhir, hendaklah ia berkata baik atau diam.”'],
                                ['title' => 'QS. Al-Ahzab: 70', 'text' => '“Wahai orang-orang yang beriman! Bertakwalah kamu kepada Allah dan ucapkanlah perkataan yang benar.”'],
                                ['title' => 'HR. Muslim', 'text' => '“Allah tidak melihat rupa dan harta kalian, tetapi Dia melihat hati dan amal kalian.”'],
                                ['title' => 'QS. Ali-Imran: 139', 'text' => '“Janganlah kamu bersikap lemah dan janganlah pula kamu bersedih hati, padahal kamulah orang-orang yang paling tinggi (derajatnya) jika kamu orang-orang yang beriman.”'],
                            ];
                            $initialQuote = $quotes[array_rand($quotes)];
                        @endphp
                        <div class="quote-item absolute inset-0 opacity-100 transition-all duration-800 ease-in-out flex flex-col">
                            <h3 class="font-semibold text-base sm:text-lg lg:text-xl mt-1 leading-tight">
                                {{ $initialQuote['title'] }}
                            </h3>
                            <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed overflow-y-auto flex-1 pr-1 sm:pr-2">
                                {{ $initialQuote['text'] }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- === BERITA & PENGUMUMAN === --}}
        <section class="py-16 bg-gradient-to-br from-emerald-50 via-white to-sky-50">
            <div class="container mx-auto px-4 lg:px-6 grid lg:grid-cols-[1.5fr_minmax(0,1fr)] gap-10">

                <!-- BERITA -->
                <div>
                    <div class="flex justify-between mb-5">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Berita</p>
                            <h2 class="text-xl font-semibold text-slate-900">Berita</h2>
                        </div>
                        <a href="{{ route('berita.index') }}" class="text-xs text-emerald-700">Semua →</a>
                    </div>

                    <div class="space-y-4">   <!-- tambah space-y lebih besar biar terlihat rapi -->
                        @forelse($beritas as $b)
                            <a href="{{ $b['url'] }}" 
                               class="block group bg-white border border-slate-100 rounded-2xl shadow-sm hover:border-emerald-300 transition-all duration-200 overflow-hidden w-full max-w-full">

                                <div class="flex flex-col sm:flex-row gap-3 sm:gap-4 p-3 sm:p-4">  <!-- ubah ke column di mobile, row di sm+ -->

                                    <div class="w-full sm:w-32 h-40 sm:h-24 rounded-lg overflow-hidden flex-shrink-0">
                                        <img src="{{ $b['gambar'] ?? 'https://via.placeholder.com/300x200?text=Berita' }}" 
                                             loading="lazy" 
                                             alt="{{ $b['judul'] ?? 'Berita masjid' }}" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm sm:text-base font-semibold text-slate-900 line-clamp-2 group-hover:text-emerald-700 transition-colors">
                                            {{ $b['judul'] }}
                                        </h3>
                                        <p class="text-xs sm:text-[13px] text-slate-600 mt-1.5 line-clamp-3 sm:line-clamp-2">
                                            {{ $b['ringkas'] ?? Str::limit(strip_tags($b['isi'] ?? ''), 120) }}
                                        </p>
                                        <div class="mt-2 text-[10px] sm:text-xs text-slate-400">
                                            {{ $b['waktu'] ?? 'Baru saja' }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="bg-slate-50 rounded-xl p-6 text-center text-slate-500 text-sm">
                                Belum ada berita terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- =================================================
                     PENGUMUMAN — Tampilan rapi + preview modal
                     ================================================= --}}
                <div>
                    <div class="flex items-center justify-between mb-3 sm:mb-4">
                        <h2 class="text-base sm:text-lg font-semibold text-slate-900">Pengumuman</h2>
                        <a href="{{ route('pengumuman.index') }}" 
                           class="text-xs sm:text-sm text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1 hover:underline">
                            Semua →
                        </a>
                    </div>

                    <div class="space-y-3 sm:space-y-4">
                        @forelse($pengumuman as $p)
                            @php
                                $short = Str::limit(strip_tags($p['isi'] ?? ''), 100); // dikecilin lagi biar lebih aman di mobile
                                $tanggal = $p['tanggal'] ?? ($p['created_at'] ?? now())->translatedFormat('d M Y');
                            @endphp

                            <article class="bg-white rounded-xl sm:rounded-2xl border border-amber-100/70 shadow-sm 
                                           hover:shadow-md hover:border-amber-300 transition-all duration-200 
                                           group w-full max-w-full overflow-hidden">

                                <div class="p-3 sm:p-4 flex items-start gap-3 sm:gap-4">
                                    <!-- Ikon tetap kecil & fixed width -->
                                    <div class="flex-shrink-0">
                                        <div class="w-9 h-9 sm:w-11 sm:h-11 rounded-lg bg-amber-50 border border-amber-200/80 
                                                    flex items-center justify-center text-amber-700 text-xl sm:text-2xl">
                                            📢
                                        </div>
                                    </div>

                                    <!-- Konten utama -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-sm sm:text-base font-semibold text-slate-900 leading-tight 
                                                   line-clamp-2 group-hover:text-amber-700 transition-colors mb-1.5">
                                            {{ $p['judul'] }}
                                        </h3>

                                        <p class="text-xs sm:text-[13px] text-slate-600 line-clamp-3 sm:line-clamp-2 leading-relaxed">
                                            {{ $short }}
                                        </p>

                                        <!-- Baris tanggal + tombol → dibuat lebih compact di mobile -->
                                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                                            <span class="whitespace-nowrap">{{ $tanggal }}</span>
                                            <button type="button"
                                                    class="text-amber-700 hover:text-amber-800 font-medium 
                                                           px-2.5 py-1 rounded-md hover:bg-amber-50 transition-colors text-xs sm:text-sm"
                                                    data-pengumuman-id="{{ $p['id'] ?? '' }}"
                                                    data-pengumuman-judul="{{ e($p['judul'] ?? '') }}"
                                                    data-pengumuman-isi="{{ e(strip_tags($p['isi'] ?? '')) }}"
                                                    data-pengumuman-url="{{ e($p['url'] ?? '#') }}"
                                                    onclick="openPengumumanPreview(this)">
                                                Lihat →
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="bg-amber-50/40 rounded-xl p-5 sm:p-6 text-center text-slate-500 text-sm border border-amber-100/70">
                                Belum ada pengumuman terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ======== MODAL PREVIEW PENGUMUMAN (GANTI SELURUH BLOCK INI) ======== --}}
                <dialog id="pengumumanModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="pengumumanModalTitle" aria-describedby="pengumumanModalBody">
                    <div class="modal-box max-w-3xl w-[92%] sm:w-[80%] mx-auto p-0 overflow-hidden relative">
                        {{-- CLOSE BUTTON (absolute supaya tidak mempengaruhi flow) --}}
                        <button
                            type="button"
                            class="absolute right-3 top-3 z-30 btn btn-xs btn-circle bg-slate-200 text-slate-800 border-none shadow-sm"
                            aria-label="Tutup"
                            onclick="closePengumumanPreview()"
                        >✕</button>

                        {{-- HEADER --}}
                        <header class="px-4 sm:px-6 py-4 border-b border-slate-100 bg-white/50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 id="pengumumanModalTitle" class="text-lg font-semibold text-slate-900 leading-tight truncate"></h3>
                                    <div id="pengumumanModalDate" class="text-[12px] text-slate-500 mt-1"></div>
                                </div>
                            </div>
                        </header>

                        {{-- BODY --}}
                        <div class="px-4 sm:px-6 py-4 bg-white">
                            <div id="pengumumanModalBody" class="prose text-sm text-slate-700 max-h-[58vh] overflow-auto break-words"></div>
                        </div>

                        {{-- FOOTER --}}
                        <footer class="px-4 sm:px-6 py-3 border-t border-slate-100 bg-white flex items-center justify-end gap-2">
                            <a id="pengumumanModalDetail" href="#" class="btn btn-sm bg-emerald-600 text-white rounded-full px-4">Buka Halaman</a>
                            <button type="button" class="btn btn-sm bg-white-600 text-black border rounded-full px-4" onclick="closePengumumanPreview()">Tutup</button>
                        </footer>
                    </div>

                    {{-- fallback backdrop (untuk browser yg tidak mendukung <dialog>) --}}
                    <div class="modal-backdrop" aria-hidden="true" onclick="closePengumumanPreview()"></div>
                </dialog>
            </div>
        </section>

        {{-- === LAYANAN MASJID === --}}
        <section class="py-16 bg-gradient-to-b from-white to-emerald-50/30">
            <div class="container mx-auto px-4 lg:px-8">
                <div class="text-center mb-12">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium mb-3">
                        Layanan Terpercaya
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Layanan Masjid untuk Umat
                    </h2>
                    <p class="text-slate-600 max-w-3xl mx-auto">
                        Kami menyediakan berbagai layanan untuk memudahkan ibadah dan kebutuhan sosial jamaah
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                    @forelse($layanans as $l)
                        <div class="service-card group rounded-3xl bg-white border border-emerald-100/50 shadow-lg overflow-hidden">
                            <div class="p-8 lg:p-10 text-center">
                                <div class="service-icon-bg mx-auto mb-6 w-20 h-20 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-4xl shadow-md group-hover:shadow-xl transition">
                                    {{ $l->icon ?? '🕌' }}
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-4 group-hover:text-emerald-700 transition">
                                    {{ $l->judul }}
                                </h3>
                                <p class="text-sm text-slate-600 leading-relaxed line-clamp-4">
                                    {{ Str::limit(strip_tags($l->deskripsi ?? ''), 120) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        ...
                    @endforelse
                </div>
            </div>
        </section>

        {{-- === DONASI === --}}
        <section id="donasi" class="py-16 bg-gradient-to-br from-emerald-50 via-white to-teal-50">
            <div class="container mx-auto px-4 lg:px-6">

                <!-- Carousel Ajakan Infaq - Versi Mobile Rapi & Rata Tinggi -->
                <div id="infaqCarousel" class="relative mb-10 lg:mb-16">
                    <div class="overflow-hidden rounded-3xl">
                        <div class="infaq-track flex transition-transform duration-700 ease-out">
                            
                            <!-- Slide 1 -->
                            <div class="w-full shrink-0 px-3 sm:px-6 lg:px-8">
                                <div class="bg-gradient-to-br from-emerald-700 via-teal-700 to-emerald-600 text-white rounded-3xl p-6 sm:p-8 lg:p-12 text-center min-h-[420px] sm:min-h-[400px] lg:min-h-[380px] flex flex-col justify-between items-center shadow-2xl border border-emerald-500/30 overflow-hidden">
                                    <div class="flex flex-col items-center justify-center flex-grow">
                                        <h3 class="text-xl sm:text-2xl lg:text-4xl font-extrabold mb-4 lg:mb-6 leading-tight">
                                            Setiap kebaikan kecil yang kita tanam...
                                        </h3>
                                        <p class="text-lg sm:text-xl lg:text-2xl font-semibold mb-6 lg:mb-8 px-2 sm:px-0 line-clamp-4 sm:line-clamp-none">
                                            akan tumbuh menjadi <span class="text-yellow-300 block text-3xl sm:text-4xl lg:text-5xl mt-2">PAHALA</span>  
                                            yang menemani kita selamanya.
                                        </p>
                                    </div>
                                    <a href="#rekening" class="btn btn-lg bg-white text-emerald-800 hover:bg-yellow-300 hover:text-emerald-900 font-bold px-8 sm:px-10 py-4 sm:py-5 rounded-full shadow-xl text-lg sm:text-xl mt-auto w-full sm:w-auto max-w-xs">
                                        Yuk, Tanam Kebaikan Hari Ini
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Slide 2 -->
                            <div class="w-full shrink-0 px-3 sm:px-6 lg:px-8">
                                <div class="bg-gradient-to-br from-teal-700 via-emerald-700 to-cyan-700 text-white rounded-3xl p-6 sm:p-8 lg:p-12 text-center min-h-[420px] sm:min-h-[400px] lg:min-h-[380px] flex flex-col justify-between items-center shadow-2xl border border-teal-500/30 overflow-hidden">
                                    <div class="flex flex-col items-center justify-center flex-grow">
                                        <h3 class="text-xl sm:text-2xl lg:text-4xl font-extrabold mb-4 lg:mb-6">
                                            Bayangkan senyuman mereka...
                                        </h3>
                                        <p class="text-lg sm:text-xl lg:text-2xl font-semibold mb-6 lg:mb-8 px-2 sm:px-0 line-clamp-4 sm:line-clamp-none">
                                            karena <span class="text-yellow-300">sedekah kecilmu</span> hari ini.  
                                            Doa mereka menjadi <span class="text-yellow-300 block text-3xl sm:text-4xl lg:text-5xl mt-2">pembuka rezeki</span> untukmu.
                                        </p>
                                    </div>
                                    <a href="#qris" class="btn btn-lg bg-white text-emerald-800 hover:bg-yellow-300 hover:text-emerald-900 font-bold px-8 sm:px-10 py-4 sm:py-5 rounded-full shadow-xl text-lg sm:text-xl mt-auto w-full sm:w-auto max-w-xs">
                                        Mulai dengan Senyuman
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Slide 3 -->
                            <div class="w-full shrink-0 px-3 sm:px-6 lg:px-8">
                                <div class="bg-gradient-to-br from-cyan-700 via-teal-700 to-emerald-700 text-white rounded-3xl p-6 sm:p-8 lg:p-12 text-center min-h-[420px] sm:min-h-[400px] lg:min-h-[380px] flex flex-col justify-between items-center shadow-2xl border border-cyan-500/30 overflow-hidden">
                                    <div class="flex flex-col items-center justify-center flex-grow">
                                        <h3 class="text-xl sm:text-2xl lg:text-4xl font-extrabold mb-4 lg:mb-6">
                                            Sedekah itu seperti menabur benih...
                                        </h3>
                                        <p class="text-lg sm:text-xl lg:text-2xl font-semibold mb-6 lg:mb-8 px-2 sm:px-0 line-clamp-4 sm:line-clamp-none">
                                            yang Allah rawat sendiri.  
                                            Hasilnya? <span class="text-yellow-300 block text-3xl sm:text-4xl lg:text-5xl mt-2">BERKALI-KALI LIPAT</span>  
                                            di dunia & akhirat.
                                        </p>
                                    </div>
                                    <a href="#rekening" class="btn btn-lg bg-white text-emerald-800 hover:bg-yellow-300 hover:text-emerald-900 font-bold px-8 sm:px-10 py-4 sm:py-5 rounded-full shadow-xl text-lg sm:text-xl mt-auto w-full sm:w-auto max-w-xs">
                                        Tabur Benih Kebaikanmu
                                    </a>
                                </div>
                            </div>
                            
                            <!-- Slide 4 -->
                            <div class="w-full shrink-0 px-3 sm:px-6 lg:px-8">
                                <div class="bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 text-white rounded-3xl p-6 sm:p-8 lg:p-12 text-center min-h-[420px] sm:min-h-[400px] lg:min-h-[380px] flex flex-col justify-between items-center shadow-2xl border border-emerald-500/30 overflow-hidden">
                                    <div class="flex flex-col items-center justify-center flex-grow">
                                        <h3 class="text-2xl sm:text-3xl lg:text-5xl font-extrabold mb-4 lg:mb-6">
                                            Malam ini...  
                                            <span class="text-yellow-300 block text-3xl sm:text-4xl lg:text-6xl mt-2">bisa jadi berkah terindahmu</span>
                                        </h3>
                                        <p class="text-lg sm:text-xl lg:text-2xl font-semibold mb-6 lg:mb-8 px-2 sm:px-0 line-clamp-3 sm:line-clamp-none">
                                            Satu langkah kecil, pahala tak terhingga.
                                        </p>
                                    </div>
                                    <a href="#donasi" class="btn btn-lg bg-white text-emerald-800 hover:bg-yellow-300 hover:text-emerald-900 font-bold px-8 sm:px-12 py-4 sm:py-6 rounded-full shadow-2xl text-lg sm:text-xl mt-auto w-full sm:w-auto max-w-xs">
                                        Ayo, Mulai Dari Hati
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Dots Navigation -->
                    <div class="flex justify-center gap-3 sm:gap-4 mt-6 sm:mt-8">
                        <button class="infaq-dot w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full bg-emerald-300 hover:bg-emerald-700 transition" data-index="0"></button>
                        <button class="infaq-dot w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full bg-emerald-300 hover:bg-emerald-700 transition" data-index="1"></button>
                        <button class="infaq-dot w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full bg-emerald-300 hover:bg-emerald-700 transition" data-index="2"></button>
                        <button class="infaq-dot w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full bg-emerald-300 hover:bg-emerald-700 transition" data-index="3"></button>
                    </div>
                </div>

                <div class="text-center mb-10">
                    <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-2">Infaq & Donasi</p>
                    <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-4">
                        Mari Tebar Kebaikan
                    </h2>
                    <p class="text-base lg:text-lg text-slate-600 max-w-3xl mx-auto">
                        Setiap infaq yang kamu berikan adalah investasi akhirat yang tak pernah putus pahalanya. 
                        Allah berfirman: “Dan barangsiapa yang menafkahkan hartanya karena mencari keridhaan Allah...”
                    </p>
                </div>

                <div class="max-w-4xl mx-auto bg-white rounded-3xl border border-emerald-100/50 shadow-2xl overflow-hidden">
                    <div class="p-8 lg:p-12">
                        <!-- Rekening Tunggal -->
                        <div class="text-center mb-10">
                            <h3 class="text-2xl font-bold text-emerald-800 mb-4">Transfer ke Rekening Resmi</h3>
                            <div class="inline-block bg-emerald-50 rounded-2xl p-5 sm:p-6 shadow-inner w-full max-w-md mx-auto">
                                <p class="text-lg font-semibold text-emerald-700 mb-2">Bank {!! profil('bank_name') !!} • {!! profil('bank_code') !!}</p>
                                
                                <!-- Nomor rekening + copy – dengan chunking & hint -->
                                <div class="relative flex items-center justify-center gap-3 mb-3 bg-white/70 rounded-xl px-4 py-3 shadow-sm">
                                    <p 
                                        id="rekeningNum" 
                                        class="text-xl sm:text-2xl font-bold text-slate-900 tracking-widest whitespace-nowrap overflow-x-auto touch-pan-x"
                                        style="max-width: 75%; scrollbar-width: thin;"
                                    >
                                        {!! profil('rekening') !!}  <!-- sudah ada spasi tiap 4 digit -->
                                    </p>
                                    <button
                                        type="button"
                                        onclick="copyToClipboard('123456789010')"
                                        class="btn btn-sm btn-circle bg-emerald-600 hover:bg-emerald-700 text-white shrink-0 tooltip tooltip-bottom before:content-[attr(data-tip)]"
                                        data-tip="Salin nomor rekening"
                                    >
                                        📋
                                    </button>

                                    <!-- Indikator scroll horizontal kecil (hanya muncul jika overflow) -->
                                    <div class="absolute right-2 top-1/2 -translate-y-1/2 pointer-events-none opacity-60 text-xs text-slate-500 hidden sm:block">
                                        → geser
                                    </div>
                                </div>

                                <!-- Instruksi tambahan untuk user yang mungkin bingung -->
                                <p class="text-xs text-slate-500 mt-1 italic">
                                    Tekan lama nomor rekening jika ingin salin manual
                                </p>

                                <p class="text-base text-slate-600 mt-2">a/n {!! profil('atas_nama') !!}</p>
                            </div>
                        </div>

                        <!-- QRIS -->
                        <div class="text-center mb-10">
                            <h3 class="text-2xl font-bold text-emerald-800 mb-4">Scan QRIS Instan</h3>
                            <div class="mx-auto w-64 h-64 sm:w-72 sm:h-72 bg-white p-4 rounded-2xl shadow-lg border border-teal-100 relative cursor-pointer group"onclick="document.getElementById('qris-modal').showModal()">
                                <img src="{{ asset('storage/'.profil('qris')) }}" loading="lazy" alt="QRIS Donasi" class="w-full h-full object-contain group-hover:scale-105 pb-3">
                                <a href="{{ asset('storage/'.profil('qris')) }}" download="QRIS_{{ profil('nama') }}.png"
                                   class="absolute -bottom-4 left-1/2 -translate-x-1/2 btn btn-sm bg-emerald-600 text-white shadow-md">
                                    Simpan QRIS
                                </a>
                            </div>
                            <!-- Modal Preview QRIS -->
                            <dialog id="qris-modal" class="modal">
                                <div class="modal-box bg-white rounded-3xl shadow-2xl max-w-md sm:max-w-lg p-0 overflow-hidden">
                                    <!-- Header Modal -->
                                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-6 text-center">
                                        <h3 class="text-2xl font-bold text-white">Preview QRIS Donasi</h3>
                                        <p class="text-emerald-100/90 text-sm mt-1">
                                            Scan untuk donasi ke {{ profil('nama') ?? 'Masjid' }}
                                        </p>
                                    </div>

                                    <!-- Gambar Besar -->
                                    <div class="p-6 sm:p-8 bg-white">
                                        <img src="{{ asset('storage/' . profil('qris')) }}" 
                                             alt="QRIS Donasi" 
                                             class="w-full h-auto max-h-[500px] object-contain mx-auto rounded-xl shadow-inner">
                                    </div>

                                    <!-- Footer Modal -->
                                    <div class="modal-action p-6 border-t border-emerald-100 flex justify-center gap-4">
                                        <form method="dialog">
                                            <button class="btn btn-outline text-emerald-700 btn-emerald-700 px-8 py-3 rounded-full text-lg">
                                                Tutup
                                            </button>
                                        </form>
                                        <a href="{{ asset('storage/' . profil('qris')) }}" 
                                           download="QRIS_{{ Str::slug(profil('nama')) }}.png"
                                           class="btn btn-primary px-8 py-3 rounded-full text-lg">
                                            Simpan Gambar
                                        </a>
                                    </div>
                                </div>

                                <!-- Klik luar modal untuk close (DaisyUI default) -->
                                <form method="dialog" class="modal-backdrop">
                                    <button>close</button>
                                </form>
                            </dialog>
                            <p class="text-sm text-slate-600 mt-6">Konfirmasi via WhatsApp setelah transfer</p>
                        </div>

                        <!-- Quotes tentang Sedekah / Infaq -->
                        <div class="bg-gradient-to-r from-emerald-600/10 to-teal-600/10 rounded-2xl p-6 lg:p-8 text-center">
                            @php
                                $infaqQuotes = [
                                    ['text' => '“Perumpamaan orang-orang yang menafkahkan hartanya di jalan Allah adalah seperti sebutir benih yang menumbuhkan tujuh tangkai, pada tiap-tiap tangkai seratus biji...”', 'sumber' => 'QS. Al-Baqarah: 261'],
                                    ['text' => '“Sedekah itu tidak mengurangi harta.”', 'sumber' => 'HR. Muslim'],
                                    ['text' => '“Janganlah kalian takut miskin karena bersedekah.”', 'sumber' => 'HR. Tirmidzi'],
                                    ['text' => '“Infakkanlah (hartamu) di jalan Allah, dan janganlah kamu jatuhkan dirimu sendiri ke dalam kebinasaan...”', 'sumber' => 'QS. Al-Baqarah: 195'],
                                ];
                                $randomInfaq = $infaqQuotes[array_rand($infaqQuotes)];
                            @endphp
                            <p class="text-base lg:text-lg italic text-emerald-800 mb-3">
                                “{{ $randomInfaq['text'] }}”
                            </p>
                            <p class="text-sm text-slate-600 font-medium">
                                — {{ $randomInfaq['sumber'] }}
                            </p>
                        </div>

                        <!-- CTA Besar -->
                        <div class="text-center mt-10">
                            <a href="https://wa.me/6281234567890?text=Assalamu'alaikum%20saya%20ingin%20donasi..."
                               target="_blank"
                               class="inline-flex items-center px-8 sm:px-12 py-4 sm:py-6 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-full shadow-2xl text-lg sm:text-xl">
                                💚 Donasi via WhatsApp
                            </a>
                            <p class="text-sm text-slate-600 mt-4">
                                Konfirmasi donasi melalui WA untuk mendapatkan laporan penyaluran. Jazakumullah khairan.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- === GALERI + MODAL === --}}
        <section class="py-16 bg-gradient-to-br from-emerald-50 via-slate-50 to-sky-50">
            <div class="container mx-auto px-4 lg:px-6">
                <div class="flex justify-between mb-5">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Galeri</p>
                        <h2 class="text-xl sm:text-2xl font-semibold text-slate-900">Suasana Masjid</h2>
                    </div>

                    <a href="{{ route('galeri.index') }}" class="text-xs text-emerald-700">Lihat semua →</a>
                </div>

                <!-- Grid galeri -->
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2 sm:gap-3">
                    @forelse($galeri as $g)
                        <button type="button"
                            class="relative group rounded-xl overflow-hidden shadow-sm"
                            data-galeri-item="true"
                            data-id="{{ $g['id'] }}"
                            data-title="{{ $g['judul'] }}"
                            data-img="{{ $g['img'] }}">
                            
                            <img src="{{ $g['img'] }}" 
                                 loading="lazy" class="w-full h-20 sm:h-28 md:h-32 object-cover group-hover:scale-110 transition">

                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-end">
                                <p class="text-[10px] text-white px-2 pb-2">{{ $g['judul'] }}</p>
                            </div>

                        </button>
                    @empty
                        <p class="text-sm text-gray-500">Belum ada foto galeri.</p>
                    @endforelse
                </div>
            </div>

            {{-- Modal --}}
            <!-- Modal Galeri -->
            <dialog id="galeriModal" class="modal">
                <div class="modal-box max-w-4xl p-0 overflow-hidden rounded-2xl bg-white">
                <!-- IMAGE WRAPPER (relative so overlay buttons can be absolute) -->
                    <div class="relative bg-slate-900/5">
                        <img id="galeriModalImg" class="w-full max-h-[70vh] object-contain bg-black/5" alt="Galeri foto" />

                        <!-- left overlay prev -->
                        <button id="galeriPrev" aria-label="Sebelumnya"
                            class="absolute left-3 top-1/2 -translate-y-1/2 z-50 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/90 shadow-lg hover:scale-105 transition">
                            <svg class="w-5 h-5 text-emerald-700" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" 
                            stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>

                        <!-- right overlay next -->
                        <button id="galeriNext" aria-label="Berikutnya"
                            class="absolute right-3 top-1/2 -translate-y-1/2 z-50 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/90 shadow-lg hover:scale-105 transition">
                            <svg class="w-5 h-5 text-emerald-700" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>

                        <!-- close -->
                        <button id="closeGaleriModalBtn" type="button"
                            class="absolute right-3 top-3 z-50 inline-flex items-center justify-center w-9 h-9 rounded-md bg-black/60 text-white shadow-sm">
                            ✕
                        </button>
                    </div>

                    <!-- BODY: title, thumbs, controls -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 id="galeriModalTitle" class="text-lg font-semibold text-slate-900">Judul Galeri</h3>
                                <div id="galeriCounter" class="text-xs text-slate-500 mt-1">0 / 0</div>
                            </div>

                            <div class="hidden sm:flex items-center gap-2">
                            <!-- optional small pill nav -->
                            <button id="galeriPrevPill" class="btn btn-sm btn-ghost">Prev</button>
                            <button id="galeriNextPill" class="btn btn-sm btn-ghost">Next</button>
                            </div>
                        </div>

                        <!-- thumbs -->
                        <div id="galeriThumbs" class="mt-3 flex gap-2 overflow-x-auto py-2">
                        <!-- thumbs inserted here -->
                        </div>

                        <!-- small hint -->
                        <div class="mt-3 text-xs text-slate-400">Gunakan panah kiri/kanan pada keyboard untuk navigasi. Klik thumbnail untuk berpindah.</div>
                    </div>
                </div>

                <!-- backdrop fallback button -->
                <form method="dialog" class="modal-backdrop">
                    <button>Tutup</button>
                </form>
            </dialog>
        </section>

        {{-- === KONTAK === --}}
        <section id="kontak" class="py-16 bg-gradient-to-br from-slate-50 to-white">
            <div class="container mx-auto px-4 lg:px-6">
                <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
                    <!-- Kolom Kiri: Lokasi & Maps -->
                        <div class="bg-white rounded-2xl border border-emerald-100/60 shadow-lg p-6 lg:p-10">
                            <div class="mb-6">
                                <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">Kontak</p>
                                <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-4">Hubungi Kami</h2>
                                <p class="text-base text-slate-600 mt-3 leading-relaxed">
                                    {{ $profil->alamat ?? 'Alamat belum tersedia. Hubungi kami untuk info lebih lanjut.' }}
                                </p>
                            </div>

                            <div class="flex-1 bg-slate-50">
                                @if(!empty($profil->latitude) && !empty($profil->longitude))
                                    <iframe
                                        class="w-full h-full min-h-[400px] rounded-2xl shadow-xl shadow-emerald-200/30 border border-emerald-100/50"
                                        loading="lazy"
                                        allowfullscreen
                                        referrerpolicy="no-referrer-when-downgrade"
                                        src="https://www.google.com/maps?q={{ $profil->latitude }},{{ $profil->longitude }}&z=20&output=embed">
                                    </iframe>
                                @else
                                    <div class="w-full h-full min-h-[400px] flex items-center justify-center text-slate-400 text-lg bg-slate-100">
                                        Peta Masjid Belum Tersedia
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Kolom Kanan: Kontak & Form Pesan -->
                        <div class="bg-white rounded-2xl border border-emerald-100/60 shadow-lg p-6 lg:p-10">
                            <div class="mb-6">
                                <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">Kontak</p>
                                <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-4">Kontak & Pesan Jamaah</h2>
                            <div class="space-y-4 text-base text-slate-700">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl text-emerald-600">📞</span>
                                    <span>WhatsApp: <strong class="text-emerald-700">{{ $profil->telepon ?? ($profil->no_wa ?? '-') }}</strong></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl text-emerald-600">✉️</span>
                                    <span>Email: <strong class="text-emerald-700">{{ $profil->email ?? '-' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- Form Pesan - Sudah Diperbaiki -->
                        <form id="contactForm" class="mt-3 space-y-6 bg-white">
                            @csrf

                            <!-- Nama -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Anda <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        name="nama"
                                        id="contactNama"
                                        required
                                        class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:border-opacity-100 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                        placeholder="Masukkan nama lengkap"
                                    />
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👤</span>
                                </div>
                            </div>

                            <!-- Telepon -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nomor Telepon (opsional)</span>
                                </label>
                                <div class="relative">
                                    <input
                                        type="text"
                                        name="telepon"
                                        id="contactTelp"
                                        class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:border-opacity-100 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                        placeholder="Contoh: 08123456789"
                                    />
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📱</span>
                                </div>
                            </div>

                            <!-- Pesan -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Pesan atau Saran <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <textarea
                                        name="pesan"
                                        id="contactPesan"
                                        rows="5"
                                        required
                                        class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 focus:border-opacity-100 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                        placeholder="Ketik pesan atau saran Anda di sini..."
                                    ></textarea>
                                    <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">💬</span>
                                </div>
                            </div>

                            <!-- Button & Status -->
                            <div class="flex items-center justify-between pt-4">
                                <button
                                    id="contactSubmitBtn"
                                    type="submit"
                                    class="px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base"
                                >
                                    Kirim Pesan
                                </button>
                                <div id="contactStatus" class="text-sm text-slate-600 ml-4"></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

        {{-- ================= FLOATING ACTION BUTTONS ================= --}}
        <!-- Floating Action Bar: Install PWA + Notifikasi + Chat WA -->
        <div class="fixed bottom-6 right-6 z-50 flex flex-col-reverse items-end gap-4 sm:bottom-8 sm:right-8">

            <!-- Tombol Chat WA (paling bawah) -->
            @php $wa = preg_replace('/[^0-9]/', '', $profil->telepon ?? '6281234567890'); @endphp
            <a href="https://wa.me/{{ $wa }}" target="_blank" 
               class="btn btn-circle btn-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xl shadow-emerald-900/40 border-none transition-all duration-300 hover:scale-110 active:scale-95">
                <span class="text-2xl">💬</span>
            </a>

            <!-- Tombol Notifikasi (tengah) -->
            <button id="enableNotificationBtn" 
                    class="btn btn-circle btn-lg bg-cyan-600 hover:bg-cyan-700 text-white shadow-xl shadow-cyan-900/40 border-none transition-all duration-300 hover:scale-110 active:scale-95 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span id="notifBadge" class="absolute -top-1 -right-1 badge badge-error badge-xs animate-ping hidden">!</span>
            </button>

            <!-- Tombol Install PWA (paling atas) -->
            <button id="installPwaBtn" 
                    class="btn btn-circle btn-lg bg-gradient-to-br from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-xl shadow-teal-900/50 border-none transition-all duration-300 hover:scale-110 active:scale-95 hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span class="absolute -top-1 -right-1 badge badge-primary badge-xs animate-pulse">App</span>
            </button>

        </div>

        <!-- Modal Custom Persetujuan Notifikasi -->
        <dialog id="notifConsentModal" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box text-center rounded-3xl max-w-md">
                <!-- Icon -->
                <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-4xl shadow-md">
                    🔔
                </div>
                <!-- Judul -->
                <h3 class="font-bold text-xl text-slate-900 mb-3">
                    Aktifkan Notifikasi?
                </h3>
                <!-- Deskripsi -->
                <p class="text-sm text-slate-600 leading-relaxed mb-6 px-4">
                    Dapatkan <span class="font-semibold text-emerald-700">pengumuman penting</span>, jadwal kajian, pengingat sholat, dan update masjid langsung di perangkat Anda — bahkan saat aplikasi tertutup.
                </p>

                <!-- Pesan Denied (muncul otomatis kalau denied) -->
                <div id="permissionDeniedMessage" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-left text-sm text-red-800">
                    <strong>Izin notifikasi sebelumnya ditolak oleh browser.</strong><br><br>
                    <span id="resetInstructions"></span>
                </div>

                <!-- Tombol Aksi -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center mt-6">
                    <button id="notifAllow" 
                            class="btn bg-emerald-600 hover:bg-emerald-700 text-white border-none rounded-full px-10 py-3 font-semibold shadow-md">
                        Aktifkan Sekarang
                    </button>
                    <button id="notifDeny" 
                            class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-full px-10 py-3 font-semibold">
                        Nanti Saja
                    </button>
                </div>

                <!-- Catatan kecil -->
                <p class="text-xs text-slate-400 mt-6 italic">
                    Anda bisa mengubah pilihan ini kapan saja di pengaturan perangkat/browser.
                </p>
            </div>
        </dialog>

    </div>

@endsection

@push('style')
    <style>
        :root {
            --primary: #059669;           /* emerald-600 - warna utama */
            --primary-dark: #047857;
            --primary-light: #34d399;
            --teal: #0d9488;
            --teal-dark: #0c7a6e;
            --cyan: #0891b2;
            --cyan-dark: #077c9c;
            --soft-bg: #f0fdfa;
            --card-bg: rgba(255, 255, 255, 0.98);
            --card-border: rgba(5, 150, 105, 0.25);
            --shadow: 0 14px 40px -12px rgba(5, 150, 105, 0.25);
            --shadow-hover: 0 20px 48px -16px rgba(5, 150, 105, 0.4);
            --transition: all 0.32s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 50%, #e0f2fe 100%);
            color: #0f172a;
            font-feature-settings: "cv03", "cv04", "ss01";
        }

        .container {
            max-width: 1400px;
        }

        h1, h2, h3 {
            font-feature-settings: "cv01", "ss01";
            color: #065f46; /* emerald-900 lebih hidup */
        }

        .banner-track {
            scroll-snap-type: x mandatory;
        }
        .banner-page {
            scroll-snap-align: start;
        }

        /* ==================== JADWAL SHOLAT - Responsif + Lebih Berwarna ==================== */
        #jadwal {
            width: 100%;
            max-width: 100%;
            margin: 0 auto;
        }

        #jadwal > div {
            background: linear-gradient(145deg, rgba(255,255,255,0.98), rgba(236,253,245,0.96));
            backdrop-filter: blur(16px);
            border: 1px solid rgba(5,150,105,0.25);
            border-radius: 1.75rem;
            box-shadow: var(--shadow);
            overflow: hidden;
            padding: 1.5rem;
            transition: var(--transition);
        }

        #jadwal .grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        #jadwal .grid > div {
            background: rgba(255,255,255,0.97);
            border: 1px solid rgba(5,150,105,0.22);
            border-radius: 1rem;
            padding: 1rem 0.75rem;
            text-align: center;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: var(--transition);
            overflow: hidden;
        }

        #jadwal .grid > div:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px -8px rgba(16,185,129,0.35);
            border-color: #10b981;
        }

        /* Label */
        #jadwal .grid > div .text-xs,
        #jadwal .grid > div .text-sm {
            font-size: 0.875rem;
            font-weight: 600;
            color: #065f46;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        /* Waktu */
        #jadwal .grid > div .text-xl,
        #jadwal .grid > div .text-2xl,
        #jadwal .grid > div .text-3xl {
            font-weight: 800;
            color: #065f46;
            line-height: 1.1;
            white-space: nowrap;
        }

        /* Judul & tanggal */
        #jadwal h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #065f46;
            margin-bottom: 1rem;
        }

        #jadwal span.bg-emerald-100 {
            font-size: 0.95rem;
            padding: 0.5rem 1.25rem;
            background-color: rgba(5,150,105,0.08);
            color: #065f46;
            white-space: nowrap;
        }

        /* ==================== RESPONSIVE JADWAL ==================== */
        @media (min-width: 768px) {
            #jadwal .grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 1.25rem;
            }
            #jadwal > div {
                padding: 1.75rem 2rem;
            }
            #jadwal .grid > div {
                padding: 1.25rem 1rem;
                min-height: 120px;
            }
            #jadwal .grid > div .text-3xl {
                font-size: 2.25rem;
            }
            #jadwal h2 {
                font-size: 1.75rem;
            }
        }

        @media (min-width: 1024px) {
            #jadwal {
                max-width: 900px;
            }
            #jadwal .grid > div .text-3xl {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 767px) {
            #jadwal .grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 1rem;
            }
            #jadwal .grid > div {
                min-height: 105px;
            }
            #jadwal .grid > div .text-xl {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            #jadwal .grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            #jadwal .grid > div .text-xl {
                font-size: 1.35rem;
            }
        }

        /* ==================== STYLE UMUM LAINNYA ==================== */
        .btn {
            border-radius: 9999px;
            font-weight: 600;
            background: linear-gradient(to right, #059669, #0d9488);
            transition: var(--transition);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(5,150,105,0.35);
            background: linear-gradient(to right, #047857, #0c7a6e);
        }

        .rounded-2xl, .rounded-3xl {
            border-radius: 1.5rem;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .rounded-2xl:hover, .rounded-3xl:hover {
            transform: translateY(-6px);
            box-shadow: var(--shadow-hover);
            border-color: #10b981;
        }

        /* Hilangkan border grid kecil */
        .grid.gap-3 > div,
        .grid.gap-4 > div,
        .grid.gap-5 > div,
        .grid.gap-6 > div {
            border: none !important;
        }

        /* Loader */
        #page-loader {
            background: linear-gradient(to bottom, #f0f9ff, #ecfeff, #f0fdfa);
        }

        /* Scrollbar custom */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(5,150,105,0.05);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(5,150,105,0.4);
            border-radius: 3px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(5,150,105,0.6);
        }

        /* Agenda - Compact & Lebih Berwarna */
        #acara .bg-white.rounded-xl {
            background: linear-gradient(to bottom, white, #ecfdf5);
            border: 1px solid rgba(5,150,105,0.25);
        }

        #acara .bg-white.rounded-xl:hover {
            box-shadow: var(--shadow-hover);
            border-color: #10b981;
        }

        /* Quote text – area yang bisa discroll jika terlalu panjang */
        .quote-text {
            max-height: 11rem;           /* ~10-12 baris di mobile – sesuaikan selera */
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;   /* smooth scroll di iOS */
            scrollbar-width: thin;
            scrollbar-color: rgba(255,255,255,0.5) transparent;
            padding-right: 0.25rem;      /* ruang untuk scrollbar */
        }

        /* Hilangkan scrollbar default di WebKit (Chrome, Safari) agar lebih clean */
        .quote-text::-webkit-scrollbar {
            width: 5px;
        }
        .quote-text::-webkit-scrollbar-track {
            background: transparent;
        }
        .quote-text::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.5);
            border-radius: 10px;
        }
        .quote-text::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.8);
        }

        /* Di desktop – biarkan teks mengembang tanpa batas tinggi */
        @media (min-width: 640px) {
            .quote-text {
                max-height: none;
                overflow-y: visible;
            }
        }

        /* Animasi pergantian lebih mulus – container ikut menyesuaikan tinggi */
        #quote-container {
            transition: min-height 0.6s ease-out;
        }
        /* Quote - Gradient Lebih Kuat */
        #quote-container .quote-item {
            background: linear-gradient(135deg, rgba(5,150,105,0.97), rgba(13,148,136,0.97));
        }

        #quote-container p {
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (max-width: 640px) {
            #quote-container {
                min-height: 160px !important;
            }
            #quote-container p {
                max-height: 9rem;
                overflow-y: auto;
            }
        }

        .service-card {
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
          }
          .service-card:hover {
            transform: translateY(-12px) scale(1.02);
          }
          .service-icon-bg {
            transition: all 0.5s ease;
          }
          .service-card:hover .service-icon-bg {
            background: linear-gradient(135deg, #059669, #0d9488);
            color: white;
          }

        #rekeningNum {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #10b981 transparent;
        }

        #rekeningNum::-webkit-scrollbar {
            height: 4px;
        }

        #rekeningNum::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 10px;
        }

        /* Tampilkan indikator scroll hanya jika benar-benar overflow */
        @media (max-width: 400px) {
            #rekeningNum.overflow-scroll {
                position: relative;
            }
            #rekeningNum.overflow-scroll::after {
                content: "→ geser";
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                font-size: 10px;
                color: rgba(255,255,255,0.7);
                pointer-events: none;
            }
        }
        /* Kontak - Form Lebih Berwarna */
        #kontak input,
        #kontak textarea {
            border-color: #cbd5e1;
            color: #0f172a;
            background: white;
        }

        #kontak input:focus,
        #kontak textarea:focus {
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5,150,105,0.18);
        }

        #kontak form {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .infaq-track > div {
            padding: 0 1rem; /* jarak antar slide lebih jelas */
        }

        .infaq-track {
            scroll-snap-type: x mandatory;
        }

        .infaq-dot.w-4, .infaq-dot[data-active="true"] {
            width: 1.25rem;
            height: 1.25rem;
            background-color: #10b981 !important;
        }

        @media (max-width: 640px) {
            #infaqCarousel .min-h-[420px] {
                min-height: 420px !important;
            }
            #infaqCarousel p {
                font-size: 1.125rem; /* text-lg lebih kecil di mobile */
            }
            #infaqCarousel h3 {
                font-size: 1.5rem; /* text-2xl lebih kecil */
            }
        }

        /* Galeri - Hover Lebih Hidup */
        [data-galeri-item] img {
            transition: transform 0.5s ease;
        }

        [data-galeri-item]:hover img {
            transform: scale(1.15);
        }
    </style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>

        // =============================================
        // PWA Install Prompt
        // =============================================
        let deferredPrompt = null;

        document.querySelectorAll('#qris-modal img').forEach(img => {
            img.addEventListener('click', () => img.classList.toggle('scale-150'));
        });

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('installPwaBtn')?.classList.remove('hidden');
            console.log('PWA install prompt tersedia');
        });

        document.getElementById('installPwaBtn')?.addEventListener('click', async () => {
            if (!deferredPrompt) return;

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`PWA install outcome: ${outcome}`);

            deferredPrompt = null;
            document.getElementById('installPwaBtn')?.classList.add('hidden');

            if (outcome === 'accepted') {
                alert('Terima kasih! Aplikasi sedang diinstall.');
            }
        });

        window.addEventListener('appinstalled', () => {
            console.log('PWA berhasil diinstall');
            document.getElementById('installPwaBtn')?.classList.add('hidden');
        });

        // =============================================
        // Custom Notifikasi Modal + Permission Handling (Versi B: Pesan di Modal)
        // =============================================
        const notifModal       = document.getElementById('notifConsentModal');
        const btnNotifTrigger  = document.getElementById('enableNotificationBtn');
        const btnAllow         = document.getElementById('notifAllow');
        const btnDeny          = document.getElementById('notifDeny');
        const notifBadge       = document.getElementById('notifBadge');
        const deniedMessage    = document.getElementById('permissionDeniedMessage');
        const resetInstructions = document.getElementById('resetInstructions');

        // Fungsi open/close modal
        function openNotifModal() { notifModal?.showModal(); }
        function closeNotifModal() { notifModal?.close(); }

        // Reset UI modal saat dibuka
        function resetModalUI() {
            deniedMessage?.classList.add('hidden');
            btnAllow.disabled = false;
            btnAllow.textContent = 'Aktifkan Sekarang';
        }

        // Cek status permission saat halaman dimuat
        if ("Notification" in window) {
            const permission = Notification.permission;

            if (permission === "granted") {
                notifBadge?.classList.add('hidden');
            } 
            else if (permission === "denied") {
                notifBadge?.classList.remove('hidden');
            } 
            else {
                notifBadge?.classList.remove('hidden');
            }
        }

        // Event: tombol floating → buka modal + reset UI
        btnNotifTrigger?.addEventListener('click', () => {
            resetModalUI();
            
            // Kalau sudah denied, langsung tampilkan pesan panduan di modal
            if ("Notification" in window && Notification.permission === "denied") {
                deniedMessage?.classList.remove('hidden');
                const isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                resetInstructions.textContent = isPwa 
                    ? "Cara mengaktifkan: Tekan lama ikon app di home screen → App info → Notifikasi → ubah ke Diizinkan. Lalu buka ulang aplikasi."
                    : "Cara mengaktifkan: Klik ikon gembok di address bar → Notifications → ubah ke Allow atau Ask (default). Lalu refresh halaman.";
                
                // Nonaktifkan tombol Aktifkan (karena browser tidak akan tampilkan popup lagi)
                btnAllow.disabled = true;
                btnAllow.textContent = 'Sudah Diblokir (Aktifkan Manual)';
            }

            openNotifModal();
        });

        // Tombol "Aktifkan" di modal → hanya request kalau bukan denied
        btnAllow?.addEventListener('click', async () => {
            if ("Notification" in window && Notification.permission === "denied") {
                // Jangan request lagi, user harus reset manual
                return;
            }

            closeNotifModal();

            try {
                const permission = await Notification.requestPermission();

                if (permission === "granted") {
                    alert("Notifikasi berhasil diaktifkan!\nAnda akan menerima update masjid.");
                    notifBadge?.classList.add('hidden');
                } 
                else if (permission === "denied") {
                    // Setelah request, kalau denied lagi → tampilkan pesan di modal saat dibuka ulang
                    notifBadge?.classList.remove('hidden');
                }
            } catch (err) {
                console.error("Error request notification:", err);
            }
        });

        // Tombol "Nanti Saja" → tutup modal
        btnDeny?.addEventListener('click', closeNotifModal);

        document.addEventListener('DOMContentLoaded', function () {
            const quotes = @json($quotes); // ambil array quotes dari PHP
            const container = document.getElementById('quote-container');
            if (!container || quotes.length === 0) return;

            let currentIndex = 0;
            let timer = null; // ← deklarasikan di sini (penting!)

            // Fungsi untuk membuat elemen quote baru
            function createQuoteElement(quote) {
                const div = document.createElement('div');
                div.className = 'quote-item absolute inset-0 opacity-0 translate-y-4 transition-all duration-800 ease-in-out flex flex-col';
                div.innerHTML = `
                    <h3 class="font-semibold text-base sm:text-lg lg:text-xl mt-1 leading-tight">
                        ${quote.title}
                    </h3>
                    <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed overflow-y-auto flex-1 pr-1 sm:pr-2">
                        ${quote.text}
                    </div>
                `;
                return div;
            }

            // Fungsi untuk ganti quote dengan animasi
            function rotateQuote() {
                const oldQuote = container.querySelector('.quote-item.opacity-100');
                if (oldQuote) {
                    oldQuote.classList.remove('opacity-100', 'translate-y-0');
                    oldQuote.classList.add('opacity-0', '-translate-y-4');
                    setTimeout(() => oldQuote.remove(), 800);
                }

                currentIndex = (currentIndex + 1) % quotes.length;
                const newQuote = createQuoteElement(quotes[currentIndex]);
                container.appendChild(newQuote);

                setTimeout(() => {
                    newQuote.classList.remove('opacity-0', 'translate-y-4');
                    newQuote.classList.add('opacity-100', 'translate-y-0');
                }, 50);
            }

            // Mulai rotasi
            function startRotation() {
                if (timer) clearInterval(timer);
                timer = setInterval(rotateQuote, 6000);
            }

            // Optional: rotasi pertama setelah delay
            setTimeout(rotateQuote, 4000);

            // Mulai otomatis saat load
            startRotation();

            // Pause & resume on hover
            container.addEventListener('mouseenter', () => {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            });

            container.addEventListener('mouseleave', () => {
                if (!timer) {
                    startRotation();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            const infaqCarousel = document.getElementById('infaqCarousel');
            if (!infaqCarousel) return;

            const track = infaqCarousel.querySelector('.infaq-track');
            const dots = infaqCarousel.querySelectorAll('.infaq-dot');
            let current = 0;
            const total = dots.length;
            let timer = null;

            function setDot(i) {
                dots.forEach((d, idx) => {
                    d.classList.toggle('bg-emerald-600', idx === i);
                    d.classList.toggle('bg-emerald-300', idx !== i);
                    d.classList.toggle('w-4', idx === i);
                    d.classList.toggle('h-4', idx === i);
                });
            }

            function go(index) {
                if (index < 0) index = total - 1;
                if (index >= total) index = 0;
                track.style.transform = `translateX(-${index * 100}%)`;
                setDot(index);
                current = index;
            }

            function startAuto() {
                stopAuto();
                timer = setInterval(() => go(current + 1), 6000); // 6 detik per slide
            }

            function stopAuto() {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            }

            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    go(parseInt(dot.dataset.index));
                    startAuto();
                });
            });

            infaqCarousel.addEventListener('mouseenter', stopAuto);
            infaqCarousel.addEventListener('mouseleave', startAuto);
            infaqCarousel.addEventListener('touchstart', stopAuto);
            infaqCarousel.addEventListener('touchend', startAuto);

            // Mulai
            go(0);
            startAuto();
        });

        function copyToClipboard(text) {
            const el = document.getElementById('rekeningNum');
            if (!el) return;

            // Simpan original untuk feedback
            const originalColor = el.style.color || '';

            // Fungsi feedback sukses
            function showSuccess() {
                el.style.color = '#10b981';
                el.classList.add('font-medium');
                setTimeout(() => {
                    el.style.color = originalColor;
                    el.classList.remove('font-medium');
                }, 2500);
            }

            // Coba modern Clipboard API dulu
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text)
                    .then(showSuccess)
                    .catch(err => {
                        console.warn('Clipboard API gagal:', err);
                        fallbackCopy();
                    });
            } else {
                // Langsung fallback jika API tidak ada
                fallbackCopy();
            }

            function fallbackCopy() {
                // Buat textarea sementara
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'absolute';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);

                textarea.select();
                textarea.setSelectionRange(0, 99999); // Untuk mobile

                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        showSuccess();
                    } else {
                        alert('Gagal menyalin otomatis. Tekan lama nomor rekening lalu pilih "Salin".');
                    }
                } catch (err) {
                    console.error('Fallback copy gagal:', err);
                    alert('Gagal menyalin. Tekan lama nomor rekening lalu pilih "Salin".');
                }

                document.body.removeChild(textarea);
            }
        }

        // buka modal menggunakan atribut data dari tombol (sudah ada fungsi openPengumumanPreview sebelumnya)
        function openPengumumanPreview(btn) {
            const title = btn.getAttribute('data-pengumuman-judul') || '';
            const isi   = btn.getAttribute('data-pengumuman-isi') || '';
            const url   = btn.getAttribute('data-pengumuman-url') || '#';

            const modal = document.getElementById('pengumumanModal');
            const elTitle = document.getElementById('pengumumanModalTitle');
            const elBody  = document.getElementById('pengumumanModalBody');
            const elDate  = document.getElementById('pengumumanModalDate');
            const elDetail= document.getElementById('pengumumanModalDetail');

            elTitle.textContent = title;
            // jika isi mengandung HTML yang aman, gunakan innerHTML setelah sanitasi.
            // di sini kita menampilkan text saja supaya aman:
            elBody.textContent = isi;

            // tanggal: jika tersedia di tombol, gunakan; kalau tidak biarkan kosong
            const tanggalAttr = btn.getAttribute('data-pengumuman-tanggal');
            if (tanggalAttr) elDate.textContent = tanggalAttr;
            else {
                const parent = btn.closest('article');
                const dateEl = parent ? parent.querySelector('.text-amber-700, .text-amber-700') : null;
                elDate.textContent = dateEl ? dateEl.textContent.trim() : '';
            }

            elDetail.href = url;

            // focus & open
            try {
                if (typeof modal.showModal === 'function') {
                    modal.showModal();
                } else {
                    // polyfill fallback: add class to show
                    modal.classList.add('modal-open');
                    modal.style.display = 'block';
                }
                // move focus to title for accessibility
                elTitle.focus && elTitle.focus();
            } catch (e) {
                // fallback
                modal.classList.add('modal-open');
            }
        }

        function closePengumumanPreview() {
            const modal = document.getElementById('pengumumanModal');
            try {
                if (typeof modal.close === 'function') modal.close();
                else {
                    modal.classList.remove('modal-open');
                    modal.style.display = 'none';
                }
            } catch (e) {
                modal.classList.remove('modal-open');
                modal.style.display = 'none';
            }
        }

        // close on ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('pengumumanModal');
                if (!modal) return;
                // only close if modal is open
                if (modal.open || modal.classList.contains('modal-open')) closePengumumanPreview();
            }
        });

        window.addEventListener('load',function(){
            const loader=document.getElementById('page-loader');
            if(loader){ loader.classList.add('opacity-0','pointer-events-none'); setTimeout(()=>loader.remove(),600);}
        });

        document.addEventListener('DOMContentLoaded',function(){
            const carousel=document.getElementById('bannerCarousel');
            if(!carousel)return;

            const track=carousel.querySelector('.banner-track');
            const pages=track.querySelectorAll('.banner-page');
            const dots=carousel.querySelectorAll('.banner-dot');

            let current=0,total=pages.length,timer=null;

            function setDot(i){
                dots.forEach((d,idx)=>{
                    d.classList.toggle('bg-emerald-500',idx===i);
                    d.classList.toggle('bg-emerald-200',idx!==i);
                    d.classList.toggle('w-4',idx===i);
                });
            }
            function go(i){
                if(i<0)i=total-1;
                if(i>=total)i=0;
                track.style.transform=`translateX(-${i*100}%)`;
                setDot(i);
                current=i;
            }
            function start(){
                stop();
                timer=setInterval(()=>go(current+1),6000);
            }
            function stop(){ if(timer){clearInterval(timer);timer=null;}}

            dots.forEach(d=>d.onclick=()=>{go(+d.dataset.index);start();});
            carousel.addEventListener('mouseenter',stop);
            carousel.addEventListener('mouseleave',start);

            go(0);start();


            // Galeri Modal
            const items=document.querySelectorAll('[data-galeri-item]');
            const modal=document.getElementById('galeriModal');
            const modalImg=document.getElementById('galeriModalImg');
            const modalTitle=document.getElementById('galeriModalTitle');

            items.forEach(btn=>{
                btn.onclick=()=>{
                    modalImg.src=btn.dataset.img;
                    modalTitle.textContent=btn.dataset.title;
                    modal.showModal();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('galeriModal');
            const modalImg = document.getElementById('galeriModalImg');
            const modalTitle = document.getElementById('galeriModalTitle');
            const thumbsBox = document.getElementById('galeriThumbs'); // pastikan id ini ada di blade
            const prevBtn = document.getElementById('galeriPrev');
            const nextBtn = document.getElementById('galeriNext');
            const counter = document.getElementById('galeriCounter');
            const closeBtn = document.getElementById('closeGaleriModalBtn');

            let fotos = []; // array of {url, caption}
            let index = 0;

            function openModal() {
                try { if (typeof modal.showModal === 'function') modal.showModal(); else modal.classList.add('modal-open'); }
                catch (e) { modal.classList.add('modal-open'); }
            }
            function closeModal() {
                try { if (typeof modal.close === 'function') modal.close(); else modal.classList.remove('modal-open'); }
                catch (e) { modal.classList.remove('modal-open'); }
            }

            function isModalOpen() {
                if (!modal) return false;
                if (typeof modal.open !== 'undefined') return !!modal.open;
                return modal.classList.contains('modal-open');
            }

            // safe event bindings
            if (closeBtn) closeBtn.addEventListener('click', closeModal);
            if (modal) {
                modal.addEventListener('click', (ev) => {
                    // close when clicking backdrop (modal element itself)
                    if (ev.target === modal) closeModal();
                });
            }

            function updateCounter() {
                if (!counter) return;
                counter.textContent = fotos.length ? `${index + 1} / ${fotos.length}` : '';
            }

            function setImage(i) {
                if (!modalImg) return;
                if (!fotos.length) {
                    modalImg.src = '';
                    modalImg.alt = '';
                    updateCounter();
                    // remove highlight if thumbsBox exists
                    if (thumbsBox) Array.from(thumbsBox.children).forEach(ch => { ch.classList.remove('ring'); ch.classList.remove('ring-emerald-400'); });
                    return;
                }

                index = (Number(i) + fotos.length) % fotos.length;
                modalImg.src = fotos[index].url;
                modalImg.alt = fotos[index].caption || fotos[index].file_name || '';
                updateCounter();

                // highlight thumb (use add/remove to avoid multi-token errors)
                if (thumbsBox) {
                    Array.from(thumbsBox.children).forEach((child, idx) => {
                        if (idx === index) {
                            child.classList.add('ring');
                            child.classList.add('ring-emerald-400');
                        } else {
                            child.classList.remove('ring');
                            child.classList.remove('ring-emerald-400');
                        }
                    });
                    // ensure the active thumb is visible (scroll into view if overflow)
                    const active = thumbsBox.children[index];
                    if (active && typeof active.scrollIntoView === 'function') {
                        active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                    }
                }
            }

            function renderThumbs() {
                if (!thumbsBox) return;
                thumbsBox.innerHTML = '';

                fotos.forEach((f, i) => {
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'inline-block border rounded overflow-hidden';
                    btn.style.width = '90px';
                    btn.style.height = '64px';
                    btn.style.flex = '0 0 auto';
                    btn.style.padding = '0';
                    btn.style.margin = '0 6px 6px 0';
                    btn.setAttribute('aria-label', f.caption || `Foto ${i+1}`);
                    btn.innerHTML = `<img src="${f.url}" loading="lazy" class="w-full h-full object-cover" alt="${(f.caption||f.file_name||'foto')}">`;
                    btn.addEventListener('click', () => setImage(i));
                    thumbsBox.appendChild(btn);
                });
            }

            if (prevBtn) prevBtn.addEventListener('click', () => setImage(index - 1));
            if (nextBtn) nextBtn.addEventListener('click', () => setImage(index + 1));

            document.addEventListener('keydown', (e) => {
                if (!isModalOpen()) return;
                if (e.key === 'ArrowLeft') setImage(index - 1);
                if (e.key === 'ArrowRight') setImage(index + 1);
                if (e.key === 'Escape') closeModal();
            });

            // Delegate click on gallery items (buttons with data-galeri-item and data-id)
            document.body.addEventListener('click', (ev) => {
                const btn = ev.target.closest('[data-galeri-item]');
                if (!btn) return;

                const id = btn.dataset.id || null;
                const fallbackImg = btn.dataset.img || null;
                const title = btn.dataset.title || '';

                if (modalTitle) modalTitle.textContent = title || '';

                if (!id) {
                    // if no id, show single fallback image
                    fotos = fallbackImg ? [{ url: fallbackImg, caption: title }] : [];
                    renderThumbs();
                    setImage(0);
                    openModal();
                    return;
                }

                // Fetch full foto list
                $.ajax({
                    url: `/home/galeri/${encodeURIComponent(id)}`,
                    method: 'GET',
                    dataType: 'json',
                    success: function(data) {
                        try {
                            fotos = Array.isArray(data.fotos)
                                ? data.fotos.map(function(f) {
                                    return {
                                        url: f.url || (f.file_name ? `/storage/galeri/${f.file_name}` : ''),
                                        caption: f.caption || f.file_name || ''
                                    };
                                })
                                : [];

                            // fallback to single image if empty
                            if (!fotos.length && fallbackImg) {
                                fotos = [{ url: fallbackImg, caption: title }];
                            }

                            renderThumbs();
                            setImage(0);
                            openModal();
                        } catch (e) {
                            console.error('Galeri parse error', e);
                            // fallback
                            if (fallbackImg) {
                                fotos = [{ url: fallbackImg, caption: title }];
                                renderThumbs();
                                setImage(0);
                                openModal();
                            } else if (window.Swal) {
                                Swal.fire('Error', 'Gagal memproses data galeri.', 'error');
                            }
                        }
                    },
                    error: function(xhr, status, err) {
                        console.error('Galeri fetch error', status, err);
                        if (fallbackImg) {
                            fotos = [{ url: fallbackImg, caption: title }];
                            renderThumbs();
                            setImage(0);
                            openModal();
                        } else if (window.Swal) {
                            Swal.fire('Error', 'Gagal memuat foto galeri dari server.', 'error');
                        }
                    }
                });
            });
        });

        // pastikan functions setImage(index) dan index var ada di scope
        const prevBtn = document.getElementById('galeriPrev');
        const nextBtn = document.getElementById('galeriNext');
        const prevPill = document.getElementById('galeriPrevPill');
        const nextPill = document.getElementById('galeriNextPill');

        // fallback: jika fungsi setImage belum ada, gunakan dispatch click pada element lain
        function safePrev() {
            if (typeof setImage === 'function') setImage(index - 1);
            else document.dispatchEvent(new CustomEvent('galeriPrev'));
        }
        function safeNext() {
            if (typeof setImage === 'function') setImage(index + 1);
            else document.dispatchEvent(new CustomEvent('galeriNext'));
        }

        if (prevBtn) prevBtn.addEventListener('click', safePrev);
        if (nextBtn) nextBtn.addEventListener('click', safeNext);
        if (prevPill) prevPill.addEventListener('click', safePrev);
        if (nextPill) nextPill.addEventListener('click', safeNext);

        // keyboard hint: left/right also control
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') safePrev();
            if (e.key === 'ArrowRight') safeNext();
        });

        const $form   = $('#contactForm');
        const $btn    = $('#contactSubmitBtn');
        const $status = $('#contactStatus');

        // pastikan meta csrf ada
        const csrf = $('meta[name="csrf-token"]').attr('content');

        $form.on('submit', function(e) {
            e.preventDefault();
            $status.html('');
            $btn.prop('disabled', true).text('Mengirim...');

            // hapus pesan error lama
            $('.invalid-feedback, .is-invalid').remove();

            const fd = new FormData(this);

            $.ajax({
                url: '{{ route("kontak.kirim") }}',
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json'
                },
                success: function(res) {
                    $btn.prop('disabled', false).text('Kirim Pesan');
                    $status.html('<span class="text-sm text-emerald-600">' + (res.message || 'Pesan terkirim') + '</span>');
                    $form[0].reset();
                },
                error: function(xhr) {
                    $btn.prop('disabled', false).text('Kirim Pesan');

                    // jika ada pesan validasi (422)
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        const errors = xhr.responseJSON.errors;
                        // tampilkan error pertama di atas form (atau per-field)
                        let firstMsg = '';
                        $.each(errors, function (field, msgs) {
                            firstMsg = msgs[0];
                            // tambahkan pesan di bawah field jika ada input dengan name=field
                            const $input = $form.find('[name="'+field+'"]');
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $input.after('<div class="invalid-feedback">' + msgs[0] + '</div>');
                            }
                        });
                        if (firstMsg) {
                            $status.html('<span class="text-sm text-red-600">' + firstMsg + '</span>');
                        }
                        return;
                    }

                    // fallback: tampilkan message dari server atau pesan generik
                    let msg = 'Terjadi kesalahan. Coba lagi.';
                    try {
                        const json = JSON.parse(xhr.responseText);
                        msg = json.message || msg;
                    } catch(e){ /* ignore parse error */ }

                    $status.html('<span class="text-sm text-red-600">' + msg + '</span>');
                }
            });
        });
</script>
@endpush