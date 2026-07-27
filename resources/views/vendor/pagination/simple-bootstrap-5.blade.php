{{--
    Apresenta a paginação simples através dos componentes do Bootstrap 5.

    O nome da variável $paginator pertence ao contrato interno do sistema
    de paginação do Laravel.

    @since 1.0.0
    @version 3.0.0
--}}

@if ($paginator->hasPages())
    <nav aria-label="Paginação dos resultados">
        <ul class="pagination mb-0">
            @if ($paginator->onFirstPage())
                <li
                    class="page-item disabled"
                    aria-disabled="true"
                >
                    <span class="page-link">
                        Anterior
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a
                        class="page-link"
                        href="{{ $paginator->previousPageUrl() }}"
                        rel="prev"
                    >
                        Anterior
                    </a>
                </li>
            @endif

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a
                        class="page-link"
                        href="{{ $paginator->nextPageUrl() }}"
                        rel="next"
                    >
                        Seguinte
                    </a>
                </li>
            @else
                <li
                    class="page-item disabled"
                    aria-disabled="true"
                >
                    <span class="page-link">
                        Seguinte
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif
