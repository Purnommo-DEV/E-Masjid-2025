<?php

use App\Models\PendaftaranAnakYatimDhuafa;
use App\Models\SantunanParticipation;
use App\Models\SantunanPerson;
use App\Services\SantunanLegacyBackfillService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function multiYearPayload(array $overrides = []): array
{
    return array_merge([
        'tahun_program' => 2026,
        'kategori' => 'dhuafa',
        'nama_lengkap' => 'Muhammad Hamdani Yanto',
        'nama_panggilan' => 'Hamdani',
        'tanggal_lahir' => '2015-05-07',
        'umur' => 11,
        'umur_satuan' => 'tahun',
        'jenis_kelamin' => 'L',
        'alamat' => 'Jalan Taman Cipulir',
        'rt' => '06',
        'rw' => '07',
        'nama_rt' => 'Pak Parno',
        'no_wa' => '081234567890',
        'nama_orang_tua' => 'Bapak Yanto',
        'pekerjaan_orang_tua' => 'Pedagang',
        'sumber_informasi' => 'Pak Indra',
        'catatan_tambahan' => null,
    ], $overrides);
}

function multiYearPerson(array $overrides = []): SantunanPerson
{
    $data = multiYearPayload($overrides);

    return SantunanPerson::query()->create(collect($data)->only([
        'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin',
    ])->all());
}

function multiYearParticipation(array $overrides = [], ?SantunanPerson $person = null): SantunanParticipation
{
    $data = multiYearPayload($overrides);
    $person ??= multiYearPerson($overrides);

    return SantunanParticipation::query()->create(collect($data)->except([
        'nama_lengkap', 'nama_panggilan', 'tanggal_lahir', 'jenis_kelamin',
    ])->all() + ['person_id' => $person->id]);
}

function legacyMultiYearRegistration(array $overrides = []): PendaftaranAnakYatimDhuafa
{
    $data = multiYearPayload($overrides);

    return PendaftaranAnakYatimDhuafa::query()->create(collect($data)->only([
        'tahun_program', 'sumber_informasi', 'kategori', 'nama_lengkap', 'nama_panggilan',
        'umur', 'umur_satuan', 'tanggal_lahir', 'jenis_kelamin', 'alamat', 'rt', 'rw',
        'nama_rt', 'no_wa', 'nama_orang_tua', 'pekerjaan_orang_tua', 'catatan_tambahan',
    ])->all());
}

function multiYearWorkbook(array $row, int $styledThroughRow = 2): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        'Kategori', 'Nama Lengkap', 'Nama Panggilan', 'Jenis Kelamin', 'Tanggal Lahir', 'Umur',
        'Satuan Umur', 'Nama Orang Tua / Wali', 'Pekerjaan Orang Tua / Wali', 'Alamat', 'No WA',
        'Sumber Informasi', 'Catatan Tambahan', 'RT', 'RW', 'Nama RT',
    ], null, 'A1');
    $sheet->fromArray($row, null, 'A2');
    $sheet->getStyle("N2:O{$styledThroughRow}")->getNumberFormat()->setFormatCode('@');
    $path = tempnam(sys_get_temp_dir(), 'santunan-multi-year-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    return new UploadedFile($path, 'santunan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}

it('backfills 207 legacy rows one-to-one without mutating legacy data', function () {
    $now = now();
    $rows = collect(range(1, 207))->map(fn (int $number) => [
        'tahun_program' => 2026,
        'sumber_informasi' => $number <= 100 ? 'Pak Indra' : 'Pak Maman',
        'kategori' => $number % 2 === 0 ? 'dhuafa' : 'yatim_dhuafa',
        'nama_lengkap' => 'Peserta Legacy '.$number,
        'nama_panggilan' => null,
        'umur' => 10,
        'umur_satuan' => 'tahun',
        'tanggal_lahir' => null,
        'jenis_kelamin' => $number % 2 === 0 ? 'L' : 'P',
        'alamat' => 'Alamat '.$number,
        'rt' => str_pad((string) (($number % 10) + 1), 2, '0', STR_PAD_LEFT),
        'rw' => '07',
        'nama_rt' => 'Koordinator '.$number,
        'no_wa' => null,
        'nama_orang_tua' => 'Orang Tua '.$number,
        'pekerjaan_orang_tua' => null,
        'status' => 'baru',
        'catatan_tambahan' => null,
        'catatan_admin' => null,
        'ip_address' => null,
        'created_at' => $now,
        'updated_at' => $now,
    ]);
    $rows->chunk(100)->each(fn ($chunk) => DB::table('pendaftaran_anak_yatim_dhuafa')->insert($chunk->all()));
    $legacyBefore = PendaftaranAnakYatimDhuafa::query()->orderBy('id')->get()->toJson();

    $report = app(SantunanLegacyBackfillService::class)->run();

    expect($report['legacy_total'])->toBe(207)
        ->and($report['legacy_2026'])->toBe(207)
        ->and($report['invalid_categories'])->toBe([])
        ->and($report['ambiguous_identity_groups'])->toBe([])
        ->and($report['persons_total'])->toBe(207)
        ->and($report['participations_total'])->toBe(207)
        ->and($report['mapped_legacy_total'])->toBe(207)
        ->and($report['orphans'])->toBe(0)
        ->and(SantunanParticipation::query()->whereNull('legacy_registration_id')->count())->toBe(0)
        ->and(PendaftaranAnakYatimDhuafa::query()->orderBy('id')->get()->toJson())->toBe($legacyBefore);
});

it('stops legacy backfill when category or same-year identity needs review', function () {
    legacyMultiYearRegistration(['kategori' => 'yatim']);

    $invalidReport = app(SantunanLegacyBackfillService::class)->preflight();
    expect($invalidReport['invalid_categories'])->toHaveCount(1)
        ->and(fn () => app(SantunanLegacyBackfillService::class)->run())
        ->toThrow(RuntimeException::class, 'kategori di luar dhuafa dan yatim_dhuafa')
        ->and(SantunanPerson::count())->toBe(0)
        ->and(SantunanParticipation::count())->toBe(0);

    PendaftaranAnakYatimDhuafa::query()->delete();
    legacyMultiYearRegistration();
    legacyMultiYearRegistration();

    $ambiguousReport = app(SantunanLegacyBackfillService::class)->preflight();
    expect($ambiguousReport['ambiguous_identity_groups'])->toHaveCount(1)
        ->and(fn () => app(SantunanLegacyBackfillService::class)->run())
        ->toThrow(RuntimeException::class, 'identity legacy yang ambigu')
        ->and(SantunanPerson::count())->toBe(0)
        ->and(SantunanParticipation::count())->toBe(0);
});

it('renders one year-aware start workflow action without mutating on page or candidate load', function () {
    $participation = multiYearParticipation();
    config()->set('santunan.registration_open', false);
    $before = SantunanParticipation::query()->count();

    $page2026 = $this->get(route('santunan-ramadhan.index', ['tahun' => 2026]))
        ->assertOk()
        ->assertSee('Aksi Tahun')
        ->assertSee('Mulai Data Tahun Baru')
        ->assertSee('2026 → 2027')
        ->assertSee('Mulai dari data 2026 → 2027')
        ->assertSee('Peserta sumber')
        ->assertSee('Batal')
        ->assertSee('Lanjutkan')
        ->assertSee('id="startYearModal"', false)
        ->assertSee('id="startYearContinue" type="submit" disabled', false);

    $dom = new DOMDocument;
    @$dom->loadHTML($page2026->getContent());
    $xpath = new DOMXPath($dom);

    expect(substr_count($page2026->getContent(), 'id="btnStartYear"'))->toBe(1)
        ->and(substr_count($page2026->getContent(), "$('#btnStartYear')"))->toBe(1)
        ->and($xpath->query('//*[@id="pageYearActionsHeader"]//*[@id="btnYearActions"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="filterDataCard"]//*[@id="btnYearActions"]')->length)->toBe(0)
        ->and(SantunanParticipation::query()->count())->toBe($before);

    $this->getJson(route('santunan-ramadhan.year-candidates', ['tahun' => 2026]))
        ->assertOk()
        ->assertJsonPath('records.0.id', $participation->id);

    $this->get(route('santunan-ramadhan.index', ['tahun' => 2027]))
        ->assertOk()
        ->assertSee('2027 → 2028')
        ->assertSee('Mulai dari data 2027 → 2028');

    expect(SantunanParticipation::query()->count())->toBe($before);
});

it('renders a compact import card with a finite single-request loading lifecycle', function () {
    $page = $this->get(route('santunan-ramadhan.index', ['tahun' => 2027]))->assertOk();
    $content = $page->getContent();

    $page->assertSee('id="importExcelCard"', false)
        ->assertSee('id="exportExcelCard"', false)
        ->assertSee('id="excelCardsGrid" class="mb-10 grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2"', false)
        ->assertSee('id="importCardAction" class="mt-auto pt-2"', false)
        ->assertSee('id="exportCardAction" class="mt-auto pt-2"', false)
        ->assertSee('Format XLSX/XLS · Tahun target wajib dipilih.')
        ->assertSee('md:grid-cols-[minmax(150px,0.4fr)_minmax(0,1fr)]', false)
        ->assertSee('id="loadingImport" class="hidden"', false);

    expect(substr_count($content, "$('#formImportExcel')"))->toBe(1)
        ->and($content)->toContain('let importSubmitting = false;')
        ->and($content)->toContain('if (importSubmitting)')
        ->and($content)->toContain('timeout: 120000')
        ->and($content)->toContain('.always(function ()')
        ->and($content)->toContain('setImportSubmitting(false)')
        ->and($content)->toContain('xhr.status === 0')
        ->and($content)->not->toContain('timeout: 0')
        ->and($content)->not->toContain("$('#importOverlay')");
});

it('renders contextual table mode controls and preserves the selected mode across data views', function () {
    $page = $this->get(route('santunan-ramadhan.index', ['tahun' => 2027]))->assertOk();
    $content = $page->getContent();

    $dom = new DOMDocument;
    @$dom->loadHTML($content);
    $xpath = new DOMXPath($dom);

    expect($xpath->query('//*[@id="dataViewControls"]/*[@id="viewSelectionCard"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="dataViewControls"]/*[@id="tableModeControls"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="tableViewPanel"]//*[@id="tableModeControls"]')->length)->toBe(0)
        ->and($xpath->query('//*[@id="viewSelectionCard" and contains(@class, "border-emerald-100")]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="tableModeControls" and contains(@class, "border-slate-200")]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="btnViewTable" and @aria-selected="true" and @aria-controls="tableViewPanel"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="btnViewGrouped" and @aria-selected="false" and @aria-controls="groupedViewPanel"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="btnModeResponsive" and @aria-pressed="true"]')->length)->toBe(1)
        ->and($xpath->query('//*[@id="btnModeFull" and @aria-pressed="false"]')->length)->toBe(1)
        ->and($content)->toContain('Pilih cara menampilkan data peserta.')
        ->and($content)->toContain('Pilih perilaku tabel peserta.')
        ->and($content)->toContain('grid grid-cols-1 gap-4 md:grid-cols-2')
        ->and($content)->toContain("$('#tableModeControls')")
        ->and($content)->toContain(".toggleClass('hidden', !tableActive)")
        ->and($content)->toContain('if (table && tableMode === mode)')
        ->and($content)->toContain(".off('click.santunanView')")
        ->and(substr_count($content, "initTable('responsive');"))->toBe(1);
});

it('reuses one person across years and rejects a second participation in the same year', function () {
    config()->set('santunan.registration_open', true);
    $first = $this->postJson(route('santunan-ramadhan.submit'), multiYearPayload())->assertOk();
    $personId = $first->json('person_id');

    $this->postJson(route('santunan-ramadhan.submit'), multiYearPayload([
        'tahun_program' => 2027,
        'kategori' => 'yatim_dhuafa',
        'sumber_informasi' => 'Pak Maman',
        'rt' => '02',
        'rw' => '06',
        'nama_rt' => 'Pak Maman',
    ]))->assertOk()->assertJsonPath('person_id', $personId)->assertJsonPath('identity_reused', true);

    $this->postJson(route('santunan-ramadhan.submit'), multiYearPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('identity');

    expect(SantunanPerson::count())->toBe(1)
        ->and(SantunanParticipation::count())->toBe(2)
        ->and(SantunanParticipation::where('tahun_program', 2026)->sole()->kategori)->toBe('dhuafa')
        ->and(SantunanParticipation::where('tahun_program', 2027)->sole()->kategori)->toBe('yatim_dhuafa');

    expect(fn () => SantunanParticipation::query()->create([
        'person_id' => $personId,
        'tahun_program' => 2027,
        'kategori' => 'dhuafa',
        'umur' => 11,
        'umur_satuan' => 'tahun',
        'alamat' => 'Alamat duplikat',
        'nama_orang_tua' => 'Bapak Yanto',
    ]))->toThrow(QueryException::class);
});

it('does not auto-merge an ambiguous identity and rejects non-canonical category', function () {
    config()->set('santunan.registration_open', true);
    multiYearParticipation([
        'tanggal_lahir' => null,
        'no_wa' => null,
        'alamat' => 'Alamat Lama',
    ]);

    $this->postJson(route('santunan-ramadhan.submit'), multiYearPayload([
        'tahun_program' => 2027,
        'tanggal_lahir' => null,
        'no_wa' => null,
        'alamat' => 'Alamat Baru',
    ]))->assertUnprocessable()
        ->assertJsonPath('message', 'Identitas memiliki kandidat yang perlu diperiksa. Data tidak disimpan.')
        ->assertJsonStructure(['review' => [['person_id', 'nama', 'evidence']]]);

    $this->postJson(route('santunan-ramadhan.submit'), multiYearPayload(['kategori' => 'yatim']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('kategori');

    expect(SantunanPerson::count())->toBe(1)->and(SantunanParticipation::count())->toBe(1);
});

it('scopes table grouped search and cascade options to the selected year', function () {
    multiYearParticipation(['nama_lengkap' => 'Peserta 2026', 'sumber_informasi' => 'Sumber 2026', 'rt' => '06', 'rw' => '07']);
    multiYearParticipation(['tahun_program' => 2027, 'nama_lengkap' => 'Peserta 2027', 'sumber_informasi' => 'Sumber 2027', 'rt' => '02', 'rw' => '03']);

    $table = $this->getJson(route('santunan-ramadhan.data', [
        'tahun' => 2026, 'draw' => 1, 'start' => 0, 'length' => 100, 'search' => 'Peserta',
    ]))->assertOk()->assertJsonPath('recordsFiltered', 1);
    expect(collect($table->json('data'))->pluck('nama_lengkap')->all())->toBe(['Peserta 2026']);

    $grouped = $this->getJson(route('santunan-ramadhan.data-grouped', ['tahun' => 2027, 'search' => 'Peserta']))
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('groups.0.label', 'Sumber 2027');
    expect(collect($grouped->json('groups'))->pluck('label')->all())->not->toContain('Sumber 2026');

    $options = $this->getJson(route('santunan-ramadhan.filter-options', ['tahun' => 2026]))
        ->assertOk()->assertJsonPath('sources.0', 'Sumber 2026')->assertJsonPath('rws.0', '07')->assertJsonPath('rts.0', '06');
    expect($options->json('sources'))->not->toContain('Sumber 2027')
        ->and($options->json('categories'))->toBe(['dhuafa']);
});

it('starts a new year then edits and deletes only the target participation', function () {
    $returning = multiYearParticipation();
    $nonReturning = multiYearParticipation(['nama_lengkap' => 'Peserta Tidak Lanjut', 'no_wa' => '081234567891']);
    $legacySnapshot = $returning->fresh()->toArray();

    $this->postJson(route('santunan-ramadhan.start-year'), [
        'source_year' => 2026,
        'target_year' => 2027,
        'participation_ids' => [$returning->id],
    ])->assertOk()->assertJsonPath('created', 1);

    $target = SantunanParticipation::where('tahun_program', 2027)->sole();
    expect($target->person_id)->toBe($returning->person_id)
        ->and($target->id)->not->toBe($returning->id)
        ->and($target->legacy_registration_id)->toBeNull()
        ->and(SantunanParticipation::where('person_id', $nonReturning->person_id)->where('tahun_program', 2027)->exists())->toBeFalse();

    $this->putJson(route('santunan-ramadhan.update', $target), multiYearPayload([
        'tahun_program' => 2027,
        'kategori' => 'yatim_dhuafa',
        'sumber_informasi' => 'Pak Maman',
        'rt' => '02',
        'rw' => '06',
        'nama_rt' => 'Pak Maman',
    ]))->assertOk();

    expect($returning->fresh()->toArray())->toBe($legacySnapshot)
        ->and($target->fresh()->only(['kategori', 'sumber_informasi', 'rt', 'rw', 'nama_rt']))->toBe([
            'kategori' => 'yatim_dhuafa', 'sumber_informasi' => 'Pak Maman', 'rt' => '02', 'rw' => '06', 'nama_rt' => 'Pak Maman',
        ]);

    $this->deleteJson(route('santunan-ramadhan.destroy', $target), ['tahun_program' => 2027])->assertOk();
    expect(SantunanParticipation::whereKey($returning->id)->exists())->toBeTrue()
        ->and(SantunanParticipation::whereKey($target->id)->exists())->toBeFalse()
        ->and(SantunanPerson::whereKey($returning->person_id)->exists())->toBeTrue();
});

it('requires an explicit import year reuses identity and rejects a repeated workbook', function () {
    $row = [
        'dhuafa', 'Anak Import Tahunan', 'Anak', 'L', '07/05/2015', '', '', 'Bapak Import',
        'Pedagang', 'Alamat Import', '081234567899', 'Pak Indra', '', '06', '07', 'Pak Parno',
    ];

    $this->post(route('santunan-ramadhan.import'), ['file' => multiYearWorkbook($row)])
        ->assertSessionHasErrors('tahun_program');

    $this->post(route('santunan-ramadhan.import'), [
        'file' => multiYearWorkbook($row), 'tahun_program' => 2026,
    ])->assertOk()->assertJsonPath('created', 1);
    $personId = SantunanPerson::sole()->id;

    $this->post(route('santunan-ramadhan.import'), [
        'file' => multiYearWorkbook($row), 'tahun_program' => 2027,
    ])->assertOk()->assertJsonPath('identities_reused', 1);

    $this->post(route('santunan-ramadhan.import'), [
        'file' => multiYearWorkbook($row), 'tahun_program' => 2027,
    ])->assertUnprocessable()->assertJsonPath('success', false);

    expect(SantunanPerson::count())->toBe(1)
        ->and(SantunanParticipation::where('person_id', $personId)->count())->toBe(2);
});

it('ignores styled blank template rows during import', function () {
    $row = [
        'dhuafa', 'Anak Dari Template', 'Anak', 'L', '', '10', 'tahun', 'Bapak Template',
        'Pedagang', 'Alamat Template', '081234567898', 'Pak Indra', '', '06', '07', 'Pak Parno',
    ];

    $this->post(route('santunan-ramadhan.import'), [
        'file' => multiYearWorkbook($row, 1000), 'tahun_program' => 2027,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('created', 1)
        ->assertJsonPath('tahun_program', 2027);

    expect(SantunanPerson::count())->toBe(1)
        ->and(SantunanParticipation::where('tahun_program', 2027)->count())->toBe(1);
});

it('exports single and all workbooks only for the selected year', function () {
    multiYearParticipation(['nama_lengkap' => 'Nama Tahun 2026', 'sumber_informasi' => 'Pak Indra']);
    multiYearParticipation(['tahun_program' => 2027, 'nama_lengkap' => 'Nama Tahun 2027', 'sumber_informasi' => 'Pak Indra']);
    multiYearParticipation(['tahun_program' => 2027, 'nama_lengkap' => 'Nama Sumber Lain', 'sumber_informasi' => 'Pak Maman']);

    $single = $this->post(route('santunan-ramadhan.exportBySumber'), [
        'tahun_program' => 2026, 'sumber_informasi' => 'Pak Indra',
    ])->assertOk();
    $singlePath = tempnam(sys_get_temp_dir(), 'single-year-').'.xlsx';
    file_put_contents($singlePath, $single->streamedContent());
    $singleBook = IOFactory::load($singlePath);
    $singleValues = collect($singleBook->getAllSheets())->flatMap(fn ($sheet) => $sheet->toArray())->flatten();
    expect($singleValues)->toContain('Nama Tahun 2026')->not->toContain('Nama Tahun 2027');

    $all = $this->post(route('santunan-ramadhan.exportAll'), ['tahun_program' => 2027])->assertOk();
    $allPath = tempnam(sys_get_temp_dir(), 'all-year-').'.xlsx';
    file_put_contents($allPath, $all->streamedContent());
    $allBook = IOFactory::load($allPath);
    $allValues = collect($allBook->getAllSheets())->flatMap(fn ($sheet) => $sheet->toArray())->flatten();
    $coupons = collect($allBook->getAllSheets())->flatMap(fn ($sheet) => collect($sheet->toArray())->pluck(0)->filter(fn ($value) => is_numeric($value)))->map(fn ($value) => (int) $value)->values()->all();

    expect($allBook->getSheetNames())->toBe(['Pak Indra', 'Pak Maman'])
        ->and($allValues)->toContain('Nama Tahun 2027', 'Nama Sumber Lain')->not->toContain('Nama Tahun 2026')
        ->and($coupons)->toBe([1, 2]);

    @unlink($singlePath);
    @unlink($allPath);
});
