@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Master Rekening / Kas')

@section('content')
    @include('masjid.mrj.admin.financial-v2.masters._header', [
        'title' => 'Rekening / Kas',
        'subtitle' => 'Rekening menjawab tempat uang berada. Dana tetap dipilih terpisah saat transaksi, sehingga satu rekening tidak disamakan dengan satu Dana.',
    ])

    @if ($entity)
        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.25fr)]">
            <form method="post" action="{{ route('financial-v2.masters.accounts.store') }}" data-financial-ajax class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                @csrf
                <input type="hidden" name="entity" value="{{ $entity->id }}">
                <h2 class="text-lg font-bold">Tambah Rekening / Kas</h2>
                <p class="mt-1 text-sm text-base-content/65">Disimpan sebagai draft terlebih dahulu. Tidak membuat saldo atau pencatatan akuntansi.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text text-sm">Nama</span><input required name="name" maxlength="160" class="input input-bordered" placeholder="Contoh: nama rekening atau kas"></label>
                    <label class="form-control"><span class="label-text text-sm">Kode</span><input required name="code" maxlength="40" class="input input-bordered" placeholder="Contoh: KAS-001"></label>
                    <label class="form-control"><span class="label-text text-sm">Jenis</span>
                        <select required name="account_type" class="select select-bordered"><option value="bank">Bank</option><option value="cash">Kas</option><option value="petty_cash">Petty cash</option><option value="e_wallet">E-wallet</option></select>
                    </label>
                    <label class="form-control"><span class="label-text text-sm">Basis pencatatan internal</span>
                        <select required name="account_id" class="select select-bordered"><option value="">Pilih akun</option>@foreach ($liquidityAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} — {{ $account->name }}</option>@endforeach</select>
                    </label>
                    <label class="form-control"><span class="label-text text-sm">Tanggal mulai</span><input required type="date" name="opening_date" value="{{ now()->toDateString() }}" class="input input-bordered"></label>
                    <label class="form-control"><span class="label-text text-sm">Mata uang</span><input required name="currency_code" value="IDR" maxlength="3" class="input input-bordered"></label>
                    <label class="form-control sm:col-span-2"><span class="label-text text-sm">Referensi kustodian <span class="text-base-content/45">(opsional)</span></span><input name="custodian_reference" maxlength="100" class="input input-bordered" placeholder="Referensi penanggung jawab atau arsip"></label>
                </div>
                <div class="mt-5 rounded-xl bg-base-200 p-4">
                    <p class="text-sm font-semibold">Rincian bank atau kas</p>
                    <p class="mt-1 text-xs text-base-content/60">Isi rincian yang sesuai dengan jenis yang dipilih. Nomor rekening hanya disimpan dalam bentuk tersamarkan.</p>
                    <div class="mt-3 grid gap-4 sm:grid-cols-2">
                        <label class="form-control"><span class="label-text text-sm">Nama bank</span><input name="bank_name" maxlength="160" class="input input-bordered" placeholder="Untuk jenis Bank"></label>
                        <label class="form-control"><span class="label-text text-sm">Nomor tersamarkan</span><input name="account_number_masked" maxlength="80" class="input input-bordered" placeholder="Contoh: ****1234"></label>
                        <label class="form-control"><span class="label-text text-sm">Cabang <span class="text-base-content/45">(opsional)</span></span><input name="branch_name" maxlength="160" class="input input-bordered"></label>
                        <label class="form-control"><span class="label-text text-sm">Referensi terlindungi <span class="text-base-content/45">(opsional)</span></span><input name="account_number_protected_ref" maxlength="500" class="input input-bordered"></label>
                        <label class="form-control"><span class="label-text text-sm">Lokasi kas</span><input name="cash_location" maxlength="240" class="input input-bordered" placeholder="Untuk Kas / Petty cash"></label>
                        <label class="form-control"><span class="label-text text-sm">Frekuensi hitung kas</span><select name="cash_count_frequency" class="select select-bordered"><option value="">Pilih bila kas</option><option value="daily">Harian</option><option value="weekly">Mingguan</option><option value="monthly">Bulanan</option><option value="ad_hoc">Sesuai kebutuhan</option></select></label>
                        <label class="form-control"><span class="label-text text-sm">Batas petty cash <span class="text-base-content/45">(opsional)</span></span><input name="petty_cash_limit" inputmode="decimal" class="input input-bordered" placeholder="0.00"></label>
                    </div>
                </div>
                <button class="btn btn-primary mt-5 w-full sm:w-auto">Simpan draft Rekening / Kas</button>
            </form>

            <section class="rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-5"><h2 class="text-lg font-bold">Daftar Rekening / Kas</h2><p class="mt-1 text-sm text-base-content/65">Penghapusan tidak tersedia. Nonaktifkan master yang sudah tidak digunakan.</p></div>
                <div class="divide-y divide-base-200">
                    @forelse ($financialAccounts as $financialAccount)
                        <article class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div><p class="font-semibold">{{ $financialAccount->name }}</p><p class="text-sm text-base-content/60">{{ $financialAccount->code }} · {{ str($financialAccount->account_type)->replace('_', ' ')->title() }} · {{ $financialAccount->account?->name }}</p></div>
                                <span class="badge {{ $financialAccount->status === 'active' ? 'badge-success' : 'badge-ghost' }}">{{ ucfirst($financialAccount->status) }}</span>
                            </div>
                            <p class="mt-2 text-sm text-base-content/65">
                                @if ($financialAccount->bankDetail) {{ $financialAccount->bankDetail->bank_name }} · {{ $financialAccount->bankDetail->account_number_masked }}
                                @elseif ($financialAccount->cashDetail) {{ $financialAccount->cashDetail->cash_location }} · Hitung {{ $financialAccount->cashDetail->cash_count_frequency }}
                                @else Tidak ada rincian kustodian tambahan. @endif
                            </p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($financialAccount->status !== 'active' && $financialAccount->status !== 'closed')
                                    <form method="post" action="{{ route('financial-v2.masters.accounts.activate', $financialAccount) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="effective_date" value="{{ now()->toDateString() }}"><button class="btn btn-success btn-sm">Aktifkan</button></form>
                                @endif
                                @if ($financialAccount->status === 'active')
                                    <form method="post" action="{{ route('financial-v2.masters.accounts.deactivate', $financialAccount) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><input type="hidden" name="effective_date" value="{{ now()->toDateString() }}"><button class="btn btn-outline btn-sm">Nonaktifkan</button></form>
                                @endif
                            </div>
                            @if ($financialAccount->status !== 'active')
                                <details class="mt-4 rounded-xl bg-base-200 p-4">
                                    <summary class="cursor-pointer text-sm font-semibold">Ubah draft</summary>
                                    <form method="post" action="{{ route('financial-v2.masters.accounts.update', $financialAccount) }}" data-financial-ajax class="mt-4 grid gap-3 sm:grid-cols-2">
                                        @csrf @method('PUT')<input type="hidden" name="entity" value="{{ $entity->id }}">
                                        <input required name="name" value="{{ $financialAccount->name }}" class="input input-bordered input-sm"><input required name="code" value="{{ $financialAccount->code }}" class="input input-bordered input-sm">
                                        <select required name="account_type" class="select select-bordered select-sm">@foreach (['bank' => 'Bank', 'cash' => 'Kas', 'petty_cash' => 'Petty cash', 'e_wallet' => 'E-wallet'] as $value => $label)<option value="{{ $value }}" @selected($financialAccount->account_type === $value)>{{ $label }}</option>@endforeach</select>
                                        <select required name="account_id" class="select select-bordered select-sm">@foreach ($liquidityAccounts as $account)<option value="{{ $account->id }}" @selected($financialAccount->account_id === $account->id)>{{ $account->code }} — {{ $account->name }}</option>@endforeach</select>
                                        <input required type="date" name="opening_date" value="{{ $financialAccount->opening_date?->toDateString() }}" class="input input-bordered input-sm"><input required name="currency_code" value="{{ $financialAccount->currency_code }}" maxlength="3" class="input input-bordered input-sm">
                                        <input name="custodian_reference" value="{{ $financialAccount->custodian_reference }}" placeholder="Referensi kustodian" class="input input-bordered input-sm sm:col-span-2">
                                        <input name="bank_name" value="{{ $financialAccount->bankDetail?->bank_name }}" placeholder="Nama bank" class="input input-bordered input-sm"><input name="account_number_masked" value="{{ $financialAccount->bankDetail?->account_number_masked }}" placeholder="Nomor tersamarkan" class="input input-bordered input-sm">
                                        <input name="branch_name" value="{{ $financialAccount->bankDetail?->branch_name }}" placeholder="Cabang" class="input input-bordered input-sm"><input name="account_number_protected_ref" value="{{ $financialAccount->bankDetail?->account_number_protected_ref }}" placeholder="Referensi terlindungi" class="input input-bordered input-sm">
                                        <input name="cash_location" value="{{ $financialAccount->cashDetail?->cash_location }}" placeholder="Lokasi kas" class="input input-bordered input-sm"><select name="cash_count_frequency" class="select select-bordered select-sm"><option value="">Frekuensi kas</option>@foreach (['daily' => 'Harian', 'weekly' => 'Mingguan', 'monthly' => 'Bulanan', 'ad_hoc' => 'Sesuai kebutuhan'] as $value => $label)<option value="{{ $value }}" @selected($financialAccount->cashDetail?->cash_count_frequency === $value)>{{ $label }}</option>@endforeach</select>
                                        <input name="petty_cash_limit" value="{{ $financialAccount->cashDetail?->petty_cash_limit }}" placeholder="Batas petty cash" class="input input-bordered input-sm"><button class="btn btn-primary btn-sm">Simpan perubahan</button>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @empty
                        <div class="p-8 text-center text-sm text-base-content/60">Belum ada Rekening / Kas yang dikonfigurasi untuk entitas ini.</div>
                    @endforelse
                </div>
            </section>
        </section>
    @endif
@endsection
