@if (! empty($lineage))
    <details class="mt-2 text-xs text-base-content/60">
        <summary class="cursor-pointer font-medium">Jejak sumber saldo awal</summary>
        <p class="mt-1">Ini menjelaskan sumber sebelum V2 dan bukan transaksi V2 yang diposting ulang.</p>
        <ul class="mt-1 list-disc space-y-1 pl-4">
            @foreach ($lineage as $source)
                <li>{{ $source['date'] }} · {{ $source['description'] }} · Rp{{ number_format((float) $source['amount'], 2, ',', '.') }} <span class="text-base-content/45">({{ $source['reference'] }})</span></li>
            @endforeach
        </ul>
    </details>
@endif
