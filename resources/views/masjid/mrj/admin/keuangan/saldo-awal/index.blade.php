@extends('masjid.master')
@section('title', 'Saldo Awal Periode')

@section('content')
@push('style')
<style>
  /* Outer card wrapper (seragam dengan UI sebelumnya) */
  .card-wrapper { max-width:1100px; margin:1.5rem auto; border-radius:14px; overflow:visible; box-shadow:0 12px 30px rgba(2,6,23,0.06); border:1px solid rgba(15,23,42,0.04); background:#fff; }

  .card-head {
    padding:1.25rem 1.5rem; display:flex; align-items:center; justify-content:space-between;
    background: linear-gradient(90deg,#0f766e 0%, #10b981 100%); color:#fff; border-top-left-radius:14px; border-top-right-radius:14px;
  }
  .card-head .title { margin:0; font-weight:700; font-size:1.125rem; display:flex; gap:.5rem; align-items:center; }
  .card-head .subtitle { margin:0; opacity:.95; font-size:.95rem; }

  .card-body { padding:1.5rem; background:#f8fbfa; border-bottom-left-radius:14px; border-bottom-right-radius:14px; }

  /* Form panel inside card — visible border and modern */
  .form-panel {
    background: #ffffff;
    border: 1px solid rgba(15,23,42,0.06);
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 8px 20px rgba(2,6,23,0.03);
    margin-bottom: .75rem;
  }

  /* Inputs modern */
  .form-control {
    width:100%;
    display:block;
    padding:.65rem .75rem;
    border-radius:10px;
    border:1px solid rgba(15,23,42,0.08);
    background:#fff;
    font-size:.95rem;
    transition:box-shadow .12s ease, border-color .12s ease, transform .06s ease;
    box-shadow:none;
  }
  .form-control:focus {
    outline: none;
    border-color: rgba(16,185,129,0.9);
    box-shadow: 0 6px 20px rgba(16,185,129,0.06);
    transform: translateY(-1px);
  }
  label.small { font-size:.9rem; color:#064e3b; font-weight:600; display:block; margin-bottom:.35rem; }

  /* Form top layout */
  .form-top { display:flex; gap:1rem; align-items:flex-start; flex-wrap:wrap; }
  .form-left { flex:1 1 520px; }
  .form-right { width:320px; display:flex; flex-direction:column; gap:.75rem; align-items:flex-end; }

  /* Table modern and column color separation */
  .table-clean { width:100%; border-collapse: separate; border-spacing:0 10px; }
  .table-clean thead th { text-align:left; padding:.6rem 1rem; color:#0f766e; font-weight:700; font-size:.95rem; }
  .table-clean tbody tr { background:transparent; }
  .table-clean td { padding:.6rem 0; vertical-align:middle; border:none; }

  /* make each row visually card-like with separated colored columns */
  .row-card {
    display:flex;
    gap:1rem;
    align-items:center;
    background: linear-gradient(180deg,#ffffff,#fbfffb);
    border:1px solid rgba(15,23,42,0.08); /* stronger border so edge visible */
    border-radius:10px;
    padding:.65rem .9rem;
    box-shadow: 0 10px 22px rgba(2,6,23,0.04); /* slightly stronger shadow */
  }
  .row-card .col-akun { flex:1; background:transparent; padding-right: .6rem; }
  .row-card .col-input { width:240px; text-align:right; display:flex; justify-content:flex-end; align-items:center; }

  /* add subtle background to left column so columns look separated (more visible now) */
  .col-akun-box {
    background: linear-gradient(180deg,#f0fff4,#ecfff8);      /* slightly stronger green tint */
    padding:.6rem .9rem;
    border-radius:8px;
    border:1px solid rgba(16,185,129,0.14); /* stronger border */
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.6);
  }

  /* add a visible vertical divider between columns */
  .row-card::after {
    content: "";
    position: absolute;
    pointer-events: none;
  }
  /* ensure row-card is positioned relative for pseudo element if needed */
  .row-card { position: relative; }

  /* add a right-border on the akun box to emphasize separation */
  .col-akun-box { border-right: 3px solid rgba(6,95,70,0.06); }

  /* Currency input styling — give soft bg so it stands out */
  .currency {
    text-align:right;
    font-variant-numeric: tabular-nums;
    font-weight:700;
    padding:.5rem .8rem;
    border-radius:10px;
    border:1px solid rgba(15,23,42,0.08);
    background: #fbffff; /* slightly different bg for contrast */
    min-width:160px;
  }
  .currency:focus { border-color: rgba(16,185,129,0.95); box-shadow: 0 8px 26px rgba(16,185,129,0.07); }

  /* Preview total */
  .preview-total {
    display:flex; justify-content:space-between; align-items:center;
    background: linear-gradient(90deg, rgba(16,185,129,0.06), rgba(6,95,70,0.02));
    border:1px solid rgba(16,185,129,0.12); padding:.65rem .9rem; border-radius:10px; margin-top:.9rem;
    font-weight:700; color:#064e3b; font-size:.98rem;
  }

  /* Button styles: modern, consistent */
  .btn {
    display:inline-flex; align-items:center; gap:.6rem; padding:.56rem .9rem; font-weight:700; border-radius:10px; font-size:.95rem; cursor:pointer; transition:transform .08s ease, box-shadow .12s ease;
  }
  .btn:focus { outline:none; box-shadow:0 8px 26px rgba(2,6,23,0.08); transform:translateY(-2px); }

  .btn-ghost { background:#fff; color:#0f766e; border:1px solid rgba(2,6,23,0.06); }
  .btn-ghost:hover { background:#f8fdf9; }

  .btn-warning {
    background: linear-gradient(180deg,#f59e0b,#d97706);
    color:#fff; border:none;
  }
  .btn-warning:hover { filter:brightness(.98); }

  .btn-success {
    background: linear-gradient(180deg,#059669,#047857);
    color:#fff; border:none;
  }
  .btn-success:hover { filter:brightness(.98); }

  .btn-icon {
    display:inline-flex; align-items:center; justify-content:center; width:40px; height:40px; border-radius:10px;
  }

  /* Disabled state */
  .disabled { opacity:.6; pointer-events:none; }

  /* Locked badge */
  .badge-locked { background:#ecfdf5; color:#065f46; padding:.35rem .6rem; border-radius:999px; font-weight:700; border:1px solid rgba(16,185,129,0.12); }

  @media (max-width:900px) {
    .form-right { width:100%; align-items:stretch; }
    .card-head { flex-direction:column; gap:.5rem; align-items:flex-start; }
  }
</style>
@endpush

<div class="card-wrapper">
  <div class="card-head">
    <div>
      <div class="title">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="opacity:.95"><path d="M12 3v18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Input Saldo Awal Periode
      </div>
      <div class="subtitle">Masukkan saldo awal untuk akun — angka akan dipakai untuk jurnal pembuka jika di-lock.</div>
    </div>

    <div style="display:flex;gap:.75rem;align-items:center;">
      @if($periodeTerakhir?->status === 'locked')
        <div class="badge-locked">🔒 Ter-lock — Periode {{ $periodeTerakhir->periode->format('d M Y') }}</div>
      @endif
      <button class="btn btn-ghost" onclick="window.location.reload()" title="Reset form">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M21 12A9 9 0 1 1 12 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Reset
      </button>
    </div>
  </div>

  <div class="card-body">
    <div class="form-panel">
      <form id="formSaldo">
        @csrf
        <div class="form-top">
          <div class="form-left">
            <div style="display:flex;gap:1rem;flex-wrap:wrap;">
              <div style="flex:0 0 240px">
                <label class="small">Periode Awal <span style="color:#ef4444">*</span></label>
                <input type="date" name="periode" class="form-control" required {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}>
              </div>

              <div style="flex:1 1 380px">
                <label class="small">Keterangan</label>
                <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Saldo awal tahun 2025" {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}>
              </div>
            </div>
          </div>

          <div class="form-right">
            <div style="width:100%; display:flex; gap:.6rem; justify-content:flex-end;">
              <button type="button" id="btnDraft" class="btn btn-warning" {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 7v10a2 2 0 0 0 2 2h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Simpan Draft
              </button>

              <button type="button" id="btnLock" class="btn btn-success" {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 11v6M8 11v6M4 7h16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                Simpan & Lock
              </button>
            </div>

            <div style="margin-top:.5rem; font-size:.9rem; color:#6b7280;">
              <div>Draft: simpan tanpa membuat jurnal.</div>
              <div style="color:#065f46;font-weight:600;margin-top:.35rem;">Lock: membuat jurnal pembuka dan menutup input pada periode ini.</div>
            </div>
          </div>
        </div>

        <div style="margin-top:1.1rem; overflow:auto;">
          <table class="table-clean" role="presentation">
            <thead>
              <tr>
                <th>Akun</th>
                <th style="width:240px; text-align:right;">Saldo Awal (Rp)</th>
              </tr>
            </thead>
            <tbody>
              @foreach($akuns as $akun)
              <tr>
                <td style="padding:0">
                  <div class="row-card">
                    <div class="col-akun">
                      <div class="col-akun-box">
                        <div style="font-weight:700;">{{ $akun->kode }}</div>
                        <div style="color:#6b7280;">{{ $akun->nama }}</div>
                      </div>
                    </div>

                    <div class="col-input">
                      <input type="text"
                            name="saldo[{{ $akun->id }}]"
                            class="currency"
                            value="0"
                            data-id="{{ $akun->id }}"
                            {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}>
                    </div>
                  </div>
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>

          <div class="preview-total" id="previewTotal">
            <div>Total Saldo Awal</div>
            <div id="totalAmount">Rp 0</div>
          </div>
        </div>

        <input type="hidden" name="lock" id="lockFlag" value="0">
      </form>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // helper: format angka ke tampilan Rupiah "Rp 1.234.567"
  function formatRupiahDigits(digitsStr) {
    if (!digitsStr) return 'Rp 0';
    digitsStr = digitsStr.replace(/^0+(?=\d)/, '');
    let n = digitsStr;
    return 'Rp ' + n.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  function findCaretPosFromDigitIndex(formatted, digitIndex) {
    if (digitIndex <= 0) return 0;
    let count = 0;
    for (let i = 0; i < formatted.length; i++) {
      if (/\d/.test(formatted[i])) count++;
      if (count === digitIndex) return i + 1;
    }
    return formatted.length;
  }

  function liveFormatInput(el) {
    const prev = el.value || '';
    const selStart = el.selectionStart || prev.length;
    const digitsBefore = prev.slice(0, selStart).replace(/\D/g, '').length;
    let digits = prev.replace(/\D/g, '');
    if (digits === '') {
      el.value = '';
      el.setSelectionRange(0,0);
      calculateTotal();
      return;
    }
    const formattedDigits = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    el.value = formattedDigits;
    const newPos = findCaretPosFromDigitIndex(formattedDigits, digitsBefore);
    el.setSelectionRange(newPos, newPos);
    calculateTotal();
  }

  function cleanInputForSubmitValue(el) {
    return String(el.value).replace(/\D/g, '') || '0';
  }

  function initCurrencyInputs() {
    document.querySelectorAll('.currency').forEach(function(el) {
      let initialDigits = String(el.value || '').replace(/\D/g, '');
      if (!initialDigits) initialDigits = '0';
      el.value = initialDigits === '0' ? '' : initialDigits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

      el.addEventListener('focus', function() {
        if (this.value === '' || this.value === '0') { this.value = ''; return; }
        this.setSelectionRange(this.value.length, this.value.length);
      });

      el.addEventListener('input', function(e) {
        if (e.isComposing) return;
        liveFormatInput(this);
      });

      el.addEventListener('blur', function() {
        let digits = String(this.value).replace(/\D/g, '');
        if (!digits || digits === '') { this.value = '0'; }
        else { this.value = digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.'); }
        calculateTotal();
      });
    });
  }

  function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.currency').forEach(function(el) {
      let n = Number(String(el.value).replace(/\D/g, '') || 0);
      total += n;
    });
    const totalEl = document.getElementById('totalAmount');
    totalEl.textContent = formatRupiahDigits(String(total));
  }

  function kirimSaldo(lock) {
    if ({{ $periodeTerakhir?->status === 'locked' ? 'true' : 'false' }}) {
      Swal.fire('Dilarang', 'Periode ini sudah di-lock.', 'warning');
      return;
    }

    document.getElementById('lockFlag').value = lock ? 1 : 0;
    Swal.fire({
      title: lock ? 'Simpan dan Lock saldo?' : 'Simpan draft saldo?',
      text: lock ? 'Aksi ini akan membuat jurnal pembuka dan mengunci periode.' : '',
      icon: lock ? 'warning' : 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya'
    }).then((r) => {
      if (!r.isConfirmed) return;

      document.querySelectorAll('.currency').forEach(function(el) {
        el.value = cleanInputForSubmitValue(el);
      });

      $.ajax({
        url: '{{ route("admin.keuangan.saldo-awal.store") }}',
        type: 'POST',
        data: $('#formSaldo').serialize(),
        success: function(res) {
          Swal.fire('Sukses!', res.message || 'Data tersimpan', 'success').then(() => location.reload());
        },
        error: function(xhr) {
          let msg = 'Terjadi kesalahan';
          try { msg = xhr.responseJSON?.message || Object.values(xhr.responseJSON?.errors || { })[0]?.[0] || msg; } catch(e){}
          Swal.fire('Error', msg, 'error');
        }
      });
    });
  }

  // binding
  $(function() {
    initCurrencyInputs();
    calculateTotal();

    $('#btnDraft').on('click', function() { kirimSaldo(0); });
    $('#btnLock').on('click', function() { kirimSaldo(1); });

    $(document).on('input', '.currency', function() { calculateTotal(); });

    @if($periodeTerakhir?->status === 'locked')
      document.querySelectorAll('#formSaldo input, #formSaldo select, #formSaldo button').forEach(function(i){ i.disabled = true; i.classList.add('disabled'); });
    @endif
  });
</script>
@endpush
