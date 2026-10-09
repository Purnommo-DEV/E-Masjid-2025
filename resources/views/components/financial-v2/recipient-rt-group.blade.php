@props(['rw', 'rt', 'total'])

<div {{ $attributes->class(['mx-3 mb-3 mt-3 min-w-0 overflow-hidden rounded-lg border border-base-300']) }}>
    <h4 class="border-b border-base-300 bg-base-200/60 px-3 py-2 text-xs font-semibold text-base-content/70">RT: {{ trim((string) $rt) !== '' ? trim((string) $rt) : 'Belum ditentukan' }} · RW: {{ trim((string) $rw) !== '' ? trim((string) $rw) : 'Belum ditentukan' }} · {{ $total }} penerima</h4>
    {{ $slot }}
</div>
