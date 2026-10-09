
@extends('masjid.mrj.admin.financial-v2.layout')
@section('title', 'Detail Penyaluran ZISWAF')
@push('styles')
<style>
@media (min-width: 1024px) {
    .beneficiary-filter-grid { grid-template-columns: minmax(13rem, 1.45fr) minmax(7rem, .7fr) minmax(10rem, 1fr) 4.5rem 4.5rem minmax(10rem, 1fr) auto; }
    .recipient-picker-grid { grid-template-columns: 2rem 3rem minmax(11rem, 1.5fr) minmax(8rem, .8fr) 3rem 3rem minmax(9rem, 1fr) 5rem 4rem; }
    .draft-recipient-grid { grid-template-columns: 3rem minmax(12rem, 1.35fr) minmax(8rem, .8fr) minmax(8rem, .7fr) minmax(10rem, 1fr) auto; }
}
</style>
@endpush
@php
$editable = $distribution->status === 'draft' && $distribution->realization_id === null;
$transaction = $distribution->realization?->transaction;
$money = static fn ($amount) => \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($amount, true);
$groupKey = static fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8');
$compareGroupValue = static function ($left, $right): int {
    $left = trim((string) $left);
    $right = trim((string) $right);
    if ($left === '' || $right === '') return $left === $right ? 0 : ($left === '' ? 1 : -1);
    return strnatcasecmp($left, $right);
};
$sortRecipients = static fn ($rows, $identityPrefix = '') => $rows->sort(static function ($left, $right) use ($compareGroupValue, $identityPrefix): int {
    foreach (['rw', 'rt', 'display_name'] as $field) {
        $comparison = $compareGroupValue(data_get($left, $identityPrefix.$field), data_get($right, $identityPrefix.$field));
        if ($comparison !== 0) return $comparison;
    }
    return strcmp((string) $left->id, (string) $right->id);
});
$sortedDistributionItems = $sortRecipients($distribution->items, 'identity_snapshot.');
$distributionItemsByRw = $sortedDistributionItems->groupBy(fn ($item) => $groupKey($item->identity_snapshot['rw'] ?? null));
$recipientAmounts = $distribution->items->pluck('amount')->map(fn ($amount) => \App\Domain\FinancialV2\DecimalAmount::normalize($amount))->unique()->values();
$uniformRecipientAmount = $recipientAmounts->count() === 1 ? $recipientAmounts->first() : null;
$linkedFunds = $transaction?->splits?->pluck('fund.name')->filter()->unique()->values()->implode(', ');
@endphp
@section('content')
<a class="link text-sm" href="{{ route('financial-v2.distributions.index', ['entity' => $entity->id, 'program_id' => $distribution->program_id]) }}">← Periode penyaluran program</a>
<section class="rounded-2xl bg-emerald-950 text-white p-5 my-5"><h1 class="text-2xl font-bold">{{ $distribution->title }}</h1><p class="mt-2">{{ $distribution->program->name }} · {{ $distribution->period_label }}</p><p class="text-sm mt-1">{{ \App\Support\FinancialDate::date($distribution->starts_on) }} — {{ \App\Support\FinancialDate::date($distribution->ends_on) }}</p><p class="mt-4">Operasional: {{ strtoupper($distribution->status) }} · Finansial: {{ strtoupper($transaction?->status ?? 'unposted') }}</p></section>
<div class="grid gap-4 sm:grid-cols-3 mb-5"><x-financial-v2.metric title="Jumlah penerima" :value="$distribution->items->count()" /><x-financial-v2.metric title="Total penyaluran operasional" :value="$money($total)" /><x-financial-v2.metric title="Nominal realisasi tertaut" :value="$transaction ? $money($transaction->gross_amount) : 'Belum ditautkan'" /></div>
<section class="rounded-2xl border border-base-300 bg-base-100 p-5 mb-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-xl font-bold">Realisasi Keuangan</h2><p class="mt-1 text-sm text-base-content/65">Referensi ke fakta finansial existing; Penyaluran tidak membuat posting.</p></div><span @class(['badge', 'badge-success' => $transaction, 'badge-warning' => !$transaction])>{{ $transaction ? 'Sudah ditautkan' : 'Belum ditautkan' }}</span></div>
@if($transaction)
<dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-5"><div><dt class="text-base-content/55">Tanggal</dt><dd class="font-semibold">{{ $transaction->business_date->format('d/m/Y') }}</dd></div><div><dt class="text-base-content/55">Fund</dt><dd class="font-semibold">{{ $linkedFunds ?: '—' }}</dd></div><div><dt class="text-base-content/55">Program</dt><dd class="font-semibold">{{ $distribution->program->name }}</dd></div><div><dt class="text-base-content/55">Nominal</dt><dd class="font-semibold">{{ $money($transaction->gross_amount) }}</dd></div><div><dt class="text-base-content/55">Penerima</dt><dd class="font-semibold">{{ $distribution->items->count() }}</dd></div></dl><div class="mt-4"><a class="btn btn-outline btn-sm" href="{{ route('financial-v2.transactions.show', $transaction->id) }}">Lihat Realisasi</a></div>
@if(!\App\Domain\FinancialV2\DecimalAmount::equals($total, $transaction->gross_amount))<div role="alert" class="alert alert-error mt-4">Total detail penerima tidak sama dengan nominal Realisasi existing.</div>@endif
@else<p class="mt-4 text-sm">Gunakan action “Tautkan ke Realisasi” di bawah untuk memilih Realisasi yang sudah ada.</p>@endif
</section>
<div class="grid gap-3 sm:grid-cols-3 mb-5"><x-financial-v2.metric title="Jumlah penerima" :value="$distribution->items->count()" /><x-financial-v2.metric title="Nominal per penerima" :value="$uniformRecipientAmount ? $money($uniformRecipientAmount) : 'Bervariasi'" /><x-financial-v2.metric title="Total rincian penerima" :value="$money($total)" /></div>
<p class="text-sm mb-5 break-words">{{ $distribution->notes }}</p>
@if($distribution->copied_from_id)<section class="rounded-2xl bg-base-100 p-5 mb-5"><h2 class="font-semibold">Perbandingan dengan periode sumber salinan</h2><p class="text-sm mt-2">Sebelumnya {{ $continuity['previous'] }} · Sekarang {{ $continuity['current'] }} · Masuk {{ $continuity['added'] }} · Keluar {{ $continuity['removed'] }}</p></section>
@endif
@if($editable)
<section class="mt-6 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5" data-distribution-selection
    data-storage-key="financial-v2-distribution-selection:{{ $entity->id }}:{{ $distribution->id }}"
    data-clear-selection="{{ session('distribution_batch_added') ? 'true' : 'false' }}">
<form method="post" action="{{ route('financial-v2.distributions.items.store', $distribution->id) }}" data-batch-form>
@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}">
<div class="sticky top-2 z-10 flex flex-wrap items-start justify-between gap-2 bg-base-100 py-2"><div><h2 class="text-xl font-bold">Penerima Terpilih (<span data-staging-count>0</span>)</h2><p class="mt-1 text-sm text-base-content/65">Pilihan tetap tersimpan saat pencarian, filter, atau browser Back digunakan.</p><span class="sr-only" aria-live="polite" data-selected-summary>0 penerima dipilih</span></div><strong class="text-lg text-emerald-800" data-staging-total>Total: Rp0</strong></div>
<div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4" data-staging-list></div><p class="mt-3 rounded-lg bg-base-200 p-3 text-sm text-base-content/65" data-staging-empty>Belum ada penerima dipilih.</p>
<div class="mt-4 grid gap-3 rounded-lg bg-base-200/60 p-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-end"><label class="form-control text-sm" data-money-field><span class="label-text font-semibold">Nominal sama untuk semua</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input class="input input-bordered w-full pl-9" data-money-input data-apply-all-display inputmode="decimal" autocomplete="off" placeholder="0"><input type="hidden" data-money-value data-apply-all-value></div></label><button type="button" class="btn btn-outline" data-apply-all>Terapkan ke semua</button></div>
<button class="btn btn-primary mt-5" data-batch-submit disabled>Tambah 0 penerima ke draft</button>
</form>
<template data-staging-template><article class="flex min-w-0 flex-col gap-2 rounded-lg border border-base-300 p-3" data-staging-item><div class="min-w-0"><strong class="block break-words text-sm" data-staging-name></strong><span class="mt-0.5 block text-xs text-base-content/65" data-staging-region></span></div><label class="form-control text-sm" data-money-field><span class="label-text text-xs">Nominal</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input class="input input-bordered input-sm w-full pl-9" data-money-input data-staging-amount-display inputmode="decimal" autocomplete="off" placeholder="0" required><input type="hidden" data-money-value data-staging-amount></div></label><button type="button" class="btn btn-ghost btn-xs self-start text-error" data-remove-selection>Hapus</button><input type="hidden" data-staging-beneficiary></article></template>

<div class="mt-8 border-t border-base-300 pt-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-xl font-bold">Tambah Penerima ke Draft</h2><p class="mt-1 text-sm text-base-content/65">Cari dan pilih penerima aktif yang belum ada di draft.</p></div><span class="badge badge-outline h-auto py-1">{{ $people->count() }} hasil</span></div></div>
@include('masjid.mrj.admin.financial-v2.distributions.filters', [
    'recipientSelection' => true,
    'filterAction' => route('financial-v2.distributions.show', ['distribution' => $distribution->id]),
    'filterResetUrl' => route('financial-v2.distributions.show', ['distribution' => $distribution->id, 'entity' => $entity->id]),
])
<fieldset><legend class="sr-only">Pilih penerima</legend>
<label class="mb-3 inline-flex cursor-pointer items-center gap-3 rounded-lg border border-base-300 px-3 py-2 text-sm font-semibold"><input type="checkbox" class="checkbox checkbox-sm" data-select-all-page aria-label="Pilih semua penerima yang tersedia"><span>Pilih semua yang tersedia</span></label>
@php
$personNumber = 0;
$peopleByRw = $sortRecipients($people)->groupBy(fn ($person) => $groupKey($person->rw));
@endphp
<div class="space-y-5">
@forelse($peopleByRw as $rwKey => $rwPeople)
<x-financial-v2.recipient-rw-group :rw="$rwPeople->first()->rw" data-recipient-rw-group>
@foreach($rwPeople->groupBy(fn ($person) => $groupKey($person->rt)) as $rtKey => $rtPeople)
<x-financial-v2.recipient-rt-group :rw="$rwPeople->first()->rw" :rt="$rtPeople->first()->rt" :total="$rtPeople->count()" data-recipient-rt-group>
<div class="recipient-picker-grid hidden gap-2 border-b border-base-200 bg-base-200/40 px-3 py-2 text-xs font-semibold text-base-content/65 lg:grid"><span></span><span>No</span><span>Nama</span><span>Jenis</span><span>RT</span><span>RW</span><span>Koordinator</span><span>Status</span><span>Aksi</span></div><div class="divide-y divide-base-200">
@foreach($rtPeople as $person)
@php
$personNumber++;
$alreadyAdded = $distribution->items->contains('beneficiary_id', $person->id);
$eligible = $person->status === 'active' && ! $alreadyAdded;
$statusLabel = ['active' => 'Aktif', 'inactive' => 'Tidak aktif', 'archived' => 'Arsip'][$person->status] ?? ucfirst($person->status);
$categoryLabel = \Illuminate\Support\Str::of($person->beneficiary_type ?: 'BELUM_DITENTUKAN')->replace('_', ' ')->lower()->title();
@endphp
<div @class(['recipient-picker-grid grid min-w-0 grid-cols-[auto_2rem_minmax(0,1fr)] items-start gap-x-2 gap-y-1 px-3 py-2.5 text-sm lg:items-center', 'hover:bg-base-200/40' => $eligible, 'bg-base-200/40 text-base-content/55' => ! $eligible]) data-recipient-row>
<input id="beneficiary-{{ $person->id }}" type="checkbox" class="checkbox checkbox-sm mt-0.5 lg:mt-0" value="{{ $person->id }}" data-beneficiary-select
    data-name="{{ $person->display_name }}" data-rt="{{ $person->rt }}" data-rw="{{ $person->rw }}"
    data-coordinator="{{ $person->rt_coordinator_name }}" @checked($alreadyAdded) @disabled(! $eligible)>
<span class="text-xs text-base-content/60 lg:text-sm">{{ $personNumber }}</span>
<label for="beneficiary-{{ $person->id }}" @class(['min-w-0 break-words font-semibold', 'cursor-pointer' => $eligible])>{{ $person->display_name }}
@if($alreadyAdded)<span class="block text-xs font-semibold">Sudah ditambahkan</span>
@endif</label>
<span class="hidden break-words lg:block">{{ $categoryLabel }}</span><span class="hidden lg:block">{{ $person->rt ?: '—' }}</span><span class="hidden lg:block">{{ $person->rw ?: '—' }}</span><span class="hidden break-words lg:block">{{ $person->rt_coordinator_name ?: '—' }}</span><span class="hidden lg:block">{{ $statusLabel }}</span><a class="link hidden text-xs lg:block" href="{{ route('financial-v2.beneficiaries.show', ['beneficiary' => $person->id, 'entity' => $entity->id]) }}">Lihat</a>
<span class="col-start-3 break-words text-xs text-base-content/65 lg:hidden">{{ $categoryLabel }} · RT {{ $person->rt ?: '—' }} / RW {{ $person->rw ?: '—' }} · {{ $person->rt_coordinator_name ?: '—' }} · {{ $statusLabel }} @if(!$eligible && !$alreadyAdded) · Tidak dapat dipilih @endif · <a class="link" href="{{ route('financial-v2.beneficiaries.show', ['beneficiary' => $person->id, 'entity' => $entity->id]) }}">Lihat</a></span>
</div>
@endforeach
</div></x-financial-v2.recipient-rt-group>
@endforeach
</x-financial-v2.recipient-rw-group>
@empty<p class="py-4">Tidak ada penerima sesuai filter.</p>
@endforelse
</div></fieldset>
</section>
@endif

<section class="mt-6 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5" data-draft-items><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="text-xl font-bold">Kelola Penerima Draft</h2><p class="mt-1 text-sm text-base-content/65">Penerima dikelompokkan berdasarkan RW dan RT dari snapshot saat ditambahkan.</p></div><div class="flex flex-wrap gap-2"><span class="badge badge-outline h-auto py-1">{{ $distribution->items->count() }} penerima</span><span class="badge badge-primary badge-outline h-auto py-1">Total {{ $money($total) }}</span></div></div>
@if(!$editable)<p class="alert mt-4 text-sm">Penyaluran final terkunci. Edit dan Hapus tidak tersedia; perubahan master tidak mengubah snapshot ini.</p>@endif
@php $itemNumber = 0; @endphp
<div class="mt-4 space-y-5">
@forelse($distributionItemsByRw as $rwKey => $rwItems)
<x-financial-v2.recipient-rw-group :rw="$rwItems->first()->identity_snapshot['rw'] ?? null" data-draft-rw-group>
@foreach($rwItems->groupBy(fn ($item) => $groupKey($item->identity_snapshot['rt'] ?? null)) as $rtKey => $rtItems)
<x-financial-v2.recipient-rt-group :rw="$rwItems->first()->identity_snapshot['rw'] ?? null" :rt="$rtItems->first()->identity_snapshot['rt'] ?? null" :total="$rtItems->count()" data-draft-rt-group>
<div class="draft-recipient-grid hidden gap-2 border-b border-base-200 bg-base-200/40 px-3 py-2 text-xs font-semibold text-base-content/65 lg:grid"><span>No</span><span>Nama penerima</span><span>Telepon</span><span>Nominal</span><span>Catatan</span><span>Aksi</span></div><div class="divide-y divide-base-200">
@foreach($rtItems as $item)
@php $itemNumber++; @endphp
<div class="draft-recipient-grid grid min-w-0 grid-cols-[2rem_minmax(0,1fr)] items-start gap-x-2 gap-y-1 px-3 py-2.5 text-sm lg:items-center" data-draft-item-row><span class="text-xs text-base-content/60 lg:text-sm">{{ $itemNumber }}</span><span class="min-w-0 break-words"><strong class="block">{{ $item->identity_snapshot['display_name'] }}</strong><span class="mt-0.5 block text-xs text-base-content/60">{{ $item->beneficiary?->beneficiary_type_label ?? 'Belum ditentukan' }} · RT {{ $item->identity_snapshot['rt'] ?? 'Belum ditentukan' }} / RW {{ $item->identity_snapshot['rw'] ?? 'Belum ditentukan' }} · {{ $item->identity_snapshot['rt_coordinator_name'] ?? 'Tanpa koordinator' }} · {{ ucfirst($item->beneficiary?->status ?? 'tidak diketahui') }}</span></span><span class="col-start-2 text-xs text-base-content/65 lg:col-auto lg:text-sm">{{ $item->identity_snapshot['contact_reference'] ?? '—' }}</span><span class="col-start-2 font-semibold lg:col-auto">{{ $money($item->amount) }}</span><span class="col-start-2 min-w-0 break-words text-xs text-base-content/65 lg:col-auto lg:text-sm">{{ $item->notes ?: '—' }}</span>@include('masjid.mrj.admin.financial-v2.distributions.item-edit')</div>
@endforeach
</div></x-financial-v2.recipient-rt-group>
@endforeach
</x-financial-v2.recipient-rw-group>
@empty<p class="rounded-lg bg-base-200 p-3 text-sm">Draft belum memiliki penerima.</p>
@endforelse
</div></section>

@if($editable)
<section class="bg-base-100 rounded-2xl p-5 mt-6"><h2 class="font-bold text-xl">Tautkan ke Realisasi</h2><p class="text-sm my-3">Gunakan Realisasi yang sudah ada. Proses ini hanya menyimpan reference dan mengunci rincian Penyaluran; tidak membuat atau mem-posting financial fact.</p>
<form method="post" action="{{ route('financial-v2.distributions.realizations.link', $distribution->id) }}">
@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}">
<fieldset class="space-y-3"><legend class="sr-only">Realisasi yang tersedia</legend>
@forelse($realizations as $realization)
<label @class(['flex gap-3 items-start rounded-xl border p-3 text-sm', 'border-emerald-300 bg-emerald-50' => $realization->link_compatible, 'border-error/30 bg-error/5' => !$realization->link_compatible])><input type="radio" class="radio radio-sm mt-1" name="realization_id" value="{{ $realization->id }}" @disabled(!$realization->link_compatible) required><span class="min-w-0 break-words"><strong>{{ $realization->transaction->description }}</strong><br>Tanggal: {{ $realization->transaction->business_date->format('d/m/Y') }} · Fund: {{ $realization->link_funds ?: '—' }} · Program Realisasi: {{ $realization->link_program?->name ?? '—' }}<br>Nominal: {{ $money($realization->transaction->gross_amount) }} · Penerima operasional: {{ $distribution->items->count() }} · Status: {{ strtoupper($realization->transaction->status) }}<br>
@if($realization->link_compatible)<strong class="text-success">Seluruh dimensi dan total cocok</strong>@else<strong class="text-error">{{ implode(' ', $realization->link_validation_errors) }}</strong>@endif
@if($realization->link_validation_warnings !== [])<span class="mt-1 block font-semibold text-warning">{{ implode(' ', $realization->link_validation_warnings) }}</span>@endif</span></label>
@empty<p>Belum ditemukan Realisasi yang cocok untuk Penyaluran ini.</p>
@endforelse</fieldset>
<button class="btn btn-primary mt-4" 
@disabled(!$realizations->contains(fn ($candidate) => $candidate->link_compatible) || $distribution->items->isEmpty())>Gunakan Realisasi Ini</button>
</form></section>
@endif
@endsection
@push('scripts')
<script>
(() => {
    const root = document.querySelector('[data-distribution-selection]');
    if (!root) return;

    const storageKey = root.dataset.storageKey;
    if (root.dataset.clearSelection === 'true') sessionStorage.removeItem(storageKey);

    const selected = new Map();
    const loadSelection = () => {
        selected.clear();
        try {
            const stored = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
            if (Array.isArray(stored)) stored.forEach(item => {
                if (item && typeof item.id === 'string') selected.set(item.id, item);
            });
        } catch (_) {
            sessionStorage.removeItem(storageKey);
        }
    };
    loadSelection();

    const list = root.querySelector('[data-staging-list]');
    const template = root.querySelector('[data-staging-template]');
    const selectAll = root.querySelector('[data-select-all-page]');
    const summary = root.querySelector('[data-selected-summary]');
    const count = root.querySelector('[data-staging-count]');
    const total = root.querySelector('[data-staging-total]');
    const empty = root.querySelector('[data-staging-empty]');
    const submit = root.querySelector('[data-batch-submit]');
    const applyAllValue = root.querySelector('[data-apply-all-value]');

    const pageCheckboxes = () => Array.from(root.querySelectorAll('[data-beneficiary-select]:not(:disabled)'));
    const persist = () => sessionStorage.setItem(storageKey, JSON.stringify(Array.from(selected.values())));
    const toCents = value => {
        const match = String(value || '').match(/^(\d+)(?:\.(\d{1,2}))?$/);
        if (!match) return 0n;
        return (BigInt(match[1]) * 100n) + BigInt((match[2] || '').padEnd(2, '0') || '0');
    };
    const formatCents = value => {
        const whole = (value / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        const fraction = (value % 100n).toString().padStart(2, '0');
        return `Rp${whole}${fraction === '00' ? '' : `,${fraction}`}`;
    };
    const updateSelectAll = () => {
        const eligible = pageCheckboxes();
        const selectedCount = eligible.filter(checkbox => checkbox.checked).length;
        selectAll.disabled = eligible.length === 0;
        selectAll.checked = eligible.length > 0 && selectedCount === eligible.length;
        selectAll.indeterminate = selectedCount > 0 && selectedCount < eligible.length;
    };
    const syncPageCheckboxes = () => {
        root.querySelectorAll('[data-beneficiary-select]').forEach(checkbox => {
            if (checkbox.disabled) selected.delete(checkbox.value);
            else checkbox.checked = selected.has(checkbox.value);
        });
        updateSelectAll();
    };
    const render = () => {
        list.replaceChildren();
        Array.from(selected.values()).forEach((person, index) => {
            const row = template.content.firstElementChild.cloneNode(true);
            row.dataset.beneficiaryId = person.id;
            row.querySelector('[data-staging-name]').textContent = person.name;
            row.querySelector('[data-staging-region]').textContent = `RT ${person.rt || '—'} / RW ${person.rw || '—'}`;
            const beneficiary = row.querySelector('[data-staging-beneficiary]');
            beneficiary.name = `items[${index}][beneficiary_id]`;
            beneficiary.value = person.id;
            const amount = row.querySelector('[data-staging-amount]');
            amount.name = `items[${index}][amount]`;
            amount.value = person.amount || '';
            list.appendChild(row);
        });
        window.FinancialV2Money?.bind(list);
        const size = selected.size;
        const cents = Array.from(selected.values()).reduce((sum, person) => sum + toCents(person.amount), 0n);
        summary.textContent = `${size} penerima dipilih`;
        count.textContent = String(size);
        total.textContent = `Total: ${formatCents(cents)}`;
        empty.classList.toggle('hidden', size > 0);
        submit.disabled = size === 0;
        submit.textContent = `Tambah ${size} penerima ke draft`;
        syncPageCheckboxes();
    };
    const selectPerson = checkbox => {
        if (checkbox.checked) {
            selected.set(checkbox.value, {
                id: checkbox.value,
                name: checkbox.dataset.name,
                rt: checkbox.dataset.rt,
                rw: checkbox.dataset.rw,
                coordinator: checkbox.dataset.coordinator,
                amount: selected.get(checkbox.value)?.amount || '',
            });
        } else selected.delete(checkbox.value);
    };

    root.addEventListener('change', event => {
        if (!event.target.matches('[data-beneficiary-select]')) return;
        selectPerson(event.target);
        persist();
        render();
    });
    selectAll.addEventListener('change', () => {
        pageCheckboxes().forEach(checkbox => {
            checkbox.checked = selectAll.checked;
            selectPerson(checkbox);
        });
        persist();
        render();
    });
    list.addEventListener('input', event => {
        const row = event.target.closest('[data-staging-item]');
        if (!row) return;
        const person = selected.get(row.dataset.beneficiaryId);
        if (!person) return;
        if (event.target.matches('[data-staging-amount]')) person.amount = event.target.value;
        persist();
        const cents = Array.from(selected.values()).reduce((sum, item) => sum + toCents(item.amount), 0n);
        total.textContent = `Total: ${formatCents(cents)}`;
    });
    list.addEventListener('click', event => {
        const remove = event.target.closest('[data-remove-selection]');
        if (!remove) return;
        selected.delete(remove.closest('[data-staging-item]').dataset.beneficiaryId);
        persist();
        render();
    });
    root.querySelector('[data-apply-all]').addEventListener('click', () => {
        if (!String(applyAllValue.value || '').trim()) return;
        selected.forEach(person => { person.amount = applyAllValue.value; });
        persist();
        render();
    });
    window.addEventListener('pageshow', () => {
        loadSelection();
        render();
    });

    syncPageCheckboxes();
    persist();
    render();
})();
</script>
@endpush
