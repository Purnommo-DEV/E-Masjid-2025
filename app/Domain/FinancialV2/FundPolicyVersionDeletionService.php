<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class FundPolicyVersionDeletionService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @return array{status:string,can_delete:bool,message:string,transaction_count:int,journal_line_count:int} */
    public function usage(FundPolicyVersion $version): array
    {
        $transactions = DB::table('financial_v2_transactions')
            ->where('policy_version_ref', $version->id)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');
        $journalLineCount = DB::table('financial_v2_journal_lines')->where('policy_version_ref', $version->id)->count();
        $transactionCount = (int) $transactions->sum();

        if ($journalLineCount > 0 || (int) ($transactions['posted'] ?? 0) > 0 || (int) ($transactions['reversed'] ?? 0) > 0) {
            return $this->result('USED_IN_POSTED', false, 'Versi digunakan — tidak dapat dihapus', $transactionCount, $journalLineCount);
        }
        foreach (['approved' => 'USED_IN_APPROVED', 'verified' => 'USED_IN_APPROVED', 'submitted' => 'USED_IN_SUBMITTED', 'draft' => 'USED_IN_DRAFT_ONLY'] as $state => $status) {
            if ((int) ($transactions[$state] ?? 0) > 0) {
                return $this->result($status, false, 'Versi digunakan — tidak dapat dihapus', $transactionCount, $journalLineCount);
            }
        }
        if ($transactionCount > 0) {
            return $this->result('USED_IN_WORKFLOW', false, 'Versi digunakan — tidak dapat dihapus', $transactionCount, $journalLineCount);
        }
        if ($version->status === 'effective') {
            return $this->result('ACTIVE_POLICY', false, 'Versi berlaku — tidak dapat dihapus', 0, 0);
        }
        if ($version->status !== 'draft' && ! $this->hasReplacementCoverage($version)) {
            return $this->result('ONLY_VALID_POLICY', false, 'Satu-satunya versi untuk periode ini — tidak dapat dihapus', 0, 0);
        }

        return $this->result('UNUSED', true, 'Belum digunakan', 0, 0);
    }

    public function delete(string $entityId, string $versionId, ?int $actorUserId = null, ?string $origin = null): void
    {
        DB::transaction(function () use ($entityId, $versionId, $actorUserId, $origin): void {
            $version = FundPolicyVersion::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->findOrFail($versionId);
            $usage = $this->usage($version);
            if (! $usage['can_delete']) {
                $used = str_starts_with($usage['status'], 'USED_');
                throw new FinancialDomainException(
                    $used ? 'E-FUND-POLICY-VERSION-USED' : 'E-FUND-POLICY-VERSION-PROTECTED',
                    $used
                        ? 'Versi aturan dana tidak dapat dihapus karena sudah digunakan dalam pencatatan keuangan.'
                        : 'Versi aturan dana tidak dapat dihapus karena masih diperlukan oleh periode kebijakan.',
                );
            }

            $before = $version->only(['fund_id', 'version_no', 'effective_from', 'effective_to', 'policy_document_ref', 'status']);
            FundPolicyRule::query()->where('fund_policy_version_id', $version->id)->delete();
            $version->delete();
            $this->auditTrail->record($entityId, 'fund_policy_version_deleted', 'fund_policy_version', $versionId, (string) Str::uuid(), $actorUserId, $before, ['deleted' => true, 'usage_status' => 'UNUSED', 'origin' => $origin]);
        }, 3);
    }

    private function hasReplacementCoverage(FundPolicyVersion $version): bool
    {
        return FundPolicyVersion::query()
            ->where('fund_id', $version->fund_id)
            ->whereKeyNot($version->id)
            ->where(fn ($query) => $query->where('status', 'effective')->orWhere(fn ($historical) => $historical->where('status', 'superseded')->whereNotNull('approved_at')))
            ->where('effective_from', '<=', $version->effective_from)
            ->when(
                $version->effective_to,
                fn ($query) => $query->where(fn ($range) => $range->whereNull('effective_to')->orWhere('effective_to', '>=', $version->effective_to)),
                fn ($query) => $query->whereNull('effective_to'),
            )
            ->exists();
    }

    /** @return array{status:string,can_delete:bool,message:string,transaction_count:int,journal_line_count:int} */
    private function result(string $status, bool $canDelete, string $message, int $transactionCount, int $journalLineCount): array
    {
        return compact('status', 'message') + ['can_delete' => $canDelete, 'transaction_count' => $transactionCount, 'journal_line_count' => $journalLineCount];
    }
}
