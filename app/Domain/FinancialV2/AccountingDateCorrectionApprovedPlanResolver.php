<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingDateCorrectionApproval;
use App\Models\FinancialV2\AccountingDateCorrectionEvaluation;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;

/** Resolves the immutable approval or a later append-only SuperAdmin reauthorization. */
final class AccountingDateCorrectionApprovedPlanResolver
{
    /** @return array{snapshot:array<string,mixed>,fingerprint:string,source:string,actor_user_id:int} */
    public function resolve(AccountingDateCorrectionRequest $request): array
    {
        $approval = AccountingDateCorrectionApproval::query()
            ->where('correction_request_id', $request->id)
            ->where('decision', 'approved')
            ->firstOrFail();

        $reauthorization = AccountingDateCorrectionEvaluation::query()
            ->where('correction_request_id', $request->id)
            ->where('evaluated_at', '>', $approval->decided_at)
            ->latest('evaluated_at')
            ->latest('id')
            ->first();

        if ($reauthorization) {
            return [
                'snapshot' => $reauthorization->evaluation_snapshot,
                'fingerprint' => $reauthorization->condition_fingerprint,
                'source' => 'append_only_reauthorization',
                'actor_user_id' => (int) $reauthorization->evaluated_by_user_id,
            ];
        }

        return [
            'snapshot' => $approval->approval_snapshot,
            'fingerprint' => $approval->condition_fingerprint,
            'source' => 'governance_approval',
            'actor_user_id' => (int) $approval->decided_by_user_id,
        ];
    }
}
