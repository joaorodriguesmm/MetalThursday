import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorFiltrosDinamicos
    from '../../../resources/js/modulos/GestorFiltrosDinamicos.js';

const criarOpcoes = (
    quantidade,
) => Array.from(
    {
        length: quantidade,
    },
    (_, indice) => ({
        identificador:
            indice + 1,

        nome:
            `  Artista ${indice + 1}  `,
    }),
);

const criarGestor = (
    opcoesArtistas,
) => {
    document.body.innerHTML = [
        '<select id="lista-filtros"></select>',
        '<div id="filtros-ativos"></div>',
    ].join('');

    return new GestorFiltrosDinamicos({
        seletorListaFiltros:
            '#lista-filtros',

        seletorContentorFiltros:
            '#filtros-ativos',

        dadosFiltros: {
            artistas:
                opcoesArtistas,
        },

        filtrosDisponiveis: {
            artista: {
                parametro:
                    'artista',

                tipo:
                    'selecao',

                rotulo:
                    'Artista',

                chaveDados:
                    'artistas',
            },
        },
    });
};

describe(
    'GestorFiltrosDinamicos',
    () => {
        beforeEach(
            () => {
                window.history.replaceState(
                    {},
                    '',
                    '/',
                );
            },
        );

        it(
            'mantém no select nativo apenas a opção atual antes do Tom Select',
            () => {
                const gestor =
                    criarGestor(
                        criarOpcoes(
                            2000,
                        ),
                    );

                const configuracao =
                    gestor.filtrosDisponiveis
                        .artista;

                const campo =
                    gestor.criarCampoSelecao(
                        configuracao,
                        'filtro_artista',
                        'filtro_artista',
                        '1234',
                    );

                expect(
                    Array.from(
                        campo.options,
                    ).map(
                        (opcao) =>
                            opcao.value,
                    ),
                ).toEqual([
                    '',
                    '1234',
                ]);

                expect(
                    campo.value,
                ).toBe(
                    '1234',
                );

                expect(
                    campo.options[1]
                        .textContent,
                ).toBe(
                    'Artista 1234',
                );
            },
        );

        it(
            'entrega as opções diretamente ao Tom Select e preserva a seleção',
            async () => {
                const gestor =
                    criarGestor([
                        {
                            identificador: 1,
                            nome: '  Artista Um  ',
                        },
                        {
                            identificador: 2,
                            nome: '  Artista Dois  ',
                        },
                    ]);

                const configuracao =
                    gestor.filtrosDisponiveis
                        .artista;

                const campo =
                    gestor.criarCampoSelecao(
                        configuracao,
                        'filtro_artista',
                        'filtro_artista',
                        '2',
                    );

                const componente =
                    document.createElement(
                        'div',
                    );

                componente.append(
                    campo,
                );

                document.body.append(
                    componente,
                );

                gestor.componentesAtivos.set(
                    'filtro_artista',
                    componente,
                );

                let opcoesRecebidas = null;

                class TomSelectFalso {
                    constructor(
                        campoRecebido,
                        opcoes,
                    ) {
                        this.campo =
                            campoRecebido;

                        opcoesRecebidas =
                            opcoes;
                    }
                }

                gestor.carregarTomSelect =
                    vi.fn()
                        .mockResolvedValue(
                            TomSelectFalso,
                        );

                await gestor.inicializarTomSelect(
                    'filtro_artista',
                    campo,
                    configuracao,
                    '2',
                );

                expect(
                    opcoesRecebidas.options,
                ).toEqual([
                    {
                        identificador: 1,
                        nome: 'Artista Um',
                    },
                    {
                        identificador: 2,
                        nome: 'Artista Dois',
                    },
                ]);

                expect(
                    opcoesRecebidas.items,
                ).toEqual([
                    '2',
                ]);

                expect(
                    opcoesRecebidas.valueField,
                ).toBe(
                    'identificador',
                );

                expect(
                    opcoesRecebidas.labelField,
                ).toBe(
                    'nome',
                );

                expect(
                    opcoesRecebidas.searchField,
                ).toEqual([
                    'nome',
                ]);

                expect(
                    gestor.instanciasTomSelect.has(
                        'filtro_artista',
                    ),
                ).toBe(
                    true,
                );
            },
        );

        it(
            'materializa todas as opções no select nativo se o Tom Select falhar',
            async () => {
                const gestor =
                    criarGestor([
                        {
                            identificador: 1,
                            nome: '  Artista Um  ',
                        },
                        {
                            identificador: 2,
                            nome: '  Artista Dois  ',
                        },
                        {
                            identificador: 3,
                            nome: '  Artista Três  ',
                        },
                    ]);

                const configuracao =
                    gestor.filtrosDisponiveis
                        .artista;

                const campo =
                    gestor.criarCampoSelecao(
                        configuracao,
                        'filtro_artista',
                        'filtro_artista',
                        '2',
                    );

                const componente =
                    document.createElement(
                        'div',
                    );

                componente.append(
                    campo,
                );

                document.body.append(
                    componente,
                );

                gestor.componentesAtivos.set(
                    'filtro_artista',
                    componente,
                );

                gestor.carregarTomSelect =
                    vi.fn()
                        .mockRejectedValue(
                            new Error(
                                'Falha simulada.',
                            ),
                        );

                await gestor.inicializarTomSelect(
                    'filtro_artista',
                    campo,
                    configuracao,
                    '2',
                );

                expect(
                    Array.from(
                        campo.options,
                    ).map(
                        (opcao) => ({
                            valor:
                                opcao.value,

                            texto:
                                opcao.textContent,
                        }),
                    ),
                ).toEqual([
                    {
                        valor: '',
                        texto:
                            'Seleciona uma opção',
                    },
                    {
                        valor: '1',
                        texto:
                            'Artista Um',
                    },
                    {
                        valor: '2',
                        texto:
                            'Artista Dois',
                    },
                    {
                        valor: '3',
                        texto:
                            'Artista Três',
                    },
                ]);

                expect(
                    campo.value,
                ).toBe(
                    '2',
                );
            },
        );
    },
);
