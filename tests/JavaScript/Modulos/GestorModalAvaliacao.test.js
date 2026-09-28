import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
} from 'vitest';

import GestorModalAvaliacao
    from '../../../resources/js/modulos/GestorModalAvaliacao.js';

describe(
    'GestorModalAvaliacao',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML =
                    criarEstrutura();
            },
        );

        afterEach(
            () => {
                document.body.replaceChildren();
            },
        );

        it(
            'configura a modal a partir de um acionador valido',
            () => {
                const gestor =
                    new GestorModalAvaliacao();

                const acionador =
                    document.getElementById(
                        'botao-avaliar',
                    );

                gestor.configurarModal({
                    relatedTarget:
                        acionador,
                });

                expect(
                    gestor.botaoAcionador,
                ).toBe(
                    acionador,
                );

                expect(
                    gestor.enderecoSubmissao,
                ).toBe(
                    new URL(
                        '/avaliacoes/77',
                        window.location.origin,
                    ).href,
                );

                expect(
                    gestor.formulario.dataset
                        .tipoAvaliavel,
                ).toBe('lancamento');

                expect(
                    gestor.formulario.dataset
                        .identificadorAvaliavel,
                ).toBe('77');

                expect(
                    gestor.elementoNomeAvaliavel
                        .textContent,
                ).toBe(
                    'Master of Puppets',
                );

                expect(
                    gestor.campoPontuacao.value,
                ).toBe('3.5');

                expect(
                    gestor.botaoLimparAvaliacao
                        .hidden,
                ).toBe(false);

                expect(
                    gestor.estrelas[3]
                        .classList
                        .contains(
                            'bi-star-half',
                        ),
                ).toBe(true);
            },
        );

        it(
            'rejeita um endereco de avaliacao de outra origem',
            () => {
                const gestor =
                    new GestorModalAvaliacao();

                const acionador =
                    document.getElementById(
                        'botao-avaliar',
                    );

                acionador.dataset
                    .enderecoAvaliacao =
                        'https://example.com/avaliacoes/77';

                gestor.configurarModal({
                    relatedTarget:
                        acionador,
                });

                expect(
                    gestor.botaoAcionador,
                ).toBeNull();

                expect(
                    gestor.enderecoSubmissao,
                ).toBeNull();

                expect(
                    gestor.formulario
                        .hasAttribute(
                            'action',
                        ),
                ).toBe(false);

                expect(
                    gestor.campoPontuacao
                        .getAttribute(
                            'aria-invalid',
                        ),
                ).toBe('true');

                expect(
                    gestor.elementoErro
                        .textContent,
                ).toBe(
                    'Não foi possível preparar esta avaliação.',
                );
            },
        );

        it(
            'permite alterar a avaliacao em meios pontos com o teclado',
            () => {
                const gestor =
                    new GestorModalAvaliacao();

                const acionador =
                    document.getElementById(
                        'botao-avaliar',
                    );

                gestor.configurarModal({
                    relatedTarget:
                        acionador,
                });

                const quartaEstrela =
                    gestor.estrelas[3];

                quartaEstrela.dispatchEvent(
                    new KeyboardEvent(
                        'keydown',
                        {
                            key:
                                'ArrowRight',
                            bubbles: true,
                            cancelable: true,
                        },
                    ),
                );

                expect(
                    gestor.pontuacaoSelecionada,
                ).toBe(4);

                expect(
                    gestor.campoPontuacao.value,
                ).toBe('4');

                expect(
                    gestor.estrelas[3]
                        .classList
                        .contains(
                            'bi-star-fill',
                        ),
                ).toBe(true);

                expect(
                    gestor.estrelas[3]
                        .tabIndex,
                ).toBe(0);

                quartaEstrela.dispatchEvent(
                    new KeyboardEvent(
                        'keydown',
                        {
                            key:
                                'ArrowLeft',
                            bubbles: true,
                            cancelable: true,
                        },
                    ),
                );

                expect(
                    gestor.pontuacaoSelecionada,
                ).toBe(3.5);

                expect(
                    gestor.estrelas[3]
                        .classList
                        .contains(
                            'bi-star-half',
                        ),
                ).toBe(true);
            },
        );

        it(
            'aplica a resposta ao acionador e ao resumo das avaliacoes',
            () => {
                const gestor =
                    new GestorModalAvaliacao();

                const acionador =
                    document.getElementById(
                        'botao-avaliar',
                    );

                gestor.configurarModal({
                    relatedTarget:
                        acionador,
                });

                expect(
                    gestor.atualizarResultado(
                        acionador,
                        {
                            pontuacao_utilizador:
                                4.5,
                            media_avaliacoes:
                                4.2,
                            numero_avaliacoes:
                                12,
                            conteudo_indicador_html:
                                '',
                        },
                    ),
                ).toBe(true);

                expect(
                    acionador.dataset
                        .pontuacaoUtilizador,
                ).toBe('4.5');

                expect(
                    acionador.querySelector(
                        '[data-texto-avaliacao]',
                    ).textContent,
                ).toContain(
                    '4,5',
                );

                expect(
                    gestor.botaoLimparAvaliacao
                        .hidden,
                ).toBe(false);

                const resumo =
                    document.querySelector(
                        '.apresentacao-avaliacoes',
                    );

                expect(
                    resumo.querySelector(
                        '.media-avaliacoes',
                    ).textContent,
                ).toBe('4,2');

                expect(
                    resumo.querySelector(
                        '.quantidade-avaliacoes',
                    ).textContent,
                ).toBe('12');

                expect(
                    resumo.getAttribute(
                        'aria-label',
                    ),
                ).toContain(
                    'Média: 4,2. Total: 12.',
                );

                expect(
                    gestor.atualizarResultado(
                        acionador,
                        {
                            pontuacao_utilizador:
                                6,
                            media_avaliacoes:
                                4.2,
                            numero_avaliacoes:
                                12,
                        },
                    ),
                ).toBe(false);

                expect(
                    acionador.dataset
                        .pontuacaoUtilizador,
                ).toBe('4.5');
            },
        );
    },
);

/**
 * Cria a estrutura mínima utilizada pela modal de avaliação.
 *
 * @returns {string} HTML de teste.
 *
 * @since 2.0.0
 */
function criarEstrutura() {
    return `
        <div data-contentor-interacoes>
            <button
                id="botao-avaliar"
                type="button"
                data-tipo-avaliavel="lancamento"
                data-identificador-avaliavel="77"
                data-nome-avaliavel="Master of Puppets"
                data-endereco-avaliacao="/avaliacoes/77"
                data-pontuacao-utilizador="3.5"
                data-texto-sem-avaliacao="Avaliar"
            >
                <span data-texto-avaliacao>
                    A tua avaliação: 3,5
                </span>
            </button>

            <button
                type="button"
                class="apresentacao-avaliacoes"
                aria-label="Consultar detalhes das avaliações."
            >
                <span class="media-avaliacoes">
                    3,8
                </span>
                <span class="quantidade-avaliacoes">
                    8
                </span>
            </button>
        </div>

        <div
            id="modal-avaliacao"
            class="modal"
        >
            <form
                id="formulario-avaliacao"
                data-formulario-avaliacao
            >
                <span data-nome-avaliavel>
                    elemento selecionado
                </span>

                <div data-estrelas-avaliacao>
                    ${criarEstrela(1)}
                    ${criarEstrela(2)}
                    ${criarEstrela(3)}
                    ${criarEstrela(4)}
                    ${criarEstrela(5)}
                </div>

                <input
                    type="hidden"
                    name="pontuacao"
                    value="0"
                >

                <p data-feedback-avaliacao></p>

                <p
                    id="erro-pontuacao-avaliacao"
                ></p>

                <button
                    type="submit"
                >
                    Guardar
                </button>

                <button
                    type="button"
                    data-limpar-avaliacao
                    hidden
                >
                    Limpar
                </button>
            </form>
        </div>
    `;
}

/**
 * Cria um botão de estrela.
 *
 * @param {number} valor Valor integral representado.
 *
 * @returns {string} HTML da estrela.
 *
 * @since 2.0.0
 */
function criarEstrela(valor) {
    return `
        <button
            type="button"
            class="bi bi-star"
            data-valor="${valor}"
            aria-label="${valor} de 5"
        ></button>
    `;
}
