@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', ($transaction ? 'Ubah Draft ' : 'Tambah ').$definition['label'])

@section('content')
    @php
        $split = $transaction?->splits->first();
        $value = fn ($key, $default = null) => old($key, $default);
        $sourceDefault = $transaction && preg_match('/^Sumber:\s*(.*?)\n\n/s', (string) $transaction->description, $matches) ? $matches[1] : null;
        $descriptionDefault = $transaction ? preg_replace('/^Sumber:\s*.*?\n\n/s', '', (string) $transaction->description) : null;
        $isEdit = (bool) $transaction;
        $amountValue = $value('amount', $transaction?->gross_amount);
        $counterpartyName = $value('counterparty_name', $transaction?->counterparty?->display_name);
        $fundForAllocation = fn ($version) => $options['funds']->firstWhere('id', $version->allocation?->fund_id)?->name ?? 'Dana';
        $programForAllocation = fn ($version) => $options['programs']->firstWhere('id', $version->allocation?->program_id)?->name;
        $selectedAllocationVersionId = $selectedAllocationVersionId ?? null;
    @endphp
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <a class="link text-sm text-base-content/60" href="{{ route('financial-v2.transactions.index', ['entity' => $entity?->id]) }}">← Kembali ke riwayat</a>
            <h1 class="mt-2 text-2xl font-bold">{{ $isEdit ? 'Ubah draft' : 'Tambah' }} {{ $definition['label'] }}</h1>
            <p class="mt-1 text-sm text-base-content/65">Isi kejadian yang terjadi. Sistem menerjemahkannya ke pencatatan keuangan secara otomatis.</p>
        </div>
        <span class="badge badge-outline">{{ $isEdit ? 'DRAFT' : 'DRAFT BARU' }}</span>
    </div>

    @if (! $entity)
        <div class="alert items-start border border-amber-200 bg-amber-50 text-amber-950"><span>Pilih satu entitas keuangan aktif terlebih dahulu. Tidak ada master, saldo awal, atau transaksi aktual yang dibuat otomatis.</span></div>
        @if ($entities->isNotEmpty())
            <form method="GET" class="mt-4 flex max-w-md gap-2"><select name="entity" class="select select-bordered grow"><option value="">Pilih entitas</option>@foreach($entities as $availableEntity)<option value="{{ $availableEntity->id }}">{{ $availableEntity->name }}</option>@endforeach</select><button class="btn btn-primary">Pilih</button></form>
        @endif
    @else
        <form method="POST" action="{{ $isEdit ? route('financial-v2.transactions.update', $transaction) : route('financial-v2.transactions.store', $operation) }}" enctype="multipart/form-data" data-financial-ajax data-operation="{{ $operation }}" data-preview-url="{{ route('financial-v2.preview') }}" class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
            @csrf
            @if ($isEdit) @method('PUT') @endif
            <input type="hidden" name="entity" value="{{ $entity->id }}">
            <input type="hidden" name="submission_key" value="{{ $submissionKey }}">

            <section class="rounded-2xl bg-base-100 p-4 shadow-sm ring-1 ring-base-300 sm:p-6">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text font-medium">Tanggal</span><input type="date" name="date" value="{{ $value('date', $transaction?->accounting_date?->toDateString() ?? $today) }}" class="input input-bordered w-full" required></label>
                    <label class="form-control"><span class="label-text font-medium">Nominal</span><div class="relative" data-money-field><span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base-content/55">Rp</span><input type="hidden" name="amount" value="{{ $amountValue }}" data-money-value><input type="text" inputmode="decimal" autocomplete="off" value="{{ $amountValue !== null && $amountValue !== '' ? number_format((float) $amountValue, 2, ',', '.') : '' }}" placeholder="0" class="input input-bordered w-full pl-9 text-lg font-semibold" data-money-input required></div><span class="label-text-alt">Pemisah ribuan dibuat otomatis. Gunakan koma untuk sen.</span></label>
                </div>

                @if ($operation === 'receipt')
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text font-medium">Sumber</span><input type="text" name="source" value="{{ $value('source', $sourceDefault) }}" placeholder="Contoh: Infak Jumat" class="input input-bordered w-full" required></label>
                        <label class="form-control"><span class="label-text font-medium">Masuk ke kas/rekening</span><select name="financial_account_id" class="select select-bordered w-full" required><option value="">Pilih kas/rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected($value('financial_account_id', $transaction?->primary_financial_account_id) === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Dana</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected($value('fund_id', $split?->fund_id) === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Kategori</span><select name="category_id" class="select select-bordered w-full" required><option value="">Pilih kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected($value('category_id', $transaction?->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    </div>
                    <label class="form-control mt-4"><span class="label-text font-medium">Program <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="program_id" class="select select-bordered w-full"><option value="">Tanpa program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected($value('program_id', $split?->program_id) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                @elseif (in_array($operation, ['payment', 'realization'], true))
                    @if ($operation === 'realization')
                        <label class="form-control mt-4"><span class="label-text font-medium">Alokasi dana</span><select name="budget_allocation_version_id" data-realization-allocation class="select select-bordered w-full" required><option value="">Pilih alokasi yang sudah disetujui</option>@foreach($options['allocationVersions'] as $version)<option value="{{ $version->id }}" data-allocated="{{ $version->availability['allocated'] }}" data-actual="{{ $version->availability['actual'] }}" data-available="{{ $version->availability['available'] }}" @selected($value('budget_allocation_version_id', $transaction?->realization?->budget_allocation_version_id ?? $selectedAllocationVersionId) === $version->id)>{{ $fundForAllocation($version) }}{{ $programForAllocation($version) ? ' · '.$programForAllocation($version) : '' }} · total Rp{{ number_format((float) $version->availability['allocated'], 2, ',', '.') }} · sisa Rp{{ number_format((float) $version->availability['available'], 2, ',', '.') }}</option>@endforeach</select><span class="label-text-alt">Alokasi hanya menentukan peruntukan. Realisasi akan dicatat sebagai pengeluaran resmi.</span></label>
                        <div data-realization-summary class="mt-3 hidden rounded-xl bg-base-200 px-3 py-3 text-sm"></div>
                    @endif
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control sm:col-span-2 {{ $operation === 'realization' ? 'rounded-xl border border-primary/20 bg-primary/5 p-3' : '' }}"><span class="label-text font-semibold">Dibayarkan kepada</span><input type="text" name="counterparty_name" value="{{ $counterpartyName }}" list="counterparty-options" placeholder="Contoh: Penerima Santunan Anak Yatim" class="input input-bordered w-full" required><datalist id="counterparty-options">@foreach($options['counterparties'] as $counterparty)<option value="{{ $counterparty->display_name }}">@endforeach</datalist><span class="label-text-alt">Ketik nama penerima. Nama yang belum ada akan disimpan sebagai pihak penerima agar dapat dipakai lagi.</span></label>
                        <label class="form-control"><span class="label-text font-medium">Dibayar dari kas/rekening</span><select name="financial_account_id" class="select select-bordered w-full" required><option value="">Pilih kas/rekening</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected($value('financial_account_id', $transaction?->primary_financial_account_id) === $account->id)>{{ $account->name }}</option>@endforeach</select></label>
                        @if ($operation === 'payment')
                            <label class="form-control"><span class="label-text font-medium">Dana</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}" @selected($value('fund_id', $split?->fund_id) === $fund->id)>{{ $fund->name }}</option>@endforeach</select></label>
                        @endif
                        <label class="form-control"><span class="label-text font-medium">Kategori</span><select name="category_id" class="select select-bordered w-full" required><option value="">Pilih kategori</option>@foreach($options['categories'] as $category)<option value="{{ $category->id }}" @selected($value('category_id', $transaction?->category_id) === $category->id)>{{ $category->name }}</option>@endforeach</select></label>
                    </div>
                    @if ($operation === 'payment')
                        <label class="form-control mt-4"><span class="label-text font-medium">Program <span class="font-normal text-base-content/55">(jika relevan)</span></span><select name="program_id" class="select select-bordered w-full"><option value="">Tanpa program</option>@foreach($options['programs'] as $program)<option value="{{ $program->id }}" @selected($value('program_id', $split?->program_id) === $program->id)>{{ $program->name }}</option>@endforeach</select></label>
                    @endif
                @elseif ($operation === 'transfer')
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text font-medium">Rekening asal</span><select name="source_financial_account_id" class="select select-bordered w-full" required><option value="">Pilih rekening asal</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Rekening tujuan</span><select name="destination_financial_account_id" class="select select-bordered w-full" required><option value="">Pilih rekening tujuan</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}">{{ $account->name }}</option>@endforeach</select></label>
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Dana yang dipindahkan</span><select name="fund_id" class="select select-bordered w-full" required><option value="">Pilih dana</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select><span class="label-text-alt">Transfer bukan pemasukan atau pengeluaran.</span></label>
                    </div>
                @elseif ($operation === 'interfund')
                    <div class="mt-4 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text font-medium">Dana asal</span><select name="source_fund_id" class="select select-bordered w-full" required><option value="">Pilih dana asal</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select></label>
                        <label class="form-control"><span class="label-text font-medium">Dana tujuan</span><select name="destination_fund_id" class="select select-bordered w-full" required><option value="">Pilih dana tujuan</option>@foreach($options['funds'] as $fund)<option value="{{ $fund->id }}">{{ $fund->name }}</option>@endforeach</select></label>
                        <label class="form-control sm:col-span-2"><span class="label-text font-medium">Rekening atribusi <span class="font-normal text-base-content/55">(saldo tidak berpindah)</span></span><select name="financial_account_id" class="select select-bordered w-full" required><option value="">Pilih lokasi uang yang direklasifikasi</option>@foreach($options['financialAccounts'] as $account)<option value="{{ $account->id }}" @selected($value('financial_account_id', $transaction?->primary_financial_account_id) === $account->id)>{{ $account->name }}</option>@endforeach</select><span class="label-text-alt">Pilihan ini hanya menjelaskan bagian saldo rekening yang berubah peruntukan Dana; kas/bank tidak dipindahkan.</span></label>
                    </div>
                    <label class="form-control mt-4"><span class="label-text font-medium">Rujukan kebijakan</span><input type="text" name="policy_basis_ref" value="{{ $value('policy_basis_ref') }}" placeholder="Contoh: SK/Notulen persetujuan" class="input input-bordered w-full" required><span class="label-text-alt">Dana terikat tetap fail-closed bila matriks kebijakannya tidak mengizinkan.</span></label>
                    <label class="form-control mt-4"><span class="label-text font-medium">Alasan pindah dana</span><textarea name="reason" rows="3" class="textarea textarea-bordered w-full" required>{{ $value('reason') }}</textarea></label>
                @endif

                <label class="form-control mt-4"><span class="label-text font-medium">Keterangan</span><textarea name="description" rows="3" class="textarea textarea-bordered w-full" placeholder="Tambahkan keterangan agar mudah ditelusuri.">{{ $value('description', $descriptionDefault) }}</textarea></label>
                <label class="form-control mt-4"><span class="label-text font-medium">Lampiran bukti <span class="font-normal text-base-content/55">(JPG, PNG, atau PDF; maks. 10 MB)</span></span><input type="file" name="attachment" accept="image/jpeg,image/png,application/pdf" class="file-input file-input-bordered w-full"><span class="label-text-alt">Jika bukti diwajibkan oleh aturan transaksi, lampirkan sebelum transaksi dicatat resmi.</span></label>

                @if (in_array($operation, ['receipt', 'payment', 'realization'], true))
                    <div class="mt-4"><button type="button" data-financial-preview class="btn btn-ghost btn-sm">Periksa kombinasi dana</button><p data-preview-output class="hidden"></p></div>
                @endif
                <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><a class="btn btn-ghost" href="{{ route('financial-v2.transactions.index', ['entity' => $entity->id]) }}">Batal</a><button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan perubahan draft' : 'Simpan sebagai draft' }}</button></div>
            </section>

            <aside class="space-y-4">
                <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-950"><p class="font-bold">Cara kerja pencatatan</p><ol class="mt-2 list-decimal space-y-1 pl-5 text-xs leading-5"><li>Simpan dulu sebagai draft.</li><li>Periksa data dan lampiran bukti.</li><li>Pilih “catat resmi” dari detail transaksi.</li></ol></div>
                <div class="rounded-2xl bg-base-100 p-4 text-sm shadow-sm ring-1 ring-base-300"><p class="font-bold">Status transaksi</p><dl class="mt-3 space-y-2 text-xs"><div><dt class="font-semibold">Draft</dt><dd class="text-base-content/60">Masih dapat diperiksa dan diubah.</dd></div><div><dt class="font-semibold">Diajukan / Disetujui</dt><dd class="text-base-content/60">Sedang melalui pemeriksaan atau persetujuan yang berlaku.</dd></div><div><dt class="font-semibold">Dicatat resmi</dt><dd class="text-base-content/60">Sudah dicatat secara resmi dan tidak dapat diubah langsung.</dd></div></dl></div>
            </aside>
        </form>
    @endif
@endsection
