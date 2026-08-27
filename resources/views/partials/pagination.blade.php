@php($paginator->appends(request()->query()))
@if ($paginator->hasPages())
    <nav class="pagination" aria-label="Pagination">
        <span class="pagination-info">
            Showing {{ $paginator->firstItem() }}&ndash;{{ $paginator->lastItem() }} of {{ $paginator->total() }}
        </span>

        <span class="pagination-links">
            @if ($paginator->onFirstPage())
                <span class="pagination-btn is-disabled" aria-disabled="true">&lsaquo; Prev</span>
            @else
                <a class="pagination-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev">&lsaquo; Prev</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="pagination-ellipsis">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pagination-btn is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a class="pagination-btn" href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a class="pagination-btn" href="{{ $paginator->nextPageUrl() }}" rel="next">Next &rsaquo;</a>
            @else
                <span class="pagination-btn is-disabled" aria-disabled="true">Next &rsaquo;</span>
            @endif
        </span>
    </nav>
@endif
