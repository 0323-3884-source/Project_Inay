@if ($paginator->hasPages())
<nav class="site-pagination" aria-label="Pagination">
    <p>@if(method_exists($paginator, 'total')) Showing {{ $paginator->firstItem() ?? 0 }} to {{ $paginator->lastItem() ?? 0 }} of {{ $paginator->total() }} results @else Page {{ $paginator->currentPage() }} @endif</p>
    <div class="site-pagination-controls">
        @if($paginator->onFirstPage())<span class="site-pagination-link is-disabled" aria-disabled="true">Previous</span>@else<a class="site-pagination-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Previous</a>@endif
        <span aria-current="page">Page {{ $paginator->currentPage() }}@if(method_exists($paginator, 'lastPage')) of {{ $paginator->lastPage() }}@endif</span>
        @if($paginator->hasMorePages())<a class="site-pagination-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Next</a>@else<span class="site-pagination-link is-disabled" aria-disabled="true">Next</span>@endif
    </div>
</nav>
@endif
