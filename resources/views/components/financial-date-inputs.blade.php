@once
<script>
(() => {
    const financialPath = /^(?:\/admin\/(?:keuangan(?:-v2)?|kotak-infak|dana-terikat|pengeluaran|penerimaan|zakat|alokasi-dana|saldo-awal)(?:\/|$)|\/laporan-ziswaf(?:-v2)?(?:\/|$))/;
    if (!financialPath.test(window.location.pathname)) return;
    const datepickerPath = /^\/admin\/keuangan-v2\/(?:receipt\/baru|payment\/baru|transfer\/baru|mutasi-bank(?:\/[^/]+)?|alokasi-dana\/baru|riwayat|penyaluran(?:\/[^/]+)?|penerima(?:\/[^/]+)?|laporan-ziswaf)(?:\/|$)/.test(window.location.pathname);

    const isoPattern = /^(\d{4})-(\d{2})-(\d{2})$/;
    const displayPattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;

    const validParts = (year, month, day) => {
        const date = new Date(Date.UTC(Number(year), Number(month) - 1, Number(day)));
        return date.getUTCFullYear() === Number(year)
            && date.getUTCMonth() === Number(month) - 1
            && date.getUTCDate() === Number(day);
    };
    const toDisplay = value => {
        const match = String(value || '').match(isoPattern);
        return match && validParts(match[1], match[2], match[3]) ? `${match[3]}/${match[2]}/${match[1]}` : value;
    };
    const toIso = value => {
        const match = String(value || '').trim().match(displayPattern);
        return match && validParts(match[3], match[2], match[1]) ? `${match[3]}-${match[2]}-${match[1]}` : null;
    };
    const dateTime = value => new Intl.DateTimeFormat('en-GB', {
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit',
        hourCycle: 'h23', timeZone: 'Asia/Jakarta',
    }).format(new Date(value)).replace(',', '');
    window.financialDate = Object.freeze({ toDisplay, toIso, dateTime });

    const attachCalendar = input => {
        if (!datepickerPath || input.dataset.financialCalendarReady === 'true') return;
        input.dataset.financialCalendarReady = 'true';

        const wrapper = document.createElement('span');
        wrapper.className = 'relative block min-w-0';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        input.classList.add('pr-11');

        const picker = document.createElement('input');
        picker.type = 'date';
        picker.lang = 'id';
        picker.tabIndex = -1;
        picker.setAttribute('aria-hidden', 'true');
        picker.style.cssText = 'position:absolute;width:1px;height:1px;right:1.25rem;bottom:0;opacity:0;pointer-events:none';
        picker.min = input.dataset.isoMin;
        picker.max = input.dataset.isoMax;
        picker.value = toIso(input.value) || '';

        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-ghost btn-sm absolute inset-y-0 right-0 my-auto min-h-0 h-full w-10 rounded-l-none px-0';
        button.setAttribute('aria-label', 'Pilih tanggal dari kalender');
        button.title = 'Pilih tanggal';
        button.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 2v4m8-4v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14H3V6a2 2 0 0 1 2-2Z"/></svg>';
        button.addEventListener('click', () => {
            picker.value = toIso(input.value) || '';
            if (typeof picker.showPicker === 'function') picker.showPicker();
            else picker.click();
        });
        picker.addEventListener('change', () => {
            input.value = toDisplay(picker.value);
            input.setCustomValidity('');
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
        });
        wrapper.append(picker, button);
    };

    const enhance = input => {
        if (input.dataset.indonesianDateReady === 'true') return;
        input.dataset.indonesianDateReady = 'true';
        const canonicalTarget = input.dataset.dateTarget ? document.getElementById(input.dataset.dateTarget) : null;
        input.dataset.isoMin = input.dataset.minDate || input.min || '';
        input.dataset.isoMax = input.max || '';
        input.type = 'text';
        input.inputMode = 'numeric';
        input.autocomplete = 'off';
        input.maxLength = 10;
        input.placeholder ||= 'DD/MM/YYYY';
        input.value = toDisplay(input.value || canonicalTarget?.value || '');

        const validate = () => {
            if (isoPattern.test(input.value)) input.value = toDisplay(input.value);
            const iso = input.value === '' ? '' : toIso(input.value);
            let message = '';
            if (input.value !== '' && !iso) message = 'Gunakan format DD/MM/YYYY dengan tanggal yang valid.';
            if (iso && input.dataset.isoMin && iso < input.dataset.isoMin) message = `Tanggal minimal ${toDisplay(input.dataset.isoMin)}.`;
            if (iso && input.dataset.isoMax && iso > input.dataset.isoMax) message = `Tanggal maksimal ${toDisplay(input.dataset.isoMax)}.`;
            input.setCustomValidity(message);
            return iso;
        };
        input.addEventListener('input', validate);
        input.addEventListener('blur', validate);
        attachCalendar(input);
    };

    const enhanceAll = root => root.querySelectorAll?.('input[type="date"], input[data-allocation-date-display]').forEach(enhance);
    enhanceAll(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
        if (!(node instanceof Element)) return;
        if (node.matches('input[type="date"], input[data-allocation-date-display]')) enhance(node);
        enhanceAll(node);
    }))).observe(document.documentElement, { childList: true, subtree: true });

    document.addEventListener('submit', event => {
        for (const input of event.target.querySelectorAll('input[data-indonesian-date-ready="true"]')) {
            input.dispatchEvent(new Event('blur'));
            if (!input.checkValidity()) {
                event.preventDefault();
                input.reportValidity();
                break;
            }
        }
    }, true);

    document.addEventListener('formdata', event => {
        for (const input of event.target.querySelectorAll('input[data-indonesian-date-ready="true"][name]')) {
            const iso = input.value === '' ? '' : toIso(input.value);
            if (iso !== null) event.formData.set(input.name, iso);
        }
    });
})();
</script>
@endonce
