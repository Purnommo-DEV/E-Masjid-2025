@extends('masjid.master-guest')

@section('title', 'Program Qurban 1447 H - Fasilitas Masjid Raudhotul Jannah')

@push('head')
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endpush

@section('content')

@php
    $waInfoPhone = normalizeWaNumber($contactInfoPhone ?? null, '085716503815');
    $waConfirmPhone = normalizeWaNumber($contactConfirmPhone ?? null, '081310185948');
    $featuredPaket = $qurbans->first();
    $heroImage = $featuredPaket?->cover_image_url ?: asset('assets/mrj/images/default-share.jpg');
    $availableCount = $qurbans->count();
    $startingPrice = $qurbans->min('harga') ? 'Rp ' . number_format($qurbans->min('harga'), 0, ',', '.') : 'Segera hadir';
    $collectiveCount = $qurbans->where('max_share', '>', 1)->count();
@endphp

@push('style')
<style>
    * { 
        font-family: 'Inter', sans-serif; 
        scroll-behavior: smooth; 
    }
    
    /* ================= ANIMATIONS ================= */
    @keyframes fadeInUp { 
        from { opacity: 0; transform: translateY(30px); } 
        to { opacity: 1; transform: translateY(0); } 
    }
    @keyframes floatLeft { 
        0%, 100% { transform: translateY(0px) translateX(0px); } 
        50% { transform: translateY(-12px) translateX(-4px); } 
    }
    @keyframes floatRight { 
        0%, 100% { transform: translateY(0px) translateX(0px); } 
        50% { transform: translateY(-10px) translateX(4px); } 
    }
    @keyframes gradientShift { 
        0% { background-position: 0% 50%; } 
        50% { background-position: 100% 50%; } 
        100% { background-position: 0% 50%; } 
    }
    @keyframes sheen { 
        from { transform: translateX(-120%) skewX(-12deg); } 
        to { transform: translateX(160%) skewX(-12deg); } 
    }

    .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
    .animation-delay-200 { animation-delay: 0.2s; }
    .animation-delay-300 { animation-delay: 0.3s; }
    .animation-delay-400 { animation-delay: 0.4s; }

    /* ================= BACKGROUND SECTIONS ================= */
    .qurban-hero-soft {
        background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 45%, #fffbeb 100%);
        position: relative;
        overflow: hidden;
    }
    
    .qurban-section {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 48%, #f0fdf4 100%);
    }
    
    .qurban-info-section {
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfccb 50%, #fefce8 100%);
    }

    /* ================= FLOATING BADGES - POSISI DIPERBAIKI ================= */
    .badge-floating {
        position: absolute;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,0.96);
        border-radius: 9999px;
        padding: 12px 26px;
        box-shadow: 0 10px 30px -8px rgb(0 0 0 / 18%);
        z-index: 30;
        white-space: nowrap;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,0.8);
        font-weight: 700;
        font-size: 0.95rem;
    }

    .badge-terpercaya {
        top: 22%;
        left: 5%;
        animation: floatLeft 4.2s ease-in-out infinite;
        border-left: 5px solid #10b981;
    }
    
    .badge-distribusi {
        top: 28%;
        right: 5%;
        animation: floatRight 3.8s ease-in-out infinite;
        border-right: 5px solid #3b82f6;
    }

    .badge-floating i {
        font-size: 1.35rem;
    }

    /* ================= MAIN BADGE IDUL ADHA ================= */
    .badge-main {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        background: linear-gradient(135deg, #f59e0b, #ea580c, #f59e0b);
        background-size: 200% 200%;
        animation: gradientShift 3s ease infinite;
        border-radius: 60px;
        padding: 12px 32px;
        box-shadow: 0 10px 30px rgba(245, 158, 11, 0.45);
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .badge-main:hover {
        transform: scale(1.05);
        box-shadow: 0 14px 35px rgba(245, 158, 11, 0.55);
    }

    .badge-main span {
        background: linear-gradient(135deg, #FFF8E7, #FFE4B5, #FFD700);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        font-weight: 900;
        font-size: 1.1rem;
    }

    .pulse-ring::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 100%;
        height: 100%;
        background: rgba(245, 158, 11, 0.5);
        border-radius: 50%;
        transform: translate(-50%, -50%) scale(0.8);
        animation: pulse-ring 1.6s ease-out infinite;
    }

    @keyframes pulse-ring {
        0% { transform: scale(0.8); opacity: 0.6; }
        100% { transform: scale(1.4); opacity: 0; }
    }

    /* ================= PAKET CARD - HOVER & SELECTED DIPERBAIKI ================= */
    .paket-card {
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
        position: relative;
        border: 2px solid #e2e8f0;
        background: white;
        border-radius: 0.75rem;
        overflow: hidden;
    }
    
    /* Hover Effect */
    .paket-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        border-color: #f59e0b;
    }
    
    .paket-card:hover .icon-hewan {
        transform: scale(1.12);
        background: linear-gradient(135deg, #fef3c7, #fde68a) !important;
        border-color: #f59e0b;
    }
    
    .paket-card:hover .btn-pilih {
        background: linear-gradient(95deg, #f59e0b, #ea580c) !important;
        transform: scale(1.05);
        box-shadow: 0 8px 16px rgba(245, 158, 11, 0.4);
    }
    
    .paket-card:hover h3 {
        color: #f59e0b;
    }
    
    .paket-card:hover .price-gradient {
        background: linear-gradient(135deg, #f59e0b, #ea580c) !important;
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    /* Selected State - BORDER ORANYE JELAS */
    .paket-card.selected {
        border: 3px solid #f59e0b !important;
        background: linear-gradient(145deg, #f0fdfa 0%, #fffbeb 100%);
        box-shadow: 0 25px 50px -12px rgba(245, 158, 11, 0.35) !important;
        transform: scale(1.02);
    }
    
    .paket-card.selected::after {
        content: "✓";
        position: absolute;
        top: 14px;
        right: 14px;
        background: #f59e0b;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 9999px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 800;
        box-shadow: 0 4px 10px rgba(0,0,0,0.15);
        z-index: 20;
    }
    
    .paket-card.selected .icon-hewan {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        transform: scale(1.08);
    }

    /* Sheen Effect */
    .paket-card::before {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: linear-gradient(110deg, transparent 40%, rgba(255,255,255,0.6) 50%, transparent 60%);
        transform: translateX(-150%) skewX(-15deg);
        opacity: 0;
        transition: all 0.6s;
    }
    
    .paket-card:hover::before {
        opacity: 1;
        transform: translateX(250%) skewX(-15deg);
    }

    /* ================= LAINNYA (dipertahankan & dibersihkan) ================= */
    #paket-list::-webkit-scrollbar { width: 6px; }
    #paket-list::-webkit-scrollbar-track { background: #e2e8f0; border-radius: 10px; }
    #paket-list::-webkit-scrollbar-thumb { background: #10b981; border-radius: 10px; }

    .price-gradient {
        background: linear-gradient(to right, #047857, #0f766e);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
    }

    /* Responsive */
    @media (max-width: 1024px) {
        .badge-terpercaya, .badge-distribusi {
            display: none;
        }
    }

    @media (max-width: 640px) {
        .paket-card:hover {
            transform: translateY(-4px);
        }
        .paket-card.selected {
            transform: scale(1.01);
        }
    }
</style>
@endpush
{{-- ================= HERO SECTION - WITH ANIMATION ================= --}}
<section class="qurban-hero-soft relative overflow-hidden min-h-screen pt-6 md:pt-20">
    <div class="container mx-auto px-4 md:px-8 lg:px-20 pt-12 md:pt-20 pb-16 md:pb-20 relative z-10">
        <div class="max-w-5xl mx-auto text-center">
            
            <!-- MAIN BADGE IDUL ADHA - RESPONSIVE -->
            <div class="flex justify-center mb-6 md:mb-8 animate-fade-in-up">
                <div class="badge-main relative group" id="mainBadge">
                    <!-- Glow cerah -->
                    <div class="absolute inset-0 rounded-full bg-gradient-to-r from-emerald-200/50 via-amber-200/50 to-emerald-200/50 blur-xl opacity-0 group-hover:opacity-100 transition-all duration-500"></div>
                    
                    <!-- Badge responsive -->
                    <div class="relative bg-gradient-to-r from-emerald-100/60 via-amber-50/60 to-emerald-100/60 backdrop-blur-md rounded-full shadow-md group-hover:shadow-emerald-300/40 transition-all duration-300 group-hover:scale-[1.02] border border-white/60">
                        
                        <div class="flex items-center gap-1.5 md:gap-3 px-3 md:px-7 py-1.5 md:py-3.5">
                            
                            <!-- Icon kiri - ukuran responsive -->
                            <div class="bg-gradient-to-br from-emerald-400 to-emerald-500 rounded-full p-1 md:p-2 shadow-sm">
                                <i class="fas fa-star-of-life text-white text-xs md:text-base lg:text-lg"></i>
                            </div>
                            
                            <!-- Teks - ukuran responsive diperkecil -->
                            <span class="text-sm sm:text-base md:text-xl lg:text-2xl font-black tracking-wide text-emerald-700 drop-shadow-sm">
                                ✨ IDUL ADHA 1447 H ✨
                            </span>
                            
                            <!-- Icon kanan - ukuran responsive -->
                            <div class="bg-gradient-to-br from-teal-400 to-teal-500 rounded-full p-1 md:p-2 shadow-sm">
                                <i class="fas fa-mosque text-white text-xs md:text-base lg:text-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Hero Title dengan ANIMASI pada teks "Qurban" -->
            <h1 class="text-3xl sm:text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-extrabold mb-4 md:mb-6 leading-tight md:leading-[1.1] animate-fade-in-up max-w-4xl mx-auto text-slate-900 px-2">
                {{ $heroTitle ?? 'Fasilitas' }}
                <span class="block bg-gradient-to-r from-emerald-700 via-teal-600 to-amber-500 bg-clip-text text-transparent mt-1 md:mt-2 relative inline-block">
                    {{ $heroSubtitle ?? 'Qurban untuk Umat' }}
                    <!-- Animated underline effect -->
                    <svg class="absolute -bottom-2 left-0 w-full" height="8" viewBox="0 0 300 8" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0 4 C50 0, 100 8, 150 4 C200 0, 250 8, 300 4" stroke="url(#gradient)" stroke-width="2" fill="none" stroke-dasharray="300" stroke-dashoffset="300">
                            <animate attributeName="stroke-dashoffset" from="300" to="0" dur="1.5s" fill="freeze"/>
                            <animate attributeName="opacity" values="0;1" dur="1s" fill="freeze"/>
                        </path>
                        <defs>
                            <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                <stop offset="0%" stop-color="#059669"/>
                                <stop offset="50%" stop-color="#f59e0b"/>
                                <stop offset="100%" stop-color="#ea580c"/>
                                <animate attributeName="x1" values="0%;100%;0%" dur="3s" repeatCount="indefinite"/>
                            </linearGradient>
                        </defs>
                    </svg>
                </span>
            </h1>
            
            <!-- Subtitle dengan ANIMASI TYPING EFFECT pada teks penting -->
            <div class="max-w-3xl mx-auto mb-8 md:mb-10 px-3">
                <p class="text-slate-600 text-sm sm:text-base md:text-lg lg:text-xl leading-relaxed animate-fade-in-up animation-delay-200">
                    <span class="font-semibold text-emerald-700">Masjid Raudhotul Jannah</span> 
                    memfasilitasi, membantu penyediaan, serta menyalurkan hewan qurban Anda dengan 
                    <span class="relative inline-block group">
                        <span class="bg-gradient-to-r from-emerald-600 to-teal-600 bg-clip-text text-transparent font-semibold animate-pulse">amanah & transparan</span>
                        <svg class="absolute -bottom-1 left-0 w-full h-0.5 bg-gradient-to-r from-emerald-400 to-teal-400 scale-x-0 group-hover:scale-x-100 transition-transform duration-300 origin-left" viewBox="0 0 100 2">
                            <line x1="0" y1="1" x2="100" y2="1" stroke="currentColor" stroke-width="2" stroke-dasharray="4 4">
                                <animate attributeName="stroke-dashoffset" from="8" to="0" dur="0.5s" repeatCount="indefinite"/>
                            </line>
                        </svg>
                    </span>.
                </p>
                <p class="text-amber-700 font-semibold text-sm md:text-base mt-3 animate-pulse">
                    <i class="fas fa-quote-right mr-1"></i> 
                    "Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah." 
                    <i class="fas fa-quote-left ml-1"></i>
                    <span class="block text-xs text-slate-400 mt-1">(QS. Al-An'am: 162)</span>
                </p>
            </div>
            
            <!-- VALUE PROPOSITION CARDS - SIMPLE ISLAMI -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 md:gap-6 mb-10 md:mb-12 animate-fade-in-up animation-delay-300 max-w-4xl mx-auto">
                
                <!-- Card 1 -->
                <div class="group bg-white/90 backdrop-blur rounded-2xl md:rounded-3xl px-3 py-3 md:px-5 md:py-4 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-emerald-100">
                    <div class="flex items-center justify-center sm:justify-start gap-2 md:gap-3">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-emerald-100 flex items-center justify-center text-lg md:text-xl group-hover:bg-emerald-500 transition-all duration-300">
                            <i class="fas fa-hand-sparkles text-emerald-600 group-hover:text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm md:text-base font-bold text-slate-800">🤍 Ikhlas karena Allah</p>
                            <p class="text-xs text-slate-500">Lillahi ta'ala</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 2 -->
                <div class="group bg-white/90 backdrop-blur rounded-2xl md:rounded-3xl px-3 py-3 md:px-5 md:py-4 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-amber-100">
                    <div class="flex items-center justify-center sm:justify-start gap-2 md:gap-3">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-amber-100 flex items-center justify-center text-lg md:text-xl group-hover:bg-amber-500 transition-all duration-300">
                            <i class="fas fa-people-arrows text-amber-600 group-hover:text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm md:text-base font-bold text-slate-800">📿 Untuk Fakir Miskin</p>
                            <p class="text-xs text-slate-500">Prioritas mustahik</p>
                        </div>
                    </div>
                </div>
                
                <!-- Card 3 -->
                <div class="group bg-white/90 backdrop-blur rounded-2xl md:rounded-3xl px-3 py-3 md:px-5 md:py-4 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border border-teal-100">
                    <div class="flex items-center justify-center sm:justify-start gap-2 md:gap-3">
                        <div class="w-10 h-10 md:w-12 md:h-12 rounded-full bg-teal-100 flex items-center justify-center text-lg md:text-xl group-hover:bg-teal-500 transition-all duration-300">
                            <i class="fas fa-chart-line text-teal-600 group-hover:text-white"></i>
                        </div>
                        <div>
                            <p class="text-sm md:text-base font-bold text-slate-800">✨ Pahala Berlipat Ganda</p>
                            <p class="text-xs text-slate-500">10 sampai 700 kali</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Buttons - responsive -->
            <div class="flex flex-col sm:flex-row justify-center gap-3 md:gap-4 animate-fade-in-up animation-delay-400 px-4">
                <a href="#paket-qurban" class="group inline-flex items-center justify-center gap-2 md:gap-3 px-5 md:px-8 py-3 md:py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold text-sm md:text-lg rounded-xl md:rounded-2xl shadow-xl shadow-emerald-800/30 transition-all hover:scale-105">
                    <i class="fas fa-hand-holding-heart text-sm md:text-base"></i>
                    <span>Daftar Qurban Sekarang</span>
                    <i class="fas fa-arrow-right text-sm md:text-base group-hover:translate-x-1 transition-transform duration-300"></i>
                </a>
                <a href="#info-qurban" class="inline-flex items-center justify-center gap-2 md:gap-3 px-5 md:px-8 py-3 md:py-4 bg-white border-2 border-emerald-200 hover:border-emerald-300 text-emerald-700 font-semibold text-sm md:text-lg rounded-xl md:rounded-2xl transition-all hover:shadow-md hover:-translate-y-0.5">
                    <i class="fas fa-info-circle text-sm md:text-base"></i>
                    <span>Info Lengkap</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Bottom Gradient -->
    <div class="absolute inset-x-0 bottom-0 h-32 md:h-40 bg-gradient-to-t from-white to-transparent"></div>
</section>

{{-- ================= PAKET QURBAN & FORM ================= --}}
<section id="paket-qurban" class="qurban-section py-14 md:py-20">
    <div class="container mx-auto px-5 md:px-10 lg:px-20">
        <div class="grid lg:grid-cols-[1fr_auto] items-end gap-5 mb-10">
            <div>
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wide">
                    <i class="fas fa-star text-amber-500 animate-pulse"></i> Fasilitas Tahun Ini
                </span>
                <h2 class="text-3xl md:text-5xl font-extrabold text-slate-900 mt-4">Hewan Qurban yang Difasilitasi</h2>
                <p class="text-slate-600 max-w-2xl mt-3">Pilih hewan yang sesuai, isi data jamaah, dan kami akan membantu proses hingga penyaluran.</p>
            </div>
            <div class="hidden lg:flex items-center gap-3 rounded-lg border border-emerald-200 bg-white px-4 py-3 shadow-sm hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center animate-pulse">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div>
                    <p class="text-sm font-bold text-slate-800">Amanah Panitia</p>
                    <p class="text-xs text-slate-500">Potong, kemas, dan distribusi</p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-[minmax(0,1.05fr)_minmax(380px,0.95fr)] gap-8 lg:gap-10 xl:gap-12">
            {{-- KIRI: Daftar Paket --}}
            <div>
                <div class="grid gap-5" id="paket-list">
                    @forelse($qurbans as $paket)
                    @php $paketImage = $paket->cover_image_url; @endphp
                    <div class="paket-card relative bg-white border-2 border-slate-200 hover:border-amber-400 cursor-pointer overflow-hidden shadow-sm hover:shadow-xl rounded-xl"
                         data-id="{{ $paket->id }}"
                         data-jenis="{{ $paket->jenis_hewan }}"
                         data-harga="{{ $paket->harga }}"
                         data-max-share="{{ $paket->max_share }}"
                         data-stok="{{ $paket->stok }}"
                         data-filter-kind="{{ $paket->jenis_hewan }}"
                         data-filter-collective="{{ $paket->max_share > 1 ? 'true' : 'false' }}"
                         data-nama="{{ ucfirst($paket->jenis_hewan) }} - {{ $paket->max_share == 1 ? '1 Ekor' : 'Kolektif ' . $paket->max_share . ' Orang' }}">
                        
                        @if($paket->max_share > 1)
                        <div class="absolute -top-1 -left-1 z-20">
                            <div class="relative">
                                <div class="relative bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] md:text-xs font-bold px-3 py-1 rounded-br-2xl rounded-tl-2xl shadow-lg flex items-center gap-1 animate-pulse">
                                    <span>👥</span><span>KOLEKTIF</span>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        @if($paketImage)
                        <div class="relative h-40 overflow-hidden bg-slate-100">
                            <img src="{{ $paketImage }}" alt="{{ ucfirst($paket->jenis_hewan) }}" class="w-full h-full object-cover transition-transform duration-500 hover:scale-110">
                            <div class="absolute inset-0 bg-gradient-to-t from-slate-950/60 via-transparent to-transparent"></div>
                            <div class="absolute bottom-3 left-4 right-4 flex items-center justify-between gap-3 text-white">
                                <span class="text-xs font-bold uppercase tracking-wide">Tersedia</span>
                                <span class="text-xs px-2 py-1 rounded bg-white/20 backdrop-blur">{{ $paket->stok }} slot</span>
                            </div>
                        </div>
                        @endif

                        <div class="p-5 sm:p-6 relative z-10">
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 mb-4">
                                <div class="flex items-center gap-3">
                                    <div class="relative">
                                        <div class="icon-hewan w-14 h-14 rounded-lg bg-gradient-to-br from-emerald-100 to-white border border-emerald-200 flex items-center justify-center text-3xl shadow-md transition-all duration-300">
                                            {{ $paket->jenis_icon }}
                                        </div>
                                    </div>
                                    <div>
                                        <h3 class="font-black text-slate-800 text-xl md:text-2xl">{{ ucfirst($paket->jenis_hewan) }}</h3>
                                        <span class="text-xs px-2 py-0.5 rounded bg-emerald-100 text-emerald-700 font-semibold">
                                            {{ $paket->max_share == 1 ? '1 Ekor' : '1/' . $paket->max_share . ' Bagian' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-left sm:text-right">
                                    <p class="text-2xl md:text-3xl font-black bg-gradient-to-r from-emerald-700 to-teal-700 bg-clip-text text-transparent">
                                        {{ $paket->harga_formatted }}
                                    </p>
                                </div>
                            </div>
                            
                            @if($paket->berat_range)
                            <div class="bg-gradient-to-r from-slate-50 to-emerald-50/50 rounded-lg p-3 mb-4 flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-weight-hanging text-emerald-600"></i>
                                    <span class="font-semibold text-slate-700 text-sm">{{ $paket->berat_range }}</span>
                                </div>
                                <div class="flex items-center gap-1">
                                    <i class="fas fa-check-circle text-emerald-500 text-xs"></i>
                                    <span class="text-xs text-slate-500">Fasilitasi Potong & Distribusi</span>
                                </div>
                            </div>
                            @endif
                            
                            @if($paket->deskripsi_singkat)
                            <p class="text-sm text-slate-500 mb-4 leading-relaxed line-clamp-2">{{ $paket->deskripsi_singkat }}</p>
                            @endif
                            
                            @if($paket->max_share > 1)
                            <div class="bg-amber-50/70 rounded-lg p-3 mb-4 border border-amber-200">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <i class="fas fa-users text-amber-600"></i>
                                        <span class="text-xs font-bold text-amber-800">Kolektif {{ $paket->max_share }} peserta</span>
                                    </div>
                                    <span class="text-[11px] text-amber-700">Per peserta 1/{{ $paket->max_share }} bagian</span>
                                </div>
                                <div class="mt-2 h-1.5 bg-amber-200 rounded-full overflow-hidden">
                                    <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500 rounded-full" style="width: {{ min(100, max(8, (($paket->max_share - $paket->stok) / max(1, $paket->max_share)) * 100)) }}%"></div>
                                </div>
                            </div>
                            @endif
                            
                            <div class="flex items-center justify-between mt-3 pt-3 border-t border-dashed border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $paket->stok > 0 ? 'bg-emerald-500 animate-pulse' : 'bg-red-500' }}"></span>
                                    <span class="text-xs font-medium {{ $paket->stok > 0 ? 'text-emerald-600' : 'text-red-500' }}">
                                        {{ $paket->stok > 0 ? 'Tersedia' : 'Habis' }}
                                    </span>
                                </div>
                                <button class="btn-pilih pilih-paket-btn px-5 py-2 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-amber-500 hover:to-orange-500 text-white text-sm font-bold rounded-lg shadow-md transition-all flex items-center gap-1">
                                    <span>Pilih Paket</span> <i class="fas fa-chevron-right text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-16 bg-gradient-to-br from-slate-50 to-emerald-50 rounded-lg">
                        <div class="text-7xl mb-4 animate-bounce">🐏</div>
                        <h3 class="text-slate-700 font-bold text-lg">Belum Ada Paket Qurban</h3>
                        <p class="text-slate-400 text-sm mt-1">Silakan cek kembali nanti</p>
                    </div>
                    @endforelse
                </div>
                <div id="no-paket-filtered" class="hidden text-center py-12 bg-white border border-dashed border-emerald-200 rounded-lg">
                    <div class="text-4xl mb-3">⌕</div>
                    <h3 class="text-slate-700 font-bold">Paket tidak ditemukan</h3>
                    <p class="text-slate-500 text-sm mt-1">Coba pilih kategori paket lain.</p>
                </div>
            </div>

            {{-- KANAN: Form Pendaftaran --}}
            <div id="form-pendaftaran">
                <div class="form-shell bg-white shadow-2xl shadow-emerald-950/10 border border-emerald-100 overflow-hidden rounded-xl sticky top-24">
                    <div class="bg-gradient-to-r from-emerald-700 to-teal-700 px-5 py-4 md:px-6 md:py-5 relative overflow-hidden">
                        <div class="absolute inset-x-0 bottom-0 h-1 bg-gradient-to-r from-amber-400 via-orange-300 to-amber-400"></div>
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center animate-bounce">
                                <i class="fas fa-clipboard-list text-white text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-lg md:text-xl">Formulir Pendaftaran</h3>
                                <p class="text-emerald-100 text-xs md:text-sm">Isi data jamaah dengan benar</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-5 md:p-8">
                        <div id="selected-paket-indicator" class="hidden mb-5 p-3 bg-amber-50 rounded-lg border border-amber-200">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-check-circle text-amber-600"></i>
                                <span class="text-sm font-semibold text-amber-800">Hewan dipilih:</span>
                                <span id="selected-paket-name" class="text-sm font-medium text-slate-700"></span>
                            </div>
                        </div>

                        <div id="live-total-card" class="hidden mb-5 rounded-lg border border-emerald-100 bg-gradient-to-br from-emerald-50 to-white p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wide text-emerald-700">Total yang harus ditransfer</p>
                                    <p id="live-total-name" class="text-sm text-slate-500 mt-1">Pilih hewan terlebih dahulu</p>
                                </div>
                                <div class="w-10 h-10 rounded-lg bg-emerald-600 text-white flex items-center justify-center animate-pulse">
                                    <i class="fas fa-calculator"></i>
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap items-end justify-between gap-2">
                                <p id="live-total-price" class="text-2xl font-black text-slate-900">Rp0</p>
                                <p id="live-total-share" class="text-xs font-semibold text-emerald-700 bg-emerald-100 px-2 py-1 rounded">1 bagian</p>
                            </div>
                        </div>
                        
                        <form id="formQurban" action="{{ route('qurban.register.store') }}" method="POST" class="space-y-5">
                            @csrf
                            <input type="hidden" name="qurban_id" id="selected_paket_id">
                            
                            @if(session('error'))
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                                <p class="text-red-700 text-sm">{{ session('error') }}</p>
                            </div>
                            @endif
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Hewan Pilihan <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select id="paket_display" class="w-full px-4 py-3 bg-gray-50 border-2 border-gray-300 rounded-xl text-slate-700 appearance-none cursor-not-allowed focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all" disabled>
                                        <option value="">-- Pilih hewan dari kartu di samping --</option>
                                    </select>
                                    <div class="absolute right-3 top-3 pointer-events-none">
                                        <i class="fas fa-chevron-down text-emerald-500"></i>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-400 mt-1 lg:hidden">📱 Pilih hewan dari daftar di atas</p>
                                <p class="text-xs text-slate-400 mt-1 hidden lg:block">🖥️ Klik kartu hewan di samping untuk memilih</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Nama Lengkap <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i class="fas fa-user absolute left-4 top-4 text-slate-400"></i>
                                    <input type="text" name="nama_lengkap" class="w-full pl-11 pr-4 py-3 bg-white border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none" placeholder="Contoh: Ahmad bin Abdullah" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">No. WhatsApp <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <i class="fab fa-whatsapp absolute left-4 top-4 text-green-500"></i>
                                    <input type="tel" name="telepon" class="w-full pl-11 pr-4 py-3 bg-white border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none" placeholder="08123456789" required>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Alamat (Opsional)</label>
                                <div class="relative">
                                    <i class="fas fa-map-marker-alt absolute left-4 top-4 text-slate-400"></i>
                                    <textarea name="alamat" rows="2" class="w-full pl-11 pr-4 py-3 bg-white border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none" placeholder="Alamat lengkap domisili"></textarea>
                                </div>
                            </div>
                            
                            <div id="share-container" style="display:none;">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Jumlah Kolektif (patungan)</label>
                                <div class="flex flex-wrap items-center gap-3">
                                    <button type="button" class="share-step w-10 h-10 rounded-xl border-2 border-emerald-200 bg-emerald-50 text-emerald-700 font-bold hover:bg-emerald-100 transition-all" data-step="-1" aria-label="Kurangi jumlah">−</button>
                                    <input type="number" name="jumlah_share" id="jumlah_share" class="w-20 px-3 py-2 text-center font-bold bg-white border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none" value="1" min="1" max="7">
                                    <button type="button" class="share-step w-10 h-10 rounded-xl border-2 border-emerald-200 bg-emerald-50 text-emerald-700 font-bold hover:bg-emerald-100 transition-all" data-step="1" aria-label="Tambah jumlah">+</button>
                                    <span class="text-sm text-slate-500" id="share_info_text">(Max 7 orang)</span>
                                </div>
                                <p class="text-xs text-amber-600 mt-2"><i class="fas fa-info-circle"></i> Jika peserta tidak mencapai maksimal, akan dialihkan ke kambing + biaya Rp50.000</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Catatan (Opsional)</label>
                                <textarea name="catatan" rows="2" class="w-full px-4 py-3 bg-white border-2 border-gray-300 rounded-xl focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none" placeholder="Contoh: Qurban untuk almarhum/ah, atau membawa hewan sendiri"></textarea>
                            </div>
                            
                            <button type="submit" class="w-full py-3 sm:py-3.5 md:py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-500 hover:to-teal-500 text-white font-bold sm:font-extrabold rounded-xl shadow-lg shadow-emerald-900/20 flex items-center justify-center gap-1.5 sm:gap-2 md:gap-3 transition-all duration-300 transform hover:scale-[1.02] hover:shadow-xl">
                                <i class="fas fa-hand-holding-heart text-sm sm:text-base"></i>
                                
                                <!-- Teks berbeda di mobile vs desktop -->
                                <span class="text-sm sm:hidden">Daftar Qurban</span>
                                <span class="hidden sm:inline-block sm:text-base md:text-lg">Daftar Qurban Sekarang</span>
                                
                                <i class="fas fa-paper-plane text-sm sm:text-base"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= INFO QURBAN ================= --}}
<section id="info-qurban" class="qurban-info-section relative overflow-hidden py-14 md:py-20">
    <div class="container mx-auto px-5 md:px-10 lg:px-20">
        <div class="text-center mb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-lg bg-emerald-100 text-emerald-800 text-xs font-bold uppercase tracking-wide animate-pulse">
                <i class="fas fa-circle-info text-amber-500"></i> Detail Layanan
            </span>
            <h2 class="text-3xl md:text-4xl font-extrabold text-slate-800 mt-4">Informasi & Biaya Qurban</h2>
            <p class="text-slate-600 mt-3">Masjid Raudhotul Jannah - Taman Cipulir Estate</p>
        </div>
        
        <div class="grid md:grid-cols-2 gap-6 lg:gap-8 mb-6">
            <!-- Kontak & Bank -->
            <div class="bg-white rounded-2xl shadow-xl p-5 md:p-8 border border-emerald-100 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-xl bg-emerald-100 flex items-center justify-center text-2xl">
                        <i class="fas fa-phone-alt text-emerald-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Kontak Panitia & Rekening</h3>
                </div>
                <div class="space-y-4">
                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 border-b border-emerald-100 pb-3">
                        <div>
                            <p class="font-semibold text-slate-800 flex items-center gap-2">
                                📞 Informasi/Pendaftaran
                            </p>
                            <p class="text-emerald-700 font-mono text-sm break-all mt-1 flex items-center gap-1 flex-wrap">
                                <span>{{ $contactInfoName ?? 'Joko Santoso' }}</span>
                                <span>-</span>
                                <span class="font-bold">{{ $contactInfoPhone ?? '0857 1650 3815' }}</span>
                            </p>
                        </div>
                        <a href="https://wa.me/{{ $waInfoPhone }}" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm text-center inline-flex items-center justify-center gap-2 w-full sm:w-auto transition-all duration-300 hover:scale-105 shadow-lg group">
                            <span class="font-semibold">Chat via WhatsApp</span>
                            <i class="fas fa-arrow-right text-xs group-hover:translate-x-1 transition-transform duration-300"></i>
                        </a>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 border-b border-emerald-100 pb-3">
                        <div>
                            <p class="font-semibold text-slate-800 flex items-center gap-2">
                                💬 Konfirmasi Transfer
                            </p>
                            <p class="text-amber-700 font-mono text-sm break-all mt-1 flex items-center gap-1 flex-wrap">
                                <span>{{ $contactConfirmName ?? 'Jazuli' }}</span>
                                <span>-</span>
                                <span class="font-bold">{{ $contactConfirmPhone ?? '0813 1018 5948' }}</span>
                            </p>
                        </div>
                        <a href="https://wa.me/{{ $waConfirmPhone }}" class="bg-gradient-to-r from-green-500 to-emerald-600 hover:from-green-600 hover:to-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm text-center inline-flex items-center justify-center gap-2 w-full sm:w-auto transition-all duration-300 hover:scale-105 shadow-lg group">
                            <span class="font-semibold">Konfirmasi via WhatsApp</span>
                            <i class="fas fa-check-circle text-xs group-hover:scale-110 transition-transform duration-300"></i>
                        </a>
                    </div>
                    <div class="bg-gradient-to-r from-slate-800 to-emerald-800 rounded-xl p-4 text-white shadow-md relative overflow-hidden group">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-amber-300 via-emerald-300 to-teal-300"></div>
                        
                        <div class="relative z-10 flex flex-col gap-3">
                            <!-- Header Bank -->
                            <div class="flex items-center gap-2">
                                <i class="fas fa-university text-2xl text-amber-300"></i>
                                <span class="font-bold text-lg">{{ $bankName ?? 'Bank Mandiri' }}</span>
                                <span class="text-xs bg-white/20 px-2 py-0.5 rounded-full">Transfer</span>
                            </div>
                            
                            <!-- Nomor Rekening dengan Copy Button -->
                            <div class="flex flex-wrap items-center justify-between gap-2 bg-white/10 rounded-xl p-3 border border-white/20 group-hover:bg-white/20 transition-all duration-300">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <i class="fas fa-credit-card text-amber-300 text-lg"></i>
                                    <span class="font-mono text-base md:text-xl font-bold text-amber-100 tracking-wider select-all break-all">
                                        {{ $bankAccount ?? '1010 0109 47479' }}
                                    </span>
                                </div>
                                <button class="copy-rek-btn bg-white text-slate-900 hover:bg-amber-100 px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 transition-all duration-200 hover:scale-105 shadow-md" 
                                        data-account="{{ str_replace(' ', '', $bankAccount ?? '1010010947479') }}">
                                    <i class="far fa-copy"></i> 
                                    <span class="btn-text">Salin No. Rekening</span>
                                </button>
                            </div>
                            
                            <!-- Nama Pemilik Rekening -->
                            <div class="flex items-center gap-2 text-emerald-100">
                                <i class="fas fa-user-check text-amber-300"></i>
                                <span>a.n. {{ $bankAccountName ?? 'Jazuli' }}</span>
                            </div>
                            
                            <!-- Informasi Penting -->
                            <div class="flex items-start gap-2 text-xs text-emerald-200 bg-white/5 rounded-lg p-2 mt-1">
                                <i class="fas fa-info-circle text-amber-300 mt-0.5"></i>
                                <p>✅ Transfer sesuai nominal, lalu konfirmasi via WhatsApp ke nomor yang tersedia</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Harga & Biaya Potong -->
            <div class="bg-white rounded-2xl shadow-xl p-5 md:p-8 border border-emerald-100 hover:shadow-2xl transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center gap-3 mb-5">
                    <div class="w-12 h-12 rounded-xl bg-amber-100 flex items-center justify-center text-2xl">
                        <i class="fas fa-hand-holding-usd text-amber-600"></i>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800">Rincian Harga & Biaya Potong</h3>
                </div>
                <div class="space-y-4">
                    @foreach($qurbans as $paket)
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 p-3 bg-emerald-50 rounded-xl border border-emerald-100 hover:bg-emerald-100 transition-all duration-300">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-2xl">{{ $paket->jenis_icon }}</span>
                            <span class="font-bold text-slate-800">{{ ucfirst($paket->jenis_hewan) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded bg-emerald-200 text-emerald-800">{{ $paket->max_share == 1 ? '1 Ekor' : '1/' . $paket->max_share . ' Bagian' }}</span>
                        </div>
                        <div>
                            <span class="text-xl font-black text-emerald-700">{{ $paket->harga_formatted }}</span>
                        </div>
                    </div>
                    @endforeach
                    
                    @php
                        $potongSapiClean = (int) preg_replace('/[^0-9]/', '', $potongSapi);
                        $potongKambingClean = (int) preg_replace('/[^0-9]/', '', $potongKambing);
                    @endphp
                    
                    <div class="mt-4 pt-4 border-t border-dashed border-emerald-200">
                        <div class="flex flex-wrap items-center gap-2 mb-4">
                            <span class="text-xl">📌</span>
                            <p class="text-sm font-bold text-amber-800">Biaya Potong & Distribusi</p>
                            <span class="text-xs px-2 py-0.5 rounded bg-amber-200 text-amber-800 font-semibold animate-pulse">Jika Membawa Hewan Sendiri</span>
                        </div>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-amber-50 rounded-xl p-4 text-center border border-amber-200 hover:bg-amber-100 transition-all duration-300 hover:scale-105">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <span class="text-2xl">🐮</span>
                                    <span class="font-bold text-slate-800">Sapi</span>
                                </div>
                                <span class="text-xl font-extrabold text-emerald-700">Rp{{ number_format($potongSapiClean, 0, ',', '.') }}</span>
                            </div>
                            <div class="bg-amber-50 rounded-xl p-4 text-center border border-amber-200 hover:bg-amber-100 transition-all duration-300 hover:scale-105">
                                <div class="flex items-center justify-center gap-2 mb-2">
                                    <span class="text-2xl">🐐</span>
                                    <span class="font-bold text-slate-800">Kambing</span>
                                </div>
                                <span class="text-xl font-extrabold text-emerald-700">Rp{{ number_format($potongKambingClean, 0, ',', '.') }}</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 flex items-center justify-center gap-2 text-xs text-amber-700 bg-amber-50 p-2 rounded-lg">
                            <span>💡</span>
                            <span>Biaya sudah termasuk pemotongan, pengemasan, dan distribusi oleh panitia</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="relative mt-12 md:mt-16 lg:mt-20">
            <!-- Main Card -->
            <div class="bg-gradient-to-br from-white via-amber-50/30 to-orange-50/20 rounded-2xl shadow-2xl border-2 border-amber-200 overflow-hidden hover:shadow-amber-200/50 transition-all duration-300">
                
                <!-- Header dengan warna lebih hidup -->
                <div class="bg-gradient-to-r from-amber-400 via-orange-400 to-amber-500 px-6 md:px-8 py-5">
                    <div class="flex items-center gap-4">
                        <div class="relative">
                            <div class="absolute inset-0 bg-white rounded-xl blur-md opacity-50 animate-ping"></div>
                            <div class="relative w-12 h-12 md:w-14 md:h-14 rounded-xl bg-white/20 backdrop-blur flex items-center justify-center text-2xl md:text-3xl shadow-lg">
                                📋
                            </div>
                        </div>
                        <div>
                            <h3 class="font-black text-white text-xl md:text-2xl drop-shadow-md">Catatan Penting</h3>
                            <p class="text-amber-100 text-sm">Simak informasi berikut sebelum mendaftar</p>
                        </div>
                    </div>
                </div>
                
                <!-- List dengan warna lebih cerah -->
                <div class="p-6 md:p-8 space-y-4">
                    @foreach($importantNotes ?? [
                        '⏰ Pendaftaran paling lambat H-3 sebelum Sholat Idul Adha.',
                        '🐫 Penyerahan hewan (bagi yang membawa sendiri): H-1 sebelum Sholat Idul Adha.',
                        '💰 Biaya potong dan distribusi khusus yang membawa hewan sendiri: Kambing Rp 300.000, Sapi Rp 1.900.000.',
                        '🔄 Jika penggurban sapi secara kolektif tidak mencapai 7 orang, akan dialihkan ke kambing dengan menambah biaya sebesar Rp 50.000.'
                    ] as $index => $note)
                    <div class="flex items-start gap-4 p-4 rounded-xl bg-gradient-to-r from-white to-amber-50/50 hover:from-amber-50 hover:to-orange-100 border border-amber-100 hover:border-amber-300 shadow-sm hover:shadow-md transition-all duration-300 group cursor-pointer transform hover:-translate-x-1">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 group-hover:scale-110 transition-all duration-300 flex items-center justify-center shadow-md">
                                <span class="text-white text-sm font-bold">{{ $index + 1 }}</span>
                            </div>
                        </div>
                        <p class="text-slate-700 text-sm md:text-base leading-relaxed flex-1 font-medium">
                            {{ $note }}
                        </p>
                        <div class="flex-shrink-0 opacity-0 group-hover:opacity-100 transition-all duration-300 transform group-hover:translate-x-1">
                            <div class="w-6 h-6 rounded-full bg-amber-200 flex items-center justify-center">
                                <i class="fas fa-check text-amber-600 text-xs"></i>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <!-- Footer dengan warna menarik -->
                <div class="bg-gradient-to-r from-amber-500 via-orange-500 to-amber-500 px-6 md:px-8 py-4">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <div class="w-8 h-8 rounded-full bg-white/20 backdrop-blur flex items-center justify-center animate-bounce">
                                    <i class="fas fa-clock text-white text-sm"></i>
                                </div>
                            </div>
                            <span class="text-white font-semibold text-sm md:text-base">
                                🤲 Semoga Allah mudahkan langkah kita dalam berqurban
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ -->
        <div class="mt-10">
            <div class="text-center mb-6">
                <h3 class="text-2xl md:text-3xl font-bold text-slate-800">Pertanyaan Seputar Qurban</h3>
            </div>
            <div class="grid sm:grid-cols-2 gap-5">
                @foreach($faqItems ?? [
                    ['question' => 'Bagaimana jika patungan sapi kurang dari 7 orang?', 'answer' => 'Akan dialihkan ke qurban kambing dengan tambahan biaya Rp 50.000 sesuai ketentuan panitia.'],
                    ['question' => 'Apakah harga sudah termasuk distribusi?', 'answer' => 'Ya, harga paket yang difasilitasi Masjid sudah termasuk biaya pemotongan & distribusi ke mustahik.'],
                    ['question' => 'Bisa titip hewan sendiri?', 'answer' => 'Bisa, penyerahan H-1 Idul Adha dengan biaya potong sesuai ketentuan di atas.'],
                    ['question' => 'Batas pendaftaran?', 'answer' => 'Paling lambat H-3 sebelum sholat Idul Adha.']
                ] as $faq)
                <div class="faq-item bg-white rounded-xl p-5 shadow-sm border border-emerald-100 cursor-pointer transition hover:shadow-md">
                    <div class="flex gap-3 items-start">
                        <span class="text-emerald-600 font-bold text-xl">Q.</span>
                        <div class="flex-1">
                            <div class="flex items-start justify-between gap-3">
                                <h4 class="font-semibold text-slate-800">{{ $faq['question'] }}</h4>
                                <span class="faq-icon shrink-0 w-7 h-7 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center transition-transform">
                                    <i class="fas fa-plus text-xs"></i>
                                </span>
                            </div>
                            <p class="faq-answer text-sm text-slate-600 leading-relaxed">{{ $faq['answer'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    let selectedPaket = null;
    
    $('#selected-paket-indicator').hide();

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0
        }).format(Number(angka || 0));
    }

    function getShareValue() {
        const $input = $('#jumlah_share');
        const maxShare = Number($input.attr('max') || selectedPaket?.maxShare || 1);
        const currentShare = Number($input.val() || 1);
        const cleanShare = Math.min(Math.max(currentShare, 1), Math.max(maxShare, 1));

        $input.val(cleanShare);
        return cleanShare;
    }

    $('.copy-rek-btn').on('click', function() {
        const account = $(this).data('account');
        const $btn = $(this);
        const $btnText = $btn.find('.btn-text');
        const originalText = $btnText.text();
        
        // Copy ke clipboard
        navigator.clipboard.writeText(account).then(() => {
            // Ubah teks tombol sementara
            $btnText.html('<i class="fas fa-check"></i> Tersalin!');
            $btn.addClass('bg-green-600');
            
            // Reset setelah 2 detik
            setTimeout(() => {
                $btnText.html(originalText);
                $btn.removeClass('bg-green-600');
            }, 2000);
            
            // SweetAlert notifikasi
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Nomor rekening telah disalin',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                background: '#1f2937',
                color: '#ffffff'
            });
        }).catch(() => {
            Swal.fire({
                icon: 'error',
                title: 'Gagal!',
                text: 'Silakan salin manual',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500
            });
        });
    });

    function updateLiveTotal() {
        if (!selectedPaket) return;

        const share = getShareValue();
        const total = Number(selectedPaket.harga || 0) * share;

        $('#live-total-name').text(selectedPaket.nama);
        $('#live-total-price').text(formatRupiah(total));
        $('#live-total-share').text(`${share} ${share > 1 ? 'bagian' : 'bagian'}`);
        $('#live-total-card').removeClass('hidden');
    }
    
    function updateFormWithPaket(paketId, paketJenis, paketHarga, paketMaxShare, paketNama) {
        const cleanHarga = Number(paketHarga || 0);
        const cleanMaxShare = Number(paketMaxShare || 1);

        selectedPaket = { id: paketId, jenis: paketJenis, harga: cleanHarga, maxShare: cleanMaxShare, nama: paketNama };
        $('#selected_paket_id').val(paketId);
        $('#paket_display').html(`<option selected>${paketNama} (${formatRupiah(cleanHarga)})</option>`);
        $('#selected-paket-name').text(paketNama);
        $('#selected-paket-indicator').fadeIn(200);
        
        if ((paketJenis === 'sapi' || paketJenis === 'kerbau') && cleanMaxShare > 1) {
            $('#share-container').slideDown(200);
            $('#jumlah_share').attr('max', cleanMaxShare).val(1);
            $('#share_info_text').text(`(Max ${cleanMaxShare} orang)`);
        } else {
            $('#share-container').slideUp(200);
            $('#jumlah_share').attr('max', 1).val(1);
        }

        updateLiveTotal();
    }

    // Pilih Paket (tanpa filter)
    $('.paket-card').on('click', function() {
        const $card = $(this);
        const paketId = $card.data('id');
        const paketJenis = $card.data('jenis');
        const paketHarga = $card.data('harga');
        const paketMaxShare = $card.data('max-share');
        const paketNama = $card.data('nama');
        
        $('.paket-card').removeClass('selected');
        $card.addClass('selected');
        
        updateFormWithPaket(paketId, paketJenis, paketHarga, paketMaxShare, paketNama);
        
        if (window.innerWidth < 1024) {
            document.getElementById('form-pendaftaran').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
        
        Swal.fire({
            icon: 'success',
            title: 'Hewan Dipilih!',
            text: paketNama,
            confirmButtonColor: '#10b981',
            timer: 1500,
            showConfirmButton: true
        });
    });
    
    $('.pilih-paket-btn').on('click', function(e) {
        e.stopPropagation();
        $(this).closest('.paket-card').trigger('click');
    });

    $('.share-step').on('click', function() {
        if (!selectedPaket) return;

        const $input = $('#jumlah_share');
        const step = Number($(this).data('step') || 0);
        const maxShare = Number($input.attr('max') || selectedPaket.maxShare || 1);
        const nextShare = Math.min(Math.max(Number($input.val() || 1) + step, 1), Math.max(maxShare, 1));

        $input.val(nextShare);
        updateLiveTotal();
    });

    $('#jumlah_share').on('input change', updateLiveTotal);

    // FAQ toggle
    $('.faq-item').on('click', function() {
        $(this).toggleClass('open');
    });

    $('.faq-item').first().addClass('open');
    
    $('#formQurban').on('submit', function(e) {
        if (!selectedPaket) {
            e.preventDefault();
            Swal.fire({ title: 'Belum Memilih Hewan', text: 'Silakan klik kartu hewan qurban terlebih dahulu', icon: 'warning', confirmButtonColor: '#f59e0b' });
            return false;
        }
        
        const nama = $('input[name="nama_lengkap"]').val().trim();
        const wa = $('input[name="telepon"]').val().trim();
        
        if (!nama || !wa) {
            e.preventDefault();
            Swal.fire('Perhatian', 'Nama & No WhatsApp wajib diisi', 'warning');
            return false;
        }
        
        return true;
    });
    
    $('.copy-btn').on('click', function() {
        const account = $(this).data('account');
        const $btnText = $(this).find('.btn-text');
        const originalText = $btnText.text();
        
        navigator.clipboard.writeText(account).then(() => {
            $btnText.text('Tersalin!');
            setTimeout(() => $btnText.text(originalText), 2000);
            Swal.fire({ toast: true, icon: 'success', title: 'Rekening tersalin', position: 'top-end', showConfirmButton: false, timer: 1000 });
        }).catch(() => {
            alert('Gagal menyalin, silakan salin manual');
        });
    });

    // Interaktif badge hover effect
    $('#mainBadge').on('click', function() {
        Swal.fire({
            title: '✨ IDUL ADHA 1447 H ✨',
            html: '🎉 Mari tunaikan ibadah qurban bersama Masjid Raudhotul Jannah 🎉<br><br>💝 "Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah."',
            icon: 'success',
            confirmButtonColor: '#f59e0b',
            background: 'linear-gradient(135deg, #1f2937, #064e3b)',
            color: '#ffffff',
            timer: 4000
        });
    });
});
</script>
@endpush