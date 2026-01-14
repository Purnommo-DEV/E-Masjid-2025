@extends('masjid.master')
@section('title', 'Kategori Berita')
@section('content')

@push('style')
<style>
    /* Card wrapper (sama gaya dengan halaman role/user) */
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

    /* table tweaks */
    table.dataTable td { white-space: normal !important; }
    .color-column { max-width: 120px; }

    /* small buttons */
    .btn-circle-ico {
        display:inline-flex;align-items:center;justify-content:center;
        width:36px;height:36px;border-radius:8px;
        transition: transform .12s ease, box-shadow .12s;
    }
    .btn-circle-ico:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2,6,23,0.06); }

    /* modal backdrop */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; }
    .modal-box { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); }

    /* badge color preview */
    .color-badge {
        display:inline-block;width:34px;height:22px;border-radius:6px;border:1px solid rgba(0,0,0,0.06);
    }

    /* input plain */
    .input-plain { border: 1px solid #e6e6e6; border-radius: 8px; padding: .5rem .75rem; width: 100%; }

    @media (max-width:640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
        .header-action { padding: .4rem .7rem; }
    }
</style>
@endpush

<div class="card-wrapper">

    <!-- header -->
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Kategori</h3>
            <p class="subtitle">Tambah, edit, dan atur kategori berita/acara/galeri</p>
        </div>

        <button type="button" class="header-action" onclick="addKategori()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Kategori</span>
        </button>
    </div>

    <!-- body -->
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="kategoriTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Nama</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Warna</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: kategori (dialog plain JS) -->
<dialog id="kategoriModal" class="modal">
    <div class="modal-box w-11/12 max-w-lg p-0 overflow-hidden">
        <form id="kategoriForm" class="p-5">
            @csrf
            <input type="hidden" id="method" value="POST">

            <div class="flex items-center justify-between mb-4">
                <h3 id="kategoriModalTitle" class="text-lg font-semibold text-emerald-800">Form Kategori</h3>
                <button type="button" id="closeKategoriModalBtn" class="text-gray-600 hover:text-gray-800">✕</button>
            </div>

            <div class="grid grid-cols-1 gap-3">
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Kategori</label>
                    <input type="text" name="nama" class="input-plain" required>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Tipe Kategori</label>
                    <select name="tipe" class="input-plain" required>
                        <option value="berita">Berita</option>
                        <option value="acara">Acara</option>
                        <option value="galeri">Galeri</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <div style="flex:1">
                        <label class="block text-sm font-medium mb-1">Warna</label>
                        <input type="color" name="warna" class="input-plain" value="#007bff" style="height:44px;padding:.25rem">
                    </div>
                    <div style="width:90px;text-align:center">
                        <label class="block text-sm font-medium mb-1 invisible">Preview</label>
                        <div id="warnaPreview" class="color-badge" title="Preview warna"></div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" id="cancelKategoriBtn" class="px-4 py-2 rounded-md border">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@push('scripts')
<!-- libs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
    let table = null;
    const modal = document.getElementById('kategoriModal');
    const $modal = $('#kategoriModal');
    const form = $('#kategoriForm');

    // show/close dialog helpers (fallback)
    function showDialog(d) {
        try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
        catch(e){ d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
        catch(e){ d.classList.remove('modal-open'); }
    }

    $(function () {
        table = $('#kategoriTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.kategori.data') }}',
            columns: [
                { data: 'nama' },
                { data: 'tipe' },
                {
                    data: 'warna', orderable: false, className: 'color-column',
                    render: function (warna) {
                        if (!warna) warna = '#e5e7eb';
                        return `<div class="color-badge" style="background:${warna}"></div> <span class="ml-2 text-sm">${warna}</span>`;
                    }
                },
                {
                    data: null, orderable: false, className: 'text-center',
                    render: function (d) {
                        return `
                            <div class="inline-flex gap-2">
                                <button class="btn-circle-ico bg-yellow-50 text-yellow-700" title="Edit" onclick="editKategori(${d.id})">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <button class="btn-circle-ico bg-red-50 text-red-700" title="Hapus" onclick="deleteKategori(${d.id})">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            responsive: true,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_",
                processing: "Memuat..."
            }
        });

        // modal controls
        $('#closeKategoriModalBtn').on('click', () => closeDialog(modal));
        $('#cancelKategoriBtn').on('click', () => closeDialog(modal));
        if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

        // warna preview update
        $('input[name="warna"]').on('input change', function(){
            $('#warnaPreview').css('background', $(this).val());
        });
        // init preview
        $('#warnaPreview').css('background', $('input[name="warna"]').val() || '#007bff');
    });

    // open add
    window.addKategori = function () {
        form[0].reset();
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.kategori.store') }}');
        $('#kategoriModalTitle').text('Tambah Kategori');
        $('#warnaPreview').css('background', $('input[name="warna"]').val() || '#007bff');
        showDialog(modal);
        setTimeout(()=> $('[name=nama]').focus(), 120);
    };

    // open edit
    window.editKategori = function (id) {
        $.get(`{{ url('admin/kategori') }}/${id}`)
            .done(function (data) {
                form[0].reset();
                $('[name=nama]').val(data.nama);
                $('[name=tipe]').val(data.tipe);
                $('[name=warna]').val(data.warna || '#007bff');
                $('#warnaPreview').css('background', data.warna || '#007bff');
                $('#method').val('PUT');
                form.attr('action', `{{ url('admin/kategori') }}/${id}`);
                $('#kategoriModalTitle').text(data.nama ? `Edit: ${data.nama}` : 'Edit Kategori');
                showDialog(modal);
                setTimeout(()=> $('[name=nama]').focus(), 120);
            })
            .fail(function () {
                Swal.fire('Error', 'Gagal memuat data.', 'error');
            });
    };

    // delete
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

    // submit form
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
                closeDialog(modal);
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

@endsection
