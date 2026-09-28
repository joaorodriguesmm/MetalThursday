import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorSeccoes
    from '../../../resources/js/modulos/GestorSeccoes.js';

describe(
    'GestorSeccoes',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = `
                    <div id="contentor-seccoes">
                        <article
                            class="item-seccao"
                            data-indice-seccao="0"
                        >
                            <select
                                class="seletor-tipo-seccao"
                                name="seccoes[0][tipo_seccao_id]"
                            >
                                <option
                                    value="1"
                                    data-exige-detalhes="false"
                                    selected
                                >
                                    Texto
                                </option>
                            </select>
                        </article>

                        <article
                            class="item-seccao"
                            data-indice-seccao="2"
                        >
                            <select
                                class="seletor-tipo-seccao"
                                name="seccoes[2][tipo_seccao_id]"
                            >
                                <option
                                    value="1"
                                    data-exige-detalhes="false"
                                    selected
                                >
                                    Texto
                                </option>
                            </select>
                        </article>
                    </div>

                    <button
                        id="botao-adicionar-seccao"
                        type="button"
                    >
                        Adicionar
                    </button>

                    <template id="modelo-item-seccao">
                        <article
                            id="seccao-__INDICE_SECCAO__"
                            class="item-seccao"
                        >
                            <select
                                class="seletor-tipo-seccao"
                                name="seccoes[__INDICE_SECCAO__][tipo_seccao_id]"
                            >
                                <option
                                    value="1"
                                    data-exige-detalhes="false"
                                    selected
                                >
                                    Texto
                                </option>
                                <option
                                    value="2"
                                    data-exige-detalhes="true"
                                >
                                    Lançamento
                                </option>
                            </select>

                            <div
                                class="linha-detalhes-seccao"
                            >
                                <select
                                    name="seccoes[__INDICE_SECCAO__][artista_id]"
                                >
                                    <option value="">Selecionar</option>
                                    <option value="10">Artista</option>
                                </select>

                                <input
                                    type="text"
                                    name="seccoes[__INDICE_SECCAO__][titulo]"
                                >

                                <input
                                    type="url"
                                    name="seccoes[__INDICE_SECCAO__][ligacao]"
                                >

                                <input
                                    type="number"
                                    name="seccoes[__INDICE_SECCAO__][ano]"
                                >

                                <textarea
                                    name="seccoes[__INDICE_SECCAO__][descricao]"
                                ></textarea>

                                <button
                                    type="button"
                                    class="botao-detalhe"
                                >
                                    Ação
                                </button>
                            </div>

                            <span
                                class="indicador-titulo-obrigatorio"
                            >
                                *
                            </span>

                            <button
                                type="button"
                                class="botao-remover-seccao"
                            >
                                Remover
                            </button>
                        </article>
                    </template>
                `;
            },
        );

        afterEach(
            () => {
                vi.restoreAllMocks();
                document.body.replaceChildren();
            },
        );

        it(
            'adiciona secao com indice posterior ao maior existente',
            () => {
                const aoAdicionar =
                    vi.fn();

                const gestor =
                    new GestorSeccoes(
                        '#contentor-seccoes',
                        '#botao-adicionar-seccao',
                        '#modelo-item-seccao',
                        aoAdicionar,
                    );

                const seccao =
                    gestor.adicionar();

                expect(
                    seccao.dataset
                        .indiceSeccao,
                ).toBe('3');

                expect(
                    seccao.id,
                ).toBe('seccao-3');

                expect(
                    seccao.querySelector(
                        '.seletor-tipo-seccao',
                    ).name,
                ).toBe(
                    'seccoes[3][tipo_seccao_id]',
                );

                expect(
                    seccao.querySelector(
                        'input[name$="[titulo]"]',
                    ).name,
                ).toBe(
                    'seccoes[3][titulo]',
                );

                expect(
                    aoAdicionar,
                ).toHaveBeenCalledTimes(1);

                expect(
                    aoAdicionar,
                ).toHaveBeenCalledWith(
                    seccao,
                );
            },
        );

        it(
            'nao reutiliza indice removido durante a mesma sessao',
            () => {
                const gestor =
                    new GestorSeccoes(
                        '#contentor-seccoes',
                        '#botao-adicionar-seccao',
                        '#modelo-item-seccao',
                    );

                const primeira =
                    gestor.adicionar();

                expect(
                    primeira.dataset
                        .indiceSeccao,
                ).toBe('3');

                expect(
                    gestor.remover(
                        primeira.querySelector(
                            '.botao-remover-seccao',
                        ),
                    ),
                ).toBe(true);

                const segunda =
                    gestor.adicionar();

                expect(
                    segunda.dataset
                        .indiceSeccao,
                ).toBe('4');

                expect(
                    document.querySelectorAll(
                        '.item-seccao',
                    ),
                ).toHaveLength(3);
            },
        );

        it(
            'ativa e desativa detalhes obrigatorios conforme o tipo',
            () => {
                const gestor =
                    new GestorSeccoes(
                        '#contentor-seccoes',
                        '#botao-adicionar-seccao',
                        '#modelo-item-seccao',
                    );

                const seccao =
                    gestor.adicionar();

                const selecaoTipo =
                    seccao.querySelector(
                        '.seletor-tipo-seccao',
                    );

                const linhaDetalhes =
                    seccao.querySelector(
                        '.linha-detalhes-seccao',
                    );

                const titulo =
                    seccao.querySelector(
                        'input[name$="[titulo]"]',
                    );

                const descricao =
                    seccao.querySelector(
                        'textarea[name$="[descricao]"]',
                    );

                const indicador =
                    seccao.querySelector(
                        '.indicador-titulo-obrigatorio',
                    );

                expect(
                    linhaDetalhes.hidden,
                ).toBe(true);

                expect(
                    linhaDetalhes.getAttribute(
                        'aria-hidden',
                    ),
                ).toBe('true');

                expect(
                    titulo.disabled,
                ).toBe(true);

                expect(
                    titulo.required,
                ).toBe(false);

                expect(
                    descricao.disabled,
                ).toBe(true);

                expect(
                    indicador.hidden,
                ).toBe(true);

                const eventoEstado =
                    vi.fn();

                seccao.addEventListener(
                    GestorSeccoes
                        .EVENTO_ESTADO_ATUALIZADO,
                    eventoEstado,
                );

                selecaoTipo.value = '2';

                gestor.atualizarEstadoSeccao(
                    selecaoTipo,
                );

                expect(
                    linhaDetalhes.hidden,
                ).toBe(false);

                expect(
                    linhaDetalhes.hasAttribute(
                        'aria-hidden',
                    ),
                ).toBe(false);

                expect(
                    titulo.disabled,
                ).toBe(false);

                expect(
                    titulo.required,
                ).toBe(true);

                expect(
                    descricao.disabled,
                ).toBe(false);

                expect(
                    indicador.hidden,
                ).toBe(false);

                expect(
                    eventoEstado,
                ).toHaveBeenCalledTimes(1);

                expect(
                    eventoEstado.mock.calls[0][0]
                        .detail,
                ).toEqual({
                    exigeDetalhes: true,
                });
            },
        );

        it(
            'destroi tom select antes de remover uma secao',
            () => {
                const gestor =
                    new GestorSeccoes(
                        '#contentor-seccoes',
                        '#botao-adicionar-seccao',
                        '#modelo-item-seccao',
                    );

                const seccao =
                    gestor.adicionar();

                const selecaoArtista =
                    seccao.querySelector(
                        'select[name$="[artista_id]"]',
                    );

                const destruir =
                    vi.fn();

                selecaoArtista.tomselect = {
                    destroy:
                        destruir,
                };

                const removida =
                    gestor.remover(
                        seccao.querySelector(
                            '.botao-remover-seccao',
                        ),
                    );

                expect(
                    removida,
                ).toBe(true);

                expect(
                    destruir,
                ).toHaveBeenCalledTimes(1);

                expect(
                    seccao.isConnected,
                ).toBe(false);
            },
        );
    },
);
