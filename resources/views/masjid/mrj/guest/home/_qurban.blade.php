        {{-- AJAKAN QURBAN --}}
        @php
            use App\Models\QurbanSetting;
            

                $showQurbanOnHome = QurbanSetting::get('show_qurban_on_home', true);
            
            if ($showQurbanOnHome) {
                $homeQurbanBadge = QurbanSetting::get('home_qurban_badge', '✨ HARI RAYA IDUL ADHA 1447 H✨');
                $homeQurbanTitleLine1 = QurbanSetting::get('home_qurban_title_line1', 'Raih Kemuliaan');
                $homeQurbanTitleLine2 = QurbanSetting::get('home_qurban_title_line2', 'Ibadah Qurban');
                $homeQurbanSubtitle = QurbanSetting::get('home_qurban_subtitle', '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah!" (QS. Al-Kautsar: 2)');
                $homeQurbanBenefits = QurbanSetting::get('home_qurban_benefits', ['Mendekatkan diri kepada Allah', 'Berbagi kebahagiaan', 'Amal yang paling mulia']);
                $homeQurbanBtnDaftarText = QurbanSetting::get('home_qurban_btn_daftar_text', 'Daftar Qurban');
                $homeQurbanBtnInfoText = QurbanSetting::get('home_qurban_btn_info_text', 'Info Lengkap Qurban');
                $homeQurbanLinkDaftar = QurbanSetting::get('home_qurban_link_daftar', '/qurban#form-pendaftaran');
                $homeQurbanLinkInfo = QurbanSetting::get('home_qurban_link_info', '/qurban#info-qurban');
                $homeQurbanTglPendaftaran = QurbanSetting::get('home_qurban_tgl_pendaftaran', '1 Apr - 20 Mei 2026');
                $homeQurbanTglPelaksanaan = QurbanSetting::get('home_qurban_tgl_pelaksanaan', '27 Mei 2026');
                $homeQurbanTglHijriah = QurbanSetting::get('home_qurban_tgl_hijriah', '10 Dzulhijjah 1447 H');
                $homeQurbanHargaMulai = QurbanSetting::get('home_qurban_harga_mulai', 'Rp 3.000.000,-');
                $homeQurbanImage = QurbanSetting::get('home_qurban_image', 'storage/qurban-hewan.png');
                $homeQurbanBgStart = QurbanSetting::get('home_qurban_bg_start', 'from-emerald-900');
                $homeQurbanBgMid = QurbanSetting::get('home_qurban_bg_mid', 'via-emerald-800');
                $homeQurbanBgEnd = QurbanSetting::get('home_qurban_bg_end', 'to-emerald-900');
            }
        @endphp
        
        @if($showQurbanOnHome)
            <section class="py-12 relative overflow-hidden">
                <div class="container mx-auto px-6 lg:px-16 xl:px-24">
                    <div class="relative group">
                        <div class="absolute -inset-0.5 bg-gradient-to-r from-emerald-600 via-amber-600 to-emerald-700 rounded-3xl blur-xl opacity-75 group-hover:opacity-100 transition duration-500"></div>
                        <div class="relative rounded-3xl overflow-hidden bg-gradient-to-br {{ $homeQurbanBgStart }} {{ $homeQurbanBgMid }} {{ $homeQurbanBgEnd }} shadow-2xl bg-pattern-islamic border border-white/10">
                            <div class="absolute inset-0 opacity-10">
                                <div class="absolute top-0 left-0 w-full h-full" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.1) 1px, transparent 1px); background-size: 40px 40px;"></div>
                                <div class="absolute -top-20 -right-20 w-80 h-80 bg-amber-500/20 rounded-full blur-3xl"></div>
                                <div class="absolute -bottom-32 -left-32 w-96 h-96 bg-emerald-500/20 rounded-full blur-3xl"></div>
                            </div>
                            <div class="relative z-10 flex flex-col lg:flex-row items-center justify-between p-6 md:p-8 lg:p-10 gap-6 lg:gap-8">
                                <div class="flex-1 text-center lg:text-left space-y-4 lg:space-y-5">
                                    <div class="inline-flex items-center gap-2 px-4 py-1.5 bg-amber-500/20 backdrop-blur-sm rounded-full border border-amber-400/50">
                                        <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-amber-500"></span></span>
                                        <span class="text-amber-300 text-xs font-semibold tracking-wider">{{ $homeQurbanBadge }}</span>
                                    </div>
                                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-extrabold leading-tight">
                                        <span class="text-white">{{ $homeQurbanTitleLine1 }}</span>
                                        <span class="block mt-2">
                                            <span class="relative inline-block">
                                                <span class="absolute inset-0 bg-gradient-to-r from-amber-500 to-orange-500 rounded-lg animate-pulse-label shadow-lg"></span>
                                                <span class="relative text-white font-bold z-10 px-6 py-2 inline-block drop-shadow-lg">{{ $homeQurbanTitleLine2 }}</span>
                                            </span>
                                        </span>
                                    </h2>
                                    <p class="text-emerald-100 text-base sm:text-lg leading-relaxed max-w-lg mx-auto lg:mx-0">{!! nl2br(e($homeQurbanSubtitle)) !!}</p>
                                    @php
                                        // Decode jika string JSON, atau pastikan array
                                        $benefits = is_string($homeQurbanBenefits) ? json_decode($homeQurbanBenefits, true) : $homeQurbanBenefits;
                                        if (!is_array($benefits)) {
                                            $benefits = [];
                                        }
                                    @endphp

                                    <div class="flex flex-wrap justify-center lg:justify-start gap-3 pt-2">
                                        @foreach($benefits as $benefit)
                                            <div class="flex items-center gap-2 px-3 py-1.5 bg-white/10 backdrop-blur-sm rounded-full">
                                                <span class="text-amber-400 text-sm">✓</span>
                                                <span class="text-white text-xs">{{ $benefit }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="flex flex-col sm:flex-row gap-3 pt-4 justify-center lg:justify-start">
                                        <a href="{{ $homeQurbanLinkDaftar }}" class="group relative inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-gradient-to-r from-amber-500 to-orange-500 hover:from-amber-600 hover:to-orange-600 text-white font-semibold rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-0.5 text-sm overflow-hidden">
                                            <span class="absolute inset-0 w-0 bg-gradient-to-r from-amber-600 to-orange-600 transition-all duration-500 group-hover:w-full"></span>
                                            <span class="relative z-10 text-base">🐏</span>
                                            <span class="relative z-10">{{ $homeQurbanBtnDaftarText }}</span>
                                            <span class="relative z-10 group-hover:translate-x-1 transition">→</span>
                                        </a>
                                        <a href="{{ $homeQurbanLinkInfo }}" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-white/10 backdrop-blur-sm border border-white/30 hover:bg-white/20 text-white font-medium rounded-full transition-all duration-300 text-sm hover:scale-105"><span>📖</span>{{ $homeQurbanBtnInfoText }}</a>
                                    </div>
                                    <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-emerald-900 via-emerald-800 to-emerald-900 shadow-xl border border-emerald-700/50 bg-pattern-islamic">
                                        <!-- Background Pattern -->
                                        <div class="absolute inset-0 opacity-5">
                                            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, rgba(255,255,255,0.15) 1px, transparent 1px); background-size: 40px 40px;"></div>
                                        </div>
                                        
                                        <!-- Konten Utama -->
                                        <div class="relative z-10 p-5">
                                            <!-- Baris 1: Judul & Countdown (Sejajar) -->
                                            <div class="flex flex-col sm:flex-row items-center sm:items-center justify-between gap-4">
                                                <!-- Kiri: Judul -->
                                                <div class="text-center sm:text-left">
                                                    <p class="text-white font-semibold text-base flex items-center justify-center sm:justify-start gap-2 flex-wrap">
                                                        Menuju Hari Raya Idul Adha
                                                    </p>
                                                    <p class="text-emerald-300 text-xs mt-0.5 text-center sm:text-left">10 Dzulhijjah 1447 H</p>
                                                </div>
                                                <!-- Kanan: Countdown -->
                                                <div class="flex items-center gap-2 bg-black/30 backdrop-blur-sm rounded-xl px-4 py-2 border border-white/10">
                                                    <div class="text-center min-w-[55px]">
                                                        <p id="qurbanCountdownDays" class="text-white text-xl md:text-2xl font-bold font-mono">00</p>
                                                        <p class="text-amber-400/60 text-[9px] uppercase tracking-wider">Hari</p>
                                                    </div>
                                                    <span class="text-amber-400/40 text-lg font-mono">:</span>
                                                    <div class="text-center min-w-[50px]">
                                                        <p id="qurbanCountdownHours" class="text-white text-xl md:text-2xl font-bold font-mono">00</p>
                                                        <p class="text-amber-400/60 text-[9px] uppercase tracking-wider">Jam</p>
                                                    </div>
                                                    <span class="text-amber-400/40 text-lg font-mono">:</span>
                                                    <div class="text-center min-w-[50px]">
                                                        <p id="qurbanCountdownMinutes" class="text-white text-xl md:text-2xl font-bold font-mono">00</p>
                                                        <p class="text-amber-400/60 text-[9px] uppercase tracking-wider">Menit</p>
                                                    </div>
                                                    <span class="text-amber-400/40 text-lg font-mono">:</span>
                                                    <div class="text-center min-w-[50px]">
                                                        <p id="qurbanCountdownSeconds" class="text-white text-xl md:text-2xl font-bold font-mono">00</p>
                                                        <p class="text-amber-400/60 text-[9px] uppercase tracking-wider">Detik</p>
                                                    </div>
                                                </div>
                                            </div>
                                                    
                                            <!-- Progress Bar -->
                                            <div class="mt-5">
                                                <div class="flex justify-between text-[10px] text-emerald-300 mb-1.5">
                                                    <span class="flex items-center gap-1">📅 Pendaftaran Dimulai</span>
                                                    <span class="flex items-center gap-1">🎯 Hari Raya Idul Adha</span>
                                                </div>
                                                <div class="h-1.5 bg-emerald-800/50 rounded-full overflow-hidden">
                                                    <div id="qurbanProgressBar" class="h-full bg-gradient-to-r from-amber-400 to-orange-500 rounded-full" style="width: 0%; transition: width 0.5s ease;"></div>
                                                </div>
                                                <div class="flex justify-between text-[9px] text-emerald-400/60 mt-1">
                                                    <span>24 April 2026</span>
                                                    <span>27 Mei 2026</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <script>
                                        function updateQurbanCountdown() {
                                            const targetDate = new Date('May 27, 2026 00:00:00').getTime();
                                            const now = new Date().getTime();
                                            const diff = targetDate - now;
                                            
                                            if (diff <= 0) {
                                                document.getElementById('qurbanCountdownDays').innerHTML = '00';
                                                document.getElementById('qurbanCountdownHours').innerHTML = '00';
                                                document.getElementById('qurbanCountdownMinutes').innerHTML = '00';
                                                document.getElementById('qurbanCountdownSeconds').innerHTML = '00';
                                                return;
                                            }
                                            
                                            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                                            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                                            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                                            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
                                            
                                            document.getElementById('qurbanCountdownDays').innerHTML = days.toString().padStart(2, '0');
                                            document.getElementById('qurbanCountdownHours').innerHTML = hours.toString().padStart(2, '0');
                                            document.getElementById('qurbanCountdownMinutes').innerHTML = minutes.toString().padStart(2, '0');
                                            document.getElementById('qurbanCountdownSeconds').innerHTML = seconds.toString().padStart(2, '0');
                                        }

                                        function updateQurbanProgressBar() {
                                            const targetDate = new Date('May 27, 2026 00:00:00').getTime();
                                            const startDate = new Date('April 24, 2026 00:00:00').getTime();
                                            const now = new Date().getTime();
                                            
                                            if (now < startDate) {
                                                document.getElementById('qurbanProgressBar').style.width = '0%';
                                                return;
                                            }
                                            
                                            if (now >= targetDate) {
                                                document.getElementById('qurbanProgressBar').style.width = '100%';
                                                return;
                                            }
                                            
                                            const total = targetDate - startDate;
                                            const elapsed = now - startDate;
                                            let progress = (elapsed / total) * 100;
                                            progress = Math.min(Math.max(progress, 0), 100);
                                            
                                            document.getElementById('qurbanProgressBar').style.width = progress + '%';
                                        }

                                        setInterval(function() {
                                            updateQurbanCountdown();
                                            updateQurbanProgressBar();
                                        }, 1000);

                                        updateQurbanCountdown();
                                        updateQurbanProgressBar();
                                    </script>
                                    <div class="flex flex-wrap items-center justify-center lg:justify-start gap-3 pt-4">
                                        <div class="flex items-center gap-2 px-3 py-2 bg-emerald-800/50 backdrop-blur-sm rounded-full border border-emerald-600/50 hover:bg-emerald-800/70 transition">
                                            <span class="text-amber-400 text-sm">📞</span>
                                            <span class="text-emerald-200 text-xs">Panitia Qurban:</span>
                                            <a href="https://wa.me/{{ waNumberInternational() }}" target="_blank" class="text-white text-xs font-semibold hover:text-amber-300 transition flex items-center gap-1">
                                                <span>💚</span> {{ waNumberFormatted() }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex-1 flex justify-center items-center">
                                    <div class="relative group/hewan">
                                        <div class="absolute inset-0 bg-gradient-to-tr from-amber-500/30 to-emerald-500/30 rounded-full blur-2xl animate-pulse"></div>
                                        <div class="relative transform hover:scale-105 transition-all duration-500 hover:rotate-2">
                                            <div class="absolute -top-4 -right-4 w-16 h-16 bg-amber-500/20 rounded-full blur-xl"></div>
                                            <div class="absolute -bottom-4 -left-4 w-16 h-16 bg-emerald-500/20 rounded-full blur-xl"></div>
                                            <div class="relative rounded-2xl overflow-hidden shadow-2xl border-2 border-white/20">
                                                <img src="{{ asset($homeQurbanImage) }}" alt="Hewan Qurban" loading="lazy" class="w-80 sm:w-96 h-auto object-cover transition-transform duration-700 group-hover/hewan:scale-110" onerror="this.src='https://placehold.co/400x300/059669/white?text=Hewan+Qurban'">
                                                <div class="absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-emerald-900 to-transparent"></div>
                                            </div>
                                            <div class="absolute top-1/2 -right-8 animate-spin-slow"><span class="text-xl drop-shadow-lg">✨</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-amber-500 to-transparent"></div>
                        </div>
                    </div>
                </div>
            </section>

            <style>
                @keyframes pulseLabel { 
                    0%, 100% { 
                        opacity: 0.8; 
                        transform: scale(1); 
                        box-shadow: 0 0 10px rgba(245, 158, 11, 0.3); 
                    } 
                    50% { 
                        opacity: 1; 
                        transform: scale(1.02); 
                        box-shadow: 0 0 30px rgba(245, 158, 11, 0.8); 
                    } 
                }
                @keyframes spin-slow { 
                    from { transform: rotate(0deg); } 
                    to { transform: rotate(360deg); } 
                }
                .animate-pulse-label { 
                    animation: pulseLabel 1.5s ease-in-out infinite; 
                }
                .animate-spin-slow { 
                    animation: spin-slow 8s linear infinite; 
                }
            </style>
        @endif

