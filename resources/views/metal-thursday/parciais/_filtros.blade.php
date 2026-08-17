{{--
    Apresenta os controlos de filtragem, ordenação, paginação e alternância
    da vista da listagem de MetalThursdays.

    Os grupos de filtros e os parâmetros dos controlos são preparados pelo
    App\Http\Controllers\MetalThursday\ControladorMetalThursday.

    @since 1.0.0
--}}

<section
    class="card shadow-sm mb-4 bg-dark"
    aria-labelledby="titulo-filtros-ordenacao"
>
    <div class="card-body">
        <h2
            id="titulo-filtros-ordenacao"
            class="h5 card-title mb-3 text-white"
        >
            Filtrar e ordenar
        </h2>

        <form
            id="formulario-filtros-ordenacao"
            method="GET"
            action="{{ route('inicio') }}"
        >
            <input
                id="campo-tipo-vista"
                type="hidden"
                name="{{ $nomeParametroVista }}"
                value="{{ $vistaAtual }}"
            >

            <div class="row g-3 align-items-end">
                <div class="col-12 col-md-3">
                    <label
                        class="form-label small text-muted"
                        for="seletor-adicionar-filtro"
                    >
                        Adicionar filtro
                    </label>

                    <select
                        id="seletor-adicionar-filtro"
                        class="form-select bg-secondary text-white border-secondary"
                        aria-describedby="ajuda-adicionar-filtro"
                    >
                        <option value="">
                            Seleciona um filtro
                        </option>

                        @foreach ($gruposFiltrosDisponiveis as $grupo)
                            <optgroup label="{{ $grupo['rotulo'] }}">
                                @foreach ($grupo['filtros'] as $filtro)
                                    <option value="{{ $filtro['chave'] }}">
                                        {{ $filtro['rotulo'] }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>

                    <div
                        id="ajuda-adicionar-filtro"
                        class="form-text"
                    >
                        O filtro selecionado será adicionado abaixo.
                    </div>
                </div>

                <div class="col-12 col-md-3">
                    <label
                        class="form-label small text-muted"
                        for="seletor-por-pagina"
                    >
                        Resultados por página
                    </label>

                    <select
                        id="seletor-por-pagina"
                        class="form-select bg-secondary text-white border-secondary submissao-automatica"
                        name="{{ $nomeParametroPorPagina }}"
                    >
                        @foreach ($opcoesPorPagina as $opcao)
                            <option
                                value="{{ $opcao }}"
                                @selected($porPagina === $opcao)
                            >
                                {{ $opcao }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label
                        class="form-label small text-muted"
                        for="seletor-ordenacao"
                    >
                        Ordenar por
                    </label>

                    <select
                        id="seletor-ordenacao"
                        class="form-select bg-secondary text-white border-secondary submissao-automatica"
                        name="{{ $nomeParametroOrdenacao }}"
                    >
                        @foreach ($opcoesOrdenacao as $opcao)
                            <option
                                value="{{ $opcao['chave'] }}"
                                @selected(
                                    $ordenacaoAtual
                                    === $opcao['chave']
                                )
                            >
                                {{ $opcao['valor'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 col-md-3">
                    <label
                        class="form-label small text-muted"
                        for="seletor-direcao-ordenacao"
                    >
                        Ordem
                    </label>

                    <select
                        id="seletor-direcao-ordenacao"
                        class="form-select bg-secondary text-white border-secondary submissao-automatica"
                        name="{{ $nomeParametroDirecaoOrdenacao }}"
                    >
                        @foreach ($opcoesDirecaoOrdenacao as $opcao)
                            <option
                                value="{{ $opcao['chave'] }}"
                                @selected(
                                    $direcaoOrdenacaoAtual
                                    === $opcao['chave']
                                )
                            >
                                {{ $opcao['valor'] }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="my-4 border-secondary">

            <div
                id="area-filtros-ativos"
                class="row g-3"
                aria-label="Filtros ativos"
            ></div>

            <hr class="my-4 border-secondary">

            <div
                class="d-grid d-md-flex justify-content-md-between align-items-md-center gap-2"
            >
                <button
                    id="botao-alternar-vista"
                    class="btn btn-primary order-md-1"
                    type="button"
                    data-identificador-campo-vista="campo-tipo-vista"
                    data-vista-completa="{{ $vistaCompleta }}"
                    data-vista-simplificada="{{ $vistaSimplificada }}"
                >
                    {{ $textoBotaoAlternarVista }}
                </button>

                <div class="d-grid d-md-flex gap-2 order-md-2">
                    <button
                        class="btn btn-primary"
                        type="submit"
                    >
                        Aplicar filtros
                    </button>

                    <a
                        class="btn btn-secondary"
                        href="{{ $ligacaoLimparFiltros }}"
                    >
                        Limpar filtros
                    </a>
                </div>
            </div>
        </form>
    </div>
</section>
