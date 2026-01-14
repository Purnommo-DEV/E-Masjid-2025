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

    <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-slate-50 to-sky-50">

        {{-- HERO --}}
        <section class="relative overflow-hidden py-16 lg:py-24">
            <div class="absolute inset-0">
                <div class="absolute -top-24 -left-10 w-80 h-80 bg-emerald-200 rounded-full blur-3xl opacity-60"></div>
                <div class="absolute top-40 -right-24 w-96 h-96 bg-sky-200 rounded-full blur-3xl opacity-60"></div>
                <div class="absolute -bottom-10 left-1/2 -translate-x-1/2 w-[600px] h-40 bg-gradient-to-r from-emerald-200/60 via-teal-200/60 to-sky-200/60 blur-3xl opacity-70"></div>
            </div>

            <div class="container mx-auto px-4 lg:px-6 relative">
                <div class="grid lg:grid-cols-[1.25fr_minmax(0,1fr)] gap-12 items-center">
                    {{-- HERO TEXT --}}
                    <div class="space-y-8">
                        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-50 border border-emerald-200 text-[12px] text-emerald-800 shadow-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-600"></span>
                            </span>
                            Selamat datang di {{ $profil->nama ?? 'Masjid Al-Ikhlas' }}
                        </div>

                        <div class="space-y-4">
                            <h1 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 leading-tight">
                                Pusat Ibadah,
                                <span class="block text-transparent bg-clip-text bg-gradient-to-r from-emerald-600 via-teal-600 to-sky-600">
                                    Dakwah & Layanan Umat.
                                </span>
                            </h1>
                            <p class="text-sm sm:text-base text-slate-600 max-w-xl leading-relaxed">
                                {{ $profil->tagline ?? 'Menghidupkan shalat berjamaah, menguatkan ukhuwah, dan menebar manfaat melalui program-program masjid.' }}
                            </p>
                        </div>

                        {{-- CTA --}}
                        <div class="flex flex-wrap gap-3">
                            <a href="#donasi"
                               class="btn btn-sm sm:btn-md bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 hover:from-emerald-700 hover:via-teal-700 hover:to-emerald-600 border-none text-white shadow-lg shadow-emerald-500/40">
                                🌟 Donasi Sekarang
                            </a>
                            <a href="#acara"
                               class="btn btn-sm sm:btn-md btn-outline border-emerald-400/70 text-emerald-700 hover:bg-emerald-50">
                                Lihat Agenda
                            </a>
                        </div>

                        {{-- STATS --}}
                        <div class="grid grid-cols-3 gap-4 pt-4 text-[11px] sm:text-xs">
                            <div class="stat bg-white/80 backdrop-blur-sm rounded-2xl border border-emerald-100 shadow-sm p-3 flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <div class="stat-title text-[11px] text-slate-500">Program Rutin</div>
                                    <span class="text-[11px] text-emerald-500">📘</span>
                                </div>
                                <div class="stat-value text-base sm:text-lg text-emerald-700">
                                    +{{ $profil->jumlah_program ?? 12 }}
                                </div>
                            </div>

                            <div class="stat bg-white/80 backdrop-blur-sm rounded-2xl border border-teal-100 shadow-sm p-3 flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <div class="stat-title text-[11px] text-slate-500">Jamaah Aktif</div>
                                    <span class="text-[11px] text-teal-500">🕌</span>
                                </div>
                                <div class="stat-value text-base sm:text-lg text-teal-700">
                                    +{{ $profil->jumlah_jamaah ?? 300 }}
                                </div>
                            </div>

                            <div class="stat bg-white/80 backdrop-blur-sm rounded-2xl border border-amber-100 shadow-sm p-3 flex flex-col gap-1">
                                <div class="flex items-center justify-between">
                                    <div class="stat-title text-[11px] text-slate-500">Program Sosial</div>
                                    <span class="text-[11px] text-amber-500">🤝</span>
                                </div>
                                <div class="stat-value text-base sm:text-lg text-amber-700">
                                    +{{ $profil->jumlah_program_sosial ?? 8 }}
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- JADWAL SHOLAT --}}
                    <div id="jadwal" class="lg:justify-self-end">
                        <div class="card w-full max-w-md bg-white/90 backdrop-blur-sm border border-emerald-100 rounded-3xl shadow-xl shadow-emerald-100/60">
                            <div class="card-body p-5 sm:p-6">
                                <h2 class="font-semibold text-sm sm:text-base text-slate-900 mb-2">Waktu Sholat Hari Ini</h2>
                                <p class="text-[11px] text-slate-500 mb-4">{{ now()->translatedFormat('l, d M Y') }}</p>

                                <div class="grid grid-cols-3 gap-2 text-xs sm:text-sm">
                                    @foreach(['subuh','dzuhur','ashar','maghrib','isya'] as $p)
                                        <div class="p-2.5 rounded-xl bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 text-center">
                                            <div class="text-[11px] text-emerald-700 font-medium">
                                                {{ ucfirst($p) }}
                                            </div>
                                            <div class="text-base font-semibold text-emerald-800">
                                                {{ $jadwalSholat[$p] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="mt-3 text-[10px] text-slate-500 text-center">
                                    * Waktu sholat mengikuti pengaturan lokasi masjid.
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
                        <div class="rounded-[2rem] bg-gradient-to-r from-emerald-100/70 via-teal-50 to-sky-100/70 p-4 shadow-[0_18px_45px_rgba(15,118,110,0.18)]">
                            <div class="overflow-hidden rounded-[1.7rem]">
                                <div class="banner-track flex transition-transform duration-500 ease-out">
                                    @foreach($pages as $pageBanners)
                                        <div class="banner-page w-full shrink-0">
                                            <div class="grid gap-4 lg:gap-6 lg:grid-cols-3">
                                                @foreach($pageBanners as $banner)
                                                    <div class="relative rounded-3xl overflow-hidden bg-emerald-700 text-white shadow-xl shadow-emerald-900/40 h-full">
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

        {{-- AGENDA (menggunakan array $acaras dari AcaraService) --}}
        <section id="acara" class="py-16 bg-white/90">
            <div class="container mx-auto px-4 lg:px-6">

                <div class="flex items-center justify-between mb-8">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-600 mb-1">Agenda</p>
                        <h2 class="text-xl sm:text-2xl font-semibold text-slate-900 flex items-center gap-3">
                            Agenda Kegiatan Terdekat
                            <span class="h-px w-12 bg-gradient-to-r from-emerald-500 to-teal-400 rounded-full hidden sm:inline-block"></span>
                        </h2>
                    </div>
                    <a href="{{ route('acara.index') }}" class="text-xs sm:text-[13px] text-emerald-600 hover:text-emerald-800 font-medium inline-flex items-center gap-1">
                        Lihat semua
                        <span>→</span>
                    </a>
                </div>

                {{-- layout grid: list + sidebar --}}
                <div class="grid lg:grid-cols-[minmax(0,2fr)_minmax(0,1fr)] gap-6">

                    {{-- List utama acara --}}
                    <div class="space-y-4">
                        @forelse($acaras as $acara)
                            {{-- setiap $acara diasumsikan array dengan key:
                                 title, tanggal_label, waktu_label, image, kategori, pemateri, excerpt, lokasi, url --}}
                            <div class="bg-white border border-slate-100 rounded-2xl shadow-sm hover:shadow-lg transition p-4 hover:border-emerald-200">
                                <div class="flex items-start gap-3">
                                    {{-- gambar (desktop) --}}
                                    <img
                                        src="{{ $acara['image'] ?? asset('images/masjid-banner.jpg') }}"
                                        alt="{{ $acara['title'] ?? 'Acara' }}"
                                        class="w-20 h-16 object-cover rounded-md hidden sm:block"
                                    >

                                    <div class="flex-1">
                                        <div class="flex justify-between text-[11px] mb-1">
                                            <span class="badge bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                {{ $acara['kategori'] ?? 'Acara' }}
                                            </span>
                                            <span class="text-slate-500">
                                                {{ $acara['tanggal_label'] ?? ($acara['tanggal'] ?? '-') }}
                                            </span>
                                        </div>

                                        <h3 class="font-semibold text-sm text-slate-900">
                                            {{ $acara['title'] ?? ($acara['judul'] ?? '-') }}
                                        </h3>

                                        @if(!empty($acara['pemateri']))
                                            <p class="text-[11px] text-emerald-600">{{ $acara['pemateri'] }}</p>
                                        @endif

                                        <p class="text-[12px] text-slate-600 mt-1 line-clamp-2">
                                            {!! $acara['excerpt'] ?? \Illuminate\Support\Str::limit(strip_tags($acara['deskripsi'] ?? ''), 90) !!}
                                        </p>

                                        <div class="flex justify-between text-[11px] text-slate-500 my-2">
                                            <div>⏰ {{ $acara['waktu_label'] ?? ($acara['waktu'] ?? '-') }} 
                                                @if(!empty($acara['waktu_teks']))
                                                    ( {{ $acara['waktu_teks'] }} )
                                                @endif
                                            </div>
                                            <div>📍 
                                                {{ $acara['lokasi'] ?? '-' }}
                                            </div>
                                        </div>
                                        <a href="{{ $acara['url'] ?? (isset($acara['slug']) ? route('acara.show', $acara['slug']) : '#') }}"
                                           class="btn btn-xs btn-outline border-emerald-500 text-emerald-700 w-full rounded-full">
                                            Detail
                                        </a>                            
                                    </div>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-500">Belum ada agenda.</p>
                        @endforelse
                    </div>

                    {{-- Sidebar --}}
                    <div class="space-y-4">

                        {{-- Shalat Jum'at --}}
                        <div class="rounded-2xl bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-500 text-white p-5 shadow-lg">
                            <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-100 mb-1">Shalat Jum’at</p>
                            <h3 class="text-sm font-semibold mb-2">Pekan Ini</h3>

                            @php
                                // fallback data jika service belum kirim
                                $jumat = $jumat ?? [
                                    'khatib' => 'Ust. Dr. Muhammad',
                                    'tema'   => 'Jaga Hati di Era Digital',
                                    'tgl'    => 'Jum’at, 12 Jan',
                                    'jam'    => '11.45 - 12.30'
                                ];
                            @endphp

                            <dl class="space-y-1 text-[12px]">
                                <div class="flex gap-2"><dt class="w-10 text-emerald-100/80">Khatib</dt><dd class="flex-1">{{ $jumat['khatib'] }}</dd></div>
                                <div class="flex gap-2"><dt class="w-10 text-emerald-100/80">Tema</dt><dd class="flex-1">{{ $jumat['tema'] }}</dd></div>
                                <div class="flex gap-2"><dt class="w-10 text-emerald-100/80">Tanggal</dt><dd class="flex-1">{{ $jumat['tgl'] }}</dd></div>
                                <div class="flex gap-2"><dt class="w-10 text-emerald-100/80">Waktu</dt><dd class="flex-1">{{ $jumat['jam'] }}</dd></div>
                            </dl>
                        </div>

                        {{-- Mini Kalender Pekan Ini --}}
                        @php $today = \Carbon\Carbon::today(); @endphp
                        <div class="rounded-2xl bg-white border border-slate-100 shadow-sm p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="text-sm font-semibold text-slate-900">Kalender Pekan Ini</h3>
                                <span class="text-[11px] text-slate-500">{{ $today->translatedFormat('F Y') }}</span>
                            </div>

                            <div class="grid grid-cols-7 gap-1.5 text-center">
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $date = $today->copy()->addDays($i);
                                        $isToday = $date->isToday();
                                    @endphp
                                    <div class="flex flex-col items-center text-[11px]">
                                        <span class="text-[10px] text-slate-400">{{ $date->translatedFormat('D') }}</span>
                                        <span class="flex h-7 w-7 items-center justify-center rounded-full {{ $isToday ? 'bg-emerald-600 text-white' : 'bg-slate-50 border border-slate-100' }}">
                                            {{ $date->format('d') }}
                                        </span>
                                    </div>
                                @endfor
                            </div>
                        </div>

                        {{-- Tombol Lihat Semua Acara --}}
                        <div class="rounded-2xl bg-white border border-slate-100 shadow-sm p-4 text-center">
                            <a href="#!" class="btn btn-sm bg-emerald-600 hover:bg-emerald-700 text-white rounded-full w-full">
                                Lihat semua agenda
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        {{-- QUOTE HARI INI --}}
        <section class="py-10">
            <div class="container mx-auto px-4 lg:px-6">
                @php
                    $quotes = [
                        ['title'=>'QS. Al-Baqarah:186','text'=>'“Aku mengabulkan doa orang yang memohon kepada-Ku.”'],
                        ['title'=>'HR. Muslim','text'=>'“Shalat terbaik setelah wajib adalah shalat malam.”'],
                        ['title'=>'HR. Tirmidzi','text'=>'“Senyum kepada saudaramu adalah sedekah.”'],
                    ];
                    $quote=$quotes[array_rand($quotes)];
                @endphp

                <div class="rounded-3xl bg-gradient-to-r from-emerald-600 via-teal-600 to-emerald-500 text-white px-6 py-5 shadow-lg">
                    <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-100">Pengingat Hari Ini</p>
                    <h3 class="font-semibold text-sm mt-1">{{ $quote['title'] }}</h3>
                    <p class="text-[13px] mt-2">{{ $quote['text'] }}</p>
                </div>
            </div>
        </section>

        {{-- === BERITA & PENGUMUMAN === --}}
        <section class="py-16 bg-gradient-to-br from-emerald-50 via-white to-sky-50">
            <div class="container mx-auto px-4 lg:px-6 grid lg:grid-cols-[1.5fr_minmax(0,1fr)] gap-10">

                {{-- BERITA --}}
                <div>
                    <div class="flex justify-between mb-5">
                        <div>
                            <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Berita</p>
                            <h2 class="text-xl font-semibold text-slate-900">Berita Masjid</h2>
                        </div>
                        <a href="{{ route('berita.index') }}" class="text-xs text-emerald-700">Semua →</a>
                    </div>

                    <div class="space-y-3">
                        @forelse($beritas as $b)
                            <a href="{{ $b['url'] }}" class="block group bg-white border border-slate-100 p-3 rounded-2xl shadow-sm hover:border-emerald-300 transition">
                                <div class="flex gap-3 items-start">
                                    <div class="w-20 h-16 rounded overflow-hidden flex-shrink-0">
                                        <img src="{{ $b['gambar'] }}" alt="{{ $b['judul'] }}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-semibold text-slate-900">{{ $b['judul'] }}</h3>
                                        <p class="text-[12px] text-slate-600 mt-1">{{ $b['ringkas'] }}</p>
                                        <div class="mt-2 text-[10px] text-slate-400">{{ $b['waktu'] }}</div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="text-sm text-slate-500">Belum ada berita.</div>
                        @endforelse
                    </div>
                </div>

                {{-- =================================================
                     PENGUMUMAN — Tampilan rapi + preview modal
                     ================================================= --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-sm font-semibold text-slate-900">Pengumuman</h2>
                        <a href="{{ route('pengumuman.index') }}" class="text-xs text-emerald-700 inline-flex items-center gap-1">
                            Semua
                            <span>→</span>
                        </a>
                    </div>

                    <div class="space-y-2">
                        @forelse($pengumuman as $p)
                            @php
                                // safety: ambil excerpt / strip tags untuk preview singkat
                                $short = \Illuminate\Support\Str::limit(strip_tags($p['isi'] ?? ''), 120);
                                $tanggal = $p['tanggal'] ?? (isset($p['created_at']) ? \Carbon\Carbon::parse($p['created_at'])->translatedFormat('d M Y') : '-');
                            @endphp

                            <article
                                class="bg-white rounded-2xl border border-amber-100 shadow-sm p-3 transition hover:shadow-md hover:-translate-y-0.5 flex items-start gap-3"
                                role="article"
                            >
                                {{-- ikon --}}
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-lg bg-amber-50 border border-amber-200 flex items-center justify-center text-amber-700 text-lg">
                                        📢
                                    </div>
                                </div>

                                {{-- konten --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-semibold text-slate-900 leading-snug truncate">
                                                {{ $p['judul'] }}
                                            </h3>
                                            <p class="text-[12px] text-slate-600 mt-1 line-clamp-2">
                                                {{ $short }}
                                            </p>
                                        </div>

                                        <div class="flex flex-col items-end gap-2 flex-shrink-0">
                                            <div class="text-[11px] text-amber-700 font-medium">
                                                {{ $tanggal }}
                                            </div>

                                            <div class="flex items-center gap-2">
                                                {{-- tombol preview (buka modal) --}}
                                                <button
                                                    type="button"
                                                    class="btn btn-xs bg-amber-100 text-amber-800 border-none rounded-full px-3 py-1"
                                                    data-pengumuman-id="{{ e($p['id'] ?? '') }}"
                                                    data-pengumuman-judul="{{ e($p['judul'] ?? '') }}"
                                                    data-pengumuman-isi="{{ e(strip_tags($p['isi'] ?? '')) }}"
                                                    data-pengumuman-url="{{ e($p['url'] ?? '#') }}"
                                                    onclick="openPengumumanPreview(this)"
                                                    aria-label="Lihat pengumuman {{ e($p['judul'] ?? '') }}">
                                                    Lihat
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="text-xs text-slate-500">Belum ada pengumuman.</div>
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
        <section class="py-16 bg-white">
            <div class="container mx-auto px-4 lg:px-6 text-center">

                <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">
                    Layanan Masjid
                </p>
                <h2 class="text-2xl font-semibold text-slate-900">
                    Kami Hadir untuk Melayani
                </h2>

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mt-8">

                    @forelse($layanans as $l)
                        @php
                            $icon = $l->icon ?? '🕌';
                        @endphp

                        <div class="rounded-2xl bg-gradient-to-b from-white to-emerald-50 
                            border border-emerald-100 p-6 shadow-sm hover:shadow-lg transition">

                            {{-- ICON --}}
                            <div
                                class="flex items-center justify-center mx-auto mb-3
                                bg-emerald-50 border border-emerald-100 rounded-xl 
                                w-12 h-12 text-xl">
                                {{ $icon }}
                            </div>

                            {{-- Judul --}}
                            <h3 class="text-sm font-semibold text-slate-900">
                                {{ $l->judul }}
                            </h3>

                            {{-- Deskripsi --}}
                            <p class="text-[12px] text-slate-600 mt-1">
                                {!! \Illuminate\Support\Str::limit(strip_tags($l->deskripsi ?? ''), 90) !!}
                            </p>

                        </div>
                    @empty
                        <p class="col-span-full text-sm text-slate-500">
                            Belum ada layanan tersedia.
                        </p>
                    @endforelse

                </div>
            </div>
        </section>

        {{-- === DONASI === --}}
        <section id="donasi" class="py-16 bg-white">
            <div class="container mx-auto px-4 lg:px-6">

                <div class="text-center mb-8">
                    <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Donasi</p>
                    <h2 class="text-2xl font-semibold text-slate-900">Program Donasi Umat</h2>
                </div>

                {{-- Ringkasan --}}
                <div class="max-w-xl mx-auto mb-6">
                    <div class="rounded-2xl bg-emerald-50 border border-emerald-100 px-4 py-3 text-[12px]">
                        <p class="font-semibold text-emerald-700">Ringkasan Infaq Pekan Ini</p>
                        <div class="flex justify-between mt-1 text-slate-600">
                            <span>Masuk: Rp12.500.000</span>
                            <span>Disalurkan: Rp8.350.000</span>
                        </div>
                    </div>
                </div>

                @php
                    $programs = [
                        ['judul'=>'Operasional Masjid','desc'=>'Listrik, kebersihan, keamanan.'],
                        ['judul'=>'Pembangunan & Renovasi','desc'=>'Perbaikan & perluasan masjid.'],
                        ['judul'=>'Beasiswa Santri','desc'=>'Dukung pendidikan Qur’an.'],
                        ['judul'=>'Santunan Sosial','desc'=>'Bantuan dhuafa & yatim.'],
                    ];
                @endphp

                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($programs as $prog)
                        <div class="p-5 rounded-2xl bg-gradient-to-b from-white to-emerald-50 border border-emerald-100 text-center shadow-sm hover:shadow-lg">
                            <div class="w-12 h-12 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-3">💚</div>
                            <h3 class="font-semibold text-sm">{{ $prog['judul'] }}</h3>
                            <p class="text-[12px] text-slate-600">{{ $prog['desc'] }}</p>
                            <a href="#" class="btn btn-xs bg-gradient-to-r from-emerald-600 to-teal-600 text-white w-full rounded-full mt-4">Donasi</a>
                        </div>
                    @endforeach
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
                <div class="grid grid-cols-3 md:grid-cols-6 gap-2 sm:gap-3">
                    @forelse($galeri as $g)
                        <button type="button"
                            class="relative group rounded-xl overflow-hidden shadow-sm"
                            data-galeri-item="true"
                            data-id="{{ $g['id'] }}"
                            data-title="{{ $g['judul'] }}"
                            data-img="{{ $g['img'] }}">
                            
                            <img src="{{ $g['img'] }}"
                                 class="w-full h-20 sm:h-28 md:h-32 object-cover group-hover:scale-110 transition">

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
        <section id="kontak" class="py-16 bg-white">
            <div class="container mx-auto px-4 lg:px-6 grid lg:grid-cols-2 gap-10">
                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Lokasi</p>
                    <h2 class="text-xl sm:text-2xl font-semibold text-slate-900">Lokasi Masjid</h2>
                    <p class="text-sm text-slate-600 mt-2">{{ $profil->alamat ?? 'Alamat belum tersedia.' }}</p>
                    <div class="mt-4 rounded-2xl border h-56 sm:h-64 flex items-center justify-center text-[11px] text-slate-500">
                        {{-- Kalau mau, masukkan embed Google Maps di sini --}}
                        @if(!empty($profil->latitude) && !empty($profil->longitude))
                            <iframe
                                class="w-full h-full rounded-2xl"
                                frameborder="0"
                                src="https://www.google.com/maps?q={{ $profil->latitude }},{{ $profil->longitude }}&z=16&output=embed"
                                allowfullscreen>
                            </iframe>
                        @else
                            [ MAP MASJID ]
                        @endif
                    </div>
                </div>

                <div>
                    <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Kontak</p>
                    <h2 class="text-xl sm:text-2xl font-semibold text-slate-900">Kontak & Pesan Jamaah</h2>

                    <div class="text-sm text-slate-600 mt-3 space-y-1.5">
                        <div>📞 WhatsApp: <span class="font-medium">{{ $profil->telepon ?? ($profil->no_wa ?? '-') }}</span></div>
                        <div>✉️ Email: <span class="font-medium">{{ $profil->email ?? '-' }}</span></div>
                        <div>⏰ Jam Layanan: {{ $profil->jam_layanan ?? 'Ba’da Subuh - Isya' }}</div>
                    </div>
                    <form id="contactForm" class="mt-6 space-y-4 bg-white p-5 rounded-2xl shadow-sm border border-slate-100">
                        @csrf

                        {{-- NAMA --}}
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-medium text-slate-700">Nama Anda</span>
                            </label>
                            <input
                                type="text"
                                name="nama"
                                id="contactNama"
                                required
                                class="input input-bordered w-full rounded-xl focus:border-emerald-500 focus:ring-emerald-300"
                                placeholder="Masukkan nama lengkap"
                            />
                        </div>

                        {{-- TELEPON --}}
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-medium text-slate-700">Nomor Telepon (opsional)</span>
                            </label>
                            <input
                                type="text"
                                name="telepon"
                                id="contactTelp"
                                class="input input-bordered w-full rounded-xl focus:border-emerald-500 focus:ring-emerald-300"
                                placeholder="Contoh: 08123456789"
                            />
                        </div>

                        {{-- PESAN --}}
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-medium text-slate-700">Pesan atau Saran</span>
                            </label>
                            <textarea
                                name="pesan"
                                id="contactPesan"
                                rows="4"
                                required
                                class="textarea textarea-bordered w-full rounded-xl focus:border-emerald-500 focus:ring-emerald-300"
                                placeholder="Ketik pesan Anda..."
                            ></textarea>
                        </div>

                        {{-- BUTTON + STATUS --}}
                        <div class="flex items-center justify-between pt-2">
                            <button
                                id="contactSubmitBtn"
                                type="submit"
                                class="btn px-6 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-md border-0"
                            >
                                Kirim Pesan
                            </button>

                            <div id="contactStatus" class="text-sm text-slate-500 ml-3"></div>
                        </div>
                    </form>


                </div>
            </div>
        </section>

        {{-- FLOATING WA --}}
        @php $wa = preg_replace('/[^0-9]/','',$profil->telepon??'6281234567890'); @endphp
        <a href="https://wa.me/{{ $wa }}" target="_blank"
           class="fixed bottom-6 right-5 z-40 btn btn-circle bg-emerald-600 text-white shadow-xl">
            💬
        </a>

    </div>
@endsection

@push('style')
    <style>
        /* line-clamp util (fallback if plugin tidak tersedia) */
        .line-clamp-2 { 
            overflow: hidden; 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
        }

        /* modal-box .prose height */
        dialog.modal .modal-box .prose { 
            max-height: 55vh; 
            overflow:auto; 
        }

        /* Make the preview button not move when the article height changes */
        article .flex-shrink-0 { 
            flex-shrink: 0; 
        }

        /* backdrop + modal styling (override default agar konsisten) */
        dialog#pengumumanModal::backdrop {
            background: rgba(2,6,23,0.5);
            backdrop-filter: blur(4px) saturate(1.02);
        }

        /* For browsers that don't support ::backdrop, use the fallback overlay */
        dialog#pengumumanModal .modal-backdrop {
            display:none;
        }
        .dialog-polyfill .modal-backdrop { 
            display:block; 
        }

        /* ensure modal-box has subtle entrance */
        dialog#pengumumanModal .modal-box {
            border-radius: 14px;
            box-shadow: 0 18px 40px rgba(2,6,23,0.12);
            transform-origin: center top;
            animation: modal-in .14s ease-out;
        }

        @keyframes modal-in {
            from { transform: translateY(-6px) scale(.995); opacity: 0.02; }
            to   { transform: translateY(0) scale(1); opacity: 1; }
        }

        /* make .prose content scroll nicely */
        #pengumumanModalBody::-webkit-scrollbar { 
            height: 6px; 
            width: 8px; 
        }
        
        #pengumumanModalBody::-webkit-scrollbar-thumb { 
            background: rgba(15,23,42,0.06); 
            border-radius: 6px; 
        }

        /* ensure close button stays small and not overlapping text on small screens */
        .btn.btn-circle { 
            width:36px; 
            height:36px; 
            padding:0; 
        }

        /* keep footer buttons fixed height so they don't shift */
        footer .btn { 
            height: 36px; 
            line-height: 1; 
        }

        /* fallback for dialog polyfill: center modal */
        .dialog-polyfill .modal-box { 
            margin: 6vh auto; 
        }

        /* Modern gallery nav buttons */
        .galeri-thumb-active {
            box-shadow: 0 6px 20px rgba(2,6,23,0.18);
            transform: translateY(-4px);
            border-color: rgba(13,148,136,0.18); /* emerald-ish */
        }
        
        /* hide native dialog outline when using showModal fallback */
        dialog[open] { 
            outline: none; 
        }

    </style>
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
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
                    btn.innerHTML = `<img src="${f.url}" class="w-full h-full object-cover" alt="${(f.caption||f.file_name||'foto')}">`;
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