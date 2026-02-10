    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login — {{ profil('nama') ?? 'Sistem Masjid' }}</title>
        <link rel="icon" href="{{ asset('elantera.png') }}" type="image/png">
        
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        @vite('resources/css/app.css')
        
        <style>
            /* Animasi halus */
            .fade-in {
                animation: fadeIn 0.6s ease-in-out;
            }
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(8px); }
                to { opacity: 1; transform: translateY(0); }
            }
            /* Pulse logo subtle */
            .pulse-logo {
                animation: pulse 4s infinite ease-in-out;
            }
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.05); }
            }
        </style>
    </head>
    <body class="min-h-screen bg-gradient-to-br from-emerald-950 via-emerald-900 to-cyan-950 flex items-center justify-center px-4">
        <div class="w-full max-w-md">
            
            <!-- Logo + Judul -->
            <div class="text-center mb-8 fade-in">
                <div class="bg-white/10 backdrop-blur-lg w-20 h-20 rounded-2xl flex items-center justify-center mx-auto shadow-lg ring-1 ring-emerald-400/20 pulse-logo">
                    <img src="{{ asset('pwa/mrj-logo.png') }}" alt="Logo Masjid" class="w-16 h-16 object-contain drop-shadow">
                </div>
                <h2 class="text-3xl font-bold text-white mt-4 drop-shadow-md">
                    {{ profil('nama') ?? 'Sistem Informasi Masjid' }}
                </h2>
                <p class="text-emerald-100/80 mt-1 text-sm">
                    Akses dashboard & informasi masjid
                </p>
                <p class="text-cyan-100/70 mt-1 text-xs italic">
                    Bersama membangun jamaah yang lebih baik
                </p>
            </div>

            <!-- Warning Offline -->
            <div id="offline-warning" class="alert alert-warning shadow-lg mb-6 hidden" role="alert">
                <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Anda sedang offline. Login tidak tersedia saat ini. Silakan coba lagi saat terhubung internet.</span>
            </div>

            <!-- Card Login -->
            <div class="bg-white/10 backdrop-blur-xl border border-emerald-400/20 rounded-2xl shadow-2xl p-8 fade-in">
                @if (session('status'))
                    <div class="mb-4 p-3 rounded-lg bg-emerald-800/50 text-emerald-100 text-sm text-center">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <label class="block text-emerald-100 font-semibold mb-2">Email</label>
                    <input
                        type="email"
                        name="email"
                        id="email-input"
                        value="{{ old('email') }}"
                        class="mt-1 w-full rounded-xl border border-emerald-400/30 bg-white/5 text-white placeholder-emerald-300 focus:border-emerald-400 focus:ring-emerald-500/40 focus:bg-white/10 transition-all py-3 px-4"
                        required
                        autofocus
                    >
                    @error('email')
                        <p class="text-red-300 text-sm mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Password -->
                    <label class="block mt-6 text-emerald-100 font-semibold mb-2">Password</label>
                    <input
                        type="password"
                        name="password"
                        class="mt-1 w-full rounded-xl border border-emerald-400/30 bg-white/5 text-white placeholder-emerald-300 focus:border-emerald-400 focus:ring-emerald-500/40 focus:bg-white/10 transition-all py-3 px-4"
                        required
                    >
                    @error('password')
                        <p class="text-red-300 text-sm mt-1">{{ $message }}</p>
                    @enderror

                    <!-- Remember Me -->
                    <div class="flex items-center mt-6">
                        <input id="remember_me" type="checkbox" class="rounded border-emerald-400/50 text-emerald-500 focus:ring-emerald-500/50 bg-white/10" name="remember">
                        <label for="remember_me" class="ml-3 text-emerald-100 text-sm cursor-pointer">
                            Ingat saya
                        </label>
                    </div>

                    <!-- Tombol Login -->
                    <button
                        type="submit"
                        id="login-button"
                        class="w-full mt-8 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-cyan-600 text-white font-bold shadow-lg hover:shadow-xl hover:from-emerald-700 hover:to-cyan-700 transition-all duration-300 hover:scale-[1.02] disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        Masuk
                    </button>

                    <!-- Lupa Password -->
                    @if (Route::has('password.request'))
                        <div class="text-right mt-4">
                            <a href="{{ route('password.request') }}" class="text-sm text-emerald-300 hover:text-emerald-100 transition">
                                Lupa password?
                            </a>
                        </div>
                    @endif
                </form>
            </div>

            <!-- Footer -->
            <p class="text-center text-emerald-100/70 text-sm mt-8 fade-in">
                © {{ date('Y') }} | {{ profil('nama') ?? 'Sistem Informasi Masjid' }}<br>
                <span class="text-cyan-100/70">DKM Masjid Raudhotul Jannah</span>
            </p>
        </div>

        <!-- Script Offline Handling & LocalStorage Email -->
        <script>
            const offlineWarning = document.getElementById('offline-warning');
            const loginButton = document.getElementById('login-button');
            const emailInput = document.getElementById('email-input');

            function updateOfflineStatus() {
                if (!navigator.onLine) {
                    offlineWarning.classList.remove('hidden');
                    loginButton.disabled = true;
                    loginButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    offlineWarning.classList.add('hidden');
                    loginButton.disabled = false;
                    loginButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            }

            updateOfflineStatus();
            window.addEventListener('online', updateOfflineStatus);
            window.addEventListener('offline', updateOfflineStatus);

            // Simpan email sementara di localStorage
            if (emailInput) {
                const savedEmail = localStorage.getItem('lastLoginEmail');
                if (savedEmail) emailInput.value = savedEmail;
                emailInput.addEventListener('input', () => {
                    localStorage.setItem('lastLoginEmail', emailInput.value);
                });
            }

            // Redirect ke dashboard kalau offline & pernah login
            if (!navigator.onLine && localStorage.getItem('isLoggedIn') === 'true') {
                Swal.fire({
                    title: 'Mode Offline',
                    text: 'Anda masih bisa akses dashboard (data sebelumnya).',
                    icon: 'info',
                    confirmButtonText: 'Lanjut ke Dashboard',
                    confirmButtonColor: '#10b981'  // emerald-600
                }).then(() => {
                    window.location.href = '/dashboard'; // ganti sesuai route dashboard kamu
                });
            }
        </script>

        <script src="https://kit.fontawesome.com/a2d9d6c66f.js" crossorigin="anonymous"></script>
    </body>
    </html>