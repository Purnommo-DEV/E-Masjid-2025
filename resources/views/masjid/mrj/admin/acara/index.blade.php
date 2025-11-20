@extends('masjid.master')
@section('title', 'Manajemen Acara')
@section('content')
<style>
    .badge { margin: 2px; display: inline-block; }
    td.thumb-column { max-width: 80px; }
    td.permissions-column { max-width: 300px; word-wrap: break-word; }

    /* Modal selalu di atas */
    #acaraModal.modal {
        position: fixed !important;
        z-index: 9999 !important;
    }
    .modal-backdrop.show {
        z-index: 9998 !important;
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
        <h3>Kelola Acara</h3>
        <button class="btn btn-primary btn-sm" onclick="addAcara()">Tambah Acara</button>
    </div>
    <div class="card-body">
        <table id="acaraTable" class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Poster</th>
                    <th>Judul</th>
                    <th>Kategori</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>

<div class="modal fade" id="acaraModal">
    <form id="acaraForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="method" value="POST">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Form Acara</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Judul</label>
                        <input type="text" name="judul" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Deskripsi</label>
                        <textarea name="deskripsi" class="form-control"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Mulai</label>
                            <input type="datetime-local" name="mulai" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Selesai</label>
                            <input type="datetime-local" name="selesai" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Penyelenggara</label>
                        <input type="text" name="penyelenggara" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Kategori</label>
                        <select name="kategori_id[]" class="form-select select2" multiple>
                            @foreach(\App\Models\Kategori::where('tipe', 'acara')->get() as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label>Poster</label>
                        <input type="file" name="poster" id="gambarInput" class="form-control" accept="image/*">
                        <div class="row mt-2">
                            <!-- FOTO LAMA -->
                            <div class="col-md-6"  id="kolomFotoLama">
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

@push('scripts')
<script>
    let table = null;
    const modal = $('#acaraModal');
    const form = $('#acaraForm');

    $(function () {
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
                    data: null,
                    orderable: false,
                    render: d => `
                        <button class="btn btn-sm btn-warning" onclick="editAcara(${d.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteAcara(${d.id})">Hapus</button>
                    `
                }
            ]
        });
    });

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

    window.addAcara = function () {
        form[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        $('#method').val('POST');
        form.attr('action', '{{ route('admin.acara.store') }}');
        modal.find('.modal-title').text('Tambah Acara');

        // Hanya preview foto baru
        $('#previewGambar').empty();
        
        // 🔥 SEMBUNYIKAN seluruh kolom foto lama
        $('#kolomFotoLama').hide();

        modal.modal('show');
    };


    window.editAcara = function (id) {
        $.get(`{{ url('admin/acara') }}/${id}`, function (data) {
            form[0].reset();
            $('#previewGambar, #daftarFotoLama').empty();
            $('#kolomFotoLama').show();

            $('[name=judul]').val(data.judul);
            $('[name=deskripsi]').val(data.deskripsi);
            $('[name=mulai]').val(data.mulai);
            $('[name=selesai]').val(data.selesai);
            $('[name=lokasi]').val(data.lokasi);
            $('[name=penyelenggara]').val(data.penyelenggara);

            if (data.poster.length > 0) {

                $('#daftarFotoLama').empty();

                data.poster.forEach(foto => {
                    const html = `
                        <div class="preview-image-box mb-2">
                            <img src="${foto.url}">
                        </div>`;
                    $('#daftarFotoLama').append(html);
                });

            } else {
                $('#daftarFotoLama').html(`<div class="text-muted small">Tidak ada foto lama.</div>`);
            }

            $('[name="kategori_id[]"]').val(data.kategori_ids).trigger('change');
            $('[name=is_published]').prop('checked', data.is_published);
            $('#method').val('PUT');
            form.attr('action', `{{ url('admin/acara') }}/${id}`);
            modal.find('.modal-title').text('Edit Acara');
            modal.modal('show');
        });
    };

    window.deleteAcara = function (id) {
        Swal.fire({
            title: 'Yakin?', text: 'Acara akan dihapus!', icon: 'warning',
            showCancelButton: true
        }).then(result => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/acara') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => { table.ajax.reload(); Swal.fire('Berhasil', 'Acara dihapus.', 'success'); }
                });
            }
        });
    };

    form.on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        let method = $('#method').val();
        if (method === 'PUT') formData.append('_method', 'PUT');

        $.ajax({
            url: form.attr('action'), type: 'POST', data: formData,
            processData: false, contentType: false,
            success: res => {
                modal.modal('hide');
                table.ajax.reload();
                Swal.fire('Berhasil', res.message, 'success');
            },
            error: xhr => Swal.fire('Error', xhr.responseJSON?.message || 'Gagal.', 'error')
        });
    });
</script>
@endpush