@extends('masjid.master-guest')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 py-12 lg:py-20">
        <div class="container mx-auto px-5 lg:px-8 max-w-7xl">
            <div class="text-center mb-12 lg:mb-16">
                <span class="inline-flex items-center px-4 py-2 rounded-full bg-emerald-100 text-emerald-700 text-sm font-semibold mb-5">
                    GALERI KEGIATAN
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-5 tracking-tight">
                    Dokumentasi
                    <span class="block text-emerald-700 mt-2">{{ profil('nama') ?? 'Masjid' }}</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Arsip foto dan video kegiatan masjid, kajian, program sosial, Ramadhan, dan qurban.
                </p>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                @forelse($galeris as $item)
                    @php
                        $thumbnail = $item->thumbnail_url ?: asset('storage/404.png');
                        $isVideo = $item->tipe === 'video' && $item->url_video;
                    @endphp

                    <article class="group bg-white rounded-2xl border border-emerald-100 shadow-md hover:shadow-xl hover:border-emerald-300 transition-all duration-300 overflow-hidden">
                        <div class="relative aspect-video overflow-hidden bg-emerald-900">
                            <img src="{{ $thumbnail }}"
                                 alt="{{ $item->judul }}"
                                 loading="lazy"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                 onerror="this.src='{{ asset('storage/404.png') }}'">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent"></div>
                            <span class="absolute top-3 left-3 px-3 py-1 rounded-full bg-white/95 text-emerald-700 text-xs font-semibold uppercase">
                                {{ $item->tipe }}
                            </span>
                        </div>

                        <div class="p-5">
                            <h2 class="text-lg font-bold text-slate-900 mb-2 line-clamp-2">
                                {{ $item->judul }}
                            </h2>
                            <p class="text-sm text-slate-600 line-clamp-2 min-h-[40px]">
                                {{ Str::limit(strip_tags($item->keterangan ?? ''), 100) }}
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach($item->kategoris as $kategori)
                                    <span class="px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium">
                                        {{ $kategori->nama }}
                                    </span>
                                @endforeach
                            </div>

                            @if($isVideo)
                                <a href="{{ $item->url_video }}"
                                   target="_blank"
                                   rel="noopener noreferrer"
                                   class="mt-5 inline-flex items-center justify-between w-full px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl font-semibold transition">
                                    <span>Tonton Video</span>
                                    <span aria-hidden="true">-&gt;</span>
                                </a>
                            @else
                                <button type="button"
                                        data-gallery-button
                                        data-gallery-id="{{ $item->id }}"
                                        data-gallery-title="{{ e($item->judul) }}"
                                        data-gallery-thumb="{{ $thumbnail }}"
                                        class="mt-5 inline-flex items-center justify-between w-full px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl font-semibold transition">
                                    <span>Lihat Foto</span>
                                    <span aria-hidden="true">-&gt;</span>
                                </button>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="sm:col-span-2 lg:col-span-3 xl:col-span-4 bg-white rounded-3xl border-2 border-dashed border-emerald-200 p-12 text-center shadow-sm">
                        <h2 class="text-2xl font-bold text-slate-800 mb-3">Belum Ada Galeri Publik</h2>
                        <p class="text-slate-500 max-w-md mx-auto mb-8">
                            Dokumentasi kegiatan akan tampil di halaman ini setelah dipublikasikan oleh admin.
                        </p>
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center px-6 py-3 bg-emerald-100 text-emerald-700 rounded-xl font-semibold hover:bg-emerald-200 transition">
                            Kembali ke Beranda
                        </a>
                    </div>
                @endforelse
            </div>

            @if($galeris->hasPages())
                <div class="mt-12">
                    {{ $galeris->links('vendor.pagination.tailwind-modern') }}
                </div>
            @endif
        </div>
    </div>

    <dialog id="galleryPageModal" class="modal">
        <div class="modal-box max-w-5xl w-11/12 p-0 overflow-hidden bg-white rounded-2xl">
            <div class="relative bg-slate-950">
                <button type="button"
                        id="galleryPageClose"
                        class="absolute right-4 top-4 z-20 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-900 shadow">
                    x
                </button>
                <img id="galleryPageImage"
                     class="w-full max-h-[75vh] object-contain bg-slate-950"
                     alt="Foto galeri">
                <button type="button"
                        id="galleryPagePrev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/90 text-slate-900 shadow">
                    &lt;
                </button>
                <button type="button"
                        id="galleryPageNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 inline-flex h-12 w-12 items-center justify-center rounded-full bg-white/90 text-slate-900 shadow">
                    &gt;
                </button>
            </div>
            <div class="p-5">
                <h3 id="galleryPageTitle" class="text-xl font-bold text-slate-900"></h3>
                <p id="galleryPageCounter" class="text-sm text-slate-500 mt-1"></p>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>Tutup</button>
        </form>
    </dialog>
@endsection

@push('scripts')
    <script>
        (() => {
            const modal = document.getElementById('galleryPageModal');
            const image = document.getElementById('galleryPageImage');
            const title = document.getElementById('galleryPageTitle');
            const counter = document.getElementById('galleryPageCounter');
            const closeButton = document.getElementById('galleryPageClose');
            const prevButton = document.getElementById('galleryPagePrev');
            const nextButton = document.getElementById('galleryPageNext');

            let photos = [];
            let currentIndex = 0;

            const render = () => {
                if (!photos.length) {
                    return;
                }

                const current = photos[currentIndex];
                image.src = current.url;
                image.alt = current.file_name || title.textContent || 'Foto galeri';
                counter.textContent = `${currentIndex + 1} / ${photos.length}`;
                prevButton.classList.toggle('hidden', photos.length <= 1);
                nextButton.classList.toggle('hidden', photos.length <= 1);
            };

            const move = (direction) => {
                if (!photos.length) {
                    return;
                }

                currentIndex = (currentIndex + direction + photos.length) % photos.length;
                render();
            };

            document.querySelectorAll('[data-gallery-button]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const galleryTitle = button.dataset.galleryTitle;
                    const fallbackPhoto = {
                        url: button.dataset.galleryThumb,
                        file_name: galleryTitle,
                    };

                    title.textContent = galleryTitle;
                    photos = [fallbackPhoto];
                    currentIndex = 0;
                    render();
                    modal.showModal();

                    try {
                        const response = await fetch(`/home/galeri/${button.dataset.galleryId}`);
                        const payload = await response.json();

                        if (Array.isArray(payload.fotos) && payload.fotos.length) {
                            photos = payload.fotos;
                            currentIndex = 0;
                            render();
                        }
                    } catch (error) {
                        console.error('Gagal memuat foto galeri', error);
                    }
                });
            });

            closeButton?.addEventListener('click', () => modal.close());
            prevButton?.addEventListener('click', () => move(-1));
            nextButton?.addEventListener('click', () => move(1));

            document.addEventListener('keydown', (event) => {
                if (!modal.open) {
                    return;
                }

                if (event.key === 'ArrowLeft') {
                    move(-1);
                }

                if (event.key === 'ArrowRight') {
                    move(1);
                }
            });
        })();
    </script>
@endpush
