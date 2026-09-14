@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', $batchTransactions->isNotEmpty() ? 'Ubah Batch Mutasi Bank' : 'Tambah Mutasi Bank')

@section('content')
    @php
        $isEdit = $batchTransactions->isNotEmpty();
        $firstTransaction = $batchTransactions->first();
        $initialRows = old('mutations');
        if ($initialRows === null) {
            $initialRows = $batchTransactions->map(fn ($item) => [
                'transaction_id' => $item->id,
                'category_id' => $item->category_id,
                'fund_id' => $item->splits->first()?->fund_id,
                'amount' => $item->gross_amount,
                'description' => $item->description,
                'source_reference' => $item->source_reference,
            ])->values()->all();
        }
        $initialRows = $initialRows ?: [[
            'transaction_id' => null,
            'category_id' => null,
            'fund_id' => $options['defaultFundId'],
            'amount' => null,
            'description' => null,
            'source_reference' => null,
        ]];
        $selectedAccount = old('financial_account_id', $firstTransaction?->primary_financial_account_id ?? $options['financialAccounts']->first()?->id);
        $selectedDate = old('date', $firstTransaction?->accounting_date?->toDateString() ?? $today);
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.bank-mutations.index', ['entity' => $entity?->id]) }}">← Kembali ke Mutasi Bank</a>
            <h1 class="mt-2 text-2xl font-bold">{{ $isEdit ? 'Ubah batch' : 'Tambah' }} Mutasi Bank</h1>
            <p class="mt-1 text-sm text-base-content/65">Satu rekening koran dapat dipakai bersama oleh beberapa draft mutasi.</p>
        </div>
        <span class="badge badge-outline">{{ $isEdit ? 'BATCH DRAFT' : 'BATCH BARU' }}</span>
    </div>

    @if (! $entity)
        <div class="alert alert-warning">Pilih entitas keuangan aktif terlebih dahulu.</div>
        <form method="GET" class="mt-4 flex max-w-md gap-2">
            <select name="entity" class="select select-bordered grow" required><option value="">Pilih entitas</option>@foreach($entities as $available)<option value="{{ $available->id }}">{{ $available->name }}</option>@endforeach</select>
            <button class="btn btn-primary">Pilih</button>
        </form>
    @else
        <form method="POST"
              action="{{ $isEdit ? route('financial-v2.bank-mutations.batches.update', ['batch' => $batchId]) : route('financial-v2.bank-mutations.store') }}"
              enctype="multipart/form-data"
              class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_21rem]"
              data-bank-batch-form
              data-preview-url="{{ route('financial-v2.bank-mutations.preview') }}">
            @csrf
            @if($isEdit) @method('PUT') @endif
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <input type="hidden" name="bank_mutation_batch_id" value="{{ $batchId }}">

            <div class="min-w-0 space-y-5">
                <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
                    <h2 class="font-bold">Informasi rekening koran</h2>
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control">
                            <span class="label-text font-medium">Rekening</span>
                            <select name="financial_account_id" class="select select-bordered w-full" required>
                                @foreach($options['financialAccounts'] as $account)
                                    <option value="{{ $account->id }}" data-account-code="{{ $account->code }}" @selected($selectedAccount === $account->id)>{{ $account->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="form-control">
                            <span class="label-text font-medium">Tanggal</span>
                            <input type="date" name="date" value="{{ $selectedDate }}" class="input input-bordered w-full" required>
                        </label>
                    </div>
                    <label class="form-control mt-4">
                        <span class="label-text font-medium">Bukti rekening koran {{ $isEdit ? '(opsional jika tidak diganti)' : '' }}</span>
                        <input type="file" name="proof" accept="image/jpeg,image/png,image/webp,application/pdf,.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="file-input file-input-bordered w-full" @required(!$isEdit)>
                        <span class="label-text-alt">Satu PDF, gambar, XLS, atau XLSX dipakai bersama oleh seluruh mutasi dalam batch; maksimal 10 MB.</span>
                    </label>
                </section>

                <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div><h2 class="font-bold">Daftar Mutasi</h2><p class="text-xs text-base-content/60">Setiap baris tetap menjadi transaksi individual dengan Source Reference sendiri.</p></div>
                        <button type="button" class="btn btn-outline btn-sm self-start sm:self-auto" data-add-mutation>+ Tambah Mutasi</button>
                    </div>
                    <div class="mt-4 space-y-4" data-mutation-list></div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a class="btn btn-ghost" href="{{ route('financial-v2.bank-mutations.index', ['entity' => $entity->id]) }}">Batal</a>
                    <button class="btn btn-primary" data-bank-submit disabled>{{ $isEdit ? 'Simpan perubahan batch' : 'Simpan batch sebagai draft' }}</button>
                </div>
            </div>

            <aside class="min-w-0 space-y-4">
                <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 lg:sticky lg:top-24">
                    <div class="flex items-center justify-between gap-2"><h2 class="font-bold">Pratinjau Batch</h2><span class="loading loading-spinner loading-xs hidden" data-preview-loading></span></div>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-3"><dt class="text-base-content/60">Saldo saat ini</dt><dd class="text-right font-semibold" data-preview-opening>—</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-base-content/60">Total credit</dt><dd class="text-right font-semibold text-emerald-700" data-preview-credit>Rp0,00</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-base-content/60">Total debit</dt><dd class="text-right font-semibold text-rose-700" data-preview-debit>Rp0,00</dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-base-content/60">Net mutasi</dt><dd class="text-right font-semibold" data-preview-net>Rp0,00</dd></div>
                        <div class="flex justify-between gap-3 border-t border-base-300 pt-3"><dt class="font-semibold">Saldo setelah</dt><dd class="text-right font-bold text-emerald-700" data-preview-closing>—</dd></div>
                    </dl>
                    <p class="mt-3 text-xs leading-5 text-base-content/55" data-preview-note>Pratinjau saldo tersedia setelah data transaksi lengkap.</p>
                </section>
                <section class="rounded-2xl border border-base-300 bg-base-200/40 p-4 text-sm" data-bank-configuration aria-live="polite">
                    <h2 class="font-bold">Status Konfigurasi</h2>
                    <p class="mt-2 text-xs leading-5" data-bank-configuration-message>Lengkapi data transaksi untuk memeriksa konfigurasi.</p>
                </section>
            </aside>
        </form>

        <template data-mutation-template>
            <article class="min-w-0 rounded-2xl border border-base-300 bg-base-50 p-4" data-mutation-row>
                <input type="hidden" name="mutations[__INDEX__][transaction_id]" data-field="transaction_id">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <h3 class="font-semibold">Mutasi <span data-row-number></span></h3>
                    <button type="button" class="btn btn-ghost btn-xs text-error" data-remove-mutation>Hapus</button>
                </div>
                <div class="grid min-w-0 gap-3 md:grid-cols-12">
                    <label class="form-control min-w-0 md:col-span-4"><span class="label-text text-xs">Jenis Mutasi</span><select name="mutations[__INDEX__][category_id]" class="select select-bordered select-sm w-full" data-field="category_id" required><option value="">Pilih jenis</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" data-category-code="{{ $category->code }}">{{ \App\Domain\FinancialV2\BankMutationService::CATEGORY_CODES[$category->code] ?? $category->name }}</option>@endforeach</select></label>
                    <label class="form-control min-w-0 md:col-span-3"><span class="label-text text-xs">Nominal</span><div class="join w-full"><span class="join-item grid place-items-center border border-base-300 bg-base-200 px-2 text-xs">Rp</span><input type="number" min="0.01" step="0.01" name="mutations[__INDEX__][amount]" class="input input-sm join-item input-bordered min-w-0 w-full" data-field="amount" required></div></label>
                    <label class="form-control min-w-0 md:col-span-5"><span class="label-text text-xs">Dana</span><select name="mutations[__INDEX__][fund_id]" class="select select-bordered select-sm w-full" data-field="fund_id" required>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select></label>
                    <label class="form-control min-w-0 md:col-span-6"><span class="label-text text-xs">Keterangan</span><input type="text" maxlength="1000" name="mutations[__INDEX__][description]" class="input input-sm input-bordered w-full" data-field="description" required></label>
                    <label class="form-control min-w-0 md:col-span-6"><span class="label-text text-xs">Source Reference</span><input type="text" maxlength="160" name="mutations[__INDEX__][source_reference]" class="input input-sm input-bordered w-full font-mono text-xs" data-field="source_reference" data-auto-reference="true" placeholder="BNI-20260630-JASA-GIRO" required></label>
                </div>
                <div class="mt-3 flex flex-wrap justify-between gap-2 rounded-xl bg-base-200 px-3 py-2 text-xs"><span data-row-movement>—</span><span>Saldo: <strong data-row-balance>—</strong></span></div>
            </article>
        </template>
    @endif
@endsection

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-bank-batch-form]');
    if (!form || form.dataset.initialized === 'true') return;
    form.dataset.initialized = 'true';

    const list = form.querySelector('[data-mutation-list]');
    const template = document.querySelector('[data-mutation-template]');
    const initialRows = @json($initialRows);
    const defaultFundId = @json($options['defaultFundId']);
    const submit = form.querySelector('[data-bank-submit]');
    const currency = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR' });
    const suffixes = { BANK_INTEREST: 'JASA-GIRO', BANK_WHT_PPH: 'PPH', BANK_ACCOUNT_FEE: 'ADM-REK', BANK_CARD_FEE: 'ADM-KARTU', BANK_TRANSFER_FEE: 'TRANSFER-FEE' };
    let nextIndex = 0;
    let timer;
    let requestVersion = 0;
    let controller;
    let baseBalance = null;
    let postedMovement = 0;

    const money = (value, signed = false) => {
        const number = Number(value || 0);
        return `${signed && number > 0 ? '+' : ''}${currency.format(number)}`;
    };
    const rows = () => [...list.querySelectorAll('[data-mutation-row]')];
    const field = (row, name) => row.querySelector(`[data-field="${name}"]`);

    const updateNumbers = () => rows().forEach((row, index) => { row.querySelector('[data-row-number]').textContent = index + 1; });
    const suggestedReference = (row) => {
        const reference = field(row, 'source_reference');
        if (reference.dataset.autoReference !== 'true' || reference.value.trim() !== '') return;
        const category = field(row, 'category_id').selectedOptions[0]?.dataset.categoryCode;
        const date = form.elements.date.value.replaceAll('-', '');
        const account = form.elements.financial_account_id.selectedOptions[0]?.dataset.accountCode?.split('-')[0] || 'BANK';
        if (category && date && suffixes[category]) reference.value = `${account}-${date}-${suffixes[category]}`;
    };
    const addRow = (data = {}) => {
        const index = nextIndex++;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(index)).trim();
        const row = wrapper.firstElementChild;
        list.appendChild(row);
        ['transaction_id', 'category_id', 'fund_id', 'amount', 'description', 'source_reference'].forEach((name) => {
            const input = field(row, name);
            if (input) input.value = data[name] ?? (name === 'fund_id' ? defaultFundId : '');
        });
        if (data.source_reference) field(row, 'source_reference').dataset.autoReference = 'false';
        updateNumbers();
        suggestedReference(row);
        renderCurrentState();
    };

    const currentEntries = () => rows().map((row) => {
        const category = field(row, 'category_id');
        const amountText = field(row, 'amount').value.trim();
        const amount = amountText === '' ? 0 : Number(amountText);
        return {
            row,
            category_id: category.value,
            category_code: category.selectedOptions[0]?.dataset.categoryCode || '',
            fund_id: field(row, 'fund_id').value,
            amount: Number.isFinite(amount) && amount > 0 ? amount : 0,
        };
    });

    const renderCurrentState = () => {
        let credit = 0;
        let debit = 0;
        let running = baseBalance === null ? null : baseBalance + postedMovement;
        currentEntries().forEach((entry) => {
            const signed = entry.category_code === 'BANK_INTEREST' ? entry.amount : -entry.amount;
            if (signed >= 0) credit += signed; else debit += Math.abs(signed);
            if (entry.amount > 0 && running !== null) running += signed;
            entry.row.querySelector('[data-row-movement]').textContent = entry.amount > 0 ? money(signed, true) : '—';
            entry.row.querySelector('[data-row-balance]').textContent = entry.amount > 0 && running !== null ? money(running) : '—';
        });
        const net = postedMovement + credit - debit;
        form.querySelector('[data-preview-credit]').textContent = money(credit);
        form.querySelector('[data-preview-debit]').textContent = money(debit);
        form.querySelector('[data-preview-net]').textContent = money(net, true);
        form.querySelector('[data-preview-closing]').textContent = baseBalance === null ? '—' : money(baseBalance + net);
    };

    const paintConfiguration = (state, message) => {
        const status = form.querySelector('[data-bank-configuration]');
        const tones = {
            ready: 'border-emerald-200 bg-emerald-50 text-emerald-950',
            missing: 'border-amber-200 bg-amber-50 text-amber-950',
            pending: 'border-base-300 bg-base-200/40',
        };
        status.className = `rounded-2xl border p-4 text-sm ${tones[state]}`;
        status.querySelector('[data-bank-configuration-message]').textContent = message;
        submit.disabled = state !== 'ready';
        form.dataset.configurationReady = state === 'ready' ? 'true' : 'false';
    };

    const refresh = () => {
        const version = ++requestVersion;
        controller?.abort();
        renderCurrentState();
        clearTimeout(timer);
        const accountId = form.elements.financial_account_id?.value;
        const date = form.elements.date?.value;
        const validEntries = currentEntries().filter((entry) => entry.category_id && entry.fund_id && entry.amount > 0);
        const configurationComplete = !!accountId && !!date && rows().length > 0 && validEntries.length === rows().length;
        if (!configurationComplete) {
            baseBalance = null;
            postedMovement = 0;
            form.querySelector('[data-preview-opening]').textContent = '—';
            form.querySelector('[data-preview-note]').textContent = 'Pratinjau saldo tersedia setelah data transaksi lengkap.';
            paintConfiguration('pending', 'Lengkapi data transaksi untuk memeriksa konfigurasi.');
            renderCurrentState();
            return;
        }

        baseBalance = null;
        postedMovement = 0;
        form.querySelector('[data-preview-opening]').textContent = '—';
        paintConfiguration('pending', 'Memeriksa konfigurasi yang berlaku pada tanggal transaksi…');
        form.querySelector('[data-preview-note]').textContent = 'Memuat pratinjau saldo…';
        renderCurrentState();

        timer = setTimeout(async () => {
            const loading = form.querySelector('[data-preview-loading]');
            loading?.classList.remove('hidden');
            controller = new AbortController();
            try {
                const response = await fetch(form.dataset.previewUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    signal: controller.signal,
                    body: JSON.stringify({
                        entity: form.elements.entity.value,
                        financial_account_id: accountId,
                        date,
                        mutations: validEntries.map(({ category_id, fund_id, amount }) => ({ category_id, fund_id, amount })),
                    }),
                });
                const data = await response.json();
                if (!response.ok || data.state !== 'ready') {
                    const message = data.state === 'missing' && String(data.message || '').startsWith('○ Konfigurasi pencatatan belum tersedia')
                        ? data.message
                        : 'Status konfigurasi belum dapat diperiksa. Coba lagi.';
                    throw new Error(message);
                }
                if (version !== requestVersion) return;
                baseBalance = data.balance_preview_available === false ? null : Number(data.opening);
                postedMovement = Number(data.posted_movement);
                form.querySelector('[data-preview-opening]').textContent = baseBalance === null ? '—' : money(baseBalance);
                paintConfiguration('ready', '● Siap digunakan');
                form.querySelector('[data-preview-note]').textContent = data.preview_message || 'Pratinjau mengikuti ledger posted dan nilai form saat ini.';
                renderCurrentState();
            } catch (error) {
                if (error.name === 'AbortError') return;
                if (version !== requestVersion) return;
                baseBalance = null;
                postedMovement = 0;
                form.querySelector('[data-preview-opening]').textContent = '—';
                form.querySelector('[data-preview-note]').textContent = 'Pratinjau saldo belum tersedia.';
                paintConfiguration('missing', error.message || '○ Konfigurasi pencatatan belum tersedia untuk kombinasi ini.');
                renderCurrentState();
            } finally {
                if (version === requestVersion) loading?.classList.add('hidden');
            }
        }, 250);
    };

    form.addEventListener('click', (event) => {
        if (event.target.closest('[data-add-mutation]')) {
            addRow({ fund_id: defaultFundId });
            refresh();
            return;
        }
        const remove = event.target.closest('[data-remove-mutation]');
        if (!remove) return;
        remove.closest('[data-mutation-row]').remove();
        if (rows().length === 0) addRow({ fund_id: defaultFundId });
        updateNumbers();
        refresh();
    });
    form.addEventListener('input', (event) => {
        if (event.target.matches('[data-field="source_reference"]')) event.target.dataset.autoReference = 'false';
        refresh();
    });
    form.addEventListener('change', (event) => {
        const row = event.target.closest('[data-mutation-row]');
        if (row && event.target.matches('[data-field="category_id"]')) {
            const description = field(row, 'description');
            const label = event.target.selectedOptions[0]?.textContent?.trim();
            if (description.value.trim() === '' && label) description.value = label.toUpperCase();
            suggestedReference(row);
        }
        refresh();
    });

    initialRows.forEach(addRow);
    refresh();
})();
</script>
@endpush
