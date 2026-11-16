@if($p->hasPages())
<nav class="pager noprint">
  @if($p->onFirstPage())<span>Previous</span>@else<a href="{{ $p->previousPageUrl() }}">Previous</a>@endif
  <span>Page {{ $p->currentPage() }} of {{ $p->lastPage() }}</span>
  @if($p->hasMorePages())<a href="{{ $p->nextPageUrl() }}">Next</a>@else<span>Next</span>@endif
</nav>
@endif
