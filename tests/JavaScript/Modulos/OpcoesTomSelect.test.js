import {
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import {
    adicionarOpcaoTomSelect,
    obterOpcaoResposta,
} from '../../../resources/js/modulos/OpcoesTomSelect.js';

describe(
    'OpcoesTomSelect',
    () => {
        it(
            'normaliza uma opção válida recebida por AJAX',
            () => {
                expect(
                    obterOpcaoResposta(
                        {
                            artista: {
                                id: 7,
                                nome: '  Alcest  ',
                            },
                        },
                        'artista',
                        'nome',
                    ),
                ).toEqual({
                    identificador: 7,
                    nome: 'Alcest',
                });
            },
        );

        it(
            'rejeita uma opção com identificador inválido',
            () => {
                expect(
                    obterOpcaoResposta(
                        {
                            artista: {
                                id: 0,
                                nome: 'Alcest',
                            },
                        },
                        'artista',
                        'nome',
                    ),
                ).toBeNull();
            },
        );

        it(
            'adiciona uma opção e preserva a seleção múltipla',
            () => {
                const seletor =
                    document.createElement(
                        'select',
                    );

                seletor.multiple = true;

                const adicionarOpcao =
                    vi.fn();

                const definirValor =
                    vi.fn();

                const atualizarOpcoes =
                    vi.fn();

                const instancia = {
                    addOption:
                        adicionarOpcao,

                    input:
                        seletor,

                    items: [
                        '1',
                        '2',
                    ],

                    options: {},

                    refreshOptions:
                        atualizarOpcoes,

                    setValue:
                        definirValor,
                };

                expect(
                    adicionarOpcaoTomSelect(
                        instancia,
                        3,
                        '  Nova opção  ',
                        true,
                    ),
                ).toBe(
                    true,
                );

                expect(
                    adicionarOpcao,
                ).toHaveBeenCalledWith({
                    value: '3',
                    text: 'Nova opção',
                });

                expect(
                    definirValor,
                ).toHaveBeenCalledWith([
                    '1',
                    '2',
                    '3',
                ]);

                expect(
                    atualizarOpcoes,
                ).toHaveBeenCalledWith(
                    false,
                );
            },
        );
    },
);
