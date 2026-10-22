import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import InicializadorComentarios
    from '../../../resources/js/modulos/InicializadorComentarios.js';

function criarPedidoPendente() {
    let resolver;
    let rejeitar;

    const promessa = new Promise((resolve, reject) => {
        resolver = resolve;
        rejeitar = reject;
    });

    return {promessa, resolver, rejeitar};
}

describe('B25.7 — carregamentos tardios de comentarios', () => {
    let gestor;

    beforeEach(() => {
        document.body.innerHTML = `
            <main id="interacoes">
                <article
                    id="metal-thursday-42"
                    class="cartao-metal-thursday"
                >
                    <button
                        id="botao-comentarios"
                        type="button"
                        aria-controls="painel-comentarios"
                        data-endereco-comentarios="/comentarios/metal-thursday/42"
                    >
                        <span data-quantidade-comentarios>1</span>
                    </button>

                    <div
                        id="painel-comentarios"
                        class="collapse"
                        data-comentarios-carregados="false"
                    ></div>

                    <article
                        id="comentario-10"
                        class="comentario"
                        data-nivel-visual="1"
                    >
                        <button
                            id="botao-respostas"
                            type="button"
                            data-endereco-respostas="/comentarios/10/respostas"
                            data-quantidade-respostas="1"
                        >
                            <span data-texto-alternador-respostas>
                                Ver 1 resposta
                            </span>
                        </button>

                        <div
                            id="respostas-10"
                            data-respostas-comentario
                            data-respostas-carregadas="false"
                        >
                            <p id="conteudo-original">
                                Respostas por carregar
                            </p>
                        </div>
                    </article>
                </article>
            </main>
        `;

        gestor = new InicializadorComentarios(
            document.getElementById('interacoes'),
        );
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao preenche uma seccao removida durante o GET', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const botao = document.getElementById(
            'botao-comentarios',
        );

        const painel = document.getElementById(
            'painel-comentarios',
        );

        const contador = botao.querySelector(
            '[data-quantidade-comentarios]',
        );

        const carregamento = gestor.carregarSeccaoComentarios(
            botao,
        );

        expect(pedido).toHaveBeenCalledTimes(1);

        document.getElementById(
            'metal-thursday-42',
        ).remove();

        pendente.resolver({
            data: {
                numero_comentarios: 3,
                comentarios_html: `
                    <section aria-label="Comentários">
                        <div class="lista-comentarios"></div>
                    </section>
                `,
            },
        });

        await carregamento;

        expect(painel.innerHTML).toBe('');
        expect(painel.dataset.comentariosCarregados)
            .toBe('false');
        expect(contador.textContent).toBe('1');
        expect(botao.disabled).toBe(false);
        expect(botao.hasAttribute('aria-busy')).toBe(false);
    });

    it('nao preenche respostas removidas durante o GET', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const comentario = document.getElementById(
            'comentario-10',
        );

        const botao = document.getElementById(
            'botao-respostas',
        );

        const respostas = document.getElementById(
            'respostas-10',
        );

        vi.spyOn(
            gestor,
            'obterQuantidadeRespostas',
        ).mockReturnValue(1);

        vi.spyOn(
            gestor,
            'obterNivelVisual',
        ).mockReturnValue(1);

        vi.spyOn(
            gestor,
            'definirTextoAlternador',
        ).mockImplementation(() => {});

        const atualizarQuantidade = vi.spyOn(
            gestor,
            'atualizarQuantidadeRespostas',
        ).mockImplementation(() => {});

        const carregamento =
            gestor.executarCarregamentoRespostas(
                comentario,
                botao,
                respostas,
            );

        expect(pedido).toHaveBeenCalledTimes(1);

        document.getElementById(
            'metal-thursday-42',
        ).remove();

        pendente.resolver({
            data: {
                numero_respostas: 0,
                respostas: [],
            },
        });

        await carregamento;

        expect(
            respostas.querySelector('#conteudo-original'),
        ).not.toBeNull();

        expect(respostas.dataset.respostasCarregadas)
            .toBe('false');

        expect(atualizarQuantidade).not.toHaveBeenCalled();
        expect(botao.disabled).toBe(false);
        expect(botao.hasAttribute('aria-busy')).toBe(false);
    });

    it('ignora erro HTTP tardio de uma seccao removida', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const botao = document.getElementById(
            'botao-comentarios',
        );

        const painel = document.getElementById(
            'painel-comentarios',
        );

        const carregamento = gestor.carregarSeccaoComentarios(
            botao,
        );

        expect(pedido).toHaveBeenCalledTimes(1);

        document.getElementById('metal-thursday-42').remove();

        pendente.rejeitar(new Error('Falha HTTP simulada.'));

        await expect(carregamento).resolves.toBeUndefined();

        expect(painel.innerHTML).toBe('');
        expect(painel.dataset.comentariosCarregados)
            .toBe('false');
        expect(botao.disabled).toBe(false);
        expect(botao.hasAttribute('aria-busy')).toBe(false);
        expect(gestor.carregamentosSeccoes.has(botao))
            .toBe(false);
    });

    it('ignora erro HTTP tardio de respostas removidas', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'get')
            .mockReturnValue(pendente.promessa);

        const comentario = document.getElementById(
            'comentario-10',
        );

        const botao = document.getElementById(
            'botao-respostas',
        );

        const respostas = document.getElementById(
            'respostas-10',
        );

        vi.spyOn(gestor, 'obterQuantidadeRespostas')
            .mockReturnValue(1);

        vi.spyOn(gestor, 'definirTextoAlternador')
            .mockImplementation(() => {});

        const atualizar = vi.spyOn(
            gestor,
            'atualizarQuantidadeRespostas',
        ).mockImplementation(() => {});

        const carregamento = gestor.carregarRespostas(
            comentario,
            botao,
            respostas,
        );

        expect(pedido).toHaveBeenCalledTimes(1);

        document.getElementById('metal-thursday-42').remove();

        pendente.rejeitar(new Error('Falha HTTP simulada.'));

        await expect(carregamento).resolves.toBeUndefined();

        expect(
            respostas.querySelector('#conteudo-original'),
        ).not.toBeNull();

        expect(respostas.dataset.respostasCarregadas)
            .toBe('false');
        expect(atualizar).not.toHaveBeenCalled();
        expect(botao.disabled).toBe(false);
        expect(botao.hasAttribute('aria-busy')).toBe(false);
        expect(gestor.carregamentosRespostas.has(botao))
            .toBe(false);
    });

    it('continua a carregar comentarios normalmente', async () => {
        const pedido = vi.spyOn(axios, 'get')
            .mockResolvedValue({
                data: {
                    numero_comentarios: 3,
                    comentarios_html: `
                        <section aria-label="Comentários">
                            <div class="lista-comentarios"></div>
                        </section>
                    `,
                },
            });

        const botao = document.getElementById(
            'botao-comentarios',
        );

        const painel = document.getElementById(
            'painel-comentarios',
        );

        await gestor.carregarSeccaoComentarios(botao);

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(painel.dataset.comentariosCarregados)
            .toBe('true');
        expect(
            painel.querySelector('.lista-comentarios'),
        ).not.toBeNull();

        expect(
            botao.querySelector(
                '[data-quantidade-comentarios]',
            ).textContent,
        ).toBe('3');

        expect(botao.disabled).toBe(false);
        expect(gestor.carregamentosSeccoes.has(botao))
            .toBe(false);
    });

    it('continua a carregar respostas normalmente', async () => {
        const pedido = vi.spyOn(axios, 'get')
            .mockResolvedValue({
                data: {
                    numero_respostas: 1,
                    respostas: [
                        {
                            comentario_html:
                                '<article class="comentario" id="comentario-11"></article>',
                        },
                    ],
                },
            });

        const comentario = document.getElementById(
            'comentario-10',
        );

        const botao = document.getElementById(
            'botao-respostas',
        );

        const respostas = document.getElementById(
            'respostas-10',
        );

        vi.spyOn(gestor, 'obterQuantidadeRespostas')
            .mockReturnValue(1);

        vi.spyOn(gestor, 'obterNivelVisual')
            .mockReturnValue(1);

        vi.spyOn(gestor, 'definirTextoAlternador')
            .mockImplementation(() => {});

        const atualizar = vi.spyOn(
            gestor,
            'atualizarQuantidadeRespostas',
        ).mockImplementation(() => {});

        await gestor.carregarRespostas(
            comentario,
            botao,
            respostas,
        );

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(
            respostas.querySelector('#comentario-11'),
        ).not.toBeNull();

        expect(respostas.dataset.respostasCarregadas)
            .toBe('true');
        expect(atualizar).toHaveBeenCalledWith(botao, 1);
        expect(botao.disabled).toBe(false);
        expect(gestor.carregamentosRespostas.has(botao))
            .toBe(false);
    });
});
