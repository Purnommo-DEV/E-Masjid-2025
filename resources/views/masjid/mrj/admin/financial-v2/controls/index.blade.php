@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Kontrol Periode & Rekonsiliasi')

@section('content')
    @php
        $statusTone = fn ($status) => match ($status) {
            'completed', 'hard_closed' => 'badge-success',
            'soft_closed', 'reviewed' => 'badge-warning',
            'blocked', 'exception' => 'badge-error',
            default => 'badge-ghost',
        };
        $statusLabel = fn ($status) => match ($status) {
            'open' => 'Terbuka',
            'soft_closed' => 'Dalam penutupan',
            'hard_closed' => 'Ditutup',
            'draft' => 'Draft',
            'in_progress' => 'Sedang ditinjau',
            'reviewed' => 'Ditinjau',
            'completed' => 'Sesuai',
            'exception' => 'Ada selisih',
            'blocked' => 'Perlu tindak lanjut',
            default => ucfirst(str_replace('_', ' ', (string) $status)),
        };
    @endphp

    <section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Kontrol Keuangan</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">Tutup Periode & Rekonsiliasi Rekening</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">Gunakan halaman ini untuk menutup periode dan mencocokkan saldo sistem dengan rekening atau kas. Tidak ada transaksi baru yang dibuat dari kontrol ini.</p>
            @if ($entity)<a class="mt-2 inline-block text-sm text-emerald-700 hover:underline" href="{{ route('financial-v2.opening-balances.index', ['entity' => $entity->id]) }}">Kelola saldo awal dan rehearsal migrasi</a>@endif
        </div>
    </section>

    <section class="card mb-6 border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body p-4 sm:p-5">
            <form method="GET" action="{{ route('financial-v2.controls.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <label class="form-control max-w-md flex-1">
                    <span class="label-text text-xs font-semibold">Entitas keuangan</span>
                    <select class="select select-bordered select-sm" name="entity" onchange="this.form.submit()">
                        <option value="">Pilih entitas</option>
                        @foreach ($entities as $availableEntity)
                            <option value="{{ $availableEntity->id }}" @selected($entity?->id === $availableEntity->id)>{{ $availableEntity->name }}</option>
                        @endforeach
                    </select>
                </label>
            </form>
        </div>
    </section>

    @if (! $entity)
        <div class="alert alert-info"><span>Pilih satu entitas keuangan aktif untuk melihat dan menjalankan kontrol.</span></div>
    @else
        <section class="card mb-6 border border-base-300 bg-base-100 shadow-sm">
            <div class="card-body p-4 sm:p-5">
                <div class="mb-4">
                    <h2 class="font-bold">Tutup Periode</h2>
                    <p class="text-sm text-base-content/65">Penutupan awal menjalankan pemeriksaan. Penutupan final tersedia setelah semua rekening bank/kas periode ini direkonsiliasi tanpa selisih.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead><tr><th>Periode</th><th>Tanggal</th><th>Status</th><th>Riwayat kontrol</th><th class="text-right">Tindakan</th></tr></thead>
                        <tbody>
                            @forelse ($periods as $period)
                                @php $periodRuns = $runs->get($period->id, collect()); @endphp
                                <tr>
                                    <td class="font-medium">{{ $period->period_name }}</td>
                                    <td>{{ $period->start_date->toDateString() }} s.d. {{ $period->end_date->toDateString() }}</td>
                                    <td><span class="badge {{ $statusTone($period->status) }} badge-sm">{{ $statusLabel($period->status) }}</span></td>
                                    <td class="text-xs">
                                        @forelse ($periodRuns as $run)
                                            <div>{{ $run->run_type === 'soft_close' ? 'Penutupan awal' : 'Penutupan final' }}: <span class="font-medium">{{ $statusLabel($run->status) }}</span></div>
                                        @empty
                                            <span class="opacity-60">Belum ada.</span>
                                        @endforelse
                                    </td>
                                    <td class="text-right">
                                        @if ($period->status === 'open')
                                            <form method="POST" action="{{ route('financial-v2.controls.close', $period) }}" data-financial-ajax>
                                                @csrf
                                                <input type="hidden" name="entity" value="{{ $entity->id }}">
                                                <input type="hidden" name="run_type" value="soft_close">
                                                <input type="hidden" name="reference" value="UX soft close">
                                                <button class="btn btn-warning btn-xs" type="submit">Mulai penutupan periode</button>
                                            </form>
                                        @elseif ($period->status === 'soft_closed')
                                            <form method="POST" action="{{ route('financial-v2.controls.close', $period) }}" data-financial-ajax>
                                                @csrf
                                                <input type="hidden" name="entity" value="{{ $entity->id }}">
                                                <input type="hidden" name="run_type" value="hard_close">
                                                <input type="hidden" name="reference" value="UX hard close">
                                                <button class="btn btn-error btn-xs" type="submit">Selesaikan penutupan periode</button>
                                            </form>
                                        @else
                                            <span class="text-xs text-base-content/55">Tidak ada tindakan langsung.</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-base-content/60">Belum ada periode untuk entitas ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.35fr)]">
            <div class="card min-w-0 border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4 sm:p-5">
                    <h2 class="font-bold">Rekonsiliasi Rekening</h2>
                    <p class="mb-4 text-sm text-base-content/65">Masukkan saldo bank atau hasil hitung kas pada tanggal yang dipilih. Sistem menghitung saldo menurut sistem secara otomatis dari transaksi yang sudah dicatat resmi.</p>
                    <form method="POST" action="{{ route('financial-v2.controls.reconciliations.store') }}" enctype="multipart/form-data" class="grid gap-3" data-financial-ajax>
                        @csrf
                        <input type="hidden" name="entity" value="{{ $entity->id }}">
                        <label class="form-control"><span class="label-text text-xs font-semibold">Rekening / Kas</span><select class="select select-bordered select-sm" name="financial_account_id" required><option value="">Pilih rekening</option>@foreach ($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text text-xs font-semibold">Periode</span><select class="select select-bordered select-sm" name="accounting_period_id" required><option value="">Pilih periode</option>@foreach ($periods->whereIn('status', ['open', 'soft_closed']) as $period)<option value="{{ $period->id }}">{{ $period->period_name }} ({{ $statusLabel($period->status) }})</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text text-xs font-semibold">Tanggal rekening / hitung kas</span><input class="input input-bordered input-sm" type="date" name="as_of_date" value="{{ now()->toDateString() }}" required></label>
                        <label class="form-control"><span class="label-text text-xs font-semibold">Saldo menurut rekening atau kas</span><input class="input input-bordered input-sm" inputmode="decimal" name="statement_balance" placeholder="0.00" required></label>
                        <label class="form-control"><span class="label-text text-xs font-semibold">Lampiran bukti rekening / hitung kas</span><input class="file-input file-input-bordered file-input-sm" type="file" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.webp" required></label>
                        <label class="form-control"><span class="label-text text-xs font-semibold">Catatan (opsional)</span><textarea class="textarea textarea-bordered textarea-sm" name="notes" rows="2"></textarea></label>
                        <button class="btn btn-primary btn-sm" type="submit">Buat Rekonsiliasi</button>
                    </form>
                </div>
            </div>

            <div class="card min-w-0 border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-4 sm:p-5">
                    <h2 class="font-bold">Status Rekonsiliasi</h2>
                    <p class="mb-4 text-sm text-base-content/65">Rekonsiliasi selesai hanya jika selisih tepat Rp 0,00 dan bukti tersedia. Jika ada selisih, catat sebagai tindak lanjut; sistem tidak mengubah saldo secara otomatis.</p>
                    <div class="overflow-x-auto">
                        <table class="table table-sm">
                            <thead><tr><th>Rekening / tanggal</th><th class="text-right">Menurut rekening/kas</th><th class="text-right">Menurut sistem</th><th class="text-right">Selisih</th><th>Status</th><th>Tindakan</th></tr></thead>
                            <tbody>
                                @forelse ($reconciliations as $reconciliation)
                                    <tr>
                                        <td><div class="font-medium">{{ $reconciliation->financialAccount->code }} · {{ $reconciliation->financialAccount->name }}</div><div class="text-xs opacity-60">{{ $reconciliation->business_date->toDateString() }} · {{ $reconciliation->period->period_name }}</div></td>
                                        <td class="text-right font-mono">Rp {{ $reconciliation->statement_balance }}</td>
                                        <td class="text-right font-mono">Rp {{ $reconciliation->ledger_balance }}</td>
                                        <td class="text-right font-mono">Rp {{ $reconciliation->difference }}</td>
                                        <td><span class="badge {{ $statusTone($reconciliation->status) }} badge-sm">{{ $statusLabel($reconciliation->status) }}</span></td>
                                        <td class="min-w-48">
                                            @if (in_array($reconciliation->status, ['draft', 'in_progress'], true))
                                                <form method="POST" action="{{ route('financial-v2.controls.reconciliations.review', $reconciliation) }}" class="mb-1" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-xs" type="submit">Tinjau</button></form>
                                            @endif
                                            @if ($reconciliation->status === 'reviewed')
                                                <form method="POST" action="{{ route('financial-v2.controls.reconciliations.complete', $reconciliation) }}" class="mb-1" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-success btn-xs" type="submit">Selesaikan</button></form>
                                                <form method="POST" action="{{ route('financial-v2.controls.reconciliations.exception', $reconciliation) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input class="input input-bordered input-xs mb-1 w-full" name="reason" placeholder="Alasan selisih" required><button class="btn btn-error btn-xs" type="submit">Catat ada selisih</button></form>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-base-content/60">Belum ada rekonsiliasi untuk entitas ini.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
