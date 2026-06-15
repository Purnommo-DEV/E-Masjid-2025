@extends('masjid.master-guest')

@section('title', 'Data Evaluasi & Ringkasan - Masjid Raudhatul Jannah TCE')

@push('head')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
@endpush

@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    
    .evaluasi-hero { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 45%, #fffbeb 100%); position: relative; overflow: hidden; }
    .evaluasi-section { background: linear-gradient(180deg, #ffffff 0%, #f8fafc 48%, #f0fdf4 100%); }
    
    .badge-main {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: linear-gradient(135deg, #059669, #10b981, #059669);
        background-size: 200% 200%;
        animation: gradientShift 3s ease infinite;
        border-radius: 60px;
        padding: 12px 32px;
        box-shadow: 0 10px 30px rgba(5, 150, 105, 0.45);
        transition: all 0.3s ease;
    }
    .badge-main:hover { transform: scale(1.05); box-shadow: 0 14px 35px rgba(5, 150, 105, 0.55); }
    
    @keyframes gradientShift { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
    .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
    
    .card-wrapper { background: white; border-radius: 1rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; }
    .card-header { background: linear-gradient(135deg, #059669 0%, #10b981 100%); padding: 1rem 1.5rem; }
    .card-header .title { color: white; font-size: 1.25rem; font-weight: 600; margin: 0; }
    
    .table-container { padding: 1rem; overflow-x: auto; }
    
    #evaluasiTable { width: 100% !important; border-collapse: collapse; min-width: 1200px; }
    #evaluasiTable thead th { background: #f1f5f9; color: #1e293b; font-weight: 700; padding: 14px 12px; font-size: 0.85rem; border-bottom: 2px solid #cbd5e1; white-space: nowrap; }
    #evaluasiTable tbody td { padding: 12px 12px; color: #000000 !important; font-size: 0.8rem; vertical-align: middle; border-bottom: 1px solid #e2e8f0; background: white; }
    #evaluasiTable tbody tr:hover { background-color: #f8fafc; }
    
    .stars { color: #fbbf24; font-size: 0.85rem; letter-spacing: 2px; white-space: nowrap; }
    
    .badge-rencana { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 600; white-space: nowrap; }
    .badge-ya { background: #d1fae5; color: #065f46; }
    .badge-mungkin { background: #fed7aa; color: #9a3412; }
    .badge-tidak { background: #fee2e2; color: #991b1b; }
    
    .btn-detail { background: #e0f2fe; color: #0284c7; padding: 8px 12px; border-radius: 0.5rem; border: none; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; justify-content: center; }
    .btn-detail:hover { background: #0284c7; color: white; transform: scale(1.05); }
    
    .wish-cell { max-width: 200px; white-space: normal; word-wrap: break-word; line-height: 1.4; color: #000000; }
    
    .filter-select { padding: 0.375rem 2rem 0.375rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; background: white; cursor: pointer; }
    
    /* Stat Cards */
    .stat-card { background: white; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease; text-align: center; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .stat-number { font-size: 2rem; font-weight: 800; color: #059669; }
    
    .chart-container { background: white; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .progress-bar-custom { height: 0.5rem; background: #e5e7eb; border-radius: 1rem; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 1rem; transition: width 0.5s ease; }
    
    /* ================= PERBAIKAN CHART RESPONSIVE ================= */
    .chart-container canvas {
        max-width: 100%;
        height: auto !important;
        max-height: 300px;
    }
    
    @media (max-width: 1200px) {
        #evaluasiTable thead th { font-size: 0.75rem; padding: 10px 8px; }
        #evaluasiTable tbody td { font-size: 0.75rem; padding: 10px 8px; }
        .wish-cell { max-width: 150px; }
        
        /* Perkecil chart di tablet */
        .chart-container canvas {
            max-height: 260px;
        }
    }
    
    @media (max-width: 768px) { 
        .table-container { padding: 0.5rem; } 
        .dataTables_wrapper .dataTables_filter input { width: 180px; }
        
        /* Perbaikan chart di mobile */
        .chart-container canvas {
            max-height: 220px;
        }
        
        .chart-container {
            padding: 0.75rem;
        }
        
        .chart-container h3 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        /* Stat Cards di mobile */
        .stat-card {
            padding: 0.75rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
        }
        
        .stat-card .w-12 {
            width: 2rem;
            height: 2rem;
        }
        
        .stat-card .text-xl {
            font-size: 0.9rem;
        }
        
        .gap-6 {
            gap: 1rem;
        }
        
        .mb-8 {
            margin-bottom: 1rem;
        }
        
        /* Grid chart di mobile jadi 1 kolom */
        .grid-cols-2 {
            grid-template-columns: 1fr !important;
        }
    }
    
    @media (max-width: 480px) {
        .chart-container canvas {
            max-height: 180px;
        }
        
        .stat-number {
            font-size: 1.2rem;
        }
        
        .stat-card p {
            font-size: 0.7rem;
        }
        
        .chart-container h3 {
            font-size: 0.8rem;
        }
    }
    
    div.dataTables_wrapper div.dataTables_length, div.dataTables_wrapper div.dataTables_filter, div.dataTables_wrapper div.dataTables_info, div.dataTables_wrapper div.dataTables_paginate { color: #212529 !important; }
    div.dataTables_wrapper select, div.dataTables_wrapper input { color: #212529 !important; background-color: #fff !important; }

    /* ================= PERBAIKAN SUMBER INFORMASI ================= */
    .sumber-info-wrapper {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .sumber-info-wrapper {
            flex-direction: row;
            align-items: stretch;
        }
    }

    .sumber-info-chart {
        flex: 1.2;
        min-width: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .sumber-info-list {
        flex: 1;
        min-width: 0;
        max-height: 280px;
        overflow-y: auto;
        padding-right: 0.75rem;
    }

    .sumber-info-item {
        margin-bottom: 1rem;
        padding: 0.25rem 0;
    }

    .sumber-info-label {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: 0.8rem;
        margin-bottom: 0.35rem;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .sumber-info-name {
        font-weight: 600;
        color: #1e293b;
        word-break: break-word;
        flex: 1;
        font-size: 0.8rem;
    }

    .sumber-info-count {
        font-weight: 700;
        color: #059669;
        background: #ecfdf5;
        padding: 0.125rem 0.5rem;
        border-radius: 1rem;
        font-size: 0.7rem;
        white-space: nowrap;
    }

    /* Progress Bar */
    .sumber-info-progress {
        height: 0.6rem;
        background: #e5e7eb;
        border-radius: 1rem;
        overflow: hidden;
    }

    .sumber-info-fill {
        height: 100%;
        border-radius: 1rem;
        transition: width 0.3s ease;
    }

    /* Chart Container Sumber Info */
    #sumberInfoChart {
        max-width: 100%;
        max-height: 280px;
        width: auto !important;
        margin: 0 auto;
    }

    /* Scrollbar */
    .sumber-info-list::-webkit-scrollbar {
        width: 5px;
    }

    .sumber-info-list::-webkit-scrollbar-track {
        background: #e5e7eb;
        border-radius: 5px;
    }

    .sumber-info-list::-webkit-scrollbar-thumb {
        background: #10b981;
        border-radius: 5px;
    }

    /* Mobile */
    @media (max-width: 768px) {
        .sumber-info-wrapper {
            gap: 1rem;
        }
        
        .sumber-info-label {
            font-size: 0.7rem;
        }
        
        .sumber-info-name {
            font-size: 0.7rem;
            max-width: 65%;
        }
        
        .sumber-info-count {
            font-size: 0.6rem;
            padding: 0.1rem 0.4rem;
        }
        
        .sumber-info-list {
            max-height: 220px;
        }
        
        #sumberInfoChart {
            max-height: 200px;
        }
    }

    @media (max-width: 480px) {
        .sumber-info-name {
            max-width: 55%;
            font-size: 0.65rem;
        }
        
        .sumber-info-count {
            font-size: 0.55rem;
        }
        
        .sumber-info-item {
            margin-bottom: 0.75rem;
        }
    }

        /* ================= BATAS/BORDER PADA CARD ================= */

    /* Stat Cards */
    .stat-card {
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .stat-card:hover {
        border-color: #10b981;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }

    /* Chart Container */
    .chart-container {
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    .chart-container:hover {
        border-color: #10b981;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }

    /* Detail Rating Container (di dalam chart-container) */
    #detailRatingContainer .space-y-4 > div {
        border: 1px solid #f1f5f9;
        border-radius: 0.75rem;
        padding: 0.75rem;
        background: #fafafa;
        transition: all 0.2s ease;
    }
    #detailRatingContainer .space-y-4 > div:hover {
        border-color: #cbd5e1;
        background: white;
    }

    /* Insight Box Cards */
    #insightContainer > div {
        border: 1px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    #insightContainer > div:hover {
        border-color: #10b981;
        transform: translateY(-2px);
    }

    /* Sumber Info Item */
    .sumber-info-item {
        border-bottom: 1px dashed #e2e8f0;
        padding-bottom: 0.75rem;
        margin-bottom: 0.75rem;
    }
    .sumber-info-item:last-child {
        border-bottom: none;
    }

    /* Sumber Info Card (wrapper) */
    .sumber-info-chart canvas {
        border: 1px solid #f1f5f9;
        border-radius: 1rem;
        padding: 0.5rem;
        background: #fafafa;
    }

    /* Progress Bar container (already has border via .sumber-info-progress) */
    .sumber-info-progress {
        border: 1px solid #e2e8f0;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.02);
    }

    /* Card Wrapper (already exists, just add border) */
    .card-wrapper {
        border: 1px solid #e2e8f0;
    }
    .card-wrapper:hover {
        border-color: #cbd5e1;
    }

    /* Modal (optional) */
    .modal-custom {
        border: 1px solid #e2e8f0;
    }
</style>

<section class="evaluasi-hero relative overflow-hidden pt-6 md:pt-20">
    <div class="container mx-auto px-4 md:px-8 lg:px-20 pt-12 md:pt-20 pb-16 md:pb-20">
        <div class="max-w-5xl mx-auto text-center">
            <div class="flex justify-center mb-6 animate-fade-in-up">
                <div class="badge-main relative group cursor-pointer" id="mainBadge">
                    <div class="relative bg-gradient-to-r from-emerald-100/60 via-amber-50/60 to-emerald-100/60 backdrop-blur-md rounded-full shadow-md">
                        <div class="flex items-center gap-1.5 md:gap-3 px-3 md:px-7 py-1.5 md:py-3.5">
                            <div class="bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-full p-1 md:p-2"><i class="fas fa-chart-line text-white text-xs md:text-base"></i></div>
                            <span class="text-sm sm:text-base md:text-xl lg:text-2xl font-black tracking-wide text-emerald-700">✨ DATA EVALUASI QURBAN ✨</span>
                            <div class="bg-gradient-to-br from-teal-400 to-teal-500 rounded-full p-1 md:p-2"><i class="fas fa-file-alt text-white text-xs md:text-base"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4 animate-fade-in-up text-slate-900">
                <span class="block bg-gradient-to-r from-emerald-700 via-teal-600 to-amber-500 bg-clip-text text-transparent">Masukan & Kritik Pequrban</span>
            </h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto animate-fade-in-up">Dokumentasi lengkap evaluasi dari para shohibul qurban sebagai bahan perbaikan pelayanan ke depan.</p>
        </div>
    </div>
    <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-white to-transparent"></div>
</section>

{{-- ================= FILTER TAHUN ================= --}}
<section class="py-5">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="flex justify-end items-center gap-3 flex-wrap">
            <label class="text-gray-600 font-medium">Filter Data & Resumen:</label>
            <select id="filterTahun" class="filter-select text-black">
                <option value="">Semua Tahun</option>
                @foreach($tahunList as $thn)
                    <option value="{{ $thn }}" {{ ($selectedTahun ?? '') == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                @endforeach
            </select>
            <button id="btnResetFilter" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm transition text-black">Reset Filter</button>
        </div>
    </div>
</section>

{{-- ================= SECTION A: STATISTIK & MATRIKS (CHART) ================= --}}
<section class="py-5">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        
        {{-- STAT CARDS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8" id="statCards">
            <div class="stat-card"><div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-users text-emerald-600 text-xl"></i></div><div class="stat-number" id="totalResponden">0</div><p class="text-gray-500 text-sm">Total Responden</p></div>
            <div class="stat-card"><div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-star text-amber-600 text-xl"></i></div><div class="stat-number" id="rataRata">0 / 5</div><p class="text-gray-500 text-sm">Rata-rata Kepuasan</p></div>
            <div class="stat-card"><div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-drumstick-bite text-teal-600 text-xl"></i></div><div class="stat-number" id="jumlahSapi">0</div><p class="text-gray-500 text-sm">Qurban Sapi</p></div>
            <div class="stat-card"><div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-calendar-check text-orange-600 text-xl"></i></div><div class="stat-number" id="minatKembali">0</div><p class="text-gray-500 text-sm">Berminat Qurban Lagi</p></div>
        </div>
        
        {{-- CHART ROW 1: Rating Bar & Jenis Hewan Pie --}}
        <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <div class="chart-container"><h3 class="font-bold text-lg mb-4 text-black"><i class="fas fa-chart-simple text-emerald-600"></i> Rating Kepuasan Layanan</h3><canvas id="ratingChart" height="250"></canvas></div>
            <div class="chart-container"><h3 class="font-bold text-lg mb-4 text-black"><i class="fas fa-chart-pie text-emerald-600"></i> Jenis Hewan Qurban</h3><canvas id="jenisHewanChart" height="250"></canvas></div>
        </div>
        
        {{-- CHART ROW 2: Detail Rating Progress Bar --}}
        <div class="chart-container mb-8">
            <h3 class="font-bold text-lg mb-4 text-black"><i class="fas fa-table-list"></i> Detail Rating per Aspek</h3>
            <div id="detailRatingContainer">
                <div class="space-y-4">
                    <div><div class="flex justify-between mb-1 text-black"><span>📝 Pendaftaran</span><span id="ratingPendaftaran" class="text-black">0 / 5</span></div><div class="progress-bar-custom"><div id="progressPendaftaran" class="progress-fill bg-emerald-500" style="width:0%"></div></div></div>
                    <div><div class="flex justify-between mb-1 text-black"><span>🔪 Penyembelihan</span><span id="ratingPelaksanaan" class="text-black">0 / 5</span></div><div class="progress-bar-custom"><div id="progressPelaksanaan" class="progress-fill bg-amber-500" style="width:0%"></div></div></div>
                    <div><div class="flex justify-between mb-1 text-black"><span>🚚 Distribusi</span><span id="ratingDistribusi" class="text-black">0 / 5</span></div><div class="progress-bar-custom"><div id="progressDistribusi" class="progress-fill bg-teal-500" style="width:0%"></div></div></div>
                    <div><div class="flex justify-between mb-1 text-black"><span>🥩 Kualitas Hewan</span><span id="ratingKualitas" class="text-black">0 / 5</span></div><div class="progress-bar-custom"><div id="progressKualitas" class="progress-fill bg-emerald-500" style="width:0%"></div></div></div>
                </div>
            </div>
        </div>
        
        {{-- CHART ROW 3: Rencana Qurban Donut & Sumber Info --}}
        <div class="grid lg:grid-cols-2 gap-6 mb-8">
            <div class="chart-container">
                <h3 class="font-bold text-lg mb-4 text-black"><i class="fas fa-calendar-alt text-emerald-600"></i> Rencana Qurban Tahun Depan</h3>
                <canvas id="rencanaChart" height="250"></canvas>
            </div>
            <div class="chart-container">
                <h3 class="font-bold text-lg mb-4 text-black"><i class="fas fa-bullhorn text-emerald-600"></i> Sumber Informasi</h3>
                <div id="sumberInfoContainer" class="sumber-info-wrapper">
                    <div class="sumber-info-chart">
                        <canvas id="sumberInfoChart" height="220"></canvas>
                    </div>
                    <div class="sumber-info-list" id="sumberInfoList"></div>
                </div>
            </div>
        </div>
        
        {{-- INSIGHT BOX --}}
        <div class="chart-container">
            <h3 class="font-bold text-lg mb-4 text-black"><i class="fas fa-lightbulb text-amber-600"></i> Insight Penting</h3>
            <div id="insightContainer" class="grid md:grid-cols-2 gap-4">
                <div class="bg-emerald-50 p-3 rounded-lg"><p class="font-semibold text-emerald-800 text-black">Rata-rata Kepuasan</p><p class="text-sm">Memuat data...</p></div>
                <div class="bg-amber-50 p-3 rounded-lg"><p class="font-semibold text-amber-800 text-black">Aspek Terendah</p><p class="text-sm">Memuat data...</p></div>
                <div class="bg-teal-50 p-3 rounded-lg"><p class="font-semibold text-teal-800 text-black">Aspek Tertinggi</p><p class="text-sm">Memuat data...</p></div>
                <div class="bg-blue-50 p-3 rounded-lg"><p class="font-semibold text-blue-800 text-black">Loyalitas Pequrban</p><p class="text-sm">Memuat data...</p></div>
            </div>
        </div>
        
    </div>
</section>

{{-- ================= SECTION B: DATA TABEL ================= --}}
<section class="evaluasi-section py-10">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="card-wrapper shadow-xl">
            <div class="card-header">
                <h3 class="title">📊 Data Evaluasi Peserta Qurban</h3>
                <p class="text-white/80 text-xs mt-1">Daftar lengkap evaluasi dari shohibul qurban</p>
            </div>
            <div class="table-container">
                <table id="evaluasiTable" class="display text-black" style="width:100%">
                    <thead>
                        <tr>
                            <th width="35">No</th><th>Nama</th><th>Tahun</th><th>Jenis</th>
                            <th>Pendaftaran</th><th>Pelaksanaan</th><th>Distribusi</th><th>Kualitas</th>
                            <th>Sumber Info</th><th>Rencana Qurban di MRJ</th><th width="180">Keinginan (Penyembelihan)</th><th width="180">Keinginan (Distribusi)</th><th width="50">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<!-- Modal Detail -->
<dialog id="detailModal" class="modal-custom">
    <div style="background: white; border-radius: 1rem; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #059669, #10b981); padding: 1rem 1.5rem; display: flex; justify-content: space-between;">
            <h3 style="color: white; font-weight: 700;">📋 Detail Evaluasi Qurban</h3>
            <button onclick="closeDetailModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
        </div>
        <div id="detailContent" style="padding: 1.5rem; max-height: 65vh; overflow-y: auto;"></div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; text-align: right;">
            <button onclick="closeDetailModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
        </div>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    let table, ratingChart, jenisHewanChart, rencanaChart, sumberInfoChart;

    function escapeHtml(str) { if (!str) return str; return str.replace(/[&<>]/g, m => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;' }[m])); }
    function updateStars(rating) { let full = Math.floor(rating), half = rating - full >= 0.5, stars = ''; for (let i = 0; i < full; i++) stars += '★'; if (half) stars += '½'; for (let i = stars.length; i < 5; i++) stars += '☆'; return stars; }

    function loadCharts(tahun) {
        $.ajax({ 
            url: '{{ route("guest.evaluasi-qurban.resumen-data") }}', 
            type: 'GET', 
            data: { tahun: tahun }, 
            success: function(res) {
                if (!res.success) return;
                
                // Stat Cards
                $('#totalResponden').text(res.totalResponden);
                $('#rataRata').text(res.rataRataKeseluruhan + ' / 5');
                $('#jumlahSapi').text(res.jenisHewan.sapi);
                $('#minatKembali').text(res.rencanaQurban.ya + res.rencanaQurban.mungkin);
                
                // Rating Progress
                $('#ratingPendaftaran').text(res.ratingData.pendaftaran + ' / 5');
                $('#ratingPelaksanaan').text(res.ratingData.pelaksanaan + ' / 5');
                $('#ratingDistribusi').text(res.ratingData.distribusi + ' / 5');
                $('#ratingKualitas').text(res.ratingData.kualitas + ' / 5');
                $('#progressPendaftaran').css('width', (res.ratingData.pendaftaran / 5) * 100 + '%');
                $('#progressPelaksanaan').css('width', (res.ratingData.pelaksanaan / 5) * 100 + '%');
                $('#progressDistribusi').css('width', (res.ratingData.distribusi / 5) * 100 + '%');
                $('#progressKualitas').css('width', (res.ratingData.kualitas / 5) * 100 + '%');
                
                // Bar Chart
                if (ratingChart) ratingChart.destroy();
                ratingChart = new Chart(document.getElementById('ratingChart'), { 
                    type: 'bar', 
                    data: { 
                        labels: ['Pendaftaran', 'Penyembelihan', 'Distribusi', 'Kualitas'], 
                        datasets: [{ 
                            label: 'Rating (1-5)', 
                            data: [res.ratingData.pendaftaran, res.ratingData.pelaksanaan, res.ratingData.distribusi, res.ratingData.kualitas], 
                            backgroundColor: ['#10b981', '#f59e0b', '#14b8a6', '#059669'], 
                            borderRadius: 8 
                        }] 
                    }, 
                    options: { 
                        responsive: true, 
                        scales: { y: { min: 0, max: 5 } } 
                    } 
                });
                
                // Pie Chart Jenis Hewan
                if (jenisHewanChart) jenisHewanChart.destroy();
                jenisHewanChart = new Chart(document.getElementById('jenisHewanChart'), { 
                    type: 'pie', 
                    data: { 
                        labels: ['Sapi', 'Kambing'], 
                        datasets: [{ 
                            data: [res.jenisHewan.sapi, res.jenisHewan.kambing], 
                            backgroundColor: ['#f59e0b', '#10b981'] 
                        }] 
                    }, 
                    options: { 
                        responsive: true, 
                        plugins: { legend: { position: 'bottom' } } 
                    } 
                });
                
                // Donut Chart Rencana
                if (rencanaChart) rencanaChart.destroy();
                rencanaChart = new Chart(document.getElementById('rencanaChart'), { 
                    type: 'doughnut', 
                    data: { 
                        labels: ['Ya', 'Mungkin', 'Tidak'], 
                        datasets: [{ 
                            data: [res.rencanaQurban.ya, res.rencanaQurban.mungkin, res.rencanaQurban.tidak], 
                            backgroundColor: ['#10b981', '#f59e0b', '#ef4444'] 
                        }] 
                    }, 
                    options: { 
                        responsive: true, 
                        plugins: { legend: { position: 'bottom' } } 
                    } 
                });
                
                // Sumber Info - Chart & List dengan Progress Bar
                if (sumberInfoChart) sumberInfoChart.destroy();
                let sumberLabels = Object.keys(res.sumberInfo);
                let sumberData = Object.values(res.sumberInfo);
                let totalSumber = sumberData.reduce((a, b) => a + b, 0);

                if (sumberLabels.length) {
                    sumberInfoChart = new Chart(document.getElementById('sumberInfoChart'), {
                        type: 'pie',
                        data: {
                            labels: sumberLabels,
                            datasets: [{
                                data: sumberData,
                                backgroundColor: ['#10b981', '#f59e0b', '#14b8a6', '#8b5cf6', '#ef4444', '#06b6d4', '#f97316', '#3b82f6', '#ec4899'],
                                borderWidth: 0
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: { position: 'bottom', labels: { font: { size: 10 }, boxWidth: 10 } }
                            }
                        }
                    });
                    
                    let listHtml = '<div class="space-y-3">';
                    const colors = ['#10b981', '#f59e0b', '#14b8a6', '#8b5cf6', '#ef4444', '#06b6d4', '#f97316', '#3b82f6', '#ec4899'];
                    for (let i = 0; i < sumberLabels.length; i++) {
                        let percent = totalSumber > 0 ? Math.round((sumberData[i] / totalSumber) * 100) : 0;
                        listHtml += `
                            <div class="sumber-info-item">
                                <div class="sumber-info-label">
                                    <span class="sumber-info-name" title="${escapeHtml(sumberLabels[i])}">${escapeHtml(sumberLabels[i])}</span>
                                    <span class="sumber-info-count">${sumberData[i]} (${percent}%)</span>
                                </div>
                                <div class="sumber-info-progress">
                                    <div class="sumber-info-fill" style="width: ${percent}%; background-color: ${colors[i % colors.length]}"></div>
                                </div>
                            </div>
                        `;
                    }
                    listHtml += '</div>';
                    $('#sumberInfoList').html(listHtml);
                } else {
                    const ctx = document.getElementById('sumberInfoChart').getContext('2d');
                    if (ctx) ctx.clearRect(0, 0, 400, 250);
                    $('#sumberInfoList').html('<p class="text-gray-500 text-center py-6">Belum ada data sumber informasi</p>');
                }
                
                // ========== INSIGHT BOX - TAMBAHKAN INI! ==========
                let aspekNames = { 
                    pendaftaran: 'Pendaftaran', 
                    pelaksanaan: 'Penyembelihan', 
                    distribusi: 'Distribusi', 
                    kualitas: 'Kualitas Hewan' 
                };
                
                let ratingValues = Object.values(res.ratingData);
                let minRating = Math.min(...ratingValues);
                let maxRating = Math.max(...ratingValues);
                let minAspek = Object.keys(res.ratingData).find(k => res.ratingData[k] === minRating);
                let maxAspek = Object.keys(res.ratingData).find(k => res.ratingData[k] === maxRating);
                let loyalitas = res.totalResponden > 0 ? Math.round((res.rencanaQurban.ya / res.totalResponden) * 100) : 0;
                
                let insightHtml = `
                    <div class="bg-emerald-50 p-3 rounded-lg">
                        <p class="font-semibold text-emerald-800">📊 Rata-rata Kepuasan</p>
                        <p class="text-sm text-black">Dari skala 1-5, rata-rata keseluruhan <strong>${res.rataRataKeseluruhan}</strong> dari 5.</p>
                    </div>
                    <div class="bg-amber-50 p-3 rounded-lg">
                        <p class="font-semibold text-amber-800">⚠️ Aspek Terendah</p>
                        <p class="text-sm text-black"><strong>${aspekNames[minAspek]}</strong> dengan rating <strong>${minRating}/5</strong>. Perlu perhatian khusus.</p>
                    </div>
                    <div class="bg-teal-50 p-3 rounded-lg">
                        <p class="font-semibold text-teal-800">🏆 Aspek Tertinggi</p>
                        <p class="text-sm text-black"><strong>${aspekNames[maxAspek]}</strong> dengan rating <strong>${maxRating}/5</strong>. Pertahankan!</p>
                    </div>
                    <div class="bg-blue-50 p-3 rounded-lg">
                        <p class="font-semibold text-blue-800">🤝 Loyalitas Pequrban</p>
                        <p class="text-sm text-black">${res.rencanaQurban.ya} responden (${loyalitas}%) akan qurban kembali di MRJ.</p>
                    </div>
                `;
                $('#insightContainer').html(insightHtml);
            },
            error: function(xhr) {
                console.error('Error loading charts:', xhr);
            }
        });
    }

    $(function() {
        // DataTable
        table = $('#evaluasiTable').DataTable({
            processing: true, serverSide: true,
            ajax: { url: '{{ route("guest.evaluasi-qurban.data") }}', data: function(d) { d.tahun = $('#filterTahun').val(); } },
            columns: [
                { data: null, orderable: false, searchable: false, render: (data, type, row, meta) => meta.row + 1 },
                { data: 'nama_shohibul', name: 'nama_shohibul' },
                { data: 'tahun_hijriah', name: 'tahun_hijriah' },
                { data: 'jenis_hewan', name: 'jenis_hewan', render: (data) => data == 'sapi' ? '🐃 Sapi' : '🐐 Kambing' },
                { data: 'rating_pendaftaran_star', name: 'rating_pendaftaran', orderable: false },
                { data: 'rating_pelaksanaan_star', name: 'rating_pelaksanaan', orderable: false },
                { data: 'rating_distribusi_star', name: 'rating_distribusi', orderable: false },
                { data: 'rating_kualitas_star', name: 'rating_kualitas_hewan', orderable: false },
                { data: 'sumber_info_text', name: 'sumber_info', orderable: false },
                { data: 'rencana_qurban_text', name: 'rencana_qurban', orderable: false, render: (data) => `<span class="badge-rencana badge-${data === 'Ya' ? 'ya' : (data === 'Mungkin' ? 'mungkin' : 'tidak')}">${data}</span>` },
                { data: 'wish_pelaksanaan_text', name: 'wish_pelaksanaan', orderable: false, render: (data) => `<div class="wish-cell">${escapeHtml(data) || '-'}</div>` },
                { data: 'wish_distribusi_text', name: 'wish_distribusi', orderable: false, render: (data) => `<div class="wish-cell">${escapeHtml(data) || '-'}</div>` },
                { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
            ],
            language: { processing: "Memuat data...", emptyTable: "Belum ada data evaluasi", zeroRecords: "Tidak ditemukan data", paginate: { first: "Pertama", last: "Terakhir", next: "→", previous: "←" } },
            order: [[2, 'desc']], scrollX: true
        });
        
        // Filter change
        $('#filterTahun').on('change', function() { let tahun = $(this).val(); table.ajax.reload(); loadCharts(tahun); });
        $('#btnResetFilter').on('click', function() { $('#filterTahun').val(''); table.ajax.reload(); loadCharts(''); });
        
        // Initial load
        let initialTahun = $('#filterTahun').val();
        loadCharts(initialTahun);
        
        $('#mainBadge').on('click', () => Swal.fire({ title: '✨ DATA EVALUASI QURBAN ✨', html: '📊 Seluruh data ditampilkan secara transparan', icon: 'success', confirmButtonColor: '#10b981', timer: 3000 }));
    });

    function detailEvaluasi(id) {
        $('#detailContent').html('<div class="text-center py-4">Memuat data...</div>');
        detailModal.showModal();
        $.get('{{ url("guest/evaluasi-qurban") }}/' + id).done(function(data) {
            let html = `<div class="space-y-4"><div class="font-bold text-emerald-700 border-b pb-2">DATA RESPONDEN</div>
                <div class="grid grid-cols-2 gap-2"><span class="font-semibold">Nama:</span><span>${escapeHtml(data.nama_shohibul)}</span></div>
                <div class="grid grid-cols-2 gap-2"><span class="font-semibold">Tahun:</span><span>${escapeHtml(data.tahun_hijriah)}</span></div>
                <div class="grid grid-cols-2 gap-2"><span class="font-semibold">Jenis Hewan:</span><span>${data.jenis_hewan == 'sapi' ? '🐃 Sapi' : '🐐 Kambing'}</span></div>
                <div class="grid grid-cols-2 gap-2"><span class="font-semibold">Sumber Info:</span><span>${escapeHtml(data.sumber_info_text) || '-'}</span></div>
                <div class="grid grid-cols-2 gap-2"><span class="font-semibold">Rencana Qurban:</span><span>${data.rencana_qurban == 'ya' ? '✅ Ya' : (data.rencana_qurban == 'mungkin' ? '🤔 Mungkin' : '❌ Tidak')}</span></div>
                <div class="font-bold text-emerald-700 border-b pb-2 mt-2">MASUKAN PENYEBARAN INFORMASI</div>
                <div class="bg-gray-50 p-2 rounded">${escapeHtml(data.masukan_penyebaran_informasi) || '-'}</div>
                <div class="font-bold text-emerald-700 border-b pb-2">RATING PELAYANAN</div>
                <div class="grid grid-cols-2 gap-2"><span>📝 Pendaftaran:</span><span>${updateStars(data.rating_pendaftaran)} (${data.rating_pendaftaran}/5)</span></div>
                <div class="grid grid-cols-2 gap-2"><span>🔪 Pelaksanaan:</span><span>${updateStars(data.rating_pelaksanaan)} (${data.rating_pelaksanaan}/5)</span></div>
                <div class="grid grid-cols-2 gap-2"><span>🚚 Distribusi:</span><span>${updateStars(data.rating_distribusi)} (${data.rating_distribusi}/5)</span></div>
                <div class="grid grid-cols-2 gap-2"><span>🥩 Kualitas:</span><span>${updateStars(data.rating_kualitas_hewan)} (${data.rating_kualitas_hewan}/5)</span></div>
                <div class="font-bold text-emerald-700 border-b pb-2">KEINGINAN (WISH)</div>
                <div><span class="font-semibold">🔪 Penyembelihan:</span><div class="bg-gray-50 p-2 rounded mt-1">${escapeHtml(data.wish_pelaksanaan) || '-'}</div></div>
                <div class="mt-2"><span class="font-semibold">🚚 Distribusi:</span><div class="bg-gray-50 p-2 rounded mt-1">${escapeHtml(data.wish_distribusi) || '-'}</div></div>
                <div class="font-bold text-emerald-700 border-b pb-2">EVALUASI</div>
                <div><span class="font-semibold">✅ Hal yang sudah baik:</span><div class="bg-green-50 p-2 rounded mt-1">${escapeHtml(data.hal_baik) || '-'}</div></div>
                <div class="mt-2"><span class="font-semibold">🔧 Hal perlu diperbaiki:</span><div class="bg-red-50 p-2 rounded mt-1">${escapeHtml(data.hal_perbaikan) || '-'}</div></div>
                <div class="mt-2"><span class="font-semibold">💡 Saran Tambahan:</span><div class="bg-gray-50 p-2 rounded mt-1">${escapeHtml(data.saran_tambahan) || '-'}</div></div>
            </div>`;
            $('#detailContent').html(html);
        }).fail(() => $('#detailContent').html('<div class="text-center text-red-500">❌ Gagal memuat detail</div>'));
    }
    function closeDetailModal() { detailModal.close(); }
</script>
@endpush