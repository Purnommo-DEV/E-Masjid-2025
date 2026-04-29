@extends('masjid.master')

@section('title', 'Daftar Pendaftar Qurban')

@section('content')

@push('style')
    <style>

        /* Preview gambar di modal */
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            object-fit: contain;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .image-preview:hover {
            transform: scale(1.02);
        }
        /* Perbaikan modal agar bisa scroll */
        .modal-box {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            padding: 0 !important;
            overflow: hidden;
            border-radius: 1rem;
        }

        .modal-header-sticky {
            flex-shrink: 0;
            border-radius: 1rem 1rem 0 0;
        }

        .modal-body-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .modal-body-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .modal-body-scroll::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 10px;
        }

        .modal-body-scroll::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 10px;
        }

        .modal-body-scroll::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }

        .modal-upload {
            max-height: 90vh;
        }

        .modal-upload .modal-box {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
            padding: 0 !important;
            overflow: hidden;
            border-radius: 1rem;
        }

        .modal-upload .modal-header {
            flex-shrink: 0;
            padding: 1rem 1.5rem;
            border-radius: 1rem 1rem 0 0;
        }

        .modal-upload .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        .modal-upload .modal-footer {
            flex-shrink: 0;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            background: white;
            border-radius: 0 0 1rem 1rem;
        }

        /* Scrollbar styling */
        .modal-upload .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .modal-upload .modal-body::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 10px;
        }

        .modal-upload .modal-body::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 10px;
        }
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            border: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        }
        .badge-status {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-confirmed { background: #d1fae5; color: #059669; }
        .badge-cancelled { background: #fee2e2; color: #dc2626; }
        .badge-completed { background: #dbeafe; color: #2563eb; }
        .btn-action {
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        .btn-action:hover { transform: scale(1.05); }
        .filter-btn-active {
            background-color: #10b981 !important;
            color: white !important;
        }
        .modal-box {
            max-width: 600px;
        }

    </style>
@endpush

    <div class="container mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-2xl p-6 text-white mb-6">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold">📋 Daftar Pendaftar Qurban</h1>
                    <p class="text-emerald-100 mt-1">Kelola data pendaftar dan update status</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.qurban.paket.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7M3 12h14" />
                        </svg>
                        Kembali
                    </a>
                    <button type="button" id="btnRefresh" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6" id="statCards">
            <div class="stat-card text-center">
                <div class="text-2xl font-bold text-gray-800" id="statTotal">0</div>
                <div class="text-sm text-gray-500">Total Pendaftar</div>
            </div>
            <div class="stat-card text-center border-l-4 border-l-yellow-500">
                <div class="text-2xl font-bold text-yellow-600" id="statPending">0</div>
                <div class="text-sm text-gray-500">Menunggu</div>
            </div>
            <div class="stat-card text-center border-l-4 border-l-green-500">
                <div class="text-2xl font-bold text-green-600" id="statConfirmed">0</div>
                <div class="text-sm text-gray-500">Dikonfirmasi</div>
            </div>
            <div class="stat-card text-center border-l-4 border-l-blue-500">
                <div class="text-2xl font-bold text-blue-600" id="statCompleted">0</div>
                <div class="text-sm text-gray-500">Selesai</div>
            </div>
            <div class="stat-card text-center border-l-4 border-l-red-500">
                <div class="text-2xl font-bold text-red-600" id="statCancelled">0</div>
                <div class="text-sm text-gray-500">Dibatalkan</div>
            </div>
        </div>

        <!-- Filter Status -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
            <div class="flex flex-wrap gap-2">
                <button type="button" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700" data-status="all">📋 Semua</button>
                <button type="button" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700" data-status="pending">⏳ Menunggu</button>
                <button type="button" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700" data-status="confirmed">✅ Dikonfirmasi</button>
                <button type="button" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700" data-status="completed">🎉 Selesai</button>
                <button type="button" class="filter-btn px-4 py-2 rounded-lg text-sm font-medium transition bg-gray-100 text-gray-700" data-status="cancelled">❌ Dibatalkan</button>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="p-4">  <!-- ✅ TAMBAHKAN PADDING INI -->
                <div class="overflow-x-auto">
                    <table id="registrasiTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-12">No</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kode</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nama</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Kontak</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paket</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Share</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        </tbody>
                    </table>
                </div>
            </div>  <!-- ✅ TUTUP PADDING -->
        </div>

    </div>

    <!-- Modal Detail -->
    <dialog id="detailModal" class="modal">
        <div class="modal-box w-11/12 max-w-3xl p-0 overflow-hidden">
            <!-- Header Sticky -->
            <div class="modal-header-sticky bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white">📄 Detail Pendaftaran</h3>
                    <button type="button" id="closeDetailModal" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
                </div>
            </div>
            
            <!-- Body Scroll -->
            <div id="detailContent" class="modal-body-scroll">
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600"></div>
                    <p class="mt-2 text-gray-500">Memuat data...</p>
                </div>
            </div>
        </div>
    </dialog>

    <!-- Modal Upload Bukti -->
    <dialog id="uploadModal" class="modal modal-upload">
        <div class="modal-box w-11/12 max-w-md p-0 overflow-hidden">
            <!-- Header -->
            <div class="modal-header bg-gradient-to-r from-emerald-600 to-teal-600">
                <div class="flex justify-between items-center">
                    <h3 class="text-xl font-bold text-white">📎 Upload Bukti Pembayaran</h3>
                    <button type="button" id="closeUploadModal" class="text-white hover:text-gray-200 text-2xl leading-none">&times;</button>
                </div>
            </div>
            
            <!-- Body (Scrollable) -->
            <div class="modal-body">
                <form id="uploadForm" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="uploadRegId" name="id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pilih File Gambar</label>
                        <input type="file" name="bukti_pembayaran" id="buktiFile" accept="image/jpeg,image/png,image/webp" class="w-full border border-gray-300 rounded-lg p-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" required>
                        <p class="text-xs text-gray-500 mt-1">📌 Format: JPG, PNG, WEBP (Max 2MB)</p>
                    </div>
                    
                    <div id="previewContainer" class="mb-4 hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">📷 Preview:</label>
                        <div class="border border-gray-200 rounded-lg p-2 bg-gray-50">
                            <img id="previewImage" class="w-full rounded-lg max-h-64 object-contain">
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Klik gambar untuk memperbesar</p>
                    </div>
                    
                    <div class="text-xs text-gray-400 bg-gray-50 p-2 rounded-lg">
                        <p class="font-semibold mb-1">📌 Tips:</p>
                        <ul class="list-disc list-inside space-y-0.5">
                            <li>Pastikan bukti transfer terbaca jelas</li>
                            <li>File maksimal 2MB</li>
                            <li>Format yang didukung: JPG, PNG, WEBP</li>
                        </ul>
                    </div>
                </form>
            </div>
            
            <!-- Footer (Sticky dengan Tombol) -->
            <div class="modal-footer">
                <div class="flex justify-end gap-3">
                    <button type="button" id="cancelUploadBtn" class="px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Batal
                    </button>
                    <button type="submit" form="uploadForm" class="px-4 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-lg hover:from-emerald-700 hover:to-teal-700 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                        </svg>
                        Upload Sekarang
                    </button>
                </div>
            </div>
        </div>
    </dialog>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.min.css">

<script>
$(document).ready(function() {
    let table;
    let currentStatus = 'all';
    
    // Load statistics
    function loadStatistics() {
        $.ajax({
            url: '{{ route("admin.qurban.registrasi.data") }}',
            type: 'GET',
            success: function(res) {
                if (res.data && res.data.length >= 0) {
                    // Hitung statistik dari data yang ada
                    let total = res.data.length;
                    let pending = res.data.filter(d => d.status === 'pending').length;
                    let confirmed = res.data.filter(d => d.status === 'confirmed').length;
                    let completed = res.data.filter(d => d.status === 'completed').length;
                    let cancelled = res.data.filter(d => d.status === 'cancelled').length;
                    
                    $('#statTotal').text(total);
                    $('#statPending').text(pending);
                    $('#statConfirmed').text(confirmed);
                    $('#statCompleted').text(completed);
                    $('#statCancelled').text(cancelled);
                }
            }
        });
    }
    
    // Initialize DataTable
    function initTable(status = 'all') {
        if (table) {
            table.destroy();
        }
        
        table = $('#registrasiTable').DataTable({
            processing: true,
            serverSide: false,
            ajax: {
                url: '{{ route("admin.qurban.registrasi.data") }}',
                type: 'GET',
                data: { status: status },
                dataSrc: function(json) {
                    // Filter data berdasarkan status jika diperlukan
                    let data = json.data || [];
                    if (status && status !== 'all') {
                        data = data.filter(item => item.status === status);
                    }
                    // Update statistics setelah load
                    setTimeout(loadStatistics, 100);
                    return data;
                },
                error: function(xhr) {
                    console.log('AJAX Error:', xhr.responseText);
                    Swal.fire('Error', 'Gagal memuat data', 'error');
                }
            },
            columns: [
                { 
                    data: null, 
                    render: (data, type, row, meta) => meta.row + 1 
                },
                { data: 'kode_registrasi' },
                { data: 'nama_lengkap' },
                { data: 'telepon' },
                { 
                    data: null,
                    render: (data) => `${data.jenis_icon || '🐐'} ${data.jenis_hewan || '-'}`
                },
                { data: 'jumlah_share', className: 'text-center' },
                { 
                    data: 'total_harga', 
                    className: 'text-right font-semibold text-emerald-600'
                },
                { 
                    data: 'status_badge',
                    className: 'text-center',
                    render: (data) => data || '<span class="badge-status bg-gray-100 text-gray-600">-</span>'
                },
                {
                    data: null,
                    className: 'text-center',
                    orderable: false,
                    render: (data) => `
                        <div class="flex justify-center gap-1">
                            <button class="btn-view p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Detail" data-id="${data.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </button>
                            ${data.status === 'pending' && !data.bukti_pembayaran ? `
                                <button class="btn-upload p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg" title="Upload Bukti" data-id="${data.id}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                </button>
                            ` : ''}
                            ${data.bukti_pembayaran ? `
                                <button class="btn-view-bukti p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Lihat Bukti" data-id="${data.id}" data-bukti="${data.bukti_pembayaran}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                </button>
                            ` : ''}
                            ${data.status === 'confirmed' ? `
                                <button class="btn-complete p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg transition" title="Selesai" data-id="${data.id}">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </button>
                            ` : ''}
                            <button class="btn-delete p-1.5 text-red-600 hover:bg-red-50 rounded-lg transition" title="Hapus" data-id="${data.id}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                            </button>
                        </div>
                    `
                }
            ],
            order: [[0, 'asc']],
            language: {
                "emptyTable": "Tidak ada data pendaftar qurban",
                "zeroRecords": "Tidak ada data yang cocok",
                "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ data",
                "infoEmpty": "Menampilkan 0 sampai 0 dari 0 data",
                "infoFiltered": "(disaring dari _MAX_ total data)",
                "search": "Cari:",
                "lengthMenu": "Tampilkan _MENU_ data",
                "paginate": {
                    "first": "Pertama",
                    "last": "Terakhir",
                    "next": "Selanjutnya",
                    "previous": "Sebelumnya"
                }
            }
        });
    }
    
    // Initial load
    initTable('all');
    loadStatistics();
    
    // Filter buttons
    $('.filter-btn').on('click', function() {
        $('.filter-btn').removeClass('filter-btn-active bg-emerald-600 text-white').addClass('bg-gray-100 text-gray-700');
        $(this).removeClass('bg-gray-100 text-gray-700').addClass('filter-btn-active bg-emerald-600 text-white');
        currentStatus = $(this).data('status');
        initTable(currentStatus);
    });
    
    // Refresh button
    $('#btnRefresh').on('click', function() {
        initTable(currentStatus);
        Swal.fire('Refresh', 'Data berhasil diperbarui', 'success');
    });
    
    // View detail
    $(document).on('click', '.btn-view', function() {
        const id = $(this).data('id');
        const modal = document.getElementById('detailModal');
        
        // Reset content dengan loading
        $('#detailContent').html(`
            <div class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-600"></div>
                <p class="mt-2 text-gray-500">Memuat data...</p>
            </div>
        `);
        
        modal.showModal();
        
        $.get(`{{ url('admin/qurban/registrasi') }}/${id}`, function(res) {
            if (res.success) {
                const d = res.data;
                
                // Cek apakah ada bukti pembayaran
                const buktiHtml = d.bukti_pembayaran ? 
                    `<div class="bg-gray-50 p-3 rounded-lg mt-2">
                        <label class="text-xs text-gray-500">Bukti Pembayaran</label>
                        <div class="mt-2">
                            <img src="${d.bukti_pembayaran}" class="image-preview max-h-40 rounded-lg cursor-pointer" onclick="window.open('${d.bukti_pembayaran}', '_blank')">
                            <p class="text-xs text-gray-400 mt-1">Klik gambar untuk memperbesar</p>
                        </div>
                    </div>` : 
                    `<div class="bg-gray-50 p-3 rounded-lg mt-2">
                        <label class="text-xs text-gray-500">Bukti Pembayaran</label>
                        <p class="text-yellow-600 text-sm">Belum ada bukti pembayaran</p>
                    </div>`;
                
                const html = `
                    <div class="space-y-4">
                        <!-- Informasi Pendaftar -->
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h4 class="font-semibold text-emerald-700 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                Informasi Pendaftar
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div><label class="text-xs text-gray-500">Kode Registrasi</label><p class="font-semibold text-emerald-700">${d.kode_registrasi}</p></div>
                                <div><label class="text-xs text-gray-500">Status</label><div>${d.status_badge}</div></div>
                                <div><label class="text-xs text-gray-500">Nama Lengkap</label><p class="font-semibold">${d.nama_lengkap}</p></div>
                                <div><label class="text-xs text-gray-500">Email</label><p>${d.email}</p></div>
                                <div><label class="text-xs text-gray-500">No WhatsApp</label><p class="font-semibold">${d.telepon}</p></div>
                                <div><label class="text-xs text-gray-500">Tanggal Daftar</label><p>${d.tanggal_daftar}</p></div>
                                <div class="md:col-span-2"><label class="text-xs text-gray-500">Alamat</label><p>${d.alamat}</p></div>
                            </div>
                        </div>
                        
                        <!-- Detail Qurban -->
                        <div class="bg-emerald-50 rounded-lg p-4">
                            <h4 class="font-semibold text-emerald-700 mb-3 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Detail Qurban
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div><label class="text-xs text-gray-500">Paket Qurban</label><p class="font-semibold">${d.qurban.jenis} (${d.qurban.share_badge})</p></div>
                                <div><label class="text-xs text-gray-500">Harga per Share</label><p>${d.qurban.harga}</p></div>
                                <div><label class="text-xs text-gray-500">Jumlah Share</label><p class="font-semibold">${d.jumlah_share} orang</p></div>
                                <div><label class="text-xs text-gray-500">Total Harga</label><p class="text-lg font-bold text-emerald-600">${d.total_harga}</p></div>
                            </div>
                        </div>
                        
                        <!-- Catatan -->
                        ${d.catatan && d.catatan !== '-' ? `
                        <div class="bg-yellow-50 rounded-lg p-4">
                            <h4 class="font-semibold text-yellow-700 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                Catatan
                            </h4>
                            <p class="text-gray-700">${d.catatan}</p>
                        </div>
                        ` : ''}
                        
                        <!-- Bukti Pembayaran -->
                        <div class="bg-purple-50 rounded-lg p-4">
                            <h4 class="font-semibold text-purple-700 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Bukti Pembayaran
                            </h4>
                            ${d.bukti_pembayaran ? `
                                <div class="mt-2">
                                    <img src="${d.bukti_pembayaran}" class="max-h-48 rounded-lg cursor-pointer border border-gray-200" onclick="window.open('${d.bukti_pembayaran}', '_blank')">
                                    <p class="text-xs text-gray-400 mt-1">Klik gambar untuk memperbesar</p>
                                    ${d.uploaded_at ? `<p class="text-xs text-gray-400 mt-1">Diupload: ${d.uploaded_at}</p>` : ''}
                                </div>
                            ` : '<p class="text-yellow-600 text-sm">Belum ada bukti pembayaran</p>'}
                        </div>
                        
                        <!-- Informasi Konfirmasi -->
                        ${d.confirmed_at && d.confirmed_at !== '-' ? `
                        <div class="bg-blue-50 rounded-lg p-4">
                            <h4 class="font-semibold text-blue-700 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Informasi Konfirmasi
                            </h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div><label class="text-xs text-gray-500">Dikonfirmasi Pada</label><p class="font-semibold">${d.confirmed_at}</p></div>
                                <div><label class="text-xs text-gray-500">Dikonfirmasi Oleh</label><p class="font-semibold">${d.confirmed_by}</p></div>
                            </div>
                        </div>
                        ` : ''}
                    </div>
                `;
                
                $('#detailContent').html(html);
            } else {
                $('#detailContent').html(`<div class="text-center text-red-500 py-8">Gagal memuat data</div>`);
            }
        }).fail(function() {
            $('#detailContent').html(`<div class="text-center text-red-500 py-8">Gagal memuat data</div>`);
        });
    });
    
    // ==================== LIHAT BUKTI ====================
    $(document).on('click', '.btn-view-bukti', function(e) {
        e.preventDefault();
        const buktiUrl = $(this).data('bukti');
        console.log('Lihat bukti clicked, URL:', buktiUrl); // Debug
        
        if (buktiUrl && buktiUrl !== '') {
            // Tampilkan gambar dengan SweetAlert
            Swal.fire({
                title: 'Bukti Pembayaran',
                imageUrl: buktiUrl,
                imageAlt: 'Bukti Pembayaran',
                imageWidth: '80%',
                imageHeight: 'auto',
                showConfirmButton: true,
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#10b981',
                background: '#1f2937',
                customClass: {
                    popup: 'rounded-xl',
                    image: 'rounded-lg max-h-96 object-contain'
                }
            });
        } else {
            Swal.fire({
                icon: 'info',
                title: 'Belum Ada Bukti',
                text: 'Pendaftar belum mengupload bukti pembayaran.',
                confirmButtonColor: '#10b981'
            });
        }
    });
    
    // Update status function
    function updateStatus(id, status, confirmMessage, successMessage) {
        Swal.fire({
            title: confirmMessage,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: status === 'cancelled' ? '#dc2626' : '#10b981',
            confirmButtonText: 'Ya, lanjutkan!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/qurban/registrasi') }}/${id}/status`,
                    method: 'PUT',
                    data: { status: status, _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', successMessage, 'success');
                            initTable(currentStatus);
                            loadStatistics();
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.fire('Error!', 'Terjadi kesalahan', 'error');
                    }
                });
            }
        });
    }
    
    // Confirm registration
    $(document).on('click', '.btn-confirm', function() {
        const id = $(this).data('id');
        updateStatus(id, 'confirmed', 'Konfirmasi pendaftaran?', 'Pendaftaran telah dikonfirmasi!');
    });
    
    // Cancel registration
    $(document).on('click', '.btn-cancel', function() {
        const id = $(this).data('id');
        updateStatus(id, 'cancelled', 'Batalkan pendaftaran? Stok akan dikembalikan.', 'Pendaftaran dibatalkan!');
    });
    
    // Complete registration
    $(document).on('click', '.btn-complete', function() {
        const id = $(this).data('id');
        updateStatus(id, 'completed', 'Tandai sebagai selesai?', 'Pendaftaran telah selesai!');
    });
    
    // Delete registration
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Pendaftaran?',
            text: "Data akan dihapus permanen! Stok akan dikembalikan.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/qurban/registrasi') }}/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Terhapus!', res.message, 'success');
                            initTable(currentStatus);
                            loadStatistics();
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
    
    // Preview gambar sebelum upload dengan resize
    $('#buktiFile').on('change', function() {
        const file = this.files[0];
        if (file) {
            // Validasi ukuran file
            const fileSizeMB = file.size / 1024 / 1024;
            if (fileSizeMB > 2) {
                Swal.fire('Error', `Ukuran file ${fileSizeMB.toFixed(2)}MB melebihi batas maksimal 2MB. Silakan kompres file terlebih dahulu.`, 'error');
                $(this).val('');
                $('#previewContainer').addClass('hidden');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImage').attr('src', e.target.result);
                $('#previewContainer').removeClass('hidden');
                
                // Optional: resize preview image
                const img = new Image();
                img.onload = function() {
                    const maxHeight = 250;
                    if (img.height > maxHeight) {
                        const ratio = maxHeight / img.height;
                        $('#previewImage').css({
                            'max-height': maxHeight + 'px',
                            'width': 'auto'
                        });
                    }
                }
                img.src = e.target.result;
            }
            reader.readAsDataURL(file);
        }
    });

    // Preview gambar klik untuk memperbesar
    $(document).on('click', '#previewImage', function() {
        const src = $(this).attr('src');
        if (src) {
            Swal.fire({
                imageUrl: src,
                imageAlt: 'Preview Bukti Pembayaran',
                imageWidth: '80%',
                imageHeight: 'auto',
                showConfirmButton: true,
                confirmButtonText: 'Tutup',
                background: '#1f2937'
            });
        }
    });

    // Upload bukti
    $(document).on('click', '.btn-upload', function() {
        const id = $(this).data('id');
        $('#uploadRegId').val(id);
        $('#uploadForm')[0].reset();
        $('#previewContainer').addClass('hidden');
        $('#uploadModal')[0].showModal();
    });

    // Submit upload form
    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const id = $('#uploadRegId').val();
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Disable button dan show loading
        submitBtn.html('<svg class="w-4 h-4 animate-spin mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Uploading...').prop('disabled', true);
        
        $.ajax({
            url: `{{ url('admin/qurban/registrasi') }}/${id}/upload-bukti`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#uploadModal')[0].close();
                    Swal.fire('Berhasil!', res.message, 'success');
                    initTable(currentStatus);
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error!', message, 'error');
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Close modal
    $('#closeUploadModal, #cancelUploadBtn').on('click', function() {
        $('#uploadModal')[0].close();
        $('#uploadForm')[0].reset();
        $('#previewContainer').addClass('hidden');
    });

    // Upload bukti
    $(document).on('click', '.btn-upload', function() {
        const id = $(this).data('id');
        $('#uploadRegId').val(id);
        $('#uploadForm')[0].reset();
        $('#previewContainer').addClass('hidden');
        $('#uploadModal')[0].showModal();
    });

    $('#uploadForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const id = $('#uploadRegId').val();
        
        $.ajax({
            url: `{{ url('admin/qurban/registrasi') }}/${id}/upload-bukti`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    $('#uploadModal')[0].close();
                    Swal.fire('Berhasil!', res.message, 'success');
                    initTable(currentStatus);
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error!', message, 'error');
            }
        });
    });

    // Hapus bukti
    $(document).on('click', '.btn-delete-bukti', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Bukti?',
            text: "Bukti pembayaran akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `{{ url('admin/qurban/registrasi') }}/${id}/delete-bukti`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Terhapus!', res.message, 'success');
                            initTable(currentStatus);
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    }
                });
            }
        });
    });

    // Close modals
    $('#closeUploadModal, #cancelUploadBtn').on('click', function() {
        $('#uploadModal')[0].close();
    });

    // Close modal
    $('#closeDetailModal').on('click', function() {
        document.getElementById('detailModal').close();
    });
    
    console.log('Registrasi page loaded successfully');
});
</script>
@endpush