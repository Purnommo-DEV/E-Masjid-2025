<?php

namespace App\Support;

use RuntimeException;
use Throwable;

final class FinancialV2Migration17RecoveryPlan
{
    public static function execute(string $mode,callable $createPrimary,callable $createLink,callable $postflight):void
    {
        if(!in_array($mode,['normal','recover_link_only'],true))throw new RuntimeException("Mode recovery migration 17 tidak dikenal: {$mode}");
        if($mode==='normal')self::stage('create execution-pair utama',$createPrimary);
        self::stage('create batch-item linkage',$createLink);
        self::stage('postflight lengkap',$postflight);
    }

    private static function stage(string $stage,callable $operation):void
    {
        try{$operation();}
        catch(Throwable $error){throw new RuntimeException("Migration 17 gagal pada tahap {$stage}: {$error->getMessage()}",0,$error);}
    }
}
