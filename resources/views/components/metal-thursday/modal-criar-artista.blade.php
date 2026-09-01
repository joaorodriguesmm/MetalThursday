{{--
    Apresenta o formulário modal para criação de um artista sem abandonar
    o formulário principal da MetalThursday.

    As opções, o endereço de submissão e os limites do formulário são
    preparados pela classe
    App\View\Components\MetalThursday\ModalCriarArtista.

    A validação da submissão AJAX é apresentada através dos contentores
    identificados com o atributo data-erro-campo.

    @since 1.0.0
--}}

@can(
    'create',
    App\Models\Musica\Artista::class
)
    <div
        id="modal-criar-artista"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="titulo-modal-criar-artista"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <form
                    id="formulario-criar-artista"
                    method="POST"
                    action="{{ $enderecoGuardarArtista }}"
                    data-ajax-form
                    data-formulario-criar-artista
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
                        <h2
                            id="titulo-modal-criar-artista"
                            class="h5 modal-title"
                        >
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

                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="nome-novo-artista"
                            >
                                Nome

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
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
                                aria-atomic="true"
                                data-erro-campo="nome"
                            ></div>
                        </div>

                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="origem-geografica-novo-artista"
                            >
                                Origem geográfica

                                <span class="fw-normal text-muted">
                                    (opcional)
                                </span>
                            </label>

                            <select
                                id="origem-geografica-novo-artista"
                                class="form-select tom-select-unico"
                                name="origem_geografica_id"
                                placeholder="Seleciona uma origem geográfica"
                                aria-describedby="erro-origem-geografica-novo-artista"
                                data-ordenar-alfabeticamente
                            >
                                <option value="">
                                    Seleciona uma origem geográfica
                                </option>

                                @foreach ($origensGeograficas as $origemGeografica)
                                    <option
                                        value="{{ $origemGeografica['identificador'] }}"
                                    >
                                        {{ $origemGeografica['nome'] }}
                                    </option>
                                @endforeach
                            </select>

                            <div
                                id="erro-origem-geografica-novo-artista"
                                class="invalid-feedback"
                                aria-live="polite"
                                aria-atomic="true"
                                data-erro-campo="origem_geografica_id"
                            ></div>
                        </div>

                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="generos-novo-artista"
                            >
                                Géneros

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
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
                                    required
                                >
                                    @foreach ($generos as $genero)
                                        <option
                                            value="{{ $genero['identificador'] }}"
                                        >
                                            {{ $genero['nome'] }}
                                        </option>
                                    @endforeach
                                </select>

                                @can(
                                    'create',
                                    App\Models\Musica\Genero::class
                                )
                                    <button
                                        class="btn btn-secondary"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#modal-criar-genero"
                                        aria-label="Criar novo género"
                                        title="Criar novo género"
                                    >
                                        <i
                                            class="bi bi-plus-lg"
                                            aria-hidden="true"
                                        ></i>
                                    </button>
                                @endcan
                            </div>

                            <div
                                id="erro-generos-novo-artista"
                                class="invalid-feedback"
                                aria-live="polite"
                                aria-atomic="true"
                                data-erro-campo="generos"
                            ></div>
                        </div>
                    </div>

                    <div class="modal-footer border-secondary">
                        <button
                            class="btn btn-secondary"
                            type="button"
                            data-bs-dismiss="modal"
                        >
                            Cancelar
                        </button>

                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            Criar artista
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
