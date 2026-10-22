import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';
import InicializadorComentarios
    from '../../../resources/js/modulos/InicializadorComentarios.js';

function criarPedidoPendente() {
    let resolver;

    const promessa = new Promise((resolve) => {
        resolver = resolve;
    });

    return {promessa, resolver};
}

describe('B25.6 — respostas tardias de comentarios', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <article
                id="metal-thursday-42"
                class="cartao-metal-thursday"
            >
                <button
                    type="button"
                    aria-controls="painel-comentarios"
                >
                    <span data-quantidade-comentarios>0</span>
                </button>

                <div id="painel-comentarios" class="collapse">
                    <section aria-label="Comentários">
                        <form id="formulario-comentario">
                            <textarea name="conteudo">Teste</textarea>
                            <button type="submit">Publicar</button>
                        </form>

                        <div class="lista-comentarios"></div>
                    </section>
                </div>
            </article>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao atualiza comentarios de um cartao removido', async () => {
        const gestor = new InicializadorComentarios(
            document.createElement('div'),
        );

        const formulario = document.getElementById(
            'formulario-comentario',
        );

        const lista = document.querySelector(
            '.lista-comentarios',
        );

        const cartao = document.getElementById(
            'metal-thursday-42',
        );

        cartao.remove();

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            {
                submissao_repetida: false,
                comentario: {id: 99},
                comentario_html:
                    '<article id="comentario-99" class="comentario"></article>',
            },
        );

        expect(
            lista.querySelector('#comentario-99'),
        ).toBeNull();
    });

    it('nao executa atualizacoes locais apos remocao durante pedido HTTP', async () => {
        const aoSucesso = vi.fn(async () => {});

        const tratador = new TratadorFormularioAjax(
            'formulario-comentario',
            '/comentarios',
            aoSucesso,
        );

        const mensagemSucesso = vi.spyOn(
            tratador,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        vi.spyOn(
            tratador,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});

        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const submissao = tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        document.getElementById(
            'metal-thursday-42',
        ).remove();

        pendente.resolver({
            data: {
                mensagem: 'Comentário publicado com sucesso.',
            },
        });

        await submissao;

        expect(aoSucesso).not.toHaveBeenCalled();
        expect(mensagemSucesso).not.toHaveBeenCalled();
        expect(tratador.emSubmissao).toBe(false);
    });

    it('mantem processamento normal quando o formulario continua presente', async () => {
        const aoSucesso = vi.fn(async () => {});

        const tratador = new TratadorFormularioAjax(
            'formulario-comentario',
            '/comentarios',
            aoSucesso,
        );

        const mensagemSucesso = vi.spyOn(
            tratador,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        vi.spyOn(
            tratador,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});

        const pedido = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: {
                    mensagem: 'Comentário publicado com sucesso.',
                },
            });

        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(aoSucesso).toHaveBeenCalledTimes(1);
        expect(mensagemSucesso).toHaveBeenCalledTimes(1);
        expect(tratador.emSubmissao).toBe(false);
    });
});

describe('B25.6 — ciclo de vida dos formularios AJAX', () => {
    beforeEach(() => {
        document.body.innerHTML = `
            <article id="metal-thursday-42">
                <form id="formulario-comentario">
                    <textarea name="conteudo">Teste</textarea>
                    <button type="submit">Publicar</button>
                </form>
            </article>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('preserva evento global de sucesso depois da remocao', async () => {
        const aoSucesso = vi.fn();
        const tratador = new TratadorFormularioAjax(
            'formulario-comentario',
            '/comentarios',
            aoSucesso,
        );

        const eventos = vi.fn();
        document.addEventListener(
            'formulario-ajax:sucesso',
            eventos,
            {once: true},
        );

        let concluirPedido;
        const pendente = new Promise((resolve) => {
            concluirPedido = resolve;
        });

        vi.spyOn(axios, 'post').mockReturnValue(pendente);
        const mensagem = vi.spyOn(
            tratador,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        const submissao = tratador.submeter();

        document.getElementById('metal-thursday-42').remove();

        concluirPedido({
            data: {mensagem: 'Comentário publicado com sucesso.'},
        });
        await submissao;

        expect(eventos).toHaveBeenCalledTimes(1);
        expect(eventos.mock.calls[0][0].detail.idFormulario)
            .toBe('formulario-comentario');
        expect(aoSucesso).not.toHaveBeenCalled();
        expect(mensagem).not.toHaveBeenCalled();
        expect(tratador.emSubmissao).toBe(false);
    });

    it('ignora erro HTTP tardio de formulario removido', async () => {
        const aoErro = vi.fn();
        const tratador = new TratadorFormularioAjax(
            'formulario-comentario',
            '/comentarios',
            null,
            aoErro,
        );

        const erroVisual = vi.spyOn(
            tratador,
            'tratarErroSubmissao',
        ).mockImplementation(() => {});

        let rejeitarPedido;
        const pendente = new Promise((resolve, reject) => {
            rejeitarPedido = reject;
        });

        vi.spyOn(axios, 'post').mockReturnValue(pendente);

        const submissao = tratador.submeter();

        document.getElementById('metal-thursday-42').remove();

        rejeitarPedido(new Error('Falha HTTP simulada'));
        await submissao;

        expect(aoErro).not.toHaveBeenCalled();
        expect(erroVisual).not.toHaveBeenCalled();
        expect(tratador.emSubmissao).toBe(false);
    });

    it('nao apresenta mensagens se o callback remover o formulario', async () => {
        const aoSucesso = vi.fn(async () => {
            document.getElementById('metal-thursday-42').remove();
        });

        const tratador = new TratadorFormularioAjax(
            'formulario-comentario',
            '/comentarios',
            aoSucesso,
        );

        const sucessoVisual = vi.spyOn(
            tratador,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        const avisoVisual = vi.spyOn(
            tratador,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});

        vi.spyOn(axios, 'post').mockResolvedValue({
            data: {mensagem: 'OK'},
        });

        await tratador.submeter();

        expect(aoSucesso).toHaveBeenCalledTimes(1);
        expect(sucessoVisual).not.toHaveBeenCalled();
        expect(avisoVisual).not.toHaveBeenCalled();
        expect(tratador.emSubmissao).toBe(false);
    });
});
