{{-- Reusable Custom Datatable Pagination Component Baku SAE (Acuan: peserta-didik-aktif) --}}
@if (isset($paginator) && method_exists($paginator, 'hasPages') && $paginator->hasPages())
    <div class="custom-pagination">
        @if ($paginator->onFirstPage())
            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-btn" title="Sebelumnya"><i
                    class="fas fa-chevron-left"></i></a>
        @endif

        @php
            $cur = $paginator->currentPage();
            $last = $paginator->lastPage();
            $from = max(1, $cur - 2);
            $to = min($last, $cur + 2);
        @endphp

        @if ($from > 1)
            <a href="{{ $paginator->url(1) }}" class="page-btn">1</a>
            @if ($from > 2)
                <span class="page-info">&hellip;</span>
            @endif
        @endif

        @for ($i = $from; $i <= $to; $i++)
            <a href="{{ $paginator->url($i) }}"
                class="page-btn {{ $i === $cur ? 'current' : '' }}">{{ $i }}</a>
        @endfor

        @if ($to < $last)
            @if ($to < $last - 1)
                <span class="page-info">&hellip;</span>
            @endif
            <a href="{{ $paginator->url($last) }}" class="page-btn">{{ $last }}</a>
        @endif

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-btn" title="Selanjutnya"><i
                    class="fas fa-chevron-right"></i></a>
        @else
            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
        @endif
    </div>
@endif
