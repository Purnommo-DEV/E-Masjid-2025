@extends('masjid.master')
@section('title', 'Hitung Kotak Infak')
@section('content')

@push('style')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<style>

/* ---------- Card / layout ---------- */
.card-wrapper { max-width: 1100px; margin: 2rem auto; border-radius: 1.2rem; overflow: hidden; background: white; box-shadow: 0 15px 35px rgba(0,0,0,0.08); border: 1px solid rgba(15,23,42,0.05); }
.card-header { padding: 1.4rem 1.8rem; background: linear-gradient(90deg, #059669 0%, #10b981 100%); color: #ffffff; }
.card-header .title { font-size: 1.25rem; font-weight: 800; letter-spacing: 0.3px; }
.card-header .subtitle { font-size: 0.95rem; opacity: 0.95; margin-top: 4px; }

/* ---------- Form styles ---------- */
.accent-border { border-left: 7px solid #10b981; padding: 1.1rem 1.2rem; border-radius: 1rem; background: #f8fdfb; transition: all 0.25s ease; box-shadow: 0 4px 15px rgba(16,185,129,0.04); }
.accent-border:focus-within { border-left-color: #059669; box-shadow: 0 10px 35px rgba(5,150,105,0.15); background: #f0fdfa; }
.form-control { display:block; width:100%; padding:0.9rem 1rem; font-size:1.05rem; font-weight:700; text-align:center; color:#1e293b; background:#fff; border:3px solid #cbd5e1; border-radius:1rem; transition:all 0.2s ease; }
.form-control:focus { outline:none; border-color:#10b981; box-shadow:0 0 0 6px rgba(16,185,129,0.12); background:#f0fdfa; }

/* ---------- Buttons ---------- */
.btn-emerald { padding:1rem 2.2rem; border-radius:9999px; background:linear-gradient(135deg,#059669,#10b981); color:#fff; font-weight:800; font-size:1rem; box-shadow:0 12px 30px rgba(5,150,105,0.24); transition:all .22s; display:inline-flex; align-items:center; gap:.5rem; }
.btn-emerald:hover { transform:translateY(-4px); box-shadow:0 16px 36px rgba(5,150,105,0.34); }

.btn-detail { padding:.45rem 1rem; border-radius:9999px; background:#ecfdf5; border:2px solid #10b981; color:#059669; font-weight:800; font-size:.85rem; transition:all .18s; }
.btn-detail:hover { background:#10b981; color:#fff; transform:translateY(-2px); }

/* ---------- Table ---------- */
.table-modern thead th { background:#ecfdf5; color:#065f46; font-weight:800; padding:.6rem .9rem; font-size:.92rem; }
#tabel-kotak.table-modern, #tabel-kotak.table-modern thead th, #tabel-kotak.table-modern tbody td { font-size:.92rem; line-height:1.15; }
#tabel-kotak.table-modern tbody td { padding:.5rem .9rem; vertical-align:middle; }
#tabel-kotak tbody tr { transition:all .18s ease; }
#tabel-kotak tbody tr:hover { transform:translateY(-1px); box-shadow:0 6px 14px rgba(16,185,129,0.06); }

/* group header styling */
tr.group-header { background:linear-gradient(135deg,#064e3b 0%,#059669 100%) !important; color:#fff !important; box-shadow:0 10px 25px rgba(5,150,105,0.12); border-radius:.8rem; overflow:hidden; }
tr.group-header td { padding:1rem 1rem !important; border:none !important; background:linear-gradient(135deg,#064e3b 0%,#059669 100%) !important; color:#fff !important; box-shadow:0 6px 18px rgba(5,150,105,0.06); }
.group-total-card { background:rgba(255,255,255,0.12); padding:.4rem .8rem; border-radius:10px; min-width:140px; text-align:center; }
.group-total-card .total { font-size:1.05rem; font-weight:900; }

/* ---------- GAP BETWEEN GROUPS ---------- */
tr.group-header.dtrg-start.dtrg-level-0:not(:first-child) td { padding-top:22px !important; }
tr.dtrg-end + tr.group-header td { padding-top:22px !important; }

/* ---------- small tweaks for numbers ---------- */
#tabel-kotak td .text-emerald-600, #tabel-kotak td .text-emerald-700 { font-size:1rem; font-weight:800; }

/* ---------- Upload file indicator (no preview) ---------- */
.upload-info { display:flex; align-items:center; gap:0.6rem; justify-content:space-between; }
.upload-left { display:flex; align-items:center; gap:0.6rem; }
.upload-filename { font-weight:700; color:#065f46; font-size:0.95rem; }
.upload-hint { font-size:0.86rem; color: #374151; opacity:0.85; }

/* remove preview area (we intentionally keep none) */
#previewBukti { display:none !important; }

/* Align submit button with upload on md+ */
@media(min-width:768px){
    /* grid in form: already md:grid-cols-2 — ensure upload takes left, submit right */
    .upload-cell { display:flex; flex-direction:column; justify-content:center; }
    .submit-cell { display:flex; align-items:center; justify-content:flex-end; }
}
@media(max-width:767px){
    .submit-cell { justify-content:flex-end; }
}
</style>
@endpush

{{-- CARD DAFTAR --}}
<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Daftar Kotak Infak</h3>
            <p class="subtitle">Ringkasan kotak infak per hari — cepat, rapi & dapat diekspor.</p>
        </div>
    </div>

    <div class="p-6">
        <div class="overflow-x-auto">
            <table id="tabel-kotak" class="table-modern w-full text-sm" style="width:100%">
                <thead>
                    <tr>
                        <th>Akun Pendapatan</th>
                        <th style="display:none;">tanggal_raw</th>
                        <th class="text-end">Jumlah</th>
                        <th width="90" class="text-center">Detail</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

{{-- CARD FORM --}}
<div class="card-wrapper mt-10">
    <div class="card-header">
        <div>
            <h3 class="title">Hitung Kotak Infak Baru</h3>
            <p class="subtitle">Isi jumlah lembar per nominal lalu simpan ke keuangan.</p>
        </div>
    </div>

    <div class="p-6">
        {{-- grid: 2 kolom pada md+, 1 kolom mobile --}}
        <form id="form-kotak" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @csrf

            {{-- Jenis Akun --}}
            <div class="accent-border">
                <label class="block text-sm font-bold text-emerald-800 mb-2">Jenis Akun</label>
                <select name="akun_pendapatan_id" required class="form-control">
                    <option value="">Pilih Akun Kotak Infak</option>
                    @foreach(\App\Models\AkunKeuangan::kotakInfak()->get() as $akun)
                        <option value="{{ $akun->id }}">{{ $akun->kode }} - {{ $akun->nama }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Tanggal --}}
            <div class="accent-border">
                <label class="block text-sm font-bold text-emerald-800 mb-2">Tanggal</label>
                <input type="date" name="tanggal" value="{{ date('Y-m-d') }}" required class="form-control">
            </div>

            {{-- Keterangan --}}
            <div class="md:col-span-2 accent-border">
                <label class="block text-sm font-bold text-emerald-800 mb-2">Keterangan (Opsional)</label>
                <input type="text" name="keterangan" placeholder="Misal: Jumat, 14 Nov 2025" class="form-control">
            </div>

            {{-- Tabel nominal / lembar --}}
            <div class="md:col-span-2 accent-border">
                <div class="overflow-x-auto rounded-xl border-2 border-emerald-100">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-emerald-100">
                                <th class="text-left px-5 py-4 font-bold text-emerald-900">Nominal</th>
                                <th class="text-center px-5 py-4 font-bold text-emerald-900">Lembar</th>
                                <th class="text-right px-5 py-4 font-bold text-emerald-900">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach([100000,50000,20000,10000,5000,2000,1000] as $i => $nom)
                            <tr class="hover:bg-emerald-50 transition">
                                <td class="px-5 py-4 font-bold text-gray-800">Rp {{ number_format($nom,0,',','.') }}</td>
                                <td class="px-5 py-4">
                                    <input type="text"
                                           name="lembar[{{ $i }}]"
                                           class="form-control lembar text-center"
                                           data-nom="{{ $nom }}"
                                           value="0"
                                           inputmode="numeric">
                                    <input type="hidden" name="nominal[{{ $i }}]" value="{{ $nom }}">
                                </td>
                                <td class="px-5 py-4 text-right font-bold text-emerald-700 subtotal">Rp 0</td>
                            </tr>
                            @endforeach
                            <tr class="bg-emerald-200 font-black text-emerald-900">
                                <td colspan="2" class="text-right px-5 py-4 text-2xl">TOTAL</td>
                                <td id="total" class="text-right px-5 py-4 text-3xl">Rp 0</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Upload bukti (kiri) --}}
            <div class="accent-border upload-cell">
                <label class="block text-sm font-bold text-emerald-800 mb-3">Bukti Foto (Opsional)</label>

                <label for="bukti_kotak" class="block cursor-pointer border-2 border-dashed border-emerald-300 rounded-xl p-4 hover:border-emerald-500 hover:bg-emerald-50 transition">
                    <div class="upload-info">
                        <div class="upload-left">
                            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            <div>
                                <div class="upload-filename" id="uploadName">Tidak ada file</div>
                                <div class="upload-hint">Klik untuk pilih file bukti (jpg/png). Ukuran maks 5MB.</div>
                            </div>
                        </div>

                        <div>
                            <button type="button" id="removeBukti" class="btn-detail" style="display:none;">Hapus</button>
                        </div>
                    </div>
                </label>

                <input id="bukti_kotak" type="file" name="bukti_kotak" accept="image/*" class="hidden">
                {{-- preview intentionally removed --}}
            </div>

            {{-- Tombol Simpan (kanan sejajar upload pada md+) --}}
            <div class="submit-cell">
                <div class="md:w-9/12 w-full">
                    <button type="submit" id="submitKotak" class="btn-emerald w-full">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Simpan & Masuk Keuangan
                    </button>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- MODAL DETAIL --}}
<dialog id="modal-detail" class="modal">
    <div class="modal-box max-w-4xl rounded-2xl shadow-2xl border-2 border-emerald-100">
        <div class="flex justify-between items-center mb-6 pb-4 border-b-2 border-emerald-100">
            <h3 class="text-3xl font-black text-emerald-800">Detail Kotak Infak</h3>
            <button type="button" id="closeDetail" class="text-3xl text-gray-500 hover:text-gray-800">X</button>
        </div>
        <div id="detail-body"></div>
    </div>
</dialog>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/rowgroup/1.1.4/js/dataTables.rowGroup.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(function(){
    function defaultSubmitHtml() {
        return `
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            Simpan & Masuk Keuangan
        `;
    }

    // DataTable init (tidak diubah — tetap seperti sebelumnya)
    const table = $('#tabel-kotak').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ route("admin.keuangan.kotak-infak.list") }}',
        order: [[1, 'desc']],
        rowGroup: {
            dataSrc: 'tanggal_group',
            startRender: function (rows, group) {
                const arr = rows.data().toArray();
                const totalHari = arr.reduce((acc, item) => acc + (Number(item.jumlah || 0)), 0);
                const colspan = $('#tabel-kotak thead th').length;

                const header = `
                    <tr class="group-header dtrg-group dtrg-start dtrg-level-0">
                        <td colspan="${colspan}">
                            <div style="display:flex;align-items:center;gap:1rem;justify-content:space-between">
                                <div style="display:flex;align-items:center;gap:0.8rem">
                                    <div style="background:rgba(255,255,255,0.12);border-radius:9999px;padding:0.45rem;">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <div style="font-weight:900;font-size:1rem;color:#fff;margin-bottom:2px">${group}</div>
                                        <div style="font-size:0.84rem;color:rgba(255,255,255,0.9)">${rows.count()} kotak infak</div>
                                    </div>
                                </div>

                                <div class="group-total-card">
                                    <div class="total">Rp ${totalHari.toLocaleString('id-ID')}</div>
                                    <div style="font-size:0.75rem;color:rgba(255,255,255,0.9)">Total Hari</div>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                return $(header);
            }
        },
        columns: [
            { data: 'jenis', render: d => `<strong class="text-emerald-800 text-sm">${d}</strong>` },
            { data: 'tanggal_raw', visible: false },
            {
                data: 'jumlah',
                className: 'text-end',
                render: d => {
                    const num = Number(d || 0);
                    return `<span class="text-sm font-black text-emerald-600">Rp ${num.toLocaleString('id-ID')}</span>`;
                }
            },
            {
                data: 'detail_btn',
                orderable: false,
                searchable: false,
                className: 'text-center',
                render: function(data, type, row) {
                    if (data && String(data).trim().startsWith('<')) return data;
                    try {
                        const payload = encodeURIComponent(JSON.stringify(row || {}));
                        return `<button class="btn-detail detail-kotak-btn" data-kotak='${payload}'>Detail</button>`;
                    } catch (e) {
                        return `<button class="btn-detail detail-kotak-btn" data-kotak='{}'>Detail</button>`;
                    }
                }
            },
        ],
        language: { processing: 'Memuat...' }
    });

    // format lembar -> subtotal -> total
    $(document).on('input', '.lembar', function(){
        let clean = this.value.replace(/\D/g, '');
        if (!clean) clean = '0';
        this.value = Number(clean).toLocaleString('id-ID');

        const nom = parseInt($(this).data('nom')) || 0;
        const lem = parseInt(clean) || 0;
        $(this).closest('tr').find('.subtotal').text('Rp ' + (nom * lem).toLocaleString('id-ID'));

        let total = 0;
        $('.lembar').each(function(){
            const val = parseInt(this.value.replace(/\D/g,'')||0);
            total += val * (parseInt($(this).data('nom'))||0);
        });
        $('#total').text('Rp ' + total.toLocaleString('id-ID'));
    });

    // FILE UPLOAD: tampilkan nama file & tombol hapus; PREVIEW DINONAKTIFKAN
    $('#bukti_kotak').on('change', function(){
        const file = this.files[0];
        if (!file) {
            $('#uploadName').text('Tidak ada file');
            $('#removeBukti').hide();
            return;
        }
        // cek tipe
        if (!file.type.startsWith('image/')) {
            Swal.fire('Gagal', 'Hanya file gambar yang diperbolehkan', 'error');
            this.value = '';
            $('#uploadName').text('Tidak ada file');
            $('#removeBukti').hide();
            return;
        }
        // tampilkan nama file (singkatkan jika terlalu panjang)
        let name = file.name;
        if (name.length > 40) name = name.slice(0, 20) + '…' + name.slice(-15);
        $('#uploadName').text('Bukti terpilih: ' + name);
        $('#removeBukti').show();
    });

    // tombol hapus file
    $('#removeBukti').on('click', function(e){
        e.preventDefault();
        $('#bukti_kotak').val('');
        $('#uploadName').text('Tidak ada file');
        $(this).hide();
    });

    // SUBMIT FORM
    $('#form-kotak').on('submit', function(e){
        e.preventDefault();
        const $btn = $('#submitKotak');
        $btn.prop('disabled', true).text('Menyimpan...');

        const formData = new FormData(this);
        $('.lembar').each(function(i){
            const num = parseInt(this.value.replace(/\D/g,'') || 0);
            formData.set(`lembar[${i}]`, num);
        });

        $.ajax({
            url: '{{ route("admin.keuangan.kotak-infak.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: res => {
                Swal.fire({ icon:'success', title:'Tersimpan!', text: res.message || 'Kotak infak berhasil masuk ke keuangan', timer:1600, showConfirmButton:false });
                // reset form + UI indikator upload
                this.reset();
                $('.lembar').each(function(){ $(this).val('0'); });
                $('.subtotal, #total').text('Rp 0');
                $('#uploadName').text('Tidak ada file');
                $('#removeBukti').hide();
                table.ajax.reload(null, false);
            },
            error: xhr => {
                let msg = 'Terjadi kesalahan saat menyimpan';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) msg = xhr.responseJSON.message;
                Swal.fire('Gagal', msg, 'error');
            },
            complete: () => {
                $btn.prop('disabled', false).html(defaultSubmitHtml());
            }
        });
    });

    // DETAIL modal handler (tetap tolerant)
    $(document).on('click', '.detail-kotak-btn', function(){
        let raw = $(this).attr('data-kotak') || '{}';
        let data = {};
        try {
            const decoded = decodeURIComponent(raw);
            data = JSON.parse(decoded);
        } catch (e1) {
            try { data = JSON.parse(raw); } catch (e2) {
                try { const unescaped = $('<div>').html(raw).text(); data = JSON.parse(unescaped); } catch (e3) { data = {}; }
            }
        }

        const rows = (data.details || []).filter(d => (Number(d.lembar || 0) > 0)).map(d => `
            <tr>
                <td class="px-6 py-4 text-left font-bold text-gray-800 text-lg">Rp ${Number(d.nominal).toLocaleString('id-ID')}</td>
                <td class="px-6 py-4 text-center font-black text-2xl">${d.lembar}</td>
                <td class="px-6 py-4 text-right font-bold text-emerald-700 text-lg">Rp ${Number(d.subtotal).toLocaleString('id-ID')}</td>
            </tr>
        `).join('');

        const html = `
            <div class="text-center mb-8">
                <h3 class="text-4xl font-black text-emerald-800 mb-2">${escapeHtml(data.jenis || 'Kotak Infak')}</h3>
                <p class="text-lg text-gray-600">${data.tanggal || ''}${data.keterangan ? ' — '+escapeHtml(data.keterangan) : ''}</p>
                <div class="text-5xl font-black text-emerald-600 mt-6">Rp ${Number(data.total||0).toLocaleString('id-ID')}</div>
            </div>

            <div class="overflow-x-auto rounded-2xl border-2 border-emerald-200">
                <table class="w-full text-lg">
                    <thead class="bg-emerald-100">
                        <tr>
                            <th class="px-6 py-4 text-left font-black text-emerald-900">Nominal</th>
                            <th class="px-6 py-4 text-center font-black text-emerald-900">Lembar</th>
                            <th class="px-6 py-4 text-right font-black text-emerald-900">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${rows || '<tr><td colspan="3" class="text-center py-10 text-gray-500 text-lg">Tidak ada rincian lembar</td></tr>'}
                        <tr class="bg-emerald-300 font-black">
                            <td colspan="2" class="px-6 py-6 text-right text-2xl">TOTAL KOTAK</td>
                            <td class="px-6 py-6 text-right text-3xl text-emerald-900">Rp ${Number(data.total||0).toLocaleString('id-ID')}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `;

        $('#detail-body').html(html);
        document.getElementById('modal-detail').showModal();
    });

    $('#closeDetail').on('click', () => document.getElementById('modal-detail').close());

    function escapeHtml(t) {
        return String(t||'').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[s]));
    }
});
</script>
@endpush
