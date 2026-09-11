@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $editing ? 'Ubah Planning' : 'Buat Planning')

@push('styles')
<style>
    @media (min-width: 1024px) {
        #planning-layout { grid-template-columns: minmax(0, 1.35fr) minmax(18rem, .65fr); }
    }
</style>
@endpush

@section('content')
    @php
        $savedLines = $planning->exists ? $planning->fundings->map(fn ($line) => ['fund_id' => $line->fund_id, 'amount' => $line->amount, 'notes' => $line->notes])->all() : [['fund_id' => '', 'amount' => '', 'notes' => '']];
        $fundingLines = old('fundings', $savedLines);
        $formatMoneyInput = static function ($value): string {
            if ($value === null || $value === '') return '';
            try {
                return \App\Domain\FinancialV2\DecimalAmount::formatIndonesian((string) $value);
            } catch (\InvalidArgumentException) {
                return (string) $value;
            }
        };
        $formatCountInput = static fn ($value): string => $value === null || $value === '' ? '' : number_format((int) preg_replace('/\D/', '', (string) $value), 0, ',', '.');
    @endphp

    <div class="mb-5"><a class="text-sm text-emerald-700 hover:underline" href="{{ $planning->exists ? route('financial-v2.plannings.show', ['entity' => $entity->id, 'planning' => $planning->id]) : route('financial-v2.plannings.index', ['entity' => $entity->id]) }}">← Kembali</a></div>
    <form id="planning-form" method="POST" action="{{ $editing ? route('financial-v2.plannings.update', ['entity' => $entity->id, 'planning' => $planning->id]) : route('financial-v2.plannings.store', ['entity' => $entity->id]) }}" data-preview-url="{{ route('financial-v2.plannings.preview', ['entity' => $entity->id]) }}">
        @csrf
        @if ($editing) @method('PUT') @endif
        <input type="hidden" name="entity" value="{{ $entity->id }}">
        @if ($editing) <input type="hidden" name="planning_id" value="{{ $planning->id }}"> @endif

        <div id="planning-layout" class="grid items-start gap-5 lg:grid-cols-[1.35fr_.65fr]">
            <div id="planning-workflow" class="min-w-0 space-y-5">
                <section data-planning-section="information" class="rounded-3xl bg-base-100 p-5 shadow-sm sm:p-6">
                    <div class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">A · Informasi Rencana</div>
                    <h1 class="mt-2 text-2xl font-bold">{{ $editing ? 'Ubah Draft Planning' : 'Buat Draft Planning' }}</h1>
                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Nama rencana *</span><input class="input input-bordered" name="name" maxlength="240" required value="{{ old('name', $planning->name) }}"></label>
                        <label class="form-control"><span class="label-text font-medium">Mulai *</span><input type="date" class="input input-bordered" name="period_start" required value="{{ old('period_start', optional($planning->period_start)->format('Y-m-d')) }}"></label>
                        <label class="form-control"><span class="label-text font-medium">Selesai *</span><input type="date" class="input input-bordered" name="period_end" required value="{{ old('period_end', optional($planning->period_end)->format('Y-m-d')) }}"></label>
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Program (opsional)</span><select class="select select-bordered" name="program_id"><option value="">Tanpa program</option>@foreach ($programs as $program)<option value="{{ $program->id }}" @selected(old('program_id', $planning->program_id) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Target penerima</span><input id="recipient-count" inputmode="numeric" class="input input-bordered localized-count-input" name="target_recipient_count" value="{{ $formatCountInput(old('target_recipient_count', $planning->target_recipient_count)) }}" placeholder="0"></label>
                        <label class="form-control" data-money-field><span class="label-text font-medium">Nominal per penerima</span><input id="per-recipient-display" data-money-input inputmode="decimal" class="input input-bordered" value="{{ $formatMoneyInput(old('amount_per_recipient', $planning->amount_per_recipient)) }}" placeholder="0"><input id="per-recipient" data-money-value type="hidden" name="amount_per_recipient" value="{{ old('amount_per_recipient', $planning->amount_per_recipient) }}"></label>
                        <label class="form-control sm:col-span-2" data-money-field><span class="label-text font-medium">Total Planning *</span><input id="planning-total-display" data-money-input inputmode="decimal" class="input input-bordered font-semibold" value="{{ $formatMoneyInput(old('total_amount', $planning->total_amount)) }}" placeholder="0" required><input id="planning-total" data-money-value type="hidden" name="total_amount" value="{{ old('total_amount', $planning->total_amount) }}"><span class="label-text-alt mt-1">Jika target dan nominal/penerima terisi, total dihitung otomatis dan tetap divalidasi server.</span></label>
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Catatan</span><textarea class="textarea textarea-bordered" rows="3" name="notes">{{ old('notes', $planning->notes) }}</textarea></label>
                    </div>
                </section>

                <section data-planning-section="funding" class="rounded-3xl bg-base-100 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><div class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">B · Sumber Dana</div><h2 class="mt-2 text-xl font-bold">Multi-Fund</h2></div>
                        <button id="add-funding" type="button" class="btn btn-outline btn-sm">+ Tambah Sumber Dana</button>
                    </div>
                    <p class="mt-2 text-sm text-base-content/60">Setiap Dana memakai kapasitas canonical: saldo aktual dikurangi Allocation berjalan dan Planning yang sudah disetujui.</p>
                    <div id="funding-lines" class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($fundingLines as $index => $line)
                            <article class="funding-line min-w-0 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm">
                                <div class="flex items-start gap-2">
                                    <label class="form-control min-w-0 flex-1"><span class="label-text font-semibold">Dana</span><select class="select select-bordered fund-select w-full" name="fundings[{{ $index }}][fund_id]" required><option value="">Pilih Dana</option>@foreach ($funds as $fund)<option value="{{ $fund->id }}" @selected(($line['fund_id'] ?? '') === $fund->id)>{{ $fund->code }} · {{ $fund->name }}</option>@endforeach</select></label>
                                    <button type="button" class="btn btn-ghost btn-sm mt-7 text-error remove-funding" aria-label="Hapus sumber Dana">Hapus</button>
                                </div>
                                <div class="fund-capacity mt-4 hidden" aria-live="polite">
                                    <dl class="grid grid-cols-2 gap-3 text-xs">
                                        <div><dt class="text-base-content/55">Saldo Aktual</dt><dd class="fund-actual mt-1 font-semibold">Rp0</dd></div>
                                        <div><dt class="text-base-content/55">Allocation Berjalan</dt><dd class="fund-outstanding mt-1 font-semibold">Rp0</dd></div>
                                        <div><dt class="text-base-content/55">Planning Disetujui</dt><dd class="fund-approved mt-1 font-semibold">Rp0</dd></div>
                                        <div class="rounded-xl bg-emerald-50 p-3"><dt class="font-semibold text-emerald-800">Maks. Dapat Direncanakan</dt><dd class="fund-available mt-1 text-base font-bold text-emerald-800">Rp0</dd></div>
                                    </dl>
                                </div>
                                <p class="fund-capacity-empty mt-4 rounded-xl bg-base-200 p-3 text-xs text-base-content/60">Pilih Dana untuk menampilkan kapasitas terbaru.</p>
                                <label class="form-control mt-4" data-money-field><span class="label-text font-semibold">Nominal Sumber Dana</span><input class="input input-bordered fund-amount-display" data-money-input inputmode="decimal" required value="{{ $formatMoneyInput($line['amount'] ?? '') }}" placeholder="0"><input class="fund-amount" data-money-value type="hidden" name="fundings[{{ $index }}][amount]" value="{{ $line['amount'] ?? '' }}"></label>
                                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                    <button type="button" class="btn btn-outline btn-sm use-maximum" disabled>Gunakan Maksimal</button>
                                    <span class="fund-state text-xs font-semibold text-base-content/60">Kapasitas belum dihitung</span>
                                </div>
                                <label class="form-control mt-3"><span class="label-text text-xs">Catatan sumber Dana (opsional)</span><input class="input input-bordered fund-notes" name="fundings[{{ $index }}][notes]" value="{{ $line['notes'] ?? '' }}" placeholder="Catatan opsional"></label>
                            </article>
                        @endforeach
                    </div>
                </section>

                <section data-planning-section="capacity" class="rounded-3xl bg-base-100 p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div><div class="text-xs font-bold uppercase tracking-[.18em] text-emerald-700">C · Fund Capacity Preview</div><h2 class="mt-2 text-xl font-bold">Kapasitas Dana</h2></div>
                        <button id="preview-capacity" type="button" class="btn btn-neutral btn-sm">Hitung kapasitas terbaru</button>
                    </div>
                    <p class="mt-2 text-sm text-base-content/60">Dampak gabungan memakai saldo Posted Financial V2, Allocation berjalan, dan Planning disetujui yang belum dikonversi.</p>
                    <div id="capacity-output" class="mt-4 grid gap-3 md:grid-cols-2" aria-live="polite"><div class="rounded-2xl border border-dashed border-base-300 p-5 text-sm text-base-content/55">Pilih Dana dan nominal untuk menampilkan dampaknya.</div></div>
                </section>

                <div class="flex flex-wrap justify-end gap-3"><a class="btn btn-ghost" href="{{ route('financial-v2.plannings.index', ['entity' => $entity->id]) }}">Batal</a><button class="btn btn-primary" type="submit">Simpan Draft Planning</button></div>
            </div>

            <aside id="planning-summary" class="order-last rounded-3xl bg-emerald-950 p-5 text-emerald-50 shadow-sm lg:sticky lg:self-start" style="top: 7rem">
                <div class="text-xs font-bold uppercase tracking-[.18em] text-emerald-300">Ringkasan</div>
                <dl class="mt-5 space-y-4 text-sm">
                    <div><dt class="text-emerald-200/70">Total Planning</dt><dd id="summary-planning" class="mt-1 text-xl font-bold">Rp0</dd></div>
                    <div><dt class="text-emerald-200/70">Total Funding</dt><dd id="summary-funding" class="mt-1 text-xl font-bold">Rp0</dd></div>
                    <div class="border-t border-emerald-800 pt-4"><dt class="text-emerald-200/70">Selisih</dt><dd id="summary-variance" class="mt-1 text-xl font-bold">Rp0</dd></div>
                    <div class="grid grid-cols-2 gap-3 border-t border-emerald-800 pt-4 text-xs">
                        <div><dt class="text-emerald-200/70">Target Penerima</dt><dd id="summary-recipients" class="mt-1 font-bold">—</dd></div>
                        <div><dt class="text-emerald-200/70">Nominal / Penerima</dt><dd id="summary-per-recipient" class="mt-1 font-bold">—</dd></div>
                    </div>
                </dl>
                <div id="summary-capacity" class="mt-5 rounded-2xl border border-emerald-800 bg-emerald-900/70 p-4" aria-live="polite">
                    <div id="summary-capacity-state" class="font-bold">Kapasitas belum dihitung</div>
                    <div id="summary-capacity-detail" class="mt-1 text-xs text-emerald-100/70">Pilih Dana untuk melihat batas aman Planning.</div>
                </div>
                <p class="mt-5 text-xs leading-relaxed text-emerald-100/70">Selisih harus nol. Penyimpanan hanya membuat data Planning; tidak membuat Transaction, Journal, Ledger, Voucher, Realization, atau Distribution.</p>
            </aside>
        </div>
    </form>

    <template id="funding-template">
        <article class="funding-line min-w-0 rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm">
            <div class="flex items-start gap-2">
                <label class="form-control min-w-0 flex-1"><span class="label-text font-semibold">Dana</span><select class="select select-bordered fund-select w-full" required><option value="">Pilih Dana</option>@foreach ($funds as $fund)<option value="{{ $fund->id }}">{{ $fund->code }} · {{ $fund->name }}</option>@endforeach</select></label>
                <button type="button" class="btn btn-ghost btn-sm mt-7 text-error remove-funding" aria-label="Hapus sumber Dana">Hapus</button>
            </div>
            <div class="fund-capacity mt-4 hidden" aria-live="polite"><dl class="grid grid-cols-2 gap-3 text-xs"><div><dt class="text-base-content/55">Saldo Aktual</dt><dd class="fund-actual mt-1 font-semibold">Rp0</dd></div><div><dt class="text-base-content/55">Allocation Berjalan</dt><dd class="fund-outstanding mt-1 font-semibold">Rp0</dd></div><div><dt class="text-base-content/55">Planning Disetujui</dt><dd class="fund-approved mt-1 font-semibold">Rp0</dd></div><div class="rounded-xl bg-emerald-50 p-3"><dt class="font-semibold text-emerald-800">Maks. Dapat Direncanakan</dt><dd class="fund-available mt-1 text-base font-bold text-emerald-800">Rp0</dd></div></dl></div>
            <p class="fund-capacity-empty mt-4 rounded-xl bg-base-200 p-3 text-xs text-base-content/60">Pilih Dana untuk menampilkan kapasitas terbaru.</p>
            <label class="form-control mt-4" data-money-field><span class="label-text font-semibold">Nominal Sumber Dana</span><input class="input input-bordered fund-amount-display" data-money-input inputmode="decimal" required placeholder="0"><input class="fund-amount" data-money-value type="hidden" value=""></label>
            <div class="mt-3 flex flex-wrap items-center justify-between gap-2"><button type="button" class="btn btn-outline btn-sm use-maximum" disabled>Gunakan Maksimal</button><span class="fund-state text-xs font-semibold text-base-content/60">Kapasitas belum dihitung</span></div>
            <label class="form-control mt-3"><span class="label-text text-xs">Catatan sumber Dana (opsional)</span><input class="input input-bordered fund-notes" placeholder="Catatan opsional"></label>
        </article>
    </template>
@endsection

@push('scripts')
<script>
(() => {
    const form = document.getElementById('planning-form');
    const lines = document.getElementById('funding-lines');
    const total = document.getElementById('planning-total');
    const recipientCount = document.getElementById('recipient-count');
    const perRecipient = document.getElementById('per-recipient');
    const capacityByFund = new Map();

    const normalizeMoney = value => {
        let clean = String(value ?? '').trim().replace(/^Rp\s*/i, '').replace(/\s+/g, '');
        if (clean === '') return '';
        if (clean.includes(',')) {
            const [whole, fraction = ''] = clean.split(',', 2);
            const decimals = fraction.replace(/\D/g, '').slice(0, 2);
            clean = whole.replace(/\D/g, '') + (decimals ? '.' + decimals : '');
        } else if (/^\d{1,3}(?:\.\d{3})+$/.test(clean)) {
            clean = clean.replace(/\./g, '');
        }
        return /^\d+(?:\.\d{1,2})?$/.test(clean) ? clean : clean.replace(/\D/g, '');
    };
    const normalizeCount = value => String(value ?? '').replace(/\D/g, '');
    const formatInteger = digits => (digits === '' ? '' : BigInt(digits).toLocaleString('id-ID'));
    const formatMoney = value => {
        const raw = normalizeMoney(value);
        if (raw === '') return '';
        const [whole, fraction = ''] = raw.split('.');
        return formatInteger(whole) + (fraction && fraction !== '00' ? ',' + fraction : '');
    };
    const parseCents = value => {
        const raw = normalizeMoney(value);
        if (!/^\d+(?:\.\d{1,2})?$/.test(raw)) return 0n;
        const [whole, fraction = ''] = raw.split('.');
        return BigInt(whole) * 100n + BigInt((fraction + '00').slice(0, 2));
    };
    const decimal = cents => `${cents / 100n}.${String(cents < 0n ? -(cents % 100n) : cents % 100n).padStart(2, '0')}`;
    const rupiah = cents => `${cents < 0n ? '-' : ''}Rp${formatInteger(String((cents < 0n ? -cents : cents) / 100n))}${(cents < 0n ? -cents : cents) % 100n === 0n ? '' : ',' + String((cents < 0n ? -cents : cents) % 100n).padStart(2, '0')}`;
    const escapeHtml = value => String(value).replace(/[&<>"']/g, character => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[character]));
    const fundingCards = () => [...lines.querySelectorAll('.funding-line')];

    const reindex = () => fundingCards().forEach((card, index) => {
        card.querySelector('.fund-select').name = `fundings[${index}][fund_id]`;
        card.querySelector('.fund-amount').name = `fundings[${index}][amount]`;
        card.querySelector('.fund-notes').name = `fundings[${index}][notes]`;
    });

    const updateCardCapacity = card => {
        if (!card) return;
        const fundId = card.querySelector('.fund-select').value;
        const capacity = capacityByFund.get(fundId);
        const panel = card.querySelector('.fund-capacity');
        const empty = card.querySelector('.fund-capacity-empty');
        const maximumButton = card.querySelector('.use-maximum');
        if (!fundId || !capacity) {
            panel.classList.add('hidden'); empty.classList.remove('hidden'); maximumButton.disabled = true;
            card.querySelector('.fund-state').textContent = fundId ? 'Kapasitas belum dihitung' : 'Pilih Dana';
            return;
        }
        const available = parseCents(capacity.available) > 0n ? parseCents(capacity.available) : 0n;
        const requested = parseCents(card.querySelector('.fund-amount').value);
        const projected = available - requested;
        panel.classList.remove('hidden'); empty.classList.add('hidden'); maximumButton.disabled = available === 0n;
        card.dataset.available = decimal(available);
        card.querySelector('.fund-actual').textContent = rupiah(parseCents(capacity.actual));
        card.querySelector('.fund-outstanding').textContent = rupiah(parseCents(capacity.outstanding));
        card.querySelector('.fund-approved').textContent = rupiah(parseCents(capacity.approved));
        card.querySelector('.fund-available').textContent = rupiah(available);
        const state = card.querySelector('.fund-state');
        state.textContent = projected >= 0n ? `Tersisa ${rupiah(projected)}` : `Kekurangan ${rupiah(-projected)}`;
        state.className = `fund-state text-xs font-semibold ${projected >= 0n ? 'text-emerald-700' : 'text-error'}`;
    };

    const currentCapacityRows = () => fundingCards().map(card => {
        const select = card.querySelector('.fund-select');
        const capacity = capacityByFund.get(select.value);
        if (!select.value || !capacity) return null;
        const requested = parseCents(card.querySelector('.fund-amount').value);
        const available = parseCents(capacity.available) > 0n ? parseCents(capacity.available) : 0n;
        return {card, capacity, requested, available, projected: available - requested, label: select.options[select.selectedIndex]?.text || ''};
    }).filter(Boolean);

    const renderCapacityOutput = () => {
        const selected = fundingCards().filter(card => card.querySelector('.fund-select').value);
        const rows = currentCapacityRows();
        const output = document.getElementById('capacity-output');
        if (selected.length === 0) {
            output.innerHTML = '<div class="rounded-2xl border border-dashed border-base-300 p-5 text-sm text-base-content/55">Pilih Dana dan nominal untuk menampilkan dampaknya.</div>';
            return;
        }
        if (rows.length !== selected.length) {
            output.innerHTML = '<div class="rounded-2xl border border-dashed border-base-300 p-5 text-sm text-base-content/55">Mengambil kapasitas canonical terbaru…</div>';
            return;
        }
        output.innerHTML = rows.map(row => `<article class="rounded-2xl border ${row.projected >= 0n ? 'border-emerald-300 bg-emerald-50' : 'border-error/40 bg-error/5'} p-4"><div class="flex flex-wrap justify-between gap-3"><strong>${escapeHtml(row.label)}</strong><span class="badge ${row.projected >= 0n ? 'badge-success' : 'badge-error'}">${row.projected >= 0n ? 'Dana Mencukupi' : 'Dana Tidak Mencukupi'}</span></div><dl class="mt-4 grid grid-cols-2 gap-3 text-xs"><div><dt class="text-base-content/55">Saldo Aktual</dt><dd class="font-semibold">${rupiah(parseCents(row.capacity.actual))}</dd></div><div><dt class="text-base-content/55">Allocation Berjalan</dt><dd class="font-semibold">${rupiah(parseCents(row.capacity.outstanding))}</dd></div><div><dt class="text-base-content/55">Planning Disetujui</dt><dd class="font-semibold">${rupiah(parseCents(row.capacity.approved))}</dd></div><div><dt class="text-base-content/55">Maks. Dapat Direncanakan</dt><dd class="font-bold text-emerald-700">${rupiah(row.available)}</dd></div><div><dt class="text-base-content/55">Nominal Sumber Dana</dt><dd class="font-semibold">${rupiah(row.requested)}</dd></div><div><dt class="text-base-content/55">Setelah Planning</dt><dd class="font-bold ${row.projected >= 0n ? 'text-emerald-700' : 'text-error'}">${row.projected >= 0n ? rupiah(row.projected) : 'Kekurangan ' + rupiah(-row.projected)}</dd></div></dl></article>`).join('');
    };

    const summarize = () => {
        const planning = parseCents(total.value);
        const funding = fundingCards().reduce((sum, card) => sum + parseCents(card.querySelector('.fund-amount').value), 0n);
        const variance = funding - planning;
        document.getElementById('summary-planning').textContent = rupiah(planning);
        document.getElementById('summary-funding').textContent = rupiah(funding);
        const varianceNode = document.getElementById('summary-variance');
        varianceNode.textContent = rupiah(variance);
        varianceNode.className = `mt-1 text-xl font-bold ${variance === 0n ? 'text-emerald-300' : 'text-amber-300'}`;
        const count = normalizeCount(recipientCount.value);
        document.getElementById('summary-recipients').textContent = count === '' ? '—' : formatInteger(count);
        document.getElementById('summary-per-recipient').textContent = normalizeMoney(perRecipient.value) === '' ? '—' : rupiah(parseCents(perRecipient.value));

        const selected = fundingCards().filter(card => card.querySelector('.fund-select').value);
        const rows = currentCapacityRows();
        const state = document.getElementById('summary-capacity-state');
        const detail = document.getElementById('summary-capacity-detail');
        if (selected.length === 0 || rows.length !== selected.length) {
            state.textContent = 'Kapasitas belum dihitung'; detail.textContent = 'Pilih Dana untuk melihat batas aman Planning.';
        } else {
            const shortfall = rows.reduce((sum, row) => sum + (row.projected < 0n ? -row.projected : 0n), 0n);
            state.textContent = shortfall === 0n ? 'Dana Mencukupi' : 'Dana Tidak Mencukupi';
            detail.textContent = shortfall === 0n ? 'Seluruh sumber Dana berada dalam batas aman.' : `Kekurangan ${rupiah(shortfall)}`;
            state.className = `font-bold ${shortfall === 0n ? 'text-emerald-200' : 'text-amber-300'}`;
        }
    };

    const refreshCapacity = async () => {
        const selected = fundingCards().filter(card => card.querySelector('.fund-select').value);
        if (selected.length === 0) { renderCapacityOutput(); summarize(); return; }
        const button = document.getElementById('preview-capacity');
        button.disabled = true; button.textContent = 'Menghitung…';
        const data = new FormData();
        data.append('entity', form.querySelector('[name="entity"]').value);
        data.append('period_start', form.querySelector('[name="period_start"]').value);
        if (form.querySelector('[name="planning_id"]')) data.append('planning_id', form.querySelector('[name="planning_id"]').value);
        selected.forEach((card, index) => {
            data.append(`fundings[${index}][fund_id]`, card.querySelector('.fund-select').value);
            data.append(`fundings[${index}][amount]`, normalizeMoney(card.querySelector('.fund-amount').value) || '0.00');
        });
        try {
            const response = await fetch(form.dataset.previewUrl, {method: 'POST', headers: {'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: data});
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.message || 'Pratinjau gagal.');
            payload.lines.forEach(line => capacityByFund.set(line.fund.id, line));
            fundingCards().forEach(updateCardCapacity); renderCapacityOutput(); summarize();
        } catch (error) {
            document.getElementById('capacity-output').innerHTML = `<div class="alert alert-error">${escapeHtml(error.message)}</div>`;
            document.getElementById('summary-capacity-state').textContent = 'Kapasitas tidak tersedia';
            document.getElementById('summary-capacity-detail').textContent = error.message;
        } finally {
            button.disabled = false; button.textContent = 'Hitung kapasitas terbaru';
        }
    };

    const calculateRecipientTotal = () => {
        const count = normalizeCount(recipientCount.value);
        const amount = normalizeMoney(perRecipient.value);
        if (/^\d+$/.test(count) && /^\d+(?:\.\d{1,2})?$/.test(amount)) {
            total.value = decimal(BigInt(count) * parseCents(amount));
            document.getElementById('planning-total-display').value = formatMoney(total.value);
            total.dispatchEvent(new Event('input', {bubbles: true}));
        }
        summarize();
    };

    const wireDynamicMoneyField = field => {
        const display = field.querySelector('[data-money-input]');
        const value = field.querySelector('[data-money-value]');
        display.addEventListener('input', () => {
            value.value = normalizeMoney(display.value);
            display.value = formatMoney(value.value);
            value.dispatchEvent(new Event('input', {bubbles: true}));
        });
    };

    lines.addEventListener('click', event => {
        const remove = event.target.closest('.remove-funding');
        if (remove && fundingCards().length > 1) { remove.closest('.funding-line').remove(); reindex(); renderCapacityOutput(); summarize(); return; }
        const maximum = event.target.closest('.use-maximum');
        if (maximum && !maximum.disabled) {
            const card = maximum.closest('.funding-line');
            const amount = card.querySelector('.fund-amount');
            amount.value = card.dataset.available || '0.00';
            card.querySelector('.fund-amount-display').value = formatMoney(amount.value);
            amount.dispatchEvent(new Event('input', {bubbles: true}));
            updateCardCapacity(card); renderCapacityOutput(); summarize();
        }
    });
    lines.addEventListener('change', event => {
        if (event.target.matches('.fund-select')) { updateCardCapacity(event.target.closest('.funding-line')); renderCapacityOutput(); summarize(); refreshCapacity(); }
    });
    lines.addEventListener('input', event => {
        updateCardCapacity(event.target.closest('.funding-line')); renderCapacityOutput(); summarize();
    });
    document.getElementById('add-funding').addEventListener('click', () => {
        lines.append(document.getElementById('funding-template').content.cloneNode(true));
        wireDynamicMoneyField(fundingCards().at(-1).querySelector('[data-money-field]'));
        reindex(); renderCapacityOutput(); summarize();
    });
    document.getElementById('preview-capacity').addEventListener('click', refreshCapacity);
    total.addEventListener('input', summarize);
    recipientCount.addEventListener('input', () => { recipientCount.value = formatInteger(normalizeCount(recipientCount.value)); calculateRecipientTotal(); });
    perRecipient.addEventListener('input', calculateRecipientTotal);
    form.addEventListener('submit', () => { recipientCount.value = normalizeCount(recipientCount.value); });

    recipientCount.value = formatInteger(normalizeCount(recipientCount.value));
    reindex(); summarize();
    if (fundingCards().some(card => card.querySelector('.fund-select').value)) refreshCapacity();
})();
</script>
@endpush
