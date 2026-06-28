<!DOCTYPE html>
<html lang="id">
<head>
    @include(admin_layout('_head'))
    @include('sweetalert::alert')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="bg-base-200 min-h-screen overflow-x-hidden"
    x-data="{
        sidebarOpen: false,
    }"
    @keydown.escape.window="sidebarOpen = false"
    :class="{ 'overflow-hidden lg:overflow-x-hidden lg:overflow-y-auto': sidebarOpen }"
>

    @if(Auth::check())
        @include(admin_layout('_sidebar'))
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity.duration.200ms
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-slate-950/55 backdrop-blur-[1px] lg:hidden"
            aria-hidden="true"
        ></div>
    @endif

    <main class="main-content min-h-screen w-full min-w-0 transition-all duration-300 lg:ml-64 lg:w-auto">
        @include(admin_layout('_navbar'))
        <div class="admin-content container mx-auto w-full max-w-full min-w-0 px-3 py-4 sm:px-4 lg:px-6">
            @yield('content')
        </div>
    </main>

    <script> lucide.createIcons(); </script>
    @include(admin_layout('_script'))
</body>
</html>
