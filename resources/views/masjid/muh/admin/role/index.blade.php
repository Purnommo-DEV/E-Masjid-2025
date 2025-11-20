@extends('masjid.master')
@section('title', 'Role & Permission')

@section('content')
<style>
/* Buat kolom permissions membungkus isi */
table.dataTable td {
    white-space: normal !important;
    word-wrap: break-word;
}

/* Supaya badge tampil rapi dan tidak terlalu rapat */
.badge {
    margin: 2px;
    display: inline-block;
}

/* Opsional: batasi lebar kolom permissions agar tabel tidak melebar */
td.permissions-column {
    max-width: 300px;
}
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3>Kelola Role</h3>
        <button class="btn btn-primary btn-sm" onclick="addRole()">Tambah Role</button>
    </div>
    <div class="card-body">
        <table id="roleTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama Role</th>
                    <th>Permission</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="roleModal">
    <form id="roleForm">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Role</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama Role</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Permission</label>
                        <div class="row">
                            @foreach($permissions as $p)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input type="checkbox" name="permissions[]" value="{{ $p->name }}" class="form-check-input">
                                    <label class="form-check-label">{{ $p->name }}</label>
                                </div>
                            </div>
                            @endforeach
                        </div>
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
    const modal = $('#roleModal');
    const form = $('#roleForm');

    $(function () {
        table = $('#roleTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.role.data') }}',
            columns: [
                { data: 'name' },
                {
                    data: 'permissions',
                    orderable: false,
                    className: 'permissions-column',
                    render: function (permissions) {
                        if (!permissions) return '-';
                        let items = permissions.split(',').map(p => p.trim());
                        return items.map(p => {
                            let color = 'bg-secondary';
                            if (p.includes('view')) color = 'bg-info text-dark';
                            else if (p.includes('create')) color = 'bg-success';
                            else if (p.includes('edit')) color = 'bg-warning text-dark';
                            else if (p.includes('delete')) color = 'bg-danger';
                            return `<span class="badge ${color} me-1 mb-1">${p}</span>`;
                        }).join('');
                    }
                },
                {
                    data: null,
                    orderable: false,
                    render: function (d) {
                        return `
                            <button class="btn btn-sm btn-warning" onclick="editRole(${d.id})">Edit</button>
                            <button class="btn btn-sm btn-danger" onclick="deleteRole(${d.id})">Hapus</button>
                        `;
                    }
                }
            ]
        });
    });

    // === Tambah Role ===
    window.addRole = function () {
        form[0].reset();
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.role.store') }}');
        modal.find('.modal-title').text('Tambah Role');
        modal.modal('show');
    }

    // === Edit Role ===
    window.editRole = function (id) {
        $.get(`{{ url('admin/role') }}/${id}`, function (data) {
            form[0].reset();
            $('[name=name]').val(data.name);
            $('[name^=permissions]').prop('checked', false);
            data.permissions.forEach(p => $(`[value="${p}"]`).prop('checked', true));

            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/role') }}/${id}`);
            modal.find('.modal-title').text('Edit Role');
            modal.modal('show');
        }).fail(function () {
            Swal.fire('Error', 'Gagal memuat data role.', 'error');
        });
    }

    // === Hapus Role ===
    window.deleteRole = function (id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data role ini akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/role') }}/${id}`,
                    type: 'DELETE',
                    data: {},
                    success: function (res) {
                        table.ajax.reload();
                        Swal.fire('Berhasil', res.message || 'Data berhasil dihapus.', 'success');
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    }

    // === Simpan Role (POST / PUT) ===
        form.on('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(this);
            let method = $('#method').val();
            let action = form.attr('action');

            // Jika method PUT, tambahkan _method=PUT agar route update terbaca
            if (method === 'PUT') {
                formData.append('_method', 'PUT');
            }

            $.ajax({
                url: action,
                type: 'POST', // selalu POST agar CSRF valid
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    modal.modal('hide');
                    table.ajax.reload();
                    Swal.fire('Berhasil', res.message || 'Data berhasil disimpan.', 'success');
                },
                error: function (xhr) {
                    let msg = xhr.responseJSON?.errors?.email?.[0]
                        || xhr.responseJSON?.errors?.name?.[0]
                        || xhr.responseJSON?.message
                        || 'Gagal menyimpan data.';
                    Swal.fire('Error', msg, 'error');
                }
            });
        });

</script>
@endpush