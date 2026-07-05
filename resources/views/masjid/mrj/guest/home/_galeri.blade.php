        {{-- === GALERI + MODAL === --}}
        <section class="home-section home-section-galeri py-16 relative overflow-hidden bg-pattern-islamic">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="mx-auto mb-8 max-w-4xl text-center sm:mb-10">
                    @include('masjid.mrj.guest.components.section-heading', [
                        'badgeIcon' => '🖼️',
                        'badge' => 'Galeri',
                        'title' => 'Dokumentasi',
                        'highlight' => 'Kegiatan',
                        'description' => "Momen kegiatan masjid, kajian, ibadah, dan aktivitas jamaah yang terdokumentasi.<br class='hidden sm:block'>Lihat perjalanan kebaikan dan kebersamaan umat melalui galeri kami."
                    ])
                    <div class="mt-6 flex justify-center">
                        <a href="{{ route('galeri.index') }}"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-white px-5 py-3 text-sm font-black text-emerald-700 shadow-lg shadow-emerald-900/5 ring-1 ring-emerald-100 transition hover:-translate-y-0.5 hover:bg-emerald-50 sm:w-auto sm:px-6">
                            <span>Lihat Semua Galeri</span>
                            <span>→</span>
                        </a>
                    </div>
                </div>
<!-- Grid galeri -->
<div class="mx-auto max-w-7xl">
    <div class="grid grid-flow-dense grid-cols-2 auto-rows-[4.6rem] gap-3 sm:grid-cols-4 sm:auto-rows-[5rem] sm:gap-4 lg:auto-rows-[5.4rem]">
        @forelse($galeri as $g)
            @php
                $pattern = $loop->index % 4;

                /*
                    Pattern rapi tanpa square:

                    Desktop/tablet:
                    [ Portrait ] [ Landscape ][ Landscape ] [ Portrait ]
                    [ Portrait ] [ Landscape ][ Landscape ] [ Portrait ]

                    Mobile:
                    Landscape full
                    Portrait + Portrait
                    Landscape full
                */
                $spanClass = match ($pattern) {
                    0 => 'col-span-2 row-span-2 sm:col-span-1 sm:row-span-4', // portrait desktop, landscape mobile
                    1 => 'col-span-1 row-span-3 sm:col-span-2 sm:row-span-2', // landscape desktop, portrait mobile
                    2 => 'col-span-1 row-span-3 sm:col-span-2 sm:row-span-2', // landscape desktop, portrait mobile
                    default => 'col-span-2 row-span-2 sm:col-span-1 sm:row-span-4', // portrait desktop, landscape mobile
                };
            @endphp

            <button type="button"
                class="group relative h-full w-full overflow-hidden rounded-2xl border border-white/80 bg-white text-left shadow-xl shadow-emerald-900/5 ring-1 ring-emerald-100/50 transition-all duration-500 hover:-translate-y-1 hover:shadow-2xl hover:shadow-emerald-200/40 sm:rounded-[1.5rem] {{ $spanClass }}"
                data-galeri-item="true"
                data-id="{{ $g['id'] }}"
                data-title="{{ $g['judul'] }}"
                data-img="{{ $g['img'] }}">

                <img src="{{ $g['img'] }}"
                    loading="lazy"
                    class="h-full w-full object-cover object-center transition duration-700 group-hover:scale-110"
                    onerror="this.src='{{ asset('storage/404.png') }}'">

                {{-- Overlay --}}
                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-slate-950/25 to-transparent opacity-75 transition duration-500 group-hover:opacity-90"></div>

                {{-- Soft glow --}}
                <div class="pointer-events-none absolute -right-10 -top-10 h-28 w-28 rounded-full bg-emerald-300/20 blur-2xl opacity-0 transition duration-500 group-hover:opacity-100"></div>

                {{-- Shine effect --}}
                <div class="pointer-events-none absolute inset-0 opacity-0 transition duration-700 group-hover:opacity-100">
                    <div class="absolute -left-20 top-0 h-full w-16 rotate-12 bg-white/20 blur-md"></div>
                </div>

                {{-- View icon --}}
                <div class="absolute right-3 top-3 flex h-8 w-8 scale-90 items-center justify-center rounded-full bg-white/90 text-xs text-emerald-700 opacity-0 shadow-lg shadow-emerald-900/10 backdrop-blur-md transition duration-500 group-hover:scale-100 group-hover:opacity-100 sm:h-9 sm:w-9 sm:text-sm">
                    👁️
                </div>

                {{-- Title --}}
                <div class="absolute inset-x-0 bottom-0 p-3 text-left sm:p-4">
                    <div class="translate-y-2 transition duration-500 group-hover:translate-y-0">
                        <div class="mb-2 h-1 w-8 rounded-full bg-gradient-to-r from-emerald-400 to-teal-300 transition duration-500 group-hover:w-14 sm:w-9"></div>

                        <p class="line-clamp-2 text-[11px] font-black leading-snug text-white drop-shadow sm:text-sm">
                            {{ $g['judul'] }}
                        </p>

                        <p class="mt-1 hidden text-[11px] font-semibold text-white/75 sm:block">
                            Klik untuk melihat detail
                        </p>
                    </div>
                </div>
            </button>
        @empty
            <div class="col-span-full rounded-[2rem] border border-dashed border-emerald-200 bg-white/80 px-6 py-12 text-center shadow-xl shadow-emerald-900/5">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-2xl">
                    🖼️
                </div>
                <p class="text-sm font-bold text-slate-700">Belum ada foto galeri.</p>
                <p class="mt-1 text-xs text-slate-500">Dokumentasi kegiatan akan tampil di sini.</p>
            </div>
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
