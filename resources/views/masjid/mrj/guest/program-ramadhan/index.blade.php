@extends('masjid.master-guest')
@section('title', 'Program Ramadhan 1447 H - Masjid Raudhotul Jannah TCE')
@section('og_title', 'Program Ramadhan 1447 H - Masjid Raudhotul Jannah')
@section('meta_description', 'Jelajahi program spesial Ramadhan di Masjid Raudhotul Jannah Taman Cipulir Estate: ibadah, sosial, anak, dan kebersamaan. Ikuti progres dan berkontribusi bersama.')
@section('og_image', asset('images/ramadhan-share.jpg')) <!-- ganti dengan gambar hero kalau ada -->

@section('content')
<div class="min-h-screen bg-gradient-to-br from-emerald-950 via-teal-950 to-cyan-950 text-white relative overflow-hidden">
    <!-- Background efek malam Ramadhan -->
    <div class="absolute inset-0 opacity-20 pointer-events-none">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_20%_30%,_rgba(16,185,129,0.15)_0%,_transparent_60%)]"></div>
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_80%_70%,_rgba(245,158,11,0.12)_0%,_transparent_60%)]"></div>
    </div>

    <!-- HERO BESAR -->
    <section class="relative pt-20 pb-24 lg:pt-32 lg:pb-40 flex items-center justify-center">
        <div class="absolute top-10 sm:top-20 right-10 sm:right-20 w-32 sm:w-64 h-32 sm:h-64 opacity-40 animate-pulse-slow">
            <svg viewBox="0 0 200 200" class="w-full h-full drop-shadow-2xl">
                <circle cx="100" cy="100" r="90" fill="none" stroke="#fef08a" stroke-width="8" opacity="0.4"/>
                <circle cx="120" cy="100" r="70" fill="#0f766e" opacity="0.7"/>
            </svg>
        </div>

        <div class="relative z-10 text-center px-6 max-w-5xl">
            <span class="inline-flex px-6 py-3 rounded-full bg-white/10 backdrop-blur-lg border border-white/20 text-base font-medium mb-6 shadow-lg">
                <span class="mr-2 animate-pulse">🌙</span> Ramadhan 1447 H
            </span>

            <h1 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-extrabold tracking-tight leading-tight mb-6">
                Program Ramadhan
                <span class="block mt-2 sm:mt-4 text-amber-300/90 text-5xl sm:text-6xl md:text-7xl lg:text-8xl font-black">
                    Masjid Raudhotul Jannah TCE
                </span>
            </h1>

            <p class="text-lg sm:text-xl md:text-2xl text-teal-100/90 max-w-3xl mx-auto leading-relaxed font-light">
                Bulan penuh berkah — dari ibadah hingga kebaikan sosial, semuanya dirangkai untuk menghidupkan masjid dan memberkahi kita semua.
            </p>

            <div class="mt-10 flex flex-wrap justify-center gap-4 sm:gap-6">
                <a href="#program-list" class="btn btn-lg bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white shadow-2xl shadow-amber-900/40 px-10 py-5 text-lg font-bold rounded-full transition-all hover:scale-105">
                    Jelajahi Program →
                </a>
            </div>
        </div>
    </section>

    <!-- ==================== DAFTAR PROGRAM (MASONRY + WAH) ==================== -->
    <section id="program-list" class="pb-20 sm:pb-32 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-4">
                    Program Unggulan Ramadhan
                </h2>
                <p class="text-xl text-teal-100/80 max-w-3xl mx-auto">
                    Dari ibadah hingga kebaikan sosial — semuanya dirangkai untuk menghidupkan masjid dan memberkahi umat.
                </p>
            </div>

            <!-- Masonry Grid -->
            <div class="columns-1 sm:columns-2 lg:columns-3 xl:columns-4 gap-6 space-y-6">
                @forelse($beritas as $berita)
                    <div class="group break-inside-avoid mb-6 rounded-3xl overflow-hidden bg-gradient-to-br from-white/10 to-white/5 backdrop-blur-xl border border-white/10 shadow-2xl hover:shadow-[0_30px_60px_-15px_rgba(245,158,11,0.3)] transition-all duration-500 hover:-translate-y-4">
                        <!-- Gambar + overlay -->
                        <div class="relative h-48 sm:h-64 overflow-hidden">
                            <img src="{{ $berita->gambar_url ?? '/storage/404.jpg' }}"
                                 alt="{{ $berita->judul }}"
                                 class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">

                            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

                            <div class="absolute bottom-0 left-0 right-0 p-6">
                                <span class="inline-flex px-4 py-1.5 rounded-full bg-amber-500/80 backdrop-blur-sm text-white text-sm font-medium mb-3">
                                    {{ $berita->kategoris->pluck('nama')->first() ?? 'Program' }}
                                </span>
                                <h3 class="text-2xl sm:text-3xl font-bold text-white leading-tight drop-shadow-lg line-clamp-2">
                                    {{ $berita->judul }}
                                </h3>
                            </div>
                        </div>

                        <!-- Konten -->
                        <div class="p-6 sm:p-8">
                            <p class="text-base sm:text-lg text-teal-50/90 leading-relaxed mb-6 line-clamp-4">
                                {{ Str::limit(strip_tags($berita->excerpt ?? $berita->isi), 140) }}
                            </p>

                            <div class="flex items-center justify-between text-sm text-teal-100/80">
                                <span>{{ $berita->published_at?->translatedFormat('d M Y') ?? $berita->created_at?->translatedFormat('d M Y') ?? 'Baru saja' }}</span>
                                <a href="{{ route('program-ramadhan.show', $berita->slug) }}"
                                   class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white font-medium rounded-full transition-all shadow-lg hover:shadow-xl hover:scale-105">
                                    Lihat Detail →
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-20 text-teal-100/70 text-xl">
                        Program Ramadhan sedang disiapkan. Pantau terus ya!
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            <div class="mt-12 lg:mt-16 flex justify-center">
                {{ $beritas->links('vendor.pagination.tailwind-modern') }}
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-12">
                <a href="{{ route('home') }}" class="text-teal-300 hover:text-teal-200 font-medium inline-flex items-center gap-2 text-lg">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>
</div>
@endsection