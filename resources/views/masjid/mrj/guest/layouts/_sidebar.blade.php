<aside x-data="{ openKeuangan: {{ request()->routeIs('admin.keuangan*') ? 'true' : 'false' }}, sidebarOpen: (window.innerWidth >= 1024) }"
       :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-64 lg:w-62 transform transition-transform duration-300"
       aria-label="Sidebar">

    <div class="h-full overflow-y-auto max-h-screen sidebar-wrap rounded-r-2xl shadow-2xl">

        <!-- HEADER -->
        <div class="flex items-center justify-between px-6 py-5 border-b" style="background: linear-gradient(180deg,#07332e,#0f4d45);">
            <div class="flex items-center gap-3">
                <img src="{{ asset('vendor/material-ui/img/logo-ct.png') }}" alt="logo"
                     class="h-10 w-10 rounded-full object-cover ring-1 ring-white/10 shadow-sm">
                <div>
                    <div class="text-sm font-semibold text-white">
                        @auth Administrator @else Users @endauth
                    </div>
                    <div class="text-xs text-emerald-200">Sistem Informasi Masjid</div>
                </div>
            </div>

            <button @click="sidebarOpen = false" class="lg:hidden p-2 rounded-md text-emerald-100 hover:bg-white/5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- PRAYER TIMES (SUBTLE) -->
        <div class="px-4 py-3 border-b bg-[rgba(255,255,255,0.02)]">
            <div class="flex items-center justify-between text-xs text-emerald-100/80 mb-2">
                <span>Waktu Sholat</span>
                <a href="#" class="text-emerald-200 text-xs">refresh</a>
            </div>
            <div class="grid grid-cols-2 gap-2 text-[12px]">
                <div class="p-2 rounded-md text-center bg-[rgba(255,255,255,0.02)]">Subuh<br><span class="font-semibold">04:25</span></div>
                <div class="p-2 rounded-md text-center bg-[rgba(255,255,255,0.02)]">Dzuhur<br><span class="font-semibold">12:10</span></div>
                <div class="p-2 rounded-md text-center bg-[rgba(255,255,255,0.02)]">Ashar<br><span class="font-semibold">15:20</span></div>
                <div class="p-2 rounded-md text-center bg-[rgba(255,255,255,0.02)]">Maghrib<br><span class="font-semibold">17:45</span></div>
            </div>
        </div>

        <!-- NAV ITEMS -->
        <nav class="px-3 py-4">
            <ul class="space-y-2">

                @php
                    $menuItems = [
                        ['route' => 'admin.dashboard', 'icon' => 'home', 'label' => 'Dashboard'],
                        ['route' => 'admin.profil', 'icon' => 'building', 'label' => 'Profil'],
                        ['route' => 'admin.role', 'icon' => 'shield', 'label' => 'Role & Permission'],
                        ['route' => 'admin.user', 'icon' => 'users', 'label' => 'Kelola User'],
                        ['route' => 'admin.kategori.index', 'icon' => 'tag', 'label' => 'Kategori'],
                        ['route' => 'admin.berita.index', 'icon' => 'newspaper', 'label' => 'Berita'],
                        ['route' => 'admin.acara.index', 'icon' => 'calendar', 'label' => 'Acara'],
                        ['route' => 'admin.galeri.index', 'icon' => 'photo', 'label' => 'Galeri'],
                        ['route' => 'admin.pengumuman.index', 'icon' => 'megaphone', 'label' => 'Pengumuman'],
                    ];
                @endphp

                @foreach($menuItems as $m)
                    @php $isActive = request()->routeIs($m['route'].'*'); @endphp
                    <li>
                        <a href="{{ route($m['route']) }}"
                           @class([
                                'flex items-center gap-4 px-5 py-3 rounded-xl transition-all duration-200',
                                'bg-amber-400 text-emerald-900 font-semibold shadow-md ring-1 ring-amber-200' => $isActive,
                                'text-white hover:bg-white/6 hover:translate-x-1 hover:shadow' => !$isActive
                           ])>
                            {{-- Icon mapping --}}
                            @if($m['icon'] === 'home')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 12l9-9 9 9M4 10v10a1 1 0 001 1h4m10-11v10a1 1 0 01-1 1h-4" />
                                </svg>
                            @elseif($m['icon'] === 'building')
                                <svg class="w-5 h-5 text-current" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                  <path d="M3 21h18V8H3v13Zm5-9h2v2H8v-2Zm0 4h2v2H8v-2Zm4-4h2v2h-2v-2Zm0 4h2v2h-2v-2Zm4-4h2v2h-2v-2Zm0 4h2v2h-2v-2Z"/>
                                </svg>
                            @elseif($m['icon'] === 'shield')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 22s8-4 8-10V6l-8-4-8 4v6c0 6 8 10 8 10z" />
                                </svg>
                            @elseif($m['icon'] === 'users')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        d="M17 20h5v-2a4 4 0 00-5-4m-6 6H3v-2a4 4 0 014-4m6 6v-2a4 4 0 00-4-4m4-4a4 4 0 11-8 0 4 4 0 018 0z"/>
                                </svg>
                            @elseif($m['icon'] === 'tag')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        d="M7 7h.01M3 11l9 9 9-9-9-9-9 9z"/>
                                </svg>
                            @elseif($m['icon'] === 'newspaper')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                        d="M19 20H5a2 2 0 01-2-2V6h16v12a2 2 0 002 2zM7 8h6m-6 4h6m-6 4h6"/>
                                </svg>
                            @elseif($m['icon'] === 'calendar')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7H3v12a2 2 0 002 2z"/>
                                </svg>
                            @elseif($m['icon'] === 'photo')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 5h16v14H4V5zm4 5l3 3 2-2 3 3"/>
                                </svg>
                            @elseif($m['icon'] === 'megaphone')
                                <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11V5l7-2v12l-7-2v-6M5 9v6a4 4 0 004 4h1v-2H9a2 2 0 01-2-2V9H5z"/>
                                </svg>
                            @endif

                            <span class="text-sm">{{ $m['label'] }}</span>
                        </a>
                    </li>
                @endforeach

                {{-- KEUANGAN COLLAPSIBLE --}}
                <li class="mt-2">
                    <button @click="openKeuangan = !openKeuangan"
                            :class="openKeuangan ? 'bg-amber-400 text-emerald-900 font-semibold shadow-md ring-1 ring-amber-200' : 'text-white hover:bg-white/6'"
                            class="w-full flex items-center gap-4 px-5 py-3 rounded-xl transition">
                        {{-- wallet icon --}}
                        <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                d="M4 7h16v12H4V7zm0-2h16V3H4v2zm12 7h2v2h-2v-2z"/>
                        </svg>

                        <span class="flex-1 text-sm text-left">Manajemen Keuangan</span>

                        {{-- chevrons: up / down --}}
                        <svg x-show="openKeuangan" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7" />
                        </svg>
                        <svg x-show="!openKeuangan" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    <ul x-show="openKeuangan" x-collapse class="mt-2 space-y-1 pl-4">
                        @php
                            $sub = [
                                ['route'=>'admin.keuangan.saldo-awal','label'=>'Saldo Awal Periode','icon'=>'scale'],
                                ['route'=>'admin.keuangan.kotak-infak','label'=>'Hitung Kotak Infak','icon'=>'donate'],
                                ['route'=>'admin.keuangan.akun.index','label'=>'Daftar Akun (CoA)','icon'=>'book'],
                                ['route'=>'admin.keuangan.petty-cash','label'=>'Petty Cash','icon'=>'banknotes'],
                                ['route'=>'admin.keuangan.pengeluaran','label'=>'Pengeluaran','icon'=>'arrow-up'],
                                ['route'=>'admin.keuangan.penerimaan','label'=>'Penerimaan','icon'=>'arrow-down'],
                                ['route'=>'admin.keuangan.alokasi-dana','label'=>'Alokasi Dana','icon'=>'hand'],
                                ['route'=>'admin.keuangan.zakat.index','label'=>'Zakat & Fidyah','icon'=>'zakat'],
                                ['route'=>'admin.keuangan.dana-terikat.index','label'=>'Dana Terikat & Program Rutin','icon'=>'inbox'],
                                ['route'=>'admin.keuangan.jurnal.index','label'=>'Jurnal Umum','icon'=>'document'],
                            ];
                        @endphp

                        @foreach($sub as $s)
                            @php $a = request()->routeIs($s['route'].'*'); @endphp
                            <li>
                                <a href="{{ route($s['route']) }}"
                                   @class([
                                       'flex items-center gap-3 px-4 py-2.5 rounded-lg transition',
                                       'bg-amber-300/90 text-emerald-900 font-semibold shadow' => $a,
                                       'text-white hover:bg-white/5' => !$a
                                   ])>
                                    {{-- sub-icons (w-4 h-4) --}}
                                    @if($s['icon'] === 'scale')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 3v18m9-9H3"/>
                                        </svg>
                                    @elseif($s['icon'] === 'donate')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 3v12m6 6H6m0 0V9l6-3 6 3v12z"/>
                                        </svg>
                                    @elseif($s['icon'] === 'book')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 6v12m-6-6h12"/>
                                        </svg>
                                    @elseif($s['icon'] === 'banknotes')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 10h18v7H3v-7zm3-4h12v4H6V6z"/>
                                        </svg>
                                    @elseif($s['icon'] === 'arrow-up')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 19V5m0 0l-6 6m6-6l6 6"/>
                                        </svg>
                                    @elseif($s['icon'] === 'arrow-down')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 5v14m0 0l-6-6m6 6l6-6"/>
                                        </svg>
                                    @elseif($s['icon'] === 'hand')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 21s6-3 6-9V7l-6-3-6 3v5c0 6 6 9 6 9z"/>
                                        </svg>
                                    @elseif($s['icon'] === 'zakat')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M12 7a4 4 0 110 8 4 4 0 010-8z"/>
                                        </svg>
                                    @elseif($s['icon'] === 'inbox')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M3 7h18v12H3V7zm3 4h12"/>
                                        </svg>
                                    @elseif($s['icon'] === 'document')
                                        <svg class="w-4 h-4 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                          <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                                d="M5 5h14v14H5V5zm4 4h6m-6 4h6"/>
                                        </svg>
                                    @endif

                                    <span class="text-xs">{{ $s['label'] }}</span>

                                    @if($s['route'] === 'admin.keuangan.saldo-awal')
                                        @if(\App\Models\SaldoAwalPeriode::where('status','locked')->exists())
                                            <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-emerald-800/40">Locked</span>
                                        @else
                                            <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs bg-yellow-400/20 text-yellow-100">Belum Lock</span>
                                        @endif
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </li>

            </ul>
        </nav>

        <!-- Logout (bottom) -->
        <div class="px-6 py-5 border-t mt-4">
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-3 px-5 py-3
                               bg-red-600 hover:bg-red-700 rounded-xl transition text-white font-semibold shadow-lg">
                    <svg class="w-5 h-5 text-current" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                      <path stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 8h8v-2"/>
                    </svg>
                    <span class="text-sm">Keluar</span>
                </button>
            </form>
        </div>
    </div>

    {{-- small footer --}}
    <div class="px-4 py-3 text-xs text-emerald-200/70">
        © {{ date('Y') }} E-Masjid — Sistem Informasi Masjid
    </div>

    <!-- Styles -->
    <style>
        .sidebar-wrap {
            background: linear-gradient(180deg,#0b3b37,#0f4d45);
            color: #fff;
        }
        .sidebar-wrap .info-pill { background: rgba(255,255,255,0.03); border-radius: 8px; padding: 6px; }
        .sidebar-wrap .custom-scroll::-webkit-scrollbar { width: 6px; }
        .sidebar-wrap .custom-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 999px; }
        @media (max-width: 1024px) {
            aside[aria-label="Sidebar"] { width: 56px; }
            .sidebar-wrap nav ul li a span { display: none; }
        }
    </style>
</aside>
