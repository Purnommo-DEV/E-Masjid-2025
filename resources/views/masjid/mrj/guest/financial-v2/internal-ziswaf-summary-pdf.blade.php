<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan ZISWAF {{ $from }} sampai {{ $through }}</title>
    <style>
        @page { margin: 30px 36px 55px; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #24352e; font-family: "DejaVu Sans", sans-serif; font-size: 8.5px; line-height: 1.4; }
        h1, h2, h3, p { margin: 0; }
        .header { border-bottom: 3px solid #176b4b; padding-bottom: 13px; }
        .header-table, .metric-table { width: 100%; border-collapse: collapse; }
        .logo { width: 54px; height: 54px; }
        .brand { padding-left: 11px; vertical-align: middle; }
        .brand h1 { color: #0b5137; font-size: 17px; letter-spacing: .2px; }
        .brand p { color: #65756e; font-size: 8px; margin-top: 3px; }
        .period { width: 175px; text-align: right; vertical-align: middle; }
        .period strong { color: #0b5137; font-size: 9px; }
        .period span { display: block; color: #65756e; margin-top: 3px; }
        .intro { background: #eff8f3; border: 1px solid #d5eadf; margin-top: 13px; padding: 9px 11px; }
        .section { margin-top: 16px; page-break-inside: auto; }
        .section-title { color: #0b5137; font-size: 12px; margin-bottom: 7px; }
        .section-note { color: #65756e; font-size: 7.5px; margin: -4px 0 7px; }
        .metric { width: 32%; border: 1px solid #dce7e1; padding: 9px; vertical-align: top; }
        .metric-gap { width: 2%; }
        .metric-label { color: #65756e; font-size: 7px; text-transform: uppercase; letter-spacing: .5px; }
        .metric-value { color: #123f2f; font-size: 13px; font-weight: bold; margin-top: 4px; }
        .metric-help { color: #6b7a74; font-size: 7px; margin-top: 4px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data th { background: #176b4b; color: white; font-size: 7px; padding: 6px 5px; text-align: left; text-transform: uppercase; }
        table.data td { border-bottom: 1px solid #e2e9e5; padding: 6px 5px; vertical-align: top; }
        table.data tbody tr:nth-child(even) td { background: #f7faf8; }
        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .money { white-space: nowrap; font-family: "DejaVu Sans Mono", monospace; }
        .muted { color: #6b7a74; }
        .strong { font-weight: bold; color: #123f2f; }
        .empty { border: 1px solid #e2e9e5; background: #f7faf8; color: #65756e; padding: 10px; text-align: center; }
        .page-break { page-break-before: always; }
        .confidential { color: #8a5b13; font-size: 7px; margin-top: 10px; }
    </style>
</head>
<body>
@php
    $rupiah = static function ($value): string {
        $amount = (string) $value;
        $negative = str_starts_with($amount, '-');
        $normalized = ltrim($amount, '-');
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '00');
        return ($negative ? '- ' : '').'Rp'.number_format((int) $integer, 0, ',', '.').','.str_pad(substr($decimal, 0, 2), 2, '0');
    };
    $date = static fn ($value): string => \Carbon\CarbonImmutable::parse($value)->format('d/m/Y');
    $selectedFund = ($filters['fund_id'] ?? null)
        ? data_get(collect($report['funds'])->firstWhere('fund_id', $filters['fund_id']), 'name', 'Dana terpilih')
        : 'Seluruh Dana ZISWAF';
    $filterDescriptions = array_filter([
        $selectedFund,
        ($filters['type'] ?? null) ? 'Jenis transaksi: '.$filters['type'] : null,
        ($filters['program_id'] ?? null) ? 'Program terpilih' : null,
        ($filters['category_id'] ?? null) ? 'Kategori terpilih' : null,
        'Status: POSTED',
    ]);
@endphp

<div class="header">
    <table class="header-table">
        <tr>
            <td style="width:58px">@if($logoDataUri)<img class="logo" src="{{ $logoDataUri }}" alt="Logo Masjid Raudhotul Jannah">@endif</td>
            <td class="brand"><h1>Ringkasan Monitoring ZISWAF</h1><p>{{ $entity->name }} · Financial V2</p></td>
            <td class="period"><strong>{{ $date($from) }} — {{ $date($through) }}</strong><span>Saldo terkini per {{ $date($latest) }}</span><span>Dibuat {{ $generatedAt->format('d/m/Y H:i') }} WIB</span></td>
        </tr>
    </table>
</div>

<div class="intro">
    <strong>Cakupan laporan:</strong> {{ implode(' · ', $filterDescriptions) }}.<br>
    Ringkasan ini bersumber dari transaksi Financial V2 berstatus POSTED dan dana dalam cakupan resmi ZISWAF.
</div>

<div class="section">
    <h2 class="section-title">Posisi keuangan — Ringkasan Saldo Saat Ini</h2>
    <p class="section-note">Saldo dana menunjukkan peruntukan dana; saldo rekening/kas menunjukkan lokasi penyimpanannya.</p>
    <table class="metric-table"><tr>
        <td class="metric"><div class="metric-label">Total Dana ZISWAF</div><div class="metric-value">{{ $rupiah($positionReport['total_fund_balance']) }}</div><div class="metric-help">Total dana yang dikelola sesuai peruntukan.</div></td>
        <td class="metric-gap"></td>
        <td class="metric"><div class="metric-label">Total Pemasukan Periode</div><div class="metric-value">{{ $rupiah($report['summary']['receipts']) }}</div><div class="metric-help">Mutasi masuk sesuai filter laporan.</div></td>
        <td class="metric-gap"></td>
        <td class="metric"><div class="metric-label">Total Pengeluaran Periode</div><div class="metric-value">{{ $rupiah($report['summary']['expenses']) }}</div><div class="metric-help">Mutasi keluar aktual sesuai filter laporan.</div></td>
    </tr></table>
    @if($positionReport['financial_accounts'] !== [])
        <table class="data" style="margin-top:8px"><thead><tr><th>Rekening / Kas</th><th class="right">Saldo Saat Ini</th></tr></thead><tbody>
        @foreach($positionReport['financial_accounts'] as $account)<tr><td>{{ $account['name'] }}</td><td class="right money strong">{{ $rupiah($account['balance']) }}</td></tr>@endforeach
        </tbody></table>
    @endif
</div>

<div class="section">
    <h2 class="section-title">Rincian Pemasukan &amp; Pengeluaran Per Dana</h2>
    <table class="data"><thead><tr><th>Dana</th><th>Kode</th><th class="right">Pemasukan</th><th class="right">Pengeluaran</th><th class="right">Saldo Akhir Periode</th></tr></thead><tbody>
    @forelse($report['funds'] as $fund)
        <tr><td class="strong">{{ $fund['name'] }}</td><td>{{ $fund['code'] }}</td><td class="right money">{{ $rupiah($fund['receipts']) }}</td><td class="right money">{{ $rupiah($fund['expenses']) }}</td><td class="right money strong">{{ $rupiah($fund['fund_balance']) }}</td></tr>
    @empty
        <tr><td colspan="5" class="center muted">Tidak ada dana dalam cakupan filter.</td></tr>
    @endforelse
    </tbody></table>
</div>

<div class="section">
    <h2 class="section-title">Allocation dibanding Realisasi</h2>
    <table class="metric-table"><tr>
        <td class="metric"><div class="metric-label">Total Allocation</div><div class="metric-value">{{ $rupiah($allocation) }}</div></td><td class="metric-gap"></td>
        <td class="metric"><div class="metric-label">Total Realisasi</div><div class="metric-value">{{ $rupiah($realization) }}</div></td><td class="metric-gap"></td>
        <td class="metric"><div class="metric-label">Sisa Allocation</div><div class="metric-value">{{ $rupiah($remainingAllocation) }}</div></td>
    </tr></table>
    <table class="data" style="margin-top:8px"><thead><tr><th>Program</th><th class="right">Allocation</th><th class="right">Realisasi</th><th class="right">Pengeluaran Aktual</th><th class="right">Sisa</th></tr></thead><tbody>
    @forelse($report['programs'] as $program)
        <tr><td class="strong">{{ $program['name'] }}</td><td class="right money">{{ $rupiah($program['allocation']) }}</td><td class="right money">{{ $rupiah($program['realization']) }}</td><td class="right money">{{ $rupiah($program['actual_expense']) }}</td><td class="right money">{{ $rupiah($program['remaining']) }}</td></tr>
    @empty
        <tr><td colspan="5" class="center muted">Tidak ada allocation dalam cakupan periode.</td></tr>
    @endforelse
    </tbody></table>
</div>

<div class="section page-break">
    <h2 class="section-title">Pergerakan dalam Periode Laporan</h2>
    <p class="section-note">Urutan mengikuti tanggal akuntansi dan urutan posting resmi. Saldo awal {{ $rupiah($runningBalance['opening_balance']) }} · saldo akhir {{ $rupiah($runningBalance['closing_balance']) }}.</p>
    <table class="data"><thead><tr><th style="width:11%">Tanggal</th><th>Uraian</th><th style="width:18%">Dana</th><th class="right">Masuk</th><th class="right">Keluar</th><th class="right">Saldo</th></tr></thead><tbody>
        <tr><td>{{ $date($from) }}</td><td class="strong">Saldo Awal Periode</td><td>{{ $selectedFund }}</td><td class="right">—</td><td class="right">—</td><td class="right money strong">{{ $rupiah($runningBalance['opening_balance']) }}</td></tr>
        @foreach($runningBalance['rows'] as $row)
            <tr><td>{{ $date($row['date']) }}</td><td>{{ $row['description'] }}<br><span class="muted">Urutan posting {{ $row['posting_sequence'] }}</span></td><td>{{ $row['fund'] ?: '—' }}</td><td class="right money">{{ $row['in'] !== '0.00' ? $rupiah($row['in']) : '—' }}</td><td class="right money">{{ $row['out'] !== '0.00' ? $rupiah($row['out']) : '—' }}</td><td class="right money strong">{{ $rupiah($row['running_balance']) }}</td></tr>
        @endforeach
    </tbody></table>
</div>

<div class="section">
    <h2 class="section-title">Ringkasan Penyaluran</h2>
    <p class="section-note">Disajikan secara agregat tanpa nama, alamat, nomor identitas, atau data pribadi penerima.</p>
    <table class="metric-table"><tr>
        <td class="metric"><div class="metric-label">Kegiatan Penyaluran</div><div class="metric-value">{{ $report['distributions']['distribution_events'] }}</div></td><td class="metric-gap"></td>
        <td class="metric"><div class="metric-label">Penerima Unik</div><div class="metric-value">{{ $report['distributions']['unique_beneficiaries'] }}</div></td><td class="metric-gap"></td>
        <td class="metric"><div class="metric-label">Program Tercakup</div><div class="metric-value">{{ count($report['distributions']['programs']) }}</div></td>
    </tr></table>
    <table class="data" style="margin-top:8px"><thead><tr><th>Program</th><th class="center">Kegiatan</th><th class="center">Penerima Unik</th><th class="right">Total Operasional</th><th class="right">Actual Tertaut</th></tr></thead><tbody>
    @forelse($report['distributions']['programs'] as $program)
        <tr><td class="strong">{{ $program['program_name'] }}</td><td class="center">{{ $program['distribution_events'] }}</td><td class="center">{{ $program['unique_beneficiaries'] }}</td><td class="right money">{{ $rupiah($program['operational_total']) }}</td><td class="right money">{{ $rupiah($program['actual_amount']) }}</td></tr>
    @empty
        <tr><td colspan="5" class="center muted">Belum ada penyaluran dalam cakupan filter.</td></tr>
    @endforelse
    </tbody></table>
    <p class="confidential">Dokumen internal DKM. Actual bersumber dari Financial V2 posted ledger. Total operasional bukan tambahan pengeluaran finansial.</p>
</div>
</body>
</html>
