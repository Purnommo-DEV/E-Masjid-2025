@props(['title'])

<section {{ $attributes->merge(['class' => 'card overflow-hidden border border-base-300 bg-base-100 shadow-sm']) }}>
    <div class="border-b border-base-300 px-4 py-3">
        <h2 class="font-semibold">{{ $title }}</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="table table-zebra table-sm text-sm">{{ $slot }}</table>
    </div>
</section>
