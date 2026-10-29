import {
    afterEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorSeccoes
    from '../../../resources/js/modulos/GestorSeccoes.js';

import GestorLigacoesSecao
    from '../../../resources/js/modulos/GestorLigacoesSecao.js';

import EditorLancamentoDiscogs
    from '../../../resources/js/modulos/EditorLancamentoDiscogs.js';

describe('B27.8 — acessibilidade dos campos dinamicos', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it(
        'devolve o foco ao adicionar depois de remover a ultima seccao',
        () => {
            document.body.innerHTML = `
                <div id="contentor-seccoes">
                    <section class="item-seccao">
                        <button
                            type="button"
                            class="botao-remover-seccao"
                        >
                            Remover secção
                        </button>
                    </section>
                </div>

                <button
                    id="adicionar-seccao"
                    type="button"
                >
                    Adicionar secção
                </button>
            `;

            const contentor = document.getElementById(
                'contentor-seccoes',
            );

            const adicionar = document.getElementById(
                'adicionar-seccao',
            );

            const remover = contentor.querySelector(
                '.botao-remover-seccao',
            );

            const gestor = Object.create(
                GestorSeccoes.prototype,
            );

            gestor.contentor = contentor;
            gestor.botaoAdicionar = adicionar;

            remover.focus();

            expect(document.activeElement).toBe(
                remover,
            );

            expect(gestor.remover(remover)).toBe(true);

            expect(
                contentor.querySelector('.item-seccao'),
            ).toBeNull();

            expect(document.activeElement).toBe(
                adicionar,
            );
        },
    );

    it(
        'devolve o foco ao adicionar depois de remover a ultima ligacao',
        () => {
            document.body.innerHTML = `
                <section class="item-seccao">
                    <div data-lista-ligacoes-seccao>
                        <div data-ligacao-seccao>
                            <button
                                type="button"
                                data-acao-ligacao-seccao="remover"
                            >
                                Remover ligação
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        data-acao-ligacao-seccao="adicionar"
                    >
                        Adicionar ligação
                    </button>
                </section>
            `;

            const lista = document.querySelector(
                '[data-lista-ligacoes-seccao]',
            );

            const adicionar = document.querySelector(
                '[data-acao-ligacao-seccao="adicionar"]',
            );

            const remover = document.querySelector(
                '[data-acao-ligacao-seccao="remover"]',
            );

            const gestor = Object.create(
                GestorLigacoesSecao.prototype,
            );

            gestor.lista = lista;
            gestor.botaoAdicionar = adicionar;
            gestor.normalizar = vi.fn();

            remover.focus();

            expect(document.activeElement).toBe(
                remover,
            );

            expect(gestor.remover(remover)).toBe(true);

            expect(
                lista.querySelector('[data-ligacao-seccao]'),
            ).toBeNull();

            expect(document.activeElement).toBe(
                adicionar,
            );
        },
    );

    it(
        'devolve o foco ao adicionar depois de remover a ultima faixa',
        () => {
            document.body.innerHTML = `
                <section class="item-seccao">
                    <div data-lista-faixas-lancamento>
                        <div data-faixa-lancamento>
                            <button
                                type="button"
                                data-acao-faixa-lancamento="remover"
                            >
                                Remover faixa
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        data-acao-faixa-lancamento="adicionar"
                    >
                        Adicionar faixa
                    </button>
                </section>
            `;

            const seccao = document.querySelector(
                '.item-seccao',
            );

            const lista = seccao.querySelector(
                '[data-lista-faixas-lancamento]',
            );

            const adicionar = seccao.querySelector(
                '[data-acao-faixa-lancamento="adicionar"]',
            );

            const remover = seccao.querySelector(
                '[data-acao-faixa-lancamento="remover"]',
            );

            const editor = Object.create(
                EditorLancamentoDiscogs.prototype,
            );

            editor.reindexarFaixas = vi.fn();

            seccao.addEventListener(
                'click',
                (evento) => editor.tratarClique(evento),
            );

            remover.focus();

            expect(document.activeElement).toBe(
                remover,
            );

            remover.click();

            expect(
                lista.querySelector('[data-faixa-lancamento]'),
            ).toBeNull();

            expect(editor.reindexarFaixas).toHaveBeenCalledWith(
                seccao,
            );

            expect(document.activeElement).toBe(
                adicionar,
            );
        },
    );

    it(
        'associa as etiquetas aos campos das faixas dinamicas',
        () => {
            const editor = Object.create(
                EditorLancamentoDiscogs.prototype,
            );

            const linha = editor.criarLinhaFaixa({
                id: null,
                musica_id: null,
                ordem: null,
                posicao: 'A1',
                titulo: 'Faixa de teste',
            });

            document.body.append(linha);

            const posicao = linha.querySelector(
                '[data-campo-posicao-faixa]',
            );

            const titulo = linha.querySelector(
                '[data-campo-titulo-faixa]',
            );

            expect(posicao).toBeInstanceOf(
                HTMLInputElement,
            );

            expect(titulo).toBeInstanceOf(
                HTMLInputElement,
            );

            expect(posicao.labels).toHaveLength(1);
            expect(titulo.labels).toHaveLength(1);

            expect(
                posicao.labels[0].textContent.trim(),
            ).toBe('Posição');

            expect(
                titulo.labels[0].textContent.trim(),
            ).toBe('Título');
        },
    );
    it(
        'mantem a navegacao ao remover uma seccao intermedia',
        () => {
            document.body.innerHTML = `
                <div id="seccoes-teste">
                    ${[0, 1, 2].map((indice) => `
                        <section
                            class="item-seccao"
                            data-indice-seccao="${indice}"
                        >
                            <select class="seletor-tipo-seccao">
                                <option value="1">Texto</option>
                            </select>
                            <button class="botao-remover-seccao">
                                Remover
                            </button>
                        </section>
                    `).join('')}
                </div>
                <button id="adicionar-teste">Adicionar</button>
                <button id="externo-teste">Externo</button>
            `;

            const contentor = document.getElementById(
                'seccoes-teste',
            );

            const gestor = Object.create(
                GestorSeccoes.prototype,
            );

            gestor.contentor = contentor;
            gestor.botaoAdicionar = document.getElementById(
                'adicionar-teste',
            );

            const seccoes = Array.from(
                contentor.querySelectorAll('.item-seccao'),
            );

            const remover = seccoes[1].querySelector(
                '.botao-remover-seccao',
            );

            const proximoCampo = seccoes[2].querySelector(
                '.seletor-tipo-seccao',
            );

            remover.focus();

            expect(gestor.remover(remover)).toBe(true);
            expect(document.activeElement).toBe(proximoCampo);

            const externo = document.getElementById(
                'externo-teste',
            );

            externo.focus();

            expect(
                gestor.remover(
                    seccoes[0].querySelector(
                        '.botao-remover-seccao',
                    ),
                ),
            ).toBe(true);

            expect(document.activeElement).toBe(externo);
        },
    );

    it(
        'mantem a navegacao ao remover uma ligacao intermedia',
        () => {
            document.body.innerHTML = `
                <div data-lista-ligacoes-seccao>
                    ${[0, 1, 2].map((indice) => `
                        <div
                            data-ligacao-seccao
                            data-indice="${indice}"
                        >
                            <input data-campo-url-ligacao>
                            <button
                                data-acao-ligacao-seccao="remover"
                            >
                                Remover
                            </button>
                        </div>
                    `).join('')}
                </div>
                <button
                    data-acao-ligacao-seccao="adicionar"
                >
                    Adicionar
                </button>
                <button id="externo-teste">Externo</button>
            `;

            const lista = document.querySelector(
                '[data-lista-ligacoes-seccao]',
            );

            const linhas = Array.from(
                lista.querySelectorAll('[data-ligacao-seccao]'),
            );

            const gestor = Object.create(
                GestorLigacoesSecao.prototype,
            );

            gestor.lista = lista;

            gestor.botaoAdicionar = document.querySelector(
                '[data-acao-ligacao-seccao="adicionar"]',
            );

            gestor.normalizar = vi.fn();

            const remover = linhas[1].querySelector(
                '[data-acao-ligacao-seccao="remover"]',
            );

            const proximoCampo = linhas[2].querySelector(
                '[data-campo-url-ligacao]',
            );

            remover.focus();

            expect(gestor.remover(remover)).toBe(true);
            expect(document.activeElement).toBe(proximoCampo);

            const externo = document.getElementById(
                'externo-teste',
            );

            externo.focus();

            expect(
                gestor.remover(
                    linhas[0].querySelector(
                        '[data-acao-ligacao-seccao="remover"]',
                    ),
                ),
            ).toBe(true);

            expect(document.activeElement).toBe(externo);
        },
    );

    it(
        'mantem a navegacao ao remover uma faixa intermedia',
        () => {
            document.body.innerHTML = `
                <section class="item-seccao">
                    <div data-lista-faixas-lancamento>
                        ${[0, 1, 2].map((indice) => `
                            <div
                                data-faixa-lancamento
                                data-indice="${indice}"
                            >
                                <input data-campo-titulo-faixa>
                                <button
                                    data-acao-faixa-lancamento="remover"
                                >
                                    Remover
                                </button>
                            </div>
                        `).join('')}
                    </div>
                    <button
                        data-acao-faixa-lancamento="adicionar"
                    >
                        Adicionar
                    </button>
                </section>
                <button id="externo-teste">Externo</button>
            `;

            const seccao = document.querySelector(
                '.item-seccao',
            );

            const linhas = Array.from(
                seccao.querySelectorAll('[data-faixa-lancamento]'),
            );

            const editor = Object.create(
                EditorLancamentoDiscogs.prototype,
            );

            editor.reindexarFaixas = vi.fn();

            seccao.addEventListener(
                'click',
                (evento) => editor.tratarClique(evento),
            );

            const remover = linhas[1].querySelector(
                '[data-acao-faixa-lancamento="remover"]',
            );

            const proximoCampo = linhas[2].querySelector(
                '[data-campo-titulo-faixa]',
            );

            remover.focus();
            remover.click();

            expect(document.activeElement).toBe(proximoCampo);
            expect(linhas[1].isConnected).toBe(false);

            const externo = document.getElementById(
                'externo-teste',
            );

            externo.focus();

            linhas[0].querySelector(
                '[data-acao-faixa-lancamento="remover"]',
            ).click();

            expect(document.activeElement).toBe(externo);

            expect(editor.reindexarFaixas).toHaveBeenCalledTimes(
                2,
            );
        },
    );

    it(
        'cria etiquetas associadas com identificadores distintos',
        () => {
            const editor = Object.create(
                EditorLancamentoDiscogs.prototype,
            );

            const criar = (titulo) => editor.criarLinhaFaixa({
                id: null,
                musica_id: null,
                ordem: null,
                posicao: null,
                titulo,
            });

            const primeira = criar('Primeira faixa');
            const segunda = criar('Segunda faixa');

            document.body.append(primeira, segunda);

            const campos = [
                ...document.querySelectorAll(
                    '[data-campo-posicao-faixa], [data-campo-titulo-faixa]',
                ),
            ];

            expect(campos).toHaveLength(4);

            const identificadores = campos.map(
                (campo) => campo.id,
            );

            expect(
                identificadores.every(Boolean),
            ).toBe(true);

            expect(
                new Set(identificadores).size,
            ).toBe(4);

            for (const campo of campos) {
                expect(campo.labels).toHaveLength(1);

                expect(campo.labels[0].htmlFor).toBe(
                    campo.id,
                );
            }
        },
    );


    it(
        'preserva o foco ao subir uma ligacao sem atingir o limite',
        () => {
            document.body.innerHTML = `
                <div data-lista-ligacoes-seccao>
                    ${[0, 1, 2, 3].map((indice) => `
                        <div
                            data-ligacao-seccao
                            data-indice="${indice}"
                        >
                            <button
                                type="button"
                                data-acao-ligacao-seccao="subir"
                            >
                                Subir
                            </button>
                        </div>
                    `).join('')}
                </div>
            `;

            const lista = document.querySelector(
                '[data-lista-ligacoes-seccao]',
            );

            const linhas = Array.from(
                lista.querySelectorAll('[data-ligacao-seccao]'),
            );

            const gestor = Object.create(
                GestorLigacoesSecao.prototype,
            );

            gestor.lista = lista;
            gestor.normalizar = vi.fn();

            const botao = linhas[2].querySelector(
                '[data-acao-ligacao-seccao="subir"]',
            );

            botao.focus();

            expect(document.activeElement).toBe(botao);

            expect(gestor.mover(botao, -1)).toBe(true);

            expect(
                Array.from(lista.children),
            ).toEqual([
                linhas[0],
                linhas[2],
                linhas[1],
                linhas[3],
            ]);

            expect(document.activeElement).toBe(botao);
        },
    );

    it(
        'preserva o foco ao subir uma faixa sem atingir o limite',
        () => {
            document.body.innerHTML = `
                <section class="item-seccao">
                    <div data-lista-faixas-lancamento>
                        ${[0, 1, 2, 3].map((indice) => `
                            <div
                                data-faixa-lancamento
                                data-indice="${indice}"
                            >
                                <button
                                    type="button"
                                    data-acao-faixa-lancamento="subir"
                                >
                                    Subir
                                </button>
                            </div>
                        `).join('')}
                    </div>
                </section>
            `;

            const seccao = document.querySelector(
                '.item-seccao',
            );

            const lista = seccao.querySelector(
                '[data-lista-faixas-lancamento]',
            );

            const linhas = Array.from(
                lista.querySelectorAll('[data-faixa-lancamento]'),
            );

            const editor = Object.create(
                EditorLancamentoDiscogs.prototype,
            );

            editor.reindexarFaixas = vi.fn();

            seccao.addEventListener(
                'click',
                (evento) => editor.tratarClique(evento),
            );

            const botao = linhas[2].querySelector(
                '[data-acao-faixa-lancamento="subir"]',
            );

            botao.focus();

            expect(document.activeElement).toBe(botao);

            botao.click();

            expect(
                Array.from(lista.children),
            ).toEqual([
                linhas[0],
                linhas[2],
                linhas[1],
                linhas[3],
            ]);

            expect(
                editor.reindexarFaixas,
            ).toHaveBeenCalledWith(seccao);

            expect(document.activeElement).toBe(botao);
        },
    );


    it(
        'recupera o foco quando a reordenacao desativa o botao da ligacao',
        () => {
            for (const acao of ['subir', 'descer']) {
                document.body.innerHTML = `
                    <div data-lista-ligacoes-seccao>
                        ${[0, 1].map(() => `
                            <div data-ligacao-seccao>
                                <input data-campo-url-ligacao>
                                <button
                                    type="button"
                                    data-acao-ligacao-seccao="${acao}"
                                >
                                    ${acao}
                                </button>
                            </div>
                        `).join('')}
                    </div>
                `;

                const lista = document.querySelector(
                    '[data-lista-ligacoes-seccao]',
                );

                const linhas = Array.from(
                    lista.querySelectorAll('[data-ligacao-seccao]'),
                );

                const indice = acao === 'subir' ? 1 : 0;
                const linha = linhas[indice];

                const botao = linha.querySelector(
                    `[data-acao-ligacao-seccao="${acao}"]`,
                );

                const campo = linha.querySelector(
                    '[data-campo-url-ligacao]',
                );

                const gestor = Object.create(
                    GestorLigacoesSecao.prototype,
                );

                gestor.lista = lista;

                gestor.normalizar = vi.fn(() => {
                    botao.disabled = true;
                });

                botao.focus();

                expect(document.activeElement).toBe(botao);

                expect(
                    gestor.mover(
                        botao,
                        acao === 'subir' ? -1 : 1,
                    ),
                ).toBe(true);

                expect(botao.disabled).toBe(true);
                expect(document.activeElement).toBe(campo);
            }
        },
    );

    it(
        'recupera o foco quando a reordenacao desativa o botao da faixa',
        () => {
            for (const acao of ['subir', 'descer']) {
                document.body.innerHTML = `
                    <section class="item-seccao">
                        <div data-lista-faixas-lancamento>
                            ${[0, 1].map(() => `
                                <div data-faixa-lancamento>
                                    <input data-campo-titulo-faixa>
                                    <button
                                        type="button"
                                        data-acao-faixa-lancamento="${acao}"
                                    >
                                        ${acao}
                                    </button>
                                </div>
                            `).join('')}
                        </div>
                    </section>
                `;

                const seccao = document.querySelector(
                    '.item-seccao',
                );

                const linhas = Array.from(
                    seccao.querySelectorAll('[data-faixa-lancamento]'),
                );

                const indice = acao === 'subir' ? 1 : 0;
                const linha = linhas[indice];

                const botao = linha.querySelector(
                    `[data-acao-faixa-lancamento="${acao}"]`,
                );

                const campo = linha.querySelector(
                    '[data-campo-titulo-faixa]',
                );

                const editor = Object.create(
                    EditorLancamentoDiscogs.prototype,
                );

                editor.reindexarFaixas = vi.fn(() => {
                    botao.disabled = true;
                });

                seccao.addEventListener(
                    'click',
                    (evento) => editor.tratarClique(evento),
                );

                botao.focus();

                expect(document.activeElement).toBe(botao);

                botao.click();

                expect(botao.disabled).toBe(true);
                expect(document.activeElement).toBe(campo);
            }
        },
    );


});
