{{--
    Apresenta o formulário modal para avaliar uma MetalThursday ou uma
    das respetivas secções.

    O elemento avaliado e a pontuação são preenchidos dinamicamente pelo
    JavaScript. A escala de pontuações é preparada pela camada responsável
    pela apresentação da página.

    @since 1.0.0
    @version 3.0.0
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
                action="{{ route('avaliacoes.guardar') }}"
                data-formulario-avaliacao
                data-endereco="{{ route('avaliacoes.guardar') }}"
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
                        Avaliar
                    </h2>

                    <button
                        class="btn-close btn-close-white"
                        type="button"
                        data-bs-dismiss="modal"
                        aria-label="Fechar"
                    ></button>
                </div>

                <div class="modal-body text-center">
                    <input
                        id="tipo-avaliavel"
                        type="hidden"
                        name="tipo_avaliavel"
                        value="{{ old('tipo_avaliavel') }}"
                    >

                    <input
                        id="identificador-avaliavel"
                        type="hidden"
                        name="identificador_avaliavel"
                        value="{{ old('identificador_avaliavel') }}"
                    >

                    @if (
                        $errors->has('tipo_avaliavel')
                        || $errors->has('identificador_avaliavel')
                    )
                        <div
                            class="alert alert-danger text-start"
                            role="alert"
                        >
                            Não foi possível identificar o elemento
                            que pretendes avaliar.
                        </div>
                    @endif

                    <p
                        id="nome-avaliavel"
                        class="mb-3 small"
                        aria-live="polite"
                    ></p>

                    <fieldset class="border-0 p-0 m-0">
                        <legend class="visually-hidden">
                            Seleciona uma pontuação entre
                            {{ $pontuacaoMinima }}
                            e
                            {{ $pontuacaoMaxima }}
                        </legend>

                        <div
                            id="seletor-pontuacao"
                            class="estrelas-avaliacao-interativas"
                            role="radiogroup"
                            aria-label="Pontuação"
                            aria-describedby="resposta-avaliacao erro-pontuacao"
                            data-pontuacao-minima="{{ $pontuacaoMinima }}"
                            data-pontuacao-maxima="{{ $pontuacaoMaxima }}"
                        >
                            @foreach ($pontuacoesDisponiveis as $pontuacao)
                                <button
                                    class="botao-estrela-avaliacao"
                                    type="button"
                                    role="radio"
                                    data-pontuacao="{{ $pontuacao }}"
                                    aria-checked="{{
                                        (string) old('pontuacao')
                                            === (string) $pontuacao
                                                ? 'true'
                                                : 'false'
                                    }}"
                                    tabindex="{{
                                        (
                                            (string) old('pontuacao')
                                                === (string) $pontuacao
                                        )
                                        || (
                                            old('pontuacao') === null
                                            && $loop->first
                                        )
                                            ? '0'
                                            : '-1'
                                    }}"
                                    aria-label="{{
                                        $pontuacao
                                    }} em {{ $pontuacaoMaxima }}"
                                    title="{{
                                        $pontuacao
                                    }} em {{ $pontuacaoMaxima }}"
                                >
                                    <i
                                        class="bi {{
                                            (string) old('pontuacao')
                                                === (string) $pontuacao
                                                    ? 'bi-star-fill'
                                                    : 'bi-star'
                                        }}"
                                        aria-hidden="true"
                                    ></i>
                                </button>
                            @endforeach
                        </div>
                    </fieldset>

                    <div
                        id="resposta-avaliacao"
                        class="resposta-avaliacao mt-2"
                        aria-live="polite"
                        aria-atomic="true"
                    ></div>

                    <input
                        id="pontuacao-avaliacao"
                        type="hidden"
                        name="pontuacao"
                        value="{{ old('pontuacao') }}"
                    >

                    <div
                        id="erro-pontuacao"
                        class="invalid-feedback @error('pontuacao') d-block @enderror"
                        aria-live="assertive"
                    >
                        @error('pontuacao')
                            {{ $message }}
                        @enderror
                    </div>
                </div>

                <div class="modal-footer border-secondary">
                    <button
                        class="btn btn-primary w-100"
                        type="submit"
                    >
                        Guardar avaliação
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
