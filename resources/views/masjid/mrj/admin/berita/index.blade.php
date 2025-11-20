@extends('masjid.master')
@section('title', 'Manajemen Berita')
@section('content')
<style>
    .badge { margin: 2px; display: inline-block; }
    td.thumb-column { max-width: 80px; }
    td.permissions-column { max-width: 300px; word-wrap: break-word; }

    /* Modal selalu di atas */
    #beritaModal.modal {
        position: fixed !important;
        z-index: 9999 !important;
    }
    .modal-backdrop.show {
        z-index: 9998 !important;
    }

    /* TinyMCE dropdown tidak ketimpa */
    .tox-tinymce-aux,
    .tox-tinymce-inline,
    .tox-dialog {
        z-index: 999999 !important;
    }
    .tox {
        z-index: 99999 !important;
    }

    /* Validasi form */
    .is-invalid {
        border-color: #dc3545 !important;
    }
    .invalid-feedback {
        display: block;
        color: #dc3545;
        font-size: 0.875em;
    }

    .swal2-container {
        z-index: 20000 !important; /* lebih tinggi dari modal bootstrap (1050) */
    }

    /* Style preview gambar */
    .preview-card {
        background: #ffffff;
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .preview-title {
        font-weight: 600;
        font-size: 15px;
        margin-bottom: 10px;
    }

    .preview-image-box {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        display: inline-block;
    }

    .preview-image-box img {
        width: 100%;
        max-width: 200px;
        height: 120px;
        object-fit: cover;
        border-radius: 8px;
    }
    
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3>Kelola Berita</h3>
        <button class="btn btn-primary btn-sm" onclick="addBerita()">Tambah Berita</button>
    </div>
    <div class="card-body">
        <table id="beritaTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th class="thumb-column">Gambar</th>
                    <th>Judul</th>
                    <th class="permissions-column">Kategori</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="beritaModal">
    <form id="beritaForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Berita</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Isi Berita</label>
                        <textarea name="isi" id="isi" class="tinymce"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id[]" class="form-select select2" multiple required>
                            @foreach(\App\Models\Kategori::where('tipe', 'berita')->get() as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Gambar</label>
                        <input type="file" name="gambar" id="gambarInput" class="form-control" accept="image/*">
                        <div class="row mt-2">
                            <!-- FOTO LAMA -->
                            <div class="col-md-6" id="kolomFotoLama">
                                <div class="preview-card">
                                    <div class="preview-title">📁 Foto Lama</div>
                                    <div id="daftarFotoLama">
                                        <div class="text-muted small">Tidak ada foto lama.</div>
                                    </div>
                                </div>
                            </div>

                            <!-- FOTO BARU -->
                            <div class="col-md-6">
                                <div class="preview-card">
                                    <div class="preview-title">🆕 Foto Baru (Preview)</div>
                                    <div id="previewGambar">
                                        <div class="text-muted small">Belum ada gambar baru.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_published" class="form-check-input">
                        <label>Publikasikan Sekarang</label>
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
<script src="https://cdn.jsdelivr.net/npm/tinymce@5.10.7/tinymce.min.js"></script>

<script>
    let table = null;
    const modal = $('#beritaModal');
    const form = $('#beritaForm');

    // TinyMCE setup
    tinymce.init({
        selector: '#isi',
        height: 450,
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
        fixed_toolbar_container: false,
        relative_urls: false,
        zindex: 99999
    });

    // Preview gambar
    $(document).on('change', '#gambarInput', function(e) {
        const file = e.target.files[0];
        const preview = $('#previewGambar');
        preview.empty();

        if (!file) {
            preview.html(`<div class="text-muted small">Belum ada gambar baru.</div>`);
            return;
        }

        // Validasi ukuran max 1MB
        const maxSize = 1 * 1024 * 1024;
        if (file.size > maxSize) {
            Swal.fire('Ukuran terlalu besar!', 'Maksimal 1MB.', 'warning');
            $(this).val('');
            preview.html(`<div class="text-muted small">Belum ada gambar baru.</div>`);
            return;
        }

        // Validasi tipe file gambar
        if (!file.type.startsWith('image/')) {
            Swal.fire('File tidak valid!', 'Hanya file gambar diperbolehkan.', 'error');
            $(this).val('');
            preview.html(`<div class="text-muted small">Belum ada gambar baru.</div>`);
            return;
        }

        // Tampilkan preview
        const reader = new FileReader();
        reader.onload = function(event) {
            const html = `
                <div class="preview-image-box mb-2">
                    <img src="${event.target.result}">
                </div>`;
            preview.append(html);
        };

        reader.readAsDataURL(file);
    });

    // Datatable
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
                {
                    data: 'kategoris',
                    orderable: false,
                    className: 'permissions-column'
                },
                {
                    data: 'status',
                    orderable: false,
                    render: s => s
                },
                { data: 'tanggal' },
                {
                    data: null,
                    orderable: false,
                    render: d => `
                        <button class="btn btn-sm btn-warning" onclick="editBerita(${d.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBerita(${d.id})">Hapus</button>
                    `
                }
            ]
        });
    });

    // Tambah berita
    window.addBerita = function () {
        form[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        tinymce.get('isi')?.setContent('');

        $('#method').val('POST');
        form.attr('action', '{{ route('admin.berita.store') }}');
        modal.find('.modal-title').text('Tambah Berita');

        // Hanya preview foto baru
        $('#previewGambar').empty();
        
        // 🔥 SEMBUNYIKAN seluruh kolom foto lama
        $('#kolomFotoLama').hide();
        modal.modal('show');
    };

    // Edit berita
    window.editBerita = function (id) {
        $.get(`{{ url('admin/berita') }}/${id}`, function (data) {
            form[0].reset();
            $('#previewGambar, #daftarFotoLama').empty();
            $('#kolomFotoLama').show();

            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').remove();
            $('[name=judul]').val(data.judul);
            tinymce.get('isi')?.setContent(data.isi);
            $('[name="kategori_id[]"]').val(data.kategori_ids).trigger('change');

            if (data.gambar.length > 0) {

                $('#daftarFotoLama').empty();

                data.gambar.forEach(foto => {
                    const html = `
                        <div class="mb-2">
                            <img src="${foto.url}" class="img-thumbnail" style="width:200px;">
                        </div>
                    `;
                    $('#daftarFotoLama').append(html);
                });

            } else {
                $('#daftarFotoLama').html(`<div class="text-muted small">Tidak ada foto lama.</div>`);
            }

            $('[name=is_published]').prop('checked', data.is_published);
            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/berita') }}/${id}`);
            modal.find('.modal-title').text('Edit Berita');
            modal.modal('show');
        });
    };

    // Hapus berita
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
                    }
                });
            }
        });
    };

    // Submit form
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
                modal.modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil!', res.message, 'success');
            },
            error: function (xhr) {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                let errors = xhr.responseJSON?.errors;
                if (errors) {
                    $.each(errors, function (key, messages) {
                        let input = $('[name="' + key + '"]');
                        input.addClass('is-invalid');
                        input.after('<div class="invalid-feedback">' + messages[0] + '</div>');
                    });
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan!', 'error');
                }
            }
        });
    });
</script>
@endpush
