@extends('masjid.master')
@section('title', 'Daftar Akun Keuangan')
@section('content')

@push('style')
<style>
/* ---------- Card / Layout ---------- */
.container-max {
    max-width: 1200px;
    margin: 1.25rem auto;
    padding: 0 1rem;
}
.card {
    border-radius: 1rem;
    overflow: hidden;
    background: #fff;
    border: 1px solid rgba(15,23,42,0.04);
    box-shadow: 0 10px 30px rgba(2,6,23,0.06);
}

/* header */
.card-header {
    display:flex;
    gap:1rem;
    align-items:center;
    justify-content:space-between;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(90deg,#059669 0%,#10b981 100%);
    color: #fff;
}
.card-header .meta {
    display:flex;
    flex-direction:column;
    gap:0.2rem;
}
.card-header h4 { margin:0; font-size:1.125rem; font-weight:700; display:flex; align-items:center; gap:.6rem; }
.card-header p { margin:0; opacity:.95; font-size:.92rem; }

/* header action */
.header-action {
    display:inline-flex;
    gap:.6rem;
    align-items:center;
    padding:.5rem .85rem;
    border-radius:999px;
    background: rgba(255,255,255,0.12);
    border:1px solid rgba(255,255,255,0.08);
    box-shadow: 0 6px 14px rgba(4,120,87,0.06);
    color: #fff;
    cursor: pointer;
    transition: transform .12s ease, background .12s ease;
}
.header-action:focus { outline: 3px solid rgba(16,185,129,0.18); }
.header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }

/* body / table */
.card-body { padding: 1.25rem 1.5rem; background: #fff; }
.table-responsive { width:100%; overflow:auto; }
.table thead th { font-weight:700; letter-spacing:.2px; }

/* badges (small utility) */
.badge {
    display:inline-block;
    padding:.28rem .5rem;
    border-radius:999px;
    font-size:.78rem;
    font-weight:600;
}

/* action buttons (modern) */
.action-btn {
    display:inline-grid;
    place-items:center;
    width:36px;
    height:36px;
    border-radius:8px;
    border: none;
    background: #fff;
    box-shadow: 0 6px 14px rgba(2,6,23,0.04);
    cursor:pointer;
    transition: transform .12s ease, box-shadow .12s ease;
}
.action-btn:active { transform: translateY(1px); }
.action-btn .icon { width:16px; height:16px; display:block; }

/* semantic color variants */
.btn-edit { background:#fff7ed; color:#92400e; }
.btn-delete { background:#fff1f2; color:#7f1d1d; }
.btn-info { background:#ecfeff; color:#064e3b; }

/* dialog / modal */
dialog.modal::backdrop {
    background: rgba(15,23,42,0.55);
    backdrop-filter: blur(4px) saturate(1.02);
}
dialog.modal { border: none; padding: 0; z-index: 1000; }
.modal-panel { border-radius:12px; overflow:hidden; box-shadow: 0 18px 40px rgba(2,6,23,0.12); background:#fff; width:100%; }

/* modal sections */
.modal-header {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:.5rem;
    padding:1rem 1.25rem;
    background: linear-gradient(90deg,#059669 0%,#10b981 100%);
    color:#fff;
}
.modal-body { padding:1rem 1.25rem; background:#fff; }
.modal-footer { padding:.75rem 1.25rem; display:flex; gap:.5rem; justify-content:flex-end; background:#fff; }

/* form grid */
.form-row {
    display:grid;
    grid-template-columns: repeat(2, minmax(0,1fr));
    gap:1rem;
    margin-bottom: .75rem;
}
@media (max-width:768px) { .form-row { grid-template-columns: 1fr; } }

.form-label { display:block; margin-bottom:.35rem; font-weight:600; font-size:.95rem; }
.form-control, .form-select, textarea {
    width:100%;
    border-radius:10px;
    border:1px solid #e6edf3;
    padding:.55rem .9rem;
    font-size:.95rem;
    height: calc(2.25rem + 0.6rem);
}
textarea.form-control { min-height:110px; height:auto; padding-top:.6rem; padding-bottom:.6rem; }
.help-text { font-size:.85rem; color:#6b7280; margin-top:.25rem; }

/* validation */
.is-invalid { border-color:#dc3545 !important; }
.invalid-feedback { color:#dc3545; font-size:.875rem; margin-top:.25rem; display:block; }

/* primary button */
.btn-primary {
    background: linear-gradient(90deg,#059669 0%,#10b981 100%);
    border: none;
    color: #fff;
    padding:.5rem .85rem;
    border-radius:8px;
    box-shadow: 0 6px 18px rgba(16,185,129,0.12);
}
.btn-secondary { background:#f3f4f6; border:1px solid #e6edf3; padding:.45rem .75rem; border-radius:8px; }

/* ---------- New: truncate & auto-shrink for long cells ---------- */
/* wrapper for long text inside table cells */
.td-long {
    display: inline-block;
    max-width: 220px; /* desktop default */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    vertical-align: middle;
}

/* smaller font when text considered "long" */
.td-long.small-text { font-size: .82rem; }

/* responsive: reduce max-width on smaller screens so ellipsis takes effect */
@media (max-width: 1024px) {
    .td-long { max-width: 160px; }
}
@media (max-width: 640px) {
    .td-long { max-width: 120px; }
}

/* also allow wrapping for some columns on very small screens */
.table td.wrap-allow { white-space: normal; word-break: break-word; }

/* Ensure SweetAlert2 is always on top of dialog */
.swal2-container, .swal2-popup {
    z-index: 9999999 !important;
}

/* responsive tweaks */
@media (max-width:640px) {
    .card-header { padding:.9rem 1rem; }
    .card-body { padding:1rem; }
    .modal-panel { max-width: 95vw; margin: 0 auto; }
}
</style>
@endpush

<div class="container-max">
    <div class="card" aria-live="polite">
        <!-- header -->
        <div class="card-header">
            <div class="meta">
                <h4 aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M3 7h18M3 12h18M3 17h18" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    Daftar Akun Keuangan (Chart of Accounts)
                </h4>
                <p class="subtitle">Tambah, sunting, dan kelola akun-akun keuangan</p>
            </div>

            <button id="btnTambahAkun" class="header-action" aria-haspopup="dialog" aria-controls="modalAkun" type="button">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span style="font-weight:700">Tambah Akun</span>
            </button>
        </div>

        <!-- body -->
        <div class="card-body">
            <div class="table-responsive">
                <table id="tabelAkun" class="table table-hover table-bordered" width="100%">
                    <thead class="table-dark">
                        <tr>
                            <th>Kode</th>
                            <th>Nama Akun</th>
                            <th>Tipe</th>
                            <th>Saldo Normal</th>
                            <th>Jenis Beban</th>
                            <th>Grup</th>
                            <th>Status</th>
                            <th style="min-width:120px;">Aksi</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal (dialog element) -->
<dialog id="modalAkun" class="modal" aria-labelledby="modalAkunTitle" role="dialog">
  <div class="modal-panel max-w-3xl" style="width:95%; max-width:720px;">
    <form id="formAkun" class="bg-white" autocomplete="off" novalidate>
      @csrf
      <input type="hidden" name="id" id="akun_id">

      <div class="modal-header">
        <h5 id="modalAkunTitle" style="margin:0; font-weight:700; font-size:1rem;">Tambah Akun Baru</h5>
        <button type="button" id="closeModalAkunBtn" aria-label="Tutup">✕</button>
      </div>

      <div class="modal-body">
        <div class="form-row">
            <div>
                <label class="form-label" for="kode">Kode Akun <span style="color:#dc3545">*</span></label>
                <input type="text" id="kode" name="kode" class="form-control" placeholder="Contoh: 1.01.001" required>
                <div class="invalid-feedback" data-for="kode" style="display:none"></div>
            </div>
            <div>
                <label class="form-label" for="nama">Nama Akun <span style="color:#dc3545">*</span></label>
                <input type="text" id="nama" name="nama" class="form-control" placeholder="Contoh: Kas Besar" required>
                <div class="invalid-feedback" data-for="nama" style="display:none"></div>
            </div>
        </div>

        <div class="form-row">
            <div>
                <label class="form-label" for="tipe">Tipe Akun <span style="color:#dc3545">*</span></label>
                <select id="tipe" name="tipe" class="form-select" required aria-required="true">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="aset">Aset</option>
                    <option value="liabilitas">Liabilitas</option>
                    <option value="ekuitas">Ekuitas</option>
                    <option value="pendapatan">Pendapatan</option>
                    <option value="beban">Beban</option>
                </select>
                <div class="invalid-feedback" data-for="tipe" style="display:none"></div>
            </div>
            <div>
                <label class="form-label" for="saldo_normal">Saldo Normal <span style="color:#dc3545">*</span></label>
                <select id="saldo_normal" name="saldo_normal" class="form-select" required>
                    <option value="debit">Debit</option>
                    <option value="kredit">Kredit</option>
                </select>
                <div class="invalid-feedback" data-for="saldo_normal" style="display:none"></div>
            </div>
        </div>

        <!-- Combined control area: will show combined select when tipe='beban'
             and show regular Grup select when tipe != 'beban' -->
        <div id="divGrupCombined" style="margin-bottom:.75rem;">
            <!-- visible Grup select for non-beban (no name so it won't duplicate) -->
            <div id="grup_wrapper">
                <label class="form-label" for="grup_select">Grup Akun (Opsional)</label>
                <select id="grup_select" class="form-select">
                    <option value="">— Tidak ada grup khusus —</option>
                    <option value="kotak_infak">Kotak Infak (Jumat, Kajian, Ramadhan, Qurban)</option>
                    <option value="zakat">Zakat & Fidyah</option>
                    <option value="donasi_besar">Donasi Besar / QRIS / Transfer</option>
                </select>
                <span class="help-text">Pilih grup agar akun otomatis muncul di form transaksi yang tepat</span>
                <div class="invalid-feedback" data-for="grup_select" style="display:none"></div>
            </div>

            <!-- combined select (only for tipe === 'beban') - no name -->
            <div id="combined_wrapper" style="display:none;">
                <label class="form-label" for="grup_jenis_combined">Grup / Jenis Beban <span style="color:#dc3545" id="combined-required-marker" hidden>*</span></label>
                <select id="grup_jenis_combined" class="form-select" aria-describedby="combinedHelp" >
                    <option value="">-- Pilih Grup atau Jenis Beban --</option>
                    <optgroup label="Jenis Beban">
                        <option value="jenis:kecil">Beban Kecil → Petty Cash (≤ Rp 500.000)</option>
                        <option value="jenis:besar">Beban Besar → Pengeluaran Umum</option>
                    </optgroup>
                    <optgroup label="Grup Akun (opsional)">
                        <option value="grup:kotak_infak">Kotak Infak (Jumat, Kajian, Ramadhan, Qurban)</option>
                        <option value="grup:zakat">Zakat & Fidyah</option>
                        <option value="grup:donasi_besar">Donasi Besar / QRIS / Transfer</option>
                    </optgroup>
                </select>
                <span id="combinedHelp" class="help-text">
                    Pilih <strong>Jenis Beban</strong> (wajib untuk akun beban) atau pilih <strong>Grup Akun</strong> (opsional).
                </span>
                <div class="invalid-feedback" data-for="grup_jenis_combined" style="display:none"></div>
            </div>

            <!-- Hidden inputs that actually will be sent to server
                 jenis_beban DISABLED by default so it won't be submitted unless enabled -->
            <input type="hidden" id="jenis_beban" name="jenis_beban" value="" disabled>
            <input type="hidden" id="grup" name="grup" value="">
        </div>

        <div style="margin-bottom:.25rem;">
            <label class="form-label" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" class="form-control" placeholder="Catatan tambahan (opsional)"></textarea>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn-secondary" id="cancelModalAkunBtn">Batal</button>
        <button type="submit" id="btnSimpan" class="btn-primary" aria-live="polite">
            <span id="btnSimpanText">&nbsp;Simpan</span>
        </button>
      </div>
    </form>
  </div>
</dialog>

@endsection

@push('scripts')
<!-- Pastikan layout memuat jQuery, DataTables, SweetAlert2. Tetap menggunakan DataTables + jQuery -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
$(function () {
    'use strict';

    /* ---------- selectors ---------- */
    const $modal = $('#modalAkun')[0];
    const $form = $('#formAkun');
    const $table = $('#tabelAkun');
    const $btnTambah = $('#btnTambahAkun');
    const $btnSimpan = $('#btnSimpan');
    const $btnSimpanText = $('#btnSimpanText');

    const $jenisBebanHidden = $('#jenis_beban');
    const $grupHidden = $('#grup');

    const $grupSelect = $('#grup_select'); // visible for non-beban (no name)
    const $combinedSelect = $('#grup_jenis_combined'); // visible for beban (no name)
    const $combinedWrapper = $('#combined_wrapper');
    const $grupWrapper = $('#grup_wrapper');
    const $combinedFeedback = $('[data-for="grup_jenis_combined"]');
    const $combinedRequiredMarker = $('#combined-required-marker');

    /* ---------- dialog helpers (with fallback) ---------- */
    function showDialog(dialogEl) {
        try {
            if (typeof dialogEl.showModal === 'function') dialogEl.showModal();
            else $(dialogEl).addClass('modal-open');
        } catch (e) { $(dialogEl).addClass('modal-open'); }
    }
    function closeDialog(dialogEl) {
        try {
            if (typeof dialogEl.close === 'function') dialogEl.close();
            else $(dialogEl).removeClass('modal-open');
        } catch (e) { $(dialogEl).removeClass('modal-open'); }
    }

    /* ---------- helper: escape HTML (basic) ---------- */
    function escapeHtml(text) {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    /* ---------- helper: wrap long text with truncation + optional small font ---------- */
    function wrapLong(text, limit = 30) {
        const safe = escapeHtml(text);
        const isLong = safe.length > limit;
        const cls = isLong ? 'td-long small-text' : 'td-long';
        return `<span class="${cls}" title="${safe}">${safe}</span>`;
    }

    /* ---------- initialize DataTable ---------- */
    const table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.akun.data") }}',
        columns: [
            { data: 'kode', render: d => `<span class="td-long">${escapeHtml(d)}</span>` },
            { data: 'nama', render: d => wrapLong(d, 32) },
            { data: 'tipe', render: d => `<span class="badge badge-info text-capitalize">${escapeHtml(d)}</span>` },
            { data: 'saldo_normal', render: d => d === 'debit' ? '<span class="badge badge-primary">Debit</span>' : '<span class="badge badge-danger">Kredit</span>' },
            { data: 'jenis_beban', render: renderJenisBeban },
            { data: 'grup', render: d => wrapLong(renderGrupRaw(d), 26) },
            { data: 'is_active', render: d => d ? '<span class="badge badge-success">Aktif</span>' : '<span class="badge badge-secondary">Nonaktif</span>' },
            { data: 'id', orderable:false, searchable:false, render: renderActions }
        ],
        language: { processing: '<i class="fas fa-spinner fa-spin"></i>' },
        responsive: true,
        order: [[0, 'asc']]
    });

    /* helper renderers */
    function renderJenisBeban(d) {
        if (!d || d === 'tidak_berlaku') return '<span class="badge badge-secondary">–</span>';
        const v = String(d).toLowerCase().trim();
        if (v === 'kecil') return '<span class="badge badge-success">Kecil (Petty Cash)</span>';
        if (v === 'besar') return '<span class="badge badge-warning text-dark">Besar (Pengeluaran Umum)</span>';
        return `<span class="badge badge-secondary">${escapeHtml(d)}</span>`;
    }
    function renderGrupRaw(data) {
        if (!data) return '–';
        if (data === 'kotak_infak') return 'Kotak Infak (Jumat, Kajian, Ramadhan, Qurban)';
        if (data === 'zakat') return 'Zakat & Fidyah';
        return 'Donasi Besar / QRIS / Transfer';
    }

    function renderActions(id) {
        return `
            <button class="action-btn btn-edit me-1 edit-btn" aria-label="Edit akun ${id}" data-id="${id}" title="Edit">
                <span class="icon">${editIcon()}</span>
            </button>
            <button class="action-btn btn-delete delete-btn" aria-label="Hapus akun ${id}" data-id="${id}" title="Hapus">
                <span class="icon">${trashIcon()}</span>
            </button>
        `;
    }
    function editIcon() {
        return '<svg viewBox="0 0 24 24" fill="none" width="16" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M4 21h4l10.5-10.5a2.121 2.121 0 0 0 0-3L17.5 3.5a2.121 2.121 0 0 0-3 0L4 14v7z" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }
    function trashIcon() {
        return '<svg viewBox="0 0 24 24" fill="none" width="16" height="16" xmlns="http://www.w3.org/2000/svg"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 11v6M14 11v6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    /* ---------- toggle combined/group display (perbaikan: enable/disable hidden inputs) ---------- */
    function toggleGrupCombined() {
        const tipe = $('#tipe').val();
        clearCombinedErrors();

        if (tipe === 'beban') {
            // show combined, hide simple grup select
            $grupWrapper.hide();
            $combinedWrapper.show();
            $combinedSelect.prop('disabled', false).val('');
            $combinedRequiredMarker.show();

            // disable hidden grup initially (will be enabled if user picks grup)
            $grupHidden.val('').prop('disabled', true);
            // disable jenis by default until user picks jenis option
            $jenisBebanHidden.val('').prop('disabled', true).prop('required', false);
        } else {
            // non beban -> show grup select visible, hide combined
            $combinedWrapper.hide();
            $grupWrapper.show();
            $combinedSelect.prop('disabled', true).val('');
            $combinedRequiredMarker.hide();

            // enable and sync hidden grup from visible select so it's submitted
            $grupHidden.val($grupSelect.val() || '').prop('disabled', false);
            // ensure jenis is disabled so it is NOT submitted
            $jenisBebanHidden.val('').prop('disabled', true).prop('required', false);
        }
    }

    $('#tipe').on('change', toggleGrupCombined);

    /* combined select change: set + enable/disable hidden inputs correctly */
    $combinedSelect.on('change', function () {
        const val = $(this).val() || '';
        clearCombinedErrors();

        // reset first
        $jenisBebanHidden.val('').prop('disabled', true).prop('required', false);
        $grupHidden.val('').prop('disabled', true);

        if (!val) {
            return;
        }

        if (val.indexOf('jenis:') === 0) {
            const jenisVal = val.split(':')[1];
            $jenisBebanHidden.val(jenisVal).prop('disabled', false).prop('required', true);
            $grupHidden.val('').prop('disabled', true);
        } else if (val.indexOf('grup:') === 0) {
            const grupVal = val.split(':')[1];
            $grupHidden.val(grupVal).prop('disabled', false);
            $jenisBebanHidden.val('').prop('disabled', true).prop('required', false);
        }
    });

    /* when visible grup_select changes, copy to hidden so it gets submitted for non-beban */
    $grupSelect.on('change', function () {
        $grupHidden.val($(this).val() || '');
    });

    function clearCombinedErrors() {
        $combinedSelect.removeClass('is-invalid');
        $grupHidden.removeClass('is-invalid');
        $jenisBebanHidden.removeClass('is-invalid');
        $combinedFeedback.hide().text('');
        $('[data-for="grup_select"]').hide().text('');
    }

    /* ---------- open add modal ---------- */
    $btnTambah.on('click', function () {
        resetForm();
        $('#modalAkunTitle').text('Tambah Akun Baru');
        showDialog($modal);
        setTimeout(() => $('#kode').focus(), 120);
    });

    /* modal close connects */
    $('#closeModalAkunBtn, #cancelModalAkunBtn').on('click', () => closeDialog($modal));
    if ($modal) $modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog($modal); });

    /* ---------- Edit row ---------- */
    $(document).on('click', '.edit-btn', function () {
        const id = $(this).data('id');
        if (!id) return;
        $.get(`{{ url("admin/keuangan/akun") }}/${id}`)
            .done(function (data) {
                fillForm(data);
                $('#modalAkunTitle').text('Edit Akun: ' + (data.nama || ''));
                showDialog($modal);
                setTimeout(() => $('#nama').focus(), 120);
            })
            .fail(function () {
                Swal.fire({ icon:'error', title:'Error', text:'Gagal memuat data akun.' });
            });
    });

    /* ---------- Delete row ---------- */
    $(document).on('click', '.delete-btn', function () {
        const id = $(this).data('id');
        if (!id) return;
        Swal.fire({
            title: 'Yakin hapus?', icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `{{ route("admin.keuangan.akun.destroy", ":id") }}`.replace(':id', id),
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: () => { table.ajax.reload(null, false); Swal.fire({ icon:'success', title:'Terhapus!' }); },
                error: () => Swal.fire({ icon:'error', title:'Error', text:'Gagal menghapus akun.' })
            });
        });
    });

    /* ---------- form helpers ---------- */
    function resetForm() {
        $form[0].reset();
        $('#akun_id').val('');
        clearValidation();
        toggleGrupCombined();
    }
    function fillForm(data = {}) {
        $('#akun_id').val(data.id || '');
        $('#kode').val(data.kode || '');
        $('#nama').val(data.nama || '');
        $('#tipe').val(data.tipe || '');
        $('#saldo_normal').val(data.saldo_normal || 'debit');
        $('#keterangan').val(data.keterangan || '');

        // populate visible grup_select (no name)
        $grupSelect.val(data.grup || '');
        // hidden fields initial values (but disabled state handled in toggleGrupCombined)
        $grupHidden.val(data.grup || '');
        $jenisBebanHidden.val(data.jenis_beban && data.jenis_beban !== 'tidak_berlaku' ? data.jenis_beban : '');

        // toggle visibility and then set combined selection accordingly
        toggleGrupCombined();
        if ((data.tipe || '') === 'beban') {
            if (data.jenis_beban && data.jenis_beban !== 'tidak_berlaku') {
                $combinedSelect.val('jenis:' + data.jenis_beban).trigger('change');
            } else if (data.grup) {
                $combinedSelect.val('grup:' + data.grup).trigger('change');
            } else {
                $combinedSelect.val('').trigger('change');
            }
        } else {
            // non beban: ensure hidden grup in sync
            $grupHidden.val($grupSelect.val() || '').prop('disabled', false);
        }

        clearValidation();
    }
    function clearValidation() {
        $form.find('.is-invalid').removeClass('is-invalid');
        $form.find('.invalid-feedback').hide().text('');
        clearCombinedErrors();
    }

    /* ---------- submit (create/update) ---------- */
    $form.on('submit', function (e) {
        e.preventDefault();
        clearValidation();

        const id = $('#akun_id').val();
        const url = id
            ? `{{ route("admin.keuangan.akun.update", ":id") }}`.replace(':id', id)
            : `{{ route("admin.keuangan.akun.store") }}`;
        const method = id ? 'PUT' : 'POST';

        // ensure hidden inputs reflect visible state:
        if ($('#tipe').val() === 'beban') {
            // hidden inputs already set by combinedSelect change handler
        } else {
            // copy visible grup_select into hidden grup
            $grupHidden.val($grupSelect.val() || '').prop('disabled', false);
            // ensure jenis hidden empty & disabled
            $jenisBebanHidden.val('').prop('disabled', true).prop('required', false);
        }

        // UI: disable button
        $btnSimpan.prop('disabled', true);
        $btnSimpanText.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Menyimpan...');

        $.ajax({
            url: url,
            method: method,
            data: $form.serialize(),
            success: function (res) {
                closeDialog($modal);
                table.ajax.reload(null, false);
                Swal.fire({ icon:'success', title:'Sukses!', text: res.message || 'Akun berhasil disimpan!' });
            },
            error: function (xhr) {
                const json = xhr.responseJSON || {};
                const errors = json.errors || null;

                if (errors) {
                    // Field-level errors
                    Object.keys(errors).forEach(function (key) {
                        const msg = errors[key][0] || 'Invalid';
                        if (key === 'jenis_beban' || key === 'grup') {
                            // show under combined control
                            $combinedSelect.addClass('is-invalid');
                            $combinedFeedback.show().text(msg);
                            if (key === 'jenis_beban') $jenisBebanHidden.addClass('is-invalid');
                            if (key === 'grup') $grupHidden.addClass('is-invalid');
                        } else {
                            const $input = $form.find(`[name="${key}"]`);
                            if ($input.length) {
                                $input.addClass('is-invalid');
                                $form.find(`[data-for="${key}"]`).show().text(msg);
                            } else {
                                // fallback
                                Swal.fire({ icon:'error', title:'Error', text: msg });
                            }
                        }
                    });
                } else {
                    Swal.fire({ icon:'error', title:'Error', text: json.message || 'Terjadi kesalahan!' });
                }
            },
            complete: function () {
                $btnSimpan.prop('disabled', false);
                $btnSimpanText.html('Simpan');
            }
        });
    });

    /* ---------- small accessibility: close modal on Esc (fallback) ---------- */
    $(document).on('keydown', function (e) {
        if (e.key === 'Escape' && ($modal && ($modal.open || $( $modal ).hasClass('modal-open')))) {
            closeDialog($modal);
        }
    });

    // Ensure initial state
    toggleGrupCombined();
});
</script>
@endpush
