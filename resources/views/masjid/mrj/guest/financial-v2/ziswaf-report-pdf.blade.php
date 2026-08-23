<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Dana ZISWAF</title>
    <style>
        @page { margin: 25mm 14mm 18mm; }
        * { box-sizing: border-box; }
        body { color: #18352a; font-family: DejaVu Sans, sans-serif; font-size: 9pt; line-height: 1.52; }
        h1, h2, h3, p { margin: 0; }
        h1 { color: #064e3b; font-size: 21pt; letter-spacing: .3px; }
        h2 { color: #065f46; font-size: 13pt; margin: 18pt 0 7pt; }
        h3 { color: #064e3b; font-size: 11.5pt; line-height: 1.28; margin: 0; }
        .report-meta { color: #567267; font-size: 8.5pt; margin-top: 5pt; }
        .first-page-masthead { border-bottom: 1px solid #a7d7c4; margin: 0 0 10pt; padding: 0 0 7pt; }
        .first-page-masthead strong { color: #064e3b; display: block; font-size: 10pt; }
        .first-page-masthead span { color: #5f7469; display: block; font-size: 8pt; line-height: 1.45; margin-top: 2pt; }
        .hero { border-bottom: 2px solid #047857; margin-bottom: 14pt; padding: 0 0 12pt; }
        .kicker { color: #047857; font-size: 8pt; font-weight: bold; letter-spacing: 1.2px; text-transform: uppercase; }
        .summary { border-collapse: separate; border-spacing: 6pt; margin: 0 -6pt; width: calc(100% + 12pt); }
        .summary td { background: #f0fdf4; border: 1px solid #cce9da; padding: 10pt; vertical-align: top; width: 33.33%; }
        .summary-label { color: #577166; font-size: 7.5pt; font-weight: bold; letter-spacing: .7px; text-transform: uppercase; }
        .summary-value { color: #064e3b; font-size: 14pt; font-weight: bold; line-height: 1.22; margin-top: 4pt; }
        .summary-note { color: #5f7469; font-size: 7.5pt; line-height: 1.42; margin-top: 4pt; }
        table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .data-table { border: 1px solid #d0e6d9; }
        .data-table th { background: #e7f7ed; color: #065f46; font-size: 7pt; letter-spacing: .35px; line-height: 1.35; padding: 7pt 6pt; text-align: left; text-transform: uppercase; vertical-align: middle; }
        .data-table td { border-top: 1px solid #dcece3; line-height: 1.5; overflow-wrap: break-word; padding: 7pt 6pt; vertical-align: top; word-wrap: break-word; }
        .data-table tbody tr:nth-child(even) td { background: #fbfefc; }
        .recap-table th, .recap-table td { font-size: 7.2pt; padding: 6pt 4pt; }
        .movement-table th { font-size: 6.1pt; padding: 6pt 3pt; }
        .movement-table td { font-size: 6.5pt; padding: 6pt 3pt; }
        .amount { text-align: right; white-space: nowrap; }
        .positive { color: #087443; }
        .negative { color: #b42318; }
        .transfer { color: #0c6fa9; }
        .muted { color: #657b70; }
        .section-note { color: #5f7469; font-size: 8pt; line-height: 1.5; margin: 4pt 0 8pt; }
        .page-break { page-break-before: always; }
        .fund-section { border: 1px solid #b9ddca; break-inside: avoid; margin: 14pt 0 0; page-break-inside: avoid; }
        .fund-section-detailed { margin-top: 0; }
        .fund-heading { background: #e7f7ed; border-bottom: 1px solid #b9ddca; display: table; padding: 9pt 10pt; width: 100%; }
        .fund-number, .fund-title { display: table-cell; vertical-align: middle; }
        .fund-number { color: #047857; font-size: 14pt; font-weight: bold; padding-right: 9pt; width: 39pt; }
        .fund-title p { color: #557468; font-size: 7.5pt; line-height: 1.4; margin-top: 2pt; }
        .fund-content { padding: 10pt; }
        .fund-summary { background: #f7fcf8; border: 1px solid #d8e9df; margin: 0 0 10pt; padding: 8pt; }
        .fund-summary span { display: inline-block; line-height: 1.45; margin: 2pt 10pt 2pt 0; }
        .fund-summary b { color: #065f46; }
        .subheading { color: #33735a; font-size: 9pt; font-weight: bold; margin: 11pt 0 5pt; }
        .empty { background: #f8faf9; border: 1px solid #e5ede8; color: #657b70; font-size: 8.5pt; padding: 8pt; }
        .closing-page { page-break-before: always; }
        .closing-header { border-bottom: 1px solid #a7d7c4; margin-bottom: 15pt; padding-bottom: 8pt; }
        .closing-header strong { color: #064e3b; display: block; font-size: 14pt; margin-top: 4pt; }
        .closing-header span { color: #5f7469; display: block; font-size: 8.5pt; }
        .note-box { background: #fffbeb; border: 1px solid #f8e5a5; break-inside: avoid; color: #5f4b18; padding: 10pt; page-break-inside: avoid; }
        .note-box li { margin: 3pt 0; }
        .avoid-break { break-inside: avoid; page-break-inside: avoid; }
    </style>
</head>
<body>
@php
    $rupiah = static function (string $amount): string {
        $negative = str_starts_with($amount, '-');
        $normalized = ltrim($amount, '-');
        [$integer, $decimal] = array_pad(explode('.', $normalized, 2), 2, '00');

        return ($negative ? '- ' : '').'Rp'.number_format((int) $integer, 0, ',', '.').','.$decimal;
    };
    $date = static fn (string $value): string => \Carbon\Carbon::parse($value)->locale('id')->translatedFormat('d F Y');
    $updated = $report['updated_at'] ? \Carbon\Carbon::parse($report['updated_at'])->locale('id')->translatedFormat('d F Y, H:i').' WIB' : 'Belum ada pembaruan posted';
    $summaries = collect($report['funds'])->keyBy('code');
@endphp

<div class="first-page-masthead">
    <strong>Masjid Raudhotul Jannah</strong>
    <span>Taman Cipulir Estate - Laporan Dana ZISWAF</span>
</div>

<section class="hero">
    <p class="kicker">Transparansi keuangan jamaah</p>
    <h1>LAPORAN DANA ZISWAF</h1>
    <p class="report-meta">Masjid Raudhotul Jannah - Taman Cipulir Estate</p>
    <p class="report-meta">Periode {{ $date($report['period_from']) }} s.d. {{ $date($report['as_of']) }} | Terakhir diperbarui {{ $updated }}</p>
</section>

<h2>Ringkasan Saldo</h2>
<table class="summary"><tr>
    <td><p class="summary-label">Total Dana</p><p class="summary-value">{{ $rupiah($report['total_fund_balance']) }}</p><p class="summary-note">Saldo peruntukan Dana ZISWAF.</p></td>
    @forelse ($report['financial_accounts'] as $account)
        <td><p class="summary-label">{{ $account['name'] }}</p><p class="summary-value">{{ $rupiah($account['balance']) }}</p><p class="summary-note">Lokasi penyimpanan dana.</p></td>
    @empty
        <td><p class="summary-label">Rekening / Kas</p><p class="summary-value">Rp0,00</p><p class="summary-note">Belum ada posisi yang dapat ditampilkan.</p></td>
    @endforelse
</tr></table>

<h2>Rekap Dana</h2>
<p class="section-note">Pemasukan dan pengeluaran adalah mutasi dalam periode laporan. Pemindahan antar-Dana ditampilkan terpisah dan bukan pemasukan atau pengeluaran baru.</p>
<table class="data-table recap-table">
    <colgroup><col style="width:27%"><col style="width:17%"><col style="width:17%"><col style="width:22%"><col style="width:17%"></colgroup>
    <thead><tr><th>Dana</th><th class="amount">Pemasukan</th><th class="amount">Pengeluaran</th><th class="amount">Pemindahan Dana</th><th class="amount">Saldo</th></tr></thead>
    <tbody>
    @foreach ($report['funds'] as $fund)
        <tr>
            <td><strong>{{ $fund['name'] }}</strong></td>
            <td class="amount positive">{{ $rupiah($fund['receipts']) }}</td>
            <td class="amount negative">{{ $rupiah($fund['expenses']) }}</td>
            <td class="amount transfer">{{ $fund['transfer_net'] === '0.00' ? '—' : (str_starts_with($fund['transfer_net'], '-') ? 'Keluar ' : 'Masuk ').$rupiah(ltrim($fund['transfer_net'], '-')) }}</td>
            <td class="amount"><strong>{{ $rupiah($fund['balance']) }}</strong></td>
        </tr>
    @endforeach
    </tbody>
</table>

@if ($report['fund_transfers'] !== [])
    <section class="avoid-break">
        <h2>Pemindahan Dana</h2>
        <p class="section-note">Setiap peristiwa hanya ditampilkan satu kali. Cash Tromol Yatim merupakan komposisi rekening Dana Dhuafa &amp; Anak Yatim dan tidak ditampilkan sebagai pemindahan Dana.</p>
        <table class="data-table">
            <colgroup><col style="width:23%"><col style="width:19%"><col style="width:19%"><col style="width:25%"><col style="width:14%"></colgroup>
            <thead><tr><th>Kategori</th><th>Dari</th><th>Ke</th><th>Keterangan</th><th class="amount">Nominal</th></tr></thead>
            <tbody>@foreach ($report['fund_transfers'] as $transfer)<tr><td>{{ $transfer['category'] }}</td><td>{{ $transfer['from'] }}</td><td>{{ $transfer['to'] }}</td><td>{{ $transfer['description'] }}</td><td class="amount transfer">{{ $rupiah($transfer['amount']) }}</td></tr>@endforeach</tbody>
        </table>
    </section>
@endif

<h2>Rincian Per Dana</h2>
<p class="section-note">Riwayat sumber menjelaskan posisi historis sebelum Financial V2. Mutasi resmi hanya berasal dari Posted General Ledger Financial V2.</p>
@foreach ($report['fund_details'] as $detail)
    @php $summary = $summaries->get($detail['code']); @endphp
    @if ($summary && ($detail['source_entries'] !== [] || $detail['official_entries'] !== []))
        <section @class(['fund-section', 'fund-section-detailed' => count($detail['source_entries']) + count($detail['official_entries']) > 2])>
            <div class="fund-heading">
                <span class="fund-number">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                <div class="fund-title"><h3>{{ $detail['name'] }}</h3><p>Riwayat Dana, pemindahan, dan saldo peruntukan.</p></div>
            </div>
            <div class="fund-content">
                <div class="fund-summary">
                    <span><b>Pemasukan:</b> {{ $rupiah($summary['receipts']) }}</span>
                    <span><b>Pengeluaran:</b> {{ $rupiah($summary['expenses']) }}</span>
                    <span><b>Pemindahan:</b> {{ $rupiah($summary['transfer_net']) }}</span>
                    <span><b>Saldo:</b> {{ $rupiah($summary['balance']) }}</span>
                </div>
                @if ($summary['account_composition'] !== [])
                    <p class="section-note"><b>Komposisi rekening:</b> {{ collect($summary['account_composition'])->map(fn (array $account): string => $account['name'].' '.$rupiah($account['balance']))->join(' · ') }}</p>
                @endif

            @if ($detail['source_entries'] !== [])
                <p class="subheading">Riwayat sumber sebelum Financial V2</p>
                <table class="data-table movement-table">
                    <colgroup><col style="width:11%"><col style="width:29%"><col style="width:12%"><col style="width:12%"><col style="width:12%"><col style="width:12%"><col style="width:12%"></colgroup>
                    <thead><tr><th>Tanggal</th><th>Uraian</th><th>Jenis</th><th class="amount">Pemasukan</th><th class="amount">Pengeluaran</th><th class="amount">Pemindahan</th><th class="amount">Saldo</th></tr></thead>
                    <tbody>@foreach ($detail['source_entries'] as $entry)<tr>
                        <td>{{ $entry['date'] }}</td><td>{{ $entry['description'] }}</td><td>{{ $entry['kind'] === 'expense' ? 'Pengeluaran' : ($entry['kind'] === 'receipt' ? 'Pemasukan' : 'Saldo awal') }}</td>
                        <td class="amount positive">{{ in_array($entry['kind'], ['receipt', 'opening'], true) ? $rupiah($entry['amount']) : '-' }}</td>
                        <td class="amount negative">{{ $entry['kind'] === 'expense' ? $rupiah($entry['amount']) : '-' }}</td><td class="amount">-</td><td class="amount">-</td>
                    </tr>@endforeach</tbody>
                </table>
            @endif

            @if ($detail['official_entries'] !== [])
                <p class="subheading">Mutasi resmi Financial V2</p>
                <table class="data-table movement-table">
                    <colgroup><col style="width:11%"><col style="width:29%"><col style="width:12%"><col style="width:12%"><col style="width:12%"><col style="width:12%"><col style="width:12%"></colgroup>
                    <thead><tr><th>Tanggal</th><th>Uraian</th><th>Jenis</th><th class="amount">Pemasukan</th><th class="amount">Pengeluaran</th><th class="amount">Pemindahan</th><th class="amount">Saldo</th></tr></thead>
                    <tbody>@foreach ($detail['official_entries'] as $entry)<tr>
                        <td>{{ $date($entry['date']) }}</td><td>{{ $entry['description'] }}</td>
                        <td>{{ $entry['kind'] === 'receipt' ? 'Pemasukan' : ($entry['kind'] === 'expense' ? 'Pengeluaran' : ($entry['kind'] === 'transfer' ? 'Pemindahan Dana' : 'Saldo Awal')) }}</td>
                        <td class="amount positive">{{ in_array($entry['kind'], ['receipt', 'opening'], true) ? $rupiah($entry['amount']) : '-' }}</td>
                        <td class="amount negative">{{ $entry['kind'] === 'expense' ? $rupiah($entry['amount']) : '-' }}</td>
                        <td class="amount transfer">{{ $entry['kind'] === 'transfer' ? (str_starts_with($entry['delta'], '-') ? '- ' : '') . $rupiah($entry['amount']) : '-' }}</td>
                        <td class="amount">{{ $rupiah($entry['running_balance']) }}</td>
                    </tr>@endforeach</tbody>
                </table>
            @endif
            </div>
        </section>
        @if (! $loop->last)<div class="page-break"></div>@endif
    @endif
@endforeach

<section class="closing-page">
    <div class="closing-header">
        <span>Transparansi keuangan jamaah</span>
        <strong>Masjid Raudhotul Jannah</strong>
        <span>Taman Cipulir Estate - Laporan Dana ZISWAF</span>
    </div>
    <div class="note-box avoid-break">
        <p class="subheading">Catatan Transparansi</p>
        <ul>
            <li>Laporan disusun dari pencatatan keuangan resmi dan posisi Dana yang telah ditetapkan.</li>
            <li>Dana dikelola sesuai peruntukan masing-masing.</li>
            <li>Pemindahan antar-Dana tidak dianggap sebagai pemasukan atau pengeluaran baru.</li>
            <li>Saldo rekening/kas menunjukkan lokasi penyimpanan dana, sedangkan Dana menunjukkan peruntukannya.</li>
            <li>Rincian lebih lanjut tersedia pada sistem Masjid Raudhotul Jannah.</li>
        </ul>
    </div>
</section>
</body>
</html>
