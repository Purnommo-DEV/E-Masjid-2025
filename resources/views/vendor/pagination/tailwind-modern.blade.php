@if ($paginator->hasPages())
    <nav class="flex items-center justify-center mt-8 space-x-2">
        <!-- Previous -->
        @if ($paginator->onFirstPage())
            <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-full cursor-not-allowed">
                ← Prev
            </span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="px-4 py-2 text-sm font-medium text-emerald-700 bg-white border border-emerald-300 rounded-full hover:bg-emerald-50 hover:border-emerald-500 transition">
                ← Prev
            </a>
        @endif

        <!-- Numbers -->
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="px-4 py-2 text-sm font-medium text-gray-500">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="px-4 py-2 text-sm font-medium text-white bg-emerald-600 border border-emerald-600 rounded-full shadow-sm">
                            {{ $page }}
                        </span>
                    @else
                        <a href="{{ $url }}" class="px-4 py-2 text-sm font-medium text-emerald-700 bg-white border border-emerald-300 rounded-full hover:bg-emerald-50 hover:border-emerald-500 transition">
                            {{ $page }}
                        </a>
                    @endif
                @endforeach
            @endif
        @endforeach

        <!-- Next -->
        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="px-4 py-2 text-sm font-medium text-emerald-700 bg-white border border-emerald-300 rounded-full hover:bg-emerald-50 hover:border-emerald-500 transition">
                Next →
            </a>
        @else
            <span class="px-4 py-2 text-sm font-medium text-gray-400 bg-white border border-gray-300 rounded-full cursor-not-allowed">
                Next →
            </span>
        @endif
    </nav>
@endif