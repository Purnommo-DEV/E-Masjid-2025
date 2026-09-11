@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'ZISWAF Reporting V2')

@php
    $rupiah = static fn (string $amount): string => 'Rp'.number_format((float) $amount, 0, ',', '.');
    $badge = static function (string $status): string {
        return match ($status) {
            'POSTED', 'COMPLETED' => 'badge-success',
            'BELUM DIREALISASIKAN', 'DRAFT' => 'badge-warning',
            'SUBMITTED', 'BERJALAN' => 'badge-info',
            default => 'badge-ghost',
        };
    };
@endphp

@section('content')
    <section class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">Financial V2 · reporting layer</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">Laporan Pengelolaan Dana ZISWAF</h1>
            <p class="mt-2 max-w-3xl text-sm text-base-content/65">Actual hanya berasal dari Journal dan Ledger Financial V2 yang sudah <strong>POSTED</strong>. Program dan alokasi tetap ditampilkan sebagai rencana, bukan pengeluaran.</p>
        </div>
        <span class="badge badge-outline self-start">Watermark posting #{{ $report['as_of_posting_sequence'] }}</span>
    </section>

    <section class="card mb-6 border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form method="GET" action="{{ route('financial-v2.ziswaf-v2.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="form-control"><span class="label-text text-xs font-semibold">Entitas</span><select class="select select-bordered select-sm" name="entity">@foreach($entities as $item)<option value="{{ $item->id }}" @selected($entity?->id === $item->id)>{{ $item->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Tanggal mulai</span><input class="input input-bordered input-sm" type="date" name="from" value="{{ $filters['from'] }}"></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Tanggal akhir</span><input class="input input-bordered input-sm" type="date" name="through" value="{{ $filters['through'] }}"></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Dana</span><select class="select select-bordered select-sm" name="fund_id"><option value="">Semua Dana</option>@foreach($filterOptions['funds'] as $fund)<option value="{{ $fund['id'] }}" @selected(($filters['fund_id'] ?? null) === $fund['id'])>{{ $fund['label'] }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Program</span><select class="select select-bordered select-sm" name="program_id"><option value="">Semua Program</option>@foreach($filterOptions['programs'] as $program)<option value="{{ $program['id'] }}" @selected(($filters['program_id'] ?? null) === $program['id'])>{{ $program['label'] }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Kategori transaksi</span><select class="select select-bordered select-sm" name="category_id"><option value="">Semua kategori</option>@foreach($filterOptions['categories'] as $category)<option value="{{ $category['id'] }}" @selected(($filters['category_id'] ?? null) === $category['id'])>{{ $category['label'] }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Tipe transaksi</span><select class="select select-bordered select-sm" name="type"><option value="">Penerimaan dan pengeluaran</option>@foreach($filterOptions['types'] as $type)<option value="{{ $type['id'] }}" @selected(($filters['type'] ?? null) === $type['id'])>{{ $type['label'] }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs font-semibold">Status</span><select class="select select-bordered select-sm" name="status"><option value="">POSTED saja (canonical)</option><option value="posted" @selected(($filters['status'] ?? null) === 'posted')>POSTED</option></select></label>
                <div class="flex items-end"><button class="btn btn-primary btn-sm w-full" type="submit">Terapkan filter</button></div>
            </form>
        </div>
    </section>

    <section class="mb-8">
        <div class="mb-3 flex items-center gap-2"><span class="badge badge-success badge-outline">ACTUAL</span><h2 class="font-semibold">Posisi financial resmi</h2></div>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-financial-v2.metric title="Saldo Awal" :value="$rupiah($report['summary']['opening_balance'])" />
            <x-financial-v2.metric title="Total Penerimaan" :value="$rupiah($report['summary']['receipts'])" tone="success" />
            <x-financial-v2.metric title="Total Pengeluaran" :value="$rupiah($report['summary']['expenses'])" tone="warning" />
            <x-financial-v2.metric title="Saldo Akhir" :value="$rupiah($report['summary']['closing_balance'])" tone="success" />
        </div>
        <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-950"><div class="flex flex-wrap items-center justify-between gap-3"><div><span class="badge badge-warning badge-outline">PLAN</span><p class="mt-2 text-sm">Rencana penggunaan dari program dan alokasi yang aktif. Angka ini tidak masuk pengeluaran aktual.</p></div><p class="text-xl font-bold">{{ $rupiah($report['summary']['planned_usage']) }}</p></div></div>
    </section>

    <x-financial-v2.table title="Saldo Per Dana" class="mb-8 hidden md:block">
        <thead><tr><th>Dana</th><th class="text-right">Saldo awal</th><th class="text-right">Penerimaan</th><th class="text-right">Pengeluaran</th><th class="text-right">Movement</th><th class="text-right">Saldo akhir</th></tr></thead>
        <tbody>@forelse($report['funds'] as $fund)<tr><td><a class="font-semibold text-emerald-800 hover:underline" href="{{ route('financial-v2.funds.show', ['fund' => $fund['fund_id'], 'entity' => $entity?->id]) }}">{{ $fund['name'] }}</a><div class="text-xs opacity-60">{{ $fund['code'] }} · {{ $fund['restriction'] ?: 'Tanpa pembatasan tercatat' }}</div></td><td class="text-right font-mono">{{ $rupiah($fund['opening_fund_balance']) }}</td><td class="text-right font-mono text-emerald-700">{{ $rupiah($fund['receipts']) }}</td><td class="text-right font-mono text-rose-700">{{ $rupiah($fund['expenses']) }}</td><td class="text-right font-mono">{{ $rupiah($fund['movement']) }}</td><td class="text-right font-mono font-bold">{{ $rupiah($fund['fund_balance']) }}</td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-base-content/60">Belum ada Dana Financial V2 dalam cakupan ini.</td></tr>@endforelse</tbody>
    </x-financial-v2.table>
    <section class="mb-8 space-y-3 md:hidden" aria-label="Saldo Per Dana">
        <h2 class="font-semibold">Saldo Per Dana</h2>@forelse($report['funds'] as $fund)<article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm"><a class="font-semibold text-emerald-800" href="{{ route('financial-v2.funds.show', ['fund' => $fund['fund_id'], 'entity' => $entity?->id]) }}">{{ $fund['name'] }}</a><p class="mt-1 text-xs opacity-60">{{ $fund['code'] }}</p><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="opacity-60">Saldo awal</dt><dd class="font-mono">{{ $rupiah($fund['opening_fund_balance']) }}</dd></div><div><dt class="opacity-60">Saldo akhir</dt><dd class="font-mono font-bold">{{ $rupiah($fund['fund_balance']) }}</dd></div><div><dt class="opacity-60">Penerimaan</dt><dd class="font-mono text-emerald-700">{{ $rupiah($fund['receipts']) }}</dd></div><div><dt class="opacity-60">Pengeluaran</dt><dd class="font-mono text-rose-700">{{ $rupiah($fund['expenses']) }}</dd></div></dl></article>@empty<p class="rounded-2xl bg-base-100 p-4 text-sm opacity-60">Belum ada Dana Financial V2 dalam cakupan ini.</p>@endforelse
    </section>

    <section class="mb-8 grid gap-6 xl:grid-cols-2">
        <x-financial-v2.table title="Penerimaan ZISWAF · ACTUAL" class="hidden md:block">
            <thead><tr><th>Dana</th><th class="text-right">Total penerimaan</th></tr></thead>
            <tbody>@forelse($report['income_by_fund'] as $income)<tr><td>{{ $income['fund_name'] }}<div class="text-xs opacity-60">{{ $income['fund_code'] }}</div></td><td class="text-right font-mono font-semibold text-emerald-700">{{ $rupiah($income['amount']) }}</td></tr>@empty<tr><td colspan="2" class="py-8 text-center text-base-content/60">Belum ada penerimaan posted.</td></tr>@endforelse</tbody>
        </x-financial-v2.table>
        <x-financial-v2.table title="Pengeluaran Non-Program · ACTUAL" class="hidden md:block">
            <thead><tr><th>Dana</th><th class="text-right">Pengeluaran aktual</th></tr></thead>
            <tbody>@forelse($report['non_program_expenses'] as $expense)<tr><td>{{ $expense['fund_name'] }}<div class="text-xs opacity-60">{{ $expense['fund_code'] }}</div></td><td class="text-right font-mono font-semibold text-rose-700">{{ $rupiah($expense['amount']) }}</td></tr>@empty<tr><td colspan="2" class="py-8 text-center text-base-content/60">Tidak ada pengeluaran non-program posted.</td></tr>@endforelse</tbody>
        </x-financial-v2.table>
    </section>
    <section class="mb-8 grid gap-4 md:hidden"><article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm"><h2 class="font-semibold">Penerimaan ZISWAF · ACTUAL</h2><dl class="mt-3 space-y-3 text-sm">@forelse($report['income_by_fund'] as $income)<div class="flex justify-between gap-3"><dt>{{ $income['fund_name'] }}</dt><dd class="font-mono font-semibold text-emerald-700">{{ $rupiah($income['amount']) }}</dd></div>@empty<p class="opacity-60">Belum ada penerimaan posted.</p>@endforelse</dl></article><article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm"><h2 class="font-semibold">Pengeluaran Non-Program · ACTUAL</h2><dl class="mt-3 space-y-3 text-sm">@forelse($report['non_program_expenses'] as $expense)<div class="flex justify-between gap-3"><dt>{{ $expense['fund_name'] }}</dt><dd class="font-mono font-semibold text-rose-700">{{ $rupiah($expense['amount']) }}</dd></div>@empty<p class="opacity-60">Tidak ada pengeluaran non-program posted.</p>@endforelse</dl></article></section>

    <x-financial-v2.table title="Program & Rencana Penggunaan Dana" class="mb-8 hidden md:block">
        <thead><tr><th>Program</th><th>Dana sumber</th><th class="text-right">Anggaran / Alokasi<br><span class="font-normal opacity-60">PLAN</span></th><th class="text-right">Realisasi<br><span class="font-normal opacity-60">POSTED link</span></th><th class="text-right">Pengeluaran aktual</th><th>Status</th></tr></thead>
        <tbody>@forelse($report['programs'] as $program)<tr><td><a class="font-semibold text-emerald-800 hover:underline" href="{{ route('financial-v2.ziswaf-v2.program', ['program' => $program['program_id'], 'entity' => $entity?->id, 'from' => $filters['from'], 'through' => $filters['through']]) }}">{{ $program['name'] }}</a><div class="text-xs opacity-60">{{ $program['code'] }}</div></td><td class="text-xs">@forelse($program['funding_sources'] as $source)<div>{{ $source['fund_name'] }} <span class="opacity-60">({{ $rupiah($source['allocated']) }} plan · {{ $rupiah($source['realized']) }} actual)</span></div>@empty—@endforelse</td><td class="text-right font-mono text-amber-700">{{ $rupiah($program['allocation']) }}</td><td class="text-right font-mono">{{ $rupiah($program['realization']) }}</td><td class="text-right font-mono font-semibold text-rose-700">{{ $rupiah($program['actual_expense']) }}</td><td><span class="badge badge-sm {{ $badge($program['status']) }}">{{ $program['status'] }}</span></td></tr>@empty<tr><td colspan="6" class="py-8 text-center text-base-content/60">Belum ada program dengan rencana atau pengeluaran posted pada periode ini.</td></tr>@endforelse</tbody>
    </x-financial-v2.table>
    <section class="mb-8 space-y-3 md:hidden" aria-label="Program dan rencana penggunaan dana"><h2 class="font-semibold">Program &amp; Rencana</h2>@forelse($report['programs'] as $program)<article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm"><div class="flex items-start justify-between gap-3"><a class="font-semibold text-emerald-800" href="{{ route('financial-v2.ziswaf-v2.program', ['program' => $program['program_id'], 'entity' => $entity?->id, 'from' => $filters['from'], 'through' => $filters['through']]) }}">{{ $program['name'] }}</a><span class="badge badge-sm {{ $badge($program['status']) }}">{{ $program['status'] }}</span></div><p class="mt-1 text-xs opacity-60">{{ $program['code'] }}</p><dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="opacity-60">Alokasi · PLAN</dt><dd class="font-mono text-amber-700">{{ $rupiah($program['allocation']) }}</dd></div><div><dt class="opacity-60">Actual expense</dt><dd class="font-mono text-rose-700">{{ $rupiah($program['actual_expense']) }}</dd></div><div><dt class="opacity-60">Realisasi posted</dt><dd class="font-mono">{{ $rupiah($program['realization']) }}</dd></div><div><dt class="opacity-60">Sisa alokasi</dt><dd class="font-mono">{{ $rupiah($program['remaining']) }}</dd></div></dl></article>@empty<p class="rounded-2xl bg-base-100 p-4 text-sm opacity-60">Belum ada program pada periode ini.</p>@endforelse</section>

    <x-financial-v2.table title="Riwayat Transaksi ZISWAF · POSTED" class="mb-8 hidden md:block">
        <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Dana</th><th>Program</th><th>Kategori</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th>Status</th></tr></thead>
        <tbody>@forelse($report['transactions'] as $transaction)<tr><td class="whitespace-nowrap">{{ $transaction['date'] }}</td><td><a class="font-medium hover:underline" href="{{ route('financial-v2.transactions.show', $transaction['transaction_id']) }}">{{ $transaction['description'] ?: 'Transaksi Financial V2' }}</a></td><td>{{ $transaction['fund'] }}</td><td>{{ $transaction['program'] }}</td><td>{{ $transaction['category'] }}</td><td class="text-right font-mono text-emerald-700">{{ $transaction['in'] === '0.00' ? '—' : $rupiah($transaction['in']) }}</td><td class="text-right font-mono text-rose-700">{{ $transaction['out'] === '0.00' ? '—' : $rupiah($transaction['out']) }}</td><td><span class="badge badge-success badge-sm">{{ $transaction['status'] }}</span></td></tr>@empty<tr><td colspan="8" class="py-8 text-center text-base-content/60">Tidak ada transaksi posted pada periode ini.</td></tr>@endforelse</tbody>
    </x-financial-v2.table>
    <section class="mb-8 space-y-3 md:hidden" aria-label="Riwayat transaksi ZISWAF"><h2 class="font-semibold">Riwayat Transaksi · POSTED</h2>@forelse($report['transactions'] as $transaction)<article class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm"><div class="flex items-start justify-between gap-3"><a class="font-semibold text-emerald-800" href="{{ route('financial-v2.transactions.show', $transaction['transaction_id']) }}">{{ $transaction['description'] ?: 'Transaksi Financial V2' }}</a><span class="badge badge-success badge-sm">POSTED</span></div><p class="mt-1 text-xs opacity-60">{{ $transaction['date'] }} · {{ $transaction['fund'] }}</p><p class="mt-3 text-sm">{{ $transaction['program'] }} · {{ $transaction['category'] }}</p><div class="mt-3 flex justify-between gap-3 font-mono text-sm"><span class="text-emerald-700">{{ $transaction['in'] === '0.00' ? '—' : '+'.$rupiah($transaction['in']) }}</span><span class="text-rose-700">{{ $transaction['out'] === '0.00' ? '—' : '−'.$rupiah($transaction['out']) }}</span></div></article>@empty<p class="rounded-2xl bg-base-100 p-4 text-sm opacity-60">Tidak ada transaksi posted pada periode ini.</p>@endforelse</section>

    <section class="rounded-2xl border {{ $report['diagnostics']['fund_balance_reconciled'] && $report['diagnostics']['actual_expense_reconciled'] ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50' }} p-4 text-sm">
        <p class="font-semibold">Validasi laporan</p><p class="mt-1">{{ $report['diagnostics']['message'] }}</p><div class="mt-3 flex flex-wrap gap-2"><span class="badge {{ $report['diagnostics']['fund_balance_reconciled'] ? 'badge-success' : 'badge-warning' }}">Saldo Dana {{ $report['diagnostics']['fund_balance_reconciled'] ? 'reconcile' : 'perlu ditinjau' }}</span><span class="badge {{ $report['diagnostics']['actual_expense_reconciled'] ? 'badge-success' : 'badge-warning' }}">Pengeluaran aktual {{ $report['diagnostics']['actual_expense_reconciled'] ? 'reconcile' : 'perlu ditinjau' }}</span><span class="badge badge-success">Plan terpisah dari Actual</span></div>
    </section>
    @if($report && $entity)
        @include('masjid.mrj.admin.financial-v2.distributions.report')
    @endif
@endsection
