        {{-- === KONTAK === --}}
        <section id="layanan_jamaah" class="py-16 relative overflow-hidden">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">

                    <!-- Kolom Kiri: Maps & Alamat (tetap) -->
                    <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white/50 shadow-lg p-6 lg:p-10 hover:shadow-2xl hover:shadow-emerald-200/20 transition-all duration-300">
                        <div class="mb-6">
                            <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">Kontak</p>
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-4">Hubungi Kami</h2>
                            <p class="text-base text-slate-600 mt-3 leading-relaxed">
                                {{ $profil->alamat ?? 'Alamat belum tersedia. Hubungi kami untuk info lebih lanjut.' }}
                            </p>
                        </div>

                        <div class="flex-1 bg-slate-50">
                            @if(!empty($profil->latitude) && !empty($profil->longitude))
                                <iframe
                                    class="w-full h-full min-h-[400px] rounded-2xl shadow-xl shadow-emerald-200/30 border border-emerald-100/50"
                                    loading="lazy"
                                    allowfullscreen
                                    referrerpolicy="no-referrer-when-downgrade"
                                    src="https://www.google.com/maps?q={{ $profil->latitude }},{{ $profil->longitude }}&z=20&output=embed">
                                </iframe>
                            @else
                                <div class="w-full h-full min-h-[400px] flex items-center justify-center text-slate-400 text-lg bg-slate-100">
                                    Peta Masjid Belum Tersedia
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Kolom Kanan: Kontak + Form -->
                    <div class="bg-white/80 backdrop-blur-md rounded-2xl border border-white/50 shadow-lg p-6 lg:p-10 hover:shadow-2xl hover:shadow-emerald-200/20 transition-all duration-300">
                        <div class="mb-6">
                            <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">Kontak</p>
                            <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-4">Kontak & Pesan Jamaah</h2>
                            <div class="space-y-4 text-base text-slate-700">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl text-emerald-600">📞</span>
                                    <span>WhatsApp: <strong class="text-emerald-700">{{ $profil->telepon ?? ($profil->no_wa ?? '-') }}</strong></span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl text-emerald-600">✉️</span>
                                    <span>Email: <strong class="text-emerald-700">{{ $profil->email ?? '-' }}</strong></span>
                                </div>
                            </div>
                        </div>

                        <!-- FORM PESAN DENGAN reCAPTCHA v3 -->
                        <form id="contactForm" class="mt-3 space-y-6">
                            @csrf
                            <input type="hidden" name="g-recaptcha-response" id="recaptchaToken">

                            <!-- Nama -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Anda</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="nama" id="contactNama" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white" placeholder="Masukkan nama lengkap (Optional)" />
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👤</span>
                                </div>
                                <div class="error text-red-600 text-xs mt-1 hidden" id="error-nama"></div>
                            </div>

                            <!-- Telepon -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nomor Telepon (opsional)</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="telepon" id="contactTelp" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white" placeholder="Contoh: 08123456789" />
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📱</span>
                                </div>
                                <div class="error text-red-600 text-xs mt-1 hidden" id="error-telepon"></div>
                            </div>

                            <!-- Pesan -->
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Pesan atau Saran <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <textarea name="pesan" id="contactPesan" rows="5" required class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none" placeholder="Silakan sampaikan pertanyaan, saran, atau keperluan terkait kegiatan masjid."></textarea>
                                    <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">💬</span>
                                </div>
                                <div class="error text-red-600 text-xs mt-1 hidden" id="error-pesan"></div>
                            </div>

                            <!-- Submit & Status -->
                            <div class="flex items-center justify-between pt-4">
                                <button id="contactSubmitBtn" type="submit" class="px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base">
                                    Kirim Pesan
                                </button>
                                <div id="contactStatus" class="text-sm ml-4"></div>
                            </div>
                            <div id="recaptcha-credit" class="text-xs text-gray-500 mt-2 text-right">
                                This site is protected by reCAPTCHA and the Google 
                                <a href="https://policies.google.com/privacy" target="_blank" class="text-emerald-600 hover:underline">Privacy Policy</a> and 
                                <a href="https://policies.google.com/terms" target="_blank" class="text-emerald-600 hover:underline">Terms of Service</a> apply.
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </section>
