@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Riwayat Alokasi Dana')

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $statuses = ['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'cancelled' => 'Dibatalkan', 'superseded' => 'Digantikan'];
    @endphp
    <section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><a class="link text-sm text-base-content/60" href="{{ route('financial-v2.dashboard', ['entity' => $entity?->id]) }}">← Kembali ke ringkasan</a><h1 class="mt-2 text-2xl font-bold">Riwayat Alokasi Dana</h1><p class="mt-1 max-w-3xl text-sm text-base-content/65">Alokasi adalah rencana penggunaan. Angka realisasi hanya berasal dari pembayaran yang sudah tercatat resmi.</p></div>
        @if($entity)<a class="btn btn-primary btn-sm" href="{{ route('financial-v2.allocations.create', ['entity' => $entity->id]) }}">Buat alokasi</a>@endif
    </section>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih satu entitas keuangan aktif terlebih dahulu.</span></div>
    @else
        @if ($sourceAllocationAudit)
            <aside class="mb-6 rounded-2xl border border-sky-200 bg-sky-50 p-4 text-sky-950">
                <p class="font-semibold">Audit sumber ZISWAF: tidak ada alokasi historis untuk diimpor</p>
                <p class="mt-1 text-sm">{{ $sourceAllocationAudit['reason'] }} Karena itu, tidak ada alokasi buatan pada halaman ini dan tidak ada Journal/Ledger baru yang dibuat.</p>
                <p class="mt-2 text-xs opacity-75">{{ $sourceAllocationAudit['source_filename'] }} · {{ count($sourceAllocationAudit['worksheets']) }} worksheet ditinjau · SHA-256 {{ $sourceAllocationAudit['source_sha256'] }}</p>
            </aside>
        @endif

        <form class="grid gap-3 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:grid-cols-2 lg:grid-cols-5" method="GET">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <label class="form-control"><span class="label-text text-xs">Dari tanggal</span><input class="input input-bordered input-sm w-full" type="date" name="from" value="{{ $filters['from'] ?? '' }}"></label>
            <label class="form-control"><span class="label-text text-xs">Sampai tanggal</span><input class="input input-bordered input-sm w-full" type="date" name="through" value="{{ $filters['through'] ?? '' }}"></label>
            <label class="form-control"><span class="label-text text-xs">Dana</span><select name="fund_id" class="select select-bordered select-sm w-full"><option value="">Semua Dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected(($filters['fund_id'] ?? '') === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Program</span><select name="program_id" class="select select-bordered select-sm w-full"><option value="">Semua program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
            <div class="flex items-end gap-2"><label class="form-control flex-1"><span class="label-text text-xs">Status</span><select name="status" class="select select-bordered select-sm w-full"><option value="">Semua status</option>@foreach($statuses as $code => $label)<option value="{{ $code }}" @selected(($filters['status'] ?? '') === $code)>{{ $label }}</option>@endforeach</select></label><button class="btn btn-primary btn-sm" type="submit">Terapkan</button></div>
        </form>

        <section class="mt-6 space-y-3">
            @forelse ($allocationHistory as $item)
                @php $allocation = $item['allocation']; $version = $item['version']; @endphp
                <article class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-bold">{{ $allocation->fund?->name ?? 'Dana' }}{{ $allocation->program ? ' · '.$allocation->program->name : '' }}</p><p class="mt-1 text-sm text-base-content/70">{{ $allocation->reason }}</p><p class="mt-2 text-xs text-base-content/55">{{ $allocation->allocation_reference }} · Berlaku {{ $version?->effective_from?->translatedFormat('d M Y') ?? '—' }} · {{ $statuses[$allocation->status] ?? ucfirst($allocation->status) }}</p></div><div class="text-sm sm:text-right"><p>Total <strong>{{ $rupiah($item['allocated']) }}</strong></p><p>Realisasi {{ $rupiah($item['realized']) }}</p><p>Sisa <strong>{{ $rupiah($item['remaining']) }}</strong></p></div></div>
                    @if ($allocation->status === 'cancelled')<aside class="mt-4 rounded-lg border border-error/25 bg-error/10 px-3 py-3 text-sm text-error-content"><p class="font-semibold">Alokasi dibatalkan</p><p class="mt-1">{{ $allocation->cancellation_reason }}</p><p class="mt-1 text-xs opacity-75">Dibatalkan oleh {{ $allocation->cancelledBy?->name ?? 'Sistem' }} · {{ $allocation->cancelled_at?->translatedFormat('d M Y H:i') ?? '—' }}</p></aside>@endif
                    @if ($item['realizations']->isNotEmpty())<div class="mt-4 border-t border-base-200 pt-3"><p class="text-xs font-semibold text-base-content/60">Pembayaran realisasi tercatat</p><ul class="mt-2 space-y-1 text-sm text-base-content/70">@foreach($item['realizations'] as $realization)<li>{{ $realization->transaction?->accounting_date?->translatedFormat('d M Y') }} · {{ $realization->transaction?->source_reference }} · {{ $rupiah($realization->transaction?->gross_amount) }}{{ $realization->transaction?->primaryFinancialAccount ? ' · '.$realization->transaction->primaryFinancialAccount->name : '' }}</li>@endforeach</ul></div>@endif
                </article>
            @empty
                <p class="rounded-2xl bg-base-100 p-5 text-sm text-base-content/60 shadow-sm ring-1 ring-base-300">Belum ada alokasi dana. Riwayat Dana dan riwayat realisasi tetap terpisah; penerimaan/pengeluaran sumber tidak dikonversi menjadi alokasi.</p>
            @endforelse
        </section>
        <div class="mt-5">{{ $allocationHistory->links() }}</div>
    @endif
@endsection
