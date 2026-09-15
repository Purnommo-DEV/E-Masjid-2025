<?php

use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\TransactionEvidenceUploadService;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\AuditEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UatFinancialFixture;

function attachEvidence(array $context, string $transactionId, string $contents, string $type = 'statement'): AttachmentLink
{
    return app(EvidenceService::class)->attachToTransaction(
        $context['entity']->id,
        $transactionId,
        'evidence.pdf',
        'application/pdf',
        strlen($contents),
        hash('sha256', $contents),
        'test://evidence/'.hash('sha256', $contents),
        $type,
    );
}

test('retrying the same draft evidence reuses one active link', function () {
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '10.00');

    $first = attachEvidence($context, $transaction->id, 'same-pdf');
    $second = attachEvidence($context, $transaction->id, 'same-pdf');

    expect($second->id)->toBe($first->id)
        ->and(Attachment::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1)
        ->and(AttachmentLink::where('target_id', $transaction->id)->count())->toBe(1)
        ->and(AuditEvent::where('target_id', $first->id)->where('event_type', 'attachment_linked')->count())->toBe(1);
});

test('a draft can keep distinct evidence while one attachment can be shared across transactions', function () {
    $context = UatFinancialFixture::context();
    $firstTransaction = UatFinancialFixture::payment($context, '10.00');
    $secondTransaction = UatFinancialFixture::payment($context, '11.00');

    $first = attachEvidence($context, $firstTransaction->id, 'shared-pdf');
    $second = attachEvidence($context, $firstTransaction->id, 'second-pdf', 'invoice');
    $shared = attachEvidence($context, $secondTransaction->id, 'shared-pdf');

    expect($first->id)->not->toBe($second->id)
        ->and($shared->id)->not->toBe($first->id)
        ->and($shared->attachment_id)->toBe($first->attachment_id)
        ->and(Attachment::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2)
        ->and(AttachmentLink::where('accounting_entity_id', $context['entity']->id)->count())->toBe(3);
});

test('changing evidence type or restoring a released link updates the same draft link with audit', function () {
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '10.00');
    $service = app(EvidenceService::class);

    $original = attachEvidence($context, $transaction->id, 'mutable-pdf');
    $changed = attachEvidence($context, $transaction->id, 'mutable-pdf', 'invoice');
    $service->removeDraftTransactionEvidence($changed->id, 'Incorrect evidence selected');
    $restored = attachEvidence($context, $transaction->id, 'mutable-pdf', 'invoice');

    expect($changed->id)->toBe($original->id)
        ->and($changed->evidence_type)->toBe('invoice')
        ->and($restored->id)->toBe($original->id)
        ->and($restored->status)->toBe('active')
        ->and(AttachmentLink::where('target_id', $transaction->id)->count())->toBe(1)
        ->and(AuditEvent::where('target_id', $original->id)->where('event_type', 'attachment_link_updated_on_draft')->count())->toBe(2);
});

test('an immutable transaction reuses an unchanged active link but rejects link mutation', function () {
    $context = UatFinancialFixture::context();
    $transaction = UatFinancialFixture::payment($context, '10.00');
    $original = attachEvidence($context, $transaction->id, 'immutable-pdf');
    app(FinancialTransactionLifecycleService::class)->submit($transaction->id);

    expect(attachEvidence($context, $transaction->id, 'immutable-pdf')->id)->toBe($original->id)
        ->and(fn () => attachEvidence($context, $transaction->id, 'immutable-pdf', 'invoice'))
        ->toThrow(FinancialDomainException::class, 'only be changed while the transaction is Draft')
        ->and(AttachmentLink::where('target_id', $transaction->id)->count())->toBe(1);
});

test('batch upload retries remain idempotent and preserve one shared attachment', function () {
    Storage::fake('local');
    $context = UatFinancialFixture::context();
    $firstTransaction = UatFinancialFixture::payment($context, '10.00');
    $secondTransaction = UatFinancialFixture::payment($context, '11.00');
    $service = app(TransactionEvidenceUploadService::class);
    $contents = "%PDF-1.4\nshared batch evidence\n%%EOF";

    $firstRun = $service->attachToMany($context['entity']->id, [$firstTransaction->id, $secondTransaction->id], UploadedFile::fake()->createWithContent('batch.pdf', $contents), 'statement');
    $retry = $service->attachToMany($context['entity']->id, [$firstTransaction->id, $secondTransaction->id], UploadedFile::fake()->createWithContent('batch.pdf', $contents), 'statement');

    expect($retry->pluck('id')->all())->toBe($firstRun->pluck('id')->all())
        ->and(Attachment::where('accounting_entity_id', $context['entity']->id)->count())->toBe(1)
        ->and(AttachmentLink::where('accounting_entity_id', $context['entity']->id)->count())->toBe(2);
});
