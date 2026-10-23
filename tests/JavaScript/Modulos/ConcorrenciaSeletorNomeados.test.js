import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorAlertas from '../../../resources/js/modulos/GestorAlertas.js';
import SeletorNomeados
    from '../../../resources/js/modulos/SeletorNomeados.js';

function criarPedidoPendente() {
    let resolver;
    let rejeitar;

    const promessa = new Promise((resolve, reject) => {
        resolver = resolve;
        rejeitar = reject;
    });

    return {promessa, resolver, rejeitar};
}

describe('B25.14 — concorrencia no seletor de nomeados', () => {
    let formulario;
    let campoAutor;
    let botaoAleatorio;
    let botaoMaisAntigo;
    let tomSelect;
    let seletor;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-metal-thursday">
                <select name="autor_id">
                    <option value="1" selected>Autor 1</option>
                    <option value="2">Autor 2</option>
                </select>

                <select id="proximo-nomeado-metal-thursday">
                    <option value="">Selecionar</option>
                    <option value="2">Nomeado 2</option>
                    <option value="3">Nomeado 3</option>
                </select>

                <button
                    type="button"
                    id="botao-selecionar-nomeado-aleatorio"
                >
                    Aleatório
                </button>

                <button
                    type="button"
                    id="botao-selecionar-nomeado-mais-antigo"
                >
                    Mais antigo
                </button>
            </form>
        `;

        formulario = document.getElementById(
            'formulario-metal-thursday',
        );

        campoAutor = formulario.querySelector(
            '[name="autor_id"]',
        );

        botaoAleatorio = document.getElementById(
            'botao-selecionar-nomeado-aleatorio',
        );

        botaoMaisAntigo = document.getElementById(
            'botao-selecionar-nomeado-mais-antigo',
        );

        tomSelect = {
            options: {
                '2': {disabled: false},
                '3': {disabled: false},
            },
            isLocked: false,
            setValue: vi.fn(),
            lock: vi.fn(() => {
                tomSelect.isLocked = true;
            }),
            unlock: vi.fn(() => {
                tomSelect.isLocked = false;
            }),
        };

        vi.spyOn(
            GestorAlertas,
            'mostrarErro',
        ).mockResolvedValue();

        vi.spyOn(
            GestorAlertas,
            'mostrarInformacao',
        ).mockResolvedValue();

        seletor = new SeletorNomeados({
            seletorBotaoAleatorio:
                '#botao-selecionar-nomeado-aleatorio',
            seletorBotaoMaisAntigo:
                '#botao-selecionar-nomeado-mais-antigo',
            instanciaTomSelect: tomSelect,
            urlNomeadoMaisAntigo: '/utilizadores/mais-antigo',
            obterValorExcluido: () => campoAutor.value,
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('seleciona normalmente o nomeado devolvido', async () => {
        const pedido = vi.spyOn(axios, 'get')
            .mockResolvedValue({
                data: {
                    identificador: 2,
                },
            });

        await seletor.selecionarMaisAntigo();

        expect(pedido).toHaveBeenCalledWith(
            '/utilizadores/mais-antigo',
            {
                params: {
                    excluir_utilizador_id: '1',
                },
            },
        );

        expect(tomSelect.setValue).toHaveBeenCalledWith('2');
        expect(botaoAleatorio.disabled).toBe(false);
        expect(botaoMaisAntigo.disabled).toBe(false);
        expect(tomSelect.isLocked).toBe(false);
        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('bloqueia pedidos duplicados e selecao aleatoria pendente', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const primeira = seletor.selecionarMaisAntigo();

        expect(seletor.emPedidoMaisAntigo).toBe(true);
        expect(botaoAleatorio.disabled).toBe(true);
        expect(botaoMaisAntigo.disabled).toBe(true);
        expect(tomSelect.isLocked).toBe(true);

        const segunda = seletor.selecionarMaisAntigo();

        seletor.selecionarAleatorio();

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(tomSelect.setValue).not.toHaveBeenCalled();

        pendente.resolver({
            data: {
                identificador: 2,
            },
        });

        await Promise.all([primeira, segunda]);

        expect(tomSelect.setValue).toHaveBeenCalledTimes(1);
        expect(seletor.emPedidoMaisAntigo).toBe(false);
        expect(tomSelect.isLocked).toBe(false);
    });

    it('revalida o autor excluido depois do pedido', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const operacao = seletor.selecionarMaisAntigo();

        expect(campoAutor.value).toBe('1');

        campoAutor.value = '2';

        pendente.resolver({
            data: {
                identificador: 2,
            },
        });

        await operacao;

        expect(tomSelect.setValue).not.toHaveBeenCalled();
        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('ignora sucesso tardio depois de remover o formulario', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const operacao = seletor.selecionarMaisAntigo();

        formulario.remove();

        pendente.resolver({
            data: {
                identificador: 2,
            },
        });

        await operacao;

        expect(tomSelect.setValue).not.toHaveBeenCalled();

        expect(
            GestorAlertas.mostrarErro,
        ).not.toHaveBeenCalled();

        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('ignora erro tardio depois de remover o formulario', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const operacao = seletor.selecionarMaisAntigo();

        formulario.remove();

        pendente.rejeitar(
            new Error('Falha HTTP tardia simulada.'),
        );

        await expect(operacao).resolves.toBeUndefined();

        expect(
            GestorAlertas.mostrarErro,
        ).not.toHaveBeenCalled();

        expect(tomSelect.setValue).not.toHaveBeenCalled();
        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('liberta o bloqueio e permite repetir apos erro HTTP', async () => {
        const pedido = vi.spyOn(axios, 'get')
            .mockRejectedValueOnce(
                new Error('Falha temporária.'),
            )
            .mockResolvedValueOnce({
                data: {
                    identificador: 3,
                },
            });

        await seletor.selecionarMaisAntigo();

        expect(seletor.emPedidoMaisAntigo).toBe(false);
        expect(botaoMaisAntigo.disabled).toBe(false);
        expect(botaoAleatorio.disabled).toBe(false);
        expect(tomSelect.isLocked).toBe(false);

        await seletor.selecionarMaisAntigo();

        expect(pedido).toHaveBeenCalledTimes(2);
        expect(tomSelect.setValue).toHaveBeenCalledWith('3');
        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('nao seleciona um nomeado desativado durante o pedido', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const operacao = seletor.selecionarMaisAntigo();

        tomSelect.options['2'].disabled = true;

        pendente.resolver({
            data: {
                identificador: 2,
            },
        });

        await operacao;

        expect(tomSelect.setValue).not.toHaveBeenCalled();

        expect(
            GestorAlertas.mostrarErro,
        ).toHaveBeenCalled();

        expect(seletor.emPedidoMaisAntigo).toBe(false);
        expect(tomSelect.isLocked).toBe(false);
    });

    it('funciona com a referencia real ao campo Tom Select', async () => {
        const campo = document.getElementById(
            'proximo-nomeado-metal-thursday',
        );

        tomSelect.input = campo;
        campo.tomselect = tomSelect;

        vi.spyOn(axios, 'get').mockResolvedValue({
            data: {
                identificador: 3,
            },
        });

        await seletor.selecionarMaisAntigo();

        expect(tomSelect.setValue).toHaveBeenCalledWith('3');
        expect(tomSelect.unlock).toHaveBeenCalledTimes(1);
        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('ignora resposta apos destruicao do Tom Select', async () => {
        const campo = document.getElementById(
            'proximo-nomeado-metal-thursday',
        );

        tomSelect.input = campo;
        campo.tomselect = tomSelect;

        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const operacao = seletor.selecionarMaisAntigo();

        expect(tomSelect.lock).toHaveBeenCalledTimes(1);

        delete campo.tomselect;

        pendente.resolver({
            data: {
                identificador: 2,
            },
        });

        await operacao;

        expect(tomSelect.setValue).not.toHaveBeenCalled();
        expect(tomSelect.unlock).not.toHaveBeenCalled();
        expect(seletor.emPedidoMaisAntigo).toBe(false);
    });

    it('ignora erro obsoleto depois de alterar o autor excluido', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const operacao = seletor.selecionarMaisAntigo();

        campoAutor.value = '2';

        campoAutor.dispatchEvent(
            new Event('change', {
                bubbles: true,
            }),
        );

        pendente.rejeitar(
            new Error('Erro do pedido para o autor anterior.'),
        );

        await expect(operacao).resolves.toBeUndefined();

        expect(
            GestorAlertas.mostrarErro,
        ).not.toHaveBeenCalled();

        expect(tomSelect.setValue).not.toHaveBeenCalled();
        expect(seletor.emPedidoMaisAntigo).toBe(false);
        expect(tomSelect.isLocked).toBe(false);
    });

});
