<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SantunanRamadhanRtRwEnrichmentSeeder extends Seeder
{
    private const YEAR = 2026;

    private const SOURCE = 'Pak Indra';

    private const COORDINATORS = [
        'PAK PARNO' => ['name' => 'Pak Parno', 'pairs' => ['06/07']],
        'PAK ARIF' => ['name' => 'Pak Arif', 'pairs' => ['01/06']],
        'BU SUSI' => ['name' => 'Bu Susi', 'pairs' => ['05/04']],
        'PAK WAKIDJO' => ['name' => 'Pak Wakidjo', 'pairs' => ['03/06']],
        'PAK WAWANG' => ['name' => 'Pak Wawang', 'pairs' => ['03/04']],
        'PAK ANAS' => ['name' => 'Pak Anas', 'pairs' => ['04/06']],
        'PAK PURWANTO' => ['name' => 'Pak Purwanto', 'pairs' => ['04/05', '01/05', '05/05']],
        'PAK MAMAN' => ['name' => 'Pak Maman', 'pairs' => ['02/06']],
        'PAK IBRAHIM' => ['name' => 'Pak Ibrahim', 'pairs' => ['01/09']],
    ];

    /**
     * @return array<string, mixed>
     */
    public function run(): array
    {
        $report = [
            'total_before' => DB::table('pendaftaran_anak_yatim_dhuafa')->count(),
            'target_before' => $this->targetQuery()->count(),
            'updated' => 0,
            'already_correct' => 0,
            'ambiguous' => 0,
            'remaining_null' => 0,
            'unexpected' => 0,
            'ambiguous_records' => [],
            'discrepancies' => [],
            'breakdown' => [],
        ];

        DB::transaction(function () use (&$report): void {
            $records = $this->targetQuery()->orderBy('id')->get();

            foreach ($records as $record) {
                $match = $this->matchAddress((string) $record->alamat);

                if (! $match['matched']) {
                    $report['ambiguous']++;
                    $report['ambiguous_records'][] = [
                        'id' => $record->id,
                        'name' => $record->nama_lengkap,
                        'address' => $record->alamat,
                        'reason' => $match['reason'],
                    ];

                    continue;
                }

                $expected = [
                    'rt' => $match['rt'],
                    'rw' => $match['rw'],
                    'nama_rt' => $match['nama_rt'],
                ];
                $conflicts = collect($expected)
                    ->filter(fn (string $value, string $field) => $record->{$field} !== null && $record->{$field} !== $value);

                if ($conflicts->isNotEmpty()) {
                    $report['unexpected']++;
                    $report['discrepancies'][] = [
                        'id' => $record->id,
                        'name' => $record->nama_lengkap,
                        'address' => $record->alamat,
                        'existing' => ['rt' => $record->rt, 'rw' => $record->rw, 'nama_rt' => $record->nama_rt],
                        'expected' => $expected,
                    ];

                    continue;
                }

                $changes = collect($expected)
                    ->filter(fn (string $value, string $field) => $record->{$field} === null)
                    ->all();

                if ($changes === []) {
                    $report['already_correct']++;

                    continue;
                }

                $update = $this->targetQuery()
                    ->where('id', $record->id)
                    ->where('alamat', $record->alamat);

                foreach (array_keys($changes) as $field) {
                    $update->whereNull($field);
                }

                if ($update->update($changes) !== 1) {
                    throw new \RuntimeException("Enrichment record {$record->id} tidak dapat diterapkan secara aman.");
                }

                $report['updated']++;
            }

            $report['total_after'] = DB::table('pendaftaran_anak_yatim_dhuafa')->count();
            $report['target_after'] = $this->targetQuery()->count();

            if ($report['total_before'] !== $report['total_after'] || $report['target_before'] !== $report['target_after']) {
                throw new \RuntimeException('Enrichment mengubah jumlah record dan telah dibatalkan.');
            }
        });

        $report['remaining_null'] = $this->targetQuery()
            ->where(fn ($query) => $query->whereNull('rt')->orWhereNull('rw')->orWhereNull('nama_rt'))
            ->count();
        $report['breakdown'] = $this->breakdown();

        $this->printReport($report);

        return $report;
    }

    private function targetQuery()
    {
        return DB::table('pendaftaran_anak_yatim_dhuafa')
            ->where('tahun_program', self::YEAR)
            ->where('sumber_informasi', self::SOURCE);
    }

    /**
     * @return array{matched: bool, reason?: string, rt?: string, rw?: string, nama_rt?: string}
     */
    private function matchAddress(string $address): array
    {
        $normalized = mb_strtoupper(str_replace("\u{00A0}", ' ', $address));
        $normalized = preg_replace('/\s+/u', ' ', trim($normalized));

        if (! preg_match('/\bRT\s*\.?\s*0*(\d{1,3})\s*(?:\/\s*(?:RW\s*\.?)?|RW\s*\.?)\s*0*(\d{1,3})\b/u', $normalized, $region)) {
            return ['matched' => false, 'reason' => 'Alamat tidak memiliki pasangan RT/RW yang dapat dipercaya.'];
        }

        $coordinatorMatches = collect(self::COORDINATORS)
            ->filter(fn (array $mapping, string $needle) => str_contains($normalized, $needle));

        if ($coordinatorMatches->count() !== 1) {
            return ['matched' => false, 'reason' => $coordinatorMatches->isEmpty()
                ? 'Nama koordinator tidak ditemukan pada alamat.'
                : 'Alamat memuat lebih dari satu nama koordinator.'];
        }

        $rt = str_pad((string) (int) $region[1], 2, '0', STR_PAD_LEFT);
        $rw = str_pad((string) (int) $region[2], 2, '0', STR_PAD_LEFT);
        $pair = $rt.'/'.$rw;
        $mapping = $coordinatorMatches->first();

        if (! in_array($pair, $mapping['pairs'], true)) {
            return ['matched' => false, 'reason' => "Pasangan {$pair} bertentangan dengan koordinator {$mapping['name']}."];
        }

        return [
            'matched' => true,
            'rt' => $rt,
            'rw' => $rw,
            'nama_rt' => $mapping['name'],
        ];
    }

    /**
     * @return array<string, int>
     */
    private function breakdown(): array
    {
        $actual = $this->targetQuery()
            ->whereNotNull('rt')
            ->whereNotNull('rw')
            ->whereNotNull('nama_rt')
            ->selectRaw('rt, rw, nama_rt, COUNT(*) as total')
            ->groupBy('rt', 'rw', 'nama_rt')
            ->get()
            ->keyBy(fn ($row) => $row->rt.'/'.$row->rw.'|'.$row->nama_rt);

        return collect(self::COORDINATORS)
            ->flatMap(fn (array $mapping) => collect($mapping['pairs'])->mapWithKeys(fn (string $pair) => [
                "RT {$pair} — {$mapping['name']}" => (int) ($actual->get($pair.'|'.$mapping['name'])->total ?? 0),
            ]))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function printReport(array $report): void
    {
        if (! $this->command) {
            return;
        }

        $this->command->table(['Metric', 'Total'], [
            ['Target Pak Indra 2026', $report['target_after']],
            ['Updated', $report['updated']],
            ['Already correct', $report['already_correct']],
            ['Ambiguous', $report['ambiguous']],
            ['Remaining NULL', $report['remaining_null']],
            ['Unexpected / discrepancy', $report['unexpected']],
        ]);
        $this->command->table(['RT/RW/Koordinator', 'Total'], collect($report['breakdown'])
            ->map(fn (int $total, string $label) => [$label, $total])->values()->all());

        foreach ($report['ambiguous_records'] as $record) {
            $this->command->warn("AMBIGUOUS ID {$record['id']} | {$record['name']} | {$record['address']} | {$record['reason']}");
        }

        foreach ($report['discrepancies'] as $record) {
            $this->command->warn("DISCREPANCY ID {$record['id']} | {$record['name']} | {$record['address']}");
        }
    }
}
