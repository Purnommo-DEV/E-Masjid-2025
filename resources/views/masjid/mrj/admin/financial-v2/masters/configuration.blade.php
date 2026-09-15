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
