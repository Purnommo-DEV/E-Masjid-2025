<?php

namespace App\Domain\FinancialV2\Reporting;

use App\Domain\FinancialV2\DecimalAmount;
use App\Models\FinancialV2\Fund;
use Illuminate\Support\Collection;

/**
 * Read-only navigation taxonomy for operational Fund screens.
 *
 * The Financial V2 Foundation intentionally keeps Fund, Financial Account,
 * Program, and accounting dimensions separate. A management group is only a
 * presentation aid: it neither persists a new relationship nor changes a
 * Fund balance, financial account balance, policy, Journal, or Ledger fact.
 */
final class FundGroupingReadService
{
    /** @var array<string, array{name: string, description: string}> */
    private const GROUPS = [
        'operational' => [
            'name' => 'Operasional Masjid / Kas Masjid',
            'description' => 'Dana untuk kebutuhan rutin dan operasional masjid.',
        ],
        'ziswaf' => [
            'name' => 'Dana ZISWAF',
            'description' => 'Dana Zakat, Infaq, Sodaqoh, Fidyah, Yatim, Dhuafa, Wakaf, Beasiswa, Ramadhan, dan dana ZISWAF terkait.',
        ],
        'social' => [
            'name' => 'Dana Sosial / Kematian',
            'description' => 'Dana khusus bantuan sosial dan kematian.',
        ],
        'development' => [
            'name' => 'Perawatan / Pengembangan',
            'description' => 'Dana pemeliharaan, pengembangan fasilitas, dan sewa aula.',
        ],
        'other' => [
            'name' => 'Belum dikelompokkan',
            'description' => 'Dana aktif yang belum dapat ditempatkan dengan aman ke kelompok pengelolaan di atas.',
        ],
    ];

    /**
     * @param  Collection<int, array{fund: Fund, fund_balance: string, available_liquidity: string, financial_accounts?: array<int, string>}>  $fundCards
     * @return Collection<int, array{key: string, name: string, description: string, fund_count: int, fund_balance: string, financial_accounts: array<int, string>, funds: Collection<int, array{fund: Fund, fund_balance: string, available_liquidity: string, financial_accounts?: array<int, string>}>}>
     */
    public function groups(Collection $fundCards): Collection
    {
        $buckets = $fundCards
            ->groupBy(fn (array $card): string => $this->groupKey($card['fund']));

        return collect(array_keys(self::GROUPS))
            ->map(function (string $key) use ($buckets): ?array {
                /** @var Collection<int, array{fund: Fund, fund_balance: string, available_liquidity: string, financial_accounts?: array<int, string>}> $funds */
                $funds = $buckets->get($key, collect())->sortBy(fn (array $card): string => $card['fund']->name)->values();
                if ($funds->isEmpty()) {
                    return null;
                }

                return [
                    'key' => $key,
                    'name' => self::GROUPS[$key]['name'],
                    'description' => self::GROUPS[$key]['description'],
                    'fund_count' => $funds->count(),
                    // This is a sum of Fund balances, never a sum of
                    // financial-account liquidity.
                    'fund_balance' => DecimalAmount::sum($funds->pluck('fund_balance')),
                    // Account names are an optional location cue only. They
                    // must not imply that an account is itself a Fund.
                    'financial_accounts' => $funds
                        ->flatMap(fn (array $card): array => $card['financial_accounts'] ?? [])
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'funds' => $funds,
                ];
            })
            ->filter()
            ->values();
    }

    /** @return array{key: string, name: string, description: string, fund_count: int, fund_balance: string, financial_accounts: array<int, string>, funds: Collection<int, array{fund: Fund, fund_balance: string, available_liquidity: string, financial_accounts?: array<int, string>}>}|null */
    public function find(Collection $fundCards, string $key): ?array
    {
        return $this->groups($fundCards)->firstWhere('key', $key);
    }

    private function groupKey(Fund $fund): string
    {
        $identity = mb_strtoupper($fund->code.' '.$fund->name.' '.$fund->purpose_statement);

        if ($this->matches($identity, ['OPERASIONAL', 'KAS MASJID'])) {
            return 'operational';
        }

        if ($this->matches($identity, ['SOSIAL', 'KEMATIAN'])) {
            return 'social';
        }

        if ($this->matches($identity, ['SEWA AULA', 'SEWA-AULA', 'PERAWATAN', 'PEMELIHARAAN', 'PENGEMBANGAN'])) {
            return 'development';
        }

        if ($this->matches($identity, [
            'ZAKAT', 'INFAQ', 'INFAK', 'TROMOL', 'SODAQOH', 'SHODAQOH',
            'SEDEKAH', 'YATIM', 'FIDYAH', 'DHUAFA', 'WAKAF', 'BEASISWA',
            'RAMADHAN', 'ZISWAF',
        ])) {
            return 'ziswaf';
        }

        return 'other';
    }

    /** @param array<int, string> $terms */
    private function matches(string $identity, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($identity, $term)) {
                return true;
            }
        }

        return false;
    }
}
