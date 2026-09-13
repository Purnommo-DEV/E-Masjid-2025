<a class="btn btn-ghost btn-xs" href="{{ route('financial-v2.transactions.show', $transaction) }}">Detail</a>
@if ($canEdit($transaction))
    <a class="btn btn-outline btn-xs" href="{{ $isBankMutation($transaction) ? route('financial-v2.bank-mutations.edit', ['transaction' => $transaction, 'entity' => $entity->id]) : route('financial-v2.transactions.edit', $transaction) }}">Ubah draft</a>
@endif
@if ($transaction->status === 'draft')
    @if ($isBankMutation($transaction))
        <form method="POST" action="{{ route('financial-v2.bank-mutations.submit', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary btn-xs">Ajukan</button></form>
        <form method="POST" action="{{ route('financial-v2.bank-mutations.destroy', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-xs text-error">Batalkan</button></form>
    @else
        <form method="POST" action="{{ route('financial-v2.transactions.submit', $transaction) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-primary btn-xs">Ajukan</button></form>
        <details class="dropdown dropdown-end"><summary class="btn btn-ghost btn-xs text-error">Batalkan</summary><form method="POST" action="{{ route('financial-v2.transactions.cancel', $transaction) }}" data-financial-ajax class="dropdown-content z-20 mt-2 w-72 rounded-xl border border-base-300 bg-base-100 p-3 shadow-xl">@csrf<label class="form-control"><span class="label-text text-xs">Alasan pembatalan</span><input name="reason" class="input input-bordered input-sm mt-1" placeholder="Wajib diisi" required></label><button class="btn btn-error btn-sm mt-3 w-full">Batalkan Draft</button></form></details>
    @endif
@endif
