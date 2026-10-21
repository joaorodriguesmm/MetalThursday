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

describe('GestorInteracoes — concorrencia entre gosto e eliminacao', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article class="comentario" id="comentario-42"
                    data-identificador-comentario="42">
                    <button type="button"
                        data-tipo-interacao="alternar-gosto"
                        data-endereco="/gostos/42"
                        aria-pressed="false">
                        <span data-icone-gosto class="bi-heart"></span>
                        <span data-quantidade-gostos>0</span>
                    </button>
                    <button type="button"
                        data-tipo-interacao="eliminar"
                        data-endereco="/comentarios/42">
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

    it('nao elimina o mesmo comentario enquanto um gosto esta pendente', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const botaoGosto = document.querySelector(
            '[data-tipo-interacao="alternar-gosto"]',
        );
        const botaoEliminar = document.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        let rejeitarGosto;
        const gostoPendente = new Promise((resolve, reject) => {
            rejeitarGosto = reject;
        });
        const pedidoGosto = vi.spyOn(axios, 'post')
            .mockReturnValue(gostoPendente);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockResolvedValue({
                data: {
                    modo_eliminacao: 'remover',
                    comentarios_removidos_ids: [42],
                },
            });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);
        vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);
        vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);

        const gosto = gestor.tratarInteracao(botaoGosto, 'alternar-gosto');
        await gestor.tratarInteracao(botaoEliminar, 'eliminar');
        const eliminacoesDuranteGosto = pedidoEliminar.mock.calls.length;

        rejeitarGosto(new Error('Falha simulada no gosto.'));
        await gosto;

        expect(pedidoGosto).toHaveBeenCalledTimes(1);
        expect(eliminacoesDuranteGosto).toBe(0);
    });

    it('nao permite gostos durante a confirmacao e liberta ao cancelar', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const comentario = document.getElementById('comentario-42');
        const botaoGosto = comentario.querySelector(
            '[data-tipo-interacao="alternar-gosto"]',
        );
        const botaoEliminar = comentario.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        let terminarConfirmacao;
        vi.spyOn(gestor, 'confirmarEliminacao').mockReturnValue(
            new Promise((resolve) => {
                terminarConfirmacao = resolve;
            }),
        );
        const pedidoGosto = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: {
                    adicionado: true,
                    numero_gostos: 1,
                },
            });
        const pedidoEliminar = vi.spyOn(axios, 'delete');
        vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);
        vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);

        const eliminacao = gestor.tratarInteracao(botaoEliminar, 'eliminar');
        await gestor.tratarInteracao(botaoGosto, 'alternar-gosto');
        const gostosDuranteConfirmacao = pedidoGosto.mock.calls.length;

        terminarConfirmacao(false);
        await eliminacao;

        expect(gestor.elementosEmProcessamento.has(comentario)).toBe(false);
        await gestor.tratarInteracao(botaoGosto, 'alternar-gosto');

        expect(gostosDuranteConfirmacao).toBe(0);
        expect(pedidoGosto).toHaveBeenCalledTimes(1);
        expect(pedidoEliminar).not.toHaveBeenCalled();
    });

    it('liberta o bloqueio apos falha HTTP da eliminacao', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const comentario = document.getElementById('comentario-42');
        const botaoGosto = comentario.querySelector(
            '[data-tipo-interacao="alternar-gosto"]',
        );
        const botaoEliminar = comentario.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        let rejeitarEliminacao;
        const eliminacaoPendente = new Promise((resolve, reject) => {
            rejeitarEliminacao = reject;
        });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockReturnValue(eliminacaoPendente);
        const pedidoGosto = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: {
                    adicionado: true,
                    numero_gostos: 1,
                },
            });
        vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);
        vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);

        const eliminacao = gestor.tratarInteracao(botaoEliminar, 'eliminar');
        await Promise.resolve();
        await gestor.tratarInteracao(botaoGosto, 'alternar-gosto');
        const gostosDuranteEliminacao = pedidoGosto.mock.calls.length;

        rejeitarEliminacao(new Error('Falha simulada na eliminacao.'));
        await eliminacao;
        await gestor.tratarInteracao(botaoGosto, 'alternar-gosto');

        expect(pedidoEliminar).toHaveBeenCalledTimes(1);
        expect(gostosDuranteEliminacao).toBe(0);
        expect(pedidoGosto).toHaveBeenCalledTimes(1);
        expect(gestor.elementosEmProcessamento.has(comentario)).toBe(false);
    });

    it('permite eliminar outro comentario enquanto um gosto esta pendente', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const primeiro = document.getElementById('comentario-42');
        const segundo = primeiro.cloneNode(true);
        segundo.id = 'comentario-43';
        segundo.dataset.identificadorComentario = '43';
        segundo.querySelector('[data-tipo-interacao="alternar-gosto"]')
            .dataset.endereco = '/gostos/43';
        segundo.querySelector('[data-tipo-interacao="eliminar"]')
            .dataset.endereco = '/comentarios/43';
        primeiro.after(segundo);

        let rejeitarGosto;
        const gostoPendente = new Promise((resolve, reject) => {
            rejeitarGosto = reject;
        });
        const pedidoGosto = vi.spyOn(axios, 'post')
            .mockReturnValue(gostoPendente);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockResolvedValue({
                data: {
                    modo_eliminacao: 'remover',
                    comentarios_removidos_ids: [43],
                },
            });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);
        vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);
        vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);

        const gosto = gestor.tratarInteracao(
            primeiro.querySelector('[data-tipo-interacao="alternar-gosto"]'),
            'alternar-gosto',
        );
        await gestor.tratarInteracao(
            segundo.querySelector('[data-tipo-interacao="eliminar"]'),
            'eliminar',
        );

        const eliminacoesDuranteGosto = pedidoEliminar.mock.calls.length;
        const primeiroPreservado = document.getElementById('comentario-42') !== null;
        const segundoRemovido = document.getElementById('comentario-43') === null;

        rejeitarGosto(new Error('Falha simulada no gosto.'));
        await gosto;

        expect(pedidoGosto).toHaveBeenCalledTimes(1);
        expect(eliminacoesDuranteGosto).toBe(1);
        expect(primeiroPreservado).toBe(true);
        expect(segundoRemovido).toBe(true);
    });
});
