@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Perencanaan Penggunaan Dana')

@section('content')
    @if (! $entity)
        <div class="mx-auto max-w-2xl rounded-3xl bg-base-100 p-6 shadow-sm">
            <h1 class="text-2xl font-bold">Perencanaan Penggunaan Dana</h1>
            <p class="mt-2 text-sm text-base-content/65">Pilih entitas aktif untuk membuka Planning Financial V2.</p>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @forelse ($entities as $option)
                    <a class="rounded-2xl border border-base-300 p-4 transition hover:border-emerald-600 hover:bg-emerald-50" href="{{ route('financial-v2.plannings.index', ['entity' => $option->id]) }}">
                        <span class="block font-semibold">{{ $option->name }}</span>
                        <span class="text-xs text-base-content/60">{{ $option->code }}</span>
                    </a>
                @empty
                    <div class="alert alert-warning sm:col-span-2">AccountingEntity aktif belum tersedia.</div>
                @endforelse
            </div>
        </div>
    @else
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="text-xs font-semibold uppercase tracking-[.18em] text-emerald-700">Financial V2 · {{ $entity->name }}</div>
                <h1 class="mt-1 text-2xl font-bold sm:text-3xl">Perencanaan Penggunaan Dana</h1>
                <p class="mt-2 max-w-3xl text-sm text-base-content/65">Planning adalah komitmen non-finansial. Saldo aktual baru berubah ketika Realization dicatat melalui PostingEngine.</p>
            </div>
            @can('financial-v2.planning.create')
                <a class="btn btn-primary" href="{{ route('financial-v2.plannings.create', ['entity' => $entity->id]) }}">+ Buat Planning</a>
            @endcan
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ([
                ['Rencana Aktif', number_format($summary['active_count'], 0, ',', '.'), 'Draft dan Approved'],
                ['Total Nilai Rencana', \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($summary['total_amount'], true), 'Selain yang dibatalkan'],
                ['Akan Dialokasikan', \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($summary['to_allocate'], true), 'Approved, belum dikonversi'],
                ['Sudah Direalisasikan', \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($summary['realized'], true), 'Posted dari Allocation Planning'],
            ] as [$label, $value, $help])
                <div class="rounded-2xl bg-base-100 p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-base-content/55">{{ $label }}</div>
                    <div class="mt-2 text-xl font-bold">{{ $value }}</div>
                    <div class="mt-1 text-xs text-base-content/55">{{ $help }}</div>
                </div>
            @endforeach
        </div>

        <form class="mt-6 grid gap-3 rounded-2xl bg-base-100 p-4 shadow-sm md:grid-cols-6" method="GET">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <label class="form-control md:col-span-2"><span class="label-text text-xs">Cari nomor / nama</span><input class="input input-bordered input-sm" name="q" value="{{ request('q') }}"></label>
            <label class="form-control"><span class="label-text text-xs">Status</span><select class="select select-bordered select-sm" name="status"><option value="">Semua</option>@foreach (\App\Models\FinancialV2\Planning::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Program</span><select class="select select-bordered select-sm" name="program_id"><option value="">Semua</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(request('program_id') === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Dari periode</span><input type="date" class="input input-bordered input-sm" name="period_start" value="{{ request('period_start') }}"></label>
            <label class="form-control"><span class="label-text text-xs">Sampai periode</span><input type="date" class="input input-bordered input-sm" name="period_end" value="{{ request('period_end') }}"></label>
            <div class="flex gap-2 md:col-span-6"><button class="btn btn-neutral btn-sm">Terapkan</button><a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.plannings.index', ['entity' => $entity->id]) }}">Reset</a></div>
        </form>

        <div class="mt-5 overflow-x-auto rounded-2xl bg-base-100 shadow-sm">
            <table class="table table-zebra">
                <thead><tr><th>No</th><th>Planning</th><th>Periode / Program</th><th class="text-right">Total</th><th>Sumber Dana</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($plannings as $planning)
                        <tr>
                            <td>{{ $plannings->firstItem() + $loop->index }}</td>
                            <td><a class="font-semibold text-emerald-700 hover:underline" href="{{ route('financial-v2.plannings.show', ['entity' => $entity->id, 'planning' => $planning->id]) }}">{{ $planning->planning_number }}</a><div class="mt-1 text-xs text-base-content/65">{{ $planning->name }}</div></td>
                            <td><div>{{ $planning->period_start->format('d M Y') }} – {{ $planning->period_end->format('d M Y') }}</div><div class="mt-1 text-xs text-base-content/60">{{ $planning->program?->name ?? 'Tanpa program' }}</div></td>
                            <td class="text-right font-semibold">{{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($planning->total_amount, true) }}</td>
                            <td>@foreach ($planning->fundings as $line)<div class="text-xs">{{ $line->fund->name }} · {{ \App\Domain\FinancialV2\DecimalAmount::formatIndonesian($line->amount, true) }}</div>@endforeach</td>
                            <td><span @class(['badge badge-sm', 'badge-ghost' => $planning->status === 'draft', 'badge-success' => $planning->status === 'approved', 'badge-primary' => $planning->status === 'converted', 'badge-error' => $planning->status === 'cancelled'])>{{ ucfirst($planning->status) }}</span></td>
                            <td><a class="btn btn-ghost btn-xs" href="{{ route('financial-v2.plannings.show', ['entity' => $entity->id, 'planning' => $planning->id]) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-base-content/55">Belum ada Planning sesuai filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $plannings->links() }}</div>
    @endif
@endsection
