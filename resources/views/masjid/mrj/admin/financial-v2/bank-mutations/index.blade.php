@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Mutasi Bank')

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $statusLabel = fn ($status) => ['draft'=>'Draft','submitted'=>'Diajukan','verified'=>'Diperiksa','approved'=>'Disetujui','posted'=>'Dicatat resmi','cancelled'=>'Dihapus','reversed'=>'Dibalik','rejected'=>'Ditolak'][$status] ?? ucfirst($status);
        $years = range(2026, max(2027, (int) now()->format('Y') + 1));
        $isTargetEntity = $entity && $configurationStatus['entity_id'] === $entity->id;
    @endphp
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h1 class="text-2xl font-bold">Mutasi Bank</h1><p class="mt-1 text-sm text-base-content/65">Input mutasi rekening melalui alur pemeriksaan dan pencatatan Financial V2.</p></div>
        @if($isTargetEntity && $configurationStatus['active'])<a class="btn btn-primary" href="{{ route('financial-v2.bank-mutations.create', ['entity' => $entity->id]) }}">+ Tambah Mutasi</a>@endif
    </div>

    <section class="mb-5 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sky-950 shadow-sm sm:p-5" data-bank-configuration>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="font-bold">Konfigurasi Mutasi Bank</h2>
                    <span class="badge {{ $configurationStatus['active'] ? 'badge-success' : 'badge-warning' }}">{{ $configurationStatus['active'] ? 'Aktif' : 'Belum aktif' }}</span>
                </div>
                <dl class="mt-3 grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-xs text-sky-900/65">Rekening</dt><dd class="font-semibold">BNI ZISWAF</dd></div>
                    <div><dt class="text-xs text-sky-900/65">Fund</dt><dd class="font-semibold">Infaq &amp; Tromol</dd></div>
                    <div><dt class="text-xs text-sky-900/65">Effective date</dt><dd class="font-semibold">30/06/2026</dd></div>
                    <div><dt class="text-xs text-sky-900/65">Kontrol</dt><dd class="font-semibold">Maker + checker · statement min. 1</dd></div>
                </dl>
                <p class="mt-3 text-xs leading-5 text-sky-900/70">Kategori: Jasa Giro/Bunga, PPH, Biaya Administrasi Rekening, Biaya Administrasi Kartu, dan Biaya Transfer Bank.</p>
            </div>
            @if($isTargetEntity && ! $configurationStatus['active'])
                <button type="button" class="btn btn-primary shrink-0" onclick="document.getElementById('bank-mutation-config-modal').showModal()">Aktifkan Konfigurasi</button>
            @endif
        </div>
    </section>

    @if($isTargetEntity && ! $configurationStatus['active'])
        <dialog id="bank-mutation-config-modal" class="modal">
            <div class="modal-box max-w-lg">
                <h3 class="text-lg font-bold">Aktifkan Konfigurasi Mutasi Bank</h3>
                <p class="mt-4 leading-7">Aktifkan konfigurasi Mutasi Bank untuk BNI ZISWAF dengan Dana Infaq &amp; Tromol?</p>
                <p class="mt-3 rounded-xl bg-base-200 p-3 text-sm">Perubahan hanya pada configuration. Tidak ada transaksi keuangan yang dibuat.</p>
                <div class="modal-action">
                    <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
                    <form method="POST" action="{{ route('financial-v2.bank-mutations.configure') }}">
                        @csrf
                        <button class="btn btn-primary">Aktifkan</button>
                    </form>
                </div>
            </div>
            <form method="dialog" class="modal-backdrop"><button aria-label="Tutup dialog">close</button></form>
        </dialog>
    @endif

    @if(!$entity)
        <div class="alert alert-warning">Pilih entitas keuangan aktif terlebih dahulu.</div>
    @elseif(!$isTargetEntity || ! $configurationStatus['active'])
        <div class="alert alert-warning items-start"><span>Policy Mutasi Bank belum aktif. Sistem menahan input sampai konfigurasi rekening, Dana, kategori, rule, bukti, dan approval lengkap.</span></div>
    @else
        <form method="GET" class="mb-5 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
                <label class="form-control"><span class="label-text text-xs">Tahun</span><select name="year" class="select select-bordered select-sm"><option value="">Semua tahun</option>@foreach($years as $year)<option value="{{ $year }}" @selected(($filters['year'] ?? '') == $year)>{{ $year }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Bulan</span><select name="month" class="select select-bordered select-sm"><option value="">Semua bulan</option>@foreach(range(1,12) as $month)<option value="{{ $month }}" @selected(($filters['month'] ?? '') == $month)>{{ \Carbon\CarbonImmutable::create(2026, $month)->translatedFormat('F') }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Rekening</span><select name="financial_account_id" class="select select-bordered select-sm"><option value="">Semua rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected(($filters['financial_account_id'] ?? '') === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Dana</span><select name="fund_id" class="select select-bordered select-sm"><option value="">Semua Dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected(($filters['fund_id'] ?? '') === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Jenis</span><select name="category_id" class="select select-bordered select-sm"><option value="">Semua jenis</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') === $category->id)>{{ \App\Domain\FinancialV2\BankMutationService::CATEGORY_CODES[$category->code] ?? $category->name }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text text-xs">Status</span><select name="status" class="select select-bordered select-sm"><option value="">Aktif</option><option value="all" @selected(($filters['status'] ?? '') === 'all')>Semua status</option>@foreach(['draft','submitted','verified','approved','posted','cancelled'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $statusLabel($status) }}</option>@endforeach</select></label>
            </div>
            <div class="mt-3 flex justify-end gap-2"><a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.bank-mutations.index', ['entity' => $entity->id]) }}">Reset</a><button class="btn btn-primary btn-sm">Terapkan filter</button></div>
        </form>

        <div class="overflow-x-auto rounded-2xl bg-base-100 shadow-sm ring-1 ring-base-300">
            <table class="table"><thead><tr><th>Tanggal</th><th>Jenis</th><th>Keterangan</th><th>Rekening</th><th>Dana</th><th class="text-right">Nominal</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>@forelse($transactions as $transaction)
                @php $isReceipt = $transaction->type?->code === 'RCV'; @endphp
                <tr><td>{{ $transaction->accounting_date->format('d/m/Y') }}</td><td class="font-medium">{{ \App\Domain\FinancialV2\BankMutationService::CATEGORY_CODES[$transaction->category?->code] ?? $transaction->category?->name }}</td><td><div class="max-w-xs"><p class="truncate">{{ $transaction->description }}</p><p class="font-mono text-[11px] text-base-content/50">{{ $transaction->source_reference }}</p></div></td><td>{{ $transaction->primaryFinancialAccount?->name }}</td><td>{{ $transaction->splits->first()?->fund?->name ?? '—' }}</td><td class="text-right font-bold {{ $isReceipt ? 'text-emerald-700' : 'text-rose-700' }}">{{ $isReceipt ? '+' : '-' }}{{ $rupiah($transaction->gross_amount) }}</td><td><span class="badge badge-outline">{{ $statusLabel($transaction->status) }}</span></td><td><div class="flex justify-end gap-1 whitespace-nowrap">
                    @if($transaction->status === 'draft')
                        @if($editableBatchIds->contains($transaction->correlation_id))
                            <a class="btn btn-ghost btn-xs" href="{{ route('financial-v2.bank-mutations.batches.edit', ['batch' => $transaction->correlation_id, 'entity' => $entity->id]) }}">Ubah batch</a>
                        @endif
                        <form method="POST" action="{{ route('financial-v2.bank-mutations.submit', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary btn-xs">Ajukan</button></form>
                        @if($editableBatchIds->contains($transaction->correlation_id))
                            <form method="POST" action="{{ route('financial-v2.bank-mutations.batches.destroy', ['batch' => $transaction->correlation_id, 'entity' => $entity->id]) }}" onsubmit="return confirm('Hapus seluruh draft dalam batch Mutasi Bank ini?')">@csrf @method('DELETE')<button class="btn btn-ghost btn-xs text-error">Hapus batch</button></form>
                        @endif
                    @elseif($transaction->status === 'submitted')
                        <form method="POST" action="{{ route('financial-v2.bank-mutations.verify', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary btn-xs">Periksa</button></form>
                    @elseif($transaction->status === 'verified')
                        <form method="POST" action="{{ route('financial-v2.bank-mutations.approve', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary btn-xs">Setujui</button></form>
                    @elseif($transaction->status === 'approved')
                        <form method="POST" action="{{ route('financial-v2.bank-mutations.post', ['transaction' => $transaction, 'entity' => $entity->id]) }}" onsubmit="return confirm('Catat resmi transaksi ini? Data posted tidak dapat diubah atau dihapus.')">@csrf<button class="btn btn-success btn-xs">Catat resmi</button></form>
                    @else <span class="text-xs text-base-content/45">—</span>
                    @endif
                </div></td></tr>
            @empty<tr><td colspan="8" class="py-10 text-center text-sm text-base-content/60">Belum ada Mutasi Bank yang cocok.</td></tr>@endforelse</tbody></table>
        </div>
        <div class="mt-5">{{ $transactions->links() }}</div>
    @endif
@endsection
