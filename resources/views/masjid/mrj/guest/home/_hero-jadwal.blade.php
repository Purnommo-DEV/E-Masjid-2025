        {{-- HERO --}}
        <section id="jadwal" class="home-hero relative pt-16 pb-20 lg:pt-24 lg:pb-32 overflow-hidden">
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-teal-200 rounded-full blur-3xl opacity-30 animate-pulse-slow"></div>
                <div class="absolute top-40 right-0 w-[600px] h-[600px] bg-cyan-200 rounded-full blur-3xl opacity-25 animate-float-slow"></div>
            </div>

            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="grid lg:grid-cols-2 gap-12 xl:gap-16 items-center">
                    <!-- Hero Text -->
                    <div class="space-y-8 text-center lg:text-left">
                        @php
                            $profil = $profil ?? \App\Models\ProfilMasjid::first();
                        @endphp

                        <div class="hero-eyebrow inline-flex items-center gap-2 px-5 py-2 rounded-full bg-white/60 backdrop-blur-sm border border-emerald-200/50 shadow-sm text-sm text-emerald-800">
                            @if($profil && $profil->logo_url)
                                <img src="{{ $profil->logo_url }}" alt="Logo" class="w-6 h-6 rounded-full object-cover">
                            @else
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-70"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-600"></span>
                                </span>
                            @endif
                            Selamat Datang di {{ $profil->nama ?? 'Masjid' }}
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
                            <a href="#donasi" class="hero-primary-cta btn btn-lg bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 hover:brightness-110 text-white shadow-xl shadow-teal-500/30 px-10 py-4 text-base font-bold rounded-full transition-all">
                                🤲 Tunaikan Infak
                            </a>
                            <a href="#acara" class="hero-secondary-cta btn btn-lg btn-outline border-2 border-emerald-500 text-emerald-700 hover:bg-emerald-50 hover:border-emerald-600">
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
        </section>

