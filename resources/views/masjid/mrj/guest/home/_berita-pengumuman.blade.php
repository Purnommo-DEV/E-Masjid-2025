        {{-- === BERITA & PENGUMUMAN === --}}
        <section id="berita" class="py-16 relative overflow-hidden">
            <div class="container container mx-auto px-6 lg:px-16 xl:px-24 relative grid lg:grid-cols-[1.5fr_minmax(0,1fr)] gap-10">

                <div class="relative z-10">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-8 md:mb-10">
                        <div>
                            <p class="text-xs uppercase tracking-widest font-medium text-emerald-700/80">Kabar Terkini</p>
                            <h2 class="text-2xl md:text-3xl font-bold text-slate-900 mt-1.5">Kabar Masjid & Umat</h2>
                        </div>
                        <a href="{{ route('berita.index') }}"
                           class="text-sm font-medium text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1.5 self-start sm:self-auto">
                            Lihat Semua <span aria-hidden="true">→</span>
                        </a>
                    </div>

                    <div class="grid gap-6 md:gap-8">
                        @forelse($beritas as $b)
                            <a href="{{ $b['url'] }}"
                               class="group relative flex flex-col sm:flex-row gap-4 sm:gap-6 bg-white/80 backdrop-blur-md rounded-2xl border border-white/50 shadow-md hover:shadow-2xl hover:shadow-emerald-200/30 hover:border-emerald-400/50 transition-all duration-300 overflow-hidden hover:-translate-y-1.5">

                                <!-- Gambar -->
                                <div class="relative flex-shrink-0 sm:w-48 md:w-56 overflow-hidden rounded-t-2xl sm:rounded-l-2xl sm:rounded-tr-none h-48 sm:h-auto">
                                    <img src="{{ $b['gambar'] ?? asset('storage/404.png') }}"
                                         loading="lazy"
                                         alt="{{ $b['judul'] }}"
                                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                                </div>

                                <!-- Konten teks -->
                                <div class="flex-1 p-5 sm:p-6 flex flex-col">
                                    <h3 class="text-lg sm:text-xl font-semibold text-slate-900 leading-tight line-clamp-2 md:line-clamp-3 group-hover:text-emerald-700 transition-colors mb-3">
                                        {{ $b['judul'] }}
                                    </h3>

                                    <p class="text-sm text-slate-600 line-clamp-2 sm:line-clamp-3 leading-relaxed mb-4 flex-1">
                                        {{ $b['ringkas'] ?? Str::limit(strip_tags($b['isi'] ?? ''), 140) }}
                                    </p>

                                    <!-- Bagian bawah: waktu + tombol baca -->
                                    <div class="flex items-center justify-between text-xs sm:text-sm text-slate-500 mt-auto">
                                        <span class="font-medium">{{ $b['waktu'] ?? 'Baru saja' }}</span>

                                        <!-- Tombol baca: selalu visible di mobile, hover-only di desktop -->
                                        <span class="text-emerald-600 font-medium transition-opacity duration-300
                                                     sm:opacity-0 sm:group-hover:opacity-100
                                                     inline-flex items-center gap-1.5">
                                            Selengkapnya
                                            <span aria-hidden="true">→</span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="bg-white/80 rounded-2xl p-10 md:p-12 text-center text-slate-500 border border-emerald-100/60 shadow-md">
                                <p class="text-xl font-semibold text-slate-700 mb-3">Belum ada berita terbaru</p>
                                <p class="text-sm">Yuk pantau terus update kegiatan masjid kita!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- PENGUMUMAN – shadow default lebih tegas + hover hidup -->
                <div>
                    <div class="flex items-center justify-between mb-6 sm:mb-8">
                        <h2 class="text-lg sm:text-xl font-semibold text-slate-900">Pengumuman</h2>
                        <a href="{{ route('pengumuman.index') }}"
                           class="text-sm text-emerald-700 hover:text-emerald-800 inline-flex items-center gap-1.5 hover:underline transition">
                            Semua →
                        </a>
                    </div>

                    <div class="space-y-4 sm:space-y-5">
                        @forelse($pengumuman as $p)
                            @php
                                $short = Str::limit(strip_tags($p['isi'] ?? ''), 100);
                                $tanggal = $p['tanggal'] ?? ($p['created_at'] ?? now())->translatedFormat('d M Y');
                            @endphp
                            <article class="bg-white/80 backdrop-blur-md rounded-xl sm:rounded-2xl border border-white/50
                                            shadow-md hover:shadow-2xl hover:shadow-amber-100/50 hover:border-amber-300/70
                                            transition-all duration-300 group w-full overflow-hidden hover:-translate-y-1">
                                <div class="p-4 sm:p-5 flex items-start gap-4 sm:gap-5">
                                    <!-- Ikon -->
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-amber-50 border border-amber-200/80
                                                    flex items-center justify-center text-amber-700 text-2xl sm:text-3xl shadow-sm">
                                            📢
                                        </div>
                                    </div>

                                    <!-- Konten -->
                                    <div class="flex-1 min-w-0">
                                        <h3 class="text-base sm:text-lg font-semibold text-slate-900 leading-tight line-clamp-2
                                                   group-hover:text-amber-700 transition-colors mb-2">
                                            {{ $p['judul'] }}
                                        </h3>
                                        <p class="text-sm text-slate-600 line-clamp-3 sm:line-clamp-2 leading-relaxed mb-3">
                                            {{ $short }}
                                        </p>
                                        <div class="flex flex-wrap items-center justify-between gap-3 text-xs sm:text-sm text-slate-500">
                                            {{-- <span class="whitespace-nowrap font-medium">{{ $tanggal }}</span>
                                            <button type="button"
                                                    class="text-amber-700 hover:text-amber-800 font-medium px-3 py-1.5 rounded-md
                                                           hover:bg-amber-50 transition-colors"
                                                    data-pengumuman-id="{{ $p['id'] ?? '' }}"
                                                    data-pengumuman-judul="{{ e($p['judul'] ?? '') }}"
                                                    data-pengumuman-isi="{{ e(strip_tags($p['isi'] ?? '')) }}"
                                                    data-pengumuman-url="{{ e($p['url'] ?? '#') }}"
                                                    onclick="openPengumumanPreview(this)">
                                                Lihat →
                                            </button> --}}
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="bg-amber-50/50 rounded-xl p-6 sm:p-8 text-center text-slate-500 text-sm
                                        border border-amber-200/60 shadow-md">
                                Belum ada pengumuman terbaru.
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- ======== MODAL PREVIEW PENGUMUMAN (GANTI SELURUH BLOCK INI) ======== --}}
                <dialog id="pengumumanModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="pengumumanModalTitle" aria-describedby="pengumumanModalBody">
                    <div class="modal-box max-w-3xl w-[92%] sm:w-[80%] mx-auto p-0 overflow-hidden relative">
                        {{-- CLOSE BUTTON (absolute supaya tidak mempengaruhi flow) --}}
                        <button
                            type="button"
                            class="absolute right-3 top-3 z-30 btn btn-xs btn-circle bg-slate-200 text-slate-800 border-none shadow-sm"
                            aria-label="Tutup"
                            onclick="closePengumumanPreview()"
                        >✕</button>

                        {{-- HEADER --}}
                        <header class="px-4 sm:px-6 py-4 border-b border-slate-100 bg-white/50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 id="pengumumanModalTitle" class="text-lg font-semibold text-slate-900 leading-tight truncate"></h3>
                                    <div id="pengumumanModalDate" class="text-[12px] text-slate-500 mt-1"></div>
                                </div>
                            </div>
                        </header>

                        {{-- BODY --}}
                        <div class="px-4 sm:px-6 py-4 bg-white">
                            <div id="pengumumanModalBody" class="prose text-sm text-slate-700 max-h-[58vh] overflow-auto break-words"></div>
                        </div>

                        {{-- FOOTER --}}
                        <footer class="px-4 sm:px-6 py-3 border-t border-slate-100 bg-white flex items-center justify-end gap-2">
                            {{-- <a id="pengumumanModalDetail" href="#" class="btn btn-sm bg-emerald-600 text-white rounded-full px-4">Buka Halaman</a> --}}
                            <button type="button" class="btn btn-sm bg-white-600 text-black border rounded-full px-4" onclick="closePengumumanPreview()">Tutup</button>
                        </footer>
                    </div>

                    {{-- fallback backdrop (untuk browser yg tidak mendukung <dialog>) --}}
                    <div class="modal-backdrop" aria-hidden="true" onclick="closePengumumanPreview()"></div>
                </dialog>
            </div>
        </section>
