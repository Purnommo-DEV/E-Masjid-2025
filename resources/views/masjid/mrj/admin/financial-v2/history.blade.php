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
                <article class="block rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold">{{ $transaction->type?->name }}</p>
                            <p class="mt-1 text-xs text-base-content/60">Tanggal aktual {{ $transaction->effective_business_date->format('d/m/Y') }} · {{ $transaction->source_reference }}</p>
                            <p class="mt-1 text-xs text-base-content/55">Tanggal akuntansi {{ $transaction->accounting_date->format('d/m/Y') }} · Dicatat {{ $transaction->created_at?->format('d/m/Y') ?? '—' }}</p>
                            @if ($transaction->latestDateCorrection)
                                <span class="badge badge-info badge-sm mt-2">Tanggal aktual dikoreksi</span>
                            @endif
                        </div>
                        <span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span>
                    </div>
                    <p class="mt-2 text-sm text-base-content/70">{{ $transaction->display_description ?: ($transaction->counterparty?->display_name ?? 'Tanpa keterangan') }}</p>
                    <div class="mt-3 flex items-end justify-between gap-2 text-xs text-base-content/60"><span>{{ $financialAccountLabel($transaction) }} · {{ $fundLabel($transaction) }}</span><span class="text-sm font-bold text-base-content">{{ $rupiah($transaction->gross_amount) }}</span></div>
                    <div class="mt-3 flex justify-end gap-2"><a class="btn btn-ghost btn-xs" href="{{ route('financial-v2.transactions.show', $transaction) }}">Detail</a>@if(auth()->user()?->hasRole('SuperAdmin') && in_array($transaction->status, ['posted', 'reversed'], true))<button type="button" class="btn btn-outline btn-xs" data-edit-description data-action="{{ route('financial-v2.transactions.description.update', ['transaction' => $transaction, 'entity' => $entity->id]) }}" data-description="{{ $transaction->display_description }}">Edit Keterangan</button>@endif</div>
                </article>
            @empty
                <p class="rounded-2xl bg-base-100 p-6 text-center text-sm text-base-content/60">Tidak ada transaksi yang cocok.</p>
            @endforelse
        </div>
        <div class="hidden overflow-x-auto rounded-2xl bg-base-100 shadow-sm ring-1 ring-base-300 lg:block">
            <table class="table">
                <thead><tr><th>Tanggal</th><th>Jenis</th><th>Referensi</th><th>Keterangan</th><th>Rekening</th><th>Dana</th><th>Program</th><th class="text-right">Nominal</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    @forelse($transactions as $transaction)
                        <tr class="hover">
                            <td><span class="block font-medium">Aktual {{ $transaction->effective_business_date->format('d/m/Y') }}</span><span class="block text-xs text-base-content/55">Akuntansi {{ $transaction->accounting_date->format('d/m/Y') }}</span><span class="block text-xs text-base-content/55">Dicatat {{ $transaction->created_at?->format('d/m/Y') ?? '—' }}</span>@if($transaction->latestDateCorrection)<span class="badge badge-info badge-sm mt-1">Dikoreksi</span>@endif</td>
                            <td><a class="link link-hover font-medium" href="{{ route('financial-v2.transactions.show', $transaction) }}">{{ $transaction->type?->name }}</a></td>
                            <td class="font-mono text-xs">{{ $transaction->source_reference }}</td>
                            <td class="max-w-xs">{{ $transaction->display_description ?: ($transaction->counterparty?->display_name ?? '—') }}</td>
                            <td>{{ $financialAccountLabel($transaction) }}</td><td>{{ $fundLabel($transaction) }}</td>
                            <td>{{ $transaction->splits->first()?->program_id ? ($options['programs']->firstWhere('id', $transaction->splits->first()->program_id)?->name ?? '—') : '—' }}</td>
                            <td class="text-right font-semibold">{{ $rupiah($transaction->gross_amount) }}</td>
                            <td><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></td>
                            <td>@if(auth()->user()?->hasRole('SuperAdmin') && in_array($transaction->status, ['posted', 'reversed'], true))<button type="button" class="btn btn-outline btn-xs whitespace-nowrap" data-edit-description data-action="{{ route('financial-v2.transactions.description.update', ['transaction' => $transaction, 'entity' => $entity->id]) }}" data-description="{{ $transaction->display_description }}">Edit Keterangan</button>@else<span class="text-xs text-base-content/45">—</span>@endif</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="py-10 text-center text-sm text-base-content/60">Tidak ada transaksi yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $transactions->links() }}</div>

        @if(auth()->user()?->hasRole('SuperAdmin'))
            <dialog id="description-amendment-modal" class="modal" @if($errors->has('description')) open @endif>
                <div class="modal-box">
                    <form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3" aria-label="Tutup">✕</button></form>
                    <h2 class="text-lg font-bold">Edit Keterangan</h2>
                    <p class="mt-2 text-sm text-base-content/65">Perubahan disimpan sebagai amendment ber-audit. Transaksi, jurnal, ledger, dan saldo asli tidak diubah.</p>
                    <form id="description-amendment-form" method="POST" action="{{ old('description_action', '#') }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="entity" value="{{ $entity->id }}">
                        <input type="hidden" name="description_action" id="description-amendment-action" value="{{ old('description_action') }}">
                        <label class="form-control"><span class="label-text font-medium">Keterangan</span><textarea id="description-amendment-input" name="description" rows="5" maxlength="2000" class="textarea textarea-bordered w-full" required>{{ old('description') }}</textarea><span class="label-text-alt">Maksimal 2.000 karakter.</span></label>
                        @error('description')<p class="mt-2 text-sm text-error">{{ $message }}</p>@enderror
                        <div class="modal-action"><button type="button" class="btn btn-ghost" data-close-description>Batal</button><button type="submit" class="btn btn-primary">Simpan perubahan</button></div>
                    </form>
                </div>
                <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
            </dialog>
        @endif
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('description-amendment-modal');
    const form = document.getElementById('description-amendment-form');
    const input = document.getElementById('description-amendment-input');
    const action = document.getElementById('description-amendment-action');
    if (!modal || !form || !input || !action) return;

    document.querySelectorAll('[data-edit-description]').forEach((button) => button.addEventListener('click', () => {
        form.action = button.dataset.action;
        action.value = button.dataset.action;
        input.value = button.dataset.description || '';
        modal.showModal();
        input.focus();
    }));
    document.querySelector('[data-close-description]')?.addEventListener('click', () => modal.close());
    if (modal.hasAttribute('open') && action.value) form.action = action.value;
});
</script>
@endpush
