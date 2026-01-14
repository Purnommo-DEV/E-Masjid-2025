{{-- resources/views/masjid/jurnal/index.blade.php --}}
@extends('masjid.master')
@section('title', 'Jurnal Umum')

@section('content')
<div class="min-h-screen bg-gray-50 py-8 px-4">
    <div class="max-w-7xl mx-auto">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">

            <!-- Header Emerald -->
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 px-8 py-7 text-white">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <h1 class="text-3xl font-bold flex items-center gap-3">
                            <i class="fas fa-file-alt"></i> Jurnal Umum
                        </h1>
                        <p class="opacity-90 mt-1">Semua transaksi keuangan masjid</p>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button id="btnRefresh" class="btn btn-ghost btn-sm text-white hover:bg-white/20">
                            <i class="fas fa-sync-alt mr-2"></i> Refresh
                        </button>
                        <input type="month" id="filterBulan" value="{{ date('Y-m') }}"
                            class="input input-bordered input-sm bg-white/20 text-white border-white/40">
                    </div>
                </div>
            </div>

            <!-- Kontrol Atas (Show Entries + Search) -->
            <div class="bg-base-100 border-b border-gray-200 px-6 py-4">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-6 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-gray-600">Tampilkan</span>
                            <select id="lengthMenu" class="select select-bordered select-sm w-20"></select>
                            <span class="text-gray-600">entri</span>
                        </div>
                        <div id="infoText" class="text-gray-500"></div>
                    </div>
                    <div class="relative">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input type="search" id="searchBox" placeholder="Cari transaksi..."
                            class="input input-bordered input-sm pl-10 w-64 max-w-full">
                    </div>
                </div>
            </div>

            <!-- Tabel -->
            <div class="p-6">
                <div class="overflow-x-auto rounded-xl border border-gray-200">
                    <table class="table table-zebra w-full text-sm" id="tabelJurnal">
                        <thead class="bg-emerald-50 text-emerald-800 font-semibold">
                            <tr>
                                <th>Tanggal</th>
                                <th>No. Jurnal</th>
                                <th>Keterangan</th>
                                <th>Akun</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Kredit</th>
                                <th>User</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex flex-col sm:flex-row justify-between items-center mt-6 gap-4">
                    <div id="paginationLeft" class="text-sm text-gray-600"></div>
                    <div class="join" id="pagination"></div>
                </div>
            </div>

            <!-- Total Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 px-6 pb-8">
                <div class="stat place-items-center bg-base-100 rounded-2xl shadow border">
                    <div class="stat-title">Total Entri</div>
                    <div class="stat-value text-emerald-600" id="totalEntri">0</div>
                    <div class="stat-desc">transaksi</div>
                </div>
                <div class="stat place-items-center bg-emerald-50 rounded-2xl shadow border border-emerald-200">
                    <div class="stat-title text-emerald-700">Total Debit</div>
                    <div class="stat-value text-emerald-600" id="totalDebit">Rp 0</div>
                </div>
                <div class="stat place-items-center bg-rose-50 rounded-2xl shadow border border-rose-200">
                    <div class="stat-title text-rose-700">Total Kredit</div>
                    <div class="stat-value text-rose-600" id="totalKredit">Rp 0</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function() {
    // Inisialisasi DataTable
    const table = $('#tabelJurnal').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("admin.keuangan.jurnal.data") }}',
            data: function(d) {
                d.bulan = $('#filterBulan').val();
            }
        },
        columns: [
            { 
                data: 'tanggal', 
                render: d => d 
                    ? new Date(d).toLocaleDateString('id-ID', {day:'2-digit', month:'2-digit', year:'numeric'}) 
                    : '-' 
            },
            { data: 'no_jurnal' },
            { data: 'keterangan' },
            {
                data: 'akun',
                render: function(data) {
                    if (!data) return '-';

                    const badge = {
                        'aset': 'badge-info',
                        'liabilitas': 'badge-warning',
                        'ekuitas': 'badge-secondary',
                        'pendapatan': 'badge-success',
                        'beban': 'badge-error'
                    };
                    const cls = badge[data.tipe] || 'badge-neutral';
                    return `<strong><span class="badge ${cls} badge-sm">${data.kode}</span></strong> ${data.nama}`;
                }
            },
            { 
                data: 'debit', 
                className: 'text-right font-medium',
                render: d => d > 0 ? 'Rp ' + Number(d).toLocaleString('id-ID') : '-'
            },
            { 
                data: 'kredit',
                className: 'text-right font-medium',
                render: d => d > 0 ? 'Rp ' + Number(d).toLocaleString('id-ID') : '-'
            },
            {
                data: 'user.name',
                render: d => d || '<em class="text-gray-400">System</em>'
            }

        ],
        order: [[0, 'desc']],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100],

        // Hanya render table + body, pagination kita bikin custom
        dom: 'rt',

        language: {
            processing: `
                <div class="flex items-center gap-3 py-4">
                    <span class="loading loading-spinner loading-lg text-emerald-600"></span>
                    <span class="text-sm text-gray-600">Memuat data...</span>
                </div>`
        },

        initComplete: function() {
            const api = this.api();

            // Length Menu
            const opts = [10, 25, 50, 100];
            const $select = $('#lengthMenu');
            opts.forEach(n => $select.append(`<option value="${n}">${n}</option>`));
            $select.val(api.page.len()).on('change', function() {
                api.page.len($(this).val()).draw();
            });

            // Search
            $('#searchBox').on('keyup search', function() {
                api.search(this.value).draw();
            });

            // Refresh button
            $('#btnRefresh').on('click', () => api.ajax.reload());

            updateInfoAndPagination();
            updateTotals();
        },

        drawCallback: function() {
            updateInfoAndPagination();
            updateTotals();
        }
    });

    // Filter bulan
    $('#filterBulan').on('change', function() {
        table.ajax.reload();
    });

    // CLICK PAGINATION (event delegation, tanpa inline onclick)
    $('#pagination').on('click', 'button[data-page]', function() {
        const target = $(this).data('page');

        if (target === 'prev') {
            table.page('previous').draw('page');
        } else if (target === 'next') {
            table.page('next').draw('page');
        } else {
            const pageIndex = parseInt(target);
            if (!isNaN(pageIndex)) {
                table.page(pageIndex).draw('page');
            }
        }
    });

    // Fungsi update info & pagination (versi modern)
    function updateInfoAndPagination() {
        const info = table.page.info();
        const $pagination = $('#pagination').empty();
        const totalPages = info.pages || 1;

        // Info kiri (badge modern)
        $('#paginationLeft').html(`
            <div class="inline-flex items-center gap-3 px-4 py-2 rounded-full bg-gray-50 border border-gray-200 text-xs sm:text-sm text-gray-600">
                <span class="font-semibold text-gray-700">
                    Halaman ${info.page + 1} dari ${totalPages}
                </span>
                <span class="hidden sm:inline w-px h-4 bg-gray-300"></span>
                <span class="hidden sm:inline">
                    Menampilkan <span class="font-semibold">${info.start + 1}</span>
                    &ndash; <span class="font-semibold">${info.end}</span>
                    dari <span class="font-semibold">${info.recordsTotal.toLocaleString('id-ID')}</span> entri
                </span>
            </div>
        `);

        // Previous button
        $pagination.append(`
            <button 
                class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gray-200 text-xs font-medium mr-1
                       ${info.page === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-emerald-50 hover:border-emerald-300 transition'}"
                data-page="prev"
                ${info.page === 0 ? 'disabled' : ''}>
                <i class="fas fa-chevron-left text-[10px]"></i>
            </button>
        `);

        // Number buttons
        let startPage = Math.max(0, info.page - 2);
        let endPage   = Math.min(totalPages, startPage + 5);
        if (endPage - startPage < 5) startPage = Math.max(0, endPage - 5);

        if (startPage > 0) {
            $pagination.append(`
                <button class="w-9 h-9 text-xs text-gray-400 cursor-default">...</button>
            `);
        }

        for (let i = startPage; i < endPage; i++) {
            const isActive = i === info.page;
            $pagination.append(`
                <button
                    class="inline-flex items-center justify-center w-9 h-9 rounded-full border text-xs font-medium mx-0.5
                           ${isActive 
                               ? 'bg-emerald-500 border-emerald-500 text-white shadow-md' 
                               : 'border-gray-200 text-gray-600 hover:bg-emerald-50 hover:border-emerald-300 transition'}"
                    data-page="${i}">
                    ${i + 1}
                </button>
            `);
        }

        if (endPage < totalPages) {
            $pagination.append(`
                <button class="w-9 h-9 text-xs text-gray-400 cursor-default">...</button>
            `);
        }

        // Next button
        $pagination.append(`
            <button 
                class="inline-flex items-center justify-center w-9 h-9 rounded-full border border-gray-200 text-xs font-medium ml-1
                       ${info.page >= totalPages - 1 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-emerald-50 hover:border-emerald-300 transition'}"
                data-page="next"
                ${info.page >= totalPages - 1 ? 'disabled' : ''}>
                <i class="fas fa-chevron-right text-[10px]"></i>
            </button>
        `);
    }

    // Update total (stat card bawah)
    function updateTotals() {
        let debit = 0, kredit = 0;
        table.column(4, {page: 'current'}).data().each(v => {
            if (v !== '-') debit += parseFloat(v.replace(/[^0-9.-]+/g, ''));
        });
        table.column(5, {page: 'current'}).data().each(v => {
            if (v !== '-') kredit += parseFloat(v.replace(/[^0-9.-]+/g, ''));
        });

        const entri = table.page.info().recordsDisplay;

        $('#totalEntri').text(entri.toLocaleString('id-ID'));
        $('#totalDebit').text('Rp ' + debit.toLocaleString('id-ID'));
        $('#totalKredit').text('Rp ' + kredit.toLocaleString('id-ID'));
    }
});
</script>
@endpush
