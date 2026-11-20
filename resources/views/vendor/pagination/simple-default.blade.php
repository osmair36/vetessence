@if ($paginator->hasPages())
    <nav class="d-flex flex-column flex-md-row justify-content-between align-items-center py-2" role="navigation" aria-label="Navegação da Paginação">
        <div class="mb-2 mb-md-0 text-muted pagination-info">
            <span class="small font-weight-normal">
                Página
                <strong class="text-dark">{{ $paginator->currentPage() }}</strong>
            </span>
        </div>

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
@endif
