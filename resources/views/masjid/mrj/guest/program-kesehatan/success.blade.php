@extends('masjid.master-guest')

@section('title', 'Pendaftaran Berhasil - Program Kesehatan')
@section('og_title', 'Pendaftaran Berhasil ✓')
@section('meta_description', 'Terima kasih telah mendaftar Program Kesehatan Masjid Raudhotul Jannah TCE')

@section('content')
<section class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-teal-50 py-12 flex items-center">
    <div class="container mx-auto px-4 lg:px-6">
        <div class="max-w-lg mx-auto">

            <!-- Main Card -->
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-emerald-100">

                <!-- Header -->
                <div class="bg-gradient-to-br from-emerald-600 via-teal-600 to-cyan-600 py-16 px-8 text-center relative overflow-hidden">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_center,#ffffff15_0%,transparent_70%)]"></div>
                    
                    <!-- Icon Sukses Interaktif -->
                    <div class="mx-auto relative mb-6">
                        <div class="w-32 h-32 bg-white rounded-full flex items-center justify-center shadow-2xl mx-auto animate-success-icon overflow-hidden border-[6px] border-white">
                            <span class="text-6xl drop-shadow-sm transition-all duration-500 hover:rotate-12 hover:scale-110">🎉</span>
                        </div>
                    </div>

                    <h1 class="text-4xl lg:text-5xl font-bold text-white tracking-tight">
                        Pendaftaran Berhasil!
                    </h1>
                </div>

                <div class="p-8 lg:p-10">
                    
                    <!-- Pesan Terima Kasih -->
                    <div class="text-center mb-12">
                        <p class="text-emerald-700 text-2xl font-light mb-4">
                            Terima kasih banyak atas partisipasinya,
                        </p>
                    
                        <div class="mb-8">
                            <!-- Label Bapak/Ibu -->
                            <div class="text-emerald-600 text-lg mb-3 tracking-wide">
                                Bapak / Ibu
                            </div>
                            
                            <!-- Box Nama -->
                            <div class="inline-block bg-white border border-emerald-200 shadow-sm px-10 py-4 rounded-3xl">
                                <span class="text-emerald-800 font-semibold text-3xl tracking-wide">
                                    {{ request('name') ?? 'Peserta' }}
                                </span>
                            </div>
                        </div>

                        <p class="text-slate-600 text-lg leading-relaxed max-w-md mx-auto">
                            Pendaftaran Anda telah berhasil kami terima.<br>
                            Semoga program ini bermanfaat bagi kesehatan Anda dan keluarga.
                        </p>
                    </div>

                    <!-- Grup WhatsApp -->
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 border border-emerald-100 rounded-2xl p-8 mb-10">
                        <div class="flex items-center justify-center gap-3 mb-5">
                            <div class="w-10 h-10 bg-green-500 text-white rounded-xl flex items-center justify-center text-2xl">📱</div>
                            <h3 class="font-bold text-emerald-800 text-2xl">Grup WhatsApp</h3>
                        </div>
                        
                        <p class="text-center text-emerald-700 leading-relaxed mb-6">
                            Silakan bergabung ke grup berikut untuk mendapatkan informasi lengkap dan update terkini:
                        </p>

                        <!-- Tombol WA Interaktif -->
                        <a href="https://chat.whatsapp.com/DF5dPVQfSIVIdoBn36Svgb"
                           target="_blank"
                           class="group flex items-center justify-center gap-4 bg-[#25D366] hover:bg-[#20ba5a] hover:scale-[1.03] transition-all duration-300 text-white font-bold text-xl py-6 px-8 rounded-2xl shadow-lg hover:shadow-2xl w-full">
                            <i class="fab fa-whatsapp text-4xl transition-transform group-hover:rotate-12"></i>
                            <div class="text-left">
                                <div class="text-lg">Gabung Sekarang</div>
                                <div class="text-sm opacity-90">Grup WhatsApp Program Kesehatan</div>
                            </div>
                        </a>
                    </div>

                    <!-- Manfaat Bergabung (Interaktif) -->
                    <div class="mb-10">
                        <h4 class="text-emerald-700 font-semibold text-xl mb-6 flex items-center gap-3">
                            <span class="text-2xl">📋</span>
                            Apa yang akan Anda dapatkan di grup?
                        </h4>
                        
                        <div class="space-y-4">
                            <div class="benefit-item group flex gap-4 items-start bg-white border border-emerald-100 hover:border-emerald-400 rounded-2xl p-5 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                                <div class="w-10 h-10 flex-shrink-0 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl transition-colors group-hover:bg-emerald-200">
                                    🗓️
                                </div>
                                <div class="pt-1">
                                    <span class="text-slate-700 text-[17px] leading-relaxed">
                                        Jadwal lengkap & pengingat acara
                                    </span>
                                </div>
                            </div>

                            <div class="benefit-item group flex gap-4 items-start bg-white border border-emerald-100 hover:border-emerald-400 rounded-2xl p-5 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                                <div class="w-10 h-10 flex-shrink-0 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl transition-colors group-hover:bg-emerald-200">
                                    🩸
                                </div>
                                <div class="pt-1">
                                    <span class="text-slate-700 text-[17px] leading-relaxed">
                                        Persiapan donor darah & tips kesehatan
                                    </span>
                                </div>
                            </div>

                            <div class="benefit-item group flex gap-4 items-start bg-white border border-emerald-100 hover:border-emerald-400 rounded-2xl p-5 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                                <div class="w-10 h-10 flex-shrink-0 bg-emerald-100 text-emerald-600 rounded-xl flex items-center justify-center text-2xl transition-colors group-hover:bg-emerald-200">
                                    ❓
                                </div>
                                <div class="pt-1">
                                    <span class="text-slate-700 text-[17px] leading-relaxed">
                                        Tanya jawab langsung dengan panitia
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Daftar Lagi -->
                    <a href="{{ route('donor-darah.daftar') }}"
                       class="block w-full text-center py-5 border-2 border-emerald-600 text-emerald-700 hover:bg-emerald-600 hover:text-white font-semibold rounded-2xl transition-all duration-300 text-lg hover:shadow-md">
                        ← Daftar Lagi
                    </a>

                    <!-- Keterangan -->
                    <div class="text-center mt-6 text-slate-500 text-sm leading-relaxed">
                        Anda boleh mendaftar lagi untuk keluarga, saudara, tetangga, atau teman.<br>
                        Setiap orang harus mengisi form secara terpisah 🙂
                    </div>

                    <!-- Doa Penutup -->
                    <div class="text-center mt-8 text-slate-500 text-sm font-light">
                        Semoga Allah ﷻ memberikan kesehatan yang barokah kepada kita semua.
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center mt-10 text-slate-400 text-sm font-light">
                Jazakumullah khairan katsiran<br>
                <span class="text-emerald-600">Masjid Raudhotul Jannah TCE</span>
            </div>
        </div>
    </div>
</section>
@endsection

@push('styles')
<style>
    /* Animasi Icon Sukses */
    .animate-success-icon {
        animation: successPop 1s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
    }
    
    @keyframes successPop {
        0%   { transform: scale(0.3) rotate(-20deg); opacity: 0; }
        60%  { transform: scale(1.12) rotate(8deg); }
        80%  { transform: scale(0.95) rotate(-5deg); }
        100% { transform: scale(1) rotate(0deg); opacity: 1; }
    }

    /* Hover effect untuk benefit items */
    .benefit-item {
        transition: all 0.3s ease;
    }
    
    .benefit-item:hover {
        box-shadow: 0 10px 15px -3px rgb(16 185 129 / 0.1);
    }
</style>
@endpush