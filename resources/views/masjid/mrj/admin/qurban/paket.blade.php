@extends('masjid.master')

@section('title', 'Manajemen Paket Qurban')

@section('content')

@push('style')
<style>
    /* container / card */
    .card-wrapper {
        max-width: 1300px;
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
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .card-header .title { margin: 0; font-size: 1.125rem; font-weight: 700; }
    .card-header .subtitle { margin: 0; opacity: .95; font-size: .95rem; }

    .card-body { padding: 1.25rem 1.5rem; background: white; }

    /* header button */
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
        transition: transform .12s ease, background .12s;
    }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }

    /* table */
    table.dataTable td { white-space: normal !important; vertical-align: middle; }

    /* action buttons */
    .btn-circle-ico {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        transition: transform .12s ease, box-shadow .12s;
        cursor: pointer;
    }
    .btn-circle-ico:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(2,6,23,0.06); }

    /* dialog backdrop */
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; }
    .modal-box { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); }

    /* inputs */
    .input-plain { 
        border: 1px solid #e6e6e6; 
        border-radius: 8px; 
        padding: .55rem .75rem; 
        width: 100%; 
        transition: all 0.2s ease;
    }
    .input-plain:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 2px rgba(16,185,129,0.1);
    }

    /* currency input */
    .currency-input {
        text-align: right;
    }

    /* badge stok */
    .badge-stok {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.75rem;
        font-weight: 600;
    }
    .badge-stok-habis { background: #fee2e2; color: #dc2626; }
    .badge-stok-sedikit { background: #fef3c7; color: #d97706; }
    .badge-stok-banyak { background: #d1fae5; color: #059669; }

    /* select2 customization */
    .select2-container { width: 100% !important; }
    .select2-selection {
        border-radius: 8px !important;
        border-color: #e6e6e6 !important;
        min-height: 42px !important;
    }
    .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 4px !important;
    }
    .select2-selection__choice {
        background: #d1fae5 !important;
        color: #065f46 !important;
        border: none !important;
        border-radius: 20px !important;
        padding: 2px 8px !important;
    }

    /* small responsive tweak */
    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; flex-direction: column; text-align: center; }
        .card-body { padding: 1rem; }
        .header-action { padding: .4rem .7rem; }
    }
</style>
@endpush

<div class="card-wrapper">

    <!-- header -->
    <div class="card-header">
        <div>
            <h3 class="title">📦 Manajemen Paket Qurban</h3>
            <p class="subtitle">Kelola paket hewan qurban, harga, stok dan informasi lainnya</p>
        </div>

        <button type="button" class="header-action" onclick="tambahPaket()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Paket</span>
        </button>
    </div>

    <!-- body -->
    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="paketTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-center">No</th>
                        <th class="px-4 py-3 text-center">Icon</th>
                        <th class="px-4 py-3">Jenis Hewan</th>
                        <th class="px-4 py-3 text-center">Share</th>
                        <th class="px-4 py-3">Harga</th>
                        <th class="px-4 py-3 text-center">Berat</th>
                        <th class="px-4 py-3 text-center">Stok</th>
                        <th class="px-4 py-3 text-center">Urutan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>

</div>

<!-- Modal Form Tambah/Edit Paket -->
<dialog id="paketModal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl p-0 overflow-hidden">
        <form id="paketForm" class="p-5">
            @csrf
            <input type="hidden" id="method" value="POST">
            <input type="hidden" id="paket_id" name="id">

            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-emerald-800">Form Paket Qurban</h3>
                <button type="button" id="closeModalBtn" class="text-gray-600 hover:text-gray-800 text-xl">&times;</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Kolom Kiri -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Jenis Hewan <span class="text-red-500">*</span></label>
                        <select name="jenis_hewan" id="jenis_hewan" class="select select-bordered w-full" required>
                            <option value="">-- Pilih Jenis --</option>
                            <option value="sapi">🐂 Sapi</option>
                            <option value="kambing">🐐 Kambing</option>
                            <option value="kerbau">🐃 Kerbau</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Icon</label>
                        <input type="text" name="jenis_icon" id="jenis_icon" class="input-plain" placeholder="🐐" maxlength="10">
                        <p class="text-xs text-gray-400 mt-1">Emoji atau simbol singkat</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Harga (per share) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">Rp</span>
                            <input type="text" name="harga" id="harga" class="input-plain currency-input pl-8" placeholder="0" required>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Harga Full (1 ekor)</label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">Rp</span>
                            <input type="text" name="harga_full" id="harga_full" class="input-plain currency-input pl-8" placeholder="0">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Khusus untuk paket full 1 ekor sapi/kerbau</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Max Share <span class="text-red-500">*</span></label>
                        <select name="max_share" id="max_share" class="select select-bordered w-full" required>
                            <option value="1">1 (Ekor / Pribadi)</option>
                            <option value="7">7 (Patungan)</option>
                        </select>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium mb-1">Stok <span class="text-red-500">*</span></label>
                        <input type="number" name="stok" id="stok" class="input-plain" value="0" min="0" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Urutan</label>
                        <input type="number" name="urutan" id="urutan" class="input-plain" value="0" min="0">
                        <p class="text-xs text-gray-400 mt-1">Semakin kecil angka, semakin atas tampilannya</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium mb-1">Berat Min (kg)</label>
                            <input type="number" name="berat_min" id="berat_min" class="input-plain" min="0">
                        </div>
                        <div>
                            <label class="block text-sm font-medium mb-1">Berat Max (kg)</label>
                            <input type="number" name="berat_max" id="berat_max" class="input-plain" min="0">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Deskripsi Singkat</label>
                        <input type="text" name="deskripsi_singkat" id="deskripsi_singkat" class="input-plain" maxlength="255">
                        <p class="text-xs text-gray-400 mt-1">Tampil di card paket (maks 255 karakter)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1">Deskripsi Lengkap</label>
                        <textarea name="deskripsi_lengkap" id="deskripsi_lengkap" class="input-plain" rows="3"></textarea>
                    </div>

                    <div class="flex items-center gap-3">
                        <input type="checkbox" name="is_active" id="is_active" class="toggle toggle-success" value="1" checked>
                        <label class="text-sm font-medium">Aktif (Tampil di Website)</label>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-5">
                <button type="button" id="cancelBtn" class="px-4 py-2 rounded-md border border-gray-300 hover:bg-gray-50 transition">Batal</button>
                <button type="submit" class="px-4 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold transition">
                    <span class="flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan
                    </span>
                </button>
            </div>
        </form>
    </div>
</dialog>

@endsection

@push('scripts')
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- DataTables -->
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<!-- Select2 (optional, jika perlu) -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    let table;
    const modalEl = document.getElementById('paketModal');
    const $modal = $('#paketModal');
    const form = $('#paketForm');

    // Format Rupiah
    function formatRupiah(value) {
        if (!value) return '';
        let number = value.toString().replace(/\D/g, '');
        return new Intl.NumberFormat('id-ID').format(number);
    }

    function unformatRupiah(value) {
        return value ? value.toString().replace(/\D/g, '') : '';
    }

    // Show/Hide Modal
    function showDialog(d) {
        try {
            if (typeof d.showModal === 'function') d.showModal();
            else d.classList.add('modal-open');
        } catch (e) { d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try {
            if (typeof d.close === 'function') d.close();
            else d.classList.remove('modal-open');
        } catch (e) { d.classList.remove('modal-open'); }
    }

    // Format currency input
    $(document).on('input', '.currency-input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value) {
            $(this).val(new Intl.NumberFormat('id-ID').format(value));
        }
    });

    $(function(){
        // Initialize DataTable
        table = $('#paketTable').DataTable({
    processing: true,
    serverSide: false,
    ajax: '{{ route('admin.qurban.paket.data') }}',
    columns: [
        { 
            data: null, 
            orderable: false,
            className: 'text-center',
            render: (data, type, row, meta) => meta.row + 1 
        },
        { 
            data: 'icon', 
            className: 'text-center',
            render: (data) => `<span style="font-size: 24px;">${data}</span>`
        },
        { data: 'jenis_hewan' },
        { data: 'share', className: 'text-center' },
        { data: 'harga' },
        { data: 'berat', className: 'text-center' },
        { 
            data: 'stok', 
            className: 'text-center',
            render: (data) => {
                let badgeClass = 'badge-stok-banyak';
                if (data <= 0) badgeClass = 'badge-stok-habis';
                else if (data <= 5) badgeClass = 'badge-stok-sedikit';
                return `<span class="badge-stok ${badgeClass}">${data}</span>`;
            }
        },
        { data: 'urutan', className: 'text-center' },
        { data: 'status', className: 'text-center' },
        {
            data: null,
            orderable: false,
            className: 'text-center',
            render: (d) => `
                <div class="inline-flex gap-2">
                    <button class="btn-circle-ico bg-yellow-50 text-yellow-700" title="Edit" onclick="editPaket(${d.id})">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <button class="btn-circle-ico bg-red-50 text-red-700" title="Hapus" onclick="deletePaket(${d.id})">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
            `
        }
    ],
    order: [[7, 'asc']],
    language: {
        "decimal":        "",
        "emptyTable":     "Tidak ada data paket qurban",
        "info":           "Menampilkan _START_ sampai _END_ dari _TOTAL_ paket",
        "infoEmpty":      "Menampilkan 0 sampai 0 dari 0 paket",
        "infoFiltered":   "(disaring dari _MAX_ total paket)",
        "infoPostFix":    "",
        "thousands":      ",",
        "lengthMenu":     "Tampilkan _MENU_ paket",
        "loadingRecords": "Memuat...",
        "processing":     "Memproses...",
        "search":         "Cari paket:",
        "zeroRecords":    "Tidak ada paket yang cocok",
        "paginate": {
            "first":      "Pertama",
            "last":       "Terakhir",
            "next":       "Selanjutnya",
            "previous":   "Sebelumnya"
        },
        "aria": {
            "sortAscending":  ": aktifkan untuk mengurutkan kolom naik",
            "sortDescending": ": aktifkan untuk mengurutkan kolom turun"
        }
    },
    responsive: true
});

        // Modal buttons
        $('#closeModalBtn').on('click', () => closeDialog(modalEl));
        $('#cancelBtn').on('click', () => closeDialog(modalEl));
        if (modalEl) modalEl.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modalEl); });
    });

    // Reset form
    function resetForm() {
        form[0].reset();
        $('#method').val('POST');
        $('#paket_id').val('');
        $('#jenis_icon').val('');
        $('#is_active').prop('checked', true);
        $('#urutan').val(0);
        $('#stok').val(0);
        $('#max_share').val(1);
        $('#jenis_hewan').val('');
        $('.currency-input').val('');
        $('#modalTitle').text('Tambah Paket Qurban');
        form.attr('action', '{{ route('admin.qurban.paket.store') }}');
    }

    // Tambah paket
    window.tambahPaket = function() {
        resetForm();
        showDialog(modalEl);
        setTimeout(() => $('#jenis_hewan').focus(), 120);
    }

    // Edit paket
    window.editPaket = function(id) {
        $.get(`{{ url('admin/qurban/paket') }}/${id}/edit`)
            .done(function(data) {
                resetForm();
                $('#paket_id').val(data.id);
                $('#jenis_hewan').val(data.jenis_hewan);
                $('#jenis_icon').val(data.jenis_icon);
                $('#harga').val(formatRupiah(data.harga));
                if (data.harga_full) $('#harga_full').val(formatRupiah(data.harga_full));
                $('#max_share').val(data.max_share);
                $('#stok').val(data.stok);
                $('#urutan').val(data.urutan);
                $('#berat_min').val(data.berat_min);
                $('#berat_max').val(data.berat_max);
                $('#deskripsi_singkat').val(data.deskripsi_singkat);
                $('#deskripsi_lengkap').val(data.deskripsi_lengkap);
                $('#is_active').prop('checked', data.is_active == 1);
                $('#method').val('PUT');
                $('#modalTitle').text('Edit Paket Qurban');
                form.attr('action', `{{ url('admin/qurban/paket') }}/${data.id}`);
                showDialog(modalEl);
                setTimeout(() => $('#jenis_hewan').focus(), 120);
            })
            .fail(function() {
                Swal.fire('Error', 'Gagal memuat data paket.', 'error');
            });
    }

    // Delete paket
    window.deletePaket = function(id) {
        Swal.fire({
            title: 'Yakin ingin menghapus?',
            text: "Data paket ini akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/qurban/paket') }}/${id}`,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        table.ajax.reload();
                        Swal.fire('Berhasil', res.message || 'Paket dihapus.', 'success');
                    },
                    error: function(xhr) {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan.', 'error');
                    }
                });
            }
        });
    }

    // Submit form
    form.on('submit', function(e) {
        e.preventDefault();

        // Unformat currency sebelum submit
        $('#harga').val(unformatRupiah($('#harga').val()));
        if ($('#harga_full').val()) {
            $('#harga_full').val(unformatRupiah($('#harga_full').val()));
        }

        let formData = new FormData(this);
        let method = $('#method').val();
        let action = form.attr('action');

        if (method === 'PUT') {
            formData.append('_method', 'PUT');
        }

        $.ajax({
            url: action,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    closeDialog(modalEl);
                    table.ajax.reload();
                    Swal.fire('Berhasil', res.message, 'success');
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                }
            },
            error: function(xhr) {
                let msg = xhr.responseJSON?.message || 'Gagal menyimpan data.';
                Swal.fire('Error', msg, 'error');
            }
        });
    });

    // Validasi berat minimal dan maksimal
    $('#berat_min, #berat_max').on('change', function() {
        let min = parseInt($('#berat_min').val()) || 0;
        let max = parseInt($('#berat_max').val()) || 0;
        if (min > max && max > 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Perhatian',
                text: 'Berat minimal tidak boleh lebih besar dari berat maksimal!',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000
            });
            $('#berat_min').val(max);
        }
    });
</script>
@endpush