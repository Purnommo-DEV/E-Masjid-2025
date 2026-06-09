{{-- resources/views/masjid/mrj/admin/qurban/report/create.blade.php --}}
@extends('masjid.master')

@section('title', 'Buat Laporan Baru')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.qurban.report.index') }}" class="text-gray-500 hover:text-gray-700">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
        </a>
        <h1 class="text-2xl font-bold text-gray-800">📝 Buat Laporan Baru</h1>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <form action="{{ route('admin.qurban.report.store') }}" method="POST">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Hijriah <span class="text-red-500">*</span></label>
                    <input type="text" name="tahun_hijriah" value="{{ old('tahun_hijriah', $nextYear) }}" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                           placeholder="Contoh: 1448 H">
                    @error('tahun_hijriah') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tahun Masehi <span class="text-red-500">*</span></label>
                    <input type="text" name="tahun_masehi" value="{{ old('tahun_masehi', $nextMasehi) }}" 
                           class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                           placeholder="Contoh: 2027">
                    @error('tahun_masehi') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>
            
            <div class="mt-6 flex justify-end gap-3">
                <a href="{{ route('admin.qurban.report.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Batal</a>
                <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold">Buat Laporan</button>
            </div>
        </form>
    </div>
    
    <div class="mt-6 bg-blue-50 rounded-xl p-4 border border-blue-200">
        <div class="flex gap-3">
            <svg class="w-5 h-5 text-blue-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div class="text-sm text-blue-700">
                <p class="font-semibold">Tips:</p>
                <p>Setelah membuat laporan, Anda bisa mengisi data lengkap (statistik, keuangan, galeri, dll) melalui halaman Edit.</p>
                <p class="mt-1">Gunakan tombol "Clone" untuk membuat laporan tahun berikutnya dengan menyalin data dari tahun sebelumnya.</p>
            </div>
        </div>
    </div>
</div>
@endsection