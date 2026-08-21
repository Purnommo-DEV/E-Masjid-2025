@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Riwayat Transaksi')

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $statusLabel = fn ($status) => [
            'draft' => 'Draft', 'submitted' => 'Dikirim', 'verified' => 'Dalam pemeriksaan', 'approved' => 'Disetujui',
            'posted' => 'Dicatat resmi', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan', 'reversed' => 'Dibalik',
        ][$status] ?? ucfirst((string) $status);
        $fundLabel = fn ($transaction) => $transaction->interfundTransfer
            ? (($transaction->interfundTransfer->sourceFund?->name ?? '—').' → '.($transaction->interfundTransfer->destinationFund?->name ?? '—'))
            : ($transaction->splits->first()?->fund?->name ?? '—');
        $financialAccountLabel = fn ($transaction) => $transaction->primaryFinancialAccount
            ? $transaction->primaryFinancialAccount->name.($transaction->interfundTransfer ? ' · atribusi' : '')
            : '—';
    @endphp
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"><div><h1 class="text-2xl font-bold">Riwayat Transaksi</h1><p class="mt-1 text-sm text-base-content/65">Default hanya menampilkan transaksi yang sudah dicatat resmi. Draft dapat ditinjau melalui filter status bila diperlukan.</p></div><a class="btn btn-primary" href="{{ route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entity?->id]) }}">+ Tambah penerimaan</a></div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih entitas keuangan aktif untuk melihat riwayat. Data transaksi lama tidak digunakan sebagai pengganti.</span></div>
    @else
        <form method="GET" class="mb-5 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="form-control"><span class="label-text text-xs">Periode</span><input type="month" name="period" value="{{ $filters['period'] ?? '' }}" class="input input-bordered input-sm"></label>
                <label class="form-control"><span class="label-text text-xs">Jenis</span><select name="type" class="select select-bordered select-sm"><option value="">Semua jenis</option><option value="RCV" @selected(($filters['type'] ?? '') === 'RCV')>Penerimaan</option><option value="PAY" @selected(($filters['type'] ?? '') === 'PAY')>Pengeluaran/Realisasi</option><option value="TRF" @selected(($filters['type'] ?? '') === 'TRF')>Transfer rekening</option><option value="IFT" @selected(($filters['type'] ?? '') === 'IFT')>Pindah dana</option></select></label>
                <label class="form-control"><span class="label-text text-xs">Kas/rekening</span><select name="financial_account_id" class="select select-bordered select-sm"><option value="">Semua rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected(($filters['financial_account_id'] ?? '') === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Dana</span><select name="fund_id" class="select select-bordered select-sm"><option value="">Semua dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected(($filters['fund_id'] ?? '') === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Program</span><select name="program_id" class="select select-bordered select-sm"><option value="">Semua program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Kategori</span><select name="category_id" class="select select-bordered select-sm"><option value="">Semua kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Status</span><select name="status" class="select select-bordered select-sm"><option value="posted" @selected(($filters['status'] ?? 'posted') === 'posted')>Dicatat resmi</option><option value="all" @selected(($filters['status'] ?? '') === 'all')>Semua status</option>@foreach(['draft','submitted','verified','approved','rejected','cancelled','reversed'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabel($status) }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Cari</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nomor, keterangan, pihak" class="input input-bordered input-sm"></label>
            </div>
            <div class="mt-3 flex justify-end gap-2"><a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.transactions.index', ['entity' => $entity->id]) }}">Reset</a><button class="btn btn-primary btn-sm">Terapkan filter</button></div>
        </form>

        <div class="space-y-3 lg:hidden">
            @forelse ($transactions as $transaction)
                <a href="{{ route('financial-v2.transactions.show', $transaction) }}" class="block rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{{ $transaction->type?->name }}</p><p class="mt-1 text-xs text-base-content/60">{{ $transaction->accounting_date->translatedFormat('d M Y') }} · {{ $transaction->source_reference }}</p></div><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></div><p class="mt-2 truncate text-sm text-base-content/70">{{ $transaction->description ?: ($transaction->counterparty?->display_name ?? 'Tanpa keterangan') }}</p><div class="mt-3 flex items-end justify-between gap-2 text-xs text-base-content/60"><span>{{ $financialAccountLabel($transaction) }} · {{ $fundLabel($transaction) }}</span><span class="text-sm font-bold text-base-content">{{ $rupiah($transaction->gross_amount) }}</span></div></a>
            @empty <p class="rounded-2xl bg-base-100 p-6 text-center text-sm text-base-content/60">Tidak ada transaksi yang cocok.</p> @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-2xl bg-base-100 shadow-sm ring-1 ring-base-300 lg:block"><table class="table"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Referensi</th><th>Keterangan</th><th>Rekening</th><th>Dana</th><th>Program</th><th class="text-right">Nominal</th><th>Status</th></tr></thead><tbody>@forelse($transactions as $transaction)<tr class="hover"><td>{{ $transaction->accounting_date->format('d/m/Y') }}</td><td><a class="link link-hover font-medium" href="{{ route('financial-v2.transactions.show', $transaction) }}">{{ $transaction->type?->name }}</a></td><td class="font-mono text-xs">{{ $transaction->source_reference }}</td><td class="max-w-xs truncate">{{ $transaction->description ?: ($transaction->counterparty?->display_name ?? '—') }}</td><td>{{ $financialAccountLabel($transaction) }}</td><td>{{ $fundLabel($transaction) }}</td><td>{{ $transaction->splits->first()?->program_id ? ($options['programs']->firstWhere('id', $transaction->splits->first()->program_id)?->name ?? '—') : '—' }}</td><td class="text-right font-semibold">{{ $rupiah($transaction->gross_amount) }}</td><td><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></td></tr>@empty<tr><td colspan="9" class="py-10 text-center text-sm text-base-content/60">Tidak ada transaksi yang cocok.</td></tr>@endforelse</tbody></table></div>
        <div class="mt-5">{{ $transactions->links() }}</div>
    @endif
@endsection
