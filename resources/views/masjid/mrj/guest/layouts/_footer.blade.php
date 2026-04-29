<div class="w-full overflow-hidden leading-[0]" style="margin-bottom: -2px">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 120" class="w-full h-auto block" preserveAspectRatio="none" style="display: block;">
        <!-- Ubah fill dari #064e3b menjadi warna awal gradient footer -->
        <path fill="#064e3b" fill-opacity="1" d="M0,64L48,69.3C96,75,192,85,288,80C384,75,480,53,576,48C672,43,768,53,864,64C960,75,1056,85,1152,85.3C1248,85,1344,75,1392,69.3L1440,64L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"></path>
    </svg>
</div>

<footer class="bg-gradient-to-b from-emerald-900 via-emerald-950 to-slate-950 text-emerald-50 mt-0 relative z-10">
    {{-- ================= DOA PENUTUP ================= --}}
    <div class="relative overflow-hidden">
        {{-- Background lingkaran dekoratif --}}
        <div class="absolute top-0 left-0 w-64 h-64 bg-emerald-800/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-80 h-80 bg-teal-800/20 rounded-full blur-3xl"></div>
        
        <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative text-center py-12 md:py-16">
            <div class="max-w-3xl mx-auto">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-gradient-to-br from-emerald-700 to-teal-800 mb-4 shadow-lg">
                    <span class="text-2xl">🤲</span>
                </div>
                <h3 class="text-xl md:text-2xl font-semibold text-emerald-200 mb-4">
                    Harapan & Doa
                </h3>
                <p class="text-emerald-100/90 leading-relaxed">
                    Semoga setiap langkah menuju masjid ini membawa ketenangan,
                    setiap sujud di dalamnya mendekatkan kepada Allah,
                    dan setiap yang singgah pulang dengan hati yang lebih lapang.
                </p>
                <p class="mt-5 text-emerald-300 font-medium">
                    Semoga Allah menjaga masjid ini dan menjadikannya tempat tumbuhnya kebaikan bagi siapa pun yang datang.
                </p>
                <p class="mt-6 text-sm text-emerald-200/90 italic">
                    Semoga kita semua selalu memiliki bagian dalam setiap kebaikan yang lahir dari masjid ini.
                </p>
            </div>
        </div>
    </div>

    {{-- ================= GRID FOOTER ================= --}}
    <div class="relative overflow-hidden">
        {{-- Lingkaran dekoratif di background --}}
        <div class="absolute -top-32 -right-32 w-96 h-96 bg-emerald-700/10 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-teal-700/10 rounded-full blur-3xl"></div>
        
        <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative py-12 md:py-16 grid gap-10 md:grid-cols-2">

            {{-- KOLOM KIRI: MASJID --}}
            <div class="relative">
                <div class="flex items-center gap-3 mb-4">
                    @if(profil('nama') && profil('logo_url'))
                        <img src="{{ profil('logo_url') }}" alt="Logo" class="w-12 h-12 rounded-full object-cover border-2 border-emerald-500/50 shadow-lg">
                    @else
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-emerald-600 to-teal-700 flex items-center justify-center text-2xl shadow-lg">
                            🕌
                        </div>
                    @endif
                    <h4 class="text-xl font-semibold text-white">
                        {!! profil('nama') !!}
                    </h4>
                </div>

                <p class="text-sm text-emerald-100/80 leading-relaxed">
                    Masjid sebagai tempat ibadah, belajar, dan bertemunya kebersamaan jamaah.
                    Semoga setiap langkah menuju masjid menjadi kebaikan yang dicatat sebagai pahala.
                </p>

                <div class="mt-5 text-sm text-emerald-200">
                    <div class="flex items-start gap-3">
                        <span class="text-lg">📍</span>
                        <p class="leading-relaxed">{!! profil('alamat') ?? 'Alamat masjid belum tersedia' !!}</p>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: INFORMASI & LAYANAN --}}
            <div>
                <h4 class="text-xl font-semibold text-white mb-4">Informasi & Layanan</h4>
                
                {{-- Tombol WA Admin --}}
                <a href="https://wa.me/{{ waNumberFormatted() }}"
                   target="_blank"
                   class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white text-sm font-semibold shadow-lg transition-all duration-300 hover:scale-105 hover:shadow-xl">
                    💬 Layanan Jamaah
                </a>

                <p class="mt-4 text-xs text-emerald-200/80 leading-relaxed">
                    Untuk pertanyaan atau keperluan, silakan menghubungi pengurus masjid melalui WhatsApp di atas.
                </p>

                {{-- ================= KANAL INFORMASI ================= --}}
                <div class="mt-6 pt-6 border-t border-emerald-800/40">
                    <h5 class="text-sm font-semibold text-emerald-200 mb-3">
                        📢 Kanal Informasi Masjid
                    </h5>
                    <p class="text-xs text-emerald-200/80 mb-4">
                        Pengumuman, kajian, dan dokumentasi kegiatan dapat diikuti melalui kanal resmi berikut.
                    </p>

                    <div class="flex flex-wrap items-center gap-4">
                        <a href="https://whatsapp.com/channel/ISI_LINK_CHANNEL"
                           target="_blank"
                           class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-800/40 hover:bg-emerald-700/60 text-emerald-100 hover:text-white transition-all duration-300 hover:scale-105 text-xs group">
                            <span class="text-base group-hover:animate-pulse">🟢</span>
                            Channel WhatsApp
                        </a>
                        <a href="https://youtube.com/ISI_LINK_YOUTUBE"
                           target="_blank"
                           class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-800/40 hover:bg-emerald-700/60 text-emerald-100 hover:text-white transition-all duration-300 hover:scale-105 text-xs group">
                            <span class="text-base group-hover:animate-pulse">▶️</span>
                            Kajian & Dokumentasi
                        </a>
                        <a href="https://instagram.com/ISI_LINK_INSTAGRAM"
                           target="_blank"
                           class="flex items-center gap-2 px-3 py-1.5 rounded-full bg-emerald-800/40 hover:bg-emerald-700/60 text-emerald-100 hover:text-white transition-all duration-300 hover:scale-105 text-xs group">
                            <span class="text-base group-hover:animate-pulse">📷</span>
                            Kegiatan Jamaah
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= COPYRIGHT ================= --}}
    <div class="border-t border-emerald-800/40 text-center py-6 px-4 relative">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-20 left-1/4 w-40 h-40 bg-emerald-700/5 rounded-full blur-2xl"></div>
            <div class="absolute -bottom-20 right-1/4 w-40 h-40 bg-teal-700/5 rounded-full blur-2xl"></div>
        </div>
        
        <p class="text-emerald-400 italic text-xs relative">
            بارك الله فيكم وجزاكم الله خيرًا
        </p>
        <p class="mt-4 text-xs text-emerald-500/80 relative">
            © {{ date('Y') }} <span class="font-semibold">DKM {{ profil('nama') ?? 'Masjid' }}</span><br>
            Website resmi pengelolaan informasi, dakwah, dan pelayanan jamaah.
        </p>
    </div>

</footer>