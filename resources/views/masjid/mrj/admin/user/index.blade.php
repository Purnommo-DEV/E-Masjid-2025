@extends('masjid.master')
@section('title', 'Kelola User')

@section('content')

@push('style')
<style>
    /* container / card */
    .card-wrapper {
        max-width: 1100px;
        margin: 1.25rem auto;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(2,6,23,0.06);
        border: 1px solid rgba(15,23,42,0.04);
        background: white;
    }

    .card-header {
        padding: 1.25rem 1.5rem;
        color: #fff;
        background: linear-gradient(90deg, #059669 0%, #10b981 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .card-header .title { margin: 0; font-size: 1.125rem; font-weight: 700; }
    .card-header .subtitle { margin: 0; opacity: .95; font-size: .95rem; }

    .card-body { padding: 1.25rem 1.5rem; background: white; }

    /* header button */
    .header-action {
        background: rgba(255,255,255,0.12);
        color: #fff;
        padding: 0.5rem 0.9rem;
        border-radius: 999px;
        display: inline-flex;
        gap: .5rem;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 6px 14px rgba(4,120,87,0.06);
        transition: transform .12s ease, background .12s;
    }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }

    /* table */
    table.dataTable td { white-space: normal !important; }
    .permissions-column { max-width: 480px; }

    /* action buttons */
    .btn-circle-ico {
        display:inline-flex;align-items:center;justify-content:center;
        width:36px;height:36px;border-radius:8px;
        transition: transform .12s ease, box-shadow .12s;
    }
    .btn-circle-ico:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2,6,23,0.06); }

    /* dialog backdrop */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; }
    .modal-box { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); }

    /* inputs */
    .input-plain { border: 1px solid #e6e6e6; border-radius: 8px; padding: .55rem .75rem; width: 100%; }

    /* small responsive tweak */
    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
        .header-action { padding: .4rem .7rem; }
    }

    /* Select2 container width fix */
    .select2-container { width: 100% !important; }
</style>
@endpush

<div class="card-wrapper">

    <!-- header -->
    <div class="card-header">
        <div>
            <h3 class="title">Kelola User</h3>
            <p class="subtitle">Tambah, edit dan atur peran pengguna sistem</p>
        </div>

        <button type="button" class="header-action" onclick="addUser()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah User</span>
        </button>
    </div>

    <!-- body -->
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="userTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Role</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal dialog modern (plain JS) -->
<dialog id="userModal" class="modal">
    <div class="modal-box w-11/12 max-w-2xl p-0 overflow-hidden">
        <form id="userForm" class="p-5">
            @csrf
            <input type="hidden" id="method" value="POST">

            <div class="flex items-center justify-between mb-4">
                <h3 id="userModalTitle" class="text-lg font-semibold text-emerald-800">Form User</h3>
                <button type="button" id="closeUserModalBtn" class="text-gray-600 hover:text-gray-800">✕</button>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Nama</label>
                    <input type="text" name="name" class="input-plain" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Email</label>
                    <input type="email" name="email" class="input-plain" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Password <small class="text-gray-500">(kosongkan jika tidak ubah)</small></label>
                    <input type="password" name="password" id="passwordField" class="input-plain">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Role</label>
                    <select name="roles[]" id="rolesSelect" class="select2" multiple="multiple" required>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" id="cancelUserBtn" class="px-4 py-2 rounded-md border">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<!-- libs (pastikan master sudah include jQuery; kalau belum, ini tetap safe) -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<!-- Select2 -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let table;
    const modalEl = document.getElementById('userModal');
    const $modal = $('#userModal');
    const form = $('#userForm');

    function showDialog(d) {
        try {
            if (typeof d.showModal === 'function') d.showModal();
            else d.classList.add('modal-open');
        } catch (e) { d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try {
            if (typeof d.close === 'function') d.close();
            else d.classList.remove('modal-open');
        } catch (e) { d.classList.remove('modal-open'); }
    }

    $(function(){
        // init Select2 with dropdownParent pointing to dialog for correct z-index
        $('.select2').select2({
            width: '100%',
            dropdownParent: $modal.length ? $modal : $(document.body)
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
                    className: 'text-center',
                    render: d => `
                        <div class="inline-flex gap-2">
                          <button class="btn-circle-ico bg-yellow-50 text-yellow-700" title="Edit" onclick="editUser(${d.id})">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                          </button>
                          <button class="btn-circle-ico bg-red-50 text-red-700" title="Hapus" onclick="deleteUser(${d.id})">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                          </button>
                        </div>
                    `
                }
            ],
            responsive: true
        });

        // modal buttons
        $('#closeUserModalBtn').on('click', () => closeDialog(modalEl));
        $('#cancelUserBtn').on('click', () => closeDialog(modalEl));
        if (modalEl) modalEl.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modalEl); });
    });

    // Add user
    window.addUser = function() {
        form[0].reset();
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.user.store') }}');
        $('#passwordField').prop('required', true);
        $('.select2').val(null).trigger('change');
        $('#userModalTitle').text('Tambah User');
        showDialog(modalEl);
        setTimeout(()=> $('[name=name]').focus(), 120);
    }

    // Edit user
    window.editUser = function(id) {
        $.get(`{{ url('admin/user') }}/${id}`)
            .done(function(data) {
                form[0].reset();
                $('[name=name]').val(data.name);
                $('[name=email]').val(data.email);
                $('.select2').val(data.roles).trigger('change');
                $('#method').val('PUT');
                form.attr('action', `{{ url('admin/user') }}/${id}`);
                $('#passwordField').prop('required', false);
                $('#userModalTitle').text('Edit User');
                showDialog(modalEl);
                setTimeout(()=> $('[name=name]').focus(), 120);
            })
            .fail(function() {
                Swal.fire('Error', 'Gagal memuat data user.', 'error');
            });
    }

    // Delete user
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
                        table.ajax.reload();
                        Swal.fire('Berhasil', res.message || 'User dihapus.', 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    }

    // Submit form
    form.on('submit', function (e) {
        e.preventDefault();

        let formData = new FormData(this);
        let method = $('#method').val();
        let action = form.attr('action');

        if (method === 'PUT') formData.append('_method', 'PUT');

        $.ajax({
            url: action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                closeDialog(modalEl);
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
