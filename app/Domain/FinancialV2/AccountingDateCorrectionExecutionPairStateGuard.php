<?php

namespace App\Domain\FinancialV2;

use Closure;
use DomainException;

final class AccountingDateCorrectionExecutionPairStateGuard
{
    private static array $transition=[];

    public static function transition(string $from,string $to,Closure $callback):mixed
    {
        $allowed=[
            'prepared'=>['validated','invalidated','cancelled'],
            'validated'=>['staged','invalidated','cancelled'],
            'staged'=>['execution_ready','invalidated','cancelled'],
            'execution_ready'=>['executing','invalidated'],
            'executing'=>['completed','failed'],
        ];
        if(!in_array($to,$allowed[$from]??[],true))throw new DomainException("Transisi execution pair {$from} ke {$to} tidak diizinkan.");
        if(self::$transition!==[])throw new DomainException('Transisi execution pair tidak dapat dinesting.');
        self::$transition=[$from,$to];
        try{return$callback();}finally{self::$transition=[];}
    }

    public static function assertTransition(string $from,string $to):void
    {
        if(self::$transition!==[$from,$to])throw new DomainException('Status execution pair hanya dapat diubah melalui service canonical.');
    }
}
