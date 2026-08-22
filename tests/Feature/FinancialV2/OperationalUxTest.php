<?php

use App\Domain\FinancialV2\BudgetAllocationService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountGroup;
use App\Models\FinancialV2\AccountingCalendar;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

function uxOperationalContext(): array
{
    $suffix = Str::upper(Str::random(6));
    $today = now()->toDateString();
    $entity = AccountingEntity::create(['code' => "UX-{$suffix}", 'name' => 'UX Financial Entity', 'legal_name' => 'UX Financial Entity', 'status' => 'active']);
    $calendar = AccountingCalendar::create(['accounting_entity_id' => $entity->id, 'code' => "CAL-{$suffix}", 'name' => 'UX Calendar', 'fiscal_year_label' => "2099-{$suffix}", 'start_date' => '2099-01-01', 'end_date' => '2099-12-31', 'status' => 'active']);
    $period = AccountingPeriod::create(['accounting_entity_id' => $entity->id, 'accounting_calendar_id' => $calendar->id, 'period_no' => 1, 'period_name' => 'UX Period', 'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'status' => 'open']);

    $assetGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'AST', 'name' => 'Assets', 'group_class' => 'asset', 'status' => 'active']);
    $revenueGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'REV', 'name' => 'Revenue', 'group_class' => 'revenue', 'status' => 'active']);
    $expenseGroup = AccountGroup::create(['accounting_entity_id' => $entity->id, 'code' => 'EXP', 'name' => 'Expense', 'group_class' => 'expense', 'status' => 'active']);
    $cashSource = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'CASH-A', 'name' => 'Kas Operasional', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
    $cashDestination = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $assetGroup->id, 'code' => 'CASH-B', 'name' => 'Bank Operasional', 'account_class' => 'asset', 'normal_balance' => 'debit', 'is_posting_account' => true, 'is_liquidity_account' => true, 'status' => 'active']);
    $revenue = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $revenueGroup->id, 'code' => 'INF', 'name' => 'Pendapatan Infak', 'account_class' => 'revenue', 'normal_balance' => 'credit', 'is_posting_account' => true, 'status' => 'active']);
    $expense = Account::create(['accounting_entity_id' => $entity->id, 'account_group_id' => $expenseGroup->id, 'code' => 'UTL', 'name' => 'Beban Utilitas', 'account_class' => 'expense', 'normal_balance' => 'debit', 'is_posting_account' => true, 'status' => 'active']);
    $sourceFinancialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cashSource->id, 'code' => 'KAS', 'name' => 'Kas Operasional', 'account_type' => 'bank', 'opening_date' => now()->subYear(), 'status' => 'active']);
    $destinationFinancialAccount = FinancialAccount::create(['accounting_entity_id' => $entity->id, 'account_id' => $cashDestination->id, 'code' => 'BNK', 'name' => 'Bank Operasional', 'account_type' => 'bank', 'opening_date' => now()->subYear(), 'status' => 'active']);
    BankAccountDetail::create(['financial_account_id' => $sourceFinancialAccount->id, 'bank_name' => 'Bank Test', 'account_number_masked' => '****0001']);
    BankAccountDetail::create(['financial_account_id' => $destinationFinancialAccount->id, 'bank_name' => 'Bank Test', 'account_number_masked' => '****0002']);

    $fundType = FundType::create(['accounting_entity_id' => $entity->id, 'code' => 'UNR', 'name' => 'Tidak Terikat', 'classification' => 'unrestricted', 'status' => 'active']);
    $restriction = FundRestriction::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'code' => 'GEN', 'name' => 'Umum', 'severity' => 'low', 'policy_basis' => 'Test policy', 'status' => 'active']);
    $fund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'OPS', 'name' => 'Dana Operasional', 'purpose_statement' => 'Operasional masjid', 'status' => 'active']);
    $program = Program::create(['accounting_entity_id' => $entity->id, 'code' => 'OPS-PRG', 'name' => 'Program Operasional', 'start_date' => now()->subDay(), 'end_date' => now()->addDay(), 'status' => 'active']);

    $receiptType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'RCV', 'name' => 'Penerimaan', 'voucher_prefix' => 'RCV', 'status' => 'active']);
    $paymentType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'PAY', 'name' => 'Pengeluaran', 'voucher_prefix' => 'PAY', 'status' => 'active']);
    $transferType = TransactionType::create(['accounting_entity_id' => $entity->id, 'code' => 'TRF', 'name' => 'Transfer Rekening', 'voucher_prefix' => 'TRF', 'status' => 'active']);
    $receiptCategory = Category::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $receiptType->id, 'code' => 'INFAQ', 'name' => 'Infak Jumat', 'status' => 'active']);
    $paymentCategory = Category::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $paymentType->id, 'code' => 'LST', 'name' => 'Listrik', 'status' => 'active']);
    $supplier = Counterparty::create(['accounting_entity_id' => $entity->id, 'code' => 'PLN', 'party_type' => 'supplier', 'display_name' => 'Penyedia Listrik', 'status' => 'active']);

    uxPostingRule($entity, $receiptType, 'receipt', [
        ['account_id' => $cashSource->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split', 'program_source' => 'split'],
        ['account_id' => $revenue->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'program_source' => 'split', 'category_source' => 'transaction'],
    ]);
    uxPostingRule($entity, $paymentType, 'payment', [
        ['account_id' => $expense->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'fund_source' => 'split', 'program_source' => 'split', 'category_source' => 'transaction', 'counterparty_source' => 'transaction'],
        ['account_id' => $cashSource->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transaction_primary', 'fund_source' => 'split', 'program_source' => 'split'],
    ]);
    uxPostingRule($entity, $transferType, 'treasury-transfer', [
        ['account_id' => $cashDestination->id, 'entry_side' => 'debit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_destination', 'fund_source' => 'split'],
        ['account_id' => $cashSource->id, 'entry_side' => 'credit', 'amount_source' => 'split_amount', 'financial_account_source' => 'transfer_source', 'fund_source' => 'split'],
    ]);
    foreach ([$receiptType, $paymentType, $transferType] as $type) {
        DocumentSequence::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => $type->code, 'name' => $type->name, 'prefix' => $type->voucher_prefix, 'scope_key' => 'ux-'.$type->code, 'status' => 'active']);
    }

    return compact('entity', 'period', 'cashSource', 'cashDestination', 'revenue', 'expense', 'sourceFinancialAccount', 'destinationFinancialAccount', 'fund', 'program', 'receiptType', 'paymentType', 'transferType', 'receiptCategory', 'paymentCategory', 'supplier', 'today');
}

/** @param array<int, array<string, mixed>> $lines */
function uxPostingRule(AccountingEntity $entity, TransactionType $type, string $family, array $lines): void
{
    $rule = PostingRule::create(['accounting_entity_id' => $entity->id, 'transaction_type_id' => $type->id, 'code' => $type->code.'-UX', 'name' => $type->name.' UX', 'rule_family' => $family, 'status' => 'active']);
    $version = PostingRuleVersion::create(['accounting_entity_id' => $entity->id, 'posting_rule_id' => $rule->id, 'version_no' => 1, 'effective_from' => now()->subDay(), 'input_contract_ref' => 'ux-test', 'journal_template_ref' => 'ux-test', 'business_rule_refs' => 'BR-UX', 'status' => 'effective']);
    foreach ($lines as $index => $line) {
        PostingRuleLine::create(['accounting_entity_id' => $entity->id, 'posting_rule_version_id' => $version->id, 'line_no' => $index + 1] + $line);
    }
}

function uxReceiptPayload(array $context, string $submissionKey): array
{
    return [
        'entity' => $context['entity']->id,
        'submission_key' => $submissionKey,
        'date' => $context['today'],
        'amount' => '100.00',
        'source' => 'Infak Jumat',
        'financial_account_id' => $context['sourceFinancialAccount']->id,
        'fund_id' => $context['fund']->id,
        'category_id' => $context['receiptCategory']->id,
        'description' => 'Penerimaan uji UX',
    ];
}

test('operational receipt UX is idempotent, retains evidence, and posts through the canonical engine once', function () {
    Storage::fake('local');
    $context = uxOperationalContext();
    $user = User::factory()->create();
    $payload = uxReceiptPayload($context, (string) Str::uuid());
    $payload['attachment'] = UploadedFile::fake()->image('bukti.jpg');

    $response = $this->actingAs($user)->post(route('financial-v2.transactions.store', 'receipt'), $payload, ['Accept' => 'application/json']);
    $response->assertOk()->assertJsonPath('ok', true);
    $transactionId = $response->json('transaction_id');
    expect(FinancialTransaction::whereKey($transactionId)->value('status'))->toBe('draft')
        ->and(AttachmentLink::where('target_id', $transactionId)->count())->toBe(1)
        ->and(Journal::where('transaction_id', $transactionId)->count())->toBe(0);

    $updatePayload = uxReceiptPayload($context, $payload['submission_key']);
    $updatePayload['amount'] = '125.00';
    $this->actingAs($user)->putJson(route('financial-v2.transactions.update', $transactionId), $updatePayload)
        ->assertOk()->assertJsonPath('ok', true);

    $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), uxReceiptPayload($context, $payload['submission_key']))
        ->assertOk()->assertJsonPath('duplicate', true)->assertJsonPath('transaction_id', $transactionId);
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $transactionId))
        ->assertOk()->assertJsonPath('ok', true);
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $transactionId))
        ->assertOk()->assertJsonPath('already_posted', true);

    expect(Journal::where('transaction_id', $transactionId)->where('journal_status', 'posted')->count())->toBe(1)
        ->and(Journal::where('transaction_id', $transactionId)->value('total_debit'))->toBe('125.00')
        ->and(JournalLine::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2)
        ->and(FinancialTransaction::findOrFail($transactionId)->status)->toBe('posted');
});

test('financial control UX is isolated and soft close is performed only through the closing control endpoint', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('financial-v2.controls.index', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Tutup Periode & Rekonsiliasi Rekening', false)
        ->assertSee('saldo menurut sistem')
        ->assertSee('card min-w-0 border border-base-300', false)
        ->assertSee('Kelola saldo awal dan rehearsal migrasi');
    $this->actingAs($user)->postJson(route('financial-v2.controls.close', $context['period']), [
        'entity' => $context['entity']->id,
        'run_type' => 'soft_close',
        'reference' => 'UX-CONTROL-SOFT',
    ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('closed', true);

    expect($context['period']->fresh()->status)->toBe('soft_closed')
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('payment UX translates restricted fund rejection without creating a financial fact', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();
    $restrictedType = FundType::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'RES', 'name' => 'Terikat', 'classification' => 'restricted', 'status' => 'active']);
    $restriction = FundRestriction::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'code' => 'RES', 'name' => 'Terikat', 'severity' => 'high', 'policy_basis' => 'Policy', 'status' => 'active']);
    $restrictedFund = Fund::create(['accounting_entity_id' => $context['entity']->id, 'fund_type_id' => $restrictedType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'ZKT', 'name' => 'Dana Zakat', 'purpose_statement' => 'Zakat', 'status' => 'active']);
    $payload = [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '10.00',
        'counterparty_id' => $context['supplier']->id, 'financial_account_id' => $context['sourceFinancialAccount']->id,
        'fund_id' => $restrictedFund->id, 'category_id' => $context['paymentCategory']->id, 'description' => 'Bayar listrik',
    ];

    $this->actingAs($user)->postJson(route('financial-v2.preview'), [
        'entity' => $context['entity']->id,
        'operation' => 'payment',
        'date' => $context['today'],
        'financial_account_id' => $context['sourceFinancialAccount']->id,
        'fund_id' => $restrictedFund->id,
        'category_id' => $context['paymentCategory']->id,
    ])->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('message', 'Penggunaan dana belum dapat dilakukan karena aturan penggunaan dana belum dikonfigurasi.');

    $created = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'payment'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $created->json('transaction_id')))
        ->assertStatus(422)->assertJsonPath('message', 'Penggunaan dana belum dapat dilakukan karena aturan penggunaan dana belum dikonfigurasi.');
    expect(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);
});

test('fund usage preview is sent as a CSRF-protected POST request', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee("method: 'POST'", false)
        ->assertSee("credentials: 'same-origin'", false)
        ->assertDontSee("previewUrl + '?'", false);
});

test('restricted fund preview fails closed when its operational transaction type is not configured', function () {
    $entity = AccountingEntity::create(['code' => 'PREVIEW-NO-TYPE-'.Str::upper(Str::random(6)), 'name' => 'Preview No Type', 'legal_name' => 'Preview No Type', 'status' => 'active']);
    $fundType = FundType::create(['accounting_entity_id' => $entity->id, 'code' => 'PREVIEW-RES', 'name' => 'Terikat', 'classification' => 'restricted', 'status' => 'active']);
    $restriction = FundRestriction::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'code' => 'PREVIEW-RES', 'name' => 'Terikat', 'severity' => 'high', 'policy_basis' => 'Policy', 'status' => 'active']);
    $fund = Fund::create(['accounting_entity_id' => $entity->id, 'fund_type_id' => $fundType->id, 'fund_restriction_id' => $restriction->id, 'code' => 'PREVIEW-ZKT', 'name' => 'Dana Zakat Preview', 'purpose_statement' => 'Zakat', 'status' => 'active']);

    $this->actingAs(User::factory()->create())->postJson(route('financial-v2.preview'), [
        'entity' => $entity->id,
        'operation' => 'receipt',
        'date' => now()->toDateString(),
        'fund_id' => $fund->id,
    ])->assertOk()
        ->assertJsonPath('ok', true)
        ->assertJsonPath('allowed', false)
        ->assertJsonPath('message', 'Penggunaan dana belum dapat dilakukan karena aturan penggunaan dana belum dikonfigurasi.');
});

test('treasury transfer UX preserves fund and never creates income or expense impact', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();
    $receipt = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), uxReceiptPayload($context, (string) Str::uuid()))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $receipt->json('transaction_id')))->assertOk();
    $transfer = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'transfer'), [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '40.00',
        'source_financial_account_id' => $context['sourceFinancialAccount']->id, 'destination_financial_account_id' => $context['destinationFinancialAccount']->id,
        'fund_id' => $context['fund']->id, 'description' => 'Pindah lokasi kas',
    ])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $transfer->json('transaction_id')))->assertOk();
    $journal = Journal::where('transaction_id', $transfer->json('transaction_id'))->sole();

    expect(JournalLine::where('journal_id', $journal->id)->whereIn('account_id', [$context['revenue']->id, $context['expense']->id])->count())->toBe(0)
        ->and(JournalLine::where('journal_id', $journal->id)->pluck('fund_id')->unique()->all())->toBe([$context['fund']->id]);
});

test('allocation UX completes its governed lifecycle before a realization posts exactly one payment effect', function () {
    Storage::fake('local');
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $allocation = $this->actingAs($user)->postJson(route('financial-v2.allocations.store'), [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '75.00',
        'fund_id' => $context['fund']->id, 'program_id' => $context['program']->id, 'category_id' => $context['paymentCategory']->id, 'reason' => 'Rencana biaya utilitas',
    ])->assertOk()->assertJsonPath('ok', true);
    $allocationId = $allocation->json('allocation_id');

    $this->actingAs($user)->get(route('financial-v2.allocations.create', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Status Alokasi Dana')
        ->assertSee('Ajukan alokasi');
    $this->actingAs($user)->postJson(route('financial-v2.allocations.submit', $allocationId), ['entity' => $context['entity']->id])
        ->assertOk()->assertJsonPath('ok', true);
    $this->actingAs($user)->postJson(route('financial-v2.allocations.approve', $allocationId), ['entity' => $context['entity']->id])
        ->assertOk()->assertJsonPath('ok', true);

    $this->actingAs($user)->get(route('financial-v2.transactions.create', ['operation' => 'realization', 'entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('data-realization-allocation', false)
        ->assertSee('name="counterparty_name"', false)
        ->assertSee('data-money-input', false)
        ->assertSee('sisa Rp75,00')
        ->assertSee('Lampiran bukti');

    $version = BudgetAllocation::findOrFail($allocationId)->fresh('versions')->versions->sole();
    expect($version->status)->toBe('approved')
        ->and($version->allocation->fresh()->status)->toBe('approved')
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(0);

    $receipt = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), uxReceiptPayload($context, (string) Str::uuid()))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $receipt->json('transaction_id')))->assertOk();
    $realization = $this->actingAs($user)->post(route('financial-v2.transactions.store', 'realization'), [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '20.00',
        'budget_allocation_version_id' => $version->id, 'counterparty_name' => 'Penerima Santunan Uji',
        'financial_account_id' => $context['sourceFinancialAccount']->id, 'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi biaya utilitas', 'attachment' => UploadedFile::fake()->create('bukti-realisasi.pdf', 32, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('ok', true);
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $realization->json('transaction_id')))
        ->assertStatus(422)
        ->assertJsonPath('code', 'E-REALIZATION-STATE');
    $this->actingAs($user)->postJson(route('financial-v2.realizations.submit', $realization->json('transaction_id')))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.realizations.verify', $realization->json('transaction_id')))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.realizations.approve', $realization->json('transaction_id')))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $realization->json('transaction_id')))->assertOk();

    $realizationTransaction = FinancialTransaction::with('counterparty')->findOrFail($realization->json('transaction_id'));
    $this->actingAs($user)->get(route('financial-v2.transactions.show', $realizationTransaction))
        ->assertOk()
        ->assertSee('bukti-realisasi.pdf');
    $reports = app(FinancialReportService::class);
    $trialBalance = $reports->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);
    $fundBalance = collect($reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'])['data']['rows'])
        ->sole('fund_id', $context['fund']->id);
    expect($realizationTransaction->counterparty?->display_name)->toBe('Penerima Santunan Uji')
        ->and($realizationTransaction->counterparty?->party_type)->toBe('beneficiary')
        ->and(FundRealization::where('transaction_id', $realization->json('transaction_id'))->value('status'))->toBe('recorded')
        ->and(AttachmentLink::query()
            ->where('accounting_entity_id', $context['entity']->id)
            ->where('target_type', 'transaction')
            ->where('target_id', $realizationTransaction->id)
            ->where('status', 'active')
            ->count())->toBe(1)
        ->and(app(BudgetAllocationService::class)->availability($version->id))->toBe(['allocated' => '75.00', 'actual' => '20.00', 'available' => '55.00'])
        ->and(Journal::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2)
        ->and(JournalLine::where('accounting_entity_id', $context['entity']->id)->count())->toBe(4)
        ->and(LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count())->toBe(4)
        ->and(Voucher::where('transaction_id', $realizationTransaction->id)->where('status', 'issued')->count())->toBe(1)
        ->and(Voucher::where('transaction_id', $realizationTransaction->id)->where('status', 'issued')->pluck('voucher_number')->unique()->count())->toBe(1)
        ->and($trialBalance['data']['is_balanced'])->toBeTrue()
        ->and($fundBalance['fund_balance'])->toBe('80.00');

    $factsBeforeExcess = [Journal::count(), LedgerEntry::count()];
    $excess = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'realization'), [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '60.00',
        'budget_allocation_version_id' => $version->id, 'counterparty_id' => $context['supplier']->id,
        'financial_account_id' => $context['sourceFinancialAccount']->id, 'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi melebihi alokasi',
    ])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.realizations.submit', $excess->json('transaction_id')))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.realizations.verify', $excess->json('transaction_id')))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.realizations.approve', $excess->json('transaction_id')))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $excess->json('transaction_id')))
        ->assertStatus(422)
        ->assertJsonPath('message', 'Alokasi dana tidak tersedia atau sisa alokasinya tidak mencukupi untuk realisasi ini.');

    expect([Journal::count(), LedgerEntry::count()])->toBe($factsBeforeExcess);
});

test('an allocation reopens its one active realization draft without creating facts or a duplicate', function () {
    Storage::fake('local');
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $created = $this->actingAs($user)->postJson(route('financial-v2.allocations.store'), [
        'entity' => $context['entity']->id,
        'submission_key' => (string) Str::uuid(),
        'date' => $context['today'],
        'amount' => '75.00',
        'fund_id' => $context['fund']->id,
        'program_id' => $context['program']->id,
        'category_id' => $context['paymentCategory']->id,
        'reason' => 'Rencana santunan yang sedang disiapkan',
    ])->assertOk();
    $allocation = BudgetAllocation::findOrFail($created->json('allocation_id'));
    $this->actingAs($user)->postJson(route('financial-v2.allocations.submit', $allocation), ['entity' => $context['entity']->id])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.allocations.approve', $allocation), ['entity' => $context['entity']->id])->assertOk();
    $version = $allocation->fresh('versions')->versions->sole();

    $submissionKey = (string) Str::uuid();
    $payload = [
        'entity' => $context['entity']->id,
        'submission_key' => $submissionKey,
        'date' => $context['today'],
        'amount' => '20.00',
        'budget_allocation_version_id' => $version->id,
        'counterparty_name' => 'Penerima Draft Realisasi',
        'financial_account_id' => $context['sourceFinancialAccount']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Realisasi sedang disiapkan',
        'attachment' => UploadedFile::fake()->create('bukti-draft.pdf', 32, 'application/pdf'),
    ];
    $draft = $this->actingAs($user)->post(route('financial-v2.transactions.store', 'realization'), $payload, ['Accept' => 'application/json'])
        ->assertOk()
        ->assertJsonPath('ok', true);
    $draftId = $draft->json('transaction_id');
    $factsBefore = [Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()];

    $updatePayload = $payload;
    unset($updatePayload['attachment']);
    $updatePayload['amount'] = '25.00';
    $updatePayload['description'] = 'Realisasi draft telah diperbarui';
    $this->actingAs($user)->putJson(route('financial-v2.transactions.update', $draftId), $updatePayload)->assertOk();
    expect(FinancialTransaction::findOrFail($draftId)->gross_amount)->toBe('25.00')
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()])->toBe($factsBefore);

    $this->actingAs($user)->get(route('financial-v2.transactions.create', [
        'operation' => 'realization',
        'entity' => $context['entity']->id,
        'allocation_version_id' => $version->id,
    ]))->assertRedirect(route('financial-v2.transactions.show', $draftId));

    $payload['submission_key'] = (string) Str::uuid();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'realization'), $payload)
        ->assertOk()
        ->assertJsonPath('duplicate', true)
        ->assertJsonPath('transaction_id', $draftId);

    expect(FinancialTransaction::where('accounting_entity_id', $context['entity']->id)->whereHas('realization', fn ($query) => $query->where('budget_allocation_version_id', $version->id))->count())->toBe(1)
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()])->toBe($factsBefore);

    $this->actingAs($user)->get(route('financial-v2.allocations.create', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Lanjutkan Realisasi')
        ->assertSee(route('financial-v2.transactions.show', $draftId), false);
    $this->actingAs($user)->get(route('financial-v2.realizations.drafts', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Draft Realisasi')
        ->assertSee('Penerima Draft Realisasi')
        ->assertSee('1 bukti terlampir');
    $this->actingAs($user)->get(route('financial-v2.transactions.show', $draftId))
        ->assertOk()
        ->assertSee('bukti-draft.pdf');
    $this->actingAs($user)->get(route('financial-v2.transactions.index', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertDontSee($draftId);

    $this->actingAs($user)->postJson(route('financial-v2.transactions.cancel', $draftId), [
        'reason' => 'Rencana perlu diperbaiki sebelum dicatat resmi.',
    ])->assertOk();

    expect(FinancialTransaction::findOrFail($draftId)->status)->toBe('cancelled')
        ->and(FundRealization::where('transaction_id', $draftId)->value('status'))->toBe('cancelled')
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()])->toBe($factsBefore);
});

test('an unfixed allocation can be cancelled without financial facts and draft payments are hidden by default', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $postedReceipt = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), uxReceiptPayload($context, (string) Str::uuid()))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $postedReceipt->json('transaction_id')))->assertOk();
    $postedTransaction = FinancialTransaction::findOrFail($postedReceipt->json('transaction_id'));

    $created = $this->actingAs($user)->postJson(route('financial-v2.allocations.store'), [
        'entity' => $context['entity']->id,
        'submission_key' => (string) Str::uuid(),
        'date' => $context['today'],
        'amount' => '75.00',
        'fund_id' => $context['fund']->id,
        'program_id' => $context['program']->id,
        'category_id' => $context['paymentCategory']->id,
        'reason' => 'Rencana yang belum final',
    ])->assertOk();
    $allocationId = $created->json('allocation_id');
    $this->actingAs($user)->postJson(route('financial-v2.allocations.submit', $allocationId), ['entity' => $context['entity']->id])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.allocations.approve', $allocationId), ['entity' => $context['entity']->id])->assertOk();
    $allocation = BudgetAllocation::findOrFail($allocationId)->fresh('versions');
    $version = $allocation->versions->sole();

    $draftRealization = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'realization'), [
        'entity' => $context['entity']->id,
        'submission_key' => (string) Str::uuid(),
        'date' => $context['today'],
        'amount' => '20.00',
        'budget_allocation_version_id' => $version->id,
        'counterparty_name' => 'Penerima rencana belum final',
        'financial_account_id' => $context['sourceFinancialAccount']->id,
        'category_id' => $context['paymentCategory']->id,
        'description' => 'Pembayaran draft yang belum final',
    ])->assertOk();
    $draftTransaction = FinancialTransaction::findOrFail($draftRealization->json('transaction_id'));
    expect($draftTransaction->status)->toBe('draft')
        ->and(FundRealization::where('transaction_id', $draftTransaction->id)->value('status'))->toBe('draft');

    $reports = app(FinancialReportService::class);
    $fundBefore = collect($reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'])['data']['rows'])
        ->sole('fund_id', $context['fund']->id)['fund_balance'];
    $factsBefore = [
        'journals' => Journal::where('accounting_entity_id', $context['entity']->id)->count(),
        'lines' => JournalLine::where('accounting_entity_id', $context['entity']->id)->count(),
        'ledger' => LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(),
        'transactions' => FinancialTransaction::where('accounting_entity_id', $context['entity']->id)->count(),
    ];

    $reason = 'Data belum final / perlu diperbaiki.';
    $this->actingAs($user)->postJson(route('financial-v2.allocations.cancel', $allocation), [
        'entity' => $context['entity']->id,
        'reason' => $reason,
    ])->assertOk()->assertJsonPath('ok', true)->assertJsonPath('status', 'cancelled');

    $cancelled = $allocation->fresh(['versions', 'cancelledBy']);
    $cancelledAt = $cancelled->cancelled_at?->toJSON();
    $event = AuditEvent::query()
        ->where('accounting_entity_id', $context['entity']->id)
        ->where('target_type', 'budget_allocation')
        ->where('target_id', $allocation->id)
        ->where('event_type', 'budget_allocation_cancelled')
        ->sole();
    expect($cancelled->status)->toBe('cancelled')
        ->and($cancelled->versions->sole()->status)->toBe('cancelled')
        ->and($cancelled->cancellation_reason)->toBe($reason)
        ->and($cancelled->cancelled_by_user_id)->toBe($user->id)
        ->and($cancelled->cancelledBy?->id)->toBe($user->id)
        ->and($cancelledAt)->not->toBeNull()
        ->and(json_decode((string) $event->before_summary, true)['status'])->toBe('approved')
        ->and(json_decode((string) $event->after_summary, true)['status'])->toBe('cancelled')
        ->and(json_decode((string) $event->after_summary, true)['cancellation_reason'])->toBe($reason)
        ->and([Journal::where('accounting_entity_id', $context['entity']->id)->count(), JournalLine::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count(), FinancialTransaction::where('accounting_entity_id', $context['entity']->id)->count()])
        ->toBe([$factsBefore['journals'], $factsBefore['lines'], $factsBefore['ledger'], $factsBefore['transactions']])
        ->and(collect($reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today'])['data']['rows'])->sole('fund_id', $context['fund']->id)['fund_balance'])->toBe($fundBefore);

    $this->actingAs($user)->postJson(route('financial-v2.allocations.cancel', $allocation), [
        'entity' => $context['entity']->id,
        'reason' => 'Alasan kedua tidak boleh membuat audit baru.',
    ])->assertOk()->assertJsonPath('status', 'cancelled');
    expect($allocation->fresh()->cancelled_at?->toJSON())->toBe($cancelledAt)
        ->and(AuditEvent::query()->where('target_type', 'budget_allocation')->where('target_id', $allocation->id)->where('event_type', 'budget_allocation_cancelled')->count())->toBe(1);

    $this->actingAs($user)->get(route('financial-v2.allocations.create', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Dibatalkan')
        ->assertSee('Alokasi dibatalkan')
        ->assertSee($reason)
        ->assertDontSee('Lanjutkan ke realisasi');
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $draftTransaction))
        ->assertStatus(422)
        ->assertJsonPath('code', 'E-REALIZATION-ALLOCATION');
    expect($draftTransaction->fresh()->status)->toBe('draft')
        ->and([Journal::where('accounting_entity_id', $context['entity']->id)->count(), LedgerEntry::where('accounting_entity_id', $context['entity']->id)->count()])
        ->toBe([$factsBefore['journals'], $factsBefore['ledger']]);

    $this->actingAs($user)->get(route('financial-v2.transactions.index', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee($postedTransaction->source_reference)
        ->assertDontSee($draftTransaction->source_reference);
    $this->actingAs($user)->get(route('financial-v2.transactions.index', ['entity' => $context['entity']->id, 'status' => 'draft']))
        ->assertOk()
        ->assertSee($draftTransaction->source_reference)
        ->assertDontSee($postedTransaction->source_reference);
    $this->actingAs($user)->get(route('financial-v2.dashboard', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertDontSee($draftTransaction->source_reference);
});

test('dashboard and history use official financial records and do not require legacy financial records', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('financial-v2.dashboard', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('Dashboard Keuangan')->assertSee('Saldo di halaman ini dihitung dari pencatatan resmi');
    $this->actingAs($user)->get(route('financial-v2.transactions.index', ['entity' => $context['entity']->id]))
        ->assertOk()->assertSee('Riwayat Transaksi')->assertSee('Tidak ada transaksi yang cocok.');
});

test('business navigation and fund screens distinguish rekening, dana, and program without exposing technical ledger detail', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('financial-v2.dashboard', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Penerimaan')
        ->assertSee('Pengeluaran')
        ->assertSee('Transfer')
        ->assertSee('Dana')
        ->assertSee('Alokasi Dana')
        ->assertSee('Riwayat Transaksi')
        ->assertSee('Laporan')
        ->assertSee('Kontrol')
        ->assertSee('Saldo Dana')
        ->assertSee('Likuiditas rekening/kas ditampilkan terpisah')
        ->assertSee('min-w-0 rounded-2xl bg-base-100 p-5', false)
        ->assertDontSee('Saldo Awal');

    $this->actingAs($user)->get(route('financial-v2.funds.index', ['entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Kelompok Dana')
        ->assertSee('Operasional Masjid / Kas Masjid')
        ->assertSee('Total Saldo Dana')
        ->assertSee('Tempat uang berada')
        ->assertSee('Kegiatan atau tujuan penggunaan dana');

    $this->actingAs($user)->get(route('financial-v2.funds.groups.show', ['group' => 'operational', 'entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Dana Operasional')
        ->assertSee('Saldo Dana')
        ->assertSee('Dana dalam kelompok');

    $this->actingAs($user)->get(route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Infak Jumat')
        ->assertDontSee('Listrik');

    $this->actingAs($user)->get(route('financial-v2.funds.show', ['fund' => $context['fund'], 'entity' => $context['entity']->id]))
        ->assertOk()
        ->assertSee('Dana Operasional')
        ->assertSee('Operasional masjid')
        ->assertSee('Komposisi Rekening')
        ->assertSee('Alokasi dan realisasi')
        ->assertDontSee('General Ledger');
});

test('Program attribution is validated against its lifecycle dates and remains distinct from Fund and Financial Account', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();
    $payload = uxReceiptPayload($context, (string) Str::uuid());
    $payload['program_id'] = $context['program']->id;
    $receipt = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), $payload)->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $receipt->json('transaction_id')))->assertOk();

    $journal = Journal::where('transaction_id', $receipt->json('transaction_id'))->sole();
    $programLineIds = JournalLine::where('journal_id', $journal->id)->where('program_id', $context['program']->id)->pluck('id');

    expect($programLineIds)->toHaveCount(2)
        ->and(JournalLine::whereIn('id', $programLineIds)->pluck('fund_id')->unique()->all())->toBe([$context['fund']->id])
        ->and(JournalLine::whereIn('id', $programLineIds)->whereNotNull('financial_account_id')->value('financial_account_id'))->toBe($context['sourceFinancialAccount']->id)
        ->and(LedgerEntry::whereIn('journal_line_id', $programLineIds)->where('program_id', $context['program']->id)->count())->toBe(2)
        ->and($context['program']->id)->not->toBe($context['fund']->id)
        ->and($context['program']->id)->not->toBe($context['sourceFinancialAccount']->id);

    $withoutProgram = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), uxReceiptPayload($context, (string) Str::uuid()))->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $withoutProgram->json('transaction_id')))->assertOk();
    expect(JournalLine::where('journal_id', Journal::where('transaction_id', $withoutProgram->json('transaction_id'))->value('id'))->whereNotNull('program_id')->count())->toBe(0);

    $invalidProgram = uxReceiptPayload($context, (string) Str::uuid());
    $invalidProgram['program_id'] = (string) Str::uuid();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), $invalidProgram)
        ->assertStatus(422)->assertJsonPath('code', 'E-UX-PROGRAM');

    $inactiveProgram = Program::create(['accounting_entity_id' => $context['entity']->id, 'code' => 'INACTIVE-PRG', 'name' => 'Program Nonaktif', 'status' => 'suspended']);
    $inactivePayload = uxReceiptPayload($context, (string) Str::uuid());
    $inactivePayload['program_id'] = $inactiveProgram->id;
    $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), $inactivePayload)
        ->assertStatus(422)->assertJsonPath('code', 'E-UX-PROGRAM');
});

test('reporting foundation derives balances, movements, and tie-outs only from posted V2 facts', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();
    $receiptPayload = uxReceiptPayload($context, (string) Str::uuid());
    $receiptPayload['amount'] = '100.00';
    $receipt = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), $receiptPayload)->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $receipt->json('transaction_id')))->assertOk();

    $payment = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'payment'), [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '30.00',
        'counterparty_id' => $context['supplier']->id, 'financial_account_id' => $context['sourceFinancialAccount']->id,
        'fund_id' => $context['fund']->id, 'category_id' => $context['paymentCategory']->id, 'description' => 'Biaya utilitas uji laporan',
    ])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $payment->json('transaction_id')))->assertOk();

    $transfer = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'transfer'), [
        'entity' => $context['entity']->id, 'submission_key' => (string) Str::uuid(), 'date' => $context['today'], 'amount' => '20.00',
        'source_financial_account_id' => $context['sourceFinancialAccount']->id, 'destination_financial_account_id' => $context['destinationFinancialAccount']->id,
        'fund_id' => $context['fund']->id, 'description' => 'Transfer kas ke bank untuk uji laporan',
    ])->assertOk();
    $this->actingAs($user)->postJson(route('financial-v2.transactions.post', $transfer->json('transaction_id')))->assertOk();

    $beforeFacts = [Journal::count(), JournalLine::count(), LedgerEntry::count(), FinancialTransaction::count()];
    $reports = app(FinancialReportService::class);
    $accountBalances = $reports->report('account-balance', $context['entity']->id, $context['today'], $context['today']);
    $fundBalances = $reports->report('fund-balance', $context['entity']->id, $context['today'], $context['today']);
    $cashFlow = $reports->report('cash-flow', $context['entity']->id, $context['today'], $context['today']);
    $trialBalance = $reports->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);
    $history = $reports->report('transaction-history', $context['entity']->id, $context['today'], $context['today']);
    $accountMovement = $reports->report('account-movement', $context['entity']->id, $context['today'], $context['today'], ['financial_account_id' => $context['sourceFinancialAccount']->id]);
    $fundMovement = $reports->report('fund-movement', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $context['fund']->id]);
    $friday = $reports->report('friday', $context['entity']->id, $context['today'], $context['today']);
    $summary = $reports->report('summary', $context['entity']->id, $context['today'], $context['today']);
    $ziswaf = $reports->report('ziswaf', $context['entity']->id, $context['today'], $context['today'], ['fund_id' => $context['fund']->id]);
    $program = $reports->report('program', $context['entity']->id, $context['today'], $context['today']);

    $accounts = collect($accountBalances['data']['rows'])->keyBy('code');
    $fund = collect($fundBalances['data']['rows'])->sole('fund_id', $context['fund']->id);

    expect($accountBalances['source'])->toBe('financial_v2_posted_general_ledger')
        ->and($accountBalances['as_of_posting_sequence'])->toBe(3)
        ->and($accounts->get('KAS')['closing_balance'])->toBe('50.00')
        ->and($accounts->get('BNK')['closing_balance'])->toBe('20.00')
        ->and($fund['fund_balance'])->toBe('70.00')
        ->and($fund['available_liquidity'])->toBe('70.00')
        ->and($fund['receipts'])->toBe('100.00')
        ->and($fund['expenses'])->toBe('30.00')
        ->and($fund['transfer_in'])->toBe('0.00')
        ->and($fund['transfer_out'])->toBe('0.00')
        ->and($fund['other_policy_components'])->toBe('0.00')
        ->and($fund['closing_net_position'])->toBe('70.00')
        ->and(\App\Domain\FinancialV2\DecimalAmount::sum(collect($fundBalances['data']['account_composition'])->pluck('liquidity_balance')))->toBe($fund['available_liquidity'])
        ->and($cashFlow['data']['opening_balance'])->toBe('0.00')
        ->and($cashFlow['data']['cash_in'])->toBe('100.00')
        ->and($cashFlow['data']['cash_out'])->toBe('30.00')
        ->and($cashFlow['data']['closing_balance'])->toBe('70.00')
        ->and($cashFlow['data']['is_tied_out'])->toBeTrue()
        ->and($trialBalance['data']['total_debit'])->toBe('150.00')
        ->and($trialBalance['data']['total_credit'])->toBe('150.00')
        ->and($trialBalance['data']['is_balanced'])->toBeTrue()
        ->and($history['data']['rows'])->toHaveCount(3)
        ->and($accountMovement['data']['rows'])->toHaveCount(3)
        ->and($fundMovement['data']['rows'])->toHaveCount(6)
        ->and($fundMovement['data']['period_opening_fund_balance'])->toBe('0.00')
        ->and(collect($fundMovement['data']['rows'])->last()['running_fund_balance'])->toBe('70.00')
        ->and($friday['data']['closing_balance'])->toBe('70.00')
        ->and($summary['data']['cash_position'])->toBe('70.00')
        ->and($ziswaf['data']['rows'])->toHaveCount(1)
        ->and($program['data']['has_data'])->toBeFalse()
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), FinancialTransaction::count()])->toBe($beforeFacts);

    $this->actingAs($user)->getJson(route('financial-v2.reports.data', [
        'entity' => $context['entity']->id, 'report' => 'fund-balance', 'from' => $context['today'], 'through' => $context['today'], 'fund_id' => $context['fund']->id,
    ]))->assertOk()
        ->assertJsonPath('data.rows.0.fund_balance', '70.00')
        ->assertJsonPath('data.rows.0.available_liquidity', '70.00')
        ->assertJsonPath('data.rows.0.expenses', '30.00')
        ->assertJsonPath('data.compatibility.deprecated_aliases.closing_net_position', 'Use fund_balance.');

    $this->actingAs($user)->get(route('financial-v2.reports.index', [
        'entity' => $context['entity']->id, 'report' => 'fund-balance', 'from' => $context['today'], 'through' => $context['today'], 'fund_id' => $context['fund']->id,
    ]))->assertOk()
        ->assertSee('Saldo Dana')
        ->assertSee('Likuiditas tersedia')
        ->assertSee('Komposisi Rekening');

    $this->actingAs($user)->get(route('financial-v2.reports.index', [
        'entity' => $context['entity']->id, 'report' => 'friday', 'from' => $context['today'], 'through' => $context['today'],
    ]))->assertOk()
        ->assertSee('Total pemasukan')
        ->assertSee('Total pengeluaran');
});

test('report API and page are zero-data safe and exclude Financial V2 drafts', function () {
    $context = uxOperationalContext();
    $user = User::factory()->create();
    $draft = $this->actingAs($user)->postJson(route('financial-v2.transactions.store', 'receipt'), uxReceiptPayload($context, (string) Str::uuid()))->assertOk();
    expect(FinancialTransaction::findOrFail($draft->json('transaction_id'))->status)->toBe('draft');

    $reports = app(FinancialReportService::class);
    $trialBalance = $reports->report('trial-balance', $context['entity']->id, $context['today'], $context['today']);
    expect($trialBalance['data']['has_data'])->toBeFalse()
        ->and($trialBalance['as_of_posting_sequence'])->toBe(0);

    $this->actingAs($user)->getJson(route('financial-v2.reports.data', [
        'entity' => $context['entity']->id, 'report' => 'summary', 'from' => $context['today'], 'through' => $context['today'],
    ]))->assertOk()
        ->assertJsonPath('source', 'financial_v2_posted_general_ledger')
        ->assertJsonPath('data.has_data', false);
    $this->actingAs($user)->get(route('financial-v2.reports.index', [
        'entity' => $context['entity']->id, 'report' => 'trial-balance', 'from' => $context['today'], 'through' => $context['today'],
    ]))->assertOk()
        ->assertSee('Neraca Saldo')
        ->assertSee('Belum ada data pada periode ini.');
});
