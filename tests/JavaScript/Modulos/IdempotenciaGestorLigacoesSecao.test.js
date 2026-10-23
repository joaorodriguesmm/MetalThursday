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

import GestorSeccoes
    from '../../../resources/js/modulos/GestorSeccoes.js';

function criarLinha(identificador) {
    return `
        <div
            data-ligacao-seccao
            data-identificador-teste="${identificador}"
        >
            <input
                type="url"
                data-campo-url-ligacao
                value="https://example.com/${identificador}"
            >

            <span data-plataforma-ligacao></span>

            <div data-contentor-etiqueta-ligacao>
                <input
                    type="text"
                    data-campo-etiqueta-ligacao
                >
            </div>

            <input
                type="checkbox"
                data-campo-incorporar-ligacao
            >

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

function criarHtmlSeccao() {
    return `
        <article
            id="seccao-b25-24"
            class="item-seccao"
            data-indice-seccao="5"
        >
            <div class="linha-detalhes-seccao">
                <div
                    data-editor-ligacoes-seccao
                    data-nome-base-campo="seccoes[5]"
                    data-maximo-ligacoes="8"
                >
                    <div data-lista-ligacoes-seccao>
                        ${criarLinha('a')}
                        ${criarLinha('b')}
                        ${criarLinha('c')}
                    </div>

                    <template data-modelo-ligacao-seccao>
                        ${criarLinha('__INDICE_LIGACAO__')}
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
}

function obterSeccao() {
    return document.getElementById(
        'seccao-b25-24',
    );
}

function criarGestor() {
    return new GestorLigacoesSecao(
        obterSeccao(),
    );
}

function obterIdentificadores() {
    return Array.from(
        document.querySelectorAll(
            '[data-lista-ligacoes-seccao] > [data-ligacao-seccao]',
        ),
        (linha) => linha.dataset.identificadorTeste,
    );
}

describe('B25.24 — idempotencia do gestor de ligacoes', () => {
    beforeEach(() => {
        document.body.innerHTML = criarHtmlSeccao();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('reutiliza o gestor da mesma seccao', () => {
        const primeiro = criarGestor();
        const segundo = criarGestor();

        expect(segundo).toBe(primeiro);

        expect(segundo.editor).toBe(primeiro.editor);

        expect(segundo.lista).toBe(primeiro.lista);
    });

    it('adiciona apenas uma ligacao por clique', () => {
        criarGestor();
        criarGestor();

        document.querySelector(
            '[data-acao-ligacao-seccao="adicionar"]',
        ).click();

        expect(
            document.querySelectorAll(
                '[data-lista-ligacoes-seccao] > [data-ligacao-seccao]',
            ),
        ).toHaveLength(4);
    });

    it('move a ligacao apenas uma posicao por clique', () => {
        criarGestor();
        criarGestor();

        const primeira = document.querySelector(
            '[data-identificador-teste="a"]',
        );

        primeira.querySelector(
            '[data-acao-ligacao-seccao="descer"]',
        ).click();

        expect(
            obterIdentificadores(),
        ).toEqual([
            'b',
            'a',
            'c',
        ]);
    });

    it('processa uma alteracao de URL apenas uma vez', () => {
        criarGestor();
        criarGestor();

        const atualizar = vi.spyOn(
            GestorLigacoesSecao.prototype,
            'atualizarEstadoLinha',
        );

        const campo = document.querySelector(
            '[data-identificador-teste="a"] [data-campo-url-ligacao]',
        );

        campo.value =
            'https://music.apple.com/pt/album/exemplo';

        campo.dispatchEvent(
            new Event('input', {
                bubbles: true,
            }),
        );

        expect(atualizar).toHaveBeenCalledTimes(1);
    });

    it('normaliza apenas uma vez por evento de estado', () => {
        criarGestor();
        criarGestor();

        const normalizar = vi.spyOn(
            GestorLigacoesSecao.prototype,
            'normalizar',
        );

        obterSeccao().dispatchEvent(
            new CustomEvent(
                GestorSeccoes.EVENTO_ESTADO_ATUALIZADO,
                {
                    detail: {
                        exigeDetalhes: true,
                    },
                },
            ),
        );

        expect(normalizar).toHaveBeenCalledTimes(1);
    });

    it('cria novo gestor apos substituir a seccao HTML', () => {
        const primeiro = criarGestor();

        const seccaoAnterior = primeiro.seccao;

        document.body.innerHTML = criarHtmlSeccao();

        const segundo = criarGestor();

        expect(segundo).not.toBe(primeiro);

        expect(segundo.seccao).not.toBe(
            seccaoAnterior,
        );

        expect(segundo.seccao).toBe(
            obterSeccao(),
        );

        expect(segundo.obterLinhas()).toHaveLength(3);

        document.querySelector(
            '[data-acao-ligacao-seccao="adicionar"]',
        ).click();

        expect(segundo.obterLinhas()).toHaveLength(4);
    });

    it('reconhece a substituicao do editor dentro da mesma seccao', () => {
        const primeiro = criarGestor();

        const editorAnterior = primeiro.editor;

        const editorNovo = editorAnterior.cloneNode(true);

        editorAnterior.replaceWith(editorNovo);

        const segundo = criarGestor();

        expect(segundo.editor).toBe(editorNovo);

        expect(
            segundo.lista.isConnected,
        ).toBe(true);

        editorNovo.querySelector(
            '[data-acao-ligacao-seccao="adicionar"]',
        ).click();

        expect(
            obterIdentificadores(),
        ).toHaveLength(4);
    });

    it('reconhece a substituicao apenas da lista de ligacoes', () => {
        const primeiro = criarGestor();

        const listaAnterior = primeiro.lista;

        const listaNova = listaAnterior.cloneNode(true);

        listaAnterior.replaceWith(listaNova);

        const segundo = criarGestor();

        expect(segundo.lista).toBe(listaNova);

        document.querySelector(
            '[data-acao-ligacao-seccao="adicionar"]',
        ).click();

        expect(
            obterIdentificadores(),
        ).toHaveLength(4);

        expect(
            segundo.obterLinhas(),
        ).toHaveLength(4);
    });

    it('liberta o listener de estado do gestor substituido', () => {
        const primeiro = criarGestor();

        const editorNovo = primeiro.editor.cloneNode(true);

        primeiro.editor.replaceWith(editorNovo);

        const segundo = criarGestor();

        expect(segundo).not.toBe(primeiro);
        expect(segundo.editor).toBe(editorNovo);

        const normalizar = vi.spyOn(
            GestorLigacoesSecao.prototype,
            'normalizar',
        );

        obterSeccao().dispatchEvent(
            new CustomEvent(
                GestorSeccoes.EVENTO_ESTADO_ATUALIZADO,
                {
                    detail: {
                        exigeDetalhes: true,
                    },
                },
            ),
        );

        expect(normalizar).toHaveBeenCalledTimes(1);

        expect(
            primeiro.controladorEventos.signal.aborted,
        ).toBe(true);

        expect(
            segundo.controladorEventos.signal.aborted,
        ).toBe(false);
    });
});
