@extends('masjid.master-guest')

@section('title', 'Berita')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50 py-10 lg:py-16">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl">
            
            <!-- Header Section -->
            <div class="text-center mb-12 lg:mb-16">
                <span class="inline-flex px-6 py-3 rounded-full bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-800 text-base font-semibold mb-6 shadow-sm">
                    Berita Terkini
                </span>
                <h1 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-4 leading-tight">
                    Update & Kabar Terbaru
                </h1>
                <p class="text-lg text-slate-700 max-w-3xl mx-auto">
                    Ikuti berita kegiatan, pengumuman, kajian, dan informasi kebaikan seputar {{ profil('nama') ?? 'Masjid' }}
                </p>
            </div>

            <!-- Featured Berita (berita pertama lebih besar) -->
            @if($beritas->isNotEmpty() && $featured = $beritas->first())
                <div class="mb-12 lg:mb-16 rounded-3xl overflow-hidden shadow-2xl border border-emerald-100/70 group bg-white">
                    <div class="grid lg:grid-cols-2 gap-0">
                        <div class="h-64 lg:h-auto overflow-hidden relative">
                            <img src="{{ $featured->gambar_url ?? '/storage/404.jpg' }}"
                                 alt="{{ $featured->judul }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-105">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                        </div>
                        <div class="p-8 lg:p-12 flex flex-col justify-center">
                            <span class="inline-flex px-4 py-2 rounded-full bg-emerald-100 text-emerald-800 text-sm font-medium mb-4">
                                {{ $featured->kategoris->pluck('nama')->first() ?? 'Berita' }}
                            </span>
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-4 group-hover:text-emerald-700 transition-colors line-clamp-3">
                                {{ $featured->judul }}
                            </h2>
                            <p class="text-slate-600 mb-6 line-clamp-4">
                                {{ Str::limit(strip_tags($featured->isi ?? $featured->excerpt ?? ''), 180) }}
                            </p>
                            <div class="flex items-center gap-4 text-sm text-slate-500 mb-6">
                                <span>{{ $featured->published_at?->translatedFormat('d F Y') ?? $featured->created_at?->translatedFormat('d F Y') ?? 'Baru saja' }}</span>
                                <span>•</span>
                                <span>{{ $featured->author->name ?? 'Admin' }}</span>
                            </div>
                            <a href="{{ route('berita.show', $featured->slug) }}"
                               class="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-full transition shadow-md hover:shadow-lg">
                                Baca Selengkapnya →
                            </a>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Grid Berita Lainnya -->
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 lg:gap-8">
                @foreach($beritas->skip(1) as $berita)
                    <div class="group bg-white rounded-3xl border border-emerald-100/60 shadow-md hover:shadow-xl hover:border-emerald-300/70 transition-all duration-300 overflow-hidden hover:-translate-y-2 flex flex-col">
                        <!-- Gambar dengan aspect ratio fixed -->
                        <div class="relative overflow-hidden aspect-[4/3] bg-gradient-to-br from-gray-50 to-teal-50">
                           
                            <img src="{{ $berita->gambar_url ?? '/storage/404.jpg' }}"
                                 alt="{{ $berita->judul }}"
                                 class="absolute inset-0 w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                
                            <!-- Overlay gradient halus untuk efek premium -->
                            <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        </div>

                        <!-- Konten Card -->
                        <div class="p-6 flex flex-col flex-grow">
                            <span class="inline-flex px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-medium mb-3">
                                {{ $berita->kategoris->pluck('nama')->first() ?? 'Berita' }}
                            </span>
                            <h3 class="text-xl font-bold text-slate-900 mb-3 group-hover:text-emerald-700 transition-colors line-clamp-2">
                                {{ $berita->judul }}
                            </h3>
                            <p class="text-slate-600 mb-4 line-clamp-3 flex-grow">
                                {{ Str::limit(strip_tags($berita->isi ?? $berita->excerpt ?? ''), 100) }}
                            </p>
                            <div class="flex items-center justify-between text-sm text-slate-500 mt-auto">
                                <span>{{ $berita->published_at?->translatedFormat('d M Y') ?? $berita->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                                <a href="{{ route('berita.show', $berita->slug) }}"
                                   class="text-emerald-600 hover:text-emerald-800 font-medium flex items-center gap-1">
                                    Baca →
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Empty State -->
            @if($beritas->isEmpty())
                <div class="text-center py-20 bg-white/80 rounded-3xl border border-dashed border-emerald-200">
                    <p class="text-2xl font-medium text-slate-600 mb-4">Belum ada berita terbaru</p>
                    <p class="text-slate-500">Pantau terus untuk update kegiatan dan informasi masjid</p>
                </div>
            @endif

            <!-- Pagination -->
            <div class="mt-12 lg:mt-16 flex justify-center">
                {{ $beritas->links('vendor.pagination.tailwind-modern') }}
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-12">
                <a href="{{ route('home') }}" class="text-emerald-700 hover:text-emerald-800 font-medium inline-flex items-center gap-2">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection