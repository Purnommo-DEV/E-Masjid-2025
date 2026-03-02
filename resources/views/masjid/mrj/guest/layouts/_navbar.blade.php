<nav class="backdrop-blur bg-slate-950/80 border-b border-white/5 sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between gap-4">
        {{-- LOGO --}}
        <a href="{{ route('home') }}"
           class="flex items-center gap-3 group transition-all duration-200 hover:scale-105">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-transform group-hover:shadow-emerald-500/50">
                <span class="text-sm font-bold text-white">
                    {!! profil('singkatan') !!}
                </span>
            </div>
            <div class="hidden sm:block">
                <div class="text-sm font-semibold tracking-wide group-hover:text-emerald-200 transition-colors duration-200">
                    {!! profil('nama') ?? 'Masjid' !!}
                </div>
                <div class="text-[11px] text-emerald-200/80 group-hover:text-emerald-300/90 transition-colors duration-200">
                    Informasi & Kegiatan Masjid
                </div>
            </div>
        </a>

        {{-- MENU DESKTOP --}}
        <ul class="hidden md:flex items-center gap-5 text-xs font-medium h-10">
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}"
                   class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 active:bg-emerald-900/30 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple">
                    Beranda
                </a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#jadwal"
                   class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 active:bg-emerald-900/30 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple">
                    Jadwal Sholat
                </a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#acara"
                   class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 active:bg-emerald-900/30 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple">
                    Agenda
                </a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#berita"
                   class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 active:bg-emerald-900/30 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple">
                    Berita
                </a>
            </li>
            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#donasi"
                   class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 active:bg-emerald-900/30 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple">
                    Infaq & Sedekah
                </a>
            </li>

            @if($isRamadhan)
                <li class="relative group h-full flex items-center">
                    <!-- tombol utama – styling disesuaikan agar sama persis dengan menu lain -->
                    <a href="#"
                       class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 
                              active:bg-emerald-900/30 active:scale-95 transition-all duration-150 
                              focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple 
                              flex items-center gap-1 font-semibold text-emerald-300">
                        <span class="animate-pulse">🌙</span> Ramadhan 1447 H
                        <svg class="w-4 h-4 ml-1 transition-transform group-hover:rotate-180 duration-300"
                             fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>

                    <!-- Hover bridge: transparan, nutup gap tanpa menggeser layout -->
                    <div class="absolute inset-x-0 top-full h-10 bg-transparent pointer-events-auto"></div>
                    <!-- h-10 = 40px bridge ke bawah, cukup untuk kursor pindah aman -->

                    <!-- dropdown – mt-2 untuk gap visual, tapi bridge sudah nutup celah hover -->
                    <ul class="absolute left-0 top-full mt-2 hidden group-hover:block
                               bg-emerald-900/95 backdrop-blur-md rounded-xl shadow-2xl
                               border border-emerald-700/50 overflow-hidden z-50 min-w-[240px]
                               animate-dropdown">
                        <li>
                            <a href="{{ route('guest.laporan-harian') }}"
                               class="dropdown-item block px-6 py-4 hover:bg-emerald-800/70 
                                      active:bg-emerald-700/80 transition-all duration-150 
                                      flex items-center gap-3 text-sm font-medium ripple">
                                <span class="text-xl">📊</span> Laporan Harian
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('program-ramadhan.index') }}"
                               class="dropdown-item block px-6 py-4 hover:bg-emerald-800/70 
                                      active:bg-emerald-700/80 transition-all duration-150 
                                      flex items-center gap-3 text-sm font-medium ripple">
                                <span class="text-xl">🌟</span> Program Kegiatan
                            </a>
                        </li>
                    </ul>
                </li>
            @endif

            <li class="h-full flex items-center">
                <a href="{{ route('home') }}#layanan_jamaah"
                   class="px-3 py-2 rounded-md hover:text-emerald-300 active:text-emerald-400 active:bg-emerald-900/30 active:scale-95 transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-emerald-500/50 ripple">
                    Layanan Jamaah
                </a>
            </li>
        </ul>

        {{-- TOMBOL KANAN / MENU MOBILE --}}
        <div class="flex items-center gap-2">
            <details class="md:hidden relative">
                <summary class="list-none btn btn-ghost btn-square btn-xs flex items-center justify-center active:scale-95 active:bg-emerald-900/30 transition-all duration-150 focus:outline-none ripple">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </summary>
                <div class="absolute right-0 mt-2 w-64 rounded-2xl bg-slate-950/95 border border-white/10 shadow-xl py-3 px-2">
                    <ul class="menu menu-compact text-sm">
                        <li class="px-4 pb-2 text-xs tracking-wider uppercase text-slate-400 font-medium">
                            Navigasi
                        </li>
                        <li><a href="{{ route('home') }}" class="ripple hover:bg-emerald-900/40 active:bg-emerald-800/60 active:text-emerald-200 transition-all duration-150 py-3 px-5 rounded-lg">Beranda</a></li>
                        <li><a href="{{ route('home') }}#jadwal" class="ripple hover:bg-emerald-900/40 active:bg-emerald-800/60 active:text-emerald-200 transition-all duration-150 py-3 px-5 rounded-lg">Jadwal Sholat</a></li>
                        <li><a href="{{ route('home') }}#acara" class="ripple hover:bg-emerald-900/40 active:bg-emerald-800/60 active:text-emerald-200 transition-all duration-150 py-3 px-5 rounded-lg">Agenda</a></li>
                        <li><a href="{{ route('home') }}#berita" class="ripple hover:bg-emerald-900/40 active:bg-emerald-800/60 active:text-emerald-200 transition-all duration-150 py-3 px-5 rounded-lg">Berita</a></li>
                        <li><a href="{{ route('home') }}#donasi" class="ripple hover:bg-emerald-900/40 active:bg-emerald-800/60 active:text-emerald-200 transition-all duration-150 py-3 px-5 rounded-lg">Infaq & Sedekah</a></li>

                        @if($isRamadhan)
                            <div class="mt-4 mx-2 p-4 bg-emerald-900/30 backdrop-blur-sm rounded-xl border border-emerald-700/50">
                                <p class="text-xs text-emerald-300/80 mb-3 font-medium uppercase tracking-wider text-center">
                                    Ramadhan 1447 H
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <a href="{{ route('guest.laporan-harian') }}"
                                       class="ripple flex flex-col items-center justify-center gap-2 py-4 px-3 bg-emerald-800/40 hover:bg-emerald-700/60 active:bg-emerald-600/70 transition-all duration-150 rounded-lg text-center">
                                        <span class="text-2xl animate-pulse">📊</span>
                                        <span class="text-sm font-semibold text-emerald-200">Laporan Harian</span>
                                    </a>
                                    <a href="{{ route('program-ramadhan.index') }}"
                                       class="ripple flex flex-col items-center justify-center gap-2 py-4 px-3 bg-amber-800/40 hover:bg-amber-700/60 active:bg-amber-600/70 transition-all duration-150 rounded-lg text-center">
                                        <span class="text-2xl animate-pulse">🌟</span>
                                        <span class="text-sm font-semibold text-amber-200">Program Unggulan</span>
                                    </a>
                                </div>
                            </div>
                        @endif

                        <li><hr class="my-3 border-slate-700/70 mx-2"></li>
                        <li><a href="{{ route('home') }}#kontak" class="ripple hover:bg-emerald-900/40 active:bg-emerald-800/60 active:text-emerald-200 transition-all duration-150 py-3 px-5 rounded-lg">Kontak</a></li>
                    </ul>
                </div>
            </details>
        </div>
    </div>
</nav>