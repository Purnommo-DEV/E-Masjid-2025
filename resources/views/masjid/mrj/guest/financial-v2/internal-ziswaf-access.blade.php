<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive">
    <title>Dashboard Ringkasan ZISWAF | Masjid Raudhotul Jannah</title>
    <meta name="description" content="Ringkasan penerimaan, penyaluran, dan posisi dana ZISWAF untuk monitoring internal DKM.">
    <link rel="canonical" href="{{ secure_url(route('internal.ziswaf.preview', [], false)) }}">

    <meta property="og:title" content="Dashboard Ringkasan ZISWAF | Masjid Raudhotul Jannah">
    <meta property="og:description" content="Ringkasan penerimaan, penyaluran, dan posisi dana ZISWAF untuk monitoring internal DKM.">
    <meta property="og:image" content="{{ secure_asset('pwa/mrj-logo.png') }}">
    <meta property="og:image:secure_url" content="{{ secure_asset('pwa/mrj-logo.png') }}">
    <meta property="og:image:type" content="image/png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:image:alt" content="Logo Masjid Raudhotul Jannah">
    <meta property="og:url" content="{{ secure_url(route('internal.ziswaf.preview', [], false)) }}">
    <meta property="og:type" content="website">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Dashboard Ringkasan ZISWAF | Masjid Raudhotul Jannah">
    <meta name="twitter:description" content="Ringkasan penerimaan, penyaluran, dan posisi dana ZISWAF untuk monitoring internal DKM.">
    <meta name="twitter:image" content="{{ secure_asset('pwa/mrj-logo.png') }}">
    <meta name="twitter:image:alt" content="Logo Masjid Raudhotul Jannah">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900 antialiased">
<main class="flex min-h-screen items-center justify-center px-4 py-10 sm:px-6">
    <section class="w-full max-w-xl overflow-hidden rounded-3xl border border-emerald-100 bg-white shadow-xl shadow-emerald-950/10">
        <div class="bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-800 px-6 py-8 text-center text-white sm:px-10 sm:py-10">
            <img src="{{ asset('pwa/mrj-logo.png') }}" width="112" height="112" alt="Logo Masjid Raudhotul Jannah" class="mx-auto h-28 w-28 rounded-full bg-white object-contain p-1 shadow-lg">
            <p class="mt-5 text-xs font-bold uppercase tracking-[.2em] text-emerald-200">Monitoring Internal DKM</p>
            <h1 class="mt-2 text-2xl font-bold sm:text-3xl">Dashboard Ringkasan ZISWAF</h1>
            <p class="mt-3 text-sm leading-6 text-emerald-100">Ringkasan penerimaan, penyaluran, dan posisi dana ZISWAF Masjid Raudhotul Jannah.</p>
        </div>
        <div class="space-y-5 px-6 py-7 sm:px-10 sm:py-8">
            @if($accessToken !== null)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950">
                    Halaman ini belum membuka dashboard. Tekan tombol di bawah untuk memvalidasi akses dan membentuk sesi internal terbatas.
                </div>
                <form method="post" action="{{ route('internal.ziswaf.exchange') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $accessToken }}">
                    <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-emerald-700 px-5 text-center text-sm font-bold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">Buka Dashboard ZISWAF</button>
                </form>
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                    Dashboard bersifat internal dan tidak menampilkan data keuangan pada halaman ini. Gunakan tautan akses resmi yang diberikan pengelola DKM.
                </div>
            @endif
            <p class="text-center text-xs leading-5 text-slate-500">Akses dashboard bersifat read-only, terbatas, dan dapat dicabut oleh pengelola.</p>
        </div>
    </section>
</main>
</body>
</html>
