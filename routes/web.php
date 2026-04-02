<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\BeritaController;
use App\Http\Controllers\Admin\KategoriController;
use App\Http\Controllers\Admin\AcaraController;
use App\Http\Controllers\Admin\GaleriController;
use App\Http\Controllers\Admin\PengumumanController;
use App\Http\Controllers\Admin\ProfilMasjidController;
use App\Http\Controllers\Admin\KotakInfakController;
use App\Http\Controllers\Admin\AkunKeuanganController;
use App\Http\Controllers\Admin\JurnalController;
use App\Http\Controllers\Admin\PettyCashController;
use App\Http\Controllers\Admin\SaldoAwalController;
use App\Http\Controllers\Admin\AlokasiDanaController;
use App\Http\Controllers\Admin\PengeluaranController;
use App\Http\Controllers\Admin\PenerimaanPemasukanController;
use App\Http\Controllers\Admin\ZakatController;
use App\Http\Controllers\Admin\DanaTerikatController;
use App\Http\Controllers\Admin\DanaTerikatReferensiController;
use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\LayananController;
use App\Http\Controllers\Admin\SlideMotivasiController;
use App\Http\Controllers\Admin\QuoteHarianController;
use App\Http\Controllers\Admin\KhutbahJumatController;

use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\AcaraGuestController;
use App\Http\Controllers\User\BeritaGuestController;
use App\Http\Controllers\User\PendaftaranYatimDhuafaController;
use App\Http\Controllers\User\ExcelYatimDhuafaController;
use App\Http\Controllers\User\SaranController;
use App\Http\Controllers\User\ProgramRamadhanGuestController;
use App\Http\Controllers\User\KesehatanGuestController;

Route::get('/pwa-splash', function () {
    return view('pwa.splash');
})->name('pwa.splash');

Route::get('/manifest.json', function () {
    $kode = masjid(); // dari helper kamu

    $config = config("masjids.{$kode}", config('masjids.default'));

    return response()->json([
        'name'          => $config['name'],
        'short_name'    => $config['short_name'],
        'description'   => $config['jargon'] . ' – Masjid Era Digital',
        // 'start_url'     => '/pwa-splash',
        'display'       => 'standalone',
        'background_color' => $config['gradient_from'],
        'theme_color'   => $config['primary_color'],
        'orientation'   => 'portrait-primary',
        'icons'         => [
            [
                'src'     => '/pwa/mrj-logo.png',
                'sizes'   => '92x92',
                'type'    => 'image/png',
                'purpose' => 'maskable',
            ],
            [
                'src'     => '/pwa/mrj-logo.png',
                'sizes'   => '92x92',
                'type'    => 'image/png',
                'purpose' => 'maskable',
            ],
        ],
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::prefix('program-ramadhan')->name('program-ramadhan.')->group(function () {
    Route::get('/', [ProgramRamadhanGuestController::class, 'index'])->name('index');
    Route::get('/{slug}', [ProgramRamadhanGuestController::class, 'show'])->name('show');
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
    $ramadhanEnd   = \Carbon\Carbon::parse('2026-03-19');

    if (!$today->between($ramadhanStart, $ramadhanEnd)) {
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
Route::prefix('kesehatan')->name('kesehatan.')->group(function () {
    Route::get('/', [KesehatanGuestController::class, 'index'])->name('index');
    Route::get('/daftar', [KesehatanGuestController::class, 'create'])->name('daftar');
    Route::post('/daftar', [KesehatanGuestController::class, 'store'])->name('store');

    // Export
    Route::get('/export/donor-darah', [KesehatanGuestController::class, 'exportDonorDarah'])->name('export.donor');
    Route::get('/export/cek-kesehatan', [KesehatanGuestController::class, 'exportCekKesehatan'])->name('export.cek-kesehatan');
    Route::get('/export/cek-katarak', [KesehatanGuestController::class, 'exportCekKatarak'])->name('export.cek-katarak');
});

Route::get('/program-kesehatan/success', function () {
    return view('masjid.' . masjid() . '.guest.program-kesehatan.success');
})->name('kesehatan.success');

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

    // Prefix admin
    Route::prefix('admin')->group(function () {

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
        Route::post('profil/', [ProfilMasjidController::class, 'updateProfil'])->name('admin.profil.update');
        Route::post('/pengurus', [ProfilMasjidController::class, 'storePengurus'])->name('admin.profil.pengurus.store');
        Route::get('/pengurus/{id}', [ProfilMasjidController::class, 'editPengurus'])->name('admin.profil.pengurus.edit');
        Route::put('/pengurus/{id}', [ProfilMasjidController::class, 'updatePengurus'])->name('admin.profil.pengurus.update');
        Route::delete('/pengurus/{id}', [ProfilMasjidController::class, 'destroyPengurus'])->name('admin.profil.pengurus.destroy');
        Route::post('/pengurus/reorder', [ProfilMasjidController::class, 'reorderPengurus'])->name('admin.profil.pengurus.reorder');

        // Banner
        Route::get('banner',        [BannerController::class, 'index'])->name('admin.banner.index');
        Route::get('banner/data',   [BannerController::class, 'data'])->name('admin.banner.data');
        Route::post('banner',       [BannerController::class, 'store'])->name('admin.banner.store');
        Route::get('banner/{id}',   [BannerController::class, 'edit'])->name('admin.banner.edit');
        Route::put('banner/{id}',   [BannerController::class, 'update'])->name('admin.banner.update');
        Route::delete('banner/{id}',[BannerController::class, 'destroy'])->name('admin.banner.destroy');

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

        // Pengumuman
        Route::get('pengumuman', [PengumumanController::class, 'index'])->name('admin.pengumuman.index');
        Route::get('pengumuman/data', [PengumumanController::class, 'data'])->name('admin.pengumuman.data');
        Route::post('pengumuman', [PengumumanController::class, 'store'])->name('admin.pengumuman.store');
        Route::get('pengumuman/{id}', [PengumumanController::class, 'edit'])->name('admin.pengumuman.edit');
        Route::put('pengumuman/{id}', [PengumumanController::class, 'update'])->name('admin.pengumuman.update');
        Route::delete('pengumuman/{id}', [PengumumanController::class, 'destroy'])->name('admin.pengumuman.destroy');

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

            // Data untuk semua tab (saldo, penerima, penerimaan, realisasi)
            Route::get('/data', [DanaTerikatController::class, 'data'])->name('data');

            Route::get('/akun-options', [DanaTerikatController::class, 'akunOptions'])->name('options');

            Route::post('/penerimaan/store', [DanaTerikatController::class, 'storePenerimaan'])->name('penerimaan.store');

            Route::post('/penerima/store', [DanaTerikatController::class, 'storePenerima'])->name('penerima.store');
            Route::put('/penerima/update/{id}', [DanaTerikatController::class, 'updatePenerima'])->name('penerima.update');
            Route::get('/penerima/show', [DanaTerikatController::class, 'showPenerima'])->name('penerima.show');
            Route::get('/penerima/check-nama', [DanaTerikatController::class, 'cekNamaPenerima'])->name('penerima.check-nama');
            Route::delete('penerima/{id}',[DanaTerikatController::class, 'destroyPenerima'])->name('penerima.destroy');

            Route::post('/realisasi/store', [DanaTerikatController::class, 'realisasi'])->name('realisasi.store');
            Route::post('/koreksi/realisasi/store', [DanaTerikatController::class, 'koreksiStore'])->name('koreksi.realisasi.store');
            Route::get('/realisasi/penerima-aktif', [DanaTerikatController::class, 'penerimaAktif'])->name('realisasi.penerima-aktif');

            Route::post('/program/store', [DanaTerikatController::class, 'storeProgram'])->name('program.store');

            Route::get('/kwitansi/{id}', [DanaTerikatController::class, 'kwitansi'])->name('kwitansi');

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
            
            if (!$prev) {
                return response()->json(['success' => false, 'data' => null]);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'infaq_ramadan_saldo_sekarang'       => $prev->infaq_ramadan_saldo_sekarang ?? 0,
                    'ifthor_saldo_sekarang'               => $prev->ifthor_saldo_sekarang ?? 0,
                    'santunan_yatim_terkumpul_kemarin'    => ($prev->santunan_yatim_terkumpul_kemarin ?? 0) + collect($prev->santunan_yatim_penerimaan_hari_ini ?? [])->sum('nominal'),
                    'paket_sembako_terkumpul_kemarin'     => ($prev->paket_sembako_terkumpul_kemarin ?? 0) + collect($prev->paket_sembako_penerimaan_hari_ini ?? [])->sum('nominal'),
                    'gebyar_anak_terkumpul_kemarin'       => ($prev->gebyar_anak_terkumpul_kemarin ?? 0) + collect($prev->gebyar_anak_penerimaan_hari_ini ?? [])->sum('nominal'),
                    // tambahkan jika ada field kumulatif lain
                ]
            ]);
        })->name('admin.ramadhan.laporan-harian.prev');
});

require __DIR__.'/auth.php';
