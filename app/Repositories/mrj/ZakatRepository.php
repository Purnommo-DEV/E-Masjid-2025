<?php

namespace App\Repositories\mrj;
use App\Models\ZakatTransaksi;
use App\Models\ZakatTransaksiDetail;
use App\Models\Muzakki;
use App\Models\KadarZakat;
use App\Interfaces\{ZakatRepositoryInterface, JurnalRepositoryInterface};
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class ZakatRepository implements ZakatRepositoryInterface
{
    public function __construct(protected JurnalRepositoryInterface $jurnal) {}

    public function terima(array $data, $request)
    {
        return DB::transaction(function () use ($data, $request) {
            // Cek/create muzakki berdasarkan muzakki_id (bisa ID atau nama string)
            $muzakkiId = $data['muzakki_id'];
            if (is_numeric($muzakkiId)) {
                $muzakki = Muzakki::findOrFail($muzakkiId);
            } else {
                // Buat baru kalau string (nama)
                $muzakki = Muzakki::firstOrCreate(
                    ['nama' => $muzakkiId],
                    [
                        'no_hp' => $data['no_hp'] ?? null,
                        'alamat' => $data['alamat'] ?? null,
                        'blok' => $data['blok'] ?? null,
                        'status' => 'aktif',
                    ]
                );
            }

            // Generate kwitansi
            $tahun = date('Y', strtotime($data['tanggal']));
            $nomorKwitansi = ZakatTransaksi::generateKwitansi($tahun);

            // Buat header transaksi
            $transaksi = ZakatTransaksi::create([
                'nomor_kwitansi' => $nomorKwitansi,
                'muzakki_id' => $muzakki->id,
                'tipe' => 'penerimaan',
                'tanggal' => $data['tanggal'],
                'total_nominal' => 0, // Update nanti
                'total_jiwa' => 0, // Update nanti
                'metode_bayar' => $data['metode_bayar'],
                'keterangan' => $data['keterangan'] ?? null,
                'akun_id' => $data['akun_id'],
                'created_by' => auth()->id(),
            ]);

            // Buat details + hitung total
            $totalNominal = 0;
            $totalJiwa = 0;
            foreach ($data['details'] as $jenis => $detail) {
                // Ambil kadar dari tabel (kalau ada)
                $kadar = KadarZakat::where('tahun', $tahun)
                                    ->where('jenis', $jenis)
                                    ->first();

                $nominal = (int) ($detail['nominal'] ?? 0);
                if ($nominal === 0 && $kadar) {
                    $nominal = $kadar->nilai_uang * ($detail['jiwa'] ?? 1);
                }

                $jiwa = $detail['jiwa'] ?? null;

                $createdDetail = ZakatTransaksiDetail::create([
                    'zakat_transaksi_id' => $transaksi->id, // INI YANG HILANG! WAJIB DIISI
                    'jenis' => $jenis,
                    'nominal' => $nominal,
                    'jiwa' => $jiwa,
                    'jumlah_beras' => $detail['jumlah_beras'] ?? null,
                    'satuan_beras' => $detail['satuan_beras'] ?? null,
                    'kadar_zakat_id' => $kadar?->id,
                    'keterangan_detail' => $detail['keterangan_detail'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalNominal += $nominal;
                $totalJiwa += (int) ($jiwa ?? 0);
            }
            // Update total di header
            $transaksi->update([
                'total_nominal' => $totalNominal,
                'total_jiwa' => $totalJiwa,
            ]);

            // Upload bukti kalau ada
            if ($request->hasFile('bukti')) {
                $transaksi->addMedia($request->file('bukti'))->toMediaCollection('bukti_zakat');
            }

            // Jurnal
            $this->jurnal->penerimaanZakat(
                tanggal: $transaksi->tanggal,
                jumlah: $transaksi->total_nominal,
                akunLiabilitasId: $transaksi->akun_id,
                muzakki: $muzakki->nama,
                reference: $transaksi
            );

            return $transaksi;
        });
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
        return DataTables::of(ZakatTransaksi::with(['akun', 'user', 'muzakki', 'details'])->where('tipe', 'penerimaan')->latest())
            ->addColumn('tanggal', fn($r) => $r->tanggal) // kirim raw tanggal untuk render di JS
            ->addColumn('tanggal_fmt', fn($r) => $r->tanggal ? $r->tanggal->format('d/m/Y') : '-') // optional untuk sorting/filter
            ->addColumn('kwitansi', fn($r) => $r->nomor_kwitansi)
            ->addColumn('muzakki_nama', fn($r) => $r->muzakki ? $r->muzakki->nama : '-') // key sederhana
            ->addColumn('jenis', fn($r) => $r->details->pluck('jenis')->map(fn($j) => ucwords(str_replace('_', ' ', $j)))->implode(', '))
            ->addColumn('total_jiwa', fn($r) => $r->total_jiwa ?? 0)
            ->addColumn('jumlah_fmt', fn($r) => 'Rp ' . number_format($r->total_nominal ?? 0, 0, ',', '.'))
            ->addColumn('kwitansi_cetak', fn($r) => '
                <a href="'.route('admin.keuangan.zakat.kwitansi', $r->id).'" target="_blank" class="btn btn-success btn-sm">
                    <i class="fas fa-print"></i> Cetak
                </a>
            ')
            ->rawColumns(['kwitansi_cetak'])
            ->make(true);
    }

    public function editData($id)
    {
        $transaksi = ZakatTransaksi::with('details', 'muzakki')->findOrFail($id);

        // Format data untuk JS
        $details = [];
        foreach ($transaksi->details as $d) {
            $details[$d->jenis] = [
                'nominal' => $d->nominal,
                'jiwa' => $d->jiwa,
                'jumlah_beras' => $d->jumlah_beras,
                'satuan_beras' => $d->satuan_beras,
                'keterangan_detail' => $d->keterangan_detail,
            ];
        }

        return response()->json([
            'id' => $transaksi->id,
            'muzakki_id' => $transaksi->muzakki_id,
            'muzakki_nama' => $transaksi->muzakki->nama,
            'no_hp' => $transaksi->muzakki->no_hp,
            'jenis_zakat' => $transaksi->jenis_zakat,
            'details' => $details,
            'tanggal' => $transaksi->tanggal->format('Y-m-d'),
            'akun_id' => $transaksi->akun_id,
            'metode_bayar' => $transaksi->metode_bayar,
            'keterangan' => $transaksi->keterangan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $transaksi = ZakatTransaksi::findOrFail($id);

        $rules = [
            'muzakki_id' => 'required',
            'jenis_zakat' => 'required|in:zakat_fitrah,zakat_maal,fidyah,infaq,shodaqoh,wakaf,donasi_khusus',
            'tanggal' => 'required|date',
            'akun_id' => 'required|exists:akun_keuangan,id',
            'metode_bayar' => 'required|in:transfer,cash,beras,barang',
            'details.*.nominal' => 'required|numeric|min:1000',
        ];

        if ($request->jenis_zakat === 'zakat_fitrah') {
            $rules['details.zakat_fitrah.jiwa'] = 'required|integer|min:1';
            $rules['details.zakat_fitrah.jumlah_beras'] = 'nullable|numeric';
            $rules['details.zakat_fitrah.satuan_beras'] = 'nullable|in:kg,liter';
        }

        $request->validate($rules);

        // Handle muzakki (sama seperti store)
        $muzakkiId = $request->muzakki_id;
        if (!is_numeric($muzakkiId)) {
            $muzakki = Muzakki::firstOrCreate(['nama' => $muzakkiId], [
                'no_hp' => $request->no_hp ?? null,
                'status' => 'aktif',
            ]);
            $muzakkiId = $muzakki->id;
        }

        // Update header
        $transaksi->update([
            'muzakki_id' => $muzakkiId,
            'tanggal' => $request->tanggal,
            'akun_id' => $request->akun_id,
            'metode_bayar' => $request->metode_bayar,
            'keterangan' => $request->keterangan ?? null,
        ]);

        // Hapus detail lama
        $transaksi->details()->delete();

        // Hitung ulang total
        $totalNominal = 0;
        $totalJiwa = 0;
        foreach ($request->details as $jenis => $detail) {
            $nominal = (int) ($detail['nominal'] ?? 0);
            $jiwa = $detail['jiwa'] ?? null;

            ZakatTransaksiDetail::create([
                'zakat_transaksi_id' => $transaksi->id,
                'jenis' => $jenis,
                'nominal' => $nominal,
                'jiwa' => $jiwa,
                'jumlah_beras' => $detail['jumlah_beras'] ?? null,
                'satuan_beras' => $detail['satuan_beras'] ?? null,
                'keterangan_detail' => $detail['keterangan_detail'] ?? null,
            ]);

            $totalNominal += $nominal;
            $totalJiwa += (int) ($jiwa ?? 0);
        }

        $transaksi->update([
            'total_nominal' => $totalNominal,
            'total_jiwa' => $totalJiwa,
        ]);

        // Handle bukti baru kalau ada
        if ($request->hasFile('bukti')) {
            $transaksi->clearMediaCollection('bukti_zakat');
            $transaksi->addMedia($request->file('bukti'))->toMediaCollection('bukti_zakat');
        }

        // Update jurnal kalau perlu (opsional, tergantung repo)
        // $this->repo->updateJurnal($transaksi); // kalau ada method ini

        return response()->json(['success' => true, 'message' => 'Transaksi berhasil diupdate!']);
    }
}