
@php $deletable = $row->status === 'draft' && $row->realization_id === null && $row->finalized_at === null; @endphp
<div class="flex flex-wrap items-center gap-3 text-sm" role="group" aria-label="Aksi penyaluran {{ $row->program->name }} periode {{ $row->period_label }}" data-distribution-action-group>
<a class="link whitespace-nowrap" href="{{ route('financial-v2.distributions.show', ['distribution' => $row->id, 'entity' => $entity->id]) }}">{{ $deletable ? 'Detail / edit' : 'Detail' }}</a>
<button type="button" class="link whitespace-nowrap rounded px-1 font-semibold text-primary hover:opacity-75" onclick="document.getElementById('edit-period-{{ $surface }}-{{ $row->id }}').showModal()">Edit periode</button>
<dialog id="edit-period-{{ $surface }}-{{ $row->id }}" class="modal modal-bottom sm:modal-middle" aria-labelledby="edit-period-title-{{ $surface }}-{{ $row->id }}">
    <div class="modal-box w-full max-w-lg p-5 text-left sm:p-6">
        <form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3" aria-label="Tutup">✕</button></form>
        <h3 class="pr-10 text-lg font-bold" id="edit-period-title-{{ $surface }}-{{ $row->id }}">Edit Periode Penyaluran</h3>
        <p class="mt-1 text-sm text-base-content/65">Yang diubah hanya label periode yang tampil. Rentang tanggal bisnis dan tanggal transaksi tidak berubah.</p>
        <form method="post" action="{{ route('financial-v2.distributions.period.update', ['distribution' => $row->id]) }}" class="mt-5 space-y-4">
            @csrf @method('PATCH')
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <input type="hidden" name="revision" value="{{ $row->revision }}">
            <input type="hidden" name="program_id" value="{{ request('program_id') }}">
            <input type="hidden" name="page" value="{{ request('page') }}">
            <label class="form-control">
                <span class="label-text text-sm">Label periode</span>
                <input required name="period_label" maxlength="100" value="{{ $row->period_label }}" class="input input-bordered w-full" autocomplete="off">
            </label>
            <div class="rounded-xl bg-base-200 p-3 text-sm">
                <span class="font-semibold">Rentang bisnis tetap:</span><br>
                {{ \App\Support\FinancialDate::date($row->starts_on) }} — {{ \App\Support\FinancialDate::date($row->ends_on) }}
            </div>
            <div class="modal-action grid grid-cols-1 gap-2 sm:flex sm:flex-row">
                <button type="button" class="btn btn-ghost w-full sm:w-auto" onclick="document.getElementById('edit-period-{{ $surface }}-{{ $row->id }}').close()">Batal</button>
                <button class="btn btn-primary w-full sm:w-auto">Simpan periode</button>
            </div>
        </form>
    </div>
    <form method="dialog" class="modal-backdrop"><button aria-label="Tutup dialog">Tutup</button></form>
</dialog>
@if($deletable)
<a class="link whitespace-nowrap" href="{{ route('financial-v2.distributions.index', ['entity' => $entity->id, 'program_id' => $row->program_id, 'copy_previous' => 1, 'next_start' => $row->ends_on->copy()->addDay()->toDateString(), 'next_end' => $row->ends_on->copy()->addDay()->endOfMonth()->toDateString()]) }}#create">Salin ke periode berikutnya</a>
@endif
@if($deletable)
<button type="button" class="link whitespace-nowrap rounded px-1 text-error hover:opacity-75 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-error" aria-label="Hapus draft penyaluran {{ $row->program->name }} periode {{ $row->period_label }}" data-delete-distribution-trigger onclick="this.nextElementSibling.showModal()">Hapus</button>
<dialog class="modal" data-delete-distribution-dialog aria-labelledby="delete-title-{{ $surface }}-{{ $row->id }}">
    <div class="modal-box text-left">
        <h3 class="text-lg font-bold" id="delete-title-{{ $surface }}-{{ $row->id }}">Hapus Draft Penyaluran?</h3>
        <p class="mt-3 font-semibold">“{{ $row->program->name }} — {{ $row->period_label }}”</p>
        <p class="mt-3 text-sm">Draft penyaluran beserta daftar penerima di dalamnya akan dihapus.</p>
        <p class="mt-1 text-sm">Data transaksi keuangan tidak akan dihapus.</p>
        <div class="modal-action">
            <form method="dialog"><button class="btn btn-ghost">Batal</button></form>
            <form method="post" action="{{ route('financial-v2.distributions.destroy', ['distribution' => $row->id, 'entity' => $entity->id]) }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="entity" value="{{ $entity->id }}">
                <button class="btn btn-error text-white">Hapus</button>
            </form>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop"><button aria-label="Batal">Batal</button></form>
</dialog>
@endif
</div>
