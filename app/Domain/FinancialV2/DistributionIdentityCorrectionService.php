<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\DistributionItemIdentityCorrection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DistributionIdentityCorrectionService
{
    public function __construct(private readonly AuditTrailService $audit) {}

    public function correct(string $entityId, string $distributionId, string $itemId, array $input, ?int $actor): DistributionItemIdentityCorrection
    {
        $data = Validator::make($input, [
            'beneficiary_id' => 'required|uuid',
            'reason' => 'required|string|min:10|max:2000',
            'idempotency_key' => 'required|uuid',
        ])->validate();

        return DB::transaction(function () use ($entityId, $distributionId, $itemId, $data, $actor): DistributionItemIdentityCorrection {
            AccountingEntity::whereKey($entityId)->where('status', 'active')->lockForUpdate()->firstOrFail();
            $existing = DistributionItemIdentityCorrection::where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_unless($existing->accounting_entity_id === $entityId && $existing->distribution_id === $distributionId && $existing->distribution_item_id === $itemId, 409);
                return $existing;
            }
            $distribution = Distribution::forEntity($entityId)->with('realization.transaction', 'realization.budgetAllocationVersion.allocation')->lockForUpdate()->findOrFail($distributionId);
            $this->require($distribution->status === 'finalized', 'Koreksi identitas hanya tersedia untuk penyaluran final.');
            $this->require($distribution->realization?->status === 'recorded' && $distribution->realization?->transaction?->status === 'posted', 'Realisasi dan transaksi penyaluran harus sudah tercatat resmi.');
            $this->require($distribution->realization?->budgetAllocationVersion?->allocation?->status === 'approved', 'Allocation penyaluran belum berstatus approved.');
            $item = $distribution->items()->with('latestIdentityCorrection')->lockForUpdate()->findOrFail($itemId);
            $beneficiary = Counterparty::forEntity($entityId)->where('party_type', 'beneficiary')->where('status', 'active')->lockForUpdate()->findOrFail($data['beneficiary_id']);
            $correctedKey = 'master:'.$beneficiary->id;
            $duplicate = $distribution->items()->with('latestIdentityCorrection')->where('id', '<>', $item->id)->get()
                ->contains(fn ($other): bool => $other->effective_recipient_key === $correctedKey);
            $this->require(! $duplicate, 'Penerima pengganti sudah tercatat dalam penyaluran ini.');

            $originalSnapshot = $item->effective_identity_snapshot;
            $originalBeneficiaryId = $item->effective_beneficiary_id;
            $originalKey = $item->effective_recipient_key;
            $this->require($originalKey !== $correctedKey, 'Penerima yang dipilih sudah menjadi identitas efektif item ini.');
            $correctedSnapshot = $this->snapshot($beneficiary);
            $nextNo = (int) $item->identityCorrections()->lockForUpdate()->max('correction_no') + 1;
            $correction = DistributionItemIdentityCorrection::create([
                'accounting_entity_id' => $entityId, 'distribution_id' => $distribution->id, 'distribution_item_id' => $item->id,
                'correction_no' => $nextNo, 'original_beneficiary_id' => $originalBeneficiaryId, 'corrected_beneficiary_id' => $beneficiary->id,
                'original_recipient_key' => $originalKey, 'corrected_recipient_key' => $correctedKey,
                'original_identity_snapshot' => $originalSnapshot, 'corrected_identity_snapshot' => $correctedSnapshot,
                'reason' => trim($data['reason']), 'idempotency_key' => $data['idempotency_key'],
                'corrected_by_user_id' => $actor, 'corrected_at' => now(),
            ]);
            $this->audit->record($entityId, 'distribution.identity_corrected', 'ziswaf_distribution_item', $item->id, (string) Str::uuid(), $actor,
                ['beneficiary_id' => $originalBeneficiaryId, 'recipient_key' => $originalKey, 'identity_snapshot' => $originalSnapshot],
                ['correction_id' => $correction->id, 'beneficiary_id' => $beneficiary->id, 'recipient_key' => $correctedKey, 'identity_snapshot' => $correctedSnapshot, 'reason' => trim($data['reason'])]);
            return $correction;
        });
    }

    private function snapshot(Counterparty $person): array
    {
        return $person->only(['display_name', 'address', 'rt', 'rw', 'rt_coordinator_name', 'contact_reference', 'beneficiary_type', 'status']);
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) throw ValidationException::withMessages(['correction' => $message]);
    }
}
