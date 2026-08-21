<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\FinancialPostingException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Executes one real session for the disposable Financial V2 concurrency rehearsal. */
final class FinancialV2ConcurrencyWorkerCommand extends Command
{
    protected $signature = 'financial-v2:concurrency-worker
        {--payload= : Base64-encoded JSON scenario payload}
        {--start-at= : Unix timestamp (microseconds) used to synchronize worker start}';

    protected $description = 'Internal worker for the test-only Financial V2 multi-session rehearsal.';

    public function handle(): int
    {
        $this->assertDisposableTestTarget();
        $payload = json_decode(base64_decode((string) $this->option('payload'), true), true, 512, JSON_THROW_ON_ERROR);
        $startAt = (float) $this->option('start-at');
        while ($startAt > microtime(true)) {
            usleep(1_000);
        }

        try {
            $result = $this->executeScenario($payload);
            $this->line(json_encode(['status' => 'posted'] + $result, JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (FinancialPostingException $exception) {
            $this->line(json_encode([
                'status' => 'rejected',
                'failure_code' => $exception->failureCode,
                'message' => $exception->getMessage(),
            ], JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->line(json_encode([
                'status' => 'error',
                'error_class' => $exception::class,
                'message' => $exception->getMessage(),
            ], JSON_THROW_ON_ERROR));

            return self::FAILURE;
        }
    }

    /** @param array<string, mixed> $payload @return array{transaction_id: string, journal_id: string, voucher_id: string} */
    private function executeScenario(array $payload): array
    {
        $service = app(FinancialTransactionLifecycleService::class);
        if ($payload['scenario'] === 'duplicate_post') {
            $result = $service->post($payload['transaction_id'], $payload['post_key'], hash('sha256', $payload['post_key']));

            return ['transaction_id' => $result->transactionId, 'journal_id' => $result->journalId, 'voucher_id' => $result->voucherId];
        }

        $input = [
            'accounting_entity_id' => $payload['entity_id'],
            'transaction_type_id' => $payload['transaction_type_id'],
            'business_date' => $payload['date'],
            'accounting_date' => $payload['date'],
            'gross_amount' => $payload['amount'],
            'source_reference' => 'CONC-'.strtoupper($payload['scenario']).'-'.Str::uuid(),
            'idempotency_key' => 'concurrency-source-'.Str::uuid(),
            'description' => 'Disposable concurrency rehearsal '.$payload['scenario'],
        ];
        $splits = [[
            'account_id' => $payload['split_account_id'],
            'split_amount' => $payload['amount'],
            'fund_id' => $payload['fund_id'],
            'program_id' => $payload['program_id'] ?? null,
        ]];

        $transaction = match ($payload['scenario']) {
            'receipt' => $service->createReceipt($input + [
                'primary_financial_account_id' => $payload['source_financial_account_id'],
                'category_id' => $payload['category_id'],
            ], $splits),
            'payment' => $service->createPayment($input + [
                'primary_financial_account_id' => $payload['source_financial_account_id'],
                'counterparty_id' => $payload['counterparty_id'],
                'category_id' => $payload['category_id'],
            ], $splits),
            'transfer' => $service->createTreasuryTransfer($input + [
                'source_financial_account_id' => $payload['source_financial_account_id'],
                'destination_financial_account_id' => $payload['destination_financial_account_id'],
            ], $splits),
            'realization' => $service->createRealization($input + [
                'primary_financial_account_id' => $payload['source_financial_account_id'],
                'counterparty_id' => $payload['counterparty_id'],
                'category_id' => $payload['category_id'],
            ], $splits, $payload['budget_allocation_version_id']),
            default => throw new \InvalidArgumentException('Unsupported concurrency scenario.'),
        };
        $service->submit($transaction->id);
        $service->verify($transaction->id);
        $service->approve($transaction->id);
        $postKey = 'concurrency-post-'.$payload['scenario'].'-'.Str::uuid();
        $result = $service->post($transaction->id, $postKey, hash('sha256', $postKey));

        return ['transaction_id' => $result->transactionId, 'journal_id' => $result->journalId, 'voucher_id' => $result->voucherId];
    }

    private function assertDisposableTestTarget(): void
    {
        if (! app()->environment('testing') || DB::connection()->getDatabaseName() !== 'mrj_test_db') {
            throw new \RuntimeException('Financial V2 concurrency workers may run only with APP_ENV=testing on mrj_test_db.');
        }
    }
}
