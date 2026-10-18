<?php
namespace Tests\Integration\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionBatchWorkflowService;
use App\Domain\FinancialV2\AccountingDateCorrectionExecutionOrchestrator;
use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Models\FinancialV2\AccountingDateCorrectionBatch;
use App\Models\FinancialV2\AccountingDateCorrectionBatchApproval;
use App\Models\FinancialV2\AccountingDateCorrectionBatchItem;
use App\Models\FinancialV2\AccountingDateCorrectionRequest;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Support\UatFinancialFixture;
use Tests\TestCase;

final class AccountingDateCorrectionBatchWorkflowTest extends TestCase
{
    use DatabaseTransactions;
    protected function setUp():void{parent::setUp();Storage::fake('local');self::assertSame('mrj_test_db',DB::selectOne('SELECT DATABASE() name')->name);self::assertStringStartsWith('8.4.9',(string)DB::selectOne('SELECT VERSION() version')->version);self::assertGreaterThan(0,DB::connection()->transactionLevel());}

    public function test_draft_captures_all_five_authoritative_members_deterministically_and_remains_non_financial():void
    {$f=$this->fixture();$s=app(AccountingDateCorrectionBatchWorkflowService::class);$before=$this->facts();$b=$s->createDraft($f['entity'],$f['correlation'],$f['target'],(string)Str::uuid(),$f['actors'][0]);self::assertCount(5,$b->items);self::assertSame($f['ids'], $b->items->pluck('transaction_id')->sort()->values()->all());self::assertFalse($s->executionReadiness($b)['execution_ready']);self::assertSame($before,$this->facts());self::assertSame(0,AccountingDateCorrectionRequest::count());}

    public function test_idempotency_active_guard_maker_checker_and_governance_only_approval():void
    {$f=$this->fixture();$s=app(AccountingDateCorrectionBatchWorkflowService::class);$key=(string)Str::uuid();$b=$s->createDraft($f['entity'],$f['correlation'],$f['target'],$key,$f['actors'][0]);self::assertSame($b->id,$s->createDraft($f['entity'],$f['correlation'],$f['target'],$key,$f['actors'][0])->id);try{$s->createDraft($f['entity'],$f['correlation'],$f['target'],(string)Str::uuid(),$f['actors'][0]);self::fail('active guard');}catch(FinancialDomainException$e){self::assertSame('E-ACCT-BATCH-ACTIVE',$e->failureCode);}try{$s->submit($f['entity'],$b->id,$f['actors'][1]);self::fail('maker submit');}catch(FinancialDomainException$e){self::assertSame('E-ACCT-BATCH-MAKER',$e->failureCode);}$s->submit($f['entity'],$b->id,$f['actors'][0]);try{$s->approve($f['entity'],$b->id,$f['actors'][0]);self::fail('self approval');}catch(FinancialDomainException$e){self::assertSame('E-ACCT-BATCH-MAKER-CHECKER',$e->failureCode);}$b=$s->approve($f['entity'],$b->id,$f['actors'][1]);self::assertSame('approved',$b->status);self::assertFalse($s->executionReadiness($b)['execution_ready']);self::assertSame(0,AccountingDateCorrectionRequest::count());}

    public function test_member_drift_invalidates_fingerprint_and_rolls_back_transition_decision_and_audit():void
    {$f=$this->fixture();$s=app(AccountingDateCorrectionBatchWorkflowService::class);$b=$s->createDraft($f['entity'],$f['correlation'],$f['target'],(string)Str::uuid(),$f['actors'][0]);$dec=AccountingDateCorrectionBatchApproval::count();DB::table('financial_v2_transactions')->where('id',$f['ids'][0])->update(['gross_amount'=>'999.00']);try{$s->submit($f['entity'],$b->id,$f['actors'][0]);self::fail('drift');}catch(FinancialDomainException$e){self::assertSame('E-ACCT-BATCH-DRIFT',$e->failureCode);}self::assertSame('draft',$b->fresh()->status);self::assertSame($dec,AccountingDateCorrectionBatchApproval::count());self::assertSame(5,$b->items()->count());}

    public function test_authoritative_group_with_missing_or_extra_member_is_rejected_before_draft_persistence():void
    {
        $service=app(AccountingDateCorrectionBatchWorkflowService::class);
        foreach([4,6] as$count){
            $fixture=$this->fixture($count);
            $before=AccountingDateCorrectionBatch::count();
            try{$service->createDraft($fixture['entity'],$fixture['correlation'],$fixture['target'],(string)Str::uuid(),$fixture['actors'][0]);self::fail("{$count} members must fail");}
            catch(FinancialDomainException$e){self::assertSame('E-ACCT-BATCH-MEMBERSHIP',$e->failureCode);}
            self::assertSame($before,AccountingDateCorrectionBatch::count());
        }
    }

    public function test_models_are_private_and_reject_mutation():void{foreach([AccountingDateCorrectionBatch::class,AccountingDateCorrectionBatchItem::class,AccountingDateCorrectionBatchApproval::class]as$m){try{(new$m)->forceFill(['id'=>(string)Str::uuid()])->save();self::fail('public model write');}catch(DomainException){self::assertTrue(true);}}}

    public function test_execution_orchestrator_revalidates_approved_batch_and_remains_fail_closed_without_writes():void
    {
        $fixture=$this->fixture();
        $workflow=app(AccountingDateCorrectionBatchWorkflowService::class);
        $batch=$workflow->createDraft($fixture['entity'],$fixture['correlation'],$fixture['target'],(string)Str::uuid(),$fixture['actors'][0]);
        $workflow->submit($fixture['entity'],$batch->id,$fixture['actors'][0]);
        $batch=$workflow->approve($fixture['entity'],$batch->id,$fixture['actors'][1]);
        $before=$this->facts();

        $executor=app(AccountingDateCorrectionExecutionOrchestrator::class);
        $assessment=$executor->assessBatch($fixture['entity'],$batch->id,$fixture['actors'][2]);
        $codes=collect($assessment['blockers'])->pluck('code')->all();
        self::assertFalse($assessment['execution_ready']);
        self::assertContains('THREE_DISTINCT_UNRESOLVED',$codes);
        self::assertNotEmpty(array_intersect(['BATCH_EXECUTION_PAIR_SCHEMA_PENDING','BATCH_EXECUTION_LINEAGE_INCOMPLETE'],$codes));

        try{$executor->executeBatch($fixture['entity'],$batch->id,(string)Str::uuid(),$fixture['actors'][2]);self::fail('executor must remain blocked');}
        catch(FinancialDomainException$exception){self::assertSame('E-ACCOUNTING-CORRECTION-BATCH-NOT-EXECUTION-READY',$exception->failureCode);}

        self::assertSame('approved',$batch->fresh()->status);
        self::assertSame($before,$this->facts());
        self::assertSame(0,AccountingDateCorrectionRequest::count());
    }

    private function fixture(int$count=5):array{$c=UatFinancialFixture::context();$actors=User::factory()->count(3)->create()->pluck('id')->map(fn($x)=>(int)$x)->all();$corr=(string)Str::uuid();$l=app(FinancialTransactionLifecycleService::class);$ids=[];$definitions=[['PAY','2.50'],['RCV','4.30'],['PAY','0.86'],['PAY','11.00'],['PAY','7.50'],['PAY','1.25']];foreach(array_slice($definitions,0,$count)as[$type,$amount]){$input=['accounting_entity_id'=>$c['entity']->id,'transaction_type_id'=>$type==='PAY'?$c['paymentType']->id:$c['receiptType']->id,'business_date'=>now()->toDateString(),'accounting_date'=>now()->toDateString(),'gross_amount'=>$amount,'source_reference'=>'BATCH-'.Str::uuid(),'idempotency_key'=>'batch-'.Str::uuid(),'correlation_id'=>$corr,'primary_financial_account_id'=>$c['accountA']->id,'category_id'=>$type==='PAY'?$c['paymentCategory']->id:$c['receiptCategory']->id,'description'=>'Batch test'];$split=[['account_id'=>$type==='PAY'?$c['expense']->id:$c['revenue']->id,'split_amount'=>$amount,'fund_id'=>$c['fund']->id,'program_id'=>$c['program']->id]];$tx=$type==='PAY'?$l->createPayment($input+['counterparty_id'=>$c['supplier']->id],$split,$actors[0]):$l->createReceipt($input,$split,$actors[0]);$ids[]=$tx->id;}sort($ids);return['entity'=>$c['entity']->id,'correlation'=>$corr,'target'=>now()->subDay()->toDateString(),'actors'=>$actors,'ids'=>$ids];}
    private function facts():array{return[FinancialTransaction::count(),Journal::count(),JournalLine::count(),LedgerEntry::count(),FundRealization::count(),Distribution::count(),AccountingDateCorrectionRequest::count()];}
}
