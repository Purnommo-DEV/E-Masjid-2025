@extends('masjid.master-guest')
@section('title', 'Semua Agenda & Kajian')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50 py-12 lg:py-16">
        <div class="container mx-auto px-5 lg:px-8">

            <!-- Header -->
            <div class="text-center mb-12">
                <span class="inline-flex px-5 py-2 rounded-full bg-emerald-100 text-emerald-800 text-sm font-medium mb-4">
                    Agenda Masjid
                </span>
                <h1 class="text-4xl lg:text-5xl font-extrabold text-slate-900 mb-4">
                    Semua Kegiatan & Kajian Mendatang
                </h1>
                <p class="text-lg text-slate-700 max-w-2xl mx-auto">
                    Ikuti berbagai kajian, pengajian, dan acara kebaikan di {{ profil('nama') ?? 'Masjid' }}
                </p>
            </div>

            <!-- List Acara -->
            @if($acaras->isNotEmpty())
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 xl:gap-8">
                    @foreach($acaras as $acara)
                        <div class="group bg-white rounded-3xl border border-emerald-100/60 shadow-md hover:shadow-xl hover:border-emerald-300/70 transition-all duration-300 overflow-hidden hover:-translate-y-2">
                            <!-- Poster -->
                            @if($acara->hasMedia('poster'))
                                <div class="h-48 overflow-hidden">
                                    <img src="{{ $acara->poster_url ?? '/storage/404.jpg' }}" alt="{{ $acara->judul }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                                </div>
                            @else
                                <div class="h-48 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                    <span class="text-gray-400 text-lg">Tidak ada poster</span>
                                </div>
                            @endif

                            <div class="p-6 lg:p-7">
                                <div class="flex flex-wrap items-center justify-between mb-4 gap-3">
                                    <span class="inline-flex px-4 py-1.5 rounded-full bg-emerald-50 text-emerald-700 text-xs sm:text-sm font-medium">
                                        {{ $acara->kategoris->pluck('nama')->first() ?? 'Kajian' }}
                                    </span>
                                    <span class="text-sm font-medium text-slate-600 bg-slate-100 px-3 py-1 rounded-full">
                                        {{ $acara->mulai?->translatedFormat('l, d F Y') ?? 'Segera diumumkan' }}
                                    </span>
                                </div>

                                <h3 class="text-xl lg:text-2xl font-bold text-slate-900 mb-3 group-hover:text-emerald-700 transition-colors line-clamp-2">
                                    {{ $acara->judul }}
                                </h3>

                                <p class="text-slate-600 mb-5 line-clamp-3">
                                    {{ Str::limit(strip_tags($acara->deskripsi ?? ''), 140) }}
                                </p>

                                <div class="flex flex-wrap gap-6 mb-6 text-sm text-slate-700">
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xl text-emerald-600">⏰</span>
                                        <span>
                                            {{ $acara->mulai?->format('H:i') ?? '-' }}
                                            @if($acara->selesai)
                                                – {{ $acara->selesai->format('H:i') }}
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-xl text-rose-600">📍</span>
                                        <span class="line-clamp-1">{{ $acara->lokasi ?? 'Masjid' }}</span>
                                    </div>
                                </div>

                                <a href="{{ route('acara.show', $acara->slug) }}"
                                   class="inline-flex items-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-full transition-all shadow-md hover:shadow-lg">
                                    Lihat Detail →
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination (Laravel default tailwind) -->
                <div class="mt-12 flex justify-center">
                    {{ $acaras->links('vendor.pagination.tailwind-modern') }}
                </div>

            @else
                <div class="bg-white/80 rounded-3xl border border-dashed border-emerald-200 p-12 text-center text-slate-500">
                    <p class="text-2xl font-medium mb-3">Belum ada agenda mendatang</p>
                    <p class="text-base">Pantau terus atau hubungi takmir untuk update terbaru</p>
                </div>
            @endif

            <!-- Back to Home -->
            <div class="text-center mt-12">
                <a href="{{ route('home') }}" class="text-emerald-700 hover:text-emerald-800 font-medium inline-flex items-center gap-2">
                    ← Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection