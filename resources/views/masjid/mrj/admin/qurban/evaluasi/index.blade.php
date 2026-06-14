@extends('masjid.master')
@section('title', 'Data Evaluasi Qurban')

@section('content')
@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<style>
    /* Card Wrapper */
    .card-wrapper {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
        margin: 1rem;
    }
    
    /* Header */
    .card-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 100%);
        padding: 1rem 1.5rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    
    .card-header .title {
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        margin: 0;
    }
    
    .card-header .subtitle {
        color: rgba(255,255,255,0.85);
        font-size: 0.8rem;
        margin: 0;
        margin-top: 0.25rem;
    }
    
    .btn-statistik {
        background: white;
        color: #059669;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
        text-decoration: none;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-statistik:hover {
        background: #f0fdf4;
        transform: translateY(-1px);
    }
    
    /* Table Container */
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
    
    /* Badge */
    .badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .badge-success { background: #d1fae5; color: #065f46; }
    .badge-warning { background: #fed7aa; color: #9a3412; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    
    /* Rating Stars */
    .stars {
        color: #fbbf24;
        font-size: 0.875rem;
        letter-spacing: 2px;
    }
    
    /* Action Buttons */
    .action-btn {
        padding: 0.375rem;
        border-radius: 0.5rem;
        border: none;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .action-btn-info {
        background: #e0f2fe;
        color: #0284c7;
    }
    
    .action-btn-info:hover {
        background: #0284c7;
        color: white;
    }
    
    .action-btn-danger {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .action-btn-danger:hover {
        background: #dc2626;
        color: white;
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
    
    /* Detail Item */
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
        color: #4b5563;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .card-header {
            flex-direction: column;
            text-align: center;
        }
        
        .detail-item {
            grid-template-columns: 1fr;
            gap: 0.25rem;
        }
        
        .detail-label {
            font-weight: 700;
            color: #059669;
        }
    }
    .action-btn-warning {
        background: #fef3c7;
        color: #d97706;
        border: none;
        padding: 0.375rem;
        border-radius: 0.5rem;
        cursor: pointer;
    }
    .action-btn-warning:hover {
        background: #d97706;
        color: white;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">📊 Data Evaluasi Peserta Qurban</h3>
            <p class="subtitle">Kelola hasil evaluasi dari shohibul qurban</p>
        </div>
        <a href="{{ route('admin.evaluasi-qurban.statistik') }}" class="btn-statistik">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            Lihat Statistik
        </a>
    </div>
    
    <div class="table-container">
        <!-- FILTER TAHUN -->
        <div style="margin-bottom: 1rem; display: flex; justify-content: flex-end;">
            <div style="display: flex; align-items: center; gap: 0.5rem;">
                <label style="font-size: 0.875rem; font-weight: 500; color: #374151;">Filter Tahun:</label>
                <select id="filterTahun" style="padding: 0.375rem 2rem 0.375rem 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; background: white;">
                    <option value="">Semua Tahun</option>
                    @php
                        $tahunList = App\Models\EvaluasiQurban::select('tahun_hijriah')
                            ->distinct()
                            ->orderBy('tahun_hijriah', 'desc')
                            ->pluck('tahun_hijriah');
                    @endphp
                    @foreach($tahunList as $thn)
                        <option value="{{ $thn }}">{{ $thn }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <table id="evaluasiTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th width="40">No</th>
                    <th>Nama</th>
                    <th>Tahun</th>
                    <th>Jenis</th>
                    <th>Pendaftaran</th>
                    <th>Pelaksanaan</th>
                    <th>Distribusi</th>
                    <th>Kualitas</th>
                    <th>Sumber</th>
                    <th>Rencana</th>
                    <th width="80">Aksi</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>

<!-- Modal Detail -->
<dialog id="detailModal" class="modal-custom">
    <div style="background: white; border-radius: 1rem; overflow: hidden;">
        <div style="background: linear-gradient(135deg, #059669, #10b981); padding: 1rem 1.5rem; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="color: white; font-weight: 700; margin: 0;">📋 Detail Evaluasi Qurban</h3>
            <button onclick="closeDetailModal()" style="background: rgba(255,255,255,0.2); border: none; color: white; font-size: 1.5rem; cursor: pointer; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">&times;</button>
        </div>
        <div id="detailContent" style="padding: 1.5rem; max-height: 65vh; overflow-y: auto;">
            <div class="text-center py-4">Memuat data...</div>
        </div>
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
            url: '{{ route("admin.evaluasi-qurban.data") }}',
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
            { data: 'rencana_qurban_badge', name: 'rencana_qurban', orderable: false },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        language: {
            processing: "Memuat data...",
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
        responsive: true
    });
    // Event change filter
    $('#filterTahun').on('change', function() {
        table.ajax.reload();
    });
});

function detailEvaluasi(id) {
    $('#detailContent').html('<div class="text-center py-4">Memuat data...</div>');
    openModal();
    
    $.get('{{ url("admin/evaluasi-qurban") }}/' + id)
    .done(function(data) {
        function renderStars(rating) {
            let full = Math.floor(rating);
            let half = rating - full >= 0.5;
            let stars = '';
            for (let i = 0; i < full; i++) stars += '★';
            if (half) stars += '½';
            for (let i = stars.length; i < 5; i++) stars += '☆';
            return `<span style="color: #fbbf24; font-size: 1rem; letter-spacing: 2px;">${stars}</span>`;
        }
        
        // Style untuk konten text
        const contentStyle = 'background: #f9fafb; padding: 0.5rem; border-radius: 0.5rem; max-height: 60px; overflow-y: auto; line-height: 1.4; font-size: 0.875rem;';
        const greenStyle = 'background: #f0fdf4; padding: 0.5rem; border-radius: 0.5rem; max-height: 60px; overflow-y: auto; line-height: 1.4; font-size: 0.875rem;';
        const redStyle = 'background: #fef2f2; padding: 0.5rem; border-radius: 0.5rem; max-height: 60px; overflow-y: auto; line-height: 1.4; font-size: 0.875rem;';
        
        // Style untuk section header
        const sectionHeaderStyle = 'font-weight: 700; color: #065f46; background: #e2e8f0; padding: 0.375rem 0.75rem; border-radius: 0.5rem; margin-bottom: 0.75rem; font-size: 0.875rem;';
        
        let html = `
            <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                
                <!-- ==================== SECTION 1: DATA RESPONDEN ==================== -->
                <div>
                    <div style="${sectionHeaderStyle}">DATA RESPONDEN</div>
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem; margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151;">Nama</div>
                        <div>${escapeHtml(data.nama_shohibul)}</div>
                        
                        <div style="font-weight: 600; color: #374151;">Tahun Hijriah</div>
                        <div>${escapeHtml(data.tahun_hijriah)}</div>
                        
                        <div style="font-weight: 600; color: #374151;">Jenis Hewan</div>
                        <div>${data.jenis_hewan == 'sapi' ? 'Sapi' : 'Kambing'}</div>
                        
                        <div style="font-weight: 600; color: #374151;">Sumber Informasi</div>
                        <div>${escapeHtml(data.sumber_info_text) || '-'}</div>
                        
                        <div style="font-weight: 600; color: #374151;">Sumber Lainnya</div>
                        <div>${escapeHtml(data.sumber_info_lainnya) || '-'}</div>
                    </div>
                </div>
                
                <!-- ==================== SECTION 2: RENCANA & PENYEBARAN INFORMASI ==================== -->
                <div>
                    <div style="${sectionHeaderStyle}">RENCANA & PENYEBARAN INFORMASI</div>
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem; margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151;">Rencana Qurban di MRJ Tahun Depan?</div>
                        <div>${data.rencana_qurban == 'ya' ? 'Ya' : (data.rencana_qurban == 'mungkin' ? 'Mungkin' : (data.rencana_qurban == 'tidak' ? 'Tidak' : '-'))}</div>
                    </div>
                    <div style="margin-top: 0.5rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Masukan Penyebaran Informasi</div>
                        <div style="${greenStyle}">${escapeHtml(data.masukan_penyebaran_informasi) || '-'}</div>
                    </div>
                </div>
                
                <!-- ==================== SECTION 3: RATING ==================== -->
                <div>
                    <div style="${sectionHeaderStyle}">RATING PELAYANAN (1-5)</div>
                    <div style="display: grid; grid-template-columns: 140px 1fr; gap: 0.75rem; margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151;">Pendaftaran</div>
                        <div>${renderStars(data.rating_pendaftaran)} (${data.rating_pendaftaran}/5)</div>
                        
                        <div style="font-weight: 600; color: #374151;">Pelaksanaan</div>
                        <div>${renderStars(data.rating_pelaksanaan)} (${data.rating_pelaksanaan}/5)</div>
                        
                        <div style="font-weight: 600; color: #374151;">Distribusi</div>
                        <div>${renderStars(data.rating_distribusi)} (${data.rating_distribusi}/5)</div>
                        
                        <div style="font-weight: 600; color: #374151;">Kualitas Hewan</div>
                        <div>${renderStars(data.rating_kualitas_hewan)} (${data.rating_kualitas_hewan}/5)</div>
                    </div>
                </div>
                
                <!-- ==================== SECTION 4: SARAN ==================== -->
                <div>
                    <div style="${sectionHeaderStyle}">SARAN & MASUKAN</div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Saran Pendaftaran</div>
                        <div style="${contentStyle}">${escapeHtml(data.saran_pendaftaran) || '-'}</div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Saran Pelaksanaan</div>
                        <div style="${contentStyle}">${escapeHtml(data.saran_pelaksanaan) || '-'}</div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Saran Distribusi</div>
                        <div style="${contentStyle}">${escapeHtml(data.saran_distribusi) || '-'}</div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Saran Kualitas Hewan</div>
                        <div style="${contentStyle}">${escapeHtml(data.saran_kualitas_hewan) || '-'}</div>
                    </div>
                </div>
                
                <!-- ==================== SECTION 5: EVALUASI ==================== -->
                <div>
                    <div style="${sectionHeaderStyle}">EVALUASI</div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Hal yang sudah baik</div>
                        <div style="${greenStyle}">${escapeHtml(data.hal_baik) || '-'}</div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Hal yang perlu diperbaiki</div>
                        <div style="${redStyle}">${escapeHtml(data.hal_perbaikan) || '-'}</div>
                    </div>
                    <div style="margin-top: 0.75rem;">
                        <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">Saran Tambahan</div>
                        <div style="${contentStyle}">${escapeHtml(data.saran_tambahan) || '-'}</div>
                    </div>
                </div>
                
            </div>
        `;
        $('#detailContent').html(html);
    })
    .fail(function() {
        $('#detailContent').html('<div class="text-center py-4 text-red-500">❌ Gagal memuat detail</div>');
    });
}

function hapusEvaluasi(id) {
    Swal.fire({
        title: 'Yakin ingin menghapus?',
        text: 'Data evaluasi akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/evaluasi-qurban") }}/' + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        table.ajax.reload();
                        Swal.fire('Berhasil!', res.message, 'success');
                    }
                },
                error: function() {
                    Swal.fire('Error!', 'Gagal menghapus data', 'error');
                }
            });
        }
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

function generateWish(id) {
    Swal.fire({
        title: 'Generate Wish?',
        text: 'Buat ringkasan keinginan untuk data ini',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Generate!',
        cancelButtonText: 'Batal'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/evaluasi-qurban/generate-wish") }}/' + id,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        Swal.fire('Berhasil!', res.wish, 'success');
                        table.ajax.reload();
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Gagal generate wish', 'error');
                }
            });
        }
    });
}

function generateAllWish() {
    let tahun = prompt('Masukkan tahun (contoh: 1447 H):', '1447 H');
    if (!tahun) return;
    
    Swal.fire({
        title: 'Generating Wish...',
        text: 'Proses ini akan membuat ringkasan keinginan untuk semua data tahun ' + tahun,
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });
    
    $.ajax({
        url: '{{ route("admin.evaluasi-qurban.generate-all-wish") }}',
        type: 'POST',
        data: { 
            _token: '{{ csrf_token() }}',
            tahun: tahun 
        },
        success: function(res) {
            Swal.fire('Berhasil!', res.message, 'success');
            table.ajax.reload();
        },
        error: function() {
            Swal.fire('Error', 'Gagal generate wish', 'error');
        }
    });
}
</script>
@endpush