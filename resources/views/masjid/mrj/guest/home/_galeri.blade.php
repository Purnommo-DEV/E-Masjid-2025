        {{-- === GALERI + MODAL === --}}
        <section class="home-section home-section-galeri py-16 relative overflow-hidden bg-pattern-islamic">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="flex justify-between mb-5">
                    <div>
                        <p class="text-[11px] uppercase tracking-[0.2em] text-emerald-700">Galeri</p>
                        <h2 class="text-xl sm:text-2xl font-semibold text-slate-900">Dokumentasi Kegiatan</h2>
                    </div>
                    <a href="{{ route('galeri.index') }}" class="text-xs text-emerald-700">Lihat semua →</a>
                </div>

                <!-- Grid galeri -->
                <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-6 gap-2 sm:gap-3">
                    @forelse($galeri as $g)
                        <button type="button"
                            class="relative group rounded-xl overflow-hidden shadow-sm"
                            data-galeri-item="true"
                            data-id="{{ $g['id'] }}"
                            data-title="{{ $g['judul'] }}"
                            data-img="{{ $g['img'] }}">
                            
                            <img src="{{ $g['img'] }}" 
                                loading="lazy" 
                                class="w-full h-20 sm:h-28 md:h-32 object-cover group-hover:scale-110 transition"
                                onerror="this.src='{{ asset('storage/404.png') }}'">

                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-end">
                                <p class="text-[10px] text-white px-2 pb-2">{{ $g['judul'] }}</p>
                            </div>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 col-span-full">Belum ada foto galeri.</p>
                    @endforelse
                </div>
            </div>

            {{-- Modal --}}
            <!-- Modal Galeri -->
            <dialog id="galeriModal" class="modal">
                <div class="modal-box max-w-4xl p-0 overflow-hidden rounded-2xl bg-white">
                <!-- IMAGE WRAPPER (relative so overlay buttons can be absolute) -->
                    <div class="relative bg-slate-900/5">
                        <img id="galeriModalImg" class="w-full max-h-[70vh] object-contain bg-black/5" alt="Galeri foto" />

                        <!-- left overlay prev -->
                        <button id="galeriPrev" aria-label="Sebelumnya"
                            class="absolute left-3 top-1/2 -translate-y-1/2 z-50 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/90 shadow-lg hover:scale-105 transition">
                            <svg class="w-5 h-5 text-emerald-700" viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" 
                            stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>

                        <!-- right overlay next -->
                        <button id="galeriNext" aria-label="Berikutnya"
                            class="absolute right-3 top-1/2 -translate-y-1/2 z-50 inline-flex items-center justify-center w-12 h-12 rounded-full bg-white/90 shadow-lg hover:scale-105 transition">
                            <svg class="w-5 h-5 text-emerald-700" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>

                        <!-- close -->
                        <button id="closeGaleriModalBtn" type="button"
                            class="absolute right-3 top-3 z-50 inline-flex items-center justify-center w-9 h-9 rounded-md bg-black/60 text-white shadow-sm">
                            ✕
                        </button>
                    </div>

                    <!-- BODY: title, thumbs, controls -->
                    <div class="p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 id="galeriModalTitle" class="text-lg font-semibold text-slate-900">Judul Galeri</h3>
                                <div id="galeriCounter" class="text-xs text-slate-500 mt-1">0 / 0</div>
                            </div>

                            <div class="hidden sm:flex items-center gap-2">
                            <!-- optional small pill nav -->
                            <button id="galeriPrevPill" class="btn btn-sm btn-ghost">Prev</button>
                            <button id="galeriNextPill" class="btn btn-sm btn-ghost">Next</button>
                            </div>
                        </div>

                        <!-- thumbs -->
                        <div id="galeriThumbs" class="mt-3 flex gap-2 overflow-x-auto py-2">
                        <!-- thumbs inserted here -->
                        </div>

                        <!-- small hint -->
                        <div class="mt-3 text-xs text-slate-400">Gunakan panah kiri/kanan pada keyboard untuk navigasi. Klik thumbnail untuk berpindah.</div>
                    </div>
                </div>

                <!-- backdrop fallback button -->
                <form method="dialog" class="modal-backdrop">
                    <button>Tutup</button>
                </form>
            </dialog>
        </section>
