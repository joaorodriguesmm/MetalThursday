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

describe('B25.9 — ciclo de vida da edicao e eliminacao', () => {
    let gestor;
    let cartao;
    let comentario;
    let formulario;
    let botaoEliminar;

    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article
                    id="metal-thursday-42"
                    class="cartao-metal-thursday"
                >
                    <article
                        id="comentario-42"
                        class="comentario"
                        data-identificador-comentario="42"
                    >
                        <div data-conteudo-comentario>
                            <p>Texto inicial</p>
                        </div>

                        <div
                            id="contentor-edicao-comentario-42"
                            class="contentor-edicao-comentario"
                        >
                            <form
                                data-formulario-edicao-comentario
                                data-endereco="/comentarios/42"
                                data-identificador-comentario="42"
                            >
                                <textarea
                                    data-campo-conteudo-comentario
                                >Texto alterado</textarea>

                                <div class="invalid-feedback"></div>

                                <button type="submit">
                                    Guardar
                                </button>
                            </form>
                        </div>

                        <button
                            type="button"
                            data-tipo-interacao="eliminar"
                            data-endereco="/comentarios/42"
                            data-mensagem-sucesso="Comentario eliminado."
                        >
                            Eliminar
                        </button>
                    </article>
                </article>
            </main>
        `;

        gestor = new GestorInteracoes('#interacoes');
        cartao = document.getElementById('metal-thursday-42');
        comentario = document.getElementById('comentario-42');
        formulario = comentario.querySelector('form');

        botaoEliminar = comentario.querySelector(
            '[data-tipo-interacao="eliminar"]',
        );

        vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);

        vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao envia DELETE apos remover o cartao durante confirmacao', async () => {
        const confirmacao = criarPedidoPendente();

        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockReturnValue(confirmacao.promessa);

        const pedido = vi.spyOn(axios, 'delete')
            .mockResolvedValue({
                data: {
                    modo_eliminacao: 'remover',
                    comentarios_removidos_ids: [42],
                },
            });

        const eliminacao = gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(true);

        cartao.remove();

        confirmacao.resolver(true);

        await eliminacao;

        expect(pedido).not.toHaveBeenCalled();

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(false);

        expect(
            gestor.elementosEmProcessamento.has(botaoEliminar),
        ).toBe(false);
    });

    it('ignora sucesso de DELETE apos remover o cartao', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        const pedido = vi.spyOn(axios, 'delete')
            .mockReturnValue(pendente.promessa);

        const atualizarInterface = vi.spyOn(
            gestor,
            'atualizarInterface',
        ).mockImplementation(() => {});

        const sucesso = vi.spyOn(
            gestor,
            'mostrarMensagemSucesso',
        );

        const eliminacao = gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        await vi.waitFor(() => {
            expect(pedido).toHaveBeenCalledTimes(1);
        });

        cartao.remove();

        pendente.resolver({
            data: {
                modo_eliminacao: 'remover',
                comentarios_removidos_ids: [42],
                mensagem: 'Comentario eliminado.',
            },
        });

        await eliminacao;

        expect(atualizarInterface).not.toHaveBeenCalled();
        expect(sucesso).not.toHaveBeenCalled();

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(false);
    });

    it('ignora erro de DELETE apos remover o cartao', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        const pedido = vi.spyOn(axios, 'delete')
            .mockReturnValue(pendente.promessa);

        const mostrarErro = vi.spyOn(
            gestor,
            'mostrarErroPedido',
        );

        const eliminacao = gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        await vi.waitFor(() => {
            expect(pedido).toHaveBeenCalledTimes(1);
        });

        cartao.remove();

        pendente.rejeitar(
            new Error('Falha HTTP tardia simulada.'),
        );

        await eliminacao;

        expect(mostrarErro).not.toHaveBeenCalled();

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(false);
    });

    it('nao atualiza comentario removido durante PATCH', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'patch')
            .mockReturnValue(pendente.promessa);

        const atualizarTexto = vi.spyOn(
            gestor,
            'apresentarTextoComQuebras',
        ).mockImplementation(() => {});

        const sucesso = vi.spyOn(
            gestor,
            'mostrarMensagemSucesso',
        );

        const edicao = gestor.submeterEdicaoComentario(
            formulario,
        );

        expect(pedido).toHaveBeenCalledTimes(1);

        cartao.remove();

        pendente.resolver({
            data: {
                comentario: {
                    conteudo: 'Texto confirmado',
                    editado_em: null,
                },
                mensagem: 'Comentario guardado.',
            },
        });

        await edicao;

        expect(atualizarTexto).not.toHaveBeenCalled();
        expect(sucesso).not.toHaveBeenCalled();

        expect(
            comentario.querySelector(
                '[data-conteudo-comentario] p',
            ).textContent,
        ).toBe('Texto inicial');

        expect(
            gestor.elementosEmProcessamento.has(formulario),
        ).toBe(false);

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(false);
    });

    it('ignora erro de PATCH apos remover o cartao', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'patch')
            .mockReturnValue(pendente.promessa);

        const apresentarErro = vi.spyOn(
            gestor,
            'apresentarErroCampo',
        );

        const edicao = gestor.submeterEdicaoComentario(
            formulario,
        );

        expect(pedido).toHaveBeenCalledTimes(1);

        cartao.remove();

        pendente.rejeitar(
            new Error('Falha HTTP tardia na edicao.'),
        );

        await expect(edicao).resolves.toBeUndefined();

        expect(apresentarErro).not.toHaveBeenCalled();
        expect(gestor.mostrarErroPedido).not.toHaveBeenCalled();

        expect(
            gestor.elementosEmProcessamento.has(formulario),
        ).toBe(false);

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(false);
    });

    it('preserva sucesso de uma eliminacao normal', async () => {
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        const pedido = vi.spyOn(axios, 'delete')
            .mockResolvedValue({
                data: {
                    modo_eliminacao: 'remover',
                    comentarios_removidos_ids: [42],
                    mensagem: 'Comentario eliminado.',
                },
            });

        await gestor.tratarInteracao(
            botaoEliminar,
            'eliminar',
        );

        expect(pedido).toHaveBeenCalledTimes(1);

        expect(
            document.getElementById('comentario-42'),
        ).toBeNull();

        expect(gestor.mostrarMensagemSucesso)
            .toHaveBeenCalledWith('Comentario eliminado.');

        expect(gestor.mostrarErroPedido)
            .not.toHaveBeenCalled();

        expect(
            gestor.elementosEmProcessamento.has(botaoEliminar),
        ).toBe(false);

        expect(
            gestor.elementosEmProcessamento.has(comentario),
        ).toBe(false);
    });
});
