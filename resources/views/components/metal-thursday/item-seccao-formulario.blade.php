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
                            {{ $artista->nome }}
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
        class="row linha-detalhes-seccao linha-detalhes-seccao-incorporacao"
        @if (! $exigeDetalhes)
            hidden
        @endif
    >
        <div class="col-md-6 grupo-campo-formulario mb-3">
            <label
                class="form-label"
                for="{{ $identificadores['ligacao'] }}"
            >
                <strong>
                    Ligação

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
                data-bs-title="Ligação para ouvir o álbum ou a música."
                aria-label="Ajuda sobre a ligação"
            >
                <i
                    class="bi bi-info-circle"
                    aria-hidden="true"
                ></i>
            </button>

            <div class="input-group">
                <input
                    id="{{ $identificadores['ligacao'] }}"
                    class="form-control campo-ligacao @error($chavesErro['ligacao']) is-invalid @enderror"
                    type="url"
                    name="{{ $nomeBaseCampo }}[ligacao]"
                    value="{{ $valores['ligacao'] }}"
                    placeholder="https://..."
                    maxlength="{{ $comprimentoMaximoLigacao }}"
                    inputmode="url"
                    autocomplete="url"
                    aria-describedby="erro-{{ $identificadores['ligacao'] }}"
                    @if ($exigeDetalhes)
                        required
                    @endif
                    @error($chavesErro['ligacao'])
                        aria-invalid="true"
                    @enderror
                >

                <button
                    class="btn btn-secondary botao-testar-incorporacao"
                    type="button"
                    aria-controls="{{
                        $identificadores['resultadosIncorporacao']
                    }}"
                >
                    Testar incorporação
                </button>
            </div>

            <div
                id="erro-{{ $identificadores['ligacao'] }}"
                class="invalid-feedback @error($chavesErro['ligacao']) d-block @enderror"
                aria-live="polite"
                aria-atomic="true"
            >
                @error($chavesErro['ligacao'])
                    {{ $message }}
                @enderror
            </div>

            <input
                id="{{ $identificadores['tipoIncorporacao'] }}"
                class="campo-tipo-incorporacao"
                type="hidden"
                name="{{ $nomeBaseCampo }}[tipo_incorporacao]"
                value="{{ $valores['tipoIncorporacao'] }}"
            >

            <div
                id="erro-{{ $identificadores['tipoIncorporacao'] }}"
                class="invalid-feedback @error($chavesErro['tipoIncorporacao']) d-block @enderror"
                aria-live="polite"
                aria-atomic="true"
            >
                @error($chavesErro['tipoIncorporacao'])
                    {{ $message }}
                @enderror
            </div>

            <div
                id="{{ $identificadores['resultadosIncorporacao'] }}"
                class="resultados-teste-incorporacao mt-3 border-top pt-3"
                hidden
            >
                <div
                    id="{{ $identificadores['estadoTesteIncorporacao'] }}"
                    class="estado-teste-incorporacao small mb-2"
                    aria-live="polite"
                    aria-atomic="true"
                ></div>

                <div
                    class="opcao-incorporacao opcao-incorporacao-video mb-3"
                    hidden
                >
                    <div class="form-check">
                        <input
                            id="{{ $identificadores['escolhaVideo'] }}"
                            class="form-check-input escolha-incorporacao"
                            type="radio"
                            name="escolha_incorporacao_{{ $indice }}"
                            value="{{ $tiposIncorporacao['videoYouTube'] }}"
                            @checked(
                                $valores['tipoIncorporacao']
                                === $tiposIncorporacao['videoYouTube']
                            )
                        >

                        <label
                            class="form-check-label"
                            for="{{ $identificadores['escolhaVideo'] }}"
                        >
                            <strong>
                                Usar como vídeo
                            </strong>
                        </label>
                    </div>

                    <div
                        class="contentor-previsualizacao-incorporacao previsualizacao-video mt-2"
                    ></div>
                </div>

                <div
                    class="opcao-incorporacao opcao-incorporacao-lista-reproducao mb-3"
                    hidden
                >
                    <div class="form-check">
                        <input
                            id="{{
                                $identificadores['escolhaListaReproducao']
                            }}"
                            class="form-check-input escolha-incorporacao"
                            type="radio"
                            name="escolha_incorporacao_{{ $indice }}"
                            value="{{
                                $tiposIncorporacao['listaReproducaoYouTube']
                            }}"
                            @checked(
                                $valores['tipoIncorporacao']
                                ===
                                $tiposIncorporacao['listaReproducaoYouTube']
                            )
                        >

                        <label
                            class="form-check-label"
                            for="{{
                                $identificadores['escolhaListaReproducao']
                            }}"
                        >
                            <strong>
                                Usar como lista de reprodução
                            </strong>
                        </label>
                    </div>

                    <div
                        class="contentor-previsualizacao-incorporacao previsualizacao-lista-reproducao mt-2"
                    ></div>
                </div>

                <div class="opcao-incorporacao opcao-incorporacao-ligacao">
                    <div class="form-check">
                        <input
                            id="{{ $identificadores['escolhaLigacao'] }}"
                            class="form-check-input escolha-incorporacao"
                            type="radio"
                            name="escolha_incorporacao_{{ $indice }}"
                            value="{{ $tiposIncorporacao['ligacao'] }}"
                            @checked(
                                $valores['tipoIncorporacao']
                                === $tiposIncorporacao['ligacao']
                            )
                        >

                        <label
                            class="form-check-label"
                            for="{{ $identificadores['escolhaLigacao'] }}"
                        >
                            Usar como ligação simples
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6 grupo-campo-formulario mb-3">
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
