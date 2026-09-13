<?php

namespace App\Services;

use App\Models\SantunanParticipation;
use App\Models\SantunanPerson;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SantunanLegacyBackfillService
{
    /** @return array<string, mixed> */
    public function preflight(): array
    {
        $legacy = DB::table('pendaftaran_anak_yatim_dhuafa')->orderBy('id')->get();
        $invalidCategories = $legacy
            ->whereNotIn('kategori', SantunanParticipation::CATEGORIES)
            ->map(fn ($row) => ['legacy_id' => $row->id, 'kategori' => $row->kategori])
            ->values();
        $ambiguous = $this->exactIdentityDuplicates($legacy);

        return [
            'legacy_total' => $legacy->count(),
            'legacy_2026' => $legacy->where('tahun_program', 2026)->count(),
            'years' => $legacy->countBy('tahun_program')->sortKeys()->all(),
            'categories' => $legacy->countBy('kategori')->sortKeys()->all(),
            'sources' => $legacy->countBy(fn ($row) => filled($row->sumber_informasi) ? $row->sumber_informasi : 'Belum diisi')->sortKeys()->all(),
            'invalid_categories' => $invalidCategories->all(),
            'ambiguous_identity_groups' => $ambiguous,
        ];
    }

    /** @return array<string, mixed> */
    public function run(): array
    {
        $preflight = $this->preflight();

        if ($preflight['invalid_categories'] !== []) {
            throw new RuntimeException('Backfill dihentikan: ditemukan kategori di luar dhuafa dan yatim_dhuafa.');
        }

        if ($preflight['ambiguous_identity_groups'] !== []) {
            throw new RuntimeException('Backfill dihentikan: ditemukan identity legacy yang ambigu dan memerlukan review.');
        }

        $created = DB::transaction(function (): array {
            $personsCreated = 0;
            $participationsCreated = 0;
            $alreadyMapped = 0;

            DB::table('pendaftaran_anak_yatim_dhuafa')
                ->orderBy('id')
                ->chunkById(100, function (Collection $rows) use (&$personsCreated, &$participationsCreated, &$alreadyMapped): void {
                    foreach ($rows as $legacy) {
                        if (SantunanParticipation::query()->where('legacy_registration_id', $legacy->id)->exists()) {
                            $alreadyMapped++;

                            continue;
                        }

                        $person = SantunanPerson::query()->create([
                            'nama_lengkap' => $legacy->nama_lengkap,
                            'nama_panggilan' => $legacy->nama_panggilan,
                            'tanggal_lahir' => $legacy->tanggal_lahir,
                            'jenis_kelamin' => $legacy->jenis_kelamin,
                        ]);
                        $personsCreated++;

                        SantunanParticipation::query()->create([
                            'person_id' => $person->id,
                            'tahun_program' => $legacy->tahun_program,
                            'kategori' => $legacy->kategori,
                            'sumber_informasi' => $legacy->sumber_informasi,
                            'umur' => $legacy->umur,
                            'umur_satuan' => $legacy->umur_satuan,
                            'alamat' => $legacy->alamat,
                            'rt' => $legacy->rt,
                            'rw' => $legacy->rw,
                            'nama_rt' => $legacy->nama_rt,
                            'nama_orang_tua' => $legacy->nama_orang_tua,
                            'pekerjaan_orang_tua' => $legacy->pekerjaan_orang_tua,
                            'no_wa' => $legacy->no_wa,
                            'status' => $legacy->status,
                            'catatan_tambahan' => $legacy->catatan_tambahan,
                            'catatan_admin' => $legacy->catatan_admin,
                            'ip_address' => $legacy->ip_address,
                            'legacy_registration_id' => $legacy->id,
                            'created_at' => $legacy->created_at,
                            'updated_at' => $legacy->updated_at,
                        ]);
                        $participationsCreated++;
                    }
                }, 'id');

            return compact('personsCreated', 'participationsCreated', 'alreadyMapped');
        });

        $legacyTotal = DB::table('pendaftaran_anak_yatim_dhuafa')->count();
        $mappedTotal = SantunanParticipation::query()->whereNotNull('legacy_registration_id')->count();
        $orphans = SantunanParticipation::query()
            ->whereNotNull('legacy_registration_id')
            ->whereDoesntHave('legacyRegistration')
            ->count();

        if ($legacyTotal !== $mappedTotal || $orphans !== 0) {
            throw new RuntimeException("Verifikasi backfill gagal: legacy={$legacyTotal}, mapped={$mappedTotal}, orphan={$orphans}.");
        }

        return $preflight + [
            'persons_created' => $created['personsCreated'],
            'participations_created' => $created['participationsCreated'],
            'already_mapped' => $created['alreadyMapped'],
            'persons_total' => SantunanPerson::query()->count(),
            'participations_total' => SantunanParticipation::query()->count(),
            'mapped_legacy_total' => $mappedTotal,
            'orphans' => $orphans,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function exactIdentityDuplicates(Collection $legacy): array
    {
        return $legacy
            ->groupBy(fn ($row) => implode('|', [
                $row->tahun_program,
                SantunanPerson::normalizeName($row->nama_lengkap),
                $row->tanggal_lahir ?: '__unknown_birth__',
                $row->jenis_kelamin,
                Str::lower(Str::squish((string) $row->nama_orang_tua)),
            ]))
            ->filter(fn (Collection $rows) => $rows->count() > 1)
            ->map(fn (Collection $rows) => [
                'legacy_ids' => $rows->pluck('id')->all(),
                'nama' => $rows->first()->nama_lengkap,
                'tanggal_lahir' => $rows->first()->tanggal_lahir,
                'jenis_kelamin' => $rows->first()->jenis_kelamin,
                'nama_orang_tua' => $rows->first()->nama_orang_tua,
                'no_wa' => $rows->pluck('no_wa')->filter()->unique()->values()->all(),
                'alamat' => $rows->pluck('alamat')->filter()->unique()->values()->all(),
                'evidence' => ['nama, tanggal lahir, jenis kelamin, dan orang tua sama persis'],
                'reason' => 'Dua legacy record pada tahun yang sama tidak boleh digabung otomatis.',
            ])
            ->values()
            ->all();
    }
}
