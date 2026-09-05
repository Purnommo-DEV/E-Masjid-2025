@extends('masjid.master-guest')

@section('title', 'Laporan Realisasi Distribusi Sembako 107 Paket')

@php
    // Seluruh nilai pada halaman ini sengaja statis untuk laporan informasi tahap awal.
    $shoppingRows = [
        ['item' => 'Beras 5kg', 'quantity' => '107', 'unit_price' => 'Rp83.000', 'total' => 'Rp8.881.000'],
        ['item' => 'Indomie 5 bungkus', 'quantity' => '107', 'unit_price' => 'Rp15.000', 'total' => 'Rp1.605.000'],
        ['item' => 'Minyak Sania', 'quantity' => '60', 'unit_price' => 'Rp21.500', 'total' => 'Rp1.290.000'],
        ['item' => 'Minyak Bimoli', 'quantity' => '47', 'unit_price' => 'Rp21.900', 'total' => 'Rp1.029.300'],
        ['item' => 'Plastik Packing', 'quantity' => '1', 'unit_price' => 'Rp120.000', 'total' => 'Rp120.000'],
    ];
    $distributionGroups = [
        ['group' => 'Pak Wakidjo', 'recipients' => 10],
        ['group' => 'Pak Anas', 'recipients' => 9],
        ['group' => 'Pak Maman', 'recipients' => 22],
        ['group' => 'Pak Arif', 'recipients' => 9],
        ['group' => 'Pak Wawang', 'recipients' => 12],
        ['group' => 'Pak Parno', 'recipients' => 7],
        ['group' => 'Bu Susi', 'recipients' => 8],
        ['group' => 'TCE', 'recipients' => 29],
    ];
@endphp

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .sembako-report { color: #17362a; font-family: 'DM Sans', sans-serif; }
        .sembako-report .report-serif { font-family: 'Playfair Display', serif; }
        .sembako-report .report-card { box-shadow: 0 18px 48px rgba(6, 78, 59, .08); }
        .sembako-report .hero-orbit { background: radial-gradient(circle at 50% 50%, rgba(251, 191, 36, .20) 0 2px, transparent 3px 100%); background-size: 22px 22px; mask-image: linear-gradient(to bottom, rgba(0, 0, 0, .85), transparent); }
        .sembako-report .fund-ring { background: conic-gradient(#059669 0 58%, #f59e0b 58% 100%); }
        .sembako-report .fund-ring::after { background: #fff; border-radius: 9999px; content: ''; inset: 16px; position: absolute; }
        .sembako-report .flow-line { background: linear-gradient(90deg, #10b981, #f59e0b); height: 2px; }
        @media print {
            #mainNav, #mobileMenuPanel, body > footer, .sembako-report .no-print, .sembako-report .report-actions { display: none !important; }
            .sembako-report { background: #fff !important; padding: 0 !important; }
            .sembako-report .report-card { box-shadow: none !important; break-inside: avoid; }
            @page { size: A4 portrait; margin: 12mm; }
        }
    </style>
@endpush

@section('content')
    <main class="sembako-report min-h-screen bg-gradient-to-b from-emerald-50 via-white to-amber-50/40 px-4 py-8 sm:px-6 sm:py-12">
        <div class="mx-auto max-w-6xl">
            <header class="report-card relative overflow-hidden rounded-[2rem] bg-emerald-950 text-white">
                <div class="hero-orbit pointer-events-none absolute inset-x-0 top-0 h-72 opacity-40"></div>
                <div class="absolute -right-24 -top-20 h-72 w-72 rounded-full bg-emerald-400/15 blur-3xl"></div>
                <div class="absolute -bottom-24 left-1/3 h-64 w-64 rounded-full bg-amber-300/10 blur-3xl"></div>
                <div class="relative px-6 py-9 sm:px-10 sm:py-12 lg:px-14 lg:py-16">
                    <div class="flex flex-col gap-8 lg:flex-row lg:items-end lg:justify-between">
                        <div class="max-w-3xl">
                            <div class="flex items-center gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl border border-emerald-200/30 bg-white/10 p-2 backdrop-blur-sm">
                                    <img src="{{ asset('pwa/mrj-logo.png') }}" alt="Logo Masjid Raudhotul Jannah" class="h-full w-full object-contain">
                                </div>
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Masjid Raudhotul Jannah</p>
                                    <p class="mt-1 text-sm text-emerald-100/75">Taman Cipulir Estate</p>
                                </div>
                            </div>
                            <span class="mt-9 inline-flex items-center rounded-full border border-amber-200/35 bg-amber-300/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[.16em] text-amber-100">Laporan Program</span>
                            <h1 class="report-serif mt-5 text-4xl font-bold leading-tight sm:text-5xl lg:text-6xl">Laporan Realisasi Distribusi Sembako <span class="block text-amber-200">107 Paket</span></h1>
                            <p class="mt-5 max-w-2xl text-base leading-7 text-emerald-50/85 sm:text-lg">Distribusi sembako untuk warga sekitar yang membutuhkan dengan pendanaan dari Dana Fidyah dan Dana Zakat Maal.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2 lg:block lg:min-w-64">
                            <div class="rounded-2xl border border-emerald-200/25 bg-white/10 p-4 backdrop-blur-sm">
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-200">Status Program</p>
                                <p class="mt-2 flex items-center gap-2 text-base font-bold"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-amber-200 text-xs text-amber-950">•</span> 106 / 107 penerima terdata</p>
                            </div>
                            <div class="rounded-2xl border border-emerald-200/25 bg-white/10 p-4 backdrop-blur-sm lg:mt-3">
                                <p class="text-xs font-bold uppercase tracking-wider text-emerald-200">Tanggal Kegiatan</p>
                                <p class="mt-2 text-base font-bold">30 Agustus 2026</p>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <section class="mt-8 grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4" aria-label="Statistik utama program">
                @foreach ([
                    ['number' => '107', 'label' => 'Paket', 'tone' => 'text-emerald-800', 'icon' => '♧'],
                    ['number' => '107', 'label' => 'Target Penerima', 'tone' => 'text-sky-800', 'icon' => '⌘'],
                    ['number' => '106', 'label' => 'Penerima Terdata', 'tone' => 'text-emerald-800', 'icon' => '◈'],
                    ['number' => '1', 'label' => 'Penerima Belum Ditentukan', 'tone' => 'text-amber-800', 'icon' => '◌'],
                ] as $stat)
                    <article class="report-card min-w-0 rounded-2xl border border-emerald-100 bg-white p-4 sm:p-5">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-emerald-50 text-sm {{ $stat['tone'] }}" aria-hidden="true">{{ $stat['icon'] }}</span>
                        <p class="mt-4 break-words text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl {{ $loop->last ? 'whitespace-nowrap text-xl sm:text-xl lg:text-2xl xl:text-3xl' : '' }}">{{ $stat['number'] }}</p>
                        <p class="mt-1 text-xs font-semibold leading-5 text-slate-500 sm:text-sm">{{ $stat['label'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="mt-14 grid gap-6 lg:grid-cols-[1.15fr_.85fr]" aria-labelledby="ringkasan-title">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Ringkasan Program</p>
                    <h2 id="ringkasan-title" class="report-serif mt-2 text-3xl font-bold text-emerald-950">Bantuan yang direncanakan, direalisasikan, dan ditelusuri.</h2>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-slate-600">Program distribusi sembako ini direalisasikan sebanyak <strong class="font-bold text-emerald-900">107 paket</strong> untuk warga sekitar yang membutuhkan.</p>
                    <div class="mt-6 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">
                        <p class="text-sm font-semibold text-slate-700">Anggaran awal</p>
                        <p class="mt-2 text-lg font-bold text-emerald-950">107 paket × Rp120.000 <span class="mx-2 text-slate-300">=</span> Rp12.840.000</p>
                    </div>
                </div>
                <aside class="report-card rounded-2xl border border-amber-100 bg-amber-50 p-5 sm:p-6">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Perbandingan Anggaran</p>
                    <dl class="mt-5 space-y-4">
                        <div class="flex items-end justify-between gap-4"><dt class="text-sm text-slate-600">Anggaran awal</dt><dd class="whitespace-nowrap text-lg font-bold text-slate-900">Rp12.840.000</dd></div>
                        <div class="h-px bg-amber-200"></div>
                        <div class="flex items-end justify-between gap-4"><dt class="text-sm text-slate-600">Realisasi</dt><dd class="whitespace-nowrap text-xl font-bold text-emerald-800">Rp12.925.300</dd></div>
                        <div class="rounded-xl bg-white/80 p-3"><dt class="text-xs font-bold uppercase tracking-wide text-amber-800">Selisih</dt><dd class="mt-1 text-xl font-bold text-amber-900">+ Rp85.300</dd></div>
                    </dl>
                    <p class="mt-5 text-sm leading-6 text-slate-600">Realisasi lebih tinggi dari anggaran awal sebesar Rp85.300 karena penyesuaian harga pembelian aktual.</p>
                </aside>
            </section>

            <section class="mt-14" aria-labelledby="sumber-title">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Pendanaan Program</p><h2 id="sumber-title" class="report-serif mt-2 text-3xl font-bold text-emerald-950">Sumber Dana</h2></div>
                    <p class="text-sm text-slate-500">Total pendanaan: <span class="font-bold text-slate-800">Rp12.925.300</span></p>
                </div>
                <div class="grid gap-5 lg:grid-cols-[.82fr_1.18fr]">
                    <article class="report-card flex flex-col items-center justify-center rounded-2xl border border-emerald-100 bg-white p-7 text-center">
                        <div class="fund-ring relative h-44 w-44 rounded-full"><div class="absolute inset-4 z-10 flex flex-col items-center justify-center rounded-full bg-white"><span class="text-3xl font-bold text-emerald-950">100%</span><span class="mt-1 text-xs font-semibold uppercase tracking-wider text-slate-500">Terdanai</span></div></div>
                        <p class="mt-5 text-sm text-slate-500">Dana Fidyah dan Dana Zakat Maal membiayai seluruh realisasi program.</p>
                    </article>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <article class="report-card rounded-2xl border border-emerald-100 bg-white p-6"><div class="flex items-start justify-between gap-4"><div><p class="text-sm font-bold text-emerald-900">Dana Fidyah</p><p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Rp7.500.000</p></div><span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">58,0%</span></div><div class="mt-6 h-2 overflow-hidden rounded-full bg-emerald-100"><div class="h-full w-[58%] rounded-full bg-emerald-600"></div></div></article>
                        <article class="report-card rounded-2xl border border-amber-100 bg-white p-6"><div class="flex items-start justify-between gap-4"><div><p class="text-sm font-bold text-amber-900">Dana Zakat Maal</p><p class="mt-3 text-2xl font-bold tracking-tight text-slate-900">Rp5.425.300</p></div><span class="rounded-full bg-amber-100 px-3 py-1 text-sm font-bold text-amber-800">42,0%</span></div><div class="mt-6 h-2 overflow-hidden rounded-full bg-amber-100"><div class="h-full w-[42%] rounded-full bg-amber-500"></div></div></article>
                        <article class="report-card rounded-2xl border border-emerald-100 bg-emerald-800 p-6 text-white sm:col-span-2"><p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-100">Total Sumber Dana</p><p class="mt-3 text-3xl font-bold tracking-tight">Rp12.925.300</p><p class="mt-5 max-w-xl text-sm leading-6 text-emerald-100">Dana Fidyah digunakan sebesar Rp7.500.000. Kekurangan biaya realisasi sebesar Rp5.425.300 dipenuhi dari Dana Zakat Maal.</p></article>
                    </div>
                </div>
            </section>

            <section class="mt-14" aria-labelledby="belanja-title">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Realisasi Pengadaan</p><h2 id="belanja-title" class="report-serif mt-2 text-3xl font-bold text-emerald-950">Realisasi Belanja</h2></div><p class="rounded-full bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-800">Rata-rata biaya per paket: ± Rp120.797</p></div>
                <div class="report-card overflow-hidden rounded-2xl border border-emerald-100 bg-white">
                    <div class="hidden overflow-x-auto md:block"><table class="w-full min-w-[680px] text-left text-sm"><thead class="bg-emerald-50 text-xs font-bold uppercase tracking-wide text-emerald-900"><tr><th class="px-6 py-4">Item</th><th class="px-6 py-4 text-center">Jumlah</th><th class="px-6 py-4 text-right">Harga Satuan</th><th class="px-6 py-4 text-right">Total</th></tr></thead><tbody class="text-slate-700">@foreach($shoppingRows as $row)<tr class="border-t border-slate-100"><td class="px-6 py-4 font-bold text-emerald-950">{{ $row['item'] }}</td><td class="px-6 py-4 text-center">{{ $row['quantity'] }}</td><td class="px-6 py-4 text-right">{{ $row['unit_price'] }}</td><td class="px-6 py-4 text-right font-semibold">{{ $row['total'] }}</td></tr>@endforeach</tbody><tfoot class="bg-emerald-800 text-white"><tr><th colspan="3" class="px-6 py-5 text-right text-base">Total Realisasi</th><th class="px-6 py-5 text-right text-lg">Rp12.925.300</th></tr></tfoot></table></div>
                    <div class="divide-y divide-slate-100 md:hidden">@foreach($shoppingRows as $row)<article class="p-4"><div class="flex items-start justify-between gap-4"><h3 class="font-bold text-emerald-950">{{ $row['item'] }}</h3><p class="whitespace-nowrap font-bold text-slate-900">{{ $row['total'] }}</p></div><dl class="mt-3 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-slate-500">Jumlah</dt><dd class="mt-1 font-semibold">{{ $row['quantity'] }}</dd></div><div><dt class="text-slate-500">Harga satuan</dt><dd class="mt-1 font-semibold">{{ $row['unit_price'] }}</dd></div></dl></article>@endforeach<div class="bg-emerald-800 p-5 text-white"><p class="text-xs font-bold uppercase tracking-wide text-emerald-100">Total Realisasi</p><p class="mt-1 text-2xl font-bold">Rp12.925.300</p></div></div>
                </div>
            </section>

            <section class="mt-14" aria-labelledby="alur-title">
                <div class="mb-6 text-center"><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Alur Pendanaan</p><h2 id="alur-title" class="report-serif mt-2 text-3xl font-bold text-emerald-950">Sumber Dana vs Realisasi</h2></div>
                <div class="report-card overflow-hidden rounded-2xl border border-emerald-100 bg-white p-5 sm:p-8"><div class="grid gap-4 md:grid-cols-[1fr_auto_1fr_auto_1fr_auto_1fr] md:items-center"><div class="rounded-2xl bg-emerald-50 p-4 text-center"><p class="text-sm font-bold text-emerald-900">Dana Fidyah</p><p class="mt-2 text-xl font-bold text-slate-900">Rp7.500.000</p></div><div class="hidden text-2xl font-bold text-emerald-600 md:block">+</div><div class="rounded-2xl bg-amber-50 p-4 text-center"><p class="text-sm font-bold text-amber-900">Dana Zakat Maal</p><p class="mt-2 text-xl font-bold text-slate-900">Rp5.425.300</p></div><div class="hidden text-2xl font-bold text-amber-500 md:block">↓</div><div class="rounded-2xl bg-emerald-900 p-4 text-center text-white"><p class="text-sm font-bold text-emerald-100">Total Realisasi</p><p class="mt-2 text-xl font-bold">Rp12.925.300</p></div><div class="hidden text-2xl font-bold text-emerald-600 md:block">↓</div><div class="rounded-2xl border border-emerald-100 p-4 text-center"><p class="text-sm font-bold text-emerald-900">107 Paket Sembako</p><p class="mt-2 text-sm font-semibold text-slate-600">107 Target Penerima</p><p class="mt-1 text-xs font-bold text-amber-700">106 terdata · 1 belum ditentukan</p></div></div><div class="mt-4 space-y-2 md:hidden"><div class="flow-line mx-auto w-0.5"></div><p class="text-center text-xs font-bold uppercase tracking-wider text-emerald-700">Dibiayai → Direalisasikan → Disalurkan</p></div></div>
            </section>

            <section class="mt-14 grid gap-6 lg:grid-cols-[1.15fr_.85fr]" aria-labelledby="sebaran-title">
                <div><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Distribusi Penerima</p><h2 id="sebaran-title" class="report-serif mt-2 text-3xl font-bold text-emerald-950">Sebaran Penerima Manfaat</h2><p class="mt-4 inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium leading-6 text-emerald-900">Rincian berikut mencatat 106 dari 107 target penerima manfaat.</p><div class="mt-6 grid gap-3 sm:grid-cols-2">@foreach($distributionGroups as $group)<article class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><div class="flex items-start justify-between gap-4"><div><h3 class="font-bold text-emerald-950">{{ $group['group'] }}</h3><p class="mt-1 text-sm text-slate-500">Penerima terdata</p></div><span class="rounded-xl bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-800">{{ $group['recipients'] }} penerima</span></div></article>@endforeach</div></div>
                <aside class="report-card self-start rounded-2xl border border-emerald-100 bg-white p-6"><p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">Progress Distribusi</p><p class="mt-4 text-4xl font-bold tracking-tight text-emerald-950">106 <span class="text-xl text-slate-400">/ 107</span></p><p class="mt-2 text-sm text-slate-600">penerima sudah terdata untuk distribusi.</p><div class="mt-6 h-3 overflow-hidden rounded-full bg-emerald-100"><div class="h-full w-[99.1%] rounded-full bg-emerald-600"></div></div><div class="mt-3 flex justify-between text-xs font-semibold text-slate-500"><span>99,1% terdata</span><span>Target: 107</span></div><div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="text-sm font-bold text-amber-900">1 Penerima Belum Ditentukan</p><p class="mt-2 text-sm leading-6 text-amber-800">1 paket masih dalam proses penentuan penerima.</p></div></aside>
            </section>

            <section class="mt-14 rounded-[2rem] bg-emerald-900 px-5 py-8 text-white sm:px-8 sm:py-10" aria-labelledby="dampak-title"><div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-200">Dampak Program</p><h2 id="dampak-title" class="report-serif mt-2 text-3xl font-bold">Manfaat yang disalurkan untuk sekitar masjid.</h2></div></div><div class="mt-8 grid grid-cols-2 gap-3 lg:grid-cols-4">@foreach([['107', 'Paket sembako'], ['107', 'Target penerima'], ['106', 'Penerima terdata'], ['1', 'Penerima belum ditentukan']] as [$number, $label])<article class="rounded-2xl border border-emerald-700 bg-white/10 p-4 backdrop-blur-sm"><p class="text-2xl font-bold text-amber-200 sm:text-3xl">{{ $number }}</p><p class="mt-2 text-sm font-semibold text-emerald-100">{{ $label }}</p></article>@endforeach</div><p class="mt-8 max-w-3xl text-base leading-7 text-emerald-50/90">“Program ini merupakan bentuk kepedulian dan amanah dalam menyalurkan dana umat kepada warga yang membutuhkan di sekitar Masjid Raudhotul Jannah.”</p></section>

            <section class="mt-14 grid gap-5 lg:grid-cols-2" aria-labelledby="transparansi-title"><article class="report-card rounded-2xl border border-emerald-100 bg-white p-6"><p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">Transparansi Penggunaan Dana</p><h2 id="transparansi-title" class="report-serif mt-2 text-3xl font-bold text-emerald-950">Ringkasan yang mudah ditelusuri.</h2><p class="mt-5 text-sm leading-7 text-slate-600">Pendanaan program berasal dari:</p><ul class="mt-3 space-y-2 text-sm font-semibold text-emerald-900"><li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-emerald-600"></span>Dana Fidyah</li><li class="flex items-center gap-2"><span class="h-2 w-2 rounded-full bg-amber-500"></span>Dana Zakat Maal</li></ul><div class="mt-6 rounded-xl bg-emerald-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Total dana direalisasikan</p><p class="mt-1 text-2xl font-bold text-emerald-950">Rp12.925.300</p></div></article><article class="report-card rounded-2xl border border-amber-100 bg-amber-50 p-6"><p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Catatan Laporan</p><p class="mt-4 text-base leading-7 text-slate-700">Seluruh angka pada halaman ini merupakan ringkasan sementara berdasarkan data realisasi program yang tersedia.</p><p class="mt-5 border-t border-amber-200 pt-5 text-sm leading-6 text-amber-900">Halaman ini merupakan laporan informasi program dan dapat diperbarui sesuai finalisasi data administrasi.</p></article></section>

            <footer class="mt-14 border-t border-emerald-100 py-10 text-center"><p class="report-serif text-2xl font-bold text-emerald-950">Terima kasih kepada seluruh pihak yang telah mendukung program distribusi sembako ini.</p><p class="mt-4 text-sm font-bold uppercase tracking-[.16em] text-emerald-800">Masjid Raudhotul Jannah</p><p class="mt-1 text-sm text-slate-500">Taman Cipulir Estate</p><a href="{{ route('public.ziswaf.index') }}" class="report-actions no-print mt-7 inline-flex min-h-11 items-center justify-center rounded-xl border border-emerald-700 px-5 py-2.5 text-sm font-bold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">← Kembali ke Laporan ZISWAF</a></footer>
        </div>
    </main>
@endsection
