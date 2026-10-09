@once
<script>
(() => {
    const financialPath = /^(?:\/admin\/(?:keuangan(?:-v2)?|kotak-infak|dana-terikat|pengeluaran|penerimaan|zakat|alokasi-dana|saldo-awal)(?:\/|$)|\/laporan-ziswaf(?:-v2)?(?:\/|$))/;
    if (!financialPath.test(window.location.pathname)) return;

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

    const enhance = input => {
        if (input.dataset.indonesianDateReady === 'true') return;
        input.dataset.indonesianDateReady = 'true';
        input.dataset.isoMin = input.min || '';
        input.dataset.isoMax = input.max || '';
        input.type = 'text';
        input.inputMode = 'numeric';
        input.autocomplete = 'off';
        input.maxLength = 10;
        input.placeholder ||= 'DD/MM/YYYY';
        input.value = toDisplay(input.value);

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
    };

    const enhanceAll = root => root.querySelectorAll?.('input[type="date"]').forEach(enhance);
    enhanceAll(document);
    new MutationObserver(records => records.forEach(record => record.addedNodes.forEach(node => {
        if (!(node instanceof Element)) return;
        if (node.matches('input[type="date"]')) enhance(node);
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
