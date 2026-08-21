@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Saldo Awal')

@section('content')
    <section class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Financial V2 · Rehearsal</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">Saldo Awal</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">Masukkan posisi awal yang telah disetujui beserta sumber dan bukti. Ini bukan cutover dan belum mengaktifkan Ledger V2 sebagai sumber saldo resmi.</p>
        </div>
    </section>

    <section class="card mb-6 border border-base-300 bg-base-100 shadow-sm"><div class="card-body p-4 sm:p-5">
        <form method="GET" action="{{ route('financial-v2.opening-balances.index') }}" class="flex gap-3 sm:items-end">
            <label class="form-control max-w-md flex-1"><span class="label-text text-xs font-semibold">Entitas Financial V2</span><select class="select select-bordered select-sm" name="entity" onchange="this.form.submit()"><option value="">Pilih entitas</option>@foreach ($entities as $availableEntity)<option value="{{ $availableEntity->id }}" @selected($entity?->id === $availableEntity->id)>{{ $availableEntity->name }}</option>@endforeach</select></label>
        </form>
    </div></section>

    @if (! $entity)
        <div class="alert alert-info"><span>Pilih entitas aktif untuk melihat Saldo Awal dan rehearsal yang terisolasi.</span></div>
    @else
        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.4fr)]">
            <div class="card border border-base-300 bg-base-100 shadow-sm"><div class="card-body p-4 sm:p-5">
                <h2 class="font-bold">Mulai Draft Saldo Awal</h2>
                <p class="mb-4 text-sm text-base-content/65">Gunakan referensi rehearsal yang unik. Tanggal posisi dipakai untuk uji terisolasi; bukan keputusan tanggal cutover produksi.</p>
                <form method="POST" action="{{ route('financial-v2.opening-balances.store') }}" class="grid gap-3">@csrf
                    <input type="hidden" name="entity" value="{{ $entity->id }}">
                    <label class="form-control"><span class="label-text text-xs font-semibold">Periode</span><select class="select select-bordered select-sm" name="accounting_period_id" required><option value="">Pilih periode</option>@foreach ($masters['periods'] as $period)<option value="{{ $period->id }}">{{ $period->period_name }} · {{ str_replace('_', ' ', $period->status) }}</option>@endforeach</select></label>
                    <label class="form-control"><span class="label-text text-xs font-semibold">Set Pemetaan Sumber</span><select class="select select-bordered select-sm" name="mapping_set_id" required><option value="">Pilih pemetaan</option>@foreach ($masters['mappingSets'] as $mappingSet)<option value="{{ $mappingSet->id }}">{{ $mappingSet->code }} · {{ $mappingSet->mapping_status }}</option>@endforeach</select></label>
                    <label class="form-control"><span class="label-text text-xs font-semibold">Tanggal posisi untuk rehearsal</span><input class="input input-bordered input-sm" type="date" name="position_date" value="{{ now()->toDateString() }}" required></label>
                    <label class="form-control"><span class="label-text text-xs font-semibold">Referensi rehearsal / sumber</span><input class="input input-bordered input-sm" name="rehearsal_reference" placeholder="REHEARSAL-01" required></label>
                    <label class="form-control"><span class="label-text text-xs font-semibold">Referensi paket bukti</span><input class="input input-bordered input-sm" name="evidence_package_ref" placeholder="paket-bukti/saldo-awal" required></label>
                    <button class="btn btn-primary btn-sm" type="submit">Buat Draft</button>
                </form>
            </div></div>
            <div class="card border border-base-300 bg-base-100 shadow-sm"><div class="card-body p-4 sm:p-5">
                <h2 class="font-bold">Riwayat Saldo Awal</h2>
                <p class="mb-4 text-sm text-base-content/65">Status dan selisih selalu terlihat. Catatan Posted dipertahankan; koreksi dilakukan sebagai adjustment atau reversal terkelola.</p>
                <div class="overflow-x-auto"><table class="table table-sm"><thead><tr><th>Referensi / Periode</th><th>Set Pemetaan</th><th>Baris</th><th>Status</th><th></th></tr></thead><tbody>
                    @forelse ($batches as $batch)
                        @php $tone = match($batch->status) { 'posted' => 'badge-success', 'approved', 'reviewed' => 'badge-warning', default => 'badge-ghost' }; @endphp
                        <tr><td><div class="font-medium">{{ $batch->cutover_reference }}</div><div class="text-xs opacity-60">{{ $batch->period->period_name }} · posisi {{ $batch->cutover_date->toDateString() }}</div></td><td>{{ $batch->mappingSet->code }}</td><td>{{ $batch->lines->count() }}</td><td><span class="badge {{ $tone }} badge-sm">{{ str_replace('_', ' ', $batch->status) }}</span></td><td class="text-right"><a class="btn btn-outline btn-xs" href="{{ route('financial-v2.opening-balances.show', ['openingBalanceBatch' => $batch, 'entity' => $entity->id]) }}">Buka</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-base-content/60">Belum ada rehearsal Saldo Awal.</td></tr>
                    @endforelse
                </tbody></table></div>
            </div></div>
        </section>
    @endif
@endsection
