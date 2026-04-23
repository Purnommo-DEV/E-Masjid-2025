@extends('masjid.master')
@section('title', 'Manajemen Galeri')
@section('content')

@push('style')
<style>
    /* --- Reuse reference card styles --- */
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
        display:flex;
        align-items:center;
        justify-content:space-between;
    }
    .card-header .title { margin:0; font-size:1.125rem; font-weight:700; }
    .card-header .subtitle { margin:0; opacity:.95; font-size:.95rem; }

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
    td.thumb { max-width: 120px; }

    /* preview */
    .preview-card {
        background: #ffffff;
        border: 1px solid #eee;
        border-radius: 12px;
        padding: 12px;
        margin-bottom: 10px;
    }
    .preview-title { font-weight:600; font-size:14px; margin-bottom:8px; }
    .preview-image-box { position:relative; border-radius:10px; overflow:hidden; display:inline-block; }
    .preview-image-box img { width:100%; max-width:220px; height:80px; object-fit:cover; border-radius:8px; }

    /* dialog/modal styles */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; z-index: 1050; }
    .modal-panel { 
        border-radius: 12px; 
        box-shadow: 0 18px 40px rgba(2,6,23,0.12); 
        overflow: hidden; 
        background: white;
        position: relative;
    }

    /* validation */
    .is-invalid { border-color: #dc3545 !important; background: #fef2f2 !important; }
    .invalid-feedback { display:block; color:#dc3545; font-size:.75rem; margin-top: 0.25rem; }

    /* upload area */
    .upload-area { border-radius:8px; transition: all .12s; }
    .upload-area.dragover { border-color: #059669 !important; background: #f0fdf4; }

    .btn-circle-ico { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; transition: transform .12s ease; }
    .btn-circle-ico:hover { transform: translateY(-2px); }

    /* SweetAlert z-index fix */
    .swal2-container {
        z-index: 99999 !important;
    }
    .swal2-backdrop {
        z-index: 99998 !important;
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
        z-index: 60;
        backdrop-filter: blur(3px);
        border-radius: 12px;
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

    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
    }
    .delete-preview-btn {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 20px;
        height: 20px;
        background: #ef4444;
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        cursor: pointer;
        border: none;
        z-index: 10;
    }

    .delete-preview-btn:hover {
        background: #dc2626;
    }

    .preview-image-box {
        position: relative;
        display: inline-block;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Galeri</h3>
            <p class="subtitle">Tambah foto / video, atur kategori dan status tampil</p>
        </div>
        <button type="button" class="header-action" onclick="addGaleri()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Galeri</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="galeriTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Thumbnail</th>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<dialog id="galeriModal" class="modal" aria-labelledby="galeriModalTitle" role="dialog" aria-modal="true">
  <div class="modal-panel w-11/12 max-w-3xl max-h-[90vh] flex flex-col relative">
    <form id="galeriForm" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-4 text-sm bg-white rounded-t-2xl rounded-b-none shadow-xl border border-emerald-50" method="POST" novalidate>
      @csrf
      <input type="hidden" id="method" value="POST">

      <!-- Header -->
      <div class="flex items-start justify-between gap-3">
        <div>
          <h3 id="galeriModalTitle" class="text-lg font-extrabold text-emerald-800">📁 Form Galeri</h3>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" id="closeGaleriModalBtn" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-600 hover:bg-gray-100 text-sm" aria-label="Tutup">
            ✕
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="grid grid-cols-1 gap-3 mt-3">
        <!-- Judul + Tipe -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div>
            <label class="block text-xs font-medium text-emerald-700" for="judul">Judul <span class="text-red-500">*</span></label>
            <input id="judul" type="text" name="judul" class="w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" required>
            <div class="invalid-feedback"></div>
          </div>

          <div>
            <label class="block text-xs font-medium text-emerald-700" for="tipe">Tipe <span class="text-red-500">*</span></label>
            <select id="tipe" name="tipe" class="form-select w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" required>
              <option value="foto">Foto</option>
              <option value="video">Video</option>
            </select>
            <div class="invalid-feedback"></div>
          </div>
        </div>

        <!-- Foto Section -->
        <div id="fotoSection" class="mb-1">
          <label class="block text-xs font-medium text-emerald-700 mb-1">Foto (pilih banyak sekaligus)</label>

            <label for="uploadFoto" class="upload-area cursor-pointer block rounded-lg border-2 border-dashed border-emerald-200 p-3 text-center hover:bg-emerald-50 transition text-sm">
                <div class="flex items-center justify-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M3 15a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M8 11l4-4 4 4" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <div class="text-sm text-emerald-700 font-medium">Klik atau seret foto ke sini</div>
                </div>
                <div class="text-xs text-gray-400 mt-1">
                    JPG / PNG / WebP • Maksimal 2MB per foto • Rasio ideal 16:9
                    <span class="text-emerald-600 block mt-0.5">✨ File akan otomatis dikompres ke WebP</span>
                </div>
            </label>

            <input type="file" name="fotos[]" id="uploadFoto" class="hidden" multiple accept="image/*">
            <div class="invalid-feedback" data-error="fotos"></div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-2 mt-2">
            <!-- Di dalam preview-card Foto Lama -->
            <div class="preview-card">
                <div class="preview-title text-sm flex justify-between items-center">
                    <span>📁 Foto Lama</span>
                    <button type="button" id="hapusSemuaFotoLama" class="text-xs text-red-500 hover:text-red-700">Hapus Semua</button>
                </div>
                <div id="daftarFotoLama" class="text-sm text-gray-500">Tidak ada foto lama.</div>
            </div>

            <div class="preview-card">
              <div class="preview-title text-sm">🆕 Foto Baru (yang diupload)</div>
              <div id="previewFotos" class="text-sm text-gray-500">Belum ada foto baru.</div>
            </div>
          </div>

          <input type="hidden" name="deleted_fotos" id="deletedFotos" value="">
        </div>

        <!-- Video Section -->
        <div id="videoSection" style="display: none;" class="mb-1">
          <label class="block text-xs font-medium text-emerald-700" for="url_video">URL YouTube</label>
          <input id="url_video" type="url" name="url_video" class="w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" placeholder="https://youtube.com/watch?v=...">
          <div class="invalid-feedback"></div>
        </div>

        <!-- Keterangan -->
        <div>
          <label class="block text-xs font-medium text-emerald-700" for="keterangan">Keterangan</label>
          <textarea id="keterangan" name="keterangan" class="w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" rows="2"></textarea>
          <div class="invalid-feedback"></div>
        </div>

        <!-- Kategori -->
        <div>
          <label class="block text-xs font-medium text-emerald-700" for="kategori">Kategori</label>
          <select id="kategori" name="kategori_id[]" class="form-select select2 w-full rounded-md border border-emerald-50 px-2 py-1 text-sm shadow-sm" multiple>
            @foreach(\App\Models\Kategori::where('tipe', 'galeri')->get() as $k)
              <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
          </select>
          <div class="invalid-feedback"></div>
        </div>

        <!-- Publikasikan -->
        <div class="flex items-center gap-2">
          <input type="checkbox" name="is_published" id="isPublished" class="rounded text-emerald-600 focus:ring-emerald-400">
          <label for="isPublished" class="text-sm text-gray-700">Publikasikan</label>
        </div>
      </div>
    </form>

    <!-- Sticky Footer -->
    <div class="sticky bottom-0 z-30 bg-white/95 backdrop-blur-sm border-t border-emerald-50 px-4 py-3 flex items-center justify-end gap-3 rounded-b-2xl">
      <button type="button" id="cancelGaleriBtn" class="px-3 py-1 rounded-md border border-gray-200 hover:bg-gray-50 text-sm">
        Batal
      </button>
      <button type="submit" id="submitBtn" form="galeriForm" class="inline-flex items-center gap-2 px-4 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow">
        Simpan
      </button>
    </div>
    
    <!-- Loader overlay -->
    <div id="modalLoader" class="modal-loading-overlay">
      <div class="text-center">
        <div class="spinner mx-auto mb-3"></div>
        <p class="text-emerald-700 font-medium">Menyimpan data...</p>
      </div>
    </div>
  </div>
</dialog>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
    let table = null;
    let isSubmitting = false;
    const modal = document.getElementById('galeriModal');
    const $modal = $('#galeriModal');
    const form = $('#galeriForm');
    let uploadedFiles = [];

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
        $('.form-control, .select2, input, select, textarea').removeClass('is-invalid');
    }

    // dialog helpers
    function showDialog(d) {
        try { 
            if (typeof d.showModal === 'function') d.showModal(); 
            else d.classList.add('modal-open'); 
        }
        catch(e){ d.classList.add('modal-open'); }
    }
    
    function closeDialog(d) {
        try { 
            if (typeof d.close === 'function') d.close(); 
            else d.classList.remove('modal-open'); 
        }
        catch(e){ d.classList.remove('modal-open'); }
    }

    $(function () {
        table = $('#galeriTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.galeri.data') }}',
            columns: [
                { data: 'thumbnail', orderable: false, className: 'thumb' },
                { data: 'judul' },
                { data: 'tipe', orderable: false },
                { data: 'kategoris', orderable: false },
                { data: 'status', orderable: false },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: d => `
                        <div class="inline-flex gap-2">
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-yellow-50 hover:bg-yellow-100 text-yellow-700 shadow-sm" title="Edit" onclick="editGaleri(${d.id})">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-700 shadow-sm" title="Hapus" onclick="deleteGaleri(${d.id})">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    `
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

        // init select2
        if ($.fn.select2) $('.select2').select2({ width: '100%', dropdownParent: $modal });

        // modal control bindings
        $('#closeGaleriModalBtn').on('click', () => closeDialog(modal));
        $('#cancelGaleriBtn').on('click', () => closeDialog(modal));
        if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });
    });

    function addFilesToUploadList(fileList) {
        const newFiles = Array.from(fileList);
        newFiles.forEach(f => {
            const key = f.name + '_' + f.size + '_' + f.lastModified;
            const exists = uploadedFiles.some(uf => (uf.name + '_' + uf.size + '_' + uf.lastModified) === key);
            if (!exists) uploadedFiles.push(f);
        });
        renderPreview();
    }

    // Update fungsi upload handling
    $(document).on('change', '#uploadFoto', function(e) {
        const files = e.target.files;
        if (!files || !files.length) return;

        const toAdd = [];
        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            
            // Validasi ukuran 2MB
            if (file.size > 2 * 1024 * 1024) {
                showAlertOnTop('error', 'File terlalu besar!', 
                    `File ${file.name} (${(file.size / 1024 / 1024).toFixed(1)}MB) melebihi batas 2MB.`);
                continue;
            }
            
            if (!file.type.startsWith('image/')) {
                showAlertOnTop('error', 'File tidak valid!', 'Hanya file gambar diperbolehkan.');
                continue;
            }
            toAdd.push(file);
        }

        if (toAdd.length) addFilesToUploadList(toAdd);
        try { this.value = ''; } catch(err) { }
    });

    function renderPreview() {
        const preview = $('#previewFotos');
        preview.empty();
        if (!uploadedFiles.length) { 
            preview.html('<div class="text-muted small">Belum ada foto baru.</div>'); 
            return; 
        }

        uploadedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const html = `
                    <div class="inline-block mr-2 mb-1" style="width:100px">
                        <div class="preview-image-box">
                            <img src="${ev.target.result}" alt="preview" style="width:100px;height:70px;object-fit:cover;">
                            <button type="button" class="delete-preview-btn bg-red-500 text-white rounded-full w-5 h-5 text-xs absolute top-1 right-1" onclick="hapusFotoBaru(${index})">✕</button>
                        </div>
                    </div>`;
                preview.append(html);
            };
            reader.readAsDataURL(file);
        });
    }

    $('#hapusSemuaFotoLama').on('click', function() {
        const wasOpen = modal && modal.open;
        if (wasOpen) modal.close();
        
        Swal.fire({
            title: 'Yakin?',
            text: 'Hapus semua foto?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus Semua',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
        }).then(result => {
            if (result.isConfirmed) {
                // Kumpulkan semua URL foto lama
                const allUrls = [];
                $('#daftarFotoLama .preview-image-box').each(function() {
                    const parent = $(this).closest('[data-url]');
                    if (parent.length) {
                        allUrls.push(parent.data('url'));
                    }
                });
                
                // Hapus semua dari DOM
                $('#daftarFotoLama').empty().html('<div class="text-muted small">Tidak ada foto lama.</div>');
                
                // Simpan ke deleted_fotos
                let deleted = $('#deletedFotos').val();
                allUrls.forEach(url => {
                    if (deleted) deleted += ',' + url;
                    else deleted = url;
                });
                $('#deletedFotos').val(deleted);
            }
            if (wasOpen && modal) modal.showModal();
        });
    });

    window.hapusFotoBaru = function(index) {
        uploadedFiles.splice(index, 1);
        renderPreview();
    }

    window.hapusFotoLama = function(element, url) {
        const wasOpen = modal && modal.open;
        if (wasOpen) modal.close();

        Swal.fire({
            title: 'Yakin?',
            text: 'Hapus foto ini?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal',
            allowOutsideClick: false,
        }).then(result => {
            if (result.isConfirmed) {
                // Hapus element dari DOM
                $(element).closest('[data-url]').remove();
                
                // Simpan URL ke deleted_fotos
                let deleted = $('#deletedFotos').val();
                if (deleted) deleted += ',' + url;
                else deleted = url;
                $('#deletedFotos').val(deleted);
                
                // Jika tidak ada foto lama, tampilkan pesan
                if ($('#daftarFotoLama .preview-image-box').length === 0) {
                    $('#daftarFotoLama').html('<div class="text-muted small">Tidak ada foto lama.</div>');
                }
            }
            if (wasOpen && modal) modal.showModal();
        });
    }
    // drag & drop area
    $('.upload-area')
        .on('dragover dragenter', function (e) { 
            e.preventDefault(); 
            if (e.originalEvent) e.originalEvent.dataTransfer.dropEffect = 'copy'; 
            $(this).addClass('dragover'); 
        })
        .on('dragleave dragend', function (e) { 
            e.preventDefault(); 
            $(this).removeClass('dragover'); 
        })
        .on('drop', function (e) {
            e.preventDefault();
            $(this).removeClass('dragover');

            const dt = e.originalEvent && e.originalEvent.dataTransfer;
            if (!dt) return;

            const files = dt.files;
            if (!files || !files.length) return;

            const toAdd = [];
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Ubah dari 10MB ke 2MB
                if (file.size > 2 * 1024 * 1024) {  // 2MB
                    showAlertOnTop('warning', 'Ukuran terlalu besar!', `File ${file.name} melebihi batas 2MB.`);
                    continue;
                }
                if (!file.type.startsWith('image/')) {
                    showAlertOnTop('error', 'File tidak valid!', 'Hanya file gambar diperbolehkan.');
                    continue;
                }
                toAdd.push(file);
            }
            if (toAdd.length) addFilesToUploadList(toAdd);
    });

    window.addGaleri = function () {
        resetErrors();
        uploadedFiles = [];
        form[0].reset();
        $('#previewFotos').html('<div class="text-muted small">Belum ada foto baru.</div>');
        $('#daftarFotoLama').empty().html('<div class="text-muted small">Tidak ada foto lama.</div>');
        $('#deletedFotos').val('');
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.galeri.store') }}');
        $('#fotoSection').show();
        $('#videoSection').hide();
        $('#galeriModalTitle').text('Tambah Galeri');
        if ($.fn.select2) $('.select2').val(null).trigger('change');
        showDialog(modal);
        setTimeout(()=> $('[name=judul]').focus(), 120);
    };

    window.editGaleri = function (id) {
        resetErrors();
        uploadedFiles = [];
        $('#previewFotos').html('<div class="text-muted small">Belum ada foto baru.</div>');
        $('#daftarFotoLama').empty();
        $('#deletedFotos').val('');
        
        $.get(`{{ url('admin/galeri') }}/${id}`)
        .done(function (data) {
            form[0].reset();
            $('[name=judul]').val(data.judul);
            $('[name=keterangan]').val(data.keterangan);
            $('[name=tipe]').val(data.tipe).trigger('change');
            if ($.fn.select2) $('[name="kategori_id[]"]').val(data.kategori_ids || []).trigger('change');
            else $('[name="kategori_id[]"]').val(data.kategori_ids || []);
            $('[name=is_published]').prop('checked', !!data.is_published);

            if (data.tipe === 'video') {
                $('[name=url_video]').val(data.url_video);
                $('#fotoSection').hide();
                $('#videoSection').show();
            } else {
                $('#fotoSection').show();
                $('#videoSection').hide();
                if (Array.isArray(data.fotos) && data.fotos.length) {
                    data.fotos.forEach(foto => {
                        const html = `
                        <div class="inline-block mr-2 mb-1" data-url="${foto.url}" style="width:100px">
                            <div class="preview-image-box">
                                <a href="${foto.url}" data-lightbox="galeri-${id}">
                                    <img src="${foto.url}" alt="foto" style="width:100px;height:70px;object-fit:cover;">
                                </a>
                                <button type="button" class="delete-preview-btn" onclick="hapusFotoLama(this, '${foto.url}')">✕</button>
                            </div>
                        </div>`;
                        $('#daftarFotoLama').append(html);
                    });
                } else {
                    $('#daftarFotoLama').html('<div class="text-muted small">Tidak ada foto lama.</div>');
                }
            }

            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/galeri') }}/${id}`);
            $('#galeriModalTitle').text(data.judul ? `Edit: ${data.judul}` : 'Edit Galeri');
            showDialog(modal);
            setTimeout(()=> $('[name=judul]').focus(), 120);
        })
        .fail(function () { 
            showAlertOnTop('error', 'Error', 'Gagal memuat data galeri.'); 
        });
    };

    window.deleteGaleri = function (id) {
        const wasOpen = modal && modal.open;
        if (wasOpen) {
            modal.close();
        }
        
        Swal.fire({
            title: 'Yakin?', 
            text: 'Galeri akan dihapus!', 
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
                    url: `{{ url('admin/galeri') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: (res) => { 
                        table.ajax.reload(); 
                        Swal.fire('Berhasil', 'Galeri dihapus.', 'success'); 
                    },
                    error: (xhr) => { 
                        showAlertOnTop('error', 'Error', xhr.responseJSON?.message || 'Gagal menghapus galeri.'); 
                    }
                });
            } else if (wasOpen && modal) {
                modal.showModal();
            }
        });
    };

    // toggle sections
    $(document).on('change', '[name=tipe]', function () {
        $('#fotoSection').toggle(this.value === 'foto');
        $('#videoSection').toggle(this.value === 'video');
    });

    // submit dengan loader
    form.on('submit', function (e) {
        e.preventDefault();
        
        if (isSubmitting) return;
        
        resetErrors();
        
        const submitBtn = $('#submitBtn');
        const originalBtnText = submitBtn.html();
        
        isSubmitting = true;
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="btn-loader"><span class="spinner-border"></span> Menyimpan...</span>');
        $('#modalLoader').addClass('active');
        
        let formData = new FormData(this);
        formData.delete('fotos[]');
        uploadedFiles.forEach(file => formData.append('fotos[]', file));
        let method = $('#method').val();
        if (method === 'PUT') formData.append('_method', 'PUT');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: res => {
                uploadedFiles = [];
                closeDialog(modal);
                table.ajax.reload();
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message,
                    timer: 1500,
                    showConfirmButton: false
                });
            },
            error: xhr => {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                let errors = xhr.responseJSON?.errors;
                if (errors) {
                    $.each(errors, function (key, messages) {
                        let input = $(`[name="${key}"]`);
                        if (!input.length) input = $(`[name="${key}[]"]`);
                        if (input.length) {
                            input.addClass('is-invalid');
                            const errorDiv = input.siblings('.invalid-feedback');
                            if (errorDiv.length) {
                                errorDiv.text(messages[0] || 'Error');
                            } else {
                                input.after('<div class="invalid-feedback">' + (messages[0] || 'Error') + '</div>');
                            }
                        } else {
                            $(`[data-error="${key}"]`).text(messages[0] || 'Error');
                        }
                    });
                    
                    // Scroll ke error pertama
                    const firstError = $('.is-invalid').first();
                    if (firstError.length) {
                        firstError[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    let msg = xhr.responseJSON?.message || 'Terjadi kesalahan!';
                    showAlertOnTop('error', 'Error!', msg);
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