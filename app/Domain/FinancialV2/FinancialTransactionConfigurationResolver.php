<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\ApprovalRequirement;
use App\Models\FinancialV2\BankAccountDetail;
use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\CashAccountDetail;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Read-only source of truth for transaction configuration selection.
 *
 * Resolution never provisions a policy or rule. It selects only configuration
 * that was already approved and whose date range contains the transaction date.
 */
final class FinancialTransactionConfigurationResolver
{
    /** @var array<string, string> */
    private const RULE_FAMILIES = [
        'RCV' => 'receipt',
        'PAY' => 'payment',
        'TRF' => 'treasury-transfer',
        'IFT' => 'interfund-transfer',
    ];

    public function supports(?string $transactionTypeCode): bool
    {
        return $transactionTypeCode !== null && array_key_exists($transactionTypeCode, self::RULE_FAMILIES);
    }

    /** @param array<string, mixed> $input */
    public function resolve(array $input): ResolvedFinancialTransactionConfiguration
    {
        $date = CarbonImmutable::parse((string) ($input['date'] ?? ''))->toDateString();
        $entity = AccountingEntity::query()->where('status', 'active')->find($input['accounting_entity_id'] ?? null);
        if (! $entity) {
            throw new FinancialPostingException('E-MASTER-SCOPE', 'Entitas keuangan aktif tidak tersedia.');
        }

        $type = $this->transactionType($entity->id, $input);
        $category = $this->category($entity->id, $type->id, $input['category_id'] ?? null, $date);
        $accounts = $this->financialAccounts($entity->id, $input, $date);
        $program = $this->program($entity->id, $input['program_id'] ?? null, $date);
        $funds = $this->funds($entity->id, $input, $date);
        $primaryAccount = $accounts->get('primary');
        $bankPolicy = $this->bankMutationPolicy($entity->id, $type->id, $primaryAccount?->id, $category, $funds, $date);

        $outsideTypeDates = ($type->valid_from && $type->valid_from->gt($date)) || ($type->valid_to && $type->valid_to->lt($date));
        if ($outsideTypeDates && ! $bankPolicy) {
            throw $this->missing($date, $accounts, $funds, $category, 'Jenis transaksi belum berlaku pada tanggal tersebut.');
        }

        $version = $this->postingRuleVersion($entity->id, $type, $category, $bankPolicy, $date, $accounts, $funds);
        $lines = PostingRuleLine::query()->with('account')->where('posting_rule_version_id', $version->id)->orderBy('line_no')->get();
        if ($lines->count() < 2 || $lines->contains(fn (PostingRuleLine $line): bool => ! $line->account
            || $line->account->accounting_entity_id !== $entity->id
            || $line->account->status !== 'active'
            || ! $line->account->is_posting_account
            || ($line->account->valid_from && $line->account->valid_from->gt($date))
            || ($line->account->valid_to && $line->account->valid_to->lt($date)))) {
            throw $this->missing($date, $accounts, $funds, $category, 'Aturan pencatatan belum memiliki baris akun yang lengkap.');
        }

        $businessAccountId = (string) ($lines->first(fn (PostingRuleLine $line): bool => ! $line->account->is_liquidity_account)?->account_id
            ?? $lines->first()?->account_id);
        $this->validateLineFinancialAccounts($entity->id, $lines, $accounts, $funds, $category, $date);
        $fundPolicies = $this->fundPoliciesForLines($entity->id, $lines, $funds, $type, $category, $program, $bankPolicy, $date, $accounts, $input);
        $approvalSteps = (int) (ApprovalRequirement::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('transaction_type_id', $type->id)
            ->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->max('required_steps') ?? 0);
        $approvalSteps = max($approvalSteps, (int) ($bankPolicy?->required_approval_steps ?? 0));
        $evidenceRequirements = EvidenceRequirement::query()->where('posting_rule_version_id', $version->id)->orderBy('evidence_type')->get();
        if ($bankPolicy && ! $evidenceRequirements->contains(fn (EvidenceRequirement $requirement): bool => $requirement->evidence_type === $bankPolicy->evidence_type && $requirement->minimum_count > 0)) {
            throw $this->missing($date, $accounts, $funds, $category, 'Persyaratan bukti untuk Mutasi Bank belum lengkap.');
        }

        return new ResolvedFinancialTransactionConfiguration(
            $type,
            $version,
            $businessAccountId,
            $bankPolicy,
            $fundPolicies,
            $evidenceRequirements,
            $approvalSteps,
        );
    }

    public function resolveTransaction(FinancialTransaction $transaction): ResolvedFinancialTransactionConfiguration
    {
        $transaction->loadMissing(['type', 'category', 'splits', 'treasuryTransfer', 'interfundTransfer']);
        $fundIds = $transaction->splits->pluck('fund_id')->filter()->unique()->values()->all();
        if ($transaction->type?->code === TransactionTypeCode::InterfundTransfer->value && $transaction->interfundTransfer) {
            $fundIds = array_values(array_unique([
                $transaction->interfundTransfer->source_fund_id,
                $transaction->interfundTransfer->destination_fund_id,
            ]));
        }
        $programIds = $transaction->splits->pluck('program_id')->filter()->unique()->values();
        if ($programIds->count() > 1) {
            throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan belum tersedia untuk transaksi dengan beberapa Program.');
        }

        return $this->resolve([
            'accounting_entity_id' => $transaction->accounting_entity_id,
            'transaction_type_id' => $transaction->transaction_type_id,
            'date' => $transaction->accounting_date->toDateString(),
            'financial_account_id' => $transaction->primary_financial_account_id,
            'source_financial_account_id' => $transaction->treasuryTransfer?->source_financial_account_id,
            'destination_financial_account_id' => $transaction->treasuryTransfer?->destination_financial_account_id,
            'fund_ids' => $fundIds,
            'source_fund_id' => $transaction->interfundTransfer?->source_fund_id,
            'destination_fund_id' => $transaction->interfundTransfer?->destination_fund_id,
            'category_id' => $transaction->category_id,
            'program_id' => $programIds->first(),
        ]);
    }

    /** @param array<string, mixed> $input */
    private function transactionType(string $entityId, array $input): TransactionType
    {
        $query = TransactionType::query()->where('accounting_entity_id', $entityId)->where('status', 'active');
        $type = filled($input['transaction_type_id'] ?? null)
            ? $query->find($input['transaction_type_id'])
            : $query->where('code', $input['transaction_type_code'] ?? null)->first();
        if (! $type || ! array_key_exists($type->code, self::RULE_FAMILIES)) {
            throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan belum tersedia untuk jenis transaksi ini.');
        }

        return $type;
    }

    private function category(string $entityId, string $typeId, ?string $categoryId, string $date): ?Category
    {
        if (! $categoryId) {
            return null;
        }
        $category = Category::query()->where('accounting_entity_id', $entityId)->where('status', 'active')->find($categoryId);
        if (! $category || ($category->transaction_type_id && $category->transaction_type_id !== $typeId)
            || ($category->valid_from && $category->valid_from->gt($date))
            || ($category->valid_to && $category->valid_to->lt($date))) {
            throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan belum tersedia untuk kategori dan tanggal yang dipilih.');
        }

        return $category;
    }

    /** @param array<string, mixed> $input @return Collection<string, FinancialAccount> */
    private function financialAccounts(string $entityId, array $input, string $date): Collection
    {
        $ids = collect([
            'primary' => $input['financial_account_id'] ?? null,
            'source' => $input['source_financial_account_id'] ?? null,
            'destination' => $input['destination_financial_account_id'] ?? null,
        ])->filter();
        $models = FinancialAccount::query()->where('accounting_entity_id', $entityId)->whereIn('id', $ids->values())->get()->keyBy('id');

        return $ids->map(function (string $id) use ($models, $date): FinancialAccount {
            $account = $models->get($id);
            if (! $account || ! $account->isUsableOn($date) || ($account->opening_date && $account->opening_date->gt($date))) {
                throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan belum tersedia untuk rekening dan tanggal yang dipilih.');
            }

            return $account;
        });
    }

    private function program(string $entityId, ?string $programId, string $date): ?Program
    {
        if (! $programId) {
            return null;
        }
        $program = Program::query()->where('accounting_entity_id', $entityId)->where('status', 'active')->find($programId);
        if (! $program || ($program->start_date && $program->start_date->gt($date)) || ($program->end_date && $program->end_date->lt($date))) {
            throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan belum tersedia untuk program dan tanggal yang dipilih.');
        }

        return $program;
    }

    /** @param array<string, mixed> $input @return Collection<int, Fund> */
    private function funds(string $entityId, array $input, string $date): Collection
    {
        $ids = collect($input['fund_ids'] ?? [$input['fund_id'] ?? null])->filter()->unique()->values();
        $funds = Fund::query()->with('type')->where('accounting_entity_id', $entityId)->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(function (string $id) use ($funds, $date): Fund {
            $fund = $funds->get($id);
            if (! $fund || $fund->status !== 'active' || ($fund->valid_from && $fund->valid_from->gt($date)) || ($fund->valid_to && $fund->valid_to->lt($date))) {
                throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan belum tersedia untuk Dana dan tanggal yang dipilih.');
            }

            return $fund;
        });
    }

    private function bankMutationPolicy(string $entityId, string $typeId, ?string $accountId, ?Category $category, Collection $funds, string $date): ?BankMutationPolicy
    {
        if (! $category || ! array_key_exists($category->code, BankMutationService::CATEGORY_CODES)) {
            return null;
        }
        $fund = $funds->count() === 1 ? $funds->first() : null;
        $policy = $accountId && $fund ? BankMutationPolicy::query()
            ->with('postingRuleVersion.rule')
            ->where('accounting_entity_id', $entityId)
            ->where('transaction_type_id', $typeId)
            ->where('financial_account_id', $accountId)
            ->where('fund_id', $fund->id)
            ->where('category_id', $category->id)
            ->where('status', 'active')
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')->first() : null;
        if (! $policy) {
            throw $this->missing($date, collect(['primary' => $accountId ? FinancialAccount::find($accountId) : null])->filter(), $funds, $category);
        }

        return $policy;
    }

    private function postingRuleVersion(string $entityId, TransactionType $type, ?Category $category, ?BankMutationPolicy $bankPolicy, string $date, Collection $accounts, Collection $funds): PostingRuleVersion
    {
        if ($bankPolicy) {
            $version = $bankPolicy->postingRuleVersion;
            $valid = $version && $version->accounting_entity_id === $entityId
                && $version->rule?->transaction_type_id === $type->id
                && $version->rule?->status === 'active'
                && $this->approvedVersionIsValid($version, $date);
        } else {
            $query = PostingRuleVersion::query()
                ->with('rule')
                ->where('accounting_entity_id', $entityId)
                ->where(fn (Builder $builder) => $builder->where('status', 'effective')->orWhere(fn (Builder $historical) => $historical->where('status', 'superseded')->whereNotNull('approved_at')))
                ->where('effective_from', '<=', $date)
                ->where(fn (Builder $builder) => $builder->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
                ->whereHas('rule', fn (Builder $builder) => $builder->where('transaction_type_id', $type->id)->where('status', 'active'));
            if ($category?->default_posting_rule_id) {
                $query->where('posting_rule_id', $category->default_posting_rule_id);
            } else {
                $query->whereHas('rule', fn (Builder $builder) => $builder->where('rule_family', self::RULE_FAMILIES[$type->code]));
            }
            $version = $query->orderByDesc('effective_from')->orderByDesc('version_no')->first();
            $valid = (bool) $version;
        }
        if (! $valid) {
            throw $this->missing($date, $accounts, $funds, $category);
        }

        return $version;
    }

    private function approvedVersionIsValid(PostingRuleVersion $version, string $date): bool
    {
        $approvedStatus = $version->status === 'effective' || ($version->status === 'superseded' && $version->approved_at);

        return $approvedStatus && $version->effective_from?->lte($date) && (! $version->effective_to || $version->effective_to->gte($date));
    }

    /** @param Collection<int, PostingRuleLine> $lines @param Collection<string, FinancialAccount> $accounts @param Collection<int, Fund> $funds */
    private function validateLineFinancialAccounts(string $entityId, Collection $lines, Collection $accounts, Collection $funds, ?Category $category, string $date): void
    {
        foreach ($lines as $line) {
            $financialAccount = match ($line->financial_account_source) {
                'transaction_primary' => $accounts->get('primary'),
                'transfer_source' => $accounts->get('source'),
                'transfer_destination' => $accounts->get('destination'),
                'fixed' => $this->financialAccounts($entityId, ['financial_account_id' => $line->fixed_financial_account_id], $date)->get('primary'),
                default => null,
            };

            if ($line->financial_account_source !== 'none' && ! $financialAccount) {
                throw $this->missing($date, $accounts, $funds, $category, 'Aturan pencatatan membutuhkan rekening yang belum dipilih.');
            }
            if ($line->account->is_liquidity_account && ! $financialAccount) {
                throw $this->missing($date, $accounts, $funds, $category, 'Baris kas/bank membutuhkan rekening yang sah.');
            }
            if (! $financialAccount) {
                continue;
            }
            if ($financialAccount->account_id !== $line->account_id || ! $this->hasCompatibleFinancialAccountDetail($financialAccount)) {
                throw $this->missing($date, $accounts, $funds, $category, 'Rekening tidak cocok dengan aturan pencatatan yang berlaku.');
            }
        }
    }

    private function hasCompatibleFinancialAccountDetail(FinancialAccount $financialAccount): bool
    {
        $hasBankDetail = BankAccountDetail::query()->where('financial_account_id', $financialAccount->id)->exists();
        $hasCashDetail = CashAccountDetail::query()->where('financial_account_id', $financialAccount->id)->exists();

        return match ($financialAccount->account_type) {
            'bank' => $hasBankDetail && ! $hasCashDetail,
            'cash' => $hasCashDetail && ! $hasBankDetail,
            default => ! $hasBankDetail && ! $hasCashDetail,
        };
    }

    /**
     * Resolve the policy matrix against the dimensions that every posting-rule
     * line will actually produce. This keeps the preflight result aligned with
     * PostingEngine instead of treating one representative account as enough.
     *
     * @param  Collection<int, PostingRuleLine>  $lines
     * @param  Collection<int, Fund>  $funds
     * @param  array<string, mixed>  $input
     * @return Collection<int, FundPolicyVersion|BankMutationPolicy>
     */
    private function fundPoliciesForLines(string $entityId, Collection $lines, Collection $funds, TransactionType $type, ?Category $category, ?Program $program, ?BankMutationPolicy $bankPolicy, string $date, Collection $accounts, array $input): Collection
    {
        $sourceFund = $this->fundForLineSource($entityId, $input['source_fund_id'] ?? null, $funds->get(0), $date);
        $destinationFund = $this->fundForLineSource($entityId, $input['destination_fund_id'] ?? null, $funds->get(1), $date);

        $contexts = $lines->flatMap(function (PostingRuleLine $line) use ($entityId, $funds, $sourceFund, $destinationFund, $category, $program, $date): Collection {
            $lineFunds = match ($line->fund_source) {
                'split' => $funds,
                'interfund_source' => collect([$sourceFund])->filter(),
                'interfund_destination' => collect([$destinationFund])->filter(),
                'fixed' => collect([$this->fundForLineSource($entityId, $line->fixed_fund_id, null, $date)])->filter(),
                default => collect(),
            };

            if ($line->fund_source !== 'none' && $lineFunds->isEmpty()) {
                throw new FinancialPostingException('E-CONFIGURATION-MISSING', 'Konfigurasi pencatatan membutuhkan Dana yang belum dipilih.');
            }

            return $lineFunds->map(fn (Fund $fund): array => [
                'fund' => $fund,
                'account_id' => $line->account_id,
                'category_id' => match ($line->category_source) {
                    'transaction', 'split' => $category?->id,
                    'fixed' => $line->fixed_category_id,
                    default => null,
                },
                'program_id' => match ($line->program_source) {
                    'split' => $program?->id,
                    'fixed' => $line->fixed_program_id,
                    default => null,
                },
                'cost_center_id' => $line->cost_center_source === 'fixed' ? $line->fixed_cost_center_id : null,
            ]);
        });

        return $contexts
            ->map(fn (array $context) => $this->fundPolicy(
                $context['fund'],
                $type,
                $context['account_id'],
                $context['category_id'],
                $context['program_id'],
                $context['cost_center_id'],
                $category,
                $bankPolicy,
                $date,
                $accounts,
                $funds,
            ))
            ->filter()
            ->unique(fn (FundPolicyVersion|BankMutationPolicy $policy): string => $policy::class.':'.$policy->id)
            ->values();
    }

    private function fundForLineSource(string $entityId, ?string $fundId, ?Fund $fallback, string $date): ?Fund
    {
        if (! $fundId) {
            return $fallback;
        }

        return $this->funds($entityId, ['fund_id' => $fundId], $date)->first();
    }

    private function fundPolicy(Fund $fund, TransactionType $type, string $accountId, ?string $categoryId, ?string $programId, ?string $costCenterId, ?Category $selectedCategory, ?BankMutationPolicy $bankPolicy, string $date, Collection $accounts, Collection $funds): FundPolicyVersion|BankMutationPolicy|null
    {
        if (! in_array($fund->type?->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true)) {
            return null;
        }
        $policy = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where(fn (Builder $query) => $query->where('status', 'effective')->orWhere(fn (Builder $historical) => $historical->where('status', 'superseded')->whereNotNull('approved_at')))
            ->where('effective_from', '<=', $date)
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
            ->orderByDesc('effective_from')->orderByDesc('version_no')->first();
        $decisions = $policy ? FundPolicyRule::query()
            ->where('fund_policy_version_id', $policy->id)
            ->where('transaction_type_id', $type->id)
            ->where(fn (Builder $query) => $query->whereNull('account_id')->orWhere('account_id', $accountId))
            ->where(fn (Builder $query) => $query->whereNull('category_id')->orWhere('category_id', $categoryId))
            ->where(fn (Builder $query) => $query->whereNull('program_id')->orWhere('program_id', $programId))
            ->where(fn (Builder $query) => $query->whereNull('cost_center_id')->orWhere('cost_center_id', $costCenterId))
            ->pluck('decision') : collect();
        if ($decisions->contains('prohibited') || (! $decisions->contains('allowed') && ! $bankPolicy)) {
            throw $this->missing($date, $accounts, $funds, $selectedCategory, 'Aturan penggunaan Dana tidak mengizinkan kombinasi tersebut.');
        }

        return $decisions->contains('allowed') ? $policy : $bankPolicy;
    }

    private function missing(string $date, Collection $accounts, Collection $funds, ?Category $category, ?string $reason = null): FinancialPostingException
    {
        $accountLabel = $accounts->filter()->map(fn (FinancialAccount $account) => $account->name)->unique()->implode(' → ') ?: 'Belum dipilih';
        $fundLabel = $funds->map(fn (Fund $fund) => $fund->name)->unique()->implode(' → ') ?: 'Belum dipilih';
        $categoryLabel = $category?->name ?? 'Tidak berlaku';
        $message = 'Konfigurasi pencatatan belum tersedia untuk kombinasi ini. '
            .'Rekening: '.$accountLabel.'. Dana: '.$fundLabel.'. Kategori: '.$categoryLabel.'. Tanggal: '.CarbonImmutable::parse($date)->format('d/m/Y').'.';
        if ($reason) {
            $message .= ' '.$reason;
        }

        return new FinancialPostingException('E-CONFIGURATION-MISSING', $message);
    }
}
