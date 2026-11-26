<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Detail Penyaluran ZISWAF</title>
    <style>
        body { background:#f8faf9; color:#18352a; font:16px/1.6 system-ui,sans-serif; margin:0; padding:24px; }
        main { max-width:640px; margin:32px auto; background:white; border:1px solid #dce7e1; border-radius:16px; padding:28px; }
        h1 { color:#065f46; font-size:24px; margin-top:0; } h2 { font-size:20px; }
        dt { color:#65756e; } dd { margin:0 0 18px; font-weight:600; overflow-wrap:anywhere; }
        .note { color:#65756e; font-size:14px; } a { color:#047857; }
    </style>
</head>
<body><main>
    <h1>Detail Penyaluran ZISWAF</h1>
    <h2>{{ $summary['program'] }}</h2>
    <dl>
        <dt>Periode</dt><dd>{{ $summary['period'] }}</dd>
        <dt>Tanggal penyaluran</dt><dd>{{ \Carbon\CarbonImmutable::parse($summary['from'])->format('d/m/Y') }} — {{ \Carbon\CarbonImmutable::parse($summary['through'])->format('d/m/Y') }}</dd>
        <dt>Jumlah penerima</dt><dd>{{ $summary['recipient_count'] }}</dd>
        <dt>Nominal penyaluran tercatat</dt><dd>{{ \App\Support\ZiswafAmount::rupiah($summary['actual_amount'], true) }}</dd>
    </dl>
    <p class="note">Tampilan baca saja untuk satu penyaluran. Nama, alamat, nomor identitas, dan catatan pribadi penerima tidak dibagikan melalui tautan ini.</p>
    <a href="{{ route('public.ziswaf.index') }}">Lihat Laporan ZISWAF</a>
</main></body></html>
