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

describe('GestorInteracoes — recuperação na preparação da edição', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <form
                    data-formulario-edicao-comentario
                    data-endereco="/comentarios/42"
                >
                    <textarea data-campo-conteudo-comentario>Texto válido</textarea>
                    <div class="invalid-feedback"></div>
                    <button type="submit">Guardar</button>
                </form>
            </main>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('liberta o bloqueio se a limpeza de erros falhar', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const formulario = document.querySelector('form');
        const pedido = vi.spyOn(axios, 'patch');

        vi.spyOn(gestor, 'limparErroCampo').mockImplementation(() => {
            throw new Error('Falha simulada na preparação.');
        });

        await expect(gestor.submeterEdicaoComentario(formulario))
            .rejects.toThrow('Falha simulada na preparação.');

        expect(pedido).not.toHaveBeenCalled();
        expect(gestor.elementosEmProcessamento.has(formulario)).toBe(false);

        const campo = formulario.querySelector('textarea');
        const botao = formulario.querySelector('button[type="submit"]');

        expect(formulario.hasAttribute('aria-busy')).toBe(false);
        expect(campo.readOnly).toBe(false);
        expect(botao.disabled).toBe(false);
        expect(botao.innerHTML).toBe('Guardar');
    });

    it('liberta o bloqueio mesmo que o restauro falhe', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const formulario = document.querySelector('form');

        vi.spyOn(axios, 'patch').mockRejectedValue(
            new Error('Falha HTTP simulada.'),
        );
        vi.spyOn(gestor, 'mostrarErroPedido').mockResolvedValue(undefined);
        vi.spyOn(formulario, 'removeAttribute').mockImplementation((nome) => {
            if (nome === 'aria-busy') {
                throw new Error('Falha simulada no restauro.');
            }
        });

        await expect(gestor.submeterEdicaoComentario(formulario))
            .rejects.toThrow('Falha simulada no restauro.');

        expect(gestor.elementosEmProcessamento.has(formulario)).toBe(false);
    });

    it('impede duas submissões enquanto o pedido está pendente', async () => {
        const gestor = new GestorInteracoes('#interacoes');
        const formulario = document.querySelector('form');
        let rejeitarPedido;
        const pedidoPendente = new Promise((resolve, reject) => {
            rejeitarPedido = reject;
        });
        const pedido = vi.spyOn(axios, 'patch').mockReturnValue(pedidoPendente);
        vi.spyOn(gestor, 'mostrarErroPedido').mockResolvedValue(undefined);

        const primeiraSubmissao = gestor.submeterEdicaoComentario(formulario);
        const segundaSubmissao = gestor.submeterEdicaoComentario(formulario);

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(gestor.elementosEmProcessamento.has(formulario)).toBe(true);

        rejeitarPedido(new Error('Falha HTTP simulada.'));
        await Promise.all([primeiraSubmissao, segundaSubmissao]);

        expect(gestor.elementosEmProcessamento.has(formulario)).toBe(false);
        expect(formulario.hasAttribute('aria-busy')).toBe(false);
    });
});
