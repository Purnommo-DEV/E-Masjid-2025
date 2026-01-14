@extends('masjid.master')
@section('title', 'Keuangan')
@section('content')
<div class="container-fluid">

  {{-- ROW: Saldo Awal & Saldo Akhir --}}
  <div class="row g-3 mb-3 align-items-stretch">

    {{-- Card Saldo Awal --}}
    <div class="col-md-6 d-flex">
      <div class="card shadow-sm flex-fill">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0 text-muted">Saldo Awal Bulan Ini</h6>
          <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#koreksiModal">
            <i class="fas fa-edit"></i> Koreksi
          </button>
        </div>
        <div class="card-body">
          <p class="h4 mb-0">
            Rp <span id="saldo-awal-display">{{ number_format($saldoInfo['jumlah'] ?? 0, 0, ',', '.') }}</span>
          </p>
          <small class="text-muted d-block">Periode: {{ now()->format('F Y') }}</small>
          <small id="status-saldo"
                 class="d-block {{ ($saldoInfo['manual'] ?? false) ? 'text-warning' : 'text-success' }}">
            {{ ($saldoInfo['manual'] ?? false) ? 'Koreksi manual' : 'Otomatis dari bulan sebelumnya' }}
          </small>
        </div>
      </div>
    </div>

    {{-- Card Saldo Akhir --}}
    <div class="col-md-6 d-flex">
      <div class="card shadow-sm flex-fill">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h6 class="mb-0 text-muted">Saldo Akhir Bulan Ini</h6>
          <button class="btn btn-sm btn-outline-success" id="btnViewSaldo">
            <i class="fas fa-eye"></i> Detail
          </button>
        </div>
        <div class="card-body">
          <p class="h4 mb-0 text-success">
            Rp <span id="saldo-akhir-display">{{ number_format($saldoAkhir ?? 0, 0, ',', '.') }}</span>
          </p>
          <small class="text-muted">Akan jadi saldo awal bulan depan</small>
        </div>
      </div>
    </div>
  </div>

  {{-- Card Transaksi Manual (collapse) --}}
  <div class="row g-3 mb-3">
    <div class="col-12 d-flex">
      <div class="card shadow-sm flex-fill">
        <div class="card-body d-flex align-items-center justify-content-between">
          <div>
            <h6 class="mb-1 text-muted">Transaksi Manual</h6>
            <p class="mb-0"><small>Tambahkan transaksi cepat</small></p>
          </div>
          <a href="#" class="btn btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#manualForm"
             aria-expanded="false">
            <i class="fas fa-plus-circle me-1"></i> Tambah Cepat
          </a>
        </div>

        <div class="collapse" id="manualForm">
          <div class="card-body border-top">
            <form id="manualFormForm" action="{{ route('admin.keuangan.store') }}" method="POST"
                  enctype="multipart/form-data" class="row g-2 align-items-end">
              @csrf
              <div class="col-md-4">
                <label class="form-label small mb-1">Kategori</label>
                <select name="kategori_id" class="form-select form-select-sm" required>
                  @foreach($kategori as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label small mb-1">Jumlah</label>
                <input type="text" name="jumlah" class="form-control form-control-sm input-amount" required>
              </div>

              <div class="col-md-2">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" name="tanggal" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
              </div>

              <div class="col-md-2">
                <label class="form-label small mb-1">Bukti</label>
                <input type="file" name="bukti" class="form-control form-control-sm">
              </div>

              <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-paper-plane me-1"></i> Simpan
                </button>
              </div>

              <div class="col-12">
                <label class="form-label small mb-1">Deskripsi</label>
                <textarea name="deskripsi" class="form-control form-control-sm" rows="2" required></textarea>
              </div>
            </form>
          </div>
        </div>

      </div>
    </div>
  </div>

  {{-- Tabel Transaksi --}}
  <div class="card shadow-sm mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
      <div>
        <h5 class="mb-0">Daftar Transaksi</h5>
        <small class="text-muted">Kelola pemasukan & pengeluaran</small>
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="table.ajax.reload()">
          <i class="fas fa-sync-alt"></i> Refresh
        </button>
        <a href="{{ route('admin.keuangan.kotak') }}" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-calculator"></i> Hitung Kotak
        </a>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-2 mb-3 align-items-center">
        <div class="col-md-3">
          <label class="form-label small mb-1">Dari</label>
          <input type="date" id="filter_start" class="form-control form-control-sm" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label small mb-1">Sampai</label>
          <input type="date" id="filter_end" class="form-control form-control-sm" value="{{ now()->format('Y-m-d') }}">
        </div>
        <div class="col-md-3">
          <label class="form-label small mb-1 d-block">&nbsp;</label>
          <div class="d-flex gap-2">
            <button class="btn btn-primary btn-sm" id="btnFilter"><i class="fas fa-filter me-1"></i> Filter</button>
            <button class="btn btn-outline-secondary btn-sm" id="btnClear"><i class="fas fa-eraser me-1"></i> Reset</button>
          </div>
        </div>
        <div class="col-md-3 text-end">
          <label class="form-label small mb-1 d-block">&nbsp;</label>
          <div class="text-muted small">Menampilkan <span id="table-info-count">-</span> transaksi</div>
        </div>
      </div>

      <div class="table-responsive">
        <table id="transaksiTable" class="table table-hover align-middle" style="width:100%">
          <thead class="table-light">
            <tr>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th class="text-end">Jumlah</th>
              <th>Deskripsi</th>
              <th>Dibuat Oleh</th>
              <th>Bukti</th>
              <th class="text-end">Sisa Saldo</th>
              <th style="width:140px">Aksi</th>
            </tr>
          </thead>
        </table>
      </div>

    </div>
  </div>

  {{-- Modal Edit --}}
  <div class="modal fade" id="editModal" tabindex="-1">
    <form id="editForm" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="_method" value="PUT">
      <input type="hidden" id="edit_id">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Edit Transaksi</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label small mb-1">Kategori</label>
                <select id="edit_kategori_id" name="kategori_id" class="form-select" required>
                  @foreach($kategori as $k)
                    <option value="{{ $k->id }}">{{ $k->nama }}</option>
                  @endforeach
                </select>
              </div>

              <div class="col-md-6">
                <label class="form-label small mb-1">Jumlah</label>
                <input type="text" id="edit_jumlah" name="jumlah" class="form-control input-amount" required>
              </div>

              <div class="col-md-6">
                <label class="form-label small mb-1">Tanggal</label>
                <input type="date" id="edit_tanggal" name="tanggal" class="form-control" required>
              </div>

              <div class="col-md-6">
                <label class="form-label small mb-1">Bukti (opsional)</label>
                <input type="file" id="edit_bukti" name="bukti" class="form-control">
                <div id="bukti_preview" class="mt-2"></div>
              </div>

              <div class="col-12">
                <label class="form-label small mb-1">Deskripsi</label>
                <textarea id="edit_deskripsi" name="deskripsi" class="form-control" rows="3" required></textarea>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary" id="saveEditBtn">
              <i class="fas fa-save me-1"></i> Simpan
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>

  {{-- Modal Koreksi --}}
  <div class="modal fade" id="koreksiModal">
    <form id="koreksiForm" action="{{ route('admin.keuangan.saldo') }}" method="POST">
      @csrf
      <div class="modal-dialog modal-sm">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">Koreksi Saldo Awal</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" name="periode" value="{{ now()->format('Y-m') }}">
            <div class="mb-3">
              <label class="form-label">Jumlah</label>
              <input type="text" name="jumlah" class="form-control input-amount" value="{{ number_format($saldoInfo['jumlah'] ?? 0, 0, ',', '.') }}" required>
              <small class="text-muted">Otomatis: Rp {{ number_format($saldoInfo['jumlah'] ?? 0, 0, ',', '.') }}</small>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-warning">Simpan</button>
          </div>
        </div>
      </div>
    </form>
  </div>

</div>
@endsection

@push('styles')
<style>
/* Modern curved cards & softer headers */
.card {
  border-radius: 14px;
  box-shadow: 0 6px 18px rgba(20, 30, 50, 0.06);
  overflow: hidden;
}

/* Header: light, subtle gradient, no harsh dark */
.card .card-header {
  background: linear-gradient(90deg, #f8fafc 0%, #eef2f7 100%);
  border-bottom: 1px solid rgba(0,0,0,0.04);
  color: #334155;
}

/* Smaller header text, spaced */
.card .card-header h6,
.card .card-header .mb-0 {
  font-weight: 600;
  font-size: 0.95rem;
}

/* Softer buttons */
.btn-outline-primary, .btn-outline-secondary, .btn-outline-success, .btn-outline-warning {
  border-radius: 10px;
  padding: 6px 10px;
}

/* Make card-body more airy */
.card-body { padding: 1.25rem; }

/* Form small inputs consistent */
.form-control-sm {
  border-radius: 8px;
}

/* DataTable header lighter */
.table thead th {
  background: rgba(245,247,250,1);
  color: #334155;
  font-weight: 600;
}

/* Small spinner in buttons */
.btn .spinner-border-sm { width: 1rem; height: 1rem; border-width: 0.12rem; }

/* Ensure collapse card interior fits */
.collapse .card-body { padding-top: 0.8rem; padding-bottom: 0.8rem; }

/* Slight hover for rows */
.table tbody tr:hover { background: rgba(0,0,0,0.02); }
</style>
@endpush

@push('scripts')
<script>
(function($){
    // ROUTE helper (pastikan ini sesuai)
    window.routes = {
        index: '{{ route('admin.keuangan.index') }}',
        data: '{{ route('admin.keuangan.data') }}',
        store: '{{ route('admin.keuangan.store') }}',
        edit: (id) => `{{ route('admin.keuangan.edit', ':id') }}`.replace(':id', id),
        update: (id) => `{{ route('admin.keuangan.update', ':id') }}`.replace(':id', id),
        destroy: (id) => `{{ route('admin.keuangan.destroy', ':id') }}`.replace(':id', id),
        kotak: '{{ route('admin.keuangan.kotak') }}',
        saldoCheck: '{{ route('admin.keuangan.saldo.check') }}',
        saldoStore: '{{ route('admin.keuangan.saldo') }}'
    };

    toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: "4000" };

    function formatToRupiah(number) {
        const n = Number(number) || 0;
        return n.toLocaleString('id-ID');
    }

      // ----------------------------
      // Input formatting binder
      // ----------------------------
    function bindAmountInput(selector) {
        $(selector).off('input.amountFormat').on('input.amountFormat', function () {
          const raw = this.value || '';
          // keep digits only
          const digits = raw.replace(/[^\d]/g, '');
          if (digits === '') {
            this.value = '';
            return;
          }
          this.value = Number(digits).toLocaleString('id-ID');
          try { this.setSelectionRange(this.value.length, this.value.length); } catch(e){}
        });
    }

    // apply to initial inputs
    bindAmountInput('.input-amount');

    // ----------------------------
    // button loading helper
    // ----------------------------
    function setButtonLoading($btn, loading=true, text='Menyimpan...') {
        if (!$btn || $btn.length === 0) return;
            if (loading) {
              if (!$btn.data('original-html')) $btn.data('original-html', $btn.html());
              $btn.prop('disabled', true);
              $btn.html(`<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> ${text}`);
            } else {
              $btn.prop('disabled', false);
              const orig = $btn.data('original-html') || $btn.html();
              $btn.html(orig);
              $btn.removeData('original-html');
            }
    }

    // Robust money parser (fixed)
    // Return integer number of units (no decimals)
    // Examples:
    // "2.000"   -> 2000
    // "20.000"  -> 20000
    // "200.000" -> 200000
    // "50000.00"-> 50000
    // "50,000.00"->50000
    function parseMoneyToInteger(value) {
        if (value === null || value === undefined) return 0;
        if (typeof value === 'number') return Math.round(value);

        let s = String(value).trim();
        if (!s) return 0;

        // remove spaces
        s = s.replace(/\s+/g, '');

        // negative?
        const negative = /^-/.test(s);
        if (negative) s = s.replace(/^-/, '');

        const hasDot = s.indexOf('.') !== -1;
        const hasComma = s.indexOf(',') !== -1;

        // both present -> rightmost is decimal separator
        if (hasDot && hasComma) {
            if (s.lastIndexOf(',') > s.lastIndexOf('.')) {
              // comma is decimal separator: remove dots (thousands), replace comma->dot
              const cleaned = s.replace(/\./g, '').replace(/,/g, '.');
              const num = parseFloat(cleaned);
              return negative ? -Math.round(num) : Math.round(num);
            } else {
              // dot is decimal separator: remove commas (thousands)
              const cleaned = s.replace(/,/g, '');
              const num = parseFloat(cleaned);
              return negative ? -Math.round(num) : Math.round(num);
            }
        }

        // only comma present
        if (!hasDot && hasComma) {
            const lastCommaIdx = s.lastIndexOf(',');
            const decLen = s.length - lastCommaIdx - 1;
            if (decLen === 1 || decLen === 2) {
              // likely decimal (e.g. "123,45")
              const cleaned = s.replace(/\./g, '').replace(/,/g, '.');
              const num = parseFloat(cleaned);
              return negative ? -Math.round(num) : Math.round(num);
            } else {
              // treat comma as thousand separator (e.g. "1,234,567" or "50,000")
              const onlyDigits = s.replace(/[^\d]/g, '');
              return negative ? -Number(onlyDigits || 0) : Number(onlyDigits || 0);
            }
        }

        // only dot present
        if (hasDot && !hasComma) {
            const dotCount = (s.match(/\./g) || []).length;
            if (dotCount > 1) {
                // multiple dots -> thousand separators
                const onlyDigits = s.replace(/[^\d]/g, '');
                return negative ? -Number(onlyDigits || 0) : Number(onlyDigits || 0);
            } else {
              // single dot -> decide by digits after dot
              const lastDotIdx = s.lastIndexOf('.');
              const decLen = s.length - lastDotIdx - 1;
                if (decLen === 1 || decLen === 2) {
                    // likely decimal: 123.45 -> 123
                    const cleaned = s.replace(/,/g, '');
                    const num = parseFloat(cleaned);
                    return negative ? -Math.round(num) : Math.round(num);
                } else {
                    // decLen === 3 (e.g. "2.000") or >3 -> treat as thousands
                    const onlyDigits = s.replace(/[^\d]/g, '');
                    return negative ? -Number(onlyDigits || 0) : Number(onlyDigits || 0);
                }
            }
        }
        // no dot/comma -> digits only
        const onlyDigits = s.replace(/[^\d]/g, '');
        return negative ? -Number(onlyDigits || 0) : Number(onlyDigits || 0);
    }


    // ----------------------------
    // prepare FormData: convert formatted inputs -> raw numbers
    // ----------------------------
    function prepareFormData(form) {
      const fd = new FormData(form);
      $(form).find('.input-amount[name]').each(function(){
        const name = $(this).attr('name');
        const rawVal = $(this).val();
        const un = parseMoneyToInteger(rawVal); // <-- pakai fungsi baru
        fd.set(name, un);
      });
      return fd;
    }


    // ----------------------------
    // DataTable
    // ----------------------------
    let table = $('#transaksiTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
          url: routes.data,
          data: function(d) {
            d.start_date = $('#filter_start').val();
            d.end_date = $('#filter_end').val();
          }
        },
        columns: [
            { data: 'tanggal', name: 'tanggal' },
            { data: 'kategori', name: 'kategori', orderable: false, searchable: false },
            { data: 'jumlah', name: 'jumlah', className: 'text-end' },
            { data: 'deskripsi', name: 'deskripsi', orderable: false },
            { data: 'dibuat_oleh', name: 'dibuat_oleh' },
            { data: 'bukti', name: 'bukti', orderable: false, searchable: false },
            { data: 'saldo_berjalan', name: 'saldo_berjalan', className: 'text-end' },
            { data: 'aksi', name: 'aksi', orderable: false, searchable: false }
        ],
            pageLength: 25,
            responsive: true,
            drawCallback: function(settings) {
              $('#table-info-count').text(settings.json?.recordsFiltered ?? 0);
            },
            error: function(xhr, error, thrown) {
              console.error('DataTables error:', error);
            }
    });

    // filter controls
    $('#btnFilter').on('click', function(e){ e.preventDefault(); table.ajax.reload(); });
    $('#btnClear').on('click', function(){
        $('#filter_start').val('{{ now()->startOfMonth()->format("Y-m-d") }}');
        $('#filter_end').val('{{ now()->format("Y-m-d") }}');
        table.ajax.reload();
    });

    // letakkan ini di atas (di dalam IIFE kamu), agar tersedia untuk fungsi lain
    const serverSaldoInfo = @json($saldoInfo['jumlah'] ?? 0);

    // fungsi refreshSaldoAkhir yang memakai serverSaldoInfo sebagai fallback
    function refreshSaldoAkhir() {
      table.ajax.reload(function(payload) {
        if (payload && Array.isArray(payload.data) && payload.data.length > 0) {
          const last = payload.data[payload.data.length - 1];
          const raw = last.saldo_berjalan_raw ?? last.saldo_berjalan ?? null;
          const num = parseMoneyToInteger(raw);

          // Gunakan serverSaldoInfo jika num tidak valid atau benar-benar 0
          // (eksplisit check === 0 supaya bukan falsy check umum)
          const useServer = (num === 0) || Number.isNaN(num) || num === null || num === undefined;
          const finalVal = useServer ? Number(serverSaldoInfo || 0) : num;

          $('#saldo-akhir-display').text(finalVal.toLocaleString('id-ID'));
        } else {
          // tabel kosong -> tampilkan nilai dari $saldoInfo['jumlah']
          $('#saldo-akhir-display').text(Number(serverSaldoInfo || 0).toLocaleString('id-ID'));
        }
      }, false);
    }

    // ----------------------------
    // AJAX: Tambah Manual
    // ----------------------------
    $('#manualFormForm').off('submit').on('submit', function(e){
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find('button[type="submit"]').first();
        setButtonLoading($btn, true, 'Menyimpan...');
        const fd = prepareFormData(this);

        $.ajax({
            url: routes.store,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(function(res){
            toastr.success(res.message || 'Transaksi disimpan');
            $form[0].reset();
            $('#manualForm').collapse('hide');
            table.ajax.reload(null, false);
            refreshSaldoAkhir();
        }).fail(function(xhr){
            const msg = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message || 'Gagal menyimpan');
            toastr.error(msg);
        }).always(function(){ setButtonLoading($btn, false); });
    });

  // ----------------------------
  // AJAX: Koreksi Saldo
  // ----------------------------
    $('#koreksiForm').off('submit').on('submit', function(e){
            e.preventDefault();
            const $form = $(this);
            const $btn = $form.find('button[type="submit"]').first();
            setButtonLoading($btn, true, 'Menyimpan...');
            const fd = prepareFormData(this);

        $.ajax({
            url: routes.saldoStore,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(function(res){
            $('#koreksiModal').modal('hide');
            toastr.success(res.message || 'Saldo disimpan');
            if (res.saldo_formatted) $('#saldo-awal-display').text(res.saldo_formatted.replace('Rp ', ''));
            $('#status-saldo').removeClass('text-success').addClass('text-warning').text('Koreksi manual');
            refreshSaldoAkhir();
            table.ajax.reload(null, false);
        }).fail(function(xhr){
            const msg = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message || 'Gagal menyimpan');
            toastr.error(msg);
        }).always(function(){ setButtonLoading($btn, false); });
    });

    // ----------------------------
    // EDIT modal: populate & submit
    // ----------------------------
    window.editTransaksi = function(id) {
        $.get(routes.edit(id)).done(function(data){
          // debug: uncomment if needed
          // console.log('[DEBUG edit payload]', data);
            $('#edit_id').val(data.id);
            $('#edit_kategori_id').val(data.kategori_id);

            // parse jumlah dari server (handles "50000.00", "50.000", number, etc)
            const jm = parseMoneyToInteger(data.jumlah ?? 0);
            // set formatted value (string like "50.000")
            $('#edit_jumlah').off('input.amountFormat').val(formatToRupiah(jm));
            bindAmountInput('#edit_jumlah'); // rebind input formatter

            $('#edit_tanggal').val(data.tanggal);
            $('#edit_deskripsi').val(data.deskripsi);
            $('#editForm').attr('action', routes.update(id));
            $('#bukti_preview').html(data.bukti_url
            ? `<small class="text-muted">Bukti saat ini:</small><br><a href="${data.bukti_url}" target="_blank"><img src="${data.bukti_thumb}" width="100" class="rounded"></a>`
            : '<small class="text-muted">Tidak ada bukti</small>'
            );
            $('#editModal').modal('show');
        }).fail(function(){ toastr.error('Gagal memuat data transaksi'); });
    };

    $('#editForm').off('submit').on('submit', function(e){
            e.preventDefault();
            const $btn = $('#saveEditBtn');
            setButtonLoading($btn, true, 'Menyimpan...');
            const fd = prepareFormData(this);

        $.ajax({
            url: this.action,
            method: 'POST',
            data: fd,
            processData: false,
            contentType: false
        }).done(function(res){
            $('#editModal').modal('hide');
            toastr.success(res.message || 'Perubahan disimpan');
            table.ajax.reload(null, false);
            refreshSaldoAkhir();
        }).fail(function(xhr){
            const msg = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message || 'Gagal menyimpan');
            toastr.error(msg);
        }).always(function(){ setButtonLoading($btn, false); });
    });

    // ----------------------------
    // HAPUS (SweetAlert2)
    // ----------------------------
    window.hapusTransaksi = function(id) {
        if (typeof Swal === 'undefined') {
          if (!confirm('Hapus transaksi?')) return;
        }

        Swal.fire({
            title: 'Hapus Transaksi?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (!result.isConfirmed) return;

            Swal.fire({ title: 'Menghapus...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.ajax({
                url: routes.destroy(id),
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' }
            }).done(function(res){
                Swal.close();
                Swal.fire({ icon: 'success', title: 'Terhapus', text: res.message || 'Transaksi berhasil dihapus', timer: 1400, showConfirmButton: false });
                
                // reload table, lalu setelah selesai baru refresh saldo
                table.ajax.reload(function() {
                    refreshSaldoAkhir();
                }, false);

            }).fail(function(xhr){
            Swal.close();
                const msg = xhr.responseJSON?.errors ? Object.values(xhr.responseJSON.errors)[0][0] : (xhr.responseJSON?.message || 'Gagal menghapus');
                Swal.fire({ icon: 'error', title: 'Gagal', text: msg });
            });
        });
    };

    // init: check status saldo
    $.get(routes.saldoCheck, { periode: '{{ now()->format("Y-m") }}' }).done(function(res){
        if (res.manual) {
            $('#status-saldo').removeClass('text-success').addClass('text-warning').text('Koreksi manual');
        }
    });

})(jQuery);
</script>
@endpush