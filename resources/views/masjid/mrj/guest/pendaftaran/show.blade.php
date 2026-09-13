@extends('masjid.master-guest')

@section('title', 'Daftar Anak Yatim & Dhuafa - Santunan Ramadhan '.$selectedYear)

@section('og_title', 'Daftar Penerima Santunan Ramadhan 1447H – Masjid Raudhotul Jannah')

@section('meta_description',
'Transparansi data penerima santunan Ramadhan Masjid Raudhotul Jannah Taman Cipulir Estate. Berisi daftar anak yatim & dhuafa yang menerima amanah jamaah.')

@section('og_image', secure_url('images/default-ramadhan.jpg'))

@section('og_type','article')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-9xl mx-auto">
            <div id="pageYearActionsHeader" class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-emerald-600">Program Tahunan</p>
                    <h1 class="mt-1 text-2xl font-extrabold text-slate-900 sm:text-3xl">Santunan Ramadhan</h1>
                    <p class="mt-1 text-sm text-slate-500">Tahun Program <span id="pageYearContext" class="font-semibold text-slate-700">{{ $selectedYear }}</span></p>
                </div>

                <div id="yearActionMenu" class="relative w-full sm:w-auto">
                    <button id="btnYearActions" type="button" aria-haspopup="menu" aria-expanded="false" aria-controls="yearActionDropdown"
                            class="flex min-h-12 w-full items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-3 font-bold text-white shadow-md transition hover:from-indigo-700 hover:to-violet-700 hover:shadow-lg focus:outline-none focus:ring-4 focus:ring-indigo-200 sm:w-auto sm:justify-center">
                        <span>Aksi Tahun</span>
                        <span id="yearActionYear" class="rounded-md bg-white/15 px-2 py-0.5 text-xs font-semibold">{{ $selectedYear }}</span>
                        <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 9-7 7-7-7" />
                        </svg>
                    </button>

                    <div id="yearActionDropdown" role="menu" aria-labelledby="btnYearActions"
                         class="absolute left-0 right-0 z-40 mt-2 hidden rounded-xl border border-slate-200 bg-white p-2 shadow-xl sm:left-auto sm:w-80">
                        <button id="btnStartYear" type="button" role="menuitem"
                                class="flex w-full items-start gap-3 rounded-lg px-3 py-3 text-left transition hover:bg-indigo-50 focus:bg-indigo-50 focus:outline-none">
                            <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                                </svg>
                            </span>
                            <span class="min-w-0">
                                <strong class="block text-sm text-slate-900">Mulai Data Tahun Baru</strong>
                                <span id="startYearContext" class="mt-0.5 block text-xs text-slate-500">{{ $selectedYear }} → {{ $selectedYear + 1 }}</span>
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filter Section (UI baru sesuai referensi) -->
            <div id="filterDataCard" class="bg-white rounded-2xl shadow-lg border border-emerald-100/60 p-6 lg:p-10 mb-10">
                <div class="mb-6">
                    <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">Filter Data</p>
                    <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-2">Cari Data Santunan</h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        Gunakan filter di bawah untuk mencari data anak yatim & dhuafa. Ketik di pencarian untuk filter cepat.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-5 items-end">
                    <div class="form-control">
                        <label class="label pb-1" for="filterTahun"><span class="label-text font-semibold text-slate-800">Tahun Program</span></label>
                        <select id="filterTahun" class="w-full px-4 py-3.5 rounded-xl border-2 border-emerald-400 focus:border-emerald-600 focus:ring-2 focus:ring-emerald-200 outline-none bg-white text-slate-900">
                            @foreach($yearOptions as $year)
                                <option value="{{ $year }}" @selected($year === $selectedYear)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1" for="filterSumber"><span class="label-text font-semibold text-slate-800">Sumber Informasi</span></label>
                        <select id="filterSumber" class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white text-slate-900">
                            <option value="">Semua sumber</option>
                            @foreach($sumberList as $sumber)
                                <option value="{{ $sumber }}">{{ $sumber }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label pb-1" for="filterKategori"><span class="label-text font-semibold text-slate-800">Kategori</span></label>
                        <select id="filterKategori" class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white text-slate-900">
                            <option value="">Semua kategori</option>
                            @foreach($categoryList as $category)
                                <option value="{{ $category }}">{{ $category === 'dhuafa' ? 'DHUAFA' : 'YATIM YANG DHUAFA' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label pb-1" for="filterRw"><span class="label-text font-semibold text-slate-800">RW</span></label>
                        <select id="filterRw" class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white text-slate-900">
                            <option value="">Semua RW</option>
                            @foreach($rwList as $rw)
                                <option value="{{ $rw }}">{{ $rw }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-control">
                        <label class="label pb-1" for="filterRt"><span class="label-text font-semibold text-slate-800">RT</span></label>
                        <select id="filterRt" class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white text-slate-900">
                            <option value="">Semua RT</option>
                            @foreach($rtList as $rt)
                                <option value="{{ $rt }}">{{ $rt }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_auto] gap-5 items-end mt-5">
                    <div class="form-control">
                        <label class="label pb-1" for="globalSearch"><span class="label-text font-semibold text-slate-800">Cari Nama Penerima</span></label>
                        <div class="relative">
                            <input type="search" id="globalSearch" placeholder="Contoh: Muhammad"
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none placeholder-slate-400 text-slate-900 bg-white" />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🔍</span>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button id="btnApplyFilter" class="px-7 py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl shadow-md transition">Cari / Filter</button>
                        <button id="btnResetFilter" class="px-7 py-3.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl border border-slate-300 transition">Reset</button>
                    </div>
                </div>
            </div>

            <div id="excelCardsGrid" class="mb-10 grid grid-cols-1 items-stretch gap-6 lg:grid-cols-2">

                <!-- =======================================
                     CARD IMPORT
                ======================================== -->
                <div id="importExcelCard" class="flex h-full min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-800">Import Data Excel</h3>
                            <p class="mt-0.5 text-xs text-slate-500">Import participation ke tahun program yang dipilih.</p>
                        </div>
                        <a href="{{ route('santunan-ramadhan.template') }}"
                           class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-sky-700 transition hover:text-sky-900">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0-4-4m4 4 4-4M4 20h16" />
                            </svg>
                            Download Template
                        </a>
                    </div>

                    <form id="formImportExcel"
                          action="{{ route('santunan-ramadhan.import') }}"
                          method="POST"
                          enctype="multipart/form-data"
                          class="flex flex-1 flex-col gap-4">
                        @csrf

                        <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-[minmax(150px,0.4fr)_minmax(0,1fr)]">
                            <div>
                                <label for="importYear" class="mb-1 block text-xs font-semibold text-slate-700">Tahun Program</label>
                                <select id="importYear" name="tahun_program" required class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-3 py-2.5 text-slate-800">
                                    @foreach($yearOptions as $year)
                                        <option value="{{ $year }}" @selected($year === $selectedYear)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="min-w-0">
                                <label for="fileImport" class="mb-1 block text-xs font-semibold text-slate-700">File Excel</label>
                                <input type="file"
                                       name="file"
                                       id="fileImport"
                                       required
                                       accept=".xlsx,.xls"
                                       class="min-h-12 w-full rounded-xl border-2 border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:font-semibold file:text-slate-700 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200">
                            </div>
                        </div>

                        <p class="text-xs text-slate-500">Format XLSX/XLS · Tahun target wajib dipilih.</p>

                        <div id="importCardAction" class="mt-auto pt-2">
                            <button type="submit"
                                    id="btnImportExcel"
                                    class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-3 font-bold text-white shadow transition hover:from-emerald-700 hover:to-teal-700">

                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 4v12m0 0l4-4m-4 4l-4-4M4 20h16"/>
                                </svg>

                                <span id="textImport">Import Data</span>
                                <span id="loadingImport" class="hidden" aria-hidden="true">
                                    <span class="loading loading-spinner loading-sm"></span>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>


                <!-- =======================================
                     CARD EXPORT
                ======================================== -->
                <div id="exportExcelCard" class="flex h-full min-w-0 flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <div class="mb-4">
                        <h3 class="text-xl font-bold text-slate-800">Export Excel Santunan</h3>
                        <p class="mt-0.5 text-xs text-slate-500">
                            Pilih export satu sumber atau seluruh sumber informasi.
                        </p>
                    </div>

                    <form id="formExportSantunan"
                          method="POST"
                          action="{{ route('santunan-ramadhan.export') }}"
                          class="flex flex-1 flex-col gap-4">
                        @csrf
                        <input id="exportYear" type="hidden" name="tahun_program" value="{{ $selectedYear }}">

                        <fieldset class="space-y-2">
                            <legend class="mb-1 text-sm font-semibold text-slate-700">Mode Export</legend>
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3">
                                <input id="exportModeSelected" type="radio" name="export_mode" value="selected" checked
                                    class="mt-1 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block font-semibold text-slate-800">Export Sumber Terpilih</span>
                                    <span class="block text-sm text-slate-500">Satu sumber ke dua sheet kategori.</span>
                                </span>
                            </label>
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3">
                                <input id="exportModeAll" type="radio" name="export_mode" value="all"
                                    class="mt-1 h-4 w-4 border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                <span>
                                    <span class="block font-semibold text-slate-800">Export Semuanya</span>
                                    <span class="block text-sm text-slate-500">Setiap sumber informasi dibuat menjadi satu sheet.</span>
                                </span>
                            </label>
                        </fieldset>

                        <div id="exportSourceField">
                            <label for="filterSumberExport" class="mb-2 block text-sm font-semibold text-slate-700">Sumber Informasi</label>
                            <select id="filterSumberExport" name="sumber_informasi" required
                                class="w-full px-4 py-3 rounded-xl border-2 border-slate-300
                                       focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200
                                       outline-none bg-white text-slate-800">
                            <option value="">Pilih Sumber Informasi</option>
                            @foreach($sumberList as $s)
                                <option value="{{ $s }}">{{ $s }}</option>
                            @endforeach
                            </select>
                        </div>

                        <div class="space-y-1">
                            <p id="exportHelper" class="text-xs leading-relaxed text-slate-500">
                                Export satu sumber ke dua sheet kategori.
                            </p>
                            <p id="exportValidation" class="text-xs font-semibold text-red-600" role="alert">
                                Silakan pilih sumber informasi terlebih dahulu.
                            </p>
                        </div>

                        <div id="exportCardAction" class="mt-auto pt-2">
                            <button id="btnExportBySumber" type="submit" disabled
                                class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl
                                       bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-3
                                       font-bold text-white shadow transition
                                       hover:from-indigo-700 hover:to-purple-700
                                       disabled:cursor-not-allowed disabled:opacity-50">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"/>
                                </svg>
                                Export Excel
                            </button>
                        </div>
                    </form>

                </div>

            </div>



            <div id="exportLoading"
                 class="hidden fixed inset-0 bg-black/40 backdrop-blur-sm z-[999] flex items-center justify-center">

                <div class="bg-white rounded-2xl shadow-2xl p-8 flex flex-col items-center gap-4">
                    <span class="loading loading-spinner loading-lg text-emerald-600"></span>
                    <span class="font-semibold text-slate-700">Menyiapkan file Excel...</span>
                </div>
            </div>

            <div id="importOverlay"
                 class="hidden fixed inset-0 z-50 bg-black/40 backdrop-blur-sm
                        flex items-center justify-center">

                <div class="bg-white rounded-2xl shadow-2xl p-10 text-center">
                    <span class="loading loading-spinner loading-lg text-emerald-600"></span>
                    <p class="mt-4 text-lg font-semibold text-slate-700">
                        Mengupload & memproses data...
                    </p>
                    <p class="text-sm text-slate-500 mt-1">
                        Mohon tunggu, jangan menutup halaman
                    </p>
                </div>
            </div>
            <div id="dataViewControls" class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
                <section id="viewSelectionCard" class="min-w-0 rounded-2xl border border-emerald-100 bg-white p-4 shadow-sm" aria-labelledby="viewSwitcherLabel">
                    <h2 id="viewSwitcherLabel" class="text-base font-bold text-slate-800">Tampilan</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pilih cara menampilkan data peserta.</p>
                    <div class="mt-3 inline-flex w-full min-w-0 overflow-hidden rounded-xl border border-slate-300 bg-white shadow-sm sm:w-auto" role="tablist" aria-labelledby="viewSwitcherLabel">
                        <button id="btnViewTable" type="button" role="tab" aria-selected="true" aria-controls="tableViewPanel" tabindex="0"
                                class="min-h-11 flex-1 bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white transition focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-700 sm:flex-none">
                            Tabel
                        </button>
                        <button id="btnViewGrouped" type="button" role="tab" aria-selected="false" aria-controls="groupedViewPanel" tabindex="-1"
                                class="min-h-11 flex-1 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 transition hover:bg-emerald-50 focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-700 sm:flex-none">
                            Grouping
                        </button>
                    </div>
                </section>

                <section id="tableModeControls" class="min-w-0 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-labelledby="tableModeLabel" aria-hidden="false">
                    <h2 id="tableModeLabel" class="text-base font-bold text-slate-800">Mode Tabel</h2>
                    <p class="mt-0.5 text-sm text-slate-500">Pilih perilaku tabel peserta.</p>
                    <div class="mt-3 flex w-full min-w-0 flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm sm:inline-flex sm:w-auto sm:flex-row" role="group" aria-labelledby="tableModeLabel">
                        <button id="btnModeResponsive" type="button" aria-pressed="true" aria-controls="tabelYatimDhuafa"
                                class="min-h-11 flex-1 bg-slate-800 px-4 py-2.5 text-xs font-semibold text-white transition focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-slate-700 sm:flex-none">
                            Responsive
                        </button>
                        <button id="btnModeFull" type="button" aria-pressed="false" aria-controls="tabelYatimDhuafa"
                                class="min-h-11 flex-1 bg-white px-4 py-2.5 text-xs font-semibold text-slate-700 transition focus-visible:z-10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-slate-700 sm:flex-none">
                            Full Scroll
                        </button>
                    </div>
                </section>
            </div>

            <section id="tableViewPanel" role="tabpanel" aria-labelledby="btnViewTable">
                <div class="min-w-0 max-w-full overflow-x-auto rounded-3xl border border-emerald-100/60 bg-white p-4 shadow-2xl sm:p-6 lg:p-10">
                    <table id="tabelYatimDhuafa" class="table table-zebra w-full text-slate-900">
                        <thead></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section id="groupedViewPanel" class="hidden" role="tabpanel" aria-labelledby="btnViewGrouped">
                <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Data Peserta</p>
                        <h2 class="text-2xl font-bold text-slate-900 sm:text-3xl">Santunan Yatim & Dhuafa</h2>
                    </div>
                    <p id="groupedGrandTotal" class="text-sm font-semibold text-slate-600" aria-live="polite"></p>
                </div>

                <div id="groupedLoading" class="rounded-3xl border border-emerald-100 bg-white p-12 text-center shadow-lg">
                    <span class="loading loading-spinner loading-lg text-emerald-600"></span>
                    <p class="mt-4 font-semibold text-slate-700">Memuat kelompok penerima...</p>
                </div>

                <div id="groupedError" class="hidden rounded-2xl border border-red-200 bg-red-50 p-6 text-red-700" role="alert"></div>

                <div id="groupedEmpty" class="hidden rounded-3xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
                    <div class="text-4xl" aria-hidden="true">🔍</div>
                    <h3 class="mt-4 text-xl font-bold text-slate-800">Data tidak ditemukan</h3>
                    <p class="mt-2 text-slate-600">Tidak ada penerima yang sesuai dengan filter atau pencarian.</p>
                </div>

                <div id="groupedResults" class="hidden space-y-8"></div>
            </section>

            <div class="mt-8 flex flex-col sm:flex-row justify-center lg:justify-end gap-4 items-center">
                <button id="btnScanDuplikat"
                        class="px-8 py-4 bg-gradient-to-r from-rose-600 to-pink-600 hover:from-rose-700 hover:to-pink-700 
                               text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 
                               text-base flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Scan Duplikat (<span id="scanYearLabel">{{ $selectedYear }}</span>)
                </button>
            </div>
            <!-- Hasil Scan -->
            <div id="duplikatSection" class="mt-12 bg-white rounded-3xl shadow-2xl overflow-hidden border border-rose-200/70 p-6 lg:p-10 hidden">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between mb-6 gap-4">
                    <div>
                        <h3 id="duplikatTitle" class="text-2xl font-bold text-rose-700">Hasil Scan Duplikat</h3>
                        <p class="text-slate-600 mt-1">
                            Pasangan data dengan kemiripan nama ≥ 80%. Periksa & hapus/merge jika diperlukan.
                        </p>
                    </div>
                    <button id="hideDuplikat" class="btn btn-sm btn-outline text-rose-700 border-rose-400 hover:bg-rose-50">
                        Tutup Hasil
                    </button>
                </div>

                <div id="duplikatLoading" class="hidden text-center py-10">
                    <span class="loading loading-spinner loading-lg text-rose-600"></span>
                    <p class="mt-4 text-lg font-medium text-slate-700">Sedang memindai data tahun {{ now()->year }}...</p>
                    <p class="text-sm text-slate-500 mt-2">Proses ini bisa memakan waktu beberapa detik tergantung jumlah data.</p>
                </div>

                <table id="tabelDuplikat" class="table table-zebra w-full text-slate-900">
                    <thead class="bg-rose-600 text-white">
                        <tr>
                            <th>No</th>
                            <th>Record 1</th>
                            <th>Record 2</th>
                            <th class="text-center">Kemiripan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- CTA Daftar Baru -->
            <div class="text-center mt-10">
                @if($registrationOpen)
                    <a href="{{ route('santunan-ramadhan.form', ['tahun' => $selectedYear]) }}"
                       class="inline-block px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base">
                        Daftar Anak Baru
                    </a>
                @else
                    <span class="inline-block px-10 py-4 bg-slate-200 text-slate-600 font-bold rounded-full text-base" aria-disabled="true">
                        Pendaftaran Ditutup
                    </span>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Modal Detail (Read-Only) -->
    <dialog id="detailModal" class="modal">
        <div class="modal-box max-w-4xl max-h-[90vh] overflow-y-auto text-slate-800">
            <div class="modal-header flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900">Detail Data Anak</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700 text-2xl" aria-label="Tutup detail" onclick="document.getElementById('detailModal').close()">✕</button>
            </div>
            <div id="detailLoading" class="py-12 text-center text-emerald-700">
                <span class="loading loading-spinner loading-md"></span>
                <p class="mt-3">Memuat detail...</p>
            </div>
            <div id="detailError" class="hidden my-5 rounded-xl bg-red-50 p-4 text-red-700" role="alert"></div>
            <div id="detailContent" class="modal-body hidden">
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div><dt class="detail-label">Nama Lengkap</dt><dd id="detailNama" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Nama Panggilan</dt><dd id="detailPanggilan" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Kategori</dt><dd id="detailKategori" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Jenis Kelamin</dt><dd id="detailJenisKelamin" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Tanggal Lahir</dt><dd id="detailTanggalLahir" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Umur</dt><dd id="detailUmur" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Nama Orang Tua / Wali</dt><dd id="detailNamaOrtu" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Pekerjaan Orang Tua / Wali</dt><dd id="detailPekerjaanOrtu" class="detail-value"></dd></div>
                    <div><dt class="detail-label">RT</dt><dd id="detailRt" class="detail-value"></dd></div>
                    <div><dt class="detail-label">RW</dt><dd id="detailRw" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Nama RT / Koordinator</dt><dd id="detailNamaRt" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Nomor WhatsApp</dt><dd id="detailNoWa" class="detail-value"></dd></div>
                    <div class="sm:col-span-2"><dt class="detail-label">Alamat Lengkap</dt><dd id="detailAlamat" class="detail-value whitespace-pre-wrap"></dd></div>
                    <div><dt class="detail-label">Penanggung Jawab Informasi</dt><dd id="detailSumber" class="detail-value"></dd></div>
                    <div><dt class="detail-label">Tahun Program</dt><dd id="detailTahun" class="detail-value"></dd></div>
                    <div class="sm:col-span-2"><dt class="detail-label">Keterangan Tambahan</dt><dd id="detailCatatan" class="detail-value whitespace-pre-wrap"></dd></div>
                </dl>
            </div>
            <div class="modal-footer flex justify-end mt-6">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('detailModal').close()">Tutup</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button aria-label="Tutup detail">close</button>
        </form>
    </dialog>

    <!-- Modal Edit (Guest bisa edit semua field) -->
    <dialog id="editModal" class="modal">
        <div class="modal-box max-w-4xl">
            <form id="editForm">
                @csrf
                <input type="hidden" name="_method" value="PUT">
                <input type="hidden" id="editId" name="id">
                <input type="hidden" id="editYear" name="tahun_program">

                <div class="modal-header flex items-center justify-between">
                    <h3 class="text-xl font-bold text-slate-900">Edit Data Pendaftaran</h3>
                    <button type="button" class="text-slate-500 hover:text-slate-700 text-2xl" onclick="document.getElementById('editModal').close()">✕</button>
                </div>

                <div class="modal-body space-y-7">

                    <!-- Penanggung Jawab Informasi -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Penanggung Jawab Informasi <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <input type="text" name="sumber_informasi" id="editSumber" required
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="Penanggung Jawab Informasi"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🔍</span>
                        </div>
                    </div>

                    <!-- Nomor WA -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Nomor WA (opsional, untuk konfirmasi)</span>
                        </label>
                        <div class="relative">
                            <input type="tel" name="no_wa" id="editNoWa"
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="08xxxxxxxxxx" pattern="^08[0-9]{8,13}$"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📱</span>
                        </div>
                    </div>

                    <!-- Kategori -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Kategori Penerima <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <select name="kategori" required id="editKategori"
                                    class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white appearance-none">
                                <option value="" disabled selected>Pilih salah satu</option>
                                <option value="yatim_dhuafa">YATIM YANG DHUAFA</option>
                                <option value="dhuafa">DHUAFA</option>
                            </select>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👶</span>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                        </div>
                    </div>

                    <!-- Nama Lengkap & Panggilan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Nama Lengkap Anak <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <input type="text" name="nama_lengkap" id="editNamaLengkap" required
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Nama lengkap anak"/>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👤</span>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Nama Panggilan</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="nama_panggilan" id="editNamaPanggilan"
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Nama sehari-hari (misal: Adit)"/>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">😊</span>
                            </div>
                        </div>
                    </div>

                    <!-- Jenis Kelamin -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Jenis Kelamin <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <select name="jenis_kelamin" required id="editJenisKelamin"
                                    class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white appearance-none">
                                <option value="" disabled selected>Pilih jenis kelamin</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">⚥</span>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                        </div>
                    </div>

                    <!-- Tanggal Lahir & Umur -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Tanggal Lahir <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <input
                                    type="text"
                                    name="tanggal_lahir_text"
                                    id="editTanggalLahirText"
                                    placeholder="dd/mm/yyyy"
                                    class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white"
                                    inputmode="numeric"
                                />
                                <input type="hidden" name="tanggal_lahir" id="editTanggalLahirHidden" />
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📅</span>
                            </div>
                            <label class="label">
                                <span class="label-text-alt text-sm text-slate-500 italic">
                                    Format: Hari/Bulan/Tahun (contoh: 15/08/2015)
                                </span>
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Umur Saat Ini <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative flex items-center gap-3">
                                <input type="number" name="umur" id="editUmur" min="0" max="13" required
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Akan otomatis jika tgl lahir diisi" />
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🎂</span>

                                <!-- Dropdown satuan - default hidden, muncul hanya jika tgl lahir kosong -->
                                <select name="umur_satuan" id="editUmurSatuan"
                                        class="w-full p-2 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none bg-white select select-bordered select-md hidden !bg-emerald-600 !text-white !border-emerald-700">
                                    <option value="" disabled selected>Pilih satuan</option>
                                    <option value="tahun">Tahun</option>
                                    <option value="bulan">Bulan</option>
                                    <option value="hari">Hari</option>
                                </select>

                                <!-- Badge - muncul hanya jika tgl lahir terisi valid -->
                                <span id="editUmurDetailBadge"
                                      class="badge bg-emerald-600 text-white badge-lg hidden whitespace-nowrap px-4 py-3 font-medium">
                                </span>
                            </div>
                            <label class="label">
                                <span class="label-text-alt text-sm text-slate-500 italic" id="editUmurHelper">
                                    Akan otomatis ter-update jika tanggal lahir diisi
                                </span>
                            </label>
                        </div>
                    </div>

                    <!-- Alamat -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Alamat Lengkap <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <textarea name="alamat" id="editAlamat" rows="3" required
                                      class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                      placeholder="Contoh: Jl. Merdeka No. 45, Kel. Sukamaju, Kec. Cibeureum, Kota Tasikmalaya"></textarea>
                            <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">🏠</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label pb-1" for="editRt"><span class="label-text font-semibold text-slate-800">RT (opsional)</span></label>
                            <input type="text" name="rt" id="editRt" maxlength="5" inputmode="numeric"
                                   class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-slate-900 bg-white"
                                   placeholder="Contoh: 006">
                        </div>
                        <div class="form-control">
                            <label class="label pb-1" for="editRw"><span class="label-text font-semibold text-slate-800">RW (opsional)</span></label>
                            <input type="text" name="rw" id="editRw" maxlength="5" inputmode="numeric"
                                   class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-slate-900 bg-white"
                                   placeholder="Contoh: 007">
                        </div>
                    </div>

                    <div class="form-control">
                        <label class="label pb-1" for="editNamaRt"><span class="label-text font-semibold text-slate-800">Nama RT / Koordinator (opsional)</span></label>
                        <input type="text" name="nama_rt" id="editNamaRt" maxlength="150"
                               class="w-full px-4 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-slate-900 bg-white"
                               placeholder="Contoh: Pak Parno">
                    </div>

                    <!-- Nama Orang Tua & Pekerjaan -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Nama Orang Tua / Wali <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <input type="text" name="nama_orang_tua" id="editNamaOrtu" required
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Nama ayah / ibu / wali"/>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👨‍👩‍👧</span>
                            </div>
                        </div>
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Pekerjaan Orang Tua / Wali</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="pekerjaan_orang_tua" id="editPekerjaanOrtu"
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Contoh: Buruh, Ibu Rumah Tangga, Wiraswasta"/>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">💼</span>
                            </div>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Keterangan Tambahan</span>
                        </label>
                        <div class="relative">
                            <textarea name="catatan_tambahan" id="editCatatan" rows="4"
                                      class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                      placeholder="Keterangan Tambahan"></textarea>
                            <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">📝</span>
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <button type="submit" id="btn-submit-edit"
                            class="px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base w-full sm:w-auto flex items-center justify-center gap-2">

                            <!-- Spinner -->
                            <svg id="btn-edit-spinner"
                                 class="hidden w-5 h-5 animate-spin text-white"
                                 viewBox="0 0 24 24">
                                <circle class="opacity-25"
                                        cx="12" cy="12" r="10"
                                        stroke="currentColor"
                                        stroke-width="4"
                                        fill="none" />
                                <path class="opacity-75"
                                      fill="currentColor"
                                      d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                            </svg>

                            <!-- Text -->
                            <span id="btn-edit-text">Simpan Perubahan</span>
                        </button>

                        <button type="button" class="btn btn-outline w-full sm:w-auto" onclick="document.getElementById('editModal').close()">
                            Batal
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </dialog>

    <dialog id="startYearModal" class="modal" aria-labelledby="startYearModalTitle">
        <div class="modal-box max-h-[90vh] w-[calc(100%-2rem)] max-w-4xl overflow-y-auto p-5 text-slate-800 sm:w-full sm:p-7">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="startYearModalTitle" class="text-xl font-bold text-slate-900 sm:text-2xl">Mulai Data Tahun Baru</h3>
                    <p id="startYearModalContext" class="mt-1 text-sm text-slate-500">Mulai dari data {{ $selectedYear }} → {{ $selectedYear + 1 }}</p>
                </div>
                <button id="closeStartYearModal" type="button" class="rounded-lg p-2 text-2xl leading-none text-slate-500 transition hover:bg-slate-100 hover:text-slate-800" aria-label="Tutup workflow">✕</button>
            </div>
            <form id="startYearForm" class="mt-6 space-y-5">
                @csrf
                <input id="startSourceYear" name="source_year" type="hidden" value="{{ $selectedYear }}">
                <input id="startTargetYear" name="target_year" type="hidden" value="{{ $selectedYear + 1 }}">

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Tahun sumber</span>
                        <strong id="startSourceYearDisplay" class="mt-1 block text-xl text-slate-900">{{ $selectedYear }}</strong>
                    </div>
                    <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <span class="block text-xs font-semibold uppercase tracking-wide text-indigo-600">Tahun baru</span>
                        <strong id="startTargetYearDisplay" class="mt-1 block text-xl text-indigo-900">{{ $selectedYear + 1 }}</strong>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <span class="block text-xs font-semibold uppercase tracking-wide text-slate-500">Peserta sumber</span>
                        <strong id="startCandidateCount" class="mt-1 block text-xl text-slate-900">Memuat…</strong>
                    </div>
                </div>

                <p id="startYearExplanation" class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-relaxed text-amber-900">
                    Peserta yang dipilih akan dibuat sebagai participation baru pada tahun <strong id="startTargetYearInExplanation">{{ $selectedYear + 1 }}</strong>.
                    Data tahun <strong id="startSourceYearInExplanation">{{ $selectedYear }}</strong> tidak akan diubah.
                </p>

                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 font-semibold">
                    <input id="selectAllYearCandidates" type="checkbox" class="h-5 w-5"> Pilih semua peserta
                </label>
                <div id="yearCandidatesLoading" class="py-8 text-center">Memuat peserta…</div>
                <div id="yearCandidates" class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-slate-200 p-3"></div>

                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button id="cancelStartYear" type="button" class="min-h-12 rounded-xl border border-slate-300 bg-white px-6 py-3 font-bold text-slate-700 transition hover:bg-slate-50">Batal</button>
                    <button id="startYearContinue" type="submit" disabled class="min-h-12 rounded-xl bg-indigo-600 px-6 py-3 font-bold text-white transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-slate-300">Lanjutkan</button>
                </div>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>close</button></form>
    </dialog>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">


<script>
    let table = null;
    let tableMode = 'responsive';
    let activeDataView = 'table';
    let tableDirty = false;
    let groupedDirty = true;
    let groupedLoaded = false;
    let lastSelectedSatuan = '';

    function syncExportMode() {
        const mode = $('input[name="export_mode"]:checked').val();
        const source = $('#filterSumberExport').val();
        const exportAll = mode === 'all';

        $('#filterSumberExport')
            .prop('disabled', exportAll)
            .prop('required', !exportAll);
        $('#exportSourceField').toggleClass('opacity-50', exportAll);
        $('#btnExportBySumber').prop('disabled', !exportAll && !source);
        $('#exportValidation').toggleClass('hidden', exportAll || Boolean(source));
        $('#exportHelper').text(exportAll
            ? 'Export seluruh sumber informasi. Setiap sumber dibuat menjadi satu sheet.'
            : 'Export satu sumber ke dua sheet kategori.');
    }

    $('input[name="export_mode"], #filterSumberExport').on('change', syncExportMode);
    syncExportMode();

    $('#formExportSantunan').on('submit', function (event) {
        const mode = $('input[name="export_mode"]:checked').val();
        const source = $('#filterSumberExport').val();

        if (mode !== 'all' && !source) {
            event.preventDefault();
            $('#exportValidation').removeClass('hidden');
            Swal.fire({
                icon: 'warning',
                text: 'Silakan pilih sumber informasi terlebih dahulu.',
                confirmButtonColor: '#4f46e5'
            });
            return;
        }

        $('#exportLoading').removeClass('hidden');
        setTimeout(() => $('#exportLoading').addClass('hidden'), 1500);
    });

    let importSubmitting = false;

    function syncImportButton() {
        const hasFile = ($('#fileImport')[0]?.files?.length || 0) > 0;
        $('#btnImportExcel')
            .prop('disabled', importSubmitting || !hasFile)
            .toggleClass('opacity-50 cursor-not-allowed', importSubmitting || !hasFile);
    }

    function setImportSubmitting(submitting) {
        importSubmitting = submitting;
        $('#loadingImport').toggleClass('hidden', !submitting);
        $('#textImport').text(submitting ? 'Mengimpor...' : 'Import Data');
        syncImportButton();
    }

    syncImportButton();

    $('#fileImport').off('change.santunanImport').on('change.santunanImport', syncImportButton);

    $('#formImportExcel').off('submit.santunanImport').on('submit.santunanImport', function (event) {
        event.preventDefault();

        if (importSubmitting) {
            return;
        }

        const form = this;
        const targetYear = $('#importYear').val();
        const formData = new FormData(form);
        setImportSubmitting(true);

        $.ajax({
            url: $(form).attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 120000,
        })
            .done(function (response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Import Berhasil',
                    text: response.message,
                    confirmButtonColor: '#059669',
                });

                refreshDataViews();
                form.reset();
                $('#importYear').val(targetYear);
            })
            .fail(function (xhr, textStatus) {
                let message = escapeHtml(xhr.responseJSON?.message || 'Import gagal. Silakan periksa file dan coba kembali.');

                if (textStatus === 'timeout') {
                    message = 'Import melewati batas waktu. Silakan periksa koneksi dan coba kembali.';
                } else if (xhr.status === 0) {
                    message = 'Import gagal karena koneksi terputus. Silakan coba kembali.';
                }

                if (xhr.responseJSON?.detail) {
                    message += '<br><br><b>Detail:</b><br>' + xhr.responseJSON.detail.map(escapeHtml).join('<br>');
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Import Dibatalkan',
                    html: message,
                    width: 600,
                });
            })
            .always(function () {
                setImportSubmitting(false);
            });
    });

    
    let groupedRequest = null;
    let searchTimer = null;

    function currentFilterData() {
        return {
            tahun: Number($('#filterTahun').val()),
            sumber_informasi: $('#filterSumber').val() || null,
            kategori: $('#filterKategori').val() || null,
            rw: $('#filterRw').val() || null,
            rt: $('#filterRt').val() || null,
            search: $('#globalSearch').val().trim() || null
        };
    }

    function tableColumns(mode) {
        const columns = [
            { data: 'DT_RowIndex', orderable: false, searchable: false },
            { data: 'sumber_informasi', defaultContent: '-' },
            { data: 'no_wa', defaultContent: '-' },
            { data: 'kategori_display', defaultContent: '-' },
            { data: 'nama_lengkap', defaultContent: '-' },
            { data: 'nama_panggilan', defaultContent: '-' },
            { data: 'jenis_kelamin_display', defaultContent: '-' },
            { data: 'tanggal_lahir_formatted', defaultContent: '-' },
            { data: 'umur_display', defaultContent: '-' },
            { data: 'nama_orang_tua', defaultContent: '-' },
            { data: 'pekerjaan_orang_tua', defaultContent: '-' },
            { data: 'alamat', defaultContent: '-' },
            { data: 'catatan_tambahan', defaultContent: '-' },
            { data: 'tahun_program', defaultContent: '-' },
            {
                data: null,
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function (data, type, row) {
                    const safeId = JSON.stringify(row?.id ?? 0);

                    return `
                        <div class="flex justify-center gap-2">
                            <button class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-lg"
                                    onclick="openEditModal(${safeId})">Edit</button>
                            <button class="btn btn-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg"
                                    onclick="hapusData(${safeId})">Hapus</button>
                        </div>`;
                }
            }
        ];

        if (mode === 'responsive') {
            columns.unshift({ data: null, defaultContent: '', className: 'control', orderable: false, searchable: false });
        }

        return columns;
    }

    function initTable(mode = 'responsive') {
        if (table && tableMode === mode) {
            return;
        }

        if (table) {
            table.destroy();
        }

        const controlHeading = mode === 'responsive' ? '<th></th>' : '';
        $('#tabelYatimDhuafa thead').html(`
            <tr>
                ${controlHeading}
                <th>No</th>
                <th>Penanggung Jawab Informasi</th>
                <th>No WA</th>
                <th>Kategori</th>
                <th>Nama Anak</th>
                <th>Panggilan</th>
                <th>Jenis Kelamin</th>
                <th>Tanggal Lahir</th>
                <th>Umur</th>
                <th>Nama Orang Tua / Wali</th>
                <th>Pekerjaan Orang Tua / Wali</th>
                <th>Alamat Lengkap</th>
                <th>Keterangan Tambahan</th>
                <th>Tahun</th>
                <th class="text-center">Aksi</th>
            </tr>`);
        $('#tabelYatimDhuafa tbody').empty();

        table = $('#tabelYatimDhuafa').DataTable({
            processing: true,
            serverSide: true,
            order: [mode === 'responsive' ? 1 : 0, 'asc'],
            paging: true,
            searching: false,
            dom: 'lrtip',
            responsive: mode === 'responsive' ? {
                details: { type: 'column', target: 0 }
            } : false,
            scrollX: mode === 'full',
            scrollCollapse: true,
            columnDefs: mode === 'responsive'
                ? [{ className: 'control', orderable: false, targets: 0 }]
                : [],
            ajax: {
                url: '{{ route("santunan-ramadhan.data") }}',
                data: function (request) {
                    Object.assign(request, currentFilterData());
                }
            },
            columns: tableColumns(mode),
            language: {
                processing: '<div class="flex items-center gap-3 text-emerald-600"><span class="loading loading-spinner loading-md"></span> Memuat data...</div>',
                emptyTable: 'Belum ada data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
                infoEmpty: 'Tidak ada entri',
                infoFiltered: '(disaring dari total _MAX_ entri)',
                lengthMenu: 'Tampilkan _MENU_ entri',
                zeroRecords: 'Tidak ada data yang cocok',
                paginate: {
                    first: '« Pertama',
                    last: 'Terakhir »',
                    next: 'Selanjutnya ›',
                    previous: '‹ Sebelumnya'
                }
            }
        });

        tableMode = mode;
        tableDirty = false;
        $('#btnModeResponsive')
            .attr('aria-pressed', mode === 'responsive')
            .toggleClass('bg-slate-800 text-white', mode === 'responsive')
            .toggleClass('bg-white text-slate-700 hover:bg-slate-50', mode !== 'responsive');
        $('#btnModeFull')
            .attr('aria-pressed', mode === 'full')
            .toggleClass('bg-slate-800 text-white', mode === 'full')
            .toggleClass('bg-white text-slate-700 hover:bg-slate-50', mode !== 'full');
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function displayValue(value) {
        return value === null || value === undefined || String(value).trim() === ''
            ? 'Belum diisi'
            : value;
    }

    function renderRecipientTable(recipients) {
        const rows = recipients.map((recipient, index) => {
            const id = Number(recipient.id);
            const gender = recipient.jenis_kelamin === 'L' ? 'L' : (recipient.jenis_kelamin === 'P' ? 'P' : '—');

            return `
                <tr data-recipient-id="${id}" class="border-b border-slate-100 last:border-0">
                    <td data-label="No">${index + 1}</td>
                    <td data-label="Nama Anak" class="font-semibold text-slate-900">${escapeHtml(displayValue(recipient.nama_lengkap))}</td>
                    <td data-label="JK">${gender}</td>
                    <td data-label="Tanggal Lahir">${escapeHtml(displayValue(recipient.tanggal_lahir))}</td>
                    <td data-label="Umur">${escapeHtml(displayValue(recipient.umur))}</td>
                    <td data-label="Orang Tua / Wali">${escapeHtml(displayValue(recipient.nama_orang_tua))}</td>
                    <td data-label="Alamat">${escapeHtml(displayValue(recipient.alamat))}</td>
                    <td data-label="Aksi">
                        <div class="flex flex-wrap gap-2 md:justify-center">
                            <button type="button" class="grouped-action bg-emerald-600 hover:bg-emerald-700" onclick="openDetailModal(${id})">Detail</button>
                            <button type="button" class="grouped-action bg-amber-500 hover:bg-amber-600" onclick="openEditModal(${id})">Edit</button>
                            <button type="button" class="grouped-action bg-red-500 hover:bg-red-600" onclick="hapusData(${id})">Hapus</button>
                        </div>
                    </td>
                </tr>`;
        }).join('');

        return `
            <div class="grouped-table-wrap">
                <table class="grouped-recipient-table">
                    <thead>
                        <tr>
                            <th>No</th><th>Nama Anak</th><th>JK</th><th>Tanggal Lahir</th>
                            <th>Umur</th><th>Orang Tua / Wali</th><th>Alamat</th><th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    }

    function renderGroupedResults(groups) {
        return groups.map(source => `
            <article class="source-group">
                <header class="source-group-header">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-200">Sumber Informasi</p>
                        <h3 class="mt-1 text-2xl sm:text-3xl font-black uppercase text-white break-words">${escapeHtml(source.label)}</h3>
                    </div>
                    <span class="group-total group-total-dark">${source.total} penerima</span>
                </header>
                <div class="space-y-6 p-4 sm:p-6">
                    ${source.categories.map(category => `
                        <section class="category-group">
                            <header class="category-group-header">
                                <h4 class="text-lg sm:text-xl font-black uppercase text-emerald-900">${escapeHtml(category.label)}</h4>
                                <span class="group-total">${category.total} penerima</span>
                            </header>
                            <div class="space-y-5 p-3 sm:p-5">
                                ${category.rws.map(rw => `
                                    <section class="rw-group">
                                        <div class="rw-group-header">
                                            <h5 class="font-extrabold text-slate-800">RW ${escapeHtml(rw.label)}</h5>
                                            <span class="text-sm font-semibold text-slate-500">${rw.total} penerima</span>
                                        </div>
                                        <div class="space-y-4 p-3 sm:p-4">
                                            ${rw.rts.map(rt => `
                                                <section class="rt-group">
                                                    <header class="rt-group-header">
                                                        <h6 class="font-extrabold text-slate-900">RT ${escapeHtml(rt.label)} / RW ${escapeHtml(rw.label)}</h6>
                                                        <span class="text-sm font-bold text-emerald-700">${rt.total} penerima</span>
                                                    </header>
                                                    <div class="coordinator-list">
                                                        ${rt.coordinators.map(coordinator => `
                                                            <section class="coordinator-group">
                                                                ${coordinator.label ? `
                                                                    <header class="coordinator-group-header">
                                                                        <div>
                                                                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Koordinator / Nama RT</p>
                                                                            <p class="mt-1 font-extrabold text-slate-900">${escapeHtml(coordinator.label)}</p>
                                                                        </div>
                                                                        <span class="text-sm font-semibold text-slate-500">${coordinator.total} penerima</span>
                                                                    </header>
                                                                ` : ''}
                                                                ${renderRecipientTable(coordinator.recipients)}
                                                            </section>
                                                        `).join('')}
                                                    </div>
                                                </section>
                                            `).join('')}
                                        </div>
                                    </section>
                                `).join('')}
                            </div>
                        </section>
                    `).join('')}
                </div>
            </article>
        `).join('');
    }

    function loadGroupedData() {
        groupedRequest?.abort();
        $('#groupedLoading').removeClass('hidden');
        $('#groupedResults, #groupedEmpty, #groupedError').addClass('hidden');

        groupedRequest = $.ajax({
            url: '{{ route("santunan-ramadhan.data-grouped") }}',
            method: 'GET',
            data: currentFilterData(),
            success: function (response) {
                groupedLoaded = true;
                groupedDirty = false;
                $('#groupedGrandTotal').text(`Total hasil: ${response.total} penerima`);
                $('#groupedLoading').addClass('hidden');

                if (!response.groups.length) {
                    $('#groupedEmpty').removeClass('hidden');
                    return;
                }

                $('#groupedResults').html(renderGroupedResults(response.groups)).removeClass('hidden');
            },
            error: function (xhr, status) {
                if (status === 'abort') return;
                $('#groupedLoading').addClass('hidden');
                $('#groupedError')
                    .text(xhr.responseJSON?.message || 'Data penerima tidak dapat dimuat. Silakan coba lagi.')
                    .removeClass('hidden');
            },
            complete: function () {
                groupedRequest = null;
            }
        });
    }

    function updateViewSwitcher() {
        const tableActive = activeDataView === 'table';

        $('#btnViewTable')
            .attr('aria-selected', tableActive)
            .attr('tabindex', tableActive ? '0' : '-1')
            .toggleClass('bg-emerald-600 text-white', tableActive)
            .toggleClass('bg-white text-slate-700 hover:bg-emerald-50', !tableActive);
        $('#btnViewGrouped')
            .attr('aria-selected', !tableActive)
            .attr('tabindex', tableActive ? '-1' : '0')
            .toggleClass('bg-emerald-600 text-white', !tableActive)
            .toggleClass('bg-white text-slate-700 hover:bg-emerald-50', tableActive);
        $('#tableModeControls')
            .toggleClass('hidden', !tableActive)
            .attr('aria-hidden', String(!tableActive));
    }

    function setDataView(view) {
        if (view === activeDataView) return;

        activeDataView = view;
        const tableActive = view === 'table';
        $('#tableViewPanel').toggleClass('hidden', !tableActive);
        $('#groupedViewPanel').toggleClass('hidden', tableActive);
        updateViewSwitcher();

        if (tableActive) {
            groupedRequest?.abort();
            if (tableDirty) {
                table.ajax.reload();
                tableDirty = false;
            } else {
                table.columns.adjust();
                table.responsive?.recalc();
            }
            return;
        }

        if (!groupedLoaded || groupedDirty) {
            loadGroupedData();
        }
    }

    function refreshDataViews() {
        if (activeDataView === 'table') {
            groupedDirty = true;
            table.ajax.reload();
            tableDirty = false;
            return;
        }

        tableDirty = true;
        loadGroupedData();
    }

    $('#btnViewTable').off('click.santunanView').on('click.santunanView', () => setDataView('table'));
    $('#btnViewGrouped').off('click.santunanView').on('click.santunanView', () => setDataView('grouped'));
    $('#btnModeResponsive').off('click.santunanView').on('click.santunanView', () => initTable('responsive'));
    $('#btnModeFull').off('click.santunanView').on('click.santunanView', () => initTable('full'));

    $('[role="tab"][aria-controls$="ViewPanel"]').off('keydown.santunanView').on('keydown.santunanView', function (event) {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) {
            return;
        }

        event.preventDefault();
        const nextView = $(this).attr('id') === 'btnViewTable' ? 'grouped' : 'table';
        setDataView(nextView);
        $(nextView === 'table' ? '#btnViewTable' : '#btnViewGrouped').trigger('focus');
    });
    $('#btnApplyFilter').on('click', refreshDataViews);

    $('#filterSumber, #filterKategori, #filterRw, #filterRt').on('change', function () {
        tableDirty = true;
        groupedDirty = true;
    });

    function replaceOptions(selector, placeholder, values, labeler = value => value) {
        const select = $(selector);
        select.empty().append($('<option>', { value: '', text: placeholder }));
        values.forEach(value => select.append($('<option>', { value, text: labeler(value) })));
    }

    function updateStartYearContext(sourceYear) {
        const targetYear = sourceYear + 1;
        $('#pageYearContext, #yearActionYear').text(sourceYear);
        $('#startYearContext').text(`${sourceYear} → ${targetYear}`);
        $('#startYearModalContext').text(`Mulai dari data ${sourceYear} → ${targetYear}`);
        $('#startSourceYear').val(sourceYear);
        $('#startTargetYear').val(targetYear);
        $('#startSourceYearDisplay, #startSourceYearInExplanation').text(sourceYear);
        $('#startTargetYearDisplay, #startTargetYearInExplanation').text(targetYear);
    }

    function closeYearActionMenu() {
        $('#yearActionDropdown').addClass('hidden');
        $('#btnYearActions').attr('aria-expanded', 'false');
    }

    $('#btnYearActions').off('click.santunanYearAction').on('click.santunanYearAction', function (event) {
        event.stopPropagation();
        const willOpen = $('#yearActionDropdown').hasClass('hidden');
        $('#yearActionDropdown').toggleClass('hidden', !willOpen);
        $(this).attr('aria-expanded', String(willOpen));
    });

    $(document).off('click.santunanYearAction').on('click.santunanYearAction', function (event) {
        if (!$(event.target).closest('#yearActionMenu').length) {
            closeYearActionMenu();
        }
    });

    $(document).off('keydown.santunanYearAction').on('keydown.santunanYearAction', function (event) {
        if (event.key === 'Escape') {
            closeYearActionMenu();
        }
    });

    $('#filterTahun').on('change', function () {
        const year = Number($(this).val());
        const url = new URL(window.location.href);
        url.searchParams.set('tahun', year);
        window.history.replaceState({}, '', url);
        $('#exportYear').val(year);
        $('#importYear').val(year);
        $('#scanYearLabel').text(year);
        updateStartYearContext(year);

        $.get('{{ route("santunan-ramadhan.filter-options") }}', { tahun: year })
            .done(function (response) {
                replaceOptions('#filterSumber', 'Semua sumber', response.sources);
                replaceOptions('#filterSumberExport', 'Pilih Sumber Informasi', response.sources);
                replaceOptions('#filterKategori', 'Semua kategori', response.categories, value => value === 'dhuafa' ? 'DHUAFA' : 'YATIM YANG DHUAFA');
                replaceOptions('#filterRw', 'Semua RW', response.rws);
                replaceOptions('#filterRt', 'Semua RT', response.rts);
                $('#globalSearch').val('');
                syncExportMode();
                refreshDataViews();
            })
            .fail(xhr => Swal.fire('Gagal', xhr.responseJSON?.message || 'Pilihan filter tahun tidak dapat dimuat.', 'error'));
    });

    $('#globalSearch').on('input', function () {
        tableDirty = true;
        groupedDirty = true;
        clearTimeout(searchTimer);
        searchTimer = setTimeout(refreshDataViews, 350);
    });

    $('#globalSearch').on('keydown', function (event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            clearTimeout(searchTimer);
            refreshDataViews();
        }
    });

    initTable('responsive');

    // Fungsi hapus data
    function hapusData(id) {
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("santunan-ramadhan.destroy", ":id") }}'.replace(':id', id),
                    method: 'DELETE',
                    data: { tahun_program: Number($('#filterTahun').val()) },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        refreshDataViews();
                        Swal.fire('Terhapus!', res.message || 'Data berhasil dihapus', 'success');
                    },
                    error: function (xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    }

    $('#btnResetFilter').on('click', function () {
        $('#filterSumber').val('');
        $('#filterKategori').val('');
        $('#filterRw').val('');
        $('#filterRt').val('');
        $('#globalSearch').val('');
        refreshDataViews();
    });

    // =============================================
    // Logic Umur + Satuan + Badge (koreksi: badge hidden saat tgl kosong)
    // =============================================
    function setupUmurEdit() {
        const tglText   = $('#editTanggalLahirText');
        const tglHidden = $('#editTanggalLahirHidden');
        const umur      = $('#editUmur');
        const helper    = $('#editUmurHelper');
        const satuan    = $('#editUmurSatuan');
        const badge     = $('#editUmurDetailBadge');

        // Masking tanggal + switch mode
        tglText.off('input').on('input', function(e) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 8) val = val.slice(0,8);

            if (val.length > 4) val = val.slice(0,2) + '/' + val.slice(2,4) + '/' + val.slice(4);
            else if (val.length > 2) val = val.slice(0,2) + '/' + val.slice(2);

            e.target.value = val;

            if (val.length === 10) {
                const [dd, mm, yyyy] = val.split('/');
                tglHidden.val(`${yyyy}-${mm.padStart(2,'0')}-${dd.padStart(2,'0')}`);
                satuan.addClass('hidden');
                hitungDanTampilkanBadge();
            } else {
                tglHidden.val('');
                umur.val('').prop('readonly', false);
                satuan.removeClass('hidden');
                badge.addClass('hidden');  // Pastikan badge hidden saat tgl kosong
                // Restore satuan terakhir
                if (lastSelectedSatuan) {
                    satuan.val(lastSelectedSatuan);
                }
                helper.text('Tanggal lahir kosong → isi umur manual + pilih satuan')
                      .removeClass('text-red-600').addClass('text-amber-700');
            }
        });

        // Simpan pilihan satuan terakhir
        satuan.off('change').on('change', function() {
            lastSelectedSatuan = $(this).val();
        });

        // Input manual umur
        umur.off('input').on('input', function() {
            if (tglHidden.val() !== '') return;

            if ($(this).val().trim() !== '') {
                helper.text('Umur manual → pilih satuan di sebelah')
                      .addClass('text-amber-700').removeClass('text-red-600');
                badge.addClass('hidden');
            } else {
                helper.text('Isi tanggal lahir atau umur manual')
                      .removeClass('text-amber-700 text-red-600');
            }
        });
    }

    function hitungDanTampilkanBadge() {
        const tglHidden = $('#editTanggalLahirHidden');
        const umur      = $('#editUmur');
        const helper    = $('#editUmurHelper');
        const badge     = $('#editUmurDetailBadge');

        const dateStr = tglHidden.val();
        if (!dateStr) {
            badge.addClass('hidden');  // ekstra jaga-jaga
            return;
        }

        const birth = new Date(dateStr);
        const today = new Date();

        if (isNaN(birth.getTime()) || birth > today) {
            helper.addClass('text-red-600').text('Tanggal lahir tidak valid atau di masa depan');
            badge.addClass('hidden');
            umur.prop('readonly', false);
            return;
        }

        let years  = today.getFullYear() - birth.getFullYear();
        let months = today.getMonth() - birth.getMonth();
        let days   = today.getDate() - birth.getDate();

        if (months < 0 || (months === 0 && days < 0)) { years--; months += 12; }
        if (days < 0) { months--; days += new Date(today.getFullYear(), today.getMonth(), 0).getDate(); }

        // Tentukan nilai umur & satuan badge
        let nilaiUtama = years;
        let satuanBadge = 'Tahun';

        if (years < 1) {
            nilaiUtama = months;
            satuanBadge = 'Bulan';
        }
        if (years < 1 && months < 1) {
            nilaiUtama = days;
            satuanBadge = 'Hari';
        }
        if (nilaiUtama === 0) {
            satuanBadge = 'Baru lahir';
        }

        umur.val(nilaiUtama).prop('readonly', true);

        // Badge hanya satuan
        badge.text(satuanBadge)
             .removeClass('hidden badge-error')
             .addClass('badge-success');

        // Validasi
        if (years > 13) {
            helper.addClass('text-red-600').text('Usia melebihi 13 tahun (tidak memenuhi kriteria)');
            badge.removeClass('badge-success').addClass('badge-error');
        } else {
            helper.removeClass('text-red-600 text-amber-700')
                  .text('Umur dihitung otomatis dari tanggal lahir');
        }
    }

    function detailValue(value) {
        return value === null || value === undefined || String(value).trim() === '' ? 'Belum diisi' : value;
    }

    function openDetailModal(id) {
        const modal = document.getElementById('detailModal');
        $('#detailLoading').removeClass('hidden');
        $('#detailContent, #detailError').addClass('hidden');
        $('#detailError').text('');
        modal.showModal();

        $.ajax({
            url: '{{ route("santunan-ramadhan.edit", ":id") }}'.replace(':id', id),
            method: 'GET',
            success: function (row) {
                let tanggalLahir = 'Belum diisi';
                if (row.tanggal_lahir) {
                    const parts = row.tanggal_lahir.split('-');
                    tanggalLahir = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }

                const kategori = row.kategori === 'yatim_dhuafa' ? 'YATIM YANG DHUAFA' : 'DHUAFA';
                const jenisKelamin = row.jenis_kelamin === 'L' ? 'Laki-laki' : 'Perempuan';
                const umur = row.umur && row.umur_satuan ? `${row.umur} ${row.umur_satuan}` : 'Belum diisi';

                $('#detailNama').text(detailValue(row.nama_lengkap));
                $('#detailPanggilan').text(detailValue(row.nama_panggilan));
                $('#detailKategori').text(kategori);
                $('#detailJenisKelamin').text(jenisKelamin);
                $('#detailTanggalLahir').text(tanggalLahir);
                $('#detailUmur').text(umur);
                $('#detailNamaOrtu').text(detailValue(row.nama_orang_tua));
                $('#detailPekerjaanOrtu').text(detailValue(row.pekerjaan_orang_tua));
                $('#detailRt').text(detailValue(row.rt));
                $('#detailRw').text(detailValue(row.rw));
                $('#detailNamaRt').text(detailValue(row.nama_rt));
                $('#detailNoWa').text(detailValue(row.no_wa));
                $('#detailAlamat').text(detailValue(row.alamat));
                $('#detailSumber').text(detailValue(row.sumber_informasi));
                $('#detailTahun').text(detailValue(row.tahun_program));
                $('#detailCatatan').text(detailValue(row.catatan_tambahan));

                $('#detailLoading').addClass('hidden');
                $('#detailContent').removeClass('hidden');
            },
            error: function (xhr) {
                $('#detailLoading').addClass('hidden');
                $('#detailError')
                    .text(xhr.responseJSON?.message || 'Detail data tidak dapat dimuat. Silakan coba lagi.')
                    .removeClass('hidden');
            }
        });
    }

    function openEditModal(id) {
        $.ajax({
            url: '{{ route("santunan-ramadhan.edit", ":id") }}'.replace(':id', id),
            method: 'GET',
            success: function(row) {
                $('#editId').val(row.id);
                $('#editYear').val(row.tahun_program);
                $('#editNamaLengkap').val(row.nama_lengkap || '');
                $('#editNamaPanggilan').val(row.nama_panggilan || '');
                $('#editKategori').val(row.kategori || '');
                $('#editJenisKelamin').val(row.jenis_kelamin || '');
                $('#editAlamat').val(row.alamat || '');
                $('#editRt').val(row.rt || '');
                $('#editRw').val(row.rw || '');
                $('#editNamaRt').val(row.nama_rt || '');
                $('#editNoWa').val(row.no_wa || '');
                $('#editNamaOrtu').val(row.nama_orang_tua || '');
                $('#editPekerjaanOrtu').val(row.pekerjaan_orang_tua || '');
                $('#editSumber').val(row.sumber_informasi || '');
                $('#editCatatan').val(row.catatan_tambahan || '');

                // Tanggal lahir
                let tglDisplay = '';
                if (row.tanggal_lahir) {
                    const parts = row.tanggal_lahir.split('-');
                    tglDisplay = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                $('#editTanggalLahirText').val(tglDisplay);
                $('#editTanggalLahirHidden').val(row.tanggal_lahir || '');

                // Simpan nilai satuan dari DB
                lastSelectedSatuan = row.umur_satuan || '';

                // Aktifkan logic
                setupUmurEdit();

                // Inisialisasi tampilan awal
                if (row.tanggal_lahir) {
                    hitungDanTampilkanBadge();
                    $('#editUmurSatuan').addClass('hidden');
                } else {
                    $('#editUmurSatuan').removeClass('hidden');
                    if (lastSelectedSatuan) {
                        $('#editUmurSatuan').val(lastSelectedSatuan);
                    }
                    $('#editUmur').val(row.umur || '').prop('readonly', false);
                    $('#editUmurHelper').text('Tanggal lahir kosong → isi umur manual + pilih satuan')
                                       .addClass('text-amber-700');
                    $('#editUmurDetailBadge').addClass('hidden');  // <--- Koreksi utama: badge hidden saat load & tgl kosong
                }

                document.getElementById('editModal').showModal();
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memuat data', 'error');
            }
        });
    }

    // Submit form edit
    $('#editForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#editId').val();
        const $btn = $('#btn-submit-edit');
        const $spinner = $('#btn-edit-spinner');
        const $text = $('#btn-edit-text');

        $btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
        $spinner.removeClass('hidden');
        $text.text('Menyimpan...');

        $.ajax({
            url: '{{ route("santunan-ramadhan.update", ":id") }}'.replace(':id', id),
            method: 'PUT',
            data: $(this).serialize(),
            success: function (res) {
                document.getElementById('editModal').close();
                refreshDataViews();
                Swal.fire('Sukses', res.message || 'Data berhasil diperbarui', 'success');
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function () {
                $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                $spinner.addClass('hidden');
                $text.text('Simpan Perubahan');
            }
        });
    });

    let duplikatTable = null;

    $('#btnScanDuplikat').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<span class="loading loading-spinner loading-sm mr-2"></span> Memindai...');

        $('#duplikatSection').removeClass('hidden');
        $('#duplikatLoading').removeClass('hidden');

        $.ajax({
            url: '{{ route("santunan-ramadhan.scan-duplikat") }}',
            method: 'GET',
            data: { tahun: Number($('#filterTahun').val()) },
            success: function(response) {
                $('#duplikatLoading').addClass('hidden');

                let pairsArray = [];
                if (Array.isArray(response.pairs)) {
                    pairsArray = response.pairs;
                } else if (typeof response.pairs === 'object' && response.pairs !== null) {
                    pairsArray = Object.values(response.pairs);
                }

                pairsArray = pairsArray.filter(item => item && typeof item === 'object' && item.id_a);

                if (pairsArray.length === 0) {
                    Swal.fire('Peringatan', 'Tidak ada data valid dari server (cek controller).', 'warning');
                }

                $('#duplikatTitle').text(`Hasil Scan Duplikat - Tahun ${response.tahun || 'Tidak diketahui'}`);

                if (duplikatTable) {
                    duplikatTable.destroy();
                }
                $('#tabelDuplikat tbody').empty();

                duplikatTable = $('#tabelDuplikat').DataTable({
                    destroy: true,
                    data: pairsArray,
                    paging: true,
                    pageLength: 10,
                    searching: false,
                    info: true,
                    columns: [
                        { 
                            render: function (data, type, row, meta) {
                                return meta.row + meta.settings._iDisplayStart + 1;
                            }
                        },
                        {
                            render: function (data, type, row) {
                                const item = row || data || {};
                                return `
                                    <div class="font-medium text-slate-800">${item.nama_a || item.nama_lengkap || item.nama || '-'}</div>
                                    <div class="text-sm text-slate-600">Ortu: ${item.ortu_a || item.nama_orang_tua || '-'}</div>
                                    <div class="text-xs text-slate-500">Umur: ${item.umur_a || item.umur || '-'} • Tahun: ${item.tahun_a || item.tahun_program || '-'}</div>
                                    <div class="text-xs text-slate-500">${item.alamat_a || item.alamat || '-'}</div>
                                `;
                            }
                        },
                        {
                            render: function (data, type, row) {
                                const item = row || data || {};
                                return `
                                    <div class="font-medium text-slate-800">${item.nama_b || item.nama_lengkap || item.nama || '-'}</div>
                                    <div class="text-sm text-slate-600">Ortu: ${item.ortu_b || item.nama_orang_tua || '-'}</div>
                                    <div class="text-xs text-slate-500">Umur: ${item.umur_b || item.umur || '-'} • Tahun: ${item.tahun_b || item.tahun_program || '-'}</div>
                                    <div class="text-xs text-slate-500">${item.alamat_b || item.alamat || '-'}</div>
                                `;
                            }
                        },
                        {
                            data: 'similarity',
                            render: function (data) {
                                if (typeof data !== 'number') return '-';
                                let cls = data >= 90 ? 'text-red-700 font-bold' :
                                          data >= 80 ? 'text-amber-700 font-semibold' : 'text-rose-600';
                                return `<div class="text-center ${cls}">${data.toFixed(1)}%</div>`;
                            },
                            className: 'text-center font-medium'
                        },
                        {
                            render: function (data, type, row) {
                                const item = row || data || {};
                                if (!item.id_a || !item.id_b) return '-';
                                return `
                                    <div class="flex flex-col sm:flex-row gap-2 justify-center items-center">
                                        <button onclick="openEditModal(${item.id_a})" 
                                                class="btn btn-xs bg-amber-500 hover:bg-amber-600 text-white px-4 py-1 rounded shadow-sm">
                                            Edit #${item.id_a}
                                        </button>
                                        <button onclick="openEditModal(${item.id_b})" 
                                                class="btn btn-xs bg-amber-500 hover:bg-amber-600 text-white px-4 py-1 rounded shadow-sm">
                                            Edit #${item.id_b}
                                        </button>
                                    </div>
                                `;
                            },
                            orderable: false,
                            className: 'text-center py-2'
                        }
                    ],
                    language: {
                        emptyTable: 'Tidak ada data valid dari server',
                        info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ pasangan',
                        infoEmpty: 'Tidak ada data'
                    }
                });

                btn.prop('disabled', false).html(`
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Scan Duplikat (Tahun {{ now()->year }})
                `);
            },
            error: function(xhr) {
                $('#duplikatLoading').addClass('hidden');
                Swal.fire('Error', 'Gagal memindai: ' + (xhr.responseJSON?.message || 'Server error'), 'error');
                btn.prop('disabled', false).html('Scan Duplikat');
            }
        });
    });

    $('#hideDuplikat').on('click', function() {
        $('#duplikatSection').addClass('hidden');
    });

    function syncStartYearContinueState() {
        $('#startYearContinue').prop('disabled', $('.year-candidate:checked').length === 0);
    }

    function closeStartYearWorkflow() {
        document.getElementById('startYearModal').close();
        $('#startYearForm')[0].reset();
        $('#yearCandidates').empty();
        $('#startCandidateCount').text('Memuat…');
        $('#startYearContinue').prop('disabled', true).text('Lanjutkan');
    }

    $('#closeStartYearModal, #cancelStartYear').off('click.santunanStartYear').on('click.santunanStartYear', closeStartYearWorkflow);

    $('#btnStartYear').off('click.santunanStartYear').on('click.santunanStartYear', function () {
        const sourceYear = Number($('#filterTahun').val());
        closeYearActionMenu();
        updateStartYearContext(sourceYear);
        $('#selectAllYearCandidates').prop('checked', false);
        $('#yearCandidates').empty();
        $('#startCandidateCount').text('Memuat…');
        $('#startYearContinue').prop('disabled', true).text('Lanjutkan');
        $('#yearCandidatesLoading').removeClass('hidden');
        document.getElementById('startYearModal').showModal();

        $.get('{{ route("santunan-ramadhan.year-candidates") }}', { tahun: sourceYear })
            .done(function (response) {
                const rows = response.records || [];
                $('#startCandidateCount').text(rows.length);
                const html = rows.map(row => `
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3">
                        <input type="checkbox" name="participation_ids[]" value="${Number(row.id)}" class="year-candidate mt-1 h-4 w-4">
                        <span>
                            <strong>${escapeHtml(row.nama_lengkap)}</strong>
                            <span class="block text-xs text-slate-500">${escapeHtml(row.sumber_informasi || 'Sumber belum diisi')} · RT ${escapeHtml(row.rt || '-')} / RW ${escapeHtml(row.rw || '-')}</span>
                        </span>
                    </label>`).join('');
                $('#yearCandidates').html(html || '<p class="p-4 text-center text-slate-500">Tidak ada peserta pada tahun sumber.</p>');
            })
            .fail(function (xhr) {
                $('#startCandidateCount').text('Gagal');
                $('#yearCandidates').html(`<p class="p-4 text-red-600">${escapeHtml(xhr.responseJSON?.message || 'Data tidak dapat dimuat.')}</p>`);
            })
            .always(() => $('#yearCandidatesLoading').addClass('hidden'));
    });

    $('#selectAllYearCandidates').off('change.santunanStartYear').on('change.santunanStartYear', function () {
        $('.year-candidate').prop('checked', this.checked);
        syncStartYearContinueState();
    });

    $('#yearCandidates').off('change.santunanStartYear').on('change.santunanStartYear', '.year-candidate', function () {
        const candidates = $('.year-candidate');
        const checked = $('.year-candidate:checked');
        $('#selectAllYearCandidates').prop('checked', candidates.length > 0 && candidates.length === checked.length);
        syncStartYearContinueState();
    });

    $('#startYearForm').off('submit.santunanStartYear').on('submit.santunanStartYear', function (event) {
        event.preventDefault();
        if ($('.year-candidate:checked').length === 0) {
            Swal.fire('Pilih peserta', 'Pilih minimal satu peserta yang dilanjutkan.', 'warning');
            return;
        }

        const continueButton = $('#startYearContinue');
        continueButton.prop('disabled', true).text('Memproses…');

        $.post('{{ route("santunan-ramadhan.start-year") }}', $(this).serialize())
            .done(function (response) {
                document.getElementById('startYearModal').close();
                const targetYear = Number($('#startTargetYear').val());
                const skipped = Number(response.alreadyExists || 0);
                const message = skipped > 0
                    ? `${response.created} peserta dibuat. ${skipped} peserta dilewati karena data tahun ${targetYear} sudah tersedia.`
                    : response.message;
                Swal.fire(skipped > 0 ? 'Selesai dengan peringatan' : 'Sukses', message, skipped > 0 ? 'warning' : 'success');
                if (!$('#filterTahun option[value="' + targetYear + '"]').length) {
                    $('#filterTahun').prepend($('<option>', { value: targetYear, text: targetYear }));
                    $('#importYear').prepend($('<option>', { value: targetYear, text: targetYear }));
                }
            })
            .fail(xhr => Swal.fire('Gagal', Object.values(xhr.responseJSON?.errors || {}).flat().join('<br>') || xhr.responseJSON?.message || 'Tahun baru tidak dapat dibuat.', 'error'))
            .always(function () {
                continueButton.text('Lanjutkan');
                syncStartYearContinueState();
            });
    });
</script>

<!-- CSS Override (pastikan semua teks gelap & kolom terlihat) -->
<style>
    /* =========================
       GLOBAL TEXT COLOR FIX
       ========================= */
    body, .text-slate-50, .text-gray-50, .text-base-content {
        color: #0f172a !important;
    }

    .select, .input-bordered, .select option, .input input, textarea {
        color: #0f172a !important;
        background-color: white !important;
    }

    /* =========================
       DATATABLE BASE COLOR
       ========================= */
    .dataTables_wrapper,
    .dataTables_info,
    .dataTables_length,
    .dataTables_paginate,
    .dataTables_filter {
        color: #0f172a !important;
    }

    /* Loader overlay DataTables 1.13 */
    .dataTables_wrapper {
        position: relative;
    }

    .dataTables_wrapper .dataTables_processing {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.9) !important;
        padding: 20px 30px !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 50 !important;
        font-weight: 600;
        color: #059669 !important;
    }

    /* =========================
       PAGINATION STYLE
       ========================= */    
    .dataTables_paginate {
        display: flex !important;
        justify-content: center !important; /* center di semua ukuran */
        align-items: center !important;
        flex-wrap: wrap !important;
        gap: 0.5rem !important;
        margin-top: 1rem !important;
    }

    .dataTables_paginate .paginate_button {
        color: #059669 !important;
        background: white !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 6px 12px !important;
        margin-left: 4px !important;
        cursor: pointer !important;
    }

    .dataTables_paginate .paginate_button:hover {
        background: #ecfdf5 !important; /* emerald-50 */
        border-color: #059669 !important;
    }

    .dataTables_paginate .paginate_button.current {
        background: #059669 !important;
        color: white !important;
        border-color: #059669 !important;
    }

    .dataTables_paginate .paginate_button:hover:not(.disabled) {
        background: #ecfdf5 !important;
        color: #059669 !important;
        transform: scale(1.05);
    }

    /* Mobile: pagination tetap rapi & center */
    @media (max-width: 640px) {
        .dataTables_paginate {
            justify-content: center !important;
            padding: 0.5rem !important;
        }
        .dataTables_paginate .paginate_button {
            min-width: 2rem !important;
            height: 2rem !important;
            padding: 0.4rem 0.6rem !important;
            font-size: 0.8rem !important;
        }
        .dataTables_info {
            text-align: center !important;
            margin-bottom: 0.5rem !important;
        }
    }

    /* Hilangkan jarak aneh antar nomor */
    .dataTables_paginate .paginate_button + .paginate_button {
        margin-left: 0 !important;
    }

    /* =========================
       INFO + PAGINATION ALIGN FIX
       ========================= */
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        float: none !important;
        margin: 0;
    }

    /* Container baris bawah (DataTables 2.x) */
    .dataTables_wrapper .dt-layout-row:last-child {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1rem;
    }

    .dataTables_wrapper .dataTables_info {
        text-align: left !important;
    }

    .dataTables_wrapper .dataTables_paginate {
        text-align: right !important;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .dataTables_wrapper .dt-layout-row:last-child {
            flex-direction: column;
            align-items: flex-start;
        }

        .dataTables_wrapper .dataTables_paginate {
            width: 100%;
            text-align: left !important;
        }
    }

    /* =========================
       TABLE STYLING
       ========================= */
    /* Fix potong kanan di mode responsive */
    .dataTables_wrapper .dataTables_scrollBody {
        overflow-x: auto !important;
    }

    table.dataTable.dtr-inline.collapsed > tbody > tr > td:first-child,
    table.dataTable.dtr-inline.collapsed > tbody > tr > th:first-child {
        padding-left: 30px !important; /* ruang untuk tombol + */
    }

    /* Pastikan wrapper tidak memotong */
    .dataTables_wrapper {
        overflow: visible !important;
    }

    /* Responsive collapse tidak memotong kanan */
    .dtr-modal .dtr-modal-content {
        max-width: 90vw !important;
    }

    #tabelYatimDhuafa tbody td {
        color: #0f172a !important;
    }

    #tabelYatimDhuafa thead th {
        background-color: #059669 !important;
        color: #ffffff !important;
    }

    #tabelYatimDhuafa tbody tr:nth-child(odd) {
        background-color: #f1f5f9;
    }

    #tabelYatimDhuafa tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    #tabelYatimDhuafa tbody tr:hover {
        background-color: #d1fae5 !important;
        transition: background-color 0.15s ease-in-out;
    }

    #tabelYatimDhuafa td.control::before {
        all: unset !important;
        content: none !important;
        display: none !important;
    }

    #tabelYatimDhuafa td.control {
        cursor: pointer;
        position: relative;
        width: 40px;
    }

    #tabelYatimDhuafa td.control::after {
        align-items: center;
        background-color: #059669;
        border-radius: 6px;
        color: #ffffff;
        content: "+";
        display: flex;
        font-size: 16px;
        font-weight: 700;
        height: 22px;
        justify-content: center;
        margin: auto;
        transition: all 0.2s ease;
        width: 22px;
    }

    #tabelYatimDhuafa tr.parent td.control::after {
        background-color: #dc2626;
        content: "−";
    }

    #tableViewPanel .dataTables_scroll,
    #tableViewPanel .dataTables_scrollBody {
        max-width: 100%;
    }

    .source-group {
        overflow: hidden;
        border: 1px solid rgba(16, 185, 129, 0.25);
        border-radius: 1.5rem;
        background: #ffffff;
        box-shadow: 0 18px 45px rgba(15, 23, 42, 0.09);
    }

    .source-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1.25rem 1.5rem;
        background: linear-gradient(135deg, #047857, #0f766e);
    }

    .group-total {
        flex: none;
        border-radius: 9999px;
        background: #d1fae5;
        padding: 0.4rem 0.75rem;
        color: #047857;
        font-size: 0.75rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .group-total-dark {
        border: 1px solid rgba(255, 255, 255, 0.25);
        background: rgba(255, 255, 255, 0.14);
        color: #ffffff;
    }

    .category-group {
        overflow: hidden;
        border: 1px solid #d1fae5;
        border-radius: 1rem;
        background: #f8fafc;
    }

    .category-group-header,
    .rw-group-header,
    .rt-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .category-group-header {
        padding: 0.9rem 1.1rem;
        background: #ecfdf5;
        border-bottom: 1px solid #d1fae5;
    }

    .rw-group {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 0.9rem;
        background: #ffffff;
    }

    .rw-group-header {
        padding: 0.75rem 1rem;
        background: #f1f5f9;
        border-bottom: 1px solid #e2e8f0;
    }

    .rt-group {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #ffffff;
    }

    .rt-group-header {
        padding: 0.8rem 1rem;
        border-bottom: 1px solid #e2e8f0;
    }

    .coordinator-list {
        display: grid;
    }

    .coordinator-group + .coordinator-group {
        border-top: 1px solid #e2e8f0;
    }

    .coordinator-group-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.75rem 1rem;
        background: #f8fafc;
    }

    .grouped-table-wrap {
        max-width: 100%;
        overflow-x: auto;
    }

    .grouped-recipient-table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        color: #334155;
        font-size: 0.85rem;
    }

    .grouped-recipient-table th {
        padding: 0.7rem 0.8rem;
        background: #f8fafc;
        color: #475569;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-align: left;
        text-transform: uppercase;
    }

    .grouped-recipient-table td {
        padding: 0.8rem;
        vertical-align: top;
        overflow-wrap: anywhere;
    }

    .grouped-action {
        border-radius: 0.5rem;
        padding: 0.35rem 0.6rem;
        color: #ffffff;
        font-size: 0.75rem;
        font-weight: 700;
        transition: background-color 0.2s ease;
    }

    .detail-label {
        color: #64748b;
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }

    .detail-value {
        color: #0f172a;
        margin-top: 0.25rem;
        overflow-wrap: anywhere;
    }

    @media (max-width: 640px) {
        .source-group-header,
        .category-group-header,
        .rw-group-header,
        .rt-group-header {
            align-items: flex-start;
            flex-direction: column;
        }

        .grouped-table-wrap {
            overflow: visible;
        }

        .grouped-recipient-table,
        .grouped-recipient-table tbody,
        .grouped-recipient-table tr,
        .grouped-recipient-table td {
            display: block;
            width: 100%;
        }

        .grouped-recipient-table {
            min-width: 0;
        }

        .grouped-recipient-table thead {
            display: none;
        }

        .grouped-recipient-table tr {
            padding: 0.65rem 0;
        }

        .grouped-recipient-table td {
            display: grid;
            grid-template-columns: minmax(90px, 38%) minmax(0, 1fr);
            gap: 0.5rem;
            padding: 0.45rem 0.75rem;
        }

        .grouped-recipient-table td::before {
            content: attr(data-label);
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        #tabelYatimDhuafa tbody tr.child td.child {
            max-width: 100% !important;
            padding: 0.75rem !important;
            white-space: normal !important;
            width: auto !important;
        }

        #tabelYatimDhuafa tbody tr.child ul.dtr-details {
            display: block !important;
            max-width: 100% !important;
            width: 100% !important;
        }

        #tabelYatimDhuafa tbody tr.child ul.dtr-details > li {
            display: grid !important;
            gap: 0.25rem;
            grid-template-columns: minmax(0, 1fr);
            max-width: 100% !important;
        }

        #tabelYatimDhuafa .dtr-title,
        #tabelYatimDhuafa .dtr-data {
            display: block !important;
            max-width: 100% !important;
            overflow-wrap: anywhere;
            white-space: normal !important;
            word-break: break-word;
        }

        #detailModal .modal-box,
        #editModal .modal-box {
            max-height: calc(100dvh - 2rem);
            max-width: calc(100vw - 1rem);
            overflow-y: auto;
            padding: 1rem;
            width: calc(100vw - 1rem);
        }
    }

    /* =========================
       NAVBAR COLOR LOCK
       ========================= */
    nav {
        color: #e5e7eb !important;
    }

    nav a,
    nav span,
    nav div,
    nav li {
        color: #e5e7eb !important;
    }

    nav .group-hover\:text-emerald-200:hover {
        color: #a7f3d0 !important;
    }

    nav .text-emerald-200\/80 {
        color: rgba(167, 243, 208, 0.8) !important;
    }

    nav a:hover {
        color: #6ee7b7 !important;
    }

    nav .btn-outline {
        color: #a7f3d0 !important;
        border-color: rgba(52, 211, 153, 0.6) !important;
    }

    nav .btn-outline:hover {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #ecfdf5 !important;
    }

    nav details .menu a {
        color: #e5e7eb !important;
    }

    nav details .menu a:hover {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #ecfdf5 !important;
    }
</style>

@endpush
