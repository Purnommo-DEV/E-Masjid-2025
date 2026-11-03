<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\FinancialV2MigrationSchemaGuard as Guard;

return new class extends Migration
{
    private const TABLES = [
        'financial_v2_replacement_realization_requests',
        'financial_v2_replacement_realization_decisions',
        'financial_v2_replacement_realization_lineages',
        'financial_v2_distribution_correction_lineages',
    ];

    public function up(): void
    {
        $uuid = $this->preflight();

        Schema::create(self::TABLES[0], function (Blueprint $table) use ($uuid): void {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset'];
            $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $this->uuid($table, 'correction_request_id', $uuid['financial_v2_accounting_date_correction_requests']);
            $this->uuid($table, 'original_realization_id', $uuid['financial_v2_fund_realizations']);
            $this->uuid($table, 'replacement_transaction_id', $uuid['financial_v2_transactions']);
            $this->uuid($table, 'evidence_attachment_id', $uuid['financial_v2_attachments']);
            $table->enum('separation_policy', ['maker_checker', 'three_distinct']);
            $table->enum('status', ['draft', 'pending_review', 'approved', 'materialized', 'rejected', 'cancelled'])->default('draft');
            $table->json('proposal_snapshot');
            $table->char('snapshot_hash', 64);
            $table->string('idempotency_key', 120);
            $this->uuid($table, 'active_correction_guard', $uuid['financial_v2_accounting_date_correction_requests'])->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by_user_id')->nullable();
            $table->timestamp('materialized_at')->nullable();
            $table->unsignedBigInteger('materialized_by_user_id')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->unsignedBigInteger('cancelled_by_user_id')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
            $table->unique(['accounting_entity_id', 'idempotency_key'], 'fv2_repl_real_req_idem_uq');
            $table->unique('active_correction_guard', 'fv2_repl_real_req_active_uq');
            $table->foreign('accounting_entity_id', 'fv2_repl_real_req_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('correction_request_id', 'fv2_repl_real_req_corr_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('active_correction_guard', 'fv2_repl_real_req_guard_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('original_realization_id', 'fv2_repl_real_req_orig_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
            $table->foreign('replacement_transaction_id', 'fv2_repl_real_req_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
            $table->foreign('evidence_attachment_id', 'fv2_repl_real_req_evidence_fk')->references('id')->on('financial_v2_attachments')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'fv2_repl_real_req_creator_fk')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('approved_by_user_id', 'fv2_repl_real_req_approver_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('materialized_by_user_id', 'fv2_repl_real_req_materializer_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('cancelled_by_user_id', 'fv2_repl_real_req_canceller_fk')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create(self::TABLES[1], function (Blueprint $table) use ($uuid): void {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset']; $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $table->uuid('replacement_request_id');
            $table->enum('decision', ['submitted', 'approved', 'rejected', 'cancelled', 'materialized']);
            $table->json('proposal_snapshot'); $table->char('snapshot_hash', 64); $table->text('note')->nullable();
            $table->unsignedBigInteger('actor_user_id'); $table->timestamp('decided_at'); $table->timestamp('created_at')->useCurrent();
            $table->foreign('accounting_entity_id', 'fv2_repl_real_dec_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('replacement_request_id', 'fv2_repl_real_dec_req_fk')->references('id')->on(self::TABLES[0])->restrictOnDelete();
            $table->foreign('actor_user_id', 'fv2_repl_real_dec_actor_fk')->references('id')->on('users')->restrictOnDelete();
            $table->index(['replacement_request_id', 'decided_at'], 'fv2_repl_real_dec_timeline_ix');
        });

        Schema::create(self::TABLES[2], function (Blueprint $table) use ($uuid): void {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset']; $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary(); $table->uuid('replacement_request_id');
            $this->uuid($table, 'correction_request_id', $uuid['financial_v2_accounting_date_correction_requests']);
            $this->uuid($table, 'original_realization_id', $uuid['financial_v2_fund_realizations']);
            $this->uuid($table, 'replacement_realization_id', $uuid['financial_v2_fund_realizations']);
            $this->uuid($table, 'replacement_transaction_id', $uuid['financial_v2_transactions']);
            $table->json('lineage_snapshot'); $table->char('snapshot_hash', 64); $table->timestamp('created_at')->useCurrent();
            $table->unique('replacement_request_id', 'fv2_repl_real_lin_req_uq');
            $table->unique('correction_request_id', 'fv2_repl_real_lin_corr_uq');
            $table->unique('replacement_realization_id', 'fv2_repl_real_lin_new_uq');
            $table->foreign('replacement_request_id', 'fv2_repl_real_lin_req_fk')->references('id')->on(self::TABLES[0])->restrictOnDelete();
            $table->foreign('correction_request_id', 'fv2_repl_real_lin_corr_fk')->references('id')->on('financial_v2_accounting_date_correction_requests')->restrictOnDelete();
            $table->foreign('original_realization_id', 'fv2_repl_real_lin_orig_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
            $table->foreign('replacement_realization_id', 'fv2_repl_real_lin_new_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
            $table->foreign('replacement_transaction_id', 'fv2_repl_real_lin_tx_fk')->references('id')->on('financial_v2_transactions')->restrictOnDelete();
        });

        Schema::create(self::TABLES[3], function (Blueprint $table) use ($uuid): void {
            $table->charset = $uuid['financial_v2_accounting_entities']['charset']; $table->collation = $uuid['financial_v2_accounting_entities']['collation'];
            $table->uuid('id')->primary();
            $this->uuid($table, 'accounting_entity_id', $uuid['financial_v2_accounting_entities']);
            $table->uuid('replacement_realization_lineage_id');
            $this->uuid($table, 'original_distribution_id', $uuid['financial_v2_distributions']);
            $this->uuid($table, 'original_realization_id', $uuid['financial_v2_fund_realizations']);
            $this->uuid($table, 'replacement_realization_id', $uuid['financial_v2_fund_realizations']);
            $table->json('distribution_snapshot'); $table->char('snapshot_hash', 64); $table->timestamp('created_at')->useCurrent();
            $table->unique('original_distribution_id', 'fv2_dist_corr_lin_dist_uq');
            $table->unique('replacement_realization_lineage_id', 'fv2_dist_corr_lin_repl_uq');
            $table->foreign('accounting_entity_id', 'fv2_dist_corr_lin_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('replacement_realization_lineage_id', 'fv2_dist_corr_lin_repl_fk')->references('id')->on(self::TABLES[2])->restrictOnDelete();
            $table->foreign('original_distribution_id', 'fv2_dist_corr_lin_dist_fk')->references('id')->on('financial_v2_distributions')->restrictOnDelete();
            $table->foreign('original_realization_id', 'fv2_dist_corr_lin_orig_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
            $table->foreign('replacement_realization_id', 'fv2_dist_corr_lin_new_fk')->references('id')->on('financial_v2_fund_realizations')->restrictOnDelete();
        });

        $this->postflight($uuid);
    }

    public function down(): void { throw new RuntimeException('Histori replacement realization/distribution correction tidak boleh dihapus otomatis.'); }

    private function preflight(): array
    {
        Guard::assertMigrations(['2026_10_12_000001_create_financial_v2_accounting_date_correction_workflow']);
        Guard::assertTablesAbsent(self::TABLES);
        $parents = ['financial_v2_accounting_entities', 'financial_v2_accounting_date_correction_requests', 'financial_v2_fund_realizations', 'financial_v2_transactions', 'financial_v2_attachments', 'financial_v2_distributions'];
        $definitions = [];
        foreach ($parents as $parent) {
            $definitions[$parent] = Guard::uuidParent($parent);
        }
        Guard::assertUsersId();
        Guard::assertIndex('financial_v2_accounting_date_correction_requests','PRIMARY',true,['id']);
        Guard::assertIndex('financial_v2_accounting_date_correction_requests','fv2_acct_date_corr_active_uq',true,['active_transaction_guard']);
        Guard::assertForeign('financial_v2_accounting_date_correction_requests','fv2_acct_date_corr_tx_fk',['original_transaction_id'],'financial_v2_transactions',['id'],'RESTRICT');
        return $definitions;
    }

    private function postflight(array $uuid):void
    {
        $u=$uuid['financial_v2_accounting_entities'];
        foreach(self::TABLES as $table) Guard::assertTable($table,$u['collation']);
        Guard::assertColumns(self::TABLES[0],[
            'id'=>['type'=>'char(36)','nullable'=>'NO','charset'=>$u['charset'],'collation'=>$u['collation']], 'accounting_entity_id'=>['type'=>'char(36)','nullable'=>'NO'], 'correction_request_id'=>['type'=>'char(36)','nullable'=>'NO'], 'original_realization_id'=>['type'=>'char(36)','nullable'=>'NO'], 'replacement_transaction_id'=>['type'=>'char(36)','nullable'=>'NO'], 'evidence_attachment_id'=>['type'=>'char(36)','nullable'=>'NO'], 'separation_policy'=>['type'=>"enum('maker_checker','three_distinct')",'nullable'=>'NO'], 'status'=>['type'=>"enum('draft','pending_review','approved','materialized','rejected','cancelled')",'nullable'=>'NO','default'=>'draft'], 'proposal_snapshot'=>['type'=>'json','nullable'=>'NO'], 'snapshot_hash'=>['type'=>'char(64)','nullable'=>'NO'], 'idempotency_key'=>['type'=>'varchar(120)','nullable'=>'NO'], 'active_correction_guard'=>['type'=>'char(36)','nullable'=>'YES','default'=>null], 'created_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'NO'], 'submitted_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null], 'approved_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null], 'approved_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'YES','default'=>null], 'materialized_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null], 'materialized_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'YES','default'=>null], 'cancelled_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null], 'cancelled_by_user_id'=>['type'=>'bigint unsigned','nullable'=>'YES','default'=>null], 'cancellation_reason'=>['type'=>'text','nullable'=>'YES','default'=>null], 'created_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null], 'updated_at'=>['type'=>'timestamp','nullable'=>'YES','default'=>null],
        ]);
        Guard::assertColumns(self::TABLES[1],['id'=>['type'=>'char(36)','nullable'=>'NO'],'accounting_entity_id'=>['type'=>'char(36)','nullable'=>'NO'],'replacement_request_id'=>['type'=>'char(36)','nullable'=>'NO'],'decision'=>['type'=>"enum('submitted','approved','rejected','cancelled','materialized')",'nullable'=>'NO'],'proposal_snapshot'=>['type'=>'json','nullable'=>'NO'],'snapshot_hash'=>['type'=>'char(64)','nullable'=>'NO'],'note'=>['type'=>'text','nullable'=>'YES','default'=>null],'actor_user_id'=>['type'=>'bigint unsigned','nullable'=>'NO'],'decided_at'=>['type'=>'timestamp','nullable'=>'NO'],'created_at'=>['type'=>'timestamp','nullable'=>'NO','default'=>'CURRENT_TIMESTAMP']]);
        Guard::assertColumns(self::TABLES[2],['id'=>['type'=>'char(36)','nullable'=>'NO'],'replacement_request_id'=>['type'=>'char(36)','nullable'=>'NO'],'correction_request_id'=>['type'=>'char(36)','nullable'=>'NO'],'original_realization_id'=>['type'=>'char(36)','nullable'=>'NO'],'replacement_realization_id'=>['type'=>'char(36)','nullable'=>'NO'],'replacement_transaction_id'=>['type'=>'char(36)','nullable'=>'NO'],'lineage_snapshot'=>['type'=>'json','nullable'=>'NO'],'snapshot_hash'=>['type'=>'char(64)','nullable'=>'NO'],'created_at'=>['type'=>'timestamp','nullable'=>'NO','default'=>'CURRENT_TIMESTAMP']]);
        Guard::assertColumns(self::TABLES[3],['id'=>['type'=>'char(36)','nullable'=>'NO'],'accounting_entity_id'=>['type'=>'char(36)','nullable'=>'NO'],'replacement_realization_lineage_id'=>['type'=>'char(36)','nullable'=>'NO'],'original_distribution_id'=>['type'=>'char(36)','nullable'=>'NO'],'original_realization_id'=>['type'=>'char(36)','nullable'=>'NO'],'replacement_realization_id'=>['type'=>'char(36)','nullable'=>'NO'],'distribution_snapshot'=>['type'=>'json','nullable'=>'NO'],'snapshot_hash'=>['type'=>'char(64)','nullable'=>'NO'],'created_at'=>['type'=>'timestamp','nullable'=>'NO','default'=>'CURRENT_TIMESTAMP']]);
        foreach([[self::TABLES[0],'PRIMARY',true,['id']],[self::TABLES[1],'PRIMARY',true,['id']],[self::TABLES[2],'PRIMARY',true,['id']],[self::TABLES[3],'PRIMARY',true,['id']],[self::TABLES[0],'fv2_repl_real_req_idem_uq',true,['accounting_entity_id','idempotency_key']],[self::TABLES[0],'fv2_repl_real_req_active_uq',true,['active_correction_guard']],[self::TABLES[1],'fv2_repl_real_dec_timeline_ix',false,['replacement_request_id','decided_at']],[self::TABLES[2],'fv2_repl_real_lin_req_uq',true,['replacement_request_id']],[self::TABLES[2],'fv2_repl_real_lin_corr_uq',true,['correction_request_id']],[self::TABLES[2],'fv2_repl_real_lin_new_uq',true,['replacement_realization_id']],[self::TABLES[3],'fv2_dist_corr_lin_dist_uq',true,['original_distribution_id']],[self::TABLES[3],'fv2_dist_corr_lin_repl_uq',true,['replacement_realization_lineage_id']]]as[$t,$n,$q,$c])Guard::assertIndex($t,$n,$q,$c);
        $fks=[[self::TABLES[0],'fv2_repl_real_req_entity_fk',['accounting_entity_id'],'financial_v2_accounting_entities',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_corr_fk',['correction_request_id'],'financial_v2_accounting_date_correction_requests',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_guard_fk',['active_correction_guard'],'financial_v2_accounting_date_correction_requests',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_orig_fk',['original_realization_id'],'financial_v2_fund_realizations',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_tx_fk',['replacement_transaction_id'],'financial_v2_transactions',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_evidence_fk',['evidence_attachment_id'],'financial_v2_attachments',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_creator_fk',['created_by_user_id'],'users',['id'],'RESTRICT'],[self::TABLES[0],'fv2_repl_real_req_approver_fk',['approved_by_user_id'],'users',['id'],'SET NULL'],[self::TABLES[0],'fv2_repl_real_req_materializer_fk',['materialized_by_user_id'],'users',['id'],'SET NULL'],[self::TABLES[0],'fv2_repl_real_req_canceller_fk',['cancelled_by_user_id'],'users',['id'],'SET NULL'],[self::TABLES[1],'fv2_repl_real_dec_entity_fk',['accounting_entity_id'],'financial_v2_accounting_entities',['id'],'RESTRICT'],[self::TABLES[1],'fv2_repl_real_dec_req_fk',['replacement_request_id'],self::TABLES[0],['id'],'RESTRICT'],[self::TABLES[1],'fv2_repl_real_dec_actor_fk',['actor_user_id'],'users',['id'],'RESTRICT'],[self::TABLES[2],'fv2_repl_real_lin_req_fk',['replacement_request_id'],self::TABLES[0],['id'],'RESTRICT'],[self::TABLES[2],'fv2_repl_real_lin_corr_fk',['correction_request_id'],'financial_v2_accounting_date_correction_requests',['id'],'RESTRICT'],[self::TABLES[2],'fv2_repl_real_lin_orig_fk',['original_realization_id'],'financial_v2_fund_realizations',['id'],'RESTRICT'],[self::TABLES[2],'fv2_repl_real_lin_new_fk',['replacement_realization_id'],'financial_v2_fund_realizations',['id'],'RESTRICT'],[self::TABLES[2],'fv2_repl_real_lin_tx_fk',['replacement_transaction_id'],'financial_v2_transactions',['id'],'RESTRICT'],[self::TABLES[3],'fv2_dist_corr_lin_entity_fk',['accounting_entity_id'],'financial_v2_accounting_entities',['id'],'RESTRICT'],[self::TABLES[3],'fv2_dist_corr_lin_repl_fk',['replacement_realization_lineage_id'],self::TABLES[2],['id'],'RESTRICT'],[self::TABLES[3],'fv2_dist_corr_lin_dist_fk',['original_distribution_id'],'financial_v2_distributions',['id'],'RESTRICT'],[self::TABLES[3],'fv2_dist_corr_lin_orig_fk',['original_realization_id'],'financial_v2_fund_realizations',['id'],'RESTRICT'],[self::TABLES[3],'fv2_dist_corr_lin_new_fk',['replacement_realization_id'],'financial_v2_fund_realizations',['id'],'RESTRICT']];foreach($fks as$f)Guard::assertForeign(...$f);
        Guard::assertEmpty(self::TABLES);
    }

    private function uuid(Blueprint $table, string $name, array $definition): mixed
    {
        return $table->char($name, 36)->charset($definition['charset'])->collation($definition['collation']);
    }
};
