
@csrf
<input type="hidden" name="entity" value="{{ $entity->id }}">
<div class="grid gap-4 sm:grid-cols-2">
@foreach(['display_name' => ['Nama lengkap', 240], 'contact_reference' => ['Telepon', 500], 'rt' => ['RT', 10], 'rw' => ['RW', 10], 'rt_coordinator_name' => ['Koordinator RT', 160]] as $field => [$label, $max])
<label class="form-control text-sm">{{ $label }}<input class="input input-bordered w-full" name="{{ $field }}" value="{{ old($field, $person?->{$field}) }}" maxlength="{{ $max }}" 
@required($field === 'display_name')></label>
@endforeach
<label class="form-control text-sm">Jenis penerima<select class="select select-bordered" name="beneficiary_type">
@foreach(['YATIM' => 'Yatim', 'DHUAFA' => 'Dhuafa', 'YATIM_DHUAFA' => 'Yatim yang Dhuafa', 'BELUM_DITENTUKAN' => 'Belum ditentukan'] as $value => $label)<option value="{{ $value }}"
@selected(old('beneficiary_type', $person?->beneficiary_type ?? 'BELUM_DITENTUKAN') === $value)>{{ $label }}</option>
@endforeach</select></label>
<label class="form-control text-sm">Status<select class="select select-bordered" name="status">
@foreach(['active' => 'Aktif', 'inactive' => 'Tidak aktif', 'archived' => 'Arsip'] as $value => $label)<option value="{{ $value }}" 
@selected(old('status', $person?->status ?? 'active') === $value)>{{ $label }}</option>
@endforeach</select></label>
@foreach(['address' => 'Alamat', 'beneficiary_notes' => 'Catatan internal'] as $field => $label)
<label class="form-control text-sm">{{ $label }}<textarea class="textarea textarea-bordered" name="{{ $field }}" maxlength="2000">{{ old($field, $person?->{$field}) }}</textarea></label>
@endforeach
</div><button class="btn btn-primary mt-4">Simpan penerima</button>
