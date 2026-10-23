import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorEdicaoMetalThursday
    from '../../../resources/js/modulos/GestorEdicaoMetalThursday.js';

function criarEdicao({
    id,
    nome,
    inicio,
    fim,
}) {
    const elemento = document.createElement('div');

    elemento.dataset.edicaoIdentificador =
        String(id);

    elemento.dataset.edicaoNome = nome;

    elemento.dataset.edicaoInicio = inicio;

    elemento.dataset.edicaoFim = fim;

    return elemento;
}

function criarGestor() {
    document.body.innerHTML = `
        <input
            id="data-metal-thursday"
            name="data"
            type="date"
            value="2026-07-10"
        >

        <input
            id="edicao-metal-thursday"
            type="text"
        >

        <div id="estado-edicao-metal-thursday"></div>

        <div
            id="dados-edicoes-metal-thursday"
            data-data-referencia="2026-10-23"
        ></div>
    `;

    const campoData = document.getElementById(
        'data-metal-thursday',
    );

    const campoEdicao = document.getElementById(
        'edicao-metal-thursday',
    );

    const elementoEstado = document.getElementById(
        'estado-edicao-metal-thursday',
    );

    const contentorDados = document.getElementById(
        'dados-edicoes-metal-thursday',
    );

    contentorDados.append(
        criarEdicao({
            id: 1,
            nome: 'Edição A',
            inicio: '2026-01-01',
            fim: '2026-06-30',
        }),
        criarEdicao({
            id: 2,
            nome: 'Edição B',
            inicio: '2026-07-01',
            fim: '2026-12-31',
        }),
    );

    const gestor = new GestorEdicaoMetalThursday({
        campoData,
        campoEdicao,
        elementoEstado,
        contentorDados,
    });

    return {
        gestor,
        campoData,
        campoEdicao,
        elementoEstado,
    };
}

function obterEstado(gestor) {
    return gestor.edicoes.map(
        (edicao) => ({
            ...edicao,
        }),
    );
}

describe(
    'B25.28 — integridade das edicoes MetalThursday',
    () => {
        let contexto;

        beforeEach(() => {
            contexto = criarGestor();
        });

        afterEach(() => {
            vi.restoreAllMocks();
            document.body.replaceChildren();
        });

        it(
            'preserva o estado ao rejeitar uma nova edicao sobreposta',
            () => {
                const {
                    gestor,
                    campoData,
                    campoEdicao,
                } = contexto;

                const anteriores = obterEstado(gestor);

                const emitirEvento = vi.spyOn(
                    campoData,
                    'dispatchEvent',
                );

                expect(campoEdicao.value).toBe(
                    'Edição B',
                );

                expect(
                    () => gestor.adicionarEdicao({
                        id: 3,
                        nome: 'Edição sobreposta',
                        data_inicio: '2026-06-15',
                        data_fim: '2026-07-15',
                    }),
                ).toThrow(
                    'Os períodos das edições apresentados no formulário sobrepõem-se.',
                );

                expect(gestor.edicoes).toEqual(
                    anteriores,
                );

                expect(
                    () => gestor.validarIntegridadeTemporal(),
                ).not.toThrow();

                expect(
                    gestor.obterEdicaoParaData(
                        '2026-07-10',
                    )?.nome,
                ).toBe('Edição B');

                expect(campoEdicao.value).toBe(
                    'Edição B',
                );

                expect(emitirEvento).not.toHaveBeenCalled();
            },
        );

        it(
            'preserva a edicao anterior quando uma atualizacao se sobrepoe',
            () => {
                const {
                    gestor,
                    campoData,
                    campoEdicao,
                } = contexto;

                const anteriores = obterEstado(gestor);

                const emitirEvento = vi.spyOn(
                    campoData,
                    'dispatchEvent',
                );

                expect(
                    () => gestor.adicionarEdicao({
                        id: 2,
                        nome: 'Edição B inválida',
                        data_inicio: '2026-06-01',
                        data_fim: '2026-12-31',
                    }),
                ).toThrow(
                    'Os períodos das edições apresentados no formulário sobrepõem-se.',
                );

                expect(gestor.edicoes).toEqual(
                    anteriores,
                );

                expect(
                    () => gestor.validarIntegridadeTemporal(),
                ).not.toThrow();

                expect(
                    gestor.obterEdicaoParaData(
                        '2026-07-10',
                    )?.nome,
                ).toBe('Edição B');

                expect(campoEdicao.value).toBe(
                    'Edição B',
                );

                expect(emitirEvento).not.toHaveBeenCalled();
            },
        );

        it(
            'continua operacional depois de rejeitar uma edicao sobreposta',
            () => {
                const {
                    gestor,
                    campoData,
                    campoEdicao,
                } = contexto;

                expect(
                    () => gestor.adicionarEdicao({
                        id: 3,
                        nome: 'Edição inválida',
                        data_inicio: '2026-06-15',
                        data_fim: '2026-07-15',
                    }),
                ).toThrow();

                expect(
                    () => gestor.adicionarEdicao({
                        id: 3,
                        nome: 'Edição C',
                        data_inicio: '2027-01-01',
                        data_fim: '2027-12-31',
                    }),
                ).not.toThrow();

                expect(
                    gestor.validarData(
                        '2027-02-01',
                    ),
                ).toBe(true);

                expect(
                    gestor.obterEdicaoParaData(
                        '2027-02-01',
                    )?.nome,
                ).toBe('Edição C');

                expect(campoData.value).toBe(
                    '2026-07-10',
                );

                expect(campoEdicao.value).toBe(
                    'Edição B',
                );
            },
        );

        it(
            'rejeita dados invalidos sem modificar a lista',
            () => {
                const { gestor } = contexto;

                const anteriores = obterEstado(gestor);

                expect(
                    () => gestor.adicionarEdicao({
                        id: 3,
                        nome: 'Datas inválidas',
                        data_inicio: '2027-09-01',
                        data_fim: '2027-08-31',
                    }),
                ).toThrow(
                    'Os dados da edição criada são inválidos.',
                );

                expect(gestor.edicoes).toEqual(
                    anteriores,
                );
            },
        );

        it(
            'aceita periodos consecutivos sem sobreposicao',
            () => {
                const { gestor } = contexto;

                expect(
                    () => gestor.adicionarEdicao({
                        id: 3,
                        nome: 'Edição C',
                        data_inicio: '2027-01-01',
                        data_fim: '2027-12-31',
                    }),
                ).not.toThrow();

                expect(
                    gestor.obterEdicaoParaData(
                        '2026-12-31',
                    )?.nome,
                ).toBe('Edição B');

                expect(
                    gestor.obterEdicaoParaData(
                        '2027-01-01',
                    )?.nome,
                ).toBe('Edição C');

                expect(
                    gestor.edicoes,
                ).toHaveLength(3);
            },
        );

        it(
            'atualiza uma edicao valida e emite um unico evento',
            () => {
                const {
                    gestor,
                    campoData,
                    campoEdicao,
                } = contexto;

                const emitirEvento = vi.spyOn(
                    campoData,
                    'dispatchEvent',
                );

                gestor.adicionarEdicao({
                    id: 2,
                    nome: 'Edição B atualizada',
                    data_inicio: '2026-07-01',
                    data_fim: '2026-12-31',
                });

                expect(gestor.edicoes).toHaveLength(2);

                expect(
                    gestor.obterEdicaoParaData(
                        '2026-07-10',
                    )?.nome,
                ).toBe(
                    'Edição B atualizada',
                );

                expect(campoEdicao.value).toBe(
                    'Edição B atualizada',
                );

                expect(
                    emitirEvento,
                ).toHaveBeenCalledTimes(1);
            },
        );
    },
);
