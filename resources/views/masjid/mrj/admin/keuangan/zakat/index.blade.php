@extends('masjid.master')
@section('title', 'Zakat & Fidyah')

@section('content')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<style>
    /* Card & header reuse dari Petty/Pengeluaran/Penerimaan */
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

    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
    }

    /* DataTable borders fix (sama seperti halaman lain) */
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

    /* Validation error styling */
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 1px rgba(220,53,69,0.06) !important;
    }
    .invalid-feedback {
        display:block;
        color:#dc3545;
        font-size:.75rem;
        margin-top:4px;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header" role="banner">
        <div>
            <h3 class="title flex items-center gap-2">
                <span><i class="fas fa-pray"></i> Pengelolaan Zakat & Fidyah</span>
            </h3>
            <p class="subtitle">
                Catat penerimaan zakat, fidyah, infaq, dan shodaqoh secara rapi dan otomatis masuk jurnal.
            </p>
        </div>

        <button type="button"
                class="header-action"
                onclick="openZakatModal()"
                aria-haspopup="dialog"
                aria-controls="modalTerima">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14"
                      stroke="currentColor"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round" />
            </svg>
            <span class="text-sm font-semibold">Terima Zakat</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table class="table table-zebra w-full text-sm" id="tabelZakat" width="100%"></table>
        </div>
    </div>
</div>

<!-- Modal Terima Zakat: pakai <dialog> + Tailwind/daisyUI -->
<dialog id="modalTerima"
        class="modal"
        aria-labelledby="modalTerimaTitle"
        role="dialog">
    <div class="modal-panel w-11/12 max-w-5xl">
        <form id="formTerima"
              enctype="multipart/form-data"
              class="p-6 bg-white rounded-2xl shadow-xl border border-emerald-50"
              novalidate>
            @csrf

            <div class="flex items-start justify-between gap-4 mb-4">
                <div>
                    <h3 id="modalTerimaTitle" class="text-xl font-extrabold text-emerald-800">
                        🕌 Terima Zakat & Fidyah
                    </h3>
                    <p class="text-sm text-slate-500 mt-1">
                        Pilih jenis zakat, isi data muzakki & keluarga (untuk zakat fitrah), lalu simpan agar otomatis tercatat.
                    </p>
                </div>
                <button type="button"
                        id="closeTerimaModalBtn"
                        class="inline-flex items-center justify-center w-9 h-9 rounded-md text-gray-600 hover:bg-gray-100"
                        aria-label="Tutup">
                    ✕
                </button>
            </div>

            <!-- Jenis Zakat Checkbox -->
            <div class="mb-4">
                <p class="text-sm font-medium text-emerald-700 mb-2">Jenis Zakat / Dana</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2">
                    @foreach(['zakat_fitrah','zakat_maal','fidyah','infaq','shodaqoh'] as $j)
                        <label class="flex items-center gap-2 text-sm bg-emerald-50 border border-emerald-100 rounded-lg px-3 py-2">
                            <input class="checkbox checkbox-sm" type="checkbox" name="jenis_zakat[]" value="{{ $j }}" id="{{ $j }}">
                            <span>{{ ucwords(str_replace('_',' ',$j)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Muzakki Utama -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2 space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Muzakki Utama (yang bayar & tanda tangan) <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="muzakki_utama"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm"
                           required>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        No. HP
                    </label>
                    <input type="text"
                           name="no_hp"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm">
                </div>
            </div>

            <!-- Daftar Keluarga (khusus zakat fitrah) -->
            <div class="mt-4">
                <div id="blokFitrah"
                     style="display:none;"
                     class="border-2 border-emerald-500 rounded-xl bg-emerald-50 p-4">
                    <h5 class="text-emerald-700 font-semibold flex items-center gap-2 mb-1">
                        <i class="fas fa-users"></i>
                        <span>Daftar Anggota Keluarga (Khusus Zakat Fitrah)</span>
                    </h5>
                    <p class="text-xs text-slate-500 mb-3">
                        Muzakki utama masuk di baris pertama. Tambah sesuai jumlah jiwa yang dizakatkan.
                    </p>

                    <div id="daftar-keluarga" class="space-y-2">
                        <div class="flex flex-wrap items-center gap-3 mb-2 keluarga-row">
                            <div class="w-8 text-center">
                                <strong>1</strong>
                            </div>
                            <div class="flex-1 min-w-[180px]">
                                <input type="text"
                                       name="anggota_keluarga[]"
                                       class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm"
                                       placeholder="Nama muzakki utama">
                            </div>
                            <div class="w-24">
                                <input type="number"
                                       name="jiwa[]"
                                       class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm"
                                       value="1"
                                       min="1">
                            </div>
                        </div>
                    </div>

                    <button type="button"
                            class="btn btn-sm btn-outline btn-success mt-2"
                            id="tambah-keluarga">
                        + Tambah Anggota Keluarga
                    </button>
                </div>
            </div>

            <!-- Detail Lainnya -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-700">
                        Jumlah (Rp) <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           name="jumlah"
                           class="w-full rounded-lg border border-emerald-100 px-3 py-2 ribuan shadow-sm"
                           required>
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
                        Akun Liabilitas <span class="text-red-500">*</span>
                    </label>
                    <select name="akun_id"
                            class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm"
                            required>
                        @foreach(\App\Models\AkunKeuangan::where('grup','zakat')->get() as $a)
                            <option value="{{ $a->id }}">{{ $a->kode }} - {{ $a->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 space-y-2">
                <label class="text-sm font-medium text-emerald-700">
                    Bukti Transfer / Foto
                </label>
                <input type="file"
                       name="bukti"
                       class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm"
                       accept="image/*">
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <button type="button"
                        id="cancelTerimaBtn"
                        class="px-4 py-2 rounded-md border border-slate-200 text-sm hover:bg-slate-50">
                    Batal
                </button>
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow"
                        id="submitTerimaBtn">
                    
                    <span>Simpan &amp; Masuk Jurnal</span>
                </button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
<script>
    // Set locale Indonesian untuk moment
    if (window.moment) {
        moment.locale('id');
    }
</script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

<script>
    const modalTerima = document.getElementById('modalTerima');

    function showDialog(d) {
        try {
            if (typeof d.showModal === 'function') d.showModal();
            else d.classList.add('modal-open');
        } catch (e) {
            d.classList.add('modal-open');
        }
    }
    function closeDialog(d) {
        try {
            if (typeof d.close === 'function') d.close();
            else d.classList.remove('modal-open');
        } catch (e) {
            d.classList.remove('modal-open');
        }
    }

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


    // Dipanggil dari tombol header
    window.openZakatModal = function () {
        const form = $('#formTerima');
        form[0].reset();

        // bersihkan error sebelumnya
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        showDialog(modalTerima);
    };

    // Tombol close/cancel
    $('#closeTerimaModalBtn, #cancelTerimaBtn').on('click', function() {
        closeDialog(modalTerima);
    });
    if (modalTerima) {
        modalTerima.addEventListener('cancel', function(e) {
            e.preventDefault();
            closeDialog(modalTerima);
        });
    }

    // Submit form
    $('#formTerima').submit(function(e){
        e.preventDefault();

        // cleanup ribuan
        $('.ribuan').each(function() {
            const clean = $(this).val().replace(/\D/g, '');
            $(this).val(clean);
        });

        const form = this;
        const btn  = $('#submitTerimaBtn');

        // bersihkan error lama
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        btn.prop('disabled',true)
           .addClass('opacity-80')
           .html('<span class="flex items-center gap-2"><span class="loading loading-spinner loading-xs"></span> Menyimpan...</span>');

        $.ajax({
            url: '{{ route("admin.keuangan.zakat.store.penerimaan") }}',
            method: 'POST',
            data: new FormData(form),
            processData: false,
            contentType: false,
            success: () => {
                Swal.fire('Berhasil!','Zakat berhasil diterima','success');
                closeDialog(modalTerima);
                $('#tabelZakat').DataTable().ajax.reload();
                form.reset();
            },
            error: (xhr) => {
                const res = xhr.responseJSON || {};
                const errors = res.errors || null;

                if (errors) {
                    // tampilkan error per field
                    $.each(errors, function(key, messages) {
                        let $input = $('[name="'+key+'"]');
                        if (!$input.length) {
                            // coba untuk array: name="field[]"
                            $input = $('[name="'+key+'[]"]');
                        }
                        if ($input.length) {
                            $input.addClass('is-invalid');
                            $input.after('<div class="invalid-feedback">'+(messages[0] || 'Wajib diisi')+'</div>');
                        }
                    });
                } else {
                    Swal.fire('Gagal', res.message || 'Error','error');
                }
            },
            complete: () => {
                btn.prop('disabled',false)
                   .removeClass('opacity-80')
                   .html('<span>Simpan &amp; Masuk Jurnal</span>');
            }
        });
    });

    // DataTable
    $('#tabelZakat').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.zakat.data") }}',
        columns: [
            {title:'Tanggal',data:'tanggal',render:d=>moment(d).format('DD MMM YYYY')},
            {title:'Akun',data:'akun_zakat'},
            {title:'Jenis',data:'jenis'},
            {title:'Muzakki',data:'muzakki_utama'},
            {title:'Jiwa',data:'total_jiwa'},
            {title:'Jumlah',data:'jumlah_fmt',className:'text-end'},
            {title:'Kwitansi',data:'kwitansi',className:'text-center'},
        ],
        order: [[0,'desc']],
        rowGroup: { dataSrc: 'tanggal' }
    });

    // Kontrol muncul/hilang blok fitrah
    function toggleBlokFitrah() {
        const isFitrah = $('input[value="zakat_fitrah"]').is(':checked');

        if (isFitrah) {
            $('#blokFitrah').slideDown(300);
            $('#blokFitrah input[name="anggota_keluarga[]"], #blokFitrah input[name="jiwa[]"]').prop('required', true);
        } else {
            $('#blokFitrah').slideUp(300);
            $('#blokFitrah input[name="anggota_keluarga[]"], #blokFitrah input[name="jiwa[]"]').prop('required', false);
            $('#daftar-keluarga').html(`
                <div class="flex flex-wrap items-center gap-3 mb-2 keluarga-row">
                    <div class="w-8 text-center"><strong>1</strong></div>
                    <div class="flex-1 min-w-[180px]">
                        <input type="text" name="anggota_keluarga[]" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm" placeholder="Nama muzakki utama">
                    </div>
                    <div class="w-24">
                        <input type="number" name="jiwa[]" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm" value="1" min="1">
                    </div>
                </div>
            `);
            noUrut = 2;
        }
    }

    $(document).ready(toggleBlokFitrah);
    $('input[name="jenis_zakat[]"]').change(toggleBlokFitrah);

    // Tambah anggota keluarga
    let noUrut = 2;
    $('#tambah-keluarga').click(function(){
        $('#daftar-keluarga').append(`
            <div class="flex flex-wrap items-center gap-3 mb-2 keluarga-row">
                <div class="w-8 text-center"><strong>${noUrut}</strong></div>
                <div class="flex-1 min-w-[180px]">
                    <input type="text" name="anggota_keluarga[]" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm" placeholder="Nama anggota keluarga" required>
                </div>
                <div class="w-24">
                    <input type="number" name="jiwa[]" class="w-full rounded-lg border border-emerald-100 px-3 py-2 shadow-sm text-sm" value="1" min="1" required>
                </div>
                <div class="w-10">
                    <button type="button" class="btn btn-error btn-xs remove-keluarga">−</button>
                </div>
            </div>
        `);
        noUrut++;
    });

    $(document).on('click', '.remove-keluarga', function(){
        $(this).closest('.keluarga-row').remove();
        // Renumbering
        $('.keluarga-row').each(function(i){
            $(this).find('strong').text(i+1);
        });
        noUrut = $('.keluarga-row').length + 1;
    });
</script>
@endpush
