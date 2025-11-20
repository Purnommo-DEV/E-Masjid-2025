@extends('masjid.master')
@section('title', 'Profil Masjid')
@section('content')

{{-- Leaflet CSS --}}
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<style>
    /* Card */
    .card {
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .card-header {
        background: linear-gradient(135deg, #1e40af, #3b82f6);
        border-radius: 12px 12px 0 0 !important;
        color: #fff !important;
    }

    /* Button */
    .btn-primary {
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        border: none;
        border-radius: 8px;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, #2563eb, #1e40af);
    }

    /* Input */
    .form-control,
    .form-select {
        border-radius: 8px;
    }

    /* Pengurus List */
    .pengurus-item {
        background: #f8f9fa;
        padding: 12px;
        margin: 8px 0;
        border-radius: 10px;
        transition: 0.2s;
    }

    .pengurus-item:hover {
        background: #e9ecef;
        transform: translateY(-1px);
    }

    /* Map */
    #map {
        height: 320px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .preview-img {
        max-width: 80px;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.2);
    }
</style>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="mb-0">Profil Masjid</h3>
    </div>

    <div class="card-body">
        <div class="row">
            {{-- FORM PROFIL --}}
            <div class="col-lg-8">

                <form id="profilForm" enctype="multipart/form-data" class="mb-4">
                    @csrf
                    <div class="row g-3">
                        
                        {{-- Nama Masjid --}}
                        <div class="col-md-6">
                            <label class="form-label">Nama Masjid</label>
                            <input type="text" name="nama" class="form-control" value="{{ $profil->nama }}" required>
                        </div>

                        {{-- Telepon --}}
                        <div class="col-md-6">
                            <label class="form-label">Telepon</label>
                            <input type="text" name="telepon" class="form-control" value="{{ $profil->telepon }}">
                        </div>

                        {{-- Alamat --}}
                        <div class="col-12">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" rows="2" required>{{ $profil->alamat }}</textarea>
                        </div>

                        {{-- Latitude --}}
                        <div class="col-md-6">
                            <label class="form-label">Latitude</label>
                            <input type="text" id="lat" name="latitude" class="form-control" 
                                   value="{{ $profil->latitude }}" required>
                        </div>

                        {{-- Longitude --}}
                        <div class="col-md-6">
                            <label class="form-label">Longitude</label>
                            <input type="text" id="lng" name="longitude" class="form-control" 
                                   value="{{ $profil->longitude }}" required>
                        </div>

                        {{-- Logo --}}
                        <div class="col-md-6">
                            <label class="form-label">Logo</label>
                            <input type="file" name="logo" class="form-control">

                            <div id="logoPreview" class="mt-2">
                                @if($profil->getFirstMedia('logo'))
                                    <img src="{{ $profil->getFirstMediaUrl('logo') }}" class="preview-img">
                                @endif
                            </div>
                        </div>

                        {{-- Struktur --}}
                        <div class="col-md-6">
                            <label class="form-label">Struktur</label>
                            <input type="file" name="struktur" class="form-control">

                            <div id="strukturPreview" class="mt-2">
                                @if($profil->getFirstMedia('struktur'))
                                    <img src="{{ $profil->getFirstMediaUrl('struktur') }}" width="150" class="img-thumbnail">
                                @endif
                            </div>
                        </div>

                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary">Simpan Profil</button>
                    </div>
                </form>

                <hr>

                {{-- PENGURUS --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Struktur Kepengurusan</h5>
                    <button class="btn btn-success btn-sm" onclick="openPengurusModal()">Tambah</button>
                </div>

                <div id="pengurusList">
                    @forelse($pengurus as $p)
                        <div class="pengurus-item d-flex align-items-center" data-id="{{ $p->id }}">
                            <div class="me-3">
                                @if($p->getFirstMedia('foto'))
                                    <img src="{{ $p->getFirstMediaUrl('foto') }}" width="45" class="rounded-circle">
                                @else
                                    <div class="bg-secondary rounded-circle text-white d-flex 
                                                align-items-center justify-content-center"
                                         style="width:45px;height:45px;font-size:14px;">
                                        {{ substr($p->nama, 0, 1) }}
                                    </div>
                                @endif
                            </div>

                            <div class="flex-grow-1">
                                <strong>{{ $p->nama }}</strong><br>
                                <small class="text-success">{{ $p->jabatan }}</small>
                            </div>

                            <div>
                                <button class="btn btn-warning btn-sm" onclick="editPengurus({{ $p->id }})">Edit</button>
                                <button class="btn btn-danger btn-sm" onclick="hapusPengurus({{ $p->id }})">Hapus</button>
                            </div>
                        </div>
                    @empty
                        <p class="text-muted text-center">Belum ada pengurus</p>
                    @endforelse
                </div>

            </div>

            {{-- MAP --}}
            <div class="col-lg-4">
                <h5>Lokasi di Peta</h5>
                <div id="map"></div>
                <small class="text-muted d-block mt-2">Klik peta untuk mengubah lokasi</small>
            </div>
        </div>
    </div>
</div>

<!-- resources/views/admin/profil/modal.blade.php -->
<div class="modal fade" id="pengurusModal">
    <form id="pengurusForm" enctype="multipart/form-data">
        @csrf
        <input type="hidden" id="pengurusId">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Pengurus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3">
                        <div id="fotoPreview" class="d-inline-block">
                            <div class="bg-light rounded-circle" style="width:80px;height:80px;"></div>
                        </div>
                        <input type="file" name="foto" id="fotoInput" class="form-control mt-2" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label>Nama</label>
                        <input type="text" name="nama" id="nama" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Jabatan</label>
                        <input type="text" name="jabatan" id="jabatan" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Keterangan</label>
                        <textarea name="keterangan" id="keterangan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </div>
        </div>
    </form>
</div>

{{-- Scripts --}}
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

@push('scripts')
<script>
    let map, marker;

    const lat = {{ $profil->latitude ?? -6.2 }};
    const lng = {{ $profil->longitude ?? 106.8 }};

    /* =======================
       MAP INIT
    ======================== */
    function initMap() {
        map = L.map('map').setView([lat, lng], 16);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')
            .addTo(map);

        marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        marker.on('dragend', e => {
            const pos = e.target.getLatLng();
            $('#lat').val(pos.lat.toFixed(6));
            $('#lng').val(pos.lng.toFixed(6));
        });

        map.on('click', e => {
            marker.setLatLng(e.latlng);
            $('#lat').val(e.latlng.lat.toFixed(6));
            $('#lng').val(e.latlng.lng.toFixed(6));
        });
    }

    /* =======================
       PAGE INIT
    ======================== */
    $(function() {
        initMap();

        // PREVIEW FOTO
        $('[name="logo"], [name="struktur"]').on('change', function() {
            let preview = $(this).attr('name') === 'logo' ? '#logoPreview' : '#strukturPreview';
            $(preview).empty();

            if (this.files[0]) {
                let reader = new FileReader();
                reader.onload = e => {
                    const img = preview === '#logoPreview'
                        ? `<img src="${e.target.result}" class="preview-img">`
                        : `<img src="${e.target.result}" width="150" class="img-thumbnail">`;
                    $(preview).html(img);
                };
                reader.readAsDataURL(this.files[0]);
            }
        });

        // SIMPAN PROFIL
        $('#profilForm').on('submit', function(e) {
            e.preventDefault();
            let formData = new FormData(this);

            $.ajax({
                url: '{{ route("admin.profil.update") }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: res => {
                    Swal.fire('Berhasil!', res.message, 'success');
                    setTimeout(() => location.reload(), 1500);
                },
                error: xhr => {
                    Swal.fire('Error!', xhr.responseJSON?.message || 'Gagal!', 'error');
                }
            });
        });

        // DRAG & DROP PENGURUS
        new Sortable(document.getElementById('pengurusList'), {
            animation: 150,
            onEnd: evt => {
                const order = [...evt.to.children].map(el => el.dataset.id);

                $.post('{{ route("admin.profil.pengurus.reorder") }}', {
                    _token: '{{ csrf_token() }}',
                    order
                });
            }
        });
    });

    /* =======================
       MODAL CRUD PENGURUS
    ======================== */
    window.openPengurusModal = () => {
        $('#pengurusForm')[0].reset();
        $('#pengurusId').val('');
        $('#fotoPreview').html(`<div class="bg-light rounded-circle" style="width:80px;height:80px;"></div>`);
        $('#pengurusModal').modal('show');
    };

    window.editPengurus = id => {
        $.get(`/admin/profil/pengurus/${id}`, data => {
            $('#pengurusId').val(data.id);
            $('#nama').val(data.nama);
            $('#jabatan').val(data.jabatan);
            $('#keterangan').val(data.keterangan);

            $('#fotoPreview').html(
                data.foto_url
                    ? `<img src="${data.foto_url}" width="80" class="rounded-circle">`
                    : `<div class="bg-light rounded-circle" style="width:80px;height:80px;"></div>`
            );

            $('#pengurusModal').modal('show');
        });
    };

    window.hapusPengurus = id => {
        Swal.fire({
            title: 'Yakin?',
            icon: 'warning',
            showCancelButton: true
        }).then(res => {
            if (res.isConfirmed) {
                $.ajax({
                    url: `/admin/profil/pengurus/${id}`,
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => location.reload()
                });
            }
        });
    };

    $('#pengurusForm').on('submit', function(e) {
        e.preventDefault();

        const id = $('#pengurusId').val();
        let formData = new FormData(this);

        if (id) formData.append('_method', 'PUT');

        $.ajax({
            url: id 
                ? `/admin/profil/pengurus/${id}` 
                : `/admin/profil/pengurus`,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: () => {
                $('#pengurusModal').modal('hide');
                location.reload();
            }
        });
    });

    // PREVIEW FOTO MODAL
    $('#fotoInput').on('change', function() {
        const file = this.files[0];
        const preview = $('#fotoPreview');
        preview.empty();

        if (file) {
            const reader = new FileReader();
            reader.onload = e => preview.html(
                `<img src="${e.target.result}" width="80" class="rounded-circle">`
            );
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush

@endsection