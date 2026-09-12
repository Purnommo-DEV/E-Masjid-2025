
@if($editable)
<div class="col-start-2 flex flex-wrap items-center gap-1 lg:col-auto">
<details class="relative"><summary class="btn btn-ghost btn-xs">Edit</summary>
<form class="absolute left-0 z-20 mt-1 grid w-72 max-w-[calc(100vw-6rem)] gap-2 rounded-lg border border-base-300 bg-base-100 p-3 shadow-xl lg:left-auto lg:right-0" method="post" action="{{ route('financial-v2.distributions.items.update', [$distribution->id, $item->id]) }}">
@csrf 
@method('PATCH')
<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}"><input type="hidden" name="beneficiary_id" value="{{ $item->beneficiary_id }}">
<label class="text-xs" data-money-field>Nominal<div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input class="input input-bordered input-sm w-full pl-9" data-money-input inputmode="decimal" autocomplete="off" required value="{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($item->amount) }}"><input type="hidden" name="amount" data-money-value value="{{ str_ends_with((string) $item->amount, '.00') ? substr((string) $item->amount, 0, -3) : $item->amount }}"></div></label><label class="text-xs">Catatan<input class="input input-bordered input-sm w-full" name="notes" maxlength="2000" value="{{ $item->notes }}"></label><button class="btn btn-primary btn-sm">Simpan</button>
</form></details>
<form method="post" action="{{ route('financial-v2.distributions.items.destroy', [$distribution->id, $item->id]) }}">
@csrf 
@method('DELETE')<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}"><button class="btn btn-ghost btn-xs text-error">Hapus</button></form>
</div>
@else
<span class="col-start-2 text-xs font-semibold text-base-content/55 lg:col-auto">Terkunci</span>
@endif
