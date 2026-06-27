        {{-- === DONASI === --}}
        <section id="donasi" class="home-section home-section-donasi py-16 relative overflow-hidden">
            
            {{-- Background decorative elements --}}
            <div class="absolute inset-0 pointer-events-none">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-emerald-200/30 rounded-full blur-3xl"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-teal-200/30 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-emerald-100/20 rounded-full blur-3xl"></div>
            </div>

            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                
                <!-- Header Donasi - DIPERINDAH -->
                <div class="text-center mb-12 relative">
                    <!-- Decorative line atas -->
                    <div class="flex justify-center items-center gap-3 mb-4">
                        <div class="h-px w-12 bg-gradient-to-r from-transparent to-emerald-400"></div>
                        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-emerald-100/80 backdrop-blur-sm border border-emerald-200/50 shadow-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                            </span>
                            <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700">DONASI & INFAQ</span>
                        </div>
                        <div class="h-px w-12 bg-gradient-to-l from-transparent to-emerald-400"></div>
                    </div>

                    <!-- Title dengan gradient & shadow -->
                    <h2 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold mb-4 bg-gradient-to-r from-emerald-700 via-teal-600 to-emerald-500 bg-clip-text text-transparent drop-shadow-sm">
                        Jadikan Harta Lebih Berkah
                    </h2>
                    <!-- Deskripsi dengan border elegant -->
                    <div class="max-w-2xl mx-auto relative">
                        <div class="absolute left-0 top-0 w-8 h-8 border-l-2 border-t-2 border-emerald-300/50 rounded-tl-2xl"></div>
                        <div class="absolute right-0 top-0 w-8 h-8 border-r-2 border-t-2 border-emerald-300/50 rounded-tr-2xl"></div>
                        <div class="absolute left-0 bottom-0 w-8 h-8 border-l-2 border-b-2 border-emerald-300/50 rounded-bl-2xl"></div>
                        <div class="absolute right-0 bottom-0 w-8 h-8 border-r-2 border-b-2 border-emerald-300/50 rounded-br-2xl"></div>
                        
                        <p class="text-base sm:text-lg text-slate-600 leading-relaxed px-6 py-3">
                            Setiap rupiah yang Anda titipkan akan menjadi bagian dari adzan yang berkumandang,
                            shalat berjamaah, kajian ilmu, serta kegiatan sosial umat.
                        </p>
                    </div>
                    
                    <p class="mt-3 text-slate-500 text-sm text-center flex items-center justify-center gap-3">
                        <span class="hidden sm:block flex-1 h-px bg-emerald-300"></span>
                        <span>InsyaAllah menjadi <span class="font-semibold text-emerald-600">amal jariyah</span> yang pahalanya terus mengalir</span>
                        <span class="hidden sm:block flex-1 h-px bg-emerald-300"></span>
                    </p>
                </div>

                <!-- Slider Motivasi (jika ada) -->
                @if($sliders->isNotEmpty())
                    <div id="motivasiCarousel" class="relative mb-12">
                        <div class="overflow-hidden rounded-3xl shadow-2xl">
                            <div class="motivasi-track flex transition-transform duration-400 ease-[cubic-bezier(0.25,0.1,0.25,1)] snap-x snap-mandatory">
                                @foreach($sliders as $slide)
                                    <div class="w-full shrink-0 snap-start relative group">
                                        <div class="bg-gradient-to-br {{ $slide->gradient ?? 'from-emerald-700 via-teal-700 to-cyan-700' }} text-white rounded-3xl p-6 sm:p-8 lg:p-12 text-center min-h-[380px] sm:min-h-[360px] flex flex-col justify-between items-center shadow-2xl overflow-hidden group-hover:scale-[1.02] transition-transform duration-400">
                                            <!-- Decorative pattern -->
                                            <div class="absolute inset-0 opacity-10 pointer-events-none">
                                                <div class="absolute -top-20 -right-20 w-60 h-60 bg-white rounded-full blur-2xl"></div>
                                                <div class="absolute -bottom-20 -left-20 w-60 h-60 bg-white rounded-full blur-2xl"></div>
                                            </div>
                                            
                                            <div class="flex flex-col items-center justify-center flex-grow space-y-4 px-4 sm:px-8 relative z-10">
                                                <div class="w-20 h-20 rounded-full bg-white/20 backdrop-blur-sm flex items-center justify-center text-4xl mb-2 shadow-lg">
                                                    💝
                                                </div>
                                                <h3 class="text-xl sm:text-2xl lg:text-3xl font-extrabold leading-tight">
                                                    {!! $slide->title !!}
                                                </h3>
                                                <p class="text-base sm:text-lg lg:text-xl font-semibold">
                                                    {!! $slide->subtitle !!}
                                                </p>
                                            </div>
                                            <a href="{{ $slide->button_link ?? '#rekening' }}"
                                            class="btn btn-lg bg-white text-emerald-800 hover:bg-amber-100 hover:text-emerald-900 font-bold px-6 sm:px-10 py-3 sm:py-4 rounded-full shadow-xl text-base sm:text-lg mt-4 w-full sm:w-auto max-w-xs transition-all relative z-10">
                                                {{ $slide->button_text ?? 'Yuk Sedekah Sekarang' }}
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="flex justify-center gap-3 mt-4">
                            @foreach($sliders as $index => $slide)
                                <button class="motivasi-dot w-2.5 h-2.5 rounded-full bg-emerald-300 hover:bg-emerald-600 transition" data-index="{{ $index }}"></button>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Card Donasi Utama - dengan efek glassmorphism lebih elegan -->
                <div class="w-full bg-white/80 backdrop-blur-md rounded-3xl border border-white/50 shadow-2xl overflow-hidden transition-all duration-300 hover:shadow-emerald-200/40">
                    
                    <!-- Header Card dengan Gradien + Ikon -->
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-center relative overflow-hidden">
                        <div class="absolute inset-0 opacity-20">
                            <div class="absolute -top-10 -right-10 w-40 h-40 bg-white rounded-full blur-2xl"></div>
                            <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-white rounded-full blur-2xl"></div>
                        </div>
                        <div class="relative z-10">
                            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white/20 backdrop-blur-sm mb-3 shadow-lg">
                                <span class="text-3xl">💚</span>
                            </div>
                            <h3 class="text-xl sm:text-2xl font-bold text-white">Salurkan Donasi & Infaq</h3>
                            <p class="text-emerald-100 text-sm mt-1">Donasi Anda akan menjadi amal jariyah yang terus mengalir</p>
                        </div>
                    </div>

                    <div class="p-6 sm:p-8 lg:p-10">
                        
                        <!-- 2 Kolom: Rekening Bank + QRIS dengan efek hover -->
                        <div class="grid md:grid-cols-2 gap-6 mb-8">
                            
                            <!-- Kolom Kiri: Rekening Bank -->
                            <div class="bg-gradient-to-br from-emerald-50/80 to-white rounded-2xl p-5 flex flex-col h-full border border-emerald-100/50 shadow-md transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-emerald-500 to-teal-500 mb-3 shadow-md">
                                        <span class="text-xl text-white">🏦</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-emerald-800 mb-1">Transfer Bank</h4>
                                    <p class="text-xs text-slate-500 mb-3">Donasi via rekening berikut</p>
                                </div>
                                
                                <div class="bg-white rounded-xl p-4 shadow-sm flex-1 border border-emerald-100">
                                    <div class="text-center mb-2">
                                        <span class="inline-block px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold">
                                            {{ profil('bank_name') ?? 'BANK BCA' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-center gap-2 my-2 bg-slate-50 rounded-lg p-2">
                                        <p id="rekeningNum" class="text-base sm:text-lg font-mono font-bold text-slate-800 tracking-wider break-all text-center">
                                            {{ trim(chunk_split(preg_replace('/\D/','', profil('rekening') ?? '1234567890'), 4, ' ')) }}
                                        </p>
                                        <button onclick="copyToClipboard('{{ profil('rekening') ?? '' }}')"
                                                class="btn btn-xs btn-circle bg-emerald-600 hover:bg-emerald-700 text-white w-7 h-7 min-h-0 text-sm shadow-md transition-all hover:scale-110"
                                                title="Salin nomor rekening">
                                            📋
                                        </button>
                                    </div>
                                    <p class="text-xs text-slate-600 text-center font-medium">a/n {{ profil('atas_nama') ?? 'TAKMIR MASJID' }}</p>
                                </div>
                                <p class="text-xs text-slate-400 text-center mt-3 flex items-center justify-center gap-1">
                                    <span>💡</span> Klik 📋 untuk menyalin nomor rekening
                                </p>
                            </div>

                            <!-- Kolom Kanan: QRIS -->
                            <div class="bg-gradient-to-br from-emerald-50/80 to-white rounded-2xl p-5 flex flex-col h-full border border-emerald-100/50 shadow-md transition-all duration-300 hover:shadow-lg hover:-translate-y-1">
                                <div class="text-center">
                                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-gradient-to-br from-teal-500 to-cyan-500 mb-3 shadow-md">
                                        <span class="text-xl text-white">📱</span>
                                    </div>
                                    <h4 class="text-lg font-bold text-emerald-800 mb-1">Scan QRIS</h4>
                                    <p class="text-xs text-slate-500 mb-3">Donasi lebih mudah & cepat</p>
                                </div>
                                
                                <!-- QRIS Box -->
                                <div class="flex justify-center items-center flex-1">
                                    <div class="relative group/qris">
                                        <div class="absolute -inset-1 bg-gradient-to-r from-emerald-400 to-teal-400 rounded-2xl blur opacity-25 group-hover/qris:opacity-75 transition duration-300"></div>
                                        <div style="width: 190px; height: 190px; background: white; padding: 14px; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); cursor: pointer; transition: all 0.3s ease; position: relative;"
                                            onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 20px 30px -10px rgba(0, 0, 0, 0.15)'"
                                            onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='0 10px 25px -5px rgba(0, 0, 0, 0.1)'"
                                            onclick="document.getElementById('qris-modal').showModal()">
                                            @if(!empty($profil?->qris_url))
                                                <img src="{{ $profil->qris_url }}" 
                                                    loading="lazy" 
                                                    alt="QRIS Donasi" 
                                                    style="width: 100%; height: 100%; object-fit: contain;"
                                                    onerror="this.src='{{ asset('storage/404.png') }}'">
                                            @else
                                                <div class="flex flex-col items-center justify-center h-full text-center">
                                                    <span class="text-3xl mb-2">📷</span>
                                                    <span class="text-[10px] text-gray-400">QRIS belum tersedia</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="text-center mt-4">
                                    @if(!empty($profil?->qris_url))
                                        <a href="{{ $profil->qris_url }}" 
                                        download="QRIS_{{ Str::slug($profil->nama ?? 'masjid') }}.png"
                                        class="inline-flex items-center gap-1 text-xs text-emerald-600 hover:text-emerald-700 transition-all hover:gap-2">
                                            📥 Download QRIS
                                        </a>
                                    @else
                                        <p class="text-xs text-slate-400">QRIS akan segera tersedia</p>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Informasi Donasi - Responsif Mobile -->
                        <div class="bg-gradient-to-r from-emerald-50/80 to-teal-50/80 rounded-xl p-4 text-center border border-emerald-100">
                            <!-- Grid 1 kolom di mobile, flex row di desktop -->
                            <div class="flex flex-col sm:flex-row flex-wrap justify-center gap-3 sm:gap-4 text-xs text-slate-600">
                                <div class="flex items-center justify-center gap-2 px-3 py-2 bg-white rounded-full shadow-sm">
                                    <span class="text-emerald-600 text-sm">✓</span>
                                    <span class="font-medium text-xs sm:text-sm">100% untuk kegiatan masjid</span>
                                </div>
                                <div class="flex items-center justify-center gap-2 px-3 py-2 bg-white rounded-full shadow-sm">
                                    <span class="text-emerald-600 text-sm">✓</span>
                                    <span class="font-medium text-xs sm:text-sm">Laporan transparan berkala</span>
                                </div>
                                <div class="flex items-center justify-center gap-2 px-3 py-2 bg-white rounded-full shadow-sm">
                                    <span class="text-emerald-600 text-sm">✓</span>
                                    <span class="font-medium text-xs sm:text-sm">Amal jariyah</span>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Konfirmasi WA - lebih menonjol -->
                        <div class="text-center mt-8">
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', profil('wa_konfirmasi') ?? profil('telepon') ?? '628121073583') }}?text=Assalamu%27alaikum%20saya%20ingin%20konfirmasi%20donasi"
                            target="_blank"
                            class="group relative inline-flex items-center gap-3 px-8 py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105 text-sm overflow-hidden">
                                <span class="absolute inset-0 w-0 bg-gradient-to-r from-emerald-500 to-teal-500 transition-all duration-500 group-hover:w-full"></span>
                                <span class="relative z-10 text-xl">💬</span>
                                <span class="relative z-10">Konfirmasi Donasi via WhatsApp</span>
                            </a>
                            <p class="text-xs text-slate-500 mt-3">
                                Konfirmasi donasi untuk mendapatkan bukti & laporan penyaluran
                            </p>
                        </div>

                    </div>
                </div>

                <!-- Doa Penutup -->
                <div class="w-full mt-6">
                    <div class="bg-emerald-50/70 border border-emerald-200 rounded-2xl p-5 text-center backdrop-blur-sm">
                        <div class="flex justify-center mb-2">
                            <span class="text-2xl animate-pulse">🤲</span>
                        </div>
                        <p class="text-emerald-800 italic text-sm leading-relaxed">
                            “Ya Allah, terimalah sedekah dari para dermawan kami, lapangkan rezekinya, sehatkan badannya, dan jadikan sebagai pemberat amal kebaikan di akhirat.”
                        </p>
                        <p class="text-emerald-600 text-xs font-medium mt-2">Aamiin Ya Rabbal Alamin</p>
                    </div>
                </div>

            </div>
        </section>

        <!-- Modal QRIS Preview -->
        <dialog id="qris-modal" class="modal">
            <div class="modal-box bg-white rounded-3xl shadow-2xl max-w-md sm:max-w-lg p-0 overflow-hidden">
                <div class="bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-center">
                    <h3 class="text-xl font-bold text-white">Preview QRIS Donasi</h3>
                    <p class="text-emerald-100/90 text-xs mt-1">
                        Scan untuk donasi ke {{ $profil->nama ?? 'Masjid' }}
                    </p>
                </div>
                <div class="p-6 bg-white">
                    @if(!empty($profil?->qris_url))
                        <img src="{{ $profil->qris_url }}" 
                            alt="QRIS Donasi" 
                            class="w-full h-auto max-h-[400px] object-contain mx-auto rounded-xl"
                            onerror="this.src='{{ asset('storage/404.png') }}'">
                    @else
                        <div class="w-full h-48 flex items-center justify-center text-gray-400">QRIS belum tersedia</div>
                    @endif
                </div>
                <div class="modal-action p-5 border-t border-emerald-100 flex justify-center gap-3">
                    <form method="dialog">
                        <button class="btn btn-outline text-emerald-700 px-6 py-2 rounded-full text-sm">Tutup</button>
                    </form>
                    @if(!empty($profil?->qris_url))
                        <a href="{{ $profil->qris_url }}" 
                        download="QRIS_{{ Str::slug($profil->nama ?? 'masjid') }}.png"
                        class="btn bg-emerald-600 hover:bg-emerald-700 text-white px-6 py-2 rounded-full text-sm">
                            Simpan Gambar
                        </a>
                    @endif
                </div>
            </div>
            <form method="dialog" class="modal-backdrop">
                <button>close</button>
            </form>
        </dialog>
