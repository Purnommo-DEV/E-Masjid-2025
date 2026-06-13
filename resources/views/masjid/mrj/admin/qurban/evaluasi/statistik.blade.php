@extends('masjid.master')
@section('title', 'Statistik Evaluasi Qurban')

@section('content')
<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    .stat-number {
        font-size: 32px;
        font-weight: bold;
        color: #059669;
    }
    .progress-bar {
        height: 8px;
        background: #e5e7eb;
        border-radius: 4px;
        overflow: hidden;
    }
    .progress-fill {
        height: 100%;
        background: #059669;
        border-radius: 4px;
    }
</style>

<div class="card-wrapper">
    <div class="card-header">
        <h3 class="title">📈 Statistik Evaluasi Qurban</h3>
        <a href="{{ route('admin.evaluasi-qurban.index') }}" class="btn-primary-solid" style="background: white; color: #059669;">
            ← Kembali ke Data
        </a>
    </div>
    <div class="card-body">
        
        <!-- Ringkasan -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card text-center">
                <div class="stat-number">{{ $statistik['total'] }}</div>
                <div class="text-gray-500 text-sm">Total Responden</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-number">{{ $statistik['rata_pendaftaran'] }}</div>
                <div class="text-gray-500 text-sm">⭐ Rata-rata Pendaftaran</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-number">{{ $statistik['rata_pelaksanaan'] }}</div>
                <div class="text-gray-500 text-sm">⭐ Rata-rata Pelaksanaan</div>
            </div>
            <div class="stat-card text-center">
                <div class="stat-number">{{ $statistik['rata_distribusi'] }}</div>
                <div class="text-gray-500 text-sm">⭐ Rata-rata Distribusi</div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            
            <!-- Rencana Qurban -->
            <div class="stat-card">
                <h4 class="font-bold text-lg mb-3">📅 Rencana Qurban Tahun Depan</h4>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>✅ Ya ({{ $statistik['rencana_ya'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['rencana_ya']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['rencana_ya']/$statistik['total'])*100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>🤔 Mungkin ({{ $statistik['rencana_mungkin'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['rencana_mungkin']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['rencana_mungkin']/$statistik['total'])*100 : 0 }}%; background: #f59e0b;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>❌ Tidak ({{ $statistik['rencana_tidak'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['rencana_tidak']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['rencana_tidak']/$statistik['total'])*100 : 0 }}%; background: #ef4444;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Jenis Hewan -->
            <div class="stat-card">
                <h4 class="font-bold text-lg mb-3">🐪 Jenis Hewan Qurban</h4>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>🐃 Sapi ({{ $statistik['sapi'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['sapi']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['sapi']/$statistik['total'])*100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>🐐 Kambing ({{ $statistik['kambing'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['kambing']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['kambing']/$statistik['total'])*100 : 0 }}%; background: #06b6d4;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tempat Qurban -->
            <div class="stat-card">
                <h4 class="font-bold text-lg mb-3">🕌 Tempel Berqurban</h4>
                <div class="space-y-3">
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>Masjid RJ ({{ $statistik['tempat_masjid'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['tempat_masjid']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['tempat_masjid']/$statistik['total'])*100 : 0 }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>Pihak Lain ({{ $statistik['tempat_pihak_lain'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['tempat_pihak_lain']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['tempat_pihak_lain']/$statistik['total'])*100 : 0 }}%; background: #f59e0b;"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-sm">
                            <span>Keduanya ({{ $statistik['tempat_keduanya'] }})</span>
                            <span>{{ $statistik['total'] > 0 ? round(($statistik['tempat_keduanya']/$statistik['total'])*100) : 0 }}%</span>
                        </div>
                        <div class="progress-bar mt-1">
                            <div class="progress-fill" style="width: {{ $statistik['total'] > 0 ? ($statistik['tempat_keduanya']/$statistik['total'])*100 : 0 }}%; background: #8b5cf6;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Rating Rata-rata -->
            <div class="stat-card">
                <h4 class="font-bold text-lg mb-3">⭐ Rating Kepuasan</h4>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span>Pendaftaran</span>
                        <span class="font-semibold">{{ $statistik['rata_pendaftaran'] }} / 5</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($statistik['rata_pendaftaran']/5)*100 }}%"></div>
                    </div>
                    <div class="flex justify-between">
                        <span>Pelaksanaan</span>
                        <span class="font-semibold">{{ $statistik['rata_pelaksanaan'] }} / 5</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($statistik['rata_pelaksanaan']/5)*100 }}%"></div>
                    </div>
                    <div class="flex justify-between">
                        <span>Distribusi</span>
                        <span class="font-semibold">{{ $statistik['rata_distribusi'] }} / 5</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($statistik['rata_distribusi']/5)*100 }}%"></div>
                    </div>
                    <div class="flex justify-between">
                        <span>Kualitas Hewan</span>
                        <span class="font-semibold">{{ $statistik['rata_kualitas'] }} / 5</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: {{ ($statistik['rata_kualitas']/5)*100 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection