<?php

namespace App\Services;

use App\Models\PendaftaranAnakYatimDhuafa;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IntlDateFormatter;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SantunanRamadhanExportService
{
    private const LAST_COLUMN = 'H';

    private const SHEETS = [
        'dhuafa' => 'DHUAFA',
        'yatim_dhuafa' => 'YATIM YANG DHUAFA',
    ];

    /**
     * @return array{spreadsheet: Spreadsheet, filename: string, record_count: int}
     */
    public function create(string $source): array
    {
        $records = PendaftaranAnakYatimDhuafa::query()
            ->where('sumber_informasi', $source)
            ->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Data Peserta Santunan Yatim Dhuafa')
            ->setSubject('Santunan Ramadhan - '.$source);

        $periodHeading = $this->periodHeading($records);
        $couponNumber = 1;

        foreach (self::SHEETS as $category => $sheetTitle) {
            $sheet = $category === array_key_first(self::SHEETS)
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();
            $sheet->setTitle($sheetTitle);
            $this->configureSheet($sheet);

            $categoryRecords = $records
                ->whereStrict('kategori', $category)
                ->values();
            $lastRow = $this->writeReport(
                $sheet,
                $categoryRecords,
                $source,
                $periodHeading,
                $couponNumber,
            );
            $this->configurePrintLayout($sheet, $lastRow);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->filename($source, $records),
            'record_count' => $records->count(),
        ];
    }

    /**
     * @return array{spreadsheet: Spreadsheet, filename: string, record_count: int, source_count: int}
     */
    public function createAll(?int $year = null): array
    {
        $year ??= now()->year;
        $records = PendaftaranAnakYatimDhuafa::query()
            ->where('tahun_program', $year)
            ->whereIn('kategori', array_keys(self::SHEETS))
            ->whereNotNull('sumber_informasi')
            ->where('sumber_informasi', '!=', '')
            ->orderByRaw('CASE WHEN sumber_informasi = ? THEN 0 ELSE 1 END', ['Pak Indra'])
            ->orderBy('sumber_informasi')
            ->get();

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getProperties()
            ->setCreator(config('app.name'))
            ->setTitle('Data Peserta Santunan Yatim Dhuafa - Semua Sumber')
            ->setSubject('Santunan Ramadhan - Semua Sumber');

        $couponNumber = 1;
        $usedSheetNames = [];
        $sources = $records->pluck('sumber_informasi')->uniqueStrict()->values();

        foreach ($sources as $sourceIndex => $source) {
            $sourceRecords = $records->whereStrict('sumber_informasi', $source)->values();
            $sheet = $sourceIndex === 0
                ? $spreadsheet->getActiveSheet()
                : $spreadsheet->createSheet();
            $sheet->setTitle($this->uniqueSheetName($source, $usedSheetNames));
            $this->configureSheet($sheet);
            $lastRow = $this->writeCombinedReport(
                $sheet,
                $sourceRecords,
                $source,
                $this->periodHeading($sourceRecords),
                $couponNumber,
            );
            $this->configurePrintLayout($sheet, $lastRow);
        }

        $spreadsheet->setActiveSheetIndex(0);

        return [
            'spreadsheet' => $spreadsheet,
            'filename' => $this->allSourcesFilename($records),
            'record_count' => $records->count(),
            'source_count' => $sources->count(),
        ];
    }

    private function configureSheet(Worksheet $sheet): void
    {
        $sheet->setShowGridlines(false);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);
        $sheet->getParent()->getDefaultStyle()->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        foreach ([
            'A' => 12,
            'B' => 7,
            'C' => 30,
            'D' => 9,
            'E' => 17,
            'F' => 16,
            'G' => 28,
            'H' => 48,
        ] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    private function configurePrintLayout(Worksheet $sheet, int $lastRow): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()
            ->setTop(0.5)
            ->setRight(0.35)
            ->setBottom(0.5)
            ->setLeft(0.35);
        $sheet->getHeaderFooter()->setOddFooter('Halaman &P dari &N');
        $sheet->getPageSetup()->setPrintArea('A1:'.self::LAST_COLUMN.$lastRow);
    }

    private function writeReport(
        Worksheet $sheet,
        Collection $records,
        string $source,
        string $periodHeading,
        int &$couponNumber,
    ): int {
        $this->writeSheetHeader($sheet, $source, $periodHeading);

        if ($records->isEmpty()) {
            $this->writeEmptyMessage($sheet, 8);

            return 8;
        }

        return $this->writeRegionTables($sheet, $records, 8, $couponNumber) - 1;
    }

    private function writeCombinedReport(
        Worksheet $sheet,
        Collection $records,
        string $source,
        string $periodHeading,
        int &$couponNumber,
    ): int {
        $this->writeSheetHeader($sheet, $source, $periodHeading);
        $row = 8;

        foreach (self::SHEETS as $categoryIndex => $categoryLabel) {
            $categoryRecords = $records->whereStrict('kategori', $categoryIndex)->values();
            $this->writeSectionRow(
                $sheet,
                $row++,
                $categoryLabel.' — Total: '.$categoryRecords->count(),
                'FFDDEBF7',
                12,
            );

            if ($categoryRecords->isEmpty()) {
                $this->writeEmptyMessage($sheet, $row++);
            } else {
                $row = $this->writeRegionTables($sheet, $categoryRecords, $row, $couponNumber);
            }

            if ($categoryIndex !== array_key_last(self::SHEETS)) {
                $row += 2;
            }
        }

        return $row - 1;
    }

    private function writeSheetHeader(Worksheet $sheet, string $source, string $periodHeading): void
    {
        $this->writeMergedRow($sheet, 1, 'Data Peserta', 14, true);
        $this->writeMergedRow($sheet, 2, 'Santunan Yatim Dhuafa', 14, true);
        $this->writeMergedRow($sheet, 3, masjid_name(), 11, true);
        $this->writeMergedRow($sheet, 4, $periodHeading, 10, false);
        $this->writeMergedRow($sheet, 6, 'Sumber Informasi - '.$source, 11, true);
    }

    private function writeRegionTables(
        Worksheet $sheet,
        Collection $records,
        int $row,
        int &$couponNumber,
    ): int {
        $groups = $this->sortedRegionGroups($records);

        foreach ($groups as $groupIndex => $groupRecords) {
            $first = $groupRecords->first();
            $this->writeSectionRow(
                $sheet,
                $row++,
                $this->regionHeading($first->rt, $first->rw, $first->nama_rt),
                'FFF2F2F2',
                10,
            );
            $this->writeTotalRow($sheet, $row++, $groupRecords->count());

            $headerRow = $row++;
            $sheet->fromArray([
                'NO. KUPON',
                'NO.',
                'NAMA ANAK',
                'JK',
                'TANGGAL LAHIR',
                'UMUR',
                'NAMA ORANG TUA',
                'ALAMAT',
            ], null, 'A'.$headerRow);
            $this->styleTableHeader($sheet, $headerRow);

            $firstDataRow = $row;
            foreach ($this->sortedRecipients($groupRecords) as $localNumber => $record) {
                $sheet->setCellValue('A'.$row, $couponNumber++);
                $sheet->setCellValue('B'.$row, $localNumber + 1);
                $sheet->setCellValue('C'.$row, $record->nama_lengkap ?: 'Belum diisi');
                $sheet->setCellValue('D'.$row, $record->jenis_kelamin ?: 'Belum diisi');

                if ($record->tanggal_lahir) {
                    $sheet->setCellValue('E'.$row, ExcelDate::PHPToExcel($record->tanggal_lahir));
                    $sheet->getStyle('E'.$row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                } else {
                    $sheet->setCellValue('E'.$row, 'Belum diisi');
                }

                $sheet->setCellValue('F'.$row, $this->formatAge($record));
                $sheet->setCellValue('G'.$row, $record->nama_orang_tua ?: 'Belum diisi');
                $sheet->setCellValue('H'.$row, $record->alamat ?: 'Belum diisi');
                $row++;
            }

            $this->styleTableBody($sheet, $firstDataRow, $row - 1);

            if ($groupIndex < $groups->count() - 1) {
                $row += 2;
            }
        }

        return $row;
    }

    private function periodHeading(Collection $records): string
    {
        $years = $records->pluck('tahun_program')->filter()->map(fn ($year) => (int) $year)->unique()->sort()->values();

        return $years
            ->map(fn (int $year) => "Ramadhan {$this->ramadanHijriYear($year)} H / {$year} M")
            ->implode(', ');
    }

    private function ramadanHijriYear(int $gregorianYear): int
    {
        $formatter = new IntlDateFormatter(
            'en_US@calendar=islamic',
            IntlDateFormatter::NONE,
            IntlDateFormatter::NONE,
            config('app.timezone'),
            IntlDateFormatter::TRADITIONAL,
            'yyyy-M-d',
        );
        $date = CarbonImmutable::create($gregorianYear, 1, 1, 12, timezone: config('app.timezone'));

        while ($date->year === $gregorianYear) {
            [$hijriYear, $hijriMonth, $hijriDay] = array_map('intval', explode('-', $formatter->format($date->timestamp)));

            if ($hijriMonth === 9 && $hijriDay === 1) {
                return $hijriYear;
            }

            $date = $date->addDay();
        }

        throw new \RuntimeException("Tahun Ramadhan tidak ditemukan untuk {$gregorianYear}.");
    }

    private function regionHeading(?string $rt, ?string $rw, ?string $coordinator): string
    {
        $rtLabel = $this->displayGroupValue($rt);
        $rwLabel = $this->displayGroupValue($rw);
        $heading = "RT {$rtLabel} / RW {$rwLabel}";

        if (filled($coordinator)) {
            return $heading.' — '.$coordinator;
        }

        return $rtLabel === 'Belum diisi' && $rwLabel === 'Belum diisi'
            ? $heading.' — Belum diisi'
            : $heading;
    }

    private function writeMergedRow(Worksheet $sheet, int $row, string $value, int $fontSize, bool $bold): void
    {
        $sheet->setCellValue('A'.$row, $value);
        $sheet->mergeCells('A'.$row.':'.self::LAST_COLUMN.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold($bold)->setSize($fontSize);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight($fontSize + 8);
    }

    private function writeSectionRow(Worksheet $sheet, int $row, string $value, string $fill, int $fontSize): void
    {
        $this->writeMergedRow($sheet, $row, $value, $fontSize, true);
        $sheet->getStyle('A'.$row.':'.self::LAST_COLUMN.$row)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($fill);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('A'.$row.':'.self::LAST_COLUMN.$row)
            ->getBorders()->getOutline()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFB7C9B9');
    }

    private function writeTotalRow(Worksheet $sheet, int $row, int $total): void
    {
        $sheet->setCellValue('A'.$row, 'Total Penerima: '.$total);
        $sheet->mergeCells('A'.$row.':'.self::LAST_COLUMN.$row);
        $sheet->getStyle('A'.$row)->getFont()->setBold(true);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function writeEmptyMessage(Worksheet $sheet, int $row): void
    {
        $sheet->setCellValue('A'.$row, 'Belum ada data penerima.');
        $sheet->mergeCells('A'.$row.':'.self::LAST_COLUMN.$row);
        $sheet->getStyle('A'.$row)->getFont()->setItalic(true)->getColor()->setARGB('FF666666');
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    }

    private function styleTableHeader(Worksheet $sheet, int $row): void
    {
        $range = 'A'.$row.':'.self::LAST_COLUMN.$row;
        $sheet->getStyle($range)->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF385723');
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFFFFFFF');
        $sheet->getRowDimension($row)->setRowHeight(26);
    }

    private function styleTableBody(Worksheet $sheet, int $firstRow, int $lastRow): void
    {
        $range = 'A'.$firstRow.':'.self::LAST_COLUMN.$lastRow;
        $sheet->getStyle($range)->getAlignment()->setWrapText(true);
        $sheet->getStyle($range)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('FFD9E2D9');
        $sheet->getStyle('A'.$firstRow.':B'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('D'.$firstRow.':F'.$lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A'.$firstRow.':B'.$lastRow)->getNumberFormat()->setFormatCode('0');

        for ($row = $firstRow; $row <= $lastRow; $row++) {
            $sheet->getRowDimension($row)->setRowHeight(32);
        }
    }

    private function sortedRegionGroups(Collection $records): Collection
    {
        return $records
            ->sort(function ($left, $right) {
                foreach (['rw', 'rt', 'nama_rt'] as $field) {
                    $comparison = $this->compareGroupValues($left->{$field}, $right->{$field});

                    if ($comparison !== 0) {
                        return $comparison;
                    }
                }

                return 0;
            })
            ->groupBy(fn ($record) => implode('|', [
                $this->groupKey($record->rt),
                $this->groupKey($record->rw),
                $this->groupKey($record->nama_rt),
            ]))
            ->values();
    }

    private function compareGroupValues(?string $left, ?string $right): int
    {
        if (blank($left) && blank($right)) {
            return 0;
        }

        if (blank($left)) {
            return 1;
        }

        if (blank($right)) {
            return -1;
        }

        return strnatcasecmp($left, $right);
    }

    private function sortedRecipients(Collection $records): Collection
    {
        return $records
            ->sortBy([
                fn ($left, $right) => $this->genderOrder($left->jenis_kelamin) <=> $this->genderOrder($right->jenis_kelamin),
                fn ($left, $right) => strnatcasecmp((string) $left->nama_lengkap, (string) $right->nama_lengkap),
            ])
            ->values();
    }

    private function genderOrder(?string $gender): int
    {
        return match ($gender) {
            'L' => 1,
            'P' => 2,
            default => 3,
        };
    }

    private function groupKey(?string $value): string
    {
        return filled($value) ? $value : '__empty__';
    }

    private function displayGroupValue(?string $value): string
    {
        return filled($value) ? $value : 'Belum diisi';
    }

    private function formatAge(PendaftaranAnakYatimDhuafa $record): string
    {
        if ($record->umur !== null && filled($record->umur_satuan)) {
            return $record->umur.' '.Str::title($record->umur_satuan);
        }

        return 'Belum diisi';
    }

    private function uniqueSheetName(string $source, array &$usedNames): string
    {
        $baseName = preg_replace('/[\\\\\/\?\*\[\]:]/u', '-', trim($source, " \t\n\r\0\x0B'"));
        $baseName = trim((string) $baseName);
        $baseName = $baseName !== '' ? $baseName : 'Sumber';
        $candidate = mb_substr($baseName, 0, 31);
        $sequence = 2;
        $normalizedUsedNames = array_map('mb_strtolower', $usedNames);

        while (in_array(mb_strtolower($candidate), $normalizedUsedNames, true)) {
            $suffix = " ({$sequence})";
            $candidate = mb_substr($baseName, 0, 31 - mb_strlen($suffix)).$suffix;
            $sequence++;
        }

        $usedNames[] = $candidate;

        return $candidate;
    }

    private function allSourcesFilename(Collection $records): string
    {
        $period = $records->pluck('tahun_program')->filter()->unique()->sort()->implode('-');
        $periodPart = $period !== '' ? '-'.$period : '';

        return "Santunan-Ramadhan{$periodPart}-Semua-Sumber.xlsx";
    }

    private function filename(string $source, Collection $records): string
    {
        $period = $records->pluck('tahun_program')->filter()->unique()->sort()->implode('-');
        $normalizedSource = preg_replace('/[\\\\\/\?\*\[\]:]+/u', ' ', $source);
        $sourceSlug = rtrim(mb_substr(Str::slug((string) $normalizedSource), 0, 100), '-');
        $sourceSlug = $sourceSlug !== '' ? $sourceSlug : 'sumber';
        $periodPart = $period !== '' ? '-'.$period : '';

        return "Santunan-Ramadhan{$periodPart}-{$sourceSlug}.xlsx";
    }
}
