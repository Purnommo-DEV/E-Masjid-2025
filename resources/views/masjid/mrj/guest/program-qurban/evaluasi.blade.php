@extends('masjid.master-guest')

@section('title', 'Data Evaluasi Qurban - Masjid Raudhatul Jannah TCE')

@push('head')
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
@endpush

@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    
    /* ================= BACKGROUND SECTIONS ================= */
    .evaluasi-hero {
        background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 45%, #fffbeb 100%);
        position: relative;
        overflow: hidden;
    }
    
    .evaluasi-section {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 48%, #f0fdf4 100%);
    }
    
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
    
    .badge-main:hover {
        transform: scale(1.05);
        box-shadow: 0 14px 35px rgba(5, 150, 105, 0.55);
    }
    
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    @keyframes fadeInUp { 
        from { opacity: 0; transform: translateY(30px); } 
        to { opacity: 1; transform: translateY(0); } 
    }
    
    .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
    .animation-delay-200 { animation-delay: 0.2s; }
    .animation-delay-300 { animation-delay: 0.3s; }
    .animation-delay-400 { animation-delay: 0.4s; }
    
    /* Card Wrapper */
    .card-wrapper {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        padding: 1rem 1.5rem;
    }
    
    .card-header .title {
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }
    
    .table-container {
        padding: 1rem;
        overflow-x: auto;
    }
    
    /* DataTables Custom */
    .dataTables_wrapper {
        font-size: 0.875rem;
    }
    
    .dataTables_wrapper .dataTables_length,
    .dataTables_wrapper .dataTables_filter {
        margin-bottom: 1rem;
    }
    
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        padding: 0.375rem 0.75rem;
        margin-left: 0.5rem;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #e5e7eb;
        margin: 0 0.125rem;
    }
    
    .dataTables_wrapper .dataTables_paginate .paginate_button.current {
        background: #059669;
        border-color: #059669;
        color: white !important;
    }
    
    /* ================= PERBAIKAN TABEL ================= */
    #evaluasiTable {
        width: 100% !important;
        border-collapse: collapse;
        min-width: 1200px;
    }
    
    #evaluasiTable thead th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 700;
        padding: 14px 12px;
        font-size: 0.85rem;
        border-bottom: 2px solid #cbd5e1;
        text-align: left;
        white-space: nowrap;
    }
    
    #evaluasiTable tbody td {
        padding: 12px 12px;
        color: #000000 !important; /* text-black */
        font-size: 0.8rem;
        vertical-align: middle;
        border-bottom: 1px solid #e2e8f0;
        background-color: white;
    }
    
    #evaluasiTable tbody tr {
        border-bottom: 1px solid #e2e8f0;
    }
    
    #evaluasiTable tbody tr:hover {
        background-color: #f8fafc !important;
    }
    
    #evaluasiTable tbody tr:hover td {
        background-color: #f8fafc;
    }
    
    /* Kolom rating stars */
    .stars {
        color: #fbbf24;
        font-size: 0.85rem;
        letter-spacing: 2px;
        white-space: nowrap;
    }
    
    /* Badge rencana */
    .badge-rencana {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }
    .badge-ya { background: #d1fae5; color: #065f46; }
    .badge-mungkin { background: #fed7aa; color: #9a3412; }
    .badge-tidak { background: #fee2e2; color: #991b1b; }
    
    /* Tombol Detail */
    .btn-detail {
        background: #e0f2fe;
        color: #0284c7;
        padding: 8px 12px;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-detail:hover {
        background: #0284c7;
        color: white;
        transform: scale(1.05);
    }
    
    /* Keinginan text - potong jika terlalu panjang */
    .wish-cell {
        max-width: 200px;
        white-space: normal;
        word-wrap: break-word;
        line-height: 1.4;
        color: #000000;
    }
    
    /* Modal */
    .modal-custom {
        border: none;
        border-radius: 1rem;
        padding: 0;
        max-width: 650px;
        width: 90%;
        animation: fadeIn 0.2s ease;
    }
    
    .modal-custom::backdrop {
        background: rgba(0,0,0,0.5);
        backdrop-filter: blur(2px);
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
    
    .detail-item {
        display: grid;
        grid-template-columns: 130px 1fr;
        gap: 0.75rem;
        padding: 0.625rem 0;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .detail-label {
        font-weight: 600;
        color: #374151;
    }
    
    .detail-value {
        color: #000000 !important;
    }
    
    @media (max-width: 768px) {
        .detail-item {
            grid-template-columns: 1fr;
            gap: 0.25rem;
        }
        .detail-label {
            font-weight: 700;
            color: #059669;
        }
    }
    
    .content-style {
        background: #f9fafb;
        padding: 0.5rem;
        border-radius: 0.5rem;
        max-height: 80px;
        overflow-y: auto;
        line-height: 1.4;
        font-size: 0.875rem;
        color: #000000 !important;
    }
    
    .green-style {
        background: #f0fdf4;
        padding: 0.5rem;
        border-radius: 0.5rem;
        max-height: 80px;
        overflow-y: auto;
        line-height: 1.4;
        font-size: 0.875rem;
        color: #000000 !important;
    }
    
    .section-header {
        font-weight: 700;
        color: #065f46;
        background: #e2e8f0;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
    }
    
    .filter-select {
        padding: 0.375rem 2rem 0.375rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        background: white;
    }
    
    /* Responsive */
    @media (max-width: 1200px) {
        #evaluasiTable thead th {
            font-size: 0.75rem;
            padding: 10px 8px;
        }
        #evaluasiTable tbody td {
            font-size: 0.75rem;
            padding: 10px 8px;
        }
        .wish-cell {
            max-width: 150px;
        }
    }
    
    @media (max-width: 768px) {
        .table-container {
            padding: 0.5rem;
        }
        .dataTables_wrapper .dataTables_filter input {
            width: 180px;
        }
    }
    div.dataTables_wrapper div.dataTables_length,
    div.dataTables_wrapper div.dataTables_filter,
    div.dataTables_wrapper div.dataTables_info,
    div.dataTables_wrapper div.dataTables_paginate {
        color: #212529 !important;
    }

    div.dataTables_wrapper select,
    div.dataTables_wrapper input {
        color: #212529 !important;
        background-color: #fff !important;
    }
</style>

<section class="evaluasi-hero relative overflow-hidden pt-6 md:pt-20">
    <div class="container mx-auto px-4 md:px-8 lg:px-20 pt-12 md:pt-20 pb-16 md:pb-20 relative z-10">
        <div class="max-w-5xl mx-auto text-center">
            
            <!-- BADGE UTAMA -->
            <div class="flex justify-center mb-6 md:mb-8 animate-fade-in-up">
                <div class="badge-main relative group cursor-pointer" id="mainBadge">
                    <div class="relative bg-gradient-to-r from-emerald-100/60 via-amber-50/60 to-emerald-100/60 backdrop-blur-md rounded-full shadow-md">
                        <div class="flex items-center gap-1.5 md:gap-3 px-3 md:px-7 py-1.5 md:py-3.5">
                            <div class="bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-full p-1 md:p-2 shadow-sm">
                                <i class="fas fa-chart-line text-white text-xs md:text-base"></i>
                            </div>
                            <span class="text-sm sm:text-base md:text-xl lg:text-2xl font-black tracking-wide text-emerald-700">
                                ✨ DATA EVALUASI QURBAN ✨
                            </span>
                            <div class="bg-gradient-to-br from-teal-400 to-teal-500 rounded-full p-1 md:p-2 shadow-sm">
                                <i class="fas fa-file-alt text-white text-xs md:text-base"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- TITLE -->
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-extrabold mb-4 md:mb-6 leading-tight animate-fade-in-up text-slate-900 px-2">
                <span class="block bg-gradient-to-r from-emerald-700 via-teal-600 to-amber-500 bg-clip-text text-transparent mt-1 md:mt-2">
                    Masukan & Kritik Pequrban
                </span>
            </h1>
            
            <!-- SUBTITLE -->
            <div class="max-w-3xl mx-auto mb-8 md:mb-10 px-3">
                <p class="text-slate-600 text-sm sm:text-base md:text-lg leading-relaxed animate-fade-in-up animation-delay-200">
                    <span class="font-semibold text-emerald-700">Masjid Raudhotul Jannah</span> 
                    menyajikan dokumentasi lengkap evaluasi dari para shohibul qurban 
                    sebagai bahan perbaikan pelayanan ke depan.
                </p>
                <p class="text-amber-700 font-semibold text-sm md:text-base mt-3 animate-pulse">
                    <i class="fas fa-quote-right mr-1"></i> 
                    "Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah" 
                    <i class="fas fa-quote-left ml-1"></i>
                    <span class="block text-xs text-slate-400 mt-1">(QS. Al-Kautsar: 2)</span>
                </p>
            </div>
            
            <!-- STAT CARDS - DIHAPUS SESUAI PERMINTAAN -->
            <!-- Card Total Responden, Tahun Hijriah, 100% Transparan dihapus -->
            
        </div>
    </div>
    
    <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-white to-transparent"></div>
</section>

{{-- ================= DATA TABEL ================= --}}
<section class="evaluasi-section py-14 md:py-20">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        
        <!-- Card Utama -->
        <div class="card-wrapper shadow-xl">
            <div class="card-header">
                <h3 class="title">📊 Data Evaluasi Peserta Qurban</h3>
                <p class="text-white/80 text-xs mt-1">Daftar lengkap evaluasi dari shohibul qurban</p>
            </div>
            
            <div class="table-container">
                <!-- Filter Tahun -->
                <div style="margin-bottom: 1rem; display: flex; justify-content: flex-end;">
                    <div style="display: flex; align-items: center; gap: 0.5rem;">
                        <label style="font-size: 0.875rem; font-weight: 500; color: #374151;">Filter Tahun:</label>
                        <select id="filterTahun" class="filter-select text-black">
                            <option value="">Semua Tahun</option>
                            @foreach($tahunList as $thn)
                                <option class="text-black" value="{{ $thn }}">{{ $thn }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <table id="evaluasiTable" class="display text-black" style="width:100%">
                    <thead>
                        <tr>
                            <th width="35">No</th>
                            <th>Nama</th>
                            <th>Tahun</th>
                            <th>Jenis</th>
                            <th>⭐ Pendaftaran</th>
                            <th>⭐ Pelaksanaan</th>
                            <th>⭐ Distribusi</th>
                            <th>⭐ Kualitas</th>
                            <th>Sumber Info</th>
                            <th>Rencana</th>
                            <th width="180">Keinginan (Penyembelihan)</th>
                            <th width="180">Keinginan (Distribusi)</th>
                            <th width="50">Aksi</th>
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
        <div style="background: linear-gradient(135deg, #059669, #10b981); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: white; font-weight: 700; margin: 0;">📋 Detail Evaluasi Qurban</h3>
            <button onclick="closeDetailModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        <div id="detailContent" style="padding: 1.5rem; max-height: 65vh; overflow-y: auto;"></div>
        <div style="padding: 1rem 1.5rem; border-top: 1px solid #e5e7eb; display: flex; justify-content: flex-end;">
            <button onclick="closeDetailModal()" style="padding: 0.5rem 1rem; background: #f3f4f6; border: none; border-radius: 0.5rem; cursor: pointer;">Tutup</button>
        </div>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let table;
const detailModal = document.getElementById('detailModal');

function openModal() {
    if (detailModal.showModal) detailModal.showModal();
    else detailModal.style.display = 'block';
}

function closeDetailModal() {
    if (detailModal.close) detailModal.close();
    else detailModal.style.display = 'none';
}

detailModal.addEventListener('click', (e) => {
    if (e.target === detailModal) closeDetailModal();
});

$(function() {
    table = $('#evaluasiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("guest.evaluasi-qurban.data") }}',
            data: function(d) {
                d.tahun = $('#filterTahun').val();
            }
        },
        columns: [
            { data: null, orderable: false, searchable: false,
                render: (data, type, row, meta) => meta.row + 1 },
            { data: 'nama_shohibul', name: 'nama_shohibul' },
            { data: 'tahun_hijriah', name: 'tahun_hijriah' },
            { data: 'jenis_hewan', name: 'jenis_hewan',
                render: (data) => data == 'sapi' ? '🐃 Sapi' : '🐐 Kambing' },
            { data: 'rating_pendaftaran_star', name: 'rating_pendaftaran', orderable: false },
            { data: 'rating_pelaksanaan_star', name: 'rating_pelaksanaan', orderable: false },
            { data: 'rating_distribusi_star', name: 'rating_distribusi', orderable: false },
            { data: 'rating_kualitas_star', name: 'rating_kualitas_hewan', orderable: false },
            { data: 'sumber_info_text', name: 'sumber_info', orderable: false },
            { data: 'rencana_qurban_text', name: 'rencana_qurban', orderable: false,
                render: (data) => `<span class="badge-rencana ${data === 'Ya' ? 'badge-ya' : (data === 'Mungkin' ? 'badge-mungkin' : 'badge-tidak')}">${data}</span>` },
            { data: 'wish_pelaksanaan_text', name: 'wish_pelaksanaan', orderable: false,
                render: (data) => `<div class="wish-cell">${escapeHtml(data) || '-'}</div>` },
            { data: 'wish_distribusi_text', name: 'wish_distribusi', orderable: false,
                render: (data) => `<div class="wish-cell">${escapeHtml(data) || '-'}</div>` },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        language: {
            processing: '<div class="text-center py-3">Memuat data...</div>',
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
            emptyTable: "Belum ada data evaluasi",
            zeroRecords: "Tidak ditemukan data",
            paginate: {
                first: "Pertama",
                last: "Terakhir",
                next: "→",
                previous: "←"
            }
        },
        order: [[2, 'desc']],
        responsive: true,
        autoWidth: false,
        scrollX: true
    });
    
    $('#filterTahun').on('change', function() {
        table.ajax.reload();
    });
    
    $('#mainBadge').on('click', function() {
        Swal.fire({
            title: '✨ DATA EVALUASI QURBAN ✨',
            html: '📊 Seluruh data evaluasi ditampilkan secara transparan<br><br>🤲 Semoga menjadi bahan perbaikan ke depan',
            icon: 'success',
            confirmButtonColor: '#10b981',
            background: 'linear-gradient(135deg, #1f2937, #064e3b)',
            color: '#ffffff',
            timer: 4000
        });
    });
});

function detailEvaluasi(id) {
    $('#detailContent').html('<div class="text-center py-4">Memuat data...</div>');
    openModal();
    
    $.get('{{ url("guest/evaluasi-qurban") }}/' + id)
    .done(function(data) {
        function renderStars(rating) {
            let full = Math.floor(rating);
            let half = rating - full >= 0.5;
            let stars = '';
            for (let i = 0; i < full; i++) stars += '★';
            if (half) stars += '½';
            for (let i = stars.length; i < 5; i++) stars += '☆';
            return `<span style="color: #fbbf24; font-size: 1rem;">${stars}</span>`;
        }
        
        let html = `
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                
                <div>
                    <div class="section-header">DATA RESPONDEN</div>
                    <div class="detail-item">
                        <span class="detail-label">Nama</span>
                        <span class="detail-value" style="color:#1e293b;">${escapeHtml(data.nama_shohibul)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Tahun Hijriah</span>
                        <span class="detail-value" style="color:#1e293b;">${escapeHtml(data.tahun_hijriah)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Jenis Hewan</span>
                        <span class="detail-value" style="color:#1e293b;">${data.jenis_hewan == 'sapi' ? '🐃 Sapi' : '🐐 Kambing'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Sumber Informasi</span>
                        <span class="detail-value" style="color:#1e293b;">${escapeHtml(data.sumber_info_text) || '-'}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Rencana Qurban di MRJ</span>
                        <span class="detail-value" style="color:#1e293b;">${data.rencana_qurban == 'ya' ? '✅ Ya' : (data.rencana_qurban == 'mungkin' ? '🤔 Mungkin' : (data.rencana_qurban == 'tidak' ? '❌ Tidak' : '-'))}</span>
                    </div>
                </div>
                
                <div>
                    <div class="section-header">MASUKAN PENYEBARAN INFORMASI</div>
                    <div class="green-style">${escapeHtml(data.masukan_penyebaran_informasi) || '-'}</div>
                </div>
                
                <div>
                    <div class="section-header">RATING PELAYANAN (1-5)</div>
                    <div class="detail-item">
                        <span class="detail-label">Pendaftaran</span>
                        <span class="detail-value" style="color:#1e293b;">${renderStars(data.rating_pendaftaran)} (${data.rating_pendaftaran}/5)</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Pelaksanaan</span>
                        <span class="detail-value" style="color:#1e293b;">${renderStars(data.rating_pelaksanaan)} (${data.rating_pelaksanaan}/5)</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Distribusi</span>
                        <span class="detail-value" style="color:#1e293b;">${renderStars(data.rating_distribusi)} (${data.rating_distribusi}/5)</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Kualitas Hewan</span>
                        <span class="detail-value" style="color:#1e293b;">${renderStars(data.rating_kualitas_hewan)} (${data.rating_kualitas_hewan}/5)</span>
                    </div>
                </div>
                
                <div>
                    <div class="section-header">SARAN & MASUKAN</div>
                    <div><span class="detail-label">Saran Pendaftaran</span></div>
                    <div class="content-style">${escapeHtml(data.saran_pendaftaran) || '-'}</div>
                    <div style="margin-top: 0.5rem;"><span class="detail-label">Saran Pelaksanaan</span></div>
                    <div class="content-style">${escapeHtml(data.saran_pelaksanaan) || '-'}</div>
                    <div style="margin-top: 0.5rem;"><span class="detail-label">Saran Distribusi</span></div>
                    <div class="content-style">${escapeHtml(data.saran_distribusi) || '-'}</div>
                    <div style="margin-top: 0.5rem;"><span class="detail-label">Saran Kualitas Hewan</span></div>
                    <div class="content-style">${escapeHtml(data.saran_kualitas_hewan) || '-'}</div>
                </div>
                
                <div>
                    <div class="section-header">KEINGINAN (WISH)</div>
                    <div><span class="detail-label">🔪 Penyembelihan</span></div>
                    <div class="content-style">${escapeHtml(data.wish_pelaksanaan) || '-'}</div>
                    <div style="margin-top: 0.5rem;"><span class="detail-label">🚚 Distribusi</span></div>
                    <div class="content-style">${escapeHtml(data.wish_distribusi) || '-'}</div>
                </div>
                
                <div>
                    <div class="section-header">EVALUASI</div>
                    <div><span class="detail-label">✅ Hal yang sudah baik</span></div>
                    <div class="green-style">${escapeHtml(data.hal_baik) || '-'}</div>
                    <div style="margin-top: 0.5rem;"><span class="detail-label">🔧 Hal yang perlu diperbaiki</span></div>
                    <div class="green-style">${escapeHtml(data.hal_perbaikan) || '-'}</div>
                    <div style="margin-top: 0.5rem;"><span class="detail-label">💡 Saran Tambahan</span></div>
                    <div class="content-style">${escapeHtml(data.saran_tambahan) || '-'}</div>
                </div>
                
            </div>
        `;
        $('#detailContent').html(html);
    })
    .fail(function() {
        $('#detailContent').html('<div class="text-center py-4 text-red-500">❌ Gagal memuat detail</div>');
    });
}

function escapeHtml(str) {
    if (!str) return str;
    return str.replace(/[&<>]/g, function(m) {
        if (m === '&') return '&amp;';
        if (m === '<') return '&lt;';
        if (m === '>') return '&gt;';
        return m;
    });
}
</script>
@endpush