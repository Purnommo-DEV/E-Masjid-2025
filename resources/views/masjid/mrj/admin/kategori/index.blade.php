@extends('masjid.master')
@section('title', 'Kategori Berita')
@section('content')
<style>
    .badge {
        margin: 2px;
        display: inline-block;
    }
    td.color-column {
        max-width: 80px;
    }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3>Kelola Kategori</h3>
        <button class="btn btn-primary btn-sm" onclick="addKategori()">Tambah Kategori</button>
    </div>
    <div class="card-body">
        <table id="kategoriTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th class="color-column">Warna</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="kategoriModal">
    <form id="kategoriForm">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Kategori</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Kategori</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Tipe Kategori</label>
                        <select name="tipe" class="form-select" required>
                            <option value="berita">Berita</option>
                            <option value="acara">Acara</option>
                            <option value="galeri">Galeri</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Warna</label>
                        <input type="color" name="warna" class="form-control form-control-color" value="#007bff">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    let table = null;
    const modal = $('#kategoriModal');
    const form = $('#kategoriForm');

    $(function () {
        table = $('#kategoriTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.kategori.data') }}',
            columns: [
                { data: 'nama' },
                { data: 'tipe' },
                {
                    data: 'warna',
                    orderable: false,
                    className: 'color-column',
                    render: function (warna) {
                        return `<div class="badge" style="background:${warna}; width:30px; height:20px;">&nbsp;</div>`;
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (d) {
                        return `
                            <button class="btn btn-sm btn-warning" onclick="editKategori(${d.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteKategori(${d.id})">Hapus</button>
                        `;
                    }
                }
            ]
        });
    });

    window.addKategori = function () {
        form[0].reset();
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.kategori.store') }}');
        modal.find('.modal-title').text('Tambah Kategori');
        modal.modal('show');
    };

    window.editKategori = function (id) {
        $.get(`{{ url('admin/kategori') }}/${id}`, function (data) {
            form[0].reset();
            $('[name=nama]').val(data.nama);
            $('[name=warna]').val(data.warna);
            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/kategori') }}/${id}`);
            modal.find('.modal-title').text('Edit Kategori');
            modal.modal('show');
        }).fail(() => Swal.fire('Error', 'Gagal memuat data.', 'error'));
    };

    window.deleteKategori = function (id) {
        Swal.fire({
            title: 'Yakin?', text: 'Kategori akan dihapus!', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya', cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/kategori') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: res => {
                        table.ajax.reload();
                        Swal.fire('Berhasil', res.message, 'success');
                    },
                    error: xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Gagal.', 'error')
                });
            }
        });
    };

    form.on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        let method = $('#method').val();
        let action = form.attr('action');
        if (method === 'PUT') formData.append('_method', 'PUT');

        $.ajax({
            url: action, type: 'POST', data: formData,
            processData: false, contentType: false,
            success: res => {
                modal.modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: xhr => {
                let msg = xhr.responseJSON?.errors?.nama?.[0] || xhr.responseJSON?.message || 'Gagal.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });
</script>
@endpush