<!DOCTYPE html>
<html lang="id">
<head>
    @include(admin_layout('_head'))
    @include('sweetalert::alert')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<!-- PINDAHKAN x-data KE SINI (scope global) -->
<body class="bg-base-200 min-h-screen" x-data="{ sidebarOpen: window.innerWidth >= 1024 }">

    @if(Auth::check())
        @include(admin_layout('_sidebar'))
    @endif

    <main class="main-content min-h-screen transition-all duration-300 lg:ml-64">
        @include(admin_layout('_navbar'))
        <div class="container mx-auto px-4 py-4">
            @yield('content')
        </div>
    </main>

    <script> lucide.createIcons(); </script>
    @include(admin_layout('_script'))
</body>
</html>