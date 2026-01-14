<nav class="backdrop-blur bg-slate-950/80 border-b border-white/5 sticky top-0 z-40">
    <div class="container mx-auto px-4 py-3 flex items-center justify-between gap-4">

        {{-- LOGO + NAMA MASJID --}}
        <a href="#"
           class="flex items-center gap-3 group">
            <div class="w-10 h-10 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <span class="text-sm font-bold">
                    {{ Str::limit($profil->singkatan ?? 'MSJ', 3, '') }}
                </span>
            </div>
            <div class="hidden sm:block">
                <div class="text-sm font-semibold tracking-wide group-hover:text-emerald-200 transition">
                    {{ $profil->nama ?? 'Masjid Al-Ikhlas' }}
                </div>
                <div class="text-[11px] text-emerald-200/80">
                    Sistem Informasi Masjid
                </div>
            </div>
        </a>

        {{-- MENU DESKTOP --}}
        <ul class="hidden md:flex items-center gap-5 text-xs font-medium">
            <li><a href="#jadwal" class="hover:text-emerald-300 transition">Jadwal Sholat</a></li>
            <li><a href="#acara" class="hover:text-emerald-300 transition">Agenda</a></li>
            <li><a href="#berita" class="hover:text-emerald-300 transition">Berita</a></li>
            <li><a href="#donasi" class="hover:text-emerald-300 transition">Donasi</a></li>
            <li><a href="#kontak" class="hover:text-emerald-300 transition">Kontak</a></li>
        </ul>

        {{-- TOMBOL KANAN --}}
        <div class="flex items-center gap-2">
            {{-- tombol login (desktop) --}}
            <a href="#"
               class="hidden sm:inline-flex btn btn-xs sm:btn-sm btn-outline border-emerald-400/60 text-emerald-200 hover:bg-emerald-500/10">
                Admin
            </a>

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
                        <li><a href="#jadwal">Jadwal Sholat</a></li>
                        <li><a href="#acara">Agenda</a></li>
                        <li><a href="#berita">Berita</a></li>
                        <li><a href="#donasi">Donasi</a></li>
                        <li><a href="#kontak">Kontak</a></li>
                        <li><hr class="my-2 border-slate-700/70"></li>
                        <li class="px-3">
                            <a href="#"
                               class="btn btn-xs btn-outline w-full border-emerald-500/70 text-emerald-200">
                                Login Admin
                            </a>
                        </li>
                    </ul>
                </div>
            </details>
        </div>
    </div>
</nav>
