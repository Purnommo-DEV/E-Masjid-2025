<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\Reporting\DistributionReportingService;
use App\Domain\FinancialV2\Reporting\PostedLedgerQuery;
use App\Domain\FinancialV2\Reporting\ZiswafFundScope;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Distribution;
use App\Support\ZiswafPdfQrCode;
use Illuminate\Http\Request;

/** Signed read-only disclosure of aggregates for one distribution, never identities. */
final class ZiswafDistributionShareController
{
    public function __construct(
        private readonly DistributionReportingService $reports,
        private readonly ZiswafFundScope $fundScope,
        private readonly PostedLedgerQuery $ledger,
    ) {}

    public function __invoke(Request $request, string $entity, string $distribution)
    {
        $accountingEntity = AccountingEntity::query()->where('status', 'active')
            ->where('id', (string) config('financial_reporting.internal_ziswaf_dashboard.entity_id'))
            ->findOrFail($entity);
        $record = Distribution::query()->where('accounting_entity_id', $accountingEntity->id)->findOrFail($distribution);
        $through = max($record->ends_on->toDateString(), (string) ($this->ledger->latestAccountingDate($entity) ?: $record->ends_on->toDateString()));
        $report = $this->reports->report($entity, $record->starts_on->toDateString(), $through, $this->fundScope->ids($accountingEntity), restricted: true);
        $event = collect($report['events'])->firstWhere('distribution_id', $record->id);
        abort_unless($event !== null && ZiswafPdfQrCode::shareable($event), 404);

        // Explicit allow-list. Do not pass models, recipient snapshots, or report DTOs to the view.
        $summary = [
            'program' => $event['program_name'], 'period' => $event['period'],
            'from' => $event['from'], 'through' => $event['through'],
            'recipient_count' => $event['recipient_count'], 'actual_amount' => $event['actual_amount'],
        ];

        return response()->view('masjid.mrj.guest.financial-v2.ziswaf-distribution-share', ['summary' => $summary])
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Content-Security-Policy', "default-src 'none'; style-src 'unsafe-inline'; base-uri 'none'; frame-ancestors 'none'");
    }
}
