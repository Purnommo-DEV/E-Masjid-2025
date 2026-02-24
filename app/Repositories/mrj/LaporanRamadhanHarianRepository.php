<?php

namespace App\Repositories\mrj;

use App\Interfaces\LaporanRamadhanHarianRepositoryInterface;
use App\Models\LaporanRamadhanHarian;

class LaporanRamadhanHarianRepository implements LaporanRamadhanHarianRepositoryInterface
{
    public function all()
    {
        $laporans = LaporanRamadhanHarian::with('jadwalImam')
            ->orderBy('malam_ke', 'asc')
            ->get();

        $totals = [
            'infaq_ramadan'   => 0,
            'ifthor'          => 0,
            'santunan_yatim'  => 0,
            'paket_sembako'   => 0,
            'gebyar'          => 0,
        ];

        $mapped = $laporans->map(function ($item) use (&$totals) {
            // Perhitungan per hari (untuk collapse di frontend)
            $infaqHariIni = $item->infaq_ramadan_saldo_sekarang ?? 0;
            $ifthorHariIni = $item->ifthor_saldo_sekarang ?? 0;

            // Santunan Yatim: kemarin + penerimaan hari ini
            $santunanHariIni = ($item->santunan_yatim_terkumpul_kemarin ?? 0) +
                               collect($item->santunan_yatim_penerimaan_hari_ini ?? [])->sum('nominal');

            // Paket Sembako: kemarin + penerimaan hari ini
            $sembakoHariIni = ($item->paket_sembako_terkumpul_kemarin ?? 0) +
                              collect($item->paket_sembako_penerimaan_hari_ini ?? [])->sum('nominal');

            // Gebyar Ramadhan: kemarin + penerimaan hari ini
            $gebyarHariIni = ($item->gebyar_anak_terkumpul_kemarin ?? 0) +
                             collect($item->gebyar_anak_penerimaan_hari_ini ?? [])->sum('nominal');

            // Akumulasi total kumulatif untuk card atas
            $totals['infaq_ramadan']   += $infaqHariIni;
            $totals['ifthor']          += $ifthorHariIni;
            $totals['santunan_yatim']  += $santunanHariIni;
            $totals['paket_sembako']   += $sembakoHariIni;
            $totals['gebyar']          += $gebyarHariIni;

            return [
                'id'                      => $item->id,
                'malam_ke'                => $item->malam_ke,
                'tanggal'                 => $item->tanggal ? $item->tanggal->format('d M Y') : '-',
                'imam'                    => $item->jadwalImam ? $item->jadwalImam->imam_nama : '-',
                'saldo_infaq_ramadan'     => number_format($infaqHariIni, 0, ',', '.'),
                'saldo_ifthor'            => number_format($ifthorHariIni, 0, ',', '.'),
                'santunan_yatim'          => number_format($santunanHariIni, 0, ',', '.'),
                'paket_sembako'           => number_format($sembakoHariIni, 0, ',', '.'),
                'gebyar'                  => number_format($gebyarHariIni, 0, ',', '.'),

                // Kirim semua detail untuk breakdown
                'saldo_infaq_ramadan'     => number_format($infaqHariIni, 0, ',', '.'),
                'infaq_ramadan_saldo_kemarin' => number_format($item->infaq_ramadan_saldo_kemarin ?? 0, 0, ',', '.'),
                'infaq_ramadan_penerimaan_tromol' => number_format($item->infaq_ramadan_penerimaan_tromol ?? 0, 0, ',', '.'),
                'infaq_ramadan_pengeluaran_operasional' => number_format($item->infaq_ramadan_pengeluaran_operasional ?? 0, 0, ',', '.'),
                'infaq_ramadan_pengeluaran_detail' => $item->infaq_ramadan_pengeluaran_detail ?? [],
                'ifthor_saldo_kemarin'               => number_format($item->ifthor_saldo_kemarin ?? 0, 0, ',', '.'),
                'ifthor_penerimaan_detail'           => $item->ifthor_penerimaan_detail ?? [],
                'ifthor_pengeluaran_detail'          => $item->ifthor_pengeluaran_detail ?? [],
                'santunan_yatim_terkumpul_kemarin'   => number_format($item->santunan_yatim_terkumpul_kemarin ?? 0, 0, ',', '.'),
                'santunan_yatim_penerimaan_hari_ini' => $item->santunan_yatim_penerimaan_hari_ini ?? [],
                'paket_sembako_terkumpul_kemarin'    => number_format($item->paket_sembako_terkumpul_kemarin ?? 0, 0, ',', '.'),
                'paket_sembako_penerimaan_hari_ini'  => $item->paket_sembako_penerimaan_hari_ini ?? [],
                'gebyar_anak_terkumpul_kemarin'      => number_format($item->gebyar_anak_terkumpul_kemarin ?? 0, 0, ',', '.'),
                'gebyar_anak_penerimaan_hari_ini'    => $item->gebyar_anak_penerimaan_hari_ini ?? [],
                'created_at'                         => $item->created_at->format('d/m/Y H:i'),
            ];
        });

        // Ambil item terakhir untuk totals (malam terbaru)
        if ($laporans->isNotEmpty()) {
            $lastItem = $laporans->last(); // item terakhir (malam ke terbesar)

            $totals['infaq_ramadan']   = $lastItem->infaq_ramadan_saldo_sekarang ?? 0;
            $totals['ifthor']          = $lastItem->ifthor_saldo_sekarang ?? 0;
            $totals['santunan_yatim']  = ($lastItem->santunan_yatim_terkumpul_kemarin ?? 0) +
                                         collect($lastItem->santunan_yatim_penerimaan_hari_ini ?? [])->sum('nominal');
            $totals['paket_sembako']   = ($lastItem->paket_sembako_terkumpul_kemarin ?? 0) +
                                         collect($lastItem->paket_sembako_penerimaan_hari_ini ?? [])->sum('nominal');
            $totals['gebyar']          = ($lastItem->gebyar_anak_terkumpul_kemarin ?? 0) +
                                         collect($lastItem->gebyar_anak_penerimaan_hari_ini ?? [])->sum('nominal');
        }
        
        return [
            'data'   => $mapped->values()->toArray(),
            'totals' => $totals // Total kumulatif untuk card atas
        ];
    }

    public function find($id)
    {
        return LaporanRamadhanHarian::findOrFail($id);
    }

    public function donatur(){}
    
    public function create(array $data)
    {
        // Hitung total pengeluaran operasional dari detail repeater (BARU)
        $pengeluaranDetail = $data['infaq_ramadan_pengeluaran_detail'] ?? [];
        $totalPengeluaran = collect($pengeluaranDetail)->sum('nominal');

        // Simpan total ke kolom operasional (biar kompatibel dengan laporan lama)
        $data['infaq_ramadan_pengeluaran_operasional'] = $totalPengeluaran;

        // Hitung saldo sekarang Infaq Ramadhan
        $data['infaq_ramadan_saldo_sekarang'] =
            ($data['infaq_ramadan_saldo_kemarin'] ?? 0) +
            ($data['infaq_ramadan_penerimaan_tromol'] ?? 0) -
            $totalPengeluaran;

        // Hitung saldo Iftor (logika lama tetap)
        $ifthorPenerimaan = collect($data['ifthor_penerimaan_detail'] ?? [])->sum('nominal');
        $ifthorPengeluaran = collect($data['ifthor_pengeluaran_detail'] ?? [])->sum('nominal');
        $data['ifthor_saldo_sekarang'] =
            ($data['ifthor_saldo_kemarin'] ?? 0) +
            $ifthorPenerimaan -
            $ifthorPengeluaran;

        // Gebyar Ramadhan – hitung total
        $gebyarKemarin = $data['gebyar_anak_terkumpul_kemarin'] ?? 0;
        $gebyarHariIni = collect($data['gebyar_anak_penerimaan_hari_ini'] ?? [])->sum('nominal');
        $data['gebyar_anak_total_terkumpul'] = $gebyarKemarin + $gebyarHariIni;
        return LaporanRamadhanHarian::create($data);
    }

    public function update($id, array $data)
    {
        $laporan = $this->find($id);

        // Hitung ulang total pengeluaran operasional dari detail repeater (BARU)
        $pengeluaranDetail = $data['infaq_ramadan_pengeluaran_detail'] ?? $laporan->infaq_ramadan_pengeluaran_detail ?? [];
        $totalPengeluaran = collect($pengeluaranDetail)->sum('nominal');

        // Update kolom total operasional
        $data['infaq_ramadan_pengeluaran_operasional'] = $totalPengeluaran;

        // Hitung ulang saldo sekarang Infaq Ramadhan
        $data['infaq_ramadan_saldo_sekarang'] =
            ($data['infaq_ramadan_saldo_kemarin'] ?? $laporan->infaq_ramadan_saldo_kemarin) +
            ($data['infaq_ramadan_penerimaan_tromol'] ?? $laporan->infaq_ramadan_penerimaan_tromol) -
            $totalPengeluaran;

        // Hitung ulang saldo Iftor
        $ifthorPenerimaan = collect($data['ifthor_penerimaan_detail'] ?? $laporan->ifthor_penerimaan_detail ?? [])->sum('nominal');
        $ifthorPengeluaran = collect($data['ifthor_pengeluaran_detail'] ?? $laporan->ifthor_pengeluaran_detail ?? [])->sum('nominal');
        $data['ifthor_saldo_sekarang'] =
            ($data['ifthor_saldo_kemarin'] ?? $laporan->ifthor_saldo_kemarin) +
            $ifthorPenerimaan -
            $ifthorPengeluaran;
        
        // Gebyar
        $gebyarKemarin = $data['gebyar_anak_terkumpul_kemarin'] ?? $laporan->gebyar_anak_terkumpul_kemarin ?? 0;
        $gebyarHariIni = collect($data['gebyar_anak_penerimaan_hari_ini'] ?? $laporan->gebyar_anak_penerimaan_hari_ini ?? [])->sum('nominal');
        $data['gebyar_anak_total_terkumpul'] = $gebyarKemarin + $gebyarHariIni;
        $laporan->update($data);

        return $laporan;
    }

    public function delete($id)
    {
        $laporan = $this->find($id);
        $laporan->delete();
    }
}