@extends('masjid.master-guest')

@push('head')
    {{-- BASIC SEO --}}
    <title>Pendaftaran Program Kesehatan | Masjid Raudhotul Jannah TCE</title>
    <meta name="description" content="Daftarkan diri Anda untuk program kesehatan gratis: Donor Darah, Cek Gula Darah, Kolesterol, Asam Urat, Tensi Darah, dan Kesehatan Mata di Masjid Raudhotul Jannah TCE.">

    {{-- OPEN GRAPH (WA & FB) --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pendaftaran Program Kesehatan | Masjid Raudhotul Jannah TCE">
    <meta property="og:description" content="Program Kesehatan: Donor Darah, Cek Gula Darah, Kolesterol, Asam Urat, Tensi Darah, dan Kesehatan Mata.">
    <meta property="og:image" content="{{ secure_url('storage/mrj/mrj.webp') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Masjid Raudhotul Jannah TCE">
    <meta property="og:locale" content="id_ID">

    {{-- FIX IMAGE WA --}}
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- TWITTER --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pendaftaran Program Kesehatan | Masjid Raudhotul Jannah TCE">
    <meta name="twitter:description" content="Donor Darah, Cek Gula Darah, Kolesterol, Asam Urat, Tensi Darah, dan Kesehatan Mata gratis di Masjid Raudhotul Jannah TCE.">
    <meta name="twitter:image" content="{{ secure_url('storage/mrj/mrj.webp') }}">
@endpush

@section('content')
@php
    // Nomor WhatsApp harus menggunakan format 62, tanpa tanda +, spasi, atau strip
    $adminNama = profil('nama');
    $adminWhatsapp = profil('wa');
    $adminWhatsappTampil = waNumberFormatted();

    $pesanWhatsapp = urlencode(
        'Assalamu’alaikum, saya ingin bertanya mengenai pendaftaran Program Kesehatan Masjid Raudhotul Jannah TCE.'
    );
@endphp
<section class="py-14 bg-gradient-to-br from-slate-50 to-white">
    <div class="container mx-auto px-4 lg:px-6">
        <div class="max-w-xl mx-auto">
          
            <!-- Card Utama -->
            <div class="bg-white rounded-3xl border border-emerald-100 shadow-xl overflow-hidden">

                <!-- Header -->
                <div class="bg-emerald-700 py-10 md:py-12 px-6 text-center">
                    <img src="{{ asset('storage/mrj/mrj.webp') }}"
                         alt="Logo Masjid Raudhotul Jannah"
                         class="h-20 mx-auto drop-shadow-lg mb-5">
                         
                    <h2 class="text-xl md:text-2xl font-bold text-white font-arabic mb-2 tracking-wide">
                        بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ
                    </h2>

                    <p class="text-emerald-100 text-lg font-medium">Program Kesehatan Rutin</p>
                    <p class="text-emerald-200 text-sm mt-1">Masjid Raudhotul Jannah TCE</p>
                </div>

                <!-- Divider -->
                <div class="h-px bg-emerald-100"></div>

                <div class="p-6 md:p-8 text-slate-800">

                    <!-- Pengantar -->
                    <div class="bg-gradient-to-br from-emerald-50 to-white border border-emerald-100 rounded-2xl p-5 md:p-6 mb-8 shadow-sm">

                        <!-- Salam -->
                        <div class="text-center mb-5">
                            <p class="text-emerald-700 text-sm leading-relaxed">
                                Assalamu’alaikum warahmatullahi wabarakatuh.
                            </p>
                            <p class="text-slate-600 text-sm mt-2">
                                Silakan melengkapi form berikut dengan data yang sesuai.
                            </p>
                            <!-- TAMBAHAN DI SINI -->
                            <p class="text-center text-slate-600 text-sm mt-2">
                                Mari ambil bagian dalam kegiatan kebaikan ini, sekaligus menjaga kesehatan diri.
                            </p>
                        </div>

                        <!-- Kerjasama -->
                        <div class="mb-6">
                            <p class="text-center text-emerald-700 font-medium text-sm mb-4">
                               Bersama dalam kolaborasi kebaikan
                            </p>
                            
                            <div class="grid grid-cols-1 gap-3">
                                
                                <!-- RS Kanker Dharmais -->
                                <div class="flex items-center gap-3 bg-white border border-emerald-200 rounded-xl p-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden border border-emerald-100 bg-white">
                                        <img
                                            src="{{ asset('storage/mrj/dharmais.webp') }}"
                                            alt="Logo RS Kanker Dharmais"
                                            class="w-9 h-9 object-contain"
                                        >
                                    </div>
                                    <div class="text-sm">
                                        <div class="font-semibold text-emerald-900">Donor Darah</div>
                                        <div class="text-slate-500 text-xs">RS Kanker Dharmais</div>
                                    </div>
                                </div>

                                <!-- RS Murni Teguh -->
                                <div class="flex items-center gap-3 bg-white border border-emerald-200 rounded-xl p-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center overflow-hidden border border-emerald-100 bg-white">
                                        <img
                                            src="{{ asset('storage/mrj/murni_teguh.webp') }}"
                                            alt="Logo RS Murni Teguh"
                                            class="w-9 h-9 object-contain"
                                        >
                                    </div>
                                    <div class="text-sm">
                                        <div class="font-semibold text-emerald-900">Pemeriksaan Kesehatan</div>
                                        <div class="text-slate-500 text-xs">RS Murni Teguh</div>
                                    </div>
                                </div>

                            </div>

                    </div>

                    <!-- Judul -->
                    <h1 class="text-xl md:text-2xl font-bold text-emerald-800 text-center mb-8">
                        Form Pendaftaran
                    </h1>

                    <!-- Form -->
                    <form id="daftarForm" class="space-y-6">
                        @csrf
                        <input type="hidden" name="event_date" value="{{ $eventDate }}">

                        <!-- Nama -->
                        <div>
                            <label class="block pb-2">
                                <span class="font-semibold text-slate-700 text-sm">Nama Lengkap (Wajib diisi)</span>
                            </label>
                            <input type="text" name="nama_lengkap" required
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-sm"
                                   placeholder="Contoh: Ahmad Santoso">
                        </div>

                        <!-- HP -->
                        <div>
                            <label class="block pb-2">
                                <span class="font-semibold text-slate-700 text-sm">Nomor HP / WhatsApp (Wajib diisi)</span>
                            </label>
                            <input type="tel" name="no_hp" required
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-sm"
                                   placeholder="0812 3456 7890">
                        </div>

                        <!-- Alamat -->
                        <div>
                            <label class="block pb-2">
                                <span class="font-semibold text-slate-700 text-sm">Alamat (Opsional)</span>
                            </label>
                            <textarea name="alamat" rows="3"
                                      class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-sm"
                                      placeholder="Contoh: TCE Blok B1 No.10"></textarea>
                        </div>

                        <!-- Program -->
                        <div>
                            <label class="block pb-2">
                                <span class="font-semibold text-slate-700 text-sm">Pilih Program:</span>
                            </label>
                            
                            <!-- Catatan -->
                            <p class="text-xs text-emerald-600 mb-4">
                                Silakan memilih satu atau lebih layanan sesuai kebutuhan.
                            </p>                     
                            
                            <div class="space-y-3 mt-2">
                                
                                <!-- Donor Darah -->
                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition-all">
                                    <input type="checkbox" name="donor_darah" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-emerald-800">Donor Darah</div>
                                        <p class="text-slate-500 text-xs mt-1">Berpartisipasi dalam donor darah sebagai bentuk kepedulian kepada sesama.</p>
                                    </div>
                                </label>

                                @php
                                    $isFull = $jumlahGulaDarah >= $kuotaGulaDarah;
                                @endphp

                                <label class="flex gap-3 p-4 border rounded-xl cursor-pointer transition-all
                                    {{ $isFull ? 'border-red-300 bg-red-50 cursor-not-allowed opacity-70' : 'border-emerald-100 hover:border-emerald-400' }}">

                                    <input 
                                        type="checkbox" 
                                        name="cek_kesehatan[]" 
                                        value="gula_darah"
                                        class="w-5 h-5 accent-emerald-600 mt-1"
                                        {{ $isFull ? 'disabled' : '' }}
                                    >

                                    <div class="flex-1">
                                        <div class="font-semibold text-sm 
                                            {{ $isFull ? 'text-red-700' : 'text-emerald-800' }}">
                                            Pemeriksaan Gula Darah
                                        </div>

                                        <p class="text-xs mt-1 
                                            {{ $isFull ? 'text-red-500' : 'text-slate-500' }}">
                                            
                                            @if($isFull)
                                                Mohon maaf, kuota pemeriksaan ini telah terpenuhi
                                            @else
                                                Pemeriksaan kadar gula darah tanpa biaya
                                            @endif

                                        </p>
                                    </div>
                                </label>

                                <!-- Pemeriksaan Kolesterol -->
                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition-all">
                                    <input type="checkbox" name="cek_kesehatan[]" value="kolesterol" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-emerald-800">Pemeriksaan Kolesterol</div>
                                        <p class="text-slate-500 text-xs mt-1">Pemeriksaan kadar kolesterol untuk membantu memantau kesehatan tubuh.</p>
                                    </div>
                                </label>

                                <!-- Pemeriksaan Asam Urat -->
                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition-all">
                                    <input type="checkbox" name="cek_kesehatan[]" value="asam_urat" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-emerald-800">Pemeriksaan Asam Urat</div>
                                        <p class="text-slate-500 text-xs mt-1">Pemeriksaan kadar asam urat untuk mengetahui risiko keluhan sendi dan metabolisme.</p>
                                    </div>
                                </label>

                                <!-- Pemeriksaan Tensi Darah -->
                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition-all">
                                    <input type="checkbox" name="cek_kesehatan[]" value="tensi_darah" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-emerald-800">Pemeriksaan Tensi Darah</div>
                                        <p class="text-slate-500 text-xs mt-1">Pemeriksaan tekanan darah untuk mengetahui kondisi kesehatan secara umum.</p>
                                    </div>
                                </label>

                                <!-- Kesehatan Mata -->
                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition-all">
                                    <input type="checkbox" name="cek_mata_katarak" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-sm text-emerald-800">Kesehatan Mata</div>
                                        <p class="text-slate-500 text-xs mt-1">Pemeriksaan mata untuk mendeteksi dini tanda-tanda katarak dan gangguan penglihatan lainnya.</p>
                                    </div>
                                </label>

                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="pt-4">
                            <button type="submit" id="btnSubmit"
                                class="w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 
                                       text-white font-semibold text-sm md:text-base rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span id="btnText">Daftar & Ikuti Kegiatan</span>
                                <span id="spinner" class="hidden animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                            </button>
                        </div>

                    </form>
                </div>
                    <!-- ==================== BANTUAN PANITIA ==================== -->
                    <div class="mt-8 pt-8 border-t border-emerald-100">
                        <div class="relative overflow-hidden rounded-2xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-5 md:p-6 shadow-sm">

                            <!-- Dekorasi -->
                            <div class="absolute -top-10 -right-10 w-28 h-28 rounded-full bg-emerald-100 opacity-60"></div>

                            <div class="relative">
                                <div class="flex items-start gap-4">

                                    <!-- Ikon bantuan -->
                                    <div class="shrink-0 w-12 h-12 rounded-xl bg-emerald-600 text-white flex items-center justify-center shadow-md">
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            class="w-6 h-6"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M18.364 5.636a9 9 0 11-12.728 0m12.728 0L15.536 8.464m2.828-2.828l-2.828-2.828M5.636 5.636l2.828 2.828M5.636 5.636L8.464 2.808M12 8v4m0 4h.01"
                                            />
                                        </svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-bold text-emerald-900 text-base md:text-lg">
                                            Mengalami Kendala?
                                        </h3>

                                        <p class="text-slate-600 text-sm leading-relaxed mt-1">
                                            Hubungi panitia jika mengalami kendala saat pendaftaran.
                                            Kami siap membantu memberikan solusi dan informasi lebih lanjut.
                                        </p>

                                        <!-- Informasi admin -->
                                        <div class="mt-4 bg-white border border-emerald-100 rounded-xl p-4">
                                            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                                Kontak Panitia
                                            </p>

                                            <p class="font-semibold text-emerald-900 mt-1">
                                                {{ $adminNama }}
                                            </p>

                                            <p class="text-sm font-medium text-slate-600 mt-1">
                                                {{ $adminWhatsappTampil }}
                                            </p>
                                        </div>

                                        <!-- Tombol aksi -->
                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-4">

                                            <!-- Hubungi WhatsApp -->
                                            <a
                                                href="https://wa.me/{{ $adminWhatsapp }}?text={{ $pesanWhatsapp }}"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-4 py-3 text-sm font-semibold text-white shadow-sm transition"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    viewBox="0 0 24 24"
                                                    fill="currentColor"
                                                >
                                                    <path d="M20.52 3.48A11.86 11.86 0 0012.06 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.16 1.6 5.97L.06 24l6.28-1.65a11.9 11.9 0 005.71 1.46h.01c6.56 0 11.9-5.34 11.9-11.9 0-3.18-1.22-6.17-3.44-8.43zM12.06 21.8h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.73.98 1-3.63-.23-.37a9.84 9.84 0 01-1.52-5.29c0-5.45 4.43-9.88 9.89-9.88a9.8 9.8 0 017 2.9 9.81 9.81 0 012.89 7c-.01 5.45-4.45 9.88-9.9 9.88zm5.42-7.4c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48a9 9 0 01-1.66-2.07c-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.61-.91-2.21-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.22 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.19 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.42-.07-.12-.27-.2-.57-.35z"/>
                                                </svg>

                                                Hubungi via WhatsApp
                                            </a>

                                            <!-- Salin nomor -->
                                            <button
                                                type="button"
                                                id="btnCopyAdmin"
                                                data-number="{{ $adminWhatsappTampil }}"
                                                class="inline-flex items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-white hover:bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 transition"
                                            >
                                                <svg
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    class="w-5 h-5"
                                                    fill="none"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                    stroke-width="2"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                    />
                                                </svg>

                                                <span id="copyAdminText">Salin Nomor</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $('#daftarForm').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#btnSubmit');
        const $spinner = $('#spinner');
        const $btnText = $('#btnText');

        $btn.prop('disabled', true);
        $spinner.removeClass('hidden');
        $btnText.addClass('opacity-50');

        $.ajax({
            url: '{{ route("donor-darah.simpan-pendaftaran.storeNew") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.redirect;
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan. Silakan coba lagi.';
                if (xhr.status === 422) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal',
                    html: message,
                    confirmButtonColor: '#059669'
                });
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.addClass('hidden');
                $btnText.removeClass('opacity-50');
            }
        });
    });
    // ==================== COPY NOMOR ADMIN ====================
    $('#btnCopyAdmin').on('click', async function() {
        const $button = $(this);
        const $text = $('#copyAdminText');
        const number = $button.data('number');

        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(number);
            } else {
                const temporaryInput = document.createElement('textarea');

                temporaryInput.value = number;
                temporaryInput.style.position = 'fixed';
                temporaryInput.style.opacity = '0';

                document.body.appendChild(temporaryInput);
                temporaryInput.focus();
                temporaryInput.select();

                document.execCommand('copy');
                document.body.removeChild(temporaryInput);
            }

            $text.text('Nomor Tersalin');

            Swal.fire({
                icon: 'success',
                title: 'Nomor berhasil disalin',
                text: number,
                showConfirmButton: false,
                timer: 1600,
                toast: true,
                position: 'top-end'
            });

            setTimeout(function() {
                $text.text('Salin Nomor');
            }, 2000);
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'Tidak dapat menyalin nomor',
                text: 'Silakan salin nomor secara manual.',
                confirmButtonColor: '#059669'
            });
        }
    });
});
</script>
@endpush
