@extends('masjid.master')
@section('title', 'Laporan Harian Ramadhan 1447H')

@push('style')
    <style>
        .swal2-container { z-index: 999999 !important; }
        dialog.modal[open] { z-index: 1; }
        .swal2-popup { z-index: 100000 !important; }
        .card-wrapper {
            max-width: 1400px;
            margin: 1.25rem auto;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(2,6,23,0.06);
            border: 1px solid rgba(15,23,42,0.04);
            background: white;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            color: #fff;
            background: linear-gradient(90deg, #059669 0%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-header .title { margin:0; font-size:1.125rem; font-weight:700; }
        .card-header .subtitle { margin:0; opacity:.95; font-size:.95rem; }
        .card-body { padding: 1.25rem 1.5rem; background: white; }
        .header-action {
            background: rgba(255,255,255,0.12);
            color: #fff;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            display: inline-flex;
            gap: .5rem;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 6px 14px rgba(4,120,87,0.06);
            transition: transform .12s ease, background .12s;
        }
        .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }
        table.dataTable td { white-space: normal !important; }
        .malam-column { width: 100px; text-align: center; }
        .date-column { width: 140px; text-align: center; }
        .saldo-column { width: 160px; text-align: right; font-weight: 600; }
        .btn-circle-ico {
            display:inline-flex; align-items:center; justify-content:center;
            width:36px; height:36px; border-radius:8px;
            transition: transform .12s ease;
        }
        .btn-circle-ico:hover { transform: translateY(-2px); }
        dialog.modal::backdrop {
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px) saturate(1.02);
        }
        dialog.modal { border: none; padding: 0; }
        .modal-box { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); overflow: hidden; background: white; }
        .input-plain {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.5rem 0.75rem;
            width: 100%;
            font-size: 0.875rem;
        }
        .input-plain:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
        .repeater-row { display: grid; grid-template-columns: 2fr 1fr auto; gap: 0.5rem; margin-bottom: 0.5rem; align-items: center; }
        .section-total { font-weight: 600; color: #047857; text-align: right; font-size: 1.05rem; }
        .relative-input { position: relative; }
        .rp-prefix { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6b7280; pointer-events: none; font-weight: 500; }
        @media (max-width: 640px) {
            .card-header { padding: .9rem 1rem; }
            .card-body { padding: 1rem; }
            .repeater-row { grid-template-columns: 1fr; }
        }
    </style>
@endpush

@section('content')
<div class="bg-emerald-50 p-6 rounded-xl mb-6 shadow-sm border border-emerald-100">
    <h4 class="text-xl font-bold text-emerald-800 mb-4 text-center">Total Kumulatif Ramadhan 1447H Sampai Saat Ini</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
        <div class="bg-white p-5 rounded-lg shadow text-center">
            <p class="text-sm text-gray-600 font-medium">Infaq Ramadhan</p>
            <p class="text-2xl font-bold text-emerald-700 mt-2" id="total-infaq">Rp 0</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow text-center">
            <p class="text-sm text-gray-600 font-medium">Saldo Iftor</p>
            <p class="text-2xl font-bold text-emerald-700 mt-2" id="total-ifthor">Rp 0</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow text-center">
            <p class="text-sm text-gray-600 font-medium">Santunan Yatim</p>
            <p class="text-2xl font-bold text-emerald-700 mt-2" id="total-santunan">Rp 0</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow text-center">
            <p class="text-sm text-gray-600 font-medium">Paket Sembako</p>
            <p class="text-2xl font-bold text-emerald-700 mt-2" id="total-sembako">Rp 0</p>
        </div>
        <div class="bg-white p-5 rounded-lg shadow text-center">
            <p class="text-sm text-gray-600 font-medium">Gebyar Ramadhan</p>
            <p class="text-2xl font-bold text-emerald-700 mt-2" id="total-gebyar">Rp 0</p>
        </div>
    </div>
</div>

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Laporan Harian Ramadhan 1447H</h3>
            <p class="subtitle">Input, edit, dan kelola laporan infaq, santunan, sumbangan barang, serta pengumuman tiap malam tarawih</p>
        </div>
        <button type="button" class="header-action" onclick="addLaporan()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Laporan Malam Ini</span>
        </button>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="laporanTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3 malam-column">Malam ke-</th>
                        <th class="px-4 py-3 date-column">Tanggal</th>
                        <th class="px-4 py-3">Imam</th>
                        <th class="px-4 py-3 saldo-column">Saldo Infaq Ramadhan</th>
                        <th class="px-4 py-3 saldo-column">Saldo Iftor</th>
                        <th class="px-4 py-3">Santunan Yatim</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form Lengkap -->
<dialog id="laporanModal" class="modal" aria-labelledby="laporanModalTitle">
    <div class="modal-box w-11/12 max-w-5xl max-h-[95vh] flex flex-col">
        <form id="laporanForm" class="flex-1 overflow-y-auto p-6 space-y-6">
            @csrf
            <input type="hidden" id="method" value="POST">
            <input type="hidden" name="id" id="laporanId">

            <!-- Header Sticky FIXED -->
            <div class="sticky top-0 z-30 bg-white/95 backdrop-blur-md shadow-sm border-b border-gray-200 -mx-6 px-6 py-4 mb-6">
                <div class="flex items-center justify-between">
                    <h3 id="laporanModalTitle" class="text-2xl font-bold text-emerald-800">Form Laporan Harian Tarawih</h3>
                    <button type="button" id="closeLaporanModal" class="text-gray-600 hover:text-gray-800 text-3xl font-bold transition-colors">×</button>
                </div>
            </div>

            <!-- Alpine Data -->
            <div x-data="formData()" x-init="init()" x-ref="formRamadhan" class="space-y-6 pt-2">
                <!-- 0. Info Dasar -->
                <div class="bg-base-100 p-5 rounded-xl shadow-sm border border-base-200">
                    <h4 class="text-lg font-semibold text-emerald-700 mb-4">Informasi Dasar</h4>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Malam ke-</label>
                            <input type="number" name="malam_ke" min="1" max="30" class="input-plain" required x-model="malam_ke">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                            <input type="date" name="tanggal" class="input-plain" required x-model="tanggal" x-ref="tanggalInput">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Jadwal Imam Malam Ini</label>
                            <select name="jadwal_imam_id" class="input-plain" x-model="jadwal_imam_id" x-ref="imamSelect">
                                <option value="">-- Pilih Imam --</option>
                                @foreach(\App\Models\JadwalImamTarawih::orderBy('malam_ke')->get() as $jadwal)
                                    <option value="{{ $jadwal->id }}">Malam {{ $jadwal->malam_ke }} - {{ $jadwal->imam_nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Accordion Lengkap -->
                <div class="join join-vertical w-full shadow-md">
                    <!-- 1. Infaq Ramadhan (versi upgrade dengan repeater pengeluaran) -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" checked="checked" />
                        <div class="collapse-title text-lg font-semibold">1. Infaq Ramadhan</div>
                        <div class="collapse-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Saldo Malam Kemarin</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text"
                                           class="input-plain pl-10"
                                           placeholder="0"
                                           :value="formatRupiah(infaq_ramadan.saldo_kemarin)"
                                           @input="infaq_ramadan.saldo_kemarin = unformatRupiah($event.target.value);
                                                   $event.target.value = formatRupiah(infaq_ramadan.saldo_kemarin)"
                                           @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="infaq_ramadan_saldo_kemarin" :value="infaq_ramadan.saldo_kemarin">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Penerimaan Tromol / Infaq Malam Kemarin</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text"
                                           class="input-plain pl-10"
                                           placeholder="0"
                                           :value="formatRupiah(infaq_ramadan.penerimaan_tromol)"
                                           @input="infaq_ramadan.penerimaan_tromol = unformatRupiah($event.target.value);
                                                   $event.target.value = formatRupiah(infaq_ramadan.penerimaan_tromol)"
                                           @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="infaq_ramadan_penerimaan_tromol" :value="infaq_ramadan.penerimaan_tromol">
                                </div>
                            </div>

                            <!-- Repeater Pengeluaran Operasional -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium mb-2">Breakdown Pengeluaran Operasional Hari Ini</label>
                                <template x-for="(item, index) in infaq_ramadan.pengeluaran" :key="index">
                                    <div class="repeater-row">
                                        <input type="text" x-model="item.untuk" placeholder="Untuk (contoh: cetak banner, listrik, dll)" class="input-plain">
                                        <div class="relative-input">
                                            <span class="rp-prefix">Rp</span>
                                            <input type="text" class="input-plain pl-10" placeholder="0"
                                                   x-model="item.nominal_formatted"
                                                   @input.debounce.150ms="item.nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.nominal)"
                                                   @focus="$event.target.select()">
                                        </div>
                                        <button type="button" @click="infaq_ramadan.pengeluaran.splice(index, 1)" class="btn btn-error btn-sm">Hapus</button>
                                    </div>
                                </template>
                                <button type="button" @click="infaq_ramadan.pengeluaran.push({ untuk: '', nominal: 0, nominal_formatted: '0' })" class="btn btn-primary btn-sm mt-2">+ Tambah Pengeluaran</button>
                                <input type="hidden" name="infaq_ramadan_pengeluaran_detail" :value="JSON.stringify(infaq_ramadan.pengeluaran.map(i => ({ untuk: i.untuk, nominal: i.nominal })))">
                            </div>

                            <div class="text-sm text-gray-600 italic mt-2">
                                Total Pengeluaran Operasional: Rp <span x-text="formatCurrency(total(infaq_ramadan.pengeluaran))"></span>
                            </div>
                            <div class="section-total">
                                Saldo Saat Ini: Rp <span x-text="formatCurrency(infaqSaldoSekarang)"></span>
                            </div>
                            <input type="hidden" name="infaq_ramadan_saldo_sekarang" :value="infaqSaldoSekarang">
                        </div>
                    </div>

                    <!-- 2. Infaq Untuk Iftor -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">2. Infaq Untuk Iftor</div>
                        <div class="collapse-content space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1">Saldo Iftor Malam Kemarin</label>
                                <div class="relative-input">
                                    <span class="rp-prefix">Rp</span>
                                    <input type="text" class="input-plain pl-10" placeholder="0"
                                           x-model="ifthor.saldo_kemarin_formatted"
                                           @input.debounce.150ms="ifthor.saldo_kemarin = unformatRupiah($event.target.value); $event.target.value = formatRupiah(ifthor.saldo_kemarin)"
                                           @focus="$event.target.select()">
                                </div>
                                <input type="hidden" name="ifthor_saldo_kemarin" :value="ifthor.saldo_kemarin">
                            </div>
                            <!-- Penerimaan Iftor -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium mb-2">Penerimaan Iftor Hari Ini</label>
                                <template x-for="(item, index) in ifthor.penerimaan" :key="index">
                                    <div class="repeater-row">
                                        <input type="text" x-model="item.dari" placeholder="Dari (nama/jamaah)" class="input-plain">
                                        <div class="relative-input">
                                            <span class="rp-prefix">Rp</span>
                                            <input type="text" class="input-plain pl-10" placeholder="0"
                                                   x-model="item.nominal_formatted"
                                                   @input.debounce.150ms="item.nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.nominal)"
                                                   @focus="$event.target.select()">
                                        </div>
                                        <button type="button" @click="ifthor.penerimaan.splice(index, 1)" class="btn btn-error btn-sm">Hapus</button>
                                    </div>
                                </template>
                                <button type="button" @click="ifthor.penerimaan.push({ dari: '', nominal: 0, nominal_formatted: '0' })" class="btn btn-primary btn-sm mt-2">+ Tambah Penerimaan</button>
                                <input type="hidden" name="ifthor_penerimaan_detail" :value="JSON.stringify(ifthor.penerimaan.map(i => ({ dari: i.dari, nominal: i.nominal })))">
                            </div>
                            <!-- Pengeluaran Iftor -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium mb-2">Pengeluaran Iftor Hari Ini</label>
                                <template x-for="(item, index) in ifthor.pengeluaran" :key="index">
                                    <div class="repeater-row">
                                        <input type="text" x-model="item.untuk" placeholder="Untuk (takjil/sahur/nasi dll)" class="input-plain">
                                        <div class="relative-input">
                                            <span class="rp-prefix">Rp</span>
                                            <input type="text" class="input-plain pl-10" placeholder="0"
                                                   x-model="item.nominal_formatted"
                                                   @input.debounce.150ms="item.nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.nominal)"
                                                   @focus="$event.target.select()">
                                        </div>
                                        <button type="button" @click="ifthor.pengeluaran.splice(index, 1)" class="btn btn-error btn-sm">Hapus</button>
                                    </div>
                                </template>
                                <button type="button" @click="ifthor.pengeluaran.push({ untuk: '', nominal: 0, nominal_formatted: '0' })" class="btn btn-primary btn-sm mt-2">+ Tambah Pengeluaran</button>
                                <input type="hidden" name="ifthor_pengeluaran_detail" :value="JSON.stringify(ifthor.pengeluaran.map(i => ({ untuk: i.untuk, nominal: i.nominal })))">
                            </div>
                            <div class="section-total">
                                Saldo Iftor Saat Ini: Rp <span x-text="formatCurrency(ifthorSaldoSekarang)"></span>
                            </div>
                            <input type="hidden" name="ifthor_saldo_sekarang" :value="ifthorSaldoSekarang">
                        </div>
                    </div>

                    <!-- 3. Santunan Anak Yatim & Dhuafa -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">3. Santunan Anak Yatim & Dhuafa</div>
                        <div class="collapse-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Target Jumlah Anak</label>
                                    <input type="number" name="santunan_yatim_target_anak" class="input-plain" x-model.number="santunan.target_anak" min="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Target Nominal</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" class="input-plain pl-10" placeholder="0"
                                               x-model="santunan.target_nominal_formatted"
                                               @input.debounce.150ms="santunan.target_nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(santunan.target_nominal)"
                                               @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="santunan_yatim_target_nominal" :value="santunan.target_nominal">
                                    <input type="hidden" name="santunan_yatim_total_terkumpul" :value="santunan.terkumpul_kemarin + total(santunan.penerimaan)">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Terkumpul Sampai Kemarin</label>
                                <div class="relative-input">
                                    <span class="rp-prefix">Rp</span>
                                    <input type="text" class="input-plain pl-10" placeholder="0"
                                           x-model="santunan.terkumpul_kemarin_formatted"
                                           @input.debounce.150ms="santunan.terkumpul_kemarin = unformatRupiah($event.target.value); $event.target.value = formatRupiah(santunan.terkumpul_kemarin)"
                                           @focus="$event.target.select()">
                                </div>
                                <input type="hidden" name="santunan_yatim_terkumpul_kemarin" :value="santunan.terkumpul_kemarin">
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium mb-2">Penerimaan Santunan Hari Ini</label>
                                <template x-for="(item, index) in santunan.penerimaan" :key="index">
                                    <div class="repeater-row">
                                        <input type="text" x-model="item.dari" placeholder="Dari (nama/jamaah)" class="input-plain">
                                        <div class="relative-input">
                                            <span class="rp-prefix">Rp</span>
                                            <input type="text" class="input-plain pl-10" placeholder="0"
                                                   x-model="item.nominal_formatted"
                                                   @input.debounce.150ms="item.nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.nominal)"
                                                   @focus="$event.target.select()">
                                        </div>
                                        <button type="button" @click="santunan.penerimaan.splice(index, 1)" class="btn btn-error btn-sm">Hapus</button>
                                    </div>
                                </template>
                                <button type="button" @click="santunan.penerimaan.push({ dari: '', nominal: 0, nominal_formatted: '0' })" class="btn btn-primary btn-sm mt-2">+ Tambah Penerimaan</button>
                                <input type="hidden" name="santunan_yatim_penerimaan_hari_ini" :value="JSON.stringify(santunan.penerimaan.map(i => ({ dari: i.dari, nominal: i.nominal })))">
                            </div>
                            <div class="section-total">
                                Total Terkumpul: Rp <span x-text="formatCurrency(santunan.terkumpul_kemarin + total(santunan.penerimaan))"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Infaq Paket Sembako -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">4. Infaq Paket Sembako</div>
                        <div class="collapse-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Target Jumlah Paket</label>
                                    <input type="number" name="paket_sembako_target" class="input-plain" x-model.number="sembako.target" min="0">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Target Nominal</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" class="input-plain pl-10" placeholder="0"
                                               x-model="sembako.target_nominal_formatted"
                                               @input.debounce.150ms="sembako.target_nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(sembako.target_nominal)"
                                               @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="paket_sembako_target_nominal" :value="sembako.target_nominal">
                                    <input type="hidden" name="paket_sembako_total_terkumpul" :value="sembako.terkumpul_kemarin + total(sembako.penerimaan)">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1">Terkumpul Sampai Kemarin</label>
                                <div class="relative-input">
                                    <span class="rp-prefix">Rp</span>
                                    <input type="text" class="input-plain pl-10" placeholder="0"
                                           x-model="sembako.terkumpul_kemarin_formatted"
                                           @input.debounce.150ms="sembako.terkumpul_kemarin = unformatRupiah($event.target.value); $event.target.value = formatRupiah(sembako.terkumpul_kemarin)"
                                           @focus="$event.target.select()">
                                </div>
                                <input type="hidden" name="paket_sembako_terkumpul_kemarin" :value="sembako.terkumpul_kemarin">
                            </div>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium mb-2">Penerimaan Sembako Hari Ini</label>
                                <template x-for="(item, index) in sembako.penerimaan" :key="index">
                                    <div class="repeater-row">
                                        <input type="text" x-model="item.dari" placeholder="Dari (nama/jamaah)" class="input-plain">
                                        <div class="relative-input">
                                            <span class="rp-prefix">Rp</span>
                                            <input type="text" class="input-plain pl-10" placeholder="0"
                                                   x-model="item.nominal_formatted"
                                                   @input.debounce.150ms="item.nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.nominal)"
                                                   @focus="$event.target.select()">
                                        </div>
                                        <button type="button" @click="sembako.penerimaan.splice(index, 1)" class="btn btn-error btn-sm">Hapus</button>
                                    </div>
                                </template>
                                <button type="button" @click="sembako.penerimaan.push({ dari: '', nominal: 0, nominal_formatted: '0' })" class="btn btn-primary btn-sm mt-2">+ Tambah Penerimaan</button>
                                <input type="hidden" name="paket_sembako_penerimaan_hari_ini" :value="JSON.stringify(sembako.penerimaan.map(i => ({ dari: i.dari, nominal: i.nominal })))">
                            </div>
                            <div class="section-total">
                                Total Terkumpul: Rp <span x-text="formatCurrency(sembako.terkumpul_kemarin + total(sembako.penerimaan))"></span>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Zakat, Infaq, Shodaqoh, Fidyah -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">5. Zakat, Infaq, Shodaqoh, Fidyah</div>
                        <div class="collapse-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Zakat Fitrah per Jiwa</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" class="input-plain pl-10" placeholder="0"
                                               x-model="ziswaf.zakat_fitrah_formatted"
                                               @input.debounce.150ms="ziswaf.zakat_fitrah = unformatRupiah($event.target.value); $event.target.value = formatRupiah(ziswaf.zakat_fitrah)"
                                               @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="zakat_fitrah_per_jiwa" :value="ziswaf.zakat_fitrah">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Fidyah per Hari per Jiwa</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" class="input-plain pl-10" placeholder="0"
                                               x-model="ziswaf.fidyah_formatted"
                                               @input.debounce.150ms="ziswaf.fidyah = unformatRupiah($event.target.value); $event.target.value = formatRupiah(ziswaf.fidyah)"
                                               @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="fidyah_per_hari_per_jiwa" :value="ziswaf.fidyah">
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 italic mt-2">Nilai default sesuai Baznas Kota Tangerang tahun 1447H / 2026.</p>
                        </div>
                    </div>

                    <!-- 6. Lomba Anak Sholeh / Sholehah -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">6. Lomba Anak Sholeh / Sholehah</div>
                        <div class="collapse-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Tanggal Pelaksanaan Lomba</label>
                                    <input type="date" name="lomba_anak_tanggal" class="input-plain" x-model="lomba.tanggal">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium mb-1">Infaq Terkumpul untuk Lomba</label>
                                    <div class="relative-input">
                                        <span class="rp-prefix">Rp</span>
                                        <input type="text" class="input-plain pl-10" placeholder="0"
                                               x-model="lomba.infaq_formatted"
                                               @input.debounce.150ms="lomba.infaq = unformatRupiah($event.target.value); $event.target.value = formatRupiah(lomba.infaq)"
                                               @focus="$event.target.select()">
                                    </div>
                                    <input type="hidden" name="lomba_anak_infaq_terkumpul" :value="lomba.infaq">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Gebyar Ramadhan -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">7. Gebyar Ramadhan</div>
                        <div class="collapse-content space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium mb-1">Tanggal Pelaksanaan Gebyar</label>
                                    <input type="date" name="gebyar_anak_tanggal" class="input-plain" x-model="gebyar.tanggal">
                                </div>
                            </div>

                            <!-- Terkumpul Sampai Kemarin -->
                            <div>
                                <label class="block text-sm font-medium mb-1">Terkumpul Sampai Kemarin</label>
                                <div class="relative-input">
                                    <span class="rp-prefix">Rp</span>
                                    <input type="text" class="input-plain pl-10" placeholder="0"
                                           x-model="gebyar.terkumpul_kemarin_formatted"
                                           @input.debounce.150ms="gebyar.terkumpul_kemarin = unformatRupiah($event.target.value); $event.target.value = formatRupiah(gebyar.terkumpul_kemarin)"
                                           @focus="$event.target.select()">
                                </div>
                                <input type="hidden" name="gebyar_anak_terkumpul_kemarin" :value="gebyar.terkumpul_kemarin">
                                <input type="hidden" name="gebyar_anak_total_terkumpul":value="gebyar.terkumpul_kemarin + total(gebyar.penerimaan)">
                            </div>

                            <!-- Repeater Penerimaan Hari Ini -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <label class="block text-sm font-medium mb-2">Penerimaan untuk Gebyar Hari Ini</label>
                                <template x-for="(item, index) in gebyar.penerimaan" :key="index">
                                    <div class="repeater-row">
                                        <input type="text" x-model="item.dari" placeholder="Dari (nama/jamaah/donatur)" class="input-plain">
                                        <div class="relative-input">
                                            <span class="rp-prefix">Rp</span>
                                            <input type="text" class="input-plain pl-10" placeholder="0"
                                                   x-model="item.nominal_formatted"
                                                   @input.debounce.150ms="item.nominal = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.nominal)"
                                                   @focus="$event.target.select()">
                                        </div>
                                        <button type="button" @click="gebyar.penerimaan.splice(index, 1)" class="btn btn-error btn-sm">Hapus</button>
                                    </div>
                                </template>
                                <button type="button" @click="gebyar.penerimaan.push({ dari: '', nominal: 0, nominal_formatted: '0' })" class="btn btn-primary btn-sm mt-2">+ Tambah Penerimaan</button>
                                <input type="hidden" name="gebyar_anak_penerimaan_hari_ini" :value="JSON.stringify(gebyar.penerimaan.map(i => ({ dari: i.dari, nominal: i.nominal })))">
                            </div>

                            <!-- Total Otomatis -->
                            <div class="section-total">
                                Total Terkumpul: Rp <span x-text="formatCurrency(gebyar.terkumpul_kemarin + total(gebyar.penerimaan))"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Sumbangan Barang -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">Sumbangan Barang (Titipan Jamaah)</div>
                        <div class="collapse-content space-y-4">
                            <label class="block text-sm font-medium mb-2">Daftar Barang yang Diterima Hari Ini</label>
                            <template x-for="(item, index) in sumbangan_barang" :key="index">
                                <div class="repeater-row grid-cols-1 md:grid-cols-3 gap-2">
                                    <input type="text" x-model="item.barang" placeholder="Nama Barang (contoh: air mineral kemasan botol)" class="input-plain">
                                    <div class="relative-input">
                                        <span class="rp-prefix">Jml</span>
                                        <input type="text" x-model="item.jumlah_formatted" placeholder="0"
                                               class="input-plain pl-10"
                                               @input.debounce.150ms="item.jumlah = unformatRupiah($event.target.value); $event.target.value = formatRupiah(item.jumlah)"
                                               @focus="$event.target.select()">
                                    </div>
                                    <input type="text" x-model="item.dari" placeholder="Dari (nama/jamaah/lokasi)" class="input-plain">
                                    <button type="button" @click="sumbangan_barang.splice(index, 1)" class="btn btn-error btn-sm self-center">Hapus</button>
                                </div>
                            </template>
                            <button type="button" @click="sumbangan_barang.push({ barang: '', jumlah: 0, jumlah_formatted: '0', dari: '' })" class="btn btn-primary btn-sm mt-2">+ Tambah Barang</button>
                            <input type="hidden" name="sumbangan_barang" :value="JSON.stringify(sumbangan_barang.map(i => ({ barang: i.barang, jumlah: i.jumlah, dari: i.dari })))">
                        </div>
                    </div>

                    <!-- Pengingat Adab Sholat -->
                    <div class="collapse collapse-arrow join-item border border-base-300 rounded-lg">
                        <input type="radio" name="accordion" />
                        <div class="collapse-title text-lg font-semibold">Pengingat Adab Sholat Tarawih</div>
                        <div class="collapse-content">
                            <textarea name="pengingat_adab" rows="5" class="input-plain w-full" placeholder="Contoh: Mohon HP di-silent atau nonaktifkan selama pelaksanaan sholat. Ibu-ibu lantai bawah rapatkan shaf. Bapak/Ibu yang bawa anak harap dijaga agar tidak mengganggu jamaah..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Tombol Submit FIXED -->
                <div class="flex justify-end gap-4 mt-10 pt-5 border-t sticky bottom-0 bg-white z-20 shadow-inner -mx-6 px-6 py-4">
                    <button type="button" id="cancelLaporanBtn" class="px-6 py-3 bg-gray-200 hover:bg-gray-300 rounded-lg font-medium transition">Batal</button>
                    <button type="submit" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold shadow-lg transition" :disabled="!malam_ke || malam_ke < 1 || malam_ke > 30">Simpan Laporan</button>
                </div>
            </div>
        </form>
    </div>
</dialog>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
    let table = null;
    const modal = document.getElementById('laporanModal');
    const form = document.getElementById('laporanForm');
    const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

    window.fillRamadhanForm = function(data){
        const el = modal.querySelector('[x-ref="formRamadhan"]');
        if(!el) return;
        const component = Alpine.$data(el);
        component.loadFromServer(data);
        // Force Alpine refresh
        el._x_effects?.forEach(e => e());
    };

    function showDialog(el) {
        if (typeof el.showModal === 'function') el.showModal();
        else el.classList.add('modal-open');
    }

    function closeDialog(el) {
        if (typeof el.close === 'function') el.close();
        else el.classList.remove('modal-open');
    }

    function formatCurrency(value) {
        const fixed = Math.round(Number(value || 0));
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(fixed);
    }

    function formatRupiah(angka) {
        if (!angka && angka !== 0) return '0';
        return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    function unformatRupiah(rupiah) {
        return parseInt((rupiah || '').toString().replace(/\D/g, '')) || 0;
    }

function total(items) {
    return (items || []).reduce((sum, item) => sum + Math.round(Number(item.nominal || 0)), 0);
}

    function formData() {
        return {
            malam_ke: '',
            tanggal: '',
            jadwal_imam_id: '',
            infaq_ramadan: {
                saldo_kemarin: 0,
                saldo_kemarin_formatted: '0',
                penerimaan_tromol: 0,
                penerimaan_tromol_formatted: '0',
                pengeluaran: []   // untuk repeater pengeluaran
            },
            ifthor: {
                saldo_kemarin: 0,
                saldo_kemarin_formatted: '0',
                penerimaan: [],
                pengeluaran: []
            },
            santunan: {
                target_anak: 200,
                target_nominal: 70000000,
                target_nominal_formatted: '70.000.000',
                terkumpul_kemarin: 0,
                terkumpul_kemarin_formatted: '0',
                penerimaan: []
            },
            sembako: {
                target: 75,
                target_nominal: 15575000,
                target_nominal_formatted: '15.575.000',
                terkumpul_kemarin: 0,
                terkumpul_kemarin_formatted: '0',
                penerimaan: []
            },
            ziswaf: {
                zakat_fitrah: 47000,
                zakat_fitrah_formatted: '47.000',
                fidyah: 50000,
                fidyah_formatted: '50.000'
            },
            lomba: {
                tanggal: '',
                infaq: 0,
                infaq_formatted: '0'
            },
            gebyar: {
                tanggal: '',
                terkumpul_kemarin: 0,
                terkumpul_kemarin_formatted: '0',
                penerimaan: []   // ini yang hilang → harus array kosong dari awal
            },
            sumbangan_barang: [],
            pengingat_adab: '',

            formatCurrency,
            formatRupiah,
            unformatRupiah,
            total,

            loadFromServer(data) {
                // Info dasar
                this.malam_ke = data.malam_ke || '';

                // Tanggal: format YYYY-MM-DD
                this.tanggal = this.formatDateForInput(data.tanggal);

                // Jadwal Imam: null jadi ''
                this.jadwal_imam_id = (data.jadwal_imam_id ?? '').toString();

                // Infaq Ramadhan (dengan repeater)
                this.infaq_ramadan.saldo_kemarin = Number(data.infaq_ramadan_saldo_kemarin ?? 0);
                this.infaq_ramadan.saldo_kemarin_formatted = this.formatRupiah(this.infaq_ramadan.saldo_kemarin);
                this.infaq_ramadan.penerimaan_tromol = Number(data.infaq_ramadan_penerimaan_tromol ?? 0);
                this.infaq_ramadan.penerimaan_tromol_formatted = this.formatRupiah(this.infaq_ramadan.penerimaan_tromol);
                this.infaq_ramadan.pengeluaran = (data.infaq_ramadan_pengeluaran_detail || []).map(i => ({
                    untuk: i.untuk || '',
                    nominal: Number(i.nominal || 0),
                    nominal_formatted: this.formatRupiah(Number(i.nominal || 0))
                }));

                // Iftor
                this.ifthor.saldo_kemarin = Number(data.ifthor_saldo_kemarin ?? 0);
                this.ifthor.saldo_kemarin_formatted = this.formatRupiah(this.ifthor.saldo_kemarin);
                this.ifthor.penerimaan = (data.ifthor_penerimaan_detail || []).map(i => ({
                    dari: i.dari,
                    nominal: Number(i.nominal),
                    nominal_formatted: this.formatRupiah(Number(i.nominal))
                }));
                this.ifthor.pengeluaran = (data.ifthor_pengeluaran_detail || []).map(i => ({
                    untuk: i.untuk,
                    nominal: Number(i.nominal),
                    nominal_formatted: this.formatRupiah(Number(i.nominal))
                }));

                // Santunan
                this.santunan.target_anak = Number(data.santunan_yatim_target_anak ?? 200);
                this.santunan.target_nominal = Number(data.santunan_yatim_target_nominal ?? 70000000);
                this.santunan.target_nominal_formatted = this.formatRupiah(this.santunan.target_nominal);
                this.santunan.terkumpul_kemarin = Number(data.santunan_yatim_terkumpul_kemarin ?? 0);
                this.santunan.terkumpul_kemarin_formatted = this.formatRupiah(this.santunan.terkumpul_kemarin);
                this.santunan.penerimaan = (data.santunan_yatim_penerimaan_hari_ini || []).map(i => ({
                    dari: i.dari,
                    nominal: Number(i.nominal),
                    nominal_formatted: this.formatRupiah(Number(i.nominal))
                }));

                // Sembako
                this.sembako.target = Number(data.paket_sembako_target ?? 75);
                this.sembako.target_nominal = Number(data.paket_sembako_target_nominal ?? 15575000);
                this.sembako.target_nominal_formatted = this.formatRupiah(this.sembako.target_nominal);
                this.sembako.terkumpul_kemarin = Number(data.paket_sembako_terkumpul_kemarin ?? 0);
                this.sembako.terkumpul_kemarin_formatted = this.formatRupiah(this.sembako.terkumpul_kemarin);
                this.sembako.penerimaan = (data.paket_sembako_penerimaan_hari_ini || []).map(i => ({
                    dari: i.dari,
                    nominal: Number(i.nominal),
                    nominal_formatted: this.formatRupiah(Number(i.nominal))
                }));

                // ZISWAF
                this.ziswaf.zakat_fitrah = Number(data.zakat_fitrah_per_jiwa ?? 47000);
                this.ziswaf.zakat_fitrah_formatted = this.formatRupiah(this.ziswaf.zakat_fitrah);
                this.ziswaf.fidyah = Number(data.fidyah_per_hari_per_jiwa ?? 50000);
                this.ziswaf.fidyah_formatted = this.formatRupiah(this.ziswaf.fidyah);

                // Lomba
                this.lomba.tanggal  = this.formatDateForInput(data.lomba_anak_tanggal);
                this.lomba.infaq = Number(data.lomba_anak_infaq_terkumpul ?? 0);
                this.lomba.infaq_formatted = this.formatRupiah(this.lomba.infaq);

                // Gebyar
                this.gebyar.tanggal = this.formatDateForInput(data.gebyar_anak_tanggal);
                this.gebyar.terkumpul_kemarin = Number(data.gebyar_anak_terkumpul_kemarin ?? 0);
                this.gebyar.terkumpul_kemarin_formatted = this.formatRupiah(this.gebyar.terkumpul_kemarin);
                this.gebyar.penerimaan = (data.gebyar_anak_penerimaan_hari_ini || []).map(i => ({
                    dari: i.dari || '',
                    nominal: Number(i.nominal || 0),
                    nominal_formatted: this.formatRupiah(Number(i.nominal || 0))
                }));
                // Sumbangan Barang
                this.sumbangan_barang = (data.sumbangan_barang || []).map(i => ({
                    barang: i.barang || '',
                    jumlah: Number(i.jumlah || 0),
                    jumlah_formatted: this.formatRupiah(Number(i.jumlah || 0)),
                    dari: i.dari || ''
                }));

                this.pengingat_adab = data.pengingat_adab ?? '';

                this.$nextTick(() => {
                        // Tanggal input
                        if (this.$refs.tanggalInput) {
                            this.$refs.tanggalInput.value = this.tanggal;
                            this.$refs.tanggalInput.dispatchEvent(new Event('input', { bubbles: true }));
                            this.$refs.tanggalInput.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Tanggal dipaksa ke:', this.$refs.tanggalInput.value);
                        }

                        // Select imam
                        if (this.$refs.imamSelect) {
                            this.$refs.imamSelect.value = this.jadwal_imam_id;
                            this.$refs.imamSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            console.log('Select imam dipaksa ke value:', this.$refs.imamSelect.value);
                        }
                    });
            },
            formatDateForInput(datetime) {
                if (!datetime) return '';

                const d = new Date(datetime);

                // pakai LOCAL time, bukan UTC
                const year  = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2,'0');
                const day   = String(d.getDate()).padStart(2,'0');

                return `${year}-${month}-${day}`;
            },

            get infaqSaldoSekarang() {
                return Math.round(
                    Number(this.infaq_ramadan.saldo_kemarin || 0) +
                    Number(this.infaq_ramadan.penerimaan_tromol || 0) -
                    this.total(this.infaq_ramadan.pengeluaran || [])
                );
            },

            get ifthorSaldoSekarang() {
                return Math.round(
                    Number(this.ifthor.saldo_kemarin || 0) +
                    this.total(this.ifthor.penerimaan || []) -
                    this.total(this.ifthor.pengeluaran || [])
                );
            },

            init() {
               
            },

        };
    }

    $(function () {
        table = $('#laporanTable').DataTable({
            processing: true,
            ajax: {
                url: '{{ route('admin.ramadhan.laporan-harian.data') }}',
                dataSrc: function (json) {

                    // ambil totals untuk kartu atas
                    if (json.data && json.data.totals) {
                        updateTotals(json.data.totals);
                    }

                    // KUNCI MASALAHNYA DI SINI
                    return json.data.data;
                }
            },
            columns: [
                { data: 'malam_ke' },
                { data: 'tanggal' },
                { data: 'imam' },
                { data: 'saldo_infaq_ramadan' },
                { data: 'saldo_ifthor' },
                { data: 'santunan_yatim' },
                {
                    data: null,
                    orderable: false,
                    render: function (data, type, row) {
                        return `
                            <div class="inline-flex gap-2 justify-center">
                                <button class="btn-circle-ico bg-yellow-50 hover:bg-yellow-100 text-yellow-700" onclick="editLaporan(${row.id})">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button class="btn-circle-ico bg-red-50 hover:bg-red-100 text-red-700" onclick="deleteLaporan(${row.id}, ${row.malam_ke})">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
        });

        function updateTotals(totals) {
            if (!totals) return;
            document.getElementById('total-infaq').textContent = formatCurrency(totals.infaq_ramadan);
            document.getElementById('total-ifthor').textContent = formatCurrency(totals.ifthor);
            document.getElementById('total-santunan').textContent = formatCurrency(totals.santunan_yatim);
            document.getElementById('total-sembako').textContent = formatCurrency(totals.paket_sembako);
            document.getElementById('total-gebyar').textContent = formatCurrency(totals.gebyar);
        }

        document.getElementById('closeLaporanModal')?.addEventListener('click', () => closeDialog(modal));
        document.getElementById('cancelLaporanBtn')?.addEventListener('click', () => closeDialog(modal));
        modal?.addEventListener('cancel', e => { e.preventDefault(); closeDialog(modal); });

        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                submitBtn.disabled = true;
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<span class="loading loading-spinner loading-sm mr-2"></span>Menyimpan...';

                const formDataObj = new FormData(this);
                const method = document.getElementById('method').value;
                if (method === 'PUT') formDataObj.append('_method', 'PUT');

                const repeaterFields = [
                    'infaq_ramadan_pengeluaran_detail',
                    'ifthor_penerimaan_detail',
                    'ifthor_pengeluaran_detail',
                    'santunan_yatim_penerimaan_hari_ini',
                    'paket_sembako_penerimaan_hari_ini',
                    'sumbangan_barang',
                    'gebyar_anak_penerimaan_hari_ini'
                ];
                repeaterFields.forEach(field => {
                    if (!formDataObj.has(field)) formDataObj.append(field, '[]');
                });

                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        body: formDataObj,
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    if (data.success) {
                        closeDialog(modal);
                        table.ajax.reload();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: data.message,
                            timer: 1800,
                            showConfirmButton: false
                        });
                    } else {
                        Swal.fire('Gagal', data.message, 'error');
                    }
                } catch(err) {
                    Swal.fire('Error', err.message, 'error');
                } finally {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
        }
    });

    async function loadPrevData(malamKe) {
        if (malamKe <= 1) return; // malam pertama tidak perlu load kemarin

        try {
            const response = await fetch(`/admin/ramadhan/laporan-harian/prev/${malamKe}`);
            const result = await response.json();

            if (result.success && result.data) {
                const d = result.data;

                // Infaq Ramadhan
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).infaq_ramadan.saldo_kemarin = d.infaq_ramadan_saldo_sekarang;
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).infaq_ramadan.saldo_kemarin_formatted = formatRupiah(d.infaq_ramadan_saldo_sekarang);

                // Iftor
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).ifthor.saldo_kemarin = d.ifthor_saldo_sekarang;
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).ifthor.saldo_kemarin_formatted = formatRupiah(d.ifthor_saldo_sekarang);

                // Santunan
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).santunan.terkumpul_kemarin = d.santunan_yatim_terkumpul_kemarin;
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).santunan.terkumpul_kemarin_formatted = formatRupiah(d.santunan_yatim_terkumpul_kemarin);

                // Sembako
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).sembako.terkumpul_kemarin = d.paket_sembako_terkumpul_kemarin;
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).sembako.terkumpul_kemarin_formatted = formatRupiah(d.paket_sembako_terkumpul_kemarin);

                // Gebyar
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).gebyar.terkumpul_kemarin = d.gebyar_anak_terkumpul_kemarin;
                Alpine.$data(document.querySelector('[x-ref="formRamadhan"]')).gebyar.terkumpul_kemarin_formatted = formatRupiah(d.gebyar_anak_terkumpul_kemarin);

                // Optional: trigger update UI
                // document.querySelectorAll('input').forEach(el => el.dispatchEvent(new Event('input')));
            }
        } catch (err) {
            console.error('Gagal load data kemarin:', err);
        }
    }

    window.addLaporan = async function () {
        form.reset();
        document.getElementById('method').value = 'POST';
        form.action = '{{ route("admin.ramadhan.laporan-harian.store") }}';
        showDialog(modal);

        // Tunggu user isi malam_ke, atau panggil setelah modal open
        // Cara sederhana: tambah listener satu kali
        const malamInput = document.querySelector('input[name="malam_ke"]');
        if (malamInput) {
            malamInput.addEventListener('change', function handler(e) {
                const val = parseInt(e.target.value);
                if (!isNaN(val) && val > 1) {
                    loadPrevData(val);
                }
                // Hapus listener setelah sekali pakai (opsional)
                // malamInput.removeEventListener('change', handler);
            }, { once: true });
        }
    };

    window.editLaporan = function(id){
        fetch(`/admin/ramadhan/laporan-harian/${id}/edit`, {
            headers:{
                'Accept':'application/json',
                'X-Requested-With':'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(data => {
            document.getElementById('method').value = 'PUT';
            form.action = `/admin/ramadhan/laporan-harian/${id}`;
            showDialog(modal);
            setTimeout(() => {
                window.fillRamadhanForm(data);
            }, 250);
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Error', 'Gagal memuat data', 'error');
        });
    };

    window.deleteLaporan = function (id, malamKe) {
        if (!confirm(`Hapus laporan malam ke-${malamKe}?`)) return;
        fetch(`/admin/ramadhan/laporan-harian/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                table.ajax.reload();
                alert('Terhapus');
            }
        });
    };
</script>
@endpush
@endsection