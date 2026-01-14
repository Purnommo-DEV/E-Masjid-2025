<?php

namespace App\Repositories\mrj;

use App\Interfaces\DanaTerikatRepositoryInterface;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\{
    DanaTerikatProgram,
    DanaTerikatPenerima,
    DanaTerikatPenerimaan,
    DanaTerikatRealisasi,
    AkunKeuangan,
    DanaTerikatRealisasiKoreksi
};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DanaTerikatRepository implements DanaTerikatRepositoryInterface
{
    protected $jurnal;

    public function __construct(JurnalRepositoryInterface $jurnal)
    {
        $this->jurnal = $jurnal;
    }

    /* =========================
     *  DATA UNTUK TABS
     * ========================= */

    public function getSaldoData(?int $programId, ?int $tahun): Collection
    {
        $programQuery = DanaTerikatProgram::with('akun')->where('aktif', 1);

        if ($programId) {
            $programQuery->where('id', $programId);
        }

        $programs = $programQuery->get();

        return $programs->map(function ($p) use ($tahun) {
            // TERKUMPUL
            $penerimaanQuery = $p->penerimaan();
            if ($tahun) {
                $penerimaanQuery->whereYear('tanggal', $tahun);
            }
            $terkumpul = $penerimaanQuery->sum('jumlah');

            // REALISASI
            $realisasiQuery = DanaTerikatRealisasi::join(
                    'dana_terikat_penerima',
                    'dana_terikat_realisasi.penerima_id',
                    '=',
                    'dana_terikat_penerima.id'
                )
                ->where('dana_terikat_penerima.program_id', $p->id);

            if ($tahun) {
                $realisasiQuery
                    ->where('dana_terikat_penerima.tahun_program', $tahun)
                    ->whereYear('dana_terikat_realisasi.created_at', $tahun);
            }

            // Realisasi bulan ini
            $realisasiQuery->whereMonth('dana_terikat_realisasi.created_at', date('m'));

            $realisasi = $realisasiQuery->sum('dana_terikat_realisasi.jumlah');

            return [
                'nama_program'         => $p->nama_program,
                'terkumpul'           => $terkumpul,
                'realisasi_bulan_ini' => $realisasi,
                'sisa'                => $terkumpul - $realisasi,
            ];
        });
    }

    public function getPenerimaQuery(?int $programId, ?int $tahun): Builder
    {
        return DanaTerikatPenerima::query()
            ->leftJoin('dana_terikat_program', 'dana_terikat_penerima.program_id', '=', 'dana_terikat_program.id')
            ->select(
                'dana_terikat_penerima.*',
                'dana_terikat_program.nama_program as program_nama'
            )
            ->when($programId, fn($q) => $q->where('dana_terikat_penerima.program_id', $programId))
            ->when($tahun, fn($q) => $q->where('dana_terikat_penerima.tahun_program', $tahun));
    }

    public function getPenerimaanQuery(?int $programId, ?int $tahun): Builder
    {
        return DanaTerikatPenerimaan::query()
            ->leftJoin('dana_terikat_program', 'dana_terikat_penerimaan.program_id', '=', 'dana_terikat_program.id')
            ->select(
                'dana_terikat_penerimaan.*',
                'dana_terikat_program.nama_program as program_nama'
            )
            ->when($programId, fn($q) => $q->where('dana_terikat_penerimaan.program_id', $programId))
            ->when($tahun, fn($q) => $q->whereYear('dana_terikat_penerimaan.tanggal', $tahun));
    }

    public function getRealisasiQuery(?int $programId, ?int $tahun): Builder
    {
        return DanaTerikatRealisasi::query()
            ->leftJoin('dana_terikat_program', 'dana_terikat_realisasi.program_id', '=', 'dana_terikat_program.id')
            ->leftJoin('dana_terikat_penerima', 'dana_terikat_realisasi.penerima_id', '=', 'dana_terikat_penerima.id')
            ->select(
                'dana_terikat_realisasi.*',
                'dana_terikat_program.nama_program as program_nama',
                'dana_terikat_penerima.nama as penerima_nama'
            )
            ->when($programId, fn($q) => $q->where('dana_terikat_realisasi.program_id', $programId))
            ->when($tahun, fn($q) => $q->whereYear('dana_terikat_realisasi.created_at', $tahun));
    }

    /* =========================
     *  OPERASI DATA
     * ========================= */

    public function storePenerimaan(array $data)
    {
        return DB::transaction(function () use ($data) {
            $penerimaan = DanaTerikatPenerimaan::create(
                $data + ['created_by' => auth()->id()]
            );

            $this->jurnal->terimaDanaTerikat(
                $data['tanggal'],
                $data['jumlah'],
                $penerimaan->program,
                $data['donatur_nama'],
                $penerimaan
            );

            return $penerimaan;
        });
    }

    public function findPenerima(int $id)
    {
        return DanaTerikatPenerima::findOrFail($id);
    }

    public function updatePenerima(int $id, array $data)
    {
        $penerima = DanaTerikatPenerima::findOrFail($id);

        $data['status_aktif'] = isset($data['status_aktif']) ? 1 : 0;

        $umur = null;
        if (!empty($data['tanggal_lahir'])) {
            $umur = Carbon::parse($data['tanggal_lahir'])->age;
        }

        $data['status_yatim'] = 0;
        $data['umur']         = $umur; // boleh null

        if (($data['kategori'] ?? null) === 'yatim') {
            if ($umur !== null && $umur >= 15) {
                throw new \RuntimeException('Usia anak yatim maksimal 14 tahun (belum baligh).');
            }

            $data['status_yatim'] = 1;
        }
        
        $penerima->fill($data);
        $penerima->save();

        return $penerima;
    }

    public function storePenerima(array $data)
    {
        $data['status_aktif'] = isset($data['status_aktif']) ? 1 : 0;

        $umur = null;
        if (!empty($data['tanggal_lahir'])) {
            $umur = Carbon::parse($data['tanggal_lahir'])->age;
        }

        $data['status_yatim'] = 0;

        if (($data['kategori'] ?? null) === 'yatim') {
            if ($umur !== null && $umur >= 15) {
                throw new \RuntimeException('Usia anak yatim maksimal 14 tahun (belum baligh).');
            }

            $data['status_yatim'] = 1;
        }
        return DanaTerikatPenerima::create($data);
    }


    public function realisasiBulanan(int $programId, int $bulan, int $tahun)
    {
        $program = DanaTerikatProgram::findOrFail($programId);

        // CEK DULU: SUDAH PERNAH DIREALISASI BELUM? (PAKAI 4 KUNCI!)
        $sudahAda = DanaTerikatRealisasi::where('program_id', $programId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->exists();

        if ($sudahAda) {
            throw new \Exception("Realisasi untuk {$program->nama_program} bulan " . 
                Carbon::create()->month($bulan)->translatedFormat('F') . " {$tahun} sudah pernah dilakukan!");
        }

        $penerima = $program->penerima()
            ->where('status_aktif', 1)
            ->where('tahun_program', $tahun)
            ->get();

        if ($penerima->isEmpty()) {
            throw new \Exception('Tidak ada penerima aktif untuk program ini di tahun ' . $tahun);
        }

        $tanggalJurnal = now(); // atau Carbon::create($tahun, $bulan, 1);

        DB::transaction(function () use ($program, $penerima, $tanggalJurnal, $bulan, $tahun) {
            foreach ($penerima as $p) {
                // PAKAI 4 KUNCI: program_id + penerima_id + tahun + bulan → 100% ANTI DUPLIKAT!
                $realisasi = DanaTerikatRealisasi::updateOrCreate(
                    [
                        'program_id'  => $program->id,
                        'penerima_id' => $p->id,
                        'tahun'       => $tahun,
                        'bulan'       => $bulan,
                    ],
                    [
                        'jumlah'      => $p->nominal_bulanan,
                        'created_by'  => auth()->id(),
                        'keterangan'  => 'Realisasi otomatis bulan ' . $bulan . '/' . $tahun,
                    ]
                );

                // Jurnal hanya dibuat kalau realisasi BARU dibuat (bukan update)
                if ($realisasi->wasRecentlyCreated) {
                    $this->jurnal->realisasiDanaTerikat(
                        $tanggalJurnal,
                        $p->nominal_bulanan,
                        $program,
                        $p->nama,
                        $realisasi
                    );
                }
            }
        });
    }

    public function koreksiRealisasi(int $programId, int $tahun, int $bulan, int $jumlahKoreksi, string $keterangan)
    {
        $program = DanaTerikatProgram::findOrFail($programId);

        DB::transaction(function () use ($program, $tahun, $bulan, $jumlahKoreksi, $keterangan) {
            // 1. Simpan ke tabel koreksi
            $koreksi = DanaTerikatRealisasiKoreksi::create([
                'program_id'     => $program->id,
                'tahun'          => $tahun,
                'bulan'          => $bulan,
                'jumlah_koreksi' => $jumlahKoreksi,
                'keterangan'     => $keterangan,
                'created_by'     => auth()->id(),
            ]);

            // 2. Buat jurnal koreksi di bulan berjalan
            $this->jurnal->koreksiRealisasiDanaTerikat(
                now()->format('Y-m-d'),
                $jumlahKoreksi,
                $program,
                $keterangan,
                $koreksi
            );
        });
    }

    public function storeProgram(array $data)
    {
        return DanaTerikatProgram::create($data);
    }

    public function getAkunOptionsHtml(): string
    {
        $akuns = AkunKeuangan::where('tipe', 'Liabilitas')
            ->where('kode', 'like', '2%')
            ->orderBy('kode')
            ->get();

        $options = '<option value="">— Pilih Akun Liabilitas —</option>';
        foreach ($akuns as $a) {
            $options .= "<option value=\"{$a->id}\">{$a->kode} - {$a->nama}</option>";
        }

        return $options;
    }
}
