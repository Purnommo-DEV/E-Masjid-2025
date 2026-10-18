<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use Illuminate\Support\Arr;

/**
 * Immutable policy-exception proposal. Persistence occurs inside the approved
 * Correction Request snapshot; this service never mutates Allocation, policy,
 * counterparty, or a posted financial fact.
 */
final class AccountingDateCorrectionPolicyException
{
    public const BNI_INTEREST_TRANSACTION = 'a2ef0da6-2662-4b31-a8d9-8b79c2c084d3';
    public const BNI_COUNTERPARTY = 'a2bc44ef-577c-4875-8276-ae9bc896b490';

    /** @param array<string,mixed> $evidence @return array<string,mixed> */
    public function propose(FinancialTransaction $transaction, Journal $journal, string $targetDate, array $evidence, int $actor, ?string $requestId): array
    {
        if (! in_array($transaction->id, AccountingDateCorrectionGovernancePolicy::OVERRIDE_TRANSACTION_IDS, true)) {
            throw new FinancialDomainException('E-CORRECTION-POLICY-SCOPE', 'Policy exception berada di luar sembilan transaksi yang disahkan.');
        }
        if ($actor <= 0 || ! $requestId) {
            throw new FinancialDomainException('E-CORRECTION-POLICY-APPROVAL', 'Policy exception hanya dapat diikat pada Correction Request dan actor aktual.');
        }
        $transaction->loadMissing(['splits', 'counterparty', 'realization.budgetAllocationVersion.allocation', 'realization.budgetAllocationVersion.fundings']);
        $journal->loadMissing('lines');
        $journalCounterparties=$journal->lines->pluck('counterparty_id')->filter()->unique()->values()->all();
        $replacementCounterparty=$transaction->counterparty_id;
        $counterpartyMode='historical_identity_preserved';
        if($transaction->counterparty_id===null){
            if($transaction->id!==self::BNI_INTEREST_TRANSACTION||!DecimalAmount::equals($transaction->gross_amount,'4300.00')||($evidence['evidence_type']??null)!=='statement'){
                throw new FinancialDomainException('E-CORRECTION-COUNTERPARTY-MISSING','Counterparty missing hanya diizinkan untuk penerimaan bunga BNI yang dibuktikan rekening koran.');
            }
            $replacementCounterparty=self::BNI_COUNTERPARTY;
            $counterpartyMode='verified_bni_bank_counterparty';
        }elseif($journalCounterparties!==[]&&(count($journalCounterparties)!==1||$journalCounterparties[0]!==$transaction->counterparty_id)){
            throw new FinancialDomainException('E-CORRECTION-COUNTERPARTY-DRIFT','Identitas counterparty original tidak konsisten dengan jurnal posted.');
        }

        $realization=$transaction->realization;
        $version=$realization?->budgetAllocationVersion;
        $allocation=$version?->allocation;
        if($realization&&(!$version||!$allocation||$version->status!=='approved'||$allocation->status!=='approved')){
            throw new FinancialDomainException('E-CORRECTION-ALLOCATION-UNAPPROVED','Exception tidak boleh menggantikan approval Allocation yang tidak sah.');
        }
        $splitFunds=$transaction->splits->pluck('fund_id')->filter()->unique()->sort()->values()->all();
        $allocationFunds=$version?->fundings->pluck('fund_id')->filter()->unique()->sort()->values()->all()??[];
        if($version&&$allocationFunds===[]&&$allocation?->fund_id)$allocationFunds=[$allocation->fund_id];
        if($version&&$splitFunds!==$allocationFunds)throw new FinancialDomainException('E-CORRECTION-ALLOCATION-DIMENSION','Fund original tidak cocok dengan funding Allocation yang disetujui.');

        $facts=[
            'scope'=>'accounting_date_correction','original_transaction_id'=>$transaction->id,'correction_request_id'=>$requestId,
            'target_accounting_date'=>$targetDate,'original_journal_id'=>$journal->id,'original_posting_rule_version_id'=>$journal->posting_rule_version_id,
            'gross_amount'=>DecimalAmount::normalize($transaction->gross_amount),'fund_ids'=>$splitFunds,
            'program_ids'=>$transaction->splits->pluck('program_id')->filter()->unique()->sort()->values()->all(),
            'allocation_version_id'=>$version?->id,'allocation_effective_from'=>$version?->effective_from?->toDateString(),
            'counterparty'=>['mode'=>$counterpartyMode,'original_id'=>$transaction->counterparty_id,'replacement_id'=>$replacementCounterparty,'original_status'=>$transaction->counterparty?->status??'missing'],
            'evidence'=>Arr::only($evidence,['attachment_id','evidence_type','content_hash','attachment_status','link_status']),
            'reason'=>AccountingDateCorrectionGovernancePolicy::OVERRIDE_REASON,'proposed_by_user_id'=>$actor,
        ];
        return $facts+['fingerprint'=>$this->hash($facts)];
    }

    /** @param array<string,mixed> $exception */
    public function assertMatches(array $exception, FinancialTransaction $transaction, Journal $journal, string $targetDate): void
    {
        $fingerprint=(string)($exception['fingerprint']??'');$facts=Arr::except($exception,['fingerprint']);
        if($fingerprint===''||!hash_equals($fingerprint,$this->hash($facts))||($exception['original_transaction_id']??null)!==$transaction->id||($exception['original_journal_id']??null)!==$journal->id||($exception['target_accounting_date']??null)!==$targetDate){
            throw new FinancialDomainException('E-CORRECTION-POLICY-FINGERPRINT','Policy exception tidak cocok dengan fakta correction plan.');
        }
    }

    private function hash(array $facts):string{return hash('sha256',json_encode(Arr::sortRecursive($facts),JSON_THROW_ON_ERROR|JSON_UNESCAPED_SLASHES));}
}
