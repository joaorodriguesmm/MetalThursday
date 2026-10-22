import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorInteracoes
    from '../../../resources/js/modulos/GestorInteracoes.js';

function criarPedidoPendente() {
    let resolver;
    let rejeitar;

    const promessa = new Promise((resolve, reject) => {
        resolver = resolve;
        rejeitar = reject;
    });

    return {promessa, resolver, rejeitar};
}

describe('B25.8 — ciclo de vida dos tooltips de gostos', () => {
    let gestor;
    let botao;
    let atualizarTooltip;

    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article id="comentario-50" class="comentario">
                    <button
                        id="gosto"
                        type="button"
                        data-tipo-interacao="alternar-gosto"
                        data-endereco-utilizadores-gosto="/comentarios/50/utilizadores-gosto"
                        data-bs-toggle="tooltip"
                        data-bs-title="A carregar..."
                        aria-pressed="false"
                    >
                        <span data-quantidade-gostos>0</span>
                    </button>
                </article>
            </main>
        `;

        botao = document.getElementById('gosto');
        gestor = new GestorInteracoes('#interacoes');

        atualizarTooltip = vi.spyOn(
            gestor,
            'atualizarTooltip',
        ).mockImplementation((elemento, conteudo) => {
            elemento.setAttribute('data-bs-title', conteudo);
        });
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('ignora sucesso HTTP depois de remover o comentario', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const carregamento = gestor.carregarTooltipGostos(botao);

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(botao.dataset.estadoTooltipGostos)
            .toBe('a-carregar');

        document.getElementById('comentario-50').remove();

        pendente.resolver({
            data: {
                conteudo_indicador_html: 'Lista desatualizada',
            },
        });

        await carregamento;

        expect(atualizarTooltip).not.toHaveBeenCalled();
        expect(gestor.pedidosTooltipGostos.has(botao))
            .toBe(false);
        expect(botao.isConnected).toBe(false);
    });

    it('ignora falha HTTP depois de remover o comentario', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const carregamento = gestor.carregarTooltipGostos(botao);

        expect(pedido).toHaveBeenCalledTimes(1);

        document.getElementById('comentario-50').remove();

        pendente.rejeitar(new Error('Falha HTTP simulada.'));

        await expect(carregamento).resolves.toBeUndefined();

        expect(atualizarTooltip).not.toHaveBeenCalled();
        expect(gestor.pedidosTooltipGostos.has(botao))
            .toBe(false);
    });

    it('nao duplica pedidos por rato e teclado', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        botao.dispatchEvent(new MouseEvent(
            'mouseover',
            {bubbles: true},
        ));

        botao.dispatchEvent(new Event(
            'focusin',
            {bubbles: true},
        ));

        botao.dispatchEvent(new MouseEvent(
            'mouseover',
            {bubbles: true},
        ));

        expect(pedido).toHaveBeenCalledTimes(1);

        pendente.resolver({
            data: {
                conteudo_indicador_html: 'Lista atualizada',
            },
        });

        await vi.waitFor(() => {
            expect(botao.dataset.estadoTooltipGostos)
                .toBe('carregado');
        });

        botao.dispatchEvent(new MouseEvent(
            'mouseover',
            {bubbles: true},
        ));

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(atualizarTooltip).toHaveBeenCalledTimes(1);
        expect(botao.getAttribute('data-bs-title'))
            .toBe('Lista atualizada');
    });

    it('impede que a primeira de duas consultas substitua a mais recente', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        const carregamentoAntigo =
            gestor.carregarTooltipGostos(botao);

        const carregamentoNovo =
            gestor.carregarTooltipGostos(botao);

        expect(pedido).toHaveBeenCalledTimes(2);

        segunda.resolver({
            data: {
                conteudo_indicador_html: 'Resultado recente',
            },
        });

        await carregamentoNovo;

        primeira.resolver({
            data: {
                conteudo_indicador_html: 'Resultado antigo',
            },
        });

        await carregamentoAntigo;

        expect(atualizarTooltip).toHaveBeenCalledTimes(1);
        expect(botao.getAttribute('data-bs-title'))
            .toBe('Resultado recente');
        expect(botao.dataset.estadoTooltipGostos)
            .toBe('carregado');
        expect(gestor.pedidosTooltipGostos.has(botao))
            .toBe(false);
    });

    it('permite repetir a consulta depois de resposta invalida', async () => {
        const pedido = vi.spyOn(axios, 'get')
            .mockResolvedValueOnce({
                data: {
                    conteudo_indicador_html: '  ',
                },
            })
            .mockResolvedValueOnce({
                data: {
                    conteudo_indicador_html: 'Resultado valido',
                },
            });

        await gestor.carregarTooltipGostos(botao);

        expect(botao.dataset.estadoTooltipGostos)
            .toBeUndefined();

        await gestor.carregarTooltipGostos(botao);

        expect(pedido).toHaveBeenCalledTimes(2);
        expect(atualizarTooltip).toHaveBeenCalledTimes(1);
        expect(botao.dataset.estadoTooltipGostos)
            .toBe('carregado');
        expect(botao.getAttribute('data-bs-title'))
            .toBe('Resultado valido');
    });
});
