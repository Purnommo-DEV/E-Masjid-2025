<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class FinancialV2MigrationSchemaGuard
{
    public static function assertDatabaseIdentity(string $database,string $host,string $version):void
    {
        $actual=DB::selectOne('SELECT DATABASE() AS database_name, @@hostname AS host_name, VERSION() AS server_version');
        self::assertDatabaseIdentityMetadata($database,$host,$version,$actual);
    }

    public static function assertDatabaseIdentityMetadata(string $database,string $host,string $version,?object $actual):void
    {
        if(!$actual||(string)$actual->database_name!==$database||(string)$actual->host_name!==$host||(string)$actual->server_version!==$version)throw new RuntimeException('Identitas database tidak sesuai target migration yang disetujui.');
    }

    public static function assertMigrations(array $names):void
    {
        $recorded=DB::table('migrations')->whereIn('migration',$names)->pluck('migration')->all();
        self::assertMigrationNames($names,$recorded);
    }

    public static function assertMigrationNames(array $names,array $recorded):void
    {
        $missing=array_values(array_diff($names,$recorded));
        if($missing!==[])throw new RuntimeException('Migration dependency belum lengkap: '.implode(', ',$missing));
    }

    public static function assertMigrationAbsent(string $name):void
    {
        $recorded=DB::table('migrations')->where('migration',$name)->exists();
        self::assertMigrationNotRecorded($name,$recorded);
    }

    public static function assertMigrationNotRecorded(string $name,bool $recorded):void
    {
        if($recorded)throw new RuntimeException("Migration {$name} sudah tercatat tetapi schema recovery belum lengkap.");
    }

    public static function targetTablesPresent(array $tables):array
    {
        return DB::table('information_schema.TABLES')->whereRaw('TABLE_SCHEMA=DATABASE()')->whereIn('TABLE_NAME',$tables)->orderBy('TABLE_NAME')->pluck('TABLE_NAME')->all();
    }

    public static function classifyTwoTableRecovery(array $present,string $primary,string $link):string
    {
        $present=array_values(array_unique($present));sort($present);
        if($present===[])return'normal';
        if($present===[$primary])return'recover_link_only';
        throw new RuntimeException('State target migration tidak dikenal atau tidak konsisten: '.implode(', ',$present));
    }

    public static function assertTablesAbsent(array $tables):void
    {
        $found=DB::table('information_schema.TABLES')->whereRaw('TABLE_SCHEMA=DATABASE()')->whereIn('TABLE_NAME',$tables)->pluck('TABLE_NAME')->all();
        self::assertNoTargetTables($found);
    }

    public static function assertNoTargetTables(array $found):void
    {
        if($found!==[])throw new RuntimeException('Schema target sudah ada atau parsial: '.implode(', ',$found).'. Recovery harus forward-only.');
    }

    public static function uuidParent(string $table):array
    {
        $meta=DB::selectOne('SELECT t.ENGINE,c.DATA_TYPE,c.COLUMN_TYPE,c.CHARACTER_MAXIMUM_LENGTH,c.CHARACTER_SET_NAME,c.COLLATION_NAME,c.IS_NULLABLE FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA=t.TABLE_SCHEMA AND c.TABLE_NAME=t.TABLE_NAME WHERE t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME=? AND c.COLUMN_NAME=?',[$table,'id']);
        $indexes=DB::select('SELECT INDEX_NAME,NON_UNIQUE,SEQ_IN_INDEX,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND NON_UNIQUE=0 ORDER BY INDEX_NAME,SEQ_IN_INDEX',[$table]);
        return self::assertUuidParentMetadata($table,$meta,$indexes);
    }

    public static function assertUuidParentMetadata(string $table,?object $meta,array $indexes):array
    {
        if(!$meta||strtoupper((string)$meta->ENGINE)!=='INNODB'||strtolower((string)$meta->DATA_TYPE)!=='char'||strtolower((string)$meta->COLUMN_TYPE)!=='char(36)'||(int)$meta->CHARACTER_MAXIMUM_LENGTH!==36||$meta->IS_NULLABLE!=='NO'||!$meta->CHARACTER_SET_NAME||!$meta->COLLATION_NAME)throw new RuntimeException("Parent {$table}.id bukan InnoDB UUID CHAR(36) non-null yang didukung.");
        $groups=[];
        foreach($indexes as$index){
            if((int)$index->NON_UNIQUE!==0)continue;
            $groups[(string)$index->INDEX_NAME][(int)$index->SEQ_IN_INDEX]=(string)$index->COLUMN_NAME;
        }
        $singleColumnId=false;
        foreach($groups as$columns){ksort($columns);if(array_values($columns)===['id']){$singleColumnId=true;break;}}
        if(!$singleColumnId)throw new RuntimeException("Parent {$table}.id bukan single-column primary/unique key.");
        return['charset'=>$meta->CHARACTER_SET_NAME,'collation'=>$meta->COLLATION_NAME];
    }

    public static function assertUsersId():void
    {
        $user=DB::selectOne("SELECT t.ENGINE,c.DATA_TYPE,c.COLUMN_TYPE,c.COLUMN_KEY,c.IS_NULLABLE FROM information_schema.TABLES t JOIN information_schema.COLUMNS c ON c.TABLE_SCHEMA=t.TABLE_SCHEMA AND c.TABLE_NAME=t.TABLE_NAME WHERE t.TABLE_SCHEMA=DATABASE() AND t.TABLE_NAME='users' AND c.COLUMN_NAME='id'");
        if(!$user||strtoupper((string)$user->ENGINE)!=='INNODB'||strtolower((string)$user->DATA_TYPE)!=='bigint'||!str_contains(strtolower((string)$user->COLUMN_TYPE),'unsigned')||$user->COLUMN_KEY!=='PRI'||$user->IS_NULLABLE!=='NO')throw new RuntimeException('users.id harus InnoDB primary key BIGINT UNSIGNED non-null.');
    }

    public static function assertColumns(string $table,array $matrix):void
    {
        foreach($matrix as$name=>$expected){
            $column=DB::selectOne('SELECT COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,CHARACTER_SET_NAME,COLLATION_NAME,EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$name]);
            if(!$column)throw new RuntimeException("Kolom {$table}.{$name} tidak tersedia.");
            foreach(['type'=>'COLUMN_TYPE','nullable'=>'IS_NULLABLE']as$key=>$property)if(isset($expected[$key])&&strtolower((string)$column->{$property})!==strtolower((string)$expected[$key]))throw new RuntimeException("Kontrak {$table}.{$name} {$key} tidak sesuai.");
            if(array_key_exists('default',$expected)&&self::normalDefault($column->COLUMN_DEFAULT)!==self::normalDefault($expected['default']))throw new RuntimeException("Default {$table}.{$name} tidak sesuai.");
            foreach(['charset'=>'CHARACTER_SET_NAME','collation'=>'COLLATION_NAME']as$key=>$property)if(array_key_exists($key,$expected)&&(string)$column->{$property}!==(string)$expected[$key])throw new RuntimeException("Kontrak {$table}.{$name} {$key} tidak sesuai.");
        }
    }

    public static function assertTable(string $table,string $collation):void
    {
        $meta=DB::selectOne('SELECT ENGINE,TABLE_COLLATION FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?',[$table]);
        if(!$meta||strtoupper((string)$meta->ENGINE)!=='INNODB'||(string)$meta->TABLE_COLLATION!==$collation)throw new RuntimeException("Tabel {$table} bukan InnoDB dengan collation yang diharapkan.");
    }

    public static function assertIndex(string $table,string $name,bool $unique,array $columns):void
    {
        $rows=DB::select('SELECT NON_UNIQUE,COLUMN_NAME FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=? ORDER BY SEQ_IN_INDEX',[$table,$name]);
        if($rows===[]||((int)$rows[0]->NON_UNIQUE===0)!==$unique||array_map(fn($r)=>(string)$r->COLUMN_NAME,$rows)!==$columns)throw new RuntimeException("Index {$table}.{$name} tidak sesuai kontrak.");
    }

    public static function assertForeign(string $table,string $name,array $source,string $target,array $targetColumns,string $deleteRule,string $updateRule='RESTRICT'):void
    {
        $rows=DB::select('SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.UPDATE_RULE,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.TABLE_NAME=k.TABLE_NAME AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME WHERE k.CONSTRAINT_SCHEMA=DATABASE() AND k.TABLE_NAME=? AND k.CONSTRAINT_NAME=? ORDER BY k.ORDINAL_POSITION',[$table,$name]);
        self::assertForeignMetadata($table,$name,$rows,$source,$target,$targetColumns,$deleteRule,$updateRule);
        foreach($source as$position=>$sourceColumn){
            $sourceMeta=DB::selectOne('SELECT COLUMN_TYPE,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$table,$sourceColumn]);
            $targetMeta=DB::selectOne('SELECT COLUMN_TYPE,CHARACTER_SET_NAME,COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?',[$target,$targetColumns[$position]]);
            self::assertCompatibleColumnMetadata("{$table}.{$sourceColumn}","{$target}.{$targetColumns[$position]}",$sourceMeta,$targetMeta);
        }
    }

    public static function assertForeignMetadata(string $table,string $name,array $rows,array $source,string $target,array $targetColumns,string $deleteRule,string $updateRule='RESTRICT'):void
    {
        if($rows===[]||array_map(fn($r)=>(string)$r->COLUMN_NAME,$rows)!==$source||array_unique(array_map(fn($r)=>(string)$r->REFERENCED_TABLE_NAME,$rows))!==[$target]||array_map(fn($r)=>(string)$r->REFERENCED_COLUMN_NAME,$rows)!==$targetColumns)throw new RuntimeException("Foreign key {$table}.{$name} tidak sesuai pasangan kolom.");
        if(strtoupper((string)$rows[0]->DELETE_RULE)!==strtoupper($deleteRule))throw new RuntimeException("DELETE_RULE {$table}.{$name} tidak sesuai.");
        if(self::canonicalUpdateRule((string)$rows[0]->UPDATE_RULE)!==self::canonicalUpdateRule($updateRule))throw new RuntimeException("UPDATE_RULE {$table}.{$name} tidak sesuai.");
    }

    public static function assertCompatibleColumnMetadata(string $sourceName,string $targetName,?object $source,?object $target):void
    {
        if(!$source||!$target)throw new RuntimeException("Metadata kolom FK {$sourceName} atau {$targetName} tidak tersedia.");
        foreach(['COLUMN_TYPE','CHARACTER_SET_NAME','COLLATION_NAME']as$property){
            if(strtolower((string)$source->{$property})!==strtolower((string)$target->{$property}))throw new RuntimeException("Definisi kolom FK {$sourceName} tidak kompatibel dengan {$targetName} pada {$property}.");
        }
    }

    public static function canonicalUpdateRule(string $rule):string
    {
        $rule=strtoupper(trim($rule));
        if(in_array($rule,['NO ACTION','RESTRICT'],true))return'RESTRICT';
        if(in_array($rule,['CASCADE','SET NULL','SET DEFAULT'],true))return$rule;
        throw new RuntimeException("UPDATE_RULE tidak dikenal: {$rule}");
    }

    public static function assertEmpty(array $tables):void
    {
        foreach($tables as$table)self::assertTableEmptyCount($table,DB::table($table)->count());
    }

    public static function assertTableEmptyCount(string $table,int $count):void
    {
        if($count!==0)throw new RuntimeException("Migration tidak boleh menghasilkan record pada {$table}.");
    }

    private static function normalDefault(mixed $value):?string
    {
        if($value===null)return null;
        return strtoupper(trim((string)$value,"'\""));
    }
}
