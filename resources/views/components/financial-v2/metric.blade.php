@props(['title', 'value', 'tone' => 'default'])

<div class="card border border-base-300 bg-base-100 shadow-sm">
    <div class="card-body gap-1 p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-base-content/55">{{ $title }}</p>
        <p @class([
            'text-xl font-bold tracking-tight sm:text-2xl',
            'text-emerald-700' => $tone === 'success',
            'text-amber-700' => $tone === 'warning',
        ])>{{ $value }}</p>
    </div>
</div>
