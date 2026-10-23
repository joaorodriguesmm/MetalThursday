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

function criarGestor(aoAdicionar = null) {
    return new GestorSeccoes(
        '#contentor-seccoes-b25-23',
        '#adicionar-seccao-b25-23',
        '#modelo-seccao-b25-23',
        aoAdicionar,
    );
}

describe('B25.23 — idempotencia do GestorSeccoes', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <div id="contentor-seccoes-b25-23">
                <article
                    class="item-seccao"
                    data-indice-seccao="0"
                >
                    <select class="seletor-tipo-seccao">
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

                    <div class="linha-detalhes-seccao">
                        <input
                            name="seccoes[0][titulo]"
                            type="text"
                        >
                    </div>

                    <button
                        class="botao-remover-seccao"
                        type="button"
                    >
                        Remover
                    </button>
                </article>
            </div>

            <button
                id="adicionar-seccao-b25-23"
                type="button"
            >
                Adicionar
            </button>

            <template id="modelo-seccao-b25-23">
                <article
                    class="item-seccao"
                    data-indice-seccao="__INDICE_SECCAO__"
                >
                    <select class="seletor-tipo-seccao">
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

                    <div class="linha-detalhes-seccao">
                        <input
                            name="seccoes[__INDICE_SECCAO__][titulo]"
                            type="text"
                        >
                    </div>

                    <button
                        class="botao-remover-seccao"
                        type="button"
                    >
                        Remover
                    </button>
                </article>
            </template>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao duplica seccoes num clique com dois gestores', () => {
        criarGestor();
        criarGestor();

        document.getElementById(
            'adicionar-seccao-b25-23',
        ).click();

        const seccoes = document.querySelectorAll(
            '#contentor-seccoes-b25-23 > .item-seccao',
        );

        expect(seccoes).toHaveLength(2);

        expect(
            Array.from(
                seccoes,
                (seccao) => seccao.dataset.indiceSeccao,
            ),
        ).toEqual(['0', '1']);
    });

    it('reutiliza o gestor dos mesmos elementos', () => {
        const primeiro = criarGestor();
        const segundo = criarGestor();

        expect(segundo).toBe(primeiro);

        expect(
            segundo.indiceSeguinte,
        ).toBe(primeiro.indiceSeguinte);
    });

    it('emite apenas um evento por alteracao com dois gestores', () => {
        criarGestor();
        criarGestor();

        const seccao = document.querySelector(
            '#contentor-seccoes-b25-23 .item-seccao',
        );

        const selecao = seccao.querySelector(
            '.seletor-tipo-seccao',
        );

        const eventos = vi.fn();

        seccao.addEventListener(
            GestorSeccoes.EVENTO_ESTADO_ATUALIZADO,
            eventos,
        );

        selecao.value = '2';

        selecao.dispatchEvent(
            new Event('change', {
                bubbles: true,
            }),
        );

        expect(eventos).toHaveBeenCalledTimes(1);

        expect(
            eventos.mock.calls[0][0].detail,
        ).toEqual({
            exigeDetalhes: true,
        });
    });

    it('executa o callback de adicao apenas uma vez', () => {
        const aoAdicionar = vi.fn();

        criarGestor(aoAdicionar);
        criarGestor(aoAdicionar);

        document.getElementById(
            'adicionar-seccao-b25-23',
        ).click();

        expect(aoAdicionar).toHaveBeenCalledTimes(1);

        expect(
            document.querySelectorAll(
                '#contentor-seccoes-b25-23 > .item-seccao',
            ),
        ).toHaveLength(2);
    });

    it('permite criar um gestor apos substituir a DOM', () => {
        const primeiro = criarGestor();

        const contentorAnterior = primeiro.contentor;

        const novaLista = document.createElement('div');

        novaLista.id = 'contentor-seccoes-b25-23';

        const novaSelecao = document.createElement('select');

        novaSelecao.className = 'seletor-tipo-seccao';

        const novaSeccao = document.createElement('article');

        novaSeccao.className = 'item-seccao';
        novaSeccao.dataset.indiceSeccao = '0';
        novaSeccao.append(novaSelecao);

        novaLista.append(novaSeccao);

        contentorAnterior.replaceWith(novaLista);

        const segundo = criarGestor();

        expect(segundo).not.toBe(primeiro);

        expect(segundo.contentor).toBe(novaLista);

        expect(
            segundo.indiceSeguinte,
        ).toBe(1);

        const adicionada = segundo.adicionar();

        expect(
            adicionada.dataset.indiceSeccao,
        ).toBe('1');
    });

    it('mantem indices unicos apos remover e adicionar', () => {
        const gestor = criarGestor();

        const primeira = gestor.adicionar();

        expect(
            primeira.dataset.indiceSeccao,
        ).toBe('1');

        expect(
            gestor.remover(
                primeira.querySelector(
                    '.botao-remover-seccao',
                ),
            ),
        ).toBe(true);

        const segunda = gestor.adicionar();

        expect(
            segunda.dataset.indiceSeccao,
        ).toBe('2');

        expect(
            Array.from(
                document.querySelectorAll(
                    '#contentor-seccoes-b25-23 > .item-seccao',
                ),
                (seccao) => seccao.dataset.indiceSeccao,
            ),
        ).toEqual(['0', '2']);
    });

    it('preserva o callback original entre inicializacoes', () => {
        const callbackOriginal = vi.fn();
        const callbackNovo = vi.fn();

        const primeiro = criarGestor(
            callbackOriginal,
        );

        const segundo = criarGestor(
            callbackNovo,
        );

        expect(segundo).toBe(primeiro);

        document.getElementById(
            'adicionar-seccao-b25-23',
        ).click();

        expect(
            callbackOriginal,
        ).toHaveBeenCalledTimes(1);

        expect(
            callbackNovo,
        ).not.toHaveBeenCalled();

        expect(
            document.querySelectorAll(
                '#contentor-seccoes-b25-23 > .item-seccao',
            ),
        ).toHaveLength(2);
    });

    it('preserva o contador apos reutilizar o gestor', () => {
        const primeiro = criarGestor();

        const primeiraAdicao = primeiro.adicionar();

        expect(
            primeiraAdicao.dataset.indiceSeccao,
        ).toBe('1');

        const segundo = criarGestor();

        expect(segundo).toBe(primeiro);

        const segundaAdicao = segundo.adicionar();

        expect(
            segundaAdicao.dataset.indiceSeccao,
        ).toBe('2');

        expect(
            Array.from(
                document.querySelectorAll(
                    '#contentor-seccoes-b25-23 > .item-seccao',
                ),
                (seccao) => seccao.dataset.indiceSeccao,
            ),
        ).toEqual(['0', '1', '2']);
    });
});
