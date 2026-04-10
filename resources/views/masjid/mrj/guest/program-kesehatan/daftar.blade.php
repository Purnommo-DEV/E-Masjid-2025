@extends('masjid.master-guest')

@push('head')
    <title>Pendaftaran Program Kesehatan | Masjid Raudhotul Jannah TCE</title>
    <meta name="description" content="Daftarkan diri Anda untuk Donor Darah dan Cek Gula Darah di Masjid Raudhotul Jannah TCE.">
@endpush

@section('content')
<section class="py-14 bg-gradient-to-br from-slate-50 to-white">
    <div class="container mx-auto px-4 lg:px-6">
        <div class="max-w-xl mx-auto">
          
            <!-- Card Utama -->
            <div class="bg-white rounded-3xl border border-emerald-100 shadow-xl overflow-hidden">

                <!-- Header -->
                <div class="bg-emerald-700 py-10 md:py-12 px-6 text-center">
                    <img src="{{ asset('mrj.png') }}"
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
                                Silakan lengkapi form di bawah ini dengan data yang benar.
                            </p>
                        </div>

                        <!-- Kerjasama -->
                        <div class="mb-6">
                            <p class="text-center text-emerald-700 font-medium text-sm mb-4">
                                Bekerja sama dengan
                            </p>
                            
                            <div class="grid grid-cols-1 gap-3">
                                
                                <div class="flex items-center gap-3 bg-white border border-emerald-200 rounded-xl p-3">
                                    <div class="w-10 h-10 bg-red-100 text-red-600 rounded-lg flex items-center justify-center text-xl">
                                        🩸
                                    </div>
                                    <div class="text-sm">
                                        <div class="font-semibold text-emerald-900">Donor Darah</div>
                                        <div class="text-slate-500 text-xs">RS Kanker Dharmais</div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 bg-white border border-emerald-200 rounded-xl p-3">
                                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center text-xl">
                                        🧪
                                    </div>
                                    <div class="text-sm">
                                        <div class="font-semibold text-emerald-900">Cek Gula Darah</div>
                                        <div class="text-slate-500 text-xs">RS Hermina Ciledug</div>
                                    </div>
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
                                <span class="font-semibold text-slate-700 text-sm">Nomor HP / WhatsApp (Mohon diisi)</span>
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
                            <p class="text-xs text-emerald-600 mb-3">
                                Anda dapat memilih satu atau kedua program sesuai kebutuhan Anda.
                            </p>                        

                            <div class="space-y-3 mt-2">

                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition">
                                    <input type="checkbox" name="donor_darah" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div>
                                        <div class="font-semibold text-sm text-emerald-800">Donor Darah</div>
                                        <p class="text-slate-500 text-xs mt-1">Mendonorkan darah untuk sesama.</p>
                                    </div>
                                </label>

                                <label class="flex gap-3 p-4 border border-emerald-100 hover:border-emerald-400 rounded-xl cursor-pointer transition">
                                    <input type="checkbox" name="cek_kesehatan[]" value="gula_darah" class="w-5 h-5 accent-emerald-600 mt-1">
                                    <div>
                                        <div class="font-semibold text-sm text-emerald-800">Pemeriksaan Gula Darah</div>
                                        <p class="text-slate-500 text-xs mt-1">Pemeriksaan gula darah gratis.</p>
                                    </div>
                                </label>

                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="pt-4">
                            <button type="submit" id="btnSubmit"
                                class="w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 
                                       text-white font-semibold text-sm md:text-base rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span id="btnText">Daftar Sekarang</span>
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
});
</script>
@endpush