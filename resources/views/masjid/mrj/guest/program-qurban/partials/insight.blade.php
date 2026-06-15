<div class="space-y-4">
    <div class="flex items-start gap-3 p-3 bg-emerald-50 rounded-lg">
        <i class="fas fa-chart-line text-emerald-600 mt-1"></i>
        <div><p class="font-semibold text-emerald-800">Rata-rata Kepuasan</p><p class="text-sm text-gray-600">Dari skala 1-5, rata-rata keseluruhan mencapai <strong>{{ $rataRataKeseluruhan }}</strong> dari 5.</p></div>
    </div>
    <div class="flex items-start gap-3 p-3 bg-amber-50 rounded-lg">
        <i class="fas fa-chart-simple text-amber-600 mt-1"></i>
        <div><p class="font-semibold text-amber-800">Aspek Terendah</p><p class="text-sm text-gray-600">Aspek dengan rating terendah adalah <strong>@php $minRating = min($ratingData); $aspekName = ['pendaftaran' => 'Pendaftaran', 'pelaksanaan' => 'Penyembelihan', 'distribusi' => 'Distribusi', 'kualitas' => 'Kualitas Hewan']; echo $aspekName[array_search($minRating, $ratingData)]; @endphp</strong> dengan rating {{ $minRating }}/5.</p></div>
    </div>
    <div class="flex items-start gap-3 p-3 bg-teal-50 rounded-lg">
        <i class="fas fa-heart text-teal-600 mt-1"></i>
        <div><p class="font-semibold text-teal-800">Aspek Tertinggi</p><p class="text-sm text-gray-600">Aspek dengan rating tertinggi adalah <strong>@php $maxRating = max($ratingData); echo $aspekName[array_search($maxRating, $ratingData)]; @endphp</strong> dengan rating {{ $maxRating }}/5.</p></div>
    </div>
    <div class="flex items-start gap-3 p-3 bg-blue-50 rounded-lg">
        <i class="fas fa-hand-holding-heart text-blue-600 mt-1"></i>
        <div><p class="font-semibold text-blue-800">Loyalitas Pequrban</p><p class="text-sm text-gray-600">{{ ($rencanaQurban['ya'] ?? 0) + ($rencanaQurban['mungkin'] ?? 0) }} dari {{ $totalResponden }} responden berminat qurban kembali ({{ $totalResponden > 0 ? round((($rencanaQurban['ya'] ?? 0) / $totalResponden) * 100) : 0 }}%).</p></div>
    </div>
</div>