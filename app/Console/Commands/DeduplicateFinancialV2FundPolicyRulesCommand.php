<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FundPolicyRuleDeduplicationService;
use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Console\Command;

final class DeduplicateFinancialV2FundPolicyRulesCommand extends Command
{
    protected $signature = 'financial-v2:deduplicate-fund-policy-rules
                            {--entity= : Accounting entity UUID or code; omit to audit every entity}
                            {--apply : Remove only unused semantic duplicates; without this flag the command is read-only}';

    protected $description = 'Audit and safely remove unused, semantically identical Financial V2 Fund Policy rules.';

    public function handle(FundPolicyRuleDeduplicationService $service): int
    {
        $entityId = $this->resolveEntityId($this->option('entity'));
        $audit = $service->audit($entityId);
        $rows = collect($audit['groups'])->map(fn (array $group): array => [
            $group['fund_policy_version_id'],
            $group['canonical_rule_id'],
            implode(', ', $group['duplicate_rule_ids']),
            $group['protected'] ? 'PROTECTED' : 'UNUSED',
            $group['transaction_count'],
            $group['journal_line_count'],
        ])->all();
        $this->table(['Policy version', 'Canonical', 'Duplicates', 'Usage', 'Transactions', 'Journal lines'], $rows);

        if (! $this->option('apply')) {
            $this->info("Audit selesai: {$audit['duplicate_count']} duplicate rule; {$audit['protected_count']} protected group. Database tidak diubah.");

            return self::SUCCESS;
        }

        try {
            $result = $service->clean($entityId);
        } catch (FinancialDomainException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
        $this->info("Cleanup selesai: {$result['removed_count']} unused duplicate rule dihapus. Financial facts tidak berubah.");

        return self::SUCCESS;
    }

    private function resolveEntityId(?string $entity): ?string
    {
        if (! $entity) {
            return null;
        }

        return AccountingEntity::query()
            ->where(fn ($query) => $query->whereKey($entity)->orWhere('code', $entity))
            ->value('id') ?? throw new FinancialDomainException('E-MASTER-ENTITY-SCOPE', 'Entitas Financial V2 tidak ditemukan.');
    }
}
