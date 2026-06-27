        {{-- INFO CEPAT --}}
        <section class="pb-10 -mt-2">
            <div class="container mx-auto px-6 lg:px-16 xl:px-24 relative">
                @php
                    $infoCepat = [
                        ['icon'=>'🕌','text'=>'Kajian Rutin & Pembinaan Umat'],
                        ['icon'=>'📿','text'=>'Shalat 5 Waktu Berjamaah'],
                        ['icon'=>'🤝','text'=>'Layanan Sosial & Umat'],
                    ];
                @endphp

                <div class="grid md:grid-cols-3 gap-4">
                    @foreach($infoCepat as $info)
                        <div class="group flex items-center gap-4 rounded-2xl bg-white/75 backdrop-blur-md border border-white/50 px-5 py-4 shadow-sm hover:shadow-lg hover:shadow-emerald-200/20 hover:border-emerald-300/50 hover:-translate-y-1 transition-all duration-300">
                            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-50/80 text-xl shadow-inner group-hover:bg-gradient-to-r group-hover:from-emerald-500 group-hover:to-teal-500 group-hover:text-white transition-all duration-300">{{ $info['icon'] }}</div>
                            <p class="text-[13px] font-medium text-slate-700 leading-snug">{{ $info['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

