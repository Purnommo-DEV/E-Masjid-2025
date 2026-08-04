@extends('masjid.master')
@section('title', 'Saldo Awal Periode')

@push('style')
<style>
  .currency-input {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    padding-right: 1rem !important; /* ruang ekstra di kanan agar tidak mentok */
  }

  .currency-input:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15);
  }

  /* Prefix Rp lebih rapi */
  .input-group .prefix {
    background-color: hsl(var(--b3));
    color: hsl(var(--bc) / 0.9);
    font-weight: 600;
    border-color: hsl(var(--bc) / 0.15);
    min-width: 3.5rem;          /* lebar tetap agar konsisten */
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.75rem;
    font-size: 0.95rem;
  }

  /* Pastikan input-group tidak punya border dobel atau gap aneh */
  .input-group {
    border-radius: var(--rounded-btn, 0.5rem);
    overflow: hidden;
  }

  .input-group > * {
    border-radius: 0;
  }

  .input-group > :first-child {
    border-top-left-radius: var(--rounded-btn, 0.5rem);
    border-bottom-left-radius: var(--rounded-btn, 0.5rem);
  }

  .input-group > :last-child {
    border-top-right-radius: var(--rounded-btn, 0.5rem);
    border-bottom-right-radius: var(--rounded-btn, 0.5rem);
  }

  .card-header {
    background: linear-gradient(135deg, #0f766e 0%, #065f46 100%);
  }

  .account-item {
    transition: all 0.2s ease;
  }

  .account-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);
    border-color: hsl(var(--p) / 0.45) !important;
  }

  .account-item .kode {
    font-family: ui-monospace, monospace;
    letter-spacing: 0.6px;
  }

  @media (max-width: 640px) {
    .form-actions {
      flex-direction: column !important;
      gap: 0.75rem !important;
    }
    .form-actions .btn {
      width: 100%;
    }
    .stat {
      margin-top: 1.75rem !important;
    }
  }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-8 max-w-7xl">
  <div class="card bg-base-100 shadow-xl border border-base-200 rounded-2xl overflow-hidden">
    <div class="card-header px-6 py-6 sm:py-7 text-white">
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-5">
        <div class="space-y-2">
          <h1 class="text-2xl sm:text-3xl font-bold flex items-center gap-3">
            <svg class="w-8 h-8 opacity-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Input Saldo Awal Periode
          </h1>
          <p class="opacity-90 text-sm sm:text-base">
            Masukkan saldo awal tiap akun. Data ini akan menjadi jurnal pembuka saat periode dikunci.
          </p>
        </div>

        <div class="flex items-center gap-3 flex-wrap justify-end sm:justify-start">
          @if($periodeTerakhir?->status === 'locked')
            <div class="badge badge-lg badge-success gap-2 px-4 py-3 text-sm font-medium">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
              </svg>
              Terkunci — {{ $periodeTerakhir->periode->format('d M Y') }}
            </div>
          @endif

          @if($periodeTerakhir?->status === 'locked')
            <button id="btnCreateNewPeriod" class="btn btn-primary gap-2 shadow-md min-w-[140px]">
              <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
              </svg>
              Periode {{ now()->year }}
            </button>
          @endif

          @if($periodeTerakhir?->status !== 'locked')
              <button class="btn btn-warning gap-2 min-w-[160px]" onclick="bukaModalEditSaldo()">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                  </svg>
                  Edit & Pisahkan Dana
              </button>
          @endif
          
        {{-- ====================== MODAL EDIT SALDO AWAL ====================== --}}
        <div id="modalEditSaldo" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-black/50 backdrop-blur-sm">
            <div class="w-full max-w-2xl mx-4 max-h-[90vh]">
                <div class="bg-base-100 rounded-3xl shadow-2xl border border-base-300 overflow-hidden flex flex-col">
                    
                    <!-- Header -->
                    <div class="px-6 py-4 bg-warning text-warning-content flex items-center justify-between rounded-t-3xl flex-shrink-0">
                        <h3 class="font-bold text-lg flex items-center gap-2">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Edit & Pisahkan Saldo Awal
                        </h3>
                        <button type="button" class="btn btn-sm btn-circle btn-ghost" onclick="tutupModalEditSaldo()">✕</button>
                    </div>

                    <!-- Body -->
                    <div class="p-6 overflow-y-auto flex-1" style="max-height: calc(90vh - 140px);">
                        <form id="formEditSaldo">
                            @csrf
                            
                            <div class="bg-info/10 rounded-xl p-4 mb-4 text-sm">
                                <p class="font-semibold text-info">💡 Panduan:</p>
                                <ul class="list-disc list-inside text-base-content/80 mt-1 space-y-1">
                                    <li>Saldo awal <strong>Bank BNI</strong> adalah total keseluruhan (<strong>Rp 151.411.818</strong>)</li>
                                    <li>Pisahkan dana ke akun masing-masing sesuai jenisnya</li>
                                    <li>Total semua akun harus sama dengan saldo Bank BNI</li>
                                    <li>Akun <strong>20099 Dana Titipan</strong> akan otomatis kosong setelah dipindah</li>
                                </ul>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="table table-sm table-zebra">
                                    <thead class="bg-base-200 sticky top-0">
                                        <tr>
                                            <th class="w-20">Kode</th>
                                            <th>Nama Akun</th>
                                            <th class="text-right w-48">Saldo (Rp)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="saldoAwalList">
                                        <tr>
                                            <td colspan="3" class="text-center py-8 text-base-content/60">
                                                <span class="loading loading-spinner loading-md"></span>
                                                Memuat data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div id="totalSaldoInfo" class="mt-4 p-3 bg-base-200 rounded-xl text-center hidden">
                                <span class="text-sm text-base-content/70">Total Saldo:</span>
                                <span id="totalSaldoDisplay" class="font-bold text-lg text-primary">Rp 0</span>
                                <span id="totalSaldoStatus" class="ml-2"></span>
                            </div>

                            <div class="mt-4 flex justify-end gap-3">
                                <button type="button" class="btn btn-ghost" onclick="tutupModalEditSaldo()">Batal</button>
                                <button type="submit" class="btn btn-warning" id="btnSaveSaldo">
                                    <svg class="h-4 w-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                    </svg>
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

          <button class="btn btn-outline gap-2 min-w-[100px]" onclick="location.reload()">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            Reset
          </button>
        </div>
      </div>
    </div>

    <div class="card-body p-6 lg:p-8">
      <form id="formSaldo" class="space-y-10">
        @csrf

        {{-- ================= HEADER FORM ================= --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-end">

          {{-- LEFT : PERIODE --}}
          <div class="lg:col-span-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

              <div>
                <label class="label">
                  <span class="label-text font-semibold">
                    Periode Awal <span class="text-error">*</span>
                  </span>
                </label>
                <input
                  type="date"
                  name="periode"
                  required
                  class="input input-bordered w-full focus:input-primary"
                  {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}
                >
              </div>

              <div>
                <label class="label">
                  <span class="label-text font-semibold">Keterangan</span>
                </label>
                <input
                  type="text"
                  name="keterangan"
                  class="input input-bordered w-full"
                  placeholder="Contoh: Saldo awal tahun 2025"
                  {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}
                >
              </div>

            </div>
          </div>

          {{-- RIGHT : ACTION BUTTON --}}
          <div class="lg:col-span-4 flex flex-col gap-4 lg:items-end">

            <div class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto">

                    <button
                      type="button"
                      id="btnDraft"
                      class="btn btn-warning flex-1 inline-flex flex-row items-center justify-center gap-2 shadow-sm"
                      {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}
                    >
                      <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                      </svg>
                      <span class="leading-none">Simpan Draft</span>
                    </button>

                <button
                  type="button"
                  id="btnLock"
                  class="btn btn-success flex-1 shadow-sm"
                  {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}
                >
                  <span class="inline-flex items-center gap-2 whitespace-nowrap">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    <span class="leading-none text-sm">Kunci Periode</span>
                  </span>
                </button>

            </div>

            {{-- HELPER TEXT --}}
            <div class="text-sm opacity-70 space-y-1 text-right hidden lg:block">
              <div>Draft → simpan sementara</div>
              <div class="text-success font-medium">
                Kunci → buat jurnal & kunci permanen
              </div>
            </div>

          </div>
        </div>

        {{-- ================= DIVIDER ================= --}}
        <div class="divider my-10 text-base-content/50 font-medium">
          Daftar Akun dan Saldo Awal
        </div>

        {{-- ================= ACCOUNT LIST ================= --}}
        <div class="space-y-2 max-h-[62vh] overflow-y-auto pr-2 custom-scrollbar scroll-smooth">
            @foreach($akuns as $akun)
            <div class="group relative flex flex-col sm:flex-row sm:items-center justify-between gap-4 px-4 py-3.5
                        bg-base-100/50 hover:bg-base-200/50 rounded-xl border border-base-300/40
                        transition-all duration-200 hover:shadow-sm
                        {{ !$loop->last ? 'border-b border-base-300/30 pb-5' : '' }}">  <!-- border-b di item kecuali terakhir -->

                <!-- Kode + Nama Akun -->
                <div class="flex items-center gap-3.5 flex-1 min-w-0">
                    <div class="font-mono font-semibold text-base lg:text-lg tracking-tight text-base-content/90 whitespace-nowrap min-w-[90px] sm:min-w-[110px]">
                        {{ $akun->kode }}
                    </div>
                    <div class="text-sm opacity-80 line-clamp-1 flex-1">
                        {{ $akun->nama }}
                    </div>
                </div>

                <!-- Input Saldo -->
                <div class="w-full sm:w-auto sm:min-w-[200px] lg:min-w-[220px] shrink-0">
                    <div class="join w-full">
                        <span class="join-item btn btn-sm lg:btn-md px-4 min-w-[50px] flex items-center justify-center text-base-content/80 font-medium">
                            Rp
                        </span>
                        <input
                            type="text"
                            name="saldo[{{ $akun->id }}]"
                            class="join-item input currency-input input-bordered input-md lg:input-md font-medium text-right flex-1
                                  max-w-[160px] md:max-w-[170px] lg:max-w-[190px]
                                  focus:border-primary group-hover:border-primary/40 transition-colors"
                            value="{{ number_format($saldoAwal[$akun->id] ?? 0, 0, ',', '.') }}"
                            data-id="{{ $akun->id }}"
                            data-tipe="{{ $akun->tipe }}"
                            {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}
                        >
                    </div>
                </div>

                      <!-- Pilihan akun lawan (ekuitas) -->
                      <div class="w-full sm:w-auto sm:min-w-[200px] lg:min-w-[260px] shrink-0">
                        <label class="label mb-1 hidden sm:block">
                          <span class="label-text text-xs opacity-70">Sumber Dana (Opsional)</span>
                        </label>
                        <select name="lawan[{{ $akun->id }}]" class="select select-bordered w-full" {{ $periodeTerakhir?->status === 'locked' ? 'disabled' : '' }}>
                          <option value="">Default (Saldo Awal)</option>
                          @foreach($ekuitas as $eq)
                            <option value="{{ $eq->id }}" @if(isset($lawanMap[$akun->id]) && $lawanMap[$akun->id] == $eq->id) selected @endif>
                              {{ $eq->kode }} - {{ $eq->nama }}
                            </option>
                          @endforeach
                        </select>
                      </div>

                <!-- Garis tipis di dalam box (opsional, jika ingin lebih presisi) -->
                @if(!$loop->last)
                <div class="absolute bottom-0 left-4 right-4 h-px bg-base-300/30 pointer-events-none"></div>
                @endif
            </div>
            @endforeach
        </div>

        {{-- ================= TOTAL ================= --}}
        <div
          class="stat bg-gradient-to-br from-base-100 to-base-200/60
                 border border-base-300 rounded-2xl shadow-md mt-8"
          >

          <div class="space-y-2">
              <div class="flex justify-between">
                  <span>Total Aset</span>
                  <span id="totalAset" class="font-semibold">Rp 0</span>
              </div>

              <div class="flex justify-between">
                  <span>Total Liabilitas</span>
                  <span id="totalLiabilitas" class="font-semibold">Rp 0</span>
              </div>

              <div class="flex justify-between">
                  <span>Total Ekuitas</span>
                  <span id="totalEkuitas" class="font-semibold">Rp 0</span>
              </div>

              <div class="divider my-2"></div>

              <div class="flex justify-between items-center">
                  <span class="font-semibold">Status</span>
                  <span id="statusBalance" class="badge badge-success">
                      BALANCE ✅
                  </span>
              </div>
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
// Format Rupiah (tetap sama, tapi pastikan output konsisten)
const formatRupiah = num => isNaN(num) || num === '' ? '' : new Intl.NumberFormat('id-ID').format(num);
const parseRupiah  = str => parseInt((str||'').replace(/\D/g,'')) || 0;

function initCurrencyInputs() {
  document.querySelectorAll('.currency-input').forEach(input => {
    let raw = parseRupiah(input.value);
    input.value = raw ? formatRupiah(raw) : '0';

    input.addEventListener('input', e => {
      let val = parseRupiah(e.target.value);
      e.target.value = formatRupiah(val);
      calculateTotal();
    });

    input.addEventListener('focus', () => {
      if (input.value === '0') input.select();
    });

    input.addEventListener('blur', () => {
      let val = parseRupiah(input.value);
      input.value = val ? formatRupiah(val) : '0';
      calculateTotal();
    });
  });
}

function calculateTotal() {
    let aset = 0;
    let liabilitas = 0;
    let ekuitas = 0;

    document.querySelectorAll('.currency-input').forEach(el => {
        const nilai = parseRupiah(el.value);
        const tipe = el.dataset.tipe;

        switch (tipe) {
            case 'aset':
                aset += nilai;
                break;

            case 'liabilitas':
                liabilitas += nilai;
                break;

            case 'ekuitas':
                ekuitas += nilai;
                break;
        }
    });

    document.getElementById('totalAset').textContent =
        'Rp ' + formatRupiah(aset);

    document.getElementById('totalLiabilitas').textContent =
        'Rp ' + formatRupiah(liabilitas);

    document.getElementById('totalEkuitas').textContent =
        'Rp ' + formatRupiah(ekuitas);

    const isBalance = aset === (liabilitas + ekuitas);

    const statusEl = document.getElementById('statusBalance');

    if (isBalance) {
        statusEl.className = 'badge badge-success';
        statusEl.textContent = 'BALANCE ✅';
    } else {
        statusEl.className = 'badge badge-error';
        statusEl.textContent = 'TIDAK BALANCE ❌';
    }
}

function cleanInputsForSubmit() {
  document.querySelectorAll('.currency-input').forEach(el => {
    el.value = parseRupiah(el.value);
  });
}

// Fungsi umum AJAX submit (bisa dipakai draft & lock)
function submitSaldo(isLock = false) {
  if ({{ $periodeTerakhir?->status === 'locked' ? 'true' : 'false' }}) {
    Swal.fire('Dilarang', 'Periode ini sudah dikunci.', 'warning');
    return;
  }

  document.getElementById('lockFlag').value = isLock ? '1' : '0';

  const titleText = isLock ? 'Kunci Periode Ini?' : 'Simpan sebagai Draft?';
  const textMsg   = isLock 
    ? 'Ini akan membuat jurnal pembuka dan periode dikunci permanen.' 
    : 'Data akan disimpan sebagai draft (belum terkunci, bisa diedit lagi).';
  const iconType  = isLock ? 'warning' : 'question';
  const btnColor  = isLock ? '#10b981' : '#f59e0b';

  Swal.fire({
    title: titleText,
    text: textMsg,
    icon: iconType,
    showCancelButton: true,
    confirmButtonColor: btnColor,
    confirmButtonText: 'Ya, Lanjutkan',
    cancelButtonText: 'Batal'
  }).then(result => {
    if (!result.isConfirmed) return;

    // Tampilkan loading di tombol
    const btn = isLock ? $('#btnLock') : $('#btnDraft');
    const originalText = btn.html();
    btn.html('<span class="loading loading-spinner loading-sm"></span> Memproses...').prop('disabled', true);

    cleanInputsForSubmit();

    $.ajax({
      url: '{{ route("admin.keuangan.saldo-awal.store") }}',
      type: 'POST',
      data: $('#formSaldo').serialize(),
      success: function(res) {
        Swal.fire({
          title: 'Berhasil!',
          text: res.message || (isLock ? 'Periode berhasil dikunci.' : 'Draft berhasil disimpan.'),
          icon: 'success',
          timer: isLock ? 2000 : 1500,
          showConfirmButton: false
        });

        // Draft → tidak reload, biar bisa lanjut edit
        // Lock  → reload supaya form disabled
        if (isLock) {
          setTimeout(() => location.reload(), 1800);
        } else {
          // Kembalikan tombol ke semula
          btn.html(originalText).prop('disabled', false);
        }
      },
      error: function(xhr) {
        console.error('AJAX error (store):', xhr);
        let msg = 'Terjadi kesalahan saat menyimpan.';
        try {
          if (xhr.responseJSON) {
            if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
            else if (xhr.responseJSON.errors) msg = Object.values(xhr.responseJSON.errors)[0][0] || msg;
          } else if (xhr.responseText) {
            // kadang responseText berisi HTML/stacktrace
            msg = xhr.responseText;
          }
        } catch (e) {
          console.error('Error parsing response:', e);
        }
        Swal.fire({
          title: 'Gagal',
          html: msg.replace(/\n/g, '<br>'),
          icon: 'error',
          confirmButtonText: 'OK'
        });

        // Kembalikan tombol
        btn.html(originalText).prop('disabled', false);
      }
    });
  });
}


// =============================================
// ===== EDIT SALDO AWAL - UI =====
// =============================================

function bukaModalEditSaldo() {
    document.getElementById('modalEditSaldo').classList.remove('hidden');
    document.getElementById('modalEditSaldo').classList.add('flex');
    document.body.classList.add('overflow-hidden');
    loadSaldoAwal();
}

function tutupModalEditSaldo() {
    document.getElementById('modalEditSaldo').classList.add('hidden');
    document.getElementById('modalEditSaldo').classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function loadSaldoAwal() {
    $('#saldoAwalList').html(`
        <tr>
            <td colspan="3" class="text-center py-8 text-base-content/60">
                <span class="loading loading-spinner loading-md"></span>
                Memuat data...
            </td>
        </tr>
    `);
    $('#totalSaldoInfo').addClass('hidden');

    $.get('{{ route("admin.keuangan.saldo-awal.data") }}', function(data) {
        if (!data || data.length === 0) {
            $('#saldoAwalList').html(`
                <tr>
                    <td colspan="3" class="text-center py-8 text-base-content/60">
                        Tidak ada data saldo awal. Silakan input saldo awal terlebih dahulu.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        let total = 0;
        const targetSaldo = 151411818; // Total saldo Bank BNI
        
        data.forEach(function(item) {
            // Skip akun Bank BNI (10003) dan Opening Balance (50001)
            if (item.kode == '10003' || item.kode == '50001') return;
            
            const nominal = Number(item.jumlah || 0);
            total += nominal;
            
            // Tandai akun 20099 dengan highlight
            const isTitipan = item.kode == '20099';
            const rowClass = isTitipan ? 'bg-warning/20' : '';
            const labelTitipan = isTitipan ? ' <span class="badge badge-warning badge-sm">📦 Titipan</span>' : '';
            
            html += `
                <tr class="${rowClass}">
                    <td class="font-mono">${item.kode}</td>
                    <td>${item.nama} ${labelTitipan}</td>
                    <td>
                        <input type="text" 
                               name="saldo[${item.akun_id}]" 
                               class="input input-bordered input-sm w-full text-right font-mono ribuan saldo-input" 
                               value="${nominal.toLocaleString('id-ID')}"
                               data-kode="${item.kode}"
                               placeholder="0">
                    </td>
                </tr>
            `;
        });

        // Tambahkan baris total
        html += `
            <tr class="bg-primary/10 font-bold border-t-2 border-primary">
                <td colspan="2" class="text-right">TOTAL SALDO</td>
                <td class="text-right text-primary text-lg" id="totalSaldoDisplay">
                    Rp ${total.toLocaleString('id-ID')}
                </td>
            </tr>
        `;

        $('#saldoAwalList').html(html);
        
        // Tampilkan info total
        $('#totalSaldoInfo').removeClass('hidden');
        $('#totalSaldoDisplay').text('Rp ' + total.toLocaleString('id-ID'));
        updateTotalSaldoStatus(total);

        // Inisialisasi format ribuan
        $('.ribuan').off('input').on('input', function() {
            let v = this.value.replace(/[^\d]/g, '');
            if (!v) {
                this.value = '';
                return;
            }
            this.value = parseInt(v, 10).toLocaleString('id-ID');
            hitungTotalSaldo();
        });

    }).fail(function() {
        $('#saldoAwalList').html(`
            <tr>
                <td colspan="3" class="text-center py-8 text-error">
                    ❌ Gagal memuat data. Silakan refresh halaman.
                </td>
            </tr>
        `);
    });
}

function hitungTotalSaldo() {
    let total = 0;
    $('.saldo-input').each(function() {
        const val = $(this).val().replace(/[^\d]/g, '');
        if (val) {
            total += parseInt(val);
        }
    });
    
    $('#totalSaldoDisplay').text('Rp ' + total.toLocaleString('id-ID'));
    updateTotalSaldoStatus(total);
}

function updateTotalSaldoStatus(total) {
    const target = 151411818; // Total saldo Bank BNI
    const selisih = total - target;
    
    let statusHtml = '';
    if (selisih === 0) {
        statusHtml = '<span class="badge badge-success ml-2">✅ Balance! (' + target.toLocaleString('id-ID') + ')</span>';
    } else if (selisih > 0) {
        statusHtml = '<span class="badge badge-error ml-2">⚠️ Kelebihan Rp ' + selisih.toLocaleString('id-ID') + '</span>';
    } else {
        statusHtml = '<span class="badge badge-warning ml-2">⚠️ Kurang Rp ' + Math.abs(selisih).toLocaleString('id-ID') + '</span>';
    }
    
    $('#totalSaldoStatus').html(statusHtml);
}

// Submit form edit saldo
$('#formEditSaldo').on('submit', function(e) {
    e.preventDefault();
    
    // Validasi total
    let total = 0;
    $('.saldo-input').each(function() {
        const val = $(this).val().replace(/[^\d]/g, '');
        if (val) {
            total += parseInt(val);
        }
    });
    
    const target = 151411818;
    if (total !== target) {
        const selisih = total - target;
        let msg = `Total saldo (Rp ${total.toLocaleString('id-ID')}) tidak sama dengan saldo Bank BNI (Rp ${target.toLocaleString('id-ID')})`;
        if (selisih > 0) {
            msg += `\nKelebihan: Rp ${selisih.toLocaleString('id-ID')}`;
        } else {
            msg += `\nKekurangan: Rp ${Math.abs(selisih).toLocaleString('id-ID')}`;
        }
        Swal.fire('Perhatian!', msg, 'warning');
        return;
    }
    
    const $btn = $('#btnSaveSaldo');
    const originalText = $btn.html();
    $btn.html('<span class="loading loading-spinner loading-sm"></span> Menyimpan...').prop('disabled', true);
    
    $.ajax({
        url: '{{ route("admin.keuangan.saldo-awal.update") }}',
        method: 'POST',
        data: $(this).serialize(),
        success: function(res) {
            Swal.fire({
                icon: 'success',
                title: 'Sukses!',
                text: res.message || 'Saldo awal berhasil diupdate',
                timer: 2000,
                showConfirmButton: false
            });
            tutupModalEditSaldo();
            setTimeout(() => location.reload(), 1500);
        },
        error: function(xhr) {
            let msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
            Swal.fire('Gagal!', msg, 'error');
        },
        complete: function() {
            $btn.html(originalText).prop('disabled', false);
        }
    });
});

// Tutup modal klik backdrop
document.getElementById('modalEditSaldo').addEventListener('click', function(e) {
    if (e.target === this) tutupModalEditSaldo();
});

$(function() {
  initCurrencyInputs();
  calculateTotal();

  // Event tombol Draft & Lock
  $('#btnDraft').on('click', () => submitSaldo(false));
  $('#btnLock').on('click', () => submitSaldo(true));

  // Buat periode baru (tetap sama)
  $('#btnCreateNewPeriod').on('click', function() {
    Swal.fire({
      title: 'Buat Periode Baru?',
      text: 'Ini akan membuat periode baru tahun {{ now()->year }}. Pastikan periode sebelumnya sudah di-lock.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonColor: '#10b981',
      confirmButtonText: 'Ya, Buat',
      cancelButtonText: 'Batal'
    }).then((result) => {
      if (!result.isConfirmed) return;
      $.ajax({
        url: '{{ route("admin.keuangan.saldo-awal.create-new") }}',
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
          if (response.success) {
            Swal.fire({
              title: 'Sukses!',
              text: response.message || 'Periode baru berhasil dibuat.',
              icon: 'success',
              timer: 2500,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            Swal.fire('Info', response.message || 'Operasi selesai tapi ada catatan.', 'info');
          }
        },
        error: function(xhr) {
          console.error('AJAX error (create-new):', xhr);
          let errorMessage = 'Terjadi kesalahan tidak diketahui';
          try {
            if (xhr.responseJSON && xhr.responseJSON.message) {
              errorMessage = xhr.responseJSON.message;
            } else if (xhr.responseJSON && xhr.responseJSON.errors) {
              errorMessage = Object.values(xhr.responseJSON.errors)[0][0] || 'Validasi gagal';
            } else if (xhr.responseText) {
              errorMessage = xhr.responseText;
            } else {
              errorMessage = xhr.statusText || 'Gagal terhubung ke server';
            }
          } catch (e) {
            console.error('Error parsing response:', e);
          }
          Swal.fire({
            title: 'Gagal',
            html: errorMessage.replace(/\n/g, '<br>'),
            icon: 'error',
            confirmButtonText: 'OK'
          });
        }
      });
    });
  });

  @if($periodeTerakhir?->status === 'locked')
    document.querySelectorAll('#formSaldo input, #formSaldo button').forEach(el => {
      el.disabled = true;
      el.classList.add('opacity-60', 'cursor-not-allowed');
    });
  @endif
});

</script>
@endpush