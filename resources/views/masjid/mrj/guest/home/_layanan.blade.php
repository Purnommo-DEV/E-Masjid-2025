        {{-- === LAYANAN MASJID === --}}
        <section class="py-16 relative overflow-hidden bg-pattern-islamic">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="text-center mb-12">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-medium mb-3">
                        Layanan Terpercaya
                    </span>
                    <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">
                        Layanan Masjid untuk Umat
                    </h2>
                    <p class="text-slate-600 max-w-3xl mx-auto">
                        Kami menyediakan berbagai layanan untuk memudahkan ibadah dan kebutuhan sosial jamaah
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 lg:gap-8">
                    @forelse($layanans as $l)
                        <div class="service-card group rounded-3xl bg-white/80 backdrop-blur-md border border-white/50 shadow-lg overflow-hidden hover:shadow-2xl hover:shadow-emerald-200/20">
                            <div class="p-8 lg:p-10 text-center">
                                <div class="service-icon-bg mx-auto mb-6 w-20 h-20 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-4xl shadow-md group-hover:shadow-xl transition">
                                    {{ $l->icon ?? '🕌' }}
                                </div>
                                <h3 class="text-xl font-bold text-slate-800 mb-4 group-hover:text-emerald-700 transition">
                                    {{ $l->judul }}
                                </h3>
                                <p class="text-sm text-slate-600 leading-relaxed line-clamp-4">
                                    {{ Str::limit(strip_tags($l->deskripsi ?? ''), 120) }}
                                </p>
                            </div>
                        </div>
                    @empty
                        ...
                    @endforelse
                </div>
            </div>
        </section>
