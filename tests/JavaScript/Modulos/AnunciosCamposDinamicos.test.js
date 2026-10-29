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

describe('B27.9 — anuncios das operacoes dinamicas', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('anuncia a adicao e a remocao de seccoes', () => {
        document.body.innerHTML = `
            <div id="contentor-seccoes"></div>
            <button id="botao-adicionar-seccao">
                Adicionar secção
            </button>
            <p
                data-estado-seccoes
                aria-live="polite"
                aria-atomic="true"
            ></p>
        `;

        const gestor = Object.create(
            GestorSeccoes.prototype,
        );

        gestor.contentor = document.getElementById(
            'contentor-seccoes',
        );

        gestor.botaoAdicionar = document.getElementById(
            'botao-adicionar-seccao',
        );

        gestor.indiceSeguinte = 0;

        gestor.criarSeccaoDoModelo = vi.fn((indice) => {
            const seccao = document.createElement('section');
            seccao.className = 'item-seccao';
            seccao.dataset.indiceSeccao = String(indice);

            seccao.innerHTML = `
                <select class="seletor-tipo-seccao">
                    <option value="1">Texto</option>
                </select>
                <button class="botao-remover-seccao">
                    Remover
                </button>
            `;

            return seccao;
        });

        gestor.atualizarEstadoSeccao = vi.fn();
        gestor.aoAdicionarSeccao = null;
        gestor.destruirTooltipsDaSeccao = vi.fn();
        gestor.destruirTomSelectDaSeccao = vi.fn();

        const estado = document.querySelector(
            '[data-estado-seccoes]',
        );

        const seccao = gestor.adicionar();

        expect(gestor.contentor.children).toHaveLength(1);

        expect(estado.textContent.trim()).toBe(
            'Secção adicionada. 1 secção.',
        );

        const remover = seccao.querySelector(
            '.botao-remover-seccao',
        );

        remover.focus();

        expect(gestor.remover(remover)).toBe(true);

        expect(gestor.contentor.children).toHaveLength(0);

        expect(estado.textContent.trim()).toBe(
            'Secção removida. 0 secções.',
        );
    });

    it('anuncia a adicao e a remocao de ligacoes', () => {
        document.body.innerHTML = `
            <section class="item-seccao">
                <div data-lista-ligacoes-seccao>
                    <div data-ligacao-seccao>
                        <input data-campo-url-ligacao>
                        <button
                            data-acao-ligacao-seccao="remover"
                        >
                            Remover
                        </button>
                    </div>
                </div>

                <template data-modelo-ligacao-seccao>
                    <div data-ligacao-seccao>
                        <input data-campo-url-ligacao>
                        <button
                            data-acao-ligacao-seccao="remover"
                        >
                            Remover
                        </button>
                    </div>
                </template>

                <button
                    data-acao-ligacao-seccao="adicionar"
                >
                    Adicionar
                </button>

                <p
                    data-estado-ligacoes-seccao
                    aria-live="polite"
                    aria-atomic="true"
                >1 de 3 ligações.</p>
            </section>
        `;

        const gestor = Object.create(
            GestorLigacoesSecao.prototype,
        );

        gestor.lista = document.querySelector(
            '[data-lista-ligacoes-seccao]',
        );

        gestor.modelo = document.querySelector(
            '[data-modelo-ligacao-seccao]',
        );

        gestor.botaoAdicionar = document.querySelector(
            '[data-acao-ligacao-seccao="adicionar"]',
        );

        gestor.estado = document.querySelector(
            '[data-estado-ligacoes-seccao]',
        );

        gestor.maximoLigacoes = 3;

        gestor.obterLinhas = () => Array.from(
            gestor.lista.querySelectorAll(
                ':scope > [data-ligacao-seccao]',
            ),
        );

        gestor.normalizar = vi.fn(() => {
            gestor.estado.textContent =
                `${gestor.obterLinhas().length} de 3 ligações.`;
        });

        const adicionada = gestor.adicionar();

        expect(adicionada).toBeInstanceOf(HTMLElement);

        expect(gestor.estado.textContent.trim()).toBe(
            '2 de 3 ligações. Ligação adicionada.',
        );

        const remover = adicionada.querySelector(
            '[data-acao-ligacao-seccao="remover"]',
        );

        expect(gestor.remover(remover)).toBe(true);

        expect(gestor.estado.textContent.trim()).toBe(
            '1 de 3 ligações. Ligação removida.',
        );
    });

    it('anuncia a nova posicao de uma ligacao', () => {
        document.body.innerHTML = `
            <div data-lista-ligacoes-seccao>
                ${[0, 1].map((indice) => `
                    <div
                        data-ligacao-seccao
                        data-indice="${indice}"
                    >
                        <button
                            data-acao-ligacao-seccao="subir"
                        >
                            Subir
                        </button>
                    </div>
                `).join('')}
            </div>

            <p
                data-estado-ligacoes-seccao
                aria-live="polite"
                aria-atomic="true"
            >2 de 3 ligações.</p>
        `;

        const gestor = Object.create(
            GestorLigacoesSecao.prototype,
        );

        gestor.lista = document.querySelector(
            '[data-lista-ligacoes-seccao]',
        );

        gestor.estado = document.querySelector(
            '[data-estado-ligacoes-seccao]',
        );

        gestor.normalizar = vi.fn(() => {
            gestor.estado.textContent = '2 de 3 ligações.';
        });

        const linhas = Array.from(
            gestor.lista.querySelectorAll(
                '[data-ligacao-seccao]',
            ),
        );

        const botao = linhas[1].querySelector(
            '[data-acao-ligacao-seccao="subir"]',
        );

        botao.focus();

        expect(gestor.mover(botao, -1)).toBe(true);

        expect(
            Array.from(gestor.lista.children),
        ).toEqual([linhas[1], linhas[0]]);

        expect(gestor.estado.textContent.trim()).toBe(
            '2 de 3 ligações. Ligação movida para a posição 1 de 2.',
        );

        expect(document.activeElement).toBe(botao);
    });

    it('anuncia a adicao, reordenacao e remocao de faixas', () => {
        document.body.innerHTML = `
            <section class="item-seccao">
                <div data-editor-lancamento>
                    <div data-lista-faixas-lancamento></div>

                    <p
                        data-estado-faixas-lancamento
                        aria-live="polite"
                        aria-atomic="true"
                    ></p>

                    <button
                        data-acao-faixa-lancamento="adicionar"
                    >
                        Adicionar faixa
                    </button>
                </div>
            </section>
        `;

        const seccao = document.querySelector(
            '.item-seccao',
        );

        const lista = seccao.querySelector(
            '[data-lista-faixas-lancamento]',
        );

        const estado = seccao.querySelector(
            '[data-estado-faixas-lancamento]',
        );

        const editor = Object.create(
            EditorLancamentoDiscogs.prototype,
        );

        editor.obterEditor = vi.fn(() => {
            return seccao.querySelector(
                '[data-editor-lancamento]',
            );
        });

        editor.reindexarFaixas = vi.fn();

        seccao.addEventListener(
            'click',
            (evento) => editor.tratarClique(evento),
        );

        editor.adicionarFaixa(seccao);

        expect(lista.children).toHaveLength(1);

        expect(estado.textContent.trim()).toBe(
            'Faixa adicionada. 1 faixa.',
        );

        editor.adicionarFaixa(seccao);

        expect(lista.children).toHaveLength(2);

        expect(estado.textContent.trim()).toBe(
            'Faixa adicionada. 2 faixas.',
        );

        const linhas = Array.from(
            lista.querySelectorAll('[data-faixa-lancamento]'),
        );

        const subir = linhas[1].querySelector(
            '[data-acao-faixa-lancamento="subir"]',
        );

        subir.focus();
        subir.click();

        expect(Array.from(lista.children)).toEqual([
            linhas[1],
            linhas[0],
        ]);

        expect(estado.textContent.trim()).toBe(
            'Faixa movida para a posição 1 de 2.',
        );

        const remover = linhas[1].querySelector(
            '[data-acao-faixa-lancamento="remover"]',
        );

        remover.focus();
        remover.click();

        expect(lista.children).toHaveLength(1);

        expect(estado.textContent.trim()).toBe(
            'Faixa removida. 1 faixa.',
        );
    });
    it(
        'evita anuncios repetidos quando a contagem nao muda',
        () => {
            document.body.innerHTML = `
                <button id="adicionar-ligacao">
                    Adicionar ligação
                </button>
                <p
                    data-estado-ligacoes-seccao
                    aria-live="polite"
                    aria-atomic="true"
                >1 de 3 ligações.</p>
            `;

            const gestor = Object.create(
                GestorLigacoesSecao.prototype,
            );

            gestor.estado = document.querySelector(
                '[data-estado-ligacoes-seccao]',
            );

            gestor.botaoAdicionar = document.getElementById(
                'adicionar-ligacao',
            );

            gestor.maximoLigacoes = 3;
            gestor.obterLinhas = () => [
                document.createElement('div'),
            ];
            gestor.detalhesAtivos = () => true;

            const observador = new MutationObserver(() => {});

            observador.observe(gestor.estado, {
                childList: true,
                characterData: true,
                subtree: true,
            });

            try {
                gestor.atualizarEstadoGeral();

                expect(gestor.estado.textContent).toBe(
                    '1 de 3 ligações.',
                );

                expect(
                    observador.takeRecords(),
                ).toHaveLength(0);
            } finally {
                observador.disconnect();
            }
        },
    );


});
