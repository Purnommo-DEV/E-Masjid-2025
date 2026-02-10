<?php

namespace App\Repositories\mrj;

use App\Interfaces\SaldoAwalRepositoryInterface;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\SaldoAwalPeriode;
use App\Models\SaldoAwalDetail;
use App\Models\AkunKeuangan;
use Illuminate\Support\Facades\DB;

class SaldoAwalRepository implements SaldoAwalRepositoryInterface
{
protected $jurnalRepo;

    public function __construct(JurnalRepositoryInterface $jurnalRepo)
    {
        $this->jurnalRepo = $jurnalRepo;
    }

    public function allPeriodes()
    {
        return SaldoAwalPeriode::with('user')->orderByDesc('periode')->get();
    }

    public function getDetails($periodeId)
    {
        return SaldoAwalDetail::with('akun')
            ->where('saldo_awal_periode_id', $periodeId)
            ->get();
    }

    public function simpanDraft(array $data)
    {
        return DB::transaction(function () use ($data) {
            $periode = SaldoAwalPeriode::updateOrCreate(
                ['periode' => $data['periode']],
                [
                    'keterangan' => $data['keterangan'] ?? null,
                    'created_by' => auth()->id(),
                    'status'     => 'draft'
                ]
            );

            // Hapus detail lama (jika edit draft)
            SaldoAwalDetail::where('saldo_awal_periode_id', $periode->id)->delete();

            foreach ($data['saldo'] as $akun_id => $jumlah) {
                if ($jumlah > 0) {
                    SaldoAwalDetail::create([
                        'saldo_awal_periode_id' => $periode->id,
                        'akun_id'               => $akun_id,
                        'jumlah'                => $jumlah
                    ]);
                }
            }

            return $periode;
        });
    }

    public function lockPeriode($periodeId)
    {
        $periode = SaldoAwalPeriode::findOrFail($periodeId);

        if ($periode->status === 'locked') {
            throw new \Exception('Periode sudah di-lock sebelumnya!');
        }

        $periode->update(['status' => 'locked']);

        foreach ($periode->details as $detail) {
            if ($detail->jumlah > 0) {
                $this->jurnalRepo->buatJurnal(
                    $periode->periode,
                    'Jurnal Pembuka – Saldo Awal ' . $detail->akun->nama,
                    [
                        ['akun_id' => $detail->akun_id, 'debit'  => $detail->jumlah],
                        ['akun_id' => akunIdByKode(50001),            'kredit' => $detail->jumlah], // Modal Awal
                    ],
                    $detail
                );
            }
        }

        return $periode;
    }

    public function isLocked($periodeTanggal): bool
    {
        return SaldoAwalPeriode::where('periode', $periodeTanggal)
            ->where('status', 'locked')
            ->exists();
    }

    public function hapusDraft($periodeId)
    {
        $periode = SaldoAwalPeriode::findOrFail($periodeId);
        if ($periode->status === 'locked') {
            throw new \Exception('Tidak bisa hapus periode yang sudah di-lock!');
        }
        $periode->details()->delete();
        $periode->delete();
    }

    /**
     * Membuat periode saldo awal baru (otomatis tahun berikutnya)
     * Hanya bisa dilakukan jika periode terakhir sudah locked
     *
     * @return SaldoAwalPeriode
     * @throws \Exception
     */
    public function createNewPeriod()
    {
        $periodeTerakhir = $this->allPeriodes()->first();

        if (!$periodeTerakhir) {
            throw new \Exception('Belum ada periode sebelumnya.');
        }

        if ($periodeTerakhir->status !== 'locked') {
            throw new \Exception('Periode sebelumnya (' . $periodeTerakhir->periode->format('d M Y') . ') belum di-lock!');
        }

        $tanggalBaru = $periodeTerakhir->periode->addYear()->startOfYear();

        // Cek duplikat dengan query langsung (pakai exists() di Query Builder)
        if (SaldoAwalPeriode::where('periode', $tanggalBaru)->exists()) {
            throw new \Exception('Periode untuk tahun ' . $tanggalBaru->year . ' sudah ada.');
        }

        return DB::transaction(function () use ($tanggalBaru) {
            $periodeBaru = SaldoAwalPeriode::create([
                'periode'     => $tanggalBaru,
                'keterangan'  => 'Saldo awal tahun ' . $tanggalBaru->year,
                'status'      => 'draft',
                'created_by'  => auth()->id(),
            ]);

            return $periodeBaru;
        });
    }
}
