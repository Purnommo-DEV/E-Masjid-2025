
@php $deletable = $row->status === 'draft' && $row->realization_id === null && $row->finalized_at === null; @endphp
<div class="flex flex-wrap items-center gap-3 text-sm" role="group" aria-label="Aksi penyaluran {{ $row->program->name }} periode {{ $row->period_label }}" data-distribution-action-group>
<a class="link whitespace-nowrap" href="{{ route('financial-v2.distributions.show', ['distribution' => $row->id, 'entity' => $entity->id]) }}">{{ $deletable ? 'Detail / edit' : 'Detail' }}</a>
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
