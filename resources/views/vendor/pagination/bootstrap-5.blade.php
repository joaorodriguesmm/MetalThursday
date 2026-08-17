{{--
    Apresenta a paginação completa através dos componentes do Bootstrap 5.

    Os nomes das variáveis $paginator e $elements pertencem ao contrato
    interno do sistema de paginação do Laravel.

    @since 1.0.0
--}}

@if ($paginator->hasPages())
    <nav
        class="d-flex justify-content-between align-items-center"
        aria-label="Paginação dos resultados"
    >
        <div class="d-flex justify-content-between flex-fill d-sm-none">
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
        </div>

        <div
            class="d-none flex-sm-fill d-sm-flex align-items-sm-center justify-content-sm-between gap-3"
        >
            <p class="small text-muted mb-0">
                A mostrar
                <strong>{{ $paginator->firstItem() }}</strong>
                a
                <strong>{{ $paginator->lastItem() }}</strong>
                de
                <strong>{{ $paginator->total() }}</strong>
                resultados
            </p>

            <ul class="pagination mb-0">
                @if ($paginator->onFirstPage())
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                        aria-label="Primeira página"
                    >
                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            &laquo;
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{ $paginator->url(1) }}"
                            rel="first"
                            aria-label="Primeira página"
                        >
                            <span aria-hidden="true">
                                &laquo;
                            </span>
                        </a>
                    </li>
                @endif

                @if ($paginator->onFirstPage())
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                        aria-label="Página anterior"
                    >
                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            &lsaquo;
                        </span>
                    </li>
                @else
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{ $paginator->previousPageUrl() }}"
                            rel="prev"
                            aria-label="Página anterior"
                        >
                            <span aria-hidden="true">
                                &lsaquo;
                            </span>
                        </a>
                    </li>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li
                            class="page-item disabled"
                            aria-disabled="true"
                        >
                            <span
                                class="page-link"
                                aria-hidden="true"
                            >
                                {{ $element }}
                            </span>

                            <span class="visually-hidden">
                                Existem mais páginas
                            </span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if (
                                (int) $page
                                === $paginator->currentPage()
                            )
                                <li
                                    class="page-item active"
                                    aria-current="page"
                                >
                                    <span class="page-link">
                                        {{ $page }}

                                        <span class="visually-hidden">
                                            Página atual
                                        </span>
                                    </span>
                                </li>
                            @else
                                <li class="page-item">
                                    <a
                                        class="page-link"
                                        href="{{ $url }}"
                                        aria-label="Ir para a página {{ $page }}"
                                    >
                                        {{ $page }}
                                    </a>
                                </li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{ $paginator->nextPageUrl() }}"
                            rel="next"
                            aria-label="Página seguinte"
                        >
                            <span aria-hidden="true">
                                &rsaquo;
                            </span>
                        </a>
                    </li>
                @else
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                        aria-label="Página seguinte"
                    >
                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            &rsaquo;
                        </span>
                    </li>
                @endif

                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a
                            class="page-link"
                            href="{{
                                $paginator->url(
                                    $paginator->lastPage(),
                                )
                            }}"
                            rel="last"
                            aria-label="Última página"
                        >
                            <span aria-hidden="true">
                                &raquo;
                            </span>
                        </a>
                    </li>
                @else
                    <li
                        class="page-item disabled"
                        aria-disabled="true"
                        aria-label="Última página"
                    >
                        <span
                            class="page-link"
                            aria-hidden="true"
                        >
                            &raquo;
                        </span>
                    </li>
                @endif
            </ul>
        </div>
    </nav>
@endif
