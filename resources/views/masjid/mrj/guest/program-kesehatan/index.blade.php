@extends('masjid.master-guest')

@section('title', 'Daftar Pendaftar Program Kesehatan - Masjid Raudhotul Jannah TCE')
@section('og_title', 'Daftar Pendaftar Program Kesehatan')

@section('content')
@php
    $normalizeCekKesehatan = function ($value) {
        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \Illuminate\Support\Collection) {
            return $value->all();
        }

        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }

        return [];
    };

    $formatWaktuDaftar = function ($value) {
        return $value ? \Carbon\Carbon::parse($value)->format('d/m/Y H:i') : '-';
    };

    $layananCekKesehatan = [
        'gula_darah' => 'Gula Darah',
        'kolesterol' => 'Kolesterol',
        'asam_urat' => 'Asam Urat',
        'tensi_darah' => 'Tensi Darah',
    ];
@endphp
<section class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-emerald-50 py-12">
    <div class="container mx-auto px-4 lg:px-6 max-w-7xl">

        <!-- Header -->
        <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6 mb-12">
            <div>
                <h1 class="text-4xl lg:text-5xl font-bold text-emerald-800 tracking-tight">Daftar Pendaftar</h1>
                <p class="text-emerald-700 mt-3 text-lg flex items-center gap-2">
                    Program Kesehatan - {{ \Carbon\Carbon::parse($eventDate)->translatedFormat('d F Y') }}
                </p>
            </div>
            <div class="stats shadow-xl bg-white border border-emerald-200 rounded-3xl">
                <div class="stat text-center px-10 py-2">
                    <div class="stat-title text-emerald-600 font-medium">Total Pendaftar</div>
                    <div class="stat-value text-6xl font-bold text-emerald-700">{{ $totalPendaftar }}</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs tabs-lifted tabs-lg mb-10">
            <a class="tab tab-active font-semibold text-emerald-700" data-tab="all">Semua Pendaftar</a>
            <a class="tab font-semibold text-emerald-700" data-tab="donor">Donor Darah ({{ $donorDarah->count() }})</a>
            <a class="tab font-semibold text-emerald-700" data-tab="cek">Cek Kesehatan ({{ $cekKesehatan->count() }})</a>
            <a class="tab font-semibold text-emerald-700" data-tab="katarak">Cek Mata Katarak ({{ $cekKatarak->count() }})</a>
        </div>

        <!-- ==================== TAB SEMUA PENDAFTAR ==================== -->
        <div id="tab-all" class="custom-tab-content">
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
                                @php $cek = $normalizeCekKesehatan($item->cek_kesehatan); @endphp
                                <tr class="hover:bg-emerald-50">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap ?: '-' }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp ?: '-' }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?: '-' }}</td>
                                    <td>
                                        @if($item->donor_darah)<span class="badge badge-success">Donor Darah</span>@endif
                                        @if($item->cek_mata_katarak)<span class="badge badge-info">Cek Mata Katarak</span>@endif
                                        @foreach($layananCekKesehatan as $key => $label)
                                            @if(in_array($key, $cek, true))
                                                <span class="badge badge-warning">{{ $label }}</span>
                                            @endif
                                        @endforeach
                                        @if(! $item->donor_darah && ! $item->cek_mata_katarak && count($cek) === 0)
                                            <span class="text-slate-400">-</span>
                                        @endif
                                    </td>
                                    <td class="text-sm text-emerald-700">{{ $formatWaktuDaftar($item->created_at) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB DONOR DARAH ==================== -->
        <div id="tab-donor" class="custom-tab-content hidden">
            <div class="flex justify-end mb-6">
                <a href="{{ route('donor-darah.export.donor') }}" class="btn btn-success shadow-md hover:shadow-lg">
                    Export Excel Donor Darah
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
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap ?: '-' }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp ?: '-' }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?: '-' }}</td>
                                    <td class="text-sm text-emerald-700">{{ $formatWaktuDaftar($item->created_at) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB CEK KESEHATAN ==================== -->
        <div id="tab-cek" class="custom-tab-content hidden">
            <div class="flex justify-end mb-6">
                <a href="{{ route('donor-darah.export.cek-kesehatan') }}" class="btn btn-success shadow-md hover:shadow-lg">
                    Export Excel Cek Kesehatan
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
                                    <th>Tensi Darah</th>
                                    <th>Alamat</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cekKesehatan as $index => $item)
                                @php $cek = $normalizeCekKesehatan($item->cek_kesehatan); @endphp
                                <tr class="hover:bg-emerald-50">
                                    <td>{{ $index + 1 }}</td>
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap ?: '-' }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp ?: '-' }}</td>
                                    <td class="text-center font-semibold text-emerald-700">{{ in_array('gula_darah', $cek, true) ? 'Ya' : '-' }}</td>
                                    <td class="text-center font-semibold text-emerald-700">{{ in_array('kolesterol', $cek, true) ? 'Ya' : '-' }}</td>
                                    <td class="text-center font-semibold text-emerald-700">{{ in_array('asam_urat', $cek, true) ? 'Ya' : '-' }}</td>
                                    <td class="text-center font-semibold text-emerald-700">{{ in_array('tensi_darah', $cek, true) ? 'Ya' : '-' }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?: '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- ==================== TAB CEK KATARAK ==================== -->
        <div id="tab-katarak" class="custom-tab-content hidden">
            <div class="flex justify-end mb-6">
                <a href="{{ route('donor-darah.export.cek-katarak') }}" class="btn btn-success shadow-md hover:shadow-lg">
                    Export Excel Cek Mata Katarak
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
                                    <td class="font-semibold text-emerald-800">{{ $item->nama_lengkap ?: '-' }}</td>
                                    <td class="font-medium text-emerald-800">{{ $item->no_hp ?: '-' }}</td>
                                    <td class="text-slate-600">{{ $item->alamat ?: '-' }}</td>
                                    <td class="text-sm text-emerald-700">{{ $formatWaktuDaftar($item->created_at) }}</td>
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
    .dataTables_filter {
        margin-bottom: 20px !important;
    }
    .dataTables_filter label input {
        border: 2px solid #10b981 !important;
        border-radius: 9999px !important;
        padding: 10px 20px !important;
        width: 280px !important;
        color: #065f46 !important;
        background: #f8fafc !important;
        font-size: 15px;
    }
    .dataTables_filter input:focus {
        border-color: #059669 !important;
        box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.25) !important;
    }
    .dataTables_length select {
        border: 2px solid #10b981 !important;
        border-radius: 9999px !important;
        color: #065f46;
    }
    .dataTables_info {
        color: #065f46 !important;
        font-weight: 600 !important;
    }
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
                previous: "Sebelumnya",
                next: "Berikutnya"
            }
        },
        pageLength: 25,
        order: [[0, 'asc']],
        responsive: true,
        deferRender: true
    };

    const initialized = new Set();

    function initTable(tableId) {
        if (initialized.has(tableId)) return;
        
        $(tableId).DataTable(tableOptions);
        initialized.add(tableId);
    }

    // Inisialisasi tab pertama (yang aktif)
    initTable('#tableAll');

    // Tab Switching
    $('.tab').on('click', function() {
        $('.tab').removeClass('tab-active');
        $(this).addClass('tab-active');

        $('.custom-tab-content').addClass('hidden');
        const target = $('#tab-' + $(this).data('tab'));
        target.removeClass('hidden');

        // Inisialisasi tabel sesuai tab yang diklik
        const tabName = $(this).data('tab');
        
        if (tabName === 'all') initTable('#tableAll');
        else if (tabName === 'donor') initTable('#tableDonor');
        else if (tabName === 'cek') initTable('#tableCek');
        else if (tabName === 'katarak') initTable('#tableKatarak');

        // Adjust kolom setelah tampil
        setTimeout(() => {
            $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
        }, 10);
    });
});
</script>
@endpush