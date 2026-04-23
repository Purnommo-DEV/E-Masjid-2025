@extends('masjid.master-guest')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 py-12 lg:py-20">
        <div class="container mx-auto px-5 lg:px-8 max-w-7xl">
            
            <!-- Header -->
            <div class="text-center mb-16 relative">
                <div class="absolute top-0 left-1/2 -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-emerald-400 via-teal-400 to-emerald-400 rounded-full"></div>
                
                <span class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-100 text-emerald-700 text-sm font-semibold mb-5 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                    </span>
                    AGENDA MASJID
                </span>
                
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-slate-900 mb-5 tracking-tight">
                    Kegiatan & Kajian
                    <span class="block bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent mt-2">
                        Mendatang
                    </span>
                </h1>
                
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Ikuti berbagai kajian, pengajian, dan acara kebaikan di 
                    <span class="font-semibold text-emerald-700">{{ profil('nama') ?? 'Masjid' }}</span>
                </p>
            </div>

            <!-- List Acara -->
            @if($acaras->isNotEmpty())
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-7 xl:gap-8">
                    @foreach($acaras as $acara)
                        <div class="agenda-card group bg-white rounded-2xl border border-emerald-100/60 shadow-md hover:shadow-xl hover:shadow-emerald-200/40 hover:border-emerald-300/70 transition-all duration-400 overflow-hidden hover:-translate-y-2 flex flex-col h-full">
                            
                            <!-- Poster - Rasio 16:9 LANDSCAPE (lebar:tinggi = 16:9) -->
                            <div class="relative w-full aspect-video flex-shrink-0 overflow-hidden bg-gradient-to-br from-emerald-700 to-teal-800">
                                @if($acara->image_path && $acara->image_path != asset('storage/404.png'))
                                    <img src="{{ $acara->image_path }}" 
                                         alt="{{ $acara->judul }}" 
                                         class="w-full h-full object-cover object-center group-hover:scale-110 transition-transform duration-700 ease-out"
                                         onerror="this.src='{{ asset('storage/404.png') }}'">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center">
                                        <span class="text-white/80 text-4xl font-medium">🕌</span>
                                    </div>
                                @endif
                                
                                <!-- Badge tanggal di pojok -->
                                <div class="absolute top-3 right-3 bg-white/95 backdrop-blur-sm rounded-xl px-3 py-1.5 shadow-md z-10">
                                    <p class="text-xs font-bold text-emerald-700">
                                        {{ \Carbon\Carbon::parse($acara->mulai)->translatedFormat('d M') }}
                                    </p>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-5 lg:p-6 flex flex-col flex-grow">
                                
                                <!-- Kategori -->
                                <div class="mb-3">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $acara->kategori ?? 'Kajian' }}
                                    </span>
                                </div>

                                <!-- Judul -->
                                <h3 class="text-xl font-bold text-slate-900 mb-2 group-hover:text-emerald-700 transition-colors line-clamp-2 leading-tight min-h-[56px]">
                                    {{ $acara->judul }}
                                </h3>

                                <!-- Deskripsi -->
                                <p class="text-slate-600 text-sm mb-4 line-clamp-2 leading-relaxed min-h-[40px]">
                                    {{ Str::limit(strip_tags($acara->excerpt ?? ''), 100) }}
                                </p>

                                <!-- Info Waktu & Lokasi -->
                                <div class="space-y-2 mb-5 mt-auto">
                                    <div class="flex items-center gap-2.5 text-sm text-slate-600">
                                        <span class="text-emerald-600 text-base flex-shrink-0">📅</span>
                                        <span class="line-clamp-1">{{ $acara->tanggal_label ?? \Carbon\Carbon::parse($acara->mulai)->translatedFormat('l, d F Y') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 text-sm text-slate-600">
                                        <span class="text-emerald-600 text-base flex-shrink-0">⏰</span>
                                        <span>{{ $acara->waktu_label ?? (\Carbon\Carbon::parse($acara->mulai)->format('H:i') . ' WIB') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2.5 text-sm text-slate-600">
                                        <span class="text-rose-500 text-base flex-shrink-0">📍</span>
                                        <span class="line-clamp-1">{{ $acara->lokasi ?? 'Masjid' }}</span>
                                    </div>
                                </div>

                                <!-- Tombol Detail -->
                                <a href="{{ route('acara.show', $acara->slug) }}"
                                   class="inline-flex items-center justify-between w-full px-5 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl transition-all duration-300 shadow-md hover:shadow-lg group/btn mt-2">
                                    <span>Lihat Detail Acara</span>
                                    <svg class="w-5 h-5 group-hover/btn:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-14">
                    {{ $acaras->links('vendor.pagination.tailwind-modern') }}
                </div>

            @else
                <!-- Empty State -->
                <div class="bg-white/80 backdrop-blur-sm rounded-3xl border-2 border-dashed border-emerald-200 p-16 text-center shadow-lg">
                    <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-emerald-100 mb-6">
                        <span class="text-5xl">📅</span>
                    </div>
                    <h3 class="text-2xl font-bold text-slate-800 mb-3">Belum Ada Agenda Mendatang</h3>
                    <p class="text-slate-500 max-w-md mx-auto mb-8">
                        Saat ini belum ada jadwal kegiatan atau kajian yang dijadwalkan. 
                        Pantau terus halaman ini untuk update terbaru.
                    </p>
                    <div class="flex flex-wrap gap-4 justify-center">
                        <a href="{{ route('home') }}#berita" class="inline-flex items-center gap-2 px-6 py-3 bg-emerald-100 text-emerald-700 rounded-xl font-semibold hover:bg-emerald-200 transition-all">📰 Lihat Berita</a>
                        <a href="{{ route('galeri.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-slate-100 text-slate-600 rounded-xl font-semibold hover:bg-slate-200 transition-all">📸 Galeri Kegiatan</a>
                        <a href="{{ route('home') }}#donasi" class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all">🤲 Dukung Kegiatan</a>
                    </div>
                </div>
            @endif

            <!-- Back to Home -->
            <div class="text-center mt-16 pt-6 border-t border-emerald-100">
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
    
    /* Animasi fade in */
    .agenda-card {
        animation: fadeInUp 0.5s ease-out backwards;
    }
    
    .agenda-card:nth-child(1) { animation-delay: 0.05s; }
    .agenda-card:nth-child(2) { animation-delay: 0.1s; }
    .agenda-card:nth-child(3) { animation-delay: 0.15s; }
    .agenda-card:nth-child(4) { animation-delay: 0.2s; }
    .agenda-card:nth-child(5) { animation-delay: 0.25s; }
    .agenda-card:nth-child(6) { animation-delay: 0.3s; }
    
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
    .line-clamp-1 {
        display: -webkit-box;
        -webkit-line-clamp: 1;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    /* Aspect ratio 16:9 untuk semua gambar */
    .aspect-video {
        aspect-ratio: 16 / 9;
    }
    
    /* Pastikan gambar cover dengan object-position center */
    .object-cover {
        object-fit: cover;
        object-position: center;
    }
    
    /* Smooth transition */
    * {
        -webkit-tap-highlight-color: transparent;
    }
</style>
@endpush