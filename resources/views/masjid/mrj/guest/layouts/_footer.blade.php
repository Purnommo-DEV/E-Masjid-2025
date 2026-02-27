<footer class="mt-1 bg-gradient-to-b from-emerald-900 via-emerald-950 to-slate-950 text-emerald-50">

    {{-- ================= DOA PENUTUP ================= --}}
    <div class="max-w-4xl mx-auto text-center px-6 pt-16 pb-12">
        <h3 class="text-2xl font-semibold text-emerald-200 mb-4">
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

    {{-- ================= GRID FOOTER ================= --}}
    <div class="border-t border-emerald-800/40">
        <div class="container mx-auto px-6 py-14 grid gap-10 md:grid-cols-2 lg:grid-cols-2">

            {{-- MASJID --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">
                    {!! profil('nama') !!}
                </h4>

                <p class="text-sm text-emerald-100/80 leading-relaxed">
                    Masjid sebagai tempat ibadah, belajar, dan bertemunya kebersamaan jamaah.
                    Semoga setiap langkah menuju masjid menjadi kebaikan yang dicatat sebagai pahala.
                </p>

                <div class="mt-5 text-sm text-emerald-200">
                    <p>{!! profil('alamat') ?? 'Alamat masjid belum tersedia' !!}</p>
                </div>
            </div>

            {{-- INFORMASI & LAYANAN --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Informasi & Layanan</h4>

                <div class="space-y-3 text-sm text-emerald-100/90">

                    <div class="flex items-center gap-3">
                        <span class="text-xl">📞</span>
                        <span>{!! profil('no_wa') ?? '0895704043814' !!}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xl">🕒</span>
                        <span>Layanan tersedia pada waktu-waktu sholat</span>
                    </div>

                </div>

                {{-- Tombol WA Admin --}}
                @php
                    $wa = preg_replace('/[^0-9]/', '', profil('no_wa') ?? '62895704043814');
                @endphp

                <a href="https://wa.me/{{ $wa }}"
                   target="_blank"
                   class="inline-block mt-5 px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-lg transition">
                    💬 Layanan Jamaah
                </a>

                <p class="mt-4 text-xs text-emerald-200/80 leading-relaxed">
                    Untuk pertanyaan atau keperluan, silakan menghubungi pengurus masjid melalui WhatsApp di atas.
                    Setelah berinfak, silakan lakukan konfirmasi melalui menu Infak agar dapat kami catat.
                </p>

                {{-- ================= KANAL INFORMASI MASJID ================= --}}
                <div class="mt-8 border-t border-emerald-800/40 pt-6">
                    <h5 class="text-sm font-semibold text-emerald-200 mb-3">
                        Kanal Informasi Masjid
                    </h5>

                    <p class="text-xs text-emerald-200/80 mb-4 leading-relaxed">
                        Pengumuman, kajian, dan dokumentasi kegiatan dapat diikuti melalui kanal resmi berikut.
                    </p>

                    <div class="flex flex-wrap items-center gap-4">

                        <!-- WhatsApp Channel -->
                        <a href="https://whatsapp.com/channel/ISI_LINK_CHANNEL"
                           target="_blank"
                           class="flex items-center gap-2 text-emerald-100 hover:text-white transition text-xs">
                            <span class="text-lg">🟢</span>
                            Channel WhatsApp
                        </a>

                        <!-- YouTube -->
                        <a href="https://youtube.com/ISI_LINK_YOUTUBE"
                           target="_blank"
                           class="flex items-center gap-2 text-emerald-100 hover:text-white transition text-xs">
                            <span class="text-lg">▶️</span>
                            Kajian & Dokumentasi
                        </a>

                        <!-- Instagram -->
                        <a href="https://instagram.com/ISI_LINK_INSTAGRAM"
                           target="_blank"
                           class="flex items-center gap-2 text-emerald-100 hover:text-white transition text-xs">
                            <span class="text-lg">📷</span>
                            Kegiatan Jamaah
                        </a>

                    </div>
                </div>

            </div>

        </div>
    </div>

    {{-- ================= PENUTUP ================= --}}
    <div class="border-t border-emerald-800/40 text-center py-8 px-4">

        <p class="mt-4 text-emerald-400 italic text-xs">
            بارك الله فيكم وجزاكم الله خيرًا
        </p>

        <p class="mt-6 text-xs text-emerald-500/90">
            © {{ date('Y') }} <span class="font-semibold">DKM Masjid Raudhotul Jannah TCE</span><br>
            Website resmi pengelolaan informasi, dakwah, dan pelayanan jamaah.
        </p>

    </div>

</footer>