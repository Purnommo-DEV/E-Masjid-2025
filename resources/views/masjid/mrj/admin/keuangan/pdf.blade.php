<!-- resources/views/admin/keuangan/pdf.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: Arial; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <h2>LAPORAN KEUANGAN MASJID</h2>
    <p>Periode: {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>

    <table>
        <tr><th>Saldo Awal</th><td class="text-right">Rp {{ number_format($data['saldoAwal'], 0, ',', '.') }}</td></tr>
        <tr><th>Pemasukan</th><td class="text-right">Rp {{ number_format($data['pemasukan'], 0, ',', '.') }}</td></tr>
        <tr><th>Pengeluaran</th><td class="text-right">Rp {{ number_format($data['pengeluaran'], 0, ',', '.') }}</td></tr>
        <tr><th>Saldo Akhir</th><td class="text-right">Rp {{ number_format($data['saldoAkhir'], 0, ',', '.') }}</td></tr>
    </table>

    <h3>Detail Transaksi</h3>
    <table>
        <thead><tr><th>Tanggal</th><th>Kategori</th><th>Jumlah</th><th>Deskripsi</th></tr></thead>
        <tbody>
            @foreach($transaksi as $t)
            <tr>
                <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                <td>{{ $t->kategori->nama }}</td>
                <td class="text-right">Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                <td>{{ $t->deskripsi }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 50px;">
        <p>Mengetahui,</p>
        <br><br>
        <p>____________________</p>
        <p>Ketua Takmir</p>
    </div>
</body>
</html>