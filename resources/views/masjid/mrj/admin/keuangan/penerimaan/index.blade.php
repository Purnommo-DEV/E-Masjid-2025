@extends('masjid.master')
@section('title', 'Penerimaan Pemasukan')

@section('content')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    /* Reuse style seperti Petty Cash / Pengeluaran Umum */

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
    .header-action:hover {
        transform: translateY(-2px);
        background: rgba(255,255,255,0.18);
        box-shadow: 0 10px 26px rgba(4,120,87,0.09);
    }

    /* dialog/modal styles */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; }
    .modal-panel {
        border-radius: 12px;
        box-shadow: 0 18px 40px rgba(2,6,23,0.12);
        overflow:hidden;
        background:white;
        position: relative;
        z-index: 60;
    }

    /* select2 z-index fixes */
    .select2-container { z-index: 999999 !important; }
    .select2-dropdown { z-index: 999999 !important; }

    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
    }

    /* DataTable borders fix (sama seperti Petty) */
    table.dataTable,
    table.dataTable thead th,
    table.dataTable tbody td,
    table.dataTable tfoot th,
    table.dataTable tfoot td {
        border: 1px solid #e6e6e6 !important;
        border-bottom-width: 1px !important;
    }
    table.dataTable {
        border-collapse: collapse !important;
    }
    table.dataTable thead th {
        background: rgba(16,185,129,0.04) !important;
        color: #065f46 !important;
        font-weight: 600 !important;
    }
    table.dataTable tbody tr:hover td {
        background: #fbfefb !important;
    }
    table.dataTable tbody td {
        border-top: 0 !important;
    }
    .dataTables_wrapper {
        overflow: visible !important;
    }
    .table-zebra.dataTable {
        width: 100% !important;
        border: 1px solid #e6e6e6 !important;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header" role="banner">
        <div>
            <h3 class="title flex items-center gap-2">
                <span>Penerimaan Pemasukan</span>
            </h3>
            <p class="subtitle">
                Catat semua penerimaan dana: infak, zakat, wakaf, donasi, dan pemasukan lain.
            </p>
        </div>

        <button type="button"
                class="header-action"
                onclick="openPenerimaanModal()"
                aria-haspopup="dialog"
                aria-controls="penerimaanModal">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round" />
            </svg>
            <span class="text-sm font-semibold">Tambah Penerimaan</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full text-sm" id="tabelPenerimaan" style="width:100%">
                <thead>
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Akun</th>
                        <th class="px-4 py-3 text-right">Jumlah</th>
                        <th class="px-4 py-3">Sumber</th>
                        <th class="px-4 py-3">Keterangan</th>
                        <th class="px-4 py-3">Dibuat Oleh</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tambah: pakai <dialog> seperti halaman lain -->
<dialog id="penerimaanModal"
        class="modal"
        aria-labelledby="penerimaanModalTitle"
        role="dialog">
    <div class="modal-panel w-11/12 max-w-3xl">
        <form id="formPenerimaan"
              class="p-6 bg-white rounded-2xl shadow-xl border border-emerald-50"
              novalidate>
            @csrf

            <div class="flex items-start justify-between gap-4 mb-2">
                <div>
                    <h3 id="penerimaanModalTitle" class="text-xl font-extrabold text-emerald-800">
                        💰 Tambah Penerimaan Dana
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Catat penerimaan infak, zakat, wakaf, donasi, dan pemasukan lainnya.
                    </p>
                </div>
                <div>
                    <button type="button"
                            id="closePenerimaanModalBtn"
                            class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100"
                            aria-label="Tutup">
                        ✕
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Akun Pendapatan <span class="text-red-500">*</span>
                    </label>
                    <select name="akun_pendapatan_id"
                            class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm select2"
                            required>
                        <option value="">— Pilih Akun —</option>
                        @foreach(\App\Models\AkunKeuangan::where('tipe','pendapatan')->orderBy('kode')->get() as $a)
                            <option value="{{ $a->id }}">{{ $a->kode }} - {{ $a->nama }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Tanggal <span class="text-red-500">*</span>
                    </label>
                    <input type="date"
                           name="tanggal"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm"
                           value="{{ date('Y-m-d') }}"
                           required>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Jumlah (Rp) <span class="text-red-500">*</span>
                    </label>
                    <div class="flex">
                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-emerald-100 bg-emerald-50 text-emerald-700 text-sm">
                            Rp
                        </span>
                        <input type="text"
                               name="jumlah"
                               class="flex-1 rounded-r-md border border-emerald-100 px-3 py-2 text-right ribuan"
                               required
                               placeholder="0">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Keterangan <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="keterangan"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm"
                           required
                           placeholder="Contoh: Infak Jumat, Donasi Program Pendidikan, dll">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Sumber Penerimaan (Opsional)
                    </label>
                    <input type="text"
                           name="sumber_nama"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm"
                           placeholder="Infak Jumat, Zakat Fitrah 350 orang, H. Ahmad, dll">
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Telepon/Kontak (Opsional)
                    </label>
                    <input type="text"
                           name="sumber_telepon"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm"
                           placeholder="0812xxx">
                </div>
            </div>

            <div class="mt-4">
                <label class="text-sm font-medium text-emerald-700">
                    Keterangan Tambahan
                </label>
                <textarea name="keterangan_tambahan"
                          class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm"
                          rows="2"
                          placeholder="Wakaf tanah 500m², dari 120 muzakki, dll"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 mt-5">
                <button type="button"
                        id="cancelPenerimaanBtn"
                        class="px-4 py-2 rounded-md border border-slate-200 text-sm hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow"
                        id="submitPenerimaanBtn">
                    <span>Penerimaan Dana</span>
                </button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(function() {
    const modal = document.getElementById('penerimaanModal');
    const form  = $('#formPenerimaan');

    function showDialog(d) {
        try {
            if (typeof d.showModal === 'function') d.showModal();
            else d.classList.add('modal-open');
        } catch(e) {
            d.classList.add('modal-open');
        }
    }
    function closeDialog(d) {
        try {
            if (typeof d.close === 'function') d.close();
            else d.classList.remove('modal-open');
        } catch(e) {
            d.classList.remove('modal-open');
        }
    }

    // Select2 di dalam dialog
    if ($.fn.select2) {
        const $dp = $('#penerimaanModal .modal-panel').length
            ? $('#penerimaanModal .modal-panel')
            : $(document.body);

        $('.select2').select2({
            dropdownParent: $dp,
            placeholder: "Pilih akun pendapatan",
            allowClear: true,
            width: '100%'
        });
    }

    // -------------------------------
    // FORMAT RIBUAN UNTUK NOMINAL
    // -------------------------------
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
        const formatted = formatRibuan(onlyNumber);
        this.value = formatted;
    });

    // DataTable
    const tablePenerimaan = $('#tabelPenerimaan').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.penerimaan.data") }}',
        columns: [
            {data: 'tanggal',    name: 'tanggal'},
            {data: 'akun_pendapatan',       name: 'akun_pendapatan'},
            {data: 'jumlah',     name: 'jumlah', className: 'text-right'},
            {data: 'sumber',     name: 'sumber'},
            {data: 'keterangan', name: 'keterangan'},
            {data: 'user',       name: 'user'},
        ],
        responsive: true,
        language: {
            processing: "Memuat...",
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_"
        }
    });

    // open modal
    window.openPenerimaanModal = function() {
        form[0].reset();

        if ($.fn.select2) {
            $('.select2').each(function() {
                if ($(this).data('select2')) {
                    $(this).val(null).trigger('change');
                    try { $(this).select2('close'); } catch(e) {}
                }
            });
        }

        showDialog(modal);

        setTimeout(() => {
            $('select[name="akun_pendapatan_id"]').trigger('focus');
        }, 150);
    };

    // tombol close / cancel
    $('#closePenerimaanModalBtn, #cancelPenerimaanBtn').on('click', () => {
        closeDialog(modal);
    });
    if (modal) {
        modal.addEventListener('cancel', function(e) {
            e.preventDefault();
            closeDialog(modal);
        });
    }

    // submit form
    form.on('submit', function(e) {
        e.preventDefault();

        const btn = $('#submitPenerimaanBtn');

        // Hapus titik dulu sebelum dikirim ke server
        $('.ribuan').each(function() {
            let clean = $(this).val().replace(/\D/g, '');
            $(this).val(clean);
        });

        $.ajax({
            url: '{{ route("admin.keuangan.penerimaan.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            beforeSend: () => {
                btn.prop('disabled', true)
                   .addClass('opacity-80')
                   .html('<span class="flex items-center gap-2"><span class="loading loading-spinner loading-xs"></span> Menyimpan...</span>');
            },
            success: function(res) {
                Swal.fire('Sukses!', res.message || 'Penerimaan dana berhasil dicatat!', 'success');
                closeDialog(modal);
                form[0].reset();
                if ($.fn.select2) {
                    $('.select2').val(null).trigger('change');
                }
                tablePenerimaan.ajax.reload();
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Gagal menyimpan penerimaan dana';
                Swal.fire('Error!', msg, 'error');
            },
            complete: () => {
                btn.prop('disabled', false)
                   .removeClass('opacity-80')
                   .html('<span>Penerimaan Dana</span>');
            }
        });
    });
});
</script>
@endpush
