<?php

use App\Http\Controllers\Admin\AcaraController;
use App\Http\Controllers\Admin\AkunKeuanganController;
use App\Http\Controllers\Admin\AlokasiDanaController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BeritaController;
use App\Http\Controllers\Admin\DanaAlokasiController;
use App\Http\Controllers\Admin\DanaTerikatController;
use App\Http\Controllers\Admin\DanaTerikatReferensiController;
use App\Http\Controllers\Admin\EvaluasiQurbanController;
use App\Http\Controllers\Admin\GaleriController;
use App\Http\Controllers\Admin\JurnalController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\KhutbahJumatController;
use App\Http\Controllers\Admin\KotakInfakController;
use App\Http\Controllers\Admin\LayananController;
use App\Http\Controllers\Admin\PenerimaanPemasukanController;
use App\Http\Controllers\Admin\PengeluaranController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\PettyCashController;
use App\Http\Controllers\Admin\ProfilMasjidController;
use App\Http\Controllers\Admin\QuoteHarianController;
use App\Http\Controllers\Admin\Qurban\QurbanGalleryController;
use App\Http\Controllers\Admin\Qurban\QurbanPaketController;
use App\Http\Controllers\Admin\Qurban\QurbanRegistrasiController;
use App\Http\Controllers\Admin\Qurban\QurbanReportController;
use App\Http\Controllers\Admin\Qurban\QurbanSettingController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SaldoAwalController;
use App\Http\Controllers\Admin\SeoPageController;

use App\Http\Controllers\Admin\SlideMotivasiController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ZakatController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FinancialV2\FinancialControlController;
use App\Http\Controllers\FinancialV2\FinancialMasterDataController;
use App\Http\Controllers\FinancialV2\FinancialOpeningBalanceController;
use App\Http\Controllers\FinancialV2\FinancialReportController;
use App\Http\Controllers\FinancialV2\HistoricalFundHistoryController;
use App\Http\Controllers\FinancialV2\OperationalFinancialController;
use App\Http\Controllers\FinancialV2\PublicZiswafReportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\User\AcaraGuestController;
use App\Http\Controllers\User\BeritaGuestController;
use App\Http\Controllers\User\DokumentasiEvaluasiController;
use App\Http\Controllers\User\EvaluasiQurbanGuestController;
use App\Http\Controllers\User\ExcelYatimDhuafaController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\JamaahGuestController;
use App\Http\Controllers\User\KesehatanGuestController;
use App\Http\Controllers\User\PendaftaranYatimDhuafaController;
use App\Http\Controllers\User\ProgramRamadhanGuestController;
use App\Http\Controllers\User\QurbanGuestController;
use Illuminate\Support\Facades\Route;

Route::get('/pwa-splash', function () {
    return view('pwa.splash');
})->name('pwa.splash');

// ⚠️  SECURITY: Route /clear-cache, /run-migrate, /run-seeder telah dihapus dari akses publik.
// Gunakan Artisan CLI: php artisan cache:clear | migrate --force | db:seed --force
// Jika harus via web, lihat group "SuperAdmin Operations" di bawah (dilindungi auth + signed + role).

Route::get('/manifest.json', function () {
    $kode = masjid(); // dari helper kamu

    $config = config("masjids.{$kode}", config('masjids.default'));

    return response()->json([
        'name' => $config['name'],
        'short_name' => $config['short_name'],
        'description' => $config['jargon'].' – Masjid Era Digital',
        // 'start_url'     => '/pwa-splash',
        'display' => 'standalone',
        'background_color' => $config['gradient_from'],
        'theme_color' => $config['primary_color'],
        'orientation' => 'portrait-primary',
        'icons' => [
            [
                'src' => '/pwa/mrj-logo.png',
                'sizes' => '92x92',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src' => '/pwa/mrj-logo.png',
                'sizes' => '92x92',
                'type' => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

Route::get('/', [HomeController::class, 'index'])->name('home');

// Landing page publik untuk informasi dan permintaan sewa Aula Masjid.
// Tidak terhubung ke alur pemesanan atau data operasional yang sudah ada.
Route::get('/aula', function () {
    return view('masjid.'.masjid().'.guest.aula.index');
})->name('aula.index');

Route::prefix('program-ramadhan')->name('program-ramadhan.')->group(function () {
    Route::get('/', [ProgramRamadhanGuestController::class, 'index'])->name('index');
    Route::get('/{slug}', [ProgramRamadhanGuestController::class, 'show'])->name('show');
});

// Route publik - sederhana
Route::get('/form-jamaah', [JamaahGuestController::class, 'indexPublik'])->name('jamaah.form');
Route::post('/form-jamaah/store', [JamaahGuestController::class, 'store'])->name('jamaah.store');

// ==================== ROUTE GUEST (USER) ====================
Route::get('qurban/', [QurbanGuestController::class, 'index'])->name('qurban.index');
Route::get('qurban/1446h', [QurbanGuestController::class, 'evaluasi'])->name('qurban.evaluasi');
Route::post('qurban/register', [QurbanGuestController::class, 'register'])->name('qurban.register.store');
Route::get('qurban/thankyou/{kode?}', [QurbanGuestController::class, 'thankyou'])->name('qurban.thankyou');
Route::get('qurban/check-stock', [QurbanGuestController::class, 'checkStock'])->name('qurban.check.stock');
Route::get('qurban/paket/{id}/detail', [QurbanGuestController::class, 'getPaketDetail'])->name('qurban.paket.detail');

Route::get('qurban/laporan/{tahun?}', [QurbanGuestController::class, 'laporan'])->name('qurban.laporan');

Route::get('/evaluasi-qurban', [EvaluasiQurbanGuestController::class, 'index'])->name('evaluasi-qurban.guest.index');
Route::post('/evaluasi-qurban', [EvaluasiQurbanGuestController::class, 'store'])->name('evaluasi-qurban.guest.store');

Route::get('/dokumentasi-evaluasi/{tahun?}', [DokumentasiEvaluasiController::class, 'index'])->name('guest.dokumentasi-evaluasi');

// ==================== ROUTE PUBLIK (LIHAT DATA) ====================
Route::prefix('guest')->group(function () {
    Route::get('/evaluasi-qurban', [EvaluasiQurbanGuestController::class, 'evaluasi'])->name('guest.evaluasi-qurban.index');
    Route::get('/evaluasi-qurban/data', [EvaluasiQurbanGuestController::class, 'data'])->name('guest.evaluasi-qurban.data');
    Route::get('/evaluasi-qurban/resumen-data', [EvaluasiQurbanGuestController::class, 'resumenData'])->name('guest.evaluasi-qurban.resumen-data');
    Route::get('/evaluasi-qurban/{id}', [EvaluasiQurbanGuestController::class, 'show'])->name('guest.evaluasi-qurban.show');
});

Route::get('/test-gemini', function () {
    $apiKey = env('GEMINI_API_KEY');
    $model = env('GEMINI_DEFAULT_MODEL', 'models/gemini-2.0-flash');
    $url = "https://generativelanguage.googleapis.com/v1beta/{$model}:generateContent?key={$apiKey}";

    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => 'Sebutkan 3 keutamaan ibadah qurban dalam bahasa Indonesia!'],
                ],
            ],
        ],
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return response()->json([
        'model' => $model,
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
    ]);
});

Route::get('/test-deepseek', function () {
    $apiKey = env('DEEPSEEK_API_KEY');

    $data = [
        'model' => 'deepseek-chat',
        'messages' => [
            ['role' => 'user', 'content' => 'Sebutkan 3 keutamaan ibadah qurban dalam bahasa Indonesia!'],
        ],
    ];

    $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer '.$apiKey,
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return response()->json([
        'http_code' => $httpCode,
        'response' => json_decode($response, true),
    ]);
});

Route::get('/test-groq', function () {
    $service = new \App\Services\mrj\GroqAIService;
    $result = $service->testConnection();

    return response()->json($result);
});

Route::get('acara', [AcaraGuestController::class, 'index'])->name('acara.index');
Route::get('acara-show/{slug}', [AcaraGuestController::class, 'show'])->name('acara.show');

Route::get('berita', [BeritaGuestController::class, 'index'])->name('berita.index');
Route::get('berita-show/{slug}', [BeritaGuestController::class, 'show'])->name('berita.show');

Route::get('pengumuman', [HomeController::class, 'pengumumanIndex'])->name('pengumuman.index');
Route::get('pengumuman-show/{slug}', [HomeController::class, 'pengumumanShow'])->name('pengumuman.show');

Route::get('/home/galeri/{id}', [GaleriController::class, 'apiFotos']);

Route::get('galeri', [HomeController::class, 'galeriIndex'])->name('galeri.index');

Route::post('/kontak/kirim', [HomeController::class, 'kirimPesan'])->name('kontak.kirim');

Route::get('/kegiatan-ramadhan', function () {
    $today = now();
    $ramadhanStart = \Carbon\Carbon::parse('2026-02-19');
    $ramadhanEnd = \Carbon\Carbon::parse('2026-03-19');

    if (! $today->between($ramadhanStart, $ramadhanEnd)) {
        abort(404, 'Halaman ini hanya tersedia selama bulan Ramadhan.');
    }

    return view('masjid.'.masjid().'.guest.laporan-harian.index');
})->name('guest.laporan-harian');

Route::get('/galeri/public', [HomeController::class, 'galeriPublic'])->name('home.galeri.public');

Route::get('/home/galeri/{id}', [HomeController::class, 'galeriDetail'])->name('home.galeri.detail');

Route::post('/set-location', [HomeController::class, 'setLocation'])
    ->name('set.location');

Route::prefix('santunan-ramadhan')->name('santunan-ramadhan.')->group(function () {
    Route::get('/', [PendaftaranYatimDhuafaController::class, 'indexPublik'])->name('index');
    Route::get('/daftar-anak-yatim-dhuafa', [PendaftaranYatimDhuafaController::class, 'index'])->name('form');
    Route::get('/data', [PendaftaranYatimDhuafaController::class, 'dataTable'])->name('data');
    Route::post('/daftar-anak-yatim-dhuafa', [PendaftaranYatimDhuafaController::class, 'store'])->name('submit');
    Route::get('{id}/edit', [PendaftaranYatimDhuafaController::class, 'edit'])->name('edit');
    Route::put('{id}', [PendaftaranYatimDhuafaController::class, 'update'])->name('update');
    Route::delete('/delete/{id}', [PendaftaranYatimDhuafaController::class, 'destroy'])->name('destroy');

    Route::get('/scan-duplikat', [PendaftaranYatimDhuafaController::class, 'scanDuplikat'])
        ->name('scan-duplikat');

    Route::post('import', [ExcelYatimDhuafaController::class, 'import'])->name('import');
    Route::get('template', [ExcelYatimDhuafaController::class, 'downloadTemplate'])->name('template');
    Route::post('export', [ExcelYatimDhuafaController::class, 'export'])->name('export');

    Route::post('export-by-sumber',
        [ExcelYatimDhuafaController::class, 'exportBySumber']
    )->name('exportBySumber');

});

Route::get('/santunan-ramadhan/scan-duplikat', [PendaftaranYatimDhuafaController::class, 'scanDuplikat'])
    ->name('santunan-ramadhan.scan-duplikat');

// Program Kesehatan
Route::prefix('daftar-donor-darah')->name('donor-darah.')->group(function () {
    Route::get('/', [KesehatanGuestController::class, 'create'])->name('daftar');
    Route::get('/peserta', [KesehatanGuestController::class, 'index'])->name('index');
    Route::post('/simpan-pendaftaran', [KesehatanGuestController::class, 'store'])->name('simpan-pendaftaran.store');
    Route::post('/simpan-pendaftaran-new', [KesehatanGuestController::class, 'storeNew'])->name('simpan-pendaftaran.storeNew');

    // Export
    Route::get('/export/donor-darah', [KesehatanGuestController::class, 'exportDonorDarah'])->name('export.donor');
    Route::get('/export/cek-kesehatan', [KesehatanGuestController::class, 'exportCekKesehatanNew'])->name('export.cek-kesehatan');
    Route::get('/export/cek-katarak', [KesehatanGuestController::class, 'exportCekKatarak'])->name('export.cek-katarak');

    // === FEEDBACK BARU ===
    Route::get('/feedback', [KesehatanGuestController::class, 'feedback'])->name('feedback');
    Route::post('/feedback', [KesehatanGuestController::class, 'storeFeedback'])->name('feedback.store');
});

Route::get('/donor-darah/success', function () {
    return view('masjid.'.masjid().'.guest.program-kesehatan.success');
})->name('donor-darah.success');

// Public Financial V2 disclosure. These endpoints are intentionally outside
// the admin middleware and have no writer route or legacy financial source.
Route::prefix('laporan-ziswaf')->name('public.ziswaf.')->group(function () {
    Route::get('/', [PublicZiswafReportController::class, 'index'])->name('index');
    Route::get('/dana/{fundCode}', [PublicZiswafReportController::class, 'fund'])
        ->where('fundCode', '[A-Za-z0-9-]+')
        ->name('fund');
});

// Group untuk user yang sudah login
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [LoginController::class, 'index'])->name('admin.dashboard');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Logout
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Financial V2 operational UX. This namespace and URL are intentionally
    // separate from legacy /admin/keuangan routes: no legacy journal, balance,
    // or transaction table participates in this workflow.
    Route::prefix('admin/keuangan-v2')->name('financial-v2.')->group(function () {
        Route::get('/', [OperationalFinancialController::class, 'dashboard'])->name('dashboard');
        Route::get('/riwayat', [OperationalFinancialController::class, 'history'])->name('transactions.index');
        // Financial V2 reports are isolated from legacy /admin/keuangan
        // reporting and read only the immutable Posted V2 ledger/journals.
        Route::get('/laporan', [FinancialReportController::class, 'index'])->name('reports.index');
        Route::get('/laporan/data', [FinancialReportController::class, 'data'])->name('reports.data');
        Route::get('/kontrol', [FinancialControlController::class, 'index'])->name('controls.index');
        Route::post('/kontrol/periode/{period}/tutup', [FinancialControlController::class, 'close'])->name('controls.close');
        Route::post('/kontrol/rekonsiliasi', [FinancialControlController::class, 'storeReconciliation'])->name('controls.reconciliations.store');
        Route::post('/kontrol/rekonsiliasi/{reconciliation}/tinjau', [FinancialControlController::class, 'review'])->name('controls.reconciliations.review');
        Route::post('/kontrol/rekonsiliasi/{reconciliation}/selesai', [FinancialControlController::class, 'complete'])->name('controls.reconciliations.complete');
        Route::post('/kontrol/rekonsiliasi/{reconciliation}/exception', [FinancialControlController::class, 'exception'])->name('controls.reconciliations.exception');
        // Saldo Awal V2 is a governed rehearsal workflow; it never writes legacy facts.
        Route::get('/saldo-awal', [FinancialOpeningBalanceController::class, 'index'])->name('opening-balances.index');
        Route::post('/saldo-awal', [FinancialOpeningBalanceController::class, 'store'])->name('opening-balances.store');
        Route::get('/saldo-awal/{openingBalanceBatch}', [FinancialOpeningBalanceController::class, 'show'])->name('opening-balances.show');
        Route::post('/saldo-awal/{openingBalanceBatch}/baris', [FinancialOpeningBalanceController::class, 'storeLine'])->name('opening-balances.lines.store');
        Route::post('/saldo-awal/{openingBalanceBatch}/verifikasi', [FinancialOpeningBalanceController::class, 'review'])->name('opening-balances.review');
        Route::post('/saldo-awal/{openingBalanceBatch}/setujui', [FinancialOpeningBalanceController::class, 'approve'])->name('opening-balances.approve');
        Route::post('/saldo-awal/{openingBalanceBatch}/catat', [FinancialOpeningBalanceController::class, 'post'])->name('opening-balances.post');
        Route::get('/dana', [OperationalFinancialController::class, 'funds'])->name('funds.index');
        Route::get('/dana/kelompok/{group}', [OperationalFinancialController::class, 'fundGroup'])->name('funds.groups.show');
        Route::get('/dana/{fund}/riwayat-sumber/tambah', [HistoricalFundHistoryController::class, 'create'])->name('funds.history.create');
        Route::post('/dana/{fund}/riwayat-sumber', [HistoricalFundHistoryController::class, 'store'])->name('funds.history.store');
        Route::get('/dana/{fund}/riwayat-sumber/{history}/edit', [HistoricalFundHistoryController::class, 'edit'])->name('funds.history.edit');
        Route::put('/dana/{fund}/riwayat-sumber/{history}', [HistoricalFundHistoryController::class, 'update'])->name('funds.history.update');
        Route::get('/dana/{fund}', [OperationalFinancialController::class, 'fundDetail'])->name('funds.show');
        // Governed V2 master data stays separate from legacy financial menus.
        // These routes create configuration and audit events only; they never
        // create Journal, JournalLine, Ledger, opening balance, or legacy facts.
        Route::prefix('/master')->name('masters.')->group(function () {
            Route::get('/rekening-kas', [FinancialMasterDataController::class, 'accounts'])->name('accounts.index');
            Route::post('/rekening-kas', [FinancialMasterDataController::class, 'storeAccount'])->name('accounts.store');
            Route::put('/rekening-kas/{financialAccount}', [FinancialMasterDataController::class, 'updateAccount'])->name('accounts.update');
            Route::post('/rekening-kas/{financialAccount}/aktifkan', [FinancialMasterDataController::class, 'activateAccount'])->name('accounts.activate');
            Route::post('/rekening-kas/{financialAccount}/nonaktifkan', [FinancialMasterDataController::class, 'deactivateAccount'])->name('accounts.deactivate');

            Route::get('/dana', [FinancialMasterDataController::class, 'funds'])->name('funds.index');
            Route::post('/dana/klasifikasi', [FinancialMasterDataController::class, 'storeFundType'])->name('fund-types.store');
            Route::put('/dana/klasifikasi/{fundType}', [FinancialMasterDataController::class, 'updateFundType'])->name('fund-types.update');
            Route::post('/dana/pembatasan', [FinancialMasterDataController::class, 'storeRestriction'])->name('restrictions.store');
            Route::put('/dana/pembatasan/{restriction}', [FinancialMasterDataController::class, 'updateRestriction'])->name('restrictions.update');
            Route::post('/dana', [FinancialMasterDataController::class, 'storeFund'])->name('funds.store');
            Route::put('/dana/{fund}', [FinancialMasterDataController::class, 'updateFund'])->name('funds.update');
            Route::post('/dana/{fund}/aktifkan', [FinancialMasterDataController::class, 'activateFund'])->name('funds.activate');
            Route::post('/dana/{fund}/nonaktifkan', [FinancialMasterDataController::class, 'deactivateFund'])->name('funds.deactivate');

            Route::get('/program', [FinancialMasterDataController::class, 'programs'])->name('programs.index');
            Route::post('/program', [FinancialMasterDataController::class, 'storeProgram'])->name('programs.store');
            Route::put('/program/{program}', [FinancialMasterDataController::class, 'updateProgram'])->name('programs.update');
            Route::post('/program/{program}/aktifkan', [FinancialMasterDataController::class, 'activateProgram'])->name('programs.activate');
            Route::post('/program/{program}/nonaktifkan', [FinancialMasterDataController::class, 'deactivateProgram'])->name('programs.deactivate');

            Route::get('/kategori', [FinancialMasterDataController::class, 'categories'])->name('categories.index');
            Route::post('/kategori', [FinancialMasterDataController::class, 'storeCategory'])->name('categories.store');
            Route::put('/kategori/{category}', [FinancialMasterDataController::class, 'updateCategory'])->name('categories.update');
            Route::post('/kategori/{category}/nonaktifkan', [FinancialMasterDataController::class, 'deactivateCategory'])->name('categories.deactivate');

            Route::get('/aturan-dana', [FinancialMasterDataController::class, 'policies'])->name('policies.index');
            Route::post('/aturan-dana', [FinancialMasterDataController::class, 'storePolicy'])->name('policies.store');
            Route::put('/aturan-dana/{policyVersion}', [FinancialMasterDataController::class, 'updatePolicy'])->name('policies.update');
            Route::post('/aturan-dana/{policyVersion}/berlakukan', [FinancialMasterDataController::class, 'makePolicyEffective'])->name('policies.effective');
            Route::post('/aturan-dana/{policyVersion}/aturan', [FinancialMasterDataController::class, 'storePolicyRule'])->name('policy-rules.store');
            Route::put('/aturan-dana/rule/{policyRule}', [FinancialMasterDataController::class, 'updatePolicyRule'])->name('policy-rules.update');
        });
        Route::get('/alokasi-dana/riwayat', [OperationalFinancialController::class, 'allocationHistory'])->name('allocations.history');
        Route::get('/alokasi-dana/baru', [OperationalFinancialController::class, 'allocationForm'])->name('allocations.create');
        Route::post('/alokasi-dana', [OperationalFinancialController::class, 'storeAllocation'])->name('allocations.store');
        Route::post('/alokasi-dana/{allocation}/ajukan', [OperationalFinancialController::class, 'submitAllocation'])->name('allocations.submit');
        Route::post('/alokasi-dana/{allocation}/setujui', [OperationalFinancialController::class, 'approveAllocation'])->name('allocations.approve');
        Route::post('/alokasi-dana/{allocation}/batalkan', [OperationalFinancialController::class, 'cancelAllocation'])->name('allocations.cancel');
        Route::get('/realisasi/draft', [OperationalFinancialController::class, 'realizationDrafts'])->name('realizations.drafts');
        Route::get('/opsi', [OperationalFinancialController::class, 'options'])->name('options');
        Route::post('/pratinjau', [OperationalFinancialController::class, 'preview'])->name('preview');
        Route::get('/lampiran/{attachment}/lihat', [OperationalFinancialController::class, 'viewAttachment'])->name('attachments.view');
        Route::get('/lampiran/{attachment}/unduh', [OperationalFinancialController::class, 'downloadAttachment'])->name('attachments.download');
        Route::get('/{operation}/baru', [OperationalFinancialController::class, 'create'])
            ->where('operation', 'receipt|payment|transfer|interfund|realization')
            ->name('transactions.create');
        Route::post('/{operation}', [OperationalFinancialController::class, 'store'])
            ->where('operation', 'receipt|payment|transfer|interfund|realization')
            ->name('transactions.store');
        Route::get('/transaksi/{transaction}/ubah', [OperationalFinancialController::class, 'edit'])->name('transactions.edit');
        Route::put('/transaksi/{transaction}', [OperationalFinancialController::class, 'update'])->name('transactions.update');
        Route::post('/transaksi/{transaction}/ajukan-realisasi', [OperationalFinancialController::class, 'submitRealization'])->name('realizations.submit');
        Route::post('/transaksi/{transaction}/verifikasi-realisasi', [OperationalFinancialController::class, 'verifyRealization'])->name('realizations.verify');
        Route::post('/transaksi/{transaction}/setujui-realisasi', [OperationalFinancialController::class, 'approveRealization'])->name('realizations.approve');
        Route::post('/transaksi/{transaction}/catat', [OperationalFinancialController::class, 'post'])->name('transactions.post');
        Route::post('/transaksi/{transaction}/batalkan', [OperationalFinancialController::class, 'cancel'])->name('transactions.cancel');
        Route::get('/transaksi/{transaction}', [OperationalFinancialController::class, 'show'])->name('transactions.show');
    });

    // Prefix admin
    Route::prefix('admin')->group(function () {

        // ==================== MANAJEMEN QURBAN ====================
        Route::get('/paket', [QurbanPaketController::class, 'index'])->name('admin.qurban.paket.index');
        Route::get('/paket/data', [QurbanPaketController::class, 'data'])->name('admin.qurban.paket.data');
        Route::post('/paket', [QurbanPaketController::class, 'store'])->name('admin.qurban.paket.store');
        Route::get('/qurban/paket/{id}/edit', [QurbanPaketController::class, 'edit'])->name('admin.qurban.paket.edit');
        Route::put('/qurban/paket/{id}', [QurbanPaketController::class, 'update'])->name('admin.qurban.paket.update');
        Route::delete('/paket/{id}', [QurbanPaketController::class, 'destroy'])->name('admin.qurban.paket.destroy');
        Route::patch('/paket/{id}/stok', [QurbanPaketController::class, 'updateStok'])->name('admin.qurban.paket.stok');

        // Pengaturan Qurban
        Route::get('/setting', [QurbanSettingController::class, 'index'])->name('admin.qurban.setting.index');
        Route::post('/setting', [QurbanSettingController::class, 'update'])->name('admin.qurban.setting.update');
        Route::post('/setting/reset', [QurbanSettingController::class, 'reset'])->name('admin.qurban.setting.reset');

        // Registrasi Qurban
        Route::get('/registrasi', [QurbanRegistrasiController::class, 'index'])->name('admin.qurban.registrasi.index');
        Route::get('/registrasi/data', [QurbanRegistrasiController::class, 'data'])->name('admin.qurban.registrasi.data');
        Route::get('/qurban/registrasi/{id}', [QurbanRegistrasiController::class, 'show'])->name('admin.qurban.registrasi.show');
        Route::put('/qurban/registrasi/{id}/status', [QurbanRegistrasiController::class, 'updateStatus'])->name('admin.qurban.registrasi.update-status');
        Route::delete('/qurban/registrasi/{id}', [QurbanRegistrasiController::class, 'destroy'])->name('admin.qurban.registrasi.destroy');
        Route::get('/registrasi/export/excel', [QurbanRegistrasiController::class, 'export'])->name('admin.qurban.registrasi.export');
        Route::post('/qurban/registrasi/{id}/upload-bukti', [QurbanRegistrasiController::class, 'uploadBukti'])->name('upload-bukti');
        Route::delete('/qurban/registrasi/{id}/delete-bukti', [QurbanRegistrasiController::class, 'deleteBukti'])->name('delete-bukti');

        // GALERI QURBAN
        Route::get('/qurban/galeri', [QurbanGalleryController::class, 'index'])->name('admin.qurban.galeri.index');
        Route::get('/qurban/galeri/data', [QurbanGalleryController::class, 'data'])->name('admin.qurban.galeri.data');
        Route::get('/qurban/galeri/create', [QurbanGalleryController::class, 'create'])->name('admin.qurban.galeri.create');
        Route::post('/qurban/galeri', [QurbanGalleryController::class, 'store'])->name('admin.qurban.galeri.store');
        Route::get('/qurban/galeri/{id}/edit', [QurbanGalleryController::class, 'edit'])->name('admin.qurban.galeri.edit');
        Route::put('/qurban/galeri/{id}', [QurbanGalleryController::class, 'update'])->name('admin.qurban.galeri.update');
        Route::delete('/qurban/galeri/{id}', [QurbanGalleryController::class, 'destroy'])->name('admin.qurban.galeri.destroy');
        Route::post('/qurban/galeri/reorder', [QurbanGalleryController::class, 'reorder'])->name('admin.qurban.galeri.reorder');
        Route::post('/qurban/galeri/{id}/cover', [QurbanGalleryController::class, 'setCover'])->name('admin.qurban.galeri.cover');

        // ==================== LAPORAN QURBAN ====================

        // INDEX (tampil semua data)
        Route::get('qurban/report', [QurbanReportController::class, 'index'])->name('admin.qurban.report.index');

        // CREATE (form tambah baru)
        Route::get('qurban/report/create', [QurbanReportController::class, 'create'])->name('admin.qurban.report.create');

        // STORE (simpan data baru)
        Route::post('qurban/report', [QurbanReportController::class, 'store'])->name('admin.qurban.report.store');

        // EDIT (form edit)
        Route::get('qurban/report/{id}/edit', [QurbanReportController::class, 'edit'])->name('admin.qurban.report.edit');

        // UPDATE (simpan perubahan)
        Route::put('qurban/report/{id}', [QurbanReportController::class, 'update'])->name('admin.qurban.report.update');

        // DESTROY (hapus)
        Route::delete('qurban/report/{id}', [QurbanReportController::class, 'destroy'])->name('admin.qurban.report.destroy');

        // CLONE
        Route::get('qurban/report/{id}/clone', [QurbanReportController::class, 'clone'])->name('admin.qurban.report.clone');

        // SET ACTIVE
        Route::post('qurban/report/{id}/set-active', [QurbanReportController::class, 'setActive'])->name('admin.qurban.report.set-active');

        // DATA (untuk datatable)
        Route::get('qurban/report/data', [QurbanReportController::class, 'data'])->name('admin.qurban.report.data');

        // GALLERY
        Route::post('qurban/report/{id}/upload-gallery', [QurbanReportController::class, 'uploadGallery'])->name('admin.qurban.report.upload-gallery');
        Route::post('qurban/report/{id}/remove-gallery', [QurbanReportController::class, 'removeGallery'])->name('admin.qurban.report.remove-gallery');

        // REMOVE FIELD IMAGE
        Route::post('qurban/report/{id}/remove-field-image', [QurbanReportController::class, 'removeFieldImage'])->name('admin.qurban.report.remove-field-image');

        // EVALUASI QURBAN
        // STATIC ROUTES (harus di atas)
        Route::get('/evaluasi-qurban/data', [EvaluasiQurbanController::class, 'data'])->name('admin.evaluasi-qurban.data');
        Route::get('/evaluasi-qurban/statistik', [EvaluasiQurbanController::class, 'statistik'])->name('admin.evaluasi-qurban.statistik');
        Route::get('/evaluasi-qurban/statistik-data', [EvaluasiQurbanController::class, 'statistikData'])->name('admin.evaluasi-qurban.statistik-data');
        Route::get('/evaluasi-qurban/generate-summary', [EvaluasiQurbanController::class, 'generateSummary'])->name('admin.evaluasi-qurban.generate-summary');

        // WISH GENERATION ROUTES
        Route::post('/evaluasi-qurban/generate-wish/{id}', [EvaluasiQurbanController::class, 'generateWish'])->name('admin.evaluasi-qurban.generate-wish');
        Route::post('/evaluasi-qurban/generate-all-wish', [EvaluasiQurbanController::class, 'generateAllWish'])->name('admin.evaluasi-qurban.generate-all-wish');

        // ✅ CEK STATUS AI (TAMBAHKAN INI)
        Route::get('/evaluasi-qurban/check-ai-status', [EvaluasiQurbanController::class, 'checkAIStatus'])->name('admin.evaluasi-qurban.check-ai-status');

        // DYNAMIC ROUTES (dengan parameter)
        Route::get('/evaluasi-qurban', [EvaluasiQurbanController::class, 'index'])->name('admin.evaluasi-qurban.index');
        Route::get('/evaluasi-qurban/{id}', [EvaluasiQurbanController::class, 'show'])->name('admin.evaluasi-qurban.show');
        Route::delete('/evaluasi-qurban/{id}', [EvaluasiQurbanController::class, 'destroy'])->name('admin.evaluasi-qurban.destroy');

        // Role
        Route::get('/role', [RoleController::class, 'index'])->name('admin.role');
        Route::get('/role/data', [RoleController::class, 'data'])->name('admin.role.data');
        Route::post('/role', [RoleController::class, 'store'])->name('admin.role.store');
        Route::get('/role/{id}', [RoleController::class, 'show'])->name('admin.role.show');
        Route::put('/role/{id}', [RoleController::class, 'update'])->name('admin.role.update');
        Route::delete('/role/{id}', [RoleController::class, 'destroy'])->name('admin.role.destroy');

        // User CRUD
        Route::get('/user', [UserController::class, 'index'])->name('admin.user');
        Route::get('/user/data', [UserController::class, 'data'])->name('admin.user.data');
        Route::post('/user', [UserController::class, 'store'])->name('admin.user.store');
        Route::get('/user/{id}', [UserController::class, 'show'])->name('admin.user.show');
        Route::put('/user/{id}', [UserController::class, 'update'])->name('admin.user.update');
        Route::delete('/user/{id}', [UserController::class, 'destroy'])->name('admin.user.destroy');

        // Profil Masjid
        Route::get('profil/', [ProfilMasjidController::class, 'index'])->name('admin.profil');
        Route::put('profil/', [ProfilMasjidController::class, 'updateProfil'])->name('admin.profil.update');
        Route::post('/pengurus', [ProfilMasjidController::class, 'storePengurus'])->name('admin.profil.pengurus.store');
        Route::get('/pengurus/{id}', [ProfilMasjidController::class, 'editPengurus'])->name('admin.profil.pengurus.edit');
        Route::put('/pengurus/{id}', [ProfilMasjidController::class, 'updatePengurus'])->name('admin.profil.pengurus.update');
        Route::delete('/pengurus/{id}', [ProfilMasjidController::class, 'destroyPengurus'])->name('admin.profil.pengurus.destroy');
        Route::post('/pengurus/reorder', [ProfilMasjidController::class, 'reorderPengurus'])->name('admin.profil.pengurus.reorder');

        // Banner
        Route::get('banner', [BannerController::class, 'index'])->name('admin.banner.index');
        Route::get('banner/data', [BannerController::class, 'data'])->name('admin.banner.data');
        Route::post('banner', [BannerController::class, 'store'])->name('admin.banner.store');
        Route::get('banner/{id}', [BannerController::class, 'edit'])->name('admin.banner.edit');
        Route::put('banner/{id}', [BannerController::class, 'update'])->name('admin.banner.update');
        Route::delete('banner/{id}', [BannerController::class, 'destroy'])->name('admin.banner.destroy');

        // Kategori
        Route::get('kategori', [KategoriController::class, 'index'])->name('admin.kategori.index');
        Route::get('kategori/data', [KategoriController::class, 'data'])->name('admin.kategori.data');
        Route::post('kategori', [KategoriController::class, 'store'])->name('admin.kategori.store');
        Route::get('kategori/{id}', [KategoriController::class, 'edit'])->name('admin.kategori.edit');
        Route::put('kategori/{id}', [KategoriController::class, 'update'])->name('admin.kategori.update');
        Route::delete('kategori/{id}', [KategoriController::class, 'destroy'])->name('admin.kategori.destroy');

        // Berita
        Route::get('berita', [BeritaController::class, 'index'])->name('admin.berita.index');
        Route::get('berita/data', [BeritaController::class, 'data'])->name('admin.berita.data');
        Route::post('berita', [BeritaController::class, 'store'])->name('admin.berita.store');
        Route::get('berita/{id}', [BeritaController::class, 'show'])->name('admin.berita.show');
        Route::put('berita/{id}', [BeritaController::class, 'update'])->name('admin.berita.update');
        Route::delete('berita/{id}', [BeritaController::class, 'destroy'])->name('admin.berita.destroy');

        // Acara
        Route::get('acara', [AcaraController::class, 'index'])->name('admin.acara.index');
        Route::get('acara/data', [AcaraController::class, 'data'])->name('admin.acara.data');
        Route::post('acara', [AcaraController::class, 'store'])->name('admin.acara.store');
        Route::get('acara/{id}', [AcaraController::class, 'edit'])->name('admin.acara.edit');
        Route::put('acara/{id}', [AcaraController::class, 'update'])->name('admin.acara.update');
        Route::delete('acara/{id}', [AcaraController::class, 'destroy'])->name('admin.acara.destroy');

        // Layanan
        Route::get('layanan', [LayananController::class, 'index'])->name('admin.layanan.index');
        Route::get('layanan/data', [LayananController::class, 'data'])->name('admin.layanan.data');
        Route::post('layanan', [LayananController::class, 'store'])->name('admin.layanan.store');
        Route::get('layanan/{id}/edit', [LayananController::class, 'edit'])->name('admin.layanan.edit');
        Route::put('layanan/{id}', [LayananController::class, 'update'])->name('admin.layanan.update');
        Route::delete('layanan/{id}', [LayananController::class, 'destroy'])->name('admin.layanan.destroy');

        // Galeri
        Route::get('galeri', [GaleriController::class, 'index'])->name('admin.galeri.index');
        Route::get('galeri/data', [GaleriController::class, 'data'])->name('admin.galeri.data');
        Route::post('galeri', [GaleriController::class, 'store'])->name('admin.galeri.store');
        Route::get('galeri/{id}', [GaleriController::class, 'edit'])->name('admin.galeri.edit');
        Route::put('galeri/{id}', [GaleriController::class, 'update'])->name('admin.galeri.update');
        Route::delete('galeri/{id}', [GaleriController::class, 'destroy'])->name('admin.galeri.destroy');
        Route::get('galeri-api/{id}/fotos', [GaleriController::class, 'apiFotos'])->name('galeri.api.fotos');

        // Pengumuman
        Route::get('pengumuman', [PengumumanController::class, 'index'])->name('admin.pengumuman.index');
        Route::get('pengumuman/data', [PengumumanController::class, 'data'])->name('admin.pengumuman.data');
        Route::post('pengumuman', [PengumumanController::class, 'store'])->name('admin.pengumuman.store');
        Route::get('pengumuman/{id}', [PengumumanController::class, 'edit'])->name('admin.pengumuman.edit');
        Route::put('pengumuman/{id}', [PengumumanController::class, 'update'])->name('admin.pengumuman.update');
        Route::delete('pengumuman/{id}', [PengumumanController::class, 'destroy'])->name('admin.pengumuman.destroy');

        // SEO Halaman
        Route::get('seo-pages', [SeoPageController::class, 'index'])->name('admin.seo-pages.index');
        Route::put('seo-pages', [SeoPageController::class, 'update'])->name('admin.seo-pages.update');

        // Keuangan
        Route::get('/kotak-infak', [KotakInfakController::class, 'index'])->name('admin.keuangan.kotak-infak');
        Route::get('/keuangan/kotak-infak/list', [KotakInfakController::class, 'data'])->name('admin.keuangan.kotak-infak.list');
        Route::post('/kotak-infak', [KotakInfakController::class, 'storeKotak'])->name('admin.keuangan.kotak-infak.store');

        // Akun Keuangan (Chart of Accounts)
        Route::get('keuangan/akun', [AkunKeuanganController::class, 'index'])->name('admin.keuangan.akun.index');
        Route::get('keuangan/akun/data', [AkunKeuanganController::class, 'data'])->name('admin.keuangan.akun.data');
        Route::post('keuangan/akun', [AkunKeuanganController::class, 'store'])->name('admin.keuangan.akun.store');
        Route::get('keuangan/akun/{id}', [AkunKeuanganController::class, 'edit'])->name('admin.keuangan.akun.edit');
        Route::put('keuangan/akun/{id}', [AkunKeuanganController::class, 'update'])->name('admin.keuangan.akun.update');
        Route::delete('keuangan/akun/{id}', [AkunKeuanganController::class, 'destroy'])->name('admin.keuangan.akun.destroy');
        Route::get('/keuangan/options', [AkunKeuanganController::class, 'options'])->name('admin.keuangan.akun.options');

        // Jurnal Umum (read-only, bisa difilter bulan)
        Route::get('keuangan/jurnal', [JurnalController::class, 'index'])->name('admin.keuangan.jurnal.index');
        Route::get('keuangan/jurnal/data', [JurnalController::class, 'data'])->name('admin.keuangan.jurnal.data');

        // Keuangan (Saldo Awal, Petty Cash, Laporan)
        // Saldo Awal
        Route::get('keuangan/saldo-awal', [SaldoAwalController::class, 'index'])->name('admin.keuangan.saldo-awal');
        Route::post('keuangan/saldo-awal', [SaldoAwalController::class, 'store'])->name('admin.keuangan.saldo-awal.store');
        Route::get('saldo-awal/data', [SaldoAwalController::class, 'getData'])->name('admin.keuangan.saldo-awal.data');
        Route::post('keuangan/saldo-awal/update', [SaldoAwalController::class, 'updateSaldo'])->name('admin.keuangan.saldo-awal.update');
        Route::post('saldo-awal/create-new', [SaldoAwalController::class, 'createNewPeriod'])->name('admin.keuangan.saldo-awal.create-new');

        // Petty Cash
        Route::get('keuangan/petty-cash', [PettyCashController::class, 'index'])->name('admin.keuangan.petty-cash');
        Route::post('keuangan/petty-cash', [PettyCashController::class, 'store'])->name('admin.keuangan.petty-cash.store');
        Route::get('keuangan/petty-cash/data', [PettyCashController::class, 'data'])->name('admin.keuangan.petty-cash.data');
        Route::get('keuangan/petty-cash/saldo', [PettyCashController::class, 'saldo'])->name('admin.keuangan.petty-cash.saldo')->middleware('auth');

        // Pengeluaran Umum
        Route::get('keuangan/pengeluaran', [PengeluaranController::class, 'index'])->name('admin.keuangan.pengeluaran');
        Route::get('/pengeluaran/data', [PengeluaranController::class, 'data'])->name('admin.keuangan.pengeluaran.data');
        Route::post('keuangan/pengeluaran', [PengeluaranController::class, 'store'])->name('admin.keuangan.pengeluaran.store');

        // Alokasi Dana
        Route::get('keuangan/alokasi-dana', [AlokasiDanaController::class, 'index'])->name('admin.keuangan.alokasi-dana');
        Route::get('/alokasi-dana/data', [AlokasiDanaController::class, 'data'])->name('admin.keuangan.alokasi-dana.data');
        Route::post('keuangan/alokasi-dana', [AlokasiDanaController::class, 'store'])->name('admin.keuangan.alokasi-dana.store');

        // Zakat
        Route::get('keuangan/zakat', [ZakatController::class, 'index'])->name('admin.keuangan.zakat.index');
        Route::get('/data', [ZakatController::class, 'data'])->name('admin.keuangan.zakat.data');
        Route::post('/store-penerimaan', [ZakatController::class, 'storePenerimaan'])->name('admin.keuangan.zakat.store.penerimaan');
        Route::post('zakat/penyaluran', [ZakatController::class, 'storePenyaluran'])->name('admin.keuangan.zakat.store.penyaluran');
        Route::get('/kwitansi/{id}', [ZakatController::class, 'kwitansi'])->name('admin.keuangan.zakat.kwitansi');
        Route::get('/search-muzakki', [ZakatController::class, 'searchMuzakki'])->name('admin.keuangan.zakat.search-muzakki');
        Route::get('/zakat/edit-data/{id}', [ZakatController::class, 'editData'])->name('admin.keuangan.zakat.edit-data');
        Route::put('/zakat/update/{id}', [ZakatController::class, 'update'])->name('admin.keuangan.zakat.update');
        Route::delete('/zakat/delete/{id}', [ZakatController::class, 'delete'])->name('admin.keuangan.zakat.delete');

        // Pendapatan
        Route::get('keuangan/penerimaan', [PenerimaanPemasukanController::class, 'index'])->name('admin.keuangan.penerimaan');
        Route::get('penerimaan/data', [PenerimaanPemasukanController::class, 'data'])->name('admin.keuangan.penerimaan.data');
        Route::post('penerimaan/store', [PenerimaanPemasukanController::class, 'store'])->name('admin.keuangan.penerimaan.store');

        // Dana Terikat & Program Rutin
        Route::prefix('dana-terikat')->name('admin.keuangan.dana-terikat.')->group(function () {
            Route::get('/', [DanaTerikatController::class, 'index'])->name('index');

            // Data untuk semua tab (saldo, penerima, penerimaan, realisasi, status_bulanan)
            Route::get('/data', [DanaTerikatController::class, 'data'])->name('data');

            Route::get('/akun-options', [DanaTerikatController::class, 'akunOptions'])->name('options');

            Route::post('/penerimaan/store', [DanaTerikatController::class, 'storePenerimaan'])->name('penerimaan.store');

            Route::post('/penerima/store', [DanaTerikatController::class, 'storePenerima'])->name('penerima.store');
            Route::put('/penerima/update/{id}', [DanaTerikatController::class, 'updatePenerima'])->name('penerima.update');
            Route::get('/penerima/show', [DanaTerikatController::class, 'showPenerima'])->name('penerima.show');
            Route::get('/penerima/check-nama', [DanaTerikatController::class, 'cekNamaPenerima'])->name('penerima.check-nama');
            Route::delete('penerima/{id}', [DanaTerikatController::class, 'destroyPenerima'])->name('penerima.destroy');

            Route::post('/realisasi/store', [DanaTerikatController::class, 'realisasi'])->name('realisasi.store');
            Route::post('/koreksi/realisasi/store', [DanaTerikatController::class, 'koreksiStore'])->name('koreksi.realisasi.store');
            Route::get('/realisasi/penerima-aktif', [DanaTerikatController::class, 'penerimaAktif'])->name('realisasi.penerima-aktif');

            Route::post('/program/store', [DanaTerikatController::class, 'storeProgram'])->name('program.store');

            Route::get('/kwitansi/{id}', [DanaTerikatController::class, 'kwitansi'])->name('kwitansi');

            // ===== STATUS BULANAN (BARU) =====
            Route::prefix('status-bulanan')->name('status-bulanan.')->group(function () {
                Route::get('/', [DanaTerikatController::class, 'getStatusBulanan'])->name('index');
                Route::get('/{penerimaId}', [DanaTerikatController::class, 'getStatusBulananById'])->name('show');
                Route::post('/update', [DanaTerikatController::class, 'updateStatusBulanan'])->name('update');
                Route::post('/update-lengkap', [DanaTerikatController::class, 'updateStatusBulananLengkap'])->name('update-lengkap');
                Route::post('/copy', [DanaTerikatController::class, 'copyStatusBulanan'])->name('copy');
                Route::post('/realisasi', [DanaTerikatController::class, 'realisasiDariStatus'])->name('realisasi');
                Route::get('/export', [DanaTerikatController::class, 'exportStatusExcel'])->name('export');
            });

            // Di dalam group dana-terikat
            Route::prefix('alokasi')->name('alokasi.')->group(function () {
                Route::post('/store', [DanaAlokasiController::class, 'store'])->name('store');
                Route::get('/riwayat/{programId}', [DanaAlokasiController::class, 'riwayat'])->name('riwayat');
                Route::post('/hitung', [DanaAlokasiController::class, 'hitungAlokasi'])->name('hitung');
            });

            // CRUD referensi
            Route::resource('referensi', DanaTerikatReferensiController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
        });

        // Slider Motivasi
        Route::resource('slide-motivasi', SlideMotivasiController::class)
            ->names('admin.slide-motivasi')
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('slide-motivasi/data', [SlideMotivasiController::class, 'data'])->name('admin.slide-motivasi.data');

        // Quote Harian
        Route::resource('quote-harian', QuoteHarianController::class)
            ->names('admin.quote-harian')
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('quote-harian/data', [QuoteHarianController::class, 'data'])->name('admin.quote-harian.data');

        // Khutbah Jumat
        Route::resource('khutbah-jumat', KhutbahJumatController::class)
            ->names('admin.khutbah-jumat')
            ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::get('khutbah-jumat/data', [KhutbahJumatController::class, 'data'])->name('admin.khutbah-jumat.data');

        // Neraca Saldo + Export
        // Route::get('keuangan/laporan/neraca-saldo', [LaporanController::class, 'neracaSaldo'])->name('admin.keuangan.laporan.neraca-saldo');
        // Route::get('keuangan/laporan/neraca-saldo/pdf', [LaporanController::class, 'neracaSaldoPdf'])->name('admin.keuangan.laporan.neraca-saldo.pdf');
        // Route::get('keuangan/laporan/neraca-saldo/excel', [LaporanController::class, 'neracaSaldoExcel'])->name('admin.keuangan.laporan.neraca-saldo.excel');

    });

    // PINDAHKAN RAMADHAN KE SINI (sejajar dengan prefix 'admin')
    Route::prefix('admin/ramadhan')
        ->name('admin.ramadhan.')
        ->middleware(['auth'])
        ->group(function () {

            // ================= JADWAL IMAM =================

            Route::get('jadwal-imam/data', [\App\Http\Controllers\Admin\Ramadhan\JadwalImamController::class, 'data'])
                ->name('jadwal-imam.data')->withoutMiddleware(['auth']);

            Route::get('jadwal-imam/{jadwal_imam}/edit', [\App\Http\Controllers\Admin\Ramadhan\JadwalImamController::class, 'edit'])
                ->name('jadwal-imam.edit');

            Route::resource('jadwal-imam', \App\Http\Controllers\Admin\Ramadhan\JadwalImamController::class)
                ->only(['index', 'store', 'update', 'destroy']);

            // ================= LAPORAN HARIAN =================

            Route::get('laporan-harian/data', [\App\Http\Controllers\Admin\Ramadhan\LaporanHarianController::class, 'data'])
                ->name('laporan-harian.data')->withoutMiddleware(['auth']);

            Route::get('donatur-hari-ini/data', [\App\Http\Controllers\Admin\Ramadhan\LaporanHarianController::class, 'donatur'])
                ->name('donatur-hari-ini.data')->withoutMiddleware(['auth']);

            Route::get('laporan-harian/{laporan_harian}/edit', [\App\Http\Controllers\Admin\Ramadhan\LaporanHarianController::class, 'edit'])
                ->name('laporan-harian.edit');

            Route::resource('laporan-harian', \App\Http\Controllers\Admin\Ramadhan\LaporanHarianController::class)
                ->only(['index', 'store', 'update', 'destroy']);
        });

    Route::get('/admin/ramadhan/laporan-harian/prev/{malam_ke}', function ($malam_ke) {
        $prev = \App\Models\LaporanRamadhanHarian::where('malam_ke', $malam_ke - 1)->first();

        if (! $prev) {
            return response()->json(['success' => false, 'data' => null]);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'infaq_ramadan_saldo_sekarang' => $prev->infaq_ramadan_saldo_sekarang ?? 0,
                'ifthor_saldo_sekarang' => $prev->ifthor_saldo_sekarang ?? 0,
                'santunan_yatim_terkumpul_kemarin' => ($prev->santunan_yatim_terkumpul_kemarin ?? 0) + collect($prev->santunan_yatim_penerimaan_hari_ini ?? [])->sum('nominal'),
                'paket_sembako_terkumpul_kemarin' => ($prev->paket_sembako_terkumpul_kemarin ?? 0) + collect($prev->paket_sembako_penerimaan_hari_ini ?? [])->sum('nominal'),
                'gebyar_anak_terkumpul_kemarin' => ($prev->gebyar_anak_terkumpul_kemarin ?? 0) + collect($prev->gebyar_anak_penerimaan_hari_ini ?? [])->sum('nominal'),
                // tambahkan jika ada field kumulatif lain
            ],
        ]);
    })->name('admin.ramadhan.laporan-harian.prev');
});

require __DIR__.'/auth.php';
