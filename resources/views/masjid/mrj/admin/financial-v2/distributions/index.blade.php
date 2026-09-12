
@extends('masjid.mrj.admin.financial-v2.layout')
@section('title', 'Penyaluran ZISWAF')
@section('content')
<h1 class="text-2xl font-bold">Penyaluran ZISWAF</h1><p class="my-3 text-sm opacity-70">Data operasional penerima. Realisasi dan posting tetap melalui Financial V2.</p>
<form method="get" class="flex flex-wrap gap-3 mb-5"><input type="hidden" name="entity" value="{{ $entity->id }}"><label>Program <select class="select select-bordered" name="program_id"><option value="">Semua program</option>
@foreach($programs as $program)<option value="{{ $program->id }}" 
@selected(request('program_id') === $program->id)>{{ $program->name }}</option>
@endforeach</select></label><button class="btn btn-outline">Filter</button></form>
<div class="hidden md:block rounded-2xl bg-base-100 overflow-x-auto"><table class="table"><thead><tr><th>Program / periode</th><th>Penerima</th><th>Total operasional</th><th>Status operasional / finansial</th><th class="w-1/3">Aksi</th></tr></thead><tbody>
@forelse($distributions as $row)<tr><td>{{ $row->program->name }}<div class="text-sm">{{ $row->period_label }}</div></td><td>{{ $row->items_count }}</td><td>Rp{{ number_format((float) $row->items_sum_amount, 2, ',', '.') }}</td><td>{{ $row->status }} / {{ strtoupper($row->realization?->transaction?->status ?? 'unposted') }}</td><td class="w-1/3 align-top">
@include('masjid.mrj.admin.financial-v2.distributions.links', ['surface' => 'desktop'])</td></tr>
@empty<tr><td colspan="5">Belum ada penyaluran.</td></tr>
@endforelse
</tbody></table></div>
<div class="grid gap-3 md:hidden">
@forelse($distributions as $row)<article class="rounded-2xl bg-base-100 p-4"><h2 class="font-semibold">{{ $row->program->name }} · {{ $row->period_label }}</h2><p class="mt-2 text-sm">{{ $row->items_count }} penerima · Rp{{ number_format((float) $row->items_sum_amount, 2, ',', '.') }}</p><p class="text-sm mb-3">{{ $row->status }} / {{ strtoupper($row->realization?->transaction?->status ?? 'unposted') }}</p>
@include('masjid.mrj.admin.financial-v2.distributions.links', ['surface' => 'mobile'])</article>
@empty<p>Belum ada penyaluran.</p>
@endforelse</div>
<div class="mt-4">{{ $distributions->links() }}</div>
<section id="create" class="bg-base-100 rounded-2xl p-5 mt-6"><h2 class="font-bold text-xl mb-4">Buat penyaluran / salin periode sebelumnya</h2>
<form method="post" action="{{ route('financial-v2.distributions.store') }}">
@csrf<input type="hidden" name="entity" value="{{ $entity->id }}">
<div class="grid gap-4 sm:grid-cols-2">
<label class="form-control text-sm">Program<select name="program_id" required class="select select-bordered">
@foreach($programs->where('status', 'active') as $program)<option value="{{ $program->id }}" 
@selected(old('program_id', request('program_id')) === $program->id)>{{ $program->name }}</option>
@endforeach</select></label>
<label class="form-control text-sm">Judul<input class="input input-bordered w-full" name="title" required maxlength="200" value="{{ old('title', 'Penyaluran penerima manfaat') }}"></label>
<label class="form-control text-sm">Label periode<input class="input input-bordered w-full" name="period_label" required maxlength="100" value="{{ old('period_label', \Carbon\Carbon::parse(request('next_start', now()->toDateString()))->translatedFormat('F Y')) }}"></label>
<label class="form-control text-sm">Tanggal mulai<input class="input input-bordered w-full" type="date" name="starts_on" required value="{{ old('starts_on', request('next_start', now()->startOfMonth()->toDateString())) }}"></label>
<label class="form-control text-sm">Tanggal akhir<input class="input input-bordered w-full" type="date" name="ends_on" required value="{{ old('ends_on', request('next_end', now()->endOfMonth()->toDateString())) }}"></label>
<label class="form-control text-sm">Catatan internal<textarea class="textarea textarea-bordered" name="notes" maxlength="2000">{{ old('notes') }}</textarea></label>
</div><label class="flex gap-3 items-center my-5"><input class="checkbox" type="checkbox" name="copy_previous" value="1" 
@checked(old('copy_previous', request('copy_previous')))><span>Salin dari Penyaluran Sebelumnya</span></label><p class="text-sm mb-4 opacity-70">Menyalin daftar dan nominal dari periode terakhir sebelum tanggal mulai. Salinan selalu draft, tanpa realisasi atau posting.</p><button class="btn btn-primary">Buat draft penyaluran</button>
</form></section>
@endsection
