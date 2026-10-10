<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Creates a reviewable draft successor with an exact copy of its predecessor rules. */
final class FundPolicySuccessorService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @param array<string, mixed> $data */
    public function create(string $entityId, string $predecessorId, array $data, ?int $actorUserId = null): FundPolicyVersion
    {
        return DB::transaction(function () use ($entityId, $predecessorId, $data, $actorUserId): FundPolicyVersion {
            $predecessor = FundPolicyVersion::query()
                ->where('accounting_entity_id', $entityId)
                ->with('rules')
                ->lockForUpdate()
                ->findOrFail($predecessorId);
            Fund::query()->whereKey($predecessor->fund_id)->lockForUpdate()->firstOrFail();

            if ($predecessor->status !== 'effective' || ! $predecessor->approved_at) {
                throw new FinancialDomainException('E-FUND-POLICY-SUCCESSOR-STATUS', 'Draft penerus hanya dapat dibuat dari Fund Policy effective terakhir. Versi historis atau superseded harus dilanjutkan melalui penerus yang sudah ada.');
            }
            $effectiveFrom = CarbonImmutable::parse($data['effective_from'])->startOfDay();
            if ($effectiveFrom->lte($predecessor->effective_from)) {
                throw new FinancialDomainException('E-FUND-POLICY-SUCCESSOR-DATE', 'Tanggal successor harus setelah tanggal mulai predecessor.');
            }
            if ($predecessor->effective_to && ! $effectiveFrom->equalTo($predecessor->effective_to->copy()->addDay())) {
                throw new FinancialDomainException('E-FUND-POLICY-SUCCESSOR-DATE', 'Successor harus mulai tepat satu hari setelah predecessor berakhir.');
            }

            $laterVersions = FundPolicyVersion::query()
                ->where('fund_id', $predecessor->fund_id)
                ->where('version_no', '>', $predecessor->version_no)
                ->with('rules')
                ->orderBy('version_no')
                ->lockForUpdate()
                ->get();
            $existingSuccessor = $laterVersions->first();
            $canReuseEmptyDraft = $laterVersions->count() === 1
                && $existingSuccessor->version_no === $predecessor->version_no + 1
                && $existingSuccessor->status === 'draft'
                && $existingSuccessor->rules->isEmpty();
            if ($laterVersions->isNotEmpty() && ! $canReuseEmptyDraft) {
                throw new FinancialDomainException('E-FUND-POLICY-SUCCESSOR-EXISTS', 'Fund Policy ini sudah memiliki versi setelah predecessor yang dipilih.');
            }

            if ($existingSuccessor && ! $existingSuccessor->effective_from->equalTo($effectiveFrom)) {
                throw new FinancialDomainException('E-FUND-POLICY-SUCCESSOR-DATE', 'Draft successor kosong memiliki tanggal mulai berbeda. Hapus melalui lifecycle resmi sebelum membuat successor kembali.');
            }

            $successorAttributes = [
                'effective_from' => $effectiveFrom->toDateString(),
                'effective_to' => $data['effective_to'] ?? null,
                'policy_document_ref' => $data['policy_document_ref'],
                'allowed_matrix_ref' => $data['allowed_matrix_ref'] ?? null,
                'exception_approval_level' => $data['exception_approval_level'],
                'status' => 'draft',
                'updated_by_user_id' => $actorUserId,
            ];
            if ($existingSuccessor) {
                $existingSuccessor->update($successorAttributes);
                $successor = $existingSuccessor->fresh('rules');
            } else {
                $successor = FundPolicyVersion::query()->create($successorAttributes + [
                    'accounting_entity_id' => $entityId,
                    'fund_id' => $predecessor->fund_id,
                    'version_no' => $predecessor->version_no + 1,
                    'created_by_user_id' => $actorUserId,
                ]);
            }

            foreach ($predecessor->rules as $rule) {
                FundPolicyRule::query()->create($rule->only(FundPolicyRuleSet::cloneableFields()) + [
                    'accounting_entity_id' => $entityId,
                    'fund_policy_version_id' => $successor->id,
                ]);
            }

            $sourceSignature = FundPolicyRuleSet::signature($predecessor->rules);
            $successor->load('rules');
            $targetSignature = FundPolicyRuleSet::signature($successor->rules);
            if ($targetSignature !== $sourceSignature) {
                throw new FinancialDomainException('E-FUND-POLICY-SUCCESSOR-CLONE', 'Clone rule successor tidak lengkap; seluruh pembuatan dibatalkan.');
            }

            $this->auditTrail->record(
                $entityId,
                'fund_policy_successor_cloned',
                'fund_policy_version',
                $successor->id,
                (string) Str::uuid(),
                $actorUserId,
                ['predecessor_id' => $predecessor->id, 'rules' => $sourceSignature],
                [
                    'status' => 'draft',
                    'predecessor_id' => $predecessor->id,
                    'reused_empty_draft' => $existingSuccessor !== null,
                    'rules' => $targetSignature,
                ],
            );

            return $successor;
        }, 3);
    }
}
