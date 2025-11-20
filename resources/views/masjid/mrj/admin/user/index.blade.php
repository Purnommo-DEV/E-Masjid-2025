@extends('masjid.master')
@section('title', 'Kelola User')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3>Kelola User</h3>
        <button class="btn btn-primary btn-sm" onclick="addUser()">Tambah User</button>
    </div>
    <div class="card-body">
        <table id="userTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="userModal">
    <form id="userForm">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Password <small>(kosongkan jika tidak ubah)</small></label>
                        <input type="password" name="password" class="form-control" id="passwordField">
                    </div>
                    <div class="mb-3">
                        <label>Role</label>
                        <select name="roles[]" class="form-control select2" multiple required>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
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
    let table, modal = $('#userModal'), form = $('#userForm');

    $(function() {
        // ✅ Fix: Select2 dropdown muncul di atas modal
        $('.select2').select2({
            width: '100%',
            dropdownParent: $('#userModal')
        });

        table = $('#userTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.user.data') }}',
            columns: [
                { data: 'name' },
                { data: 'email' },
                { data: 'roles' },
                {
                    data: null,
                    orderable: false,
                    render: d => `
                        <button class="btn btn-sm btn-warning" onclick="editUser(${d.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteUser(${d.id})">Hapus</button>
                    `
                }
            ]
        });
    });

    // === Tambah User ===
    window.addUser = function() {
        form[0].reset();
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.user.store') }}');
        $('#passwordField').prop('required', true);
        $('.select2').val(null).trigger('change');
        modal.find('.modal-title').text('Tambah User');
        modal.modal('show');
    }

    // === Edit User ===
    window.editUser = function(id) {
        $.get(`{{ url('admin/user') }}/${id}`, function(data) {
            form[0].reset();
            $('[name=name]').val(data.name);
            $('[name=email]').val(data.email);
            $('.select2').val(data.roles).trigger('change');
            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/user') }}/${id}`);
            $('#passwordField').prop('required', false);
            modal.find('.modal-title').text('Edit User');
            modal.modal('show');
        }).fail(function() {
            Swal.fire('Error', 'Gagal memuat data user.', 'error');
        });
    }

    // === Hapus User ===
    window.deleteUser = function(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data user ini akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/user') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            table.ajax.reload();
                            Swal.fire('Berhasil', res.message, 'success');
                        } else {
                            Swal.fire('Gagal', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    }

    // === Submit Form ===
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
