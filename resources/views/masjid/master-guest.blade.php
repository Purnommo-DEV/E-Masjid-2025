<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    
    @if(isset($seoData))
        {!! seo($seoData) !!}
    @endif

    {{-- TITLE --}}
    <title>
        @hasSection('title')
            @yield('title') | Masjid Raudhotul Jannah
        @else
            Masjid Raudhotul Jannah Taman Cipulir Estate
        @endif
    </title>

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#059669">
    <link rel="apple-touch-icon" href="{{ asset('/pwa/icon-128.png') }}">

    {{-- FAVICON --}}
    <link rel="icon" type="image/png" href="{{ asset('/pwa/mrj-logo.png') }}">

    {{-- VITE --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('sweetalert::alert')
    @stack('head')

    <!-- GLOBAL SCROLL FIX -->
    <style>
        /* Hilangkan garis di wave divider */
        .wave-divider svg,
        .wave-divider img {
            display: block;
            margin: 0;
            padding: 0;
            border: none;
        }

        .wave-divider {
            line-height: 0;
            font-size: 0;
            display: block;
        }
        html {
            scroll-behavior: smooth;
            scroll-padding-top: 80px;
        }

        /* agar section tidak ketutup navbar */
        section {
            scroll-margin-top: 80px;
        }

        /* footer selalu nempel */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        main {
            flex: 1;
        }

        /* Hide reCAPTCHA badge */
        .grecaptcha-badge,
        .grecaptcha-badge > div,
        .grecaptcha-badge iframe,
        .rc-anchor,
        .rc-anchor-light,
        .rc-anchor-invisible,
        .rc-anchor-invisible-hover,
        .rc-anchor-normal-footer,
        .rc-anchor-pt,
        .rc-anchor-logo-large,
        .rc-anchor-logo-img,
        .rc-anchor-aria-status,
        .rc-anchor-error-msg-container {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            position: absolute !important;
            left: -9999px !important;
            top: -9999px !important;
        }

        /* Ripple effect saat tap/klik */
        .ripple {
            position: relative;
            overflow: hidden;
        }
        
        .ripple::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(16, 185, 129, 0.25);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            opacity: 0;
            transition: all 0.5s;
            pointer-events: none;
        }
        
        .ripple:active::after {
            width: 300px;
            height: 300px;
            opacity: 1;
        }

        /* Pastikan semua link punya transisi */
        a {
            transition: all 0.2s ease-in-out;
        }
        
        /* ========== CUSTOM QRIS BOX ========== */
        .qris-box {
            width: 96px;
            height: 96px;
            background: white;
            padding: 10px;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qris-box:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }
        
        .qris-box img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        /* Loading spinner untuk PWA */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Responsive QRIS */
        @media (max-width: 640px) {
            .qris-box {
                width: 80px;
                height: 80px;
                padding: 8px;
            }
        }
    </style>
</head>

<body class="bg-white text-slate-100 overflow-x-hidden">
    {{-- NAVBAR --}}
    @include(guest_layout('_navbar'))

    {{-- CONTENT --}}
    <main>
        @yield('content')

        {{-- ================= FLOATING ACTION BUTTONS ================= --}}
        <!-- Floating Action Bar: Install PWA + Notifikasi + Chat WA -->
        <div class="fixed bottom-6 right-6 z-50 flex flex-col-reverse items-end gap-4 sm:bottom-8 sm:right-8">

            <!-- Tombol Chat WA (paling bawah) -->
            @php $wa = preg_replace('/[^0-9]/', '', profil('no_wa') ?? '62895704043814'); @endphp
            <a href="https://wa.me/{{ $wa }}" target="_blank" 
               class="btn btn-circle btn-lg bg-emerald-600 hover:bg-emerald-700 text-white shadow-xl shadow-emerald-900/40 border-none transition-all duration-300 hover:scale-110 active:scale-95">
                <span class="text-2xl">💬</span>
            </a>

            <!-- Tombol Notifikasi (tengah) -->
            <button id="enableNotificationBtn" 
                    class="btn btn-circle btn-lg bg-cyan-600 hover:bg-cyan-700 text-white shadow-xl shadow-cyan-900/40 border-none transition-all duration-300 hover:scale-110 active:scale-95 relative">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <span id="notifBadge" class="absolute -top-1 -right-1 badge badge-error badge-xs animate-ping hidden">!</span>
            </button>

            <!-- Tombol Install PWA (paling atas) -->
            <button id="installPwaBtn" 
                    class="btn btn-circle btn-lg bg-gradient-to-br from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-xl shadow-teal-900/50 border-none transition-all duration-300 hover:scale-110 active:scale-95 hidden">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                <span class="absolute -top-1 -right-1 badge badge-primary badge-xs animate-pulse">App</span>
            </button>
        </div>

        <!-- Modal Custom Persetujuan Notifikasi -->
        <dialog id="notifConsentModal" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box text-center rounded-3xl max-w-md">
                <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-4xl shadow-md">
                    🔔
                </div>
                <h3 class="font-bold text-xl text-slate-900 mb-3">
                    Aktifkan Notifikasi?
                </h3>
                <p class="text-sm text-slate-600 leading-relaxed mb-6 px-4">
                    Dapatkan <span class="font-semibold text-emerald-700">pengumuman penting</span>, jadwal kajian, pengingat sholat, dan update masjid langsung di perangkat Anda — bahkan saat aplikasi tertutup.
                </p>

                <div id="permissionDeniedMessage" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-left text-sm text-red-800">
                    <strong>Izin notifikasi sebelumnya ditolak oleh browser.</strong><br><br>
                    <span id="resetInstructions"></span>
                </div>

                <div class="flex flex-col sm:flex-row gap-4 justify-center mt-6">
                    <button id="notifAllow" 
                            class="btn bg-emerald-600 hover:bg-emerald-700 text-white border-none rounded-full px-10 py-3 font-semibold shadow-md">
                        Aktifkan Sekarang
                    </button>
                    <button id="notifDeny" 
                            class="btn bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 rounded-full px-10 py-3 font-semibold">
                        Nanti Saja
                    </button>
                </div>

                <p class="text-xs text-slate-400 mt-6 italic">
                    Anda bisa mengubah pilihan ini kapan saja di pengaturan perangkat/browser.
                </p>
            </div>
        </dialog>
        
    </main>

    {{-- FOOTER --}}
    @include(guest_layout('_footer'))

    <!-- SCROLL HASH -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            function scrollToHash(){
                const hash = window.location.hash;
                if(hash.startsWith("#!")){
                    const id = hash.replace("#!","");
                    const el = document.getElementById(id);
                    if(el){
                        setTimeout(()=>{
                            const yOffset = -80;
                            const y = el.getBoundingClientRect().top + window.pageYOffset + yOffset;
                            window.scrollTo({top:y,behavior:"smooth"});
                        },300);
                    }
                }
            }
            scrollToHash();
            window.addEventListener("hashchange", scrollToHash);
        });

        // =============================================
        // PWA Install Prompt
        // =============================================
        let deferredPrompt = null;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            document.getElementById('installPwaBtn')?.classList.remove('hidden');
            console.log('PWA install prompt tersedia');
        });

        document.getElementById('installPwaBtn')?.addEventListener('click', async () => {
            if (!deferredPrompt) return;
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log(`PWA install outcome: ${outcome}`);
            deferredPrompt = null;
            document.getElementById('installPwaBtn')?.classList.add('hidden');
            if (outcome === 'accepted') {
                alert('Terima kasih! Aplikasi sedang diinstall.');
            }
        });

        window.addEventListener('appinstalled', () => {
            console.log('PWA berhasil diinstall');
            document.getElementById('installPwaBtn')?.classList.add('hidden');
        });

        // =============================================
        // Custom Notifikasi Modal
        // =============================================
        const notifModal       = document.getElementById('notifConsentModal');
        const btnNotifTrigger  = document.getElementById('enableNotificationBtn');
        const btnAllow         = document.getElementById('notifAllow');
        const btnDeny          = document.getElementById('notifDeny');
        const notifBadge       = document.getElementById('notifBadge');
        const deniedMessage    = document.getElementById('permissionDeniedMessage');
        const resetInstructions = document.getElementById('resetInstructions');

        function openNotifModal() { notifModal?.showModal(); }
        function closeNotifModal() { notifModal?.close(); }

        function resetModalUI() {
            deniedMessage?.classList.add('hidden');
            btnAllow.disabled = false;
            btnAllow.textContent = 'Aktifkan Sekarang';
        }

        if ("Notification" in window) {
            const permission = Notification.permission;
            if (permission === "granted") {
                notifBadge?.classList.add('hidden');
            } else if (permission === "denied") {
                notifBadge?.classList.remove('hidden');
            } else {
                notifBadge?.classList.remove('hidden');
            }
        }

        btnNotifTrigger?.addEventListener('click', () => {
            resetModalUI();
            if ("Notification" in window && Notification.permission === "denied") {
                deniedMessage?.classList.remove('hidden');
                const isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                resetInstructions.textContent = isPwa 
                    ? "Cara mengaktifkan: Tekan lama ikon app di home screen → App info → Notifikasi → ubah ke Diizinkan. Lalu buka ulang aplikasi."
                    : "Cara mengaktifkan: Klik ikon gembok di address bar → Notifications → ubah ke Allow atau Ask (default). Lalu refresh halaman.";
                btnAllow.disabled = true;
                btnAllow.textContent = 'Sudah Diblokir (Aktifkan Manual)';
            }
            openNotifModal();
        });

        btnAllow?.addEventListener('click', async () => {
            if ("Notification" in window && Notification.permission === "denied") {
                return;
            }
            closeNotifModal();
            try {
                const permission = await Notification.requestPermission();
                if (permission === "granted") {
                    alert("Notifikasi berhasil diaktifkan!\nAnda akan menerima update masjid.");
                    notifBadge?.classList.add('hidden');
                } else if (permission === "denied") {
                    notifBadge?.classList.remove('hidden');
                }
            } catch (err) {
                console.error("Error request notification:", err);
            }
        });

        btnDeny?.addEventListener('click', closeNotifModal);
    </script>

    @stack('scripts')

</body>
</html>