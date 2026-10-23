import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorFiltrosDinamicos
    from '../../../resources/js/modulos/GestorFiltrosDinamicos.js';

function criarPromessaPendente() {
    let resolver;

    const promessa = new Promise((resolve) => {
        resolver = resolve;
    });

    return {promessa, resolver};
}

function criarGestor() {
    return new GestorFiltrosDinamicos({
        seletorListaFiltros: '#lista-filtros-b25-22',
        seletorContentorFiltros: '#filtros-b25-22',

        dadosFiltros: {
            artistas: [
                {identificador: 1, nome: 'Artista Um'},
                {identificador: 2, nome: 'Artista Dois'},
            ],
        },

        filtrosDisponiveis: {
            artista: {
                parametro: 'artista',
                tipo: 'selecao',
                rotulo: 'Artista',
                chaveDados: 'artistas',
            },

            ativo: {
                parametro: 'ativo',
                tipo: 'sim_nao',
                rotulo: 'Ativo',
            },
        },
    });
}

function criarTomSelectFalso(criadas) {
    return class TomSelectFalso {
        constructor(campo, configuracao) {
            this.input = campo;
            this.configuracao = configuracao;
            this.destroy = vi.fn();

            campo.tomselect = this;

            criadas.push(this);
        }
    };
}

describe('B25.22 — concorrencia dos filtros dinamicos', () => {
    beforeEach(() => {
        window.history.replaceState({}, '', '/');

        document.body.innerHTML = `
            <select id="lista-filtros-b25-22"></select>
            <div id="filtros-b25-22"></div>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        window.history.replaceState({}, '', '/');
        document.body.replaceChildren();
    });

    it('nao duplica filtros do URL com dois gestores', () => {
        window.history.replaceState(
            {},
            '',
            '/?filtro_ativo=sim',
        );

        criarGestor();
        criarGestor();

        const campos = document.querySelectorAll(
            '[name="filtro_ativo"]',
        );

        expect(campos).toHaveLength(1);
    });

    it('nao cria dois Tom Select para o mesmo campo', async () => {
        const gestor = criarGestor();

        const configuracao =
            gestor.filtrosDisponiveis.artista;

        const campo = gestor.criarCampoSelecao(
            configuracao,
            'filtro_artista',
            'campo-artista-b25-22',
            '1',
        );

        const componente = document.createElement('div');

        componente.append(campo);

        document.getElementById(
            'filtros-b25-22',
        ).append(componente);

        gestor.componentesAtivos.set(
            'filtro_artista',
            componente,
        );

        const pendente = criarPromessaPendente();
        const criadas = [];

        gestor.carregarTomSelect = vi.fn()
            .mockReturnValue(pendente.promessa);

        const primeira = gestor.inicializarTomSelect(
            'filtro_artista',
            campo,
            configuracao,
            '1',
        );

        const segunda = gestor.inicializarTomSelect(
            'filtro_artista',
            campo,
            configuracao,
            '1',
        );

        pendente.resolver(
            criarTomSelectFalso(criadas),
        );

        await Promise.all([primeira, segunda]);

        expect(criadas).toHaveLength(1);

        expect(
            gestor.instanciasTomSelect.get(
                'filtro_artista',
            ),
        ).toBe(criadas[0]);
    });

    it('preserva a selecao alterada durante o carregamento', async () => {
        const gestor = criarGestor();

        const configuracao =
            gestor.filtrosDisponiveis.artista;

        const campo = gestor.criarCampoSelecao(
            configuracao,
            'filtro_artista',
            'campo-artista-b25-22',
            '1',
        );

        const componente = document.createElement('div');

        componente.append(campo);

        document.getElementById(
            'filtros-b25-22',
        ).append(componente);

        gestor.componentesAtivos.set(
            'filtro_artista',
            componente,
        );

        const pendente = criarPromessaPendente();
        const criadas = [];

        gestor.carregarTomSelect = vi.fn()
            .mockReturnValue(pendente.promessa);

        const carregamento = gestor.inicializarTomSelect(
            'filtro_artista',
            campo,
            configuracao,
            '1',
        );

        expect(campo.value).toBe('1');

        campo.value = '';

        expect(campo.value).toBe('');

        pendente.resolver(
            criarTomSelectFalso(criadas),
        );

        await carregamento;

        expect(criadas).toHaveLength(1);

        expect(
            criadas[0].configuracao.items,
        ).toEqual([]);
    });

    it('ignora carregamento antigo apos remover e recriar filtro', async () => {
        const gestor = criarGestor();

        const pendente = criarPromessaPendente();
        const criadas = [];

        gestor.carregarTomSelect = vi.fn()
            .mockReturnValue(pendente.promessa);

        expect(
            gestor.renderizar('artista', '1'),
        ).toBe(true);

        const componenteAnterior =
            gestor.componentesAtivos.get('filtro_artista');

        const campoAnterior =
            componenteAnterior.querySelector('select');

        expect(
            gestor.removerFiltro('filtro_artista'),
        ).toBe(true);

        expect(
            gestor.renderizar('artista', '2'),
        ).toBe(true);

        const componenteAtual =
            gestor.componentesAtivos.get('filtro_artista');

        const campoAtual =
            componenteAtual.querySelector('select');

        pendente.resolver(
            criarTomSelectFalso(criadas),
        );

        await vi.waitFor(() => {
            expect(
                gestor.instanciasTomSelect.has('filtro_artista'),
            ).toBe(true);
        });

        expect(criadas).toHaveLength(1);

        expect(criadas[0].input).toBe(campoAtual);

        expect(campoAnterior.tomselect).toBeUndefined();

        expect(
            componenteAnterior.isConnected,
        ).toBe(false);
    });

    it('preserva a selecao atual quando o carregamento falha', async () => {
        const gestor = criarGestor();

        const configuracao =
            gestor.filtrosDisponiveis.artista;

        const campo = gestor.criarCampoSelecao(
            configuracao,
            'filtro_artista',
            'campo-artista-fallback',
            '1',
        );

        const componente = document.createElement('div');

        componente.append(campo);

        document.getElementById(
            'filtros-b25-22',
        ).append(componente);

        gestor.componentesAtivos.set(
            'filtro_artista',
            componente,
        );

        const pendente = criarPromessaPendente();

        gestor.carregarTomSelect = vi.fn()
            .mockReturnValue(
                pendente.promessa.then(() => {
                    throw new Error('Falha simulada');
                }),
            );

        const carregamento = gestor.inicializarTomSelect(
            'filtro_artista',
            campo,
            configuracao,
            '1',
        );

        const novaOpcao = document.createElement('option');

        novaOpcao.value = '2';
        novaOpcao.textContent = 'Artista Dois';

        campo.append(novaOpcao);
        campo.value = '2';

        expect(campo.value).toBe('2');

        pendente.resolver();

        await carregamento;

        expect(campo.value).toBe('2');

        expect(
            Array.from(campo.options, (opcao) => opcao.value),
        ).toEqual(['', '1', '2']);

        expect(
            gestor.instanciasTomSelect.has('filtro_artista'),
        ).toBe(false);

        expect(
            gestor.inicializacoesTomSelect.has(campo),
        ).toBe(false);

        const criadas = [];

        gestor.carregarTomSelect = vi.fn()
            .mockResolvedValue(
                criarTomSelectFalso(criadas),
            );

        await gestor.inicializarTomSelect(
            'filtro_artista',
            campo,
            configuracao,
            '1',
        );

        expect(criadas).toHaveLength(1);

        expect(
            criadas[0].configuracao.items,
        ).toEqual(['2']);
    });

    it('reutiliza o gestor sem duplicar listeners', () => {
        const primeiro = criarGestor();
        const segundo = criarGestor();

        expect(segundo).toBe(primeiro);

        const renderizar = vi.spyOn(
            primeiro,
            'renderizar',
        );

        const lista = document.getElementById(
            'lista-filtros-b25-22',
        );

        const opcao = document.createElement('option');

        opcao.value = 'ativo';
        opcao.textContent = 'Ativo';

        lista.append(opcao);
        lista.value = 'ativo';

        lista.dispatchEvent(
            new Event('change', {
                bubbles: true,
            }),
        );

        expect(renderizar).toHaveBeenCalledTimes(1);

        expect(
            document.querySelectorAll(
                '[name="filtro_ativo"]',
            ),
        ).toHaveLength(1);
    });

    it('cria novo gestor depois de substituir os elementos', () => {
        const primeiro = criarGestor();

        const listaAnterior =
            primeiro.listaAdicionarFiltro;

        const contentorAnterior =
            primeiro.areaFiltrosAtivos;

        document.body.innerHTML = `
            <select id="lista-filtros-b25-22"></select>
            <div id="filtros-b25-22"></div>
        `;

        const segundo = criarGestor();

        expect(segundo).not.toBe(primeiro);

        expect(
            segundo.listaAdicionarFiltro,
        ).not.toBe(listaAnterior);

        expect(
            segundo.areaFiltrosAtivos,
        ).not.toBe(contentorAnterior);

        expect(
            segundo.renderizar('ativo', 'sim'),
        ).toBe(true);

        expect(
            document.querySelectorAll(
                '[name="filtro_ativo"]',
            ),
        ).toHaveLength(1);
    });
});
