<?php

namespace Tests\Unit\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionPolicyException;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Models\FinancialV2\Counterparty;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class AccountingDateCorrectionPolicyExceptionTest extends TestCase
{
    public function test_historical_counterparty_is_preserved_and_fingerprinted():void
    {
        $counterparty=new Counterparty(['status'=>'archived']);$counterparty->id='cp-1';
        $transaction=new FinancialTransaction(['gross_amount'=>'375.00','counterparty_id'=>'cp-1']);
        $transaction->id='a2eefd9f-9976-4348-b42f-7b66d50879a6';
        $transaction->setRelation('counterparty',$counterparty);$transaction->setRelation('splits',new Collection());$transaction->setRelation('realization',null);
        $line=new JournalLine(['counterparty_id'=>'cp-1']);
        $journal=new Journal(['posting_rule_version_id'=>'rule-1']);$journal->id='journal-1';$journal->setRelation('lines',new Collection([$line]));
        $service=new AccountingDateCorrectionPolicyException;
        $proposal=$service->propose($transaction,$journal,'2026-09-27',['attachment_id'=>'evidence-1','evidence_type'=>'invoice','content_hash'=>str_repeat('a',64),'attachment_status'=>'active','link_status'=>'active'],1,'request-1');
        self::assertSame('cp-1',$proposal['counterparty']['replacement_id']);
        self::assertSame('historical_identity_preserved',$proposal['counterparty']['mode']);
        $service->assertMatches($proposal,$transaction,$journal,'2026-09-27');
    }

    public function test_missing_counterparty_remains_rejected_outside_verified_bni_interest():void
    {
        $transaction=new FinancialTransaction(['gross_amount'=>'10.00','counterparty_id'=>null]);$transaction->id='a2eec515-6015-4c9d-8344-2deb3819385a';
        $transaction->setRelation('counterparty',null);$transaction->setRelation('splits',new Collection());$transaction->setRelation('realization',null);
        $journal=new Journal(['posting_rule_version_id'=>'rule-1']);$journal->id='journal-1';$journal->setRelation('lines',new Collection());
        $this->expectException(FinancialDomainException::class);
        (new AccountingDateCorrectionPolicyException)->propose($transaction,$journal,'2026-09-13',['attachment_id'=>'evidence-1','evidence_type'=>'invoice'],1,'request-1');
    }

    public function test_bni_interest_requires_statement_and_maps_only_to_governed_bank_counterparty():void
    {
        $transaction=new FinancialTransaction(['gross_amount'=>'4300.00','counterparty_id'=>null]);$transaction->id=AccountingDateCorrectionPolicyException::BNI_INTEREST_TRANSACTION;
        $transaction->setRelation('counterparty',null);$transaction->setRelation('splits',new Collection());$transaction->setRelation('realization',null);
        $journal=new Journal(['posting_rule_version_id'=>'rule-1']);$journal->id='journal-1';$journal->setRelation('lines',new Collection());
        $proposal=(new AccountingDateCorrectionPolicyException)->propose($transaction,$journal,'2026-09-30',['attachment_id'=>'evidence-1','evidence_type'=>'statement','content_hash'=>str_repeat('b',64),'attachment_status'=>'active','link_status'=>'active'],1,'request-1');
        self::assertSame(AccountingDateCorrectionPolicyException::BNI_COUNTERPARTY,$proposal['counterparty']['replacement_id']);
        self::assertSame('verified_bni_bank_counterparty',$proposal['counterparty']['mode']);
    }
}
