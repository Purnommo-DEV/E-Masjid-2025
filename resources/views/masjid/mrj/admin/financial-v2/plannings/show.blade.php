@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $planning->planning_number)

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><a class="text-sm text-emerald-700 hover:underline" href="{{ route('financial-v2.plannings.index', ['entity' => $entity->id]) }}">← Daftar Planning</a><div class="mt-4 flex flex-wrap items-center gap-2"><h1 class="text-2xl font-bold sm:text-3xl">{{ $planning->name }}</h1><span @class(['badge', 'badge-ghost' => $planning->status === 'draft', 'badge-success' => $planning->status === 'approved', 'badge-primary' => $planning->status === 'converted', 'badge-error' => $planning->status === 'cancelled'])>{{ ucfirst($planning->status) }}</span></div><p class="mt-2 font-mono text-sm text-base-content/60">{{ $planning->planning_number }}</p></div>
        <div class="flex flex-wrap gap-2">
            @if ($planning->status === 'draft')
                <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.plannings.edit', ['entity' => $entity->id, 'planning' => $planning->id]) }}">Edit</a>
                <form method="POST" action="{{ route('financial-v2.plannings.approve', ['entity' => $entity->id, 'planning' => $planning->id]) }}">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
            @elseif ($planning->status === 'approved')
                <form method="POST" action="{{ route('financial-v2.plannings.convert', ['entity' => $entity->id, 'planning' => $planning->id]) }}">@csrf<button class="btn btn-primary btn-sm">Convert to Allocation</button></form>
            @elseif ($planning->status === 'converted' && $planning->allocation)
                <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.allocations.history', ['entity' => $entity->id]) }}">Lihat Allocation</a>
                @if ($realizationCount > 0)<a class="btn btn-outline btn-sm" href="{{ route('financial-v2.transactions.index', ['entity' => $entity->id]) }}">Lihat Realization ({{ $realizationCount }})</a>@endif
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-5 lg:grid-cols-[1fr_.7fr]">
        <section class="rounded-3xl bg-base-100 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold">Informasi Rencana</h2>
            <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                @foreach ([['Periode', $planning->period_start->format('d M Y').' – '.$planning->period_end->format('d M Y')], ['Program', $planning->program?->name ?? 'Tanpa program'], ['Target penerima', $planning->target_recipient_count === null ? '—' : number_format($planning->target_recipient_count, 0, ',', '.')], ['Nominal/penerima', $planning->amount_per_recipient === null ? '—' : \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($planning->amount_per_recipient, true)], ['Total Planning', \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($planning->total_amount, true)], ['Sudah direalisasikan', \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($realized, true)]] as [$label, $value])<div><dt class="text-xs uppercase tracking-wide text-base-content/50">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $value }}</dd></div>@endforeach
                <div class="sm:col-span-2"><dt class="text-xs uppercase tracking-wide text-base-content/50">Catatan</dt><dd class="mt-1 whitespace-pre-line">{{ $planning->notes ?: '—' }}</dd></div>
            </dl>
        </section>
        <section class="rounded-3xl bg-base-100 p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold">Lifecycle & Audit</h2>
            <ol class="mt-5 space-y-4 text-sm">
                <li class="border-l-2 border-emerald-500 pl-4"><strong>Draft</strong><div class="text-xs text-base-content/55">{{ $planning->created_at?->format('d M Y H:i') }}</div></li>
                @if ($planning->approved_at)<li class="border-l-2 border-emerald-500 pl-4"><strong>Approved</strong><div class="text-xs text-base-content/55">{{ $planning->approved_at->format('d M Y H:i') }} · {{ $planning->approvedBy?->name ?? 'Sistem' }}</div></li>@endif
                @if ($planning->converted_at)<li class="border-l-2 border-emerald-500 pl-4"><strong>Converted</strong><div class="text-xs text-base-content/55">{{ $planning->converted_at->format('d M Y H:i') }} · {{ $planning->convertedBy?->name ?? 'Sistem' }}</div><div class="mt-1 font-mono text-xs">Allocation {{ $planning->allocation?->allocation_reference }}</div></li>@endif
                @if ($planning->cancelled_at)<li class="border-l-2 border-error pl-4"><strong>Cancelled</strong><div class="text-xs text-base-content/55">{{ $planning->cancelled_at->format('d M Y H:i') }} · {{ $planning->cancelledBy?->name ?? 'Sistem' }}</div><div class="mt-1">{{ $planning->cancellation_reason }}</div></li>@endif
            </ol>
        </section>
    </div>

    <section class="mt-5 rounded-3xl bg-base-100 p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-bold">Funding & Kapasitas Dana</h2>
        <p class="mt-1 text-xs text-base-content/55">Available to Plan sudah mengurangi seluruh Approved Planning yang belum dikonversi. Angka negatif tidak ditampilkan sebagai kas tersedia.</p>
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            @foreach ($planning->fundings as $line)
                @php $impact = $impacts[$line->fund_id]; @endphp
                <article class="rounded-2xl border border-base-300 p-4">
                    <div class="flex justify-between gap-3"><div><div class="font-bold">{{ $line->fund->code }} · {{ $line->fund->name }}</div><div class="text-xs text-base-content/55">Requested {{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($line->amount, true) }}</div></div></div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div><dt class="text-base-content/50">Saldo Aktual</dt><dd class="font-semibold">{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($impact['actual'], true) }}</dd></div>
                        <div><dt class="text-base-content/50">Outstanding Allocation</dt><dd class="font-semibold">{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($impact['outstanding'], true) }}</dd></div>
                        <div><dt class="text-base-content/50">Approved Planning</dt><dd class="font-semibold">{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($impact['approved'], true) }}</dd></div>
                        <div><dt class="text-base-content/50">Available to Plan</dt><dd class="font-bold text-emerald-700">{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian(\App\Domain\FinancialV2\DecimalAmount::compare($impact['available'], '0.00') < 0 ? '0.00' : $impact['available'], true) }}</dd></div>
                    </dl>
                </article>
            @endforeach
        </div>
    </section>

    @if (in_array($planning->status, ['draft', 'approved'], true))
        <section class="mt-5 rounded-3xl border border-error/30 bg-error/5 p-5"><h2 class="font-bold">Batalkan Planning</h2><p class="mt-1 text-sm text-base-content/60">Pembatalan tidak menghapus histori dan tidak membuat fakta finansial.</p><form class="mt-4 flex flex-col gap-3 sm:flex-row" method="POST" action="{{ route('financial-v2.plannings.cancel', ['entity' => $entity->id, 'planning' => $planning->id]) }}">@csrf<input class="input input-bordered flex-1" name="cancellation_reason" required maxlength="1000" placeholder="Alasan pembatalan"><button class="btn btn-error">Batalkan Planning</button></form></section>
    @endif
@endsection
