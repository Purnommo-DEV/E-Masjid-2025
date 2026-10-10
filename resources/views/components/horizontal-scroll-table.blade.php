@props(['label' => 'Geser tabel ke kiri atau kanan untuk melihat kolom lainnya.'])

<div
    x-data="{
        overflow: false,
        atStart: true,
        atEnd: true,
        update() {
            const el = this.$refs.scroller;
            this.overflow = el.scrollWidth > el.clientWidth + 1;
            this.atStart = el.scrollLeft <= 1;
            this.atEnd = el.scrollLeft + el.clientWidth >= el.scrollWidth - 1;
        }
    }"
    x-init="$nextTick(() => update())"
    @resize.window.debounce.150ms="update()"
    class="min-w-0"
>
    <p x-cloak x-show="overflow" class="flex items-center gap-2 border-b border-slate-100 bg-slate-50/80 px-4 py-2 text-xs font-medium leading-5 text-slate-600 sm:px-5">
        <span aria-hidden="true" class="text-base text-emerald-700">↔</span>
        {{ $label }}
    </p>
    <div class="relative min-w-0">
        <div x-cloak x-show="overflow && !atStart" class="pointer-events-none absolute inset-y-0 left-0 z-10 w-5 bg-gradient-to-r from-slate-300/50 to-transparent"></div>
        <div x-cloak x-show="overflow && !atEnd" class="pointer-events-none absolute inset-y-0 right-0 z-10 w-5 bg-gradient-to-l from-slate-300/50 to-transparent"></div>
        <div x-ref="scroller" @scroll.passive="update()" class="max-w-full overflow-x-auto overscroll-x-contain">
            {{ $slot }}
        </div>
    </div>
</div>
