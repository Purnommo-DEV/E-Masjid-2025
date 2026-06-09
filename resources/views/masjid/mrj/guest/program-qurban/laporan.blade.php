{{-- resources/views/masjid/mrj/guest/program-qurban/laporan.blade.php --}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Laporan Qurban {{ $heroData['subtitle'] }} | {{ $heroData['masjid'] }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #fef9e6 0%, #f0f7f0 20%, #e8f0ea 40%, #fef5e8 60%, #f0f4f0 80%, #fff8e7 100%);
            background-size: 400% 400%;
            animation: softMorphGradient 24s ease infinite;
            position: relative;
        }
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Cpath fill='%23064e3b' fill-opacity='0.03' d='M50,10 L65,30 L35,30 Z M20,40 L80,40 L80,45 L20,45 Z M30,45 L70,45 L70,50 L30,50 Z M40,50 L60,50 L60,55 L40,55 Z M45,55 L55,55 L55,60 L45,60 Z M48,60 L52,60 L52,65 L48,65 Z'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 60px;
            pointer-events: none;
            z-index: 0;
            opacity: 0.4;
        }
        body::after {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 200 200'%3E%3Ccircle cx='100' cy='30' r='20' fill='none' stroke='%230f766e' stroke-opacity='0.04' stroke-width='2'/%3E%3Cpath d='M80,50 L120,50 L120,55 L80,55 Z' fill='%230f766e' fill-opacity='0.04'/%3E%3C/svg%3E");
            background-repeat: repeat;
            background-size: 120px;
            pointer-events: none;
            z-index: 0;
            opacity: 0.3;
        }
        @keyframes softMorphGradient {
            0% { background-position: 0% 50%; }
            25% { background-position: 40% 30%; }
            50% { background-position: 100% 50%; }
            75% { background-position: 40% 70%; }
            100% { background-position: 0% 50%; }
        }
        .font-serif { font-family: 'Playfair Display', serif; }
        
        .dramatic-section {
            position: relative;
            background-attachment: fixed;
            background-position: center center;
            background-size: cover;
            border-radius: 2rem;
            overflow: hidden;
        }
        .dramatic-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 50%, rgba(0,0,0,0.7) 100%);
            z-index: 1;
        }
        .dramatic-content { position: relative; z-index: 2; }
        
        .card-modern {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(8px);
            border-radius: 1.75rem;
            border: 1px solid rgba(6,78,59,0.2);
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0,0,0,0.05);
        }
        .card-modern:hover { transform: translateY(-5px); border-color: #f59e0b; background: white; }
        
        .badge-gold {
            background: linear-gradient(95deg, #064e3b, #0f766e);
            color: #fef3c7;
            padding: 0.35rem 1.4rem;
            border-radius: 100px;
            font-size: 0.7rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid #f59e0b50;
        }
        .title-grad {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            background: linear-gradient(125deg, #064e3b, #0f766e, #f59e0b);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }
        
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
        .gallery-item {
            position: relative;
            border-radius: 1.25rem;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 15px 25px -12px rgba(0,0,0,0.15);
            background: #d4c9a8;
            width: 100%;
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s;
            display: block;
        }
        .gallery-item:hover img { transform: scale(1.05); }
        .gallery-item.square { aspect-ratio: 1 / 1; }
        .gallery-item.landscape {
            grid-column: span 2;
            aspect-ratio: 20.8 / 10;
        }
        .gallery-more-overlay {
            position: absolute;
            inset: 0;
            background: rgba(6,78,59,0.85);
            backdrop-filter: blur(4px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            z-index: 2;
        }
        .gallery-more-overlay i { font-size: 2rem; margin-bottom: 0.5rem; }
        .gallery-more-overlay span { font-size: 1.2rem; }
        
        @media (max-width: 768px) {
            .gallery-grid { grid-template-columns: repeat(2, 1fr); gap: 0.75rem; }
            .gallery-item.landscape { grid-column: span 2; }
        }
        @media (max-width: 480px) {
            .gallery-grid { grid-template-columns: 1fr; gap: 0.75rem; }
            .gallery-item.landscape { grid-column: span 1; aspect-ratio: 16 / 9; }
        }
        
        /* MODAL GALLERY - GLOSSY/GLASSMORPHISM FULLSCREEN */
        .modal-gallery {
            display: none !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100vw !important;
            height: 100vh !important;
            background: rgba(0, 0, 0, 0.85) !important;
            backdrop-filter: blur(16px) !important;
            -webkit-backdrop-filter: blur(16px) !important;
            z-index: 999999 !important;
            justify-content: center !important;
            align-items: center !important;
            cursor: pointer !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .modal-gallery.active { display: flex !important; }
        
        .modal-content-gallery {
            position: relative !important;
            cursor: default !important;
            animation: modalFloatIn 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1) !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 1000000 !important;
            width: auto !important;
            height: auto !important;
            background: transparent !important;
        }
        
        @keyframes modalFloatIn {
            0% { opacity: 0; transform: scale(0.9) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }
        
        .modal-content-gallery img {
            max-width: 85vw !important;
            max-height: 80vh !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain !important;
            border-radius: 1.5rem !important;
            box-shadow: 0 30px 50px -20px rgba(0, 0, 0, 0.3), 0 0 0 6px rgba(255, 255, 255, 0.2), 0 0 0 12px rgba(0, 0, 0, 0.3) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            background: #111 !important;
        }
        
        .modal-close {
            position: absolute !important;
            top: -50px !important;
            right: -10px !important;
            color: white !important;
            font-size: 1.5rem !important;
            cursor: pointer !important;
            background: rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            width: 44px !important;
            height: 44px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            transition: all 0.2s !important;
            z-index: 1000001 !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
        }
        .modal-close:hover { 
            background: #f59e0b !important; 
            transform: scale(1.1) rotate(90deg) !important;
            border-color: white !important;
            color: white !important;
        }
        
        .modal-nav {
            position: absolute !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: 100% !important;
            display: flex !important;
            justify-content: space-between !important;
            pointer-events: none !important;
            left: 0 !important;
            padding: 0 20px !important;
            z-index: 1000001 !important;
            box-sizing: border-box !important;
        }
        
        .modal-nav button {
            background: rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            color: white !important;
            font-size: 1.3rem !important;
            width: 48px !important;
            height: 48px !important;
            border-radius: 50% !important;
            cursor: pointer !important;
            pointer-events: auto !important;
            transition: all 0.2s !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2) !important;
        }
        .modal-nav button:hover { 
            background: #f59e0b !important; 
            transform: scale(1.1) !important; 
            border-color: white !important;
            color: white !important;
        }
        
        .modal-counter {
            position: absolute !important;
            bottom: -50px !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
            text-align: center !important;
            color: white !important;
            font-size: 0.85rem !important;
            font-weight: 500 !important;
            background: rgba(0, 0, 0, 0.6) !important;
            backdrop-filter: blur(8px) !important;
            padding: 8px 24px !important;
            border-radius: 40px !important;
            width: fit-content !important;
            white-space: nowrap !important;
            border: 1px solid rgba(255, 255, 255, 0.3) !important;
            z-index: 1000001 !important;
            letter-spacing: 0.5px !important;
        }
        
        @media (max-width: 768px) {
            .modal-nav button { width: 40px !important; height: 40px !important; font-size: 1rem !important; }
            .modal-close { top: -45px !important; right: -5px !important; width: 36px !important; height: 36px !important; font-size: 1.2rem !important; }
            .modal-content-gallery img { max-width: 92vw !important; max-height: 70vh !important; box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.3), 0 0 0 4px rgba(255, 255, 255, 0.15), 0 0 0 8px rgba(0, 0, 0, 0.2) !important; }
            .modal-counter { bottom: -45px !important; font-size: 0.7rem !important; padding: 6px 18px !important; }
            .modal-nav { padding: 0 10px !important; }
        }
        
        .list-distribution-single li { 
            display: flex; justify-content: space-between; align-items: center;
            padding: 0.75rem 0; border-bottom: 1px solid #fde68a; flex-wrap: wrap; gap: 0.5rem;
        }
        .list-distribution-single li span:first-child { font-weight: 600; color: #1e293b; font-size: 0.85rem; }
        .list-distribution-single li span:last-child { font-weight: 800; color: #0f766e; background: #e6f7f0; padding: 0.2rem 0.8rem; border-radius: 30px; font-size: 0.75rem; }
        @media (max-width: 640px) {
            .list-distribution-single li { flex-direction: column; align-items: flex-start; }
        }
        
        .ring-container { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; }
        @media (max-width: 768px) { .ring-container { grid-template-columns: 1fr; gap: 1.5rem; } }
        .ring-card { border: 1px solid #e2e8f0; border-radius: 1.25rem; overflow: hidden; background: white; }
        .ring-header { padding: 1rem 1.25rem; font-weight: 800; display: flex; align-items: center; gap: 0.75rem; }
        .ring-body { padding: 1rem 1.25rem; }
        .ring-footer { background: #f8fafc; padding: 0.75rem 1.25rem; text-align: right; border-top: 1px solid #f1f5f9; font-weight: 700; }
        
        .info-bar-modern {
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 0.75rem 1.5rem;
            padding: 0.7rem 1.8rem;
            border: 1px solid rgba(255,255,255,0.25);
        }
        .info-bar-modern span { font-size: 0.8rem; color: white; }
        .info-bar-modern i { margin-right: 0.4rem; color: #fbbf24; }
        @media (max-width: 640px) {
            .info-bar-modern { flex-direction: column; padding: 0.6rem 1.2rem; border-radius: 1.5rem; }
        }
        
        .btn-pdf { background: rgba(255,255,255,0.2); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3); transition: all 0.3s; }
        .btn-pdf:hover { background: rgba(255,255,255,0.35); transform: translateY(-2px); }
        
        .hero-premium { background: linear-gradient(115deg, #064e3b 0%, #0f766e 45%, #14b8a6 100%); border-radius: 2rem; }
        .stat-card { background: rgba(255,255,255,0.96); border-radius: 1.25rem; border-bottom: 3px solid #f59e0b; }
        .stat-number { font-size: 2.6rem; font-weight: 900; }
        
        .finance-section {
            background: white;
            border-radius: 1.25rem;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            transition: all 0.2s;
        }
        .finance-section-header {
            background: linear-gradient(95deg, #f8fafc, #f1f5f9);
            padding: 0.75rem 1.25rem;
            border-bottom: 2px solid #cbd5e1;
        }
        .finance-section-body { padding: 1rem 1.25rem; }
        
        .finance-row { display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid #e2e8f0; }
        .finance-label { font-weight: 500; color: #334155; font-size: 0.875rem; }
        .finance-amount { font-weight: 700; color: #0f766e; font-size: 0.95rem; }
        @media (max-width: 640px) {
            .finance-row { flex-direction: column; align-items: flex-start; gap: 0.25rem; }
        }
        
        .total-box { background: #f0fdf4; padding: 1rem; border-radius: 0.75rem; }
        .total-box-inner { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
        @media (max-width: 640px) { .total-box-inner { flex-direction: column; text-align: center; } }
        
        .cinematic-quote { font-size: 1.4rem; line-height: 1.5; font-style: italic; text-shadow: 0 2px 10px rgba(0,0,0,0.4); font-weight: 600; }
        @media (min-width:768px) { .cinematic-quote { font-size: 2rem; } }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #fef9e6; }
        ::-webkit-scrollbar-thumb { background: linear-gradient(#064e3b, #f59e0b); border-radius: 10px; }
        
        .keterangan-list { list-style: none; padding-left: 0; }
        .keterangan-list li { display: flex; align-items: flex-start; gap: 0.5rem; margin-bottom: 0.5rem; font-size: 0.8rem; color: #334155; }
        .keterangan-list li i { color: #0f766e; margin-top: 0.2rem; flex-shrink: 0; }
        
        .content-wrapper { position: relative; z-index: 2; }
        
        /* Dropdown tahun */
        .tahun-dropdown {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            color: white;
            font-size: 0.875rem;
            cursor: pointer;
        }
        .tahun-dropdown option {
            background: #064e3b;
            color: white;
        }
    </style>
</head>
<body>
<div class="content-wrapper max-w-7xl mx-auto px-5 md:px-8 lg:px-10 py-8 md:py-12 space-y-16">

    <!-- SELECTOR TAHUN -->
    @if(count($availableYears) > 0)
    <div class="flex justify-end">
        <select id="tahunSelect" class="tahun-dropdown">
            @foreach($availableYears as $year)
                <option value="{{ route('qurban.laporan', $year) }}" {{ $report->tahun_hijriah == $year ? 'selected' : '' }}>
                    📅 Laporan {{ $year }}
                </option>
            @endforeach
        </select>
    </div>
    @endif

    <!-- HERO UTAMA -->
    <div class="hero-premium relative overflow-hidden" data-aos="fade-up">
        <div class="absolute top-0 right-0 w-64 h-64 bg-amber-400 rounded-full opacity-10 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-48 h-48 bg-teal-300 rounded-full opacity-10 blur-2xl"></div>
        <div class="relative z-10 py-12 md:py-20 lg:py-24 px-6 text-center">
            <div class="inline-flex items-center gap-2 bg-black/30 backdrop-blur-md rounded-full px-3 py-1 md:px-5 md:py-1.5 mb-5 border border-white/20">
                <span class="relative flex h-2 w-2 md:h-2.5 md:w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-teal-300"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 md:h-2.5 md:w-2.5 bg-teal-400"></span>
                </span>
                <span class="text-white text-[10px] md:text-xs font-bold tracking-wider">{{ $heroData['badge'] }}</span>
            </div>
            <h1 class="font-serif font-black text-white drop-shadow-2xl tracking-tight leading-[1.1]">
                <span class="block text-6xl sm:text-7xl md:text-7xl lg:text-8xl xl:text-9xl">{{ $heroData['title'] }}</span>
                <span class="block text-amber-300 mt-3 text-5xl sm:text-6xl md:text-6xl lg:text-7xl xl:text-8xl">{{ $heroData['subtitle'] }}</span>
            </h1>
            <div class="flex justify-center gap-3 my-6">
                <div class="w-20 h-1 bg-amber-400 rounded-full"></div>
                <div class="w-8 h-1 bg-teal-300 rounded-full"></div>
            </div>
            <p class="max-w-2xl mx-auto text-white/95 text-sm md:text-lg font-semibold bg-black/20 backdrop-blur-md inline-block px-4 py-1.5 md:px-6 md:py-2 rounded-full">
                <i class="fas fa-mosque mr-1 md:mr-2"></i> {{ $heroData['masjid'] }}
            </p>
            
            <div class="flex justify-center mt-6">
                <div class="info-bar-modern">
                    <span><i class="fas fa-book-quran"></i> {{ $heroData['tagline'] }}</span>
                </div>
            </div>

            <div class="mt-8">
                <button onclick="window.print()" class="btn-pdf inline-flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-full transition-all">
                    <i class="fas fa-file-pdf"></i>
                    <span>Cetak/Simpan PDF</span>
                    <i class="fas fa-arrow-down text-xs"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- STATISTIK UTAMA -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-6" data-aos="fade-up" data-aos-delay="100">
        <div class="stat-card p-3 md:p-5 text-center">
            <i class="fas fa-drumstick-bite text-2xl md:text-3xl text-emerald-700 mb-1 md:mb-2"></i>
            <div class="stat-number text-emerald-800" id="counterHewan">0</div>
            <p class="font-extrabold text-gray-700 uppercase text-[10px] md:text-xs tracking-wide mt-1">Hewan Qurban</p>
            <span class="text-[10px] md:text-xs font-semibold">{{ $stats['hewan']['sapi'] }} Sapi + {{ $stats['hewan']['kambing'] }} Kambing</span>
        </div>
        <div class="stat-card p-3 md:p-5 text-center">
            <i class="fas fa-box-open text-2xl md:text-3xl text-blue-700 mb-1 md:mb-2"></i>
            <div class="stat-number text-blue-800" id="counterPaket">0</div>
            <p class="font-extrabold text-gray-700 uppercase text-[10px] md:text-xs tracking-wide mt-1">Paket Daging</p>
            <span class="text-[10px] md:text-xs font-semibold">100% Tersalurkan</span>
        </div>
        <div class="stat-card p-3 md:p-5 text-center">
            <i class="fas fa-users text-2xl md:text-3xl text-amber-700 mb-1 md:mb-2"></i>
            <div class="stat-number text-amber-800" id="counterMustahik">0</div>
            <p class="font-extrabold text-gray-700 uppercase text-[10px] md:text-xs tracking-wide mt-1">Mustahik</p>
            <span class="text-[10px] md:text-xs font-semibold">{{ number_format($stats['mustahik']) }} Keluarga</span>
        </div>
        <div class="stat-card p-3 md:p-5 text-center">
            <i class="fas fa-weight-hanging text-2xl md:text-3xl text-purple-700 mb-1 md:mb-2"></i>
            <div class="stat-number text-purple-800" id="counterDaging">0</div>
            <p class="font-extrabold text-gray-700 uppercase text-[10px] md:text-xs tracking-wide mt-1">Daging (kg)</p>
            <span class="text-[10px] md:text-xs font-semibold">Bersih & Halal</span>
        </div>
    </div>

    <!-- DRAMATIS 1: SHOLAT ID -->
    <div class="dramatic-section" style="background-image: url('{{ $dramatis[1]['image'] ? asset($dramatis[1]['image']) : asset('images/qurban/dramatic/sholat.jpg') }}'); background-position: center 30%;" data-aos="fade-up">
        <div class="dramatic-overlay"></div>
        <div class="dramatic-content py-20 md:py-28 lg:py-36 px-6 text-center text-white">
            <i class="fas fa-mosque text-5xl md:text-7xl mb-4 md:mb-6 drop-shadow-2xl"></i>
            <h2 class="font-serif text-4xl md:text-5xl lg:text-7xl font-black mb-4 md:mb-5">{{ $dramatis[1]['title'] }}</h2>
            <div class="w-20 md:w-28 h-1 bg-amber-400 mx-auto mb-5 md:mb-7"></div>
            <p class="cinematic-quote max-w-3xl mx-auto">{{ $dramatis[1]['quote'] }}</p>
            <div class="mt-8 md:mt-10 text-amber-300 font-bold text-sm md:text-base"><i class="fas fa-camera"></i> {{ $dramatis[1]['stat'] }}</div>
        </div>
    </div>

    <!-- PELAKSANAAN -->
    <div class="grid lg:grid-cols-2 gap-8 md:gap-12 items-center" data-aos="fade-right">
        <div>
            <div class="badge-gold mb-4"><i class="fas fa-calendar-check"></i> LAPORAN RESMI</div>
            <h2 class="title-grad text-3xl md:text-4xl lg:text-5xl font-black">Pelaksanaan Idul Adha<br>& Qurban {{ $heroData['subtitle'] }}</h2>
            <div class="w-20 h-1 bg-gradient-to-r from-amber-500 to-emerald-500 my-5 rounded-full"></div>
            <p class="text-gray-700 leading-relaxed font-medium"><span class="text-emerald-700 font-black">Alhamdulillah</span>, sholat Idul Adha di {{ $pelaksanaan['lokasi_sholat'] }} dan penyembelihan hewan qurban di {{ $pelaksanaan['lokasi_qurban'] }} berjalan lancar.</p>
            <p class="text-gray-600 mt-3 font-medium">Terima kasih kepada para shohibul qurban, relawan, dan seluruh pihak yang berkontribusi.</p>
            <div class="card-modern p-4 md:p-5 mt-7 flex flex-col md:flex-row md:justify-between md:items-center gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-landmark text-xl md:text-2xl text-emerald-700"></i>
                    </div>
                    <div>
                        <p class="font-black text-gray-800 text-sm md:text-base">{{ $pelaksanaan['masjid_nama'] }}</p>
                        <p class="text-[10px] md:text-xs text-gray-500">{{ $pelaksanaan['masjid_sub'] }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 md:justify-end">
                    <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-user-tie text-xl md:text-2xl text-amber-700"></i>
                    </div>
                    <div class="md:text-right">
                        <p class="font-serif text-base md:text-xl font-black bg-gradient-to-r from-amber-600 to-orange-500 bg-clip-text text-transparent">{{ $pelaksanaan['ketua_nama'] }}</p>
                        <p class="text-[10px] md:text-xs font-semibold text-gray-500">{{ $pelaksanaan['ketua_jabatan'] }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3 md:gap-4">
            <div class="rounded-2xl overflow-hidden shadow-xl h-40 md:h-56">
                <img src="{{ $pelaksanaan['gambar1'] ? asset($pelaksanaan['gambar1']) : 'https://picsum.photos/id/20/600/500' }}" alt="Sholat Ied" class="w-full h-full object-cover">
            </div>
            <div class="rounded-2xl overflow-hidden shadow-xl h-40 md:h-56 mt-4 md:mt-6">
                <img src="{{ $pelaksanaan['gambar2'] ? asset($pelaksanaan['gambar2']) : 'https://picsum.photos/id/30/600/500' }}" alt="Khatib" class="w-full h-full object-cover">
            </div>
            <div class="rounded-2xl overflow-hidden shadow-xl h-36 md:h-48 col-span-2">
                <img src="{{ $pelaksanaan['gambar3'] ? asset($pelaksanaan['gambar3']) : 'https://picsum.photos/id/141/1200/500' }}" alt="Hewan Qurban" class="w-full h-full object-cover">
            </div>
        </div>
    </div>

    <!-- DRAMATIS 2: PENYEMBELIHAN -->
    <div class="dramatic-section" style="background-image: url('{{ $dramatis[2]['image'] ? asset($dramatis[2]['image']) : asset('images/qurban/dramatic/penyembelihan.jpg') }}');" data-aos="fade-up">
        <div class="dramatic-overlay"></div>
        <div class="dramatic-content py-20 md:py-24 px-6 text-center text-white">
            <i class="fas fa-hand-holding-heart text-5xl md:text-6xl mb-4 md:mb-5"></i>
            <h2 class="font-serif text-4xl md:text-5xl lg:text-7xl font-bold">{{ $dramatis[2]['title'] }}</h2>
            <p class="cinematic-quote max-w-3xl mx-auto mt-4">{{ $dramatis[2]['quote'] }}</p>
            <div class="mt-6 md:mt-8 text-amber-300 font-black text-sm md:text-base">{{ $dramatis[2]['stat'] }}</div>
        </div>
    </div>

    <!-- DATA PEMOTONGAN -->
    <div class="bg-white rounded-2xl shadow-xl border border-teal-100 p-5 md:p-8" data-aos="fade-up">
        <div class="flex flex-wrap justify-between items-center border-b border-teal-100 pb-4 mb-6">
            <div><h3 class="font-serif text-xl md:text-2xl font-black text-gray-800"><i class="fas fa-chart-pie text-teal-600 mr-2"></i> Rincian Pemotongan</h3><p class="text-xs md:text-sm font-medium text-gray-500">Data Real-Time Qurban {{ $heroData['subtitle'] }}</p></div>
            <div class="flex gap-2"><span class="bg-gray-100 px-2 py-1 md:px-3 md:py-1 rounded-full text-[10px] md:text-xs font-bold"><i class="far fa-calendar-alt"></i> 10 Dzulhijjah {{ $heroData['subtitle'] }}</span></div>
        </div>
        <div class="grid md:grid-cols-2 gap-5 md:gap-6">
            <div class="text-center p-3 md:p-4 bg-blue-50 rounded-xl">
                <div class="text-3xl md:text-4xl font-black text-blue-800">{{ $stats['hewan']['sapi'] }}</div>
                <p class="font-black text-sm md:text-base">Sapi Kolektif</p>
                <span class="text-[10px] md:text-xs font-semibold">Rata-rata {{ number_format($pemotongan['sapi_berat_kg']) }} kg/ekor</span>
            </div>
            <div class="text-center p-3 md:p-4 bg-amber-50 rounded-xl">
                <div class="text-3xl md:text-4xl font-black text-amber-800">{{ $stats['hewan']['kambing'] }}</div>
                <p class="font-black text-sm md:text-base">Kambing</p>
                <span class="text-[10px] md:text-xs font-semibold">Rata-rata {{ number_format($pemotongan['kambing_berat_kg']) }} kg/ekor</span>
            </div>
        </div>
        <div class="mt-5 md:mt-6 bg-gradient-to-r from-emerald-700 to-teal-600 rounded-xl p-4 md:p-5 text-white flex flex-wrap justify-between items-center gap-3">
            <div class="flex gap-3 md:gap-4 flex-wrap">
                <div><i class="fas fa-cow text-xl md:text-2xl"></i> <span class="font-black ml-1 text-sm md:text-base">Total Hewan: {{ $stats['hewan']['total'] }} Ekor</span></div>
                <div><i class="fas fa-check-circle"></i> <span class="font-black text-sm md:text-base">100% Halal & Thayyib</span></div>
            </div>
            <div><i class="fas fa-clock"></i> <span class="text-sm md:text-base">Pemotongan Tepat Waktu</span></div>
        </div>
    </div>

    <!-- PENERIMA MANFAAT -->
    <div class="bg-white rounded-2xl shadow-xl p-5 md:p-8 border-t-8 border-amber-500" data-aos="fade-up">
        <div class="text-center mb-6 md:mb-8">
            <div class="inline-flex bg-emerald-100 text-emerald-800 px-4 py-1 md:px-5 md:py-1.5 rounded-full font-black text-[10px] md:text-sm">
                <i class="fas fa-chart-pie mr-1 md:mr-2"></i> DISTRIBUSI QURBAN {{ $heroData['subtitle'] }}
            </div>
            <h2 class="font-serif text-2xl md:text-3xl lg:text-4xl font-black mt-3 text-gray-800">Penerima Manfaat Qurban</h2>
            <p class="text-gray-600 font-medium text-xs md:text-sm">Transparansi penyaluran daging (tanpa duplikasi data)</p>
        </div>
        
        <!-- Distribusi 3 kolom - DATA DARI CONTROLLER -->
        @php
            $distribusiCount = count($distribusi);
            
            // Tentukan grid columns berdasarkan jumlah
            if ($distribusiCount == 1) {
                $gridClass = 'grid grid-cols-1 max-w-md mx-auto gap-4 md:gap-6 mb-8 md:mb-10';
            } elseif ($distribusiCount == 2) {
                $gridClass = 'grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-8 md:mb-10';
            } elseif ($distribusiCount == 3) {
                $gridClass = 'grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-10';
            } else {
                $gridClass = 'grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-8 md:mb-10';
            }
        @endphp

        <div class="{{ $gridClass }}">
            @foreach($distribusi as $item)
            <div class="bg-{{ $item['color'] }}-50 rounded-xl p-3 md:p-5 text-center">
                <i class="fas {{ $item['icon'] }} text-2xl md:text-3xl text-{{ $item['color'] }}-700 mb-1 md:mb-2"></i>
                <p class="font-black text-sm md:text-lg">{{ $item['label'] }}</p>
                <span class="text-2xl md:text-3xl font-black text-{{ $item['color'] }}-800">{{ number_format($item['value']) }}</span>
                <p class="text-[10px] md:text-sm font-semibold">Paket ({{ $item['percentage'] }}%)</p>
            </div>
            @endforeach
        </div>
        <!-- Rings - dinamis -->
        <div class="ring-container" style="display: grid; grid-template-columns: repeat({{ count($penerima) }}, 1fr); gap: 2rem;">
            @php $ringColors = ['emerald', 'teal', 'blue', 'amber', 'purple']; @endphp
            @foreach($penerima as $ring)
                @php 
                    $colorIndex = $loop->index % count($ringColors);
                    $color = $ringColors[$colorIndex];
                @endphp
                <div class="ring-card">
                    <div class="ring-header bg-gradient-to-r from-{{ $color }}-800 to-{{ $color }}-600 text-white">
                        <i class="fas {{ $ring['icon'] ?? 'fa-building' }}"></i>
                        <h4 class="text-sm md:text-base lg:text-lg">{{ $ring['title'] }}</h4>
                    </div>
                    <div class="ring-body">
                        <ul class="list-distribution-single space-y-1 md:space-y-2">
                            @foreach($ring['items'] as $ringItem)
                                <li><span>{{ $ringItem['label'] }}</span><span>{{ $ringItem['value'] }}</span></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="ring-footer bg-{{ $color }}-50 text-{{ $color }}-700 text-xs md:text-sm">
                        <i class="fas fa-users mr-1"></i> TOTAL: {{ $ring['total'] }}
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Setelah rings, tampilkan grand total -->
        <div class="mt-6 md:mt-8 bg-emerald-50 rounded-xl p-4 md:p-5 border border-emerald-200">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                <div class="flex items-center gap-2">
                    <i class="fas fa-chart-line text-2xl text-emerald-600"></i>
                    <span class="font-black text-gray-800 text-base md:text-lg">GRAND TOTAL PENERIMA</span>
                </div>
                <div class="text-center sm:text-right">
                    <span class="font-black text-emerald-700 text-2xl md:text-3xl">{{ number_format($grandTotalPenerima) }} penerima</span>
                    <p class="text-xs text-gray-500 mt-1">Dari seluruh Ring I, II, dst</p>
                </div>
            </div>
        </div>
    </div>

    <!-- DRAMATIS 3: SENYUMAN -->
    <div class="dramatic-section" style="background-image: url('{{ $dramatis[3]['image'] ? asset($dramatis[3]['image']) : asset('images/qurban/dramatic/senyum.jpg') }}');" data-aos="fade-up">
        <div class="dramatic-overlay"></div>
        <div class="dramatic-content py-20 md:py-28 px-6 text-center text-white">
            <i class="fas fa-smile-wink text-5xl md:text-7xl mb-4 md:mb-5"></i>
            <h2 class="font-serif text-4xl md:text-5xl lg:text-7xl font-bold">{{ $dramatis[3]['title'] }}</h2>
            <p class="cinematic-quote max-w-3xl mx-auto mt-4">{{ $dramatis[3]['quote'] }}</p>
            <div class="mt-6 md:mt-8 text-amber-300 font-black text-sm md:text-base">{{ $dramatis[3]['stat'] }}</div>
        </div>
    </div>

    <!-- GALERI FOTO -->
    <div class="card-modern p-4 md:p-6" data-aos="fade-up">
        <div class="flex justify-between items-center mb-4 md:mb-6">
            <div>
                <h3 class="font-serif text-xl md:text-2xl font-black text-gray-800">📸 Galeri Momen Idul Adha {{ $heroData['subtitle'] }}</h3>
                <p class="text-[10px] md:text-sm font-medium text-gray-500">Klik foto untuk melihat lebih banyak (total {{ count($galleryImages) + count($additionalImages) }} foto)</p>
            </div>
            <i class="fas fa-camera-retro text-2xl md:text-3xl text-amber-500"></i>
        </div>
        
        <div class="gallery-grid" id="galleryContainer">
            @foreach($galleryImages as $index => $image)
                @if($index < 10)
                    <div class="gallery-item {{ $image['type'] ?? 'square' }}" data-index="{{ $index }}">
                        <img src="{{ $image['url'] }}" alt="{{ $image['alt'] ?? 'Foto galeri' }}">
                    </div>
                @endif
            @endforeach
            
            <!-- More overlay button -->
            <div class="gallery-item square relative" id="morePhotosTrigger" data-index="{{ count($galleryImages) }}">
                <img src="{{ count($galleryImages) > 0 ? $galleryImages[0]['url'] : asset('images/qurban/gallery/default.jpg') }}" alt="+{{ count($additionalImages) }} Foto Lainnya">
                <div class="gallery-more-overlay">
                    <i class="fas fa-images"></i>
                    <span>+{{ count($additionalImages) }} Foto</span>
                </div>
            </div>
            
            <!-- Placeholder untuk menjaga grid rapi -->
            @php
                $displayedCount = min(10, count($galleryImages)) + 1;
                $placeholders = 4 - ($displayedCount % 4);
                if($placeholders == 4) $placeholders = 0;
            @endphp
            @for($i = 0; $i < $placeholders; $i++)
                <div class="gallery-item square" style="opacity: 0; cursor: default; pointer-events: none;"></div>
            @endfor
        </div>
        <p class="text-center text-[10px] md:text-xs text-gray-500 mt-4 md:mt-5 font-medium">
            <i class="fas fa-info-circle"></i> Total {{ count($galleryImages) + count($additionalImages) }} foto dokumentasi Idul Adha {{ $heroData['subtitle'] }}
        </p>
    </div>

    <!-- Modal Gallery -->
    <div id="galleryModal" class="modal-gallery">
        <div class="modal-content-gallery">
            <div class="modal-close" id="modalClose">
                <i class="fas fa-times"></i>
            </div>
            <img id="modalImage" src="" alt="Gallery">
            <div class="modal-nav">
                <button id="prevBtn"><i class="fas fa-chevron-left"></i></button>
                <button id="nextBtn"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div class="modal-counter" id="modalCounter"></div>
        </div>
    </div>

    <!-- RINCIAN PENGELUARAN & PENGELOLAAN DANA QURBAN -->
    <div class="bg-white rounded-2xl shadow-xl border border-teal-100 overflow-hidden" data-aos="fade-up">
        <div class="bg-gradient-to-r from-emerald-800 to-teal-700 px-5 md:px-8 py-4 md:py-5">
            <h2 class="font-serif text-xl md:text-2xl lg:text-3xl font-black text-white flex items-center gap-3">
                <i class="fas fa-chart-line text-amber-300"></i>
                Rincian Pengeluaran & Pengelolaan Dana Qurban
            </h2>
            <p class="text-emerald-100 text-xs md:text-sm mt-1">Transparansi penuh atas setiap rupiah yang diamanahkan</p>
        </div>
        
        <div class="p-5 md:p-8 space-y-6">
            
            <!-- PENERIMAAN -->
            <div class="finance-section">
                <div class="finance-section-header">
                    <h3 class="font-black text-gray-800 text-base md:text-lg flex items-center gap-2">
                        <i class="fas fa-money-bill-wave text-emerald-600"></i>
                        1. PENERIMAAN
                    </h3>
                </div>
                <div class="finance-section-body">
                    @forelse($keuangan['penerimaan'] as $item)
                    <div class="finance-row">
                        <span class="finance-label">{{ $item['label'] }}</span>
                        <span class="finance-amount">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Belum ada data penerimaan</p>
                    @endforelse
                    @if(count($keuangan['penerimaan']) > 0)
                    <div class="total-box mt-3">
                        <div class="total-box-inner">
                            <span class="font-black text-gray-800">Total Penerimaan</span>
                            <span class="font-black text-emerald-800 text-lg md:text-xl">Rp {{ number_format($keuangan['total_penerimaan'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- PENGELUARAN -->
            <div class="finance-section">
                <div class="finance-section-header">
                    <h3 class="font-black text-gray-800 text-base md:text-lg flex items-center gap-2">
                        <i class="fas fa-receipt text-amber-600"></i>
                        2. PENGELUARAN
                    </h3>
                </div>
                <div class="finance-section-body">
                    @forelse($keuangan['pengeluaran'] as $item)
                    <div class="finance-row">
                        <span class="finance-label">{{ $item['label'] }}</span>
                        <span class="finance-amount">Rp {{ number_format($item['amount'], 0, ',', '.') }}</span>
                    </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Belum ada data pengeluaran</p>
                    @endforelse
                    @if(count($keuangan['pengeluaran']) > 0)
                    <div class="total-box mt-3">
                        <div class="total-box-inner">
                            <span class="font-black text-gray-800">Total Pengeluaran</span>
                            <span class="font-black text-amber-800 text-lg md:text-xl">Rp {{ number_format($keuangan['total_pengeluaran'], 0, ',', '.') }}</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- SISA DANA -->
            <div class="bg-gradient-to-r from-amber-100 to-orange-50 rounded-xl p-4 md:p-6 border-l-8 border-amber-500">
                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <div>
                        <span class="font-black text-gray-800 text-base md:text-lg"><i class="fas fa-coins text-amber-600 mr-2"></i>Sisa Dana / Saldo Akhir</span>
                        <p class="text-gray-500 text-xs mt-1">(Total Penerimaan - Total Pengeluaran)</p>
                    </div>
                    <div class="text-right">
                        <span class="font-black text-amber-700 text-xl md:text-2xl">Rp {{ number_format($keuangan['sisa_dana'], 0, ',', '.') }}</span>
                        <p class="text-emerald-600 text-xs font-semibold mt-1">✓ Akan dialokasikan untuk kegiatan sosial berikutnya</p>
                    </div>
                </div>
            </div>

            <!-- KETERANGAN -->
            <div class="bg-blue-50 rounded-xl p-4 md:p-5 border border-blue-200">
                <div class="flex gap-3">
                    <i class="fas fa-info-circle text-blue-500 text-lg flex-shrink-0 mt-0.5"></i>
                    <div>
                        <p class="font-bold text-gray-800 text-sm md:text-base mb-3">Keterangan:</p>
                        <ul class="keterangan-list">
                            <li><i class="fas fa-check-circle text-xs"></i> {{ $stats['hewan']['sapi'] }} ekor sapi qurban merupakan hewan kolektif dari peserta</li>
                            <li><i class="fas fa-check-circle text-xs"></i> {{ $stats['hewan']['kambing'] }} ekor kambing dari peserta + 10 ekor kambing hibah/sumbangan</li>
                            <li><i class="fas fa-check-circle text-xs"></i> Total hewan qurban: {{ $stats['hewan']['sapi'] }} sapi + {{ $stats['hewan']['kambing'] }} kambing = {{ $stats['hewan']['total'] }} ekor</li>
                            <li><i class="fas fa-check-circle text-xs"></i> Seluruh dana telah dikelola secara amanah dan transparan</li>
                            <li><i class="fas fa-check-circle text-xs"></i> Distribusi daging tepat sasaran kepada {{ number_format($stats['mustahik']) }}+ keluarga</li>
                        </ul>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- QR DAN CARD TERIMA KASIH -->
    <div class="grid md:grid-cols-2 gap-6 md:gap-8" data-aos="fade-up">
        <div class="card-modern p-5 md:p-7 border-t-8 border-amber-500 text-center">
            <i class="fas fa-qrcode text-5xl md:text-6xl text-amber-600 mb-3"></i>
            <h3 class="font-serif text-xl md:text-2xl font-black">Dokumentasi Lengkap</h3>
            <p class="text-xs md:text-sm text-gray-600 mt-2">Scan QR untuk galeri video & foto Qurban {{ $heroData['subtitle'] }}</p>
            <div class="bg-white w-24 h-24 md:w-32 md:h-32 rounded-2xl mx-auto flex items-center justify-center my-4 shadow-lg border">
                @if($qr['image'])
                    <img src="{{ asset($qr['image']) }}" alt="QR Code" class="w-full h-full object-cover rounded-2xl">
                @else
                    <i class="fas fa-qrcode text-5xl md:text-6xl text-emerald-800"></i>
                @endif
            </div>
            @if($qr['link'])
                <a href="{{ $qr['link'] }}" target="_blank" class="inline-block bg-emerald-700 text-white px-5 py-2 rounded-full text-xs md:text-sm font-semibold hover:bg-emerald-800 transition">Buka Arsip Momen →</a>
            @else
                <a href="{{ route('qurban.laporan') }}" class="inline-block bg-emerald-700 text-white px-5 py-2 rounded-full text-xs md:text-sm font-semibold hover:bg-emerald-800 transition">Buka Arsip Momen →</a>
            @endif
        </div>
        
        <div class="rounded-2xl p-6 md:p-8 text-center text-white shadow-2xl relative overflow-hidden" style="background: linear-gradient(135deg, #064e3b, #0f766e, #14b8a6);">
            <div class="absolute top-0 right-0 w-32 h-32 bg-amber-400 rounded-full opacity-20 blur-2xl"></div>
            <div class="absolute bottom-0 left-0 w-24 h-24 bg-teal-300 rounded-full opacity-20 blur-xl"></div>
            <div class="relative z-10">
                <i class="fas fa-hand-holding-heart text-5xl md:text-6xl mb-4 text-amber-300"></i>
                <h3 class="text-2xl md:text-3xl font-black mb-3">{{ $thankyou['title'] }}</h3>
                <p class="text-sm md:text-base opacity-95 leading-relaxed">{{ $thankyou['message'] }}</p>
                <div class="mt-5 pt-3 border-t border-white/20">
                    <p class="text-xs md:text-sm italic">{{ $thankyou['hadits'] }}</p>
                </div>
                <div class="mt-4 flex justify-center gap-1">
                    <i class="fas fa-star text-amber-300 text-xs"></i>
                    <i class="fas fa-star text-amber-300 text-xs"></i>
                    <i class="fas fa-star text-amber-300 text-xs"></i>
                    <i class="fas fa-star text-amber-300 text-xs"></i>
                    <i class="fas fa-star text-amber-300 text-xs"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <div class="footer-card text-center py-8 md:py-12 rounded-3xl bg-gradient-to-r from-emerald-100 via-amber-100 to-orange-100" data-aos="fade-up">
        <i class="fas fa-mosque text-4xl md:text-5xl text-emerald-800 mb-3 md:mb-4"></i>
        <p class="font-serif italic text-gray-800 text-base md:text-xl max-w-2xl mx-auto font-bold px-4">{{ $footer['quote'] }}</p>
        <div class="w-16 md:w-20 h-1 bg-amber-500 mx-auto my-4 md:my-5"></div>
        <p class="text-gray-700 font-medium text-sm md:text-base px-4">Masjid Raudhotul Jannah — Taman Cipulir Estate | Laporan Resmi {{ $heroData['subtitle'] }}</p>
        <div class="flex justify-center gap-5 md:gap-6 mt-4 md:mt-5 text-amber-700 text-lg md:text-xl">
            <a href="{{ $footer['instagram'] }}" target="_blank" class="hover:scale-110 transition"><i class="fab fa-instagram"></i></a>
            <a href="{{ $footer['whatsapp'] }}" target="_blank" class="hover:scale-110 transition"><i class="fab fa-whatsapp"></i></a>
            <a href="mailto:{{ $footer['email'] }}" class="hover:scale-110 transition"><i class="fas fa-envelope"></i></a>
        </div>
    </div>
</div>

<script>
    AOS.init({ duration: 600, once: true });
    
    // Animasi counter
    const animateNum = (id, target) => { 
        let el = document.getElementById(id); 
        if(!el) return; 
        let curr = 0; 
        let step = Math.ceil(target / 60); 
        let intv = setInterval(() => { 
            curr += step; 
            if(curr >= target) { 
                el.innerText = target.toLocaleString('id-ID'); 
                clearInterval(intv); 
            } else { 
                el.innerText = Math.floor(curr).toLocaleString('id-ID'); 
            } 
        }, 25); 
    };
    
    const observer = new IntersectionObserver((e) => { 
        if(e[0].isIntersecting) { 
            animateNum('counterHewan', {{ $stats['hewan']['total'] }}); 
            animateNum('counterPaket', {{ $stats['paket'] }}); 
            animateNum('counterMustahik', {{ $stats['mustahik'] }}); 
            animateNum('counterDaging', {{ $stats['daging_kg'] }}); 
            observer.disconnect(); 
        } 
    }, { threshold: 0.3 });
    
    const statSec = document.querySelector('.grid-cols-2.md\\:grid-cols-4');
    if(statSec) observer.observe(statSec);
    
    // Gallery images untuk modal
    const allGalleryImages = [
        @foreach($galleryImages as $img)
            "{{ $img['url'] }}",
        @endforeach
        @foreach($additionalImages as $img)
            "{{ $img }}",
        @endforeach
    ];
    
    let currentIndex = 0;
    const modal = document.getElementById('galleryModal');
    const modalImage = document.getElementById('modalImage');
    const modalCounter = document.getElementById('modalCounter');
    const closeBtn = document.getElementById('modalClose');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    function openModal(index) {
        currentIndex = index;
        modalImage.src = allGalleryImages[currentIndex];
        modalCounter.innerText = `${currentIndex + 1} / ${allGalleryImages.length}`;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function nextImage() { 
        currentIndex = (currentIndex + 1) % allGalleryImages.length; 
        modalImage.src = allGalleryImages[currentIndex]; 
        modalCounter.innerText = `${currentIndex + 1} / ${allGalleryImages.length}`; 
    }
    
    function prevImage() { 
        currentIndex = (currentIndex - 1 + allGalleryImages.length) % allGalleryImages.length; 
        modalImage.src = allGalleryImages[currentIndex]; 
        modalCounter.innerText = `${currentIndex + 1} / ${allGalleryImages.length}`; 
    }
    
    // Daftarkan gallery item
    const galleryItems = document.querySelectorAll('.gallery-item');
    galleryItems.forEach((item) => {
        const idx = item.getAttribute('data-index');
        if(idx && idx !== '' && !isNaN(parseInt(idx))) {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                openModal(parseInt(idx));
            });
        }
    });
    
    // Tombol more
    const moreBtn = document.getElementById('morePhotosTrigger');
    if(moreBtn) {
        moreBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            openModal({{ count($galleryImages) }});
        });
    }
    
    closeBtn.addEventListener('click', closeModal);
    prevBtn.addEventListener('click', prevImage);
    nextBtn.addEventListener('click', nextImage);
    modal.addEventListener('click', (e) => { 
        if(e.target === modal) closeModal(); 
    });
    document.addEventListener('keydown', (e) => {
        if(!modal.classList.contains('active')) return;
        if(e.key === 'Escape') closeModal();
        if(e.key === 'ArrowLeft') prevImage();
        if(e.key === 'ArrowRight') nextImage();
    });
    
    // Dropdown tahun
    const tahunSelect = document.getElementById('tahunSelect');
    if(tahunSelect) {
        tahunSelect.addEventListener('change', function() {
            window.location.href = this.value;
        });
    }
</script>
</body>
</html>