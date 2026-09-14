<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\ApprovalRequirement;
use App\Models\FinancialV2\BankMutationPolicy;
use App\Models\FinancialV2\Category;
use App\Models\FinancialV2\EvidenceRequirement;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\PostingRuleLine;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\Program;
use App\Models\FinancialV2\TransactionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Creates governed configuration drafts only; it never writes financial facts. */
final class InlineConfigurationService
{
    private const OPERATION_CODES = ['receipt' => 'RCV', 'payment' => 'PAY', 'transfer' => 'TRF', 'interfund' => 'IFT'];

    private const RULE_FAMILIES = ['RCV' => 'receipt', 'PAY' => 'payment', 'TRF' => 'treasury-transfer', 'IFT' => 'interfund-transfer'];

    public function __construct(
        private readonly FinancialTransactionConfigurationResolver $resolver,
        private readonly FinancialMasterDataService $masters,
        private readonly AuditTrailService $auditTrail,
    ) {}

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function describe(array $input): array
    {
        $context = $this->context($input);
        $ready = $this->isReady($context);
        $versions = $this->candidateVersions($context);
        $approval = ApprovalRequirement::query()
            ->where('accounting_entity_id', $context['entity']->id)
            ->where('transaction_type_id', $context['type']->id)
            ->where('status', 'active')
            ->where('effective_from', '<=', $context['date'])
            ->where(fn (Builder $query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', $context['date']))
            ->first();

        return [
            'state' => $ready ? 'ready' : 'missing',
            'message' => $ready ? 'Konfigurasi untuk kombinasi ini sudah tersedia.' : ($versions->isEmpty() ? 'Posting Rule belum tersedia.' : 'Lengkapi konfigurasi khusus lalu simpan sebagai draft.'),
            'context' => $this->labels($context),
            'posting_rules' => $versions->map(fn (PostingRuleVersion $version) => [
                'id' => $version->id,
                'label' => $version->rule->name.' — Versi '.$version->version_no,
                'evidence' => $version->evidenceRequirements->pluck('evidence_type')->values(),
            ])->values(),
            'selected_posting_rule_version_id' => $versions->count() === 1 ? $versions->first()->id : null,
            'required_approval_steps' => (int) ($approval?->required_steps ?? 0),
        ];
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    public function createDraft(array $input, ?int $actorUserId): array
    {
        $context = $this->context($input);
        if ($this->isReady($context)) {
            throw new FinancialDomainException('E-CONFIGURATION-DUPLICATE', 'Konfigurasi untuk kombinasi ini sudah tersedia.');
        }
        $version = $this->candidateVersions($context)->firstWhere('id', $input['posting_rule_version_id'] ?? null);
        if (! $version) {
            throw new FinancialDomainException('E-POSTING-RULE-MISSING', 'Posting Rule belum tersedia atau tidak berlaku pada tanggal tersebut.');
        }
        if (filled($input['evidence_type'] ?? null) && ! $version->evidenceRequirements->contains(fn (EvidenceRequirement $requirement) => $requirement->evidence_type === $input['evidence_type'] && $requirement->minimum_count > 0)) {
            throw new FinancialDomainException('E-EVIDENCE-CONFIGURATION', 'Evidence Requirement tersebut belum tersedia pada Posting Rule yang dipilih.');
        }

        return DB::transaction(function () use ($context, $version, $input, $actorUserId): array {
            $factsBefore = $this->factCounts();
            $created = $context['bank_mutation']
                ? collect([$this->createBankMutationPolicy($context, $version, $input, $actorUserId)])
                : $this->createFundPolicyDrafts($context, $version, $input, $actorUserId);

            if ($created->isEmpty()) {
                throw new FinancialDomainException('E-CONFIGURATION-NOT-CREATABLE', 'Kombinasi ini tidak membutuhkan Aturan Dana baru. Periksa Posting Rule dan master terkait pada halaman Konfigurasi.');
            }
            if ($factsBefore !== $this->factCounts()) {
                throw new FinancialDomainException('E-CONFIGURATION-FACT-MUTATION', 'Pembuatan konfigurasi mencoba mengubah financial fact. Seluruh perubahan dibatalkan.');
            }
            $this->auditTrail->record(
                $context['entity']->id,
                'inline_transaction_configuration_draft_created',
                'financial_configuration',
                (string) $created->first()->id,
                (string) Str::uuid(),
                $actorUserId,
                null,
                [
                    'origin' => 'INLINE_FROM_TRANSACTION_FORM',
                    'operation' => $context['operation'],
                    'transaction_type_id' => $context['type']->id,
                    'date' => $context['date'],
                    'financial_account_ids' => $context['accounts']->pluck('id')->values()->all(),
                    'fund_ids' => $context['funds']->pluck('id')->values()->all(),
                    'category_id' => $context['category']?->id,
                    'program_id' => $context['program']?->id,
                    'posting_rule_version_id' => $version->id,
                    'policy_document_ref' => $input['policy_document_ref'],
                    'notes' => $input['notes'] ?? null,
                ],
            );

            return [
                'state' => 'pending',
                'message' => 'Draft konfigurasi tersimpan dan menunggu aktivasi/approval.',
                'configuration_url' => route('financial-v2.configuration.index', ['entity' => $context['entity']->id]),
            ];
        }, 3);
    }

    /** @param array<string, mixed> $input @return array<string, mixed> */
    private function context(array $input): array
    {
        $entity = AccountingEntity::query()->where('status', 'active')->findOrFail($input['entity']);
        $operation = (string) $input['operation'];
        $category = filled($input['category_id'] ?? null) ? Category::query()->forEntity($entity->id)->where('status', 'active')->findOrFail($input['category_id']) : null;
        $code = $operation === 'bank_mutation'
            ? TransactionType::query()->forEntity($entity->id)->whereKey($category?->transaction_type_id)->value('code')
            : (self::OPERATION_CODES[$operation] ?? null);
        $type = TransactionType::query()->forEntity($entity->id)->where('status', 'active')->where('code', $code)->firstOrFail();
        $accountIds = collect([$input['financial_account_id'] ?? null, $input['source_financial_account_id'] ?? null, $input['destination_financial_account_id'] ?? null])->filter()->unique()->values();
        $fundIds = collect([$input['fund_id'] ?? null, $input['source_fund_id'] ?? null, $input['destination_fund_id'] ?? null])->filter()->unique()->values();
        $accounts = FinancialAccount::query()->forEntity($entity->id)->whereIn('id', $accountIds)->get()->sortBy(fn ($item) => $accountIds->search($item->id))->values();
        $funds = Fund::query()->with('type')->forEntity($entity->id)->whereIn('id', $fundIds)->get()->sortBy(fn ($item) => $fundIds->search($item->id))->values();
        if ($accounts->count() !== $accountIds->count() || $funds->count() !== $fundIds->count()) {
            throw new FinancialDomainException('E-MASTER-SCOPE', 'Rekening atau Dana berada di luar entitas yang dipilih.');
        }
        $transactionDate = (string) $input['date'];
        if ($accounts->contains(fn (FinancialAccount $account) => ! $account->isUsableOn($transactionDate) || ($account->opening_date && $account->opening_date->gt($transactionDate)))) {
            throw new FinancialDomainException('E-MASTER-LIFECYCLE', 'Rekening tidak dapat digunakan pada tanggal transaksi.');
        }
        if ($funds->contains(fn (Fund $fund) => $fund->status !== 'active' || ($fund->valid_from && $fund->valid_from->gt($transactionDate)) || ($fund->valid_to && $fund->valid_to->lt($transactionDate)))) {
            throw new FinancialDomainException('E-MASTER-LIFECYCLE', 'Dana tidak dapat digunakan pada tanggal transaksi.');
        }
        $program = filled($input['program_id'] ?? null) ? Program::query()->forEntity($entity->id)->findOrFail($input['program_id']) : null;
        if ($program && ! $program->isBusinessActiveOn($transactionDate)) {
            throw new FinancialDomainException('E-MASTER-LIFECYCLE', 'Program tidak dapat digunakan pada tanggal transaksi.');
        }
        $validShape = match ($operation) {
            'receipt', 'payment', 'bank_mutation' => $accounts->count() === 1 && $funds->count() === 1 && $category !== null,
            'transfer' => $accounts->count() === 2 && $funds->count() === 1,
            'interfund' => $accounts->count() === 1 && $funds->count() === 2 && $input['source_fund_id'] !== $input['destination_fund_id'],
            default => false,
        };
        if (! $validShape || ($category && $category->transaction_type_id && $category->transaction_type_id !== $type->id)) {
            throw new FinancialDomainException('E-CONFIGURATION-CONTEXT', 'Konteks konfigurasi transaksi belum lengkap atau tidak konsisten.');
        }

        return compact('entity', 'operation', 'type', 'category', 'accounts', 'funds', 'program') + [
            'date' => $input['effective_from'] ?? $input['date'],
            'transaction_date' => $input['date'],
            'bank_mutation' => $operation === 'bank_mutation',
            'source_fund_id' => $input['source_fund_id'] ?? null,
            'destination_fund_id' => $input['destination_fund_id'] ?? null,
        ];
    }

    /** @param array<string, mixed> $context */
    private function isReady(array $context): bool
    {
        try {
            $this->resolver->resolve($this->resolverInput($context));

            return true;
        } catch (FinancialDomainException|FinancialPostingException) {
            return false;
        }
    }

    /** @param array<string, mixed> $context @return Collection<int, PostingRuleVersion> */
    private function candidateVersions(array $context): Collection
    {
        return PostingRuleVersion::query()->with(['rule', 'lines.account', 'evidenceRequirements'])
            ->where('accounting_entity_id', $context['entity']->id)
            ->where(fn (Builder $q) => $q->where('status', 'effective')->orWhere(fn (Builder $h) => $h->where('status', 'superseded')->whereNotNull('approved_at')))
            ->where('effective_from', '<=', $context['date'])
            ->where(fn (Builder $q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $context['date']))
            ->whereHas('rule', fn (Builder $q) => $q->where('status', 'active')->where('transaction_type_id', $context['type']->id))
            ->when($context['category']?->default_posting_rule_id, fn (Builder $q, string $id) => $q->where('posting_rule_id', $id))
            ->when(! $context['bank_mutation'] && ! $context['category']?->default_posting_rule_id, fn (Builder $q) => $q->whereHas('rule', fn (Builder $rule) => $rule->where('rule_family', self::RULE_FAMILIES[$context['type']->code])))
            ->get()->filter(fn (PostingRuleVersion $version) => $version->lines->count() >= 2)->values();
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $input */
    private function createBankMutationPolicy(array $context, PostingRuleVersion $version, array $input, ?int $actorUserId): BankMutationPolicy
    {
        $account = $context['accounts']->first();
        $fund = $context['funds']->first();
        $this->assertExistingFundPolicyAllows($context, $version, $fund);
        $overlap = BankMutationPolicy::query()->where('accounting_entity_id', $context['entity']->id)
            ->whereIn('status', ['draft', 'active'])
            ->where('financial_account_id', $account->id)->where('fund_id', $fund->id)
            ->where('transaction_type_id', $context['type']->id)->where('category_id', $context['category']->id)
            ->where('effective_from', '<=', $input['effective_to'] ?? '9999-12-31')
            ->where(fn (Builder $q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $context['date']))->exists();
        if ($overlap) {
            throw new FinancialDomainException('E-CONFIGURATION-OVERLAP', 'Konfigurasi untuk kombinasi dan periode ini sudah tersedia atau overlap.');
        }

        return BankMutationPolicy::query()->create([
            'accounting_entity_id' => $context['entity']->id, 'financial_account_id' => $account->id,
            'fund_id' => $fund->id, 'transaction_type_id' => $context['type']->id, 'category_id' => $context['category']->id,
            'posting_rule_version_id' => $version->id, 'policy_document_ref' => $input['policy_document_ref'],
            'evidence_type' => ($input['evidence_type'] ?? null) ?: 'statement', 'required_approval_steps' => $input['required_approval_steps'] ?? 0,
            'effective_from' => $context['date'], 'effective_to' => $input['effective_to'] ?? null, 'status' => 'draft',
            'created_by_user_id' => $actorUserId, 'updated_by_user_id' => $actorUserId,
        ]);
    }

    /** @param array<string, mixed> $context */
    private function assertExistingFundPolicyAllows(array $context, PostingRuleVersion $version, Fund $fund): void
    {
        if (! in_array($fund->type?->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true)) {
            return;
        }
        $policy = FundPolicyVersion::query()->where('fund_id', $fund->id)
            ->where(fn (Builder $q) => $q->where('status', 'effective')->orWhere(fn (Builder $h) => $h->where('status', 'superseded')->whereNotNull('approved_at')))
            ->where('effective_from', '<=', $context['date'])
            ->where(fn (Builder $q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $context['date']))
            ->first();
        foreach ($this->linePolicyContexts($context, $version, $fund) as $required) {
            $decisions = $policy?->rules()->where('transaction_type_id', $required['transaction_type_id'])
                ->where(fn (Builder $q) => $q->whereNull('account_id')->orWhere('account_id', $required['account_id']))
                ->where(fn (Builder $q) => $q->whereNull('category_id')->orWhere('category_id', $required['category_id']))
                ->where(fn (Builder $q) => $q->whereNull('program_id')->orWhere('program_id', $required['program_id']))
                ->where(fn (Builder $q) => $q->whereNull('cost_center_id')->orWhere('cost_center_id', $required['cost_center_id']))
                ->pluck('decision') ?? collect();
            if ($decisions->contains('prohibited') || ! $decisions->contains('allowed')) {
                throw new FinancialDomainException('E-FUND-POLICY-MISSING', "Aturan penggunaan Dana {$fund->name} belum mengizinkan seluruh akun pada Posting Rule yang dipilih.");
            }
        }
    }

    /** @param array<string, mixed> $context @param array<string, mixed> $input @return Collection<int, FundPolicyVersion> */
    private function createFundPolicyDrafts(array $context, PostingRuleVersion $version, array $input, ?int $actorUserId): Collection
    {
        $restrictedFunds = $context['funds']->filter(fn (Fund $fund) => in_array($fund->type?->classification, ['restricted', 'perpetual_restricted', 'custodial', 'syariah'], true));

        return $restrictedFunds->map(function (Fund $fund) use ($context, $version, $input, $actorUserId): FundPolicyVersion {
            $overlapDraft = FundPolicyVersion::query()->where('fund_id', $fund->id)->where('status', 'draft')
                ->where('effective_from', '<=', $input['effective_to'] ?? '9999-12-31')
                ->where(fn (Builder $q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $context['date']))->exists();
            if ($overlapDraft) {
                throw new FinancialDomainException('E-CONFIGURATION-OVERLAP', 'Draft Aturan Dana untuk periode ini sudah tersedia. Lanjutkan dari halaman Konfigurasi.');
            }
            $predecessor = FundPolicyVersion::query()->where('fund_id', $fund->id)
                ->where(fn (Builder $q) => $q->where('status', 'effective')->orWhere(fn (Builder $h) => $h->where('status', 'superseded')->whereNotNull('approved_at')))
                ->where('effective_from', '<=', $context['date'])->orderByDesc('effective_from')->first();
            $policy = $this->masters->createFundPolicyVersion($context['entity']->id, [
                'fund_id' => $fund->id, 'effective_from' => $context['date'], 'effective_to' => $input['effective_to'] ?? null,
                'policy_document_ref' => $input['policy_document_ref'], 'allowed_matrix_ref' => $input['policy_document_ref'],
                'exception_approval_level' => $predecessor?->exception_approval_level ?: 'financial-governance',
            ], $actorUserId);
            $predecessor?->rules()->get()->each(fn (FundPolicyRule $rule) => $this->masters->createFundPolicyRule($context['entity']->id, $policy->id, $rule->only(['transaction_type_id', 'account_id', 'category_id', 'program_id', 'cost_center_id', 'decision', 'rationale']), $actorUserId));
            foreach ($this->linePolicyContexts($context, $version, $fund) as $ruleData) {
                $exists = FundPolicyRule::query()->where('fund_policy_version_id', $policy->id)
                    ->where('transaction_type_id', $ruleData['transaction_type_id'])->where('account_id', $ruleData['account_id'])
                    ->where(fn (Builder $q) => $this->nullable($q, 'category_id', $ruleData['category_id']))
                    ->where(fn (Builder $q) => $this->nullable($q, 'program_id', $ruleData['program_id']))
                    ->where(fn (Builder $q) => $this->nullable($q, 'cost_center_id', $ruleData['cost_center_id']))->exists();
                if (! $exists) {
                    $this->masters->createFundPolicyRule($context['entity']->id, $policy->id, $ruleData, $actorUserId);
                }
            }

            return $policy;
        })->values();
    }

    /** @param array<string, mixed> $context @return array<int, array<string, mixed>> */
    private function linePolicyContexts(array $context, PostingRuleVersion $version, Fund $fund): array
    {
        return $version->lines->filter(function (PostingRuleLine $line) use ($context, $fund) {
            return match ($line->fund_source) {
                'split' => $context['funds']->contains('id', $fund->id),
                'interfund_source' => $context['source_fund_id'] === $fund->id,
                'interfund_destination' => $context['destination_fund_id'] === $fund->id,
                'fixed' => $line->fixed_fund_id === $fund->id,
                default => false,
            };
        })->map(fn (PostingRuleLine $line) => [
            'transaction_type_id' => $context['type']->id,
            'account_id' => $line->account_id,
            'category_id' => in_array($line->category_source, ['transaction', 'split']) ? $context['category']?->id : $line->fixed_category_id,
            'program_id' => $line->program_source === 'split' ? $context['program']?->id : $line->fixed_program_id,
            'cost_center_id' => $line->cost_center_source === 'fixed' ? $line->fixed_cost_center_id : null,
            'decision' => 'allowed', 'rationale' => 'INLINE_FROM_TRANSACTION_FORM; '.$context['transaction_date'],
        ])->unique(fn ($row) => json_encode($row))->values()->all();
    }

    private function nullable(Builder $query, string $column, ?string $value): Builder
    {
        return $value === null ? $query->whereNull($column) : $query->where($column, $value);
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function resolverInput(array $context): array
    {
        return ['accounting_entity_id' => $context['entity']->id, 'transaction_type_id' => $context['type']->id, 'date' => $context['transaction_date'],
            'financial_account_id' => $context['operation'] === 'transfer' ? null : $context['accounts']->first()?->id,
            'source_financial_account_id' => $context['operation'] === 'transfer' ? $context['accounts']->get(0)?->id : null,
            'destination_financial_account_id' => $context['operation'] === 'transfer' ? $context['accounts']->get(1)?->id : null,
            'fund_ids' => $context['funds']->pluck('id')->all(), 'source_fund_id' => $context['source_fund_id'], 'destination_fund_id' => $context['destination_fund_id'],
            'category_id' => $context['category']?->id, 'program_id' => $context['program']?->id];
    }

    /** @param array<string, mixed> $context @return array<string, mixed> */
    private function labels(array $context): array
    {
        return ['entity' => $context['entity']->name, 'transaction_type' => $context['type']->name, 'date' => $context['transaction_date'],
            'financial_accounts' => $context['accounts']->pluck('name')->values(), 'funds' => $context['funds']->pluck('name')->values(),
            'category' => $context['category']?->name ?? 'Tidak berlaku', 'program' => $context['program']?->name ?? 'Tanpa Program'];
    }

    /** @return array<string, int> */
    private function factCounts(): array
    {
        return collect(['financial_v2_transactions', 'financial_v2_journals', 'financial_v2_journal_lines', 'financial_v2_ledger_entries', 'financial_v2_vouchers'])
            ->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }
}
