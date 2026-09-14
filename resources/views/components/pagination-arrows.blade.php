@if ($paginator->hasPages())
    <nav aria-label="Paginación" class="flex items-center justify-between gap-3">
        @foreach (['prev' => ['Anterior', $paginator->previousPageUrl(), '&#8592;'], 'next' => ['Siguiente', $paginator->nextPageUrl(), '&#8594;']] as $direction => [$label, $url, $arrow])
            @if ($url)
                <a href="{{ $url }}" rel="{{ $direction }}" aria-label="{{ $label }}" title="{{ $label }}" class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border border-sky-700 bg-white text-3xl font-bold leading-none text-sky-800 hover:bg-sky-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sky-700">
                    <span aria-hidden="true">{!! $arrow !!}</span>
                </a>
            @else
                <span aria-disabled="true" aria-label="{{ $label }}" title="{{ $label }}" class="inline-flex h-12 w-12 shrink-0 cursor-not-allowed items-center justify-center rounded-lg border border-slate-300 bg-slate-100 text-3xl font-bold leading-none text-slate-500">
                    <span aria-hidden="true">{!! $arrow !!}</span>
                </span>
            @endif
        @endforeach
    </nav>
@endif
