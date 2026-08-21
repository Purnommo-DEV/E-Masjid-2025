<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\BalanceInquiryService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\EvidenceService;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\HistoricalFundHistoryService;
use App\Domain\FinancialV2\MasterDataGovernanceService;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\Account;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AccountingPeriod;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AuditEvent;
use App\Models\FinancialV2\DocumentSequence;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\FundPolicyRule;
use App\Models\FinancialV2\FundPolicyVersion;
use App\Models\FinancialV2\HistoricalFundHistory;
use App\Models\FinancialV2\IdempotencyKey;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\OpeningBalanceBatch;
use App\Models\FinancialV2\OpeningBalanceLine;
use App\Models\FinancialV2\PostingAttempt;
use App\Models\FinancialV2\PostingRule;
use App\Models\FinancialV2\PostingRuleVersion;
use App\Models\FinancialV2\TransactionType;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Applies the owner-approved Phase 12.5 Fund-only correction.
 *
 * The command never edits the opening Journal or Ledger. Two distinct IFT
 * source transactions are posted through the canonical PostingEngine while
 * their primary Financial Account remains traceability metadata only.
 */
final class CorrectMrjZiswafFundAttributionCommand extends Command
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    private const INFAQ_CODE = 'INFAQ-TROMOL';

    private const DHUAFA_CODE = 'DHUAFA';

    private const BNI_CODE = 'BNI-ZISWAF';

    private const CASH_CODE = 'CASH-ZISWAF';

    private const IFT_CODE = 'IFT';

    private const CORRECTION_DATE = '2026-08-16';

    private const POST_CORRECTION_POLICY_DATE = '2026-08-17';

    private const PREDECESSOR_POLICY_DATE = '2026-08-15';

    private const SOURCE_FILENAME = 'ZISWAF UPDATE 3.xlsx';

    private const SOURCE_HASH = '404fc8cd54ecd3e35e17c30ffe6a3d88df6656260cbeac8f614ef99689a02f9c';

    private const EVIDENCE_FILENAME = 'Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf';

    private const EVIDENCE_HASH = '0a83d582b1e78ea8f41e76024008aa358a053f99ffa8598b70f398bfd587ae82';

    private const CASH_SOURCE_REFERENCE = 'Sisa Alokasi Dana!D66:E66';

    private const OPENING_REFERENCE = 'MRJ-ZISWAF-OPENING-2026-06-27-V1';

    private const ORIGINAL_INFAQ = '19319949.00';

    private const ORIGINAL_DHUAFA = '9658977.00';

    private const CASH_ATTRIBUTION = '2653000.00';

    private const FUND_RECLASSIFICATION = '1200000.00';

    private const FINAL_INFAQ = '15466949.00';

    private const FINAL_DHUAFA = '13511977.00';

    private const FINAL_DHUAFA_BNI = '10858977.00';

    private const FINAL_BNI = '123077312.00';

    private const FINAL_CASH = '2653000.00';

    private const FINAL_TOTAL = '125730312.00';

    private const COMBINED_TARGET_FUNDS = '28978926.00';

    private const DHUAFA_FINAL_NAME = 'Dana Dhuafa & Anak Yatim';

    private const CASH_SOURCE_REFERENCE_KEY = 'MRJ-P12.5-CASH-ATTRIBUTION-2026-08-16';

    private const RECLASS_SOURCE_REFERENCE_KEY = 'MRJ-P12.5-FUND-RECLASS-2026-08-16';

    protected $signature = 'financial-v2:correct-mrj-ziswaf-fund-attribution
        {source : Absolute path to ZISWAF UPDATE 3.xlsx}
        {evidence : Absolute path to the approved 16-Aug-2026 PDF}
        {--apply : Persist the correction; without this flag the command is read-only dry-run}
        {--allow-testing : Permit execution only under APP_ENV=testing with mrj_test_db}';

    protected $description = 'Apply the governed Phase 12.5 Cash Tromol Fund attribution and Rp1.2M Fund reclassification.';

    private AccountingEntity $entity;

    private User $actor;

    /** @var array<string, Fund> */
    private array $funds = [];

    /** @var array<string, FinancialAccount> */
    private array $financialAccounts = [];

    /** @var array<string, string> Fund code => governed IFT transfer Account UUID */
    private array $iftTransferAccountIds = [];

    private string $iftPostingRuleVersionId;

    private string $iftDocumentSequenceId;

    private TransactionType $iftType;

    private string $sourceHash;

    private string $evidenceHash;

    private string $evidenceFilename;

    public function handle(
        FinancialMasterDataService $masters,
        MasterDataGovernanceService $governance,
        HistoricalFundHistoryService $historicalHistory,
        FinancialTransactionLifecycleService $lifecycle,
        EvidenceService $evidence,
        BalanceInquiryService $balances,
        FinancialReportService $reports,
    ): int {
        $this->assertPermittedEnvironment();
        $sourceFile = $this->verifiedSourceFile((string) $this->argument('source'), self::SOURCE_FILENAME, self::SOURCE_HASH, 'workbook sumber');
        $evidenceFile = $this->verifiedSourceFile((string) $this->argument('evidence'), self::EVIDENCE_FILENAME, self::EVIDENCE_HASH, 'PDF keputusan');
        $source = $sourceFile['path'];
        $evidencePdf = $evidenceFile['path'];
        $this->sourceHash = $sourceFile['hash'];
        $this->evidenceHash = $evidenceFile['hash'];
        $this->evidenceFilename = $evidenceFile['filename'];
        $this->loadGovernedContext();
        $this->assertPostingReadiness();

        $specifications = $this->transactionSpecifications();
        $existingCorrections = collect();
        foreach ($specifications as $specification) {
            $existing = $this->validateExistingTransaction($specification);
            if ($existing) {
                $existingCorrections->push($existing);
            }
        }
        if ($existingCorrections->isNotEmpty()
            && ($existingCorrections->count() !== 2 || ! $existingCorrections->every(fn (FinancialTransaction $transaction): bool => $transaction->status === 'posted'))) {
            throw new RuntimeException('Partial Phase 12.5 correction state is unsupported; refusing to post under a closed historical policy window.');
        }
        $state = $this->assertKnownReconciliationState($balances, $reports);

        if (! $this->option('apply')) {
            $this->info('DRY RUN ONLY - no database or storage write was performed.');
            $this->table(['Control', 'Result'], [
                ['Database', DB::connection()->getDatabaseName()],
                ['Source workbook SHA-256', strtoupper($this->sourceHash)],
                ['Decision PDF SHA-256', strtoupper($this->evidenceHash)],
                ['Current Infaq & Tromol', self::idr($state['infaq'])],
                ['Current Dhuafa', self::idr($state['dhuafa'])],
                ['Cash attribution planned', self::idr(self::CASH_ATTRIBUTION)],
                ['PDF p.3-4 reclassification planned', self::idr(self::FUND_RECLASSIFICATION)],
                ['Target Infaq & Tromol', self::idr(self::FINAL_INFAQ)],
                ['Target Dhuafa & Anak Yatim', self::idr(self::FINAL_DHUAFA)],
                ['Execution', 'Pass --apply to persist atomically'],
            ]);

            return self::SUCCESS;
        }

        $archives = $this->archiveVerifiedSources($source, $evidencePdf);
        $before = $this->financialFactSnapshot();
        $existingTransactionsBefore = collect($specifications)
            ->map(fn (array $specification): ?FinancialTransaction => $this->findTransaction($specification['source_reference']))
            ->filter();
        $existingPostedBefore = $existingTransactionsBefore->where('status', 'posted')->count();

        $result = DB::transaction(function () use (
            $masters,
            $governance,
            $historicalHistory,
            $lifecycle,
            $evidence,
            $balances,
            $reports,
            $archives,
            $specifications,
            $before,
            $existingTransactionsBefore,
            $existingPostedBefore,
        ): array {
            $originalDhuafaId = $this->funds[self::DHUAFA_CODE]->id;
            $this->funds[self::DHUAFA_CODE] = $masters->renameActiveFund(
                $this->entity->id,
                $originalDhuafaId,
                self::DHUAFA_FINAL_NAME,
                $this->fundRenameReason(),
                $this->actor->id,
            );
            if ($this->funds[self::DHUAFA_CODE]->id !== $originalDhuafaId) {
                throw new RuntimeException('The governed Fund rename changed the DHUAFA UUID.');
            }
            $this->assertFundRenameAudit();

            $this->ensureSuccessorPolicies($masters, $governance);
            $this->correctHistoricalCashAttribution($historicalHistory);

            $transactions = [];
            foreach ($specifications as $key => $specification) {
                $transactions[$key] = $this->ensurePostedInterfundTransfer($specification, $archives, $lifecycle, $evidence);
            }
            $this->ensurePostCorrectionFailClosedPolicies($masters, $governance);

            $after = $this->financialFactSnapshot();
            $this->assertExpectedFactDelta(
                $before,
                $after,
                2 - $existingTransactionsBefore->count(),
                2 - $existingPostedBefore,
            );
            $reconciliation = $this->assertFinalReconciliation($transactions, $balances, $reports);

            return ['transactions' => $transactions, 'reconciliation' => $reconciliation, 'facts' => $after];
        }, 1);

        $this->info('Phase 12.5 Fund attribution correction is posted and reconciled.');
        $this->table(['Control', 'Result'], [
            ['Fund UUID preserved', $this->funds[self::DHUAFA_CODE]->id],
            ['Infaq & Tromol', self::idr($result['reconciliation']['infaq'])],
            ['Dhuafa & Anak Yatim', self::idr($result['reconciliation']['dhuafa'])],
            ['BNI ZISWAF', self::idr($result['reconciliation']['bni'])],
            ['Cash Tromol Yatim', self::idr($result['reconciliation']['cash'])],
            ['Total liquidity', self::idr($result['reconciliation']['liquidity_total'])],
            ['Cash attribution IFT', $result['transactions']['cash']->source_reference.' / '.$result['transactions']['cash']->status],
            ['Rp1.2M reclassification IFT', $result['transactions']['reclassification']->source_reference.' / '.$result['transactions']['reclassification']->status],
            ['Trial balance', 'BALANCED'],
        ]);

        return self::SUCCESS;
    }

    private function assertPermittedEnvironment(): void
    {
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();
        $driver = (string) $connection->getDriverName();
        if ($driver !== 'mysql') {
            throw new RuntimeException('Phase 12.5 correction requires the governed MySQL connection.');
        }
        if ($this->option('allow-testing')) {
            if (! app()->environment('testing') || $database !== 'mrj_test_db') {
                throw new RuntimeException('Testing correction is permitted only under APP_ENV=testing on mrj_test_db.');
            }

            return;
        }

        if (! app()->environment('local') || $database !== 'mrj_prod_db') {
            throw new RuntimeException('Actual correction is permitted only in local development on mrj_prod_db.');
        }
    }

    /** @return array{path:string,filename:string,hash:string} */
    private function verifiedSourceFile(string $path, string $expectedFilename, string $expectedHash, string $label): array
    {
        $resolved = realpath($path);
        if ($resolved === false || ! is_file($resolved) || ! is_readable($resolved)) {
            throw new RuntimeException("The {$label} is missing or unreadable: {$path}");
        }
        $filename = basename($resolved);
        $actualHash = hash_file('sha256', $resolved);
        if ($actualHash === false) {
            throw new RuntimeException("The {$label} could not be hashed.");
        }
        $actualHash = strtolower($actualHash);
        if (! $this->option('allow-testing') && $filename !== $expectedFilename) {
            throw new RuntimeException("Unexpected {$label} filename; expected {$expectedFilename}.");
        }
        if (! $this->option('allow-testing') && ! hash_equals($expectedHash, $actualHash)) {
            throw new RuntimeException("The {$label} SHA-256 does not match the audited Phase 12.5 source.");
        }

        return ['path' => $resolved, 'filename' => $filename, 'hash' => $actualHash];
    }

    private function loadGovernedContext(): void
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->first();
        if (! $entity || $entity->status !== 'active') {
            throw new RuntimeException('Active MRJ-ACTUAL Accounting Entity is required.');
        }
        $actor = User::query()->where('email', 'superadmin@emasjid.com')->first();
        if (! $actor) {
            throw new RuntimeException('The existing local QA Super Admin is required; this correction never creates or changes credentials.');
        }

        $this->entity = $entity;
        $this->actor = $actor;

        foreach ([self::INFAQ_CODE, self::DHUAFA_CODE] as $code) {
            $fund = Fund::query()->where('accounting_entity_id', $entity->id)->where('code', $code)->first();
            if (! $fund || $fund->status !== 'active') {
                throw new RuntimeException("Active actual Fund {$code} is required.");
            }
            $this->funds[$code] = $fund;
        }
        if ($this->funds[self::INFAQ_CODE]->name !== 'Dana Infaq & Tromol') {
            throw new RuntimeException('INFAQ-TROMOL has an unexpected actual name.');
        }
        if (! in_array($this->funds[self::DHUAFA_CODE]->name, ['Dana Dhuafa', self::DHUAFA_FINAL_NAME], true)) {
            throw new RuntimeException('DHUAFA has an unexpected actual name; refusing a broad master-data rename.');
        }

        foreach ([self::BNI_CODE, self::CASH_CODE] as $code) {
            $account = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->where('code', $code)->first();
            if (! $account || $account->status !== 'active' || $account->currency_code !== 'IDR') {
                throw new RuntimeException("Active IDR Financial Account {$code} is required.");
            }
            $this->financialAccounts[$code] = $account;
        }

        $type = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', self::IFT_CODE)->first();
        if (! $type || $type->status !== 'active' || ($type->valid_from && $type->valid_from->toDateString() > self::CORRECTION_DATE)
            || ($type->valid_to && $type->valid_to->toDateString() < self::CORRECTION_DATE)) {
            throw new RuntimeException('An effective IFT Transaction Type is required on 16 August 2026.');
        }
        $this->iftType = $type;
    }

    private function assertPostingReadiness(): void
    {
        $period = AccountingPeriod::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('start_date', '<=', self::CORRECTION_DATE)
            ->where('end_date', '>=', self::CORRECTION_DATE)
            ->first();
        if (! $period || ! $period->permitsOrdinaryPosting()) {
            throw new RuntimeException('The August 2026 accounting period must be open for the correction.');
        }

        $rule = PostingRule::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('transaction_type_id', $this->iftType->id)
            ->where('code', 'MRJ-IFT-STANDARD')
            ->where('status', 'active')
            ->first();
        $version = $rule ? PostingRuleVersion::query()
            ->where('posting_rule_id', $rule->id)
            ->where('status', 'effective')
            ->where('effective_from', '<=', self::CORRECTION_DATE)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', self::CORRECTION_DATE))
            ->first() : null;
        if (! $version) {
            throw new RuntimeException('The effective MRJ IFT posting rule is missing.');
        }
        $this->iftPostingRuleVersionId = $version->id;

        $lines = DB::table('financial_v2_posting_rule_lines as line')
            ->join('financial_v2_accounts as account', 'account.id', '=', 'line.account_id')
            ->where('line.posting_rule_version_id', $version->id)
            ->orderBy('line.line_no')
            ->get([
                'line.line_no', 'line.account_id', 'line.entry_side', 'line.amount_source', 'line.financial_account_source',
                'line.fund_source', 'account.account_class',
            ]);
        $expected = [
            [1, 'debit', 'transaction_gross_amount', 'none', 'interfund_destination', 'transfer'],
            [2, 'credit', 'transaction_gross_amount', 'none', 'interfund_source', 'transfer'],
        ];
        $actual = $lines->map(fn (object $line): array => [
            (int) $line->line_no,
            $line->entry_side,
            $line->amount_source,
            $line->financial_account_source,
            $line->fund_source,
            $line->account_class,
        ])->all();
        if ($actual !== $expected) {
            throw new RuntimeException('The MRJ IFT posting rule does not preserve the approved Fund-only accounting treatment.');
        }
        $this->iftTransferAccountIds = [
            self::DHUAFA_CODE => (string) $lines->firstWhere('line_no', 1)->account_id,
            self::INFAQ_CODE => (string) $lines->firstWhere('line_no', 2)->account_id,
        ];

        $sequence = DocumentSequence::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('transaction_type_id', $this->iftType->id)
            ->where('code', 'MRJ-IFT')
            ->where('status', 'active')
            ->first();
        if (! $sequence) {
            throw new RuntimeException('The active MRJ IFT voucher sequence is missing.');
        }
        $this->iftDocumentSequenceId = $sequence->id;

        $requiredApprovals = (int) DB::table('financial_v2_approval_requirements')
            ->where('accounting_entity_id', $this->entity->id)
            ->where('transaction_type_id', $this->iftType->id)
            ->where('status', 'active')
            ->where('effective_from', '<=', self::CORRECTION_DATE)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', self::CORRECTION_DATE))
            ->max('required_steps');
        if ($requiredApprovals !== 0) {
            throw new RuntimeException('Phase 12.5 cannot bypass a configured IFT approval workflow.');
        }

        $opening = OpeningBalanceBatch::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('cutover_reference', self::OPENING_REFERENCE)
            ->first();
        if (! $opening || $opening->status !== 'posted' || ! $opening->journal_id) {
            throw new RuntimeException('The immutable posted MRJ ZISWAF opening position is required.');
        }
    }

    /** @return array<string, array<string, string>> */
    private function transactionSpecifications(): array
    {
        return [
            'cash' => [
                'source_reference' => self::CASH_SOURCE_REFERENCE_KEY,
                'idempotency_key' => 'mrj-p12.5-cash-attribution-source-v1',
                'posting_key' => 'mrj-p12.5-cash-attribution-post-v1',
                'gross_amount' => self::CASH_ATTRIBUTION,
                'primary_financial_account_id' => $this->financialAccounts[self::CASH_CODE]->id,
                'primary_financial_account_code' => self::CASH_CODE,
                'description' => 'Historical Fund Attribution Correction: seluruh Cash Tromol Yatim direklasifikasi dari Dana Infaq & Tromol ke Dana Dhuafa & Anak Yatim.',
                'policy_version_ref' => 'PHASE-12.5-CASH-ATTRIBUTION',
                'policy_basis_ref' => 'PHASE-12.5|'.self::SOURCE_FILENAME.'|'.self::CASH_SOURCE_REFERENCE.'|workbook_sha256='.$this->sourceHash,
                'reason' => 'Final business decision: Cash Tromol Rp2.653.000 remains physically in CASH-ZISWAF and only its Fund attribution changes.',
            ],
            'reclassification' => [
                'source_reference' => self::RECLASS_SOURCE_REFERENCE_KEY,
                'idempotency_key' => 'mrj-p12.5-fund-reclassification-source-v1',
                'posting_key' => 'mrj-p12.5-fund-reclassification-post-v1',
                'gross_amount' => self::FUND_RECLASSIFICATION,
                'primary_financial_account_id' => $this->financialAccounts[self::BNI_CODE]->id,
                'primary_financial_account_code' => self::BNI_CODE,
                'description' => 'Historical Fund Reclassification: Dana Infaq & Tromol ke Dana Dhuafa & Anak Yatim berdasarkan rapat 16 Agustus 2026.',
                'policy_version_ref' => 'PHASE-12.5-FUND-RECLASS',
                'policy_basis_ref' => 'PHASE-12.5|'.$this->evidenceFilename.'|PDF p.3-4|pdf_sha256='.$this->evidenceHash,
                'reason' => 'PDF p.3-4: pemindahan alokasi Rp1.200.000 dari Infaq & Tromol ke Dhuafa dan Anak Yatim; no income and no liquidity movement.',
            ],
        ];
    }

    /** @param array<string, string> $specification */
    private function validateExistingTransaction(array $specification): ?FinancialTransaction
    {
        $transaction = $this->findTransaction($specification['source_reference']);
        if (! $transaction) {
            return null;
        }

        $expected = [
            'transaction_type_id' => $this->iftType->id,
            'business_date' => self::CORRECTION_DATE,
            'accounting_date' => self::CORRECTION_DATE,
            'gross_amount' => DecimalAmount::normalize($specification['gross_amount']),
            'primary_financial_account_id' => $specification['primary_financial_account_id'],
            'idempotency_key' => $specification['idempotency_key'],
            'description' => $specification['description'],
            'policy_version_ref' => $specification['policy_version_ref'],
        ];
        $actual = [
            'transaction_type_id' => $transaction->transaction_type_id,
            'business_date' => $transaction->business_date->toDateString(),
            'accounting_date' => $transaction->accounting_date->toDateString(),
            'gross_amount' => DecimalAmount::normalize((string) $transaction->gross_amount),
            'primary_financial_account_id' => $transaction->primary_financial_account_id,
            'idempotency_key' => $transaction->idempotency_key,
            'description' => $transaction->description,
            'policy_version_ref' => $transaction->policy_version_ref,
        ];
        if ($actual !== $expected || ! in_array($transaction->status, ['draft', 'submitted', 'verified', 'approved', 'posted'], true)) {
            throw new RuntimeException("Existing correction transaction {$transaction->id} does not match its idempotent Phase 12.5 payload.");
        }

        $detail = $transaction->interfundTransfer;
        if (! $detail
            || $detail->source_fund_id !== $this->funds[self::INFAQ_CODE]->id
            || $detail->destination_fund_id !== $this->funds[self::DHUAFA_CODE]->id
            || $detail->policy_basis_ref !== $specification['policy_basis_ref']
            || $detail->reason !== $specification['reason']) {
            throw new RuntimeException("Existing correction transaction {$transaction->id} has mismatched IFT detail.");
        }

        if ($transaction->status === 'posted') {
            $this->assertPostedIftIntegrity($transaction);
        }

        return $transaction;
    }

    private function findTransaction(string $sourceReference): ?FinancialTransaction
    {
        $matches = FinancialTransaction::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('source_reference', $sourceReference)
            ->with('interfundTransfer')
            ->get();
        if ($matches->count() > 1) {
            throw new RuntimeException("Duplicate Phase 12.5 source reference detected: {$sourceReference}.");
        }

        return $matches->first();
    }

    /** @return array{infaq:string,dhuafa:string,bni:string,cash:string} */
    private function assertKnownReconciliationState(BalanceInquiryService $balances, FinancialReportService $reports): array
    {
        $fundData = $reports->report('fund-balance', $this->entity->id, '2026-01-01', self::CORRECTION_DATE)['data'];
        $funds = collect($fundData['rows'])->keyBy('code');
        $infaq = DecimalAmount::normalize((string) ($funds->get(self::INFAQ_CODE)['fund_balance'] ?? 0));
        $dhuafa = DecimalAmount::normalize((string) ($funds->get(self::DHUAFA_CODE)['fund_balance'] ?? 0));
        $allowedStates = [
            self::ORIGINAL_INFAQ.'|'.self::ORIGINAL_DHUAFA,
            self::FINAL_INFAQ.'|'.self::FINAL_DHUAFA,
        ];
        if (! in_array($infaq.'|'.$dhuafa, $allowedStates, true)
            || ! DecimalAmount::equals(DecimalAmount::add($infaq, $dhuafa), self::COMBINED_TARGET_FUNDS)) {
            throw new RuntimeException('Current target Fund balances are neither the audited baseline nor an idempotently resumable Phase 12.5 state.');
        }

        $bni = $balances->financialAccountBalance($this->entity->id, $this->financialAccounts[self::BNI_CODE]->id, self::CORRECTION_DATE)['balance'];
        $cash = $balances->financialAccountBalance($this->entity->id, $this->financialAccounts[self::CASH_CODE]->id, self::CORRECTION_DATE)['balance'];
        $allFunds = DecimalAmount::sum(collect($fundData['rows'])->pluck('fund_balance'));
        $trial = $reports->report('trial-balance', $this->entity->id, '2026-01-01', self::CORRECTION_DATE)['data'];
        if (! DecimalAmount::equals($bni, self::FINAL_BNI)
            || ! DecimalAmount::equals($cash, self::FINAL_CASH)
            || ! DecimalAmount::equals($allFunds, self::FINAL_TOTAL)
            || ! $trial['is_balanced']) {
            throw new RuntimeException('The pre-correction liquidity, total Fund, or trial-balance control does not match the approved limited correction boundary.');
        }

        return compact('infaq', 'dhuafa', 'bni', 'cash');
    }

    /** @return array{source_archive:string,evidence_archive:string,evidence_hash:string,evidence_size:int} */
    private function archiveVerifiedSources(string $source, string $evidencePdf): array
    {
        $sourceArchive = 'financial-v2/source-archive/mrj-ziswaf/2026-06-27/'.$this->sourceHash.'.xlsx';
        $this->ensureContentAddressedArchive($source, $sourceArchive, $this->sourceHash);

        $existingEvidence = Attachment::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('content_hash', $this->evidenceHash)
            ->first();
        $evidenceArchive = $existingEvidence?->storage_reference
            ?? 'financial-v2/correction-evidence/mrj-ziswaf/'.self::CORRECTION_DATE.'/'.$this->evidenceHash.'.pdf';
        $this->ensureContentAddressedArchive($evidencePdf, $evidenceArchive, $this->evidenceHash);
        if ($existingEvidence
            && ($existingEvidence->media_type !== 'application/pdf'
                || (int) $existingEvidence->byte_size !== (int) filesize($evidencePdf)
                || $existingEvidence->status !== 'active')) {
            throw new RuntimeException('Existing evidence metadata conflicts with the approved Phase 12.5 PDF content hash.');
        }

        return [
            'source_archive' => $sourceArchive,
            'evidence_archive' => $evidenceArchive,
            'evidence_hash' => $this->evidenceHash,
            'evidence_size' => (int) filesize($evidencePdf),
        ];
    }

    private function ensureContentAddressedArchive(string $source, string $archive, string $expectedHash): void
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($archive)) {
            $contents = file_get_contents($source);
            if ($contents === false || ! $disk->put($archive, $contents)) {
                throw new RuntimeException("Failed to archive governed Phase 12.5 source {$archive}.");
            }
        }
        $archivedContents = $disk->get($archive);
        if (! hash_equals($expectedHash, hash('sha256', $archivedContents))) {
            throw new RuntimeException("Archived Phase 12.5 source failed content-address integrity: {$archive}.");
        }
    }

    private function ensureSuccessorPolicies(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        foreach ([self::INFAQ_CODE, self::DHUAFA_CODE] as $fundCode) {
            $this->ensureSuccessorPolicy($this->funds[$fundCode], $fundCode, $masters, $governance);
        }
    }

    private function ensureSuccessorPolicy(Fund $fund, string $fundCode, FinancialMasterDataService $masters, MasterDataGovernanceService $governance): FundPolicyVersion
    {
        $predecessor = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('version_no', 2)
            ->where('effective_from', '<=', self::PREDECESSOR_POLICY_DATE)
            ->where(fn ($query) => $query->whereNull('effective_to')->orWhere('effective_to', '>=', self::PREDECESSOR_POLICY_DATE))
            ->first();
        if (! $predecessor || ! in_array($predecessor->status, ['effective', 'superseded'], true)
            || $predecessor->policy_document_ref !== 'PHASE-12-MRJ-OPERATIONAL-POLICY|'.$fundCode) {
            throw new RuntimeException("The audited effective Phase 12 policy predecessor is missing for {$fundCode}.");
        }

        $documentReference = 'PHASE-12.5-FINAL-FUND-ATTRIBUTION|'.$fundCode.'|PDF-SHA256:'.$this->evidenceHash;
        $matrixReference = 'PHASE-12.5-IFT-INFAQ-TROMOL-TO-DHUAFA|SOURCE-SHA256:'.$this->sourceHash;
        $successor = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('policy_document_ref', $documentReference)
            ->first();
        if (! $successor) {
            if ((int) FundPolicyVersion::query()->where('fund_id', $fund->id)->max('version_no') !== 2) {
                throw new RuntimeException("Unexpected Fund policy version history for {$fundCode}; refusing to manufacture a conflicting successor.");
            }
            $successor = $masters->createFundPolicyVersion($this->entity->id, [
                'fund_id' => $fund->id,
                'effective_from' => self::CORRECTION_DATE,
                'effective_to' => null,
                'policy_document_ref' => $documentReference,
                'allowed_matrix_ref' => $matrixReference,
                'exception_approval_level' => $predecessor->exception_approval_level,
            ], $this->actor->id);
        }
        $expectedCorrectionPolicyEnd = $successor->status === 'superseded' ? self::CORRECTION_DATE : null;
        if ((int) $successor->version_no !== 3
            || $successor->effective_from->toDateString() !== self::CORRECTION_DATE
            || $successor->effective_to?->toDateString() !== $expectedCorrectionPolicyEnd
            || $successor->allowed_matrix_ref !== $matrixReference
            || $successor->exception_approval_level !== $predecessor->exception_approval_level
            || ! in_array($successor->status, ['draft', 'effective', 'superseded'], true)) {
            throw new RuntimeException("Existing Phase 12.5 Fund policy successor is inconsistent for {$fundCode}.");
        }

        $predecessorRules = FundPolicyRule::query()->where('fund_policy_version_id', $predecessor->id)->get();
        if ($predecessorRules->isEmpty()) {
            throw new RuntimeException("The Phase 12 policy predecessor has no rules for {$fundCode}.");
        }
        if ($successor->status === 'draft') {
            foreach ($predecessorRules as $rule) {
                $this->ensurePolicyRule($successor, [
                    'transaction_type_id' => $rule->transaction_type_id,
                    'account_id' => $rule->account_id,
                    'category_id' => $rule->category_id,
                    'program_id' => $rule->program_id,
                    'cost_center_id' => $rule->cost_center_id,
                    'decision' => $rule->decision,
                    'rationale' => $rule->rationale,
                ], $masters);
            }
            $this->ensurePolicyRule($successor, [
                'transaction_type_id' => $this->iftType->id,
                'account_id' => $this->iftTransferAccountIds[$fundCode],
                'category_id' => null,
                'program_id' => null,
                'cost_center_id' => null,
                'decision' => 'allowed',
                'rationale' => $this->iftPolicyRationale(),
            ], $masters);
            $successor = $governance->makeFundPolicyVersionEffective($successor->id, $this->actor->id);
        }

        $this->assertSuccessorPolicyRules($successor, $predecessorRules, $fundCode);

        return $successor;
    }

    /**
     * Closes the narrowly-authorized correction window on the next day. The
     * effective v4 restores the v2 fail-closed rules and deliberately omits
     * IFT, so Phase 12.5 cannot become standing authority for future Fund
     * transfers.
     */
    private function ensurePostCorrectionFailClosedPolicies(FinancialMasterDataService $masters, MasterDataGovernanceService $governance): void
    {
        foreach ([self::INFAQ_CODE, self::DHUAFA_CODE] as $fundCode) {
            $fund = $this->funds[$fundCode];
            $baseline = FundPolicyVersion::query()
                ->where('fund_id', $fund->id)
                ->where('version_no', 2)
                ->first();
            $correction = FundPolicyVersion::query()
                ->where('fund_id', $fund->id)
                ->where('version_no', 3)
                ->where('policy_document_ref', 'like', 'PHASE-12.5-FINAL-FUND-ATTRIBUTION|%')
                ->first();
            if (! $baseline || ! $correction) {
                throw new RuntimeException("The governed Phase 12.5 policy chain is incomplete for {$fundCode}.");
            }

            $baselineRules = FundPolicyRule::query()->where('fund_policy_version_id', $baseline->id)->get();
            if ($baselineRules->isEmpty() || $baselineRules->contains(
                fn (FundPolicyRule $rule): bool => $rule->transaction_type_id === $this->iftType->id,
            )) {
                throw new RuntimeException("The audited baseline policy for {$fundCode} cannot be used to restore fail-closed IFT behavior.");
            }

            $documentReference = 'PHASE-12.5-POST-CORRECTION-FAIL-CLOSED|'.$fundCode.'|PDF-SHA256:'.$this->evidenceHash;
            $matrixReference = 'PHASE-12.5-RESTORE-V2-RULES-WITHOUT-IFT|'.$fundCode;
            $restored = FundPolicyVersion::query()
                ->where('fund_id', $fund->id)
                ->where('policy_document_ref', $documentReference)
                ->first();
            if (! $restored) {
                if ((int) FundPolicyVersion::query()->where('fund_id', $fund->id)->max('version_no') !== 3) {
                    throw new RuntimeException("Unexpected Fund policy version history for {$fundCode}; refusing a conflicting fail-closed successor.");
                }
                $restored = $masters->createFundPolicyVersion($this->entity->id, [
                    'fund_id' => $fund->id,
                    'effective_from' => self::POST_CORRECTION_POLICY_DATE,
                    'effective_to' => null,
                    'policy_document_ref' => $documentReference,
                    'allowed_matrix_ref' => $matrixReference,
                    'exception_approval_level' => $baseline->exception_approval_level,
                ], $this->actor->id);
            }
            $restoredDateRangeIsValid = $restored->status === 'superseded'
                ? $restored->effective_to !== null && $restored->effective_to->gte($restored->effective_from)
                : $restored->effective_to === null;
            if ((int) $restored->version_no !== 4
                || $restored->effective_from->toDateString() !== self::POST_CORRECTION_POLICY_DATE
                || ! $restoredDateRangeIsValid
                || $restored->allowed_matrix_ref !== $matrixReference
                || $restored->exception_approval_level !== $baseline->exception_approval_level
                || ! in_array($restored->status, ['draft', 'effective', 'superseded'], true)) {
                throw new RuntimeException("The Phase 12.5 fail-closed successor is inconsistent for {$fundCode}.");
            }

            if ($restored->status === 'draft') {
                foreach ($baselineRules as $rule) {
                    $this->ensurePolicyRule($restored, [
                        'transaction_type_id' => $rule->transaction_type_id,
                        'account_id' => $rule->account_id,
                        'category_id' => $rule->category_id,
                        'program_id' => $rule->program_id,
                        'cost_center_id' => $rule->cost_center_id,
                        'decision' => $rule->decision,
                        'rationale' => $rule->rationale,
                    ], $masters);
                }
                $restored = $governance->makeFundPolicyVersionEffective($restored->id, $this->actor->id);
            }

            foreach ($baselineRules as $rule) {
                $this->assertMatchingPolicyRule($restored->id, $rule->transaction_type_id, $rule->account_id, $rule->category_id, $rule->program_id, $rule->cost_center_id, $rule->decision, $rule->rationale);
            }
            $restoredRules = FundPolicyRule::query()->where('fund_policy_version_id', $restored->id);
            if ((clone $restoredRules)->count() !== $baselineRules->count()
                || (clone $restoredRules)
                    ->where('transaction_type_id', $this->iftType->id)
                    ->exists()) {
                throw new RuntimeException("The post-correction policy does not exactly restore the fail-closed baseline for {$fundCode}.");
            }

            $correction->refresh();
            if ($correction->status !== 'superseded'
                || $correction->effective_to?->toDateString() !== self::CORRECTION_DATE
                || ! in_array($restored->status, ['effective', 'superseded'], true)) {
                throw new RuntimeException("The one-day Phase 12.5 correction authority was not closed for {$fundCode}.");
            }
            $this->assertContiguousPolicySuccessors($fund, $restored);
        }
    }

    /** @param array<string, mixed> $data */
    private function ensurePolicyRule(FundPolicyVersion $version, array $data, FinancialMasterDataService $masters): FundPolicyRule
    {
        $query = FundPolicyRule::query()
            ->where('fund_policy_version_id', $version->id)
            ->where('transaction_type_id', $data['transaction_type_id']);
        foreach (['account_id', 'category_id', 'program_id', 'cost_center_id'] as $field) {
            isset($data[$field]) ? $query->where($field, $data[$field]) : $query->whereNull($field);
        }
        $existing = $query->first();
        if ($existing) {
            if ($existing->decision !== $data['decision'] || $existing->rationale !== $data['rationale']) {
                throw new RuntimeException('A Phase 12.5 successor policy rule conflicts with its governed source rule.');
            }

            return $existing;
        }

        return $masters->createFundPolicyRule($this->entity->id, $version->id, $data, $this->actor->id);
    }

    private function assertSuccessorPolicyRules(FundPolicyVersion $successor, $predecessorRules, string $fundCode): void
    {
        if (! in_array($successor->status, ['effective', 'superseded'], true)) {
            throw new RuntimeException('The Phase 12.5 Fund policy successor did not become governed.');
        }
        foreach ($predecessorRules as $rule) {
            $this->assertMatchingPolicyRule($successor->id, $rule->transaction_type_id, $rule->account_id, $rule->category_id, $rule->program_id, $rule->cost_center_id, $rule->decision, $rule->rationale);
        }
        $this->assertMatchingPolicyRule(
            $successor->id,
            $this->iftType->id,
            $this->iftTransferAccountIds[$fundCode],
            null,
            null,
            null,
            'allowed',
            $this->iftPolicyRationale(),
        );
        if (FundPolicyRule::query()->where('fund_policy_version_id', $successor->id)->count() !== $predecessorRules->count() + 1) {
            throw new RuntimeException('The Phase 12.5 correction policy contains authority beyond its predecessor plus the explicit IFT allowance.');
        }
    }

    private function iftPolicyRationale(): string
    {
        return 'Phase 12.5 permits only the evidence-backed Fund attribution and reclassification on the governed transfer Account; Financial Account liquidity remains unchanged.';
    }

    private function assertContiguousPolicySuccessors(Fund $fund, FundPolicyVersion $restored): void
    {
        if ($restored->status === 'effective') {
            if ($restored->effective_to !== null) {
                throw new RuntimeException("The current post-correction policy has an unexpected end date for {$fund->code}.");
            }

            return;
        }

        $versions = FundPolicyVersion::query()
            ->where('fund_id', $fund->id)
            ->where('version_no', '>', $restored->version_no)
            ->orderBy('version_no')
            ->get();
        if ($versions->isEmpty()) {
            throw new RuntimeException("A superseded post-correction policy has no governed successor for {$fund->code}.");
        }

        $previous = $restored;
        foreach ($versions as $version) {
            if (! in_array($version->status, ['effective', 'superseded'], true)
                || $previous->effective_to === null
                || $version->effective_from->toDateString() !== $previous->effective_to->copy()->addDay()->toDateString()) {
                throw new RuntimeException("The governed policy successor chain is not contiguous for {$fund->code}.");
            }
            $previous = $version;
        }
        if ($previous->status !== 'effective' || $previous->effective_to !== null) {
            throw new RuntimeException("The governed policy successor chain has no current effective version for {$fund->code}.");
        }
    }

    private function assertMatchingPolicyRule(
        string $versionId,
        string $transactionTypeId,
        ?string $accountId,
        ?string $categoryId,
        ?string $programId,
        ?string $costCenterId,
        string $decision,
        string $rationale,
    ): void {
        $query = FundPolicyRule::query()
            ->where('fund_policy_version_id', $versionId)
            ->where('transaction_type_id', $transactionTypeId);
        foreach (['account_id' => $accountId, 'category_id' => $categoryId, 'program_id' => $programId, 'cost_center_id' => $costCenterId] as $field => $value) {
            $value === null ? $query->whereNull($field) : $query->where($field, $value);
        }
        $rule = $query->first();
        if (! $rule || $rule->decision !== $decision || $rule->rationale !== $rationale) {
            throw new RuntimeException('The Phase 12.5 successor policy does not preserve its predecessor plus the explicit IFT allowance.');
        }
    }

    private function correctHistoricalCashAttribution(HistoricalFundHistoryService $history): HistoricalFundHistory
    {
        $records = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('source_filename', self::SOURCE_FILENAME)
            ->where('source_reference', self::CASH_SOURCE_REFERENCE)
            ->where('entry_kind', 'account_position')
            ->get();
        if ($records->count() !== 1) {
            throw new RuntimeException('Exactly one workbook-backed Cash Tromol account-position history row is required.');
        }
        $record = $records->first();
        if (! hash_equals(self::SOURCE_HASH, strtolower((string) $record->source_hash))
            || $record->source_worksheet !== 'Sisa Alokasi Dana'
            || ! DecimalAmount::equals($record->amount, self::CASH_ATTRIBUTION)
            || $record->source_fund_code !== self::INFAQ_CODE) {
            throw new RuntimeException('The historical Cash Tromol source row does not match the audited workbook lineage.');
        }

        $reason = 'Phase 12.5 final Fund attribution correction; source '.self::SOURCE_FILENAME.'!'.self::CASH_SOURCE_REFERENCE.'; workbook_sha256='.$this->sourceHash.'; decision_pdf_sha256='.$this->evidenceHash.'.';
        if ($record->fund_id === $this->funds[self::DHUAFA_CODE]->id) {
            if ($record->status !== 'corrected' || $record->correction_reason !== $reason || ! $record->corrected_at) {
                throw new RuntimeException('The existing Cash Tromol history attribution is not an idempotent Phase 12.5 correction.');
            }

            $this->assertHistoricalCashAudit($record, $reason);

            return $record;
        }
        if ($record->fund_id !== $this->funds[self::INFAQ_CODE]->id) {
            throw new RuntimeException('Cash Tromol history is attributed to an unexpected Fund.');
        }

        $corrected = $history->correct($this->entity->id, $record->id, [
            'fund_id' => $this->funds[self::DHUAFA_CODE]->id,
            'effective_date' => $record->effective_date?->toDateString(),
            'date_label' => $record->date_label,
            'entry_kind' => $record->entry_kind,
            'description' => $record->description,
            'notes' => $record->notes,
            'amount' => DecimalAmount::normalize((string) $record->amount),
            'source_reference' => $record->source_reference,
            'correction_reason' => $reason,
        ], $this->actor->id);
        $this->assertHistoricalCashAudit($corrected, $reason);

        return $corrected;
    }

    private function fundRenameReason(): string
    {
        return 'Phase 12.5 final owner decision; Fund UUID and all historical relationships remain unchanged; decision_pdf_sha256='.$this->evidenceHash.'.';
    }

    private function assertFundRenameAudit(): void
    {
        $events = AuditEvent::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('target_type', 'fund')
            ->where('target_id', $this->funds[self::DHUAFA_CODE]->id)
            ->where('event_type', 'fund_active_renamed')
            ->get();
        if ($events->count() !== 1) {
            throw new RuntimeException('The governed DHUAFA Fund rename must have exactly one immutable audit event.');
        }
        $event = $events->first();
        $before = json_decode((string) $event->before_summary, true);
        $after = json_decode((string) $event->after_summary, true);
        if (! is_array($before) || ! is_array($after)
            || ($before['name'] ?? null) !== 'Dana Dhuafa'
            || ($after['name'] ?? null) !== self::DHUAFA_FINAL_NAME
            || ($after['rename_reason'] ?? null) !== $this->fundRenameReason()
            || (int) $event->actor_user_id !== (int) $this->actor->id) {
            throw new RuntimeException('The governed DHUAFA Fund rename audit lineage is inconsistent.');
        }
    }

    private function assertHistoricalCashAudit(HistoricalFundHistory $record, string $reason): void
    {
        $events = AuditEvent::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('target_type', 'historical_fund_history')
            ->where('target_id', $record->id)
            ->where('event_type', 'historical_fund_history_corrected')
            ->get();
        if ($events->count() !== 1) {
            throw new RuntimeException('The Cash Tromol source correction must have exactly one immutable audit event.');
        }
        $event = $events->first();
        $before = json_decode((string) $event->before_summary, true);
        $after = json_decode((string) $event->after_summary, true);
        if (! is_array($before) || ! is_array($after)
            || ($before['fund_id'] ?? null) !== $this->funds[self::INFAQ_CODE]->id
            || ($after['fund_id'] ?? null) !== $this->funds[self::DHUAFA_CODE]->id
            || ! in_array('fund_id', $after['changed_fields'] ?? [], true)
            || ($after['correction_reason'] ?? null) !== $reason
            || (int) $event->actor_user_id !== (int) $this->actor->id) {
            throw new RuntimeException('The Cash Tromol source correction audit lineage is inconsistent.');
        }
    }

    /** @param array<string, string> $specification @param array{source_archive:string,evidence_archive:string,evidence_hash:string,evidence_size:int} $archives */
    private function ensurePostedInterfundTransfer(
        array $specification,
        array $archives,
        FinancialTransactionLifecycleService $lifecycle,
        EvidenceService $evidence,
    ): FinancialTransaction {
        $transaction = $this->findTransaction($specification['source_reference']);
        if (! $transaction) {
            $transaction = $lifecycle->createInterfundTransfer([
                'accounting_entity_id' => $this->entity->id,
                'transaction_type_id' => $this->iftType->id,
                'business_date' => self::CORRECTION_DATE,
                'accounting_date' => self::CORRECTION_DATE,
                'gross_amount' => $specification['gross_amount'],
                'primary_financial_account_id' => $specification['primary_financial_account_id'],
                'source_reference' => $specification['source_reference'],
                'idempotency_key' => $specification['idempotency_key'],
                'description' => $specification['description'],
                'policy_version_ref' => $specification['policy_version_ref'],
                'source_fund_id' => $this->funds[self::INFAQ_CODE]->id,
                'destination_fund_id' => $this->funds[self::DHUAFA_CODE]->id,
                'policy_basis_ref' => $specification['policy_basis_ref'],
                'reason' => $specification['reason'],
            ], $this->actor->id);
        }
        $transaction = $this->validateExistingTransaction($specification) ?? $transaction;
        $this->ensureTransactionEvidence($transaction, $archives, $evidence);

        if ($transaction->status === 'draft') {
            $transaction = $lifecycle->submit($transaction->id, $this->actor->id);
        }
        if ($transaction->status === 'submitted') {
            $transaction = $lifecycle->verify($transaction->id, $this->actor->id);
        }
        if ($transaction->status === 'verified') {
            $transaction = $lifecycle->approve($transaction->id, $this->actor->id);
        }
        if ($transaction->status === 'approved') {
            $fingerprint = $this->postingFingerprint($specification);
            $lifecycle->post($transaction->id, $specification['posting_key'], $fingerprint, $this->actor->id);
            $transaction = $transaction->fresh(['interfundTransfer']);
        }
        if ($transaction->status !== 'posted') {
            throw new RuntimeException("Phase 12.5 transaction {$transaction->source_reference} did not reach posted state.");
        }
        $this->assertPostedIftIntegrity($transaction);

        return $transaction;
    }

    /** @param array<string, string> $specification */
    private function postingFingerprint(array $specification): string
    {
        return hash('sha256', implode('|', [
            $specification['posting_key'],
            $specification['source_reference'],
            $specification['gross_amount'],
            self::INFAQ_CODE,
            self::DHUAFA_CODE,
            $specification['primary_financial_account_code'],
        ]));
    }

    /** @param array{source_archive:string,evidence_archive:string,evidence_hash:string,evidence_size:int} $archives */
    private function ensureTransactionEvidence(FinancialTransaction $transaction, array $archives, EvidenceService $evidence): void
    {
        $links = DB::table('financial_v2_attachment_links as link')
            ->join('financial_v2_attachments as attachment', 'attachment.id', '=', 'link.attachment_id')
            ->where('link.accounting_entity_id', $this->entity->id)
            ->where('link.target_type', 'transaction')
            ->where('link.target_id', $transaction->id)
            ->where('link.evidence_type', 'policy')
            ->where('link.status', 'active')
            ->get(['link.id', 'attachment.content_hash', 'attachment.storage_reference', 'attachment.status']);
        if ($links->count() > 1) {
            throw new RuntimeException("Duplicate active policy evidence exists for {$transaction->source_reference}.");
        }
        if ($links->isNotEmpty()) {
            $link = $links->first();
            if (! hash_equals($archives['evidence_hash'], strtolower((string) $link->content_hash))
                || $link->storage_reference !== $archives['evidence_archive']
                || $link->status !== 'active') {
                throw new RuntimeException("Existing policy evidence conflicts for {$transaction->source_reference}.");
            }

            return;
        }

        $evidence->attachToTransaction(
            $this->entity->id,
            $transaction->id,
            $this->evidenceFilename,
            'application/pdf',
            $archives['evidence_size'],
            $archives['evidence_hash'],
            $archives['evidence_archive'],
            'policy',
            $this->actor->id,
        );
    }

    private function assertPostedIftIntegrity(FinancialTransaction $transaction): void
    {
        $specification = collect($this->transactionSpecifications())
            ->first(fn (array $candidate): bool => $candidate['source_reference'] === $transaction->source_reference);
        if (! is_array($specification)) {
            throw new RuntimeException("Posted IFT {$transaction->id} is not one of the two governed Phase 12.5 corrections.");
        }

        $journals = Journal::query()->where('accounting_entity_id', $this->entity->id)->where('transaction_id', $transaction->id)->get();
        if ($journals->count() !== 1) {
            throw new RuntimeException("Posted IFT {$transaction->source_reference} must have exactly one Journal.");
        }
        $journal = $journals->first();
        $lines = JournalLine::query()->where('journal_id', $journal->id)->orderBy('line_no')->get();
        $attempts = PostingAttempt::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('transaction_id', $transaction->id)
            ->where('status', 'committed')
            ->get();
        $keys = IdempotencyKey::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('scope_name', 'transaction-posting')
            ->where('key_value', $specification['posting_key'])
            ->get();
        $vouchers = Voucher::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('transaction_id', $transaction->id)
            ->get();
        if ($journal->journal_status !== 'posted'
            || $journal->posting_rule_version_id !== $this->iftPostingRuleVersionId
            || $journal->business_date->toDateString() !== self::CORRECTION_DATE
            || $journal->accounting_date->toDateString() !== self::CORRECTION_DATE
            || ! DecimalAmount::equals($journal->total_debit, $transaction->gross_amount)
            || ! DecimalAmount::equals($journal->total_credit, $transaction->gross_amount)
            || ! $journal->posting_sequence
            || ! $journal->posted_at
            || (int) $journal->posted_by_user_id !== (int) $this->actor->id
            || $journal->correlation_id !== $transaction->correlation_id
            || $lines->count() !== 2
            || $attempts->count() !== 1
            || $keys->count() !== 1
            || $vouchers->count() !== 1) {
            throw new RuntimeException("Posted IFT {$transaction->source_reference} violates Fund-only Journal/Ledger integrity.");
        }

        $attempt = $attempts->first();
        $key = $keys->first();
        $voucher = $vouchers->first();
        if ($journal->posting_attempt_id !== $attempt->id
            || $attempt->journal_id !== $journal->id
            || $attempt->idempotency_record_id !== $key->id
            || $attempt->correlation_id !== $transaction->correlation_id
            || ! $attempt->completed_at
            || $key->request_fingerprint !== $this->postingFingerprint($specification)
            || $key->status !== 'completed'
            || $key->result_reference !== $journal->id
            || $voucher->document_sequence_id !== $this->iftDocumentSequenceId
            || $voucher->status !== 'issued'
            || ! $voucher->issued_at) {
            throw new RuntimeException("Posted IFT {$transaction->source_reference} lacks canonical attempt, idempotency, or voucher provenance.");
        }

        $policyIds = [];
        foreach ([self::INFAQ_CODE, self::DHUAFA_CODE] as $fundCode) {
            $policy = FundPolicyVersion::query()
                ->where('fund_id', $this->funds[$fundCode]->id)
                ->where('version_no', 3)
                ->where('policy_document_ref', 'PHASE-12.5-FINAL-FUND-ATTRIBUTION|'.$fundCode.'|PDF-SHA256:'.$this->evidenceHash)
                ->first();
            if (! $policy || ! in_array($policy->status, ['effective', 'superseded'], true)) {
                throw new RuntimeException("Posted IFT {$transaction->source_reference} lacks its governed Phase 12.5 policy provenance.");
            }
            $policyIds[$fundCode] = $policy->id;
        }

        $expectedLines = [
            1 => [
                'account_id' => $this->iftTransferAccountIds[self::DHUAFA_CODE],
                'fund_id' => $this->funds[self::DHUAFA_CODE]->id,
                'debit' => DecimalAmount::normalize($transaction->gross_amount),
                'credit' => '0.00',
                'policy_id' => $policyIds[self::DHUAFA_CODE],
            ],
            2 => [
                'account_id' => $this->iftTransferAccountIds[self::INFAQ_CODE],
                'fund_id' => $this->funds[self::INFAQ_CODE]->id,
                'debit' => '0.00',
                'credit' => DecimalAmount::normalize($transaction->gross_amount),
                'policy_id' => $policyIds[self::INFAQ_CODE],
            ],
        ];
        foreach ($lines as $line) {
            $expected = $expectedLines[(int) $line->line_no] ?? null;
            if (! $expected
                || $line->account_id !== $expected['account_id']
                || $line->fund_id !== $expected['fund_id']
                || ! DecimalAmount::equals($line->debit_amount, $expected['debit'])
                || ! DecimalAmount::equals($line->credit_amount, $expected['credit'])
                || $line->policy_version_ref !== $expected['policy_id']
                || $line->financial_account_id !== null
                || $line->program_id !== null
                || $line->cost_center_id !== null
                || $line->counterparty_id !== null
                || $line->category_id !== null) {
                throw new RuntimeException("Posted IFT {$transaction->source_reference} has non-canonical JournalLine dimensions or policy lineage.");
            }

            $ledgerEntries = LedgerEntry::query()->where('journal_line_id', $line->id)->get();
            $account = Account::query()->find($line->account_id);
            if ($ledgerEntries->count() !== 1 || ! $account || $account->account_class !== 'transfer') {
                throw new RuntimeException("Posted IFT {$transaction->source_reference} has non-canonical transfer Account or Ledger cardinality.");
            }
            $ledger = $ledgerEntries->first();
            $expectedSigned = $account->normal_balance === 'debit'
                ? DecimalAmount::subtract($line->debit_amount, $line->credit_amount)
                : DecimalAmount::subtract($line->credit_amount, $line->debit_amount);
            if ($ledger->accounting_entity_id !== $this->entity->id
                || $ledger->accounting_date->toDateString() !== self::CORRECTION_DATE
                || (int) $ledger->posting_sequence !== (int) $journal->posting_sequence
                || (int) $ledger->line_no !== (int) $line->line_no
                || $ledger->account_id !== $line->account_id
                || $ledger->fund_id !== $line->fund_id
                || $ledger->financial_account_id !== null
                || $ledger->program_id !== null
                || ! DecimalAmount::equals($ledger->signed_amount, $expectedSigned)) {
                throw new RuntimeException("Posted IFT {$transaction->source_reference} has a Ledger row that does not mirror its immutable JournalLine.");
            }
        }

        if (AuditEvent::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('event_type', 'posting_committed')
            ->where('target_type', 'journal')
            ->where('target_id', $journal->id)
            ->where('actor_user_id', $this->actor->id)
            ->count() !== 1) {
            throw new RuntimeException("Posted IFT {$transaction->source_reference} lacks one immutable canonical posting audit event.");
        }
    }

    /** @param array<string, FinancialTransaction> $transactions @return array{infaq:string,dhuafa:string,bni:string,cash:string,liquidity_total:string} */
    private function assertFinalReconciliation(array $transactions, BalanceInquiryService $balances, FinancialReportService $reports): array
    {
        foreach ($transactions as $transaction) {
            $this->validateExistingTransaction($this->transactionSpecifications()[$transaction->source_reference === self::CASH_SOURCE_REFERENCE_KEY ? 'cash' : 'reclassification']);
        }

        $fundData = $reports->report('fund-balance', $this->entity->id, '2026-01-01', self::CORRECTION_DATE)['data'];
        $fundRows = collect($fundData['rows'])->keyBy('code');
        $infaq = DecimalAmount::normalize((string) ($fundRows->get(self::INFAQ_CODE)['fund_balance'] ?? 0));
        $dhuafa = DecimalAmount::normalize((string) ($fundRows->get(self::DHUAFA_CODE)['fund_balance'] ?? 0));
        $fundTotal = DecimalAmount::sum(collect($fundData['rows'])->pluck('fund_balance'));

        $bni = $balances->financialAccountBalance($this->entity->id, $this->financialAccounts[self::BNI_CODE]->id, self::CORRECTION_DATE)['balance'];
        $cash = $balances->financialAccountBalance($this->entity->id, $this->financialAccounts[self::CASH_CODE]->id, self::CORRECTION_DATE)['balance'];
        $liquidityTotal = DecimalAmount::add($bni, $cash);
        $accountReport = $reports->report('account-balance', $this->entity->id, '2026-01-01', self::CORRECTION_DATE)['data'];
        $allAccountTotal = DecimalAmount::sum(collect($accountReport['rows'])->pluck('closing_balance'));
        $allCompositionTotal = DecimalAmount::sum(collect($accountReport['fund_composition'])->pluck('balance'));
        $trial = $reports->report('trial-balance', $this->entity->id, '2026-01-01', self::CORRECTION_DATE)['data'];

        $composition = collect($fundData['account_composition'] ?? [])
            ->filter(fn (array $row): bool => in_array($row['fund_code'], [self::INFAQ_CODE, self::DHUAFA_CODE], true)
                && in_array($row['financial_account_code'], [self::BNI_CODE, self::CASH_CODE], true))
            ->mapWithKeys(fn (array $row): array => [
                $row['fund_code'].'|'.$row['financial_account_code'] => DecimalAmount::normalize((string) $row['liquidity_balance']),
            ]);
        $expectedComposition = collect([
            self::INFAQ_CODE.'|'.self::BNI_CODE => self::FINAL_INFAQ,
            self::DHUAFA_CODE.'|'.self::BNI_CODE => self::FINAL_DHUAFA_BNI,
            self::DHUAFA_CODE.'|'.self::CASH_CODE => self::FINAL_CASH,
        ]);

        if (! DecimalAmount::equals($infaq, self::FINAL_INFAQ)
            || ! DecimalAmount::equals($dhuafa, self::FINAL_DHUAFA)
            || ! DecimalAmount::equals(DecimalAmount::add($infaq, $dhuafa), self::COMBINED_TARGET_FUNDS)
            || ! DecimalAmount::equals($fundTotal, self::FINAL_TOTAL)
            || ! DecimalAmount::equals($bni, self::FINAL_BNI)
            || ! DecimalAmount::equals($cash, self::FINAL_CASH)
            || ! DecimalAmount::equals($liquidityTotal, self::FINAL_TOTAL)
            || ! DecimalAmount::equals($allAccountTotal, self::FINAL_TOTAL)
            || ! DecimalAmount::equals($allCompositionTotal, self::FINAL_TOTAL)
            || ! $trial['is_balanced']
            || $composition->sortKeys()->all() !== $expectedComposition->sortKeys()->all()) {
            throw new RuntimeException('Phase 12.5 final Fund, account-composition, liquidity, or trial-balance reconciliation failed.');
        }

        $historicalCash = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $this->entity->id)
            ->where('source_filename', self::SOURCE_FILENAME)
            ->where('source_reference', self::CASH_SOURCE_REFERENCE)
            ->sole();
        if ($historicalCash->fund_id !== $this->funds[self::DHUAFA_CODE]->id || $historicalCash->status !== 'corrected') {
            throw new RuntimeException('Cash Tromol historical lineage did not retain the governed destination Fund correction.');
        }

        if ($this->funds[self::DHUAFA_CODE]->fresh()->name !== self::DHUAFA_FINAL_NAME
            || $this->orphanLedgerCount() !== 0
            || $this->postedLinesMissingLedgerCount() !== 0
            || $this->duplicateVoucherCount() !== 0) {
            throw new RuntimeException('Phase 12.5 master, Ledger linkage, or voucher uniqueness control failed.');
        }

        return ['infaq' => $infaq, 'dhuafa' => $dhuafa, 'bni' => $bni, 'cash' => $cash, 'liquidity_total' => $liquidityTotal];
    }

    /** @return array<string, int> */
    private function financialFactSnapshot(): array
    {
        return [
            'transactions' => FinancialTransaction::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'journals' => Journal::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'journal_lines' => JournalLine::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'vouchers' => Voucher::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'opening_batches' => OpeningBalanceBatch::query()->where('accounting_entity_id', $this->entity->id)->count(),
            'opening_lines' => OpeningBalanceLine::query()->where('accounting_entity_id', $this->entity->id)->count(),
        ];
    }

    /** @param array<string, int> $before @param array<string, int> $after */
    private function assertExpectedFactDelta(array $before, array $after, int $newTransactions, int $newPostings): void
    {
        $expected = [
            'transactions' => $before['transactions'] + $newTransactions,
            'journals' => $before['journals'] + $newPostings,
            'journal_lines' => $before['journal_lines'] + ($newPostings * 2),
            'ledger_entries' => $before['ledger_entries'] + ($newPostings * 2),
            'vouchers' => $before['vouchers'] + $newPostings,
            'opening_batches' => $before['opening_batches'],
            'opening_lines' => $before['opening_lines'],
        ];
        if ($after !== $expected) {
            throw new RuntimeException('Phase 12.5 created an unexpected financial-fact delta; the database transaction will be rolled back.');
        }
    }

    private function orphanLedgerCount(): int
    {
        return DB::table('financial_v2_ledger_entries as ledger')
            ->leftJoin('financial_v2_journal_lines as line', 'line.id', '=', 'ledger.journal_line_id')
            ->where('ledger.accounting_entity_id', $this->entity->id)
            ->whereNull('line.id')
            ->count();
    }

    private function postedLinesMissingLedgerCount(): int
    {
        return DB::table('financial_v2_journal_lines as line')
            ->join('financial_v2_journals as journal', 'journal.id', '=', 'line.journal_id')
            ->leftJoin('financial_v2_ledger_entries as ledger', 'ledger.journal_line_id', '=', 'line.id')
            ->where('line.accounting_entity_id', $this->entity->id)
            ->where('journal.journal_status', 'posted')
            ->whereNull('ledger.id')
            ->count();
    }

    private function duplicateVoucherCount(): int
    {
        return DB::query()->fromSub(
            DB::table('financial_v2_vouchers')
                ->where('accounting_entity_id', $this->entity->id)
                ->select('voucher_number')
                ->groupBy('voucher_number')
                ->havingRaw('COUNT(*) > 1'),
            'duplicates',
        )->count();
    }

    private static function idr(string $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}
