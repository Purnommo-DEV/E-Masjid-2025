@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Draft Realisasi')

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $statusLabel = [
            'draft' => 'Draft', 'submitted' => 'Diajukan', 'verified' => 'Diverifikasi', 'approved' => 'Siap dicatat resmi',
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.allocations.create', ['entity' => $entity?->id]) }}">← Kembali ke Alokasi Dana</a>
            <h1 class="mt-2 text-2xl font-bold">Draft Realisasi</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">Realisasi yang sedang disiapkan. Draft tidak masuk Dashboard, laporan, saldo Dana, maupun riwayat transaksi resmi sampai dicatat melalui Posting Engine.</p>
        </div>
        @if ($entity)<a class="btn btn-primary btn-sm" href="{{ route('financial-v2.transactions.create', ['operation' => 'realization', 'entity' => $entity->id]) }}">+ Draft Realisasi</a>@endif
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih entitas keuangan aktif untuk melihat Draft Realisasi.</span></div>
    @else
        <form method="GET" class="mb-5 grid gap-3 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:grid-cols-[1fr_1fr_auto]">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <label class="form-control"><span class="label-text text-xs">Dana</span><select name="fund_id" class="select select-bordered select-sm"><option value="">Semua Dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected(($filters['fund_id'] ?? null) === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Program</span><select name="program_id" class="select select-bordered select-sm"><option value="">Semua Program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected(($filters['program_id'] ?? null) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
            <div class="flex items-end"><button class="btn btn-primary btn-sm w-full">Terapkan</button></div>
        </form>

        <section class="overflow-hidden rounded-2xl bg-base-100 shadow-sm ring-1 ring-base-300">
            <div class="border-b border-base-300 px-4 py-4 sm:px-6"><h2 class="font-bold">Realisasi yang belum dicatat resmi</h2><p class="mt-1 text-xs text-base-content/60">Satu alokasi hanya dapat memiliki satu Draft Realisasi aktif pada satu waktu.</p></div>
            <div class="divide-y divide-base-300">
                @forelse ($drafts as $transaction)
                    @php
                        $realization = $transaction->realization;
                        $version = $realization?->budgetAllocationVersion;
                        $allocation = $version?->allocation;
                        $availability = $transaction->allocation_availability ?? ['allocated' => 0, 'recorded' => 0, 'remaining' => 0];
                    @endphp
                    <article class="p-4 sm:p-6">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2"><p class="font-semibold">{{ $allocation?->fund?->name ?? $transaction->splits->first()?->fund?->name ?? 'Dana' }}{{ $allocation?->program ? ' · '.$allocation->program->name : '' }}</p><span class="badge badge-outline">{{ $statusLabel[$transaction->status] ?? ucfirst($transaction->status) }}</span></div>
                                <p class="mt-1 text-sm text-base-content/65">{{ $allocation?->reason ?? $transaction->description ?? 'Realisasi Dana' }}</p>
                                <p class="mt-2 text-xs text-base-content/55">{{ $transaction->source_reference }} · {{ $transaction->accounting_date->translatedFormat('d M Y') }} · diubah {{ $transaction->updated_at->translatedFormat('d M Y H:i') }}</p>
                            </div>
                            <div class="shrink-0 text-left lg:text-right"><p class="text-lg font-bold">{{ $rupiah($transaction->gross_amount) }}</p><p class="mt-1 text-xs text-base-content/60">Alokasi {{ $rupiah($availability['allocated']) }} · Sisa {{ $rupiah($availability['remaining']) }}</p><p class="mt-1 text-xs {{ $transaction->evidence_count > 0 ? 'text-emerald-700' : 'text-amber-700' }}">{{ $transaction->evidence_count }} bukti terlampir</p></div>
                        </div>
                        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-3"><div><dt class="text-xs text-base-content/55">Dibayarkan kepada</dt><dd class="mt-1 font-medium">{{ $transaction->counterparty?->display_name ?? '—' }}</dd></div><div><dt class="text-xs text-base-content/55">Dibayar dari</dt><dd class="mt-1 font-medium">{{ $transaction->primaryFinancialAccount?->name ?? '—' }}</dd></div><div><dt class="text-xs text-base-content/55">Kategori</dt><dd class="mt-1 font-medium">{{ $transaction->category?->name ?? '—' }}</dd></div></dl>
                        <div class="mt-4 flex flex-wrap gap-2"><a class="btn btn-primary btn-sm" href="{{ route('financial-v2.transactions.show', $transaction) }}">Buka Draft</a>@if($transaction->status === 'draft')<a class="btn btn-outline btn-sm" href="{{ route('financial-v2.transactions.edit', $transaction) }}">Edit</a>@endif
                            <details class="dropdown"><summary class="btn btn-ghost btn-sm text-error">Batalkan</summary><form method="POST" action="{{ route('financial-v2.transactions.cancel', $transaction) }}" data-financial-ajax class="dropdown-content z-20 mt-2 w-72 rounded-xl border border-base-300 bg-base-100 p-3 shadow-xl">@csrf<label class="form-control"><span class="label-text text-xs">Alasan pembatalan</span><input name="reason" class="input input-bordered input-sm mt-1" placeholder="Wajib diisi" required></label><button class="btn btn-error btn-sm mt-3 w-full">Batalkan Draft</button></form></details>
                        </div>
                    </article>
                @empty
                    <p class="p-6 text-sm text-base-content/65">Tidak ada Draft Realisasi aktif. Realisasi yang sudah dicatat resmi tampil pada Riwayat Transaksi.</p>
                @endforelse
            </div>
        </section>
        <div class="mt-5">{{ $drafts->links() }}</div>
    @endif
@endsection
