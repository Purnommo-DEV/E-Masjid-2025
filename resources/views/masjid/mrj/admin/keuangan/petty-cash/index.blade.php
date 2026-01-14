@extends('masjid.master')

@section('title', 'Petty Cash / Kas Kecil')

@section('content')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    /* Reuse Berita styles so Petty matches exactly */

    .card-wrapper, .card {
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

    /* select2 z-index fixes */
    .select2-container { z-index: 999999 !important; }
    .select2-dropdown { z-index: 999999 !important; }

    /* validation */
    .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 1px rgba(220,53,69,0.06) !important; }
    .invalid-feedback { display:block; color:#dc3545; font-size:.875rem; margin-top:6px; }

    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
    }

    /* small table styling */
    #tabelPetty_wrapper { width:100%; }

    /* =========================
   DataTable borders fix
   ========================= */

/* Pastikan selector memiliki bobot yang cukup dan ditempatkan setelah DataTables CSS */
table.dataTable, 
table.dataTable thead th, 
table.dataTable tbody td, 
table.dataTable tfoot th, 
table.dataTable tfoot td {
  border: 1px solid #e6e6e6 !important;   /* warna border netral */
  border-bottom-width: 1px !important;
}

/* Hilangkan double border dan rapikan */
table.dataTable {
  border-collapse: collapse !important;
}

/* Header style lebih tegas */
table.dataTable thead th {
  background: rgba(16,185,129,0.04) !important; /* very light emerald */
  color: #065f46 !important;
  font-weight: 600 !important;
}

/* Baris hover & zebra (opsional) */
table.dataTable tbody tr:hover td {
  background: #fbfefb !important;
}

/* Jika kamu ingin garis antar-baris lebih halus */
table.dataTable tbody td {
  border-top: 0 !important; /* hanya gunakan border di sekeliling sel */
}

/* Jika masih tidak muncul karena container overflow hidden, tampilkan border di luar */
.dataTables_wrapper {
  overflow: visible !important;
}

/* Jika menggunakan Tailwind/daisy yang override table styles, paksa tipe tampilan */
.table-zebra.dataTable {
  width: 100% !important;
  border: 1px solid #e6e6e6 !important;
}

</style>
@endpush

<div class="card-wrapper">
    <div class="card-header" role="banner">
        <div>
            <h3 class="title">Petty Cash / Kas Kecil</h3>
            <p class="subtitle">
                Saldo Saat Ini: <strong id="saldoPetty">Rp {{ number_format($saldo, 0, ',', '.') }}</strong>
            </p>
        </div>

        <button type="button" class="header-action" onclick="openPettyModal()" aria-haspopup="dialog" aria-controls="pettyModal">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Transaksi</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="tabelPetty" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Akun Beban</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3">Pembuat</th>
                        <th class="px-4 py-3">Keterangan</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Dialog modal (like Berita modal) -->
<dialog id="pettyModal" class="modal" aria-labelledby="pettyModalTitle" role="dialog">
  <div class="modal-panel w-11/12 max-w-2xl">
    <form id="formPetty" class="p-6 bg-white rounded-2xl shadow-xl border border-emerald-50" novalidate>
        @csrf
        <input type="hidden" id="methodPetty" value="POST">

        <div class="flex items-start justify-between gap-4 mb-2">
            <div>
                <h3 id="pettyModalTitle" class="text-xl font-extrabold text-emerald-800">💼 Transaksi Petty Cash</h3>
                <p class="text-sm text-slate-500 mt-1">Catat isi ulang / pengeluaran kas kecil</p>
            </div>
            <div>
                <button type="button" id="closePettyModalBtn" class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100" aria-label="Tutup">✕</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            <div class="space-y-3">
                <label class="text-sm font-medium text-emerald-700">Tipe <span class="text-red-500">*</span></label>
                <select name="tipe" id="tipePetty" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm">
                    <option value="isi_ulang">Isi Ulang</option>
                    <option value="pengeluaran">Pengeluaran</option>
                </select>

                <label class="text-sm font-medium text-emerald-700">Jumlah (Rp) <span class="text-red-500">*</span></label>
                <div class="flex">
                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-emerald-100 bg-emerald-50 text-emerald-700">Rp</span>
                    <input type="text" name="jumlah" class="flex-1 rounded-r-md border border-emerald-100 px-3 py-2 text-right ribuan" placeholder="0" required>
                </div>

                <label class="text-sm font-medium text-emerald-700">Tanggal Transaksi</label>
                <input type="date" name="tanggal" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm" required value="{{ date('Y-m-d') }}">
            </div>

            <div class="space-y-3">
                <label class="text-sm font-medium text-emerald-700">Akun Beban (wajib jika pengeluaran)</label>
                <select name="akun_beban_id" id="akunBebanPetty" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm select2-petty" disabled>
                    <option value="">— Hanya Pengeluaran Kecil (≤ Rp 500.000) —</option>
                    @foreach(
                        \App\Models\AkunKeuangan::where('tipe','beban')
                            ->where('jenis_beban','kecil')
                            ->orderBy('kode')->get() as $akun
                    )
                        <option value="{{ $akun->id }}">{{ $akun->kode }} - {{ $akun->nama }}</option>
                    @endforeach
                </select>
                <small class="text-danger block mt-1">Gaji, listrik, honor → pakai menu Pengeluaran Umum!</small>

                <label class="text-sm font-medium text-emerald-700">Keterangan</label>
                <textarea name="keterangan" rows="6" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm" required></textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 mt-4">
            <button type="button" id="cancelPettyBtn" class="px-4 py-2 rounded-md border border-slate-200 text-sm hover:bg-slate-50">Batal</button>
            <button type="submit" id="submitPettyBtn" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow">Simpan</button>
        </div>
    </form>
  </div>
</dialog>

@endsection

@push('scripts')
<!-- libs -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function() {
    const modal = document.getElementById('pettyModal');
    const form = $('#formPetty');

    function showDialog(d) {
        try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
        catch(e){ d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
        catch(e){ d.classList.remove('modal-open'); }
    }

    // init select2 safely with dropdownParent inside modal panel
    if ($.fn.select2) {
        const $dp = $('#pettyModal .modal-panel').length ? $('#pettyModal .modal-panel') : $(document.body);
        $('#akunBebanPetty').select2({
            width: '100%',
            dropdownParent: $dp,
            placeholder: 'Pilih akun beban...',
            allowClear: true
        });
    }

    // toggle akun beban enable/disable depending tipe
    const $tipe = $('[name="tipe"]');
    const $akunBeban = $('#akunBebanPetty');

    function toggleAkunBeban() {
        const tipeVal = $tipe.val();
        if (tipeVal === 'isi_ulang') {
            // disable
            if ($.fn.select2 && $akunBeban.data('select2')) {
                $akunBeban.prop('disabled', true).val(null).trigger('change');
            } else {
                $akunBeban.prop('disabled', true).val('');
            }
            $akunBeban.prop('required', false);
        } else {
            // enable
            if ($.fn.select2 && $akunBeban.data('select2')) {
                $akunBeban.prop('disabled', false).trigger('change');
            } else {
                $akunBeban.prop('disabled', false);
            }
            $akunBeban.prop('required', true);
        }
    }
    // initial
    toggleAkunBeban();
    $tipe.on('change', toggleAkunBeban);

    // DataTable init
    const tablePetty = $('#tabelPetty').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.petty-cash.data") }}',
        columns: [
            { data: 'tanggal', name: 'tanggal' },
            { data: 'akun_beban', name: 'akun_beban' },
            { data: 'tipe', name: 'tipe' },
            { data: 'jumlah', name: 'jumlah', className: 'text-right' },
            { data: 'user', name: 'user'},
            { data: 'keterangan', name: 'keterangan' }
        ],
        responsive: true,
        language: {
            processing: "Memuat...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_"
        }
    });

    // format ribuan input
    function formatRibuan(angka) {
        angka = angka.replace(/\D/g, '');
        if (angka === '') return '';
        return angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }
    $(document).on('input', '.ribuan', function() {
        const before = this.value;
        const onlyNumber = before.replace(/\D/g, '');
        if (onlyNumber === '') { this.value = ''; return; }
        this.value = formatRibuan(onlyNumber);
    });

    // open modal
    window.openPettyModal = function() {
        form[0].reset();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#methodPetty').val('POST');
        $('#pettyModalTitle').text('Tambah Transaksi Petty Cash');

        // close any open select2 safely
        if ($.fn.select2) {
            $('#akunBebanPetty').each(function(){
                if ($(this).data('select2')) {
                    try { $(this).select2('close'); } catch(e) {}
                }
            });
        }

        showDialog(modal);

        setTimeout(()=> {
            // focus tipe; if select2 needs open, handle safely
            $('[name="tipe"]').trigger('focus');
        }, 150);
    };

    // modal controls
    $('#closePettyModalBtn, #cancelPettyBtn').on('click', () => closeDialog(modal));
    if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

    // submit form AJAX
    form.on('submit', function(e) {
        e.preventDefault();

        // cleanup ribuan
        $('.ribuan').each(function() {
            const clean = $(this).val().replace(/\D/g, '');
            $(this).val(clean);
        });

        // prepare data
        const data = $(this).serialize();

        const $btn = $('#submitPettyBtn');
        $btn.prop('disabled', true).addClass('opacity-80');

        $.ajax({
            url: '{{ route("admin.keuangan.petty-cash.store") }}',
            type: 'POST',
            data: data,
            success: function(res) {
                closeDialog(modal);
                tablePetty.ajax.reload();
                        // update saldo header
                $.get('{{ route("admin.keuangan.petty-cash.saldo") }}', function(data) {
                    if (data.saldo !== undefined) {
                        const formattedSaldo = new Intl.NumberFormat('id-ID').format(data.saldo);
                        $('#saldoPetty').text('Rp ' + formattedSaldo);
                    }
                });
                Swal.fire('Sukses!', res.message || 'Transaksi tersimpan.', 'success');
            },
            error: function(xhr) {
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();

                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    $.each(errors, function(key, messages) {
                        let $input = $('[name="'+key+'"]');
                        if (!$input.length) $input = $('[name="'+key+'[]"]');
                        if ($input.length) {
                            $input.addClass('is-invalid');
                            $input.after('<div class="invalid-feedback">'+(messages[0]||'Error')+'</div>');
                        }
                    });
                } else {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                }
            },
            complete: function() {
                $btn.prop('disabled', false).removeClass('opacity-80');
            }
        });
    });
});
</script>
@endpush
