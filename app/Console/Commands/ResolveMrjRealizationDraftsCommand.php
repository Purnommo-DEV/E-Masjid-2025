<?php

namespace App\Console\Commands;

use App\Domain\FinancialV2\FinancialDomainException;
use App\Domain\FinancialV2\FinancialTransactionLifecycleService;
use App\Domain\FinancialV2\RealizationDraftReadService;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\FundRealization;
use App\Models\FinancialV2\Journal;
use App\Models\FinancialV2\JournalLine;
use App\Models\FinancialV2\LedgerEntry;
use App\Models\FinancialV2\Voucher;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Repairs only non-financial realization workflow state discovered before the
 * one-active-draft guard was introduced. It never deletes records and it
 * always delegates cancellation to the lifecycle service.
 */
final class ResolveMrjRealizationDraftsCommand extends Command
{
    protected $signature = 'financial-v2:resolve-mrj-realization-drafts
                            {--apply : Cancel only unposted, provably redundant realization drafts}
                            {--allow-testing : Permit execution only under testing on mrj_test_db}';

    protected $description = 'Inventory and safely resolve superseded or duplicate MRJ realization drafts.';

    public function handle(FinancialTransactionLifecycleService $lifecycle): int
    {
        $this->assertSafeDatabase();
        $entity = AccountingEntity::query()->where('code', 'MRJ-ACTUAL')->firstOrFail();
        $drafts = $this->activeDrafts($entity->id);
        $evidenceCounts = $this->evidenceCounts($drafts);

        $this->table(['Transaksi', 'Alokasi', 'Status alokasi', 'Status transaksi', 'Nominal', 'Bukti'], $drafts->map(function (FundRealization $realization) use ($evidenceCounts): array {
            $transaction = $realization->transaction;
            $allocation = $realization->budgetAllocationVersion?->allocation;

            return [
                $transaction?->source_reference ?? $transaction?->id ?? '—',
                $allocation?->allocation_reference ?? '—',
                $allocation?->status ?? '—',
                $transaction?->status ?? '—',
                $transaction?->gross_amount ?? '0.00',
                (string) $evidenceCounts->get($transaction?->id, 0),
            ];
        })->all());

        if (! $this->option('apply')) {
            $this->info('Dry-run selesai. Tidak ada Draft, transaksi, Journal, JournalLine, Ledger, atau voucher yang diubah.');

            return self::SUCCESS;
        }

        $factsBefore = $this->financialFactCounts($entity->id);
        $actorId = User::query()->orderBy('id')->value('id');
        $result = DB::transaction(function () use ($entity, $lifecycle, $actorId): array {
            $cancelledForAllocation = 0;
            $cancelledAsDuplicate = 0;

            foreach ($this->activeDrafts($entity->id) as $realization) {
                if ($realization->budgetAllocationVersion?->allocation?->status !== 'cancelled') {
                    continue;
                }

                $lifecycle->cancel(
                    $realization->transaction_id,
                    'Lifecycle cleanup: Draft Realisasi dibatalkan karena alokasi terkait telah dibatalkan.',
                    $actorId,
                );
                $cancelledForAllocation++;
            }

            $remaining = $this->activeDrafts($entity->id)
                ->filter(fn (FundRealization $realization) => $realization->budgetAllocationVersion?->allocation?->status === 'approved')
                ->groupBy('budget_allocation_version_id');

            foreach ($remaining as $versionDrafts) {
                if ($versionDrafts->count() <= 1) {
                    continue;
                }

                $evidenceCounts = $this->evidenceCounts($versionDrafts);
                $supported = $versionDrafts->filter(fn (FundRealization $realization) => $evidenceCounts->get($realization->transaction_id, 0) > 0);
                if ($supported->count() !== 1) {
                    throw new FinancialDomainException(
                        'E-REALIZATION-DUPLICATE-REVIEW',
                        'Duplicate Draft Realisasi tidak dapat dibatalkan otomatis karena tidak ada tepat satu draft yang didukung bukti aktif.',
                    );
                }

                $keeper = $supported->sole();
                foreach ($versionDrafts->where('id', '!=', $keeper->id) as $duplicate) {
                    if ($evidenceCounts->get($duplicate->transaction_id, 0) > 0) {
                        throw new FinancialDomainException('E-REALIZATION-DUPLICATE-REVIEW', 'Duplicate Draft Realisasi yang memiliki bukti aktif memerlukan peninjauan manual.');
                    }

                    $lifecycle->cancel(
                        $duplicate->transaction_id,
                        'Lifecycle cleanup: Draft Realisasi duplikat dibatalkan. Draft dengan bukti aktif dipertahankan sebagai satu-satunya draft alokasi.',
                        $actorId,
                    );
                    $cancelledAsDuplicate++;
                }
            }

            return compact('cancelledForAllocation', 'cancelledAsDuplicate');
        }, 3);

        if ($this->financialFactCounts($entity->id) !== $factsBefore) {
            throw new FinancialDomainException('E-REALIZATION-DRAFT-FACTS', 'Cleanup Draft Realisasi tidak boleh mengubah financial facts.');
        }

        $this->info("Selesai: {$result['cancelledForAllocation']} draft dari alokasi dibatalkan dan {$result['cancelledAsDuplicate']} draft duplikat dibatalkan. Tidak ada financial fact yang dibuat atau dihapus.");

        return self::SUCCESS;
    }

    /** @return Collection<int, FundRealization> */
    private function activeDrafts(string $entityId): Collection
    {
        return FundRealization::query()
            ->with([
                'transaction:id,accounting_entity_id,source_reference,status,gross_amount',
                'budgetAllocationVersion.allocation:id,allocation_reference,status',
            ])
            ->where('accounting_entity_id', $entityId)
            ->where('status', 'draft')
            ->whereHas('transaction', fn ($query) => $query->whereIn('status', RealizationDraftReadService::ACTIVE_TRANSACTION_STATUSES))
            ->orderBy('created_at')
            ->get();
    }

    /** @param Collection<int, FundRealization> $drafts */
    private function evidenceCounts(Collection $drafts): \Illuminate\Support\Collection
    {
        return AttachmentLink::query()
            ->where('target_type', 'transaction')
            ->whereIn('target_id', $drafts->pluck('transaction_id'))
            ->where('status', 'active')
            ->select('target_id', DB::raw('COUNT(*) as total'))
            ->groupBy('target_id')
            ->pluck('total', 'target_id');
    }

    /** @return array<string, int> */
    private function financialFactCounts(string $entityId): array
    {
        return [
            'journals' => Journal::query()->where('accounting_entity_id', $entityId)->count(),
            'journal_lines' => JournalLine::query()->where('accounting_entity_id', $entityId)->count(),
            'ledger_entries' => LedgerEntry::query()->where('accounting_entity_id', $entityId)->count(),
            'vouchers' => Voucher::query()->where('accounting_entity_id', $entityId)->count(),
        ];
    }

    private function assertSafeDatabase(): void
    {
        $database = DB::connection()->getDatabaseName();
        $testing = app()->environment('testing');
        $localActual = app()->environment(['local', 'development']) && $database === 'mrj_prod_db';
        $safeTesting = $testing && $database === 'mrj_test_db' && (bool) $this->option('allow-testing');

        if (! $localActual && ! $safeTesting) {
            throw new FinancialDomainException('E-REALIZATION-ENVIRONMENT', 'Command hanya dapat dijalankan pada local mrj_prod_db atau testing mrj_test_db dengan --allow-testing.');
        }
    }
}
