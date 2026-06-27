@extends('masjid.master-guest')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-emerald-50/30 to-teal-50/20 py-12 lg:py-20">
        <div class="container mx-auto px-5 lg:px-8 max-w-6xl">
            <div class="text-center mb-12 lg:mb-16">
                <span class="inline-flex items-center px-4 py-2 rounded-full bg-amber-100 text-amber-800 text-sm font-semibold mb-5">
                    PENGUMUMAN MASJID
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-slate-900 mb-5 tracking-tight">
                    Informasi Resmi
                    <span class="block text-emerald-700 mt-2">{{ profil('nama') ?? 'Masjid' }}</span>
                </h1>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    Kabar penting untuk jamaah, warga, dan masyarakat sekitar.
                </p>
            </div>

            <div class="grid md:grid-cols-2 gap-6 lg:gap-8">
                @forelse($pengumumans as $item)
                    <article class="bg-white rounded-2xl border border-amber-100 shadow-md hover:shadow-xl hover:border-amber-300 transition-all duration-300 overflow-hidden">
                        <div class="p-6 lg:p-7">
                            <div class="flex flex-wrap items-center gap-3 mb-4">
                                <span class="inline-flex px-3 py-1 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold uppercase">
                                    {{ $item->tipe ?? 'info' }}
                                </span>
                                <span class="text-xs text-slate-500">
                                    {{ $item->created_at?->translatedFormat('d F Y') ?? 'Baru saja' }}
                                </span>
                            </div>

                            <h2 class="text-xl lg:text-2xl font-bold text-slate-900 mb-3 leading-tight">
                                {{ $item->judul }}
                            </h2>

                            <p class="text-slate-600 leading-relaxed line-clamp-3 mb-6">
                                {{ Str::limit(strip_tags($item->isi ?? ''), 160) }}
                            </p>

                            @if($item->mulai || $item->selesai)
                                <div class="text-sm text-slate-500 mb-6">
                                    Berlaku:
                                    {{ $item->mulai?->translatedFormat('d M Y H:i') ?? 'Sekarang' }}
                                    -
                                    {{ $item->selesai?->translatedFormat('d M Y H:i') ?? 'selesai diumumkan' }}
                                </div>
                            @endif

                            <a href="{{ route('pengumuman.show', $item->public_identifier) }}"
                               class="inline-flex items-center justify-between w-full px-5 py-3 bg-amber-50 hover:bg-amber-100 text-amber-800 font-semibold rounded-xl transition">
                                <span>Baca Pengumuman</span>
                                <span aria-hidden="true">-&gt;</span>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 bg-white rounded-3xl border-2 border-dashed border-amber-200 p-12 text-center shadow-sm">
                        <h2 class="text-2xl font-bold text-slate-800 mb-3">Belum Ada Pengumuman Aktif</h2>
                        <p class="text-slate-500 max-w-md mx-auto mb-8">
                            Pengumuman terbaru akan tampil di halaman ini setelah dipublikasikan oleh admin masjid.
                        </p>
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center px-6 py-3 bg-emerald-100 text-emerald-700 rounded-xl font-semibold hover:bg-emerald-200 transition">
                            Kembali ke Beranda
                        </a>
                    </div>
                @endforelse
            </div>

            @if($pengumumans->hasPages())
                <div class="mt-12">
                    {{ $pengumumans->links('vendor.pagination.tailwind-modern') }}
                </div>
            @endif

            <div class="text-center mt-12 pt-6 border-t border-emerald-100">
                <a href="{{ route('home') }}" class="inline-flex items-center gap-2 text-emerald-700 hover:text-emerald-800 font-medium">
                    <span aria-hidden="true">&lt;-</span>
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
@endsection
