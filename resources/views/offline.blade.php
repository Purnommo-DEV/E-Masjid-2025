<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline - E-Masjid</title>
    <link rel="stylesheet" href="{{ asset('build/assets/app.css') }}">
</head>
<body class="bg-emerald-50 text-emerald-900 min-h-screen flex items-center justify-center text-center p-6">
    <div>
        <h1 class="text-4xl font-bold mb-4">Koneksi Hilang</h1>
        <p class="text-lg mb-6">Mohon cek internet Anda. E-Masjid akan otomatis sync saat online lagi.</p>
        <button onclick="location.reload()" class="btn bg-emerald-600 text-white px-8 py-4 rounded-full text-xl">
            Coba Lagi
        </button>
    </div>
</body>
</html>