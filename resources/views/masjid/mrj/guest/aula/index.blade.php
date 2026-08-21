@extends('masjid.master-guest')

@section('title', 'Sewa Aula Masjid Raudatul Jannah | Taman Cipulir Estate')

@push('head')
    <meta name="description" content="Aula Masjid Raudatul Jannah untuk berbagai kebutuhan acara dengan fasilitas lengkap, nyaman, dan kapasitas hingga 1.000 orang.">
    <meta property="og:title" content="Sewa Aula Masjid Raudatul Jannah | Taman Cipulir Estate">
    <meta property="og:description" content="Ruang nyaman dan representatif untuk momen berkesan, dengan kapasitas hingga 1.000 orang.">
    <meta property="og:image" content="{{ asset('assets/mrj/aula/hero-aula.png') }}">
@endpush

@section('content')
    @php
        $images = [
            'hero' => asset('assets/mrj/aula/hero-aula.png'),
            'interior' => asset('assets/mrj/aula/suasana-aula.png'),
            'setup' => asset('assets/mrj/aula/setup-meja-kursi-aula.png'),
        ];
        $bookingUrl = 'https://wa.me/'.waNumberInternational().'?text='.rawurlencode('Assalamu\'alaikum, saya ingin mengecek ketersediaan Aula Masjid Raudatul Jannah.');
        $managerWhatsAppUrl = 'https://wa.me/6285716503815?text='.rawurlencode('Assalamu\'alaikum Bapak Joko, saya ingin menanyakan ketersediaan Aula Masjid Raudatul Jannah.');
        $facilities = [
            ['icon' => 'snowflake', 'title' => 'Full AC', 'description' => 'Kenyamanan ruangan selama acara berlangsung.'],
            ['icon' => 'volume-2', 'title' => 'Sound system', 'description' => 'Dukungan audio untuk acara yang tertata.'],
            ['icon' => 'mic-2', 'title' => 'Microphone', 'description' => 'Mendukung sambutan, akad, dan sesi acara.'],
            ['icon' => 'trees', 'title' => 'Halaman', 'description' => 'Area luar yang nyaman untuk kebutuhan acara.'],
            ['icon' => 'contact-round', 'title' => 'Petugas', 'description' => 'Pendampingan selama penggunaan gedung.'],
            ['icon' => 'sparkles', 'title' => 'Ruang rias', 'description' => 'Ruang persiapan untuk kebutuhan acara.'],
            ['icon' => 'cup-soda', 'title' => 'Dispenser', 'description' => 'Fasilitas minum untuk mendukung kegiatan.'],
            ['icon' => 'shield-check', 'title' => 'Keamanan', 'description' => 'Keamanan selama acara berlangsung.'],
            ['icon' => 'heart-handshake', 'title' => 'Meja akad nikah', 'description' => 'Perlengkapan untuk prosesi akad yang khidmat.'],
            ['icon' => 'file-check-2', 'title' => 'Surat izin acara', 'description' => 'Dokumen izin pesta atau acara tersedia.'],
        ];
        $gallery = [
            ['src' => $images['hero'], 'alt' => 'Panggung acara di Aula Masjid Raudatul Jannah', 'label' => 'Area utama'],
            ['src' => $images['interior'], 'alt' => 'Suasana area masuk Aula Masjid Raudatul Jannah', 'label' => 'Suasana aula'],
            ['src' => $images['setup'], 'alt' => 'Setup meja dan kursi di Aula Masjid Raudatul Jannah', 'label' => 'Setup meja & kursi'],
        ];
    @endphp

    <div class="bg-slate-50 text-slate-800">
        <section id="aula" class="relative isolate min-h-[42rem] overflow-hidden bg-emerald-950 lg:min-h-[44rem]">
            <img src="{{ $images['hero'] }}" alt="Panggung acara di Aula Masjid Raudatul Jannah" class="absolute inset-0 -z-20 h-full w-full object-cover object-center" fetchpriority="high">
            <div class="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950/75 via-emerald-950/50 to-emerald-950/5"></div>
            <div class="absolute inset-0 -z-10 bg-gradient-to-t from-slate-950/25 via-transparent to-emerald-950/5"></div>

            <div class="container mx-auto flex min-h-[42rem] items-center px-5 py-16 sm:px-8 sm:py-20 lg:min-h-[44rem] lg:px-12 xl:px-20">
                <div class="max-w-3xl">
                    <span class="inline-flex items-center gap-2 rounded-full border border-amber-300/30 bg-amber-300/10 px-4 py-2 text-xs font-semibold tracking-[0.16em] text-amber-100 shadow-sm backdrop-blur-sm">
                        <i data-lucide="building-2" class="h-4 w-4" aria-hidden="true"></i>
                        AULA MASJID RAUDATUL JANNAH
                    </span>
                    <h1 class="mt-6 text-4xl font-bold leading-tight tracking-tight text-white sm:text-5xl lg:text-6xl">
                        Ruang Nyaman untuk
                        <span class="text-amber-200">Momen Berkesan</span>
                    </h1>
                    <p class="mt-6 max-w-2xl text-base leading-8 text-emerald-50/90 sm:text-lg">
                        Hadirkan acara Anda di Aula Masjid Raudatul Jannah dengan fasilitas lengkap, nyaman, dan suasana yang representatif.
                    </p>

                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-300 px-6 py-3.5 text-sm font-bold text-emerald-950 shadow-lg shadow-black/20 transition hover:bg-amber-200 focus:outline-none focus:ring-4 focus:ring-amber-200/40">
                            Cek Ketersediaan
                            <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                        <a href="#fasilitas" class="inline-flex items-center justify-center gap-2 rounded-xl border border-white/30 bg-white/10 px-6 py-3.5 text-sm font-semibold text-white backdrop-blur-sm transition hover:bg-white/20 focus:outline-none focus:ring-4 focus:ring-white/25">
                            Lihat Fasilitas
                            <i data-lucide="arrow-down" class="h-4 w-4" aria-hidden="true"></i>
                        </a>
                    </div>

                    <dl class="mt-10 grid max-w-2xl grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-white/10 bg-white/10 px-4 py-3.5 backdrop-blur-sm">
                            <dt class="text-xs text-emerald-100/70">Kapasitas</dt>
                            <dd class="mt-1 text-sm font-semibold text-white">Hingga 1.000 orang</dd>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 px-4 py-3.5 backdrop-blur-sm">
                            <dt class="text-xs text-emerald-100/70">Hari penggunaan</dt>
                            <dd class="mt-1 text-sm font-semibold text-white">Sabtu &amp; Minggu</dd>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-white/10 px-4 py-3.5 backdrop-blur-sm">
                            <dt class="text-xs text-emerald-100/70">Mulai dari</dt>
                            <dd class="mt-1 text-sm font-semibold text-white">Rp8.000.000</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </section>

        <section aria-label="Informasi singkat Aula" class="py-8 sm:py-12">
            <div class="container mx-auto px-5 lg:px-8">
                <dl class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="flex gap-4 border-b border-slate-100 p-5 sm:border-r lg:border-b-0">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><i data-lucide="users-round" class="h-5 w-5" aria-hidden="true"></i></span>
                        <div><dt class="text-xs font-medium text-slate-500">Kapasitas</dt><dd class="mt-1 font-semibold text-slate-900">Hingga 1.000 orang</dd></div>
                    </div>
                    <div class="flex gap-4 border-b border-slate-100 p-5 lg:border-b-0 lg:border-r">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><i data-lucide="calendar-days" class="h-5 w-5" aria-hidden="true"></i></span>
                        <div><dt class="text-xs font-medium text-slate-500">Hari penggunaan</dt><dd class="mt-1 font-semibold text-slate-900">Sabtu &amp; Minggu</dd></div>
                    </div>
                    <div class="flex gap-4 border-b border-slate-100 p-5 sm:border-r sm:border-b-0">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><i data-lucide="wallet-cards" class="h-5 w-5" aria-hidden="true"></i></span>
                        <div><dt class="text-xs font-medium text-slate-500">Harga sewa</dt><dd class="mt-1 font-semibold text-slate-900">Rp8.000.000</dd></div>
                    </div>
                    <div class="flex gap-4 p-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700"><i data-lucide="map-pin" class="h-5 w-5" aria-hidden="true"></i></span>
                        <div><dt class="text-xs font-medium text-slate-500">Lokasi</dt><dd class="mt-1 font-semibold text-slate-900">Masjid Raudatul Jannah</dd></div>
                    </div>
                </dl>
            </div>
        </section>

        <section class="py-14 sm:py-20">
            <div class="container mx-auto grid items-center gap-10 px-5 lg:grid-cols-2 lg:px-8">
                <div class="max-w-xl">
                    <span class="text-sm font-bold tracking-[0.18em] text-emerald-700">TENTANG AULA</span>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Aula Masjid Raudatul Jannah</h2>
                    <p class="mt-5 leading-8 text-slate-600">
                        Ruang serbaguna yang dirancang untuk menyambut berbagai kebutuhan acara—mulai dari akad nikah, pengajian, seminar, pertemuan, hingga kegiatan keluarga dan komunitas yang diperbolehkan.
                    </p>
                    <p class="mt-4 leading-8 text-slate-600">
                        Dengan suasana yang tertata dan fasilitas yang memadai, kami membantu setiap penyelenggaraan terasa nyaman, khidmat, dan berkesan.
                    </p>
                    <a href="#harga" class="mt-7 inline-flex items-center gap-2 text-sm font-bold text-emerald-700 transition hover:text-emerald-800">
                        Lihat informasi harga <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="relative">
                    <div class="absolute -inset-3 rounded-[2rem] bg-amber-100/80"></div>
                    <img src="{{ $images['interior'] }}" alt="Suasana area masuk Aula Masjid Raudatul Jannah" class="relative aspect-square w-full rounded-[1.5rem] object-cover shadow-xl shadow-emerald-950/15">
                    <div class="absolute bottom-5 left-5 rounded-xl border border-white/30 bg-emerald-950/85 px-4 py-3 text-sm font-medium text-white shadow-lg backdrop-blur-sm">
                        Ruang yang rapi untuk setiap momen penting
                    </div>
                </div>
            </div>
        </section>

        <section id="fasilitas" class="bg-emerald-950 py-14 text-white sm:py-20">
            <div class="container mx-auto px-5 lg:px-8">
                <div class="max-w-2xl">
                    <span class="text-sm font-bold tracking-[0.18em] text-amber-200">FASILITAS</span>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Fasilitas Lengkap untuk Acara Anda</h2>
                    <p class="mt-4 leading-8 text-emerald-100/75">Kebutuhan dasar acara telah kami siapkan agar Anda dapat fokus pada momen yang akan dijalankan.</p>
                </div>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
                    @foreach ($facilities as $facility)
                        <article class="group rounded-2xl border border-white/10 bg-white/5 p-5 transition duration-300 hover:-translate-y-1 hover:border-amber-200/40 hover:bg-white/10">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-200/10 text-amber-200"><i data-lucide="{{ $facility['icon'] }}" class="h-5 w-5" aria-hidden="true"></i></span>
                            <h3 class="mt-4 font-semibold text-white">{{ $facility['title'] }}</h3>
                            <p class="mt-2 text-sm leading-6 text-emerald-100/65">{{ $facility['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="galeri" class="py-14 sm:py-20">
            <div class="container mx-auto px-5 lg:px-8">
                <div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                    <div class="max-w-2xl">
                        <span class="text-sm font-bold tracking-[0.18em] text-emerald-700">GALERI</span>
                        <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Galeri Aula</h2>
                        <p class="mt-4 leading-8 text-slate-600">Lihat suasana area utama, dekorasi, serta setup meja dan kursi di Aula Masjid Raudatul Jannah.</p>
                    </div>
                    <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700"><i data-lucide="images" class="h-4 w-4" aria-hidden="true"></i> Klik foto untuk memperbesar</span>
                </div>

                <div class="mt-9 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($gallery as $index => $image)
                        <button type="button" class="aula-gallery-item group relative aspect-[4/3] overflow-hidden rounded-2xl bg-emerald-950 text-left shadow-lg shadow-emerald-950/10 transition duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-emerald-950/15 focus:outline-none focus:ring-4 focus:ring-emerald-500/40 md:last:col-span-2 md:last:aspect-[11/4] lg:last:col-span-1 lg:last:aspect-[4/3]" data-image="{{ $image['src'] }}" data-alt="{{ $image['alt'] }}" data-label="{{ $image['label'] }}" aria-haspopup="dialog">
                            <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" class="h-full w-full object-cover transition duration-500 group-hover:scale-105">
                            <span class="absolute inset-0 bg-gradient-to-t from-slate-950/55 via-transparent to-transparent"></span>
                            <span class="absolute bottom-0 left-0 right-0 flex items-center justify-between gap-3 p-4 text-sm font-semibold text-white"><span>{{ $image['label'] }}</span><i data-lucide="maximize-2" class="h-4 w-4 opacity-0 transition group-hover:opacity-100" aria-hidden="true"></i></span>
                        </button>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="harga" class="bg-emerald-50/70 py-14 sm:py-20">
            <div class="container mx-auto px-5 lg:px-8">
                <div class="mx-auto max-w-3xl rounded-[2rem] border border-emerald-100 bg-white p-6 shadow-xl shadow-emerald-950/10 sm:p-10">
                    <div class="grid gap-8 md:grid-cols-[1fr_auto] md:items-center">
                        <div>
                            <span class="text-sm font-bold tracking-[0.18em] text-emerald-700">HARGA SEWA</span>
                            <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Harga Sewa Aula</h2>
                            <p class="mt-3 text-slate-600">Ketentuan lainnya mengikuti peraturan penggunaan gedung.</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-950 px-6 py-5 text-left text-white md:text-right">
                            <p class="text-sm text-emerald-100/70">Harga utama</p>
                            <p class="mt-1 text-3xl font-bold text-amber-200 sm:text-4xl">Rp8.000.000</p>
                            <p class="mt-1 text-sm text-emerald-100/80">Delapan juta rupiah</p>
                        </div>
                    </div>
                    <div class="mt-8 flex flex-col gap-5 border-t border-slate-100 pt-7 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700"><i data-lucide="badge-percent" class="h-6 w-6" aria-hidden="true"></i></span>
                            <div><p class="text-xs font-bold tracking-[0.13em] text-amber-700">KHUSUS WARGA TCE</p><p class="mt-1 text-lg font-bold text-slate-900">Diskon 25%</p></div>
                        </div>
                        <a href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">Tanyakan ketersediaan <i data-lucide="arrow-up-right" class="h-4 w-4" aria-hidden="true"></i></a>
                    </div>
                </div>
            </div>
        </section>

        <section id="ketentuan" class="py-14 sm:py-20">
            <div class="container mx-auto grid gap-10 px-5 lg:grid-cols-[0.78fr_1.22fr] lg:px-8">
                <div class="max-w-md">
                    <span class="text-sm font-bold tracking-[0.18em] text-emerald-700">INFORMASI PENTING</span>
                    <h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Ketentuan Penggunaan</h2>
                    <p class="mt-5 leading-8 text-slate-600">Informasi diringkas agar mudah dipahami. Buka tiap kategori untuk melihat ketentuan yang perlu diperhatikan sebelum pemesanan.</p>
                </div>
                <div class="space-y-3">
                    <details open class="group rounded-2xl border border-slate-200 bg-white px-5 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-semibold text-slate-900"><span class="flex items-center gap-3"><i data-lucide="calendar-check" class="h-5 w-5 text-emerald-600" aria-hidden="true"></i>Pemesanan</span><i data-lucide="chevron-down" class="h-5 w-5 text-slate-400 transition group-open:rotate-180" aria-hidden="true"></i></summary>
                        <ul class="space-y-2 border-t border-slate-100 pb-5 pt-4 text-sm leading-6 text-slate-600"><li>Pemesanan sementara berlaku selama 4 hari.</li><li>Jika tidak ada konfirmasi dalam 4 hari, pemesanan dianggap batal.</li></ul>
                    </details>
                    <details class="group rounded-2xl border border-slate-200 bg-white px-5 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-semibold text-slate-900"><span class="flex items-center gap-3"><i data-lucide="credit-card" class="h-5 w-5 text-emerald-600" aria-hidden="true"></i>Pembayaran</span><i data-lucide="chevron-down" class="h-5 w-5 text-slate-400 transition group-open:rotate-180" aria-hidden="true"></i></summary>
                        <ul class="space-y-2 border-t border-slate-100 pb-5 pt-4 text-sm leading-6 text-slate-600"><li>DP Rp2.000.000.</li><li>Pelunasan gedung paling lambat 4 minggu sebelum acara.</li><li>Catering, dekorasi, dan AC paling lambat 1 minggu sebelum acara.</li></ul>
                    </details>
                    <details class="group rounded-2xl border border-slate-200 bg-white px-5 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-semibold text-slate-900"><span class="flex items-center gap-3"><i data-lucide="clock-3" class="h-5 w-5 text-emerald-600" aria-hidden="true"></i>Waktu Penggunaan</span><i data-lucide="chevron-down" class="h-5 w-5 text-slate-400 transition group-open:rotate-180" aria-hidden="true"></i></summary>
                        <ul class="space-y-2 border-t border-slate-100 pb-5 pt-4 text-sm leading-6 text-slate-600"><li>Sabtu dan Minggu, pagi hingga sebelum masuk waktu Ashar.</li><li>Persiapan dekorasi diperbolehkan sebelum waktu penggunaan.</li><li>AC digunakan ketika waktu penggunaan gedung dimulai.</li></ul>
                    </details>
                    <details class="group rounded-2xl border border-slate-200 bg-white px-5 shadow-sm">
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 py-5 font-semibold text-slate-900"><span class="flex items-center gap-3"><i data-lucide="sparkles" class="h-5 w-5 text-emerald-600" aria-hidden="true"></i>Kebersihan</span><i data-lucide="chevron-down" class="h-5 w-5 text-slate-400 transition group-open:rotate-180" aria-hidden="true"></i></summary>
                        <ul class="space-y-2 border-t border-slate-100 pb-5 pt-4 text-sm leading-6 text-slate-600"><li>Sisa dekorasi wajib dibersihkan.</li><li>Sampah dekorasi dan catering wajib dibawa keluar.</li><li>Kebersihan area menjadi tanggung jawab penyewa.</li></ul>
                    </details>
                </div>
            </div>
        </section>

        <section class="bg-slate-900 py-14 text-white sm:py-20">
            <div class="container mx-auto px-5 lg:px-8">
                <div class="max-w-2xl"><span class="text-sm font-bold tracking-[0.18em] text-amber-200">TRANSPARAN SEJAK AWAL</span><h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">Biaya Tambahan</h2><p class="mt-4 leading-8 text-slate-300">Kami menyampaikan biaya tambahan secara jelas agar kebutuhan acara dapat direncanakan dengan nyaman.</p></div>
                <div class="mt-9 grid gap-4 md:grid-cols-3">
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6"><i data-lucide="store" class="h-6 w-6 text-amber-200" aria-hidden="true"></i><h3 class="mt-5 text-lg font-semibold">Vendor dari luar</h3><p class="mt-3 text-sm leading-7 text-slate-300">Dekorasi, catering, rias, dan/atau fotografer dari luar dikenakan <strong class="text-white">5% dari total nilai jasa</strong>.</p></article>
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6"><i data-lucide="wind" class="h-6 w-6 text-amber-200" aria-hidden="true"></i><h3 class="mt-5 text-lg font-semibold">AC tambahan</h3><p class="mt-3 text-sm leading-7 text-slate-300">Penambahan penggunaan AC dikenakan biaya <strong class="text-white">Rp1.000.000</strong>.</p></article>
                    <article class="rounded-2xl border border-white/10 bg-white/5 p-6"><i data-lucide="fan" class="h-6 w-6 text-amber-200" aria-hidden="true"></i><h3 class="mt-5 text-lg font-semibold">Fasilitas tambahan</h3><p class="mt-3 text-sm leading-7 text-slate-300">Fasilitas tambahan yang tersedia: <strong class="text-white">kipas angin</strong>.</p></article>
                </div>
            </div>
        </section>

        <section class="py-14 sm:py-20">
            <div class="container mx-auto grid gap-10 px-5 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <div class="max-w-md"><span class="text-sm font-bold tracking-[0.18em] text-emerald-700">PERLU DIPERHATIKAN</span><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Kebijakan Pembatalan</h2><p class="mt-5 leading-8 text-slate-600">Apabila ada perubahan rencana, mohon sampaikan kepada pengelola sedini mungkin.</p></div>
                <ol class="relative space-y-6 border-l-2 border-emerald-100 pl-7">
                    <li class="relative"><span class="absolute -left-[2.2rem] flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">1</span><h3 class="font-semibold text-slate-900">Setelah pembayaran DP</h3><p class="mt-1 text-sm leading-6 text-slate-600">Pembatalan setelah pembayaran DP menyebabkan DP hangus.</p></li>
                    <li class="relative"><span class="absolute -left-[2.2rem] flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">2</span><h3 class="font-semibold text-slate-900">Setelah pelunasan</h3><p class="mt-1 text-sm leading-6 text-slate-600">Pembatalan setelah pelunasan dipotong 50% di luar DP.</p></li>
                    <li class="relative"><span class="absolute -left-[2.2rem] flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">3</span><h3 class="font-semibold text-slate-900">Kurang dari 1 minggu sebelum acara</h3><p class="mt-1 text-sm leading-6 text-slate-600">Seluruh pembayaran hangus.</p></li>
                    <li class="relative"><span class="absolute -left-[2.2rem] flex h-7 w-7 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">4</span><h3 class="font-semibold text-slate-900">Perubahan tanggal</h3><p class="mt-1 text-sm leading-6 text-slate-600">Diperbolehkan jika tanggal baru masih tersedia, dan hanya dapat dilakukan 1 kali.</p></li>
                </ol>
            </div>
            <div class="container mx-auto mt-8 px-5 lg:px-8"><p class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900"><i data-lucide="info" class="mr-2 inline-block h-4 w-4 align-text-bottom" aria-hidden="true"></i>Dalam kondisi di luar kemampuan manusia, kebijakan dapat dipertimbangkan oleh pengelola.</p></div>
        </section>

        <section class="bg-amber-50 py-14 sm:py-20">
            <div class="container mx-auto px-5 lg:px-8"><div class="max-w-2xl"><span class="text-sm font-bold tracking-[0.18em] text-amber-800">ATURAN PENTING</span><h2 class="mt-3 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl">Kenyamanan Bersama Dimulai dari Kita</h2></div>
                <ul class="mt-9 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (['Menjaga kebersihan aula.', 'Tidak merusak atau mengotori dinding.', 'Barang pribadi menjadi tanggung jawab penyewa.', 'Kegiatan dihentikan sementara ketika adzan berkumandang.', 'Sampah catering dan dekorasi wajib dibawa keluar.', 'Pemadaman listrik PLN tidak menjadi tanggung jawab pengelola.'] as $rule)
                        <li class="flex gap-3 rounded-xl border border-amber-200/80 bg-white/70 p-4 text-sm leading-6 text-slate-700"><i data-lucide="triangle-alert" class="mt-0.5 h-5 w-5 shrink-0 text-amber-600" aria-hidden="true"></i><span>{{ $rule }}</span></li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section id="booking" class="relative isolate overflow-hidden bg-gradient-to-br from-emerald-800 via-emerald-800 to-emerald-950 pb-0 pt-12 text-white sm:pt-14">
            <div class="absolute inset-0 -z-10 bg-[radial-gradient(circle_at_top_right,_rgba(251,191,36,.18),transparent_28rem)]"></div>
            <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-amber-200/60 to-transparent"></div>

            <div class="container mx-auto px-5 text-center lg:px-8">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-xs font-bold tracking-[0.16em] text-amber-100 shadow-sm shadow-emerald-950/20 backdrop-blur-sm">
                    <i data-lucide="messages-square" class="h-4 w-4" aria-hidden="true"></i>
                    HUBUNGI PENGELOLA
                </span>
                <h2 class="mx-auto mt-5 max-w-3xl text-3xl font-bold tracking-tight sm:text-5xl">
                    Siap Menyiapkan <span class="text-amber-200">Acara Anda?</span>
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-emerald-50/85 sm:text-base sm:leading-8">
                    Hubungi pengelola Aula Masjid Raudatul Jannah untuk mengecek ketersediaan tanggal dan informasi lebih lanjut.
                </p>

                <div class="mx-auto mt-8 grid max-w-3xl gap-3 text-left lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                    <a href="{{ $bookingUrl }}" target="_blank" rel="noopener noreferrer" class="group inline-flex min-h-[5.5rem] items-center justify-center gap-2 rounded-2xl bg-amber-300 px-5 py-4 text-center text-sm font-bold text-emerald-950 shadow-lg shadow-emerald-950/20 transition duration-300 hover:-translate-y-0.5 hover:bg-amber-200 hover:shadow-xl hover:shadow-emerald-950/25 focus:outline-none focus:ring-4 focus:ring-amber-200/40">
                        <span>Cek Ketersediaan Aula</span>
                        <i data-lucide="calendar-search" class="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" aria-hidden="true"></i>
                    </a>

                    <a href="{{ $managerWhatsAppUrl }}" target="_blank" rel="noopener noreferrer" class="group flex min-h-[5.5rem] items-center gap-3 rounded-2xl border border-white/20 bg-white/10 px-4 py-3.5 shadow-lg shadow-emerald-950/15 backdrop-blur-sm transition duration-300 hover:-translate-y-0.5 hover:border-amber-100/45 hover:bg-white/15 hover:shadow-xl hover:shadow-emerald-950/20 focus:outline-none focus:ring-4 focus:ring-white/25">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-950/30 text-amber-200 ring-1 ring-white/15">
                            <i data-lucide="message-circle" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-semibold uppercase tracking-[0.14em] text-emerald-100/75">Hubungi Pengelola</span>
                            <span class="mt-0.5 block text-base font-bold tracking-tight text-white sm:text-lg">+62 857-1650-3815</span>
                            <span class="block text-sm text-amber-100">Bapak Joko</span>
                        </span>
                        <i data-lucide="arrow-up-right" class="h-4 w-4 shrink-0 text-amber-200 transition-transform duration-300 group-hover:-translate-y-0.5 group-hover:translate-x-0.5" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <div class="relative -mb-px mt-8 leading-[0] sm:mt-10" aria-hidden="true">
                <svg viewBox="0 0 1440 72" class="block h-auto w-full" preserveAspectRatio="none">
                    <path fill="#f8fafc" d="M0,28C218,56,431,58,720,32C1006,6,1210,56,1440,34L1440,72L0,72Z"></path>
                </svg>
            </div>
        </section>
    </div>

    <dialog id="aulaGalleryModal" class="modal p-4 backdrop:bg-slate-950/80" aria-labelledby="aulaGalleryModalTitle">
        <div class="relative max-h-[90vh] max-w-5xl overflow-hidden rounded-2xl bg-slate-950 p-2 shadow-2xl">
            <button type="button" id="closeAulaGalleryModal" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/90 text-slate-900 shadow-lg transition hover:bg-white focus:outline-none focus:ring-4 focus:ring-emerald-400" aria-label="Tutup galeri"><i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i></button>
            <img id="aulaGalleryModalImage" src="" alt="" class="max-h-[78vh] w-full rounded-xl object-contain">
            <p id="aulaGalleryModalTitle" class="px-3 py-3 text-sm font-medium text-white"></p>
        </div>
        <form method="dialog" class="modal-backdrop"><button aria-label="Tutup galeri">Tutup</button></form>
    </dialog>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.lucide?.createIcons();

            const modal = document.getElementById('aulaGalleryModal');
            const image = document.getElementById('aulaGalleryModalImage');
            const title = document.getElementById('aulaGalleryModalTitle');

            document.querySelectorAll('.aula-gallery-item').forEach(function (item) {
                item.addEventListener('click', function () {
                    image.src = item.dataset.image;
                    image.alt = item.dataset.alt;
                    title.textContent = item.dataset.label;
                    modal.showModal();
                });
            });

            document.getElementById('closeAulaGalleryModal').addEventListener('click', function () {
                modal.close();
            });
        });
    </script>
@endpush
