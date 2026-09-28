import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
} from 'vitest';

import EditorLancamentoDiscogs
    from '../../../resources/js/modulos/EditorLancamentoDiscogs.js';

describe(
    'EditorLancamentoDiscogs',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML =
                    criarFormulario();
            },
        );

        afterEach(
            () => {
                document.body.replaceChildren();
            },
        );

        it(
            'valida o contrato completo do snapshot importado',
            () => {
                const editor =
                    criarEditor();

                expect(
                    editor.dadosImportacaoValidos(
                        criarLancamento(),
                    ),
                ).toBe(true);

                expect(
                    editor.dadosImportacaoValidos({
                        ...criarLancamento(),
                        id: 0,
                    }),
                ).toBe(false);

                expect(
                    editor.dadosImportacaoValidos({
                        ...criarLancamento(),
                        titulo: '   ',
                    }),
                ).toBe(false);

                expect(
                    editor.dadosImportacaoValidos({
                        ...criarLancamento(),
                        ano_original: 0,
                    }),
                ).toBe(false);

                expect(
                    editor.dadosImportacaoValidos({
                        ...criarLancamento(),
                        faixas: [
                            {
                                id: 10,
                                musica_id: 20,
                                titulo: '',
                                posicao: 'A1',
                                ordem: 1,
                            },
                        ],
                    }),
                ).toBe(false);

                expect(
                    editor.dadosImportacaoValidos({
                        ...criarLancamento(),
                        faixas: [
                            {
                                id: -1,
                                musica_id: null,
                                titulo: 'Battery',
                                posicao: 'A1',
                                ordem: 1,
                            },
                        ],
                    }),
                ).toBe(false);
            },
        );

        it(
            'carrega metadados faixas e nomes de submissao',
            () => {
                const editor =
                    criarEditor();

                const seccao =
                    document.querySelector(
                        '.item-seccao',
                    );

                editor.carregarLancamento(
                    seccao,
                    criarLancamento(),
                );

                const contentorEditor =
                    seccao.querySelector(
                        '[data-editor-lancamento]',
                    );

                expect(
                    contentorEditor.hidden,
                ).toBe(false);

                expect(
                    contentorEditor.querySelector(
                        '[data-campo-titulo-lancamento]',
                    ).value,
                ).toBe(
                    'Master of Puppets',
                );

                expect(
                    contentorEditor.querySelector(
                        '[data-campo-tipo-lancamento]',
                    ).value,
                ).toBe('Album');

                expect(
                    contentorEditor.querySelector(
                        '[data-campo-ano-original-lancamento]',
                    ).value,
                ).toBe('1986');

                expect(
                    seccao.querySelector(
                        '[data-campo-titulo-seccao]',
                    ).value,
                ).toBe(
                    'Master of Puppets',
                );

                expect(
                    seccao.querySelector(
                        '[data-campo-ano-seccao]',
                    ).value,
                ).toBe('1986');

                const faixas =
                    Array.from(
                        contentorEditor
                            .querySelectorAll(
                                '[data-faixa-lancamento]',
                            ),
                    );

                expect(
                    faixas,
                ).toHaveLength(2);

                expect(
                    faixas[0].querySelector(
                        '[data-campo-titulo-faixa]',
                    ).value,
                ).toBe('Battery');

                expect(
                    faixas[0].querySelector(
                        '[data-campo-id-faixa]',
                    ).name,
                ).toBe(
                    'seccoes[4][lancamento][faixas][0][id]',
                );

                expect(
                    faixas[0].querySelector(
                        '[data-campo-musica-id-faixa]',
                    ).name,
                ).toBe(
                    'seccoes[4][lancamento][faixas][0][musica_id]',
                );

                expect(
                    faixas[0].querySelector(
                        '[data-campo-titulo-faixa]',
                    ).name,
                ).toBe(
                    'seccoes[4][lancamento][faixas][0][titulo]',
                );

                expect(
                    faixas[0].querySelector(
                        '[data-campo-posicao-faixa]',
                    ).name,
                ).toBe(
                    'seccoes[4][lancamento][faixas][0][posicao]',
                );

                expect(
                    faixas[0].querySelector(
                        '[data-campo-ordem-faixa]',
                    ).value,
                ).toBe('1');

                expect(
                    faixas[1].querySelector(
                        '[data-campo-ordem-faixa]',
                    ).value,
                ).toBe('2');

                expect(
                    faixas[0].querySelector(
                        '[data-acao-faixa-lancamento="subir"]',
                    ).disabled,
                ).toBe(true);

                expect(
                    faixas[1].querySelector(
                        '[data-acao-faixa-lancamento="descer"]',
                    ).disabled,
                ).toBe(true);
            },
        );

        it(
            'reordena a tracklist e recalcula nomes e ordem',
            () => {
                const editor =
                    criarEditor();

                const seccao =
                    document.querySelector(
                        '.item-seccao',
                    );

                editor.carregarLancamento(
                    seccao,
                    criarLancamento(),
                );

                const lista =
                    seccao.querySelector(
                        '[data-lista-faixas-lancamento]',
                    );

                const primeira =
                    lista.querySelector(
                        '[data-faixa-lancamento]',
                    );

                primeira.querySelector(
                    '[data-acao-faixa-lancamento="descer"]',
                ).click();

                const reordenadas =
                    Array.from(
                        lista.querySelectorAll(
                            '[data-faixa-lancamento]',
                        ),
                    );

                expect(
                    reordenadas[0].querySelector(
                        '[data-campo-titulo-faixa]',
                    ).value,
                ).toBe(
                    'Master of Puppets',
                );

                expect(
                    reordenadas[1].querySelector(
                        '[data-campo-titulo-faixa]',
                    ).value,
                ).toBe('Battery');

                expect(
                    reordenadas[0].querySelector(
                        '[data-campo-titulo-faixa]',
                    ).name,
                ).toBe(
                    'seccoes[4][lancamento][faixas][0][titulo]',
                );

                expect(
                    reordenadas[0].querySelector(
                        '[data-campo-ordem-faixa]',
                    ).value,
                ).toBe('1');

                expect(
                    reordenadas[1].querySelector(
                        '[data-campo-titulo-faixa]',
                    ).name,
                ).toBe(
                    'seccoes[4][lancamento][faixas][1][titulo]',
                );

                expect(
                    reordenadas[1].querySelector(
                        '[data-campo-ordem-faixa]',
                    ).value,
                ).toBe('2');
            },
        );

        it(
            'limpa editor tracklist e campos espelho',
            () => {
                const editor =
                    criarEditor();

                const seccao =
                    document.querySelector(
                        '.item-seccao',
                    );

                editor.carregarLancamento(
                    seccao,
                    criarLancamento(),
                );

                editor.limpar(
                    seccao,
                );

                const contentorEditor =
                    seccao.querySelector(
                        '[data-editor-lancamento]',
                    );

                expect(
                    contentorEditor.hidden,
                ).toBe(true);

                expect(
                    contentorEditor.querySelector(
                        '[data-campo-titulo-lancamento]',
                    ).value,
                ).toBe('');

                expect(
                    contentorEditor.querySelector(
                        '[data-campo-tipo-lancamento]',
                    ).value,
                ).toBe('');

                expect(
                    contentorEditor.querySelector(
                        '[data-campo-ano-original-lancamento]',
                    ).value,
                ).toBe('');

                expect(
                    contentorEditor.querySelectorAll(
                        '[data-faixa-lancamento]',
                    ),
                ).toHaveLength(0);

                expect(
                    seccao.querySelector(
                        '[data-campo-titulo-seccao]',
                    ).value,
                ).toBe('');

                expect(
                    seccao.querySelector(
                        '[data-campo-ano-seccao]',
                    ).value,
                ).toBe('');
            },
        );
    },
);

/**
 * Cria o editor associado ao formulário de teste.
 *
 * @returns {EditorLancamentoDiscogs} Editor criado.
 *
 * @since 2.0.0
 */
function criarEditor() {
    return new EditorLancamentoDiscogs(
        document.getElementById(
            'formulario-metal-thursday',
        ),
    );
}

/**
 * Cria um snapshot válido de lançamento com duas faixas.
 *
 * @returns {object} Dados de lançamento.
 *
 * @since 2.0.0
 */
function criarLancamento() {
    return {
        id: 77,
        discogs_release_id: 12345,
        titulo: 'Master of Puppets',
        tipo: 'Album',
        ano_original: 1986,
        faixas: [
            {
                id: 101,
                musica_id: 201,
                titulo: 'Battery',
                posicao: 'A1',
                ordem: 1,
            },
            {
                id: 102,
                musica_id: 202,
                titulo: 'Master of Puppets',
                posicao: 'A2',
                ordem: 2,
            },
        ],
    };
}

/**
 * Cria a estrutura mínima do editor de lançamento.
 *
 * @returns {string} HTML do formulário.
 *
 * @since 2.0.0
 */
function criarFormulario() {
    return `
        <form id="formulario-metal-thursday">
            <article class="item-seccao">
                <input
                    type="hidden"
                    name="seccoes[4][lancamento_id]"
                    value="77"
                >

                <div class="coluna-titulo-seccao">
                    <input
                        type="text"
                        data-campo-titulo-seccao
                        value=""
                    >
                </div>

                <div class="coluna-ano-seccao">
                    <input
                        type="number"
                        data-campo-ano-seccao
                        value=""
                    >
                </div>

                <div
                    data-importacao-lancamento
                    data-nome-base-campo="seccoes[4]"
                >
                    <div
                        data-editor-lancamento
                        hidden
                    >
                        <input
                            type="text"
                            data-campo-titulo-lancamento
                        >

                        <input
                            type="text"
                            data-campo-tipo-lancamento
                        >

                        <input
                            type="number"
                            data-campo-ano-original-lancamento
                        >

                        <div
                            data-lista-faixas-lancamento
                        ></div>
                    </div>
                </div>
            </article>
        </form>
    `;
}
