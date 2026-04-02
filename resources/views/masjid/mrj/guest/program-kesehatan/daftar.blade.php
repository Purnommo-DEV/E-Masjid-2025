@extends('masjid.master-guest')
@push('head')

    {{-- BASIC SEO --}}
    <title>Pendaftaran Program Kesehatan | Masjid Raudhotul Jannah</title>
    <meta name="description" content="Daftarkan diri Anda untuk program kesehatan: donor darah, cek kesehatan, dan cek mata katarak di Masjid Raudhotul Jannah TCE.">

    {{-- OPEN GRAPH (WA & FB) --}}
    <meta property="og:type" content="website">
    <meta property="og:title" content="Pendaftaran Program Kesehatan Masjid Raudhotul Jannah">
    <meta property="og:description" content="Program kesehatan gratis: donor darah, cek gula darah, kolesterol, asam urat, dan cek mata katarak.">
    <meta property="og:image" content="{{ secure_url('images/default-ramadhan.jpg') }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:site_name" content="Masjid Raudhotul Jannah">
    <meta property="og:locale" content="id_ID">

    {{-- FIX IMAGE WA --}}
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- TWITTER --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Pendaftaran Program Kesehatan Masjid Raudhotul Jannah">
    <meta name="twitter:description" content="Daftar donor darah, cek kesehatan, dan cek mata katarak di Masjid Raudhotul Jannah.">
    <meta name="twitter:image" content="{{ secure_url('images/default-ramadhan.jpg') }}">

@endpush
@section('content')
<section class="py-16 bg-gradient-to-br from-slate-50 to-white">
    <div class="container mx-auto px-4 lg:px-6">
        <div class="max-w-2xl mx-auto">

            <!-- Card Utama -->
            <div class="bg-white rounded-3xl border border-emerald-100 shadow-xl overflow-hidden">

                <!-- Header -->
                <div class="bg-emerald-700 py-10 px-6 text-center">
                    <img src="{{ asset('mrj-mtrj.png') }}" 
                         alt="Logo Masjid Raudhotul Jannah" 
                         class="h-20 mx-auto drop-shadow-lg mb-6">

                    <h2 class="text-3xl font-bold text-white font-arabic mb-2">
                        بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ
                    </h2>
                    <p class="text-emerald-100 text-xl">
                        Program Kesehatan Rutin
                    </p>
                    <p class="text-emerald-200 mt-1">
                        Masjid Raudhotul Jannah TCE
                    </p>
                </div>

                <div class="p-8 lg:p-10">

                    <!-- Pengantar -->
                    <div class="bg-emerald-50 border border-emerald-100 rounded-2xl p-6 mb-8 text-center">
                        <p class="text-emerald-800 leading-relaxed">
                            Assalamu’alaikum wr. wb.<br><br>
                            Silakan isi form di bawah ini dengan teliti.<br>
                            <strong>Anda boleh memilih satu atau lebih program</strong> sesuai kebutuhan kesehatan Anda.
                        </p>
                    </div>

                    <h1 class="text-2xl lg:text-3xl font-bold text-emerald-800 text-center mb-10">
                        Form Pendaftaran Program Kesehatan
                    </h1>

                    <form id="daftarForm" class="space-y-8">
                        @csrf
                        <input type="hidden" name="event_date" value="{{ $eventDate }}">

                        <!-- Data Diri -->
                        <div class="space-y-6">
                            <div>
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Lengkap <span class="text-red-500">*</span></span>
                                </label>
                                <input type="text" name="nama_lengkap" required
                                       class="w-full px-6 py-4 rounded-2xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none text-slate-900"
                                       placeholder="Contoh: Bapak Ahmad Santoso">
                            </div>

                            <div>
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nomor HP / WhatsApp <span class="text-red-500">*</span></span>
                                </label>
                                <input type="tel" name="no_hp" required
                                       class="w-full px-6 py-4 rounded-2xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none text-slate-900"
                                       placeholder="0812 3456 7890">
                            </div>

                            <div>
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Alamat Lengkap (Opsional)</span>
                                </label>
                                <textarea name="alamat" rows="3"
                                          class="w-full px-6 py-4 rounded-2xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all outline-none text-slate-900 resize-y"
                                          placeholder="Contoh: TCE Blok B1 No. 10 atau Kav. Deplu 289"></textarea>
                            </div>
                        </div>

                        <!-- Pilih Program -->
                        <div>
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800 text-lg">Pilih Program yang Diikuti:</span>
                            </label>

                            <div class="space-y-6 mt-4">

                                <!-- Donor Darah -->
                                <label class="flex gap-4 p-5 border-2 border-emerald-100 hover:border-emerald-300 rounded-2xl cursor-pointer transition-all">
                                    <input type="checkbox" name="donor_darah" class="w-6 h-6 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-lg text-emerald-800">Donor Darah</div>
                                        <p class="text-slate-600 text-sm mt-1">
                                            Mendonorkan darah untuk menyelamatkan nyawa sesama.<br>
                                            <span class="text-xs text-emerald-600">(Minimal usia 18 tahun)</span>
                                        </p>
                                    </div>
                                </label>

                                <!-- Cek Kesehatan -->
                                <div class="p-5 border-2 border-emerald-100 hover:border-emerald-300 rounded-2xl transition-all">
                                    <div class="font-semibold text-lg text-emerald-800 mb-4">Cek Kesehatan</div>
                                    <div class="grid grid-cols-1 gap-4 pl-4">
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="cek_kesehatan[]" value="gula_darah" class="w-5 h-5 accent-emerald-600">
                                            <span class="text-slate-700">Gula Darah</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="cek_kesehatan[]" value="kolesterol" class="w-5 h-5 accent-emerald-600">
                                            <span class="text-slate-700">Kolesterol</span>
                                        </label>
                                        <label class="flex items-center gap-3 cursor-pointer">
                                            <input type="checkbox" name="cek_kesehatan[]" value="asam_urat" class="w-5 h-5 accent-emerald-600">
                                            <span class="text-slate-700">Asam Urat</span>
                                        </label>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-4 pl-4">
                                        ✅ Anda boleh memilih satu, dua, atau ketiganya
                                    </p>
                                </div>

                                <!-- Cek Mata Katarak -->
                                <label class="flex gap-4 p-5 border-2 border-emerald-100 hover:border-emerald-300 rounded-2xl cursor-pointer transition-all">
                                    <input type="checkbox" name="cek_mata_katarak" class="w-6 h-6 accent-emerald-600 mt-1">
                                    <div class="flex-1">
                                        <div class="font-semibold text-lg text-emerald-800">Cek Mata Katarak</div>
                                        <p class="text-slate-600 text-sm mt-1">
                                            Pemeriksaan mata gratis untuk deteksi dini katarak.
                                        </p>
                                    </div>
                                </label>

                            </div>
                        </div>

                        <!-- Submit Button dengan Loader -->
                        <div class="pt-6">
                            <button type="submit" id="btnSubmit"
                                    class="w-full py-6 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 
                                           text-white font-bold text-lg rounded-2xl shadow-lg transition-all duration-300 flex items-center justify-center gap-3">
                                <span id="btnText">KIRIM PENDAFTARAN</span>
                                <span id="spinner" class="hidden animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                            </button>
                        </div>
                    </form>
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

        // Tampilkan loader
        $btn.prop('disabled', true);
        $spinner.removeClass('hidden');
        $btnText.addClass('opacity-50');

        $.ajax({
            url: '{{ route("kesehatan.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Redirect ke halaman success dengan nama di URL
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
});
</script>
@endpush