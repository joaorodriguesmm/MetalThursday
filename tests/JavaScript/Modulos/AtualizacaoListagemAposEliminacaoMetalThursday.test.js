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

describe('A09.11 — atualizar a listagem após eliminar uma MetalThursday', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-filtros-ordenacao"></form>
            <main id="interacoes">
                <article class="cartao-metal-thursday" id="metal-thursday-42">
                    <button
                        type="button"
                        data-tipo-interacao="eliminar"
                        data-endereco="/metal-thursday/42"
                        data-seletor-elemento-removivel="#metal-thursday-42"
                        data-mensagem-sucesso="MetalThursday eliminada com sucesso."
                    >Eliminar</button>
                </article>
            </main>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    function prepararGestor() {
        const gestor = new GestorInteracoes('#interacoes');
        vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);
        vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);
        return gestor;
    }

    function obterBotao() {
        return document.querySelector('[data-tipo-interacao="eliminar"]');
    }

    it('recarrega apenas depois de eliminar o cartão com sucesso', async () => {
        const gestor = prepararGestor();
        const recarregar = vi.spyOn(
            gestor, 'recarregarListagemDepoisEliminacao',
        ).mockImplementation(() => {});
        const eliminar = vi.spyOn(axios, 'delete')
            .mockResolvedValue({ data: null });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        await gestor.tratarInteracao(obterBotao(), 'eliminar');

        expect(eliminar).toHaveBeenCalledTimes(1);
        expect(document.getElementById('metal-thursday-42')).toBeNull();
        expect(recarregar).toHaveBeenCalledTimes(1);
        expect(gestor.mostrarMensagemSucesso).not.toHaveBeenCalled();
    });

    it('não recarrega quando a eliminação é cancelada', async () => {
        const gestor = prepararGestor();
        const recarregar = vi.spyOn(
            gestor, 'recarregarListagemDepoisEliminacao',
        ).mockImplementation(() => {});
        const eliminar = vi.spyOn(axios, 'delete');
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(false);

        await gestor.tratarInteracao(obterBotao(), 'eliminar');

        expect(eliminar).not.toHaveBeenCalled();
        expect(recarregar).not.toHaveBeenCalled();
        expect(document.getElementById('metal-thursday-42')).not.toBeNull();
    });

    it('não recarrega quando a eliminação falha', async () => {
        const gestor = prepararGestor();
        const recarregar = vi.spyOn(
            gestor, 'recarregarListagemDepoisEliminacao',
        ).mockImplementation(() => {});
        vi.spyOn(axios, 'delete')
            .mockRejectedValue(new Error('Falha simulada'));
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        await gestor.tratarInteracao(obterBotao(), 'eliminar');

        expect(recarregar).not.toHaveBeenCalled();
        expect(gestor.mostrarErroPedido).toHaveBeenCalledTimes(1);
        expect(document.getElementById('metal-thursday-42')).not.toBeNull();
    });

    it('preserva o comportamento de eliminação sem listagem paginada', async () => {
        document.getElementById('formulario-filtros-ordenacao').remove();
        const gestor = prepararGestor();
        const recarregar = vi.spyOn(
            gestor, 'recarregarListagemDepoisEliminacao',
        ).mockImplementation(() => {});
        vi.spyOn(axios, 'delete')
            .mockResolvedValue({ data: null });
        vi.spyOn(gestor, 'confirmarEliminacao')
            .mockResolvedValue(true);

        await gestor.tratarInteracao(obterBotao(), 'eliminar');

        expect(recarregar).not.toHaveBeenCalled();
        expect(gestor.mostrarMensagemSucesso).toHaveBeenCalledTimes(1);
    });
});
