<?php

use App\Domain\FinancialV2\Reporting\AccountingDateCorrectionOperationalVisibility;

function completedCorrectionFact(array $overrides = []): array
{
    return array_replace([
        'request_status' => 'completed',
        'request_entity_id' => 'entity-1',
        'pair_status' => 'completed',
        'pair_original_transaction_id' => 'original-1',
        'pair_original_journal_id' => 'original-journal-1',
        'pair_reversal_transaction_id' => 'reversal-1',
        'pair_replacement_transaction_id' => 'replacement-1',
        'original_transaction_id' => 'original-1',
        'original_journal_id' => 'original-journal-1',
        'reversal_transaction_id' => 'reversal-1',
        'reversal_journal_id' => 'reversal-journal-1',
        'replacement_transaction_id' => 'replacement-1',
        'replacement_journal_id' => 'replacement-journal-1',
        'original_transaction_entity_id' => 'entity-1',
        'reversal_transaction_entity_id' => 'entity-1',
        'replacement_transaction_entity_id' => 'entity-1',
        'original_transaction_status' => 'reversed',
        'reversal_transaction_status' => 'posted',
        'replacement_transaction_status' => 'posted',
        'original_journal_transaction_id' => 'original-1',
        'original_journal_status' => 'reversed',
        'reversal_journal_transaction_id' => 'reversal-1',
        'reversal_journal_status' => 'posted',
        'replacement_journal_transaction_id' => 'replacement-1',
        'replacement_journal_status' => 'posted',
    ], $overrides);
}

test('completed correction hides only original and technical reversal', function () {
    $hidden = (new AccountingDateCorrectionOperationalVisibility)->hiddenIdsFromFacts([
        completedCorrectionFact(),
    ]);

    expect($hidden)->toBe(['original-1', 'reversal-1'])
        ->and($hidden)->not->toContain('replacement-1');
});

test('invalid or incomplete correction facts remain visible fail closed', function (string $field, mixed $value) {
    $hidden = (new AccountingDateCorrectionOperationalVisibility)->hiddenIdsFromFacts([
        completedCorrectionFact([$field => $value]),
    ]);

    expect($hidden)->toBe([]);
})->with([
    'replacement is not posted' => ['replacement_transaction_status', 'approved'],
    'reversal is not posted' => ['reversal_transaction_status', 'posting'],
    'original is not reversed' => ['original_transaction_status', 'posted'],
    'replacement journal is not posted' => ['replacement_journal_status', 'draft'],
    'reversal journal is not posted' => ['reversal_journal_status', 'draft'],
    'replacement relation is missing' => ['replacement_transaction_id', null],
    'request is not completed' => ['request_status', 'executing'],
    'execution pair is not completed' => ['pair_status', 'execution_ready'],
    'pair points to another replacement' => ['pair_replacement_transaction_id', 'replacement-2'],
    'replacement journal belongs to another transaction' => ['replacement_journal_transaction_id', 'replacement-2'],
    'replacement belongs to another entity' => ['replacement_transaction_entity_id', 'entity-2'],
]);

test('normal transactions are not introduced into the hidden set and duplicate lineage rows are deduplicated', function () {
    $fact = completedCorrectionFact();
    $hidden = (new AccountingDateCorrectionOperationalVisibility)->hiddenIdsFromFacts([$fact, $fact]);

    expect($hidden)->toBe(['original-1', 'reversal-1'])
        ->and($hidden)->not->toContain('normal-transaction');
});
