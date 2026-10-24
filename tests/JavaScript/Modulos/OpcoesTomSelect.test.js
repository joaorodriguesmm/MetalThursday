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

        it(
            'aceita o maior identificador inteiro seguro numa resposta',
            () => {
                expect(
                    obterOpcaoResposta(
                        {
                            artista: {
                                id: Number.MAX_SAFE_INTEGER,
                                nome: 'Artista válido',
                            },
                        },
                        'artista',
                        'nome',
                    ),
                ).toEqual({
                    identificador: Number.MAX_SAFE_INTEGER,
                    nome: 'Artista válido',
                });
            },
        );

        it(
            'rejeita identificadores inteiros inseguros numa resposta',
            () => {
                expect(
                    obterOpcaoResposta(
                        {
                            artista: {
                                id: Number.MAX_SAFE_INTEGER + 1,
                                nome: 'Artista inválido',
                            },
                        },
                        'artista',
                        'nome',
                    ),
                ).toBeNull();
            },
        );

        it(
            'adiciona uma opcao com identificador inteiro seguro',
            () => {
                const adicionar = vi.fn();

                const instancia = {
                    addOption: adicionar,
                    options: {},
                };

                expect(
                    adicionarOpcaoTomSelect(
                        instancia,
                        Number.MAX_SAFE_INTEGER,
                        'Opção válida',
                    ),
                ).toBe(true);

                expect(adicionar).toHaveBeenCalledWith({
                    value: String(Number.MAX_SAFE_INTEGER),
                    text: 'Opção válida',
                });
            },
        );

        it(
            'rejeita identificadores inteiros inseguros antes de adicionar',
            () => {
                const adicionar = vi.fn();
                const atualizar = vi.fn();
                const selecionar = vi.fn();

                const instancia = {
                    addOption: adicionar,
                    addItem: selecionar,
                    options: {},
                    refreshOptions: atualizar,
                };

                expect(
                    adicionarOpcaoTomSelect(
                        instancia,
                        Number.MAX_SAFE_INTEGER + 1,
                        'Opção inválida',
                        true,
                    ),
                ).toBe(false);

                expect(adicionar).not.toHaveBeenCalled();
                expect(atualizar).not.toHaveBeenCalled();
                expect(selecionar).not.toHaveBeenCalled();
            },
        );


        it(
            'seleciona a nova opcao atraves de addItem',
            () => {
                const adicionar = vi.fn();
                const selecionar = vi.fn();
                const definirValor = vi.fn();

                const instancia = {
                    addOption: adicionar,
                    addItem: selecionar,
                    setValue: definirValor,
                    options: {},
                };

                expect(
                    adicionarOpcaoTomSelect(
                        instancia,
                        44,
                        'Artista novo',
                        true,
                    ),
                ).toBe(true);

                expect(
                    adicionar,
                ).toHaveBeenCalledExactlyOnceWith({
                    value: '44',
                    text: 'Artista novo',
                });

                expect(
                    selecionar,
                ).toHaveBeenCalledExactlyOnceWith('44');

                expect(
                    definirValor,
                ).not.toHaveBeenCalled();
            },
        );

        it(
            'rejeita selecao impossivel sem alterar a instancia',
            () => {
                const adicionar = vi.fn();
                const atualizar = vi.fn();

                const instancia = {
                    addOption: adicionar,
                    options: {},
                    refreshOptions: atualizar,
                };

                expect(
                    adicionarOpcaoTomSelect(
                        instancia,
                        45,
                        'Artista sem selecao',
                        true,
                    ),
                ).toBe(false);

                expect(
                    adicionar,
                ).not.toHaveBeenCalled();

                expect(
                    atualizar,
                ).not.toHaveBeenCalled();
            },
        );

    },
);
