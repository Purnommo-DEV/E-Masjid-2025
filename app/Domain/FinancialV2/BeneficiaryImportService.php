<?php

namespace App\Domain\FinancialV2;

use App\Models\FinancialV2\AccountingEntity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\IOFactory;

/** Imports beneficiary masters only; this service has no financial-fact dependencies. */
final class BeneficiaryImportService
{
    public const HEADERS = [
        'Nama lengkap', 'Telepon', 'RT', 'RW', 'Koordinator RT',
        'Jenis penerima', 'Status', 'Alamat', 'Catatan internal',
    ];

    public function __construct(
        private readonly BeneficiaryDuplicateService $duplicates,
        private readonly DistributionService $beneficiaries,
    ) {}

    /** @return array{rows:array<int,array<string,mixed>>,summary:array<string,int>} */
    public function preview(string $entityId, string $path): array
    {
        $sheet = IOFactory::load($path)->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();
        $headers = [];
        foreach (range(1, count(self::HEADERS)) as $column) {
            $headers[] = trim((string) $sheet->getCell([$column, 1])->getFormattedValue());
        }
        if ($headers !== self::HEADERS) {
            throw ValidationException::withMessages(['import_file' => 'Header file tidak sesuai template resmi Penerima Financial V2.']);
        }

        $rawRows = [];
        $examplesIgnored = 0;
        for ($rowNumber = 2; $rowNumber <= $highestRow; $rowNumber++) {
            $values = [];
            foreach (range(1, count(self::HEADERS)) as $column) {
                $values[] = trim((string) $sheet->getCell([$column, $rowNumber])->getFormattedValue());
            }
            if (collect($values)->every(fn (string $value): bool => $value === '')) {
                continue;
            }
            // Official template examples are explicitly marked and can never become data.
            if (str_starts_with(mb_strtoupper($values[0]), '[CONTOH]')) {
                $examplesIgnored++;
                continue;
            }
            $rawRows[] = ['source_row' => $rowNumber, 'values' => $values];
        }

        $nameCounts = collect($rawRows)->countBy(fn (array $row): string => $this->duplicates->normalize($row['values'][0]));
        $rows = [];
        $summary = ['valid_new' => 0, 'duplicate_existing' => 0, 'duplicate_in_file' => 0, 'invalid' => 0, 'examples_ignored' => $examplesIgnored];
        foreach ($rawRows as $raw) {
            $data = $this->map($raw['values']);
            $validator = Validator::make($data, $this->rules());
            $normalized = $this->duplicates->normalize($data['display_name']);
            $status = 'valid_new';
            $messages = [];
            $existing = collect();
            if ($validator->fails()) {
                $status = 'invalid';
                $messages = $validator->errors()->all();
            } elseif (($nameCounts[$normalized] ?? 0) > 1) {
                $status = 'duplicate_in_file';
                $messages[] = 'Nama muncul lebih dari sekali di file.';
            } else {
                $existing = $this->duplicates->exact($entityId, $data['display_name']);
                if ($existing->isNotEmpty()) {
                    $status = 'duplicate_existing';
                    $messages[] = 'Nama sudah tersedia pada master Penerima entity ini; data existing tidak ditimpa.';
                }
            }
            $summary[$status]++;
            $rows[] = compact('data', 'status', 'messages') + [
                'source_row' => $raw['source_row'],
                'existing_ids' => $existing->pluck('id')->all(),
            ];
        }

        return compact('rows', 'summary');
    }

    /** @return array{created:int,skipped:int} */
    public function import(string $entityId, string $path, ?int $actorUserId): array
    {
        return DB::transaction(function () use ($entityId, $path, $actorUserId): array {
            AccountingEntity::query()->whereKey($entityId)->where('status', 'active')->lockForUpdate()->firstOrFail();
            $preview = $this->preview($entityId, $path);
            $result = ['created' => 0, 'skipped' => 0];
            foreach ($preview['rows'] as $row) {
                if ($row['status'] !== 'valid_new' || $this->duplicates->exact($entityId, $row['data']['display_name'])->isNotEmpty()) {
                    $result['skipped']++;
                    continue;
                }
                $this->beneficiaries->saveBeneficiary($entityId, $row['data'], null, $actorUserId);
                $result['created']++;
            }

            return $result;
        }, 3);
    }

    /** @return array<string, mixed> */
    private function map(array $values): array
    {
        $type = mb_strtoupper(str_replace([' ', '-'], '_', $values[5]));
        $type = match ($type) {
            'YATIM' => 'YATIM',
            'DHUAFA' => 'DHUAFA',
            'YATIM_DHUAFA', 'YATIM_YANG_DHUAFA' => 'YATIM_DHUAFA',
            '', 'BELUM_DITENTUKAN' => 'BELUM_DITENTUKAN',
            default => $type,
        };
        $status = match (mb_strtolower($values[6])) {
            '', 'aktif', 'active' => 'active',
            'tidak aktif', 'inactive' => 'inactive',
            'arsip', 'archived' => 'archived',
            default => mb_strtolower($values[6]),
        };

        return [
            'display_name' => $values[0],
            'contact_reference' => $values[1] ?: null,
            'rt' => $values[2] ?: null,
            'rw' => $values[3] ?: null,
            'rt_coordinator_name' => $values[4] ?: null,
            'beneficiary_type' => $type,
            'status' => $status,
            'address' => $values[7] ?: null,
            'beneficiary_notes' => $values[8] ?: null,
        ];
    }

    /** @return array<string, mixed> */
    private function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:240'],
            'contact_reference' => ['nullable', 'string', 'max:500'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'rt_coordinator_name' => ['nullable', 'string', 'max:160'],
            'beneficiary_type' => ['required', Rule::in(['YATIM', 'DHUAFA', 'YATIM_DHUAFA', 'BELUM_DITENTUKAN'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'archived'])],
            'address' => ['nullable', 'string', 'max:2000'],
            'beneficiary_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
