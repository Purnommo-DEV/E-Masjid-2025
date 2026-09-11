<?php

namespace Database\Seeders;

use App\Models\FinancialV2\AccountingEntity;
use App\Models\FinancialV2\Counterparty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Reconciles only the MRJ-ACTUAL beneficiary master with the approved 107-row source.
 *
 * Existing source lineage and primary keys are retained. Non-source beneficiary rows
 * are never deleted; they are retained as inactive master/history records.
 */
final class MrjActualMustahikSeeder extends Seeder
{
    private const ENTITY_CODE = 'MRJ-ACTUAL';

    private const SOURCE_PREFIX = 'MRJ-MUSTAHIK-NO-';

    /** @var array<string, int> */
    public array $summary = [];

    public function run(): void
    {
        $entity = AccountingEntity::query()->where('code', self::ENTITY_CODE)->where('status', 'active')->sole();
        $records = collect($this->records());
        $this->validateSource($records);
        $factsBefore = $this->financialFactCounts();
        $transactionLinksBefore = $this->transactionCounterpartyLinks();

        $this->summary = DB::transaction(function () use ($entity, $records, $factsBefore, $transactionLinksBefore): array {
            $people = Counterparty::forEntity($entity->id)
                ->where('party_type', 'beneficiary')
                ->lockForUpdate()
                ->get();
            $sourceIds = collect();
            $summary = [
                'source' => $records->count(),
                'reused' => 0,
                'created' => 0,
                'updated' => 0,
                'retained_non_source_inactive' => 0,
                'historical_references_retained' => 0,
                'deactivated_non_source' => 0,
                'active' => 0,
            ];

            foreach ($records as $record) {
                $person = $this->match($people, $record);
                $attributes = [
                    'accounting_entity_id' => $entity->id,
                    'code' => $this->sourceCode($record['legacy_source_no']),
                    'party_type' => 'beneficiary',
                    'beneficiary_type' => 'BELUM_DITENTUKAN',
                    'display_name' => $record['name'],
                    'external_reference' => $this->sourceReference($record['legacy_source_no']),
                    'rt' => $record['rt'],
                    'rw' => $record['rw'],
                    'rt_coordinator_name' => $record['coordinator'],
                    'status' => 'active',
                ];

                if ($person) {
                    $summary['reused']++;
                    $changed = collect($attributes)->contains(
                        fn (mixed $value, string $key): bool => (string) $person->getAttribute($key) !== (string) $value
                    );
                    if ($changed) {
                        // Preserve non-source fields such as address, phone, notes, and the existing primary key.
                        $person->fill($attributes)->save();
                        $summary['updated']++;
                    }
                } else {
                    $person = Counterparty::create($attributes);
                    $people->push($person);
                    $summary['created']++;
                }

                $sourceIds->push($person->id);
            }

            foreach ($people->whereNotIn('id', $sourceIds) as $oldMaster) {
                if ($this->hasHistoricalReference($oldMaster->id)) {
                    $summary['historical_references_retained']++;
                }
                $summary['retained_non_source_inactive']++;
                if ($oldMaster->status !== 'inactive') {
                    $oldMaster->update(['status' => 'inactive']);
                    $summary['deactivated_non_source']++;
                }
            }

            $summary['active'] = $this->validateResult($entity->id, $records);
            if ($this->financialFactCounts() !== $factsBefore) {
                throw new RuntimeException('Seeder mengubah jumlah financial facts; seluruh perubahan dibatalkan.');
            }
            if ($this->transactionCounterpartyLinks() !== $transactionLinksBefore) {
                throw new RuntimeException('Seeder mengubah referensi counterparty transaksi; seluruh perubahan dibatalkan.');
            }

            return $summary;
        });

        if ($this->command) {
            $this->command->info('Master Penerima MRJ-ACTUAL berhasil direkonsiliasi.');
            $this->command->table(['Metric', 'Jumlah'], collect($this->summary)->map(fn ($value, $key) => [$key, $value])->values()->all());
        }
    }

    /** @return array<int, array{legacy_source_no:int,name:string,rt:string,rw:string,coordinator:string}> */
    public function records(): array
    {
        return array_merge(
            $this->group('01', '06', 'Pak Arif', [
                64 => 'Anih Nurhayati', 63 => 'Faridah', 60 => 'Fiennya atau Tafiq', 62 => 'Hayati',
                67 => 'Hj. Maswanih', 61 => 'Masliah', 59 => 'Neneng Asmanih', 66 => 'Sapuroh', 65 => 'Unu Kusum',
            ]),
            $this->group('02', '06', 'Pak Maman', [
                36 => 'Abdul Rohman', 34 => 'Atiqah', 46 => 'Hawiyah', 33 => 'Hidayah', 35 => 'Hj. Majlifi',
                47 => 'Icla K Rhodah', 44 => 'Irmayatani', 30 => 'Karimina', 29 => 'Khotati', 40 => 'Komariah',
                41 => 'Mariam', 42 => 'Minim', 32 => 'Mukhlis', 28 => 'Mulyati', 43 => 'Munhari', 37 => 'Nasik',
                48 => 'Pak Agus', 27 => 'Sri Murni', 45 => 'Suherni', 39 => 'Sukarti', 38 => 'Supanci', 31 => 'Unyanah',
            ]),
            $this->group('03', '04', 'Pak Wawang', [
                76 => 'Irsinah', 78 => 'Irsyinah', 73 => 'Maesaroh', 79 => 'Marjuki', 77 => 'Munayah',
                71 => 'Mursidi', 72 => 'Nadiah', 74 => 'Nasrina', 68 => 'Satiyem', 75 => 'Sirmunjiyati',
                70 => 'Sumini', 69 => 'Sumiyah',
            ]),
            $this->group('03', '06', 'Wakijo', [
                5 => 'Beno', 10 => 'Fitnugawati', 7 => 'Kari', 1 => 'Kokom', 3 => 'Muanah',
                9 => 'Ranten', 8 => 'Rinah', 2 => 'Sainah', 6 => 'Sumiani', 4 => 'Yuyin',
            ]),
            $this->group('04', '06', 'Bu Anas', [
                11 => 'Ai Herlina', 24 => 'Ajidin', 15 => 'Amenih', 20 => 'Halimah (Tambahan)', 18 => 'Jariyah',
                16 => 'Saali', 13 => 'Saira', 12 => 'Terni', 19 => 'Wakino',
            ]),
            $this->group('05', '04', 'Bu Susi', [
                98 => 'Abdul Rozak', 100 => 'Hj Rabiah Arab', 101 => 'Imsiran', 94 => 'Luswita', 97 => 'Misni',
                96 => 'Rohedah', 95 => 'Subur', 93 => 'Sukarti', 99 => 'Yuli',
            ]),
            $this->group('06', '07', 'Pak Farno', [
                89 => 'Bi Hamdah', 92 => 'Ismiyul Hasanah', 91 => 'Mima Mbeng', 90 => 'Mpo Atik',
                86 => 'Pak Sarlan', 88 => 'Reza', 87 => 'Romlah',
            ]),
            $this->group('TCE', '08', 'Pak Indra (TCE)', [
                133 => 'Ajis', 115 => 'Darsono', 114 => 'Daus', 119 => 'Gofur (Tk Taman)', 131 => 'Hakin',
                139 => 'Hartoyo', 112 => 'Ibu Jamu', 123 => 'Ibu Moses', 116 => 'Irwan (Tk Taman)', 132 => 'Joko',
                129 => 'Kurtubi', 128 => 'Muhajir', 134 => 'Nawiri', 127 => 'Ohi Adam', 138 => 'Pak Sur Warbot',
                122 => 'Picin (Tk Taman)', 126 => 'saiful', 135 => 'Sajiman', 125 => 'Sapardi', 118 => 'Sofwan (Tk Taman)',
                136 => 'Suraeli', 130 => 'Suryadi', 121 => 'Tk Taman (Tk Taman)', 111 => 'Tuna Netra Depu',
                120 => 'Ulay (Tk Taman)', 137 => 'Wahono', 117 => 'Yani (Tk Taman)', 113 => 'Yanto', 124 => 'Zuraeli',
            ]),
        );
    }

    /** @param array<int, string> $names */
    private function group(string $rt, string $rw, string $coordinator, array $names): array
    {
        return collect($names)->map(
            fn (string $name, int $sourceNo): array => [
                'legacy_source_no' => $sourceNo,
                'name' => $name,
                'rt' => $rt,
                'rw' => $rw,
                'coordinator' => $coordinator,
            ]
        )->values()->all();
    }

    private function match(Collection $people, array $record): ?Counterparty
    {
        $reference = $this->sourceReference($record['legacy_source_no']);
        $code = $this->sourceCode($record['legacy_source_no']);
        $priorities = [
            fn (Counterparty $person): bool => $person->external_reference === $reference,
            fn (Counterparty $person): bool => $person->code === $code,
            fn (Counterparty $person): bool => $this->legacySourceNo($person->beneficiary_notes) === $record['legacy_source_no'],
            fn (Counterparty $person): bool => $this->normalize($person->display_name) === $this->normalize($record['name'])
                && (string) $person->rt === $record['rt']
                && (string) $person->rw === $record['rw'],
        ];

        foreach ($priorities as $matches) {
            $candidates = $people->filter($matches);
            if ($candidates->count() > 1) {
                throw new RuntimeException("Ambiguous beneficiary source {$reference}; reconciliation dibatalkan.");
            }
            if ($candidates->count() === 1) {
                return $candidates->first();
            }
        }

        return null;
    }

    private function legacySourceNo(?string $notes): ?int
    {
        return preg_match('/No ALL:\s*0*(\d+)/i', (string) $notes, $matches) === 1 ? (int) $matches[1] : null;
    }

    private function hasHistoricalReference(string $id): bool
    {
        foreach ([
            ['financial_v2_distribution_items', 'beneficiary_id'],
            ['financial_v2_transactions', 'counterparty_id'],
            ['financial_v2_transaction_splits', 'counterparty_id'],
            ['financial_v2_journal_lines', 'counterparty_id'],
            ['financial_v2_posting_rule_lines', 'fixed_counterparty_id'],
        ] as [$table, $column]) {
            if (DB::table($table)->where($column, $id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function validateSource(Collection $records): void
    {
        $groups = $records->countBy(fn (array $record): string => $record['rt'].'|'.$record['rw'])->all();
        $expected = ['01|06' => 9, '02|06' => 22, '03|04' => 12, '03|06' => 10, '04|06' => 9, '05|04' => 9, '06|07' => 7, 'TCE|08' => 29];
        if ($records->count() !== 107 || $groups !== $expected) {
            throw new RuntimeException('Dataset source Master Penerima harus tepat 107 dengan group count yang ditentukan.');
        }
        if ($records->pluck('legacy_source_no')->unique()->count() !== 107) {
            throw new RuntimeException('Legacy source lineage harus unik untuk seluruh 107 penerima.');
        }
    }

    private function validateResult(string $entityId, Collection $records): int
    {
        $active = Counterparty::forEntity($entityId)
            ->where('party_type', 'beneficiary')
            ->where('status', 'active')
            ->get();
        if ($active->count() !== 107) {
            throw new RuntimeException("Final active Master Penerima bukan 107 ({$active->count()}).");
        }

        foreach ($records as $record) {
            $matched = $active->where('external_reference', $this->sourceReference($record['legacy_source_no']));
            $person = $matched->sole();
            if ($person->display_name !== $record['name']
                || $person->rt !== $record['rt']
                || $person->rw !== $record['rw']
                || $person->rt_coordinator_name !== $record['coordinator']
                || $person->beneficiary_type !== 'BELUM_DITENTUKAN') {
                throw new RuntimeException("Hasil beneficiary {$record['name']} tidak sama dengan source.");
            }
        }

        return $active->count();
    }

    /** @return array<string, int> */
    private function financialFactCounts(): array
    {
        return collect([
            'transactions' => 'financial_v2_transactions',
            'journals' => 'financial_v2_journals',
            'journal_lines' => 'financial_v2_journal_lines',
            'ledger_entries' => 'financial_v2_ledger_entries',
            'vouchers' => 'financial_v2_vouchers',
            'allocations' => 'financial_v2_budget_allocations',
            'realizations' => 'financial_v2_fund_realizations',
            'distributions' => 'financial_v2_distributions',
            'distribution_items' => 'financial_v2_distribution_items',
            'opening_balance_batches' => 'financial_v2_opening_balance_batches',
            'opening_balance_lines' => 'financial_v2_opening_balance_lines',
        ])->mapWithKeys(fn (string $table, string $key): array => [$key => DB::table($table)->count()])->all();
    }

    /** @return array<string, string|null> */
    private function transactionCounterpartyLinks(): array
    {
        return DB::table('financial_v2_transactions')->orderBy('id')->pluck('counterparty_id', 'id')->all();
    }

    private function sourceReference(int $sourceNo): string
    {
        return self::SOURCE_PREFIX.sprintf('%03d', $sourceNo);
    }

    private function sourceCode(int $sourceNo): string
    {
        return 'BEN-MRJ-MUSTAHIK-'.sprintf('%03d', $sourceNo);
    }

    private function normalize(string $name): string
    {
        return (string) Str::of($name)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', ' ')->squish();
    }
}
