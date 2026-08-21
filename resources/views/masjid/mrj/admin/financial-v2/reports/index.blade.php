@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $reportDefinitions[$report])

@section('content')
    @php
        $data = $reportData['data'];
        $apiQuery = array_filter(array_merge($filters, ['report' => $report]), fn ($value) => $value !== null && $value !== '');
        $format = fn ($amount) => 'Rp '.($amount ?? '0.00');
        $hasData = (bool) ($data['has_data'] ?? false);
        $isFridayReport = $report === 'friday';
    @endphp

    <section class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">Laporan Keuangan</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $reportDefinitions[$report] }}</h1>
            <p class="mt-1 max-w-3xl text-sm text-base-content/65">Angka hanya berasal dari transaksi yang sudah dicatat resmi pada periode yang dipilih.</p>
        </div>
        <details class="text-sm"><summary class="cursor-pointer text-base-content/65">Detail sumber akuntansi</summary><div class="mt-2 rounded-xl bg-base-100 p-3 text-xs text-base-content/65 shadow-sm ring-1 ring-base-300">Data laporan bersifat baca-saja dan berasal dari jurnal serta buku besar yang sudah dicatat resmi. <a class="link" href="{{ route('financial-v2.reports.data', $apiQuery) }}" target="_blank" rel="noopener">Buka data teknis (JSON)</a></div></details>
    </section>

    <section class="card mb-6 border border-base-300 bg-base-100 shadow-sm">
        <div class="card-body gap-4 p-4 sm:p-5">
            <form method="GET" action="{{ route('financial-v2.reports.index') }}" class="grid gap-3 md:grid-cols-2 xl:grid-cols-5" data-report-filter>
                <input type="hidden" name="report" value="{{ $report }}">
                <label class="form-control">
                    <span class="label-text text-xs font-semibold">Entitas</span>
                    <select class="select select-bordered select-sm w-full" name="entity">
                        <option value="">Pilih entitas</option>
                        @foreach ($entities as $availableEntity)
                            <option value="{{ $availableEntity->id }}" @selected($entity?->id === $availableEntity->id)>{{ $availableEntity->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-control">
                    <span class="label-text text-xs font-semibold">Laporan</span>
                    <select class="select select-bordered select-sm w-full" name="report" onchange="this.form.submit()">
                        @foreach ($reportDefinitions as $key => $label)
                            <option value="{{ $key }}" @selected($report === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="form-control">
                    <span class="label-text text-xs font-semibold">{{ $isFridayReport ? 'Tanggal Jumat / mulai periode' : 'Dari tanggal' }}</span>
                    <input class="input input-bordered input-sm w-full" type="date" name="from" value="{{ $filters['from'] }}">
                </label>
                <label class="form-control">
                    <span class="label-text text-xs font-semibold">{{ $isFridayReport ? 'Akhir periode' : 'Sampai tanggal' }}</span>
                    <input class="input input-bordered input-sm w-full" type="date" name="through" value="{{ $filters['through'] }}">
                </label>
                <div class="flex items-end">
                    <button class="btn btn-primary btn-sm w-full" type="submit">Terapkan filter</button>
                </div>

                @if (in_array($report, ['account-balance', 'account-movement'], true))
                    <label class="form-control md:col-span-2">
                        <span class="label-text text-xs font-semibold">Rekening</span>
                        <select class="select select-bordered select-sm w-full" name="financial_account_id">
                            <option value="">Semua Rekening</option>
                            @foreach ($filterOptions['financial_accounts'] as $option)
                                <option value="{{ $option['id'] }}" @selected(($filters['financial_account_id'] ?? null) === $option['id'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if (in_array($report, ['fund-balance', 'fund-movement', 'ziswaf'], true))
                    <label class="form-control md:col-span-2">
                        <span class="label-text text-xs font-semibold">Dana</span>
                        <select class="select select-bordered select-sm w-full" name="fund_id">
                            <option value="">Semua Dana</option>
                            @foreach ($filterOptions['funds'] as $option)
                                <option value="{{ $option['id'] }}" @selected(($filters['fund_id'] ?? null) === $option['id'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                @if ($report === 'program')
                    <label class="form-control md:col-span-2">
                        <span class="label-text text-xs font-semibold">Program</span>
                        <select class="select select-bordered select-sm w-full" name="program_id">
                            <option value="">Semua Program</option>
                            @foreach ($filterOptions['programs'] as $option)
                                <option value="{{ $option['id'] }}" @selected(($filters['program_id'] ?? null) === $option['id'])>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </form>
        </div>
    </section>

    <div class="mb-5 flex flex-wrap gap-2 text-xs text-base-content/60">
        <span class="badge badge-outline">Periode: {{ $reportData['period']['from_accounting_date'] }} s.d. {{ $reportData['period']['through_accounting_date'] }}</span>
        <span class="badge badge-outline">Data sampai transaksi resmi ke-{{ $reportData['as_of_posting_sequence'] }}</span>
    </div>

    @if (! $entity)
        <div class="alert alert-info"><span>{{ $data['message'] }}</span></div>
    @elseif (isset($data['requires_filter']))
        <div class="alert alert-info"><span>{{ $data['message'] }}</span></div>
    @elseif (! $hasData)
        <div class="card border border-dashed border-base-300 bg-base-100">
            <div class="card-body items-start py-10">
                <h2 class="font-semibold">Belum ada data pada periode ini.</h2>
                <p class="text-sm text-base-content/65">Setelah transaksi dicatat resmi, laporan akan menampilkan angkanya di sini.</p>
            </div>
        </div>
    @elseif ($report === 'summary')
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-financial-v2.metric title="Posisi kas/bank" :value="$format($data['cash_position'])" />
            <x-financial-v2.metric title="Penerimaan periode" :value="$format($data['cash_in'])" />
            <x-financial-v2.metric title="Pengeluaran periode" :value="$format($data['cash_out'])" />
            <x-financial-v2.metric title="Neraca Saldo" :value="$data['trial_balance_is_balanced'] ? 'Seimbang' : 'Perlu investigasi'" :tone="$data['trial_balance_is_balanced'] ? 'success' : 'warning'" />
        </section>
        <p class="mt-5 text-sm text-base-content/65">{{ $data['definition'] }}</p>
    @elseif ($report === 'account-balance')
        <x-financial-v2.table title="Saldo Rekening">
            <thead><tr><th>Rekening</th><th class="text-right">Saldo awal</th><th class="text-right">Bertambah</th><th class="text-right">Berkurang</th><th class="text-right">Saldo akhir</th></tr></thead>
            <tbody>
                @foreach ($data['rows'] as $row)
                    <tr><td><div class="font-medium">{{ $row['code'] }}</div><div class="text-xs opacity-60">{{ $row['name'] }}</div></td><td class="text-right font-mono">{{ $format($row['opening_balance']) }}</td><td class="text-right font-mono">{{ $format($row['period_debit']) }}</td><td class="text-right font-mono">{{ $format($row['period_credit']) }}</td><td class="text-right font-mono font-semibold">{{ $format($row['closing_balance']) }}</td></tr>
                @endforeach
            </tbody>
        </x-financial-v2.table>
        @if ($data['fund_composition'] !== [])
            <x-financial-v2.table class="mt-6" title="Komposisi Dana pada Rekening">
                <thead><tr><th>Rekening</th><th>Dana</th><th class="text-right">Saldo pada rekening</th></tr></thead>
                <tbody>@foreach ($data['fund_composition'] as $row)<tr><td>{{ $row['financial_account_code'] }}</td><td>{{ $row['fund_code'] }} · {{ $row['fund_name'] }}</td><td class="text-right font-mono">{{ $format($row['balance']) }}</td></tr>@endforeach</tbody>
            </x-financial-v2.table>
        @endif
    @elseif (in_array($report, ['fund-balance', 'ziswaf'], true))
        <x-financial-v2.table :title="$report === 'ziswaf' ? 'Saldo Dana untuk pelaporan ZISWAF' : 'Saldo Dana'">
            <thead><tr><th>Dana</th><th>Klasifikasi</th><th class="text-right">Saldo awal</th><th class="text-right">Penerimaan</th><th class="text-right">Penggunaan</th><th class="text-right">Transfer masuk</th><th class="text-right">Transfer keluar</th><th class="text-right">Adjustment</th><th class="text-right">Komponen kebijakan lain</th><th class="text-right">Saldo Dana</th><th class="text-right">Likuiditas tersedia</th></tr></thead>
            <tbody>@foreach ($data['rows'] as $row)<tr><td><div class="font-medium">{{ $row['code'] }}</div><div class="text-xs opacity-60">{{ $row['name'] }}</div></td><td>{{ $row['classification'] }}</td><td class="text-right font-mono">{{ $format($row['opening_fund_balance']) }}</td><td class="text-right font-mono">{{ $format($row['receipts']) }}</td><td class="text-right font-mono">{{ $format($row['expenses']) }}</td><td class="text-right font-mono">{{ $format($row['transfer_in']) }}</td><td class="text-right font-mono">{{ $format($row['transfer_out']) }}</td><td class="text-right font-mono">{{ $format($row['adjustments']) }}</td><td class="text-right font-mono">{{ $format($row['other_policy_components']) }}</td><td class="text-right font-mono font-semibold">{{ $format($row['fund_balance']) }}</td><td class="text-right font-mono">{{ $format($row['available_liquidity']) }}</td></tr>@endforeach</tbody>
        </x-financial-v2.table>
        @if ($data['account_composition'] !== [])
            <x-financial-v2.table class="mt-6" title="Komposisi Rekening">
                <thead><tr><th>Dana</th><th>Rekening</th><th class="text-right">Likuiditas tersedia</th></tr></thead>
                <tbody>@foreach ($data['account_composition'] as $row)<tr><td>{{ $row['fund_code'] }}</td><td>{{ $row['financial_account_code'] }} · {{ $row['financial_account_name'] }}</td><td class="text-right font-mono">{{ $format($row['liquidity_balance']) }}</td></tr>@endforeach</tbody>
            </x-financial-v2.table>
        @endif
        <p class="mt-4 text-sm text-base-content/65">{{ $data['definition_note'] ?? $data['definition'] }}</p>
    @elseif (in_array($report, ['account-movement', 'fund-movement'], true))
        <div class="stats mb-5 w-full border border-base-300 bg-base-100 shadow-sm">
            <div class="stat"><div class="stat-title">{{ $report === 'fund-movement' ? 'Saldo Dana pembuka halaman' : 'Saldo pembuka halaman' }}</div><div class="stat-value text-xl font-mono">{{ $format($data['page_opening_balance'] ?? $data['page_opening_fund_balance'] ?? $data['page_opening_net_position']) }}</div></div>
            <div class="stat"><div class="stat-title">{{ $report === 'fund-movement' ? 'Saldo Dana pembuka periode' : 'Saldo pembuka periode' }}</div><div class="stat-value text-xl font-mono">{{ $format($data['period_opening_balance'] ?? $data['period_opening_fund_balance'] ?? $data['period_opening_net_position']) }}</div></div>
        </div>
        <x-financial-v2.table :title="$report === 'account-movement' ? 'Mutasi Rekening' : 'Mutasi Dana'">
            <thead><tr><th>Tanggal</th><th>Nomor bukti</th><th>Rekening / Dana / Program</th><th class="text-right">{{ $report === 'fund-movement' ? 'Dampak Dana' : 'Perubahan' }}</th><th class="text-right">{{ $report === 'fund-movement' ? 'Saldo Dana berjalan' : 'Saldo berjalan' }}</th></tr></thead>
            <tbody>@foreach ($data['rows'] as $row)@php $movementAmount = $report === 'fund-movement' ? $row['fund_balance_delta'] : $row['signed_amount']; @endphp<tr><td>{{ $row['accounting_date'] }}</td><td><div>{{ $row['voucher_number'] ?: '—' }}</div><div class="text-xs opacity-60">{{ $row['transaction_type_name'] }} @if($row['reversal_of_journal_id']) · pembalikan @endif</div></td><td><div>{{ $row['financial_account_code'] ?? $row['fund_code'] ?? '—' }}</div><div class="text-xs opacity-60">{{ $row['program_code'] ? 'Program '.$row['program_code'] : 'Tanpa program' }}</div></td><td class="text-right font-mono">{{ $movementAmount[0] === '-' ? '−' : '+' }}{{ $format(ltrim($movementAmount, '-')) }}</td><td class="text-right font-mono font-semibold">{{ $format($report === 'fund-movement' ? $row['running_fund_balance'] : $row['running_balance']) }}</td></tr>@endforeach</tbody>
        </x-financial-v2.table>
    @elseif ($report === 'transaction-history')
        <x-financial-v2.table title="Riwayat Transaksi Resmi">
            <thead><tr><th>Tanggal / bukti</th><th>Jenis</th><th>Rekening, dana, dan program</th><th>Lampiran bukti</th><th class="text-right">Nominal</th></tr></thead>
            <tbody>@foreach ($data['rows'] as $row)<tr><td>{{ $row['accounting_date'] }}<div class="text-xs opacity-60">{{ $row['voucher_number'] ?: 'Nomor bukti belum tersedia' }}</div></td><td><div>{{ $row['transaction_type_name'] }}</div><div class="text-xs opacity-60">Dicatat resmi</div></td><td class="text-xs">Dana: {{ $row['fund_codes'] ?: '—' }}<br>Rekening: {{ $row['financial_account_codes'] ?: '—' }}<br>Program: {{ $row['program_codes'] ?: '—' }}</td><td>{{ $row['evidence_count'] }} lampiran</td><td class="text-right font-mono">{{ $format($row['amount']) }}</td></tr>@endforeach</tbody>
        </x-financial-v2.table>
        <details class="mt-4 rounded-xl border border-base-300 bg-base-100 p-4"><summary class="cursor-pointer text-sm font-semibold">Detail akuntansi transaksi</summary><div class="mt-3 overflow-x-auto"><table class="table table-sm"><thead><tr><th>ID jurnal</th><th>Jumlah baris</th></tr></thead><tbody>@foreach ($data['rows'] as $row)<tr><td class="font-mono text-xs">{{ $row['journal_id'] }}</td><td>{{ $row['line_count'] }}</td></tr>@endforeach</tbody></table></div></details>
    @elseif ($report === 'cash-flow' || $report === 'friday')
        @php $isFriday = $report === 'friday'; @endphp
        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-financial-v2.metric title="Saldo awal" :value="$format($data['opening_balance'])" />
            <x-financial-v2.metric :title="$isFriday ? 'Penerimaan' : 'Arus masuk'" :value="$format($data[$isFriday ? 'receipts' : 'cash_in'])" />
            <x-financial-v2.metric :title="$isFriday ? 'Pengeluaran' : 'Arus keluar'" :value="$format($data[$isFriday ? 'payments' : 'cash_out'])" />
            <x-financial-v2.metric title="Saldo akhir" :value="$format($data['closing_balance'])" :tone="$data['is_tied_out'] ? 'success' : 'warning'" />
        </section>
        <div class="mt-5 rounded-2xl border border-base-300 bg-base-100 p-4 text-sm text-base-content/70">
            <p>Transfer internal (net): <span class="font-mono">{{ $format($data['internal_transfer_net']) }}</span></p>
            <p>Pergerakan kas belum terklasifikasi: <span class="font-mono">{{ $format($data['unclassified_cash_movement']) }}</span></p>
            <p class="mt-2 font-medium {{ $data['is_tied_out'] ? 'text-emerald-700' : 'text-amber-700' }}">{{ $data['is_tied_out'] ? 'Arus kas sudah sesuai dengan saldo akhir.' : 'Arus kas dan saldo akhir perlu ditelusuri.' }}</p>
        </div>
        @if ($isFriday)
            <div class="mt-6 grid gap-6 xl:grid-cols-2">
                <x-financial-v2.table title="Pemasukan">
                    <thead><tr><th>Tanggal / bukti</th><th>Rincian</th><th class="text-right">Nominal</th></tr></thead>
                    <tbody>
                        @forelse ($data['receipt_rows'] as $row)
                            <tr><td>{{ $row['accounting_date'] }}<div class="text-xs opacity-60">{{ $row['voucher_number'] ?: 'Bukti belum tersedia' }}</div></td><td>{{ $row['description'] ?: $row['transaction_type_name'] }}@if($row['is_reversal'])<div class="text-xs text-amber-700">Pembalikan</div>@endif</td><td class="text-right font-mono">{{ $format($row['amount']) }}</td></tr>
                        @empty <tr><td colspan="3" class="text-center text-sm opacity-60">Tidak ada penerimaan resmi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="font-semibold"><td colspan="2">Total pemasukan</td><td class="text-right font-mono">{{ $format($data['receipts']) }}</td></tr></tfoot>
                </x-financial-v2.table>
                <x-financial-v2.table title="Pengeluaran">
                    <thead><tr><th>Tanggal / bukti</th><th>Rincian</th><th class="text-right">Nominal</th></tr></thead>
                    <tbody>
                        @forelse ($data['payment_rows'] as $row)
                            <tr><td>{{ $row['accounting_date'] }}<div class="text-xs opacity-60">{{ $row['voucher_number'] ?: 'Bukti belum tersedia' }}</div></td><td>{{ $row['description'] ?: $row['transaction_type_name'] }}@if($row['is_reversal'])<div class="text-xs text-amber-700">Pembalikan</div>@endif</td><td class="text-right font-mono">{{ $format($row['amount']) }}</td></tr>
                        @empty <tr><td colspan="3" class="text-center text-sm opacity-60">Tidak ada pengeluaran resmi pada periode ini.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot><tr class="font-semibold"><td colspan="2">Total pengeluaran</td><td class="text-right font-mono">{{ $format($data['payments']) }}</td></tr></tfoot>
                </x-financial-v2.table>
            </div>
        @endif
        <p class="mt-4 text-sm text-base-content/65">{{ $data['definition_note'] ?? $data['definition'] }}</p>
    @elseif ($report === 'trial-balance')
        <div class="alert {{ $data['is_balanced'] ? 'alert-success' : 'alert-warning' }} mb-5"><span>{{ $data['is_balanced'] ? 'Total debit dan kredit seimbang pada watermark ini.' : 'Total debit dan kredit tidak seimbang; investigasi sebelum digunakan.' }}</span></div>
        <details class="rounded-xl border border-base-300 bg-base-100 p-4"><summary class="cursor-pointer font-semibold">Buka detail akuntansi per akun</summary><div class="mt-4"><x-financial-v2.table title="Neraca Saldo"><thead><tr><th>Akun</th><th>Kelas</th><th class="text-right">Saldo awal</th><th class="text-right">Debit</th><th class="text-right">Kredit</th><th class="text-right">Saldo akhir</th></tr></thead><tbody>@foreach ($data['rows'] as $row)<tr><td>{{ $row['code'] }} · {{ $row['name'] }}</td><td>{{ $row['class'] }}</td><td class="text-right font-mono">{{ $format($row['opening_balance']) }}</td><td class="text-right font-mono">{{ $format($row['debit_total']) }}</td><td class="text-right font-mono">{{ $format($row['credit_total']) }}</td><td class="text-right font-mono">{{ $format($row['closing_balance']) }}</td></tr>@endforeach</tbody><tfoot><tr class="font-semibold"><td colspan="3">Total</td><td class="text-right font-mono">{{ $format($data['total_debit']) }}</td><td class="text-right font-mono">{{ $format($data['total_credit']) }}</td><td></td></tr></tfoot></x-financial-v2.table></div></details>
    @elseif ($report === 'program')
        <x-financial-v2.table title="Realisasi Program">
            <thead><tr><th>Program</th><th>Sumber Dana</th><th class="text-right">Penerimaan</th><th class="text-right">Penggunaan</th><th class="text-right">Penggunaan net</th></tr></thead>
            <tbody>@foreach ($data['rows'] as $row)<tr><td>{{ $row['code'] }} · {{ $row['name'] }}</td><td>{{ $row['fund_codes'] ?: '—' }}</td><td class="text-right font-mono">{{ $format($row['receipts']) }}</td><td class="text-right font-mono">{{ $format($row['usage']) }}</td><td class="text-right font-mono font-semibold">{{ $format($row['net_usage']) }}</td></tr>@endforeach</tbody>
        </x-financial-v2.table>
        <p class="mt-4 text-sm text-base-content/65">{{ $data['definition'] }}</p>
    @endif

    @if (is_string($data['definition'] ?? null))
        <p class="mt-6 text-xs text-base-content/55">{{ $data['definition'] }}</p>
    @endif
@endsection
