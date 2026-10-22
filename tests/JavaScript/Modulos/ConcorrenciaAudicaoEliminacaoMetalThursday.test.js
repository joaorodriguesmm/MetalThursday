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
    return { promessa, resolver, rejeitar };
}

function prepararGestor() {
    const gestor = new GestorInteracoes('#interacoes');
    vi.spyOn(gestor, 'mostrarErroPedido')
        .mockResolvedValue(undefined);
    vi.spyOn(gestor, 'mostrarMensagemSucesso')
        .mockResolvedValue(undefined);
    return gestor;
}

function obterBotao(tipo) {
    return document.querySelector(`[data-tipo-interacao="${tipo}"]`);
}

function obterAudicao(tipo) {
    return document.querySelector(`[data-tipo-audivel="${tipo}"]`);
}

describe('GestorInteracoes — concorrencia entre audicoes e eliminacao de MetalThursday', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article class="card cartao-metal-thursday"
                    id="metal-thursday-42"
                    data-identificador-metal-thursday="42">
                    <button type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="seccao-metal-thursday"
                        data-estado-audicao="false"
                        data-endereco="/audicoes/seccao-metal-thursday/15">
                        <span data-texto-interacao>Marcar como ouvido</span>
                    </button>
                    <button type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="metal-thursday"
                        data-estado-audicao="false"
                        data-endereco="/audicoes/metal-thursday/42">
                        <span data-texto-interacao>Marcar como ouvida</span>
                    </button>
                    <button type="button"
                        data-tipo-interacao="eliminar"
                        data-endereco="/metal-thursday/42"
                        data-seletor-elemento-removivel="#metal-thursday-42">
                        Eliminar MetalThursday
                    </button>
                </article>
            </main>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao elimina o cartao enquanto a audicao de uma secao esta pendente', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();
        const pedidoAudicao = vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockResolvedValue({ data: null });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        const audicao = gestor.tratarInteracao(
            obterAudicao('seccao-metal-thursday'), 'alternar-audicao',
        );
        await gestor.tratarInteracao(obterBotao('eliminar'), 'eliminar');
        const eliminacoesDuranteAudicao = pedidoEliminar.mock.calls.length;
        const cartaoPreservado = document.getElementById('metal-thursday-42') !== null;

        pendente.rejeitar(new Error('Falha de audição simulada.'));
        await audicao;

        expect(pedidoAudicao).toHaveBeenCalledTimes(1);
        expect(eliminacoesDuranteAudicao).toBe(0);
        expect(cartaoPreservado).toBe(true);
    });

    it('impede audicoes durante a confirmacao e liberta ao cancelar', async () => {
        const gestor = prepararGestor();
        const confirmacao = criarPedidoPendente();
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockReturnValue(confirmacao.promessa);
        const pedidoAudicao = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: { marcado_como_ouvido: true, numero_audicoes: 1 },
            });
        const pedidoEliminar = vi.spyOn(axios, 'delete');

        const eliminacao = gestor.tratarInteracao(
            obterBotao('eliminar'), 'eliminar',
        );
        await gestor.tratarInteracao(
            obterAudicao('metal-thursday'), 'alternar-audicao',
        );
        const audicoesDuranteConfirmacao = pedidoAudicao.mock.calls.length;

        confirmacao.resolver(false);
        await eliminacao;
        await gestor.tratarInteracao(
            obterAudicao('metal-thursday'), 'alternar-audicao',
        );

        expect(audicoesDuranteConfirmacao).toBe(0);
        expect(pedidoAudicao).toHaveBeenCalledTimes(1);
        expect(pedidoEliminar).not.toHaveBeenCalled();
    });

    it('impede audicoes durante o pedido de eliminacao e liberta apos falha HTTP', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockReturnValue(pendente.promessa);
        const pedidoAudicao = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: { marcado_como_ouvido: true, numero_audicoes: 1 },
            });

        const eliminacao = gestor.tratarInteracao(
            obterBotao('eliminar'), 'eliminar',
        );
        await Promise.resolve();
        await gestor.tratarInteracao(
            obterAudicao('metal-thursday'), 'alternar-audicao',
        );
        const audicoesDuranteEliminacao = pedidoAudicao.mock.calls.length;

        pendente.rejeitar(new Error('Falha HTTP simulada.'));
        await eliminacao;
        await gestor.tratarInteracao(
            obterAudicao('metal-thursday'), 'alternar-audicao',
        );

        expect(pedidoEliminar).toHaveBeenCalledTimes(1);
        expect(audicoesDuranteEliminacao).toBe(0);
        expect(pedidoAudicao).toHaveBeenCalledTimes(1);
    });

    it('permite audicoes independentes na secao e na MetalThursday', async () => {
        const gestor = prepararGestor();
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();
        const pedidoAudicao = vi.spyOn(axios, 'post')
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        const audicaoSeccao = gestor.tratarInteracao(
            obterAudicao('seccao-metal-thursday'), 'alternar-audicao',
        );
        const audicaoMetalThursday = gestor.tratarInteracao(
            obterAudicao('metal-thursday'), 'alternar-audicao',
        );
        const chamadasSimultaneas = pedidoAudicao.mock.calls.length;

        const resultado = {
            data: { marcado_como_ouvido: true, numero_audicoes: 1 },
        };
        primeira.resolver(resultado);
        segunda.resolver(resultado);
        await Promise.all([audicaoSeccao, audicaoMetalThursday]);

        expect(chamadasSimultaneas).toBe(2);
        expect(obterAudicao('seccao-metal-thursday').dataset.estadoAudicao)
            .toBe('true');
        expect(obterAudicao('metal-thursday').dataset.estadoAudicao)
            .toBe('true');
    });

    it('permite eliminar outro cartao durante uma audicao pendente', async () => {
        const gestor = prepararGestor();
        const primeiro = document.getElementById('metal-thursday-42');
        const segundo = primeiro.cloneNode(true);
        segundo.id = 'metal-thursday-43';
        segundo.dataset.identificadorMetalThursday = '43';
        const eliminarSegundo = segundo.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );
        eliminarSegundo.dataset.endereco = '/metal-thursday/43';
        eliminarSegundo.dataset.seletorElementoRemovivel = '#metal-thursday-43';
        primeiro.after(segundo);

        const pendente = criarPedidoPendente();
        vi.spyOn(axios, 'post').mockReturnValue(pendente.promessa);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockResolvedValue({ data: null });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        const audicao = gestor.tratarInteracao(
            primeiro.querySelector('[data-tipo-audivel="metal-thursday"]'),
            'alternar-audicao',
        );
        await gestor.tratarInteracao(eliminarSegundo, 'eliminar');
        const eliminacoesDuranteAudicao = pedidoEliminar.mock.calls.length;
        const primeiroPreservado = primeiro.isConnected;
        const segundoRemovido = !segundo.isConnected;

        pendente.rejeitar(new Error('Falha de audição simulada.'));
        await audicao;

        expect(eliminacoesDuranteAudicao).toBe(1);
        expect(primeiroPreservado).toBe(true);
        expect(segundoRemovido).toBe(true);
    });
});

describe('GestorInteracoes — duas audicoes pendentes no mesmo cartao', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article class="cartao-metal-thursday"
                    id="metal-thursday-42">
                    <button type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="seccao-metal-thursday"
                        data-estado-audicao="false"
                        data-endereco="/audicoes/seccao-metal-thursday/15">
                        <span data-texto-interacao>Marcar como ouvido</span>
                    </button>
                    <button type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="metal-thursday"
                        data-estado-audicao="false"
                        data-endereco="/audicoes/metal-thursday/42">
                        <span data-texto-interacao>Marcar como ouvida</span>
                    </button>
                    <button type="button"
                        data-tipo-interacao="eliminar"
                        data-endereco="/metal-thursday/42"
                        data-seletor-elemento-removivel="#metal-thursday-42">
                        Eliminar
                    </button>
                </article>
            </main>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('so permite eliminar depois de terminarem ambas as audicoes', async () => {
        const gestor = prepararGestor();

        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        const pedidoAudicao = vi.spyOn(axios, 'post')
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockResolvedValue({ data: null });

        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        const botaoSeccao = document.querySelector(
            '[data-tipo-audivel="seccao-metal-thursday"]',
        );

        const botaoMetalThursday = document.querySelector(
            '[data-tipo-audivel="metal-thursday"]',
        );

        const botaoEliminar = document.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        const audicaoSeccao = gestor.tratarInteracao(
            botaoSeccao,
            'alternar-audicao',
        );

        const audicaoMetalThursday = gestor.tratarInteracao(
            botaoMetalThursday,
            'alternar-audicao',
        );

        expect(pedidoAudicao).toHaveBeenCalledTimes(2);

        const resposta = {
            data: {
                marcado_como_ouvido: true,
                numero_audicoes: 1,
            },
        };

        primeira.resolver(resposta);
        await audicaoSeccao;

        await gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        expect(pedidoEliminar).not.toHaveBeenCalled();

        expect(
            document.getElementById('metal-thursday-42'),
        ).not.toBeNull();

        segunda.resolver(resposta);
        await audicaoMetalThursday;

        await gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        expect(pedidoEliminar).toHaveBeenCalledTimes(1);

        expect(
            document.getElementById('metal-thursday-42'),
        ).toBeNull();
    });
});
