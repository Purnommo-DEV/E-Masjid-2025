@extends('masjid.master')
@section('title', 'Alokasi Dana Terikat')

@section('content')

@push('style')
<style>
    /* --- Reuse Berita styles so Alokasi matches exactly --- */

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
        transition: transform .12s ease, background .12s, box-shadow .12s;
        cursor: pointer;
        font-weight: 600;
    }
    .header-action svg { display:block; }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); box-shadow: 0 10px 26px rgba(4,120,87,0.09); }

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
    .modal-panel { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); overflow:hidden; background:white; position: relative; z-index: 60; }

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

    /* TinyMCE z-index (if needed later) */
    .tox-tinymce-aux, .tox-tinymce-inline, .tox-dialog { z-index: 999999 !important; }
    .tox { z-index: 99999 !important; }

    /* select2 z-index fixes so dropdown shows above dialog/backdrop */
    .select2-container { z-index: 999999 !important; }
    .select2-dropdown { z-index: 999999 !important; }

    /* validation */
    .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 1px rgba(220,53,69,0.06) !important; }
    .invalid-feedback { display:block; color:#dc3545; font-size:.875rem; margin-top:6px; }

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
    <div class="card-header" role="banner">
        <div>
            <h3 class="title">Alokasi Dana Terikat</h3>
            <p class="subtitle">Catat pemindahan dana dari akun sumber ke akun dana terikat</p>
        </div>

        <button type="button" class="header-action" onclick="addAlokasi()" aria-haspopup="dialog" aria-controls="alokasiModal">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Alokasi Baru</span>
        </button>
    </div>

    <!-- body -->
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="tabelAlokasi" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Dari Akun</th>
                        <th class="px-4 py-3">Ke Akun (Dana Terikat)</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Nama Pembuat</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal: Alokasi (dialog plain JS styled like Berita modal) -->
<dialog id="alokasiModal" class="modal" aria-labelledby="alokasiModalTitle" role="dialog">
  <div class="modal-panel w-11/12 max-w-3xl">
    <form id="formAlokasi" class="p-6 bg-white rounded-2xl shadow-xl border border-emerald-50" novalidate>
      @csrf
      <input type="hidden" id="methodAlokasi" value="POST">

      <!-- Header -->
      <div class="flex items-start justify-between gap-4 mb-2">
        <div>
          <h3 id="alokasiModalTitle" class="text-xl font-extrabold text-emerald-800">💸 Buat Alokasi Dana</h3>
          <p class="text-sm text-slate-500 mt-1">Isi detail pemindahan dana ke akun dana terikat</p>
        </div>
        <div class="flex items-center gap-2">
          <button type="button" id="closeAlokasiModalBtn" class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Tutup">
            ✕
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="space-y-4">
          <label class="block text-sm font-medium text-emerald-700">Dari Akun Sumber <span class="text-red-500">*</span></label>
          <select name="akun_sumber_id" id="akunSumber" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm select2" required>
              <option value="">— Pilih Akun Sumber —</option>
              @foreach(\App\Models\AkunKeuangan::whereIn('tipe',['pendapatan','aset'])->get() as $a)
                <option value="{{ $a->id }}">{{ $a->kode }} - {{ $a->nama }}</option>
              @endforeach
          </select>

          <label class="block text-sm font-medium text-emerald-700">Ke Akun Tujuan (Dana Terikat) <span class="text-red-500">*</span></label>
          <select name="akun_tujuan_id" id="akunTujuan" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm select2" required>
              <option value="">— Pilih Dana Terikat —</option>
              @foreach(\App\Models\AkunKeuangan::where('tipe','ekuitas')->where('nama','like','%dana%')->get() as $a)
                <option value="{{ $a->id }}">{{ $a->kode }} - {{ $a->nama }}</option>
              @endforeach
          </select>

          <label class="block text-sm font-medium text-emerald-700">Jumlah (Rp) <span class="text-red-500">*</span></label>
          <div class="flex items-center gap-2">
            <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-emerald-100 bg-emerald-50 text-emerald-700">Rp</span>
            <input type="text" name="jumlah" id="jumlahAlokasi" class="flex-1 rounded-r-lg border border-emerald-100 px-3 py-2 shadow-sm text-right ribuan" placeholder="0" required>
          </div>

          <label class="block text-sm font-medium text-emerald-700">Tanggal</label>
          <input type="date" name="tanggal" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm" value="{{ date('Y-m-d') }}">
        </div>

        <div>
          <label class="block text-sm font-medium text-emerald-700 mb-1">Keterangan</label>
          <textarea name="keterangan" rows="10" class="w-full rounded-lg border border-emerald-100 p-2 shadow-sm" placeholder="Contoh: Alokasi infak untuk pembangunan mushola" required></textarea>

          <div class="mt-4 flex items-center gap-3">
            <input type="checkbox" name="is_internal" id="isInternal" class="rounded text-emerald-600 focus:ring-emerald-300">
            <label for="isInternal" class="text-sm text-gray-700">Catatan internal (tidak ditampilkan publik)</label>
          </div>
        </div>
      </div>

      <!-- Footer actions -->
      <div class="flex items-center justify-end gap-3 mt-4">
        <button type="button" id="cancelAlokasiBtn" class="px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-50">Batal</button>
        <button type="submit" id="submitAlokasiBtn" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow">Simpan</button>
      </div>
    </form>
  </div>
</dialog>

@endsection

@push('scripts')
<!-- libs (keep same versions you have in project) -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let tableAlokasi = null;
    const modal = document.getElementById('alokasiModal');
    const form = $('#formAlokasi');

    // dialog helpers (fallback)
    function showDialog(d) {
        try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
        catch(e){ d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
        catch(e){ d.classList.remove('modal-open'); }
    }

    $(function () {
        // --- INIT SELECT2 with dropdownParent = modal panel (important for dialog) ---
        if ($.fn.select2) {
            const $dropdownParent = $('#alokasiModal .modal-panel');
            // if not found, fallback to body
            const dp = $dropdownParent.length ? $dropdownParent : $(document.body);

            $('.select2').each(function() {
                // initialize each select2 individually to avoid issues
                $(this).select2({
                    width: '100%',
                    dropdownParent: dp,
                    placeholder: $(this).find('option:first').text() || 'Pilih...',
                    allowClear: true
                });
            });
        }

        // initialize datatable
        tableAlokasi = $('#tabelAlokasi').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("admin.keuangan.alokasi-dana.data") }}',
            columns: [
                { data:'tanggal' },
                { data:'dari_akun' },
                { data:'ke_akun' },
                { data:'jumlah', className: 'text-right' },
                { data:'keterangan' },
                { data:'user' }
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
        $('#closeAlokasiModalBtn').on('click', () => closeDialog(modal));
        $('#cancelAlokasiBtn').on('click', () => closeDialog(modal));
        if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

        // format ribuan input
        function formatRibuan(angka) {
            angka = angka.replace(/\D/g, '');
            if (angka === '') return '';
            return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        }
        $(document).on('input', '.ribuan', function() {
            const before = this.value;
            const onlyNumber = before.replace(/\D/g, '');
            if (onlyNumber === '') {
                this.value = '';
                return;
            }
            this.value = formatRibuan(onlyNumber);
        });
    });

    // open modal to add alokasi
    window.addAlokasi = function () {
        form[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#methodAlokasi').val('POST');
        form.attr('action', '{{ route("admin.keuangan.alokasi-dana.store") }}');
        $('#alokasiModalTitle').text('Tambah Alokasi Dana');

        // close any open select2 dropdowns safely
        if ($.fn.select2) {
            $('.select2').each(function() {
                if ($(this).data('select2')) {
                    try { $(this).select2('close'); } catch (e) {}
                }
            });
        }

        showDialog(modal);

        // focus first field when modal open - if select2, open its dropdown (safely)
        setTimeout(()=> {
            const $first = $('[name="akun_sumber_id"]');
            if ($first.length && $.fn.select2 && $first.data('select2')) {
                try { $first.select2('open'); }
                catch(e) { $first.trigger('focus'); }
            } else {
                $first.trigger('focus');
            }
        }, 160);
    };

    // submit alokasi via AJAX
    form.on('submit', function (e) {
        e.preventDefault();
        let formData = $(this).serializeArray();

        // cleanup ribuan fields
        formData = formData.map(f => {
            if (f.name === 'jumlah') {
                f.value = f.value.replace(/\D/g,'');
            }
            return f;
        });

        const payload = {};
        formData.forEach(f => payload[f.name] = f.value);

        const $btn = $('#submitAlokasiBtn');
        $btn.prop('disabled', true).addClass('opacity-80');

        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: payload,
            success: function (res) {
                closeDialog(modal);
                tableAlokasi.ajax.reload();
                Swal.fire('Berhasil!', res.message || 'Alokasi dana berhasil disimpan.', 'success');
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
            },
            complete: function () {
                $btn.prop('disabled', false).removeClass('opacity-80');
            }
        });
    });
</script>
@endpush
