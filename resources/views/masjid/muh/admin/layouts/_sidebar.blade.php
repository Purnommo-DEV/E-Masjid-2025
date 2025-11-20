<aside
    class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark"
    id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
            aria-hidden="true" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="#!" target="_blank">
            <img src="{{ asset('vendor/material-ui/img/logo-ct.png') }}" class="navbar-brand-img h-100" alt="main_logo">
            <span class="ms-1 font-weight-bold text-white">@auth Administrator
                @else
                Users @endauth
            </span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse  w-auto " id="sidenav-collapse-main">
        <ul class="navbar-nav">

            <li class="nav-item">
                <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                    </div>
                    <span class="nav-link-text ms-1">Dashboard</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.role') }}" class="nav-link text-white {{ request()->routeIs('admin.role') ? 'active' : '' }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                    </div>
                    <span class="nav-link-text ms-1">Role & Permission</span>
                </a>
            </li>

            <li class="nav-item">
                <a href="{{ route('admin.user') }}" class="nav-link text-white {{ request()->routeIs('admin.user') ? 'active' : '' }}">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                    </div>
                    <span class="nav-link-text ms-1">Kelola User</span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link text-white" href="#">
                    <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
                        <i class="fas fa-arrow-left"></i>
                    </div>
                    <span class="nav-link-text ms-1">Keluar</span>
                </a>
            </li>
        </ul>
    </div>
</aside>
