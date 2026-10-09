@extends('masjid.mrj.admin.financial-v2.layout')
@section('title', 'Master Penerima ZISWAF')
@push('styles')
<style>
@media (min-width: 1024px) {
    .beneficiary-filter-grid { grid-template-columns: minmax(13rem, 1.45fr) minmax(7rem, .7fr) minmax(10rem, 1fr) 4.5rem 4.5rem minmax(10rem, 1fr) auto; }
}
</style>
@endpush
@section('content')
@php $columnCount = 10; @endphp

<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div class="min-w-0">
        <h1 class="text-2xl font-bold">Master Penerima ZISWAF</h1>
        <p class="mt-1 max-w-2xl text-sm text-base-content/65">Kelola identitas penerima dalam {{ $entity->name }}. Data master ini tidak membuat transaksi atau pencatatan keuangan.</p>
        <p class="mt-2 text-sm font-semibold text-emerald-800">{{ number_format($people->total(), 0, ',', '.') }} penerima sesuai filter</p>
    </div>
    <div class="flex flex-wrap gap-2 sm:justify-end">
        <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.beneficiaries.import.template', ['entity' => $entity->id]) }}">Download Template</a>
        <a class="btn btn-success btn-sm" href="{{ route('financial-v2.beneficiaries.export', array_merge(['entity' => $entity->id], request()->only(['q', 'status', 'beneficiary_type', 'rt', 'rw', 'coordinator']))) }}">Export Excel</a>
    </div>
</header>

<section class="mb-5 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div><h2 class="font-bold">Import Excel</h2><p class="mt-1 text-sm text-base-content/65">Pilih XLS/XLSX lalu periksa hasil preview sebelum menyimpan data baru.</p></div>
        <form method="post" action="{{ route('financial-v2.beneficiaries.import.preview') }}" enctype="multipart/form-data" class="grid gap-3 sm:grid-cols-[minmax(16rem,1fr)_auto] sm:items-end lg:w-[36rem]">
            @csrf
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <label class="form-control min-w-0 text-sm"><span class="label-text font-medium">File XLS/XLSX</span><input required type="file" name="import_file" accept=".xls,.xlsx" class="file-input file-input-bordered file-input-sm w-full"></label>
            <button class="btn btn-primary btn-sm w-full sm:w-auto">Preview Import</button>
        </form>
    </div>
</section>

@if(session('warning'))
    <div role="alert" class="alert alert-warning mb-5 text-sm"><span>{{ session('warning') }}</span></div>
@endif

<section class="mb-5 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5">
    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><h2 class="font-bold">Filter penerima</h2><p class="mt-1 text-sm text-base-content/65">Pencarian nama berjalan otomatis; filter lain diterapkan melalui tombol Cari / filter.</p></div>
        <form method="get" action="{{ route('financial-v2.beneficiaries.index') }}" class="flex items-end gap-2" data-page-size-form>
            @foreach(['entity', 'q', 'status', 'beneficiary_type', 'rt', 'rw', 'coordinator'] as $key)
                @if(request()->filled($key))<input type="hidden" name="{{ $key }}" value="{{ request($key) }}">@endif
            @endforeach
            <label class="form-control text-sm"><span class="label-text font-medium">Tampilkan</span><select class="select select-bordered select-sm" name="per_page" data-page-size>@foreach(['10' => '10', '20' => '20', '100' => '100', 'all' => 'Semua'] as $value => $label)<option value="{{ $value }}" @selected($perPage === (string) $value)>{{ $label }}</option>@endforeach</select></label>
            <noscript><button class="btn btn-outline btn-sm">Terapkan</button></noscript>
        </form>
    </div>
    @include('masjid.mrj.admin.financial-v2.distributions.filters')
</section>

@isset($importPreview)
<section class="mb-6 rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div><h2 class="text-lg font-bold">Preview Import Penerima</h2><p class="mt-1 text-sm text-base-content/65">Belum ada data yang disimpan. Baris contoh dan duplikat otomatis dilewati.</p></div>
        <div class="flex flex-wrap gap-2 text-xs">
            <span class="badge badge-success">Valid baru: {{ $importPreview['summary']['valid_new'] }}</span>
            <span class="badge badge-warning">Duplicate existing: {{ $importPreview['summary']['duplicate_existing'] }}</span>
            <span class="badge badge-warning">Duplicate dalam file: {{ $importPreview['summary']['duplicate_in_file'] }}</span>
            <span class="badge badge-error">Tidak valid: {{ $importPreview['summary']['invalid'] }}</span>
            <span class="badge badge-ghost">Contoh diabaikan: {{ $importPreview['summary']['examples_ignored'] }}</span>
        </div>
    </div>
    <div class="mt-4 max-h-[32rem] overflow-auto rounded-xl border border-base-300">
        <table class="table table-sm min-w-[65rem]"><thead><tr><th>Baris</th><th>Nama</th><th>Telepon</th><th>RT/RW</th><th>Jenis</th><th>Status data</th><th>Keterangan</th></tr></thead><tbody>
        @forelse($importPreview['rows'] as $row)
            <tr><td>{{ $row['source_row'] }}</td><td>{{ $row['data']['display_name'] ?: '—' }}</td><td>{{ $row['data']['contact_reference'] ?: '—' }}</td><td>{{ $row['data']['rt'] ?: '—' }}/{{ $row['data']['rw'] ?: '—' }}</td><td>{{ $row['data']['beneficiary_type'] }}</td><td><span @class(['badge badge-sm', 'badge-success' => $row['status'] === 'valid_new', 'badge-warning' => str_starts_with($row['status'], 'duplicate'), 'badge-error' => $row['status'] === 'invalid'])>{{ str_replace('_', ' ', $row['status']) }}</span></td><td class="max-w-80 whitespace-normal">{{ implode(' ', $row['messages']) ?: 'Siap diimpor' }}</td></tr>
        @empty<tr><td colspan="7" class="text-center">Tidak ada baris data selain contoh.</td></tr>@endforelse
        </tbody></table>
    </div>
    <form method="post" action="{{ route('financial-v2.beneficiaries.import.store') }}" class="mt-4 flex justify-end">
        @csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="import_token" value="{{ $importToken }}">
        <button class="btn btn-success" @disabled($importPreview['summary']['valid_new'] === 0)>Simpan {{ $importPreview['summary']['valid_new'] }} penerima baru</button>
    </form>
</section>
@endisset

<form id="beneficiary-bulk-delete" method="post" action="{{ route('financial-v2.beneficiaries.destroy-bulk') }}">
    @csrf
    @method('DELETE')
    <input type="hidden" name="entity" value="{{ $entity->id }}">

    @if($people->isNotEmpty())
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
                    <th class="w-12 text-center"><span class="sr-only">Pilih</span></th>
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
                        <td class="text-center"><input type="checkbox" class="checkbox checkbox-sm checkbox-primary" name="beneficiary_ids[]" value="{{ $person->id }}" data-beneficiary-select aria-label="Pilih {{ $person->display_name }}"></td>
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
</form>

@if($people->hasPages())
    <div class="mt-5">{{ $people->links() }}</div>
@endif

<details class="mt-6 rounded-2xl bg-base-100 p-5" @if($errors->any()) open @endif>
    <summary class="cursor-pointer font-semibold">Tambah penerima</summary>
    <form method="post" action="{{ route('financial-v2.beneficiaries.store') }}" class="mt-4">
        @include('masjid.mrj.admin.financial-v2.distributions.person-form', ['person' => null])
    </form>
</details>
@endsection

@push('scripts')
<script>
(() => {
    const filterForm = document.querySelector('[data-beneficiary-filter-form]');
    const searchInput = filterForm?.querySelector('[data-beneficiary-live-search]');
    if (filterForm && searchInput && !searchInput.dataset.liveSearchBound) {
        searchInput.dataset.liveSearchBound = 'true';
        let searchTimer;
        searchInput.addEventListener('input', () => {
            window.clearTimeout(searchTimer);
            searchTimer = window.setTimeout(() => {
                const page = filterForm.querySelector('input[name="page"]');
                page?.remove();
                filterForm.requestSubmit();
            }, 450);
        });
    }

    document.querySelectorAll('[data-beneficiary-name-check]').forEach((input) => {
        if (input.dataset.nameCheckBound) return;
        input.dataset.nameCheckBound = 'true';
        const output = input.parentElement.querySelector('[data-beneficiary-name-results]');
        let timer;
        let requestSequence = 0;
        input.addEventListener('input', () => {
            window.clearTimeout(timer);
            const sequence = ++requestSequence;
            const name = input.value.trim();
            if (name.length < 2) { output.textContent = ''; return; }
            timer = window.setTimeout(async () => {
                const url = new URL(input.dataset.nameCheckUrl, window.location.origin);
                url.searchParams.set('name', name);
                const response = await fetch(url, { headers: { Accept: 'application/json' } });
                if (!response.ok) return;
                const matches = await response.json();
                if (sequence !== requestSequence) return;
                output.textContent = matches.length ? `Nama mirip sudah ada: ${matches.map((item) => item.display_name).join(', ')}` : '';
            }, 400);
        });
    });

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
