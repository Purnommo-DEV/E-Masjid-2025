<?php

use App\Domain\FinancialV2\DecimalAmount;

test('adds, subtracts, and compares large monetary values exactly', function () {
    expect(DecimalAmount::add('90071992547409.91', '0.09'))->toBe('90071992547410.00')
        ->and(DecimalAmount::subtract('90071992547410.00', '0.01'))->toBe('90071992547409.99')
        ->and(DecimalAmount::add('0.01', '-0.02'))->toBe('-0.01')
        ->and(DecimalAmount::compare('100.00', '99.99'))->toBe(1)
        ->and(DecimalAmount::sum(['99999999999999999.99', '0.01']))->toBe('100000000000000000.00');
});

test('normalizes the MySQL DECIMAL scale without using floats', function () {
    expect(DecimalAmount::normalize('42'))->toBe('42.00')
        ->and(DecimalAmount::normalize('-0004.5'))->toBe('-4.50')
        ->and(DecimalAmount::equals('7.5', '7.50'))->toBeTrue();
});
