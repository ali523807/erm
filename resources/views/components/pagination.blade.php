@props(['paginator'])

@if($paginator->hasPages() || $paginator->total() > 0)
    @php($elements = \Illuminate\Pagination\UrlWindow::make($paginator))

    <div class="pagination-shell">
        <div class="pagination-summary">
            Showing <strong>{{ $paginator->firstItem() ?? 0 }}</strong> to <strong>{{ $paginator->lastItem() ?? 0 }}</strong>
            of <strong>{{ $paginator->total() }}</strong> results
        </div>

        @if($paginator->hasPages())
            <nav class="pagination-list" aria-label="Pagination">
                @if($paginator->onFirstPage())
                    <span class="pagination-link is-disabled" aria-disabled="true">
                        <x-lucide-chevron-left class="w-4 h-4"/>
                    </span>
                @else
                    <a class="pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                        <x-lucide-chevron-left class="w-4 h-4"/>
                    </a>
                @endif

                @foreach($elements as $element)
                    @if(is_string($element))
                        <span class="pagination-link is-gap">{{ $element }}</span>
                    @endif

                    @if(is_array($element))
                        @foreach($element as $page => $url)
                            @if($page === $paginator->currentPage())
                                <span class="pagination-link is-active" aria-current="page">{{ $page }}</span>
                            @else
                                <a class="pagination-link" href="{{ $url }}">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if($paginator->hasMorePages())
                    <a class="pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                        <x-lucide-chevron-right class="w-4 h-4"/>
                    </a>
                @else
                    <span class="pagination-link is-disabled" aria-disabled="true">
                        <x-lucide-chevron-right class="w-4 h-4"/>
                    </span>
                @endif
            </nav>
        @endif
    </div>
@endif
