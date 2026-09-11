<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\MustahikBeneficiarySyncService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SyncMrjMustahikBeneficiariesCommand extends Command
{
    protected $signature = 'financial-v2:sync-mrj-mustahik
        {--entity=MRJ-ACTUAL : AccountingEntity code}
        {--dry-run : Run the full matching pass and roll back all writes}
        {--allow-testing : Permit execution in the isolated mrj_test_db test run}';

    protected $description = 'Idempotently synchronize the local MRJ beneficiary master from the reviewed mustahik source.';

    public function handle(MustahikBeneficiarySyncService $sync): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Sinkronisasi master mustahik hanya diizinkan pada APP_ENV local/testing.');

            return self::FAILURE;
        }
        if (app()->environment('testing') && ! $this->option('allow-testing')) {
            $this->error('Gunakan --allow-testing hanya untuk database test terisolasi.');

            return self::FAILURE;
        }
        $host = (string) config('database.connections.'.config('database.default').'.host');
        if (! in_array($host, ['127.0.0.1', 'localhost'], true)) {
            $this->error('Database remote/hosting tidak diizinkan untuk sinkronisasi ini.');

            return self::FAILURE;
        }

        $entity = AccountingEntity::query()->where('code', $this->option('entity'))->where('status', 'active')->first();
        if (! $entity) {
            $this->error('AccountingEntity aktif tidak ditemukan.');

            return self::FAILURE;
        }

        $factsBefore = $this->financialFactCounts();
        DB::beginTransaction();
        try {
            $summary = $sync->sync($entity);
            if ($this->financialFactCounts() !== $factsBefore) {
                throw new \RuntimeException('Sinkronisasi master mencoba mengubah fakta finansial.');
            }
            $this->option('dry-run') ? DB::rollBack() : DB::commit();
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(($this->option('dry-run') ? 'DRY RUN' : 'SYNC').' beneficiary master selesai pada '.DB::connection()->getDatabaseName().'.');
        $this->table(['Metric', 'Jumlah'], [
            ['Source record', $summary['source_records']], ['Tanda Terima = 1', $summary['approved']], ['Tanda Terima = 0', $summary['not_approved']],
            ['Created', $summary['created']], ['Updated', $summary['updated']], ['Already matched', $summary['already_matched']],
            ['Matched total', $summary['matched']], ['Manual review resolved', $summary['manual_review_resolved']],
            ['Needs manual verification', $summary['needs_manual_verification']], ['Duplicate prevented', $summary['duplicate_prevented']],
            ['YATIM', $summary['categories']['YATIM']], ['DHUAFA', $summary['categories']['DHUAFA']], ['YATIM_DHUAFA', $summary['categories']['YATIM_DHUAFA']],
            ['BELUM_DITENTUKAN', $summary['categories']['BELUM_DITENTUKAN']],
        ]);
        if ($summary['review'] !== []) {
            $this->warn('Needs manual verification:');
            $this->table(['No ALL', 'Nama sumber', 'Tanda Terima', 'Alasan'], array_map(fn (array $row): array => array_values($row), $summary['review']));
        }
        $this->line('Tidak ada Distribution, Realization, Transaction, Journal, JournalLine, atau Ledger yang dibuat.');

        return self::SUCCESS;
    }

    /** @return array<int, int> */
    private function financialFactCounts(): array
    {
        return [FinancialTransaction::count(), Journal::count(), JournalLine::count(), LedgerEntry::count()];
    }
}
