@extends('masjid.master-guest')

@section('title', 'Pendaftaran Berhasil - Terima Kasih')

@section('content')

@push('style')
<style>
    @keyframes float {
        0%, 100% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
    }
    
    @keyframes scale-in {
        0% { transform: scale(0); opacity: 0; }
        80% { transform: scale(1.1); }
        100% { transform: scale(1); opacity: 1; }
    }
    
    @keyframes slide-up {
        0% { transform: translateY(30px); opacity: 0; }
        100% { transform: translateY(0); opacity: 1; }
    }
    
    @keyframes shimmer {
        0% { background-position: -1000px 0; }
        100% { background-position: 1000px 0; }
    }
    
    .animate-float {
        animation: float 3s ease-in-out infinite;
    }
    
    .animate-scale-in {
        animation: scale-in 0.5s ease-out forwards;
    }
    
    .animate-slide-up {
        animation: slide-up 0.6s ease-out forwards;
        opacity: 0;
    }
    
    .delay-1 { animation-delay: 0.1s; }
    .delay-2 { animation-delay: 0.2s; }
    .delay-3 { animation-delay: 0.3s; }
    .delay-4 { animation-delay: 0.4s; }
    .delay-5 { animation-delay: 0.5s; }
    
    .shimmer-text {
        background: linear-gradient(90deg, #10b981 0%, #f59e0b 50%, #10b981 100%);
        background-size: 200% auto;
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        animation: shimmer 3s linear infinite;
    }
    
    .confetti-bg {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }
    
    .code-card {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
    }
    
    .code-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: linear-gradient(45deg, transparent, rgba(255,255,255,0.05), transparent);
        transform: rotate(45deg);
        animation: shimmer-card 3s infinite;
    }
    
    @keyframes shimmer-card {
        0% { transform: translateX(-100%) rotate(45deg); }
        100% { transform: translateX(100%) rotate(45deg); }
    }
    
    .step-card {
        transition: all 0.3s ease;
    }
    
    .step-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
    }
    
    .copy-btn {
        transition: all 0.2s ease;
    }
    
    .copy-btn:active {
        transform: scale(0.95);
    }
    
    .whatsapp-btn {
        background: linear-gradient(135deg, #25D366, #128C7E);
    }
    
    .whatsapp-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 25px -5px rgba(37, 211, 102, 0.4);
    }
</style>
@endpush

<div class="confetti-bg" id="confetti"></div>

<div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-amber-50 py-12 px-4 relative z-10">
    <div class="container mx-auto max-w-4xl">
        
        {{-- Success Animation --}}
        <div class="text-center animate-slide-up">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-r from-emerald-500 to-teal-500 rounded-full shadow-2xl mb-6 animate-scale-in">
                <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            
            <h1 class="text-4xl md:text-5xl font-extrabold text-slate-800 mb-3">
                🎉 Pendaftaran <span class="shimmer-text">Berhasil!</span>
            </h1>
            <p class="text-slate-600 text-lg md:text-xl max-w-2xl mx-auto">
                Terima kasih telah mendaftar qurban di Masjid Raudhotul Jannah
            </p>
        </div>

        {{-- Confetti Effect --}}
        <div class="text-center mt-4 animate-slide-up delay-1">
            <p class="text-amber-600 font-semibold flex items-center justify-center gap-2">
                <span>✨</span> Semoga Allah menerima ibadah qurban kita <span>✨</span>
            </p>
        </div>

        {{-- Main Card --}}
        <div class="mt-8 bg-white rounded-3xl shadow-2xl overflow-hidden animate-slide-up delay-2">
            
            {{-- Header Gradient --}}
            <div class="bg-gradient-to-r from-emerald-600 via-teal-600 to-amber-500 px-6 py-6 text-center">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full mb-3">
                    <span class="text-yellow-200">🏷️</span>
                    <span class="text-white text-sm font-semibold">KODE REGISTRASI</span>
                </div>
                
                @if($registration)
                <div class="code-card mt-2">
                    <p class="text-emerald-300 text-sm mb-2">Simpan kode ini sebagai bukti pendaftaran</p>
                    <div class="flex items-center justify-center gap-3 flex-wrap">
                        <code class="text-2xl md:text-3xl font-mono font-bold text-white tracking-wider" id="kodeRegistrasi">
                            {{ $registration->kode_registrasi }}
                        </code>
                        <button onclick="copyKode()" class="copy-btn bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-xl transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            Salin
                        </button>
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Body --}}
            <div class="p-6 md:p-8">
                
                {{-- Detail Pendaftaran --}}
                @if($registration)
                <div class="grid md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-emerald-50 rounded-2xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-emerald-200 flex items-center justify-center">
                                <span class="text-xl">🐐</span>
                            </div>
                            <h3 class="font-bold text-slate-800">Detail Qurban</h3>
                        </div>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">Paket Qurban</span>
                                <span class="font-semibold text-emerald-700">{{ $registration->qurban->jenis_label }} ({{ $registration->qurban->share_badge }})</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">Harga per Share</span>
                                <span class="font-semibold">{{ $registration->qurban->harga_formatted }}</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">Jumlah Share</span>
                                <span class="font-semibold">{{ $registration->jumlah_share }} orang</span>
                            </div>
                            <div class="border-t border-emerald-200 pt-2 mt-2">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-slate-800">Total Pembayaran</span>
                                    <span class="text-2xl font-bold text-emerald-700">{{ $registration->total_harga_formatted }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-amber-50 rounded-2xl p-5">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-full bg-amber-200 flex items-center justify-center">
                                <span class="text-xl">👤</span>
                            </div>
                            <h3 class="font-bold text-slate-800">Informasi Pendaftar</h3>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <span class="text-xs text-slate-500">Nama Lengkap</span>
                                <p class="font-semibold text-slate-800">{{ $registration->nama_lengkap }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Nomor WhatsApp</span>
                                <p class="font-semibold text-slate-800">{{ $registration->telepon }}</p>
                            </div>
                            <div>
                                <span class="text-xs text-slate-500">Tanggal Pendaftaran</span>
                                <p class="text-slate-800">{{ $registration->created_at->format('d F Y H:i') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                {{-- Status Card --}}
                <div class="bg-gradient-to-r from-yellow-50 to-orange-50 rounded-2xl p-5 mb-8 border border-yellow-200">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-full bg-yellow-200 flex items-center justify-center flex-shrink-0">
                            <span class="text-xl">⏳</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-amber-800">Status: Menunggu Konfirmasi</h3>
                            <p class="text-sm text-amber-700 mt-1">
                                Pendaftaran Anda sedang dalam proses verifikasi oleh panitia. 
                                Silakan lakukan pembayaran dan konfirmasi sesuai langkah di bawah.
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Langkah Selanjutnya --}}
                <div class="mb-8">
                    <h3 class="text-xl font-bold text-slate-800 mb-5 text-center flex items-center justify-center gap-2">
                        <span>📌</span> Langkah Selanjutnya <span>📌</span>
                    </h3>
                    
                    <div class="grid md:grid-cols-3 gap-4">
                        {{-- Step 1 --}}
                        <div class="step-card bg-white rounded-xl p-4 border-2 border-emerald-100 shadow-sm hover:shadow-lg transition">
                            <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold mb-3 mx-auto">1</div>
                            <h4 class="font-semibold text-center text-slate-800 mb-2">💳 Lakukan Pembayaran</h4>
                            <div class="bg-gray-50 rounded-lg p-3 mt-2">
                                <p class="text-xs text-slate-500 text-center">Transfer ke rekening:</p>
                                <p class="font-mono font-bold text-emerald-700 text-center text-sm">{{ $bankAccount ?? '1010010947479' }}</p>
                                <p class="text-xs text-slate-500 text-center">a.n. {{ $bankAccountName ?? 'JAZULI' }}</p>
                                <p class="text-xs text-slate-500 text-center mt-1">{{ $bankName ?? 'BCA' }}</p>
                            </div>
                        </div>
                        
                        {{-- Step 2 --}}
                        <div class="step-card bg-white rounded-xl p-4 border-2 border-amber-100 shadow-sm hover:shadow-lg transition">
                            <div class="w-12 h-12 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold mb-3 mx-auto">2</div>
                            <h4 class="font-semibold text-center text-slate-800 mb-2">📱 Konfirmasi Pembayaran</h4>
                            <p class="text-sm text-slate-600 text-center mb-3">Kirim bukti transfer ke panitia</p>
                            <a href="https://wa.me/62{{ $contactConfirmPhone ?? '081310185948' }}?text=Assalamu%27alaikum%2C%20saya%20mau%20konfirmasi%20pembayaran%20qurban%0A%0ANama%3A%20{{ urlencode($registration->nama_lengkap ?? '') }}%0AKode%20Registrasi%3A%20{{ urlencode($registration->kode_registrasi ?? '') }}%0AJumlah%20Transfer%3A%20{{ urlencode($registration->total_harga_formatted ?? '') }}" target="_blank" 
                            class="w-full py-3 rounded-xl font-semibold flex items-center justify-center gap-2 transition-all duration-300 bg-emerald-500 hover:bg-emerald-600 text-white shadow-md hover:shadow-lg">
                                <span class="text-xl">💚</span>
                                <span>Konfirmasi via WhatsApp</span>
                            </a>
                        </div>
                        
                        {{-- Step 3 --}}
                        <div class="step-card bg-white rounded-xl p-4 border-2 border-teal-100 shadow-sm hover:shadow-lg transition">
                            <div class="w-12 h-12 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold mb-3 mx-auto">3</div>
                            <h4 class="font-semibold text-center text-slate-800 mb-2">✅ Selesai</h4>
                            <p class="text-sm text-slate-600 text-center">
                                Panitia akan mengonfirmasi dan menginformasikan jadwal pelaksanaan qurban
                            </p>
                        </div>
                    </div>
                </div>
                
                {{-- Informasi Penting --}}
                <div class="bg-blue-50 rounded-2xl p-5 mb-6 border border-blue-200">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-200 flex items-center justify-center flex-shrink-0">
                            <span class="text-sm">ℹ️</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-blue-800 mb-2">Informasi Penting</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li class="flex items-center gap-2">• Kode registrasi adalah bukti pendaftaran Anda</li>
                                <li class="flex items-center gap-2">• Pembayaran harus dilakukan maksimal H-2 sebelum Idul Adha</li>
                                <li class="flex items-center gap-2">• Simpan bukti transfer untuk konfirmasi ke panitia</li>
                                <li class="flex items-center gap-2">• Panitia akan menghubungi Anda via WhatsApp untuk informasi lebih lanjut</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex flex-wrap gap-4 justify-center">
                    <a href="{{ route('qurban.index') }}" class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 text-white rounded-xl font-semibold hover:shadow-lg transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7M3 12h14"/>
                        </svg>
                        Kembali ke Halaman Qurban
                    </a>
                    <a href="{{ url('/') }}" class="px-6 py-3 border-2 border-emerald-600 text-emerald-600 rounded-xl font-semibold hover:bg-emerald-50 transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                        </svg>
                        Ke Beranda
                    </a>
                </div>
                
            </div>
        </div>
        
        {{-- Footer Quote --}}
        <div class="text-center mt-8 animate-slide-up delay-5">
            <p class="text-slate-500 italic">
                "Sesungguhnya shalatku, ibadahku, hidupku dan matiku hanyalah untuk Allah, Tuhan semesta alam"
                <br>
                <span class="text-xs text-slate-400">(QS. Al-An'am: 162)</span>
            </p>
        </div>
        
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1"></script>
<script>
    // Konfeti effect
    function launchConfetti() {
        canvasConfetti({
            particleCount: 150,
            spread: 70,
            origin: { y: 0.6 },
            colors: ['#10b981', '#059669', '#f59e0b', '#d97706']
        });
        
        setTimeout(() => {
            canvasConfetti({
                particleCount: 100,
                spread: 100,
                origin: { y: 0.5, x: 0.3 },
                colors: ['#10b981', '#f59e0b']
            });
        }, 200);
        
        setTimeout(() => {
            canvasConfetti({
                particleCount: 100,
                spread: 100,
                origin: { y: 0.5, x: 0.7 },
                colors: ['#059669', '#d97706']
            });
        }, 400);
    }
    
    // Copy kode registrasi
    function copyKode() {
        const kode = document.getElementById('kodeRegistrasi').innerText;
        navigator.clipboard.writeText(kode).then(() => {
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Kode registrasi telah disalin ke clipboard',
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });
    }
    
    // Jalankan konfeti saat halaman load
    document.addEventListener('DOMContentLoaded', function() {
        launchConfetti();
    });
    
    // Tambahan konfeti saat user scroll atau klik
    document.addEventListener('click', function(e) {
        if (e.target.closest('.whatsapp-btn')) {
            canvasConfetti({
                particleCount: 50,
                spread: 60,
                origin: { y: 0.7 },
                colors: ['#25D366', '#128C7E']
            });
        }
    });
</script>
@endpush