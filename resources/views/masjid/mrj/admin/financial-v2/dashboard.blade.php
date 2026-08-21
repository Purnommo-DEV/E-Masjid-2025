@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Dashboard Keuangan')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-emerald-700">Dashboard Keuangan</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight sm:text-3xl">Catat apa yang terjadi, sistem menjaga pencatatannya.</h1>
            <p class="mt-2 max-w-2xl text-sm text-base-content/65">Saldo di halaman ini dihitung dari pencatatan resmi yang sudah selesai, bukan dari draft transaksi.</p>
        </div>
        @if ($entities->count() > 1 || ! $entity)
            <form method="GET" class="flex w-full gap-2 sm:w-auto">
                <select name="entity" class="select select-bordered w-full" onchange="this.form.submit()">
                    <option value="">Pilih entitas keuangan</option>
                    @foreach ($entities as $availableEntity)
                        <option value="{{ $availableEntity->id }}" @selected($entity?->id === $availableEntity->id)>{{ $availableEntity->name }}</option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950">
            <span>Belum ada entitas keuangan aktif yang dapat dipakai. Halaman operasional sudah siap; aktifkan dan konfigurasi master yang disetujui terlebih dahulu. Tidak ada saldo atau transaksi historis yang dibuat oleh halaman ini.</span>
        </div>
    @else
        @php
            $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
            $statusLabel = fn ($status) => ['posted' => 'Dicatat resmi', 'draft' => 'Draft', 'submitted' => 'Dikirim', 'verified' => 'Dalam pemeriksaan', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', 'reversed' => 'Dibalik'][$status] ?? ucfirst((string) $status);
        @endphp
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl bg-emerald-800 p-5 text-white shadow-sm">
                <p class="text-sm text-emerald-100">Total kas & rekening</p>
                <p class="mt-2 text-2xl font-bold">{{ $rupiah($summary['totalBalance']) }}</p>
                <p class="mt-2 text-xs text-emerald-100">Per {{ \Carbon\Carbon::parse($summary['asOf'])->translatedFormat('d F Y') }}</p>
            </article>
            <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                <p class="text-sm text-base-content/60">Pemasukan bulan ini</p>
                <p class="mt-2 text-2xl font-bold text-emerald-700">{{ $rupiah($summary['activity']['receipts']) }}</p>
                <p class="mt-2 text-xs text-base-content/55">Hanya transaksi yang dicatat resmi</p>
            </article>
            <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                <p class="text-sm text-base-content/60">Pengeluaran bulan ini</p>
                <p class="mt-2 text-2xl font-bold text-rose-700">{{ $rupiah($summary['activity']['payments']) }}</p>
                <p class="mt-2 text-xs text-base-content/55">Tidak termasuk transfer</p>
            </article>
            <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                <p class="text-sm text-base-content/60">Transfer bulan ini</p>
                <p class="mt-2 text-2xl font-bold text-sky-700">{{ $rupiah($summary['activity']['transfers']) }}</p>
                <p class="mt-2 text-xs text-base-content/55">Perpindahan rekening, bukan pemasukan/pengeluaran</p>
            </article>
        </section>

        <section class="mt-6 grid gap-5 lg:grid-cols-2">
            <article class="min-w-0 rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                <div class="flex items-center justify-between gap-3">
                    <div><h2 class="font-bold">Saldo kas & rekening</h2><p class="text-xs text-base-content/60">Dari pencatatan resmi yang sudah selesai.</p></div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.transactions.create', ['operation' => 'transfer', 'entity' => $entity->id]) }}">Transfer</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($summary['financialAccounts'] as $account)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-base-200 px-3 py-3">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $account['name'] }}</p><p class="text-xs text-base-content/55">{{ str_replace('_', ' ', $account['type']) }} · perubahan bulan ini {{ $account['periodChange'][0] === '-' ? '−' : '+' }}{{ $rupiah(ltrim($account['periodChange'], '-')) }}</p></div>
                            <p class="shrink-0 text-sm font-bold">{{ $rupiah($account['balance']) }}</p>
                        </div>
                    @empty
                        <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/65">Belum ada rekening/kas aktif. Konfigurasi master tetap dapat dilakukan tanpa membuat saldo awal atau transaksi.</p>
                    @endforelse
                </div>
            </article>
            <article class="min-w-0 rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
                <div class="flex items-center justify-between gap-3">
                    <div><h2 class="font-bold">Saldo Dana</h2><p class="text-xs text-base-content/60">Penerimaan dikurangi penggunaan dana. Likuiditas rekening/kas ditampilkan terpisah.</p></div>
                    <a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.funds.index', ['entity' => $entity->id]) }}">Lihat dana</a>
                </div>
                <div class="mt-4 space-y-3">
                    @forelse ($summary['funds'] as $fund)
                        <div class="flex items-center justify-between gap-3 rounded-xl bg-base-200 px-3 py-3">
                            <div class="min-w-0"><p class="truncate text-sm font-semibold">{{ $fund['name'] }}</p><p class="text-xs text-base-content/55">{{ $fund['classification'] === 'unrestricted' ? 'Tidak terikat' : 'Dana '.str_replace('_', ' ', (string) $fund['classification']) }}</p></div>
                            <div class="shrink-0 text-right"><p class="text-sm font-bold">{{ $rupiah($fund['fundBalance']) }}</p><p class="text-xs text-base-content/55">Likuiditas {{ $rupiah($fund['availableLiquidity']) }}</p></div>
                        </div>
                    @empty
                        <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/65">Belum ada dana aktif. Dana tetap terpisah dari program dan rekening.</p>
                    @endforelse
                </div>
            </article>
        </section>

        @php
            $periodLabel = match ($summary['controls']['periodStatus']) {
                'open' => 'Terbuka',
                'soft_closed' => 'Dalam penutupan',
                'hard_closed' => 'Ditutup',
                default => 'Belum tersedia',
            };
            $reconciliationCount = $summary['controls']['unresolvedReconciliations'];
        @endphp
        <section class="mt-6 grid gap-4 sm:grid-cols-2">
            <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300"><p class="text-sm text-base-content/60">Status periode</p><p class="mt-2 text-xl font-bold">{{ $periodLabel }}</p><p class="mt-2 text-xs text-base-content/55">{{ $summary['controls']['periodName'] ?? 'Belum ada periode untuk tanggal ini.' }}</p><a class="btn btn-ghost btn-sm mt-3" href="{{ route('financial-v2.controls.index', ['entity' => $entity->id]) }}">Lihat kontrol</a></article>
            <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300"><p class="text-sm text-base-content/60">Rekonsiliasi rekening</p><p class="mt-2 text-xl font-bold">{{ $reconciliationCount === 0 ? 'Tidak ada yang tertunda' : $reconciliationCount.' perlu ditindaklanjuti' }}</p><p class="mt-2 text-xs text-base-content/55">Periksa saldo sistem dengan saldo rekening atau kas.</p><a class="btn btn-ghost btn-sm mt-3" href="{{ route('financial-v2.controls.index', ['entity' => $entity->id]) }}">Buka rekonsiliasi</a></article>
        </section>

        <section class="mt-6 rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div><h2 class="font-bold">Transaksi terbaru</h2><p class="text-xs text-base-content/60">Draft dan transaksi resmi tampil bersama agar tindak lanjut jelas.</p></div>
                <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.transactions.index', ['entity' => $entity->id]) }}">Lihat riwayat</a>
            </div>
            <div class="mt-4 space-y-3 lg:hidden">
                @forelse ($summary['recent'] as $transaction)
                    <a href="{{ route('financial-v2.transactions.show', $transaction) }}" class="block rounded-xl border border-base-300 p-4 transition hover:bg-base-200">
                        <div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{{ $transaction->type?->name }}</p><p class="mt-1 text-xs text-base-content/60">{{ $transaction->accounting_date->translatedFormat('d M Y') }} · {{ $transaction->primaryFinancialAccount?->name ?? '—' }}</p></div><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></div>
                        <p class="mt-2 truncate text-sm text-base-content/70">{{ $transaction->description ?: 'Tanpa keterangan' }}</p>
                        <p class="mt-2 font-bold">{{ $rupiah($transaction->gross_amount) }}</p>
                    </a>
                @empty
                    <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/65">Belum ada transaksi yang dicatat.</p>
                @endforelse
            </div>
            <div class="mt-4 hidden overflow-x-auto lg:block">
                <table class="table table-sm"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th>Rekening</th><th>Dana</th><th class="text-right">Nominal</th><th>Status</th></tr></thead>
                    <tbody>@forelse ($summary['recent'] as $transaction)<tr class="hover"><td>{{ $transaction->accounting_date->format('d/m/Y') }}</td><td><a class="link link-hover font-medium" href="{{ route('financial-v2.transactions.show', $transaction) }}">{{ $transaction->type?->name }}</a></td><td class="max-w-xs truncate">{{ $transaction->description ?: '—' }}</td><td>{{ $transaction->primaryFinancialAccount?->name ?? '—' }}</td><td>{{ $transaction->splits->first()?->fund?->name ?? '—' }}</td><td class="text-right font-semibold">{{ $rupiah($transaction->gross_amount) }}</td><td><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></td></tr>@empty<tr><td colspan="7" class="py-8 text-center text-base-content/60">Belum ada transaksi yang dicatat.</td></tr>@endforelse</tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
