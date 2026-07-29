@if ($paginator->hasPages())
    <nav class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" role="navigation" aria-label="Pagination">
        <p class="text-sm text-slate-600">
            Résultats
            <span class="font-semibold text-emerald-950">{{ $paginator->firstItem() ?? 0 }}</span>
            à
            <span class="font-semibold text-emerald-950">{{ $paginator->lastItem() ?? 0 }}</span>
            sur
            <span class="font-semibold text-emerald-950">{{ $paginator->total() }}</span>
        </p>

        <div class="flex flex-wrap items-center gap-2">
            @if ($paginator->onFirstPage())
                <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-400" aria-disabled="true">
                    Précédent
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-950 transition hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                    Précédent
                </a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-slate-400" aria-hidden="true">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page === $paginator->currentPage())
                            <span class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl bg-emerald-950 px-3 text-sm font-bold text-white" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a href="{{ $url }}" class="inline-flex h-10 min-w-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 text-sm font-semibold text-emerald-950 transition hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2" aria-label="Aller à la page {{ $page }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-emerald-950 transition hover:border-emerald-300 hover:bg-emerald-50 focus:outline-none focus:ring-2 focus:ring-emerald-600 focus:ring-offset-2">
                    Suivant
                </a>
            @else
                <span class="cursor-not-allowed rounded-xl border border-slate-200 bg-slate-100 px-3 py-2 text-sm text-slate-400" aria-disabled="true">
                    Suivant
                </span>
            @endif
        </div>
    </nav>
@endif
