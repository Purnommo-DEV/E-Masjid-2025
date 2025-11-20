@extends('masjid.master')
@section('title', 'Laporan')
@section('content')
<div class="card">
    <div class="card-header bg-dark text-white d-flex justify-content-between">
        <h4>Laporan Keuangan</h4>
        <a href="{{ route('admin.keuangan.laporan.pdf', ['start' => $start, 'end' => $end]) }}" class="btn btn-danger btn-sm">Export PDF</a>
    </div>
    <div class="card-body">
        <form class="row g-3 mb-4">
            <div class="col-md-4"><input type="date" name="start" class="form-control" value="{{ $start }}"></div>
            <div class="col-md-4"><input type="date" name="end" class="form-control" value="{{ $end }}"></div>
            <div class="col-md-4"><button class="btn btn-primary w-100">Filter</button></div>
        </form>

        <div class="row text-center">
            <div class="col-md-3"><div class="card bg-info text-white"><div class="card-body"><h6>Saldo Awal</h6><h3>Rp {{ number_format($data['saldoAwal'], 0, ',', '.') }}</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-success text-white"><div class="card-body"><h6>Pemasukan</h6><h3>Rp {{ number_format($data['pemasukan'], 0, ',', '.') }}</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-danger text-white"><div class="card-body"><h6>Pengeluaran</h6><h3>Rp {{ number_format($data['pengeluaran'], 0, ',', '.') }}</h3></div></div></div>
            <div class="col-md-3"><div class="card bg-primary text-white"><div class="card-body"><h6>Saldo Akhir</h6><h3>Rp {{ number_format($data['saldoAkhir'], 0, ',', '.') }}</h3></div></div></div>
        </div>

        <div class="mt-4">
            <h5>Detail Transaksi</h5>
            <table class="table table-bordered">
                <thead><tr><th>Tanggal</th><th>Kategori</th><th>Jumlah</th><th>Deskripsi</th></tr></thead>
                <tbody>
                    @foreach($transaksi as $t)
                    <tr>
                        <td>{{ $t->tanggal->format('d/m/Y') }}</td>
                        <td>{{ $t->kategori->nama }}</td>
                        <td>Rp {{ number_format($t->jumlah, 0, ',', '.') }}</td>
                        <td><small>{{ $t->deskripsi }}</small></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection