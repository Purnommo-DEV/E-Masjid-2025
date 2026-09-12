
@extends('masjid.mrj.admin.financial-v2.layout')
@section('title', 'Detail Penerima ZISWAF')
@section('content')
<a class="link text-sm" href="{{ route('financial-v2.beneficiaries.index', ['entity' => $entity->id]) }}">← Master Penerima</a>
<section class="rounded-2xl bg-base-100 p-5 my-5 break-words"><h1 class="text-2xl font-bold">{{ $person->display_name }}</h1><p class="text-sm font-semibold mt-1">{{ $person->beneficiary_type_label }}</p><p class="mt-3">{{ $person->address ?: '—' }}</p><p>RT {{ $person->rt ?: '—' }} / RW {{ $person->rw ?: '—' }} · {{ $person->rt_coordinator_name ?: '—' }}</p><p>{{ $person->contact_reference ?: '—' }} · {{ $person->status }}</p><p class="mt-3 text-sm">{{ $person->beneficiary_notes }}</p></section>
<details class="bg-base-100 rounded-2xl p-5 mb-5"
@if($errors->any()) open
@endif><summary class="font-semibold cursor-pointer">Edit identitas penerima</summary><p class="text-sm my-3">Perubahan master tidak mengubah snapshot histori penyaluran.</p><form method="post" action="{{ route('financial-v2.beneficiaries.update', $person->id) }}">
@method('PATCH')
@include('masjid.mrj.admin.financial-v2.distributions.person-form')</form></details>
<h2 class="font-bold text-xl mb-3">Riwayat penyaluran</h2><div class="grid gap-3 sm:grid-cols-2">
@forelse($history as $item)<article class="bg-base-100 rounded-2xl p-5"><a class="font-semibold link" href="{{ route('financial-v2.distributions.show', ['distribution' => $item->distribution_id, 'entity' => $entity->id]) }}">{{ $item->distribution->period_label }} · {{ $item->distribution->program->name }}</a><p class="mt-2">Rp{{ number_format((float) $item->amount, 2, ',', '.') }}</p><p class="text-sm">Operasional: {{ $item->distribution->status }} · Finansial: {{ strtoupper($item->distribution->realization?->transaction?->status ?? 'unposted') }}</p><p class="text-xs mt-2">Snapshot: {{ $item->identity_snapshot['display_name'] }} · {{ $item->identity_snapshot['address'] ?? '—' }}</p></article>
@empty<p>Belum ada riwayat penyaluran.</p>
@endforelse
</div><div class="mt-4">{{ $history->links() }}</div>
@endsection
