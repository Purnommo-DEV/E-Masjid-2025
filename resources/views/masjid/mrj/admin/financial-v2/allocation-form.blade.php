@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Alokasikan Dana')

@section('content')
    <div class="mb-6">
        <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.dashboard', ['entity' => $entity?->id]) }}">← Kembali ke ringkasan</a>
        <h1 class="mt-2 text-2xl font-bold">Alokasikan Dana</h1>
        <p class="mt-1 max-w-2xl text-sm text-base-content/65">Alokasi menentukan dana akan digunakan untuk tujuan tertentu. Ini belum merupakan pengeluaran dan belum mengurangi saldo rekening.</p>
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih satu entitas keuangan aktif terlebih dahulu. Struktur alokasi sudah siap, tetapi tidak akan membuat data aktual secara otomatis.</span></div>
    @else
        <form method="POST" action="{{ route('financial-v2.allocations.store') }}" data-financial-ajax class="grid max-w-3xl gap-5 lg:grid-cols-[minmax(0,1fr)_17rem]">
            @csrf
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
            <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text font-medium">Tanggal berlaku</span><input type="date" name="date" value="{{ old('date', $today) }}" class="input input-bordered w-full" required></label>
                    @php
                        $amountValue = old('amount');
                    @endphp
                    <label class="form-control"><span class="label-text font-medium">Nominal alokasi</span><div class="relative" data-money-field><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="amount" value="{{ $amountValue }}" data-money-value><input type="text" inputmode="decimal" autocomplete="off" value="{{ $amountValue !== null && $amountValue !== '' ? number_format((float) $amountValue, 2, ',', '.') : '' }}" placeholder="0" class="input input-bordered w-full pl-9 text-lg font-semibold" data-money-input required></div><span class="label-text-alt">Pemisah ribuan dibuat otomatis. Gunakan koma untuk sen.</span></label>
                    <label class="form-control"><span class="label-text font-medium">Dana</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected(old('fund_id') === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                    <label class="form-control"><span class="label-text font-medium">Program <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="program_id" class="select select-bordered w-full"><option value="">Tanpa program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected(old('program_id') === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                </div>
                <label class="form-control mt-4"><span class="label-text font-medium">Kategori <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="category_id" class="select select-bordered w-full"><option value="">Tanpa kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected(old('category_id') === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label class="form-control mt-4"><span class="label-text font-medium">Tujuan dan keterangan</span><textarea name="reason" rows="4" class="textarea textarea-bordered w-full" placeholder="Contoh: Peruntukan biaya program Ramadhan 1448 H" required>{{ old('reason') }}</textarea></label>
                <div class="mt-6 flex justify-end"><button type="submit" class="btn btn-primary">Simpan alokasi sebagai draft</button></div>
            </section>
            <aside class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-bold">Tentang bukti alokasi</p><p class="mt-2 text-xs leading-5">Bukti langsung untuk alokasi belum tersedia. Simpan bukti pengeluaran pada transaksi realisasi saat uang benar-benar dibayarkan. Alokasi tetap disimpan sebagai rencana yang melalui proses pengajuan dan persetujuan.</p></aside>
        </form>

        <section class="mt-8 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
            <div class="mb-4 flex flex-wrap items-start justify-between gap-3"><div><h2 class="font-bold">Status Alokasi Dana</h2><p class="mt-1 text-sm text-base-content/65">Ajukan lalu setujui alokasi sebelum dipilih pada Realisasi Dana. Alokasi menetapkan rencana; uang baru keluar saat realisasi dicatat resmi.</p></div><a class="btn btn-outline btn-sm" href="{{ route('financial-v2.allocations.history', ['entity' => $entity->id]) }}">Lihat riwayat</a></div>
            <div class="space-y-3">
                @forelse (($allocationHistory?->items() ?? []) as $summary)
                        @php
                            $allocation = $summary['allocation'];
                            $version = $summary['version'];
                            $activeRealizationDrafts = $summary['active_realization_drafts'] ?? collect();
                            $activeRealizationDraft = $activeRealizationDrafts->first();
                            $statusLabel = match ($allocation->status) {
                            'draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui',
                            'cancelled' => 'Dibatalkan', 'superseded' => 'Digantikan', default => ucfirst($allocation->status),
                        };
                    @endphp
                    <article class="rounded-xl border border-base-300 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-semibold">{{ $allocation->fund?->name ?? 'Dana' }}{{ $allocation->program ? ' · '.$allocation->program->name : '' }}</p><p class="mt-1 text-sm text-base-content/65">{{ $allocation->reason }}</p><p class="mt-2 text-xs text-base-content/55">{{ $allocation->allocation_reference }} · Berlaku {{ $version?->effective_from?->toDateString() ?? '—' }}</p></div><div class="sm:text-right"><p class="text-lg font-bold">Rp{{ number_format((float) ($version?->allocated_amount ?? 0), 2, ',', '.') }}</p><span class="badge badge-outline">{{ $statusLabel }}</span></div></div>
                        <p class="mt-3 rounded-lg bg-base-200 px-3 py-2 text-xs text-base-content/70">Total Rp{{ number_format((float) $summary['allocated'], 2, ',', '.') }} · Sudah direalisasikan Rp{{ number_format((float) $summary['realized'], 2, ',', '.') }} · Sisa Rp{{ number_format((float) $summary['remaining'], 2, ',', '.') }}</p>
                        @if ($allocation->status === 'cancelled')
                            <aside class="mt-3 rounded-lg border border-error/25 bg-error/10 px-3 py-3 text-sm text-error-content"><p class="font-semibold">Alokasi dibatalkan</p><p class="mt-1">{{ $allocation->cancellation_reason }}</p><p class="mt-1 text-xs opacity-75">Dibatalkan oleh {{ $allocation->cancelledBy?->name ?? 'Sistem' }} · {{ $allocation->cancelled_at?->translatedFormat('d M Y H:i') ?? '—' }}</p></aside>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($allocation->status === 'draft')
                                <form method="POST" action="{{ route('financial-v2.allocations.submit', $allocation) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-sm" type="submit">Ajukan alokasi</button></form>
                            @elseif ($allocation->status === 'submitted' && $version?->status === 'draft')
                                <form method="POST" action="{{ route('financial-v2.allocations.approve', $allocation) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-primary btn-sm" type="submit">Setujui alokasi</button></form>
                            @elseif ($allocation->status === 'approved')
                                @if ($activeRealizationDraft)
                                    <a class="btn btn-success btn-sm" href="{{ route('financial-v2.transactions.show', $activeRealizationDraft) }}">Lanjutkan Realisasi</a>
                                    <p class="self-center text-xs text-base-content/60">Draft Realisasi sudah disiapkan{{ $activeRealizationDrafts->count() > 1 ? ' (perlu ditinjau)' : '' }}.</p>
                                @else
                                    <a class="btn btn-success btn-sm" href="{{ route('financial-v2.transactions.create', ['operation' => 'realization', 'entity' => $entity->id, 'allocation_version_id' => $version?->id]) }}">Mulai Realisasi</a>
                                @endif
                            @endif
                            @if (in_array($allocation->status, ['draft', 'submitted', 'approved'], true))
                                <button class="btn btn-ghost btn-sm text-error" type="button" onclick="document.getElementById('cancel-allocation-{{ $allocation->id }}').showModal()">Batalkan</button>
                            @endif
                        </div>
                        @if (in_array($allocation->status, ['draft', 'submitted', 'approved'], true))
                            <dialog id="cancel-allocation-{{ $allocation->id }}" class="modal">
                                <div class="modal-box"><form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-2 top-2" aria-label="Tutup">✕</button></form><h3 class="text-lg font-bold">Batalkan alokasi ini?</h3><p class="mt-2 text-sm text-base-content/65">Alokasi tetap tersimpan untuk audit. Pembatalan tidak membuat Journal, Ledger, atau perubahan saldo Dana.</p><form method="POST" action="{{ route('financial-v2.allocations.cancel', $allocation) }}" data-financial-ajax class="mt-4 space-y-3">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><label class="form-control"><span class="label-text font-medium">Alasan pembatalan</span><textarea class="textarea textarea-bordered w-full" name="reason" rows="3" placeholder="Contoh: Data belum final / perlu diperbaiki." required></textarea></label><div class="flex justify-end gap-2"><button class="btn btn-ghost" type="button" onclick="document.getElementById('cancel-allocation-{{ $allocation->id }}').close()">Kembali</button><button class="btn btn-error" type="submit">Batalkan alokasi</button></div></form></div>
                            </dialog>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/65">Belum ada alokasi dana. Simpan rencana pertama untuk memulai.</p>
                @endforelse
            </div>
        </section>
    @endif
@endsection
