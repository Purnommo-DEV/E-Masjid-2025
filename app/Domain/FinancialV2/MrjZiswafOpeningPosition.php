<?php

namespace App\Domain\FinancialV2;

use LogicException;

/**
 * Immutable, cell-level manifest for the owner-approved MRJ ZISWAF position
 * supplied in ZISWAF UPDATE 3.xlsx. It represents an opening position only;
 * it is not a historical transaction-import instruction.
 */
final class MrjZiswafOpeningPosition
{
    public const AS_OF_DATE = '2026-06-27';

    public const SOURCE_FILENAME = 'ZISWAF UPDATE 3.xlsx';

    public const TOTAL = '125730312.00';

    public const BNI_TOTAL = '123077312.00';

    public const CASH_TOTAL = '2653000.00';

    /** @return array<int, array{code:string,name:string,bni:string,cash:string,total:string,source_range:string}> */
    public static function funds(): array
    {
        return [
            ['code' => 'ZAKAT-MAAL', 'name' => 'Dana Zakat Maal', 'bni' => '75745386.00', 'cash' => '0.00', 'total' => '75745386.00', 'source_range' => 'Sisa Alokasi Dana!A5:D5'],
            ['code' => 'INFAQ-TROMOL', 'name' => 'Dana Infaq & Tromol', 'bni' => '16666949.00', 'cash' => '2653000.00', 'total' => '19319949.00', 'source_range' => 'Sisa Alokasi Dana!A6:D7'],
            ['code' => 'SODAQOH', 'name' => 'Dana Sodaqoh', 'bni' => '6906000.00', 'cash' => '0.00', 'total' => '6906000.00', 'source_range' => 'Sisa Alokasi Dana!A8:D8'],
            ['code' => 'SANTUNAN-YATIM', 'name' => 'Dana Santunan Anak Yatim', 'bni' => '6600000.00', 'cash' => '0.00', 'total' => '6600000.00', 'source_range' => 'Sisa Alokasi Dana!A9:D9'],
            ['code' => 'FIDYAH', 'name' => 'Dana Fidyah', 'bni' => '7500000.00', 'cash' => '0.00', 'total' => '7500000.00', 'source_range' => 'Sisa Alokasi Dana!A10:D10'],
            ['code' => 'DHUAFA', 'name' => 'Dana Dhuafa', 'bni' => '9658977.00', 'cash' => '0.00', 'total' => '9658977.00', 'source_range' => 'Sisa Alokasi Dana!A11:D11'],
        ];
    }

    /** @return array<int, array{key:string,source_reference:string,fund_code:string,financial_account_code:string,amount:string,description:string}> */
    public static function liquidityLines(): array
    {
        $lines = [];

        foreach (self::funds() as $fund) {
            foreach (['BNI-ZISWAF' => $fund['bni'], 'CASH-ZISWAF' => $fund['cash']] as $financialAccountCode => $amount) {
                if (DecimalAmount::equals($amount, '0.00')) {
                    continue;
                }

                $location = $financialAccountCode === 'BNI-ZISWAF' ? 'BNI ZISWAF' : 'Cash Tromol Yatim';
                $lines[] = [
                    'key' => $fund['code'].'-'.$financialAccountCode,
                    'source_reference' => self::SOURCE_FILENAME.'|'.$fund['source_range'].'|'.$location,
                    'fund_code' => $fund['code'],
                    'financial_account_code' => $financialAccountCode,
                    'amount' => $amount,
                    'description' => "Saldo awal {$fund['name']} pada {$location}; sumber {$fund['source_range']}",
                ];
            }
        }

        return $lines;
    }

    /** @return array<int, array{key:string,source_reference:string,fund_code:string,amount:string,description:string}> */
    public static function fundNetAssetLines(): array
    {
        return array_map(fn (array $fund): array => [
            'key' => $fund['code'].'-NET-ASSET',
            'source_reference' => self::SOURCE_FILENAME.'|'.$fund['source_range'].'|FUND-NET-ASSET',
            'fund_code' => $fund['code'],
            'amount' => $fund['total'],
            'description' => "Saldo dana awal {$fund['name']}; sumber {$fund['source_range']}",
        ], self::funds());
    }

    /** @return array{opening_cash:string,internal_transfer:string,ending_cash:string,bni_closing:string,source_reference:string} */
    public static function cashResolution(): array
    {
        return [
            'opening_cash' => '3853000.00',
            'internal_transfer' => '1200000.00',
            'ending_cash' => self::CASH_TOTAL,
            'bni_closing' => self::BNI_TOTAL,
            'source_reference' => self::SOURCE_FILENAME.'|Rekonsil 27 Juni 2026!B46:C46; Ringkasan Laporan!B12:C14',
        ];
    }

    /**
     * Result of the complete allocation-semantic review of the final workbook.
     *
     * "Sisa Alokasi Dana" is the source's name for a closing fund-position
     * report.  It does not evidence a budget/allocation decision, an approved
     * peruntukan, or a realization.  The details below must consequently stay
     * source-only explanation of the single opening position, rather than be
     * manufactured into operational allocation records.
     *
     * @return array{source_filename:string,source_sha256:string,worksheets:array<int,string>,allocation_event_count:int,decision:string,reason:string}
     */
    public static function allocationSourceAudit(): array
    {
        return [
            'source_filename' => self::SOURCE_FILENAME,
            'source_sha256' => '404FC8CD54ECD3E35E17C30FFE6A3D88DF6656260CBEAC8F614EF99689A02F9C',
            'worksheets' => [
                'Rekap Harian Ziswaf 1447 H (Mar',
                'Rincian Rekap 18 Maret',
                'Rincian Rekap 19 Maret',
                'Rekonsil 27 Juni 2026',
                'Ringkasan Laporan',
                'Buku Kas Detail',
                'Sisa Alokasi Dana',
            ],
            'allocation_event_count' => 0,
            'decision' => 'no_allocation_import',
            'reason' => 'Tidak ada event alokasi, peruntukan, anggaran, persetujuan, atau realisasi yang didukung sumber. Sheet "Sisa Alokasi Dana" adalah laporan saldo akhir Dana.',
        ];
    }

    /**
     * Source-only Fund activity that explains the approved opening position.
     *
     * These are not V2 transactions and must never be replayed into the
     * Journal/Ledger. The final workbook identifies both specific dates and
     * source periods (for example "Sesi Ramadhan"); the latter deliberately
     * stays a source label because an exact calendar date is not available.
     *
     * @return array<int, array{kind:string,date_label:string,description:string,notes:string,amount:string,source_reference:string}>
     */
    public static function fundSourceHistory(string $fundCode): array
    {
        $history = [
            'ZAKAT-MAAL' => [
                ['kind' => 'receipt', 'date_label' => 'Maret 2026', 'description' => 'Penerimaan Ramadhan 1447 H - Zakat Maal', 'notes' => '', 'amount' => '97145386.00', 'source_reference' => 'Buku Kas Detail!A7:F7'],
                ['kind' => 'usage', 'date_label' => 'April 2026', 'description' => 'Penyaluran Zakat Maal April', 'notes' => '', 'amount' => '10700000.00', 'source_reference' => 'Buku Kas Detail!A12:F12'],
                ['kind' => 'usage', 'date_label' => 'Mei 2026', 'description' => 'Penyaluran Zakat Maal Mei', 'notes' => '', 'amount' => '10700000.00', 'source_reference' => 'Buku Kas Detail!A13:F13'],
                ['kind' => 'closing', 'date_label' => '27 Juni 2026', 'description' => 'Saldo sumber Zakat Maal', 'notes' => 'Laporan posisi Fund; bukan Allocation.', 'amount' => '75745386.00', 'source_reference' => 'Sisa Alokasi Dana!D24:E24'],
            ],
            'INFAQ-TROMOL' => [
                ['kind' => 'receipt', 'date_label' => '10 Desember 2025', 'description' => 'Cash Tromol 10 Desember 2025', 'notes' => 'Sudah masuk ke rekening BNI.', 'amount' => '7030000.00', 'source_reference' => 'Buku Kas Detail!A3:F3'],
                ['kind' => 'receipt', 'date_label' => '03 Januari 2026', 'description' => 'Cash Tromol 3 Januari 2026', 'notes' => 'Sudah masuk ke rekening BNI.', 'amount' => '2592000.00', 'source_reference' => 'Buku Kas Detail!A4:F4'],
                ['kind' => 'receipt', 'date_label' => '22 Februari 2026', 'description' => 'Cash Tromol 22 Februari 2026', 'notes' => 'Sudah masuk ke rekening BNI.', 'amount' => '5098000.00', 'source_reference' => 'Buku Kas Detail!A5:F5'],
                ['kind' => 'receipt', 'date_label' => '25 Maret 2026', 'description' => 'Cash Tromol 25 Maret 2026', 'notes' => 'Sudah masuk ke rekening BNI.', 'amount' => '3446000.00', 'source_reference' => 'Buku Kas Detail!A6:F6'],
                ['kind' => 'receipt', 'date_label' => 'Maret 2026', 'description' => 'Penerimaan Ramadhan 1447 H - Infaq', 'notes' => '', 'amount' => '2779000.00', 'source_reference' => 'Buku Kas Detail!A8:F8'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Cetak Amplop berlogo MRJ', 'notes' => '', 'amount' => '750000.00', 'source_reference' => 'Buku Kas Detail!A14:F14'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Cetak Kupon Kurban', 'notes' => '', 'amount' => '250000.00', 'source_reference' => 'Buku Kas Detail!A17:F17'],
                ['kind' => 'usage', 'date_label' => '19 April 2026', 'description' => 'Konsumsi Donor 19 April', 'notes' => '', 'amount' => '939000.00', 'source_reference' => 'Buku Kas Detail!A18:F18'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Pak Jamal', 'notes' => '', 'amount' => '150000.00', 'source_reference' => 'Buku Kas Detail!A20:F20'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Petugas Counter 5 hari', 'notes' => '', 'amount' => '1000000.00', 'source_reference' => 'Buku Kas Detail!A21:F21'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Revo Print', 'notes' => '', 'amount' => '300000.00', 'source_reference' => 'Buku Kas Detail!A22:F22'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Kupon Print', 'notes' => '', 'amount' => '280000.00', 'source_reference' => 'Buku Kas Detail!A23:F23'],
                ['kind' => 'usage', 'date_label' => '27 Juni 2026', 'description' => 'Rekonsiliasi berupa admin Bank', 'notes' => 'Biaya administrasi dan transfer.', 'amount' => '609051.00', 'source_reference' => 'Rekonsil 27 Juni 2026!A43:B43'],
                ['kind' => 'closing', 'date_label' => '27 Juni 2026', 'description' => 'Saldo sumber Infaq & Tromol pada BNI', 'notes' => 'Laporan posisi rekening BNI; bukan Allocation.', 'amount' => '16666949.00', 'source_reference' => 'Sisa Alokasi Dana!D41:E41'],
                ['kind' => 'account_position', 'date_label' => '14 Juni 2026', 'description' => 'Cash Tromol Yatim', 'notes' => 'Komposisi rekening/kas, bukan penerimaan atau pengeluaran Dana.', 'amount' => '2653000.00', 'source_reference' => 'Sisa Alokasi Dana!D66:E66'],
            ],
            'SODAQOH' => [
                ['kind' => 'receipt', 'date_label' => 'Maret 2026', 'description' => 'Penerimaan Ramadhan 1447 H - Sodaqoh', 'notes' => '', 'amount' => '8506000.00', 'source_reference' => 'Buku Kas Detail!A9:F9'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Beras 20 Pack', 'notes' => '', 'amount' => '1600000.00', 'source_reference' => 'Buku Kas Detail!A24:F24'],
                ['kind' => 'closing', 'date_label' => '27 Juni 2026', 'description' => 'Saldo sumber Sodaqoh', 'notes' => 'Laporan posisi Fund; bukan Allocation.', 'amount' => '6906000.00', 'source_reference' => 'Sisa Alokasi Dana!D46:E46'],
            ],
            'SANTUNAN-YATIM' => [
                ['kind' => 'receipt', 'date_label' => 'Maret 2026', 'description' => 'Dana Santunan Anak Yatim', 'notes' => '', 'amount' => '6600000.00', 'source_reference' => 'Buku Kas Detail!A10:F10'],
                ['kind' => 'closing', 'date_label' => '27 Juni 2026', 'description' => 'Saldo sumber Dana Santunan Anak Yatim', 'notes' => 'Laporan posisi Fund; bukan Allocation.', 'amount' => '6600000.00', 'source_reference' => 'Sisa Alokasi Dana!D50:E50'],
            ],
            'FIDYAH' => [
                ['kind' => 'receipt', 'date_label' => 'Maret 2026', 'description' => 'Penerimaan Ramadhan 1447 H - Fidyah', 'notes' => '', 'amount' => '7500000.00', 'source_reference' => 'Buku Kas Detail!A11:F11'],
                ['kind' => 'closing', 'date_label' => '27 Juni 2026', 'description' => 'Saldo sumber Fidyah', 'notes' => 'Laporan posisi Fund; bukan Allocation.', 'amount' => '7500000.00', 'source_reference' => 'Sisa Alokasi Dana!D54:E54'],
            ],
            'DHUAFA' => [
                ['kind' => 'opening', 'date_label' => '18 Februari 2026', 'description' => 'Saldo Awal Buku', 'notes' => 'Untuk Dhuafa.', 'amount' => '20378977.00', 'source_reference' => 'Buku Kas Detail!A2:F2'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Beasiswa Fauzan SMP AL Madina', 'notes' => '', 'amount' => '5530000.00', 'source_reference' => 'Buku Kas Detail!A15:F15'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'SPP Mei-Juli 2026', 'notes' => '', 'amount' => '1095000.00', 'source_reference' => 'Buku Kas Detail!A16:F16'],
                ['kind' => 'usage', 'date_label' => 'Sesi Ramadhan', 'description' => 'Beasiswa SMP AL Madina', 'notes' => '', 'amount' => '4095000.00', 'source_reference' => 'Buku Kas Detail!A19:F19'],
                ['kind' => 'closing', 'date_label' => '27 Juni 2026', 'description' => 'Saldo sumber Dhuafa', 'notes' => 'Laporan posisi Fund; bukan Allocation.', 'amount' => '9658977.00', 'source_reference' => 'Sisa Alokasi Dana!D61:E61'],
            ],
        ];

        return $history[$fundCode] ?? [];
    }

    public static function assertIntegrity(): void
    {
        $fundTotal = DecimalAmount::sum(array_column(self::funds(), 'total'));
        $bniTotal = DecimalAmount::sum(array_column(self::funds(), 'bni'));
        $cashTotal = DecimalAmount::sum(array_column(self::funds(), 'cash'));
        $cashResolution = self::cashResolution();

        if (! DecimalAmount::equals($fundTotal, self::TOTAL)
            || ! DecimalAmount::equals($bniTotal, self::BNI_TOTAL)
            || ! DecimalAmount::equals($cashTotal, self::CASH_TOTAL)
            || ! DecimalAmount::equals(DecimalAmount::add($bniTotal, $cashTotal), self::TOTAL)
            || ! DecimalAmount::equals(DecimalAmount::subtract($cashResolution['opening_cash'], $cashResolution['internal_transfer']), $cashResolution['ending_cash'])) {
            throw new LogicException('MRJ ZISWAF opening-position manifest does not reconcile to its source totals.');
        }
    }
}
