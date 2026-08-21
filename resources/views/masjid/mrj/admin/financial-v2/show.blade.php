@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Detail Transaksi')

@section('content')
    @php
        $rupiah = fn ($amount) => 'Rp'.number_format((float) $amount, 2, ',', '.');
        $statusLabel = [
            'draft' => 'Draft', 'submitted' => 'Dikirim', 'verified' => 'Dalam pemeriksaan', 'approved' => 'Disetujui',
            'posted' => 'Dicatat resmi', 'reversed' => 'Dibalik', 'rejected' => 'Ditolak', 'cancelled' => 'Dibatalkan',
        ][$transaction->status] ?? ucfirst($transaction->status);
        $fund = $transaction->splits->first()?->fund;
        $program = $transaction->splits->first()?->program_id ? $options['programs']->firstWhere('id', $transaction->splits->first()->program_id) : null;
        $isRealization = $operation === 'realization';
        $realizationVersion = $transaction->realization?->budgetAllocationVersion;
        $realizationAllocation = $realizationVersion?->allocation;
        $canEdit = $transaction->status === 'draft' && in_array($operation, ['receipt', 'payment', 'realization'], true);
        $financialAccountLabel = match ($operation) {
            'receipt' => 'Masuk ke',
            'payment', 'realization' => 'Dibayar dari',
            'interfund' => 'Rekening atribusi (saldo tidak berpindah)',
            default => 'Kas/rekening',
        };
    @endphp
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><a class="link text-sm text-base-content/60" href="{{ route('financial-v2.transactions.index', ['entity' => $entity->id]) }}">← Kembali ke riwayat</a><p class="mt-3 text-sm font-medium text-emerald-700">{{ $transaction->type?->name }}</p><h1 class="mt-1 text-3xl font-bold">{{ $rupiah($transaction->gross_amount) }}</h1><p class="mt-2 text-sm text-base-content/65">{{ $transaction->accounting_date->translatedFormat('d F Y') }}</p></div>
        <span @class(['badge badge-lg', 'badge-success' => $transaction->status === 'posted', 'badge-warning' => in_array($transaction->status, ['draft', 'submitted', 'verified']), 'badge-error' => in_array($transaction->status, ['reversed', 'rejected', 'cancelled'])])>{{ $statusLabel }}</span>
    </div>

    @if ($transaction->status === 'posted')
        <div class="alert mb-5 border border-emerald-200 bg-emerald-50 text-emerald-950"><span>Transaksi sudah dicatat secara resmi. Data keuangannya tidak dapat diubah langsung; bila diperlukan gunakan koreksi/reversal sesuai kewenangan pada tahap berikutnya.</span></div>
    @elseif (in_array($transaction->status, ['submitted', 'verified']))
        <div class="alert mb-5 border border-amber-200 bg-amber-50 text-amber-950"><span>Transaksi sedang melalui pemeriksaan atau persetujuan yang dikonfigurasi. Pencatatan resmi hanya dapat dilakukan setelah seluruh syarat terpenuhi.</span></div>
    @elseif ($isRealization && $transaction->status === 'draft')
        <div class="alert mb-5 border border-sky-200 bg-sky-50 text-sky-950"><span>Draft Realisasi sedang disiapkan. Simpan bukti, periksa data, lalu ajukan untuk pemeriksaan. Draft belum mengurangi Dana atau kas/rekening.</span></div>
    @elseif ($isRealization && $transaction->status === 'approved')
        <div class="alert mb-5 border border-emerald-200 bg-emerald-50 text-emerald-950"><span>Realisasi sudah siap dicatat resmi. Saat dicatat, sistem akan memproses satu pembayaran melalui Posting Engine.</span></div>
    @endif

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <h2 class="font-bold">Ringkasan</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-base-content/55">{{ $financialAccountLabel }}</dt><dd class="mt-1 font-semibold">{{ $transaction->primaryFinancialAccount?->name ?? '—' }}</dd>@if($operation === 'interfund' && $transaction->primaryFinancialAccount)<p class="mt-1 text-xs text-base-content/55">Menjelaskan atribusi lokasi likuiditas; saldo kas/rekening tidak dipindahkan.</p>@endif</div>
                <div><dt class="text-xs text-base-content/55">Dana</dt><dd class="mt-1 font-semibold">{{ $fund?->name ?? ($transaction->interfundTransfer ? 'Pindah dana' : '—') }}</dd></div>
                @if ($transaction->category)<div><dt class="text-xs text-base-content/55">Kategori</dt><dd class="mt-1 font-semibold">{{ $transaction->category->name }}</dd></div>@endif
                @if ($program)<div><dt class="text-xs text-base-content/55">Program</dt><dd class="mt-1 font-semibold">{{ $program->name }}</dd></div>@endif
                @if ($transaction->counterparty)<div><dt class="text-xs text-base-content/55">{{ $operation === 'receipt' ? 'Sumber' : 'Penerima' }}</dt><dd class="mt-1 font-semibold">{{ $transaction->counterparty->display_name }}</dd></div>@endif
                @if ($isRealization && $realizationAllocation)
                    <div><dt class="text-xs text-base-content/55">Alokasi</dt><dd class="mt-1 font-semibold">{{ $realizationAllocation->allocation_reference }}</dd><p class="mt-1 text-xs text-base-content/55">{{ $realizationAllocation->fund?->name ?? 'Dana' }}{{ $realizationAllocation->program ? ' · '.$realizationAllocation->program->name : '' }}</p></div>
                    <div><dt class="text-xs text-base-content/55">Posisi alokasi</dt><dd class="mt-1 font-semibold">Total {{ $rupiah($realizationAvailability['allocated'] ?? 0) }}</dd><p class="mt-1 text-xs text-base-content/55">Sudah direalisasikan {{ $rupiah($realizationAvailability['actual'] ?? 0) }} · Sisa {{ $rupiah($realizationAvailability['available'] ?? 0) }}</p></div>
                @endif
                @if ($transaction->treasuryTransfer)
                    <div><dt class="text-xs text-base-content/55">Rekening asal</dt><dd class="mt-1 font-semibold">{{ $options['financialAccounts']->firstWhere('id', $transaction->treasuryTransfer->source_financial_account_id)?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/55">Rekening tujuan</dt><dd class="mt-1 font-semibold">{{ $options['financialAccounts']->firstWhere('id', $transaction->treasuryTransfer->destination_financial_account_id)?->name ?? '—' }}</dd></div>
                @endif
                @if ($transaction->interfundTransfer)
                    <div><dt class="text-xs text-base-content/55">Dana asal</dt><dd class="mt-1 font-semibold">{{ $options['funds']->firstWhere('id', $transaction->interfundTransfer->source_fund_id)?->name ?? '—' }}</dd></div>
                    <div><dt class="text-xs text-base-content/55">Dana tujuan</dt><dd class="mt-1 font-semibold">{{ $options['funds']->firstWhere('id', $transaction->interfundTransfer->destination_fund_id)?->name ?? '—' }}</dd></div>
                    @if (filled($transaction->interfundTransfer->policy_basis_ref))<div><dt class="text-xs text-base-content/55">Dasar kebijakan</dt><dd class="mt-1 break-words font-semibold">{{ $transaction->interfundTransfer->policy_basis_ref }}</dd></div>@endif
                    @if (filled($transaction->interfundTransfer->reason))<div><dt class="text-xs text-base-content/55">Alasan koreksi/reklasifikasi</dt><dd class="mt-1 whitespace-pre-line font-semibold">{{ $transaction->interfundTransfer->reason }}</dd></div>@endif
                @endif
            </dl>
            <div class="mt-5 border-t border-base-300 pt-5"><p class="text-xs text-base-content/55">Keterangan</p><p class="mt-1 whitespace-pre-line text-sm leading-6">{{ $transaction->description ?: 'Tidak ada keterangan tambahan.' }}</p></div>
            <div class="mt-5 border-t border-base-300 pt-5"><div class="flex items-center justify-between"><h3 class="font-semibold">Bukti/lampiran</h3><span class="text-xs text-base-content/55">{{ $attachments->count() }} file</span></div><div class="mt-3 space-y-2">@forelse($attachments as $attachment)<div class="flex items-center justify-between gap-3 rounded-xl bg-base-200 px-3 py-3 text-sm"><span class="min-w-0 truncate"><span class="font-medium">{{ $attachment->original_filename }}</span><span class="mt-1 block text-xs text-base-content/55">{{ strtoupper($attachment->evidence_type) }} · {{ number_format($attachment->byte_size / 1024, 1, ',', '.') }} KB · {{ \Carbon\Carbon::parse($attachment->linked_at)->format('d/m/Y') }}</span></span><span class="flex shrink-0 gap-2"><a class="link text-emerald-700" target="_blank" href="{{ route('financial-v2.attachments.view', ['attachment' => $attachment->attachment_id]) }}">Lihat</a><a class="link text-emerald-700" href="{{ route('financial-v2.attachments.download', ['attachment' => $attachment->attachment_id]) }}">Unduh</a></span></div>@empty<p class="rounded-xl bg-base-200 p-3 text-sm text-base-content/65">Belum ada bukti terlampir.</p>@endforelse</div></div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300"><p class="text-xs text-base-content/55">Nomor bukti</p><p class="mt-1 break-all font-mono text-sm font-semibold">{{ $voucher?->voucher_number ?? 'Akan diterbitkan saat Posted' }}</p><p class="mt-3 text-xs text-base-content/55">Status</p><p class="mt-1 text-sm font-semibold">{{ $statusLabel }}</p></div>
            @if ($canEdit)<a class="btn btn-outline w-full" href="{{ route('financial-v2.transactions.edit', $transaction) }}">Ubah draft</a>@endif
            @if ($isRealization && $transaction->status === 'draft')
                <form method="POST" action="{{ route('financial-v2.realizations.submit', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Ajukan Realisasi</button></form>
            @elseif ($isRealization && $transaction->status === 'submitted')
                <form method="POST" action="{{ route('financial-v2.realizations.verify', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Verifikasi Realisasi</button></form>
            @elseif ($isRealization && $transaction->status === 'verified')
                <form method="POST" action="{{ route('financial-v2.realizations.approve', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Setujui Realisasi</button></form>
            @elseif ($isRealization && $transaction->status === 'approved')
                <form method="POST" action="{{ route('financial-v2.transactions.post', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Catat Resmi</button></form>
            @elseif (! $isRealization && in_array($transaction->status, ['draft', 'submitted', 'verified', 'approved'], true))
                <form method="POST" action="{{ route('financial-v2.transactions.post', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Catat resmi</button></form>
            @endif
            @if (in_array($transaction->status, ['draft', 'submitted', 'verified', 'approved'], true))
                <form method="POST" action="{{ route('financial-v2.transactions.cancel', $transaction) }}" data-financial-ajax class="rounded-2xl border border-base-300 p-3">@csrf<label class="form-control"><span class="label-text text-xs">Alasan pembatalan</span><input name="reason" class="input input-bordered input-sm" placeholder="Wajib diisi" required></label><button class="btn btn-ghost btn-sm mt-2 w-full text-error">Batalkan draft</button></form>
            @endif
        </aside>
    </div>

    <section class="mt-5 rounded-2xl bg-base-100 shadow-sm ring-1 ring-base-300">
        <details class="group"><summary class="cursor-pointer list-none px-5 py-4 font-semibold"><span class="flex items-center justify-between">Detail Akuntansi <span class="text-xs font-normal text-base-content/55 group-open:hidden">Tampilkan</span></span></summary><div class="border-t border-base-300 p-5 text-sm">
            @if ($journal)
                <dl class="grid gap-3 sm:grid-cols-3"><div><dt class="text-xs text-base-content/55">ID jurnal</dt><dd class="mt-1 break-all font-mono text-xs">{{ $journal->id }}</dd></div><div><dt class="text-xs text-base-content/55">Versi aturan pencatatan</dt><dd class="mt-1 break-all font-mono text-xs">{{ $journal->posting_rule_version_id }}</dd></div><div><dt class="text-xs text-base-content/55">Urutan pencatatan</dt><dd class="mt-1 font-semibold">{{ $journal->posting_sequence }}</dd></div></dl>
                <div class="mt-5 overflow-x-auto"><table class="table table-sm"><thead><tr><th>Akun</th><th>Dana</th><th>Rekening</th><th>Program</th><th class="text-right">Debit</th><th class="text-right">Kredit</th><th>Referensi buku besar</th></tr></thead><tbody>@foreach($journal->lines as $line)<tr><td>{{ $labels['accounts'][$line->account_id] ?? $line->account_id }}</td><td>{{ $labels['funds'][$line->fund_id] ?? '—' }}</td><td>{{ $labels['financialAccounts'][$line->financial_account_id] ?? '—' }}</td><td>{{ $labels['programs'][$line->program_id] ?? '—' }}</td><td class="text-right">{{ $rupiah($line->debit_amount) }}</td><td class="text-right">{{ $rupiah($line->credit_amount) }}</td><td class="font-mono text-xs">{{ $ledgerReferences[$line->id] ?? '—' }}</td></tr>@endforeach</tbody></table></div>
            @else
                <p class="text-base-content/65">Informasi akuntansi akan tersedia setelah transaksi dicatat secara resmi.</p>
            @endif
        </div></details>
    </section>
@endsection
