
@if($editable)
<div class="col-start-2 flex flex-wrap items-center gap-1 lg:col-auto">
<button type="button" class="btn btn-outline btn-xs" onclick="document.getElementById('replace-beneficiary-{{ $item->id }}').showModal()">Ganti Penerima</button>
<dialog id="replace-beneficiary-{{ $item->id }}" class="modal modal-bottom sm:modal-middle" aria-labelledby="replace-beneficiary-title-{{ $item->id }}">
<div class="modal-box w-full max-w-xl p-5 text-left sm:p-6">
<form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3" aria-label="Tutup">✕</button></form>
<h3 id="replace-beneficiary-title-{{ $item->id }}" class="pr-10 text-lg font-bold">Ganti Penerima</h3>
<div class="mt-4 rounded-xl bg-base-200 p-3 text-sm"><span class="text-base-content/60">Penerima saat ini</span><strong class="mt-1 block break-words">{{ $item->identity_snapshot['display_name'] ?? 'Identitas tidak tersedia' }}</strong><span class="mt-1 block text-xs text-base-content/65">{{ $item->beneficiary_id ? 'Terhubung ke Master Penerima' : 'Snapshot identitas operasional' }} · Nominal tetap {{ $money($item->amount) }}</span></div>
<form method="post" action="{{ route('financial-v2.distributions.items.update', [$distribution->id, $item->id]) }}" class="mt-5 space-y-4">
@csrf @method('PATCH')
<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}"><input type="hidden" name="replace_beneficiary" value="1">
<label class="form-control text-sm"><span class="label-text">Penerima pengganti</span><select name="beneficiary_id" required class="select select-bordered w-full"><option value="">Pilih penerima aktif</option>
@foreach($replacementCandidates as $candidate)
@php $usedByAnotherItem = $distribution->items->where('id', '!=', $item->id)->contains(fn($other) => $other->effective_beneficiary_id === $candidate->id); @endphp
<option value="{{ $candidate->id }}" @selected($item->effective_beneficiary_id === $candidate->id) @disabled($usedByAnotherItem)>{{ $candidate->display_name }} · RT {{ $candidate->rt ?: '—' }} / RW {{ $candidate->rw ?: '—' }}{{ $usedByAnotherItem ? ' · sudah ada' : '' }}</option>
@endforeach
</select><span class="label-text-alt">Snapshot item akan diambil dari Master Penerima terpilih. Data master dan penyaluran lain tidak berubah.</span></label>
<div class="modal-action grid grid-cols-1 gap-2 sm:flex sm:flex-row"><button type="button" class="btn btn-ghost w-full sm:w-auto" onclick="document.getElementById('replace-beneficiary-{{ $item->id }}').close()">Batal</button><button class="btn btn-primary w-full sm:w-auto">Simpan penggantian</button></div>
</form>
</div>
<form method="dialog" class="modal-backdrop"><button aria-label="Tutup dialog">Tutup</button></form>
</dialog>
<details class="relative"><summary class="btn btn-ghost btn-xs">Edit</summary>
<form class="absolute left-0 z-20 mt-1 grid w-72 max-w-[calc(100vw-6rem)] gap-2 rounded-lg border border-base-300 bg-base-100 p-3 shadow-xl lg:left-auto lg:right-0" method="post" action="{{ route('financial-v2.distributions.items.update', [$distribution->id, $item->id]) }}">
@csrf 
@method('PATCH')
<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}">
@if($item->beneficiary_id)
<input type="hidden" name="beneficiary_id" value="{{ $item->beneficiary_id }}">
@else
<label class="text-xs">Nama lengkap<input class="input input-bordered input-sm w-full" name="display_name" maxlength="240" required value="{{ $item->identity_snapshot['display_name'] ?? '' }}"></label>
<label class="text-xs">Telepon<input class="input input-bordered input-sm w-full" name="contact_reference" maxlength="500" value="{{ $item->identity_snapshot['contact_reference'] ?? '' }}"></label>
<div class="grid grid-cols-2 gap-2"><label class="text-xs">RT<input class="input input-bordered input-sm w-full" name="rt" maxlength="10" value="{{ $item->identity_snapshot['rt'] ?? '' }}"></label><label class="text-xs">RW<input class="input input-bordered input-sm w-full" name="rw" maxlength="10" value="{{ $item->identity_snapshot['rw'] ?? '' }}"></label></div>
<label class="text-xs">Koordinator<input class="input input-bordered input-sm w-full" name="rt_coordinator_name" maxlength="160" value="{{ $item->identity_snapshot['rt_coordinator_name'] ?? '' }}"></label>
<label class="text-xs">Jenis<select class="select select-bordered select-sm w-full" name="beneficiary_type">@foreach(['BELUM_DITENTUKAN' => 'Belum ditentukan', 'YATIM' => 'Yatim', 'DHUAFA' => 'Dhuafa', 'YATIM_DHUAFA' => 'Yatim yang Dhuafa'] as $value => $label)<option value="{{ $value }}" @selected(($item->identity_snapshot['beneficiary_type'] ?? 'BELUM_DITENTUKAN') === $value)>{{ $label }}</option>@endforeach</select></label>
<label class="text-xs">Alamat<textarea class="textarea textarea-bordered textarea-sm w-full" name="address" maxlength="2000">{{ $item->identity_snapshot['address'] ?? '' }}</textarea></label>
@endif
<label class="text-xs" data-money-field>Nominal<div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input class="input input-bordered input-sm w-full pl-9" data-money-input inputmode="decimal" autocomplete="off" required value="{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($item->amount) }}"><input type="hidden" name="amount" data-money-value value="{{ str_ends_with((string) $item->amount, '.00') ? substr((string) $item->amount, 0, -3) : $item->amount }}"></div></label><label class="text-xs">Catatan<input class="input input-bordered input-sm w-full" name="notes" maxlength="2000" value="{{ $item->notes }}"></label><button class="btn btn-primary btn-sm">Simpan</button>
</form></details>
<form method="post" action="{{ route('financial-v2.distributions.items.destroy', [$distribution->id, $item->id]) }}">
@csrf 
@method('DELETE')<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="revision" value="{{ $distribution->revision }}"><button class="btn btn-ghost btn-xs text-error">Hapus</button></form>
</div>
@else
<div class="col-start-2 flex flex-wrap items-center gap-2 lg:col-auto">
@if(auth()->user()?->hasRole('SuperAdmin') && $distribution->status === 'finalized' && $distribution->realization?->status === 'recorded' && $distribution->realization?->transaction?->status === 'posted' && $distribution->realization?->budgetAllocationVersion?->allocation?->status === 'approved')
<button type="button" class="btn btn-warning btn-xs" onclick="document.getElementById('correct-identity-{{ $item->id }}').showModal()">Koreksi Identitas Penerima</button>
<dialog id="correct-identity-{{ $item->id }}" class="modal modal-bottom sm:modal-middle"><div class="modal-box w-full max-w-xl p-5 text-left sm:p-6"><form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3" aria-label="Tutup">✕</button></form><h3 class="pr-10 text-lg font-bold">Koreksi Identitas Penerima</h3><div class="alert alert-warning mt-4 text-sm">Koreksi hanya mengubah atribusi identitas efektif. Nominal pembayaran, transaksi, jurnal, ledger, dan snapshot asli tetap dipertahankan.</div><div class="mt-4 rounded-xl bg-base-200 p-3 text-sm"><span class="text-base-content/60">Identitas efektif saat ini</span><strong class="mt-1 block">{{ $item->effective_identity_snapshot['display_name'] ?? '—' }}</strong><span class="text-xs">Nominal tetap {{ $money($item->amount) }}</span></div><form method="post" action="{{ route('financial-v2.distributions.items.identity-corrections.store', [$distribution->id, $item->id]) }}" class="mt-5 space-y-4">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><label class="form-control text-sm"><span class="label-text">Penerima yang benar</span><select required name="beneficiary_id" class="select select-bordered w-full"><option value="">Pilih penerima aktif</option>@foreach($replacementCandidates as $candidate)@php $used = $distribution->items->where('id', '!=', $item->id)->contains(fn($other) => $other->effective_beneficiary_id === $candidate->id); @endphp<option value="{{ $candidate->id }}" @disabled($used)>{{ $candidate->display_name }} · RT {{ $candidate->rt ?: '—' }} / RW {{ $candidate->rw ?: '—' }}{{ $used ? ' · sudah ada' : '' }}</option>@endforeach</select></label><label class="form-control text-sm"><span class="label-text">Alasan koreksi</span><textarea required minlength="10" maxlength="2000" rows="4" name="reason" class="textarea textarea-bordered w-full" placeholder="Jelaskan kesalahan pencatatan dan dasar identitas yang benar."></textarea></label><div class="modal-action grid grid-cols-1 gap-2 sm:flex"><button type="button" class="btn btn-ghost w-full sm:w-auto" onclick="document.getElementById('correct-identity-{{ $item->id }}').close()">Batal</button><button class="btn btn-warning w-full sm:w-auto">Simpan koreksi</button></div></form></div><form method="dialog" class="modal-backdrop"><button>Tutup</button></form></dialog>
@if(auth()->user()?->hasRole('SuperAdmin') && $item->identityCorrections->isNotEmpty())
<details class="text-xs"><summary class="cursor-pointer font-semibold text-warning">Riwayat koreksi ({{ $item->identityCorrections->count() }})</summary><div class="mt-2 space-y-2">@foreach($item->identityCorrections as $correction)<div class="rounded-lg border border-base-300 p-3"><div><strong>{{ data_get($correction->original_identity_snapshot, 'display_name', '—') }}</strong> → <strong>{{ data_get($correction->corrected_identity_snapshot, 'display_name', '—') }}</strong></div><div class="mt-1 text-base-content/65">{{ $correction->reason }}</div><div class="mt-1 text-base-content/50">{{ $correction->corrected_at?->format('d/m/Y H:i') }} · {{ $correction->correctedBy?->name ?? 'Pengguna tidak tersedia' }}</div></div>@endforeach</div></details>
@endif
@endif
<span class="text-xs font-semibold text-base-content/55">Terkunci</span>
</div>
@endif
