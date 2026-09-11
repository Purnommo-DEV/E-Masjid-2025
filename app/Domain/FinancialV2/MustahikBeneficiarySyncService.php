<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Counterparty;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Synchronizes beneficiary master data only; it has no financial writer. */
final class MustahikBeneficiarySyncService
{
    public function __construct(
        private readonly DistributionService $beneficiaries,
        private readonly MustahikMrjSource $source,
    ) {}

    /** @return array<string, mixed> */
    public function sync(AccountingEntity $entity, ?int $actorUserId = null): array
    {
        $records = collect($this->source->records());
        $sourceNameCounts = $records->countBy(fn (array $row): string => $this->normalize($row['name']));
        $people = Counterparty::forEntity($entity->id)->where('party_type', 'beneficiary')->get();
        $summary = [
            'source_records' => $records->count(),
            'approved' => $records->where('tanda_terima', 1)->count(),
            'not_approved' => $records->where('tanda_terima', 0)->count(),
            'created' => 0,
            'updated' => 0,
            'already_matched' => 0,
            'matched' => 0,
            'manual_review_resolved' => $records->where('was_manually_reviewed', true)->where('needs_manual_verification', false)->count(),
            'needs_manual_verification' => 0,
            'duplicate_prevented' => 0,
            'skipped_not_approved' => 0,
            'categories' => ['YATIM' => 0, 'DHUAFA' => 0, 'YATIM_DHUAFA' => 0, 'BELUM_DITENTUKAN' => 0],
            'review' => [],
        ];

        foreach ($records as $row) {
            if ($row['needs_manual_verification']) {
                $this->review($summary, $row, $row['review_reason']);

                continue;
            }

            $normalized = $this->normalize($row['name']);
            $candidates = $people->filter(fn (Counterparty $person): bool => $this->normalize($person->display_name) === $normalized);
            $match = $this->match($candidates, $row, ($sourceNameCounts[$normalized] ?? 0) > 1);
            if ($match === false) {
                $this->review($summary, $row, 'Lebih dari satu master existing cocok dan identitas tidak cukup untuk auto-merge.');

                continue;
            }
            if ($match instanceof Counterparty) {
                $summary['matched']++;
                $summary['duplicate_prevented']++;
            }

            if ($row['tanda_terima'] !== 1 && ! $match) {
                $summary['skipped_not_approved']++;

                continue;
            }

            $marker = sprintf('[Source: %s; No ALL: %03d; Tanda Terima: %d]', MustahikMrjSource::NAME, $row['source_no'], $row['tanda_terima']);
            $existingNotes = $match?->beneficiary_notes;
            $resolutionNote = $row['was_manually_reviewed']
                ? sprintf('[Manual review: "%s" resolved as "%s". %s]', $row['source_name'], $row['name'], $row['review_reason'])
                : null;
            $notes = str_contains((string) $existingNotes, $marker)
                ? $existingNotes
                : trim(implode("\n", array_filter([$existingNotes, $marker, $resolutionNote, $row['area'] ? 'Area sumber: '.$row['area'] : null])));
            $data = [
                'display_name' => $row['name'],
                'contact_reference' => $match?->contact_reference,
                'address' => $match?->address,
                'rt' => $row['rt'] ?? $match?->rt,
                'rw' => $row['rw'] ?? $match?->rw,
                'rt_coordinator_name' => $row['rt_coordinator_name'] ?? $match?->rt_coordinator_name,
                'beneficiary_type' => $match?->beneficiary_type ?: $row['beneficiary_type'],
                'beneficiary_notes' => $notes,
                // A zero never activates or deactivates an existing master.
                'status' => $row['tanda_terima'] === 1 ? 'active' : $match->status,
            ];

            if ($match) {
                $changed = collect($data)->contains(fn (mixed $value, string $key): bool => (string) $match->getAttribute($key) !== (string) $value);
                if (! $changed) {
                    $summary['already_matched']++;
                } else {
                    $this->beneficiaries->saveBeneficiary($entity->id, $data, $match->id, $actorUserId);
                    $summary['updated']++;
                }
            } else {
                $created = $this->beneficiaries->saveBeneficiary($entity->id, $data, null, $actorUserId);
                $people->push($created);
                $summary['created']++;
            }

            if ($row['tanda_terima'] === 1) {
                $summary['categories'][$data['beneficiary_type']]++;
            }
        }

        return $summary;
    }

    /** @return Counterparty|false|null False means ambiguous existing candidates. */
    private function match(Collection $candidates, array $row, bool $duplicateNameInSource): Counterparty|false|null
    {
        if ($candidates->isEmpty()) {
            return null;
        }
        if ($candidates->count() === 1 && ! $duplicateNameInSource) {
            return $candidates->first();
        }

        $sameRegion = $candidates->filter(fn (Counterparty $person): bool => (string) $person->rt === (string) $row['rt'] && (string) $person->rw === (string) $row['rw']);
        if ($sameRegion->count() === 1) {
            return $sameRegion->first();
        }
        if ($candidates->count() === 1 && $duplicateNameInSource) {
            return null;
        }

        return false;
    }

    private function normalize(string $name): string
    {
        return (string) Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish();
    }

    private function review(array &$summary, array $row, string $reason): void
    {
        $summary['needs_manual_verification']++;
        $summary['review'][] = [
            'source_no' => $row['source_no'],
            'source_name' => $row['source_name'],
            'tanda_terima' => $row['tanda_terima'],
            'reason' => $reason,
        ];
    }
}
