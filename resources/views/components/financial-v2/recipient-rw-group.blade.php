@props(['rw'])

<section {{ $attributes->class(['min-w-0 overflow-hidden rounded-xl border border-emerald-200 bg-base-100']) }}>
    <h3 class="bg-emerald-50 px-4 py-3 text-sm font-bold text-emerald-950">RW: {{ trim((string) $rw) !== '' ? trim((string) $rw) : 'Belum ditentukan' }}</h3>
    {{ $slot }}
</section>
