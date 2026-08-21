<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\BudgetAllocation;
use App\Models\FinancialV2\CashAccountDetail;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionSplit;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Governed CRUD for Financial V2 master configuration.
 *
 * It deliberately writes master and immutable audit records only. Journal,
 * JournalLine, LedgerEntry, balances, transactions, allocation actuals, and
 * opening balances remain outside this service.
 */
final class FinancialMasterDataService
{
    public function __construct(private readonly AuditTrailService $auditTrail) {}

    /** @param array<string, mixed> $data */
    public function createFinancialAccount(string $entityId, array $data, ?int $actorUserId = null): FinancialAccount
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): FinancialAccount {
            $account = $this->liquidityAccount($entityId, $data['account_id']);
            $financialAccount = FinancialAccount::create([
                'accounting_entity_id' => $entityId,
                'account_id' => $account->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'account_type' => $data['account_type'],
                'custodian_reference' => $data['custodian_reference'] ?? null,
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'opening_date' => $data['opening_date'],
                'closing_date' => $data['closing_date'] ?? null,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->syncCustodyDetail($financialAccount, $data, $actorUserId);
            $this->record($entityId, 'financial_account_created', 'financial_account', $financialAccount, $actorUserId, null, $this->financialAccountSummary($financialAccount));

            return $financialAccount->fresh(['account', 'bankDetail', 'cashDetail']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateFinancialAccount(string $entityId, string $financialAccountId, array $data, ?int $actorUserId = null): FinancialAccount
    {
        return DB::transaction(function () use ($entityId, $financialAccountId, $data, $actorUserId): FinancialAccount {
            $financialAccount = $this->modelForEntity(FinancialAccount::class, $entityId, $financialAccountId, 'Rekening');
            $before = $this->financialAccountSummary($financialAccount);
            $referenced = $this->financialAccountIsReferenced($financialAccount->id);

            if ($referenced && ($financialAccount->code !== $data['code']
                || $financialAccount->name !== $data['name']
                || $financialAccount->account_id !== $data['account_id']
                || $financialAccount->account_type !== $data['account_type']
                || $financialAccount->opening_date->toDateString() !== $data['opening_date'])) {
                throw new FinancialDomainException('E-MASTER-REFERENCED', 'Rekening yang sudah dipakai transaksi tidak dapat mengubah kode, nama, jenis, akun pencatatan, atau tanggal mulai. Nonaktifkan bila tidak lagi digunakan.');
            }

            $account = $this->liquidityAccount($entityId, $data['account_id']);
            $financialAccount->update([
                'account_id' => $account->id,
                'code' => $data['code'],
                'name' => $data['name'],
                'custodian_reference' => $data['custodian_reference'] ?? null,
                'currency_code' => $data['currency_code'] ?? 'IDR',
                'opening_date' => $data['opening_date'],
                'closing_date' => $data['closing_date'] ?? null,
                'updated_by_user_id' => $actorUserId,
            ]);
            if (! $referenced) {
                $this->syncCustodyDetail($financialAccount, $data, $actorUserId);
            }
            $this->record($entityId, 'financial_account_updated', 'financial_account', $financialAccount, $actorUserId, $before, $this->financialAccountSummary($financialAccount->fresh()));

            return $financialAccount->fresh(['account', 'bankDetail', 'cashDetail']);
        }, 3);
    }

    public function deactivateFinancialAccount(string $entityId, string $financialAccountId, string $effectiveDate, ?int $actorUserId = null): FinancialAccount
    {
        return DB::transaction(function () use ($entityId, $financialAccountId, $effectiveDate, $actorUserId): FinancialAccount {
            $financialAccount = $this->modelForEntity(FinancialAccount::class, $entityId, $financialAccountId, 'Rekening');
            if ($financialAccount->status === 'closed') {
                throw new FinancialDomainException('E-MASTER-CLOSED', 'Rekening yang sudah ditutup tidak dapat diubah kembali dari halaman master.');
            }
            $before = $this->financialAccountSummary($financialAccount);
            $financialAccount->update([
                'status' => 'suspended',
                'closing_date' => $financialAccount->closing_date ?? $effectiveDate,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'financial_account_deactivated', 'financial_account', $financialAccount, $actorUserId, $before, $this->financialAccountSummary($financialAccount->fresh()));

            return $financialAccount->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createFundType(string $entityId, array $data, ?int $actorUserId = null): FundType
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): FundType {
            $type = FundType::create($data + [
                'accounting_entity_id' => $entityId,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'fund_type_created', 'fund_type', $type, $actorUserId, null, $this->summary($type, ['code', 'name', 'classification', 'status', 'valid_from', 'valid_to']));

            return $type;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateFundType(string $entityId, string $fundTypeId, array $data, ?int $actorUserId = null): FundType
    {
        return DB::transaction(function () use ($entityId, $fundTypeId, $data, $actorUserId): FundType {
            $type = $this->modelForEntity(FundType::class, $entityId, $fundTypeId, 'Klasifikasi dana');
            if (Fund::query()->where('fund_type_id', $type->id)->exists() && $type->classification !== $data['classification']) {
                throw new FinancialDomainException('E-MASTER-REFERENCED', 'Klasifikasi dana yang sudah dipakai tidak dapat diubah. Buat klasifikasi baru untuk kebutuhan berikutnya.');
            }
            $before = $this->summary($type, ['code', 'name', 'classification', 'status', 'valid_from', 'valid_to']);
            $type->update($data + ['updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'fund_type_updated', 'fund_type', $type, $actorUserId, $before, $this->summary($type->fresh(), ['code', 'name', 'classification', 'status', 'valid_from', 'valid_to']));

            return $type->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createFundRestriction(string $entityId, array $data, ?int $actorUserId = null): FundRestriction
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): FundRestriction {
            $type = $this->modelForEntity(FundType::class, $entityId, $data['fund_type_id'], 'Klasifikasi dana');
            $restriction = FundRestriction::create($data + [
                'accounting_entity_id' => $entityId,
                'fund_type_id' => $type->id,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'fund_restriction_created', 'fund_restriction', $restriction, $actorUserId, null, $this->summary($restriction, ['code', 'name', 'severity', 'policy_basis', 'status', 'valid_from', 'valid_to']));

            return $restriction;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateFundRestriction(string $entityId, string $restrictionId, array $data, ?int $actorUserId = null): FundRestriction
    {
        return DB::transaction(function () use ($entityId, $restrictionId, $data, $actorUserId): FundRestriction {
            $restriction = $this->modelForEntity(FundRestriction::class, $entityId, $restrictionId, 'Aturan pembatasan dana');
            $type = $this->modelForEntity(FundType::class, $entityId, $data['fund_type_id'], 'Klasifikasi dana');
            if (Fund::query()->where('fund_restriction_id', $restriction->id)->exists() && $restriction->fund_type_id !== $type->id) {
                throw new FinancialDomainException('E-MASTER-REFERENCED', 'Aturan pembatasan yang sudah dipakai Dana tidak dapat dipindah ke klasifikasi lain.');
            }
            $before = $this->summary($restriction, ['fund_type_id', 'code', 'name', 'severity', 'policy_basis', 'status', 'valid_from', 'valid_to']);
            $restriction->update($data + ['fund_type_id' => $type->id, 'updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'fund_restriction_updated', 'fund_restriction', $restriction, $actorUserId, $before, $this->summary($restriction->fresh(), ['fund_type_id', 'code', 'name', 'severity', 'policy_basis', 'status', 'valid_from', 'valid_to']));

            return $restriction->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createFund(string $entityId, array $data, ?int $actorUserId = null): Fund
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): Fund {
            [$type, $restriction] = $this->fundConfiguration($entityId, $data['fund_type_id'], $data['fund_restriction_id']);
            $fund = Fund::create($data + [
                'accounting_entity_id' => $entityId,
                'fund_type_id' => $type->id,
                'fund_restriction_id' => $restriction->id,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'fund_created', 'fund', $fund, $actorUserId, null, $this->fundSummary($fund));

            return $fund->fresh(['type', 'restriction']);
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateFund(string $entityId, string $fundId, array $data, ?int $actorUserId = null): Fund
    {
        return DB::transaction(function () use ($entityId, $fundId, $data, $actorUserId): Fund {
            $fund = $this->modelForEntity(Fund::class, $entityId, $fundId, 'Dana');
            if ($fund->status === 'active') {
                throw new FinancialDomainException('E-MASTER-ACTIVE', 'Dana aktif tidak dapat diubah langsung. Nonaktifkan atau buat versi policy berikutnya sesuai tata kelola.');
            }
            [$type, $restriction] = $this->fundConfiguration($entityId, $data['fund_type_id'], $data['fund_restriction_id']);
            $before = $this->fundSummary($fund);
            $fund->update($data + ['fund_type_id' => $type->id, 'fund_restriction_id' => $restriction->id, 'updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'fund_updated', 'fund', $fund, $actorUserId, $before, $this->fundSummary($fund->fresh()));

            return $fund->fresh(['type', 'restriction']);
        }, 3);
    }

    /**
     * Renames an active Fund without reopening the rest of its governed
     * configuration. The code, classification, restriction, lifecycle dates,
     * and every historical relationship remain untouched.
     */
    public function renameActiveFund(string $entityId, string $fundId, string $name, string $reason, ?int $actorUserId = null): Fund
    {
        return DB::transaction(function () use ($entityId, $fundId, $name, $reason, $actorUserId): Fund {
            $fund = $this->modelForEntity(Fund::class, $entityId, $fundId, 'Dana');
            $name = trim($name);
            $reason = trim($reason);

            if ($fund->status !== 'active') {
                throw new FinancialDomainException('E-MASTER-ACTIVE-RENAME', 'Hanya Dana aktif yang dapat menggunakan koreksi nama terkontrol.');
            }
            if ($name === '' || Str::length($name) > 160 || $reason === '') {
                throw new FinancialDomainException('E-MASTER-RENAME-INPUT', 'Koreksi nama Dana memerlukan nama valid dan alasan audit.');
            }
            if ($fund->name === $name) {
                return $fund->fresh(['type', 'restriction']);
            }

            $before = $this->fundSummary($fund);
            $fund->update([
                'name' => $name,
                'updated_by_user_id' => $actorUserId,
            ]);
            $after = $this->fundSummary($fund->fresh());
            $after['rename_reason'] = $reason;
            $this->record($entityId, 'fund_active_renamed', 'fund', $fund, $actorUserId, $before, $after);

            return $fund->fresh(['type', 'restriction']);
        }, 3);
    }

    public function deactivateFund(string $entityId, string $fundId, string $effectiveDate, ?int $actorUserId = null): Fund
    {
        return DB::transaction(function () use ($entityId, $fundId, $effectiveDate, $actorUserId): Fund {
            $fund = $this->modelForEntity(Fund::class, $entityId, $fundId, 'Dana');
            if ($fund->status === 'closed') {
                throw new FinancialDomainException('E-MASTER-CLOSED', 'Dana yang sudah ditutup tidak dapat dinonaktifkan kembali.');
            }
            $before = $this->fundSummary($fund);
            $fund->update(['status' => 'suspended', 'valid_to' => $fund->valid_to ?? $effectiveDate, 'updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'fund_deactivated', 'fund', $fund, $actorUserId, $before, $this->fundSummary($fund->fresh()));

            return $fund->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createFundPolicyVersion(string $entityId, array $data, ?int $actorUserId = null): FundPolicyVersion
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): FundPolicyVersion {
            $fund = $this->modelForEntity(Fund::class, $entityId, $data['fund_id'], 'Dana');
            $version = FundPolicyVersion::create($data + [
                'accounting_entity_id' => $entityId,
                'fund_id' => $fund->id,
                'version_no' => ((int) FundPolicyVersion::query()->where('fund_id', $fund->id)->max('version_no')) + 1,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'fund_policy_version_created', 'fund_policy_version', $version, $actorUserId, null, $this->policySummary($version));

            return $version;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateFundPolicyVersion(string $entityId, string $versionId, array $data, ?int $actorUserId = null): FundPolicyVersion
    {
        return DB::transaction(function () use ($entityId, $versionId, $data, $actorUserId): FundPolicyVersion {
            $version = $this->modelForEntity(FundPolicyVersion::class, $entityId, $versionId, 'Versi aturan Dana');
            if ($version->status !== 'draft') {
                throw new FinancialDomainException('E-MASTER-POLICY-IMMUTABLE', 'Versi aturan Dana yang sudah berlaku tidak dapat diubah. Buat versi baru dengan tanggal berlaku berikutnya.');
            }
            $before = $this->policySummary($version);
            $version->update($data + ['updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'fund_policy_version_updated', 'fund_policy_version', $version, $actorUserId, $before, $this->policySummary($version->fresh()));

            return $version->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createFundPolicyRule(string $entityId, string $policyVersionId, array $data, ?int $actorUserId = null): FundPolicyRule
    {
        return DB::transaction(function () use ($entityId, $policyVersionId, $data, $actorUserId): FundPolicyRule {
            $version = $this->modelForEntity(FundPolicyVersion::class, $entityId, $policyVersionId, 'Versi aturan Dana');
            if ($version->status !== 'draft') {
                throw new FinancialDomainException('E-MASTER-POLICY-IMMUTABLE', 'Aturan hanya dapat ditambah pada versi Dana yang masih draft.');
            }
            $this->policyDimensionsForEntity($entityId, $data);
            $duplicate = FundPolicyRule::query()
                ->where('fund_policy_version_id', $version->id)
                ->where('transaction_type_id', $data['transaction_type_id'])
                ->where(fn ($query) => $this->matchNullable($query, 'account_id', $data['account_id'] ?? null))
                ->where(fn ($query) => $this->matchNullable($query, 'category_id', $data['category_id'] ?? null))
                ->where(fn ($query) => $this->matchNullable($query, 'program_id', $data['program_id'] ?? null))
                ->where(fn ($query) => $this->matchNullable($query, 'cost_center_id', $data['cost_center_id'] ?? null))
                ->exists();
            if ($duplicate) {
                throw new FinancialDomainException('E-MASTER-POLICY-DUPLICATE', 'Kombinasi aturan Dana ini sudah ada pada versi yang dipilih.');
            }
            $rule = FundPolicyRule::create($data + [
                'accounting_entity_id' => $entityId,
                'fund_policy_version_id' => $version->id,
            ]);
            $this->record($entityId, 'fund_policy_rule_created', 'fund_policy_rule', $rule, $actorUserId, null, $this->ruleSummary($rule));

            return $rule;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateFundPolicyRule(string $entityId, string $ruleId, array $data, ?int $actorUserId = null): FundPolicyRule
    {
        return DB::transaction(function () use ($entityId, $ruleId, $data, $actorUserId): FundPolicyRule {
            $rule = $this->modelForEntity(FundPolicyRule::class, $entityId, $ruleId, 'Aturan Dana');
            $version = $this->modelForEntity(FundPolicyVersion::class, $entityId, $rule->fund_policy_version_id, 'Versi aturan Dana');
            if ($version->status !== 'draft') {
                throw new FinancialDomainException('E-MASTER-POLICY-IMMUTABLE', 'Aturan pada versi Dana yang sudah berlaku tidak dapat diubah.');
            }
            $this->policyDimensionsForEntity($entityId, $data);
            $duplicate = FundPolicyRule::query()
                ->where('fund_policy_version_id', $version->id)
                ->where('transaction_type_id', $data['transaction_type_id'])
                ->where(fn ($query) => $this->matchNullable($query, 'account_id', $data['account_id'] ?? null))
                ->where(fn ($query) => $this->matchNullable($query, 'category_id', $data['category_id'] ?? null))
                ->where(fn ($query) => $this->matchNullable($query, 'program_id', $data['program_id'] ?? null))
                ->where(fn ($query) => $this->matchNullable($query, 'cost_center_id', $data['cost_center_id'] ?? null))
                ->where('id', '!=', $rule->id)
                ->exists();
            if ($duplicate) {
                throw new FinancialDomainException('E-MASTER-POLICY-DUPLICATE', 'Kombinasi aturan Dana ini sudah ada pada versi yang dipilih.');
            }
            $before = $this->ruleSummary($rule);
            $rule->update($data);
            $this->record($entityId, 'fund_policy_rule_updated', 'fund_policy_rule', $rule, $actorUserId, $before, $this->ruleSummary($rule->fresh()));

            return $rule->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createProgram(string $entityId, array $data, ?int $actorUserId = null): Program
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): Program {
            $program = Program::create($data + [
                'accounting_entity_id' => $entityId,
                'status' => 'draft',
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'program_created', 'program', $program, $actorUserId, null, $this->programSummary($program));

            return $program;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateProgram(string $entityId, string $programId, array $data, ?int $actorUserId = null): Program
    {
        return DB::transaction(function () use ($entityId, $programId, $data, $actorUserId): Program {
            $program = $this->modelForEntity(Program::class, $entityId, $programId, 'Program');
            if ($program->status === 'active' && $this->programIsReferenced($program->id)) {
                throw new FinancialDomainException('E-MASTER-REFERENCED', 'Program aktif yang sudah dipakai transaksi tidak dapat diubah langsung. Nonaktifkan atau buat Program baru untuk konfigurasi berikutnya.');
            }
            $before = $this->programSummary($program);
            $program->update($data + ['updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'program_updated', 'program', $program, $actorUserId, $before, $this->programSummary($program->fresh()));

            return $program->fresh();
        }, 3);
    }

    public function deactivateProgram(string $entityId, string $programId, ?int $actorUserId = null): Program
    {
        return DB::transaction(function () use ($entityId, $programId, $actorUserId): Program {
            $program = $this->modelForEntity(Program::class, $entityId, $programId, 'Program');
            if ($program->status === 'closed') {
                throw new FinancialDomainException('E-MASTER-CLOSED', 'Program yang sudah ditutup tidak dapat dinonaktifkan kembali.');
            }
            $before = $this->programSummary($program);
            $program->update(['status' => 'suspended', 'updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'program_deactivated', 'program', $program, $actorUserId, $before, $this->programSummary($program->fresh()));

            return $program->fresh();
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function createCategory(string $entityId, array $data, ?int $actorUserId = null): Category
    {
        return DB::transaction(function () use ($entityId, $data, $actorUserId): Category {
            $this->categoryDimensionsForEntity($entityId, $data);
            $category = Category::create($data + [
                'accounting_entity_id' => $entityId,
                'created_by_user_id' => $actorUserId,
                'updated_by_user_id' => $actorUserId,
            ]);
            $this->record($entityId, 'category_created', 'category', $category, $actorUserId, null, $this->categorySummary($category));

            return $category;
        }, 3);
    }

    /** @param array<string, mixed> $data */
    public function updateCategory(string $entityId, string $categoryId, array $data, ?int $actorUserId = null): Category
    {
        return DB::transaction(function () use ($entityId, $categoryId, $data, $actorUserId): Category {
            $category = $this->modelForEntity(Category::class, $entityId, $categoryId, 'Kategori');
            if ($this->categoryIsReferenced($category->id) && ($category->code !== $data['code'] || $category->name !== $data['name'] || $category->transaction_type_id !== ($data['transaction_type_id'] ?? null))) {
                throw new FinancialDomainException('E-MASTER-REFERENCED', 'Kategori yang sudah dipakai transaksi tidak dapat mengubah kode, nama, atau jenis transaksi. Nonaktifkan bila tidak lagi digunakan.');
            }
            $this->categoryDimensionsForEntity($entityId, $data);
            $before = $this->categorySummary($category);
            $category->update($data + ['updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'category_updated', 'category', $category, $actorUserId, $before, $this->categorySummary($category->fresh()));

            return $category->fresh();
        }, 3);
    }

    public function deactivateCategory(string $entityId, string $categoryId, ?int $actorUserId = null): Category
    {
        return DB::transaction(function () use ($entityId, $categoryId, $actorUserId): Category {
            $category = $this->modelForEntity(Category::class, $entityId, $categoryId, 'Kategori');
            $before = $this->categorySummary($category);
            $category->update(['status' => 'inactive', 'updated_by_user_id' => $actorUserId]);
            $this->record($entityId, 'category_deactivated', 'category', $category, $actorUserId, $before, $this->categorySummary($category->fresh()));

            return $category->fresh();
        }, 3);
    }

    private function liquidityAccount(string $entityId, string $accountId): Account
    {
        $account = Account::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($accountId);
        if (! $account || ! $account->is_liquidity_account) {
            throw new FinancialDomainException('E-MASTER-LIQUIDITY-ACCOUNT', 'Rekening/Kas harus dihubungkan ke Akun likuiditas yang sesuai pada entitas yang sama.');
        }

        return $account;
    }

    /** @return array{0: FundType, 1: FundRestriction} */
    private function fundConfiguration(string $entityId, string $typeId, string $restrictionId): array
    {
        $type = $this->modelForEntity(FundType::class, $entityId, $typeId, 'Klasifikasi dana');
        $restriction = $this->modelForEntity(FundRestriction::class, $entityId, $restrictionId, 'Aturan pembatasan dana');
        if ($restriction->fund_type_id !== $type->id) {
            throw new FinancialDomainException('E-MASTER-FUND-CONFIGURATION', 'Aturan pembatasan Dana harus berasal dari klasifikasi Dana yang dipilih.');
        }

        return [$type, $restriction];
    }

    /** @param array<string, mixed> $data */
    private function syncCustodyDetail(FinancialAccount $financialAccount, array $data, ?int $actorUserId): void
    {
        if ($financialAccount->account_type === 'bank') {
            BankAccountDetail::updateOrCreate(
                ['financial_account_id' => $financialAccount->id],
                [
                    'bank_name' => $data['bank_name'],
                    'branch_name' => $data['branch_name'] ?? null,
                    'account_number_masked' => $data['account_number_masked'],
                    'account_number_protected_ref' => $data['account_number_protected_ref'] ?? null,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ],
            );

            return;
        }

        if (in_array($financialAccount->account_type, ['cash', 'petty_cash'], true)) {
            CashAccountDetail::updateOrCreate(
                ['financial_account_id' => $financialAccount->id],
                [
                    'cash_location' => $data['cash_location'],
                    'cash_count_frequency' => $data['cash_count_frequency'],
                    'petty_cash_limit' => $data['petty_cash_limit'] ?? null,
                    'created_by_user_id' => $actorUserId,
                    'updated_by_user_id' => $actorUserId,
                ],
            );
        }
    }

    /** @param array<string, mixed> $data */
    private function policyDimensionsForEntity(string $entityId, array $data): void
    {
        $this->modelForEntity(TransactionType::class, $entityId, $data['transaction_type_id'], 'Jenis transaksi');
        foreach ([
            'account_id' => [Account::class, 'Akun'],
            'category_id' => [Category::class, 'Kategori'],
            'program_id' => [Program::class, 'Program'],
        ] as $field => [$model, $label]) {
            if (! empty($data[$field])) {
                $this->modelForEntity($model, $entityId, $data[$field], $label);
            }
        }
    }

    /** @param array<string, mixed> $data */
    private function categoryDimensionsForEntity(string $entityId, array $data): void
    {
        if (! empty($data['transaction_type_id'])) {
            $this->modelForEntity(TransactionType::class, $entityId, $data['transaction_type_id'], 'Jenis transaksi');
        }
        if (! empty($data['default_posting_rule_id'])) {
            $this->modelForEntity(PostingRule::class, $entityId, $data['default_posting_rule_id'], 'Aturan pencatatan default');
        }
    }

    private function financialAccountIsReferenced(string $financialAccountId): bool
    {
        return FinancialTransaction::query()->where('primary_financial_account_id', $financialAccountId)->exists()
            || JournalLine::query()->where('financial_account_id', $financialAccountId)->exists()
            || DB::table('financial_v2_treasury_transfers')->where('source_financial_account_id', $financialAccountId)->orWhere('destination_financial_account_id', $financialAccountId)->exists()
            || DB::table('financial_v2_opening_balance_lines')->where('financial_account_id', $financialAccountId)->exists()
            || DB::table('financial_v2_reconciliations')->where('financial_account_id', $financialAccountId)->exists();
    }

    private function programIsReferenced(string $programId): bool
    {
        return TransactionSplit::query()->where('program_id', $programId)->exists()
            || JournalLine::query()->where('program_id', $programId)->exists()
            || BudgetAllocation::query()->where('program_id', $programId)->exists();
    }

    private function categoryIsReferenced(string $categoryId): bool
    {
        return FinancialTransaction::query()->where('category_id', $categoryId)->exists()
            || JournalLine::query()->where('category_id', $categoryId)->exists()
            || BudgetAllocation::query()->where('category_id', $categoryId)->exists();
    }

    /** @template T of Model @param class-string<T> $modelClass @return T */
    private function modelForEntity(string $modelClass, string $entityId, string $id, string $label): Model
    {
        $model = $modelClass::query()->where('accounting_entity_id', $entityId)->lockForUpdate()->find($id);
        if (! $model) {
            throw new FinancialDomainException('E-MASTER-ENTITY-SCOPE', "{$label} tidak tersedia pada entitas keuangan yang dipilih.");
        }

        return $model;
    }

    private function matchNullable($query, string $column, ?string $value): void
    {
        if ($value === null) {
            $query->whereNull($column);

            return;
        }

        $query->where($column, $value);
    }

    private function record(string $entityId, string $eventType, string $targetType, Model $target, ?int $actorUserId, ?array $before, ?array $after): void
    {
        $this->auditTrail->record($entityId, $eventType, $targetType, $target->getKey(), (string) Str::uuid(), $actorUserId, $before, $after);
    }

    /** @param array<int, string> $fields @return array<string, mixed> */
    private function summary(Model $model, array $fields): array
    {
        return collect($fields)->mapWithKeys(fn (string $field) => [$field => $model->{$field} instanceof \DateTimeInterface ? $model->{$field}->format(DATE_ATOM) : $model->{$field}])->all();
    }

    /** @return array<string, mixed> */
    private function financialAccountSummary(FinancialAccount $financialAccount): array
    {
        return $this->summary($financialAccount, ['account_id', 'code', 'name', 'account_type', 'custodian_reference', 'currency_code', 'opening_date', 'closing_date', 'status']);
    }

    /** @return array<string, mixed> */
    private function fundSummary(Fund $fund): array
    {
        return $this->summary($fund, ['fund_type_id', 'fund_restriction_id', 'code', 'name', 'purpose_statement', 'prohibited_use_statement', 'minimum_balance_policy', 'allow_negative_balance', 'status', 'valid_from', 'valid_to']);
    }

    /** @return array<string, mixed> */
    private function policySummary(FundPolicyVersion $version): array
    {
        return $this->summary($version, ['fund_id', 'version_no', 'effective_from', 'effective_to', 'policy_document_ref', 'allowed_matrix_ref', 'exception_approval_level', 'status', 'approved_at']);
    }

    /** @return array<string, mixed> */
    private function ruleSummary(FundPolicyRule $rule): array
    {
        return $this->summary($rule, ['fund_policy_version_id', 'transaction_type_id', 'account_id', 'category_id', 'program_id', 'cost_center_id', 'decision', 'rationale']);
    }

    /** @return array<string, mixed> */
    private function programSummary(Program $program): array
    {
        return $this->summary($program, ['cost_center_id', 'code', 'name', 'start_date', 'end_date', 'program_owner_reference', 'status']);
    }

    /** @return array<string, mixed> */
    private function categorySummary(Category $category): array
    {
        return $this->summary($category, ['transaction_type_id', 'default_posting_rule_id', 'code', 'name', 'status', 'valid_from', 'valid_to']);
    }
}
