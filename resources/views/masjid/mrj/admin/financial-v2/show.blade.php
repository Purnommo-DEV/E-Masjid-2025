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
        $fundingSplits = $transaction->splits->groupBy('fund_id')->map(fn ($items) => ['fund' => $items->first()?->fund, 'amount' => $items->sum('split_amount'), 'notes' => $items->pluck('purpose_note')->filter()->unique()->values()]);
        $program = $transaction->splits->first()?->program_id ? $options['programs']->firstWhere('id', $transaction->splits->first()->program_id) : null;
        $isRealization = $operation === 'realization';
        $realizationVersion = $transaction->realization?->budgetAllocationVersion;
        $realizationAllocation = $realizationVersion?->allocation;
        $realizationParentInactive = $isRealization && ($realizationVersion?->status !== 'approved' || $realizationAllocation?->status !== 'approved');
        $canEdit = ! $isBankMutation && ! $realizationParentInactive && $transaction->status === 'draft' && in_array($operation, ['receipt', 'payment', 'realization'], true);
        $canEditBankMutation = $isBankMutation && $transaction->status === 'draft';
        $activeDateCorrectionAttachments = $attachments->where('attachment_status', 'active');
        $dateCorrectionAttachments = $isBankMutation ? $activeDateCorrectionAttachments->where('evidence_type', 'statement') : $activeDateCorrectionAttachments;
        $isGenericDraftPage = ! $isRealization && ! in_array($transaction->status, ['posted', 'reversed'], true);
        $backUrl = $isGenericDraftPage
            ? route('financial-v2.transactions.drafts', ['entity' => $entity->id, 'year' => $transaction->accounting_date->year, 'status' => $transaction->status])
            : ($isRealization && ! in_array($transaction->status, ['posted', 'reversed', 'cancelled'], true)
                ? route('financial-v2.realizations.drafts', ['entity' => $entity->id])
                : route('financial-v2.transactions.index', ['entity' => $entity->id]));
        $backLabel = $isGenericDraftPage ? 'Draft Transaksi' : ($isRealization && ! in_array($transaction->status, ['posted', 'reversed', 'cancelled'], true) ? 'Draft Realisasi' : 'riwayat');
        $financialAccountLabel = match ($operation) {
            'receipt' => 'Masuk ke',
            'payment', 'realization' => 'Dibayar dari',
            'interfund' => 'Rekening atribusi (saldo tidak berpindah)',
            default => 'Kas/rekening',
        };
    @endphp
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div><a class="link text-sm text-base-content/60" href="{{ $backUrl }}">← Kembali ke {{ $backLabel }}</a><p class="mt-3 text-sm font-medium text-emerald-700">{{ $isBankMutation ? 'Mutasi Bank' : $transaction->type?->name }}</p><h1 class="mt-1 text-3xl font-bold">{{ $rupiah($transaction->gross_amount) }}</h1><p class="mt-2 text-sm text-base-content/65">Tanggal aktual {{ $transaction->effective_business_date->format('d/m/Y') }} · Tanggal akuntansi {{ $transaction->accounting_date->format('d/m/Y') }}</p></div>
        <span @class(['badge badge-lg', 'badge-success' => $transaction->status === 'posted', 'badge-warning' => in_array($transaction->status, ['draft', 'submitted', 'verified']), 'badge-error' => in_array($transaction->status, ['reversed', 'rejected', 'cancelled'])])>{{ $statusLabel }}</span>
    </div>

    @if ($realizationParentInactive)
        <div class="alert mb-5 border border-error/25 bg-error/10 text-error-content"><span>Transaksi ini tidak dapat dicatat karena Alokasi induknya telah dibatalkan atau versi Alokasinya sudah tidak aktif. Draft disimpan hanya untuk audit dan seluruh bukti tetap dipertahankan.</span></div>
    @elseif ($transaction->status === 'posted')
        <div class="alert mb-5 border border-emerald-200 bg-emerald-50 text-emerald-950"><span>Transaksi sudah dicatat secara resmi. Data keuangannya tidak dapat diubah langsung; bila diperlukan gunakan koreksi/reversal sesuai kewenangan pada tahap berikutnya.</span></div>
    @elseif (in_array($transaction->status, ['submitted', 'verified']))
        <div class="alert mb-5 border border-amber-200 bg-amber-50 text-amber-950"><span>Transaksi sedang melalui pemeriksaan atau persetujuan yang dikonfigurasi. Pencatatan resmi hanya dapat dilakukan setelah seluruh syarat terpenuhi.</span></div>
    @elseif ($isRealization && $transaction->status === 'draft')
        <div class="alert mb-5 border border-sky-200 bg-sky-50 text-sky-950"><span>Draft Realisasi sedang disiapkan. Simpan bukti, periksa data, lalu ajukan untuk pemeriksaan. Draft belum mengurangi Dana atau kas/rekening.</span></div>
    @elseif ($isRealization && $transaction->status === 'approved')
        <div class="alert mb-5 border border-emerald-200 bg-emerald-50 text-emerald-950"><span>Realisasi sudah siap dicatat resmi. Saat dicatat, sistem akan memproses satu pembayaran melalui Posting Engine.</span></div>
    @endif

    <div @class([
        'mb-5 rounded-2xl border px-4 py-3 text-sm',
        'border-emerald-200 bg-emerald-50 text-emerald-950' => $configurationStatus['state'] === 'ready',
        'border-amber-200 bg-amber-50 text-amber-950' => $configurationStatus['state'] === 'missing',
    ]) data-transaction-configuration="{{ $configurationStatus['state'] }}">
        <p class="font-semibold">Status konfigurasi</p>
        <p class="mt-1 text-xs leading-5">{{ $configurationStatus['message'] }}</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_20rem]">
        <section class="rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <h2 class="font-bold">Ringkasan</h2>
            <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><dt class="text-xs text-base-content/55">Tanggal aktual berlaku</dt><dd class="mt-1 font-semibold">{{ $transaction->effective_business_date->format('d/m/Y') }}</dd>@if($transaction->latestDateCorrection)<p class="mt-1 text-xs text-info">Dikoreksi melalui histori append-only.</p>@endif</div>
                <div><dt class="text-xs text-base-content/55">Tanggal akuntansi</dt><dd class="mt-1 font-semibold">{{ $transaction->accounting_date->format('d/m/Y') }}</dd><p class="mt-1 text-xs text-base-content/55">Dasar periode laporan dan ledger.</p></div>
                <div><dt class="text-xs text-base-content/55">Dicatat ke sistem</dt><dd class="mt-1 font-semibold">{{ $transaction->created_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/55">Diposting resmi</dt><dd class="mt-1 font-semibold">{{ $journal?->posted_at?->format('d/m/Y H:i') ?? '—' }}</dd></div>
                <div><dt class="text-xs text-base-content/55">{{ $financialAccountLabel }}</dt><dd class="mt-1 font-semibold">{{ $transaction->primaryFinancialAccount?->name ?? '—' }}</dd>@if($operation === 'interfund' && $transaction->primaryFinancialAccount)<p class="mt-1 text-xs text-base-content/55">Menjelaskan atribusi lokasi likuiditas; saldo kas/rekening tidak dipindahkan.</p>@endif</div>
                <div><dt class="text-xs text-base-content/55">Dana</dt><dd class="mt-1 space-y-1 font-semibold">@forelse($fundingSplits as $funding)<span class="block">{{ $funding['fund']?->name ?? 'Dana' }}{{ $fundingSplits->count() > 1 ? ' · '.$rupiah($funding['amount']) : '' }}</span>@empty{{ $transaction->interfundTransfer ? 'Pindah dana' : '—' }}@endforelse</dd></div>
                @if ($transaction->category)<div><dt class="text-xs text-base-content/55">Kategori</dt><dd class="mt-1 font-semibold">{{ $transaction->category->name }}</dd></div>@endif
                @if ($program)<div><dt class="text-xs text-base-content/55">Program</dt><dd class="mt-1 font-semibold">{{ $program->name }}</dd></div>@endif
                @if ($transaction->counterparty)<div><dt class="text-xs text-base-content/55">{{ $operation === 'receipt' ? 'Sumber' : 'Penerima' }}</dt><dd class="mt-1 font-semibold">{{ $transaction->counterparty->display_name }}</dd></div>@endif
                @if ($isRealization && $realizationAllocation)
                    <div><dt class="text-xs text-base-content/55">Alokasi</dt><dd class="mt-1 font-semibold">{{ $realizationAllocation->allocation_reference }}</dd><p class="mt-1 text-xs text-base-content/55">{{ $realizationVersion?->fundings?->pluck('fund.name')->filter()->join(' + ') ?: ($realizationAllocation->fund?->name ?? 'Dana') }}{{ $realizationAllocation->program ? ' · '.$realizationAllocation->program->name : '' }}</p></div>
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
            @include('masjid.mrj.admin.financial-v2.components.evidence-status')
                <div class="mt-5 border-t border-base-300 pt-5"><div class="flex items-center justify-between"><h3 class="font-semibold">Bukti/lampiran</h3><span class="text-xs text-base-content/55">{{ $attachments->count() }} file</span></div><div class="mt-3 space-y-2">@forelse($attachments as $attachment)<div class="flex flex-col gap-3 rounded-xl bg-base-200 px-3 py-3 text-sm sm:flex-row sm:items-center sm:justify-between"><span class="min-w-0 truncate"><span class="font-medium">{{ $attachment->original_filename }}</span><span class="mt-1 block text-xs text-base-content/55">{{ $evidenceLabels[$attachment->evidence_type] ?? str($attachment->evidence_type)->replace('_', ' ')->title() }} · Aktif · {{ strtoupper(str_replace(['image/', 'application/'], '', $attachment->media_type)) }} · {{ number_format($attachment->byte_size / 1024, 1, ',', '.') }} KB · {{ \Carbon\Carbon::parse($attachment->linked_at)->format('d/m/Y') }}</span></span><span class="flex shrink-0 flex-wrap items-center gap-2"><a class="link text-emerald-700" target="_blank" href="{{ route('financial-v2.attachments.view', ['attachment' => $attachment->attachment_id]) }}">Lihat</a><a class="link text-emerald-700" href="{{ route('financial-v2.attachments.download', ['attachment' => $attachment->attachment_id]) }}">Unduh</a>@if($transaction->status === 'draft' && ! $realizationParentInactive)<form method="POST" action="{{ route('financial-v2.attachments.remove', ['attachmentLink' => $attachment->link_id]) }}" data-financial-ajax class="flex items-center gap-1">@csrf<input name="reason" class="input input-bordered input-xs w-32" placeholder="Alasan lepas" required><button class="btn btn-ghost btn-xs text-error">Lepas</button></form>@endif</span></div>@empty<p class="rounded-xl bg-base-200 p-3 text-sm text-base-content/65">Belum ada bukti terlampir.</p>@endforelse</div></div>
        </section>

        <aside class="space-y-4">
            <div class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300"><p class="text-xs text-base-content/55">Nomor bukti</p><p class="mt-1 break-all font-mono text-sm font-semibold">{{ $voucher?->voucher_number ?? 'Akan diterbitkan saat Posted' }}</p><p class="mt-3 text-xs text-base-content/55">Status</p><p class="mt-1 text-sm font-semibold">{{ $statusLabel }}</p></div>
            @if ($canEdit)<a class="btn btn-outline w-full" href="{{ route('financial-v2.transactions.edit', $transaction) }}">Ubah draft</a>@endif
            @if ($canEditBankMutation)<a class="btn btn-outline w-full" href="{{ route('financial-v2.bank-mutations.edit', ['transaction' => $transaction, 'entity' => $entity->id]) }}">Ubah draft</a>@endif
            @if ($isBankMutation && $transaction->status === 'draft')
                <form method="POST" action="{{ route('financial-v2.bank-mutations.submit', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary w-full">Ajukan</button></form>
            @elseif ($isBankMutation && $transaction->status === 'submitted')
                <form method="POST" action="{{ route('financial-v2.bank-mutations.verify', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary w-full">Periksa Mutasi Bank</button></form>
            @elseif ($isBankMutation && $transaction->status === 'verified')
                <form method="POST" action="{{ route('financial-v2.bank-mutations.approve', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary w-full">Setujui Mutasi Bank</button></form>
            @elseif ($isBankMutation && $transaction->status === 'approved')
                <form method="POST" action="{{ route('financial-v2.bank-mutations.post', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf<button class="btn btn-primary w-full">Catat Resmi</button></form>
            @elseif (! $realizationParentInactive && $isRealization && $transaction->status === 'draft')
                <form method="POST" action="{{ route('financial-v2.realizations.submit', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Ajukan Realisasi</button></form>
            @elseif (! $realizationParentInactive && $isRealization && $transaction->status === 'submitted')
                <form method="POST" action="{{ route('financial-v2.realizations.verify', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Verifikasi Realisasi</button></form>
            @elseif (! $realizationParentInactive && $isRealization && $transaction->status === 'verified')
                <form method="POST" action="{{ route('financial-v2.realizations.approve', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Setujui Realisasi</button></form>
            @elseif (! $realizationParentInactive && $isRealization && $transaction->status === 'approved')
                <form method="POST" action="{{ route('financial-v2.transactions.post', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Catat Resmi</button></form>
            @elseif (! $realizationParentInactive && ! $isRealization && ! $isBankMutation && $transaction->status === 'draft')
                <form method="POST" action="{{ route('financial-v2.transactions.submit', $transaction) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-primary w-full">Ajukan</button></form>
            @elseif (! $realizationParentInactive && ! $isRealization && ! $isBankMutation && in_array($transaction->status, ['submitted', 'verified', 'approved'], true))
                <form method="POST" action="{{ route('financial-v2.transactions.post', $transaction) }}" data-financial-ajax>@csrf<button class="btn btn-primary w-full">Catat resmi</button></form>
            @endif
            @if ($transaction->status === 'approved' && auth()->user()?->hasRole('SuperAdmin'))
                <details class="rounded-2xl border border-warning/40 bg-warning/5 p-3" data-controlled-backdated>
                    <summary class="cursor-pointer text-sm font-semibold">Controlled backdated posting</summary>
                    <p class="mt-2 text-xs leading-5 text-base-content/65">Gunakan hanya jika tanggal faktual benar. Sistem akan memeriksa saldo pada tanggal transaksi dan setiap aktivitas posted sesudahnya.</p>
                    <form class="mt-3 space-y-3" method="POST" action="{{ route('financial-v2.transactions.controlled-backdated.post', $transaction) }}" data-controlled-backdated-form>
                        @csrf
                        <label class="form-control"><span class="label-text text-xs font-semibold">Alasan koreksi historis</span><textarea class="textarea textarea-bordered textarea-sm" name="reason" minlength="10" maxlength="2000" required></textarea></label>
                        <button class="btn btn-outline btn-sm w-full" type="button" data-controlled-backdated-preview data-preview-url="{{ route('financial-v2.transactions.controlled-backdated.preview', $transaction) }}">Periksa seluruh saldo berjalan</button>
                        <div class="hidden rounded-xl bg-base-200 p-3 text-xs" data-controlled-backdated-result></div>
                        <label class="hidden items-start gap-2 text-xs" data-controlled-backdated-confirm><input class="checkbox checkbox-sm" type="checkbox" name="confirmed" value="1" required><span>Saya memahami bahwa transaksi akan dicatat pada tanggal historis setelah validasi diulang di server.</span></label>
                        <button class="btn btn-warning btn-sm hidden w-full" type="submit" data-controlled-backdated-submit>Catat dengan kontrol historis</button>
                    </form>
                </details>
            @endif
            @if ($transaction->status === 'posted' && auth()->user()?->hasRole('SuperAdmin'))
                <details class="rounded-2xl border border-info/40 bg-info/5 p-3" data-date-correction>
                    <summary class="cursor-pointer text-sm font-semibold">Koreksi Tanggal</summary>
                    <p class="mt-2 text-xs leading-5 text-base-content/65">Koreksi tanggal aktual bersifat append-only. Tanggal akuntansi ditampilkan terpisah dan tidak dapat diubah sampai alur reversal dan transaksi pengganti tersedia.</p>
                    @if ($isBankMutation)<p class="mt-2 text-xs leading-5 text-info">Untuk Mutasi Bank, tanggal baru harus merupakan tanggal debit/kredit sebenarnya pada rekening koran, bukan periode layanan biaya.</p>@endif
                    @if ($dateCorrectionAttachments->isEmpty())
                        <div class="alert alert-warning mt-3 py-2 text-xs">{{ $isBankMutation ? 'Transaksi belum memiliki lampiran rekening koran aktif (statement).' : 'Transaksi belum memiliki lampiran aktif yang dapat dijadikan bukti koreksi.' }}</div>
                    @else
                        <form class="mt-3 space-y-3" method="POST" action="{{ route('financial-v2.transactions.date-corrections.store', $transaction) }}" data-date-correction-form>
                            @csrf
                            <input type="hidden" name="entity" value="{{ $entity->id }}">
                            <input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <label class="form-control"><span class="label-text text-xs font-semibold">Tanggal aktual transaksi</span><input class="input input-bordered input-sm" type="date" name="business_date" value="{{ $transaction->effective_business_date->toDateString() }}" required><span class="mt-1 text-xs text-base-content/55">Saat ini {{ $transaction->effective_business_date->format('d/m/Y') }}</span></label>
                            <label class="form-control"><span class="label-text text-xs font-semibold">Tanggal akuntansi</span><input class="input input-bordered input-sm" type="date" name="accounting_date" value="{{ $transaction->accounting_date->toDateString() }}" required><span class="mt-1 text-xs text-base-content/55">Perubahan nilai ini akan ditolak karena memerlukan koreksi akuntansi resmi.</span></label>
                            <label class="form-control"><span class="label-text text-xs font-semibold">Alasan koreksi</span><textarea class="textarea textarea-bordered textarea-sm" name="reason" minlength="10" maxlength="2000" required></textarea></label>
                            <label class="form-control"><span class="label-text text-xs font-semibold">{{ $isBankMutation ? 'Rekening koran pendukung' : 'Bukti pendukung' }}</span><select class="select select-bordered select-sm" name="attachment_id" required><option value="">Pilih lampiran aktif</option>@foreach($dateCorrectionAttachments as $attachment)<option value="{{ $attachment->attachment_id }}">{{ $attachment->original_filename }}</option>@endforeach</select></label>
                            <button class="btn btn-outline btn-sm w-full" type="button" data-date-correction-preview data-preview-url="{{ route('financial-v2.transactions.date-corrections.preview', $transaction) }}">Pratinjau dampak</button>
                            <div class="hidden rounded-xl bg-base-200 p-3 text-xs leading-5" data-date-correction-result></div>
                            <label class="hidden items-start gap-2 text-xs" data-date-correction-confirm><input class="checkbox checkbox-sm" type="checkbox" name="confirmed" value="1" required><span>Saya sudah memeriksa tanggal, alasan, bukti, dan dampak koreksi ini.</span></label>
                            <label @class(['items-start gap-2 rounded-xl border border-warning/30 bg-warning/5 p-3 text-xs', 'flex' => $bankMutationBatchOtherCount > 0, 'hidden' => $bankMutationBatchOtherCount === 0]) data-bank-batch-confirm data-required="{{ $bankMutationBatchOtherCount > 0 ? 'true' : 'false' }}"><input class="checkbox checkbox-sm" type="checkbox" name="bank_batch_confirmed" value="1" @required($bankMutationBatchOtherCount > 0)><span>Saya memahami koreksi ini hanya mengubah tanggal aktual efektif transaksi yang dipilih; {{ $bankMutationBatchOtherCount }} baris Mutasi Bank lain dalam batch tidak berubah.</span></label>
                            <button class="btn btn-info btn-sm hidden w-full" type="submit" data-date-correction-submit>Simpan koreksi tanggal aktual</button>
                        </form>
                    @endif
                </details>
            @endif
            @if ($isBankMutation && $transaction->status === 'draft')
                <form method="POST" action="{{ route('financial-v2.bank-mutations.destroy', ['transaction' => $transaction, 'entity' => $entity->id]) }}">@csrf @method('DELETE')<button class="btn btn-ghost btn-sm w-full text-error">Batalkan draft</button></form>
            @elseif (! $isBankMutation && ! $realizationParentInactive && in_array($transaction->status, ['draft', 'submitted', 'verified', 'approved'], true))
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

    @if ($transaction->dateCorrections->isNotEmpty())
        <section class="mt-5 rounded-2xl bg-base-100 p-5 shadow-sm ring-1 ring-base-300">
            <h2 class="font-bold">Riwayat Koreksi Tanggal Aktual</h2>
            <div class="mt-4 space-y-3">@foreach($transaction->dateCorrections as $correction)<div class="rounded-xl border border-base-300 p-3 text-sm"><p class="font-semibold">{{ $correction->original_business_date->format('d/m/Y') }} → {{ $correction->corrected_business_date->format('d/m/Y') }}</p><p class="mt-1 text-xs text-base-content/60">Tanggal akuntansi tetap {{ $correction->accounting_date->format('d/m/Y') }} · {{ $correction->corrected_at->format('d/m/Y H:i') }}</p><p class="mt-2 whitespace-pre-line">{{ $correction->reason }}</p><p class="mt-2 text-xs text-base-content/55">Bukti: {{ $correction->evidence?->original_filename ?? $correction->evidence_attachment_id }}</p></div>@endforeach</div>
        </section>
    @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-controlled-backdated]');
    if (!root) return;
    const form = root.querySelector('[data-controlled-backdated-form]');
    const preview = root.querySelector('[data-controlled-backdated-preview]');
    const result = root.querySelector('[data-controlled-backdated-result]');
    const confirm = root.querySelector('[data-controlled-backdated-confirm]');
    const submit = root.querySelector('[data-controlled-backdated-submit]');
    const escapeHtml = value => String(value).replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
    preview.addEventListener('click', async () => {
        result.classList.remove('hidden');
        result.textContent = 'Memeriksa saldo berjalan…';
        confirm.classList.add('hidden'); submit.classList.add('hidden');
        try {
            const response = await fetch(preview.dataset.previewUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value }, body: new FormData(form) });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.message || 'Validasi tidak dapat diselesaikan.');
            const checks = payload.validation || [];
            result.innerHTML = checks.map(check => {
                const rows = (check.timeline || []).map(row => `<li>${escapeHtml(row.accounting_date)}${row.posting_sequence ? ` · urutan ${escapeHtml(row.posting_sequence)}` : ''}: saldo ${escapeHtml(row.current_balance)}, proyeksi ${escapeHtml(row.projected_balance)}</li>`).join('');
                return `<p class="font-semibold">Minimum ${escapeHtml(check.minimum_projected_balance)} pada ${escapeHtml(check.minimum_projected_date)}</p><ul class="mt-1 list-disc pl-4">${rows}</ul>`;
            }).join('');
            confirm.classList.remove('hidden'); confirm.classList.add('flex'); submit.classList.remove('hidden');
        } catch (error) {
            result.textContent = error.message;
        }
    });
    form.querySelector('[name="reason"]').addEventListener('input', () => {
        confirm.classList.add('hidden'); submit.classList.add('hidden'); result.classList.add('hidden');
        form.querySelector('[name="confirmed"]').checked = false;
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-date-correction]');
    if (!root) return;
    const form = root.querySelector('[data-date-correction-form]');
    if (!form) return;
    const preview = root.querySelector('[data-date-correction-preview]');
    const result = root.querySelector('[data-date-correction-result]');
    const confirm = root.querySelector('[data-date-correction-confirm]');
    const submit = root.querySelector('[data-date-correction-submit]');
    const batchConfirm = root.querySelector('[data-bank-batch-confirm]');
    const batchConfirmationRequired = batchConfirm?.dataset.required === 'true';
    const escapeHtml = value => String(value ?? '').replace(/[&<>'"]/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;' })[character]);
    const resetPreview = () => { result.classList.add('hidden'); confirm.classList.add('hidden'); submit.classList.add('hidden'); form.querySelector('[name="confirmed"]').checked = false; if (batchConfirm) { const field = batchConfirm.querySelector('input'); field.checked = false; field.required = batchConfirmationRequired; batchConfirm.classList.toggle('hidden', !batchConfirmationRequired); batchConfirm.classList.toggle('flex', batchConfirmationRequired); } };
    form.querySelectorAll('input, textarea, select').forEach(field => { if (!['_token', 'idempotency_key', 'confirmed', 'bank_batch_confirmed'].includes(field.name)) field.addEventListener('input', resetPreview); });
    preview.addEventListener('click', async () => {
        result.classList.remove('hidden'); result.textContent = 'Memeriksa koreksi…'; confirm.classList.add('hidden'); submit.classList.add('hidden');
        try {
            const response = await fetch(preview.dataset.previewUrl, { method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value }, body: new FormData(form) });
            const payload = await response.json();
            if (!response.ok || !payload.ok) throw new Error(payload.message || 'Pratinjau tidak dapat diselesaikan.');
            const item = payload.preview;
            const bank = item.bank_mutation;
            const batch = bank?.batch;
            const reconciliations = (bank?.reconciliations?.items || []).map(reconciliation => `<li>${escapeHtml(reconciliation.period || 'Periode tidak diketahui')} · ${escapeHtml(reconciliation.status)}${reconciliation.matched_by_source_reference ? ' · cocok berdasarkan source reference' : ' · kandidat berdasarkan rekening/periode'}${reconciliation.completed_warning ? `<br><span class="text-warning">${escapeHtml(reconciliation.completed_warning)}</span>` : ''}</li>`).join('');
            result.innerHTML = `<p class="font-semibold">Transaksi ${escapeHtml(item.transaction_id)} · ${escapeHtml(item.status)}</p><p>Aktual: ${escapeHtml(item.old_business_date)} → ${escapeHtml(item.new_business_date)}</p><p>Akuntansi: ${escapeHtml(item.old_accounting_date)} → ${escapeHtml(item.new_accounting_date)} (periode ${escapeHtml(item.accounting_period)})</p><p>Nominal tetap Rp${escapeHtml(item.amount)}</p><p class="mt-2">${escapeHtml(item.ledger_impact)}</p><p>${escapeHtml(item.balance_validation)}</p><p class="mt-2">Jurnal ${escapeHtml(item.journal_id)} · posted ${escapeHtml(item.journal_posted_at)}</p>${bank ? `<div class="mt-2 border-t border-base-300 pt-2"><p>${escapeHtml(bank.statement_note)}</p><p class="mt-1">${batch.is_batch ? `Bagian batch; ${escapeHtml(batch.other_count)} baris lain tidak ikut berubah.` : 'Bukan bagian batch dengan baris lain.'}</p><p class="mt-1">Keterkaitan rekonsiliasi: ${escapeHtml(bank.reconciliations.match_basis)}.</p>${reconciliations ? `<ul class="mt-1 list-disc pl-4">${reconciliations}</ul>` : '<p class="mt-1">Rekonsiliasi terkait tidak ditemukan.</p>'}</div>` : ''}`;
            if (batchConfirm && batch?.is_batch) { const field = batchConfirm.querySelector('input'); field.required = true; batchConfirm.classList.remove('hidden'); batchConfirm.classList.add('flex'); }
            confirm.classList.remove('hidden'); confirm.classList.add('flex'); submit.classList.remove('hidden');
        } catch (error) { result.textContent = error.message; }
    });
});
</script>
@endpush
