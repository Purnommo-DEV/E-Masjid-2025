{{-- resources/views/masjid/mrj/admin/qurban/report/index.blade.php --}}
@extends('masjid.master')

@section('title', 'Manajemen Laporan Qurban')

@push('style')
<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        font-size: 0.7rem;
        font-weight: 600;
    }
    .status-active { background: #d1fae5; color: #059669; }
    .status-inactive { background: #fef3c7; color: #d97706; }
    .status-published { background: #dbeafe; color: #2563eb; }
    .status-draft { background: #f3f4f6; color: #6b7280; }
    .btn-action {
        padding: 0.35rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn-action:hover { transform: translateY(-1px); }
</style>
@endpush

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">📊 Manajemen Laporan Qurban</h1>
            <p class="text-gray-500 text-sm mt-1">Kelola laporan qurban per tahun hijriah</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.qurban.report.create') }}" class="bg-emerald-600 hover:bg-emerald-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Buat Laporan Baru
            </a>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tahun</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Aktif</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Publikasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Statistik</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Terakhir Update</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($reports as $report)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="font-semibold text-gray-900">{{ $report->tahun_hijriah }}</div>
                            <div class="text-xs text-gray-500">{{ $report->tahun_masehi }} M</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($report->is_active)
                                <span class="status-badge status-active">✓ Aktif</span>
                            @else
                                <span class="status-badge status-inactive">○ Tidak Aktif</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($report->is_published)
                                <span class="status-badge status-published">📢 Terbit</span>
                            @else
                                <span class="status-badge status-draft">📝 Draft</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-600">
                                <div>🐂 Sapi: {{ $report->stat_hewan_sapi }} | 🐐 Kambing: {{ $report->stat_hewan_kambing }}</div>
                                <div class="text-xs text-gray-400">Total: {{ $report->total_hewan }} ekor</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ $report->updated_at->diffForHumans() }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.qurban.report.edit', $report->id) }}" class="btn-action bg-amber-50 text-amber-700 hover:bg-amber-100">Edit</a>
                                <a href="{{ route('qurban.laporan', $report->tahun_hijriah) }}" target="_blank" class="btn-action bg-blue-50 text-blue-700 hover:bg-blue-100">Lihat</a>
                                @if(!$report->is_active)
                                <button onclick="setActive({{ $report->id }})" class="btn-action bg-green-50 text-green-700 hover:bg-green-100">Aktifkan</button>
                                @endif
                                <button onclick="cloneReport({{ $report->id }})" class="btn-action bg-purple-50 text-purple-700 hover:bg-purple-100">Clone</button>
                                <button onclick="deleteReport({{ $report->id }}, '{{ $report->tahun_hijriah }}')" class="btn-action bg-red-50 text-red-700 hover:bg-red-100">Hapus</button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Belum ada data laporan. <a href="{{ route('admin.qurban.report.create') }}" class="text-emerald-600 hover:underline">Buat laporan pertama</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function cloneReport(id) {
    Swal.fire({
        title: 'Clone Laporan?',
        text: 'Akan membuat laporan untuk tahun berikutnya dengan data yang sama (statistik akan direset).',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Ya, Clone!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '{{ url("admin/qurban/report") }}/' + id + '/clone';
        }
    });
}

function setActive(id) {
    Swal.fire({
        title: 'Aktifkan Laporan?',
        text: 'Laporan ini akan menjadi laporan yang ditampilkan di halaman publik. Laporan lain akan dinonaktifkan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Aktifkan!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '{{ url("admin/qurban/report") }}/' + id + '/set-active';
        }
    });
}

function deleteReport(id, tahun) {
    Swal.fire({
        title: 'Hapus Laporan?',
        html: `Apakah Anda yakin ingin menghapus laporan <strong>${tahun}</strong>?<br>Data yang dihapus tidak dapat dikembalikan!`,
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ url("admin/qurban/report") }}/' + id,
                type: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    Swal.fire('Berhasil!', 'Laporan dihapus.', 'success').then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire('Gagal!', 'Terjadi kesalahan.', 'error');
                }
            });
        }
    });
}
</script>
@endpush
@endsection