@extends('masjid.master')
@section('title', 'Manajemen Berita')
@section('content')

@push('style')
<style>
    /* CARD wrapper (sama gaya dengan halaman lainnya) */
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
    td.thumb-column { max-width: 120px; }
    td.permissions-column { max-width: 320px; word-wrap: break-word; }

    /* dialog/modal styles */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; }
    .modal-panel { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); overflow:hidden; background:white; }

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
    .preview-image-box img { width:100%; max-width:220px; height:140px; object-fit:cover; border-radius:8px; }

    /* TinyMCE z-index */
    .tox-tinymce-aux, .tox-tinymce-inline, .tox-dialog { z-index: 999999 !important; }
    .tox { z-index: 99999 !important; }

    /* validation */
    .is-invalid { border-color: #dc3545 !important; }
    .invalid-feedback { display:block; color:#dc3545; font-size:.875rem; }

    /* button circle */
    .btn-circle-ico { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; transition: transform .12s ease; }
    .btn-circle-ico:hover { transform: translateY(-2px); }

    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
    }
</style>
@endpush

<div class="card-wrapper">

    <!-- header -->
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Berita</h3>
            <p class="subtitle">Tambahkan & kelola artikel berita, atur kategori dan gambar</p>
        </div>

        <button type="button" class="header-action" onclick="addBerita()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Berita</span>
        </button>
    </div>

    <!-- body -->
    <div class="card-body">
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

<!-- Modal: Berita (dialog plain JS) -->
<!-- Modal Berita: versi lebih cantik -->
<dialog id="beritaModal" class="modal" aria-labelledby="beritaModalTitle" role="dialog">
  <div class="modal-panel w-11/12 max-w-4xl">
    <form id="beritaForm" enctype="multipart/form-data" class="p-6 bg-white rounded-2xl shadow-xl border border-emerald-50">
      @csrf
      <input type="hidden" id="method" value="POST">

      <!-- Header -->
      <div class="flex items-start justify-between gap-4 mb-2">
        <div>
          <h3 id="beritaModalTitle" class="text-xl font-extrabold text-emerald-800">📰 Buat / Sunting Berita</h3>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" id="closeBeritaModalBtn" class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Tutup">
            ✕
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left: metadata + image -->
        <div class="space-y-4">
          <label class="block text-sm font-medium text-emerald-700">Judul Berita <span class="text-red-500">*</span></label>
          <input type="text" name="judul" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm focus:ring-2 focus:ring-emerald-200" placeholder="Contoh: Bakti Sosial Minggu Ini" required>

          <label class="block text-sm font-medium text-emerald-700">Kategori</label>
          <select name="kategori_id[]" class="form-select select2 w-full rounded-lg border border-emerald-100 px-2 py-2 shadow-sm" multiple required>
            @foreach(\App\Models\Kategori::where('tipe', 'berita')->get() as $k)
              <option value="{{ $k->id }}">{{ $k->nama }}</option>
            @endforeach
          </select>

          <label class="block text-sm font-medium text-emerald-700">Gambar Berita</label>

          <!-- Dropzone visual -->
          <label for="gambarInput" class="cursor-pointer block rounded-xl border-2 border-dashed border-emerald-200 p-4 text-center hover:bg-emerald-50 transition">
            <div class="flex items-center justify-center gap-3">
              <svg class="w-6 h-6 text-emerald-600" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M3 15a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/><path d="M8 11l4-4 4 4" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
              <div class="text-sm text-emerald-700 font-medium">Klik atau seret gambar ke sini</div>
            </div>
            <div class="text-xs text-gray-400 mt-1">JPG / PNG • Maks 1MB • Rasio ideal 16:9</div>
          </label>
          <input type="file" name="gambar" id="gambarInput" accept="image/*" class="hidden">

          <!-- previews -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-1">
            <div id="kolomFotoLama" class="hidden bg-white rounded-lg border border-emerald-50 p-3 shadow-sm">
              <div class="text-sm font-semibold text-emerald-700 mb-2">📁 Foto Lama</div>
              <div id="daftarFotoLama" class="text-sm text-gray-500">Tidak ada foto lama.</div>
            </div>
            <div class="bg-white rounded-lg border border-emerald-50 p-3 shadow-sm">
              <div class="text-sm font-semibold text-emerald-700 mb-2">🖼️ Preview Gambar Baru</div>
              <div id="previewGambar" class="text-sm text-gray-500">Belum ada gambar baru.</div>
            </div>
          </div>

          <div class="flex items-center gap-3 mt-1">
            <input type="checkbox" name="is_published" id="isPublished" class="rounded text-emerald-600 focus:ring-emerald-400">
            <label for="isPublished" class="text-sm text-gray-700">Publikasikan sekarang</label>
          </div>
        </div>

        <!-- Right: content editor -->
        <div>
          <label class="block text-sm font-medium text-emerald-700 mb-1">Isi Berita</label>
          <textarea name="isi" id="isi" class="w-full h-full min-h-[300px] rounded-lg border border-emerald-100 p-2 shadow-sm" rows="18"></textarea>
        </div>
      </div>

      <!-- Footer actions -->
      <div class="flex items-center justify-end gap-3 mt-1">
        <button type="button" id="cancelBeritaBtn" class="px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">Batal</button>
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow">Simpan</button>
      </div>
    </form>
  </div>
</dialog>


@endsection

@push('scripts')
<!-- libs -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@5.10.7/tinymce.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
    let table = null;
    const modal = document.getElementById('beritaModal');
    const $modal = $('#beritaModal');
    const form = $('#beritaForm');

    // dialog helpers (fallback)
    function showDialog(d) {
        try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
        catch(e){ d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
        catch(e){ d.classList.remove('modal-open'); }
    }

    // TinyMCE init
    tinymce.init({
        selector: '#isi',
        height: 520,
        menubar: 'file edit view format',
        plugins: 'lists advlist link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount textcolor colorpicker',
        toolbar: `
            undo redo | formatselect fontselect fontsizeselect |
            bold italic underline forecolor backcolor |
            alignleft aligncenter alignright alignjustify |
            bullist numlist outdent indent | removeformat | code
        `,
        branding: false,
        content_style: 'body { font-family:Arial,Helvetica,sans-serif; font-size:14px }',
        relative_urls: false,
        zindex: 99999
    });

    // image preview logic
    $(document).on('change', '#gambarInput', function(e) {
        const file = e.target.files[0];
        const preview = $('#previewGambar');
        preview.empty();

        if (!file) {
            preview.html(`<div class="text-muted small">Belum ada gambar baru.</div>`);
            return;
        }

        const maxSize = 1 * 1024 * 1024; // 1MB
        if (file.size > maxSize) {
            Swal.fire('Ukuran terlalu besar!', 'Maksimal 1MB.', 'warning');
            $(this).val('');
            preview.html(`<div class="text-muted small">Belum ada gambar baru.</div>`);
            return;
        }

        if (!file.type.startsWith('image/')) {
            Swal.fire('File tidak valid!', 'Hanya file gambar diperbolehkan.', 'error');
            $(this).val('');
            preview.html(`<div class="text-muted small">Belum ada gambar baru.</div>`);
            return;
        }

        const reader = new FileReader();
        reader.onload = function(event) {
            const html = `<div class="preview-image-box mb-2"><img src="${event.target.result}" alt="preview"></div>`;
            preview.append(html);
        };
        reader.readAsDataURL(file);
    });

    // initialize datatable
    $(function () {
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
                { data: 'kategoris', orderable: false, className: 'permissions-column' },
                { data: 'status', orderable: false, render: s => s },
                { data: 'tanggal' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: d => `
                        <div class="inline-flex gap-2">
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-yellow-50 hover:bg-yellow-100 text-yellow-700 shadow-sm btn-ghost-ico" title="Edit" onclick="editBerita(${d.id})">
                              <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button class="inline-flex items-center justify-center w-9 h-9 rounded-md bg-red-50 hover:bg-red-100 text-red-700 shadow-sm btn-ghost-ico" title="Hapus" onclick="deleteBerita(${d.id})">
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

        // modal control bindings
        $('#closeBeritaModalBtn').on('click', () => closeDialog(modal));
        $('#cancelBeritaBtn').on('click', () => closeDialog(modal));
        if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

        // init select2 if available (use dropdownParent fallback not needed for dialog)
        if ($.fn.select2) {
            $('.select2').select2({ width: '100%' });
        }
    });

    // add Berita
    window.addBerita = function () {
        form[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        tinymce.get('isi')?.setContent('');
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.berita.store') }}');
        $('#beritaModalTitle').text('Tambah Berita');
        $('#previewGambar').empty().html('<div class="text-muted small">Belum ada gambar baru.</div>');
        $('#daftarFotoLama').html('<div class="text-muted small">Tidak ada foto lama.</div>');
        $('#kolomFotoLama').hide();
        showDialog(modal);
        setTimeout(()=> $('[name=judul]').focus(), 120);
    };

    // edit berita
    window.editBerita = function (id) {
        $.get(`{{ url('admin/berita') }}/${id}`)
        .done(function (data) {
            form[0].reset();
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('#previewGambar').empty();
            $('#daftarFotoLama').empty();
            $('#kolomFotoLama').show();

            $('[name=judul]').val(data.judul);
            tinymce.get('isi')?.setContent(data.isi || '');
            if ($.fn.select2) {
                $('[name="kategori_id[]"]').val(data.kategori_ids || []).trigger('change');
            } else {
                $('[name="kategori_id[]"]').val(data.kategori_ids || []);
            }

            if (Array.isArray(data.gambar) && data.gambar.length) {
                data.gambar.forEach(foto => {
                    const html = `<div class="mb-1"><img src="${foto.url}" class="img-thumbnail" style="width:100px;border-radius:8px;"></div>`;
                    $('#daftarFotoLama').append(html);
                });
            } else {
                $('#daftarFotoLama').html('<div class="text-muted small">Tidak ada foto lama.</div>');
            }

            $('[name=is_published]').prop('checked', !!data.is_published);
            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/berita') }}/${id}`);
            $('#beritaModalTitle').text(data.judul ? `Edit: ${data.judul}` : 'Edit Berita');
            showDialog(modal);
            setTimeout(()=> $('[name=judul]').focus(), 120);
        })
        .fail(function () {
            Swal.fire('Error', 'Gagal memuat data berita.', 'error');
        });
    };

    // delete berita
    window.deleteBerita = function (id) {
        Swal.fire({
            title: 'Yakin?',
            text: 'Berita akan dihapus!',
            icon: 'warning',
            showCancelButton: true
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/berita') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => {
                        table.ajax.reload();
                        Swal.fire('Berhasil', 'Berita dihapus.', 'success');
                    },
                    error: () => Swal.fire('Error', 'Gagal menghapus berita.', 'error')
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
        formData.append('isi', tinymce.get('isi').getContent());

        $.ajax({
            url: action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (res) {
                closeDialog(modal);
                table.ajax.reload();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function (xhr) {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let errors = xhr.responseJSON?.errors;
                if (errors) {
                    $.each(errors, function (key, messages) {
                        // field could be like kategori_id. handle array names
                        let input = $('[name="' + key + '"]');
                        if (!input.length) {
                            // try with [] (select multiple)
                            input = $('[name="' + key + '[]"]');
                        }
                        if (input.length) {
                            input.addClass('is-invalid');
                            input.after('<div class="invalid-feedback">' + (messages[0] || 'Error') + '</div>');
                        }
                    });
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan!', 'error');
                }
            }
        });
    });

</script>
@endpush