<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\CostCenter;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\FundRestriction;
use App\Models\FinancialV2\FundType;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * UI adapter for governed Financial V2 master configuration.
 *
 * This controller never writes financial facts. Financial account, fund,
 * program, category, and policy changes are delegated to the master-data and
 * governance services, both of which append to the existing audit trail.
 */
final class FinancialMasterDataController
{
    public function __construct(
        private readonly FinancialMasterDataService $masters,
        private readonly MasterDataGovernanceService $governance,
    ) {}

    public function accounts(Request $request)
    {
        $context = $this->context($request->query('entity'));

        return view('masjid.mrj.admin.financial-v2.masters.accounts', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'financialAccounts' => $context['entity'] ? FinancialAccount::query()->forEntity($context['entity']->id)->with(['account', 'bankDetail', 'cashDetail'])->orderBy('name')->get() : collect(),
            'liquidityAccounts' => $context['entity'] ? Account::query()->forEntity($context['entity']->id)->where('is_liquidity_account', true)->orderBy('code')->get() : collect(),
        ]);
    }

    public function storeAccount(Request $request)
    {
        return $this->perform($request, 'accounts', function (AccountingEntity $entity) use ($request) {
            $account = $this->masters->createFinancialAccount($entity->id, $this->financialAccountInput($request), $request->user()?->id);

            return ['Rekening/Kas disimpan sebagai draft. Aktifkan setelah konfigurasi lengkap.', ['financial_account_id' => $account->id]];
        });
    }

    public function updateAccount(Request $request, string $financialAccount)
    {
        return $this->perform($request, 'accounts', function (AccountingEntity $entity) use ($request, $financialAccount) {
            $account = $this->masters->updateFinancialAccount($entity->id, $financialAccount, $this->financialAccountInput($request), $request->user()?->id);

            return ['Rekening/Kas diperbarui.', ['financial_account_id' => $account->id]];
        });
    }

    public function activateAccount(Request $request, string $financialAccount)
    {
        return $this->perform($request, 'accounts', function (AccountingEntity $entity) use ($request, $financialAccount) {
            $date = $request->validate(['effective_date' => ['required', 'date']])['effective_date'];
            $this->ensureScoped(FinancialAccount::class, $entity->id, $financialAccount);
            $account = $this->governance->activateFinancialAccount($financialAccount, $date, $request->user()?->id);

            return ['Rekening/Kas aktif dan siap dipilih pada transaksi baru.', ['financial_account_id' => $account->id]];
        });
    }

    public function deactivateAccount(Request $request, string $financialAccount)
    {
        return $this->perform($request, 'accounts', function (AccountingEntity $entity) use ($request, $financialAccount) {
            $date = $request->validate(['effective_date' => ['required', 'date']])['effective_date'];
            $account = $this->masters->deactivateFinancialAccount($entity->id, $financialAccount, $date, $request->user()?->id);

            return ['Rekening/Kas dinonaktifkan. Riwayat transaksi tetap dipertahankan.', ['financial_account_id' => $account->id]];
        });
    }

    public function funds(Request $request)
    {
        $context = $this->context($request->query('entity'));
        $entityId = $context['entity']?->id;

        return view('masjid.mrj.admin.financial-v2.masters.funds', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'funds' => $entityId ? Fund::query()->forEntity($entityId)->with(['type', 'restriction', 'policyVersions'])->orderBy('name')->get() : collect(),
            'fundTypes' => $entityId ? FundType::query()->forEntity($entityId)->orderBy('name')->get() : collect(),
            'restrictions' => $entityId ? FundRestriction::query()->forEntity($entityId)->orderBy('name')->get() : collect(),
        ]);
    }

    public function storeFundType(Request $request)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request) {
            $type = $this->masters->createFundType($entity->id, $this->fundTypeInput($request), $request->user()?->id);

            return ['Klasifikasi Dana disimpan.', ['fund_type_id' => $type->id]];
        });
    }

    public function updateFundType(Request $request, string $fundType)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request, $fundType) {
            $type = $this->masters->updateFundType($entity->id, $fundType, $this->fundTypeInput($request), $request->user()?->id);

            return ['Klasifikasi Dana diperbarui.', ['fund_type_id' => $type->id]];
        });
    }

    public function storeRestriction(Request $request)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request) {
            $restriction = $this->masters->createFundRestriction($entity->id, $this->restrictionInput($request), $request->user()?->id);

            return ['Aturan pembatasan Dana disimpan.', ['fund_restriction_id' => $restriction->id]];
        });
    }

    public function updateRestriction(Request $request, string $restriction)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request, $restriction) {
            $restriction = $this->masters->updateFundRestriction($entity->id, $restriction, $this->restrictionInput($request), $request->user()?->id);

            return ['Aturan pembatasan Dana diperbarui.', ['fund_restriction_id' => $restriction->id]];
        });
    }

    public function storeFund(Request $request)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request) {
            $fund = $this->masters->createFund($entity->id, $this->fundInput($request), $request->user()?->id);

            return ['Dana disimpan sebagai draft. Dana tidak membentuk saldo sampai transaksi resmi diposting.', ['fund_id' => $fund->id]];
        });
    }

    public function updateFund(Request $request, string $fund)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request, $fund) {
            $fund = $this->masters->updateFund($entity->id, $fund, $this->fundInput($request), $request->user()?->id);

            return ['Dana diperbarui.', ['fund_id' => $fund->id]];
        });
    }

    public function activateFund(Request $request, string $fund)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request, $fund) {
            $date = $request->validate(['effective_date' => ['required', 'date']])['effective_date'];
            $this->ensureScoped(Fund::class, $entity->id, $fund);
            $fund = $this->governance->activateFund($fund, $date, $request->user()?->id);

            return ['Dana aktif dan dapat dipilih jika aturan serta periode mengizinkan.', ['fund_id' => $fund->id]];
        });
    }

    public function deactivateFund(Request $request, string $fund)
    {
        return $this->perform($request, 'funds', function (AccountingEntity $entity) use ($request, $fund) {
            $date = $request->validate(['effective_date' => ['required', 'date']])['effective_date'];
            $fund = $this->masters->deactivateFund($entity->id, $fund, $date, $request->user()?->id);

            return ['Dana dinonaktifkan. Riwayat dan saldo resmi di ledger tetap utuh.', ['fund_id' => $fund->id]];
        });
    }

    public function policies(Request $request)
    {
        $context = $this->context($request->query('entity'));
        $entityId = $context['entity']?->id;

        return view('masjid.mrj.admin.financial-v2.masters.policies', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'versions' => $entityId ? FundPolicyVersion::query()->forEntity($entityId)->with(['fund.type', 'rules'])->orderByDesc('effective_from')->get() : collect(),
            'funds' => $entityId ? Fund::query()->forEntity($entityId)->orderBy('name')->get() : collect(),
            'transactionTypes' => $entityId ? TransactionType::query()->forEntity($entityId)->where('status', 'active')->orderBy('name')->get() : collect(),
            'accounts' => $entityId ? Account::query()->forEntity($entityId)->where('status', 'active')->orderBy('code')->get() : collect(),
            'categories' => $entityId ? Category::query()->forEntity($entityId)->where('status', 'active')->orderBy('name')->get() : collect(),
            'programs' => $entityId ? Program::query()->forEntity($entityId)->where('status', 'active')->orderBy('name')->get() : collect(),
        ]);
    }

    public function storePolicy(Request $request)
    {
        return $this->perform($request, 'policies', function (AccountingEntity $entity) use ($request) {
            $version = $this->masters->createFundPolicyVersion($entity->id, $this->policyInput($request, true), $request->user()?->id);

            return ['Versi Aturan Dana disimpan sebagai draft.', ['fund_policy_version_id' => $version->id]];
        });
    }

    public function updatePolicy(Request $request, string $policyVersion)
    {
        return $this->perform($request, 'policies', function (AccountingEntity $entity) use ($request, $policyVersion) {
            $version = $this->masters->updateFundPolicyVersion($entity->id, $policyVersion, $this->policyInput($request, false), $request->user()?->id);

            return ['Versi Aturan Dana diperbarui.', ['fund_policy_version_id' => $version->id]];
        });
    }

    public function makePolicyEffective(Request $request, string $policyVersion)
    {
        return $this->perform($request, 'policies', function (AccountingEntity $entity) use ($request, $policyVersion) {
            $this->ensureScoped(FundPolicyVersion::class, $entity->id, $policyVersion);
            $version = $this->governance->makeFundPolicyVersionEffective($policyVersion, $request->user()?->id);

            return ['Versi Aturan Dana kini berlaku. Perubahan berikutnya harus dibuat sebagai versi baru.', ['fund_policy_version_id' => $version->id]];
        });
    }

    public function storePolicyRule(Request $request, string $policyVersion)
    {
        return $this->perform($request, 'policies', function (AccountingEntity $entity) use ($request, $policyVersion) {
            $rule = $this->masters->createFundPolicyRule($entity->id, $policyVersion, $this->policyRuleInput($request), $request->user()?->id);

            return ['Aturan Dana disimpan.', ['fund_policy_rule_id' => $rule->id]];
        });
    }

    public function updatePolicyRule(Request $request, string $policyRule)
    {
        return $this->perform($request, 'policies', function (AccountingEntity $entity) use ($request, $policyRule) {
            $rule = $this->masters->updateFundPolicyRule($entity->id, $policyRule, $this->policyRuleInput($request), $request->user()?->id);

            return ['Aturan Dana diperbarui.', ['fund_policy_rule_id' => $rule->id]];
        });
    }

    public function programs(Request $request)
    {
        $context = $this->context($request->query('entity'));
        $entityId = $context['entity']?->id;

        return view('masjid.mrj.admin.financial-v2.masters.programs', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'programs' => $entityId ? Program::query()->forEntity($entityId)->with('costCenter')->orderBy('name')->get() : collect(),
            'costCenters' => $entityId ? CostCenter::query()->forEntity($entityId)->where('status', 'active')->orderBy('name')->get() : collect(),
        ]);
    }

    public function storeProgram(Request $request)
    {
        return $this->perform($request, 'programs', function (AccountingEntity $entity) use ($request) {
            $program = $this->masters->createProgram($entity->id, $this->programInput($request), $request->user()?->id);

            return ['Program disimpan sebagai draft. Program bukan Dana dan tidak membentuk saldo.', ['program_id' => $program->id]];
        });
    }

    public function updateProgram(Request $request, string $program)
    {
        return $this->perform($request, 'programs', function (AccountingEntity $entity) use ($request, $program) {
            $program = $this->masters->updateProgram($entity->id, $program, $this->programInput($request), $request->user()?->id);

            return ['Program diperbarui.', ['program_id' => $program->id]];
        });
    }

    public function activateProgram(Request $request, string $program)
    {
        return $this->perform($request, 'programs', function (AccountingEntity $entity) use ($request, $program) {
            $this->ensureScoped(Program::class, $entity->id, $program);
            $program = $this->governance->activateProgram($program, $request->user()?->id);

            return ['Program aktif dan dapat dipilih pada transaksi baru.', ['program_id' => $program->id]];
        });
    }

    public function deactivateProgram(Request $request, string $program)
    {
        return $this->perform($request, 'programs', function (AccountingEntity $entity) use ($request, $program) {
            $program = $this->masters->deactivateProgram($entity->id, $program, $request->user()?->id);

            return ['Program dinonaktifkan. Riwayat penggunaan tetap dipertahankan.', ['program_id' => $program->id]];
        });
    }

    public function categories(Request $request)
    {
        $context = $this->context($request->query('entity'));
        $entityId = $context['entity']?->id;

        return view('masjid.mrj.admin.financial-v2.masters.categories', [
            'entities' => $context['entities'],
            'entity' => $context['entity'],
            'categories' => $entityId ? Category::query()->forEntity($entityId)->orderBy('name')->get() : collect(),
            'transactionTypes' => $entityId ? TransactionType::query()->forEntity($entityId)->where('status', 'active')->orderBy('name')->get() : collect(),
            'postingRules' => $entityId ? PostingRule::query()->forEntity($entityId)->where('status', 'active')->orderBy('name')->get() : collect(),
        ]);
    }

    public function storeCategory(Request $request)
    {
        return $this->perform($request, 'categories', function (AccountingEntity $entity) use ($request) {
            $category = $this->masters->createCategory($entity->id, $this->categoryInput($request), $request->user()?->id);

            return ['Kategori transaksi disimpan.', ['category_id' => $category->id]];
        });
    }

    public function updateCategory(Request $request, string $category)
    {
        return $this->perform($request, 'categories', function (AccountingEntity $entity) use ($request, $category) {
            $category = $this->masters->updateCategory($entity->id, $category, $this->categoryInput($request), $request->user()?->id);

            return ['Kategori transaksi diperbarui.', ['category_id' => $category->id]];
        });
    }

    public function deactivateCategory(Request $request, string $category)
    {
        return $this->perform($request, 'categories', function (AccountingEntity $entity) use ($request, $category) {
            $category = $this->masters->deactivateCategory($entity->id, $category, $request->user()?->id);

            return ['Kategori transaksi dinonaktifkan. Riwayat penggunaan tetap dipertahankan.', ['category_id' => $category->id]];
        });
    }

    /** @return array<string, mixed> */
    private function financialAccountInput(Request $request): array
    {
        return $request->validate([
            'account_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'account_type' => ['required', Rule::in(['bank', 'cash', 'petty_cash', 'e_wallet'])],
            'custodian_reference' => ['nullable', 'string', 'max:100'],
            'currency_code' => ['required', 'string', 'size:3'],
            'opening_date' => ['required', 'date'],
            'closing_date' => ['nullable', 'date', 'after_or_equal:opening_date'],
            'bank_name' => [Rule::requiredIf($request->input('account_type') === 'bank'), 'nullable', 'string', 'max:160'],
            'branch_name' => ['nullable', 'string', 'max:160'],
            'account_number_masked' => [Rule::requiredIf($request->input('account_type') === 'bank'), 'nullable', 'string', 'max:80'],
            'account_number_protected_ref' => ['nullable', 'string', 'max:500'],
            'cash_location' => [Rule::requiredIf(in_array($request->input('account_type'), ['cash', 'petty_cash'], true)), 'nullable', 'string', 'max:240'],
            'cash_count_frequency' => [Rule::requiredIf(in_array($request->input('account_type'), ['cash', 'petty_cash'], true)), 'nullable', Rule::in(['daily', 'weekly', 'monthly', 'ad_hoc'])],
            'petty_cash_limit' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
        ], $this->messages());
    }

    /** @return array<string, mixed> */
    private function fundTypeInput(Request $request): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'classification' => ['required', Rule::in(['unrestricted', 'designated', 'restricted', 'perpetual_restricted', 'custodial', 'syariah'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], $this->messages());
    }

    /** @return array<string, mixed> */
    private function restrictionInput(Request $request): array
    {
        return $request->validate([
            'fund_type_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'severity' => ['required', Rule::in(['low', 'medium', 'high', 'critical'])],
            'policy_basis' => ['required', 'string'],
            'status' => ['required', Rule::in(['draft', 'active', 'retired'])],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], $this->messages());
    }

    /** @return array<string, mixed> */
    private function fundInput(Request $request): array
    {
        return $request->validate([
            'fund_type_id' => ['required', 'uuid'],
            'fund_restriction_id' => ['required', 'uuid'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'purpose_statement' => ['required', 'string'],
            'prohibited_use_statement' => ['nullable', 'string'],
            'minimum_balance_policy' => ['nullable', 'regex:/^\d+(\.\d{1,2})?$/'],
            'allow_negative_balance' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], $this->messages()) + ['allow_negative_balance' => $request->boolean('allow_negative_balance')];
    }

    /** @return array<string, mixed> */
    private function policyInput(Request $request, bool $includeFund): array
    {
        $rules = [
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'policy_document_ref' => ['required', 'string', 'max:500'],
            'allowed_matrix_ref' => ['nullable', 'string', 'max:500'],
            'exception_approval_level' => ['required', 'string', 'max:80'],
        ];
        if ($includeFund) {
            $rules['fund_id'] = ['required', 'uuid'];
        }

        return $request->validate($rules, $this->messages());
    }

    /** @return array<string, mixed> */
    private function policyRuleInput(Request $request): array
    {
        return $request->validate([
            'transaction_type_id' => ['required', 'uuid'],
            'account_id' => ['nullable', 'uuid'],
            'category_id' => ['nullable', 'uuid'],
            'program_id' => ['nullable', 'uuid'],
            'decision' => ['required', Rule::in(['allowed', 'prohibited'])],
            'rationale' => ['nullable', 'string'],
        ], $this->messages()) + ['cost_center_id' => null];
    }

    /** @return array<string, mixed> */
    private function programInput(Request $request): array
    {
        return $request->validate([
            'cost_center_id' => ['nullable', 'uuid'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'program_owner_reference' => ['nullable', 'string', 'max:100'],
        ], $this->messages());
    }

    /** @return array<string, mixed> */
    private function categoryInput(Request $request): array
    {
        return $request->validate([
            'transaction_type_id' => ['nullable', 'uuid'],
            'default_posting_rule_id' => ['nullable', 'uuid'],
            'code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9][A-Za-z0-9._-]*$/'],
            'name' => ['required', 'string', 'max:160'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'valid_from' => ['nullable', 'date'],
            'valid_to' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], $this->messages());
    }

    /** @param callable(AccountingEntity): array{0: string, 1: array<string, mixed>} $callback */
    private function perform(Request $request, string $page, callable $callback)
    {
        try {
            $entity = $this->requiredEntity($request);
            [$message, $payload] = $callback($entity);

            return $this->success($request, $page, $entity->id, $message, $payload);
        } catch (FinancialDomainException $exception) {
            return $this->failure($request, $exception);
        } catch (QueryException $exception) {
            return $this->failure($request, new FinancialDomainException('E-MASTER-DUPLICATE', 'Kode master sudah digunakan atau data masih dirujuk oleh konfigurasi lain.'));
        }
    }

    private function requiredEntity(Request $request): AccountingEntity
    {
        $id = $request->validate(['entity' => ['required', 'uuid']], $this->messages())['entity'];
        $entity = AccountingEntity::query()->where('status', 'active')->find($id);
        abort_unless($entity, 404, 'Entitas Financial V2 aktif tidak tersedia.');

        return $entity;
    }

    /** @param class-string<\Illuminate\Database\Eloquent\Model> $model */
    private function ensureScoped(string $model, string $entityId, string $id): void
    {
        abort_unless($model::query()->where('accounting_entity_id', $entityId)->whereKey($id)->exists(), 404);
    }

    /** @return array{entities: \Illuminate\Support\Collection<int, AccountingEntity>, entity: ?AccountingEntity} */
    private function context(?string $requestedId): array
    {
        $entities = AccountingEntity::query()->where('status', 'active')->orderBy('name')->get();
        $entity = $requestedId ? $entities->firstWhere('id', $requestedId) : ($entities->count() === 1 ? $entities->first() : null);

        return compact('entities', 'entity');
    }

    /** @param array<string, mixed> $payload */
    private function success(Request $request, string $page, string $entityId, string $message, array $payload = [])
    {
        $redirect = route('financial-v2.masters.'.$page.'.index', ['entity' => $entityId]);
        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'message' => $message, 'redirect' => $redirect] + $payload);
        }

        return redirect($redirect)->with('success', $message);
    }

    private function failure(Request $request, FinancialDomainException $exception)
    {
        $message = match ($exception->failureCode) {
            'E-MASTER-REFERENCED' => 'Master ini sudah digunakan dan tidak dapat dihapus atau diubah secara langsung. Nonaktifkan untuk transaksi baru.',
            'E-MASTER-POLICY-IMMUTABLE' => 'Aturan Dana yang sudah berlaku tidak dapat diubah. Buat versi baru untuk perubahan berikutnya.',
            'E-MASTER-POLICY-DUPLICATE' => 'Aturan Dana dengan cakupan yang sama sudah ada.',
            'E-MASTER-LIQUIDITY-ACCOUNT', 'E-MASTER-FUND-CONFIGURATION', 'E-MASTER-ENTITY-SCOPE' => $exception->getMessage(),
            default => $exception->getMessage() ?: 'Master keuangan belum dapat diproses. Periksa konfigurasi yang diisi.',
        };
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => $message, 'code' => $exception->failureCode], 422);
        }

        return back()->withInput()->withErrors(['financial' => $message]);
    }

    /** @return array<string, string> */
    private function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'uuid' => ':attribute tidak valid.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'after_or_equal' => ':attribute tidak boleh lebih awal dari tanggal mulai.',
            'regex' => ':attribute berisi format yang tidak valid.',
        ];
    }
}
