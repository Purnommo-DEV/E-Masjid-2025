<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\Counterparty;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** One canonical name matcher for beneficiary UI and imports. */
final class BeneficiaryDuplicateService
{
    public function normalize(string $name): string
    {
        return (string) Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish();
    }

    public function exact(string $entityId, string $name): Collection
    {
        $normalized = $this->normalize($name);
        if ($normalized === '') {
            return collect();
        }

        return Counterparty::forEntity($entityId)
            ->where('party_type', 'beneficiary')
            ->get()
            ->filter(fn (Counterparty $person): bool => $this->normalize($person->display_name) === $normalized)
            ->values();
    }

    public function suggestions(string $entityId, string $name, int $limit = 10): Collection
    {
        $name = trim($name);
        if ($name === '') {
            return collect();
        }

        return Counterparty::forEntity($entityId)
            ->where('party_type', 'beneficiary')
            ->where('display_name', 'like', '%'.$name.'%')
            ->orderBy('display_name')
            ->limit($limit)
            ->get(['id', 'display_name', 'address', 'rt', 'rw', 'rt_coordinator_name', 'contact_reference', 'status']);
    }
}
