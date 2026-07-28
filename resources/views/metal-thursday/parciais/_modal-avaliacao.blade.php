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
>
    <form
        id="formulario-avaliacao"
        data-formulario-avaliacao
    >
        <span data-nome-avaliavel></span>

        <div data-estrelas-avaliacao>
            @foreach ($pontuacoesDisponiveis as $pontuacao)
                <i
                    class="bi bi-star"
                    data-valor="{{ $pontuacao }}"
                    aria-hidden="true"
                ></i>
            @endforeach
        </div>

        <input
            name="pontuacao"
            type="hidden"
            value="0"
        >

        <div
            data-feedback-avaliacao
            aria-live="polite"
        >
            Clica numa estrela para avaliar.
        </div>
    </form>
</div>
