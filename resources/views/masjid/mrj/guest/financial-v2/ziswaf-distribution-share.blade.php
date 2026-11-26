<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow,noarchive">
    <title>Detail Penyaluran ZISWAF</title>
    @vite(['resources/css/app.css'])
    <style>
        [hidden]{display:none!important} .share-grid{display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(160px,1fr))}
        .share-filters{display:grid;gap:.75rem;grid-template-columns:repeat(auto-fit,minmax(140px,1fr))}
        .share-filters input,.share-filters select{display:block;width:100%;min-height:44px;margin-top:.35rem;border:1px solid #cbd5e1;border-radius:8px;padding:.5rem;background:white;color:#0f172a}
        .share-summary{cursor:pointer;list-style:none;padding:1.25rem;background:#ecfdf5;color:#064e3b;display:flex;flex-wrap:wrap;justify-content:space-between;gap:1rem}
        .share-summary::-webkit-details-marker{display:none}.share-summary:after{content:'＋';font-size:1.25rem}details[open]>.share-summary:after{content:'−'}
        .share-card{border:1px solid #e2e8f0;background:white;border-radius:16px;overflow:hidden;box-shadow:0 1px 2px #00000008}
        .share-button{display:inline-flex;align-items:center;justify-content:center;min-height:44px;padding:.6rem 1rem;border-radius:8px;background:#047857;color:white;font-weight:700;border:1px solid #047857}
        .share-note{font-size:.8rem;color:#64748b;line-height:1.6}.share-scroll{max-width:100%;overflow-x:auto}.share-table{width:100%;min-width:640px;border-collapse:collapse;font-size:.875rem}
        .share-table th,.share-table td{padding:.85rem 1rem;border-bottom:1px solid #f1f5f9;text-align:left}.share-table th{background:#f8fafc;color:#64748b;font-size:.75rem;text-transform:uppercase}
        .share-table .amount{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}.share-table tfoot{background:#ecfdf5;color:#065f46;font-weight:700}
        .share-filter-total{padding:1rem;border:1px solid #a7f3d0;background:#ecfdf5;border-radius:12px;color:#065f46;font-weight:700}
    </style>
</head>
<body class="min-h-screen overflow-x-hidden bg-slate-50 text-slate-900">
@php
    $rp = static fn ($value) => \App\Support\ZiswafAmount::rupiah((string) $value);
    $date = static fn ($value) => \Carbon\CarbonImmutable::parse($value)->format('d/m/Y');
@endphp
<header class="border-b border-emerald-900 bg-emerald-950 text-white"><div class="mx-auto max-w-7xl px-4 py-7 sm:px-6">
    <nav class="text-xs text-emerald-200" aria-label="Breadcrumb"><a href="{{ route('public.ziswaf.index') }}">Laporan ZISWAF</a> / Detail penyaluran</nav>
    <div class="mt-4 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between"><div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[.2em] text-emerald-300">{{ $summary['program'] }}</p><h1 class="mt-2 break-words text-2xl font-bold sm:text-3xl">Detail Penyaluran ZISWAF</h1><p class="mt-2 text-sm text-emerald-100">{{ $summary['period'] }} · {{ $date($summary['from']) }}–{{ $date($summary['through']) }}</p></div><div class="flex flex-wrap gap-2"><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">Operasional: {{ strtoupper($summary['operational_status']) }}</span><span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold">Finansial: {{ strtoupper($summary['financial_status']) }}</span></div></div>
</div></header>
<main id="share-detail" class="mx-auto max-w-7xl space-y-7 px-4 py-6 sm:px-6">
    <a class="share-button" href="{{ route('public.ziswaf.index') }}">← Kembali ke Laporan ZISWAF</a>
    <section aria-label="Statistik seluruh penyaluran" class="share-grid">
        @foreach ([['Total penerima', $summary['recipient_count'].' orang'], ['Total rincian penyaluran', $rp($summary['operational_total'])], ['Actual tertaut', $rp($summary['actual_amount'])], ['RT / RW penerima', $summary['rt_count'].' RT / '.$summary['rw_count'].' RW'], ['Rata-rata per penerima', $rp($summary['average'])]] as [$label,$value])
            <article class="share-card p-5"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="mt-2 break-words text-2xl font-extrabold tracking-tight text-emerald-800">{{ $value }}</p></article>
        @endforeach
    </section>
    <section class="share-card p-5 sm:p-6"><h2 class="text-lg font-bold">Informasi Penyaluran</h2><dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2 lg:grid-cols-5">
        @foreach (['Program' => $summary['program'], 'Periode' => $summary['period'], 'Tanggal penyaluran' => $date($summary['from']).'–'.$date($summary['through']), 'Tanggal aktual' => $summary['business_date'], 'Tanggal akuntansi' => $summary['accounting_date'], 'Sumber dana' => implode(', ',$summary['funds']), 'Kategori dana' => implode(', ',$summary['fund_categories']), 'Kategori penerima' => implode(', ',$summary['recipient_categories']), 'Status penyaluran' => strtoupper($summary['operational_status'])] as $label=>$value)
            <div class="min-w-0"><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-1 break-words font-semibold">{{ $value ?: '—' }}</dd></div>
        @endforeach
    </dl></section>
    <section class="share-card p-5 sm:p-6" aria-label="Filter penerima">
        <h2 class="text-xl font-bold">Penerima per RT/RW</h2><p class="share-note mt-2">Status di bawah mengikuti status kegiatan penyaluran. Sumber existing belum mencatat status penerimaan bantuan per orang.</p>
        <div class="share-filters mt-5">
            <label class="text-sm font-semibold">Cari nama<input id="share-name" type="search" placeholder="Nama penerima" maxlength="120" autocomplete="off"></label>
            <label class="text-sm font-semibold">RT<select id="share-rt"><option value="">Seluruh RT</option>@foreach($options['rt'] as $rt)<option value="{{ $rt }}">RT {{ $rt }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">RW<select id="share-rw"><option value="">Seluruh RW</option>@foreach($options['rw'] as $rw)<option value="{{ $rw }}">RW {{ $rw }}</option>@endforeach</select></label>
            <label class="text-sm font-semibold">Status penyaluran<select id="share-status"><option value="">Seluruh status</option>@foreach($options['status'] as $status)<option value="{{ $status }}">{{ strtoupper($status) }}</option>@endforeach</select></label>
        </div>
        <div class="mt-4 flex flex-wrap gap-2"><button type="button" id="share-reset" class="share-button">Reset filter</button><button type="button" id="share-expand" class="share-button">Buka semua kelompok</button><button type="button" id="share-collapse" class="share-button">Tutup semua kelompok</button></div>
        <p id="share-filter-result" class="share-filter-total mt-5" aria-live="polite">{{ $recipients->count() }} penerima · {{ $rp($summary['operational_total']) }}</p>
        <noscript><p class="share-note mt-2">Aktifkan JavaScript untuk pencarian dan filter. Seluruh daftar tetap tersedia di bawah.</p></noscript>
    </section>
    <section class="space-y-5" aria-label="Seluruh kelompok penerima">
        @foreach($groups as $group)
            <details data-group class="share-card" open>
                <summary class="share-summary"><span><strong>{{ $group['rt']!=='' || $group['rw']!=='' ? 'RT '.($group['rt']?:'—').' / RW '.($group['rw']?:'—') : 'Wilayah belum tercatat' }}</strong><span class="mt-1 block text-sm"><span data-group-count>{{ $group['count'] }}</span> penerima · Subtotal <span data-group-total>{{ $rp($group['total']) }}</span></span></span><span class="text-sm">Rata-rata <strong data-group-average>{{ $rp($group['average']) }}</strong><span data-group-status class="mt-1 block">@foreach($group['statuses'] as $status=>$count){{ strtoupper($status) }}: {{ $count }}{{ !$loop->last?' · ':'' }}@endforeach</span></span></summary>
                <div class="share-scroll" tabindex="0" role="region" aria-label="Tabel penerima RT {{ $group['rt'] }} RW {{ $group['rw'] }}">
                    <table class="share-table"><thead><tr><th>No</th><th>Nama Penerima</th><th>Kategori</th><th>Status penyaluran</th><th class="amount">Nominal Bantuan</th></tr></thead><tbody>
                    @foreach($group['members'] as $recipient)
                        <tr data-recipient data-name="{{ $recipient['name'] }}" data-rt="{{ $recipient['rt'] }}" data-rw="{{ $recipient['rw'] }}" data-status="{{ $recipient['status'] }}" data-amount="{{ $recipient['amount'] }}"><td>{{ $loop->iteration }}</td><td class="font-semibold">{{ $recipient['name'] }}</td><td>{{ $recipient['category'] }}@if($recipient['category_from_current_master'])<span class="share-note block">Kategori berdasarkan Master Penerima saat ini</span>@endif</td><td>{{ strtoupper($recipient['status']) }}</td><td class="amount font-mono font-bold text-emerald-800">{{ $rp($recipient['amount']) }}</td></tr>
                    @endforeach
                    </tbody><tfoot><tr><td colspan="4">Subtotal RT/RW</td><td class="amount" data-group-subtotal>{{ $rp($group['total']) }}</td></tr></tfoot></table>
                </div>
            </details>
        @endforeach
        <p id="share-no-results" hidden class="share-card p-5 text-center text-slate-500">Tidak ada penerima yang sesuai dengan filter.</p>
        <div class="share-filter-total" id="share-grand-total" aria-live="polite">Grand total seluruh RT/RW: {{ $recipients->count() }} penerima · {{ $rp($summary['operational_total']) }} · Rata-rata {{ $rp($summary['average']) }}</div>
    </section>
    <section class="share-card p-5 sm:p-6"><h2 class="text-xl font-bold">Bukti dan Dokumentasi Penyaluran</h2>
        @if($attachments->isEmpty())<div class="mt-5 rounded-xl bg-slate-50 p-5 text-center text-slate-500">Belum ada lampiran yang diizinkan untuk ditampilkan.</div><p class="share-note mt-2">Bukti transaksi dan dokumen pribadi tidak dipublikasikan otomatis. Izin publikasi diperlukan sebelum file dapat ditampilkan atau diunduh.</p>@endif
    </section>
    <p class="share-note pb-8 text-center">Tautan bertanda tangan hanya memberikan akses baca untuk penyaluran ini. Data NIK, KK, rekening, telepon, alamat lengkap, dan catatan pribadi tidak dibagikan.</p>
</main>
<script nonce="{{ $nonce }}">
(() => {
    const root = document.getElementById('share-detail');
    const name = root.querySelector('#share-name'), rt = root.querySelector('#share-rt'), rw = root.querySelector('#share-rw'), status = root.querySelector('#share-status');
    const groups = [...root.querySelectorAll('[data-group]')];
    const cents = value => { const negative = value.startsWith('-'); const [whole, fraction = ''] = value.replace('-', '').split('.'); const amount = BigInt(whole) * 100n + BigInt(fraction.padEnd(2, '0').slice(0, 2)); return negative ? -amount : amount; };
    const money = value => { const negative = value < 0n; const amount = negative ? -value : value; const fraction = amount % 100n; return (negative ? '-' : '') + 'Rp' + (amount / 100n).toLocaleString('id-ID') + (fraction ? ',' + fraction.toString().padStart(2, '0') : ''); };
    const average = (total, count) => { if (!count) return 0n; const n = BigInt(count), sign = total < 0n ? -1n : 1n, amount = total * sign; return ((amount * 2n + n) / (n * 2n)) * sign; };
    const update = () => {
        let grandCount = 0, grandTotal = 0n, visibleGroups = 0;
        const query = name.value.trim().toLocaleLowerCase('id');
        groups.forEach(group => {
            let count = 0, total = 0n; const counts = new Map();
            group.querySelectorAll('[data-recipient]').forEach(row => {
                const show = (!query || row.dataset.name.toLocaleLowerCase('id').includes(query)) && (!rt.value || row.dataset.rt === rt.value) && (!rw.value || row.dataset.rw === rw.value) && (!status.value || row.dataset.status === status.value);
                row.hidden = !show;
                if (show) { count++; total += cents(row.dataset.amount); counts.set(row.dataset.status, (counts.get(row.dataset.status) || 0) + 1); }
            });
            group.hidden = count === 0;
            if (count) visibleGroups++;
            group.querySelector('[data-group-count]').textContent = count;
            group.querySelector('[data-group-total]').textContent = money(total);
            group.querySelector('[data-group-subtotal]').textContent = money(total);
            group.querySelector('[data-group-average]').textContent = money(average(total, count));
            group.querySelector('[data-group-status]').textContent = [...counts].map(([s, n]) => s.toUpperCase() + ': ' + n).join(' · ');
            grandCount += count; grandTotal += total;
        });
        root.querySelector('#share-filter-result').textContent = grandCount + ' penerima · ' + visibleGroups + ' kelompok RT/RW · ' + money(grandTotal);
        root.querySelector('#share-grand-total').textContent = 'Grand total hasil filter: ' + grandCount + ' penerima · ' + money(grandTotal) + ' · Rata-rata ' + money(average(grandTotal, grandCount));
        root.querySelector('#share-no-results').hidden = grandCount !== 0;
    };
    name.addEventListener('input', update);
    [rt, rw, status].forEach(field => field.addEventListener('change', update));
    root.querySelector('#share-reset').addEventListener('click', () => { [name, rt, rw, status].forEach(field => field.value = ''); update(); });
    root.querySelector('#share-expand').addEventListener('click', () => groups.forEach(group => group.open = true));
    root.querySelector('#share-collapse').addEventListener('click', () => groups.forEach(group => group.open = false));
    update();
})();
</script>
</body>
</html>
