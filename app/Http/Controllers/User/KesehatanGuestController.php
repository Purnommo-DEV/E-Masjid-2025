<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Models\KesehatanRegistration;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class KesehatanGuestController extends Controller
{
    public function index()
    {
        $eventDate = now()->format('Y-m-d');

        $allRegistrations = KesehatanRegistration::get();

        $totalPendaftar = $allRegistrations->count();

        $donorDarah = KesehatanRegistration::where('donor_darah', true)->latest()->get();

        $cekKesehatan = KesehatanRegistration::whereNotNull('cek_kesehatan')
            ->whereJsonLength('cek_kesehatan', '>', 0)
            ->latest()->get();

        $cekKatarak = KesehatanRegistration::where('cek_mata_katarak', true)->latest()->get();

        $seoData = new SEOData(
            title: 'Daftar Pendaftar Program Kesehatan',
            description: 'Daftar peserta Program Kesehatan Masjid Raudhotul Jannah TCE',
        );

        return view('masjid.' . masjid() . '.guest.program-kesehatan.index', compact(
            'allRegistrations',
            'totalPendaftar',
            'donorDarah',
            'cekKesehatan',
            'cekKatarak',
            'eventDate',
            'seoData'
        ));
    }

    public function create()
    {
        $eventDate = now()->format('Y-m-d');

        $jumlahGulaDarah = KesehatanRegistration::whereJsonContains('cek_kesehatan', 'gula_darah')->count();

        $kuotaGulaDarah = 100;

        return view('masjid.' . masjid() . '.guest.program-kesehatan.daftar', compact(
            'eventDate',
            'jumlahGulaDarah',
            'kuotaGulaDarah'
        ));
    }

    // ===================== FEEDBACK =====================
    public function feedback()
    {
        return view('masjid.' . masjid() . '.guest.program-kesehatan.feedback');
    }

    public function storeFeedback(Request $request)
    {
        $request->validate([
            'nama' => 'nullable|string|max:255',
            'saran' => 'required|string|min:10|max:1000',
        ]);

        // Simpan ke database (buat model Feedback jika belum ada)
        Feedback::create([
            'nama' => $request->nama,
            'saran' => $request->saran,
            'program' => 'kesehatan',
            'created_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas saran Anda.',
        ]);
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'nama_lengkap' => 'required|string|max:255',
    //         'no_hp'        => 'required|string|max:20',
    //         'alamat'       => 'nullable|string|max:500',
    //     ]);

    //     KesehatanRegistration::create([
    //         'nama_lengkap'     => $request->nama_lengkap,
    //         'no_hp'            => $request->no_hp,
    //         'alamat'           => $request->alamat,
    //         'event_date'       => $request->event_date ?? now()->format('Y-m-d'),
    //         'donor_darah'      => $request->boolean('donor_darah'),
    //         'cek_kesehatan'    => $request->cek_kesehatan ?? [],
    //         'cek_mata_katarak' => $request->boolean('cek_mata_katarak'),
    //     ]);

    //     // Kirim nama melalui query string
    //     return response()->json([
    //         'success'      => true,
    //         'nama_lengkap' => $request->nama_lengkap,
    //         'redirect'     => route('kesehatan.success', ['name' => $request->nama_lengkap])
    //     ]);
    // }

    public function storeNew(Request $request)
    {
        $request->validate([
            'nama_lengkap' => 'required|string|max:255',
            'no_hp' => 'required|string|max:20',
            'alamat' => 'nullable|string|max:500',
        ]);

        KesehatanRegistration::create([
            'nama_lengkap' => $request->nama_lengkap,
            'no_hp' => $request->no_hp,
            'alamat' => $request->alamat,
            'event_date' => $request->event_date ?? now()->format('Y-m-d'),
            'donor_darah' => $request->boolean('donor_darah'),
            'cek_kesehatan' => $request->cek_kesehatan ?? [],
            'cek_mata_katarak' => $request->boolean('cek_mata_katarak'),
        ]);

        // Kirim nama melalui query string
        return response()->json([
            'success' => true,
            'nama_lengkap' => $request->nama_lengkap,
            'redirect' => route('donor-darah.success', ['name' => $request->nama_lengkap]),
        ]);
    }

    // ===================== EXPORT =====================
    // Ambil data donor darah
    public function exportDonorDarah(Request $request)
    {
        // Ambil data donor darah dan urutkan berdasarkan nama A-Z
        $data = KesehatanRegistration::where('donor_darah', true)
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Donor Darah');

        // ====================== PAGE SETUP + MARGIN ======================
        $sheet->getPageSetup()
            ->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT
            )
            ->setPaperSize(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
            )
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true);

        $sheet->getPageMargins()
            ->setTop(0.8)
            ->setRight(0.8)
            ->setLeft(0.8)
            ->setBottom(0.8);

        // ====================== JUDUL ======================
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'PENDAFTARAN DONOR DARAH');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Masjid Raudhotul Jannah TCE');

        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue(
            'A3',
            'Tanggal: ' . now()->translatedFormat('d F Y')
        );

        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // Baris kosong sebagai jarak antara judul dan tabel
        $sheet->getRowDimension(4)->setRowHeight(10);

        // ====================== HEADER TABEL ======================
        $header = [
            'NO',
            'NAMA',
            'NO HP',
        ];

        $col = 'A';

        foreach ($header as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }

        // Styling header
        $sheet->getStyle('A5:C5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],
            'fill' => [
                'fillType' =>
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '059669',
                ],
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ====================== ISI DATA ======================
        $row = 6;

        foreach ($data as $index => $item) {
            // Nomor
            $sheet->setCellValue(
                'A' . $row,
                $index + 1
            );

            // Nama
            $sheet->setCellValue(
                'B' . $row,
                $item->nama_lengkap ?? ''
            );

            // Nomor HP disimpan sebagai teks agar angka 0 tidak hilang
            $sheet->setCellValueExplicit(
                'C' . $row,
                $item->no_hp ?? '',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            // Semua kolom rata tengah terlebih dahulu
            $sheet->getStyle('A' . $row . ':C' . $row)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            // Nama rata kiri dan tengah vertikal
            $sheet->getStyle('B' . $row)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            // Tinggi baris yang sudah berisi data
            $sheet->getRowDimension($row)->setRowHeight(27);

            $row++;
        }

        /*
        * Header ada di baris 5.
        * Data dimulai dari baris 6.
        * Baris 6 sampai 155 menghasilkan nomor 1 sampai 150.
        */
        $minimumLastRow = 155;

        // ====================== BARIS KOSONG SAMPAI NOMOR 150 ======================
        for ($i = $row; $i <= $minimumLastRow; $i++) {
            $sheet->setCellValue('A' . $i, $i - 5);
            $sheet->setCellValue('B' . $i, '');
            $sheet->setCellValue('C' . $i, '');

            // Semua kolom rata tengah terlebih dahulu
            $sheet->getStyle('A' . $i . ':C' . $i)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            // Kolom nama tetap rata kiri
            $sheet->getStyle('B' . $i)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            // Tinggi baris kosong
            $sheet->getRowDimension($i)->setRowHeight(35);
        }

        /*
        * Jika jumlah data lebih dari 150,
        * border dan format tetap mengikuti seluruh data.
        */
        $lastRow = max($minimumLastRow, $row - 1);

        // ====================== BORDER TABEL ======================
        $sheet->getStyle('A5:C' . $lastRow)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // ====================== LEBAR KOLOM ======================
        $sheet->getColumnDimension('A')->setWidth(6);  // NO
        $sheet->getColumnDimension('B')->setWidth(35); // NAMA
        $sheet->getColumnDimension('C')->setWidth(25); // NO HP

        // Tinggi header
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Freeze header tabel
        $sheet->freezePane('A6');

        // ====================== DOWNLOAD ======================
        $filename = 'Pendaftaran_Donor_Darah_'
            . now()->format('d_F_Y')
            . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        header(
            'Content-Disposition: attachment; filename="' . $filename . '"'
        );

        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function exportCekKesehatanNew(Request $request)
    {
        /*
        * Ambil pendaftar yang:
        * 1. Memilih salah satu cek kesehatan, atau
        * 2. Memilih cek kesehatan mata/katarak
        */
        $data = KesehatanRegistration::where(function ($query) {
            $query->where(function ($q) {
                $q->whereNotNull('cek_kesehatan')
                    ->whereJsonLength('cek_kesehatan', '>', 0);
            })
                ->orWhere('cek_mata_katarak', true);
        })
            ->orderBy('nama_lengkap', 'asc')
            ->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cek Kesehatan');

        // ====================== PAGE SETUP + MARGIN ======================
        $sheet->getPageSetup()
            ->setOrientation(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT
            )
            ->setPaperSize(
                \PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4
            )
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setHorizontalCentered(true);

        $sheet->getPageMargins()
            ->setTop(0.8)
            ->setRight(0.8)
            ->setLeft(0.8)
            ->setBottom(0.8);

        // ====================== JUDUL ======================
        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'PENDAFTARAN CEK KESEHATAN');

        $sheet->getStyle('A1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', 'Masjid Raudhotul Jannah TCE');

        $sheet->getStyle('A2')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        $sheet->mergeCells('A3:H3');
        $sheet->setCellValue(
            'A3',
            'Tanggal: ' . now()->translatedFormat('d F Y')
        );

        $sheet->getStyle('A3')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ====================== KETERANGAN SINGKATAN ======================
        $sheet->mergeCells('A4:H4');

        $sheet->setCellValue(
            'A4',
            'Keterangan: GD = Gula Darah | K = Kolesterol | AU = Asam Urat | TD = Tensi Darah | KM = Kesehatan Mata'
        );

        $sheet->getStyle('A4')->applyFromArray([
            'font' => [
                'italic' => true,
                'size' => 10,
                'color' => [
                    'rgb' => '374151',
                ],
            ],
            'fill' => [
                'fillType' =>
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F3F4F6',
                ],
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'wrapText' => false,
                'shrinkToFit' => true,
            ],
        ]);

        $sheet->getRowDimension(4)->setRowHeight(22);

        // ====================== HEADER TABEL ======================
        $header = [
            'NO',
            'NAMA',
            'NO HP',
            'GD',
            'K',
            'AU',
            'TD',
            'KM',
        ];

        $col = 'A';

        foreach ($header as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }

        // Styling header
        $sheet->getStyle('A5:H5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => [
                    'rgb' => 'FFFFFF',
                ],
            ],
            'fill' => [
                'fillType' =>
                \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => '059669',
                ],
            ],
            'alignment' => [
                'horizontal' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' =>
                \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ====================== ISI DATA ======================
        $row = 6;

        foreach ($data as $index => $item) {
            $cek = $item->cek_kesehatan ?? [];

            // Antisipasi jika cek_kesehatan masih berupa JSON string
            if (is_string($cek)) {
                $cek = json_decode($cek, true) ?? [];
            }

            if (!is_array($cek)) {
                $cek = [];
            }

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->nama_lengkap ?? '');

            // Nomor HP sebagai teks agar angka 0 di depan tidak hilang
            $sheet->setCellValueExplicit(
                'C' . $row,
                $item->no_hp ?? '',
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
            );

            // GD = Gula Darah
            $sheet->setCellValue(
                'D' . $row,
                in_array('gula_darah', $cek, true) ? '✓' : ''
            );

            // K = Kolesterol
            $sheet->setCellValue(
                'E' . $row,
                in_array('kolesterol', $cek, true) ? '✓' : ''
            );

            // AU = Asam Urat
            $sheet->setCellValue(
                'F' . $row,
                in_array('asam_urat', $cek, true) ? '✓' : ''
            );

            // TD = Tensi Darah
            $sheet->setCellValue(
                'G' . $row,
                in_array('tensi_darah', $cek, true) ? '✓' : ''
            );

            // KM = Kesehatan Mata/Katarak
            $sheet->setCellValue(
                'H' . $row,
                $item->cek_mata_katarak ? '✓' : ''
            );

            // Posisi semua data di tengah
            $sheet->getStyle('A' . $row . ':H' . $row)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            // Nama rata kiri dan tengah vertikal
            $sheet->getStyle('B' . $row)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            $sheet->getRowDimension($row)->setRowHeight(27);

            $row++;
        }

        // ====================== BARIS KOSONG SAMPAI 150 ======================
        for ($i = $row; $i <= 150; $i++) {
            $sheet->setCellValue('A' . $i, $i - 5);
            $sheet->setCellValue('B' . $i, '');
            $sheet->setCellValue('C' . $i, '');
            $sheet->setCellValue('D' . $i, '');
            $sheet->setCellValue('E' . $i, '');
            $sheet->setCellValue('F' . $i, '');
            $sheet->setCellValue('G' . $i, '');
            $sheet->setCellValue('H' . $i, '');

            $sheet->getStyle('A' . $i . ':H' . $i)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            // Kolom nama tetap rata kiri
            $sheet->getStyle('B' . $i)
                ->getAlignment()
                ->setHorizontal(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT
                )
                ->setVertical(
                    \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                );

            $sheet->getRowDimension($i)->setRowHeight(35);
        }

        // ====================== BORDER TABEL ======================
        $sheet->getStyle('A5:H150')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' =>
                    \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
            ],
        ]);

        // ====================== LEBAR KOLOM ======================
        $sheet->getColumnDimension('A')->setWidth(6);  // NO
        $sheet->getColumnDimension('B')->setWidth(32); // NAMA
        $sheet->getColumnDimension('C')->setWidth(18); // NO HP
        $sheet->getColumnDimension('D')->setWidth(8);  // GD
        $sheet->getColumnDimension('E')->setWidth(8);  // K
        $sheet->getColumnDimension('F')->setWidth(8);  // AU
        $sheet->getColumnDimension('G')->setWidth(8);  // TD
        $sheet->getColumnDimension('H')->setWidth(8);  // KM

        // Tinggi header
        $sheet->getRowDimension(5)->setRowHeight(25);

        // Freeze header tabel
        $sheet->freezePane('A6');

        // ====================== DOWNLOAD ======================
        $filename = 'Cek_Kesehatan_' . now()->format('d_F_Y') . '.xlsx';

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header(
            'Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );
        header(
            'Content-Disposition: attachment; filename="' . $filename . '"'
        );
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // public function exportCekKatarak(Request $request)
    // {
    //     // Ambil data cek katarak
    //     $data = KesehatanRegistration::where('cek_mata_katarak', true)
    //         ->get();

    //     $spreadsheet = new Spreadsheet;
    //     $sheet = $spreadsheet->getActiveSheet();
    //     $sheet->setTitle('Cek Katarak');

    //     // ====================== PAGE SETUP - A4 Portrait ======================
    //     $sheet->getPageSetup()
    //         ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
    //         ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

    //     $sheet->getPageSetup()->setHorizontalCentered(true);

    //     // ====================== JUDUL (Urutan sama persis dengan Donor Darah) ======================
    //     $sheet->mergeCells('A1:E1');
    //     $sheet->setCellValue('A1', 'PENDAFTARAN CEK KATARAK');
    //     $sheet->getStyle('A1')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 16],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    //         ],
    //     ]);

    //     $sheet->mergeCells('A2:E2');
    //     $sheet->setCellValue('A2', 'Tanggal: 18 April 2026');
    //     $sheet->getStyle('A2')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 12],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    //         ],
    //     ]);

    //     $sheet->mergeCells('A3:E3');
    //     $sheet->setCellValue('A3', 'MASJID RAUDHOTUL JANNAH TCE');
    //     $sheet->getStyle('A3')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 11],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    //         ],
    //     ]);

    //     // ====================== HEADER TABEL (Urutan sama seperti Donor Darah) ======================
    //     $header = ['NO', 'NAMA', 'ALAMAT', 'NO HP', 'PARAF'];
    //     $col = 'A';
    //     foreach ($header as $h) {
    //         $sheet->setCellValue($col . '6', $h);
    //         $col++;
    //     }

    //     // Styling Header (hijau sama persis)
    //     $sheet->getStyle('A6:E6')->applyFromArray([
    //         'font' => [
    //             'bold' => true,
    //             'color' => ['rgb' => 'FFFFFF'],
    //         ],
    //         'fill' => [
    //             'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    //             'startColor' => ['rgb' => '059669'],
    //         ],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
    //         ],
    //     ]);

    //     // ====================== ISI DATA ======================
    //     $row = 7;

    //     foreach ($data as $index => $item) {
    //         $sheet->setCellValue('A' . $row, $index + 1);
    //         $sheet->setCellValue('B' . $row, $item->nama_lengkap ?? '');
    //         $sheet->setCellValue('C' . $row, $item->alamat ?? '-');
    //         $sheet->setCellValue('D' . $row, $item->no_hp ? "'" . $item->no_hp : '');
    //         $sheet->setCellValue('E' . $row, '');   // PARAF kosong

    //         $row++;
    //     }

    //     // Lanjutkan nomor urut sampai 100
    //     for ($i = $row; $i <= 106; $i++) {
    //         $nomor = $i - 6;
    //         $sheet->setCellValue('A' . $i, $nomor);
    //         $sheet->setCellValue('B' . $i, '');
    //         $sheet->setCellValue('C' . $i, '');
    //         $sheet->setCellValue('D' . $i, '');
    //         $sheet->setCellValue('E' . $i, '');
    //     }

    //     // Border untuk semua baris data
    //     $sheet->getStyle('A7:E106')->applyFromArray([
    //         'borders' => [
    //             'allBorders' => [
    //                 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
    //             ],
    //         ],
    //     ]);

    //     // ====================== PENGATURAN LEBAR KOLOM (PERSIS SEPERTI PERMINTAAN TERAKHIR) ======================
    //     $sheet->getColumnDimension('A')->setWidth(6);      // NO
    //     $sheet->getColumnDimension('B')->setWidth(25);     // NAMA
    //     $sheet->getColumnDimension('C')->setWidth(25);     // ALAMAT
    //     $sheet->getColumnDimension('D')->setWidth(18);     // NO HP
    //     $sheet->getColumnDimension('E')->setWidth(10);     // PARAF

    //     // Freeze pane
    //     $sheet->freezePane('A7');

    //     // ====================== DOWNLOAD ======================
    //     $filename = 'Cek_Katarak_' . now()->format('d_F_Y') . '.xlsx';

    //     $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    //     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //     header('Content-Disposition: attachment; filename="' . $filename . '"');
    //     header('Cache-Control: max-age=0');

    //     $writer->save('php://output');
    //     exit;
    // }

    // ===================== HELPER =====================
    private function styleHeader($sheet, $range)
    {
        $style = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF'],
            ],
        ];
        $sheet->getStyle($range)->applyFromArray($style);
    }

    private function autoSizeColumns($sheet, array $columns)
    {
        foreach ($columns as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function downloadExcel($spreadsheet, $filename)
    {
        $writer = new Xlsx($spreadsheet);

        return response()->stream(function () use ($writer) {
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
