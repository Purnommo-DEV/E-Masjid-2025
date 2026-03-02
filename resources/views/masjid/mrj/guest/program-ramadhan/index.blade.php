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

    <!-- HERO BESAR (tetap sama) -->
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

    <!-- ==================== DAFTAR PROGRAM (DINAMIS) ==================== -->
    <section id="program-list" class="pb-20 sm:pb-32 lg:pb-40 relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16 lg:mb-20">
                <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight mb-4 bg-gradient-to-r from-amber-300 via-teal-200 to-cyan-200 bg-clip-text text-transparent">
                    Program Kegiatan Ramadhan 1447 H
                </h2>
                <p class="text-lg sm:text-xl lg:text-2xl text-teal-100/80 max-w-4xl mx-auto leading-relaxed">
                    Berbagai kegiatan ibadah, sosial, dan kebersamaan untuk menghidupkan masjid dan memberkahi umat.
                </p>
            </div>

            <!-- Grid Card Modern Estetik – sekarang pakai $beritas -->
            @if($beritas->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8 lg:gap-10">
                    @foreach($beritas as $index => $berita)
                        @php
                            // Ambil gambar pertama (gambar atau banner)
                            $media = $berita->getFirstMedia('gambar') ?? $berita->getFirstMedia('banner');
                            $imgUrl = $media
                                ? asset('storage/' . ($media->custom_properties['folder'] ?? 'berita') . '/' . $media->file_name)
                                : asset('storage/404.jpg');

                            // Excerpt / ringkasan
                            $excerpt = $berita->excerpt
                                ? Str::limit(strip_tags($berita->excerpt), 120)
                                : Str::limit(strip_tags($berita->isi ?? ''), 120);

                            // Gradient cyclic agar tetap variatif meski data dinamis
                            $gradients = [
                                'from-emerald-800 via-teal-700 to-cyan-700',
                                'from-indigo-950 via-cyan-900 to-teal-800',
                                'from-cyan-900 via-teal-800 to-emerald-700',
                                'from-emerald-900 via-cyan-800 to-teal-700',
                            ];
                            $gradient = $gradients[$index % count($gradients)];
                        @endphp

                        <div class="group relative rounded-3xl overflow-hidden bg-gradient-to-br {{ $gradient }} backdrop-blur-xl border border-white/10 shadow-[0_8px_32px_rgba(0,0,0,0.3)] hover:shadow-[0_25px_50px_-12px_rgba(16,185,129,0.5)] transition-all duration-400 ease-out hover:-translate-y-3 hover:scale-[1.015] hover:brightness-105 active:scale-98 opacity-0 animate-fade-in-up ripple min-h-[480px] flex flex-col z-0 hover:z-10" style="animation-delay: {{ $index * 80 }}ms;">
                            <!-- Gambar hero landscape -->
                            <div class="relative h-48 sm:h-56 lg:h-64 overflow-hidden flex-shrink-0">
                                <img src="{{ $imgUrl }}" alt="{{ $berita->judul }}"
                                     class="w-full h-full object-cover transition-transform duration-1000 group-hover:scale-110">
                                <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/85 to-transparent pointer-events-none"></div>
                                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>
                            </div>

                            <!-- Konten -->
                            <div class="flex-1 flex flex-col p-6 sm:p-8 lg:p-10">
                                <h3 class="text-2xl sm:text-3xl lg:text-3xl font-extrabold text-transparent bg-gradient-to-r from-white via-teal-100 to-cyan-100 bg-clip-text leading-tight drop-shadow-[0_4px_12px_rgba(0,0,0,0.6)] mb-4 text-balance hyphens-auto transition-all duration-500 group-hover:drop-shadow-[0_8px_20px_rgba(16,185,129,0.6)] group-hover:-translate-y-1">
                                    {{ $berita->judul }}
                                </h3>
                                <p class="text-base sm:text-lg lg:text-xl text-teal-50/90 mb-6 flex-grow line-clamp-4">
                                    {{ $excerpt }}
                                </p>
                                <div class="mt-auto">
                                    <a href="{{ route('program-ramadhan.show', $berita->slug) }}"
                                       class="inline-flex items-center px-6 sm:px-8 py-3 sm:py-4 bg-white/15 backdrop-blur-lg hover:bg-white/25 text-white font-semibold rounded-full transition-all shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 w-fit text-sm sm:text-base ripple">
                                        Lihat Detail →
                                    </a>
                                </div>
                            </div>

                            <!-- Bottom glow bar -->
                            <div class="h-3 bg-gradient-to-r from-white/20 via-white/10 to-transparent"></div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination Laravel (real, bukan dummy) -->
                <div class="mt-16 lg:mt-20">
                    {{ $beritas->appends(request()->query())->links('pagination::tailwind') }}
                </div>
            @else
                <div class="text-center py-20">
                    <p class="text-2xl text-teal-200/80">Belum ada program Ramadhan yang dipublikasikan saat ini.</p>
                    <p class="mt-4 text-lg text-teal-100/70">Pantau terus atau hubungi panitia masjid untuk info terbaru.</p>
                </div>
            @endif

            <!-- Back to Home -->
            <div class="text-center mt-12 lg:mt-16">
                <a href="{{ route('home') }}" class="text-teal-300 hover:text-teal-200 font-medium inline-flex items-center gap-3 text-lg transition-all hover:scale-105 active:scale-95 ripple">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

@push('style')
<style>
    /* Gradient bergerak slow aurora */
    @keyframes gradient-move {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    .animate-gradient-move {
        background-size: 300% 300%;
        animation: gradient-move 25s ease infinite;
    }

    /* Stagger fade-in */
    .animate-fade-in-up {
        opacity: 0;
        transform: translateY(60px);
        transition: opacity 0.9s ease-out, transform 0.9s ease-out;
    }
    .animate-fade-in-up.visible {
        opacity: 1;
        transform: translateY(0);
    }

    /* Ripple effect */
    .ripple {
        position: relative;
        overflow: hidden;
    }
    .ripple::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 0;
        height: 0;
        background: rgba(16, 185, 129, 0.25);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        opacity: 0;
        transition: all 0.6s;
        pointer-events: none;
    }
    .ripple:active::after {
        width: 300px;
        height: 300px;
        opacity: 1;
    }

    /* Hover card lebih estetik */
.group {
        transition: all 0.4s ease-out;
    }

    .group:hover {
        transform: translateY(-0.75rem) scale(1.015);   /* <--- INI YANG DIKECILKAN */
        box-shadow: 0 25px 50px -12px rgba(16,185,129,0.5);
        filter: brightness(1.05);
        z-index: 10;   /* agar card hover berada di atas tetangga */
    }

    /* Kurangi scale gambar agar tidak terlalu dominan */
    .group:hover img {
        transform: scale(1.08);   /* dari 1.10 → lebih kecil */
    }
</style>
@endpush

@push('scripts')
<script>
    // Stagger animation (tetap sama)
    const cards = document.querySelectorAll('#program-list .group');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('opacity-100');
                entry.target.classList.remove('opacity-0');
                entry.target.classList.remove('translate-y-[60px]');
            }
        });
    }, { threshold: 0.15 });
    cards.forEach(card => observer.observe(card));
</script>
@endpush