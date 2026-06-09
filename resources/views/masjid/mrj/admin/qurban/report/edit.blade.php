{{-- resources/views/masjid/mrj/admin/qurban/report/edit.blade.php --}}
@extends('masjid.master')

@section('title', 'Edit Laporan ' . $report->tahun_hijriah)

@push('style')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/dropzone@5.9.3/dist/min/dropzone.min.css" rel="stylesheet" />
<style>
    .section-card {
        background: white;
        border-radius: 1rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .section-header {
        background: #f9fafb;
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .section-body {
        padding: 1.5rem;
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
    }
    .form-control {
        width: 100%;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.5rem 0.75rem;
        transition: all 0.2s;
    }
    .form-control:focus {
        outline: none;
        border-color: #10b981;
        ring: 2px solid rgba(16,185,129,0.1);
    }
    .btn-json-add {
        background: #059669;
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        margin-top: 0.5rem;
    }
    .json-item {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
        align-items: center;
    }
    .json-item input {
        flex: 1;
    }
    .image-preview {
        width: 100px;
        height: 100px;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
    }
    .gallery-item-preview {
        position: relative;
        display: inline-block;
        margin: 0.5rem;
    }
    .gallery-item-preview img {
        width: 120px;
        height: 120px;
        object-fit: cover;
        border-radius: 0.5rem;
        border: 2px solid #e5e7eb;
    }
    .gallery-item-preview .btn-remove {
        position: absolute;
        top: -8px;
        right: -8px;
        background: red;
        color: white;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 12px;
    }
    .dropzone {
        border: 2px dashed #d1d5db;
        border-radius: 0.5rem;
        background: #f9fafb;
        min-height: 150px;
    }
    .dropzone .dz-message {
        margin: 2em 0;
    }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.qurban.report.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">✏️ Edit Laporan {{ $report->tahun_hijriah }}</h1>
        <a href="{{ route('qurban.laporan', $report->tahun_hijriah) }}" target="_blank" class="ml-auto text-sm text-blue-600 hover:underline">Lihat Publikasi →</a>
    </div>
    
    <form action="{{ route('admin.qurban.report.update', $report->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        {{-- 1. Informasi Dasar --}}
        <div class="section-card">
            <div class="section-header">📋 Informasi Dasar</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Tahun Hijriah</label>
                        <input type="text" name="tahun_hijriah" value="{{ old('tahun_hijriah', $report->tahun_hijriah) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tahun Masehi</label>
                        <input type="text" name="tahun_masehi" value="{{ old('tahun_masehi', $report->tahun_masehi) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Aktif</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ $report->is_active ? 'selected' : '' }}>✅ Aktif (Tampil di Website)</option>
                            <option value="0" {{ !$report->is_active ? 'selected' : '' }}>❌ Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status Publikasi</label>
                        <select name="is_published" class="form-control">
                            <option value="1" {{ $report->is_published ? 'selected' : '' }}>📢 Terbit</option>
                            <option value="0" {{ !$report->is_published ? 'selected' : '' }}>📝 Draft</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 2. Data Hero --}}
        <div class="section-card">
            <div class="section-header">🎯 Hero Section</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Hero Title</label>
                        <input type="text" name="hero_title" value="{{ old('hero_title', $report->hero_title) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hero Subtitle</label>
                        <input type="text" name="hero_subtitle" value="{{ old('hero_subtitle', $report->hero_subtitle) }}" class="form-control" placeholder="Kosongkan untuk otomatis pakai tahun">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Hero Badge (gunakan {TAHUN} untuk dinamis)</label>
                        <input type="text" name="hero_badge" value="{{ old('hero_badge', $report->hero_badge) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Masjid</label>
                        <input type="text" name="hero_masjid" value="{{ old('hero_masjid', $report->hero_masjid) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Tagline / Quote</label>
                        <textarea name="hero_tagline" rows="2" class="form-control">{{ old('hero_tagline', $report->hero_tagline) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 3. Statistik Utama --}}
        <div class="section-card">
            <div class="section-header">📊 Statistik Utama</div>
            <div class="section-body">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="form-group">
                        <label class="form-label">Jumlah Sapi</label>
                        <input type="number" name="stat_hewan_sapi" value="{{ old('stat_hewan_sapi', $report->stat_hewan_sapi) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jumlah Kambing</label>
                        <input type="number" name="stat_hewan_kambing" value="{{ old('stat_hewan_kambing', $report->stat_hewan_kambing) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paket Daging</label>
                        <input type="number" name="stat_paket_daging" value="{{ old('stat_paket_daging', $report->stat_paket_daging) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Mustahik (Keluarga)</label>
                        <input type="number" name="stat_mustahik" value="{{ old('stat_mustahik', $report->stat_mustahik) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Daging (kg)</label>
                        <input type="number" name="stat_daging_kg" value="{{ old('stat_daging_kg', $report->stat_daging_kg) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Stat Jamaah</label>
                        <input type="text" name="stat_jamaah" value="{{ old('stat_jamaah', $report->stat_jamaah) }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 4. Pelaksanaan dengan Upload Gambar --}}
        <div class="section-card">
            <div class="section-header">🕌 Data Pelaksanaan</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Nama Ketua Panitia</label>
                        <input type="text" name="pelaksanaan_ketua_nama" value="{{ old('pelaksanaan_ketua_nama', $report->pelaksanaan_ketua_nama) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Jabatan Ketua</label>
                        <input type="text" name="pelaksanaan_ketua_jabatan" value="{{ old('pelaksanaan_ketua_jabatan', $report->pelaksanaan_ketua_jabatan) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nama Masjid</label>
                        <input type="text" name="pelaksanaan_masjid_nama" value="{{ old('pelaksanaan_masjid_nama', $report->pelaksanaan_masjid_nama) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Subtitle Masjid</label>
                        <input type="text" name="pelaksanaan_masjid_sub" value="{{ old('pelaksanaan_masjid_sub', $report->pelaksanaan_masjid_sub) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi Sholat</label>
                        <input type="text" name="pelaksanaan_lokasi_sholat" value="{{ old('pelaksanaan_lokasi_sholat', $report->pelaksanaan_lokasi_sholat) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Lokasi Qurban</label>
                        <input type="text" name="pelaksanaan_lokasi_qurban" value="{{ old('pelaksanaan_lokasi_qurban', $report->pelaksanaan_lokasi_qurban) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Deskripsi Pelaksanaan</label>
                        <textarea name="pelaksanaan_deskripsi" rows="3" class="form-control">{{ old('pelaksanaan_deskripsi', $report->pelaksanaan_deskripsi) }}</textarea>
                    </div>
                </div>
                
                {{-- Upload Gambar Pelaksanaan --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                    <div class="form-group">
                        <label class="form-label">Gambar Sholat Ied</label>
                        @if($report->pelaksanaan_gambar1)
                            <div class="mb-2">
                                <img src="{{ asset($report->pelaksanaan_gambar1) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('pelaksanaan_gambar1')">Hapus</button>
                                <input type="hidden" name="remove_pelaksanaan_gambar1" id="remove_pelaksanaan_gambar1" value="0">
                            </div>
                        @endif
                        <input type="file" name="pelaksanaan_gambar1" class="form-control" accept="image/*">
                        <small class="text-gray-400">Kosongkan jika tidak ingin mengubah</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Khatib</label>
                        @if($report->pelaksanaan_gambar2)
                            <div class="mb-2">
                                <img src="{{ asset($report->pelaksanaan_gambar2) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('pelaksanaan_gambar2')">Hapus</button>
                                <input type="hidden" name="remove_pelaksanaan_gambar2" id="remove_pelaksanaan_gambar2" value="0">
                            </div>
                        @endif
                        <input type="file" name="pelaksanaan_gambar2" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Hewan Qurban</label>
                        @if($report->pelaksanaan_gambar3)
                            <div class="mb-2">
                                <img src="{{ asset($report->pelaksanaan_gambar3) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('pelaksanaan_gambar3')">Hapus</button>
                                <input type="hidden" name="remove_pelaksanaan_gambar3" id="remove_pelaksanaan_gambar3" value="0">
                            </div>
                        @endif
                        <input type="file" name="pelaksanaan_gambar3" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 5. Dramatis Section dengan Upload Gambar --}}
        <div class="section-card">
            <div class="section-header">🎬 Dramatis Section 1 (Sholat Ied)</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Judul</label>
                        <input type="text" name="dramatis1_title" value="{{ old('dramatis1_title', $report->dramatis1_title) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Quote</label>
                        <textarea name="dramatis1_quote" rows="2" class="form-control">{{ old('dramatis1_quote', $report->dramatis1_quote) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statistik</label>
                        <input type="text" name="dramatis1_stat" value="{{ old('dramatis1_stat', $report->dramatis1_stat) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Background</label>
                        @if($report->dramatis1_image)
                            <div class="mb-2">
                                <img src="{{ asset($report->dramatis1_image) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('dramatis1_image')">Hapus</button>
                                <input type="hidden" name="remove_dramatis1_image" id="remove_dramatis1_image" value="0">
                            </div>
                        @endif
                        <input type="file" name="dramatis1_image" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-card">
            <div class="section-header">🎬 Dramatis Section 2 (Penyembelihan)</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Judul</label>
                        <input type="text" name="dramatis2_title" value="{{ old('dramatis2_title', $report->dramatis2_title) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Quote</label>
                        <textarea name="dramatis2_quote" rows="2" class="form-control">{{ old('dramatis2_quote', $report->dramatis2_quote) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statistik</label>
                        <input type="text" name="dramatis2_stat" value="{{ old('dramatis2_stat', $report->dramatis2_stat) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Background</label>
                        @if($report->dramatis2_image)
                            <div class="mb-2">
                                <img src="{{ asset($report->dramatis2_image) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('dramatis2_image')">Hapus</button>
                                <input type="hidden" name="remove_dramatis2_image" id="remove_dramatis2_image" value="0">
                            </div>
                        @endif
                        <input type="file" name="dramatis2_image" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section-card">
            <div class="section-header">🎬 Dramatis Section 3 (Senyuman)</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Judul</label>
                        <input type="text" name="dramatis3_title" value="{{ old('dramatis3_title', $report->dramatis3_title) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Quote</label>
                        <textarea name="dramatis3_quote" rows="2" class="form-control">{{ old('dramatis3_quote', $report->dramatis3_quote) }}</textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Statistik</label>
                        <input type="text" name="dramatis3_stat" value="{{ old('dramatis3_stat', $report->dramatis3_stat) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Gambar Background</label>
                        @if($report->dramatis3_image)
                            <div class="mb-2">
                                <img src="{{ asset($report->dramatis3_image) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('dramatis3_image')">Hapus</button>
                                <input type="hidden" name="remove_dramatis3_image" id="remove_dramatis3_image" value="0">
                            </div>
                        @endif
                        <input type="file" name="dramatis3_image" class="form-control" accept="image/*">
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 6. Pemotongan --}}
        <div class="section-card">
            <div class="section-header">⚖️ Data Pemotongan</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">Berat Rata-rata Sapi (kg)</label>
                        <input type="number" name="pemotongan_sapi_berat_kg" value="{{ old('pemotongan_sapi_berat_kg', $report->pemotongan_sapi_berat_kg) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Berat Rata-rata Kambing (kg)</label>
                        <input type="number" name="pemotongan_kambing_berat_kg" value="{{ old('pemotongan_kambing_berat_kg', $report->pemotongan_kambing_berat_kg) }}" class="form-control">
                    </div>
                </div>
            </div>
        </div>
        
{{-- 7. Keuangan (JSON Dinamis) --}}
<div class="section-card">
    <div class="section-header">💰 Data Keuangan</div>
    <div class="section-body">
        <div class="mb-6">
            <label class="form-label font-semibold">Penerimaan</label>
            <div id="penerimaan-container">
                @foreach(($report->keuangan_penerimaan ?? []) as $index => $item)
                <div class="json-item">
                    <input type="text" name="keuangan_penerimaan[{{ $index }}][label]" value="{{ $item['label'] }}" class="form-control" placeholder="Label penerimaan">
                    <input type="text" name="keuangan_penerimaan[{{ $index }}][amount]" value="{{ number_format($item['amount'], 0, ',', '.') }}" class="form-control w-48 currency-input" placeholder="Jumlah (Rp)">
                    <button type="button" class="remove-item bg-red-500 text-white px-3 py-2 rounded-lg">✕</button>
                </div>
                @endforeach
            </div>
            <button type="button" id="add-penerimaan" class="btn-json-add">+ Tambah Item Penerimaan</button>
            
            {{-- Display Total Penerimaan --}}
            <div class="mt-3 p-3 bg-green-50 rounded-lg border border-green-200">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-700">📊 TOTAL PENERIMAAN</span>
                    <span id="total-penerimaan-display" class="font-bold text-xl text-emerald-700">Rp 0</span>
                </div>
            </div>
        </div>
        
        <div class="mb-6">
            <label class="form-label font-semibold">Pengeluaran</label>
            <div id="pengeluaran-container">
                @foreach(($report->keuangan_pengeluaran ?? []) as $index => $item)
                <div class="json-item">
                    <input type="text" name="keuangan_pengeluaran[{{ $index }}][label]" value="{{ $item['label'] }}" class="form-control" placeholder="Label pengeluaran">
                    <input type="text" name="keuangan_pengeluaran[{{ $index }}][amount]" value="{{ number_format($item['amount'], 0, ',', '.') }}" class="form-control w-48 currency-input" placeholder="Jumlah (Rp)">
                    <button type="button" class="remove-item bg-red-500 text-white px-3 py-2 rounded-lg">✕</button>
                </div>
                @endforeach
            </div>
            <button type="button" id="add-pengeluaran" class="btn-json-add">+ Tambah Item Pengeluaran</button>
            
            {{-- Display Total Pengeluaran --}}
            <div class="mt-3 p-3 bg-orange-50 rounded-lg border border-orange-200">
                <div class="flex justify-between items-center">
                    <span class="font-bold text-gray-700">📊 TOTAL PENGELUARAN</span>
                    <span id="total-pengeluaran-display" class="font-bold text-xl text-orange-700">Rp 0</span>
                </div>
            </div>
        </div>
        
        {{-- Sisa Dana --}}
        <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex justify-between items-center">
                <div>
                    <span class="font-bold text-gray-700"><i class="fas fa-coins text-blue-500 mr-2"></i>SISA DANA</span>
                    <p class="text-xs text-gray-500 mt-1">(Total Penerimaan - Total Pengeluaran)</p>
                </div>
                <span id="sisa-dana-display" class="font-bold text-2xl text-emerald-700">Rp 0</span>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Catatan Keuangan</label>
            <textarea name="keuangan_catatan" rows="2" class="form-control">{{ old('keuangan_catatan', $report->keuangan_catatan) }}</textarea>
        </div>
    </div>
</div>
        
        {{-- 8. Rings & Distribusi --}}
        <div class="section-card">
            <div class="section-header">
                👥 Penerima Manfaat (Rings)
                <span class="text-sm text-gray-500">Total keseluruhan akan dihitung otomatis</span>
            </div>
            <div class="section-body">
                <div id="rings-container">
                    @foreach(($report->rings ?? []) as $ringIndex => $ring)
                    <div class="ring-group border rounded-lg p-4 mb-4 bg-gray-50">
                        <div class="flex justify-between items-center mb-3">
                            <h4 class="font-bold">Ring {{ $ringIndex + 1 }}</h4>
                            <button type="button" class="remove-ring bg-red-500 text-white px-3 py-1 rounded text-sm">Hapus Ring</button>
                        </div>
                        <div class="grid grid-cols-2 gap-3 mb-3">
                            <input type="text" name="rings[{{ $ringIndex }}][title]" value="{{ $ring['title'] }}" class="form-control" placeholder="Judul Ring (contoh: RING I — Warga TCE)">
                            <div class="flex gap-2">
                                <select name="rings[{{ $ringIndex }}][icon]" class="form-control">
                                    <option value="fa-building" {{ ($ring['icon'] ?? '') == 'fa-building' ? 'selected' : '' }}>🏢 Building</option>
                                    <option value="fa-globe" {{ ($ring['icon'] ?? '') == 'fa-globe' ? 'selected' : '' }}>🌍 Globe</option>
                                    <option value="fa-hand-holding-heart" {{ ($ring['icon'] ?? '') == 'fa-hand-holding-heart' ? 'selected' : '' }}>🤲 Heart</option>
                                    <option value="fa-users" {{ ($ring['icon'] ?? '') == 'fa-users' ? 'selected' : '' }}>👥 Users</option>
                                </select>
                                <select name="rings[{{ $ringIndex }}][color]" class="form-control">
                                    <option value="emerald" {{ ($ring['color'] ?? '') == 'emerald' ? 'selected' : '' }}>Hijau (Emerald)</option>
                                    <option value="teal" {{ ($ring['color'] ?? '') == 'teal' ? 'selected' : '' }}>Biru Kehijauan (Teal)</option>
                                    <option value="blue" {{ ($ring['color'] ?? '') == 'blue' ? 'selected' : '' }}>Biru (Blue)</option>
                                    <option value="amber" {{ ($ring['color'] ?? '') == 'amber' ? 'selected' : '' }}>Kuning (Amber)</option>
                                    <option value="purple" {{ ($ring['color'] ?? '') == 'purple' ? 'selected' : '' }}>Ungu (Purple)</option>
                                </select>
                            </div>
                        </div>
                        <div class="ring-items-container mb-3">
                            <label class="font-semibold text-sm mb-1 block">Daftar Penerima:</label>
                            @foreach($ring['items'] ?? [] as $itemIndex => $item)
                            <div class="flex gap-2 mb-2 ring-item">
                                <input type="text" name="rings[{{ $ringIndex }}][items][{{ $itemIndex }}][label]" value="{{ $item['label'] }}" class="form-control flex-1" placeholder="Label (contoh: 👥 Warga RT/RW)">
                                <input type="text" name="rings[{{ $ringIndex }}][items][{{ $itemIndex }}][value]" value="{{ $item['value'] }}" class="form-control w-40" placeholder="Jumlah (contoh: 792 penerima)">
                                <button type="button" class="remove-item-ring bg-red-500 text-white px-3 rounded">✕</button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" class="add-item-ring bg-gray-500 text-white px-3 py-1 rounded text-sm">+ Tambah Penerima</button>
                        <div class="mt-3">
                            <label class="font-semibold text-sm mb-1 block">Total Ring Ini (Otomatis)</label>
                            <input type="text" name="rings[{{ $ringIndex }}][total]" value="{{ $ring['total'] ?? '0 penerima' }}" class="form-control bg-gray-100" readonly placeholder="Total akan terisi otomatis">
                        </div>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="add-ring" class="btn-json-add">+ Tambah Ring Baru</button>
                
                {{-- Grand Total Semua Ring --}}
                <div class="mt-4 p-4 bg-emerald-50 rounded-lg border border-emerald-200">
                    <div class="flex justify-between items-center">
                        <span class="font-bold text-gray-800">📊 TOTAL KESELURUHAN PENERIMA</span>
                        <span id="grand-total-display" class="font-bold text-2xl text-emerald-700">0 penerima</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">*Total dihitung otomatis dari semua Ring</p>
                </div>
            </div>
        </div>
        
        {{-- 9. Distribusi --}}
        <div class="section-card">
            <div class="section-header">📊 Distribusi</div>
            <div class="section-body">
                <div id="distribusi-container">
                    @foreach(($report->distribusi ?? []) as $index => $item)
                    <div class="json-item">
                        <input type="text" name="distribusi[{{ $index }}][label]" value="{{ $item['label'] }}" class="form-control" placeholder="Label">
                        <input type="number" name="distribusi[{{ $index }}][value]" value="{{ $item['value'] }}" class="form-control w-32" placeholder="Jumlah">
                        <input type="text" name="distribusi[{{ $index }}][icon]" value="{{ $item['icon'] }}" class="form-control w-24" placeholder="Icon (fa-...)">
                        <input type="number" name="distribusi[{{ $index }}][percentage]" value="{{ $item['percentage'] }}" class="form-control w-24" placeholder="Persen">
                        <button type="button" class="remove-distribusi bg-red-500 text-white px-3 py-2 rounded-lg">✕</button>
                    </div>
                    @endforeach
                </div>
                <button type="button" id="add-distribusi" class="btn-json-add">+ Tambah Item Distribusi</button>
            </div>
        </div>
        
        {{-- 10. Galeri dengan Upload Multiple --}}
        <div class="section-card">
            <div class="section-header">🖼️ Galeri Foto</div>
            <div class="section-body">
                <div class="mb-4">
                    <label class="form-label font-semibold">Foto Utama (10 foto)</label>
                    <div id="gallery-images-container" class="flex flex-wrap">
                        @foreach(($report->gallery_images ?? []) as $index => $item)
                        <div class="gallery-item-preview" data-index="{{ $index }}">
                            <img src="{{ asset($item['url']) }}" alt="{{ $item['alt'] }}">
                            <button type="button" class="btn-remove-gallery" data-index="{{ $index }}" data-type="gallery">✕</button>
                            <input type="hidden" name="gallery_images[{{ $index }}][url]" value="{{ $item['url'] }}">
                            <input type="hidden" name="gallery_images[{{ $index }}][alt]" value="{{ $item['alt'] }}">
                            <input type="hidden" name="gallery_images[{{ $index }}][type]" value="{{ $item['type'] }}">
                            <select name="gallery_images[{{ $index }}][type]" class="form-control text-xs mt-1 w-full">
                                <option value="square" {{ $item['type'] == 'square' ? 'selected' : '' }}>Square</option>
                                <option value="landscape" {{ $item['type'] == 'landscape' ? 'selected' : '' }}>Landscape</option>
                            </select>
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <input type="file" id="upload-gallery" multiple accept="image/*" class="form-control">
                        <small class="text-gray-400">Upload foto baru untuk ditambahkan ke galeri utama</small>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="form-label font-semibold">Foto Tambahan (di modal)</label>
                    <div id="additional-images-container" class="flex flex-wrap">
                        @foreach(($report->additional_images ?? []) as $index => $url)
                        <div class="gallery-item-preview" data-index="{{ $index }}">
                            <img src="{{ asset($url) }}" alt="Foto tambahan">
                            <button type="button" class="btn-remove-additional" data-index="{{ $index }}">✕</button>
                            <input type="hidden" name="additional_images[{{ $index }}]" value="{{ $url }}">
                        </div>
                        @endforeach
                    </div>
                    <div class="mt-2">
                        <input type="file" id="upload-additional" multiple accept="image/*" class="form-control">
                        <small class="text-gray-400">Upload foto tambahan yang hanya muncul di modal</small>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 11. QR & Thank You --}}
        <div class="section-card">
            <div class="section-header">📱 QR Code & Pesan Terima Kasih</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label class="form-label">QR Code Image</label>
                        @if($report->qr_image)
                            <div class="mb-2">
                                <img src="{{ asset($report->qr_image) }}" class="image-preview">
                                <button type="button" class="text-red-500 text-sm mt-1" onclick="removeImage('qr_image')">Hapus</button>
                                <input type="hidden" name="remove_qr_image" id="remove_qr_image" value="0">
                            </div>
                        @endif
                        <input type="file" name="qr_image" class="form-control" accept="image/*">
                    </div>
                    <div class="form-group">
                        <label class="form-label">QR Link (URL tujuan scan)</label>
                        <input type="url" name="qr_link" value="{{ old('qr_link', $report->qr_link) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Judul Thank You</label>
                        <input type="text" name="thankyou_title" value="{{ old('thankyou_title', $report->thankyou_title) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Pesan Thank You</label>
                        <textarea name="thankyou_message" rows="3" class="form-control">{{ old('thankyou_message', $report->thankyou_message) }}</textarea>
                    </div>
                    <div class="form-group md:col-span-2">
                        <label class="form-label">Hadits / Quote</label>
                        <textarea name="thankyou_hadits" rows="2" class="form-control">{{ old('thankyou_hadits', $report->thankyou_hadits) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 12. Footer --}}
        <div class="section-card">
            <div class="section-header">🔗 Footer & Sosial Media</div>
            <div class="section-body">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="form-group">
                        <label class="form-label">Instagram URL</label>
                        <input type="url" name="footer_instagram" value="{{ old('footer_instagram', $report->footer_instagram) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">WhatsApp URL</label>
                        <input type="url" name="footer_whatsapp" value="{{ old('footer_whatsapp', $report->footer_whatsapp) }}" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="footer_email" value="{{ old('footer_email', $report->footer_email) }}" class="form-control">
                    </div>
                    <div class="form-group md:col-span-3">
                        <label class="form-label">Quote Footer</label>
                        <textarea name="footer_quote" rows="2" class="form-control">{{ old('footer_quote', $report->footer_quote) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- 13. Catatan --}}
        <div class="section-card">
            <div class="section-header">📝 Catatan Keterangan</div>
            <div class="section-body">
                <textarea name="catatan_keterangan" rows="3" class="form-control">{{ old('catatan_keterangan', $report->catatan_keterangan) }}</textarea>
            </div>
        </div>
        
        <div class="flex justify-end gap-3 mt-6">
            <a href="{{ route('admin.qurban.report.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold">Simpan Perubahan</button>
        </div>
    </form>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// ============ RINGS DINAMIS DENGAN AUTO CALCULATE ============

// Fungsi untuk ekstrak angka dari string (contoh: "792 penerima" -> 792)
function extractNumber(value) {
    if (!value) return 0;
    let number = parseInt(value.toString().replace(/\D/g, ''));
    return isNaN(number) ? 0 : number;
}

// Fungsi untuk menghitung total satu ring
function calculateRingTotal(ringGroup) {
    let total = 0;
    // Cari semua input value (placeholder mengandung "Jumlah")
    const items = ringGroup.querySelectorAll('.ring-item input[placeholder*="Jumlah"]');
    items.forEach(item => {
        let value = extractNumber(item.value);
        total += value;
    });
    
    // Update input total ring (readonly)
    const totalInput = ringGroup.querySelector('input[name*="[total]"]');
    if (totalInput) {
        let totalText = total.toLocaleString('id-ID') + ' penerima';
        totalInput.value = totalText;
    }
    
    return total;
}

// Fungsi untuk menghitung semua ring dan total keseluruhan
function calculateAllRingsTotal() {
    let grandTotal = 0;
    const ringGroups = document.querySelectorAll('.ring-group');
    ringGroups.forEach(ringGroup => {
        const total = calculateRingTotal(ringGroup);
        grandTotal += total;
    });
    
    // Update grand total display
    const grandTotalElement = document.getElementById('grand-total-display');
    if (grandTotalElement) {
        grandTotalElement.innerText = grandTotal.toLocaleString('id-ID') + ' penerima';
    }
    
    return grandTotal;
}

// Fungsi untuk attach event ke tombol hapus item ring
function attachRemoveItemRingEvent(btn) {
    btn.addEventListener('click', function() {
        this.closest('.ring-item').remove();
        // Hitung ulang total ring dan grand total
        const ringGroup = this.closest('.ring-group');
        calculateRingTotal(ringGroup);
        calculateAllRingsTotal();
    });
}

// Fungsi untuk mendapatkan index ring
function getRingIndex(ringGroup) {
    return Array.from(document.querySelectorAll('.ring-group')).indexOf(ringGroup);
}

// Fungsi untuk reindex semua ring (setelah hapus/tambah ring)
function reindexRings() {
    document.querySelectorAll('.ring-group').forEach((ring, idx) => {
        ring.querySelectorAll('[name^="rings["]').forEach(input => {
            input.name = input.name.replace(/rings\[\d+\]/, `rings[${idx}]`);
        });
    });
}

// Fungsi utama attach events ke ring group
function attachRingEvents(ringGroup) {
    // Event hapus ring
    const removeRingBtn = ringGroup.querySelector('.remove-ring');
    if (removeRingBtn) {
        removeRingBtn.addEventListener('click', function() {
            ringGroup.remove();
            calculateAllRingsTotal();
            reindexRings();
        });
    }
    
    // Event tambah item ring
    const addItemBtn = ringGroup.querySelector('.add-item-ring');
    if (addItemBtn) {
        addItemBtn.addEventListener('click', function() {
            const itemsContainer = ringGroup.querySelector('.ring-items-container');
            const itemIndex = itemsContainer.children.length;
            const ringIndex = getRingIndex(ringGroup);
            const newItem = document.createElement('div');
            newItem.className = 'flex gap-2 mb-2 ring-item';
            newItem.innerHTML = `
                <input type="text" name="rings[${ringIndex}][items][${itemIndex}][label]" class="form-control flex-1" placeholder="Label (contoh: 👥 Warga RT/RW)">
                <input type="text" name="rings[${ringIndex}][items][${itemIndex}][value]" class="form-control w-40" placeholder="Jumlah (contoh: 792 penerima)">
                <button type="button" class="remove-item-ring bg-red-500 text-white px-3 rounded">✕</button>
            `;
            itemsContainer.appendChild(newItem);
            
            // Attach event ke tombol hapus item baru
            const removeBtn = newItem.querySelector('.remove-item-ring');
            if (removeBtn) {
                attachRemoveItemRingEvent(removeBtn);
            }
            
            // Attach event input ke field value baru
            const valueInput = newItem.querySelector('input[placeholder*="Jumlah"]');
            if (valueInput) {
                valueInput.addEventListener('input', function() {
                    calculateRingTotal(ringGroup);
                    calculateAllRingsTotal();
                });
            }
            
            // Hitung ulang total
            calculateRingTotal(ringGroup);
            calculateAllRingsTotal();
        });
    }
    
    // Event untuk setiap input value yang sudah ada (auto calculate saat diketik)
    const valueInputs = ringGroup.querySelectorAll('.ring-item input[placeholder*="Jumlah"]');
    valueInputs.forEach(input => {
        // Hapus event listener lama jika ada (untuk menghindari duplikasi)
        input.removeEventListener('input', null);
        input.addEventListener('input', function() {
            calculateRingTotal(ringGroup);
            calculateAllRingsTotal();
        });
    });
    
    // Event untuk tombol hapus item yang sudah ada
    const removeItemBtns = ringGroup.querySelectorAll('.remove-item-ring');
    removeItemBtns.forEach(btn => {
        attachRemoveItemRingEvent(btn);
    });
    
    // Hitung total awal ring ini
    calculateRingTotal(ringGroup);
}

// Inisialisasi semua ring yang sudah ada di halaman
function initAllRings() {
    const ringGroups = document.querySelectorAll('.ring-group');
    ringGroups.forEach(ringGroup => {
        attachRingEvents(ringGroup);
    });
    calculateAllRingsTotal();
}

// Tambah Ring Baru
const addRingBtn = document.getElementById('add-ring');
if (addRingBtn) {
    addRingBtn.addEventListener('click', function() {
        const container = document.getElementById('rings-container');
        const ringIndex = container.children.length;
        const newRing = document.createElement('div');
        newRing.className = 'ring-group border rounded-lg p-4 mb-4 bg-gray-50';
        newRing.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-bold">Ring ${ringIndex + 1}</h4>
                <button type="button" class="remove-ring bg-red-500 text-white px-3 py-1 rounded text-sm">Hapus Ring</button>
            </div>
            <div class="grid grid-cols-2 gap-3 mb-3">
                <input type="text" name="rings[${ringIndex}][title]" class="form-control" placeholder="Judul Ring (contoh: RING I — Warga TCE)">
                <div class="flex gap-2">
                    <select name="rings[${ringIndex}][icon]" class="form-control">
                        <option value="fa-building">🏢 Building</option>
                        <option value="fa-globe">🌍 Globe</option>
                        <option value="fa-hand-holding-heart">🤲 Heart</option>
                        <option value="fa-users">👥 Users</option>
                    </select>
                    <select name="rings[${ringIndex}][color]" class="form-control">
                        <option value="emerald">Hijau (Emerald)</option>
                        <option value="teal">Biru Kehijauan (Teal)</option>
                        <option value="blue">Biru (Blue)</option>
                        <option value="amber">Kuning (Amber)</option>
                        <option value="purple">Ungu (Purple)</option>
                    </select>
                </div>
            </div>
            <div class="ring-items-container mb-3">
                <label class="font-semibold text-sm mb-1 block">Daftar Penerima:</label>
                <div class="flex gap-2 mb-2 ring-item">
                    <input type="text" name="rings[${ringIndex}][items][0][label]" class="form-control flex-1" placeholder="Label (contoh: 👥 Warga RT/RW)">
                    <input type="text" name="rings[${ringIndex}][items][0][value]" class="form-control w-40" placeholder="Jumlah (contoh: 792 penerima)">
                    <button type="button" class="remove-item-ring bg-red-500 text-white px-3 rounded">✕</button>
                </div>
            </div>
            <button type="button" class="add-item-ring bg-gray-500 text-white px-3 py-1 rounded text-sm">+ Tambah Penerima</button>
            <div class="mt-3">
                <label class="font-semibold text-sm mb-1 block">Total Ring Ini (Otomatis)</label>
                <input type="text" name="rings[${ringIndex}][total]" class="form-control bg-gray-100" readonly placeholder="Total akan terisi otomatis">
            </div>
        `;
        container.appendChild(newRing);
        attachRingEvents(newRing);
        calculateAllRingsTotal();
    });
}

// Jalankan inisialisasi saat halaman siap
document.addEventListener('DOMContentLoaded', function() {
    initAllRings();
    initKeuangan();  // <-- TAMBAHKAN INI
    formatCurrencyInput();
});

// ============ DISTRIBUSI DINAMIS ============
document.getElementById('add-distribusi').addEventListener('click', function() {
    const container = document.getElementById('distribusi-container');
    const index = container.children.length;
    const newItem = document.createElement('div');
    newItem.className = 'json-item';
    newItem.innerHTML = `
        <input type="text" name="distribusi[${index}][label]" class="form-control" placeholder="Label">
        <input type="number" name="distribusi[${index}][value]" class="form-control w-32" placeholder="Jumlah">
        <input type="text" name="distribusi[${index}][icon]" class="form-control w-24" placeholder="Icon">
        <input type="number" name="distribusi[${index}][percentage]" class="form-control w-24" placeholder="Persen">
        <button type="button" class="remove-distribusi bg-red-500 text-white px-3 py-2 rounded-lg">✕</button>
    `;
    container.appendChild(newItem);
    attachRemoveDistribusiEvent(newItem.querySelector('.remove-distribusi'));
});

function attachRemoveDistribusiEvent(btn) {
    btn.addEventListener('click', function() {
        this.closest('.json-item').remove();
        reindexDistribusi();
    });
}

function reindexDistribusi() {
    const container = document.getElementById('distribusi-container');
    const items = container.querySelectorAll('.json-item');
    items.forEach((item, idx) => {
        item.querySelectorAll('[name^="distribusi["]').forEach(input => {
            input.name = input.name.replace(/distribusi\[\d+\]/, `distribusi[${idx}]`);
        });
    });
}

document.querySelectorAll('.remove-distribusi').forEach(btn => attachRemoveDistribusiEvent(btn));

// ============ KEUANGAN OTOMATIS ============

// Fungsi untuk ekstrak angka dari string/number
function extractNominal(value) {
    if (!value) return 0;
    if (typeof value === 'number') return value;
    let number = parseInt(value.toString().replace(/\D/g, ''));
    return isNaN(number) ? 0 : number;
}

// Fungsi untuk format rupiah
function formatRupiah(value) {
    return 'Rp ' + value.toLocaleString('id-ID');
}

// Fungsi untuk menghitung total penerimaan
function calculateTotalPenerimaan() {
    let total = 0;
    const penerimaanItems = document.querySelectorAll('#penerimaan-container .json-item input[placeholder*="Jumlah"]');
    penerimaanItems.forEach(item => {
        total += extractNominal(item.value);
    });
    
    // Update display total penerimaan
    const totalPenerimaanElement = document.getElementById('total-penerimaan-display');
    if (totalPenerimaanElement) {
        totalPenerimaanElement.innerText = formatRupiah(total);
    }
    
    // Update hidden input untuk total penerimaan (jika ada)
    const totalPenerimaanInput = document.getElementById('total_penerimaan');
    if (totalPenerimaanInput) {
        totalPenerimaanInput.value = total;
    }
    
    return total;
}

// Fungsi untuk menghitung total pengeluaran
function calculateTotalPengeluaran() {
    let total = 0;
    const pengeluaranItems = document.querySelectorAll('#pengeluaran-container .json-item input[placeholder*="Jumlah"]');
    pengeluaranItems.forEach(item => {
        total += extractNominal(item.value);
    });
    
    // Update display total pengeluaran
    const totalPengeluaranElement = document.getElementById('total-pengeluaran-display');
    if (totalPengeluaranElement) {
        totalPengeluaranElement.innerText = formatRupiah(total);
    }
    
    // Update hidden input untuk total pengeluaran (jika ada)
    const totalPengeluaranInput = document.getElementById('total_pengeluaran');
    if (totalPengeluaranInput) {
        totalPengeluaranInput.value = total;
    }
    
    return total;
}

// Fungsi untuk menghitung sisa dana
function calculateSisaDana() {
    const totalPenerimaan = calculateTotalPenerimaan();
    const totalPengeluaran = calculateTotalPengeluaran();
    const sisaDana = totalPenerimaan - totalPengeluaran;
    
    // Update display sisa dana
    const sisaDanaElement = document.getElementById('sisa-dana-display');
    if (sisaDanaElement) {
        sisaDanaElement.innerText = formatRupiah(sisaDana);
        
        // Tambah warna jika sisa dana negatif
        if (sisaDana < 0) {
            sisaDanaElement.classList.add('text-red-600');
            sisaDanaElement.classList.remove('text-emerald-700');
        } else {
            sisaDanaElement.classList.add('text-emerald-700');
            sisaDanaElement.classList.remove('text-red-600');
        }
    }
    
    return sisaDana;
}

// Fungsi untuk attach event ke item keuangan (penerimaan)
function attachKeuanganPenerimaanEvents(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Event listener untuk setiap input amount
    const amountInputs = container.querySelectorAll('input[placeholder*="Jumlah"]');
    amountInputs.forEach(input => {
        input.removeEventListener('input', null);
        input.addEventListener('input', function() {
            calculateTotalPenerimaan();
            calculateSisaDana();
        });
    });
    
    // Event untuk tombol remove
    const removeBtns = container.querySelectorAll('.remove-item');
    removeBtns.forEach(btn => {
        btn.removeEventListener('click', null);
        btn.addEventListener('click', function() {
            setTimeout(() => {
                calculateTotalPenerimaan();
                calculateSisaDana();
            }, 50);
        });
    });
}

// Fungsi untuk attach event ke item keuangan (pengeluaran)
function attachKeuanganPengeluaranEvents(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Event listener untuk setiap input amount
    const amountInputs = container.querySelectorAll('input[placeholder*="Jumlah"]');
    amountInputs.forEach(input => {
        input.removeEventListener('input', null);
        input.addEventListener('input', function() {
            calculateTotalPengeluaran();
            calculateSisaDana();
        });
    });
    
    // Event untuk tombol remove
    const removeBtns = container.querySelectorAll('.remove-item');
    removeBtns.forEach(btn => {
        btn.removeEventListener('click', null);
        btn.addEventListener('click', function() {
            setTimeout(() => {
                calculateTotalPengeluaran();
                calculateSisaDana();
            }, 50);
        });
    });
}


// Tambah item penerimaan
document.getElementById('add-penerimaan').addEventListener('click', function() {
    const container = document.getElementById('penerimaan-container');
    const index = container.children.length;
    const newItem = document.createElement('div');
    newItem.className = 'json-item';
    newItem.innerHTML = `
        <input type="text" name="keuangan_penerimaan[${index}][label]" class="form-control" placeholder="Label penerimaan">
        <input type="text" name="keuangan_penerimaan[${index}][amount]" class="form-control w-48 currency-input" placeholder="Jumlah (Rp)">
        <button type="button" class="remove-item bg-red-500 text-white px-3 py-2 rounded-lg">✕</button>
    `;
    container.appendChild(newItem);
    attachRemoveEvent(newItem.querySelector('.remove-item'));
    
    // Attach event untuk input amount baru
    const amountInput = newItem.querySelector('.currency-input');
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            calculateTotalPenerimaan();
            calculateSisaDana();
        });
    }
    
    calculateTotalPenerimaan();
    calculateSisaDana();
});

// Tambah item pengeluaran
document.getElementById('add-pengeluaran').addEventListener('click', function() {
    const container = document.getElementById('pengeluaran-container');
    const index = container.children.length;
    const newItem = document.createElement('div');
    newItem.className = 'json-item';
    newItem.innerHTML = `
        <input type="text" name="keuangan_pengeluaran[${index}][label]" class="form-control" placeholder="Label pengeluaran">
        <input type="text" name="keuangan_pengeluaran[${index}][amount]" class="form-control w-48 currency-input" placeholder="Jumlah (Rp)">
        <button type="button" class="remove-item bg-red-500 text-white px-3 py-2 rounded-lg">✕</button>
    `;
    container.appendChild(newItem);
    attachRemoveEvent(newItem.querySelector('.remove-item'));
    
    // Attach event untuk input amount baru
    const amountInput = newItem.querySelector('.currency-input');
    if (amountInput) {
        amountInput.addEventListener('input', function() {
            calculateTotalPengeluaran();
            calculateSisaDana();
        });
    }
    
    calculateTotalPengeluaran();
    calculateSisaDana();
});

// Fungsi untuk inisialisasi keuangan
function initKeuangan() {
    attachKeuanganPenerimaanEvents('penerimaan-container');
    attachKeuanganPengeluaranEvents('pengeluaran-container');
    calculateTotalPenerimaan();
    calculateTotalPengeluaran();
    calculateSisaDana();
}
function attachRemoveEvent(btn) {
    btn.addEventListener('click', function() {
        this.closest('.json-item').remove();
        reindexItems('penerimaan-container', 'keuangan_penerimaan');
        reindexItems('pengeluaran-container', 'keuangan_pengeluaran');
        
        // Hitung ulang total
        calculateTotalPenerimaan();
        calculateTotalPengeluaran();
        calculateSisaDana();
    });
}
// Format currency input
function formatCurrencyInput() {
    document.querySelectorAll('.currency-input').forEach(input => {
        input.addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value) {
                this.value = new Intl.NumberFormat('id-ID').format(value);
            }
        });
    });
}


// ============ UPLOAD GALERI VIA AJAX ============
const uploadGalleryInput = document.getElementById('upload-gallery');
if (uploadGalleryInput) {
    uploadGalleryInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files.length === 0) return;
        
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('gallery_images[]', files[i]);
        }
        formData.append('type', 'gallery');
        
        Swal.fire({
            title: 'Uploading...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('{{ route("admin.qurban.report.upload-gallery", $report->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal!', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error!', 'Terjadi kesalahan: ' + error.message, 'error');
        });
        
        // Reset input file
        e.target.value = '';
    });
}

const uploadAdditionalInput = document.getElementById('upload-additional');
if (uploadAdditionalInput) {
    uploadAdditionalInput.addEventListener('change', function(e) {
        const files = e.target.files;
        if (files.length === 0) return;
        
        const formData = new FormData();
        for (let i = 0; i < files.length; i++) {
            formData.append('additional_images[]', files[i]);
        }
        formData.append('type', 'additional');
        
        Swal.fire({
            title: 'Uploading...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
        
        fetch('{{ route("admin.qurban.report.upload-gallery", $report->id) }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Berhasil!', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('Gagal!', data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire('Error!', 'Terjadi kesalahan: ' + error.message, 'error');
        });
        
        // Reset input file
        e.target.value = '';
    });
}

// ============ HAPUS GAMBAR ============
function removeImage(fieldName) {
    document.getElementById(`remove_${fieldName}`).value = '1';
    const imgElement = document.querySelector(`img[src*="${fieldName}"]`);
    if (imgElement) imgElement.style.opacity = '0.5';
}

document.querySelectorAll('.btn-remove-gallery').forEach(btn => {
    btn.addEventListener('click', function() {
        const index = this.dataset.index;
        Swal.fire({
            title: 'Hapus foto?',
            text: 'Foto akan dihapus dari galeri',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("admin.qurban.report.remove-gallery", $report->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ index: index, type: 'gallery' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                });
            }
        });
    });
});

document.querySelectorAll('.btn-remove-additional').forEach(btn => {
    btn.addEventListener('click', function() {
        const index = this.dataset.index;
        Swal.fire({
            title: 'Hapus foto?',
            text: 'Foto akan dihapus dari galeri tambahan',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('{{ route("admin.qurban.report.remove-gallery", $report->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ index: index, type: 'additional' })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        Swal.fire('Gagal!', data.message, 'error');
                    }
                });
            }
        });
    });
});

// Initial attach events
document.querySelectorAll('.ring-group').forEach(ring => attachRingEvents(ring));
</script>
@endpush
@endsection