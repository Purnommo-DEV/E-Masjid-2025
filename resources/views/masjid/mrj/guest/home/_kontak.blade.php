{{-- === KONTAK PREMIUM === --}}
<section id="layanan_jamaah" class="relative overflow-hidden bg-gradient-to-br from-white via-emerald-50/50 to-cyan-50/60 py-14 sm:py-16 lg:py-20">
    
    {{-- Background Decoration --}}
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -left-28 top-10 h-80 w-80 rounded-full bg-emerald-200/35 blur-3xl"></div>
        <div class="absolute -right-28 bottom-10 h-80 w-80 rounded-full bg-cyan-200/35 blur-3xl"></div>
        <div class="absolute left-1/2 top-1/3 h-72 w-72 -translate-x-1/2 rounded-full bg-yellow-100/40 blur-3xl"></div>
        <div class="absolute inset-0 bg-[radial-gradient(#10b98110_1px,transparent_1px)] [background-size:28px_28px]"></div>

        <div class="absolute -right-20 top-0 hidden h-72 w-72 rounded-full border border-emerald-200/50 lg:block"></div>
        <div class="absolute -left-20 bottom-0 hidden h-72 w-72 rounded-full border border-yellow-200/50 lg:block"></div>
    </div>

    <div class="container relative z-10 mx-auto px-4 sm:px-6 lg:px-16 xl:px-24">
        <div class="grid gap-6 lg:grid-cols-2 lg:gap-8 xl:gap-10">

            {{-- Kolom Kiri: Maps & Alamat --}}
            <div class="group relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/85 p-5 shadow-2xl shadow-emerald-900/5 backdrop-blur-xl transition-all duration-500 hover:-translate-y-1 hover:shadow-emerald-200/40 sm:p-7 lg:p-9">
                
                {{-- Card Decoration --}}
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-emerald-100/70 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-40 w-40 rounded-full bg-cyan-100/50 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="mb-6">
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-emerald-700 ring-1 ring-emerald-100">
                            <span class="text-sm">🕌</span>
                            <span class="text-xs font-black uppercase tracking-[0.16em]">Kontak</span>
                        </div>

                        <h2 class="font-serif text-3xl font-black leading-tight text-slate-950 sm:text-4xl">
                            Hubungi Kami
                        </h2>

                        <div class="mt-4 flex items-center gap-2">
                            <span class="h-px w-12 bg-yellow-300"></span>
                            <span class="text-yellow-400">✦</span>
                            <span class="h-px w-12 bg-yellow-300"></span>
                        </div>

                        <p class="mt-5 text-sm leading-7 text-slate-600 sm:text-base">
                            {{ $profil->alamat ?? 'Alamat belum tersedia. Hubungi kami untuk info lebih lanjut.' }}
                        </p>
                    </div>

                    {{-- Map Box --}}
                    <div class="overflow-hidden rounded-[1.5rem] border border-emerald-100 bg-emerald-50/60 shadow-xl shadow-emerald-900/5">
                        @if(!empty($profil->latitude) && !empty($profil->longitude))
                            <iframe
                                class="h-[320px] w-full sm:h-[380px] lg:h-[420px]"
                                loading="lazy"
                                allowfullscreen
                                referrerpolicy="no-referrer-when-downgrade"
                                src="https://www.google.com/maps?q={{ $profil->latitude }},{{ $profil->longitude }}&z=20&output=embed">
                            </iframe>
                        @else
                            <div class="relative flex h-[320px] w-full items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-cyan-50 sm:h-[380px] lg:h-[420px]">
                                <div class="absolute inset-0 opacity-40">
                                    <div class="h-full w-full bg-[linear-gradient(45deg,transparent_24%,rgba(16,185,129,0.12)_25%,rgba(16,185,129,0.12)_26%,transparent_27%,transparent_74%,rgba(16,185,129,0.12)_75%,rgba(16,185,129,0.12)_76%,transparent_77%,transparent)] [background-size:42px_42px]"></div>
                                </div>

                                <div class="relative z-10 mx-5 max-w-sm rounded-[2rem] border border-white/80 bg-white/80 p-8 text-center shadow-xl shadow-emerald-900/5 backdrop-blur">
                                    <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-emerald-50 text-4xl text-emerald-600 ring-1 ring-emerald-100">
                                        🕌
                                    </div>
                                    <h3 class="text-xl font-black text-emerald-900">
                                        Peta Masjid Belum Tersedia
                                    </h3>
                                    <p class="mt-3 text-sm leading-6 text-slate-500">
                                        Mohon maaf, peta lokasi masjid saat ini belum tersedia.
                                    </p>
                                </div>
                            </div>
                        @endif

                        {{-- Info Mini --}}
                        <div class="grid border-t border-emerald-100 bg-white/85 sm:grid-cols-3">
                            <div class="flex gap-3 p-4 sm:border-r sm:border-emerald-100">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    📍
                                </div>
                                <div>
                                    <p class="text-xs font-black text-emerald-800">Alamat</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Informasi alamat masjid
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-3 border-t border-emerald-100 p-4 sm:border-r sm:border-t-0 sm:border-emerald-100">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-100 text-teal-700">
                                    🕘
                                </div>
                                <div>
                                    <p class="text-xs font-black text-emerald-800">Jam Operasional</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Setiap hari
                                    </p>
                                </div>
                            </div>

                            <div class="flex gap-3 border-t border-emerald-100 p-4 sm:border-t-0">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-cyan-100 text-cyan-700">
                                    👥
                                </div>
                                <div>
                                    <p class="text-xs font-black text-emerald-800">Layanan Jamaah</p>
                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Kami siap membantu
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kolom Kanan: Kontak + Form --}}
            <div class="group relative overflow-hidden rounded-[2rem] border border-white/80 bg-white/85 p-5 shadow-2xl shadow-emerald-900/5 backdrop-blur-xl transition-all duration-500 hover:-translate-y-1 hover:shadow-emerald-200/40 sm:p-7 lg:p-9">
                
                {{-- Card Decoration --}}
                <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-cyan-100/70 blur-3xl"></div>
                <div class="pointer-events-none absolute bottom-0 left-0 h-40 w-40 rounded-full bg-emerald-100/50 blur-3xl"></div>

                <div class="relative z-10">
                    <div class="mb-6">
                        <div class="mb-4 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-emerald-700 ring-1 ring-emerald-100">
                            <span class="text-sm">🕌</span>
                            <span class="text-xs font-black uppercase tracking-[0.16em]">Kontak</span>
                        </div>

                        <h2 class="font-serif text-3xl font-black leading-tight text-slate-950 sm:text-4xl">
                            Kontak & Pesan Jamaah
                        </h2>

                        <div class="mt-4 flex items-center gap-2">
                            <span class="h-px w-12 bg-yellow-300"></span>
                            <span class="text-yellow-400">✦</span>
                            <span class="h-px w-12 bg-yellow-300"></span>
                        </div>
                    </div>

                    {{-- Contact Quick Cards --}}
                    <div class="mb-7 grid gap-3 sm:grid-cols-2">
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $profil->telepon ?? ($profil->no_wa ?? '')) }}"
                           target="_blank"
                           class="group/item flex items-center gap-4 rounded-2xl border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-emerald-100">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500 text-2xl text-white shadow-lg shadow-emerald-500/20">
                                ☎
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-900">WhatsApp</p>
                                <p class="mt-1 truncate text-xs text-slate-500">Hubungi kami langsung</p>
                                <p class="mt-1 truncate text-sm font-black text-emerald-700">
                                    {{ $profil->telepon ?? ($profil->no_wa ?? '-') }}
                                </p>
                            </div>
                        </a>

                        <a href="mailto:{{ $profil->email ?? '' }}"
                           class="group/item flex items-center gap-4 rounded-2xl border border-cyan-100 bg-gradient-to-br from-cyan-50 to-white p-4 transition hover:-translate-y-1 hover:shadow-lg hover:shadow-cyan-100">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-cyan-500 text-2xl text-white shadow-lg shadow-cyan-500/20">
                                ✉
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-black text-slate-900">Email</p>
                                <p class="mt-1 truncate text-xs text-slate-500">Kirim email kepada kami</p>
                                <p class="mt-1 truncate text-sm font-black text-cyan-700">
                                    {{ $profil->email ?? '-' }}
                                </p>
                            </div>
                        </a>
                    </div>

                    {{-- FORM PESAN DENGAN reCAPTCHA v3 --}}
                    <form id="contactForm" class="mt-3 space-y-5">
                        @csrf
                        <input type="hidden" name="g-recaptcha-response" id="recaptchaToken">

                        {{-- Nama --}}
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-black text-slate-800">Nama Anda</span>
                            </label>

                            <div class="relative">
                                <input
                                    type="text"
                                    name="nama"
                                    id="contactNama"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-12 py-4 text-sm text-slate-900 outline-none transition-all duration-300 placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                    placeholder="Masukkan nama lengkap Anda"
                                />
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                    👤
                                </span>
                            </div>

                            <div class="error mt-1 hidden text-xs text-red-600" id="error-nama"></div>
                        </div>

                        {{-- Telepon --}}
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-black text-slate-800">Nomor Telepon <span class="font-semibold text-slate-400">(opsional)</span></span>
                            </label>

                            <div class="relative">
                                <input
                                    type="text"
                                    name="telepon"
                                    id="contactTelp"
                                    class="w-full rounded-2xl border border-slate-200 bg-white px-12 py-4 text-sm text-slate-900 outline-none transition-all duration-300 placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                    placeholder="Contoh: 0812-3456-7890"
                                />
                                <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-lg text-slate-400">
                                    📱
                                </span>
                            </div>

                            <div class="error mt-1 hidden text-xs text-red-600" id="error-telepon"></div>
                        </div>

                        {{-- Pesan --}}
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text text-sm font-black text-slate-800">
                                    Pesan atau Saran <span class="text-red-500">*</span>
                                </span>
                            </label>

                            <div class="relative">
                                <textarea
                                    name="pesan"
                                    id="contactPesan"
                                    rows="5"
                                    required
                                    class="w-full resize-none rounded-2xl border border-slate-200 bg-white px-12 py-4 text-sm leading-7 text-slate-900 outline-none transition-all duration-300 placeholder:text-slate-400 focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100"
                                    placeholder="Silakan sampaikan pertanyaan, saran, atau keperluan terkait kegiatan masjid."></textarea>
                                <span class="pointer-events-none absolute left-4 top-4 text-lg text-slate-400">
                                    💬
                                </span>
                            </div>

                            <div class="error mt-1 hidden text-xs text-red-600" id="error-pesan"></div>
                        </div>

                        {{-- Submit & Status --}}
                        <div class="pt-2">
                            <button
                                id="contactSubmitBtn"
                                type="submit"
                                class="inline-flex w-full items-center justify-center gap-3 rounded-2xl bg-gradient-to-r from-emerald-600 to-teal-500 px-8 py-4 text-base font-black text-white shadow-xl shadow-emerald-500/20 transition-all duration-300 hover:-translate-y-1 hover:from-emerald-700 hover:to-teal-600 hover:shadow-emerald-500/30 sm:w-auto sm:min-w-[210px]">
                                <span>➤</span>
                                <span>Kirim Pesan</span>
                            </button>

                            <div id="contactStatus" class="mt-3 text-sm"></div>
                        </div>

                        <div class="flex items-start gap-2 rounded-2xl bg-emerald-50/70 px-4 py-3 text-xs leading-5 text-slate-500">
                            <span class="mt-0.5 text-emerald-600">🛡</span>
                            <div>
                                <p>Pesan Anda aman dan hanya digunakan untuk keperluan komunikasi.</p>
                                <div id="recaptcha-credit" class="mt-1">
                                    This site is protected by reCAPTCHA and the Google 
                                    <a href="https://policies.google.com/privacy" target="_blank" class="text-emerald-600 hover:underline">Privacy Policy</a> and 
                                    <a href="https://policies.google.com/terms" target="_blank" class="text-emerald-600 hover:underline">Terms of Service</a> apply.
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>