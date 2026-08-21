<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\Reconciliation;
use Illuminate\Support\Facades\DB;

/** Stores evidence metadata and immutable links, never a financial fact. */
final class EvidenceService
{
    private const ACCEPTED_MEDIA_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/heic',
        'image/heif',
    ];

    public function __construct(private readonly AuditTrailService $auditTrail) {}

    public function attachToTransaction(string $entityId, string $transactionId, string $originalFilename, string $mediaType, int $byteSize, string $contentHash, string $storageReference, string $evidenceType, ?int $actorUserId = null): AttachmentLink
    {
        return DB::transaction(function () use ($entityId, $transactionId, $originalFilename, $mediaType, $byteSize, $contentHash, $storageReference, $evidenceType, $actorUserId): AttachmentLink {
            if (! in_array($mediaType, self::ACCEPTED_MEDIA_TYPES, true) || $byteSize <= 0 || ! preg_match('/^[a-f0-9]{64}(?:[a-f0-9]{64})?$/i', $contentHash)) {
                throw new FinancialDomainException('E-ATTACHMENT-INVALID', 'Evidence must be a supported image/PDF with positive size and SHA-256/SHA-512 hash.');
            }
            if (! in_array($evidenceType, ['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other'], true)) {
                throw new FinancialDomainException('E-ATTACHMENT-TYPE', 'Evidence type is not in the approved evidence taxonomy.');
            }
            $transaction = FinancialTransaction::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->findOrFail($transactionId);
            $attachment = Attachment::firstOrCreate(
                ['accounting_entity_id' => $entityId, 'content_hash' => $contentHash],
                [
                    'original_filename' => $originalFilename,
                    'media_type' => $mediaType,
                    'byte_size' => $byteSize,
                    'storage_reference' => $storageReference,
                    'status' => 'active',
                    'received_at' => now(),
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ],
            );
            if ($attachment->storage_reference !== $storageReference || $attachment->media_type !== $mediaType || (int) $attachment->byte_size !== $byteSize) {
                throw new FinancialDomainException('E-ATTACHMENT-INTEGRITY', 'A content hash cannot be silently reused with different evidence metadata.');
            }
            $link = AttachmentLink::create([
                'accounting_entity_id' => $entityId,
                'attachment_id' => $attachment->id,
                'target_type' => 'transaction',
                'target_id' => $transaction->id,
                'evidence_type' => $evidenceType,
                'status' => 'active',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($entityId, 'attachment_linked', 'attachment_link', $link->id, $transaction->correlation_id, $actorUserId, null, ['transaction_id' => $transaction->id, 'attachment_id' => $attachment->id, 'evidence_type' => $evidenceType]);

            return $link;
        }, 3);
    }

    public function supersedeTransactionEvidence(string $attachmentLinkId, string $replacementAttachmentLinkId, string $reason, ?int $actorUserId = null): AttachmentLink
    {
        if (blank($reason)) {
            throw new FinancialDomainException('E-ATTACHMENT-REASON', 'Superseding posted evidence requires a reason.');
        }

        return DB::transaction(function () use ($attachmentLinkId, $replacementAttachmentLinkId, $reason, $actorUserId): AttachmentLink {
            $original = AttachmentLink::query()->lockForUpdate()->findOrFail($attachmentLinkId);
            $replacement = AttachmentLink::query()->lockForUpdate()->findOrFail($replacementAttachmentLinkId);
            if ($original->target_type !== 'transaction' || $replacement->target_type !== 'transaction'
                || $original->accounting_entity_id !== $replacement->accounting_entity_id
                || $original->target_id !== $replacement->target_id || $original->status !== 'active' || $replacement->status !== 'active') {
                throw new FinancialDomainException('E-ATTACHMENT-SUPERSEDE', 'Evidence replacement must target the same active transaction evidence context.');
            }
            $transaction = FinancialTransaction::query()->findOrFail($original->target_id);
            $original->update(['status' => 'superseded', 'updated_by_user_id' => $actorUserId]);
            $this->auditTrail->record($original->accounting_entity_id, 'attachment_superseded', 'attachment_link', $original->id, $transaction->correlation_id, $actorUserId, ['status' => 'active'], ['status' => 'superseded', 'replacement_attachment_link_id' => $replacement->id, 'reason' => $reason]);

            return $original->fresh();
        }, 3);
    }

    /**
     * Adds append-only supporting evidence to a reconciliation control record.
     * This never changes statement, book, Journal, JournalLine, or Ledger facts.
     */
    public function attachToReconciliation(string $entityId, string $reconciliationId, string $originalFilename, string $mediaType, int $byteSize, string $contentHash, string $storageReference, string $evidenceType, ?int $actorUserId = null): AttachmentLink
    {
        return DB::transaction(function () use ($entityId, $reconciliationId, $originalFilename, $mediaType, $byteSize, $contentHash, $storageReference, $evidenceType, $actorUserId): AttachmentLink {
            $this->assertValidEvidence($mediaType, $byteSize, $contentHash, $evidenceType);
            $reconciliation = Reconciliation::query()
                ->where('accounting_entity_id', $entityId)
                ->lockForUpdate()
                ->findOrFail($reconciliationId);
            if ($reconciliation->status === 'completed') {
                throw new FinancialDomainException('E-RECONCILIATION-IMMUTABLE', 'Completed reconciliations cannot receive new evidence. Create a governed follow-up reconciliation instead.');
            }

            $attachment = $this->findOrCreateAttachment($entityId, $originalFilename, $mediaType, $byteSize, $contentHash, $storageReference, $actorUserId);
            $link = AttachmentLink::create([
                'accounting_entity_id' => $entityId,
                'attachment_id' => $attachment->id,
                'target_type' => 'reconciliation',
                'target_id' => $reconciliation->id,
                'evidence_type' => $evidenceType,
                'status' => 'active',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($entityId, 'reconciliation_evidence_linked', 'attachment_link', $link->id, (string) \Illuminate\Support\Str::uuid(), $actorUserId, null, ['reconciliation_id' => $reconciliation->id, 'attachment_id' => $attachment->id, 'evidence_type' => $evidenceType]);

            return $link;
        }, 3);
    }

    /** Adds evidence to a Draft opening-position line; posted lines remain immutable. */
    public function attachToOpeningBalanceLine(string $entityId, string $lineId, string $originalFilename, string $mediaType, int $byteSize, string $contentHash, string $storageReference, string $evidenceType, ?int $actorUserId = null): AttachmentLink
    {
        return DB::transaction(function () use ($entityId, $lineId, $originalFilename, $mediaType, $byteSize, $contentHash, $storageReference, $evidenceType, $actorUserId): AttachmentLink {
            $this->assertValidEvidence($mediaType, $byteSize, $contentHash, $evidenceType);
            $line = OpeningBalanceLine::query()->with('openingBalanceBatch')->where('accounting_entity_id', $entityId)->lockForUpdate()->findOrFail($lineId);
            if ($line->openingBalanceBatch->status !== 'draft') {
                throw new FinancialDomainException('E-OPENING-EVIDENCE-IMMUTABLE', 'Evidence may only be attached while an opening balance is Draft.');
            }
            $attachment = $this->findOrCreateAttachment($entityId, $originalFilename, $mediaType, $byteSize, $contentHash, $storageReference, $actorUserId);
            $link = AttachmentLink::create([
                'accounting_entity_id' => $entityId,
                'attachment_id' => $attachment->id,
                'target_type' => 'opening_balance_line',
                'target_id' => $line->id,
                'evidence_type' => $evidenceType,
                'status' => 'active',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->auditTrail->record($entityId, 'opening_balance_evidence_linked', 'attachment_link', $link->id, (string) \Illuminate\Support\Str::uuid(), $actorUserId, null, ['opening_balance_line_id' => $line->id, 'attachment_id' => $attachment->id, 'evidence_type' => $evidenceType]);

            return $link;
        }, 3);
    }

    private function assertValidEvidence(string $mediaType, int $byteSize, string $contentHash, string $evidenceType): void
    {
        if (! in_array($mediaType, self::ACCEPTED_MEDIA_TYPES, true) || $byteSize <= 0 || ! preg_match('/^[a-f0-9]{64}(?:[a-f0-9]{64})?$/i', $contentHash)) {
            throw new FinancialDomainException('E-ATTACHMENT-INVALID', 'Evidence must be a supported image/PDF with positive size and SHA-256/SHA-512 hash.');
        }
        if (! in_array($evidenceType, ['receipt', 'invoice', 'transfer_proof', 'statement', 'cash_count', 'approval', 'policy', 'other'], true)) {
            throw new FinancialDomainException('E-ATTACHMENT-TYPE', 'Evidence type is not in the approved evidence taxonomy.');
        }
    }

    private function findOrCreateAttachment(string $entityId, string $originalFilename, string $mediaType, int $byteSize, string $contentHash, string $storageReference, ?int $actorUserId): Attachment
    {
        $attachment = Attachment::firstOrCreate(
            ['accounting_entity_id' => $entityId, 'content_hash' => $contentHash],
            [
                'original_filename' => $originalFilename,
                'media_type' => $mediaType,
                'byte_size' => $byteSize,
                'storage_reference' => $storageReference,
                'status' => 'active',
                'received_at' => now(),
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ],
        );
        if ($attachment->storage_reference !== $storageReference || $attachment->media_type !== $mediaType || (int) $attachment->byte_size !== $byteSize) {
            throw new FinancialDomainException('E-ATTACHMENT-INTEGRITY', 'A content hash cannot be silently reused with different evidence metadata.');
        }

        return $attachment;
    }
}
