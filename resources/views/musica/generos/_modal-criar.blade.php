{{--
    Apresenta o formulário modal reutilizável para criação de um género
    musical sem abandonar o formulário principal.

    O formulário é enviado assincronamente. Os erros de validação são
    associados aos respetivos campos pelo gestor de formulários AJAX.

    O campo oculto dos géneros pais garante que é enviada uma lista vazia
    quando não é selecionada qualquer relação hierárquica.

    @since 1.0.0
--}}

@can(
    'create',
    App\Models\Musica\Genero::class
)
    <div
        id="modal-criar-genero"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="titulo-modal-criar-genero"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <form
                    id="formulario-criar-genero"
                    method="POST"
                    action="{{ route('generos.guardar') }}"
                    autocomplete="off"
                    data-ajax-form
                    data-formulario-criar-genero
                    data-mensagem-sucesso="Género criado com sucesso."
                    data-mensagem-erro="Não foi possível criar o género."
                    novalidate
                >
                    @csrf

                    <div class="modal-header border-secondary">
                        <h2
                            id="titulo-modal-criar-genero"
                            class="h5 modal-title"
                        >
                            Criar novo género
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
                                for="nome-novo-genero"
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
                                id="nome-novo-genero"
                                class="form-control"
                                type="text"
                                name="nome"
                                placeholder="Nome do género"
                                maxlength="{{ App\Models\Musica\Genero::COMPRIMENTO_MAXIMO_NOME }}"
                                autocomplete="off"
                                aria-describedby="erro-nome-novo-genero"
                                required
                            >

                            <div
                                id="erro-nome-novo-genero"
                                class="invalid-feedback"
                                aria-live="polite"
                                data-erro-campo="nome"
                            ></div>
                        </div>

                        <div
                            class="grupo-campo-formulario mb-3"
                            data-grupo-campo
                        >
                            <label
                                class="form-label"
                                for="generos-pai-novo-genero"
                            >
                                Géneros pais

                                <span class="fw-normal text-muted">
                                    (opcional)
                                </span>
                            </label>

                            <input
                                type="hidden"
                                name="generos_pai[]"
                                value=""
                            >

                            <select
                                id="generos-pai-novo-genero"
                                class="form-select tom-select-multiplo"
                                name="generos_pai[]"
                                placeholder="Seleciona um ou mais géneros pais"
                                aria-describedby="ajuda-generos-pai-novo-genero erro-generos-pai-novo-genero"
                                autocomplete="off"
                                data-ordenar-alfabeticamente
                                multiple
                            >
                                @foreach ($generos as $genero)
                                    <option value="{{ $genero->getKey() }}">
                                        {{ $genero->nome }}
                                    </option>
                                @endforeach
                            </select>

                            <div
                                id="ajuda-generos-pai-novo-genero"
                                class="form-text"
                            >
                                Seleciona os géneros dos quais este género
                                deriva diretamente.
                            </div>

                            <div
                                id="erro-generos-pai-novo-genero"
                                class="invalid-feedback"
                                aria-live="polite"
                                data-erro-campo="generos_pai"
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
                            Criar género
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
