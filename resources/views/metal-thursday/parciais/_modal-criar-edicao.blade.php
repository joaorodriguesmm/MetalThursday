{{--
    Apresenta o formulário modal para criação de uma edição sem abandonar
    o formulário principal da MetalThursday.

    O formulário é enviado assincronamente. Os erros de validação são
    associados aos respetivos campos pelo gestor global de formulários AJAX.

    @since 1.0.0
    @version 3.0.0
--}}

@can(
    'create',
    App\Models\MetalThursday\Edicao::class
)
    <div
        id="modal-criar-edicao"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="titulo-modal-criar-edicao"
        aria-hidden="true"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-dark text-white">
                <form
                    id="formulario-criar-edicao"
                    method="POST"
                    action="{{ route('edicoes.guardar') }}"
                    data-ajax-form
                    data-formulario-criar-edicao
                    novalidate
                >
                    @csrf

                    <div class="modal-header border-secondary">
                        <h2
                            id="titulo-modal-criar-edicao"
                            class="h5 modal-title"
                        >
                            Criar nova edição
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
                                for="nome-nova-edicao"
                            >
                                Nome da edição

                                <span
                                    class="text-danger"
                                    aria-hidden="true"
                                >
                                    *
                                </span>
                            </label>

                            <input
                                id="nome-nova-edicao"
                                class="form-control"
                                type="text"
                                name="nome"
                                placeholder="Nome da edição"
                                maxlength="255"
                                aria-describedby="erro-nome-nova-edicao"
                                required
                            >

                            <div
                                id="erro-nome-nova-edicao"
                                class="invalid-feedback"
                                aria-live="polite"
                                data-erro-campo="nome"
                            ></div>
                        </div>

                        <div class="row">
                            <div
                                class="col-md-6 grupo-campo-formulario mb-3"
                                data-grupo-campo
                            >
                                <label
                                    class="form-label"
                                    for="data-inicio-nova-edicao"
                                >
                                    Data de início

                                    <span
                                        class="text-danger"
                                        aria-hidden="true"
                                    >
                                        *
                                    </span>
                                </label>

                                <input
                                    id="data-inicio-nova-edicao"
                                    class="form-control"
                                    type="date"
                                    name="data_inicio"
                                    aria-describedby="erro-data-inicio-nova-edicao"
                                    required
                                >

                                <div
                                    id="erro-data-inicio-nova-edicao"
                                    class="invalid-feedback"
                                    aria-live="polite"
                                    data-erro-campo="data_inicio"
                                ></div>
                            </div>

                            <div
                                class="col-md-6 grupo-campo-formulario mb-3"
                                data-grupo-campo
                            >
                                <label
                                    class="form-label"
                                    for="data-fim-nova-edicao"
                                >
                                    Data de fim
                                </label>

                                <input
                                    id="data-fim-nova-edicao"
                                    class="form-control"
                                    type="date"
                                    name="data_fim"
                                    aria-describedby="ajuda-data-fim-nova-edicao erro-data-fim-nova-edicao"
                                >

                                <div
                                    id="ajuda-data-fim-nova-edicao"
                                    class="form-text"
                                >
                                    Campo opcional.
                                </div>

                                <div
                                    id="erro-data-fim-nova-edicao"
                                    class="invalid-feedback"
                                    aria-live="polite"
                                    data-erro-campo="data_fim"
                                ></div>
                            </div>
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
                            Criar edição
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endcan
