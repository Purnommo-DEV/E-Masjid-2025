<?php

namespace App\Repositories\mrj;

use App\Interfaces\KotakInfakRepositoryInterface;
use App\Interfaces\JurnalRepositoryInterface;
use App\Models\{KotakInfak, DetailKotak};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Http\Request;

class KotakInfakRepository implements KotakInfakRepositoryInterface
{
    public function __construct(JurnalRepositoryInterface $jurnalRepo)
    {
        $this->jurnalRepo = $jurnalRepo;
    }

    public function getKotakList()
    {
        $query = KotakInfak::with(['akunPendapatan', 'details', 'media'])
                ->select('kotak_infaks.*')
                ->orderBy('tanggal', 'desc');

            return DataTables::of($query)
                ->addColumn('tanggal_group', fn($k) => $k->tanggal->format('d M Y'))
                ->addColumn('tanggal_raw', fn($k) => $k->tanggal->toDateString())
                ->addColumn('jenis', fn($k) => $k->akunPendapatan->nama ?? 'Kotak Infak') // PAKAI NAMA AKUN!
                ->addColumn('jumlah', fn($k) => 'Rp ' . number_format($k->total, 0, ',', '.'))
                ->addColumn('sudah_dihitung', fn($k) => $k->transaksi_id !== null)
                ->addColumn('detail_btn', function ($k) {
                    $buktiUrl = $k->getFirstMediaUrl('bukti_kotak') ?: null;

                    $details = $k->details->map(fn($d) => [
                        'nominal'  => (int)$d->nominal,
                        'lembar'   => (int)$d->jumlah_lembar,
                        'subtotal' => (int)$d->subtotal,
                    ])->toArray();

                    // Paksa jadi string JSON yang bersih
                    $json = json_encode([
                        'jenis'      => $k->akunPendapatan->nama ?? 'Kotak Infak',
                        'total'      => (int)$k->total,
                        'bukti'      => $buktiUrl,
                        'details'    => $details,
                        'tanggal'    => $k->tanggal->format('Y-m-d'),
                        'keterangan' => $k->keterangan ?? ''
                    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    // PAKAI single quote di HTML + htmlspecialchars agar aman
                    $jsonEscaped = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');

                    return '<button class="btn-detail detail-kotak-btn" data-kotak="'. $jsonEscaped .'">
                                Detail
                            </button>';
                })
->addColumn('tanggal_group', function ($row) {
    return $row->tanggal->translatedFormat('l, d F Y'); // Contoh: Senin, 15 Desember 2025
    // atau pakai: $row->tanggal->format('d-m-Y');
})

->addColumn('tanggal_raw', function ($row) {
    return $row->tanggal->format('Y-m-d'); // untuk data-tanggal di tombol
})

->editColumn('jumlah', function ($row) {
    return $row->total; // pastikan ini angka murni (bukan string Rp)
})
            ->addColumn('aksi', fn() => '') // kosong, tombol ada di rowGroup
            ->rawColumns(['detail_btn'])
            ->make(true);
    }

    public function hitungKotak(array $data)
    {
        $tanggal  = Carbon::parse($data['tanggal']);
        $nominals = $data['nominal'] ?? [];
        $lembars  = $data['lembar'] ?? [];
        $bukti    = $data['bukti_kotak'] ?? null; // UploadedFile atau null

        return DB::transaction(function () use ($data, $tanggal, $nominals, $lembars, $bukti) {
            // 1. Buat header kotak
            $kotak = KotakInfak::create([
                'akun_pendapatan_id' => $data['akun_pendapatan_id'],
                'tanggal'            => $tanggal->toDateString(),
                'keterangan'         => $data['keterangan'] ?? null,
                'created_by'         => $data['created_by'] ?? auth()->id(),
            ]);

            // 2. Hitung detail + total
            $total = 0;
            foreach ($nominals as $i => $nom) {
                $nom    = (float) ($nom ?? 0);
                $lembar = (int) ($lembars[$i] ?? 0);

                if ($nom > 0 && $lembar > 0) {
                    $subtotal = $nom * $lembar;
                    $total   += $subtotal;

                    DetailKotak::create([
                        'kotak_id'      => $kotak->id,
                        'nominal'       => $nom,
                        'jumlah_lembar' => $lembar,
                        'subtotal'      => $subtotal,
                    ]);
                }
            }

            $kotak->total = $total;
            $kotak->save();

            // 3. Upload bukti kalau ada
            if ($bukti) {
                $kotak->addMedia($bukti)
                      ->toMediaCollection('bukti_kotak', 'public');
            }

            // Kalau tidak ada uang masuk, tidak perlu jurnal
            if ($total <= 0) {
                $this->updateRingkasanHarianInfak($tanggal);
                return $kotak;
            }

            // 4. Jurnal otomatis
            $jenisKotak = $kotak->jenisKotak?->nama ?? 'Kotak Infak'; // pakai ?-> biar aman
            $akun       = $kotak->akunPendapatan; // relasi ke AkunKeuangan

            if (!$akun) {
                throw new \RuntimeException('Akun pendapatan untuk kotak infak belum di-set.');
            }

            $keterangan = "Penerimaan Infak dari {$jenisKotak} - " . $tanggal->format('d/m/Y');
            if (!empty($kotak->keterangan)) {
                $keterangan .= ' | ' . $kotak->keterangan;
            }

            // Panggil jurnal repo
            $this->jurnalRepo->penerimaanPemasukan(
                tanggal:          $tanggal,
                akunPendapatanId: $akun->id,
                jumlah:           $total,
                keterangan:       $keterangan,
                reference:        $kotak
            );

            // 5. Update ringkasan harian
            $this->updateRingkasanHarianInfak($tanggal);

            return $kotak;
        });
    }

    private function updateRingkasanHarianInfak($tanggal)
    {
        $kotakHarian = KotakInfak::with(['akunPendapatan'])
            ->whereDate('tanggal', $tanggal->toDateString())
            ->get();

        $totalHarian = $kotakHarian->sum('total');
        $detail = $kotakHarian->map(fn($k) => "{$k->akunPendapatan->nama}: Rp " . number_format($k->total, 0, ',', '.'))->implode(' | ');
    }
}