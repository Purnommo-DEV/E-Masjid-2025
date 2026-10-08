@php($canManageConfiguration = auth()->user()?->hasRole('SuperAdmin') ?? false)
<div class="mt-3 hidden" data-configuration-missing-action
     data-can-manage="{{ $canManageConfiguration ? 'true' : 'false' }}"
     data-show-url="{{ route('financial-v2.configuration.inline.show') }}"
     data-store-url="{{ route('financial-v2.configuration.inline.store') }}">
    @if ($canManageConfiguration)
        <button type="button" class="btn btn-outline btn-sm" data-inline-configuration-open
                title="Membuat draft konfigurasi berdasarkan data transaksi yang sedang diisi.">
            + Buat Konfigurasi Penggunaan Dana
        </button>
        <p class="mt-1 text-xs opacity-70">Membuat draft konfigurasi berdasarkan data transaksi yang sedang diisi.</p>
        <dialog class="modal" data-inline-configuration-dialog>
            <div class="modal-box max-w-3xl">
                <form method="dialog"><button class="btn btn-circle btn-ghost btn-sm absolute right-3 top-3" aria-label="Tutup">✕</button></form>
                <h3 class="pr-10 text-lg font-bold">Konfigurasi Pencatatan Baru</h3>
                <p class="mt-1 text-sm text-base-content/60">Konteks transaksi dikunci dari form yang sedang diisi. Penyimpanan hanya membuat draft konfigurasi.</p>
                <div class="mt-4 hidden alert alert-error text-sm" data-inline-configuration-error></div>
                <dl class="mt-4 grid gap-3 rounded-xl bg-base-200/55 p-4 text-sm sm:grid-cols-2" data-inline-configuration-context></dl>
                <form class="mt-5 grid gap-4 sm:grid-cols-2" data-inline-configuration-form>
                    <label class="form-control sm:col-span-2"><span class="label-text font-medium">Aturan pencatatan kanonis</span><input type="hidden" name="posting_rule_version_id" data-inline-posting-rule-id><input class="input input-bordered w-full bg-base-200" readonly data-inline-posting-rule><span class="label-text-alt" data-inline-posting-rule-help></span></label>
                    <label class="form-control"><span class="label-text font-medium">Berlaku Mulai</span><input name="effective_from" type="date" class="input input-bordered w-full" required></label>
                    <label class="form-control"><span class="label-text font-medium">Berlaku Sampai</span><input name="effective_to" type="date" class="input input-bordered w-full"><span class="label-text-alt">Kosongkan jika belum ditentukan.</span></label>
                    <div class="form-control"><span class="label-text font-medium">Aturan bukti transaksi</span><div class="rounded-xl border border-base-300 bg-base-200 px-3 py-3 text-sm" data-inline-evidence-requirements>Diambil dari aturan pencatatan kanonis.</div></div>
                    <label class="form-control"><span class="label-text font-medium">Approval Requirement</span><input name="required_approval_steps" type="number" min="0" max="9" class="input input-bordered w-full"><span class="label-text-alt">Untuk Mutasi Bank; transaksi lain mengikuti requirement jenis transaksi.</span></label>
                    <label class="form-control sm:col-span-2"><span class="label-text font-medium">Policy / Reference</span><input name="policy_document_ref" maxlength="500" class="input input-bordered w-full" required></label>
                    <label class="form-control sm:col-span-2"><span class="label-text font-medium">Catatan</span><textarea name="notes" maxlength="2000" rows="3" class="textarea textarea-bordered w-full"></textarea></label>
                    <div class="sm:col-span-2 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end"><button type="button" class="btn btn-ghost" data-inline-configuration-cancel>Batal</button><button class="btn btn-primary" data-inline-configuration-save>Simpan Draft Konfigurasi</button></div>
                </form>
            </div>
            <form method="dialog" class="modal-backdrop"><button aria-label="Tutup">close</button></form>
        </dialog>
    @else
        <p class="text-xs font-medium">Konfigurasi belum tersedia. Hubungi administrator keuangan.</p>
    @endif
</div>
@if ($canManageConfiguration)
    <a class="mt-3 hidden btn btn-outline btn-sm" data-posting-rule-configuration-link href="{{ route('financial-v2.configuration.index', ['entity' => $entity?->id]) }}">Buat Aturan Pencatatan</a>
    <a class="mt-3 hidden btn btn-outline btn-sm" data-evidence-configuration-link href="{{ route('financial-v2.configuration.index', ['entity' => $entity?->id]) }}">Buat Aturan Bukti</a>
@endif
