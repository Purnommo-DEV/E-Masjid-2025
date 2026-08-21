<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Creates the governed successor policy that permits the specifically approved
 * Zakat Maal Santunan / Bantuan Dhuafa realization. It never posts a payment
 * and it never changes an existing effective policy version.
 */
final class ConfigureMrjZakatDhuafaRealizationCommand extends Command
{
    private const EFFECTIVE_FROM = '2026-08-21';

    private const POLICY_DOCUMENT = 'PHASE-REALIZATION-OPERATIONS|ZAKAT-MAAL|BANTUAN-DHUAFA';

    private const ALLOWED_MATRIX = 'PHASE-REALIZATION-OPERATIONS-MATRIX|ZAKAT-MAAL|PAY-SANTUNAN|BANTUAN-DHUAFA';

    protected $signature = 'financial-v2:configure-mrj-zakat-dhuafa-realization
                            {--apply : Persist the governed successor policy; without this flag the command is read-only}
                            {--allow-testing : Permit execution only under testing on mrj_test_db}';

    protected $description = 'Configure the governed Zakat Maal → Bantuan Dhuafa Santunan realization policy.';

    public function handle(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): int
    {
        $this->assertSafeDatabase();

        $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->firstOrFail();
        $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', 'ZAKAT-MAAL')->firstOrFail();
        $program = Program::query()->where('accounting_entity_id', $entity->id)->where('code', 'BANTUAN-DHUAFA')->where('status', 'active')->firstOrFail();
        $payment = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY')->where('status', 'active')->firstOrFail();
        $category = Category::query()->where('accounting_entity_id', $entity->id)->where('code', 'PAY-SANTUNAN')->where('status', 'active')->firstOrFail();
        $target = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('effective_from', self::EFFECTIVE_FROM)
            ->where('policy_document_ref', self::POLICY_DOCUMENT)
            ->first();
        $predecessor = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('effective_from', '<', self::EFFECTIVE_FROM)
            ->orderByDesc('effective_from')
            ->firstOrFail();

        $this->table(['Konfigurasi', 'Status'], [
            ['Database', DB::connection()->getDatabaseName()],
            ['Dana', $fund->name],
            ['Program', $program->name],
            ['Kategori', $category->name],
            ['Policy saat ini', 'v'.$predecessor->version_no.' · '.$predecessor->status],
            ['Successor', $target ? 'v'.$target->version_no.' · '.$target->status : 'belum ada'],
        ]);

        if (! $this->option('apply')) {
            $this->info('Dry-run selesai. Tidak ada policy, transaksi, Journal, JournalLine, Ledger, atau voucher yang diubah.');

            return self::SUCCESS;
        }

        $actorId = User::query()->orderBy('id')->value('id');
        $target = DB::transaction(function () use ($masters, $governance, $entity, $fund, $program, $payment, $category, $predecessor, $target, $actorId): FundPolicyVersion {
            $successor = $target ?: $masters->createFundPolicyVersion($entity->id, [
                'fund_id' => $fund->id,
                'effective_from' => self::EFFECTIVE_FROM,
                'effective_to' => null,
                'policy_document_ref' => self::POLICY_DOCUMENT,
                'allowed_matrix_ref' => self::ALLOWED_MATRIX,
                'exception_approval_level' => 'financial-governance',
            ], $actorId);

            if (! in_array($successor->status, ['draft', 'effective'], true)
                || $successor->policy_document_ref !== self::POLICY_DOCUMENT
                || $successor->allowed_matrix_ref !== self::ALLOWED_MATRIX) {
                throw new FinancialDomainException('E-REALIZATION-POLICY', 'Successor policy exists but does not match the approved realization configuration.');
            }

            if ($successor->status === 'draft') {
                $this->copyPolicyRules($masters, $entity->id, $predecessor, $successor, $actorId);
                $this->ensureSantunanDhuafaRule($masters, $entity->id, $successor, $payment, $category, $program, $actorId);
                $successor = $governance->makeFundPolicyVersionEffective($successor->id, $actorId);
            }

            $this->assertEffectiveSuccessor($successor, $predecessor->fresh(), $payment, $category, $program);

            return $successor;
        }, 3);

        $this->info('Policy v'.$target->version_no.' efektif. Realisasi tetap harus melalui lifecycle, bukti, dan Posting Engine. Tidak ada financial fact yang dibuat oleh command ini.');

        return self::SUCCESS;
    }

    private function assertSafeDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        $testing = app()->environment('testing');
        $localActual = app()->environment(['local', 'development']) && $database === 'mrj_prod_db';
        $safeTesting = $testing && $database === 'mrj_test_db' && (bool) $this->option('allow-testing');

        if (! $localActual && ! $safeTesting) {
            throw new FinancialDomainException('E-REALIZATION-ENVIRONMENT', 'Command hanya dapat dijalankan pada local mrj_prod_db atau testing mrj_test_db dengan --allow-testing.');
        }
    }

    private function copyPolicyRules(FinancialMasterDataService $masters, string $entityId, FundPolicyVersion $source, FundPolicyVersion $target, ?int $actorId): void
    {
        FundPolicyRule::query()
            ->where('fund_policy_version_id', $source->id)
            ->orderBy('created_at')
            ->each(function (FundPolicyRule $rule) use ($masters, $entityId, $target, $actorId): void {
                $this->ensureRule($masters, $entityId, $target, [
                    'transaction_type_id' => $rule->transaction_type_id,
                    'account_id' => $rule->account_id,
                    'category_id' => $rule->category_id,
                    'program_id' => $rule->program_id,
                    'cost_center_id' => $rule->cost_center_id,
                    'decision' => $rule->decision,
                    'rationale' => $rule->rationale,
                ], $actorId);
            });
    }

    private function ensureSantunanDhuafaRule(FinancialMasterDataService $masters, string $entityId, FundPolicyVersion $policy, TransactionType $payment, Category $category, Program $program, ?int $actorId): void
    {
        $this->ensureRule($masters, $entityId, $policy, [
            'transaction_type_id' => $payment->id,
            'account_id' => null,
            'category_id' => $category->id,
            'program_id' => $program->id,
            'cost_center_id' => null,
            'decision' => 'allowed',
            'rationale' => 'Diizinkan untuk realisasi santunan Program Bantuan Dhuafa yang telah dialokasikan, didukung bukti, dan diposting melalui Posting Engine.',
        ], $actorId);
    }

    /** @param array<string, mixed> $data */
    private function ensureRule(FinancialMasterDataService $masters, string $entityId, FundPolicyVersion $policy, array $data, ?int $actorId): void
    {
        $exists = FundPolicyRule::query()
            ->where('fund_policy_version_id', $policy->id)
            ->where('transaction_type_id', $data['transaction_type_id'])
            ->where(fn ($query) => $this->whereNullable($query, 'account_id', $data['account_id']))
            ->where(fn ($query) => $this->whereNullable($query, 'category_id', $data['category_id']))
            ->where(fn ($query) => $this->whereNullable($query, 'program_id', $data['program_id']))
            ->where(fn ($query) => $this->whereNullable($query, 'cost_center_id', $data['cost_center_id']))
            ->exists();
        if (! $exists) {
            $masters->createFundPolicyRule($entityId, $policy->id, $data, $actorId);
        }
    }

    private function assertEffectiveSuccessor(FundPolicyVersion $successor, FundPolicyVersion $predecessor, TransactionType $payment, Category $category, Program $program): void
    {
        if ($successor->status !== 'effective'
            || $successor->effective_from->toDateString() !== self::EFFECTIVE_FROM
            || ! FundPolicyRule::query()->where('fund_policy_version_id', $successor->id)
                ->where('transaction_type_id', $payment->id)
                ->where('category_id', $category->id)
                ->where('program_id', $program->id)
                ->where('decision', 'allowed')
                ->exists()) {
            throw new FinancialDomainException('E-REALIZATION-POLICY', 'Successor policy is not effective with the required Santunan Bantuan Dhuafa allowance.');
        }

        if ($predecessor->id !== $successor->id && $predecessor->status === 'effective') {
            throw new FinancialDomainException('E-REALIZATION-POLICY', 'Predecessor policy was not superseded by the governed successor.');
        }
    }

    private function whereNullable($query, string $column, ?string $value): void
    {
        $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }
}
