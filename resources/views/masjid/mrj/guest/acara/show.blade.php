@extends('masjid.master-guest')

@section('title', $acara->judul ?? 'Detail Acara')

@section('content')
    <!-- Subtle Islamic pattern background -->
    <div class="min-h-screen bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50 relative overflow-hidden py-10 lg:py-20">
        <!-- SVG pattern subtle -->
        <div class="absolute inset-0 opacity-5 pointer-events-none">
            <svg width="100%" height="100%" xmlns="http://www.w3.org/2000/svg">
                <defs>
                    <pattern id="islamic-pattern" width="100" height="100" patternUnits="userSpaceOnUse">
                        <path d="M50 0 L93.3 25 L93.3 75 L50 100 L6.7 75 L6.7 25 Z" fill="none" stroke="#10b981" stroke-width="1" opacity="0.3"/>
                        <circle cx="50" cy="50" r="10" fill="none" stroke="#059669" stroke-width="1" opacity="0.2"/>
                    </pattern>
                </defs>
                <rect width="100%" height="100%" fill="url(#islamic-pattern)"/>
            </svg>
        </div>

        <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-6xl relative z-10">
            
            <!-- Hero Header -->
            <div class="text-center mb-12 lg:mb-16">
                <span class="inline-flex items-center px-6 py-3 rounded-full bg-gradient-to-r from-emerald-100 to-teal-100 text-emerald-800 text-base font-semibold mb-6 shadow-sm">
                    {{ $acara->kategoris->pluck('nama')->first() ?? 'Kajian & Acara' }}
                </span>
                <h1 class="text-4xl lg:text-5xl xl:text-6xl font-extrabold text-slate-900 mb-6 leading-tight tracking-tight">
                    {{ $acara->judul }}
                </h1>
                <div class="flex flex-wrap justify-center items-center gap-4 text-lg lg:text-xl text-slate-700">
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 text-2xl">🗓️</span>
                        {{ $acara->mulai?->translatedFormat('l, d F Y') ?? 'Segera' }}
                    </div>
                    <span class="hidden sm:block text-emerald-400">•</span>
                    <div class="flex items-center gap-2">
                        <span class="text-emerald-600 text-2xl">🕒</span>
                        {{ $acara->mulai?->format('H:i') ?? '-' }}
                        @if($acara->selesai) – {{ $acara->selesai->format('H:i') }} @endif
                    </div>
                </div>
            </div>

            <!-- Poster -->
            <div class="relative mb-12 lg:mb-16 rounded-3xl overflow-hidden shadow-2xl border-2 border-emerald-100/50 group">
                @if($acara->hasMedia('poster') || $acara->poster_url)
                    <img src="{{ $acara->poster_url ?? '/storage/404.jpg' }}"
                         alt="{{ $acara->judul }}"
                         class="w-full h-auto max-h-[600px] object-cover transition-transform duration-700 group-hover:scale-105">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent"></div>
                @else
                    <div class="bg-gradient-to-br from-gray-100 to-teal-50 h-80 lg:h-[600px] flex items-center justify-center">
                        <div class="text-center">
                            <span class="text-7xl text-emerald-300 mb-4 block">🕌</span>
                            <span class="text-gray-500 text-2xl font-medium">Poster acara belum tersedia</span>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Deskripsi -->
            <div class="mb-12 lg:mb-20 bg-white/90 backdrop-blur-sm rounded-3xl p-8 lg:p-12 shadow-xl border border-emerald-100/70">
                <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-8 pb-4 border-b-2 border-emerald-200 inline-block">
                    Deskripsi Acara
                </h2>
                
                @if($acara->deskripsi && trim(strip_tags($acara->deskripsi)) !== '')
                    <div class="text-slate-800 leading-relaxed space-y-6">
                        {!! $acara->deskripsi !!}
                    </div>
                @else
                    <div class="text-center py-16 text-slate-500 italic bg-slate-50/50 rounded-2xl">
                        <p class="text-xl">Deskripsi acara belum diisi. Silakan hubungi takmir untuk info lebih lanjut.</p>
                    </div>
                @endif
            </div>

            <!-- Info + Share + Maps -->
            <div class="grid lg:grid-cols-2 gap-8 mb-12 lg:mb-20">
                <div class="bg-gradient-to-br from-white to-emerald-50/40 rounded-3xl p-8 lg:p-10 shadow-lg border border-emerald-100">
                    <h3 class="text-2xl font-bold text-slate-900 mb-8 flex items-center gap-3">
                        <span class="text-3xl">ℹ️</span> Informasi Acara
                    </h3>
                    <div class="space-y-8 text-slate-800">
                        <div class="flex items-start gap-4">
                            <span class="text-4xl text-emerald-600 flex-shrink-0">👤</span>
                            <div>
                                <span class="block text-lg font-semibold">Pemateri</span>
                                <p class="text-base">{{ $acara->pemateri ?? 'Belum ditentukan' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-4xl text-rose-600 flex-shrink-0">📍</span>
                            <div>
                                <span class="block text-lg font-semibold">Lokasi</span>
                                <p class="text-base">{{ $acara->lokasi ?? 'Masjid' }}</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-4">
                            <span class="text-4xl text-emerald-600 flex-shrink-0">🗓️</span>
                            <div>
                                <span class="block text-lg font-semibold">Jadwal</span>
                                <p class="text-base">
                                    {{ $acara->mulai?->translatedFormat('l, d F Y • H:i') ?? 'Belum ditentukan' }}
                                    @if($acara->selesai) – {{ $acara->selesai->format('H:i') }} WIB @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Share & Maps Card -->
                <div class="bg-gradient-to-br from-white to-teal-50/40 rounded-3xl p-8 lg:p-10 shadow-lg border border-teal-100 flex flex-col">
                    <h3 class="text-2xl font-bold text-slate-900 mb-6">Bagikan & Lokasi</h3>
                    
                    <!-- Share Buttons -->
                    <div class="flex flex-wrap gap-3 mb-8">
                        <!-- Facebook -->
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ url()->current() }}" target="_blank" rel="noopener noreferrer"
                           class="flex-1 flex items-center justify-center gap-2 bg-[#1877f2] hover:bg-[#166fe5] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M24 12c0-6.627-5.373-12-12-12S0 5.373 0 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.234 2.686.234v2.953h-1.517c-1.49 0-1.955.925-1.955 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 22.954 24 17.99 24 12z"/>
                            </svg>
                            Facebook
                        </a>

                        <!-- X / Twitter -->
                        <a href="https://twitter.com/intent/tweet?url={{ url()->current() }}&text={{ urlencode($acara->judul) }}" target="_blank" rel="noopener noreferrer"
                           class="flex-1 flex items-center justify-center gap-2 bg-[#000000] hover:bg-[#1a1a1a] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                            </svg>
                            X
                        </a>

                        <!-- WhatsApp -->
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($acara->judul . ' - ' . url()->current()) }}" target="_blank" rel="noopener noreferrer"
                           class="flex-1 flex items-center justify-center gap-2 bg-[#25D366] hover:bg-[#20b058] text-white px-3 py-2.5 rounded-lg text-sm font-medium transition-all shadow hover:shadow-md hover:scale-105">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.198-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.297-.497.099-.198.05-.371-.025-.52-.074-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                            </svg>
                            WhatsApp
                        </a>
                    </div>

                    <!-- Google Maps Placeholder -->
                    @if(profil('latitude') && profil('longitude')) <!-- asumsi kolom lat/long ada di model -->
                        <div class="rounded-xl overflow-hidden shadow-md border border-teal-100 mt-auto">
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d{{ profil('latitude') }}!2d{{ profil('longitude') }}!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2z{{ $acara->latitude }}%2C{{ $acara->longitude }}!5e0!3m2!1sen!2sid!4v{{ now()->timestamp }}!5m2!1sen!2sid" 
                                    width="100%" height="250" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    @else
                        <div class="text-center py-8 text-slate-500 italic mt-auto">
                            Lokasi belum memiliki koordinat. Hubungi panitia untuk petunjuk.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Related Acara -->
            @if(!empty($related))
                <div class="mt-16 lg:mt-24">
                    <h2 class="text-3xl lg:text-4xl font-bold text-slate-900 mb-10 lg:mb-12 text-center">
                        Acara Mendatang Lainnya
                    </h2>
                    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
                        @foreach($related as $item)
                            <div class="group bg-white rounded-3xl border border-emerald-100/50 shadow-lg hover:shadow-2xl hover:border-emerald-300 transition-all duration-500 overflow-hidden transform hover:-translate-y-3">
                                <div class="h-56 overflow-hidden relative">
                                    <img src="{{ $item['image'] ?? '/storage/404.jpg' }}"
                                         alt="{{ $item['title'] }}"
                                         class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
                                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                                </div>
                                <div class="p-8">
                                    <div class="flex flex-wrap items-center justify-between mb-5 gap-3">
                                        <span class="inline-flex px-5 py-2 rounded-full bg-emerald-100 text-emerald-800 text-sm font-medium">
                                            {{ $item['kategori'] }}
                                        </span>
                                        <span class="text-sm font-semibold text-slate-600 bg-slate-100 px-4 py-2 rounded-full">
                                            {{ $item['tanggal_label'] }}
                                        </span>
                                    </div>

                                    <h3 class="text-2xl font-bold text-slate-900 mb-4 group-hover:text-emerald-700 transition-colors line-clamp-2">
                                        {{ $item['title'] }}
                                    </h3>

                                    <p class="text-slate-600 mb-6 line-clamp-3">
                                        {{ $item['excerpt'] }}
                                    </p>

                                    <a href="{{ route('acara.show', $item['slug']) }}"
                                       class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-full transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                                        Lihat Selengkapnya →
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Back Button -->
            <div class="text-center mt-16 lg:mt-24">
                <a href="{{ route('acara.index') }}"
                   class="inline-flex items-center gap-3 px-10 py-5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-lg font-semibold rounded-full shadow-xl hover:shadow-2xl transition-all transform hover:scale-105">
                    ← Kembali ke Daftar Acara
                </a>
            </div>
        </div>
    </div>
@endsection