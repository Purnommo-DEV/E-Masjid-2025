@extends('masjid.master')
@section('title', 'Manajemen Galeri')
@section('content')
<style>
    .badge { margin: 2px; display: inline-block; }
    td.thumb { max-width: 100px; }

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
    }

    .preview-image-box img {
        width: 100%;
        height: 120px;
        object-fit: cover;
    }

    .delete-btn {
        position: absolute;
        top: 6px;
        right: 6px;
        padding: 4px 8px;
        border-radius: 6px;
    }
</style>
<div class="card">
    <div class="card-header d-flex justify-content-between">
        <h3>Kelola Galeri</h3>
        <button class="btn btn-primary btn-sm" onclick="addGaleri()">Tambah Galeri</button>
    </div>
    <div class="card-body">
        <table id="galeriTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th class="thumb">Thumbnail</th>
                    <th>Judul</th>
                    <th>Tipe</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="galeriModal">
    <form id="galeriForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Galeri</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Tipe</label>
                        <select name="tipe" class="form-select" required>
                            <option value="foto">Foto</option>
                            <option value="video">Video</option>
                        </select>
                    </div>
                    <div id="fotoSection">
                        <label class="form-label fw-bold">Foto (pilih banyak sekaligus)</label>
                        <div class="border border-2 border-dashed rounded p-4 text-center mb-3 upload-area"
                             style="background:#f8f9fa; cursor:pointer;">
                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-2"></i>
                            <p class="text-muted mb-0">Klik atau seret foto ke sini</p>
                            <small class="text-muted">Maks 10MB per foto</small>
                        </div>
                        <input type="file" name="fotos[]" id="uploadFoto" class="d-none" multiple accept="image/*">
                            <div class="row">
                                <!-- FOTO LAMA -->
                                <div class="col-md-6">
                                    <div class="preview-card">
                                        <div class="preview-title">📁 Foto Lama</div>
                                        <div id="daftarFotoLama" class="row g-2"></div>
                                    </div>
                                </div>

                                <!-- FOTO BARU -->
                                <div class="col-md-6">
                                    <div class="preview-card">
                                        <div class="preview-title">🆕 Foto Baru (yang diupload)</div>
                                        <div id="previewFotos" class="row g-2"></div>
                                    </div>
                                </div>
                            </div>
                        <input type="hidden" name="deleted_fotos" id="deletedFotos" value="">
                    </div>
                    <div id="videoSection" style="display:none">
                        <label>URL YouTube</label>
                        <input type="url" name="url_video" class="form-control" placeholder="https://youtube.com/watch?v=...">
                    </div>
                    <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id[]" class="form-select select2" multiple>
                            @foreach(\App\Models\Kategori::where('tipe', 'galeri')->get() as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" name="is_published" class="form-check-input">
                        <label>Publikasikan</label>
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

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<script>
    let table = null;
    const modal = $('#galeriModal');
    const form = $('#galeriForm');
    let uploadedFiles = [];

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
                    render: d => `
                        <button class="btn btn-sm btn-warning" onclick="editGaleri(${d.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteGaleri(${d.id})">Hapus</button>
                    `
                }
            ]
        });
    });

    $(document).on('change', '#uploadFoto', function(e) {
        const files = Array.from(e.target.files);
        if (!files.length) return;

        files.forEach(file => {
            if (file.size > 10 * 1024 * 1024) {
                Swal.fire('Error!', 'Maksimal 10MB per foto!', 'error');
                return;
            }
            uploadedFiles.push(file);
        });

        renderPreview();
        this.value = '';
    });

    function renderPreview() {
        $('#previewFotos').empty();

        uploadedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(ev) {
                const html = `
                    <div class="col-6 col-md-4">
                        <div class="preview-image-box">
                            <img src="${ev.target.result}">
                            <button type="button" 
                                class="btn btn-danger btn-sm delete-btn"
                                onclick="hapusFotoBaru(${index})">Hapus</button>
                        </div>
                    </div>`;
                $('#previewFotos').append(html);
            };
            reader.readAsDataURL(file);
        });
    }

    function hapusFotoBaru(index) {
        uploadedFiles.splice(index, 1);
        renderPreview();
    }

    function hapusFotoLama(url) {
        Swal.fire({
            title: 'Yakin?', text: 'Hapus foto ini?', icon: 'warning',
            showCancelButton: true
        }).then(result => {
            if (result.isConfirmed) {
                $(`[data-url="${url}"]`).remove();
                
                let deleted = $('#deletedFotos').val();
                if (deleted) deleted += ',' + url;
                else deleted = url;
                
                $('#deletedFotos').val(deleted);
            }
        });
    }


    $('.upload-area').on('click', () => $('#uploadFoto').click())
    .on('dragover dragenter', e => { e.preventDefault(); $(this).addClass('border-primary bg-light'); })
    .on('dragleave dragend', e => { e.preventDefault(); $(this).removeClass('border-primary bg-light'); })
    .on('drop', e => {
        e.preventDefault();
        $('#uploadFoto')[0].files = e.originalEvent.dataTransfer.files;
        $('#uploadFoto').trigger('change');
    });

    window.addGaleri = function () {
        uploadedFiles = [];
        form[0].reset();
        $('#previewFotos, #daftarFotoLama').empty();
        $('#deletedFotos').val('');
        $('#method').val('POST');
        form.attr('action', '{{ route('admin.galeri.store') }}');
        $('#fotoSection').show();
        $('#videoSection').hide();
        modal.find('.modal-title').text('Tambah Galeri');
        modal.modal('show');
    };

    window.editGaleri = function (id) {
        uploadedFiles = [];
        $.get(`{{ url('admin/galeri') }}/${id}`, function (data) {
            form[0].reset();
            $('#previewFotos, #daftarFotoLama').empty();
            $('#deletedFotos').val(''); // Reset
            $('[name=judul]').val(data.judul);
            $('[name=keterangan]').val(data.keterangan);
            $('[name=tipe]').val(data.tipe).trigger('change');
            $('[name="kategori_id[]"]').val(data.kategori_ids).trigger('change');
            $('[name=is_published]').prop('checked', data.is_published);

            if (data.tipe === 'video') {
                $('[name=url_video]').val(data.url_video);
                $('#fotoSection').hide();
                $('#videoSection').show();
            } else {
                $('#fotoSection').show();
                $('#videoSection').hide();
                data.fotos.forEach(foto => {
                const html = `
                    <div class="col-6 col-md-4" data-url="${foto.url}">
                        <div class="preview-image-box">
                            <img src="${foto.url}">
                            <button type="button" 
                                class="btn btn-danger btn-sm delete-btn"
                                onclick="hapusFotoLama('${foto.url}')">Hapus</button>
                        </div>
                    </div>`;
                    $('#daftarFotoLama').append(html);
                });
            }

            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/galeri') }}/${id}`);
            modal.find('.modal-title').text('Edit Galeri');
            modal.modal('show');
        });
    };

    window.deleteGaleri = function (id) {
        Swal.fire({
            title: 'Yakin?', text: 'Galeri akan dihapus!', icon: 'warning',
            showCancelButton: true
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/galeri') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => { table.ajax.reload(); Swal.fire('Berhasil', 'Galeri dihapus.', 'success'); }
                });
            }
        });
    };

    $('[name=tipe]').on('change', function () {
        $('#fotoSection').toggle(this.value === 'foto');
        $('#videoSection').toggle(this.value === 'video');
    });

    form.on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        formData.delete('fotos[]');
        uploadedFiles.forEach(file => {
            formData.append('fotos[]', file);
        });
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
                modal.modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: xhr => {
                let msg = xhr.responseJSON?.errors
                    ? Object.values(xhr.responseJSON.errors)[0][0]
                    : xhr.responseJSON?.message || 'Gagal.';
                Swal.fire('Error!', msg, 'error');
            }
        });
    });
</script>
@endpush