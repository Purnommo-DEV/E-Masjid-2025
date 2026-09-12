<?php

use App\Models\PendaftaranAnakYatimDhuafa;
use Database\Seeders\SantunanRamadhanRtRwEnrichmentSeeder;
use Illuminate\Support\Facades\DB;

function enrichmentRecipient(array $overrides = []): PendaftaranAnakYatimDhuafa
{
    static $sequence = 0;
    $sequence++;

    return PendaftaranAnakYatimDhuafa::create(array_merge([
        'tahun_program' => 2026,
        'sumber_informasi' => 'Pak Indra',
        'kategori' => 'dhuafa',
        'nama_lengkap' => 'Peserta Enrichment '.$sequence,
        'nama_panggilan' => null,
        'umur' => 10,
        'umur_satuan' => 'tahun',
        'tanggal_lahir' => '2016-01-01',
        'jenis_kelamin' => 'L',
        'alamat' => 'RT.06 RW.07 (PAK PARNO)',
        'rt' => null,
        'rw' => null,
        'nama_rt' => null,
        'no_wa' => '081234567890',
        'nama_orang_tua' => 'Orang Tua '.$sequence,
        'pekerjaan_orang_tua' => 'Wiraswasta',
        'status' => 'baru',
        'catatan_tambahan' => 'Tetap',
        'catatan_admin' => 'Tidak boleh berubah',
        'ip_address' => '127.0.0.1',
        'created_at' => '2026-02-01 10:00:00',
        'updated_at' => '2026-02-01 10:00:00',
    ], $overrides));
}

it('safely enriches only confident Pak Indra 2026 addresses and is idempotent', function () {
    $parno = enrichmentRecipient();
    $arif = enrichmentRecipient([
        'alamat' => "RT\u{00A0}01 RW 06 (PAK ARIF)",
        'rt' => '01',
        'rw' => '06',
        'nama_rt' => 'Pak Arif',
    ]);
    $susiDiscrepancy = enrichmentRecipient([
        'alamat' => 'RT 05 RW 04 (RT Bu SUSI)',
        'rt' => '99',
        'rw' => '99',
        'nama_rt' => 'Koordinator Salah',
    ]);
    $wakidjoPartial = enrichmentRecipient([
        'alamat' => 'Jl. Jaelani RT 03/06 ( Pak Wakidjo )',
        'rt' => '03',
    ]);
    $anas = enrichmentRecipient(['alamat' => 'RT 04 RW 06 (RT Pak Anas)']);
    $wawang = enrichmentRecipient(['alamat' => 'RT 03/04 Kreo Selatan (Pak Wawang)']);
    $purwanto04 = enrichmentRecipient(['alamat' => 'RT04/RW05 Jurangmangu (Pak Purwanto)']);
    $purwanto01 = enrichmentRecipient(['alamat' => 'RT01/ RW05 Jurtim (Pak Purwanto)']);
    $purwanto05 = enrichmentRecipient(['alamat' => 'RT05/ RW05 Jurtim (Pak Purwanto)']);
    $maman = enrichmentRecipient(['alamat' => 'RT.002 RW.06 (Pak Maman)']);
    $ibrahim = enrichmentRecipient(['alamat' => 'Duta 1 Kav. 112B RT 01/09 (Pak Ibrahim)']);
    $ambiguous = enrichmentRecipient(['alamat' => '----']);
    $conflictingAddress = enrichmentRecipient(['alamat' => 'RT 06/07 (Pak Arif)']);
    $otherSource = enrichmentRecipient(['sumber_informasi' => 'Sumber Lain']);
    $otherYear = enrichmentRecipient(['tahun_program' => 2025]);

    $allBefore = PendaftaranAnakYatimDhuafa::orderBy('id')->get()->keyBy('id');
    $countBefore = PendaftaranAnakYatimDhuafa::count();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $report = app(SantunanRamadhanRtRwEnrichmentSeeder::class)->run();
    $queries = collect(DB::getQueryLog());
    DB::disableQueryLog();

    expect($report)
        ->target_before->toBe(13)
        ->target_after->toBe(13)
        ->updated->toBe(9)
        ->already_correct->toBe(1)
        ->ambiguous->toBe(2)
        ->remaining_null->toBe(2)
        ->unexpected->toBe(1)
        ->and($report['ambiguous_records'])->toHaveCount(2)
        ->and($report['discrepancies'])->toHaveCount(1)
        ->and($report['breakdown'])->toMatchArray([
            'RT 06/07 — Pak Parno' => 1,
            'RT 01/06 — Pak Arif' => 1,
            'RT 05/04 — Bu Susi' => 0,
            'RT 03/06 — Pak Wakidjo' => 1,
            'RT 04/06 — Pak Anas' => 1,
            'RT 03/04 — Pak Wawang' => 1,
            'RT 04/05 — Pak Purwanto' => 1,
            'RT 01/05 — Pak Purwanto' => 1,
            'RT 05/05 — Pak Purwanto' => 1,
            'RT 02/06 — Pak Maman' => 1,
            'RT 01/09 — Pak Ibrahim' => 1,
        ]);

    expect($parno->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '06', 'rw' => '07', 'nama_rt' => 'Pak Parno'])
        ->and($arif->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '01', 'rw' => '06', 'nama_rt' => 'Pak Arif'])
        ->and($wakidjoPartial->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '03', 'rw' => '06', 'nama_rt' => 'Pak Wakidjo'])
        ->and($anas->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '04', 'rw' => '06', 'nama_rt' => 'Pak Anas'])
        ->and($wawang->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '03', 'rw' => '04', 'nama_rt' => 'Pak Wawang'])
        ->and($purwanto04->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '04', 'rw' => '05', 'nama_rt' => 'Pak Purwanto'])
        ->and($purwanto01->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '01', 'rw' => '05', 'nama_rt' => 'Pak Purwanto'])
        ->and($purwanto05->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '05', 'rw' => '05', 'nama_rt' => 'Pak Purwanto'])
        ->and($maman->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '02', 'rw' => '06', 'nama_rt' => 'Pak Maman'])
        ->and($ibrahim->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '01', 'rw' => '09', 'nama_rt' => 'Pak Ibrahim'])
        ->and($ambiguous->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => null, 'rw' => null, 'nama_rt' => null])
        ->and($conflictingAddress->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => null, 'rw' => null, 'nama_rt' => null])
        ->and($susiDiscrepancy->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => '99', 'rw' => '99', 'nama_rt' => 'Koordinator Salah'])
        ->and($otherSource->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => null, 'rw' => null, 'nama_rt' => null])
        ->and($otherYear->fresh()->only('rt', 'rw', 'nama_rt'))->toBe(['rt' => null, 'rw' => null, 'nama_rt' => null])
        ->and(PendaftaranAnakYatimDhuafa::count())->toBe($countBefore);

    foreach (PendaftaranAnakYatimDhuafa::orderBy('id')->get() as $record) {
        expect(collect($record->toArray())->except(['rt', 'rw', 'nama_rt'])->all())
            ->toBe(collect($allBefore[$record->id]->toArray())->except(['rt', 'rw', 'nama_rt'])->all());
    }

    $mutations = $queries->filter(fn ($query) => preg_match('/^\s*(insert|update|delete)\s/i', $query['query']));
    expect($mutations)->toHaveCount(9)
        ->and($mutations->every(fn ($query) => str_starts_with(strtolower(trim($query['query'])), 'update `pendaftaran_anak_yatim_dhuafa`')))->toBeTrue()
        ->and($mutations->every(fn ($query) => ! str_contains(strtolower($query['query']), 'updated_at')))->toBeTrue()
        ->and($mutations->contains(fn ($query) => preg_match('/^\s*(insert|delete)\s/i', $query['query'])))->toBeFalse()
        ->and($mutations->contains(fn ($query) => preg_match('/financial|journal|ledger|voucher|allocation|realization|distribution|fund/i', $query['query'])))->toBeFalse();

    DB::flushQueryLog();
    DB::enableQueryLog();
    $secondReport = app(SantunanRamadhanRtRwEnrichmentSeeder::class)->run();
    $secondMutations = collect(DB::getQueryLog())
        ->filter(fn ($query) => preg_match('/^\s*(insert|update|delete)\s/i', $query['query']));
    DB::disableQueryLog();

    expect($secondReport['updated'])->toBe(0)
        ->and($secondReport['already_correct'])->toBe(10)
        ->and($secondReport['ambiguous'])->toBe(2)
        ->and($secondReport['unexpected'])->toBe(1)
        ->and($secondMutations)->toBeEmpty();
});
