<!doctype html>
<html lang="id" class="!overflow-x-hidden">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') · Keuangan Masjid · E-Masjid</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.x/dist/full.min.css" rel="stylesheet" type="text/css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-base-200 font-[Poppins] text-base-content">
    @php $entityId = $entity?->id; @endphp
    <header class="sticky top-0 z-30 border-b border-base-300 bg-base-100/95 backdrop-blur">
        <div class="mx-auto max-w-7xl px-4 sm:px-6">
            <div class="flex min-h-16 items-center justify-between gap-3">
                <a href="{{ route('financial-v2.dashboard', ['entity' => $entityId]) }}" class="flex min-w-0 items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-2xl bg-emerald-700 text-lg font-bold text-white">Rp</span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-bold text-emerald-900">Keuangan Masjid</span>
                        <span class="block text-xs text-base-content/60">Pencatatan keuangan terkontrol</span>
                    </span>
                </a>
                <div class="flex items-center gap-2">
                    <a class="btn btn-ghost btn-sm hidden sm:inline-flex" href="{{ route('admin.dashboard') }}">Admin</a>
                    <a class="btn btn-primary btn-sm" href="{{ route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entityId]) }}">+ Catat</a>
                </div>
            </div>
            @php
                $isTransactionCreate = request()->routeIs('financial-v2.transactions.create');
                $transactionOperation = request()->route('operation');
                $isTransactionDetail = request()->routeIs('financial-v2.transactions.show', 'financial-v2.transactions.edit');
                $isGenericDraftDetail = $isTransactionDetail
                    && isset($transaction)
                    && $transaction
                    && ($operation ?? null) !== 'realization'
                    && ! in_array($transaction->status, ['posted', 'reversed'], true);
                $navGroups = [
                    [
                        'key' => 'finance',
                        'label' => 'Keuangan',
                        'items' => [
                            ['key' => 'receipt', 'label' => 'Penerimaan', 'url' => route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entityId]), 'active' => $isTransactionCreate && $transactionOperation === 'receipt'],
                            ['key' => 'payment', 'label' => 'Pengeluaran', 'url' => route('financial-v2.transactions.create', ['operation' => 'payment', 'entity' => $entityId]), 'active' => $isTransactionCreate && $transactionOperation === 'payment'],
                            ['key' => 'transfer', 'label' => 'Transfer', 'url' => route('financial-v2.transactions.create', ['operation' => 'transfer', 'entity' => $entityId]), 'active' => $isTransactionCreate && in_array($transactionOperation, ['transfer', 'interfund'], true)],
                            ['key' => 'bank-mutations', 'label' => 'Mutasi Bank', 'url' => route('financial-v2.bank-mutations.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.bank-mutations.*')],
                            ['key' => 'funds', 'label' => 'Dana', 'url' => route('financial-v2.funds.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.funds.*')],
                            ['key' => 'allocations', 'label' => 'Alokasi Dana', 'url' => route('financial-v2.allocations.create', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.allocations.*')],
                            ['key' => 'drafts', 'label' => 'Draft Transaksi', 'url' => route('financial-v2.transactions.drafts', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.transactions.drafts') || $isGenericDraftDetail],
                            ['key' => 'realization', 'label' => 'Draft Realisasi', 'url' => route('financial-v2.realizations.drafts', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.realizations.*') || ($isTransactionCreate && $transactionOperation === 'realization')],
                            ['key' => 'history', 'label' => 'Riwayat Transaksi', 'url' => route('financial-v2.transactions.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.transactions.index') || ($isTransactionDetail && ! $isGenericDraftDetail && ($operation ?? null) !== 'realization')],
                        ],
                    ],
                    [
                        'key' => 'ziswaf',
                        'label' => 'ZISWAF',
                        'items' => [
                            ['key' => 'distributions', 'label' => 'Penyaluran ZISWAF', 'url' => route('financial-v2.distributions.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.distributions.*')],
                            ['key' => 'beneficiaries', 'label' => 'Penerima ZISWAF', 'url' => route('financial-v2.beneficiaries.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.beneficiaries.*')],
                            ['key' => 'planning', 'label' => 'Perencanaan', 'url' => route('financial-v2.plannings.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.plannings.*')],
                        ],
                    ],
                    [
                        'key' => 'reports',
                        'label' => 'Laporan',
                        'align' => 'end',
                        'items' => [
                            ['key' => 'financial-report', 'label' => 'Laporan Keuangan', 'url' => route('financial-v2.reports.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.reports.*')],
                            ['key' => 'ziswaf-report', 'label' => 'Laporan ZISWAF V2', 'url' => route('financial-v2.ziswaf-v2.index', ['entity' => $entityId]), 'active' => request()->routeIs('financial-v2.ziswaf-v2.*')],
                        ],
                    ],
                ];

                foreach ($navGroups as &$navGroup) {
                    $navGroup['active'] = collect($navGroup['items'])->contains('active', true);
                }
                unset($navGroup);
            @endphp
            <nav aria-label="Navigasi Financial V2" class="-mx-4 flex flex-wrap items-center gap-2 px-4 pb-3 text-sm font-medium sm:mx-0 sm:px-0" data-financial-nav>
                @foreach ($navGroups as $navGroup)
                    <details @class(['dropdown group', 'dropdown-end' => ($navGroup['align'] ?? 'start') === 'end']) data-nav-group="{{ $navGroup['key'] }}" data-active="{{ $navGroup['active'] ? 'true' : 'false' }}">
                        <summary id="financial-v2-nav-{{ $navGroup['key'] }}-trigger" aria-controls="financial-v2-nav-{{ $navGroup['key'] }}-menu" aria-expanded="false" aria-haspopup="menu" data-nav-trigger @class([
                            'btn btn-ghost btn-sm list-none whitespace-nowrap rounded-full font-semibold [&::-webkit-details-marker]:hidden',
                            'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' => $navGroup['active'],
                            'text-base-content/75 hover:bg-base-300' => ! $navGroup['active'],
                        ])>
                            {{ $navGroup['label'] }}
                            <svg class="h-3.5 w-3.5 transition-transform group-open:rotate-180" aria-hidden="true" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                            </svg>
                        </summary>
                        <ul id="financial-v2-nav-{{ $navGroup['key'] }}-menu" aria-labelledby="financial-v2-nav-{{ $navGroup['key'] }}-trigger" class="menu dropdown-content z-40 mt-2 w-60 max-w-[calc(100vw-2rem)] rounded-box border border-base-300 bg-base-100 p-2 text-sm shadow-xl" data-nav-menu>
                            @foreach ($navGroup['items'] as $navItem)
                                <li>
                                    <a href="{{ $navItem['url'] }}" data-nav-item="{{ $navItem['key'] }}" data-active="{{ $navItem['active'] ? 'true' : 'false' }}" @if ($navItem['active']) aria-current="page" @endif @class([
                                        'min-h-10 rounded-xl px-3 py-2.5',
                                        'bg-emerald-100 font-semibold text-emerald-900' => $navItem['active'],
                                        'text-base-content/75 hover:bg-base-200' => ! $navItem['active'],
                                    ])>{{ $navItem['label'] }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endforeach
                @php $controlActive = request()->routeIs('financial-v2.controls.*'); @endphp
                @php $configurationActive = request()->routeIs('financial-v2.configuration.*', 'financial-v2.masters.*'); @endphp
                <a href="{{ route('financial-v2.configuration.index', ['entity' => $entityId]) }}" data-nav-item="configuration" data-active="{{ $configurationActive ? 'true' : 'false' }}" @if ($configurationActive) aria-current="page" @endif @class([
                    'btn btn-ghost btn-sm whitespace-nowrap rounded-full font-semibold',
                    'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' => $configurationActive,
                    'text-base-content/75 hover:bg-base-300' => ! $configurationActive,
                ])>Konfigurasi</a>
                <a href="{{ route('financial-v2.controls.index', ['entity' => $entityId]) }}" data-nav-item="controls" data-active="{{ $controlActive ? 'true' : 'false' }}" @if ($controlActive) aria-current="page" @endif @class([
                    'btn btn-ghost btn-sm whitespace-nowrap rounded-full font-semibold',
                    'bg-emerald-100 text-emerald-900 hover:bg-emerald-200' => $controlActive,
                    'text-base-content/75 hover:bg-base-300' => ! $controlActive,
                ])>Kontrol</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-5 sm:px-6 sm:py-8">
        @if (session('success'))
            <div role="alert" class="alert alert-success mb-5 text-sm"><span>{{ session('success') }}</span></div>
        @endif
        @if ($errors->any())
            <div role="alert" class="alert alert-error mb-5 items-start text-sm">
                <span>{{ $errors->first('financial') ?: $errors->first() }}</span>
            </div>
        @endif
        <div id="financial-ajax-message" class="hidden mb-5"></div>
        @yield('content')
    </main>

    <script>
        (() => {
            const navigation = document.querySelector('[data-financial-nav]');
            if (navigation) {
                const dropdowns = [...navigation.querySelectorAll('[data-nav-group]')];
                let openDropdown = null;

                const applyOpenDropdown = (nextDropdown) => {
                    openDropdown = nextDropdown;
                    dropdowns.forEach((dropdown) => {
                        const isOpen = dropdown === openDropdown;
                        dropdown.open = isOpen;
                        dropdown.querySelector('[data-nav-trigger]')?.setAttribute('aria-expanded', String(isOpen));
                    });
                };

                dropdowns.forEach((dropdown) => {
                    dropdown.querySelector('[data-nav-trigger]')?.addEventListener('click', (event) => {
                        event.preventDefault();
                        applyOpenDropdown(openDropdown === dropdown ? null : dropdown);
                    });
                });

                document.addEventListener('click', (event) => {
                    if (openDropdown && ! navigation.contains(event.target)) {
                        applyOpenDropdown(null);
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key !== 'Escape' || ! openDropdown) return;
                    const trigger = openDropdown.querySelector('[data-nav-trigger]');
                    applyOpenDropdown(null);
                    trigger?.focus();
                    event.preventDefault();
                });

                applyOpenDropdown(null);
            }

            const message = document.getElementById('financial-ajax-message');
            const showMessage = (text, tone = 'error') => {
                message.className = `alert ${tone === 'success' ? 'alert-success' : 'alert-error'} mb-5 text-sm`;
                message.textContent = text;
                message.classList.remove('hidden');
                message.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            };
            document.querySelectorAll('[data-financial-ajax]').forEach((form) => {
                form.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const submit = form.querySelector('[type="submit"]');
                    if (submit?.disabled) return;
                    if (submit) {
                        submit.disabled = true;
                        submit.dataset.originalText = submit.textContent;
                        submit.textContent = 'Memproses…';
                    }
                    try {
                        const response = await fetch(form.action, {
                            method: form.method || 'POST',
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: new FormData(form),
                        });
                        const payload = await response.json();
                        if (!response.ok || !payload.ok) throw new Error(payload.message || 'Data belum dapat diproses.');
                        window.location.assign(payload.redirect);
                    } catch (error) {
                        showMessage(error.message || 'Data belum dapat diproses.');
                        if (submit) {
                            submit.disabled = false;
                            submit.textContent = submit.dataset.originalText || 'Simpan';
                        }
                    }
                });
            });
            document.querySelectorAll('[data-program-options-url]').forEach((form) => {
                const program = form.elements.program_id;
                const entity = form.elements.entity;
                const watched = ['date', 'financial_account_id', 'fund_id', 'category_id']
                    .map((name) => form.elements[name])
                    .filter(Boolean);
                if (!program || !entity || !form.dataset.typeCode) return;
                let timer;
                let requestVersion = 0;
                let controller;
                const refresh = () => {
                    clearTimeout(timer);
                    controller?.abort();
                    const version = ++requestVersion;
                    timer = setTimeout(async () => {
                        controller = new AbortController();
                        const selected = program.value;
                        const params = new URLSearchParams({
                            entity: entity.value,
                            type: form.dataset.typeCode,
                            date: form.elements.date?.value || '',
                            financial_account_id: form.elements.financial_account_id?.value || '',
                            fund_id: form.elements.fund_id?.value || '',
                            category_id: form.elements.category_id?.value || '',
                        });
                        try {
                            const response = await fetch(`${form.dataset.programOptionsUrl}?${params}`, {
                                credentials: 'same-origin',
                                signal: controller.signal,
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            });
                            const payload = await response.json();
                            if (!response.ok || !payload.ok || version !== requestVersion) return;
                            program.replaceChildren(new Option('Tanpa program', ''));
                            payload.programs.forEach((item) => program.add(new Option(item.name, item.id)));
                            program.value = [...program.options].some((option) => option.value === selected) ? selected : '';
                            if (program.value !== selected) program.dispatchEvent(new Event('change', { bubbles: true }));
                        } catch (error) {
                            if (error.name !== 'AbortError') console.warn('Program options could not be refreshed.', error);
                        }
                    }, 150);
                };
                watched.forEach((field) => field.addEventListener('change', refresh));
                refresh();
            });
            document.querySelectorAll('[data-financial-configuration]').forEach((status) => {
                if (status.dataset.initialized === 'true') return;
                status.dataset.initialized = 'true';
                const form = status.closest('form');
                const submit = form?.querySelector('[data-configuration-submit]');
                const message = status.querySelector('[data-configuration-message]');
                const loading = status.querySelector('[data-configuration-loading]');
                const missingAction = status.querySelector('[data-configuration-missing-action]');
                const requiredByOperation = {
                    receipt: ['date', 'financial_account_id', 'fund_id', 'category_id'],
                    payment: ['date', 'financial_account_id', 'fund_id', 'category_id'],
                    transfer: ['date', 'source_financial_account_id', 'destination_financial_account_id', 'fund_id'],
                    interfund: ['date', 'financial_account_id', 'source_fund_id', 'destination_fund_id'],
                };
                if (!form || !submit || !message) return;
                let timer;
                let requestVersion = 0;
                let controller;
                const paint = (state, text) => {
                    const tones = {
                        ready: 'border-emerald-200 bg-emerald-50 text-emerald-950',
                        missing: 'border-amber-200 bg-amber-50 text-amber-950',
                        pending: 'border-base-300 bg-base-200/40',
                    };
                    status.className = `mt-4 rounded-xl border px-3 py-3 text-sm ${tones[state]}`;
                    message.textContent = text;
                    missingAction?.classList.toggle('hidden', state !== 'missing');
                    submit.disabled = state !== 'ready';
                    form.dataset.configurationReady = state === 'ready' ? 'true' : 'false';
                };
                const resolve = () => {
                    clearTimeout(timer);
                    controller?.abort();
                    const version = ++requestVersion;
                    const fields = requiredByOperation[form.dataset.operation] || [];
                    if (fields.some((name) => !form.elements[name]?.value)) {
                        loading?.classList.add('hidden');
                        paint('pending', 'Lengkapi data transaksi untuk memeriksa konfigurasi.');
                        return;
                    }
                    paint('pending', 'Memeriksa konfigurasi yang berlaku pada tanggal transaksi…');
                    loading?.classList.remove('hidden');
                    timer = setTimeout(async () => {
                        controller = new AbortController();
                        const data = new FormData(form);
                        data.set('operation', form.dataset.operation);
                        try {
                            const response = await fetch(form.dataset.previewUrl, {
                                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: data,
                            });
                            const payload = await response.json();
                            if (version !== requestVersion) return;
                            const state = payload.state === 'ready' && response.ok && payload.ok && payload.allowed
                                ? 'ready'
                                : (payload.state === 'incomplete' ? 'pending' : 'missing');
                            paint(state, payload.message || '○ Konfigurasi pencatatan belum tersedia untuk kombinasi ini.');
                        } catch (error) {
                            if (error.name === 'AbortError' || version !== requestVersion) return;
                            paint('missing', 'Status konfigurasi belum dapat diperiksa. Coba lagi.');
                        } finally {
                            if (version === requestVersion) loading?.classList.add('hidden');
                        }
                    }, 200);
                };
                form.addEventListener('input', resolve);
                form.addEventListener('change', resolve);
                resolve();
            });
            document.querySelectorAll('[data-configuration-missing-action]').forEach((action) => {
                if (action.dataset.initialized === 'true' || action.dataset.canManage !== 'true') return;
                action.dataset.initialized = 'true';
                const parentForm = action.closest('form');
                const dialog = action.querySelector('[data-inline-configuration-dialog]');
                const open = action.querySelector('[data-inline-configuration-open]');
                const modalForm = action.querySelector('[data-inline-configuration-form]');
                const error = action.querySelector('[data-inline-configuration-error]');
                const contextList = action.querySelector('[data-inline-configuration-context]');
                const postingRule = action.querySelector('[data-inline-posting-rule]');
                const postingRuleHelp = action.querySelector('[data-inline-posting-rule-help]');
                let context = null;
                const currentContext = () => {
                    if (parentForm._inlineConfigurationContext) return parentForm._inlineConfigurationContext;
                    const data = new FormData(parentForm);
                    return Object.fromEntries(['entity', 'date', 'financial_account_id', 'source_financial_account_id', 'destination_financial_account_id', 'fund_id', 'source_fund_id', 'destination_fund_id', 'category_id', 'program_id'].map((key) => [key, data.get(key) || '']).concat([['operation', parentForm.dataset.operation]]));
                };
                const showError = (message) => { error.textContent = message; error.classList.remove('hidden'); };
                open?.addEventListener('click', async () => {
                    error.classList.add('hidden');
                    context = currentContext();
                    try {
                        const response = await fetch(`${action.dataset.showUrl}?${new URLSearchParams(context)}`, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
                        const payload = await response.json();
                        if (!response.ok || !payload.ok) throw new Error(payload.message || 'Form konfigurasi belum dapat dibuka.');
                        const labels = [['Entity', payload.context.entity], ['Jenis Transaksi', payload.context.transaction_type], ['Tanggal Transaksi', payload.context.date], ['Rekening', payload.context.financial_accounts.join(' → ') || 'Tidak berlaku'], ['Dana', payload.context.funds.join(' → ') || 'Tidak berlaku'], ['Kategori', payload.context.category], ['Program', payload.context.program]];
                        contextList.replaceChildren(...labels.flatMap(([term, value]) => {
                            const wrapper = document.createElement('div');
                            const dt = document.createElement('dt'); dt.className = 'text-xs text-base-content/55'; dt.textContent = term;
                            const dd = document.createElement('dd'); dd.className = 'font-semibold'; dd.textContent = value;
                            wrapper.append(dt, dd); return [wrapper];
                        }));
                        postingRule.innerHTML = '<option value="">Pilih Posting Rule</option>';
                        payload.posting_rules.forEach((rule) => postingRule.add(new Option(rule.label, rule.id, false, rule.id === payload.selected_posting_rule_version_id)));
                        postingRule.disabled = payload.posting_rules.length === 0 || payload.state === 'ready';
                        postingRuleHelp.textContent = payload.message;
                        modalForm.elements.effective_from.value = context.date;
                        modalForm.elements.required_approval_steps.value = payload.required_approval_steps;
                        modalForm.querySelector('[data-inline-configuration-save]').disabled = postingRule.disabled;
                        dialog.showModal();
                    } catch (exception) { showError(exception.message || 'Form konfigurasi belum dapat dibuka.'); dialog.showModal(); }
                });
                action.querySelector('[data-inline-configuration-cancel]')?.addEventListener('click', () => dialog.close());
                modalForm?.addEventListener('submit', async (event) => {
                    event.preventDefault(); error.classList.add('hidden');
                    const save = modalForm.querySelector('[data-inline-configuration-save]');
                    save.disabled = true;
                    const body = new FormData(modalForm);
                    Object.entries(context || {}).forEach(([key, value]) => body.set(key, value));
                    try {
                        const response = await fetch(action.dataset.storeUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body });
                        const payload = await response.json();
                        if (!response.ok || !payload.ok) throw new Error(payload.message || Object.values(payload.errors || {})[0]?.[0] || 'Draft konfigurasi belum dapat disimpan.');
                        dialog.close();
                        action.classList.add('hidden');
                        const status = action.closest('[data-financial-configuration], [data-bank-configuration]');
                        const output = status?.querySelector('[data-configuration-message], [data-bank-configuration-message]');
                        if (output) output.textContent = '○ Konfigurasi menunggu aktivasi/approval.';
                        parentForm.dispatchEvent(new CustomEvent('configuration-draft-created', { detail: payload }));
                    } catch (exception) { showError(exception.message || 'Draft konfigurasi belum dapat disimpan.'); }
                    finally { save.disabled = false; }
                });
            });
            const parseMoney = (raw) => {
                const value = String(raw || '').replace(/[^\d,.-]/g, '');
                const comma = value.lastIndexOf(',');
                const hasDecimal = comma !== -1;
                const whole = (hasDecimal ? value.slice(0, comma) : value).replace(/\D/g, '').replace(/^0+(?=\d)/, '') || '0';
                const fraction = hasDecimal ? value.slice(comma + 1).replace(/\D/g, '').slice(0, 2) : '';

                return {
                    canonical: fraction ? `${whole}.${fraction}` : whole,
                    hasDecimal,
                    fraction,
                };
            };
            const parseCanonicalMoney = (raw) => {
                const match = String(raw || '').trim().match(/^(\d+)(?:\.(\d{1,2}))?$/);
                if (!match) return parseMoney(raw);

                const whole = match[1].replace(/^0+(?=\d)/, '') || '0';
                const fraction = match[2] || '';

                return {
                    canonical: fraction ? `${whole}.${fraction}` : whole,
                    hasDecimal: Boolean(fraction),
                    fraction,
                };
            };
            const formatMoney = ({ canonical, hasDecimal, fraction }) => {
                if (!canonical) return '';
                const [whole] = canonical.split('.');
                const grouped = whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

                return hasDecimal ? `${grouped},${fraction}` : grouped;
            };
            const bindMoneyField = (field) => {
                if (field.dataset.moneyBound === 'true') return;
                const input = field.querySelector('[data-money-input]');
                const value = field.querySelector('[data-money-value]');
                if (!input || !value) return;
                field.dataset.moneyBound = 'true';
                const sync = (raw, notify = true, canonicalInput = false) => {
                    if (!String(raw || '').trim()) {
                        value.value = '';
                        input.value = '';
                        if (notify) value.dispatchEvent(new Event('input', { bubbles: true }));
                        return;
                    }
                    const parsed = canonicalInput ? parseCanonicalMoney(raw) : parseMoney(raw);
                    value.value = parsed.canonical;
                    input.value = formatMoney(parsed);
                    if (notify) value.dispatchEvent(new Event('input', { bubbles: true }));
                };
                sync(value.value, false, true);
                input.addEventListener('input', () => sync(input.value));
                input.closest('form')?.addEventListener('submit', () => sync(input.value, false));
            };
            const bindMoneyFields = (root = document) => {
                if (root.matches?.('[data-money-field]')) bindMoneyField(root);
                root.querySelectorAll?.('[data-money-field]').forEach(bindMoneyField);
            };
            window.FinancialV2Money = Object.freeze({ bind: bindMoneyFields });
            bindMoneyFields();
            document.querySelectorAll('[data-realization-allocation]').forEach((select) => {
                const form = select.closest('form');
                const amount = form?.querySelector('input[name="amount"]');
                const summary = form?.querySelector('[data-realization-summary]');
                if (!amount || !summary) return;
                const rupiah = (value) => `Rp${Number(value || 0).toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                const render = () => {
                    const option = select.options[select.selectedIndex];
                    if (!option?.dataset.available) {
                        summary.classList.add('hidden');
                        return;
                    }
                    const allocated = Number(option.dataset.allocated);
                    const actual = Number(option.dataset.actual);
                    const available = Number(option.dataset.available);
                    const requested = Number(amount.value || 0);
                    const exceeds = requested > available;
                    summary.className = `mt-3 rounded-xl px-3 py-3 text-sm ${exceeds ? 'bg-amber-50 text-amber-900' : 'bg-base-200 text-base-content/75'}`;
                    summary.textContent = `Total alokasi ${rupiah(allocated)} · Sudah direalisasikan ${rupiah(actual)} · Sisa ${rupiah(available)}${exceeds ? ' · Nominal realisasi melebihi sisa dana yang tersedia.' : ''}`;
                };
                select.addEventListener('change', render);
                amount.addEventListener('input', render);
                render();
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>
