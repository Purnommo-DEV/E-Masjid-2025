@extends('masjid.master-guest')

@push('head')
    <title>Saran & Feedback - Program Kesehatan | Masjid Raudhotul Jannah TCE</title>
@endpush

@section('content')
<section class="py-16 bg-gradient-to-br from-slate-50 to-white">
    <div class="container mx-auto px-4 lg:px-6">
        <div class="max-w-xl mx-auto">
            
            <div class="bg-white rounded-3xl border border-emerald-100 shadow-xl overflow-hidden">
                
                <!-- Header -->
                <div class="bg-emerald-700 py-10 px-6 text-center">
                    <img src="{{ asset('mrj.png') }}" 
                         alt="Logo Masjid Raudhotul Jannah"
                         class="h-20 mx-auto drop-shadow-lg mb-5">
                    
                    <h2 class="text-2xl font-bold text-white font-arabic mb-2">
                        بِسْمِ اللّٰهِ الرَّحْمٰنِ الرَّحِيْمِ
                    </h2>
                    <p class="text-emerald-100 text-lg">Saran & Feedback</p>
                    <p class="text-emerald-200 text-sm mt-1">Program Kesehatan Masjid Raudhotul Jannah TCE</p>
                </div>

                <div class="p-8 lg:p-10  text-slate-800">
                    
                    <div class="text-center mb-8">
                        <p class="text-emerald-700">
                            Terima kasih telah mengikuti program kesehatan kami.<br>
                            Masukan Anda sangat berharga untuk perbaikan ke depannya.
                        </p>
                    </div>

                    <form id="feedbackForm" class="space-y-8">
                        @csrf

                        <!-- Nama (Opsional) -->
                        <div>
                            <label class="block pb-2">
                                <span class="font-semibold text-slate-700 text-sm">Nama (Opsional)</span>
                            </label>
                            <input type="text" name="nama" 
                                   class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-sm"
                                   placeholder="Contoh: Ahmad Santoso">
                        </div>

                        <!-- Saran / Komentar -->
                        <div>
                            <label class="block pb-2">
                                <span class="font-semibold text-slate-700 text-sm">Saran atau Komentar Anda</span>
                            </label>
                            <textarea name="saran" rows="6" required
                                      class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none text-sm resize-y"
                                      placeholder="Apa yang baik dari program ini? Apa yang perlu diperbaiki? Saran untuk acara selanjutnya?"></textarea>
                        </div>

                        <!-- Submit -->
                        <div class="pt-4">
                            <button type="submit" id="btnSubmit"
                                class="w-full py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700
                                       text-white font-semibold text-base rounded-xl shadow-md transition flex items-center justify-center gap-2">
                                <span id="btnText">Kirim Saran</span>
                                <span id="spinner" class="hidden animate-spin h-5 w-5 border-2 border-white border-t-transparent rounded-full"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-8 text-slate-500 text-sm">
                Terima kasih atas waktu dan masukan Anda 🙏
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
    $('#feedbackForm').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#btnSubmit');
        const $spinner = $('#spinner');
        const $btnText = $('#btnText');

        $btn.prop('disabled', true);
        $spinner.removeClass('hidden');
        $btnText.text('Mengirim...');

        $.ajax({
            url: '{{ route("donor-darah.feedback.store") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Terima Kasih!',
                    text: 'Saran Anda telah kami terima.',
                    confirmButtonColor: '#059669'
                }).then(() => {
                    window.location.href = '{{ route("berita.show", "kegiatan-donor-darah-di-masjid-raudhotul-jannah-tce-sebagai-upaya-kepedulian-sosial-dan-kesehatan") }}';
                });
            },
            error: function() {
                Swal.fire('Gagal', 'Terjadi kesalahan. Silakan coba lagi.', 'error');
            },
            complete: function() {
                $btn.prop('disabled', false);
                $spinner.addClass('hidden');
                $btnText.text('Kirim Saran');
            }
        });
    });
});
</script>
@endpush