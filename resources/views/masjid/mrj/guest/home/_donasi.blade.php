{{-- === INFAQ ONLINE - MIRIP REFERENSI RESPONSIVE FIX === --}}
<section id="donasi" class="home-section relative overflow-hidden bg-gradient-to-br from-white via-emerald-50/40 to-cyan-50/60 py-12 sm:py-16 lg:py-20">
    
    {{-- Background Soft Decoration --}}
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-32 top-0 h-72 w-72 rounded-full bg-emerald-100/60 blur-3xl sm:h-96 sm:w-96"></div>
        <div class="absolute -right-32 top-10 h-72 w-72 rounded-full bg-cyan-100/70 blur-3xl sm:h-96 sm:w-96"></div>
        <div class="absolute bottom-0 left-1/3 h-64 w-64 rounded-full bg-yellow-100/50 blur-3xl sm:h-80 sm:w-80"></div>

        <div class="absolute left-0 top-10 hidden h-72 w-72 opacity-30 sm:block">
            <div class="h-full w-full rounded-full border border-yellow-200/60"></div>
        </div>

        <div class="absolute right-0 top-20 hidden h-72 w-72 opacity-30 sm:block">
            <div class="h-full w-full rounded-full border border-emerald-200/60"></div>
        </div>

        <div class="absolute bottom-24 right-0 hidden w-[360px] opacity-[0.08] lg:block">
            <svg viewBox="0 0 700 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M0 360H700V308H0V360Z" fill="#047857"/>
                <path d="M292 308V205C292 160 328 124 373 124C418 124 454 160 454 205V308H292Z" fill="#047857"/>
                <path d="M329 127C337 75 373 42 373 42C373 42 409 75 417 127H329Z" fill="#047857"/>
                <path d="M120 308V226C120 190 149 161 185 161C221 161 250 190 250 226V308H120Z" fill="#047857"/>
                <path d="M505 308V226C505 190 534 161 570 161C606 161 635 190 635 226V308H505Z" fill="#047857"/>
                <path d="M55 308V147H95V308H55Z" fill="#047857"/>
                <path d="M640 308V147H680V308H640Z" fill="#047857"/>
            </svg>
        </div>

        <div class="absolute left-4 bottom-10 hidden opacity-40 lg:block">
            <div class="relative h-44 w-44">
                <span class="absolute bottom-10 left-0 h-24 w-10 rotate-[28deg] rounded-full bg-emerald-300/60"></span>
                <span class="absolute bottom-20 left-12 h-20 w-9 rotate-[55deg] rounded-full bg-teal-300/50"></span>
                <span class="absolute bottom-5 left-24 h-16 w-8 rotate-[75deg] rounded-full bg-emerald-200/70"></span>
                <span class="absolute bottom-2 left-0 h-px w-40 rotate-[-12deg] bg-emerald-300/50"></span>
            </div>
        </div>
    </div>

    <div class="container relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

        {{-- Header --}}
        <div class="mx-auto mb-8 max-w-4xl text-center sm:mb-10">
            @include('masjid.mrj.guest.components.section-heading', [
                'badgeIcon' => '🤲',
                'badge' => 'Infaq Online',
                'title' => 'Berinfaq Jadi',
                'highlight' => 'Lebih Mudah',
                'description' => "Salurkan infaq terbaik Anda melalui transfer bank atau scan QRIS.<br class='hidden sm:block'>Setiap kebaikan yang Anda berikan akan menjadi <span class='font-black text-emerald-600'>amal jariyah</span> yang terus mengalir."
            ])
        </div>

        {{-- Optional Slider - Jangan Dihapus --}}
        @if($sliders->isNotEmpty())
            <div class="mb-10 lg:mb-20">
                <div id="infaqMotivasiSlider" class="relative overflow-hidden rounded-[1.5rem] border border-emerald-100/70 bg-white/80 p-2 shadow-xl shadow-emerald-900/5 backdrop-blur-xl sm:rounded-[2rem] sm:p-3">
                    <div class="infaq-slider-track flex transition-all duration-700 ease-in-out">
                        @foreach($sliders as $index => $slide)
                            <div class="w-full shrink-0">
                                <div class="flex flex-col items-start justify-between gap-4 rounded-[1.25rem] bg-gradient-to-r from-emerald-50 via-white to-cyan-50 p-4 sm:rounded-[1.5rem] sm:p-5 md:flex-row md:items-center">
                                    <div class="flex min-w-0 items-start gap-3 sm:gap-4">
                                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-yellow-100 to-emerald-100 text-xl sm:h-14 sm:w-14 sm:text-2xl">
                                            💝
                                        </div>
                                        <div class="min-w-0">
                                            <h3 class="line-clamp-2 text-sm font-black leading-snug text-slate-900 sm:text-base md:text-lg">
                                                {!! $slide->title !!}
                                            </h3>
                                            <p class="mt-1 line-clamp-2 text-xs leading-5 text-slate-500 sm:text-sm sm:leading-6">
                                                {!! $slide->subtitle !!}
                                            </p>
                                        </div>
                                    </div>

                                    <a href="{{ $slide->button_link ?? '#rekening' }}"
                                       class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-full bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:-translate-y-0.5 hover:bg-emerald-700 md:w-auto">
                                        <span>{{ $slide->button_text ?? 'Infaq Sekarang' }}</span>
                                        <span>→</span>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <button id="infaqSliderPrev" type="button" class="absolute left-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-100 bg-white/90 text-slate-600 shadow-md backdrop-blur transition hover:bg-emerald-50 sm:left-3 sm:h-9 sm:w-9">
                        ‹
                    </button>

                    <button id="infaqSliderNext" type="button" class="absolute right-2 top-1/2 flex h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-slate-100 bg-white/90 text-slate-600 shadow-md backdrop-blur transition hover:bg-emerald-50 sm:right-3 sm:h-9 sm:w-9">
                        ›
                    </button>

                    <div class="mt-3 flex justify-center gap-2">
                        @foreach($sliders as $index => $slide)
                            <button type="button"
                                    class="infaq-slider-dot h-1.5 w-1.5 rounded-full bg-emerald-300/50 transition-all duration-300 {{ $index === 0 ? '!w-5 bg-emerald-500' : '' }}"
                                    data-index="{{ $index }}"></button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        {{-- Main Payment Panel --}}
        <div id="rekening" class="relative rounded-[1.5rem] border border-emerald-100/80 bg-white/85 shadow-2xl shadow-emerald-900/10 backdrop-blur-xl sm:rounded-[2rem] lg:rounded-[2.3rem]">
            
            {{-- Floating Labels Desktop --}}
            <div class="pointer-events-none absolute left-10 top-0 hidden -translate-y-1/2 lg:block">
                <div class="inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-7 py-4 text-white shadow-xl shadow-emerald-700/25">
                    <span class="text-xl">🏦</span>
                    <span class="text-lg font-black uppercase tracking-wide">Transfer Bank</span>
                </div>
            </div>

            <div class="pointer-events-none absolute left-1/2 top-0 hidden -translate-y-1/2 lg:block">
                <div class="inline-flex items-center gap-3 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-7 py-4 text-white shadow-xl shadow-emerald-700/25">
                    <span class="text-xl">▦</span>
                    <span class="text-lg font-black uppercase tracking-wide">Scan QRIS</span>
                </div>
            </div>

            <div class="grid lg:grid-cols-2">
                
                {{-- Transfer Bank --}}
                <div class="relative p-4 pt-5 sm:p-8 lg:p-14 lg:pt-16">
                    <div class="mb-5 lg:hidden">
                        <div class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-4 py-2.5 text-white shadow-xl shadow-emerald-700/20 sm:gap-3 sm:px-5 sm:py-3">
                            <span class="text-base sm:text-lg">🏦</span>
                            <span class="text-sm font-black uppercase tracking-wide sm:text-base">Transfer Bank</span>
                        </div>
                    </div>

                    <p class="mb-5 text-sm leading-6 text-slate-600 sm:mb-6 sm:leading-7">
                        Salurkan infaq Anda melalui rekening resmi berikut:
                    </p>

                    <div class="rounded-[1.35rem] border border-slate-100 bg-white p-4 shadow-xl shadow-slate-900/5 sm:rounded-[1.7rem] sm:p-6">
                        <div class="flex flex-col gap-4 rounded-2xl bg-gradient-to-r from-white to-emerald-50/60 p-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex min-w-0 items-center gap-3 sm:gap-4">
                                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-white text-lg font-black text-blue-700 shadow-md ring-1 ring-slate-100 sm:h-16 sm:w-16 sm:text-xl">BSI
                                </div>
                                <div class="min-w-0">
                                    <h3 class="break-words text-base font-black leading-snug text-slate-900 sm:text-lg">
                                        {{ profil('bank_name') ?? 'Bank Syariah Indonesia (BSI)' }}
                                    </h3>
                                    <p class="mt-1 text-xs text-slate-500 sm:text-sm">
                                        Rekening Infaq
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 sm:mt-7">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-emerald-700 sm:text-xs sm:tracking-[0.18em]">
                                Nomor Rekening
                            </p>

                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
                                <p id="rekeningNum" class="infaq-account-number min-w-0 break-words font-mono font-black leading-tight text-slate-950">
                                    {{ trim(chunk_split(preg_replace('/\D/','', profil('rekening') ?? '7025516952'), 4, ' ')) }}
                                </p>

                                <button onclick="copyInfaqRekening('{{ profil('rekening') ?? '' }}')"
                                        type="button"
                                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-50 px-5 py-3 text-sm font-black text-slate-900 ring-1 ring-emerald-100 transition hover:bg-emerald-100 sm:w-fit">
                                    <span>Salin</span>
                                    <svg class="h-4 w-4 text-emerald-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="mt-6 sm:mt-7">
                            <p class="text-[11px] font-black uppercase tracking-[0.14em] text-emerald-700 sm:text-xs sm:tracking-[0.18em]">
                                Atas Nama
                            </p>
                            <p class="mt-2 break-words text-sm font-black uppercase leading-6 text-slate-900 sm:text-lg">
                                {{ profil('atas_nama') ?? 'Masjid Raudhotul Jannah' }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 flex items-start gap-3 rounded-2xl border border-yellow-200 bg-yellow-50 px-4 py-4 text-xs leading-6 text-yellow-900 sm:mt-5 sm:px-5 sm:text-sm">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-yellow-100 text-lg sm:h-10 sm:w-10 sm:text-xl">
                            🛡
                        </span>
                        <span>
                            Pastikan nominal sudah benar. Setelah transfer, lakukan konfirmasi melalui WhatsApp agar infaq Anda dapat kami catat.
                        </span>
                    </div>
                </div>

                {{-- QRIS --}}
                <div class="relative border-t border-emerald-100 p-4 pt-5 sm:p-8 lg:border-l lg:border-t-0 lg:p-14 lg:pt-16">
                    <div class="mb-5 lg:hidden">
                        <div class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-4 py-2.5 text-white shadow-xl shadow-emerald-700/20 sm:gap-3 sm:px-5 sm:py-3">
                            <span class="text-base sm:text-lg">▦</span>
                            <span class="text-sm font-black uppercase tracking-wide sm:text-base">Scan QRIS</span>
                        </div>
                    </div>

                    <p class="mb-5 text-sm leading-6 text-slate-600 sm:mb-6 sm:leading-7">
                        Scan QRIS berikut menggunakan aplikasi e-wallet atau mobile banking:
                    </p>

                    <div class="rounded-[1.35rem] border border-slate-100 bg-white p-4 shadow-xl shadow-slate-900/5 sm:rounded-[1.7rem] sm:p-5">
                        <div class="grid gap-6 md:grid-cols-[220px_1fr]">
                            <div class="mx-auto w-full max-w-[260px] md:max-w-none">
                                <button type="button"
                                        onclick="document.getElementById('qris-modal').showModal()"
                                        class="group relative block w-full overflow-hidden rounded-2xl border border-emerald-200 bg-white p-3 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-100">
                                    
                                    <span class="absolute left-3 top-3 h-6 w-6 border-l-2 border-t-2 border-emerald-600 sm:h-7 sm:w-7"></span>
                                    <span class="absolute right-3 top-3 h-6 w-6 border-r-2 border-t-2 border-emerald-600 sm:h-7 sm:w-7"></span>
                                    <span class="absolute bottom-9 left-3 h-6 w-6 border-b-2 border-l-2 border-emerald-600 sm:h-7 sm:w-7"></span>
                                    <span class="absolute bottom-9 right-3 h-6 w-6 border-b-2 border-r-2 border-emerald-600 sm:h-7 sm:w-7"></span>

                                    @if(!empty(profil('qris_url')))
                                        <img src="{{ profil('qris_url') }}"
                                             loading="lazy"
                                             alt="QRIS Infaq"
                                             class="h-48 w-full rounded-xl object-contain sm:h-56"
                                             onerror="this.src='{{ asset('storage/404.png') }}'">
                                    @else
                                        <div class="flex h-48 w-full flex-col items-center justify-center rounded-xl border-2 border-dashed border-emerald-100 bg-emerald-50/70 text-slate-400 sm:h-56">
                                            <span class="text-3xl sm:text-4xl">📷</span>
                                            <span class="mt-2 text-xs font-black">QRIS belum tersedia</span>
                                        </div>
                                    @endif

                                    <div class="mt-2 flex items-center justify-center gap-2 text-xs font-semibold text-slate-600 sm:justify-start">
                                        <span class="text-emerald-700">🔍</span>
                                        <span>Klik untuk memperbesar</span>
                                    </div>
                                </button>
                            </div>

                            <div class="space-y-3 sm:space-y-4">
                                @foreach ([
                                    'Buka aplikasi e-wallet atau mobile banking',
                                    'Pilih menu Scan QRIS',
                                    'Scan kode QR di samping',
                                    'Masukkan nominal infaq',
                                    'Konfirmasi pembayaran'
                                ] as $index => $step)
                                    <div class="flex items-start gap-3 sm:gap-4">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-700 text-xs font-black text-white shadow-md shadow-emerald-600/20 sm:text-sm">
                                            {{ $index + 1 }}
                                        </div>
                                        <p class="pt-1 text-sm font-semibold leading-6 text-slate-700">
                                            {{ $step }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-3 rounded-2xl border border-emerald-600/40 bg-emerald-50/70 px-4 py-4 text-sm leading-6 text-slate-700 sm:mt-5 sm:flex-row sm:items-center sm:px-5">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-emerald-300 bg-white text-sm font-black text-emerald-700 sm:h-10 sm:w-10">
                            i
                        </div>
                        <p class="break-words">
                            QRIS atas nama:
                            <span class="font-black uppercase text-slate-900">
                                {{ profil('atas_nama') ?? 'Masjid Raudhotul Jannah' }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Trust Cards --}}
        <div class="mt-6 grid overflow-hidden rounded-[1.5rem] border border-emerald-100/80 bg-white/80 shadow-xl shadow-emerald-900/5 backdrop-blur-xl sm:mt-8 sm:grid-cols-2 sm:rounded-[1.8rem] lg:grid-cols-4">
            <div class="flex gap-3 p-4 sm:gap-4 sm:p-6 lg:border-r lg:border-emerald-100">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-100 text-2xl sm:h-16 sm:w-16 sm:text-3xl">
                    🛡
                </div>
                <div>
                    <h4 class="text-sm font-black text-emerald-800 sm:text-base">Aman & Terpercaya</h4>
                    <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm sm:leading-6">Dana dikelola secara amanah dan transparan.</p>
                </div>
            </div>

            <div class="flex gap-3 border-t border-emerald-100 p-4 sm:gap-4 sm:border-t-0 sm:p-6 lg:border-r">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-yellow-100 text-2xl sm:h-16 sm:w-16 sm:text-3xl">
                    🕘
                </div>
                <div>
                    <h4 class="text-sm font-black text-emerald-800 sm:text-base">Praktis</h4>
                    <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm sm:leading-6">Infaq kapan saja dan di mana saja.</p>
                </div>
            </div>

            <div class="flex gap-3 border-t border-emerald-100 p-4 sm:gap-4 sm:p-6 lg:border-r lg:border-t-0">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-100 text-2xl sm:h-16 sm:w-16 sm:text-3xl">
                    📄
                </div>
                <div>
                    <h4 class="text-sm font-black text-emerald-800 sm:text-base">Transparan</h4>
                    <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm sm:leading-6">Laporan keuangan dapat diakses secara terbuka.</p>
                </div>
            </div>

            <div class="flex gap-3 border-t border-emerald-100 p-4 sm:gap-4 sm:p-6 lg:border-t-0">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-purple-100 text-2xl sm:h-16 sm:w-16 sm:text-3xl">
                    💜
                </div>
                <div>
                    <h4 class="text-sm font-black text-emerald-800 sm:text-base">Pahala Mengalir</h4>
                    <p class="mt-1 text-xs leading-5 text-slate-500 sm:text-sm sm:leading-6">Setiap infaq menjadi amal jariyah yang tak terputus.</p>
                </div>
            </div>
        </div>

        {{-- Quote Strip --}}
        <div class="mt-6 flex flex-col gap-4 rounded-[1.25rem] border border-emerald-100 bg-gradient-to-r from-emerald-50 via-white to-teal-50 px-4 py-5 shadow-lg shadow-emerald-900/5 sm:mt-8 sm:rounded-[1.5rem] sm:px-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3 sm:gap-4">
                <div class="text-4xl font-black leading-none text-emerald-400 sm:text-5xl">
                    “
                </div>
                <p class="max-w-4xl text-xs font-medium leading-6 text-slate-700 sm:text-sm sm:leading-7">
                    Perumpamaan nafkah yang dikeluarkan oleh orang-orang yang menafkahkan hartanya di jalan Allah adalah serupa dengan sebutir benih yang menumbuhkan tujuh bulir, pada tiap-tiap bulir seratus biji. Allah melipatgandakan pahala bagi siapa yang Dia kehendaki.
                </p>
            </div>

            <div class="inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-700 to-emerald-600 px-5 py-3 text-xs font-black text-white shadow-lg shadow-emerald-700/20 sm:w-auto sm:text-sm">
                <span>📖</span>
                <span>QS. Al-Baqarah: 261</span>
            </div>
        </div>
    </div>
</section>

{{-- ================= MODAL QRIS ================= --}}
<dialog id="qris-modal" class="modal">
    <div class="modal-box max-w-sm overflow-hidden rounded-[2rem] border border-emerald-100 bg-white p-0 shadow-2xl shadow-emerald-900/20">
        <div class="border-b border-emerald-100 bg-gradient-to-r from-emerald-50 to-cyan-50 p-5 text-center">
            <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-2xl">
                📱
            </div>
            <h3 class="text-lg font-black text-slate-900">QRIS Infaq</h3>
            <p class="mt-1 text-xs text-slate-500">
                Scan untuk berinfaq ke {{ profil('nama') ?? 'Masjid' }}
            </p>
        </div>

        <div class="flex justify-center bg-white p-6">
            @if(!empty(profil('qris_url')))
                <img src="{{ profil('qris_url') }}"
                     alt="QRIS Infaq"
                     class="w-full max-w-[240px] rounded-2xl object-contain shadow-lg"
                     onerror="this.src='{{ asset('storage/404.png') }}'">
            @else
                <div class="flex h-60 w-60 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50 text-slate-400">
                    <span class="text-4xl">📷</span>
                    <span class="mt-2 text-xs">QRIS belum tersedia</span>
                </div>
            @endif
        </div>

        <div class="modal-action m-0 flex justify-center gap-2 border-t border-emerald-100 bg-emerald-50/40 p-4">
            <form method="dialog">
                <button class="rounded-full border border-emerald-200 bg-white px-6 py-2 text-xs font-bold text-slate-600 transition hover:bg-emerald-50">
                    Tutup
                </button>
            </form>

            @if(!empty(profil('qris_url')))
                <a href="{{ profil('qris_url') }}"
                   download="QRIS_{{ Str::slug(profil('nama')) }}.png"
                   class="rounded-full bg-gradient-to-r from-emerald-600 to-teal-500 px-6 py-2 text-xs font-bold text-white shadow-lg shadow-emerald-500/20 transition hover:shadow-emerald-500/30">
                    Simpan QR
                </a>
            @endif
        </div>
    </div>

    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>

<style>
    .infaq-slider-track {
        display: flex;
        transition: transform 0.7s cubic-bezier(0.25, 0.1, 0.25, 1);
    }

    .infaq-slider-dot.active {
        background-color: #10b981 !important;
        width: 20px !important;
    }

    .infaq-account-number {
        font-size: clamp(1.35rem, 7vw, 2.35rem);
        letter-spacing: 0.045em;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    @media (min-width: 640px) {
        .infaq-account-number {
            font-size: 2.25rem;
            letter-spacing: 0.1em;
        }
    }

    @media (max-width: 360px) {
        .infaq-account-number {
            font-size: 1.2rem;
            letter-spacing: 0.025em;
        }
    }

    .toast-copy {
        position: fixed;
        bottom: 24px;
        left: 50%;
        transform: translateX(-50%);
        padding: 12px 20px;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(14px);
        color: #047857;
        font-size: 13px;
        font-weight: 700;
        border-radius: 999px;
        box-shadow: 0 20px 60px rgba(15, 23, 42, 0.15);
        z-index: 9999;
        transition: all 0.35s ease;
        border: 1px solid rgba(16, 185, 129, 0.18);
        max-width: calc(100vw - 32px);
        text-align: center;
    }

    .toast-copy.hide {
        opacity: 0;
        transform: translateX(-50%) translateY(16px) scale(0.95);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const track = document.querySelector('.infaq-slider-track');

        // Penting: ambil hanya anak langsung dari track, bukan semua .w-full di dalamnya
        const slides = track ? Array.from(track.children) : [];

        const dots = document.querySelectorAll('.infaq-slider-dot');
        const prevBtn = document.getElementById('infaqSliderPrev');
        const nextBtn = document.getElementById('infaqSliderNext');
        const sliderContainer = document.getElementById('infaqMotivasiSlider');

        let currentIndex = 0;
        let totalSlides = slides.length;
        let interval = null;

        if (!track || totalSlides === 0) return;

        function goToSlide(index) {
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;

            currentIndex = index;
            track.style.transform = `translateX(-${currentIndex * 100}%)`;

            dots.forEach((dot, i) => {
                const isActive = i === currentIndex;

                dot.classList.toggle('active', isActive);
                dot.style.width = isActive ? '20px' : '6px';

                dot.classList.toggle('bg-emerald-500', isActive);
                dot.classList.toggle('bg-emerald-300/50', !isActive);
            });
        }

        function nextSlide() {
            goToSlide(currentIndex + 1);
        }

        function prevSlide() {
            goToSlide(currentIndex - 1);
        }

        if (prevBtn) prevBtn.addEventListener('click', prevSlide);
        if (nextBtn) nextBtn.addEventListener('click', nextSlide);

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => goToSlide(index));
        });

        function startAutoPlay() {
            if (totalSlides <= 1) return;
            stopAutoPlay();
            interval = setInterval(nextSlide, 5000);
        }

        function stopAutoPlay() {
            if (interval) {
                clearInterval(interval);
                interval = null;
            }
        }

        startAutoPlay();

        if (sliderContainer) {
            sliderContainer.addEventListener('mouseenter', stopAutoPlay);
            sliderContainer.addEventListener('mouseleave', startAutoPlay);

            let touchStartX = 0;

            sliderContainer.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            sliderContainer.addEventListener('touchend', (e) => {
                const diff = touchStartX - e.changedTouches[0].screenX;

                if (Math.abs(diff) < 50) return;

                if (diff > 50) nextSlide();
                if (diff < -50) prevSlide();
            }, { passive: true });
        }

        if (totalSlides <= 1) {
            if (prevBtn) prevBtn.classList.add('hidden');
            if (nextBtn) nextBtn.classList.add('hidden');
            dots.forEach(dot => dot.classList.add('hidden'));
        }

        goToSlide(0);
    });

    function copyInfaqRekening(text) {
        if (!text) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(() => {
                showInfaqToast('✅ Nomor rekening berhasil disalin!');
            }).catch(() => fallbackCopyInfaq(text));
        } else {
            fallbackCopyInfaq(text);
        }
    }

    function fallbackCopyInfaq(text) {
        const input = document.createElement('input');
        input.value = text;
        document.body.appendChild(input);
        input.select();

        try {
            document.execCommand('copy');
            showInfaqToast('✅ Nomor rekening berhasil disalin!');
        } catch (err) {
            showInfaqToast('❌ Gagal menyalin, silakan salin manual.');
        }

        document.body.removeChild(input);
    }

    function showInfaqToast(message) {
        const existing = document.querySelector('.toast-copy');
        if (existing) existing.remove();

        const toast = document.createElement('div');
        toast.className = 'toast-copy';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('hide');
            setTimeout(() => toast.remove(), 400);
        }, 2600);
    }
</script>