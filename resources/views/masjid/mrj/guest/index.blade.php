@extends('masjid.master-guest')

@push('head')
    <script src="https://www.google.com/recaptcha/api.js?render={{ env('RECAPTCHA_SITE_KEY') }}"></script>
@endpush

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
        <section id="jadwal" class="relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-teal-200 rounded-full blur-3xl opacity-30 animate-pulse"></div>
                <div class="absolute top-40 right-0 w-[600px] h-[600px] bg-cyan-200 rounded-full blur-3xl opacity-25 animate-pulse delay-1000"></div>
            </div>

            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="grid lg:grid-cols-2 gap-12 xl:gap-16 items-center">
                    <!-- Hero Text -->
                    <div class="space-y-8 text-center lg:text-left">
                        <div class="inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/60 backdrop-blur-sm border border-emerald-200/50 shadow-sm text-sm text-emerald-800">
                            <span class="relative flex h-3 w-3">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-70"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                            </span>
                            Selamat Datang di {!! profil('nama') ?? 'Masjid' !!}
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
                                🤲 Tunaikan Infak
                            </a>
                            <a href="#acara" class="btn btn-lg btn-outline border-2 border-emerald-500 text-emerald-700 hover:bg-emerald-50 hover:border-emerald-600">
                                Lihat Agenda
                            </a>
                        </div>

                        <!-- Stats -->
                        <!--                    
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
                        </div> -->
                    </div>

                    <!-- JADWAL SHOLAT – VERSI BARU & RESPONSIF -->
                    <div class="w-full max-w-3xl mx-auto lg:mx-0 lg:max-w-none">  <!-- lebar lebih fleksibel di desktop -->
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
                                        <div class="group relative bg-white/95 backdrop-blur-sm rounded-2xl p-5 md:p-6 text-center 
                                                    border border-emerald-100/60 shadow-md hover:shadow-xl hover:shadow-{{ $data['color'] }}-300/40 
                                                    hover:border-{{ $data['color'] }}-400/50 transition-all duration-400 transform hover:-translate-y-2 hover:scale-[1.03] 
                                                    overflow-hidden min-h-[100px] md:min-h-[120px] flex flex-col items-center justify-center">
                                            
                                            <!-- Subtle glow overlay -->
                                            <div class="absolute inset-0 bg-gradient-to-br from-{{ $data['color'] }}-400/0 via-{{ $data['color'] }}-500/10 to-{{ $data['color'] }}-600/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                                            
                                            <div class="relative z-10">
                                                <div class="text-xs md:text-sm font-semibold text-{{ $data['color'] }}-700 uppercase tracking-wide mb-2 md:mb-3">
                                                    {{ $data['label'] }}
                                                </div>
                                                <div class="text-2xl md:text-3xl font-extrabold text-slate-900 group-hover:text-{{ $data['color'] }}-700 transition-colors whitespace-nowrap drop-shadow-sm">
                                                    {{ $jadwalSholat[$key] ?? '--:--' }}
                                                </div>
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
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
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
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">

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

            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
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
                                    Lihat Semua Agenda
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
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 text-white px-5 sm:px-6 py-6 sm:py-8 shadow-xl relative overflow-hidden">
                    <p class="text-xs uppercase tracking-widest text-emerald-100/90 mb-3">Pengingat Harian</p>

                    <!-- Container quote -->
                    <div id="quote-container" class="relative min-h-[180px] sm:min-h-[140px] lg:min-h-[120px] overflow-hidden">
                        @if($quoteHarianList->isNotEmpty())
                            <!-- Quote pertama (acak di JS nanti) -->
                            <div class="quote-item absolute inset-0 opacity-100 transition-all duration-800 ease-in-out flex flex-col">
                                <h3 class="font-semibold text-base sm:text-lg lg:text-xl mt-1 leading-tight">
                                    {{ $quoteHarianList->first()->title }}
                                </h3>
                                <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed overflow-y-auto flex-1 pr-1 sm:pr-2">
                                    {{ $quoteHarianList->first()->text }}
                                </div>
                            </div>
                        @else
                            <!-- Fallback -->
                            <div class="quote-item absolute inset-0 opacity-100 transition-all duration-800 ease-in-out flex flex-col">
                                <h3 class="font-semibold text-base sm:text-lg lg:text-xl mt-1 leading-tight">
                                    Pengingat Harian
                                </h3>
                                <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed overflow-y-auto flex-1 pr-1 sm:pr-2">
                                    “Sesungguhnya bersama kesulitan ada kemudahan.” — QS. Al-Insyirah: 6
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

        {{-- === BERITA & PENGUMUMAN === --}}
        <section id="berita" class="py-16 bg-gradient-to-br from-emerald-50 via-white to-sky-50">
            <div class="container container mx-auto px-6 lg:px-16 xl:px-24 relative grid lg:grid-cols-[1.5fr_minmax(0,1fr)] gap-10">

                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-8 md:mb-10">
                        <div>
                            <p class="text-xs uppercase tracking-widest font-medium text-emerald-700/80">Kabar Terkini</p>
                            <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mt-1.5">Kabar Masjid & Umat</h2>
                        </div>
                        <a href="{{ route('berita.index') }}"
                           class="text-sm font-medium text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1.5 self-start sm:self-auto">
                            Lihat Semua <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="grid gap-6 md:gap-8">
                        @forelse($beritas as $b)
                            <a href="{{ $b['url'] }}"
                               class="group relative flex flex-col sm:flex-row gap-4 sm:gap-6 bg-white rounded-2xl border border-emerald-100/60 shadow-md hover:shadow-xl hover:shadow-emerald-200/40 hover:border-emerald-300/70 transition-all duration-300 overflow-hidden">

                                <!-- Gambar -->
                                <div class="relative flex-shrink-0 sm:w-48 md:w-56 overflow-hidden rounded-t-2xl sm:rounded-l-2xl sm:rounded-tr-none h-48 sm:h-auto">
                                    <img src="{{ $b['gambar'] ?? asset('storage/404.jpg') }}"
                                         loading="lazy"
                                         alt="{{ $b['judul'] }}"
                                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                </div>

                                <!-- Konten teks -->
                                <div class="flex-1 p-5 sm:p-6 flex flex-col">
                                    <h3 class="text-lg sm:text-xl font-semibold text-slate-900 leading-tight line-clamp-2 md:line-clamp-3 group-hover:text-emerald-700 transition-colors mb-3">
                                        {{ $b['judul'] }}
                                    </h3>

                                    <p class="text-sm text-slate-600 line-clamp-2 sm:line-clamp-3 leading-relaxed mb-4 flex-1">
                                        {{ $b['ringkas'] ?? Str::limit(strip_tags($b['isi'] ?? ''), 140) }}
                                    </p>

                                    <!-- Bagian bawah: waktu + tombol baca -->
                                    <div class="flex items-center justify-between text-xs sm:text-sm text-slate-500 mt-auto">
                                        <span class="font-medium">{{ $b['waktu'] ?? 'Baru saja' }}</span>

                                        <!-- Tombol baca: selalu visible di mobile, hover-only di desktop -->
                                        <span class="text-emerald-600 font-medium transition-opacity duration-300
                                                     sm:opacity-0 sm:group-hover:opacity-100
                                                     inline-flex items-center gap-1.5">
                                            Selengkapnya
                                            <span aria-hidden="true">→</span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="bg-white/80 rounded-2xl p-10 md:p-12 text-center text-slate-500 border border-emerald-100/60 shadow-md">
                                <p class="text-xl font-semibold text-slate-700 mb-3">Belum ada berita terbaru</p>
                                <p class="text-sm">Yuk pantau terus update kegiatan masjid kita!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- PENGUMUMAN – shadow default lebih tegas + hover hidup -->
                <div>
                    <div class="flex items-center justify-between mb-6 sm:mb-8">
                        <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Pengumuman</h2>
                        <a href="{{ route('pengumuman.index') }}"
                           class="text-sm text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1.5 hover:underline transition">
                            Semua →
                        </a>
                    </div>

                    <div class="space-y-4 sm:space-y-5">
                        @forelse($pengumuman as $p)
                            @php
                                $short = Str::limit(strip_tags($p['isi'] ?? ''), 100);
                                $tanggal = $p['tanggal'] ?? ($p['created_at'] ?? now())->translatedFormat('d M Y');
                            @endphp
                            <article class="bg-white rounded-xl sm:rounded-2xl border border-amber-100/70
                                            shadow-md hover:shadow-xl hover:shadow-amber-100/50 hover:border-amber-300/70
                                            transition-all duration-300 group w-full overflow-hidden">
                                <div class="p-4 sm:p-5 flex items-start gap-4 sm:gap-5">
                                    <!-- Ikon -->
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-50 border border-amber-200/80
                                                    flex items-center justify-center text-amber-700 text-2xl sm:text-3xl shadow-sm">
                                            📢
                                        </div>
                                    </div>

                                    <!-- Konten -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 leading-tight line-clamp-2
                                                   group-hover:text-amber-700 transition-colors mb-2">
                                            {{ $p['judul'] }}
                                        </h3>
                                        <p class="text-sm text-slate-600 line-clamp-3 sm:line-clamp-2 leading-relaxed mb-3">
                                            {{ $short }}
                                        </p>
                                        <div class="flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm text-slate-500">
                                            <span class="whitespace-nowrap font-medium">{{ $tanggal }}</span>
                                            <button type="button"
                                                    class="text-amber-700 hover:text-amber-800 font-medium px-3 py-1.5 rounded-md
                                                           hover:bg-amber-50 transition-colors"
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
                            <div class="bg-amber-50/50 rounded-xl p-6 sm:p-8 text-center text-slate-500 text-sm
                                        border border-amber-200/60 shadow-md">
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
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
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
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <!-- Slider Motivasi dari Database - Full Lebar + Batas Super Jelas -->
                @if($sliders->isNotEmpty())
                    <div id="motivasiCarousel" class="relative mb-12 lg:mb-16">
                        <div class="overflow-hidden rounded-3xl shadow-2xl">
                            <div class="motivasi-track flex transition-transform duration-400 ease-[cubic-bezier(0.25,0.1,0.25,1)] snap-x snap-mandatory">
                                @foreach($sliders as $slide)
                                    <div class="w-full shrink-0 snap-start relative group">
                                        <!-- Slide utama dengan border pemisah tipis + shadow lapisan -->
                                        <div class="bg-gradient-to-br {{ $slide->gradient ?? 'from-emerald-700 via-teal-700 to-cyan-700' }} text-white rounded-3xl p-6 sm:p-8 lg:p-12 text-center min-h-[420px] sm:min-h-[400px] lg:min-h-[380px] flex flex-col justify-between items-center shadow-2xl border {{ $slide->border_color ?? 'border-emerald-500/30' }} overflow-hidden group-hover:scale-[1.02] transition-transform duration-400 border-l-4 border-r-4 border-l-white/10 border-r-white/10">
                                            <div class="flex flex-col items-center justify-center flex-grow space-y-4 lg:space-y-6 px-4 sm:px-8 lg:px-16">
                                                <h3 class="text-xl sm:text-2xl lg:text-4xl font-extrabold leading-tight">
                                                    {!! $slide->title !!}
                                                </h3>
                                                <p class="text-lg sm:text-xl lg:text-2xl font-semibold line-clamp-4 sm:line-clamp-none">
                                                    {!! $slide->subtitle !!}
                                                </p>
                                            </div>
                                            <a href="{{ $slide->button_link ?? '#rekening' }}"
                                               class="btn btn-lg bg-white text-emerald-800 hover:bg-yellow-300 hover:text-emerald-900 font-bold px-8 sm:px-12 py-4 sm:py-5 rounded-full shadow-xl text-lg sm:text-xl mt-auto w-full sm:w-auto max-w-xs transition-all">
                                                {{ $slide->button_text ?? 'Yuk Sedekah Sekarang' }}
                                            </a>
                                        </div>

                                        <!-- Overlay gelap samping untuk efek pemisah kuat (non-aktif) -->
                                        <div class="absolute inset-y-0 left-0 w-24 bg-gradient-to-r from-black/40 to-transparent pointer-events-none opacity-60 group-[.active]:opacity-0 transition-opacity duration-400"></div>
                                        <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-l from-black/40 to-transparent pointer-events-none opacity-60 group-[.active]:opacity-0 transition-opacity duration-400"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Dots Navigation -->
                        <div class="flex justify-center gap-3 sm:gap-4 mt-6 sm:mt-8">
                            @foreach($sliders as $index => $slide)
                                <button class="motivasi-dot w-3 h-3 sm:w-3.5 sm:h-3.5 rounded-full bg-emerald-300 hover:bg-emerald-700 transition" data-index="{{ $index }}"></button>
                            @endforeach
                        </div>
                    </div>
                @else
                    <!-- Fallback -->
                    <div class="text-center py-12 bg-white rounded-3xl shadow-lg border border-emerald-100">
                        <p class="text-xl text-slate-600">Belum ada slide motivasi untuk Ramadhan saat ini.</p>
                        <p class="text-sm text-slate-500 mt-2">Admin akan segera menambahkannya.</p>
                    </div>
                @endif

                <div class="max-w-4xl mx-auto bg-white rounded-3xl border border-emerald-100/50 shadow-2xl overflow-hidden">
                    <div class="p-8 lg:p-12">
                        <!-- Rekening Tunggal -->
                        <div class="text-center mb-10">
                            <div class="text-center max-w-2xl mx-auto mb-10">
                                <h3 class="text-2xl font-bold text-emerald-800 mb-4">
                                    Jadikan Harta Lebih Berkah
                                </h3>

                                <p class="text-slate-700 leading-relaxed text-base">
                                    Setiap rupiah yang Anda titipkan akan menjadi bagian dari adzan yang berkumandang,
                                    shalat berjamaah, kajian ilmu, serta kegiatan sosial umat.
                                </p>

                                <p class="mt-3 text-slate-600">
                                    InsyaAllah menjadi <span class="font-semibold text-emerald-700">amal jariyah</span>
                                    yang pahalanya terus mengalir, meski kita telah tiada.
                                </p>
                            </div>
                            <h3 class="text-2xl font-bold text-emerald-800 mb-4">Salurkan Sedekah Anda</h3>
                            <div class="inline-block bg-emerald-50 rounded-2xl p-5 sm:p-6 shadow-inner w-full max-w-md mx-auto">
                                <p class="text-lg font-semibold text-emerald-700 mb-2">Bank {!! profil('bank_name') !!} • {!! profil('bank_code') !!}</p>
                                
                                <!-- Nomor rekening + copy – dengan chunking & hint -->
                                <div class="relative flex items-center justify-center gap-3 mb-3 bg-white/70 rounded-xl px-4 py-3 shadow-sm">
                                    <p 
                                        id="rekeningNum" 
                                        class="text-xl sm:text-2xl font-bold text-slate-900 tracking-widest whitespace-nowrap overflow-x-auto touch-pan-x"
                                        style="max-width: 75%; scrollbar-width: thin;"
                                    >
                                        {{ trim(chunk_split(preg_replace('/\D/','', profil('rekening')), 4, ' ')) }}
                                    </p>
                                    <button
                                        type="button"
                                        onclick="copyToClipboard('{!! profil('rekening') !!}')"
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
                            <p class="text-sm text-slate-500 mt-4 italic">
                                Donasi digunakan untuk operasional masjid, kegiatan dakwah,
                                pendidikan Al-Qur’an, serta layanan sosial jamaah.
                            </p>

                            <p class="text-sm text-emerald-700 mt-2 font-medium">
                                📊 Laporan penyaluran dipublikasikan secara berkala.
                            </p>
                        </div>

                        <!-- QRIS -->
                        <div class="text-center mb-10">
                            <h3 class="text-2xl font-bold text-emerald-800 mb-4">Sedekah Lebih Mudah (QRIS)</h3>
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
                        <div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-5 mt-8 text-center">
                            <p class="text-emerald-800 italic">
                                “Ya Allah, terimalah sedekah dari para dermawan kami,
                                lapangkan rezekinya, sehatkan badannya,
                                dan jadikan sebagai pemberat amal kebaikan di akhirat.”
                            </p>
                        </div>
                        <!-- CTA Besar -->
                        <div class="text-center mt-10">
                            <a href="https://wa.me/628121073583?text=Assalamu'alaikum%20Bapak%20Ari%20saya%20ingin%20donasi..."
                               target="_blank"
                               class="inline-flex items-center px-8 sm:px-12 py-4 sm:py-6 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-full shadow-2xl text-lg sm:text-xl">
                                💚 Konfirmasi Sedekah
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
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
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
        
        <section id="layanan_jamaah" class="py-16 bg-gradient-to-br from-slate-50 to-white">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">

                    <!-- Kolom Kiri: Maps & Alamat (tetap) -->
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

                    <!-- Kolom Kanan: Kontak + Form -->
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

                        <!-- FORM PESAN DENGAN reCAPTCHA v3 -->
                        <form id="contactForm" class="mt-3 space-y-6">
                            @csrf
                            <input type="hidden" name="g-recaptcha-response" id="recaptchaToken">

                            <!-- Nama -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Anda</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="nama" id="contactNama" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white" placeholder="Masukkan nama lengkap (Optional)" />
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👤</span>
                                </div>
                                <div class="error text-red-600 text-xs mt-1 hidden" id="error-nama"></div>
                            </div>

                            <!-- Telepon -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nomor Telepon (opsional)</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="telepon" id="contactTelp" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white" placeholder="Contoh: 08123456789" />
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📱</span>
                                </div>
                                <div class="error text-red-600 text-xs mt-1 hidden" id="error-telepon"></div>
                            </div>

                            <!-- Pesan -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Pesan atau Saran <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <textarea name="pesan" id="contactPesan" rows="5" required class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none" placeholder="Silakan sampaikan pertanyaan, saran, atau keperluan terkait kegiatan masjid."></textarea>
                                    <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">💬</span>
                                </div>
                                <div class="error text-red-600 text-xs mt-1 hidden" id="error-pesan"></div>
                            </div>

                            <!-- Submit & Status -->
                            <div class="flex items-center justify-between pt-4">
                                <button id="contactSubmitBtn" type="submit" class="px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base">
                                    Kirim Pesan
                                </button>
                                <div id="contactStatus" class="text-sm ml-4"></div>
                            </div>
                            <div id="recaptcha-credit" class="text-xs text-gray-500 mt-2 text-right">
                                This site is protected by reCAPTCHA and the Google 
                                <a href="https://policies.google.com/privacy" target="_blank" class="text-emerald-600 hover:underline">Privacy Policy</a> and 
                                <a href="https://policies.google.com/terms" target="_blank" class="text-emerald-600 hover:underline">Terms of Service</a> apply.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>

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
        #layanan_jamaah input,
        #layanan_jamaah textarea {
            border-color: #cbd5e1;
            color: #0f172a;
            background: white;
        }

        #layanan_jamaah input:focus,
        #layanan_jamaah textarea:focus {
            border-color: #059669;
            box-shadow: 0 0 0 4px rgba(5,150,105,0.18);
        }

        #layanan_jamaah form {
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


        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('quote-container');
            if (!container) return;

            // Ambil semua quote aktif dari Blade (sudah di-pass dari controller)
            const quotes = @json($quoteHarianList->map(function($q) {
                return ['title' => $q->title, 'text' => $q->text];
            })->toArray());

            if (quotes.length <= 1) return; // tidak perlu rotate kalau cuma 1 atau kosong

            // Acak urutan quote setiap load halaman
            quotes.sort(() => Math.random() - 0.5);

            let currentIndex = 0;
            let timer = null;

            // Buat elemen quote baru
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

            // Ganti quote dengan animasi
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
                timer = setInterval(rotateQuote, 7000); // ganti setiap 7 detik
            }

            // Pause saat hover
            container.addEventListener('mouseenter', () => {
                if (timer) {
                    clearInterval(timer);
                    timer = null;
                }
            });

            container.addEventListener('mouseleave', () => {
                if (!timer) startRotation();
            });

            // Mulai setelah baca quote pertama (delay 5 detik)
            setTimeout(() => {
                rotateQuote();
                startRotation();
            }, 5000);
        });

        document.addEventListener('DOMContentLoaded', function () {
            const carousel = document.getElementById('motivasiCarousel');
            if (!carousel) return;

            const track = carousel.querySelector('.motivasi-track');
            const dots = carousel.querySelectorAll('.motivasi-dot');
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

                // Tambah class active ke slide yang sedang dilihat (untuk overlay)
                document.querySelectorAll('.motivasi-track > div').forEach((slide, i) => {
                    slide.classList.toggle('active', i === index);
                });
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

            carousel.addEventListener('mouseenter', stopAuto);
            carousel.addEventListener('mouseleave', startAuto);

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

        window.addEventListener('load', function () {
            const loader = document.getElementById('page-loader');

            // simpan hash tujuan
            const hash = window.location.hash;

            if (loader) {
                loader.classList.add('opacity-0','pointer-events-none');

                setTimeout(() => {
                    loader.remove();

                    // 🔥 setelah loader hilang → baru scroll
                    if(hash){
                        const target = document.querySelector(hash);
                        if(target){
                            setTimeout(()=>{
                                target.scrollIntoView({
                                    behavior:'smooth',
                                    block:'start'
                                });
                            },150);
                        }
                    }
                }, 600);
            }
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

            // Reset UI sebelum kirim
            $('.error, .invalid-feedback').remove();
            $('input, textarea').removeClass('border-red-500');
            $status.html('').removeClass('text-green-600 text-red-600');
            $btn.prop('disabled', true).text('Mengirim...');

            // Generate reCAPTCHA v3 token (invisible)
            grecaptcha.ready(function() {
                grecaptcha.execute('{{ env('RECAPTCHA_SITE_KEY') }}', {action: 'submit_saran'})
                    .then(function(token) {
                        // Masukkan token ke hidden input
                        $('#recaptchaToken').val(token);

                        // Siapkan data form
                        const formData = $form.serialize();

                        $.ajax({
                            url: '{{ route("kontak.kirim") }}',
                            type: 'POST',
                            data: formData,
                            success: function(res) {
                                if (res.success) {
                                    $status.html('<span class="text-green-600 font-medium">' + (res.message || 'Pesan berhasil dikirim! Terima kasih.') + '</span>');
                                    $form[0].reset(); // reset hanya saat sukses
                                } else {
                                    $status.html('<span class="text-red-600">' + (res.message || 'Gagal mengirim.') + '</span>');
                                }
                            },
                            error: function(xhr) {
                                let errorMsg = 'Terjadi kesalahan. Coba lagi nanti.';
                                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                                    const errors = xhr.responseJSON.errors;
                                    let firstError = '';

                                    $.each(errors, function(field, messages) {
                                        const msg = messages[0];
                                        firstError = firstError || msg;

                                        // Tampil error di bawah field
                                        const $input = $form.find('[name="' + field + '"]');
                                        if ($input.length) {
                                            $input.addClass('border-red-500');
                                            $input.after('<div class="error text-red-600 text-xs mt-1">' + msg + '</div>');
                                        }
                                    });

                                    if (firstError) {
                                        $status.html('<span class="text-red-600">' + firstError + '</span>');
                                    }
                                } else {
                                    // Error lain (500, network, dll)
                                    try {
                                        const json = xhr.responseJSON || JSON.parse(xhr.responseText);
                                        errorMsg = json.message || errorMsg;
                                    } catch (e) {
                                        // ignore parse error
                                    }
                                    $status.html('<span class="text-red-600">' + errorMsg + '</span>');
                                }
                            },
                            complete: function() {
                                $btn.prop('disabled', false).text('Kirim Pesan');
                            }
                        });
                    })
                    .catch(function(error) {
                        $status.html('<span class="text-red-600">Gagal verifikasi reCAPTCHA. Coba lagi atau refresh halaman.</span>');
                        $btn.prop('disabled', false).text('Kirim Pesan');
                    });
            });
        });


    /* ===================== DETEKSI LOKASI USER ===================== */
    (async function () {

        const today = new Date().toISOString().slice(0,10);
        const lastCheck = localStorage.getItem('masjid_location_date');

        // sudah pernah cek hari ini → tidak minta GPS lagi
        if (lastCheck === today) return;

        // browser tidak support
        if (!navigator.geolocation) return;

        // jangan ganggu user saat loading awal
        setTimeout(() => {

            navigator.geolocation.getCurrentPosition(async (pos) => {

                try {
                    const res = await fetch("{{ route('set.location') }}", {
                        method: "POST",
                            credentials: "same-origin", // 🔥 INI YANG HILANG
                            headers: {
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": "{{ csrf_token() }}"
                            },
                            body: JSON.stringify({
                            lat: pos.coords.latitude,
                            lng: pos.coords.longitude
                            })
                        });


                    const data = await res.json();

                    if (data.success) {
                        localStorage.setItem('masjid_location_date', today);

                        console.log("Lokasi terdeteksi:", data.city);

                        // reload sekali agar jadwal ikut kota user
                        setTimeout(()=>location.reload(), 600);
                    }

                } catch (e) {
                    console.log("Gagal kirim lokasi");
                }

            }, () => {
                console.log("User menolak izin lokasi");
            });

        }, 2500); // tunggu halaman selesai render dulu

    })();

</script>
@endpush