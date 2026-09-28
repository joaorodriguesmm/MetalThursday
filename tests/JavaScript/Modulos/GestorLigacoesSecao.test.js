import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorLigacoesSecao
    from '../../../resources/js/modulos/GestorLigacoesSecao.js';

describe(
    'GestorLigacoesSecao',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = `
                    <article
                        id="seccao-5"
                        class="item-seccao"
                        data-indice-seccao="5"
                    >
                        <div class="linha-detalhes-seccao">
                            <div
                                data-editor-ligacoes-seccao
                                data-nome-base-campo="seccoes[5]"
                                data-maximo-ligacoes="3"
                            >
                                <div data-lista-ligacoes-seccao>
                                    ${criarLinhaLigacao(
                                        'https://open.spotify.com/track/abc',
                                        'Primeira',
                                    )}
                                    ${criarLinhaLigacao(
                                        'https://example.com/segunda',
                                        'Segunda',
                                    )}
                                </div>

                                <template
                                    data-modelo-ligacao-seccao
                                >
                                    ${criarLinhaLigacao(
                                        '',
                                        '',
                                        '__INDICE_LIGACAO__',
                                    )}
                                </template>

                                <button
                                    type="button"
                                    data-acao-ligacao-seccao="adicionar"
                                >
                                    Adicionar ligação
                                </button>

                                <p data-estado-ligacoes-seccao></p>
                            </div>
                        </div>
                    </article>
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
            'deteta plataformas sem aceitar dominios apenas parecidos',
            () => {
                expect(
                    GestorLigacoesSecao.detetarPlataforma(
                        'https://open.spotify.com/track/123',
                    ),
                ).toMatchObject({
                    valor: 'spotify',
                    suportaIncorporacao: true,
                });

                expect(
                    GestorLigacoesSecao.detetarPlataforma(
                        'https://music.apple.com/pt/album/album/123',
                    ),
                ).toMatchObject({
                    valor: 'apple_music',
                    suportaIncorporacao: true,
                });

                expect(
                    GestorLigacoesSecao.detetarPlataforma(
                        'https://youtu.be/abc123',
                    ),
                ).toMatchObject({
                    valor: 'youtube',
                    suportaIncorporacao: true,
                });

                expect(
                    GestorLigacoesSecao.detetarPlataforma(
                        'https://spotify.com.exemplo.test/faixa',
                    ),
                ).toMatchObject({
                    valor: 'outro',
                    suportaIncorporacao: false,
                });

                expect(
                    GestorLigacoesSecao.detetarPlataforma(
                        'javascript:alert(1)',
                    ),
                ).toBeNull();
            },
        );

        it(
            'reordena ligacoes e reindexa nomes identificadores e ordem visual',
            () => {
                const gestor =
                    new GestorLigacoesSecao(
                        document.getElementById(
                            'seccao-5',
                        ),
                    );

                const linhas =
                    gestor.obterLinhas();

                expect(
                    linhas[0].querySelector(
                        '[data-campo-url-ligacao]',
                    ).name,
                ).toBe(
                    'seccoes[5][ligacoes][0][url]',
                );

                expect(
                    linhas[1].querySelector(
                        '[data-campo-url-ligacao]',
                    ).name,
                ).toBe(
                    'seccoes[5][ligacoes][1][url]',
                );

                const botaoSubirSegunda =
                    linhas[1].querySelector(
                        '[data-acao-ligacao-seccao="subir"]',
                    );

                expect(
                    gestor.mover(
                        botaoSubirSegunda,
                        -1,
                    ),
                ).toBe(true);

                const reordenadas =
                    gestor.obterLinhas();

                expect(
                    reordenadas[0].querySelector(
                        '[data-campo-etiqueta-ligacao]',
                    ).value,
                ).toBe('Segunda');

                expect(
                    reordenadas[0].querySelector(
                        '[data-campo-url-ligacao]',
                    ).name,
                ).toBe(
                    'seccoes[5][ligacoes][0][url]',
                );

                expect(
                    reordenadas[0].querySelector(
                        '[data-campo-url-ligacao]',
                    ).id,
                ).toBe(
                    'seccoes-5-ligacao-0-url',
                );

                expect(
                    reordenadas[1].querySelector(
                        '[data-campo-etiqueta-ligacao]',
                    ).value,
                ).toBe('Primeira');

                expect(
                    reordenadas[1].querySelector(
                        '[data-campo-url-ligacao]',
                    ).name,
                ).toBe(
                    'seccoes[5][ligacoes][1][url]',
                );
            },
        );

        it(
            'respeita limite maximo e reativa adicao depois de remover',
            () => {
                const gestor =
                    new GestorLigacoesSecao(
                        document.getElementById(
                            'seccao-5',
                        ),
                    );

                const botaoAdicionar =
                    document.querySelector(
                        '[data-acao-ligacao-seccao="adicionar"]',
                    );

                const estado =
                    document.querySelector(
                        '[data-estado-ligacoes-seccao]',
                    );

                expect(
                    botaoAdicionar.disabled,
                ).toBe(false);

                expect(
                    estado.textContent,
                ).toBe(
                    '2 de 3 ligações.',
                );

                const adicionada =
                    gestor.adicionar();

                expect(
                    adicionada,
                ).toBeInstanceOf(
                    HTMLElement,
                );

                expect(
                    adicionada.dataset
                        .indiceLigacao,
                ).toBe('2');

                expect(
                    adicionada.querySelector(
                        '[data-campo-url-ligacao]',
                    ).name,
                ).toBe(
                    'seccoes[5][ligacoes][2][url]',
                );

                expect(
                    botaoAdicionar.disabled,
                ).toBe(true);

                expect(
                    estado.textContent,
                ).toBe(
                    '3 de 3 ligações.',
                );

                expect(
                    gestor.adicionar(),
                ).toBeNull();

                expect(
                    gestor.remover(
                        adicionada.querySelector(
                            '[data-acao-ligacao-seccao="remover"]',
                        ),
                    ),
                ).toBe(true);

                expect(
                    botaoAdicionar.disabled,
                ).toBe(false);

                expect(
                    estado.textContent,
                ).toBe(
                    '2 de 3 ligações.',
                );
            },
        );

        it(
            'ajusta etiqueta e incorporacao conforme a plataforma',
            () => {
                const gestor =
                    new GestorLigacoesSecao(
                        document.getElementById(
                            'seccao-5',
                        ),
                    );

                const linha =
                    gestor.obterLinhas()[0];

                const campoUrl =
                    linha.querySelector(
                        '[data-campo-url-ligacao]',
                    );

                const contentorEtiqueta =
                    linha.querySelector(
                        '[data-contentor-etiqueta-ligacao]',
                    );

                const campoEtiqueta =
                    linha.querySelector(
                        '[data-campo-etiqueta-ligacao]',
                    );

                const campoIncorporar =
                    linha.querySelector(
                        '[data-campo-incorporar-ligacao]',
                    );

                expect(
                    linha.dataset
                        .plataforma,
                ).toBe('spotify');

                expect(
                    contentorEtiqueta.hidden,
                ).toBe(true);

                expect(
                    campoEtiqueta.required,
                ).toBe(false);

                expect(
                    campoEtiqueta.disabled,
                ).toBe(true);

                expect(
                    campoIncorporar.disabled,
                ).toBe(false);

                campoIncorporar.checked = true;

                campoUrl.value =
                    'https://example.com/audio';

                campoUrl.dispatchEvent(
                    new Event(
                        'input',
                        {
                            bubbles: true,
                        },
                    ),
                );

                expect(
                    linha.dataset
                        .plataforma,
                ).toBe('outro');

                expect(
                    contentorEtiqueta.hidden,
                ).toBe(false);

                expect(
                    campoEtiqueta.required,
                ).toBe(true);

                expect(
                    campoEtiqueta.disabled,
                ).toBe(false);

                expect(
                    campoIncorporar.checked,
                ).toBe(false);

                expect(
                    campoIncorporar.disabled,
                ).toBe(true);

                expect(
                    linha.querySelector(
                        '[data-plataforma-ligacao]',
                    ).textContent,
                ).toBe('Outro');
            },
        );
    },
);

/**
 * Cria uma linha HTML de ligação para os cenários de teste.
 *
 * @param {string} url URL inicial.
 * @param {string} etiqueta Etiqueta inicial.
 * @param {string} indice Índice textual do modelo.
 *
 * @returns {string} HTML da linha.
 *
 * @since 2.0.0
 */
function criarLinhaLigacao(
    url,
    etiqueta,
    indice = '0',
) {
    return `
        <div
            data-ligacao-seccao
            data-indice-ligacao="${indice}"
        >
            <span data-plataforma-ligacao></span>

            <label
                data-etiqueta-url-ligacao
            >
                Ligação
            </label>

            <input
                type="url"
                value="${url}"
                data-campo-url-ligacao
            >

            <div
                data-erro-url-ligacao
            ></div>

            <div
                data-contentor-etiqueta-ligacao
            >
                <label
                    data-etiqueta-etiqueta-ligacao
                >
                    Etiqueta
                </label>

                <input
                    type="text"
                    value="${etiqueta}"
                    data-campo-etiqueta-ligacao
                >

                <div
                    data-erro-etiqueta-ligacao
                ></div>
            </div>

            <input
                type="hidden"
                value="0"
                data-campo-incorporar-ligacao-oculto
            >

            <input
                type="checkbox"
                value="1"
                data-campo-incorporar-ligacao
            >

            <label
                data-etiqueta-incorporar-ligacao
            >
                Incorporar
            </label>

            <button
                type="button"
                data-acao-ligacao-seccao="subir"
            >
                Subir
            </button>

            <button
                type="button"
                data-acao-ligacao-seccao="descer"
            >
                Descer
            </button>

            <button
                type="button"
                data-acao-ligacao-seccao="remover"
            >
                Remover
            </button>
        </div>
    `;
}
