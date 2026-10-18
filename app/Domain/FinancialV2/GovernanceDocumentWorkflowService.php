<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\GovernanceDocument;
use App\Models\FinancialV2\GovernanceDocumentDecision;
use App\Models\FinancialV2\ReversalPrerequisiteProposal;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

final class GovernanceDocumentWorkflowService
{
    private const MEDIA_TYPES = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];

    public function __construct(
        private readonly AuditTrailService $auditTrail,
        private readonly AccountingDateCorrectionGovernancePolicy $governancePolicy,
    ) {}

    public function createDraft(string $entityId, UploadedFile $upload, string $purpose, string $basis, string $idempotencyKey, int $actor): GovernanceDocument
    {
        if (! in_array($upload->getMimeType(), self::MEDIA_TYPES, true) || $upload->getSize() < 1) {
            throw new FinancialDomainException('E-GOV-DOC-FILE', 'Dokumen governance harus PDF atau gambar yang valid dan tidak kosong.');
        }
        $contents = file_get_contents($upload->getRealPath());
        if ($contents === false) throw new FinancialDomainException('E-GOV-DOC-FILE', 'Dokumen governance tidak dapat dibaca.');
        $hash = hash('sha256', $contents);
        $path = 'financial-v2/governance/'.$entityId.'/'.$hash.'.'.strtolower($upload->getClientOriginalExtension() ?: 'bin');
        $createdObject = ! Storage::disk('local')->exists($path);
        if ($createdObject && Storage::disk('local')->put($path, $contents) !== true) {
            throw new FinancialDomainException('E-GOV-DOC-STORAGE', 'Dokumen governance gagal disimpan ke storage canonical.');
        }
        if (! Storage::disk('local')->exists($path)
            || (int) Storage::disk('local')->size($path) !== strlen($contents)
            || ! hash_equals($hash, hash_file('sha256', Storage::disk('local')->path($path)))) {
            if ($createdObject) Storage::disk('local')->delete($path);
            throw new FinancialDomainException('E-GOV-DOC-STORAGE', 'Verifikasi file governance setelah upload gagal.');
        }

        try {
            return DB::transaction(function () use ($entityId, $upload, $purpose, $basis, $idempotencyKey, $actor, $hash, $path): GovernanceDocument {
                $this->lockEntity($entityId);
                $attachment = Attachment::query()->firstOrCreate(
                    ['accounting_entity_id' => $entityId, 'content_hash' => $hash],
                    ['original_filename' => $upload->getClientOriginalName(), 'media_type' => $upload->getMimeType(), 'byte_size' => $upload->getSize(), 'storage_reference' => $path, 'status' => 'active', 'received_at' => now(), 'created_by_user_id' => $actor, 'updated_by_user_id' => $actor],
                );
                if ($attachment->storage_reference !== $path || (int) $attachment->byte_size !== (int) $upload->getSize() || $attachment->media_type !== $upload->getMimeType()) {
                    throw new FinancialDomainException('E-GOV-DOC-INTEGRITY', 'Hash dokumen telah digunakan dengan metadata atau storage reference berbeda.');
                }
                if ($attachment->status !== 'active') throw new FinancialDomainException('E-GOV-DOC-ATTACHMENT', 'Attachment canonical tidak lagi aktif; unggah dokumen pengganti melalui workflow resmi.');
                $predecessor = GovernanceDocument::query()->where('accounting_entity_id', $entityId)->where('purpose', $purpose)->where('status', 'approved')->lockForUpdate()->first();
                $snapshot = ['purpose' => $purpose, 'document_basis' => trim($basis), 'supersedes_document_id' => $predecessor?->id, 'attachment' => ['id' => $attachment->id, 'original_filename' => $attachment->original_filename, 'content_hash' => $attachment->content_hash, 'byte_size' => (int) $attachment->byte_size, 'media_type' => $attachment->media_type, 'storage_reference' => $attachment->storage_reference]];
                $snapshotHash = $this->hash($snapshot);
                $retry = GovernanceDocument::query()->where('accounting_entity_id', $entityId)->where('idempotency_key', $idempotencyKey)->first();
                if ($retry) {
                    if (! hash_equals($retry->snapshot_hash, $snapshotHash)) throw new FinancialDomainException('E-GOV-DOC-IDEMPOTENCY', 'Idempotency key dokumen dipakai untuk substansi berbeda.');
                    return $retry;
                }
                if (GovernanceDocument::query()->where('active_purpose_guard', $entityId)->where('purpose', $purpose)->lockForUpdate()->exists()) {
                    throw new FinancialDomainException('E-GOV-DOC-ACTIVE', 'Masih ada workflow dokumen governance aktif untuk tujuan yang sama.');
                }
                $document = $this->persistDraft(['accounting_entity_id' => $entityId, 'attachment_id' => $attachment->id, 'supersedes_document_id' => $predecessor?->id, 'purpose' => $purpose, 'document_basis' => trim($basis), 'document_snapshot' => $snapshot, 'snapshot_hash' => $snapshotHash, 'idempotency_key' => $idempotencyKey, 'active_purpose_guard' => $entityId, 'status' => 'draft', 'created_by_user_id' => $actor]);
                $this->auditTrail->record($entityId, 'governance_document_draft_created', 'governance_document', $document->id, $document->id, $actor, null, ['purpose' => $purpose, 'snapshot_hash' => $snapshotHash, 'attachment_id' => $attachment->id]);
                return $document;
            }, 3);
        } catch (Throwable $exception) {
            if ($createdObject && ! Attachment::query()->where('accounting_entity_id', $entityId)->where('content_hash', $hash)->exists()) Storage::disk('local')->delete($path);
            throw $exception;
        }
    }

    public function submit(string $entityId, string $id, int $actor): GovernanceDocument
    {
        return $this->transition($entityId, $id, 'draft', 'pending_review', 'submitted', $actor);
    }

    public function approve(string $entityId, string $id, int $actor, ?string $note): GovernanceDocument
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $note): GovernanceDocument {
            $this->lockEntity($entityId);
            $document = $this->locked($entityId, $id, 'pending_review');
            $override = null;
            if ((int) $document->created_by_user_id === $actor) {
                $override = $this->authorizeScopedOverride($actor);
            }
            $this->assertApprovedIntegrity($document, true, false);
            $predecessor = GovernanceDocument::query()->where('accounting_entity_id', $entityId)->where('purpose', $document->purpose)->where('status', 'approved')->whereKeyNot($document->id)->lockForUpdate()->first();
            if (($document->supersedes_document_id ?? null) !== ($predecessor?->id)) throw new FinancialDomainException('E-GOV-DOC-PREDECESSOR', 'Dokumen predecessor berubah sejak draft dibuat. Buat draft pengganti baru.');
            if ($predecessor) {
                $this->persistTransition($predecessor, 'approved', 'superseded', ['active_purpose_guard' => null]);
                $this->decision($predecessor, 'superseded', $actor, 'Digantikan oleh dokumen governance '.$document->id.'.');
                $this->audit($predecessor, 'governance_document_superseded', $actor, $document->id);
            }
            $this->persistTransition($document, 'pending_review', 'approved', ['active_purpose_guard' => null, 'approved_at' => now(), 'approved_by_user_id' => $actor]);
            $decisionNote = $override
                ? trim(($note ? $note.' ' : '').'SUPERADMIN_OVERRIDE: '.AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON.' Fingerprint '.$override['fingerprint'])
                : $note;
            $this->decision($document, 'approved', $actor, $decisionNote);
            $this->auditTrail->record($entityId, 'governance_document_approved', 'governance_document', $document->id, $document->id, $actor, null, [
                'status' => $document->status,
                'snapshot_hash' => $document->snapshot_hash,
                'attachment_id' => $document->attachment_id,
                'segregation_of_duties_exception' => $override,
            ]);
            return $document->fresh('decisions');
        }, 3);
    }

    public function reject(string $entityId, string $id, int $actor, string $note): GovernanceDocument
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $note): GovernanceDocument {
            $this->lockEntity($entityId); $document = $this->locked($entityId, $id, 'pending_review');
            if ((int) $document->created_by_user_id === $actor) throw new FinancialDomainException('E-GOV-DOC-MAKER-CHECKER', 'Maker tidak boleh menolak dokumen governance sendiri.');
            $this->persistTransition($document, 'pending_review', 'rejected', ['active_purpose_guard' => null]);
            $this->decision($document, 'rejected', $actor, $note);
            $this->audit($document, 'governance_document_rejected', $actor);
            return $document->fresh('decisions');
        }, 3);
    }

    private function transition(string $entityId, string $id, string $from, string $to, string $decision, int $actor): GovernanceDocument
    {
        return DB::transaction(function () use ($entityId, $id, $from, $to, $decision, $actor): GovernanceDocument {
            $this->lockEntity($entityId); $document = $this->locked($entityId, $id, $from); $this->assertApprovedIntegrity($document, true, false);
            $this->persistTransition($document, $from, $to, ['submitted_at' => now()]);
            $this->decision($document, $decision, $actor, null);
            $this->audit($document, 'governance_document_'.$decision, $actor);
            return $document->fresh('decisions');
        }, 3);
    }

    public function approvedForUse(string $entityId, string $id, bool $lock = true): array
    {
        $documentQuery = GovernanceDocument::query()->where('accounting_entity_id', $entityId);
        if ($lock) $documentQuery->lockForUpdate();
        $document = $documentQuery->find($id);
        if (! $document || $document->status !== 'approved' || $document->purpose !== 'reversal_master_configuration') {
            throw new FinancialDomainException('E-GOV-DOC-STATE', 'Dokumen governance approved yang berlaku tidak tersedia.');
        }
        $this->assertApprovedIntegrity($document, $lock, true);
        $decisionQuery = GovernanceDocumentDecision::query()->where('governance_document_id', $document->id)->where('decision', 'approved');
        if ($lock) $decisionQuery->lockForUpdate();
        $decision = $decisionQuery->latest('decided_at')->first();
        if (! $decision || (string) $decision->actor_user_id !== (string) $document->approved_by_user_id
            || ! hash_equals((string) $document->snapshot_hash, (string) $decision->snapshot_hash)) {
            throw new FinancialDomainException('E-GOV-DOC-DECISION', 'Keputusan approval governance tidak cocok dengan dokumen canonical.');
        }
        return [$document, $decision, $document->attachment];
    }

    public function cancel(string $entityId, string $id, int $actor, string $reason): GovernanceDocument
    {
        return DB::transaction(function () use ($entityId, $id, $actor, $reason): GovernanceDocument {
            $this->lockEntity($entityId);
            $document = GovernanceDocument::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id);
            if (! $document || ! in_array($document->status, ['draft', 'pending_review', 'approved'], true)) throw new FinancialDomainException('E-GOV-DOC-CANCEL', 'Status dokumen governance tidak aman untuk dibatalkan.');
            if ($document->status === 'approved' && ReversalPrerequisiteProposal::query()->where('governance_document_id', $document->id)->whereIn('status', ['approved', 'materialized'])->exists()) {
                throw new FinancialDomainException('E-GOV-DOC-IN-USE', 'Dokumen approved sedang dipakai proposal yang telah disetujui/materialized dan tidak dapat dibatalkan.');
            }
            $from = $document->status;
            $this->persistTransition($document, $from, 'cancelled', ['active_purpose_guard' => null, 'cancelled_at' => now(), 'cancelled_by_user_id' => $actor, 'cancellation_reason' => trim($reason)]);
            $this->decision($document, 'cancelled', $actor, $reason);
            $this->audit($document, 'governance_document_cancelled', $actor);
            return $document->fresh('decisions');
        }, 3);
    }

    private function assertApprovedIntegrity(GovernanceDocument $document, bool $lock, bool $mustBeApproved): void
    {
        if ($mustBeApproved && $document->status !== 'approved') throw new FinancialDomainException('E-GOV-DOC-STATE', 'Dokumen governance tidak lagi approved.');
        if (! hash_equals($document->snapshot_hash, $this->hash($document->document_snapshot))) throw new FinancialDomainException('E-GOV-DOC-SNAPSHOT', 'Snapshot dokumen governance tidak lagi utuh.');
        $attachmentQuery = Attachment::query()->where('accounting_entity_id', $document->accounting_entity_id);
        if ($lock) $attachmentQuery->lockForUpdate();
        $attachment = $attachmentQuery->find($document->attachment_id);
        $snapshot = $document->document_snapshot['attachment'] ?? [];
        if (! $attachment || $attachment->status !== 'active' || $document->attachment_id !== ($snapshot['id'] ?? null)
            || ! hash_equals((string) ($snapshot['content_hash'] ?? ''), (string) $attachment->content_hash)
            || (int) ($snapshot['byte_size'] ?? 0) !== (int) $attachment->byte_size
            || ($snapshot['original_filename'] ?? null) !== $attachment->original_filename
            || ($snapshot['media_type'] ?? null) !== $attachment->media_type
            || ($snapshot['storage_reference'] ?? null) !== $attachment->storage_reference
            || ! Storage::disk('local')->exists($attachment->storage_reference)
            || (int) Storage::disk('local')->size($attachment->storage_reference) !== (int) $attachment->byte_size
            || Storage::disk('local')->mimeType($attachment->storage_reference) !== $attachment->media_type
            || ! hash_equals((string) $attachment->content_hash, hash_file('sha256', Storage::disk('local')->path($attachment->storage_reference)))) {
            throw new FinancialDomainException('E-GOV-DOC-INTEGRITY', 'File atau metadata dokumen governance tidak lagi cocok dengan snapshot.');
        }
        $document->setRelation('attachment', $attachment);
    }

    private function decision(GovernanceDocument $document, string $decision, int $actor, ?string $note): void
    {
        $id = (string) Str::uuid();
        DB::table((new GovernanceDocumentDecision)->getTable())->insert(['id' => $id, 'accounting_entity_id' => $document->accounting_entity_id, 'governance_document_id' => $document->id, 'decision' => $decision, 'document_snapshot' => json_encode($document->document_snapshot, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'snapshot_hash' => $document->snapshot_hash, 'note' => $note, 'actor_user_id' => $actor, 'decided_at' => now(), 'created_at' => now()]);
    }

    /** @param array<string,mixed> $values */
    private function persistDraft(array $values): GovernanceDocument
    {
        if (($values['status'] ?? null) !== 'draft' || blank($values['active_purpose_guard'] ?? null)) throw new FinancialDomainException('E-GOV-DOC-DRAFT', 'Persistence hanya menerima draft governance canonical.');
        $id = (string) Str::uuid();
        DB::table((new GovernanceDocument)->getTable())->insert(array_merge($values, ['id' => $id, 'document_snapshot' => json_encode($values['document_snapshot'], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES), 'created_at' => now(), 'updated_at' => now()]));
        return GovernanceDocument::query()->findOrFail($id);
    }

    private function audit(GovernanceDocument $document, string $event, int $actor, ?string $correlationId = null): void
    {
        $this->auditTrail->record($document->accounting_entity_id, $event, 'governance_document', $document->id, $correlationId ?? $document->id, $actor, null, ['status' => $document->status, 'snapshot_hash' => $document->snapshot_hash, 'attachment_id' => $document->attachment_id]);
    }

    private function persistTransition(GovernanceDocument $document, string $from, string $to, array $values): void
    {
        $allowed = ['draft' => ['pending_review', 'cancelled'], 'pending_review' => ['approved', 'rejected', 'cancelled'], 'approved' => ['superseded', 'cancelled']];
        if (! in_array($to, $allowed[$from] ?? [], true) || $document->status !== $from) throw new FinancialDomainException('E-GOV-DOC-STATE', "Transisi {$from} ke {$to} tidak diizinkan.");
        $updated = DB::table($document->getTable())->where('id', $document->id)->where('accounting_entity_id', $document->accounting_entity_id)->where('status', $from)->update(array_merge($values, ['status' => $to, 'updated_at' => now()]));
        if ($updated !== 1) throw new FinancialDomainException('E-GOV-DOC-RACE', 'Dokumen governance berubah saat transisi; operasi dibatalkan.');
        $document->refresh();
    }

    private function locked(string $entityId, string $id, string $status): GovernanceDocument
    {
        $document = GovernanceDocument::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id);
        if (! $document || $document->status !== $status) throw new FinancialDomainException('E-GOV-DOC-STATE', 'Status atau Entity dokumen governance tidak sesuai.');
        return $document;
    }

    private function lockEntity(string $entityId): void
    {
        $entity = DB::table('financial_v2_accounting_entities')->where('id', $entityId)->where('status', 'active')->lockForUpdate()->first();
        if (! $entity) throw new FinancialDomainException('E-GOV-DOC-ENTITY', 'Accounting Entity aktif tidak tersedia.');
    }

    /** @return array{mode:string,actor_user_id:int,scope_type:string,scope_id:string,reason:string,fingerprint:string} */
    private function authorizeScopedOverride(int $actor): array
    {
        $user = User::query()->find($actor);

        return $this->governancePolicy->authorizeSuperAdminOverride(
            $actor,
            $actor,
            $actor,
            $user?->hasRole('SuperAdmin') === true,
            null,
            AccountingDateCorrectionGovernancePolicy::BANK_CORRELATION_ID,
            AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,
        );
    }

    private function hash(array $snapshot): string { return hash('sha256', json_encode(Arr::sortRecursive($snapshot), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)); }
}
