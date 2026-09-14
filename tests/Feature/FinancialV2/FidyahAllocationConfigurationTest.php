<?php

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FundPolicyCompatibilityService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/** @return array{0:string,1:string} */
function fidyahAllocationFixtureFiles(): array
{
    $directory = storage_path('framework/testing/fidyah-allocation');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $source = $directory.'/ZISWAF UPDATE 3.xlsx';
    $evidence = $directory.'/mrj-ziswaf-source-evidence.pdf';
    file_put_contents($source, 'Fidyah allocation test-only source fixture');
    file_put_contents($evidence, "%PDF-1.4\n% Fidyah allocation test-only evidence\n");

    return [$source, $evidence];
}

/** @return array<string, int> */
function fidyahAllocationFacts(string $entityId): array
{
    return [
        'allocations' => BudgetAllocation::query()->where('accounting_entity_id', $entityId)->count(),
        'transactions' => FinancialTransaction::query()->where('accounting_entity_id', $entityId)->count(),
        'journals' => Journal::query()->where('accounting_entity_id', $entityId)->count(),
        'journal_lines' => JournalLine::query()->where('accounting_entity_id', $entityId)->count(),
        'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $entityId)->count(),
        'vouchers' => Voucher::query()->where('accounting_entity_id', $entityId)->count(),
    ];
}

test('approved Fidyah allocation configuration readies only FIDYAH and INFAQ-TROMOL without financial facts', function () {
    Storage::fake('local');
    [$source, $evidence] = fidyahAllocationFixtureFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $funds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', ['FIDYAH', 'INFAQ-TROMOL', 'ZAKAT-MAAL'])->get()->keyBy('code');
    $payment = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->sole();
    $factsBefore = fidyahAllocationFacts($entity->id);

    $this->artisan('financial-v2:configure-mrj-fidyah-allocation', ['--allow-testing' => true])->assertExitCode(0);
    expect(Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-FIDYAH')->exists())->toBeFalse()
        ->and(fidyahAllocationFacts($entity->id))->toBe($factsBefore);

    $this->seed(\Database\Seeders\ConfigureMrjFidyahAllocationSeeder::class);

    $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-FIDYAH')->sole();
    expect($category->name)->toBe('Penyaluran Fidyah')
        ->and($category->transaction_type_id)->toBe($payment->id)
        ->and($category->valid_from->toDateString())->toBe('2026-08-22')
        ->and(fidyahAllocationFacts($entity->id))->toBe($factsBefore);

    foreach (['FIDYAH', 'INFAQ-TROMOL'] as $fundCode) {
        $effective = FundPolicyVersion::query()
            ->where('fund_id', $funds[$fundCode]->id)
            ->where('status', 'effective')
            ->where('effective_from', '<=', '2026-08-22')
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', '2026-08-22'))
            ->get();
        expect($effective)->toHaveCount(1)
            ->and($effective->sole()->effective_from->toDateString())->toBe('2026-08-22')
            ->and(FundPolicyRule::query()
                ->where('fund_policy_version_id', $effective->sole()->id)
                ->where('transaction_type_id', $payment->id)
                ->where('category_id', $category->id)
                ->whereNull('program_id')
                ->where('decision', 'allowed')
                ->exists())->toBeTrue();
    }

    $compatibility = app(FundPolicyCompatibilityService::class);
    foreach (['FIDYAH', 'INFAQ-TROMOL'] as $fundCode) {
        $compatibility->assertAllocationCompatible($entity->id, [$funds[$fundCode]->id], '2026-08-22', null, $category->id, null);
    }
    $compatibility->assertAllocationCompatible($entity->id, [$funds['FIDYAH']->id, $funds['INFAQ-TROMOL']->id], '2026-08-22', null, $category->id, null);
    expect(fn () => $compatibility->assertAllocationCompatible($entity->id, [$funds['ZAKAT-MAAL']->id], '2026-08-22', null, $category->id, null))
        ->toThrow(FinancialDomainException::class, 'tidak dapat digunakan');

    $configurationCounts = [
        Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-FIDYAH')->count(),
        FundPolicyVersion::query()->whereIn('fund_id', [$funds['FIDYAH']->id, $funds['INFAQ-TROMOL']->id])->count(),
    ];
    $this->artisan('financial-v2:configure-mrj-fidyah-allocation', [
        '--apply' => true,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    expect([
        Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-FIDYAH')->count(),
        FundPolicyVersion::query()->whereIn('fund_id', [$funds['FIDYAH']->id, $funds['INFAQ-TROMOL']->id])->count(),
    ])->toBe($configurationCounts)
        ->and(fidyahAllocationFacts($entity->id))->toBe($factsBefore);
});

test('Allocation UI lists PAY categories and excludes the RCV Fidyah category', function () {
    Storage::fake('local');
    [$source, $evidence] = fidyahAllocationFixtureFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', [
        'source' => $source,
        'evidence' => $evidence,
        '--allow-testing' => true,
    ])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:configure-mrj-fidyah-allocation', ['--apply' => true, '--allow-testing' => true])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $pay = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-FIDYAH')->sole();
    $receipt = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'RCV-FIDYAH')->sole();
    $response = $this->actingAs(User::factory()->create())->get(route('financial-v2.allocations.create', ['entity' => $entity->id]));

    $response->assertOk()
        ->assertSee('Penyaluran Fidyah')
        ->assertSee('value="'.$pay->id.'"', false)
        ->assertDontSee('value="'.$receipt->id.'"', false);
});
