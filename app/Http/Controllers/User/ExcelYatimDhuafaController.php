<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\PendaftaranAnakYatimDhuafa;
use App\Services\SantunanRamadhanExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelYatimDhuafaController extends Controller
{
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
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
                $kategori = strtolower(trim((string) $sheet->getCell('A'.$i)->getFormattedValue()));
                $nama = trim((string) $sheet->getCell('B'.$i)->getFormattedValue());
                $nama_panggilan = trim((string) $sheet->getCell('C'.$i)->getFormattedValue());
                $jenis_kelamin = strtoupper(trim((string) $sheet->getCell('D'.$i)->getFormattedValue()));
                $tanggal_lahir_cell = $sheet->getCell('E'.$i)->getValue();
                $umur_excel = trim((string) $sheet->getCell('F'.$i)->getFormattedValue());
                $satuan_excel = strtolower(trim((string) $sheet->getCell('G'.$i)->getFormattedValue()));
                $nama_ortu = trim((string) $sheet->getCell('H'.$i)->getFormattedValue());
                $pekerjaan_ortu = trim((string) $sheet->getCell('I'.$i)->getFormattedValue());
                $alamat = trim((string) $sheet->getCell('J'.$i)->getFormattedValue());
                $no_wa = trim((string) $sheet->getCell('K'.$i)->getValue());
                $sumber = trim((string) $sheet->getCell('L'.$i)->getFormattedValue());
                $catatan = trim((string) $sheet->getCell('M'.$i)->getFormattedValue());
                $rt = trim((string) $sheet->getCell('N'.$i)->getFormattedValue());
                $rw = trim((string) $sheet->getCell('O'.$i)->getFormattedValue());
                $namaRt = trim((string) $sheet->getCell('P'.$i)->getFormattedValue());

                // =========================
                // PERBAIKAN NOMOR WA (EXCEL BUG FIX)
                // =========================
                if (! empty($no_wa) && ! str_starts_with($no_wa, '08')) {
                    $no_wa = '0'.$no_wa;
                }

                if (! empty($no_wa) && ! preg_match('/^08[0-9]{8,12}$/', $no_wa)) {
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

                if (! in_array($kategori, ['yatim_dhuafa', 'dhuafa'])) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Kategori harus yatim_dhuafa atau dhuafa";

                    continue;
                }

                if (! in_array($jenis_kelamin, ['L', 'P'])) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Jenis kelamin harus L atau P";

                    continue;
                }

                if (strlen($rt) > 5 || strlen($rw) > 5) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : RT dan RW maksimal 5 karakter";

                    continue;
                }

                if (strlen($namaRt) > 150) {
                    $errors[] = "Baris {$barisExcel} — {$nama} : Nama RT maksimal 150 karakter";

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
                if (! empty($tanggal_lahir_cell)) {

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

                    $umur = (int) $umur_excel;

                    if ($satuan_excel == 'tahun' && $umur > 13) {
                        $errors[] = "Baris {$barisExcel} — {$nama} : umur lebih dari 13 tahun";

                        continue;
                    }

                    if (! in_array($satuan_excel, ['tahun', 'bulan', 'hari'])) {
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
                    'rt' => $rt !== '' ? $rt : null,
                    'rw' => $rw !== '' ? $rw : null,
                    'nama_rt' => $namaRt !== '' ? $namaRt : null,
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
                    'detail' => $errors,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport {$jumlah} data anak",
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'File Excel tidak bisa dibaca / format rusak',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function export(Request $request, SantunanRamadhanExportService $exporter)
    {
        if ($request->input('export_mode') === 'all') {
            return $this->exportAll($request, $exporter);
        }

        return $this->exportBySumber($request, $exporter);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Pendaftaran');

        $headers = [
            'Kategori',
            'Nama Lengkap',
            'Nama Panggilan',
            'Jenis Kelamin',
            'Tanggal Lahir',
            'Umur',
            'Satuan Umur',
            'Nama Orang Tua / Wali',
            'Pekerjaan Orang Tua / Wali',
            'Alamat',
            'No WA',
            'Sumber Informasi',
            'Catatan Tambahan',
            'RT (Opsional)',
            'RW (Opsional)',
            'Nama RT (Opsional)',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            'yatim_dhuafa',
            'Ahmad Fulan',
            'Ahmad',
            'L',
            '',
            '10',
            'tahun',
            'Bapak Fulan',
            'Pedagang',
            'Jl. Contoh No. 1',
            '081234567890',
            'Pengurus RT',
            '',
            '006',
            '007',
            'Bapak Ketua RT',
        ], null, 'A2');

        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFD1FAE5');

        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $sheet->getStyle('N2:O1000')->getNumberFormat()->setFormatCode('@');
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'Template Import Yatim Dhuafa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportBySumber(Request $request, SantunanRamadhanExportService $exporter)
    {
        $validated = $request->validate([
            'sumber_informasi' => ['required', 'string', 'max:255', Rule::exists('pendaftaran_anak_yatim_dhuafa', 'sumber_informasi')],
        ], [
            'sumber_informasi.required' => 'Silakan pilih sumber informasi terlebih dahulu.',
            'sumber_informasi.exists' => 'Sumber informasi yang dipilih tidak memiliki data.',
        ]);

        $export = $exporter->create($validated['sumber_informasi']);

        if ($export['record_count'] === 0) {
            throw ValidationException::withMessages([
                'sumber_informasi' => 'Sumber informasi yang dipilih tidak memiliki data.',
            ]);
        }

        return response()->streamDownload(function () use ($export) {
            $writer = new Xlsx($export['spreadsheet']);
            $writer->save('php://output');
            $export['spreadsheet']->disconnectWorksheets();
        }, $export['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }

    public function exportAll(Request $request, SantunanRamadhanExportService $exporter)
    {
        $validated = $request->validate([
            'tahun_program' => ['nullable', 'integer', 'min:2000', 'max:2100'],
        ]);

        $export = $exporter->createAll($validated['tahun_program'] ?? null);

        if ($export['record_count'] === 0) {
            throw ValidationException::withMessages([
                'export_mode' => 'Belum ada data Santunan Ramadhan yang dapat diekspor.',
            ]);
        }

        return response()->streamDownload(function () use ($export) {
            $writer = new Xlsx($export['spreadsheet']);
            $writer->save('php://output');
            $export['spreadsheet']->disconnectWorksheets();
        }, $export['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }
}
