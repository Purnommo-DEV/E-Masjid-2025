
@can('view penyaluran ziswaf')
<section class="my-8 rounded-2xl bg-base-100 border border-base-300 p-5">
<h2 class="font-bold text-xl">Penyaluran dan penerima manfaat</h2>
<p class="text-sm my-3">{{ $report['distributions']['distribution_events'] }} penyaluran · {{ $report['distributions']['unique_beneficiaries'] }} penerima unik. Total operasional bukan tambahan pengeluaran finansial. Agregat tertaut hanya menghitung realisasi utuh dalam cakupan Dana.</p>
<a class="btn btn-outline btn-sm mb-4" href="{{ route('financial-v2.distributions.index', ['entity' => $entity->id, 'program_id' => $report['filters']['program_id'] ?? null]) }}">Lihat periode / kelola penyaluran</a>
<div class="grid gap-3 sm:grid-cols-2">
@forelse($report['distributions']['programs'] as $row)<article class="rounded-xl bg-base-200 p-4"><h3 class="font-semibold">{{ $row['program_name'] }}</h3><p class="text-sm mt-2">{{ $row['distribution_events'] }} kejadian · {{ $row['unique_beneficiaries'] }} penerima unik</p><p class="text-sm">Total operasional: Rp{{ number_format((float) $row['operational_total'], 2, ',', '.') }}</p><p class="text-sm">Actual realisasi tertaut: Rp{{ number_format((float) $row['actual_amount'], 2, ',', '.') }}</p></article>
@empty<p class="text-sm">Belum ada penyaluran pada periode ini.</p>
@endforelse</div>
<div class="grid gap-3 sm:grid-cols-2 mt-4">
@foreach($report['distributions']['events'] as $row)<article class="rounded-xl border border-base-300 p-4 text-sm"><p class="font-semibold">{{ $row['program_name'] }} · {{ $row['period'] }}</p><p>{{ $row['recipient_count'] }} penerima · {{ $row['operational_status'] }} / {{ $row['financial_status'] }}</p>
@can('view penerima ziswaf')<a class="link" href="{{ route('financial-v2.distributions.show', ['entity' => $entity->id, 'distribution' => $row['distribution_id']]) }}">Rincian penyaluran</a>
@endcan</article>
@endforeach</div>
<div class="mt-5"><h3 class="font-semibold">Sebaran RT/RW</h3><div class="grid gap-2 sm:grid-cols-3 mt-2">@forelse($report['distributions']['regions'] as $region)<p class="text-sm">RT {{ $region['rt'] ?: '—' }} / RW {{ $region['rw'] ?: '—' }} · {{ $region['coordinator'] ?: 'Tanpa koordinator' }}<br><strong>{{ $region['recipient_count'] }} penerima unik</strong></p>@empty<p class="text-sm">Belum ada sebaran.</p>@endforelse</div></div>
</section>
@endcan
