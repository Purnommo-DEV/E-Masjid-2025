@extends('masjid.master-guest')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 py-12 lg:py-20">
        <div class="container mx-auto px-5 lg:px-8 max-w-7xl">
            
            <!-- Header Section -->
            <div class="text-center mb-16 relative">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-emerald-400 via-teal-400 to-emerald-400 rounded-full"></div>
                
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100 text-emerald-700 text-sm font-semibold mb-5 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    BERITA TERKINI
                </span>
                
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-5 tracking-tight">
                    Update & Kabar
                    <span class="block bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent mt-2">
                        Terbaru Masjid
                    </span>
                </h1>
                
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Ikuti berita kegiatan, pengumuman, kajian, dan informasi kebaikan seputar 
                    <span class="font-semibold text-emerald-700">{{ profil('nama') ?? 'Masjid' }}</span>
                </p>
            </div>

            <!-- Featured Berita (berita pertama) - Opsi 2 Kolom -->
            @if($beritas->isNotEmpty())
                @php $featured = $beritas->first(); @endphp
                <div class="mb-16 rounded-3xl overflow-hidden shadow-2xl border border-emerald-100/70 group bg-white hover:shadow-emerald-200/40 transition-all duration-500">
                    <div class="grid lg:grid-cols-2 gap-0">
                        <!-- Gambar Featured - Full di kolomnya -->
                        <div class="relative overflow-hidden bg-gradient-to-br from-emerald-700 to-teal-800 min-h-[300px] lg:min-h-full">
                            <img src="{{ $featured->gambar ?? asset('storage/404.png') }}"
                                alt="{{ $featured->judul }}"
                                class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-105"
                                onerror="this.src='{{ asset('storage/404.png') }}'">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent lg:bg-gradient-to-r lg:from-black/20 lg:via-transparent"></div>
                        </div>
                        
                        <div class="p-8 lg:p-10 flex flex-col justify-center">
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <span class="inline-flex px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                    {{ $featured->kategori ?? 'Berita' }}
                                </span>
                                <span class="text-xs text-slate-500 flex items-center gap-1">
                                    <span>📅</span> {{ $featured->waktu ?? now()->translatedFormat('d M Y') }}
                                </span>
                            </div>
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-4 group-hover:text-emerald-700 transition-colors line-clamp-4">
                                {{ $featured->judul }}
                            </h2>
                            <p class="text-slate-600 mb-6 line-clamp-4 leading-relaxed">
                                {{ Str::limit(strip_tags($featured->ringkas ?? $featured->isi ?? ''), 180) }}
                            </p>
                            <a href="{{ route('berita.show', $featured->slug) }}"
                            class="inline-flex items-center justify-between w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg group/btn">
                                <span>Baca Selengkapnya</span>
                                <svg class="w-5 h-5 group-hover/btn:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Grid Berita Lainnya -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-7 lg:gap-8">
                @foreach($beritas->skip(1) as $berita)
                    <div class="group bg-white rounded-2xl border border-emerald-100/60 shadow-md hover:shadow-xl hover:shadow-emerald-200/40 hover:border-emerald-300/70 transition-all duration-300 overflow-hidden hover:-translate-y-2 flex flex-col h-full">
                        
                        <!-- Gambar - landscape 16:9 -->
                        <div class="relative aspect-video flex-shrink-0 overflow-hidden bg-gradient-to-br from-emerald-700 to-teal-800">
                            <img src="{{ $berita->gambar ?? asset('storage/404.png') }}"
                                 alt="{{ $berita->judul }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                                 onerror="this.src='{{ asset('storage/404.png') }}'">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                            
                            <!-- Badge kategori di pojok -->
                            <div class="absolute top-3 left-3 z-10">
                                <span class="inline-flex px-4 py-1 rounded-lg bg-white/95 backdrop-blur-sm text-emerald-700 text-xs font-semibold shadow-sm">
                                    {{ $berita->kategori ?? 'Berita' }}
                                </span>
                            </div>
                        </div>

                        <!-- Konten Card -->
                        <div class="p-5 flex flex-col flex-grow">
                            <!-- Tanggal -->
                            <div class="flex items-center gap-2 text-xs text-slate-500 mb-3">
                                <span>📅</span>
                                <span>{{ $berita->waktu ?? now()->translatedFormat('d M Y') }}</span>
                            </div>

                            <!-- Judul -->
                            <h3 class="text-lg font-bold text-slate-900 mb-2 group-hover:text-emerald-700 transition-colors line-clamp-2 min-h-[56px]">
                                {{ $berita->judul }}
                            </h3>

                            <!-- Ringkasan -->
                            <p class="text-slate-600 text-sm mb-4 line-clamp-2 leading-relaxed min-h-[40px] flex-grow">
                                {{ Str::limit(strip_tags($berita->ringkas ?? $berita->isi ?? ''), 100) }}
                            </p>

                            <!-- Tombol Baca -->
                            <a href="{{ route('berita.show', $berita->slug) }}"
                               class="inline-flex items-center justify-between w-full px-4 py-2.5 mt-auto bg-emerald-50 hover:bg-emerald-100 text-emerald-700 font-semibold rounded-xl transition-all duration-300 group/btn">
                                <span class="text-sm">Baca Selengkapnya</span>
                                <svg class="w-4 h-4 group-hover/btn:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Empty State -->
            @if($beritas->isEmpty())
                <div class="bg-white/80 backdrop-blur-sm rounded-3xl border-2 border-dashed border-emerald-200 p-16 text-center shadow-lg">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-emerald-100 mb-6">
                        <span class="text-5xl">📰</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-3">Belum Ada Berita Terbaru</h3>
                    <p class="text-slate-500 max-w-md mx-auto mb-8">
                        Saat ini belum ada berita atau pengumuman terbaru. 
                        Pantau terus halaman ini untuk update kegiatan masjid.
                    </p>
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-100 text-emerald-700 rounded-xl font-semibold hover:bg-emerald-200 transition-all">
                        ← Kembali ke Beranda
                    </a>
                </div>
            @endif

            <!-- Pagination -->
            @if($beritas->isNotEmpty() && $beritas->hasPages())
                <div class="mt-14">
                    {{ $beritas->links('vendor.pagination.tailwind-modern') }}
                </div>
            @endif

            <!-- Back to Home -->
            <div class="text-center mt-12 pt-6 border-t border-emerald-100">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-emerald-600 hover:text-emerald-700 font-medium transition-all duration-300 hover:gap-3 group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection

@push('style')
<style>
    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb {
        background: #10b981;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #059669;
    }
    
    /* Animasi fade in untuk cards */
    .group {
        animation: fadeInUp 0.5s ease-out backwards;
    }
    
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Line clamp untuk teks */
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Aspect ratio 16:9 untuk semua gambar */
    .aspect-video {
        aspect-ratio: 16 / 9;
    }
    
    /* Object cover center */
    .object-cover {
        object-fit: cover;
        object-position: center;
    }
    
    /* Pastikan grid tidak tabrakan */
    .grid {
        display: grid;
    }
    
    /* Margin bottom untuk card di mobile agar tidak terlalu rapat */
    @media (max-width: 768px) {
        .grid {
            gap: 1.25rem;
        }
    }
    
    /* Smooth transition */
    * {
        -webkit-tap-highlight-color: transparent;
    }
</style>
@endpush