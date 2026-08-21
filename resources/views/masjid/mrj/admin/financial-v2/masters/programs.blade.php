@extends('masjid.mrj.admin.financial-v2.layout')

@section('title', 'Master Program')

@section('content')
    @include('masjid.mrj.admin.financial-v2.masters._header', [
        'title' => 'Program',
        'subtitle' => 'Program adalah tujuan atau kegiatan penggunaan Dana, bukan rekening, Dana, atau saldo kas. Kelayakan Dana terhadap Program ditentukan oleh Aturan Dana yang dikonfigurasi, bukan hubungan permanen yang dihardcode.',
    ])

    @if ($entity)
        <section class="grid gap-6 xl:grid-cols-[minmax(0,0.95fr)_minmax(0,1.25fr)]">
            <form method="post" action="{{ route('financial-v2.masters.programs.store') }}" data-financial-ajax class="rounded-2xl border border-base-300 bg-base-100 p-5 shadow-sm">
                @csrf <input type="hidden" name="entity" value="{{ $entity->id }}">
                <h2 class="text-lg font-bold">Tambah Program</h2>
                <p class="mt-1 text-sm text-base-content/65">Program baru disimpan sebagai draft dan tidak membuat alokasi, transaksi, atau saldo.</p>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    <label class="form-control"><span class="label-text text-sm">Nama Program</span><input required name="name" maxlength="160" class="input input-bordered" placeholder="Nama kegiatan atau tujuan"></label>
                    <label class="form-control"><span class="label-text text-sm">Kode Program</span><input required name="code" maxlength="40" class="input input-bordered" placeholder="PRG-001"></label>
                    <label class="form-control"><span class="label-text text-sm">Tanggal mulai <span class="text-base-content/45">(opsional)</span></span><input type="date" name="start_date" class="input input-bordered"></label>
                    <label class="form-control"><span class="label-text text-sm">Tanggal selesai <span class="text-base-content/45">(opsional)</span></span><input type="date" name="end_date" class="input input-bordered"></label>
                    <label class="form-control"><span class="label-text text-sm">Cost center <span class="text-base-content/45">(opsional)</span></span><select name="cost_center_id" class="select select-bordered"><option value="">Tidak ditetapkan</option>@foreach ($costCenters as $costCenter)<option value="{{ $costCenter->id }}">{{ $costCenter->code }} — {{ $costCenter->name }}</option>@endforeach</select></label>
                    <label class="form-control"><span class="label-text text-sm">Referensi penanggung jawab <span class="text-base-content/45">(opsional)</span></span><input name="program_owner_reference" maxlength="100" class="input input-bordered" placeholder="Referensi internal"></label>
                </div>
                <button class="btn btn-primary mt-5">Simpan draft Program</button>
            </form>

            <section class="rounded-2xl border border-base-300 bg-base-100 shadow-sm">
                <div class="border-b border-base-300 p-5"><h2 class="text-lg font-bold">Daftar Program</h2><p class="mt-1 text-sm text-base-content/65">Program aktif dapat dipakai di transaksi baru jika lifecycle dan Aturan Dana mengizinkan.</p></div>
                <div class="divide-y divide-base-200">
                    @forelse ($programs as $program)
                        <article class="p-5">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="font-semibold">{{ $program->name }}</p><p class="text-sm text-base-content/60">{{ $program->code }}@if($program->costCenter) · {{ $program->costCenter->name }}@endif</p></div><span class="badge {{ $program->status === 'active' ? 'badge-success' : 'badge-ghost' }}">{{ ucfirst($program->status) }}</span></div>
                            <p class="mt-2 text-sm text-base-content/65">{{ $program->start_date?->translatedFormat('d M Y') ?? 'Mulai belum ditetapkan' }} — {{ $program->end_date?->translatedFormat('d M Y') ?? 'Berjalan sampai ditutup' }}</p>
                            <div class="mt-4 flex flex-wrap gap-2">
                                @if ($program->status !== 'active' && $program->status !== 'closed')<form method="post" action="{{ route('financial-v2.masters.programs.activate', $program) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-success btn-sm">Aktifkan</button></form>@endif
                                @if ($program->status === 'active')<form method="post" action="{{ route('financial-v2.masters.programs.deactivate', $program) }}" data-financial-ajax>@csrf<input type="hidden" name="entity" value="{{ $entity->id }}"><button class="btn btn-outline btn-sm">Nonaktifkan</button></form>@endif
                            </div>
                            @if ($program->status !== 'active')
                                <details class="mt-4 rounded-xl bg-base-200 p-4"><summary class="cursor-pointer text-sm font-semibold">Ubah draft</summary>
                                    <form method="post" action="{{ route('financial-v2.masters.programs.update', $program) }}" data-financial-ajax class="mt-4 grid gap-3 sm:grid-cols-2">@csrf @method('PUT')<input type="hidden" name="entity" value="{{ $entity->id }}"><input required name="name" value="{{ $program->name }}" class="input input-bordered input-sm"><input required name="code" value="{{ $program->code }}" class="input input-bordered input-sm"><input type="date" name="start_date" value="{{ $program->start_date?->toDateString() }}" class="input input-bordered input-sm"><input type="date" name="end_date" value="{{ $program->end_date?->toDateString() }}" class="input input-bordered input-sm"><select name="cost_center_id" class="select select-bordered select-sm"><option value="">Tidak ditetapkan</option>@foreach ($costCenters as $costCenter)<option value="{{ $costCenter->id }}" @selected($program->cost_center_id === $costCenter->id)>{{ $costCenter->name }}</option>@endforeach</select><input name="program_owner_reference" value="{{ $program->program_owner_reference }}" placeholder="Referensi penanggung jawab" class="input input-bordered input-sm"><button class="btn btn-primary btn-sm">Simpan perubahan</button></form>
                                </details>
                            @endif
                        </article>
                    @empty <div class="p-8 text-center text-sm text-base-content/60">Belum ada Program yang dikonfigurasi.</div>
                    @endforelse
                </div>
            </section>
        </section>
    @endif
@endsection
