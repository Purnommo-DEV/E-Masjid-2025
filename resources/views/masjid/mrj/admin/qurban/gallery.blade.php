{{-- resources/views/masjid/mrj/admin/qurban/gallery.blade.php --}}

@extends('masjid.master')
@section('title', 'Galeri Qurban')

@section('content')

@push('style')
    <style>
        /* --- Reuse reference card styles --- */
        .card-wrapper {
            max-width: 1400px;
            margin: 1.25rem auto;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(2,6,23,0.06);
            border: 1px solid rgba(15,23,42,0.04);
            background: white;
        }
        .card-header {
            padding: 1.25rem 1.5rem;
            color: #fff;
            background: linear-gradient(90deg, #059669 0%, #10b981 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        .card-header .title { margin: 0; font-size: 1.125rem; font-weight: 700; }
        .card-header .subtitle { margin: 0; opacity: .95; font-size: .95rem; }

        .card-body { padding: 1.25rem 1.5rem; background: white; }

        .header-action {
            background: rgba(255,255,255,0.12);
            color: #fff;
            padding: 0.5rem 0.9rem;
            border-radius: 999px;
            display: inline-flex;
            gap: .5rem;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.08);
            box-shadow: 0 6px 14px rgba(4,120,87,0.06);
            transition: transform .12s ease, background .12s;
        }
        .header-action:hover { transform: translateY(-2px); background: rgba(255,255,255,0.18); }

        /* table tweaks (untuk filter paket) */
        .filter-select {
            max-width: 300px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        /* preview */
        .preview-card {
            background: #ffffff;
            border: 1px solid #eee;
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
        }
        .preview-title { font-weight: 600; font-size: 14px; margin-bottom: 8px; }
        .preview-image-box { position: relative; border-radius: 10px; overflow: hidden; display: inline-block; }
        .preview-image-box img { width: 100%; max-width: 220px; height: 80px; object-fit: cover; border-radius: 8px; }

        /* Gallery Grid */
        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        .gallery-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            position: relative;
            border: 1px solid #f0fdf4;
        }
        .gallery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.12);
        }
        .gallery-card .image-wrapper {
            position: relative;
            height: 200px;
            overflow: hidden;
            background: #f3f4f6;
        }
        .gallery-card .image-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .gallery-card:hover .image-wrapper img {
            transform: scale(1.05);
        }
        .gallery-card .overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            opacity: 0;
            transition: opacity 0.2s ease;
        }
        .gallery-card:hover .overlay {
            opacity: 1;
        }
        .gallery-card .overlay button {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: white;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease;
            font-size: 18px;
        }
        .gallery-card .overlay button:hover {
            transform: scale(1.1);
        }
        .gallery-card .card-info {
            padding: 12px;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
        }
        .cover-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #10b981;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            z-index: 5;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .paket-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            z-index: 5;
            backdrop-filter: blur(4px);
        }
        .sort-handle {
            cursor: grab;
            color: #9ca3af;
            transition: color 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-size: 12px;
        }
        .sort-handle:active {
            cursor: grabbing;
        }
        .sort-handle:hover {
            color: #059669;
        }

        /* dialog/modal styles */
        dialog.modal::backdrop {
            background: rgba(15,23,42,0.55);
            backdrop-filter: blur(4px) saturate(1.02);
        }
        dialog.modal { border: none; padding: 0; z-index: 1050; }
        .modal-panel { 
            border-radius: 12px; 
            box-shadow: 0 18px 40px rgba(2,6,23,0.12); 
            overflow: hidden; 
            background: white;
            position: relative;
        }

        /* validation */
        .is-invalid { border-color: #dc3545 !important; background: #fef2f2 !important; }
        .invalid-feedback { display: block; color: #dc3545; font-size: .75rem; margin-top: 0.25rem; }

        /* upload area */
        .upload-area { border-radius: 8px; transition: all .12s; }
        .upload-area.dragover { border-color: #059669 !important; background: #f0fdf4; }

        .btn-circle-ico { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; transition: transform .12s ease; }
        .btn-circle-ico:hover { transform: translateY(-2px); }

        /* SweetAlert z-index fix */
        .swal2-container { z-index: 99999 !important; }
        .swal2-backdrop { z-index: 99998 !important; }

        /* Loader Style */
        .btn-loader {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .spinner-border {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #ffffff;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .modal-loading-overlay {
            position: absolute;
            inset: 0;
            background: rgba(255, 255, 255, 0.85);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 60;
            backdrop-filter: blur(3px);
            border-radius: 12px;
        }
        .modal-loading-overlay.active { display: flex; }
        .spinner {
            width: 40px;
            height: 40px;
            border: 5px solid #e5e7eb;
            border-top: 5px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .delete-preview-btn {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 20px;
            height: 20px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            border: none;
            z-index: 10;
        }
        .delete-preview-btn:hover { background: #dc2626; }

        @media (max-width: 640px) {
            .card-header { padding: .9rem 1rem; }
            .card-body { padding: 1rem; }
            .gallery-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 1rem; }
        }
    </style>
@endpush

    <div class="card-wrapper">
        <div class="card-header">
            <div>
                <h3 class="title">🖼️ Galeri Qurban</h3>
                <p class="subtitle">Kelola foto-foto hewan qurban untuk ditampilkan di halaman depan</p>
            </div>
            <button type="button" class="header-action" onclick="openGalleryModal()">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden>
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                <span class="text-sm font-semibold">Tambah Galeri</span>
            </button>
        </div>

        <div class="card-body">
            <!-- Filter by Paket -->
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <label class="text-sm font-medium text-slate-700">Filter Paket:</label>
                    <select id="filterPaket" class="filter-select">
                        <option value="">Semua Paket</option>
                        @foreach($qurbans as $qurban)
                        <option value="{{ $qurban->id }}">{{ $qurban->judul }} ({{ $qurban->jenis_label }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="text-sm text-slate-500">
                    <span id="totalCount">{{ $galleries->count() }}</span> foto
                </div>
            </div>

            <!-- Gallery Grid -->
            <div id="galleryGrid" class="gallery-grid sortable-container">
                @forelse($galleries as $gallery)
                <div class="gallery-card" data-id="{{ $gallery->id }}" data-paket="{{ $gallery->qurban_id }}">
                    <div class="image-wrapper">
                        @if($gallery->is_cover)
                        <div class="cover-badge">
                            ⭐ Cover
                        </div>
                        @endif
                        <div class="paket-badge">
                            {{ $gallery->qurban->judul ?? 'Paket Qurban' }}
                        </div>
                        <img src="{{ $gallery->image_url }}" alt="{{ $gallery->title ?? 'Gambar Qurban' }}" loading="lazy">
                        <div class="overlay">
                            <button type="button" onclick="viewImage('{{ $gallery->image_url }}')" title="Lihat">
                                👁️
                            </button>
                            <button type="button" onclick="editGallery({{ $gallery->id }})" title="Edit">
                                ✏️
                            </button>
                            <button type="button" onclick="deleteGallery({{ $gallery->id }})" title="Hapus">
                                🗑️
                            </button>
                        </div>
                    </div>
                    <div class="card-info">
                        <div class="sort-handle">
                            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/>
                            </svg>
                            <span class="text-xs">Drag</span>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-slate-800 truncate max-w-[150px]">
                                {{ $gallery->title ?? 'Tanpa judul' }}
                            </div>
                            <div class="text-xs text-slate-400">
                                {{ $gallery->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-span-full text-center py-12 bg-slate-50 rounded-xl">
                    <div class="text-5xl mb-3">📷</div>
                    <p class="text-slate-500">Belum ada gambar galeri qurban</p>
                    <p class="text-sm text-slate-400 mt-1">Klik tombol "Tambah Galeri" untuk mulai menambahkan foto</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH / EDIT GALERI -->
    <dialog id="galleryModal" class="modal" aria-labelledby="galleryModalTitle" role="dialog" aria-modal="true">
        <div class="modal-panel w-11/12 max-w-3xl max-h-[90vh] flex flex-col relative">
            <form id="galleryForm" enctype="multipart/form-data" class="flex-1 overflow-y-auto p-4 text-sm bg-white rounded-t-2xl rounded-b-none shadow-xl border border-emerald-50" method="POST" novalidate>
                @csrf
                <input type="hidden" id="method" value="POST">
                <input type="hidden" id="galleryId" name="gallery_id" value="">

                <!-- Header -->
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 id="galleryModalTitle" class="text-lg font-extrabold text-emerald-800">📸 Form Galeri Qurban</h3>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="closeGalleryModalBtn" class="inline-flex items-center justify-center w-8 h-8 rounded-md text-gray-600 hover:bg-gray-100 text-sm" aria-label="Tutup">
                            ✕
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="grid grid-cols-1 gap-4 mt-3">
                    <!-- Pilih Paket Qurban -->
                    <div>
                        <label class="block text-xs font-medium text-emerald-700 mb-1">Paket Qurban <span class="text-red-500">*</span></label>
                        <select id="qurban_id" name="qurban_id" class="form-select w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" required>
                            <option value="">-- Pilih Paket --</option>
                            @foreach($qurbans as $qurban)
                            <option value="{{ $qurban->id }}">{{ $qurban->judul }} ({{ $qurban->jenis_label }})</option>
                            @endforeach
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Upload Gambar -->
                    <div>
                        <label class="block text-xs font-medium text-emerald-700 mb-1">Gambar <span class="text-red-500">*</span></label>
                        <label for="uploadImage" class="upload-area cursor-pointer block rounded-lg border-2 border-dashed border-emerald-200 p-3 text-center hover:bg-emerald-50 transition text-sm">
                            <div class="flex items-center justify-center gap-2">
                                <svg class="w-5 h-5 text-emerald-600" viewBox="0 0 24 24" fill="none">
                                    <path d="M3 15a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v2a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-2z" stroke="currentColor" stroke-width="1.25"/>
                                    <path d="M8 11l4-4 4 4" stroke="currentColor" stroke-width="1.25"/>
                                </svg>
                                <div class="text-sm text-emerald-700 font-medium">Klik atau seret foto ke sini</div>
                            </div>
                            <div class="text-xs text-gray-400 mt-1">
                                JPG / PNG / WebP • Maksimal 2MB • Rasio ideal 16:9
                            </div>
                        </label>
                        <input type="file" name="image" id="uploadImage" class="hidden" accept="image/*">
                        <div class="invalid-feedback" data-error="image"></div>

                        <!-- Preview -->
                        <div class="mt-3 preview-card">
                            <div class="preview-title text-sm">🖼️ Preview Gambar</div>
                            <div id="previewImageContainer" class="text-sm text-gray-500">Belum ada gambar</div>
                        </div>
                    </div>

                    <!-- Judul -->
                    <div>
                        <label class="block text-xs font-medium text-emerald-700 mb-1">Judul (Opsional)</label>
                        <input type="text" name="title" id="imageTitle" class="w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" placeholder="Contoh: Sapi Qurban 2025">
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Deskripsi -->
                    <div>
                        <label class="block text-xs font-medium text-emerald-700 mb-1">Deskripsi (Opsional)</label>
                        <textarea name="description" id="imageDescription" class="w-full rounded-md border border-emerald-100 px-2 py-1 text-sm shadow-sm" rows="2" placeholder="Deskripsi singkat tentang hewan ini"></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Jadikan Cover -->
                    <div class="flex items-center gap-2">
                        <input type="checkbox" name="is_cover" id="isCover" class="rounded text-emerald-600 focus:ring-emerald-400">
                        <label for="isCover" class="text-sm text-gray-700">Jadikan sebagai gambar cover</label>
                    </div>
                </div>
            </form>

            <!-- Sticky Footer -->
            <div class="sticky bottom-0 z-30 bg-white/95 backdrop-blur-sm border-t border-emerald-50 px-4 py-3 flex items-center justify-end gap-3 rounded-b-2xl">
                <button type="button" id="cancelGalleryBtn" class="px-3 py-1 rounded-md border border-gray-200 hover:bg-gray-50 text-sm">
                    Batal
                </button>
                <button type="submit" id="submitBtn" form="galleryForm" class="inline-flex items-center gap-2 px-4 py-1 rounded-md bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-sm shadow">
                    Simpan
                </button>
            </div>

            <!-- Loader overlay -->
            <div id="modalLoader" class="modal-loading-overlay">
                <div class="text-center">
                    <div class="spinner mx-auto mb-3"></div>
                    <p class="text-emerald-700 font-medium">Menyimpan data...</p>
                </div>
            </div>
        </div>
    </dialog>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <script>
        let isSubmitting = false;
        const modal = document.getElementById('galleryModal');
        const form = $('#galleryForm');
        let uploadedFile = null;

        // Helper SweetAlert
        function showAlertOnTop(icon, title, text, callback = null) {
            const wasOpen = modal && modal.open;
            if (wasOpen) modal.close();
            
            Swal.fire({
                icon: icon,
                title: title,
                text: text,
                confirmButtonText: 'OK',
                allowOutsideClick: false,
                didOpen: () => {
                    const swalContainer = document.querySelector('.swal2-container');
                    if (swalContainer) swalContainer.style.zIndex = '99999';
                }
            }).then((result) => {
                if (wasOpen && modal) modal.showModal();
                if (callback) callback(result);
            });
        }

        // Dialog helpers
        function showDialog(d) {
            try { if (typeof d.showModal === 'function') d.showModal(); else d.classList.add('modal-open'); }
            catch(e){ d.classList.add('modal-open'); }
        }
        function closeDialog(d) {
            try { if (typeof d.close === 'function') d.close(); else d.classList.remove('modal-open'); }
            catch(e){ d.classList.remove('modal-open'); }
        }

        // Reset errors
        function resetErrors() {
            $('.invalid-feedback').text('');
            $('.form-select, input, select, textarea').removeClass('is-invalid');
        }

        // Preview image
        document.getElementById('uploadImage')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            if (file.size > 2 * 1024 * 1024) {
                showAlertOnTop('error', 'File terlalu besar!', 'Maksimal 2MB');
                this.value = '';
                return;
            }
            if (!file.type.startsWith('image/')) {
                showAlertOnTop('error', 'File tidak valid!', 'Hanya file gambar diperbolehkan.');
                this.value = '';
                return;
            }
            
            uploadedFile = file;
            const reader = new FileReader();
            reader.onload = ev => {
                const preview = $('#previewImageContainer');
                preview.html(`
                    <div class="preview-image-box">
                        <img src="${ev.target.result}" alt="preview" style="width:100%; max-width:200px; height:auto; border-radius:8px;">
                        <button type="button" class="delete-preview-btn" onclick="removePreview()">✕</button>
                    </div>
                `);
            };
            reader.readAsDataURL(file);
        });

        function removePreview() {
            $('#uploadImage').val('');
            uploadedFile = null;
            $('#previewImageContainer').html('<div class="text-muted small">Belum ada gambar.</div>');
        }

        $(function() {
            // Filter paket
            $('#filterPaket').on('change', function() {
                const paketId = $(this).val();
                if (paketId) {
                    $('.gallery-card').hide();
                    $(`.gallery-card[data-paket="${paketId}"]`).show();
                } else {
                    $('.gallery-card').show();
                }
            });

            // Sortable drag & drop
            new Sortable(document.getElementById('galleryGrid'), {
                animation: 150,
                handle: '.sort-handle',
                ghostClass: 'opacity-50 bg-emerald-100',
                onEnd: function() {
                    const order = [];
                    $('#galleryGrid .gallery-card').each(function() {
                        order.push($(this).data('id'));
                    });
                    
                    $.ajax({
                        url: '{{ route("admin.qurban.galeri.reorder") }}',
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            order: order
                        },
                        success: function(response) {
                            if (response.success) console.log('Reorder success');
                        },
                        error: function() {
                            Swal.fire('Error', 'Gagal mengubah urutan', 'error');
                        }
                    });
                }
            });

            // Modal controls
            $('#closeGalleryModalBtn, #cancelGalleryBtn').on('click', () => closeDialog(modal));
            if (modal) modal.addEventListener('cancel', (e) => { e.preventDefault(); closeDialog(modal); });
        });

        // Open modal for add
        window.openGalleryModal = function() {
            resetErrors();
            form[0].reset();
            $('#method').val('POST');
            $('#galleryId').val('');
            $('#galleryModalTitle').text('Tambah Galeri Qurban');
            $('#qurban_id').prop('required', true);
            removePreview();
            uploadedFile = null;
            showDialog(modal);
            setTimeout(() => $('#qurban_id').focus(), 120);
        };

        // Edit gallery
        window.editGallery = function(id) {
            resetErrors();
            removePreview();
            
            $.get(`{{ url('admin/qurban/galeri') }}/${id}/edit`, function(data) {
                form[0].reset();
                $('#method').val('PUT');
                $('#galleryId').val(data.id);
                $('#galleryModalTitle').text('Edit Galeri Qurban');
                $('#qurban_id').val(data.qurban_id).prop('required', false);
                $('#imageTitle').val(data.title);
                $('#imageDescription').val(data.description);
                $('#isCover').prop('checked', data.is_cover);
                
                if (data.image_url) {
                    $('#previewImageContainer').html(`
                        <div class="preview-image-box">
                            <img src="${data.image_url}" alt="preview" style="width:100%; max-width:200px; height:auto; border-radius:8px;">
                            <button type="button" class="delete-preview-btn" onclick="markImageDeleted()">✕</button>
                            <input type="hidden" name="delete_image" id="deleteImageFlag" value="0">
                        </div>
                    `);
                }
                
                showDialog(modal);
            }).fail(() => {
                showAlertOnTop('error', 'Error', 'Gagal memuat data galeri.');
            });
        };

        window.markImageDeleted = function() {
            $('#deleteImageFlag').val('1');
            $('#previewImageContainer').html('<div class="text-muted small">Gambar akan dihapus saat disimpan.</div>');
        };

        // Delete gallery
        window.deleteGallery = function(id) {
            const wasOpen = modal && modal.open;
            if (wasOpen) modal.close();
            
            Swal.fire({
                title: 'Yakin?',
                text: 'Gambar akan dihapus permanen!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url('admin/qurban/galeri') }}/${id}`,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: (res) => {
                            location.reload();
                        },
                        error: (xhr) => {
                            showAlertOnTop('error', 'Error', xhr.responseJSON?.message || 'Gagal menghapus galeri.');
                        }
                    });
                } else if (wasOpen && modal) {
                    modal.showModal();
                }
            });
        };

        window.viewImage = function(url) {
            Swal.fire({
                imageUrl: url,
                imageAlt: 'Gambar Qurban',
                imageWidth: '80%',
                showCloseButton: true,
                showConfirmButton: false
            });
        };

        // Set as cover
        window.setCover = function(galleryId, paketId) {
            Swal.fire({
                title: 'Jadikan Cover?',
                text: 'Gambar ini akan menjadi cover utama paket qurban',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981'
            }).then(result => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `{{ url("admin/qurban/galeri") }}/${paketId}/${galleryId}/cover`,
                        method: 'POST',
                        data: { _token: '{{ csrf_token() }}' },
                        success: () => location.reload(),
                        error: () => Swal.fire('Error', 'Gagal mengubah cover', 'error')
                    });
                }
            });
        };

        // Submit form
        form.on('submit', function(e) {
            e.preventDefault();
            if (isSubmitting) return;
            resetErrors();
            
            const submitBtn = $('#submitBtn');
            const originalText = submitBtn.html();
            
            isSubmitting = true;
            submitBtn.prop('disabled', true);
            submitBtn.html('<span class="btn-loader"><span class="spinner-border"></span> Menyimpan...</span>');
            $('#modalLoader').addClass('active');
            
            let formData = new FormData(this);
            const method = $('#method').val();
            if (method === 'PUT') {
                formData.append('_method', 'PUT');
                const deleteFlag = $('#deleteImageFlag').val();
                if (deleteFlag === '1') formData.append('delete_image', '1');
            }
            
            // Jika ada file baru, replace yang lama
            if (uploadedFile) formData.set('image', uploadedFile);
            
            const url = method === 'PUT' 
                ? `{{ url('admin/qurban/galeri') }}/${$('#galleryId').val()}`
                : '{{ route("admin.qurban.galeri.store") }}';
            
            $.ajax({
                url: url,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (res) => {
                    closeDialog(modal);
                    Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
                },
                error: (xhr) => {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON?.errors;
                        if (errors) {
                            $('.invalid-feedback').text('');
                            Object.keys(errors).forEach(field => {
                                $(`[name="${field}"]`).addClass('is-invalid');
                                $(`[name="${field}"]`).siblings('.invalid-feedback').text(errors[field][0]);
                                if (field === 'image') $(`[data-error="image"]`).text(errors[field][0]);
                            });
                        }
                    } else {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Terjadi kesalahan', 'error');
                    }
                },
                complete: () => {
                    isSubmitting = false;
                    submitBtn.prop('disabled', false);
                    submitBtn.html(originalText);
                    $('#modalLoader').removeClass('active');
                }
            });
        });
    </script>
@endpush