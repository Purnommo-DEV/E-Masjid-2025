<?php
// app/Http/Controllers/User/QurbanGuestController.php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Interfaces\QurbanRepositoryInterface;
use App\Interfaces\QurbanSettingRepositoryInterface;
use App\Models\QurbanRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\QurbanReport;

class QurbanGuestController extends Controller
{
    protected $qurbanRepo;
    protected $settingRepo;

    public function __construct(
        QurbanRepositoryInterface $qurbanRepo,
        QurbanSettingRepositoryInterface $settingRepo
    ) {
        $this->qurbanRepo = $qurbanRepo;
        $this->settingRepo = $settingRepo;
    }

    /**
     * Tampilkan halaman utama qurban
     */
    public function index()
    {
        // Ambil paket qurban yang aktif
        $qurbans = $this->qurbanRepo->getActivePakets();
        
        // Ambil semua pengaturan
        $settings = $this->settingRepo->getAllSettings();
        
        // Data untuk Hero Section
        $heroTitle = $settings['hero_title'] ?? 'Masjid Raudhotul Jannah';
        $heroSubtitle = $settings['hero_subtitle'] ?? 'Menerima & Menyalurkan Hewan Qurban';
        $heroBadge = $settings['hero_badge_text'] ?? 'PANITIA IDUL ADHA 1447 H / 2026 M';
        
        // Data Statistik
        $statsHewan = $settings['stats_hewan_tersedia'] ?? '50+';
        $statsLokasi = $settings['stats_lokasi_distribusi'] ?? 'TCE';
        $statsPenerima = $settings['stats_penerima_manfaat'] ?? '500+';
        $statsTersalurkan = $settings['stats_tersalurkan'] ?? '100%';
        
        // Data Kontak
        $contactInfoName = $settings['contact_info_name'] ?? 'Bapak Joko';
        $contactInfoPhone = $settings['contact_info_phone'] ?? '085716503815';
        $contactConfirmName = $settings['contact_confirmation_name'] ?? 'Bapak Jazuli';
        $contactConfirmPhone = $settings['contact_confirmation_phone'] ?? '081310185948';
        
        // Data Bank
        $bankName = $settings['bank_name'] ?? 'BCA';
        $bankAccount = $settings['bank_account_number'] ?? '1010010947479';
        $bankAccountName = $settings['bank_account_name'] ?? 'JAZULI';
        
        // Data Harga Potong
        $potongSapi = number_format($settings['potong_sapi_harga'] ?? 1800000, 0, ',', '.');
        $potongKambing = number_format($settings['potong_kambing_harga'] ?? 300000, 0, ',', '.');
        
        // Data Catatan Penting
        $importantNotes = $settings['important_notes'] ?? [];
        if (empty($importantNotes)) {
            $importantNotes = [
                'Pendaftaran paling lambat H-2 sebelum Idul Adha (8 Dzulhijjah)',
                'Penyerahan hewan sendiri: H-1 sebelum hari pemotongan',
                'Jika patungan 1 ekor sapi tidak mencapai 7 orang, akan dialihkan ke qurban kambing & membayar biaya potong dan distribusi Rp150.000',
                'Harga sudah termasuk biaya potong dan distribusi untuk paket resmi panitia',
            ];
        }
        
        // Data FAQ
        $faqItems = $settings['faq_items'] ?? [];
        if (empty($faqItems)) {
            $faqItems = [
                ['question' => 'Bolehkah qurban untuk orang yang sudah meninggal?', 'answer' => 'Boleh, asalkan diniatkan untuk mereka yang telah wafat.'],
                ['question' => 'Bagaimana cara pembayaran qurban?', 'answer' => "Transfer ke rekening $bankName $bankAccount a.n. $bankAccountName, lalu konfirmasi ke $contactConfirmName."],
                ['question' => 'Apakah bisa memilih lokasi distribusi?', 'answer' => 'Distribusi difokuskan ke Taman Cipulir Estate dan sekitarnya.'],
                ['question' => 'Apa yang terjadi jika patungan sapi tidak sampai 7 orang?', 'answer' => 'Akan dialihkan ke qurban kambing dengan biaya tambahan potong Rp150.000.'],
            ];
        }
        
        return view('masjid.' . masjid() . '.guest.program-qurban.index', compact(
            'qurbans',
            'heroTitle',
            'heroSubtitle',
            'heroBadge',
            'statsHewan',
            'statsLokasi',
            'statsPenerima',
            'statsTersalurkan',
            'contactInfoName',
            'contactInfoPhone',
            'contactConfirmName',
            'contactConfirmPhone',
            'bankName',
            'bankAccount',
            'bankAccountName',
            'potongSapi',
            'potongKambing',
            'importantNotes',
            'faqItems'
        ));
    }

    public function evaluasi()
    {
        return view('masjid.' . masjid() . '.guest.program-qurban.1446');
    }

    /**
     * Proses pendaftaran qurban
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'qurban_id'     => 'required|exists:qurbans,id',
            'nama_lengkap'  => 'required|string|max:255',
            'telepon'       => 'required|string|max:20',
            'alamat'        => 'nullable|string',
            'jumlah_share'  => 'nullable|integer|min:1|max:7',
            'catatan'       => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Cari paket qurban
            $qurban = $this->qurbanRepo->find($validated['qurban_id']);
            
            // Cek stok
            if ($qurban->stok <= 0) {
                throw new \Exception('Maaf, stok paket qurban habis!');
            }

            $jumlahShare = $validated['jumlah_share'] ?? 1;
            
            // Validasi jumlah share tidak melebihi max_share
            if ($jumlahShare > $qurban->max_share) {
                throw new \Exception('Jumlah share melebihi batas maksimal (' . $qurban->max_share . ' orang)');
            }

            // Hitung total harga
            $totalHarga = $qurban->harga * $jumlahShare;

            // Simpan pendaftaran
            $registration = QurbanRegistration::create([
                'masjid_code'   => masjid(),
                'qurban_id'     => $validated['qurban_id'],
                'nama_lengkap'  => $validated['nama_lengkap'],
                'telepon'       => $validated['telepon'],
                'alamat'        => $validated['alamat'] ?? null,
                'jumlah_share'  => $jumlahShare,
                'total_harga'   => $totalHarga,
                'catatan'       => $validated['catatan'] ?? null,
                'status'        => 'pending',
            ]);

            // Kurangi stok
            $this->qurbanRepo->updateStok($validated['qurban_id'], $jumlahShare);

            DB::commit();

            // Redirect ke halaman sukses
            return redirect()->route('qurban.thankyou', ['kode' => $registration->kode_registrasi])
                ->with('success', 'Pendaftaran qurban berhasil! Kode registrasi: ' . $registration->kode_registrasi);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Qurban registration failed: ' . $e->getMessage());
            
            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Halaman terima kasih setelah pendaftaran
     */
    public function thankyou($kode = null)
    {
        $registration = null;
        if ($kode) {
            $registration = QurbanRegistration::where('kode_registrasi', $kode)
                ->where('masjid_code', masjid())
                ->with('qurban')
                ->first();
        }

        $settings = $this->settingRepo->getAllSettings();
        $contactConfirmName = $settings['contact_confirmation_name'] ?? 'Bapak Jazuli';
        $contactConfirmPhone = $settings['contact_confirmation_phone'] ?? '081310185948';
        $bankName = $settings['bank_name'] ?? 'BCA';
        $bankAccount = $settings['bank_account_number'] ?? '1010010947479';
        $bankAccountName = $settings['bank_account_name'] ?? 'JAZULI';
        
        return view('masjid.' . masjid() . '.guest.program-qurban.thankyou', compact(
            'registration',
            'contactConfirmName',
            'contactConfirmPhone',
            'bankName',
            'bankAccount',
            'bankAccountName'
        ));
    }

    /**
     * Cek stok via AJAX (untuk real-time validation)
     */
    public function checkStock(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:qurbans,id'
        ]);
        
        $qurban = $this->qurbanRepo->find($request->id);
        
        return response()->json([
            'success' => true,
            'stok' => $qurban->stok,
            'max_share' => $qurban->max_share,
            'harga' => $qurban->harga,
            'harga_formatted' => $qurban->harga_formatted,
            'jenis_hewan' => $qurban->jenis_label,
            'share_badge' => $qurban->share_badge,
        ]);
    }

    /**
     * Get detail paket via AJAX
     */
    public function getPaketDetail($id)
    {
        try {
            $qurban = $this->qurbanRepo->find($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $qurban->id,
                    'jenis_hewan' => $qurban->jenis_label,
                    'jenis_icon' => $qurban->jenis_icon,
                    'harga' => $qurban->harga,
                    'harga_formatted' => $qurban->harga_formatted,
                    'harga_full' => $qurban->harga_full,
                    'harga_full_formatted' => $qurban->harga_full_formatted,
                    'max_share' => $qurban->max_share,
                    'stok' => $qurban->stok,
                    'berat_range' => $qurban->berat_range,
                    'deskripsi_singkat' => $qurban->deskripsi_singkat,
                    'deskripsi_lengkap' => $qurban->deskripsi_lengkap,
                    'share_badge' => $qurban->share_badge,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Paket tidak ditemukan'
            ], 404);
        }
    }

    // public function laporan($tahun = null)
    // {
    //     // Ambil data laporan dari database
    //     if ($tahun) {
    //         $report = QurbanReport::where('masjid_code', masjid())
    //             ->where('tahun_hijriah', $tahun)
    //             ->where('is_published', true)
    //             ->first();
    //     } else {
    //         // Jika tidak ada tahun, ambil yang aktif, atau yang terbaru
    //         $report = QurbanReport::where('masjid_code', masjid())
    //             ->where('is_active', true)
    //             ->where('is_published', true)
    //             ->first();
            
    //         if (!$report) {
    //             $report = QurbanReport::where('masjid_code', masjid())
    //                 ->where('is_published', true)
    //                 ->orderBy('tahun_hijriah', 'desc')
    //                 ->first();
    //         }
    //     }
        
    //     // Jika tidak ada laporan sama sekali, buat data default
    //     if (!$report) {
    //         $report = $this->getDefaultReport();
    //     }
        
    //     // Data untuk dropdown tahun
    //     $availableYears = QurbanReport::where('masjid_code', masjid())
    //         ->where('is_published', true)
    //         ->orderBy('tahun_hijriah', 'desc')
    //         ->pluck('tahun_hijriah')
    //         ->toArray();
        
    //     // Siapkan data untuk blade dari $report
    //     $heroData = [
    //         'title' => $report->hero_title,
    //         'subtitle' => $report->hero_subtitle ?? $report->tahun_hijriah,
    //         'badge' => str_replace('{TAHUN}', $report->tahun_hijriah, $report->hero_badge),
    //         'masjid' => $report->hero_masjid,
    //         'tagline' => $report->hero_tagline,
    //     ];
        
    //     $stats = [
    //         'hewan' => [
    //             'sapi' => $report->stat_hewan_sapi,
    //             'kambing' => $report->stat_hewan_kambing,
    //             'total' => $report->stat_hewan_sapi + $report->stat_hewan_kambing,
    //         ],
    //         'paket' => $report->stat_paket_daging,
    //         'mustahik' => $report->stat_mustahik,
    //         'daging_kg' => $report->stat_daging_kg,
    //         'jamaah' => $report->stat_jamaah,
    //     ];
        
    //     // Data Pelaksanaan
    //     $pelaksanaan = [
    //         'ketua_nama' => $report->pelaksanaan_ketua_nama,
    //         'ketua_jabatan' => $report->pelaksanaan_ketua_jabatan,
    //         'masjid_nama' => $report->pelaksanaan_masjid_nama,
    //         'masjid_sub' => $report->pelaksanaan_masjid_sub,
    //         'lokasi_sholat' => $report->pelaksanaan_lokasi_sholat,
    //         'lokasi_qurban' => $report->pelaksanaan_lokasi_qurban,
    //         'deskripsi' => $report->pelaksanaan_deskripsi,
    //         'gambar1' => $report->pelaksanaan_gambar1,
    //         'gambar2' => $report->pelaksanaan_gambar2,
    //         'gambar3' => $report->pelaksanaan_gambar3,
    //     ];
        
    //     // Data Dramatis
    //     $dramatis = [
    //         1 => [
    //             'title' => $report->dramatis1_title,
    //             'quote' => $report->dramatis1_quote,
    //             'stat' => $report->dramatis1_stat,
    //             'image' => $report->dramatis1_image,
    //         ],
    //         2 => [
    //             'title' => $report->dramatis2_title,
    //             'quote' => $report->dramatis2_quote,
    //             'stat' => $report->dramatis2_stat,
    //             'image' => $report->dramatis2_image,
    //         ],
    //         3 => [
    //             'title' => $report->dramatis3_title,
    //             'quote' => $report->dramatis3_quote,
    //             'stat' => $report->dramatis3_stat,
    //             'image' => $report->dramatis3_image,
    //         ],
    //     ];
        
    //     // Data Pemotongan
    //     $pemotongan = [
    //         'sapi_berat_kg' => $report->pemotongan_sapi_berat_kg,
    //         'kambing_berat_kg' => $report->pemotongan_kambing_berat_kg,
    //     ];
        
    //     // Data Keuangan - pastikan amount berupa integer
    //     $keuangan = [];
    //     if ($report->keuangan_penerimaan) {
    //         $keuangan['penerimaan'] = [];
    //         foreach ($report->keuangan_penerimaan as $item) {
    //             // Bersihkan amount menjadi integer
    //             $cleanAmount = preg_replace('/[^0-9]/', '', $item['amount']);
    //             $keuangan['penerimaan'][] = [
    //                 'label' => $item['label'],
    //                 'amount' => (int)$cleanAmount
    //             ];
    //         }
    //     }
    //     if ($report->keuangan_pengeluaran) {
    //         $keuangan['pengeluaran'] = [];
    //         foreach ($report->keuangan_pengeluaran as $item) {
    //             // Bersihkan amount menjadi integer
    //             $cleanAmount = preg_replace('/[^0-9]/', '', $item['amount']);
    //             $keuangan['pengeluaran'][] = [
    //                 'label' => $item['label'],
    //                 'amount' => (int)$cleanAmount
    //             ];
    //         }
    //     }
    //     $keuangan['total_penerimaan'] = $report->total_penerimaan;
    //     $keuangan['total_pengeluaran'] = $report->total_pengeluaran;
    //     $keuangan['sisa_dana'] = $report->sisa_dana;
        
    //     // Data Penerima Manfaat (Rings) dengan perhitungan total
    //     $rings = $report->rings_formatted;
    //     $penerima = [];
    //     $grandTotalPenerima = 0;

    //     foreach ($rings as $index => $ring) {
    //         // Hitung total ring dari items
    //         $ringTotal = 0;
    //         foreach ($ring['items'] as $item) {
    //             // Ekstrak angka dari value (contoh: "792 penerima" -> 792)
    //             $number = preg_replace('/[^0-9]/', '', $item['value']);
    //             $ringTotal += (int)$number;
    //         }
    //         $grandTotalPenerima += $ringTotal;
            
    //         $penerima[] = [
    //             'title' => $ring['title'],
    //             'icon' => $ring['icon'] ?? 'fa-building',
    //             'color' => $ring['color'] ?? 'emerald',
    //             'items' => $ring['items'],
    //             'total' => number_format($ringTotal) . ' penerima',
    //         ];
    //     }

    //     // Jika tidak ada rings, beri default
    //     if (empty($penerima)) {
    //         $penerima = [
    //             [
    //                 'title' => 'RING I — Warga TCE & Perangkat',
    //                 'icon' => 'fa-building',
    //                 'color' => 'emerald',
    //                 'items' => [
    //                     ['label' => '👥 Warga RT/RW Taman Cipulir Estate', 'value' => 'Seluruh warga'],
    //                     ['label' => '🛡️ Satpam & Tukang Taman TCE', 'value' => '10 orang'],
    //                     ['label' => '📚 Guru TPA, SD & SMP Al Madinah', 'value' => '23 orang'],
    //                     ['label' => '🕌 Perangkat Masjid & Panitia', 'value' => '40 orang'],
    //                     ['label' => '🥩 Tukang Jagal / Pemotong', 'value' => '57 orang'],
    //                 ],
    //                 'total' => '130+ penerima'
    //             ],
    //             [
    //                 'title' => 'RING II — Luar TCE & Umum',
    //                 'icon' => 'fa-globe',
    //                 'color' => 'teal',
    //                 'items' => [
    //                     ['label' => '🏘️ RT/RW di luar Perumahan TCE', 'value' => '792 penerima'],
    //                     ['label' => '🤲 Pesanggrahan (Bu Hadi) Titipan', 'value' => '40 orang'],
    //                     ['label' => '🧸 Panti Asuhan & Anak Yatim', 'value' => '46 anak'],
    //                     ['label' => '🎟️ Masyarakat Umum (tanpa kupon)', 'value' => '244 paket'],
    //                 ],
    //                 'total' => '1.122+ penerima'
    //             ]
    //         ];
    //         $grandTotalPenerima = 1252; // Default grand total
    //     }
        
    //     // Data Distribusi - siapkan dalam format yang mudah digunakan di Blade
    //     $distribusiData = $report->distribusi_formatted;

    //     // Buat array dengan key yang jelas
    //     $distribusi = [];
    //     foreach ($distribusiData as $item) {
    //         // Mapping label ke key yang konsisten
    //         $key = match($item['label']) {
    //             'Shohibul Qurban' => 'shohibul_qurban',
    //             'Masyarakat Sekitar' => 'masyarakat_sekitar',
    //             'Fakir Miskin & Dhuafa' => 'fakir_miskin_dhuafa',
    //             default => strtolower(str_replace(' ', '_', $item['label']))
    //         };
            
    //         $distribusi[$key] = [
    //             'label' => $item['label'],
    //             'value' => $item['value'],
    //             'icon' => $item['icon'] ?? 'fa-box-open',
    //             'percentage' => $item['percentage'],
    //             'color' => $item['color'] ?? ($key == 'shohibul_qurban' ? 'emerald' : ($key == 'masyarakat_sekitar' ? 'teal' : 'amber'))
    //         ];
    //     }

    //     // Jika kosong, gunakan default
    //     if (empty($distribusi)) {
    //         $distribusi = [
    //             'shohibul_qurban' => [
    //                 'label' => 'Shohibul Qurban', 'value' => 467, 'icon' => 'fa-star-of-life', 'percentage' => 33, 'color' => 'emerald'
    //             ],
    //             'masyarakat_sekitar' => [
    //                 'label' => 'Masyarakat Sekitar', 'value' => 467, 'icon' => 'fa-building', 'percentage' => 33, 'color' => 'teal'
    //             ],
    //             'fakir_miskin_dhuafa' => [
    //                 'label' => 'Fakir Miskin & Dhuafa', 'value' => 466, 'icon' => 'fa-hands-helping', 'percentage' => 34, 'color' => 'amber'
    //             ],
    //         ];
    //     }
        
    //     // Data Galeri
    //     $galleryImages = $report->gallery_images_formatted;
    //     $additionalImages = $report->additional_images_formatted;
        
    //     // Data Footer
    //     $footer = [
    //         'instagram' => $report->footer_instagram,
    //         'whatsapp' => $report->footer_whatsapp,
    //         'email' => $report->footer_email,
    //         'quote' => $report->footer_quote,
    //     ];
        
    //     // Data Thank You
    //     $thankyou = [
    //         'title' => $report->thankyou_title,
    //         'message' => $report->thankyou_message,
    //         'hadits' => $report->thankyou_hadits,
    //     ];
        
    //     // Data QR
    //     $qr = [
    //         'image' => $report->qr_image,
    //         'link' => $report->qr_link,
    //     ];
        
    //     return view('masjid.' . masjid() . '.guest.program-qurban.laporan', compact(
    //         'report',
    //         'availableYears',
    //         'heroData',
    //         'stats',
    //         'pelaksanaan',
    //         'dramatis',
    //         'pemotongan',
    //         'keuangan',
    //         'penerima',
    //         'distribusi',
    //         'galleryImages',
    //         'additionalImages',
    //         'footer',
    //         'thankyou',
    //         'qr',
    //         'grandTotalPenerima'
    //     ));
    // }

    // private function getDefaultReport()
    // {
    //     // Buat object stdClass dengan data default (sama seperti sebelumnya)
    //     $report = new \stdClass();
        
    //     $report->hero_title = 'Laporan Idul Adha';
    //     $report->hero_subtitle = '1447 H';
    //     $report->hero_badge = '✦ 1447 H · DZULHIJJAH 1447 ✦';
    //     $report->hero_masjid = 'Masjid Raudhatul Jannah TCE';
    //     $report->hero_tagline = '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2';
        
    //     $report->stat_hewan_sapi = 8;
    //     $report->stat_hewan_kambing = 41;
    //     $report->stat_paket_daging = 1400;
    //     $report->stat_mustahik = 1252;
    //     $report->stat_daging_kg = 1500;
    //     $report->stat_jamaah = '1.800+ Jamaah';
        
    //     $report->pelaksanaan_ketua_nama = 'Bapak Hamdi';
    //     $report->pelaksanaan_ketua_jabatan = 'Ketua Panitia';
    //     $report->pelaksanaan_masjid_nama = 'Masjid Raudhotul Jannah';
    //     $report->pelaksanaan_masjid_sub = 'Panitia Idul Adha 1447 H';
    //     $report->pelaksanaan_lokasi_sholat = 'Lapangan Tenis TCE';
    //     $report->pelaksanaan_lokasi_qurban = 'Masjid';
    //     $report->pelaksanaan_deskripsi = null;
    //     $report->pelaksanaan_gambar1 = null;
    //     $report->pelaksanaan_gambar2 = null;
    //     $report->pelaksanaan_gambar3 = null;
        
    //     $report->dramatis1_title = 'Takbir Menggema, Hati Bergetar';
    //     $report->dramatis1_quote = '"Ribuan jamaah memenuhi lapangan, suara takbir menggema. Inilah keindahan ukhuwah di pagi Idul Adha."';
    //     $report->dramatis1_stat = '1.800+ Jamaah';
    //     $report->dramatis1_image = null;
        
    //     $report->dramatis2_title = 'Keikhlasan yang Tersembelih';
    //     $report->dramatis2_quote = '"8 sapi dan 41 kambing disembelih dengan penuh ketulusan."';
    //     $report->dramatis2_stat = '49 ekor hewan qurban | 1.400+ paket daging';
    //     $report->dramatis2_image = null;
        
    //     $report->dramatis3_title = 'Senyum di Balik Paket Daging';
    //     $report->dramatis3_quote = '"Tak terlihat kemewahan, hanya bahagia dari wajah-wajah yang menerima."';
    //     $report->dramatis3_stat = '1.200+ keluarga merasakan kebahagiaan';
    //     $report->dramatis3_image = null;
        
    //     $report->pemotongan_sapi_berat_kg = 350;
    //     $report->pemotongan_kambing_berat_kg = 34;
        
    //     $report->keuangan_penerimaan = [
    //         ['label' => 'Uang qurban untuk 8 ekor sapi', 'amount' => 280000000],
    //         ['label' => 'Uang qurban untuk 31 ekor kambing', 'amount' => 62000000],
    //         ['label' => 'Uang pemotongan 3 ekor sapi (beli sendiri)', 'amount' => 1500000],
    //         ['label' => 'Uang pemotongan 9 ekor kambing (beli sendiri)', 'amount' => 900000],
    //         ['label' => 'Infaq sholat Ied', 'amount' => 18750000],
    //     ];
        
    //     $report->keuangan_pengeluaran = [
    //         ['label' => 'Pembayaran ke penjual sapi (8 ekor ~2.804 kg)', 'amount' => 280000000],
    //         ['label' => 'Pembayaran ke penjual kambing (31 ekor)', 'amount' => 62000000],
    //         ['label' => 'Biaya pemotongan hewan qurban', 'amount' => 4500000],
    //         ['label' => 'Penyewaan sound system sholat Ied', 'amount' => 3500000],
    //         ['label' => 'Biaya penyelenggaraan sholat Ied', 'amount' => 5500000],
    //         ['label' => 'Biaya survei pengadaan hewan qurban', 'amount' => 1200000],
    //         ['label' => 'Biaya spanduk, flyer, banner', 'amount' => 1850000],
    //         ['label' => 'Biaya spanduk kegiatan sholat Ied', 'amount' => 800000],
    //         ['label' => 'Biaya konsumsi', 'amount' => 3200000],
    //     ];
        
    //     $report->rings = [
    //         [
    //             'title' => 'RING I — Warga TCE & Perangkat',
    //             'icon' => 'fa-building',
    //             'color' => 'emerald',
    //             'items' => [
    //                 ['label' => '👥 Warga RT/RW Taman Cipulir Estate', 'value' => 'Seluruh warga'],
    //                 ['label' => '🛡️ Satpam & Tukang Taman TCE', 'value' => '10 orang'],
    //                 ['label' => '📚 Guru TPA, SD & SMP Al Madinah', 'value' => '23 orang'],
    //                 ['label' => '🕌 Perangkat Masjid & Panitia', 'value' => '40 orang'],
    //                 ['label' => '🥩 Tukang Jagal / Pemotong', 'value' => '57 orang'],
    //             ],
    //             'total' => '130+ penerima',
    //         ],
    //         [
    //             'title' => 'RING II — Luar TCE & Umum',
    //             'icon' => 'fa-globe',
    //             'color' => 'teal',
    //             'items' => [
    //                 ['label' => '🏘️ RT/RW di luar Perumahan TCE', 'value' => '792 penerima'],
    //                 ['label' => '🤲 Pesanggrahan (Bu Hadi) Titipan', 'value' => '40 orang'],
    //                 ['label' => '🧸 Panti Asuhan & Anak Yatim', 'value' => '46 anak'],
    //                 ['label' => '🎟️ Masyarakat Umum (tanpa kupon)', 'value' => '244 paket'],
    //             ],
    //             'total' => '1.122+ penerima',
    //         ],
    //     ];
        
    //     $report->distribusi = [
    //         ['label' => 'Shohibul Qurban', 'value' => 467, 'icon' => 'fa-star-of-life', 'percentage' => 33],
    //         ['label' => 'Masyarakat Sekitar', 'value' => 467, 'icon' => 'fa-building', 'percentage' => 33],
    //         ['label' => 'Fakir Miskin & Dhuafa', 'value' => 466, 'icon' => 'fa-hands-helping', 'percentage' => 34],
    //     ];
        
    //     $report->gallery_images = [
    //         ['url' => asset('images/qurban/gallery/photo1.jpg'), 'alt' => 'Suasana Sholat Ied', 'type' => 'landscape'],
    //         ['url' => asset('images/qurban/gallery/photo2.jpg'), 'alt' => 'Jamaah Sholat', 'type' => 'square'],
    //         ['url' => asset('images/qurban/gallery/photo3.jpg'), 'alt' => 'Khatib', 'type' => 'square'],
    //         ['url' => asset('images/qurban/gallery/photo4.jpg'), 'alt' => 'Hewan Qurban', 'type' => 'square'],
    //         ['url' => asset('images/qurban/gallery/photo5.jpg'), 'alt' => 'Pembagian Daging', 'type' => 'landscape'],
    //         ['url' => asset('images/qurban/gallery/photo6.jpg'), 'alt' => 'Penyembelihan', 'type' => 'square'],
    //         ['url' => asset('images/qurban/gallery/photo7.jpg'), 'alt' => 'Senyum Mustahik', 'type' => 'square'],
    //         ['url' => asset('images/qurban/gallery/photo8.jpg'), 'alt' => 'Suasana', 'type' => 'square'],
    //         ['url' => asset('images/qurban/gallery/photo9.jpg'), 'alt' => 'Kebersamaan', 'type' => 'landscape'],
    //         ['url' => asset('images/qurban/gallery/photo10.jpg'), 'alt' => 'Momen Haru', 'type' => 'square'],
    //     ];
        
    //     $report->additional_images = [
    //         asset('images/qurban/gallery/photo11.jpg'),
    //         asset('images/qurban/gallery/photo12.jpg'),
    //         asset('images/qurban/gallery/photo13.jpg'),
    //         asset('images/qurban/gallery/photo14.jpg'),
    //         asset('images/qurban/gallery/photo15.jpg'),
    //     ];
        
    //     $report->footer_instagram = 'https://instagram.com/raudhatuljannah';
    //     $report->footer_whatsapp = 'https://wa.me/6285716503815';
    //     $report->footer_email = 'info@raudhatuljannah.com';
    //     $report->footer_quote = '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162';
        
    //     $report->thankyou_title = 'Jazakallah Khairan';
    //     $report->thankyou_message = 'Semoga Allah menerima amal ibadah kita semua, melipatgandakan pahala, dan menjadikan qurban ini sebagai penebus dosa-dosa kita. Aamiin Ya Rabbal Alamin.';
    //     $report->thankyou_hadits = '"Barangsiapa yang bersedekah, maka ia akan mendapatkan pahala dari Allah." — HR. Bukhari Muslim';
        
    //     $report->qr_image = null;
    //     $report->qr_link = null;
        
    //     // Hitung total untuk helper
    //     $report->total_penerimaan = array_sum(array_column($report->keuangan_penerimaan, 'amount'));
    //     $report->total_pengeluaran = array_sum(array_column($report->keuangan_pengeluaran, 'amount'));
    //     $report->sisa_dana = $report->total_penerimaan - $report->total_pengeluaran;
        
    //     return $report;
    // }

    /**
     * Tampilkan halaman laporan qurban
     */
    public function laporan($tahun = null)
    {
        if ($tahun) {
            $report = QurbanReport::where('masjid_code', masjid())
                ->where('tahun_hijriah', $tahun)
                ->where('is_published', true)
                ->first();
        } else {
            $report = QurbanReport::where('masjid_code', masjid())
                ->where('is_active', true)
                ->where('is_published', true)
                ->first();
            
            if (!$report) {
                $report = QurbanReport::where('masjid_code', masjid())
                    ->where('is_published', true)
                    ->orderBy('tahun_hijriah', 'desc')
                    ->first();
            }
        }
        
        $isDefault = false;
        if (!$report) {
            $report = $this->getDefaultReport();
            $isDefault = true;
        }
        
        $availableYears = QurbanReport::where('masjid_code', masjid())
            ->where('is_published', true)
            ->orderBy('tahun_hijriah', 'desc')
            ->pluck('tahun_hijriah')
            ->toArray();
        
        // Hero Data dengan null coalescing
        $heroData = [
            'title' => $report->hero_title ?? 'Laporan Qurban',
            'subtitle' => $report->hero_subtitle ?? ($report->tahun_hijriah ?? '1447 H'),
            'badge' => isset($report->hero_badge) ? str_replace('{TAHUN}', $report->tahun_hijriah ?? '1447 H', $report->hero_badge) : '✦ Qurban ✦',
            'masjid' => $report->hero_masjid ?? 'Masjid',
            'tagline' => $report->hero_tagline ?? '',
        ];
        
        // Stats dengan null coalescing
        $stats = [
            'hewan' => [
                'sapi' => $report->stat_hewan_sapi ?? 0,
                'kambing' => $report->stat_hewan_kambing ?? 0,
                'total' => ($report->stat_hewan_sapi ?? 0) + ($report->stat_hewan_kambing ?? 0),
            ],
            'paket' => $report->stat_paket_daging ?? 0,
            'mustahik' => $report->stat_mustahik ?? 0,
            'daging_kg' => $report->stat_daging_kg ?? 0,
            'jamaah' => $report->stat_jamaah ?? '0 Jamaah',
        ];
        
        // Pelaksanaan dengan null coalescing
        $pelaksanaan = [
            'ketua_nama' => $report->pelaksanaan_ketua_nama ?? '',
            'ketua_jabatan' => $report->pelaksanaan_ketua_jabatan ?? '',
            'masjid_nama' => $report->pelaksanaan_masjid_nama ?? '',
            'masjid_sub' => $report->pelaksanaan_masjid_sub ?? '',
            'lokasi_sholat' => $report->pelaksanaan_lokasi_sholat ?? '',
            'lokasi_qurban' => $report->pelaksanaan_lokasi_qurban ?? '',
            'deskripsi' => $report->pelaksanaan_deskripsi ?? '',
            
            // Gambar 1
            'gambar1' => $report->pelaksanaan_gambar1,
            'caption1' => $report->pelaksanaan_caption1 ?? 'Sholat Idul Adha',
            
            // Gambar 2
            'gambar2' => $report->pelaksanaan_gambar2,
            'caption2' => $report->pelaksanaan_caption2 ?? 'Khotib Khatibah',
            
            // Gambar 3
            'gambar3' => $report->pelaksanaan_gambar3,
            'caption3' => $report->pelaksanaan_caption3 ?? 'Penyembelihan Qurban',
            
            // Gambar 4 (baru)
            'gambar4' => $report->pelaksanaan_gambar4,
            'caption4' => $report->pelaksanaan_caption4 ?? 'Distribusi Daging Qurban',
        ];
        
        // Dramatis dengan null coalescing
        $dramatis = [
            1 => [
                'title' => $report->dramatis1_title ?? '', 
                'quote' => $report->dramatis1_quote ?? '', 
                'stat' => $report->dramatis1_stat ?? '', 
                'image' => $report->dramatis1_image ?? null
            ],
            2 => [
                'title' => $report->dramatis2_title ?? '', 
                'quote' => $report->dramatis2_quote ?? '', 
                'stat' => $report->dramatis2_stat ?? '', 
                'image' => $report->dramatis2_image ?? null
            ],
            3 => [
                'title' => $report->dramatis3_title ?? '', 
                'quote' => $report->dramatis3_quote ?? '', 
                'stat' => $report->dramatis3_stat ?? '', 
                'image' => $report->dramatis3_image ?? null
            ],
        ];
        
        // Pemotongan dengan null coalescing
        $pemotongan = [
            'sapi_berat_kg' => $report->pemotongan_sapi_berat_kg,
            'kambing_berat_kg' => $report->pemotongan_kambing_berat_kg,
            'sapi_is_range' => str_contains($report->pemotongan_sapi_berat_kg, '-'),
            'kambing_is_range' => str_contains($report->pemotongan_kambing_berat_kg, '-'),
            'sapi_avg' => $report->sapi_berat_rata ?? null,
            'kambing_avg' => $report->kambing_berat_rata ?? null,
        ];
        
        // Keuangan (3 bagian) dengan null coalescing
        $keuangan = [
            'penerimaan_peserta' => $report->keuangan_penerimaan_peserta ?? [],
            'penerimaan_infaq' => $report->keuangan_penerimaan_infaq ?? [],
            'pengeluaran' => $report->keuangan_pengeluaran ?? [],
            'total_penerimaan_peserta' => $report->total_penerimaan_peserta ?? 0,
            'total_penerimaan_infaq' => $report->total_penerimaan_infaq ?? 0,
            'total_penerimaan' => $report->total_penerimaan ?? 0,
            'total_pengeluaran' => $report->total_pengeluaran ?? 0,
            'sisa_dana' => $report->sisa_dana ?? 0,
        ];
        
        // Penerima Manfaat (Rings) - dengan pengecekan method_exists atau properti
        $rings = [];
        $grandTotalPenerima = 0;
        $penerima = [];
        
        if (!$isDefault && method_exists($report, 'getRingsFormattedAttribute')) {
            $rings = $report->rings_formatted ?? [];
        } elseif (isset($report->rings)) {
            $rings = $report->rings;
        } else {
            $rings = $this->getDefaultRings();
        }
        
        foreach ($rings as $index => $ring) {
            $ringTotal = 0;
            if (isset($ring['items']) && is_array($ring['items'])) {
                foreach ($ring['items'] as $item) {
                    if (isset($item['value'])) {
                        $number = preg_replace('/[^0-9]/', '', $item['value']);
                        $ringTotal += (int)$number;
                    }
                }
            }
            $grandTotalPenerima += $ringTotal;
            
            $penerima[] = [
                'title' => $ring['title'] ?? 'Ring ' . ($index + 1),
                'icon' => $ring['icon'] ?? 'fa-building',
                'color' => $ring['color'] ?? 'emerald',
                'items' => $ring['items'] ?? [],
                'total' => $ringTotal > 0 ? number_format($ringTotal) . ' penerima' : ($ring['total'] ?? '0 penerima'),
            ];
        }
        
        // Distribusi - dengan pengecekan
        if (!$isDefault && method_exists($report, 'getDistribusiFormattedAttribute')) {
            $distribusi = $report->distribusi_formatted ?? [];
        } elseif (isset($report->distribusi)) {
            $distribusi = $report->distribusi;
        } else {
            $distribusi = $this->getDefaultDistribusi();
        }
        
        // Galeri - dengan pengecekan
        if (!$isDefault && method_exists($report, 'getGalleryImagesFormattedAttribute')) {
            $galleryImages = $report->gallery_images_formatted ?? [];
        } else {
            $galleryImages = $report->gallery_images ?? [];
        }
        
        if (!$isDefault && method_exists($report, 'getAdditionalImagesFormattedAttribute')) {
            $additionalImages = $report->additional_images_formatted ?? [];
        } else {
            $additionalImages = $report->additional_images ?? [];
        }
        
        // Footer dengan null coalescing
        $footer = [
            'instagram' => $report->footer_instagram ?? '',
            'whatsapp' => $report->footer_whatsapp ?? '',
            'email' => $report->footer_email ?? '',
            'quote' => $report->footer_quote ?? '',
        ];
        
        // Thank You dengan null coalescing
        $thankyou = [
            'title' => $report->thankyou_title ?? 'Jazakallah Khairan',
            'message' => $report->thankyou_message ?? 'Terima kasih atas partisipasinya',
            'hadits' => $report->thankyou_hadits ?? '',
        ];
        
        // QR dengan null coalescing
        $qr = [
            'image' => $report->qr_image ?? null,
            'link' => $report->qr_link ?? '',
        ];
        
        return view('masjid.' . masjid() . '.guest.program-qurban.laporan', compact(
            'report', 'availableYears', 'heroData', 'stats', 'pelaksanaan',
            'dramatis', 'pemotongan', 'keuangan', 'penerima', 'distribusi',
            'galleryImages', 'additionalImages', 'footer', 'thankyou', 'qr', 'grandTotalPenerima'
        ));
    }

    /**
     * Default rings jika tidak tersedia
     */
    private function getDefaultRings()
    {
        return [
            [
                'title' => 'RING I — Warga TCE & Perangkat',
                'icon' => 'fa-building',
                'color' => 'emerald',
                'items' => [
                    ['label' => '👥 Warga RT/RW Taman Cipulir Estate', 'value' => 'Seluruh warga'],
                    ['label' => '🛡️ Satpam & Tukang Taman TCE', 'value' => '10 orang'],
                    ['label' => '📚 Guru TPA, SD & SMP Al Madinah', 'value' => '23 orang'],
                    ['label' => '🕌 Perangkat Masjid & Panitia', 'value' => '40 orang'],
                    ['label' => '🥩 Tukang Jagal / Pemotong', 'value' => '57 orang'],
                ],
                'total' => '130+ penerima'
            ],
            [
                'title' => 'RING II — Luar TCE & Umum',
                'icon' => 'fa-globe',
                'color' => 'teal',
                'items' => [
                    ['label' => '🏘️ RT/RW di luar Perumahan TCE', 'value' => '792 penerima'],
                    ['label' => '🤲 Pesanggrahan (Bu Hadi) Titipan', 'value' => '40 orang'],
                    ['label' => '🧸 Panti Asuhan & Anak Yatim', 'value' => '46 anak'],
                    ['label' => '🎟️ Masyarakat Umum (tanpa kupon)', 'value' => '244 paket'],
                ],
                'total' => '1.122+ penerima'
            ]
        ];
    }

    /**
     * Default distribusi jika tidak tersedia
     */
    private function getDefaultDistribusi()
    {
        return [
            ['label' => 'Shohibul Qurban', 'value' => 467, 'icon' => 'fa-star-of-life', 'percentage' => 33, 'color' => 'emerald'],
            ['label' => 'Masyarakat Sekitar', 'value' => 467, 'icon' => 'fa-building', 'percentage' => 33, 'color' => 'teal'],
            ['label' => 'Fakir Miskin & Dhuafa', 'value' => 466, 'icon' => 'fa-hands-helping', 'percentage' => 34, 'color' => 'amber'],
        ];
    }

    /**
     * Default report jika belum ada data
     */
    private function getDefaultReport()
    {
        $report = new \stdClass();
        $report->tahun_hijriah = '1447 H';
        $report->tahun_masehi = '2026';
        $report->hero_title = 'Laporan Idul Adha';
        $report->hero_subtitle = '1447 H';
        $report->hero_badge = '✦ 1447 H · DZULHIJJAH 1447 ✦';
        $report->hero_masjid = 'Masjid Raudhatul Jannah TCE';
        $report->hero_tagline = '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" — QS. Al-Kautsar: 2';
        $report->stat_hewan_sapi = 8;
        $report->stat_hewan_kambing = 41;
        $report->stat_paket_daging = 1400;
        $report->stat_mustahik = 1252;
        $report->stat_daging_kg = 1500;
        $report->stat_jamaah = '1.800+ Jamaah';
        $report->pelaksanaan_ketua_nama = 'Bapak Hamdi';
        $report->pelaksanaan_ketua_jabatan = 'Ketua Panitia';
        $report->pelaksanaan_masjid_nama = 'Masjid Raudhotul Jannah';
        $report->pelaksanaan_masjid_sub = 'Panitia Idul Adha 1447 H';
        $report->pelaksanaan_lokasi_sholat = 'Lapangan Tenis TCE';
        $report->pelaksanaan_lokasi_qurban = 'Masjid';
        $report->dramatis1_title = 'Takbir Menggema, Hati Bergetar';
        $report->dramatis1_quote = '"Ribuan jamaah memenuhi lapangan, suara takbir menggema. Inilah keindahan ukhuwah di pagi Idul Adha."';
        $report->dramatis1_stat = '1.800+ Jamaah';
        $report->dramatis2_title = 'Keikhlasan yang Tersembelih';
        $report->dramatis2_quote = '"8 sapi dan 41 kambing disembelih dengan penuh ketulusan."';
        $report->dramatis2_stat = '49 ekor hewan qurban | 1.400+ paket daging';
        $report->dramatis3_title = 'Senyum di Balik Paket Daging';
        $report->dramatis3_quote = '"Tak terlihat kemewahan, hanya bahagia dari wajah-wajah yang menerima."';
        $report->dramatis3_stat = '1.200+ keluarga merasakan kebahagiaan';
        $report->pemotongan_sapi_berat_kg = 350;
        $report->pemotongan_kambing_berat_kg = 34;
        
        // Default Keuangan
        $report->keuangan_penerimaan_peserta = [
            ['label' => 'Uang qurban untuk 8 ekor sapi', 'amount' => 280000000],
            ['label' => 'Uang qurban untuk 31 ekor kambing', 'amount' => 62000000],
            ['label' => 'Uang pemotongan 3 ekor sapi (beli sendiri)', 'amount' => 1500000],
            ['label' => 'Uang pemotongan 9 ekor kambing (beli sendiri)', 'amount' => 900000],
        ];
        $report->keuangan_penerimaan_infaq = [
            ['label' => 'Infaq sholat Ied', 'amount' => 18750000],
        ];
        $report->keuangan_pengeluaran = [
            ['label' => 'Pembayaran ke penjual sapi (8 ekor ~2.804 kg)', 'amount' => 280000000],
            ['label' => 'Pembayaran ke penjual kambing (31 ekor)', 'amount' => 62000000],
            ['label' => 'Biaya pemotongan hewan qurban', 'amount' => 4500000],
            ['label' => 'Penyewaan sound system sholat Ied', 'amount' => 3500000],
            ['label' => 'Biaya penyelenggaraan sholat Ied', 'amount' => 5500000],
            ['label' => 'Biaya survei pengadaan hewan qurban', 'amount' => 1200000],
            ['label' => 'Biaya spanduk, flyer, banner', 'amount' => 1850000],
            ['label' => 'Biaya spanduk kegiatan sholat Ied', 'amount' => 800000],
            ['label' => 'Biaya konsumsi', 'amount' => 3200000],
        ];
        
        // Default Rings
        $report->rings = $this->getDefaultRings();
        
        // Default Distribusi
        $report->distribusi = $this->getDefaultDistribusi();
        
        $report->gallery_images = [];
        $report->additional_images = [];
        
        $report->footer_instagram = 'https://instagram.com/raudhatuljannah';
        $report->footer_whatsapp = 'https://wa.me/6285716503815';
        $report->footer_email = 'info@raudhatuljannah.com';
        $report->footer_quote = '"Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam." — QS. Al-An\'am: 162';
        
        $report->thankyou_title = 'Jazakallah Khairan';
        $report->thankyou_message = 'Semoga Allah menerima amal ibadah kita semua, melipatgandakan pahala, dan menjadikan qurban ini sebagai penebus dosa-dosa kita. Aamiin Ya Rabbal Alamin.';
        $report->thankyou_hadits = '"Barangsiapa yang bersedekah, maka ia akan mendapatkan pahala dari Allah." — HR. Bukhari Muslim';
        
        $report->total_penerimaan_peserta = array_sum(array_column($report->keuangan_penerimaan_peserta, 'amount'));
        $report->total_penerimaan_infaq = array_sum(array_column($report->keuangan_penerimaan_infaq, 'amount'));
        $report->total_penerimaan = $report->total_penerimaan_peserta + $report->total_penerimaan_infaq;
        $report->total_pengeluaran = array_sum(array_column($report->keuangan_pengeluaran, 'amount'));
        $report->sisa_dana = $report->total_penerimaan - $report->total_pengeluaran;
        
        return $report;
    }
}
