@extends('masjid.master')
@section('title', 'Data Evaluasi Qurban')

@section('content')
@push('style')
<style>
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .bg-success { background: #10b981; color: white; }
    .bg-warning { background: #f59e0b; color: white; }
    .bg-danger { background: #ef4444; color: white; }
    .bg-primary { background: #3b82f6; color: white; }
    .bg-info { background: #06b6d4; color: white; }
    .btn-sm {
        padding: 4px 8px;
        border-radius: 6px;
        cursor: pointer;
    }
    .btn-outline-info {
        border: 1px solid #06b6d4;
        background: transparent;
        color: #06b6d4;
    }
    .btn-outline-info:hover {
        background: #06b6d4;
        color: white;
    }
    .btn-outline-danger {
        border: 1px solid #ef4444;
        background: transparent;
        color: #ef4444;
    }
    .btn-outline-danger:hover {
        background: #ef4444;
        color: white;
    }
    .rating-star {
        color: #fbbf24;
        font-size: 14px;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">📊 Data Evaluasi Peserta Qurban</h3>
            <p class="subtitle" style="color:rgba(255,255,255,0.9); margin:0;">Kelola hasil evaluasi dari shohibul qurban</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.evaluasi-qurban.statistik') }}" class="btn-primary-solid" style="background: white; color: #059669;">
                📈 Lihat Statistik
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="evaluasiTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama</th>
                        <th>Tahun</th>
                        <th>Jenis Hewan</th>
                        <th>Rating Pendaftaran</th>
                        <th>Rating Pelaksanaan</th>
                        <th>Rating Distribusi</th>
                        <th>Rating Kualitas</th>
                        <th>Rencana</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Detail -->
<dialog id="detailModal" class="modal" style="border-radius: 16px; padding: 0; max-width: 600px; width: 90%;">
    <div class="bg-white rounded-xl overflow-hidden">
        <div class="bg-emerald-600 text-white p-4 flex justify-between items-center">
            <h3 class="font-bold text-lg">📋 Detail Evaluasi</h3>
            <button onclick="closeDetailModal()" class="text-white text-2xl">&times;</button>
        </div>
        <div class="p-5" id="detailContent">
            Loading...
        </div>
        <div class="p-4 border-t flex justify-end">
            <button onclick="closeDetailModal()" class="px-4 py-2 bg-gray-200 rounded-lg">Tutup</button>
        </div>
    </div>
</dialog>

@endsection

@push('scripts')
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

// Close modal when clicking backdrop
detailModal.addEventListener('click', (e) => {
    if (e.target === detailModal) closeDetailModal();
});

$(function() {
    table = $('#evaluasiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.evaluasi-qurban.data") }}',
        columns: [
            { data: null, orderable: false, searchable: false, width: 50,
                render: (data, type, row, meta) => meta.row + 1 },
            { data: 'nama_shohibul', name: 'nama_shohibul' },
            { data: 'tahun_hijriah', name: 'tahun_hijriah' },
            { data: 'jenis_hewan', name: 'jenis_hewan' },
            { data: 'rating_pendaftaran_star', name: 'rating_pendaftaran', orderable: false },
            { data: 'rating_pelaksanaan_star', name: 'rating_pelaksanaan', orderable: false },
            { data: 'rating_distribusi_star', name: 'rating_distribusi', orderable: false },
            { data: 'rating_kualitas_star', name: 'rating_kualitas_hewan', orderable: false },
            { data: 'rencana_qurban_badge', name: 'rencana_qurban', orderable: false },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        language: {
            processing: "Memuat data...",
            emptyTable: "Belum ada data evaluasi",
            zeroRecords: "Tidak ditemukan data"
        },
        order: [[2, 'desc']]
    });
});

function detailEvaluasi(id) {
    $('#detailContent').html('<div class="text-center py-4">Loading...</div>');
    openModal();
    
    $.get('{{ url("admin/evaluasi-qurban") }}/' + id)
    .done(function(data) {
        let html = `
            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <span class="font-semibold">Nama:</span>
                    <span>${data.nama_shohibul}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <span class="font-semibold">Tahun Hijriah:</span>
                    <span>${data.tahun_hijriah}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <span class="font-semibold">Jenis Hewan:</span>
                    <span>${data.jenis_hewan == 'sapi' ? '🐃 Sapi' : '🐐 Kambing'}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <span class="font-semibold">Sumber Info:</span>
                    <span>${data.sumber_info_text || '-'}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <span class="font-semibold">Tempat Qurban:</span>
                    <span>${data.tempat_qurban_text}</span>
                </div>
                <div class="grid grid-cols-2 gap-2 border-b pb-2">
                    <span class="font-semibold">Rencana Qurban:</span>
                    <span>${data.rencana_qurban_badge}</span>
                </div>
                <div class="border-b pb-2">
                    <span class="font-semibold">📝 Saran Pendaftaran:</span>
                    <p class="text-gray-600 mt-1">${data.saran_pendaftaran || '-'}</p>
                </div>
                <div class="border-b pb-2">
                    <span class="font-semibold">🔪 Saran Pelaksanaan:</span>
                    <p class="text-gray-600 mt-1">${data.saran_pelaksanaan || '-'}</p>
                </div>
                <div class="border-b pb-2">
                    <span class="font-semibold">📦 Saran Distribusi:</span>
                    <p class="text-gray-600 mt-1">${data.saran_distribusi || '-'}</p>
                </div>
                <div class="border-b pb-2">
                    <span class="font-semibold">🐪 Saran Kualitas Hewan:</span>
                    <p class="text-gray-600 mt-1">${data.saran_kualitas_hewan || '-'}</p>
                </div>
                <div class="border-b pb-2">
                    <span class="font-semibold">✅ Hal yang sudah baik:</span>
                    <p class="text-gray-600 mt-1">${data.hal_baik || '-'}</p>
                </div>
                <div class="border-b pb-2">
                    <span class="font-semibold">🔧 Hal yang perlu diperbaiki:</span>
                    <p class="text-gray-600 mt-1">${data.hal_perbaikan || '-'}</p>
                </div>
                <div>
                    <span class="font-semibold">💡 Saran Tambahan:</span>
                    <p class="text-gray-600 mt-1">${data.saran_tambahan || '-'}</p>
                </div>
            </div>
        `;
        $('#detailContent').html(html);
    })
    .fail(function() {
        $('#detailContent').html('<div class="text-center py-4 text-red-500">Gagal memuat detail</div>');
    });
}

function hapusEvaluasi(id) {
    Swal.fire({
        title: 'Yakin?',
        text: 'Data evaluasi akan dihapus permanen!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
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
</script>
@endpush