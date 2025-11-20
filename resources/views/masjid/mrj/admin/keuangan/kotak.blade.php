@extends('masjid.master')
@section('title', 'Hitung Kotak Infak')

@section('content')
<div class="card">
    <div class="card-header bg-info text-white"><h4>Daftar Kotak Infak</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered" id="tabel-kotak">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Status</th>
                        <th>Detail</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header bg-warning text-dark"><h4>Hitung Kotak Infak</h4></div>
    <div class="card-body">
        <form id="form-kotak" enctype="multipart/form-data">
            @csrf
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <select name="jenis_kotak_id" class="form-select" required>
                        <option value="">Pilih Kotak</option>
                        @foreach($jenis as $j)
                            <option value="{{ $j->id }}">{{ $j->nama }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-5">
                    <input type="text" name="keterangan" class="form-control" placeholder="Keterangan">
                </div>
            </div>

            <table class="table table-bordered">
                <thead class="table-light">
                    <tr><th>Nominal</th><th>Lembar</th><th>Subtotal</th></tr>
                </thead>
                <tbody>
                    @foreach([1000,2000,5000,10000,20000,50000,100000] as $i => $nom)
                    <tr>
                        <td>Rp {{ number_format($nom, 0, ',', '.') }}</td>
                        <td>
                            <input type="number" name="lembar[{{ $i }}]" class="form-control lembar" min="0" value="0" data-nom="{{ $nom }}">
                            <input type="hidden" name="nominal[{{ $i }}]" value="{{ $nom }}">
                        </td>
                        <td class="subtotal">Rp 0</td>
                    </tr>
                    @endforeach
                    <tr class="table-success"><th colspan="2">TOTAL</th><th id="total">Rp 0</th></tr>
                </tbody>
            </table>

            <div class="mb-3">
                <input type="file" name="bukti_kotak" class="form-control" accept="image/*">
                <small class="text-muted">Foto kotak setelah dihitung</small>
            </div>

            <button type="submit" class="btn btn-success">
                <span class="spinner-border spinner-border-sm d-none"></span>
                Simpan & Masuk Keuangan
            </button>
        </form>
    </div>
</div>

<!-- Modal Detail -->
<div class="modal fade" id="modal-detail" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detail Perhitungan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detail-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const tabel = $('#tabel-kotak tbody');
    const form = $('#form-kotak');
    const submitBtn = form.find('button[type="submit"]');
    const spinner = submitBtn.find('.spinner-border');

    function loadKotak() {
        $.get('{{ route("admin.keuangan.kotak.list") }}')
            .done(function(res) {
                tabel.empty();
                console.log(res.data);
                res.data.forEach(k => {
                    const status = k.transaksi_id
                        ? `<span class="badge bg-success">Sudah di Transaksi</span>`
                        : `<span class="badge bg-warning">Belum Dihitung</span>`;

                    const aksi = k.transaksi_id
                        ? `<a href="/admin/keuangan/transaksi/${k.transaksi_id}/edit" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>`
                        : `<button class="btn btn-sm btn-primary hitung-ulang" data-id="${k.id}"><i class="fas fa-calculator"></i></button>`;

                    const detailBtn = k.details.length > 0
                        ? `<button class="btn btn-sm btn-outline-primary detail-btn" data-details='${JSON.stringify(k.details)}' data-jumlah="${k.total}"><i class="fas fa-list"></i></button>`
                        : '—';

                    tabel.append(`
                        <tr>
                            <td>${k.tanggal}</td>
                            <td>${k.jenis_kotak.nama}</td>
                            <td>Rp ${parseInt(k.total).toLocaleString('id-ID')}</td>
                            <td>${status}</td>
                            <td>${detailBtn}</td>
                            <td>${aksi}</td>
                        </tr>
                    `);
                });
            });
    }

    $(document).on('input', '.lembar', function() {
        const nom = $(this).data('nom');
        const lem = $(this).val() || 0;
        $(this).closest('tr').find('.subtotal').text('Rp ' + (nom * lem).toLocaleString('id-ID'));
        let total = 0;
        $('.lembar').each(function() { total += $(this).data('nom') * ($(this).val() || 0); });
        $('#total').text('Rp ' + total.toLocaleString('id-ID'));
    });

    form.on('submit', function(e) {
        e.preventDefault();
        if (!form.find('[name="jenis_kotak_id"]').val()) return alert('Pilih jenis kotak!');

        const formData = new FormData(this);
        submitBtn.prop('disabled', true);
        spinner.removeClass('d-none');

        $.ajax({
            url: '{{ route("admin.keuangan.kotak.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                alert(res.message);
                form[0].reset();
                $('.subtotal').text('Rp 0');
                $('#total').text('Rp 0');
                loadKotak();
            },
            error: function(xhr) {
                alert(xhr.responseJSON?.message || 'Error!');
            },
            complete: function() {
                submitBtn.prop('disabled', false);
                spinner.addClass('d-none');
            }
        });
    });

    $(document).on('click', '.hitung-ulang', function() {
        if (!confirm('Hitung ulang kotak ini?')) return;
        $.post('{{ route("admin.keuangan.kotak.recount") }}', {
            _token: '{{ csrf_token() }}',
            kotak_id: $(this).data('id')
        })
        .done(function() {
            alert('Berhasil dihitung ulang!');
            loadKotak();
        })
        .fail(function(xhr) {
            alert(xhr.responseJSON?.message || 'Gagal!');
        });
    });

    $(document).on('click', '.detail-btn', function() {
        const details = JSON.parse($(this).data('details'));
        const jumlah = $(this).data('jumlah');
        let html = `<table class="table table-sm"><thead><tr><th>Nominal</th><th>Lembar</th><th>Subtotal</th></tr></thead><tbody>`;
        details.forEach(d => {
            html += `<tr>
                <td>Rp ${parseInt(d.nominal).toLocaleString('id-ID')}</td>
                <td>${d.jumlah_lembar}</td>
                <td>Rp ${parseInt(d.subtotal).toLocaleString('id-ID')}</td>
            </tr>`;
        });
        html += `<tr class="table-success"><th colspan="2">TOTAL</th><th>Rp ${parseInt(jumlah).toLocaleString('id-ID')}</th></tr></tbody></table>`;
        $('#detail-body').html(html);
        $('#modal-detail').modal('show');
    });

    loadKotak();
});
</script>
@endpush