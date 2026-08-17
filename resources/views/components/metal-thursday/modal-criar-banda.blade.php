{{--
    Apresenta o formulário modal para criação de uma banda sem abandonar
    o formulário principal da MetalThursday.

    As opções, o endereço de submissão e os limites do formulário são
    preparados pela classe
    App\View\Components\MetalThursday\ModalCriarBanda.

    A validação da submissão AJAX é apresentada através dos contentores
    identificados com o atributo data-erro-campo.

    @since 1.0.0
--}}

@can(
    'create',
    App\Models\Musica\Banda::class
)
    <div
        id="modal-criar-banda"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="titulo-modal-criar-banda"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <form
                    id="formulario-criar-banda"
                    method="POST"
                    action="{{ $enderecoGuardarBanda }}"
                    data-ajax-form
                    data-formulario-criar-banda
                    data-endereco="{{ $enderecoGuardarBanda }}"
                    data-mensagem-sucesso="Banda criada com sucesso."
                    data-mensagem-erro="Não foi possível criar a banda."
                    novalidate
                >
                    @csrf

                    <div class="modal-header border-secondary">
                        <h2
                            id="titulo-modal-criar-banda"
                            class="h5 modal-title"
                        >
                            Criar nova banda
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
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="nome-nova-banda"
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
                                id="nome-nova-banda"
                                class="form-control"
                                type="text"
                                name="nome"
                                placeholder="Nome da banda"
                                maxlength="{{ $comprimentoMaximoNome }}"
                                aria-describedby="erro-nome-nova-banda"
                                required
                            >

                            <div
                                id="erro-nome-nova-banda"
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
                                for="origem-geografica-nova-banda"
                            >
                                Origem geográfica

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </label>

                            <select
                                id="origem-geografica-nova-banda"
                                class="form-select tom-select-unico"
                                name="origem_geografica_id"
                                placeholder="Seleciona uma origem geográfica"
                                aria-describedby="erro-origem-geografica-nova-banda"
                                required
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
                                id="erro-origem-geografica-nova-banda"
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
                                for="generos-nova-banda"
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
                                    id="generos-nova-banda"
                                    class="form-select tom-select-multiplo"
                                    name="generos[]"
                                    placeholder="Seleciona um ou mais géneros"
                                    aria-describedby="erro-generos-nova-banda"
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
                                id="erro-generos-nova-banda"
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
                            Criar banda
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
