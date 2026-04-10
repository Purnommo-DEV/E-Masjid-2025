<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KesehatanRegistration;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class KesehatanGuestController extends Controller
{
    public function index()
    {
        $eventDate = now()->format('Y-m-d');

        $allRegistrations = KesehatanRegistration::where('event_date', $eventDate)
            ->latest()->get();

        $totalPendaftar = $allRegistrations->count();

        $donorDarah = KesehatanRegistration::where('event_date', $eventDate)
            ->where('donor_darah', true)->latest()->get();

        $cekKesehatan = KesehatanRegistration::where('event_date', $eventDate)
            ->whereNotNull('cek_kesehatan')
            ->whereJsonLength('cek_kesehatan', '>', 0)
            ->latest()->get();

        $cekKatarak = KesehatanRegistration::where('event_date', $eventDate)
            ->where('cek_mata_katarak', true)->latest()->get();

        $seoData = new SEOData(
            title: 'Daftar Pendaftar Program Kesehatan',
            description: 'Daftar peserta Program Kesehatan Masjid Raudhotul Jannah TCE',
        );

        return view('masjid.' . masjid() . '.guest.program-kesehatan.index', compact(
            'allRegistrations', 'totalPendaftar', 'donorDarah', 
            'cekKesehatan', 'cekKatarak', 'eventDate', 'seoData'
        ));
    }

    public function create()
    {
        $eventDate = now()->format('Y-m-d'); // Ubah manual sesuai jadwal event

        return view('masjid.' . masjid() . '.guest.program-kesehatan.daftar', compact('eventDate'));
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
            'no_hp'        => 'required|string|max:20',
            'alamat'       => 'nullable|string|max:500',
        ]);

        KesehatanRegistration::create([
            'nama_lengkap'     => $request->nama_lengkap,
            'no_hp'            => $request->no_hp,
            'alamat'           => $request->alamat,
            'event_date'       => $request->event_date ?? now()->format('Y-m-d'),
            'donor_darah'      => $request->boolean('donor_darah'),
            'cek_kesehatan'    => $request->cek_kesehatan ?? [],
            'cek_mata_katarak' => $request->boolean('cek_mata_katarak'),
        ]);

        // Kirim nama melalui query string
        return response()->json([
            'success'      => true,
            'nama_lengkap' => $request->nama_lengkap,
            'redirect'     => route('donor-darah.success', ['name' => $request->nama_lengkap])
        ]);
    }
    // ===================== EXPORT =====================

    public function exportDonorDarah(Request $request)
    {
        $eventDate = $request->get('event_date', now()->format('Y-m-d'));
    
        // Ambil data donor darah
        $data = KesehatanRegistration::where('event_date', $eventDate)
            ->where('donor_darah', true)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Donor Darah');

        // ====================== PAGE SETUP - A4 Portrait ======================
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        $sheet->getPageSetup()->setHorizontalCentered(true);

        // ====================== JUDUL ======================
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'PENDAFTARAN DONOR DARAH');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Tanggal: 18 April 2026');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', 'MASJID RAUDHOTUL JANNAH TCE');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // ====================== HEADER TABEL ======================
        $header = ['NO', 'NAMA', 'ALAMAT', 'NO HP', 'PARAF'];
        $col = 'A';
        foreach ($header as $h) {
            $sheet->setCellValue($col . '6', $h);   // Header digeser ke baris 6
            $col++;
        }

        // Styling Header (hijau)
        $sheet->getStyle('A6:E6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // ====================== ISI DATA ======================
        $row = 7;   // mulai dari baris 7

        foreach ($data as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->nama_lengkap ?? '');
            $sheet->setCellValue('C' . $row, $item->alamat ?? '-');
            $sheet->setCellValue('D' . $row, $item->no_hp ? "'" . $item->no_hp : '');
            $sheet->setCellValue('E' . $row, '');   // Paraf kosong

            $row++;
        }

        // Lanjutkan nomor urut sampai 150 (baris kosong)
        for ($i = $row; $i <= 156; $i++) {
            $nomor = $i - 6;
            $sheet->setCellValue('A' . $i, $nomor);
            $sheet->setCellValue('B' . $i, '');
            $sheet->setCellValue('C' . $i, '');
            $sheet->setCellValue('D' . $i, '');
            $sheet->setCellValue('E' . $i, '');
        }

        // Border untuk semua baris data
        $sheet->getStyle('A7:E156')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ]);

        // ====================== PENGATURAN LEBAR KOLOM ======================
        $sheet->getColumnDimension('A')->setWidth(6);      // NO
        $sheet->getColumnDimension('B')->setWidth(25);     // NAMA
        $sheet->getColumnDimension('C')->setWidth(25);     // ALAMAT
        $sheet->getColumnDimension('D')->setWidth(18);     // NO HP
        $sheet->getColumnDimension('E')->setWidth(10);     // PARAF

        // Freeze pane
        $sheet->freezePane('A7');

        // ====================== DOWNLOAD ======================
        $filename = "Pendaftaran_Donor_Darah_" . now()->parse($eventDate)->format('d_F_Y') . ".xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // public function exportCekKesehatan(Request $request)
    // {
    //     $eventDate = $request->get('event_date', now()->format('Y-m-d'));
    
    //     // Ambil data cek kesehatan
    //     $data = KesehatanRegistration::where('event_date', $eventDate)
    //         ->whereNotNull('cek_kesehatan')
    //         ->whereJsonLength('cek_kesehatan', '>', 0)
    //         ->get();

    //     $spreadsheet = new Spreadsheet();
    //     $sheet = $spreadsheet->getActiveSheet();
    //     $sheet->setTitle('Cek Kesehatan');

    //     // ====================== PAGE SETUP - A4 Portrait ======================
    //     $sheet->getPageSetup()
    //         ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
    //         ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

    //     $sheet->getPageSetup()->setHorizontalCentered(true);

    //     // ====================== JUDUL ======================
    //     $sheet->mergeCells('A1:G1');
    //     $sheet->setCellValue('A1', 'PENDAFTARAN PEMERIKSAAN DARAH');
    //     $sheet->getStyle('A1')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 16],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    //         ]
    //     ]);

    //     $sheet->mergeCells('A2:G2');
    //     $sheet->setCellValue('A2', '(Gula Darah, Kolesterol dan Asam Urat)');
    //     $sheet->getStyle('A2')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 12],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    //         ]
    //     ]);

    //     $sheet->mergeCells('A3:G3');
    //     $sheet->setCellValue('A3', 'Tanggal: 18 April 2026');
    //     $sheet->getStyle('A3')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 12],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    //         ]
    //     ]);

    //     $sheet->mergeCells('A4:G4');
    //     $sheet->setCellValue('A4', 'MASJID RAUDHOTUL JANNAH TCE');
    //     $sheet->getStyle('A4')->applyFromArray([
    //         'font' => ['bold' => true, 'size' => 11],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    //         ]
    //     ]);

    //     // ====================== HEADER TABEL ======================
    //     $header = ['NO', 'NAMA', 'ALAMAT', 'GD', 'K', 'AU', 'NO HP'];
    //     $col = 'A';
    //     foreach ($header as $h) {
    //         $sheet->setCellValue($col . '6', $h);
    //         $col++;
    //     }

    //     // Styling Header (hijau)
    //     $sheet->getStyle('A6:G6')->applyFromArray([
    //         'font' => [
    //             'bold' => true,
    //             'color' => ['rgb' => 'FFFFFF']
    //         ],
    //         'fill' => [
    //             'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
    //             'startColor' => ['rgb' => '059669']
    //         ],
    //         'alignment' => [
    //             'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
    //             'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
    //         ]
    //     ]);

    //     // ====================== ISI DATA ======================
    //     $row = 7;

    //     foreach ($data as $index => $item) {
    //         $cek = $item->cek_kesehatan ?? [];

    //         $sheet->setCellValue('A' . $row, $index + 1);
    //         $sheet->setCellValue('B' . $row, $item->nama_lengkap ?? '');
    //         $sheet->setCellValue('C' . $row, $item->alamat ?? '-');
    //         $sheet->setCellValue('D' . $row, in_array('gula_darah', $cek) ? '✓' : '');
    //         $sheet->setCellValue('E' . $row, in_array('kolesterol', $cek) ? '✓' : '');
    //         $sheet->setCellValue('F' . $row, in_array('asam_urat', $cek) ? '✓' : '');
    //         $sheet->setCellValue('G' . $row, $item->no_hp ? "'" . $item->no_hp : '');

    //         $row++;
    //     }

    //     // Lanjutkan nomor urut sampai 150 (baris kosong)
    //     for ($i = $row; $i <= 156; $i++) {
    //         $nomor = $i - 6;
    //         $sheet->setCellValue('A' . $i, $nomor);
    //         $sheet->setCellValue('B' . $i, '');
    //         $sheet->setCellValue('C' . $i, '');
    //         $sheet->setCellValue('D' . $i, '');
    //         $sheet->setCellValue('E' . $i, '');
    //         $sheet->setCellValue('F' . $i, '');
    //         $sheet->setCellValue('G' . $i, '');
    //     }

    //     // Border untuk semua baris data
    //     $sheet->getStyle('A7:G156')->applyFromArray([
    //         'borders' => [
    //             'allBorders' => [
    //                 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
    //             ]
    //         ]
    //     ]);

    //     // ====================== PENGATURAN LEBAR KOLOM (SESUAI PERMINTAAN) ======================
    //     $sheet->getColumnDimension('A')->setWidth(6);      // NO
    //     $sheet->getColumnDimension('B')->setWidth(25);     // NAMA
    //     $sheet->getColumnDimension('C')->setWidth(20);     // ALAMAT
    //     $sheet->getColumnDimension('D')->setWidth(5);      // Gula Darah
    //     $sheet->getColumnDimension('E')->setWidth(5);      // Kolesterol
    //     $sheet->getColumnDimension('F')->setWidth(5);      // Asam Urat
    //     $sheet->getColumnDimension('G')->setWidth(18);     // NO HP

    //     // Freeze pane
    //     $sheet->freezePane('A7');

    //     // ====================== DOWNLOAD ======================
    //     $filename = "Pemeriksaan_Darah_" . now()->parse($eventDate)->format('d_F_Y') . ".xlsx";

    //     $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    //     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //     header('Content-Disposition: attachment; filename="' . $filename . '"');
    //     header('Cache-Control: max-age=0');

    //     $writer->save('php://output');
    //     exit;
    // }

    public function exportCekKesehatanNew(Request $request)
    {
        $eventDate = $request->get('event_date', now()->format('Y-m-d'));

        // Ambil data pendaftar yang memilih cek kesehatan
        $data = KesehatanRegistration::where('event_date', $eventDate)
            ->whereNotNull('cek_kesehatan')
            ->whereJsonLength('cek_kesehatan', '>', 0)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cek Kesehatan');

        // ====================== PAGE SETUP + MARGIN ======================
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4)
            ->setHorizontalCentered(true);

        $sheet->getPageMargins()
            ->setTop(0.8)
            ->setRight(0.8)
            ->setLeft(0.8)
            ->setBottom(0.8);

        // ====================== JUDUL ======================
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'PENDAFTARAN CEK KESEHATAN');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->mergeCells('A2:F2');
        $sheet->setCellValue('A2', 'Masjid Raudhotul Jannah TCE');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->mergeCells('A3:F3');
        $sheet->setCellValue('A3', 'Tanggal: ' . now()->parse($eventDate)->translatedFormat('d F Y'));
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // ====================== HEADER TABEL ======================
        $header = ['NO', 'NAMA LENGKAP', 'ALAMAT', 'CEK GULA DARAH', 'CEK TENSI DARAH', 'NO HP'];
        
        $col = 'A';
        foreach ($header as $h) {
            $sheet->setCellValue($col . '5', $h);
            $col++;
        }

        // Styling Header (hijau)
        $sheet->getStyle('A5:F5')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // ====================== ISI DATA ======================
        $row = 6;
        foreach ($data as $index => $item) {
            $cek = $item->cek_kesehatan ?? [];

            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->nama_lengkap ?? '');
            $sheet->setCellValue('C' . $row, $item->alamat ?? '-');
            $sheet->setCellValue('D' . $row, in_array('gula_darah', $cek) ? '✓' : '');
            $sheet->setCellValue('E' . $row, in_array('tensi_darah', $cek) ? '✓' : '');
            $sheet->setCellValue('F' . $row, $item->no_hp ? "'" . $item->no_hp : '');

            $row++;
        }

        // Tambahkan baris kosong sampai baris ke-150
        for ($i = $row; $i <= 150; $i++) {
            $sheet->setCellValue('A' . $i, $i - 5);
            $sheet->setCellValue('B' . $i, '');
            $sheet->setCellValue('C' . $i, '');
            $sheet->setCellValue('D' . $i, '');
            $sheet->setCellValue('E' . $i, '');
            $sheet->setCellValue('F' . $i, '');
        }

        // Border semua tabel
        $sheet->getStyle('A5:F150')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ]);

        // ====================== LEBAR KOLOM ======================
        $sheet->getColumnDimension('A')->setWidth(6);      // NO
        $sheet->getColumnDimension('B')->setWidth(30);     // NAMA LENGKAP
        $sheet->getColumnDimension('C')->setWidth(28);     // ALAMAT
        $sheet->getColumnDimension('D')->setWidth(18);     // CEK GULA DARAH
        $sheet->getColumnDimension('E')->setWidth(18);     // CEK TENSI DARAH
        $sheet->getColumnDimension('F')->setWidth(18);     // NO HP

        // Freeze pane
        $sheet->freezePane('A6');

        // ====================== DOWNLOAD ======================
        $filename = "Cek_Kesehatan_" . now()->parse($eventDate)->format('d_F_Y') . ".xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function exportCekKatarak(Request $request)
    {
        $eventDate = $request->get('event_date', now()->format('Y-m-d'));
    
        // Ambil data cek katarak
        $data = KesehatanRegistration::where('event_date', $eventDate)
            ->where('cek_mata_katarak', true)
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cek Katarak');

        // ====================== PAGE SETUP - A4 Portrait ======================
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_A4);

        $sheet->getPageSetup()->setHorizontalCentered(true);

        // ====================== JUDUL (Urutan sama persis dengan Donor Darah) ======================
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'PENDAFTARAN CEK KATARAK');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 16],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->mergeCells('A2:E2');
        $sheet->setCellValue('A2', 'Tanggal: 18 April 2026');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        $sheet->mergeCells('A3:E3');
        $sheet->setCellValue('A3', 'MASJID RAUDHOTUL JANNAH TCE');
        $sheet->getStyle('A3')->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // ====================== HEADER TABEL (Urutan sama seperti Donor Darah) ======================
        $header = ['NO', 'NAMA', 'ALAMAT', 'NO HP', 'PARAF'];
        $col = 'A';
        foreach ($header as $h) {
            $sheet->setCellValue($col . '6', $h);
            $col++;
        }

        // Styling Header (hijau sama persis)
        $sheet->getStyle('A6:E6')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '059669']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ]
        ]);

        // ====================== ISI DATA ======================
        $row = 7;

        foreach ($data as $index => $item) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $item->nama_lengkap ?? '');
            $sheet->setCellValue('C' . $row, $item->alamat ?? '-');
            $sheet->setCellValue('D' . $row, $item->no_hp ? "'" . $item->no_hp : '');
            $sheet->setCellValue('E' . $row, '');   // PARAF kosong

            $row++;
        }

        // Lanjutkan nomor urut sampai 100 
        for ($i = $row; $i <= 106; $i++) {
            $nomor = $i - 6;
            $sheet->setCellValue('A' . $i, $nomor);
            $sheet->setCellValue('B' . $i, '');
            $sheet->setCellValue('C' . $i, '');
            $sheet->setCellValue('D' . $i, '');
            $sheet->setCellValue('E' . $i, '');
        }

        // Border untuk semua baris data
        $sheet->getStyle('A7:E106')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ]);

        // ====================== PENGATURAN LEBAR KOLOM (PERSIS SEPERTI PERMINTAAN TERAKHIR) ======================
        $sheet->getColumnDimension('A')->setWidth(6);      // NO
        $sheet->getColumnDimension('B')->setWidth(25);     // NAMA
        $sheet->getColumnDimension('C')->setWidth(25);     // ALAMAT
        $sheet->getColumnDimension('D')->setWidth(18);     // NO HP
        $sheet->getColumnDimension('E')->setWidth(10);     // PARAF

        // Freeze pane
        $sheet->freezePane('A7');

        // ====================== DOWNLOAD ======================
        $filename = "Cek_Katarak_" . now()->parse($eventDate)->format('d_F_Y') . ".xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    // ===================== HELPER =====================
    private function styleHeader($sheet, $range)
    {
        $style = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E40AF']
            ]
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