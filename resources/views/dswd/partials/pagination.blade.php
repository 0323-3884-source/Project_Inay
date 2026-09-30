<nav class="dswd-pagination" aria-label="Pagination">
    <span>{{ $paginator->total() }} results · Page {{ $paginator->currentPage() }} of {{ $paginator->lastPage() }}</span>
    <div class="dswd-actions">@if($paginator->previousPageUrl())<a class="dswd-button secondary" href="{{ $paginator->previousPageUrl() }}">Previous</a>@endif @if($paginator->nextPageUrl())<a class="dswd-button secondary" href="{{ $paginator->nextPageUrl() }}">Next</a>@endif</div>
</nav>
