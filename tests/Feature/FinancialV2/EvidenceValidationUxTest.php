<?php

use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\TransactionEvidenceStatusService;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UatFinancialFixture;

function requirePaymentEvidence(array $context, string $type, int $minimum = 1): EvidenceRequirement
{
    return EvidenceRequirement::create([
        'accounting_entity_id' => $context['entity']->id,
        'posting_rule_version_id' => $context['paymentVersion']->id,
        'evidence_type' => $type,
        'minimum_count' => $minimum,
    ]);
}

test('evidence status distinguishes no attachment, mismatch, match, and multiple requirements', function () {
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '100.00');
    requirePaymentEvidence($context, 'invoice');
    requirePaymentEvidence($context, 'statement');
    $requirements = $context['paymentVersion']->evidenceRequirements()->orderBy('evidence_type')->get();
    $statusService = app(TransactionEvidenceStatusService::class);

    $empty = $statusService->evaluate($transaction, $requirements);
    expect($empty['complete'])->toBeFalse()
        ->and($empty['files'])->toBe([])
        ->and($empty['missing'])->toHaveCount(2);

    app(EvidenceService::class)->attachToTransaction(
        $context['entity']->id, $transaction->id, 'rekening-koran.pdf', 'application/pdf', 10,
        hash('sha256', 'statement'), 'test://statement', 'statement',
    );
    $partial = $statusService->evaluate($transaction, $requirements);
    expect($partial['complete'])->toBeFalse()
        ->and(collect($partial['required'])->firstWhere('value', 'statement')['complete'])->toBeTrue()
        ->and(collect($partial['required'])->firstWhere('value', 'invoice')['complete'])->toBeFalse()
        ->and($partial['files'][0]['label'])->toBe('Rekening Koran')
        ->and($partial['files'][0]['status_label'])->toBe('Aktif');

    app(EvidenceService::class)->attachToTransaction(
        $context['entity']->id, $transaction->id, 'invoice.pdf', 'application/pdf', 10,
        hash('sha256', 'invoice'), 'test://invoice', 'invoice',
    );
    expect($statusService->evaluate($transaction, $requirements)['complete'])->toBeTrue();
});

test('submit guard returns actionable structured evidence details without financial facts', function () {
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '100.00');
    requirePaymentEvidence($context, 'invoice');
    app(EvidenceService::class)->attachToTransaction(
        $context['entity']->id, $transaction->id, 'rekening-koran.pdf', 'application/pdf', 10,
        hash('sha256', 'wrong-type'), 'test://wrong-type', 'statement',
    );
    $before = [Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()];

    try {
        app(FinancialTransactionLifecycleService::class)->submit($transaction->id);
        $this->fail('Submit should reject mismatched evidence.');
    } catch (FinancialDomainException $exception) {
        expect($exception->failureCode)->toBe('E-EVIDENCE-REQUIRED')
            ->and($exception->getMessage())->toContain('jenis atau jumlah')
            ->and($exception->details['required'][0])->toMatchArray(['type' => 'INVOICE', 'minimum' => 1, 'count' => 0])
            ->and($exception->details['actual'][0])->toMatchArray(['type' => 'STATEMENT', 'count' => 1])
            ->and($exception->details['files'][0]['filename'])->toBe('rekening-koran.pdf');
    }

    expect($transaction->fresh()->status)->toBe('draft')
        ->and([Journal::count(), JournalLine::count(), LedgerEntry::count(), Voucher::count()])->toBe($before);
});

test('detail page explains mismatch and upload action defaults to the missing type', function () {
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '100.00');
    requirePaymentEvidence($context, 'invoice');
    app(EvidenceService::class)->attachToTransaction(
        $context['entity']->id, $transaction->id, 'rekening-koran.pdf', 'application/pdf', 10,
        hash('sha256', 'detail-wrong'), 'test://detail-wrong', 'statement',
    );

    $this->actingAs(User::factory()->create())->get(route('financial-v2.transactions.show', $transaction))
        ->assertOk()
        ->assertSee('File sudah ada, tetapi jenis atau jumlah buktinya belum sesuai.')
        ->assertSee('Rekening Koran')
        ->assertSee('Aktif')
        ->assertSee('Tambah Invoice')
        ->assertSee('value="invoice" selected', false);
});

test('draft upload preserves the selected type, updates status, and remains idempotent', function () {
    Storage::fake('local');
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '100.00');
    requirePaymentEvidence($context, 'invoice');
    $user = User::factory()->create();
    $contents = "%PDF-1.4\nactionable evidence\n%%EOF";
    $payload = [
        'entity' => $context['entity']->id,
        'evidence_type' => 'invoice',
        'attachment' => UploadedFile::fake()->createWithContent('invoice.pdf', $contents),
    ];

    $this->actingAs($user)->post(route('financial-v2.attachments.store', $transaction), $payload, ['Accept' => 'application/json'])
        ->assertOk()->assertJsonPath('evidence.complete', true);
    $this->actingAs($user)->post(route('financial-v2.attachments.store', $transaction), [
        'entity' => $context['entity']->id,
        'evidence_type' => 'invoice',
        'attachment' => UploadedFile::fake()->createWithContent('invoice.pdf', $contents),
    ], ['Accept' => 'application/json'])->assertOk();

    expect(AttachmentLink::where('target_id', $transaction->id)->count())->toBe(1)
        ->and(AttachmentLink::where('target_id', $transaction->id)->value('evidence_type'))->toBe('invoice');
});
