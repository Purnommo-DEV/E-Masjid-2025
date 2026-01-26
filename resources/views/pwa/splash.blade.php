<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="theme-color" content="{{ masjid_config('primary_color') }}">
    <title>{{ masjid_config('name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            margin: 0;
            height: 100dvh;
            background: linear-gradient(135deg, {{ masjid_config('gradient_from') }} 0%, {{ masjid_config('gradient_to') }} 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            font-family: 'Inter', system-ui, sans-serif;
            overflow: hidden;
        }
        .splash-content {
            animation: fadeInScale 1.3s ease-out forwards;
            opacity: 0;
        }
        @keyframes fadeInScale {
            0%   { opacity: 0; transform: scale(0.94); }
            100% { opacity: 1; transform: scale(1); }
        }
        .logo-ring {
            animation: ringPulse 3.5s infinite ease-in-out;
        }
        @keyframes ringPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(var(--tw-gradient-stops), 0.35); }
            50%      { box-shadow: 0 0 0 25px rgba(var(--tw-gradient-stops), 0); }
        }
    </style>
</head>
<body class="overflow-hidden" style="--tw-gradient-stops: {{ str_replace('#', '', masjid_config('primary_color')) }};">

    <div class="splash-content text-center px-6 max-w-md">

        <!-- Logo dengan efek modern -->
        <div class="relative w-full h-full rounded-full bg-white/90 shadow-2xl flex items-center justify-center border-8 border-white overflow-hidden">
            @if (masjid_config('logo_path'))
                <img src="{{ masjid_config('logo_path') }}" alt="Logo {{ masjid_config('name') }}" class="w-4/5 h-4/5 object-contain">
            @else
                {{ masjid_config('logo_emoji') }}
            @endif
        </div>

        <!-- Nama Masjid -->
        <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight mb-3" style="color: {{ masjid_config('primary_color') }};">
            {{ masjid_config('name') }}
        </h1>

        <!-- Jargon -->
        <p class="text-xl sm:text-2xl font-medium text-slate-700 mb-12 leading-relaxed">
            {{ masjid_config('jargon') }}
        </p>

        <!-- Loading spinner DaisyUI -->
        <div class="flex justify-center">
            <span class="loading loading-ring loading-lg" style="color: {{ masjid_config('primary_color') }};"></span>
        </div>

        <!-- Subtext kecil -->
        <p class="mt-10 text-sm text-slate-500 opacity-80">
            {{ masjid_config('subtext') }}
        </p>
    </div>

    <script>
        setTimeout(() => {
            window.location.replace('/');
        }, 3500);
    </script>

</body>
</html>