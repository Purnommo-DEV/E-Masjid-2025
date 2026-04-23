@extends('masjid.master-guest')

@push('head')

    {{-- BASIC SEO (Google) --}}
    <meta name="description" content="{{ Str::limit(strip_tags(html_entity_decode($berita->excerpt ?? $berita->isi)), 150) }}">

    {{-- OPEN GRAPH (WA & Facebook) --}}
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $berita->judul }}">
    <meta property="og:description" content="{{ Str::limit(strip_tags(html_entity_decode($berita->excerpt ?? $berita->isi)), 150) }}">
    <meta property="og:image" content="{{ $berita->cover_url ?? asset('images/default.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">

    <meta property="og:site_name" content="Masjid Raudhotul Jannah">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- TWITTER --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $berita->judul }}">
    <meta name="twitter:description" content="{{ Str::limit(strip_tags(html_entity_decode($berita->excerpt ?? $berita->isi)), 150) }}">
    <meta name="twitter:image" content="{{ $berita->cover_url ?? asset('images/default.jpg') }}">

@endpush

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50 py-10 lg:py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">

            <!-- Header Berita -->
            <div class="text-center mb-10 lg:mb-12">
                <span class="inline-flex px-5 py-2 rounded-full bg-emerald-100 text-emerald-800 text-sm font-medium mb-4">
                    {{ $berita->kategoris->pluck('nama')->first() ?? 'Berita' }}
                </span>
                <h1 class="text-3xl lg:text-4xl xl:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                    {{ $berita->judul }}
                </h1>
                <div class="flex flex-wrap justify-center items-center gap-4 text-slate-600 text-lg">
                    <span>{{ $berita->published_at?->translatedFormat('l, d F Y') ?? $berita->created_at?->translatedFormat('l, d F Y') ?? 'Baru saja' }}</span>
                    <span>•</span>
                    <span>Oleh {{ $berita->author->name ?? 'Admin Masjid' }}</span>
                </div>
            </div>

            <!-- Gambar Utama (Cover) - PERBAIKAN: pakai $berita->cover_url -->
            <div class="relative mb-10 lg:mb-12 rounded-3xl overflow-hidden shadow-2xl border border-emerald-100/70 group">
                @php
                    $coverImage = $berita->cover_url;
                @endphp
                @if($coverImage)
                    <img src="{{ $coverImage }}"
                         alt="{{ $berita->judul }}"
                         class="w-full h-auto max-h-[500px] lg:max-h-[600px] object-cover transition-transform duration-700 group-hover:scale-105"
                         onerror="this.src='{{ asset('images/default.jpg') }}'">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>
                @else
                    <div class="bg-gradient-to-br from-gray-100 to-teal-50 h-80 lg:h-[600px] flex items-center justify-center">
                        <div class="text-center">
                            <span class="text-7xl text-emerald-300 mb-4 block">🕌</span>
                            <span class="text-gray-500 text-2xl font-medium">Gambar berita belum tersedia</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Konten + Sidebar -->
            <div class="grid lg:grid-cols-3 gap-8 lg:gap-12">
                <div class="lg:col-span-2">
                    <div class="bg-white/90 backdrop-blur-sm rounded-3xl p-8 lg:p-12 shadow-xl border border-emerald-100/70">
                        <div class="text-slate-800 leading-relaxed space-y-6 max-w-none prose prose-emerald lg:prose-lg">
                            {!! $berita->isi !!}
                        </div>

                        <!-- Galeri Multiple Images -->
                        @php
                            $galleryImages = [];
                            $totalPhotos = 0;
                            $maxVisible = 6;
                            
                            if(isset($berita) && $berita->media) {
                                $galleryImages = $berita->media->map(function ($media, $index) use ($berita) {
                                    $imageUrl = get_image_url($media->image_path);
                                    return [
                                        'url' => $imageUrl,
                                        'thumb' => $imageUrl,
                                        'alt' => 'Foto kegiatan ' . $berita->judul . ' - ' . ($index + 1),
                                    ];
                                })->filter(function ($item) {
                                    return !empty($item['url']);
                                })->values()->toArray();
                                $totalPhotos = count($galleryImages);
                            }
                        @endphp

                        @if($totalPhotos > 1)
                            <div class="mt-12 pt-10 border-t border-emerald-100/50">
                                <h3 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-6 text-center lg:text-left">
                                    Dokumentasi Kegiatan
                                </h3>
                                <p class="text-sm text-slate-500 mb-6 text-center lg:text-left">
                                    {{ $totalPhotos }} foto kegiatan tersedia
                                </p>

                                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 lg:gap-6">
                                    @foreach(array_slice($galleryImages, 0, $maxVisible) as $index => $image)
                                        <button type="button" onclick="openGallery({{ $index }})" 
                                            class="aspect-square rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 group relative">
                                            <img src="{{ $image['thumb'] }}" alt="{{ $image['alt'] }}" loading="lazy" 
                                                class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110"
                                                onerror="this.src='{{ asset('images/default.jpg') }}'">
                                            <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                                <span class="text-white text-3xl">🔍</span>
                                            </div>
                                        </button>
                                    @endforeach

                                    @if($totalPhotos > $maxVisible)
                                        <button type="button" onclick="openGallery({{ $maxVisible }})" 
                                            class="aspect-square rounded-2xl overflow-hidden shadow-md hover:shadow-2xl transition-all duration-300 group relative bg-gradient-to-br from-emerald-500/80 to-teal-500/80 flex items-center justify-center">
                                            <div class="text-center text-white z-10">
                                                <span class="text-4xl font-bold block">+{{ $totalPhotos - $maxVisible }}</span>
                                                <span class="text-sm font-medium">lagi</span>
                                            </div>
                                            <div class="absolute inset-0 bg-black/10 group-hover:bg-black/20 transition-opacity duration-300"></div>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1 order-1 lg:order-2">
                    <div class="bg-white rounded-2xl p-6 lg:p-8 border border-emerald-100 shadow-md lg:sticky lg:top-20">
                        <div class="lg:pl-2">
                            <h3 class="text-xl font-bold text-slate-900 mb-6">Bagikan Berita Ini</h3>
                            
                            <!-- Share Buttons -->
                            <div class="flex flex-wrap gap-3 mb-10">
                                <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}&display=popup" target="_blank" rel="noopener noreferrer"
                                   class="flex-1 flex items-center justify-center gap-2 bg-[#1877f2] hover:bg-[#166fe5] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M24 12c0-6.627-5.373-12-12-12S0 5.373 0 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.234 2.686.234v2.953h-1.517c-1.49 0-1.955.925-1.955 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 22.954 24 17.99 24 12z"/>
                                    </svg>
                                    FB
                                </a>
                                <a href="https://twitter.com/intent/tweet?url={{ urlencode(url()->current()) }}&text={{ urlencode($berita->judul . ' - ') }}" target="_blank" rel="noopener noreferrer"
                                   class="flex-1 flex items-center justify-center gap-2 bg-[#000000] hover:bg-[#1a1a1a] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                    </svg>
                                    X
                                </a>
                                <a href="https://api.whatsapp.com/send?text={{ urlencode($berita->judul . ' - ' . url()->current()) }}" target="_blank" rel="noopener noreferrer"
                                   class="flex-1 flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20b058] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.198-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                                    </svg>
                                    WA
                                </a>
                            </div>

                            @if(!empty($related))
                                <h3 class="text-xl font-bold text-slate-900 mb-6 mt-10">Berita Terkait</h3>
                                @foreach($related as $item)
                                    <a href="{{ $item['url'] }}" class="block mb-6 group">
                                        <div class="flex gap-4">
                                            <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden">
                                                <img src="{{ $item['gambar'] ?? asset('images/default.jpg') }}" 
                                                     alt="{{ $item['judul'] }}" 
                                                     class="w-full h-full object-cover"
                                                     onerror="this.src='{{ asset('images/default.jpg') }}'">
                                            </div>
                                            <div>
                                                <h4 class="text-base font-semibold text-slate-900 group-hover:text-emerald-700 transition line-clamp-2">
                                                    {{ $item['judul'] }}
                                                </h4>
                                                <span class="text-sm text-slate-500">{{ $item['waktu'] }}</span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
                
            </div>

            <!-- Back Button -->
            <div class="text-center mt-12 lg:mt-16">
                <a href="{{ route('berita.index') }}"
                   class="inline-flex items-center gap-3 px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-medium rounded-full transition shadow-lg hover:shadow-xl transform hover:scale-105">
                    ← Kembali ke Daftar Berita
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    // Data galeri untuk modal
    let galleryImages = @json($galleryImages ?? []);
    let currentGalleryIndex = 0;
    let galleryModal = null;

    function openGallery(index) {
        currentGalleryIndex = index;
        if (!galleryModal) {
            galleryModal = document.getElementById('galleryModal');
        }
        updateGalleryImage();
        galleryModal.showModal();
    }

    function updateGalleryImage() {
        const imgElement = document.getElementById('galleryModalImg');
        const counterElement = document.getElementById('galleryCounter');
        const progressElement = document.getElementById('galleryProgress');
        
        if (imgElement && galleryImages[currentGalleryIndex]) {
            imgElement.src = galleryImages[currentGalleryIndex].url;
            imgElement.alt = galleryImages[currentGalleryIndex].alt;
        }
        
        if (counterElement && galleryImages.length) {
            counterElement.innerHTML = (currentGalleryIndex + 1) + ' / ' + galleryImages.length;
        }
        
        if (progressElement && galleryImages.length) {
            const percent = ((currentGalleryIndex + 1) / galleryImages.length) * 100;
            progressElement.style.width = percent + '%';
        }
    }

    function prevGallery() {
        if (galleryImages.length === 0) return;
        currentGalleryIndex = (currentGalleryIndex > 0) ? currentGalleryIndex - 1 : galleryImages.length - 1;
        updateGalleryImage();
    }

    function nextGallery() {
        if (galleryImages.length === 0) return;
        currentGalleryIndex = (currentGalleryIndex < galleryImages.length - 1) ? currentGalleryIndex + 1 : 0;
        updateGalleryImage();
    }

    document.addEventListener('DOMContentLoaded', function() {
        galleryModal = document.getElementById('galleryModal');
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (galleryModal && galleryModal.open) {
                if (e.key === 'ArrowLeft') {
                    prevGallery();
                } else if (e.key === 'ArrowRight') {
                    nextGallery();
                }
            }
        });
    });
</script>

<!-- Modal Galeri -->
<dialog id="galleryModal" class="modal">
    <div class="modal-box max-w-6xl w-11/12 p-0 bg-gradient-to-b from-gray-950/95 via-black/90 to-gray-900/95 rounded-t-3xl sm:rounded-3xl overflow-hidden shadow-2xl border border-emerald-500/30 backdrop-blur-md">
        <!-- Close Button -->
        <button class="absolute right-4 top-4 z-50 btn btn-circle btn-sm bg-black/50 hover:bg-black/70 text-white border-none backdrop-blur-md shadow-lg transition-all duration-300 hover:scale-110 hover:rotate-90"
                onclick="document.getElementById('galleryModal').close()">
            ✕
        </button>

        <!-- Image Area -->
        <div class="relative h-[70vh] sm:h-[85vh] flex items-center justify-center bg-black/30 overflow-hidden backdrop-blur-sm">
            <img id="galleryModalImg"
                class="max-w-full max-h-full object-contain drop-shadow-2xl transition-transform duration-500 hover:scale-[1.02]"
                alt="Gallery image">

            <!-- Nav Buttons -->
            <button onclick="prevGallery()"
                class="absolute left-4 sm:left-10 text-white text-6xl sm:text-7xl opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300 z-40 drop-shadow-2xl backdrop-blur-sm bg-black/30 hover:bg-black/50 rounded-full w-14 h-14 flex items-center justify-center">
                ❮
            </button>
            <button onclick="nextGallery()"
                class="absolute right-4 sm:right-10 text-white text-6xl sm:text-7xl opacity-70 hover:opacity-100 hover:scale-110 transition-all duration-300 z-40 drop-shadow-2xl backdrop-blur-sm bg-black/30 hover:bg-black/50 rounded-full w-14 h-14 flex items-center justify-center">
                ❯
            </button>

            <!-- Counter + Progress -->
            <div class="absolute bottom-6 sm:bottom-10 left-1/2 -translate-x-1/2 flex flex-col items-center gap-3 sm:gap-4">
                <div class="bg-black/60 backdrop-blur-xl text-white px-6 py-2.5 sm:px-8 sm:py-3 rounded-full text-lg sm:text-xl font-semibold shadow-xl border border-emerald-500/40">
                    <span id="galleryCounter"></span>
                </div>
                <div class="w-56 sm:w-96 h-1.5 bg-white/10 rounded-full overflow-hidden backdrop-blur-sm">
                    <div id="galleryProgress" class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-cyan-400 transition-all duration-700 ease-out" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop bg-black/30 backdrop-blur-xl">
        <button>close</button>
    </form>
</dialog>
@endpush