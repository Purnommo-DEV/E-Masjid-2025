@extends('masjid.master-guest')

@section('title', 'Evaluasi Qurban ' . ($data['subtitle'] ?? '1447 H'))

@push('head')
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">

    <!-- SEO Meta Tags -->
    <title>Kritik, Saran, dan Evaluasi Qurban {{ $data['subtitle'] ?? '1447 H' }} - Masjid Raudhatul Jannah</title>
    <meta name="description" content="Berikan kritik, saran, dan evaluasi untuk pelaksanaan qurban {{ $data['subtitle'] ?? '1447 H' }} di Masjid Raudhatul Jannah Taman Cipulir Estate. Partisipasi Anda untuk perbaikan layanan qurban ke depan.">
    <meta name="keywords" content="evaluasi qurban, kritik qurban, saran qurban, masjid raudhatul jannah, qurban 1447 H, idul adha, shohibul qurban, TCE">
    <meta name="author" content="Masjid Raudhatul Jannah TCE">
    <meta name="robots" content="index, follow">
    <meta name="language" content="Indonesian">

    <!-- Open Graph / Facebook / WhatsApp -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Evaluasi Qurban {{ $data['subtitle'] ?? '1447 H' }} - Masjid Raudhatul Jannah">
    <meta property="og:description" content="Berikan saran dan masukan untuk pelaksanaan qurban di Masjid Raudhatul Jannah TCE. Survei hanya 5 menit!">
    <meta property="og:image" content="{{ $profil->logo_url ?? asset('assets/logo-masjid.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="id_ID">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Evaluasi Qurban {{ $data['subtitle'] ?? '1447 H' }} - Masjid Raudhatul Jannah">
    <meta name="twitter:description" content="Berikan saran dan masukan untuk pelaksanaan qurban di Masjid Raudhatul Jannah TCE.">
    <meta name="twitter:image" content="{{ $profil->logo_url ?? asset('assets/logo-masjid.png') }}">

    <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="{{ $profil->logo_url ?? asset('assets/logo-masjid.png') }}">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">

    
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #f0fdf4 0%, #d1fae5 100%);
            min-height: 100vh;
        }
        
        /* Rating Bintang */
        .rating-group {
            display: flex;
            flex-direction: row-reverse;
            justify-content: center;
            gap: 0.75rem;
        }
        
        @media (max-width: 640px) {
            .rating-group {
                gap: 0.5rem;
            }
        }
        
        .rating-input {
            display: none;
        }
        
        .rating-label {
            cursor: pointer;
            font-size: 2rem;
            color: #cbd5e1;
            transition: all 0.2s ease;
        }
        
        @media (max-width: 640px) {
            .rating-label {
                font-size: 1.75rem;
            }
        }
        
        .rating-label:hover,
        .rating-label:hover ~ .rating-label {
            color: #f59e0b !important;
            transform: scale(1.1);
        }
        
        .rating-input:checked ~ .rating-label {
            color: #fbbf24 !important;
            text-shadow: 0 0 4px rgba(251, 191, 36, 0.4);
        }
        
        /* Input Focus */
        .input-modern {
            transition: all 0.2s ease;
            border: 1.5px solid #e5e7eb;
            font-size: 15px;
        }
        
        .input-modern:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
            outline: none;
        }
        
        /* Radio & Checkbox */
        .radio-custom {
            appearance: none;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid #cbd5e1;
            border-radius: 50%;
            transition: all 0.2s ease;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .radio-custom:checked {
            border-color: #10b981;
            background-color: #10b981;
            box-shadow: inset 0 0 0 3px white;
        }
        
        .checkbox-custom {
            appearance: none;
            width: 1.25rem;
            height: 1.25rem;
            border: 2px solid #cbd5e1;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .checkbox-custom:checked {
            background-color: #10b981;
            border-color: #10b981;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='white'%3E%3Cpath d='M20 6L9 17L4 12' stroke='white' stroke-width='2' fill='none'/%3E%3C/svg%3E");
            background-size: 0.875rem;
            background-position: center;
            background-repeat: no-repeat;
        }
        
        /* Button Submit */
        .btn-submit {
            transition: all 0.3s ease;
        }
        
        .btn-submit:active {
            transform: scale(0.98);
        }
        
        /* Spinner */
        .spinner {
            border: 2px solid #f3f4f6;
            border-top: 2px solid #10b981;
            border-radius: 50%;
            width: 1rem;
            height: 1rem;
            animation: spin 0.6s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Section Styling */
        .section-1 {
            background: linear-gradient(135deg, #f8fafc 0%, #ecfdf5 100%);
            border-radius: 1rem;
            padding: 1.25rem;
            border-left: 4px solid #10b981;
            transition: all 0.3s ease;
        }
        
        .section-2 {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 30%, #f8fafc 100%);
            border-radius: 1rem;
            padding: 1.25rem;
            border-left: 4px solid #8b5cf6;
            transition: all 0.3s ease;
        }
        
        .section-3 {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 30%, #f8fafc 100%);
            border-radius: 1rem;
            padding: 1.25rem;
            border-left: 4px solid #3b82f6;
            transition: all 0.3s ease;
        }
        
        .section-card {
            transition: all 0.3s ease;
        }
        
        .section-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Checkbox Item */
        .checkbox-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: white;
            border-radius: 0.75rem;
            border: 1.5px solid #e5e7eb;
            transition: all 0.2s ease;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
        }
        
        .checkbox-item:hover {
            border-color: #10b981;
            background: #f0fdf4;
            transform: translateY(-1px);
        }
        
        .checkbox-item.selected {
            background: #ecfdf5;
            border-color: #10b981;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
        }
        
        /* Rating Container */
        .rating-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 640px) {
            .rating-wrapper {
                flex-direction: column;
                gap: 0.75rem;
            }
        }
        
        .rating-label-left, .rating-label-right {
            font-size: 0.8rem;
            font-weight: 600;
            color: #4b5563;
            min-width: 80px;
        }
        
        /* Progress Indicator */
        .progress-step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        @media (max-width: 480px) {
            .progress-step span:last-child {
                display: none;
            }
        }
        
        /* Radio Card */
        .radio-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            border-radius: 1rem;
            border-width: 2px;
            cursor: pointer;
            transition: all 0.2s ease;
            background: white;
        }
        
        .radio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
        }
        
        /* Textarea */
        .textarea-saran {
            font-size: 14px;
            line-height: 1.5;
            min-height: 90px;
            background: white;
        }
        
        .textarea-informasi {
            font-size: 14px;
            line-height: 1.5;
            min-height: 90px;
            background: white;
        }
        
        /* Error border */
        .is-invalid {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }
        
        .error-text {
            color: #ef4444;
            font-size: 0.7rem;
            margin-top: 0.25rem;
        }
        
        /* Rating Error */
        .rating-error {
            border: 2px solid #ef4444 !important;
            border-radius: 0.75rem;
            padding: 0.5rem;
            background-color: #fef2f2;
        }
        
        .section-icon {
            width: 2rem;
            height: 2rem;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        ::-webkit-scrollbar {
            width: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 3px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #10b981;
            border-radius: 3px;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in {
            animation: fadeInUp 0.4s ease forwards;
        }
        
        .rating-card {
            transition: all 0.2s ease;
        }
        
        .rating-card:hover {
            transform: translateX(4px);
        }
        
        .rencana-group {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.75rem;
        }
        
        @media (max-width: 640px) {
            .rencana-group {
                gap: 0.5rem;
            }
            .rencana-group label {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
                font-size: 0.8rem;
            }
        }
        
        @media (max-width: 480px) {
            .rencana-group {
                gap: 0.35rem;
            }
            .rencana-group label {
                padding-left: 0.35rem;
                padding-right: 0.35rem;
                font-size: 0.7rem;
            }
        }
        
        .saran-card {
            background: white;
            border-radius: 0.75rem;
            padding: 0.75rem;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }
        
        .saran-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
    </style>
@endpush

@section('content')
    <div class="max-w-3xl mx-auto py-5 sm:py-7 px-4 sm:px-5">
        
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden fade-in">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-800 via-emerald-700 to-emerald-600 px-5 py-6 sm:py-8 text-center relative overflow-hidden">
                @if($profil->logo_url ?? false)
                <div class="absolute inset-0 opacity-10 flex items-center justify-center pointer-events-none">
                    <img src="{{ $profil->logo_url }}" alt="Logo Masjid" class="w-32 h-32 sm:w-48 sm:h-48 object-contain opacity-30">
                </div>
                @else
                <div class="absolute inset-0 opacity-5">
                    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg" patternUnits="userSpaceOnUse" width="40" height="40">
                        <circle cx="20" cy="20" r="2" fill="white"/>
                    </svg>
                </div>
                @endif
                <div class="relative">
                    <div class="inline-flex items-center justify-center bg-white/20 rounded-full p-3 mb-3 backdrop-blur-sm">
                        <span class="text-3xl sm:text-4xl">🕌</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold text-white">
                        {{ $data['hero_title'] }} <span class="text-emerald-200">{{ $data['subtitle'] }}</span>
                    </h1>
                    <p class="text-emerald-100 text-sm mt-1">{{ $data['hero_subtitle'] }}</p>
                </div>
            </div>

            <!-- Info Badge -->
            <div class="px-5 pt-4">
                <div class="bg-gradient-to-r from-emerald-50 to-emerald-100 rounded-xl px-4 py-2.5 text-emerald-700 text-sm flex items-center justify-center gap-2 shadow-sm">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="font-medium">Evaluasi untuk Masjid Raudhatul Jannah (MRJ) TCE</span>
                </div>
            </div>

            <!-- Progress Indicator -->
            <div class="px-5 py-3 bg-gray-50 border-b">
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <div class="progress-step">
                        <span class="w-6 h-6 bg-emerald-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-md">1</span>
                        <span class="text-sm font-medium">Identitas</span>
                    </div>
                    <div class="h-px flex-1 bg-gray-400 mx-2"></div>
                    <div class="progress-step">
                        <span class="w-6 h-6 bg-purple-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-md">2</span>
                        <span class="text-sm">Penilaian</span>
                    </div>
                    <div class="h-px flex-1 bg-gray-400 mx-2"></div>
                    <div class="progress-step">
                        <span class="w-6 h-6 bg-blue-500 text-white rounded-full flex items-center justify-center text-xs font-bold shadow-md">3</span>
                        <span class="text-sm">Saran</span>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <form id="evaluasiForm" class="p-5 sm:p-6 space-y-6">
                @csrf
                <input type="hidden" name="tahun_hijriah" value="{{ $tahun ?? '1447 H' }}">
                <input type="hidden" name="tempat_qurban" value="masjid">

                <!-- SECTION 1: IDENTITAS -->
                <div class="section-card section-1">
                    <div class="flex items-center gap-3 mb-4 pb-2 border-b border-emerald-200">
                        <div class="section-icon bg-emerald-100">
                            <span class="text-emerald-600 font-bold text-sm">1</span>
                        </div>
                        <h2 class="text-black font-bold text-emerald-800">Identitas & Informasi</h2>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <input type="text" name="nama_shohibul" id="nama_shohibul" 
                                    class="input-modern w-full pl-9 pr-3 py-2.5 rounded-lg bg-white focus:bg-white text-black shadow-sm"
                                    placeholder="Masukkan nama lengkap">
                            </div>
                            <div class="error-text nama_shohibul_error hidden"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Jenis Hewan Qurban <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label class="radio-card border-2 sapi-card">
                                    <input type="radio" name="jenis_hewan" value="sapi" class="radio-custom mb-1">
                                    <span class="text-2xl">🐃</span>
                                    <span class="font-medium text-gray-700 text-sm">Sapi</span>
                                </label>
                                <label class="radio-card border-2 kambing-card">
                                    <input type="radio" name="jenis_hewan" value="kambing" class="radio-custom mb-1">
                                    <span class="text-2xl">🐐</span>
                                    <span class="font-medium text-gray-700 text-sm">Kambing</span>
                                </label>
                            </div>
                            <div class="error-text jenis_hewan_error hidden"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Dari mana informasi qurban? 
                                <span class="text-emerald-600 text-xs font-normal ml-1">(bisa pilih lebih dari satu)</span>
                            </label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="checkbox-item">
                                    <input type="checkbox" name="sumber_info" value="WhatsApp" class="checkbox-custom sumber_checkbox">
                                    <span class="font-medium text-gray-700 text-sm">WhatsApp</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="sumber_info" value="Info Tetangga" class="checkbox-custom sumber_checkbox">
                                    <span class="font-medium text-gray-700 text-sm">Info Tetangga</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="sumber_info" value="Spanduk/Banner" class="checkbox-custom sumber_checkbox">
                                    <span class="font-medium text-gray-700 text-sm">Spanduk / Banner</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="sumber_info" value="Flyer" class="checkbox-custom sumber_checkbox">
                                    <span class="font-medium text-gray-700 text-sm">Flyer</span>
                                </label>
                                <label class="checkbox-item">
                                    <input type="checkbox" name="sumber_info" value="Pengumuman Masjid" class="checkbox-custom sumber_checkbox">
                                    <span class="font-medium text-gray-700 text-sm">Pengumuman Masjid</span>
                                </label>
                                <label class="checkbox-item" id="sumberLainnyaLabel">
                                    <input type="checkbox" name="sumber_info" value="Lainnya" class="checkbox-custom sumber_checkbox" id="sumberLainnyaCheckbox">
                                    <span class="font-medium text-gray-700 text-sm">Lainnya</span>
                                </label>
                            </div>
                            <div id="sumberLainnyaInput" class="mt-3 hidden">
                                <input type="text" name="sumber_info_lainnya" id="sumber_info_lainnya" placeholder="Sebutkan sumber informasinya..." 
                                    class="input-modern w-full px-4 py-2.5 rounded-lg bg-white text-black shadow-sm">
                            </div>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Masukan untuk penyebaran informasi qurban
                        </label>
                        <textarea name="masukan_penyebaran_informasi" rows="3" 
                            class="input-modern w-full px-3 py-3 rounded-lg bg-white text-black shadow-sm textarea-informasi" 
                            placeholder="Contoh: Apakah informasi sudah mudah diakses? Media apa yang perlu ditambahkan? Apakah jadwal sosialisasi sudah tepat?..."></textarea>
                    </div>
                </div>

                <!-- SECTION 2: PENILAIAN (Tanpa required attribute) -->
                <div class="section-card section-2">
                    <div class="flex items-center gap-3 mb-4 pb-2 border-b border-purple-200">
                        <div class="section-icon bg-purple-100">
                            <span class="text-purple-600 font-bold text-sm">2</span>
                        </div>
                        <h2 class="text-black font-bold text-purple-800">Rating & Penilaian</h2>
                    </div>

                    <div class="space-y-4">
                        @php
                            $ratings = [
                                'pendaftaran' => ['title' => 'Pelayanan Pendaftaran & Pembayaran', 'desc' => 'Proses pendaftaran, pembayaran, dan komunikasi', 'id' => 'rating-pendaftaran'],
                                'pelaksanaan' => ['title' => 'Penyembelihan & Pengemasan', 'desc' => 'Proses penyembelihan, pencacahan, dan pengemasan', 'id' => 'rating-pelaksanaan'],
                                'distribusi' => ['title' => 'Distribusi Daging Qurban', 'desc' => 'Pembagian dan penyaluran daging ke penerima', 'id' => 'rating-distribusi'],
                                'kualitas_hewan' => ['title' => 'Kualitas Hewan Qurban', 'desc' => 'Kesehatan, berat, dan kualitas daging', 'id' => 'rating-kualitas']
                            ];
                        @endphp

                        @foreach($ratings as $key => $rating)
                        <div class="bg-white/70 rounded-xl p-3 shadow-sm rating-card" id="{{ $rating['id'] }}">
                            <div class="mb-2">
                                <label class="font-semibold text-gray-800 text-sm">{{ $rating['title'] }} <span class="text-red-500">*</span></label>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $rating['desc'] }}</p>
                            </div>
                            
                            <div class="rating-wrapper">
                                <span class="rating-label-left">Tidak Puas</span>
                                <div class="rating-group">
                                    <input type="radio" name="rating_{{ $key }}" value="5" id="{{ $key }}5" class="rating-input">
                                    <label for="{{ $key }}5" class="rating-label" title="Sangat Puas (5)">★</label>
                                    <input type="radio" name="rating_{{ $key }}" value="4" id="{{ $key }}4" class="rating-input">
                                    <label for="{{ $key }}4" class="rating-label" title="Puas (4)">★</label>
                                    <input type="radio" name="rating_{{ $key }}" value="3" id="{{ $key }}3" class="rating-input">
                                    <label for="{{ $key }}3" class="rating-label" title="Cukup (3)">★</label>
                                    <input type="radio" name="rating_{{ $key }}" value="2" id="{{ $key }}2" class="rating-input">
                                    <label for="{{ $key }}2" class="rating-label" title="Kurang (2)">★</label>
                                    <input type="radio" name="rating_{{ $key }}" value="1" id="{{ $key }}1" class="rating-input">
                                    <label for="{{ $key }}1" class="rating-label" title="Tidak Puas (1)">★</label>
                                </div>
                                <span class="rating-label-right">Sangat Puas</span>
                            </div>
                            
                            <textarea name="saran_{{ $key }}" rows="3" class="text-black input-modern w-full mt-3 px-3 py-2 rounded-lg bg-white textarea-saran shadow-sm" 
                                placeholder="Masukan & saran untuk {{ strtolower($rating['title']) }}..."></textarea>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- SECTION 3: SARAN & MASUKAN -->
                <div class="section-card section-3">
                    <div class="flex items-center gap-3 mb-4 pb-2 border-b border-blue-200">
                        <div class="section-icon bg-blue-100">
                            <span class="text-blue-600 font-bold text-sm">3</span>
                        </div>
                        <h2 class="text-black font-bold text-blue-800">Saran & Masukan</h2>
                    </div>

                    <div class="space-y-4">
                        <div id="rencana-qurban">
                            <label class="block font-semibold text-gray-800 text-sm mb-2">
                                Rencana Qurban Tahun Depan di <p class="text-emerald-800">Masjid Raudhotul Jannah TCE?</p> <span class="text-red-500">*</span>
                            </label>
                            <div class="rencana-group">
                                <label class="flex items-center justify-center gap-1.5 py-2 rounded-lg border bg-white cursor-pointer hover:bg-emerald-50 transition-all text-sm shadow-sm flex-1 rencana-option">
                                    <input type="radio" name="rencana_qurban" value="ya" class="radio-custom">
                                    <span class="text-gray-700">Ya</span>
                                </label>
                                <label class="flex items-center justify-center gap-1.5 py-2 rounded-lg border bg-white cursor-pointer hover:bg-emerald-50 transition-all text-sm shadow-sm flex-1 rencana-option">
                                    <input type="radio" name="rencana_qurban" value="mungkin" class="radio-custom">
                                    <span class="text-gray-700">Mungkin</span>
                                </label>
                                <label class="flex items-center justify-center gap-1.5 py-2 rounded-lg border bg-white cursor-pointer hover:bg-emerald-50 transition-all text-sm shadow-sm flex-1 rencana-option">
                                    <input type="radio" name="rencana_qurban" value="tidak" class="radio-custom">
                                    <span class="text-gray-700">Tidak</span>
                                </label>
                            </div>
                            <div class="error-text rencana_qurban_error hidden"></div>
                        </div>

                        <div class="saran-card">
                            <label class="flex items-center gap-2 font-semibold text-emerald-800 text-sm mb-2">
                                <span>✅</span> Hal yang sudah baik & perlu dipertahankan
                            </label>
                            <textarea name="hal_baik" rows="3" class="text-black input-modern w-full px-3 py-2 rounded-lg bg-gray-50 textarea-saran" 
                                placeholder="Tuliskan apresiasi dan hal-hal positif dari pelaksanaan qurban tahun ini..."></textarea>
                        </div>

                        <div class="saran-card">
                            <label class="flex items-center gap-2 font-semibold text-orange-800 text-sm mb-2">
                                <span>🔧</span> Hal yang perlu diperbaiki
                            </label>
                            <textarea name="hal_perbaikan" rows="3" class="text-black input-modern w-full px-3 py-2 rounded-lg bg-gray-50 textarea-saran" 
                                placeholder="Kritik dan saran untuk perbaikan ke depannya..."></textarea>
                        </div>

                        <div class="saran-card">
                            <label class="flex items-center gap-2 font-semibold text-gray-800 text-sm mb-2">
                                <span>💡</span> Saran Tambahan
                            </label>
                            <textarea name="saran_tambahan" rows="2" class="text-black input-modern w-full px-3 py-2 rounded-lg bg-gray-50 textarea-saran" 
                                placeholder="Saran lainnya untuk kemajuan Masjid Raudhatul Jannah..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center pt-2">
                    <button type="submit" id="submitBtn" class="btn-submit bg-gradient-to-r from-emerald-600 to-emerald-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:shadow-xl transition-all w-full sm:w-auto text-black">
                        <span class="flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Kirim Evaluasi
                        </span>
                    </button>
                    <p class="text-xs text-gray-400 mt-3">Jazakumullah khairan katsiran atas partisipasinya</p>
                </div>
            </form>

            <div class="bg-gray-50 px-5 py-4 text-center border-t">
                <p class="text-xs text-gray-500">Wassalamu'alaikum warahmatullahi wabarakatuh</p>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Checkbox styling
            $('.checkbox-item').each(function() {
                const checkbox = $(this).find('input[type="checkbox"]');
                const updateHighlight = () => {
                    if (checkbox.prop('checked')) {
                        $(this).addClass('selected');
                    } else {
                        $(this).removeClass('selected');
                    }
                };
                checkbox.on('change', updateHighlight);
                updateHighlight();
                $(this).on('click', function(e) {
                    if (e.target !== checkbox[0] && !checkbox.is(e.target)) {
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        updateHighlight();
                        checkbox.trigger('change');
                    }
                });
            });

            // Show/hide "Lainnya"
            $('#sumberLainnyaCheckbox').on('change', function() {
                if ($(this).prop('checked')) {
                    $('#sumberLainnyaInput').removeClass('hidden').hide().fadeIn(200);
                } else {
                    $('#sumberLainnyaInput').fadeOut(200, function() {
                        $(this).addClass('hidden');
                        $('#sumber_info_lainnya').val('');
                    });
                }
            });

            // Radio card styling
            const updateHewanCard = () => {
                if ($('input[name="jenis_hewan"][value="sapi"]').prop('checked')) {
                    $('.sapi-card').addClass('border-emerald-500 bg-emerald-50 shadow-md');
                    $('.kambing-card').removeClass('border-emerald-500 bg-emerald-50 shadow-md').addClass('border-gray-200');
                } else if ($('input[name="jenis_hewan"][value="kambing"]').prop('checked')) {
                    $('.kambing-card').addClass('border-emerald-500 bg-emerald-50 shadow-md');
                    $('.sapi-card').removeClass('border-emerald-500 bg-emerald-50 shadow-md').addClass('border-gray-200');
                }
            };
            $('input[name="jenis_hewan"]').on('change', updateHewanCard);
            updateHewanCard();

            function scrollToElement(element, offset = 120) {
                $('html, body').animate({
                    scrollTop: $(element).offset().top - offset
                }, 500);
            }

            function highlightRatingError(ratingId) {
                $('#' + ratingId).addClass('rating-error');
                setTimeout(() => {
                    $('#' + ratingId).removeClass('rating-error');
                }, 3000);
            }

            // Submit dengan validasi manual (tanpa required attribute)
            $('#evaluasiForm').on('submit', function(e) {
                e.preventDefault();
                
                $('.error-text').addClass('hidden').empty();
                $('.input-modern').removeClass('is-invalid');
                $('.rating-card').removeClass('rating-error');
                
                let hasError = false;
                let firstErrorElement = null;
                let errorMessages = [];
                
                // Validasi Nama
                const nama = $('#nama_shohibul').val().trim();
                if (!nama) {
                    hasError = true;
                    errorMessages.push('• Nama lengkap harus diisi');
                    $('#nama_shohibul').addClass('is-invalid');
                    if (!firstErrorElement) firstErrorElement = '#nama_shohibul';
                }
                
                // Validasi Jenis Hewan
                const jenisHewan = $('input[name="jenis_hewan"]:checked').val();
                if (!jenisHewan) {
                    hasError = true;
                    errorMessages.push('• Jenis hewan qurban harus dipilih');
                    if (!firstErrorElement) firstErrorElement = '.sapi-card';
                }
                
                // Validasi Rating Pendaftaran
                const ratingPendaftaran = $('input[name="rating_pendaftaran"]:checked').val();
                if (!ratingPendaftaran) {
                    hasError = true;
                    errorMessages.push('• Rating pelayanan pendaftaran harus dipilih');
                    highlightRatingError('rating-pendaftaran');
                    if (!firstErrorElement) firstErrorElement = '#rating-pendaftaran';
                }
                
                // Validasi Rating Pelaksanaan
                const ratingPelaksanaan = $('input[name="rating_pelaksanaan"]:checked').val();
                if (!ratingPelaksanaan) {
                    hasError = true;
                    errorMessages.push('• Rating penyembelihan & pengemasan harus dipilih');
                    highlightRatingError('rating-pelaksanaan');
                    if (!firstErrorElement) firstErrorElement = '#rating-pelaksanaan';
                }
                
                // Validasi Rating Distribusi
                const ratingDistribusi = $('input[name="rating_distribusi"]:checked').val();
                if (!ratingDistribusi) {
                    hasError = true;
                    errorMessages.push('• Rating distribusi daging harus dipilih');
                    highlightRatingError('rating-distribusi');
                    if (!firstErrorElement) firstErrorElement = '#rating-distribusi';
                }
                
                // Validasi Rating Kualitas Hewan
                const ratingKualitas = $('input[name="rating_kualitas_hewan"]:checked').val();
                if (!ratingKualitas) {
                    hasError = true;
                    errorMessages.push('• Rating kualitas hewan harus dipilih');
                    highlightRatingError('rating-kualitas');
                    if (!firstErrorElement) firstErrorElement = '#rating-kualitas';
                }
                
                // Validasi Rencana Qurban
                const rencanaQurban = $('input[name="rencana_qurban"]:checked').val();
                if (!rencanaQurban) {
                    hasError = true;
                    errorMessages.push('• Rencana qurban tahun depan harus dipilih');
                    if (!firstErrorElement) firstErrorElement = '#rencana-qurban';
                }
                
                if (hasError) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Data Belum Lengkap',
                        html: `<div style="font-size: 13px; line-height: 1.7; text-align: left; color: #4b5563;">
                                    ${errorMessages.map(msg => `<span style="display: block; margin-bottom: 6px;">${msg}</span>`).join('')}
                            </div>`,
                        confirmButtonColor: '#10b981',
                        confirmButtonText: 'Ok, Saya perbaiki'
                    });
                    if (firstErrorElement) {
                        scrollToElement(firstErrorElement);
                    }
                    return;
                }
                
                // Ambil nilai checkbox sumber_info
                let sumberInfoValues = [];
                $('input[name="sumber_info"]:checked').each(function() {
                    sumberInfoValues.push($(this).val());
                });
                if ($('#sumberLainnyaCheckbox').prop('checked') && $('#sumber_info_lainnya').val()) {
                    sumberInfoValues.push($('#sumber_info_lainnya').val());
                }
                let sumberInfoString = sumberInfoValues.join(', ');
                
                // Kumpulkan data
                let formData = {
                    _token: $('input[name="_token"]').val(),
                    tahun_hijriah: $('input[name="tahun_hijriah"]').val(),
                    tempat_qurban: $('input[name="tempat_qurban"]').val(),
                    nama_shohibul: nama,
                    jenis_hewan: jenisHewan,
                    sumber_info: sumberInfoString,
                    rencana_qurban: rencanaQurban,
                    hal_baik: $('textarea[name="hal_baik"]').val(),
                    hal_perbaikan: $('textarea[name="hal_perbaikan"]').val(),
                    saran_tambahan: $('textarea[name="saran_tambahan"]').val(),
                    rating_pendaftaran: ratingPendaftaran,
                    rating_pelaksanaan: ratingPelaksanaan,
                    rating_distribusi: ratingDistribusi,
                    rating_kualitas_hewan: ratingKualitas,
                    saran_pendaftaran: $('textarea[name="saran_pendaftaran"]').val(),
                    saran_pelaksanaan: $('textarea[name="saran_pelaksanaan"]').val(),
                    saran_distribusi: $('textarea[name="saran_distribusi"]').val(),
                    saran_kualitas_hewan: $('textarea[name="saran_kualitas_hewan"]').val()
                };
                
                const $btn = $('#submitBtn');
                const originalText = $btn.html();
                $btn.prop('disabled', true);
                $btn.html('<div class="spinner"></div> Mengirim...');
                
                $.ajax({
                    url: '{{ route("evaluasi-qurban.guest.store") }}',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#evaluasiForm')[0].reset();
                            $('.checkbox-item').removeClass('selected');
                            $('#sumberLainnyaInput').addClass('hidden');
                            $('#sumber_info_lainnya').val('');
                            updateHewanCard();
                            
                            Swal.fire({
                                icon: 'success',
                                title: '✅ Evaluasi Berhasil Dikirim!',
                                html: `<p class="text-black">${response.message || 'Terima kasih atas partisipasi dan masukannya!'}</p>
                                    <p class="text-sm text-emerald-600 mt-2">Semoga ibadah qurban kita diterima Allah SWT.</p>`,
                                confirmButtonColor: '#10b981',
                                confirmButtonText: 'Terima Kasih',
                                backdrop: true,
                                allowOutsideClick: false
                            }).then(() => {
                                $('html, body').animate({ scrollTop: 0 }, 300);
                            });
                        }
                    },
                    error: function(xhr) {
                        let errorMessage = 'Terjadi kesalahan. Silakan coba lagi.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            const errors = xhr.responseJSON.errors;
                            let errorList = '';
                            $.each(errors, function(key, messages) {
                                errorList += `<li class="text-left">• ${messages[0]}</li>`;
                            });
                            Swal.fire({
                                icon: 'error',
                                title: 'Validasi Gagal',
                                html: `<ul class="text-left">${errorList}</ul>`,
                                confirmButtonColor: '#ef4444',
                                confirmButtonText: 'Ok'
                            });
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON.message,
                                confirmButtonColor: '#ef4444'
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: errorMessage,
                                confirmButtonColor: '#ef4444'
                            });
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false);
                        $btn.html(originalText);
                    }
                });
            });
        });
    </script>
@endpush