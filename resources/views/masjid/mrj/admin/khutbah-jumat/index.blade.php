@extends('masjid.master')
@section('title', 'Kelola Khutbah Jumat')

@push('style')
<style>
    /* Sama seperti slider & quote */
    .card-wrapper { max-width: 1100px; margin: 1.25rem auto; border-radius: 1rem; overflow: hidden; box-shadow: 0 10px 30px rgba(2,6,23,0.06); border: 1px solid rgba(15,23,42,0.04); background: white; }
    .card-header { padding: 1.25rem 1.5rem; color: #fff; background: linear-gradient(90deg, #059669 0%, #10b981 100%); display: flex; align-items: center; justify-content: space-between; }
    .card-header .title { margin: 0; font-size: 1.125rem; font-weight: 700; }
    .card-header .subtitle { margin: 0; opacity: .95; font-size: .95rem; }
    .card-body { padding: 1.25rem 1.5rem; background: white; }
    .header-action { background: rgba(255,255,255,0.12); color: #fff; padding: 0.5rem 0.9rem; border-radius: 999px; display: inline-flex; gap: .5rem; align-items: center; border: 1px solid rgba(255,255,255,0.08); box-shadow: 0 6px 14px rgba(4,120,87,0.06); transition: transform .12s ease, background .12s; }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }
    dialog.modal::backdrop { background: rgba(15,23,42,0.55); backdrop-filter: blur(4px) saturate(1.02); }
    dialog.modal { border: none; padding: 0; }
    .modal-box { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); }
    .field-error { display: none; }
    .input-plain { border: 1px solid #e6e6e6; border-radius: 8px; padding: .5rem .75rem; width: 100%; }
    textarea.input-plain { min-height: 100px; }
    @media (max-width:640px) { .card-header, .card-body { padding: 1rem; } }
</style>
@endpush

@section('content')
<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Kelola Khutbah Jumat</h3>
            <p class="subtitle">Atur jadwal khatib, tema, tanggal & jam tiap pekan</p>
        </div>
        <button type="button" class="header-action" onclick="addKhutbah()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="text-sm font-semibold">Tambah Jadwal</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="khutbahTable" class="table table-zebra w-full text-sm">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">Khatib</th>
                        <th class="px-4 py-3">Tema</th>
                        <th class="px-4 py-3">Jam</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-center">Aktif</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form -->
<dialog id="khutbahModal" class="modal">
    <div class="modal-box w-11/12 max-w-3xl p-0 overflow-hidden">
        <form id="khutbahForm" class="p-5 space-y-4">
            @csrf
            <input type="hidden" id="method" value="POST">
            <input type="hidden" name="id" id="id">

            <div class="flex items-center justify-between mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold text-emerald-800">Form Jadwal Khutbah Jumat</h3>
                <button type="button" id="closeModal" class="text-gray-600 hover:text-gray-800 text-xl">✕</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 overflow-y-auto max-h-[60vh]">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Nama Khatib <span class="text-red-500">*</span></label>
                    <input type="text" name="khatib" class="input-plain" required placeholder="Ust. Dr. Muhammad">
                    <p class="text-xs text-red-500 hidden field-error mt-1" data-for="khatib"></p>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Tema Khutbah <span class="text-red-500">*</span></label>
                    <input type="text" name="tema" class="input-plain" required placeholder="Jaga Hati di Era Digital">
                    <p class="text-xs text-red-500 hidden field-error mt-1" data-for="tema"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal (tampilan) <span class="text-red-500">*</span></label>
                    <input type="text" name="tanggal" class="input-plain" required placeholder="Jum’at, 12 Jan 2026">
                    <p class="text-xs text-gray-500 mt-1">Format bebas untuk tampilan</p>
                    <p class="text-xs text-red-500 hidden field-error mt-1" data-for="tanggal"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Jam <span class="text-red-500">*</span></label>
                    <input type="text" name="jam" class="input-plain" required placeholder="11.45 - 12.30">
                    <p class="text-xs text-red-500 hidden field-error mt-1" data-for="jam"></p>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Tanggal Asli (untuk sorting)</label>
                    <input type="date" name="tanggal_asli" class="input-plain">
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Status</label>
                    <select name="status" class="input-plain">
                        <option value="coming_soon">Segera Hadir</option>
                        <option value="berlangsung">Sedang Berlangsung</option>
                        <option value="selesai">Sudah Selesai</option>
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-medium mb-1">Keterangan Tambahan</label>
                    <textarea name="keterangan" class="input-plain" rows="2"></textarea>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" class="toggle toggle-success" checked />
                    <label for="is_active" class="text-sm font-medium text-emerald-900">Tampilkan di Halaman Utama</label>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 border-t pt-4">
                <button type="button" id="cancelBtn" class="px-5 py-2 rounded-md border border-gray-300 hover:bg-gray-100">Batal</button>
                <button type="submit" id="saveBtn" class="px-6 py-2 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold shadow-md flex items-center gap-2">
                    <span id="saveBtnText">Simpan</span>
                    <span id="saveSpinner" class="loading loading-spinner loading-sm hidden"></span>
                </button>
            </div>
        </form>
    </div>
</dialog>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
    let table = null;
    const modal = document.getElementById('khutbahModal');
    const form = document.getElementById('khutbahForm');
    const saveBtn = document.getElementById('saveBtn');
    const saveBtnText = document.getElementById('saveBtnText');
    const saveSpinner = document.getElementById('saveSpinner');

    function showDialog() { modal.showModal ? modal.showModal() : modal.classList.add('modal-open'); }
    function closeDialog() { 
        modal.close ? modal.close() : modal.classList.remove('modal-open'); 
        form.reset();
        clearErrors();
        resetSaveButton();
    }

    function clearErrors() {
        document.querySelectorAll('.field-error').forEach(el => {
            el.textContent = '';
            el.classList.add('hidden');
        });
    }

    function showError(field, message) {
        const errEl = document.querySelector(`.field-error[data-for="${field}"]`);
        if (errEl) {
            errEl.textContent = message;
            errEl.classList.remove('hidden');
        }
    }

    function disableSaveButton() {
        saveBtn.disabled = true;
        saveBtn.classList.add('opacity-70', 'cursor-not-allowed');
        saveBtnText.textContent = 'Menyimpan...';
        saveSpinner.classList.remove('hidden');
    }

    function resetSaveButton() {
        saveBtn.disabled = false;
        saveBtn.classList.remove('opacity-70', 'cursor-not-allowed');
        saveBtnText.textContent = 'Simpan';
        saveSpinner.classList.add('hidden');
    }

    $(function() {
        table = $('#khutbahTable').DataTable({
            ajax: '{{ route("admin.khutbah-jumat.data") }}',
            columns: [
                { data: 'tanggal' },
                { data: 'khatib' },
                { data: 'tema' },
                { data: 'jam' },
                { data: 'status', render: d => {
                    if (d === 'coming_soon') return '<span class="badge badge-warning">Segera Hadir</span>';
                    if (d === 'berlangsung') return '<span class="badge badge-info">Berlangsung</span>';
                    return '<span class="badge badge-success">Selesai</span>';
                }},
                { data: 'is_active', render: d => d ? '<span class="badge badge-success">Ya</span>' : '<span class="badge badge-ghost">Tidak</span>' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: d => `
                        <div class="inline-flex gap-2">
                            <button class="btn btn-sm btn-warning btn-circle" onclick="edit(${d.id})">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M4 20h4l11-11a2.828 2.828 0 0 0-4-4L4 16v4z" stroke="currentColor" stroke-width="1.25"/></svg>
                            </button>
                            <button class="btn btn-sm btn-error btn-circle" onclick="hapus(${d.id})">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6v12a2 2 0 0 0 2 2h4a2 2 0 0 0 2-2V6" stroke="currentColor" stroke-width="1.25"/></svg>
                            </button>
                        </div>
                    `
                }
            ],
            language: {
                emptyTable: "Belum ada jadwal Khutbah Jumat",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_ entri",
                infoEmpty: "Tidak ada data",
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_",
                processing: "Memuat..."
            },
            order: [[0, 'desc']], // urut tanggal terbaru dulu
            pageLength: 10,
            lengthMenu: [5, 10, 25, 50]
        });

        $('#closeModal, #cancelBtn').on('click', closeDialog);
        modal.addEventListener('cancel', e => { e.preventDefault(); closeDialog(); });

        form.addEventListener('submit', async e => {
            e.preventDefault();
            clearErrors();
            disableSaveButton();

            const formData = new FormData(form);
            const method = document.getElementById('method').value;
            let action = form.action || '{{ route("admin.khutbah-jumat.store") }}';

            if (method === 'PUT') formData.append('_method', 'PUT');

            try {
                const response = await fetch(action, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                });

                const res = await response.json();

                if (res.success) {
                    closeDialog();
                    table.ajax.reload();
                    Swal.fire('Sukses', res.message, 'success');
                } else {
                    if (res.errors) {
                        Object.keys(res.errors).forEach(field => {
                            showError(field, res.errors[field][0]);
                        });
                    } else {
                        Swal.fire('Error', res.message || 'Gagal menyimpan', 'error');
                    }
                }
            } catch (err) {
                console.error(err);
                Swal.fire('Error', 'Terjadi kesalahan sistem', 'error');
            } finally {
                resetSaveButton();
            }
        });
    });

    window.addKhutbah = () => {
        form.reset();
        document.getElementById('method').value = 'POST';
        form.action = '{{ route("admin.khutbah-jumat.store") }}';
        document.getElementById('modalTitle').textContent = 'Tambah Jadwal Khutbah Baru';
        clearErrors();
        showDialog();
    };

    window.edit = async id => {
        try {
            const response = await fetch(`{{ url('admin/khutbah-jumat') }}/${id}/edit`);
            const data = await response.json();
            form.reset();
            Object.keys(data).forEach(key => {
                const el = form.querySelector(`[name="${key}"]`);
                if (el) {
                    if (el.type === 'checkbox') {
                        el.checked = !!data[key];
                    } else {
                        el.value = data[key] || '';
                    }
                }
            });
            document.getElementById('method').value = 'PUT';
            form.action = `{{ url('admin/khutbah-jumat') }}/${id}`;
            document.getElementById('modalTitle').textContent = `Edit: ${data.tema || 'Khutbah'}`;
            clearErrors();
            showDialog();
        } catch (err) {
            Swal.fire('Error', 'Gagal memuat data', 'error');
        }
    };

    window.hapus = id => {
        Swal.fire({
            title: 'Yakin hapus?',
            text: "Jadwal khutbah akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus',
            cancelButtonText: 'Batal'
        }).then(async result => {
            if (result.isConfirmed) {
                try {
                    const response = await fetch(`{{ url('admin/khutbah-jumat') }}/${id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                    });
                    const res = await response.json();
                    table.ajax.reload();
                    Swal.fire('Terhapus!', res.message || 'Jadwal dihapus', 'success');
                } catch (err) {
                    Swal.fire('Error', 'Gagal menghapus', 'error');
                }
            }
        });
    };
</script>
@endpush