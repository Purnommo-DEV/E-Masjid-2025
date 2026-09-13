@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', ($transaction ? 'Ubah Draft ' : 'Tambah ').$definition['label'])

@section('content')
    @php
        $split = $transaction?->splits->first();
        $value = fn ($key, $default = null) => old($key, $default);
        $sourceDefault = $transaction && preg_match('/^Sumber:\s*(.*?)\n\n/s', (string) $transaction->description, $matches) ? $matches[1] : null;
        $descriptionDefault = $transaction ? preg_replace('/^Sumber:\s*.*?\n\n/s', '', (string) $transaction->description) : null;
        $isEdit = (bool) $transaction;
        $amountValue = $value('amount', $transaction?->gross_amount);
        $counterpartyName = $value('counterparty_name', $transaction?->counterparty?->display_name);
        $fundForAllocation = fn ($version) => $version->fundings->pluck('fund.name')->filter()->join(' + ') ?: ($options['funds']->firstWhere('id', $version->allocation?->fund_id)?->name ?? 'Dana');
        $programForAllocation = fn ($version) => $options['programs']->firstWhere('id', $version->allocation?->program_id)?->name;
        $selectedAllocationVersionId = $selectedAllocationVersionId ?? null;
        $realizationSources = old('funding_sources');
        if ($operation === 'realization' && $realizationSources === null) {
            $realizationSources = $transaction
                ? $transaction->splits->map(fn ($item) => ['fund_id' => $item->fund_id, 'amount' => $item->split_amount, 'note' => $item->purpose_note, 'source_reference' => $item->source_reference])->values()->all()
                : [];
        }
        $backUrl = $operation === 'realization'
            ? route('financial-v2.realizations.drafts', ['entity' => $entity?->id])
            : route('financial-v2.transactions.drafts', ['entity' => $entity?->id, 'year' => $transaction?->accounting_date?->year]);
        $backLabel = $operation === 'realization' ? 'Draft Realisasi' : 'Draft Transaksi';
    @endphp
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a class="link text-sm text-base-content/60" href="{{ $backUrl }}">← Kembali ke {{ $backLabel }}</a>
            <h1 class="mt-2 text-2xl font-bold">{{ $isEdit ? 'Ubah draft' : 'Tambah' }} {{ $definition['label'] }}</h1>
            <p class="mt-1 text-sm text-base-content/65">Isi kejadian yang terjadi. Sistem menerjemahkannya ke pencatatan keuangan secara otomatis.</p>
        </div>
        <span class="badge badge-outline">{{ $isEdit ? 'DRAFT' : 'DRAFT BARU' }}</span>
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih satu entitas keuangan aktif terlebih dahulu. Tidak ada master, saldo awal, atau transaksi aktual yang dibuat otomatis.</span></div>
        @if ($entities->isNotEmpty())
            <form method="GET" class="mt-4 flex max-w-md gap-2"><select name="entity" class="select select-bordered grow"><option value="">Pilih entitas</option>@foreach($entities as $availableEntity)<option value="{{ $availableEntity->id }}">{{ $availableEntity->name }}</option>@endforeach</select><button class="btn btn-primary">Pilih</button></form>
        @endif
    @else
        <form method="POST" action="{{ $isEdit ? route('financial-v2.transactions.update', $transaction) : route('financial-v2.transactions.store', $operation) }}" enctype="multipart/form-data" data-financial-ajax data-operation="{{ $operation }}" data-preview-url="{{ route('financial-v2.preview') }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <input type="hidden" name="submission_key" value="{{ $submissionKey }}">

            <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text font-medium">Tanggal</span><input type="date" name="date" value="{{ $value('date', $transaction?->accounting_date?->toDateString() ?? $today) }}" class="input input-bordered w-full" required></label>
                    <label class="form-control"><span class="label-text font-medium">Nominal</span><div class="relative" data-money-field><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="amount" value="{{ $amountValue }}" data-money-value><input type="text" inputmode="decimal" autocomplete="off" value="{{ $amountValue !== null && $amountValue !== '' ? number_format((float) $amountValue, 2, ',', '.') : '' }}" placeholder="0" class="input input-bordered w-full pl-9 text-lg font-semibold" data-money-input required></div><span class="label-text-alt">Pemisah ribuan dibuat otomatis. Gunakan koma untuk sen.</span></label>
                </div>

                @if ($operation === 'receipt')
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text font-medium">Sumber</span><input type="text" name="source" value="{{ $value('source', $sourceDefault) }}" placeholder="Contoh: Infak Jumat" class="input input-bordered w-full" required></label>
                        <label class="form-control"><span class="label-text font-medium">Masuk ke kas/rekening</span><select name="financial_account_id" class="select select-bordered w-full" required><option value="">Pilih kas/rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected($value('financial_account_id', $transaction?->primary_financial_account_id) === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Dana</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected($value('fund_id', $split?->fund_id) === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Kategori</span><select name="category_id" class="select select-bordered w-full" required><option value="">Pilih kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected($value('category_id', $transaction?->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    </div>
                    <label class="form-control mt-4"><span class="label-text font-medium">Program <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="program_id" class="select select-bordered w-full"><option value="">Tanpa program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected($value('program_id', $split?->program_id) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                @elseif (in_array($operation, ['payment', 'realization'], true))
                    @if ($operation === 'realization')
                        <label class="form-control mt-4"><span class="label-text font-medium">Alokasi dana</span><select name="budget_allocation_version_id" data-realization-allocation class="select select-bordered w-full" required @disabled($isEdit)><option value="">Pilih alokasi yang sudah disetujui</option>@foreach($options['allocationVersions'] as $version)<option value="{{ $version->id }}" data-allocated="{{ $version->availability['allocated'] }}" data-actual="{{ $version->availability['actual'] }}" data-available="{{ $version->availability['available'] }}" data-fundings='@json($version->funding_availability)' @selected($value('budget_allocation_version_id', $transaction?->realization?->budget_allocation_version_id ?? $selectedAllocationVersionId) === $version->id)>{{ $fundForAllocation($version) }}{{ $programForAllocation($version) ? ' · '.$programForAllocation($version) : '' }} · total Rp{{ number_format((float) $version->availability['allocated'], 2, ',', '.') }} · sisa Rp{{ number_format((float) $version->availability['available'], 2, ',', '.') }}</option>@endforeach</select>@if($isEdit)<input type="hidden" name="budget_allocation_version_id" value="{{ $transaction?->realization?->budget_allocation_version_id }}">@endif<span class="label-text-alt">Alokasi hanya menentukan peruntukan. Realisasi akan dicatat sebagai pengeluaran resmi.</span></label>
                        <div data-realization-summary class="mt-3 hidden rounded-xl bg-base-200 px-3 py-3 text-sm"></div>
                        <section class="mt-4 rounded-xl border border-base-300 bg-base-200/40 p-3 sm:p-4" data-realization-funding data-initial-fundings='@json($realizationSources)'>
                            <div class="flex flex-wrap items-center justify-between gap-2"><div><h2 class="font-semibold">Sumber Dana</h2><p class="mt-1 text-xs text-base-content/60">Rinci bagian realisasi yang dibebankan ke setiap Dana pada alokasi.</p></div><button type="button" class="btn btn-outline btn-sm" data-add-realization-funding>+ Tambah Sumber Dana</button></div>
                            <div class="mt-3 space-y-3" data-realization-funding-lines></div>
                            <div class="mt-3 rounded-xl bg-base-100 px-3 py-3 text-sm"><div class="flex justify-between gap-3"><span>Total Sumber</span><strong data-realization-funding-total>Rp0,00</strong></div><p class="mt-1 text-xs text-error" data-realization-funding-status>Total sumber ≠ Nominal realisasi</p></div>
                        </section>
                    @endif
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control sm:col-span-2 {{ $operation === 'realization' ? 'rounded-xl border border-primary/20 bg-primary/5 p-3' : '' }}"><span class="label-text font-semibold">Dibayarkan kepada</span><input type="text" name="counterparty_name" value="{{ $counterpartyName }}" list="counterparty-options" placeholder="Contoh: Penerima Santunan Anak Yatim" class="input input-bordered w-full" required><datalist id="counterparty-options">@foreach($options['counterparties'] as $counterparty)<option value="{{ $counterparty->display_name }}">@endforeach</datalist><span class="label-text-alt">Ketik nama penerima. Nama yang belum ada akan disimpan sebagai pihak penerima agar dapat dipakai lagi.</span></label>
                        <label class="form-control"><span class="label-text font-medium">Dibayar dari kas/rekening</span><select name="financial_account_id" class="select select-bordered w-full" required><option value="">Pilih kas/rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected($value('financial_account_id', $transaction?->primary_financial_account_id) === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                        @if ($operation === 'payment')
                            <label class="form-control"><span class="label-text font-medium">Dana</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected($value('fund_id', $split?->fund_id) === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                        @endif
                        <label class="form-control"><span class="label-text font-medium">Kategori</span><select name="category_id" class="select select-bordered w-full" required><option value="">Pilih kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected($value('category_id', $transaction?->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    </div>
                    @if ($operation === 'payment')
                        <label class="form-control mt-4"><span class="label-text font-medium">Program <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="program_id" class="select select-bordered w-full"><option value="">Tanpa program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected($value('program_id', $split?->program_id) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                    @endif
                @elseif ($operation === 'transfer')
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text font-medium">Rekening asal</span><select name="source_financial_account_id" class="select select-bordered w-full" required><option value="">Pilih rekening asal</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Rekening tujuan</span><select name="destination_financial_account_id" class="select select-bordered w-full" required><option value="">Pilih rekening tujuan</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Dana yang dipindahkan</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select><span class="label-text-alt">Transfer bukan pemasukan atau pengeluaran.</span></label>
                    </div>
                @elseif ($operation === 'interfund')
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text font-medium">Dana asal</span><select name="source_fund_id" class="select select-bordered w-full" required><option value="">Pilih dana asal</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Dana tujuan</span><select name="destination_fund_id" class="select select-bordered w-full" required><option value="">Pilih dana tujuan</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select></label>
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Rekening atribusi <span class="font-normal text-base-content/55">(saldo tidak berpindah)</span></span><select name="financial_account_id" class="select select-bordered w-full" required><option value="">Pilih lokasi uang yang direklasifikasi</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected($value('financial_account_id', $transaction?->primary_financial_account_id) === $account->id)>{{ $account->name }}</option>@endforeach</select><span class="label-text-alt">Pilihan ini hanya menjelaskan bagian saldo rekening yang berubah peruntukan Dana; kas/bank tidak dipindahkan.</span></label>
                    </div>
                    <label class="form-control mt-4"><span class="label-text font-medium">Rujukan kebijakan</span><input type="text" name="policy_basis_ref" value="{{ $value('policy_basis_ref') }}" placeholder="Contoh: SK/Notulen persetujuan" class="input input-bordered w-full" required><span class="label-text-alt">Dana terikat tetap fail-closed bila matriks kebijakannya tidak mengizinkan.</span></label>
                    <label class="form-control mt-4"><span class="label-text font-medium">Alasan pindah dana</span><textarea name="reason" rows="3" class="textarea textarea-bordered w-full" required>{{ $value('reason') }}</textarea></label>
                @endif

                <label class="form-control mt-4"><span class="label-text font-medium">Keterangan</span><textarea name="description" rows="3" class="textarea textarea-bordered w-full" placeholder="Tambahkan keterangan agar mudah ditelusuri.">{{ $value('description', $descriptionDefault) }}</textarea></label>
                <section class="mt-4 rounded-xl border border-base-300 bg-base-200/35 p-3 sm:p-4" data-evidence-uploads data-default-evidence="{{ $definition['evidence'] }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div><h2 class="font-semibold">Lampiran bukti</h2><p class="mt-1 text-xs leading-5 text-base-content/60">JPG, PNG, WEBP, atau PDF; maksimal 10 MB per file. Gambar disimpan sebagai WebP teroptimasi tanpa mengubah proporsi.</p></div>
                        <button type="button" class="btn btn-outline btn-sm" data-add-evidence>+ Tambah Lampiran</button>
                    </div>
                    <div class="mt-3 space-y-3" data-evidence-lines>
                        <div class="grid gap-3 rounded-xl bg-base-100 p-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]" data-evidence-line>
                            <label class="form-control"><span class="label-text text-xs">File bukti</span><input type="file" name="attachments[0]" accept="image/jpeg,image/png,image/webp,application/pdf" class="file-input file-input-bordered w-full"></label>
                            <label class="form-control"><span class="label-text text-xs">Jenis bukti</span><select name="attachment_types[0]" class="select select-bordered w-full"><option value="receipt" @selected($definition['evidence'] === 'receipt')>Tanda terima</option><option value="invoice" @selected($definition['evidence'] === 'invoice')>Invoice/tagihan</option><option value="transfer_proof" @selected($definition['evidence'] === 'transfer_proof')>Bukti transfer</option><option value="statement">Rekening koran</option><option value="cash_count">Perhitungan kas</option><option value="approval">Persetujuan</option><option value="policy" @selected($definition['evidence'] === 'policy')>Dokumen kebijakan</option><option value="other">Lainnya</option></select></label>
                            <button type="button" class="btn btn-ghost btn-sm self-end text-error" data-remove-evidence disabled>Hapus</button>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-base-content/55">Draft dapat menambah atau melepas bukti dengan audit. Bukti transaksi yang sudah diajukan/posted tidak dihapus.</p>
                </section>

                @if ($operation !== 'realization')
                    <div class="mt-4 rounded-xl border border-base-300 bg-base-200/40 px-3 py-3 text-sm" data-financial-configuration aria-live="polite">
                        <div class="flex items-center gap-2"><span class="loading loading-spinner loading-xs hidden" data-configuration-loading></span><strong>Status konfigurasi</strong></div>
                        <p class="mt-1 text-xs text-base-content/60" data-configuration-message>Lengkapi kombinasi transaksi untuk memeriksa konfigurasi.</p>
                    </div>
                @endif
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a class="btn btn-ghost" href="{{ $backUrl }}">Batal</a><button type="submit" class="btn btn-primary" @if($operation === 'realization') data-realization-funding-submit @else data-configuration-submit disabled @endif>{{ $isEdit ? 'Simpan perubahan draft' : 'Simpan sebagai draft' }}</button></div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950"><p class="font-bold">Cara kerja pencatatan</p><ol class="mt-2 list-decimal space-y-1 pl-5 text-xs leading-5"><li>Simpan dulu sebagai draft.</li><li>Periksa data dan lampiran bukti.</li><li>Ajukan draft dari detail transaksi saat datanya siap.</li></ol></div>
                <div class="rounded-2xl bg-base-100 p-4 text-sm shadow-sm ring-1 ring-base-300"><p class="font-bold">Status transaksi</p><dl class="mt-3 space-y-2 text-xs"><div><dt class="font-semibold">Draft</dt><dd class="text-base-content/60">Masih dapat diperiksa dan diubah.</dd></div><div><dt class="font-semibold">Diajukan / Disetujui</dt><dd class="text-base-content/60">Sedang melalui pemeriksaan atau persetujuan yang berlaku.</dd></div><div><dt class="font-semibold">Dicatat resmi</dt><dd class="text-base-content/60">Sudah dicatat secara resmi dan tidak dapat diubah langsung.</dd></div></dl></div>
            </aside>
        </form>
    @endif
@endsection

@if ($operation === 'realization' && $entity)
@push('scripts')
<script>
(() => {
    const section = document.querySelector('[data-realization-funding]');
    const allocation = document.querySelector('[data-realization-allocation]');
    const form = section?.closest('form');
    if (!section || !allocation || !form) return;
    const lines = section.querySelector('[data-realization-funding-lines]');
    const totalOutput = section.querySelector('[data-realization-funding-total]');
    const statusOutput = section.querySelector('[data-realization-funding-status]');
    const submit = form.querySelector('[data-realization-funding-submit]');
    const target = form.querySelector('input[name="amount"]');
    let initial = JSON.parse(section.dataset.initialFundings || '[]');
    let nextIndex = 0;
    const parse = (raw) => {
        const cleaned = String(raw || '').replace(/[^\d,]/g, '');
        const comma = cleaned.lastIndexOf(',');
        const whole = (comma >= 0 ? cleaned.slice(0, comma) : cleaned).replace(/\D/g, '') || '0';
        const fraction = comma >= 0 ? cleaned.slice(comma + 1).replace(/\D/g, '').slice(0, 2) : '';
        return { canonical: fraction ? `${BigInt(whole)}.${fraction}` : `${BigInt(whole)}`, cents: BigInt(whole) * 100n + BigInt((fraction + '00').slice(0, 2)), fraction };
    };
    const canonicalCents = (raw) => { const match = String(raw || '').match(/^(\d+)(?:\.(\d{1,2}))?$/); return match ? BigInt(match[1]) * 100n + BigInt(((match[2] || '') + '00').slice(0, 2)) : 0n; };
    const display = (cents) => `Rp${(cents / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')},${(cents % 100n).toString().padStart(2, '0')}`;
    const escape = (raw) => String(raw || '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;').replaceAll("'", '&#039;');
    const permitted = () => { try { return JSON.parse(allocation.options[allocation.selectedIndex]?.dataset.fundings || '[]'); } catch (_) { return []; } };
    const row = (source = {}) => {
        const options = permitted().map((funding) => `<option value="${escape(funding.fund_id)}" ${source.fund_id === funding.fund_id ? 'selected' : ''}>${escape(funding.fund_name)} · sisa ${display(canonicalCents(funding.available))}</option>`).join('');
        const index = nextIndex++;
        const amount = source.amount || '';
        const formatted = amount ? display(canonicalCents(amount)).replace(/^Rp/, '') : '';
        lines.insertAdjacentHTML('beforeend', `<div class="rounded-xl border border-base-300 bg-base-100 p-3" data-realization-funding-line><div class="mb-2 flex items-center justify-between"><span class="text-sm font-semibold" data-realization-funding-label>Sumber Dana</span><button type="button" class="btn btn-ghost btn-xs text-error" data-remove-realization-funding>Hapus</button></div><div class="grid gap-3 sm:grid-cols-2"><label class="form-control"><span class="label-text text-xs">Dana</span><select name="funding_sources[${index}][fund_id]" class="select select-bordered w-full" required><option value="">Pilih dana</option>${options}</select></label><label class="form-control"><span class="label-text text-xs">Nominal sumber</span><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="funding_sources[${index}][amount]" value="${escape(amount)}" data-realization-funding-value><input type="text" inputmode="decimal" value="${escape(formatted)}" class="input input-bordered w-full pl-9" data-realization-funding-input required></div></label><label class="form-control sm:col-span-2"><span class="label-text text-xs">Catatan / referensi <span class="font-normal text-base-content/50">(opsional)</span></span><input type="text" name="funding_sources[${index}][note]" value="${escape(source.note)}" class="input input-bordered w-full" maxlength="1000"></label></div></div>`);
        bind(lines.lastElementChild); renumber();
    };
    const bind = (line) => {
        const input = line.querySelector('[data-realization-funding-input]');
        const hidden = line.querySelector('[data-realization-funding-value]');
        input.addEventListener('input', () => { const value = parse(input.value); hidden.value = value.canonical; input.value = `${(value.cents / 100n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.')}${value.fraction ? ',' + value.fraction : ''}`; render(); });
        line.querySelector('[data-remove-realization-funding]').addEventListener('click', () => { if (lines.children.length > 1) line.remove(); renumber(); render(); });
    };
    const renumber = () => [...lines.querySelectorAll('[data-realization-funding-line]')].forEach((line, index) => line.querySelector('[data-realization-funding-label]').textContent = `Sumber Dana #${index + 1}`);
    const render = () => {
        const total = [...lines.querySelectorAll('[data-realization-funding-value]')].reduce((sum, field) => sum + canonicalCents(field.value), 0n);
        const expected = canonicalCents(target.value);
        const balanced = expected > 0n && total === expected && lines.children.length > 0;
        totalOutput.textContent = display(total);
        statusOutput.textContent = balanced ? '✓ Seimbang dengan Nominal Realisasi' : 'Total sumber ≠ Nominal realisasi';
        statusOutput.className = `mt-1 text-xs ${balanced ? 'text-success' : 'text-error'}`;
        submit.disabled = !balanced;
    };
    const resetForAllocation = () => {
        lines.innerHTML = ''; nextIndex = 0;
        permitted().forEach((funding) => row({ fund_id: funding.fund_id, amount: funding.available }));
        render();
    };
    allocation.addEventListener('change', resetForAllocation);
    section.querySelector('[data-add-realization-funding]').addEventListener('click', () => { if (permitted().length) row({}); render(); });
    target.addEventListener('input', render);
    if (initial.length) initial.forEach(row); else resetForAllocation();
    render();
})();
</script>
@endpush
@endif

@if ($entity)
@push('scripts')
<script>
(() => {
    const section = document.querySelector('[data-evidence-uploads]');
    if (!section) return;
    const lines = section.querySelector('[data-evidence-lines]');
    const add = section.querySelector('[data-add-evidence]');
    const options = lines.querySelector('select').innerHTML;
    const renumber = () => {
        [...lines.querySelectorAll('[data-evidence-line]')].forEach((line, index) => {
            line.querySelector('input[type="file"]').name = `attachments[${index}]`;
            line.querySelector('select').name = `attachment_types[${index}]`;
            line.querySelector('[data-remove-evidence]').disabled = lines.children.length === 1;
        });
        add.disabled = lines.children.length >= 10;
    };
    const bind = (line) => line.querySelector('[data-remove-evidence]').addEventListener('click', () => {
        if (lines.children.length > 1) line.remove();
        renumber();
    });
    [...lines.querySelectorAll('[data-evidence-line]')].forEach(bind);
    add.addEventListener('click', () => {
        if (lines.children.length >= 10) return;
        const line = document.createElement('div');
        line.className = 'grid gap-3 rounded-xl bg-base-100 p-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]';
        line.dataset.evidenceLine = '';
        line.innerHTML = `<label class="form-control"><span class="label-text text-xs">File bukti</span><input type="file" accept="image/jpeg,image/png,image/webp,application/pdf" class="file-input file-input-bordered w-full"></label><label class="form-control"><span class="label-text text-xs">Jenis bukti</span><select class="select select-bordered w-full">${options}</select></label><button type="button" class="btn btn-ghost btn-sm self-end text-error" data-remove-evidence>Hapus</button>`;
        const select = line.querySelector('select');
        if ([...select.options].some((item) => item.value === section.dataset.defaultEvidence)) select.value = section.dataset.defaultEvidence;
        lines.appendChild(line);
        bind(line);
        renumber();
    });
    renumber();
})();
</script>
@endpush
@endif
