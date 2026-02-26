<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kwitansi Zakat - {{ $transaksi->nomor_kwitansi }}</title>
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; color: #333; background: white; }
        .container { max-width: 800px; margin: 40px auto; padding: 30px; border: 2px solid #10b981; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .header { position: relative; text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #10b981; }
        .logo { position: absolute; top: 0; right: 0; width: 120px; height: 120px; object-fit: contain; }
        .kop { font-size: 28px; font-weight: bold; color: #065f46; margin: 0; }
        .subkop { font-size: 14px; color: #555; margin: 5px 0; }
        .info { margin-bottom: 30px; }
        .info table { width: 100%; border-collapse: collapse; }
        .info td { padding: 10px 8px; border-bottom: 1px solid #eee; vertical-align: top; }
        .info td:first-child { width: 180px; font-weight: bold; color: #065f46; }
        .detail { margin-bottom: 30px; }
        .detail table { width: 100%; border-collapse: collapse; }
        .detail th, .detail td { padding: 12px 10px; border: 1px solid #ddd; text-align: left; }
        .detail th { background: #ecfdf5; color: #065f46; font-weight: bold; }
        .total { font-size: 20px; font-weight: bold; text-align: right; margin: 20px 0; color: #065f46; padding: 10px 0; border-top: 2px dashed #10b981; }
        .ttd { margin-top: 60px; display: flex; justify-content: flex-end; }
        .ttd div { width: 220px; text-align: center; border-top: 1px solid #333; padding-top: 50px; margin-left: 40px; }
        @media print {
            body { margin: 0; }
            .container { box-shadow: none; border: 1px solid #ddd; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="{{ asset('images/logo-masjid.png') }}" alt="Logo Masjid Raudhotul Jannah" class="logo">
            <h1 class="kop">MASJID RAUDHOTUL JANNAH</h1>
            <p class="subkop">Jl. Raya Contoh No. 123, Kelurahan Sukamaju, Kecamatan Cikarang, Kabupaten Bekasi</p>
            <p class="subkop">Telp: (021) 1234 5678 | Email: info@raudhotuljannah.or.id</p>
        </div>

        <div class="info">
            <table>
                <tr>
                    <td>No. Kwitansi</td>
                    <td>: {{ $transaksi->nomor_kwitansi }}</td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td>: {{ $transaksi->tanggal->format('d F Y') }}</td>
                </tr>
                <tr>
                    <td>Diterima dari</td>
                    <td>: {{ $transaksi->muzakki->nama }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>: {{ $transaksi->muzakki->alamat ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Metode Bayar</td>
                    <td>: {{ ucfirst($transaksi->metode_bayar) }}</td>
                </tr>
            </table>
        </div>

        <div class="detail">
            <table>
                <thead>
                    <tr>
                        <th>Jenis Zakat / Dana</th>
                        <th>Nominal (Rp)</th>
                        <th>Jiwa / Hari</th>
                        <th>Beras</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transaksi->details as $detail)
                    <tr>
                        <td>{{ ucwords(str_replace('_', ' ', $detail->jenis)) }}</td>
                        <td style="text-align:right">{{ number_format($detail->nominal, 0, ',', '.') }}</td>
                        <td style="text-align:center">{{ $detail->jiwa ?? '-' }}</td>
                        <td style="text-align:center">{{ $detail->jumlah_beras ? $detail->jumlah_beras . ' ' . $detail->satuan_beras : '-' }}</td>
                        <td>{{ $detail->keterangan_detail ?? '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="total">
            TOTAL NOMINAL: Rp {{ number_format($transaksi->total_nominal, 0, ',', '.') }}
        </div>

        <div class="ttd">
            <div>
                Bendahara<br><br><br>
                (..................................)
            </div>
            <div>
                Ketua<br><br><br>
                (..................................)
            </div>
        </div>
    </div>

    <!-- Tombol cetak (hilang saat print) -->
    <div class="text-center mt-6 no-print">
        <button onclick="window.print()" class="btn btn-success gap-2">
            <i class="fas fa-print"></i> Cetak Kwitansi
        </button>
    </div>
</body>
</html>