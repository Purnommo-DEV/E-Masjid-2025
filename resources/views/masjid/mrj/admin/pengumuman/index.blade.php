@extends('masjid.master')
@section('title', 'Manajemen Pengumuman')
@section('content')
<style>
    td.thumb { max-width: 100px; text-align: center; }
    #gambarField { display: none; }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Kelola Pengumuman & Banner</h3>
        <button class="btn btn-primary btn-sm" onclick="addPengumuman()">
            Tambah
        </button>
    </div>
    <div class="card-body">
        <table id="pengumumanTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th class="thumb">Gambar</th>
                    <th>Judul</th>
                    <th>Tipe</th>
                    <th>Status</th>
                    <th>Periode</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
{{-- resources/views/admin/pengumuman/modal.blade.php --}}
<div class="modal fade" id="pengumumanModal">
    <form id="pengumumanForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Pengumuman</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Judul <span class="text-danger">*</span></label>
                        <input type="text" name="judul" id="judul" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Isi</label>
                        <textarea name="isi" id="isi" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe <span class="text-danger">*</span></label>
                        <select name="tipe" id="tipe" class="form-select" required>
                            <option value="banner">Banner Slider</option>
                            <option value="popup">Popup</option>
                            <option value="notif">Notif Push</option>
                        </select>
                    </div>
                    <div class="mb-3" id="gambarField">
                        <label class="form-label">Gambar (Wajib untuk Banner)</label>
                        <input type="file" name="gambar" id="gambarInput" class="form-control" accept="image/*">
                        <div id="previewGambar" class="mt-2"></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Mulai</label>
                            <input type="datetime-local" name="mulai" id="mulai" class="form-control">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Selesai</label>
                            <input type="datetime-local" name="selesai" id="selesai" class="form-control">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <app-check type="checkbox" name="is_active" id="is_active" class="form-check-input" checked>
                        <label class="form-check-label">Aktif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let table, modal = $('#pengumumanModal'), form = $('#pengumumanForm');

    $(function() {
        table = $('#pengumumanTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.pengumuman.data') }}',
            columns: [
                { data: 'gambar', orderable: false, className: 'thumb' },
                { data: 'judul' },
                { data: 'tipe' },
                { data: 'status', orderable: false },
                { data: 'periode', orderable: false },
                {
                    data: null,
                    orderable: false,
                    render: d => `
                        <button class="btn btn-sm btn-warning" onclick="editPengumuman(${d.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="hapusPengumuman(${d.id})">Hapus</button>
                    `
                }
            ],
            language: { processing: "Memuat..." }
        });
    });

    window.addPengumuman = function() {
        form[0].reset();
        $('#previewGambar').empty();
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.pengumuman.store') }}');
        modal.find('.modal-title').text('Tambah Pengumuman');
        toggleGambarField();
        modal.modal('show');
    };

    window.editPengumuman = function(id) {
        $.get(`{{ url('admin/pengumuman') }}/${id}`, function(data) {
            form[0].reset();
            $('#previewGambar').empty();
            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/pengumuman') }}/${id}`);

            $('#judul').val(data.judul);
            $('#isi').val(data.isi);
            $('#tipe').val(data.tipe).trigger('change');
            $('#mulai').val(data.mulai);
            $('#selesai').val(data.selesai);
            $('#is_active').prop('checked', data.is_active);

            if (data.gambar_url) {
                $('#previewGambar').html(`<img src="${data.gambar_url}" width="200" class="img-thumbnail mt-2">`);
            }

            toggleGambarField();
            modal.find('.modal-title').text('Edit Pengumuman');
            modal.modal('show');
        });
    };

    window.hapusPengumuman = function(id) {
        Swal.fire({
            title: 'Yakin?', text: 'Data akan dihapus!', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya, Hapus!'
        }).then(r => {
            if (r.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/pengumuman') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => {
                        table.ajax.reload();
                        Swal.fire('Berhasil!', 'Dihapus.', 'success');
                    }
                });
            }
        });
    };

    $('#tipe').on('change', toggleGambarField);
    function toggleGambarField() {
        $('#gambarField').toggle($('#tipe').val() === 'banner');
    }

    $(document).on('change', '#gambarInput', function(e) {
        const file = e.target.files[0];
        const preview = $('#previewGambar');
        preview.empty();
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            Swal.fire('Error!', 'Maksimal 2MB!', 'error');
            $(this).val('');
            return;
        }

        const reader = new FileReader();
        reader.onload = ev => preview.html(`<img src="${ev.target.result}" width="200" class="img-thumbnail mt-2">`);
        reader.readAsDataURL(file);
    });

    form.on('submit', function(e) {
        e.preventDefault();
        let formData = new FormData(this);
        if ($('#method').val() === 'PUT') formData.append('_method', 'PUT');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: res => {
                modal.modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: xhr => {
                let msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0][0]
                    : 'Gagal menyimpan!';
                Swal.fire('Error!', msg, 'error');
            }
        });
    });
</script>
@endpush