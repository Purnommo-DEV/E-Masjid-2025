<?php
namespace App\Repositories\mrj;

use App\Models\Jurnal;
use App\Models\AkunKeuangan;
use App\Interfaces\JurnalRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\SaldoAwalPeriode;

class JurnalRepository implements JurnalRepositoryInterface
{
    public function buatJurnal($tanggal, $keterangan, $entries, $reference = null)
    {
        if (! $tanggal instanceof Carbon) {
            $tanggal = Carbon::parse($tanggal);
        }

        $no = 'JNL-' . $tanggal->format('Ym') . '-' . str_pad(
            Jurnal::whereYear('tanggal', $tanggal->year)
                  ->whereMonth('tanggal', $tanggal->month)
                  ->count() + 1, 5, '0', STR_PAD_LEFT
        );
        foreach ($entries as $e) {
            Jurnal::create([
                'tanggal'         => $tanggal,
                'no_jurnal'       => $no,
                'keterangan'      => $keterangan,
                'akun_id'         => $e['akun_id'],
                'debit'           => $e['debit'] ?? 0,
                'kredit'          => $e['kredit'] ?? 0,
                'jurnalable_id'   => $reference?->id,
                'jurnalable_type' => $reference ? get_class($reference) : null,
                'created_by'      => auth()->id(),
            ]);
        }
    }

    public function lockSaldoAwal($periodeId)
    {
        $periode = SaldoAwalPeriode::findOrFail($periodeId);
        $periode->update(['status' => 'locked']);

        foreach ($periode->details as $detail) {
            if ($detail->jumlah > 0) {
                $this->buatJurnal($periode->periode, 'Saldo Awal – ' . $detail->akun->nama, [
                    ['akun_id' => $detail->akun_id, 'debit'  => $detail->jumlah],
                    ['akun_id' => akunIdByKode(50001), 'kredit' => $detail->jumlah],
                ], $detail);
            }
        }
    }

    public function isiUlangPettyCash($tanggal, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => akunIdByKode(10005), 'debit'  => $jumlah], // Kas Kecil (Petty Cash)
            ['akun_id' => akunIdByKode(10001), 'kredit' => $jumlah], // Kas Utama
        ], $reference);
    }

    public function pengeluaranDariPettyCash($tanggal, $akunBebanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunBebanId,        'debit'  => $jumlah],
            ['akun_id' => akunIdByKode(10005), 'kredit' => $jumlah], // Kas Kecil (Petty Cash)
        ], $reference);
    }

    public function pengeluaranUmum($tanggal, $akunBebanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunBebanId,         'debit'  => $jumlah],
            ['akun_id' => akunIdByKode(10001),  'kredit' => $jumlah], // Kas Utama
        ], $reference);
    }

    public function penerimaanPemasukan($tanggal, $akunPendapatanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => akunIdByKode('10001'), 'debit'  => $jumlah], // Kas Utama naik
            ['akun_id' => $akunPendapatanId,     'kredit' => $jumlah], // Pendapatan
        ], $reference);
    }

    public function alokasiDana($tanggal, $akunSumberId, $akunTujuanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunSumberId, 'kredit' => $jumlah],
            ['akun_id' => $akunTujuanId, 'debit'  => $jumlah],
        ], $reference);
    }

    public function penerimaanZakat($tanggal, $jumlah, $akunLiabilitasId, $muzakki, $reference = null)
    {
        $keterangan = "Penerimaan Zakat dari {$muzakki}";

        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => akunIdByKode('10001'), 'debit'  => $jumlah], // Kas Utama
            ['akun_id' => $akunLiabilitasId,     'kredit' => $jumlah], // Zakat Diterima (Liabilitas)
        ], $reference);
    }

    public function penyaluranZakat($tanggal, $jumlah, $akunLiabilitasId, $keteranganPenyaluran, $reference = null)
    {
        $keterangan = "Penyaluran Zakat - {$keteranganPenyaluran}";

        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunLiabilitasId, 'debit'  => $jumlah], // Kurangi Liabilitas
            ['akun_id' => akunIdByKode('10001'), 'kredit' => $jumlah], // Kas Utama keluar
        ], $reference);
    }

    public function terimaDanaTerikat($tanggal, $jumlah, $program, $donatur, $reference = null)
    {
        $this->buatJurnal($tanggal, "Penerimaan Dana Terikat {$program->nama_program} - {$donatur}", [
            ['akun_id' => akunIdByKode('10001'), 'debit'  => $jumlah], // Kas
            ['akun_id' => $program->akun_liabilitas_id, 'kredit' => $jumlah],
        ], $reference);
    }

    public function realisasiDanaTerikat($tanggal, $jumlah, $program, $penerima, $reference = null)
    {
        $this->buatJurnal($tanggal, "Realisasi {$program->nama_program} - {$penerima}", [
            ['akun_id' => $program->akun_liabilitas_id, 'debit'  => $jumlah],
            ['akun_id' => akunIdByKode('10001'), 'kredit' => $jumlah], // Kas keluar
        ], $reference);
    }

    public function koreksiRealisasiDanaTerikat($tanggal, $jumlah, $program, $keterangan, $reference = null)
    {
        $bebanAkunId = $program->akun_liabilitas_id; // sesuaikan kode akun Beban Santunan kamu
        $kasAkunId   = akunIdByKode('10001'); // Kas

        $entries = [
            ['akun_id' => $bebanAkunId, 'debit'  => abs($jumlah)],
            ['akun_id' => $kasAkunId,   'kredit' => abs($jumlah)],
        ];

        // Jika koreksi negatif (pengurangan), balik debit-kredit
        if ($jumlah < 0) {
            $entries = array_reverse($entries);
            foreach ($entries as $k => $v) {
                $entries[$k]['debit']  = $v['kredit'] ?? 0;
                $entries[$k]['kredit'] = $v['debit'] ?? 0;
            }
        }

        $this->buatJurnal($tanggal, "Koreksi Realisasi {$program->nama_program} — {$keterangan}", $entries, $reference);
    }

}