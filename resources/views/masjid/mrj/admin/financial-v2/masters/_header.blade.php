@php
    $masterTabs = [
        ['route' => 'financial-v2.masters.accounts.index', 'label' => 'Rekening / Kas'],
        ['route' => 'financial-v2.masters.funds.index', 'label' => 'Dana'],
        ['route' => 'financial-v2.masters.programs.index', 'label' => 'Program'],
        ['route' => 'financial-v2.masters.categories.index', 'label' => 'Kategori Transaksi'],
        ['route' => 'financial-v2.masters.policies.index', 'label' => 'Aturan Dana'],
    ];
@endphp

<section class="mb-6 rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Master Keuangan V2</p>
            <h1 class="mt-1 text-2xl font-bold text-emerald-950">{{ $title }}</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-base-content/70">{{ $subtitle }}</p>
        </div>
        <form method="get" class="min-w-56">
            <label class="label py-0"><span class="label-text text-xs">Entitas Financial V2</span></label>
            <select name="entity" class="select select-bordered select-sm w-full" onchange="this.form.submit()">
                <option value="">Pilih entitas</option>
                @foreach ($entities as $availableEntity)
                    <option value="{{ $availableEntity->id }}" @selected($entity?->id === $availableEntity->id)>{{ $availableEntity->name }}</option>
                @endforeach
            </select>
        </form>
    </div>
    <nav class="mt-5 flex gap-2 overflow-x-auto pb-1 text-xs font-medium" aria-label="Master Keuangan">
        @foreach ($masterTabs as $tab)
            <a href="{{ route($tab['route'], ['entity' => $entity?->id]) }}" @class([
                'whitespace-nowrap rounded-full px-3 py-2 transition',
                'bg-emerald-100 text-emerald-950' => request()->routeIs($tab['route']),
                'bg-base-200 text-base-content/70 hover:bg-base-300' => ! request()->routeIs($tab['route']),
            ])>{{ $tab['label'] }}</a>
        @endforeach
    </nav>
</section>

@if (! $entity)
    <div role="alert" class="alert mb-6 text-sm">
        <span>Pilih satu Entitas Financial V2 aktif sebelum menambah atau mengubah master.</span>
    </div>
@endif
