@extends('masjid.master')
@section('title', 'Hitung Kotak Infak')

@section('content')

<div class="card mb-4 card-modern">
    <div class="card-header bg-info text-white border-0">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-box-open me-2"></i> Daftar Kotak Infak (Grup per Hari)
                </h4>
                <small class="text-white-50">Ringkasan kotak infak per hari — cepat & rapi</small>
            </div>
            <div>
                <button class="btn btn-light btn-modern" id="reload-table" title="Muat ulang">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover align-middle" id="tabel-kotak" width="100%">
                <thead class="table-light">
                    <tr>
                        <!-- Tanggal ada di group header -->
                        <th>Jenis Kotak</th>
                        <th class="text-end">Jumlah</th>
                        <th width="90" class="text-center">Detail</th>
                        <th width="160" class="text-center">Aksi</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

<div class="card card-modern">
    <div class="card-header bg-warning text-dark border-0">
        <h4 class="mb-0">
            <i class="fas fa-calculator me-2"></i> Hitung Kotak Infak Baru
        </h4>
    </div>
    <div class="card-body">
        <form id="form-kotak" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Jenis Kotak</label>
                <select name="jenis_kotak_id" class="form-select form-control-lg" required>
                    <option value="">— Pilih Kotak —</option>
                    @foreach($jenis as $j)
                        <option value="{{ $j->id }}">{{ $j->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-lg" value="{{ date('Y-m-d') }}" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">Keterangan (Opsional)</label>
                <input type="text" name="keterangan" class="form-control form-control-lg" placeholder="Misal: Jumat, 14 Nov 2025">
            </div>

            <div class="col-12">
                <div class="table-responsive mb-3">
                    <table class="table table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30%">Nominal</th>
                                <th width="30%">Lembar</th>
                                <th width="40%">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([100000, 50000, 20000, 10000, 5000, 2000, 1000] as $i => $nom)
                            <tr>
                                <td class="fw-bold align-middle">Rp {{ number_format($nom, 0, ',', '.') }}</td>
                                <td>
                                    <input type="number" name="lembar[{{ $i }}]" class="form-control lembar" min="0" value="0" data-nom="{{ $nom }}">
                                    <input type="hidden" name="nominal[{{ $i }}]" value="{{ $nom }}">
                                </td>
                                <td class="subtotal fw-bold text-end align-middle">Rp 0</td>
                            </tr>
                            @endforeach
                            <tr class="table-success">
                                <th colspan="2" class="text-end">TOTAL</th>
                                <th id="total" class="text-end fw-bold">Rp 0</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-md-8">
                <label class="form-label">Bukti Foto (Opsional)</label>
                <input type="file" name="bukti_kotak" class="form-control" accept="image/*">
                <small class="text-muted">Upload foto kotak setelah dihitung (opsional).</small>
            </div>

            <div class="col-md-4 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-success btn-lg btn-modern w-100">
                    <span class="spinner-border spinner-border-sm d-none me-2"></span>
                    <i class="fas fa-save me-2"></i> Simpan & Masuk Keuangan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Kotak (modal-lg, scrollable) -->
<div class="modal fade" id="modal-detail" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title"><i class="fas fa-receipt me-2"></i> Detail Kotak Infak</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-body">
                <!-- diisi oleh JS -->
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/rowgroup/1.4.0/css/rowGroup.bootstrap5.min.css">
<style>
    /* ------------------------------
       Palet (ubah sesuai brand masjidmu)
       ------------------------------ */
    :root{
        --accent-1: #4f46e5; /* indigo */
        --accent-2: #06b6d4; /* teal/cyan */
        --accent-soft: linear-gradient(90deg, rgba(79,70,229,0.95) 0%, rgba(6,182,212,0.95) 100%);
        --card-bg: #ffffff;
        --muted: #6b7280;
        --glass: rgba(255,255,255,0.6);
    }

    /* ------------------------------
       Tables – rounded header + subtle rows
       ------------------------------ */
    table#tabel-kotak {
        border-radius: 10px;
        overflow: hidden;
        border-collapse: separate;
    }
    table#tabel-kotak thead th {
        background: linear-gradient(90deg, rgba(248,250,252,1), rgba(239,246,255,1));
        border-bottom: none;
        font-weight: 700;
        color: #111827;
        padding: 12px 14px;
        font-size: .95rem;
    }
    table#tabel-kotak tbody tr {
        transition: transform .16s ease, background .18s ease;
    }
    table#tabel-kotak tbody tr:hover {
        transform: translateY(-2px);
        background: rgba(79,70,229,0.03);
    }
    .table td, .table th { vertical-align: middle; padding: 10px 12px; }

    /* ------------------------------
       RowGroup header — stylish
       ------------------------------ */
    .group-header {
        background: linear-gradient(135deg, rgba(79,70,229,0.95) 0%, rgba(6,182,212,0.9) 100%) !important;
        color: #fff !important;
        border-bottom: 0;
        padding: 12px 16px;
    }
    .group-header .fa-calendar-week {
        opacity: .95;
        color: rgba(255,255,255,0.95);
    }
    .group-header h5 { margin: 0; font-size: 1rem; letter-spacing: .2px; }
    .group-header small { opacity: .95; color: rgba(255,255,255,0.92); }

    /* group total card (contrasting) */
    .group-total-card {
        background: rgba(255,255,255,0.12);
        border-radius: 12px;
        padding: 10px 14px;
        box-shadow: 0 6px 18px rgba(0,0,0,0.06);
        display: inline-block;
    }
    .group-total-card .fs-5 { font-weight: 800; color: #fff; }

    /* ------------------------------
       Buttons: modern accent
       ------------------------------ */
    .btn-modern {
        border-radius: 999px;
        font-weight: 700;
        padding: .5rem .95rem;
        transition: transform .18s ease, box-shadow .18s ease;
        border: 0;
    }
    .btn-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(79,70,229,0.12);
    }

    .btn-outline-primary {
        border: 1.6px solid rgba(79,70,229,0.09);
        color: var(--accent-1);
        background: linear-gradient(180deg, rgba(255,255,255,0.9), rgba(255,255,255,0.7));
        padding-left: 1.05rem;
        padding-right: 1.05rem;
    }

    .btn-success {
        background: linear-gradient(90deg, rgba(34,197,94,1), rgba(16,185,129,1));
        border: 0;
    }

    .btn-outline-primary.btn-lg {
        box-shadow: 0 6px 18px rgba(79,70,229,0.06);
    }

    /* action cells */
    .btn-action {
        min-width: 40px;
        border-radius: 8px;
    }

    /* subtle helper text */
    .text-muted-small { font-size:.92rem; color: var(--muted); }

    /* form inputs larger & softer */
    .form-control, .form-select {
        border-radius: 10px;
        padding: .55rem .85rem;
        box-shadow: inset 0 1px 0 rgba(16,24,40,0.02);
        border: 1px solid rgba(15,23,42,0.06);
    }
    .form-control:focus, .form-select:focus {
        outline: none;
        box-shadow: 0 8px 30px rgba(79,70,229,0.08);
        border-color: rgba(79,70,229,0.6);
    }

    /* modal modern */
    .modal-content {
        border-radius: 12px;
        overflow: hidden;
    }
    .modal-header {
        border-bottom: none;
    }

    /* responsive tweaks */
    @media (max-width: 992px) {
        .card-modern .card-header { padding: 12px 14px; }
        table#tabel-kotak thead th { font-size: .88rem; }
        .group-total-card { display:block; margin-top:8px; text-align:center; }
    }
    /* Pastikan wrapper card memakai radius & clipping */
    .card-modern {
      border-radius: 14px !important;
      overflow: hidden !important;
      -webkit-border-radius: 14px !important;
      -moz-border-radius: 14px !important;
      box-shadow: 0 12px 30px rgba(16,24,40,0.08) !important;
      border: 0 !important;
      background-clip: padding-box !important; /* jaga agar background tidak meluber */
    }

    /* Header harus benar2 ikut membulat (override Bootstrap .card-header:first-child) */
    .card.card-modern > .card-header:first-child,
    .card-modern > .card-header:first-child,
    .card-modern .card-header:first-child {
      border-top-left-radius: 14px !important;
      border-top-right-radius: 14px !important;
      border-radius: 14px 14px 0 0 !important;
      background-clip: padding-box !important;
    }

    /* Jika ada card-footer, bulatkan sudut bawah */
    .card.card-modern > .card-footer:last-child,
    .card-modern > .card-footer:last-child,
    .card-modern .card-footer:last-child {
      border-bottom-left-radius: 14px !important;
      border-bottom-right-radius: 14px !important;
    }

    /* Hapus default kecilnya bootstrap yang mungkin masih aktif */
    .card-header:first-child {
      border-radius: 0 !important;
    }

    /* Pastikan inner elements tidak menimpa sudut (mis. background pada header) */
    .card-modern .card-header {
      background-clip: padding-box !important;
    }

    /* Safety: jika parent container menambahkan background/outline, kurangi overflow */
    .card-modern * {
      box-sizing: border-box;
    }

    /* Responsive tweak */
    @media (max-width: 576px) {
      .card-modern { border-radius: 12px !important; }
      .card-modern .card-header { border-top-left-radius: 12px !important; border-top-right-radius: 12px !important; }
    }

    /* Jika ada footer pada card, bulatkan sudut bawah */
    .card-modern .card-footer {
      border-bottom-left-radius: 14px !important;
      border-bottom-right-radius: 14px !important;
    }

    /* kecilkan padding agar rounded terlihat rapi di mobile */
    @media (max-width: 576px) {
      .card-modern { border-radius: 12px !important; }
      .card-modern .card-header { padding: 12px !important; }
      .card-modern .card-body { padding: 14px !important; }
    }


</style>
@endpush

@push('scripts')
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.4.0/js/dataTables.rowGroup.min.js"></script>

<script>
$(document).ready(function() {

    // reload table short handler
    $('#reload-table').on('click', function() {
        table.ajax.reload(null, false);
        $(this).addClass('text-success').delay(600).queue(function(next){ $(this).removeClass('text-success'); next(); });
    });

    // === DATATABLES ===
    const table = $('#tabel-kotak').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.kotak.list") }}',
        columns: [
            { data: 'jenis', name: 'jenis_kotak.nama' },
            { data: 'jumlah', name: 'total', className: 'text-end fw-bold text-success' },
            { data: 'detail_btn', orderable: false, searchable: false, className: 'text-center' },
            { data: 'aksi', orderable: false, searchable: false, className: 'text-center' },
        ],
        order: [[0, 'desc']],
        rowGroup: {
            dataSrc: 'tanggal_group',
            startRender: function(rows, group) {
                const totalHari = rows.data().pluck('jumlah')
                    .reduce((a, b) => a + parseInt((b+'').replace(/[^0-9]/g, '') || 0), 0);

                const firstRow = rows.data().toArray()[0] || {};
                const tanggalRaw = firstRow.tanggal_raw || '';
                const sudahDihitung = firstRow.sudah_dihitung || false;

                const badge = sudahDihitung
                    ? '<span class="badge bg-success rounded-pill px-3 py-2"><i class="fas fa-check-double me-1"></i>Sudah Dijumlahkan</span>'
                    : `<button class="btn btn-warning btn-modern hitung-ulang-hari" data-tanggal="${tanggalRaw}">
                           <i class="fas fa-calculator me-1"></i> Hitung Ulang
                       </button>`;

                return $(`
                    <tr class="group-header">
                        <td colspan="2" class="text-start">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-week me-3 fa-2x opacity-85"></i>
                                <div>
                                    <h5 class="mb-0">${group}</h5>
                                    <small class="text-white-75">${rows.count()} kotak</small>
                                </div>
                            </div>
                        </td>
                        <td class="text-end">
                            <div class="group-total-card">
                                <div class="fs-5 fw-bold">Rp ${totalHari.toLocaleString('id-ID')}</div>
                                <small class="text-white-50">Total Hari Ini</small>
                            </div>
                        </td>
                        <td class="text-center">
                            ${badge}
                        </td>
                    </tr>
                `);
            }
        },
        columnDefs: [
            { targets: 0, className: 'ps-4' }
        ],
        language: {
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>',
            emptyTable: '<div class="text-center py-5"><i class="fas fa-inbox fa-3x text-muted mb-3"></i><br>Belum ada kotak infak</div>',
            info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ kotak",
            infoEmpty: "Tidak ada data",
            lengthMenu: "Tampilkan _MENU_ kotak",
            paginate: { previous: "<i class='fas fa-chevron-left'></i>", next: "<i class='fas fa-chevron-right'></i>" }
        },
        drawCallback: function() {
            // tambahkan subtle animation pada row group saat muncul
            $('.group-header').css({opacity:0, transform:'translateY(4px)'}).animate({opacity:1, transform:'translateY(0)'}, 300);
        }
    });

    // === HITUNG TOTAL DI FORM ===
    $(document).on('input', '.lembar', function() {
        const nom = parseInt($(this).data('nom'));
        const lem = parseInt($(this).val()) || 0;
        $(this).closest('tr').find('.subtotal').text('Rp ' + (nom * lem).toLocaleString('id-ID'));

        let total = 0;
        $('.lembar').each(function() {
            total += parseInt($(this).data('nom')) * (parseInt($(this).val()) || 0);
        });
        $('#total').text('Rp ' + total.toLocaleString('id-ID'));
    });

    // === SUBMIT FORM ===
    $('#form-kotak').on('submit', function(e) {
        e.preventDefault();
        if (!$('[name="jenis_kotak_id"]').val()) {
            alert('Pilih jenis kotak terlebih dahulu!');
            return;
        }

        const formData = new FormData(this);
        const btn = $(this).find('button[type="submit"]');
        const spinner = btn.find('.spinner-border');

        btn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.ajax({
            url: '{{ route("admin.keuangan.kotak.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: res.message || 'Berhasil disimpan!'
                });

                $('#form-kotak')[0].reset();
                $('.subtotal').text('Rp 0');
                $('#total').text('Rp 0');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                const msg = xhr.responseJSON?.message || 'Terjadi kesalahan!';
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    text: msg
                });
            },
            complete: function() {
                btn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    // === HITUNG ULANG HARI ===
    $(document).on('click', '.hitung-ulang-hari', function() {
        const tanggal = $(this).data('tanggal');
        Swal.fire({
            title: 'Hitung Ulang Semua Kotak Hari Ini?',
            text: 'Semua kotak infak di tanggal ini akan dijumlahkan menjadi 1 transaksi harian.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hitung Ulang!'
        }).then(result => {
            if (!result.isConfirmed) return;
            Swal.fire({ title: 'Menghitung...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            $.post('{{ route("admin.keuangan.kotak.recount-hari") }}', {
                _token: '{{ csrf_token() }}',
                tanggal: tanggal
            })
            .done(() => {
                Swal.fire('Berhasil!', 'Semua kotak hari ini sudah dijumlahkan.', 'success');
                table.ajax.reload(null, false);
            })
            .fail(xhr => Swal.fire('Gagal', xhr.responseJSON?.message || 'Error', 'error'));
        });
    });

    // DETAIL KOTAK — tanpa preview gambar, hanya tombol a href (buka tab baru)
    $(document).on('click', '.detail-kotak-btn', function() {
        const data = $(this).data('kotak');

        let html = `
            <div class="text-center mb-2 pb-4 border-bottom">
                <h3 class="text-primary fw-bold mb-1">${data.jenis}</h3>
                <h2 class="text-success fw-bold mb-0">Rp ${parseInt(data.total).toLocaleString('id-ID')}</h2>
                <p class="text-muted-small mt-2">Rincian lembar per nominal</p>
            </div>`;

        if (data.bukti) {
            let imgUrl = `${data.bukti}?v=${Date.now()}`; // cache buster

            html += `
                <div class="text-center my-3">
                    <a href="${imgUrl}" target="_blank" rel="noopener noreferrer"
                       class="btn btn-outline-primary btn-lg px-4 py-2 btn-modern">
                        <i class="fas fa-image me-2"></i> Lihat Foto Bukti
                    </a>
                    <p class="mt-2 text-primary"><i class="fas fa-camera me-1"></i> Klik tombol untuk membuka foto di tab baru</p>
                </div>
            `;
        } else {
            html += `
                <div class="text-center py-4 text-muted">
                    <i class="fas fa-image fa-3x mb-3 opacity-50"></i>
                    <p class="fs-5 mb-0">Tidak ada foto bukti</p>
                </div>`;
        }

        // Tabel detail lembaran
        html += `
        <div class="table-responsive mt-3">
            <table class="table table-bordered table-hover mb-0">
                <thead class="table-primary">
                    <tr>
                        <th>Nominal</th>
                        <th class="text-center">Lembar</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>`;

        data.details.forEach(d => {
            html += `
            <tr>
                <td class="fw-bold">Rp ${parseInt(d.nominal).toLocaleString('id-ID')}</td>
                <td class="text-center fs-6">${d.lembar}</td>
                <td class="text-end fw-bold text-success fs-6">Rp ${parseInt(d.subtotal).toLocaleString('id-ID')}</td>
            </tr>`;
        });

        html += `
            <tr class="table-success fw-bold">
                <td colspan="2" class="text-end">TOTAL KOTAK</td>
                <td class="text-end text-primary">Rp ${parseInt(data.total).toLocaleString('id-ID')}</td>
            </tr>
            </tbody>
            </table>
        </div>`;

        $('#detail-body').html(html);
        $('#modal-detail').modal('show');
    });

    // trigger initial
    $('.lembar').trigger('input');
});
</script>
@endpush