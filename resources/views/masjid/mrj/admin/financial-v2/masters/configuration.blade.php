@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Konfigurasi Financial V2')

@section('content')
    @include('masjid.mrj.admin.financial-v2.masters._header', [
        'title' => 'Konfigurasi Financial V2',
        'subtitle' => 'Satu tempat resmi untuk melihat master, versi aturan pencatatan, Aturan Dana, bukti, dan persetujuan yang dipakai resolver transaksi.',
    ])

    @if ($entity)
        @php
            $typeNames = $transactionTypes->pluck('name', 'id');
            $statusBadge = fn (string $status) => in_array($status, ['active', 'effective'], true) ? 'badge-success' : (in_array($status, ['draft'], true) ? 'badge-warning' : 'badge-ghost');
        @endphp

        <section id="reversal-configuration" class="mt-5 rounded-2xl border border-indigo-200 bg-base-100 shadow-sm">
            <div class="border-b border-indigo-100 p-5">
                <div class="flex flex-col justify-between gap-3 lg:flex-row lg:items-start">
                    <div>
                        <h2 class="text-lg font-bold">Workflow Master Reversal (REV)</h2>
                        <p class="mt-1 max-w-4xl text-sm text-base-content/65">Draft → review → approval → aktivasi. Draft dan approval tidak membuat master aktif, transaksi, jurnal, atau ledger. Reversal selalu membalik JournalLine original yang posted dan belum pernah dibalik.</p>
                    </div>
                    <span class="badge badge-outline whitespace-nowrap">Source Journal Reversal</span>
                </div>
            </div>

            @role('SuperAdmin')
                <div class="grid gap-4 border-b border-indigo-100 p-5 lg:grid-cols-2">
                    <div class="rounded-xl border border-base-300 p-4">
                        <h3 class="font-semibold">1. Dokumen governance REV</h3>
                        <p class="mt-1 text-xs text-base-content/60">Attachment aktif belum berarti disetujui. Approval memakai maker-checker normal atau scoped SuperAdmin Override yang dicatat sebagai penyimpangan segregation of duties.</p>
                        <form method="post" enctype="multipart/form-data" action="{{ route('financial-v2.configuration.reversal.documents.store') }}" class="mt-3 grid gap-2">
                            @csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                            <input type="file" name="document" accept="application/pdf,image/jpeg,image/png,image/webp" class="file-input file-input-bordered file-input-sm" required>
                            <input name="document_basis" class="input input-bordered input-sm" placeholder="Dasar/otoritas dokumen" required>
                            <button class="btn btn-outline btn-sm">Simpan draft dokumen</button>
                        </form>
                        <div class="mt-3 space-y-2">
                            @forelse($governanceDocuments as $document)
                                <div class="rounded-lg bg-base-200 p-3 text-xs"><div class="flex justify-between gap-2"><span class="font-semibold">{{ $document->attachment_id }}</span><span class="badge badge-sm">{{ $document->status }}</span></div><p class="mt-1">{{ $document->document_basis }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @if($document->status === 'draft')<form method="post" action="{{ route('financial-v2.configuration.reversal.documents.submit', $document) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-xs">Ajukan</button></form>@endif
                                        @if($document->status === 'pending_review')<form method="post" action="{{ route('financial-v2.configuration.reversal.documents.approve', $document) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="note" value="Scoped SuperAdmin Override untuk paket koreksi tanggal akuntansi September 2026."><button class="btn btn-success btn-xs">Setujui dengan Override</button></form>@if((int)$document->created_by_user_id !== (int)auth()->id())<form method="post" action="{{ route('financial-v2.configuration.reversal.documents.reject', $document) }}" class="flex gap-1">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input name="note" class="input input-bordered input-xs" placeholder="Alasan" required><button class="btn btn-error btn-outline btn-xs">Tolak</button></form>@endif @endif
                                        @if(in_array($document->status, ['draft', 'pending_review', 'approved'], true))<form method="post" action="{{ route('financial-v2.configuration.reversal.documents.cancel', $document) }}" class="flex gap-1">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input name="reason" class="input input-bordered input-xs" placeholder="Alasan pembatalan" required><button class="btn btn-ghost btn-xs">Batalkan</button></form>@endif
                                    </div>
                                </div>
                            @empty<p class="text-xs text-base-content/55">Belum ada dokumen governance.</p>@endforelse
                        </div>
                    </div>
                    <div class="rounded-xl border border-base-300 p-4">
                        <h3 class="font-semibold">2. Proposal prerequisite</h3>
                        <p class="mt-1 text-xs text-base-content/60">Approval proposal belum membuat master. Materialisasi membuat Transaction Type dan Sequence sama-sama draft/nonaktif.</p>
                        <form method="post" action="{{ route('financial-v2.configuration.reversal.prerequisites.store') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                            @csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="idempotency_key" value="{{ (string) \Illuminate\Support\Str::uuid() }}"><input type="hidden" name="transaction_type_code" value="REV">
                            <select name="governance_document_id" class="select select-bordered select-sm sm:col-span-2" required><option value="">Dokumen governance disetujui</option>@foreach($approvedGovernanceDocuments as $document)<option value="{{ $document->id }}">{{ $document->document_basis }} · {{ $document->approved_at?->format('d/m/Y') }}</option>@endforeach</select>
                            <input name="transaction_type_name" class="input input-bordered input-sm" placeholder="Nama Transaction Type REV" required><input name="transaction_type_description" class="input input-bordered input-sm" placeholder="Deskripsi/substansi REV" required>
                            <input name="voucher_prefix" class="input input-bordered input-sm" placeholder="Prefix voucher" required><input name="sequence_code" class="input input-bordered input-sm" placeholder="Kode sequence" required>
                            <input name="sequence_name" class="input input-bordered input-sm" placeholder="Nama sequence" required><input name="scope_key" class="input input-bordered input-sm" placeholder="Scope key" required>
                            <select name="reset_rule" class="select select-bordered select-sm" required><option value="">Reset rule</option><option value="never">Tidak reset</option><option value="yearly">Tahunan</option><option value="monthly">Bulanan</option></select><input type="number" min="1" name="initial_number" class="input input-bordered input-sm" placeholder="Nomor awal" required>
                            <input type="date" name="effective_from" class="input input-bordered input-sm" required><input type="date" name="effective_to" class="input input-bordered input-sm">
                            <select name="activation_separation_rule" class="select select-bordered select-sm sm:col-span-2" required><option value="">Pemisahan activator</option><option value="maker_checker">Maker dan checker berbeda</option><option value="three_distinct">Three distinct</option><option value="superadmin_override">Scoped SuperAdmin Override - 9 transaksi September 2026</option></select>
                            <button class="btn btn-outline btn-sm sm:col-span-2">Simpan proposal prerequisite</button>
                        </form>
                        <div class="mt-3 space-y-2">@forelse($reversalPrerequisiteProposals as $proposal)@php($isOverride = ($proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? null) === 'superadmin_override')<div class="rounded-lg bg-base-200 p-3 text-xs"><div class="flex justify-between gap-2"><span class="font-semibold">{{ $proposal->proposal_snapshot['transaction_type']['name'] ?? 'REV' }}</span><span class="badge badge-sm">{{ $proposal->status }}</span></div><p class="mt-1">{{ $proposal->proposal_snapshot['document_sequence']['code'] ?? '—' }} · {{ $proposal->proposal_snapshot['activation_governance']['separation_rule'] ?? '—' }}</p><div class="mt-2 flex flex-wrap gap-2">@if($proposal->status === 'draft')<form method="post" action="{{ route('financial-v2.configuration.reversal.prerequisites.submit', $proposal) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-xs">Ajukan</button></form>@elseif($proposal->status === 'pending_review' && ((int)$proposal->created_by_user_id !== (int)auth()->id() || $isOverride))<form method="post" action="{{ route('financial-v2.configuration.reversal.prerequisites.approve', $proposal) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-success btn-xs">{{ $isOverride ? 'Setujui dengan Override' : 'Setujui' }}</button></form>@if(!$isOverride)<form method="post" action="{{ route('financial-v2.configuration.reversal.prerequisites.reject', $proposal) }}" class="flex gap-1">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input name="note" class="input input-bordered input-xs" placeholder="Alasan" required><button class="btn btn-error btn-outline btn-xs">Tolak</button></form>@endif @elseif($proposal->status === 'approved' && ((int)$proposal->created_by_user_id !== (int)auth()->id() || $isOverride))<form method="post" action="{{ route('financial-v2.configuration.reversal.prerequisites.materialize', $proposal) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-warning btn-xs">Materialisasi draft master</button></form>@endif @if(in_array($proposal->status, ['draft', 'pending_review', 'approved'], true))<form method="post" action="{{ route('financial-v2.configuration.reversal.prerequisites.cancel', $proposal) }}" class="flex gap-1">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input name="reason" class="input input-bordered input-xs" placeholder="Alasan pembatalan" required><button class="btn btn-ghost btn-xs">Batalkan</button></form>@endif</div></div>@empty<p class="text-xs text-base-content/55">Belum ada proposal prerequisite.</p>@endforelse</div>
                    </div>
                </div>
            @endrole

            <div class="grid gap-5 p-5 xl:grid-cols-[minmax(0,1.05fr)_minmax(0,1.45fr)]">
                @role('SuperAdmin')
                    <form method="post" action="{{ route('financial-v2.configuration.reversal.store') }}" class="space-y-4 rounded-xl border border-base-300 p-4">
                        @csrf
                        <input type="hidden" name="entity" value="{{ $entity->id }}">
                        <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) \Illuminate\Support\Str::uuid()) }}">
                        <div>
                            <h3 class="font-semibold">Buat draft paket REV</h3>
                            <p class="mt-1 text-xs text-base-content/60">Nilai menjadi snapshot immutable. Perubahan substansi dibuat sebagai workflow baru.</p>
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="form-control sm:col-span-2"><span class="label-text mb-1 text-xs">Pasangan prerequisite materialized</span><select name="prerequisite_proposal_id" class="select select-bordered select-sm" required><option value="">Pilih pasangan draft/nonaktif yang disetujui</option>@foreach ($materializedReversalPrerequisites as $proposal)<option value="{{ $proposal->id }}" @selected(old('prerequisite_proposal_id') === $proposal->id)>{{ $proposal->transactionType?->name }} · {{ $proposal->documentSequence?->prefix }} · {{ $proposal->id }}</option>@endforeach</select></label>
                            <label class="form-control"><span class="label-text mb-1 text-xs">Kode Posting Rule</span><input name="posting_rule_code" class="input input-bordered input-sm" value="{{ old('posting_rule_code', 'REV-CANONICAL') }}" required></label>
                            <label class="form-control sm:col-span-2"><span class="label-text mb-1 text-xs">Nama Posting Rule</span><input name="posting_rule_name" class="input input-bordered input-sm" value="{{ old('posting_rule_name', 'Canonical Source Journal Reversal') }}" required></label>
                            <label class="form-control"><span class="label-text mb-1 text-xs">Efektif mulai</span><input type="date" name="effective_from" class="input input-bordered input-sm" value="{{ old('effective_from') }}" required></label>
                            <label class="form-control"><span class="label-text mb-1 text-xs">Efektif sampai (opsional)</span><input type="date" name="effective_to" class="input input-bordered input-sm" value="{{ old('effective_to') }}"></label>
                        </div>
                        <label class="form-control"><span class="label-text mb-1 text-xs">Referensi kontrak input</span><input name="input_contract_ref" class="input input-bordered input-sm" value="{{ old('input_contract_ref') }}" placeholder="Dokumen field dan lineage original" required></label>
                        <label class="form-control"><span class="label-text mb-1 text-xs">Referensi template jurnal</span><input name="journal_template_ref" class="input input-bordered input-sm" value="{{ old('journal_template_ref') }}" placeholder="Dokumen reversal JournalLine canonical" required></label>
                        <label class="form-control"><span class="label-text mb-1 text-xs">Referensi aturan bisnis</span><textarea name="business_rule_refs" class="textarea textarea-bordered textarea-sm" required>{{ old('business_rule_refs') }}</textarea></label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <label class="form-control"><span class="label-text mb-1 text-xs">Kode alasan reversal</span><input name="reason_code" class="input input-bordered input-sm" value="{{ old('reason_code', 'ACCOUNTING-CORRECTION') }}" required></label>
                            <label class="form-control"><span class="label-text mb-1 text-xs">Nama alasan</span><input name="reason_name" class="input input-bordered input-sm" value="{{ old('reason_name', 'Koreksi Tanggal Akuntansi') }}" required></label>
                            <label class="form-control"><span class="label-text mb-1 text-xs">Langkah approval transaksi REV</span><input type="number" min="1" max="10" name="required_approval_steps" class="input input-bordered input-sm" value="{{ old('required_approval_steps', 1) }}" required></label>
                            <div class="rounded-lg bg-base-200 p-3 text-xs">Dokumen governance dan approval diambil dari snapshot prerequisite terpilih; browser tidak dapat menggantinya.</div>
                        </div>
                        <fieldset class="rounded-xl bg-base-200/60 p-3">
                            <legend class="px-1 text-xs font-semibold">Bukti wajib transaksi REV</legend>
                            <div class="space-y-2">
                                @foreach ([0 => 'approval', 1 => '', 2 => ''] as $index => $defaultEvidence)
                                    <div class="grid gap-2 sm:grid-cols-[1fr_7rem]">
                                        <select name="evidence_requirements[{{ $index }}][evidence_type]" class="select select-bordered select-sm" @required($index === 0)>
                                            @if ($index > 0)<option value="">— Bukti tambahan —</option>@endif
                                            @foreach (['approval' => 'Dokumen approval', 'policy' => 'Dokumen kebijakan', 'statement' => 'Rekening koran', 'other' => 'Bukti lain'] as $value => $label)
                                                <option value="{{ $value }}" @selected(old("evidence_requirements.{$index}.evidence_type", $defaultEvidence) === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" min="1" max="20" name="evidence_requirements[{{ $index }}][minimum_count]" class="input input-bordered input-sm" value="{{ old("evidence_requirements.{$index}.minimum_count", $index === 0 ? 1 : null) }}" aria-label="Jumlah minimum bukti">
                                    </div>
                                @endforeach
                            </div>
                        </fieldset>
                        <button class="btn btn-primary btn-sm w-full">Simpan Draft REV</button>
                    </form>
                @else
                    <div class="rounded-xl border border-base-300 p-4 text-sm text-base-content/65">Hanya SuperAdmin dapat mengelola workflow konfigurasi REV.</div>
                @endrole

                <div class="min-w-0">
                    <h3 class="mb-3 font-semibold">Riwayat workflow</h3>
                    <div class="space-y-3">
                        @forelse ($reversalConfigurationRequests as $configurationRequest)
                            @php($snapshot = $configurationRequest->configuration_snapshot)
                            @php($configurationOverride = ($snapshot['prerequisite']['activation_separation_rule'] ?? null) === 'superadmin_override')
                            <article class="rounded-xl border border-base-300 p-4">
                                <div class="flex flex-col justify-between gap-2 sm:flex-row sm:items-start">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2"><span class="font-semibold">REV · {{ $snapshot['posting_rule']['code'] ?? '—' }}</span><span class="badge badge-sm {{ $statusBadge($configurationRequest->status) }}">{{ str_replace('_', ' ', ucfirst($configurationRequest->status)) }}</span></div>
                                        <p class="mt-1 text-xs text-base-content/55">{{ $configurationRequest->id }} · {{ $configurationRequest->created_at?->format('d/m/Y H:i') }}</p>
                                    </div>
                                    <span class="text-xs text-base-content/60">Efektif {{ \Illuminate\Support\Carbon::parse($snapshot['effective_from'])->format('d/m/Y') }}</span>
                                </div>
                                <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
                                    <div><dt class="text-base-content/55">Kompilasi jurnal</dt><dd class="font-medium">Source Journal Reversal</dd></div>
                                    <div><dt class="text-base-content/55">Rule lines</dt><dd class="font-medium">Tidak digunakan</dd></div>
                                    <div><dt class="text-base-content/55">Reason</dt><dd>{{ $snapshot['reason_code']['code'] ?? '—' }}</dd></div>
                                    <div><dt class="text-base-content/55">Approval / bukti</dt><dd>{{ $snapshot['approval']['required_steps'] ?? 0 }} langkah · {{ count($snapshot['evidence_requirements'] ?? []) }} jenis</dd></div>
                                    <div class="sm:col-span-2"><dt class="text-base-content/55">Dokumen governance</dt><dd class="break-all">Attachment {{ $configurationRequest->governance_attachment_id ?? '—' }} · {{ \Illuminate\Support\Str::limit($snapshot['governance_document']['content_hash'] ?? '', 20) }}</dd></div>
                                </dl>
                                @role('SuperAdmin')
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @if ($configurationRequest->status === 'draft')
                                            <form method="post" action="{{ route('financial-v2.configuration.reversal.submit', $configurationRequest) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-xs">Ajukan review</button></form>
                                        @elseif ($configurationRequest->status === 'pending_review' && ((int) $configurationRequest->created_by_user_id !== (int) auth()->id() || $configurationOverride))
                                            <form method="post" action="{{ route('financial-v2.configuration.reversal.approve', $configurationRequest) }}">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-success btn-xs">{{ $configurationOverride ? 'Setujui dengan Override' : 'Setujui governance' }}</button></form>
                                            @if(!$configurationOverride)<form method="post" action="{{ route('financial-v2.configuration.reversal.reject', $configurationRequest) }}" class="flex gap-2"><input type="hidden" name="entity" value="{{ $entity->id }}">@csrf<input name="note" class="input input-bordered input-xs" placeholder="Alasan penolakan" required><button class="btn btn-error btn-outline btn-xs">Tolak</button></form>@endif
                                        @elseif ($configurationRequest->status === 'approved' && ((int) $configurationRequest->created_by_user_id !== (int) auth()->id() || $configurationOverride))
                                            <form method="post" action="{{ route('financial-v2.configuration.reversal.activate', $configurationRequest) }}" onsubmit="return confirm('Aktivasi akan membuat master REV efektif dengan scoped SuperAdmin Override. Tindakan ini tidak membuat transaksi atau jurnal. Lanjutkan?')">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-warning btn-xs">Aktifkan master REV{{ $configurationOverride ? ' dengan Override' : '' }}</button></form>
                                        @endif
                                        @if (in_array($configurationRequest->status, ['draft', 'pending_review', 'approved'], true))
                                            <form method="post" action="{{ route('financial-v2.configuration.reversal.cancel', $configurationRequest) }}" class="flex gap-2">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input name="reason" class="input input-bordered input-xs" placeholder="Alasan pembatalan" required><button class="btn btn-ghost btn-xs">Batalkan</button></form>
                                        @endif
                                    </div>
                                @endrole
                                @if ($configurationRequest->decisions->isNotEmpty())
                                    <p class="mt-3 text-xs text-base-content/55">Keputusan: {{ $configurationRequest->decisions->map(fn ($item) => ucfirst($item->decision).' '.$item->decided_at?->format('d/m/Y H:i'))->join(' · ') }}</p>
                                @endif
                            </article>
                        @empty
                            <div class="rounded-xl border border-dashed border-base-300 p-8 text-center text-sm text-base-content/55">Belum ada draft konfigurasi REV. Tidak ada master REV yang dibuat otomatis.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ([
                ['Rekening / Kas', $financialAccounts->count(), 'financial-v2.masters.accounts.index'],
                ['Dana', $funds->count(), 'financial-v2.masters.funds.index'],
                ['Kategori', $categories->count(), 'financial-v2.masters.categories.index'],
                ['Program', $programs->count(), 'financial-v2.masters.programs.index'],
                ['Aturan Dana', $fundPolicyVersions->count(), 'financial-v2.masters.policies.index'],
            ] as [$label, $count, $routeName])
                <a href="{{ route($routeName, ['entity' => $entity->id]) }}" class="rounded-2xl border border-base-300 bg-base-100 p-4 shadow-sm transition hover:border-emerald-300">
                    <p class="text-xs text-base-content/60">{{ $label }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ $count }}</p>
                    <p class="mt-2 text-xs font-semibold text-emerald-700">Lihat dan kelola →</p>
                </a>
            @endforeach
        </section>

        @if ($fidyahAllocationStatus)
            @include('masjid.mrj.admin.financial-v2.masters._configuration-provision', ['provision' => [
                'title' => 'Konfigurasi Alokasi Fidyah',
                'status' => $fidyahAllocationStatus,
                'summary' => '22/08/2026 · PAY · Penyaluran Fidyah · Dana Fidyah + Dana Infaq & Tromol · Tanpa Program.',
                'info' => 'Perubahan hanya pada configuration. Tidak membuat transaksi keuangan.',
                'button_label' => 'Provision Konfigurasi Alokasi Fidyah',
                'active_label' => 'Konfigurasi Alokasi Fidyah Sudah Aktif',
                'modal_id' => 'provision-fidyah-allocation',
                'modal_title' => 'Provision konfigurasi Alokasi Fidyah?',
                'action' => route('financial-v2.configuration.provision-fidyah-allocation'),
                'details' => [
                    'Tanggal' => '22/08/2026',
                    'Kategori' => 'Penyaluran Fidyah',
                    'Sumber' => "Fidyah\nInfaq & Tromol",
                    'Program' => 'Tanpa Program',
                ],
            ]])
        @endif

        @if ($legacyProgramLifecycleStatus)
            @include('masjid.mrj.admin.financial-v2.masters._configuration-provision', ['provision' => [
                'title' => 'Lifecycle Program Legacy',
                'status' => $legacyProgramLifecycleStatus,
                'summary' => 'Santunan Anak Yatim Bulanan · tanggal mulai bisnis legacy belum diketahui · tanggal transaksi Financial V2 tidak dipakai sebagai lifecycle Program.',
                'info' => 'Koreksi hanya mengosongkan tanggal cutover 15/08/2026 yang terbukti berasal dari provisioning Phase 12. Financial fact dan policy tidak berubah.',
                'button_label' => 'Koreksi Lifecycle Program',
                'active_label' => 'Lifecycle Program Sudah Benar',
                'modal_id' => 'correct-legacy-program-lifecycle',
                'modal_title' => 'Koreksi lifecycle Program legacy?',
                'action' => route('financial-v2.configuration.correct-legacy-program-lifecycle'),
                'details' => [
                    'Program' => 'Santunan Anak Yatim Bulanan',
                    'Nilai lama' => '15/08/2026 (cutover Financial V2)',
                    'Nilai benar' => 'Belum diketahui / tanpa batas awal',
                    'Dampak' => 'Master Program saja',
                ],
            ]])
        @endif

        @if ($historicalDhuafaStatus)
            <section class="mt-5 rounded-2xl border border-amber-300 bg-amber-50 p-5 text-amber-950 shadow-sm">
                <div class="flex flex-col justify-between gap-4 lg:flex-row lg:items-center">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-bold">Konfigurasi Historis DHUAFA</h2>
                            <span class="badge badge-sm {{ $historicalDhuafaStatus['ready'] ? 'badge-success' : 'badge-warning' }}">
                                {{ $historicalDhuafaStatus['ready'] ? 'Siap digunakan' : 'Belum diprovision' }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm leading-6">11/07/2026 · Penerimaan · BNI ZISWAF · Dana Dhuafa &amp; Anak Yatim · Donasi · Tanpa Program.</p>
                        <p class="text-xs leading-5 opacity-75">Provisioning hanya menambah konfigurasi accounting dan audit trail. Tidak membuat transaksi, jurnal, ledger, atau voucher.</p>
                    </div>
                    @unless ($historicalDhuafaStatus['ready'])
                        <form method="post" action="{{ route('financial-v2.configuration.provision-historical-dhuafa') }}" onsubmit="return confirm('Ini hanya mengaktifkan konfigurasi accounting.\nTidak membuat transaksi keuangan.')">
                            @csrf
                            <input type="hidden" name="entity" value="{{ $entity->id }}">
                            <button class="btn btn-warning whitespace-nowrap">Provision Konfigurasi Historis DHUAFA</button>
                        </form>
                    @endunless
                </div>
            </section>
        @endif

        <section class="mt-5 rounded-2xl border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-5"><h2 class="text-lg font-bold">Aturan Pencatatan dan Bukti</h2><p class="mt-1 text-sm text-base-content/65">Versi dipilih dari tanggal transaksi. Versi superseded yang disetujui tetap tersedia untuk periode historisnya.</p></div>
            <div class="overflow-x-auto">
                <table class="table table-sm">
                    <thead><tr><th>Jenis transaksi</th><th>Aturan</th><th>Versi</th><th>Periode</th><th>Status</th><th>Bukti</th></tr></thead>
                    <tbody>
                        @forelse ($postingRules as $rule)
                            @forelse ($rule->versions as $version)
                                <tr>
                                    <td><span class="font-semibold">{{ $rule->transactionType?->name ?? '—' }}</span><span class="block text-xs text-base-content/55">{{ $rule->transactionType?->code }}</span></td>
                                    <td>{{ $rule->name }}<span class="block text-xs text-base-content/55">{{ $rule->rule_family }}</span></td>
                                    <td>{{ $version->version_no }}</td>
                                    <td class="whitespace-nowrap">{{ $version->effective_from?->format('d/m/Y') }} — {{ $version->effective_to?->format('d/m/Y') ?? 'seterusnya' }}</td>
                                    <td><span class="badge badge-sm {{ $statusBadge($version->status) }}">{{ ucfirst($version->status) }}</span></td>
                                    <td>{{ $version->evidenceRequirements->map(fn ($item) => strtoupper($item->evidence_type).' × '.$item->minimum_count)->join(', ') ?: 'Tidak ada' }}</td>
                                </tr>
                            @empty
                                <tr><td>{{ $rule->transactionType?->name ?? '—' }}</td><td>{{ $rule->name }}</td><td colspan="4" class="text-base-content/55">Belum memiliki versi.</td></tr>
                            @endforelse
                        @empty
                            <tr><td colspan="6" class="py-8 text-center text-base-content/55">Belum ada aturan pencatatan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="mt-5 grid gap-5 xl:grid-cols-2">
            <div class="rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-5"><h2 class="font-bold">Persetujuan</h2><p class="mt-1 text-sm text-base-content/65">Resolver memilih persyaratan yang berlaku pada tanggal transaksi.</p></div>
                <div class="overflow-x-auto"><table class="table table-sm"><thead><tr><th>Jenis</th><th>Langkah</th><th>Periode</th><th>Status</th></tr></thead><tbody>
                    @forelse ($approvalRequirements as $requirement)<tr><td>{{ $typeNames[$requirement->transaction_type_id] ?? '—' }}</td><td>{{ $requirement->required_steps }}</td><td class="whitespace-nowrap">{{ $requirement->effective_from?->format('d/m/Y') }} — {{ $requirement->effective_to?->format('d/m/Y') ?? 'seterusnya' }}</td><td><span class="badge badge-sm {{ $statusBadge($requirement->status) }}">{{ ucfirst($requirement->status) }}</span></td></tr>
                    @empty<tr><td colspan="4" class="py-8 text-center text-base-content/55">Belum ada persyaratan persetujuan.</td></tr>@endforelse
                </tbody></table></div>
            </div>

            <div class="rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-5"><div class="flex items-center justify-between gap-3"><div><h2 class="font-bold">Aturan Dana</h2><p class="mt-1 text-sm text-base-content/65">Perubahan dilakukan sebagai versi baru agar riwayat tetap utuh.</p></div><a class="btn btn-outline btn-sm" href="{{ route('financial-v2.masters.policies.index', ['entity' => $entity->id]) }}">Kelola</a></div></div>
                <div class="overflow-x-auto"><table class="table table-sm"><thead><tr><th>Dana</th><th>Versi</th><th>Periode</th><th>Aturan</th><th>Status</th><th>Penggunaan</th></tr></thead><tbody>
                    @forelse ($fundPolicyVersions as $version)<tr><td>{{ $version->fund?->name ?? '—' }}</td><td>{{ $version->version_no }}</td><td class="whitespace-nowrap">{{ $version->effective_from?->format('d/m/Y') }} — {{ $version->effective_to?->format('d/m/Y') ?? 'seterusnya' }}</td><td>{{ $version->rules->count() }}</td><td><span class="badge badge-sm {{ $statusBadge($version->status) }}">{{ ucfirst($version->status) }}</span></td><td class="text-xs">{{ $fundPolicyUsage->get($version->id)['message'] ?? 'Versi digunakan — tidak dapat dihapus' }}</td></tr>
                    @empty<tr><td colspan="6" class="py-8 text-center text-base-content/55">Belum ada versi Aturan Dana.</td></tr>@endforelse
                </tbody></table></div>
            </div>
        </section>

        <section class="mt-5 rounded-2xl border border-base-300 bg-base-100 shadow-sm">
            <div class="border-b border-base-300 p-5"><h2 class="font-bold">Kebijakan Mutasi Bank</h2><p class="mt-1 text-sm text-base-content/65">Kombinasi rekening, Dana, jenis transaksi, kategori, dan periode yang diselesaikan oleh resolver yang sama.</p></div>
            <div class="overflow-x-auto"><table class="table table-sm"><thead><tr><th>Jenis</th><th>Rekening</th><th>Dana</th><th>Kategori</th><th>Periode</th><th>Aturan</th><th>Status</th></tr></thead><tbody>
                @forelse ($bankPolicies as $policy)<tr><td>{{ $policy->transactionType?->name ?? '—' }}</td><td>{{ $policy->financialAccount?->name ?? '—' }}</td><td>{{ $policy->fund?->name ?? '—' }}</td><td>{{ $policy->category?->name ?? '—' }}</td><td class="whitespace-nowrap">{{ $policy->effective_from?->format('d/m/Y') }} — {{ $policy->effective_to?->format('d/m/Y') ?? 'seterusnya' }}</td><td>Versi {{ $policy->postingRuleVersion?->version_no ?? '—' }}</td><td><span class="badge badge-sm {{ $statusBadge($policy->status) }}">{{ ucfirst($policy->status) }}</span></td></tr>
                @empty<tr><td colspan="7" class="py-8 text-center text-base-content/55">Belum ada kebijakan Mutasi Bank.</td></tr>@endforelse
            </tbody></table></div>
        </section>
    @endif
@endsection
