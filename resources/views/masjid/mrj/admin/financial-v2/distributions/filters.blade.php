@php
$statusFilter = request()->has('status') ? (string) request('status') : (($recipientSelection ?? false) ? 'active' : '');
@endphp
<form method="get" class="grid gap-3 sm:grid-cols-3 lg:grid-cols-7 mb-5">
<input type="hidden" name="entity" value="{{ $entity->id }}">
@if(request()->filled('per_page'))<input type="hidden" name="per_page" value="{{ request('per_page') }}">@endif
<label class="form-control text-sm">Cari nama / telepon / RT / RW<input class="input input-bordered w-full" name="q" value="{{ request('q') }}" maxlength="240"></label>
<label class="form-control text-sm">Status<select class="select select-bordered" name="status"><option value="" @selected($statusFilter === '')>Semua</option>
@foreach(['active' => 'Aktif', 'inactive' => 'Tidak aktif', 'archived' => 'Arsip'] as $value => $label)<option value="{{ $value }}" 
@selected($statusFilter === $value)>{{ $label }}</option>
@endforeach</select></label>
<label class="form-control text-sm">Jenis<select class="select select-bordered" name="beneficiary_type"><option value="">Semua</option>
@foreach(['YATIM' => 'Yatim', 'DHUAFA' => 'Dhuafa', 'YATIM_DHUAFA' => 'Yatim yang Dhuafa', 'BELUM_DITENTUKAN' => 'Belum ditentukan'] as $value => $label)<option value="{{ $value }}"
@selected(request('beneficiary_type') === $value)>{{ $label }}</option>
@endforeach</select></label>
<label class="form-control text-sm">RT<input class="input input-bordered w-full" name="rt" value="{{ request('rt') }}" maxlength="10"></label>
<label class="form-control text-sm">RW<input class="input input-bordered w-full" name="rw" value="{{ request('rw') }}" maxlength="10"></label>
<label class="form-control text-sm">Koordinator<input class="input input-bordered w-full" name="coordinator" value="{{ request('coordinator') }}" maxlength="160"></label>
<button class="btn btn-outline self-end">Cari / filter</button>
</form>
