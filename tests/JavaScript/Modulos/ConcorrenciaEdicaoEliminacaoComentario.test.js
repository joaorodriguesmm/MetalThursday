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

describe('GestorInteracoes — concorrencia entre edicao e eliminacao', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article class="comentario" id="comentario-42"
                    data-identificador-comentario="42">
                    <div data-conteudo-comentario>
                        <p>Texto inicial</p>
                    </div>
                    <div class="contentor-edicao-comentario"
                        id="contentor-edicao-comentario-42">
                        <form data-formulario-edicao-comentario
                            data-endereco="/comentarios/42"
                            data-identificador-comentario="42">
                            <textarea data-campo-conteudo-comentario>Texto alterado</textarea>
                            <div class="invalid-feedback"></div>
                            <button type="submit">Guardar</button>
                        </form>
                    </div>
                    <button type="button" data-tipo-interacao="eliminar"
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

    it('nao elimina o mesmo comentario enquanto a edicao esta pendente', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const formulario = document.querySelector(
            'form[data-formulario-edicao-comentario]',
        );
        const botaoEliminar = document.querySelector(
            'button[data-tipo-interacao="eliminar"]',
        );

        let rejeitarEdicao;
        const pedidoPendente = new Promise((resolve, reject) => {
            rejeitarEdicao = reject;
        });

        const pedidoEditar = vi.spyOn(axios, 'patch')
            .mockReturnValue(pedidoPendente);
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

        const edicao = gestor.submeterEdicaoComentario(formulario);
        const eliminacao = gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        await eliminacao;

        const eliminacoesDuranteEdicao = pedidoEliminar.mock.calls.length;

        rejeitarEdicao(new Error('Falha simulada na edição.'));
        await edicao;

        expect(pedidoEditar).toHaveBeenCalledTimes(1);
        expect(eliminacoesDuranteEdicao).toBe(0);
    });

    it('impede a edicao durante a confirmacao e liberta ao cancelar', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const comentario = document.getElementById('comentario-42');
        const formulario = comentario.querySelector('form');
        const botaoEliminar = comentario.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        let terminarConfirmacao;
        vi.spyOn(gestor, 'confirmarEliminacao').mockReturnValue(
            new Promise((resolve) => {
                terminarConfirmacao = resolve;
            }),
        );
        const pedidoEditar = vi.spyOn(axios, 'patch')
            .mockRejectedValue(new Error('Falha de edição simulada.'));
        const pedidoEliminar = vi.spyOn(axios, 'delete');
        vi.spyOn(gestor, 'mostrarErroPedido').mockResolvedValue(undefined);

        const eliminacao = gestor.tratarInteracao(botaoEliminar, 'eliminar');
        await gestor.submeterEdicaoComentario(formulario);

        expect(pedidoEditar).not.toHaveBeenCalled();
        expect(pedidoEliminar).not.toHaveBeenCalled();
        expect(gestor.elementosEmProcessamento.has(comentario)).toBe(true);

        terminarConfirmacao(false);
        await eliminacao;

        expect(gestor.elementosEmProcessamento.has(comentario)).toBe(false);
        await gestor.submeterEdicaoComentario(formulario);
        expect(pedidoEditar).toHaveBeenCalledTimes(1);
    });

    it('liberta o bloqueio da eliminacao depois de falha HTTP', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const comentario = document.getElementById('comentario-42');
        const formulario = comentario.querySelector('form');
        const botaoEliminar = comentario.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        vi.spyOn(gestor, 'confirmarEliminacao').mockResolvedValue(true);
        const pedidoEliminar = vi.spyOn(axios, 'delete')
            .mockRejectedValue(new Error('Falha de eliminação simulada.'));
        const pedidoEditar = vi.spyOn(axios, 'patch')
            .mockRejectedValue(new Error('Falha de edição simulada.'));
        vi.spyOn(gestor, 'mostrarErroPedido').mockResolvedValue(undefined);

        await gestor.tratarInteracao(botaoEliminar, 'eliminar');
        expect(pedidoEliminar).toHaveBeenCalledTimes(1);
        expect(gestor.elementosEmProcessamento.has(comentario)).toBe(false);

        await gestor.submeterEdicaoComentario(formulario);
        expect(pedidoEditar).toHaveBeenCalledTimes(1);
    });

    it('permite eliminar outro comentario durante uma edicao pendente', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const primeiro = document.getElementById('comentario-42');
        const segundo = primeiro.cloneNode(true);
        segundo.id = 'comentario-43';
        segundo.dataset.identificadorComentario = '43';
        segundo.querySelector('form').dataset.endereco = '/comentarios/43';
        segundo.querySelector('[data-tipo-interacao="eliminar"]')
            .dataset.endereco = '/comentarios/43';
        primeiro.after(segundo);

        let rejeitarEdicao;
        const pedidoPendente = new Promise((resolve, reject) => {
            rejeitarEdicao = reject;
        });
        vi.spyOn(axios, 'patch').mockReturnValue(pedidoPendente);
        const pedidoEliminar = vi.spyOn(axios, 'delete').mockResolvedValue({
            data: {
                modo_eliminacao: 'remover',
                comentarios_removidos_ids: [43],
            },
        });
        vi.spyOn(gestor, 'confirmarEliminacao').mockResolvedValue(true);
        vi.spyOn(gestor, 'mostrarErroPedido').mockResolvedValue(undefined);

        const edicao = gestor.submeterEdicaoComentario(
            primeiro.querySelector('form'),
        );
        await gestor.tratarInteracao(
            segundo.querySelector('[data-tipo-interacao="eliminar"]'),
            'eliminar',
        );

        expect(pedidoEliminar).toHaveBeenCalledTimes(1);
        expect(document.getElementById('comentario-43')).toBeNull();
        expect(document.getElementById('comentario-42')).not.toBeNull();

        rejeitarEdicao(new Error('Falha de edição simulada.'));
        await edicao;
    });
});
