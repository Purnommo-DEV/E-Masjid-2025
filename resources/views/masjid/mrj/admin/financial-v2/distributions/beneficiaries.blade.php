@extends('masjid.mrj.admin.financial-v2.layout')
@section('title', 'Master Penerima ZISWAF')
@section('content')
@php
    $canDelete = auth()->user()?->can('delete penerima ziswaf') ?? false;
    $columnCount = $canDelete ? 10 : 9;
@endphp

<div class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold">Master Penerima ZISWAF</h1>
        <p class="mt-1 text-sm opacity-70">Identitas internal · {{ $entity->name }} · {{ number_format($people->total(), 0, ',', '.') }} penerima sesuai filter</p>
    </div>
    <form method="get" class="flex items-end gap-2" data-page-size-form>
        @foreach(request()->except(['page', 'per_page']) as $key => $value)
            @if(is_scalar($value))
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endif
        @endforeach
        <label class="form-control text-sm">
            <span class="label-text">Tampilkan</span>
            <select class="select select-bordered select-sm" name="per_page" data-page-size>
                @foreach(['10' => '10', '20' => '20', '100' => '100', 'all' => 'Semua'] as $value => $label)
                    <option value="{{ $value }}" @selected($perPage === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <noscript><button class="btn btn-outline btn-sm">Terapkan</button></noscript>
    </form>
</div>

@if(session('warning'))
    <div role="alert" class="alert alert-warning mb-5 text-sm"><span>{{ session('warning') }}</span></div>
@endif

@include('masjid.mrj.admin.financial-v2.distributions.filters')

<form id="beneficiary-bulk-delete" method="post" action="{{ route('financial-v2.beneficiaries.destroy-bulk') }}">
    @csrf
    @method('DELETE')
    <input type="hidden" name="entity" value="{{ $entity->id }}">

    @if($canDelete && $people->isNotEmpty())
        <div class="mb-3 flex flex-col gap-3 rounded-2xl border border-base-300 bg-base-100 p-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-4">
                <label class="flex cursor-pointer items-center gap-2 font-medium">
                    <input id="beneficiary-select-all" type="checkbox" class="checkbox checkbox-sm checkbox-primary">
                    <span>Pilih semua di halaman ini</span>
                </label>
                <span id="beneficiary-selected-count" class="text-sm text-base-content/65" aria-live="polite">0 penerima dipilih</span>
            </div>
            <button id="beneficiary-delete-selected" type="button" class="btn btn-error btn-sm" disabled>Hapus Terpilih</button>
        </div>
    @endif

    <div id="beneficiary-table-scroll" class="max-w-full overflow-x-auto rounded-2xl border border-base-300 bg-base-100 shadow-sm">
        <table class="table min-w-[70rem]">
            <thead class="bg-base-200/80 text-xs uppercase tracking-wide text-base-content/65">
                <tr>
                    @if($canDelete)<th class="w-12 text-center"><span class="sr-only">Pilih</span></th>@endif
                    <th class="w-16 text-right">No</th>
                    <th>Nama</th>
                    <th>Kategori</th>
                    <th class="text-center">RT</th>
                    <th class="text-center">RW</th>
                    <th>Koordinator RT</th>
                    <th>Telepon</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $lastRwKey = null;
                    $lastRtKey = null;
                @endphp
                @forelse($people as $person)
                    @php
                        $rwKey = mb_strtolower(trim((string) $person->rw));
                        $rtKey = mb_strtolower(trim((string) $person->rt));
                        $rwLabel = $rwKey === '' ? 'Belum Ditentukan' : trim((string) $person->rw);
                        $rtLabel = $rtKey === '' ? 'Belum Ditentukan' : trim((string) $person->rt);
                        $newRw = $lastRwKey !== $rwKey;
                        $newRt = $newRw || $lastRtKey !== $rtKey;
                    @endphp
                    @if($newRw)
                        <tr class="border-t-4 border-emerald-800 bg-emerald-950 text-emerald-50" data-rw-group="{{ $rwKey }}">
                            <th colspan="{{ $columnCount }}" class="py-4 text-base font-bold">RW: {{ $rwLabel }}</th>
                        </tr>
                    @endif
                    @if($newRt)
                        <tr class="bg-emerald-50 text-emerald-950" data-rt-group="{{ $rwKey }}|{{ $rtKey }}">
                            <th colspan="{{ $columnCount }}" class="py-3 font-semibold">
                                RT: {{ $rtLabel }} <span class="mx-1 text-emerald-700/45">·</span>
                                RW: {{ $rwLabel }} <span class="mx-1 text-emerald-700/45">·</span>
                                Total Penerima: {{ number_format((int) $person->matching_group_total, 0, ',', '.') }}
                            </th>
                        </tr>
                    @endif
                    <tr class="transition-colors hover:bg-base-200/60" data-beneficiary-row>
                        @if($canDelete)
                            <td class="text-center"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" name="beneficiary_ids[]" value="{{ $person->id }}" data-beneficiary-select aria-label="Pilih {{ $person->display_name }}"></td>
                        @endif
                        <td class="text-right tabular-nums text-base-content/55">{{ number_format(($people->firstItem() ?? 1) + $loop->index, 0, ',', '.') }}</td>
                        <td class="min-w-52"><a class="font-semibold text-emerald-800 hover:underline" href="{{ route('financial-v2.beneficiaries.show', ['beneficiary' => $person->id, 'entity' => $entity->id]) }}">{{ $person->display_name }}</a></td>
                        <td><span class="badge badge-outline whitespace-nowrap">{{ $person->beneficiary_type_label }}</span></td>
                        <td class="text-center font-medium">{{ trim((string) $person->rt) !== '' ? $person->rt : '—' }}</td>
                        <td class="text-center font-medium">{{ trim((string) $person->rw) !== '' ? $person->rw : '—' }}</td>
                        <td class="min-w-44">{{ $person->rt_coordinator_name ?: '—' }}</td>
                        <td class="min-w-36">{{ $person->contact_reference ?: '—' }}</td>
                        <td><span @class(['badge whitespace-nowrap', 'badge-success' => $person->status === 'active', 'badge-warning' => $person->status === 'inactive', 'badge-ghost' => $person->status === 'archived'])>{{ ['active' => 'Aktif', 'inactive' => 'Tidak aktif', 'archived' => 'Arsip'][$person->status] ?? $person->status }}</span></td>
                        <td class="text-right"><a class="btn btn-ghost btn-xs whitespace-nowrap" href="{{ route('financial-v2.beneficiaries.show', ['beneficiary' => $person->id, 'entity' => $entity->id]) }}">Detail / riwayat</a></td>
                    </tr>
                    @php
                        $lastRwKey = $rwKey;
                        $lastRtKey = $rtKey;
                    @endphp
                @empty
                    <tr><td colspan="{{ $columnCount }}" class="py-12 text-center text-base-content/55">Tidak ada penerima yang sesuai.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($canDelete)
        <dialog id="beneficiary-delete-dialog" class="modal">
            <div class="modal-box max-w-lg">
                <h2 class="text-xl font-bold">Hapus <span data-confirm-count>0</span> penerima?</h2>
                <p class="mt-3 text-sm leading-relaxed text-base-content/70">Tidak ada data keuangan yang akan dihapus. Penerima yang sudah memiliki riwayat penyaluran atau referensi Financial V2 tidak akan dihapus secara permanen.</p>
                <div class="modal-action">
                    <button type="button" class="btn btn-ghost" data-close-delete-dialog>Batal</button>
                    <button type="submit" class="btn btn-error">Ya, Hapus Terpilih</button>
                </div>
            </div>
        </dialog>
    @endif
</form>

@if($people->hasPages())
    <div class="mt-5">{{ $people->links() }}</div>
@endif

@can('create penerima ziswaf')
    <details class="mt-6 rounded-2xl bg-base-100 p-5" @if($errors->any()) open @endif>
        <summary class="cursor-pointer font-semibold">Tambah penerima</summary>
        <form method="post" action="{{ route('financial-v2.beneficiaries.store') }}" class="mt-4">
            @include('masjid.mrj.admin.financial-v2.distributions.person-form', ['person' => null])
        </form>
    </details>
@endcan
@endsection

@push('scripts')
<script>
(() => {
    const pageSize = document.querySelector('[data-page-size]');
    pageSize?.addEventListener('change', () => pageSize.form.submit());

    const form = document.getElementById('beneficiary-bulk-delete');
    if (!form) return;
    const rows = Array.from(form.querySelectorAll('[data-beneficiary-select]'));
    const selectAll = document.getElementById('beneficiary-select-all');
    const selectedCount = document.getElementById('beneficiary-selected-count');
    const deleteButton = document.getElementById('beneficiary-delete-selected');
    const dialog = document.getElementById('beneficiary-delete-dialog');
    const confirmCount = dialog?.querySelector('[data-confirm-count]');

    const selected = () => rows.filter((row) => row.checked);
    const sync = () => {
        const count = selected().length;
        if (selectedCount) selectedCount.textContent = `${count.toLocaleString('id-ID')} penerima dipilih`;
        if (deleteButton) deleteButton.disabled = count === 0;
        if (selectAll) {
            selectAll.checked = rows.length > 0 && count === rows.length;
            selectAll.indeterminate = count > 0 && count < rows.length;
        }
    };

    selectAll?.addEventListener('change', () => {
        rows.forEach((row) => { row.checked = selectAll.checked; });
        sync();
    });
    rows.forEach((row) => row.addEventListener('change', sync));
    deleteButton?.addEventListener('click', () => {
        const count = selected().length;
        if (count === 0) return;
        if (confirmCount) confirmCount.textContent = count.toLocaleString('id-ID');
        dialog?.showModal();
    });
    dialog?.querySelector('[data-close-delete-dialog]')?.addEventListener('click', () => dialog.close());
    dialog?.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close();
    });
    form.addEventListener('submit', (event) => {
        if (selected().length === 0) event.preventDefault();
    });
    sync();
})();
</script>
@endpush
