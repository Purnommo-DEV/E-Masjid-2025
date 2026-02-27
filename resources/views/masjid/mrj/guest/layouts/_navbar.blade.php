<nav class="backdrop-blur bg-slate-950/80 border-b border-white/5 sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between gap-4">

        {{-- LOGO --}}
        <a href="{{ route('home') }}"
           class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <span class="text-sm font-bold text-white">
                    {!! profil('singkatan') !!}
                </span>
            </div>
            <div class="hidden sm:block">
                <div class="text-sm font-semibold tracking-wide group-hover:text-emerald-200 transition">
                    {!! profil('nama') ?? 'Masjid' !!}
                </div>
                <div class="text-[11px] text-emerald-200/80">
                    Informasi & Kegiatan Masjid
                </div>
            </div>
        </a>

        {{-- MENU DESKTOP --}}
        <ul class="hidden md:flex items-center gap-5 text-xs font-medium h-10"> <!-- tambah h-10 untuk tinggi tetap -->
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}" class="hover:text-emerald-300 transition">Beranda</a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#jadwal" class="hover:text-emerald-300 transition">Jadwal Sholat</a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#acara" class="hover:text-emerald-300 transition">Agenda</a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#berita" class="hover:text-emerald-300 transition">Berita</a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#donasi" class="hover:text-emerald-300 transition">Infaq & Sedekah</a>
            </li>

            @if($isRamadhan)
                <li class="h-full flex items-center relative group">
                    <a href="#"
                       class="hover:text-emerald-300 transition flex items-center gap-1 font-semibold text-emerald-300">
                        <span class="animate-pulse">🌙</span> Ramadhan 1447 H
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </a>

                    <ul class="absolute left-0 top-full hidden group-hover:block bg-emerald-900/95 backdrop-blur-md rounded-xl shadow-2xl border border-emerald-700/50 overflow-hidden z-50 min-w-[240px] translate-y-0 pt-2">
                        <div class="absolute inset-x-0 -top-8 h-8 pointer-events-auto bg-transparent"></div>

                        <li>
                            <a href="{{ route('guest.laporan-harian') }}"
                               class="block px-6 py-4 hover:bg-emerald-800/70 transition flex items-center gap-3 text-sm font-medium">
                                <span class="text-xl">📊</span> Laporan Harian
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('program-ramadhan.index') }}"
                               class="block px-6 py-4 hover:bg-emerald-800/70 transition flex items-center gap-3 text-sm font-medium">
                                <span class="text-xl">🌟</span> Program Unggulan Ramadhan
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#layanan_jamaah" class="hover:text-emerald-300 transition">Layanan Jamaah</a>
            </li>
        </ul>

        {{-- TOMBOL KANAN --}}
        <div class="flex items-center gap-2">
            {{-- MENU MOBILE (DETAILS DROPDOWN) --}}
            <details class="md:hidden relative">
                <summary class="list-none btn btn-ghost btn-square btn-xs flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="h-5 w-5" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round"
                              stroke-linejoin="round"
                              stroke-width="2"
                              d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </summary>
                <div class="absolute right-0 mt-2 w-52 rounded-2xl bg-slate-950/95 border border-white/10 shadow-xl py-2">
                    <ul class="menu menu-compact text-xs">
                        <li class="px-3 pb-1 text-[10px] tracking-[0.18em] uppercase text-slate-400">
                            Navigasi
                        </li>
                        <li><a href="{{ route('home') }}">Beranda</a></li>
                        <li><a href="{{ route('home') }}#jadwal">Jadwal Sholat</a></li>
                        <li><a href="{{ route('home') }}#acara">Agenda</a></li>
                        <li><a href="{{ route('home') }}#berita">Berita</a></li>
                        <li><a href="{{ route('home') }}#donasi">Infaq & Sedekah</a></li>

                        {{-- KHUSUS RAMADHAN di mobile --}}
                        @if($isRamadhan)
                            <div class="mt-4 px-4 py-3 bg-emerald-900/30 backdrop-blur-sm rounded-xl border border-emerald-700/50">
                                <p class="text-xs text-emerald-300/80 mb-2 font-medium uppercase tracking-wider text-center">
                                    Ramadhan 1447 H
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="{{ route('guest.laporan-harian') }}"
                                       class="flex flex-col items-center justify-center gap-2 py-3 px-4 bg-emerald-800/40 hover:bg-emerald-700/60 rounded-lg transition-all text-center">
                                        <span class="text-2xl animate-pulse">📊</span>
                                        <span class="text-sm font-semibold text-emerald-200">Laporan Harian</span>
                                    </a>

                                    <a href="{{ route('program-ramadhan.index') }}"
                                       class="flex flex-col items-center justify-center gap-2 py-3 px-4 bg-amber-800/40 hover:bg-amber-700/60 rounded-lg transition-all text-center">
                                        <span class="text-2xl animate-pulse">🌟</span>
                                        <span class="text-sm font-semibold text-amber-200">Program Unggulan</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <li><a href="{{ route('home') }}#kontak">Kontak</a></li>
                        <li><hr class="my-2 border-slate-700/70"></li>
                    </ul>
                </div>
            </details>
        </div>
    </div>
</nav>