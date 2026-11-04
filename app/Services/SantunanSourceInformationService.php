<?php

namespace App\Services;

use App\Models\SantunanParticipation;
use App\Models\SantunanSourceInformationRenameHistory;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SantunanSourceInformationService
{
    public function grouped(?int $year, bool $allYears): Collection
    {
        return SantunanParticipation::query()
            ->select(['tahun_program', 'sumber_informasi'])
            ->when(! $allYears, fn ($query) => $query->where('tahun_program', $year))
            ->whereNotNull('sumber_informasi')
            ->where('sumber_informasi', '!=', '')
            ->get()
            ->filter(fn ($row) => $this->normalize($row->sumber_informasi) !== '')
            ->groupBy(fn ($row) => $this->normalize($row->sumber_informasi))
            ->map(function (Collection $rows, string $name): array {
                return [
                    'nama' => $name,
                    'jumlah' => $rows->count(),
                    'tahun' => $rows->countBy(fn ($row) => (string) $row->tahun_program)->sortKeys()->all(),
                ];
            })
            ->sortBy('nama', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function rename(
        string $oldName,
        string $newName,
        ?int $year,
        bool $allYears,
        $user = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): int {
        $oldName = $this->normalize($oldName);
        $newName = $this->normalize($newName);

        if (mb_strlen($newName) < 2 || mb_strlen($newName) > 255) {
            throw ValidationException::withMessages(['nama_baru' => 'Nama baru harus terdiri dari 2 sampai 255 karakter.']);
        }

        if ($oldName === $newName) {
            throw ValidationException::withMessages(['nama_baru' => 'Nama baru harus berbeda dari nama lama.']);
        }

        return DB::transaction(function () use ($oldName, $newName, $year, $allYears, $user, $ipAddress, $userAgent): int {
            $ids = SantunanParticipation::query()
                ->select(['id', 'sumber_informasi'])
                ->when(! $allYears, fn ($query) => $query->where('tahun_program', $year))
                ->whereNotNull('sumber_informasi')
                ->lockForUpdate()
                ->get()
                ->filter(fn ($row) => $this->normalize($row->sumber_informasi) === $oldName)
                ->pluck('id');

            if ($ids->isEmpty()) {
                throw ValidationException::withMessages(['nama_lama' => 'Sumber informasi tidak ditemukan pada cakupan yang dipilih.']);
            }

            $affected = SantunanParticipation::query()->whereIn('id', $ids)->update([
                'sumber_informasi' => $newName,
                'updated_at' => now(),
            ]);

            SantunanSourceInformationRenameHistory::query()->create([
                'nama_lama' => $oldName,
                'nama_baru' => $newName,
                'tahun_program' => $allYears ? null : $year,
                'semua_tahun' => $allYears,
                'jumlah_terdampak' => $affected,
                'changed_by' => $user?->getAuthIdentifier(),
                'changed_by_label' => $user?->name ?? $user?->email,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent ? mb_substr($userAgent, 0, 500) : null,
                'changed_at' => now(),
            ]);

            return $affected;
        });
    }

    public function normalize(?string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', (string) $value));
    }
}
