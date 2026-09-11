<?php

namespace App\Domain\FinancialV2;

/**
 * Human-reviewed transcription of "Data Mustahik ZISWAF MRJ".
 *
 * The PDF is a scan. Rows with an unclear handwritten correction remain in
 * the review list and must never be imported until a person verifies them.
 */
final class MustahikMrjSource
{
    public const NAME = 'Data Mustahik ZISWAF MRJ';

    /** @return array<int, array<string, mixed>> */
    public function records(): array
    {
        $names = [
            1 => 'Kokom', 'Sainah', 'Munah', 'Yuyun', 'Bano', 'Sumani', 'Karli', 'Rinah', 'Ranten', 'Risug awati',
            'Ai Herlina', 'Temi', 'Saira', 'Jamaludin', 'Amenih', 'Saali', 'Purwanti', 'Jariyah', 'Walkino', 'Halimah (Tambahan)',
            'Hafsah (Tambahan)', 'Mus Muslihat', 'Subur', 'Ajidin', 'Sauri', 'Maiyah', 'Sri Murni', 'Mulyati', 'Khotati', 'Kartinina', 'Unyanah',
            'Mulchlis', 'Hidayah', 'Atiqah', 'Hj. Mapih', 'Abdul Rohman', 'Nasik', 'Supanci', 'Sukarti', 'Komarih', 'Mariani', 'Minin', 'Munhari',
            'Irmayanti', 'Suherni', 'Hawiyah', 'Ida K Rihodah', 'Pak Agus', 'Suhendi', 'Lisabela', 'Joni', 'By Redi', 'Siti Rahayu (Aryo)',
            'Sri Sisvitri', 'Sarjono', 'Suhendi', 'Ahmad', 'Yuliandri', 'Neneng Asmanih', 'Fannya atau Tafiq', 'Masilah', 'Hayatih', 'Faridah',
            'Anih Nurhayati', 'Umu Kulsum', 'Sapuroh', 'Hj. Maswarlih', 'Satiyem', 'Sumiyah', 'Sumini', 'Mursidi', 'Nadiah', 'Maesaroh', 'Nenina',
            'Sirmuniati', 'Muhartini', 'Munayah', 'Tursinah', 'Marjuki', 'Asihery', 'Asli Ranti', 'Nawiah', 'Ncpiah', 'Asmani', 'Hamdan',
            'Pak Sarian', 'Romlah', 'Reza', 'Bi Hamdah', 'Mpo Atik', 'Mama Mbeng', 'Bu Ima', 'Ramsah', 'Luswita', 'Subur', 'Rohedah', 'Misni',
            'Abdul Rozak', 'Yuli', 'Hj. Rabiah Arab', 'Imsiran', 'Bapak Esih', 'Siti', 'Ahyani', 'Endang', 'Tasiyem', 'Munisah', 'Dimeroh', 'Hasan',
            'Roisah', 'Tuna Netra Deplu', 'Ibu Jamu', 'Yanto', 'Daus', 'Darsono', 'Irwan (TK Taman)', 'Yani (TK Taman)', 'Sofwan (TK Taman)',
            'Gofur (TK Taman)', 'Ulcay (TK Taman)', 'TK Taman (TK Taman)', 'Pian (TK Taman)', 'Ibu Moses', 'Bu Sarah', 'Sapardi', 'Saiful', 'Ori Adam',
            'Muhajir', 'Kurtubi', 'Suryadi', 'Hakim', 'Joko', 'Ajis', 'Nawiri', 'Sajiman', 'Suradi', 'Wahono', 'Pak Sur Marbot', 'Hartoyo',
        ];
        $notApproved = array_fill_keys([
            14, 17, 21, 22, 23, 25, 26,
            49, 50, 51, 52, 53, 54, 55, 56, 57, 58,
            80, 81, 82, 83, 84, 85,
            102, 103, 104, 105, 106, 107, 108, 109, 110,
        ], true);
        $manualReview = [
            5 => [
                'reason' => 'Nama cetak Bano dicoret; nama pengganti tulisan tangan tetap tidak terbaca pasti.',
                'resolved_name' => null,
            ],
            10 => [
                'reason' => 'Nama cetak Risug awati dicoret; nama pengganti tulisan tangan terbaca jelas sebagai Tosah.',
                'resolved_name' => 'Tosah',
            ],
            37 => [
                'reason' => 'Nasik diberi catatan meninggal; nama pengganti pada baris yang sama terbaca jelas sebagai Uni Yeni.',
                'resolved_name' => 'Uni Yeni',
            ],
            60 => [
                'reason' => 'Sumber secara eksplisit menulis dua kemungkinan nama: Fannya atau Tafiq.',
                'resolved_name' => null,
            ],
            61 => [
                'reason' => 'Pembacaan ulang scan beresolusi tinggi memastikan nama cetak adalah Masilah.',
                'resolved_name' => 'Masilah',
            ],
            74 => [
                'reason' => 'Nama cetak Nenina dicoret; nama pengganti tulisan tangan terbaca jelas sebagai Munah.',
                'resolved_name' => 'Munah',
            ],
            78 => [
                'reason' => 'Nama cetak Tursinah dicoret; nama pengganti tulisan tangan terbaca jelas sebagai Atanah.',
                'resolved_name' => 'Atanah',
            ],
            80 => [
                'reason' => 'Nama cetak pada baris Tanda Terima 0 tidak cukup terbaca.',
                'resolved_name' => null,
            ],
            83 => [
                'reason' => 'Nama cetak diberi koreksi tulisan tangan yang tidak terbaca pasti.',
                'resolved_name' => null,
            ],
            92 => [
                'reason' => 'Nama cetak Bu Ima dicoret; nama pengganti tulisan tangan terbaca jelas sebagai Isnawati Hasanah.',
                'resolved_name' => 'Isnawati Hasanah',
            ],
            114 => [
                'reason' => 'Nama cetak Daus dicoret; nama pengganti hanya terbaca sebagian dan belum cukup pasti.',
                'resolved_name' => null,
            ],
            121 => [
                'reason' => 'Nama generik TK Taman dicoret; nama pengganti terbaca jelas sebagai Ucup, dengan konteks TK Taman tetap dipertahankan.',
                'resolved_name' => 'Ucup (TK Taman)',
            ],
        ];

        $records = [];
        foreach ($names as $sourceNo => $name) {
            [$rt, $rw, $coordinator, $area] = $this->region($sourceNo);
            $manual = $manualReview[$sourceNo] ?? null;
            $records[] = [
                'source_no' => $sourceNo,
                'source_name' => $name,
                'name' => $manual['resolved_name'] ?? $name,
                'tanda_terima' => isset($notApproved[$sourceNo]) ? 0 : 1,
                'rt' => $rt,
                'rw' => $rw,
                'rt_coordinator_name' => $coordinator,
                'area' => $area,
                'beneficiary_type' => 'BELUM_DITENTUKAN',
                'was_manually_reviewed' => $manual !== null,
                'needs_manual_verification' => $manual !== null && $manual['resolved_name'] === null,
                'review_reason' => $manual['reason'] ?? null,
            ];
        }

        return $records;
    }

    /** @return array{?string, ?string, ?string, ?string} */
    private function region(int $sourceNo): array
    {
        return match (true) {
            $sourceNo <= 10 => ['03', '06', 'Pak Wakidjo', null],
            $sourceNo <= 26 => ['04', '06', 'Bu Anas', null],
            $sourceNo <= 58 => ['02', '06', 'Pak Maman', null],
            $sourceNo <= 67 => ['01', '06', 'Pak Arif', null],
            $sourceNo <= 85 => ['03', '04', 'Pak Wawang', null],
            $sourceNo <= 92 => ['06', '07', 'Pak Parno', null],
            $sourceNo <= 110 => ['05', '04', 'Bu Susi', null],
            default => [null, null, null, 'TCE'],
        };
    }
}
