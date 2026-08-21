@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $history ? 'Koreksi Riwayat Dana' : 'Tambah Koreksi Riwayat Dana')

@section('content')
    @php
        $editing = $history !== null;
        $amount = old('amount', $history?->amount);
        $displayAmount = $amount !== null && $amount !== '' ? number_format((float) $amount, 2, ',', '.') : '';
        $selectedFundId = old('fund_id', $history?->fund_id ?? $fund->id);
        $selectedKind = old('entry_kind', $history?->entry_kind ?? 'adjustment_in');
    @endphp

    <div class="mb-6">
        <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.funds.show', ['fund' => $fund, 'entity' => $entity->id]) }}">← Kembali ke detail Dana</a>
        <p class="mt-3 text-sm font-medium text-emerald-700">Riwayat sumber Dana</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight">{{ $editing ? 'Koreksi riwayat Dana' : 'Tambah koreksi riwayat Dana' }}</h1>
        <p class="mt-1 max-w-3xl text-sm text-base-content/65">Data ini menjelaskan posisi Dana sebelum Financial V2. Menyimpan koreksi tidak membuat atau mengubah transaksi, Journal, JournalLine, maupun Ledger.</p>
    </div>

    @if ($errors->any())
        <div class="alert mb-5 items-start border border-error/30 bg-error/10 text-error"><div><p class="font-semibold">Koreksi belum dapat disimpan.</p><ul class="mt-1 list-inside list-disc text-sm">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>
    @endif

    <form method="POST" action="{{ $editing ? route('financial-v2.funds.history.update', ['fund' => $fund, 'history' => $history, 'entity' => $entity->id]) : route('financial-v2.funds.history.store', ['fund' => $fund, 'entity' => $entity->id]) }}" class="grid max-w-5xl gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="entity" value="{{ $entity->id }}">

        <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="form-control"><span class="label-text font-medium">Dana</span><select name="fund_id" class="select select-bordered w-full" required>@foreach ($funds as $availableFund)<option value="{{ $availableFund->id }}" @selected($selectedFundId === $availableFund->id)>{{ $availableFund->name }}</option>@endforeach</select><span class="label-text-alt">Pemetaan hanya dapat dipindahkan ke Dana aktif dalam entitas yang sama.</span></label>
                <label class="form-control"><span class="label-text font-medium">Jenis riwayat</span><select name="entry_kind" class="select select-bordered w-full" required>@foreach ($entryKinds as $kind => $label)<option value="{{ $kind }}" @selected($selectedKind === $kind)>{{ $label }}</option>@endforeach</select></label>
                <label class="form-control"><span class="label-text font-medium">Tanggal</span><input type="date" name="effective_date" value="{{ old('effective_date', $history?->effective_date?->toDateString()) }}" class="input input-bordered w-full"><span class="label-text-alt">Kosongkan bila sumber hanya mencantumkan periode.</span></label>
                <label class="form-control"><span class="label-text font-medium">Label tanggal/periode</span><input type="text" name="date_label" value="{{ old('date_label', $history?->date_label) }}" class="input input-bordered w-full" placeholder="Contoh: Maret 2026" required></label>
                <label class="form-control sm:col-span-2"><span class="label-text font-medium">Uraian</span><input type="text" name="description" value="{{ old('description', $history?->description) }}" class="input input-bordered w-full" placeholder="Contoh: Beras 20 Pack" required></label>
                <label class="form-control"><span class="label-text font-medium">Nominal</span><div class="relative" data-money-field><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="amount" value="{{ $amount }}" data-money-value><input type="text" inputmode="decimal" autocomplete="off" value="{{ $displayAmount }}" placeholder="0" class="input input-bordered w-full pl-9 text-lg font-semibold" data-money-input required></div><span class="label-text-alt">Pemisah ribuan dibuat otomatis.</span></label>
                <label class="form-control"><span class="label-text font-medium">Referensi sumber</span><input type="text" name="source_reference" value="{{ old('source_reference', $history?->source_reference) }}" class="input input-bordered w-full" placeholder="Contoh: Buku Kas Detail!A24:F24" @if($editing) readonly @endif required><span class="label-text-alt">{{ $editing ? 'Lineage sumber asli dipertahankan.' : 'Wajib diisi untuk koreksi manual.' }}</span></label>
            </div>
            <label class="form-control mt-4"><span class="label-text font-medium">Keterangan tambahan</span><textarea name="notes" rows="3" class="textarea textarea-bordered w-full" placeholder="Penjelasan dari sumber atau koreksi">{{ old('notes', $history?->notes) }}</textarea></label>
            <label class="form-control mt-4"><span class="label-text font-medium">Alasan koreksi</span><textarea name="correction_reason" rows="3" class="textarea textarea-bordered w-full" placeholder="Jelaskan sumber dan alasan perubahan agar dapat diaudit" required>{{ old('correction_reason', $history?->correction_reason) }}</textarea></label>
            <div class="mt-6 flex flex-wrap justify-end gap-2"><a class="btn btn-ghost" href="{{ route('financial-v2.funds.show', ['fund' => $fund, 'entity' => $entity->id]) }}">Batal</a><button type="submit" class="btn btn-primary">{{ $editing ? 'Simpan koreksi' : 'Tambah koreksi' }}</button></div>
        </section>

        <aside class="space-y-4">
            <section class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950"><p class="font-bold">Batas accounting</p><p class="mt-2 leading-5">Riwayat ini adalah penjelasan sumber historis. Saldo accounting resmi tetap hanya berasal dari Posted V2 Ledger. Jika nilai sumber berubah, sistem menampilkan selisih rekonsiliasi secara jujur; sistem tidak membuat penyesuaian fiktif.</p></section>
            @if ($editing)
                <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 text-sm"><p class="font-bold">Lineage asli</p><dl class="mt-3 space-y-2 text-base-content/70"><div><dt class="text-xs text-base-content/50">Workbook</dt><dd>{{ $history->source_filename }}</dd></div><div><dt class="text-xs text-base-content/50">Worksheet</dt><dd>{{ $history->source_worksheet ?: '—' }}</dd></div><div><dt class="text-xs text-base-content/50">Referensi</dt><dd>{{ $history->source_reference ?: '—' }}</dd></div><div><dt class="text-xs text-base-content/50">Hash sumber</dt><dd class="break-all text-xs">{{ $history->source_hash ?: 'Koreksi admin' }}</dd></div><div><dt class="text-xs text-base-content/50">Diimpor</dt><dd>{{ $history->imported_at?->translatedFormat('d M Y H:i') ?? '—' }}</dd></div></dl></section>
            @endif
        </aside>
    </form>

    @if ($editing)
        <section class="mt-6 max-w-5xl rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
            <h2 class="font-bold">Audit perubahan</h2>
            <p class="mt-1 text-sm text-base-content/65">Jejak ini append-only: siapa mengubah, kapan, alasan, serta ringkasan nilai sebelum dan sesudah.</p>
            <div class="mt-4 space-y-3">
                @forelse ($auditEvents as $event)
                    @php
                        $before = $event->before_summary ? json_decode($event->before_summary, true) : null;
                        $after = $event->after_summary ? json_decode($event->after_summary, true) : null;
                    @endphp
                    <article class="rounded-xl border border-base-300 p-4">
                        <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between"><div><p class="font-semibold">{{ str_replace('_', ' ', $event->event_type) }}</p><p class="mt-1 text-xs text-base-content/60">{{ $event->actor?->name ?? 'Sistem / akun tidak tersedia' }} · {{ $event->event_at?->translatedFormat('d M Y H:i:s') }}</p></div><span class="badge badge-outline">Teraudit</span></div>
                        @if ($after && ! empty($after['correction_reason']))
                            <p class="mt-3 text-sm"><span class="font-medium">Alasan:</span> {{ $after['correction_reason'] }}</p>
                        @endif
                        @if ($after && ! empty($after['changed_fields']))
                            <p class="mt-2 text-xs text-base-content/65">Field berubah: {{ collect($after['changed_fields'])->join(', ') }}</p>
                        @endif
                        @if ($before || $after)
                            <details class="mt-3 text-xs"><summary class="cursor-pointer text-primary">Lihat nilai sebelum/sesudah</summary><div class="mt-2 grid gap-2 sm:grid-cols-2"><pre class="overflow-x-auto rounded-lg bg-base-200 p-3">{{ json_encode($before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><pre class="overflow-x-auto rounded-lg bg-base-200 p-3">{{ json_encode($after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details>
                        @endif
                    </article>
                @empty
                    <p class="rounded-xl bg-base-200 p-4 text-sm text-base-content/60">Belum ada event audit yang tersedia.</p>
                @endforelse
            </div>
        </section>
    @endif
@endsection
