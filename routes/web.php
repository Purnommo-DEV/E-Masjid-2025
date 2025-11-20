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
use App\Http\Controllers\Admin\KeuanganController;

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
        Route::get('berita/{id}', [BeritaController::class, 'edit'])->name('admin.berita.edit');
        Route::put('berita/{id}', [BeritaController::class, 'update'])->name('admin.berita.update');
        Route::delete('berita/{id}', [BeritaController::class, 'destroy'])->name('admin.berita.destroy');

        // Acara
        Route::get('acara', [AcaraController::class, 'index'])->name('admin.acara.index');
        Route::get('acara/data', [AcaraController::class, 'data'])->name('admin.acara.data');
        Route::post('acara', [AcaraController::class, 'store'])->name('admin.acara.store');
        Route::get('acara/{id}', [AcaraController::class, 'edit'])->name('admin.acara.edit');
        Route::put('acara/{id}', [AcaraController::class, 'update'])->name('admin.acara.update');
        Route::delete('acara/{id}', [AcaraController::class, 'destroy'])->name('admin.acara.destroy');

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
        Route::get('/keuangan', [KeuanganController::class, 'index'])->name('admin.keuangan.index');
        Route::post('/saldo', [KeuanganController::class, 'storeSaldoAwal'])->name('admin.keuangan.saldo');
        Route::post('/', [KeuanganController::class, 'storeTransaksi'])->name('admin.keuangan.store');
        Route::get('/data', [KeuanganController::class, 'data'])->name('admin.keuangan.data');
        Route::get('/{id}/edit', [KeuanganController::class, 'editTransaksi'])->name('admin.keuangan.edit');
        Route::put('/{id}', [KeuanganController::class, 'updateTransaksi'])->name('admin.keuangan.update');
        Route::delete('/{id}', [KeuanganController::class, 'destroyTransaksi'])->name('admin.keuangan.destroy');

        Route::get('/kotak', [KeuanganController::class, 'kotak'])->name('admin.keuangan.kotak');
        Route::post('/kotak', [KeuanganController::class, 'storeKotak'])->name('admin.keuangan.kotak.store');
        Route::get('/laporan', [KeuanganController::class, 'laporan'])->name('admin.keuangan.laporan');
        Route::get('/laporan/pdf', [KeuanganController::class, 'exportPdf'])->name('admin.keuangan.laporan.pdf');
        Route::get('/saldo/check', [KeuanganController::class, 'cekSaldoAwal'])->name('admin.keuangan.saldo.check');

        Route::get('/keuangan/kotak/list', [KeuanganController::class, 'getKotakList'])->name('admin.keuangan.kotak.list');
        Route::post('/keuangan/kotak/recount', [KeuanganController::class, 'recountKotak'])->name('admin.keuangan.kotak.recount');
    });
});

require __DIR__.'/auth.php';
