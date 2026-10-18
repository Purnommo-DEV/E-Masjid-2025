<?php

namespace Tests\Unit\FinancialV2;

use App\Support\FinancialV2MigrationSchemaGuard as Guard;
use App\Support\FinancialV2Migration17RecoveryPlan as RecoveryPlan;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FinancialV2MigrationSchemaGuardTest extends TestCase
{
    public function test_missing_migration_dependency_is_rejected():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertMigrationNames(['foundation','replacement'],['foundation']);
    }

    public function test_partial_target_schema_is_rejected():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertNoTargetTables(['financial_v2_partial_table']);
    }

    public function test_supported_uuid_parent_requires_single_column_unique_key():void
    {
        self::assertSame(['charset'=>'utf8mb4','collation'=>'utf8mb4_general_ci'],Guard::assertUuidParentMetadata('parent',$this->uuidMeta(),[$this->index('PRIMARY',1,'id')]));
        self::assertSame(['charset'=>'utf8mb4','collation'=>'utf8mb4_general_ci'],Guard::assertUuidParentMetadata('parent',$this->uuidMeta(),[$this->index('parent_id_uq',1,'id')]));
    }

    public function test_composite_unique_starting_with_id_is_rejected():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertUuidParentMetadata('parent',$this->uuidMeta(),[$this->index('composite_uq',1,'id'),$this->index('composite_uq',2,'other_column')]);
    }

    public function test_parent_without_single_column_unique_key_is_rejected():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertUuidParentMetadata('parent',$this->uuidMeta(),[]);
    }

    #[DataProvider('invalidUuidMetadata')]
    public function test_incompatible_uuid_parent_metadata_is_rejected(array $override):void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertUuidParentMetadata('parent',$this->uuidMeta($override),[$this->index('PRIMARY',1,'id')]);
    }

    public static function invalidUuidMetadata():array
    {
        return[
            'non InnoDB'=>[['ENGINE'=>'MyISAM']],
            'wrong data type'=>[['DATA_TYPE'=>'varchar','COLUMN_TYPE'=>'varchar(36)']],
            'wrong length'=>[['CHARACTER_MAXIMUM_LENGTH'=>32,'COLUMN_TYPE'=>'char(32)']],
            'missing charset'=>[['CHARACTER_SET_NAME'=>null]],
            'missing collation'=>[['COLLATION_NAME'=>null]],
            'nullable'=>[['IS_NULLABLE'=>'YES']],
        ];
    }

    public function test_missing_or_mismatched_foreign_key_is_rejected():void
    {
        foreach([[],[$this->foreignRow('wrong_column','parents','id','RESTRICT','RESTRICT')]]as$rows){
            try{Guard::assertForeignMetadata('children','child_parent_fk',$rows,['parent_id'],'parents',['id'],'RESTRICT');self::fail('Foreign key yang hilang atau salah harus ditolak.');}
            catch(RuntimeException){self::addToAssertionCount(1);}
        }
    }

    public function test_wrong_delete_rule_is_rejected():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertForeignMetadata('children','child_parent_fk',[$this->foreignRow('parent_id','parents','id','CASCADE','RESTRICT')],['parent_id'],'parents',['id'],'RESTRICT');
    }

    public function test_foreign_key_column_definition_mismatch_is_rejected():void
    {
        $source=(object)['COLUMN_TYPE'=>'char(36)','CHARACTER_SET_NAME'=>'utf8mb4','COLLATION_NAME'=>'utf8mb4_general_ci'];
        $target=(object)['COLUMN_TYPE'=>'char(36)','CHARACTER_SET_NAME'=>'utf8mb4','COLLATION_NAME'=>'utf8mb4_unicode_ci'];
        $this->expectException(RuntimeException::class);
        Guard::assertCompatibleColumnMetadata('children.parent_id','parents.id',$source,$target);
    }

    public function test_update_rule_equivalence_is_narrow_and_unknown_is_rejected():void
    {
        foreach(['NO ACTION','RESTRICT']as$actual){Guard::assertForeignMetadata('children','child_parent_fk',[$this->foreignRow('parent_id','parents','id','RESTRICT',$actual)],['parent_id'],'parents',['id'],'RESTRICT');self::addToAssertionCount(1);}
        self::assertSame('CASCADE',Guard::canonicalUpdateRule('CASCADE'));
        self::assertSame('SET NULL',Guard::canonicalUpdateRule('SET NULL'));
        $this->expectException(RuntimeException::class);
        Guard::canonicalUpdateRule('UNKNOWN');
    }

    public function test_migrations_keep_fail_closed_down_and_execution_guard_fk():void
    {
        $root=dirname(__DIR__,3);
        $files=[$root.'/database/migrations/financial_v2/2026_10_15_000001_create_financial_v2_replacement_realization_workflow.php',$root.'/database/migrations/financial_v2/2026_10_16_000001_create_financial_v2_accounting_date_correction_batches.php',$root.'/database/migrations/financial_v2/2026_10_17_000001_create_financial_v2_accounting_date_correction_execution_pairs.php'];
        foreach($files as$file){$source=file_get_contents($file);self::assertStringContainsString('Guard::assertMigrations',$source);self::assertStringContainsString('Guard::assertTable',$source);self::assertStringContainsString('Guard::assertColumns',$source);self::assertStringContainsString('Guard::assertForeign',$source);self::assertStringContainsString('throw new RuntimeException',$source);self::assertStringNotContainsString('dropIfExists',$source);}
        self::assertStringContainsString('Guard::assertTablesAbsent',file_get_contents($files[0]));
        self::assertStringContainsString('Guard::assertTablesAbsent',file_get_contents($files[1]));
        self::assertStringContainsString('Guard::classifyTwoTableRecovery',file_get_contents($files[2]));
        self::assertStringContainsString('2026_10_15_000001_create_financial_v2_replacement_realization_workflow',file_get_contents($files[1]));
        $execution=file_get_contents($files[2]);
        self::assertStringContainsString("foreign('active_original_guard','fv2_corr_pair_active_original_fk')",$execution);
        self::assertStringContainsString("'fv2_corr_pair_active_original_fk'",$execution);
    }

    public function test_migration_17_identifiers_fit_mysql_limit_and_use_short_link_table():void
    {
        $root=dirname(__DIR__,3);
        $migration=file_get_contents($root.'/database/migrations/financial_v2/2026_10_17_000001_create_financial_v2_accounting_date_correction_execution_pairs.php');
        preg_match_all("/'((?:financial_v2|fv2_)[a-z0-9_]+)'/",$migration,$matches);
        $identifiers=array_values(array_unique($matches[1]));
        self::assertNotEmpty($identifiers);
        foreach($identifiers as$identifier)self::assertLessThanOrEqual(64,strlen($identifier),"Identifier MySQL terlalu panjang: {$identifier}");
        self::assertStringContainsString("private const LINKS='financial_v2_acct_date_corr_batch_item_pairs'",$migration);
        self::assertSame(44,strlen('financial_v2_acct_date_corr_batch_item_pairs'));
    }

    public function test_migration_17_normal_plan_creates_both_tables_then_postflights():void
    {
        self::assertSame('normal',Guard::classifyTwoTableRecovery([],'pairs','links'));
        $events=[];
        RecoveryPlan::execute('normal',function()use(&$events){$events[]='pairs';},function()use(&$events){$events[]='links';},function()use(&$events){$events[]='postflight';});
        self::assertSame(['pairs','links','postflight'],$events);
    }

    public function test_migration_17_known_partial_plan_only_creates_link_then_postflights():void
    {
        self::assertSame('recover_link_only',Guard::classifyTwoTableRecovery(['pairs'],'pairs','links'));
        $events=[];
        RecoveryPlan::execute('recover_link_only',function()use(&$events){$events[]='pairs';},function()use(&$events){$events[]='links';},function()use(&$events){$events[]='postflight';});
        self::assertSame(['links','postflight'],$events);
    }

    public function test_migration_17_unknown_target_states_are_rejected():void
    {
        foreach([['links'],['pairs','links'],['unexpected']]as$state){
            try{Guard::classifyTwoTableRecovery($state,'pairs','links');self::fail('State target tidak dikenal harus ditolak.');}
            catch(RuntimeException){self::addToAssertionCount(1);}
        }
    }

    public function test_migration_17_non_empty_partial_primary_is_rejected():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertTableEmptyCount('pairs',1);
    }

    public function test_migration_17_recorded_history_is_rejected_for_recovery():void
    {
        $this->expectException(RuntimeException::class);
        Guard::assertMigrationNotRecorded('migration-17',true);
    }

    public function test_migration_17_postflight_failure_propagates_and_never_reports_success():void
    {
        $events=[];
        try{
            RecoveryPlan::execute('recover_link_only',fn()=>null,function()use(&$events){$events[]='links';},function()use(&$events){$events[]='postflight';throw new RuntimeException('schema mismatch');});
            self::fail('Postflight gagal tidak boleh dianggap berhasil.');
        }catch(RuntimeException$error){
            self::assertStringContainsString('postflight lengkap',$error->getMessage());
            self::assertSame(['links','postflight'],$events);
        }
    }

    public function test_migration_17_contains_no_destructive_or_business_data_mutation_path():void
    {
        $source=file_get_contents(dirname(__DIR__,3).'/database/migrations/financial_v2/2026_10_17_000001_create_financial_v2_accounting_date_correction_execution_pairs.php');
        foreach(['dropIfExists','Schema::drop','->delete(','->truncate(','->insert(','->update(']as$forbidden)self::assertStringNotContainsString($forbidden,$source);
    }

    private function uuidMeta(array $override=[]):object{return(object)array_merge(['ENGINE'=>'InnoDB','DATA_TYPE'=>'char','COLUMN_TYPE'=>'char(36)','CHARACTER_MAXIMUM_LENGTH'=>36,'CHARACTER_SET_NAME'=>'utf8mb4','COLLATION_NAME'=>'utf8mb4_general_ci','IS_NULLABLE'=>'NO'],$override);}
    private function index(string $name,int $sequence,string $column):object{return(object)['INDEX_NAME'=>$name,'NON_UNIQUE'=>0,'SEQ_IN_INDEX'=>$sequence,'COLUMN_NAME'=>$column];}
    private function foreignRow(string $column,string $target,string $targetColumn,string $delete,string $update):object{return(object)['COLUMN_NAME'=>$column,'REFERENCED_TABLE_NAME'=>$target,'REFERENCED_COLUMN_NAME'=>$targetColumn,'DELETE_RULE'=>$delete,'UPDATE_RULE'=>$update];}
}
