@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Draft Transaksi')

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $statusLabel = fn ($status) => [
            'draft' => 'Draft', 'submitted' => 'Dikirim', 'verified' => 'Dalam pemeriksaan',
            'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan',
        ][$status] ?? ucfirst((string) $status);
        $bankCategoryCodes = array_keys(\App\Domain\FinancialV2\BankMutationService::CATEGORY_CODES);
        $isBankMutation = fn ($transaction) => in_array($transaction->category?->code, $bankCategoryCodes, true);
        $typeLabel = fn ($transaction) => $isBankMutation($transaction) ? 'Mutasi Bank' : match ($transaction->type?->code) {
            'RCV' => 'Penerimaan', 'PAY' => 'Pengeluaran', 'TRF', 'IFT' => 'Transfer', default => $transaction->type?->name ?? 'Transaksi',
        };
        $fundLabel = fn ($transaction) => $transaction->interfundTransfer
            ? (($transaction->interfundTransfer->sourceFund?->name ?? '—').' → '.($transaction->interfundTransfer->destinationFund?->name ?? '—'))
            : ($transaction->splits->first()?->fund?->name ?? '—');
        $canEdit = fn ($transaction) => $transaction->status === 'draft' && ($isBankMutation($transaction) || in_array($transaction->type?->code, ['RCV', 'PAY'], true));
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Draft Transaksi</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">Temukan dan lanjutkan Penerimaan, Pengeluaran, Transfer, atau Mutasi Bank yang belum dicatat resmi. Draft Realisasi tetap dikelola pada halaman khusus realisasi.</p>
        </div>
        <a class="btn btn-primary" href="{{ route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entity?->id]) }}">+ Buat Penerimaan</a>
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih entitas keuangan aktif untuk melihat draft transaksi.</span></div>
        @if ($entities->isNotEmpty())
            <form method="GET" class="mt-4 flex max-w-md gap-2"><select name="entity" class="select select-bordered grow"><option value="">Pilih entitas</option>@foreach($entities as $availableEntity)<option value="{{ $availableEntity->id }}">{{ $availableEntity->name }}</option>@endforeach</select><button class="btn btn-primary">Pilih</button></form>
        @endif
    @else
        <form method="GET" class="mb-5 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                <label class="form-control"><span class="label-text text-xs">Tahun</span><select name="year" class="select select-bordered select-sm"><option value="">Semua tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected((string) ($filters['year'] ?? '') === (string) $year)>{{ $year }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Jenis</span><select name="type" class="select select-bordered select-sm"><option value="">Semua jenis</option><option value="receipt" @selected(($filters['type'] ?? '') === 'receipt')>Penerimaan</option><option value="payment" @selected(($filters['type'] ?? '') === 'payment')>Pengeluaran</option><option value="transfer" @selected(($filters['type'] ?? '') === 'transfer')>Transfer</option><option value="bank_mutation" @selected(($filters['type'] ?? '') === 'bank_mutation')>Mutasi Bank</option></select></label>
                <label class="form-control"><span class="label-text text-xs">Rekening</span><select name="financial_account_id" class="select select-bordered select-sm"><option value="">Semua rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected(($filters['financial_account_id'] ?? '') === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Status</span><select name="status" class="select select-bordered select-sm"><option value="draft" @selected(($filters['status'] ?? 'draft') === 'draft')>Draft</option><option value="all" @selected(($filters['status'] ?? '') === 'all')>Semua yang belum posted</option>@foreach(['submitted','verified','approved','rejected','cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabel($status) }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Cari</span><input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Keterangan, referensi, rekening, Dana, kategori" class="input input-bordered input-sm"></label>
            </div>
            <div class="mt-3 flex justify-end gap-2"><a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.transactions.drafts', ['entity' => $entity->id]) }}">Reset</a><button class="btn btn-primary btn-sm">Terapkan filter</button></div>
        </form>

        @if ($transactions->isEmpty())
            <section class="rounded-2xl bg-base-100 p-6 text-center shadow-sm ring-1 ring-base-300">
                <p class="font-semibold">Tidak ada draft transaksi.</p>
                <p class="mt-1 text-sm text-base-content/60">Buat transaksi baru atau ubah filter untuk menemukan draft yang sudah ada.</p>
                <div class="mt-4 flex flex-wrap justify-center gap-2">
                    <a class="btn btn-primary btn-sm" href="{{ route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entity->id]) }}">Buat Penerimaan</a>
                    <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.transactions.create', ['operation' => 'payment', 'entity' => $entity->id]) }}">Buat Pengeluaran</a>
                    <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.bank-mutations.create', ['entity' => $entity->id]) }}">Buat Mutasi Bank</a>
                </div>
            </section>
        @else
            <div class="space-y-3 lg:hidden">
                @foreach ($transactions as $transaction)
                    <article class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
                        <div class="flex items-start justify-between gap-3"><div class="min-w-0"><p class="font-semibold">{{ $typeLabel($transaction) }}</p><p class="mt-1 text-xs text-base-content/60">{{ $transaction->accounting_date->translatedFormat('d M Y') }}</p></div><span class="badge badge-outline shrink-0">{{ $statusLabel($transaction->status) }}</span></div>
                        <p class="mt-3 text-xl font-bold">{{ $rupiah($transaction->gross_amount) }}</p>
                        <p class="mt-2 truncate text-sm text-base-content/70">{{ $transaction->description ?: ($transaction->counterparty?->display_name ?? 'Tanpa keterangan') }}</p>
                        <p class="mt-2 text-xs leading-5 text-base-content/60">{{ $transaction->primaryFinancialAccount?->name ?? '—' }} · {{ $fundLabel($transaction) }} · {{ $transaction->category?->name ?? '—' }}</p>
                        <div class="mt-4 flex flex-wrap gap-2">@include('masjid.mrj.admin.financial-v2.drafts._actions', ['transaction' => $transaction])</div>
                    </article>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto rounded-2xl bg-base-100 shadow-sm ring-1 ring-base-300 lg:block">
                <table class="table"><thead><tr><th>Tanggal</th><th>Jenis</th><th class="text-right">Nominal</th><th>Rekening</th><th>Dana</th><th>Kategori</th><th>Keterangan</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
                    @foreach ($transactions as $transaction)
                        <tr class="hover"><td class="whitespace-nowrap">{{ $transaction->accounting_date->format('d/m/Y') }}</td><td class="font-medium">{{ $typeLabel($transaction) }}</td><td class="whitespace-nowrap text-right font-semibold">{{ $rupiah($transaction->gross_amount) }}</td><td>{{ $transaction->primaryFinancialAccount?->name ?? '—' }}</td><td>{{ $fundLabel($transaction) }}</td><td>{{ $transaction->category?->name ?? '—' }}</td><td class="max-w-xs truncate">{{ $transaction->description ?: ($transaction->counterparty?->display_name ?? '—') }}</td><td><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></td><td><div class="flex flex-wrap gap-1">@include('masjid.mrj.admin.financial-v2.drafts._actions', ['transaction' => $transaction])</div></td></tr>
                    @endforeach
                </tbody></table>
            </div>
            <div class="mt-5">{{ $transactions->links() }}</div>
        @endif
    @endif
@endsection
