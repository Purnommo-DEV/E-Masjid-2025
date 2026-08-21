@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $group['name'])

@section('content')
    @php $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.'); @endphp
    <section class="mb-6">
        <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.funds.index', ['entity' => $entity->id]) }}">← Kembali ke Dana</a>
        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-emerald-700">Kelompok Dana</p>
                <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $group['name'] }}</h1>
                <p class="mt-1 max-w-3xl text-sm text-base-content/65">{{ $group['description'] }}</p>
            </div>
            <a class="btn btn-primary btn-sm" href="{{ route('financial-v2.allocations.create', ['entity' => $entity->id]) }}">Alokasikan dana</a>
        </div>
    </section>

    <section class="mb-6 grid gap-4 sm:grid-cols-2">
        <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <p class="text-sm font-medium text-base-content/60">Total Saldo Dana</p>
            <p class="mt-2 text-2xl font-bold">{{ $rupiah($group['fund_balance']) }}</p>
            <p class="mt-2 text-xs text-base-content/60">Total ini adalah penjumlahan Saldo Dana dalam kelompok, bukan saldo Rekening/Kas.</p>
        </article>
        <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <p class="text-sm font-medium text-base-content/60">Dana dalam kelompok</p>
            <p class="mt-2 text-2xl font-bold">{{ $group['fund_count'] }} Dana</p>
            @if ($group['financial_accounts'] !== [])
                <p class="mt-2 text-xs text-base-content/60">Rekening terkait: {{ implode(' · ', $group['financial_accounts']) }}</p>
            @else
                <p class="mt-2 text-xs text-base-content/60">Belum ada lokasi likuiditas tercatat pada Posted V2 Ledger.</p>
            @endif
        </article>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($group['funds'] as $card)
            @php $fund = $card['fund']; @endphp
            <a href="{{ route('financial-v2.funds.show', ['fund' => $fund, 'entity' => $entity->id]) }}" class="block rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300 transition hover:bg-base-200">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="font-semibold">{{ $fund->name }}</p>
                        <p class="mt-1 text-xs text-base-content/55">{{ $fund->type?->classification === 'unrestricted' ? 'Dana tidak terikat' : 'Dana dengan peruntukan khusus' }}</p>
                    </div>
                    <span class="badge badge-outline">Detail</span>
                </div>
                <p class="mt-5 text-xs font-medium text-base-content/60">Saldo Dana</p>
                <p class="mt-1 text-2xl font-bold">{{ $rupiah($card['fund_balance']) }}</p>
                @if (($card['financial_accounts'] ?? []) !== [])
                    <p class="mt-2 text-xs text-base-content/60">Rekening terkait: {{ implode(' · ', $card['financial_accounts']) }}</p>
                @endif
            </a>
        @endforeach
    </section>
@endsection
