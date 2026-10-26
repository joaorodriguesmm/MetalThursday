import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import Modal from 'bootstrap/js/dist/modal';
import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorAlertas from '../../../resources/js/modulos/GestorAlertas.js';
import GestorModalAvaliacao
    from '../../../resources/js/modulos/GestorModalAvaliacao.js';

function criarPedidoPendente() {
    let resolver;
    let rejeitar;

    const promessa = new Promise((resolve, reject) => {
        resolver = resolve;
        rejeitar = reject;
    });

    return {promessa, resolver, rejeitar};
}

function criarEstrutura() {
    return `
        <article
            id="metal-thursday-42"
            class="cartao-metal-thursday"
        >
            <div data-contentor-interacoes>
                <button
                    id="botao-avaliar"
                    type="button"
                    data-tipo-avaliavel="metal-thursday"
                    data-identificador-avaliavel="42"
                    data-nome-avaliavel="MetalThursday 42"
                    data-endereco-avaliacao="/avaliacoes/metal-thursday/42"
                    data-pontuacao-utilizador="3.5"
                    data-texto-sem-avaliacao="Avaliar"
                >
                    <span data-texto-avaliacao>
                        A tua avaliação: 3,5
                    </span>
                </button>

                <button
                    type="button"
                    class="apresentacao-avaliacoes"
                >
                    <span class="media-avaliacoes">3,8</span>
                    <span class="quantidade-avaliacoes">8</span>
                </button>
            </div>
        </article>

        <div id="modal-avaliacao" class="modal">
            <form
                id="formulario-avaliacao"
                data-formulario-avaliacao
            >
                <span data-nome-avaliavel>
                    elemento selecionado
                </span>

                <div data-estrelas-avaliacao>
                    <button type="button" data-valor="1"
                        aria-label="1 de 5"></button>
                    <button type="button" data-valor="2"
                        aria-label="2 de 5"></button>
                    <button type="button" data-valor="3"
                        aria-label="3 de 5"></button>
                    <button type="button" data-valor="4"
                        aria-label="4 de 5"></button>
                    <button type="button" data-valor="5"
                        aria-label="5 de 5"></button>
                </div>

                <input
                    type="hidden"
                    name="pontuacao"
                    value="0"
                >

                <p data-feedback-avaliacao></p>
                <p id="erro-pontuacao-avaliacao"></p>

                <button type="submit">Guardar</button>

                <button
                    type="button"
                    data-limpar-avaliacao
                    hidden
                >
                    Limpar
                </button>
            </form>
        </div>
    `;
}

function prepararGestor() {
    const gestor = new GestorModalAvaliacao();

    gestor.configurarModal({
        relatedTarget: document.getElementById('botao-avaliar'),
    });

    return gestor;
}

function removerCartao() {
    document.getElementById('metal-thursday-42').remove();
}

function dadosAvaliacao(pontuacao) {
    return {
        pontuacao_utilizador: pontuacao,
        media_avaliacoes: 3.8,
        numero_avaliacoes: pontuacao === 0 ? 7 : 8,
        mensagem: 'Avaliação atualizada com sucesso.',
    };
}

describe('B25.6 — respostas tardias de avaliacoes', () => {
    beforeEach(() => {
        document.body.innerHTML = criarEstrutura();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao atualiza um cartao removido apos guardar avaliacao', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const atualizar = vi.spyOn(
            gestor,
            'atualizarResultado',
        );

        const erro = vi.spyOn(
            gestor,
            'tratarErroSubmissao',
        ).mockImplementation(() => {});

        const sucessoVisual = vi.spyOn(
            gestor,
            'mostrarMensagemSucesso',
        ).mockResolvedValue(undefined);

        const esconder = vi.fn();

        vi.spyOn(Modal, 'getOrCreateInstance')
            .mockReturnValue({hide: esconder});

        const eventos = vi.fn();

        document.addEventListener(
            'formulario-ajax:sucesso',
            eventos,
            {once: true},
        );

        const submissao = gestor.submeterFormulario({
            preventDefault() {},
        });

        expect(pedido).toHaveBeenCalledTimes(1);

        removerCartao();

        pendente.resolver({
            data: dadosAvaliacao(4),
        });

        await submissao;

        expect(atualizar).not.toHaveBeenCalled();
        expect(erro).not.toHaveBeenCalled();
        expect(sucessoVisual).not.toHaveBeenCalled();

        expect(eventos).toHaveBeenCalledTimes(1);
        expect(gestor.emSubmissao).toBe(false);
    });

    it('nao apresenta erro HTTP de um cartao removido', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const erroVisual = vi.spyOn(
            gestor,
            'tratarErroSubmissao',
        ).mockImplementation(() => {});

        const submissao = gestor.submeterFormulario({
            preventDefault() {},
        });

        removerCartao();

        pendente.rejeitar(
            new Error('Falha HTTP simulada.'),
        );

        await submissao;

        expect(erroVisual).not.toHaveBeenCalled();
        expect(gestor.emSubmissao).toBe(false);
    });

    it('nao envia limpeza confirmada apos remocao do cartao', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();

        vi.spyOn(GestorAlertas, 'confirmar')
            .mockReturnValue(pendente.promessa);

        vi.spyOn(GestorAlertas, 'mostrarSucesso')
            .mockResolvedValue(undefined);

        vi.spyOn(GestorAlertas, 'mostrarErro')
            .mockResolvedValue(undefined);

        const pedido = vi.spyOn(axios, 'delete')
            .mockResolvedValue({
                data: dadosAvaliacao(0),
            });

        vi.spyOn(Modal, 'getOrCreateInstance')
            .mockReturnValue({hide: vi.fn()});

        const limpeza = gestor.limparAvaliacao();

        expect(pedido).not.toHaveBeenCalled();

        removerCartao();

        pendente.resolver(true);

        await limpeza;

        expect(pedido).not.toHaveBeenCalled();
        expect(gestor.emSubmissao).toBe(false);
    });
});

describe('B25.6 — conclusao das operacoes de avaliacao', () => {
    beforeEach(() => {
        document.body.innerHTML = criarEstrutura();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao atualiza cartao removido durante pedido de limpeza', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();

        vi.spyOn(GestorAlertas, 'confirmar')
            .mockResolvedValue(true);

        const pedido = vi.spyOn(axios, 'delete')
            .mockReturnValue(pendente.promessa);

        const atualizar = vi.spyOn(
            gestor,
            'atualizarResultado',
        );

        const erro = vi.spyOn(
            GestorAlertas,
            'mostrarErro',
        ).mockResolvedValue(undefined);

        const esconder = vi.fn();

        vi.spyOn(Modal, 'getOrCreateInstance')
            .mockReturnValue({hide: esconder});

        const limpeza = gestor.limparAvaliacao();

        await Promise.resolve();

        expect(pedido).toHaveBeenCalledTimes(1);

        removerCartao();

        pendente.resolver({
            data: dadosAvaliacao(0),
        });

        await limpeza;

        expect(atualizar).not.toHaveBeenCalled();
        expect(erro).not.toHaveBeenCalled();
        expect(esconder).not.toHaveBeenCalled();
        expect(gestor.emSubmissao).toBe(false);
    });

    it('ignora falha HTTP de limpeza depois de remover o cartao', async () => {
        const gestor = prepararGestor();
        const pendente = criarPedidoPendente();

        vi.spyOn(GestorAlertas, 'confirmar')
            .mockResolvedValue(true);

        const pedido = vi.spyOn(axios, 'delete')
            .mockReturnValue(pendente.promessa);

        const erroVisual = vi.spyOn(
            GestorAlertas,
            'mostrarErro',
        ).mockResolvedValue(undefined);

        const limpeza = gestor.limparAvaliacao();

        await Promise.resolve();

        expect(pedido).toHaveBeenCalledTimes(1);

        removerCartao();

        pendente.rejeitar(
            new Error('Falha HTTP simulada.'),
        );

        await limpeza;

        expect(erroVisual).not.toHaveBeenCalled();
        expect(gestor.emSubmissao).toBe(false);
    });

    it('continua a guardar normalmente com o cartao presente', async () => {
        const gestor = prepararGestor();

        const pedido = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: dadosAvaliacao(4),
            });

        const erro = vi.spyOn(
            gestor,
            'tratarErroSubmissao',
        ).mockImplementation(() => {});

        const sucesso = vi.spyOn(
            gestor,
            'mostrarMensagemSucesso',
        ).mockResolvedValue(undefined);

        const esconder = vi.fn();

        vi.spyOn(Modal, 'getOrCreateInstance')
            .mockReturnValue({hide: esconder});

        const eventos = vi.fn();

        document.addEventListener(
            'formulario-ajax:sucesso',
            eventos,
            {once: true},
        );

        await gestor.submeterFormulario({
            preventDefault() {},
        });

        const botao = document.getElementById(
            'botao-avaliar',
        );

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(botao.dataset.pontuacaoUtilizador).toBe('4');
        expect(eventos).toHaveBeenCalledTimes(1);
        expect(esconder).toHaveBeenCalledTimes(1);
        expect(sucesso).toHaveBeenCalledTimes(1);
        expect(erro).not.toHaveBeenCalled();
        expect(gestor.emSubmissao).toBe(false);
    });

    it('continua a limpar normalmente com o cartao presente', async () => {
        const gestor = prepararGestor();

        const confirmar = vi.spyOn(
            GestorAlertas,
            'confirmar',
        ).mockResolvedValue(true);

        const pedido = vi.spyOn(axios, 'delete')
            .mockResolvedValue({
                data: dadosAvaliacao(0),
            });

        vi.spyOn(
            GestorAlertas,
            'mostrarSucesso',
        ).mockResolvedValue(undefined);

        const erro = vi.spyOn(
            GestorAlertas,
            'mostrarErro',
        ).mockResolvedValue(undefined);

        const esconder = vi.fn();

        vi.spyOn(Modal, 'getOrCreateInstance')
            .mockReturnValue({hide: esconder});

        await gestor.limparAvaliacao();

        const botao = document.getElementById(
            'botao-avaliar',
        );

        expect(confirmar).toHaveBeenCalledTimes(1);
        expect(pedido).toHaveBeenCalledTimes(1);
        expect(botao.dataset.pontuacaoUtilizador).toBe('0');
        expect(esconder).toHaveBeenCalledTimes(1);
        expect(erro).not.toHaveBeenCalled();
        expect(gestor.emSubmissao).toBe(false);
    });

    it(
        'B25.56 — falha de rede ao limpar avaliacao comunica resultado incerto',
        async () => {
            const gestor = prepararGestor();

            const confirmar = vi.spyOn(
                GestorAlertas,
                'confirmar',
            ).mockResolvedValue(true);

            const alerta = vi.spyOn(
                GestorAlertas,
                'mostrarErro',
            ).mockResolvedValue(undefined);

            const erroComunicacao = new axios.AxiosError(
                'A ligação foi interrompida.',
                'ERR_NETWORK',
                {},
                {readyState: 4},
            );

            const pedido = vi.spyOn(
                axios,
                'delete',
            ).mockRejectedValueOnce(
                erroComunicacao,
            );

            const botao = document.getElementById(
                'botao-avaliar',
            );

            await gestor.limparAvaliacao();

            expect(confirmar).toHaveBeenCalledTimes(1);
            expect(pedido).toHaveBeenCalledTimes(1);

            // O gestor normaliza o endereco para URL absoluto.
            // A identidade da rota nao depende dessa forma.
            expect(
                new URL(
                    pedido.mock.calls[0][0],
                    document.baseURI,
                ).pathname,
            ).toBe('/avaliacoes/metal-thursday/42');

            expect(alerta).toHaveBeenCalledTimes(1);

            expect(alerta).toHaveBeenCalledWith(
                'Não foi possível confirmar se a operação foi concluída. '
                + 'Verifica o resultado antes de tentares novamente.',
                'Erro ao limpar avaliação',
            );

            // Uma resposta desconhecida nao pode
            // alterar indevidamente o estado visual.
            expect(botao.dataset.pontuacaoUtilizador)
                .toBe('3.5');

            expect(gestor.emSubmissao).toBe(false);
        },
    );

    it(
        'B25.56 — erro HTTP ao limpar preserva mensagem do servidor',
        async () => {
            const gestor = prepararGestor();

            vi.spyOn(
                GestorAlertas,
                'confirmar',
            ).mockResolvedValue(true);

            const alerta = vi.spyOn(
                GestorAlertas,
                'mostrarErro',
            ).mockResolvedValue(undefined);

            const erroHttp = new axios.AxiosError(
                'Erro HTTP 409.',
                'ERR_BAD_RESPONSE',
                {},
                {},
                {
                    data: {
                        mensagem:
                            'A avaliação não pode ser eliminada.',
                    },
                    status: 409,
                    statusText: 'Conflict',
                    headers: {},
                    config: {},
                },
            );

            const pedido = vi.spyOn(
                axios,
                'delete',
            ).mockRejectedValueOnce(
                erroHttp,
            );

            await gestor.limparAvaliacao();

            expect(pedido).toHaveBeenCalledTimes(1);
            expect(alerta).toHaveBeenCalledTimes(1);

            expect(alerta).toHaveBeenCalledWith(
                'A avaliação não pode ser eliminada.',
                'Erro ao limpar avaliação',
            );

            expect(gestor.emSubmissao).toBe(false);
        },
    );

});
