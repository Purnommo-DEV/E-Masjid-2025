@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', ($editingAllocation ? 'Ubah Draft ' : '').'Alokasikan Dana')

@section('content')
    <div class="mb-6">
        <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.dashboard', ['entity' => $entity?->id]) }}">← Kembali ke ringkasan</a>
        <h1 class="mt-2 text-2xl font-bold">{{ $editingAllocation ? 'Ubah Draft Alokasi Dana' : 'Alokasikan Dana' }}</h1>
        <p class="mt-1 max-w-2xl text-sm text-base-content/65">Alokasi menentukan dana akan digunakan untuk tujuan tertentu. Ini belum merupakan pengeluaran dan belum mengurangi saldo rekening.</p>
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih satu entitas keuangan aktif terlebih dahulu. Struktur alokasi sudah siap, tetapi tidak akan membuat data aktual secara otomatis.</span></div>
    @else
        @php
            $editingVersion = $editingAllocation?->versions?->sortByDesc('version_no')->first();
            $fundingSources = old('funding_sources');
            if ($fundingSources === null) {
                $fundingSources = $editingVersion
                    ? $editingVersion->fundings->map(fn ($funding) => ['fund_id' => $funding->fund_id, 'amount' => $funding->amount, 'note' => $funding->note, 'source_reference' => $funding->source_reference])->values()->all()
                    : [['fund_id' => '', 'amount' => '', 'note' => '', 'source_reference' => '']];
            }
            $allocationAmount = old('amount', $editingVersion?->allocated_amount);
        @endphp
        <form method="POST" action="{{ $editingAllocation ? route('financial-v2.allocations.update', $editingAllocation) : route('financial-v2.allocations.store') }}" data-financial-ajax data-funding-form class="grid max-w-3xl gap-5 lg:grid-cols-[minmax(0,1fr)_17rem]">
            @csrf
            @if ($editingAllocation) @method('PUT') @endif
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <input type="hidden" name="submission_key" value="{{ $submissionKey }}">
            <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text font-medium">Tanggal berlaku</span><input type="date" name="date" value="{{ old('date', $editingVersion?->effective_from?->toDateString() ?? $today) }}" class="input input-bordered w-full" required></label>
                    <label class="form-control"><span class="label-text font-medium">Nominal alokasi</span><div class="relative" data-money-field><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="amount" value="{{ $allocationAmount }}" data-money-value><input type="text" inputmode="decimal" autocomplete="off" value="{{ $allocationAmount !== null && $allocationAmount !== '' ? number_format((float) $allocationAmount, 2, ',', '.') : '' }}" placeholder="0" class="input input-bordered w-full pl-9 text-lg font-semibold" data-money-input required></div><span class="label-text-alt">Pemisah ribuan dibuat otomatis. Gunakan koma untuk sen.</span></label>
                    <label class="form-control"><span class="label-text font-medium">Program <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="program_id" class="select select-bordered w-full"><option value="">Tanpa program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected(old('program_id', $editingAllocation?->program_id) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                </div>
                <section class="mt-5 rounded-xl border border-base-300 bg-base-200/40 p-3 sm:p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-semibold">Sumber Dana</h2><p class="mt-1 text-xs text-base-content/60">Satu alokasi dapat menggunakan satu atau beberapa Dana.</p></div><button type="button" class="btn btn-outline btn-sm" data-add-funding>+ Tambah Sumber Dana</button></div>
                    <div class="mt-3 space-y-3" data-funding-lines>
                        @foreach($fundingSources as $index => $source)
                            <div class="rounded-xl border border-base-300 bg-base-100 p-3" data-funding-line>
                                <div class="mb-2 flex items-center justify-between"><span class="text-sm font-semibold" data-funding-label>Sumber Dana #{{ $loop->iteration }}</span><button type="button" class="btn btn-ghost btn-xs text-error" data-remove-funding>Hapus</button></div>
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <label class="form-control"><span class="label-text text-xs">Dana</span><select name="funding_sources[{{ $index }}][fund_id]" class="select select-bordered w-full" data-funding-fund required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected(($source['fund_id'] ?? '') === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                                    <label class="form-control"><span class="label-text text-xs">Nominal sumber</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="funding_sources[{{ $index }}][amount]" value="{{ $source['amount'] ?? '' }}" data-funding-value><input type="text" inputmode="decimal" autocomplete="off" value="{{ filled($source['amount'] ?? null) ? number_format((float) $source['amount'], 2, ',', '.') : '' }}" class="input input-bordered w-full pl-9" placeholder="0" data-funding-input required></div></label>
                                    <label class="form-control sm:col-span-2"><span class="label-text text-xs">Catatan / referensi <span class="font-normal text-base-content/50">(opsional)</span></span><input type="text" name="funding_sources[{{ $index }}][note]" value="{{ $source['note'] ?? '' }}" class="input input-bordered w-full" maxlength="1000" placeholder="Contoh: Bagian Fidyah untuk distribusi sembako"></label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3 rounded-xl bg-base-100 px-3 py-3 text-sm"><div class="flex justify-between gap-3"><span>Total Sumber</span><strong data-funding-total>Rp0,00</strong></div><p class="mt-1 text-xs" data-funding-status>Total sumber belum seimbang dengan nominal alokasi.</p></div>
                </section>
                <label class="form-control mt-4"><span class="label-text font-medium">Kategori <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="category_id" class="select select-bordered w-full"><option value="">Tanpa kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected(old('category_id', $editingAllocation?->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                <label class="form-control mt-4"><span class="label-text font-medium">Tujuan dan keterangan</span><textarea name="reason" rows="4" class="textarea textarea-bordered w-full" placeholder="Contoh: Peruntukan biaya program Ramadhan 1448 H" required>{{ old('reason', $editingAllocation?->reason) }}</textarea></label>
                <div class="mt-6 flex justify-end"><button type="submit" class="btn btn-primary" data-funding-submit>{{ $editingAllocation ? 'Simpan perubahan draft' : 'Simpan alokasi sebagai draft' }}</button></div>
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
                            $pendingVersion = $summary['pending_version'] ?? null;
                            $activeRealizationDrafts = $summary['active_realization_drafts'] ?? collect();
                            $activeRealizationDraft = $activeRealizationDrafts->first();
                            $amendmentDate = $version?->effective_from?->copy()->addDay()->max(now()->startOfDay())->toDateString() ?? $today;
                            $statusLabel = match ($allocation->status) {
                            'draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui',
                            'cancelled' => 'Dibatalkan', 'superseded' => 'Digantikan', default => ucfirst($allocation->status),
                        };
                    @endphp
                    <article class="rounded-xl border border-base-300 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-semibold">{{ ($version?->fundings?->pluck('fund.name')->filter()->join(' + ')) ?: ($allocation->fund?->name ?? 'Dana') }}{{ $allocation->program ? ' · '.$allocation->program->name : '' }}</p><p class="mt-1 text-sm text-base-content/65">{{ $allocation->reason }}</p><p class="mt-2 text-xs text-base-content/55">{{ $allocation->allocation_reference }} · Berlaku {{ $version?->effective_from?->toDateString() ?? '—' }}</p></div><div class="sm:text-right"><p class="text-lg font-bold">Rp{{ number_format((float) ($version?->allocated_amount ?? 0), 2, ',', '.') }}</p><span class="badge badge-outline">{{ $statusLabel }}</span></div></div>
                        @if ($version?->fundings?->isNotEmpty())<div class="mt-3 flex flex-wrap gap-2">@foreach($version->fundings as $funding)<span class="badge badge-ghost h-auto py-1">{{ $funding->fund?->name ?? 'Dana' }} · Rp{{ number_format((float) $funding->amount, 2, ',', '.') }}</span>@endforeach</div>@endif
                        <p class="mt-3 rounded-lg bg-base-200 px-3 py-2 text-xs text-base-content/70">Total Rp{{ number_format((float) $summary['allocated'], 2, ',', '.') }} · Sudah direalisasikan Rp{{ number_format((float) $summary['realized'], 2, ',', '.') }} · Sisa Rp{{ number_format((float) $summary['remaining'], 2, ',', '.') }}</p>
                        @if ($pendingVersion)
                            <aside class="mt-3 rounded-lg border border-info/25 bg-info/10 px-3 py-3 text-sm"><p class="font-semibold">Perubahan alokasi menunggu persetujuan</p><p class="mt-1">Versi {{ $pendingVersion->version_no }} menetapkan total Rp{{ number_format((float) $pendingVersion->allocated_amount, 2, ',', '.') }} mulai {{ $pendingVersion->effective_from?->translatedFormat('d M Y') }}.</p><p class="mt-1 text-xs opacity-70">Sebelum disetujui, batas realisasi tetap mengikuti versi aktif Rp{{ number_format((float) ($version?->allocated_amount ?? 0), 2, ',', '.') }}.</p></aside>
                        @endif
                        @if ($allocation->status === 'cancelled')
                            <aside class="mt-3 rounded-lg border border-error/25 bg-error/10 px-3 py-3 text-sm text-error-content"><p class="font-semibold">Alokasi dibatalkan</p><p class="mt-1">{{ $allocation->cancellation_reason }}</p><p class="mt-1 text-xs opacity-75">Dibatalkan oleh {{ $allocation->cancelledBy?->name ?? 'Sistem' }} · {{ $allocation->cancelled_at?->translatedFormat('d M Y H:i') ?? '—' }}</p></aside>
                        @endif
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if ($allocation->status === 'draft')
                                <a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.allocations.edit', ['allocation' => $allocation, 'entity' => $entity->id]) }}">Ubah draft</a>
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
                                @if ($pendingVersion)
                                    <form method="POST" action="{{ route('financial-v2.allocations.amendments.approve', ['allocation' => $allocation, 'version' => $pendingVersion]) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-sm" type="submit">Setujui perubahan alokasi</button></form>
                                @else
                                    <button class="btn btn-outline btn-sm" type="button" onclick="document.getElementById('amend-allocation-{{ $allocation->id }}').showModal()">Tambah perubahan alokasi</button>
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
                        @if ($allocation->status === 'approved' && ! $pendingVersion && $version)
                            <dialog id="amend-allocation-{{ $allocation->id }}" class="modal">
                                <div class="modal-box max-w-2xl"><form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-2 top-2" aria-label="Tutup">✕</button></form><h3 class="text-lg font-bold">Tambah perubahan alokasi</h3><p class="mt-2 text-sm text-base-content/65">Perubahan membuat versi baru. Nilai aktif saat ini tetap Rp{{ number_format((float) $version->allocated_amount, 2, ',', '.') }} sampai versi baru disetujui.</p>
                                    <form method="POST" action="{{ route('financial-v2.allocations.amendments.store', $allocation) }}" data-financial-ajax class="mt-4 space-y-4">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}">
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <label class="form-control"><span class="label-text font-medium">Tanggal berlaku</span><input type="date" name="effective_from" value="{{ $amendmentDate }}" min="{{ $amendmentDate }}" class="input input-bordered" required></label>
                                            <label class="form-control"><span class="label-text font-medium">Nilai tambahan</span><div class="relative" data-money-field><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="amendment_amount" data-money-value><input type="text" inputmode="decimal" autocomplete="off" class="input input-bordered w-full pl-9" placeholder="0" data-money-input required></div></label>
                                        </div>
                                        <section class="rounded-xl bg-base-200/60 p-3"><p class="font-semibold">Tambahan menurut Sumber Dana</p><p class="mt-1 text-xs text-base-content/60">Isi hanya Dana yang menambah pembiayaan. Totalnya harus sama dengan nilai tambahan.</p><div class="mt-3 space-y-3">
                                            @foreach($version->fundings as $funding)
                                                <div class="grid gap-2 rounded-lg bg-base-100 p-3 sm:grid-cols-[minmax(0,1fr)_13rem]"><div><p class="text-sm font-medium">{{ $funding->fund?->name ?? 'Dana' }}</p><p class="text-xs text-base-content/55">Saat ini Rp{{ number_format((float) $funding->amount, 2, ',', '.') }}</p></div><div data-money-field><input type="hidden" name="funding_adjustments[{{ $loop->index }}][fund_id]" value="{{ $funding->fund_id }}"><input type="hidden" name="funding_adjustments[{{ $loop->index }}][amount]" data-money-value><input type="text" inputmode="decimal" autocomplete="off" class="input input-bordered input-sm w-full" placeholder="Tambahan Rp" data-money-input></div></div>
                                            @endforeach
                                        </div></section>
                                        <label class="form-control"><span class="label-text font-medium">Alasan perubahan</span><textarea name="reason" rows="3" class="textarea textarea-bordered" placeholder="Contoh: Tambahan biaya aktual distribusi sembako Rp85.300." required></textarea></label>
                                        <div class="flex justify-end gap-2"><button class="btn btn-ghost" type="button" onclick="document.getElementById('amend-allocation-{{ $allocation->id }}').close()">Kembali</button><button class="btn btn-primary" type="submit">Simpan perubahan</button></div>
                                    </form>
                                </div>
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

@push('scripts')
<template data-funding-template>
    <div class="rounded-xl border border-base-300 bg-base-100 p-3" data-funding-line>
        <div class="mb-2 flex items-center justify-between"><span class="text-sm font-semibold" data-funding-label>Sumber Dana</span><button type="button" class="btn btn-ghost btn-xs text-error" data-remove-funding>Hapus</button></div>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="form-control"><span class="label-text text-xs">Dana</span><select name="funding_sources[__INDEX__][fund_id]" class="select select-bordered w-full" data-funding-fund required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Nominal sumber</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="funding_sources[__INDEX__][amount]" data-funding-value><input type="text" inputmode="decimal" autocomplete="off" class="input input-bordered w-full pl-9" placeholder="0" data-funding-input required></div></label>
            <label class="form-control sm:col-span-2"><span class="label-text text-xs">Catatan / referensi <span class="font-normal text-base-content/50">(opsional)</span></span><input type="text" name="funding_sources[__INDEX__][note]" class="input input-bordered w-full" maxlength="1000"></label>
        </div>
    </div>
</template>
<script>
(() => {
    const form = document.querySelector('[data-funding-form]');
    if (!form) return;
    const lines = form.querySelector('[data-funding-lines]');
    const template = document.querySelector('[data-funding-template]');
    const totalOutput = form.querySelector('[data-funding-total]');
    const statusOutput = form.querySelector('[data-funding-status]');
    const submit = form.querySelector('[data-funding-submit]');
    const target = form.querySelector('input[name="amount"]');
    let nextIndex = lines.children.length;
    const parse = (raw) => {
        const cleaned = String(raw || '').replace(/[^\d,]/g, '');
        const comma = cleaned.lastIndexOf(',');
        const whole = (comma >= 0 ? cleaned.slice(0, comma) : cleaned).replace(/\D/g, '') || '0';
        const fraction = comma >= 0 ? cleaned.slice(comma + 1).replace(/\D/g, '').slice(0, 2) : '';
        return { canonical: fraction ? `${BigInt(whole)}.${fraction}` : `${BigInt(whole)}`, cents: (BigInt(whole) * 100n) + BigInt((fraction + '00').slice(0, 2)), fraction };
    };
    const canonicalCents = (raw) => { const match = String(raw || '').match(/^(\d+)(?:\.(\d{1,2}))?$/); return match ? (BigInt(match[1]) * 100n) + BigInt(((match[2] || '') + '00').slice(0, 2)) : 0n; };
    const display = (cents) => `Rp${(cents / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')},${(cents % 100n).toString().padStart(2, '0')}`;
    const bind = (line) => {
        const input = line.querySelector('[data-funding-input]');
        const hidden = line.querySelector('[data-funding-value]');
        input.addEventListener('input', () => { const value = parse(input.value); hidden.value = value.canonical; input.value = `${(value.cents / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}${value.fraction ? ',' + value.fraction : ''}`; render(); });
        line.querySelector('[data-remove-funding]').addEventListener('click', () => { if (lines.children.length > 1) line.remove(); renumber(); render(); });
    };
    const renumber = () => [...lines.querySelectorAll('[data-funding-line]')].forEach((line, index) => line.querySelector('[data-funding-label]').textContent = `Sumber Dana #${index + 1}`);
    const render = () => {
        const total = [...lines.querySelectorAll('[data-funding-value]')].reduce((sum, field) => sum + canonicalCents(field.value), 0n);
        const expected = canonicalCents(target.value);
        const balanced = expected > 0n && total === expected;
        totalOutput.textContent = display(total);
        statusOutput.textContent = balanced ? '✓ Seimbang dengan Nominal Alokasi' : 'Total sumber ≠ Nominal alokasi';
        statusOutput.className = `mt-1 text-xs ${balanced ? 'text-success' : 'text-error'}`;
        submit.disabled = !balanced;
    };
    [...lines.querySelectorAll('[data-funding-line]')].forEach(bind);
    form.querySelector('[data-add-funding]').addEventListener('click', () => { lines.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++)); bind(lines.lastElementChild); renumber(); render(); });
    target.addEventListener('input', render);
    renumber(); render();
})();
</script>
@endpush
