@php
    $badgeIcon = $badgeIcon ?? '✦';
    $badge = $badge ?? '';
    $title = $title ?? '';
    $highlight = $highlight ?? null;
    $description = $description ?? null;
@endphp

<div class="mx-auto mb-8 max-w-4xl text-center sm:mb-10">
    <div class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-emerald-600 to-teal-500 px-3 py-1.5 text-white shadow-lg shadow-emerald-500/20 sm:mb-4 sm:px-4 sm:py-2">
        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-white/15 text-[10px] sm:h-6 sm:w-6 sm:text-xs">
            {{ $badgeIcon }}
        </span>

        <span class="text-[9px] font-black uppercase tracking-[0.1em] sm:text-[11px] sm:tracking-[0.12em]">
            {{ $badge }}
        </span>
    </div>

    <h2 class="font-serif text-[2rem] font-black leading-[1.12] tracking-tight text-slate-950 min-[390px]:text-4xl sm:text-4xl lg:text-5xl">
        {{ $title }}

        @if($highlight)
            <span class="block bg-gradient-to-r from-emerald-600 via-teal-500 to-cyan-500 bg-clip-text text-transparent">
                {{ $highlight }}
            </span>
        @endif
    </h2>

    @if($description)
        <p class="mx-auto mt-4 max-w-3xl text-sm leading-7 text-slate-600 sm:mt-5 sm:text-base sm:leading-8 lg:text-lg">
            {!! $description !!}
        </p>
    @endif
</div>