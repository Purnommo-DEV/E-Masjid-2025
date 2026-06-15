<div>
    <div class="flex justify-between items-center mb-1">
        <span class="font-medium text-gray-700">📝 Pendaftaran & Pembayaran</span>
        <span class="font-bold text-emerald-600">{{ $ratingData['pendaftaran'] }} / 5</span>
    </div>
    <div class="progress-bar-custom">
        <div class="progress-fill bg-emerald-500" style="width: {{ ($ratingData['pendaftaran'] / 5) * 100 }}%"></div>
    </div>
</div>
<div>
    <div class="flex justify-between items-center mb-1">
        <span class="font-medium text-gray-700">🔪 Penyembelihan & Pengemasan</span>
        <span class="font-bold text-amber-600">{{ $ratingData['pelaksanaan'] }} / 5</span>
    </div>
    <div class="progress-bar-custom">
        <div class="progress-fill bg-amber-500" style="width: {{ ($ratingData['pelaksanaan'] / 5) * 100 }}%"></div>
    </div>
</div>
<div>
    <div class="flex justify-between items-center mb-1">
        <span class="font-medium text-gray-700">🚚 Distribusi Daging</span>
        <span class="font-bold text-teal-600">{{ $ratingData['distribusi'] }} / 5</span>
    </div>
    <div class="progress-bar-custom">
        <div class="progress-fill bg-teal-500" style="width: {{ ($ratingData['distribusi'] / 5) * 100 }}%"></div>
    </div>
</div>
<div>
    <div class="flex justify-between items-center mb-1">
        <span class="font-medium text-gray-700">🥩 Kualitas Hewan Qurban</span>
        <span class="font-bold text-emerald-600">{{ $ratingData['kualitas'] }} / 5</span>
    </div>
    <div class="progress-bar-custom">
        <div class="progress-fill bg-emerald-500" style="width: {{ ($ratingData['kualitas'] / 5) * 100 }}%"></div>
    </div>
</div>