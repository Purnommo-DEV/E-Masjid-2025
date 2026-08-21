@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Dana')

@section('content')
    @php $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.'); @endphp
    <section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-emerald-700">Dana</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">Kelompok Dana</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">Pilih kelompok pengelolaan Dana terlebih dahulu. Dana tetap menjawab “uang ini untuk apa”; Rekening/Kas menjawab “uangnya berada di mana”.</p>
        </div>
        @if ($entity)<a class="btn btn-primary btn-sm" href="{{ route('financial-v2.allocations.create', ['entity' => $entity->id]) }}">Alokasikan dana</a>@endif
    </section>

    @if (! $entity)
        <div class="alert alert-info"><span>Pilih satu entitas keuangan aktif untuk melihat kelompok Dana.</span></div>
    @else
        <section class="mb-6 grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300"><p class="font-semibold">Rekening / Kas</p><p class="mt-2 text-sm text-base-content/65">Tempat uang berada, misalnya bank atau kas.</p></article>
            <article class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300"><p class="font-semibold">Dana</p><p class="mt-2 text-sm text-base-content/65">Sumber atau peruntukan uang yang dapat memiliki aturan penggunaan.</p></article>
            <article class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300"><p class="font-semibold">Program</p><p class="mt-2 text-sm text-base-content/65">Kegiatan atau tujuan penggunaan dana; bukan rekening dan bukan saldo kas.</p></article>
        </section>
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($groups as $group)
                <a href="{{ route('financial-v2.funds.groups.show', ['group' => $group['key'], 'entity' => $entity->id]) }}" class="block rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300 transition hover:bg-base-200">
                    <div class="flex items-start justify-between gap-4">
                        <div><p class="font-semibold uppercase">{{ $group['name'] }}</p><p class="mt-1 text-xs text-base-content/55">{{ $group['description'] }}</p></div>
                        <span class="badge badge-outline">Buka</span>
                    </div>
                    <p class="mt-5 text-xs font-medium text-base-content/60">Total Saldo Dana</p>
                    <p class="mt-1 text-2xl font-bold">{{ $rupiah($group['fund_balance']) }}</p>
                    @if ($group['financial_accounts'] !== [])
                        <p class="mt-3 text-xs text-base-content/60">Rekening terkait: {{ implode(' · ', $group['financial_accounts']) }}</p>
                    @endif
                </a>
            @empty
                <p class="rounded-2xl bg-base-100 p-6 text-center text-sm text-base-content/60 sm:col-span-2 xl:col-span-3">Belum ada Dana aktif untuk entitas ini.</p>
            @endforelse
        </section>
        <p class="mt-4 text-xs text-base-content/55">Total pada kartu adalah jumlah Saldo Dana yang termasuk kelompok tersebut. Rekening hanya ditampilkan sebagai lokasi likuiditas terkait dan tidak menambah Saldo Dana.</p>
    @endif
@endsection
