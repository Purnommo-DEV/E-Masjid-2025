<?php

namespace App\Domain\FinancialV2;

use Illuminate\Support\Collection;

final class FundPolicyRuleSet
{
    private const FIELDS = [
        'transaction_type_id',
        'account_id',
        'category_id',
        'program_id',
        'cost_center_id',
        'decision',
        'rationale',
    ];

    /** @return array{count:int,hash:string} */
    public static function signature(Collection $rules): array
    {
        $normalized = $rules
            ->map(fn ($rule): array => collect(self::FIELDS)
                ->mapWithKeys(fn (string $field): array => [$field => $rule->{$field}])
                ->all())
            ->sortBy(fn (array $rule): string => json_encode($rule, JSON_THROW_ON_ERROR))
            ->values()
            ->all();

        return [
            'count' => count($normalized),
            'hash' => hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR)),
        ];
    }

    /** @return list<string> */
    public static function cloneableFields(): array
    {
        return self::FIELDS;
    }
}
