<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_v2_budget_allocation_fundings', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('accounting_entity_id');
            $table->uuid('budget_allocation_version_id');
            $table->uuid('fund_id');
            $table->unsignedSmallInteger('line_no');
            $table->decimal('amount', 19, 2);
            $table->text('note')->nullable();
            $table->string('source_reference', 500)->nullable();
            $table->timestamps();
            $table->foreignId('created_by_user_id')->nullable();
            $table->foreignId('updated_by_user_id')->nullable();
            $table->foreign('accounting_entity_id', 'fv2_budget_funding_entity_fk')->references('id')->on('financial_v2_accounting_entities')->restrictOnDelete();
            $table->foreign('budget_allocation_version_id', 'fv2_budget_funding_version_fk')->references('id')->on('financial_v2_budget_allocation_versions')->restrictOnDelete();
            $table->foreign('fund_id', 'fv2_budget_funding_fund_fk')->references('id')->on('financial_v2_funds')->restrictOnDelete();
            $table->foreign('created_by_user_id', 'fv2_budget_funding_created_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by_user_id', 'fv2_budget_funding_updated_user_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['budget_allocation_version_id', 'line_no'], 'fv2_budget_funding_version_line_uq');
            $table->unique(['budget_allocation_version_id', 'fund_id'], 'fv2_budget_funding_version_fund_uq');
            $table->index(['accounting_entity_id', 'fund_id', 'budget_allocation_version_id'], 'fv2_budget_funding_entity_fund_ix');
        });

        DB::statement('ALTER TABLE financial_v2_budget_allocation_fundings ADD CONSTRAINT fv2_budget_funding_amount_ck CHECK (amount > 0)');

        Schema::table('financial_v2_transaction_splits', function (Blueprint $table): void {
            $table->string('source_reference', 500)->nullable()->after('purpose_note');
        });

        $now = now();
        DB::table('financial_v2_budget_allocation_versions as version')
            ->join('financial_v2_budget_allocations as allocation', 'allocation.id', '=', 'version.budget_allocation_id')
            ->orderBy('version.id')
            ->select([
                'version.id as version_id',
                'version.accounting_entity_id',
                'version.allocated_amount',
                'version.created_by_user_id',
                'version.updated_by_user_id',
                'allocation.fund_id',
            ])
            ->chunk(250, function ($versions) use ($now): void {
                DB::table('financial_v2_budget_allocation_fundings')->insert($versions->map(fn (object $version): array => [
                    'id' => (string) Str::uuid(),
                    'accounting_entity_id' => $version->accounting_entity_id,
                    'budget_allocation_version_id' => $version->version_id,
                    'fund_id' => $version->fund_id,
                    'line_no' => 1,
                    'amount' => $version->allocated_amount,
                    'note' => 'Backward-compatible funding source from the original single-Fund allocation.',
                    'source_reference' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'created_by_user_id' => $version->created_by_user_id,
                    'updated_by_user_id' => $version->updated_by_user_id,
                ])->all());
            });
    }

    public function down(): void
    {
        Schema::table('financial_v2_transaction_splits', function (Blueprint $table): void {
            $table->dropColumn('source_reference');
        });

        Schema::dropIfExists('financial_v2_budget_allocation_fundings');
    }
};
