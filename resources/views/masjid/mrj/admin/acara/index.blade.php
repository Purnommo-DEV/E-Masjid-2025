@extends('masjid.master')
@section('title', 'Manajemen Acara')
@section('content')

@push('style')
<style>
    /* Modern card using Tailwind-friendly utilities but with a few custom tweaks */
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
        display:flex;align-items:center;justify-content:space-between;gap:1rem;
    }

    .card-header .title { font-size:1.125rem;font-weight:800;letter-spacing:0.2px }
    .card-header .subtitle { font-size:0.95rem;opacity:0.95 }

    /* modern small pill button */
    .header-action { display:inline-flex;align-items:center;gap:.6rem;padding:.5rem .9rem;border-radius:999px;background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.08);box-shadow:0 8px 24px rgba(4,120,87,0.05);transition:transform .12s ease }
    .header-action:hover{ transform:translateY(-3px) }

    /* modal improvements */
    dialog.modal::backdrop { background: rgba(15,23,42,0.55); backdrop-filter: blur(4px) saturate(1.02); }
    .modal-box { border-radius: 12px; box-shadow: 0 26px 60px rgba(2,6,23,0.16); }

    /* dropzone preview */
    .dropzone {
        border: 2px dashed rgba(15,23,42,0.06);
        padding: 14px; border-radius:10px; display:flex;align-items:center;gap:12px;cursor:pointer;
    }
    .dropzone:hover{ background: #f8fafc }
    .preview-image-box img{ width:100%; height:160px; object-fit:cover; border-radius:8px; }

    /* form controls fallback styles when .form-control exists */
    .form-control { display:block;width:100%;padding:.6rem .75rem;border:1px solid rgba(15,23,42,0.08);border-radius:8px;background:#fff }
    .form-control:focus{ outline:none; box-shadow: 0 6px 20px rgba(16,185,129,0.08); border-color: rgba(16,185,129,0.6) }

    .invalid-feedback{ color:#dc3545;font-size:.875rem;margin-top:.25rem }
    /* make modal a column layout so footer can stay fixed */
    .modal-box {
        border-radius: 12px;
        box-shadow: 0 26px 60px rgba(2,6,23,0.16);
        position: relative;              /* for absolute/sticky children */
        max-height: 80vh;                /* prevent modal growing off-screen */
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    /* the form area that can scroll when content is large */
    .modal-form {
        padding: 1.5rem;                 /* keep same spacing */
        display: flex;
        flex-direction: column;
        gap: 1rem;
        overflow: auto;                  /* allow scrolling inside modal body */
        flex: 1 1 auto;                  /* take remaining height */
    }

    /* limit preview card size to avoid pushing footer */
    .preview-card {
        border-radius: 8px;
        padding: .6rem;
        border: 1px solid rgba(15,23,42,0.04);
        background: #fafafa;
        max-height: 220px;               /* restrict height */
        overflow: auto;                  /* allow scrolling inside preview */
    }

    .preview-card img {
        width: 100%;
        height: 140px;                   /* lower than previous so it won't push footer */
        object-fit: cover;
        border-radius: 6px;
        display:block;
    }

    /* sticky footer that remains visible */
    .modal-footer {
        position: sticky;
        bottom: 0;
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        padding: 0.75rem 1.5rem;
        background: linear-gradient(180deg, rgba(255,255,255,0), rgba(255,255,255,1) 60%); /* subtle fade */
        box-shadow: 0 -6px 18px rgba(2,6,23,0.06);
        z-index: 30;
    }

    /* kecilkan input/textarea/select ketika menggunakan class form-control-sm, textarea-sm, form-select-sm */
    .form-control-sm {
        padding: .35rem .5rem;
        font-size: .85rem;
        line-height: 1.1;
        border-radius: 6px;
    }
    .textarea-sm {
        padding: .45rem .5rem;
        font-size: .85rem;
        border-radius: 6px;
    }
    .form-select-sm {
        padding: .35rem .5rem;
        font-size: .85rem;
        border-radius: 6px;
    }

    /* sedikit pengaturan preview agar tidak mendorong footer */
    .preview-card { max-height: 200px; overflow:auto; padding:.5rem; border-radius:8px; }
    .preview-card img { height:120px; object-fit:cover; border-radius:6px; width:100%; display:block; }

    /* small screens: ensure modal not too cramped */
    @media (max-width: 640px) {
        .modal-box { max-height: 90vh; }
        .preview-card { max-height: 180px; }
    }
    @media (max-width:640px){ .card-header{padding:.9rem 1rem} }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Acara</h3>
            <p class="subtitle">Tambah, sunting, dan publikasikan acara masjid dengan cepat</p>
        </div>

        <button type="button" class="header-action" onclick="addAcara()" aria-label="Tambah acara">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
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
<!-- Modal -->
<dialog id="acaraModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="acaraModalTitle">
    <div class="modal-box w-11/12 max-w-4xl p-0 overflow-hidden">
        <!-- ubah class form jadi modal-form -->
        <form id="acaraForm" class="modal-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="method" value="POST">

            <div class="flex items-center justify-between">
                <h3 id="acaraModalTitle" class="text-lg font-semibold text-emerald-800">Form Acara</h3>
                <button type="button" id="closeAcaraModalBtn" class="text-gray-600 hover:text-gray-800" aria-label="Tutup">✕</button>
            </div>

            <!-- wrap the scrollable content -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <label class="block text-sm font-medium">Judul <span class="text-red-500">*</span></label>
                    <input type="text" name="judul" class="form-control" required placeholder="Contoh: Pengajian Rutin Jumat">

                    <label class="block text-sm font-medium">Deskripsi</label>
                    <textarea name="deskripsi" class="form-control" rows="5" placeholder="Ringkasan acara"></textarea>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Mulai <span class="text-red-500">*</span></label>
                            <input type="datetime-local" name="mulai" class="form-control" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Selesai</label>
                            <input type="datetime-local" name="selesai" class="form-control">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium">Lokasi</label>
                            <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Masjid Al-Ikhlas">
                        </div>
                        <div>
                            <label class="block text-sm font-medium">Penyelenggara</label>
                            <input type="text" name="penyelenggara" class="form-control" placeholder="Contoh: Takmir Masjid">
                        </div>
                    </div>

                    <!-- TAMBAHAN: Pemateri -->
                    <div>
                        <label class="block text-sm font-medium">Pemateri / Pengisi</label>
                        <input type="text" name="pemateri" class="form-control" placeholder="Contoh: Ust. Fulan / Ustzh. Maryam">
                        <p class="text-xs text-gray-500 mt-1">Opsional — tampilkan di list publik jika diisi.</p>
                    </div>

                    <!-- TAMBAHAN: Waktu (teks) -->
                    <div>
                        <label class="block text-sm font-medium">Waktu (teks)</label>
                        <input type="text" name="waktu_teks" class="form-control" placeholder="Contoh: Ba’da Maghrib / Setelah Tarawih">
                        <p class="text-xs text-gray-500 mt-1">Jika diisi, akan diprioritaskan pada tampilan publik. Jika kosong, gunakan jam dari Mulai/Selesai.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium">Kategori</label>
                        <select name="kategori_id[]" class="form-select select2" multiple style="width:100%">
                            @foreach(\App\Models\Kategori::where('tipe', 'acara')->get() as $k)
                                <option value="{{ $k->id }}">{{ $k->nama }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-start gap-3">
                        <input type="checkbox" name="is_published" class="form-check-input mt-1" id="isPublishedCheck">
                        <label for="isPublishedCheck" class="text-sm">Publikasikan sekarang</label>
                    </div>
                </div>

                <div class="space-y-3">
                    <label class="block text-sm font-medium">Poster</label>

                    <!-- prettier dropzone -->
                    <label for="gambarInput" class="dropzone" tabindex="0">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" aria-hidden>
                            <path d="M3 15a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M8 11l4-4 4 4" stroke="currentColor" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="text-sm text-gray-600">Klik atau seret untuk memilih gambar (.jpg/.png). Maks 1MB.</div>
                    </label>
                    <input type="file" name="poster" id="gambarInput" class="sr-only" accept="image/*">

                    <div class="grid grid-cols-1 gap-3">
                        <div id="kolomFotoLama" style="display:none;">
                            <div class="preview-card">
                                <div class="preview-title font-semibold mb-2">📁 Foto Lama</div>
                                <div id="daftarFotoLama" class="text-sm text-gray-600">Tidak ada foto lama.</div>
                            </div>
                        </div>

                        <div>
                            <div class="preview-card">
                                <div class="preview-title font-semibold mb-2">🆕 Preview Foto Baru</div>
                                <div id="previewGambar" class="text-sm text-gray-600">Belum ada gambar.</div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 text-xs text-gray-500">Tips: Gunakan poster dengan rasio 16:9 agar tampak rapi di tabel.</div>
                </div>
            </div>
            <!-- pindahkan footer ke sini supaya sticky -->
            <div class="modal-footer">
                <button type="button" id="cancelAcaraBtn" class="px-4 py-2 rounded-md border">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan</button>
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

<script>
    let table = null;
    const modal = document.getElementById('acaraModal');
    const $modal = $('#acaraModal');
    const form = $('#acaraForm');

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
    };

    // edit
    window.editAcara = function(id){
        $.get(`{{ url('admin/acara') }}/${id}`, function(data){
            form[0].reset(); $('#previewGambar, #daftarFotoLama').empty(); $('#kolomFotoLama').show();
            $('[name=judul]').val(data.judul); $('[name=deskripsi]').val(data.deskripsi); $('[name=mulai]').val(data.mulai); $('[name=selesai]').val(data.selesai);
            $('[name=lokasi]').val(data.lokasi); $('[name=penyelenggara]').val(data.penyelenggara);
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
    form.on('submit', function(e){ e.preventDefault(); let formData = new FormData(this); let method = $('#method').val(); if(method === 'PUT') formData.append('_method','PUT');
        $.ajax({ url: form.attr('action'), type: 'POST', data: formData, processData:false, contentType:false,
            success: res => { closeDialog(modal); table.ajax.reload(); Swal.fire('Berhasil', res.message, 'success'); },
            error: xhr => {
                const json = xhr.responseJSON || {};
                if(json.errors){ // show first validation error inline
                    $('.invalid-feedback').remove(); $('.is-invalid').removeClass('is-invalid');
                    Object.keys(json.errors).forEach(k => { const el = $('[name="'+k+'"]'); if(el.length){ el.addClass('is-invalid'); el.after(`<div class="invalid-feedback">${json.errors[k][0]}</div>`); } });
                } else {
                    Swal.fire('Error', json.message || 'Gagal menyimpan.', 'error');
                }
            }
        });
    });
</script>
@endpush

@endsection