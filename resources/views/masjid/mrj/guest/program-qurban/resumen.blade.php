@extends('masjid.master-guest')

@section('title', 'Resumen Evaluasi Qurban - Masjid Raudhatul Jannah TCE')

@push('head')
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
@endpush

@section('content')

<style>
    * { font-family: 'Inter', sans-serif; }
    .resumen-hero { background: linear-gradient(135deg, #ecfdf5 0%, #f0fdf4 45%, #fffbeb 100%); }
    .stat-card { background: white; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.3s ease; }
    .stat-card:hover { transform: translateY(-4px); box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    .stat-number { font-size: 2rem; font-weight: 800; color: #059669; }
    .chart-container { background: white; border-radius: 1rem; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .filter-select { padding: 0.5rem 2rem 0.5rem 1rem; border: 1px solid #e5e7eb; border-radius: 0.5rem; font-size: 0.875rem; background: white; cursor: pointer; }
    .progress-bar-custom { height: 0.5rem; background: #e5e7eb; border-radius: 1rem; overflow: hidden; }
    .progress-fill { height: 100%; border-radius: 1rem; transition: width 0.5s ease; }
</style>

<section class="resumen-hero relative overflow-hidden pt-6 md:pt-20">
    <div class="container mx-auto px-4 md:px-8 lg:px-20 pt-12 md:pt-20 pb-16 md:pb-20">
        <div class="max-w-5xl mx-auto text-center">
            <div class="flex justify-center mb-6">
                <div class="inline-flex items-center gap-2 bg-emerald-100 rounded-full px-5 py-2">
                    <i class="fas fa-chart-pie text-emerald-600"></i>
                    <span class="text-emerald-700 font-semibold">RESUMEN & MATRIKS EVALUASI</span>
                </div>
            </div>
            <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold mb-4 text-slate-900">Ringkasan Evaluasi Qurban</h1>
            <p class="text-slate-600 text-lg max-w-2xl mx-auto mb-6">Visualisasi data lengkap dari hasil evaluasi para shohibul qurban</p>
            
            <!-- FILTER TAHUN -->
            <div class="flex justify-center items-center gap-3 flex-wrap">
                <label class="text-gray-600 font-medium">Filter Tahun:</label>
                <select id="filterTahun" class="filter-select">
                    <option value="">Semua Tahun</option>
                    @foreach($tahunDropdown as $thn)
                        <option value="{{ $thn }}" {{ ($tahunFilter ?? '') == $thn ? 'selected' : '' }}>{{ $thn }}</option>
                    @endforeach
                </select>
                <button id="btnReset" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 rounded-lg text-sm transition">Reset Filter</button>
            </div>
        </div>
    </div>
</section>

{{-- STATISTIK UTAMA --}}
<section class="py-10">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5" id="statCards">
            <div class="stat-card text-center">
                <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-users text-emerald-600 text-xl"></i></div>
                <div class="stat-number" id="totalResponden">{{ $totalResponden }}</div>
                <p class="text-gray-500 text-sm">Total Responden</p>
            </div>
            <div class="stat-card text-center">
                <div class="w-12 h-12 rounded-full bg-amber-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-star text-amber-600 text-xl"></i></div>
                <div class="stat-number" id="rataRata">{{ $rataRataKeseluruhan }} / 5</div>
                <p class="text-gray-500 text-sm">Rata-rata Kepuasan</p>
            </div>
            <div class="stat-card text-center">
                <div class="w-12 h-12 rounded-full bg-teal-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-drumstick-bite text-teal-600 text-xl"></i></div>
                <div class="stat-number" id="jumlahSapi">{{ $jenisHewan['sapi'] ?? 0 }}</div>
                <p class="text-gray-500 text-sm">Jumlah Qurban Sapi</p>
            </div>
            <div class="stat-card text-center">
                <div class="w-12 h-12 rounded-full bg-orange-100 flex items-center justify-center mx-auto mb-3"><i class="fas fa-calendar-check text-orange-600 text-xl"></i></div>
                <div class="stat-number" id="minatKembali">{{ ($rencanaQurban['ya'] ?? 0) + ($rencanaQurban['mungkin'] ?? 0) }}</div>
                <p class="text-gray-500 text-sm">Berminat Qurban Lagi</p>
            </div>
        </div>
    </div>
</section>

{{-- RATING CHART & JENIS HEWAN --}}
<section class="py-8">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="grid lg:grid-cols-2 gap-8">
            <div class="chart-container"><h3 class="font-bold text-lg mb-4"><i class="fas fa-chart-simple text-emerald-600"></i> Rating Kepuasan Layanan</h3><canvas id="ratingChart" height="250"></canvas></div>
            <div class="chart-container"><h3 class="font-bold text-lg mb-4"><i class="fas fa-chart-pie text-emerald-600"></i> Jenis Hewan Qurban</h3><canvas id="jenisHewanChart" height="250"></canvas></div>
        </div>
    </div>
</section>

{{-- DETAIL RATING --}}
<section class="py-8">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="chart-container"><h3 class="font-bold text-lg mb-4"><i class="fas fa-table-list text-emerald-600"></i> Detail Rating per Aspek</h3><div id="detailRatingContainer">@include('guest.evaluasi-qurban.partials.detail-rating', ['ratingData' => $ratingData])</div></div>
    </div>
</section>

{{-- TREN PER TAHUN (hanya jika lebih dari 1 tahun) --}}
@if(count($tahunList) > 1)
<section class="py-8">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="chart-container"><h3 class="font-bold text-lg mb-4"><i class="fas fa-chart-line text-emerald-600"></i> Tren Rating per Tahun</h3><canvas id="trendChart" height="250"></canvas></div>
    </div>
</section>
@endif

{{-- SUMBER INFORMASI --}}
@if(count($sumberInfo) > 0)
<section class="py-8">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="chart-container"><h3 class="font-bold text-lg mb-4"><i class="fas fa-bullhorn text-emerald-600"></i> Sumber Informasi Qurban</h3><div id="sumberInfoContainer">@include('guest.evaluasi-qurban.partials.sumber-info', ['sumberInfo' => $sumberInfo, 'totalResponden' => $totalResponden])</div></div>
    </div>
</section>
@endif

{{-- RENCANA QURBAN & INSIGHT --}}
<section class="py-8">
    <div class="container mx-auto px-4 md:px-8 lg:px-20">
        <div class="grid lg:grid-cols-2 gap-8">
            <div class="chart-container"><h3 class="font-bold text-lg mb-4"><i class="fas fa-calendar-alt text-emerald-600"></i> Rencana Qurban Tahun Depan</h3><canvas id="rencanaChart" height="250"></canvas></div>
            <div class="chart-container" id="insightContainer">@include('guest.evaluasi-qurban.partials.insight', ['ratingData' => $ratingData, 'rencanaQurban' => $rencanaQurban, 'totalResponden' => $totalResponden, 'rataRataKeseluruhan' => $rataRataKeseluruhan])</div>
        </div>
    </div>
</section>

<footer class="text-center py-8 border-t border-emerald-200 mt-8"><p class="text-gray-400 text-sm"><i class="fas fa-mosque"></i> Masjid Raudhatul Jannah · Resumen Evaluasi Qurban</p></footer>

@endsection

@push('scripts')
<script>
let ratingChart, jenisHewanChart, rencanaChart, trendChart;

function reloadCharts(tahun) {
    $.ajax({ url: '{{ route("guest.evaluasi-qurban.resumen-data") }}', type: 'GET', data: { tahun: tahun }, success: function(data) {
        $('#totalResponden').text(data.totalResponden);
        $('#rataRata').text(data.rataRataKeseluruhan + ' / 5');
        $('#jumlahSapi').text(data.jenisHewan.sapi);
        $('#minatKembali').text(data.rencanaQurban.ya + data.rencanaQurban.mungkin);
        
        if (ratingChart) ratingChart.destroy();
        ratingChart = new Chart(document.getElementById('ratingChart'), { type: 'bar', data: { labels: ['Pendaftaran', 'Penyembelihan', 'Distribusi', 'Kualitas Hewan'], datasets: [{ label: 'Rating (1-5)', data: [data.ratingData.pendaftaran, data.ratingData.pelaksanaan, data.ratingData.distribusi, data.ratingData.kualitas], backgroundColor: ['#10b981', '#f59e0b', '#14b8a6', '#059669'], borderRadius: 8 }] }, options: { responsive: true, scales: { y: { min: 0, max: 5 } } } });
        
        if (jenisHewanChart) jenisHewanChart.destroy();
        jenisHewanChart = new Chart(document.getElementById('jenisHewanChart'), { type: 'pie', data: { labels: ['Sapi', 'Kambing'], datasets: [{ data: [data.jenisHewan.sapi, data.jenisHewan.kambing], backgroundColor: ['#f59e0b', '#10b981'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
        
        if (rencanaChart) rencanaChart.destroy();
        rencanaChart = new Chart(document.getElementById('rencanaChart'), { type: 'doughnut', data: { labels: ['Ya', 'Mungkin', 'Tidak'], datasets: [{ data: [data.rencanaQurban.ya, data.rencanaQurban.mungkin, data.rencanaQurban.tidak], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'] }] }, options: { responsive: true, plugins: { legend: { position: 'bottom' } } } });
        
        $('#detailRatingContainer').html(data.detailRatingHtml);
        $('#insightContainer').html(data.insightHtml);
        $('#sumberInfoContainer').html(data.sumberInfoHtml);
        
        if (data.ratingPerTahun && Object.keys(data.ratingPerTahun).length > 1) {
            if (trendChart) trendChart.destroy();
            let tahunLabels = Object.keys(data.ratingPerTahun);
            trendChart = new Chart(document.getElementById('trendChart'), { type: 'line', data: { labels: tahunLabels, datasets: [{ label: 'Pendaftaran', data: tahunLabels.map(t => data.ratingPerTahun[t].pendaftaran), borderColor: '#10b981', fill: true }, { label: 'Penyembelihan', data: tahunLabels.map(t => data.ratingPerTahun[t].pelaksanaan), borderColor: '#f59e0b', fill: true }, { label: 'Distribusi', data: tahunLabels.map(t => data.ratingPerTahun[t].distribusi), borderColor: '#14b8a6', fill: true }, { label: 'Kualitas', data: tahunLabels.map(t => data.ratingPerTahun[t].kualitas), borderColor: '#8b5cf6', fill: true }] }, options: { responsive: true, scales: { y: { min: 0, max: 5 } } } });
        }
    }});
}

$(document).ready(function() {
    let urlParams = new URLSearchParams(window.location.search);
    let initialTahun = urlParams.get('tahun') || '{{ $tahunFilter }}';
    if (initialTahun) $('#filterTahun').val(initialTahun);
    reloadCharts(initialTahun);
    
    $('#filterTahun').on('change', function() { let tahun = $(this).val(); reloadCharts(tahun); let url = new URL(window.location.href); if (tahun) url.searchParams.set('tahun', tahun); else url.searchParams.delete('tahun'); window.history.pushState({}, '', url); });
    $('#btnReset').on('click', function() { $('#filterTahun').val(''); reloadCharts(''); let url = new URL(window.location.href); url.searchParams.delete('tahun'); window.history.pushState({}, '', url); });
});
</script>
@endpush