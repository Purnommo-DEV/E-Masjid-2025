<!DOCTYPE html>
<html lang="id">

<head>
    {{-- HEAD --}}
    @include(admin_layout('_head'))

    {{-- SWEETALERT --}}
    @include('sweetalert::alert')

    {{-- VITE --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-base-200 min-h-screen">

    {{-- SIDEBAR --}}
    @if(Auth::check())
        @include(admin_layout('_sidebar'))
    @endif

    {{-- MAIN --}}
    <main class="main-content min-h-screen transition-all lg:ml-64">

        @include(admin_layout('_navbar'))

        <div class="container mx-auto px-4 py-4">
            @yield('content')
        </div>
    </main>

    {{-- SCRIPT --}}
    <script> lucide.createIcons(); </script>
    @include(admin_layout('_script'))

</body>
</html>
