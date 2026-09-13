<?php

namespace App\Services;

use App\Models\SantunanPerson;
use Edgaras\StrSim\JaroWinkler;
use Illuminate\Support\Str;

class SantunanIdentityMatcher
{
    /**
     * @return array{status: 'matched'|'new'|'ambiguous', person: ?SantunanPerson, candidates: array<int, array<string, mixed>>}
     */
    public function match(array $identity): array
    {
        if (filled($identity['person_id'] ?? null)) {
            $person = SantunanPerson::query()->find($identity['person_id']);

            return $person
                ? ['status' => 'matched', 'person' => $person, 'candidates' => []]
                : ['status' => 'ambiguous', 'person' => null, 'candidates' => [['person_id' => $identity['person_id'], 'evidence' => ['Stable identifier tidak ditemukan']]]];
        }

        $normalizedName = SantunanPerson::normalizeName($identity['nama_lengkap'] ?? null);
        $people = SantunanPerson::query()
            ->with(['participations' => fn ($query) => $query->latest('tahun_program')])
            ->where(function ($query) use ($normalizedName, $identity): void {
                $query->where('normalized_name', $normalizedName);

                if (filled($identity['tanggal_lahir'] ?? null)) {
                    $query->orWhere('tanggal_lahir', $identity['tanggal_lahir']);
                }
            })
            ->get();

        $candidates = $people
            ->map(fn (SantunanPerson $person) => $this->evaluate($person, $identity, $normalizedName))
            ->filter()
            ->values();
        $strong = $candidates->where('strong', true)->values();

        if ($strong->count() === 1) {
            return ['status' => 'matched', 'person' => $strong->first()['person'], 'candidates' => []];
        }

        if ($candidates->isNotEmpty()) {
            return [
                'status' => 'ambiguous',
                'person' => null,
                'candidates' => $candidates->map(fn (array $candidate) => collect($candidate)->except(['person', 'strong'])->all())->all(),
            ];
        }

        return ['status' => 'new', 'person' => null, 'candidates' => []];
    }

    /** @return array<string, mixed>|null */
    private function evaluate(SantunanPerson $person, array $identity, string $normalizedName): ?array
    {
        $latest = $person->participations->first();
        $nameExact = $person->normalized_name === $normalizedName;
        $nameSimilarity = JaroWinkler::similarity($person->normalized_name, $normalizedName);
        $birthProvided = filled($identity['tanggal_lahir'] ?? null) && $person->tanggal_lahir;
        $birthExact = $birthProvided && $person->tanggal_lahir->format('Y-m-d') === (string) $identity['tanggal_lahir'];
        $genderExact = $person->jenis_kelamin === ($identity['jenis_kelamin'] ?? null);
        $parentExact = $this->same($latest?->nama_orang_tua, $identity['nama_orang_tua'] ?? null);
        $phoneExact = $this->samePhone($latest?->no_wa, $identity['no_wa'] ?? null);
        $addressExact = $this->same($latest?->alamat, $identity['alamat'] ?? null);

        if (($birthProvided && ! $birthExact) || ! $genderExact) {
            return null;
        }

        $strong = $nameExact && (
            ($birthExact && $parentExact)
            || ($birthExact && $phoneExact)
            || ($parentExact && $phoneExact)
        );
        $plausible = $strong
            || ($nameExact && ($birthExact || $parentExact || $phoneExact || $addressExact))
            || ($nameSimilarity >= 0.90 && ($birthExact || $parentExact));

        if (! $plausible) {
            return null;
        }

        $evidence = collect([
            $nameExact ? 'nama sama' : 'nama mirip '.round($nameSimilarity * 100, 1).'%',
            $birthExact ? 'tanggal lahir sama' : null,
            $genderExact ? 'jenis kelamin sama' : null,
            $parentExact ? 'orang tua/wali sama' : null,
            $phoneExact ? 'nomor HP sama' : null,
            $addressExact ? 'alamat sama' : null,
        ])->filter()->values()->all();

        return [
            'person' => $person,
            'person_id' => $person->id,
            'nama' => $person->nama_lengkap,
            'tanggal_lahir' => $person->tanggal_lahir?->format('Y-m-d'),
            'jenis_kelamin' => $person->jenis_kelamin,
            'nama_orang_tua' => $latest?->nama_orang_tua,
            'no_wa' => $latest?->no_wa,
            'alamat' => $latest?->alamat,
            'evidence' => $evidence,
            'strong' => $strong,
        ];
    }

    private function same(?string $left, ?string $right): bool
    {
        return filled($left) && filled($right)
            && Str::lower(Str::squish($left)) === Str::lower(Str::squish($right));
    }

    private function samePhone(?string $left, ?string $right): bool
    {
        $left = preg_replace('/\D+/', '', (string) $left);
        $right = preg_replace('/\D+/', '', (string) $right);

        return $left !== '' && $right !== '' && $left === $right;
    }
}
