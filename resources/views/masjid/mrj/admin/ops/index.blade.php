@extends('masjid.master')

@section('title', 'Operasi Sistem')

@section('content')
@php
    $ops = [
        [
            'title' => 'Clear Cache',
            'description' => 'Membersihkan view cache, application cache, dan config cache.',
            'route' => 'admin.ops.clear-cache',
            'button' => 'Jalankan Clear Cache',
            'tone' => 'emerald',
            'confirm' => 'Bersihkan cache aplikasi sekarang?',
        ],
        [
            'title' => 'Run Migration',
            'description' => 'Menjalankan migration production dengan flag --force.',
            'route' => 'admin.ops.run-migrate',
            'button' => 'Jalankan Migration',
            'tone' => 'amber',
            'confirm' => 'Jalankan migration production sekarang? Pastikan database sudah dibackup.',
        ],
        [
            'title' => 'Run Seeder',
            'description' => 'Menjalankan database seeder production dengan flag --force.',
            'route' => 'admin.ops.run-seeder',
            'button' => 'Jalankan Seeder',
            'tone' => 'rose',
            'confirm' => 'Jalankan seeder production sekarang? Data bisa berubah sesuai isi seeder.',
        ],
    ];
@endphp

<div class="mx-auto max-w-5xl">
    <div class="mb-6 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Operasi Sistem</h1>
                <p class="mt-1 text-sm text-slate-500">Aksi sensitif khusus SuperAdmin. Semua tombol memakai POST, CSRF, dan signed URL sementara.</p>
            </div>
            <span class="inline-flex w-fit items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-100">
                SuperAdmin
            </span>
        </div>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        @foreach($ops as $op)
            @php
                $action = \Illuminate\Support\Facades\URL::temporarySignedRoute($op['route'], now()->addMinutes(10));
                $classes = [
                    'emerald' => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-300',
                    'amber' => 'bg-amber-500 hover:bg-amber-600 focus:ring-amber-300',
                    'rose' => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-300',
                ][$op['tone']];
            @endphp

            <div class="flex h-full flex-col rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div class="flex-1">
                    <h2 class="text-base font-bold text-slate-900">{{ $op['title'] }}</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $op['description'] }}</p>
                </div>

                <form method="POST" action="{{ $action }}" class="mt-5" onsubmit="return confirm(@js($op['confirm']))">
                    @csrf
                    <button
                        type="submit"
                        class="w-full rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-4 {{ $classes }}"
                    >
                        {{ $op['button'] }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <div class="mt-5 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
        Gunakan tombol migration dan seeder hanya setelah backup database. Untuk operasi rutin server, CLI tetap menjadi pilihan paling aman.
    </div>
</div>
@endsection
