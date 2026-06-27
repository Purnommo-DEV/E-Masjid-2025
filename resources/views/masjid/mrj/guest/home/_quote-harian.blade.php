        {{-- === QUOTE HARIAN === --}}
        <section class="py-10">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="rounded-3xl bg-gradient-to-r from-emerald-700 via-teal-700 to-emerald-600 text-white px-6 sm:px-8 py-8 sm:py-10 shadow-2xl relative overflow-hidden bg-pattern-islamic border border-white/10 hover:shadow-emerald-500/20 hover:scale-[1.01] transition-all duration-350">
                    <p class="text-xs uppercase tracking-widest text-emerald-100/90 mb-3">Pengingat Harian</p>

                    <!-- Container quote -->
                    <div id="quote-container" class="relative min-h-[180px] sm:min-h-[140px] lg:min-h-[120px] overflow-hidden">
                        @if($quoteHarianList->isNotEmpty())
                            <div class="quote-item absolute inset-0 opacity-100 transition-all duration-800 ease-in-out flex flex-col">
                                <h3 class="font-semibold text-base sm:text-lg lg:text-xl mt-1 leading-tight">
                                    {{ $quoteHarianList->first()->title }}
                                </h3>
                                <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed overflow-y-auto flex-1 pr-1 sm:pr-2">
                                    {{ $quoteHarianList->first()->text }}
                                </div>
                            </div>
                        @else
                            <div class="quote-item absolute inset-0 opacity-100 transition-all duration-800 ease-in-out flex flex-col">
                                <h3 class="font-semibold text-base sm:text-lg lg:text-xl mt-1 leading-tight">
                                    Pengingat Harian
                                </h3>
                                <div class="quote-text mt-3 text-base sm:text-lg leading-relaxed overflow-y-auto flex-1 pr-1 sm:pr-2">
                                    “Sesungguhnya bersama kesulitan ada kemudahan.” — QS. Al-Insyirah: 6
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </section>

