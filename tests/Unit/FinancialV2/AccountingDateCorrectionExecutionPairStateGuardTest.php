<?php

namespace Tests\Unit\FinancialV2;

use App\Domain\FinancialV2\AccountingDateCorrectionExecutionPairStateGuard;
use DomainException;
use PHPUnit\Framework\TestCase;

final class AccountingDateCorrectionExecutionPairStateGuardTest extends TestCase
{
    public function test_state_machine_distinguishes_staging_from_execution_readiness():void
    {
        self::assertSame('validated',AccountingDateCorrectionExecutionPairStateGuard::transition('prepared','validated',fn()=>'validated'));
        self::assertSame('staged',AccountingDateCorrectionExecutionPairStateGuard::transition('validated','staged',fn()=>'staged'));
        self::assertSame('execution_ready',AccountingDateCorrectionExecutionPairStateGuard::transition('staged','execution_ready',fn()=>'execution_ready'));
    }

    public function test_invalid_shortcuts_and_public_status_changes_are_rejected():void
    {
        foreach([['prepared','staged'],['validated','execution_ready'],['staged','executing'],['completed','executing']]as[$from,$to]){
            try{AccountingDateCorrectionExecutionPairStateGuard::transition($from,$to,fn()=>null);self::fail("{$from} -> {$to} must fail");}
            catch(DomainException){self::assertTrue(true);}
        }
        $this->expectException(DomainException::class);
        AccountingDateCorrectionExecutionPairStateGuard::assertTransition('prepared','validated');
    }
}
