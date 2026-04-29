<?php
// app/Http/Controllers/Admin/Qurban/QurbanSettingController.php

namespace App\Http\Controllers\Admin\Qurban;

use App\Http\Controllers\Controller;
use App\Interfaces\QurbanSettingRepositoryInterface;
use App\Models\QurbanSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QurbanSettingController extends Controller
{
    protected $settingRepo;

    public function __construct(QurbanSettingRepositoryInterface $settingRepo)
    {
        $this->settingRepo = $settingRepo;
    }

    public function index()
    {
        // Data untuk Tampilan
        $showQurbanOnHome = $this->settingRepo->get('show_qurban_on_home', true);
        
        // Data untuk Home Section
        $homeQurbanBadge = $this->settingRepo->get('home_qurban_badge', '✨ HARI RAYA IDUL ADHA 1447 H / 27 MEI 2026 ✨');
        $homeQurbanTitleLine1 = $this->settingRepo->get('home_qurban_title_line1', 'Raih Kemuliaan');
        $homeQurbanTitleLine2 = $this->settingRepo->get('home_qurban_title_line2', 'Ibadah Qurban');
        $homeQurbanSubtitle = $this->settingRepo->get('home_qurban_subtitle', '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah!" (QS. Al-Kautsar: 2)');
        $homeQurbanBenefits = $this->settingRepo->get('home_qurban_benefits', [
            'Mendekatkan diri kepada Allah',
            'Berbagi kebahagiaan',
            'Amal yang paling mulia'
        ]);
        $homeQurbanBtnDaftarText = $this->settingRepo->get('home_qurban_btn_daftar_text', 'Daftar Qurban');
        $homeQurbanBtnInfoText = $this->settingRepo->get('home_qurban_btn_info_text', 'Info Lengkap Qurban');
        $homeQurbanTglPendaftaran = $this->settingRepo->get('home_qurban_tgl_pendaftaran', '1 Apr - 20 Mei 2026');
        $homeQurbanTglPelaksanaan = $this->settingRepo->get('home_qurban_tgl_pelaksanaan', '27 Mei 2026');
        $homeQurbanTglHijriah = $this->settingRepo->get('home_qurban_tgl_hijriah', '10 Dzulhijjah 1447 H');
        $homeQurbanHargaMulai = $this->settingRepo->get('home_qurban_harga_mulai', 'Rp 3.000.000,-');
        $homeQurbanLinkDaftar = $this->settingRepo->get('home_qurban_link_daftar', '/qurban#form-pendaftaran');
        $homeQurbanLinkInfo = $this->settingRepo->get('home_qurban_link_info', '/qurban#info-qurban');
        $homeQurbanImage = $this->settingRepo->get('home_qurban_image', 'storage/qurban-hewan.png');
        
        // Background Gradient
        $homeQurbanBgStart = $this->settingRepo->get('home_qurban_bg_start', 'from-emerald-900');
        $homeQurbanBgMid = $this->settingRepo->get('home_qurban_bg_mid', 'via-emerald-800');
        $homeQurbanBgEnd = $this->settingRepo->get('home_qurban_bg_end', 'to-emerald-900');
        
        // Data lain yang sudah ada sebelumnya
        $groupedSettings = $this->settingRepo->getGroupedSettings();
        $settings = $this->settingRepo->all();
        
        $heroTitle = $this->settingRepo->get('hero_title', 'Masjid Raudhotul Jannah');
        $heroSubtitle = $this->settingRepo->get('hero_subtitle', 'Menerima & Menyalurkan Hewan Qurban');
        $heroBadge = $this->settingRepo->get('hero_badge_text', 'PANITIA IDUL ADHA 1447 H / 2026 M');
        
        $contactInfoName = $this->settingRepo->get('contact_info_name', 'Bapak Joko');
        $contactInfoPhone = $this->settingRepo->get('contact_info_phone', '085716503815');
        $contactConfirmName = $this->settingRepo->get('contact_confirmation_name', 'Bapak Jazuli');
        $contactConfirmPhone = $this->settingRepo->get('contact_confirmation_phone', '081310185948');
        
        $bankName = $this->settingRepo->get('bank_name', 'BCA');
        $bankAccount = $this->settingRepo->get('bank_account_number', '1010010947479');
        $bankAccountName = $this->settingRepo->get('bank_account_name', 'JAZULI');
        
        $statsHewan = $this->settingRepo->get('stats_hewan_tersedia', '50+');
        $statsLokasi = $this->settingRepo->get('stats_lokasi_distribusi', 'TCE');
        $statsPenerima = $this->settingRepo->get('stats_penerima_manfaat', '500+');
        $statsTersalurkan = $this->settingRepo->get('stats_tersalurkan', '100%');
        
        $potongSapi = $this->settingRepo->get('potong_sapi_harga', '1800000');
        $potongKambing = $this->settingRepo->get('potong_kambing_harga', '300000');
        
        $importantNotes = $this->settingRepo->get('important_notes', []);
        $faqItems = $this->settingRepo->get('faq_items', []);
        
        return view('masjid.' . masjid() . '.admin.qurban.settings', compact(
            'groupedSettings', 'settings',
            'heroTitle', 'heroSubtitle', 'heroBadge',
            'contactInfoName', 'contactInfoPhone', 'contactConfirmName', 'contactConfirmPhone',
            'bankName', 'bankAccount', 'bankAccountName',
            'statsHewan', 'statsLokasi', 'statsPenerima', 'statsTersalurkan',
            'potongSapi', 'potongKambing',
            'importantNotes', 'faqItems',
            'showQurbanOnHome',
            'homeQurbanBadge', 'homeQurbanTitleLine1', 'homeQurbanTitleLine2',
            'homeQurbanSubtitle', 'homeQurbanBenefits',
            'homeQurbanBtnDaftarText', 'homeQurbanBtnInfoText',
            'homeQurbanTglPendaftaran', 'homeQurbanTglPelaksanaan', 'homeQurbanTglHijriah',
            'homeQurbanHargaMulai', 'homeQurbanLinkDaftar', 'homeQurbanLinkInfo',
            'homeQurbanImage', 'homeQurbanBgStart', 'homeQurbanBgMid', 'homeQurbanBgEnd'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            // Hero Section
            'hero_title' => 'nullable|string|max:255',
            'hero_subtitle' => 'nullable|string|max:255',
            'hero_badge_text' => 'nullable|string|max:255',
            
            // Kontak
            'contact_info_name' => 'nullable|string|max:255',
            'contact_info_phone' => 'nullable|string|max:20',
            'contact_confirmation_name' => 'nullable|string|max:255',
            'contact_confirmation_phone' => 'nullable|string|max:20',
            
            // Bank
            'bank_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
            'bank_account_name' => 'nullable|string|max:255',
            
            // Statistik
            'stats_hewan_tersedia' => 'nullable|string|max:50',
            'stats_lokasi_distribusi' => 'nullable|string|max:100',
            'stats_penerima_manfaat' => 'nullable|string|max:50',
            'stats_tersalurkan' => 'nullable|string|max:50',
            
            // Harga Potong
            'potong_sapi_harga' => 'nullable|numeric|min:0',
            'potong_kambing_harga' => 'nullable|numeric|min:0',
            
            // Catatan Penting
            'important_notes' => 'nullable|array',
            'important_notes.*' => 'nullable|string',
            
            // FAQ
            'faq_questions' => 'nullable|array',
            'faq_questions.*' => 'nullable|string',
            'faq_answers' => 'nullable|array',
            'faq_answers.*' => 'nullable|string',
            
            // Tampilan Home
            'show_qurban_on_home' => 'nullable',
            
            // Konten Home
            'home_qurban_badge' => 'nullable|string',
            'home_qurban_title_line1' => 'nullable|string',
            'home_qurban_title_line2' => 'nullable|string',
            'home_qurban_subtitle' => 'nullable|string',
            'home_qurban_benefits' => 'nullable|array',
            'home_qurban_benefits.*' => 'nullable|string',
            'home_qurban_btn_daftar_text' => 'nullable|string',
            'home_qurban_btn_info_text' => 'nullable|string',
            'home_qurban_tgl_pendaftaran' => 'nullable|string',
            'home_qurban_tgl_pelaksanaan' => 'nullable|string',
            'home_qurban_tgl_hijriah' => 'nullable|string',
            'home_qurban_harga_mulai' => 'nullable|string',
            'home_qurban_link_daftar' => 'nullable|string',
            'home_qurban_link_info' => 'nullable|string',
            'home_qurban_bg_start' => 'nullable|string',
            'home_qurban_bg_mid' => 'nullable|string',
            'home_qurban_bg_end' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // Update Tampilan Home
            $this->settingRepo->set('show_qurban_on_home', $request->boolean('show_qurban_on_home'), 'boolean');
            
            // Update Konten Home
            $this->settingRepo->set('home_qurban_badge', $validated['home_qurban_badge'] ?? '');
            $this->settingRepo->set('home_qurban_title_line1', $validated['home_qurban_title_line1'] ?? '');
            $this->settingRepo->set('home_qurban_title_line2', $validated['home_qurban_title_line2'] ?? '');
            $this->settingRepo->set('home_qurban_subtitle', $validated['home_qurban_subtitle'] ?? '');
            
            if (isset($validated['home_qurban_benefits'])) {
                $benefits = array_filter($validated['home_qurban_benefits']);
                $this->settingRepo->set('home_qurban_benefits', array_values($benefits), 'json');
            }
            
            $this->settingRepo->set('home_qurban_btn_daftar_text', $validated['home_qurban_btn_daftar_text'] ?? '');
            $this->settingRepo->set('home_qurban_btn_info_text', $validated['home_qurban_btn_info_text'] ?? '');
            $this->settingRepo->set('home_qurban_tgl_pendaftaran', $validated['home_qurban_tgl_pendaftaran'] ?? '');
            $this->settingRepo->set('home_qurban_tgl_pelaksanaan', $validated['home_qurban_tgl_pelaksanaan'] ?? '');
            $this->settingRepo->set('home_qurban_tgl_hijriah', $validated['home_qurban_tgl_hijriah'] ?? '');
            $this->settingRepo->set('home_qurban_harga_mulai', $validated['home_qurban_harga_mulai'] ?? '');
            $this->settingRepo->set('home_qurban_link_daftar', $validated['home_qurban_link_daftar'] ?? '');
            $this->settingRepo->set('home_qurban_link_info', $validated['home_qurban_link_info'] ?? '');
            $this->settingRepo->set('home_qurban_bg_start', $validated['home_qurban_bg_start'] ?? 'from-emerald-900');
            $this->settingRepo->set('home_qurban_bg_mid', $validated['home_qurban_bg_mid'] ?? 'via-emerald-800');
            $this->settingRepo->set('home_qurban_bg_end', $validated['home_qurban_bg_end'] ?? 'to-emerald-900');
            
            // Upload Gambar
            if ($request->hasFile('home_qurban_image')) {
                $oldImage = $this->settingRepo->get('home_qurban_image');
                $this->settingRepo->uploadImage('home_qurban_image', $request->file('home_qurban_image'), $oldImage);
            }
            
            // Update Hero Section
            $this->settingRepo->set('hero_title', $validated['hero_title'] ?? '');
            $this->settingRepo->set('hero_subtitle', $validated['hero_subtitle'] ?? '');
            $this->settingRepo->set('hero_badge_text', $validated['hero_badge_text'] ?? '');
            
            // Update Kontak
            $this->settingRepo->set('contact_info_name', $validated['contact_info_name'] ?? '');
            $this->settingRepo->set('contact_info_phone', $validated['contact_info_phone'] ?? '');
            $this->settingRepo->set('contact_confirmation_name', $validated['contact_confirmation_name'] ?? '');
            $this->settingRepo->set('contact_confirmation_phone', $validated['contact_confirmation_phone'] ?? '');
            
            // Update Bank
            $this->settingRepo->set('bank_name', $validated['bank_name'] ?? '');
            $this->settingRepo->set('bank_account_number', $validated['bank_account_number'] ?? '');
            $this->settingRepo->set('bank_account_name', $validated['bank_account_name'] ?? '');
            
            // Update Statistik
            $this->settingRepo->set('stats_hewan_tersedia', $validated['stats_hewan_tersedia'] ?? '');
            $this->settingRepo->set('stats_lokasi_distribusi', $validated['stats_lokasi_distribusi'] ?? '');
            $this->settingRepo->set('stats_penerima_manfaat', $validated['stats_penerima_manfaat'] ?? '');
            $this->settingRepo->set('stats_tersalurkan', $validated['stats_tersalurkan'] ?? '');
            
            // Update Harga Potong
            $this->settingRepo->set('potong_sapi_harga', $validated['potong_sapi_harga'] ?? '0');
            $this->settingRepo->set('potong_kambing_harga', $validated['potong_kambing_harga'] ?? '0');
            
            // Update Catatan Penting
            if (isset($validated['important_notes'])) {
                $importantNotes = array_filter($validated['important_notes']);
                $this->settingRepo->set('important_notes', array_values($importantNotes), 'json');
            }
            
            // Update FAQ
            if (isset($validated['faq_questions']) && isset($validated['faq_answers'])) {
                $faqItems = [];
                foreach ($validated['faq_questions'] as $index => $question) {
                    if (!empty($question) && !empty($validated['faq_answers'][$index])) {
                        $faqItems[] = [
                            'question' => $question,
                            'answer' => $validated['faq_answers'][$index],
                        ];
                    }
                }
                $this->settingRepo->set('faq_items', $faqItems, 'json');
            }
            
            DB::commit();
            $this->settingRepo->clearCache();
            
            return response()->json([
                'success' => true,
                'message' => 'Pengaturan Qurban berhasil disimpan!'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset ke pengaturan default
     */
    public function reset()
    {
        try {
            DB::beginTransaction();

            // Hapus semua settings untuk masjid ini
            QurbanSetting::where('masjid_code', masjid())->delete();

            // Data default
            $defaultSettings = [
                // Hero Section
                ['key' => 'hero_title', 'value' => 'Masjid Raudhotul Jannah', 'type' => 'text', 'label' => 'Hero Title', 'urutan' => 1],
                ['key' => 'hero_subtitle', 'value' => 'Menerima & Menyalurkan Hewan Qurban', 'type' => 'text', 'label' => 'Hero Subtitle', 'urutan' => 2],
                ['key' => 'hero_badge_text', 'value' => 'PANITIA IDUL ADHA 1447 H / 2026 M', 'type' => 'text', 'label' => 'Badge Teks', 'urutan' => 3],
                
                // Kontak
                ['key' => 'contact_info_name', 'value' => 'Bapak Joko', 'type' => 'text', 'label' => 'Nama Kontak Informasi', 'urutan' => 4],
                ['key' => 'contact_info_phone', 'value' => '085716503815', 'type' => 'text', 'label' => 'No WA Informasi', 'urutan' => 5],
                ['key' => 'contact_confirmation_name', 'value' => 'Bapak Jazuli', 'type' => 'text', 'label' => 'Nama Kontak Konfirmasi', 'urutan' => 6],
                ['key' => 'contact_confirmation_phone', 'value' => '081310185948', 'type' => 'text', 'label' => 'No WA Konfirmasi', 'urutan' => 7],
                
                // Bank
                ['key' => 'bank_name', 'value' => 'BCA', 'type' => 'text', 'label' => 'Nama Bank', 'urutan' => 8],
                ['key' => 'bank_account_number', 'value' => '1010010947479', 'type' => 'text', 'label' => 'Nomor Rekening', 'urutan' => 9],
                ['key' => 'bank_account_name', 'value' => 'JAZULI', 'type' => 'text', 'label' => 'Atas Nama', 'urutan' => 10],
                
                // Statistik
                ['key' => 'stats_hewan_tersedia', 'value' => '50+', 'type' => 'text', 'label' => 'Hewan Tersedia', 'urutan' => 11],
                ['key' => 'stats_lokasi_distribusi', 'value' => 'TCE', 'type' => 'text', 'label' => 'Lokasi Distribusi', 'urutan' => 12],
                ['key' => 'stats_penerima_manfaat', 'value' => '500+', 'type' => 'text', 'label' => 'Penerima Manfaat', 'urutan' => 13],
                ['key' => 'stats_tersalurkan', 'value' => '100%', 'type' => 'text', 'label' => 'Tersalurkan', 'urutan' => 14],
                
                // Harga Potong
                ['key' => 'potong_sapi_harga', 'value' => '1800000', 'type' => 'number', 'label' => 'Biaya Potong Sapi', 'urutan' => 15],
                ['key' => 'potong_kambing_harga', 'value' => '300000', 'type' => 'number', 'label' => 'Biaya Potong Kambing', 'urutan' => 16],
                
                // Catatan Penting
                ['key' => 'important_notes', 'value' => json_encode([
                    'Pendaftaran paling lambat H-2 sebelum Idul Adha (8 Dzulhijjah)',
                    'Penyerahan hewan sendiri: H-1 sebelum hari pemotongan',
                    'Jika patungan 1 ekor sapi tidak mencapai 7 orang, akan dialihkan ke qurban kambing & membayar biaya potong dan distribusi Rp150.000',
                    'Harga sudah termasuk biaya potong dan distribusi untuk paket resmi panitia',
                ]), 'type' => 'json', 'label' => 'Catatan Penting', 'urutan' => 17],
                
                // FAQ
                ['key' => 'faq_items', 'value' => json_encode([
                    ['question' => 'Bolehkah qurban untuk orang yang sudah meninggal?', 'answer' => 'Boleh, asalkan diniatkan untuk mereka yang telah wafat.'],
                    ['question' => 'Bagaimana cara pembayaran qurban?', 'answer' => 'Transfer ke rekening BCA 1010010947479 a.n. JAZULI, lalu konfirmasi ke Bapak Jazuli.'],
                    ['question' => 'Apakah bisa memilih lokasi distribusi?', 'answer' => 'Distribusi difokuskan ke Taman Cipulir Estate dan sekitarnya.'],
                    ['question' => 'Apa yang terjadi jika patungan sapi tidak sampai 7 orang?', 'answer' => 'Akan dialihkan ke qurban kambing dengan biaya tambahan potong Rp150.000.'],
                ]), 'type' => 'json', 'label' => 'FAQ', 'urutan' => 18],
            ];
            
            foreach ($defaultSettings as $setting) {
                QurbanSetting::create([
                    'masjid_code' => masjid(),
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'label' => $setting['label'],
                    'urutan' => $setting['urutan'],
                ]);
            }

            DB::commit();

            // Clear cache
            $this->settingRepo->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan berhasil direset ke default!'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal reset: ' . $e->getMessage()
            ], 500);
        }
    }
}