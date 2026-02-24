@extends('masjid.master-guest')

@section('title', 'Ramadhan Masjid Raudhotul Jannah TCE 1447H')

@section('meta_description', 'Ikuti kegiatan Ramadhan Masjid Raudhotul Jannah Taman Cipulir Estate bersama Majelis Ta’lim Raudhotul Jannah. Lihat perkembangan donasi, jadwal imam tarawih, dan penyaluran amanah jamaah setiap hari.')

@section('content')
<section class="min-h-screen bg-gray-50 py-12 px-2 sm:px-4 lg:px-6">
    <div class="max-w-6xl mx-auto">

        <!-- ===================================================== -->
        <!-- HERO + AJAKAN DONASI + QRIS + KONFIRMASI DONASI FINAL -->
        <!-- ===================================================== -->
        <div class="text-center mb-12 sm:mb-9">

            <!-- LOGO -->
            <div class="flex justify-center items-center gap-4 sm:gap-6 mb-6 sm:mb-8">
                <img
                    src="{{ asset('mrj-mtrj.png') }}"
                    alt="Logo MRJ & MTRJ"
                    class="h-24 sm:h-28 md:h-32 lg:h-36 object-contain drop-shadow-lg transition-transform duration-300 hover:scale-105"
                >
            </div>

            <!-- JUDUL -->
            <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl font-extrabold text-emerald-900 tracking-tight leading-tight px-2">
                Ramadhan 1447 H di <br>
                <span class="highlight-masjid">
                    Masjid Raudhotul Jannah
                </span>
            </h1>
            <!-- LOKASI (pengganti TCE) -->
            <p class="mt-2 text-sm sm:text-base text-emerald-700 font-medium">
                Taman Cipulir Estate
            </p>

            <!-- SUBTITLE -->
            <p class="mt-4 text-base sm:text-lg md:text-xl font-medium text-emerald-800 max-w-2xl mx-auto leading-relaxed px-4">
                Ruang ibadah, doa, dan kebersamaan untuk kita semua.
            </p>

            <!-- DESKRIPSI -->
            <p class="mt-4 text-sm sm:text-base md:text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed px-4">
                Halaman ini memuat kegiatan masjid serta laporan penerimaan dan penyaluran amanah jama'ah yang diperbarui setiap hari selama Ramadhan.
            </p>

            <!-- ================= AJAKAN DONASI ================= -->
            <div class="max-w-5xl mx-auto mt-12 sm:mt-16">

                <div class="bg-white border border-gray-200 rounded-3xl shadow-2xl p-8 sm:p-10 overflow-hidden relative transition-all duration-300 hover:shadow-3xl">

                    <div class="absolute inset-0 bg-gradient-to-br from-emerald-50/50 via-white to-emerald-50/50 opacity-80"></div>

                    <!-- NARASI UPGRADE (FINAL) -->
                    <div class="text-center mb-10 sm:mb-12 relative z-10">

                        <!-- Icon Bulan Sabit (focal point) -->
                        <div class="inline-flex items-center justify-center w-20 sm:w-24 h-20 sm:h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-emerald-100/80 to-teal-100/80 shadow-xl backdrop-blur-sm border border-emerald-200/50">
                            <span class="text-5xl sm:text-6xl drop-shadow-md">🌙</span>
                        </div>

                        <!-- Judul -->
                        <h2 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-900 tracking-tight leading-tight">
                            Semoga Ramadhan Ini
                            <span class="block mt-1 sm:mt-2 text-teal-700 relative inline-block">
                                Menjadi Keberkahan untuk Kita Semua
                                <span class="absolute -bottom-1 left-0 right-0 h-1 bg-gradient-to-r from-teal-400/40 to-emerald-400/40 rounded-full"></span>
                            </span>
                        </h2>

                        <!-- Narasi utama -->
                        <p class="mt-6 text-base sm:text-lg text-gray-700 max-w-xl mx-auto leading-relaxed">
                            Setiap sore menjelang maghrib, masjid mulai hidup.
                            Ada yang duduk menunggu adzan, ada yang membantu menyiapkan hidangan.
                            Lalu berbuka bersama dan menutup hari dengan shalat berjamaah.
                        </p>

                        <!-- Kalimat emosional (inti) -->
                        <p class="mt-6 text-base sm:text-lg text-emerald-800 italic max-w-lg mx-auto leading-relaxed">
                            Tidak semua dari kita bisa selalu hadir.
                            Namun semoga Allah tetap memberi kita bagian pahala
                            dari setiap ayat yang dibaca, setiap doa yang dipanjatkan,
                            dan setiap hidangan yang dinikmati oleh jamaah.
                        </p>

                    </div>

                    <!-- ================= QRIS ================= -->
                    <div class="mb-8 relative z-10">
                        <div class="bg-white border border-amber-100 rounded-3xl shadow-md p-6 sm:p-8 text-center transition-shadow duration-300 hover:shadow-xl">

                            <h3 class="text-xl sm:text-2xl font-bold text-amber-900 mb-3">
                                Saluran Amal Ramadhan
                            </h3>

                            <!-- QRIS -->
                            <div class="flex justify-center">
                                <div class="bg-white p-3 rounded-2xl border border-gray-200 shadow-sm transition-transform duration-300 hover:scale-105">
                                    <img src="{{ asset('storage/'.profil('qris')) }}"
                                         alt="QRIS Masjid"
                                         class="w-60 sm:w-72 rounded-xl object-contain">
                                </div>
                            </div>

                            <!-- BUTTON -->
                            <div class="mt-6 flex flex-col sm:flex-row justify-center gap-3">

                                <a href="https://s.id/QRIS-MRJ" target="_blank"
                                   class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-full shadow-md transition duration-300 text-sm">
                                   Lihat QRIS
                                </a>

                                <a href="https://wa.me/628121073583?text=Assalamu'alaikum%20Panitia%20Masjid.%20Saya%20ingin%20konfirmasi%20sedekah%20Ramadhan.%20Mohon%20dicatat%20dan%20disalurkan.%20Terima%20kasih."
                                   target="_blank"
                                   class="bg-white border border-amber-300 hover:bg-amber-50 text-amber-700 font-semibold px-6 py-2.5 rounded-full transition duration-300 text-sm">
                                   💬 Konfirmasi Amanah
                                </a>

                            </div>

                            <!-- NOTE -->
                            <p class="text-xs text-gray-500 mt-4">
                                Konfirmasi hanya untuk pencatatan amanah agar tidak ada yang terlewat
                            </p>

                        </div>
                    </div>
                    <!-- ================= REKENING ================= -->
                    <div class="grid md:grid-cols-2 gap-8 relative z-10">

                        <!-- ================= PROGRAM RAMADHAN ================= -->
                        <div class="bg-white rounded-3xl border border-emerald-100 shadow-md p-8 flex flex-col justify-between relative overflow-hidden transition-shadow duration-300 hover:shadow-xl">

                            <div class="absolute -top-16 -right-16 w-48 h-48 bg-emerald-50 rounded-full blur-3xl opacity-50"></div>

                            <div class="relative">
                                <h3 class="text-2xl sm:text-2xl font-bold text-emerald-900 mb-3">
                                    🌙 Program Ramadhan
                                </h3>

                                <p class="text-sm text-gray-600 mb-6">
                                        Buka puasa jamaah • Santunan yatim & dhuafa • Paket sembako • Kegiatan anak
                                </p>

                                <!-- REKENING BOX -->
                                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6 text-center">

                                        <!-- BANK -->
                                        <div class="text-xs uppercase tracking-widest text-emerald-700 font-semibold mb-2">
                                            Bank Mandiri
                                        </div>

                                        <!-- NO REK -->
                                        <div id="rek-ramadhan"
                                             data-copy="1010011737242"
                                             class="rek-number font-mono font-semibold text-emerald-800 mb-2 select-all break-all">
                                            10 100 11737 242
                                        </div>

                                        <div class="text-sm text-gray-700">
                                            a.n. <span class="font-semibold">Chaeriyah Alpi</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">
                                            (Rekening amanah panitia kegiatan Ramadhan)
                                        </p>

                                    </div>

                                <p class="mt-5 text-sm text-gray-500 text-center italic">
                                    Semoga setiap kebaikan yang dititipkan di sini menjadi cahaya bagi pemberinya.
                                </p>
                            </div>

                            <div class="mt-8 grid sm:grid-cols-2 gap-4">
                                <button onclick="copyRek('rek-ramadhan')" 
                                    class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-full transition duration-300 transform hover:-translate-y-1 shadow-md flex items-center justify-center gap-2">
                                    📋 Copy Rekening
                                </button>

                                <a href="https://wa.me/6281288975634?text=Assalamu%27alaikum%20Ibu%20Alfi,%20saya%20sudah%20transfer%20donasi%20Ramadhan.%20Berikut%20bukti%20transfernya."
                                   target="_blank"
                                   class="w-full text-center bg-white border border-emerald-300 hover:bg-emerald-50 text-emerald-700 font-semibold py-3 rounded-full transition duration-300 transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                    💬 Konfirmasi
                                </a>
                            </div>

                        </div>


                        <!-- ================= INFAQ MASJID ================= -->
                        <div class="bg-white rounded-3xl border border-teal-100 shadow-md p-8 flex flex-col justify-between relative overflow-hidden transition-shadow duration-300 hover:shadow-xl">

                            <div class="absolute -top-16 -right-16 w-48 h-48 bg-teal-50 rounded-full blur-3xl opacity-50"></div>

                            <div class="relative">
                                <h3 class="text-2xl sm:text-2xl font-bold text-emerald-900 mb-3">
                                    💚 Infaq Operasional Masjid
                                </h3>

                                <p class="text-sm text-gray-600 mb-6">
                                    Listrik • Air • Kebersihan • Dakwah & Kegiatan Sepanjang Tahun
                                </p>

                                <!-- REKENING BOX -->
                                <div class="bg-teal-50 border border-teal-100 rounded-2xl p-6 text-center">

                                    <!-- BANK -->
                                    <div class="text-xs uppercase tracking-widest text-teal-700 font-semibold mb-2">
                                        {{ profil('bank_name') }} • Kode {{ profil('bank_code') }}
                                    </div>

                                    <!-- NO REK -->
                                    <div id="rek-masjid"
                                         data-copy="7025516952"
                                         class="rek-number font-mono font-semibold text-teal-800 mb-2 select-all break-all">
                                        {{ trim(chunk_split(preg_replace('/\D/','', profil('rekening')), 4, ' ')) }}
                                    </div>

                                    <div class="text-sm text-gray-700">
                                        a.n. <span class="font-semibold">{{ profil('atas_nama') }}</span>
                                    </div>

                                </div>

                                <p class="mt-5 text-sm text-gray-500 text-center italic">
                                    Setiap lampu masjid yang menyala ada pahala bagi yang ikut menyalakannya.
                                </p>
                            </div>

                            <div class="mt-8 grid sm:grid-cols-2 gap-4">
                                <button onclick="copyRek('rek-masjid')" 
                                    class="w-full bg-teal-600 hover:bg-teal-700 text-white font-semibold py-3 rounded-full transition duration-300 transform hover:-translate-y-1 shadow-md flex items-center justify-center gap-2">
                                    📋 Copy Rekening
                                </button>

                                <a href="https://wa.me/628121073583?text=Assalamu%27alaikum%20Pak%20Ari,%20saya%20sudah%20transfer%20infaq%20masjid.%20Berikut%20bukti%20transfernya."
                                   target="_blank"
                                   class="w-full text-center bg-white border border-teal-300 hover:bg-teal-50 text-teal-700 font-semibold py-3 rounded-full transition duration-300 transform hover:-translate-y-1 flex items-center justify-center gap-2">
                                    💬 Konfirmasi
                                </a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- ==================== KEHIDUPAN RAMADHAN DI MASJID ==================== -->
    <section class="py-16 sm:py-20 bg-gradient-to-b from-emerald-50/80 via-white to-teal-50/50 relative overflow-hidden">
        <!-- Background subtle -->
        <div class="absolute inset-0 opacity-5 pointer-events-none">
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,_rgba(16,185,129,0.08)_0%,_transparent_50%)]"></div>
        </div>

        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <!-- Header -->
            <div class="text-center mb-12 sm:mb-16 relative">
                <div class="inline-flex items-center justify-center w-16 h-16 mx-auto mb-6 rounded-full bg-gradient-to-br from-emerald-100 to-teal-100 shadow-lg">
                    <span class="text-3xl">🌙</span>
                </div>
                <h2 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-emerald-900 tracking-tight">
                    Kehidupan Ramadhan
                    <span class="block text-teal-700 mt-2">di Masjid Raudhotul Jannah</span>
                </h2>
                <p class="mt-4 text-base sm:text-lg text-gray-700 max-w-3xl mx-auto leading-relaxed">
                    Setiap Ramadhan, suasana masjid terasa lebih hidup.
                    Langkah kaki jamaah datang lebih sering, Al-Qur’an terdengar lebih lama,
                    dan banyak hati menemukan kembali ketenangan.
                </p>
            </div>

            <!-- Grid utama – 2 kolom rata tinggi -->
            <div class="grid md:grid-cols-2 gap-8 lg:gap-12 auto-rows-fr">
                <!-- Card Ibadah -->
                <div class="bg-white/90 backdrop-blur-sm rounded-3xl border border-emerald-100/60 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 overflow-hidden group flex flex-col">
                    <div class="h-2 bg-gradient-to-r from-emerald-500 to-teal-500"></div>
                    <div class="p-6 sm:p-8 flex flex-col flex-grow">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-emerald-50 flex items-center justify-center text-3xl shadow-inner group-hover:bg-emerald-100 transition-colors">
                                🕌
                            </div>
                            <h3 class="text-2xl font-bold text-emerald-900">Ibadah Harian</h3>
                        </div>
                        <ul class="space-y-4 text-gray-700 flex-grow">
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-emerald-600 mt-1">→</span>
                                <span>Shalat Tarawih berjamaah setiap malam</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-emerald-600 mt-1">→</span>
                                <span>Tadarus Al-Qur'an</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-emerald-600 mt-1">→</span>
                                <span>I'tikaf di sepuluh malam terakhir Ramadhan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-emerald-600 mt-1">→</span>
                                <span>Khatmil Qur'an</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Card Pelayanan -->
                <div class="bg-white/90 backdrop-blur-sm rounded-3xl border border-teal-100/60 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 overflow-hidden group flex flex-col">
                    <div class="h-2 bg-gradient-to-r from-teal-500 to-cyan-500"></div>
                    <div class="p-6 sm:p-8 flex flex-col flex-grow">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-teal-50 flex items-center justify-center text-3xl shadow-inner group-hover:bg-teal-100 transition-colors">
                                🍽️
                            </div>
                            <h3 class="text-2xl font-bold text-teal-900">Pelayanan Jamaah</h3>
                        </div>
                        <ul class="space-y-4 text-gray-700 flex-grow">
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-teal-600 mt-1">→</span>
                                <span>Hidangan berbuka puasa untuk jamaah</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-teal-600 mt-1">→</span>
                                <span>Sahur untuk marbot dan petugas keamanan</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-teal-600 mt-1">→</span>
                                <span>Masjid dijaga tetap bersih dan nyaman</span>
                            </li>
                            <!-- Tambah dummy kalau perlu biar rata, atau biarkan kosong -->
                            <li class="flex items-start gap-3 opacity-0 pointer-events-none">
                                <span class="text-xl mt-1">→</span>
                                <span>&nbsp;</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Card Sosial -->
                <div class="bg-white/90 backdrop-blur-sm rounded-3xl border border-amber-100/60 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 overflow-hidden group flex flex-col">
                    <div class="h-2 bg-gradient-to-r from-amber-500 to-orange-500"></div>
                    <div class="p-6 sm:p-8 flex flex-col flex-grow">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-amber-50 flex items-center justify-center text-3xl shadow-inner group-hover:bg-amber-100 transition-colors">
                                💝
                            </div>
                            <h3 class="text-2xl font-bold text-amber-900">Program Sosial</h3>
                        </div>
                        <ul class="space-y-4 text-gray-700 flex-grow">
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-amber-600 mt-1">→</span>
                                <span>Santunan anak yatim yang dhuafa dan anak dhuafa</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-amber-600 mt-1">→</span>
                                <span>Pembagian paket sembako</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-amber-600 mt-1">→</span>
                                <span>Penyaluran zakat, infaq dan fidyah jamaah</span>
                            </li>
                            <!-- Dummy untuk rata -->
                            <li class="flex items-start gap-3 opacity-0 pointer-events-none">
                                <span class="text-xl mt-1">→</span>
                                <span>&nbsp;</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Card Syiar -->
                <div class="bg-white/90 backdrop-blur-sm rounded-3xl border border-purple-100/60 shadow-xl hover:shadow-2xl transition-all duration-500 hover:-translate-y-2 overflow-hidden group flex flex-col">
                    <div class="h-2 bg-gradient-to-r from-purple-500 to-indigo-500"></div>
                    <div class="p-6 sm:p-8 flex flex-col flex-grow">
                        <div class="flex items-center gap-4 mb-6">
                            <div class="w-14 h-14 rounded-2xl bg-purple-50 flex items-center justify-center text-3xl shadow-inner group-hover:bg-purple-100 transition-colors">
                                ✨
                            </div>
                            <h3 class="text-2xl font-bold text-purple-900">Syiar & Kebersamaan</h3>
                        </div>
                        <ul class="space-y-4 text-gray-700 flex-grow">
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-purple-600 mt-1">→</span>
                                <span>Peringatan Nuzulul Qur'an</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-purple-600 mt-1">→</span>
                                <span>Gebyar Ramadhan dan lomba anak sholeh-sholehah</span>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="text-xl text-purple-600 mt-1">→</span>
                                <span>Shalat Idul Fitri berjamaah</span>
                            </li>
                            <!-- Dummy untuk rata -->
                            <li class="flex items-start gap-3 opacity-0 pointer-events-none">
                                <span class="text-xl mt-1">→</span>
                                <span>&nbsp;</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Penutup emosional -->
            <div class="text-center mt-16 max-w-3xl mx-auto">
                <p class="text-lg sm:text-xl italic text-emerald-800 font-medium leading-relaxed">
                    Masjid hidup dari doa yang dipanjatkan, dari langkah kaki yang datang, dan dari kebaikan yang dititipkan oleh jamaah.
                </p>
                <p class="mt-4 text-sm text-gray-600">
                    Semoga kita semua mendapat bagian keberkahan Ramadhan ini.
                </p>
            </div>
        </div>
    </section>

    <div class="max-w-[1400px] 2xl:max-w-[1550px] mx-auto px-3 sm:px-6">
        <h2 class="text-2xl sm:text-3xl font-bold text-center text-emerald-900 mb-6">
            🌟 Progress Donasi Ramadhan
        </h2>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-5 gap-6 mb-16 sm:mb-20 bg-emerald-50/50 p-6 rounded-3xl"
            id="totals-container">

            <!-- Infaq -->
            <div class="card bg-white border border-emerald-100 shadow-lg rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-2">
                <div class="card-body flex flex-col items-center text-center py-8 px-5 h-full">
                    <div class="text-4xl sm:text-5xl mb-3 transition-transform duration-300 hover:rotate-12">💰</div>
                    <h3 class="text-lg sm:text-base md:text-lg font-semibold text-emerald-900">Infaq Ramadhan</h3>
                    <p class="total-amount text-emerald-800 mt-3 font-extrabold tracking-tight text-emerald-700" id="total-infaq">Rp 0</p>
                    <div id="update-infaq" class="card-meta text-emerald-700 mt-3"></div>
                    <p class="program-desc mt-4">
                        Tercatat dan terlapor setiap hari selama Ramadhan.
                    </p>
                </div>
            </div>
            <!-- Iftor -->
            <div class="card bg-white border border-teal-100 shadow-lg rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-2">
                <div class="card-body flex flex-col items-center text-center py-8 px-5 h-full">
                    <div class="text-4xl sm:text-5xl mb-3 transition-transform duration-300 hover:rotate-12">🍲</div>
                    <h3 class="text-lg sm:text-base md:text-lg font-semibold text-teal-900">Saldo Iftor</h3>
                    <p class="total-amount text-teal-800 mt-3 font-extrabold tracking-tight text-emerald-700" id="total-ifthor">Rp 0</p>
                    <div id="update-iftor" class="card-meta text-teal-700 mt-3"></div>
                    <p class="program-desc mt-4">
                        Digunakan untuk penyediaan buka puasa jama'ah setiap hari.
                    </p>
                </div>
            </div>
            <!-- Santunan Yatim & Dhuafa -->
            <div class="card bg-white border border-amber-100 shadow-lg rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-2">
                <div class="card-body flex flex-col items-center text-center py-8 px-5 h-full">
                    <div class="text-4xl sm:text-5xl mb-3 transition-transform duration-300 hover:rotate-12">👶</div>
                    <h3 class="text-lg sm:text-base md:text-lg font-semibold text-amber-900">Santunan Yatim & Dhuafa</h3>
                    <div class="text-xs text-amber-700 mt-1">
                        Target 200 penerima
                    </div>
                    <p class="total-amount text-amber-800 mt-3 font-extrabold tracking-tight text-emerald-700" id="total-santunan">Rp 0</p>
                    <div class="progress-wrapper mt-4" id="progress-santunan">
                        <svg class="progress-ring" width="80" height="80">
                            <circle
                                class="progress-ring-bg"
                                stroke="#fde68a"
                                stroke-width="8"
                                fill="transparent"
                                r="32"
                                cx="40"
                                cy="40"
                            />
                            <circle
                                class="progress-ring-fill"
                                stroke="#d97706"
                                stroke-width="8"
                                fill="transparent"
                                r="32"
                                cx="40"
                                cy="40"
                            />
                        </svg>
                        <span class="progress-label">0 / 200</span>
                    </div>
                    <div id="need-santunan" class="progress-need text-amber-700 mt-2"></div>
                    <p class="program-desc mt-4">
                        Santunan disalurkan kepada yatim dan dhuafa sekitar masjid.
                    </p>
                </div>
            </div>
            <!-- Paket Sembako -->
            <div class="card bg-white border border-cyan-100 shadow-lg rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-2">
                <div class="card-body flex flex-col items-center text-center py-8 px-5 h-full">
                    <div class="text-4xl sm:text-5xl mb-3 transition-transform duration-300 hover:rotate-12">🛒</div>
                    <h3 class="text-lg sm:text-base md:text-lg font-semibold text-cyan-900">Paket Sembako</h3>
                    <div class="text-xs text-cyan-700 mt-1">
                        Target 75 paket
                    </div>
                    <p class="total-amount text-cyan-800 mt-3 font-extrabold tracking-tight text-emerald-700" id="total-sembako">Rp 0</p>
                    <div class="progress-wrapper mt-4" id="progress-sembako">
                        <svg class="progress-ring" width="80" height="80">
                            <circle
                                class="progress-ring-bg"
                                stroke="#bae6fd"
                                stroke-width="8"
                                fill="transparent"
                                r="32"
                                cx="40"
                                cy="40"
                            />
                            <circle
                                class="progress-ring-fill"
                                stroke="#0891b2"
                                stroke-width="8"
                                fill="transparent"
                                r="32"
                                cx="40"
                                cy="40"
                            />
                        </svg>
                        <span class="progress-label">0 / 75</span>
                    </div>
                    <div id="need-sembako" class="progress-need text-cyan-700 mt-2"></div>
                    <p class="program-desc mt-4">
                        Paket sembako dibagikan kepada warga yang membutuhkan.
                    </p>
                </div>
            </div>
            <!-- Gebyar -->
            <div class="card bg-white border border-purple-100 shadow-lg rounded-3xl overflow-hidden transition-all duration-300 hover:shadow-xl hover:-translate-y-2">
                <div class="card-body flex flex-col items-center text-center py-8 px-5 h-full">
                    <div class="text-4xl sm:text-5xl mb-3 transition-transform duration-300 hover:rotate-12">🎉</div>
                    <h3 class="text-lg sm:text-base md:text-lg font-semibold text-purple-900">Gebyar Ramadhan</h3>
                    <p class="total-amount text-purple-800 mt-3 font-extrabold tracking-tight text-emerald-700" id="total-gebyar">Rp 0</p>
                    <div id="update-gebyar" class="card-meta text-purple-700 mt-3"></div>
                    <p class="program-desc mt-4">
                        Kegiatan anak dan remaja untuk meramaikan masjid di bulan Ramadhan.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto">
        <!-- Jadwal Imam (Dinamis) -->
        <div class="mt-16 max-w-5xl mx-auto">
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-emerald-900 mb-6">🕌 Jadwal Imam Tarawih</h2>
            <div class="bg-white rounded-3xl shadow overflow-hidden">  <!-- Hilangkan overflow-x-auto di sini, pindah ke inner div -->
                <div class="p-2 sm:p-4">
                     <div class="overflow-x-auto overflow-y-auto max-h-[400px]">
                        <table class="w-full text-sm text-gray-900 min-w-max table-fixed">  <!-- Satu table saja -->
                            <thead class="bg-emerald-100 text-emerald-900">
                                <tr>
                                    <th class="px-4 py-3 text-left sm:px-6 sm:py-4 w-[10%] sticky top-0 z-10 bg-emerald-100">Malam</th>  <!-- Sticky per th -->
                                    <th class="px-4 py-3 text-left sm:px-6 sm:py-4 w-[20%] sticky top-0 z-10 bg-emerald-100">Tanggal</th>
                                    <th class="px-4 py-3 text-left sm:px-6 sm:py-4 w-[30%] sticky top-0 z-10 bg-emerald-100">Imam</th>
                                    <th class="px-4 py-3 text-left sm:px-6 sm:py-4 w-[40%] sticky top-0 z-10 bg-emerald-100">Tema Tausiyah</th>
                                </tr>
                            </thead>
                            <tbody id="jadwal-imam-body" class="text-gray-800">
                                <tr><td colspan="4" class="text-center py-6">Memuat...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <p class="text-center text-xs sm:text-sm text-gray-500 mt-4 italic leading-relaxed">
                Jadwal lengkap dapat dilihat dengan menggeser tabel
            </p>
        </div>

        <!-- Donatur Hari Ini (Card Baru) -->
        <div class="mt-16 max-w-5xl mx-auto bg-white rounded-3xl shadow-lg p-6 sm:p-8">
            <h2 class="text-2xl sm:text-3xl font-bold text-center text-emerald-900 mb-6">🙏 Donatur Hari Ini</h2>
            <p class="text-center text-gray-600 mb-6">Jazakumullahu khairan kepada para jamaah yang telah menitipkan amanahnya.</p>
            <div id="donatur-list" class="space-y-4">
                <!-- Placeholder -->
                <div class="text-center text-gray-500">Memuat daftar donatur...</div>
            </div>
        </div>

        <!-- ==================== GALERI MOMEN ==================== -->
        <div class="mt-16 max-w-5xl mx-auto bg-white rounded-3xl shadow-lg p-6 sm:p-8">

            <!-- HEADER GALERI (DITENGAHKAN & LEBIH NATURAL) -->
            <div class="text-center mb-6">
                <h2 class="text-xl sm:text-2xl md:text-3xl font-bold text-emerald-900">
                    📸 Momen Ramadhan di Masjid
                </h2>

                <p class="mt-2 text-sm sm:text-base text-gray-600 max-w-2xl mx-auto leading-relaxed">
                    Beberapa suasana buka puasa dan shalat tarawih bersama jamaah.
                </p>
            </div>

            <!-- Grid galeri (akan diisi via JS) -->
            <div class="flex justify-center">
                <div id="galeri-list"
                     class="galeri-grid">
                    <div class="col-span-full text-center py-8 text-gray-500 animate-pulse">
                        Memuat galeri suasana masjid...
                    </div>
                </div>
            </div>
        </div>

        <!-- ====================== MODAL GALERI ====================== -->
        <dialog id="galeriModal" class="modal modal-bottom sm:modal-middle">
            <div class="modal-box max-w-4xl p-0 rounded-2xl overflow-hidden bg-white shadow-2xl">

                <!-- ================= IMAGE VIEWER ================= -->
                <div class="galeri-viewer-wrapper">

                    <!-- FRAME (TIDAK IKUT GAMBAR) -->
                    <div class="galeri-viewer">
                        <img id="galeriModalImg" alt="Foto galeri">
                    </div>

                    <!-- NAV PREV -->
                    <button id="galeriPrev" class="galeri-nav galeri-prev" aria-label="Sebelumnya">
                        ❮
                    </button>

                    <!-- NAV NEXT -->
                    <button id="galeriNext" class="galeri-nav galeri-next" aria-label="Berikutnya">
                        ❯
                    </button>

                    <!-- CLOSE -->
                    <button id="closeGaleriModalBtn" class="galeri-close">
                        ✕
                    </button>

                </div>

                <!-- ================= INFO ================= -->
                <div class="p-4 sm:p-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">

                        <div>
                            <h3 id="galeriModalTitle" class="text-lg sm:text-xl font-semibold text-emerald-900"></h3>
                            <p id="galeriCounter" class="text-sm text-gray-500 mt-1">0 / 0</p>
                        </div>

                        <div class="text-xs text-gray-500 italic">
                            Swipe kiri/kanan atau gunakan tombol
                        </div>

                    </div>

                    <!-- THUMBNAILS -->
                    <div id="galeriThumbs" class="flex gap-2 overflow-x-auto pb-2 snap-x snap-mandatory">
                    </div>
                </div>

            </div>

            <form method="dialog" class="modal-backdrop">
                <button>Tutup</button>
            </form>
        </dialog>

        <!-- Trust Statement -->
        <h2 class="text-2xl mt-16 font-bold text-center text-emerald-900 mb-4">
            📌 Transparansi Kami
        </h2>
        <div class="text-center mb-10 text-gray-600 text-sm max-w-2xl mx-auto px-4">
            Seluruh penerimaan dan penyaluran dicatat oleh panitia dan diperbarui setiap malam selama Ramadhan.
        </div>
        <!-- Laporan Detail -->
        <h2 class="text-2xl sm:text-3xl font-bold text-center text-emerald-900 mb-6 mt-16">
            📊 Laporan Detail Harian
        </h2>
        <div class="space-y-6" id="laporan-list"></div>
        <!-- Loading -->
        <div id="loading-state" class="text-center py-20">
            <span class="loading loading-spinner loading-lg text-emerald-600"></span>
            <p class="mt-4 text-gray-600 text-base">Memuat data...</p>
        </div>
        <!-- Empty -->
        <div id="empty-state" class="hidden text-center py-20 text-gray-600">
            <p class="text-lg font-medium">Belum ada laporan malam ini</p>
            <p class="mt-2 text-sm">Laporan akan ditampilkan setelah kegiatan malam ini selesai.</p>
        </div>
        <!-- Footer -->
        <footer class="text-center mt-16 pb-8 text-gray-500 text-sm">
            Setiap amanah jamaah dicatat dan dilaporkan setiap malam oleh panitia.
            Bila membutuhkan rincian atau konfirmasi, silakan hubungi nomor konfirmasi donasi yang tertera di atas.
        </footer>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script id="countup">
    function animateCurrency(el, finalValue){
        let start = 0;
        const duration = 900;
        const startTime = performance.now();

        function update(now){
            const progress = Math.min((now - startTime)/duration, 1);
            const value = Math.floor(progress * finalValue);

            el.textContent = formatCurrency(value);

            if(progress < 1){
                requestAnimationFrame(update);
            }
        }

        requestAnimationFrame(update);
    }
</script>
<!-- 
<script id="nightmode">
    const hour = new Date().getHours();
    if(hour >= 18 || hour <= 4){
        document.body.classList.add('ramadhan-night');
    }
</script> -->
<script>
    function copyRek(elementId) {

        const el = document.getElementById(elementId);

        // Ambil angka asli tanpa spasi
        const text = el.dataset.copy;

        navigator.clipboard.writeText(text).then(() => {

            const notif = document.createElement("div");
            notif.innerText = "Nomor rekening siap ditempel 👍";
            notif.className =
                "fixed bottom-6 left-1/2 -translate-x-1/2 bg-emerald-600 text-white px-5 py-3 rounded-xl shadow-lg z-50 text-sm transition-opacity duration-300";

            document.body.appendChild(notif);

            setTimeout(() => notif.remove(), 2000);

        }).catch(() => {
            alert("Gagal menyalin rekening");
        });
    }

    // Helper: Parse string ke number, handle berbagai format separator
    function parseToNumber(str_val) {
        str_val = str_val.toString().trim();
        str_val = str_val.replace(/^Rp\s*/i, '').trim(); // Hapus "Rp" jika ada
        str_val = str_val.replace(/\s/g, ''); // Hapus spasi
        if (!str_val) return 0;

        let dot_pos = str_val.lastIndexOf('.');
        let comma_pos = str_val.lastIndexOf(',');
        let decimal_sep = null;
        let thousand_sep = null;
        let decimal_digits = 0;

        if (dot_pos > comma_pos) {
            // Last separator is .
            decimal_sep = '.';
            thousand_sep = ',';
            decimal_digits = str_val.length - dot_pos - 1;
        } else if (comma_pos > dot_pos) {
            // Last separator is ,
            decimal_sep = ',';
            thousand_sep = '.';
            decimal_digits = str_val.length - comma_pos - 1;
        }

        // Jika digit setelah 'decimal' >2, likely bukan desimal, treat as thousand sep
        if (decimal_digits > 2) {
            decimal_sep = null;
            thousand_sep = dot_pos > -1 ? '.' : ',';
        }

        // Hapus thousand separator
        if (thousand_sep) {
            str_val = str_val.replace(new RegExp('\\' + thousand_sep, 'g'), '');
        }

        // Ganti decimal sep jadi . (standar JS)
        if (decimal_sep) {
            str_val = str_val.replace(decimal_sep, '.');
        }

        let num = parseFloat(str_val);
        return isNaN(num) ? 0 : num;
    }

    // Format Rupiah - sekarang pakai parser di atas
    function formatCurrency(value) {
        if (value === null || value === undefined) {
            value = 0;
        }
        const num = parseToNumber(value);
        const fixed = Math.round(num);
        return 'Rp ' + new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0,
            minimumFractionDigits: 0
        }).format(fixed);
    }

    function updateTotals(totals) {
        if (!totals) return;

        // ===== FORMAT NOMINAL =====
        // document.getElementById('total-infaq').textContent   = formatCurrency(totals.infaq_ramadan);
        // document.getElementById('total-ifthor').textContent  = formatCurrency(totals.ifthor);
        // document.getElementById('total-santunan').textContent= formatCurrency(totals.santunan_yatim);
        // document.getElementById('total-sembako').textContent = formatCurrency(totals.paket_sembako);
        // document.getElementById('total-gebyar').textContent  = formatCurrency(totals.gebyar);

        animateCurrency(document.getElementById('total-infaq'), totals.infaq_ramadan);
        animateCurrency(document.getElementById('total-ifthor'), totals.ifthor);
        animateCurrency(document.getElementById('total-santunan'), totals.santunan_yatim);
        animateCurrency(document.getElementById('total-sembako'), totals.paket_sembako);
        animateCurrency(document.getElementById('total-gebyar'), totals.gebyar);

        // ===== KONVERSI KE JUMLAH PENERIMA =====
        const biayaPerAnak  = 70000000 / 200;   // 350rb
        const biayaPerPaket = 15575000 / 75;    // ±207rb

        const anakTerpenuhi  = Math.floor(totals.santunan_yatim / biayaPerAnak);
        const paketTerpenuhi = Math.floor(totals.paket_sembako / biayaPerPaket);

        // ===== UPDATE PROGRESS RING =====
        setProgress('progress-santunan', anakTerpenuhi, 200);
        setProgress('progress-sembako', paketTerpenuhi, 75);
        // ===== HITUNG SISA KEBUTUHAN =====
        const sisaAnak = Math.max(200 - anakTerpenuhi, 0);
        const sisaPaket = Math.max(75 - paketTerpenuhi, 0);

        const needSantunan = document.getElementById('need-santunan');
        const needSembako = document.getElementById('need-sembako');

        if(needSantunan){
            needSantunan.textContent =
                sisaAnak > 0
                ? `Masih membutuhkan ${sisaAnak} penerima lagi`
                : `Alhamdulillah target santunan telah terpenuhi`;
        }

        if(needSembako){
            needSembako.textContent =
                sisaPaket > 0
                ? `Masih ${sisaPaket} paket sembako diperlukan`
                : `Alhamdulillah paket sembako sudah terpenuhi`;
        }
        const now = new Date();

        const jam = now.toLocaleTimeString('id-ID', {
            hour: '2-digit',
            minute: '2-digit'
        });

        const hari = now.toLocaleDateString('id-ID', {
            weekday: 'long'
        });

        const teksUpdate = `Diperbarui ${hari}, ${jam}`;

        document.getElementById('update-infaq').textContent  = teksUpdate;
        document.getElementById('update-iftor').textContent  = teksUpdate;
        document.getElementById('update-gebyar').textContent = teksUpdate;
    }

    function setProgress(wrapperId, value, max){
        const wrapper = document.getElementById(wrapperId);
        if(!wrapper) return;

        const circle = wrapper.querySelector('.progress-ring-fill');
        const label = wrapper.querySelector('.progress-label');

        const radius = 32;
        const circumference = 2 * Math.PI * radius;

        const percent = Math.min(value / max, 1);
        const offset = circumference - percent * circumference;

        circle.style.strokeDashoffset = circumference; // mulai kosong dulu

        setTimeout(() => {
            circle.style.strokeDashoffset = offset;
        }, 250);

        label.textContent = `${value} / ${max}`;
    }

    // Helper: Format tanggal jadi "d M Y" (misal "18 Feb 2026")
    function formatTanggal(dateStr) {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('id-ID', options).replace(/ /g, ' ');
    }

    // Helper: Hitung tanggal kemarin dari tanggal malam ini
    function getTanggalKemarin(tanggalStr) {
        if (!tanggalStr) return '-';
        const date = new Date(tanggalStr);
        date.setDate(date.getDate() - 1);
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        return date.toLocaleDateString('id-ID', options).replace(/ /g, ' ');
    }

    function renderLaporanItem(item) {
        const malamKe   = item.malam_ke;
        const tanggal   = item.tanggal; // misal "19 Feb 2026"

        // Hitung tanggal kemarin
        const tglKemarin = getTanggalKemarin(tanggal);

        // Nilai utama
        const infaqSaldo     = formatCurrency(item.saldo_infaq_ramadan ?? 0);
        const ifthorSaldo    = formatCurrency(item.saldo_ifthor ?? 0);
        const santunan       = formatCurrency(item.santunan_yatim ?? 0);
        const sembako        = formatCurrency(item.paket_sembako ?? 0);
        const gebyar         = formatCurrency(item.gebyar ?? 0);

        // Breakdown Infaq lengkap dengan tanggal
        const infaqSaldoKemarin = formatCurrency(item.infaq_ramadan_saldo_kemarin ?? 0);
        const infaqPenerimaan   = formatCurrency(item.infaq_ramadan_penerimaan_tromol ?? 0);
        const infaqPengeluaran  = formatCurrency(item.infaq_ramadan_pengeluaran_operasional ?? 0);

        let infaqPengeluaranList = '';
        if (item.infaq_ramadan_pengeluaran_detail?.length > 0) {
            infaqPengeluaranList = '<ul class="text-xs list-disc pl-4 mt-1 text-gray-600">';
            item.infaq_ramadan_pengeluaran_detail.forEach(d => {
                infaqPengeluaranList += `<li>${d.untuk}: ${formatCurrency(d.nominal)}</li>`;
            });
            infaqPengeluaranList += '</ul>';
        } else {
            infaqPengeluaranList = '<p class="text-xs text-gray-600 mt-1">Belum ada pengeluaran detail malam ini</p>';
        }

        const infaqBreakdown = `
            <div class="text-sm space-y-2 mt-3">
                <p class="flex justify-between items-center"><span>Saldo ${tglKemarin}:</span> <strong class="text-emerald-700">${infaqSaldoKemarin}</strong></p>
                <p class="flex justify-between items-center"><span>Penerimaan ${tanggal}:</span> <strong class="text-green-600">${infaqPenerimaan}</strong></p>
                <p class="flex justify-between items-center"><span>Pengeluaran ${tanggal}:</span> <strong class="text-red-600">${infaqPengeluaran}</strong></p>
                ${infaqPengeluaranList}
                <hr class="my-3 border-emerald-100">
                <p class="text-base font-bold text-emerald-900 flex justify-between items-center">
                    <span>Saldo Saat Ini (${tanggal}):</span> <span>${infaqSaldo}</span>
                </p>
            </div>
        `;

        // Breakdown Iftor dengan tanggal
        const ifthorSaldoKemarin = formatCurrency(item.ifthor_saldo_kemarin ?? 0);

        let ifthorPenerimaanList = '';
        if (item.ifthor_penerimaan_detail?.length > 0) {
            ifthorPenerimaanList = '<ul class="text-xs list-disc pl-4 mt-1 text-gray-600">';
            item.ifthor_penerimaan_detail.forEach(d => {
                ifthorPenerimaanList += `<li>${d.dari}: ${formatCurrency(d.nominal)}</li>`;
            });
            ifthorPenerimaanList += '</ul>';
        } else {
            ifthorPenerimaanList = '<p class="text-xs text-gray-600 mt-1">Belum ada detail penerimaan malam ini</p>';
        }

        let ifthorPengeluaranList = '';
        if (item.ifthor_pengeluaran_detail?.length > 0) {
            ifthorPengeluaranList = '<ul class="text-xs list-disc pl-4 mt-1 text-gray-600">';
            item.ifthor_pengeluaran_detail.forEach(d => {
                ifthorPengeluaranList += `<li>${d.untuk}: ${formatCurrency(d.nominal)}</li>`;
            });
            ifthorPengeluaranList += '</ul>';
        } else {
            ifthorPengeluaranList = '<p class="text-xs text-gray-600 mt-1">Belum ada detail pengeluaran malam ini</p>';
        }

        const ifthorBreakdown = `
            <div class="text-sm space-y-2 mt-3">
                <p class="flex justify-between items-center"><span>Saldo ${tglKemarin}:</span> <strong class="text-teal-700">${ifthorSaldoKemarin}</strong></p>
                <p>Penerimaan ${tanggal}:</p>
                ${ifthorPenerimaanList}
                <p>Pengeluaran ${tanggal}:</p>
                ${ifthorPengeluaranList}
                <hr class="my-3 border-teal-100">
                <p class="text-base font-bold text-teal-900 flex justify-between items-center">
                    <span>Saldo Saat Ini (${tanggal}):</span> <span>${ifthorSaldo}</span>
                </p>
            </div>
        `;

        // Santunan Yatim & Dhuafa breakdown dengan tanggal
        let santunanList = '';
        if (item.santunan_yatim_penerimaan_hari_ini?.length > 0) {
            santunanList = '<ul class="text-xs list-disc pl-4 mt-1 text-gray-600">';
            item.santunan_yatim_penerimaan_hari_ini.forEach(d => {
                santunanList += `<li>${d.dari}: ${formatCurrency(d.nominal)}</li>`;
            });
            santunanList += '</ul>';
        } else {
            santunanList = '<p class="text-xs text-gray-600 mt-1">Belum ada penerimaan detail malam ini</p>';
        }

        const santunanBreakdown = `
            <div class="text-sm space-y-2 mt-3">
                <p class="flex justify-between items-center"><span>Terkumpul ${tglKemarin}:</span> <strong class="text-amber-700">${formatCurrency(item.santunan_yatim_terkumpul_kemarin ?? 0)}</strong></p>
                <p>Penerimaan ${tanggal}:</p>
                ${santunanList}
                <hr class="my-3 border-amber-100">
                <p class="text-base font-bold text-amber-900 flex justify-between items-center">
                    <span>Total Terkumpul (${tanggal}):</span> <span>${santunan}</span>
                </p>
            </div>
        `;

        // Paket Sembako breakdown dengan tanggal
        let sembakoList = '';
        if (item.paket_sembako_penerimaan_hari_ini?.length > 0) {
            sembakoList = '<ul class="text-xs list-disc pl-4 mt-1 text-gray-600">';
            item.paket_sembako_penerimaan_hari_ini.forEach(d => {
                sembakoList += `<li>${d.dari}: ${formatCurrency(d.nominal)}</li>`;
            });
            sembakoList += '</ul>';
        } else {
            sembakoList = '<p class="text-xs text-gray-600 mt-1">Belum ada penerimaan detail malam ini</p>';
        }

        const sembakoBreakdown = `
            <div class="text-sm space-y-2 mt-3">
                <p class="flex justify-between items-center"><span>Terkumpul ${tglKemarin}:</span> <strong class="text-cyan-700">${formatCurrency(item.paket_sembako_terkumpul_kemarin ?? 0)}</strong></p>
                <p>Penerimaan ${tanggal}:</p>
                ${sembakoList}
                <hr class="my-3 border-cyan-100">
                <p class="text-base font-bold text-cyan-900 flex justify-between items-center">
                    <span>Total Terkumpul (${tanggal}):</span> <span>${sembako}</span>
                </p>
            </div>
        `;

        // Gebyar breakdown dengan tanggal
        let gebyarList = '';
        if (item.gebyar_anak_penerimaan_hari_ini?.length > 0) {
            gebyarList = '<ul class="text-xs list-disc pl-4 mt-1 text-gray-600">';
            item.gebyar_anak_penerimaan_hari_ini.forEach(d => {
                gebyarList += `<li>${d.dari}: ${formatCurrency(d.nominal)}</li>`;
            });
            gebyarList += '</ul>';
        } else {
            gebyarList = '<p class="text-xs text-gray-600 mt-1">Belum ada penerimaan detail malam ini</p>';
        }

        const gebyarBreakdown = `
            <div class="text-sm space-y-2 mt-3">
                <p class="flex justify-between items-center"><span>Terkumpul ${tglKemarin}:</span> <strong class="text-purple-700">${formatCurrency(item.gebyar_anak_terkumpul_kemarin ?? 0)}</strong></p>
                <p>Penerimaan ${tanggal}:</p>
                ${gebyarList}
                <hr class="my-3 border-purple-100">
                <p class="text-base font-bold text-purple-900 flex justify-between items-center">
                    <span>Total Terkumpul (${tanggal}):</span> <span>${gebyar}</span>
                </p>
            </div>
        `;

        return `
            <div class="collapse collapse-arrow bg-white rounded-3xl shadow-md border border-gray-100 hover:shadow-xl transition-all duration-300 hover:-translate-y-2">
               <input type="checkbox" class="laporan-toggle" />
                <div class="collapse-title text-base sm:text-lg font-semibold text-emerald-800 px-5 py-4 flex flex-col leading-snug">
                    <span class="text-emerald-900">
                        Laporan Malam ke-${malamKe}
                    </span>

                    <span class="text-sm sm:text-base text-gray-600 font-medium mt-1">
                        ${tanggal}
                    </span>
                </div>
                <div class="collapse-content px-6 pb-8 pt-4 text-gray-700">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Infaq Ramadhan -->
                        <div class="bg-emerald-50/50 p-6 rounded-2xl border border-emerald-100 shadow-sm transition-shadow duration-300 hover:shadow-md">
                            <h4 class="font-bold text-emerald-900 mb-4 text-lg flex items-center gap-2">💰 Infaq Ramadhan</h4>
                            ${infaqBreakdown}
                        </div>

                        <!-- Saldo Iftor -->
                        <div class="bg-teal-50/50 p-6 rounded-2xl border border-teal-100 shadow-sm transition-shadow duration-300 hover:shadow-md">
                            <h4 class="font-bold text-teal-900 mb-4 text-lg flex items-center gap-2">🍲 Saldo Iftor</h4>
                            ${ifthorBreakdown}
                        </div>

                        <!-- Santunan Yatim & Dhuafa -->
                        <div class="bg-amber-50/50 p-6 rounded-2xl border border-amber-100 shadow-sm transition-shadow duration-300 hover:shadow-md">
                            <h4 class="font-bold text-amber-900 mb-4 text-lg flex items-center gap-2">👶 Santunan Yatim & Dhuafa</h4>
                            ${santunanBreakdown}
                        </div>

                        <!-- Paket Sembako -->
                        <div class="bg-cyan-50/50 p-6 rounded-2xl border border-cyan-100 shadow-sm transition-shadow duration-300 hover:shadow-md">
                            <h4 class="font-bold text-cyan-900 mb-4 text-lg flex items-center gap-2">🛒 Paket Sembako</h4>
                            ${sembakoBreakdown}
                        </div>

                        <!-- Gebyar Ramadhan -->
                        <div class="bg-purple-50/50 p-6 rounded-2xl border border-purple-100 shadow-sm transition-shadow duration-300 hover:shadow-md">
                            <h4 class="font-bold text-purple-900 mb-4 text-lg flex items-center gap-2">🎉 Gebyar Ramadhan</h4>
                            ${gebyarBreakdown}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }


    /* ================================
    GALERI RAMADHAN - FIXED VERSION
    ================================ */

    let galeriButtons = [];

    async function loadGaleri() {
    
        const container = document.getElementById('galeri-list');
        if (!container) return;

            container.innerHTML = `
                <div class="col-span-full text-center py-10 text-gray-500 animate-pulse">
                    Memuat galeri suasana masjid...
                </div>
            `;

            try {
                const res = await fetch('{{ route("home.galeri.public") }}');
                if (!res.ok) throw new Error('Server tidak merespon');

                const json = await res.json();

                container.innerHTML = '';

                if (!json?.data?.length) {
                    container.innerHTML =
                        '<p class="col-span-full text-center text-gray-500 py-10">Belum ada foto galeri saat ini.</p>';
                    return;
                }

                /* ===== INI PENTING (untuk slider modal) ===== */
                window.galeriItems = json.data;

                galeriButtons = [];

                json.data.forEach((item, index) => {

                    if (!item.img) return;

                    const button = document.createElement('button');
                    button.type = 'button';
                    button.className =
                        'relative group rounded-xl overflow-hidden shadow-sm';
                    button.dataset.index = index;

                    button.innerHTML = `
                        <img src="${item.img}" 
                             loading="lazy"
                             class="w-full aspect-[4/3] object-cover group-hover:scale-110 transition duration-500">

                        <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition flex items-end">
                            <p class="text-[10px] text-white px-2 pb-2 line-clamp-2">
                                ${item.judul}
                            </p>
                        </div>
                    `;

                    button.onclick = () => openGaleriModal(item.id);

                    container.appendChild(button);
                    galeriButtons.push(button);
                });

            } catch (err) {
                console.error(err);
                container.innerHTML =
                    '<p class="col-span-full text-center text-red-600 py-10">Gagal memuat galeri. Coba refresh.</p>';
            }

    }

    /* =============================
    MODAL SLIDER GALERI
    ============================= */

    async function openGaleriModal(galeriId) {

        const modal = document.getElementById('galeriModal');
        if (!modal) return;

        const imgEl = document.getElementById('galeriModalImg');
        const titleEl = document.getElementById('galeriModalTitle');
        const counterEl = document.getElementById('galeriCounter');
        const thumbsEl = document.getElementById('galeriThumbs');

        try {

            const res = await fetch(`/home/galeri/${galeriId}`);
            const json = await res.json();

            if (!json?.fotos?.length) return;

            let current = 0;
            const items = json.fotos;

            function update() {
                imgEl.src = items[current].url;
                imgEl.alt = items[current].caption || '';
                titleEl.textContent = items[current].caption || '';
                counterEl.textContent = `${current + 1} / ${items.length}`;

                Array.from(thumbsEl.children).forEach((thumb, i) => {
                    thumb.classList.toggle('ring-2', i === current);
                    thumb.classList.toggle('ring-emerald-500', i === current);
                });
            }

            thumbsEl.innerHTML = '';

            items.forEach((item, i) => {
                const thumb = document.createElement('button');
                thumb.className =
                    'snap-center flex-shrink-0 w-20 sm:w-24 h-16 sm:h-20 rounded-lg overflow-hidden border-2 border-transparent transition';

                thumb.innerHTML =
                    `<img src="${item.url}" class="w-full h-full object-cover">`;

                thumb.onclick = () => {
                    current = i;
                    update();
                };

                thumbsEl.appendChild(thumb);
            });

            document.getElementById('galeriPrev').onclick = () => {
                current = (current - 1 + items.length) % items.length;
                update();
            };

            document.getElementById('galeriNext').onclick = () => {
                current = (current + 1) % items.length;
                update();
            };

            document.getElementById('closeGaleriModalBtn').onclick = () => modal.close();

            update();
            modal.showModal();

        } catch (err) {
            console.error('Gagal memuat detail galeri', err);
        }
    }

    /* ===== SWIPE MOBILE ===== */
    let startX = 0;
    const img = document.getElementById('galeriModalImg');

    img.addEventListener('touchstart', e=>{
        startX = e.touches[0].clientX;
    });

    img.addEventListener('touchend', e=>{
        let endX = e.changedTouches[0].clientX;

        if(endX - startX > 60){
            document.getElementById('galeriPrev').click();
        }
        if(startX - endX > 60){
            document.getElementById('galeriNext').click();
        }
    });

    async function loadLaporan() {
        const loading = document.getElementById('loading-state');
        const empty = document.getElementById('empty-state');
        const list = document.getElementById('laporan-list');

        loading.classList.remove('hidden');
        empty.classList.add('hidden');
        list.innerHTML = '';

        try {
            const res = await fetch('{{ route('admin.ramadhan.laporan-harian.data') }}');
            const json = await res.json();

            if (json?.data?.data?.length > 0) {
                json.data.data.forEach(item => {
                    list.innerHTML += renderLaporanItem(item);
                    document.querySelectorAll('.collapse').forEach(el => {
                        el.classList.add('animate-fade-in', 'delay-1000');
                    });
                });
                updateTotals(json.data.totals);
            } else {
                empty.classList.remove('hidden');
            }
        } catch (err) {
            console.error(err);
            Swal.fire({
                icon: 'error',
                title: 'Gagal memuat',
                text: 'Coba refresh halaman',
                confirmButtonColor: '#10b981'
            });
        } finally {
            loading.classList.add('hidden');
        }
    }

    // Fungsi baru untuk load Jadwal Imam (asumsikan endpoint)
    async function loadJadwalImam() {
        const body = document.getElementById('jadwal-imam-body');
        body.innerHTML = '<tr><td colspan="4" class="text-center py-6">Memuat...</td></tr>';  // colspan 4
        try {
            console.log('Fetching jadwal from: {{ route('admin.ramadhan.jadwal-imam.data') }}'); // Debug URL
            const res = await fetch('{{ route('admin.ramadhan.jadwal-imam.data') }}');
            if (!res.ok) {
                throw new Error(`Error: ${res.status} - ${res.statusText}`);
            }
            const contentType = res.headers.get("content-type");
            if (!contentType || !contentType.includes("application/json")) {
                throw new Error("Response bukan JSON: " + contentType);
            }
            const json = await res.json();
            console.log('JSON jadwal:', json); // Debug full JSON
            if (json.data && json.data.length > 0) {
                console.log('Contoh item pertama:', json.data[0]); // Debug satu item untuk cek fields
            }
            body.innerHTML = '';
            if (json?.data?.length > 0) {
                json.data.forEach(item => {
                    const tgl = formatTanggal(item.tanggal) || '-'; // Sudah ada fungsi ini
                    body.innerHTML += `
                        <tr class="border-b">
                            <td class="px-4 py-3 sm:px-6 sm:py-4 w-[10%] whitespace-nowrap overflow-hidden text-ellipsis">${item.malam_ke || '-'}</td>  <!-- Lebar 10%, nowrap -->
                            <td class="px-4 py-3 sm:px-6 sm:py-4 w-[20%] whitespace-nowrap overflow-hidden text-ellipsis">${tgl}</td>  <!-- Lebar 20%, nowrap -->
                            <td class="px-4 py-3 sm:px-6 sm:py-4 w-[30%] whitespace-nowrap overflow-hidden text-ellipsis">${item.imam_nama || '-'}</td>  <!-- Lebar 30%, nowrap -->
                            <td class="px-4 py-3 sm:px-6 sm:py-4 w-[40%] whitespace-normal break-words hyphens-auto overflow-hidden">${item.tema_tausiyah || '-'}</td>  <!-- Lebar 40%, wrap full + hyphen -->
                        </tr>
                    `;
                });
            } else {
                body.innerHTML = '<tr><td colspan="4" class="text-center py-6">Belum ada jadwal</td></tr>';  // colspan 4
            }
        } catch (err) {
            console.error('Error load jadwal imam:', err);
            body.innerHTML = '<tr><td colspan="4" class="text-center py-6 text-red-600">Gagal memuat jadwal: ' + err.message + '</td></tr>';  // colspan 4
        }
    }
    // Fungsi baru untuk load Dampak Hari Ini (asumsikan dari data laporan atau endpoint terpisah)
    // function updateDampakHariIni(data) {
    //     // Asumsikan data dari laporan, misal hitung dari totals
    //     document.getElementById('dampak-iftor').textContent = `${Math.round(parseToNumber(data.ifthor) / 20000)} Porsi`; // Misal 20k per porsi
    //     document.getElementById('dampak-yatim').textContent = `${Math.round(parseToNumber(data.santunan_yatim) / 500000)} Anak`; // Misal 500k per anak
    //     document.getElementById('dampak-sembako').textContent = `${Math.round(parseToNumber(data.paket_sembako) / 300000)} Paket`; // Misal 300k per paket
    // }

    // Fungsi baru untuk load Donatur Hari Ini (asumsikan endpoint)
    async function loadDonaturHariIni() {
        const list = document.getElementById('donatur-list');
        list.innerHTML = '<div class="text-center text-gray-500">Memuat daftar donatur...</div>';
        try {
            const res = await fetch('{{ route('admin.ramadhan.donatur-hari-ini.data') }}'); // Asumsikan route
            const json = await res.json();
            list.innerHTML = '';
            if (json?.data?.length > 0) {
                json.data.forEach(d => {
                    list.innerHTML += `
                        <div class="bg-gray-50 p-4 rounded-xl flex justify-between items-center">
                            <span class="font-semibold text-emerald-800">${d.nama}</span>
                            <span class="text-gray-600">${formatCurrency(d.nominal)}</span>
                        </div>
                    `;
                });
            } else {
                list.innerHTML = '<div class="text-center text-gray-500">Belum ada donatur hari ini</div>';
            }
        } catch (err) {
            list.innerHTML = '<div class="text-center text-red-600">Gagal memuat donatur</div>';
        }
    }

    // Load semua data
    async function loadAll() {
        const res = await fetch('{{ route('admin.ramadhan.laporan-harian.data') }}');
        const json = await res.json();
        loadLaporan();
        loadJadwalImam();
        loadDonaturHariIni();
        loadGaleri();
    }

    loadAll();
</script>

<style>
    .total-amount {
        font-size: clamp(1.25rem, 4vw, 1.75rem);
        font-weight: 900;
        line-height: 1.1;
        letter-spacing: -0.5px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        display: block;
    }

    .breakdown-container {
        font-size: 0.875rem; /* 14px */
        line-height: 1.4;
    }

    .breakdown-list {
        max-height: 120px;
        overflow-y: auto;
        padding-right: 8px;
    }

    @media (max-width: 640px) {
        .card-body { padding: 1.25rem 1rem !important; }
        .radial-progress { --size: 3.5rem !important; --thickness: 5px !important; }
        .text--6xl { font-size: 4rem !important; }
        .total-amount{
            font-weight: 900;
            line-height: 1.05;
            letter-spacing: -0.4px;
            white-space: nowrap;

            /* INI YANG BENAR */
            font-size: clamp(2rem, 7vw, 3rem);
        }
        .breakdown-container {
            font-size: 0.85rem; /* lebih kecil di HP */
        }
        .breakdown-list {
            max-height: 100px;
        }
        .grid-cols-1 > div {
            min-width: 100%;
        }
        /* Agar teks breakdown wrap rapi */
        p.flex, .flex.justify-between {
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        p.flex span:first-child {
            flex: 1 1 60%;
        }
        p.flex span:last-child {
            flex: 1 1 40%;
            text-align: right;
        }
        thead th {
            position: sticky;
            top: 0;
            z-index: 10;
            background-color: inherit;  /* Inherit bg dari thead */
        }
        .overflow-x-auto {
            -webkit-overflow-scrolling: touch;  /* Smooth scroll di iOS/HP */
        }
        .program-desc{
            font-size: .82rem;
        }
    }

    .collapse:hover {
        transform: translateY(-2px);
    }

    /* Add smooth fade-in animation */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }

    .delay-1000 {
        animation-delay: 0.3s;
    }
    .overflow-y-auto::-webkit-scrollbar {
        +width: 8px;
    }
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #10b981;  /* Warna emerald */
        border-radius: 4px;
    }
    /* =================================================
       GALERI MODAL VIEWER - STABLE LANDSCAPE RESPONSIVE
       ================================================= */

    .galeri-viewer-wrapper{
        position: relative;
        width: 100%;
        background: #f1f5f9;
    }

    /* FRAME LANDSCAPE (TIDAK BERUBAH) */
    .galeri-viewer{
        width: 100%;
        aspect-ratio: 16/9;
        background: #f8fafc;
        display:flex;
        align-items:center;
        justify-content:center;
        overflow:hidden;
    }

    /* GAMBAR */
    #galeriModalImg{
        max-width:100%;
        max-height:100%;
        object-fit:contain;
        background:white;
        border-radius:12px;
        box-shadow:0 15px 35px rgba(0,0,0,.15);
        transition:opacity .25s ease;
    }

    /* ================= NAVIGATION ================= */

    .galeri-nav{
        position:absolute;
        top:50%;
        transform:translateY(-50%);
        width:44px;
        height:44px;
        border-radius:50%;
        background:rgba(255,255,255,.92);
        border:1px solid #e5e7eb;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:22px;
        cursor:pointer;
        z-index:20;
        transition:.25s;
    }

    .galeri-nav:hover{
        transform:translateY(-50%) scale(1.1);
        background:white;
    }

    .galeri-prev{ left:12px; }
    .galeri-next{ right:12px; }

    /* CLOSE BUTTON */
    .galeri-close{
        position:absolute;
        top:12px;
        right:12px;
        width:38px;
        height:38px;
        border-radius:10px;
        background:rgba(0,0,0,.6);
        color:white;
        border:none;
        font-size:18px;
        z-index:25;
        display:flex;
        align-items:center;
        justify-content:center;
    }

    /* ================= MOBILE ================= */

    @media(max-width:768px){

        /* biar HP nyaman */
        .galeri-viewer{
            aspect-ratio:4/3;
        }

        .galeri-nav{
            width:36px;
            height:36px;
            font-size:16px;
        }

        .galeri-prev{ left:6px; }
        .galeri-next{ right:6px; }

        .galeri-close{
            width:34px;
            height:34px;
            top:8px;
            right:8px;
        }
    }

    /* HP kecil */
    @media(max-width:420px){
        .galeri-viewer{
            aspect-ratio:1/1;
        }
    }

    /* =========================
       RESET BUTTON DAISYUI MODAL
       ========================= */

    #galeriModal .galeri-nav,
    #galeriModal .galeri-close{
        all: unset;
        position: absolute;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        user-select: none;
    }

    /* NAV BUTTON (PREV / NEXT) */
    #galeriModal .galeri-nav{
        top: 50%;
        transform: translateY(-50%);
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(255,255,255,.92);
        color: #065f46;
        font-size: 22px;
        box-shadow: 0 8px 22px rgba(0,0,0,.25);
        transition: .2s;
    }

    #galeriModal .galeri-nav:hover{
        background: #10b981;
        color: white;
        transform: translateY(-50%) scale(1.1);
    }

    /* POSITION */
    #galeriModal .galeri-prev{ left: 14px; }
    #galeriModal .galeri-next{ right: 14px; }

    /* CLOSE BUTTON */
    #galeriModal .galeri-close{
        top: 12px;
        right: 12px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(0,0,0,.65);
        color: white;
        font-size: 18px;
        transition: .2s;
    }

    #galeriModal .galeri-close:hover{
        background: #ef4444;
    }

    /* MOBILE SIZE */
    @media(max-width:640px){
        #galeriModal .galeri-nav{
            width:40px;
            height:40px;
            font-size:20px;
        }
    }
    /* =========================
       RESET BUTTON DAISYUI MODAL
       ========================= */

    #galeriModal .galeri-nav,
    #galeriModal .galeri-close{
        all: unset;
        position: absolute;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        user-select: none;
    }

    /* NAV BUTTON (PREV / NEXT) */
    #galeriModal .galeri-nav{
        top: 50%;
        transform: translateY(-50%);
        width: 46px;
        height: 46px;
        border-radius: 50%;
        background: rgba(255,255,255,.92);
        color: #065f46;
        font-size: 22px;
        box-shadow: 0 8px 22px rgba(0,0,0,.25);
        transition: .2s;
    }

    #galeriModal .galeri-nav:hover{
        background: #10b981;
        color: white;
        transform: translateY(-50%) scale(1.1);
    }

    /* POSITION */
    #galeriModal .galeri-prev{ left: 14px; }
    #galeriModal .galeri-next{ right: 14px; }

    /* CLOSE BUTTON */
    #galeriModal .galeri-close{
        top: 12px;
        right: 12px;
        width: 38px;
        height: 38px;
        border-radius: 50%;
        background: rgba(0,0,0,.65);
        color: white;
        font-size: 18px;
        transition: .2s;
    }

    #galeriModal .galeri-close:hover{
        background: #ef4444;
    }

    /* MOBILE SIZE */
    @media(max-width:640px){
        #galeriModal .galeri-nav{
            width:40px;
            height:40px;
            font-size:20px;
        }
    }
    .total-amount{
        letter-spacing: .02em;
    }

    /* Desktop khusus */
    @media (min-width:1024px){
        .total-amount{
            font-size: 1.15rem;
        }
    }

    /* Layar sangat lebar */
    @media (min-width:1400px){
        .total-amount{
            font-size: 1.3rem;
        }
    }
    /* ===== DESKRIPSI EMOTIONAL ===== */
    .program-desc{
        font-size: .855rem;
        line-height: 1.6;
        color: #065f46;

        border: 1px solid rgba(16,185,129,.18);
        border-radius: 14px;

        padding: .8rem .9rem;

        font-style: italic;
        font-weight: 450;

        /* agar semua card sama tinggi TANPA memotong teks */
        min-height: 84px;

        display:flex;
        align-items:center;
        justify-content:center;
        text-align:center;

        transition:.25s ease;
    }

    .program-desc:hover{
        background: linear-gradient(
            180deg,
            rgba(16,185,129,.14),
            rgba(16,185,129,.06)
        );
        transform: translateY(-2px);
    }
    .card-body{
        display:flex;
        flex-direction:column;
        justify-content:space-between;
    }

    /* ===== GALERI CENTER PERFECT ===== */
    .galeri-grid{
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
        justify-content: center;
        gap: 14px;
        max-width: 720px;   /* ini penting */
        margin: 0 auto;     /* true center */
    }

    /* kartu foto */
    .galeri-grid > button{
        width: 100%;
        max-width: 170px;
    }

    /* biar isi kartunya ikut center */
    .galeri-grid > button{
        justify-self: center;
    }

    /* mobile */
    @media (max-width:640px){
        .galeri-grid{
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            max-width: 95%;
        }
    }
    /* ===== RADIAL PROGRESS LABEL FIX (FINAL) ===== */
    .progress-wrapper{
        position: relative;
        width: 80px;
        height: 80px;
        margin: 0 auto;
    }

    .progress-ring{
        transform: rotate(-90deg);
    }

    .progress-ring-bg{
        opacity: .3;
    }

    .progress-ring-fill{
        stroke-linecap: round;
        stroke-dasharray: 201;
        stroke-dashoffset: 201;
        transition: stroke-dashoffset .6s ease;
    }

    .progress-label{
        position: absolute;
        inset: 0;
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:.8rem;
        font-weight:700;
    }
    /* Santunan */
    #progress-santunan .progress-label{
        color: #b45309;        /* amber-700 */
    }

    /* Sembako */
    #progress-sembako .progress-label{
        color: #0e7490;        /* cyan-700 */
    }

    /* bonus: sedikit lebih kontras di mobile */
    @media (max-width:640px){
        .progress-label{
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .02em;
        }
    }
    .card-meta{
        font-size: .8rem;
        font-weight: 600;
        opacity: .85;
        text-align:center;
        padding: .35rem .7rem;
        border-radius: 999px;
        background: rgba(16,185,129,.08);
        display:inline-block;
    }
    /* ===== HERO HIGHLIGHT MASJID ===== */

    .highlight-masjid{
        position: relative;
        display: inline-block;
        padding: 0 .35em;
        z-index: 1;
    }

    /* efek stabilo */
    .highlight-masjid::before{
        content:"";
        position:absolute;
        left:0;
        right:0;
        bottom:.15em;
        height:.55em;

        background: linear-gradient(
            120deg,
            rgba(16,185,129,.35),
            rgba(52,211,153,.55)
        );

        border-radius: .25em;
        z-index:-1;

        transform: skewX(-8deg);
    }
    .highlight-masjid::before{
        animation: highlightReveal .9s ease-out .2s both;
    }

    @keyframes highlightReveal{
        from{
            transform: scaleX(0) skewX(-8deg);
            transform-origin:left;
        }
        to{
            transform: scaleX(1) skewX(-8deg);
            transform-origin:left;
        }
    }
    .ramadhan-night{
        background: linear-gradient(
            180deg,
            #ecfdf5 0%,
            #d1fae5 40%,
            #bbf7d0 100%
        );
    }
    .card:hover .program-desc{
        background: linear-gradient(
            180deg,
            rgba(16,185,129,.15),
            rgba(16,185,129,.05)
        );
    }
    /* ===== NOMOR REKENING RESPONSIVE FIX ===== */
    .rek-number{
        font-size: clamp(0.95rem, 4.2vw, 1.6rem);
        letter-spacing: .06em;
        line-height: 1.35;
        word-break: break-word;
    }

    /* HP kecil banget */
    @media (max-width:380px){
        .rek-number{
            font-size: 1.1rem;
            letter-spacing: .04em;
        }
    }
    /* Jadwal tarawih mobile breathing */
    #jadwal-imam-body td{
        padding-left: .75rem;
        padding-right: .75rem;
    }

    @media (max-width:640px){
        #jadwal-imam-body td{
            padding-left: .6rem;
            padding-right: .6rem;
        }
    }
    #jadwal-imam-body tr:hover{
        background: #f0fdf4;
    }
    /* Hierarchy fix laporan */
    #laporan-list .collapse-content{
        font-size: 0.92rem;
    }

    #laporan-list h4{
        font-size: 1rem;
        font-weight: 600;
    }

</style>
@endpush