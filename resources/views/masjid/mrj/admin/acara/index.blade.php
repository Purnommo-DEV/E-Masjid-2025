@extends('masjid.master')
@section('title', 'Manajemen Acara')

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

    /* Pill button */
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

    .modal-box {
        border-radius: 12px;
        box-shadow: 0 26px 60px rgba(2,6,23,0.16);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 9991 !important;
    }

    /* Scrollable form content */
    .modal-form {
        padding: 1.5rem;
        flex: 1 1 auto;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Form controls */
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

    /* Dropzone */
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

    /* Preview card */
    .preview-card {
        border-radius: 8px;
        padding: .75rem;
        border: 1px solid rgba(15,23,42,0.06);
        background: #fafafa;
        max-height: 180px;
        overflow: auto;
    }
    .preview-card img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 6px;
        display: block;
    }

    /* Sticky footer */
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

    /* FIX Select2 dropdown di depan modal */
    .select2-container--open .select2-dropdown,
    .select2-container--open,
    .select2-container {
        z-index: 99999 !important;
    }

    /* FIX SweetAlert2 (notif/error) di depan modal */
    .swal2-container,
    .swal2-popup {
        z-index: 100000 !important;
    }

    @media (max-width: 640px) {
        .modal-box { max-height: 92vh; }
        .modal-footer { padding: 0.9rem 1.25rem; }
        .card-header { padding: .9rem 1rem; }
    }

    /* Tambahan untuk loader */
    .modal-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255, 255, 255, 0.7);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 50;
        backdrop-filter: blur(2px);
    }
    .modal-loading-overlay.active {
        display: flex;
    }
    .spinner {
        width: 40px;
        height: 40px;
        border: 5px solid #e5e7eb;
        border-top: 5px solid #10b981;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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
            <h3 class="title">Kelola Acara</h3>
            <p class="subtitle">Tambah, sunting, dan publikasikan acara masjid dengan cepat</p>
        </div>
        <button type="button" class="header-action" onclick="addAcara()" aria-label="Tambah acara">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Acara</span>
        </button>
    </div>

    <div class="p-4">
        <div class="overflow-x-auto">
            <table id="acaraTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Poster</th>
                        <th class="px-4 py-3">Judul</th>
                        <th class="px-4 py-3">Kategori</th>
                        <th class="px-4 py-3">Tanggal</th>
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
<dialog id="acaraModal" class="modal">
    <div class="modal-box w-11/12 max-w-4xl p-0">
        <form id="acaraForm" class="modal-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="method" value="POST">

            <!-- Header modal -->
            <div class="flex items-center justify-between px-6 pt-5 pb-3 border-b border-base-200">
                <h3 id="acaraModalTitle" class="text-xl font-bold text-emerald-800">Form Acara</h3>
                <button type="button" id="closeAcaraModalBtn" class="btn btn-ghost btn-sm btn-circle text-xl">✕</button>
            </div>

            <!-- Konten utama (scrollable) -->
            <div class="px-6 py-5 grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- Kolom kiri -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Judul <span class="text-red-500">*</span></label>
                        <input type="text" name="judul" class="form-control" required placeholder="Contoh: Pengajian Rutin Jumat">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Mulai <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="mulai" class="form-control" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Selesai</label>
                            <input type="datetime-local" name="selesai" class="form-control">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium mb-1">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Masjid Al-Ikhlas">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Penyelenggara</label>
                            <input type="text" name="penyelenggara" class="form-control" placeholder="Contoh: Takmir Masjid">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Pemateri / Pengisi</label>
                        <input type="text" name="pemateri" class="form-control" placeholder="Contoh: Ust. Fulan / Ustzh. Maryam">
                        <p class="text-xs text-gray-500 mt-1">Opsional</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Waktu (teks)</label>
                        <input type="text" name="waktu_teks" class="form-control" placeholder="Contoh: Ba’da Maghrib">
                        <p class="text-xs text-gray-500 mt-1">Akan diprioritaskan jika diisi</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Kategori</label>
                        <select name="kategori_id[]" class="select select-bordered w-full select2" multiple>
                            @foreach(\App\Models\Kategori::where('tipe', 'acara')->get() as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_published" class="checkbox checkbox-success" id="isPublishedCheck">
                        <label for="isPublishedCheck" class="text-sm">Publikasikan sekarang</label>
                    </div>
                </div>

                <!-- Kolom kanan: Poster -->
                <div class="space-y-4">
                    <label class="block text-sm font-medium mb-1">Poster</label>
                    <label for="gambarInput" class="dropzone">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                            <path d="M3 15a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z" stroke="currentColor" stroke-width="1.25"/>
                            <path d="M8 11l4-4 4 4" stroke="currentColor" stroke-width="1.25"/>
                        </svg>
                        <div class="text-sm text-gray-600">Klik atau seret gambar (jpg/png, max 1MB)</div>
                    </label>
                    <input type="file" name="poster" id="gambarInput" class="hidden" accept="image/*">

                    <div class="space-y-4">
                        <div id="kolomFotoLama" style="display:none;">
                            <div class="preview-card">
                                <div class="font-semibold mb-2">Foto Lama</div>
                                <div id="daftarFotoLama" class="text-sm text-gray-600"></div>
                            </div>
                        </div>
                        <div class="preview-card">
                            <div class="font-semibold mb-2">Preview Foto Baru</div>
                            <div id="previewGambar" class="text-sm text-gray-600">Belum ada gambar</div>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500">Tips: Gunakan rasio 16:9 untuk tampilan terbaik</p>
                </div>
            </div>

            <!-- Deskripsi full width di bawah -->
            <div class="px-6 pb-6">
                <label class="block text-sm font-medium mb-2">Deskripsi</label>
                <textarea name="deskripsi" id="deskripsiEditor" class="form-control w-full" rows="10" placeholder="Isi lengkap acara..."></textarea>
            </div>

            <!-- Footer sticky -->
            <div class="modal-footer">
                <button type="button" id="cancelAcaraBtn" class="btn btn-outline">Batal</button>
                <button type="submit" class="btn bg-emerald-600 hover:bg-emerald-700 text-white">Simpan</button>
            </div>
            <!-- Loader overlay (seluruh modal) -->
            <div id="modalLoader" class="modal-loading-overlay">
                <div class="text-center">
                    <div class="spinner mx-auto mb-3"></div>
                    <p class="text-emerald-700 font-medium">Menyimpan data...</p>
                </div>
            </div>
        </form>
    </div>
</dialog>

@push('scripts')
<!-- dependencies (kept same libs but load order recommended) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/tinymce@5.10.7/tinymce.min.js"></script>

<script>
    let table = null;
    const modal = document.getElementById('acaraModal');
    const $modal = $('#acaraModal');
    const form = $('#acaraForm');

    let tinyObserver = null;

    tinymce.init({
        selector: '#deskripsiEditor',
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

                const dialog = document.getElementById('acaraModal');

                // hentikan observer lama (INI YANG PENTING)
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

            // ketika editor dihancurkan → bersihkan observer
            editor.on('remove', function () {
                if (tinyObserver) {
                    tinyObserver.disconnect();
                    tinyObserver = null;
                }
            });
        }
    });

    function showDialog(d){ try{ if(typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); } catch(e){ d.classList.add('modal-open'); } }
    function closeDialog(d){ try{ if(typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); } catch(e){ d.classList.remove('modal-open'); } }

    $(function(){
        table = $('#acaraTable').DataTable({
            processing: true,
            ajax: '{{ route('admin.acara.data') }}',
            columns: [
                { data: 'poster', orderable: false },
                { data: 'judul' },
                { data: 'kategoris', orderable: false },
                { data: 'tanggal' },
                { data: 'status', orderable: false },
                {
                    data: null, orderable: false, render: function(d){
                        return `
                            <div class="inline-flex gap-2">
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-yellow-50 hover:bg-yellow-100 text-yellow-700 shadow-sm btn-ghost-ico" title="Edit" onclick="editAcara(${d.id})">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-700 shadow-sm btn-ghost-ico" title="Hapus" onclick="deleteAcara(${d.id})">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            </div>
                        `;
                    }
                }
            ],
            responsive:true,
            language:{ search: 'Cari:', lengthMenu: 'Tampilkan _MENU_', info: 'Menampilkan _START_ - _END_ dari _TOTAL_', processing: 'Memuat...' }
        });

        // modal handlers
        $('#closeAcaraModalBtn, #cancelAcaraBtn').on('click', ()=> closeDialog(modal));
        if(modal) modal.addEventListener('cancel', (e)=>{ e.preventDefault(); closeDialog(modal); });

        if($.fn.select2){ $('.select2').each(function(){ if($(this).hasClass('select2-hidden-accessible')) $(this).select2('destroy'); $(this).select2({ width:'100%', dropdownParent: $modal.length ? $modal : $('body') }); }); }

        // make dropzone clickable via keyboard
        $('.dropzone').on('keypress', function(e){ if(e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $('#gambarInput').trigger('click'); } });
    });

    // gambar preview
    $(document).on('change', '#gambarInput', function(e){
        const file = e.target.files[0];
        const preview = $('#previewGambar'); preview.empty();
        if(!file){ preview.html('<div class="text-muted small">Belum ada gambar.</div>'); return; }
        const maxSize = 1 * 1024 * 1024;
        if(file.size > maxSize){ Swal.fire('Ukuran terlalu besar!', 'Maksimal 1MB.', 'warning'); $(this).val(''); preview.html('<div class="text-muted small">Belum ada gambar.</div>'); return; }
        if(!file.type.startsWith('image/')){ Swal.fire('File tidak valid!', 'Hanya gambar diperbolehkan.', 'error'); $(this).val(''); preview.html('<div class="text-muted small">Belum ada gambar.</div>'); return; }

        const reader = new FileReader();
        reader.onload = function(ev){ const html = `<div class="preview-image-box mb-2"><img src="${ev.target.result}" alt="Preview"></div>`; preview.append(html); };
        reader.readAsDataURL(file);
    });

    // add
    window.addAcara = function(){
        form[0].reset(); $('.is-invalid').removeClass('is-invalid'); $('.invalid-feedback').remove();
        $('#method').val('POST'); form.attr('action', '{{ route('admin.acara.store') }}'); $('#acaraModalTitle').text('Tambah Acara');
        if($.fn.select2) $('.select2').val(null).trigger('change'); $('#previewGambar').empty(); $('#kolomFotoLama').hide(); showDialog(modal); setTimeout(()=> $('[name=judul]').focus(), 120);

        if (tinymce.get('deskripsiEditor')) {
            tinymce.get('deskripsiEditor').setContent('');
        }
    };

    // edit
    window.editAcara = function(id){
        $.get(`{{ url('admin/acara') }}/${id}`, function(data){
            form[0].reset(); $('#previewGambar, #daftarFotoLama').empty(); $('#kolomFotoLama').show();
            $('[name=judul]').val(data.judul); 
            if (tinymce.get('deskripsiEditor')) {
                tinymce.get('deskripsiEditor').setContent(data.deskripsi || '');
            }
            $('[name=mulai]').val(data.mulai); 
            $('[name=selesai]').val(data.selesai);
            $('[name=lokasi]').val(data.lokasi); 
            $('[name=penyelenggara]').val(data.penyelenggara);
            $('[name=pemateri]').val(data.pemateri);
            $('[name=waktu_teks]').val(data.waktu_teks);

            if(data.poster && data.poster.length > 0){ $('#daftarFotoLama').empty(); data.poster.forEach(foto => { const html = `<div class="preview-image-box mb-2"><img src="${foto.url}" alt="Foto lama"></div>`; $('#daftarFotoLama').append(html); }); } else { $('#daftarFotoLama').html('<div class="text-muted small">Tidak ada foto lama.</div>'); }

            if($.fn.select2){ $('.select2').val(data.kategori_ids || []).trigger('change'); } else { $('[name="kategori_id[]"]').val(data.kategori_ids || []); }
            $('[name=is_published]').prop('checked', data.is_published);
            $('#method').val('PUT'); form.attr('action', `{{ url('admin/acara') }}/${id}`); $('#acaraModalTitle').text('Edit Acara'); showDialog(modal); setTimeout(()=> $('[name=judul]').focus(), 120);
        });
    };

    // delete
    window.deleteAcara = function(id){
        Swal.fire({ title: 'Yakin?', text: 'Acara akan dihapus!', icon: 'warning', showCancelButton:true }).then(result => { if(result.isConfirmed){ $.ajax({ url: `{{ url('admin/acara') }}/${id}`, type:'DELETE', data: {_token: '{{ csrf_token() }}'}, success: ()=>{ table.ajax.reload(); Swal.fire('Berhasil','Acara dihapus.','success'); } }); } });
    };

    // submit
    form.on('submit', function(e){
        e.preventDefault();

        // Tampilkan loader
        $('#submitBtn').prop('disabled', true);
        $('#submitText').addClass('hidden');
        $('#submitLoader').removeClass('hidden');
        $('#modalLoader').addClass('active');

        tinymce.triggerSave();

        let formData = new FormData(this);
        let method = $('#method').val();
        if (method === 'PUT') formData.append('_method', 'PUT');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: res => {
                // Sembunyikan loader
                $('#submitBtn').prop('disabled', false);
                $('#submitText').removeClass('hidden');
                $('#submitLoader').addClass('hidden');
                $('#modalLoader').removeClass('active');

                closeDialog(modal);
                table.ajax.reload();
                Swal.fire('Berhasil', res.message || 'Data berhasil disimpan', 'success');
            },
            error: xhr => {
                // Sembunyikan loader juga saat error
                $('#submitBtn').prop('disabled', false);
                $('#submitText').removeClass('hidden');
                $('#submitLoader').addClass('hidden');
                $('#modalLoader').removeClass('active');

                const json = xhr.responseJSON || {};
                if (json.errors) {
                    $('.invalid-feedback').remove();
                    $('.is-invalid').removeClass('is-invalid');
                    Object.keys(json.errors).forEach(k => {
                        const el = $(`[name="${k}"]`);
                        if (el.length) {
                            el.addClass('is-invalid');
                            el.after(`<div class="invalid-feedback">${json.errors[k][0]}</div>`);
                        }
                    });
                } else {
                    Swal.fire('Error', json.message || 'Gagal menyimpan data.', 'error');
                }
            }
        });
    });
</script>
@endpush

@endsection