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
                        <p class="text-xs lg:text-sm text-emerald-50/80 mt-1">
                            Kelola dana terikat, program rutin, penerima santunan, dan realisasi bulanan.
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3 justify-start lg:justify-end">

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

                </div>

            </div>

            {{-- BODY --}}
            <div class="p-5 lg:p-7 space-y-6">

                {{-- FILTER --}}
                <div
                    class="bg-base-100/90 border border-base-300/80 rounded-2xl p-4 lg:p-5 flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4 shadow-sm"
                >
                    <div class="flex-1 grid grid-cols-1 lg:grid-cols-3 gap-4">
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
                                <table
                                    id="tabelPenerima"
                                    class="min-w-full text-[11px] md:text-xs lg:text-sm"
                                >
                                    <thead class="bg-sky-600 text-white uppercase tracking-wide">
                                        <tr>
                                            <th class="px-3 py-2">Tahun</th>
                                            <th class="px-3 py-2">Program</th>
                                            <th class="px-3 py-2">Nama</th>
                                            <th class="px-3 py-2">Kategori</th>
                                            <th class="px-3 py-2">Referensi</th>
                                            <th class="px-3 py-2">Status Yatim</th>
                                            <th class="px-3 py-2">Umur</th>
                                            <th class="px-3 py-2">Alamat</th>
                                            <th class="px-3 py-2">RT/RW</th>
                                            <th class="px-3 py-2">Nama RT</th>
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
                                <table
                                    id="tabelRealisasi"
                                    class="min-w-full text-[11px] md:text-xs lg:text-sm"
                                >
                                    <thead class="bg-amber-400 text-amber-950 uppercase tracking-wide">
                                        <tr>
                                            <th class="px-3 py-2 w-[12%] whitespace-nowrap">Bulan</th>
                                            <th class="px-3 py-2">Program</th>
                                            <th class="px-3 py-2">Penerima</th>
                                            <th class="px-3 py-2 text-right whitespace-nowrap">Jumlah</th>
                                            <th class="px-3 py-2 w-[10%] text-center whitespace-nowrap">Kwitansi</th>
                                            <th class="px-3 py-2 w-[8%] text-center">Tipe</th>
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

{{-- ====================== MODAL PENERIMA ====================== --}}
<div id="modalPenerima" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-2xl mx-4 my-8"> <!-- dari max-w-3xl jadi max-w-2xl -->
        <form id="formPenerima" class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden">
            @csrf
            <input type="hidden" name="id">

            <!-- Header -->
            <div class="px-6 py-4 bg-sky-600 text-white flex items-center justify-between rounded-t-3xl">
                <h3 class="font-bold text-lg">Tambah / Edit Penerima</h3>
                <button type="button" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20"
                        onclick="document.getElementById('modalPenerima').classList.add('hidden')">
                    ✕
                </button>
            </div>

            <!-- Body Form — SEMUA INPUT DIKECILIN -->
            <div class="p-5 space-y-5"> <!-- padding dikurangi dari p-6 jadi p-5 -->
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
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="label label-text text-xs font-medium">Nama Lengkap *</label>
                        <input type="text" name="nama" class="input input-bordered input-sm w-full" required>
                    </div>

                    <!-- KHUSUS YATIM -->
                    <div class="md:col-span-2 kategori-yatim-wrapper space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
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
                        </div>
                        <div class="text-center">
                            <div id="statusYatimBadge" class="badge badge-ghost badge-sm">Pilih kategori Yatim & isi tgl lahir</div>
                        </div>
                    </div>

                    <!-- Nominal No HP -->
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
                        <textarea name="alamat" rows="2" class="textarea textarea-bordered textarea-sm w-full"></textarea>
                    </div>

                    <!-- RT / RW / Nama RT + Checkbox dalam 1 baris -->
                    <div class="md:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                            <div class="md:col-span-2">
                                <label class="label label-text text-xs">RT</label>
                                <input type="text" name="rt" placeholder="01" maxlength="3"
                                       class="input input-bordered input-sm w-full text-center">
                            </div>
                            <div class="md:col-span-2">
                                <label class="label label-text text-xs">RW</label>
                                <input type="text" name="rw" placeholder="03" maxlength="3"
                                       class="input input-bordered input-sm w-full text-center">
                            </div>
                            <div class="md:col-span-5">
                                <label class="label label-text text-xs">Nama RT</label>
                                <input type="text" name="nama_rt" placeholder="Bpk. Ahmad"
                                       class="input input-bordered input-sm w-full">
                            </div>
                            <div class="md:col-span-3 flex items-center">
                                <label class="cursor-pointer label gap-2">
                                    <input type="checkbox" name="status_aktif" value="1"
                                           class="checkbox checkbox-success checkbox-sm" checked>
                                    <span class="label-text text-xs font-medium">Aktif bulanan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-base-200 rounded-b-3xl flex justify-end gap-3">
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
                            <label class="text-sm font-medium">Bulan</label>
                            <select
                                name="bulan"
                                class="select select-bordered w-full"
                                required
                            >
                                @for($i=1;$i<=12;$i++)
                                    <option value="{{ $i }}">{{ \Carbon\Carbon::create()->month($i)->translatedFormat('F') }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-sm font-medium">Tahun</label>
                            <input
                                type="number"
                                name="tahun"
                                class="input input-bordered w-full"
                                value="{{ date('Y') }}"
                                required
                            >
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
                            name="jumlah"
                            class="input input-bordered w-full text-right font-semibold ribuan"
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
<div id="modalProgramBaru" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
    <div class="w-full max-w-md mx-4 scale-95 opacity-0 transition-all duration-200" id="modalProgramContent">
        <form id="formProgramBaru" class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden">
            @csrf
            <div class="px-6 py-4 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white flex items-center justify-between">
                <h5 class="font-semibold text-lg">Tambah Program Dana Terikat</h5>
                <button type="button" class="btn btn-sm btn-circle btn-ghost text-white hover:bg-white/20"
                        onclick="tutupModalProgram()">✕</button>
            </div>
            <div class="p-6 space-y-5">
                <!-- kode program, nama, dan select akun tetap sama -->
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
            </div>
            <div class="px-6 py-4 bg-base-200 flex justify-end gap-3">
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
    <style>
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


    /* ====================== PROGRAM BARU ====================== */

    function bukaModalProgram() {
        const modal = document.getElementById('modalProgramBaru');
        const content = document.getElementById('modalProgramContent');
        
        modal.classList.remove('hidden');           // munculin backdrop
        content.classList.remove('scale-95', 'opacity-0');
        content.classList.add('scale-100', 'opacity-100');

        // Load data akun (hanya sekali)
        const select = document.getElementById('selectAkunLiabilitas');
        if (select.dataset.loaded) return;

        select.innerHTML = '<option>Memuat akun...</option>';
        fetch('{{ route("admin.keuangan.dana-terikat.options") }}')
            .then(r => r.text())
            .then(html => {
                select.innerHTML = html;
                select.dataset.loaded = 'true';
            })
            .catch(() => select.innerHTML = '<option>Gagal memuat</option>');
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
        const $btn  = $form.find('button[type="submit"]');

        let jumlah = $('[name="jumlah"]').val()
            .replace(/\./g, '')
            .replace(/,/g, '');

        if (!jumlah || jumlah == 0) {
            Swal.fire('Error', 'Jumlah harus diisi!', 'error');
            return;
        }

        $.ajax({
            url: '{{ route("admin.keuangan.dana-terikat.penerimaan.store") }}',
            method: 'POST',
            data: $form.serialize() + '&jumlah=' + jumlah,
            beforeSend: function () {
                toggleButtonLoading($btn, true);
            },
            success: function (res) {
                Swal.fire(
                    'Sukses!',
                    res.message || 'Dana berhasil dicatat!',
                    'success'
                );
                $('#modalTerimaDana').modal('hide');
                $('#formTerimaDana')[0].reset();
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
                    Swal.fire('Error!', 'Server error', 'error');
                }
            },
            complete: function () {
                toggleButtonLoading($btn, false);
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
$('select[name="program_id_koreksi"], select[name="bulan"], input[name="tahun"]').on('change', function() {
const programId = $('select[name="program_id_koreksi"]').val();
const bulan = $('select[name="bulan"]').val();
const tahun = $('input[name="tahun"]').val();

const $select = $('#divPenerima select');

console.log(programId, bulan, tahun); 
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
    console.log('Penerima loaded:', penerima); // DEBUG — CEK DI CONSOLE!

    if (!penerima || penerima.length === 0) {
        $select.html('<option value="">Tidak ada penerima aktif di bulan ini</option>');
        return;
    }

    let options = '<option value="">— Koreksi Umum (tidak spesifik penerima) —</option>';
    penerima.forEach(p => {
        options += `<option value="${p.id}">${p.nama} (Rp ${Number(p.nominal_bulanan).toLocaleString('id-ID')}/bln)</option>`;
    });
    $select.html(options);
})
.fail(function(xhr) {
    console.error('Error loading penerima:', xhr.responseText);
    $select.html('<option value="">Gagal memuat penerima</option>');
    alert('Gagal memuat data penerima. Cek console (F12) untuk detail.');
});
});

    // Saat pilih penerima → otomatis isi keterangan
    $('select[name="penerima_id"]').on('change', function() {
        const nama = $(this).find('option:selected').text().split(' (Rp')[0];
        const jumlah = $('[name="jumlah"]').val().replace(/\./g,'');
        const prefix = jumlah && parseInt(jumlah) > 0 ? 'Tambahan santunan' : 'Koreksi pengurangan santunan';
        
        if (this.value) {
            $('textarea[name="keterangan"]').val(`${prefix} untuk ${nama}`);
        } else {
            $('textarea[name="keterangan"]').val('');
        }
    });

    $('#formKoreksi').on('submit', function(e) {
        e.preventDefault();
        let jumlah = $('[name="jumlah_koreksi"]').val().replace(/\./g, '');
        console.log(jumlah);
        if (!jumlah || jumlah == 0) return Swal.fire('Error', 'Jumlah koreksi harus diisi!', 'error');

        Swal.fire({
            title: 'Yakin catat koreksi?',
            text: "Jurnal koreksi akan dibuat di bulan berjalan",
            icon: 'question',
            showCancelButton: true,
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.keuangan.dana-terikat.koreksi.realisasi.store") }}',
                    method: 'POST',
                    data: $(this).serialize() + '&jumlah=' + (jumlah.startsWith('-') ? jumlah : '+' + jumlah),
                    success: function() {
                        Swal.fire('Sukses!', 'Koreksi berhasil dicatat & jurnal otomatis dibuat', 'success');
                        $('#modalKoreksiRealisasi').modal('hide');
                        $('#tabelRealisasi').DataTable().ajax.reload();
                        loadSaldo();
                    },
                    error: function(xhr) {
                        Swal.fire('Gagal!', xhr.responseJSON?.message || 'Error', 'error');
                    }
                });
            }
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
        $.get(baseUrl, {
            tab: 'saldo',
            program: $('#filterProgram').val(),
            tahun: $('#filterTahun').val()
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
    }

    const tabelPenerima = $('#tabelPenerima').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        ajax: {
            url: baseUrl,
            data: function (d) {
                d.tab     = 'penerima';
                d.program = $('#filterProgram').val();
                d.tahun   = $('#filterTahun').val();
            }
        },
        columns: [
            { data: 'tahun_program',   name: 'tahun_program' },
            { data: 'program_nama',    name: 'program_nama' },
            { data: 'nama',            name: 'nama' },
            { data: 'kategori',        name: 'kategori' },
            { data: 'referensi_nama',  name: 'referensi_nama', defaultContent: '-' },
            { data: 'status_yatim',    name: 'status_yatim', orderable: false, searchable: false },
            { data: 'umur',            name: 'umur', orderable: false, searchable: false },
            { data: 'alamat',          name: 'alamat' },
            { data: 'rt_rw',           name: 'rt_rw' },
            { data: 'nama_rt',         name: 'nama_rt' },
            { data: 'nominal_bulanan', name: 'nominal_bulanan', className: 'text-end' },
            {
                data: 'status_aktif',
                name: 'status_aktif',
                orderable: false,
                searchable: false,
                className: 'text-center'
            },
            {
                data: 'id',
                render: function (id, type, row) {
                    return `
                        <button
                            class="btn btn-sm btn-warning edit-penerima"
                            data-id="${id}"
                            title="Edit"
                        >
                            <i class="fas fa-edit"></i>
                        </button>
                        <button
                            class="btn btn-sm btn-danger hapus-penerima"
                            data-id="${id}"
                            title="Hapus"
                        >
                            <i class="fas fa-trash"></i>
                        </button>
                    `;
                },
                orderable: false,
                searchable: false
            }
        ],
        rowCallback: function (row, data) {
            const $row = $(row);

            $row.css('background-color', '');

            const kategori  = (data.kategori || '').toString().toLowerCase();
            const refNama   = data.referensi_nama || '';
            const refWarna  = data.referensi_warna || '';

            if (kategori === 'yatim' && refNama) {
                const color = refWarna || stringToPastelColor(refNama);
                $row.css('background-color', color);
            }
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
        ]
    });

    const tabelRealisasi = $('#tabelRealisasi').DataTable({
        processing: true,
        serverSide: false,
        ajax: {
            url: baseUrl,
            data: function (d) {
                d.tab = 'realisasi';
                d.program = $('#filterProgram').val() || '';
                d.tahun = $('#filterTahun').val() || '';
            },
            dataSrc: function (json) {
                // KALAU DATA KOSONG, RETURN ARRAY KOSONG!
                return json && Array.isArray(json) ? json : [];
            }
        },
        columns: [
            { 
                data: 'bulan_tahun',
                render: data => data ? '<strong>' + data + '</strong>' : '-'
            },
            { data: 'program_nama', defaultContent: '-' },
            { 
                data: 'penerima_nama',
                render: (data, type, row) => row.tipe === 'koreksi' 
                    ? '<em class="text-muted">' + data + '</em>' 
                    : data
            },
            { 
                data: 'jumlah_tampil',
                className: 'text-end fw-bold',
                render: function(data, type, row) {
                    if (!data) return '-';
                    let jumlah = parseInt(data);
                    let prefix = jumlah >= 0 ? '' : '-';
                    let color = row.tipe === 'koreksi'
                        ? (jumlah >= 0 ? 'text-success' : 'text-danger')
                        : 'text-dark';
                    return '<span class="' + color + '">Rp ' + 
                           prefix + Math.abs(jumlah).toLocaleString('id-ID') + '</span>';
                }
            },
            {
                data: 'kwitansi',
                className: 'text-center',
                render: (data, type, row) => 
                    row.tipe === 'koreksi' 
                        ? '<span class="text-muted">—</span>'
                        : '<button class="btn btn-sm btn-outline-primary print-kwitansi" data-id="' + row.id + '"><i class="fas fa-print"></i></button>'
            },
            {
                data: 'tipe',
                render: data => data === 'koreksi'
                    ? '<span class="badge bg-warning text-dark fw-bold">KOREKSI</span>'
                    : '<span class="badge bg-success">NORMAL</span>'
            }
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: {
            emptyTable: "Belum ada realisasi atau koreksi di periode ini",
            zeroRecords: "Tidak ada data yang sesuai"
        }
    });

    $('#filterProgram, #filterTahun').on('change', function () {
        loadSaldo();
        tabelPenerima.ajax.reload();
        tabelPenerimaan.ajax.reload();
        tabelRealisasi.ajax.reload();
    });

    // initial load
    loadSaldo();

</script>
@endpush
