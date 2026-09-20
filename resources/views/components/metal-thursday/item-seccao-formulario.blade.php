{{--
    Apresenta um item repetível do formulário de secções.

    Os valores, identificadores, chaves de erro, tipos de incorporação e
    visibilidade inicial dos detalhes são preparados pela classe
    App\View\Components\MetalThursday\ItemSeccaoFormulario.

    @since 1.0.0
--}}

<section
    {{
        $attributes
            ->except([
                'data-indice-seccao',
            ])
            ->class([
                'item-seccao',
                'border',
                'rounded',
                'p-3',
                'mb-3',
                'bg-light',
                'bg-opacity-10',
                'position-relative',
            ])
    }}
    data-indice-seccao="{{ $indice }}"
>
    <input
        type="hidden"
        name="{{ $nomeBaseCampo }}[id]"
        value="{{ $valores['identificador'] }}"
    >

    <div class="row">
        <div class="col-12 grupo-campo-formulario mb-3">
            <label
                class="form-label"
                for="{{ $identificadores['tipoSeccao'] }}"
            >
                <strong>
                    Tipo de secção

                    <span
                        class="text-danger"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </strong>
            </label>

            <button
                class="btn border-0 bg-transparent text-muted p-0 align-baseline"
                type="button"
                data-bs-toggle="tooltip"
                data-bs-title="Indica se a secção contém apenas texto, um álbum ou uma música."
                aria-label="Ajuda sobre o tipo de secção"
            >
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </button>

            <select
                id="{{ $identificadores['tipoSeccao'] }}"
                class="form-select tom-select-unico seletor-tipo-seccao @error($chavesErro['tipoSeccao']) is-invalid @enderror"
                name="{{ $nomeBaseCampo }}[tipo_seccao_id]"
                placeholder="Seleciona um tipo"
                aria-describedby="erro-{{ $identificadores['tipoSeccao'] }}"
                required
                @error($chavesErro['tipoSeccao'])
                    aria-invalid="true"
                @enderror
            >
                <option value="">
                    Seleciona um tipo
                </option>

                @foreach ($tiposSeccao as $tipoSeccao)
                    <option
                        value="{{ $tipoSeccao->getKey() }}"
                        data-identificador-tipo-seccao="{{ $tipoSeccao->identificador }}"
                        data-exige-detalhes="{{
                            $tipoSeccao->exige_detalhes
                                ? 'true'
                                : 'false'
                        }}"
                        @selected(
                            $valores['tipoSeccao']
                            === (string) $tipoSeccao->getKey()
                        )
                    >
                        {{ $tipoSeccao->nome }}
                    </option>
                @endforeach
            </select>

            <div
                id="erro-{{ $identificadores['tipoSeccao'] }}"
                class="invalid-feedback @error($chavesErro['tipoSeccao']) d-block @enderror"
                aria-live="polite"
                aria-atomic="true"
            >
                @error($chavesErro['tipoSeccao'])
                    {{ $message }}
                @enderror
            </div>
        </div>
    </div>

    <div
        class="row linha-detalhes-seccao linha-detalhes-seccao-principal"
        @if (! $exigeDetalhes)
            hidden
        @endif
    >
        <input
            type="hidden"
            name="{{ $nomeBaseCampo }}[lancamento_id]"
            value="{{ $valores['lancamento'] }}"
        >
        <div class="col-md-6 grupo-campo-formulario mb-3">
            <label
                class="form-label"
                for="{{ $identificadores['artista'] }}"
            >
                <strong>
                    Artista

                    <span
                        class="text-danger"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </strong>
            </label>

            <button
                class="btn border-0 bg-transparent text-muted p-0 align-baseline"
                type="button"
                data-bs-toggle="tooltip"
                data-bs-title="Artista associado ao álbum ou à música."
                aria-label="Ajuda sobre o artista"
            >
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </button>

            <div class="input-group has-validation">
                <select
                    id="{{ $identificadores['artista'] }}"
                    class="form-select tom-select-unico tom-select-artistas @error($chavesErro['artista']) is-invalid @enderror"
                    name="{{ $nomeBaseCampo }}[artista_id]"
                    placeholder="Seleciona um artista"
                    aria-describedby="erro-{{ $identificadores['artista'] }}"
                    @if ($exigeDetalhes)
                        required
                    @endif
                    @error($chavesErro['artista'])
                        aria-invalid="true"
                    @enderror
                >
                    <option value="">
                        Seleciona um artista
                    </option>

                    @foreach ($artistas as $artista)
                        <option
                            value="{{ $artista->getKey() }}"
                            @selected(
                                $valores['artista']
                                === (string) $artista->getKey()
                            )
                        >
                            {{ $artista->obterRotuloSelecao() }}
                        </option>
                    @endforeach
                </select>

                @can(
                    'create',
                    App\Models\Musica\Artista::class
                )
                    <button
                        class="btn btn-secondary"
                        type="button"
                        data-bs-toggle="modal"
                        data-bs-target="#modal-criar-artista"
                        aria-label="Criar novo artista"
                        title="Criar novo artista"
                    >
                        <i
                            class="bi bi-plus-lg"
                            aria-hidden="true"
                        ></i>
                    </button>
                @endcan
            </div>

            <div
                id="erro-{{ $identificadores['artista'] }}"
                class="invalid-feedback @error($chavesErro['artista']) d-block @enderror"
                aria-live="polite"
                aria-atomic="true"
            >
                @error($chavesErro['artista'])
                    {{ $message }}
                @enderror
            </div>

        </div>

        <div
            class="col-md-6 grupo-campo-formulario mb-3"
            data-importacao-lancamento
            data-nome-base-campo="{{ $nomeBaseCampo }}"
            aria-hidden="true"
            hidden
        >
            <label class="form-label">
                <strong>Discogs</strong>

                <span class="badge text-bg-secondary ms-1">
                    Opcional
                </span>
            </label>

            <button
                class="btn border-0 bg-transparent text-muted p-0 align-baseline"
                type="button"
                data-bs-toggle="tooltip"
                data-bs-title="Cola a ligação de uma Release do Discogs (/release/...) para preencher automaticamente os dados do lançamento."
                aria-label="Ajuda sobre o preenchimento pelo Discogs"
            >
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </button>

            <div class="input-group">
                <input
                    class="form-control"
                    type="url"
                    maxlength="2048"
                    autocomplete="url"
                    inputmode="url"
                    placeholder="https://www.discogs.com/release/..."
                    aria-label="Ligação da edição no Discogs"
                    data-ligacao-lancamento-discogs
                >

                <button
                    class="btn btn-secondary d-inline-flex align-items-center justify-content-center gap-2 text-nowrap px-3"
                    type="button"
                    data-acao-importar-lancamento
                >
                    <i
                        class="bi bi-box-arrow-in-down"
                        aria-hidden="true"
                    ></i>

                    Preencher
                </button>
            </div>

            <div
                class="small text-muted mt-2"
                aria-live="polite"
                aria-atomic="true"
                data-estado-importacao-lancamento
            ></div>

            <div
                class="alert alert-success py-2 px-3 mt-2 mb-0"
                role="status"
                data-lancamento-associado
                hidden
            ></div>
        </div>

        <div
            class="col-md-6 grupo-campo-formulario coluna-titulo-seccao mb-3"
        >
            <label
                class="form-label"
                for="{{ $identificadores['titulo'] }}"
            >
                <strong>
                    Título

                    <span
                        class="text-danger indicador-titulo-obrigatorio"
                        aria-hidden="true"
                        @if (! $exigeDetalhes)
                            hidden
                        @endif
                    >
                        *
                    </span>
                </strong>
            </label>

            <button
                class="btn border-0 bg-transparent text-muted p-0 align-baseline"
                type="button"
                data-bs-toggle="tooltip"
                data-bs-title="Título do álbum ou da música."
                aria-label="Ajuda sobre o título"
            >
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </button>

            <input
                id="{{ $identificadores['titulo'] }}"
                class="form-control @error($chavesErro['titulo']) is-invalid @enderror"
                type="text"
                name="{{ $nomeBaseCampo }}[titulo]"
                data-campo-titulo-seccao
                value="{{ $valores['titulo'] }}"
                maxlength="{{ $comprimentoMaximoTitulo }}"
                aria-describedby="erro-{{ $identificadores['titulo'] }}"
                @if ($exigeDetalhes)
                    required
                @endif
                @error($chavesErro['titulo'])
                    aria-invalid="true"
                @enderror
            >

            <div
                id="erro-{{ $identificadores['titulo'] }}"
                class="invalid-feedback @error($chavesErro['titulo']) d-block @enderror"
                aria-live="polite"
                aria-atomic="true"
            >
                @error($chavesErro['titulo'])
                    {{ $message }}
                @enderror
            </div>
        </div>
    </div>

    <div
        class="historico-artista-metal-thursday mb-3"
        hidden
    >
        <div class="small fw-semibold mb-2">
            Aparições anteriores em MetalThursdays
        </div>

        <div
            class="estado-historico-artista-metal-thursday small text-muted"
            aria-live="polite"
            aria-atomic="true"
        ></div>

        <div
            class="lista-historico-artista-metal-thursday"
        ></div>
    </div>

    <div
        class="mb-3"
        data-editor-lancamento
        hidden
    >
        <div class="border rounded p-3 bg-black bg-opacity-10">
            <div class="fw-semibold mb-3">
                Dados do lançamento
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">
                        <strong>Título do lançamento</strong>
                    </label>

                    <input
                        class="form-control"
                        type="text"
                        name="{{ $nomeBaseCampo }}[lancamento][titulo]"
                        value="{{ $dadosLancamento['titulo'] }}"
                        maxlength="{{ $comprimentoMaximoTituloLancamento }}"
                        data-campo-titulo-lancamento
                        required
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        <strong>Tipo</strong>
                    </label>

                    <select
                        class="form-select"
                        name="{{ $nomeBaseCampo }}[lancamento][tipo]"
                        data-campo-tipo-lancamento
                    >
                        <option value="">
                            Não definido
                        </option>

                        @foreach ($tiposLancamento as $tipoLancamento)
                            <option
                                value="{{ $tipoLancamento['valor'] }}"
                                @selected(
                                    $dadosLancamento['tipo']
                                    === $tipoLancamento['valor']
                                )
                            >
                                {{ $tipoLancamento['etiqueta'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        <strong>Ano original</strong>
                    </label>

                    <input
                        class="form-control"
                        type="number"
                        name="{{ $nomeBaseCampo }}[lancamento][ano_original]"
                        value="{{ $dadosLancamento['ano_original'] }}"
                        min="{{ $anoMinimo }}"
                        max="{{ $anoMaximo }}"
                        step="1"
                        inputmode="numeric"
                        data-campo-ano-original-lancamento
                    >

                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-4 mb-2">
                <strong>Tracklist</strong>

                <button
                    class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-2"
                    type="button"
                    data-acao-faixa-lancamento="adicionar"
                >
                    <i
                        class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>

                    Adicionar faixa
                </button>
            </div>

            <div data-lista-faixas-lancamento>
                @foreach ($dadosLancamento['faixas'] as $indiceFaixa => $faixa)
                    <div
                        class="row g-2 align-items-end border rounded p-2 mb-2"
                        data-faixa-lancamento
                    >
                        <input
                            type="hidden"
                            name="{{ $nomeBaseCampo }}[lancamento][faixas][{{ $indiceFaixa }}][id]"
                            value="{{ $faixa['id'] }}"
                            data-campo-id-faixa
                        >

                        <input
                            type="hidden"
                            name="{{ $nomeBaseCampo }}[lancamento][faixas][{{ $indiceFaixa }}][musica_id]"
                            value="{{ $faixa['musica_id'] }}"
                            data-campo-musica-id-faixa
                        >

                        <input
                            type="hidden"
                            name="{{ $nomeBaseCampo }}[lancamento][faixas][{{ $indiceFaixa }}][ordem]"
                            value="{{ $indiceFaixa + 1 }}"
                            data-campo-ordem-faixa
                        >

                        <div class="col-sm-2">
                            <label class="form-label small mb-1">
                                Posição
                            </label>

                            <input
                                class="form-control form-control-sm"
                                type="text"
                                name="{{ $nomeBaseCampo }}[lancamento][faixas][{{ $indiceFaixa }}][posicao]"
                                value="{{ $faixa['posicao'] }}"
                                maxlength="100"
                                data-campo-posicao-faixa
                            >
                        </div>

                        <div class="col">
                            <label class="form-label small mb-1">
                                Título
                            </label>

                            <input
                                class="form-control form-control-sm"
                                type="text"
                                name="{{ $nomeBaseCampo }}[lancamento][faixas][{{ $indiceFaixa }}][titulo]"
                                value="{{ $faixa['titulo'] }}"
                                maxlength="255"
                                data-campo-titulo-faixa
                                required
                            >
                        </div>

                        <div class="col-auto d-flex gap-1 align-items-center">
                            <span
                                class="badge text-bg-secondary align-self-center me-1"
                                data-ordem-faixa-lancamento
                            >
                                {{ $indiceFaixa + 1 }}
                            </span>

                            <button
                                class="btn btn-sm btn-secondary"
                                type="button"
                                data-acao-faixa-lancamento="subir"
                                aria-label="Subir faixa"
                                title="Subir faixa"
                                @disabled($loop->first)
                            >
                                <i class="bi bi-arrow-up" aria-hidden="true"></i>
                            </button>

                            <button
                                class="btn btn-sm btn-secondary"
                                type="button"
                                data-acao-faixa-lancamento="descer"
                                aria-label="Descer faixa"
                                title="Descer faixa"
                                @disabled($loop->last)
                            >
                                <i class="bi bi-arrow-down" aria-hidden="true"></i>
                            </button>

                            <button
                                class="btn btn-sm btn-danger"
                                type="button"
                                data-acao-faixa-lancamento="remover"
                                aria-label="Remover faixa"
                                title="Remover faixa"
                            >
                                <i class="bi bi-trash" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div
        class="row linha-detalhes-seccao linha-detalhes-seccao-incorporacao"
        @if (! $exigeDetalhes)
            hidden
        @endif
    >
        <div
            class="col-12 grupo-campo-formulario mb-3"
            data-editor-ligacoes-seccao
            data-nome-base-campo="{{ $nomeBaseCampo }}"
            data-maximo-ligacoes="{{ $numeroMaximoLigacoes }}"
        >
            <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mb-2">
                <div>
                    <strong>Ligações</strong>

                    <span class="badge text-bg-secondary ms-1">
                        Opcional
                    </span>

                    <button
                        class="btn border-0 bg-transparent text-muted p-0 align-baseline ms-1"
                        type="button"
                        data-bs-toggle="tooltip"
                        data-bs-title="Podes adicionar várias ligações. Spotify, Apple Music e YouTube são detetados automaticamente; as restantes usam uma etiqueta personalizada."
                        aria-label="Ajuda sobre as ligações"
                    >
                        <i
                            class="bi bi-info-circle"
                            aria-hidden="true"
                        ></i>
                    </button>
                </div>

                <button
                    class="btn btn-sm btn-secondary d-inline-flex align-items-center gap-2"
                    type="button"
                    data-acao-ligacao-seccao="adicionar"
                >
                    <i
                        class="bi bi-plus-lg"
                        aria-hidden="true"
                    ></i>

                    Adicionar ligação
                </button>
            </div>

            <div
                class="small text-muted mb-3"
                data-estado-ligacoes-seccao
                aria-live="polite"
                aria-atomic="true"
            >
                {{ count($ligacoes) }} de {{ $numeroMaximoLigacoes }} ligações.
            </div>

            <div data-lista-ligacoes-seccao>
                @foreach ($ligacoes as $indiceLigacao => $ligacao)
                    @php
                        $idBaseLigacao = "seccoes-{$indice}-ligacao-{$indiceLigacao}";
                        $chaveUrlLigacao = "seccoes.{$indice}.ligacoes.{$indiceLigacao}.url";
                        $chaveEtiquetaLigacao = "seccoes.{$indice}.ligacoes.{$indiceLigacao}.etiqueta";
                    @endphp

                    <div
                        class="border rounded p-3 mb-2"
                        data-ligacao-seccao
                        data-indice-ligacao="{{ $indiceLigacao }}"
                    >
                        <div class="row g-3 align-items-start">
                            <div class="col-xl-5 col-lg-6">
                                <label
                                    class="form-label small mb-1"
                                    for="{{ $idBaseLigacao }}-url"
                                    data-etiqueta-url-ligacao
                                >
                                    URL
                                </label>

                                <input
                                    id="{{ $idBaseLigacao }}-url"
                                    class="form-control @error($chaveUrlLigacao) is-invalid @enderror"
                                    type="url"
                                    name="{{ $nomeBaseCampo }}[ligacoes][{{ $indiceLigacao }}][url]"
                                    value="{{ $ligacao['url'] }}"
                                    placeholder="https://..."
                                    maxlength="{{ $comprimentoMaximoLigacao }}"
                                    inputmode="url"
                                    autocomplete="url"
                                    aria-describedby="{{ $idBaseLigacao }}-erro-url"
                                    data-campo-url-ligacao
                                    required
                                    @error($chaveUrlLigacao)
                                        aria-invalid="true"
                                    @enderror
                                >

                                <div
                                    id="{{ $idBaseLigacao }}-erro-url"
                                    class="invalid-feedback @error($chaveUrlLigacao) d-block @enderror"
                                    data-erro-url-ligacao
                                    aria-live="polite"
                                    aria-atomic="true"
                                >
                                    @error($chaveUrlLigacao)
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>

                            <div
                                class="col-xl-3 col-lg-6"
                                data-contentor-etiqueta-ligacao
                                hidden
                            >
                                <label
                                    class="form-label small mb-1"
                                    for="{{ $idBaseLigacao }}-etiqueta"
                                    data-etiqueta-etiqueta-ligacao
                                >
                                    Etiqueta
                                </label>

                                <input
                                    id="{{ $idBaseLigacao }}-etiqueta"
                                    class="form-control @error($chaveEtiquetaLigacao) is-invalid @enderror"
                                    type="text"
                                    name="{{ $nomeBaseCampo }}[ligacoes][{{ $indiceLigacao }}][etiqueta]"
                                    value="{{ $ligacao['etiqueta'] }}"
                                    maxlength="{{ $comprimentoMaximoEtiquetaLigacao }}"
                                    placeholder="Ex.: Comprar, Crítica, Site oficial"
                                    aria-describedby="{{ $idBaseLigacao }}-erro-etiqueta"
                                    data-campo-etiqueta-ligacao
                                    @error($chaveEtiquetaLigacao)
                                        aria-invalid="true"
                                    @enderror
                                >

                                <div
                                    id="{{ $idBaseLigacao }}-erro-etiqueta"
                                    class="invalid-feedback @error($chaveEtiquetaLigacao) d-block @enderror"
                                    data-erro-etiqueta-ligacao
                                    aria-live="polite"
                                    aria-atomic="true"
                                >
                                    @error($chaveEtiquetaLigacao)
                                        {{ $message }}
                                    @enderror
                                </div>
                            </div>

                            <div class="col-md-3 col-xl-2">
                                <div class="form-label small mb-1">
                                    Plataforma
                                </div>

                                <span
                                    class="badge text-bg-secondary"
                                    data-plataforma-ligacao
                                >
                                    Por detetar
                                </span>
                            </div>

                            <div class="col-md-5 col-xl-2">
                                <input
                                    type="hidden"
                                    name="{{ $nomeBaseCampo }}[ligacoes][{{ $indiceLigacao }}][incorporar]"
                                    value="0"
                                    data-campo-incorporar-ligacao-oculto
                                >

                                <div class="form-check form-switch mb-1">
                                    <input
                                        id="{{ $idBaseLigacao }}-incorporar"
                                        class="form-check-input"
                                        type="checkbox"
                                        name="{{ $nomeBaseCampo }}[ligacoes][{{ $indiceLigacao }}][incorporar]"
                                        value="1"
                                        data-campo-incorporar-ligacao
                                        @checked($ligacao['incorporar'])
                                    >

                                    <label
                                        class="form-check-label"
                                        for="{{ $idBaseLigacao }}-incorporar"
                                        data-etiqueta-incorporar-ligacao
                                    >
                                        Incorporar
                                    </label>
                                </div>

                                <div class="small text-muted">
                                    Quando suportado.
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end align-items-center gap-1 mt-3">
                            <button
                                class="btn btn-sm btn-secondary"
                                type="button"
                                data-acao-ligacao-seccao="subir"
                                aria-label="Subir ligação"
                                title="Subir ligação"
                                @disabled($loop->first)
                            >
                                <i
                                    class="bi bi-arrow-up"
                                    aria-hidden="true"
                                ></i>
                            </button>

                            <button
                                class="btn btn-sm btn-secondary"
                                type="button"
                                data-acao-ligacao-seccao="descer"
                                aria-label="Descer ligação"
                                title="Descer ligação"
                                @disabled($loop->last)
                            >
                                <i
                                    class="bi bi-arrow-down"
                                    aria-hidden="true"
                                ></i>
                            </button>

                            <button
                                class="btn btn-sm btn-danger"
                                type="button"
                                data-acao-ligacao-seccao="remover"
                                aria-label="Remover ligação"
                                title="Remover ligação"
                            >
                                <i
                                    class="bi bi-trash"
                                    aria-hidden="true"
                                ></i>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <template data-modelo-ligacao-seccao>
                <div
                    class="border rounded p-3 mb-2"
                    data-ligacao-seccao
                    data-indice-ligacao="__INDICE_LIGACAO__"
                >
                    <div class="row g-3 align-items-start">
                        <div class="col-xl-5 col-lg-6">
                            <label
                                class="form-label small mb-1"
                                for="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-url"
                                data-etiqueta-url-ligacao
                            >
                                URL
                            </label>

                            <input
                                id="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-url"
                                class="form-control"
                                type="url"
                                name="{{ $nomeBaseCampo }}[ligacoes][__INDICE_LIGACAO__][url]"
                                value=""
                                placeholder="https://..."
                                maxlength="{{ $comprimentoMaximoLigacao }}"
                                inputmode="url"
                                autocomplete="url"
                                aria-describedby="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-erro-url"
                                data-campo-url-ligacao
                                required
                            >

                            <div
                                id="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-erro-url"
                                class="invalid-feedback"
                                data-erro-url-ligacao
                                aria-live="polite"
                                aria-atomic="true"
                            ></div>
                        </div>

                        <div
                            class="col-xl-3 col-lg-6"
                            data-contentor-etiqueta-ligacao
                            hidden
                        >
                            <label
                                class="form-label small mb-1"
                                for="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-etiqueta"
                                data-etiqueta-etiqueta-ligacao
                            >
                                Etiqueta
                            </label>

                            <input
                                id="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-etiqueta"
                                class="form-control"
                                type="text"
                                name="{{ $nomeBaseCampo }}[ligacoes][__INDICE_LIGACAO__][etiqueta]"
                                value=""
                                maxlength="{{ $comprimentoMaximoEtiquetaLigacao }}"
                                placeholder="Ex.: Comprar, Crítica, Site oficial"
                                aria-describedby="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-erro-etiqueta"
                                data-campo-etiqueta-ligacao
                            >

                            <div
                                id="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-erro-etiqueta"
                                class="invalid-feedback"
                                data-erro-etiqueta-ligacao
                                aria-live="polite"
                                aria-atomic="true"
                            ></div>
                        </div>

                        <div class="col-md-3 col-xl-2">
                            <div class="form-label small mb-1">
                                Plataforma
                            </div>

                            <span
                                class="badge text-bg-secondary"
                                data-plataforma-ligacao
                            >
                                Por detetar
                            </span>
                        </div>

                        <div class="col-md-5 col-xl-2">
                            <input
                                type="hidden"
                                name="{{ $nomeBaseCampo }}[ligacoes][__INDICE_LIGACAO__][incorporar]"
                                value="0"
                                data-campo-incorporar-ligacao-oculto
                            >

                            <div class="form-check form-switch mb-1">
                                <input
                                    id="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-incorporar"
                                    class="form-check-input"
                                    type="checkbox"
                                    name="{{ $nomeBaseCampo }}[ligacoes][__INDICE_LIGACAO__][incorporar]"
                                    value="1"
                                    data-campo-incorporar-ligacao
                                >

                                <label
                                    class="form-check-label"
                                    for="seccoes-{{ $indice }}-ligacao-__INDICE_LIGACAO__-incorporar"
                                    data-etiqueta-incorporar-ligacao
                                >
                                    Incorporar
                                </label>
                            </div>

                            <div class="small text-muted">
                                Quando suportado.
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-1 mt-3">
                        <button
                            class="btn btn-sm btn-secondary"
                            type="button"
                            data-acao-ligacao-seccao="subir"
                            aria-label="Subir ligação"
                            title="Subir ligação"
                        >
                            <i
                                class="bi bi-arrow-up"
                                aria-hidden="true"
                            ></i>
                        </button>

                        <button
                            class="btn btn-sm btn-secondary"
                            type="button"
                            data-acao-ligacao-seccao="descer"
                            aria-label="Descer ligação"
                            title="Descer ligação"
                        >
                            <i
                                class="bi bi-arrow-down"
                                aria-hidden="true"
                            ></i>
                        </button>

                        <button
                            class="btn btn-sm btn-danger"
                            type="button"
                            data-acao-ligacao-seccao="remover"
                            aria-label="Remover ligação"
                            title="Remover ligação"
                        >
                            <i
                                class="bi bi-trash"
                                aria-hidden="true"
                            ></i>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div class="col-12 grupo-campo-formulario coluna-ano-seccao mb-3">
            <label
                class="form-label"
                for="{{ $identificadores['ano'] }}"
            >
                <strong>
                    Ano

                    <span
                        class="text-danger"
                        aria-hidden="true"
                    >
                        *
                    </span>
                </strong>
            </label>

            <button
                class="btn border-0 bg-transparent text-muted p-0 align-baseline"
                type="button"
                data-bs-toggle="tooltip"
                data-bs-title="Ano de lançamento do álbum ou da música."
                aria-label="Ajuda sobre o ano"
            >
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </button>

            <input
                id="{{ $identificadores['ano'] }}"
                class="form-control @error($chavesErro['ano']) is-invalid @enderror"
                type="number"
                name="{{ $nomeBaseCampo }}[ano]"
                value="{{ $valores['ano'] }}"
                data-campo-ano-seccao
                min="{{ $anoMinimo }}"
                max="{{ $anoMaximo }}"
                step="1"
                inputmode="numeric"
                aria-describedby="erro-{{ $identificadores['ano'] }}"
                @if ($exigeDetalhes)
                    required
                @endif
                @error($chavesErro['ano'])
                    aria-invalid="true"
                @enderror
            >

            <div
                id="erro-{{ $identificadores['ano'] }}"
                class="invalid-feedback @error($chavesErro['ano']) d-block @enderror"
                aria-live="polite"
                aria-atomic="true"
            >
                @error($chavesErro['ano'])
                    {{ $message }}
                @enderror
            </div>
        </div>
    </div>

    <div class="grupo-campo-formulario mb-3">
        <label
            class="form-label"
            for="{{ $identificadores['descricao'] }}"
        >
            <strong>
                Descrição

                <span
                    class="text-danger"
                    aria-hidden="true"
                >
                    *
                </span>
            </strong>
        </label>

        <button
            class="btn border-0 bg-transparent text-muted p-0 align-baseline"
            type="button"
            data-bs-toggle="tooltip"
            data-bs-title="Descrição da secção."
            aria-label="Ajuda sobre a descrição"
        >
            <i
                class="bi bi-info-circle"
                aria-hidden="true"
            ></i>
        </button>

        <textarea
            id="{{ $identificadores['descricao'] }}"
            class="form-control @error($chavesErro['descricao']) is-invalid @enderror"
            name="{{ $nomeBaseCampo }}[descricao]"
            rows="3"
            maxlength="{{ $comprimentoMaximoDescricao }}"
            aria-describedby="erro-{{ $identificadores['descricao'] }}"
            required
            @error($chavesErro['descricao'])
                aria-invalid="true"
            @enderror
        >{{ $valores['descricao'] }}</textarea>

        <div
            id="erro-{{ $identificadores['descricao'] }}"
            class="invalid-feedback @error($chavesErro['descricao']) d-block @enderror"
            aria-live="polite"
            aria-atomic="true"
        >
            @error($chavesErro['descricao'])
                {{ $message }}
            @enderror
        </div>
    </div>

    <button
        class="btn btn-sm btn-danger botao-remover-seccao"
        type="button"
        aria-label="Remover esta secção"
    >
        Remover secção
    </button>
</section>
