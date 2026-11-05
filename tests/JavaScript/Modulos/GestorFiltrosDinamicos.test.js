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

describe('remoção individual de filtros', () => {
    it('remove apenas o filtro selecionado', () => {
        window.history.replaceState({}, '', '/');

        const gestor = criarGestor([]);

        for (const [nome, rotulo] of [
            ['filtro_artista', 'Artista'],
            ['filtro_edicao', 'Edição'],
        ]) {
            const componente = gestor.criarComponenteFiltro(
                rotulo,
                nome,
                `campo-${nome}`,
                document.createElement('input'),
            );

            gestor.areaFiltrosAtivos.append(componente);
            gestor.componentesAtivos.set(nome, componente);
        }

        const botao = document.querySelector(
            '[data-remover-filtro="filtro_artista"]',
        );

        expect(botao).toBeInstanceOf(HTMLButtonElement);
        expect(botao.classList.contains('bi-x-lg')).toBe(true);
        expect(botao.getAttribute('aria-label')).toBe(
            'Remover o filtro Artista',
        );

        botao.click();

        expect(gestor.componentesAtivos.has('filtro_artista')).toBe(false);
        expect(gestor.componentesAtivos.has('filtro_edicao')).toBe(true);
        expect(gestor.areaFiltrosAtivos.children).toHaveLength(1);
    });
});

describe('apresentação dos filtros binários', () => {
    it('mantém o mesmo fundo dos restantes campos', () => {
        const gestor = criarGestor([]);

        const campoSimNao = gestor.criarCampoSimNao(
            'filtro_audicao',
            'campo-audicao',
            'nao',
        );

        const campoSelecao = gestor.criarCampoSelecao(
            gestor.filtrosDisponiveis.artista,
            'filtro_artista',
            'campo-artista',
            '',
        );

        const campoData = gestor.criarCampoData(
            'filtro_data',
            'campo-data',
            '',
        );

        for (const campo of [campoSimNao, campoSelecao, campoData]) {
            expect(campo.classList.contains('bg-dark')).toBe(true);
        }

        expect(campoSimNao.classList.contains('filtro-binario')).toBe(true);
        expect(campoSimNao.value).toBe('nao');

        expect(
            Array.from(campoSimNao.options).map(opcao => opcao.value),
        ).toEqual(['sim', 'nao']);

        expect(
            gestor.criarCampoSimNao('outro', 'outro', '').value,
        ).toBe('sim');
    });
});

describe('Tom Select dos filtros binários', () => {
    const criarGestorBinario = () => {
        window.history.replaceState({}, '', '/');

        document.body.innerHTML = [
            '<select id="lista-binarios"></select>',
            '<div id="area-binarios"></div>',
        ].join('');

        return new GestorFiltrosDinamicos({
            seletorListaFiltros: '#lista-binarios',
            seletorContentorFiltros: '#area-binarios',
            filtrosDisponiveis: {
                autoria_utilizador: {
                    parametro: 'autoria_utilizador',
                    tipo: 'sim_nao',
                    rotulo: 'Da minha autoria',
                },
            },
        });
    };

    it('utiliza Tom Select e preserva a opção selecionada', async () => {
        const gestor = criarGestorBinario();

        let configuracaoRecebida;
        let instanciaCriada;

        class TomSelectFalso {
            constructor(campo, configuracao) {
                this.input = campo;
                this.destroy = vi.fn();

                configuracaoRecebida = configuracao;
                instanciaCriada = this;
            }
        }

        gestor.carregarTomSelect = vi.fn()
            .mockResolvedValue(TomSelectFalso);

        expect(
            gestor.renderizar('autoria_utilizador', 'nao'),
        ).toBe(true);

        const campo = gestor.areaFiltrosAtivos.querySelector(
            '[name="filtro_autoria_utilizador"]',
        );

        expect(campo).toBeInstanceOf(HTMLSelectElement);

        await gestor.inicializacoesTomSelect.get(campo);

        expect(configuracaoRecebida.options).toEqual([
            {identificador: 'sim', nome: 'Sim'},
            {identificador: 'nao', nome: 'Não'},
        ]);

        expect(configuracaoRecebida.items).toEqual(['nao']);

        expect(
            gestor.instanciasTomSelect.get('filtro_autoria_utilizador'),
        ).toBe(instanciaCriada);

        expect(
            gestor.removerFiltro('filtro_autoria_utilizador'),
        ).toBe(true);

        expect(instanciaCriada.destroy).toHaveBeenCalledTimes(1);
    });

    it('preserva o seletor nativo quando Tom Select falha', async () => {
        const gestor = criarGestorBinario();

        gestor.carregarTomSelect = vi.fn()
            .mockRejectedValue(new Error('Falha simulada'));

        expect(
            gestor.renderizar('autoria_utilizador', 'nao'),
        ).toBe(true);

        const campo = gestor.areaFiltrosAtivos.querySelector(
            '[name="filtro_autoria_utilizador"]',
        );

        await gestor.inicializacoesTomSelect.get(campo);

        expect(
            Array.from(campo.options, opcao => opcao.value),
        ).toEqual(['sim', 'nao']);

        expect(campo.value).toBe('nao');

        expect(
            gestor.instanciasTomSelect.has('filtro_autoria_utilizador'),
        ).toBe(false);
    });
});
