        {{-- === LAYANAN MASJID === --}}
        <section class="py-16 relative overflow-hidden bg-pattern-islamic">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="mx-auto mb-8 max-w-4xl text-center sm:mb-12">
                    @include('masjid.mrj.guest.components.section-heading', [
                        'badgeIcon' => '🕌',
                        'badge' => 'Layanan Terpercaya',
                        'title' => 'Layanan Masjid',
                        'highlight' => 'Untuk Umat',
                        'description' => "Kami menyediakan berbagai layanan untuk memudahkan ibadah dan kebutuhan sosial jamaah."
                    ])
                </div>
                @php
                    $serviceAccents = [
                        [
                            'icon' => 'from-emerald-50 to-teal-50 text-emerald-700 shadow-emerald-100',
                            'line' => 'from-emerald-500 to-teal-400',
                            'link' => 'text-emerald-700',
                        ],
                        [
                            'icon' => 'from-yellow-50 to-orange-50 text-yellow-600 shadow-yellow-100',
                            'line' => 'from-yellow-400 to-orange-400',
                            'link' => 'text-yellow-600',
                        ],
                        [
                            'icon' => 'from-rose-50 to-pink-50 text-rose-600 shadow-rose-100',
                            'line' => 'from-rose-400 to-pink-400',
                            'link' => 'text-rose-600',
                        ],
                        [
                            'icon' => 'from-sky-50 to-cyan-50 text-sky-600 shadow-sky-100',
                            'line' => 'from-sky-400 to-cyan-400',
                            'link' => 'text-sky-600',
                        ],
                        [
                            'icon' => 'from-violet-50 to-purple-50 text-violet-600 shadow-violet-100',
                            'line' => 'from-violet-400 to-purple-400',
                            'link' => 'text-violet-600',
                        ],
                        [
                            'icon' => 'from-indigo-50 to-violet-50 text-indigo-600 shadow-indigo-100',
                            'line' => 'from-indigo-400 to-violet-400',
                            'link' => 'text-indigo-600',
                        ],
                        [
                            'icon' => 'from-amber-50 to-yellow-50 text-amber-600 shadow-amber-100',
                            'line' => 'from-amber-400 to-yellow-400',
                            'link' => 'text-amber-600',
                        ],
                        [
                            'icon' => 'from-teal-50 to-emerald-50 text-teal-700 shadow-teal-100',
                            'line' => 'from-teal-500 to-emerald-400',
                            'link' => 'text-teal-700',
                        ],
                    ];
                @endphp

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 lg:gap-7">
                    @forelse($layanans as $l)
                        @php
                            $accent = $serviceAccents[$loop->index % count($serviceAccents)];
                        @endphp

                        <div class="service-card group relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 shadow-xl shadow-emerald-900/5 backdrop-blur-xl transition-all duration-500 hover:-translate-y-2 hover:bg-white hover:shadow-2xl hover:shadow-emerald-200/40">
                            
                            {{-- Soft glow --}}
                            <div class="pointer-events-none absolute -top-16 left-1/2 h-36 w-36 -translate-x-1/2 rounded-full bg-emerald-100/60 blur-3xl transition duration-500 group-hover:bg-emerald-200/70"></div>
                            <div class="pointer-events-none absolute -bottom-16 -right-16 h-36 w-36 rounded-full bg-cyan-100/60 blur-3xl"></div>

                            {{-- Content --}}
                            <div class="relative z-10 flex min-h-[260px] flex-col items-center justify-center px-6 py-8 text-center sm:min-h-[280px] lg:px-7 lg:py-9">
                                
                                <div class="mb-7 flex h-20 w-20 items-center justify-center rounded-[1.6rem] bg-gradient-to-br {{ $accent['icon'] }} text-4xl shadow-xl ring-1 ring-white/80 transition-all duration-500 group-hover:-translate-y-1 group-hover:scale-110 group-hover:rotate-3">
                                    {{ $l->icon ?? '🕌' }}
                                </div>

                                <h3 class="mb-4 text-xl font-black leading-tight text-slate-950 transition duration-300 group-hover:text-emerald-700">
                                    {{ $l->judul }}
                                </h3>

                                <p class="mx-auto max-w-[220px] text-sm leading-7 text-slate-600">
                                    {{ Str::limit(strip_tags($l->deskripsi ?? ''), 105) }}
                                </p>

                                <div class="mt-7 inline-flex items-center justify-center gap-2 text-xs font-black text-emerald-700 transition-all duration-300 group-hover:translate-x-1">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white shadow-md shadow-emerald-500/20">
                                        →
                                    </span>
                                    <span>Lihat detail</span>
                                </div>
                            </div>

                            {{-- Bottom color accent --}}
                            <div class="absolute inset-x-0 bottom-0 h-1.5 bg-gradient-to-r {{ $accent['line'] }} transition-all duration-500 group-hover:h-2"></div>
                        </div>
                    @empty
                        @foreach([
                            ['icon' => '📖', 'judul' => 'Pendidikan', 'deskripsi' => 'TPA, Tahfidz, dan Kajian Rutin untuk semua usia.'],
                            ['icon' => '🤝', 'judul' => 'Sosial Umat', 'deskripsi' => 'Zakat, Sedekah, dan Santunan untuk Yatim & Dhuafa.'],
                            ['icon' => '🩸', 'judul' => 'Kesehatan', 'deskripsi' => 'Donor Darah dan Cek Kesehatan Gratis untuk jamaah.'],
                            ['icon' => '💧', 'judul' => 'Wakaf & Infaq', 'deskripsi' => 'Renovasi Masjid dan Program Wakaf untuk kemaslahatan umat.'],
                            ['icon' => '🕌', 'judul' => 'Kajian Rutin', 'deskripsi' => 'Pengajian Umum, Tafsir, dan Hadits untuk menambah ilmu.'],
                            ['icon' => '🧕', 'judul' => 'Kajian Muslimah', 'deskripsi' => 'Pengajian Akhwat dan Tahsin untuk kaum muslimah.'],
                            ['icon' => '🌙', 'judul' => 'Ramadhan Spesial', 'deskripsi' => 'Tarawih, Tadarus, I’tikaf, dan Sahur Bersama.'],
                            ['icon' => '🏛️', 'judul' => 'Fasilitas', 'deskripsi' => 'Aula, parkir luas, dan perpustakaan untuk jamaah.'],
                        ] as $index => $item)
                            @php
                                $accent = $serviceAccents[$index % count($serviceAccents)];
                            @endphp

                            <div class="service-card group relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/90 shadow-xl shadow-emerald-900/5 backdrop-blur-xl transition-all duration-500 hover:-translate-y-2 hover:bg-white hover:shadow-2xl hover:shadow-emerald-200/40">
                                
                                <div class="pointer-events-none absolute -top-16 left-1/2 h-36 w-36 -translate-x-1/2 rounded-full bg-emerald-100/60 blur-3xl transition duration-500 group-hover:bg-emerald-200/70"></div>
                                <div class="pointer-events-none absolute -bottom-16 -right-16 h-36 w-36 rounded-full bg-cyan-100/60 blur-3xl"></div>

                                <div class="relative z-10 flex min-h-[260px] flex-col items-center justify-center px-6 py-8 text-center sm:min-h-[280px] lg:px-7 lg:py-9">
                                    
                                    <div class="mb-7 flex h-20 w-20 items-center justify-center rounded-[1.6rem] bg-gradient-to-br {{ $accent['icon'] }} text-4xl shadow-xl ring-1 ring-white/80 transition-all duration-500 group-hover:-translate-y-1 group-hover:scale-110 group-hover:rotate-3">
                                        {{ $item['icon'] }}
                                    </div>

                                    <h3 class="mb-4 text-xl font-black leading-tight text-slate-950 transition duration-300 group-hover:text-emerald-700">
                                        {{ $item['judul'] }}
                                    </h3>

                                    <p class="mx-auto max-w-[220px] text-sm leading-7 text-slate-600">
                                        {{ $item['deskripsi'] }}
                                    </p>

                                    {{-- <div class="mt-7 inline-flex items-center justify-center gap-2 text-xs font-black text-emerald-700 transition-all duration-300 group-hover:translate-x-1">
                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-600 text-white shadow-md shadow-emerald-500/20">
                                            →
                                        </span>
                                        <span>Lihat detail</span>
                                    </div> --}}
                                </div>

                                <div class="absolute inset-x-0 bottom-0 h-1.5 bg-gradient-to-r {{ $accent['line'] }} transition-all duration-500 group-hover:h-2"></div>
                            </div>
                        @endforeach
                    @endforelse
                </div>
            </div>
        </section>
