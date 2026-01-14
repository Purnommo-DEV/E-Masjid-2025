@extends('masjid.master')
@section('title', 'Banner Halaman Utama')

@push('style')
<style>
    .card-wrapper {
        max-width: 1200px;
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
    .card-body { padding: 1.25rem 1.5rem; }

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

    .btn-circle-ico {
        display:inline-flex;align-items:center;justify-content:center;
        width:36px;height:36px;border-radius:8px;
        transition: transform .12s ease, box-shadow .12s;
    }
    .btn-circle-ico:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2,6,23,0.06); }

    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px);
    }
    dialog.modal { border: none; padding: 0; }

    .banner-modal-box{
        width: 100%;
        max-width: 1000px;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 18px 40px rgba(15,23,42,0.24);
        background: #f9fafb;
        display:flex;
        flex-direction:column;
        max-height: 90vh;
    }
    .banner-modal-header{
        padding: 1rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        background: #fff;
    }
    .banner-modal-body{
        padding: 1rem 1.5rem 1.5rem;
        overflow-y: auto;
    }
    .banner-modal-footer{
        padding: .75rem 1.5rem;
        background: #fff;
        border-top: 1px solid #e5e7eb;
        position: sticky;
        bottom: 0;
        z-index: 10;
    }
    .input-plain{
        border:1px solid #e5e7eb;border-radius:8px;padding:.5rem .75rem;width:100%;font-size:.875rem;
    }
    .input-plain.error{
        border-color:#ef4444;
        background:#fef2f2;
    }
    .error-text{
        font-size:.75rem;
        color:#ef4444;
    }
    .thumb-banner{
        width:120px;height:70px;border-radius:.75rem;object-fit:cover;
    }
    @media(max-width:768px){
        .banner-modal-body{padding: .75rem 1rem 1rem;}
        .banner-modal-header,.banner-modal-footer{padding: .75rem 1rem;}
    }
</style>
@endpush

@section('content')

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Banner</h3>
            <p class="subtitle">Banner yang muncul di halaman utama (slider 3 kartu).</p>
        </div>
        <button type="button" class="header-action" onclick="openBannerModal()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="text-sm font-semibold">Tambah Banner</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="bannerTable" class="table table-zebra w-full text-sm">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th>Gambar</th>
                        <th>Judul</th>
                        <th>Catatan</th>
                        <th>Label</th>
                        <th>Urutan</th>
                        <th>Aktif</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- MODAL BANNER --}}
<dialog id="bannerModal" class="modal">
    <div class="banner-modal-box">
        <form id="bannerForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="bannerMethod" value="POST">
            <input type="hidden" id="bannerId">

            <div class="banner-modal-header flex items-center justify-between">
                <div>
                    <h3 id="bannerModalTitle" class="text-base sm:text-lg font-semibold text-slate-900">
                        Tambah Banner
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Isi informasi banner yang akan tampil di halaman utama.
                    </p>
                </div>
                <button type="button"
                        class="text-slate-400 hover:text-slate-600"
                        onclick="closeBannerModal()">
                    ✕
                </button>
            </div>

            <div class="banner-modal-body">
                <div class="grid md:grid-cols-2 gap-4">
                    {{-- Kolom kiri --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Judul Banner <span class="text-red-500">*</span></label>
                            <input type="text" name="judul" class="input-plain" placeholder="Judul utama banner">
                            <small class="error-text" data-error="judul"></small>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold mb-1">Subjudul / Tagline</label>
                            <input type="text" name="subjudul" class="input-plain" placeholder="Optional">
                            <small class="error-text" data-error="subjudul"></small>
                        </div>

                            <div>
                                <label class="block text-xs font-semibold mb-1">Catatan Singkat</label>
                                <input type="text" name="catatan_singkat" class="input-plain"
                                       placeholder="Contoh: Terbuka untuk umum, jamaah putra & putri">
                                <small class="error-text" data-error="catatan_singkat"></small>
                            </div>

                            <div class="grid grid-cols-[minmax(0,1.2fr)_minmax(0,.8fr)] gap-3">
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Label Tombol</label>
                                    <input type="text" name="label_tombol" class="input-plain" placeholder="Lihat Detail">
                                    <small class="error-text" data-error="label_tombol"></small>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold mb-1">Urutan</label>
                                    <input type="number" name="urutan" class="input-plain" placeholder="0,1,2,...">
                                    <small class="error-text" data-error="urutan"></small>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold mb-1">Link / URL Tujuan</label>
                                <input type="text" name="url_tujuan" class="input-plain"
                                       placeholder="Contoh: http://127.0.0.1:8000/acara/slug-kajian">
                                <small class="error-text" data-error="url_tujuan"></small>
                                <p class="text-[10px] text-slate-400 mt-1">
                                    Bisa diisi URL detail kajian, donasi, atau halaman lain. Kosongkan jika tidak ada.
                                </p>
                            </div>

                            <div class="flex items-center gap-2 mt-2">
                                <input type="checkbox" id="is_active" name="is_active" class="toggle toggle-sm toggle-success" checked>
                                <label for="is_active" class="text-xs text-slate-700">Banner Aktif</label>
                            </div>
                        </div>

                    {{-- Kolom kanan --}}
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1">Gambar Banner</label>
                            <input type="file" name="gambar" id="gambarInput" class="input-plain" accept="image/*">
                            <small class="error-text" data-error="gambar"></small>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Rekomendasi rasio 16:9. Format: JPG/PNG/WEBP, maks 2 MB.
                            </p>
                        </div>

                        <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-50">
                            <img id="bannerPreview" src="{{ asset('images/masjid-banner.jpg') }}"
                                 alt="Preview"
                                 class="w-full h-40 object-cover">
                        </div>

                        <div class="mt-2">
                            <label class="block text-xs font-semibold mb-1">Deskripsi Detail (TinyMCE)</label>
                            <textarea id="banner_deskripsi" name="deskripsi"></textarea>
                            <small class="error-text" data-error="deskripsi"></small>
                            <p class="text-[10px] text-slate-400 mt-1">
                                Deskripsi ini akan ditampilkan saat tombol detail diklik (misalnya dalam modal di halaman depan).
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="banner-modal-footer flex justify-end gap-2">
                <button type="button"
                        class="px-4 py-2 rounded-full border text-sm text-slate-700"
                        onclick="closeBannerModal()">
                    Batal
                </button>
                <button type="submit"
                        class="px-5 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-sm text-white font-semibold">
                    Simpan
                </button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script src="https://cdn.jsdelivr.net/npm/tinymce@5.10.7/tinymce.min.js"></script>

<script>
    let bannerTable;
    const bannerModal = document.getElementById('bannerModal');
    const bannerForm  = document.getElementById('bannerForm');

    function showDialog(d){
        if (typeof d.showModal === 'function') d.showModal();
        else d.classList.add('modal-open');
    }
    function closeDialog(d){
        if (typeof d.close === 'function') d.close();
        else d.classList.remove('modal-open');
    }

    function resetErrors() {
        document.querySelectorAll('#bannerForm .error-text').forEach(el => el.textContent = '');
        document.querySelectorAll('#bannerForm .input-plain').forEach(el => el.classList.remove('error'));
    }

    window.openBannerModal = function() {
        resetErrors();
        bannerForm.reset();
        document.getElementById('bannerMethod').value = 'POST';
        document.getElementById('bannerId').value = '';
        document.getElementById('bannerModalTitle').textContent = 'Tambah Banner';
        document.getElementById('is_active').checked = true;
        document.getElementById('bannerPreview').src = "{{ asset('images/masjid-banner.jpg') }}";

        if (tinymce.get('banner_deskripsi')) {
            tinymce.get('banner_deskripsi').setContent('');
        }

        bannerForm.action = "{{ route('admin.banner.store') }}";
        showDialog(bannerModal);
    };

    window.closeBannerModal = function(){
        closeDialog(bannerModal);
    };

    // preview gambar
    document.getElementById('gambarInput').addEventListener('change', function(e){
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = ev => {
            document.getElementById('bannerPreview').src = ev.target.result;
        };
        reader.readAsDataURL(file);
    });

    // edit
    window.editBanner = function(id){
        resetErrors();
        $.get(`{{ url('admin/banner') }}/${id}`)
            .done(function(res){
                document.getElementById('bannerMethod').value = 'PUT';
                document.getElementById('bannerId').value     = res.id;
                bannerForm.action = `{{ url('admin/banner') }}/${id}`;
                document.getElementById('bannerModalTitle').textContent = 'Edit Banner';

                bannerForm.judul.value           = res.judul || '';
                bannerForm.subjudul.value        = res.subjudul || '';
                bannerForm.catatan_singkat.value = res.catatan_singkat || '';
                bannerForm.label_tombol.value    = res.label_tombol || '';
                bannerForm.urutan.value          = res.urutan ?? 0;
                bannerForm.url_tujuan.value      = res.url_tujuan || '';
                document.getElementById('is_active').checked = !!res.is_active;

                document.getElementById('bannerPreview').src = res.gambar_url || "{{ asset('images/masjid-banner.jpg') }}";

                if (tinymce.get('banner_deskripsi')) {
                    tinymce.get('banner_deskripsi').setContent(res.deskripsi || '');
                }

                showDialog(bannerModal);
            })
            .fail(() => Swal.fire('Error', 'Gagal memuat data banner.', 'error'));
    };

    // delete
    window.deleteBanner = function(id){
        Swal.fire({
            title: 'Hapus banner?',
            text: 'Banner akan dihapus dan tidak tampil lagi di halaman utama.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `{{ url('admin/banner') }}/${id}`,
                type: 'DELETE',
                data: {_token: '{{ csrf_token() }}'},
                success: res => {
                    bannerTable.ajax.reload();
                    Swal.fire('Berhasil', res.message, 'success');
                },
                error: xhr => {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Gagal menghapus banner.', 'error');
                }
            })
        });
    };

    // submit
    bannerForm.addEventListener('submit', function(e){
        e.preventDefault();
        resetErrors();

        const method = document.getElementById('bannerMethod').value;
        const formData = new FormData(bannerForm);

        // masukkan content TinyMCE
        if (tinymce.get('banner_deskripsi')) {
            formData.set('deskripsi', tinymce.get('banner_deskripsi').getContent());
        }

        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: bannerForm.action,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: res => {
                closeBannerModal();
                bannerTable.ajax.reload();
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: xhr => {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors || {};
                    Object.keys(errors).forEach(field => {
                        const msg = errors[field][0];
                        const errorElem = document.querySelector(`[data-error="${field}"]`);
                        if (errorElem) errorElem.textContent = msg;

                        const input = bannerForm.querySelector(`[name="${field}"]`);
                        if (input && input.classList.contains('input-plain')) {
                            input.classList.add('error');
                        }
                    });
                } else {
                    Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                }
            }
        });
    });

    // init datatable + tinymce
    $(function(){
        bannerTable = $('#bannerTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.banner.data') }}',
            columns: [
                { data: 'gambar', orderable: false, searchable: false },
                { data: 'judul' },
                { data: 'catatan_singkat', defaultContent:'-' },
                { data: 'label_tombol', defaultContent:'Lihat Detail' },
                { data: 'urutan' },
                {
                    data: 'is_active',
                    render: function(v){
                        return v
                            ? '<span class="badge bg-emerald-100 text-emerald-700 text-xs px-2 py-1 rounded-full">Aktif</span>'
                            : '<span class="badge bg-slate-100 text-slate-500 text-xs px-2 py-1 rounded-full">Nonaktif</span>';
                    }
                },
                {
                    data: null,
                    orderable:false,
                    className:'text-center',
                    render: function(row){
                        return `
                            <div class="inline-flex gap-2">
                                <button class="btn-circle-ico bg-yellow-50 text-yellow-700" type="button"
                                        onclick="editBanner(${row.id})">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z"
                                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <button class="btn-circle-ico bg-red-50 text-red-700" type="button"
                                        onclick="deleteBanner(${row.id})">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none">
                                        <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6"
                                              stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                    }
                }
            ]
        });

        tinymce.init({
            selector: '#banner_deskripsi',
            height: 220,
            menubar: false,
            branding:false,
            plugins: 'link lists',
            toolbar: 'bold italic underline | bullist numlist | link | undo redo',
            default_link_target: '_blank'
        });

        if (bannerModal) {
            bannerModal.addEventListener('cancel', function(e){
                e.preventDefault();
                closeBannerModal();
            });
        }
    });
</script>
@endpush
