<?php

use App\Models\SantunanParticipation;
use App\Models\SantunanPerson;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function santunanPayload(array $overrides = []): array
{
    return array_merge([
        'kategori' => 'yatim_dhuafa',
        'nama_lengkap' => 'Anak Uji '.fake()->unique()->numerify('####'),
        'nama_panggilan' => 'Anak',
        'tanggal_lahir' => null,
        'umur' => 10,
        'umur_satuan' => 'tahun',
        'jenis_kelamin' => 'L',
        'alamat' => 'Jalan Pengujian Nomor 10',
        'no_wa' => '081234567890',
        'nama_orang_tua' => 'Orang Tua Uji',
        'pekerjaan_orang_tua' => 'Pedagang',
        'sumber_informasi' => 'Pengurus RT',
        'catatan_tambahan' => null,
        'tahun_program' => now()->year,
    ], $overrides);
}

function santunanWorkbook(array $row, bool $withRegionColumns): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $headers = [
        'Kategori', 'Nama Lengkap', 'Nama Panggilan', 'Jenis Kelamin',
        'Tanggal Lahir', 'Umur', 'Satuan Umur', 'Nama Orang Tua / Wali',
        'Pekerjaan Orang Tua / Wali', 'Alamat', 'No WA',
        'Sumber Informasi', 'Catatan Tambahan',
    ];

    if ($withRegionColumns) {
        array_push($headers, 'RT (Opsional)', 'RW (Opsional)', 'Nama RT (Opsional)');
    }

    $sheet->fromArray($headers, null, 'A1');
    $sheet->fromArray(array_slice($row, 0, 13), null, 'A2');

    if ($withRegionColumns) {
        $sheet->setCellValueExplicit('N2', $row[13] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('O2', $row[14] ?? '', DataType::TYPE_STRING);
        $sheet->setCellValue('P2', $row[15] ?? '');
    }

    $path = tempnam(sys_get_temp_dir(), 'santunan-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile(
        $path,
        'santunan.xlsx',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        null,
        true,
    );
}

function createSantunanParticipation(array $overrides = []): SantunanParticipation
{
    $data = array_merge(santunanPayload(), $overrides);
    $person = SantunanPerson::query()->create(collect($data)->only([
        'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin',
    ])->all());

    return SantunanParticipation::query()->create(collect($data)->except([
        'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin',
    ])->all() + ['person_id' => $person->id]);
}

function groupedSantunan(array $overrides = []): SantunanParticipation
{
    return createSantunanParticipation(array_merge([
        'tahun_program' => now()->year,
        'sumber_informasi' => 'Pak Indra',
        'kategori' => 'yatim_dhuafa',
        'rw' => '07',
        'rt' => '06',
        'nama_rt' => 'Pak Parno',
    ], $overrides));
}

function groupedRecipientIds(array $groups): array
{
    return collect($groups)
        ->flatMap(fn ($source) => collect($source['categories']))
        ->flatMap(fn ($category) => collect($category['rws']))
        ->flatMap(fn ($rw) => collect($rw['rts']))
        ->flatMap(fn ($rt) => collect($rt['coordinators']))
        ->flatMap(fn ($coordinator) => collect($coordinator['recipients']))
        ->pluck('id')
        ->sort()
        ->values()
        ->all();
}

it('keeps Santunan pages public and uses one registration status source', function () {
    config()->set('santunan.registration_open', true);

    $this->get(route('santunan-ramadhan.index'))
        ->assertOk()
        ->assertSee('Daftar Anak Baru')
        ->assertSee('id="tableViewPanel"', false)
        ->assertSee('id="groupedViewPanel" class="hidden"', false)
        ->assertSee('Export Sumber Terpilih')
        ->assertSee('Export Semuanya')
        ->assertSee('Export satu sumber ke dua sheet kategori.')
        ->assertSee('Export seluruh sumber informasi. Setiap sumber dibuat menjadi satu sheet.')
        ->assertSeeInOrder(['Tabel', 'Grouping']);

    $this->get(route('santunan-ramadhan.form'))
        ->assertOk()
        ->assertSee('Kirim Pendaftaran');

    config()->set('santunan.registration_open', false);

    $this->get(route('santunan-ramadhan.index'))
        ->assertOk()
        ->assertSee('Pendaftaran Ditutup');

    $this->get(route('santunan-ramadhan.form'))
        ->assertOk()
        ->assertSee('Telah Ditutup');

    $this->postJson(route('santunan-ramadhan.submit'), santunanPayload())
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('creates records without optional RT fields', function () {
    config()->set('santunan.registration_open', true);

    $this->postJson(route('santunan-ramadhan.submit'), santunanPayload())
        ->assertOk()
        ->assertJsonPath('success', true);

    $record = SantunanParticipation::with('person')->sole();

    expect($record->rt)->toBeNull()
        ->and($record->rw)->toBeNull()
        ->and($record->nama_rt)->toBeNull();
});

it('creates records with RT fields and preserves leading zeroes', function () {
    config()->set('santunan.registration_open', true);

    $this->postJson(route('santunan-ramadhan.submit'), santunanPayload([
        'rt' => '006',
        'rw' => '007',
        'nama_rt' => 'Bapak Ketua RT',
    ]))->assertOk();

    $record = SantunanParticipation::with('person')->sole();

    expect($record->rt)->toBe('006')
        ->and($record->rw)->toBe('007')
        ->and($record->nama_rt)->toBe('Bapak Ketua RT');
});

it('rejects oversized RT fields', function (string $field, string $value) {
    config()->set('santunan.registration_open', true);

    $this->postJson(route('santunan-ramadhan.submit'), santunanPayload([$field => $value]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);
})->with([
    'RT' => ['rt', '123456'],
    'RW' => ['rw', '123456'],
    'Nama RT' => ['nama_rt', str_repeat('a', 151)],
]);

it('updates and clears optional RT fields to null', function () {
    $record = createSantunanParticipation([
        'tahun_program' => now()->year,
        'rt' => '006',
        'rw' => '007',
        'nama_rt' => 'Ketua Lama',
    ]);

    $this->putJson(route('santunan-ramadhan.update', $record), santunanPayload([
        'nama_lengkap' => $record->nama_lengkap,
        'rt' => '',
        'rw' => null,
        'nama_rt' => '  ',
    ]))->assertOk();

    $record->refresh();

    expect($record->rt)->toBeNull()
        ->and($record->rw)->toBeNull()
        ->and($record->nama_rt)->toBeNull();
});

it('imports the old template without optional columns', function () {
    $row = [
        'yatim_dhuafa', 'Anak Import Lama', 'Anak', 'L', '', 10, 'tahun',
        'Orang Tua Import', 'Pedagang', 'Alamat Import Lama', '081234567890',
        'Pengurus RT', '',
    ];

    $this->post(route('santunan-ramadhan.import'), [
        'file' => santunanWorkbook($row, false),
        'tahun_program' => now()->year,
    ])->assertOk()->assertJsonPath('success', true);

    $record = SantunanParticipation::with('person')->sole();
    expect($record->rt)->toBeNull()->and($record->rw)->toBeNull()->and($record->nama_rt)->toBeNull();
});

it('imports optional RT fields and preserves leading zeroes', function () {
    $row = [
        'dhuafa', 'Anak Import Baru', 'Anak', 'P', '', 9, 'tahun',
        'Orang Tua Import', 'Buruh', 'Alamat Import Baru', '081234567891',
        'Koordinator', '', '006', '007', 'Ibu Ketua RT',
    ];

    $this->post(route('santunan-ramadhan.import'), [
        'file' => santunanWorkbook($row, true),
        'tahun_program' => now()->year,
    ])->assertOk()->assertJsonPath('success', true);

    $record = SantunanParticipation::with('person')->sole();
    expect($record->rt)->toBe('006')->and($record->rw)->toBe('007')->and($record->nama_rt)->toBe('Ibu Ketua RT');
});

it('downloads a valid template with optional RT headers', function () {
    $response = $this->get(route('santunan-ramadhan.template'))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $path = tempnam(sys_get_temp_dir(), 'template-santunan-').'.xlsx';
    file_put_contents($path, $response->streamedContent());

    $sheet = IOFactory::load($path)->getActiveSheet();

    expect($sheet->getCell('N1')->getValue())->toBe('RT (Opsional)')
        ->and($sheet->getCell('O1')->getValue())->toBe('RW (Opsional)')
        ->and($sheet->getCell('P1')->getValue())->toBe('Nama RT (Opsional)')
        ->and($sheet->getCell('N2')->getFormattedValue())->toBe('006')
        ->and($sheet->getCell('O2')->getFormattedValue())->toBe('007');
});

it('returns RT fields in detail and table data with clear null fallbacks', function () {
    $filled = createSantunanParticipation([
        'tahun_program' => now()->year,
        'rt' => '006',
        'rw' => '007',
        'nama_rt' => 'Ketua RT',
    ]);
    createSantunanParticipation([
        'tahun_program' => now()->year,
        'rt' => null,
        'rw' => null,
        'nama_rt' => null,
    ]);

    $this->getJson(route('santunan-ramadhan.edit', $filled))
        ->assertOk()
        ->assertJsonPath('rt', '006')
        ->assertJsonPath('rw', '007')
        ->assertJsonPath('nama_rt', 'Ketua RT');

    $response = $this->getJson(route('santunan-ramadhan.data', [
        'draw' => 1,
        'start' => 0,
        'length' => 10,
    ]))->assertOk();

    expect(collect($response->json('data'))->pluck('rt')->all())->toContain('006', '-');

    $this->getJson(route('santunan-ramadhan.data', [
        'draw' => 2,
        'start' => 0,
        'length' => 10,
        'rt' => '006',
    ]))->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $filled->id);
});

it('requires one valid source before exporting Santunan data', function () {
    $this->postJson(route('santunan-ramadhan.export'))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('sumber_informasi')
        ->assertJsonPath('errors.sumber_informasi.0', 'Silakan pilih sumber informasi terlebih dahulu.');

    $this->postJson(route('santunan-ramadhan.exportBySumber'), [
        'sumber_informasi' => 'Sumber Tidak Ada',
        'tahun_program' => now()->year,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('sumber_informasi');
});

it('exports exactly two category sheets with global coupons grouped tables and no data mutation', function () {
    $source = 'Pak Indra/TCE:*?[] Laporan Sumber Sangat Panjang';
    $sourceRecords = collect([
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Muhammad Hamdani', 'kategori' => 'dhuafa', 'rw' => '07', 'rt' => '06', 'nama_rt' => 'Pak Parno', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2013-05-07']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Adam Nur', 'kategori' => 'dhuafa', 'rw' => '07', 'rt' => '06', 'nama_rt' => 'Pak Parno', 'jenis_kelamin' => 'L', 'tanggal_lahir' => '2013-01-02']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Zahra Aulia', 'kategori' => 'dhuafa', 'rw' => '07', 'rt' => '06', 'nama_rt' => 'Pak Parno', 'jenis_kelamin' => 'P']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Aisyah Putri', 'kategori' => 'dhuafa', 'rw' => '07', 'rt' => '06', 'nama_rt' => 'Pak Parno', 'jenis_kelamin' => 'P']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Zulfikar Aman', 'kategori' => 'dhuafa', 'rw' => '07', 'rt' => '06', 'nama_rt' => 'Pak Zain', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Alief Putra', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '03', 'nama_rt' => 'Pak Wawang', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Beni Saputra', 'kategori' => 'dhuafa', 'rw' => '06', 'rt' => '03', 'nama_rt' => null, 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Nanda Putri', 'kategori' => 'yatim_dhuafa', 'rw' => '04', 'rt' => '03', 'nama_rt' => 'Pak Wawang', 'jenis_kelamin' => 'P']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Tegar TCE', 'kategori' => 'dhuafa', 'rw' => '08', 'rt' => 'TCE', 'nama_rt' => 'Pak Indra (TCE)', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => $source, 'nama_lengkap' => 'Tanpa Wilayah', 'kategori' => 'yatim_dhuafa', 'rw' => null, 'rt' => null, 'nama_rt' => null, 'jenis_kelamin' => 'P']),
    ]);
    groupedSantunan(['sumber_informasi' => 'Sumber Lain', 'nama_lengkap' => 'Tidak Boleh Bocor']);

    $beforeCount = SantunanParticipation::count();
    $beforeUpdatedAt = SantunanParticipation::orderBy('id')->pluck('updated_at', 'id')->map->toISOString()->all();
    $beforeCategories = SantunanParticipation::orderBy('id')->pluck('kategori', 'id')->all();

    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $response = $this->post(route('santunan-ramadhan.exportBySumber'), [
        'sumber_informasi' => $source,
        'tahun_program' => now()->year,
    ])->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $contents = $response->streamedContent();
    $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog());
    \Illuminate\Support\Facades\DB::disableQueryLog();

    expect(substr($contents, 0, 2))->toBe('PK')
        ->and($response->headers->get('content-disposition'))->toContain('Santunan-Ramadhan-'.now()->year.'-pak-indra-tce-laporan-sumber-sangat-panjang.xlsx')
        ->and($queries->filter(fn ($query) => preg_match('/^\s*(insert|update|delete)\s/i', $query['query'])))->toBeEmpty()
        ->and($queries->filter(fn ($query) => str_contains(strtolower($query['query']), 'santunan_participations')))->toHaveCount(2)
        ->and(SantunanParticipation::count())->toBe($beforeCount)
        ->and(SantunanParticipation::orderBy('id')->pluck('updated_at', 'id')->map->toISOString()->all())->toBe($beforeUpdatedAt)
        ->and(SantunanParticipation::orderBy('id')->pluck('kategori', 'id')->all())->toBe($beforeCategories);

    $path = tempnam(sys_get_temp_dir(), 'santunan-export-').'.xlsx';
    file_put_contents($path, $contents);
    $workbook = IOFactory::load($path);
    $dhuafaSheet = $workbook->getSheetByName('DHUAFA');
    $yatimDhuafaSheet = $workbook->getSheetByName('YATIM YANG DHUAFA');

    expect($workbook->getSheetCount())->toBe(2)
        ->and($workbook->getSheetNames())->toBe(['DHUAFA', 'YATIM YANG DHUAFA'])
        ->and($workbook->getSheetByName('YATIM'))->toBeNull()
        ->and($dhuafaSheet)->not->toBeNull()
        ->and($yatimDhuafaSheet)->not->toBeNull();

    foreach ([$dhuafaSheet, $yatimDhuafaSheet] as $sheet) {
        expect($sheet->getCell('A1')->getValue())->toBe('Data Peserta')
            ->and($sheet->getCell('A2')->getValue())->toBe('Santunan Yatim Dhuafa')
            ->and($sheet->getCell('A3')->getValue())->toBe(masjid_name())
            ->and($sheet->getCell('A4')->getValue())->toBe('Ramadhan 1447 H / '.now()->year.' M')
            ->and($sheet->getCell('A6')->getValue())->toBe('Sumber Informasi - '.$source)
            ->and($sheet->getShowGridlines())->toBeFalse()
            ->and($sheet->getPageSetup()->getOrientation())->toBe('landscape')
            ->and($sheet->getPageSetup()->getPaperSize())->toBe(9)
            ->and($sheet->getPageSetup()->getPrintArea())->toStartWith('A1:H');
    }

    $sheetData = collect([$dhuafaSheet, $yatimDhuafaSheet])->mapWithKeys(function ($sheet) {
        $columnA = [];
        $nameRows = [];
        $couponByName = [];
        $localNumberByName = [];
        $tableHeaders = [];

        for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $columnA[$row] = $sheet->getCell('A'.$row)->getValue();
            $name = $sheet->getCell('C'.$row)->getValue();

            if ($columnA[$row] === 'NO. KUPON') {
                $tableHeaders[$row] = $sheet->rangeToArray('A'.$row.':H'.$row)[0];
            }

            if (is_numeric($columnA[$row]) && is_string($name) && $name !== '') {
                $nameRows[$name] = $row;
                $couponByName[$name] = $columnA[$row];
                $localNumberByName[$name] = $sheet->getCell('B'.$row)->getValue();
            }
        }

        return [$sheet->getTitle() => compact('columnA', 'nameRows', 'couponByName', 'localNumberByName', 'tableHeaders')];
    });

    $dhuafa = $sheetData['DHUAFA'];
    $yatimDhuafa = $sheetData['YATIM YANG DHUAFA'];
    $expectedHeader = ['NO. KUPON', 'NO.', 'NAMA ANAK', 'JK', 'TANGGAL LAHIR', 'UMUR', 'NAMA ORANG TUA', 'ALAMAT'];

    expect($dhuafa['columnA'])->toContain('RT 06 / RW 07 — Pak Parno')
        ->and($dhuafa['columnA'])->toContain('RT 06 / RW 07 — Pak Zain')
        ->and($dhuafa['columnA'])->toContain('RT 03 / RW 04 — Pak Wawang')
        ->and($dhuafa['columnA'])->toContain('RT 03 / RW 06')
        ->and($dhuafa['columnA'])->not->toContain('RT 03 / RW 06 — Belum diisi')
        ->and($dhuafa['columnA'])->toContain('RT TCE / RW 08 — Pak Indra (TCE)')
        ->and($yatimDhuafa['columnA'])->toContain('RT Belum diisi / RW Belum diisi — Belum diisi')
        ->and($yatimDhuafa['columnA'])->toContain('RT 03 / RW 04 — Pak Wawang')
        ->and($dhuafa['columnA'])->not->toContain('DHUAFA', 'DHUAFA — Total: 8')
        ->and($yatimDhuafa['columnA'])->not->toContain('YATIM YANG DHUAFA', 'YATIM YANG DHUAFA — Total: 2')
        ->and($dhuafa['tableHeaders'])->each->toBe($expectedHeader)
        ->and($yatimDhuafa['tableHeaders'])->each->toBe($expectedHeader);

    $parnoHeadingRow = array_search('RT 06 / RW 07 — Pak Parno', $dhuafa['columnA'], true);
    expect($dhuafaSheet->getCell('A'.($parnoHeadingRow + 1))->getValue())->toBe('Total Penerima: 4')
        ->and($dhuafaSheet->getCell('A'.($parnoHeadingRow + 2))->getValue())->toBe('NO. KUPON')
        ->and($dhuafa['nameRows']['Adam Nur'])->toBeLessThan($dhuafa['nameRows']['Muhammad Hamdani'])
        ->and($dhuafa['nameRows']['Muhammad Hamdani'])->toBeLessThan($dhuafa['nameRows']['Aisyah Putri'])
        ->and($dhuafa['nameRows']['Aisyah Putri'])->toBeLessThan($dhuafa['nameRows']['Zahra Aulia'])
        ->and($dhuafaSheet->rangeToArray('A'.($parnoHeadingRow + 3).':D'.($parnoHeadingRow + 6), null, true, false))->toBe([
            [3, 1, 'Adam Nur', 'L'],
            [4, 2, 'Muhammad Hamdani', 'L'],
            [5, 3, 'Aisyah Putri', 'P'],
            [6, 4, 'Zahra Aulia', 'P'],
        ])
        ->and($dhuafaSheet->getCell('E'.$dhuafa['nameRows']['Adam Nur'])->getFormattedValue())->toBe('02/01/2013');

    $allCoupons = array_merge(array_values($dhuafa['couponByName']), array_values($yatimDhuafa['couponByName']));
    $exportedNames = array_merge(array_keys($dhuafa['nameRows']), array_keys($yatimDhuafa['nameRows']));
    $expectedNames = $sourceRecords
        ->whereIn('kategori', ['dhuafa', 'yatim_dhuafa'])
        ->pluck('nama_lengkap')
        ->sort()
        ->values()
        ->all();

    expect($allCoupons)->toBe(range(1, count($allCoupons)))
        ->and($yatimDhuafa['couponByName']['Nanda Putri'])->toBe(count($dhuafa['couponByName']) + 1)
        ->and($dhuafa['localNumberByName']['Alief Putra'])->toBe(1)
        ->and($dhuafa['localNumberByName']['Beni Saputra'])->toBe(1)
        ->and($yatimDhuafa['localNumberByName']['Nanda Putri'])->toBe(1)
        ->and(collect($exportedNames)->sort()->values()->all())->toBe($expectedNames)
        ->and(collect($exportedNames)->duplicates())->toBeEmpty()
        ->and($exportedNames)->not->toContain('Yatim Tidak Masuk', 'Tidak Boleh Bocor');

    foreach ([$dhuafaSheet, $yatimDhuafaSheet] as $sheet) {
        $headingRows = [];
        for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $value = $sheet->getCell('A'.$row)->getValue();
            if (is_string($value) && str_starts_with($value, 'RT ')) {
                $headingRows[] = $row;
            }
        }

        foreach (array_slice($headingRows, 1) as $headingRow) {
            expect($sheet->getCell('A'.($headingRow - 1))->getValue())->toBeNull()
                ->and($sheet->getCell('A'.($headingRow - 2))->getValue())->toBeNull();
        }

        expect(substr_count(implode('|', array_filter(array_values($sheetData[$sheet->getTitle()]['columnA']))), 'Sumber Informasi - '.$source))->toBe(1);
    }

    @unlink($path);
});

it('keeps both category sheets when one category has no recipients', function () {
    $source = 'Sumber Hanya Dhuafa';
    groupedSantunan(['sumber_informasi' => $source, 'kategori' => 'dhuafa']);

    $response = $this->post(route('santunan-ramadhan.exportBySumber'), [
        'sumber_informasi' => $source,
        'tahun_program' => now()->year,
    ])->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'santunan-empty-category-').'.xlsx';
    file_put_contents($path, $response->streamedContent());
    $workbook = IOFactory::load($path);

    expect($workbook->getSheetNames())->toBe(['DHUAFA', 'YATIM YANG DHUAFA'])
        ->and($workbook->getSheetByName('YATIM'))->toBeNull()
        ->and($workbook->getSheetByName('YATIM YANG DHUAFA')->getCell('A8')->getValue())->toBe('Belum ada data penerima.');

    @unlink($path);
});

it('exports every active-year source to its own sheet with one global coupon sequence', function () {
    $records = collect([
        groupedSantunan(['sumber_informasi' => 'Pak Indra', 'nama_lengkap' => 'Muhammad Indra', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '01', 'nama_rt' => 'Pak Indra', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => 'Pak Indra', 'nama_lengkap' => 'Adam Indra', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '01', 'nama_rt' => 'Pak Indra', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => 'Pak Indra', 'nama_lengkap' => 'Aisyah Indra', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '01', 'nama_rt' => 'Pak Indra', 'jenis_kelamin' => 'P']),
        groupedSantunan(['sumber_informasi' => 'Pak Indra', 'nama_lengkap' => 'Tegar TCE', 'kategori' => 'dhuafa', 'rw' => '08', 'rt' => 'TCE', 'nama_rt' => 'Pak Indra (TCE)', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => 'Pak Indra', 'nama_lengkap' => 'Tanpa Wilayah', 'kategori' => 'yatim_dhuafa', 'rw' => null, 'rt' => null, 'nama_rt' => null, 'jenis_kelamin' => 'P']),
        groupedSantunan(['sumber_informasi' => 'Bu Susi', 'nama_lengkap' => 'Budi Susi', 'kategori' => 'dhuafa', 'rw' => '05', 'rt' => '02', 'nama_rt' => 'Bu Susi', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => 'Bu Susi', 'nama_lengkap' => 'Cici Susi', 'kategori' => 'yatim_dhuafa', 'rw' => '06', 'rt' => '03', 'nama_rt' => 'Bu Susi', 'jenis_kelamin' => 'L']),
        groupedSantunan(['sumber_informasi' => 'Bu Susi', 'nama_lengkap' => 'Dian Susi', 'kategori' => 'yatim_dhuafa', 'rw' => '06', 'rt' => '03', 'nama_rt' => 'Bu Susi', 'jenis_kelamin' => 'P']),
        groupedSantunan(['sumber_informasi' => 'Pak Wawang', 'nama_lengkap' => 'Eka Wawang', 'kategori' => 'dhuafa', 'rw' => '07', 'rt' => '04', 'nama_rt' => 'Pak Wawang', 'jenis_kelamin' => 'P']),
    ]);
    groupedSantunan([
        'tahun_program' => now()->year - 1,
        'sumber_informasi' => 'Sumber Tahun Lama',
        'nama_lengkap' => 'Tidak Masuk Tahun Aktif',
        'kategori' => 'dhuafa',
    ]);

    $beforeCount = SantunanParticipation::count();
    $beforeData = SantunanParticipation::orderBy('id')->get()->toJson();

    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();
    $response = $this->post(route('santunan-ramadhan.exportAll'), [
        'tahun_program' => now()->year,
    ])->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $contents = $response->streamedContent();
    $queries = collect(\Illuminate\Support\Facades\DB::getQueryLog());
    \Illuminate\Support\Facades\DB::disableQueryLog();

    expect($response->headers->get('content-disposition'))->toContain('Santunan-Ramadhan-'.now()->year.'-Semua-Sumber.xlsx')
        ->and($queries->filter(fn ($query) => preg_match('/^\s*(insert|update|delete)\s/i', $query['query'])))->toBeEmpty()
        ->and($queries->filter(fn ($query) => str_contains(strtolower($query['query']), 'santunan_participations')))->toHaveCount(1)
        ->and(SantunanParticipation::count())->toBe($beforeCount)
        ->and(SantunanParticipation::orderBy('id')->get()->toJson())->toBe($beforeData);

    $path = tempnam(sys_get_temp_dir(), 'santunan-export-all-').'.xlsx';
    file_put_contents($path, $contents);
    $workbook = IOFactory::load($path);

    expect($workbook->getSheetNames())->toBe(['Pak Indra', 'Bu Susi', 'Pak Wawang'])
        ->and($workbook->getSheetByName('DHUAFA'))->toBeNull()
        ->and($workbook->getSheetByName('YATIM YANG DHUAFA'))->toBeNull()
        ->and($workbook->getSheetByName('Sumber Tahun Lama'))->toBeNull();

    $expectedBySource = [
        'Pak Indra' => [
            'names' => ['Adam Indra', 'Muhammad Indra', 'Aisyah Indra', 'Tegar TCE', 'Tanpa Wilayah'],
            'categories' => ['DHUAFA — Total: 4', 'YATIM YANG DHUAFA — Total: 1'],
        ],
        'Bu Susi' => [
            'names' => ['Budi Susi', 'Cici Susi', 'Dian Susi'],
            'categories' => ['DHUAFA — Total: 1', 'YATIM YANG DHUAFA — Total: 2'],
        ],
        'Pak Wawang' => [
            'names' => ['Eka Wawang'],
            'categories' => ['DHUAFA — Total: 1', 'YATIM YANG DHUAFA — Total: 0'],
        ],
    ];
    $allCoupons = [];
    $allExportedNames = [];

    foreach ($expectedBySource as $source => $expected) {
        $sheet = $workbook->getSheetByName($source);
        $columnA = [];
        $rowsByName = [];

        for ($row = 1; $row <= $sheet->getHighestDataRow(); $row++) {
            $columnA[$row] = $sheet->getCell('A'.$row)->getValue();
            $name = $sheet->getCell('C'.$row)->getValue();

            if (is_numeric($columnA[$row]) && is_string($name) && $name !== '') {
                $rowsByName[$name] = $row;
                $allCoupons[] = (int) $columnA[$row];
                $allExportedNames[] = $name;
                expect($sheet->getCell('A'.$row)->getDataType())->toBe(DataType::TYPE_NUMERIC);
            }
        }

        $dhuafaRow = array_search($expected['categories'][0], $columnA, true);
        $yatimDhuafaRow = array_search($expected['categories'][1], $columnA, true);

        expect($sheet->getCell('A1')->getValue())->toBe('Data Peserta')
            ->and($sheet->getCell('A2')->getValue())->toBe('Santunan Yatim Dhuafa')
            ->and($sheet->getCell('A3')->getValue())->toBe(masjid_name())
            ->and($sheet->getCell('A4')->getValue())->toBe('Ramadhan 1447 H / '.now()->year.' M')
            ->and($sheet->getCell('A6')->getValue())->toBe('Sumber Informasi - '.$source)
            ->and($dhuafaRow)->toBeInt()
            ->and($yatimDhuafaRow)->toBeInt()
            ->and($dhuafaRow)->toBeLessThan($yatimDhuafaRow)
            ->and(array_keys($rowsByName))->toBe($expected['names']);
    }

    $pakIndra = $workbook->getSheetByName('Pak Indra');
    $pakIndraValues = [];
    for ($row = 1; $row <= $pakIndra->getHighestDataRow(); $row++) {
        $pakIndraValues[$row] = $pakIndra->getCell('A'.$row)->getValue();
    }
    $rtMainRow = array_search('RT 01 / RW 04 — Pak Indra', $pakIndraValues, true);
    $rtTceRow = array_search('RT TCE / RW 08 — Pak Indra (TCE)', $pakIndraValues, true);
    $nullRegionRow = array_search('RT Belum diisi / RW Belum diisi — Belum diisi', $pakIndraValues, true);

    expect($pakIndra->getCell('A'.($rtMainRow + 1))->getValue())->toBe('Total Penerima: 3')
        ->and($pakIndra->rangeToArray('A'.($rtMainRow + 3).':D'.($rtMainRow + 5), null, true, false))->toBe([
            [1, 1, 'Adam Indra', 'L'],
            [2, 2, 'Muhammad Indra', 'L'],
            [3, 3, 'Aisyah Indra', 'P'],
        ])
        ->and($pakIndra->getCell('A'.($rtTceRow + 1))->getValue())->toBe('Total Penerima: 1')
        ->and($pakIndra->getCell('B'.($rtTceRow + 3))->getValue())->toBe(1)
        ->and($pakIndra->getCell('A'.($nullRegionRow + 1))->getValue())->toBe('Total Penerima: 1')
        ->and($pakIndra->getCell('B'.($nullRegionRow + 3))->getValue())->toBe(1)
        ->and($allCoupons)->toBe(range(1, $records->count()))
        ->and(collect($allExportedNames)->duplicates())->toBeEmpty()
        ->and(collect($allExportedNames)->sort()->values()->all())->toBe($records->pluck('nama_lengkap')->sort()->values()->all());

    @unlink($path);
});

it('creates deterministic unique Excel-safe sheet names for all-source export', function () {
    $sharedPrefix = str_repeat('Sumber Sangat Panjang ', 2);
    groupedSantunan(['sumber_informasi' => $sharedPrefix.'Alpha', 'kategori' => 'dhuafa']);
    groupedSantunan(['sumber_informasi' => $sharedPrefix.'Beta', 'kategori' => 'dhuafa']);

    $response = $this->post(route('santunan-ramadhan.exportAll'), [
        'tahun_program' => now()->year,
    ])->assertOk();

    $path = tempnam(sys_get_temp_dir(), 'santunan-export-safe-sheets-').'.xlsx';
    file_put_contents($path, $response->streamedContent());
    $workbook = IOFactory::load($path);
    $sheetNames = $workbook->getSheetNames();

    expect($sheetNames)->toHaveCount(2)
        ->and(collect($sheetNames)->unique(fn ($name) => mb_strtolower($name)))->toHaveCount(2)
        ->and($sheetNames[0])->toHaveLength(31)
        ->and($sheetNames[1])->toEndWith(' (2)')
        ->and(mb_strlen($sheetNames[1]))->toBeLessThanOrEqual(31);

    @unlink($path);
});

it('finds duplicate candidates while keeping the response contract', function () {
    foreach (['Ahmad Fauzan', 'Ahmad Fauzan', 'Nama Berbeda'] as $name) {
        createSantunanParticipation([
            'nama_lengkap' => $name,
            'nama_orang_tua' => 'Bapak Fauzan',
        ]);
    }

    $this->getJson(route('santunan-ramadhan.scan-duplikat', ['tahun' => now()->year]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'pairs')
        ->assertJsonPath('pairs.0.similarity', 100);
});

it('groups recipients by source category RW RT and coordinator with sorted names and valid totals', function () {
    $records = collect([
        groupedSantunan(['nama_lengkap' => 'Reyhan Azahlan']),
        groupedSantunan(['nama_lengkap' => 'Muhammad Hamdani']),
        groupedSantunan(['nama_lengkap' => 'Zahra Aulia', 'nama_rt' => 'Pak Zain']),
        groupedSantunan(['nama_lengkap' => 'Adam Nur', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '03', 'nama_rt' => 'Pak Wawang']),
        groupedSantunan(['nama_lengkap' => 'Dian Musyafa', 'kategori' => 'dhuafa']),
        groupedSantunan(['nama_lengkap' => 'Tanpa Wilayah', 'sumber_informasi' => 'Pak Maman', 'kategori' => 'yatim_dhuafa', 'rw' => null, 'rt' => null, 'nama_rt' => null]),
    ]);

    \Illuminate\Support\Facades\DB::flushQueryLog();
    \Illuminate\Support\Facades\DB::enableQueryLog();

    $response = $this->getJson(route('santunan-ramadhan.data-grouped', ['tahun' => now()->year]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('total', 6);

    $recordQueries = collect(\Illuminate\Support\Facades\DB::getQueryLog())
        ->filter(fn ($query) => str_contains(strtolower($query['query']), 'santunan_participations'));
    \Illuminate\Support\Facades\DB::disableQueryLog();

    expect($recordQueries)->toHaveCount(1);

    $groups = collect($response->json('groups'));
    expect($groups->pluck('label')->all())->toBe(['Pak Indra', 'Pak Maman']);

    $pakIndra = $groups->first();
    expect($pakIndra['total'])->toBe(5)
        ->and(collect($pakIndra['categories'])->pluck('label')->all())
        ->toBe(['DHUAFA', 'YATIM YANG DHUAFA'])
        ->and(collect($pakIndra['categories'])->sum('total'))->toBe($pakIndra['total']);

    $combined = collect($pakIndra['categories'])->firstWhere('label', 'YATIM YANG DHUAFA');
    $rw = $combined['rws'][0];
    $rt = $rw['rts'][0];
    $coordinators = collect($rt['coordinators']);
    $coordinator = $coordinators->firstWhere('label', 'Pak Parno');

    expect($rw['label'])->toBe('07')
        ->and($rw['total'])->toBe(3)
        ->and($rt['label'])->toBe('06')
        ->and($rt['total'])->toBe(3)
        ->and($coordinators->pluck('label')->all())->toBe(['Pak Parno', 'Pak Zain'])
        ->and($coordinators->sum('total'))->toBe($rt['total'])
        ->and($coordinator['label'])->toBe('Pak Parno')
        ->and($coordinator['total'])->toBe(2)
        ->and(collect($coordinator['recipients'])->pluck('nama_lengkap')->all())
        ->toBe(['Muhammad Hamdani', 'Reyhan Azahlan']);

    $returnedIds = $groups
        ->flatMap(fn ($source) => collect($source['categories']))
        ->flatMap(fn ($category) => collect($category['rws']))
        ->flatMap(fn ($rwGroup) => collect($rwGroup['rts']))
        ->flatMap(fn ($rtGroup) => collect($rtGroup['coordinators']))
        ->flatMap(fn ($coordinatorGroup) => collect($coordinatorGroup['recipients']))
        ->pluck('id');

    expect($returnedIds->sort()->values()->all())->toBe($records->pluck('id')->sort()->values()->all())
        ->and($returnedIds->duplicates()->isEmpty())->toBeTrue();

    $withoutRegion = $groups->last()['categories'][0];
    expect($withoutRegion['label'])->toBe('YATIM YANG DHUAFA')
        ->and($withoutRegion['rws'][0]['label'])->toBe('Belum diisi')
        ->and($withoutRegion['rws'][0]['rts'][0]['label'])->toBe('Belum diisi')
        ->and($withoutRegion['rws'][0]['rts'][0]['coordinators'][0]['label'])->toBeNull();
});

it('filters grouped recipients without empty parents duplicate records or data loss', function () {
    groupedSantunan(['nama_lengkap' => 'Muhammad Hamdani']);
    groupedSantunan(['nama_lengkap' => 'Reyhan Azahlan']);
    groupedSantunan(['nama_lengkap' => 'Ahmad Lain', 'sumber_informasi' => 'Pak Maman', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '03']);

    $cases = [
        ['params' => ['sumber_informasi' => 'Pak Indra'], 'total' => 2, 'source' => 'Pak Indra'],
        ['params' => ['kategori' => 'dhuafa'], 'total' => 1, 'source' => 'Pak Maman'],
        ['params' => ['rw' => '07'], 'total' => 2, 'source' => 'Pak Indra'],
        ['params' => ['rt' => '03'], 'total' => 1, 'source' => 'Pak Maman'],
        ['params' => ['search' => 'Muhammad'], 'total' => 1, 'source' => 'Pak Indra'],
        ['params' => ['sumber_informasi' => 'Pak Maman', 'kategori' => 'dhuafa', 'rw' => '04', 'rt' => '03', 'search' => 'Ahmad'], 'total' => 1, 'source' => 'Pak Maman'],
    ];

    foreach ($cases as $case) {
        $groupedResponse = $this->getJson(route('santunan-ramadhan.data-grouped', $case['params']))
            ->assertOk()
            ->assertJsonPath('total', $case['total'])
            ->assertJsonCount(1, 'groups')
            ->assertJsonPath('groups.0.label', $case['source']);

        $tableResponse = $this->getJson(route('santunan-ramadhan.data', array_merge([
            'draw' => 1,
            'start' => 0,
            'length' => 100,
        ], $case['params'])))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', $case['total']);

        expect(collect($tableResponse->json('data'))->pluck('id')->sort()->values()->all())
            ->toBe(groupedRecipientIds($groupedResponse->json('groups')));
    }

    $this->getJson(route('santunan-ramadhan.data-grouped', ['search' => 'Tidak Ada']))
        ->assertOk()
        ->assertJsonPath('total', 0)
        ->assertJsonCount(0, 'groups');
});
