<footer class="mt-20 bg-gradient-to-b from-emerald-900 via-emerald-950 to-slate-950 text-emerald-50">

    {{-- ================= DOA PENUTUP ================= --}}
    <div class="max-w-4xl mx-auto text-center px-6 pt-16 pb-12">
        <h3 class="text-2xl font-semibold text-emerald-200 mb-4">
            Doakan Kami
        </h3>

        <p class="text-emerald-100/90 leading-relaxed">
            Semoga masjid ini selalu dipenuhi orang-orang yang memakmurkannya,
            menjadi tempat sujud, tempat belajar Al-Qur’an,
            dan tempat kembali bagi setiap hati yang ingin mendekat kepada Allah.
        </p>

        <p class="mt-5 text-emerald-300 font-medium">
            Mohon doa dan dukungan jamaah agar masjid ini terus hidup dan bermanfaat.
        </p>
    </div>


    {{-- ================= GRID FOOTER ================= --}}
    <div class="border-t border-emerald-800/40">
        <div class="container mx-auto px-6 py-14 grid gap-10 md:grid-cols-2 lg:grid-cols-4">

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


            {{-- NAVIGASI JAMAAH --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Navigasi Jamaah</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('home') }}" class="hover:text-white transition">Beranda</a></li>
                    <li><a href="{{ route('home') }}#!jadwal" class="hover:text-white transition">Jadwal Sholat</a></li>
                    <li><a href="{{ route('home') }}#!acara" class="hover:text-white transition">Agenda Kajian</a></li>
                    <li><a href="{{ route('home') }}#!berita" class="hover:text-white transition">Berita Masjid</a></li>
                    <li><a href="{{ route('home') }}#!donasi" class="hover:text-white transition">Infaq & Sedekah</a></li>
                    <li><a href="{{ route('home') }}#!kontak" class="hover:text-white transition">Kontak Jamaah</a></li>
                </ul>
            </div>


            {{-- AMANAH JAMAAH --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Amanah Jamaah</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('guest.laporan-harian') }}" class="hover:text-white transition">Laporan Ramadhan</a></li>
                    <li><a href="{{ route('home') }}#!donasi" class="hover:text-white transition">Program Sosial</a></li>
                    <li><a href="{{ route('home') }}#!donasi" class="hover:text-white transition">Penyaluran Donasi</a></li>
                    <li><a href="{{ route('home') }}#!berita" class="hover:text-white transition">Pengumuman</a></li>
                </ul>
            </div>


            {{-- KONTAK JAMAAH --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Kontak Jamaah</h4>

                <div class="space-y-3 text-sm text-emerald-100/90">

                    <div class="flex items-center gap-3">
                        <span class="text-xl">📞</span>
                        <span>{{ $profil->telepon ?? '-' }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xl">✉️</span>
                        <span>{{ $profil->email ?? '-' }}</span>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="text-xl">🕒</span>
                        <span>Terbuka setiap waktu sholat</span>
                    </div>

                </div>

                {{-- Tombol WA --}}
                @php
                    $wa = preg_replace('/[^0-9]/', '', $profil->telepon ?? '62895704043814');
                @endphp

                <a href="https://wa.me/{{ $wa }}"
                   target="_blank"
                   class="inline-block mt-5 px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-lg transition">
                    💬 Hubungi via WhatsApp
                </a>
            </div>

        </div>
    </div>


    {{-- ================= COPYRIGHT / PENUTUP ================= --}}
    <div class="border-t border-emerald-800/40 text-center py-8 px-4">

        <p class="text-sm text-emerald-200">
            Website ini dibuat untuk pelayanan jamaah dan kemakmuran masjid.
        </p>

        <p class="text-sm text-emerald-300 mt-1">
            Semoga menjadi amal jariyah bagi semua yang terlibat.
        </p>

        <p class="mt-4 text-emerald-400 italic text-xs">
            بارك الله فيكم وجزاكم الله خيرًا
        </p>

    </div>

</footer>