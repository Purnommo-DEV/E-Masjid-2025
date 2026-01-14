@extends('masjid.master')
@section('title', 'Role & Permission')

@section('content')

@push('style')
<style>
    table.dataTable td { white-space: normal !important; }
    .permissions-column { max-width: 480px; }

    /* subtle animation */
    .btn-ghost-ico { transition: transform .12s ease, background-color .12s; }
    .btn-ghost-ico:hover { transform: translateY(-2px); }

    /* modern badges */
    .perm-badge {
        display: inline-block;
        padding: .18rem .5rem;
        border-radius: 999px;
        font-size: .72rem;
        line-height: 1;
        box-shadow: 0 1px 2px rgba(0,0,0,0.04);
    }

    /* permission card */
    .perm-card {
        transition: transform .12s ease, box-shadow .12s ease, background-color .12s;
        border-radius: .6rem;
    }
    .perm-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 18px rgba(16,24,40,.08);
    }

    /* nicer dialog backdrop fallback */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }

    /* ---- Header card color tweaks ---- */
    .card-wrapper {
        max-width: 1100px;
        margin-left: auto;
        margin-right: auto;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(2,6,23,0.06);
        border: 1px solid rgba(15,23,42,0.04);
        background: white; /* body white */
    }

    .card-header {
        padding: 1.25rem 1.5rem;
        color: #fff;
        background: linear-gradient(90deg, #059669 0%, #10b981 100%); /* emerald gradient */
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .card-header .title {
        margin: 0;
    }
    .card-header .subtitle { margin: 0; opacity: .95; font-size: .95rem; }

    .card-body {
        padding: 1.25rem 1.5rem;
        background: white;
    }

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

    /* small responsive tweak */
    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
        .header-action { padding: .4rem .7rem; }
    }
</style>
@endpush

<!-- CARD -->
<div class="card-wrapper mx-auto">

    <!-- colored header (full width of card-wrapper) -->
    <div class="card-header">
        <div>
            <h3 class="title text-lg font-semibold">Kelola Role</h3>
            <p class="subtitle text-sm">Atur role dan permission agar kontrol akses lebih rapi.</p>
        </div>

        <button type="button"
                class="header-action"
                onclick="openRoleModal()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Role</span>
        </button>
    </div>

    <!-- body -->
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="roleTable"
                   class="table table-zebra w-full text-sm"
                   style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Nama Role</th>
                        <th class="px-4 py-3">Permission</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL: Role Form (dialog modern) -->
<dialog id="roleModal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl p-0 overflow-hidden rounded-2xl">
        <!-- header -->
        <div class="flex items-center justify-between px-6 py-4 bg-gradient-to-r from-emerald-700 to-emerald-600 text-white">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-white/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" aria-hidden>
                        <path d="M12 4v16M4 12h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h3 id="roleModalTitle" class="text-lg font-semibold">Tambah Role Baru</h3>
                    <p class="text-xs text-white/80">Berikan nama role dan pilih permission yang sesuai.</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button id="closeRoleModalBtn" class="text-white/90 hover:text-white btn-ghost-ico" aria-label="Tutup">
                    <!-- X icon -->
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M6 18L18 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            </div>
        </div>

        <!-- body -->
        <form id="roleForm" class="p-6 bg-white">
            @csrf
            <input type="hidden" id="roleId" name="id" value="">
            <input type="hidden" id="role_method" name="_method" value="POST">

            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nama Role</label>
                    <input type="text" id="roleName" name="name" class="w-full rounded-lg border border-gray-200 px-4 py-2 focus:ring-2 focus:ring-emerald-300" placeholder="Contoh: Administrator" required>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Permission</label>

                    <div id="permissionsList" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        @foreach($permissions as $p)
                        <label class="perm-card bg-white border border-gray-100 p-3 flex items-start gap-3 cursor-pointer">
                            <input type="checkbox" name="permissions[]" value="{{ $p->name }}" class="checkbox checkbox-primary mt-1 role-perm-checkbox">
                            <div>
                                <div class="font-medium text-sm text-gray-800">{{ $p->name }}</div>
                                <div class="text-xs text-gray-500 mt-1">Deskripsi singkat permission (opsional)</div>
                            </div>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex items-center gap-2 justify-end pt-2">
                    <button type="button" id="cancelRoleBtn" class="px-4 py-2 rounded-md border border-gray-200 text-sm hover:bg-gray-50">Batal</button>

                    <button type="submit" id="saveRoleBtn" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-4 py-2 rounded-full shadow hover:scale-[1.02]">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M12 5v14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Simpan Role
                    </button>
                </div>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
/* ====== DataTable init ====== */
let table = null;

$(function(){
    table = $('#roleTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: '{{ route("admin.role.data") }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'permissions', name: 'permissions', orderable: false, className: 'permissions-column',
              render: function(permissions){
                  if(!permissions) return '-';
                  let items = Array.isArray(permissions) ? permissions : String(permissions).split(',').map(s => s.trim()).filter(Boolean);
                  return items.map(p => {
                      let bg = 'bg-gray-100 text-gray-800';
                      if (p.includes('view')) bg = 'bg-blue-50 text-blue-700';
                      else if (p.includes('create')) bg = 'bg-green-50 text-green-700';
                      else if (p.includes('edit')) bg = 'bg-yellow-50 text-yellow-700';
                      else if (p.includes('delete')) bg = 'bg-red-50 text-red-700';
                      return `<span class="perm-badge ${bg} mr-1 mb-1">${p}</span>`;
                  }).join(' ');
              }
            },
            { data: null, orderable: false, render: function(d){
                return `
                  <div class="flex items-center justify-center gap-2">
                    <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-yellow-50 hover:bg-yellow-100 text-yellow-700 shadow-sm btn-ghost-ico" title="Edit" onclick="editRole(${d.id})">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-700 shadow-sm btn-ghost-ico" title="Hapus" onclick="deleteRole(${d.id})">
                      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                  </div>
                `;
            }}
        ],
        responsive: true,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_",
            processing: "Memuat..."
        }
    });
});

/* ====== Role modal (plain dialog) ====== */
(function(){
    const modal = document.getElementById('roleModal');
    const form = document.getElementById('roleForm');
    const roleId = document.getElementById('roleId');
    const methodInput = document.getElementById('role_method');
    const titleEl = document.getElementById('roleModalTitle');
    const closeBtn = document.getElementById('closeRoleModalBtn');
    const cancelBtn = document.getElementById('cancelRoleBtn');
    const saveBtn = document.getElementById('saveRoleBtn');
    const nameInput = document.getElementById('roleName');
    const permCheckboxes = () => document.querySelectorAll('.role-perm-checkbox');

    function showDialog(d){
        try {
            if (typeof d.showModal === 'function') d.showModal();
            else d.classList.add('modal-open');
        } catch(e) { d.classList.add('modal-open'); }
    }
    function closeDialog(d){
        try {
            if (typeof d.close === 'function') d.close();
            else d.classList.remove('modal-open');
        } catch(e) { d.classList.remove('modal-open'); }
    }

    // open create modal
    window.openRoleModal = function(){
        form.reset();
        roleId.value = '';
        methodInput.value = 'POST';
        titleEl.textContent = 'Tambah Role';
        permCheckboxes().forEach(c => c.checked = false);
        showDialog(modal);
        setTimeout(()=> nameInput?.focus(), 100);
    };

    // open edit modal with data
    window.editRole = function(id){
        $.get(`{{ url('admin/role') }}/${id}`)
        .done(function(data){
            roleId.value = data.id ?? '';
            methodInput.value = 'PUT';
            titleEl.textContent = data.name ? `Edit Role: ${data.name}` : 'Edit Role';
            nameInput.value = data.name ?? '';
            const perms = Array.isArray(data.permissions) ? data.permissions : (typeof data.permissions === 'string' ? data.permissions.split(',').map(s=>s.trim()).filter(Boolean) : []);
            permCheckboxes().forEach(c => { c.checked = perms.includes(c.value); });
            showDialog(modal);
            setTimeout(()=> nameInput?.focus(), 100);
        })
        .fail(function(){
            Swal.fire('Error', 'Gagal memuat data role.', 'error');
        });
    };

    closeBtn?.addEventListener('click', ()=> closeDialog(modal));
    cancelBtn?.addEventListener('click', ()=> closeDialog(modal));
    if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

    // submit form
    form?.addEventListener('submit', function(e){
        e.preventDefault();
        const name = nameInput.value?.trim();
        if (!name) { Swal.fire('Gagal', 'Nama role wajib diisi.', 'error'); nameInput.focus(); return; }

        const fd = new FormData(form);
        fd.delete('permissions[]');
        permCheckboxes().forEach(c => { if (c.checked) fd.append('permissions[]', c.value); });

        const isPut = methodInput.value === 'PUT' && roleId.value;
        if (isPut) fd.append('_method', 'PUT');

        const action = isPut ? `{{ url('admin/role') }}/${roleId.value}` : '{{ route("admin.role.store") }}';

        saveBtn.disabled = true;
        const oldTxt = saveBtn.textContent;
        saveBtn.textContent = 'Menyimpan...';

        $.ajax({
            url: action,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: (res) => {
                saveBtn.disabled = false;
                saveBtn.textContent = oldTxt;
                closeDialog(modal);
                table.ajax.reload(null, false);
                Swal.fire('Berhasil', res.message || 'Data tersimpan.', 'success');
            },
            error: (xhr) => {
                saveBtn.disabled = false;
                saveBtn.textContent = oldTxt;
                const msg = xhr.responseJSON?.message || 'Gagal menyimpan';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    window.deleteRole = function(id){
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
                    data: { _token: '{{ csrf_token() }}' },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Berhasil', res.message || 'Data berhasil dihapus.', 'success');
                    },
                    error: function (xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    };
})();
</script>
@endpush
