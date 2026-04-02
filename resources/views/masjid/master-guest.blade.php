<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
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

    {{-- SEO DESCRIPTION (WAJIB SATU SAJA) --}}
    <!-- <meta name="description"
          content="@yield('meta_description', 'Website resmi Masjid Raudhotul Jannah Taman Cipulir Estate. Informasi kegiatan, kajian, dan layanan jamaah.')">
 -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- PWA --}}
    <link rel="manifest" href="{{ route('pwa.manifest') }}">
    <meta name="theme-color" content="#059669">
    <link rel="apple-touch-icon" href="{{ asset('/pwa/icon-128.png') }}">

    {{-- UPDATE TIME (anti cache WA) --}}
    <!-- <meta property="og:updated_time" content="{{ now()->timestamp }}"> -->

    {{-- ================= OPEN GRAPH ================= --}}
    <!-- 
    <meta property="og:type" content="@yield('og_type','website')">

    <meta property="og:title"
          content="@yield('og_title', trim($__env->yieldContent('title')) ?: 'Masjid Raudhotul Jannah')">

    <meta property="og:description"
          content="@yield('meta_description', 'Website resmi Masjid Raudhotul Jannah Taman Cipulir Estate.')">

    <meta property="og:url"
          content="@yield('og_url', url()->current())">

    <meta property="og:image"
          content="@yield('og_image', secure_asset('images/default-share.jpg'))">

    <meta property="og:image:secure_url"
          content="@yield('og_image', secure_asset('images/default-share.jpg'))">

    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:type" content="image/jpeg">
    <meta property="og:image:alt"
          content="@yield('og_image_alt','Masjid Raudhotul Jannah Taman Cipulir Estate')">

    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="Masjid Raudhotul Jannah"> -->

    {{-- ================= TWITTER CARD (WA juga pakai ini) ================= --}}
<!--     <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', trim($__env->yieldContent('title')))">
    <meta name="twitter:description" content="@yield('meta_description')">
    <meta name="twitter:image"
          content="@yield('og_image', secure_asset('images/default-share.jpg'))"> -->
      
    {{-- FAVICON --}}
    <link rel="icon" type="image/png" href="{{ asset('/pwa/mrj-logo.png') }}">

    {{-- VITE --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @include('sweetalert::alert')
    @stack('head')

    <!-- GLOBAL SCROLL FIX -->
    <style>
        html{
            scroll-behavior: smooth;
            scroll-padding-top: 95px;
        }

        /* agar section tidak ketutup navbar */
        section{
            scroll-margin-top: 110px;
        }

        /* footer selalu nempel */
        body{
            display:flex;
            flex-direction:column;
            min-height:100vh;
        }

        main{
            flex:1;
        }
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

        /*Header*/
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
          background: rgba(16, 185, 129, 0.25); /* emerald-500 opacity */
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
                <!-- Icon -->
                <div class="mx-auto mb-5 w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center text-4xl shadow-md">
                    🔔
                </div>
                <!-- Judul -->
                <h3 class="font-bold text-xl text-slate-900 mb-3">
                    Aktifkan Notifikasi?
                </h3>
                <!-- Deskripsi -->
                <p class="text-sm text-slate-600 leading-relaxed mb-6 px-4">
                    Dapatkan <span class="font-semibold text-emerald-700">pengumuman penting</span>, jadwal kajian, pengingat sholat, dan update masjid langsung di perangkat Anda — bahkan saat aplikasi tertutup.
                </p>

                <!-- Pesan Denied (muncul otomatis kalau denied) -->
                <div id="permissionDeniedMessage" class="hidden mt-4 p-4 bg-red-50 border border-red-200 rounded-xl text-left text-sm text-red-800">
                    <strong>Izin notifikasi sebelumnya ditolak oleh browser.</strong><br><br>
                    <span id="resetInstructions"></span>
                </div>

                <!-- Tombol Aksi -->
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

                <!-- Catatan kecil -->
                <p class="text-xs text-slate-400 mt-6 italic">
                    Anda bisa mengubah pilihan ini kapan saja di pengaturan perangkat/browser.
                </p>
            </div>
        </dialog>

    </main>

    {{-- FOOTER --}}
    @include(guest_layout('_footer'))

    <!-- SCROLL HASH (#!jadwal dll) -->
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
                            const yOffset = -90;
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

        document.querySelectorAll('#qris-modal img').forEach(img => {
            img.addEventListener('click', () => img.classList.toggle('scale-150'));
        });

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
        // Custom Notifikasi Modal + Permission Handling (Versi B: Pesan di Modal)
        // =============================================
        const notifModal       = document.getElementById('notifConsentModal');
        const btnNotifTrigger  = document.getElementById('enableNotificationBtn');
        const btnAllow         = document.getElementById('notifAllow');
        const btnDeny          = document.getElementById('notifDeny');
        const notifBadge       = document.getElementById('notifBadge');
        const deniedMessage    = document.getElementById('permissionDeniedMessage');
        const resetInstructions = document.getElementById('resetInstructions');

        // Fungsi open/close modal
        function openNotifModal() { notifModal?.showModal(); }
        function closeNotifModal() { notifModal?.close(); }

        // Reset UI modal saat dibuka
        function resetModalUI() {
            deniedMessage?.classList.add('hidden');
            btnAllow.disabled = false;
            btnAllow.textContent = 'Aktifkan Sekarang';
        }

        // Cek status permission saat halaman dimuat
        if ("Notification" in window) {
            const permission = Notification.permission;

            if (permission === "granted") {
                notifBadge?.classList.add('hidden');
            } 
            else if (permission === "denied") {
                notifBadge?.classList.remove('hidden');
            } 
            else {
                notifBadge?.classList.remove('hidden');
            }
        }

        // Event: tombol floating → buka modal + reset UI
        btnNotifTrigger?.addEventListener('click', () => {
            resetModalUI();
            
            // Kalau sudah denied, langsung tampilkan pesan panduan di modal
            if ("Notification" in window && Notification.permission === "denied") {
                deniedMessage?.classList.remove('hidden');
                const isPwa = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
                resetInstructions.textContent = isPwa 
                    ? "Cara mengaktifkan: Tekan lama ikon app di home screen → App info → Notifikasi → ubah ke Diizinkan. Lalu buka ulang aplikasi."
                    : "Cara mengaktifkan: Klik ikon gembok di address bar → Notifications → ubah ke Allow atau Ask (default). Lalu refresh halaman.";
                
                // Nonaktifkan tombol Aktifkan (karena browser tidak akan tampilkan popup lagi)
                btnAllow.disabled = true;
                btnAllow.textContent = 'Sudah Diblokir (Aktifkan Manual)';
            }

            openNotifModal();
        });

        // Tombol "Aktifkan" di modal → hanya request kalau bukan denied
        btnAllow?.addEventListener('click', async () => {
            if ("Notification" in window && Notification.permission === "denied") {
                // Jangan request lagi, user harus reset manual
                return;
            }

            closeNotifModal();

            try {
                const permission = await Notification.requestPermission();

                if (permission === "granted") {
                    alert("Notifikasi berhasil diaktifkan!\nAnda akan menerima update masjid.");
                    notifBadge?.classList.add('hidden');
                } 
                else if (permission === "denied") {
                    // Setelah request, kalau denied lagi → tampilkan pesan di modal saat dibuka ulang
                    notifBadge?.classList.remove('hidden');
                }
            } catch (err) {
                console.error("Error request notification:", err);
            }
        });

        // Tombol "Nanti Saja" → tutup modal
        btnDeny?.addEventListener('click', closeNotifModal);
    </script>

    @stack('scripts')

</body>
</html>