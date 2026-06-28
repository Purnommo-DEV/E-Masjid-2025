@extends('masjid.master')

@section('title', 'SEO Halaman')

@section('content')
    <div class="max-w-6xl mx-auto my-6 bg-white rounded-2xl shadow-xl border border-emerald-50 overflow-hidden">
        <div class="px-6 py-5 bg-gradient-to-r from-emerald-700 to-teal-600 text-white flex flex-col gap-1">
            <h1 class="text-xl font-extrabold">SEO Halaman</h1>
            <p class="text-sm text-emerald-50">Atur meta title, description, canonical, robots, dan image untuk halaman utama website.</p>
        </div>

        @if(session('success'))
            <div class="mx-6 mt-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mx-6 mt-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                Periksa kembali field SEO yang diisi.
            </div>
        @endif

        <form action="{{ route('admin.seo-pages.update') }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            @foreach($pages as $page)
                @php
                    $key = $page['key'];
                    $seo = $page['seo'];
                @endphp

                <section class="rounded-2xl border border-slate-200 bg-slate-50/60 p-5">
                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-5">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">{{ $page['label'] }}</h2>
                            <p class="text-xs text-slate-500 mt-1">{{ $key }}</p>
                        </div>
                        <span class="inline-flex self-start px-3 py-1 rounded-full bg-white border border-emerald-100 text-xs font-semibold text-emerald-700">
                            Optional override
                        </span>
                    </div>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Meta Title</label>
                            <input type="text"
                                   name="pages[{{ $key }}][title]"
                                   maxlength="70"
                                   value="{{ old("pages.$key.title", $seo->title) }}"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="Kosongkan untuk otomatis">
                            <p class="text-xs text-slate-400 mt-1">Ideal 50-60 karakter.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Robots</label>
                            <select name="pages[{{ $key }}][robots]"
                                    class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                @php $robots = old("pages.$key.robots", $seo->robots); @endphp
                                <option value="">Default: index, follow</option>
                                <option value="index, follow" @selected($robots === 'index, follow')>index, follow</option>
                                <option value="noindex, follow" @selected($robots === 'noindex, follow')>noindex, follow</option>
                                <option value="noindex, nofollow" @selected($robots === 'noindex, nofollow')>noindex, nofollow</option>
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Meta Description</label>
                            <textarea name="pages[{{ $key }}][description]"
                                      maxlength="170"
                                      rows="3"
                                      class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                      placeholder="Kosongkan untuk otomatis">{{ old("pages.$key.description", $seo->description) }}</textarea>
                            <p class="text-xs text-slate-400 mt-1">Ideal 140-160 karakter.</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Social Image URL</label>
                            <input type="text"
                                   name="pages[{{ $key }}][image]"
                                   value="{{ old("pages.$key.image", $seo->image) }}"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="https://... atau /storage/...">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Canonical URL</label>
                            <input type="url"
                                   name="pages[{{ $key }}][canonical_url]"
                                   value="{{ old("pages.$key.canonical_url", $seo->canonical_url) }}"
                                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"
                                   placeholder="Kosongkan untuk URL halaman saat ini">
                        </div>
                    </div>
                </section>
            @endforeach

            <div class="sticky bottom-4 flex justify-end">
                <button type="submit" class="px-6 py-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-bold shadow-lg">
                    Simpan SEO Halaman
                </button>
            </div>
        </form>
    </div>
@endsection
