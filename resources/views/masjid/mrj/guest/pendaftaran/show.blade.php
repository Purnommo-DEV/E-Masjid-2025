@extends('masjid.master-guest')

@section('title', 'Daftar Anak Yatim & Dhuafa - Santunan Ramadhan 1447 H / 2026')

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-white py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-9xl mx-auto">
            <!-- Filter Section (UI baru sesuai referensi) -->
            <div class="bg-white rounded-2xl shadow-lg border border-emerald-100/60 p-6 lg:p-10 mb-10">
                <div class="mb-6">
                    <p class="text-xs uppercase tracking-widest text-emerald-600 font-medium mb-1">Filter Data</p>
                    <h2 class="text-2xl lg:text-3xl font-bold text-slate-900 mb-2">Cari Data Santunan</h2>
                    <p class="text-base text-slate-600 leading-relaxed">
                        Gunakan filter di bawah untuk mencari data anak yatim & dhuafa. Ketik di pencarian untuk filter cepat.
                    </p>
                </div>

                <!-- Baris Atas: 3 kolom -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end">
                    <!-- Tahun -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Tahun</span>
                        </label>
                        <div class="relative">
                            <select id="filterTahun" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none bg-white appearance-none">
                                <option value="">Semua</option>
                                @for($y = date('Y'); $y >= 2024; $y--)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endfor
                            </select>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📅</span>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                        </div>
                    </div>

                    <!-- Umur (nilai) -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Umur</span>
                        </label>
                        <div class="relative">
                            <input type="number" id="filterUmurValue" min="0" placeholder="Angka umur"
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white" />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🎂</span>
                        </div>
                    </div>

                    <!-- Satuan Umur -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Satuan</span>
                        </label>
                        <div class="relative">
                            <select id="filterUmurSatuan" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none bg-white appearance-none">
                                <option value="">Semua</option>
                                <option value="tahun">Tahun</option>
                                <option value="bulan">Bulan</option>
                                <option value="hari">Hari</option>
                            </select>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📏</span>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                        </div>
                    </div>
                </div>

                <!-- Baris Bawah: 3 kolom + search span lebar -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-end mt-6">
                    <!-- Jenis Kelamin -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Jenis Kelamin</span>
                        </label>
                        <div class="relative">
                            <select id="filterJk" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none bg-white appearance-none">
                                <option value="">Semua</option>
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">⚥</span>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                        </div>
                    </div>

                    <!-- Kategori -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Kategori</span>
                        </label>
                        <div class="relative">
                            <select id="filterKategori" class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none bg-white appearance-none">
                                <option value="">Semua</option>
                                <option value="yatim_dhuafa">Yatim Dhuafa</option>
                                <option value="dhuafa">Dhuafa</option>
                            </select>
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👶</span>
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                        </div>
                    </div>

                    <!-- Search Box (span lebar di baris bawah) -->
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text font-semibold text-slate-800">Cari Nama / Orang Tua / Sumber / Alamat</span>
                        </label>
                        <div class="relative">
                            <input type="text" id="globalSearch" placeholder="Ketik untuk cari..."
                                   class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white" />
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🔍</span>
                        </div>
                    </div>

                </div>
                    <div class="flex justify-center mt-8">
                        <button id="btnResetFilter"
                                class="px-10 py-4 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <span>Reset Filter</span>
                        </button>
                    </div>
            </div>

            <!-- Tabel (padding lebih besar, tidak mepet) -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-emerald-100/60 p-6 lg:p-10 mt-4">
                <table id="tabelYatimDhuafa" class="table table-zebra w-full text-slate-900">
                    <thead class="bg-emerald-600 text-white">
                        <tr>
                            <th></th> <!-- Control Responsive -->
                            <th>No</th>
                            <th>Penanggung Jawab Informasi</th>
                            <th>No WA</th>
                            <th>Kategori</th>
                            <th>Nama Anak</th>
                            <th>Panggilan</th>
                            <th>Jenis Kelamin</th>
                            <th>Tanggal Lahir</th>
                            <th>Umur</th>
                            <th>Nama Orang Tua / Wali</th>
                            <th>Pekerjaan Orang Tua / Wali</th>
                            <th>Alamat Lengkap</th>
                            <th>Keterangan Tambahan</th>
                            <th>Tahun</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <!-- CTA Daftar Baru -->
            <div class="text-center mt-10">
                <a href="{{ route('santunan-ramadhan.form') }}" 
                   class="inline-block px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base">
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
                                <input
                                    type="text"
                                    name="tanggal_lahir_text"
                                    id="editTanggalLahirText"
                                    placeholder="dd/mm/yyyy"
                                    class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white"
                                    inputmode="numeric"
                                />
                                <input type="hidden" name="tanggal_lahir" id="editTanggalLahirHidden" />
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📅</span>
                            </div>
                            <label class="label">
                                <span class="label-text-alt text-sm text-slate-500 italic">
                                    Format: Hari/Bulan/Tahun (contoh: 15/08/2015)
                                </span>
                            </label>
                        </div>

                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Umur Saat Ini <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative flex items-center gap-3">
                                <input type="number" name="umur" id="editUmur" min="0" max="13" required
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Akan otomatis jika tgl lahir diisi" />
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🎂</span>

                                <!-- Dropdown satuan - default hidden, muncul hanya jika tgl lahir kosong -->
                                <select name="umur_satuan" id="editUmurSatuan"
                                        class="w-full p-2 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none bg-white select select-bordered select-md hidden !bg-emerald-600 !text-white !border-emerald-700">
                                    <option value="" disabled selected>Pilih satuan</option>
                                    <option value="tahun">Tahun</option>
                                    <option value="bulan">Bulan</option>
                                    <option value="hari">Hari</option>
                                </select>

                                <!-- Badge - muncul hanya jika tgl lahir terisi valid -->
                                <span id="editUmurDetailBadge"
                                      class="badge bg-emerald-600 text-white badge-lg hidden whitespace-nowrap px-4 py-3 font-medium">
                                </span>
                            </div>
                            <label class="label">
                                <span class="label-text-alt text-sm text-slate-500 italic" id="editUmurHelper">
                                    Akan otomatis ter-update jika tanggal lahir diisi
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
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">


<script>
    let table;
    let lastSelectedSatuan = '';

    $(document).ready(function () {
        table = $('#tabelYatimDhuafa').DataTable({
            processing: true,
            serverSide: true,
            responsive: {
                details: {
                    type: 'column',
                    target: 0
                }
            },
            columnDefs: [
                {
                    className: 'control',
                    orderable: false,
                    targets: 0
                }
            ],
            order: [1, 'asc'],
            paging: true,
            searching: false,          // <-- matikan search bawaan
            dom: 'lrtip',              // <-- hilangkan search box bawaan ('f' dihilangkan), hanya length + table + info + pagination
            ajax: {
                url: '{{ route("santunan-ramadhan.data") }}',
                data: function (d) {
                    d.tahun           = $('#filterTahun').val() || null;
                    d.umur_value      = $('#filterUmurValue').val() || null;
                    d.umur_satuan     = $('#filterUmurSatuan').val() || null;
                    d.jenis_kelamin   = $('#filterJk').val() || null;
                    d.kategori        = $('#filterKategori').val() || null;
                    d.search          = $('#globalSearch').val() || null; // global search
                }
            },
            columns: [
                {
                    data: null,
                    defaultContent: '',
                    className: 'control',
                    orderable: false
                },
                { data: 'DT_RowIndex', orderable: false, searchable: false },
                { data: 'sumber_informasi' },
                { data: 'no_wa' },
                { data: 'kategori_display' }, // custom untuk tampilan UI
                { data: 'nama_lengkap' },
                { data: 'nama_panggilan' ?? '-' },
                { data: 'jenis_kelamin_display' },
                { data: 'tanggal_lahir_formatted' },
                { data: 'umur_display' }, // custom: umur + satuan
                { data: 'nama_orang_tua' },
                { data: 'pekerjaan_orang_tua' ?? '-' },
                { data: 'alamat' },
                { data: 'catatan_tambahan' ?? '-' },
                { data: 'tahun_program' },
                {
                    data: null,
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `
                            <div class="flex justify-center gap-2">
                                <button class="btn btn-sm bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded-lg"
                                        onclick="openEditModal(${row.id})">
                                    Edit
                                </button>
                                <button class="btn btn-sm bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-lg" onclick="hapusData(${row.id})">
                                    Hapus
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            language: {
                processing: `
                    <div class="flex items-center gap-3 text-emerald-600">
                        <span class="loading loading-spinner loading-md"></span>
                        Memuat data...
                    </div>
                `,
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
            }
        });

        // Event filter
        $('#filterTahun, #filterUmurValue, #filterUmurSatuan, #filterJk, #filterKategori').on('change', function () {
            table.ajax.reload();
        });

        // Khusus input number umur: reload saat ketik (dengan debounce agar smooth)
        let debounceTimer;
        $('#filterUmurValue').on('input', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                table.ajax.reload();
            }, 300); // 300ms delay
        });

        // Search custom auto reload saat ketik
        $('#globalSearch').on('keyup', function () {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                table.ajax.reload();
            }, 300);
        });
    });

    // Fungsi hapus data
    function hapusData(id) {
        Swal.fire({
            title: 'Yakin hapus data ini?',
            text: "Data yang dihapus tidak bisa dikembalikan!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("santunan-ramadhan.destroy", ":id") }}'.replace(':id', id),
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function (res) {
                        table.ajax.reload(null, false);
                        Swal.fire('Terhapus!', res.message || 'Data berhasil dihapus', 'success');
                    },
                    error: function (xhr) {
                        Swal.fire('Gagal', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    }


    // Tombol Reset Filter
    $('#btnResetFilter').on('click', function () {
        // Reset semua filter ke default
        $('#filterTahun').val('');
        $('#filterUmurValue').val('');
        $('#filterUmurSatuan').val('');
        $('#filterJk').val('');
        $('#filterKategori').val('');
        $('#globalSearch').val('');

        // Reload tabel dengan filter kosong
        table.ajax.reload();

        // Optional: tampilkan notif sukses
        Swal.fire({
            icon: 'success',
            title: 'Filter Direset',
            text: 'Semua filter telah dikembalikan ke default',
            timer: 1500,
            showConfirmButton: false
        });
    });

    // =============================================
    // Logic Umur + Satuan + Badge (koreksi: badge hidden saat tgl kosong)
    // =============================================
    function setupUmurEdit() {
        const tglText   = $('#editTanggalLahirText');
        const tglHidden = $('#editTanggalLahirHidden');
        const umur      = $('#editUmur');
        const helper    = $('#editUmurHelper');
        const satuan    = $('#editUmurSatuan');
        const badge     = $('#editUmurDetailBadge');

        // Masking tanggal + switch mode
        tglText.off('input').on('input', function(e) {
            let val = e.target.value.replace(/\D/g, '');
            if (val.length > 8) val = val.slice(0,8);

            if (val.length > 4) val = val.slice(0,2) + '/' + val.slice(2,4) + '/' + val.slice(4);
            else if (val.length > 2) val = val.slice(0,2) + '/' + val.slice(2);

            e.target.value = val;

            if (val.length === 10) {
                const [dd, mm, yyyy] = val.split('/');
                tglHidden.val(`${yyyy}-${mm.padStart(2,'0')}-${dd.padStart(2,'0')}`);
                satuan.addClass('hidden');
                hitungDanTampilkanBadge();
            } else {
                tglHidden.val('');
                umur.val('').prop('readonly', false);
                satuan.removeClass('hidden');
                badge.addClass('hidden');  // Pastikan badge hidden saat tgl kosong
                // Restore satuan terakhir
                if (lastSelectedSatuan) {
                    satuan.val(lastSelectedSatuan);
                }
                helper.text('Tanggal lahir kosong → isi umur manual + pilih satuan')
                      .removeClass('text-red-600').addClass('text-amber-700');
            }
        });

        // Simpan pilihan satuan terakhir
        satuan.off('change').on('change', function() {
            lastSelectedSatuan = $(this).val();
        });

        // Input manual umur
        umur.off('input').on('input', function() {
            if (tglHidden.val() !== '') return;

            if ($(this).val().trim() !== '') {
                helper.text('Umur manual → pilih satuan di sebelah')
                      .addClass('text-amber-700').removeClass('text-red-600');
                badge.addClass('hidden');
            } else {
                helper.text('Isi tanggal lahir atau umur manual')
                      .removeClass('text-amber-700 text-red-600');
            }
        });
    }

    function hitungDanTampilkanBadge() {
        const tglHidden = $('#editTanggalLahirHidden');
        const umur      = $('#editUmur');
        const helper    = $('#editUmurHelper');
        const badge     = $('#editUmurDetailBadge');

        const dateStr = tglHidden.val();
        if (!dateStr) {
            badge.addClass('hidden');  // ekstra jaga-jaga
            return;
        }

        const birth = new Date(dateStr);
        const today = new Date();

        if (isNaN(birth.getTime()) || birth > today) {
            helper.addClass('text-red-600').text('Tanggal lahir tidak valid atau di masa depan');
            badge.addClass('hidden');
            umur.prop('readonly', false);
            return;
        }

        let years  = today.getFullYear() - birth.getFullYear();
        let months = today.getMonth() - birth.getMonth();
        let days   = today.getDate() - birth.getDate();

        if (months < 0 || (months === 0 && days < 0)) { years--; months += 12; }
        if (days < 0) { months--; days += new Date(today.getFullYear(), today.getMonth(), 0).getDate(); }

        // Tentukan nilai umur & satuan badge
        let nilaiUtama = years;
        let satuanBadge = 'Tahun';

        if (years < 1) {
            nilaiUtama = months;
            satuanBadge = 'Bulan';
        }
        if (years < 1 && months < 1) {
            nilaiUtama = days;
            satuanBadge = 'Hari';
        }
        if (nilaiUtama === 0) {
            satuanBadge = 'Baru lahir';
        }

        umur.val(nilaiUtama).prop('readonly', true);

        // Badge hanya satuan
        badge.text(satuanBadge)
             .removeClass('hidden badge-error')
             .addClass('badge-success');

        // Validasi
        if (years > 13) {
            helper.addClass('text-red-600').text('Usia melebihi 13 tahun (tidak memenuhi kriteria)');
            badge.removeClass('badge-success').addClass('badge-error');
        } else {
            helper.removeClass('text-red-600 text-amber-700')
                  .text('Umur dihitung otomatis dari tanggal lahir');
        }
    }

    function openEditModal(id) {
        $.ajax({
            url: '{{ route("santunan-ramadhan.edit", ":id") }}'.replace(':id', id),
            method: 'GET',
            success: function(row) {
                $('#editId').val(row.id);
                $('#editNamaLengkap').val(row.nama_lengkap || '');
                $('#editNamaPanggilan').val(row.nama_panggilan || '');
                $('#editKategori').val(row.kategori || '');
                $('#editJenisKelamin').val(row.jenis_kelamin || '');
                $('#editAlamat').val(row.alamat || '');
                $('#editNoWa').val(row.no_wa || '');
                $('#editNamaOrtu').val(row.nama_orang_tua || '');
                $('#editPekerjaanOrtu').val(row.pekerjaan_orang_tua || '');
                $('#editSumber').val(row.sumber_informasi || '');
                $('#editCatatan').val(row.catatan_tambahan || '');

                // Tanggal lahir
                let tglDisplay = '';
                if (row.tanggal_lahir) {
                    const parts = row.tanggal_lahir.split('-');
                    tglDisplay = `${parts[2]}/${parts[1]}/${parts[0]}`;
                }
                $('#editTanggalLahirText').val(tglDisplay);
                $('#editTanggalLahirHidden').val(row.tanggal_lahir || '');

                // Simpan nilai satuan dari DB
                lastSelectedSatuan = row.umur_satuan || '';

                // Aktifkan logic
                setupUmurEdit();

                // Inisialisasi tampilan awal
                if (row.tanggal_lahir) {
                    hitungDanTampilkanBadge();
                    $('#editUmurSatuan').addClass('hidden');
                } else {
                    $('#editUmurSatuan').removeClass('hidden');
                    if (lastSelectedSatuan) {
                        $('#editUmurSatuan').val(lastSelectedSatuan);
                    }
                    $('#editUmur').val(row.umur || '').prop('readonly', false);
                    $('#editUmurHelper').text('Tanggal lahir kosong → isi umur manual + pilih satuan')
                                       .addClass('text-amber-700');
                    $('#editUmurDetailBadge').addClass('hidden');  // <--- Koreksi utama: badge hidden saat load & tgl kosong
                }

                document.getElementById('editModal').showModal();
            },
            error: function(xhr) {
                Swal.fire('Error', xhr.responseJSON?.message || 'Gagal memuat data', 'error');
            }
        });
    }

    // Submit form edit
    $('#editForm').on('submit', function (e) {
        e.preventDefault();
        const id = $('#editId').val();
        const $btn = $('#btn-submit-edit');
        const $spinner = $('#btn-edit-spinner');
        const $text = $('#btn-edit-text');

        $btn.prop('disabled', true).addClass('opacity-75 cursor-not-allowed');
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
                $btn.prop('disabled', false).removeClass('opacity-75 cursor-not-allowed');
                $spinner.addClass('hidden');
                $text.text('Simpan Perubahan');
            }
        });
    });
</script>

<!-- CSS Override (pastikan semua teks gelap & kolom terlihat) -->
<style>
    /* =========================
       GLOBAL TEXT COLOR FIX
       ========================= */
    body, .text-slate-50, .text-gray-50, .text-base-content {
        color: #0f172a !important;
    }

    .select, .input-bordered, .select option, .input input, textarea {
        color: #0f172a !important;
        background-color: white !important;
    }

    /* =========================
       DATATABLE BASE COLOR
       ========================= */
    .dataTables_wrapper,
    .dataTables_info,
    .dataTables_length,
    .dataTables_paginate,
    .dataTables_filter {
        color: #0f172a !important;
    }

    /* Loader overlay DataTables 1.13 */
    .dataTables_wrapper {
        position: relative;
    }

    .dataTables_wrapper .dataTables_processing {
        position: absolute !important;
        top: 50% !important;
        left: 50% !important;
        transform: translate(-50%, -50%);
        background: rgba(255, 255, 255, 0.9) !important;
        padding: 20px 30px !important;
        border-radius: 12px !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 50 !important;
        font-weight: 600;
        color: #059669 !important;
    }

    /* =========================
       PAGINATION STYLE
       ========================= */
    .dataTables_paginate .paginate_button {
        color: #059669 !important;
        background: white !important;
        border: 1px solid #cbd5e1 !important;
        border-radius: 8px !important;
        padding: 6px 12px !important;
        margin-left: 4px !important;
        cursor: pointer !important;
    }

    .dataTables_paginate .paginate_button:hover {
        background: #ecfdf5 !important; /* emerald-50 */
        border-color: #059669 !important;
    }

    .dataTables_paginate .paginate_button.current {
        background: #059669 !important;
        color: white !important;
        border-color: #059669 !important;
    }

    /* Hilangkan search bawaan */
    .dataTables_filter {
        display: none !important;
    }

    /* =========================
       INFO + PAGINATION ALIGN FIX
       ========================= */
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_paginate {
        float: none !important;
        margin: 0;
    }

    /* Container baris bawah (DataTables 2.x) */
    .dataTables_wrapper .dt-layout-row:last-child {
        display: flex !important;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1rem;
    }

    .dataTables_wrapper .dataTables_info {
        text-align: left !important;
    }

    .dataTables_wrapper .dataTables_paginate {
        text-align: right !important;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .dataTables_wrapper .dt-layout-row:last-child {
            flex-direction: column;
            align-items: flex-start;
        }

        .dataTables_wrapper .dataTables_paginate {
            width: 100%;
            text-align: left !important;
        }
    }

    /* =========================
       TABLE STYLING
       ========================= */
    #tabelYatimDhuafa tbody td {
        color: #0f172a !important;
    }

    #tabelYatimDhuafa thead th {
        background-color: #059669 !important;
        color: white !important;
    }

    /* Zebra */
    #tabelYatimDhuafa tbody tr:nth-child(odd) {
        background-color: #f1f5f9;
    }

    #tabelYatimDhuafa tbody tr:nth-child(even) {
        background-color: #ffffff;
    }

    /* Hover */
    #tabelYatimDhuafa tbody tr:hover {
        background-color: #d1fae5 !important;
        transition: background-color 0.15s ease-in-out;
    }


    /* Matikan TOTAL icon default Responsive */
    td.control::before {
        all: unset !important;
        content: none !important;
        display: none !important;
    }

    td.control {
        position: relative;
        width: 40px;
        cursor: pointer;
    }

    td.control::after {
        content: "+";
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        background-color: #059669;
        color: white;
        font-weight: bold;
        border-radius: 6px;
        font-size: 16px;
        margin: auto;
        transition: all 0.2s ease;
    }

    tr.parent td.control::after {
        content: "−";
        background-color: #dc2626;
    }

    /* =========================
       NAVBAR COLOR LOCK
       ========================= */
    nav {
        color: #e5e7eb !important;
    }

    nav a,
    nav span,
    nav div,
    nav li {
        color: #e5e7eb !important;
    }

    nav .group-hover\:text-emerald-200:hover {
        color: #a7f3d0 !important;
    }

    nav .text-emerald-200\/80 {
        color: rgba(167, 243, 208, 0.8) !important;
    }

    nav a:hover {
        color: #6ee7b7 !important;
    }

    nav .btn-outline {
        color: #a7f3d0 !important;
        border-color: rgba(52, 211, 153, 0.6) !important;
    }

    nav .btn-outline:hover {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #ecfdf5 !important;
    }

    nav details .menu a {
        color: #e5e7eb !important;
    }

    nav details .menu a:hover {
        background-color: rgba(16, 185, 129, 0.15) !important;
        color: #ecfdf5 !important;
    }
</style>

@endpush