{{-- INFO CEPAT - Nilai & Komitmen Masjid --}}
<section class="relative py-10 -mt-2 z-10">
    <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
        
        <!-- Section Header -->
        <div class="mx-auto mb-8 max-w-4xl text-center sm:mb-10">
            @include('masjid.mrj.guest.components.section-heading', [
                'badgeIcon' => '✦',
                'badge' => 'Nilai & Komitmen',
                'title' => 'Prinsip Kami',
                'highlight' => null,
                'description' => "Menjadi masjid yang terbuka, transparan, dan bermanfaat bagi umat.<br class='hidden sm:block'>Kami berkomitmen menghadirkan pelayanan terbaik untuk jamaah dan masyarakat."
            ])
        </div>

        @php
            $infoCepat = [
                [
                    'icon'=>'🕌',
                    'text'=>'Terbuka untuk Semua Jamaah',
                    'desc'=>'Masjid yang ramah dan inklusif bagi seluruh umat',
                    'color'=>'emerald',
                    'badge'=>'Inklusif'
                ],
                [
                    'icon'=>'💚',
                    'text'=>'Pengelolaan Transparan',
                    'desc'=>'Laporan keuangan dan program terbuka untuk umum',
                    'color'=>'teal',
                    'badge'=>'Amanah'
                ],
                [
                    'icon'=>'❤️',
                    'text'=>'Aktif dalam Kegiatan Sosial',
                    'desc'=>'Peduli sesama melalui berbagai program kemasyarakatan',
                    'color'=>'rose',
                    'badge'=>'Peduli'
                ],
                [
                    'icon'=>'🌱',
                    'text'=>'Pembinaan Umat Berkelanjutan',
                    'desc'=>'Program pengembangan diri dan keislaman secara berkesinambungan',
                    'color'=>'cyan',
                    'badge'=>'Berkesinambungan'
                ],
            ];
        @endphp

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($infoCepat as $info)
                <div class="group relative bg-white rounded-2xl border border-slate-200/80 px-5 py-5 shadow-md hover:shadow-2xl hover:shadow-{{ $info['color'] }}-200/50 hover:border-{{ $info['color'] }}-300 hover:-translate-y-1.5 transition-all duration-500 overflow-hidden">
                    
                    <!-- Background gradient on hover -->
                    <div class="absolute inset-0 bg-gradient-to-br from-{{ $info['color'] }}-50/0 via-{{ $info['color'] }}-50/0 to-{{ $info['color'] }}-50/0 group-hover:from-{{ $info['color'] }}-50/80 group-hover:via-{{ $info['color'] }}-50/50 group-hover:to-{{ $info['color'] }}-50/30 transition-all duration-500"></div>
                    
                    <!-- Glow effect -->
                    <div class="absolute -top-10 -right-10 w-32 h-32 bg-{{ $info['color'] }}-400/0 rounded-full blur-2xl group-hover:bg-{{ $info['color'] }}-400/20 transition-all duration-700"></div>
                    <div class="absolute -bottom-10 -left-10 w-32 h-32 bg-{{ $info['color'] }}-400/0 rounded-full blur-2xl group-hover:bg-{{ $info['color'] }}-400/20 transition-all duration-700"></div>
                    
                    <div class="relative flex flex-col items-center text-center gap-3">
                        <!-- Icon -->
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-{{ $info['color'] }}-500 to-{{ $info['color'] }}-600 text-3xl text-white shadow-lg shadow-{{ $info['color'] }}-500/30 group-hover:scale-110 group-hover:rotate-3 group-hover:shadow-xl group-hover:shadow-{{ $info['color'] }}-500/50 transition-all duration-500 relative">
                            {{ $info['icon'] }}
                            <!-- Pulse ring -->
                            <div class="absolute inset-0 rounded-2xl border-2 border-{{ $info['color'] }}-400/0 group-hover:border-{{ $info['color'] }}-400/30 animate-ping group-hover:animate-none"></div>
                        </div>
                        
                        <div class="flex-1">
                            <!-- Badge -->
                            <div class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-{{ $info['color'] }}-50/80 text-{{ $info['color'] }}-700 text-[10px] font-semibold border border-{{ $info['color'] }}-200/50 mb-1.5">
                                <span class="w-1 h-1 rounded-full bg-{{ $info['color'] }}-500"></span>
                                {{ $info['badge'] }}
                            </div>
                            
                            <h3 class="text-sm font-bold text-slate-800 group-hover:text-{{ $info['color'] }}-700 transition-colors duration-300 leading-tight">
                                {{ $info['text'] }}
                            </h3>
                            <p class="text-[11px] text-slate-500 mt-1 group-hover:text-slate-600 transition-colors duration-300 leading-relaxed">
                                {{ $info['desc'] }}
                            </p>
                        </div>
                    </div>

                    <!-- Decorative line bottom -->
                    <div class="absolute bottom-0 left-0 right-0 h-0.5 bg-gradient-to-r from-{{ $info['color'] }}-400 to-{{ $info['color'] }}-500 scale-x-0 group-hover:scale-x-100 transition-transform duration-500 origin-left rounded-full"></div>
                </div>
            @endforeach
        </div>
    </div>
</section>