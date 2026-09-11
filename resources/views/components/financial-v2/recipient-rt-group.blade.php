@props(['rw', 'rt', 'total'])

<div {{ $attributes->class(['mt-3 min-w-0']) }}>
    <h4 class="border-b border-base-300 bg-base-200/50 px-3 py-2 text-xs font-semibold text-base-content/70">RT: {{ trim((string) $rt) !== '' ? trim((string) $rt) : '—' }} · RW: {{ trim((string) $rw) !== '' ? trim((string) $rw) : '—' }} · Total Penerima: {{ $total }}</h4>
    {{ $slot }}
</div>
