<?php

use App\Support\FinancialV2Migration17RecoveryPlan as RecoveryPlan;
use App\Support\FinancialV2MigrationSchemaGuard as Guard;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private const MIGRATION='2026_10_17_000001_create_financial_v2_accounting_date_correction_execution_pairs';
    private const TARGET_DATABASE='mrj_prod_new';
    private const TARGET_HOST='DESKTOP-O10NAPB';
    private const TARGET_VERSION='8.4.9';
    private const PAIRS='financial_v2_accounting_date_correction_execution_pairs';
    private const LINKS='financial_v2_acct_date_corr_batch_item_pairs';

    public function up():void
    {
        $plan=$this->preflight();
        $definitions=$plan['definitions'];
        RecoveryPlan::execute(
            $plan['mode'],
            fn()=>$this->createPairs($definitions),
            fn()=>$this->createLinks($definitions),
            fn()=>$this->postflight($definitions),
        );
    }

    public function down():void
    {
        throw new RuntimeException('Execution pair dan lineage batch item adalah histori append-only dan tidak boleh dihapus otomatis.');
    }

    private function preflight():array
    {
        Guard::assertDatabaseIdentity(self::TARGET_DATABASE,self::TARGET_HOST,self::TARGET_VERSION);
        Guard::assertMigrationAbsent(self::MIGRATION);
        Guard::assertMigrations([
            '2026_10_12_000001_create_financial_v2_accounting_date_correction_workflow',
            '2026_10_13_000001_harden_financial_v2_reversal_configuration_workflow',
            '2026_10_14_000001_create_financial_v2_reversal_prerequisite_governance',
            '2026_10_15_000001_create_financial_v2_replacement_realization_workflow',
            '2026_10_16_000001_create_financial_v2_accounting_date_correction_batches',
        ]);

        $parents=['financial_v2_accounting_entities','financial_v2_accounting_date_correction_requests','financial_v2_transactions','financial_v2_journals','financial_v2_reversal_configuration_requests','financial_v2_reason_codes','financial_v2_accounting_date_correction_batches','financial_v2_accounting_date_correction_batch_items'];
        $definitions=[];
        foreach($parents as$parent)$definitions[$parent]=Guard::uuidParent($parent);
        Guard::assertUsersId();
        Guard::assertIndex('financial_v2_journals','fv2_journal_reversal_once_uq',true,['reversal_of_journal_id']);
        Guard::assertForeign('financial_v2_journals','fv2_journal_reversal_fk',['reversal_of_journal_id'],'financial_v2_journals',['id'],'NO ACTION');
        Guard::assertIndex('financial_v2_accounting_date_correction_requests','fv2_acct_date_corr_active_uq',true,['active_transaction_guard']);
        Guard::assertForeign('financial_v2_accounting_date_correction_requests','fv2_acct_date_corr_active_fk',['active_transaction_guard'],'financial_v2_transactions',['id'],'RESTRICT');
        Guard::assertIndex('financial_v2_accounting_date_correction_batches','fv2_acct_batch_idem_uq',true,['accounting_entity_id','idempotency_key']);
        Guard::assertIndex('financial_v2_accounting_date_correction_batches','fv2_acct_batch_active_uq',true,['active_group_guard']);
        Guard::assertIndex('financial_v2_accounting_date_correction_batch_items','fv2_acct_batch_item_tx_uq',true,['batch_id','transaction_id']);
        Guard::assertIndex('financial_v2_accounting_date_correction_batch_items','fv2_acct_batch_item_no_uq',true,['batch_id','item_no']);
        Guard::assertForeign('financial_v2_accounting_date_correction_batch_items','fv2_acct_batch_item_batch_fk',['batch_id'],'financial_v2_accounting_date_correction_batches',['id'],'RESTRICT');

        $mode=Guard::classifyTwoTableRecovery(Guard::targetTablesPresent([self::PAIRS,self::LINKS]),self::PAIRS,self::LINKS);
        if($mode==='recover_link_only')$this->assertPairsSchema($definitions);

        return['mode'=>$mode,'definitions'=>$definitions];
    }

    private function createPairs(array $d):void
    {
        Schema::create(self::PAIRS,function(Blueprint$table)use($d):void{
            $table->charset=$d['financial_v2_accounting_entities']['charset'];$table->collation=$d['financial_v2_accounting_entities']['collation'];
            $this->uuid($table,'id',$d['financial_v2_accounting_entities'])->primary();
            $this->uuid($table,'accounting_entity_id',$d['financial_v2_accounting_entities']);
            $this->uuid($table,'correction_request_id',$d['financial_v2_accounting_date_correction_requests']);
            $this->uuid($table,'active_original_guard',$d['financial_v2_transactions'])->nullable();
            $this->uuid($table,'original_transaction_id',$d['financial_v2_transactions']);
            $this->uuid($table,'original_journal_id',$d['financial_v2_journals']);
            $this->uuid($table,'reversal_configuration_request_id',$d['financial_v2_reversal_configuration_requests'])->nullable();
            $this->uuid($table,'reason_code_id',$d['financial_v2_reason_codes'])->nullable();
            $this->uuid($table,'reversal_transaction_id',$d['financial_v2_transactions'])->nullable();
            $this->uuid($table,'replacement_transaction_id',$d['financial_v2_transactions'])->nullable();
            $table->date('target_accounting_date');
            $table->enum('status',['prepared','validated','staged','execution_ready','executing','completed','invalidated','failed','cancelled'])->default('prepared');
            $table->json('approved_plan_snapshot');$table->char('approved_plan_fingerprint',64);$table->char('source_fingerprint',64);
            $table->json('validation_snapshot')->nullable();$table->char('validation_fingerprint',64)->nullable();$table->json('blockers')->nullable();
            $table->string('idempotency_key',120);$table->unsignedBigInteger('prepared_by_user_id');$table->unsignedBigInteger('validated_by_user_id')->nullable();
            $table->timestamp('validated_at')->nullable();$table->timestamp('staged_at')->nullable();$table->timestamp('execution_ready_at')->nullable();$table->timestamp('execution_started_at')->nullable();$table->timestamp('completed_at')->nullable();$table->timestamp('invalidated_at')->nullable();$table->timestamps();
            $table->unique('correction_request_id','fv2_corr_pair_request_uq');$table->unique('active_original_guard','fv2_corr_pair_active_original_uq');$table->unique(['accounting_entity_id','idempotency_key'],'fv2_corr_pair_idem_uq');$table->unique('reversal_transaction_id','fv2_corr_pair_reversal_uq');$table->unique('replacement_transaction_id','fv2_corr_pair_replacement_uq');$table->index(['accounting_entity_id','status'],'fv2_corr_pair_status_ix');
            $table->foreign('accounting_entity_id','fv2_corr_pair_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('correction_request_id','fv2_corr_pair_request_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('active_original_guard','fv2_corr_pair_active_original_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('original_transaction_id','fv2_corr_pair_original_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('original_journal_id','fv2_corr_pair_original_journal_fk')->references('id')->on('financial_v2_journals')->restrictOnDelete();
            $table->foreign('reversal_configuration_request_id','fv2_corr_pair_rev_config_fk')->references('id')->on('financial_v2_reversal_configuration_requests')->restrictOnDelete();
            $table->foreign('reason_code_id','fv2_corr_pair_reason_fk')->references('id')->on('financial_v2_reason_codes')->restrictOnDelete();
            $table->foreign('reversal_transaction_id','fv2_corr_pair_reversal_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('replacement_transaction_id','fv2_corr_pair_replacement_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('prepared_by_user_id','fv2_corr_pair_preparer_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('validated_by_user_id','fv2_corr_pair_validator_fk')->references('id')->on('users')->nullOnDelete();
        });
    }

    private function createLinks(array $d):void
    {
        Schema::create(self::LINKS,function(Blueprint$table)use($d):void{
            $table->charset=$d['financial_v2_accounting_entities']['charset'];$table->collation=$d['financial_v2_accounting_entities']['collation'];
            $this->uuid($table,'id',$d['financial_v2_accounting_entities'])->primary();$this->uuid($table,'accounting_entity_id',$d['financial_v2_accounting_entities']);$this->uuid($table,'batch_id',$d['financial_v2_accounting_date_correction_batches']);$this->uuid($table,'batch_item_id',$d['financial_v2_accounting_date_correction_batch_items']);$this->uuid($table,'correction_request_id',$d['financial_v2_accounting_date_correction_requests']);$this->uuid($table,'execution_pair_id',$d['financial_v2_accounting_entities']);
            $table->json('link_snapshot');$table->char('link_fingerprint',64);$table->unsignedBigInteger('created_by_user_id');$table->timestamp('created_at')->useCurrent();
            $table->unique('batch_item_id','fv2_batch_pair_item_uq');$table->unique('correction_request_id','fv2_batch_pair_request_uq');$table->unique('execution_pair_id','fv2_batch_pair_pair_uq');
            $table->foreign('accounting_entity_id','fv2_batch_pair_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();$table->foreign('batch_id','fv2_batch_pair_batch_fk')->references('id')->on('financial_v2_accounting_date_correction_batches')->restrictOnDelete();$table->foreign('batch_item_id','fv2_batch_pair_item_fk')->references('id')->on('financial_v2_accounting_date_correction_batch_items')->restrictOnDelete();$table->foreign('correction_request_id','fv2_batch_pair_request_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();$table->foreign('execution_pair_id','fv2_batch_pair_pair_fk')->references('id')->on(self::PAIRS)->restrictOnDelete();$table->foreign('created_by_user_id','fv2_batch_pair_creator_fk')->references('id')->on('users')->restrictOnDelete();
        });
    }

    private function postflight(array $d):void
    {
        $this->assertPairsSchema($d);
        $this->assertLinksSchema($d);
    }

    private function assertPairsSchema(array $d):void
    {
        $u=$d['financial_v2_accounting_entities'];
        Guard::assertTable(self::PAIRS,$u['collation']);
        Guard::assertColumns(self::PAIRS,['id'=>['type'=>'char(36)','nullable'=>'NO','charset'=>$u['charset'],'collation'=>$u['collation']],'accounting_entity_id'=>['type'=>'char(36)','nullable'=>'NO'],'correction_request_id'=>['type'=>'char(36)','nullable'=>'NO'],'active_original_guard'=>['type'=>'char(36)','nullable'=>'YES','default'=>null],'original_transaction_id'=>['type'=>'char(36)','nullable'=>'NO'],'original_journal_id'=>['type'=>'char(36)','nullable'=>'NO'],'reversal_configuration_request_id'=>['type'=>'char(36)','nullable'=>'YES','default'=>null],'reason_code_id'=>['type'=>'char(36)','nullable'=>'YES','default'=>null],'reversal_transaction_id'=>['type'=>'char(36)','nullable'=>'YES','default'=>null],'replacement_transaction_id'=>['type'=>'char(36)','nullable'=>'YES','default'=>null],'target_accounting_date'=>['type'=>'date','nullable'=>'NO'],'status'=>['type'=>"enum('prepared','validated','staged','execution_ready','executing','completed','invalidated','failed','cancelled')",'nullable'=>'NO','default'=>'prepared'],'approved_plan_snapshot'=>['type'=>'json','nullable'=>'NO'],'approved_plan_fingerprint'=>['type'=>'char(64)','nullable'=>'NO'],'source_fingerprint'=>['type'=>'char(64)','nullable'=>'NO'],'validation_snapshot'=>['type'=>'json','nullable'=>'YES','default'=>null],'validation_fingerprint'=>['type'=>'char(64)','nullable'=>'YES','default'=>null],'blockers'=>['type'=>'json','nullable'=>'YES','default'=>null],'idempotency_key'=>['type'=>'varchar(120)','nullable'=>'NO'],'prepared_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'NO'],'validated_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'YES','default'=>null],'validated_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'staged_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'execution_ready_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'execution_started_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'completed_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'invalidated_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'created_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],'updated_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null]]);
        foreach([['PRIMARY',true,['id']],['fv2_corr_pair_request_uq',true,['correction_request_id']],['fv2_corr_pair_active_original_uq',true,['active_original_guard']],['fv2_corr_pair_idem_uq',true,['accounting_entity_id','idempotency_key']],['fv2_corr_pair_reversal_uq',true,['reversal_transaction_id']],['fv2_corr_pair_replacement_uq',true,['replacement_transaction_id']],['fv2_corr_pair_status_ix',false,['accounting_entity_id','status']]]as[$n,$q,$c])Guard::assertIndex(self::PAIRS,$n,$q,$c);
        $fks=[['fv2_corr_pair_entity_fk',['accounting_entity_id'],'financial_v2_accounting_entities',['id'],'RESTRICT'],['fv2_corr_pair_request_fk',['correction_request_id'],'financial_v2_accounting_date_correction_requests',['id'],'RESTRICT'],['fv2_corr_pair_active_original_fk',['active_original_guard'],'financial_v2_transactions',['id'],'RESTRICT'],['fv2_corr_pair_original_tx_fk',['original_transaction_id'],'financial_v2_transactions',['id'],'RESTRICT'],['fv2_corr_pair_original_journal_fk',['original_journal_id'],'financial_v2_journals',['id'],'RESTRICT'],['fv2_corr_pair_rev_config_fk',['reversal_configuration_request_id'],'financial_v2_reversal_configuration_requests',['id'],'RESTRICT'],['fv2_corr_pair_reason_fk',['reason_code_id'],'financial_v2_reason_codes',['id'],'RESTRICT'],['fv2_corr_pair_reversal_tx_fk',['reversal_transaction_id'],'financial_v2_transactions',['id'],'RESTRICT'],['fv2_corr_pair_replacement_tx_fk',['replacement_transaction_id'],'financial_v2_transactions',['id'],'RESTRICT'],['fv2_corr_pair_preparer_fk',['prepared_by_user_id'],'users',['id'],'RESTRICT'],['fv2_corr_pair_validator_fk',['validated_by_user_id'],'users',['id'],'SET NULL']];
        foreach($fks as[$n,$s,$t,$tc,$delete])Guard::assertForeign(self::PAIRS,$n,$s,$t,$tc,$delete);
        Guard::assertEmpty([self::PAIRS]);
    }

    private function assertLinksSchema(array $d):void
    {
        $u=$d['financial_v2_accounting_entities'];
        Guard::assertTable(self::LINKS,$u['collation']);
        Guard::assertColumns(self::LINKS,['id'=>['type'=>'char(36)','nullable'=>'NO','charset'=>$u['charset'],'collation'=>$u['collation']],'accounting_entity_id'=>['type'=>'char(36)','nullable'=>'NO'],'batch_id'=>['type'=>'char(36)','nullable'=>'NO'],'batch_item_id'=>['type'=>'char(36)','nullable'=>'NO'],'correction_request_id'=>['type'=>'char(36)','nullable'=>'NO'],'execution_pair_id'=>['type'=>'char(36)','nullable'=>'NO','charset'=>$u['charset'],'collation'=>$u['collation']],'link_snapshot'=>['type'=>'json','nullable'=>'NO'],'link_fingerprint'=>['type'=>'char(64)','nullable'=>'NO'],'created_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'NO'],'created_at'=>['type'=>'timestamp','nullable'=>'NO','default'=>'CURRENT_TIMESTAMP']]);
        foreach([['PRIMARY',true,['id']],['fv2_batch_pair_item_uq',true,['batch_item_id']],['fv2_batch_pair_request_uq',true,['correction_request_id']],['fv2_batch_pair_pair_uq',true,['execution_pair_id']]]as[$n,$q,$c])Guard::assertIndex(self::LINKS,$n,$q,$c);
        $fks=[['fv2_batch_pair_entity_fk',['accounting_entity_id'],'financial_v2_accounting_entities',['id'],'RESTRICT'],['fv2_batch_pair_batch_fk',['batch_id'],'financial_v2_accounting_date_correction_batches',['id'],'RESTRICT'],['fv2_batch_pair_item_fk',['batch_item_id'],'financial_v2_accounting_date_correction_batch_items',['id'],'RESTRICT'],['fv2_batch_pair_request_fk',['correction_request_id'],'financial_v2_accounting_date_correction_requests',['id'],'RESTRICT'],['fv2_batch_pair_pair_fk',['execution_pair_id'],self::PAIRS,['id'],'RESTRICT'],['fv2_batch_pair_creator_fk',['created_by_user_id'],'users',['id'],'RESTRICT']];
        foreach($fks as[$n,$s,$t,$tc,$delete])Guard::assertForeign(self::LINKS,$n,$s,$t,$tc,$delete);
        Guard::assertEmpty([self::LINKS]);
    }

    private function uuid(Blueprint$table,string$name,array$definition):mixed
    {
        return$table->char($name,36)->charset($definition['charset'])->collation($definition['collation']);
    }
};
