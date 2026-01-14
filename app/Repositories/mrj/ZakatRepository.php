<?php

namespace App\Repositories\mrj;
use App\Models\ZakatTransaksi;
use App\Interfaces\{ZakatRepositoryInterface, JurnalRepositoryInterface};
use Yajra\DataTables\Facades\DataTables;

class ZakatRepository implements ZakatRepositoryInterface
{
    public function __construct(protected JurnalRepositoryInterface $jurnal) {}

    public function terima(array $data, $request)
    {
        $data['tipe'] = 'penerimaan';
        $data['created_by'] = auth()->id();
        $data['total_jiwa'] = collect($data['daftar_keluarga'])->sum('jiwa');
        
        $transaksi = ZakatTransaksi::create($data);

        if ($request->hasFile('bukti')) {
            $transaksi->addMedia($request->file('bukti'))->toMediaCollection('bukti_zakat');
        }

        // LANGSUNG PAKAI JURNAL REPOSITORY — RAPI & KONSISTEN!
        $this->jurnal->penerimaanZakat(
            tanggal: $transaksi->tanggal,
            jumlah: $transaksi->jumlah,
            akunLiabilitasId: $transaksi->akun_id,
            muzakki: $transaksi->muzakki_utama,
            reference: $transaksi
        );

        return $transaksi;
    }

    public function salurkan(array $data, $request)
    {
        $akun = AkunKeuangan::findOrFail($data['akun_id']);
        if ($data['jumlah'] > $akun->saldo()) {
            throw new \Exception('Saldo zakat tidak mencukupi!');
        }

        $data['tipe'] = 'penyaluran';
        $data['created_by'] = auth()->id();
        $transaksi = ZakatTransaksi::create($data);

        if ($request->hasFile('bukti')) {
            $transaksi->addMedia($request->file('bukti'))->toMediaCollection('bukti_zakat');
        }

        // PAKAI JURNAL REPOSITORY JUGA
        $this->jurnal->penyaluranZakat(
            tanggal: $transaksi->tanggal,
            jumlah: $transaksi->jumlah,
            akunLiabilitasId: $transaksi->akun_id,
            keteranganPenyaluran: $transaksi->keterangan,
            reference: $transaksi
        );

        return $transaksi;
    }

    public function dataTable()
    {
        return DataTables::of(ZakatTransaksi::with(['akun','user'])->where('tipe','penerimaan')->latest())
            ->addColumn('jenis', fn($r) => collect($r->jenis_zakat)->map(fn($j)=>ucwords(str_replace('_',' ',$j)))->implode(', '))
            ->addColumn('akun_zakat', function ($row) {
                if ($row->akun) {

                    return '
                        <div class="inline-flex items-center bg-red-50 text-red-700 border border-red-300 rounded-lg px-2 py-1 text-xs gap-1">
                            <span class="font-bold">' . e($row->akun->kode) . '</span>
                            <span>&nbsp;' . e($row->akun->nama) . '</span>
                        </div>
                    ';
                }

                return '<span class="badge badge-neutral text-xs px-2 py-1">—</span>';
            })
            ->addColumn('jumlah_fmt', fn($r) => 'Rp ' . number_format($r->jumlah,0,',','.'))
            ->addColumn('kwitansi', fn($r) => '<a href="'.route('admin.keuangan.zakat.kwitansi',$r->id).'" target="_blank" class="btn btn-success btn-sm"><i class="fas fa-print"></i> Cetak</a>')
            ->rawColumns(['kwitansi', 'akun_zakat'])
            ->make(true);
    }
}