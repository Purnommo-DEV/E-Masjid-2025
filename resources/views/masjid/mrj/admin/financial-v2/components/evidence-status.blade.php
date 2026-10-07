@if ($evidenceStatus['required'] !== [])
    @php
        $hasFiles = $evidenceStatus['files'] !== [];
        $suggestedType = $evidenceStatus['suggested_type'] ?? ($evidenceStatus['required'][0]['value'] ?? null);
        $suggestedLabel = $suggestedType ? ($evidenceLabels[$suggestedType] ?? str($suggestedType)->replace('_', ' ')->title()) : 'Bukti';
    @endphp
    <section class="mt-5 rounded-xl border p-4 {{ $evidenceStatus['complete'] ? 'border-success/30 bg-success/10' : 'border-warning/40 bg-warning/10' }}" data-evidence-status aria-live="polite">
        <h3 class="font-bold">{{ $evidenceStatus['complete'] ? '✅ Lengkap' : '⚠ Belum lengkap' }}</h3>
        <p class="mt-1 text-sm">
            @if ($evidenceStatus['complete'])
                Bukti wajib sudah memenuhi aturan pencatatan.
            @elseif ($hasFiles)
                File sudah ada, tetapi jenis atau jumlah buktinya belum sesuai.
            @else
                Bukti wajib belum dilampirkan.
            @endif
        </p>

        <div class="mt-3">
            <p class="text-xs font-bold uppercase tracking-wide text-base-content/60">Wajib</p>
            <ul class="mt-1 space-y-1 text-sm">
                @foreach ($evidenceStatus['required'] as $requirement)
                    <li>{{ $requirement['complete'] ? '✓' : '⚠' }} {{ $requirement['label'] }} ({{ $requirement['count'] }}/{{ $requirement['minimum'] }})</li>
                @endforeach
            </ul>
        </div>

        @if ($hasFiles)
            <div class="mt-3">
                <p class="text-xs font-bold uppercase tracking-wide text-base-content/60">Lampiran saat ini</p>
                <ul class="mt-1 space-y-2 text-sm">
                    @foreach ($evidenceStatus['files'] as $file)
                        <li><span class="font-medium">{{ $file['filename'] }}</span><br><span class="text-xs text-base-content/65">{{ $file['label'] }} · {{ $file['status_label'] }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $evidenceStatus['complete'] && $transaction->status === 'draft')
            <details class="mt-4 rounded-lg bg-base-100 p-3" data-required-evidence-upload>
                <summary class="cursor-pointer font-semibold text-emerald-800">+ Tambah {{ $suggestedLabel }}</summary>
                <form method="POST" action="{{ route('financial-v2.attachments.store', $transaction) }}" enctype="multipart/form-data" data-financial-ajax class="mt-3 grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_auto]">
                    @csrf
                    <input type="hidden" name="entity" value="{{ $entity->id }}">
                    <label class="form-control"><span class="label-text text-xs">File bukti</span><input required type="file" name="attachment" accept="image/jpeg,image/png,image/webp,application/pdf" class="file-input file-input-bordered w-full"></label>
                    <label class="form-control"><span class="label-text text-xs">Jenis bukti</span><select required name="evidence_type" class="select select-bordered w-full">
                        @foreach ($evidenceLabels as $value => $label)
                            <option value="{{ $value }}" @selected($value === $suggestedType)>{{ $label }}</option>
                        @endforeach
                    </select></label>
                    <button type="submit" class="btn btn-primary self-end">Tambah Bukti</button>
                </form>
                <p class="mt-2 text-xs text-base-content/55">Jenis bukti dipilih sesuai persyaratan. Ubah hanya jika isi dokumen memang merupakan jenis bukti lain.</p>
            </details>
        @endif
    </section>
@endif
