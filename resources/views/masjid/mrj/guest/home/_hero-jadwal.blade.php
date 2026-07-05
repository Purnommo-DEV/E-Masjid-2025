{{-- HERO dengan Background Gambar Masjid
<section id="jadwal" class="home-hero relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden min-h-[600px] lg:min-h-[700px] flex items-center">
    <!-- BACKGROUND GAMBAR MASJID -->
    <div class="absolute inset-0 z-0">
        <img 
            src="{{ asset('storage/mrj/masjid-rj.webp') }}" 
            alt="Masjid Background" 
            class="w-full h-full object-cover object-center"
            loading="lazy"
        >
        <!-- Overlay gelap agar teks terbaca -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/85 via-slate-900/70 to-slate-900/40"></div>
        <!-- OVERLAY BAWAH - DIPERKUAT agar transisi ke section bawah halus -->
        <div class="absolute bottom-0 left-0 right-0 h-48 bg-gradient-to-t from-white via-white/95 to-transparent"></div>
    </div>

    <!-- Background decorations - HILANGKAN karena bikin noise -->
    <!-- <div class="absolute inset-0 pointer-events-none z-0"> ... </div> -->

    <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative z-10">
        <div class="grid lg:grid-cols-2 gap-12 xl:gap-16 items-center">
            <!-- Hero Text (kiri) -->
            <div class="space-y-8 text-center lg:text-left">
                @php
                    $profil = $profil ?? \App\Models\ProfilMasjid::first();
                @endphp

                <div class="hero-eyebrow inline-flex items-center gap-2 px-5 py-2 rounded-full 
                            bg-white/20 backdrop-blur-sm border border-white/30 shadow-sm text-sm text-white">
                    @if($profil && $profil->logo_url)
                        <img src="{{ $profil->logo_url }}" alt="Logo" class="w-6 h-6 rounded-full object-cover">
                    @else
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-70"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-400"></span>
                        </span>
                    @endif
                    Selamat Datang di {{ $profil->nama ?? 'Masjid' }}
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                    Masjid yang Hidup,
                    <span class="block bg-gradient-to-r from-emerald-300 via-teal-300 to-cyan-300 bg-clip-text text-transparent mt-2">
                        Pusat Ibadah & Ukhuwah
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-white/90 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                    {{ e($profil->tagline ?? 'Menghidupkan shalat berjamaah, mempererat silaturahmi, dan menebar kebaikan bagi umat.') }}
                </p>

                <div class="flex flex-wrap justify-center lg:justify-start gap-4 mt-8">
                    <a href="#donasi" class="hero-primary-cta btn btn-lg bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:brightness-110 text-white shadow-xl shadow-emerald-500/30 px-10 py-4 text-base font-bold rounded-full transition-all transform hover:scale-105">
                        🤲 Tunaikan Infak
                    </a>
                    <a href="#acara" class="hero-secondary-cta btn btn-lg border-2 border-white/50 text-white hover:bg-white/20 hover:border-white px-10 py-4 text-base font-bold rounded-full transition-all backdrop-blur-sm">
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
                        <div class="prayer-panel bg-white/85 backdrop-blur-2xl rounded-3xl shadow-2xl shadow-teal-200/40 border border-white/30 overflow-hidden">
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

                                <div class="prayer-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                                    @foreach($sholat as $key => $data)
                                        <div class="prayer-time-card group relative bg-white/95 backdrop-blur-sm rounded-2xl p-5 md:p-6 text-center 
                                                    border border-emerald-100/60 shadow-md hover:shadow-xl hover:shadow-{{ $data['color'] }}-300/40 
                                                    hover:border-{{ $data['color'] }}-400/50 transition-all duration-400 transform hover:-translate-y-2 hover:scale-[1.03] 
                                                    overflow-hidden min-h-[100px] md:min-h-[120px] flex flex-col items-center justify-center">
                                            
                                            <!-- Subtle glow overlay -->
                                            <div class="absolute inset-0 bg-gradient-to-br from-{{ $data['color'] }}-400/0 via-{{ $data['color'] }}-500/10 to-{{ $data['color'] }}-600/0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
                                            
                                            <div class="relative z-10">
                                                <div class="text-xs md:text-sm font-semibold text-{{ $data['color'] }}-700 uppercase tracking-wide mb-2 md:mb-3">
                                                    {{ $data['label'] }}
                                                </div>
                                                <div class="text-lg md:text-xl font-extrabold text-slate-900 group-hover:text-{{ $data['color'] }}-700 transition-colors whitespace-nowrap drop-shadow-sm">
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
</section> --}}

{{-- HERO dengan Background Gambar Masjid --}}
<section id="jadwal" class="home-hero relative pt-16 pb-0 lg:pt-24 lg:pb-0 overflow-hidden min-h-[600px] lg:min-h-[700px] flex items-center">
    <!-- BACKGROUND GAMBAR MASJID -->
    <div class="absolute inset-0 z-0">
        <img 
            src="{{ asset('storage/mrj/masjid-rj.webp') }}" 
            alt="Masjid Background" 
            class="w-full h-full object-cover object-center"
            loading="lazy"
        >
        <!-- Overlay gelap agar teks terbaca -->
        <div class="absolute inset-0 bg-gradient-to-r from-slate-900/85 via-slate-900/70 to-slate-900/40"></div>
    </div>

    <!-- SVG WAVE - JavaScript Canvas Version (2 Layer, Smooth & Lambat) -->
    <style>
        .wave-canvas-container {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 10;
            height: 100px;
            overflow: hidden;
            pointer-events: none;
        }
        
        #waveCanvas {
            width: 100%;
            height: 100%;
            display: block;
        }
    </style>

    <div class="wave-canvas-container">
        <canvas id="waveCanvas"></canvas>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('waveCanvas');
            const ctx = canvas.getContext('2d');
            let width, height;
            
            function resize() {
                const rect = canvas.parentElement.getBoundingClientRect();
                width = canvas.width = rect.width;
                height = canvas.height = rect.height;
            }
            resize();
            window.addEventListener('resize', resize);
            
            let time = 0;
            
            function drawWaves() {
                time += 0.008; // Lebih lambat (sebelumnya 0.015)
                
                ctx.clearRect(0, 0, width, height);
                
                // ============ WAVE 1: PALING BELAKANG (WARNA) ============
                ctx.beginPath();
                ctx.moveTo(0, height);
                for (let x = 0; x <= width; x++) {
                    const y = height * 0.35 + 
                            Math.sin(x * 0.018 + time * 0.8) * height * 0.12 +
                            Math.sin(x * 0.025 + time * 1.0 + 0.5) * height * 0.06;
                    ctx.lineTo(x, y);
                }
                ctx.lineTo(width, height);
                ctx.closePath();
                
                // Gradasi warna soft
                const grad1 = ctx.createLinearGradient(0, 0, width, 0);
                grad1.addColorStop(0, 'rgba(16, 185, 129, 0.20)');   // emerald soft
                grad1.addColorStop(0.5, 'rgba(20, 184, 166, 0.20)'); // teal soft
                grad1.addColorStop(1, 'rgba(6, 182, 212, 0.20)');    // cyan soft
                ctx.fillStyle = grad1;
                ctx.fill();
                
                // ============ WAVE 2: PALING DEPAN (PUTIH) ============
                ctx.beginPath();
                ctx.moveTo(0, height);
                for (let x = 0; x <= width; x++) {
                    const y = height * 0.45 + 
                            Math.sin(x * 0.022 + time * 1.0 + 1.2) * height * 0.08;
                    ctx.lineTo(x, y);
                }
                ctx.lineTo(width, height);
                ctx.closePath();
                ctx.fillStyle = 'rgba(255, 255, 255, 255)';
                ctx.fill();
                
                // ============ PARTIKEL BUIH (5 saja) ============
                const particles = [
                    { x: 0.15, seed: 1.2 },
                    { x: 0.35, seed: 3.8 },
                    { x: 0.55, seed: 5.1 },
                    { x: 0.75, seed: 7.3 },
                    { x: 0.92, seed: 9.5 }
                ];
                
                particles.forEach((p) => {
                    const x = p.x * width + Math.sin(time * 0.2 + p.seed) * 15;
                    const y = height * 0.38 + 
                            Math.sin(x * 0.02 + time * 0.9 + p.seed) * height * 0.15 +
                            Math.sin(x * 0.035 + time * 0.6 + p.seed * 2) * height * 0.08;
                    const r = 1.5 + Math.sin(time * 0.4 + p.seed) * 0.5 + 1;
                    const opacity = 0.3 + Math.sin(time * 0.4 + p.seed) * 0.15 + 0.15;
                    
                    ctx.beginPath();
                    ctx.arc(x, y, r, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(255, 255, 255, ${opacity})`;
                    ctx.fill();
                });
                
                requestAnimationFrame(drawWaves);
            }
            
            drawWaves();
        });
    </script>

    <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative z-10 pb-16">
        <div class="grid lg:grid-cols-2 gap-12 xl:gap-16 items-center">
            <!-- Hero Text (kiri) -->
            <div class="space-y-8 text-center lg:text-left">
                @php
                    $profil = $profil ?? \App\Models\ProfilMasjid::first();
                @endphp

                <div class="hero-eyebrow inline-flex items-center gap-2 px-5 py-2 rounded-full 
                            bg-white/20 backdrop-blur-sm border border-white/30 shadow-sm text-sm text-white">
                    @if($profil && $profil->logo_url)
                        <img src="{{ $profil->logo_url }}" alt="Logo" class="w-6 h-6 rounded-full object-cover">
                    @else
                        <span class="relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-70"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-400"></span>
                        </span>
                    @endif
                    Selamat Datang di {{ $profil->nama ?? 'Masjid' }}
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-white leading-tight">
                    Masjid yang Hidup,
                    <span class="block bg-gradient-to-r from-emerald-300 via-teal-300 to-cyan-300 bg-clip-text text-transparent mt-2">
                        Pusat Ibadah & Ukhuwah
                    </span>
                </h1>

                <p class="text-base sm:text-lg text-white/90 max-w-2xl mx-auto lg:mx-0 leading-relaxed">
                    {{ e($profil->tagline ?? 'Menghidupkan shalat berjamaah, mempererat silaturahmi, dan menebar kebaikan bagi umat.') }}
                </p>

                <div class="flex flex-wrap justify-center lg:justify-start gap-4 mt-8">
                    <a href="#donasi" class="hero-primary-cta inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 via-teal-500 to-cyan-500 hover:brightness-110 text-white shadow-lg shadow-emerald-500/30 px-6 py-2.5 text-sm font-semibold rounded-full transition-all transform hover:scale-105">
                        🤲 Tunaikan Infak
                    </a>
                    <a href="#acara" class="hero-secondary-cta inline-flex items-center gap-2 bg-white/20 backdrop-blur-sm border border-white/40 text-white hover:bg-white/30 hover:border-white/60 px-6 py-2.5 text-sm font-semibold rounded-full transition-all">
                        📋 Lihat Agenda
                    </a>
                </div>
            </div>

            <!-- JADWAL SHOLAT - CARD WARNA CERAH -->
            <div class="w-full max-w-3xl mx-auto lg:mx-0 lg:max-w-none">
                <!-- Panel utama dengan gradasi gelap -->
                <div class="prayer-panel rounded-3xl shadow-2xl shadow-black/40 border border-white/20 overflow-hidden"
                     style="background: linear-gradient(145deg, rgba(15,23,42,0.85), rgba(30,41,59,0.75)); backdrop-filter: blur(20px);">
                    <div class="p-6 lg:p-8">
                        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-3">
                            <h2 class="text-xl lg:text-2xl font-bold text-white drop-shadow-lg">Jadwal Sholat Hari Ini</h2>
                            <span class="text-sm font-medium bg-white/20 text-white px-4 py-1.5 rounded-full backdrop-blur-sm border border-white/20 whitespace-nowrap shadow-lg">
                                {{ now()->translatedFormat('l, d M Y') }}
                            </span>
                        </div>

                        @php
                            $warnaCard = [
                                'subuh'    => ['label' => 'Subuh',    'warna' => '#10b981', 'light' => '#34d399', 'icon' => '🌙'],
                                'dzuhur'   => ['label' => 'Dzuhur',   'warna' => '#14b8a6', 'light' => '#2dd4bf', 'icon' => '☀️'],
                                'ashar'    => ['label' => 'Ashar',    'warna' => '#06b6d4', 'light' => '#22d3ee', 'icon' => '🌅'],
                                'maghrib'  => ['label' => 'Maghrib',  'warna' => '#f97316', 'light' => '#fb923c', 'icon' => '🌇'],
                                'isya'     => ['label' => 'Isya',     'warna' => '#8b5cf6', 'light' => '#a78bfa', 'icon' => '🌃'],
                            ];
                        @endphp

                        <div class="prayer-grid grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                            @foreach($warnaCard as $key => $data)
                                <div class="prayer-time-card group relative rounded-2xl p-5 md:p-6 text-center 
                                            border-2 border-white/30 shadow-xl 
                                            hover:shadow-2xl hover:scale-[1.05] hover:border-white/60
                                            transition-all duration-300 transform 
                                            overflow-hidden min-h-[100px] md:min-h-[120px] flex flex-col items-center justify-center"
                                            style="background: linear-gradient(145deg, {{ $data['warna'] }}dd, {{ $data['warna'] }}99);">
                                    
                                    <!-- Glow effect on hover -->
                                    <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"
                                         style="background: linear-gradient(135deg, {{ $data['light'] }}55, transparent);">
                                    </div>
                                    
                                    <!-- Icon -->
                                    <div class="absolute top-2 right-2 text-white/40 group-hover:text-white/70 transition-colors text-lg">
                                        {{ $data['icon'] }}
                                    </div>
                                    
                                    <div class="relative z-10">
                                        <div class="text-xs md:text-sm font-bold text-white uppercase tracking-wider mb-2 md:mb-3 drop-shadow-lg">
                                            {{ $data['label'] }}
                                        </div>
                                        <div class="text-lg md:text-xl font-extrabold text-white drop-shadow-xl">
                                            {{ $jadwalSholat[$key] ?? '--:--' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <p class="text-center text-xs md:text-sm text-white/60 mt-6 md:mt-8 italic drop-shadow-lg">
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