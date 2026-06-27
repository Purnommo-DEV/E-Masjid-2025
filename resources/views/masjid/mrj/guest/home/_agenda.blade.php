        {{-- SECTION AGENDA --}}
        <section id="acara" class="py-12 sm:py-16 bg-gradient-to-br from-emerald-50 via-white to-teal-50/50 relative overflow-hidden">
            <!-- Subtle background pattern -->


            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-8 sm:mb-10 gap-4">
                    <div>
                        <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">AGENDA TERDEKAT</p>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 flex items-center gap-3">
                            Kegiatan Mendatang
                            <span class="hidden sm:block h-1.5 w-16 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full"></span>
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">Kegiatan & kajian yang akan segera dilaksanakan</p>
                    </div>
                    @if(count($acaras) > 0)
                        <a href="{{ route('acara.index') }}" 
                        class="text-sm sm:text-base text-emerald-700 hover:text-emerald-800 font-semibold inline-flex items-center gap-2 hover:underline transition">
                            Lihat Semua Agenda →
                        </a>
                    @endif
                </div>

                @if(count($acaras) > 0)
                    {{-- ADA AGENDA --}}
                    <div class="grid lg:grid-cols-[3fr_1fr] gap-6 xl:gap-8">
                        <!-- List Acara Utama -->
                        <div class="space-y-5 sm:space-y-6">
                            @foreach(array_slice($acaras, 0, 3) as $acara)
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
                                        class="inline-flex items-center px-6 py-2.5 border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-50 hover:text-emerald-800 font-medium rounded-full transition-all duration-300 text-sm shadow-sm hover:shadow">
                                            Detail Acara →
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Sidebar -->
                        <aside class="space-y-6 lg:space-y-8 h-fit">
                            <!-- Shalat Jum'at -->
                            <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-700 to-emerald-800 text-white shadow-2xl overflow-hidden ring-1 ring-emerald-500/30">
                                <div class="p-6 sm:p-7 lg:p-8">
                                    <div class="flex items-center justify-between mb-5">
                                        <div>
                                            <p class="text-xs uppercase tracking-widest text-emerald-200/90 font-medium">Shalat Jum’at</p>
                                            <h3 class="text-xl font-bold text-white mt-1">Pekan Ini</h3>
                                        </div>
                                        @if($jumatData && isset($jumatData['tema']) && $jumatData['tema'])
                                            <span class="px-3 py-1 bg-emerald-600/60 backdrop-blur-sm rounded-full text-xs font-medium text-emerald-100 border border-emerald-400/30">
                                                {{ $jumatData['tema'] }}
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($jumatData)
                                        <dl class="space-y-3 text-sm">
                                            <div class="flex items-start gap-4">
                                                <span class="text-2xl">🕌</span>
                                                <div>
                                                    <dt class="text-emerald-200/80 text-xs uppercase">Khatib</dt>
                                                    <dd class="font-bold">{{ $jumatData['khatib'] }}</dd>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-4">
                                                <span class="text-2xl mt-0.5">📅</span>
                                                <div>
                                                    <dt class="text-emerald-200/80 text-xs uppercase">Tanggal</dt>
                                                    <dd class="font-semibold">{{ $jumatData['tgl'] }}</dd>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-4">
                                                <span class="text-2xl mt-0.5">⏰</span>
                                                <div>
                                                    <dt class="text-emerald-200/80 text-xs uppercase">Waktu</dt>
                                                    <dd class="font-semibold">{{ $jumatData['jam'] }}</dd>
                                                </div>
                                            </div>
                                        </dl>
                                    @else
                                        <div class="text-center py-4">
                                            <p class="text-emerald-200/70">Belum ada jadwal khatib</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                        <!-- Mini Kalender -->
                        <div class="rounded-2xl sm:rounded-3xl bg-white border border-emerald-100/60 shadow-md p-6">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="text-lg font-semibold text-slate-900">Kalender Minggu Ini</h3>
                                <span class="px-3 py-1 bg-emerald-50 text-emerald-700 text-[10px] sm:text-xs font-medium rounded-full border border-emerald-200 whitespace-nowrap">
                                    {{ now('Asia/Jakarta')->translatedFormat('F Y') }}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-7 gap-1.5 text-center">
                                @for($i = 0; $i < 7; $i++)
                                    @php
                                        $date = now('Asia/Jakarta')->addDays($i);
                                        $isToday = $date->isToday();
                                        $isFriday = $date->dayOfWeek == 5;
                                        $dayName = $date->translatedFormat('D');
                                        $dayNumber = $date->format('d');
                                    @endphp
                                    
                                    <div class="flex flex-col items-center">
                                        <span class="text-[10px] font-medium uppercase tracking-wide
                                            {{ $isToday ? 'text-emerald-600' : ($isFriday ? 'text-emerald-700' : 'text-slate-400') }}">
                                            {{ $dayName }}
                                        </span>
                                        <div class="mt-1 relative">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-full text-sm font-semibold transition-all
                                                @if($isToday)
                                                    bg-emerald-600 text-white shadow-lg ring-2 ring-emerald-400 ring-offset-2
                                                @elseif($isFriday)
                                                    bg-emerald-50 text-emerald-700 border-2 border-emerald-200 hover:bg-emerald-100
                                                @else
                                                    bg-slate-50 text-slate-700 border border-slate-200 hover:bg-emerald-50
                                                @endif
                                            ">
                                                {{ $dayNumber }}
                                            </span>
                                            
                                            @if($isToday)
                                                <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
                                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                                                </span>
                                            @endif
                                            
                                            @if($isFriday)
                                                <span class="absolute -bottom-1 -right-1 text-[10px]">🕌</span>
                                            @endif
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                        </aside>
                    </div>
                @else
                    {{-- TIDAK ADA AGENDA - 1 BARIS 2 CARD (JUM'AT + KALENDER) --}}
                    <div>
                        <!-- Pesan Kosong - Responsif Mobile -->
                        <div class="text-center py-8 sm:py-12 md:py-16 bg-white/60 rounded-2xl sm:rounded-3xl border border-dashed border-emerald-200 backdrop-blur-sm mb-6 sm:mb-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-emerald-50 mb-3 sm:mb-4">
                                <span class="text-3xl sm:text-4xl">📅</span>
                            </div>
                            <h3 class="text-lg sm:text-xl font-semibold text-slate-700 mb-2">Belum Ada Kegiatan Mendatang</h3>
                            <p class="text-slate-500 max-w-md mx-auto text-xs sm:text-sm px-4">
                                Saat ini belum ada jadwal kegiatan. 
                                Pantau terus website ini atau follow media sosial kami untuk update terbaru.
                            </p>
                            
                            <!-- Tombol aksi - responsive: column di mobile, row di desktop -->
                            <div class="flex flex-col sm:flex-row gap-3 justify-center mt-5 sm:mt-6 px-4">
                                <a href="{{ route('galeri.index') }}" 
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 bg-emerald-50 text-emerald-700 rounded-full text-xs sm:text-sm font-medium hover:bg-emerald-100 transition-all hover:scale-105">
                                    📸 Lihat Galeri
                                </a>
                                <a href="{{ route('berita.index') }}" 
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 bg-slate-50 text-slate-600 rounded-full text-xs sm:text-sm font-medium hover:bg-slate-100 transition-all hover:scale-105">
                                    📰 Baca Berita
                                </a>
                                <a href="#donasi" 
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 sm:px-5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-full text-xs sm:text-sm font-medium hover:brightness-110 transition-all hover:scale-105">
                                    🤲 Donasi Sekarang
                                </a>
                            </div>
                        </div>

                        {{-- 1 BARIS 2 CARD (JADWAL JUM'AT + KALENDER) --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Card Shalat Jum'at -->
                            <div class="rounded-2xl sm:rounded-3xl bg-gradient-to-br from-emerald-700 via-teal-700 to-emerald-800 text-white shadow-2xl overflow-hidden ring-1 ring-emerald-500/30">
                                <div class="p-6 sm:p-7">
                                    <div class="flex items-center justify-between mb-5">
                                        <div>
                                            <p class="text-xs uppercase tracking-widest text-emerald-200/90 font-medium">Shalat Jum’at</p>
                                            <h3 class="text-xl font-bold mt-1">Pekan Ini</h3>
                                        </div>
                                        @if($jumatData && isset($jumatData['tema']) && $jumatData['tema'])
                                            <span class="inline-flex px-4 py-1.5 bg-white/25 backdrop-blur-md rounded-full text-xs font-semibold shadow-sm border border-white/20">
                                                {{ $jumatData['tema'] }}
                                            </span>
                                        @else
                                            <span class="inline-flex px-4 py-1.5 bg-white/25 backdrop-blur-md rounded-full text-xs font-semibold shadow-sm border border-white/20">
                                                Segera Hadir
                                            </span>
                                        @endif
                                    </div>
                                    
                                    @if($jumatData)
                                        <dl class="space-y-3.5 text-sm">
                                            <div class="flex items-start gap-4">
                                                <span class="text-2xl">🕌</span>
                                                <div>
                                                    <dt class="text-emerald-200/80 text-xs uppercase tracking-wider">Khatib</dt>
                                                    <dd class="font-bold text-base">{{ $jumatData['khatib'] }}</dd>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-4">
                                                <span class="text-2xl mt-0.5">📅</span>
                                                <div>
                                                    <dt class="text-emerald-200/80 text-xs uppercase tracking-wider">Tanggal</dt>
                                                    <dd class="font-semibold">{{ $jumatData['tgl'] }}</dd>
                                                </div>
                                            </div>
                                            <div class="flex items-start gap-4">
                                                <span class="text-2xl mt-0.5">⏰</span>
                                                <div>
                                                    <dt class="text-emerald-200/80 text-xs uppercase tracking-wider">Waktu</dt>
                                                    <dd class="font-semibold">{{ $jumatData['jam'] }}</dd>
                                                </div>
                                            </div>
                                        </dl>
                                    @else
                                        <div class="text-center py-6">
                                            <div class="text-5xl mb-3">🕌</div>
                                            <p class="text-emerald-100/80 font-medium">Belum ada jadwal khatib</p>
                                            <p class="text-emerald-200/60 text-xs mt-1">Pantau terus informasi terbaru</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <!-- Card Kalender -->
                            <div class="rounded-2xl sm:rounded-3xl bg-white border border-emerald-100/60 shadow-md p-6">
                                <div class="flex items-center justify-between mb-5">
                                    <h3 class="text-lg font-semibold text-slate-900">📅 Kalender Kegiatan</h3>
                                    <span class="text-sm text-slate-500 bg-slate-100 px-3 py-1 rounded-full">{{ now()->translatedFormat('F Y') }}</span>
                                </div>
                                
                                @php
                                    $days = [];
                                    $startDate = now(); // Mulai dari hari ini
                                    
                                    // Tampilkan 5 hari ke depan (termasuk hari ini)
                                    for($i = 0; $i < 7; $i++) {
                                        $date = $startDate->copy()->addDays($i);
                                        $isToday = $date->isToday();
                                        $isFriday = $date->isFriday();
                                        $days[] = ['date' => $date, 'isToday' => $isToday, 'isFriday' => $isFriday];
                                    }
                                @endphp
                                
                                <!-- Tabel Kalender -->
                                <table class="w-full">
                                    <tr class="text-center">
                                        @foreach($days as $day)
                                            <th class="pb-2 text-xs font-medium {{ $day['isFriday'] ? 'text-emerald-600' : 'text-slate-500' }}">
                                                {{ $day['date']->translatedFormat('D') }}
                                            </th>
                                        @endforeach
                                    <tr>
                                    <tr class="text-center">
                                        @foreach($days as $day)
                                            @php $date = $day['date']; @endphp
                                            <td class="py-1">
                                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-full text-sm font-bold
                                                    @if($day['isToday'])
                                                        bg-emerald-600 text-white shadow-lg ring-2 ring-emerald-400
                                                    @elseif($day['isFriday'])
                                                        bg-emerald-100 text-emerald-700 border-2 border-emerald-400
                                                    @else
                                                        bg-slate-50 text-emerald-800 border border-slate-200
                                                    @endif
                                                ">
                                                    {{ $date->format('d') }}
                                                </span>
                                            </td>
                                        @endforeach
                                    </tr>
                                </table>
                                
                                <!-- Legenda -->
                                <div class="mt-5 pt-3 border-t border-slate-100 flex flex-wrap justify-center gap-4 text-xs">
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-full bg-emerald-600"></div>
                                        <span class="text-slate-500">Hari Ini</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-full bg-emerald-100 border-2 border-emerald-400"></div>
                                        <span class="text-slate-500">Hari Jum'at</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <div class="w-3 h-3 rounded-full bg-slate-50 border border-slate-200"></div>
                                        <span class="text-slate-500">Hari Biasa</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                @endif

            </div>
        </section>

        {{-- SECTION QUOTE HARI INI - AUTO ROTATE DENGAN ANIMASI --}}
        {{-- <section class="py-10">
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
                                                                               <span class="mt-1 flex h-10 w-10 items-center justify-center rounded-full text-base font-semibold 
                                                        {{ $isToday ? 'bg-emerald-600 text-white shadow-lg ring-2 ring-emerald-400' : 'bg-slate-50 border border-slate-200 hover:bg-emerald-50' }}">
                                                {{ $date->format('d') }}
                                            </span> “Sesungguhnya bersama kesulitan ada kemudahan.” — QS. Al-Insyirah: 6
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section> --}}

