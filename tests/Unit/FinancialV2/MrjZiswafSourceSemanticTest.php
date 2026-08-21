<?php

use App\Domain\FinancialV2\MrjZiswafOpeningPosition;

test('the final ZISWAF workbook is an opening-position source and carries no allocation event to import', function () {
    $audit = MrjZiswafOpeningPosition::allocationSourceAudit();

    expect($audit['source_filename'])->toBe('ZISWAF UPDATE 3.xlsx')
        ->and($audit['source_sha256'])->toBe('404FC8CD54ECD3E35E17C30FFE6A3D88DF6656260CBEAC8F614EF99689A02F9C')
        ->and($audit['worksheets'])->toHaveCount(7)
        ->and($audit['allocation_event_count'])->toBe(0)
        ->and($audit['decision'])->toBe('no_allocation_import');
});

test('source fund history remains source-only receipt and usage lineage rather than allocation history', function () {
    expect(collect(MrjZiswafOpeningPosition::fundSourceHistory('SODAQOH'))->pluck('kind')->all())
        ->toBe(['receipt', 'usage', 'closing'])
        ->and(collect(MrjZiswafOpeningPosition::fundSourceHistory('ZAKAT-MAAL'))->pluck('amount')->all())
        ->toBe(['97145386.00', '10700000.00', '10700000.00', '75745386.00'])
        ->and(collect(MrjZiswafOpeningPosition::fundSourceHistory('DHUAFA'))->pluck('amount')->all())
        ->toBe(['20378977.00', '5530000.00', '1095000.00', '4095000.00', '9658977.00']);
});

test('historical Fund usage retains source date labels, row lineage, and separate cash composition', function () {
    $infaq = collect(MrjZiswafOpeningPosition::fundSourceHistory('INFAQ-TROMOL'));
    $activity = $infaq->whereIn('kind', ['receipt', 'usage']);

    expect($activity->where('kind', 'receipt')->pluck('amount')->map(fn (string $amount) => (int) $amount)->sum())->toBe(20_945_000)
        ->and($activity->where('kind', 'usage')->pluck('amount')->map(fn (string $amount) => (int) $amount)->sum())->toBe(4_278_051)
        ->and($infaq->firstWhere('description', 'Cash Tromol 10 Desember 2025')['date_label'])->toBe('10 Desember 2025')
        ->and($infaq->firstWhere('description', 'Beras 20 Pack'))->toBeNull()
        ->and($infaq->firstWhere('kind', 'account_position')['amount'])->toBe('2653000.00')
        ->and($infaq->firstWhere('kind', 'account_position')['source_reference'])->toBe('Sisa Alokasi Dana!D66:E66');
});
