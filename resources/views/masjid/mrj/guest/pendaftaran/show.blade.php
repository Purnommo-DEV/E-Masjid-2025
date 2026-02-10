@extends('masjid.master-guest')

@section('title', 'Daftar Anak Yatim & Dhuafa - Santunan Ramadhan 1447 H / 2026')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-white py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <!-- Filter -->
<div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 mb-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">

        <!-- Tahun -->
        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-slate-800">Tahun</span>
            </label>
            <div class="relative">
                <select id="filterTahun"
                    class="w-full px-12 py-3 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white">
                    <option value="">Semua</option>
                    @for($y = date('Y'); $y >= 2024; $y--)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endfor
                </select>
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600">📅</span>
            </div>
        </div>

        <!-- Umur Min -->
        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-slate-800">Umur Min</span>
            </label>
            <div class="relative">
                <input type="number" id="umurMin" min="0" max="13" placeholder="Min"
                    class="w-full px-10 py-3 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none">
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600">🎂</span>
            </div>
        </div>

        <!-- Umur Max -->
        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-slate-800">Umur Max</span>
            </label>
            <input type="number" id="umurMax" min="0" max="13" placeholder="Max"
                class="w-full px-4 py-3 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none">
        </div>

        <!-- Jenis Kelamin -->
        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-slate-800">Jenis Kelamin</span>
            </label>
            <div class="relative">
                <select id="filterJk"
                    class="w-full px-12 py-3 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white">
                    <option value="">Semua</option>
                    <option value="L">L</option>
                    <option value="P">P</option>
                </select>
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600">⚥</span>
            </div>
        </div>

        <!-- Kategori -->
        <div class="form-control">
            <label class="label pb-1">
                <span class="label-text font-semibold text-slate-800">Kategori</span>
            </label>
            <div class="relative">
                <select id="filterKategori"
                    class="w-full px-12 py-3 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none bg-white">
                    <option value="">Semua</option>
                    <option value="yatim_dhuafa">Yatim</option>
                    <option value="dhuafa">Dhuafa</option>
                </select>
                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600">👶</span>
            </div>
        </div>

        <!-- Button -->
        <div>
            <button id="btnFilter"
                class="w-full h-[46px] bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                Filter
            </button>
        </div>

    </div>
</div>


        <!-- Tabel -->
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-emerald-100">
            <table id="tabelYatimDhuafa" class="table table-zebra w-full text-slate-900 border-emerald-500">
                <thead class="bg-emerald-600 text-white">
                    <tr>
                        <th>No</th>
                        <th>Nama Lengkap</th>
                        <th>Nama Panggilan</th>
                        <th>Tanggal Lahir</th>
                        <th>Umur</th>
                        <th>Kategori</th>
                        <th>Jenis Kelamin</th>
                        <th>Alamat</th>
                        <th>No WA</th>
                        <th>Nama Orang Tua</th>
                        <th>Pekerjaan Orang Tua</th>
                        <th>Sumber Informasi</th>
                        <th>Catatan Tambahan</th>
                        <th>Tahun</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="text-slate-900"></tbody>
            </table>
        </div>

        <!-- CTA Daftar Baru -->
        <div class="text-center mt-10">
            <a href="{{ route('santunan-ramadhan.form') }}" class="btn btn-lg bg-gradient-to-r from-emerald-600 to-teal-600 text-white px-10 shadow-lg hover:shadow-xl">
                Daftar Anak Baru
            </a>
        </div>

    </div>
</div>

<!-- Modal Detail (Read-Only) -->
<dialog id="detailModal" class="modal">
    <div class="modal-box max-w-4xl">
        <div class="modal-header flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-900">Detail Data Anak</h3>
            <button type="button" class="text-slate-500 hover:text-slate-700 text-2xl" onclick="document.getElementById('detailModal').close()">✕</button>
        </div>
        <div class="modal-body space-y-4 text-slate-800">
            <!-- Isi detail seperti sebelumnya -->
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="font-semibold">Nama Lengkap</label>
                    <p id="detailNama" class="mt-1"></p>
                </div>
                <!-- ... tambahkan field lain sesuai kebutuhan ... -->
            </div>
        </div>
        <div class="modal-footer flex justify-end">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('detailModal').close()">Tutup</button>
        </div>
    </div>
</dialog>

<!-- Modal Edit (Guest bisa edit semua field) -->
<dialog id="editModal" class="modal">
    <div class="modal-box max-w-4xl">
        <form id="editForm">
            @csrf
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" id="editId" name="id">

            <div class="modal-header flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900">Edit Data Pendaftaran</h3>
                <button type="button" class="text-slate-500 hover:text-slate-700 text-2xl" onclick="document.getElementById('editModal').close()">✕</button>
            </div>

            <div class="modal-body space-y-7">
                <!-- Kategori -->
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text font-semibold text-slate-800">Kategori Penerima <span class="text-red-500">*</span></span>
                    </label>
                    <div class="relative">
                        <select name="kategori" required id="editKategori"
                                class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white appearance-none">
                            <option value="" disabled selected>Pilih salah satu</option>
                            <option value="yatim_dhuafa">Anak Yatim yang Dhuafa</option>
                            <option value="dhuafa">Anak Dhuafa</option>
                        </select>
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👶</span>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                    </div>
                </div>

                <!-- Nama Lengkap & Panggilan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Nama Lengkap Anak <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <input type="text" name="nama_lengkap" id="editNamaLengkap" required
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="Nama lengkap anak"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👤</span>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Nama Panggilan</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="nama_panggilan" id="editNamaPanggilan"
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="Nama sehari-hari (misal: Adit)"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">😊</span>
                        </div>
                    </div>
                </div>

                <!-- Tanggal Lahir & Umur -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Tanggal Lahir <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <input type="date" name="tanggal_lahir" id="editTanggalLahir"
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📅</span>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Umur Saat Ini <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <input type="number" name="umur" id="editUmur" min="0" max="13" required
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="Akan otomatis ter-update"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🎂</span>
                        </div>
                        <label class="label">
                            <span class="label-text-alt text-sm text-slate-500 italic" id="editUmurHelper">
                                Akan otomatis ter-update setiap tanggal lahir diubah
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Jenis Kelamin -->
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text font-semibold text-slate-800">Jenis Kelamin <span class="text-red-500">*</span></span>
                    </label>
                    <div class="relative">
                        <select name="jenis_kelamin" required id="editJenisKelamin"
                                class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white appearance-none">
                            <option value="" disabled selected>Pilih jenis kelamin</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">⚥</span>
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                    </div>
                </div>

                <!-- Alamat -->
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text font-semibold text-slate-800">Alamat Lengkap <span class="text-red-500">*</span></span>
                    </label>
                    <div class="relative">
                        <textarea name="alamat" id="editAlamat" rows="3" required
                                  class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                  placeholder="Contoh: Jl. Merdeka No. 45, Kel. Sukamaju, Kec. Cibeureum, Kota Tasikmalaya"></textarea>
                        <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">🏠</span>
                    </div>
                </div>

                <!-- Nomor WA -->
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text font-semibold text-slate-800">Nomor WA (opsional, untuk konfirmasi)</span>
                    </label>
                    <div class="relative">
                        <input type="tel" name="no_wa" id="editNoWa"
                               class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                               placeholder="08xxxxxxxxxx" pattern="^08[0-9]{8,13}$"/>
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📱</span>
                    </div>
                </div>

                <!-- Nama Orang Tua & Pekerjaan -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Nama Orang Tua / Wali <span class="text-red-500">*</span></span>
                        </label>
                        <div class="relative">
                            <input type="text" name="nama_orang_tua" id="editNamaOrtu" required
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="Nama ayah / ibu / wali"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👨‍👩‍👧</span>
                        </div>
                    </div>
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Pekerjaan Orang Tua / Wali</span>
                        </label>
                        <div class="relative">
                            <input type="text" name="pekerjaan_orang_tua" id="editPekerjaanOrtu"
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                   placeholder="Contoh: Buruh, Ibu Rumah Tangga, Wiraswasta"/>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">💼</span>
                        </div>
                    </div>
                </div>

                <!-- Sumber Informasi -->
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text font-semibold text-slate-800">Sumber Informasi <span class="text-red-500">*</span></span>
                    </label>
                    <div class="relative">
                        <input type="text" name="sumber_informasi" id="editSumber" required
                               class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                               placeholder="Dari mana Anda tahu program ini? (misal: grup WA tetangga, pengumuman masjid, IG, dll)"/>
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🔍</span>
                    </div>
                </div>

                <!-- Catatan Tambahan -->
                <div class="form-control">
                    <label class="label pb-1">
                        <span class="label-text font-semibold text-slate-800">Catatan Tambahan (opsional)</span>
                    </label>
                    <div class="relative">
                        <textarea name="catatan_tambahan" id="editCatatan" rows="4"
                                  class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                  placeholder="Misal: Kondisi kesehatan anak, kebutuhan khusus, atau informasi lain yang perlu diketahui panitia..."></textarea>
                        <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">📝</span>
                    </div>
                </div>

                <!-- Submit -->
                <div class="pt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <button type="submit" id="btn-submit-edit"
                        class="px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base w-full sm:w-auto flex items-center justify-center gap-2">

                        <!-- Spinner -->
                        <svg id="btn-edit-spinner"
                             class="hidden w-5 h-5 animate-spin text-white"
                             viewBox="0 0 24 24">
                            <circle class="opacity-25"
                                    cx="12" cy="12" r="10"
                                    stroke="currentColor"
                                    stroke-width="4"
                                    fill="none" />
                            <path class="opacity-75"
                                  fill="currentColor"
                                  d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z" />
                        </svg>

                        <!-- Text -->
                        <span id="btn-edit-text">Simpan Perubahan</span>
                    </button>

                    <button type="button" class="btn btn-outline w-full sm:w-auto" onclick="document.getElementById('editModal').close()">
                        Batal
                    </button>
                </div>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
    let table;

    // Fungsi hitung umur (reusable untuk create & edit modal)
    function hitungUmur(tglSelector, umurSelector, helperSelector) {
        const tglLahirStr = $(tglSelector).val();
        const umurInput = $(umurSelector);
        const helper = $(helperSelector);

        // 🔒 JIKA TANGGAL LAHIR KOSONG → JANGAN HAPUS UMUR
        if (!tglLahirStr) {
            const umurSekarang = umurInput.val();

            if (umurSekarang !== '') {
                helper.text(`Umur manual: ${umurSekarang} tahun`);
            } else {
                helper.text('Isi umur manual jika tanggal lahir tidak diketahui');
            }

            return;
        }

        const tglLahir = new Date(tglLahirStr);
        if (isNaN(tglLahir.getTime())) {
            helper.text('Tanggal lahir tidak valid');
            return;
        }

        const today = new Date();
        let umur = today.getFullYear() - tglLahir.getFullYear();
        const bulan = today.getMonth() - tglLahir.getMonth();

        if (bulan < 0 || (bulan === 0 && today.getDate() < tglLahir.getDate())) {
            umur--;
        }

        if (umur < 0) {
            helper.text('Tanggal lahir di masa depan → umur tidak valid');
            return;
        }

        umurInput.val(umur);
        helper.text(`Umur otomatis: ${umur} tahun (berdasarkan tanggal lahir)`);
    }


    $(document).ready(function () {
        table = $('#tabelYatimDhuafa').DataTable({
            processing: true,
            serverSide: true,
            scrollX: true,           // Aktifkan scroll horizontal (ini yang paling penting!)
            scrollCollapse: true,
            paging: true,
            ajax: {
                url: '{{ route("santunan-ramadhan.data") }}',
                data: function (d) {
                    d.tahun = $('#filterTahun').val() || null;
                    d.umur_min = $('#umurMin').val() || null;
                    d.umur_max = $('#umurMax').val() || null;
                    d.jenis_kelamin = $('#filterJk').val() || null;
                    d.kategori = $('#filterKategori').val() || null;
                }
            },
            columns: [
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'nama_lengkap' },
                { data: 'nama_panggilan' },
                { data: 'tanggal_lahir' },
                { data: 'umur' },
                { data: 'kategori' },
                { data: 'jenis_kelamin' },
                { data: 'alamat' },
                { data: 'no_wa' },
                { data: 'nama_orang_tua' },
                { data: 'pekerjaan_orang_tua' },
                { data: 'sumber_informasi' },
                { data: 'catatan_tambahan' },
                { data: 'tahun_program' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `
                            <button class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-lg"
                                    onclick="openEditModal(${row.id})">
                                Edit
                            </button>
                        `;
                    }
                }
            ],
            language: {
                processing: '<div class="flex justify-center"><span class="loading loading-spinner loading-lg text-emerald-600"></span> Memuat data...</div>',
                emptyTable: 'Belum ada data',
                info: 'Menampilkan _START_ sampai _END_ dari _TOTAL_ entri',
                infoEmpty: 'Tidak ada entri',
                infoFiltered: '(disaring dari total _MAX_ entri)',
                lengthMenu: 'Tampilkan _MENU_ entri',
                zeroRecords: 'Tidak ada data yang cocok',
                paginate: {
                    first: '« Pertama',
                    last: 'Terakhir »',
                    next: 'Selanjutnya ›',
                    previous: '‹ Sebelumnya'
                }
            },
            drawCallback: function () {
                $('#tabelYatimDhuafa tbody td, #tabelYatimDhuafa tbody th').css('color', '#0f172a');
                $('#tabelYatimDhuafa tbody tr.odd').css('background-color', '#f8fafc');
                $('#tabelYatimDhuafa tbody tr.even').css('background-color', '#ffffff');
            }
        });

        $('#filterTahun, #umurMin, #umurMax').on('change', function () {
            table.ajax.reload();
        });

        $('#btnFilter').on('click', function () {
            table.ajax.reload();
        });
    });

    // Fungsi Detail Modal (read-only)
    function showDetailModal(id) {
        $.ajax({
            url: '{{ route("santunan-ramadhan.data") }}',
            data: { id: id },
            success: function(response) {
                const row = response.data && response.data[0] ? response.data[0] : response;
                $('#detailNama').text(row.nama_lengkap + (row.nama_panggilan ? ' (' + row.nama_panggilan + ')' : ''));
                // ... isi field detail lainnya sesuai kebutuhan ...
                document.getElementById('detailModal').showModal();
            },
            error: function() {
                Swal.fire('Error', 'Gagal memuat detail data', 'error');
            }
        });
    }

    // Fungsi Edit Modal
    function openEditModal(id) {
        $.ajax({
            url: '{{ route("santunan-ramadhan.edit", ":id") }}'.replace(':id', id),
            method: 'GET',
            success: function(row) {
                // Isi semua field di modal edit
                $('#editId').val(row.id);
                $('#editNamaLengkap').val(row.nama_lengkap || '');
                $('#editNamaPanggilan').val(row.nama_panggilan || '');
                $('#editTanggalLahir').val(row.tanggal_lahir || '');
                $('#editUmur').val(row.umur || '');
                $('#editKategori').val(row.kategori || '');
                $('#editJenisKelamin').val(row.jenis_kelamin || '');
                $('#editAlamat').val(row.alamat || '');
                $('#editNoWa').val(row.no_wa || '');
                $('#editNamaOrtu').val(row.nama_orang_tua || '');
                $('#editPekerjaanOrtu').val(row.pekerjaan_orang_tua || '');
                $('#editSumber').val(row.sumber_informasi || '');
                $('#editCatatan').val(row.catatan_tambahan || '');

                // Hitung ulang umur saat modal dibuka
                hitungUmur('#editTanggalLahir', '#editUmur', '#editUmurHelper');

                // Pasang event listener untuk hitung umur otomatis di modal edit
                $('#editTanggalLahir').off('change input blur keyup').on('change input blur keyup', function() {
                    hitungUmur('#editTanggalLahir', '#editUmur', '#editUmurHelper');
                });

                document.getElementById('editModal').showModal();
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memuat data. Pastikan data ini milik Anda.', 'error');
            }
        });
    }

    // Submit Edit (guest bisa update semua field)
    $('#editForm').on('submit', function (e) {
        e.preventDefault();

        const id = $('#editId').val();
        const $btn = $('#btn-submit-edit');
        const $spinner = $('#btn-edit-spinner');
        const $text = $('#btn-edit-text');

        // aktifkan loading tombol
        $btn.prop('disabled', true)
            .addClass('opacity-75 cursor-not-allowed');

        $spinner.removeClass('hidden');
        $text.text('Menyimpan...');

        $.ajax({
            url: '{{ route("santunan-ramadhan.update", ":id") }}'.replace(':id', id),
            method: 'PUT',
            data: $(this).serialize(),
            success: function (res) {
                document.getElementById('editModal').close();
                table.ajax.reload(null, false);
                Swal.fire('Sukses', res.message || 'Data berhasil diperbarui', 'success');
            },
            error: function (xhr) {
                Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
            },
            complete: function () {
                // reset tombol
                $btn.prop('disabled', false)
                    .removeClass('opacity-75 cursor-not-allowed');

                $spinner.addClass('hidden');
                $text.text('Simpan Perubahan');
            }
        });
    });

</script>

<!-- CSS Override (pastikan semua teks gelap & kolom terlihat) -->
<style>
    body, .text-slate-50, .text-gray-50, .text-base-content {
        color: #0f172a !important;
    }

    .select, .input-bordered, .select option, .input input, textarea {
        color: #0f172a !important;
        background-color: white !important;
    }
    .dataTables_wrapper, .dataTables_info, .dataTables_length, .dataTables_paginate, .dataTables_filter {
        color: #0f172a !important;
    }
    .dataTables_paginate .paginate_button {
        color: #059669 !important;
        background: white !important;
        border: 1px solid #cbd5e1 !important;
    }
    .dataTables_paginate .paginate_button.current {
        background: #059669 !important;
        color: white !important;
        border-color: #059669 !important;
    }
    #tabelYatimDhuafa tbody td {
        color: #0f172a !important;
    }
    #tabelYatimDhuafa thead th {
        background-color: #059669 !important;
        color: white !important;
    }
    .table-zebra tbody tr.odd {
        background-color: #f8fafc !important;
    }
    .table-zebra tbody tr.even {
        background-color: #ffffff !important;
    }

    /* Zebra stripe body - LEBIH KELIHATAN */
    #tabelYatimDhuafa tbody tr:nth-child(odd) {
        background-color: #f1f5f9; /* slate-100 */
    }

    #tabelYatimDhuafa tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    /* Hover effect - tetep cakep */
    #tabelYatimDhuafa tbody tr:hover {
        background-color: #d1fae5 !important; /* emerald-100 */
        transition: background-color 0.15s ease-in-out;
    }

    /* Teks body */
    #tabelYatimDhuafa tbody td {
        color: #0f172a;
    }

    /* Header */
    #tabelYatimDhuafa thead th {
        background-color: #059669;
        color: #ffffff;
    }

/* =========================
   NAVBAR COLOR LOCK
   ========================= */
nav {
    color: #e5e7eb !important; /* slate-200 */
}

nav a,
nav span,
nav div,
nav li {
    color: #e5e7eb !important;
}

/* Nama masjid */
nav .group-hover\:text-emerald-200:hover {
    color: #a7f3d0 !important;
}

/* Subtext */
nav .text-emerald-200\/80 {
    color: rgba(167, 243, 208, 0.8) !important;
}

/* Menu hover */
nav a:hover {
    color: #6ee7b7 !important; /* emerald-300 */
}

/* Button outline admin */
nav .btn-outline {
    color: #a7f3d0 !important;
    border-color: rgba(52, 211, 153, 0.6) !important;
}

nav .btn-outline:hover {
    background-color: rgba(16, 185, 129, 0.15) !important;
    color: #ecfdf5 !important;
}

/* Mobile dropdown */
nav details .menu a {
    color: #e5e7eb !important;
}

nav details .menu a:hover {
    background-color: rgba(16, 185, 129, 0.15) !important;
    color: #ecfdf5 !important;
}


</style>
@endpush