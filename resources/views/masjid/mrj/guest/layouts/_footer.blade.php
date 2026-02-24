<footer class="mt-20 bg-gradient-to-b from-emerald-900 via-emerald-950 to-slate-950 text-emerald-50">

    {{-- ================= DOA PENUTUP ================= --}}
    <div class="max-w-4xl mx-auto text-center px-6 pt-16 pb-12">
        <h3 class="text-2xl font-semibold text-emerald-200 mb-4">
            Mohon Doa Jamaah
        </h3>

        <p class="text-emerald-100/90 leading-relaxed">
            Semoga masjid ini selalu dipenuhi orang-orang yang memakmurkannya,
            menjadi tempat sujud, tempat belajar Al-Qur’an,
            dan tempat kembali bagi setiap hati yang ingin mendekat kepada Allah.
        </p>

        <p class="mt-5 text-emerald-300 font-medium">
            Mohon doa dan dukungan jamaah agar masjid ini terus hidup dan bermanfaat.
        </p>

        <p class="mt-6 text-sm text-emerald-200/90 italic">
            Jika berkenan, mari ikut mengambil bagian dalam kebaikan Ramadhan di masjid kita.
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
                    Masjid sebagai pusat ibadah, dakwah, dan pelayanan umat.
                    Semoga setiap langkah menuju masjid menjadi pahala kebaikan.
                </p>

                <div class="mt-5 text-sm text-emerald-200">
                    <p>{{ $profil->alamat ?? 'Alamat masjid belum tersedia' }}</p>
                </div>
            </div>


            {{-- KONTAK JAMAAH --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Kontak Jamaah</h4>

                <div class="space-y-3 text-sm text-emerald-100/90">

                    <div class="flex items-center gap-3">
                        <span class="text-xl">📞</span>
                        <span>{!! profil('no_wa') ?? '0895704043814' !!}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xl">🕒</span>
                        <span>Terbuka setiap waktu sholat</span>
                    </div>

                </div>

                {{-- Tombol WA Admin --}}
                @php
                    $wa = preg_replace('/[^0-9]/', '', profil('no_wa') ?? '62895704043814');
                @endphp

                <a href="https://wa.me/{{ $wa }}"
                   target="_blank"
                   class="inline-block mt-5 px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-lg transition">
                    💬 Hubungi Admin Masjid
                </a>

                <p class="mt-4 text-xs text-emerald-200/80 leading-relaxed">
                    Untuk pertanyaan atau bantuan, silakan hubungi admin masjid melalui WhatsApp di atas.
                    Konfirmasi donasi dilakukan melalui tombol konfirmasi pada bagian donasi.
                </p>
            </div>

        </div>
    </div>


    {{-- ================= PENUTUP ================= --}}
    <div class="border-t border-emerald-800/40 text-center py-8 px-4">

        <p class="text-sm text-emerald-200">
            Amanah jamaah dicatat dan dilaporkan setiap malam oleh panitia.
        </p>

        <p class="text-sm text-emerald-300 mt-1">
            Semoga menjadi amal jariyah bagi semua yang terlibat.
        </p>

        <p class="mt-4 text-emerald-400 italic text-xs">
            بارك الله فيكم وجزاكم الله خيرًا
        </p>

    </div>

</footer>