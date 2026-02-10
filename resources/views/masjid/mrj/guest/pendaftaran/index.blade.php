@extends('masjid.master-guest')

@section('title', 'Pendaftaran Santunan Anak Yatim & Dhuafa Ramadhan 1447 H / 2026')

@section('content')
<section class="py-16 bg-gradient-to-br from-slate-50 to-white">
    <div class="container mx-auto px-4 lg:px-6">
        <div class="max-w-3xl mx-auto">
            <!-- Card Utama -->
            <div class="bg-white rounded-2xl border border-emerald-100/60 shadow-lg overflow-hidden">
                <div class="p-4 lg:p-8">
                    <!-- HEADER UTAMA -->
                    <div class="relative overflow-hidden mb-12">
                        <!-- Background lembut -->
                        <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-white to-teal-50"></div>

                        <div class="relative max-w-9xl mx-auto px-4 sm:px-2 lg:px-4 py-10 text-center">

                            <!-- Logo Center -->
                            <div class="flex items-center justify-center gap-3 sm:gap-4 mb-6">
                                <img src="{{ asset('mrj-mtrj.png') }}"
                                     alt="Logo MRJ"
                                     class="h-16 sm:h-20 md:h-28 drop-shadow-md">
                            </div>

                            <!-- Card Konten -->
                            <div class="max-w-4xl mx-auto bg-white/80 backdrop-blur-md rounded-3xl border border-emerald-100 shadow-xl p-6 sm:p-8 md:p-10">

                                <h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-emerald-800 leading-snug mb-5">
                                    Pendataan Santunan Anak Yatim & Anak Dhuafa  
                                    <span class="block text-lg sm:text-xl md:text-2xl font-semibold text-emerald-600 mt-1">
                                        MRJ & MTRJ — Ramadhan 1447 H / 2026
                                    </span>
                                </h1>

                                <p class="text-base sm:text-lg text-slate-700 leading-relaxed mb-6">
                                    Formulir ini digunakan untuk pendataan <strong>Anak Yatim yang Dhuafa</strong> dan
                                    <strong>Anak Dhuafa</strong> dalam rangka Program Santunan Ramadhan.
                                </p>

                                <!-- Kriteria -->
                                <div class="bg-emerald-50/80 rounded-2xl border border-emerald-100 p-5 text-left mb-6">
                                    <p class="font-semibold text-emerald-800 mb-3">Kriteria Penerima:</p>
                                    <ul class="space-y-2 text-slate-700">
                                        <li class="flex items-center gap-2">
                                            <span class="text-emerald-600">✔</span>
                                            Anak Yatim yang Dhuafa
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="text-emerald-600">✔</span>
                                            Anak Dhuafa
                                        </li>
                                        <li class="flex items-center gap-2">
                                            <span class="text-emerald-600">✔</span>
                                            Usia maksimal <strong>13 tahun</strong>
                                        </li>
                                    </ul>
                                </div>
                                <!-- Penutup -->
                                <div class="text-slate-700 italic text-sm sm:text-base text-center space-y-3">
                                    <p class="font-semibold text-emerald-700">
                                        Program Santunan Ramadhan MRJ &amp; MTRJ<br>
                                        Ramadhan 1447 H / 2026
                                    </p>

                                    <p class="text-slate-600">
                                        Masjid Raudhotul Jannah &amp; Majelis Ta’lim Raudhotul Jannah
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>
                    <!-- Form -->
                    <form id="form-pendaftaran" class="space-y-7">

                        <!-- Sumber Informasi -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Penanggung Jawab Informasi <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <input type="text" name="sumber_informasi" required
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="Dari mana Anda tahu program ini? (misal: grup WA tetangga, pengumuman masjid, IG, dll)"/>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🔍</span>
                            </div>
                        </div>

                        <!-- Nomor WA -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Nomor WA (opsional, untuk konfirmasi)</span>
                            </label>
                            <div class="relative">
                                <input type="tel" name="no_wa"
                                       class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                       placeholder="08xxxxxxxxxx" pattern="^08[0-9]{8,13}$"/>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📱</span>
                            </div>
                        </div>

                        <!-- Kategori -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Kategori Penerima <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <select name="kategori" required
                                        class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white appearance-none">
                                    <option value="" disabled selected>Pilih salah satu</option>
                                    <option value="yatim_dhuafa">Anak Yatim yang Dhuafa</option>
                                    <option value="dhuafa">Anak Dhuafa</option>
                                </select>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👶</span>
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                            </div>
                        </div>

                        <!-- Nama Lengkap & Panggilan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Lengkap Anak <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="nama_lengkap" required
                                           class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                           placeholder="Nama lengkap anak"/>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👤</span>
                                </div>
                            </div>
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Panggilan</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="nama_panggilan"
                                           class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                           placeholder="Nama sehari-hari (misal: Adit)"/>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">😊</span>
                                </div>
                            </div>
                        </div>

                        <!-- Jenis Kelamin -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Jenis Kelamin <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <select name="jenis_kelamin" required
                                        class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white appearance-none">
                                    <option value="" disabled selected>Pilih jenis kelamin</option>
                                    <option value="L">Laki-laki</option>
                                    <option value="P">Perempuan</option>
                                </select>
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">⚥</span>
                                <span class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">▼</span>
                            </div>
                        </div>

                        <!-- Tanggal Lahir & Umur -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Tanggal Lahir <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <input type="date" name="tanggal_lahir" id="tanggal_lahir"
                                           class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none text-slate-900 bg-white"/>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">📅</span>
                                </div>
                            </div>
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Umur Saat Ini <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <input type="number" name="umur" id="umur" min="0" max="13" required
                                           class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                           placeholder="Akan otomatis ter-update"/>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">🎂</span>
                                </div>
                                <label class="label">
                                    <span class="label-text-alt text-sm text-slate-500 italic" id="umurHelper">
                                        Akan otomatis ter-update setiap tanggal lahir diubah
                                    </span>
                                </label>
                            </div>
                        </div>


                        <!-- Nama Orang Tua & Pekerjaan -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Nama Orang Tua / Wali <span class="text-red-500">*</span></span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="nama_orang_tua" required
                                           class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                           placeholder="Nama ayah / ibu / wali"/>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">👨‍👩‍👧</span>
                                </div>
                            </div>
                            <div class="form-control">
                                <label class="label pb-1">
                                    <span class="label-text font-semibold text-slate-800">Pekerjaan Orang Tua / Wali</span>
                                </label>
                                <div class="relative">
                                    <input type="text" name="pekerjaan_orang_tua"
                                           class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white"
                                           placeholder="Contoh: Buruh, Ibu Rumah Tangga, Wiraswasta"/>
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-emerald-600 text-xl pointer-events-none">💼</span>
                                </div>
                            </div>
                        </div>

                        <!-- Alamat -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Alamat Lengkap <span class="text-red-500">*</span></span>
                            </label>
                            <div class="relative">
                                <textarea name="alamat" rows="3" required
                                          class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                          placeholder="Contoh: Jl. Merdeka No. 45, Kel. Sukamaju, Kec. Cibeureum, Kota Tasikmalaya"></textarea>
                                <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">🏠</span>
                            </div>
                        </div>

                        <!-- Catatan Tambahan -->
                        <div class="form-control">
                            <label class="label pb-1">
                                <span class="label-text font-semibold text-slate-800">Keterangan Tambahan</span>
                            </label>
                            <div class="relative">
                                <textarea name="catatan_tambahan" rows="4"
                                          class="w-full px-12 py-3.5 rounded-xl border-2 border-slate-300 focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 transition-all duration-300 outline-none placeholder-slate-400 text-slate-900 bg-white resize-none"
                                          placeholder="Misal: Kondisi kesehatan anak, kebutuhan khusus, atau informasi lain yang perlu diketahui panitia..."></textarea>
                                <span class="absolute left-4 top-4 text-emerald-600 text-xl pointer-events-none">📝</span>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="pt-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <button type="submit" id="btn-submit"
                                    class="px-10 py-4 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold rounded-full shadow-lg hover:shadow-xl transition transform hover:-translate-y-1 text-base w-full sm:w-auto">
                                Kirim Pendaftaran
                            </button>
                            <div id="formStatus" class="text-sm text-slate-600"></div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Bawah -->
            <div class="text-center mt-8 text-slate-600 text-sm">
                Terima kasih atas kepercayaan Anda.<br>
                Data akan diverifikasi oleh panitia. Semoga Ramadhan penuh berkah!
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function () {

    // Fungsi hitung umur
    function hitungUmur() {
        const tglLahirStr = $('#tanggal_lahir').val();
        const umurInput = $('#umur');
        const helper = $('#umurHelper');

        if (!tglLahirStr) {
            // JANGAN hapus umur manual
            const umurManual = umurInput.val();

            if (umurManual !== '') {
                helper.text('Umur diisi manual (tanggal lahir tidak digunakan)');
            } else {
                helper.text('Akan otomatis ter-update setiap tanggal lahir diubah');
            }
            return;
        }

        const tglLahir = new Date(tglLahirStr);
        if (isNaN(tglLahir.getTime())) {
            umurInput.val('');
            helper.text('Tanggal lahir tidak valid');
            return;
        }

        const today = new Date();
        let umur = today.getFullYear() - tglLahir.getFullYear();
        const bulan = today.getMonth() - tglLahir.getMonth();

        if (bulan < 0 || (bulan === 0 && today.getDate() < tglLahir.getDate())) {
            umur--;
        }

        console.log(`Tanggal lahir: ${tglLahirStr} → Umur dihitung: ${umur}`);

        if (umur < 0) {
            umurInput.val('');
            helper.text('Tanggal lahir di masa depan → umur tidak bisa negatif');
            return;
        }

        // Selalu update umur (overwrite manual sekalipun)
        umurInput.val(umur);
        helper.text(`Umur otomatis: ${umur} tahun (berdasarkan tanggal lahir)`);
    }

    // Event super responsif
    $('#tanggal_lahir').on('change input blur keyup', hitungUmur);

    // Trigger awal
    hitungUmur();

    $('#umur').on('input', function () {
        const umurVal = $(this).val();
        const tglInput = $('#tanggal_lahir');
        const helper = $('#umurHelper');

        // hanya jika user benar-benar mengisi umur
        if (umurVal !== '') {
            tglInput.val(''); // kosongkan tanggal lahir
            helper.text('Umur diisi manual (tanggal lahir tidak digunakan)');
        }
    });

    // Reset form → reset umur & helper
    $('#form-pendaftaran').on('reset', function() {
        setTimeout(() => {
            hitungUmur();
        }, 100);
    });

    // Submit AJAX
    $('#form-pendaftaran').on('submit', function(e) {
        e.preventDefault();

        const $btn = $('#btn-submit');
        const originalText = $btn.text();

        $btn.prop('disabled', true)
            .html('<span class="loading loading-spinner loading-md mr-2"></span> Mengirim...')
            .addClass('opacity-80 cursor-not-allowed');

        $.ajax({
            url: '{{ route("santunan-ramadhan.submit") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Alhamdulillah!',
                        text: res.message,
                        confirmButtonColor: '#059669',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#form-pendaftaran')[0].reset();
                        hitungUmur(); // reset juga hitung ulang
                    });
                }
            },
            error: function(xhr) {
                let msg = 'Terjadi kesalahan. Silakan coba lagi.';
                if (xhr.status === 422) {
                    msg = Object.values(xhr.responseJSON.errors || {}).flat().join('<br>');
                } else if (xhr.responseJSON?.message) {
                    msg = xhr.responseJSON.message;
                }
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal Mengirim',
                    html: msg,
                    confirmButtonColor: '#dc2626'
                });
            },
            complete: function() {
                $btn.prop('disabled', false)
                    .text(originalText)
                    .removeClass('opacity-80 cursor-not-allowed');
            }
        });
    });

    // Hapus highlight error
    $('#form-pendaftaran').on('input change', 'input, select, textarea', function() {
        $(this).removeClass('border-red-500');
    });
});
</script>
@endpush