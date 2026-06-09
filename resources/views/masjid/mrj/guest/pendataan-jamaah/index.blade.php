@extends('masjid.master-guest')

@push('head')
    {{-- BASIC SEO --}}
    <title>Pendataan Jamaah Masjid | Masjid Raudhotul Jannah TCE</title>
    <meta name="description" content="Isi data diri Anda untuk menjadi jamaah terdaftar Masjid Raudhotul Jannah TCE. Dapatkan informasi kegiatan masjid, kajian rutin, dan program sosial melalui WhatsApp.">
    <meta name="keywords" content="pendataan jamaah, form jamaah, masjid, pendaftaran jamaah, kegiatan masjid, kajian rutin, TPA, TPQ, zakat, sedekah, qurban">
    <meta name="author" content="Masjid Raudhotul Jannah TCE">
    <meta name="robots" content="index, follow">

    {{-- OPEN GRAPH (WhatsApp, Facebook, LinkedIn) --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pendataan Jamaah Masjid | Masjid Raudhotul Jannah TCE">
    <meta property="og:description" content="Isi data diri Anda untuk menjadi jamaah terdaftar. Dapatkan informasi kajian rutin, TPA/TPQ, kegiatan sosial, zakat, sedekah, dan qurban langsung via WhatsApp.">
    <meta property="og:image" content="{{ secure_url('images/og-image-masjid.jpg') }}">
    <meta property="og:image:alt" content="Masjid Raudhotul Jannah TCE - Pendataan Jamaah">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Masjid Raudhotul Jannah TCE">
    <meta property="og:locale" content="id_ID">

    {{-- Fix Image Dimension untuk WA & FB --}}
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- TWITTER CARD --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pendataan Jamaah Masjid | Masjid Raudhotul Jannah TCE">
    <meta name="twitter:description" content="Daftar jadi jamaah masjid. Dapatkan info kajian rutin, TPA/TPQ, kegiatan sosial, zakat, sedekah, dan qurban via WhatsApp.">
    <meta name="twitter:image" content="{{ secure_url('images/og-image-masjid.jpg') }}">
    <meta name="twitter:image:alt" content="Masjid Raudhotul Jannah TCE">

    {{-- SCHEMA.ORG untuk Organization & WebPage --}}
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebPage",
        "name": "Pendataan Jamaah Masjid",
        "description": "Formulir pendataan jamaah Masjid Raudhotul Jannah TCE",
        "publisher": {
            "@type": "Organization",
            "name": "Masjid Raudhotul Jannah TCE",
            "logo": {
                "@type": "ImageObject",
                "url": "{{ secure_url('images/logo-masjid.png') }}"
            },
            "url": "{{ url('/') }}"
        },
        "url": "{{ url()->current() }}",
        "mainEntity": {
            "@type": "WebApplication",
            "name": "Form Pendataan Jamaah",
            "description": "Formulir online untuk pendataan jamaah masjid",
            "applicationCategory": "Religious",
            "operatingSystem": "All"
        }
    }
    </script>

    {{-- CANONICAL URL --}}
    <link rel="canonical" href="{{ url()->current() }}">
    
    {{-- FAVICON (opsional) --}}
    <link rel="icon" type="image/png" href="{{ secure_url('favicon.png') }}">
@endpush

@section('content')
<div class="min-h-screen py-12" style="background: linear-gradient(135deg, #ecfdf5 0%, #f0fdfa 50%, #e0f2fe 100%);">
    
    <div class="relative z-10 max-w-3xl mx-auto px-4 sm:px-6">
        <!-- Header -->
        <div class="text-center mb-10">
            <h1 class="text-3xl sm:text-4xl font-bold mb-3 text-slate-800">Pendataan Jamaah Masjid</h1>
            <p class="text-slate-600 max-w-md mx-auto">
                Data ini digunakan untuk pendataan jamaah dan penyampaian informasi kegiatan masjid. 
                Data tidak akan dibagikan kepada pihak lain.
            </p>
            <div class="mt-3 inline-flex items-center gap-1 text-sm text-emerald-600 bg-emerald-50 px-4 py-2 rounded-full">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                ⏱️ Waktu pengisian kurang dari 1 menit
            </div>
        </div>

        <!-- Form Card -->
        <div class="bg-white rounded-3xl shadow-xl border border-emerald-100 p-6 sm:p-8">
            <form id="formPendataanJamaah">
                @csrf
                
                <!-- Data Jamaah -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold border-b border-emerald-200 pb-3 mb-6 flex items-center gap-2 text-slate-800">
                        <span class="text-emerald-600">🕌</span> Data Jamaah
                    </h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2 text-slate-700">
                                Nama Lengkap <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="nama_lengkap" id="nama_lengkap" required
                                   class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition">
                            <div class="text-red-500 text-xs mt-1 error-nama_lengkap hidden"></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2 text-slate-700">
                                Nomor WhatsApp <span class="text-red-500">*</span>
                            </label>
                            <input type="tel" name="nomor_whatsapp" id="nomor_whatsapp" required
                                   placeholder="08xxxxxxxxxx"
                                   class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition">
                            <div class="text-red-500 text-xs mt-1 error-nomor_whatsapp hidden"></div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2 text-slate-700">Jenis Kelamin</label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="jenis_kelamin" value="L" class="w-4 h-4 accent-emerald-600">
                                    <span class="text-slate-700">Laki-laki</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="radio" name="jenis_kelamin" value="P" class="w-4 h-4 accent-emerald-600">
                                    <span class="text-slate-700">Perempuan</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2 text-slate-700">Tanggal Lahir</label>
                            <input type="date" name="tanggal_lahir" id="tanggal_lahir"
                                   class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 outline-none transition">
                            <div class="text-red-500 text-xs mt-1 error-tanggal_lahir hidden"></div>
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium mb-2 text-slate-700">
                                RT/RW atau Alamat Singkat
                            </label>
                            <div class="grid grid-cols-3 gap-3">
                                <input type="text" name="rt" placeholder="RT"
                                       class="px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition">
                                <input type="text" name="rw" placeholder="RW"
                                       class="px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition">
                                <input type="text" name="alamat_singkat" placeholder="Alamat (Jalan/Dusun)"
                                       class="px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Keterlibatan Jamaah -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold border-b border-emerald-200 pb-3 mb-6 flex items-center gap-2 text-slate-800">
                        <span class="text-emerald-600">🤝</span> Keterlibatan Jamaah
                    </h2>
                    
                    <div class="mb-6">
                        <label class="block text-sm font-medium mb-3 text-slate-700">
                            Bersedia menerima informasi kegiatan melalui WhatsApp?
                        </label>
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="bersedia_info_wa" value="1" class="w-4 h-4 accent-emerald-600" checked>
                                <span class="text-slate-700">Ya</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="bersedia_info_wa" value="0" class="w-4 h-4 accent-emerald-600">
                                <span class="text-slate-700">Tidak</span>
                            </label>
                        </div>
                    </div>

                    <label class="block text-sm font-medium mb-3 text-slate-700">
                        Minat Kegiatan Masjid (boleh pilih lebih dari satu)
                    </label>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_kajian_rutin" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Kajian Rutin</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_tpa_tpq" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">TPA/TPQ</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_remaja_masjid" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Remaja Masjid</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_kegiatan_sosial" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Kegiatan Sosial</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_zakat_sedekah" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Zakat & Sedekah</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_qurban" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Qurban</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="minat_kerelawanan" value="1" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Kerelawanan</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer col-span-2 sm:col-span-1">
                            <input type="checkbox" name="minat_lainnya" value="1" id="minat_lainnya_checkbox" class="w-4 h-4 rounded accent-emerald-600">
                            <span class="text-slate-700">Lainnya</span>
                        </label>
                    </div>
                    
                    <div id="minat_lainnya_container" class="mt-3 hidden">
                        <input type="text" name="minat_lainnya_text" placeholder="Sebutkan kegiatan lainnya..."
                               class="w-full px-4 py-2 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition">
                    </div>
                </div>

                <!-- Aspirasi -->
                <div class="mb-8">
                    <h2 class="text-xl font-semibold border-b border-emerald-200 pb-3 mb-6 flex items-center gap-2 text-slate-800">
                        <span class="text-emerald-600">💭</span> Aspirasi Jamaah
                    </h2>
                    <textarea name="aspirasi" rows="4" 
                              placeholder="Saran, Masukan, atau Harapan untuk Masjid (Opsional)"
                              class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/50 text-slate-800 placeholder-gray-400 outline-none transition resize-none"></textarea>
                </div>

                <!-- Submit Button -->
                <div class="text-center pt-4">
                    <button type="submit" id="submitBtn" class="group relative inline-flex items-center justify-center gap-2 px-8 py-4 text-lg font-bold rounded-full bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white shadow-lg shadow-emerald-500/30 transition-all hover:scale-105 active:scale-95 w-full sm:w-auto">
                        <svg id="spinner" class="hidden w-5 h-5 animate-spin text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span id="btnText">📝 Kirim Pendataan</span>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Footer -->
        <div class="text-center mt-8 text-emerald-700/60 text-sm">
            <p>© {{ date('Y') }} Masjid Raudhotul Jannah TCE - Layanan Pendataan Jamaah</p>
        </div>
    </div>
</div>
@endsection

@push('style')
<style>
    .btn-loading {
        pointer-events: none;
        opacity: 0.7;
    }
    input:focus, textarea:focus, select:focus {
        outline: none;
    }
    input[type="radio"], input[type="checkbox"] {
        cursor: pointer;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Toggle "Lainnya" input
    const checkboxLainnya = document.getElementById('minat_lainnya_checkbox');
    const lainnyaContainer = document.getElementById('minat_lainnya_container');
    
    if (checkboxLainnya && lainnyaContainer) {
        checkboxLainnya.addEventListener('change', function() {
            if (this.checked) {
                lainnyaContainer.classList.remove('hidden');
            } else {
                lainnyaContainer.classList.add('hidden');
                document.querySelector('input[name="minat_lainnya_text"]').value = '';
            }
        });
    }
    
    // Form submission with SweetAlert2
    $('#formPendataanJamaah').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        $('[class*="error-"]').addClass('hidden').text('');
        
        const $btn = $('#submitBtn');
        const $spinner = $('#spinner');
        const $btnText = $('#btnText');
        
        // Show loading state
        $btn.prop('disabled', true);
        $spinner.removeClass('hidden');
        $btnText.addClass('opacity-50');
        
        // Collect form data
        const formData = new FormData(this);
        
        $.ajax({
            url: '{{ route("jamaah.store") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Reset form
                    $('#formPendataanJamaah')[0].reset();
                    if (checkboxLainnya) checkboxLainnya.checked = false;
                    if (lainnyaContainer) lainnyaContainer.classList.add('hidden');
                    
                    // Show success SweetAlert
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        html: response.message,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'OK',
                        timer: 3000,
                        timerProgressBar: true
                    });
                }
            },
            error: function(xhr) {
                let message = 'Terjadi kesalahan. Silakan coba lagi.';
                
                if (xhr.status === 422) {
                    // Validation errors
                    const errors = xhr.responseJSON.errors;
                    let errorHtml = '<ul class="text-left">';
                    
                    for (const [field, errorMessages] of Object.entries(errors)) {
                        errorMessages.forEach(msg => {
                            errorHtml += `<li>${msg}</li>`;
                            // Show error under field
                            $(`.error-${field}`).removeClass('hidden').text(msg);
                        });
                    }
                    errorHtml += '</ul>';
                    message = errorHtml;
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    html: message,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false);
                $spinner.addClass('hidden');
                $btnText.removeClass('opacity-50');
            }
        });
    });
});
</script>
@endpush