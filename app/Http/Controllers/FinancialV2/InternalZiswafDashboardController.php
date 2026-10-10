<?php

namespace App\Http\Controllers\FinancialV2;

use App\Domain\FinancialV2\DecimalAmount;
use App\Domain\FinancialV2\Reporting\FinancialReportService;
use App\Domain\FinancialV2\Reporting\PostedLedgerQuery;
use App\Domain\FinancialV2\Reporting\PublicZiswafReportService;
use App\Domain\FinancialV2\Reporting\ZiswafFundScope;
use App\Domain\FinancialV2\Reporting\ZiswafReportingV2Service;
use App\Domain\FinancialV2\Reporting\ZiswafRunningBalanceService;
use App\Http\Middleware\InternalZiswafDashboardAccess;
use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Attachment;
use App\Models\FinancialV2\AttachmentLink;
use App\Models\FinancialV2\Distribution;
use App\Models\FinancialV2\FinancialTransaction;
use App\Models\FinancialV2\TransactionType;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

final class InternalZiswafDashboardController
{
    public function __construct(
        private readonly ZiswafReportingV2Service $reports,
        private readonly PostedLedgerQuery $ledger,
        private readonly FinancialReportService $financialReports,
        private readonly ZiswafFundScope $fundScope,
        private readonly PublicZiswafReportService $publicReports,
        private readonly ZiswafRunningBalanceService $runningBalances,
    ) {}

    public function access(Request $request, string $token)
    {
        $hash = strtolower(trim((string) config('financial_reporting.internal_ziswaf_dashboard.token_hash')));
        abort_unless(preg_match('/^[a-f0-9]{64}$/', $hash) && hash_equals($hash, hash('sha256', $token)), 404);
        $request->session()->regenerate();
        $request->session()->put(InternalZiswafDashboardAccess::SESSION_KEY, [
            'fingerprint' => hash('sha256', $hash),
            'expires_at' => now()->addMinutes(max(5, (int) config('financial_reporting.internal_ziswaf_dashboard.session_minutes', 120)))->timestamp,
        ]);

        return redirect()->route('internal.ziswaf.dashboard');
    }

    public function index(Request $request)
    {
        $input = $this->validated($request);
        $entity = $this->entity();
        $latest = $this->ledger->latestAccountingDate($entity->id) ?: now()->toDateString();
        $through = $input['through'] ?? $latest;
        $from = $input['from'] ?? CarbonImmutable::parse($through)->startOfMonth()->toDateString();
        $ziswafFundIds = $this->fundScope->ids($entity);
        $report = $this->reports->report($entity, $from, $through, $ziswafFundIds, $input);
        $positionReport = $this->publicReports->reportForEntity($entity, $from, $through);
        $runningBalance = $this->runningBalances->report($entity, $from, $through, $report['funds'] === [] ? [] : collect($report['funds'])->pluck('fund_id')->all(), $input['type'] ?? null);
        $balancePage = max(1, (int) ($input['balance_page'] ?? 1));
        $balancePerPage = 15;
        $balanceRows = collect($runningBalance['rows']);
        $runningBalancePaginator = new LengthAwarePaginator(
            $balanceRows->forPage($balancePage, $balancePerPage)->values(),
            $balanceRows->count(),
            $balancePerPage,
            $balancePage,
            ['path' => $request->url(), 'pageName' => 'balance_page', 'query' => $request->query()],
        );

        $transactions = collect($report['transactions']);
        $attachmentCounts = AttachmentLink::query()->where('accounting_entity_id', $entity->id)
            ->where('target_type', 'transaction')->where('status', 'active')
            ->whereIn('target_id', $transactions->pluck('transaction_id'))->select('target_id')
            ->selectRaw('COUNT(*) as aggregate')->groupBy('target_id')->pluck('aggregate', 'target_id');
        $transactions = $transactions->map(fn (array $row) => $row + ['attachment_count' => (int) $attachmentCounts->get($row['transaction_id'], 0)]);
        $page = max(1, (int) ($input['page'] ?? 1));
        $perPage = 15;
        $paginator = new LengthAwarePaginator($transactions->forPage($page, $perPage)->values(), $transactions->count(), $perPage, $page, [
            'path' => $request->url(), 'query' => $request->query(),
        ]);

        $allocation = DecimalAmount::sum(collect($report['programs'])->pluck('allocation'));
        $realization = DecimalAmount::sum(collect($report['programs'])->pluck('realization'));

        return view('masjid.mrj.guest.financial-v2.internal-ziswaf-dashboard', [
            'entity' => $entity, 'filters' => $input + compact('from', 'through'), 'report' => $report,
            'positionReport' => $positionReport, 'latestDate' => $latest,
            'runningBalance' => $runningBalance, 'runningBalanceRows' => $runningBalancePaginator,
            'allocation' => $allocation, 'realization' => $realization,
            'remainingAllocation' => DecimalAmount::subtract($allocation, $realization),
            'transactions' => $paginator,
            'options' => $this->options($entity),
        ]);
    }

    public function transaction(Request $request, FinancialTransaction $transaction)
    {
        $entity = $this->entity();
        abort_unless($transaction->accounting_entity_id === $entity->id, 404);
        $transaction->load(['type', 'splits.fund', 'primaryFinancialAccount', 'counterparty', 'category', 'realization.budgetAllocationVersion.allocation.program']);
        $attachments = AttachmentLink::query()->where('financial_v2_attachment_links.accounting_entity_id', $entity->id)
            ->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('financial_v2_attachment_links.status', 'active')
            ->join('financial_v2_attachments as attachment', 'attachment.id', '=', 'financial_v2_attachment_links.attachment_id')
            ->where('attachment.status', 'active')->orderByDesc('financial_v2_attachment_links.created_at')
            ->get(['attachment.id', 'attachment.original_filename', 'attachment.media_type', 'attachment.byte_size', 'attachment.received_at', 'financial_v2_attachment_links.evidence_type']);

        return view('masjid.mrj.guest.financial-v2.internal-ziswaf-transaction', compact('entity', 'transaction', 'attachments'));
    }

    public function distribution(Request $request, Distribution $distribution)
    {
        $entity = $this->entity();
        abort_unless($distribution->accounting_entity_id === $entity->id, 404);
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $distribution->load([
            'program', 'realization.transaction.type', 'realization.transaction.splits.fund',
            'realization.budgetAllocationVersion.allocation.program',
        ]);
        $latestCorrectionNumbers = DB::table('financial_v2_distribution_item_identity_corrections')
            ->select('distribution_item_id')->selectRaw('MAX(correction_no) as correction_no')->groupBy('distribution_item_id');
        $effectiveSnapshot = 'COALESCE(identity_correction.corrected_identity_snapshot, financial_v2_distribution_items.identity_snapshot)';
        $snapshotValue = fn (string $field): string => "JSON_UNQUOTE(JSON_EXTRACT({$effectiveSnapshot}, '$.{$field}'))";
        $itemsQuery = $distribution->items()
            ->leftJoinSub($latestCorrectionNumbers, 'latest_identity_correction_no', fn ($join) => $join->on('latest_identity_correction_no.distribution_item_id', '=', 'financial_v2_distribution_items.id'))
            ->leftJoin('financial_v2_distribution_item_identity_corrections as identity_correction', function ($join): void {
                $join->on('identity_correction.distribution_item_id', '=', 'financial_v2_distribution_items.id')
                    ->on('identity_correction.correction_no', '=', 'latest_identity_correction_no.correction_no');
            })
            ->select('financial_v2_distribution_items.*')
            ->with('latestIdentityCorrection')
            ->orderByRaw("COALESCE({$snapshotValue('rw')}, '')")
            ->orderByRaw("COALESCE({$snapshotValue('rt')}, '')")
            ->orderByRaw("COALESCE({$snapshotValue('display_name')}, '')")
            ->when($input['q'] ?? null, fn ($query, $value) => $query->whereRaw("LOWER({$snapshotValue('display_name')}) LIKE ?", ['%'.mb_strtolower($value).'%']))
            ->when($input['rt'] ?? null, fn ($query, $value) => $query->whereRaw("{$snapshotValue('rt')} = ?", [$value]))
            ->when($input['rw'] ?? null, fn ($query, $value) => $query->whereRaw("{$snapshotValue('rw')} = ?", [$value]));
        $allItems = $distribution->items();
        $totalRecipients = (clone $allItems)->count();
        $operationalTotal = DecimalAmount::normalize((string) (clone $allItems)->sum('amount'));
        $filteredCount = (clone $itemsQuery)->count();
        $filteredTotal = DecimalAmount::normalize((string) (clone $itemsQuery)->sum('financial_v2_distribution_items.amount'));
        $groupRows = (clone $itemsQuery)
            ->reorder()
            ->select([])
            ->selectRaw("COALESCE({$snapshotValue('rt')}, '') as snapshot_rt")
            ->selectRaw("COALESCE({$snapshotValue('rw')}, '') as snapshot_rw")
            ->selectRaw("COALESCE({$snapshotValue('rt_coordinator_name')}, '') as snapshot_coordinator")
            ->selectRaw('COUNT(*) as recipient_count, COALESCE(SUM(financial_v2_distribution_items.amount), 0) as group_total')
            ->groupBy('snapshot_rt', 'snapshot_rw', 'snapshot_coordinator')->get();
        $groups = $groupRows->mapWithKeys(fn ($row): array => [
            $this->recipientGroupKey((string) $row->snapshot_rt, (string) $row->snapshot_rw, (string) $row->snapshot_coordinator) => [
                'rt' => (string) $row->snapshot_rt, 'rw' => (string) $row->snapshot_rw,
                'coordinator' => (string) $row->snapshot_coordinator, 'recipient_count' => (int) $row->recipient_count,
                'total' => DecimalAmount::normalize((string) $row->group_total),
            ],
        ]);
        $items = $itemsQuery->paginate(25)->withQueryString();
        $pageGroups = $items->getCollection()->groupBy(fn ($item): string => $this->recipientGroupKey(
            (string) data_get($item->effective_identity_snapshot, 'rt'), (string) data_get($item->effective_identity_snapshot, 'rw'),
            (string) data_get($item->effective_identity_snapshot, 'rt_coordinator_name')
        ));
        $report = $this->reports->report($entity, $distribution->starts_on->toDateString(), $distribution->ends_on->toDateString());
        $reported = collect($report['distributions']['events'])->firstWhere('distribution_id', $distribution->id);

        return view('masjid.mrj.guest.financial-v2.internal-ziswaf-distribution', compact(
            'entity', 'distribution', 'items', 'input', 'totalRecipients', 'operationalTotal', 'filteredCount', 'filteredTotal', 'reported', 'groups', 'pageGroups'
        ));
    }

    public function attachment(Request $request, FinancialTransaction $transaction, Attachment $attachment, string $disposition = 'view')
    {
        $entity = $this->entity();
        abort_unless($transaction->accounting_entity_id === $entity->id && $attachment->accounting_entity_id === $entity->id, 404);
        $linked = AttachmentLink::query()->where('accounting_entity_id', $entity->id)->where('attachment_id', $attachment->id)
            ->where('target_type', 'transaction')->where('target_id', $transaction->id)->where('status', 'active')->exists();
        abort_unless($linked && $attachment->status === 'active' && Storage::disk('local')->exists($attachment->storage_reference), 404);
        abort_unless(in_array($attachment->media_type, ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], true) || $disposition === 'download', 415);

        $response = $disposition === 'download'
            ? Storage::disk('local')->download($attachment->storage_reference, $attachment->original_filename)
            : response()->file(Storage::disk('local')->path($attachment->storage_reference), ['Content-Type' => $attachment->media_type, 'Content-Disposition' => 'inline']);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox");

        return $response;
    }

    private function entity(): AccountingEntity
    {
        return AccountingEntity::query()->where('status', 'active')->findOrFail((string) config('financial_reporting.internal_ziswaf_dashboard.entity_id'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'], 'through' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'fund_id' => ['nullable', 'uuid'], 'program_id' => ['nullable', 'uuid'], 'category_id' => ['nullable', 'uuid'],
            'type' => ['nullable', 'string', 'max:40'], 'status' => ['nullable', Rule::in(['posted'])],
            'page' => ['nullable', 'integer', 'min:1'], 'balance_page' => ['nullable', 'integer', 'min:1'],
        ]);
    }

    private function options(AccountingEntity $entity): array
    {
        $options = $this->financialReports->filterOptions($entity->id);
        $ziswafFundIds = $this->fundScope->ids($entity);
        $options['funds'] = collect($options['funds'])
            ->filter(fn (array $fund): bool => in_array($fund['id'], $ziswafFundIds, true))
            ->values()
            ->all();
        $options['types'] = TransactionType::query()->where('accounting_entity_id', $entity->id)->orderBy('code')->get(['code', 'name'])
            ->map(fn ($type) => ['id' => $type->code, 'label' => $type->name])->all();

        return $options;
    }

    private function recipientGroupKey(string $rt, string $rw, string $coordinator): string
    {
        return mb_strtolower(trim($rw))."\x1F".mb_strtolower(trim($rt))."\x1F".mb_strtolower(trim($coordinator));
    }
}
