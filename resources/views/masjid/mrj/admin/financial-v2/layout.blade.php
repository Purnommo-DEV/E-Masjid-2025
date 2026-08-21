<!doctype html>
<html lang="id">
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
            <nav class="-mx-4 flex gap-1 overflow-x-auto px-4 pb-3 text-xs font-medium sm:mx-0 sm:px-0">
                @php
                    $nav = [
                        ['financial-v2.dashboard', 'Dashboard', route('financial-v2.dashboard', ['entity' => $entityId])],
                        ['financial-v2.transactions.create', 'Penerimaan', route('financial-v2.transactions.create', ['operation' => 'receipt', 'entity' => $entityId]), 'receipt'],
                        ['financial-v2.transactions.create', 'Pengeluaran', route('financial-v2.transactions.create', ['operation' => 'payment', 'entity' => $entityId]), 'payment'],
                        ['financial-v2.transactions.create', 'Transfer', route('financial-v2.transactions.create', ['operation' => 'transfer', 'entity' => $entityId]), 'transfer'],
                        ['financial-v2.funds.index', 'Dana', route('financial-v2.funds.index', ['entity' => $entityId])],
                        ['financial-v2.allocations.create', 'Alokasi Dana', route('financial-v2.allocations.create', ['entity' => $entityId])],
                        ['financial-v2.realizations.drafts', 'Draft Realisasi', route('financial-v2.realizations.drafts', ['entity' => $entityId])],
                        ['financial-v2.transactions.index', 'Riwayat Transaksi', route('financial-v2.transactions.index', ['entity' => $entityId])],
                        ['financial-v2.reports.index', 'Laporan', route('financial-v2.reports.index', ['entity' => $entityId])],
                        ['financial-v2.controls.index', 'Kontrol', route('financial-v2.controls.index', ['entity' => $entityId])],
                    ];
                @endphp
                @foreach ($nav as $item)
                    @php
                        [$routeName, $label, $url] = $item;
                        $operation = $item[3] ?? null;
                        $isActive = request()->routeIs($routeName) && ($operation === null || request()->route('operation') === $operation);
                    @endphp
                    <a href="{{ $url }}" @class([
                        'whitespace-nowrap rounded-full px-3 py-2 transition',
                        'bg-emerald-100 text-emerald-900' => $isActive,
                        'text-base-content/70 hover:bg-base-300' => ! $isActive,
                    ])>{{ $label }}</a>
                @endforeach
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
            document.querySelectorAll('[data-financial-preview]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const form = button.closest('form');
                    const output = form.querySelector('[data-preview-output]');
                    const data = new FormData(form);
                    data.set('operation', form.dataset.operation);
                    try {
                        const response = await fetch(form.dataset.previewUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            body: data,
                        });
                        const payload = await response.json();
                        output.className = `mt-3 rounded-xl px-3 py-2 text-sm ${payload.ok && payload.allowed ? 'bg-emerald-50 text-emerald-900' : 'bg-amber-50 text-amber-900'}`;
                        output.textContent = payload.message;
                    } catch (_) {
                        output.className = 'mt-3 rounded-xl bg-base-200 px-3 py-2 text-sm';
                        output.textContent = 'Pratinjau belum tersedia. Pemeriksaan akhir tetap dilakukan saat pencatatan resmi.';
                    }
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
            document.querySelectorAll('[data-money-field]').forEach((field) => {
                const input = field.querySelector('[data-money-input]');
                const value = field.querySelector('[data-money-value]');
                if (!input || !value) return;
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
            });
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
