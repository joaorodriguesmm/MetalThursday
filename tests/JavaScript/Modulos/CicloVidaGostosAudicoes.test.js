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

const cenarios = [
    {
        nome: 'gosto',
        tipo: 'alternar-gosto',
        identificadorBotao: 'botao-gosto',
        identificadorRemovivel: 'comentario-50',
        dados: {
            adicionado: true,
            numero_gostos: 1,
            mensagem: 'Gosto atualizado.',
        },
        mensagem: 'Gosto atualizado.',
    },
    {
        nome: 'audicao',
        tipo: 'alternar-audicao',
        identificadorBotao: 'botao-audicao',
        identificadorRemovivel: 'metal-thursday-42',
        dados: {
            marcado_como_ouvido: true,
            numero_audicoes: 1,
            mensagem: 'Audicao atualizada.',
        },
        mensagem: 'Audicao atualizada.',
    },
];

describe('B25.10 — ciclo de vida de gostos e audicoes', () => {
    let gestor;
    let erro;
    let sucesso;

    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article
                    id="metal-thursday-42"
                    class="cartao-metal-thursday"
                >
                    <div data-contentor-interacoes>
                        <button
                            id="botao-audicao"
                            type="button"
                            data-tipo-interacao="alternar-audicao"
                            data-tipo-audivel="metal-thursday"
                            data-estado-audicao="false"
                            data-endereco="/audicoes/metal-thursday/42"
                        >
                            <span data-texto-interacao>
                                Marcar como ouvida
                            </span>
                        </button>

                        <span
                            class="apresentacao-audicoes"
                            aria-label="Consultar detalhes das audições. Total: 0."
                        >
                            <span class="quantidade-audicoes">0</span>
                        </span>
                    </div>

                    <article
                        id="comentario-50"
                        class="comentario"
                    >
                        <button
                            id="botao-gosto"
                            type="button"
                            data-tipo-interacao="alternar-gosto"
                            data-endereco="/gostos/50"
                            aria-pressed="false"
                        >
                            <span
                                data-icone-gosto
                                class="bi-heart"
                            ></span>
                            <span data-quantidade-gostos>0</span>
                        </button>
                    </article>
                </article>
            </main>
        `;

        gestor = new GestorInteracoes('#interacoes');

        erro = vi.spyOn(gestor, 'mostrarErroPedido')
            .mockResolvedValue(undefined);

        sucesso = vi.spyOn(gestor, 'mostrarMensagemSucesso')
            .mockResolvedValue(undefined);
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    function obterBotao(cenario) {
        return document.getElementById(
            cenario.identificadorBotao,
        );
    }

    function obterEstado(botao, cenario) {
        return cenario.tipo === 'alternar-gosto'
            ? botao.getAttribute('aria-pressed')
            : botao.dataset.estadoAudicao;
    }

    function obterContador(botao, cenario) {
        if (cenario.tipo === 'alternar-gosto') {
            return botao.querySelector(
                '[data-quantidade-gostos]',
            ).textContent;
        }

        return botao.closest(
            '[data-contentor-interacoes]',
        ).querySelector(
            '.quantidade-audicoes',
        ).textContent;
    }

    function verificarDesbloqueio(botao, cenario, removivel) {
        expect(botao.disabled).toBe(false);
        expect(botao.hasAttribute('aria-busy')).toBe(false);

        expect(
            gestor.elementosEmProcessamento.has(botao),
        ).toBe(false);

        if (cenario.tipo === 'alternar-gosto') {
            expect(
                gestor.elementosEmProcessamento.has(removivel),
            ).toBe(false);
        } else {
            expect(
                gestor.audicoesPendentesPorCartao.has(removivel),
            ).toBe(false);
        }
    }

    it.each(cenarios)(
        'ignora sucesso HTTP tardio de $nome removido',
        async (cenario) => {
            const pendente = criarPedidoPendente();

            const pedido = vi.spyOn(axios, 'post')
                .mockReturnValue(pendente.promessa);

            const atualizar = vi.spyOn(
                gestor,
                'atualizarInterface',
            );

            const botao = obterBotao(cenario);

            const removivel = document.getElementById(
                cenario.identificadorRemovivel,
            );

            const operacao = gestor.tratarInteracao(
                botao,
                cenario.tipo,
            );

            expect(pedido).toHaveBeenCalledTimes(1);
            expect(botao.disabled).toBe(true);

            removivel.remove();

            pendente.resolver({
                data: cenario.dados,
            });

            await operacao;

            expect(atualizar).not.toHaveBeenCalled();
            expect(sucesso).not.toHaveBeenCalled();
            expect(erro).not.toHaveBeenCalled();

            expect(obterEstado(botao, cenario)).toBe('false');
            expect(obterContador(botao, cenario)).toBe('0');

            verificarDesbloqueio(
                botao,
                cenario,
                removivel,
            );
        },
    );

    it.each(cenarios)(
        'ignora falha HTTP tardia de $nome removido',
        async (cenario) => {
            const pendente = criarPedidoPendente();

            const pedido = vi.spyOn(axios, 'post')
                .mockReturnValue(pendente.promessa);

            const atualizar = vi.spyOn(
                gestor,
                'atualizarInterface',
            );

            const botao = obterBotao(cenario);

            const removivel = document.getElementById(
                cenario.identificadorRemovivel,
            );

            const operacao = gestor.tratarInteracao(
                botao,
                cenario.tipo,
            );

            expect(pedido).toHaveBeenCalledTimes(1);

            removivel.remove();

            pendente.rejeitar(
                new Error('Falha HTTP tardia simulada.'),
            );

            await expect(operacao).resolves.toBeUndefined();

            expect(atualizar).not.toHaveBeenCalled();
            expect(erro).not.toHaveBeenCalled();
            expect(sucesso).not.toHaveBeenCalled();

            expect(obterEstado(botao, cenario)).toBe('false');
            expect(obterContador(botao, cenario)).toBe('0');

            verificarDesbloqueio(
                botao,
                cenario,
                removivel,
            );
        },
    );

    it.each(cenarios)(
        'mantem o funcionamento normal de $nome',
        async (cenario) => {
            const pedido = vi.spyOn(axios, 'post')
                .mockResolvedValue({
                    data: cenario.dados,
                });

            const atualizar = vi.spyOn(
                gestor,
                'atualizarInterface',
            );

            const botao = obterBotao(cenario);

            const removivel = document.getElementById(
                cenario.identificadorRemovivel,
            );

            await gestor.tratarInteracao(
                botao,
                cenario.tipo,
            );

            expect(pedido).toHaveBeenCalledTimes(1);
            expect(atualizar).toHaveBeenCalledTimes(1);

            expect(obterEstado(botao, cenario)).toBe('true');
            expect(obterContador(botao, cenario)).toBe('1');

            expect(sucesso).toHaveBeenCalledWith(
                cenario.mensagem,
            );

            expect(erro).not.toHaveBeenCalled();
            expect(removivel.isConnected).toBe(true);

            verificarDesbloqueio(
                botao,
                cenario,
                removivel,
            );
        },
    );
});
