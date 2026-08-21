<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\HistoricalFundHistoryService;
use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Console\Command;

/** Idempotently persists the approved workbook's source-only Fund history. */
final class SyncMrjZiswafHistoricalFundHistoryCommand extends Command
{
    protected $signature = 'financial-v2:sync-mrj-ziswaf-history
        {--allow-testing : Permit execution only in the isolated test database}
        {--dry-run : Validate the MRJ source mapping without writing history records}';

    protected $description = 'Synchronize source-only MRJ ZISWAF Fund-history lineage without creating accounting facts.';

    public function handle(HistoricalFundHistoryService $history): int
    {
        if (app()->environment('testing') && ! $this->option('allow-testing')) {
            $this->error('Use --allow-testing only for the isolated mrj_test_db test run.');

            return self::FAILURE;
        }
        if (app()->environment('production')) {
            $this->error('This local source-history synchronization is not permitted in production.');

            return self::FAILURE;
        }

        $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->where('status', 'active')->first();
        if (! $entity) {
            $this->warn('MRJ-ACTUAL tidak ditemukan; tidak ada riwayat sumber yang disinkronkan.');

            return self::SUCCESS;
        }
        if ($this->option('dry-run')) {
            $this->info('Pemetaan MRJ ZISWAF tervalidasi. Tidak ada record riwayat sumber yang ditulis.');

            return self::SUCCESS;
        }

        $result = $history->syncMrjZiswafSource($entity);
        $this->info("Riwayat sumber MRJ ZISWAF tersinkron: {$result['created']} baru, {$result['existing']} sudah ada, {$result['total']} total.");
        $this->line('Tidak ada transaksi, Journal, JournalLine, Ledger, atau Opening Balance yang dibuat atau diubah.');

        return self::SUCCESS;
    }
}
