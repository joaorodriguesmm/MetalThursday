{{--
    Apresenta o formulário modal para criação de um artista sem abandonar
    o formulário principal da MetalThursday.

    Os campos essenciais permanecem sempre visíveis. Os restantes metadados
    são apresentados apenas a pedido do utilizador.

    @since 1.0.0
--}}

@can('create', App\Models\Musica\Artista::class)
    <div
        id="modal-criar-artista"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="titulo-modal-criar-artista"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form
                id="formulario-criar-artista"
                class="modal-content bg-dark text-white"
                method="POST"
                action="{{ $enderecoGuardarArtista }}"
                autocomplete="off"
                data-ajax-form
                data-formulario-criar-artista
                data-formulario-perfil-artista
                data-endereco="{{ $enderecoGuardarArtista }}"
                data-mensagem-sucesso="Artista criado com sucesso."
                data-mensagem-erro="Não foi possível criar o artista."
                novalidate
            >
                @csrf

                <input
                    type="hidden"
                    name="confirmar_nome_repetido"
                    value="1"
                    disabled
                >

                <div class="modal-header border-secondary">
                    <h2 id="titulo-modal-criar-artista" class="h5 modal-title">
                        Criar novo artista
                    </h2>

                    <button
                        class="btn-close btn-close-white"
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body">
                    <div
                        class="aviso-artista-homonimo"
                        role="alert"
                        aria-live="polite"
                        data-confirmacao-nome-repetido
                        hidden
                    >
                        <div class="aviso-artista-homonimo__cabecalho">
                            <i
                                class="bi bi-exclamation-triangle-fill aviso-artista-homonimo__icone"
                                aria-hidden="true"
                            ></i>

                            <div>
                                <div class="aviso-artista-homonimo__titulo">
                                    Artista com o mesmo nome
                                </div>

                                <p
                                    class="aviso-artista-homonimo__mensagem"
                                    data-mensagem-confirmacao-nome-repetido
                                ></p>
                            </div>
                        </div>

                        <div
                            class="aviso-artista-homonimo__lista"
                            data-lista-artistas-homonimos
                        ></div>

                        <p class="aviso-artista-homonimo__nota">
                            Se for um artista diferente, volta a confirmar a criação.
                        </p>
                    </div>

                    <div class="grupo-campo-formulario mb-3" data-grupo-campo>
                        <label class="form-label" for="nome-novo-artista">
                            Nome
                            <span class="text-danger" aria-hidden="true">*</span>
                        </label>

                        <input
                            id="nome-novo-artista"
                            class="form-control"
                            type="text"
                            name="nome"
                            placeholder="Nome do artista"
                            maxlength="{{ $comprimentoMaximoNome }}"
                            aria-describedby="erro-nome-novo-artista"
                            required
                        >

                        <div
                            id="erro-nome-novo-artista"
                            class="invalid-feedback"
                            aria-live="polite"
                            data-erro-campo="nome"
                        ></div>
                    </div>

                    <div class="grupo-campo-formulario mb-3" data-grupo-campo>
                        <label class="form-label" for="origem-geografica-novo-artista">
                            Origem geográfica
                            <span class="fw-normal text-muted">(opcional)</span>
                        </label>

                        <select
                            id="origem-geografica-novo-artista"
                            class="form-select tom-select-unico"
                            name="origem_geografica_id"
                            placeholder="Seleciona uma origem geográfica"
                            aria-describedby="erro-origem-geografica-novo-artista"
                            data-ordenar-alfabeticamente
                        >
                            <option value="">Seleciona uma origem geográfica</option>

                            @foreach ($origensGeograficas as $origemGeografica)
                                <option value="{{ $origemGeografica['identificador'] }}">
                                    {{ $origemGeografica['nome'] }}
                                </option>
                            @endforeach
                        </select>

                        <div
                            id="erro-origem-geografica-novo-artista"
                            class="invalid-feedback"
                            aria-live="polite"
                            data-erro-campo="origem_geografica_id"
                        ></div>
                    </div>

                    <div class="grupo-campo-formulario mb-3" data-grupo-campo>
                        <label class="form-label" for="generos-novo-artista">
                            Géneros
                            <span class="fw-normal text-muted">(opcional)</span>
                        </label>

                        <div class="input-group has-validation">
                            <select
                                id="generos-novo-artista"
                                class="form-select tom-select-multiplo"
                                name="generos[]"
                                placeholder="Seleciona um ou mais géneros"
                                aria-describedby="erro-generos-novo-artista"
                                data-ordenar-alfabeticamente
                                multiple
                            >
                                @foreach ($generos as $genero)
                                    <option value="{{ $genero['identificador'] }}">
                                        {{ $genero['nome'] }}
                                    </option>
                                @endforeach
                            </select>

                            @can('create', App\Models\Musica\Genero::class)
                                <button
                                    class="btn btn-secondary"
                                    type="button"
                                    data-bs-toggle="modal"
                                    data-bs-target="#modal-criar-genero"
                                    aria-label="Criar novo género"
                                    title="Criar novo género"
                                >
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                </button>
                            @endcan
                        </div>

                        <div
                            id="erro-generos-novo-artista"
                            class="invalid-feedback"
                            aria-live="polite"
                            data-erro-campo="generos"
                        ></div>
                    </div>

                    <div class="mb-3">
                        <button
                            class="btn btn-sm btn-secondary"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#campos-adicionais-novo-artista"
                            aria-expanded="false"
                            aria-controls="campos-adicionais-novo-artista"
                            data-alternar-campos-adicionais
                            data-texto-apresentar="Apresentar campos adicionais"
                            data-texto-ocultar="Ocultar campos adicionais"
                        >
                            Apresentar campos adicionais
                        </button>
                    </div>

                    <div
                        id="campos-adicionais-novo-artista"
                        class="collapse"
                        data-campos-adicionais-artista
                    >
                        <div class="rounded-3 border border-secondary bg-black bg-opacity-10 p-3 p-lg-4 mb-3">
                            @include(
                                'musica.artistas._importacao',
                                [
                                    'identificadorMusicBrainzImportacao' => '',
                                    'identificadorDiscogsImportacao' => '',
                                ]
                            )

                            <div class="row">
                                <div class="col-md-4 grupo-campo-formulario mb-3" data-grupo-campo>
                                    <label class="form-label" for="ano-inicio-novo-artista">
                                        Ano de início
                                        <span class="fw-normal text-muted">(opcional)</span>
                                    </label>

                                    <input
                                        id="ano-inicio-novo-artista"
                                        class="form-control"
                                        type="number"
                                        name="ano_inicio_atividade"
                                        min="{{ App\Models\Musica\Artista::ANO_MINIMO_ATIVIDADE }}"
                                        max="{{ date('Y') }}"
                                        step="1"
                                        data-erro-campo="ano_inicio_atividade"
                                    >
                                </div>

                                <div class="col-md-4 grupo-campo-formulario mb-3" data-grupo-campo>
                                    <label class="form-label" for="ano-fim-novo-artista">
                                        Ano de fim
                                        <span class="fw-normal text-muted">(opcional)</span>
                                    </label>

                                    <input
                                        id="ano-fim-novo-artista"
                                        class="form-control"
                                        type="number"
                                        name="ano_fim_atividade"
                                        min="{{ App\Models\Musica\Artista::ANO_MINIMO_ATIVIDADE }}"
                                        max="{{ date('Y') }}"
                                        step="1"
                                    >

                                    <div class="invalid-feedback" data-erro-campo="ano_fim_atividade"></div>
                                </div>

                                <div class="col-md-4 grupo-campo-formulario mb-3" data-grupo-campo>
                                    <label class="form-label" for="estado-atividade-novo-artista">
                                        Estado de atividade
                                        <span class="fw-normal text-muted">(opcional)</span>
                                    </label>

                                    <select
                                        id="estado-atividade-novo-artista"
                                        class="form-select"
                                        name="estado_atividade"
                                    >
                                        <option value="">Não indicado</option>

                                        @foreach (App\Enumeracoes\EstadoAtividadeArtista::cases() as $estadoAtividade)
                                            <option value="{{ $estadoAtividade->value }}">
                                                {{ $estadoAtividade->etiqueta() }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <div class="invalid-feedback" data-erro-campo="estado_atividade"></div>
                                </div>
                            </div>

                            <div class="grupo-campo-formulario mb-3" data-grupo-campo>
                                <label class="form-label" for="biografia-novo-artista">
                                    Biografia
                                    <span class="fw-normal text-muted">(opcional)</span>
                                </label>

                                <textarea
                                    id="biografia-novo-artista"
                                    class="form-control"
                                    name="biografia"
                                    rows="5"
                                    maxlength="{{ App\Models\Musica\Artista::COMPRIMENTO_MAXIMO_BIOGRAFIA }}"
                                ></textarea>

                                <div class="invalid-feedback" data-erro-campo="biografia"></div>
                            </div>

                            <div class="grupo-campo-formulario mb-3" data-grupo-campo>
                                <label class="form-label" for="imagem-novo-artista">
                                    Imagem
                                    <span class="fw-normal text-muted">(URL externa, opcional)</span>
                                </label>

                                <input
                                    id="imagem-novo-artista"
                                    class="form-control"
                                    type="url"
                                    name="imagem"
                                    maxlength="{{ App\Models\Musica\Artista::COMPRIMENTO_MAXIMO_URL_IMAGEM }}"
                                    placeholder="https://exemplo.com/imagem.jpg"
                                    inputmode="url"
                                >

                                <div class="form-text">
                                    A imagem não é carregada nem guardada no servidor do MetalThursday.
                                </div>

                                <div class="invalid-feedback" data-erro-campo="imagem"></div>
                            </div>

                            <section class="rounded-3 border border-secondary bg-black bg-opacity-25 p-3">
                                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                                    <div>
                                        <h3 class="h6 mb-0">Ligações</h3>
                                        <p class="small text-muted mb-0">
                                            Site oficial, Bandcamp, redes sociais ou outras páginas relevantes.
                                        </p>
                                    </div>

                                    <button
                                        class="btn btn-sm btn-secondary"
                                        type="button"
                                        data-adicionar-ligacao
                                    >
                                        <i class="bi bi-plus-lg" aria-hidden="true"></i>
                                        Adicionar ligação
                                    </button>
                                </div>

                                <div data-lista-ligacoes>
                                    <div class="row g-2 mb-2" data-ligacao>
                                        <div class="col-md-4">
                                            <input
                                                class="form-control"
                                                type="text"
                                                name="ligacoes[0][titulo]"
                                                maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_TITULO }}"
                                                placeholder="Ex.: Site oficial"
                                                data-campo-ligacao="titulo"
                                            >
                                        </div>

                                        <div class="col-md-7">
                                            <input
                                                class="form-control"
                                                type="url"
                                                name="ligacoes[0][url]"
                                                maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_URL }}"
                                                placeholder="https://..."
                                                inputmode="url"
                                                data-campo-ligacao="url"
                                            >
                                        </div>

                                        <div class="col-md-1 d-grid">
                                            <button
                                                class="btn btn-outline-danger"
                                                type="button"
                                                data-remover-ligacao
                                                aria-label="Remover ligação"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <template data-modelo-ligacao>
                                    <div class="row g-2 mb-2" data-ligacao>
                                        <div class="col-md-4">
                                            <input
                                                class="form-control"
                                                type="text"
                                                name="ligacoes[__INDICE__][titulo]"
                                                maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_TITULO }}"
                                                placeholder="Ex.: Site oficial"
                                                data-campo-ligacao="titulo"
                                            >
                                        </div>

                                        <div class="col-md-7">
                                            <input
                                                class="form-control"
                                                type="url"
                                                name="ligacoes[__INDICE__][url]"
                                                maxlength="{{ App\Models\Comum\Ligacao::COMPRIMENTO_MAXIMO_URL }}"
                                                placeholder="https://..."
                                                inputmode="url"
                                                data-campo-ligacao="url"
                                            >
                                        </div>

                                        <div class="col-md-1 d-grid">
                                            <button
                                                class="btn btn-outline-danger"
                                                type="button"
                                                data-remover-ligacao
                                                aria-label="Remover ligação"
                                            >
                                                <i class="bi bi-trash" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-secondary">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">
                        Cancelar
                    </button>

                    <button class="btn btn-primary" type="submit">
                        Criar artista
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts-pagina')
        @vite('resources/js/paginas/perfilArtista.js')
    @endpush
@endcan
