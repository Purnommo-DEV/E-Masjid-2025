<!DOCTYPE html>
<html lang="id">
{{-- 1. HEAD DINAMIS --}}
@include(admin_layout('_head'))

{{-- 2. SWEETALERT --}}
@include('sweetalert::alert')

{{-- 3. VITE --}}
@vite(['resources/js/app.js'])

<body class="g-sidenav-show bg-gray-200">
    @if(Auth::check())
        @include(admin_layout('_sidebar'))
    @endif

    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
        @include(admin_layout('_navbar'))
        <div class="container-fluid py-4">
            @yield('content')
        </div>
    </main>

    {{-- 4. SCRIPT DINAMIS --}}
    @include(admin_layout('_script'))
</body>
</html>