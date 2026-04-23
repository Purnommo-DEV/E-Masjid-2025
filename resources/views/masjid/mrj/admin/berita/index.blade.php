@extends('masjid.master')
@section('title', 'Manajemen Berita')

@section('content')
@push('style')
<style>
    /* Modern card */
    .card-wrapper {
        max-width: 1100px;
        margin: 1.5rem auto;
        border-radius: 1rem;
        overflow: hidden;
        background: white;
        box-shadow: 0 12px 30px rgba(2,6,23,0.08);
        border: 1px solid rgba(15,23,42,0.04);
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(90deg, #059669 0%, #10b981 100%);
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }
    .card-header .title { font-size: 1.125rem; font-weight: 800; letter-spacing: 0.2px; }
    .card-header .subtitle { font-size: 0.95rem; opacity: 0.95; }

    .header-action {
        display: inline-flex;
        align-items: center;
        gap: .6rem;
        padding: .5rem .9rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 8px 24px rgba(4,120,87,0.05);
        transition: transform .12s ease;
    }
    .header-action:hover { transform: translateY(-3px); }

    dialog.modal { z-index: 1050; }
    dialog.modal::backdrop { background: rgba(15,23,42,0.55); backdrop-filter: blur(4px) saturate(1.02); }
    .modal-box {
        border-radius: 12px;
        box-shadow: 0 26px 60px rgba(2,6,23,0.16);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        position: relative;
    }

    .modal-form {
        padding: 1.5rem;
        flex: 1 1 auto;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .form-control {
        display: block;
        width: 100%;
        padding: .6rem .75rem;
        border: 1px solid rgba(15,23,42,0.08);
        border-radius: 8px;
        background: #fff;
    }
    .form-control:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(16,185,129,0.15);
        border-color: #10b981;
    }
    .form-control.is-invalid {
        border-color: #ef4444;
        background: #fef2f2;
    }
    .invalid-feedback {
        font-size: 0.75rem;
        color: #ef4444;
        margin-top: 0.25rem;
        display: block;
    }

    .dropzone {
        border: 2px dashed rgba(15,23,42,0.1);
        padding: 1.25rem;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .dropzone:hover { background: #f8fafc; border-color: #10b981; }

    .preview-card {
        border-radius: 8px;
        padding: .75rem;
        border: 1px solid rgba(15,23,42,0.06);
        background: #fafafa;
        max-height: 220px;
        overflow: auto;
    }

    .modal-footer {
        position: sticky;
        bottom: 0;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.5rem;
        background: white;
        border-top: 1px solid rgba(15,23,42,0.1);
        box-shadow: 0 -4px 12px rgba(0,0,0,0.04);
        z-index: 10;
    }

    .select2-container--open .select2-dropdown, .select2-container--open, .select2-container {
        z-index: 99999 !important;
    }
    
    /* SweetAlert z-index fix */
    .swal2-container, .swal2-popup {
        z-index: 100000 !important;
    }

    /* Loader Style untuk tombol */
    .btn-loader {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .spinner-border {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid #ffffff;
        border-right-color: transparent;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .modal-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.85);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50;
        backdrop-filter: blur(3px);
    }
    .modal-loading-overlay.active { display: flex; }
    .spinner {
        width: 40px;
        height: 40px;
        border: 5px solid #e5e7eb;
        border-top: 5px solid #10b981;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* Multiple preview */
    .preview-multi {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }
    .preview-item {
        position: relative;
        width: 120px;
        height: 90px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    }
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .delete-preview-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 24px;
        height: 24px;
        background: rgba(220, 53, 69, 0.9);
        color: white;
        border-radius: 50%;
        font-size: 14px;
        line-height: 24px;
        text-align: center;
        cursor: pointer;
        border: none;
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
    .delete-preview-btn:hover { background: #dc3545; }

    .thumb-img {
        max-width: 80px;
        border-radius: 6px;
        object-fit: cover;
        display: block;
        margin: 0 auto;
    }

    @media (max-width: 640px) {
        .modal-box { max-height: 92vh; }
        .modal-footer { padding: 0.9rem 1.25rem; }
        .card-header { padding: .9rem 1rem; }
    }
    .tox-tinymce-aux,
    .tox-dialog-wrap {
        z-index: 10000 !important;
    }
    .modal-box {
        overflow: visible !important;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Berita</h3>
            <p class="subtitle">Tambah, sunting, dan publikasikan berita masjid dengan cepat</p>
        </div>
        <button type="button" class="header-action" onclick="addBerita()" aria-label="Tambah berita">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Berita</span>
        </button>
    </div>

    <div class="p-4">
        <div class="overflow-x-auto">
            <table id="beritaTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Gambar</th>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<dialog id="beritaModal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl p-0 relative">
        <form id="beritaForm" class="modal-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="method" value="POST">
            <input type="hidden" name="deleted_gambar" id="deletedGambar" value="">

            <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-base-200">
                <h3 id="beritaModalTitle" class="text-xl font-bold text-emerald-800">Form Berita</h3>
                <button type="button" id="closeBeritaModalBtn" class="btn btn-ghost btn-sm btn-circle text-xl">✕</button>
            </div>

            <div class="px-6 py-5 space-y-6">
                <div>
                    <label class="block text-sm font-medium mb-1">Judul <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" class="form-control" required placeholder="Contoh: Bakti Sosial Minggu Ini">
                    <div class="invalid-feedback"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Kategori</label>
                    <select name="kategori_id[]" class="select select-bordered w-full select2" multiple>
                        @foreach(\App\Models\Kategori::where('tipe', 'berita')->get() as $k)
                            <option value="{{ $k->id }}">{{ $k->nama }}</option>
                        @endforeach
                    </select>
                    <div class="invalid-feedback"></div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Gambar Berita (bisa multiple)</label>
                    <label for="gambarInput" class="dropzone">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M3 15a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z" stroke="currentColor" stroke-width="1.25"/>
                            <path d="M8 11l4-4 4 4" stroke="currentColor" stroke-width="1.25"/>
                        </svg>
                        <div class="text-sm text-gray-600">Klik atau seret gambar (jpg/png, max 1MB per file)</div>
                    </label>
                    <input type="file" name="gambar[]" id="gambarInput" class="hidden" accept="image/*" multiple>
                    <div class="invalid-feedback" data-error="gambar"></div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                        <div id="kolomFotoLama" style="display:none;">
                            <div class="preview-card">
                                <div class="font-semibold mb-2">Gambar Lama</div>
                                <div id="daftarFotoLama" class="preview-multi"></div>
                            </div>
                        </div>
                        <div class="preview-card">
                            <div class="font-semibold mb-2">Preview Gambar Baru</div>
                            <div id="previewGambar" class="preview-multi text-sm text-gray-600">Belum ada gambar baru</div>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 mt-2">Bisa upload banyak sekaligus, gunakan rasio 16:9</p>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_published" class="checkbox checkbox-success" id="isPublishedCheck">
                    <label for="isPublishedCheck" class="text-sm">Publikasikan sekarang</label>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Isi Berita <span class="text-red-500">*</span></label>
                    <textarea name="isi" id="isiEditor" class="form-control w-full" rows="12" placeholder="Tulis isi berita lengkap di sini..."></textarea>
                    <div class="invalid-feedback" data-error="isi"></div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" id="cancelBeritaBtn" class="btn btn-outline px-4 py-2 rounded-md border text-slate-700 hover:bg-slate-50">Batal</button>
                <button type="submit" id="submitBtn" class="btn bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2 rounded-full font-semibold">Simpan</button>
            </div>

            <div id="modalLoader" class="modal-loading-overlay">
                <div class="text-center">
                    <div class="spinner mx-auto mb-3"></div>
                    <p class="text-emerald-700 font-medium">Menyimpan berita...</p>
                </div>
            </div>
        </form>
    </div>
</dialog>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@5.10.7/tinymce.min.js"></script>

<script>
    let table = null;
    let isSubmitting = false;
    let isTinyMceInitialized = false;
    const modal = document.getElementById('beritaModal');
    const $modal = $(modal);
    const form = $('#beritaForm');
    let uploadedFiles = [];

    let tinyObserver = null;

    // Helper function untuk SweetAlert yang selalu di atas modal
    function showAlertOnTop(icon, title, text, callback = null) {
        const wasOpen = modal && modal.open;
        if (wasOpen) {
            modal.close();
        }
        
        Swal.fire({
            icon: icon,
            title: title,
            text: text,
            confirmButtonText: 'OK',
            allowOutsideClick: false,
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '99999';
                }
            }
        }).then((result) => {
            if (wasOpen && modal) {
                modal.showModal();
            }
            if (callback) callback(result);
        });
    }

    // Reset error messages
    function resetErrors() {
        $('.invalid-feedback').text('');
        $('.form-control').removeClass('is-invalid');
    }

    // Fungsi untuk menginisialisasi atau mengembalikan TinyMCE
    function initTinyMCE() {
        if (tinymce.get('isiEditor')) {
            // Jika sudah ada, jangan inisialisasi ulang
            return;
        }
        
        tinymce.init({
            selector: '#isiEditor',
            height: 480,
            menubar: true,
            branding: false,
            plugins: 'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount',
            toolbar: 'undo redo | formatselect fontselect fontsizeselect | bold italic underline forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image table | removeformat code preview fullscreen',
            fontsize_formats: "8pt 9pt 10pt 11pt 12pt 14pt 16pt 18pt 20pt 24pt 28pt 32pt 36pt 48pt 60pt 72pt",
            font_formats: "Arial=arial,helvetica,sans-serif;Helvetica=helvetica,arial,sans-serif;Times New Roman=times new roman,times;Georgia=georgia,serif;Verdana=verdana,geneva,sans-serif;Tahoma=tahoma,arial,helvetica,sans-serif;Poppins=poppins,sans-serif;",
            content_style: "body { font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 1.7; } h2 { font-size: 22px; } h3 { font-size: 18px; }",
            setup: function (editor) {
                editor.on('init', function () {
                    const dialog = document.getElementById('beritaModal');
                    if (tinyObserver) {
                        tinyObserver.disconnect();
                        tinyObserver = null;
                    }
                    tinyObserver = new MutationObserver(() => {
                        document.querySelectorAll(
                            '.tox-tinymce-aux, .tox-dialog-wrap, .tox-menu, .tox-collection, .tox-silver-sink'
                        ).forEach(el => {
                            if (!dialog.contains(el)) {
                                dialog.appendChild(el);
                            }
                        });
                    });
                    tinyObserver.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                });
                editor.on('remove', function () {
                    if (tinyObserver) {
                        tinyObserver.disconnect();
                        tinyObserver = null;
                    }
                });
            }
        });
        isTinyMceInitialized = true;
    }

    // Fungsi untuk menghancurkan TinyMCE (opsional, untuk reset)
    function destroyTinyMCE() {
        if (tinymce.get('isiEditor')) {
            tinymce.get('isiEditor').remove();
        }
        isTinyMceInitialized = false;
    }

    function closeDialog(el) {
        try {
            // Jangan hancurkan TinyMCE saat modal ditutup
            // Biarkan saja tetap ada
            if (typeof el.close === 'function') el.close();
            else el.classList.remove('modal-open');
        } catch (e) {
            el.classList.remove('modal-open');
        }
    }

    function showDialog(el) {
        try {
            if (typeof el.showModal === 'function') el.showModal();
            else el.classList.add('modal-open');
            
            // Pastikan TinyMCE tetap ada setelah modal dibuka
            setTimeout(() => {
                if (tinymce.get('isiEditor')) {
                    // Refresh editor jika perlu
                    tinymce.get('isiEditor').focus();
                }
            }, 100);
        } catch (e) {
            el.classList.add('modal-open');
        }
    }

    $(function(){
        // Inisialisasi TinyMCE saat halaman load
        initTinyMCE();
        
        table = $('#beritaTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.berita.data') }}',
            columns: [
                {
                    data: 'gambar',
                    orderable: false,
                    className: 'thumb-column',
                    render: g => g || '<small class="text-muted">Tanpa Gambar</small>'
                },
                { data: 'judul' },
                { data: 'kategoris', orderable: false },
                { data: 'status', orderable: false },
                { data: 'tanggal' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: d => `
                        <div class="inline-flex gap-2">
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-yellow-50 hover:bg-yellow-100 text-yellow-700 shadow-sm" title="Edit" onclick="editBerita(${d.id})">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-700 shadow-sm" title="Hapus" onclick="deleteBerita(${d.id})">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    `
                }
            ],
            responsive: true,
            language: { search: 'Cari:', lengthMenu: 'Tampilkan _MENU_', info: 'Menampilkan _START_ - _END_ dari _TOTAL_', processing: 'Memuat...' }
        });

        $('.select2').each(function(){
            if ($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy');
            $(this).select2({ width: '100%', dropdownParent: $modal });
        });

        $('#closeBeritaModalBtn, #cancelBeritaBtn').on('click', () => closeDialog(modal));
        if (modal) modal.addEventListener('cancel', e => { e.preventDefault(); closeDialog(modal); });

        // Multiple preview baru
        $(document).on('change', '#gambarInput', function(e){
            const files = Array.from(e.target.files);
            if (!files.length) return;

            files.forEach(file => {
                if (file.size > 1048576) {
                    showAlertOnTop('warning', 'Ukuran terlalu besar!', 'Maksimal 1MB per file.');
                    return;
                }
                if (!file.type.startsWith('image/')) {
                    showAlertOnTop('error', 'File tidak valid!', 'Hanya gambar diperbolehkan.');
                    return;
                }
                uploadedFiles.push(file);
            });

            renderMultiplePreview();
            this.value = '';
        });

        function renderMultiplePreview() {
            const preview = $('#previewGambar');
            preview.empty();
            if (!uploadedFiles.length) {
                preview.html('<div class="text-sm text-gray-600">Belum ada gambar baru.</div>');
                return;
            }

            uploadedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = ev => {
                    const html = `
                        <div class="preview-item">
                            <img src="${ev.target.result}" alt="preview">
                            <button type="button" class="delete-preview-btn" onclick="hapusFotoBaru(${index})">✕</button>
                        </div>`;
                    preview.append(html);
                };
                reader.readAsDataURL(file);
            });
        }

        window.hapusFotoBaru = function(index) {
            uploadedFiles.splice(index, 1);
            renderMultiplePreview();
        };

        window.hapusFotoLama = function(url) {
            const wasOpen = modal && modal.open;
            if (wasOpen) {
                modal.close();
            }
            
            Swal.fire({
                title: 'Yakin?',
                text: 'Hapus gambar ini?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                allowOutsideClick: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) {
                        swalContainer.style.zIndex = '99999';
                    }
                }
            }).then(result => {
                if (result.isConfirmed) {
                    let deleted = $('#deletedGambar').val();
                    deleted = deleted ? deleted + ',' + url : url;
                    $('#deletedGambar').val(deleted);
                    $(`[data-url="${url}"]`).remove();
                    $(`.preview-item`).each(function() {
                        if ($(this).data('url') === url) {
                            $(this).remove();
                        }
                    });
                }
                if (wasOpen && modal) {
                    modal.showModal();
                }
            });
        };
    });

    window.addBerita = function(){
        resetErrors();
        uploadedFiles = [];
        form[0].reset();
        $('#gambarInput').val('');
        $('#deletedGambar').val('');
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.berita.store') }}');
        $('#beritaModalTitle').text('Tambah Berita');
        $('.select2').val(null).trigger('change');

        $('#previewGambar').html('<div class="text-sm text-gray-600">Belum ada gambar baru.</div>');
        $('#kolomFotoLama').hide();
        $('#daftarFotoLama').empty();

        // Reset konten TinyMCE tanpa menghancurkannya
        if (tinymce.get('isiEditor')) {
            tinymce.get('isiEditor').setContent('');
        }

        showDialog(modal);
        setTimeout(() => $('[name=judul]').focus(), 120);
    };

    window.editBerita = function(id) {
        resetErrors();
        uploadedFiles = [];
        form[0].reset();
        $('#gambarInput').val('');
        $('#deletedGambar').val('');
        $('#previewGambar').html('<div class="text-sm text-gray-600">Belum ada gambar baru</div>');
        $('#kolomFotoLama').show();
        $('#daftarFotoLama').html('<div class="text-sm text-gray-600 text-center py-4">Memuat data...</div>');

        showDialog(modal);

        $.get(`{{ url('admin/berita') }}/${id}`, function(data) {
            $('[name="judul"]').val(data.judul || '');
            
            // Set konten TinyMCE tanpa menghancurkan editor
            if (tinymce.get('isiEditor')) {
                tinymce.get('isiEditor').setContent(data.isi || '');
            }
            
            $('.select2').val(data.kategori_ids || []).trigger('change');
            $('[name="is_published"]').prop('checked', !!data.is_published);

            setTimeout(() => {
                const container = $('#daftarFotoLama');
                container.empty();

                if (Array.isArray(data.gambar) && data.gambar.length > 0) {
                    data.gambar.forEach(item => {
                        if (!item.url) return;
                        const imgSrc = item.url + '?v=' + Date.now();
                        const html = `
                            <div class="preview-item" data-url="${item.url}">
                                <img src="${imgSrc}" alt="Gambar lama" class="w-full h-full object-cover">
                                <button type="button" class="delete-preview-btn" onclick="hapusFotoLama('${item.url}')">✕</button>
                            </div>
                        `;
                        container.append(html);
                    });
                } else {
                    container.html('<div class="text-sm text-gray-600 text-center py-4">Tidak ada gambar lama.</div>');
                }
            }, 200);

            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/berita') }}/${id}`);
            $('#beritaModalTitle').text('Edit Berita: ' + (data.judul || 'ID ' + id));
        }).fail(function() {
            closeDialog(modal);
            showAlertOnTop('error', 'Error', 'Gagal memuat data berita.');
        });
    };
    
    window.deleteBerita = function(id){
        const wasOpen = modal && modal.open;
        if (wasOpen) {
            modal.close();
        }
        
        Swal.fire({
            title: 'Yakin?',
            text: 'Berita akan dihapus permanen!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
            didOpen: () => {
                const swalContainer = document.querySelector('.swal2-container');
                if (swalContainer) {
                    swalContainer.style.zIndex = '99999';
                }
            }
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/berita') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: (res) => {
                        table.ajax.reload();
                        Swal.fire('Berhasil', 'Berita dihapus.', 'success');
                    },
                    error: (xhr) => {
                        showAlertOnTop('error', 'Error', xhr.responseJSON?.message || 'Gagal menghapus.');
                    }
                });
            } else if (wasOpen && modal) {
                modal.showModal();
            }
        });
    };

    // Submit dengan loader
    form.on('submit', function(e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        resetErrors();
        
        const submitBtn = $('#submitBtn');
        const originalBtnText = submitBtn.html();
        
        isSubmitting = true;
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="btn-loader"><span class="spinner-border"></span> Menyimpan...</span>');
        $('#modalLoader').addClass('active');

        // Simpan konten TinyMCE ke textarea
        if (tinymce.get('isiEditor')) {
            tinymce.get('isiEditor').save();
        }

        let formData = new FormData(this);
        formData.delete('gambar[]');

        if (uploadedFiles.length > 0) {
            uploadedFiles.forEach((file, index) => {
                formData.append('gambar[]', file);
            });
        }

        const method = $('#method').val();
        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            timeout: 60000,
            success: function(res) {
                uploadedFiles = [];
                closeDialog(modal);
                table.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Data tersimpan',
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            error: function(xhr) {
                const json = xhr.responseJSON || {};
                if (json.errors) {
                    $('.invalid-feedback').text('');
                    $('.form-control').removeClass('is-invalid');
                    
                    Object.keys(json.errors).forEach(key => {
                        const input = $(`[name="${key}"], [name="${key}[]"]`);
                        const errorDiv = input.siblings('.invalid-feedback');
                        if (input.length) {
                            input.addClass('is-invalid');
                            if (errorDiv.length) {
                                errorDiv.text(json.errors[key][0]);
                            } else {
                                input.after(`<div class="invalid-feedback">${json.errors[key][0]}</div>`);
                            }
                        } else {
                            $(`[data-error="${key}"]`).text(json.errors[key][0]);
                        }
                    });
                    
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    showAlertOnTop('error', 'Gagal', json.message || 'Terjadi kesalahan server');
                }
            },
            complete: function() {
                isSubmitting = false;
                submitBtn.prop('disabled', false);
                submitBtn.html(originalBtnText);
                $('#modalLoader').removeClass('active');
            }
        });
    });
</script>
@endpush
@endsection