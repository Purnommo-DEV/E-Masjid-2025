@extends('masjid.master')
@section('title', 'Manajemen Pengumuman')
@section('content')

@push('style')
<style>
  /* reuse visual style from Manajemen Berita reference */
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
  .card-header .title { margin:0; font-size:1.125rem; font-weight:700; color: white; }
  .card-body { padding: 1.25rem 1.5rem; background: white; }

  /* table tweaks */
  td.thumb { max-width: 120px; text-align:center; vertical-align: middle; }
  #gambarField { display: none; }

  /* modal/dialog styles (plain dialog like berita) */
  dialog.modal::backdrop {
      background: rgba(15,23,42,0.55);
      backdrop-filter: blur(4px) saturate(1.02);
  }
  dialog.modal { border: none; padding: 0; }
  .modal-panel { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); overflow:hidden; background:white; }

  /* preview: reduced so it's not too large */
  .preview-image-box { display:inline-block; border-radius:8px; overflow:hidden; border:1px solid #eee; padding:6px; background:#fff; }
  .preview-image-box img {
      display:block;
      width: 160px;        /* changed: fixed max display width */
      height: 90px;        /* maintain compact aspect */
      object-fit: cover;
      border-radius:6px;
  }
  /* small preview variant (used in table thumbnails) */
  .thumb img { width: 80px; height: 45px; object-fit: cover; border-radius:6px; }

  /* button/icon styles — consistent small circular icons */
  .btn-circle-ico { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; transition: transform .12s ease, background .12s; border:1px solid rgba(2,6,23,0.04); background: #fff; }
  .btn-circle-ico:hover { transform: translateY(-2px); box-shadow: 0 6px 14px rgba(2,6,23,0.06); }
  .btn-primary-solid {
      display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .9rem; border-radius:8px; background:#059669; color:#fff; border:none;
  }
  /* TinyMCE z-index */
  .tox-tinymce-aux, .tox-tinymce-inline, .tox-dialog { z-index: 999999 !important; }
  .tox { z-index: 99999 !important; }

  @media (max-width: 640px) {
      .card-header { padding: .9rem 1rem; }
      .card-body { padding: 1rem; }
  }
</style>
@endpush

<div class="card-wrapper">

  <div class="card-header">
    <div>
      <h3 class="title">Kelola Pengumuman & Banner</h3>
      <p class="subtitle" style="color:rgba(255,255,255,0.95);margin:0;font-size:.95rem;">Tambah, edit, hapus pengumuman atau banner popup</p>
    </div>

    <!-- tombol tambah: lebih jelas dan konsisten -->
    <button type="button" class="btn-primary-solid" onclick="addPengumuman()">
      <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden style="width:16px;height:16px;stroke:currentColor">
        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
      <span class="text-sm font-semibold">Tambah</span>
    </button>
  </div>

  <div class="card-body">
    <div class="overflow-x-auto">
      <table id="pengumumanTable" class="table table-zebra w-full text-sm" style="width:100%">
        <thead class="bg-emerald-50 text-emerald-900 font-semibold">
          <tr>
            <th class="px-4 py-3">Gambar</th>
            <th class="px-4 py-3">Judul</th>
            <th class="px-4 py-3">Tipe</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Periode</th>
            <th class="px-4 py-3 text-center">Aksi</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

</div>

<!-- Dialog modal (plain JS, consistent with Manajemen Berita) -->
<dialog id="pengumumanModal" class="modal" aria-labelledby="pengumumanModalTitle" role="dialog">
  <div class="modal-panel w-11/12 max-w-3xl">
    <form id="pengumumanForm" enctype="multipart/form-data" class="p-6 bg-white rounded-2xl">
      @csrf
      <input type="hidden" id="method" value="POST">

      <div class="flex items-start justify-between gap-4 mb-2">
        <div>
          <h3 id="pengumumanModalTitle" class="text-xl font-extrabold text-emerald-800">📝 Form Pengumuman</h3>
        </div>
        <div>
          <button type="button" id="closePengumumanModalBtn" class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Tutup">✕</button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-3">
          <label class="block text-sm font-medium text-emerald-700">Judul <span class="text-red-500">*</span></label>
          <input type="text" name="judul" id="judul" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm" required>

          <label class="block text-sm font-medium text-emerald-700">Tipe <span class="text-red-500">*</span></label>
          <select name="tipe" id="tipe" class="form-select w-full rounded-lg border border-emerald-100 px-2 py-2 shadow-sm" required>
            <option value="banner">Banner Slider</option>
            <option value="popup">Popup</option>
            <option value="notif">Notif Push</option>
          </select>

          <div id="gambarField" class="mt-2">
            <label class="block text-sm font-medium text-emerald-700">Gambar (Wajib untuk Banner)</label>
            <label for="gambarInput" class="cursor-pointer block rounded-xl border-2 border-dashed border-emerald-200 p-3 text-center hover:bg-emerald-50 transition">
              <div class="text-sm text-emerald-700 font-medium">Klik atau seret gambar ke sini (JPG/PNG • Maks 2MB)</div>
            </label>
            <input type="file" name="gambar" id="gambarInput" accept="image/*" class="hidden">
            <div id="previewGambar" class="mt-2 text-sm text-gray-500">Belum ada gambar.</div>
          </div>

          <div class="flex items-center gap-3 mt-2">
            <input type="checkbox" name="is_active" id="is_active" class="rounded text-emerald-600 focus:ring-emerald-400">
            <label for="is_active" class="text-sm text-gray-700">Aktif</label>
          </div>
        </div>

        <div>
          <label class="block text-sm font-medium text-emerald-700 mb-1">Isi Pengumuman</label>
          <textarea name="isi" id="isi" class="w-full h-full min-h-[300px] rounded-lg border border-emerald-100 p-2 shadow-sm" rows="12"></textarea>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
            <div>
              <label class="block text-sm font-medium text-emerald-700">Mulai</label>
              <input type="datetime-local" name="mulai" id="mulai" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm">
            </div>
            <div>
              <label class="block text-sm font-medium text-emerald-700">Selesai</label>
              <input type="datetime-local" name="selesai" id="selesai" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm">
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-end gap-3 mt-4">
        <button type="button" id="cancelPengumumanBtn" class="px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">Batal</button>
        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan</button>
      </div>

    </form>
  </div>
</dialog>

@endsection

@push('scripts')
<!-- libs (same as berita) -->
<script src="https://cdn.jsdelivr.net/npm/tinymce@5.10.7/tinymce.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
  let table = null;
  const modal = document.getElementById('pengumumanModal');
  const $modal = $('#pengumumanModal');
  const form = $('#pengumumanForm');

  // dialog helpers (reuse)
  function showDialog(d) {
      try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
      catch(e){ d.classList.add('modal-open'); }
  }
  function closeDialog(d) {
      try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
      catch(e){ d.classList.remove('modal-open'); }
  }

  // TinyMCE for isi
  tinymce.init({
      selector: '#isi',
      height: 360,
      menubar: 'edit view format',
      plugins: 'lists advlist link image paste preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount',
      toolbar: 'undo redo | formatselect | bold italic underline | alignleft aligncenter alignright | bullist numlist | link image | removeformat | code',
      branding: false,
      content_style: 'body { font-family:Arial,Helvetica,sans-serif; font-size:14px }',
      relative_urls: false,
      zindex: 99999
  });

  // image preview logic (smaller preview)
  $(document).on('change', '#gambarInput', function(e) {
      const file = e.target.files[0];
      const preview = $('#previewGambar');
      preview.empty();

      if (!file) {
          preview.html(`<div class="text-muted small">Belum ada gambar.</div>`);
          return;
      }

      const maxSize = 2 * 1024 * 1024; // 2MB
      if (file.size > maxSize) {
          Swal.fire('Ukuran terlalu besar!', 'Maksimal 2MB.', 'warning');
          $(this).val('');
          preview.html(`<div class="text-muted small">Belum ada gambar.</div>`);
          return;
      }

      if (!file.type.startsWith('image/')) {
          Swal.fire('File tidak valid!', 'Hanya file gambar diperbolehkan.', 'error');
          $(this).val('');
          preview.html(`<div class="text-muted small">Belum ada gambar.</div>`);
          return;
      }

      const reader = new FileReader();
      reader.onload = function(event) {
          // use preview-image-box with compact image size
          const html = `<div class="preview-image-box mb-2"><img src="${event.target.result}" alt="preview"></div>`;
          preview.append(html);
      };
      reader.readAsDataURL(file);
  });

  // initialize datatable
  $(function () {
      table = $('#pengumumanTable').DataTable({
          processing: true,
          ajax: '{{ route('admin.pengumuman.data') }}',
          columns: [
              {
                  data: 'gambar',
                  orderable: false,
                  className: 'thumb',
                  render: function(g) {
                      // render small thumbnail if available
                      if (!g) return '<small class="text-muted">-</small>';
                      // g may already be an <img> or URL depending on backend; handle both
                      if (/<img/i.test(g)) {
                          // ensure class thumb image styles
                          return g.replace(/<img\s+/i, '<img class="thumb-img" ');
                      }
                      return `<img src="${g}" alt="thumb" class="rounded" style="width:80px;height:45px;object-fit:cover;border-radius:6px">`;
                  }
              },
              { data: 'judul' },
              { data: 'tipe' },
              { data: 'status', orderable: false },
              { data: 'periode', orderable: false },
              {
                  data: null,
                  orderable: false,
                  className: 'text-center',
                  render: d => `
                      <div class="inline-flex gap-2">
                        <button class="btn-circle-ico" title="Edit" onclick="editPengumuman(${d.id})" aria-label="Edit pengumuman ${d.judul ?? ''}">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <button class="btn-circle-ico" title="Hapus" onclick="hapusPengumuman(${d.id})" aria-label="Hapus pengumuman ${d.judul ?? ''}">
                          <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                      </div>
                  `
              }
          ],
          responsive: true,
          language: { processing: "Memuat..." }
      });

      // modal control bindings
      $('#closePengumumanModalBtn').on('click', () => closeDialog(modal));
      $('#cancelPengumumanBtn').on('click', () => closeDialog(modal));
      if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

      // ensure tipe field toggles gambar field on init
      $('#tipe').on('change', toggleGambarField);
  });

  // add Pengumuman
  window.addPengumuman = function () {
      form[0].reset();
      $('.is-invalid').removeClass('is-invalid');
      $('.invalid-feedback').remove();
      tinymce.get('isi')?.setContent('');
      $('#method').val('POST');
      form.attr('action', '{{ route('admin.pengumuman.store') }}');
      $('#pengumumanModalTitle').text('Tambah Pengumuman');
      $('#previewGambar').empty().html('<div class="text-muted small">Belum ada gambar.</div>');
      toggleGambarField();
      showDialog(modal);
      setTimeout(()=> $('[name=judul]').focus(), 120);
  };

  // edit pengumuman
  window.editPengumuman = function (id) {
      $.get(`{{ url('admin/pengumuman') }}/${id}`)
      .done(function (data) {
          form[0].reset();
          $('.is-invalid').removeClass('is-invalid');
          $('.invalid-feedback').remove();
          $('#previewGambar').empty();
          $('#method').val('PUT');
          form.attr('action', `{{ url('admin/pengumuman') }}/${id}`);

          $('#judul').val(data.judul || '');
          tinymce.get('isi')?.setContent(data.isi || '');
          $('#tipe').val(data.tipe || 'banner').trigger('change');
          $('#mulai').val(data.mulai ?? '');
          $('#selesai').val(data.selesai ?? '');
          $('#is_active').prop('checked', !!data.is_active);

          if (data.gambar_url) {
              // smaller preview when editing
              $('#previewGambar').html(`<div class="preview-image-box"><img src="${data.gambar_url}" alt="preview"></div>`);
          } else {
              $('#previewGambar').html('<div class="text-muted small">Tidak ada gambar.</div>');
          }

          toggleGambarField();
          $('#pengumumanModalTitle').text(data.judul ? `Edit: ${data.judul}` : 'Edit Pengumuman');
          showDialog(modal);
          setTimeout(()=> $('[name=judul]').focus(), 120);
      })
      .fail(function () {
          Swal.fire('Error', 'Gagal memuat data pengumuman.', 'error');
      });
  };

  // delete
  window.hapusPengumuman = function (id) {
      Swal.fire({
          title: 'Yakin?',
          text: 'Data akan dihapus!',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, Hapus!'
      }).then(result => {
          if (result.isConfirmed) {
              $.ajax({
                  url: `{{ url('admin/pengumuman') }}/${id}`,
                  type: 'DELETE',
                  data: { _token: '{{ csrf_token() }}' },
                  success: () => {
                      table.ajax.reload();
                      Swal.fire('Berhasil!', 'Dihapus.', 'success');
                  },
                  error: () => Swal.fire('Error', 'Gagal menghapus data.', 'error')
              });
          }
      });
  };

  // toggle gambar field: only show when tipe === 'banner'
  function toggleGambarField() {
      const show = $('#tipe').val() === 'banner';
      if (show) $('#gambarField').show();
      else { $('#gambarField').hide(); $('#gambarInput').val(''); $('#previewGambar').empty().html('<div class="text-muted small">Belum ada gambar.</div>'); }
  }

  // submit form
  form.on('submit', function (e) {
      e.preventDefault();
      let formData = new FormData(this);
      let method = $('#method').val();
      let action = form.attr('action');
      if (method === 'PUT') formData.append('_method', 'PUT');
      formData.append('isi', tinymce.get('isi')?.getContent() ?? '');

      $.ajax({
          url: action,
          type: 'POST',
          data: formData,
          processData: false,
          contentType: false,
          success: function (res) {
              closeDialog(modal);
              table.ajax.reload();
              Swal.fire('Berhasil!', res.message || 'Tersimpan.', 'success');
          },
          error: function (xhr) {
              $('.is-invalid').removeClass('is-invalid');
              $('.invalid-feedback').remove();

              let errors = xhr.responseJSON?.errors;
              if (errors) {
                  $.each(errors, function (key, messages) {
                      let input = $('[name="' + key + '"]');
                      if (!input.length) input = $('[name="' + key + '[]"]');
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
