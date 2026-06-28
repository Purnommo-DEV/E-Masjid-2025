@extends('masjid.master-guest')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 py-12 lg:py-20">
        <div class="container mx-auto px-5 lg:px-8 max-w-5xl">
            <article class="bg-white rounded-3xl border border-amber-100 shadow-xl overflow-hidden">
                <header class="px-6 lg:px-10 py-8 lg:py-10 border-b border-amber-100 bg-amber-50/50">
                    <div class="flex flex-wrap items-center gap-3 mb-5">
                        <span class="inline-flex px-3 py-1 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold uppercase">
                            {{ $pengumuman->tipe ?? 'info' }}
                        </span>
                        <span class="text-sm text-slate-500">
                            {{ $pengumuman->created_at?->translatedFormat('l, d F Y') ?? 'Baru saja' }}
                        </span>
                    </div>

                    <h1 class="text-3xl lg:text-5xl font-extrabold text-slate-900 leading-tight mb-5">
                        {{ $pengumuman->judul }}
                    </h1>

                    @if($pengumuman->mulai || $pengumuman->selesai)
                        <p class="text-sm lg:text-base text-slate-600">
                            Berlaku:
                            {{ $pengumuman->mulai?->translatedFormat('d M Y H:i') ?? 'Sekarang' }}
                            -
                            {{ $pengumuman->selesai?->translatedFormat('d M Y H:i') ?? 'selesai diumumkan' }}
                        </p>
                    @endif
                </header>

                <div class="px-6 lg:px-10 py-8 lg:py-10">
                    <div class="prose prose-emerald max-w-none text-slate-800 leading-relaxed">
                        {!! nl2br(e($pengumuman->isi ?? '')) !!}
                    </div>

                    <div class="mt-10 flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('pengumuman.index') }}"
                           class="inline-flex items-center justify-center px-6 py-3 bg-amber-50 hover:bg-amber-100 text-amber-800 rounded-xl font-semibold transition">
                            <span aria-hidden="true">&lt;-</span>
                            <span class="ml-2">Daftar Pengumuman</span>
                        </a>
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($pengumuman->judul . ' - ' . url()->current()) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center justify-center px-6 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold transition">
                            Bagikan via WhatsApp
                        </a>
                    </div>
                </div>
            </article>

            @if($related->isNotEmpty())
                <section class="mt-12">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">Pengumuman Lainnya</h2>
                    <div class="grid md:grid-cols-2 gap-6">
                        @foreach($related as $item)
                            <a href="{{ route('pengumuman.show', $item->public_identifier) }}"
                               class="block bg-white rounded-2xl border border-amber-100 p-6 shadow-sm hover:shadow-lg hover:border-amber-300 transition">
                                <span class="text-xs text-amber-700 font-semibold uppercase">{{ $item->tipe ?? 'info' }}</span>
                                <h3 class="text-lg font-bold text-slate-900 mt-2 mb-2 line-clamp-2">{{ $item->judul }}</h3>
                                <p class="text-sm text-slate-600 line-clamp-2">
                                    {{ Str::limit(strip_tags($item->isi ?? ''), 110) }}
                                </p>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
@endsection
