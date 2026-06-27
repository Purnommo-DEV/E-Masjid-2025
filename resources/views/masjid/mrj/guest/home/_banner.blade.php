        {{-- === SLIDER BANNER 3 KARTU (DB + SERVICE) === --}}
        <section class="pb-12 -mt-6">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">

                @php
                    // supaya kode bawah tetap pakai variabel $pages
                    $pages = $banner ?? [];
                    $totalPages = count($pages);
                @endphp

                @if($totalPages > 0)
                    <div id="bannerCarousel" class="relative">
                        <div class="rounded-[2.25rem] bg-gradient-to-r from-emerald-100/50 via-teal-50/70 to-cyan-100/50 p-6 lg:p-8 shadow-[0_20px_50px_-12px_rgba(5,150,105,0.15)] border border-white/50 bg-pattern-islamic">
                            <div class="overflow-hidden rounded-[1.7rem]">
                                <div class="banner-track flex transition-transform duration-700 ease-out snap-x snap-mandatory">
                                    @foreach($pages as $pageBanners)
                                        <div class="banner-page w-full shrink-0 snap-start px-4 lg:px-8">
                                            <div class="grid gap-5 lg:gap-8 lg:grid-cols-3">
                                                @foreach($pageBanners as $banner)
                                                    <div class="relative rounded-3xl overflow-hidden bg-emerald-700 text-white shadow-xl shadow-emerald-900/40 h-full min-h-[250px] flex flex-col">
                                                        <img
                                                            src="{{ $banner['image'] }}"
                                                            alt="{{ $banner['title'] }}"
                                                            class="absolute inset-0 w-full h-full object-cover"
                                                        >
                                                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-900/80 via-emerald-900/70 to-teal-900/70"></div>

                                                        <div class="relative p-5 sm:p-7 flex flex-col justify-between h-full">
                                                            <div class="space-y-2">
                                                                <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/10 text-[11px] backdrop-blur-sm border border-white/20">
                                                                    <span class="mr-1.5">🕌</span> Informasi Masjid
                                                                </div>
                                                                <h3 class="text-lg sm:text-xl font-semibold leading-snug">
                                                                    {{ $banner['title'] }}
                                                                </h3>
                                                                @if(!empty($banner['subtitle']))
                                                                    <p class="text-[12px] sm:text-[13px] text-emerald-50/90">
                                                                        {{ $banner['subtitle'] }}
                                                                    </p>
                                                                @endif
                                                            </div>
                                                            <div class="mt-4 flex items-center justify-between text-[11px]">
                                                                @if(!empty($banner['note']))
                                                                    <span class="text-emerald-50/90">
                                                                        {{ $banner['note'] }}
                                                                    </span>
                                                                @else
                                                                    <span class="text-emerald-50/70">&nbsp;</span>
                                                                @endif

                                                                @if(!empty($banner['button']) && !empty($banner['url']))
                                                                    <a href="{{ $banner['url'] }}"
                                                                       class="btn btn-xs bg-white/95 text-emerald-800 border-none hover:bg-emerald-50 rounded-full px-4">
                                                                        {{ $banner['button'] }}
                                                                    </a>
                                                                @else
                                                                    <a href="#acara"
                                                                       class="btn btn-xs bg-white/95 text-emerald-800 border-none hover:bg-emerald-50 rounded-full px-4">
                                                                        Lihat Detail
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- indikator titik per halaman --}}
                        <div class="flex justify-center gap-2 mt-4">
                            @foreach($pages as $i => $page)
                                <button
                                    type="button"
                                    class="banner-dot w-2.5 h-2.5 rounded-full bg-emerald-200 transition-all duration-200"
                                    data-index="{{ $i }}">
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>

