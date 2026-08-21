<?php

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\OpeningBalanceService;
use App\Domain\FinancialV2\PostingEngine;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\MappingSet;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\OpeningBalanceFixture;

test('balanced opening succeeds through the canonical Posting Engine with immutable Journal and Ledger facts', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context);
    $result = app(OpeningBalanceService::class)->post($fixture['batch']->id);

    expect(OpeningBalanceBatch::findOrFail($fixture['batch']->id)->status)->toBe('posted')
        ->and(Journal::findOrFail($result->journalId)->total_debit)->toBe('100.00')
        ->and(Journal::findOrFail($result->journalId)->total_credit)->toBe('100.00')
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});

test('unbalanced opening is rejected before review', function () {
    $context = OpeningBalanceFixture::context();
    $service = app(OpeningBalanceService::class);
    $set = $service->createMappingSet(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-UNBAL', 'name' => 'Unbalanced', 'source_system_name' => 'fixture']);
    OpeningBalanceFixture::map($service, $set, 'ONLY-CASH', 'account', $context['cash']->id);
    OpeningBalanceFixture::map($service, $set, 'ONLY-CASH', 'fund', $context['fund']->id);
    OpeningBalanceFixture::map($service, $set, 'ONLY-CASH', 'financial_account', $context['financialAccount']->id);
    $service->reviewMappingSet($set->id);
    $service->approveMappingSet($set->id);
    $batch = $service->createDraft(['accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id, 'mapping_set_id' => $set->id, 'position_date' => now()->toDateString(), 'rehearsal_reference' => 'UNBALANCED', 'evidence_package_ref' => 'fixture']);
    $line = $service->addLine($batch->id, ['account_id' => $context['cash']->id, 'fund_id' => $context['fund']->id, 'financial_account_id' => $context['financialAccount']->id, 'debit_amount' => '100', 'credit_amount' => '0', 'source_debit_amount' => '100', 'source_credit_amount' => '0', 'source_reference' => 'ONLY-CASH', 'evidence_ref' => 'statement']);
    OpeningBalanceFixture::attach($context['entity']->id, $line->id, 'unbalanced');

    expect(fn () => $service->review($batch->id))->toThrow(FinancialDomainException::class, 'balance');
});

test('duplicate opening reference is prevented', function () {
    $context = OpeningBalanceFixture::context();
    OpeningBalanceFixture::approvedBatch($context, 'ONE-REFERENCE');
    $set = MappingSet::where('accounting_entity_id', $context['entity']->id)->firstOrFail();

    expect(fn () => app(OpeningBalanceService::class)->createDraft(['accounting_entity_id' => $context['entity']->id, 'accounting_period_id' => $context['period']->id, 'mapping_set_id' => $set->id, 'position_date' => now()->toDateString(), 'rehearsal_reference' => 'ONE-REFERENCE', 'evidence_package_ref' => 'fixture']))->toThrow(FinancialDomainException::class, 'already been imported');
});

test('posted opening batch and lines are immutable', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context);
    app(OpeningBalanceService::class)->post($fixture['batch']->id);

    expect(fn () => OpeningBalanceBatch::findOrFail($fixture['batch']->id)->update(['evidence_package_ref' => 'changed']))->toThrow(DomainException::class)
        ->and(fn () => OpeningBalanceLine::findOrFail($fixture['lines'][0]->id)->update(['line_description' => 'changed']))->toThrow(DomainException::class);
});

test('a posted opening can be corrected with a governed reversal, not an edit', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context);
    $posted = app(OpeningBalanceService::class)->post($fixture['batch']->id);
    $reversal = OpeningBalanceFixture::correctionTransaction($context, 'REV', 'reversal', $posted->transactionId);
    $result = app(PostingEngine::class)->post($reversal->id, 'opening-reversal', hash('sha256', 'opening-reversal'));

    expect(Journal::findOrFail($result->journalId)->reversal_of_journal_id)->toBe($posted->journalId)
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});

test('a posted opening can be adjusted through the governed adjustment workflow', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context);
    app(OpeningBalanceService::class)->post($fixture['batch']->id);
    $adjustment = OpeningBalanceFixture::correctionTransaction($context, 'ADJ', 'adjustment');

    expect(app(PostingEngine::class)->post($adjustment->id, 'opening-adjustment', hash('sha256', 'opening-adjustment'))->journalId)->not->toBeEmpty();

    $row = collect(app(FinancialReportService::class)->report('fund-balance', $context['entity']->id, now()->toDateString(), now()->toDateString())['data']['rows'])
        ->sole('fund_id', $context['fund']->id);
    expect($row['fund_balance'])->toBe('110.00')
        ->and($row['adjustments'])->toBe('10.00');
});

test('evidence is required and source traceability is preserved on every line', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'NO-EVIDENCE', false);
    $service = app(OpeningBalanceService::class);
    expect($service->summary($fixture['batch']->id)['lines'][0]['source_reference'])->toBe('SOURCE-CASH');
    expect(fn () => $service->approve($fixture['batch']->id))->toThrow(FinancialDomainException::class, 'evidence');
});

test('valid explicit mappings can be approved without treating Program as Fund', function () {
    $context = OpeningBalanceFixture::context();
    $program = Program::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'P-OB', 'name' => 'Program Opening', 'status' => 'active']);
    $service = app(OpeningBalanceService::class);
    $set = $service->createMappingSet(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-PROG', 'name' => 'Program mapping', 'source_system_name' => 'fixture']);
    OpeningBalanceFixture::map($service, $set, 'PROGRAM-SOURCE', 'program', $program->id);
    $service->reviewMappingSet($set->id);
    $approved = $service->approveMappingSet($set->id);

    expect($approved->mapping_status)->toBe('approved');
});

test('a relevant Program is optional, separately mapped, and retained in Journal and Ledger', function () {
    $context = OpeningBalanceFixture::context();
    $program = Program::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'P-OPEN', 'name' => 'Program Opening', 'status' => 'active']);
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'WITH-PROGRAM', true, '100.00', $program);
    $result = app(OpeningBalanceService::class)->post($fixture['batch']->id);

    expect(JournalLine::where('journal_id', $result->journalId)->where('program_id', $program->id)->count())->toBe(1)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->where('program_id', $program->id)->count())->toBe(1);
});

test('unmapped and ambiguous sources are rejected rather than guessed', function (string $outcome) {
    $context = OpeningBalanceFixture::context();
    $service = app(OpeningBalanceService::class);
    $set = $service->createMappingSet(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-'.$outcome, 'name' => 'Blocked mapping', 'source_system_name' => 'fixture']);
    $service->recordMapping($set->id, 'UNKNOWN', 'account', $outcome, null, 'unresolved source', 'Needs governance decision');
    $service->reviewMappingSet($set->id);

    expect(fn () => $service->approveMappingSet($set->id))->toThrow(FinancialDomainException::class, 'All mappings');
})->with(['unmapped', 'ambiguous']);

test('duplicate mapping is rejected before it can create an import ambiguity', function () {
    $context = OpeningBalanceFixture::context();
    $service = app(OpeningBalanceService::class);
    $set = $service->createMappingSet(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-DUP', 'name' => 'Duplicate mapping', 'source_system_name' => 'fixture']);
    OpeningBalanceFixture::map($service, $set, 'SOURCE', 'account', $context['cash']->id);

    expect(fn () => OpeningBalanceFixture::map($service, $set, 'SOURCE', 'account', $context['equity']->id))->toThrow(FinancialDomainException::class, 'already mapped');
});

test('a rejected mapping remains explicit archive scope and is never converted into a target', function () {
    $context = OpeningBalanceFixture::context();
    $set = app(OpeningBalanceService::class)->createMappingSet(['accounting_entity_id' => $context['entity']->id, 'code' => 'MAP-REJECTED', 'name' => 'Rejected source', 'source_system_name' => 'fixture']);
    $mapping = app(OpeningBalanceService::class)->recordMapping($set->id, 'ARCHIVE-ONLY', 'account', 'rejected', null, 'out of scope', 'Historical archive only');

    expect($mapping->mapping_status)->toBe('out_of_scope_archive')
        ->and($mapping->target_entity_id)->toBeNull();
});

test('account and fund opening positions reconcile with explicit zero difference', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context);
    $summary = app(OpeningBalanceService::class)->summary($fixture['batch']->id);

    expect($summary['by_account'][0]['difference'])->toBe('0.00')
        ->and($summary['by_fund'][0]['difference'])->toBe('0.00')
        ->and($summary['totals']['difference'])->toBe('0.00');
});

test('a source difference is visible and mismatch cannot be finalized', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'DIFFERENCE', true, '99.00');
    $service = app(OpeningBalanceService::class);
    $summary = $service->reconcile($fixture['batch']->id);

    expect($summary['totals']['difference'])->toBe('1.00')
        ->and($summary['lines'][0]['reconciliation_status'])->toBe('difference')
        ->and(fn () => $service->review($fixture['batch']->id))->toThrow(FinancialDomainException::class, 'reconcile');
});

test('a clean isolated rehearsal imports, reconciles, and posts an opening position', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'CLEAN-REHEARSAL');
    $result = app(OpeningBalanceService::class)->post($fixture['batch']->id);

    expect($result->journalId)->not->toBeEmpty()->and(LedgerEntry::count())->toBe(2);
});

test('two clean rehearsal runs produce the same opening position result', function () {
    $firstContext = OpeningBalanceFixture::context();
    $first = OpeningBalanceFixture::approvedBatch($firstContext, 'REHEARSAL-ONE');
    app(OpeningBalanceService::class)->post($first['batch']->id);
    $secondContext = OpeningBalanceFixture::context();
    $second = OpeningBalanceFixture::approvedBatch($secondContext, 'REHEARSAL-TWO');
    app(OpeningBalanceService::class)->post($second['batch']->id);

    expect(app(OpeningBalanceService::class)->summary($first['batch']->id)['totals'])->toBe(app(OpeningBalanceService::class)->summary($second['batch']->id)['totals'])
        ->and(Journal::where('accounting_entity_id', $firstContext['entity']->id)->count())->toBe(1)
        ->and(Journal::where('accounting_entity_id', $secondContext['entity']->id)->count())->toBe(1);
});

test('repeated opening import posting is idempotent with no duplicate journal or ledger', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'IDEMPOTENT');
    $service = app(OpeningBalanceService::class);
    $first = $service->post($fixture['batch']->id);
    $second = $service->post($fixture['batch']->id);

    expect($second->journalId)->toBe($first->journalId)
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});

test('posted opening flows into account, fund, financial account, trial balance, and cash reports', function () {
    $context = OpeningBalanceFixture::context();
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'REPORTING');
    app(OpeningBalanceService::class)->post($fixture['batch']->id);
    $date = now()->toDateString();
    $reports = app(FinancialReportService::class);

    $fundReport = $reports->report('fund-balance', $context['entity']->id, $date, $date)['data'];
    $fundRow = collect($fundReport['rows'])->sole('fund_id', $context['fund']->id);

    expect(app(BalanceInquiryService::class)->accountBalance($context['entity']->id, $context['cash']->id, $date)['balance'])->toBe('100.00')
        ->and(app(BalanceInquiryService::class)->financialAccountBalance($context['entity']->id, $context['financialAccount']->id, $date)['balance'])->toBe('100.00')
        ->and($fundReport['has_data'])->toBeTrue()
        ->and($fundRow['fund_balance'])->toBe('100.00')
        ->and($fundRow['available_liquidity'])->toBe('100.00')
        ->and($reports->report('account-balance', $context['entity']->id, $date, $date)['data']['has_data'])->toBeTrue()
        ->and($reports->report('trial-balance', $context['entity']->id, $date, $date)['data']['is_balanced'])->toBeTrue()
        ->and($reports->report('cash-flow', $context['entity']->id, $date, $date)['data']['has_data'])->toBeTrue();
});

test('opening posting respects a closed period', function () {
    $context = OpeningBalanceFixture::context('hard_closed');
    $fixture = OpeningBalanceFixture::approvedBatch($context, 'CLOSED-PERIOD');

    expect(fn () => app(OpeningBalanceService::class)->post($fixture['batch']->id))->toThrow(FinancialPostingException::class, 'open accounting period');
});

test('opening-balance rehearsal schema has Program attribution, FK, reconciliation indexes, and database constraint coverage', function () {
    expect(Schema::hasColumns('financial_v2_opening_balance_lines', ['program_id', 'source_reference', 'source_debit_amount', 'source_credit_amount', 'reconciliation_difference', 'reconciliation_status']))->toBeTrue();
    $indexes = collect(DB::select('SHOW INDEX FROM financial_v2_opening_balance_lines'))->pluck('Key_name');
    $foreignKeys = collect(DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'financial_v2_opening_balance_lines' AND CONSTRAINT_TYPE = 'FOREIGN KEY'"))->pluck('CONSTRAINT_NAME');

    expect($indexes)->toContain('fv2_open_line_fin_acc_ix')->toContain('fv2_open_line_program_ix')->toContain('fv2_open_line_recon_status_ix')
        ->and($foreignKeys->count())->toBeGreaterThanOrEqual(5);
});
