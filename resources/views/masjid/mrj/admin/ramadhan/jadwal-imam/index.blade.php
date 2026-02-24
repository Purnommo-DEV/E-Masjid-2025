@extends('masjid.master')

@section('title', 'Jadwal Imam Tarawih Ramadhan 1447H')

@section('content')
@push('style')
<style>
    /* Reuse style dari halaman kategori berita kamu */
    .card-wrapper {
        max-width: 1200px;
        margin: 1.25rem auto;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(2,6,23,0.06);
        border: 1px solid rgba(15,23,42,0.04);
        background: white;
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        color: #fff;
        background: linear-gradient(90deg, #059669 0%, #10b981 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .card-header .title { margin:0; font-size:1.125rem; font-weight:700; }
    .card-header .subtitle { margin:0; opacity:.95; font-size:.95rem; }
    .card-body { padding: 1.25rem 1.5rem; background: white; }
    .header-action {
        background: rgba(255,255,255,0.12);
        color: #fff;
        padding: 0.5rem 0.9rem;
        border-radius: 999px;
        display: inline-flex;
        gap: .5rem;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 6px 14px rgba(4,120,87,0.06);
        transition: transform .12s ease, background .12s;
    }
    .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }
    table.dataTable td { white-space: normal !important; }
    .malam-column { width: 100px; text-align: center; }
    .date-column { width: 140px; text-align: center; }
    .btn-circle-ico {
        display:inline-flex; align-items:center; justify-content:center;
        width:36px; height:36px; border-radius:8px;
        transition: transform .12s ease;
    }
    .btn-circle-ico:hover { transform: translateY(-2px); }
    dialog.modal::backdrop {
        background: rgba(15,23,42,0.55);
        backdrop-filter: blur(4px) saturate(1.02);
    }
    dialog.modal { border: none; padding: 0; }
    .modal-box { border-radius: 12px; box-shadow: 0 18px 40px rgba(2,6,23,0.12); overflow: hidden; background: white; }
    .input-plain {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 0.5rem 0.75rem;
        width: 100%;
        font-size: 0.875rem;
    }
    .input-plain:focus { outline: none; border-color: #10b981; box-shadow: 0 0 0 3px rgba(16,185,129,0.1); }
    @media (max-width: 640px) {
        .card-header { padding: .9rem 1rem; }
        .card-body { padding: 1rem; }
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <div>
            <h3 class="title">Jadwal Imam Tarawih Ramadhan 1447H</h3>
            <p class="subtitle">Kelola imam, penceramah, dan tema tausiyah untuk setiap malam tarawih</p>
        </div>
        <button type="button" class="header-action" onclick="addJadwal()">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            <span class="text-sm font-semibold">Tambah Jadwal</span>
        </button>
    </div>

    <div class="card-body">
        <div class="overflow-x-auto">
            <table id="jadwalTable" class="table table-zebra w-full text-sm" style="width:100%">
                <thead class="bg-emerald-50 text-emerald-900 font-semibold">
                    <tr>
                        <th class="px-4 py-3 malam-column">Malam ke-</th>
                        <th class="px-4 py-3 date-column">Tanggal</th>
                        <th class="px-4 py-3">Imam</th>
                        <th class="px-4 py-3">Penceramah</th>
                        <th class="px-4 py-3">Tema Tausiyah</th>
                        <th class="px-4 py-3 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Form (CRUD Add/Edit) -->
<dialog id="jadwalModal" class="modal" aria-labelledby="jadwalModalTitle">
    <div class="modal-box w-11/12 max-w-2xl max-h-[90vh] flex flex-col">
        <form id="jadwalForm" class="flex-1 p-6 overflow-y-auto">
            @csrf
            <input type="hidden" id="method" value="POST">
            <input type="hidden" name="id" id="jadwalId">

            <div class="flex items-center justify-between mb-6">
                <h3 id="jadwalModalTitle" class="text-xl font-bold text-emerald-800">Form Jadwal Imam Tarawih</h3>
                <button type="button" id="closeJadwalModal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Malam ke-</label>
                    <input type="number" name="malam_ke" min="1" max="30" class="input-plain" required placeholder="Contoh: 1">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal</label>
                    <input type="date" name="tanggal" class="input-plain">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Imam</label>
                    <input type="text" name="imam_nama" class="input-plain" required placeholder="Ustadz Suparlan">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Penceramah / Khatib</label>
                    <input type="text" name="penceramah_nama" class="input-plain" placeholder="Ustadz Imam Syuhada">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tema Tausiyah</label>
                    <input type="text" name="tema_tausiyah" class="input-plain" placeholder="Al-Qur’an sebagai obat segala penyakit (Asy-Syifa)">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Catatan Tambahan (opsional)</label>
                    <textarea name="catatan" rows="3" class="input-plain"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-4 mt-8 border-t pt-5">
                <button type="button" id="cancelJadwalBtn" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 rounded-lg font-medium">Batal</button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold shadow">Simpan Jadwal</button>
            </div>
        </form>
    </div>
</dialog>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
    let table = null;
    const modal = document.getElementById('jadwalModal');
    const form = document.getElementById('jadwalForm');

    // Helper show/close dialog (fallback support)
    function showDialog(el) {
        if (typeof el.showModal === 'function') {
            el.showModal();
        } else {
            el.classList.add('modal-open');
        }
    }
    function closeDialog(el) {
        if (typeof el.close === 'function') {
            el.close();
        } else {
            el.classList.remove('modal-open');
        }
    }

    $(function () {
        table = $('#jadwalTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: '{{ route('admin.ramadhan.jadwal-imam.data') }}',
            columns: [
                { data: 'malam_ke', className: 'malam-column font-medium' },
                { data: 'tanggal', className: 'date-column' },
                { data: 'imam_nama' },
                { data: 'penceramah_nama' },
                { data: 'tema_tausiyah' },
                {
                    data: null,
                    orderable: false,
                    className: 'text-center',
                    render: function (data, type, row) {
                        return `
                            <div class="inline-flex gap-2 justify-center">
                                <button class="btn-circle-ico bg-yellow-50 hover:bg-yellow-100 text-yellow-700" title="Edit" onclick="editJadwal(${row.id})">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <button class="btn-circle-ico bg-red-50 hover:bg-red-100 text-red-700" title="Hapus" onclick="deleteJadwal(${row.id}, ${row.malam_ke})">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </div>
                        `;
                    }
                }
            ],
            order: [[0, 'asc']],
            responsive: true,
            language: {
                search: "Cari:",
                lengthMenu: "Tampil _MENU_ data",
                info: "Menampilkan _START_ - _END_ dari _TOTAL_",
                processing: "Memuat data..."
            }
        });

        // Modal controls
        document.getElementById('closeJadwalModal').onclick = () => closeDialog(modal);
        document.getElementById('cancelJadwalBtn').onclick = () => closeDialog(modal);
        if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });

        // Submit form (AJAX)
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const method = document.getElementById('method').value;
            const action = form.getAttribute('action') || '{{ route("admin.ramadhan.jadwal-imam.store") }}';

            if (method === 'PUT') {
                formData.append('_method', 'PUT');
            }

            fetch(action, {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    closeDialog(modal);
                    table.ajax.reload();
                    Swal.fire('Berhasil!', data.message, 'success');
                } else {
                    Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
                }
            })
            .catch(err => {
                Swal.fire('Error', 'Gagal terhubung ke server', 'error');
            });
        });
    });

    window.addJadwal = function () {
        form.reset();
        document.getElementById('method').value = 'POST';
        form.action = '{{ route("admin.ramadhan.jadwal-imam.store") }}';
        document.getElementById('jadwalModalTitle').textContent = 'Tambah Jadwal Imam Tarawih';
        showDialog(modal);
        setTimeout(() => document.querySelector('[name="malam_ke"]').focus(), 100);
    };

    window.editJadwal = function (id) {
        fetch(`{{ url('admin/ramadhan/jadwal-imam') }}/${id}/edit`, {
            headers: { 
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'  // ← tambahkan ini biar Laravel tahu AJAX
            }
        })
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
            return res.json();
        })
        .then(data => {
            console.log('Data edit diterima:', data); // ← TAMBAHKAN INI untuk debug di console browser

            form.reset();
            document.getElementById('method').value = 'PUT';
            form.action = `{{ url('admin/ramadhan/jadwal-imam') }}/${id}`;
            document.getElementById('jadwalModalTitle').textContent = `Edit Jadwal Malam ke-${data.malam_ke}`;

            // Pastikan selector dan value match persis dengan key JSON
            document.querySelector('[name="malam_ke"]').value = data.malam_ke || '';
            document.querySelector('[name="tanggal"]').value = data.tanggal || '';
            document.querySelector('[name="imam_nama"]').value = data.imam_nama || '';
            document.querySelector('[name="penceramah_nama"]').value = data.penceramah_nama || '';
            document.querySelector('[name="tema_tausiyah"]').value = data.tema_tausiyah || '';
            document.querySelector('[name="catatan"]').value = data.catatan || '';

            showDialog(modal);
            setTimeout(() => document.querySelector('[name="malam_ke"]').focus(), 100);
        })
        .catch(err => {
            console.error('Fetch error:', err); // ← TAMBAHKAN INI untuk debug
            Swal.fire('Error', 'Gagal memuat data jadwal: ' + err.message, 'error');
        });
    };

    window.deleteJadwal = function (id, malamKe) {
        Swal.fire({
            title: 'Yakin hapus?',
            text: `Jadwal malam ke-${malamKe} akan dihapus permanen!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`{{ url('admin/ramadhan/jadwal-imam') }}/${id}`, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        table.ajax.reload();
                        Swal.fire('Terhapus!', data.message, 'success');
                    } else {
                        Swal.fire('Gagal', data.message || 'Terjadi kesalahan', 'error');
                    }
                })
                .catch(() => Swal.fire('Error', 'Gagal menghapus data', 'error'));
            }
        });
    };
</script>
@endpush
@endsection