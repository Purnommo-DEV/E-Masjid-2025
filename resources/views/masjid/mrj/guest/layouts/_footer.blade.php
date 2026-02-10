<footer class="border-t border-white/10 bg-slate-950/90">
    <div class="container mx-auto px-4 py-5 flex flex-col sm:flex-row items-center justify-between gap-3 text-[11px] text-slate-400">

        {{-- COPYRIGHT --}}
        <div class="text-center sm:text-left">
            © {{ date('Y') }} {!! profil('nama') ?? 'Masjid' !!} —
            Sistem Informasi Masjid.
        </div>

        {{-- LINK CEPAT --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('home') }}" class="hover:text-emerald-300 transition">Beranda</a>
            <a href="#berita" class="hover:text-emerald-300 transition">Berita</a>
            <a href="#donasi" class="hover:text-emerald-300 transition">Donasi</a>
            <a href="#kontak" class="hover:text-emerald-300 transition">Kontak</a>
        </div>
    </div>
</footer>
