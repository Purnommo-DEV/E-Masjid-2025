<?php

namespace App\Repositories\mrj;

use App\Interfaces\JurnalRepositoryInterface;
use App\Models\Jurnal;
use App\Models\SaldoAwalPeriode;
use Carbon\Carbon;

class JurnalRepository implements JurnalRepositoryInterface
{
    public function buatJurnal($tanggal, $keterangan, $entries, $reference = null)
    {
        if (! $tanggal instanceof Carbon) {
            $tanggal = Carbon::parse($tanggal);
        }

        $no = 'JNL-'.$tanggal->format('Ym').'-'.str_pad(
            Jurnal::whereYear('tanggal', $tanggal->year)
                ->whereMonth('tanggal', $tanggal->month)
                ->count() + 1, 5, '0', STR_PAD_LEFT
        );
        foreach ($entries as $e) {
            Jurnal::create([
                'tanggal' => $tanggal,
                'no_jurnal' => $no,
                'keterangan' => $keterangan,
                'akun_id' => $e['akun_id'],
                'debit' => $e['debit'] ?? 0,
                'kredit' => $e['kredit'] ?? 0,
                'jurnalable_id' => $reference?->id,
                'jurnalable_type' => $reference ? get_class($reference) : null,
                'created_by' => auth()->id(),
            ]);
        }
    }

    public function lockSaldoAwal($periodeId)
    {
        $periode = SaldoAwalPeriode::findOrFail($periodeId);
        $periode->update(['status' => 'locked']);

        foreach ($periode->details as $detail) {
            if ($detail->jumlah > 0) {
                $entries = \createSaldoAwalOpeningEntries($detail);
                $this->buatJurnal($periode->periode, 'Saldo Awal – '.$detail->akun->nama, $entries, $detail);
            }
        }
    }

    public function isiUlangPettyCash($tanggal, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => akunIdByKode(10005), 'debit' => $jumlah], // Kas Kecil (Petty Cash)
            ['akun_id' => akunIdByKode(10001), 'kredit' => $jumlah], // Kas Utama
        ], $reference);
    }

    public function pengeluaranDariPettyCash($tanggal, $akunBebanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunBebanId,        'debit' => $jumlah],
            ['akun_id' => akunIdByKode(10005), 'kredit' => $jumlah], // Kas Kecil (Petty Cash)
        ], $reference);
    }

    public function pengeluaranUmum($tanggal, $akunBebanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunBebanId,         'debit' => $jumlah],
            ['akun_id' => akunIdByKode(10001),  'kredit' => $jumlah], // Kas Utama
        ], $reference);
    }

    public function penerimaanPemasukan($tanggal, $akunPendapatanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => akunIdByKode('10001'), 'debit' => $jumlah], // Kas Utama naik
            ['akun_id' => $akunPendapatanId,     'kredit' => $jumlah], // Pendapatan
        ], $reference);
    }

    public function penerimaanZakat($tanggal, $jumlah, $akunLiabilitasId, $muzakki, $reference = null)
    {
        $keterangan = "Penerimaan Zakat dari {$muzakki}";

        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => akunIdByKode('10001'), 'debit' => $jumlah], // Kas Utama
            ['akun_id' => $akunLiabilitasId,     'kredit' => $jumlah], // Zakat Diterima (Liabilitas)
        ], $reference);
    }

    public function penyaluranZakat($tanggal, $jumlah, $akunLiabilitasId, $keteranganPenyaluran, $reference = null)
    {
        $keterangan = "Penyaluran Zakat - {$keteranganPenyaluran}";

        $this->buatJurnal($tanggal, $keterangan, [
            ['akun_id' => $akunLiabilitasId, 'debit' => $jumlah], // Kurangi Liabilitas
            ['akun_id' => akunIdByKode('10001'), 'kredit' => $jumlah], // Kas Utama keluar
        ], $reference);
    }

    /**
     * Terima Dana Terikat dengan jenis dana
     */
    public function terimaDanaTerikat($tanggal, $jumlah, $program, $donatur, $jenisDana = 'dana_terikat', $reference = null)
    {
        $akunAsetId = $program->akun_aset_id ?? akunIdByKode('10003');

        // 🔥 Pilih akun liabilitas berdasarkan jenis dana
        $akunLiabilitasId = match ($jenisDana) {
            'zakat_maal' => akunIdByKode('20002'),
            'zakat_fitrah' => akunIdByKode('20001'),
            'fidyah' => akunIdByKode('20003'),
            'infaq_umum' => akunIdByKode('20005'),
            'shodaqoh' => akunIdByKode('20006'),
            'dana_titipan' => akunIdByKode('20099'),
            'dana_terikat' => $program->akun_liabilitas_id,
            default => $program->akun_liabilitas_id,
        };

        $this->buatJurnal($tanggal, "Penerimaan Dana {$jenisDana} - {$donatur}", [
            ['akun_id' => $akunAsetId, 'debit' => $jumlah],
            ['akun_id' => $akunLiabilitasId, 'kredit' => $jumlah],
        ], $reference);
    }

    /**
     * 🔥 BUAT JURNAL GROUPING REALISASI (1 JURNAL PER BULAN)
     * BUKAN PER PENERIMA!
     */
    public function buatJurnalGroupRealisasi(
        $tanggal,
        $totalNominal,
        $program,
        $bulan,
        $tahun,
        $count,
        $hasOperasional = false
    ) {
        $akunAsetId = $program->akun_aset_id ?? akunIdByKode('10003'); // Bank BNI

        $keterangan = "Realisasi Santunan {$program->nama_program} - {$count} penerima (Bulan ".
                    Carbon::create()->month($bulan)->translatedFormat('F')." {$tahun})";

        if ($hasOperasional) {
            $keterangan = "Realisasi Santunan + Biaya Operasional {$program->nama_program} - {$count} penerima (Bulan ".
                        Carbon::create()->month($bulan)->translatedFormat('F')." {$tahun})";
        }

        $this->buatJurnal(
            $tanggal,
            $keterangan,
            [
                ['akun_id' => $program->akun_liabilitas_id, 'debit' => $totalNominal], // 20004 Infaq Terikat (Y/D)
                ['akun_id' => $akunAsetId, 'kredit' => $totalNominal], // 10003 Bank BNI
            ],
            null
        );

        return true;
    }

    /**
     * Alokasi dana dari sumber ke tujuan (misal Zakat → Dana Terikat)
     */
    public function alokasiDana($tanggal, $akunSumberId, $akunTujuanId, $jumlah, $keterangan, $reference = null)
    {
        $this->buatJurnal($tanggal, "Alokasi Dana - {$keterangan}", [
            ['akun_id' => $akunSumberId, 'kredit' => $jumlah],  // Sumber berkurang
            ['akun_id' => $akunTujuanId, 'debit' => $jumlah],  // Tujuan bertambah
        ], $reference);
    }

    /**
     * 🔴 METHOD INI DI-COMMENT / DIHAPUS
     * Karena sudah diganti dengan buatJurnalGroupRealisasi()
     */
    // public function realisasiDanaTerikat($tanggal, $jumlah, $program, $penerima, $reference = null)
    // {
    //     $akunAsetId = $program->akun_aset_id ?? akunIdByKode('10001');
    //     $this->buatJurnal($tanggal, "Realisasi {$program->nama_program} - {$penerima}", [
    //         ['akun_id' => $program->akun_liabilitas_id, 'debit'  => $jumlah],
    //         ['akun_id' => $akunAsetId, 'kredit' => $jumlah],
    //     ], $reference);
    // }

    public function koreksiRealisasiDanaTerikat($tanggal, $jumlah, $program, $keterangan, $reference = null)
    {
        $akunAsetId = $program->akun_aset_id ?? akunIdByKode('10001');

        // 🔥 Tentukan jenis koreksi berdasarkan keterangan
        $isOperasional = str_contains($keterangan, 'biaya operasional') ||
                        str_contains($keterangan, 'Biaya Operasional') ||
                        str_contains($keterangan, 'operasional');

        // 🔥 Buat judul jurnal yang sesuai
        if ($isOperasional) {
            $judul = "Koreksi Biaya Operasional {$program->nama_program} — {$keterangan}";
        } else {
            $judul = "Koreksi Realisasi {$program->nama_program} — {$keterangan}";
        }

        // 🔥 Tentukan entries berdasarkan nilai
        $nominal = abs($jumlah);

        if ($jumlah > 0) {
            // POSITIF: Tambah realisasi
            $entries = [
                ['akun_id' => $program->akun_liabilitas_id, 'debit' => $nominal],
                ['akun_id' => $akunAsetId,                   'kredit' => $nominal],
            ];
        } else {
            // NEGATIF: Kurangi realisasi (biaya admin, dll)
            $entries = [
                ['akun_id' => $akunAsetId,                   'debit' => $nominal],
                ['akun_id' => $program->akun_liabilitas_id, 'kredit' => $nominal],
            ];
        }

        $this->buatJurnal($tanggal, $judul, $entries, $reference);
    }
}
