<?php

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Program;
use Illuminate\Support\Facades\Storage;

/** @return array{0:string,1:string} */
function phase12MrjFixtureFiles(): array
{
    $directory = storage_path('framework/testing/phase12-mrj-opening');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $source = $directory.'/ZISWAF UPDATE 3.xlsx';
    $evidence = $directory.'/mrj-ziswaf-source-evidence.pdf';
    file_put_contents($source, 'Phase 12 test-only source fixture');
    file_put_contents($evidence, "%PDF-1.4\n% Phase 12 test-only evidence\n");

    return [$source, $evidence];
}

test('Phase 12 provisions governed operational masters without creating or duplicating MRJ financial facts', function () {
    Storage::fake('local');
    [$source, $evidence] = phase12MrjFixtureFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->firstOrFail();
    $factsBefore = phase12Facts($entity->id);
    expect(BudgetAllocation::query()->where('accounting_entity_id', $entity->id)->count())->toBe(0);

    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])
        ->assertExitCode(0);

    $funds = Fund::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->pluck('id', 'code');
    expect($funds->keys()->all())->toContain('ZAKAT-MAAL', 'INFAQ-TROMOL', 'SODAQOH', 'SANTUNAN-YATIM', 'FIDYAH', 'DHUAFA', 'OPERASIONAL-MASJID', 'QURBAN', 'RAMADHAN', 'SOSIAL-KEMATIAN', 'SEWA-AULA')
        ->and(FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->pluck('code')->all())->toContain('BNI-ZISWAF', 'CASH-ZISWAF', 'BSI-MRJ-TCE', 'MANDIRI-ZISWAF', 'BCA-SEWA-AULA', 'BANK-QURBAN', 'BANK-SOSIAL-KEMATIAN', 'CASH-OPERASIONAL', 'CASH-QURBAN', 'CASH-SOSIAL')
        ->and(Program::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->count())->toBeGreaterThanOrEqual(12)
        ->and(Category::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->count())->toBeGreaterThanOrEqual(31)
        ->and(phase12Facts($entity->id))->toBe($factsBefore);

    $zakatPolicy = FundPolicyVersion::query()
        ->where('fund_id', $funds['ZAKAT-MAAL'])
        ->where('status', 'effective')
        ->where('policy_document_ref', 'PHASE-12-MRJ-OPERATIONAL-POLICY|ZAKAT-MAAL')
        ->firstOrFail();
    $operationsCategory = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-OPERASIONAL-MASJID')->firstOrFail();
    expect(FundPolicyRule::query()->where('fund_policy_version_id', $zakatPolicy->id)->where('category_id', $operationsCategory->id)->where('decision', 'prohibited')->exists())->toBeTrue()
        ->and(FundPolicyVersion::query()->where('fund_id', $funds['ZAKAT-MAAL'])->where('version_no', 1)->value('status'))->toBe('superseded');

    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])
        ->assertExitCode(0);
    expect(phase12Facts($entity->id))->toBe($factsBefore)
        ->and(FundPolicyVersion::query()->where('fund_id', $funds['ZAKAT-MAAL'])->count())->toBe(2);
});

test('Fund menu groups actual MRJ Funds without changing financial facts or conflating Funds with Financial Accounts', function () {
    Storage::fake('local');
    [$source, $evidence] = phase12MrjFixtureFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $this->artisan('financial-v2:sync-mrj-ziswaf-history', ['--allow-testing' => true])
        ->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])
        ->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $factsBefore = phase12Facts($entity->id);
    $reports = app(FinancialReportService::class);
    $fundRows = collect($reports->report('fund-balance', $entity->id, '2026-01-01', MrjZiswafOpeningPosition::AS_OF_DATE)['data']['rows']);
    $ziswafTotal = DecimalAmount::sum($fundRows
        ->whereIn('code', ['ZAKAT-MAAL', 'INFAQ-TROMOL', 'SODAQOH', 'SANTUNAN-YATIM', 'FIDYAH', 'DHUAFA', 'RAMADHAN'])
        ->pluck('fund_balance'));
    $user = \App\Models\User::factory()->create();

    $index = $this->actingAs($user)->get(route('financial-v2.funds.index', ['entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Kelompok Dana')
        ->assertSee('Operasional Masjid / Kas Masjid')
        ->assertSee('Dana ZISWAF')
        ->assertSee('Dana Sosial / Kematian')
        ->assertSee('Perawatan / Pengembangan')
        ->assertSee('Belum dikelompokkan')
        ->assertSee('Rp125.730.312,00')
        ->assertDontSee('Dana Zakat Maal');

    expect($ziswafTotal)->toBe(MrjZiswafOpeningPosition::TOTAL)
        ->and(substr_count($index->getContent(), 'Dana ZISWAF'))->toBe(1);

    $ziswaf = $this->actingAs($user)->get(route('financial-v2.funds.groups.show', ['group' => 'ziswaf', 'entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Dana Zakat Maal')
        ->assertSee('Dana Infaq &amp; Tromol', false)
        ->assertSee('Dana Sodaqoh')
        ->assertSee('Dana Santunan Anak Yatim')
        ->assertSee('Dana Fidyah')
        ->assertSee('Dana Dhuafa')
        ->assertSee('Dana Ramadhan')
        ->assertSee('BNI ZISWAF')
        ->assertSee('Total ini adalah penjumlahan Saldo Dana dalam kelompok, bukan saldo Rekening/Kas.');

    expect(substr_count($ziswaf->getContent(), 'Dana Sodaqoh'))->toBe(1)
        ->and($this->actingAs($user)->get(route('financial-v2.funds.groups.show', ['group' => 'other', 'entity' => $entity->id]))->getContent())->toContain('Dana Qurban');

    $sodaqoh = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'SODAQOH')->sole();
    $this->actingAs($user)->get(route('financial-v2.funds.show', ['fund' => $sodaqoh, 'entity' => $entity->id]))
        ->assertOk()
        ->assertSee('Riwayat Penggunaan Dana')
        ->assertSee('Beras 20 Pack')
        ->assertSee('Buku Kas Detail!A24:F24');

    expect(phase12Facts($entity->id))->toBe($factsBefore);
});

test('governed Zakat Maal Santunan configuration creates one successor policy and never creates financial facts', function () {
    Storage::fake('local');
    [$source, $evidence] = phase12MrjFixtureFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])
        ->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $zakat = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'ZAKAT-MAAL')->sole();
    $program = Program::query()->where('accounting_entity_id', $entity->id)->where('code', 'BANTUAN-DHUAFA')->sole();
    $payment = \App\Models\FinancialV2\TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->sole();
    $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-SANTUNAN')->sole();
    $factsBefore = phase12Facts($entity->id);

    $this->artisan('financial-v2:configure-mrj-zakat-dhuafa-realization', ['--allow-testing' => true])
        ->assertExitCode(0);
    expect(phase12Facts($entity->id))->toBe($factsBefore)
        ->and(FundPolicyVersion::query()->where('fund_id', $zakat->id)->count())->toBe(2);

    $this->artisan('financial-v2:configure-mrj-zakat-dhuafa-realization', [
        '--apply' => true,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $successor = FundPolicyVersion::query()
        ->where('fund_id', $zakat->id)
        ->where('effective_from', '2026-08-21')
        ->where('policy_document_ref', 'PHASE-REALIZATION-OPERATIONS|ZAKAT-MAAL|BANTUAN-DHUAFA')
        ->sole();

    expect($successor->status)->toBe('effective')
        ->and(FundPolicyVersion::query()->where('fund_id', $zakat->id)->where('version_no', 2)->value('status'))->toBe('superseded')
        ->and(FundPolicyRule::query()
            ->where('fund_policy_version_id', $successor->id)
            ->where('transaction_type_id', $payment->id)
            ->where('category_id', $category->id)
            ->where('program_id', $program->id)
            ->where('decision', 'allowed')
            ->exists())->toBeTrue()
        ->and(phase12Facts($entity->id))->toBe($factsBefore);

    $this->artisan('financial-v2:configure-mrj-zakat-dhuafa-realization', [
        '--apply' => true,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    expect(FundPolicyVersion::query()->where('fund_id', $zakat->id)->count())->toBe(3)
        ->and(phase12Facts($entity->id))->toBe($factsBefore);
});

/** @return array<string, int> */
function phase12Facts(string $entityId): array
{
    return [
        'transactions' => FinancialTransaction::query()->where('accounting_entity_id', $entityId)->count(),
        'journals' => Journal::query()->where('accounting_entity_id', $entityId)->count(),
        'journal_lines' => JournalLine::query()->where('accounting_entity_id', $entityId)->count(),
        'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $entityId)->count(),
    ];
}
