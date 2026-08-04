<?php

namespace App\Repositories\mrj;

use App\Interfaces\DanaTerikatRepositoryInterface;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\AkunKeuangan;
use App\Models\DanaTerikatPenerima;
use App\Models\DanaTerikatPenerimaan;
use App\Models\DanaTerikatProgram;
use App\Models\DanaTerikatRealisasi;
use App\Models\DanaTerikatRealisasiKoreksi;
use App\Models\DanaTerikatStatusBulanan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DanaTerikatRepository implements DanaTerikatRepositoryInterface
{
    protected $jurnal;

    public function __construct(JurnalRepositoryInterface $jurnal)
    {
        $this->jurnal = $jurnal;
    }

    /* =========================
     *  DATA UNTUK TABS (EXISTING)
     * ========================= */

    public function getSaldoData(?int $programId, ?int $tahun): Collection
    {
        $programQuery = DanaTerikatProgram::with('akun')->where('aktif', 1);

        if ($programId) {
            $programQuery->where('id', $programId);
        }

        $programs = $programQuery->get();
        $bulanFilter = request()->bulan ?? date('m');

        return $programs->map(function ($p) use ($tahun, $bulanFilter) {
            // 1️⃣ TERKUMPUL
            $penerimaanQuery = $p->penerimaan();
            if ($tahun) {
                $penerimaanQuery->whereYear('tanggal', $tahun);
            }
            $terkumpul = $penerimaanQuery->sum('jumlah');

            // 2️⃣ REALISASI BULAN INI
            $realisasiBulanIni = DanaTerikatRealisasi::where('program_id', $p->id)
                ->where('bulan', $bulanFilter)
                ->where('tahun', $tahun ?? date('Y'))
                ->sum('jumlah');

            // 🔥 KOREKSI BULAN INI - PAKAI ABS()
            $koreksiBulanIni = DanaTerikatRealisasiKoreksi::where('program_id', $p->id)
                ->where('bulan', $bulanFilter)
                ->where('tahun', $tahun ?? date('Y'))
                ->sum('jumlah_koreksi');

            // 🔥 PAKAI abs() AGAR KOREKSI NEGATIF MENJADI POSITIF
            $totalRealisasiBulanIni = $realisasiBulanIni + abs($koreksiBulanIni);

            // 3️⃣ TOTAL REALISASI KUMULATIF
            $totalRealisasiKumulatif = DanaTerikatRealisasi::where('program_id', $p->id)
                ->where('tahun', $tahun ?? date('Y'))
                ->where('bulan', '<=', $bulanFilter)
                ->sum('jumlah');

            // 🔥 KOREKSI KUMULATIF - PAKAI ABS()
            $totalKoreksiKumulatif = DanaTerikatRealisasiKoreksi::where('program_id', $p->id)
                ->where('tahun', $tahun ?? date('Y'))
                ->where('bulan', '<=', $bulanFilter)
                ->sum('jumlah_koreksi');

            // 🔥 PAKAI abs()
            $totalRealisasiKumulatif = $totalRealisasiKumulatif + abs($totalKoreksiKumulatif);

            return [
                'nama_program' => $p->nama_program,
                'terkumpul' => $terkumpul,
                'realisasi_bulan_ini' => $totalRealisasiBulanIni,
                'sisa' => $terkumpul - $totalRealisasiKumulatif,
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
            ->when($programId, fn ($q) => $q->where('dana_terikat_penerima.program_id', $programId))
            ->when($tahun, fn ($q) => $q->where('dana_terikat_penerima.tahun_program', $tahun));
    }

    public function getPenerimaanQuery(?int $programId, ?int $tahun): Builder
    {
        return DanaTerikatPenerimaan::query()
            ->leftJoin('dana_terikat_program', 'dana_terikat_penerimaan.program_id', '=', 'dana_terikat_program.id')
            ->select(
                'dana_terikat_penerimaan.*',
                'dana_terikat_program.nama_program as program_nama'
            )
            ->when($programId, fn ($q) => $q->where('dana_terikat_penerimaan.program_id', $programId))
            ->when($tahun, fn ($q) => $q->whereYear('dana_terikat_penerimaan.tanggal', $tahun));
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
            ->when($programId, fn ($q) => $q->where('dana_terikat_realisasi.program_id', $programId))
            ->when($tahun, fn ($q) => $q->whereYear('dana_terikat_realisasi.created_at', $tahun));
    }

    /* =========================
     *  OPERASI DATA (EXISTING)
     * ========================= */

    public function storePenerimaan(array $data)
    {
        return DB::transaction(function () use ($data) {
            $isSaldoAwal = isset($data['is_saldo_awal']) && $data['is_saldo_awal'] == 1;
            $jenisDana = $data['jenis_dana'] ?? 'dana_terikat';

            // Simpan penerimaan
            $penerimaan = DanaTerikatPenerimaan::create([
                'program_id' => $data['program_id'],
                'jenis_dana' => $jenisDana,  // 🔥 BARU
                'tanggal' => $data['tanggal'],
                'jumlah' => $data['jumlah'],
                'donatur_nama' => $data['donatur_nama'],
                'donatur_kontak' => $data['donatur_kontak'] ?? null,
                'keterangan' => $data['keterangan'] ?? null,
                'is_saldo_awal' => $isSaldoAwal,
                'created_by' => auth()->id(),
            ]);

            // 🔥 Hanya buat jurnal jika BUKAN saldo awal
            if (! $isSaldoAwal) {
                $this->jurnal->terimaDanaTerikat(
                    $data['tanggal'],
                    $data['jumlah'],
                    $penerimaan->program,
                    $data['donatur_nama'],
                    $jenisDana,
                    $penerimaan
                );
            } else {
                Log::info('Saldo awal Dana Terikat dicatat tanpa jurnal', [
                    'program_id' => $data['program_id'],
                    'jenis_dana' => $jenisDana,
                    'jumlah' => $data['jumlah'],
                    'donatur' => $data['donatur_nama'],
                ]);
            }

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
        if (! empty($data['tanggal_lahir'])) {
            $umur = Carbon::parse($data['tanggal_lahir'])->age;
        }

        $data['status_yatim'] = 0;
        $data['umur'] = $umur;

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
        if (! empty($data['tanggal_lahir'])) {
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

    /**
     * 🔥 REALISASI BULANAN - MENGGUNAKAN GROUPING JURNAL
     */
    public function realisasiBulanan(int $programId, int $bulan, int $tahun, ?array $penerimaIds = null)
    {
        $program = DanaTerikatProgram::findOrFail($programId);

        // Cek apakah sudah ada realisasi di bulan ini
        $sudahAda = DanaTerikatRealisasi::where('program_id', $programId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->exists();

        if ($sudahAda) {
            throw new \Exception("Realisasi untuk {$program->nama_program} bulan ".
                Carbon::create()->month($bulan)->translatedFormat('F')." {$tahun} sudah pernah dilakukan!");
        }

        // Ambil penerima
        if ($penerimaIds === null) {
            $penerima = $program->penerima()
                ->where('status_aktif', 1)
                ->where('tahun_program', $tahun)
                ->get();
        } else {
            $penerima = DanaTerikatPenerima::whereIn('id', $penerimaIds)
                ->where('program_id', $programId)
                ->where('tahun_program', $tahun)
                ->where('status_aktif', 1)
                ->get();
        }

        if ($penerima->isEmpty()) {
            throw new \Exception('Tidak ada penerima aktif untuk program ini di tahun '.$tahun);
        }

        $tanggalJurnal = now();
        $totalNominal = 0;
        $realisasiCount = 0;
        $hasOperasional = false;  // 🔥 DEKLARASI DI SINI

        DB::transaction(function () use ($program, $penerima, $tanggalJurnal, $bulan, $tahun, &$totalNominal, &$realisasiCount, &$hasOperasional) {
            foreach ($penerima as $p) {
                // Cek status bulanan
                $status = DanaTerikatStatusBulanan::where('program_id', $program->id)
                    ->where('penerima_id', $p->id)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if ($status && ! $status->status_dapat) {
                    continue;
                }

                $penerima = $status->penerima;
                $nama = $status->nama_alternatif ?? $penerima->nama;
                $nominal = $status->nominal_alternatif ?? $penerima->nominal_bulanan;
                $kategori = $penerima->kategori ?? 'dhuafa';

                // 🔥 CEK APAKAH ADA KATEGORI OPERASIONAL
                if ($kategori === 'operasional') {
                    $hasOperasional = true;
                }

                // 🔥 TAMBAHKAN KETERANGAN DI DETAIL REALISASI
                $keteranganDetail = $kategori === 'operasional'
                    ? "Biaya operasional distribusi - {$nama}"
                    : "Realisasi santunan - {$nama}";

                DanaTerikatRealisasi::updateOrCreate(
                    [
                        'program_id' => $program->id,
                        'penerima_id' => $p->id,
                        'tahun' => $tahun,
                        'bulan' => $bulan,
                    ],
                    [
                        'jumlah' => $nominal,
                        'nama_saat_realisasi' => $nama,
                        'nominal_saat_realisasi' => $nominal,
                        'status_bulanan_id' => $status?->id,
                        'tipe_realisasi' => $status ? 'auto_dari_status' : 'manual',
                        'created_by' => auth()->id(),
                        'keterangan' => $keteranganDetail,  // 🔥 BEDA!
                    ]
                );

                $totalNominal += $nominal;
                $realisasiCount++;
            }

            // 🔥 BUAT 1 JURNAL GROUP (BUKAN PER PENERIMA!)
            if ($realisasiCount > 0) {
                $this->jurnal->buatJurnalGroupRealisasi(
                    $tanggalJurnal,
                    $totalNominal,
                    $program,
                    $bulan,
                    $tahun,
                    $realisasiCount,
                    $hasOperasional
                );
            }
        });
    }

    /* =========================
     *  FITUR STATUS BULANAN (BARU)
     * ========================= */

    public function getStatusBulanan(int $programId, int $bulan, int $tahun): Collection
    {
        $penerima = DanaTerikatPenerima::where('program_id', $programId)
            ->where('tahun_program', $tahun)
            ->where('status_aktif', 1)
            ->with('program')
            ->get();

        $statusBulanan = DanaTerikatStatusBulanan::where('program_id', $programId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->get()
            ->keyBy('penerima_id');

        return $penerima->map(function ($p) use ($statusBulanan) {
            $status = $statusBulanan->get($p->id);

            return (object) [
                'id' => $p->id,
                'nama' => $p->nama,
                'program_id' => $p->program_id,
                'program_nama' => $p->program->nama_program ?? 'Unknown',
                'nominal_bulanan' => $p->nominal_bulanan,
                'status_aktif' => $p->status_aktif,
                'kategori' => $p->kategori,
                'status_yatim' => $p->status_yatim,
                'umur' => $p->umur,
                'alamat' => $p->alamat,
                'rt' => $p->rt,
                'rw' => $p->rw,
                'nama_rt' => $p->nama_rt,
                'status_bulanan_id' => $status?->id,
                'status_dapat' => $status ? (bool) $status->status_dapat : (bool) $p->status_aktif,
                'alasan_tidak_dapat' => $status?->alasan_tidak_dapat,
                'nama_alternatif' => $status?->nama_alternatif,
                'nominal_alternatif' => $status?->nominal_alternatif,
                'verified_by' => $status?->verified_by,
                'verified_at' => $status?->verified_at,
                'nama_aktual' => $status?->nama_alternatif ?? $p->nama,
                'nominal_aktual' => $status?->nominal_alternatif ?? $p->nominal_bulanan,
                'is_modified' => $status && ($status->nama_alternatif || $status->nominal_alternatif),
            ];
        });
    }

    public function updateStatusBulanan(int $penerimaId, int $bulan, int $tahun, array $data): DanaTerikatStatusBulanan
    {
        return DB::transaction(function () use ($penerimaId, $bulan, $tahun, $data) {
            $penerima = DanaTerikatPenerima::findOrFail($penerimaId);

            $status = DanaTerikatStatusBulanan::updateOrCreate(
                [
                    'penerima_id' => $penerimaId,
                    'bulan' => $bulan,
                    'tahun' => $tahun,
                ],
                [
                    'program_id' => $data['program_id'] ?? $penerima->program_id,
                    'status_dapat' => $data['status_dapat'] ?? 1,
                    'alasan_tidak_dapat' => $data['alasan_tidak_dapat'] ?? null,
                    'nama_alternatif' => $data['nama_alternatif'] ?? null,
                    'nominal_alternatif' => $data['nominal_alternatif'] ?? null,
                    'verified_by' => $data['verified_by'] ?? auth()->user()->name,
                    'verified_at' => now(),
                    'updated_by' => auth()->id(),
                    'created_by' => $data['created_by'] ?? auth()->id(),
                ]
            );

            return $status;
        });
    }

    public function copyStatusDariBulanLalu(int $programId, int $bulan, int $tahun): int
    {
        $bulanLalu = $bulan - 1;
        $tahunLalu = $tahun;

        if ($bulanLalu < 1) {
            $bulanLalu = 12;
            $tahunLalu = $tahun - 1;
        }

        $statusLalu = DanaTerikatStatusBulanan::where('program_id', $programId)
            ->where('bulan', $bulanLalu)
            ->where('tahun', $tahunLalu)
            ->get();

        if ($statusLalu->isEmpty()) {
            throw new \Exception('Tidak ada data status dari bulan sebelumnya');
        }

        $count = 0;
        foreach ($statusLalu as $status) {
            $exists = DanaTerikatStatusBulanan::where('program_id', $programId)
                ->where('penerima_id', $status->penerima_id)
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->exists();

            if (! $exists) {
                $newStatus = $status->replicate();
                $newStatus->bulan = $bulan;
                $newStatus->tahun = $tahun;
                $newStatus->verified_at = null;
                $newStatus->verified_by = null;
                $newStatus->created_by = auth()->id();
                $newStatus->updated_by = auth()->id();
                $newStatus->save();
                $count++;
            }
        }

        return $count;
    }

    /**
     * 🔥 REALISASI DARI STATUS BULANAN (GROUPING JURNAL)
     */
    public function realisasiDariStatusBulanan(int $programId, int $bulan, int $tahun, array $penerimaIds): array
    {
        $program = DanaTerikatProgram::findOrFail($programId);

        // Cek sudah pernah realisasi
        $sudahAda = DanaTerikatRealisasi::where('program_id', $programId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->exists();

        if ($sudahAda) {
            throw new \Exception('Realisasi untuk bulan '.
                Carbon::create()->month($bulan)->translatedFormat('F')." {$tahun} sudah pernah dilakukan!");
        }

        $realisasiCount = 0;
        $totalNominal = 0;
        $hasOperasional = false;  // 🔥 DEKLARASI DI SINI

        DB::transaction(function () use ($program, $bulan, $tahun, $penerimaIds, &$realisasiCount, &$totalNominal, &$hasOperasional) {
            foreach ($penerimaIds as $penerimaId) {
                $status = DanaTerikatStatusBulanan::where('program_id', $program->id)
                    ->where('penerima_id', $penerimaId)
                    ->where('bulan', $bulan)
                    ->where('tahun', $tahun)
                    ->first();

                if (! $status || ! $status->status_dapat) {
                    continue;
                }

                $penerima = $status->penerima;
                $nama = $status->nama_alternatif ?? $penerima->nama;
                $nominal = $status->nominal_alternatif ?? $penerima->nominal_bulanan;
                $kategori = $penerima->kategori ?? 'dhuafa';

                // 🔥 CEK APAKAH ADA KATEGORI OPERASIONAL
                if ($kategori === 'operasional') {
                    $hasOperasional = true;
                }

                // 🔥 TAMBAHKAN KETERANGAN DI DETAIL REALISASI
                $keteranganDetail = $kategori === 'operasional'
                    ? "Biaya operasional distribusi - {$nama}"
                    : "Realisasi santunan - {$nama}";

                // 🔥 1. SIMPAN DETAIL REALISASI (per penerima)
                DanaTerikatRealisasi::create([
                    'program_id' => $program->id,
                    'penerima_id' => $penerimaId,
                    'status_bulanan_id' => $status->id,
                    'tahun' => $tahun,
                    'bulan' => $bulan,
                    'jumlah' => $nominal,
                    'nama_saat_realisasi' => $nama,
                    'nominal_saat_realisasi' => $nominal,
                    'tipe_realisasi' => 'auto_dari_status',
                    'keterangan' => $keteranganDetail,
                    'created_by' => auth()->id(),
                ]);

                $realisasiCount++;
                $totalNominal += $nominal;
            }

            // 🔥 2. BUAT 1 JURNAL GROUP (bukan per penerima)
            if ($realisasiCount > 0) {
                $tanggalJurnal = Carbon::create($tahun, $bulan, 1); // 2026-04-01
                $this->jurnal->buatJurnalGroupRealisasi(
                    $tanggalJurnal,
                    $totalNominal,
                    $program,
                    $bulan,
                    $tahun,
                    $realisasiCount,
                    $hasOperasional
                );
            }
        });

        return [
            'count' => $realisasiCount,
            'total' => $totalNominal,
        ];
    }

    public function getStatusBulananById(int $penerimaId, int $bulan, int $tahun): ?object
    {
        $status = DanaTerikatStatusBulanan::where('penerima_id', $penerimaId)
            ->where('bulan', $bulan)
            ->where('tahun', $tahun)
            ->with('penerima')
            ->first();

        if (! $status) {
            $penerima = DanaTerikatPenerima::findOrFail($penerimaId);

            return (object) [
                'id' => null,
                'penerima_id' => $penerima->id,
                'nama' => $penerima->nama,
                'program_id' => $penerima->program_id,
                'nominal_bulanan' => $penerima->nominal_bulanan,
                'status_dapat' => $penerima->status_aktif,
                'alasan_tidak_dapat' => null,
                'nama_alternatif' => null,
                'nominal_alternatif' => null,
                'verified_by' => null,
                'verified_at' => null,
                'nominal_aktual' => $penerima->nominal_bulanan,
            ];
        }

        return (object) [
            'id' => $status->id,
            'penerima_id' => $status->penerima_id,
            'nama' => $status->penerima->nama,
            'program_id' => $status->program_id,
            'nominal_bulanan' => $status->penerima->nominal_bulanan,
            'status_dapat' => $status->status_dapat,
            'alasan_tidak_dapat' => $status->alasan_tidak_dapat,
            'nama_alternatif' => $status->nama_alternatif,
            'nominal_alternatif' => $status->nominal_alternatif,
            'verified_by' => $status->verified_by,
            'verified_at' => $status->verified_at,
            'nominal_aktual' => $status->nominal_alternatif ?? $status->penerima->nominal_bulanan,
        ];
    }

    public function koreksiRealisasi(int $programId, int $tahun, int $bulan, int $jumlahKoreksi, string $keterangan)
    {
        $program = DanaTerikatProgram::findOrFail($programId);

        DB::transaction(function () use ($program, $tahun, $bulan, $jumlahKoreksi, $keterangan) {
            // 1️⃣ SIMPAN KOREKSI
            $koreksi = DanaTerikatRealisasiKoreksi::create([
                'program_id' => $program->id,
                'tahun' => $tahun,
                'bulan' => $bulan,
                'jumlah_koreksi' => $jumlahKoreksi,
                'keterangan' => $keterangan,
                'created_by' => auth()->id(),
            ]);

            // 2️⃣ BUAT JURNAL KOREKSI
            // 🔥 Tanggal jurnal = tanggal periode yang dikoreksi (bukan now)
            $tanggalJurnal = Carbon::create($tahun, $bulan, 1);

            $this->jurnal->koreksiRealisasiDanaTerikat(
                $tanggalJurnal->format('Y-m-d'),
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

    public function getAkunOptionsHtml(string $tipe = 'liabilitas'): string
    {
        $query = AkunKeuangan::query();

        if (strtolower($tipe) === 'liabilitas') {
            $query->where('tipe', 'liabilitas')
                ->where('kode', 'like', '2%');
            $placeholder = '— Pilih Akun Liabilitas —';
        } else {
            $query->where('tipe', 'aset')
                ->where('kode', 'like', '1%');
            $placeholder = '— Pilih Akun Penampung Dana —';
        }

        $akuns = $query->orderBy('kode')->get();

        $options = "<option value=\"\">{$placeholder}</option>";

        foreach ($akuns as $a) {
            $options .= "<option value=\"{$a->id}\">{$a->kode} - {$a->nama}</option>";
        }

        return $options;
    }
}
