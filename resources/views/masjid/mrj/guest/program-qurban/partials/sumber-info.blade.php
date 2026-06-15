<div class="grid md:grid-cols-2 gap-6">
    <canvas id="sumberInfoChart" height="250"></canvas>
    <div class="space-y-3">
        @foreach($sumberInfo as $sumber => $total)
        <div>
            <div class="flex justify-between text-sm mb-1"><span>{{ $sumber ?: 'Tidak disebutkan' }}</span><span class="font-semibold">{{ $total }}</span></div>
            <div class="progress-bar-custom"><div class="progress-fill bg-purple-500" style="width: {{ ($total / max($totalResponden, 1)) * 100 }}%"></div></div>
        </div>
        @endforeach
    </div>
</div>
<script>
if (window.sumberInfoChart) window.sumberInfoChart.destroy();
window.sumberInfoChart = new Chart(document.getElementById('sumberInfoChart'), {
    type: 'pie', data: { labels: {!! json_encode(array_keys($sumberInfo)) !!}, datasets: [{ data: {!! json_encode(array_values($sumberInfo)) !!}, backgroundColor: ['#10b981', '#f59e0b', '#14b8a6', '#8b5cf6', '#ef4444', '#06b6d4'] }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
});
</script>