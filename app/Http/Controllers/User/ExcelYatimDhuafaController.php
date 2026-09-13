<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\SantunanParticipation;
use App\Models\SantunanPerson;
use App\Services\SantunanIdentityMatcher;
use App\Services\SantunanRamadhanExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Throwable;

class ExcelYatimDhuafaController extends Controller
{
    public function import(Request $request, SantunanIdentityMatcher $matcher): JsonResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'mimes:xlsx,xls'],
            'tahun_program' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);
        $targetYear = (int) $validated['tahun_program'];

        try {
            $sheet = IOFactory::load($request->file('file'))->getActiveSheet();
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'File Excel tidak bisa dibaca atau format rusak.',
                'error' => $exception->getMessage(),
            ], 422);
        }

        DB::beginTransaction();
        $created = 0;
        $reused = 0;
        $errors = [];
        $review = [];

        try {
            for ($rowNumber = 2; $rowNumber <= $sheet->getHighestRow(); $rowNumber++) {
                if ($this->rowIsBlank($sheet, $rowNumber)) {
                    continue;
                }

                $row = $this->readImportRow($sheet, $rowNumber, $targetYear, $errors);
                if ($row === null) {
                    continue;
                }

                $match = $matcher->match($row);
                if ($match['status'] === 'ambiguous') {
                    $errors[] = "Baris {$rowNumber} — {$row['nama_lengkap']}: identitas ambigu dan memerlukan review";
                    $review[] = ['row' => $rowNumber, 'input' => $row, 'candidates' => $match['candidates']];

                    continue;
                }

                $person = $match['person'] ?: SantunanPerson::query()->create([
                    'nama_lengkap' => $row['nama_lengkap'],
                    'nama_panggilan' => $row['nama_panggilan'],
                    'tanggal_lahir' => $row['tanggal_lahir'],
                    'jenis_kelamin' => $row['jenis_kelamin'],
                ]);

                if (SantunanParticipation::query()->where('person_id', $person->id)->where('tahun_program', $targetYear)->exists()) {
                    $errors[] = "Baris {$rowNumber} — {$row['nama_lengkap']}: orang ini sudah terdaftar pada tahun {$targetYear}";

                    continue;
                }

                SantunanParticipation::query()->create(collect($row)->except([
                    'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin',
                ])->all() + [
                    'person_id' => $person->id,
                    'ip_address' => $request->ip(),
                ]);
                $created++;
                $reused += $match['status'] === 'matched' ? 1 : 0;
            }

            if ($errors !== []) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Import dibatalkan karena ada data tidak valid atau duplikat.',
                    'detail' => $errors,
                    'review' => $review,
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Berhasil mengimport {$created} participation untuk tahun {$targetYear}.",
                'created' => $created,
                'identities_reused' => $reused,
                'tahun_program' => $targetYear,
            ]);
        } catch (Throwable $exception) {
            DB::rollBack();
            throw $exception;
        }
    }

    public function export(Request $request, SantunanRamadhanExportService $exporter)
    {
        return $request->input('export_mode') === 'all'
            ? $this->exportAll($request, $exporter)
            : $this->exportBySumber($request, $exporter);
    }

    public function downloadTemplate()
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Pendaftaran');
        $headers = [
            'Kategori', 'Nama Lengkap', 'Nama Panggilan', 'Jenis Kelamin', 'Tanggal Lahir', 'Umur',
            'Satuan Umur', 'Nama Orang Tua / Wali', 'Pekerjaan Orang Tua / Wali', 'Alamat', 'No WA',
            'Sumber Informasi', 'Catatan Tambahan', 'RT (Opsional)', 'RW (Opsional)', 'Nama RT (Opsional)',
        ];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->fromArray([
            'yatim_dhuafa', 'Ahmad Fulan', 'Ahmad', 'L', '', '10', 'tahun', 'Bapak Fulan', 'Pedagang',
            'Jl. Contoh No. 1', '081234567890', 'Pengurus RT', '', '006', '007', 'Bapak Ketua RT',
        ], null, 'A2');
        $sheet->getStyle('A1:P1')->getFont()->setBold(true);
        $sheet->getStyle('A1:P1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD1FAE5');
        foreach (range('A', 'P') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        $sheet->getStyle('N2:O1000')->getNumberFormat()->setFormatCode('@');
        $sheet->freezePane('A2');

        return response()->streamDownload(function () use ($spreadsheet): void {
            (new Xlsx($spreadsheet))->save('php://output');
        }, 'Template Import Yatim Dhuafa.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    public function exportBySumber(Request $request, SantunanRamadhanExportService $exporter)
    {
        $validated = $request->validate([
            'tahun_program' => ['required', 'integer', 'min:2000', 'max:2100'],
            'sumber_informasi' => [
                'required', 'string', 'max:255',
                Rule::exists('santunan_participations', 'sumber_informasi')
                    ->where(fn ($query) => $query->where('tahun_program', $request->integer('tahun_program'))),
            ],
        ], [
            'sumber_informasi.required' => 'Silakan pilih sumber informasi terlebih dahulu.',
            'sumber_informasi.exists' => 'Sumber informasi tidak memiliki data pada tahun yang dipilih.',
        ]);

        $export = $exporter->create($validated['sumber_informasi'], (int) $validated['tahun_program']);

        if ($export['record_count'] === 0) {
            throw ValidationException::withMessages(['sumber_informasi' => 'Sumber informasi tidak memiliki data pada tahun yang dipilih.']);
        }

        return $this->downloadExport($export);
    }

    public function exportAll(Request $request, SantunanRamadhanExportService $exporter)
    {
        $validated = $request->validate([
            'tahun_program' => ['required', 'integer', 'min:2000', 'max:2100'],
        ]);
        $export = $exporter->createAll((int) $validated['tahun_program']);

        if ($export['record_count'] === 0) {
            throw ValidationException::withMessages(['export_mode' => 'Belum ada data pada tahun yang dipilih.']);
        }

        return $this->downloadExport($export);
    }

    private function downloadExport(array $export)
    {
        return response()->streamDownload(function () use ($export): void {
            (new Xlsx($export['spreadsheet']))->save('php://output');
            $export['spreadsheet']->disconnectWorksheets();
        }, $export['filename'], [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
            'Pragma' => 'public',
        ]);
    }

    private function readImportRow($sheet, int $rowNumber, int $targetYear, array &$errors): ?array
    {
        $value = fn (string $column) => trim((string) $sheet->getCell($column.$rowNumber)->getFormattedValue());
        $kategori = strtolower($value('A'));
        $nama = $value('B');
        $gender = strtoupper($value('D'));
        $alamat = $value('J');
        $phone = trim((string) $sheet->getCell('K'.$rowNumber)->getValue());
        $rt = $value('N');
        $rw = $value('O');
        $coordinator = $value('P');

        if ($phone !== '' && ! str_starts_with($phone, '08')) {
            $phone = '0'.$phone;
        }
        if ($nama === '' || $alamat === '' || ! in_array($kategori, SantunanParticipation::CATEGORIES, true)
            || ! in_array($gender, ['L', 'P'], true) || strlen($rt) > 5 || strlen($rw) > 5
            || strlen($coordinator) > 150 || ($phone !== '' && ! preg_match('/^08[0-9]{8,12}$/', $phone))) {
            $errors[] = "Baris {$rowNumber} — {$nama}: data wajib, kategori, JK, wilayah, atau nomor WA tidak valid";

            return null;
        }

        $birth = null;
        $birthCell = $sheet->getCell('E'.$rowNumber)->getValue();
        if (filled($birthCell)) {
            try {
                $birth = is_numeric($birthCell)
                    ? Carbon::instance(ExcelDate::excelToDateTimeObject($birthCell))
                    : Carbon::createFromFormat('d/m/Y', (string) $birthCell);
            } catch (Throwable) {
                $errors[] = "Baris {$rowNumber} — {$nama}: format tanggal salah (gunakan dd/mm/yyyy)";

                return null;
            }
            if ($birth->isFuture() || $birth->diff(now())->y >= 14) {
                $errors[] = "Baris {$rowNumber} — {$nama}: tanggal lahir atau usia tidak valid";

                return null;
            }
            $diff = $birth->diff(now());
            [$age, $unit] = $diff->y > 0 ? [$diff->y, 'tahun'] : ($diff->m > 0 ? [$diff->m, 'bulan'] : [max($diff->d, 1), 'hari']);
        } else {
            $age = (int) $value('F');
            $unit = strtolower($value('G'));
            if ($age < 0 || ! in_array($unit, ['tahun', 'bulan', 'hari'], true) || ($unit === 'tahun' && $age > 13)) {
                $errors[] = "Baris {$rowNumber} — {$nama}: isi tanggal lahir atau umur dan satuan yang valid";

                return null;
            }
        }

        return [
            'tahun_program' => $targetYear,
            'kategori' => $kategori,
            'nama_lengkap' => $nama,
            'nama_panggilan' => $value('C') ?: null,
            'tanggal_lahir' => $birth?->toDateString(),
            'umur' => $age,
            'umur_satuan' => $unit,
            'jenis_kelamin' => $gender,
            'alamat' => $alamat,
            'rt' => $rt ?: null,
            'rw' => $rw ?: null,
            'nama_rt' => $coordinator ?: null,
            'nama_orang_tua' => $value('H'),
            'pekerjaan_orang_tua' => $value('I') ?: null,
            'no_wa' => $phone ?: null,
            'sumber_informasi' => $value('L') ?: null,
            'catatan_tambahan' => $value('M') ?: null,
        ];
    }

    private function rowIsBlank($sheet, int $rowNumber): bool
    {
        foreach (range('A', 'P') as $column) {
            if (trim((string) $sheet->getCell($column.$rowNumber)->getValue()) !== '') {
                return false;
            }
        }

        return true;
    }
}
