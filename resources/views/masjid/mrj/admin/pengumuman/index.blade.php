@extends('masjid.master')
@section('title', 'Manajemen Pengumuman')
@section('content')

@push('style')
<style>
    .card-wrapper {
        max-width: 1200px;
        margin: 1.25rem auto;
        border-radius: 1rem;
        background: white;
        box-shadow: 0 10px 30px rgba(2,6,23,0.06);
        border: 1px solid rgba(15,23,42,0.04);
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(90deg, #059669 0%, #10b981 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 1rem 1rem 0 0;
    }
    .card-header .title {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: white;
    }
    .card-body {
        padding: 1.25rem 1.5rem;
    }
    .btn-primary-solid {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .9rem;
        border-radius: 8px;
        background: white;
        color: #059669;
        border: none;
        font-weight: 600;
        cursor: pointer;
    }
    .btn-primary-solid:hover {
        background: #f0fdf4;
    }
    .modal-panel {
        background: white;
        border-radius: 16px;
        max-width: 800px;
        width: 90%;
        margin: auto;
    }
    dialog.modal::backdrop {
        background: rgba(0,0,0,0.5);
    }
    .badge {
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
    .bg-primary { background: #3b82f6; color: white; }
    .bg-info { background: #06b6d4; color: white; }
    .bg-warning { background: #f59e0b; color: white; }
    .bg-success { background: #10b981; color: white; }
    .bg-danger { background: #ef4444; color: white; }
    .bg-secondary { background: #6b7280; color: white; }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <h3 class="title">📋 Manajemen Pengumuman & Banner</h3>
        <button type="button" class="btn-primary-solid" onclick="addPengumuman()">
            + Tambah Pengumuman
        </button>
    </div>
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="pengumumanTable" class="display" style="width:100%">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Tipe</th>
                        <th>Status</th>
                        <th>Periode</th>
                        <th width="100">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<dialog id="pengumumanModal" class="modal">
    <div class="modal-panel p-6">
        <form id="pengumumanForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="method" value="POST">
            <input type="hidden" id="edit_id" name="edit_id">

            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-xl font-bold text-emerald-800">Tambah Pengumuman</h3>
                <button type="button" id="closeModalBtn" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Judul <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" id="judul" class="w-full rounded-lg border px-3 py-2" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Tipe <span class="text-red-500">*</span></label>
                    <select name="tipe" id="tipe" class="w-full rounded-lg border px-3 py-2" required>
                        <option value="banner">📢 Banner</option>
                        <option value="popup">🪟 Popup</option>
                        <option value="notif">🔔 Notif Push</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700">Isi Pengumuman</label>
                    <textarea name="isi" id="isi" rows="5" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Mulai</label>
                        <input type="datetime-local" name="mulai" id="mulai" class="w-full rounded-lg border px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Selesai</label>
                        <input type="datetime-local" name="selesai" id="selesai" class="w-full rounded-lg border px-3 py-2">
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="is_active" id="is_active" class="rounded">
                    <label class="text-sm text-gray-700">Aktif</label>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" id="cancelBtn" class="px-4 py-2 rounded-lg border hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
let table;
const modal = document.getElementById('pengumumanModal');

function showModal() {
    modal.showModal();
}

function closeModal() {
    modal.close();
}

$(function() {
    table = $('#pengumumanTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.pengumuman.data") }}',
        columns: [
            { data: 'judul', name: 'judul' },
            { data: 'tipe_badge', name: 'tipe', orderable: false },
            { data: 'status_badge', name: 'is_active', orderable: false },
            { data: 'periode_text', name: 'periode', orderable: false },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
        language: {
            processing: "Memuat data...",
            emptyTable: "Tidak ada data pengumuman"
        }
    });

    $('#closeModalBtn, #cancelBtn').on('click', closeModal);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
});

window.addPengumuman = function() {
    $('#pengumumanForm')[0].reset();
    $('#method').val('POST');
    $('#pengumumanForm').attr('action', '{{ route("admin.pengumuman.store") }}');
    $('#modalTitle').text('Tambah Pengumuman');
    $('#edit_id').val('');
    showModal();
}

window.editPengumuman = function(id) {
    $.get('{{ url("admin/pengumuman") }}/' + id)
    .done(function(data) {
        $('#judul').val(data.judul);
        $('#tipe').val(data.tipe);
        $('#isi').val(data.isi);
        $('#mulai').val(data.mulai);
        $('#selesai').val(data.selesai);
        $('#is_active').prop('checked', data.is_active);
        $('#method').val('PUT');
        $('#edit_id').val(data.id);
        $('#pengumumanForm').attr('action', '{{ url("admin/pengumuman") }}/' + id);
        $('#modalTitle').text('Edit Pengumuman');
        showModal();
    })
    .fail(function() {
        Swal.fire('Error', 'Gagal memuat data', 'error');
    });
}

window.hapusPengumuman = function(id) {
    Swal.fire({
        title: 'Yakin?',
        text: 'Pengumuman akan dihapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!'
    }).then(result => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/pengumuman") }}/' + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        table.ajax.reload();
                        Swal.fire('Berhasil!', res.message, 'success');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Gagal menghapus data', 'error');
                }
            });
        }
    });
}

$('#pengumumanForm').on('submit', function(e) {
    e.preventDefault();
    let formData = $(this).serialize();
    let action = $(this).attr('action');
    let method = $('#method').val();

    if (method === 'PUT') {
        formData += '&_method=PUT';
    }

    $.ajax({
        url: action,
        type: 'POST',
        data: formData,
        success: function(res) {
            if (res.success) {
                closeModal();
                table.ajax.reload();
                Swal.fire('Berhasil!', res.message, 'success');
            }
        },
        error: function(xhr) {
            let errors = xhr.responseJSON?.errors;
            if (errors) {
                let msg = Object.values(errors).flat().join('\n');
                Swal.fire('Validasi Error', msg, 'error');
            } else {
                Swal.fire('Error', 'Terjadi kesalahan', 'error');
            }
        }
    });
});
</script>
@endpush