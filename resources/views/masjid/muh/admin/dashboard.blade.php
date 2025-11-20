@extends('masjid.mrj.admin.layouts.master')

@section('title', 'Dashboard')

@push('styles')
<!-- Tambahan CSS opsional -->
<style>
    .small-box {
        border-radius: 0.75rem;
        box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    }
    .small-box .icon {
        top: 5px;
        opacity: 0.4;
    }
</style>
@endpush

@section('content')
<div class="row">
    <!-- Box Total Users -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3></h3>
                <p>Total Pengguna</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <a href="{{ route('admin.user') }}" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Box Total Roles -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3></h3>
                <p>Role & Permission</p>
            </div>
            <div class="icon">
                <i class="fas fa-shield-alt"></i>
            </div>
            <a href="{{ route('admin.role') }}" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Box Total Masjid -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3></h3>
                <p>Data Masjid</p>
            </div>
            <div class="icon">
                <i class="fas fa-mosque"></i>
            </div>
            <a href="#" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <!-- Box Aktivitas -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3></h3>
                <p>Aktivitas Terakhir</p>
            </div>
            <div class="icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <a href="#" class="small-box-footer">
                Lihat Detail <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

<!-- Bagian Info Tambahan -->
<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="card-title"><i class="fas fa-info-circle"></i> Informasi Sistem</h3>
    </div>
    <div class="card-body">
        <p>Selamat datang di <strong>E-Masjid</strong> {{ masjid() }}👋</p>
        <p>Sistem ini membantu pengelolaan data masjid, pengguna, dan peran (role & permission) secara terintegrasi.</p>
        <ul>
            <li>Kelola pengguna dan hak akses di menu <b>Kelola User</b>.</li>
            <li>Atur peran dan izin di menu <b>Role & Permission</b>.</li>
            <li>Gunakan menu navigasi di sidebar untuk berpindah halaman.</li>
        </ul>
    </div>
</div>
@endsection
