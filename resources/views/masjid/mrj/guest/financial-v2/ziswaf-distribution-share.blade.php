<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Detail Penyaluran ZISWAF</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-900">
@php
    $rp = static fn ($value) => \App\Support\ZiswafAmount::rupiah((string) $value);
    $date = static fn ($value) => \Carbon\CarbonImmutable::parse($value)->format('d/m/Y');
@endphp
<header class="border-b border-emerald-900 bg-emerald-950 text-white">
    <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6">
        <nav aria-label="Breadcrumb" class="text-xs text-emerald-200"><a href="{{ route('public.ziswaf.index') }}">Laporan ZISWAF</a><span class="mx-2">/</span><span>Detail penyaluran</span></nav>
        <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">{{ $summary['program'] }}</p><h1 class="mt-2 break-words text-2xl font-bold sm:text-3xl">Detail Penyaluran ZISWAF</h1><p class="mt-2 text-sm text-emerald-100">{{ $summary['period'] }} · {{ $date($summary['from']) }}–{{ $date($summary['through']) }}</p></div>
            <div class="flex flex-wrap gap-2"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">Operasional: {{ strtoupper($summary['operational_status']) }}</span><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">Finansial: {{ strtoupper($summary['financial_status']) }}</span></div>
        </div>
    </div>
</header>
<main class="mx-auto max-w-7xl space-y-7 px-4 py-6 sm:px-6">
    <section aria-label="Ringkasan realisasi" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Penerima', $summary['recipient_count'].' orang', 'Seluruh penerima pada penyaluran ini'], ['Total rincian bantuan', $rp($summary['operational_total']), 'Jumlah nominal seluruh penerima'], ['Actual tertaut', $rp($summary['actual_amount']), 'Expense posted yang lolos rekonsiliasi'], ['Periode', $summary['period'], $date($summary['from']).'–'.$date($summary['through'])]] as [$label, $value, $hint])
            <article class="min-w-0 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="mt-2 break-words text-2xl font-extrabold tracking-tight text-emerald-800">{{ $value }}</p><p class="mt-2 text-xs leading-5 text-slate-500">{{ $hint }}</p></article>
        @endforeach
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold">Transaksi Keuangan Tertaut</h2>
        <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
            @foreach (['Tanggal aktual' => $summary['business_date'], 'Tanggal akuntansi' => $summary['accounting_date'], 'Nominal penyaluran' => $rp($summary['actual_amount']), 'Sumber dana' => implode(', ', $summary['funds']), 'Status' => strtoupper($summary['financial_status'])] as $label => $value)
                <div class="min-w-0"><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words font-semibold">{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
    </section>
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-xl font-bold">Daftar Penerima Manfaat</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $recipients->count() }} penerima · total {{ $rp($summary['operational_total']) }}</p>
        <x-horizontal-scroll-table>
            <table class="mt-6 w-full min-w-[680px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-4 py-3">No</th><th class="px-4 py-3">Nama Penerima</th><th class="px-4 py-3">Kategori</th><th class="px-4 py-3 text-right">Nominal Bantuan</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recipients as $recipient)
                        <tr class="hover:bg-emerald-50/40"><td class="px-4 py-3 text-slate-500">{{ $loop->iteration }}</td><td class="px-4 py-3 font-semibold">{{ $recipient['name'] }}</td><td class="px-4 py-3">{{ $recipient['category'] }}@if($recipient['category_from_current_master'])<span class="mt-1 block text-[11px] leading-4 text-amber-700">Kategori berdasarkan Master Penerima saat ini</span>@endif</td><td class="whitespace-nowrap px-4 py-3 text-right font-mono font-bold text-emerald-800">{{ $rp($recipient['amount']) }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-12 text-center text-slate-500">Belum ada penerima pada penyaluran ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-horizontal-scroll-table>
    </section>
</main>
</body>
</html>
