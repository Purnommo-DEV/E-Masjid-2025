@extends('masjid.master')
@section('title', 'Manajemen Layanan')
@section('content')

@push('style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<style>
    /* --- Reuse reference card styles --- */
    .card-wrapper { max-width: 1200px; margin: 1.25rem auto; border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 30px rgba(2,6,23,0.06); border: 1px solid rgba(15,23,42,0.04); background: white; }
    .card-header { padding: 1.25rem 1.5rem; color: #fff; background: linear-gradient(90deg, #059669 0%, #10b981 100%); display:flex; align-items:center; justify-content:space-between; }
    .card-header .title { margin:0; font-size:1.125rem; font-weight:700; }
    .card-header .subtitle { margin:0; opacity:.95; font-size:.95rem; }
    .card-body { padding: 1.25rem 1.5rem; background: white; }
    .header-action { background: rgba(255,255,255,0.12); color: #fff; padding: 0.5rem 0.9rem; border-radius: 999px; display: inline-flex; gap: .5rem; align-items: center; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 6px 14px rgba(4,120,87,0.06); transition: transform .12s ease, background .12s; }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }
    table.dataTable td { white-space: normal !important; }
    dialog.modal::backdrop { background: rgba(15,23,42,0.55); backdrop-filter: blur(4px) saturate(1.02); }
    dialog.modal { border: none; padding: 0; }
    .modal-panel { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); overflow:hidden; background:white; }
    .is-invalid { border-color: #dc3545 !important; }
    .invalid-feedback { display:block; color:#dc3545; font-size:.875rem; }
    .btn-circle-ico { display:inline-flex; align-items:center; justify-content:center; width:36px; height:36px; border-radius:8px; transition: transform .12s ease; }
    .btn-circle-ico:hover { transform: translateY(-2px); }
    .input-plain { width: 100%; padding: 8px 12px; border-radius: 8px; border: 1px solid #d1d5db; background: #fff; }
    .input-plain:focus { border-color: #059669; outline: none; box-shadow: 0 0 0 2px rgba(5,150,105,0.15); }

    #iconPickerOverlay.open { display: block; }

    #iconList .grid { grid-template-columns: repeat(6, minmax(0,1fr)); }
    #iconList button { cursor: pointer; }
    #iconList button.ring { box-shadow: 0 0 0 4px rgba(5,150,105,0.12); }

    @media (max-width: 640px) { .card-header { padding: .9rem 1rem; } .card-body { padding: 1rem; } }

    /* HIDE dialog backdrop when attribute [hide-backdrop] present */
    dialog.modal[hide-backdrop]::backdrop {
        display: none !important;
        background: transparent !important;
        pointer-events: none !important;
    }

    /* Safety: jika browser menempatkan dialog above everything,
       kita tetap pastikan panel berada fixed + super-high z-index */
    #iconPickerPanel {
        position: fixed;
        left: 50%;
        top: 30%;
        transform: translateX(-50%);
        width: 540px;
        max-width: calc(100% - 32px);
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 24px 60px rgba(2,6,23,0.25);
        padding: 12px;
        /* gunakan nilai maksimal aman (32-bit signed int) */
        z-index: 2147483647;
        display: none;
    }
    #iconPickerPanel.open { display: block; }
    #iconPickerOverlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.35);
        z-index: 2147483646;
        display: none;
    }
    #iconPickerOverlay.open { display: block; }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Manajemen Layanan</h3>
            <p class="subtitle">Tambah, edit, dan atur layanan (tanpa gambar)</p>
        </div>

        <button type="button" class="header-action" onclick="addLayanan()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Layanan</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="layananTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3 text-center">Icon</th>
                        <th class="px-4 py-3">Judul</th>
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

<!-- Modal Layanan -->
<dialog id="layananModal" class="modal" aria-labelledby="layananModalTitle">
    <div class="modal-box max-w-lg p-0 overflow-hidden rounded-2xl">
        <form id="layananForm" class="p-5" method="POST">
            @csrf
            <input type="hidden" id="method" value="POST">

            <!-- HEADER -->
            <div class="flex items-center justify-between mb-4">
                <h3 id="layananModalTitle" class="text-lg font-semibold text-emerald-800">Form Layanan</h3>
                <button type="button" id="closeLayananModalBtn" class="text-gray-600 hover:text-gray-800">✕</button>
            </div>

            <!-- INPUTS -->
            <div class="grid gap-3">
                <!-- Judul -->
                <div>
                    <label class="block text-sm font-medium mb-1" for="judul">Judul Layanan</label>
                    <input id="judul" type="text" name="judul" class="input-plain" required>
                    <div id="err_judul" class="invalid-feedback" style="display:none"></div>
                </div>

                <!-- Icon (picker) -->
                <div>
                    <label class="block text-sm font-medium mb-1">Icon</label>
                    <div class="flex items-center gap-3">
                        <div id="iconPreview" class="w-10 h-10 border border-slate-300 bg-white rounded-md flex items-center justify-center text-xl">
                            <span id="iconPreviewInner">📖</span>
                        </div>

                        <input type="hidden" name="icon" id="iconInput" value="📖">

                        <button type="button" id="openIconPickerBtn" class="px-3 py-2 rounded-md border bg-white text-sm">Pilih Icon</button>
                    </div>

                    <div id="err_icon" class="invalid-feedback" style="display:none"></div>
                </div>

                <!-- Urutan -->
                <div>
                    <label class="block text-sm font-medium mb-1" for="urutan">Urutan Tampil</label>
                    <input id="urutan" type="number" name="urutan" class="input-plain" min="0">
                    <div id="err_urutan" class="invalid-feedback" style="display:none"></div>
                </div>

                <!-- Aktif -->
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" class="rounded border-gray-300">
                    <label for="is_active" class="text-sm text-gray-700">Tampilkan</label>
                    <div id="err_is_active" class="invalid-feedback" style="display:none;margin-left:8px"></div>
                </div>

                <!-- Deskripsi -->
                <div>
                    <label class="block text-sm font-medium mb-1" for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="4" class="input-plain" placeholder="Tuliskan deskripsi layanan..."></textarea>
                    <div id="err_deskripsi" class="invalid-feedback" style="display:none"></div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="flex justify-end gap-3 mt-5 pt-4 border-t">
                <button type="button" id="cancelLayananBtn" class="px-4 py-2 rounded-md border bg-white">Batal</button>
                <button type="submit" id="btnSave" class="px-5 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold">Simpan</button>
            </div>
        </form>
    </div>

    <form method="dialog" class="modal-backdrop">
        <button>Tutup</button>
    </form>
</dialog>

<!-- Icon Picker Panel (fixed, moved to body by JS) -->
<div id="iconPickerPanel" role="dialog" aria-modal="true" aria-labelledby="iconPickerTitle">
    <div class="flex items-center justify-between mb-3">
        <h4 id="iconPickerTitle" class="text-sm font-semibold">Pilih Icon</h4>
        <button type="button" id="closeIconPickerBtn" class="text-slate-600">✕</button>
    </div>

    <input id="iconSearch" type="text" class="input-plain mb-2" placeholder="Cari emoji (contoh: pendidikan, sosial, masjid)...">

    <div id="iconList" style="max-height:300px; overflow-y:auto;"></div>

    <div class="mt-3 text-right">
        <button type="button" id="pickIconCancel" class="px-3 py-2 rounded-md border mr-2">Batal</button>
        <button type="button" id="pickIconSave" class="px-3 py-2 rounded-md bg-emerald-600 text-white">Pilih</button>
    </div>
</div>

<!-- overlay for picker -->
<div id="iconPickerOverlay" aria-hidden="true"></div>

@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // DATATABLE
    let table = $('#layananTable').DataTable({
        processing: true,
        serverSide: false,
        ajax: '{{ route("admin.layanan.data") }}',
        columns: [
            { data: 'icon', orderable: false, className: 'text-center' },
            { data: 'judul' },
            { data: 'urutan', className: 'text-center' },
            { data: 'status', orderable:false, searchable:false, className: 'text-center' },
            {
                data: null, orderable: false, searchable:false, className: 'text-center',
                render: d => `
                    <div class="inline-flex gap-2">
                        <button class="btn btn-sm" onclick="editLayanan(${d.id})">Edit</button>
                        <button class="btn btn-sm btn-ghost" onclick="deleteLayanan(${d.id})">Hapus</button>
                    </div>`
            }
        ],
        responsive: true,
        language: {
            search: "Cari:",
            lengthMenu: "Tampilkan _MENU_",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_",
            processing: "Memuat..."
        }
    });

    // ELEMENTS
    const modal = document.getElementById('layananModal');
    const form = $('#layananForm');

    // Icon picker elements
    const panel = document.getElementById("iconPickerPanel");
    const overlay = document.getElementById("iconPickerOverlay");
    const openBtn = document.getElementById("openIconPickerBtn");
    const closePanelBtn = document.getElementById("closeIconPickerBtn");
    const pickCancel = document.getElementById("pickIconCancel");
    const pickSave = document.getElementById("pickIconSave");
    const iconList = document.getElementById("iconList");
    const iconSearch = document.getElementById("iconSearch");
    const iconInput = document.getElementById("iconInput");
    const iconPreviewInner = document.getElementById("iconPreviewInner");

    // small helper show/hide for dialogs (dialog fallback)
    function showDialog(d) {
        try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
        catch(e){ d.classList.add('modal-open'); }
    }
    function closeDialog(d) {
        try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
        catch(e){ d.classList.remove('modal-open'); }
    }

    // bindings for modal buttons
    $('#closeLayananModalBtn').on('click', () => closeDialog(modal));
    $('#cancelLayananBtn').on('click', () => closeDialog(modal));
    if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

    // FORM submit (create/update)
    form.on('submit', function (e) {
        e.preventDefault();
        clearErrors();

        const action = form.attr('action') || '{{ route("admin.layanan.store") }}';
        const method = $('#method').val();
        const fd = new FormData(this);
        if (method === 'PUT') fd.append('_method', 'PUT');

        fd.set('is_active', document.getElementById('is_active').checked ? '1' : '0');
        
        const btn = document.getElementById('btnSave');
        const oldText = btn.innerHTML;
        btn.disabled = true; btn.innerHTML = 'Menyimpan...';

        $.ajax({
            url: action,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: res => {
                closeDialog(modal);
                table.ajax.reload();
                Swal.fire('Berhasil', res.message || 'Tersimpan', 'success');
            },
            error: xhr => {
                const errors = xhr.responseJSON?.errors;
                if (errors) {
                    Object.keys(errors).forEach(k => {
                        const el = document.getElementById('err_' + k);
                        if (el) {
                            el.textContent = errors[k][0];
                            el.style.display = 'block';
                            const input = document.querySelector('[name="'+k+'"]');
                            if (input) input.classList.add('is-invalid');
                        }
                    });
                } else {
                    const msg = xhr.responseJSON?.message || 'Terjadi kesalahan';
                    Swal.fire('Error', msg, 'error');
                }
            },
            complete: () => {
                btn.disabled = false; btn.innerHTML = oldText;
            }
        });
    });

    // open add
    window.addLayanan = function () {
        resetForm();
        $('#method').val('POST');
        form.attr('action', '{{ route("admin.layanan.store") }}');
        document.getElementById('layananModalTitle').textContent = 'Tambah Layanan';
        showDialog(modal);
        setTimeout(()=> document.getElementById('judul').focus(), 120);
    };

    // open edit
    window.editLayanan = function (id) {
        resetForm();
        $.get(`{{ url('admin/layanan') }}/${id}/edit`)
            .done(function (data) {
                $('#method').val('PUT');
                form.attr('action', `{{ url('admin/layanan') }}/${id}`);
                document.getElementById('layananModalTitle').textContent = data.judul ? `Edit: ${data.judul}` : 'Edit Layanan';

                // populate fields (pastikan controller kembalikan keys ini)
                document.getElementById('judul').value = data.judul || '';
                document.getElementById('urutan').value = data.urutan ?? '';
                document.getElementById('deskripsi').value = data.deskripsi || '';
                document.getElementById('is_active').checked = !!data.is_active;

                // icon handling: preview & input
                // icon handling: preview & input
                if (data.icon) {
                    const iconVal = data.icon;
                    iconInput.value = iconVal;

                    // jika backend menyimpan class (contoh 'fa-solid fa-mosque'), tampilkan tag <i>, 
                    // otherwise tampilkan emoji/tulisan langsung
                    if (typeof iconVal === 'string' && (iconVal.includes('fa-') || iconVal.includes('fab') || iconVal.includes('fa '))) {
                        iconPreviewInner.innerHTML = `<i class="${iconVal}"></i>`;
                    } else {
                        iconPreviewInner.textContent = iconVal;
                    }
                } else {
                    iconInput.value = '📖';
                    iconPreviewInner.textContent = '📖';
                }

                showDialog(modal);
                setTimeout(()=> document.getElementById('judul').focus(), 120);
            })
            .fail(function () {
                Swal.fire('Error', 'Gagal memuat data layanan.', 'error');
            });
    };

    // delete
    window.deleteLayanan = function (id) {
        Swal.fire({
            title: 'Yakin?', text: 'Layanan akan dihapus!', icon: 'warning',
            showCancelButton: true, confirmButtonText: 'Ya, hapus'
        }).then(result => {
            if (!result.isConfirmed) return;
            $.ajax({
                url: `{{ url('admin/layanan') }}/${id}`,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: res => {
                    table.ajax.reload();
                    Swal.fire('Berhasil', res.message || 'Dihapus', 'success');
                },
                error: () => Swal.fire('Error', 'Gagal menghapus layanan.', 'error')
            });
        });
    };

    function resetForm() {
        form[0].reset();
        clearErrors();
        $('#method').val('POST');
        form.attr('action', '{{ route("admin.layanan.store") }}');

        // icon reset
        iconInput.value = '📖';
        iconPreviewInner.textContent = '📖';
    }

    function clearErrors() {
        ['judul','icon','urutan','deskripsi','is_active'].forEach(k => {
            const el = document.getElementById('err_' + k);
            if (el) { el.textContent = ''; el.style.display = 'none'; }
            const input = document.querySelector('[name="'+k+'"]');
            if (input) input.classList.remove('is-invalid');
        });
    }

    /* ==== ICON PICKER IMPLEMENTATION ==== */
    const ICONS = [
        { key:'📖', label:'pendidikan buku' },
        { key:'🤝', label:'sosial bantuan' },
        { key:'🩸', label:'kesehatan donor' },
        { key:'💧', label:'wakaf air' },
        { key:'🕌', label:'masjid ibadah' },
        { key:'🧕', label:'muslimah wanita' },
        { key:'🌙', label:'ramadhan bulan' },
        { key:'🏛', label:'fasilitas gedung' }
    ];

    let selectedIcon = null;

    function renderIconGrid(term = "") {
        iconList.innerHTML = "";
        term = term.toLowerCase();

        const filtered = ICONS.filter(i =>
            i.label.includes(term) || (i.key && i.key.toLowerCase().includes(term))
        );

        const grid = document.createElement("div");
        grid.className = "grid gap-2";
        grid.style.gridTemplateColumns = "repeat(6, minmax(0,1fr))";

        filtered.forEach(item => {
            const btn = document.createElement("button");
            btn.type = "button";
            btn.className = "p-2 border rounded-md bg-white hover:bg-emerald-50 flex items-center justify-center text-xl";

            if (item.isClass) btn.innerHTML = `<i class="${item.key}"></i>`;
            else btn.textContent = item.key;

            btn.onclick = () => {
                // clear previous visual selection
                grid.querySelectorAll("button").forEach(el => el.classList.remove("ring"));
                btn.classList.add("ring");
                selectedIcon = item;
            };

            grid.appendChild(btn);
        });

        iconList.appendChild(grid);
    }

    // open/close panel helpers (with overlay & focus handling)
    if (panel && panel.parentNode !== document.body) document.body.appendChild(panel);
    if (overlay && overlay.parentNode !== document.body) document.body.appendChild(overlay);

    // --- GANTI BAGIAN saveAndLowerDialogs / restoreDialogs / openIconPicker / closeIconPicker
    // dengan potongan ini -- masukkan di dalam DOMContentLoaded Anda.

    const _previouslyOpenDialogs = []; // simpan node yang sebelumnya terbuka

    function saveAndCloseOpenDialogs() {
        _previouslyOpenDialogs.length = 0;
        // HTMLDialogElement punya property .open, tapi beberapa fallback mungkin memakai kelas/modal custom.
        document.querySelectorAll('dialog').forEach(d => {
            try {
                // HTMLDialogElement: jika sedang terbuka (d.open === true) kita close() dan simpan
                if (typeof d.open !== 'undefined' && d.open) {
                    _previouslyOpenDialogs.push({ node: d, useShowModal: true });
                    try { d.close(); } catch(e){ d.style.display = 'none'; }
                } else {
                    // jika bukan native open, coba deteksi kelas modal-open (fallback Anda)
                    if (d.classList.contains('modal-open')) {
                        _previouslyOpenDialogs.push({ node: d, useShowModal: false });
                        d.classList.remove('modal-open');
                    }
                }
            } catch (e) {
                // defensif: jika close() gagal, fallback hide
                try { d.style.display = 'none'; _previouslyOpenDialogs.push({ node: d, useShowModal: false }); } catch(e){}
            }
        });

        // juga tutup any element yang menggunakan attribute aria-hidden managed by libs (opsional)
        document.querySelectorAll('[data-modal-open="true"]').forEach(el => {
            _previouslyOpenDialogs.push({ node: el, attr: true });
            el.setAttribute('data-modal-open', 'false');
            // sembunyikan visual
            el.style.display = 'none';
        });
    }

    function restorePreviouslyOpenDialogs() {
        // reopen in same order (or reverse if you prefer)
        _previouslyOpenDialogs.forEach(entry => {
            const d = entry.node;
            try {
                if (entry.useShowModal && typeof d.showModal === 'function') {
                    d.showModal();
                } else if (entry.useShowModal && d.tagName && d.tagName.toLowerCase() === 'dialog' && typeof d.showModal === 'undefined') {
                    // fallback: restore display
                    d.style.display = '';
                    d.classList.add('modal-open');
                } else if (entry.attr) {
                    // restore attribute-managed modals
                    d.setAttribute('data-modal-open', 'true');
                    d.style.display = '';
                } else {
                    // fallback for class-based modals
                    d.classList.add('modal-open');
                    d.style.display = '';
                }
            } catch (e) {
                // jika showModal gagal, coba restore kelas
                try { d.classList.add('modal-open'); d.style.display = ''; } catch(e) {}
            }
        });

        // bersihkan array
        _previouslyOpenDialogs.length = 0;
    }

    // trap escape
    function iconPickerKeydown(e) { if (e.key === 'Escape') closeIconPicker(); }

    function openIconPicker() {
        // tutup sementara dialog/modal lain yang terbuka supaya picker tampil di atas tanpa gangguan
        saveAndCloseOpenDialogs();

        // append ke body memastikan stacking root
        if (panel && panel.parentNode !== document.body) document.body.appendChild(panel);
        if (overlay && overlay.parentNode !== document.body) document.body.appendChild(overlay);

        panel.classList.add('open');
        overlay.classList.add('open');

        document.addEventListener('keydown', iconPickerKeydown);
        setTimeout(()=> { try { iconSearch.focus(); } catch(e){} }, 60);
    }

    function closeIconPicker() {
        panel.classList.remove('open');
        overlay.classList.remove('open');

        document.removeEventListener('keydown', iconPickerKeydown);

        // buka kembali dialog/modal yang sempat kita close
        setTimeout(() => {
            try { restorePreviouslyOpenDialogs(); } catch(e){}
        }, 40); // beri sedikit jeda agar repaint selesai
    }


    // Bind UI actions (sisa binding Anda tetap sama)
    openBtn.onclick = () => { renderIconGrid(); selectedIcon = null; iconSearch.value = ''; openIconPicker(); };
    closePanelBtn.onclick = pickCancel.onclick = () => closeIconPicker();
    overlay.addEventListener('click', () => closeIconPicker());
    iconSearch.oninput = (e) => renderIconGrid(e.target.value);

    // --- tambahkan ini tepat setelah: iconSearch.oninput = (e) => renderIconGrid(e.target.value);
    // ganti pickSave.onclick Anda dengan ini
    pickSave.onclick = () => {
        try {
            // ambil elemen DOM terkini (hindari referensi stale)
            const iconInputEl = document.getElementById('iconInput');
            const iconPreviewInnerEl = document.getElementById('iconPreviewInner');

            // fallback: jika user belum memilih apa-apa, gunakan yang pertama
            if (!selectedIcon) selectedIcon = ICONS[0];

            const value = selectedIcon.key || '📖';
            const looksLikeClass = typeof value === 'string' && value.includes('fa-');

            // set sekarang (sementara modal sedang tertutup)
            if (iconInputEl) iconInputEl.value = value;
            if (iconPreviewInnerEl) {
                if (looksLikeClass) iconPreviewInnerEl.innerHTML = `<i class="${value}"></i>`;
                else iconPreviewInnerEl.textContent = value;
            }

            // tutup picker
            closeIconPicker();

            // setelah modal direstore, pastikan nilai di-DOM benar (hindari race)
            setTimeout(() => {
                const ii = document.getElementById('iconInput');
                const ip = document.getElementById('iconPreviewInner');
                if (ii) ii.value = value;
                if (ip) {
                    if (looksLikeClass) ip.innerHTML = `<i class="${value}"></i>`;
                    else ip.textContent = value;
                }
            }, 120);

        } catch (err) {
            console.error('pickSave error:', err);
            try { closeIconPicker(); } catch(e){}
        }
    };

});
</script>
@endpush
