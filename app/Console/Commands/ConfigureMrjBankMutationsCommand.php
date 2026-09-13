<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\ConfigureFinancialV2DefaultsService;
use App\Domain\FinancialV2\ConfigureMrjBankMutationsService;
use Illuminate\Console\Command;
use Throwable;

final class ConfigureMrjBankMutationsCommand extends Command
{
    protected $signature = 'financial-v2:configure-mrj-bank-mutations
        {--entity=MRJ-ACTUAL : Accounting Entity code; only MRJ-ACTUAL is supported}
        {--actor= : User id recorded as approver/author}
        {--apply : Persist master configuration; without this flag the command is read-only}';

    protected $description = 'Provision governed RCV/PAY bank-mutation categories and historical policy without posting transactions.';

    public function __construct(private readonly ConfigureFinancialV2DefaultsService $configuration)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('entity') !== ConfigureMrjBankMutationsService::ENTITY_CODE) {
            $this->error('Command ini hanya dapat mengonfigurasi Accounting Entity MRJ-ACTUAL.');

            return self::FAILURE;
        }

        $this->scopeTable();
        if (! $this->option('apply')) {
            $status = $this->configuration->mrjBankMutationStatus();
            $this->line('Status: '.($status['active'] ? 'ACTIVE' : 'BELUM LENGKAP'));
            if (! $status['active']) {
                $this->warn('Belum tersedia: '.implode(', ', $status['missing']));
            }
            $this->warn('Dry-run selesai. Tambahkan --apply untuk menyimpan master/config saja.');

            return self::SUCCESS;
        }

        try {
            $actor = $this->option('actor') !== null ? (int) $this->option('actor') : null;
            $result = $this->configuration->configureMrjBankMutations($actor);
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Konfigurasi Mutasi Bank gagal: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info($result['changed']
            ? 'Master/config Mutasi Bank berhasil diaktifkan. Tidak ada financial fact yang dibuat.'
            : 'Konfigurasi Mutasi Bank sudah aktif. Tidak ada perubahan atau financial fact baru.');
        $this->table(
            ['Financial fact', 'Before', 'After'],
            collect($result['facts_before'])->map(fn (int $before, string $name): array => [$name, $before, $result['facts_after'][$name]])->values()->all(),
        );

        return self::SUCCESS;
    }

    private function scopeTable(): void
    {
        $this->table(['Scope', 'Value'], [
            ['Entity', ConfigureMrjBankMutationsService::ENTITY_CODE],
            ['Rekening', ConfigureMrjBankMutationsService::FINANCIAL_ACCOUNT_CODE],
            ['Dana', ConfigureMrjBankMutationsService::FUND_CODE],
            ['Effective from', ConfigureMrjBankMutationsService::EFFECTIVE_FROM],
            ['Categories', implode(', ', ConfigureMrjBankMutationsService::CATEGORY_CODES)],
            ['Evidence', 'statement (minimum 1)'],
            ['Approval', 'Maker + 1 checker/approver decision'],
            ['Financial transactions posted', '0'],
        ]);
    }
}
