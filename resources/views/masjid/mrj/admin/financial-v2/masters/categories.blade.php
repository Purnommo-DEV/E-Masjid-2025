@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Kategori Transaksi')

@section('content')
    @include('masjid.mrj.admin.financial-v2.masters._header', [
        'title' => 'Kategori Transaksi',
        'subtitle' => 'Kategori memudahkan pengurus memilih jenis penerimaan atau pengeluaran. Pemetaan pencatatan tetap bekerja di belakang melalui aturan yang sudah disetujui.',
    ])

    @if ($entity)
        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.25fr)]">
            <form method="post" action="{{ route('financial-v2.masters.categories.store') }}" data-financial-ajax class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                @csrf <input type="hidden" name="entity" value="{{ $entity->id }}">
                <h2 class="text-lg font-bold">Tambah Kategori</h2>
                <p class="mt-1 text-sm text-base-content/65">Pilih jenis transaksi dan aturan default bila memang sudah tersedia pada konfigurasi V2. Pengurus tidak perlu memilih debit, kredit, atau ledger.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text text-sm">Nama Kategori</span><input required name="name" maxlength="160" class="input input-bordered" placeholder="Nama kategori"></label>
                    <label class="form-control"><span class="label-text text-sm">Kode Kategori</span><input required name="code" maxlength="40" class="input input-bordered" placeholder="KAT-001"></label>
                    <label class="form-control"><span class="label-text text-sm">Jenis transaksi <span class="text-base-content/45">(opsional)</span></span><select name="transaction_type_id" class="select select-bordered"><option value="">Semua jenis</option>@foreach ($transactionTypes as $type)<option value="{{ $type->id }}">{{ $type->name }}</option>@endforeach</select></label>
                    <label class="form-control"><span class="label-text text-sm">Aturan posting default <span class="text-base-content/45">(opsional)</span></span><select name="default_posting_rule_id" class="select select-bordered"><option value="">Tidak ditetapkan</option>@foreach ($postingRules as $rule)<option value="{{ $rule->id }}">{{ $rule->name }}</option>@endforeach</select></label>
                    <label class="form-control"><span class="label-text text-sm">Status</span><select required name="status" class="select select-bordered"><option value="active">Aktif</option><option value="inactive">Tidak aktif</option></select></label>
                    <span></span><label class="form-control"><span class="label-text text-sm">Berlaku mulai <span class="text-base-content/45">(opsional)</span></span><input type="date" name="valid_from" class="input input-bordered"></label><label class="form-control"><span class="label-text text-sm">Berlaku sampai <span class="text-base-content/45">(opsional)</span></span><input type="date" name="valid_to" class="input input-bordered"></label>
                </div>
                <button class="btn btn-primary mt-5">Simpan Kategori</button>
            </form>

            <section class="rounded-2xl border border-base-300 bg-base-100 shadow-sm"><div class="border-b border-base-300 p-5"><h2 class="text-lg font-bold">Daftar Kategori</h2><p class="mt-1 text-sm text-base-content/65">Kategori yang telah dipakai tidak dapat dihapus; cukup nonaktifkan untuk transaksi berikutnya.</p></div><div class="divide-y divide-base-200">
                @forelse ($categories as $category)
                    <article class="p-5"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold">{{ $category->name }}</p><p class="text-sm text-base-content/60">{{ $category->code }}@if($category->transaction_type_id) · Jenis transaksi terikat @endif</p></div><span class="badge {{ $category->status === 'active' ? 'badge-success' : 'badge-ghost' }}">{{ ucfirst($category->status) }}</span></div>
                        @if ($category->status === 'active')<form method="post" action="{{ route('financial-v2.masters.categories.deactivate', $category) }}" data-financial-ajax class="mt-4">@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-sm">Nonaktifkan</button></form>@endif
                        <details class="mt-4 rounded-xl bg-base-200 p-4"><summary class="cursor-pointer text-sm font-semibold">Ubah kategori</summary><form method="post" action="{{ route('financial-v2.masters.categories.update', $category) }}" data-financial-ajax class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')<input type="hidden" name="entity" value="{{ $entity->id }}"><input required name="name" value="{{ $category->name }}" class="input input-bordered input-sm"><input required name="code" value="{{ $category->code }}" class="input input-bordered input-sm"><select name="transaction_type_id" class="select select-bordered select-sm"><option value="">Semua jenis</option>@foreach ($transactionTypes as $type)<option value="{{ $type->id }}" @selected($category->transaction_type_id === $type->id)>{{ $type->name }}</option>@endforeach</select><select name="default_posting_rule_id" class="select select-bordered select-sm"><option value="">Tidak ditetapkan</option>@foreach ($postingRules as $rule)<option value="{{ $rule->id }}" @selected($category->default_posting_rule_id === $rule->id)>{{ $rule->name }}</option>@endforeach</select><select required name="status" class="select select-bordered select-sm"><option value="active" @selected($category->status === 'active')>Aktif</option><option value="inactive" @selected($category->status === 'inactive')>Tidak aktif</option></select><span></span><input type="date" name="valid_from" value="{{ optional($category->valid_from)->toDateString() }}" class="input input-bordered input-sm"><input type="date" name="valid_to" value="{{ optional($category->valid_to)->toDateString() }}" class="input input-bordered input-sm"><button class="btn btn-primary btn-sm">Simpan perubahan</button></form></details>
                    </article>
                @empty <div class="p-8 text-center text-sm text-base-content/60">Belum ada Kategori transaksi yang dikonfigurasi.</div>
                @endforelse
            </div></section>
        </section>
    @endif
@endsection
