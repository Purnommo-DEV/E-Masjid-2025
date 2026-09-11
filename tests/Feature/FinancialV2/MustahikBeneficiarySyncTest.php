<?php

use App\Domain\FinancialV2\DistributionService;
use App\Domain\FinancialV2\MustahikBeneficiarySyncService;
use App\Domain\FinancialV2\MustahikMrjSource;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItem;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use Illuminate\Validation\ValidationException;
use Tests\Support\UatFinancialFixture;

test('reviewed MRJ mustahik source preserves the scan totals and uncertainty', function () {
    $records = collect(app(MustahikMrjSource::class)->records());

    expect($records)->toHaveCount(139)
        ->and($records->pluck('source_no')->first())->toBe(1)
        ->and($records->pluck('source_no')->last())->toBe(139)
        ->and($records->where('tanda_terima', 1))->toHaveCount(107)
        ->and($records->where('tanda_terima', 0))->toHaveCount(32)
        ->and($records->where('was_manually_reviewed', true)->where('tanda_terima', 1))->toHaveCount(10)
        ->and($records->where('was_manually_reviewed', true)->where('needs_manual_verification', false))->toHaveCount(7)
        ->and($records->where('needs_manual_verification', true))->toHaveCount(5)
        ->and($records->where('needs_manual_verification', true)->where('tanda_terima', 1))->toHaveCount(3)
        ->and($records->firstWhere('source_no', 10))->toMatchArray(['source_name' => 'Risug awati', 'name' => 'Tosah'])
        ->and($records->firstWhere('source_no', 37))->toMatchArray(['source_name' => 'Nasik', 'name' => 'Uni Yeni'])
        ->and($records->firstWhere('source_no', 61))->toMatchArray(['source_name' => 'Masilah', 'name' => 'Masilah'])
        ->and($records->firstWhere('source_no', 74))->toMatchArray(['source_name' => 'Nenina', 'name' => 'Munah'])
        ->and($records->firstWhere('source_no', 78))->toMatchArray(['source_name' => 'Tursinah', 'name' => 'Atanah'])
        ->and($records->firstWhere('source_no', 92))->toMatchArray(['source_name' => 'Bu Ima', 'name' => 'Isnawati Hasanah'])
        ->and($records->firstWhere('source_no', 121))->toMatchArray(['source_name' => 'TK Taman (TK Taman)', 'name' => 'Ucup (TK Taman)'])
        ->and($records->where('beneficiary_type', 'BELUM_DITENTUKAN'))->toHaveCount(139);
});

test('sync is idempotent matches exact beneficiaries preserves zero status and creates no financial facts', function () {
    $context = UatFinancialFixture::context();
    $beneficiaries = app(DistributionService::class);
    $existingApproved = $beneficiaries->saveBeneficiary($context['entity']->id, [
        'display_name' => '  KOKOM ',
        'beneficiary_type' => 'DHUAFA',
        'address' => 'Alamat existing yang harus dipertahankan',
        'status' => 'inactive',
    ], null, null);
    $existingZero = $beneficiaries->saveBeneficiary($context['entity']->id, [
        'display_name' => 'Jamaludin',
        'beneficiary_type' => 'BELUM_DITENTUKAN',
        'status' => 'inactive',
    ], null, null);
    $factsBefore = [
        FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(),
        Distribution::count(), DistributionItem::count(),
    ];

    $first = app(MustahikBeneficiarySyncService::class)->sync($context['entity']);
    $kokom = $existingApproved->fresh();
    $jamaludin = $existingZero->fresh();
    $sainah = Counterparty::forEntity($context['entity']->id)->where('display_name', 'Sainah')->sole();
    $subur = Counterparty::forEntity($context['entity']->id)->where('display_name', 'Subur')->sole();

    expect($first['source_records'])->toBe(139)
        ->and($first['approved'])->toBe(107)
        ->and($first['not_approved'])->toBe(32)
        ->and($first['created'])->toBe(103)
        ->and($first['updated'])->toBe(2)
        ->and($first['matched'])->toBe(2)
        ->and($first['already_matched'])->toBe(0)
        ->and($first['manual_review_resolved'])->toBe(7)
        ->and($first['needs_manual_verification'])->toBe(5)
        ->and($first['duplicate_prevented'])->toBe(2)
        ->and($first['categories'])->toBe(['YATIM' => 0, 'DHUAFA' => 1, 'YATIM_DHUAFA' => 0, 'BELUM_DITENTUKAN' => 103])
        ->and(Counterparty::forEntity($context['entity']->id)->where('party_type', 'beneficiary')->count())->toBe(105)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Kokom')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Bano')->count())->toBe(0)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Tosah')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Uni Yeni')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Masilah')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Munah')->where('rt', '03')->where('rw', '04')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Atanah')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Isnawati Hasanah')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Ucup (TK Taman)')->count())->toBe(1)
        ->and(Counterparty::forEntity($context['entity']->id)->whereIn('display_name', ['Fannya atau Tafiq', 'Daus'])->count())->toBe(0)
        ->and(Counterparty::forEntity($context['entity']->id)->where('display_name', 'Purwanti')->count())->toBe(0)
        ->and($kokom->status)->toBe('active')
        ->and($kokom->beneficiary_type)->toBe('DHUAFA')
        ->and($kokom->address)->toBe('Alamat existing yang harus dipertahankan')
        ->and($jamaludin->status)->toBe('inactive')
        ->and($jamaludin->beneficiary_notes)->toContain('Tanda Terima: 0')
        ->and($sainah->status)->toBe('active')
        ->and($sainah->beneficiary_type)->toBe('BELUM_DITENTUKAN')
        ->and($sainah->rt)->toBe('03')
        ->and($sainah->rw)->toBe('06')
        ->and($sainah->rt_coordinator_name)->toBe('Pak Wakidjo')
        ->and($sainah->contact_reference)->toBeNull()
        ->and($subur->rt)->toBe('05')
        ->and($subur->rw)->toBe('04')
        ->and([FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Distribution::count(), DistributionItem::count()])->toBe($factsBefore);

    $second = app(MustahikBeneficiarySyncService::class)->sync($context['entity']);
    expect($second['created'])->toBe(0)
        ->and($second['updated'])->toBe(0)
        ->and($second['already_matched'])->toBe(105)
        ->and($second['matched'])->toBe(105)
        ->and($second['duplicate_prevented'])->toBe(105)
        ->and(Counterparty::forEntity($context['entity']->id)->where('party_type', 'beneficiary')->count())->toBe(105)
        ->and([FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count(), Distribution::count(), DistributionItem::count()])->toBe($factsBefore);
});

test('beneficiary type accepts only the governed classifications', function () {
    $context = UatFinancialFixture::context();

    expect(fn () => app(DistributionService::class)->saveBeneficiary($context['entity']->id, [
        'display_name' => 'Kategori Tidak Valid',
        'beneficiary_type' => 'TEBAKAN',
        'status' => 'active',
    ], null, null))->toThrow(ValidationException::class);
});
