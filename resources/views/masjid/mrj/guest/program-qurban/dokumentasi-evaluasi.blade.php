<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Dokumentasi Evaluasi Qurban {{ $tahun }} | Masjid Raudhatul Jannah TCE</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    
    <style>
        body { background: linear-gradient(145deg, #f0fdf4 0%, #ecfdf5 100%); }
        .gradient-hero { background: linear-gradient(125deg, #022c22 0%, #065f46 35%, #059669 70%, #10b981 100%); }
        .card-feedback { transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1); border: 1px solid rgba(16, 185, 129, 0.15); }
        .card-feedback:hover { transform: translateY(-4px); box-shadow: 0 20px 30px -12px rgba(2, 44, 34, 0.2); border-color: rgba(16, 185, 129, 0.4); }
        .badge-kategori { background: linear-gradient(135deg, #065f46, #10b981); }
        .wish-bubble { background: linear-gradient(105deg, #fef3c7 0%, #fffbeb 100%); border-left: 4px solid #fbbf24; }
        .divider-custom { background: linear-gradient(90deg, transparent, #10b981, #fbbf24, #10b981, transparent); height: 2px; }
        .tahun-selector { transition: all 0.2s; }
        .tahun-selector:hover { background: #f0fdf4; border-color: #10b981; }
    </style>
</head>
<body class="font-sans antialiased">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">
        
        <!-- HERO SECTION -->
        <div class="gradient-hero rounded-3xl shadow-2xl mb-10 overflow-hidden relative">
            <div class="relative z-10 px-6 py-12 md:py-16 text-center">
                <div class="inline-flex items-center gap-2 bg-white/20 backdrop-blur-md rounded-full px-5 py-2 mb-5">
                    <span class="text-white text-sm font-semibold tracking-wide">MASJID RAUDHATUL JANNAH TCE</span>
                </div>
                <h1 class="text-3xl md:text-5xl font-extrabold text-white tracking-tight">
                    Dokumentasi Lengkap <br>
                    <span class="text-gold">Masukan & Kritik Pequrban</span>
                </h1>
                <p class="text-emerald-100 text-lg max-w-2xl mx-auto mt-4">
                    {{ $tahun }} · Agar setiap suara dipahami maknanya dan keinginannya
                </p>
                
                <div class="flex justify-center mt-6">
                    <div class="inline-flex bg-white/10 rounded-full p-1 backdrop-blur-sm">
                        @foreach($tahunList as $thn)
                            <a href="{{ route('guest.dokumentasi-evaluasi', $thn) }}" 
                               class="tahun-selector px-5 py-2 rounded-full text-sm font-medium transition-all {{ $thn == $tahun ? 'bg-white text-emerald-800 shadow-md' : 'text-white hover:bg-white/20' }}">
                                {{ $thn }}
                            </a>
                        @endforeach
                    </div>
                </div>
                
                <div class="flex flex-wrap justify-center gap-3 mt-6">
                    <div class="bg-white/10 rounded-full px-4 py-1.5 text-white text-sm"><i class="fas fa-comment-dots mr-1"></i> {{ $totalMasukan }} Masukan</div>
                    <div class="bg-white/10 rounded-full px-4 py-1.5 text-white text-sm"><i class="fas fa-chart-line mr-1"></i> Lengkap & Utuh</div>
                </div>
            </div>
        </div>

        <!-- PENGANTAR -->
        <div class="bg-amber-50 border-l-8 border-gold rounded-2xl p-5 mb-10 shadow-md">
            <div class="flex gap-3 items-start">
                <i class="fas fa-lightbulb text-gold text-2xl mt-1"></i>
                <div>
                    <h3 class="font-bold text-amber-800 text-lg">📌 Agar Tidak Ada yang Diminimalkan</h3>
                    <p class="text-amber-700">Halaman ini menampilkan <strong>seluruh komentar asli pequrban</strong> tanpa diedit. Setiap masukan disertai <strong class="bg-amber-200 px-1">"Maksud & Keinginan"</strong> agar panitia paham apa yang ingin disampaikan.</p>
                </div>
            </div>
        </div>

        @if($data->isEmpty())
            <div class="text-center py-12 bg-white rounded-2xl shadow">
                <i class="fas fa-inbox text-5xl text-gray-300 mb-3"></i>
                <p class="text-gray-500">Belum ada data evaluasi untuk tahun {{ $tahun }}.</p>
            </div>
        @else

        <!-- A. INFORMASI & PROMOSI -->
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-5">
                <div class="badge-kategori w-10 h-10 rounded-xl flex items-center justify-center"><i class="fas fa-bullhorn text-white text-lg"></i></div>
                <h2 class="text-2xl font-bold text-gray-800">A. Informasi & Promosi Qurban</h2>
            </div>
            <div class="divider-custom w-full mb-4"></div>

            @if(count($kategori['informasi']['pujian']) > 0)
            <h3 class="text-lg font-semibold text-emerald-700 mb-3"><i class="fas fa-thumbs-up text-emerald-500"></i> ✅ Yang Sudah Baik (Pertahankan)</h3>
            <div class="grid md:grid-cols-2 gap-5 mb-6">
                @foreach($kategori['informasi']['pujian'] as $item)
                <div class="card-feedback bg-white rounded-xl p-5 shadow-sm">
                    <i class="fas fa-quote-left text-emerald-300 mb-2"></i>
                    <p class="text-gray-700 italic">"{{ $item->masukan_penyebaran_informasi }}"</p>
                    <div class="mt-2 text-sm font-medium text-emerald-600">— {{ $item->nama_shohibul }}</div>
                    @if($item->wish)
                    <div class="mt-3 bg-emerald-50 p-3 rounded-lg">
                        <i class="fas fa-bullseye text-emerald-500 mr-1"></i> 
                        <span class="font-semibold">🎯 Maksud & Keinginan:</span> {{ $item->wish }}
                    </div>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            @if(count($kategori['informasi']['kritik']) > 0)
            <h3 class="text-lg font-semibold text-orange-600 mb-3"><i class="fas fa-exclamation-triangle text-orange-500"></i> ⚠️ Kritik & Saran Perbaikan</h3>
            <div class="grid md:grid-cols-2 gap-5">
                @foreach($kategori['informasi']['kritik'] as $item)
                <div class="card-feedback bg-white rounded-xl p-5 shadow-sm border-l-4 border-orange-300">
                    <i class="fas fa-quote-left text-orange-300 mb-2"></i>
                    <p class="text-gray-700 italic">"{{ $item->masukan_penyebaran_informasi }}"</p>
                    <div class="mt-2 text-sm font-medium text-orange-600">— {{ $item->nama_shohibul }}</div>
                    <div class="mt-3 wish-bubble p-3 rounded-lg">
                        <i class="fas fa-gem text-amber-500 mr-1"></i> 
                        <span class="font-semibold">🎯 Maksud & Keinginan:</span> {{ $item->wish ?? 'Silakan tinjau kembali untuk perbaikan.' }}
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <!-- B. PENDAFTARAN -->
        @if(count($kategori['pendaftaran']) > 0)
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-5">
                <div class="badge-kategori w-10 h-10 rounded-xl"><i class="fas fa-credit-card text-white text-lg"></i></div>
                <h2 class="text-2xl font-bold text-gray-800">B. Pendaftaran & Pembayaran</h2>
            </div>
            <div class="divider-custom w-full mb-4"></div>
            <div class="grid md:grid-cols-2 gap-5">
                @foreach($kategori['pendaftaran'] as $item)
                <div class="card-feedback bg-white rounded-xl p-5 shadow-sm">
                    <i class="fas fa-quote-left text-emerald-300 mb-2"></i>
                    <p class="text-gray-700 italic">"{{ $item->saran_pendaftaran }}"</p>
                    <div class="mt-2 text-sm font-medium text-emerald-600">— {{ $item->nama_shohibul }}</div>
                    <div class="mt-3 bg-emerald-50 p-3 rounded-lg">
                        <i class="fas fa-bullseye text-emerald-500 mr-1"></i> 
                        <span class="font-semibold">🎯 Maksud & Keinginan:</span> {{ $item->wish ?? 'Pertahankan atau tingkatkan pelayanan pendaftaran.' }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- C. PENYEMBELIHAN -->
        @if(count($kategori['penyembelihan']) > 0)
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-5">
                <div class="badge-kategori w-10 h-10 rounded-xl"><i class="fas fa-drumstick-bite text-white text-lg"></i></div>
                <h2 class="text-2xl font-bold text-gray-800">C. Penyembelihan, Pencacahan & Pengemasan <span class="text-sm bg-red-100 text-red-600 px-2 py-0.5 rounded-full ml-2">PRIORITAS</span></h2>
            </div>
            <div class="divider-custom w-full mb-4"></div>
            <div class="bg-red-50 p-4 rounded-xl mb-4 text-red-800 text-sm"><i class="fas fa-chart-line mr-2"></i> Area ini mendapat banyak keluhan. Semua masukan disertakan secara utuh.</div>
            <div class="grid gap-5">
                @foreach($kategori['penyembelihan'] as $item)
                <div class="card-feedback bg-white rounded-xl p-5 shadow-sm border-l-8 border-red-400">
                    <i class="fas fa-quote-left text-red-300 mb-2"></i>
                    <p class="text-gray-700 italic">"{{ $item->saran_pelaksanaan }}"</p>
                    <div class="mt-2 text-sm font-medium text-red-600">— {{ $item->nama_shohibul }}</div>
                    <div class="mt-3 bg-red-50 p-3 rounded-lg">
                        <i class="fas fa-bullhorn text-red-500 mr-1"></i> 
                        <span class="font-semibold">🎯 Maksud & Keinginan:</span> {{ $item->wish ?? 'Perbaiki proses penyembelihan, perbanyak tim jagal.' }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- D. DISTRIBUSI -->
        @if(count($kategori['distribusi']) > 0)
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-5">
                <div class="badge-kategori w-10 h-10 rounded-xl"><i class="fas fa-truck text-white text-lg"></i></div>
                <h2 class="text-2xl font-bold text-gray-800">D. Distribusi Daging</h2>
            </div>
            <div class="divider-custom w-full mb-4"></div>
            <div class="grid md:grid-cols-2 gap-5">
                @foreach($kategori['distribusi'] as $item)
                <div class="card-feedback bg-white rounded-xl p-5 shadow-sm">
                    <i class="fas fa-quote-left text-amber-300 mb-2"></i>
                    <p class="text-gray-700 italic">"{{ $item->saran_distribusi }}"</p>
                    <div class="mt-2 text-sm font-medium text-amber-700">— {{ $item->nama_shohibul }}</div>
                    <div class="mt-3 wish-bubble p-3 rounded-lg">
                        <span class="font-semibold">🎯 Keinginan:</span> {{ $item->wish ?? 'Tingkatkan sistem distribusi.' }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- E. KUALITAS HEWAN -->
        @if(count($kategori['kualitas']) > 0)
        <div class="mb-12">
            <div class="flex items-center gap-3 mb-5">
                <div class="badge-kategori w-10 h-10 rounded-xl"><i class="fas fa-drumstick-bite text-white text-lg"></i></div>
                <h2 class="text-2xl font-bold text-gray-800">E. Kualitas Hewan Qurban</h2>
            </div>
            <div class="divider-custom w-full mb-4"></div>
            <div class="grid md:grid-cols-2 gap-5">
                @foreach($kategori['kualitas'] as $item)
                <div class="card-feedback bg-white rounded-xl p-5 shadow-sm">
                    <p class="text-gray-700 italic">"{{ $item->saran_kualitas_hewan }}"</p>
                    <div class="mt-2 text-sm font-medium text-emerald-600">— {{ $item->nama_shohibul }}</div>
                    <div class="mt-2 text-green-700 text-sm">✅ Pertahankan standar kualitas.</div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- RINGKASAN KESELURUHAN (AI SUMMARY) -->
        <div class="bg-gradient-to-r from-emerald-900 to-teal-800 rounded-2xl p-6 md:p-8 text-white shadow-xl mt-8">
            <div class="flex items-center gap-3 mb-4">
                <i class="fas fa-list-check text-gold text-2xl"></i>
                <h3 class="text-2xl font-bold">Ringkasan: Apa yang Pequrban Inginkan?</h3>
                @if($aiSummary)<span class="text-xs bg-purple-500/30 px-2 py-1 rounded-full">✨ AI Summary</span>@endif
            </div>
            
            @if($aiSummary && $aiSummary->summary_data)
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    @foreach($aiSummary->summary_data as $category => $points)
                        @if(!empty($points))
                            <div class="bg-white/10 rounded-xl p-3">
                                <h4 class="font-bold text-gold mb-2">
                                    @switch($category)
                                        @case('informasi') 📢 Informasi @break
                                        @case('pendaftaran') 💳 Pendaftaran @break
                                        @case('penyembelihan') 🔪 Penyembelihan @break
                                        @case('distribusi') 🚚 Distribusi @break
                                        @case('kualitas') 🥩 Kualitas @break
                                        @default {{ $category }}
                                    @endswitch
                                </h4>
                                <ul class="space-y-1">
                                    @foreach($points as $point)
                                        <li><i class="fas fa-check-circle text-gold text-xs mr-2"></i> {{ $point }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <div class="grid md:grid-cols-2 gap-4 text-sm">
                    <div class="bg-white/10 rounded-xl p-3">✅ Tim jagal profesional + tambah mesin & operator</div>
                    <div class="bg-white/10 rounded-xl p-3">✅ Tempat penyembelihan diperluas</div>
                    <div class="bg-white/10 rounded-xl p-3">✅ Info harga H-1 bulan sebelum Idul Adha</div>
                    <div class="bg-white/10 rounded-xl p-3">✅ Jadwal pasti untuk setiap pequrban</div>
                    <div class="bg-white/10 rounded-xl p-3">✅ Pisahkan hewan yang belum disembelih</div>
                    <div class="bg-white/10 rounded-xl p-3">✅ Google Form + QRIS untuk kemudahan</div>
                </div>
            @endif
        </div>

        @endif

        <div class="text-center text-gray-400 text-xs mt-10 pt-6 border-t border-emerald-200">
            <i class="fas fa-mosque"></i> Masjid Raudhatul Jannah · Laporan Evaluasi Qurban {{ $tahun }}<br>
            Semua masukan ditampilkan secara utuh agar setiap suara didengar dan dipahami.
        </div>
    </div>
</body>
</html>