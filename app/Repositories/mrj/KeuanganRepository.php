<?php

namespace App\Repositories\mrj;

use App\Interfaces\KeuanganRepositoryInterface;
use App\Models\{SaldoAwal, JenisKotakInfak, KotakInfak, DetailKotak, KategoriKeuangan, Transaksi};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class KeuanganRepository implements KeuanganRepositoryInterface
{
    public function getKotakList()
    {
        return KotakInfak::with(['jenis_kotak', 'transaksi', 'details', 'media'])
            ->orderBy('tanggal', 'desc')
            ->get();
    }

    public function hitungSaldo($start, $end)
    {
        $start = Carbon::parse($start);
        $end = Carbon::parse($end);

        // GUNAKAN FUNGSI BARU yang aman
        $saldoInfo = $this->ambilSaldoAwal($start->copy()->startOfMonth());
        $saldoAwal = $saldoInfo['jumlah'];

        $pemasukan = Transaksi::whereHas('kategori', fn($q) => $q->where('tipe', 'pemasukan'))
            ->whereBetween('tanggal', [$start, $end])->sum('jumlah');

        $pengeluaran = Transaksi::whereHas('kategori', fn($q) => $q->where('tipe', 'pengeluaran'))
            ->whereBetween('tanggal', [$start, $end])->sum('jumlah');

        $saldoAkhir = $saldoAwal + $pemasukan - $pengeluaran;

        return compact('saldoAwal', 'pemasukan', 'pengeluaran', 'saldoAkhir');
    }

    public function ambilSaldoAwal($periode)
    {
        $periode = Carbon::parse($periode)->startOfMonth();

        // BATAS MUNDUR: Maksimal mundur sampai Januari 2020 (atau tahun mulai operasional masjid)
        $batasMundur = Carbon::create(2020, 1, 1)->startOfMonth(); // Ubah tahun sesuai kebutuhan

        // 1. Kalau sudah ada saldo awal (manual/otomatis) → langsung return
        $exist = SaldoAwal::where('periode', $periode)->first();
        if ($exist) {
            return [
                'jumlah'     => $exist->jumlah,
                'manual'     => $exist->keterangan !== 'Otomatis dari saldo akhir bulan sebelumnya',
                'keterangan' => $exist->keterangan ?? 'Manual'
            ];
        }

        // 2. Kalau periode sudah melewati batas mundur → anggap saldo awal = 0
        if ($periode->lessThan($batasMundur)) {
            // Simpan saldo 0 supaya tidak hitung ulang lagi
            $saldoNol = SaldoAwal::create([
                'periode'    => $periode,
                'jumlah'     => 0,
                'keterangan' => 'Otomatis: Belum ada data sebelum batas',
                'created_by' => Auth::id() ?? 1,
            ]);

            return [
                'jumlah'     => 0,
                'manual'     => false,
                'keterangan' => $saldoNol->keterangan
            ];
        }

        // 3. Hitung dari bulan sebelumnya (rekursif, tapi terbatas!)
        $bulanLalu       = $periode->copy()->subMonthNoOverflow()->startOfMonth();
        $akhirBulanLalu  = $bulanLalu->copy()->endOfMonth();

        $saldoAwalLalu   = $this->ambilSaldoAwal($bulanLalu)['jumlah']; // ← sekarang aman

        $pemasukan  = Transaksi::whereHas('kategori', fn($q) => $q->where('tipe', 'pemasukan'))
            ->whereBetween('tanggal', [$bulanLalu, $akhirBulanLalu])
            ->sum('jumlah');

        $pengeluaran = Transaksi::whereHas('kategori', fn($q) => $q->where('tipe', 'pengeluaran'))
            ->whereBetween('tanggal', [$bulanLalu, $akhirBulanLalu])
            ->sum('jumlah');

        $saldoAkhirLalu = $saldoAwalLalu + $pemasukan - $pengeluaran;

        // Simpan otomatis
        $baru = SaldoAwal::create([
            'periode'    => $periode,
            'jumlah'     => $saldoAkhirLalu,
            'keterangan' => 'Otomatis dari saldo akhir bulan sebelumnya',
            'created_by' => Auth::id() ?? 1,
        ]);

        return [
            'jumlah'     => $baru->jumlah,
            'manual'     => false,
            'keterangan' => $baru->keterangan
        ];
    }

    public function cekSaldoAwalManual($periode)
    {
        $periode = Carbon::parse($periode)->startOfMonth();
        $saldo = SaldoAwal::where('periode', $periode)->first();

        if (!$saldo) return false;

        return $saldo->keterangan !== 'Otomatis dari saldo akhir bulan sebelumnya';
    }

    public function simpanKoreksiSaldoAwal($periode, $jumlah, $keterangan = 'Koreksi manual')
    {
        $periode = Carbon::parse($periode)->startOfMonth();

        return SaldoAwal::updateOrCreate(
            ['periode' => $periode],
            [
                'jumlah' => $jumlah,
                'keterangan' => $keterangan,
                'created_by' => Auth::id(),
            ]
        );
    }

    public function recountHari($tanggal)
    {
        $tanggal = Carbon::parse($tanggal)->startOfDay();

        return DB::transaction(function () use ($tanggal) {
            // 1. Ambil semua kotak di tanggal ini
            $kotakHarian = KotakInfak::whereDate('tanggal', $tanggal->toDateString())
                ->with(['jenis_kotak', 'details'])
                ->get();

            if ($kotakHarian->isEmpty()) {
                throw new \Exception('Tidak ada kotak infak di tanggal ini.');
            }

            // 2. Hitung total harian
            $totalHarian = $kotakHarian->sum('total');

            // 3. Buat deskripsi yang jelas + link ke detail (opsional di log)
            $detailParts = $kotakHarian->map(fn($k) =>
                "{$k->jenis_kotak->nama}: Rp " . number_format($k->total, 0, ',', '.')
            )->implode(' | ');

            $deskripsi = "Infak Kotak Harian ({$tanggal->format('d/m/Y')}): {$detailParts} | Total: Rp " . number_format($totalHarian, 0, ',', '.') .
                         " | Detail kotak tersedia di menu Kotak Infak";

            // 4. Kategori
            $kategori = KategoriKeuangan::firstOrCreate(
                ['nama' => 'Infak Kotak'],
                ['tipe' => 'pemasukan']
            );

            // 5. Buat/update transaksi (TANPA bukti_media_id!)
            $transaksi = Transaksi::updateOrCreate(
                [
                    'tanggal'     => $tanggal->toDateString(),
                    'kategori_id' => $kategori->id
                ],
                [
                    'jumlah'     => $totalHarian,
                    'deskripsi'  => $deskripsi,
                    'created_by' => auth()->id()
                ]
            );

            // 6. Hubungkan semua kotak ke transaksi ini
            $kotakHarian->each(fn($kotak) => $kotak->update([
                'transaksi_id' => $transaksi->id
            ]));
            
            return [
                'transaksi'   => $transaksi,
                'total'       => $totalHarian,
                'kotak_count' => $kotakHarian->count(),
                'message'     => "Berhasil! Rp " . number_format($totalHarian, 0, ',', '.') .
                                " dari {$kotakHarian->count()} kotak telah dijumlahkan."
            ];
        });
    }

    public function hitungKotak(array $data)
    {
        $tanggal = isset($data['tanggal']) ? Carbon::parse($data['tanggal']) : Carbon::now();
        $nominals = $data['nominal'] ?? [];
        $lembars = $data['lembar'] ?? [];

        DB::beginTransaction();
        try {
            // 1. Buat header kotak
            $kotak = KotakInfak::create([
                'jenis_kotak_id' => $data['jenis_kotak_id'],
                'tanggal' => $tanggal->toDateString(),
                'keterangan' => $data['keterangan'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // 2. Buat detail + hitung total
            $total = 0;
            foreach ($nominals as $i => $nom) {
                $nom = (float) ($nom ?? 0);
                $lembar = (int) ($lembars[$i] ?? 0);
                if ($nom > 0 && $lembar > 0) {
                    $subtotal = $nom * $lembar;
                    $total += $subtotal;
                    DetailKotak::create([
                        'kotak_id' => $kotak->id,
                        'nominal' => $nom,
                        'jumlah_lembar' => $lembar,
                        'subtotal' => $subtotal,
                    ]);
                }
            }

            $kotak->total = $total;
            $kotak->save();

            // === PERBAIKAN UPLOAD BUKTI KOTAK ===
            if (!empty($data['bukti_kotak']) && $data['bukti_kotak'] instanceof \Illuminate\Http\UploadedFile) {
                $year = $tanggal->format('Y');
                $month = $tanggal->format('m');
                $customPath = "keuangan/bukti/{$year}/{$month}";
                $fullPath = storage_path("app/public/{$customPath}");

                // Buat folder dulu
                if (!File::exists($fullPath)) {
                    File::makeDirectory($fullPath, 0755, true, true);
                }

                // Upload via Spatie
                $media = $kotak->addMedia($data['bukti_kotak'])
                               ->preservingOriginal()
                               ->toMediaCollection('bukti_kotak', 'public');

                // Ambil file temp
                $tempPath = $media->getPath();
                $extension = $data['bukti_kotak']->getClientOriginalExtension();
                $fileName = Str::random(16) . ".{$extension}";
                $finalPath = "{$fullPath}/{$fileName}";

                // Pindahkan file
                if (File::exists($tempPath)) {
                    if (File::exists($finalPath)) {
                        File::delete($finalPath);
                    }
                    File::move($tempPath, $finalPath);

                    // Hapus folder temp jika kosong
                    $tempDir = dirname($tempPath);
                    if (is_dir($tempDir) && count(scandir($tempDir)) == 2) {
                        rmdir($tempDir);
                    }
                }

                // Simpan custom path
                $media->setCustomProperty('custom_path', "{$customPath}/{$fileName}");
                $media->save();
            }

            // 4. Ambil semua kotak harian
            $kotakHarian = KotakInfak::with('jenis_kotak')
                ->whereDate('tanggal', $tanggal->toDateString())
                ->get();

            // 5. Buat detail string
            $detailParts = [];
            foreach ($kotakHarian as $k) {
                $namaJenis = $k->jenis->nama ?? 'Tanpa Jenis';
                $detailParts[] = "{$namaJenis}: Rp " . number_format($k->total, 0, ',', '.');
            }
            $detail = implode(' | ', $detailParts);
            $totalHarian = $kotakHarian->sum('total');

            // 6. Buat/update transaksi ringkasan
            $kategori = KategoriKeuangan::firstOrCreate(
                ['nama' => 'Infak Kotak'],
                ['tipe' => 'pemasukan']
            );

            $transaksi = Transaksi::updateOrCreate(
                ['tanggal' => $tanggal->toDateString(), 'kategori_id' => $kategori->id],
                [
                    'jumlah' => $totalHarian,
                    'deskripsi' => "Infak Kotak Harian ({$tanggal->format('d/m/Y')}): {$detail} | Total: Rp " . number_format($totalHarian, 0, ',', '.'),
                    'created_by' => Auth::id()
                ]
            );

            $kotakHarian->each(function($k) use ($transaksi) {
                $k->transaksi_id = $transaksi->id;
                $k->save();
            });

            // === SALIN BUKTI KE TRANSAKSI (DIPERBAIKI) ===
            $mediaIds = [];
            foreach ($kotakHarian as $k) {
                $media = $k->getFirstMedia('bukti_kotak');
                if ($media) {
                    $mediaIds[] = $media->id;
                }
            }

            // Simpan 1 bukti utama (pertama)
            if (!empty($mediaIds)) {
                $transaksi->bukti_media_id = $mediaIds[0];
                $transaksi->save();
            }

            DB::commit();
            return $kotak;

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Error hitungKotak: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            throw $e;
        }
    }

    public function createTransaksi(array $data)
    {
        $data['created_by'] = $data['created_by'] ?? Auth::id();

        $transaksi = Transaksi::create([
            'kategori_id' => $data['kategori_id'],
            'jumlah'       => $data['jumlah'],
            'tanggal'      => $data['tanggal'],
            'deskripsi'    => $data['deskripsi'],
            'created_by'   => $data['created_by'],
        ]);

                // === 1. TENTUKAN FOLDER CUSTOM ===
        $customPath = $transaksi->getCustomPathForBukti();
        $fullPath = storage_path("app/public/{$customPath}");

        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true, true);
        }

        if (isset($data['bukti']) && $data['bukti'] instanceof \Illuminate\Http\UploadedFile) {
            $this->handleBuktiUpload($transaksi, $data['bukti']);
        }

        return $transaksi;
    }

    public function updateTransaksi($id, array $data)
    {
        $transaksi = Transaksi::findOrFail($id);
        $transaksi->update([
            'kategori_id' => $data['kategori_id'],
            'jumlah' => $data['jumlah'],
            'tanggal' => $data['tanggal'],
            'deskripsi' => $data['deskripsi'],
        ]);

        // === 1. TENTUKAN FOLDER CUSTOM ===
        $customPath = $transaksi->getCustomPathForBukti();
        $fullPath = storage_path("app/public/{$customPath}");

        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true, true);
        }

        // === 2. HAPUS BUKTI LAMA (CUSTOM + SPATIE) ===
        $oldMedia = $transaksi->getFirstMedia('bukti');
        if ($oldMedia) {
            $oldCustomPath = $oldMedia->getCustomProperty('custom_path');
            if ($oldCustomPath) {
                $oldFilePath = storage_path("app/public/{$oldCustomPath}");
                if (File::exists($oldFilePath)) {
                    File::delete($oldFilePath);
                }
            }
            $oldMedia->delete();
        }

        if (isset($data['bukti']) && $data['bukti'] instanceof \Illuminate\Http\UploadedFile) {
            $this->handleBuktiUpload($transaksi, $data['bukti']);
        }

        return $transaksi;
    }

    private function handleBuktiUpload($transaksi, $buktiFile)
    {
        // 1. Tentukan folder custom berdasarkan model
        $customPath = $transaksi->getCustomPathForBukti();
        $fullPath = storage_path("app/public/{$customPath}");

        // Buat folder jika belum ada
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true, true);
        }

        // 2. Upload sementara via Spatie Media Library
        $media = $transaksi->addMedia($buktiFile)
                           ->preservingOriginal()
                           ->toMediaCollection('bukti');

        // Ambil path sementara
        $tempPath = $media->getPath();
        $extension = $buktiFile->getClientOriginalExtension();
        $fileName = Str::random(10) . "_{$transaksi->id}.{$extension}";
        $finalPath = "{$fullPath}/{$fileName}";

        // 3. Pindahkan dari temp ke custom path
        if (File::exists($tempPath)) {
            // Hapus file jika sudah ada (jarang terjadi, tapi aman)
            if (File::exists($finalPath)) {
                File::delete($finalPath);
            }

            File::move($tempPath, $finalPath);

            // Hapus folder temp jika kosong
            $tempDir = dirname($tempPath);
            if (is_dir($tempDir) && count(scandir($tempDir)) == 2) { // hanya . dan ..
                rmdir($tempDir);
            }
        }

        // 4. Simpan custom path ke database (untuk akses nanti)
        $media->setCustomProperty('custom_path', "{$customPath}/{$fileName}");
        $media->save();

        return $media;
    }

    public function deleteTransaksi($id)
    {
        $transaksi = Transaksi::findOrFail($id);

        $media = $transaksi->getFirstMedia('bukti');
        if ($media) {
            $customPath = $media->getCustomProperty('custom_path');
            if ($customPath && File::exists(storage_path("app/public/{$customPath}"))) {
                File::delete(storage_path("app/public/{$customPath}"));
            }
            $media->delete();
        }

        if ($transaksi->bukti_media_id){
            $transaksi->bukti_media_id = null;
            $transaksi->save();
        }

        KotakInfak::where('transaksi_id', $id)->update(['transaksi_id' => null]);

        $transaksi->delete();
    }

    public function getTransaksiForDataTable($start, $end)
    {
        // Validasi: pastikan $start dan $end adalah tanggal valid
        try {
            $startDate = $start ? Carbon::parse($start) : now()->startOfMonth();
            $endDate   = $end   ? Carbon::parse($end)   : now()->endOfMonth();
        } catch (\Exception $e) {
            // Jika gagal parse (misal: 0, null, string kosong), pakai default
            $startDate = now()->startOfMonth();
            $endDate   = now()->endOfMonth();
        }

        $startDate = $startDate->startOfDay();
        $endDate   = $endDate->endOfDay();

        // Ambil saldo awal untuk periode ini
        $saldoInfo = $this->ambilSaldoAwal($startDate->copy()->startOfMonth());
        $saldoAwal = $saldoInfo['jumlah'];

        // Ambil transaksi
        $transaksi = Transaksi::with([
            'kategori', 
            'media', 
            'creator', 
            'buktiMedia'
        ])->whereBetween('tanggal', [$startDate, $endDate])
          ->orderBy('tanggal', 'asc')
          ->orderBy('id', 'asc')
          ->get();

        $saldoBerjalan = $saldoAwal;

        return $transaksi->map(function ($t) use (&$saldoBerjalan) {
            if ($t->kategori->tipe === 'pemasukan') {
                $saldoBerjalan += $t->jumlah;
            } else {
                $saldoBerjalan -= $t->jumlah;
            }

            $t->saldo_berjalan = $saldoBerjalan;
            return $t;
        });
    }
}