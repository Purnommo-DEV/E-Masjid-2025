{{-- === QUOTE HARIAN / PENGINGAT HARIAN === --}}
<section class="relative overflow-hidden py-8 sm:py-12">
    <div class="container relative mx-auto px-4 sm:px-6 lg:px-8">
        <div class="daily-reminder-card relative mx-auto max-w-7xl overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-800 to-teal-500 px-6 py-7 text-white shadow-2xl shadow-emerald-900/20 sm:px-8 sm:py-9 lg:px-12 lg:py-10">

            {{-- Soft background glow --}}
            <div class="pointer-events-none absolute inset-0">
                <div class="absolute -left-16 bottom-0 h-52 w-52 rounded-full bg-lime-200/10 blur-3xl"></div>
                <div class="absolute right-0 top-0 h-72 w-72 rounded-full bg-cyan-200/12 blur-3xl"></div>
                <div class="absolute right-20 bottom-0 h-72 w-72 rounded-full bg-yellow-100/10 blur-3xl"></div>
            </div>

{{-- Top-right moon + stars ornament --}}
<div class="pointer-events-none absolute inset-y-0 right-0 hidden w-[42%] overflow-hidden md:block"
     style="-webkit-mask-image: linear-gradient(to left, black 58%, transparent 100%); mask-image: linear-gradient(to left, black 58%, transparent 100%);">

    <svg viewBox="0 0 420 360"
         fill="none"
         xmlns="http://www.w3.org/2000/svg"
         class="absolute -right-6 top-3 h-[300px] w-[350px] text-white/20">

        <defs>
            {{-- Mask untuk sabit yang lebih presisi --}}
            <mask id="crescentMask">
                <rect width="420" height="360" fill="black"/>
                <circle cx="286" cy="108" r="44" fill="white"/>
                <circle cx="302" cy="96" r="44" fill="black"/>
            </mask>
        </defs>

        {{-- Lingkaran ornament --}}
        <circle cx="282" cy="112" r="88" stroke="currentColor" stroke-width="1.3" opacity="0.24"/>
        <circle cx="282" cy="112" r="128" stroke="currentColor" stroke-width="1" opacity="0.14"/>

        {{-- Crescent presisi --}}
        <circle cx="286" cy="108" r="44" fill="currentColor" mask="url(#crescentMask)" opacity="0.55"/>

        {{-- Glow sabit --}}
        <circle cx="286" cy="108" r="62" fill="currentColor" opacity="0.06"/>

        {{-- Stars --}}
        <path d="M184 96L189 106L200 111L189 116L184 126L179 116L168 111L179 106L184 96Z" fill="currentColor" opacity="0.55"/>
        <path d="M338 228L343 237L353 242L343 247L338 256L333 247L323 242L333 237L338 228Z" fill="currentColor" opacity="0.36"/>
        <path d="M380 132L384 139L392 143L384 147L380 154L376 147L368 143L376 139L380 132Z" fill="currentColor" opacity="0.28"/>
        <path d="M236 262L240 269L248 273L240 277L236 284L232 277L224 273L232 269L236 262Z" fill="currentColor" opacity="0.22"/>

        {{-- Arc lines --}}
        <path d="M86 344C86 246 148 166 232 166C316 166 378 246 378 344"
              stroke="currentColor"
              stroke-width="1.35"
              stroke-linecap="round"
              opacity="0.14"/>

        <path d="M128 344C128 270 171 212 232 212C293 212 336 270 336 344"
              stroke="currentColor"
              stroke-width="1.05"
              stroke-linecap="round"
              opacity="0.10"/>
    </svg>

    <div class="absolute right-36 top-44 h-1.5 w-1.5 rounded-full bg-yellow-100/25"></div>
    <div class="absolute right-20 bottom-24 h-1 w-1 rounded-full bg-white/25"></div>
    <div class="absolute right-52 bottom-14 h-1 w-1 rounded-full bg-cyan-100/20"></div>
</div>

            {{-- Bottom-right flowing lines --}}
            <div class="pointer-events-none absolute bottom-0 right-0 hidden w-[42%] md:block">
                <svg viewBox="0 0 500 220" xmlns="http://www.w3.org/2000/svg" class="h-full w-full">
                    <defs>
                        <linearGradient id="flowLine1" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0.00)" />
                            <stop offset="55%" stop-color="rgba(253,224,71,0.34)" />
                            <stop offset="100%" stop-color="rgba(255,255,255,0.10)" />
                        </linearGradient>
                        <linearGradient id="flowLine2" x1="0" y1="0" x2="1" y2="0">
                            <stop offset="0%" stop-color="rgba(255,255,255,0.00)" />
                            <stop offset="55%" stop-color="rgba(255,255,255,0.16)" />
                            <stop offset="100%" stop-color="rgba(255,255,255,0.05)" />
                        </linearGradient>
                    </defs>

                    <path d="M20 180C120 80 190 210 290 150C360 108 400 70 500 90"
                        stroke="url(#flowLine1)" stroke-width="2.2" fill="none" stroke-linecap="round"/>
                    <path d="M10 200C120 120 190 230 300 175C380 136 430 106 500 124"
                        stroke="url(#flowLine2)" stroke-width="2" fill="none" stroke-linecap="round"/>
                    <path d="M0 220C100 150 180 248 292 198C390 154 432 132 500 148"
                        stroke="url(#flowLine1)" stroke-width="1.8" fill="none" stroke-linecap="round"/>
                </svg>
            </div>

            {{-- Bottom-left abstract accent --}}
            <div class="pointer-events-none absolute bottom-6 left-2 hidden opacity-50 sm:block">
                <div class="relative h-24 w-36">
                    <span class="absolute left-0 top-8 h-12 w-8 rotate-[28deg] rounded-full bg-lime-300/35 blur-[1px]"></span>
                    <span class="absolute left-10 top-10 h-10 w-7 rotate-[40deg] rounded-full bg-cyan-200/20 blur-[1px]"></span>
                    <span class="absolute left-20 top-14 h-7 w-12 rotate-[-18deg] rounded-full bg-white/14 blur-[1px]"></span>
                    <span class="absolute bottom-0 left-0 h-px w-28 bg-white/12"></span>
                </div>
            </div>

            {{-- Content --}}
            <div class="relative z-10">
                <div class="max-w-4xl">
                    {{-- Badge --}}
                    <div class="mb-6 inline-flex items-center gap-3 rounded-full border border-white/12 bg-white/10 px-4 py-2 shadow-lg shadow-emerald-950/10 backdrop-blur-md sm:px-5 sm:py-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-yellow-200/15 text-yellow-100 ring-1 ring-yellow-100/20">
                            ✦
                        </span>
                        <span class="text-sm font-black uppercase tracking-[0.14em] text-white">
                            Pengingat Harian
                        </span>
                    </div>

                    <div class="flex gap-4 sm:gap-5">
                        <div class="hidden text-7xl font-black leading-none text-yellow-200/90 sm:block">
                            “
                        </div>

                        <div class="min-w-0 flex-1">
                            {{-- Container ini dipakai script lama --}}
                            <div id="quote-container" class="relative min-h-[120px] overflow-hidden sm:min-h-[136px] lg:min-h-[150px]">
                                @if($quoteHarianList->isNotEmpty())
                                    <div class="quote-item absolute inset-0 flex flex-col translate-y-0 scale-100 opacity-100 transition-all duration-700 ease-in-out">
                                        <div class="quote-text flex-1 overflow-y-auto pr-2 scroll-smooth">
                                            {{ $quoteHarianList->first()->text }}
                                        </div>
                                    </div>
                                @else
                                    <div class="quote-item absolute inset-0 flex flex-col translate-y-0 scale-100 opacity-100 transition-all duration-700 ease-in-out">
                                        <div class="quote-text flex-1 overflow-y-auto pr-2 scroll-smooth">
                                            “Malu adalah bagian dari iman. (HR. Bukhari No. 24; Muslim No. 36)”
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Bottom accent --}}
                <div class="mt-5 flex items-center gap-3 sm:ml-20">
                    <span class="h-px w-14 bg-yellow-200/55"></span>
                    <span class="h-2 w-2 rounded-full bg-yellow-300"></span>
                    <span class="h-px w-20 bg-white/15"></span>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .daily-reminder-card .quote-text {
        margin-top: 0 !important;
        max-height: 145px !important;
        font-size: clamp(1rem, 3vw, 2rem) !important;
        line-height: 1.38 !important;
        font-weight: 800 !important;
        letter-spacing: -0.025em !important;
        color: #ffffff !important;
    }

    @media (min-width: 1024px) {
        .daily-reminder-card .quote-text {
            font-size: 2.25rem !important;
            max-height: 155px !important;
        }
    }

    @media (max-width: 640px) {
        .daily-reminder-card .quote-text {
            font-size: 1.25rem !important;
            line-height: 1.4 !important;
        }
    }

    @media (max-width: 390px) {
        .daily-reminder-card .quote-text {
            font-size: 1.0rem !important;
            line-height: 1.45 !important;
        }
    }

    .daily-reminder-card .quote-text::-webkit-scrollbar {
        width: 4px;
    }

    .daily-reminder-card .quote-text::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.28);
        border-radius: 999px;
    }

    .daily-reminder-card .quote-text::-webkit-scrollbar-track {
        background: transparent;
    }
</style>