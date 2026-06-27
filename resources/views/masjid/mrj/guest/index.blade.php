@extends('masjid.master-guest')

@section('title', 'Jadwal Sholat, Agenda & Layanan Jamaah')

@push('head')
    @include('masjid.mrj.guest.home._head')
@endpush

@section('content')
    @include('masjid.mrj.guest.home._loader')

    <div class="home-page-shell min-h-screen bg-gradient-to-br from-teal-50 via-emerald-50 to-cyan-50 relative overflow-hidden bg-pattern-islamic">
        @include('masjid.mrj.guest.home._hero-jadwal')
        @include('masjid.mrj.guest.home._info-cepat')
        @include('masjid.mrj.guest.home._banner')
        @include('masjid.mrj.guest.home._qurban')
        @include('masjid.mrj.guest.home._agenda')
        @include('masjid.mrj.guest.home._berita-pengumuman')
        @include('masjid.mrj.guest.home._layanan')
        @include('masjid.mrj.guest.home._quote-harian')
        @include('masjid.mrj.guest.home._donasi')
        @include('masjid.mrj.guest.home._galeri')
        @include('masjid.mrj.guest.home._kontak')
    </div>

    @include('masjid.mrj.guest.home._back-to-top')
@endsection

@push('style')
    @include('masjid.mrj.guest.home._styles')
@endpush

@push('scripts')
    @include('masjid.mrj.guest.home._scripts')
@endpush
