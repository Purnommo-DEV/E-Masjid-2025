<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\PendaftaranAnakYatimDhuafa;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\Cookie;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Style\Border;

class ExcelYatimDhuafaController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls'
        ]);

        DB::beginTransaction();

        try {

            $spreadsheet = IOFactory::load($request->file('file'));
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $jumlah = 0;
            $errors = [];

            // mulai dari baris 2 (baris 1 = header)
            for ($i = 2; $i <= $highestRow; $i++) {

                $barisExcel = $i;

                // =========================
                // AMBIL DATA DARI EXCEL
                // =========================
                $kategori           = strtolower(trim((string)$sheet->getCell('A'.$i)->getFormattedValue()));
                $nama               = trim((string)$sheet->getCell('B'.$i)->getFormattedValue());
                $nama_panggilan     = trim((string)$sheet->getCell('C'.$i)->getFormattedValue());
                $jenis_kelamin      = strtoupper(trim((string)$sheet->getCell('D'.$i)->getFormattedValue()));
                $tanggal_lahir_cell = $sheet->getCell('E'.$i)->getValue();
                $umur_excel         = trim((string)$sheet->getCell('F'.$i)->getFormattedValue());
                $satuan_excel       = strtolower(trim((string)$sheet->getCell('G'.$i)->getFormattedValue()));
                $nama_ortu          = trim((string)$sheet->getCell('H'.$i)->getFormattedValue());
                $pekerjaan_ortu     = trim((string)$sheet->getCell('I'.$i)->getFormattedValue());
                $alamat             = trim((string)$sheet->getCell('J'.$i)->getFormattedValue());
                $no_wa              = trim((string)$sheet->getCell('K'.$i)->getValue());
                $sumber             = trim((string)$sheet->getCell('L'.$i)->getFormattedValue());
                $catatan            = trim((string)$sheet->getCell('M'.$i)->getFormattedValue());

                // =========================
                // PERBAIKAN NOMOR WA (EXCEL BUG FIX)
                // =========================
                if (!empty($no_wa) && !str_starts_with($no_wa, '08')) {
                    $no_wa = '0'.$no_wa;
                }

                if (!empty($no_wa) && !preg_match('/^08[0-9]{8,12}$/', $no_wa)) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Nomor WA tidak valid";
                    continue;
                }

                // =========================
                // VALIDASI DASAR
                // =========================
                if (empty($nama)) {
                    $errors[] = "Baris {$barisExcel} : Nama lengkap kosong";
                    continue;
                }

                if (empty($alamat)) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Alamat wajib diisi";
                    continue;
                }

                if (!in_array($kategori, ['yatim_dhuafa','dhuafa'])) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Kategori harus yatim_dhuafa atau dhuafa";
                    continue;
                }

                if (!in_array($jenis_kelamin, ['L','P'])) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Jenis kelamin harus L atau P";
                    continue;
                }

                // =========================
                // LOGIKA UMUR
                // =========================
                $tglLahir = null;
                $umur = null;
                $satuan = null;

                /*
                ==========================================
                MODE 1 — PAKAI TANGGAL LAHIR
                ==========================================
                */
                if (!empty($tanggal_lahir_cell)) {

                    try {
                        if (is_numeric($tanggal_lahir_cell)) {
                            $tglLahir = Carbon::instance(
                                ExcelDate::excelToDateTimeObject($tanggal_lahir_cell)
                            );
                        } else {
                            $tglLahir = Carbon::createFromFormat('d/m/Y', $tanggal_lahir_cell);
                        }
                    } catch (\Exception $e) {
                        $errors[] = "Baris {$barisExcel} — {$nama} : Format tanggal salah (gunakan dd/mm/yyyy)";
                        continue;
                    }

                    if ($tglLahir->isFuture()) {
                        $errors[] = "Baris {$barisExcel} — {$nama} : Tanggal lahir di masa depan";
                        continue;
                    }

                    $diff = $tglLahir->diff(now());

                    // Batas maksimal 13 tahun 11 bulan 30 hari
                    if ($diff->y >= 14) {
                        $errors[] = "Baris {$barisExcel} — {$nama} usia {$diff->y} tahun {$diff->m} bulan {$diff->d} hari (MELEBIHI BATAS 13 TAHUN)";
                        continue;
                    }

                    if ($diff->y > 0) {
                        $umur = $diff->y;
                        $satuan = 'tahun';
                    } elseif ($diff->m > 0) {
                        $umur = $diff->m;
                        $satuan = 'bulan';
                    } else {
                        $umur = max($diff->d, 1);
                        $satuan = 'hari';
                    }
                }

                /*
                ==========================================
                MODE 2 — UMUR MANUAL
                ==========================================
                */
                else {

                    if (empty($umur_excel) || empty($satuan_excel)) {
                        $errors[] = "Baris {$barisExcel} — {$nama} : Isi tanggal lahir ATAU umur + satuan";
                        continue;
                    }

                    $umur = (int)$umur_excel;

                    if ($satuan_excel == 'tahun' && $umur > 13) {
                        $errors[] = "Baris {$barisExcel} — {$nama} : umur lebih dari 13 tahun";
                        continue;
                    }

                    if (!in_array($satuan_excel, ['tahun','bulan','hari'])) {
                        $errors[] = "Baris {$barisExcel} — {$nama} : satuan umur tidak valid";
                        continue;
                    }

                    $satuan = $satuan_excel;
                }

                // =========================
                // SIMPAN DATA
                // =========================
                PendaftaranAnakYatimDhuafa::create([
                    'kategori' => $kategori,
                    'nama_lengkap' => $nama,
                    'nama_panggilan' => $nama_panggilan,
                    'tanggal_lahir' => $tglLahir,
                    'umur' => $umur,
                    'umur_satuan' => $satuan,
                    'jenis_kelamin' => $jenis_kelamin,
                    'alamat' => $alamat,
                    'nama_orang_tua' => $nama_ortu,
                    'pekerjaan_orang_tua' => $pekerjaan_ortu,
                    'no_wa' => $no_wa,
                    'sumber_informasi' => $sumber,
                    'catatan_tambahan' => $catatan,
                    'tahun_program' => now()->year,
                    'ip_address' => $request->ip(),
                ]);

                $jumlah++;
            }

            // =========================
            // JIKA ADA ERROR → BATAL SEMUA
            // =========================
            if (count($errors) > 0) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Import dibatalkan karena ada data tidak valid',
                    'detail' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport {$jumlah} data anak"
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'File Excel tidak bisa dibaca / format rusak',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function export(Request $request)
    {
        $query = PendaftaranAnakYatimDhuafa::query();

        // Filter sesuai dengan datatable
        if ($request->tahun) {
            $query->where('tahun_program', $request->tahun);
        }
        if ($request->umur_value && $request->umur_satuan) {
            $query->where('umur', $request->umur_value)
                  ->where('umur_satuan', $request->umur_satuan);
        }
        if ($request->jenis_kelamin) {
            $query->where('jenis_kelamin', $request->jenis_kelamin);
        }
        if ($request->kategori) {
            $query->where('kategori', $request->kategori);
        }
        if ($request->search) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('nama_lengkap', 'like', $search)
                  ->orWhere('nama_orang_tua', 'like', $search)
                  ->orWhere('alamat', 'like', $search)
                  ->orWhere('sumber_informasi', 'like', $search);
            });
        }

        $allData = $query->get();

        // Pisahkan data berdasarkan kategori
        $yatimDataRaw  = $allData->where('kategori', 'yatim_dhuafa');
        $dhuafaDataRaw = $allData->where('kategori', 'dhuafa');

        // Inisialisasi Spreadsheet (ini yang wajib ada!)
        $spreadsheet = new Spreadsheet();

        // Warna cycle untuk group sumber_informasi (ARGB, opacity rendah ~20%)
        $groupColors = [
            '00E3F2FD', // light blue
            '00E8F5E9', // light green
            '00FFFDE7', // light yellow
            '00F3E5F5', // light purple
            '00E0F7FA', // light cyan
            '00FFECB3', // light amber
        ];

        // =====================================
        // SHEET 1: YATIM
        // =====================================
        $sheetYatim = $spreadsheet->getActiveSheet();
        $sheetYatim->setTitle('Yatim');

        // Judul
        $sheetYatim->setCellValue('A1', 'LAPORAN DATA ANAK YATIM');
        $sheetYatim->mergeCells('A1:N1');
        $sheetYatim->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheetYatim->setCellValue('A2', 'Santunan Ramadhan ' . now()->year);
        $sheetYatim->mergeCells('A2:N2');

        // Header (14 kolom sesuai tabel blade)
        $headers = [
            'No',
            'Penanggung Jawab Informasi',
            'No WA',
            'Kategori',
            'Nama Anak',
            'Panggilan',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Umur',
            'Nama Orang Tua / Wali',
            'Pekerjaan Orang Tua / Wali',
            'Alamat Lengkap',
            'Keterangan Tambahan',
            'Tahun'
        ];

        $col = 'A';
        foreach ($headers as $header) {
            $sheetYatim->setCellValue($col . '4', $header);
            $sheetYatim->getStyle($col . '4')->getFont()->setBold(true);
            $sheetYatim->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Grouping & sorting Yatim
        $groupedYatim = $yatimDataRaw
            ->groupBy('sumber_informasi')
            ->map(fn($group) => $group->sortBy('nama_lengkap'))
            ->sortKeys(); // urutkan group berdasarkan nama sumber alfabet

        // Isi data Yatim + warna per group
        $row = 5;
        $no = 1;
        $colorIndex = 0;
        foreach ($groupedYatim as $sumber => $groupData) {
            $color = $groupColors[$colorIndex % count($groupColors)];
            $colorIndex++;

            foreach ($groupData as $d) {
                $umurLengkap = $d->umur . ' ' . ucfirst($d->umur_satuan ?? 'tahun');

                $sheetYatim->setCellValue('A' . $row, $no++);
                $sheetYatim->setCellValue('B' . $row, $d->sumber_informasi);
                $sheetYatim->setCellValue('C' . $row, $d->no_wa);
                $sheetYatim->setCellValue('D' . $row, 'Yatim Dhuafa');
                $sheetYatim->setCellValue('E' . $row, $d->nama_lengkap);
                $sheetYatim->setCellValue('F' . $row, $d->nama_panggilan);
                $sheetYatim->setCellValue('G' . $row, $d->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan');
                $sheetYatim->setCellValue('H' . $row, optional($d->tanggal_lahir)->format('d/m/Y'));
                $sheetYatim->setCellValue('I' . $row, $umurLengkap);
                $sheetYatim->setCellValue('J' . $row, $d->nama_orang_tua);
                $sheetYatim->setCellValue('K' . $row, $d->pekerjaan_orang_tua);
                $sheetYatim->setCellValue('L' . $row, $d->alamat);
                $sheetYatim->setCellValue('M' . $row, $d->catatan_tambahan);
                $sheetYatim->setCellValue('N' . $row, $d->tahun_program);

                // Warna background baris ini
                $sheetYatim->getStyle("A{$row}:N{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($color);

                $row++;
            }
        }

        // Border keseluruhan
        if ($yatimDataRaw->isNotEmpty()) {
            $sheetYatim->getStyle("A4:N" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // =====================================
        // SHEET 2: DHUAFA
        // =====================================
        $sheetDhuafa = $spreadsheet->createSheet();
        $sheetDhuafa->setTitle('Dhuafa');

        // Judul
        $sheetDhuafa->setCellValue('A1', 'LAPORAN DATA ANAK DHUAFA');
        $sheetDhuafa->mergeCells('A1:N1');
        $sheetDhuafa->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheetDhuafa->setCellValue('A2', 'Santunan Ramadhan ' . now()->year);
        $sheetDhuafa->mergeCells('A2:N2');

        // Header sama
        $col = 'A';
        foreach ($headers as $header) {
            $sheetDhuafa->setCellValue($col . '4', $header);
            $sheetDhuafa->getStyle($col . '4')->getFont()->setBold(true);
            $sheetDhuafa->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Grouping & sorting Dhuafa
        $groupedDhuafa = $dhuafaDataRaw
            ->groupBy('sumber_informasi')
            ->map(fn($group) => $group->sortBy('nama_lengkap'))
            ->sortKeys();

        // Isi data Dhuafa + warna
        $row = 5;
        $no = 1;
        $colorIndex = 0;
        foreach ($groupedDhuafa as $sumber => $groupData) {
            $color = $groupColors[$colorIndex % count($groupColors)];
            $colorIndex++;

            foreach ($groupData as $d) {
                $umurLengkap = $d->umur . ' ' . ucfirst($d->umur_satuan ?? 'tahun');

                $sheetDhuafa->setCellValue('A' . $row, $no++);
                $sheetDhuafa->setCellValue('B' . $row, $d->sumber_informasi);
                $sheetDhuafa->setCellValue('C' . $row, $d->no_wa);
                $sheetDhuafa->setCellValue('D' . $row, 'Dhuafa');
                $sheetDhuafa->setCellValue('E' . $row, $d->nama_lengkap);
                $sheetDhuafa->setCellValue('F' . $row, $d->nama_panggilan);
                $sheetDhuafa->setCellValue('G' . $row, $d->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan');
                $sheetDhuafa->setCellValue('H' . $row, optional($d->tanggal_lahir)->format('d/m/Y'));
                $sheetDhuafa->setCellValue('I' . $row, $umurLengkap);
                $sheetDhuafa->setCellValue('J' . $row, $d->nama_orang_tua);
                $sheetDhuafa->setCellValue('K' . $row, $d->pekerjaan_orang_tua);
                $sheetDhuafa->setCellValue('L' . $row, $d->alamat);
                $sheetDhuafa->setCellValue('M' . $row, $d->catatan_tambahan);
                $sheetDhuafa->setCellValue('N' . $row, $d->tahun_program);

                // Warna background
                $sheetDhuafa->getStyle("A{$row}:N{$row}")
                    ->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()
                    ->setARGB($color);

                $row++;
            }
        }

        // Border
        if ($dhuafaDataRaw->isNotEmpty()) {
            $sheetDhuafa->getStyle("A4:N" . ($row - 1))
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        }

        // Aktifkan sheet pertama saat dibuka
        $spreadsheet->setActiveSheetIndex(0);

        // Download
        $fileName = 'Laporan_Yatim_Dhuafa_Grouped_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }
    public function downloadTemplate()
    {
        $path = public_path('template/template_import_yatim_dhuafa.xlsx');

        if (!file_exists($path)) {
            abort(404, 'Template tidak ditemukan');
        }

        return response()->download($path, 'Template Import Yatim Dhuafa.xlsx');
    }

    public function exportBySumber(Request $request)
    {
        $request->validate([
            'sumber_informasi' => 'required'
        ]);

        $sumber = $request->sumber_informasi;

        $data = PendaftaranAnakYatimDhuafa::where('sumber_informasi', $sumber)
            ->orderBy('jenis_kelamin') // L dulu
            ->orderBy('nama_lengkap')  // abjad
            ->get();

        if ($data->isEmpty()) {
            return back()->with('error', 'Data tidak ditemukan');
        }

        $spreadsheet = new Spreadsheet();

        // ================================
        // FUNCTION BUAT SHEET
        // ================================
        $buatSheet = function($sheet, $collection, $judul) {

            $sheet->setCellValue('A1', $judul);
            $sheet->mergeCells('A1:I1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $headers = [
                'No',
                'Nama Anak',
                'Jenis Kelamin',
                'Tanggal Lahir',
                'Umur',
                'Nama Orang Tua',
                'Alamat',
                'Kategori',
                'Tahun'
            ];

            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col.'3', $header);
                $sheet->getStyle($col.'3')->getFont()->setBold(true);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }

            $row = 4;
            $no  = 1;

            foreach ($collection as $d) {

                $umur = $d->umur . ' ' . ucfirst($d->umur_satuan);

                $sheet->setCellValue('A'.$row, $no++);
                $sheet->setCellValue('B'.$row, $d->nama_lengkap);
                $sheet->setCellValue('C'.$row, $d->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan');
                $sheet->setCellValue('D'.$row, optional($d->tanggal_lahir)->format('d/m/Y'));
                $sheet->setCellValue('E'.$row, $umur);
                $sheet->setCellValue('F'.$row, $d->nama_orang_tua);
                $sheet->setCellValue('G'.$row, $d->alamat);
                $sheet->setCellValue('H'.$row, $d->kategori == 'dhuafa' ? 'Dhuafa' : 'Yatim Dhuafa');
                $sheet->setCellValue('I'.$row, $d->tahun_program);

                $row++;
            }

            $sheet->getStyle('A3:I'.($row-1))
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);
        };

        // ================================
        // SHEET 1 — DHUAFA
        // ================================
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Dhuafa');

        $buatSheet(
            $sheet1,
            $data->where('kategori','dhuafa'),
            "DATA DHUAFA - {$sumber}"
        );

        // ================================
        // SHEET 2 — YATIM DHUAFA
        // ================================
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Yatim Dhuafa');

        $buatSheet(
            $sheet2,
            $data->where('kategori','yatim_dhuafa'),
            "DATA YATIM DHUAFA - {$sumber}"
        );

        // ================================
        // SHEET 3 — GABUNGAN
        // ================================
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Gabungan');

        $buatSheet(
            $sheet3,
            $data,
            "DATA GABUNGAN - {$sumber}"
        );

        // ================================
        // FILE NAME
        // ================================
        $safeName = Str::slug($sumber, '_');
        $fileName = "{$safeName}_yatim_dhuafa.xlsx";

        return new StreamedResponse(function() use ($spreadsheet){
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'max-age=0',
        ]);
    }


}