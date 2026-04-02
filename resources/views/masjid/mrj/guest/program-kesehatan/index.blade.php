@extends('masjid.master-guest')

@section('title', 'Daftar Pendaftar Program Kesehatan - Masjid Raudhotul Jannah TCE')
@section('og_title', 'Daftar Pendaftar Program Kesehatan')

@section('content')
<section class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50 py-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-7xl">

        <!-- Header -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-12">
            <div>
                <h1 class="text-4xl lg:text-5xl font-bold text-emerald-800 tracking-tight">Daftar Pendaftar</h1>
                <p class="text-emerald-700 mt-3 text-lg flex items-center gap-2">
                    <span class="text-2xl">🕌</span>
                    Program Kesehatan • {{ \Carbon\Carbon::parse($eventDate)->translatedFormat('d F Y') }}
                </p>
            </div>
            <div class="stats shadow-xl bg-white border border-emerald-200 rounded-3xl">
                <div class="stat text-center px-10 py-2">
                    <div class="stat-title text-emerald-600 font-medium">Total Pendaftar Hari Ini</div>
                    <div class="stat-value text-6xl font-bold text-emerald-700">{{ $totalPendaftar }}</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs tabs-lifted tabs-lg mb-10">
            <a class="tab tab-active font-semibold text-emerald-700" data-tab="all">Semua Pendaftar</a>
            <a class="tab font-semibold text-emerald-700" data-tab="donor">Donor Darah ({{ $donorDarah->count() }})</a>
            <a class="tab font-semibold text-emerald-700" data-tab="cek">Cek Kesehatan ({{ $cekKesehatan->count() }})</a>
            <a class="tab font-semibold text-emerald-700" data-tab="katarak">Cek Katarak ({{ $cekKatarak->count() }})</a>
        </div>

        <!-- ==================== TAB SEMUA PENDAFTAR ==================== -->
        <div id="tab-all" class="tab-content">
            <div class="card bg-white shadow-2xl border border-emerald-100 rounded-3xl overflow-hidden">
                <div class="card-body p-8">
                    <div class="overflow-x-auto">
                        <table id="tableAll" class="table table-zebra w-full">
                            <thead>
                                <tr class="bg-emerald-700 text-white">
                                    <th class="w-12">No</th>
                                    <th>Nama Lengkap</th>
                                    <th>No. HP</th>
                                    <th>Alamat</th>
                                    <th>Program</th>
                                    <th>Waktu Daftar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allRegistrations as $index => $item)
                                <tr class="hover:bg-emerald-50">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?? '<span class="text-slate-400">-</span>' }}</td>
                                    <td>
                                        @if($item->donor_darah)<span class="badge badge-success">Donor Darah</span>@endif
                                        @if($item->cek_mata_katarak)<span class="badge badge-info">Cek Katarak</span>@endif
                                        @if($item->cek_kesehatan && count($item->cek_kesehatan) > 0)<span class="badge badge-warning">Cek Kesehatan</span>@endif
                                    </td>
                                    <td class="text-sm text-emerald-700">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB DONOR DARAH ==================== -->
        <div id="tab-donor" class="tab-content hidden">
            <div class="flex justify-end mb-6">
                <a href="{{ route('kesehatan.export.donor') }}" class="btn btn-success shadow-md hover:shadow-lg">
                    📥 Export Excel Donor Darah
                </a>
            </div>
            <div class="card bg-white shadow-2xl border border-emerald-100 rounded-3xl overflow-hidden">
                <div class="card-body p-8">
                    <div class="overflow-x-auto">
                        <table id="tableDonor" class="table table-zebra w-full">
                            <thead>
                                <tr class="bg-emerald-700 text-white">
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>No. HP</th>
                                    <th>Alamat</th>
                                    <th>Waktu Daftar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($donorDarah as $index => $item)
                                <tr class="hover:bg-emerald-50">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?? '-' }}</td>
                                    <td class="text-sm text-emerald-700">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB CEK KESEHATAN ==================== -->
        <div id="tab-cek" class="tab-content hidden">
            <div class="flex justify-end mb-6">
                <a href="{{ route('kesehatan.export.cek-kesehatan') }}" class="btn btn-success shadow-md hover:shadow-lg">
                    📥 Export Excel Cek Kesehatan
                </a>
            </div>
            <div class="card bg-white shadow-2xl border border-emerald-100 rounded-3xl overflow-hidden">
                <div class="card-body p-8">
                    <div class="overflow-x-auto">
                        <table id="tableCek" class="table table-zebra w-full">
                            <thead>
                                <tr class="bg-emerald-700 text-white">
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>No. HP</th>
                                    <th>Gula Darah</th>
                                    <th>Kolesterol</th>
                                    <th>Asam Urat</th>
                                    <th>Alamat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cekKesehatan as $index => $item)
                                @php $cek = $item->cek_kesehatan ?? []; @endphp
                                <tr class="hover:bg-emerald-50">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp }}</td>
                                    <td class="text-center text-xl">{{ in_array('gula_darah', $cek) ? '✅' : '' }}</td>
                                    <td class="text-center text-xl">{{ in_array('kolesterol', $cek) ? '✅' : '' }}</td>
                                    <td class="text-center text-xl">{{ in_array('asam_urat', $cek) ? '✅' : '' }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB CEK KATARAK ==================== -->
        <div id="tab-katarak" class="tab-content hidden">
            <div class="flex justify-end mb-6">
                <a href="{{ route('kesehatan.export.cek-katarak') }}" class="btn btn-success shadow-md hover:shadow-lg">
                    📥 Export Excel Cek Katarak
                </a>
            </div>
            <div class="card bg-white shadow-2xl border border-emerald-100 rounded-3xl overflow-hidden">
                <div class="card-body p-8">
                    <div class="overflow-x-auto">
                        <table id="tableKatarak" class="table table-zebra w-full">
                            <thead>
                                <tr class="bg-emerald-700 text-white">
                                    <th>No</th>
                                    <th>Nama Lengkap</th>
                                    <th>No. HP</th>
                                    <th>Alamat</th>
                                    <th>Waktu Daftar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cekKatarak as $index => $item)
                                <tr class="hover:bg-emerald-50">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?? '-' }}</td>
                                    <td class="text-sm text-emerald-700">{{ $item->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">

<style>
    .dataTables_wrapper {
        color: #0f172a;
        margin-bottom: 20px;
    }

    /* Search Box - Diperkecil & Diberi Jarak */
    .dataTables_filter {
        margin-bottom: 20px !important;
    }
    .dataTables_filter label input {
        border: 2px solid #10b981 !important;
        border-radius: 9999px !important;
        padding: 10px 20px !important;
        width: 280px !important;           /* diperkecil */
        color: #065f46 !important;
        background: #f8fafc !important;
        font-size: 15px;
    }
    .dataTables_filter input:focus {
        border-color: #059669 !important;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25) !important;
    }

    /* Length Menu */
    .dataTables_length {
        margin-bottom: 15px;
    }
    .dataTables_length select {
        border: 2px solid #10b981 !important;
        border-radius: 9999px !important;
        color: #065f46;
    }

    /* Info Text */
    .dataTables_info {
        color: #065f46 !important;
        font-weight: 600 !important;
    }

    /* Pagination */
    .dataTables_paginate .paginate_button {
        border-radius: 9999px !important;
        padding: 8px 16px !important;
        color: #065f46 !important;
        border: 1px solid #a7f3d0 !important;
    }
    .dataTables_paginate .paginate_button:hover {
        background: #ecfdf5 !important;
        color: #10b981 !important;
    }
    .dataTables_paginate .paginate_button.current {
        background: #10b981 !important;
        color: white !important;
    }

    /* Table */
    .table th { padding: 18px 12px !important; }
    .table td { padding: 16px 12px !important; }
</style>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    const tableOptions = {
        language: {
            search: "Cari pendaftar...",
            lengthMenu: "Tampilkan _MENU_ data",
            info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
            zeroRecords: "Tidak ada data yang ditemukan",
            paginate: {
                previous: "← Sebelumnya",
                next: "Berikutnya →"
            }
        },
        pageLength: 25,
        order: [[0, 'desc']]
    };

    $('#tableAll').DataTable(tableOptions);
    $('#tableDonor').DataTable(tableOptions);
    $('#tableCek').DataTable(tableOptions);
    $('#tableKatarak').DataTable(tableOptions);

    // Tab Switching
    $('.tab').on('click', function() {
        $('.tab').removeClass('tab-active');
        $(this).addClass('tab-active');
        $('.tab-content').addClass('hidden');
        $('#tab-' + $(this).data('tab')).removeClass('hidden');
    });
});
</script>
@endpush