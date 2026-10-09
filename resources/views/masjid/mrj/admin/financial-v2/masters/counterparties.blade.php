@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Master Pihak Pembayaran')

@section('content')
    @include('masjid.mrj.admin.financial-v2.masters._header', [
        'title' => 'Pihak Pembayaran',
        'subtitle' => 'Kelola orang, pemasok, lembaga, bank, atau pihak lain yang benar-benar menerima pembayaran. Master Penerima Manfaat tetap dikelola terpisah melalui Penerima ZISWAF.',
    ])

    @if ($entity)
        <section class="grid gap-6 xl:grid-cols-[minmax(0,.85fr)_minmax(0,1.35fr)]">
            <form method="post" action="{{ route('financial-v2.masters.counterparties.store') }}" data-financial-ajax class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                @csrf
                <input type="hidden" name="entity" value="{{ $entity->id }}">
                <h2 class="text-lg font-bold">Tambah Pihak Pembayaran</h2>
                <p class="mt-1 text-sm text-base-content/65">Data aktif akan tersedia pada Payment dan Realisasi. Form ini tidak membuat transaksi atau penerima manfaat.</p>
                <div class="mt-5 grid gap-4">
                    <label class="form-control"><span class="label-text text-sm">Nama pihak</span><input required name="display_name" maxlength="240" class="input input-bordered" placeholder="Contoh: Toko Berkah"></label>
                    <label class="form-control"><span class="label-text text-sm">Kode unik</span><input required name="code" maxlength="40" class="input input-bordered" placeholder="SUP-TOKO-BERKAH"><span class="label-text-alt">Unik di dalam Entity; gunakan huruf, angka, titik, garis bawah, atau tanda hubung.</span></label>
                    <label class="form-control"><span class="label-text text-sm">Tipe pihak</span><select required name="party_type" class="select select-bordered"><option value="">Pilih tipe</option>@foreach($partyTypes as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><span class="label-text-alt">Tipe beneficiary tidak tersedia di halaman ini.</span></label>
                    <label class="form-control"><span class="label-text text-sm">Status awal</span><select required name="status" class="select select-bordered"><option value="active">Aktif</option><option value="inactive">Nonaktif</option></select><span class="label-text-alt">Hanya pihak aktif yang muncul pada Payment dan Realisasi.</span></label>
                </div>
                <button class="btn btn-primary mt-5">Simpan Pihak Pembayaran</button>
            </form>

            <section class="min-w-0 rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                <form method="get" class="grid gap-3 border-b border-base-300 p-5 sm:grid-cols-[minmax(0,1fr)_11rem_10rem_auto]">
                    <input type="hidden" name="entity" value="{{ $entity->id }}">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" class="input input-bordered input-sm" placeholder="Cari nama atau kode">
                    <select name="party_type" class="select select-bordered select-sm"><option value="">Semua tipe</option>@foreach($partyTypes as $value => $label)<option value="{{ $value }}" @selected(($filters['party_type'] ?? '') === $value)>{{ $label }}</option>@endforeach</select>
                    <select name="status" class="select select-bordered select-sm"><option value="">Semua status</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktif</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Nonaktif</option><option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Arsip</option></select>
                    <button class="btn btn-outline btn-sm">Cari</button>
                </form>
                <div class="divide-y divide-base-200">
                    @forelse ($counterparties as $counterparty)
                        <article class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold">{{ $counterparty->display_name }}</p><p class="text-sm text-base-content/60">{{ $counterparty->code }} · {{ $partyTypes[$counterparty->party_type] ?? ucfirst($counterparty->party_type) }}</p></div><span class="badge {{ $counterparty->status === 'active' ? 'badge-success' : 'badge-ghost' }}">{{ $counterparty->status === 'active' ? 'Aktif' : ucfirst($counterparty->status) }}</span></div>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($counterparty->status === 'active')
                                    <form method="post" action="{{ route('financial-v2.masters.counterparties.deactivate', $counterparty) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-sm">Nonaktifkan</button></form>
                                @elseif ($counterparty->status === 'inactive')
                                    <form method="post" action="{{ route('financial-v2.masters.counterparties.activate', $counterparty) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-success btn-sm">Aktifkan</button></form>
                                @endif
                            </div>
                            @if ($counterparty->status === 'archived')
                                <p class="mt-4 rounded-xl border border-base-300 bg-base-200 p-4 text-sm text-base-content/70">Record arsip bersifat final dan hanya ditampilkan untuk menjaga histori serta audit. Record ini tidak dapat diedit, diaktifkan, atau dinonaktifkan kembali.</p>
                            @else
                                <details class="mt-4 rounded-xl bg-base-200 p-4"><summary class="cursor-pointer text-sm font-semibold">Ubah data master</summary>
                                    <form method="post" action="{{ route('financial-v2.masters.counterparties.update', $counterparty) }}" data-financial-ajax class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')<input type="hidden" name="entity" value="{{ $entity->id }}"><input required name="display_name" maxlength="240" value="{{ $counterparty->display_name }}" class="input input-bordered input-sm"><input required name="code" maxlength="40" value="{{ $counterparty->code }}" class="input input-bordered input-sm"><select required name="party_type" class="select select-bordered select-sm">@foreach($partyTypes as $value => $label)<option value="{{ $value }}" @selected($counterparty->party_type === $value)>{{ $label }}</option>@endforeach</select><button class="btn btn-primary btn-sm">Simpan perubahan</button></form>
                                </details>
                            @endif
                        </article>
                    @empty
                        <div class="p-8 text-center text-sm text-base-content/60">Belum ada Pihak Pembayaran non-beneficiary yang cocok.</div>
                    @endforelse
                </div>
                @if ($counterparties->hasPages())<div class="border-t border-base-300 p-4">{{ $counterparties->links() }}</div>@endif
            </section>
        </section>
    @endif
@endsection
