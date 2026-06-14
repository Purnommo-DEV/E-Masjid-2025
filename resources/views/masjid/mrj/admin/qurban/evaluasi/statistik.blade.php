@extends('masjid.master')
@section('title', 'Statistik Evaluasi Qurban')

@section('content')
@push('style')
<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #059669;
    }
    .progress-bar {
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: #059669;
        border-radius: 4px;
    }
    .card-wrapper {
        max-width: 1400px;
        margin: 1.25rem auto;
        border-radius: 1rem;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(2,6,23,0.06);
        background: white;
    }
    .card-header {
        padding: 1.25rem 1.5rem;
        background: linear-gradient(90deg, #059669 0%, #10b981 100%);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 1rem;
    }
    .card-header .title {
        margin: 0;
        font-size: 1.125rem;
        font-weight: 700;
        color: white;
    }
    .btn-primary-solid {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .5rem .9rem;
        border-radius: 8px;
        background: white;
        color: #059669;
        border: none;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
    }
    .btn-primary-solid:hover {
        background: #f0fdf4;
    }
    .btn-ai {
        background: #8b5cf6;
        color: white;
    }
    .btn-ai:hover {
        background: #7c3aed;
    }
    .filter-select {
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        border: none;
        font-size: 0.875rem;
        background: white;
        color: #1f2937;
        cursor: pointer;
    }
    .spinner {
        width: 20px;
        height: 20px;
        border: 2px solid #e5e7eb;
        border-top-color: #059669;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
        display: inline-block;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    /* Scrollbar styling */
    .modal-scroll-content::-webkit-scrollbar {
        width: 6px;
    }
    .modal-scroll-content::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    .modal-scroll-content::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }
    .modal-scroll-content::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
@endpush

<div class="card-wrapper">
    <div class="card-header">
        <h3 class="title">📈 Statistik Evaluasi Qurban</h3>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <select id="filterTahunStat" class="filter-select">
                <option value="">Semua Tahun</option>
                @foreach($tahunList as $thn)
                    <option value="{{ $thn }}" {{ $tahunSelected == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <span id="aiStatus" class="px-2 py-1 rounded-full text-xs">
                    🔄 Cek Status...
                </span>
            </div>

            <button onclick="generateAllWish()" class="btn-primary-solid" style="background: #f59e0b;">
                🔄 Generate Semua Wish
            </button>
            <button onclick="generateAISummary()" class="btn-primary-solid" style="background: #8b5cf6;">
                🤖 Generate Ringkasan AI
            </button>
            <a href="{{ route('admin.evaluasi-qurban.index') }}" class="btn-primary-solid">← Kembali</a>
        </div>
    </div>
    
    <div id="statistikContent" class="p-6">
        <div id="loadingIndicator" style="display: none; text-align: center; padding: 2rem;">
            <div class="spinner" style="margin: 0 auto;"></div>
            <p style="margin-top: 0.5rem; color: #059669;">Memuat data...</p>
        </div>
        <div id="statistikData"></div>
    </div>
</div>

<!-- Modal - Perbaikan agar bisa scroll -->
<div id="aiModal" class="fixed inset-0 bg-black/50 hidden z-50 overflow-y-auto">
    <div class="min-h-screen px-4 py-8 flex items-start justify-center">
        <div class="bg-white rounded-xl max-w-4xl w-full my-8 shadow-2xl">
            <!-- Header - Sticky -->
            <div class="sticky top-0 bg-gradient-to-r from-purple-600 to-purple-500 text-white p-4 flex justify-between items-center rounded-t-xl">
                <h3 class="font-bold text-lg flex items-center gap-2">
                    <span class="text-2xl">🤖</span> 
                    Ringkasan Keinginan Pequrban (Gemini AI)
                </h3>
                <button onclick="closeAIModal()" class="text-white text-2xl leading-none hover:text-gray-200 transition">&times;</button>
            </div>
            
            <!-- Content - Scrollable -->
            <div id="aiSummaryContent" class="p-6 max-h-[60vh] overflow-y-auto modal-scroll-content">
                <div class="text-center text-gray-500 py-8">
                    <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    <p class="text-lg font-medium">Klik "Generate Ringkasan AI" untuk memulai</p>
                    <p class="text-sm mt-2">AI akan menganalisis semua komentar pequrban dan merangkum keinginan mereka</p>
                </div>
            </div>
            
            <!-- Footer - Sticky Bottom -->
            <div class="sticky bottom-0 bg-gray-50 px-6 py-4 flex justify-end border-t rounded-b-xl">
                <button onclick="closeAIModal()" class="px-5 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg transition font-medium">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Function to load statistik data
    function loadStatistik(tahun = '') {
        $('#loadingIndicator').show();
        $('#statistikData').html('');
        
        $.ajax({
            url: '{{ route("admin.evaluasi-qurban.statistik-data") }}',
            type: 'GET',
            data: { tahun: tahun },
            dataType: 'json',
            success: function(data) {
                updateStatistikUI(data);
            },
            error: function(xhr) {
                console.error('Error loading statistik:', xhr);
                $('#statistikData').html(`
                    <div style="text-align: center; padding: 2rem; color: #ef4444;">
                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p>Gagal memuat data statistik</p>
                    </div>
                `);
            },
            complete: function() {
                $('#loadingIndicator').hide();
            }
        });
    }
    
    function updateStatistikUI(data) {
        let total = data.total || 0;
        
        function getPercentage(value) {
            return total > 0 ? Math.round((value / total) * 100) : 0;
        }
        
        let html = `
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="stat-card text-center">
                    <div class="stat-number">${total}</div>
                    <div class="text-gray-500 text-sm">Total Responden</div>
                </div>
                <div class="stat-card text-center">
                    <div class="stat-number">${data.rata_pendaftaran || 0}</div>
                    <div class="text-gray-500 text-sm">⭐ Pendaftaran</div>
                </div>
                <div class="stat-card text-center">
                    <div class="stat-number">${data.rata_pelaksanaan || 0}</div>
                    <div class="text-gray-500 text-sm">⭐ Pelaksanaan</div>
                </div>
                <div class="stat-card text-center">
                    <div class="stat-number">${data.rata_distribusi || 0}</div>
                    <div class="text-gray-500 text-sm">⭐ Distribusi</div>
                </div>
                <div class="stat-card text-center">
                    <div class="stat-number">${data.rata_kualitas || 0}</div>
                    <div class="text-gray-500 text-sm">⭐ Kualitas Hewan</div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="stat-card">
                    <h4 class="font-bold text-lg mb-3">📅 Rencana Qurban Tahun Depan</h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm">
                                <span>✅ Ya (${data.rencana_ya || 0})</span>
                                <span>${getPercentage(data.rencana_ya || 0)}%</span>
                            </div>
                            <div class="progress-bar mt-1">
                                <div class="progress-fill" style="width: ${getPercentage(data.rencana_ya || 0)}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span>🤔 Mungkin (${data.rencana_mungkin || 0})</span>
                                <span>${getPercentage(data.rencana_mungkin || 0)}%</span>
                            </div>
                            <div class="progress-bar mt-1">
                                <div class="progress-fill" style="width: ${getPercentage(data.rencana_mungkin || 0)}%; background: #f59e0b;"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span>❌ Tidak (${data.rencana_tidak || 0})</span>
                                <span>${getPercentage(data.rencana_tidak || 0)}%</span>
                            </div>
                            <div class="progress-bar mt-1">
                                <div class="progress-fill" style="width: ${getPercentage(data.rencana_tidak || 0)}%; background: #ef4444;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="stat-card">
                    <h4 class="font-bold text-lg mb-3">🐪 Jenis Hewan Qurban</h4>
                    <div class="space-y-3">
                        <div>
                            <div class="flex justify-between text-sm">
                                <span>🐃 Sapi (${data.sapi || 0})</span>
                                <span>${getPercentage(data.sapi || 0)}%</span>
                            </div>
                            <div class="progress-bar mt-1">
                                <div class="progress-fill" style="width: ${getPercentage(data.sapi || 0)}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-sm">
                                <span>🐐 Kambing (${data.kambing || 0})</span>
                                <span>${getPercentage(data.kambing || 0)}%</span>
                            </div>
                            <div class="progress-bar mt-1">
                                <div class="progress-fill" style="width: ${getPercentage(data.kambing || 0)}%; background: #06b6d4;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        $('#statistikData').html(html);
    }
    
    $(document).ready(function() {
        var initialTahun = $('#filterTahunStat').val();
        loadStatistik(initialTahun);
        
        $('#filterTahunStat').on('change', function() {
            var tahun = $(this).val();
            loadStatistik(tahun);
        });
    });

    function generateAllWish() {
        let tahun = $('#filterTahunStat').val() || '';
        if (!tahun) {
            Swal.fire('Peringatan', 'Silakan pilih tahun terlebih dahulu', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Generate Wish?',
            text: 'Proses ini akan membuat ringkasan keinginan untuk semua data tahun ' + tahun,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Sedang memproses...',
                    text: 'Mohon tunggu',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.ajax({
                    url: '{{ route("admin.evaluasi-qurban.generate-all-wish") }}',
                    type: 'POST',
                    data: { 
                        _token: '{{ csrf_token() }}',
                        tahun: tahun 
                    },
                    success: function(res) {
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil!',
                                text: res.message,
                                confirmButtonColor: '#10b981'
                            }).then(() => {
                                if (typeof table !== 'undefined') table.ajax.reload();
                            });
                        } else {
                            // TAMPILKAN ERROR JELAS
                            Swal.fire({
                                icon: 'error',
                                title: 'AI Tidak Tersedia',
                                html: `<div style="text-align: left;">
                                    <p><strong>Status:</strong> ${res.message}</p>
                                    <p><strong>Kode Error:</strong> ${res.api_status?.code || '-'}</p>
                                    <hr class="my-2">
                                    <p class="text-sm text-gray-600">⚠️ Wish akan menggunakan fallback manual (keyword mapping).</p>
                                    <p class="text-sm text-gray-600">💡 Untuk menggunakan AI, tunggu besok atau tambah kuota di Google AI Studio.</p>
                                </div>`,
                                confirmButtonColor: '#ef4444',
                                confirmButtonText: 'OK, Lanjutkan dengan manual'
                            }).then(() => {
                                // Coba lagi dengan force manual
                                generateAllWishManual(tahun);
                            });
                        }
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Terjadi kesalahan pada server',
                            confirmButtonColor: '#ef4444'
                        });
                    }
                });
            }
        });
    }

    function generateAISummary() {
        let tahun = $('#filterTahunStat').val() || '';
        
        if (!tahun) {
            Swal.fire('Peringatan', 'Silakan pilih tahun terlebih dahulu', 'warning');
            return;
        }
        
        Swal.fire({
            title: 'Generate Ringkasan AI?',
            text: 'AI akan menganalisis semua komentar tahun ' + tahun,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal'
        }).then(result => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'AI Sedang Menganalisis...',
                    text: 'Gemini AI sedang membaca dan merangkum semua komentar pequrban',
                    allowOutsideClick: false,
                    didOpen: () => { Swal.showLoading(); }
                });
                
                $.ajax({
                    url: '{{ route("admin.evaluasi-qurban.generate-summary") }}',
                    type: 'GET',
                    data: { tahun: tahun },
                    dataType: 'json',
                    success: function(res) {
                        Swal.close();
                        
                        if (res.success) {
                            let html = '<div class="space-y-4">';
                            const categories = {
                                'informasi': '📢 Informasi & Promosi',
                                'pendaftaran': '💳 Pendaftaran & Pembayaran',
                                'penyembelihan': '🔪 Penyembelihan & Pengemasan',
                                'distribusi': '🚚 Distribusi Daging',
                                'kualitas': '🥩 Kualitas Hewan'
                            };
                            
                            let hasData = false;
                            for (let [key, label] of Object.entries(categories)) {
                                if (res.summary[key] && res.summary[key].length > 0) {
                                    hasData = true;
                                    html += `
                                        <div class="border border-purple-200 rounded-xl overflow-hidden">
                                            <div class="bg-purple-50 px-4 py-2 border-b border-purple-200">
                                                <h4 class="font-bold text-purple-800">${label}</h4>
                                            </div>
                                            <div class="p-4 space-y-2">
                                                ${res.summary[key].map(item => `
                                                    <div class="flex items-start gap-2 py-1">
                                                        <span class="text-purple-600 mt-0.5">📌</span>
                                                        <span class="text-gray-700">${escapeHtml(item)}</span>
                                                    </div>
                                                `).join('')}
                                            </div>
                                        </div>
                                    `;
                                }
                            }
                            
                            if (!hasData) {
                                html = `
                                    <div class="text-center text-gray-500 py-8">
                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                        </svg>
                                        <p class="text-lg font-medium">Tidak ada data komentar untuk tahun ini</p>
                                        <p class="text-sm mt-2">Silakan pilih tahun lain atau tunggu data evaluasi masuk</p>
                                    </div>
                                `;
                            } else {
                                html += '</div>';
                            }
                            
                            $('#aiSummaryContent').html(html);
                            $('#aiModal').removeClass('hidden');
                            $('body').addClass('overflow-hidden');
                            
                            // Jika ada pesan dari cache
                            if (res.from_cache) {
                                Swal.fire({
                                    icon: 'info',
                                    title: 'Menggunakan Data Tersimpan',
                                    text: res.message || 'Ringkasan sudah pernah dibuat sebelumnya',
                                    confirmButtonColor: '#8b5cf6'
                                });
                            }
                        } else {
                            Swal.fire('Error', res.message || 'Gagal generate ringkasan', 'error');
                        }
                    },
                    error: function(xhr) {
                        Swal.close();
                        console.error('AJAX Error:', xhr);
                        let errorMsg = xhr.responseJSON?.message || 'Terjadi kesalahan pada server';
                        Swal.fire('Error', errorMsg, 'error');
                    }
                });
            }
        });
    }

    function closeAIModal() {
        $('#aiModal').addClass('hidden');
        $('body').removeClass('overflow-hidden');
    }

    function escapeHtml(str) {
        if (!str) return str;
        return str.replace(/[&<>]/g, function(m) {
            if (m === '&') return '&amp;';
            if (m === '<') return '&lt;';
            if (m === '>') return '&gt;';
            return m;
        });
    }
    
    // Cek status AI saat halaman dimuat
    function checkAIStatus() {
        $.get('{{ route("admin.evaluasi-qurban.check-ai-status") }}', function(res) {
            if (res.available) {
                $('#aiStatus').html('✅ AI Online').removeClass('bg-red-100').addClass('bg-green-100 text-green-800');
            } else {
                $('#aiStatus').html('❌ AI Offline - ' + res.message).removeClass('bg-green-100').addClass('bg-red-100 text-red-800');
            }
        });
    }

    $(document).ready(function() {
        checkAIStatus();
    });
</script>
@endpush
@endsection