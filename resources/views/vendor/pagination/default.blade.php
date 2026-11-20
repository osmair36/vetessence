@if ($paginator->hasPages())
    <nav class="d-flex flex-column flex-md-row justify-content-between align-items-center py-2" role="navigation" aria-label="Navegação da Paginação">
        {{-- Texto informativo em Português do Brasil com espaçamento adequado --}}
        <div class="mb-2 mb-md-0 text-muted pagination-info">
            <span class="small font-weight-normal">
                Mostrando de
                <strong class="text-dark">{{ $paginator->firstItem() ?? 0 }}</strong>
                até
                <strong class="text-dark">{{ $paginator->lastItem() ?? 0 }}</strong>
                de
                <strong class="text-dark">{{ $paginator->total() }}</strong>
                registros
            </span>
        </div>

        {{-- Botões de navegação --}}
        <div>
            <ul class="pagination pagination-sm mb-0">
                {{-- Botão Anterior --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="Anterior">
                        <span class="page-link" aria-hidden="true">&laquo; Anterior</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior">&laquo; Anterior</a>
                    </li>
                @endif

                {{-- Elementos de paginação (números de página e reticências) --}}
                @foreach ($elements as $element)
                    {{-- Separador "..." --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Links de páginas --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Botão Próximo --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Próximo">Próximo &raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="Próximo">
                        <span class="page-link" aria-hidden="true">Próximo &raquo;</span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@elseif ($paginator->total() > 0)
    <div class="d-flex justify-content-between align-items-center py-2 text-muted pagination-info">
        <span class="small font-weight-normal">
            Mostrando todos os
            <strong class="text-dark">{{ $paginator->total() }}</strong>
            registros
        </span>
    </div>
@endif
