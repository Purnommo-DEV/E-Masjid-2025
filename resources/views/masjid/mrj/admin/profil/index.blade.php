@extends('masjid.master')
@section('title', 'Profil Masjid')
@section('content')

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Libraries (pastikan urutan: jQuery -> SweetAlert2 -> Alpine jika butuh) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.min.css" />
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.12/dist/sweetalert2.all.min.js"></script>
<!-- NOTE: kita tidak butuh Alpine untuk modal pengurus sekarang -->

<!-- ===== Card wrapper + colored header (DESIGN ONLY) ===== -->
<div class="max-w-7xl mx-auto card-wrapper">

    <div class="card-top">
        <div class="card-top-inner flex items-center justify-between">
            <div>
                <h1 class="card-title">Profil Masjid</h1>
                <p class="card-sub">Kelola data profil, lokasi, dan struktur kepengurusan</p>
            </div>

            <div class="card-actions flex items-center gap-3">
                <button onclick="openPengurus()" class="btn-top">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Tambah Pengurus</span>
                </button>
            </div>
        </div>
    </div>

    <div class="card-body">
        <h2 class="sr-only">Konten Profil Masjid</h2>

        <div class="inner max-w-7xl mx-auto px-6 py-6">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- FORM PROFIL --}}
                <div class="lg:col-span-2 space-y-8">

                    <form id="profilForm" enctype="multipart/form-data" class="space-y-6" novalidate>
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Nama + Singkatan + Telepon --}}
                        <div class="md:col-span-2">
                            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">

                                {{-- Nama Masjid --}}
                                <div class="md:col-span-6">
                                    <label class="label">
                                        <span class="label-text font-semibold text-emerald-1000">
                                            Nama Masjid <span class="text-red-500">*</span>
                                        </span>
                                    </label>
                                <input
                                    id="nama"
                                    type="text"
                                    name="nama"
                                    required
                                    value="{{ $profil->nama }}"
                                    class="input input-bordered border-base-800 w-full
                                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-9000"
                                />

                                    <p class="text-xs text-red-500 hidden field-error" data-for="nama"></p>
                                </div>

                                {{-- Singkatan --}}
                                <div class="md:col-span-3">
                                    <label class="label">
                                        <span class="label-text font-semibold text-emerald-1000">
                                            Singkatan
                                        </span>
                                    </label>
                                    <input
                                        id="singkatan"
                                        type="text"
                                        name="singkatan"
                                        maxlength="4"
                                        placeholder="MRJ"
                                        value="{{ $profil->singkatan ?? '' }}"
                                        class="input input-bordered border-base-900 w-full uppercase tracking-widest
                                               focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                                    />

                                    <p class="text-xs text-gray-500">
                                        Maks. 4 huruf
                                    </p>
                                    <p class="text-xs text-red-500 hidden field-error" data-for="singkatan"></p>
                                </div>

                                {{-- Telepon --}}
                                <div class="md:col-span-3">
                                    <label class="label">
                                        <span class="label-text font-semibold text-emerald-1000">
                                            Telepon
                                        </span>
                                    </label>
                                    <input
                                        id="telepon"
                                        type="text"
                                        name="telepon"
                                        value="{{ $profil->telepon }}"
                                        class="input input-bordered border-base-900 w-full
                                               focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                                    />
                                    <p class="text-xs text-red-500 hidden field-error" data-for="telepon"></p>
                                </div>

                            </div>
                        </div>


                            {{-- Alamat --}}
                            <div class="md:col-span-2">
                                <label for="alamat" class="block text-sm font-semibold text-emerald-1000 mb-2">Alamat</label>
                                <textarea
                                    id="alamat"
                                    name="alamat"
                                    rows="3"
                                    class="textarea textarea-bordered border-base-900 w-full
                                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                                >{{ $profil->alamat }}</textarea>
                                <p class="mt-1 text-xs text-red-500 hidden field-error" data-for="alamat"></p>
                            </div>

                            {{-- Latitude --}}
                            <div>
                                <label for="lat" class="block text-sm font-semibold text-emerald-1000 mb-2">Latitude <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    id="lat"
                                    name="latitude"
                                    required
                                    value="{{ $profil->latitude }}"
                                    class="input input-bordered border-base-900 w-full
                                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                                />
                                <p class="mt-1 text-xs text-red-500 hidden field-error" data-for="latitude"></p>
                            </div>

                            {{-- Longitude --}}
                            <div>
                                <label for="lng" class="block text-sm font-semibold text-emerald-1000 mb-2">Longitude <span class="text-red-500">*</span></label>
                                <input
                                    type="text"
                                    id="lng"
                                    name="longitude"
                                    required
                                    value="{{ $profil->longitude }}"
                                    class="input input-bordered border-base-900 w-full
                                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                                />
                                <p class="mt-1 text-xs text-red-500 hidden field-error" data-for="longitude"></p>
                            </div>

                            {{-- Logo --}}
                            <div>
                                <label class="block text-sm font-semibold text-emerald-1000 mb-2">Logo Masjid</label>
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="file" name="logo" id="logoInput" class="hidden" accept="image/*">
                                        <div class="px-3 py-2 border border-dashed rounded-lg bg-emerald-50 text-emerald-700 text-sm">Pilih logo</div>
                                    </label>
                                    <div id="logoPreview" class="w-20 h-20 rounded-xl overflow-hidden border bg-white shadow-sm">
                                        @if($profil->getFirstMedia('logo'))
                                            <img src="{{ $profil->getFirstMediaUrl('logo') }}" alt="logo" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-xs text-gray-400">Belum ada</div>
                                        @endif
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Format: JPG/PNG - Max 2MB</p>
                            </div>

                            {{-- Struktur Organisasi --}}
                            <div>
                                <label class="block text-sm font-semibold text-emerald-1000 mb-2">Struktur Organisasi</label>
                                <div class="flex items-center gap-3">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input type="file" name="struktur" id="strukturInput" class="hidden" accept="image/*,application/pdf">
                                        <div class="px-3 py-2 border border-dashed rounded-lg bg-emerald-50 text-emerald-700 text-sm">Pilih file</div>
                                    </label>
                                    <div id="strukturPreview" class="w-32 rounded-xl overflow-hidden border bg-white shadow-sm">
                                        @if($profil->getFirstMedia('struktur'))
                                            <img src="{{ $profil->getFirstMediaUrl('struktur') }}" alt="struktur" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-xs text-gray-400 px-2">Belum ada</div>
                                        @endif
                                    </div>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">PNG/JPG atau PDF</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-2">
                            <div class="text-sm text-gray-500">Terakhir diperbarui: <span class="font-medium text-emerald-700">{{ optional($profil->updated_at)->diffForHumans() ?? '-' }}</span></div>
                            <div class="flex gap-2">
                                <button type="button" id="resetBtn" class="px-4 py-2 rounded-md border border-gray-200 text-sm">Reset</button>
                                <button type="submit" id="saveBtn" class="px-6 py-2 rounded-md bg-emerald-700 hover:bg-emerald-1000 text-white font-semibold shadow">
                                    <span id="saveBtnText">Simpan Profil</span>
                                </button>
                            </div>
                        </div>
                    </form>

                    {{-- ===================== LIST PENGURUS ===================== --}}
                    <div class="mt-10">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-emerald-1000">Struktur Kepengurusan</h3>

                            <!-- Panggil helper openPengurus() -->
                            <button onclick="openPengurus()" class="inline-flex items-center gap-2 px-4 py-2 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-sm shadow">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                                Tambah
                            </button>
                        </div>

                        <div id="pengurusList" class="mt-4 grid grid-cols-1 gap-4">
                            @forelse($pengurus as $p)
                                <div class="flex items-center justify-between p-4 rounded-xl bg-emerald-50 border border-emerald-100 shadow-sm pengurus-card" data-id="{{ $p->id }}">
                                    <div class="flex items-center gap-4">
                                        <div class="avatar">
                                            @if($p->getFirstMedia('foto'))
                                                <img src="{{ $p->getFirstMediaUrl('foto') }}" alt="{{ $p->nama }}" class="w-14 h-14 rounded-full object-cover border">
                                            @else
                                                <div class="w-14 h-14 rounded-full bg-emerald-700 text-white flex items-center justify-center text-lg font-bold">
                                                    {{ Str::upper(substr($p->nama, 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>

                                        <div class="nama-jabatan">
                                            <div class="text-lg font-semibold text-emerald-900">{{ $p->nama }}</div>
                                            <div class="text-sm text-emerald-700">{{ $p->jabatan }}</div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <button onclick="editPengurus({{ $p->id }})" class="px-3 py-1 rounded-md bg-yellow-400 hover:bg-yellow-500 text-sm font-medium">Edit</button>
                                        <button onclick="hapusPengurus({{ $p->id }})" class="px-3 py-1 rounded-md bg-red-600 hover:bg-red-700 text-white text-sm font-medium">Hapus</button>
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500">Belum ada pengurus.</p>
                            @endforelse
                        </div>
                    </div>

                </div>

                {{-- MAP --}}
                <div>
                    <h3 class="text-xl font-bold text-emerald-1000 mb-2">Lokasi di Peta</h3>

                    <div id="map" class="rounded-2xl h-80 shadow border border-emerald-100 overflow-hidden"></div>

                    <p class="text-gray-600 text-sm mt-3">Klik peta untuk mengubah lokasi atau geser marker.</p>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- MODAL PENGURUS (dialog plain JS, DaisyUI style) --}}
<dialog id="pengurusModal" class="modal">
    <div class="modal-box w-11/12 max-w-lg p-6">
        <div class="flex items-start justify-between">
            <h3 class="text-xl font-semibold text-emerald-1000" id="modalPengurusTitle">Pengurus</h3>
            <button id="closePengurusBtn" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>

        <form id="pengurusForm" class="mt-4 space-y-4" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="pengurusId" name="id" value="">
            <input type="hidden" id="pengurus_method" value="POST">

            <div class="flex flex-col items-center gap-3 mb-2">
                <div id="fotoPreview" class="w-24 h-24 rounded-full bg-gray-100 shadow flex items-center justify-center overflow-hidden">
                    <div class="text-sm text-gray-400">Preview</div>
                </div>

                <label class="w-full">
                    <input type="file" id="fotoInputModal" name="foto" accept="image/*" class="hidden">
                    <div id="pickFotoBtn" class="px-3 py-2 border border-dashed rounded-lg text-sm text-emerald-700 bg-emerald-50 text-center cursor-pointer">Pilih foto</div>
                </label>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Nama <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="namaModal"
                    name="nama"
                    class="input input-bordered border-base-900 w-full
                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                />
                <p class="mt-1 text-xs text-red-500 hidden field-error-modal" data-for="nama"></p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Jabatan <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    id="jabatanModal"
                    name="jabatan"
                    class="input input-bordered border-base-900 w-full
                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                />
                <p class="mt-1 text-xs text-red-500 hidden field-error-modal" data-for="jabatan"></p>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Keterangan</label>
                <textarea
                    id="keteranganModal"
                    name="keterangan"
                    rows="3"
                    class="textarea textarea-bordered border-base-900 w-full
                           focus:border-emerald-600 focus:ring-2 focus:ring-emerald-800"
                ></textarea>
            </div>

            <div class="flex justify-end gap-2 mt-3">
                <button type="button" id="cancelPengurusBtn" class="px-4 py-2 rounded-md border">Batal</button>
                <button type="submit" id="savePengurusBtn" class="px-4 py-2 rounded-md bg-emerald-700 hover:bg-emerald-1000 text-white">Simpan</button>
            </div>
        </form>
    </div>
</dialog>

@endsection


@push('style')
<style>

@layer components {
  .input,
  .textarea {
    @apply border-emerald-400;          /* border normal emerald soft */
  }

  .input:focus,
  .textarea:focus {
    @apply border-emerald-600 ring-2 ring-emerald-500/20;  /* lebih bold + ring */
  }
}
    /* Card wrapper */
    .card-wrapper {
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 12px 30px rgba(2,6,23,0.06);
        border: 1px solid rgba(15,23,42,0.04);
        margin: 1.5rem auto;
    }

    /* Top header */
    .card-top {
        background: linear-gradient(90deg, #065f46 0%, #059669 50%, #10b981 100%);
        padding: 18px 0;
        color: white;
    }
    .card-top-inner {
        max-width: 1100px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    .card-title {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0;
        color: #ffffff;
        letter-spacing: -0.2px;
    }
    .card-sub {
        margin: 4px 0 0;
        color: rgba(255,255,255,0.92);
        font-size: 0.95rem;
    }

    /* top button */
    .btn-top {
        display: inline-flex;
        gap: .6rem;
        align-items: center;
        background: rgba(255,255,255,0.12);
        color: white;
        padding: .5rem .9rem;
        border-radius: 999px;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 6px 18px rgba(4,120,87,0.06);
        transition: transform .12s ease, background .12s ease;
    }
    .btn-top svg { stroke: currentColor; }
    .btn-top:hover { transform: translateY(-3px); background: rgba(255,255,255,0.18); }

    /* body */
    .card-body { background: #fff; }

    .inner { padding: 1.2rem 0 1.8rem; }

    /* Pengurus card */
    .pengurus-card {
        transition: transform .12s ease, box-shadow .12s ease;
    }
    .pengurus-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 30px rgba(2,6,23,0.06);
    }

    /* Modal look: slightly larger and rounded */
    dialog.modal .modal-box {
        border-radius: 0.9rem;
        box-shadow: 0 18px 40px rgba(2,6,23,0.12);
    }

    /* Buttons small tweaks */
    .btn, .btn-top, button {
        font-weight: 600;
        letter-spacing: -0.2px;
    }

    /* helper kecil untuk preview agar rapi */
    #logoPreview img, #strukturPreview img, #fotoPreview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }

    /* responsive compact tweaks */
    @media (max-width: 1024px) {
        .w-14 { width: 48px; height: 48px; }
        #logoPreview, #strukturPreview { width: 56px; height: 56px; }
        .text-lg { font-size: 1rem; }
        .card-top-inner { padding-left: 1rem; padding-right: 1rem; }
        .inner { padding-left: 1rem; padding-right: 1rem; }
    }

    [x-cloak] { display: none !important; }
    .field-error { min-height: 1rem; }
    .field-error-modal { min-height: 1rem; }

    /* COMPACT SIDEBAR STYLES */
    body.sidebar-collapsed .pengurus-card .nama-jabatan { display: none; }
    body.sidebar-collapsed .pengurus-card .avatar img,
    body.sidebar-collapsed .pengurus-card .avatar { width: 36px; height: 36px; }
    body.sidebar-collapsed #logoPreview, body.sidebar-collapsed #strukturPreview { width: 48px; height: 48px; }
    body.sidebar-collapsed .text-lg { font-size: 0.95rem; }
    body.sidebar-collapsed .pengurus-card { padding: 0.5rem; }
</style>
@endpush


<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

@push('scripts')
<script>
    // (Semua script kamu tidak diubah — salin persis script yang sebelumnya ada)
    // ========== HELPERS ==========
    function showFieldError(name, message) {
        const el = document.querySelector(`.field-error[data-for="${name}"]`);
        if (el) { el.textContent = message; el.classList.remove('hidden'); }
    }
    function clearFieldError(name) {
        const el = document.querySelector(`.field-error[data-for="${name}"]`);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
    }
    function clearAllFieldErrors() {
        document.querySelectorAll('.field-error').forEach(e => { e.textContent = ''; e.classList.add('hidden'); });
    }

    function showModalFieldError(name, message) {
        const el = document.querySelector(`.field-error-modal[data-for="${name}"]`);
        if (el) { el.textContent = message; el.classList.remove('hidden'); }
    }
    function clearModalFieldError(name) {
        const el = document.querySelector(`.field-error-modal[data-for="${name}"]`);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
    }

    // ========== IMAGE PREVIEW FORM ==========
    document.addEventListener('DOMContentLoaded', () => {
        const logoInput = document.getElementById('logoInput');
        const strukturInput = document.getElementById('strukturInput');

        if (logoInput) {
            logoInput.addEventListener('change', function() {
                const preview = document.getElementById('logoPreview');
                preview.innerHTML = '';
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    if (file.size > 2 * 1024 * 1024) {
                        showFieldError('logo','Ukuran file terlalu besar (max 2MB)');
                        this.value = '';
                        preview.innerHTML = `<div class="w-full h-full flex items-center justify-center text-xs text-gray-400">Belum ada</div>`;
                        return;
                    } else clearFieldError('logo');
                    const reader = new FileReader();
                    reader.onload = e => preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                    reader.readAsDataURL(file);
                }
            });
        }

        if (strukturInput) {
            strukturInput.addEventListener('change', function() {
                const preview = document.getElementById('strukturPreview');
                preview.innerHTML = '';
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const reader = new FileReader();
                    if (file.type.startsWith('image/')) {
                        reader.onload = e => preview.innerHTML = `<img src="${e.target.result}" class="w-full h-full object-cover">`;
                        reader.readAsDataURL(file);
                    } else {
                        preview.innerHTML = `<div class="p-3 text-sm text-gray-600">File: ${file.name}</div>`;
                    }
                }
            });
        }
    });

    // ========== MAP ==========
    let map, marker;
    const latInit = {{ $profil->latitude ?? -6.2 }};
    const lngInit = {{ $profil->longitude ?? 106.8 }};

    function initMap() {
        map = L.map('map').setView([latInit, lngInit], 16);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        marker = L.marker([latInit, lngInit], { draggable: true }).addTo(map);

        marker.on('dragend', e => {
            const pos = e.target.getLatLng();
            document.getElementById('lat').value = pos.lat.toFixed(6);
            document.getElementById('lng').value = pos.lng.toFixed(6);
        });

        map.on('click', e => {
            marker.setLatLng(e.latlng);
            document.getElementById('lat').value = e.latlng.lat.toFixed(6);
            document.getElementById('lng').value = e.latlng.lng.toFixed(6);
        });
    }

    // ========== PROFILE FORM SUBMIT ==========
    $(function() {
        initMap();

        $('#singkatan').on('input', function () {
            this.value = this.value
                .toUpperCase()
                .replace(/[^A-Z]/g, '');
        });

        // Realtime simple validation on input
        $('#profilForm').on('input change', '.form-input', function() {
            const name = $(this).attr('name');
            if ($(this).prop('required') && !$(this).val().trim()) {
                showFieldError(name, 'Kolom ini wajib diisi');
            } else {
                clearFieldError(name);
            }
        });

        $('#profilForm').on('submit', function(e) {
            e.preventDefault();
            clearAllFieldErrors();

            // front-end checks
            const requiredFields = ['nama','latitude','longitude'];
            let valid = true;
            requiredFields.forEach(f => {
                const v = $(`[name="${f}"]`).val();
                if (!v || !String(v).trim()) {
                    showFieldError(f, 'Kolom ini wajib diisi');
                    valid = false;
                }
            });

            if (!valid) {
                Swal.fire({ icon: 'error', title: 'Periksa kembali', text: 'Ada kolom wajib yang kosong.' });
                return;
            }

            // disable submit
            $('#saveBtn').prop('disabled', true);
            $('#saveBtnText').text('Menyimpan...');

            let formData = new FormData(this);

            $.ajax({
                url: '{{ route("admin.profil.update") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: res => {
                    Swal.fire('Berhasil!', res.message, 'success').then(() => location.reload());
                },
                error: xhr => {
                    const msg = xhr.responseJSON?.message || 'Gagal menyimpan';
                    Swal.fire('Error', msg, 'error');
                    $('#saveBtn').prop('disabled', false);
                    $('#saveBtnText').text('Simpan Profil');
                }
            });
        });

        // reset
        $('#resetBtn').on('click', function() { location.reload(); });

        // Sortable pengurus
        new Sortable(document.getElementById('pengurusList'), {
            animation: 150,
            onEnd: evt => {
                const order = [...evt.to.children].map(el => el.dataset.id);
                $.post('{{ route("admin.profil.pengurus.reorder") }}', {
                    _token: '{{ csrf_token() }}',
                    order
                });
            }
        });
    });

    /********************************************************
     *  Pengurus modal (plain JS dialog) + helpers
     ********************************************************/
    (function(){
        const modal = document.getElementById('pengurusModal');
        const form = document.getElementById('pengurusForm');
        const fotoInput = document.getElementById('fotoInputModal');
        const fotoPreview = document.getElementById('fotoPreview');
        const pickFotoBtn = document.getElementById('pickFotoBtn');
        const closeBtn = document.getElementById('closePengurusBtn');
        const cancelBtn = document.getElementById('cancelPengurusBtn');
        const modalTitle = document.getElementById('modalPengurusTitle');
        const idInput = document.getElementById('pengurusId');
        const methodInput = document.getElementById('pengurus_method');

        function showDialog(d) {
            try {
                if (typeof d.showModal === 'function') d.showModal();
                else d.classList.add('modal-open');
            } catch (err) {
                d.classList.add('modal-open');
            }
        }
        function closeDialog(d) {
            try {
                if (typeof d.close === 'function') d.close();
                else d.classList.remove('modal-open');
            } catch (err) {
                d.classList.remove('modal-open');
            }
        }

        window.openPengurus = function(detail = null) {
            document.querySelectorAll('.field-error-modal').forEach(e => e.classList.add('hidden'));
            if (!detail) {
                modalTitle.textContent = 'Tambah Pengurus';
                idInput.value = '';
                methodInput.value = 'POST';
                form.reset();
                fotoPreview.innerHTML = `<div class="text-sm text-gray-400">Preview</div>`;
            } else {
                modalTitle.textContent = detail.nama ? `Edit: ${detail.nama}` : 'Edit Pengurus';
                idInput.value = detail.id ?? '';
                methodInput.value = 'PUT';
                document.getElementById('namaModal').value = detail.nama ?? '';
                document.getElementById('jabatanModal').value = detail.jabatan ?? '';
                document.getElementById('keteranganModal').value = detail.keterangan ?? '';
                if (detail.foto_url || detail.fotoUrl || detail.foto) {
                    fotoPreview.innerHTML = `<img src="${detail.foto_url ?? detail.fotoUrl ?? detail.foto}" class="w-full h-full object-cover">`;
                } else {
                    fotoPreview.innerHTML = `<div class="text-sm text-gray-400">Preview</div>`;
                }
            }

            showDialog(modal);
            setTimeout(()=>{ const el = document.getElementById('namaModal'); if (el) el.focus(); }, 100);
        };

        window.closePengurus = function() { closeDialog(modal); };

        pickFotoBtn?.addEventListener('click', () => fotoInput.click());
        closeBtn?.addEventListener('click', () => closePengurus());
        cancelBtn?.addEventListener('click', () => closePengurus());
        if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closePengurus(); });

        fotoInput?.addEventListener('change', function(e) {
            const file = this.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = ev => fotoPreview.innerHTML = `<img src="${ev.target.result}" class="w-full h-full object-cover">`;
            reader.readAsDataURL(file);
        });

        window.editPengurus = function(id) {
            $.get(`/admin/pengurus/${id}`, data => {
                openPengurus(data);
            }).fail(() => {
                Swal.fire('Error', 'Gagal memuat data pengurus', 'error');
            });
        };

        window.hapusPengurus = function(id) {
            Swal.fire({
                title: 'Yakin ingin menghapus?',
                icon: 'warning',
                showCancelButton: true
            }).then(res => {
                if (res.isConfirmed) {
                    $.ajax({
                        url: `/admin/pengurus/${id}`,
                        method: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: () => location.reload()
                    });
                }
            });
        };

        form?.addEventListener('submit', function(e) {
            e.preventDefault();
            document.querySelectorAll('.field-error-modal').forEach(el => { el.textContent = ''; el.classList.add('hidden'); });

            const nama = document.getElementById('namaModal').value?.trim();
            const jabatan = document.getElementById('jabatanModal').value?.trim();
            let hasErr = false;
            if (!nama) { showModalFieldError('nama','Nama wajib diisi'); hasErr = true; }
            if (!jabatan) { showModalFieldError('jabatan','Jabatan wajib diisi'); hasErr = true; }
            if (hasErr) return;

            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            const id = idInput.value;
            if (methodInput.value === 'PUT' && id) fd.append('_method','PUT');

            fd.append('nama', nama);
            fd.append('jabatan', jabatan);
            fd.append('keterangan', document.getElementById('keteranganModal').value || '');
            const fotoFile = fotoInput.files?.[0];
            if (fotoFile) fd.append('foto', fotoFile);

            const url = (methodInput.value === 'PUT' && id) ? `/admin/pengurus/${id}` : `/admin/pengurus`;

            const saveBtn = document.getElementById('savePengurusBtn');
            saveBtn.disabled = true;
            saveBtn.textContent = 'Menyimpan...';

            $.ajax({
                url,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: () => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Simpan';
                    closePengurus();
                    location.reload();
                },
                error: (xhr) => {
                    saveBtn.disabled = false;
                    saveBtn.textContent = 'Simpan';
                    Swal.fire('Error', xhr.responseJSON?.message || 'Gagal', 'error');
                }
            });
        });
    })();

    /********************************************************
     *  Additional features: file validators + sidebar detection
     ********************************************************/
    (function(){
        const validators = {
            logoInput: {
                maxSize: 2 * 1024 * 1024, // 2MB
                exts: ['png','jpg','jpeg','webp']
            },
            strukturInput: {
                maxSize: 5 * 1024 * 1024, // 5MB
                exts: ['png','jpg','jpeg','webp','pdf']
            },
            fotoInputModal: {
                maxSize: 3 * 1024 * 1024, // 3MB
                exts: ['png','jpg','jpeg','webp']
            }
        };

        function extFromName(name) {
            return (name || '').split('.').pop().toLowerCase();
        }

        function validateFile(file, rules) {
            if (!file) return { ok: true };
            const ext = extFromName(file.name);
            if (rules.exts && rules.exts.indexOf(ext) === -1) {
                return { ok: false, reason: `Format tidak diizinkan (${rules.exts.join(', ')})` };
            }
            if (rules.maxSize && file.size > rules.maxSize) {
                return { ok: false, reason: `Ukuran file terlalu besar (max ${Math.round(rules.maxSize/1024/1024)}MB)` };
            }
            return { ok: true };
        }

        document.addEventListener('DOMContentLoaded', function(){
            const logoEl = document.getElementById('logoInput');
            const strukturEl = document.getElementById('strukturInput');
            const fotoModalEl = document.getElementById('fotoInputModal');

            if (logoEl) {
                logoEl.addEventListener('change', function(){
                    const file = this.files[0];
                    const res = validateFile(file, validators.logoInput);
                    if (!res.ok) {
                        this.value = '';
                        if (typeof Swal !== 'undefined') Swal.fire('Gagal', res.reason, 'error'); else alert(res.reason);
                        document.getElementById('logoPreview').innerHTML = `<div class="w-full h-full flex items-center justify-center text-xs text-gray-400">Belum ada</div>`;
                    }
                });
            }

            if (strukturEl) {
                strukturEl.addEventListener('change', function(){
                    const file = this.files[0];
                    const res = validateFile(file, validators.strukturInput);
                    if (!res.ok) {
                        this.value = '';
                        if (typeof Swal !== 'undefined') Swal.fire('Gagal', res.reason, 'error'); else alert(res.reason);
                        document.getElementById('strukturPreview').innerHTML = `<div class="w-full h-full flex items-center justify-center text-xs text-gray-400 px-2">Belum ada</div>`;
                    }
                });
            }

            if (fotoModalEl) {
                fotoModalEl.addEventListener('change', function(e){
                    const file = this.files[0];
                    const res = validateFile(file, validators.fotoInputModal);
                    if (!res.ok) {
                        this.value = '';
                        if (typeof Swal !== 'undefined') Swal.fire('Gagal', res.reason, 'error'); else alert(res.reason);
                        try {
                            const rootImg = document.getElementById('fotoPreview');
                            if (rootImg) rootImg.innerHTML = `<div class="text-sm text-gray-400">Preview</div>`;
                        } catch(e){}
                        return;
                    }
                });
            }

            // SIDEBAR COLLAPSE DETECTION
            function updateSidebarState() {
                const aside = document.querySelector('aside[aria-label="Sidebar"]');
                if (!aside) return;
                const rect = aside.getBoundingClientRect();
                const collapsed = rect.width <= 64 || aside.classList.contains('-translate-x-full') || window.getComputedStyle(aside).transform === 'none' && rect.width <= 64;
                document.body.classList.toggle('sidebar-collapsed', collapsed);
            }

            setTimeout(updateSidebarState, 300);
            window.addEventListener('resize', () => setTimeout(updateSidebarState, 150));

            const asideNode = document.querySelector('aside[aria-label="Sidebar"]');
            if (asideNode) {
                const mo = new MutationObserver(muts => {
                    for (const m of muts) {
                        if (m.attributeName === 'class' || m.attributeName === 'style') {
                            updateSidebarState();
                        }
                    }
                });
                mo.observe(asideNode, { attributes: true, attributeFilter: ['class','style'] });
            }

            window.addEventListener('sidebar:changed', updateSidebarState);
        });
    })();
</script>
@endpush