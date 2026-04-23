<nav class="sticky top-0 z-40 transition-all duration-500" id="mainNav">
    <div class="backdrop-blur-md bg-slate-950/80 border-b border-white/5 transition-all duration-500" id="navContainer">
        <div class="container mx-auto px-4 py-2 lg:py-3 flex items-center justify-between gap-4">
            
            {{-- LOGO + NAMA MASJID --}}
            <a href="{{ route('home') }}"
               class="flex items-center gap-2 sm:gap-3 group transition-all duration-300 hover:scale-105 flex-1 min-w-0">
                
                <!-- Logo -->
                <div class="relative">
                    <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-2xl blur opacity-30 group-hover:opacity-70 transition duration-500"></div>
                    <div class="relative w-9 h-9 sm:w-10 sm:h-10 rounded-xl sm:rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-700 flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-all group-hover:shadow-emerald-500/50 flex-shrink-0">
                        <span class="text-xs sm:text-sm font-bold text-white">
                            {!! profil('singkatan') !!}
                        </span>
                    </div>
                </div>

                <!-- Nama Masjid -->
                <div class="flex flex-col justify-center min-w-0">
                    <div class="text-sm sm:text-base font-semibold tracking-wide text-white group-hover:text-emerald-200 transition-colors duration-200 truncate leading-tight">
                        {!! profil('nama') ?? 'Masjid' !!}
                    </div>
                    <div class="text-[10px] sm:text-xs text-emerald-300/70 group-hover:text-emerald-200 transition-colors duration-200 whitespace-nowrap leading-tight">
                        Pusat Informasi & Kegiatan
                    </div>
                </div>
            </a>

            {{-- MENU DESKTOP --}}
            <ul class="hidden md:flex items-center gap-1 text-sm font-medium">
                <li><a href="{{ route('home') }}" class="relative px-4 py-2 rounded-full text-emerald-100/90 hover:text-white hover:bg-white/10 transition-all duration-300">Beranda</a></li>
                <li><a href="{{ route('home') }}#jadwal" class="relative px-4 py-2 rounded-full text-emerald-100/90 hover:text-white hover:bg-white/10 transition-all duration-300">Jadwal Sholat</a></li>
                <li><a href="{{ route('home') }}#acara" class="relative px-4 py-2 rounded-full text-emerald-100/90 hover:text-white hover:bg-white/10 transition-all duration-300">Agenda</a></li>
                <li><a href="{{ route('home') }}#berita" class="relative px-4 py-2 rounded-full text-emerald-100/90 hover:text-white hover:bg-white/10 transition-all duration-300">Berita</a></li>
                <li><a href="{{ route('home') }}#donasi" class="relative px-5 py-2 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white shadow-md hover:shadow-emerald-500/30 transition-all duration-300 hover:scale-105">Infaq & Sedekah</a></li>
                @if($isRamadhan)
                    <li class="relative group">
                        <a href="#" class="relative flex items-center gap-1 px-4 py-2 rounded-full bg-amber-600/20 border border-amber-500/50 text-amber-200 hover:bg-amber-600/30 transition-all duration-300">
                            <span class="animate-pulse">🌙</span> Ramadhan 1447 H
                            <svg class="w-4 h-4 transition-transform group-hover:rotate-180 duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </a>
                        <ul class="absolute left-0 top-full mt-2 hidden group-hover:block bg-slate-900/95 backdrop-blur-xl rounded-2xl shadow-2xl border border-emerald-500/30 overflow-hidden z-50 min-w-[260px]">
                            <li><a href="{{ route('guest.laporan-harian') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-emerald-800/40 transition-all duration-200"><span class="text-xl">📊</span> Laporan Harian</a></li>
                            <li class="border-t border-emerald-800/30"><a href="{{ route('program-ramadhan.index') }}" class="flex items-center gap-3 px-5 py-3 hover:bg-emerald-800/40 transition-all duration-200"><span class="text-xl">🌟</span> Program Kegiatan</a></li>
                        </ul>
                    </li>
                @endif
                <li><a href="{{ route('home') }}#layanan_jamaah" class="relative px-4 py-2 rounded-full text-emerald-100/90 hover:text-white hover:bg-white/10 transition-all duration-300">Layanan Jamaah</a></li>
            </ul>

            {{-- HAMBURGER MENU MOBILE (Sederhana dengan JS) --}}
            <div class="md:hidden">
                <button id="mobileMenuBtn" class="w-10 h-10 rounded-full hover:bg-white/10 transition-all duration-300 focus:outline-none active:scale-95 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Mobile Menu Panel dengan Animasi & Tampilan Sama Rata --}}
    <div id="mobileMenuPanel" class="fixed hidden md:hidden backdrop-blur-md bg-slate-950/80 rounded-2xl shadow-2xl border border-emerald-500/30 overflow-hidden z-50 transition-all duration-300 transform scale-95 opacity-0" style="top: 70px; right: 16px; width: 280px;">
        <div class="px-4 py-3 border-b border-emerald-800/50 flex items-center justify-between">
            <p class="text-xs font-semibold text-emerald-400 tracking-wider">✦ MENU NAVIGASI ✦</p>
            <button id="closeMobileMenu" class="text-emerald-400 hover:text-emerald-300 text-lg leading-none transition-all duration-200 hover:rotate-90 hover:scale-110">&times;</button>
        </div>
        <ul class="py-2 max-h-[70vh] overflow-y-auto">
            <!-- Semua menu pakai class yang SAMA -->
            <li><a href="{{ route('home') }}" class="flex items-center gap-3 mx-2 my-1 px-4 py-3 rounded-xl text-white hover:bg-emerald-600/30 hover:translate-x-1 transition-all duration-200 mobile-menu-link">🏠 Beranda</a></li>
            <li><a href="{{ route('home') }}#jadwal" class="flex items-center gap-3 mx-2 my-1 px-4 py-3 rounded-xl text-white hover:bg-emerald-600/30 hover:translate-x-1 transition-all duration-200 mobile-menu-link">🕌 Jadwal Sholat</a></li>
            <li><a href="{{ route('home') }}#acara" class="flex items-center gap-3 mx-2 my-1 px-4 py-3 rounded-xl text-white hover:bg-emerald-600/30 hover:translate-x-1 transition-all duration-200 mobile-menu-link">📅 Agenda</a></li>
            <li><a href="{{ route('home') }}#berita" class="flex items-center gap-3 mx-2 my-1 px-4 py-3 rounded-xl text-white hover:bg-emerald-600/30 hover:translate-x-1 transition-all duration-200 mobile-menu-link">📰 Berita</a></li>
            
            <!-- Menu Infaq & Sedekah - SAMA RATA dengan yang lain (tidak beda) -->
            <li><a href="{{ route('home') }}#donasi" class="flex items-center gap-3 mx-2 my-1 px-4 py-3 rounded-xl text-white hover:bg-emerald-600/30 hover:translate-x-1 transition-all duration-200 mobile-menu-link">🤲 Infaq & Sedekah</a></li>
            
            @if($isRamadhan)
                <li class="mx-2 my-1">
                    <div class="bg-amber-500/10 rounded-xl p-3 border border-amber-500/30">
                        <p class="text-xs text-amber-400 font-semibold mb-2 text-center">🌙 Ramadhan 1447 H</p>
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('guest.laporan-harian') }}" class="flex items-center justify-center gap-2 py-2 px-2 bg-emerald-800/30 rounded-lg text-xs text-white hover:bg-emerald-700/50 hover:scale-105 transition-all duration-200 mobile-menu-link">📊 Laporan Harian</a>
                            <a href="{{ route('program-ramadhan.index') }}" class="flex items-center justify-center gap-2 py-2 px-2 bg-amber-800/30 rounded-lg text-xs text-white hover:bg-amber-700/50 hover:scale-105 transition-all duration-200 mobile-menu-link">🌟 Program</a>
                        </div>
                    </div>
                </li>
            @endif
            
            <li><hr class="my-2 border-emerald-800/30 mx-4"></li>
            <li><a href="{{ route('home') }}#layanan_jamaah" class="flex items-center gap-3 mx-2 my-1 px-4 py-3 rounded-xl text-white hover:bg-emerald-600/30 hover:translate-x-1 transition-all duration-200 mobile-menu-link">💬 Layanan Jamaah</a></li>
        </ul>
    </div>

    {{-- Scroll Progress Bar --}}
    <div class="h-0.5 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-500 w-0 transition-all duration-300" id="scrollProgress"></div>
</nav>

<script>
    // Mobile menu toggle
    // Mobile menu toggle dengan animasi
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuPanel = document.getElementById('mobileMenuPanel');
    const closeMobileMenu = document.getElementById('closeMobileMenu');

    function openMobileMenu() {
        mobileMenuPanel.classList.remove('hidden');
        // Trigger reflow untuk memulai animasi
        void mobileMenuPanel.offsetWidth;
        mobileMenuPanel.classList.remove('scale-95', 'opacity-0');
        mobileMenuPanel.classList.add('scale-100', 'opacity-100');
    }

    function closeMobileMenuFunc() {
        mobileMenuPanel.classList.remove('scale-100', 'opacity-100');
        mobileMenuPanel.classList.add('scale-95', 'opacity-0');
        // Tunggu animasi selesai sebelum hidden
        setTimeout(() => {
            if (!mobileMenuPanel.classList.contains('scale-100')) {
                mobileMenuPanel.classList.add('hidden');
            }
        }, 300);
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', openMobileMenu);
    }

    if (closeMobileMenu) {
        closeMobileMenu.addEventListener('click', closeMobileMenuFunc);
    }

    // Tutup menu saat klik link (dengan animasi)
    document.querySelectorAll('.mobile-menu-link').forEach(link => {
        link.addEventListener('click', (e) => {
            // Tutup menu dulu baru navigasi
            closeMobileMenuFunc();
            // Biarkan link bekerja (tidak prevent default)
        });
    });

    // Tutup menu saat klik di luar
    document.addEventListener('click', function(event) {
        if (mobileMenuPanel && !mobileMenuPanel.classList.contains('hidden') && 
            mobileMenuPanel.classList.contains('scale-100')) {
            const isClickInside = mobileMenuPanel.contains(event.target) || mobileMenuBtn.contains(event.target);
            if (!isClickInside) {
                closeMobileMenuFunc();
            }
        }
    });
    // Scroll progress bar
    window.addEventListener('scroll', () => {
        const winScroll = document.documentElement.scrollTop;
        const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
        const scrolled = (winScroll / height) * 100;
        const progressBar = document.getElementById('scrollProgress');
        if (progressBar) progressBar.style.width = scrolled + '%';
    });

    // Navbar scroll effect
    const navContainer = document.getElementById('navContainer');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navContainer.classList.add('mx-2', 'sm:mx-4', 'rounded-2xl', 'sm:rounded-3xl', 'shadow-2xl', 'shadow-emerald-900/30');
            navContainer.classList.remove('mx-0', 'rounded-none');
            navContainer.style.marginTop = '4px';
        } else {
            navContainer.classList.remove('mx-2', 'sm:mx-4', 'rounded-2xl', 'sm:rounded-3xl', 'shadow-2xl', 'shadow-emerald-900/30');
            navContainer.classList.add('mx-0', 'rounded-none');
            navContainer.style.marginTop = '0px';
        }
    });
</script>

<style>
    .mobile-menu-link {
        position: relative;
        overflow: hidden;
    }

    /* Efek ripple saat klik */
    .mobile-menu-link:active {
        transform: scale(0.98);
        background: rgba(16, 185, 129, 0.4) !important;
    }

    /* Efek shine saat hover */
    .mobile-menu-link::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
        transition: left 0.5s ease;
    }

    .mobile-menu-link:hover::before {
        left: 100%;
    }

    /* Ikon animasi hover */
    .mobile-menu-link:hover span:first-child {
        transform: scale(1.1);
        display: inline-block;
    }
    /* Animasi halus untuk navbar saat scroll */
    #navContainer {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Mobile menu animation */
    #mobileMenuPanel {
        animation: fadeInDown 0.2s ease-out;
    }

    @keyframes fadeInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Smooth scroll */
    html {
        scroll-behavior: smooth;
    }
</style>