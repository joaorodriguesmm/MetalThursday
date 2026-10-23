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

function criarGestor() {
    return new GestorEdicaoMetalThursday({
        campoData:
            document.getElementById('campo-data'),

        campoEdicao:
            document.getElementById('campo-edicao'),

        elementoEstado:
            document.getElementById('estado-edicao'),

        contentorDados:
            document.getElementById('dados-edicoes'),
    });
}

function criarDados(id) {
    return {
        id,
        nome: 'Edição de teste',
        data_inicio: '2026-01-01',
        data_fim: '2026-12-31',
    };
}

describe(
    'B25.29 — normalizacao de identificadores de edicoes',
    () => {
        beforeEach(() => {
            document.body.innerHTML = `
                <input
                    id="campo-data"
                    type="date"
                    value="2026-07-10"
                >

                <input id="campo-edicao">

                <div id="estado-edicao"></div>

                <div
                    id="dados-edicoes"
                    data-data-referencia="2026-10-23"
                >
                    <div
                        data-edicao-identificador="1"
                        data-edicao-nome="Edição original"
                        data-edicao-inicio="2026-01-01"
                        data-edicao-fim="2026-12-31"
                    ></div>
                </div>
            `;
        });

        afterEach(() => {
            vi.restoreAllMocks();
            document.body.replaceChildren();
        });

        it(
            'aceita numeros e strings decimais legitimos',
            () => {
                const gestor = criarGestor();

                for (const id of [
                    1,
                    '1',
                    123,
                    '123',
                    Number.MAX_SAFE_INTEGER,
                    String(Number.MAX_SAFE_INTEGER),
                ]) {
                    expect(
                        gestor.normalizarEdicao(
                            criarDados(id),
                        )?.identificador,
                    ).toBe(Number(id));
                }
            },
        );

        it(
            'rejeita conversoes implicitas e formatos ambiguos',
            () => {
                const gestor = criarGestor();

                const entradas = [
                    ['booleano', true],
                    ['array', [1]],
                    ['notacao exponencial', '1e2'],
                    ['hexadecimal', '0x10'],
                    ['sinal positivo', '+1'],
                    ['espacos', ' 1 '],
                    ['zeros iniciais', '01'],
                    ['decimal fracionario textual', '1.0'],
                ];

                const indevidamenteAceites =
                    entradas
                        .filter(([, id]) => (
                            gestor.normalizarEdicao(
                                criarDados(id),
                            ) !== null
                        ))
                        .map(([descricao]) => descricao);

                expect(
                    indevidamenteAceites,
                ).toEqual([]);
            },
        );

        it(
            'rejeita identificadores superiores ao inteiro seguro',
            () => {
                const gestor = criarGestor();

                const entradas = [
                    Number.MAX_SAFE_INTEGER + 1,
                    '9007199254740992',
                ];

                for (const id of entradas) {
                    expect(
                        gestor.normalizarEdicao(
                            criarDados(id),
                        ),
                    ).toBeNull();
                }
            },
        );

        it(
            'rejeita identificadores ambiguos provenientes da HTML',
            () => {
                const elemento =
                    document.querySelector(
                        '[data-edicao-identificador]',
                    );

                elemento.dataset.edicaoIdentificador =
                    '1e2';

                expect(
                    () => criarGestor(),
                ).toThrow(
                    'Foi encontrado um registo de edição inválido.',
                );
            },
        );

        it(
            'preserva o estado quando recebe um identificador invalido',
            () => {
                const gestor = criarGestor();

                const anteriores = gestor.edicoes.map(
                    (edicao) => ({
                        ...edicao,
                    }),
                );

                const campoData =
                    document.getElementById(
                        'campo-data',
                    );

                const emitirEvento = vi.spyOn(
                    campoData,
                    'dispatchEvent',
                );

                expect(
                    () => gestor.adicionarEdicao({
                        ...criarDados(true),
                        nome: 'Substituição indevida',
                    }),
                ).toThrow(
                    'Os dados da edição criada são inválidos.',
                );

                expect(
                    gestor.edicoes,
                ).toEqual(anteriores);

                expect(
                    emitirEvento,
                ).not.toHaveBeenCalled();
            },
        );

        it(
            'continua a aceitar uma atualizacao valida',
            () => {
                const gestor = criarGestor();

                gestor.adicionarEdicao({
                    ...criarDados('1'),
                    nome: 'Edição atualizada',
                });

                expect(
                    gestor.edicoes,
                ).toHaveLength(1);

                expect(
                    gestor.edicoes[0].identificador,
                ).toBe(1);

                expect(
                    document.getElementById(
                        'campo-edicao',
                    ).value,
                ).toBe('Edição atualizada');
            },
        );
    },
);
