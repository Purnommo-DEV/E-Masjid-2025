@extends('masjid.master-guest')

@section('title', 'Laporan Dana ZISWAF')

@php
    $rupiah = static fn (string $amount): string => 'Rp'.number_format((float) $amount, 2, ',', '.');
    $date = static fn (string $value): string => \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d F Y');
    $updated = $report['updated_at'] ? \Carbon\Carbon::parse($report['updated_at'])->locale('id')->translatedFormat('d F Y, H:i') : null;
    $transferLabel = static function (string $amount) use ($rupiah): string {
        if ($amount === '0.00') {
            return '—';
        }
        $negative = str_starts_with($amount, '-');

        return ($negative ? 'Keluar ' : 'Masuk ').$rupiah(ltrim($amount, '-'));
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
        .ziswaf-public-report .report-table th { background: #f0fdf4; }
        .ziswaf-public-report .report-table tr + tr { border-top: 1px solid #e5e7eb; }
        .ziswaf-public-report .report-table td, .ziswaf-public-report .report-table th { padding: .9rem 1rem; }
        @media print {
            #mainNav, #mobileMenuPanel, body > footer, .ziswaf-public-report .no-print, .ziswaf-public-report .public-report-actions, .ziswaf-public-report .print-hidden { display: none !important; }
            body { background: #fff !important; color: #111827 !important; }
            .ziswaf-public-report { background: #fff !important; padding: 0 !important; }
            .ziswaf-public-report .report-card { box-shadow: none !important; break-inside: avoid; }
            .ziswaf-public-report .report-table { font-size: 9pt; }
            .ziswaf-public-report .report-table tr { break-inside: avoid; }
            @page { size: A4 portrait; margin: 12mm; }
        }
    </style>
@endpush

@section('content')
    <main class="ziswaf-public-report min-h-screen bg-gradient-to-b from-emerald-50 via-white to-emerald-50 px-4 py-8 sm:px-6 sm:py-12">
        <div class="mx-auto max-w-6xl">
            <header class="report-card overflow-hidden rounded-[2rem] bg-emerald-950 text-white">
                <div class="relative px-6 py-10 sm:px-10 sm:py-14 lg:px-14">
                    <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-emerald-400/20 blur-3xl"></div>
                    <div class="absolute -bottom-20 left-1/3 h-52 w-52 rounded-full bg-teal-300/10 blur-3xl"></div>
                    <div class="relative flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[.23em] text-emerald-200">DKM Masjid Raudhotul Jannah</p>
                            <p class="mt-2 text-sm text-emerald-100/80">Taman Cipulir Estate</p>
                            <h1 class="report-serif mt-7 text-4xl font-bold leading-tight sm:text-5xl">Laporan Dana ZISWAF</h1>
                            <p class="mt-3 text-base text-emerald-100 sm:text-lg">Transparansi <span aria-hidden="true">•</span> Amanah <span aria-hidden="true">•</span> Untuk Umat</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-300/30 bg-white/10 px-5 py-4 backdrop-blur-sm lg:max-w-xs">
                            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-200">Posisi laporan</p>
                            <p class="mt-1 text-lg font-bold">Per {{ $date($report['as_of']) }}</p>
                            <p class="mt-2 text-xs text-emerald-100/75">
                                @if ($updated)
                                    Terakhir diperbarui {{ $updated }} WIB
                                @else
                                    Belum ada pencatatan resmi pada periode ini
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </header>

            <form class="public-report-actions no-print mt-6 flex flex-col gap-3 rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm sm:flex-row sm:items-end sm:justify-between" method="GET" action="{{ route('public.ziswaf.index') }}">
                <label class="block max-w-xs text-sm font-semibold text-slate-700" for="as_of">
                    Tampilkan posisi per tanggal
                    <input id="as_of" name="as_of" type="date" value="{{ $report['as_of'] }}" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </label>
                <div class="flex flex-wrap gap-2">
                    <a class="rounded-xl px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600" href="{{ route('public.ziswaf.index') }}">Data terbaru</a>
                    <button class="rounded-xl bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2" type="submit">Terapkan</button>
                    <button class="rounded-xl border border-emerald-700 px-4 py-2 text-sm font-semibold text-emerald-800 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600" type="button" onclick="window.print()">Cetak laporan</button>
                </div>
            </form>

            <section class="mt-10" aria-labelledby="ringkasan-title">
                <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Posisi keuangan</p>
                        <h2 id="ringkasan-title" class="mt-1 text-2xl font-bold text-emerald-950">Ringkasan Saldo Saat Ini</h2>
                    </div>
                    <p class="text-sm text-slate-500">Periode pencatatan: {{ $date($report['period_from']) }} – {{ $date($report['as_of']) }}</p>
                </div>
                <div class="grid gap-4 lg:grid-cols-3">
                    <article class="report-card rounded-2xl bg-emerald-800 p-6 text-white lg:col-span-1">
                        <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-100">Total Dana ZISWAF</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ $rupiah($report['total_fund_balance']) }}</p>
                        <p class="mt-4 text-sm leading-6 text-emerald-100">Total dana yang dikelola sesuai peruntukan masing-masing dana.</p>
                    </article>
                    <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                        @forelse ($report['financial_accounts'] as $account)
                            <article class="report-card rounded-2xl border border-emerald-100 bg-white p-6">
                                <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-500">Uang tersimpan di</p>
                                <h3 class="mt-2 text-lg font-bold text-emerald-950">{{ $account['name'] }}</h3>
                                <p class="mt-4 text-2xl font-bold tracking-tight text-slate-900">{{ $rupiah($account['balance']) }}</p>
                            </article>
                        @empty
                            <article class="report-card rounded-2xl border border-emerald-100 bg-white p-6 sm:col-span-2">
                                <p class="font-semibold text-slate-700">Belum ada posisi rekening/kas yang dapat ditampilkan.</p>
                            </article>
                        @endforelse
                    </div>
                </div>
                <p class="mt-4 text-sm leading-6 text-slate-500">Saldo rekening/kas menunjukkan lokasi penyimpanan dana, sedangkan saldo dana menunjukkan peruntukannya.</p>
            </section>

            <section class="mt-12" aria-labelledby="rekap-title">
                <div class="mb-5">
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Peruntukan dana</p>
                    <h2 id="rekap-title" class="mt-1 text-2xl font-bold text-emerald-950">Rekap Dana Peruntukan</h2>
                </div>
                <div class="report-card overflow-hidden rounded-2xl border border-emerald-100 bg-white">
                    <div class="overflow-x-auto">
                        <table class="report-table min-w-[760px] w-full text-left text-sm">
                            <thead class="text-xs font-bold uppercase tracking-wide text-emerald-900">
                                <tr>
                                    <th scope="col">Dana</th>
                                    <th scope="col" class="text-right">Pemasukan</th>
                                    <th scope="col" class="text-right">Pengeluaran</th>
                                    <th scope="col" class="text-right">Pemindahan Dana</th>
                                    <th scope="col" class="text-right">Saldo Dana</th>
                                </tr>
                            </thead>
                            <tbody class="text-slate-700">
                                @forelse ($report['funds'] as $fund)
                                    <tr>
                                        <td class="font-bold text-emerald-950">{{ $fund['name'] }}</td>
                                        <td class="text-right font-medium text-emerald-700">{{ $rupiah($fund['receipts']) }}</td>
                                        <td class="text-right font-medium text-rose-700">{{ $rupiah($fund['expenses']) }}</td>
                                        <td class="text-right font-medium {{ str_starts_with($fund['transfer_net'], '-') ? 'text-amber-700' : 'text-sky-700' }}">{{ $transferLabel($fund['transfer_net']) }}</td>
                                        <td class="text-right font-bold text-slate-900">{{ $rupiah($fund['balance']) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="py-10 text-center text-slate-500">Belum ada Dana ZISWAF aktif yang ditetapkan untuk publikasi.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-500">Pemindahan dana antar peruntukan ditampilkan terpisah; bukan pemasukan atau pengeluaran baru.</p>
            </section>

            <section class="mt-12" aria-labelledby="rincian-title">
                <div class="mb-5">
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Telusuri per dana</p>
                    <h2 id="rincian-title" class="mt-1 text-2xl font-bold text-emerald-950">Rincian Pemasukan &amp; Pengeluaran Per Dana</h2>
                </div>
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($report['funds'] as $fund)
                        <article class="report-card flex min-h-64 flex-col rounded-2xl border border-emerald-100 bg-white p-6">
                            <h3 class="text-lg font-bold text-emerald-950">{{ $fund['name'] }}</h3>
                            <dl class="mt-5 space-y-3 text-sm">
                                <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Pemasukan</dt><dd class="font-semibold text-emerald-700">{{ $rupiah($fund['receipts']) }}</dd></div>
                                <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Pengeluaran</dt><dd class="font-semibold text-rose-700">{{ $rupiah($fund['expenses']) }}</dd></div>
                                <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Pemindahan Dana</dt><dd class="font-semibold text-sky-700">{{ $transferLabel($fund['transfer_net']) }}</dd></div>
                            </dl>
                            <div class="mt-auto border-t border-slate-100 pt-5">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Saldo Dana</p>
                                <p class="mt-1 text-2xl font-bold text-slate-900">{{ $rupiah($fund['balance']) }}</p>
                                <a class="mt-5 inline-flex rounded-xl bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-800 hover:bg-emerald-100 focus:outline-none focus:ring-2 focus:ring-emerald-600" href="{{ route('public.ziswaf.fund', ['fundCode' => $fund['code'], 'as_of' => $report['as_of']]) }}">Lihat rincian dana</a>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="mt-12 grid gap-5 lg:grid-cols-2">
                <article class="report-card rounded-2xl border border-amber-100 bg-amber-50 p-6">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-amber-800">Catatan penting</p>
                    <ul class="mt-4 space-y-3 text-sm leading-6 text-amber-950">
                        <li>Laporan ini disusun berdasarkan pencatatan keuangan yang telah dicatat resmi.</li>
                        <li>Pemindahan dana antar peruntukan tidak dianggap sebagai pemasukan atau pengeluaran baru.</li>
                        <li>Rincian dana dapat ditelusuri berdasarkan tanggal laporan yang dipilih.</li>
                    </ul>
                </article>
                <article class="report-card rounded-2xl border border-emerald-100 bg-white p-6">
                    <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-700">Transparansi untuk umat</p>
                    <p class="mt-4 text-sm leading-6 text-slate-600">Dana dikelola sesuai peruntukan dan penggunaannya. Laporan diperbarui berdasarkan pencatatan keuangan resmi.</p>
                    <div class="mt-5 grid grid-cols-2 gap-3 text-sm">
                        <div><p class="font-bold text-emerald-900">Amanah</p><p class="mt-1 text-slate-500">Dikelola sesuai peruntukan.</p></div>
                        <div><p class="font-bold text-emerald-900">Transparan</p><p class="mt-1 text-slate-500">Laporan terbuka untuk jamaah.</p></div>
                        <div><p class="font-bold text-emerald-900">Tepat sasaran</p><p class="mt-1 text-slate-500">Dana digunakan sesuai tujuan.</p></div>
                        <div><p class="font-bold text-emerald-900">Akuntabel</p><p class="mt-1 text-slate-500">Pencatatan dapat ditelusuri.</p></div>
                    </div>
                </article>
            </section>

            <footer class="ziswaf-report-footer mt-12 border-t border-emerald-100 py-8 text-center text-sm text-slate-500">
                <p class="font-semibold text-emerald-900">DKM Masjid Raudhotul Jannah — Taman Cipulir Estate</p>
                <p class="mt-2">Laporan publik Dana ZISWAF</p>
            </footer>
        </div>
    </main>
@endsection
