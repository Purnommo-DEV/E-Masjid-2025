<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\Reporting\DistributionReportingService;
use App\Domain\FinancialV2\Reporting\PostedLedgerQuery;
use App\Domain\FinancialV2\Reporting\ZiswafFundScope;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\FundRealization;
use App\Support\ZiswafPdfQrCode;
use Illuminate\Http\Request;

/** Signed read-only detail of one distribution, with explicitly shareable fields only. */
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

        // Same effective realization and identity corrections used by the internal detail.
        $realization = FundRealization::with(['transaction.splits.fund.type', 'transaction.latestDateCorrection'])
            ->whereHas('transaction', fn ($query) => $query->where('accounting_entity_id', $entity))
            ->findOrFail($event['effective_realization_id']);
        $transaction = $realization->transaction;
        $items = $record->items()->with(['distribution', 'beneficiary', 'latestIdentityCorrection.correctedBeneficiary'])->get();
        $recipients = $items->map(static fn ($item): array => [
            'name' => filled(data_get($item->effective_identity_snapshot, 'display_name'))
                ? data_get($item->effective_identity_snapshot, 'display_name') : '—',
            'amount' => (string) $item->amount,
            'category' => $item->beneficiary_type_resolution['label'],
            'category_from_current_master' => $item->beneficiary_type_resolution['from_current_master'],
            'rt' => trim((string) data_get($item->effective_identity_snapshot, 'rt', '')),
            'rw' => trim((string) data_get($item->effective_identity_snapshot, 'rw', '')),
            // No per-recipient delivery status exists: never infer receipt from beneficiary status.
            'status' => (string) $record->status,
        ])->sortBy(static fn (array $item): string => mb_strtolower($item['name']))->values();

        $groups = $recipients->groupBy(static fn (array $item): string => json_encode([$item['rw'], $item['rt']]))
            ->map(function ($members): array {
                $total = DecimalAmount::sum($members->pluck('amount'));

                return [
                    'rt' => $members->first()['rt'], 'rw' => $members->first()['rw'],
                    'count' => $members->count(), 'total' => $total,
                    'average' => $this->average($total, $members->count()),
                    'statuses' => $members->countBy('status'), 'members' => $members,
                ];
            })->sort(static fn (array $left, array $right): int => strnatcasecmp($left['rw'], $right['rw']) ?: strnatcasecmp($left['rt'], $right['rt']))->values();
        $options = [
            'rt' => $recipients->pluck('rt')->filter()->unique()->sort(SORT_NATURAL)->values(),
            'rw' => $recipients->pluck('rw')->filter()->unique()->sort(SORT_NATURAL)->values(),
            'status' => $recipients->pluck('status')->unique()->sort()->values(),
        ];
        // Existing evidence has no public-publication consent field/workflow.
        // Never equate an active attachment or a photo MIME type with publication permission.
        $attachments = collect();
        $nonce = base64_encode(random_bytes(18));

        // Never pass models, raw snapshots, private notes, or account identifiers to the view.
        $summary = [
            'program' => $event['program_name'], 'period' => $event['period'],
            'from' => $event['from'], 'through' => $event['through'],
            'recipient_count' => $event['recipient_count'], 'actual_amount' => $event['actual_amount'],
            'operational_total' => DecimalAmount::sum($items->pluck('amount')),
            'operational_status' => $record->status, 'financial_status' => $transaction->status,
            'funds' => $transaction->splits->pluck('fund.name')->filter()->unique()->values()->all(),
            'business_date' => $transaction->effective_business_date?->format('d/m/Y') ?: '—',
            'accounting_date' => $transaction->accounting_date?->format('d/m/Y') ?: '—',
            'fund_categories' => $transaction->splits->pluck('fund.type.name')->filter()->unique()->values()->all(),
            'recipient_categories' => $recipients->pluck('category')->unique()->values()->all(),
            'rt_count' => $recipients->filter(fn ($item) => $item['rt'] !== '')->map(fn ($item) => json_encode([$item['rw'], $item['rt']]))->unique()->count(),
            'rw_count' => $options['rw']->count(),
            'average' => $this->average(DecimalAmount::sum($items->pluck('amount')), $items->count()),
        ];

        return response()->view('masjid.mrj.guest.financial-v2.ziswaf-distribution-share', compact('summary', 'recipients', 'groups', 'options', 'attachments', 'nonce'))
            ->header('Cache-Control', 'private, no-store, max-age=0')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Content-Security-Policy', "default-src 'none'; script-src 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline'; font-src 'self'; base-uri 'none'; frame-ancestors 'none'");
    }

    /** Presentation average in exact cents, rounded half up, without floating point. */
    private function average(string $total, int $count): string
    {
        if ($count < 1) {
            return '0.00';
        }
        $normalized = DecimalAmount::normalize($total);
        $negative = str_starts_with($normalized, '-');
        $digits = str_replace(['-', '.'], '', $normalized);
        $remainder = 0;
        $quotient = '';
        foreach (str_split($digits) as $digit) {
            $remainder = $remainder * 10 + (int) $digit;
            $quotient .= (string) intdiv($remainder, $count);
            $remainder %= $count;
        }
        $quotient = str_pad(ltrim($quotient, '0'), 3, '0', STR_PAD_LEFT);
        $average = substr($quotient, 0, -2).'.'.substr($quotient, -2);
        if ($remainder * 2 >= $count) {
            $average = DecimalAmount::add($average, '0.01');
        }

        return $negative ? DecimalAmount::negate($average) : $average;
    }
}
