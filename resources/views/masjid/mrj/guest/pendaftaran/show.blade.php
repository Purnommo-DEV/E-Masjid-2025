@extends('masjid.master-guest')

@section('title', $berita->judul ?? 'Detail Berita')

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

            <!-- Gambar Utama -->
            <div class="relative mb-10 lg:mb-12 rounded-3xl overflow-hidden shadow-2xl border border-emerald-100/70 group">
                @if($berita->hasMedia('gambar') )
                    <img src="{{ $berita->gambar_url ?? '/storage/404.jpg' }}"
                         alt="{{ $berita->judul }}"
                         class="w-full h-auto max-h-[600px] object-cover transition-transform duration-700 group-hover:scale-105">
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

            <!-- Konten Utama + Sidebar -->
            <div class="grid lg:grid-cols-3 gap-8 lg:gap-12">
                <!-- Isi Berita -->
                <div class="lg:col-span-2">
                    <div class="bg-white/90 backdrop-blur-sm rounded-3xl p-8 lg:p-12 shadow-xl border border-emerald-100/70">
                        <div class="text-slate-800 leading-relaxed space-y-6 max-w-none">
                            {!! $berita->isi !!}
                        </div>

                        <!-- Tags jika ada -->
                        @if($berita->tags)
                            <div class="mt-10 flex flex-wrap gap-3">
                                @foreach(explode(',', $berita->tags) as $tag)
                                    <span class="px-4 py-2 rounded-full bg-emerald-50 text-emerald-700 text-sm">
                                        {{ trim($tag) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-2xl p-6 lg:p-8 border border-emerald-100 shadow-md lg:sticky lg:top-6">
                        <h3 class="text-xl font-bold text-slate-900 mb-6">Bagikan Berita Ini</h3>
                        
                        <!-- Share Buttons (ukuran kecil) -->
                        <div class="flex flex-wrap gap-3 mb-10">
                            <a href="https://www.facebook.com/sharer/sharer.php?u={{ url()->current() }}" target="_blank" rel="noopener noreferrer"
                               class="flex-1 flex items-center justify-center gap-2 bg-[#1877f2] hover:bg-[#166fe5] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M24 12c0-6.627-5.373-12-12-12S0 5.373 0 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.234 2.686.234v2.953h-1.517c-1.49 0-1.955.925-1.955 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 22.954 24 17.99 24 12z"/>
                                </svg>
                                FB
                            </a>
                            <a href="https://twitter.com/intent/tweet?url={{ url()->current() }}&text={{ urlencode($berita->judul) }}" target="_blank" rel="noopener noreferrer"
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

                        <!-- Related Berita -->
                        @if(!empty($related))
                            <h3 class="text-xl font-bold text-slate-900 mb-6 mt-10">Berita Terkait</h3>
                            @foreach($related as $item)
                                <a href="{{ $item['url'] }}" class="block mb-6 group">
                                    <div class="flex gap-4">
                                        <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden">
                                            <img src="{{ $item['gambar'] ?? '/storage/404.jpg' }}" alt="{{ $item['judul'] }}" class="w-full h-full object-cover">
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