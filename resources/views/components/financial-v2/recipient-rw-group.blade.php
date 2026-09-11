@props(['rw'])

<section {{ $attributes->class(['min-w-0']) }}>
    <h3 class="border-y border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-bold text-emerald-950">RW: {{ trim((string) $rw) !== '' ? trim((string) $rw) : '—' }}</h3>
    {{ $slot }}
</section>
