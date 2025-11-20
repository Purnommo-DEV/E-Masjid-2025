<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">

    <!-- Header -->
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
           aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="#!" target="_blank">
            <img src="{{ asset('vendor/material-ui/img/logo-ct.png') }}" class="navbar-brand-img h-100" alt="main_logo">
            <span class="ms-1 font-weight-bold text-white">
                @auth Administrator @else Users @endauth
            </span>
        </a>
    </div>

    <hr class="horizontal light mt-0 mb-2">

    <!-- Menu -->
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav">

            <!-- Dashboard -->
            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-home"></i>
                    <span class="nav-link-text ms-2">Dashboard</span>
                </a>
            </li>

            <!-- Profil -->
            <li class="nav-item">
                <a href="{{ route('admin.profil') }}" class="nav-link text-white {{ request()->routeIs('admin.profil') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-user-circle"></i>
                    <span class="nav-link-text ms-2">Profil</span>
                </a>
            </li>

            <!-- Role & Permission -->
            <li class="nav-item">
                <a href="{{ route('admin.role') }}" class="nav-link text-white {{ request()->routeIs('admin.role') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-user-shield"></i>
                    <span class="nav-link-text ms-2">Role & Permission</span>
                </a>
            </li>

            <!-- Kelola User -->
            <li class="nav-item">
                <a href="{{ route('admin.user') }}" class="nav-link text-white {{ request()->routeIs('admin.user') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-users"></i>
                    <span class="nav-link-text ms-2">Kelola User</span>
                </a>
            </li>

            <!-- Kategori -->
            <li class="nav-item">
                <a href="{{ route('admin.kategori.index') }}" class="nav-link text-white {{ request()->routeIs('admin.kategori*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-tags"></i>
                    <span class="nav-link-text ms-2">Kategori</span>
                </a>
            </li>

            <!-- Berita -->
            <li class="nav-item">
                <a href="{{ route('admin.berita.index') }}" class="nav-link text-white {{ request()->routeIs('admin.berita*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-newspaper"></i>
                    <span class="nav-link-text ms-2">Berita</span>
                </a>
            </li>

            <!-- Acara -->
            <li class="nav-item">
                <a href="{{ route('admin.acara.index') }}" class="nav-link text-white {{ request()->routeIs('admin.acara*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <span class="nav-link-text ms-2">Acara</span>
                </a>
            </li>

            <!-- Galeri -->
            <li class="nav-item">
                <a href="{{ route('admin.galeri.index') }}" class="nav-link text-white {{ request()->routeIs('admin.galeri*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-images"></i>
                    <span class="nav-link-text ms-2">Galeri</span>
                </a>
            </li>

            <!-- Pengumuman -->
            <li class="nav-item">
                <a href="{{ route('admin.pengumuman.index') }}" class="nav-link text-white {{ request()->routeIs('admin.pengumuman*') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-bullhorn"></i>
                    <span class="nav-link-text ms-2">Pengumuman</span>
                </a>
            </li>

            <!-- Manajemen Keuangan -->
            <li class="nav-item">
                <a class="nav-link text-white {{ request()->routeIs('admin.keuangan*') ? 'active' : '' }}"
                   data-bs-toggle="collapse" href="#keuanganMenu" role="button">
                    <i class="nav-icon fas fa-wallet"></i>
                    <span class="nav-link-text ms-2">Manajemen Keuangan</span>
                </a>

                <div class="collapse {{ request()->routeIs('admin.keuangan*') ? 'show' : '' }}" id="keuanganMenu">
                    <ul class="nav ms-4">
                        <li class="nav-item">
                            <a href="{{ route('admin.keuangan.index') }}" class="nav-link text-white {{ request()->routeIs('admin.keuangan.index') ? 'active' : '' }}">
                                <i class="fas fa-tachometer-alt"></i>
                                <span class="ms-2">Dashboard Keuangan</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.keuangan.kotak') }}" class="nav-link text-white {{ request()->routeIs('admin.keuangan.kotak*') ? 'active' : '' }}">
                                <i class="fas fa-donate"></i>
                                <span class="ms-2">Hitung Kotak Infak</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('admin.keuangan.laporan') }}" class="nav-link text-white {{ request()->routeIs('admin.keuangan.laporan*') ? 'active' : '' }}">
                                <i class="fas fa-chart-line"></i>
                                <span class="ms-2">Laporan Keuangan</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <!-- Logout -->
            <li class="nav-item mt-3">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="nav-link text-white bg-transparent border-0 w-100 text-start">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-link-text ms-2">Keluar</span>
                    </button>
                </form>
            </li>

        </ul>
    </div>
</aside>
