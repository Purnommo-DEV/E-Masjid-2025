@extends('masjid.master')

@section('title', 'Pengaturan Qurban')

@section('content')

<div class="container mx-auto px-4 py-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 rounded-t-2xl p-6 text-white">
            <div class="flex justify-between items-center flex-wrap gap-4">
                <div>
                    <h1 class="text-2xl font-bold">⚙️ Pengaturan Qurban</h1>
                    <p class="text-emerald-100 mt-1">Kelola konten halaman qurban (hero, kontak, bank, FAQ, home, dll)</p>
                </div>
                <div class="flex gap-3">
                    <button type="button" id="btnReset" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Reset Default
                    </button>
                    <a href="{{ route('admin.qurban.paket.index') }}" class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-xl transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7 7-7M3 12h14" />
                        </svg>
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="bg-white rounded-b-2xl shadow-lg p-6">
            <form id="settingsForm">
                @csrf
                
                <!-- Tabs Navigation -->
                <div class="flex flex-wrap gap-2 border-b border-gray-200 mb-6">
                    <button type="button" class="tab-btn active px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="tampilan">🖥️ Tampilan Home</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="home">🏠 Konten Home</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="hero">🏠 Hero Section</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="kontak">📞 Kontak & Bank</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="statistik">📊 Statistik</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="harga">💰 Harga Potong</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="catatan">📝 Catatan Penting</button>
                    <button type="button" class="tab-btn px-4 py-2 text-sm font-medium rounded-t-lg" data-tab="faq">❓ FAQ</button>
                </div>

                <!-- ==================== TAB: TAMPILAN HOME ==================== -->
                <div id="tab-tampilan" class="tab-pane active">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">🖥️ Tampilan di Halaman Utama (Home)</h4>
                        <div class="flex items-center justify-between p-4 bg-white rounded-xl border border-gray-200">
                            <div>
                                <label class="font-semibold text-slate-800">Tampilkan Section Qurban di Home</label>
                                <p class="text-xs text-slate-500">Aktifkan untuk menampilkan ajakan qurban di halaman utama website</p>
                            </div>
                            <div>
                                <input type="checkbox" name="show_qurban_on_home" id="show_qurban_on_home" class="toggle toggle-success" value="true" {{ $showQurbanOnHome ?? true ? 'checked' : '' }}>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: KONTEN HOME ==================== -->
                <div id="tab-home" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">🏠 Konten Section Qurban di Halaman Home</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Badge Teks</label>
                                <input type="text" name="home_qurban_badge" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanBadge ?? '✨ HARI RAYA IDUL ADHA 1447 H / 27 MEI 2026 ✨' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Baris 1</label>
                                <input type="text" name="home_qurban_title_line1" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanTitleLine1 ?? 'Raih Kemuliaan' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Baris 2 (Highlight)</label>
                                <input type="text" name="home_qurban_title_line2" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanTitleLine2 ?? 'Ibadah Qurban' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                                <textarea name="home_qurban_subtitle" class="w-full px-4 py-2 border border-gray-300 rounded-lg" rows="2">{{ $homeQurbanSubtitle ?? '"Maka dirikanlah shalat karena Tuhanmu dan berqurbanlah!" (QS. Al-Kautsar: 2)' }}</textarea>
                            </div>
                            
                            <!-- Manfaat -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">List Manfaat</label>
                                <div id="benefits-container" class="space-y-2">
                                    @php
                                        $benefits = $homeQurbanBenefits ?? ['Mendekatkan diri kepada Allah', 'Berbagi kebahagiaan', 'Amal yang paling mulia'];
                                    @endphp
                                    @foreach($benefits as $benefit)
                                    <div class="flex gap-2 benefit-item">
                                        <input type="text" name="home_qurban_benefits[]" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" value="{{ $benefit }}">
                                        <button type="button" class="remove-benefit px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                                <button type="button" id="add-benefit" class="mt-2 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                    Tambah Manfaat
                                </button>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tombol Daftar - Teks</label>
                                    <input type="text" name="home_qurban_btn_daftar_text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanBtnDaftarText ?? 'Daftar Qurban' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tombol Info - Teks</label>
                                    <input type="text" name="home_qurban_btn_info_text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanBtnInfoText ?? 'Info Lengkap Qurban' }}">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Link Tombol Daftar</label>
                                    <input type="text" name="home_qurban_link_daftar" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanLinkDaftar ?? '/qurban#form-pendaftaran' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Link Tombol Info</label>
                                    <input type="text" name="home_qurban_link_info" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanLinkInfo ?? '/qurban#info-qurban' }}">
                                </div>
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pendaftaran</label>
                                    <input type="text" name="home_qurban_tgl_pendaftaran" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanTglPendaftaran ?? '1 Apr - 20 Mei 2026' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Pelaksanaan</label>
                                    <input type="text" name="home_qurban_tgl_pelaksanaan" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanTglPelaksanaan ?? '27 Mei 2026' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Hijriah</label>
                                    <input type="text" name="home_qurban_tgl_hijriah" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanTglHijriah ?? '10 Dzulhijjah 1447 H' }}">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Label Harga Mulai Dari</label>
                                <input type="text" name="home_qurban_harga_mulai" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanHargaMulai ?? 'Rp 3.000.000,-' }}">
                            </div>
                            
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Background Gradient Start</label>
                                    <input type="text" name="home_qurban_bg_start" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanBgStart ?? 'from-emerald-900' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Background Gradient Mid</label>
                                    <input type="text" name="home_qurban_bg_mid" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanBgMid ?? 'via-emerald-800' }}">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Background Gradient End</label>
                                    <input type="text" name="home_qurban_bg_end" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $homeQurbanBgEnd ?? 'to-emerald-900' }}">
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Gambar Hewan Qurban</label>
                                <input type="file" name="home_qurban_image" id="home_qurban_image" accept="image/*" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                                <div id="image-preview-container" class="mt-2 {{ $homeQurbanImage ?? false ? '' : 'hidden' }}">
                                    <img id="image-preview" src="{{ isset($homeQurbanImage) && $homeQurbanImage ? asset($homeQurbanImage) : '' }}" class="h-32 rounded-lg object-cover border">
                                    <button type="button" id="remove-image" class="mt-1 text-xs text-red-600 hover:text-red-700">Hapus Gambar</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: HERO SECTION ==================== -->
                <div id="tab-hero" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">🏠 Hero Section</h4>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hero Title</label>
                                <input type="text" name="hero_title" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" value="{{ $heroTitle ?? 'Masjid Raudhotul Jannah' }}">
                                <p class="text-xs text-gray-400 mt-1">Judul utama di halaman qurban</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hero Subtitle</label>
                                <input type="text" name="hero_subtitle" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" value="{{ $heroSubtitle ?? 'Menerima & Menyalurkan Hewan Qurban' }}">
                                <p class="text-xs text-gray-400 mt-1">Subjudul di bawah title</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Badge Teks</label>
                                <input type="text" name="hero_badge_text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500" value="{{ $heroBadge ?? 'PANITIA IDUL ADHA 1447 H / 2026 M' }}">
                                <p class="text-xs text-gray-400 mt-1">Badge yang muncul di atas title</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: KONTAK & BANK ==================== -->
                <div id="tab-kontak" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6 mb-4">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">📞 Kontak Informasi & Pendaftaran</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kontak Informasi</label>
                                <input type="text" name="contact_info_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $contactInfoName ?? 'Bapak Joko' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">No WhatsApp Informasi</label>
                                <input type="text" name="contact_info_phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $contactInfoPhone ?? '085716503815' }}">
                                <p class="text-xs text-gray-400">Masukkan tanpa tanda +62, cukup angka</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Kontak Konfirmasi</label>
                                <input type="text" name="contact_confirmation_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $contactConfirmName ?? 'Bapak Jazuli' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">No WhatsApp Konfirmasi</label>
                                <input type="text" name="contact_confirmation_phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $contactConfirmPhone ?? '081310185948' }}">
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">🏦 Rekening Bank</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Bank</label>
                                <input type="text" name="bank_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $bankName ?? 'BCA' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Nomor Rekening</label>
                                <input type="text" name="bank_account_number" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $bankAccount ?? '1010010947479' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Atas Nama</label>
                                <input type="text" name="bank_account_name" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $bankAccountName ?? 'JAZULI' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: STATISTIK ==================== -->
                <div id="tab-statistik" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">📊 Statistik (Feature Cards)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Hewan Tersedia</label>
                                <input type="text" name="stats_hewan_tersedia" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $statsHewan ?? '50+' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Lokasi Distribusi</label>
                                <input type="text" name="stats_lokasi_distribusi" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $statsLokasi ?? 'TCE' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Penerima Manfaat</label>
                                <input type="text" name="stats_penerima_manfaat" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $statsPenerima ?? '500+' }}">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tersalurkan</label>
                                <input type="text" name="stats_tersalurkan" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $statsTersalurkan ?? '100%' }}">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: HARGA POTONG ==================== -->
                <div id="tab-harga" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">💰 Biaya Potong & Distribusi (Bawa Hewan Sendiri)</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Potong Sapi (Rp.)</label>
                                <div class="relative">
                                    <input type="text" name="potong_sapi_harga" class="currency w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg" value="{{ isset($potongSapi) ? number_format($potongSapi, 0, ',', '.') : '1.800.000' }}">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Biaya Potong Kambing (Rp.)</label>
                                <div class="relative">
                                    <input type="text" name="potong_kambing_harga" class="currency w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg" value="{{ isset($potongKambing) ? number_format($potongKambing, 0, ',', '.') : '300.000' }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ==================== TAB: CATATAN PENTING ==================== -->
                <div id="tab-catatan" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">📋 Catatan Penting</h4>
                        <div id="important-notes-container" class="space-y-3">
                            @php
                                $notes = $importantNotes ?? [];
                                if (empty($notes)) {
                                    $notes = [
                                        'Pendaftaran paling lambat H-2 sebelum Idul Adha (8 Dzulhijjah)',
                                        'Penyerahan hewan sendiri: H-1 sebelum hari pemotongan',
                                        'Jika patungan 1 ekor sapi tidak mencapai 7 orang, akan dialihkan ke qurban kambing & membayar biaya potong dan distribusi Rp150.000',
                                        'Harga sudah termasuk biaya potong dan distribusi untuk paket resmi panitia',
                                    ];
                                }
                            @endphp
                            @foreach($notes as $note)
                            <div class="flex gap-2 items-center">
                                <span class="inline-flex items-center justify-center w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full text-sm">✓</span>
                                <input type="text" name="important_notes[]" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" value="{{ $note }}">
                                <button type="button" class="remove-note px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="add-note" class="mt-4 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Tambah Catatan
                        </button>
                    </div>
                </div>

                <!-- ==================== TAB: FAQ ==================== -->
                <div id="tab-faq" class="tab-pane hidden">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <h4 class="text-lg font-semibold text-emerald-800 mb-4 pb-2 border-b border-emerald-200">❓ FAQ (Pertanyaan yang Sering Diajukan)</h4>
                        <div id="faq-container" class="space-y-4">
                            @php
                                $faqs = $faqItems ?? [];
                                if (empty($faqs)) {
                                    $faqs = [
                                        ['question' => 'Bolehkah qurban untuk orang yang sudah meninggal?', 'answer' => 'Boleh, asalkan diniatkan untuk mereka yang telah wafat.'],
                                        ['question' => 'Bagaimana cara pembayaran qurban?', 'answer' => 'Transfer ke rekening BCA 1010010947479 a.n. JAZULI, lalu konfirmasi ke Bapak Jazuli.'],
                                        ['question' => 'Apakah bisa memilih lokasi distribusi?', 'answer' => 'Distribusi difokuskan ke Taman Cipulir Estate dan sekitarnya.'],
                                        ['question' => 'Apa yang terjadi jika patungan sapi tidak sampai 7 orang?', 'answer' => 'Akan dialihkan ke qurban kambing dengan biaya tambahan potong Rp150.000.'],
                                    ];
                                }
                            @endphp
                            @foreach($faqs as $faq)
                            <div class="faq-item border border-gray-200 rounded-lg p-4 bg-white">
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan</label>
                                    <input type="text" name="faq_questions[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" value="{{ $faq['question'] ?? '' }}">
                                </div>
                                <div class="mb-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Jawaban</label>
                                    <textarea name="faq_answers[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" rows="2">{{ $faq['answer'] ?? '' }}</textarea>
                                </div>
                                <button type="button" class="remove-faq px-3 py-1.5 text-sm bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition">
                                    Hapus FAQ
                                </button>
                            </div>
                            @endforeach
                        </div>
                        <button type="button" id="add-faq" class="mt-4 px-4 py-2 bg-emerald-100 text-emerald-700 rounded-lg hover:bg-emerald-200 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                            Tambah FAQ
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end mt-6 pt-4 border-t">
                    <button type="submit" class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-semibold rounded-xl transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Simpan Semua Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // ==================== TAB NAVIGATION ====================
    $('.tab-btn').on('click', function() {
        var tabId = $(this).data('tab');
        
        $('.tab-btn').removeClass('active bg-emerald-600 text-white');
        $(this).addClass('active bg-emerald-600 text-white');
        
        $('.tab-pane').addClass('hidden');
        $('#tab-' + tabId).removeClass('hidden');
    });
    
    $('.tab-btn.active').addClass('bg-emerald-600 text-white');
    
    // ==================== FORMAT CURRENCY ====================
    $('.currency').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value) {
            $(this).val(new Intl.NumberFormat('id-ID').format(value));
        }
    });
    
    // ==================== MANFAAT ====================
    $('#add-benefit').on('click', function() {
        const newItem = `
            <div class="flex gap-2 benefit-item">
                <input type="text" name="home_qurban_benefits[]" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" placeholder="Tulis manfaat...">
                <button type="button" class="remove-benefit px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        `;
        $('#benefits-container').append(newItem);
    });
    
    $(document).on('click', '.remove-benefit', function() {
        $(this).closest('.benefit-item').remove();
    });
    
    // ==================== CATATAN PENTING ====================
    $('#add-note').on('click', function() {
        const newItem = `
            <div class="flex gap-2 items-center">
                <span class="inline-flex items-center justify-center w-6 h-6 bg-emerald-100 text-emerald-700 rounded-full text-sm">✓</span>
                <input type="text" name="important_notes[]" class="flex-1 px-4 py-2 border border-gray-300 rounded-lg" placeholder="Tulis catatan penting...">
                <button type="button" class="remove-note px-3 py-2 bg-red-100 text-red-600 rounded-lg hover:bg-red-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                </button>
            </div>
        `;
        $('#important-notes-container').append(newItem);
    });
    
    $(document).on('click', '.remove-note', function() {
        $(this).closest('.flex').remove();
    });
    
    // ==================== FAQ ====================
    $('#add-faq').on('click', function() {
        const newItem = `
            <div class="faq-item border border-gray-200 rounded-lg p-4 bg-white">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pertanyaan</label>
                    <input type="text" name="faq_questions[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Masukkan pertanyaan...">
                </div>
                <div class="mb-3">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jawaban</label>
                    <textarea name="faq_answers[]" class="w-full px-4 py-2 border border-gray-300 rounded-lg" rows="2" placeholder="Masukkan jawaban..."></textarea>
                </div>
                <button type="button" class="remove-faq px-3 py-1.5 text-sm bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition">
                    Hapus FAQ
                </button>
            </div>
        `;
        $('#faq-container').append(newItem);
    });
    
    $(document).on('click', '.remove-faq', function() {
        $(this).closest('.faq-item').remove();
    });
    
    // ==================== IMAGE PREVIEW ====================
    $('#home_qurban_image').on('change', function() {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result);
                $('#image-preview-container').removeClass('hidden');
            }
            reader.readAsDataURL(file);
        }
    });
    
    $('#remove-image').on('click', function() {
        $('#home_qurban_image').val('');
        $('#image-preview').attr('src', '');
        $('#image-preview-container').addClass('hidden');
    });
    
    // ==================== SUBMIT FORM ====================
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();
        
        $('.currency').each(function() {
            let rawValue = $(this).val().replace(/\D/g, '');
            $(this).val(rawValue);
        });
        
        Swal.fire({
            title: 'Menyimpan...',
            text: 'Mohon tunggu',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });
        
        $.ajax({
            url: '{{ route("admin.qurban.setting.update") }}',
            method: 'POST',
            data: $(this).serialize(),
            success: function(res) {
                if (res.success) {
                    Swal.fire('Berhasil!', res.message, 'success');
                } else {
                    Swal.fire('Gagal!', res.message, 'error');
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                Swal.fire('Error!', message, 'error');
            }
        });
    });
    
    // ==================== RESET SETTINGS ====================
    $('#btnReset').on('click', function() {
        Swal.fire({
            title: 'Reset Pengaturan?',
            text: "Semua pengaturan akan kembali ke default. Anda yakin?",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Ya, Reset!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.qurban.setting.reset") }}',
                    method: 'POST',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire('Berhasil!', res.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('Gagal!', res.message, 'error');
                        }
                    }
                });
            }
        });
    });
});
</script>
@endpush