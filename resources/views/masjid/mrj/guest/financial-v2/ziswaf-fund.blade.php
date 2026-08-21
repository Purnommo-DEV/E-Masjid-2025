@extends('masjid.mrj.guest.financial-v2.layout')

@section('title', $report['fund']['name'].' — Laporan Dana ZISWAF')

@php
    $rupiah = static fn (string $amount): string => 'Rp'.number_format((float) $amount, 2, ',', '.');
    $date = static fn (string $value): string => \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d F Y');
    $updated = $report['updated_at'] ? \Carbon\Carbon::parse($report['updated_at'])->locale('id')->translatedFormat('d F Y, H:i') : null;
    $amountClass = static fn (string $kind): string => match ($kind) {
        'receipt' => 'text-emerald-700',
        'expense' => 'text-rose-700',
        'transfer' => 'text-sky-700',
        default => 'text-slate-700',
    };
    $amountPrefix = static fn (string $kind, string $amount): string => match ($kind) {
        'receipt' => '+ ',
        'expense' => '− ',
        'transfer' => str_starts_with($amount, '-') ? 'Keluar ' : 'Masuk ',
        default => '',
    };
    $transferSummary = static function (string $amount) use ($rupiah): string {
        if ($amount === '0.00') {
            return '—';
        }

        return (str_starts_with($amount, '-') ? 'Keluar ' : 'Masuk ').$rupiah(ltrim($amount, '-'));
    };
@endphp

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
    <style>
        .ziswaf-public-report { font-family: 'DM Sans', sans-serif; color: #16352b; }
        .ziswaf-public-report .report-serif { font-family: 'Playfair Display', serif; }
        .ziswaf-public-report .report-card { box-shadow: 0 16px 40px rgba(15, 80, 52, .08); }
        @media print {
            #mainNav, #mobileMenuPanel, body > footer, .ziswaf-public-report .no-print, .ziswaf-public-report .public-report-actions, .ziswaf-public-report .print-hidden { display: none !important; }
            body { background: #fff !important; color: #111827 !important; }
            .ziswaf-public-report { background: #fff !important; padding: 0 !important; }
            .ziswaf-public-report .report-card, .ziswaf-public-report article { box-shadow: none !important; break-inside: avoid; }
            @page { size: A4 portrait; margin: 12mm; }
        }
    </style>
@endpush

@section('content')
    <main class="ziswaf-public-report min-h-screen bg-gradient-to-b from-emerald-50 via-white to-emerald-50 px-4 py-8 sm:px-6 sm:py-12">
        <div class="mx-auto max-w-5xl">
            <div class="public-report-actions no-print mb-6 flex flex-wrap items-center justify-between gap-3">
                <a class="inline-flex rounded-xl px-3 py-2 text-sm font-bold text-emerald-800 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-600" href="{{ route('public.ziswaf.index', ['as_of' => $report['as_of']]) }}">← Kembali ke laporan ZISWAF</a>
                <button class="rounded-xl border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600" type="button" onclick="window.print()">Cetak rincian</button>
            </div>

            <header class="report-card rounded-[2rem] bg-emerald-950 px-6 py-9 text-white sm:px-10 sm:py-12">
                <p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Rincian Dana ZISWAF</p>
                <h1 class="report-serif mt-3 text-3xl font-bold leading-tight sm:text-5xl">{{ $report['fund']['name'] }}</h1>
                <div class="mt-6 flex flex-col gap-3 text-sm text-emerald-100 sm:flex-row sm:items-center sm:justify-between">
                    <p>Posisi per {{ $date($report['as_of']) }}</p>
                    <p>{{ $updated ? 'Terakhir diperbarui '.$updated.' WIB' : 'Belum ada pencatatan resmi pada periode ini' }}</p>
                </div>
            </header>

            <section class="mt-7 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan dana">
                <article class="report-card rounded-2xl border border-emerald-100 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pemasukan</p><p class="mt-2 text-xl font-bold text-emerald-700">{{ $rupiah($report['fund']['receipts']) }}</p></article>
                <article class="report-card rounded-2xl border border-emerald-100 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pengeluaran</p><p class="mt-2 text-xl font-bold text-rose-700">{{ $rupiah($report['fund']['expenses']) }}</p></article>
                <article class="report-card rounded-2xl border border-emerald-100 bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Pemindahan Dana</p><p class="mt-2 text-xl font-bold text-sky-700">{{ $transferSummary($report['fund']['transfer_net']) }}</p></article>
                <article class="report-card rounded-2xl bg-emerald-800 p-5 text-white"><p class="text-xs font-bold uppercase tracking-wide text-emerald-100">Saldo Dana</p><p class="mt-2 text-xl font-bold">{{ $rupiah($report['fund']['balance']) }}</p></article>
            </section>

            <section class="report-card mt-9 rounded-2xl border border-emerald-100 bg-white p-5 sm:p-7" aria-labelledby="official-history-title">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">Mutasi resmi</p>
                        <h2 id="official-history-title" class="mt-1 text-2xl font-bold text-emerald-950">Pemasukan, Pengeluaran &amp; Pemindahan Dana</h2>
                    </div>
                    <p class="text-sm text-slate-500">{{ $date($report['period_from']) }} – {{ $date($report['as_of']) }}</p>
                </div>

                <div class="mt-6 divide-y divide-slate-100">
                    @forelse ($report['official_history'] as $item)
                        <article class="flex flex-col gap-3 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-500">{{ $date($item['date']) }}</p>
                                <h3 class="mt-1 font-bold text-slate-800">{{ $item['description'] ?: 'Pencatatan dana resmi' }}</h3>
                                <p class="mt-1 text-sm text-slate-500">{{ match ($item['kind']) { 'receipt' => 'Pemasukan dana', 'expense' => 'Penggunaan dana', 'transfer' => 'Pemindahan dana antar peruntukan', default => 'Posisi awal tercatat' } }}</p>
                            </div>
                            <div class="shrink-0 text-left sm:text-right">
                                <p class="font-bold {{ $amountClass($item['kind']) }}">{{ $amountPrefix($item['kind'], $item['amount']) }}{{ $rupiah(ltrim($item['amount'], '-')) }}</p>
                                <p class="mt-1 text-xs text-slate-500">Saldo berjalan {{ $rupiah($item['running_balance']) }}</p>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-xl bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada mutasi resmi untuk Dana ini pada tanggal laporan yang dipilih.</p>
                    @endforelse
                </div>
            </section>

            @if ($report['source_opening_history'] !== [])
                <section class="report-card mt-7 rounded-2xl border border-sky-100 bg-sky-50/60 p-5 sm:p-7" aria-labelledby="source-history-title">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-sky-800">Konteks saldo awal</p>
                    <h2 id="source-history-title" class="mt-1 text-2xl font-bold text-slate-900">Riwayat Sumber Posisi Awal</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">Rincian ini menjelaskan posisi saldo awal yang telah dicatat resmi. Riwayat sumber tidak dihitung ulang sebagai transaksi baru dalam periode laporan.</p>
                    <div class="mt-5 divide-y divide-sky-100 rounded-xl bg-white/70 px-4">
                        @foreach ($report['source_opening_history'] as $item)
                            <article class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div><p class="text-xs font-semibold text-slate-500">{{ $item['date'] }}</p><h3 class="mt-1 font-semibold text-slate-800">{{ $item['description'] }}</h3></div>
                                <div class="text-left sm:text-right"><p class="font-bold {{ $amountClass($item['kind']) }}">{{ $amountPrefix($item['kind'], $item['amount']) }}{{ $rupiah($item['amount']) }}</p><p class="mt-1 text-xs text-slate-500">Saldo sumber {{ $rupiah($item['running_balance']) }}</p></div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section class="report-card mt-7 rounded-2xl border border-amber-100 bg-amber-50 p-5 sm:p-7">
                <h2 class="text-lg font-bold text-amber-950">Catatan untuk jamaah</h2>
                <p class="mt-2 text-sm leading-6 text-amber-900">Pemindahan dana antar peruntukan tidak dianggap sebagai pemasukan atau pengeluaran baru. Saldo dana menjelaskan peruntukan, sementara rekening dan kas menjelaskan lokasi penyimpanan.</p>
            </section>

            <footer class="mt-10 py-6 text-center text-sm text-slate-500"><p class="font-semibold text-emerald-900">DKM Masjid Raudhotul Jannah — Taman Cipulir Estate</p><p class="mt-2">Laporan publik Dana ZISWAF</p></footer>
        </div>
    </main>
@endsection
