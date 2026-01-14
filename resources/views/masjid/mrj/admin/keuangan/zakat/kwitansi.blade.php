<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Zakat - {{ $transaksi->nomor_kwitansi }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: white; font-size: 14px; line-height: 1.5; }
        .kwitansi { max-width: 900px; margin: 0 auto; border: 4px double #000; padding: 35px; }
        .header { text-align: center; border-bottom: 3px double #000; padding-bottom: 15px; margin-bottom: 25px; }
        .logo { width: 90px; margin-bottom: 8px; }
        .judul { font-size: 23px; font-weight: bold; margin: 4px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        td { padding: 5px 0; vertical-align: top; }
        .garis { border-bottom: 1px dotted #000; display: inline-block; min-width: 380px; }
        .table-zakat { width: 100%; border: 2px solid #000; margin: 30px 0; }
        .table-zakat th, .table-zakat td { border: 1px solid #000; padding: 12px; text-align: center; font-size: 13.5px; }
        .table-zakat th { background: #f0f0f0; font-weight: bold; }
        .total { text-align: right; font-size: 21px; font-weight: bold; margin: 25px 0; padding-right: 40px; }
        .ttd { margin-top: 80px; display: flex; justify-content: space-between; }
        .ttd-box { text-align: center; width: 48%; }
        .line { border-top: 1px solid #000; width: 240px; margin: 30px auto 8px; }
        .arabic { font-family: "Traditional Arabic", "Times New Roman", serif; font-size: 28px; text-align: center; margin: 30px 0; direction: rtl; }
        @media print { body { padding: 10px; } .no-print { display: none; } }
    </style>
</head>
<body onload="window.print()">

<div class="kwitansi">

    <!-- HEADER -->
    <div class="header">
        <img src="{{ asset('assets/img/logo-masjid.png') }}" class="logo" alt="Logo">
        <div class="judul">Panitia Amil Zakat, Infaq, Sodaqoh (ZIS)</div>
        <div class="judul">MASJID : {{ strtoupper(config('app.name')) }}</div>
        <div>Alamat : {{ auth()->user()->masjid?->alamat ?? 'Jl. Raya Masjid No. 123, Kelurahan Sukamaju' }}</div>
    </div>

    <!-- NOMOR & TANGGAL -->
    <table>
        <tr>
            <td width="60%"><strong>KWITANSI / TANDA TERIMA</strong></td>
            <td><strong>Nomor : {{ $transaksi->nomor_kwitansi }}</strong></td>
            <td><strong>Tanggal : {{ $transaksi->tanggal->format('d-m-Y') }}</strong></td>
        </tr>
    </table>

    <!-- JENIS ZAKAT -->
    <div style="margin:25px 0;">
        Sudah terima 
        ◯ Zakat Fitrah @if(in_array('zakat_fitrah', $transaksi->jenis_zakat)) ✓ @endif 
        ◯ Zakat Maal @if(in_array('zakat_maal', $transaksi->jenis_zakat)) ✓ @endif 
        ◯ Fidyah @if(in_array('fidyah', $transaksi->jenis_zakat)) ✓ @endif 
        ◯ Infaq @if(in_array('infaq', $transaksi->jenis_zakat)) ✓ @endif 
        ◯ Sodaqoh @if(in_array('shodaqoh', $transaksi->jenis_zakat)) ✓ @endif 
        dari :
    </div>

    <!-- MUZAKKI UTAMA & ALAMAT -->
    <table>
        <tr>
            <td width="15%">Nama Muzakki</td>
            <td width="85%">: {{ $transaksi->muzakki_utama }}</td>
        </tr>
        <tr>
            <td>Alamat</td>
            <td>: <span class="garis">{{ $transaksi->keterangan ?: '....................................................................................' }}</span></td>
        </tr>
    </table>

    <!-- RINCIAN ZAKAT FITRAH -->
    @if(in_array('zakat_fitrah', $transaksi->jenis_zakat))
    <div style="margin:25px 0;">
        Dengan rincian sebagai berikut :<br>
        1. Zakat Fitrah untuk : <strong>{{ $transaksi->total_jiwa }} Jiwa</strong>, berupa :<br>
          A. Beras : <strong>{{ $transaksi->satuan_beras ?? '____' }}</strong><br>
          B. Uang : Rp <strong>{{ number_format($transaksi->jumlah, 0, ',', '.') }}</strong>
    </div>

    <!-- TABEL NAMA KELUARGA — PERSIS GAMBAR! -->
    <table class="table-zakat">
        <thead>
            <tr>
                <th width="10%">No.</th>
                <th width="55%">Nama</th>
                <th width="35%">Uang (Rp) / Beras (L/Kg)</th>
            </tr>
        </thead>
        <tbody>
            @php
                // Pastikan selalu dapat array
                $keluarga = is_array($transaksi->daftar_keluarga)
                    ? $transaksi->daftar_keluarga
                    : (json_decode($transaksi->daftar_keluarga ?? '[]', true) ?: []);

                $jumlahKeluarga = count($keluarga);
                $isZakatFitrah = in_array('zakat_fitrah', $transaksi->jenis_zakat ?? []);
            @endphp

            @foreach($keluarga as $i => $item)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td style="text-align:left; padding-left:20px;">
                        {{ $item['nama'] ?? '' }}
                    </td>
                    <td>
                        @if($isZakatFitrah)
                            {{ $item['jiwa'] ?? 0 }} Jiwa
                        @else
                            @php
                                $perOrang = $jumlahKeluarga > 0
                                    ? $transaksi->jumlah / $jumlahKeluarga
                                    : 0;
                            @endphp
                            Rp {{ number_format($perOrang, 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
            @endforeach

            @for($i = $jumlahKeluarga; $i < 5; $i++)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td height="45"></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align:center; font-weight:bold; font-size:15px;">TOTAL</td>
                <td style="font-weight:bold; font-size:15px;">
                    Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}
                </td>
            </tr>
        </tfoot>
    </table>
    @else
        <!-- Zakat Maal / Fidyah / Infaq → tidak tampilkan tabel jiwa -->
        <p>Telah menerima {{ implode(', ', array_map(fn($j)=>ucwords(str_replace('_',' ',$j)), $transaksi->jenis_zakat)) }}
        sebesar Rp {{ number_format($transaksi->jumlah,0,',','.') }}</p>
    @endif


    <div style="font-size:12px; margin:10px 0;">
        * Keterangan : Nilai konversi beras ke Rupiah (sesuai dengan harga beras yang di konsumsi)
    </div>

    <!-- ZAKAT MAAL / DLL -->
    <div style="margin:35px 0; text-align:center; font-weight:bold; font-size:19px;">
        2. Zakat Maal/Fidyah/Infaq/Sodaqoh sebesar : Rp {{ number_format($transaksi->jumlah, 0, ',', '.') }}<br>
        ({{ ucwords(terbilang($transaksi->jumlah)) }} Rupiah)
    </div>

    <!-- DOA ARAB -->
    <div class="arabic">
        اجْرِكَ اللّٰهُ فِيْمَا اَعْطَيْتَ وَبَارَكَ لَكَ فِيْمَا اَبْقَيْتَ وَجَعَلَهُ لَكَ طَهُوْرًا
    </div>
    <div style="text-align:center; margin:15px 0;">
        <em>Aajarakallahu fiimaa a’thoita, wa baaraka laka fiimaa abqoita, waja’alahu laka thohuuron</em>
    </div>

    <!-- TANDA TANGAN -->
    <div class="ttd">
        <div class="ttd-box">
            <p>Amil ZIS</p>
            <div class="line"></div>
            <p><strong>(_________________________)</strong></p>
        </div>
        <div class="ttd-box">
            <p>Muzakki</p>
            <div class="line"></div>
            <p><strong>({{ $transaksi->muzakki_utama }})</strong></p>
        </div>
    </div>

    <!-- TOMBOL PRINT (hanya muncul di layar) -->
    <div class="no-print text-center mt-5">
        <button onclick="window.print()" class="btn btn-success btn-lg">Cetak Kwitansi</button>
        <a href="{{ route('admin.keuangan.zakat.index') }}" class="btn btn-secondary btn-lg">Kembali</a>
    </div>

</div>
</body>
</html>