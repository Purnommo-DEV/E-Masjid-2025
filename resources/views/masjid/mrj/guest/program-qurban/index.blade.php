@extends('masjid.master-guest')

@section('title', 'Program Qurban - Masjid Raudhotul Jannah')

@section('content')

@push('style')
<style>
    /* Animations - Tetap diperlukan untuk animasi hero section */
    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(30px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    @keyframes pulse-slow {
        0%, 100% { opacity: 0.6; }
        50% { opacity: 1; }
    }
    
    .animate-fade-in-up { animation: fadeInUp 0.6s ease-out forwards; }
    .animate-float { animation: float 3s ease-in-out infinite; }
    .animate-pulse-slow { animation: pulse-slow 2s ease-in-out infinite; }
    .animation-delay-200 { animation-delay: 0.2s; }
    .animation-delay-400 { animation-delay: 0.4s; }
    
    /* Efek kartu saat selected dengan inline style akan tetap berjalan */
    .paket-card {
        position: relative;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Efek shimmer pada kartu saat hover (opsional) */
    .paket-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
        transition: left 0.5s;
        pointer-events: none;
    }

    .paket-card:hover::before {
        left: 100%;
    }

    /* Badge untuk kartu hemat */
    .paket-card .absolute {
        position: absolute;
    }
    
    /* Sticky form - Tetap diperlukan */
    .sticky-form {
        position: sticky;
        top: 100px;
    }
    
    /* Custom scrollbar - Tetap diperlukan */
    #paket-list::-webkit-scrollbar {
        width: 6px;
    }
    #paket-list::-webkit-scrollbar-track {
        background: #d1fae5;
        border-radius: 10px;
    }
    #paket-list::-webkit-scrollbar-thumb {
        background: #10b981;
        border-radius: 10px;
    }
    
    /* Form input focus - Tetap diperlukan */
    .input-custom:focus {
        outline: none;
        border-color: #10b981;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
    }
    
    /* ========== YANG DIPERLUKAN UNTUK EFEK SELECTED ========== */
    
    /* Tanda centang - Tetap pakai CSS (lebih bersih) */
    .paket-card.selected::after {
        content: "✓";
        position: absolute;
        top: 12px;
        right: 12px;
        background: #f59e0b;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: bold;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        z-index: 10;
    }
    
    /* Efek icon saat kartu dipilih */
    .paket-card.selected .w-14.h-14 {
        background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        transform: scale(1.05);
        transition: all 0.3s ease;
    }
    
    /* Efek harga saat kartu dipilih */
    .paket-card.selected .text-emerald-700 {
        color: #d97706 !important;
    }
    
    /* Efek badge saat kartu dipilih */
    .paket-card.selected .bg-emerald-100 {
        background-color: #fed7aa !important;
        color: #92400e !important;
    }
    
    /* Efek tombol pilih paket saat kartu dipilih */
    .paket-card.selected .pilih-paket-btn {
        background-color: #f59e0b !important;
        color: white !important;
        padding: 4px 12px;
        border-radius: 20px;
        transition: all 0.3s ease;
    }
    
    .paket-card.selected .pilih-paket-btn svg {
        stroke: white;
    }
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush

{{-- ================= HERO SECTION ================= --}}
<section class="relative overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-amber-50">
    <!-- Background Ornaments -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-emerald-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse-slow"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-amber-200 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse-slow" style="animation-delay: 1s;"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-teal-100 rounded-full filter blur-3xl opacity-20"></div>
        
        <svg class="absolute bottom-0 left-0 w-full h-32 text-white" preserveAspectRatio="none" viewBox="0 0 1440 120">
            <path fill="currentColor" d="M0,32L48,42.7C96,53,192,75,288,80C384,85,480,75,576,64C672,53,768,43,864,48C960,53,1056,75,1152,80C1248,85,1344,75,1392,69.3L1440,64L1440,120L1392,120C1344,120,1248,120,1152,120C1056,120,960,120,864,120C768,120,672,120,576,120C480,120,384,120,288,120C192,120,96,120,48,120L0,120Z"/>
        </svg>
    </div>

    <div class="container mx-auto px-4 md:px-8 lg:px-16 xl:px-24 py-12 md:py-16 lg:py-20 relative z-10">
        <div class="max-w-4xl mx-auto text-center">

            <!-- Badge -->
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 shadow-lg mb-6 animate-fade-in-up">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-white opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-white"></span>
                </span>
                <span class="text-xs md:text-sm font-bold text-white tracking-wide">✨ {{ $heroBadge }} ✨</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl xl:text-7xl font-extrabold text-slate-800 mb-6 leading-tight animate-fade-in-up">
                {{ $heroTitle }}
                <span class="block bg-gradient-to-r from-emerald-600 via-teal-600 to-amber-600 bg-clip-text text-transparent">
                    {{ $heroSubtitle }}
                </span>
            </h1>

            <p class="text-slate-600 text-base md:text-lg max-w-2xl mx-auto mb-8 animate-fade-in-up animation-delay-200">
                "Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah!" 
                <span class="text-amber-600 font-semibold">(QS. Al-Kautsar: 2)</span>
            </p>
            
            <div class="flex flex-wrap justify-center gap-4 animate-fade-in-up animation-delay-400">
                <a href="#paket-qurban" 
                   class="group inline-flex items-center gap-2 px-6 md:px-8 py-3 md:py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                    <span class="text-xl">🐏</span>
                    <span>Daftar Qurban Sekarang</span>
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
                <a href="#info-qurban" 
                   class="inline-flex items-center gap-2 px-6 md:px-8 py-3 md:py-4 bg-white border-2 border-emerald-500 hover:bg-emerald-50 text-emerald-700 font-semibold rounded-full transition-all duration-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Info Selengkapnya
                </a>
            </div>
        </div>
    </div>
</section>

{{-- ================= PAKET QURBAN & FORM ================= --}}
<section id="paket-qurban" class="py-16 md:py-20 bg-white">
    <div class="container mx-auto px-4 md:px-8 lg:px-16 xl:px-24">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4">Pilih Paket Qurban</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-emerald-500 to-amber-500 mx-auto rounded-full"></div>
            <p class="text-slate-600 mt-4 max-w-2xl mx-auto">Pilih jenis hewan qurban yang sesuai dengan kemampuan Anda</p>
        </div>
        
        <!-- Tambahkan di awal section, sebelum grid -->
        <div id="firstVisitGuide" class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl animate-fade-in-up">
                <div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-emerald-100 to-amber-100 flex items-center justify-center text-4xl mx-auto mb-4">
                        🐏
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Panduan Memilih Paket Qurban</h3>
                    <p class="text-slate-600 text-sm mb-4">Ikuti langkah mudah berikut:</p>
                    <div class="space-y-3 text-left mb-6">
                        <div class="flex items-center gap-3 p-2 bg-emerald-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">1</div>
                            <span class="text-slate-700">Klik kartu paket yang Anda inginkan</span>
                        </div>
                        <div class="flex items-center gap-3 p-2 bg-emerald-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">2</div>
                            <span class="text-slate-700">Kartu akan berubah warna menjadi keemasan</span>
                        </div>
                        <div class="flex items-center gap-3 p-2 bg-emerald-50 rounded-lg">
                            <div class="w-8 h-8 rounded-full bg-emerald-500 text-white flex items-center justify-center font-bold">3</div>
                            <span class="text-slate-700">Isi formulir pendaftaran di samping</span>
                        </div>
                    </div>
                    <button onclick="closeGuide()" class="w-full py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold">
                        Mulai Pilih Paket
                    </button>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8 lg:gap-12">
            {{-- Kiri: Daftar Paket --}}
<div>
    <div class="grid gap-5 md:gap-6" id="paket-list">
        @forelse($qurbans as $paket)
        <div class="paket-card group relative bg-white rounded-2xl border border-slate-200 hover:border-amber-400 transition-all duration-500 cursor-pointer overflow-hidden shadow-sm hover:shadow-2xl"
            data-id="{{ $paket->id }}"
            data-jenis="{{ $paket->jenis_hewan }}"
            data-harga="{{ $paket->harga }}"
            data-max-share="{{ $paket->max_share }}"
            data-stok="{{ $paket->stok }}"
            data-nama="{{ ucfirst($paket->jenis_hewan) }} - {{ $paket->max_share == 1 ? '1 Ekor' : 'Kolektif ' . $paket->max_share . ' Orang' }}">
            
            <!-- Glassmorphism overlay effect -->
            <div class="absolute inset-0 bg-gradient-to-br from-transparent via-white/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 pointer-events-none"></div>
            
            <!-- Premium Badge Kolektif -->
            @if($paket->max_share > 1)
            <div class="absolute -top-1 -left-1 z-20">
                <div class="relative">
                    <div class="absolute inset-0 bg-amber-400 blur-md rounded-full"></div>
                    <div class="relative bg-gradient-to-r from-amber-500 to-orange-500 text-white text-[10px] md:text-xs font-bold px-3 py-1 rounded-br-2xl rounded-tl-2xl shadow-lg flex items-center gap-1">
                        <span>👥</span>
                        <span>KOLEKTIF</span>
                    </div>
                </div>
            </div>
            @endif
            
            <div class="p-5 md:p-6 relative z-10">
                <!-- Header Premium -->
                <div class="flex items-start justify-between mb-5">
                    <div class="flex items-center gap-3 md:gap-4">
                        <!-- Icon dengan efek neon -->
                        <div class="relative">
                            <div class="absolute inset-0 bg-gradient-to-br from-emerald-400 to-amber-400 rounded-xl blur-lg opacity-0 group-hover:opacity-40 transition-opacity duration-500"></div>
                            <div class="relative w-14 h-14 md:w-16 md:h-16 rounded-xl bg-gradient-to-br from-emerald-50 to-white border border-emerald-200 group-hover:border-amber-300 flex items-center justify-center text-3xl md:text-4xl transition-all duration-500 shadow-md group-hover:shadow-xl group-hover:scale-110">
                                {{ $paket->jenis_icon }}
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800 text-xl md:text-2xl group-hover:text-emerald-700 transition-colors">{{ ucfirst($paket->jenis_hewan) }}</h3>
                            <div class="flex items-center gap-2 mt-1.5">
                                <span class="text-[11px] md:text-xs px-2.5 p-2 rounded-full bg-gradient-to-r from-emerald-50 to-emerald-100 text-emerald-700 font-semibold border border-emerald-200">
                                    @if($paket->max_share == 1)
                                        🐏 Individual
                                    @else
                                        👥 {{ $paket->max_share }} Peserta
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="relative">
                            <p class="text-2xl md:text-3xl font-black bg-gradient-to-r from-emerald-700 to-teal-700 bg-clip-text text-transparent group-hover:from-amber-600 group-hover:to-orange-600 transition-all duration-300">
                                {{ $paket->harga_formatted }}
                            </p>
                            @if($paket->harga_full && $paket->harga_full > $paket->harga)
                                <p class="text-xs text-slate-400 line-through absolute -top-3 right-0">Rp{{ number_format($paket->harga_full, 0, ',', '.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                
                <!-- Detail Berat - Modern Card -->
                @if($paket->berat_range)
                <div class="bg-gradient-to-r from-slate-50 to-emerald-50/30 rounded-xl p-3 mb-4 border border-slate-100">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <span class="text-emerald-600 text-sm">⚖️</span>
                            </div>
                            <span class="font-semibold text-slate-700 text-sm">{{ $paket->berat_range }}</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-5 h-5 rounded-full bg-emerald-100 flex items-center justify-center">
                                <span class="text-emerald-600 text-[10px]">✓</span>
                            </div>
                            <span class="text-[11px] text-slate-500">Potong & Distribusi</span>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Deskripsi -->
                @if($paket->deskripsi_singkat)
                <p class="text-sm text-slate-500 mb-4 leading-relaxed line-clamp-2">{{ $paket->deskripsi_singkat }}</p>
                @endif
                
                <!-- Info Kolektif Premium -->
                @if($paket->max_share > 1)
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80 p-3 mb-4 border border-amber-200 shadow-sm">
                    <div class="flex flex-col gap-2 relative z-10">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 rounded-full bg-amber-200 flex items-center justify-center">
                                    <span class="text-amber-700 text-xs">👥</span>
                                </div>
                                <span class="text-xs font-semibold text-amber-800">Sistem Kolektif</span>
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] text-amber-700">Setiap peserta dapat 1/{{ $paket->max_share }} bagian</span>
                        </div>
                        <div class="mt-1 h-1.5 bg-amber-200 rounded-full overflow-hidden">
                            <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Footer Premium -->
                <div class="flex items-center justify-between mt-4 pt-4 border-t-2 border-dashed border-slate-100">
                    <!-- Status Stok Premium -->
                    <div class="flex items-center gap-2">
                        @if($paket->stok > 0 && $paket->stok < 5)
                            <div class="relative">
                                <div class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></div>
                                <div class="absolute inset-0 w-2 h-2 bg-amber-500 rounded-full animate-ping"></div>
                            </div>
                            <span class="text-xs font-medium text-amber-600">Stok {{ $paket->stok }}</span>
                        @elseif($paket->stok > 0)
                            <div class="w-2 h-2 bg-emerald-500 rounded-full"></div>
                            <span class="text-xs font-medium text-emerald-600">Tersedia</span>
                        @else
                            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                            <span class="text-xs font-medium text-red-500">Habis</span>
                        @endif
                    </div>
                    
                    <!-- Tombol Premium -->
                    <button class="pilih-paket-btn group/btn relative px-5 md:px-6 py-2 md:py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white text-sm font-bold rounded-xl shadow-md hover:shadow-xl transition-all duration-300 flex items-center gap-2 overflow-hidden">
                        <span class="relative z-10 flex items-center gap-2">
                            <span>Pilih Paket</span>
                            <svg class="w-4 h-4 transform group-hover/btn:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                            </svg>
                        </span>
                        <span class="absolute inset-0 bg-white/30 transform -translate-x-full group-hover/btn:translate-x-0 transition-transform duration-500 ease-out"></span>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-16 bg-gradient-to-br from-slate-50 to-emerald-50 rounded-2xl border border-slate-200">
            <div class="text-7xl mb-4 animate-bounce">🐏</div>
            <h3 class="text-slate-700 font-bold text-lg">Belum Ada Paket Qurban</h3>
            <p class="text-slate-400 text-sm mt-1">Silakan cek kembali nanti</p>
        </div>
        @endforelse
    </div>
</div>

            {{-- Kanan: Form Pendaftaran --}}
            <div id="form-pendaftaran">
                <div class="bg-white rounded-2xl shadow-xl border border-emerald-100 overflow-hidden sticky-form">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-white/20 flex items-center justify-center">
                                <span class="text-xl">📝</span>
                            </div>
                            <div>
                                <h3 class="text-white font-bold text-xl">Formulir Pendaftaran</h3>
                                <p class="text-emerald-100 text-sm">Isi data dengan benar</p>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 md:p-8">
                        <form id="formQurban" action="{{ route('qurban.register.store') }}" method="POST" class="space-y-5">
                            <div id="selected-paket-indicator" class="hidden mb-4 p-3 bg-gradient-to-r from-amber-50 to-amber-100 rounded-xl border border-amber-200">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg">✅</span>
                                    <span class="text-sm font-semibold text-amber-800">Paket terpilih:</span>
                                    <span id="selected-paket-name" class="text-sm text-slate-700 font-medium"></span>
                                </div>
                            </div>
                            @csrf
                            <input type="hidden" name="qurban_id" id="selected_paket_id">
                            
                            @if(session('error'))
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                                <p class="text-red-700 text-sm">{{ session('error') }}</p>
                            </div>
                            @endif
                            
                            <!-- Paket Display -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Paket Pilihan <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <select id="paket_display" class="w-full px-4 py-3 bg-emerald-50 border-2 border-emerald-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition text-slate-700" required disabled>
                                        <option value="">-- Pilih paket dari daftar --</option>
                                    </select>
                                    <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-400 mt-1 lg:hidden">📱 Pilih paket dari daftar di atas</p>
                                <p class="text-xs text-slate-400 mt-1 hidden lg:block">🖥️ Pilih paket dari daftar di samping</p>
                            </div>

                            <!-- Nama Lengkap -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Nama Lengkap <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                    </div>
                                    <input type="text" name="nama_lengkap" class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition text-slate-700" placeholder="Contoh: Ahmad Bin Abdullah" required>
                                </div>
                            </div>

                            <!-- WhatsApp -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    No. WhatsApp <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                        </svg>
                                    </div>
                                    <input type="tel" name="telepon" class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition text-slate-700" placeholder="08123456789" required>
                                </div>
                            </div>

                            <!-- Alamat -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Alamat
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-3 text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        </svg>
                                    </div>
                                    <textarea name="alamat" class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition text-slate-700" rows="2" placeholder="Alamat lengkap"></textarea>
                                </div>
                            </div>

                            <!-- Jumlah Share -->
                            <div id="share-container" style="display: none;">
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Jumlah Kolektif
                                </label>
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-1 max-w-[120px]">
                                        <input type="number" name="jumlah_share" id="jumlah_share" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition text-center text-slate-700" value="1" min="1" max="7">
                                    </div>
                                    <span class="text-sm text-slate-500" id="share_info_text">(Max 7 orang)</span>
                                </div>
                                <p class="text-xs text-amber-600 mt-2 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Jika patungan tidak mencapai maksimal kolektif, akan dialihkan ke qurban kambing dengan biaya potong Rp150.000
                                </p>
                            </div>

                            <!-- Catatan -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">
                                    Catatan (Opsional)
                                </label>
                                <div class="relative">
                                    <div class="absolute left-3 top-3 text-slate-400">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </div>
                                    <textarea name="catatan" class="w-full pl-10 pr-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition text-slate-700" rows="2" placeholder="Contoh: Qurban untuk almarhum/ah, atau membawa hewan sendiri"></textarea>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <button type="submit" class="w-full py-3.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-[1.02] flex items-center justify-center gap-2">
                                <span class="text-xl">🐏</span>
                                <span>Daftar Qurban Sekarang</span>
                                <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </button>
                        </form>

                        <!-- Contact Info -->
                        <div class="mt-6 p-4 bg-gradient-to-r from-emerald-50 to-amber-50 rounded-xl">
                            <div class="flex items-start gap-3">
                                <div class="w-8 h-8 rounded-full bg-emerald-200 flex items-center justify-center flex-shrink-0">
                                    <span class="text-sm">💬</span>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-700 font-semibold">Ada pertanyaan seputar qurban?</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Hubungi panitia qurban kami:</p>
                                    <div class="mt-2 space-y-1">
                                        <a href="https://wa.me/62{{ $contactInfoPhone }}?text=Assalamu%27alaikum%2C%20saya%20mau%20tanya%20tentang%20qurban" target="_blank" class="inline-flex items-center gap-1 text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                                            💚 {{ $contactInfoName }}: {{ substr($contactInfoPhone, 0, 4) . ' ' . substr($contactInfoPhone, 4, 4) . ' ' . substr($contactInfoPhone, 8) }}
                                        </a>
                                        <br>
                                        <a href="https://wa.me/62{{ $contactConfirmPhone }}?text=Assalamu%27alaikum%2C%20saya%20mau%20konfirmasi%20pembayaran%20qurban" target="_blank" class="inline-flex items-center gap-1 text-sm text-emerald-600 hover:text-emerald-700 font-medium">
                                            💚 {{ $contactConfirmName }} (Konfirmasi): {{ substr($contactConfirmPhone, 0, 4) . ' ' . substr($contactConfirmPhone, 4, 4) . ' ' . substr($contactConfirmPhone, 8) }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ================= INFO QURBAN ================= --}}
<section id="info-qurban" class="py-16 md:py-20 bg-gradient-to-br from-emerald-50 via-white to-amber-50">
    <div class="container mx-auto px-4 md:px-8 lg:px-16 xl:px-24">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-slate-800 mb-4">Informasi Lengkap Qurban</h2>
            <div class="w-24 h-1 bg-gradient-to-r from-emerald-500 to-amber-500 mx-auto rounded-full"></div>
            <p class="text-slate-600 mt-4">Masjid Raudhotul Jannah - Taman Cipulir Estate</p>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Kontak & Rekening -->
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border border-emerald-100 hover:shadow-2xl transition-all duration-300 h-full flex flex-col">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-6 pb-3 border-b-2 border-emerald-100">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-md">
                        <span class="text-2xl">📞</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Kontak Panitia</h3>
                        <p class="text-xs text-slate-500">Hubungi kami untuk informasi lebih lanjut</p>
                    </div>
                </div>
                
                <div class="space-y-4 flex-1">
                    <!-- Informasi & Pendaftaran -->
                    <div class="group relative overflow-hidden rounded-xl transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-emerald-50 to-white rounded-xl border border-emerald-200 hover:border-emerald-300 transition-all duration-300 flex-wrap gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-500 flex items-center justify-center shadow-sm">
                                    <span class="text-xl">📱</span>
                                </div>
                                <div>
                                    <p class="text-xs text-emerald-600 font-semibold">INFORMASI & PENDAFTARAN</p>
                                    <p class="font-bold text-slate-800">{{ $contactInfoName }}</p>
                                </div>
                            </div>
                                <a href="https://wa.me/62{{ $contactInfoPhone }}?text=Assalamu%27alaikum%2C%20saya%20mau%20tanya%20tentang%20qurban" 
                                target="_blank" 
                                class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-full text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md transform hover:scale-105">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/>
                                    </svg>
                                    <span class="hidden sm:inline">{{ substr($contactInfoPhone, 0, 4) . ' ' . substr($contactInfoPhone, 4, 4) . ' ' . substr($contactInfoPhone, 8) }}</span>
                                    <span class="sm:hidden">WA</span>
                                </a>
                        </div>
                    </div>

                    <!-- Konfirmasi Transfer -->
                    <div class="group relative overflow-hidden rounded-xl transition-all duration-300 hover:shadow-md">
                        <div class="flex items-center justify-between p-3 bg-gradient-to-r from-amber-50 to-white rounded-xl border border-amber-200 hover:border-amber-300 transition-all duration-300 flex-wrap gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-amber-500 flex items-center justify-center shadow-sm">
                                    <span class="text-xl">💳</span>
                                </div>
                                <div>
                                    <p class="text-xs text-amber-600 font-semibold">KONFIRMASI TRANSFER</p>
                                    <p class="font-bold text-slate-800">{{ $contactConfirmName }}</p>
                                </div>
                            </div>
                                <a href="https://wa.me/62{{ $contactConfirmPhone }}?text=Assalamu%27alaikum%2C%20saya%20mau%20konfirmasi%20pembayaran%20qurban" 
                                target="_blank" 
                                class="flex items-center gap-2 px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-full text-sm font-semibold transition-all duration-300 shadow-sm hover:shadow-md transform hover:scale-105">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654z"/>
                                    </svg>
                                    <span class="hidden sm:inline">{{ substr($contactConfirmPhone, 0, 4) . ' ' . substr($contactConfirmPhone, 4, 4) . ' ' . substr($contactConfirmPhone, 8) }}</span>
                                    <span class="sm:hidden">WA</span>
                                </a>
                        </div>
                    </div>
                    
                    <!-- Rekening Bank Card -->
                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 p-5 text-white shadow-lg mt-4" id="bankCard">
                        <!-- Decorative elements -->
                        <div class="absolute -top-10 -right-10 w-20 h-20 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-10 -left-10 w-20 h-20 bg-white/10 rounded-full"></div>
                        
                        <div class="relative z-10">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="text-2xl">🏦</span>
                                <div>
                                    <p class="font-semibold text-sm opacity-90">Rekening</p>
                                    <p class="font-bold text-lg">{{ $bankName }}</p>
                                </div>
                            </div>
                            
                            <div class="bg-white/15 rounded-xl p-3 mb-3">
                                <div class="flex items-center justify-between gap-2 flex-wrap">
                                    <p class="text-xl md:text-2xl font-mono font-bold tracking-wider select-all">{{ $bankAccount }}</p>
                                    <button class="copy-btn bg-white/20 hover:bg-white/30 transition-all duration-200 rounded-lg px-3 py-2 text-sm font-semibold flex items-center gap-1 shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="btn-text">Salin</span>
                                    </button>
                                </div>
                            </div>
                            
                            <p class="text-emerald-100 text-sm">a.n. {{ $bankAccountName }}</p>
                        </div>
                    </div>

                    <!-- Catatan Penting Transfer -->
                    <div class="mt-2 p-3 bg-blue-50/50 rounded-lg border border-blue-100">
                        <div class="flex items-start gap-2">
                            <span class="text-blue-500 text-sm">💡</span>
                            <p class="text-xs text-slate-600">
                                <span class="font-semibold text-blue-700">Tips:</span> 
                                Setelah transfer, segera konfirmasi ke nomor WhatsApp di atas agar pendaftaran segera diproses.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Label info -->
                    <div class="mt-2 text-center">
                        <p class="text-xs text-slate-400 flex items-center justify-center gap-1">
                            <span>✅</span>
                            <span>Transfer sesuai nominal agar terverifikasi otomatis</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Harga & Biaya -->
            <div class="bg-white rounded-2xl shadow-xl p-6 md:p-8 border border-emerald-100 hover:shadow-2xl transition-all duration-300">
                <!-- Header -->
                <div class="flex items-center gap-3 mb-6 pb-3 border-b-2 border-emerald-100">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-md">
                        <span class="text-2xl">💰</span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">Daftar Harga Qurban</h3>
                        <p class="text-xs text-slate-500">Update terbaru {{ date('Y') }}</p>
                    </div>
                </div>
                
                <div class="space-y-3">
                    @foreach($qurbans as $index => $paket)
                    <div class="group relative overflow-hidden rounded-xl transition-all duration-300 hover:bg-emerald-50/50 p-3 -mx-3">
                        <div class="absolute inset-0 bg-gradient-to-r from-emerald-50/0 via-emerald-50/0 to-emerald-50/0 group-hover:from-emerald-50/30 group-hover:via-emerald-50/20 transition-all duration-500"></div>
                        
                        <div class="relative z-10">
                            <div class="flex justify-between items-center flex-wrap gap-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-emerald-100 to-amber-100 flex items-center justify-center text-xl shadow-sm group-hover:scale-110 transition-transform">
                                        {{ $paket->jenis_icon }}
                                    </div>
                                    <div>
                                        <span class="font-bold text-slate-800 text-base">{{ ucfirst($paket->jenis_hewan) }}</span>
                                        <span class="ml-2 text-xs px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold">{{ $paket->share_badge }}</span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="text-xl font-extrabold bg-gradient-to-r from-emerald-700 to-teal-700 bg-clip-text text-transparent">{{ $paket->harga_formatted }}</span>
                                    @if($paket->harga_full)
                                    <p class="text-xs text-slate-400">{{ $paket->harga_full_formatted }}</p>
                                    @endif
                                </div>
                            </div>
                            
                            @if($paket->max_share > 1)
                            <div class="mt-2 ml-12">
                                <div class="flex items-center gap-2 text-xs text-slate-500">
                                    <span class="text-amber-600">👥</span>
                                    <span>Sistem Kolektif: {{ $paket->max_share }} orang per ekor</span>
                                    <div class="flex-1 h-1.5 bg-emerald-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-gradient-to-r from-amber-400 to-amber-500 rounded-full" style="width: 0%"></div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                    @if(!$loop->last)
                    <div class="border-t border-emerald-100/50"></div>
                    @endif
                    @endforeach
                    
                    <!-- Biaya Potong Card dengan konversi aman -->
                    <div class="mt-6 relative overflow-hidden rounded-xl bg-gradient-to-r from-amber-50 to-orange-50 p-5 border border-amber-200 shadow-md">
                        @php
                            // Konversi aman ke integer
                            $potongSapiClean = (int) preg_replace('/[^0-9]/', '', $potongSapi);
                            $potongKambingClean = (int) preg_replace('/[^0-9]/', '', $potongKambing);
                        @endphp
                        
                        <div class="absolute -top-10 -right-10 w-20 h-20 bg-amber-200 rounded-full opacity-50"></div>
                        <div class="absolute -bottom-10 -left-10 w-20 h-20 bg-amber-200 rounded-full opacity-50"></div>
                        
                        <div class="relative z-10">
                            <div class="flex flex-wrap items-center gap-2 mb-3">
                                <span class="text-xl">📌</span>
                                <p class="text-sm font-bold text-amber-800">Biaya Potong & Distribusi</p>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-200 text-amber-800 font-semibold">Bawa Hewan Sendiri</span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-3 mt-3">
                                <div class="bg-white/60 rounded-lg p-3 text-center backdrop-blur-sm">
                                    <div class="flex items-center justify-center gap-2 mb-1">
                                        <span class="text-xl">🐮</span>
                                        <span class="font-bold text-slate-800">Sapi</span>
                                    </div>
                                    <span class="text-lg font-extrabold text-emerald-700">Rp{{ number_format($potongSapiClean, 0, ',', '.') }}</span>
                                </div>
                                <div class="bg-white/60 rounded-lg p-3 text-center backdrop-blur-sm">
                                    <div class="flex items-center justify-center gap-2 mb-1">
                                        <span class="text-xl">🐐</span>
                                        <span class="font-bold text-slate-800">Kambing</span>
                                    </div>
                                    <span class="text-lg font-extrabold text-emerald-700">Rp{{ number_format($potongKambingClean, 0, ',', '.') }}</span>
                                </div>
                            </div>
                            
                            <p class="text-xs text-amber-700 mt-3 flex items-center gap-1 justify-center flex-wrap">
                                <span>💡</span>
                                <span>Sudah termasuk pemotongan, pengemasan, dan distribusi</span>
                            </p>
                        </div>
                    </div>
                    
                    <div class="mt-4 text-center">
                        <p class="text-xs text-slate-400 flex items-center justify-center gap-1">
                            <span>🏷️</span>
                            <span>Harga sudah termasuk biaya potong & distribusi untuk paket yang tersedia</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Catatan Penting -->
        <div class="mt-8 bg-amber-50 rounded-2xl p-6 border-l-8 border-amber-500 shadow-md">
            <div class="flex items-start gap-3">
                <span class="text-3xl">📋</span>
                <div>
                    <h3 class="font-bold text-amber-800 text-lg mb-3">Catatan Penting:</h3>
                    <ul class="space-y-2 text-slate-700">
                        @foreach($importantNotes as $note)
                        <li class="flex items-start gap-2">
                            <span class="text-amber-500">✓</span>
                            <span>{!! nl2br(e($note)) !!}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

        <!-- FAQ -->
        <div class="mt-10">
            <div class="text-center mb-8">
                <h3 class="text-2xl md:text-3xl font-bold text-slate-800">❓ Pertanyaan Seputar Qurban</h3>
            </div>
            <div class="grid md:grid-cols-2 gap-5">
                @foreach($faqItems as $faq)
                <div class="bg-white rounded-xl p-5 shadow-md border border-emerald-100 hover:shadow-lg transition">
                    <div class="flex items-start gap-3">
                        <span class="text-emerald-600 font-bold text-xl">Q.</span>
                        <div>
                            <h4 class="font-semibold text-slate-800">{{ $faq['question'] }}</h4>
                            <p class="text-sm text-slate-600 mt-1">{{ $faq['answer'] }}</p>
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
    
    if (!localStorage.getItem('qurbanGuideShown')) {
        setTimeout(() => {
            const guide = document.getElementById('firstVisitGuide');
            if (guide) {
                guide.style.display = 'flex';
            }
        }, 1000);
    }

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(angka);
    }
    
    function updateFormWithPaket(paketId, paketJenis, paketHarga, paketMaxShare, paketNama) {
        selectedPaket = {
            id: paketId,
            jenis: paketJenis,
            harga: paketHarga,
            maxShare: paketMaxShare,
            nama: paketNama
        };
        
        $('#selected_paket_id').val(paketId);
        const displayText = `${paketNama} (${formatRupiah(paketHarga)})`;
        $('#paket_display').html(`<option value="${paketId}" selected>${displayText}</option>`);
        
        $('#selected-paket-name').text(paketNama);
        $('#selected-paket-indicator').fadeIn(300);
        
        const isSapiKerbau = (paketJenis === 'sapi' || paketJenis === 'kerbau');
        if (isSapiKerbau && paketMaxShare > 1) {
            $('#share-container').slideDown(300);
            $('#jumlah_share').attr('max', paketMaxShare).val(1);
            $('#share_info_text').text(`(Max ${paketMaxShare} orang)`);
        } else {
            $('#share-container').slideUp(300);
            $('#jumlah_share').val(1);
        }
    }
    
    // Pilih paket dengan visual feedback yang lebih jelas
    $('.paket-card').on('click', function() {
        // Debug: cek apakah event terpanggil
        console.log('Kartu diklik!');
        
        const $this = $(this);
        const paketId = $this.data('id');
        const paketJenis = $this.data('jenis');
        const paketHarga = $this.data('harga');
        const paketMaxShare = $this.data('max-share');
        const paketNama = $this.data('nama');
        
        console.log('Paket dipilih:', paketNama);
        
        $('.paket-card').removeClass('selected').css({
            'background': '',
            'border': ''
        });
        $this.addClass('selected').css({
            'background': '#ecfdf5',  // hijau soft (sama seperti bg form)
            'border': '2px solid #10b981'  // border hijau (bukan emas)
        });
        
        // Cek apakah class berhasil ditambahkan
        console.log('Class selected ada?', $this.hasClass('selected'));
        
        updateFormWithPaket(paketId, paketJenis, paketHarga, paketMaxShare, paketNama);

        const isMobile = window.innerWidth < 768;

        Swal.fire({
            icon: 'success',
            iconHtml: '', // Kosongkan icon HTML
            title: '',
            html: `
                <div class="mt-2">
                    <div class="w-12 h-12 mx-auto bg-emerald-100 rounded-full flex items-center justify-center mb-3">
                        <span class="text-2xl">🐏</span>
                    </div>
                    <p class="font-bold text-emerald-700 text-base">Paket Dipilih!</p>
                    <p class="text-slate-500 text-sm mt-1">${paketNama}</p>
                </div>
            `,
            showConfirmButton: true,
            confirmButtonText: 'OK, Lanjutkan',
            confirmButtonColor: '#10b981',
            showCancelButton: false,
            width: isMobile ? '80%' : '320px',
            padding: '1rem',
            background: '#ffffff',
            showClass: {
                icon: 'hidden' // Sembunyikan icon
            },
            customClass: {
                popup: 'rounded-2xl shadow-xl',
                confirmButton: 'px-4 py-2 text-sm rounded-lg font-semibold'
            }
        });
        
        $('#form-pendaftaran').get(0).scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    });
    
    $('.pilih-paket-btn').on('click', function(e) {
        e.stopPropagation();
        $(this).closest('.paket-card').trigger('click');
    });
    
    // Validasi form
    $('#formQurban').on('submit', function(e) {
        if (!selectedPaket) {
            Swal.fire({
                icon: 'warning',
                title: 'Belum Pilih Paket',
                text: 'Silakan pilih paket qurban terlebih dahulu!',
                confirmButtonColor: '#10b981'
            });
            e.preventDefault();
            return false;
        }
        
        const nama = $('input[name="nama_lengkap"]').val().trim();
        if (!nama) {
            Swal.fire({
                icon: 'warning',
                title: 'Nama Lengkap Wajib Diisi',
                text: 'Silakan isi nama lengkap Anda.',
                confirmButtonColor: '#10b981'
            });
            e.preventDefault();
            return false;
        }
        
        const telepon = $('input[name="telepon"]').val().trim();
        if (!telepon) {
            Swal.fire({
                icon: 'warning',
                title: 'Nomor WhatsApp Wajib Diisi',
                text: 'Silakan isi nomor WhatsApp untuk konfirmasi.',
                confirmButtonColor: '#10b981'
            });
            e.preventDefault();
            return false;
        }
        
        return true;
    });
    
    // ========== FITUR COPY NOMOR REKENING ==========
    $('.copy-btn').on('click', function() {
        const accountNumber = $(this).data('account');
        const $btnText = $(this).find('.btn-text');
        const originalText = $btnText.text();
        
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(accountNumber).then(() => {
                $btnText.text('Tersalin!');
                setTimeout(() => {
                    $btnText.text(originalText);
                }, 2000);
                
                Swal.fire({
                    icon: 'success',
                    title: 'Tersalin!',
                    text: 'Nomor rekening berhasil disalin',
                    timer: 1000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            }).catch(() => {
                fallbackCopy(accountNumber, $btnText, originalText);
            });
        } else {
            fallbackCopy(accountNumber, $btnText, originalText);
        }
    });
    
    function fallbackCopy(text, $btnText, originalText) {
        const textarea = $('<textarea>').val(text).css({
            position: 'fixed',
            top: 0,
            left: 0,
            opacity: 0
        }).appendTo('body');
        
        textarea.select();
        try {
            document.execCommand('copy');
            $btnText.text('Tersalin!');
            setTimeout(() => {
                $btnText.text(originalText);
            }, 2000);
        } catch (err) {
            console.error('Copy gagal:', err);
            alert('Gagal menyalin, silakan salin manual');
        }
        textarea.remove();
    }
});
</script>
@endpush