@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $fund->name)

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $summary = collect($report['rows'] ?? [])->first() ?? [];
        $history = $fundHistory['history'];
        $sourceHistory = $fundHistory['source_history'];
        $historyTypes = ['OPB' => 'Saldo awal', 'RCV' => 'Penerimaan', 'PAY' => 'Penggunaan', 'TRF' => 'Transfer rekening/kas', 'IFT' => 'Transfer antar Dana', 'ADJ' => 'Penyesuaian'];
        $allocationStatus = ['draft' => 'Draft', 'submitted' => 'Diajukan', 'approved' => 'Disetujui', 'cancelled' => 'Dibatalkan', 'superseded' => 'Digantikan'];
    @endphp

    <section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.funds.index', ['entity' => $entity->id]) }}">← Kembali ke dana</a>
            <p class="mt-3 text-sm font-medium text-emerald-700">Dana</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $fund->name }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">{{ $fund->purpose_statement ?: 'Peruntukan dana belum dicantumkan.' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.allocations.history', ['entity' => $entity->id, 'fund_id' => $fund->id]) }}">Riwayat alokasi</a>
            <a class="btn btn-primary btn-sm" href="{{ route('financial-v2.allocations.create', ['entity' => $entity->id, 'fund_id' => $fund->id]) }}">Alokasikan dana</a>
        </div>
    </section>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-financial-v2.metric title="Saldo Dana" :value="$rupiah($summary['fund_balance'] ?? 0)" />
        <x-financial-v2.metric title="Penerimaan periode" :value="$rupiah($summary['receipts'] ?? 0)" />
        <x-financial-v2.metric title="Penggunaan periode" :value="$rupiah($summary['expenses'] ?? 0)" />
        <x-financial-v2.metric title="Total alokasi" :value="$rupiah($allocationSummary['allocated'])" />
        <x-financial-v2.metric title="Sisa alokasi" :value="$rupiah($allocationSummary['remaining'])" />
    </section>

    <section class="mt-6 grid gap-5 lg:grid-cols-[minmax(0,1.1fr)_minmax(0,0.9fr)]">
        <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <h2 class="font-bold">Peruntukan dan aturan penggunaan</h2>
            <p class="mt-3 text-sm text-base-content/70">{{ $fund->restriction?->policy_basis ?: 'Dana ini mengikuti peruntukan yang telah ditetapkan pengurus.' }}</p>
            <p class="mt-3 text-xs text-base-content/55">Jenis Dana: {{ $fund->type?->classification === 'unrestricted' ? 'Tidak terikat' : 'Dengan peruntukan khusus' }}. Pemeriksaan aturan dilakukan server-side saat transaksi akan dicatat resmi.</p>
        </article>
        <article class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <h2 class="font-bold">Komposisi Rekening</h2>
            <p class="mt-1 text-sm text-base-content/65">Lokasi likuiditas Dana, terpisah dari saldo Dana.</p>
            <div class="mt-3 space-y-2">
                @forelse (($report['account_composition'] ?? []) as $row)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-base-200 px-3 py-3"><span class="text-sm font-medium">{{ $row['financial_account_name'] }}</span><span class="text-sm font-bold">{{ $rupiah($row['liquidity_balance']) }}</span></div>
                @empty
                    <p class="rounded-xl bg-base-200 p-3 text-sm text-base-content/60">Belum ada likuiditas Dana yang tercatat pada rekening atau kas.</p>
                @endforelse
            </div>
        </article>
    </section>

    @if ($sourceHistory['rows'] !== [])
        <section class="mt-6 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <p class="text-sm font-medium text-emerald-700">Riwayat Dana</p>
                    <h2 class="mt-1 font-bold">Riwayat Penggunaan Dana</h2>
                    <p class="mt-1 max-w-3xl text-sm text-base-content/65">Mutasi sumber sebelum V2: uang masuk, penggunaan, dan saldo berjalan Dana. Ini hanya lineage dari {{ $sourceHistory['source_filename'] }}, bukan Journal/Ledger V2 yang diposting ulang dan bukan Allocation.</p>
                </div>
                <div class="flex flex-wrap gap-2 self-start"><span class="badge badge-outline">Sumber historis · terpisah dari Ledger</span><a class="btn btn-outline btn-sm" href="{{ route('financial-v2.funds.history.create', ['fund' => $fund, 'entity' => $entity->id]) }}">Tambah koreksi</a></div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="table table-zebra">
                    <thead><tr><th>Tanggal / Periode</th><th>Uraian</th><th>Keterangan / sumber</th><th>Pemasukan</th><th>Pengeluaran</th><th class="text-right">Saldo Berjalan</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($sourceHistory['rows'] as $row)
                            <tr>
                                <td class="whitespace-nowrap text-sm">{{ $row['date_label'] }}</td>
                                <td class="font-medium">{{ $row['description'] }}@if($row['is_historical_fund_reallocation'])<p class="mt-1 text-xs font-normal text-amber-700">Mutasi historis · {{ $row['classification'] }}</p>@endif</td>
                                <td class="max-w-xs text-xs text-base-content/65">{{ $row['notes'] ?: '—' }}<br><span class="text-base-content/45">{{ $row['source_filename'] }}{{ $row['source_worksheet'] ? ' · '.$row['source_worksheet'] : '' }}{{ $row['source_reference'] ? ' · '.$row['source_reference'] : '' }}</span></td>
                                <td class="whitespace-nowrap">{{ $row['receipt'] === '0.00' ? '—' : $rupiah($row['receipt']) }}</td>
                                <td class="whitespace-nowrap">{{ $row['usage'] === '0.00' ? '—' : $rupiah($row['usage']) }}</td>
                                <td class="whitespace-nowrap text-right font-bold">{{ $rupiah($row['running_balance']) }}</td>
                                <td><span class="badge badge-sm {{ $row['status'] === 'active' ? 'badge-success badge-outline' : ($row['status'] === 'void' ? 'badge-error badge-outline' : 'badge-warning badge-outline') }}">{{ $row['status'] === 'active' ? 'Aktif' : ($row['status'] === 'void' ? 'Void' : 'Dikoreksi') }}</span>@if($row['updated_by_name'])<p class="mt-1 text-xs text-base-content/50">{{ $row['updated_by_name'] }}</p>@endif</td>
                                <td><a class="btn btn-ghost btn-xs whitespace-nowrap" href="{{ route('financial-v2.funds.history.edit', ['fund' => $fund, 'history' => $row['id'], 'entity' => $entity->id]) }}">Edit/Koreksi</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($sourceHistory['account_positions'] !== [])
                <aside class="mt-4 rounded-xl border border-sky-200 bg-sky-50 p-4 text-sm text-sky-950">
                    <p class="font-semibold">Komponen saldo rekening/kas — bukan pemasukan Dana</p>
                    @foreach ($sourceHistory['account_positions'] as $position)
                        <p class="mt-2">{{ $position['date_label'] }} · {{ $position['description'] }}: <strong>{{ $rupiah($position['amount']) }}</strong>. {{ $position['notes'] }} <span class="text-xs opacity-75">{{ $sourceHistory['source_filename'] }} · {{ $position['source_reference'] }}</span></p>
                    @endforeach
                </aside>
            @endif

            <div class="mt-4 grid gap-3 rounded-xl bg-base-200 p-4 text-sm sm:grid-cols-2 xl:grid-cols-4">
                <div><p class="text-base-content/60">Saldo baseline sumber</p><p class="mt-1 font-bold">{{ $rupiah($sourceHistory['opening_source_balance']) }}</p></div>
                <div><p class="text-base-content/60">Mutasi historis sejak baseline</p><p class="mt-1 font-bold {{ $sourceHistory['historical_movement'] === '0.00' ? '' : 'text-amber-700' }}">{{ $sourceHistory['historical_movement'] === '0.00' ? '—' : $rupiah($sourceHistory['historical_movement']) }}</p></div>
                <div><p class="text-base-content/60">Saldo Dana sumber kini</p><p class="mt-1 font-bold">{{ $rupiah($sourceHistory['current_source_balance']) }}</p></div>
                <div><p class="text-base-content/60">Rekonsiliasi riwayat sumber</p><p class="mt-1 font-bold {{ $sourceHistory['difference'] === '0.00' ? 'text-emerald-700' : 'text-error' }}">{{ $sourceHistory['difference'] === '0.00' ? 'PASS · Selisih Rp0,00' : 'Perlu ditinjau · '. $rupiah($sourceHistory['difference']) }}</p></div>
            </div>
            <p class="mt-2 text-xs text-base-content/55">Baseline ditelusuri ke {{ $sourceHistory['source_filename'] }} · {{ $sourceHistory['opening_source_reference'] }}. Saldo kini mengikuti mutasi sumber yang terdokumentasi; komponen rekening/kas di atas tetap terpisah dan tidak ditambahkan ke saldo Dana. Setiap baris dapat dikoreksi dengan alasan dan audit trail, tanpa membuat financial fact baru.</p>
        </section>
    @endif

    <section class="mt-6 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div><h2 class="font-bold">Riwayat Transaksi V2</h2><p class="mt-1 text-sm text-base-content/65">Saldo berjalan dihitung dari Posted V2 Ledger per Dana, bukan dari saldo rekening atau total debit/kredit.</p></div>
            <a class="btn btn-outline btn-sm" href="{{ route('financial-v2.transactions.index', ['entity' => $entity->id, 'fund_id' => $fund->id]) }}">Riwayat transaksi</a>
        </div>
        <form class="mt-4 grid gap-3 rounded-xl bg-base-200 p-3 sm:grid-cols-2 lg:grid-cols-4" method="GET" action="{{ route('financial-v2.funds.show', $fund) }}">
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <label class="form-control"><span class="label-text text-xs">Dari</span><input class="input input-bordered input-sm w-full" type="date" name="from" value="{{ $from }}"></label>
            <label class="form-control"><span class="label-text text-xs">Sampai</span><input class="input input-bordered input-sm w-full" type="date" name="through" value="{{ $through }}"></label>
            <label class="form-control"><span class="label-text text-xs">Jenis</span><select class="select select-bordered select-sm w-full" name="type"><option value="">Semua jenis</option>@foreach($historyTypes as $code => $label)<option value="{{ $code }}" @selected(($filters['type'] ?? '') === $code)>{{ $label }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Program</span><select class="select select-bordered select-sm w-full" name="program_id"><option value="">Semua program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected(($filters['program_id'] ?? '') === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Kategori</span><select class="select select-bordered select-sm w-full" name="category_id"><option value="">Semua kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected(($filters['category_id'] ?? '') === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Rekening/kas</span><select class="select select-bordered select-sm w-full" name="financial_account_id"><option value="">Semua rekening/kas</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected(($filters['financial_account_id'] ?? '') === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
            <label class="form-control"><span class="label-text text-xs">Status</span><select class="select select-bordered select-sm w-full" name="status"><option value="">Semua status</option><option value="posted" @selected(($filters['status'] ?? '') === 'posted')>Tercatat resmi</option><option value="reversed" @selected(($filters['status'] ?? '') === 'reversed')>Dibalik</option></select></label>
            <div class="flex items-end gap-2"><button class="btn btn-primary btn-sm" type="submit">Terapkan</button><a class="btn btn-ghost btn-sm" href="{{ route('financial-v2.funds.show', ['fund' => $fund, 'entity' => $entity->id]) }}">Reset</a></div>
        </form>
        <p class="mt-3 text-xs text-base-content/55">{{ $fundHistory['definition'] }} Saldo awal periode: {{ $rupiah($fundHistory['period_opening']) }}.</p>

        <div class="mt-4 space-y-3 md:hidden">
            @forelse ($history as $row)
                <article class="rounded-xl border border-base-300 p-4">
                    <div class="flex items-start justify-between gap-3"><div><p class="font-semibold">{{ $historyTypes[$row['transaction_type_code']] ?? $row['transaction_type_name'] }}</p><p class="mt-1 text-xs text-base-content/55">{{ \Illuminate\Support\Carbon::parse($row['accounting_date'])->translatedFormat('d M Y') }} · {{ $row['voucher_number'] ?? 'Belum ada voucher' }}</p></div><span class="text-right text-sm font-bold">{{ $rupiah($row['running_fund_balance']) }}</span></div>
                    <p class="mt-2 text-sm text-base-content/70">{{ $row['description'] ?: 'Tanpa keterangan' }}</p>
                    @if($row['financial_account_names'])<p class="mt-1 text-xs text-base-content/55">{{ $row['financial_account_is_attribution'] ? 'Rekening atribusi (saldo tidak berpindah)' : 'Rekening/kas' }}: {{ $row['financial_account_names'] }}</p>@endif
                    @if($row['policy_basis_ref'] || $row['correction_reason'])<div class="mt-2 rounded-lg bg-amber-50 p-2 text-xs text-amber-950"><p class="font-semibold">Dasar koreksi Dana</p>@if($row['correction_reason'])<p class="mt-1">{{ $row['correction_reason'] }}</p>@endif @if($row['policy_basis_ref'])<p class="mt-1 break-words opacity-75">{{ $row['policy_basis_ref'] }}</p>@endif</div>@endif
                    @if($row['source_reference'] || $row['posted_by_name'])<p class="mt-2 break-words text-xs text-base-content/45">{{ collect([$row['source_reference'], $row['posted_by_name'] ? 'Dicatat oleh '.$row['posted_by_name'] : null, $row['posted_at'] ? \Illuminate\Support\Carbon::parse($row['posted_at'])->translatedFormat('d M Y H:i') : null])->filter()->join(' · ') }}</p>@endif
                    <p class="mt-2 text-xs text-base-content/55">Penerimaan {{ $rupiah($row['receipt']) }} · Penggunaan {{ $rupiah($row['usage']) }} · Transfer Dana {{ $rupiah($row['transfer']) }}</p>
                </article>
            @empty
                <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/60">Belum ada riwayat dana pada periode ini.</p>
            @endforelse
        </div>
        <div class="mt-4 hidden overflow-x-auto md:block"><table class="table table-zebra"><thead><tr><th>Tanggal</th><th>Jenis / keterangan</th><th>Penerimaan</th><th>Penggunaan</th><th>Transfer Dana</th><th class="text-right">Saldo berjalan</th></tr></thead><tbody>
            @forelse ($history as $row)
                <tr><td class="whitespace-nowrap text-sm">{{ \Illuminate\Support\Carbon::parse($row['accounting_date'])->translatedFormat('d M Y') }}<br><span class="text-xs text-base-content/55">{{ $row['voucher_number'] ?? '—' }}</span></td><td><p class="font-medium">{{ $historyTypes[$row['transaction_type_code']] ?? $row['transaction_type_name'] }}</p><p class="max-w-md text-xs text-base-content/65">{{ $row['description'] ?: 'Tanpa keterangan' }}</p>@if($row['program_names'] || $row['category_names'])<p class="mt-1 text-xs text-base-content/55">{{ collect([$row['program_names'], $row['category_names']])->filter()->join(' · ') }}</p>@endif @if($row['financial_account_names'])<p class="mt-1 text-xs text-base-content/55">{{ $row['financial_account_is_attribution'] ? 'Rekening atribusi (saldo tidak berpindah)' : 'Rekening/kas' }}: {{ $row['financial_account_names'] }}</p>@endif @if($row['policy_basis_ref'] || $row['correction_reason'])<div class="mt-2 max-w-xl rounded-lg bg-amber-50 p-2 text-xs text-amber-950">@if($row['correction_reason'])<p>{{ $row['correction_reason'] }}</p>@endif @if($row['policy_basis_ref'])<p class="mt-1 break-words opacity-75">{{ $row['policy_basis_ref'] }}</p>@endif</div>@endif @if($row['source_reference'] || $row['posted_by_name'])<p class="mt-1 max-w-xl break-words text-xs text-base-content/45">{{ collect([$row['source_reference'], $row['posted_by_name'] ? 'Dicatat oleh '.$row['posted_by_name'] : null, $row['posted_at'] ? \Illuminate\Support\Carbon::parse($row['posted_at'])->translatedFormat('d M Y H:i') : null])->filter()->join(' · ') }}</p>@endif</td><td class="whitespace-nowrap">{{ $row['receipt'] === '0.00' ? '—' : $rupiah($row['receipt']) }}</td><td class="whitespace-nowrap">{{ $row['usage'] === '0.00' ? '—' : $rupiah($row['usage']) }}</td><td class="whitespace-nowrap">{{ $row['transfer'] === '0.00' ? '—' : $rupiah($row['transfer']) }}</td><td class="whitespace-nowrap text-right font-bold">{{ $rupiah($row['running_fund_balance']) }}</td></tr>
            @empty
                <tr><td colspan="6" class="py-8 text-center text-sm text-base-content/60">Belum ada riwayat dana pada periode ini.</td></tr>
            @endforelse
        </tbody></table></div>
        <div class="mt-4">{{ $history->links() }}</div>
    </section>

    <section class="mt-6 rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="font-bold">Riwayat Alokasi dan realisasi</h2><p class="mt-1 text-sm text-base-content/65">Alokasi adalah rencana dan tidak membuat jurnal. Realisasi hanya berasal dari pembayaran yang sudah tercatat resmi.</p></div><a class="btn btn-outline btn-sm" href="{{ route('financial-v2.allocations.history', ['entity' => $entity->id, 'fund_id' => $fund->id]) }}">Semua alokasi</a></div>
        <div class="mt-4 space-y-3">
            @forelse ($allocationHistory as $item)
                @php $allocation = $item['allocation']; @endphp
                <article class="rounded-xl border border-base-300 p-4"><div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-semibold">{{ $allocation->program?->name ?? 'Tanpa program' }}</p><p class="mt-1 text-sm text-base-content/65">{{ $allocation->reason }}</p><p class="mt-2 text-xs text-base-content/55">{{ $allocation->allocation_reference }} · {{ $allocationStatus[$allocation->status] ?? ucfirst($allocation->status) }}</p></div><div class="text-sm sm:text-right"><p>Total {{ $rupiah($item['allocated']) }}</p><p class="text-base-content/65">Realisasi {{ $rupiah($item['realized']) }}</p><p class="font-bold">Sisa {{ $rupiah($item['remaining']) }}</p></div></div>
                    @if($item['realizations']->isNotEmpty())<div class="mt-3 border-t border-base-200 pt-3 text-xs text-base-content/70">@foreach($item['realizations'] as $realization)<p>{{ $realization->transaction?->accounting_date?->translatedFormat('d M Y') }} · {{ $realization->transaction?->source_reference }} · {{ $rupiah($realization->transaction?->gross_amount) }}</p>@endforeach</div>@endif
                </article>
            @empty
                <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/60">Belum ada alokasi dana.</p>
            @endforelse
        </div>
    </section>
@endsection
