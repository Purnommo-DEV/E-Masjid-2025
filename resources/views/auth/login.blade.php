<x-guest-layout>
    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-emerald-950 via-emerald-900 to-emerald-800 p-4">
        <!-- Card Login dengan efek glassmorphism -->
        <div class="w-full max-w-md bg-white/10 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl overflow-hidden transform transition-all duration-500 hover:scale-[1.02]">
            <!-- Header -->
            <div class="bg-gradient-to-r from-emerald-700 to-emerald-600 p-8 text-center">
                <div class="mx-auto w-20 h-20 bg-white/20 rounded-full flex items-center justify-center mb-4 backdrop-blur-sm">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-white mb-1">Selamat Datang</h2>
                <p class="text-emerald-100/90 text-sm">Masuk ke Sistem Informasi Masjid</p>
            </div>

            <!-- Form -->
            <div class="p-8">
                <!-- Session Status -->
                <x-auth-session-status class="mb-6 text-center text-emerald-200" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-6">
                        <x-input-label for="email" :value="__('Email')" class="text-emerald-100" />
                        <div class="relative mt-2">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </span>
                            <x-text-input 
                                id="email" 
                                class="block w-full pl-12 bg-white/10 border border-emerald-400/30 text-white placeholder-emerald-300 focus:border-emerald-400 focus:ring-emerald-500/50 rounded-xl" 
                                type="email" 
                                name="email" 
                                :value="old('email')" 
                                required 
                                autofocus 
                                autocomplete="username" 
                            />
                        </div>
                        <x-input-error :messages="$errors->get('email')" class="mt-2 text-red-300" />
                    </div>

                    <!-- Password -->
                    <div class="mb-6">
                        <x-input-label for="password" :value="__('Password')" class="text-emerald-100" />
                        <div class="relative mt-2">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-emerald-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 11c1.104 0 2-.896 2-2s-.896-2-2-2-2 .896-2 2 .896 2 2 2zm0 2c-2.761 0-5 2.239-5 5h10c0-2.761-2.239-5-5-5z"/>
                                </svg>
                            </span>
                            <x-text-input 
                                id="password" 
                                class="block w-full pl-12 bg-white/10 border border-emerald-400/30 text-white placeholder-emerald-300 focus:border-emerald-400 focus:ring-emerald-500/50 rounded-xl" 
                                type="password" 
                                name="password" 
                                required 
                                autocomplete="current-password" 
                            />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2 text-red-300" />
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mb-8">
                        <label class="flex items-center text-emerald-200 text-sm cursor-pointer">
                            <input id="remember_me" type="checkbox" class="rounded border-emerald-400/50 text-emerald-500 focus:ring-emerald-500/50 bg-white/10" name="remember">
                            <span class="ml-2">{{ __('Ingat saya') }}</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm text-emerald-300 hover:text-emerald-100 transition" href="{{ route('password.request') }}">
                                {{ __('Lupa password?') }}
                            </a>
                        @endif
                    </div>

                    <!-- Button Login -->
                    <x-primary-button class="w-full py-3 text-lg font-semibold bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500/50 transition-all duration-300 rounded-xl shadow-lg">
                        {{ __('Masuk') }}
                    </x-primary-button>
                </form>
            </div>

            <!-- Footer kecil -->
            <div class="px-8 py-6 text-center text-emerald-200/70 text-sm border-t border-white/10">
                © {{ date('Y') }} Sistem Informasi Masjid
            </div>
        </div>
    </div>
</x-guest-layout>