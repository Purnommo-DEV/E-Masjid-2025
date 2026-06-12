<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>@yield('title') - E-Masjid</title>

<!-- Font -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Tailwind CSS CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<!-- DaisyUI CDN -->
<link href="https://cdn.jsdelivr.net/npm/daisyui@4.x/dist/full.min.css" rel="stylesheet" type="text/css" />

{{-- <!-- Alpine JS -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script> --}}

<script src="https://unpkg.com/lucide@latest"></script>

@push('style')