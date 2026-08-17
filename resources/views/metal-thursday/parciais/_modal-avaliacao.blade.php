{{--
    Apresenta o formulário modal para avaliar uma MetalThursday ou uma
    das respetivas secções.

    O elemento avaliado, o endereço de submissão e a pontuação atual são
    preenchidos dinamicamente pelo gestor JavaScript do modal.

    A lista de valores inteiros representa as dez estrelas disponíveis.
    Cada estrela permite selecionar também o respetivo meio valor.

    @since 1.0.0
--}}

<div
    id="modal-avaliacao"
    class="modal fade"
    tabindex="-1"
    aria-labelledby="titulo-modal-avaliacao"
    aria-hidden="true"
>
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <form
                id="formulario-avaliacao"
                method="POST"
                action=""
                data-formulario-avaliacao
                data-mensagem-sucesso="Avaliação guardada com sucesso."
                data-mensagem-erro="Não foi possível guardar a avaliação."
                novalidate
            >
                @csrf

                <div class="modal-header border-secondary">
                    <h2
                        id="titulo-modal-avaliacao"
                        class="h5 modal-title"
                    >
                        Guardar avaliação
                    </h2>

                    <button
                        class="btn-close btn-close-white"
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body">
                    <p class="mb-3">
                        Estás a avaliar:

                        <strong data-nome-avaliavel>
                            elemento selecionado
                        </strong>
                    </p>

                    <div
                        class="grupo-campo-formulario"
                        data-grupo-campo
                    >
                        <div
                            class="estrelas-avaliacao-interativas"
                            data-estrelas-avaliacao
                            aria-label="Selecionar pontuação"
                        >
                            @foreach ($pontuacoesDisponiveis as $pontuacao)
                                <button
                                    class="btn btn-link border-0 p-0 text-decoration-none bi bi-star fs-3"
                                    type="button"
                                    data-valor="{{ $pontuacao }}"
                                    aria-label="Selecionar até {{ $pontuacao }} em {{ $pontuacaoMaxima }}"
                                    title="{{ $pontuacao }} em {{ $pontuacaoMaxima }}"
                                ></button>
                            @endforeach
                        </div>

                        <input
                            id="pontuacao-avaliacao"
                            name="pontuacao"
                            type="hidden"
                            value="0"
                            aria-describedby="feedback-avaliacao erro-pontuacao-avaliacao"
                        >

                        <div
                            id="feedback-avaliacao"
                            class="form-text"
                            data-feedback-avaliacao
                            aria-live="polite"
                            aria-atomic="true"
                        >
                            Seleciona uma estrela para avaliar.
                        </div>

                        <div
                            id="erro-pontuacao-avaliacao"
                            class="invalid-feedback"
                            aria-live="assertive"
                            aria-atomic="true"
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
                        Guardar avaliação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
