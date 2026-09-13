<?php

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\BankMutationService;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionConfigurationResolver;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\ReconciliationService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\TransactionEvidenceUploadService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\ApprovalDecision;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Reconciliation;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Database\Seeders\ConfigureMrjBankMutationsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function bankMutationOpeningFiles(): array
{
    $directory = storage_path('framework/testing/bank-mutation-rehearsal');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $source = $directory.'/ZISWAF UPDATE 3.xlsx';
    $evidence = $directory.'/opening-evidence.pdf';
    file_put_contents($source, 'isolated test source');
    file_put_contents($evidence, "%PDF-1.4\n% isolated opening evidence\n");

    return [$source, $evidence];
}

test('all governed bank mutation categories resolve automatically for their historical date without creating financial facts', function () {
    Storage::fake('local');
    [$source, $openingEvidence] = bankMutationOpeningFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', ['source' => $source, 'evidence' => $openingEvidence, '--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:configure-mrj-bank-mutations', ['--apply' => true])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $bni = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->sole();
    $infaq = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->sole();
    $counts = fn (): array => [
        FinancialTransaction::query()->count(),
        Journal::query()->count(),
        JournalLine::query()->count(),
        LedgerEntry::query()->count(),
        Voucher::query()->count(),
    ];
    $before = $counts();
    $resolver = app(FinancialTransactionConfigurationResolver::class);
    $resolved = collect(array_keys(BankMutationService::CATEGORY_CODES))->map(function (string $categoryCode) use ($resolver, $entity, $bni, $infaq) {
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', $categoryCode)->sole();

        return $resolver->resolve([
            'accounting_entity_id' => $entity->id,
            'transaction_type_id' => $category->transaction_type_id,
            'date' => '2026-06-30',
            'financial_account_id' => $bni->id,
            'fund_id' => $infaq->id,
            'category_id' => $category->id,
        ]);
    });

    $historical = $resolved->first();
    PostingRuleVersion::query()->whereKey($historical->postingRuleVersion->id)->update([
        'status' => 'superseded',
        'approved_at' => now(),
        'effective_to' => '2026-07-31',
    ]);
    $historicalAgain = $resolver->resolve([
        'accounting_entity_id' => $entity->id,
        'transaction_type_id' => $historical->transactionType->id,
        'date' => '2026-06-30',
        'financial_account_id' => $bni->id,
        'fund_id' => $infaq->id,
        'category_id' => $historical->bankMutationPolicy->category_id,
    ]);

    expect($resolved)->toHaveCount(5)
        ->and($resolved->every(fn ($configuration): bool => $configuration->bankMutationPolicy !== null))->toBeTrue()
        ->and($historicalAgain->postingRuleVersion->id)->toBe($historical->postingRuleVersion->id)
        ->and($historicalAgain->postingRuleVersion->status)->toBe('superseded')
        ->and($counts())->toBe($before);
});

test('four June BNI mutations post canonically and reconcile to zero in the isolated database', function () {
    Storage::fake('local');
    [$source, $openingEvidence] = bankMutationOpeningFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', ['source' => $source, 'evidence' => $openingEvidence, '--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:configure-mrj-bank-mutations', ['--apply' => true])->assertExitCode(0);
    $this->artisan('financial-v2:configure-mrj-bank-mutations', ['--apply' => true])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $bni = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->sole();
    $infaq = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->sole();
    $balances = app(BalanceInquiryService::class);
    expect(BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->count())->toBe(5)
        ->and(Category::query()->where('accounting_entity_id', $entity->id)->whereIn('code', array_keys(BankMutationService::CATEGORY_CODES))->count())->toBe(5);
    expect($balances->financialAccountBalance($entity->id, $bni->id, '2026-06-27')['balance'])->toBe('123077312.00');

    $definitions = [
        ['BANK_INTEREST', '6925.00', 'JASA GIRO/BUNGA', 'BNI-20260630-JASA-GIRO'],
        ['BANK_WHT_PPH', '1385.00', 'PPH', 'BNI-20260630-PPH'],
        ['BANK_ACCOUNT_FEE', '11000.00', 'BIAYA ADM REK', 'BNI-20260630-ADM-REK'],
        ['BANK_CARD_FEE', '7500.00', 'BIAYA ADM KARTU', 'BNI-20260630-ADM-KARTU'],
    ];
    $service = app(BankMutationService::class);
    $lifecycle = app(FinancialTransactionLifecycleService::class);
    $uploader = app(TransactionEvidenceUploadService::class);
    $postedIds = [];
    foreach ($definitions as [$categoryCode, $amount, $description, $reference]) {
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', $categoryCode)->sole();
        $transaction = $service->create([
            'accounting_entity_id' => $entity->id,
            'financial_account_id' => $bni->id,
            'fund_id' => $infaq->id,
            'category_id' => $category->id,
            'date' => '2026-06-30',
            'amount' => $amount,
            'description' => $description,
            'source_reference' => $reference,
        ]);
        $statement = UploadedFile::fake()->createWithContent('bni-june-statement.pdf', "%PDF-1.4\n% BNI June fixture\n");
        $uploader->attach($entity->id, $transaction->id, $statement, 'statement');
        $lifecycle->submit($transaction->id);
        $lifecycle->verify($transaction->id);
        $lifecycle->recordApprovalDecision($transaction->id, 1, 'approved', null, 'Isolated rehearsal approval');
        $lifecycle->approve($transaction->id);
        $fingerprint = hash('sha256', implode('|', [$transaction->id, $reference, $amount, '2026-06-30']));
        $first = $lifecycle->post($transaction->id, 'bank-post:'.$reference, $fingerprint);
        $replay = $lifecycle->post($transaction->id, 'bank-post:'.$reference, $fingerprint);
        expect($replay->journalId)->toBe($first->journalId);
        $postedIds[] = $transaction->id;
    }

    $period = AccountingPeriod::query()->where('accounting_entity_id', $entity->id)->where('start_date', '<=', '2026-06-30')->where('end_date', '>=', '2026-06-30')->sole();
    $reconciliation = app(ReconciliationService::class)->createDraft([
        'accounting_entity_id' => $entity->id,
        'financial_account_id' => $bni->id,
        'accounting_period_id' => $period->id,
        'as_of_date' => '2026-06-30',
        'statement_balance' => '123064352.00',
        'notes' => 'Rehearsal matching only',
    ]);
    $reconContents = "%PDF-1.4\n% BNI June reconciliation fixture\n";
    app(EvidenceService::class)->attachToReconciliation($entity->id, $reconciliation->id, 'bni-june-statement.pdf', 'application/pdf', strlen($reconContents), hash('sha256', $reconContents), 'testing/bni-june-statement.pdf', 'statement');
    app(ReconciliationService::class)->startReview($reconciliation->id);
    app(ReconciliationService::class)->review($reconciliation->id);
    app(ReconciliationService::class)->complete($reconciliation->id);

    $fundRow = collect(app(FinancialReportService::class)->report('fund-balance', $entity->id, '2026-01-01', '2026-06-30')['data']['rows'])->sole('fund_id', $infaq->id);
    $postedJournals = Journal::query()->whereIn('transaction_id', $postedIds)->get();
    expect($postedIds)->toHaveCount(4)
        ->and(FinancialTransaction::query()->whereIn('id', $postedIds)->where('status', 'posted')->count())->toBe(4)
        ->and(FinancialTransaction::query()->where('accounting_entity_id', $entity->id)->where('source_reference', 'like', '%BIFAST%')->count())->toBe(0)
        ->and(FinancialTransaction::query()->where('accounting_entity_id', $entity->id)->where('gross_amount', '609051.00')->count())->toBe(0)
        ->and($balances->financialAccountBalance($entity->id, $bni->id, '2026-06-30')['balance'])->toBe('123064352.00')
        ->and($fundRow['fund_balance'])->toBe('16653989.00')
        ->and($postedJournals)->toHaveCount(4)
        ->and($postedJournals->every(fn (Journal $journal) => $journal->total_debit === $journal->total_credit))->toBeTrue()
        ->and(JournalLine::query()->whereIn('journal_id', $postedJournals->pluck('id'))->count())->toBe(8)
        ->and(LedgerEntry::query()->whereIn('journal_line_id', JournalLine::query()->whereIn('journal_id', $postedJournals->pluck('id'))->pluck('id'))->count())->toBe(8)
        ->and(Voucher::query()->whereIn('transaction_id', $postedIds)->count())->toBe(4)
        ->and(ApprovalDecision::query()->whereIn('transaction_id', $postedIds)->where('decision', 'approved')->count())->toBe(4)
        ->and(Reconciliation::findOrFail($reconciliation->id)->status)->toBe('completed')
        ->and(Reconciliation::findOrFail($reconciliation->id)->difference)->toBe('0.00')
        ->and(DB::table('financial_v2_journal_lines as line')->leftJoin('financial_v2_journals as journal', 'journal.id', '=', 'line.journal_id')->whereNull('journal.id')->count())->toBe(0)
        ->and(DB::table('financial_v2_ledger_entries as ledger')->leftJoin('financial_v2_journal_lines as line', 'line.id', '=', 'ledger.journal_line_id')->whereNull('line.id')->count())->toBe(0);
});

test('a governed June bank charge remains safe when later posted activity already exists', function () {
    Storage::fake('local');
    [$source, $openingEvidence] = bankMutationOpeningFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', ['source' => $source, 'evidence' => $openingEvidence, '--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:configure-mrj-bank-mutations', ['--apply' => true])->assertExitCode(0);

    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $bni = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->sole();
    $infaq = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->sole();
    $service = app(BankMutationService::class);
    $lifecycle = app(FinancialTransactionLifecycleService::class);
    $uploader = app(TransactionEvidenceUploadService::class);

    $post = function (string $categoryCode, string $date, string $amount, string $reference) use ($entity, $bni, $infaq, $service, $lifecycle, $uploader): FinancialTransaction {
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', $categoryCode)->sole();
        $transaction = $service->create([
            'accounting_entity_id' => $entity->id,
            'financial_account_id' => $bni->id,
            'fund_id' => $infaq->id,
            'category_id' => $category->id,
            'date' => $date,
            'amount' => $amount,
            'description' => 'Historical running-balance guard rehearsal',
            'source_reference' => $reference,
        ]);
        $statement = UploadedFile::fake()->createWithContent('bni-guard-statement.pdf', "%PDF-1.4\n% guard fixture\n");
        $uploader->attach($entity->id, $transaction->id, $statement, 'statement');
        $lifecycle->submit($transaction->id);
        $lifecycle->verify($transaction->id);
        $lifecycle->recordApprovalDecision($transaction->id, 1, 'approved', null, 'Isolated guard rehearsal approval');
        $lifecycle->approve($transaction->id);
        $lifecycle->post($transaction->id, 'bank-guard:'.$reference, hash('sha256', implode('|', [$transaction->id, $reference, $amount, $date])));

        return $transaction->fresh();
    };

    $later = $post('BANK_INTEREST', '2026-10-01', '100.00', 'BNI-20261001-GUARD-FUTURE');
    $historical = $post('BANK_TRANSFER_FEE', '2026-06-30', '50.00', 'BNI-20260630-GUARD-HISTORICAL');
    $balances = app(BalanceInquiryService::class);

    expect($later->status)->toBe('posted')
        ->and($historical->status)->toBe('posted')
        ->and($balances->financialAccountBalance($entity->id, $bni->id, '2026-06-30')['balance'])->toBe('123077262.00')
        ->and($balances->financialAccountBalance($entity->id, $bni->id, '2026-10-01')['balance'])->toBe('123077362.00');
});

test('Mutasi Bank UI exposes guarded lifecycle actions without accounting internals', function () {
    Storage::fake('local');
    [$source, $openingEvidence] = bankMutationOpeningFiles();
    $this->artisan('financial-v2:onboard-mrj-ziswaf', ['source' => $source, 'evidence' => $openingEvidence, '--allow-testing' => true])->assertExitCode(0);
    $this->artisan('financial-v2:provision-mrj-operational-master', ['--allow-testing' => true])->assertExitCode(0);
    $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->sole();
    $bni = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', 'BNI-ZISWAF')->sole();
    $infaq = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'INFAQ-TROMOL')->sole();
    $zakat = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'ZAKAT-MAAL')->sole();
    $user = User::factory()->create();
    $factCounts = fn (): array => [
        FinancialTransaction::query()->count(),
        Journal::query()->count(),
        JournalLine::query()->count(),
        LedgerEntry::query()->count(),
        Voucher::query()->count(),
    ];
    $factsBeforeConfiguration = $factCounts();
    $configurationRoute = app('router')->getRoutes()->getByName('financial-v2.bank-mutations.configure');
    expect($configurationRoute->methods())->toBe(['POST'])
        ->and($configurationRoute->gatherMiddleware())->toContain('web', 'auth')
        ->and(BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->count())->toBe(0);
    $this->post(route('financial-v2.bank-mutations.configure'))->assertRedirect(route('login'));
    $this->actingAs($user)->get(route('financial-v2.bank-mutations.configure'))->assertStatus(405);
    $this->actingAs($user)->get(route('financial-v2.bank-mutations.index', ['entity' => $entity->id]))
        ->assertOk()->assertSee('Status Konfigurasi')->assertSee('Sebagian belum tersedia')->assertSee('+ Tambah Mutasi')->assertDontSee('Aktifkan Konfigurasi');
    expect(BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->count())->toBe(0);

    $this->seed(ConfigureMrjBankMutationsSeeder::class);
    expect(BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->count())->toBe(5)
        ->and($factCounts())->toBe($factsBeforeConfiguration);
    BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->delete();

    $this->actingAs($user)->post(route('financial-v2.bank-mutations.configure'), [
        'entity' => (string) Str::uuid(),
        'actor' => 999999,
    ])->assertRedirect(route('financial-v2.bank-mutations.index', ['entity' => $entity->id]))
        ->assertSessionHas('success', 'Konfigurasi Mutasi Bank berhasil diaktifkan.');
    expect(BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->where('status', 'active')->count())->toBe(5)
        ->and(AuditEvent::query()->where('accounting_entity_id', $entity->id)->where('event_type', 'bank_mutation_configuration_activated')->where('actor_user_id', $user->id)->exists())->toBeTrue()
        ->and($factCounts())->toBe($factsBeforeConfiguration);
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.configure'))->assertRedirect()
        ->assertSessionHas('success', 'Konfigurasi Mutasi Bank sudah aktif.');
    expect(BankMutationPolicy::query()->where('accounting_entity_id', $entity->id)->count())->toBe(5)
        ->and($factCounts())->toBe($factsBeforeConfiguration);

    $interest = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'BANK_INTEREST')->sole();
    $withholding = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'BANK_WHT_PPH')->sole();
    $accountFee = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'BANK_ACCOUNT_FEE')->sole();
    $cardFee = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'BANK_CARD_FEE')->sole();

    $this->actingAs($user)->get(route('financial-v2.bank-mutations.index', ['entity' => $entity->id]))
        ->assertOk()->assertSee('Mutasi Bank')->assertSee('Tahun')->assertSee('Bulan')->assertSee('Rekening')->assertSee('Dana')->assertSee('Jenis')->assertSee('Status')
        ->assertSee('Status Konfigurasi')->assertSee('Siap digunakan')->assertDontSee('Aktifkan Konfigurasi');
    $this->actingAs($user)->get(route('financial-v2.bank-mutations.create', ['entity' => $entity->id]))
        ->assertOk()->assertSee('Jasa Giro/Bunga')->assertSee('PPH')->assertSee('Biaya Transfer Bank')->assertSee('Dana Zakat Maal')->assertSee('Source Reference')->assertSee('Pratinjau Batch')->assertSee('Status Konfigurasi')
        ->assertDontSee('Journal')->assertDontSee('Ledger')->assertDontSee('Debit')->assertDontSee('Kredit');

    $previewRows = [
        ['category_id' => $interest->id, 'fund_id' => $infaq->id, 'amount' => '6925.00'],
        ['category_id' => $withholding->id, 'fund_id' => $infaq->id, 'amount' => '1385.00'],
        ['category_id' => $accountFee->id, 'fund_id' => $infaq->id, 'amount' => '11000.00'],
        ['category_id' => $cardFee->id, 'fund_id' => $infaq->id, 'amount' => '7500.00'],
    ];
    $this->actingAs($user)->postJson(route('financial-v2.bank-mutations.preview'), [
        'entity' => $entity->id, 'financial_account_id' => $bni->id, 'date' => '2026-06-30', 'mutations' => $previewRows,
    ])->assertOk()
        ->assertJsonPath('opening', '123077312.00')
        ->assertJsonPath('total_credit', '6925.00')
        ->assertJsonPath('total_debit', '19885.00')
        ->assertJsonPath('net_movement', '-12960.00')
        ->assertJsonPath('closing', '123064352.00')
        ->assertJsonPath('rows.0.balance', '123084237.00')
        ->assertJsonPath('rows.1.balance', '123082852.00')
        ->assertJsonPath('rows.2.balance', '123071852.00')
        ->assertJsonPath('rows.3.balance', '123064352.00');
    $this->actingAs($user)->postJson(route('financial-v2.bank-mutations.preview'), [
        'entity' => $entity->id, 'financial_account_id' => $bni->id, 'date' => '2026-06-30',
        'mutations' => [['category_id' => $interest->id, 'fund_id' => $zakat->id, 'amount' => '6925.00']],
    ])->assertStatus(422);

    $batchId = (string) Str::uuid();
    $payload = [
        'entity' => $entity->id,
        'bank_mutation_batch_id' => $batchId,
        'financial_account_id' => $bni->id,
        'date' => '2026-06-30',
        'mutations' => [
            ['category_id' => $interest->id, 'fund_id' => $infaq->id, 'amount' => '6925.00', 'description' => 'JASA GIRO/BUNGA UI QA', 'source_reference' => 'BNI-20260630-JASA-GIRO-UI-QA'],
            ['category_id' => $withholding->id, 'fund_id' => $infaq->id, 'amount' => '1385.00', 'description' => 'PPH UI QA', 'source_reference' => 'BNI-20260630-PPH-UI-QA'],
            ['category_id' => $accountFee->id, 'fund_id' => $infaq->id, 'amount' => '11000.00', 'description' => 'BIAYA ADM REK UI QA', 'source_reference' => 'BNI-20260630-ADM-REK-UI-QA'],
            ['category_id' => $cardFee->id, 'fund_id' => $infaq->id, 'amount' => '7500.00', 'description' => 'BIAYA ADM KARTU UI QA', 'source_reference' => 'BNI-20260630-ADM-KARTU-UI-QA'],
        ],
        'proof' => UploadedFile::fake()->createWithContent('statement-ui.pdf', "%PDF-1.4\n% UI fixture\n"),
    ];
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.store'), $payload)->assertRedirect();
    $batchTransactions = FinancialTransaction::query()->where('correlation_id', $batchId)->get();
    $links = AttachmentLink::query()->whereIn('target_id', $batchTransactions->pluck('id'))->where('evidence_type', 'statement')->get();
    expect($batchTransactions)->toHaveCount(4)
        ->and($links)->toHaveCount(4)
        ->and($links->pluck('attachment_id')->unique())->toHaveCount(1);
    $this->actingAs($user)->get(route('financial-v2.transactions.drafts', [
        'entity' => $entity->id,
        'year' => 2026,
        'type' => 'bank_mutation',
    ]))->assertOk()
        ->assertSee('JASA GIRO/BUNGA UI QA')
        ->assertSee('PPH UI QA')
        ->assertSee('Mutasi Bank')
        ->assertSee('Ubah draft')
        ->assertSee(route('financial-v2.bank-mutations.edit', ['transaction' => $batchTransactions->first(), 'entity' => $entity->id]));

    $payload['proof'] = UploadedFile::fake()->createWithContent('statement-ui.pdf', "%PDF-1.4\n% UI fixture\n");
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.store'), $payload)->assertRedirect();
    expect(FinancialTransaction::query()->where('correlation_id', $batchId)->count())->toBe(4)
        ->and(AttachmentLink::query()->whereIn('target_id', $batchTransactions->pluck('id'))->where('evidence_type', 'statement')->count())->toBe(4);

    $atomicBatchId = (string) Str::uuid();
    $common = ['accounting_entity_id' => $entity->id, 'financial_account_id' => $bni->id, 'date' => '2026-06-30'];
    $invalidRows = [
        ['category_id' => $interest->id, 'fund_id' => $infaq->id, 'amount' => '1.00', 'description' => 'VALID ROW', 'source_reference' => 'BNI-20260630-ATOMIC-VALID'],
        ['category_id' => $interest->id, 'fund_id' => $zakat->id, 'amount' => '1.00', 'description' => 'INVALID FUND ROW', 'source_reference' => 'BNI-20260630-ATOMIC-INVALID'],
    ];
    expect(fn () => app(BankMutationService::class)->createBatch($common, $invalidRows, $atomicBatchId, $user->id))
        ->toThrow(FinancialPostingException::class, 'Konfigurasi pencatatan belum tersedia');
    expect(FinancialTransaction::query()->where('correlation_id', $atomicBatchId)->count())->toBe(0);

    $transaction = FinancialTransaction::query()->where('source_reference', 'BNI-20260630-JASA-GIRO-UI-QA')->sole();
    expect($transaction->status)->toBe('draft');
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.submit', ['transaction' => $transaction, 'entity' => $entity->id]))->assertRedirect();
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.verify', ['transaction' => $transaction, 'entity' => $entity->id]))->assertRedirect();
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.approve', ['transaction' => $transaction, 'entity' => $entity->id]))->assertRedirect();
    $this->actingAs($user)->post(route('financial-v2.bank-mutations.post', ['transaction' => $transaction, 'entity' => $entity->id]))->assertRedirect();
    expect($transaction->fresh()->status)->toBe('posted');
    $this->actingAs($user)->get(route('financial-v2.bank-mutations.edit', ['transaction' => $transaction, 'entity' => $entity->id]))->assertStatus(409);
    $this->actingAs($user)->delete(route('financial-v2.bank-mutations.destroy', ['transaction' => $transaction, 'entity' => $entity->id]))->assertStatus(409);

    $draft = app(BankMutationService::class)->create([
        'accounting_entity_id' => $entity->id, 'financial_account_id' => $bni->id, 'fund_id' => $infaq->id,
        'category_id' => $interest->id, 'date' => '2026-06-30', 'amount' => '50.00',
        'description' => 'DRAFT TO REMOVE', 'source_reference' => 'BNI-20260630-DRAFT-REMOVE-QA',
    ], $user->id);
    $this->actingAs($user)->delete(route('financial-v2.bank-mutations.destroy', ['transaction' => $draft, 'entity' => $entity->id]))->assertRedirect();
    expect($draft->fresh()->status)->toBe('cancelled');

    $editableBatchId = (string) Str::uuid();
    $editable = app(BankMutationService::class)->createBatch($common, [
        ['category_id' => $interest->id, 'fund_id' => $infaq->id, 'amount' => '10.00', 'description' => 'EDITABLE ONE', 'source_reference' => 'BNI-20260630-EDITABLE-ONE'],
        ['category_id' => $withholding->id, 'fund_id' => $infaq->id, 'amount' => '2.00', 'description' => 'EDITABLE TWO', 'source_reference' => 'BNI-20260630-EDITABLE-TWO'],
    ], $editableBatchId, $user->id)['transactions'];
    app(TransactionEvidenceUploadService::class)->attachToMany(
        $entity->id,
        $editable->pluck('id')->all(),
        UploadedFile::fake()->createWithContent('editable-statement.pdf', "%PDF-1.4\n% editable fixture\n"),
        'statement',
        $user->id,
    );
    $editableAttachmentId = AttachmentLink::query()->where('target_id', $editable->first()->id)->where('evidence_type', 'statement')->value('attachment_id');
    $this->actingAs($user)->get(route('financial-v2.bank-mutations.batches.edit', ['batch' => $editableBatchId, 'entity' => $entity->id]))
        ->assertOk()->assertSee('EDITABLE ONE')->assertSee('EDITABLE TWO');
    $this->actingAs($user)->put(route('financial-v2.bank-mutations.batches.update', ['batch' => $editableBatchId]), [
        'entity' => $entity->id,
        'bank_mutation_batch_id' => $editableBatchId,
        'financial_account_id' => $bni->id,
        'date' => '2026-06-30',
        'mutations' => [
            [
                'transaction_id' => $editable->first()->id,
                'category_id' => $interest->id,
                'fund_id' => $infaq->id,
                'amount' => '12.00',
                'description' => 'EDITABLE ONE UPDATED',
                'source_reference' => 'BNI-20260630-EDITABLE-ONE',
            ],
            [
                'category_id' => $accountFee->id,
                'fund_id' => $infaq->id,
                'amount' => '3.00',
                'description' => 'EDITABLE THREE NEW',
                'source_reference' => 'BNI-20260630-EDITABLE-THREE',
            ],
        ],
    ])->assertRedirect();
    $newEditable = FinancialTransaction::query()->where('source_reference', 'BNI-20260630-EDITABLE-THREE')->sole();
    expect($editable->first()->fresh()->gross_amount)->toBe('12.00')
        ->and($editable->last()->fresh()->status)->toBe('cancelled')
        ->and(AttachmentLink::query()->where('target_id', $newEditable->id)->where('attachment_id', $editableAttachmentId)->where('status', 'active')->exists())->toBeTrue();
    $this->actingAs($user)->delete(route('financial-v2.bank-mutations.batches.destroy', ['batch' => $editableBatchId, 'entity' => $entity->id]))->assertRedirect();
    expect($editable->first()->fresh()->status)->toBe('cancelled')
        ->and($newEditable->fresh()->status)->toBe('cancelled');
});
