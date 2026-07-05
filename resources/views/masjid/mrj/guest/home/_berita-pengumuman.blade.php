{{-- === BERITA & PENGUMUMAN === --}}
<section id="berita" class="py-16 relative overflow-hidden">
    <!-- Background dengan warna solid + pattern -->
    <div class="absolute inset-0 bg-gradient-to-br from-emerald-600/5 via-slate-50/95 to-teal-600/5"></div>
    
    <!-- Decorative Elements -->
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-20 right-20 w-96 h-96 bg-emerald-400/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 left-20 w-80 h-80 bg-teal-400/10 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-300/5 rounded-full blur-3xl"></div>
        
        <!-- Geometric shapes -->
        <div class="absolute top-1/4 right-10 w-12 h-12 border-2 border-emerald-200/20 rounded-lg rotate-12"></div>
        <div class="absolute bottom-1/3 left-10 w-8 h-8 border-2 border-teal-200/20 rounded-full"></div>
    </div>

    <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative z-10">
        
        <!-- Section Header -->
        <div class="mx-auto mb-8 max-w-4xl text-center sm:mb-10">
            @include('masjid.mrj.guest.components.section-heading', [
                'badgeIcon' => '📰',
                'badge' => 'Kabar Terkini',
                'title' => 'Berita &',
                'highlight' => 'Pengumuman',
                'description' => "Update terbaru seputar kegiatan, informasi, dan pengumuman masjid."
            ])
        </div>

        <div class="grid lg:grid-cols-[1.5fr_minmax(0,1fr)] gap-8">
            <!-- BERITA -->
            <div class="relative z-10">
                <div class="grid gap-5 md:gap-6">
                    @forelse($beritas as $b)
                        <a href="{{ $b['url'] }}"
                           class="group relative flex flex-col sm:flex-row gap-5 bg-gradient-to-br from-white to-emerald-50/30 rounded-2xl border border-emerald-100/80 shadow-md hover:shadow-2xl hover:shadow-emerald-200/40 hover:border-emerald-400/60 transition-all duration-500 overflow-hidden hover:-translate-y-1.5">

                            <!-- Gambar dengan overlay warna -->
                            <div class="relative flex-shrink-0 sm:w-44 md:w-52 overflow-hidden rounded-t-2xl sm:rounded-l-2xl sm:rounded-tr-none h-44 sm:h-auto">
                                <img src="{{ $b['gambar'] ?? asset('storage/404.png') }}"
                                     loading="lazy"
                                     alt="{{ $b['judul'] }}"
                                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                <div class="absolute inset-0 bg-gradient-to-t from-emerald-900/30 via-emerald-900/10 to-transparent"></div>
                                <!-- Badge floating -->
                                <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-full text-[10px] font-bold text-emerald-700 shadow-lg border border-emerald-200/50">
                                    <span class="flex items-center gap-1.5">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Baru
                                    </span>
                                </div>
                            </div>

                            <!-- Konten teks -->
                            <div class="flex-1 p-4 sm:p-5 flex flex-col">
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-[10px] font-bold text-white bg-gradient-to-r from-emerald-600 to-teal-600 px-3 py-1 rounded-full shadow-sm">
                                        📰 Berita
                                    </span>
                                </div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-800 leading-tight line-clamp-2 md:line-clamp-3 group-hover:text-emerald-700 transition-colors duration-300 mb-2.5">
                                    {{ $b['judul'] }}
                                </h3>

                                <p class="text-sm text-slate-600 line-clamp-2 sm:line-clamp-3 leading-relaxed mb-3 flex-1">
                                    {{ $b['ringkas'] ?? Str::limit(strip_tags($b['isi'] ?? ''), 140) }}
                                </p>

                                <!-- Bagian bawah -->
                                <div class="flex items-center justify-between text-xs sm:text-sm text-slate-500 mt-auto pt-3 border-t border-emerald-100">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="font-medium">{{ $b['waktu'] ?? 'Baru saja' }}</span>
                                    </div>
                                    <span class="inline-flex items-center gap-1.5 text-emerald-600 font-bold bg-emerald-100/50 px-3 py-1 rounded-full hover:bg-emerald-100 transition-colors duration-300">
                                        Baca
                                        <span aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="bg-white rounded-2xl p-10 md:p-12 text-center border border-emerald-100/80 shadow-md">
                            <p class="text-xl font-semibold text-slate-700 mb-3">Belum ada berita terbaru</p>
                            <p class="text-sm text-slate-500">Yuk pantau terus update kegiatan masjid kita!</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- PENGUMUMAN -->
            <div>
                <div class="flex items-center justify-between mb-5 sm:mb-6">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100/80 border border-amber-200/50 text-amber-700 text-xs font-semibold">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Pengumuman
                    </div>
                    <a href="{{ route('pengumuman.index') }}"
                       class="text-sm text-amber-600 hover:text-amber-700 transition-colors duration-300 inline-flex items-center gap-1 font-medium hover:underline">
                        Semua →
                    </a>
                </div>

                <div class="space-y-4">
                    @forelse($pengumuman as $p)
                        @php
                            $short = Str::limit(strip_tags($p['isi'] ?? ''), 100);
                            $tanggal = $p['tanggal'] ?? ($p['created_at'] ?? now())->translatedFormat('d M Y');
                        @endphp
                        <article class="group bg-gradient-to-br from-white to-amber-50/30 rounded-xl sm:rounded-2xl border border-amber-100/80
                                        shadow-md hover:shadow-2xl hover:shadow-amber-200/40 hover:border-amber-400/60
                                        transition-all duration-500 w-full overflow-hidden hover:-translate-y-1">
                            <div class="p-4 sm:p-5 flex items-start gap-4">
                                <!-- Ikon besar -->
                                <div class="flex-shrink-0">
                                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100
                                                flex items-center justify-center text-3xl shadow-md border border-amber-200/50
                                                group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                                        📢
                                    </div>
                                </div>

                                <!-- Konten -->
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-sm sm:text-base font-bold text-slate-800 leading-tight line-clamp-2
                                               group-hover:text-amber-700 transition-colors duration-300 mb-1.5">
                                        {{ $p['judul'] }}
                                    </h3>
                                    <p class="text-sm text-slate-600 line-clamp-2 leading-relaxed mb-2">
                                        {{ $short }}
                                    </p>
                                    <div class="flex items-center justify-between text-xs text-slate-500">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3 h-3 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <span>{{ $tanggal }}</span>
                                        </div>
                                        <button type="button"
                                                class="text-amber-600 hover:text-amber-700 font-semibold transition-colors duration-300 hover:underline"
                                                data-pengumuman-id="{{ $p['id'] ?? '' }}"
                                                data-pengumuman-judul="{{ e($p['judul'] ?? '') }}"
                                                data-pengumuman-isi="{{ e(strip_tags($p['isi'] ?? '')) }}"
                                                data-pengumuman-url="{{ e($p['url'] ?? '#') }}"
                                                onclick="openPengumumanPreview(this)">
                                            Lihat →
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="bg-white rounded-xl p-6 sm:p-8 text-center border border-amber-100/80 shadow-md">
                            <p class="text-sm text-slate-500">Belum ada pengumuman terbaru.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL -->
    <dialog id="pengumumanModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="pengumumanModalTitle" aria-describedby="pengumumanModalBody">
        <div class="modal-box max-w-3xl w-[92%] sm:w-[80%] mx-auto p-0 overflow-hidden relative bg-white border border-slate-200 shadow-2xl rounded-2xl">
            <button type="button" class="absolute right-3 top-3 z-30 w-8 h-8 rounded-full bg-slate-100 text-slate-800 border border-slate-200 shadow-sm hover:bg-slate-200 transition-colors flex items-center justify-center" aria-label="Tutup" onclick="closePengumumanPreview()">✕</button>

            <header class="px-4 sm:px-6 py-4 border-b border-slate-100 bg-white">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 id="pengumumanModalTitle" class="text-lg font-bold text-slate-900 leading-tight truncate"></h3>
                        <div id="pengumumanModalDate" class="text-[12px] text-slate-500 mt-1"></div>
                    </div>
                </div>
            </header>

            <div class="px-4 sm:px-6 py-4 bg-white">
                <div id="pengumumanModalBody" class="prose text-sm text-slate-700 max-h-[58vh] overflow-auto break-words"></div>
            </div>

            <footer class="px-4 sm:px-6 py-3 border-t border-slate-100 bg-white flex items-center justify-end gap-2">
                <button type="button" class="px-5 py-1.5 bg-white text-slate-700 border border-slate-300 rounded-full text-sm font-medium hover:bg-slate-50 transition-colors" onclick="closePengumumanPreview()">Tutup</button>
            </footer>
        </div>
        <div class="modal-backdrop" aria-hidden="true" onclick="closePengumumanPreview()"></div>
    </dialog>
</section>