@php
    $status = $provision['status'];
    $ready = (bool) ($status['ready'] ?? false);
    $conflict = (bool) ($status['conflict'] ?? false);
@endphp
<section @class([
    'mt-5 rounded-2xl border p-5 shadow-sm',
    'border-emerald-300 bg-emerald-50 text-emerald-950' => $ready,
    'border-error/40 bg-error/10 text-error-content' => $conflict,
    'border-amber-300 bg-amber-50 text-amber-950' => ! $ready && ! $conflict,
])>
    <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-bold">{{ $provision['title'] }}</h2>
                <span @class(['badge badge-sm', 'badge-success' => $ready, 'badge-error' => $conflict, 'badge-warning' => ! $ready && ! $conflict])>
                    {{ $ready ? 'Siap digunakan' : ($conflict ? 'Konfigurasi berbeda' : 'Belum diprovision') }}
                </span>
            </div>
            <p class="mt-2 text-sm leading-6">{{ $provision['summary'] }}</p>
            <p class="text-xs leading-5 opacity-75">{{ $provision['info'] }}</p>
            @if ($conflict)
                <p class="mt-2 text-sm font-semibold">Konfigurasi production berbeda dari configuration yang diharapkan. Perlu pemeriksaan administrator.</p>
            @endif
        </div>
        @if ($ready)
            <span class="btn btn-success btn-disabled whitespace-nowrap" aria-disabled="true">✓ {{ $provision['active_label'] }}</span>
        @elseif ($conflict)
            <button class="btn btn-error btn-disabled whitespace-nowrap" disabled>{{ $provision['button_label'] }}</button>
        @else
            <button type="button" class="btn btn-warning whitespace-nowrap" onclick="document.getElementById('{{ $provision['modal_id'] }}').showModal()">{{ $provision['button_label'] }}</button>
        @endif
    </div>
</section>

@if (! $ready && ! $conflict)
    <dialog id="{{ $provision['modal_id'] }}" class="modal">
        <div class="modal-box">
            <form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-2 top-2" aria-label="Tutup">✕</button></form>
            <h3 class="text-lg font-bold">{{ $provision['modal_title'] }}</h3>
            <dl class="mt-4 grid gap-3 rounded-xl bg-base-200/60 p-4 text-sm sm:grid-cols-2">
                @foreach ($provision['details'] as $label => $value)
                    <div><dt class="text-xs text-base-content/55">{{ $label }}</dt><dd class="font-semibold whitespace-pre-line">{{ $value }}</dd></div>
                @endforeach
            </dl>
            <p class="mt-4 rounded-lg border border-info/25 bg-info/10 p-3 text-sm">{{ $provision['info'] }}</p>
            <form method="post" action="{{ $provision['action'] }}" class="mt-5 flex justify-end gap-2">
                @csrf
                <input type="hidden" name="entity" value="{{ $entity->id }}">
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('{{ $provision['modal_id'] }}').close()">Batal</button>
                <button class="btn btn-warning">Provision Konfigurasi</button>
            </form>
        </div>
        <form method="dialog" class="modal-backdrop"><button>Tutup</button></form>
    </dialog>
@endif
