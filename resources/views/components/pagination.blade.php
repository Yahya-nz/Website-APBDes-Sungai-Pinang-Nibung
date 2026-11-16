@props(['paginator', 'label' => 'data'])
@if($paginator->total() > 0)
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        $start = max(1, $current - 2);
        $end = min($last, $current + 2);
    @endphp
    <div class="pagination">
        <span>Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} {{ $label }}</span>
        @if($paginator->hasPages())
            <nav class="page-links" aria-label="Halaman {{ $label }}">
                @if($paginator->onFirstPage())<span class="page-link is-disabled" aria-hidden="true"><x-icon name="chevron-left" /></span>@else<a class="page-link" href="{{ $paginator->previousPageUrl() }}" aria-label="Halaman sebelumnya"><x-icon name="chevron-left" /></a>@endif
                @if($start > 1)<a class="page-link" href="{{ $paginator->url(1) }}">1</a>@if($start > 2)<span class="page-gap">…</span>@endif @endif
                @foreach($paginator->getUrlRange($start, $end) as $page => $url)
                    @if($page === $current)<span class="page-link is-active" aria-current="page">{{ $page }}</span>@else<a class="page-link" href="{{ $url }}">{{ $page }}</a>@endif
                @endforeach
                @if($end < $last)@if($end < $last - 1)<span class="page-gap">…</span>@endif<a class="page-link" href="{{ $paginator->url($last) }}">{{ $last }}</a>@endif
                @if($paginator->hasMorePages())<a class="page-link" href="{{ $paginator->nextPageUrl() }}" aria-label="Halaman berikutnya"><x-icon name="chevron-right" /></a>@else<span class="page-link is-disabled" aria-hidden="true"><x-icon name="chevron-right" /></span>@endif
            </nav>
        @endif
    </div>
@endif
