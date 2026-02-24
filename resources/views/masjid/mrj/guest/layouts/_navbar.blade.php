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
                    Sistem Informasi Masjid
                </div>
            </div>
        </a>

        {{-- MENU DESKTOP --}}
        <ul class="hidden md:flex items-center gap-5 text-xs font-medium">
            <li><a href="{{ route('home') }}" class="hover:text-emerald-300 transition">Beranda</a></li>
            <li><a href="{{ route('home') }}#jadwal" class="hover:text-emerald-300 transition">Jadwal Sholat</a></li>
            <li><a href="{{ route('home') }}#acara" class="hover:text-emerald-300 transition">Agenda</a></li>
            <li><a href="{{ route('home') }}#berita" class="hover:text-emerald-300 transition">Berita</a></li>
            <li><a href="{{ route('home') }}#donasi" class="hover:text-emerald-300 transition">Donasi</a></li>

            @if($isRamadhan)
            <li>
                <a href="{{ route('guest.laporan-harian') }}"
                   class="hover:text-emerald-300 transition flex items-center gap-1 font-semibold text-emerald-300">
                    <span class="animate-pulse">🌙</span> Kabar Ramadhan
                </a>
            </li>
            @endif

            <li><a href="{{ route('home') }}#kontak" class="hover:text-emerald-300 transition">Kontak</a></li>
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
                        <li><a href="{{ route('home') }}#donasi">Donasi</a></li>

                        {{-- KHUSUS RAMADHAN di mobile --}}
                        @if($isRamadhan)
                            <li>
                                <a href="{{ route('guest.laporan-harian') }}"
                                   class="flex items-center gap-2 text-emerald-300 font-semibold">
                                    <span class="animate-pulse">🌙</span> Kabar Ramadhan
                                </a>
                            </li>
                        @endif

                        <li><a href="{{ route('home') }}#kontak">Kontak</a></li>
                        <li><hr class="my-2 border-slate-700/70"></li>
                    </ul>
                </div>
            </details>
        </div>
    </div>
</nav>