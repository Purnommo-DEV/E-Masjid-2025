@extends('masjid.master')

@section('title', 'Zakat & Fidyah')

@section('content')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
    .card-wrapper, .card { max-width: 1200px; margin: 1.25rem auto; border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 30px rgba(2,6,23,0.06); border: 1px solid rgba(15,23,42,0.04); background: white; }
    .card-header { padding: 1.25rem 1.5rem; color: #fff; background: linear-gradient(90deg, #059669 0%, #10b981 100%); display: flex; align-items: center; justify-content: space-between; }
    .card-header .title { margin:0; font-size:1.125rem; font-weight:700; }
    .card-header .subtitle { margin:0; opacity:.95; font-size:.95rem; }
    .card-body { padding: 1.25rem 1.5rem; background: white; }
    .header-action { background: rgba(255,255,255,0.12); color: #fff; padding: 0.5rem 0.9rem; border-radius: 999px; display: inline-flex; gap: .5rem; align-items: center; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 6px 14px rgba(4,120,87,0.06); transition: transform .12s ease, background .12s, box-shadow .12s; cursor: pointer; font-weight: 600; }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); box-shadow: 0 10px 26px rgba(4,120,87,0.09); }
    dialog.modal::backdrop { background: rgba(15,23,42,0.55); backdrop-filter: blur(4px) saturate(1.02); }
    dialog.modal { border: none; padding: 0; }
    .modal-panel { border-radius: 16px; box-shadow: 0 20px 50px rgba(5,150,105,0.25); overflow-y: auto; max-height: 85vh; background: #ecfdf5; border: 3px solid #10b981; }
    .modal-header { background: linear-gradient(90deg, #059669, #10b981); color: white; padding: 1.5rem; border-bottom: 2px solid #059669; }
    .modal-header h3 { margin: 0; font-size: 1.6rem; font-weight: bold; }
    .modal-header p { margin: 0.5rem 0 0; opacity: 0.95; font-size: 1rem; }
    .detail-row { border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem; box-shadow: 0 6px 16px rgba(16,185,129,0.15); transition: all 0.3s ease; }
    .detail-row:hover { transform: translateY(-4px); box-shadow: 0 12px 24px rgba(16,185,129,0.25); }
    .detail-row[data-jenis="zakat_fitrah"] { background: #d1fae5; border: 3px solid #059669; }
    .detail-row[data-jenis="zakat_maal"]   { background: #dbeafe; border: 3px solid #3b82f6; }
    .detail-row[data-jenis="fidyah"]       { background: #f3e8ff; border: 3px solid #8b5cf6; }
    .detail-row[data-jenis="infaq"]        { background: #fef3c7; border: 3px solid #eab308; }
    .detail-row[data-jenis="shodaqoh"]     { background: #ffedd5; border: 3px solid #f97316; }
    .detail-row[data-jenis="wakaf"]        { background: #ccfbf1; border: 3px solid #14b8a6; }
    .detail-row[data-jenis="donasi_khusus"]{ background: #fee2e2; border: 3px solid #ef4444; }
    .is-invalid { border-color: #dc3545 !important; box-shadow: 0 0 0 1px rgba(220,53,69,0.06) !important; }
    .invalid-feedback { display: block; color: #dc3545; font-size: .75rem; margin-top: 4px; }
    .btn-submit { background: linear-gradient(90deg, #059669, #10b981); transition: all 0.3s; box-shadow: 0 4px 12px rgba(5,150,105,0.3); }
    .btn-submit:hover { background: linear-gradient(90deg, #047857, #059669); transform: translateY(-3px); box-shadow: 0 12px 24px rgba(5,150,105,0.4); }
    #akun-keterangan { font-size: 0.875rem; color: #065f46; margin-top: 0.5rem; background: #ecfdf5; padding: 0.75rem; border-radius: 8px; border-left: 4px solid #10b981; }
    .swal2-container { z-index: 9999 !important; }
    @media (max-width: 640px) { .card-header, .card-body { padding: 1rem; } }
    table.dataTable, table.dataTable thead th, table.dataTable tbody td { border: 1px solid #e6e6e6 !important; }
    table.dataTable thead th { background: rgba(16,185,129,0.04) !important; color: #065f46 !important; font-weight: 600 !important; }
    table.dataTable tbody tr:hover td { background: #fbfefb !important; }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header" role="banner">
        <div>
            <h3 class="title flex items-center gap-2">
                <span><i class="fas fa-pray"></i> Pengelolaan Zakat & Fidyah</span>
            </h3>
            <p class="subtitle">
                Catat penerimaan zakat fitrah (Rp47.000/jiwa 2026), maal, infaq, fidyah, dll. secara detail & otomatis masuk jurnal.
            </p>
        </div>
        <button type="button" class="header-action" onclick="openZakatModal()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
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

<!-- Modal -->
<dialog id="modalTerima" class="modal" aria-labelledby="modalTerimaTitle" role="dialog">
    <div class="modal-panel w-11/12 max-w-5xl mx-auto my-8">
        <div class="modal-header">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h3 id="modalTerimaTitle" class="text-2xl font-bold">🕌 Terima Zakat & Fidyah</h3>
                    <p class="text-sm mt-1">Pilih satu jenis zakat per transaksi agar akun liabilitas tepat. Jika ingin input jenis lain dengan muzakki sama, klik "Input Lagi" setelah simpan.</p>
                </div>
                <button type="button" id="closeTerimaModalBtn" class="text-3xl text-white hover:text-gray-200 transition" aria-label="Tutup">✕</button>
            </div>
        </div>

        <form id="formTerima" enctype="multipart/form-data" class="p-6" novalidate>
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="md:col-span-2 space-y-2">
                    <label class="text-sm font-medium text-emerald-800">Muzakki Utama <span class="text-red-500">*</span></label>
                    <select id="muzakki_search" name="muzakki_id" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm focus:ring-emerald-500 focus:border-emerald-500" required style="width:100%;"></select>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-800">No. HP</label>
                    <input type="text" name="no_hp" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                </div>
            </div>

            <div class="mb-6">
                <p class="text-sm font-medium text-emerald-800 mb-2">Pilih Jenis Zakat / Dana (satu saja)</p>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    @foreach(['zakat_fitrah','zakat_maal','fidyah','infaq','shodaqoh','wakaf','donasi_khusus'] as $j)
                        <label class="radio-label flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 rounded-xl cursor-pointer hover:bg-emerald-100 transition">
                            <input class="radio radio-sm radio-success" type="radio" name="jenis_zakat" value="{{ $j }}">
                            <span class="text-sm font-medium text-emerald-800">{{ ucwords(str_replace('_',' ',$j)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div id="details-container" class="space-y-4 mb-6"></div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-800">Total Nominal (Rp) <span class="text-red-500">*</span></label>
                    <input type="text" id="total_nominal" class="w-full rounded-lg border border-emerald-300 px-3 py-2 ribuan shadow-sm bg-emerald-50 font-bold text-right text-emerald-900" readonly required>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-800">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                </div>
                <div class="space-y-2">
                    <label class="text-sm font-medium text-emerald-800">Akun Liabilitas <span class="text-red-500">*</span></label>
                    <select name="akun_id" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm focus:ring-emerald-500 focus:border-emerald-500" required>
                        @foreach(\App\Models\AkunKeuangan::where('grup','zakat')->get() as $a)
                            <option value="{{ $a->id }}">{{ $a->kode }} - {{ $a->nama }}</option>
                        @endforeach
                    </select>
                    <p id="akun-keterangan" class="mt-2 text-sm text-emerald-700 bg-emerald-50 p-2 rounded-lg border-l-4 border-emerald-600"></p>
                </div>
            </div>

            <div class="mb-6 space-y-2">
                <label class="text-sm font-medium text-emerald-800">Metode Bayar</label>
                <select name="metode_bayar" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm focus:ring-emerald-500 focus:border-emerald-500">
                    <option value="transfer">Transfer</option>
                    <option value="cash">Tunai</option>
                    <option value="beras">Beras</option>
                    <option value="barang">Barang Lain</option>
                </select>
            </div>

            <div class="mb-6 space-y-2">
                <label class="text-sm font-medium text-emerald-800">Bukti Transfer / Foto</label>
                <input type="file" name="bukti" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100" accept="image/*">
            </div>

            <div class="flex items-center justify-end gap-3 mt-6">
                <button type="button" id="cancelTerimaBtn" class="px-5 py-2 rounded-lg border border-emerald-300 text-emerald-700 hover:bg-emerald-50 transition">Batal</button>
                <button type="submit" class="btn-submit inline-flex items-center gap-2 px-6 py-2 rounded-lg text-white font-semibold shadow-lg" id="submitTerimaBtn">
                    <span>Simpan & Masuk Jurnal</span>
                </button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    window.routes = {
        kwitansi: '{{ route("admin.keuangan.zakat.kwitansi", ":id") }}',
        editData: '{{ route("admin.keuangan.zakat.edit-data", ":id") }}',
        update: '{{ route("admin.keuangan.zakat.update", ":id") }}',
        delete: '{{ route("admin.keuangan.zakat.delete", ":id") }}'
    };

    if (window.moment) moment.locale('id');

    const modalTerima = document.getElementById('modalTerima');
    let lastMuzakki = null;

    function showDialog() {
        modalTerima.showModal();
    }

    function closeDialog() {
        modalTerima.close();
    }

    window.openZakatModal = (preFill = false, isEdit = false) => {
        $('#formTerima')[0].reset();
        $('#details-container').empty();
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('#akun-keterangan').text('');
        $('input[name="jenis_zakat"]').prop('checked', false);

        if (preFill && lastMuzakki) {
            const option = new Option(lastMuzakki.text, lastMuzakki.id, true, true);
            $('#muzakki_search').append(option).trigger('change');
            $('input[name="no_hp"]').val(lastMuzakki.no_hp);
        }

        if (!isEdit) {
            $('#modalTerimaTitle').html('<i class="fas fa-pray"></i> Terima Zakat & Fidyah');
            $('#formTerima').attr('action', '{{ route("admin.keuangan.zakat.store.penerimaan") }}');
            $('#formTerima').attr('method', 'POST');
            $('#formTerima').find('input[name="_method"]').remove();
        }

        showDialog();
    };

    $('#closeTerimaModalBtn, #cancelTerimaBtn').on('click', closeDialog);

    if (modalTerima) {
        modalTerima.addEventListener('cancel', e => {
            e.preventDefault();
            closeDialog(modalTerima);
        });
    }

    // Format ribuan
    $(document).on('input', '.ribuan', function() {
        let val = this.value.replace(/\D/g, '');
        this.value = formatRibuan(val);
    });

    function formatRibuan(angka) {
        return angka ? angka.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    }

    function ucwords(str) {
        return (str + '').replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
    }

    const defaultAkunPerJenis = {
        'zakat_fitrah': '20001',
        'zakat_maal': '20002',
        'fidyah': '20003',
        'infaq': '20004',
        'shodaqoh': '20004',
        'wakaf': '20004',
        'donasi_khusus': '20004'
    };

    const keteranganAkun = {
        '20001': 'Zakat Fitrah Belum Disalurkan (prioritas fakir/miskin)',
        '20002': 'Zakat Maal Belum Disalurkan (8 asnaf)',
        '20003': 'Fidyah Belum Disalurkan (ganti puasa)',
        '20004': 'Infaq Terikat / Shodaqoh / Wakaf / Donasi Khusus (fleksibel)'
    };

    $('#muzakki_search').select2({
        ajax: {
            url: '{{ route("admin.keuangan.zakat.search-muzakki") }}',
            dataType: 'json',
            delay: 250,
            data: params => ({ query: params.term || '' }),
            processResults: data => ({
                results: data.map(d => ({
                    id: d.id,
                    text: d.nama + (d.no_hp ? ' (' + d.no_hp + ')' : ''),
                    no_hp: d.no_hp || ''
                }))
            }),
            cache: true
        },
        placeholder: 'Cari nama / HP muzakki...',
        minimumInputLength: 2,
        tags: true,
        createTag: params => {
            var term = $.trim(params.term);
            if (term === '') return null;
            return { id: term, text: 'Buat baru: ' + term, isNew: true };
        },
        dropdownParent: $('#modalTerima')
    }).on('select2:select', e => {
        const data = e.params.data;
        lastMuzakki = {
            id: data.id,
            text: data.text,
            no_hp: data.no_hp || $('input[name="no_hp"]').val()
        };
        if (!data.isNew) $('input[name="no_hp"]').val(data.no_hp);
    });

    $('input[name="jenis_zakat"]').change(function() {
        const container = $('#details-container');
        container.empty();
        const jenis = $(this).val();
        if (!jenis) return;
        const isJiwaBased = ['zakat_fitrah', 'fidyah'].includes(jenis);
        let label = ucwords(jenis);
        let row = `<div class="detail-row" data-jenis="${jenis}">
            <h5 class="text-emerald-800 font-semibold mb-2">${label}</h5>`;
        if (jenis === 'zakat_fitrah') row += `<p class="text-xs text-emerald-700 mb-2">Kadar 2026 BAZNAS: Rp47.000 / jiwa (setara 2,5 kg beras premium).</p>`;
        if (jenis === 'fidyah') row += `<p class="text-xs text-emerald-700 mb-2">Kadar 2026 BAZNAS: Rp50.000 / hari / jiwa</p>`;
        row += `<div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="text-sm text-emerald-800">Nominal (Rp)</label>
                <input type="text" name="details[${jenis}][nominal]" class="w-full rounded-lg border border-emerald-300 px-3 py-2 ribuan shadow-sm ${isJiwaBased ? 'bg-gray-100' : ''}" ${isJiwaBased ? 'readonly' : 'required'}>
            </div>`;
        if (isJiwaBased) {
            row += `<div>
                <label class="text-sm text-emerald-800">Jumlah Jiwa/Hari <span class="text-red-500">*</span></label>
                <input type="number" name="details[${jenis}][jiwa]" min="1" value="1" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm" required onchange="hitungNominal('${jenis}')">
            </div>
            <div>
                <label class="text-sm text-emerald-800">Beras (opsional)</label>
                <input type="number" step="0.01" name="details[${jenis}][jumlah_beras]" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm">
            </div>
            <div>
                <label class="text-sm text-emerald-800">Satuan</label>
                <select name="details[${jenis}][satuan_beras]" class="w-full rounded-lg border border-emerald-300 px-3 py-2 shadow-sm">
                    <option value="">-</option>
                    <option value="kg">Kg</option>
                    <option value="liter">Liter</option>
                </select>
            </div>`;
        }
        row += `<div class="sm:col-span-4">
            <label class="text-sm text-emerald-800">Keterangan Detail</label>
            <textarea name="details[${jenis}][keterangan_detail]" rows="2" class="w-full rounded-lg border border-emerald-300 px-3 py-2 text-sm"></textarea>
        </div></div></div>`;
        container.append(row);

        const akunSelect = $('select[name="akun_id"]');
        const keteranganEl = $('#akun-keterangan');
        const akunId = defaultAkunPerJenis[jenis];
        if (akunId) {
            akunSelect.val(akunId).trigger('change');
            keteranganEl.text(`Kategori ini akan masuk ke akun: ${keteranganAkun[akunId] || 'Tidak ada keterangan'}`);
        }
        hitungTotal();
    });

    function hitungNominal(jenis) {
        const jiwa = parseInt($(`input[name="details[${jenis}][jiwa]"]`).val()) || 0;
        let kadar = 47000;
        if (jenis === 'fidyah') kadar = 50000;
        const nominal = jiwa * kadar;
        $(`input[name="details[${jenis}][nominal]"]`).val(formatRibuan(nominal.toString()));
        hitungTotal();
    }

    function hitungTotal() {
        let total = 0;
        $('.detail-row input[name$="[nominal]"]').each(function() {
            total += parseInt($(this).val().replace(/\D/g, '')) || 0;
        });
        $('#total_nominal').val(formatRibuan(total.toString()));
    }

    $(document).on('input', '.detail-row input[name$="[nominal]"].ribuan', hitungTotal);

    $('#formTerima').submit(function(e) {
        e.preventDefault();
        $('.ribuan').each(function() { this.value = this.value.replace(/\D/g, ''); });
        const btn = $('#submitTerimaBtn');
        btn.prop('disabled', true).html('<span class="flex items-center gap-2"><span class="loading loading-spinner loading-xs"></span> Menyimpan...</span>');
        $.ajax({
            url: $('#formTerima').attr('action'),
            method: $('#formTerima').attr('method'),
            data: new FormData(this),
            processData: false,
            contentType: false,
            success: () => {
                closeDialog(modalTerima);
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Zakat diterima & jurnal tercatat.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#10b981',
                    cancelButtonColor: '#059669',
                    confirmButtonText: 'Tutup',
                    cancelButtonText: 'Input Lagi (muzakki sama)',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isDismissed && result.dismiss === Swal.DismissReason.cancel) {
                        openZakatModal(true);
                    }
                    $('#tabelZakat').DataTable().ajax.reload();
                });
            },
            error: xhr => {
                const errors = xhr.responseJSON?.errors || {};
                Object.keys(errors).forEach(key => {
                    let input = $(`[name="${key}"]`);
                    if (!input.length) input = $(`[name^="${key}"]`);
                    if (input.length) {
                        input.addClass('is-invalid');
                        input.after(`<div class="invalid-feedback">${errors[key][0]}</div>`);
                    }
                });
                Swal.fire('Gagal', 'Cek data yang diisi.', 'error');
            },
            complete: () => btn.prop('disabled', false).html('<span>Simpan & Masuk Jurnal</span>')
        });
    });

    // Edit transaksi
    $('#tabelZakat').on('click', '.btn-edit', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        $.ajax({
            url: routes.editData.replace(':id', id),
            method: 'GET',
            success: function(data) {
                console.log('Data edit loaded:', data);

                // Reset form
                $('#formTerima')[0].reset();
                $('#details-container').empty();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').remove();
                $('#akun-keterangan').text('');
                $('input[name="jenis_zakat"]').prop('checked', false);

                // Pre-fill muzakki
                const newOption = new Option(data.muzakki_nama, data.muzakki_id, true, true);
                $('#muzakki_search').append(newOption).trigger('change');
                $('input[name="no_hp"]').val(data.no_hp);

                // Pre-fill field lain
                $('input[name="tanggal"]').val(data.tanggal);
                $('select[name="metode_bayar"]').val(data.metode_bayar);
                $('textarea[name="keterangan"]').val(data.keterangan);

                // Ubah judul & form jadi PUT
                $('#modalTerimaTitle').html('<i class="fas fa-edit"></i> Edit Transaksi Zakat');
                $('#formTerima').attr('method', 'POST');
                $('#formTerima').find('input[name="_method"]').remove();
                $('#formTerima').append('<input type="hidden" name="_method" value="PUT">');
                $('#formTerima').attr('action', routes.update.replace(':id', id));

                // Pre-check radio & trigger change
                if (data.jenis_zakat) {
                    const radio = $(`input[name="jenis_zakat"][value="${data.jenis_zakat}"]`);
                    if (radio.length) {
                        radio.prop('checked', true).trigger('change');
                    }
                }

                // Pre-fill akun & detail setelah row dibuat
                setTimeout(() => {
                    // Akun liabilitas (FIX: delay & trigger change)
                    const akunSelect = $('select[name="akun_id"]');
                    akunSelect.val(data.akun_id);
                    akunSelect.trigger('change');
                    console.log('Akun di-set ke:', data.akun_id, 'Current val:', akunSelect.val());

                    const jenis = data.jenis_zakat;
                    if (jenis && data.details && data.details[jenis]) {
                        const detail = data.details[jenis];
                        $(`input[name="details[${jenis}][nominal]"]`).val(formatRibuan(detail.nominal.toString()));
                        if (detail.jiwa) $(`input[name="details[${jenis}][jiwa]"]`).val(detail.jiwa);
                        $(`input[name="details[${jenis}][jumlah_beras]"]`).val(detail.jumlah_beras);
                        $(`select[name="details[${jenis}][satuan_beras]"]`).val(detail.satuan_beras);
                        $(`textarea[name="details[${jenis}][keterangan_detail]"]`).val(detail.keterangan_detail);
                    }
                    if (jenis) hitungNominal(jenis);
                    hitungTotal();
                }, 1500);

                modalTerima.showModal();
            },
            error: () => {
                Swal.fire('Gagal', 'Tidak bisa load data untuk edit.', 'error');
            }
        });
    });

    // DataTable
    $('#tabelZakat').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.zakat.data") }}',
        columns: [
            { title: 'Tanggal', data: 'tanggal', render: d => moment(d).format('DD MMM YYYY') },
            { title: 'Kwitansi', data: 'kwitansi' },
            { title: 'Muzakki', data: 'muzakki' },
            { title: 'Jenis', data: 'jenis' },
            { title: 'Jiwa', data: 'total_jiwa' },
            { title: 'Jumlah', data: 'jumlah_fmt', className: 'text-end' },
            {
                title: 'Aksi',
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="btn-group btn-group-sm">
                            <a href="#" class="btn btn-info btn-edit" data-id="${row.id}" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="#" class="btn btn-danger btn-delete" data-id="${row.id}" title="Hapus">
                                <i class="fas fa-trash"></i>
                            </a>
                            <a href="${routes.kwitansi.replace(':id', row.id)}" target="_blank" class="btn btn-success" title="Print Kwitansi">
                                <i class="fas fa-print"></i>
                            </a>
                        </div>
                    `;
                },
                className: 'text-center',
                orderable: false,
                searchable: false
            }
        ],
        order: [[0, 'desc']],
        rowGroup: { dataSrc: 'tanggal' }
    });

    // Delete handler
    $('#tabelZakat').on('click', '.btn-delete', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Transaksi?',
            text: 'Data akan dihapus permanen.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: routes.delete.replace(':id', id),
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => {
                        Swal.fire('Terhapus!', 'Transaksi dihapus.', 'success');
                        $('#tabelZakat').DataTable().ajax.reload();
                    },
                    error: () => Swal.fire('Gagal', 'Terjadi kesalahan.', 'error')
                });
            }
        });
    });
</script>
@endpush