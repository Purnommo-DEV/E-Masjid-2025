<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Dashboard ZISWAF Internal</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
@php
    $rp = static fn ($n) => 'Rp' . number_format((float) $n, 0, ',', '.');
    $positionRupiah = static fn (string $amount): string => 'Rp'.number_format((float) $amount, 2, ',', '.');
    $positionDate = static fn (string $value): string => \Carbon\Carbon::parse($value)->locale('id')->format('d/m/Y');
    $query = collect($filters)->except('page')->filter(fn ($v) => $v !== null && $v !== '')->all();
    $allocationRate = (float) $allocation > 0
        ? min(100, ((float) $realization / (float) $allocation) * 100)
        : 0;
@endphp

<header class="relative overflow-hidden bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 text-white">
    <div class="pointer-events-none absolute -right-16 -top-24 h-72 w-72 rounded-full bg-white/5 blur-2xl"></div>
    <div class="pointer-events-none absolute -bottom-28 right-1/3 h-64 w-64 rounded-full bg-teal-400/10 blur-3xl"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-7 sm:px-6 lg:px-8 lg:py-9">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold text-emerald-100">
                    <span class="h-2 w-2 rounded-full bg-emerald-300"></span>
                    Monitoring internal DKM
                    <span class="text-white/50">•</span>
                    Read-only
                </div>
                <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-3xl lg:text-4xl">Ringkasan ZISWAF</h1>
                <p class="mt-2 text-sm text-emerald-100 sm:text-base">{{ $entity->name }}</p>
            </div>
            <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 backdrop-blur-sm sm:min-w-64">
                <p class="text-xs font-medium uppercase tracking-wider text-emerald-200">Periode laporan</p>
                <p class="mt-1.5 text-sm font-semibold sm:text-base">
                    {{ \Carbon\Carbon::parse($filters['from'])->format('d M Y') }}
                    <span class="mx-1 text-emerald-200">—</span>
                    {{ \Carbon\Carbon::parse($filters['through'])->format('d M Y') }}
                </p>
                <p class="mt-1 text-xs text-emerald-100/80">Saldo terkini per {{ \Carbon\Carbon::parse($latestDate)->format('d M Y') }}</p>
            </div>
        </div>
    </div>
</header>

<main class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    {{-- Filter laporan --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-base font-bold text-slate-900">Filter laporan</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih periode dan kategori untuk mempersempit data.</p>
            </div>
            <span class="mt-1 inline-flex w-fit items-center rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600 sm:mt-0">
                Data transaksi POSTED
            </span>
        </div>
        <form method="get" class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-5">
            <label class="block text-sm font-semibold text-slate-700">
                Tanggal mulai
                <input type="date" name="from" value="{{ $filters['from'] }}"
                    class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Tanggal akhir
                <input type="date" name="through" value="{{ $filters['through'] }}"
                    class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Pos / Fund
                <select name="fund_id" class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                    <option value="">Semua pos</option>
                    @foreach ($options['funds'] as $o)
                        <option value="{{ $o['id'] }}" @selected(($filters['fund_id'] ?? '') === $o['id'])>{{ $o['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Jenis transaksi
                <select name="type" class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                    <option value="">Semua jenis</option>
                    @foreach ($options['types'] as $o)
                        <option value="{{ $o['id'] }}" @selected(($filters['type'] ?? '') === $o['id'])>{{ $o['label'] }}</option>
                    @endforeach
                </select>
            </label>

            <label class="block text-sm font-semibold text-slate-700">
                Status
                <select name="status" class="mt-1.5 block min-h-11 w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-800 shadow-sm outline-none transition focus:border-emerald-600 focus:ring-4 focus:ring-emerald-600/10">
                    <option value="" @selected(($filters['status'] ?? '') === '')>POSTED (resmi)</option>
                    <option value="posted" @selected(($filters['status'] ?? '') === 'posted')>POSTED</option>
                </select>
            </label>

            <div class="flex flex-col gap-2 pt-1 sm:col-span-2 sm:flex-row xl:col-span-5 xl:justify-end">
                <a href="{{ route('internal.ziswaf.dashboard') }}"
                    class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    Reset filter
                </a>
                <button type="submit"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16M7 12h10m-7 7h4"/>
                    </svg>
                    Terapkan filter
                </button>
            </div>
        </form>
    </section>

    {{-- Rincian pemasukan dan pengeluaran per dana --}}
    <section class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="fund-summary-title">
        <div><p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Telusuri dana</p><h2 id="fund-summary-title" class="mt-1 text-xl font-extrabold text-emerald-950">Rincian Pemasukan &amp; Pengeluaran Per Dana</h2><p class="mt-2 text-sm text-slate-500">Angka berasal dari laporan Financial V2 yang sama. Pilih satu dana untuk memfilter transaksi pembentuknya.</p></div>
        <div class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($report['funds'] as $fund)
                @php $fundQuery=array_merge($query,['fund_id'=>$fund['fund_id']]); @endphp
                <a class="group min-w-0 rounded-2xl border border-emerald-100 bg-emerald-50/30 p-5 transition hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600" href="{{ route('internal.ziswaf.dashboard', $fundQuery) }}#transaksi">
                    <div class="flex items-start justify-between gap-3"><div class="min-w-0"><h3 class="break-words font-bold text-emerald-950">{{ $fund['name'] }}</h3><p class="mt-1 text-xs text-slate-500">{{ $fund['code'] }}</p></div><span class="shrink-0 text-emerald-700 transition group-hover:translate-x-0.5" aria-hidden="true">→</span></div>
                    <dl class="mt-5 space-y-3 text-sm"><div class="flex justify-between gap-3"><dt class="text-slate-500">Pemasukan</dt><dd class="break-words text-right font-mono font-bold text-emerald-700">{{ $rp($fund['receipts']) }}</dd></div><div class="flex justify-between gap-3"><dt class="text-slate-500">Pengeluaran aktual</dt><dd class="break-words text-right font-mono font-bold text-rose-700">{{ $rp($fund['expenses']) }}</dd></div><div class="flex justify-between gap-3 border-t border-emerald-100 pt-3"><dt class="font-semibold text-slate-600">Saldo akhir periode</dt><dd class="break-words text-right font-mono font-extrabold text-emerald-950">{{ $rp($fund['fund_balance']) }}</dd></div></dl>
                </a>
            @empty
                <div class="col-span-full rounded-xl bg-slate-50 p-8 text-center"><p class="font-semibold text-slate-700">Belum ada dana dalam cakupan</p><p class="mt-1 text-sm text-slate-500">Ubah periode atau filter untuk melihat rincian dana.</p></div>
            @endforelse
        </div>
    </section>

    {{-- Posisi keuangan --}}
    <section aria-labelledby="summary-heading">
        <div class="mb-5 flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Posisi keuangan</p>
                <h2 id="summary-heading" class="mt-1 text-2xl font-bold text-emerald-950">Ringkasan Saldo Saat Ini</h2>
            </div>
            <p class="text-sm text-slate-500">Periode pencatatan: {{ $positionDate($positionReport['period_from']) }} – {{ $positionDate($positionReport['as_of']) }}</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-3">
            <article class="report-card rounded-2xl bg-emerald-800 p-6 text-white lg:col-span-1">
                <p class="text-xs font-bold uppercase tracking-[.16em] text-emerald-100">Total Dana ZISWAF</p>
                <p class="mt-3 break-words text-3xl font-bold tracking-tight sm:text-4xl">{{ $positionRupiah($positionReport['total_fund_balance']) }}</p>
                <p class="mt-4 text-sm leading-6 text-emerald-100">Total dana yang dikelola sesuai peruntukan masing-masing dana.</p>
            </article>
            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-2">
                @forelse ($positionReport['financial_accounts'] as $account)
                    <article class="report-card rounded-2xl border border-emerald-100 bg-white p-6">
                        <p class="text-xs font-bold uppercase tracking-[.14em] text-slate-500">Uang tersimpan di</p>
                        <h3 class="mt-2 text-lg font-bold text-emerald-950">{{ $account['name'] }}</h3>
                        <p class="mt-4 break-words text-2xl font-bold tracking-tight text-slate-900">{{ $positionRupiah($account['balance']) }}</p>
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

    {{-- Saldo berjalan --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="running-balance-heading">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-end sm:justify-between sm:px-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Pergerakan dana</p>
                <h2 id="running-balance-heading" class="mt-1 text-xl font-extrabold text-slate-900">Saldo Berjalan</h2>
                <p class="mt-1 text-sm leading-6 text-slate-500">Saldo dihitung berurutan dari transaksi POSTED berdasarkan tanggal akuntansi dan urutan posting resmi.</p>
            </div>
            <span class="mt-2 inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 sm:mt-0">
                Saldo akhir {{ $positionRupiah($runningBalance['closing_balance']) }}
            </span>
        </div>

        <x-horizontal-scroll-table>
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 sm:px-6">Tanggal</th>
                        <th scope="col" class="px-4 py-3.5">Uraian transaksi</th>
                        <th scope="col" class="px-4 py-3.5">Pos/Fund</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Pemasukan</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Pengeluaran</th>
                        <th scope="col" class="px-5 py-3.5 text-right sm:px-6">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @if ($runningBalanceRows->currentPage() === 1)
                        <tr class="bg-emerald-50/60">
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-slate-600 sm:px-6">{{ $positionDate($filters['from']) }}</td>
                            <td class="px-4 py-4 font-bold text-emerald-950">Saldo Awal Periode</td>
                            <td class="px-4 py-4 text-slate-500">{{ $filters['fund_id'] ?? null ? data_get(collect($report['funds'])->firstWhere('fund_id', $filters['fund_id']), 'name', 'Dana terpilih') : 'Seluruh Dana ZISWAF' }}</td>
                            <td class="px-4 py-4 text-right text-slate-400">—</td>
                            <td class="px-4 py-4 text-right text-slate-400">—</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-bold tabular-nums text-emerald-950 sm:px-6">{{ $positionRupiah($runningBalance['opening_balance']) }}</td>
                        </tr>
                    @endif
                    @forelse ($runningBalanceRows as $row)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600 sm:px-6">{{ $positionDate($row['date']) }}</td>
                            <td class="max-w-xs px-4 py-4 align-top">
                                <a class="font-semibold text-emerald-800 hover:text-emerald-950 hover:underline" href="{{ route('internal.ziswaf.transactions.show', $row['transaction_id']) }}">{{ $row['description'] }}</a>
                                <p class="mt-1 text-xs text-slate-400">Urutan posting {{ $row['posting_sequence'] }}</p>
                            </td>
                            <td class="max-w-52 break-words px-4 py-4 text-slate-600">{{ $row['fund'] ?: '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums text-emerald-700">{{ $row['in'] !== '0.00' ? $positionRupiah($row['in']) : '—' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums text-rose-700">{{ $row['out'] !== '0.00' ? $positionRupiah($row['out']) : '—' }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-mono font-bold tabular-nums text-slate-900 sm:px-6">{{ $positionRupiah($row['running_balance']) }}</td>
                        </tr>
                    @empty
                        @if ($runningBalanceRows->currentPage() !== 1)
                            <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Halaman saldo berjalan tidak memiliki transaksi.</td></tr>
                        @endif
                    @endforelse
                </tbody>
            </table>
        </x-horizontal-scroll-table>
        @if ($runningBalanceRows->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 sm:px-6">{{ $runningBalanceRows->onEachSide(1)->links() }}</div>
        @endif
    </section>

    {{-- Allocation dan realisasi --}}
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5 sm:p-6">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-slate-900">Allocation dibanding Realisasi</h2>
                    <p class="mt-1 text-sm text-slate-500">Pantau penggunaan rencana dana untuk setiap program.</p>
                </div>
                <div class="grid min-w-0 grid-cols-1 gap-3 sm:min-w-80 sm:grid-cols-2">
                    <div class="min-w-0 rounded-xl bg-slate-50 px-4 py-3">
                        <p class="text-xs font-medium text-slate-500">Total allocation</p>
                        <p class="mt-1 whitespace-nowrap text-sm font-bold tabular-nums text-slate-900 min-[360px]:text-base">{{ $rp($allocation) }}</p>
                    </div>
                    <div class="min-w-0 rounded-xl bg-emerald-50 px-4 py-3">
                        <p class="text-xs font-medium text-emerald-700">Total realisasi</p>
                        <p class="mt-1 whitespace-nowrap text-sm font-bold tabular-nums text-emerald-800 min-[360px]:text-base">{{ $rp($realization) }}</p>
                    </div>
                </div>
            </div>

            <div class="mt-5">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
                    <span class="font-semibold text-slate-700">Progress realisasi</span>
                    <span class="font-bold tabular-nums text-emerald-700">{{ number_format($allocationRate, 1, ',', '.') }}%</span>
                </div>
                <div class="h-3 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-label="Persentase realisasi allocation" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ number_format($allocationRate, 1, '.', '') }}">
                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-500" style="width: {{ $allocationRate }}%"></div>
                </div>
                <p class="mt-2 text-xs text-slate-500">Sisa allocation: <span class="font-semibold text-slate-700">{{ $rp($remainingAllocation) }}</span></p>
            </div>
        </div>

        <x-horizontal-scroll-table>
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5 sm:px-6">Program</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Allocation</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Realisasi</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Pengeluaran Aktual</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Sisa</th>
                        <th scope="col" class="px-5 py-3.5 sm:px-6">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['programs'] as $p)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="px-5 py-4 font-semibold text-slate-800 sm:px-6">{{ $p['name'] }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums text-slate-700">{{ $rp($p['allocation']) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums font-semibold text-emerald-700">{{ $rp($p['realization']) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums font-semibold text-rose-700">{{ $rp($p['actual_expense']) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums text-slate-700">{{ $rp($p['remaining']) }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">{{ $p['status'] }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center sm:px-6">
                                <p class="font-semibold text-slate-700">Belum ada allocation</p>
                                <p class="mt-1 text-sm text-slate-500">Tidak ada allocation dalam cakupan periode yang dipilih.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-horizontal-scroll-table>
    </section>

    {{-- Histori transaksi --}}
    <section id="transaksi" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-1 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-slate-900">Histori transaksi terbaru</h2>
                <p class="mt-1 text-sm text-slate-500">Pilih uraian transaksi untuk melihat detailnya.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Ledger resmi · POSTED</span>
        </div>

        <x-horizontal-scroll-table>
            <table class="w-full min-w-[980px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th scope="col" class="px-5 py-3.5">Tanggal</th>
                        <th scope="col" class="px-4 py-3.5">Uraian</th>
                        <th scope="col" class="px-4 py-3.5">Fund</th>
                        <th scope="col" class="px-4 py-3.5">Jenis</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Masuk</th>
                        <th scope="col" class="px-4 py-3.5 text-right">Keluar</th>
                        <th scope="col" class="px-4 py-3.5">Status</th>
                        <th scope="col" class="px-5 py-3.5">Bukti</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($transactions as $t)
                        <tr class="transition hover:bg-slate-50/80">
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ \Carbon\Carbon::parse($t['date'])->format('d/m/Y') }}</td>
                            <td class="max-w-xs px-4 py-4">
                                <a class="font-semibold leading-5 text-emerald-800 underline-offset-4 transition hover:text-emerald-950 hover:underline focus:outline-none focus:ring-2 focus:ring-emerald-600"
                                   href="{{ route('internal.ziswaf.transactions.show', $t['transaction_id']) }}?{{ http_build_query($query) }}">
                                    {{ $t['description'] ?: 'Transaksi Financial V2' }}
                                </a>
                            </td>
                            <td class="px-4 py-4 text-slate-600">{{ $t['fund'] }}</td>
                            <td class="px-4 py-4 text-slate-600">{{ $t['type'] }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums font-semibold text-emerald-700">{{ $t['in'] === '0.00' ? '—' : $rp($t['in']) }}</td>
                            <td class="whitespace-nowrap px-4 py-4 text-right font-mono tabular-nums font-semibold text-rose-700">{{ $t['out'] === '0.00' ? '—' : $rp($t['out']) }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>POSTED
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-4">
                                @if ($t['attachment_count'])
                                    <span class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-50 px-2.5 py-1.5 text-xs font-semibold text-emerald-800">
                                        {{ $t['attachment_count'] }} bukti
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">Belum ada bukti</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <p class="font-semibold text-slate-700">Tidak ada transaksi</p>
                                <p class="mt-1 text-sm text-slate-500">Tidak ditemukan transaksi POSTED untuk filter yang dipilih.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-horizontal-scroll-table>
        <div class="border-t border-slate-100 px-5 py-4 sm:px-6">
            {{ $transactions->links() }}
        </div>
    </section>

    {{-- Penyaluran dan penerima manfaat --}}
    <section id="penyaluran" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 p-5 sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Cakupan operasional</p>
                    <h2 class="mt-1 text-xl font-extrabold text-slate-900 sm:text-2xl">Penyaluran dan Penerima Manfaat</h2>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-500">Total operasional bukan tambahan pengeluaran finansial. Actual tertaut hanya berasal dari realisasi utuh yang lolos validasi terhadap transaksi posted dalam cakupan dana.</p>
                </div>
                <div class="flex shrink-0 gap-3">
                    <div class="rounded-xl bg-emerald-50 px-4 py-3 text-center"><strong class="block text-2xl text-emerald-900">{{ $report['distributions']['distribution_events'] }}</strong><span class="text-xs font-semibold text-emerald-700">penyaluran</span></div>
                </div>
            </div>
        </div>

        <div class="p-5 sm:p-6">
            <h3 class="text-sm font-bold text-slate-900">Ringkasan per program</h3>
            <div class="mt-3 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($report['distributions']['programs'] as $program)
                    <article class="min-w-0 rounded-2xl border border-slate-200 bg-slate-50/70 p-5 transition hover:border-emerald-300 hover:bg-emerald-50/30">
                        <h4 class="break-words font-bold text-slate-900">{{ $program['program_name'] }}</h4>
                        <p class="mt-1 text-sm text-slate-500">{{ $program['distribution_events'] }} kegiatan · {{ $program['unique_beneficiaries'] }} penerima unik</p>
                        <dl class="mt-4 space-y-3 text-sm"><div class="flex min-w-0 flex-col gap-1 min-[360px]:flex-row min-[360px]:justify-between"><dt class="text-slate-500">Total operasional</dt><dd class="whitespace-nowrap font-mono font-bold min-[360px]:text-right">{{ $rp($program['operational_total']) }}</dd></div><div class="flex min-w-0 flex-col gap-1 min-[360px]:flex-row min-[360px]:justify-between"><dt class="text-slate-500">Actual tertaut</dt><dd class="whitespace-nowrap font-mono font-bold text-emerald-800 min-[360px]:text-right">{{ $rp($program['actual_amount']) }}</dd></div></dl>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada program penyaluran dalam periode ini.</p>
                @endforelse
            </div>

            <div class="mt-7 flex flex-col items-start gap-1 min-[360px]:flex-row min-[360px]:items-center min-[360px]:justify-between min-[360px]:gap-3"><h3 class="text-sm font-bold text-slate-900">Setiap realisasi penyaluran</h3><span class="text-xs text-slate-500">{{ count($report['distributions']['events']) }} kegiatan</span></div>
            <div class="mt-3 grid gap-4 md:grid-cols-2">
                @forelse ($report['distributions']['events'] as $event)
                    <article class="flex min-w-0 flex-col justify-between gap-5 rounded-2xl border border-slate-200 p-5 transition hover:border-emerald-300 hover:shadow-sm">
                        <div><p class="text-xs font-bold uppercase tracking-wider text-emerald-700">{{ $event['program_name'] }}</p><h4 class="mt-1 break-words text-lg font-bold text-slate-900">{{ $event['period'] }}</h4><p class="mt-2 text-sm text-slate-500">{{ $event['recipient_count'] }} penerima · {{ \Carbon\Carbon::parse($event['from'])->format('d/m/Y') }}–{{ \Carbon\Carbon::parse($event['through'])->format('d/m/Y') }}</p><div class="mt-3 flex flex-wrap gap-2"><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold uppercase text-slate-700">{{ $event['operational_status'] }}</span><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold uppercase text-emerald-800">{{ $event['financial_status'] }}</span></div></div>
                        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div class="min-w-0 text-xs leading-5 text-slate-500"><span class="block break-words">Operasional <strong class="whitespace-nowrap font-semibold text-slate-700">{{ $rp($event['operational_total']) }}</strong></span><span class="mt-1 block break-words">Actual <strong class="whitespace-nowrap font-semibold text-emerald-800">{{ $rp($event['actual_amount']) }}</strong></span></div><a class="inline-flex min-h-10 w-full shrink-0 items-center justify-center rounded-lg bg-emerald-700 px-4 text-center text-sm font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2 sm:w-auto" href="{{ route('internal.ziswaf.distributions.show', $event['distribution_id']) }}?{{ http_build_query($query) }}">Rincian penyaluran</a></div>
                    </article>
                @empty
                    <p class="col-span-full rounded-xl bg-slate-50 p-6 text-center text-sm text-slate-500">Belum ada realisasi penyaluran dalam cakupan filter.</p>
                @endforelse
            </div>
        </div>
    </section>

    <footer class="pb-5 pt-1 text-center">
        <p class="text-xs leading-5 text-slate-400">Actual bersumber dari Financial V2 posted ledger. Dashboard ini bersifat read-only dan tidak menyediakan operasi tulis.</p>
    </footer>
</main>
</body>
</html>
