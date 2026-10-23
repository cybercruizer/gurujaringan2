@props(['paginator'])

@if ($paginator->hasPages())
    @php
        $pageLinks = $paginator->getUrlRange(
            max(1, $paginator->currentPage() - 2),
            min($paginator->lastPage(), $paginator->currentPage() + 2),
        );
    @endphp

    <nav aria-label="Pagination" class="flex flex-wrap items-center justify-center gap-2">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-400">Previous</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Previous</a>
        @endif

        @foreach ($pageLinks as $page => $url)
            @if ($page === $paginator->currentPage())
                <span aria-current="page" class="rounded-lg bg-stone-900 px-3 py-2 text-sm font-medium text-white">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">{{ $page }}</a>
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-lg border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-100">Next</a>
        @else
            <span aria-disabled="true" class="rounded-lg border border-stone-200 px-3 py-2 text-sm text-stone-400">Next</span>
        @endif
    </nav>
@endif
