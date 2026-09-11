
@extends('masjid.mrj.admin.financial-v2.layout')
@section('title', 'Pilih Entitas ZISWAF')
@section('content')
<h1 class="text-2xl font-bold mb-4">Pilih entitas Financial V2</h1>
<form method="get" class="rounded-2xl bg-base-100 p-5 flex flex-wrap gap-3">
<label>Entitas <select name="entity" required class="select select-bordered">
@foreach($entities as $option)<option value="{{ $option->id }}">{{ $option->name }}</option>
@endforeach</select></label>
<button class="btn btn-primary" 
@disabled($entities->isEmpty())>Lanjutkan</button>
@if($entities->isEmpty())<p>Belum ada entitas aktif. Provisioning master Financial V2 diperlukan; halaman ini tidak membuat data finansial.</p>
@endif
</form>
@endsection
