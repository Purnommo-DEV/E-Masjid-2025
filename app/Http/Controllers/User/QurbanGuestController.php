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
        
        return view('masjid.' . masjid() . '.guest.program-qurban.thankyou', compact('registration'));
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
}