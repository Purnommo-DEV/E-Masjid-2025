@extends('masjid.master')
@section('title', 'Dana Terikat & Program Rutin')

@section('content')
<div class="min-h-[70vh] bg-base-200/60 py-6">
    <div class="max-w-7xl mx-auto px-4 lg:px-0">

        {{-- CARD UTAMA --}}
        <div
            class="bg-base-100 rounded-3xl shadow-[0_18px_45px_rgba(15,23,42,0.18)] border border-base-300/70 overflow-hidden"
        >

            {{-- HEADER --}}
            <div
                class="px-6 lg:px-8 py-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between text-white"
                style="background: linear-gradient(90deg, #059669 0%, #10b981 100%);"
            >
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 rounded-2xl bg-white/15 flex items-center justify-center shadow-md">
                        <i class="fas fa-heart text-red-300 text-lg"></i>
                    </div>
                    <div>
                        <h1 class="text-lg lg:text-2xl font-semibold">
                            Dana Terikat &amp; Program Rutin
                        </h1>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 justify-start lg:justify-end">

                    <!-- Terima Dana -->
                    <button type="button"
                            class="btn btn-sm rounded-full shadow-sm bg-emerald-600 hover:bg-emerald-700 text-white normal-case font-medium flex items-center gap-2"
                            data-modal-target="#modalTerimaDana">
                        <i data-lucide="banknote" class="w-4 h-4"></i>
                        Terima Dana
                    </button>

                    <!-- Realisasi -->
                    <button type="button"
                            class="btn btn-sm rounded-full shadow-sm bg-base-200 hover:bg-base-300 normal-case font-medium flex items-center gap-2"
                            data-modal-target="#modalRealisasi">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        Realisasi
                    </button>

                    <!-- Tambah Penerima -->
                    <button type="button"
                            class="btn btn-sm rounded-full shadow-sm bg-base-200 hover:bg-base-300 normal-case font-medium flex items-center gap-2"
                            data-modal-target="#modalPenerima">
                        <i data-lucide="user-plus" class="w-4 h-4"></i>
                        Tambah Penerima
                    </button>

                    <!-- Program -->
                    <button type="button"
                            class="btn btn-sm rounded-full shadow-sm bg-base-200 hover:bg-base-300 normal-case font-medium flex items-center gap-2"
                            onclick="bukaModalProgram()">
                        <i data-lucide="list" class="w-4 h-4"></i>
                        Program
                    </button>

                    <!-- Dana Alokasi -->
                    <button type="button"
                            class="btn btn-sm rounded-full shadow-sm bg-purple-200 hover:bg-purple-300 normal-case font-medium flex items-center gap-2"
                            onclick="bukaModalAlokasi()">
                        <i data-lucide="arrow-right-left" class="w-4 h-4"></i>
                        Alokasi Dana
                    </button>

                </div>

            </div>

            {{-- BODY --}}
            <div class="p-5 lg:p-7 space-y-6">

                {{-- FILTER --}}
                <div
                    class="bg-base-100/90 border border-base-300/80 rounded-2xl p-4 lg:p-5 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 shadow-sm"
                >
                    <div class="flex-1 grid grid-cols-1 lg:grid-cols-4 gap-4">
                        <div class="lg:col-span-2 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">Program</span>
                                <span class="hidden lg:inline-flex text-[11px] text-base-content/60">
                                    Filter program aktif
                                </span>
                            </div>
                            <select
                                id="filterProgram"
                                class="select select-bordered w-full"
                            >
                                <option value="">Semua Program</option>
                                @foreach(\App\Models\DanaTerikatProgram::where('aktif',1)->orderBy('kode_program')->get() as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->kode_program }} — {{ $p->nama_program }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        {{-- 🔥 TAMBAHKAN FILTER BULAN --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">Bulan</span>
                            </div>
                            <select id="filterBulanSaldo" class="select select-bordered w-full">
                                <option value="">Semua Bulan</option>
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                                        {{ Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-semibold">Tahun Program</span>
                                <span class="hidden lg:inline-flex text-[11px] text-base-content/60">
                                    Tahun berjalan
                                </span>
                            </div>
                            <select
                                id="filterTahun"
                                class="select select-bordered w-full"
                            >
                                <option value="">Semua Tahun</option>
                                @for($y = date('Y')+1; $y >= 2024; $y--)
                                    <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                    </div>
                    <div class="text-xs text-base-content/60 hidden lg:block">
                        <p><i class="fas fa-info-circle mr-1"></i>Tips:</p>
                        <p>- Pilih program &amp; tahun untuk memfilter semua tab.</p>
                    </div>
                </div>

                {{-- TABS --}}
                <div>
                    <div class="flex flex-wrap gap-2 border-b border-base-300 mb-4">
                        <button
                            class="tab tab-bordered tab-active text-xs md:text-sm lg:text-base rounded-t-2xl flex items-center gap-2"
                            data-tab-target="#tab-saldo"
                        >
                            <i data-lucide="wallet" class="w-4 h-4 hidden md:inline-block"></i>
                            Saldo Dana
                        </button>

                        <button
                            class="tab tab-bordered text-xs md:text-sm lg:text-base rounded-t-2xl flex items-center gap-2"
                            data-tab-target="#tab-penerima"
                        >
                            <i data-lucide="users" class="w-4 h-4 hidden md:inline-block"></i>
                            Daftar Penerima
                        </button>

                        <button
                            class="tab tab-bordered text-xs md:text-sm lg:text-base rounded-t-2xl flex items-center gap-2"
                            data-tab-target="#tab-penerimaan"
                        >
                            <i data-lucide="hand-coins" class="w-4 h-4 hidden md:inline-block"></i>
                            Penerimaan Dana
                        </button>

                        <button
                            class="tab tab-bordered text-xs md:text-sm lg:text-base rounded-t-2xl flex items-center gap-2"
                            data-tab-target="#tab-realisasi"
                        >
                            <i data-lucide="calendar-check" class="w-4 h-4 hidden md:inline-block"></i>
                            Realisasi Bulanan
                        </button>

                        <button
                            class="tab tab-bordered text-xs md:text-sm lg:text-base rounded-t-2xl flex items-center gap-2"
                            data-tab-target="#tab-status-bulanan"
                        >
                            <i data-lucide="calendar-check" class="w-4 h-4 hidden md:inline-block"></i>
                            Status Bulanan
                            <span id="badgePendingStatus" class="badge badge-warning badge-sm hidden">!</span>
                        </button>
                    </div>

                    <div class="space-y-5">
                        {{-- TAB SALDO --}}
                        <div id="tab-saldo" data-tab-content>
                            <div class="flex flex-wrap items-center justify-between mb-2 gap-2">
                                <h2 class="font-semibold text-base md:text-lg flex items-center gap-2">
                                    <span class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                        <i class="fas fa-coins text-emerald-600 text-sm"></i>
                                    </span>
                                    <span>Ringkasan Saldo Dana Terikat</span>
                                </h2>
                                <span class="text-[11px] text-base-content/60">
                                    Data diambil dari perhitungan sistem akuntansi
                                </span>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
                                <table
                                    id="tabelSaldo"
                                    class="min-w-full text-xs md:text-sm"
                                >
                                    <thead class="bg-emerald-600 text-white uppercase tracking-wide text-[11px]">
                                        <tr>
                                            <th class="px-3 py-2 text-left">Program</th>
                                            <th class="px-3 py-2 text-right">Terkumpul</th>
                                            <th class="px-3 py-2 text-right">Realisasi Bulan Ini</th>
                                            <th class="px-3 py-2 text-right">Sisa Dana</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-base-200"></tbody>
                                </table>
                            </div>
                        </div>

                        {{-- TAB PENERIMA --}}
                        <div id="tab-penerima" data-tab-content class="hidden">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2 mb-3">
                                <div>
                                    <h2 class="font-semibold text-base md:text-lg flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-full bg-sky-100 flex items-center justify-center">
                                            <i class="fas fa-users text-sky-600 text-sm"></i>
                                        </span>
                                        <span>Daftar Penerima Santunan</span>
                                    </h2>
                                    <p class="text-xs text-base-content/70 mt-1">
                                        Penerima aktif &amp; non-aktif dengan detail kategori, alamat, dan nominal bulanan.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm md:btn-md btn-primary rounded-full shadow-md"
                                    data-modal-target="#modalPenerima"
                                >
                                    <i class="fas fa-user-plus mr-2"></i>
                                    Penerima Baru
                                </button>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
                                <table id="tabelPenerima" class="min-w-full text-[11px] md:text-xs lg:text-sm">
                                    <thead class="bg-sky-600 text-white uppercase tracking-wide">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-10">No</th>
                                            <th class="px-3 py-2">Tahun</th>
                                            <th class="px-3 py-2">Program</th>
                                            <th class="px-3 py-2">Nama</th>
                                            <th class="px-3 py-2">Kategori</th>
                                            <th class="px-3 py-2">Referensi</th>
                                            <th class="px-3 py-2">Status Yatim</th>
                                            <th class="px-3 py-2">Umur</th>
                                            <th class="px-3 py-2">Alamat</th>
                                            <th class="px-3 py-2 text-right whitespace-nowrap">Nominal/Bulan</th>
                                            <th class="px-3 py-2 text-center whitespace-nowrap">Status Aktif</th>
                                            <th class="px-3 py-2 text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <p class="mt-2 text-[11px] text-base-content/60">
                                <i class="fas fa-info-circle mr-1"></i>
                                Baris anak yatim diberi warna lembut berdasarkan referensi.
                            </p>
                        </div>

                        {{-- TAB PENERIMAAN --}}
                        <div id="tab-penerimaan" data-tab-content class="hidden">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                <div>
                                    <h2 class="font-semibold text-base md:text-lg flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                            <i class="fas fa-arrow-down text-emerald-600 text-sm"></i>
                                        </span>
                                        <span>Riwayat Penerimaan Dana Terikat</span>
                                    </h2>
                                    <p class="text-xs text-base-content/70 mt-1">
                                        Semua penerimaan berdasarkan program, donatur, dan tanggal.
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
                                <table
                                    id="tabelPenerimaan"
                                    class="min-w-full text-xs md:text-sm"
                                >
                                    <thead class="bg-emerald-500 text-white uppercase tracking-wide">
                                        <tr>
                                            <th class="px-3 py-2">Tanggal</th>
                                            <th class="px-3 py-2">Program</th>
                                            <th class="px-3 py-2">Donatur</th>
                                            <th class="px-3 py-2 text-right">Jumlah</th>
                                            <th class="px-3 py-2">Keterangan</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        {{-- TAB REALISASI --}}
                        <div id="tab-realisasi" data-tab-content class="hidden">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-3">
                                <div>
                                    <h2 class="font-semibold text-base md:text-lg flex items-center gap-2">
                                        <span class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center">
                                            <i class="fas fa-calendar-check text-amber-600 text-sm"></i>
                                        </span>
                                        <span>Realisasi Bulanan &amp; Koreksi</span>
                                    </h2>
                                    <p class="text-[11px] md:text-xs text-base-content/70 mt-1">
                                        Realisasi hanya sekali per bulan per program.
                                        Gunakan koreksi untuk penyesuaian bulan sebelumnya.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    class="btn btn-sm md:btn-md btn-warning rounded-full shadow-md"
                                    data-modal-target="#modalKoreksiRealisasi"
                                >
                                    <i class="fas fa-edit mr-2"></i>
                                    Koreksi Realisasi Bulan Lalu
                                </button>
                            </div>

                            <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
                                <table id="tabelRealisasi" class="min-w-full text-[11px] md:text-xs lg:text-sm">
                                    <thead class="bg-amber-400 text-amber-950 uppercase tracking-wide">
                                        <tr>
                                            <th class="px-3 py-2 text-center w-10">No</th>
                                            <th class="px-3 py-2">Bulan & Program</th>
                                            <th class="px-3 py-2">Penerima</th>
                                            <th class="px-3 py-2 text-right whitespace-nowrap">Jumlah</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>

                            <p class="mt-2 text-[11px] text-base-content/60 flex flex-wrap gap-3">
                                <span class="flex items-center gap-1">
                                    <span class="badge badge-xs badge-success"></span>
                                    <span>Normal = realisasi rutin bulanan</span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="badge badge-xs badge-warning"></span>
                                    <span>Koreksi = penyesuaian (tambah/kurang) atas realisasi</span>
                                </span>
                            </p>
                        </div>

                        {{-- TAB STATUS BULANAN --}}
                        <div id="tab-status-bulanan" data-tab-content class="hidden">
                            <div class="flex flex-wrap justify-between items-center gap-3 mb-4">
                                <div class="flex items-center gap-3 flex-wrap">
                                    <div>
                                        <label class="label-text text-xs font-medium">Bulan</label>
                                        <select id="filterBulanStatus" class="select select-bordered select-sm">
                                            <option value="">— Pilih Bulan —</option>  {{-- BARU: opsi kosong --}}
                                            @for($i=1; $i<=12; $i++)
                                                <option value="{{ $i }}">
                                                    {{ Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div>
                                        <label class="label-text text-xs font-medium">Tahun</label>
                                        <select id="filterTahunStatus" class="select select-bordered select-sm">
                                            <option value="">— Pilih Tahun —</option>  {{-- BARU: opsi kosong --}}
                                            @for($y = date('Y')+1; $y >= 2024; $y--)
                                                <option value="{{ $y }}">
                                                    {{ $y }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <button class="btn btn-sm btn-primary mt-2" onclick="loadStatusBulanan()">
                                        <i data-lucide="refresh-cw" class="w-4 h-4"></i>
                                        Muat
                                    </button>
                                </div>
                                
                                <div class="flex gap-2 flex-wrap">
                                    <button class="btn btn-sm btn-success" onclick="realisasiDariStatus()">
                                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                                        Realisasi dari Status
                                    </button>
                                    <button class="btn btn-sm btn-warning" onclick="copyStatusDariBulanLalu()">
                                        <i data-lucide="copy" class="w-4 h-4"></i>
                                        Copy dari Bulan Lalu
                                    </button>
                                    {{-- <button class="btn btn-sm btn-ghost" onclick="exportStatusExcel()">
                                        <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                                        Export Excel
                                    </button> --}}
                                </div>
                            </div>

                            {{-- Statistik --}}
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                <div class="stat bg-base-200 rounded-2xl p-3">
                                    <div class="stat-title text-xs">Total Penerima</div>
                                    <div class="stat-value text-lg" id="statTotal">0</div>
                                </div>
                                <div class="stat bg-success/10 rounded-2xl p-3">
                                    <div class="stat-title text-xs">✅ Dapat</div>
                                    <div class="stat-value text-lg text-success" id="statDapat">0</div>
                                </div>
                                <div class="stat bg-error/10 rounded-2xl p-3">
                                    <div class="stat-title text-xs">❌ Tidak Dapat</div>
                                    <div class="stat-value text-lg text-error" id="statTidakDapat">0</div>
                                </div>
                                <div class="stat bg-warning/10 rounded-2xl p-3">
                                    <div class="stat-title text-xs">⏳ Belum Diverifikasi</div>
                                    <div class="stat-value text-lg text-warning" id="statBelumVerifikasi">0</div>
                                </div>
                            </div>

                            {{-- Tabel Status --}}
                            <div class="overflow-x-auto rounded-2xl border border-base-300 bg-base-100">
                                <table class="table table-sm table-zebra" id="tabelStatusBulanan">
                                    <thead class="bg-primary text-primary-content">
                                        <tr>
                                            <th class="w-10">
                                                <input type="checkbox" id="selectAllStatus" class="checkbox checkbox-xs checkbox-primary">
                                            </th>
                                            <th>Nama</th>
                                            <th>Program</th>
                                            <th class="text-right">Nominal Master</th>
                                            <th class="text-center">Status</th>
                                            <th class="text-right">Nominal Aktual</th>
                                            <th>Verifikator</th>
                                            <th>Keterangan</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="9" class="text-center py-8 text-base-content/60">
                                                <i data-lucide="loader-circle" class="w-6 h-6 animate-spin mx-auto mb-2"></i>
                                                <p>Loading data...</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Keterangan --}}
                            <div class="mt-3 text-xs text-base-content/60 flex flex-wrap gap-4">
                                <span class="flex items-center gap-1">
                                    <span class="badge badge-xs badge-warning"></span>
                                    <span>Baris kuning = data diubah (nama/nominal berbeda dari master)</span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="badge badge-xs badge-success"></span>
                                    <span>Status Dapat</span>
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="badge badge-xs badge-error"></span>
                                    <span>Status Tidak Dapat</span>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

            </div> {{-- end body --}}
        </div>
    </div>
</div>

{{-- ====================== MODAL TERIMA DANA ====================== --}}
<!-- MODAL TERIMA DANA — VERSI KECIL, PADAT, CANTIK -->
<div id="modalTerimaDana"
     class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm"
     aria-hidden="true">
    <div class="w-full max-w-xl mx-4 my-8"> <!-- dari max-w-2xl jadi max-w-xl = lebih kecil -->
        <form id="formTerimaDana"
              class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden">

            @csrf

            <!-- Header Hijau -->
            <div class="px-5 py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white flex items-center justify-between rounded-t-3xl">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    Terima Dana Terikat
                </h3>
                <button type="button"
                        class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20"
                        onclick="document.getElementById('modalTerimaDana').classList.add('hidden')">
                    x
                </button>
            </div>

            <!-- Body Form — SEMUA DIKECILIN -->
            <div class="p-5 space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Program -->
                    <div>
                        <label class="label label-text text-xs font-medium">Program <span class="text-error">*</span></label>
                        <select name="program_id" class="select select-bordered select-sm w-full" required>
                            <option value="">— Pilih Program —</option>
                            @foreach(\App\Models\DanaTerikatProgram::where('aktif',1)->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->kode_program }} — {{ $p->nama_program }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <!-- 🔥 JENIS DANA -->
                    <div>
                        <label class="label label-text text-xs font-medium">Jenis Dana <span class="text-error">*</span></label>
                        <select name="jenis_dana" class="select select-bordered select-sm w-full" required>
                            <option value="dana_terikat">Infaq Terikat (Yatim/Dhuafa)</option>
                            <option value="zakat_maal">Zakat Maal</option>
                            <option value="zakat_fitrah">Zakat Fitrah</option>
                            <option value="fidyah">Fidyah</option>
                            <option value="infaq_umum">Infaq Umum</option>
                            <option value="shodaqoh">Shodaqoh</option>
                            <option value="dana_titipan">📦 Dana Titipan (Belum Dikelompokkan)</option>
                        </select>
                    </div>

                    <!-- Tanggal -->
                    <div>
                        <label class="label label-text text-xs font-medium">Tanggal</label>
                        <input type="date" name="tanggal" value="{{ date('Y-m-d') }}"
                               class="input input-bordered input-sm w-full" required>
                    </div>

                    <!-- Jumlah -->
                    <div>
                        <label class="label label-text text-xs font-medium">Jumlah (Rp)</label>
                        <input type="text" name="jumlah"
                               class="input input-bordered input-sm w-full text-right font-mono ribuan"
                               placeholder="0" required>
                    </div>

                    <!-- Nama Donatur -->
                    <div>
                        <label class="label label-text text-xs font-medium">Nama Donatur</label>
                        <input type="text" name="donatur_nama"
                               class="input input-bordered input-sm w-full" required>
                    </div>

                    <!-- Kontak -->
                    <div>
                        <label class="label label-text text-xs font-medium">Kontak (Opsional)</label>
                        <input type="text" name="donatur_kontak"
                               placeholder="Email / No. HP"
                               class="input input-bordered input-sm w-full">
                    </div>

                    <!-- ===== CHECKBOX SALDO AWAL ===== -->
                    <div class="md:col-span-2">
                        <label class="label cursor-pointer justify-start gap-3 py-2">
                            <input type="checkbox" 
                                   name="is_saldo_awal" 
                                   value="1" 
                                   id="isSaldoAwalCheckbox"
                                   class="checkbox checkbox-sm checkbox-primary">
                            <span class="label-text text-sm font-medium">
                                Ini adalah <span class="text-primary font-bold">Saldo Awal</span> 
                                (tidak membuat jurnal karena sudah ada dari Saldo Awal)
                            </span>
                        </label>
                        <p class="text-xs text-base-content/50 mt-1 ml-9">
                            Centang jika ini adalah saldo awal program, bukan donasi baru.
                        </p>
                    </div>

                    <!-- Keterangan (full width) -->
                    <div class="md:col-span-2">
                        <label class="label label-text text-xs font-medium">Keterangan</label>
                        <textarea name="keterangan" rows="2"
                                  class="textarea textarea-bordered textarea-sm w-full resize-none"
                                  placeholder="Opsional..."></textarea>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="px-5 py-4 bg-base-200 rounded-b-3xl flex justify-end gap-3">
                <button type="button" class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('modalTerimaDana').classList.add('hidden')">
                    Batal
                </button>
                <button type="submit" class="btn btn-success btn-sm rounded-full px-8">
                    Simpan & Catat Jurnal
                </button>
            </div>

        </form>
    </div>
</div>

{{-- ====================== MODAL ALOKASI DANA ====================== --}}
<div id="modalAlokasi" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md mx-4 max-h-[90vh]">  {{-- 🔥 TAMBAHKAN max-h-[90vh] --}}
        <form id="formAlokasi" class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden flex flex-col h-full">
            @csrf

            <!-- Header (FIXED) -->
            <div class="px-6 py-4 bg-purple-600 text-white flex items-center justify-between flex-shrink-0 rounded-t-3xl">
                <h5 class="font-semibold text-lg flex items-center gap-2">
                    <i data-lucide="arrow-right-left" class="w-5 h-5"></i>
                    Alokasi Dana
                </h5>
                <button type="button" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20"
                        onclick="tutupModalAlokasi()">✕</button>
            </div>

            <!-- Body (BISA SCROLL) -->
            <div class="p-6 space-y-4 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">  {{-- 🔥 TAMBAHKAN overflow-y-auto --}}
                <div>
                    <label class="text-sm font-medium">Program</label>
                    <select name="program_id" class="select select-bordered w-full" required>
                        @foreach(\App\Models\DanaTerikatProgram::where('aktif',1)->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->kode_program }} — {{ $p->nama_program }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium">Tanggal Alokasi</label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="input input-bordered w-full" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Dari Akun</label>
                        <select name="akun_sumber_id" class="select select-bordered w-full" required>
                            <option value="">Pilih Sumber</option>
                            @foreach(\App\Models\AkunKeuangan::where('kode', '20002')->get() as $a)
                                <option value="{{ $a->id }}" selected>{{ $a->kode }} - {{ $a->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-medium">Ke Akun</label>
                        <select name="akun_tujuan_id" class="select select-bordered w-full" required>
                            <option value="">Pilih Tujuan</option>
                            @foreach(\App\Models\AkunKeuangan::where('kode', '20004')->get() as $a)
                                <option value="{{ $a->id }}" selected>{{ $a->kode }} - {{ $a->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium">Jumlah Alokasi (Rp)</label>
                    <input type="text" name="jumlah_alokasi" class="input input-bordered w-full text-right ribuan" placeholder="0" required>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium">Per Bulan (Rp)</label>
                        <input type="text" id="alokasiPerBulan" class="input input-bordered w-full text-right ribuan" placeholder="10.700.000">
                    </div>
                    <div>
                        <label class="text-sm font-medium">Jumlah Bulan</label>
                        <select id="alokasiBulan" class="select select-bordered w-full">
                            <option value="1">1 Bulan</option>
                            <option value="2">2 Bulan</option>
                            <option value="3" selected>3 Bulan</option>
                            <option value="4">4 Bulan</option>
                            <option value="6">6 Bulan</option>
                        </select>
                    </div>
                </div>

                <button type="button" id="btnHitungAlokasi" class="btn btn-sm btn-ghost w-full text-purple-600">
                    <i data-lucide="calculator" class="w-4 h-4"></i>
                    Hitung Total
                </button>

                <div id="hasilAlokasi" class="hidden bg-base-200 rounded-xl p-3 text-center">
                    <p class="text-sm text-base-content/70">Total Alokasi</p>
                    <p class="text-2xl font-bold text-purple-600" id="totalAlokasi">Rp 0</p>
                </div>

                <div>
                    <label class="text-sm font-medium">Keterangan</label>
                    <textarea name="keterangan" rows="2" class="textarea textarea-bordered w-full" 
                              placeholder="Contoh: Alokasi santunan Yatim & Dhuafa 3 bulan"></textarea>
                </div>
            </div>

            <!-- Footer (FIXED DI BAWAH) -->
            <div class="px-6 py-4 bg-base-200 flex justify-end gap-3 flex-shrink-0 rounded-b-3xl">
                <button type="button" class="btn btn-ghost" onclick="tutupModalAlokasi()">Batal</button>
                <button type="submit" class="btn btn-success rounded-full text-white">Alokasikan</button>
            </div>
        </form>
    </div>
</div>

{{-- ====================== MODAL PENERIMA ====================== --}}
<div id="modalPenerima" class="fixed inset-0 z-50 hidden flex items-start justify-center bg-black/50 backdrop-blur-sm pt-10 pb-10 overflow-y-auto">
    <div class="w-full max-w-2xl mx-4">
        <form id="formPenerima" class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden max-h-[90vh] flex flex-col">
            @csrf
            <input type="hidden" name="id">

            <!-- Header -->
            <div class="px-6 py-4 bg-sky-600 text-white flex items-center justify-between rounded-t-3xl flex-shrink-0">
                <h3 class="font-bold text-lg">Tambah / Edit Penerima</h3>
                <button type="button" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20"
                        onclick="document.getElementById('modalPenerima').classList.add('hidden')">
                    ✕
                </button>
            </div>

            <!-- Body Form (BISA SCROLL) -->
            <div class="p-5 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

                    <!-- Program & Tahun -->
                    <div>
                        <label class="label label-text text-xs font-medium">Program *</label>
                        <select name="program_id" class="select select-bordered select-sm w-full" required>
                            @foreach(\App\Models\DanaTerikatProgram::where('aktif',1)->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->nama_program }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Tahun Program *</label>
                        <select name="tahun_program" class="select select-bordered select-sm w-full" required>
                            @for($y = date('Y')+1; $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>{{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <!-- Kategori Nama -->
                    <div>
                        <label class="label label-text text-xs font-medium">Kategori Penerima *</label>
                        <select name="kategori" id="kategoriPenerima" class="select select-bordered select-sm w-full" required>
                            <option value="yatim">Anak Yatim</option>
                            <option value="dhuafa">Dhuafa</option>
                            <option value="operasional">Biaya Operasional</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Nama Lengkap *</label>
                        <input type="text" name="nama" class="input input-bordered input-sm w-full" required>
                    </div>

                    {{-- REFERENSI --}}
                    <div class="md:col-span-2">
                        <label class="label label-text text-xs font-medium">Referensi</label>
                        <div class="flex gap-2">
                            <select name="referensi_id" id="referensi_id" class="select select-bordered select-sm w-full">
                                <option value="">— Pilih Referensi —</option>
                                @foreach(\App\Models\DanaTerikatReferensi::orderBy('nama')->get() as $ref)
                                    <option value="{{ $ref->id }}">{{ $ref->nama }}</option>
                                @endforeach
                            </select>
                            <button type="button" id="btnTambahReferensi" class="btn btn-sm btn-square btn-outline">+</button>
                        </div>
                    </div>

                    {{-- KHUSUS YATIM --}}
                    <div class="md:col-span-2 kategori-yatim-wrapper">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label label-text text-xs">Tgl Lahir</label>
                                <input type="date" name="tanggal_lahir" class="input input-bordered input-sm w-full">
                            </div>
                            <div>
                                <label class="label label-text text-xs">Umur</label>
                                <input type="text" id="umurDisplay" class="input input-bordered input-sm w-full bg-base-200" readonly>
                            </div>
                        </div>
                        <div class="text-center mt-2">
                            <div id="statusYatimBadge" class="badge badge-ghost badge-sm">Pilih kategori Yatim & isi tgl lahir</div>
                        </div>
                    </div>

                    <!-- Nominal & No HP -->
                    <div>
                        <label class="label label-text text-xs font-medium">Nominal Bulanan (Rp) *</label>
                        <input type="text" name="nominal_bulanan" class="input input-bordered input-sm w-full text-right ribuan" required>
                    </div>
                    <div>
                        <label class="label label-text text-xs">No HP / WA</label>
                        <input type="text" name="no_hp" class="input input-bordered input-sm w-full">
                    </div>

                    <!-- Alamat -->
                    <div class="md:col-span-2">
                        <label class="label label-text text-xs font-medium">Alamat (tanpa RT/RW)</label>
                        <textarea name="alamat" rows="3" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>

                    <!-- ===== KETERANGAN (BARU) ===== -->
                    <div class="md:col-span-2">
                        <label class="label label-text text-xs font-medium">Keterangan</label>
                        <textarea name="keterangan" rows="2" 
                                class="textarea textarea-bordered textarea-sm w-full"
                                placeholder="Catatan tambahan tentang penerima (contoh: biaya operasional RT, keterangan khusus, dll)"></textarea>
                    </div>

                    <!-- RT RW Nama RT + Checkbox -->
                    <div class="md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <div class="md:col-span-2">
                                <label class="label label-text text-xs">RT</label>
                                <input type="text" name="rt" placeholder="01" maxlength="3" class="input input-bordered input-sm w-full text-center">
                            </div>
                            <div class="md:col-span-2">
                                <label class="label label-text text-xs">RW</label>
                                <input type="text" name="rw" placeholder="03" maxlength="3" class="input input-bordered input-sm w-full text-center">
                            </div>
                            <div class="md:col-span-5">
                                <label class="label label-text text-xs">Nama RT</label>
                                <input type="text" name="nama_rt" placeholder="Bpk. Ahmad" class="input input-bordered input-sm w-full">
                            </div>
                            <div class="md:col-span-3 flex items-center">
                                <label class="cursor-pointer label gap-2">
                                    <input type="checkbox" name="status_aktif" value="1" class="checkbox checkbox-success checkbox-sm" checked>
                                    <span class="label-text text-xs font-medium">Aktif bulanan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer (Selalu kelihatan) -->
            <div class="px-6 py-4 bg-base-200 rounded-b-3xl flex justify-end gap-3 flex-shrink-0">
                <button type="button" class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('modalPenerima').classList.add('hidden')">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary btn-sm rounded-full px-8">
                    Simpan Penerima
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ====================== MODAL REFERENSI ====================== --}}
<div id="modalReferensi" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md mx-4 my-8"> <!-- lebih kecil dari lg jadi md -->
        <form id="formReferensi" class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden">
            @csrf
            <input type="hidden" name="id">

            <!-- Header -->
            <div class="px-5 py-3.5 bg-base-300 flex items-center justify-between rounded-t-3xl">
                <h3 class="font-bold text-base" id="modalReferensiTitle">Tambah Referensi</h3>
                <button type="button" class="btn btn-sm btn-circle btn-ghost hover:bg-base-100"
                        onclick="document.getElementById('modalReferensi').classList.add('hidden')">
                    ×
                </button>
            </div>

            <!-- Body Form — SEMUA DIKECILIN -->
            <div class="p-5 space-y-4">

                <!-- Nama Referensi -->
                <div>
                    <label class="label label-text text-xs font-medium text-base-content/80">Nama Referensi *</label>
                    <input type="text" name="nama" placeholder="Masukkan nama" required
                           class="input input-bordered input-sm w-full mt-1">
                </div>

                <!-- Warna Baris -->
                <div>
                    <label class="label label-text text-xs font-medium text-base-content/80">Warna Baris (khusus yatim)</label>
                    <div class="flex items-center gap-3 mt-1">
                        <input type="color" name="warna" value="#fef3c7"
                               class="input input-bordered w-20 h-9 cursor-pointer">
                        <span class="text-xs text-base-content/60">Pilih warna background baris</span>
                    </div>
                </div>

                <!-- Daftar Referensi -->
                <div class="border-t border-base-300 pt-4 -mx-5 px-5">
                    <h4 class="font-semibold text-sm mb-3 text-base-content/90">Daftar Referensi</h4>
                    <div class="max-h-52 overflow-y-auto rounded-lg border border-base-300 bg-base-50">
                        <table id="tabelReferensi" class="table table-xs w-full">
                            <thead class="bg-base-200 text-xs uppercase">
                                <tr>
                                    <th class="px-3 py-2 text-left">Nama</th>
                                    <th class="px-3 py-2 text-center w-20">Warna</th>
                                    <th class="px-3 py-2 text-center w-20">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="text-xs">
                                <!-- diisi via JS -->
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-5 py-4 bg-base-200/80 rounded-b-3xl flex justify-end gap-3">
                <button type="button" class="btn btn-ghost btn-sm"
                        onclick="document.getElementById('modalReferensi').classList.add('hidden')">
                    Batal
                </button>
                <button type="submit" class="btn btn-neutral btn-sm rounded-full px-7" id="btnSubmitReferensi">
                    Simpan Referensi
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ====================== MODAL REALISASI ====================== --}}
<div
    id="modalRealisasi"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-md mx-4">
        <form
            id="formRealisasi"
            class="bg-base-100 rounded-3xl shadow-[0_18px_45px_rgba(15,23,42,0.35)] border border-base-200/80 ring-1 ring-base-300/60 overflow-hidden"
        >
            @csrf
            <div class="px-6 py-4 bg-error text-error-content flex items-center justify-between">
                <h5 class="font-semibold">Realisasi Santunan Bulanan</h5>
                <button
                    type="button"
                    class="btn btn-xs btn-circle btn-ghost text-error-content"
                    data-modal-close="#modalRealisasi"
                >
                    ✕
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="space-y-1">
                    <label class="text-sm font-medium">Program</label>
                    <select
                        name="program_id"
                        class="select select-bordered w-full"
                        required
                    >
                        <option value="">— Pilih Program —</option>
                        @foreach(\App\Models\DanaTerikatProgram::where('aktif',1)->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->nama_program }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Bulan</label>
                        <select
                            name="bulan"
                            class="select select-bordered w-full"
                            required
                        >
                            @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $i == date('n') ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Tahun</label>
                        <select
                            name="tahun"
                            class="select select-bordered w-full"
                            required
                        >
                            @for($y = date('Y')+1; $y >= 2024; $y--)
                                <option value="{{ $y }}" {{ $y == date('Y') ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-base-200/70 flex justify-end">
                <button
                    type="submit"
                    class="btn btn-error btn-lg normal-case rounded-full"
                >
                    Realisasi Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ====================== MODAL KOREKSI REALISASI ====================== --}}
<div
    id="modalKoreksiRealisasi"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 backdrop-blur-sm">
    <div class="w-full max-w-3xl mx-4">
        <form
            id="formKoreksi"
            class="bg-base-100 rounded-3xl shadow-[0_18px_45px_rgba(15,23,42,0.35)] border border-base-200/80 ring-1 ring-base-300/60 overflow-hidden"
        >
            @csrf
            <div class="px-6 py-4 bg-amber-400 text-amber-950 flex items-center justify-between">
                <h5 class="font-semibold flex items-center gap-2">
                    <i class="fas fa-edit"></i>
                    <span>Koreksi Realisasi Dana Terikat</span>
                </h5>
                <button
                    type="button"
                    class="btn btn-xs btn-circle btn-ghost text-amber-950"
                    data-modal-close="#modalKoreksiRealisasi"
                >
                    ✕
                </button>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-sm font-medium">Program</label>
                        <select
                            name="program_id_koreksi"
                            class="select select-bordered w-full"
                            required
                        >
                            <option value="">— Pilih Program —</option>
                            @foreach(\App\Models\DanaTerikatProgram::where('aktif',1)->get() as $p)
                                <option value="{{ $p->id }}">{{ $p->kode_program }} — {{ $p->nama_program }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1">
                            <label class="text-sm font-medium">Bulan <span class="text-error">*</span></label>
                            <select name="bulan_koreksi" class="select select-bordered w-full" required>  {{-- UBAH: bulan_koreksi --}}
                                <option value="">— Pilih Bulan —</option>
                                @for($i=1; $i<=12; $i++)
                                    <option value="{{ $i }}">
                                        {{ Carbon\Carbon::create()->month($i)->translatedFormat('F') }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium">Tahun <span class="text-error">*</span></label>
                            <select name="tahun_koreksi" class="select select-bordered w-full" required>
                                <option value="">— Pilih Tahun —</option>
                                @for($y = date('Y') + 1; $y >= 2024; $y--)
                                    <option value="{{ $y }}">
                                        {{ $y }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        
                        
                    </div>

                    <div class="md:col-span-2 space-y-1" id="divPenerima">
                        <label class="text-sm font-medium">Penerima (Opsional)</label>
                        <select
                            name="penerima_id"
                            class="select select-bordered w-full"
                        >
                            <option value="">— Koreksi Umum (tidak spesifik penerima) —</option>
                        </select>
                        <small class="text-xs text-base-content/70">
                            Pilih penerima jika koreksi untuk orang tertentu
                        </small>
                    </div>

                    <div class="space-y-1">
                        <label class="text-sm font-medium">Jumlah Koreksi (Rp)</label>
                        <input
                            type="text"
                            name="jumlah_koreksi"
                            class="input input-bordered w-full text-right font-semibold ribuan-koreksi"  {{-- UBAH CLASS! --}}
                            placeholder="500000 atau -200000"
                            required
                        >
                        <small class="text-xs text-success block">Positif = tambah santunan</small>
                        <small class="text-xs text-error block">Negatif = kurangi / koreksi kelebihan</small>
                    </div>

                    <div class="md:col-span-2 space-y-1">
                        <label class="text-sm font-medium">Keterangan Otomatis (bisa diedit)</label>
                        <textarea
                            name="keterangan"
                            rows="2"
                            class="textarea textarea-bordered w-full"
                            placeholder="Akan otomatis terisi jika pilih penerima"
                        ></textarea>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 bg-base-200/70 flex justify-end">
                <button
                    type="submit"
                    class="btn btn-warning btn-lg normal-case rounded-full"
                >
                    <i class="fas fa-save mr-2"></i>
                    Catat Koreksi &amp; Buat Jurnal
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ====================== MODAL PROGRAM BARU ====================== --}}
<div id="modalProgramBaru" class="fixed inset-0 z-50 hidden flex items-start justify-center bg-black/50 backdrop-blur-sm pt-8 pb-8 overflow-y-auto">
    <div class="w-full max-w-md mx-4 scale-95 opacity-0 transition-all duration-200" id="modalProgramContent">
        <form id="formProgramBaru" class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden">
            @csrf

            <!-- Header -->
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white flex items-center justify-between flex-shrink-0">
                <h5 class="font-semibold text-lg">Tambah Program Dana Terikat</h5>
                <button type="button" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20"
                        onclick="tutupModalProgram()">✕</button>
            </div>

            <!-- Body (BISA SCROLL) -->
            <div class="p-6 space-y-5 overflow-y-auto flex-1" style="max-height: calc(90vh - 130px);">
                <div class="space-y-2">
                    <label class="label"><span class="label-text font-medium">Kode Program <span class="text-error">*</span></span></label>
                    <input type="text" name="kode_program" class="input input-bordered w-full uppercase" required />
                </div>
                <div class="space-y-2">
                    <label class="label"><span class="label-text font-medium">Nama Program <span class="text-error">*</span></span></label>
                    <input type="text" name="nama_program" class="input input-bordered w-full" required />
                </div>
                <div class="space-y-2">
                    <label class="label"><span class="label-text font-medium">Akun Liabilitas di Neraca <span class="text-error">*</span></span></label>
                    <select name="akun_liabilitas_id" id="selectAkunLiabilitas" class="select select-bordered w-full" required>
                        <option value="">Memuat data akun...</option>
                    </select>
                    <div class="label"><span class="label-text-alt text-base-content/60">Hanya akun liabilitas (kode mulai 2.xx)</span></div>
                </div>
                <div class="space-y-2">
                    <label class="label">
                        <span class="label-text font-medium">
                            Akun Penampung Dana <span class="text-error">*</span>
                        </span>
                    </label>
                    <select name="akun_aset_id" id="selectAkunAset" class="select select-bordered w-full" required>
                        <option value="">Memuat data akun...</option>
                    </select>
                    <div class="label">
                        <span class="label-text-alt text-base-content/60">
                            Pilih rekening/kas tempat dana program disimpan
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-base-200 flex justify-end gap-3 flex-shrink-0">
                <button type="button" class="btn btn-ghost" onclick="tutupModalProgram()">Batal</button>
                <button type="submit" class="btn btn-success rounded-full px-8">Simpan Program</button>
            </div>
        </form>
    </div>
</div>
@endsection


@push('style')
    <link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.5.0/css/rowGroup.dataTables.min.css">

    <style>
        /* Garis tegas antar baris di tabel Realisasi */
        #tabelRealisasi tbody tr {
            border-bottom: 1px solid #e5e7eb !important;
        }

        #tabelRealisasi tbody tr:last-child {
            border-bottom: none !important;
        }

        /* Opsional: zebra style yang lebih jelas */
        #tabelRealisasi tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        /* === Tabel Penerima Style === */
        #tabelPenerima tbody tr {
            border-bottom: 1px solid #e5e7eb !important;
        }

        #tabelPenerima tbody tr:last-child {
            border-bottom: none !important;
        }

        #tabelPenerima tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        #tabelPenerima tbody tr:hover {
            background-color: #f3e8ff;
        }

        .flex-center { @apply flex items-center justify-center; }
        .tab-active { @apply bg-base-100 text-primary shadow-sm border-b-0 !important; }

        /* Pertegas tampilan form input/select/textarea */
        .input.input-bordered,
        .select.select-bordered,
        .textarea.textarea-bordered {
            @apply border-[1.5px] border-base-300/80 bg-base-50/80 rounded-2xl shadow-[0_1px_0_rgba(15,23,42,0.06)];
        }

        .input.input-bordered:focus,
        .select.select-bordered:focus,
        .textarea.textarea-bordered:focus {
            @apply outline-none border-emerald-500 ring-2 ring-emerald-100/80 shadow-[0_0_0_1px_rgba(16,185,129,0.35)];
        }

        /* Badge status yatim biar lebih rapih */
        #statusYatimBadge.badge {
            @apply rounded-full px-3 py-1 text-[11px] font-medium;
        }

        /* === PERTEGAS SEMUA INPUT, SELECT, TEXTAREA === */
        .input-bordered,
        .select-bordered,
        .textarea-bordered {
            @apply border-2 border-base-300/90 bg-base-100/95 rounded-xl shadow-sm 
                   focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/20 
                   focus:outline-none transition-all duration-200 font-medium;
        }

        /* Efek fokus lebih cantik */
        .input-bordered:focus,
        .select-bordered:focus-within,
        .textarea-bordered:focus {
            @apply border-emerald-500 ring-4 ring-emerald-500/20 shadow-lg transform scale-[1.005];
        }

        /* Select arrow lebih jelas */
        .select-bordered::after {
            @apply border-base-400;
        }

        /* Placeholder lebih kontras */
        .input-bordered::placeholder,
        .textarea-bordered::placeholder {
            @apply text-base-content/50 font-normal;
        }

        /* Label lebih bold & rapi */
        label {
            @apply block text-sm font-semibold text-base-content/90 mb-2 tracking-wide;
        }

        /* Card modal lebih elegan */
        .modal-form {
            @apply bg-base-100 rounded-3xl shadow-2xl border border-base-300/80 
                   ring-1 ring-base-300/50 overflow-hidden;
        }

        /* Header modal gradient lebih soft */
        .modal-header-gradient {
            @apply px-7 py-5 text-white flex items-center justify-between font-bold text-lg;
        }

        /* Tombol close lebih besar */
        .btn-close-modal {
            @apply btn btn-circle btn-ghost hover:bg-white/20 text-white text-xl w-11 h-11;
        }

        /* Badge status yatim lebih rapi */
        #statusYatimBadge.badge {
            @apply rounded-full px-4 py-1.5 text-xs font-bold tracking-wider;
        }
    </style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.5.0/js/dataTables.rowGroup.min.js"></script>

<script>
    // ===== Helper Modal & Tab tanpa Bootstrap =====
    lucide.createIcons();
    $(function () {
        // Polyfill jQuery .modal() supaya script lama tetap jalan
        $.fn.modal = function(action) {
            return this.each(function() {
                const $modal = $(this);
                if (action === 'show') {
                    $modal.removeClass('hidden').addClass('flex');
                    $('body').addClass('overflow-hidden');
                    $modal.trigger('shown.bs.modal');  // trigger event yang dipakai script lama
                } else if (action === 'hide') {
                    $modal.addClass('hidden').removeClass('flex');
                    $('body').removeClass('overflow-hidden');
                    $modal.trigger('hidden.bs.modal');
                }
            });
        };

        // Buka modal via data-modal-target
        $(document).on('click', '[data-modal-target]', function () {
            const target = $(this).data('modal-target');
            if (target) {
                $(target).modal('show');
            }
        });

        // Tutup modal via data-modal-close
        $(document).on('click', '[data-modal-close]', function () {
            const target = $(this).data('modal-close');
            if (target) {
                $(target).modal('hide');
            }
        });

        // Tutup modal kalau klik di luar card (backdrop)
        $(document).on('click', '[id^="modal"]', function (e) {
            if (e.target === this) {
                $(this).modal('hide');
            }
        });

        // ===== Helper Tabs tanpa Bootstrap =====
        const $tabButtons = $('[data-tab-target]');
        const $tabContents = $('[data-tab-content]');

        function activateTab(target) {
            $tabButtons.removeClass('tab-active');
            $tabButtons.filter(`[data-tab-target="${target}"]`).addClass('tab-active');
            $tabContents.addClass('hidden');
            $(target).removeClass('hidden');
        }

        // klik tab
        $tabButtons.on('click', function () {
            const target = $(this).data('tab-target');
            if (!target) return;
            activateTab(target);
        });

        // set default tab (saldo)
        activateTab('#tab-saldo');
    });

    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', function () {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('tab-active'));
            document.querySelectorAll('[data-tab-content]').forEach(c => c.classList.add('hidden'));
            this.classList.add('tab-active');
            document.querySelector(this.dataset.tabTarget).classList.remove('hidden');
        });
    });

  
    const baseUrl          = '{{ route("admin.keuangan.dana-terikat.data") }}';
    const cekNamaUrl       = '{{ route("admin.keuangan.dana-terikat.penerima.check-nama") }}';
    const referensiIndexUrl  = '{{ route("admin.keuangan.dana-terikat.referensi.index") }}';
    const referensiStoreUrl  = '{{ route("admin.keuangan.dana-terikat.referensi.store") }}';
    const referensiUpdateUrl = '{{ route("admin.keuangan.dana-terikat.referensi.update", ":id") }}';
    const referensiDeleteUrl = '{{ route("admin.keuangan.dana-terikat.referensi.destroy", ":id") }}';

    const $selectReferensi = $('#referensi_id');

    /* ====================== UTILITIES ====================== */

    // Helper: tombol loading (spinner di dalam button submit)
    function toggleButtonLoading($btn, isLoading) {
        if (!$btn || $btn.length === 0) return;

        if (isLoading) {
            if ($btn.data('loading')) return; // sudah loading

            $btn.data('loading', true);
            $btn.data('original-html', $btn.html());
            $btn.prop('disabled', true);

            const text = $btn.text().trim() || 'Memproses...';
            $btn.html(
                '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>' +
                text
            );
        } else {
            if (!$btn.data('loading')) return;

            $btn.prop('disabled', false);
            $btn.html($btn.data('original-html'));
            $btn.removeData('loading').removeData('original-html');
        }
    }

    // Format ribuan (untuk semua input yang punya class .ribuan)
    $(document).on('input', '.ribuan', function () {
        let v = this.value.replace(/[^\d]/g, '');
        if (!v) {
            this.value = '';
            return;
        }
        this.value = parseInt(v, 10).toLocaleString('id-ID');
    });

    function hitungUmur(dobStr) {
        const dob = new Date(dobStr);
        if (isNaN(dob.getTime())) return null;

        const today = new Date();
        let age = today.getFullYear() - dob.getFullYear();
        const m = today.getMonth() - dob.getMonth();

        if (m < 0 || (m === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        return age;
    }

    function hashString(str) {
        let hash = 0;
        if (!str) return hash;
        for (let i = 0; i < str.length; i++) {
            hash = ((hash << 5) - hash) + str.charCodeAt(i);
            hash |= 0;
        }
        return Math.abs(hash);
    }

    function stringToPastelColor(str) {
        const hash = hashString(str);
        const h    = hash % 360;  // hue
        const s    = 70;          // saturation
        const l    = 90;          // lightness
        return `hsl(${h}, ${s}%, ${l}%)`;
    }

    /* ====================== KATEGORI YATIM ====================== */
    function toggleYatimFields() {
        const kategori      = $('#kategoriPenerima').val();
        const $wrapper      = $('.kategori-yatim-wrapper');
        const $tglLahir     = $('input[name="tanggal_lahir"]');
        const $badge        = $('#statusYatimBadge');
        const $umurDisplay  = $('#umurDisplay');

        if (kategori === 'yatim') {
            $wrapper.show();
            $tglLahir.prop('disabled', false).attr('required', true);
            $selectReferensi.prop('disabled', false);

            $badge
                .removeClass()
                .addClass('badge bg-secondary d-inline-block mt-1')
                .text('Isi tanggal lahir untuk cek status yatim');
            $umurDisplay.val('');
        } else {
            $wrapper.hide();
            $tglLahir.prop('disabled', true).val('').removeAttr('required');
            $selectReferensi.prop('disabled', false);

            $badge
                .removeClass()
                .addClass('badge bg-secondary d-inline-block mt-1')
                .text('Hanya berlaku untuk kategori yatim');
            $umurDisplay.val('');
        }
    }

    function updateStatusYatimPreview() {
        const kategori     = $('#kategoriPenerima').val();
        const $badge       = $('#statusYatimBadge');
        const tgl          = $('input[name="tanggal_lahir"]').val();
        const $umurDisplay = $('#umurDisplay');

        if (kategori !== 'yatim') {
            $badge
                .removeClass()
                .addClass('badge bg-secondary d-inline-block mt-1')
                .text('Hanya berlaku untuk kategori yatim');
            $umurDisplay.val('');
            return;
        }

        if (!tgl) {
            $badge
                .removeClass()
                .addClass('badge bg-warning text-dark d-inline-block mt-1')
                .text('Isi tanggal lahir untuk cek status yatim');
            $umurDisplay.val('');
            return;
        }

        const umur = hitungUmur(tgl);
        if (umur === null) {
            $badge
                .removeClass()
                .addClass('badge bg-warning text-dark d-inline-block mt-1')
                .text('Tanggal lahir tidak valid');
            $umurDisplay.val('');
            return;
        }

        $umurDisplay.val(umur + ' tahun');

        if (umur < 15) {
            $badge
                .removeClass()
                .addClass('badge bg-success d-inline-block mt-1')
                .text(`Masih anak yatim (umur ${umur} tahun)`);
        } else {
            $badge
                .removeClass()
                .addClass('badge bg-danger d-inline-block mt-1')
                .text(`Sudah tidak termasuk anak yatim (umur ${umur} tahun)`);
        }
    }

    $('#kategoriPenerima').on('change', function () {
        toggleYatimFields();
        updateStatusYatimPreview();
    });

    $('#formPenerima').on('change', 'select[name="program_id"], input[name="tanggal_lahir"]', function () {
        updateStatusYatimPreview();
    });

    $('#modalPenerima').on('shown.bs.modal', function () {
        toggleYatimFields();
        updateStatusYatimPreview();
    });

    $('#modalPenerima').on('hidden.bs.modal', function () {
        const $form = $('#formPenerima');

        $form[0].reset();
        $form.find('input[name="id"]').val('');

        $form.find('input[name="tanggal_lahir"]').val('');
        $('#umurDisplay').val('');

        $('#statusYatimBadge')
            .removeClass()
            .addClass('badge bg-secondary d-inline-block mt-1')
            .text('Pilih kategori Yatim & isi tanggal lahir');

        toggleYatimFields();
        updateStatusYatimPreview();
    });

    /* ====================== REFERENSI ====================== */
    function setReferensiFormModeCreate() {
        const $form = $('#formReferensi');
        $form[0].reset();
        $form.find('input[name="id"]').val('');

        $('#modalReferensiTitle').text('Tambah Referensi');
        $('#btnSubmitReferensi').text('Simpan Referensi');
        $form.find('[name="warna"]').val('#ffeeba');
    }

    function setReferensiFormModeEdit(ref) {
        const $form = $('#formReferensi');

        $form.find('input[name="id"]').val(ref.id);
        $form.find('input[name="nama"]').val(ref.nama);
        $form.find('input[name="warna"]').val(ref.warna || '#ffeeba');

        $('#modalReferensiTitle').text('Edit Referensi');
        $('#btnSubmitReferensi').text('Update Referensi');
    }

    function loadReferensiList() {
        $.get(referensiIndexUrl)
            .done(function (list) {
                const $tbody = $('#tabelReferensi tbody');
                $tbody.empty();

                if (!list || list.length === 0) {
                    $tbody.append(
                        '<tr><td colspan="3" class="text-center text-muted">Belum ada referensi</td></tr>'
                    );
                    return;
                }

                list.forEach(function (ref) {
                    const warnaBox = ref.warna
                        ? `<span class="inline-block w-7 h-5 rounded border border-base-300" style="background:${ref.warna};"></span>`
                        : '<span class="text-base-content/40">—</span>';

                    $tbody.append(`
                        <tr data-id="${ref.id}" class="hover:bg-base-200 transition">
                            <td class="py-2">${ref.nama}</td>
                            <td class="text-center py-2">${warnaBox}</td>
                            <td class="text-center space-x-1 py-2">
                                <button type="button" class="btn btn-xs btn-warning edit-referensi" title="Edit">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                <button type="button" class="btn btn-xs btn-error hapus-referensi" title="Hapus">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    `);
                });

                // Jangan lupa re-create icon setiap kali tabel di-update!
                
            })
            .fail(function () {
                const $tbody = $('#tabelReferensi tbody');
                $tbody.html(
                    '<tr><td colspan="3" class="text-center text-danger">Gagal memuat data referensi</td></tr>'
                );
            });
    }

    $('#btnTambahReferensi').on('click', function () {
        $('#modalPenerima').modal('hide');

        setReferensiFormModeCreate();
        loadReferensiList();

        $('#modalReferensi').modal('show');
    });

    $('#modalReferensi').on('shown.bs.modal', function () {
        loadReferensiList();
    });

    // Saat modal ditutup, reset ke mode create
    $('#modalReferensi').on('hidden.bs.modal', function () {
        setReferensiFormModeCreate();
    });

    $('#formReferensi').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn  = $form.find('button[type="submit"]');

        const id    = $form.find('input[name="id"]').val();
        const isEdit = !!id;

        const url = isEdit
            ? referensiUpdateUrl.replace(':id', id)
            : referensiStoreUrl;

        // pakai POST + _method=PUT untuk update (biar gampang)
        let data = $form.serialize();
        if (isEdit) {
            data += '&_method=PUT';
        }

        $.ajax({
            url: url,
            method: 'POST',
            data: data,
            beforeSend: function () {
                toggleButtonLoading($btn, true);
            },
            success: function (res) {
                let ref = res;

                // kalau response update pakai {data: {...}}
                if (res && res.data) {
                    ref = res.data;
                }

                // update dropdown referensi di form penerima
                if (isEdit) {
                    // ubah teks option yang sudah ada
                    $selectReferensi
                        .find('option[value="' + ref.id + '"]')
                        .text(ref.nama);
                } else {
                    // tambahkan option baru kalau belum ada
                    if ($selectReferensi.find('option[value="' + ref.id + '"]').length === 0) {
                        $selectReferensi.append(
                            `<option value="${ref.id}">${ref.nama}</option>`
                        );
                    }
                    $selectReferensi.val(ref.id).change();
                }

                // reload tabel referensi
                loadReferensiList();

                // setelah simpan, balik ke mode tambah
                setReferensiFormModeCreate();

                Swal.fire(
                    'Sukses!',
                    isEdit ? 'Referensi berhasil diupdate' : 'Referensi berhasil ditambahkan',
                    'success'
                );

                tabelPenerima.ajax.reload();

                // kalau dipanggil dari modal penerima, bisa pilih close atau tetap di sini
                // sekarang: tetap buka modal referensi biar user bisa input lagi
            },
            error: function (xhr) {
                Swal.fire(
                    'Gagal',
                    xhr.responseJSON?.message || 'Terjadi kesalahan',
                    'error'
                );
            },
            complete: function () {
                toggleButtonLoading($btn, false);
            }
        });
    });

    $(document).on('click', '.edit-referensi', function () {
        const $tr = $(this).closest('tr');
        const id  = $tr.data('id');

        // ambil data detail dari backend (boleh juga pakai data di list)
        $.get(referensiIndexUrl + '/' + id)
            .done(function (ref) {
                setReferensiFormModeEdit(ref);
                tabelPenerima.ajax.reload();
            })
            .fail(function () {
                Swal.fire('Gagal', 'Gagal mengambil data referensi', 'error');
            });
    });

    $(document).on('click', '.hapus-referensi', function () {
        const $btn = $(this);
        const $tr  = $btn.closest('tr');
        const id   = $tr.data('id');

        Swal.fire({
            title: 'Hapus referensi?',
            text: 'Referensi ini akan dihapus dan tidak bisa dikembalikan.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: referensiDeleteUrl.replace(':id', id),
                method: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                },
                beforeSend: function () {
                    toggleButtonLoading($btn, true);
                },
                success: function (res) {
                    Swal.fire(
                        'Terhapus!',
                        res.message || 'Referensi berhasil dihapus.',
                        'success'
                    );

                    // hilangkan baris dari tabel
                    $tr.remove();

                    // hilangkan option dari dropdown di form penerima
                    $selectReferensi.find('option[value="' + id + '"]').remove();
                    tabelPenerima.ajax.reload();
                },
                error: function (xhr) {
                    Swal.fire(
                        'Gagal!',
                        xhr.responseJSON?.message || 'Terjadi kesalahan.',
                        'error'
                    );
                },
                complete: function () {
                    toggleButtonLoading($btn, false);
                }
            });
        });
    });

    // =============================================
    // ===== FITUR ALOKASI DANA =====
    // =============================================

    function bukaModalAlokasi() {
        document.getElementById('modalAlokasi').classList.remove('hidden');
        document.getElementById('modalAlokasi').classList.add('flex');
    }

    function tutupModalAlokasi() {
        document.getElementById('modalAlokasi').classList.add('hidden');
        document.getElementById('modalAlokasi').classList.remove('flex');
        document.getElementById('formAlokasi').reset();
        document.getElementById('hasilAlokasi').classList.add('hidden');
    }

    // Hitung total alokasi
    $('#btnHitungAlokasi').on('click', function() {
        const perBulan = $('#alokasiPerBulan').val().replace(/\./g, '');
        const bulan = $('#alokasiBulan').val();
        
        if (!perBulan || perBulan == 0) {
            Swal.fire('Info', 'Masukkan nominal per bulan dulu!', 'info');
            return;
        }
        
        const total = parseInt(perBulan) * parseInt(bulan);
        
        $('#totalAlokasi').text('Rp ' + Number(total).toLocaleString('id-ID'));
        $('#hasilAlokasi').removeClass('hidden');
        
        // Set jumlah otomatis
        $('input[name="jumlah_alokasi"]').val(Number(total).toLocaleString('id-ID'));
    });

    // Submit alokasi
    $('#formAlokasi').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $(this).find('button[type="submit"]');
        $btn.html('<span class="loading loading-spinner loading-sm"></span> Memproses...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.keuangan.dana-terikat.alokasi.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                Swal.fire('Sukses!', res.message, 'success');
                tutupModalAlokasi();
                loadSaldo();
            },
            error: function(xhr) {
                Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function() {
                $btn.html('Alokasikan').prop('disabled', false);
            }
        });
    });

    // Tutup modal klik backdrop
    document.getElementById('modalAlokasi').addEventListener('click', function(e) {
        if (e.target === this) tutupModalAlokasi();
    });

    /* ====================== PROGRAM BARU ====================== */
    function bukaModalProgram() {
        const modal = document.getElementById('modalProgramBaru');
        const content = document.getElementById('modalProgramContent');

        modal.classList.remove('hidden');
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');

        // Load akun liabilitas
        const selectLiabilitas = document.getElementById('selectAkunLiabilitas');

        if (!selectLiabilitas.dataset.loaded) {
            selectLiabilitas.innerHTML = '<option>Memuat akun...</option>';

            fetch('{{ route("admin.keuangan.dana-terikat.options") }}?tipe=liabilitas')
                .then(r => r.text())
                .then(html => {
                    selectLiabilitas.innerHTML = html;
                    selectLiabilitas.dataset.loaded = 'true';
                })
                .catch(() => {
                    selectLiabilitas.innerHTML = '<option>Gagal memuat</option>';
                });
        }

        // Load akun aset
        const selectAset = document.getElementById('selectAkunAset');

        if (!selectAset.dataset.loaded) {
            selectAset.innerHTML = '<option>Memuat akun...</option>';

            fetch('{{ route("admin.keuangan.dana-terikat.options") }}?tipe=aset')
                .then(r => r.text())
                .then(html => {
                    selectAset.innerHTML = html;
                    selectAset.dataset.loaded = 'true';
                })
                .catch(() => {
                    selectAset.innerHTML = '<option>Gagal memuat</option>';
                });
        }
    }

    function tutupModalProgram() {
        const modal = document.getElementById('modalProgramBaru');
        const content = document.getElementById('modalProgramContent');
        
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }

    // Tutup kalau klik backdrop
    document.getElementById('modalProgramBaru').addEventListener('click', function(e) {
        if (e.target === this) tutupModalProgram();
    });

    $('#formProgramBaru').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn  = $form.find('button[type="submit"]');

        $.ajax({
            url: '{{ route("admin.keuangan.dana-terikat.program.store") }}',
            method: 'POST',
            data: $form.serialize(),
            beforeSend: function () {
                toggleButtonLoading($btn, true);
            },
            success: function (res) {
                Swal.fire(
                    'Sukses!',
                    res.message || 'Program baru berhasil ditambah!',
                    'success'
                ).then(() => {
                    $('#modalProgramBaru').modal('hide');
                    $('#formProgramBaru')[0].reset();
                    location.reload();
                });
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let msg = Object.values(xhr.responseJSON.errors)
                        .flat()
                        .join('<br>');
                    Swal.fire('Gagal!', msg, 'error');
                } else {
                    Swal.fire(
                        'Error!',
                        xhr.responseJSON?.message || 'Terjadi kesalahan',
                        'error'
                    );
                }
            },
            complete: function () {
                // Kalau langsung reload, user hampir tidak lihat ini, tapi aman
                toggleButtonLoading($btn, false);
            }
        });
    });

    /* ====================== TERIMA DANA ====================== */
    $('#formTerimaDana').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]');

        // Ambil dan bersihkan jumlah
        let jumlah = $('[name="jumlah"]').val()
            .replace(/\./g, '')
            .replace(/,/g, '');

        if (!jumlah || jumlah == 0) {
            Swal.fire('Error', 'Jumlah harus diisi!', 'error');
            return;
        }

        // Cek apakah ini saldo awal
        const isSaldoAwal = $('#isSaldoAwalCheckbox').is(':checked');
        const jenisDana = $('[name="jenis_dana"]').val();
        const labelJenis = $('[name="jenis_dana"] option:selected').text();

        // Update tombol sesuai status
        if (isSaldoAwal) {
            $btn.html('<span class="loading loading-spinner loading-sm"></span> Menyimpan Saldo Awal...');
        } else {
            $btn.html('<span class="loading loading-spinner loading-sm"></span> Memproses...');
        }
        $btn.prop('disabled', true);

        $.ajax({
            url: '{{ route("admin.keuangan.dana-terikat.penerimaan.store") }}',
            method: 'POST',
            data: $form.serialize() + '&jumlah=' + jumlah,
            success: function (res) {
                const message = isSaldoAwal 
                    ? `✅ Saldo awal (${labelJenis}) berhasil dicatat tanpa jurnal!` 
                    : res.message || `✅ Dana ${labelJenis} berhasil dicatat!`;
                
                Swal.fire({
                    icon: 'success',
                    title: 'Sukses!',
                    text: message,
                    timer: 2000,
                    showConfirmButton: false
                });
                
                $('#modalTerimaDana').modal('hide');
                $('#formTerimaDana')[0].reset();
                $('#isSaldoAwalCheckbox').prop('checked', false);
                loadSaldo();
                tabelPenerimaan.ajax.reload();
            },
            error: function (xhr) {
                if (xhr.status === 422) {
                    let msg = Object.values(xhr.responseJSON.errors)
                        .flat()
                        .join('<br>');
                    Swal.fire('Gagal!', msg, 'error');
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan server', 'error');
                }
            },
            complete: function () {
                $btn.html('Simpan & Catat Jurnal');
                $btn.prop('disabled', false);
            }
        });
    });

    /* ====================== PENERIMA (CRUD) ====================== */
    $('#formPenerima').on('blur', 'input[name="nama"]', function () {
        const nama = $(this).val().trim();
        if (!nama) return;

        $.get(cekNamaUrl, { nama: nama })
            .done(function (res) {
                if (!res || res.length === 0) return;

                let html = '<div class="text-start">';
                html += '<p>Ditemukan penerima dengan nama mirip:</p>';
                html += '<ul class="list-unstyled">';

                res.forEach(function (row) {
                    const alamat = (row.alamat || '') +
                        ' RT ' + (row.rt || '-') +
                        '/' + (row.rw || '-');

                    const statusYatimText = row.status_yatim
                        ? 'Masih anak yatim'
                        : 'Bukan anak yatim';

                    html += `
                        <li class="mb-1">
                            <strong>${row.nama}</strong>
                            <br><small>Tahun: ${row.tahun_program || '-'},
                                Status: ${statusYatimText}</small>
                            <br><small>Alamat: ${alamat}</small>
                            ${row.nama_rt
                                ? '<br><small>Nama RT: ' + row.nama_rt + '</small>'
                                : ''
                            }
                        </li>
                        <hr>
                    `;
                });

                html += '</ul></div>';

                Swal.fire({
                    icon: 'info',
                    title: 'Nama mirip sudah terdaftar',
                    html: html,
                    confirmButtonText: 'Saya lanjutkan input',
                });
            });
    });

    $(document).on('click', '.edit-penerima', function () {
        const id = $(this).data('id');

        $.get('{{ route("admin.keuangan.dana-terikat.penerima.show") }}', { id: id }, function (data) {
            const $form = $('#formPenerima');

            $form[0].reset();

            $form.find('input[name="id"]').val(data.id);
            $form.find('select[name="program_id"]').val(data.program_id);
            $form.find('select[name="tahun_program"]').val(data.tahun_program);
            $form.find('input[name="nama"]').val(data.nama);
            $form.find('select[name="kategori"]').val(data.kategori);
            $form.find('select[name="referensi_id"]').val(data.referensi_id || '').trigger('change');

            $form.find('input[name="nominal_bulanan"]').val(
                Number(data.nominal_bulanan).toLocaleString('id-ID')
            );

            $form.find('input[name="no_hp"]').val(data.no_hp);
            $form.find('textarea[name="alamat"]').val(data.alamat);
            $form.find('textarea[name="keterangan"]').val(data.keterangan || '');  // <-- BARU
            $form.find('input[name="status_aktif"]').prop('checked', data.status_aktif == 1);
            $form.find('input[name="nama_rt"]').val(data.nama_rt);
            $form.find('input[name="rt"]').val(data.rt);
            $form.find('input[name="rw"]').val(data.rw);

            if (data.kategori === 'yatim' && data.tanggal_lahir) {
                $form.find('input[name="tanggal_lahir"]').val(
                    data.tanggal_lahir.substring(0, 10)
                );
            } else {
                $form.find('input[name="tanggal_lahir"]').val('');
            }

            $('#modalPenerima .modal-title').text('Edit Penerima');
            $('#modalPenerima').modal('show');
        })
        .fail(() => {
            Swal.fire('Error', 'Gagal mengambil data penerima', 'error');
        });
    });

    $('#formPenerima').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn  = $form.find('button[type="submit"]');

        let nominal = $('[name="nominal_bulanan"]').val().replace(/\./g, '');
        let id      = $('[name="id"]').val();

        $.ajax({
            url: id
                ? '{{ route("admin.keuangan.dana-terikat.penerima.update", ":id") }}'.replace(':id', id)
                : '{{ route("admin.keuangan.dana-terikat.penerima.store") }}',
            method: id ? 'PUT' : 'POST',
            data: $form.serialize() + '&nominal_bulanan=' + nominal,
            beforeSend: function () {
                toggleButtonLoading($btn, true);
            },
            success: function () {
                Swal.fire(
                    'Sukses!',
                    id ? 'Penerima berhasil diupdate!' : 'Penerima ditambahkan!',
                    'success'
                );
                $('#modalPenerima').modal('hide');
                $('#tabelPenerima').DataTable().ajax.reload();
            },
            error: function (xhr) {
                Swal.fire(
                    'Gagal!',
                    xhr.responseJSON?.message || 'Terjadi kesalahan',
                    'error'
                );
            },
            complete: function () {
                toggleButtonLoading($btn, false);
            }
        });
    });

    $(document).on('click', '.hapus-penerima', function () {
        const id = $(this).data('id');

        Swal.fire({
            title: 'Hapus penerima?',
            text: 'Data penerima ini akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.keuangan.dana-terikat.penerima.destroy", ":id") }}'.replace(':id', id),
                method: 'POST',
                data: {
                    _method: 'DELETE',
                    _token: '{{ csrf_token() }}'
                },
                success: function (res) {
                    Swal.fire(
                        'Terhapus!',
                        res.message || 'Penerima berhasil dihapus.',
                        'success'
                    );
                    $('#tabelPenerima').DataTable().ajax.reload(null, false);
                    loadSaldo();
                },
                error: function (xhr) {
                    Swal.fire(
                        'Gagal!',
                        xhr.responseJSON?.message || 'Terjadi kesalahan.',
                        'error'
                    );
                }
            });
        });
    });

    /* ====================== REALISASI ====================== */
    $('#formRealisasi').on('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Yakin?',
            text: "Realisasi hanya bisa dilakukan sekali per bulan per program!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Lanjutkan!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.keuangan.dana-terikat.realisasi.store") }}',
                    method: 'POST',
                    data: $(this).serialize(),
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(res) {
                        Swal.fire('Sukses!', res.message, 'success');
                        $('#modalRealisasi').modal('hide');
                        $('#tabelRealisasi').DataTable().ajax.reload();
                        loadSaldo();
                    },
                    error: function(xhr) {
                        if (xhr.status === 409) {
                            Swal.fire('Sudah Direalisasi!', xhr.responseJSON.message, 'warning');
                        } else {
                            Swal.fire('Gagal!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                        }
                    }
                });
            }
        });
    });

    // Saat program + bulan + tahun berubah → load penerima aktif
    $('select[name="program_id_koreksi"], select[name="bulan_koreksi"], select[name="tahun_koreksi"]').on('change', function() {
        const programId = $('select[name="program_id_koreksi"]').val();
        const bulan = $('select[name="bulan_koreksi"]').val();
        const tahun = $('select[name="tahun_koreksi"]').val();  // ← UBAH KE SELECT

        const $select = $('#divPenerima select');

        if (!programId || !bulan || !tahun) {
            $select.html('<option value="">— Pilih program, bulan & tahun dulu —</option>');
            return;
        }

    // TAMBAHKAN LOADING BIAR USER TAU SEDANG PROSES
    $select.html('<option value="">Memuat penerima...</option>');

    $.get('{{ route("admin.keuangan.dana-terikat.realisasi.penerima-aktif") }}', {
            program_id: programId,
            bulan: bulan,
            tahun: tahun
        })
        .done(function(penerima) {
            if (!penerima || penerima.length === 0) {
                $select.html('<option value="">Tidak ada penerima aktif di bulan ini</option>');
                return;
            }

            let options = '<option value="">— Koreksi Umum (tidak spesifik penerima) —</option>';
            penerima.forEach(p => {
                // 🔥 PASTIKAN data-kategori ADA!
                const kategori = p.kategori || 'santunan';
                options += `<option value="${p.id}" data-kategori="${kategori}">
                    ${p.nama} (Rp ${Number(p.nominal_bulanan).toLocaleString('id-ID')}/bln)
                    ${kategori === 'operasional' ? ' - Biaya Operasional' : ''}
                </option>`;
            });
            $select.html(options);
        })
        .fail(function(xhr) {
            console.error('Error loading penerima:', xhr.responseText);
            $select.html('<option value="">Gagal memuat penerima</option>');
            alert('Gagal memuat data penerima. Cek console (F12) untuk detail.');
        });
    });

    // ===== KOREKSI REALISASI =====
    $('select[name="penerima_id"]').on('change', function() {
        const selected = $(this).find('option:selected');
        const value = this.value;
        
        // Jika tidak memilih penerima (Koreksi Umum), JANGAN ubah apapun
        if (!value) {
            return;
        }

        const nama = selected.text().split(' (Rp')[0];
        const kategori = selected.data('kategori') || 'santunan';

        let jumlahInput = $('[name="jumlah_koreksi"]').val() || '0';
        let isNegatif = jumlahInput.startsWith('-');
        
        let prefix = (kategori === 'operasional') 
            ? (isNegatif ? 'Koreksi pengurangan biaya operasional' : 'Tambahan biaya operasional')
            : (isNegatif ? 'Koreksi pengurangan santunan' : 'Tambahan santunan');

        const teksOtomatis = `${prefix} untuk ${nama}`;

        const $keterangan = $('textarea[name="keterangan"]');
        
        // Hanya isi otomatis jika masih kosong
        if (!$keterangan.val().trim()) {
            $keterangan.val(teksOtomatis);
        }
    });

    $(document).on('input', '.ribuan-koreksi', function () {
        let val = this.value;
        
        // Cek apakah ada tanda minus di awal
        let isNegatif = val.startsWith('-');
        
        // Hapus semua karakter non-digit
        let v = val.replace(/[^\d]/g, '');
        
        if (!v) {
            this.value = isNegatif ? '-' : '';
            return;
        }
        
        // Format ribuan
        let formatted = parseInt(v, 10).toLocaleString('id-ID');
        
        // Tambahkan tanda minus jika negatif
        this.value = isNegatif ? '-' + formatted : formatted;
    });

    // 🔥 SUBMIT KOREKSI (VERSI DEBUG)
    $('#formKoreksi').on('submit', function(e) {
        e.preventDefault();
        
        // Ambil nilai dengan lebih aman
        const $form = $(this);
        let jumlahInput = $('[name="jumlah_koreksi"]', $form).val()?.trim();
        let keterangan  = $('textarea[name="keterangan"]', $form).val()?.trim();

        // Validasi jumlah
        if (!jumlahInput) {
            Swal.fire('Error', 'Jumlah koreksi harus diisi!', 'error');
            return;
        }

        // Validasi keterangan
        if (!keterangan || keterangan === '') {
            Swal.fire({
                title: 'Error',
                text: 'Keterangan harus diisi!',
                icon: 'error',
                footer: 'Nilai yang terbaca: "' + (keterangan || '(kosong)') + '"'
            });
            return;
        }

        // Bersihkan jumlah
        let jumlah = jumlahInput.replace(/\./g, '');
        let isNegatif = jumlah.startsWith('-');
        let jumlahBersih = isNegatif ? jumlah.substring(1) : jumlah;
        let jumlahFinal = isNegatif ? '-' + jumlahBersih : jumlahBersih;

        const programId      = $('select[name="program_id_koreksi"]', $form).val();
        const bulan_koreksi  = $('select[name="bulan_koreksi"]', $form).val();
        const tahun_koreksi  = $('select[name="tahun_koreksi"]', $form).val();

        if (!programId || !bulan_koreksi || !tahun_koreksi) {
            Swal.fire('Error', 'Program, bulan, dan tahun harus diisi!', 'error');
            return;
        }

        Swal.fire({
            title: 'Yakin catat koreksi?',
            text: isNegatif ? 'Jurnal koreksi PENGURANGAN akan dibuat' : 'Jurnal koreksi PENAMBAHAN akan dibuat',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Catat!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '{{ route("admin.keuangan.dana-terikat.koreksi.realisasi.store") }}',
                method: 'POST',
                data: {
                    program_id_koreksi: programId,
                    tahun: tahun_koreksi,
                    bulan: bulan_koreksi,
                    jumlah_koreksi: jumlahFinal,
                    keterangan: keterangan,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    Swal.fire('Sukses!', res.message || 'Koreksi berhasil dicatat', 'success');
                    $('#modalKoreksiRealisasi').modal('hide');
                    $('#formKoreksi')[0].reset();
                    $('#tabelRealisasi').DataTable().ajax.reload();
                    loadSaldo();
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    Swal.fire('Gagal!', msg, 'error');
                }
            });
        });
    });

    /* ====================== DATATABLES & SALDO ====================== */

    const tabelSaldo = $('#tabelSaldo').DataTable({
        paging: false,
        info: false,
        searching: false,
        autoWidth: false,
    });

function loadSaldo() {
    // 🔥 Ambil dari filter yang sudah ditambahkan
    let bulan = $('#filterBulanSaldo').val();
    let tahun = $('#filterTahun').val();
    let program = $('#filterProgram').val();

    // 🔥 Jika bulan kosong, pakai bulan sekarang
    if (!bulan) {
        bulan = new Date().getMonth() + 1;
    }

    $.get(baseUrl, {
        tab: 'saldo',
        program: program,
        tahun: tahun,
        bulan: bulan  // 🔥 KIRIM BULAN!
    }, function (data) {
        tabelSaldo.clear();

        data.forEach(r => tabelSaldo.row.add([
            r.nama_program || 'Unknown',
            'Rp ' + Number(r.terkumpul || 0).toLocaleString('id-ID'),
            'Rp ' + Number(r.realisasi_bulan_ini || 0).toLocaleString('id-ID'),
            'Rp ' + Number(r.sisa || 0).toLocaleString('id-ID')
        ]));

        tabelSaldo.draw();
    });
    console.log('Loading saldo with filters - Program:', program, 'Tahun:', tahun, 'Bulan:', bulan);
}

    const tabelPenerima = $('#tabelPenerima').DataTable({
        processing: true,
        serverSide: false,
        autoWidth: false,
        paging: false,
        info: true,
        searching: true,

        ajax: {
            url: baseUrl,
            data: function (d) {
                d.tab     = 'penerima';
                d.program = $('#filterProgram').val() || '';
                d.tahun   = $('#filterTahun').val() || '';
            },
            dataSrc: function (json) {
                return json && Array.isArray(json) ? json : [];
            }
        },

        rowGroup: {
            dataSrc: function(row) {
                let rtRw   = String(row.rt_rw || '').trim();
                let namaRt = String(row.nama_rt || '').trim();

                if (rtRw === '' || rtRw === '/') rtRw = '-';
                if (namaRt === '') namaRt = 'Umum';

                return `${rtRw}|||${namaRt.toUpperCase()}`;
            },
            startRender: function (rows, group) {
                const [rtRw, namaRt] = group.split('|||');
                
                let totalNominal = 0;
                let totalDapat = 0;
                
                rows.data().each(function (row) {
                    totalNominal += Number(row.nominal_bulanan || 0);
                    if (row.status_aktif == 1) totalDapat++;
                });

                return $('<tr/>')
                    .append(`
                        <td colspan="12" class="bg-primary/10 font-bold text-primary py-3">
                            <div class="flex items-center justify-between px-3">
                                <div>🏠 RT ${rtRw} - ${namaRt}</div>
                                <div class="flex items-center gap-3 text-sm">
                                    <span class="badge badge-ghost">${rows.count()} warga</span>
                                    <span class="badge badge-success">✅ ${totalDapat} dapat</span>
                                    <span class="font-mono font-semibold">Rp ${totalNominal.toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                        </td>
                    `);
            }
        },

        columns: [
            // 0. No
            { 
                data: null, 
                className: 'text-center font-medium w-10',
                orderable: false,
                render: function(){ return ''; } 
            },

            // 1. Tahun
            { data: 'tahun_program' },

            // 2. Program
            { data: 'program_nama' },

            // 3. Nama
            { data: 'nama' },

            // 4. Kategori
            { data: 'kategori' },

            // 5. Referensi
            { data: 'referensi_nama', defaultContent: '-' },

            // 6. Status Yatim (PERBAIKAN)
            { 
                data: 'status_yatim', 
                orderable: false, 
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    if (data == 1 || data === true) {
                        return '<span class="badge badge-info">Yatim</span>';
                    } else {
                        return '<span class="badge badge-ghost">—</span>';
                    }
                }
            },

            // 7. Umur
            { 
                data: 'umur', 
                orderable: false, 
                searchable: false,
                className: 'text-center'
            },

            // 8. Alamat
            { data: 'alamat' },

            // 9. Nominal/Bulan (PERBAIKAN)
            { 
                data: 'nominal_bulanan', 
                className: 'text-end font-mono',
                render: function(data) {
                    if (!data) return '-';
                    return 'Rp ' + Number(data).toLocaleString('id-ID');
                }
            },

            // 10. Status Aktif
            {
                data: 'status_aktif',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data) {
                    return data == 1 
                        ? '<span class="badge badge-success">Aktif</span>' 
                        : '<span class="badge badge-secondary">Nonaktif</span>';
                }
            },

            // 11. Aksi
            {
                data: 'id',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (id) {
                    return `
                        <button class="btn btn-sm btn-warning edit-penerima" data-id="${id}" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-pencil"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                        </button>
                        <button class="btn btn-sm btn-danger hapus-penerima" data-id="${id}" title="Hapus">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-trash-2"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                        </button>
                    `;
                }
            }
        ],

        // Hidden columns dipindah ke akhir supaya tidak mengganggu
        columnDefs: [
            { targets: [12, 13], data: 'rt_rw', visible: false },
            { targets: [13], data: 'nama_rt', visible: false }  // index setelah 12 visible + 1
        ],

        order: [[12, 'asc'], [13, 'asc'], [3, 'asc']],  // rt_rw, nama_rt, nama

        drawCallback: function() {
            const api = this.api();
            let currentGroup = '';
            let groupIndex = 0;

            api.rows({ page: 'current' }).every(function() {
                const data = this.data();
                const groupKey = String(data.rt_rw || '-') + '|||' + 
                            String(data.nama_rt || '-').trim().toUpperCase();

                if (groupKey !== currentGroup) {
                    currentGroup = groupKey;
                    groupIndex = 1;
                } else {
                    groupIndex++;
                }

                $(this.node()).find('td:first').text(groupIndex);   // pakai :first biar lebih aman
            });
        },

        rowCallback: function (row, data) {
            const $row = $(row);
            $row.css('background-color', '');

            const kategori = (data.kategori || '').toString().toLowerCase();
            if (kategori === 'yatim' && data.referensi_nama) {
                const color = data.referensi_warna || stringToPastelColor(data.referensi_nama);
                $row.css('background-color', color);
            }
        },

        language: {
            emptyTable: "Tidak ada data penerima",
            zeroRecords: "Tidak ada data yang sesuai filter",
            info: "Menampilkan _TOTAL_ data"
        }
    });

    const tabelPenerimaan = $('#tabelPenerimaan').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        ajax: {
            url: baseUrl,
            data: function (d) {
                d.tab     = 'penerimaan';
                d.program = $('#filterProgram').val();
                d.tahun   = $('#filterTahun').val();
            }
        },
        columns: [
            { data: 'tanggal',      name: 'tanggal' },
            { data: 'program_nama', name: 'program_nama' },
            { data: 'donatur_nama', name: 'donatur_nama' },
            { data: 'jumlah',       name: 'jumlah', className: 'text-end' },
            { data: 'keterangan',   name: 'keterangan' },
            { 
                data: 'is_saldo_awal', 
                name: 'is_saldo_awal', 
                className: 'text-center',
                render: function(data) {
                    if (data == 1 || data === true) {
                        return '<span class="badge badge-info">📌 Saldo Awal</span>';
                    }
                    return '<span class="badge badge-ghost">Donasi</span>';
                }
            }
        ]
    });
    
    const tabelRealisasi = $('#tabelRealisasi').DataTable({
        processing: true,
        serverSide: false,
        autoWidth: false,
        paging: false,
        info: true,
        searching: true,
        ajax: {
            url: baseUrl,
            data: function (d) {
                d.tab = 'realisasi';
                d.program = $('#filterProgram').val() || '';
                d.tahun = $('#filterTahun').val() || '';
            },
            dataSrc: function (json) {
                return json && Array.isArray(json) ? json : [];
            }
        },

        rowGroup: {
            dataSrc: function(row) {
                let rtRw = String(row.rt_rw || '').trim();
                let namaRt = String(row.nama_rt || '').trim();

                if (rtRw === '' || rtRw === '/') rtRw = '-';
                if (namaRt === '') namaRt = 'Umum';

                // Pakai separator yang aman
                return `${rtRw}|||${namaRt.toUpperCase()}`;
            },
            startRender: function (rows, group) {
                const [rtRw, namaRt] = group.split('|||');
                let totalNominal = 0;

                rows.data().each(function (row) {
                    totalNominal += Number(row.jumlah_tampil || 0);
                });

                return $('<tr/>')
                    .append(`
                        <td colspan="5" class="bg-amber-100 font-bold text-amber-900 py-3">
                            <div class="flex justify-between items-center px-3">
                                <div>🏠 RT ${rtRw} - ${namaRt}</div>
                                <div class="flex items-center gap-3">
                                    <span class="badge badge-ghost">${rows.count()} item</span>
                                    <span class="font-mono font-semibold">Rp ${totalNominal.toLocaleString('id-ID')}</span>
                                </div>
                            </div>
                        </td>
                    `);
            }
        },

        columns: [
            { data: 'rt_rw',       visible: false },
            { data: 'nama_rt',     visible: false },
            { data: 'tahun',       visible: false },
            { data: 'bulan',       visible: false },
            { data: 'program_nama',visible: false },

            // Kolom nomor urut dalam group
            {
                data: null,
                className: 'text-center font-medium w-12',
                orderable: false,
                render: function() { return ''; }
            },

            // Bulan + Program
            {
                data: null,
                render: function(data, type, row) {
                    return `<strong>${row.bulan_tahun || '-'}</strong><br>
                            <small class="text-gray-600">${row.program_nama || ''}</small>`;
                }
            },

            // Penerima
            {
                data: 'penerima_nama',
                render: function(data, type, row) {
                    return row.tipe === 'koreksi'
                        ? `<em class="text-amber-700">${data || '-'}</em>`
                        : (data || '-');
                }
            },

            // Jumlah
            {
                data: 'jumlah_tampil',
                className: 'text-end font-bold font-mono',
                render: function(data, type, row) {
                    if (data == null) return '-';
                    let jumlah = parseInt(data);
                    let prefix = jumlah >= 0 ? '' : '-';
                    let color = row.tipe === 'koreksi'
                        ? (jumlah >= 0 ? 'text-success' : 'text-danger')
                        : 'text-emerald-700';

                    return `<span class="${color}">Rp ${prefix}${Math.abs(jumlah).toLocaleString('id-ID')}</span>`;
                }
            }
        ],

        order: [
            [0, 'asc'],  // rt_rw
            [1, 'asc'],  // nama_rt
            [2, 'desc'], // tahun
            [3, 'desc'], // bulan
            [4, 'asc']   // program_nama
        ],

        drawCallback: function() {
            const api = this.api();
            let currentGroup = '';
            let groupIndex = 0;

            api.rows({ page: 'current' }).every(function(idx) {
                const data = this.data();
                const groupKey = String(data.rt_rw || '-').trim() + '|||' + 
                            String(data.nama_rt || '-').trim().toUpperCase();

                if (groupKey !== currentGroup) {
                    currentGroup = groupKey;
                    groupIndex = 1;
                } else {
                    groupIndex++;
                }

                // Isi nomor urut di kolom pertama yang visible
                $(this.node()).find('td:eq(0)').text(groupIndex);
            });
        },

        rowCallback: function (row, data) {
            if (data.tipe === 'koreksi') {
                $(row).addClass('bg-amber-50');
            }
        },

        language: {
            emptyTable: "Belum ada realisasi atau koreksi di periode ini",
            zeroRecords: "Tidak ada data yang sesuai",
            info: "Menampilkan _TOTAL_ data"
        }
    });

    $('#filterBulanSaldo, #filterTahun, #filterProgram').on('change', function () {
        loadSaldo();
        tabelPenerima.ajax.reload();
        tabelPenerimaan.ajax.reload();
        tabelRealisasi.ajax.reload();
    });

    // =============================================
    // ===== FITUR STATUS BULANAN (BARU) =====
    // =============================================

    // Variabel global untuk data status
    let dataStatusBulanan = [];

    // ===== LOAD STATUS BULANAN =====
    function loadStatusBulanan() {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();
        const program = $('#filterProgram').val();

        if (!program) {
            $('#tabelStatusBulanan tbody').html(`
                <tr><td colspan="9" class="text-center py-8 text-base-content/60">
                    <i data-lucide="alert-circle" class="w-6 h-6 mx-auto mb-2"></i>
                    <p>Silakan pilih program terlebih dahulu</p>
                </td></tr>
            `);
            lucide.createIcons();
            return;
        }

        $('#tabelStatusBulanan tbody').html(`
            <tr><td colspan="9" class="text-center py-8 text-base-content/60">
                <i data-lucide="loader-circle" class="w-6 h-6 animate-spin mx-auto mb-2"></i>
                <p>Memuat data...</p>
            </td></tr>
        `);
        lucide.createIcons();

        $.get('/admin/dana-terikat/data', {
            tab: 'status_bulanan',
            program: program,
            bulan: bulan,
            tahun: tahun
        }, function(data) {
            dataStatusBulanan = data;
            renderStatusTable(data);
            updateStatusStats(data);
            lucide.createIcons();

        }).fail(function(xhr) {
            console.error('❌ Error:', xhr.status, xhr.responseText);
            $('#tabelStatusBulanan tbody').html(`
                <tr><td colspan="9" class="text-center py-8 text-error">
                    <i data-lucide="alert-triangle" class="w-6 h-6 mx-auto mb-2"></i>
                    <p>Gagal memuat data. Error: ${xhr.status}</p>
                </td></tr>
            `);
            lucide.createIcons();
        });
    }

    // ===== TOGGLE STATUS =====
    function toggleStatusBulanan(penerimaId, newStatus) {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();

        Swal.fire({
            title: newStatus ? '✅ Konfirmasi Dapat' : '❌ Konfirmasi Tidak Dapat',
            text: newStatus ? 'Penerima ini akan mendapatkan santunan' : 'Penerima ini TIDAK akan mendapatkan santunan',
            icon: 'question',
            showCancelButton: true,
            input: !newStatus ? 'textarea' : null,
            inputPlaceholder: !newStatus ? 'Alasan tidak dapat (contoh: sudah mampu, pindah, dll)' : '',
            confirmButtonText: newStatus ? 'Ya, Dapat' : 'Ya, Tidak Dapat',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '/admin/dana-terikat/status-bulanan/update',  // ✅ URL BENAR
                method: 'POST',
                data: {
                    penerima_id: penerimaId,
                    bulan: bulan,
                    tahun: tahun,
                    status_dapat: newStatus ? 1 : 0,
                    alasan_tidak_dapat: !newStatus ? result.value : null,
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    loadStatusBulanan();
                    Swal.fire('Berhasil!', 'Status diperbarui', 'success');
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                }
            });
        });
    }

    // ===== EDIT LENGKAP =====
    function editStatusBulanan(penerimaId) {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();

        // UPDATE URL
        $.get(`/admin/dana-terikat/status-bulanan/${penerimaId}`, {
            bulan: bulan,
            tahun: tahun
        }, function(data) {
            // ... rest of code
        }).fail(function() {
            Swal.fire('Error!', 'Gagal mengambil data penerima', 'error');
        });
    }

    // ===== COPY DARI BULAN LALU =====
    function copyStatusDariBulanLalu() {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();
        const program = $('#filterProgram').val();

        if (!program) {
            Swal.fire('Peringatan!', 'Silakan pilih program terlebih dahulu', 'warning');
            return;
        }

        Swal.fire({
            title: '📋 Copy Status dari Bulan Lalu?',
            text: `Akan menyalin data status dari bulan sebelumnya ke ${bulan}/${tahun}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Copy!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;

            // UPDATE URL
            $.ajax({
                url: '/admin/dana-terikat/status-bulanan/copy',
                method: 'POST',
                data: {
                    program_id: program,
                    bulan: bulan,
                    tahun: tahun,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    loadStatusBulanan();
                    Swal.fire('Berhasil!', res.message, 'success');
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                }
            });
        });
    }

    // ===== EXPORT EXCEL =====
    function exportStatusExcel() {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();
        const program = $('#filterProgram').val();

        if (!program) {
            Swal.fire('Peringatan!', 'Silakan pilih program terlebih dahulu', 'warning');
            return;
        }

        // UPDATE URL
        window.location.href = `/admin/dana-terikat/status-bulanan/export?program_id=${program}&bulan=${bulan}&tahun=${tahun}`;
    }

    // ===== RENDER TABLE STATUS =====
    function renderStatusTable(data) {
        if (!data || data.length === 0) {
            $('#tabelStatusBulanan tbody').html(`
                <tr><td colspan="9" class="text-center py-8 text-base-content/60">
                    <i data-lucide="inbox" class="w-6 h-6 mx-auto mb-2"></i>
                    <p>Tidak ada penerima aktif di periode ini</p>
                </td></tr>
            `);
            return;
        }

        let html = '';
        data.forEach((p, index) => {
            const isModified = p.is_modified;
            const statusDapat = p.status_dapat;
            const nominalAktual = p.nominal_aktual || p.nominal_bulanan;
            const namaTampil = p.nama_aktual || p.nama;
            
            // Warna baris
            let rowClass = '';
            if (isModified) rowClass = 'bg-warning/10';
            if (!statusDapat) rowClass = 'bg-error/5';
            
            html += `
                <tr class="${rowClass} hover">
                    <td>
                        <input type="checkbox" 
                            class="checkbox checkbox-xs checkbox-primary checkbox-status" 
                            value="${p.id}"
                            ${statusDapat ? 'checked' : ''}>
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            ${isModified && p.nama_alternatif ? `
                                <span class="line-through text-base-content/50 text-sm">${p.nama}</span>
                                <span class="font-bold text-success">${p.nama_alternatif}</span>
                                <span class="badge badge-xs badge-warning">Ganti</span>
                            ` : `
                                <span class="font-medium">${p.nama}</span>
                            `}
                            ${p.kategori === 'yatim' ? `<span class="badge badge-xs badge-info">Yatim</span>` : ''}
                        </div>
                    </td>
                    <td>${p.program_nama}</td>
                    <td class="text-right font-mono">Rp ${Number(p.nominal_bulanan).toLocaleString('id-ID')}</td>
                    <td class="text-center">
                        <span class="badge ${statusDapat ? 'badge-success' : 'badge-error'}">
                            ${statusDapat ? '✅ Dapat' : '❌ Tidak Dapat'}
                        </span>
                        ${p.verified_at ? `<span class="badge badge-xs badge-ghost ml-1">✓</span>` : 
                                        `<span class="badge badge-xs badge-warning ml-1">⏳</span>`}
                    </td>
                    <td class="text-right font-mono">
                        ${isModified && p.nominal_alternatif ? `
                            <span class="line-through text-base-content/50 text-sm">${Number(p.nominal_bulanan).toLocaleString('id-ID')}</span>
                            <span class="font-bold text-success">${Number(p.nominal_alternatif).toLocaleString('id-ID')}</span>
                        ` : `
                            ${Number(nominalAktual).toLocaleString('id-ID')}
                        `}
                    </td>
                    <td>
                        <span class="text-xs">${p.verified_by || '-'}</span>
                        ${p.verified_at ? `<span class="text-[10px] text-base-content/40 block">${new Date(p.verified_at).toLocaleDateString('id-ID')}</span>` : ''}
                    </td>
                    <td>
                        <span class="text-xs">${p.alasan_tidak_dapat || '-'}</span>
                    </td>
                    <td class="text-center">
                        <div class="flex justify-center gap-1">
                            <button class="btn btn-xs btn-ghost btn-square" onclick="editStatusBulanan(${p.id})" title="Edit">
                                <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            </button>
                            <button class="btn btn-xs btn-ghost btn-square ${statusDapat ? 'btn-error' : 'btn-success'}" 
                                    onclick="toggleStatusBulanan(${p.id}, ${statusDapat ? 0 : 1})" 
                                    title="${statusDapat ? 'Set Tidak Dapat' : 'Set Dapat'}">
                                <i data-lucide="${statusDapat ? 'x' : 'check'}" class="w-3.5 h-3.5"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });

        $('#tabelStatusBulanan tbody').html(html);
        lucide.createIcons();
    }

    // ===== UPDATE STATISTIK =====
    function updateStatusStats(data) {
        const total = data.length;
        const dapat = data.filter(p => p.status_dapat).length;
        const tidakDapat = data.filter(p => !p.status_dapat).length;
        const belumVerifikasi = data.filter(p => !p.verified_at).length;

        $('#statTotal').text(total);
        $('#statDapat').text(dapat);
        $('#statTidakDapat').text(tidakDapat);
        $('#statBelumVerifikasi').text(belumVerifikasi);

        // Update badge
        const badge = $('#badgePendingStatus');
        if (belumVerifikasi > 0) {
            badge.text(`${belumVerifikasi} perlu verifikasi`);
            badge.removeClass('hidden');
        } else {
            badge.addClass('hidden');
        }
    }

    // ===== TOGGLE STATUS (Quick) =====
    function toggleStatusBulanan(penerimaId, newStatus) {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();

        const statusText = newStatus ? 'Dapat' : 'Tidak Dapat';
        const icon = newStatus ? '✅' : '❌';

        Swal.fire({
            title: `${icon} Konfirmasi Status`,
            text: `Penerima ini akan diubah menjadi: ${statusText}`,
            icon: 'question',
            showCancelButton: true,
            input: !newStatus ? 'textarea' : null,
            inputPlaceholder: !newStatus ? 'Alasan tidak dapat (contoh: sudah mampu, pindah, dll)' : '',
            confirmButtonText: `Ya, ${statusText}`,
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;

            const data = {
                penerima_id: penerimaId,
                bulan: bulan,
                tahun: tahun,
                status_dapat: newStatus ? 1 : 0,
                alasan_tidak_dapat: !newStatus ? result.value : null,
                _token: '{{ csrf_token() }}'
            };

            $.ajax({
                url: '/admin/dana-terikat/status-bulanan/update',
                method: 'POST',
                data: data,
                success: function() {
                    loadStatusBulanan();
                    Swal.fire('Berhasil!', 'Status diperbarui', 'success');
                },
                error: function(xhr) {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                }
            });
        });
    }

    // ===== EDIT LENGKAP =====
    function editStatusBulanan(penerimaId) {
        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();

        // Ambil data lengkap
        $.get(`/admin/dana-terikat/status-bulanan/${penerimaId}`, {
            bulan: bulan,
            tahun: tahun
        }, function(data) {
            const isDapat = data.status_dapat;
            
            Swal.fire({
                title: '✏️ Edit Data Penerima Bulan Ini',
                width: 600,
                html: `
                    <div class="text-left space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-text font-medium text-sm">Nama Lengkap</label>
                                <input id="editNama" class="input input-bordered w-full text-sm" 
                                    value="${data.nama || ''}" placeholder="Nama penerima">
                                <small class="text-[10px] text-base-content/50">Isi jika ada penggantian nama</small>
                            </div>
                            <div>
                                <label class="label-text font-medium text-sm">Status Dapat?</label>
                                <select id="editStatus" class="select select-bordered w-full text-sm">
                                    <option value="1" ${isDapat ? 'selected' : ''}>✅ Dapat</option>
                                    <option value="0" ${!isDapat ? 'selected' : ''}>❌ Tidak Dapat</option>
                                </select>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="label-text font-medium text-sm">Nominal Bulanan (Rp)</label>
                                <input id="editNominal" class="input input-bordered w-full text-sm text-right ribuan" 
                                    value="${Number(data.nominal_aktual || data.nominal_bulanan || 0).toLocaleString('id-ID')}">
                                <small class="text-[10px] text-base-content/50">Isi jika ada perubahan nominal</small>
                            </div>
                            <div>
                                <label class="label-text font-medium text-sm">Verifikator</label>
                                <input id="editVerifikator" class="input input-bordered w-full text-sm" 
                                    value="${data.verified_by || ''}" placeholder="Nama verifikator">
                            </div>
                        </div>
                        <div>
                            <label class="label-text font-medium text-sm">Alasan Tidak Dapat</label>
                            <textarea id="editAlasan" class="textarea textarea-bordered w-full text-sm" 
                                    rows="2" placeholder="Alasan tidak dapat...">${data.alasan_tidak_dapat || ''}</textarea>
                        </div>
                        ${data.verified_at ? `
                            <div class="text-xs text-base-content/50">
                                <span>Diverifikasi: ${new Date(data.verified_at).toLocaleString('id-ID')}</span>
                            </div>
                        ` : ''}
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '💾 Simpan Perubahan',
                cancelButtonText: 'Batal',
                preConfirm: () => {
                    const nominal = document.getElementById('editNominal').value.replace(/\./g, '');
                    return {
                        nama: document.getElementById('editNama').value,
                        status_dapat: document.getElementById('editStatus').value,
                        nominal: nominal || null,
                        alasan: document.getElementById('editAlasan').value,
                        verifikator: document.getElementById('editVerifikator').value
                    };
                }
            }).then(result => {
                if (!result.isConfirmed) return;

                const dataUpdate = {
                    penerima_id: penerimaId,
                    bulan: bulan,
                    tahun: tahun,
                    ...result.value,
                    _token: '{{ csrf_token() }}'
                };

                $.ajax({
                    url: '/admin/dana-terikat/status-bulanan/update-lengkap',
                    method: 'POST',
                    data: dataUpdate,
                    success: function() {
                        loadStatusBulanan();
                        Swal.fire('Berhasil!', 'Data status diperbarui', 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                });
            });
        }).fail(function() {
            Swal.fire('Error!', 'Gagal mengambil data penerima', 'error');
        });
    }

    // ===== SELECT ALL =====
    $(document).on('change', '#selectAllStatus', function() {
        const isChecked = $(this).is(':checked');
        $('.checkbox-status').prop('checked', isChecked);
    });

    // ===== REALISASI DARI STATUS =====
    function realisasiDariStatus() {

        const bulan = $('#filterBulanStatus').val();
        const tahun = $('#filterTahunStatus').val();
        const program = $('#filterProgram').val();

        if (!program) {
            Swal.fire('Peringatan!', 'Silakan pilih program terlebih dahulu', 'warning');
            return;
        }

        const penerimaDapat = [];
        $('.checkbox-status:checked').each(function() {
            penerimaDapat.push($(this).val());
        });

        if (penerimaDapat.length === 0) {
            Swal.fire('Peringatan!', 'Tidak ada penerima yang dipilih', 'warning');
            return;
        }

        // === HITUNG TOTAL DENGAN KONVERSI AMAN ===
        let totalNominal = 0;
        let detailDebug = [];

        dataStatusBulanan.forEach(p => {
            if (penerimaDapat.includes(String(p.id))) {   // pakai String() biar aman
                const nominal = Number(p.nominal_aktual) || Number(p.nominal_bulanan) || 0;
                totalNominal += nominal;

                detailDebug.push({
                    nama: p.nama,
                    nominal_bulanan: p.nominal_bulanan,
                    nominal_aktual: p.nominal_aktual,
                    hasil: nominal
                });
            }
        });

        Swal.fire({
            title: `💰 Realisasi ${penerimaDapat.length} Penerima?`,
            html: `
                <div class="text-left">
                    <p><strong>Bulan:</strong> ${bulan}/${tahun}</p>
                    <p><strong>Program:</strong> ${$('#filterProgram option:selected').text()}</p>
                    <p><strong>Jumlah Penerima:</strong> ${penerimaDapat.length} orang</p>
                    <p><strong>Total Nominal:</strong> <span class="font-bold text-success">Rp ${totalNominal.toLocaleString('id-ID')}</span></p>
                    ${totalNominal === 0 ? `<p class="text-error text-sm mt-2">⚠️ Nominal 0 kemungkinan data backend bermasalah</p>` : ''}
                    <hr class="my-2">
                    <p class="text-xs text-base-content/60">⚠️ Realisasi hanya bisa dilakukan SEKALI per bulan!</p>
                </div>
            `,
            icon: totalNominal === 0 ? 'warning' : 'question',
            showCancelButton: true,
            confirmButtonText: '✅ Ya, Realisasikan!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#10b981'
        }).then(result => {
            if (!result.isConfirmed) return;

            $.ajax({
                url: '/admin/dana-terikat/status-bulanan/realisasi',
                method: 'POST',
                data: {
                    program_id: program,
                    bulan: bulan,
                    tahun: tahun,
                    penerima_ids: penerimaDapat,
                    _token: '{{ csrf_token() }}'
                },
                success: function(res) {
                    Swal.fire({
                        title: '🎉 Sukses!',
                        html: res.message,
                        icon: 'success'
                    });
                    $('#tabelRealisasi').DataTable().ajax.reload();
                    loadSaldo();
                    loadStatusBulanan();
                },
                error: function(xhr) {
                    Swal.fire({
                        title: 'Error!',
                        html: xhr.responseJSON?.message || 'Terjadi kesalahan',
                        icon: 'error'
                    });
                }
            });
        });
    }

    // ===== AUTO LOAD SAAT FILTER BERUBAH =====
    $('#filterProgram, #filterBulanStatus, #filterTahunStatus').on('change', function() {
        // Cek apakah tab status_bulanan sedang aktif
        if (!$('#tab-status-bulanan').hasClass('hidden')) {
            loadStatusBulanan();
        }
    });

    // 3. Load saat halaman pertama kali dibuka (jika tab aktif)
    $(document).ready(function() {
        // Cek apakah tab status_bulanan aktif saat load
        if (!$('#tab-status-bulanan').hasClass('hidden')) {
            setTimeout(loadStatusBulanan, 500);
        }
        
        // Trigger change pada filter untuk load otomatis
        $('#filterProgram').trigger('change');
    });

    // 4. Juga panggil saat tombol "Muat" ditekan
    $(document).on('click', '#btnMuatStatus', function() {
        loadStatusBulanan();
    });

    // ===== LOAD SAAT TAB AKTIF =====
    $(document).on('click', '[data-tab-target="#tab-status-bulanan"]', function() {
        setTimeout(loadStatusBulanan, 300);
    });

    // initial load
    loadSaldo();

</script>
@endpush
