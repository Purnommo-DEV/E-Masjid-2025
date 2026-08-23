<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\AuditTrailService;
use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\FinancialMasterDataService;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\HistoricalFundHistoryService;
use App\Domain\FinancialV2\MrjZiswafOpeningPosition;
use App\Domain\FinancialV2\PostingEngine;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\FinancialAccount;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Fund;
use App\Models\FinancialV2\HistoricalFundHistory;
use App\Models\FinancialV2\TransactionType;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Applies the non-destructive Phase 12.6 Cash Tromol semantic correction.
 *
 * This command never writes Journal, JournalLine, Ledger, Voucher, or Opening
 * Balance records. The only permitted financial fact is the real Rp1.200.000
 * reclassification, posted through the canonical lifecycle and PostingEngine
 * when a clean local/test database has not already replayed it.
 */
final class CorrectFinancialV2CurrentStateCommand extends Command
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    private const INFAQ_CODE = 'INFAQ-TROMOL';

    private const DHUAFA_CODE = 'DHUAFA';

    private const BNI_CODE = 'BNI-ZISWAF';

    private const CASH_CODE = 'CASH-ZISWAF';

    private const IFT_CODE = 'IFT';

    private const EFFECTIVE_DATE = '2026-08-16';

    private const RECLASSIFICATION_AMOUNT = '1200000.00';

    private const FINAL_INFAQ = '15466949.00';

    private const FINAL_DHUAFA = '13511977.00';

    private const FINAL_DHUAFA_BNI = '10858977.00';

    private const FINAL_BNI = '123077312.00';

    private const FINAL_CASH = '2653000.00';

    protected $signature = 'financial-v2:correct-current-state
        {--apply : Persist the governed correction; without this flag, perform only a read-only audit}
        {--allow-testing : Permit execution only under APP_ENV=testing on mrj_test_db}';

    protected $description = 'Apply the Phase 12.6 Cash Tromol original-composition correction without altering immutable financial facts.';

    public function handle(
        FinancialMasterDataService $masters,
        HistoricalFundHistoryService $history,
        FinancialTransactionLifecycleService $lifecycle,
        PostingEngine $posting,
        FinancialReportService $reports,
        AuditTrailService $audit,
    ): int {
        $this->assertPermittedEnvironment();
        $context = $this->governedContext();
        $before = $this->snapshot($context, $reports);

        if (! $this->option('apply')) {
            $this->info('DRY RUN ONLY - no database write was performed.');
            $this->table(['Control', 'Result'], [
                ['Database', DB::connection()->getDatabaseName()],
                ['Infaq & Tromol', self::idr($before['infaq'])],
                ['Dhuafa & Anak Yatim', self::idr($before['dhuafa'])],
                ['BNI ZISWAF', self::idr($before['bni'])],
                ['Cash Tromol Yatim', self::idr($before['cash'])],
                ['Legacy Cash IFT', $context['cash_ift']
                    ? ($context['cash_ift_semantically_superseded'] ? 'present - retained as superseded audit history' : 'present - semantic supersession required')
                    : 'not present'],
                ['Genuine IFT Rp1.200.000', $context['reclass_ift'] ? $context['reclass_ift']->status : 'will be posted through PostingEngine'],
            ]);

            return self::SUCCESS;
        }

        $changed = DB::transaction(function () use ($context, $masters, $history, $lifecycle, $posting, $audit): array {
            $dhuafa = $context['dhuafa'];
            if ($dhuafa->name === 'Dana Dhuafa') {
                $dhuafa = $masters->renameActiveFund(
                    $context['entity']->id,
                    $dhuafa->id,
                    'Dana Dhuafa & Anak Yatim',
                    'Phase 12.6: Cash Tromol merupakan komposisi awal Dana Dhuafa & Anak Yatim.',
                    $context['actor']->id,
                );
            }
            if ($dhuafa->name !== 'Dana Dhuafa & Anak Yatim') {
                throw new RuntimeException('DHUAFA has an unexpected name; refusing a broad master-data update.');
            }

            $correctedContext = array_merge($context, ['dhuafa' => $dhuafa]);
            $historyChanged = $this->correctCashHistory($correctedContext, $history);
            $sourceDuplicatesSuperseded = $this->supersedeDuplicateSourceReclassification($correctedContext, $history);
            $reclassPosted = $this->ensureGenuineReclassification($correctedContext, $lifecycle, $posting);
            $cashIftSuperseded = $this->recordCashIftSemanticSupersession($context, $audit);

            return compact('historyChanged', 'sourceDuplicatesSuperseded', 'reclassPosted', 'cashIftSuperseded');
        }, 3);

        $afterContext = $this->governedContext();
        $after = $this->snapshot($afterContext, $reports);
        $this->assertFinalState($afterContext, $after, $reports);

        $this->info('Phase 12.6 current state is corrected and reconciled.');
        $this->table(['Control', 'Result'], [
            ['Cash history semantic correction', $changed['historyChanged'] ? 'applied' : 'already current'],
            ['Duplicate source reclassification history', $changed['sourceDuplicatesSuperseded'].' voided / audit retained'],
            ['Legacy Cash IFT audit supersession', $changed['cashIftSuperseded'] ? 'recorded' : 'already recorded / not present'],
            ['Genuine IFT Rp1.200.000', $changed['reclassPosted'] ? 'posted through PostingEngine' : 'already posted'],
            ['Infaq & Tromol', self::idr($after['infaq'])],
            ['Dhuafa & Anak Yatim', self::idr($after['dhuafa'])],
            ['Dhuafa BNI component', self::idr($after['dhuafa_bni'])],
            ['Cash Tromol component', self::idr($after['dhuafa_cash'])],
            ['BNI / Cash / total liquidity', self::idr($after['bni']).' / '.self::idr($after['cash']).' / '.self::idr($after['liquidity'])],
        ]);

        return self::SUCCESS;
    }

    private function assertPermittedEnvironment(): void
    {
        $database = (string) DB::connection()->getDatabaseName();
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

    /** @return array{entity:AccountingEntity,actor:User,infaq:Fund,dhuafa:Fund,bni:FinancialAccount,cash:FinancialAccount,ift:TransactionType,cash_history:HistoricalFundHistory,cash_ift:?FinancialTransaction,cash_ift_semantically_superseded:bool,reclass_ift:?FinancialTransaction} */
    private function governedContext(): array
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->sole();
        $actor = User::query()->where('email', 'superadmin@emasjid.com')->first();
        if (! $actor) {
            throw new RuntimeException('The existing local QA Super Admin is required; this correction never creates or changes credentials.');
        }
        $funds = Fund::query()->where('accounting_entity_id', $entity->id)->whereIn('code', [self::INFAQ_CODE, self::DHUAFA_CODE])->where('status', 'active')->get()->keyBy('code');
        $accounts = FinancialAccount::query()->where('accounting_entity_id', $entity->id)->whereIn('code', [self::BNI_CODE, self::CASH_CODE])->where('status', 'active')->get()->keyBy('code');
        $ift = TransactionType::query()->where('accounting_entity_id', $entity->id)->where('code', self::IFT_CODE)->where('status', 'active')->sole();
        $cashHistory = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $entity->id)
            ->where('source_reference', MrjZiswafOpeningPosition::CASH_TROMOL_SOURCE_REFERENCE)
            ->sole();

        if ($funds->count() !== 2 || $accounts->count() !== 2) {
            throw new RuntimeException('The governed MRJ Funds and Financial Accounts are incomplete.');
        }

        return [
            'entity' => $entity,
            'actor' => $actor,
            'infaq' => $funds->get(self::INFAQ_CODE),
            'dhuafa' => $funds->get(self::DHUAFA_CODE),
            'bni' => $accounts->get(self::BNI_CODE),
            'cash' => $accounts->get(self::CASH_CODE),
            'ift' => $ift,
            'cash_history' => $cashHistory,
            'cash_ift' => $cashIft = $this->transaction($entity->id, MrjZiswafOpeningPosition::CASH_TROMOL_SUPERSEDED_TRANSFER_REFERENCE),
            'cash_ift_semantically_superseded' => $cashIft
                ? DB::table('financial_v2_audit_events')
                    ->where('accounting_entity_id', $entity->id)
                    ->where('event_type', 'cash_tromol_semantic_superseded')
                    ->where('target_type', 'transaction')
                    ->where('target_id', $cashIft->id)
                    ->exists()
                : false,
            'reclass_ift' => $this->transaction($entity->id, MrjZiswafOpeningPosition::FUND_RECLASSIFICATION_REFERENCE),
        ];
    }

    private function correctCashHistory(array $context, HistoricalFundHistoryService $history): bool
    {
        $record = $context['cash_history'];
        $expectedKey = hash('sha256', implode('|', [
            MrjZiswafOpeningPosition::allocationSourceAudit()['source_sha256'],
            self::DHUAFA_CODE,
            'account_position',
            MrjZiswafOpeningPosition::CASH_TROMOL_SOURCE_REFERENCE,
            'Cash Tromol Yatim',
            self::FINAL_CASH,
        ]));
        $payload = [
            'kind' => 'account_position',
            'notes' => 'Komposisi rekening/kas Dana Dhuafa & Anak Yatim sejak posisi awal; bukan penerimaan, pengeluaran, atau pemindahan Dana.',
            'amount' => self::FINAL_CASH,
            'date_label' => '14 Juni 2026',
            'description' => 'Cash Tromol Yatim',
            'source_reference' => MrjZiswafOpeningPosition::CASH_TROMOL_SOURCE_REFERENCE,
            'financial_account_code' => self::CASH_CODE,
            'position_treatment' => 'original_fund_account_composition',
        ];
        $final = $record->fund_id === $context['dhuafa']->id
            && $record->source_fund_code === self::DHUAFA_CODE
            && $record->source_key === $expectedKey
            && DecimalAmount::equals($record->amount, self::FINAL_CASH)
            && ($record->source_payload['position_treatment'] ?? null) === 'original_fund_account_composition';
        if ($final) {
            return false;
        }

        $history->correct($context['entity']->id, $record->id, [
            'fund_id' => $context['dhuafa']->id,
            'source_key' => $expectedKey,
            'source_fund_code' => self::DHUAFA_CODE,
            'source_payload' => $payload,
            'entry_kind' => 'account_position',
            'date_label' => '14 Juni 2026',
            'description' => 'Cash Tromol Yatim',
            'notes' => $payload['notes'],
            'amount' => self::FINAL_CASH,
            'correction_reason' => 'Phase 12.6: Cash Tromol Rp2.653.000 sejak awal merupakan komposisi rekening Dana Dhuafa & Anak Yatim, bukan reklasifikasi dari Dana Infaq & Tromol.',
        ], $context['actor']->id);

        return true;
    }

    private function supersedeDuplicateSourceReclassification(array $context, HistoricalFundHistoryService $history): int
    {
        $records = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $context['entity']->id)
            ->whereIn('fund_id', [$context['infaq']->id, $context['dhuafa']->id])
            ->where('description', 'Pemindahan Dana dari alokasi Infaq & Tromol')
            ->where('source_reference', 'Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf')
            ->where('amount', self::RECLASSIFICATION_AMOUNT)
            ->whereIn('entry_kind', ['receipt', 'usage'])
            ->where('status', '!=', 'void')
            ->lockForUpdate()
            ->get();

        foreach ($records as $record) {
            $history->voidRecord(
                $context['entity']->id,
                $record->id,
                'Phase 12.6: source-only reclassification explanation is superseded for display. The single official Rp1.200.000 movement is the Posted V2 Ledger IFT from Dana Infaq & Tromol to Dana Dhuafa & Anak Yatim.',
                $context['actor']->id,
            );
        }

        return $records->count();
    }

    private function ensureGenuineReclassification(array $context, FinancialTransactionLifecycleService $lifecycle, PostingEngine $posting): bool
    {
        $existing = $context['reclass_ift'];
        if ($existing) {
            if ($existing->status !== 'posted'
                || ! DecimalAmount::equals($existing->gross_amount, self::RECLASSIFICATION_AMOUNT)) {
                throw new RuntimeException('The genuine Rp1.200.000 Fund reclassification is not in a posted, idempotent state.');
            }

            return false;
        }

        $transaction = $lifecycle->createInterfundTransfer([
            'accounting_entity_id' => $context['entity']->id,
            'transaction_type_id' => $context['ift']->id,
            'source_reference' => MrjZiswafOpeningPosition::FUND_RECLASSIFICATION_REFERENCE,
            'business_date' => self::EFFECTIVE_DATE,
            'accounting_date' => self::EFFECTIVE_DATE,
            'description' => 'Historical Fund Reclassification: Dana Infaq & Tromol ke Dana Dhuafa & Anak Yatim berdasarkan keputusan sumber 16 Agustus 2026.',
            'gross_amount' => self::RECLASSIFICATION_AMOUNT,
            'primary_financial_account_id' => $context['bni']->id,
            'source_fund_id' => $context['infaq']->id,
            'destination_fund_id' => $context['dhuafa']->id,
            'policy_basis_ref' => 'PHASE-12.6|Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf|PDF p.3-4',
            'reason' => 'Historical Fund reclassification Rp1.200.000 dari Infaq & Tromol ke Dana Dhuafa & Anak Yatim; bukan penerimaan, pengeluaran, atau perpindahan rekening.',
            'idempotency_key' => 'mrj-p12.6-fund-reclassification-source-v1',
            'policy_version_ref' => 'PHASE-12.6-FUND-RECLASS',
        ], $context['actor']->id);
        $lifecycle->submit($transaction->id, $context['actor']->id);
        $lifecycle->verify($transaction->id, $context['actor']->id);
        $lifecycle->approve($transaction->id, $context['actor']->id);
        $posting->post(
            $transaction->id,
            'mrj-p12.6-fund-reclassification-post-v1',
            hash('sha256', 'MRJ-P12.6-FUND-RECLASS|'.self::RECLASSIFICATION_AMOUNT),
            $context['actor']->id,
        );

        return true;
    }

    private function recordCashIftSemanticSupersession(array $context, AuditTrailService $audit): bool
    {
        $cashIft = $context['cash_ift'];
        if (! $cashIft) {
            return false;
        }
        if ($cashIft->status !== 'posted' || ! DecimalAmount::equals($cashIft->gross_amount, self::FINAL_CASH)) {
            throw new RuntimeException('The legacy Cash Tromol IFT is not in the expected immutable state.');
        }
        $exists = DB::table('financial_v2_audit_events')
            ->where('accounting_entity_id', $context['entity']->id)
            ->where('event_type', 'cash_tromol_semantic_superseded')
            ->where('target_type', 'transaction')
            ->where('target_id', $cashIft->id)
            ->exists();
        if ($exists) {
            return false;
        }

        $audit->record(
            $context['entity']->id,
            'cash_tromol_semantic_superseded',
            'transaction',
            $cashIft->id,
            (string) Str::uuid(),
            $context['actor']->id,
            ['source_reference' => $cashIft->source_reference, 'treatment' => 'interfund_transfer'],
            [
                'treatment' => 'superseded_by_original_fund_account_composition',
                'reason' => 'Cash Tromol Rp2.653.000 sejak awal merupakan Dana Dhuafa & Anak Yatim; bukan pemindahan Dana dari Infaq & Tromol.',
                'source_reference' => MrjZiswafOpeningPosition::CASH_TROMOL_SOURCE_REFERENCE,
            ],
        );

        return true;
    }

    /** @return array{infaq:string,dhuafa:string,dhuafa_bni:string,dhuafa_cash:string,bni:string,cash:string,liquidity:string} */
    private function snapshot(array $context, FinancialReportService $reports): array
    {
        $funds = collect($reports->report('fund-balance', $context['entity']->id, '2026-01-01', self::EFFECTIVE_DATE)['data']['rows'])->keyBy('code');
        $composition = collect($reports->report('fund-balance', $context['entity']->id, '2026-01-01', self::EFFECTIVE_DATE)['data']['account_composition'])
            ->where('fund_code', self::DHUAFA_CODE)
            ->keyBy('financial_account_code');
        $accounts = collect($reports->report('account-balance', $context['entity']->id, '2026-01-01', self::EFFECTIVE_DATE)['data']['rows'])->keyBy('code');

        return [
            'infaq' => DecimalAmount::normalize((string) ($funds->get(self::INFAQ_CODE)['fund_balance'] ?? 0)),
            'dhuafa' => DecimalAmount::normalize((string) ($funds->get(self::DHUAFA_CODE)['fund_balance'] ?? 0)),
            'dhuafa_bni' => DecimalAmount::normalize((string) ($composition->get(self::BNI_CODE)['liquidity_balance'] ?? 0)),
            'dhuafa_cash' => DecimalAmount::normalize((string) ($composition->get(self::CASH_CODE)['liquidity_balance'] ?? 0)),
            'bni' => DecimalAmount::normalize((string) ($accounts->get(self::BNI_CODE)['closing_balance'] ?? 0)),
            'cash' => DecimalAmount::normalize((string) ($accounts->get(self::CASH_CODE)['closing_balance'] ?? 0)),
            'liquidity' => DecimalAmount::sum([
                (string) ($accounts->get(self::BNI_CODE)['closing_balance'] ?? 0),
                (string) ($accounts->get(self::CASH_CODE)['closing_balance'] ?? 0),
            ]),
        ];
    }

    private function assertFinalState(array $context, array $snapshot, FinancialReportService $reports): void
    {
        $expected = [
            'infaq' => self::FINAL_INFAQ,
            'dhuafa' => self::FINAL_DHUAFA,
            'dhuafa_bni' => self::FINAL_DHUAFA_BNI,
            'dhuafa_cash' => self::FINAL_CASH,
            'bni' => self::FINAL_BNI,
            'cash' => self::FINAL_CASH,
            'liquidity' => MrjZiswafOpeningPosition::TOTAL,
        ];
        foreach ($expected as $key => $amount) {
            if (! DecimalAmount::equals($snapshot[$key], $amount)) {
                throw new RuntimeException("Phase 12.6 reconciliation failed for {$key}: {$snapshot[$key]} != {$amount}.");
            }
        }
        $trial = $reports->report('trial-balance', $context['entity']->id, '2026-01-01', self::EFFECTIVE_DATE)['data'];
        if (! ($trial['is_balanced'] ?? false)) {
            throw new RuntimeException('Phase 12.6 trial balance is not balanced.');
        }
        $activeSourceDuplicates = HistoricalFundHistory::query()
            ->where('accounting_entity_id', $context['entity']->id)
            ->whereIn('fund_id', [$context['infaq']->id, $context['dhuafa']->id])
            ->where('description', 'Pemindahan Dana dari alokasi Infaq & Tromol')
            ->where('source_reference', 'Sisa Alokasi Dana Ziswaf DKM MRJ TCE (per 16 agustus 2026).pdf')
            ->where('amount', self::RECLASSIFICATION_AMOUNT)
            ->whereIn('entry_kind', ['receipt', 'usage'])
            ->where('status', '!=', 'void')
            ->exists();
        if ($activeSourceDuplicates) {
            throw new RuntimeException('Phase 12.6 source history still exposes a duplicate Rp1.200.000 reclassification.');
        }
    }

    private function transaction(string $entityId, string $sourceReference): ?FinancialTransaction
    {
        $matches = FinancialTransaction::query()
            ->where('accounting_entity_id', $entityId)
            ->where('source_reference', $sourceReference)
            ->get();
        if ($matches->count() > 1) {
            throw new RuntimeException("Duplicate transaction source reference detected: {$sourceReference}.");
        }

        return $matches->first();
    }

    private static function idr(string $amount): string
    {
        return 'Rp'.number_format((float) $amount, 0, ',', '.');
    }
}
