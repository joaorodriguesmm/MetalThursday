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

function criarPedidoPendente() {
    let resolver;

    const promessa = new Promise((resolve) => {
        resolver = resolve;
    });

    return {
        promessa,
        resolver,
    };
}

describe('B25.16 — concorrencia dos formularios AJAX', () => {
    let formulario;
    let campo;
    let botao;
    let tratador;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="teste-concorrencia-ajax">
                <textarea name="conteudo">Mensagem inicial</textarea>
                <button type="submit">
                    <strong>Enviar</strong>
                </button>
            </form>
        `;

        formulario = document.getElementById(
            'teste-concorrencia-ajax',
        );

        campo = formulario.querySelector(
            '[name="conteudo"]',
        );

        botao = formulario.querySelector(
            'button[type="submit"]',
        );

        tratador = new TratadorFormularioAjax(
            'teste-concorrencia-ajax',
            '/comentarios',
        );

        vi.spyOn(
            tratador,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        vi.spyOn(
            tratador,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});

        vi.spyOn(
            tratador,
            'tratarErroSubmissao',
        ).mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao submete formulario removido antes do pedido', async () => {
        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {mensagem: 'OK'},
        });

        formulario.remove();

        await tratador.submeter();

        expect(pedido).not.toHaveBeenCalled();

        expect(tratador.emSubmissao).toBe(false);
    });

    it('preserva texto escrito enquanto o pedido decorre', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockReturnValue(
            pendente.promessa,
        );

        campo.value = 'Mensagem enviada ao servidor';

        const submissao = tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        expect(
            pedido.mock.calls[0][1].get('conteudo'),
        ).toBe('Mensagem enviada ao servidor');

        expect(botao.disabled).toBe(true);

        campo.value =
            'Novo texto escrito durante o pedido';

        pendente.resolver({
            data: {mensagem: 'OK'},
        });

        await submissao;

        expect(campo.value).toBe(
            'Novo texto escrito durante o pedido',
        );

        expect(botao.disabled).toBe(false);
        expect(tratador.emSubmissao).toBe(false);
    });

    it('continua a limpar campos sem alteracoes apos sucesso', async () => {
        vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {mensagem: 'OK'},
        });

        campo.value = 'Mensagem publicada';

        await tratador.submeter();

        expect(campo.value).toBe('Mensagem inicial');

        expect(botao.disabled).toBe(false);

        expect(botao.textContent.trim()).toBe(
            'Enviar',
        );

        expect(
            botao.querySelector('strong'),
        ).not.toBeNull();

        expect(tratador.emSubmissao).toBe(false);
    });

    it('recupera o botao e preserva o texto apos erro HTTP', async () => {
        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockRejectedValue(
            new Error('Falha HTTP simulada'),
        );

        campo.value = 'Mensagem por publicar';

        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        expect(campo.value).toBe(
            'Mensagem por publicar',
        );

        expect(botao.disabled).toBe(false);

        expect(botao.hasAttribute('aria-busy'))
            .toBe(false);

        expect(
            botao.querySelector('strong'),
        ).not.toBeNull();

        expect(botao.textContent.trim()).toBe(
            'Enviar',
        );

        expect(tratador.emSubmissao).toBe(false);
    });

    it('preserva alteracoes em varios campos durante o pedido', async () => {
        formulario.insertAdjacentHTML(
            'beforeend',
            `
                <input name="titulo" value="Titulo original">
                <input type="checkbox" name="visivel" value="1">
            `,
        );

        const titulo = formulario.querySelector(
            '[name="titulo"]',
        );

        const visivel = formulario.querySelector(
            '[name="visivel"]',
        );

        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockReturnValue(pendente.promessa);

        campo.value = 'Mensagem enviada';

        const submissao = tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        expect(
            pedido.mock.calls[0][1].get('titulo'),
        ).toBe('Titulo original');

        expect(
            pedido.mock.calls[0][1].has('visivel'),
        ).toBe(false);

        titulo.value = 'Titulo novo';
        visivel.checked = true;

        pendente.resolver({
            data: {mensagem: 'OK'},
        });

        await submissao;

        expect(campo.value).toBe('Mensagem enviada');
        expect(titulo.value).toBe('Titulo novo');
        expect(visivel.checked).toBe(true);
        expect(botao.disabled).toBe(false);
    });

    it('preserva texto alterado durante callback assincrono', async () => {
        let concluirCallback;

        const callbackPendente = new Promise((resolve) => {
            concluirCallback = resolve;
        });

        const aoSucesso = vi.fn(
            () => callbackPendente,
        );

        const outroTratador = new TratadorFormularioAjax(
            'teste-concorrencia-ajax',
            '/comentarios',
            aoSucesso,
        );

        vi.spyOn(
            outroTratador,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        vi.spyOn(
            outroTratador,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});

        const finalizar = vi.spyOn(
            outroTratador,
            'finalizarSubmissao',
        );

        vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {mensagem: 'OK'},
        });

        campo.value = 'Mensagem submetida';

        const submissao = outroTratador.submeter();

        await vi.waitFor(() => {
            expect(aoSucesso).toHaveBeenCalledTimes(1);
        });

        expect(botao.disabled).toBe(true);

        campo.value = 'Mensagem escrita durante callback';

        concluirCallback();

        await submissao;

        expect(campo.value).toBe(
            'Mensagem escrita durante callback',
        );

        expect(finalizar).not.toHaveBeenCalled();
        expect(botao.disabled).toBe(false);
        expect(outroTratador.emSubmissao).toBe(false);
    });

    it('restaura o estado original do botao apos erro', async () => {
        const conteudoOriginal = botao.querySelector(
            'strong',
        );

        botao.disabled = true;
        botao.setAttribute('aria-busy', 'false');

        vi.spyOn(
            axios,
            'post',
        ).mockRejectedValue(
            new Error('Erro HTTP'),
        );

        await tratador.submeter();

        expect(botao.disabled).toBe(true);

        expect(
            botao.getAttribute('aria-busy'),
        ).toBe('false');

        expect(
            botao.querySelector('strong'),
        ).toBe(conteudoOriginal);

        expect(tratador.emSubmissao).toBe(false);
    });

    it('recupera de erro na preparacao dos dados', async () => {
        formulario.setAttribute(
            'data-idempotencia-submissao',
            '',
        );

        campo.disabled = true;

        const pedido = vi.spyOn(
            axios,
            'post',
        );

        await tratador.submeter();

        expect(pedido).not.toHaveBeenCalled();
        expect(botao.disabled).toBe(false);

        expect(
            botao.hasAttribute('aria-busy'),
        ).toBe(false);

        expect(
            botao.querySelector('strong'),
        ).not.toBeNull();

        expect(tratador.emSubmissao).toBe(false);
    });

    it('preserva ficheiro diferente com metadados identicos', async () => {
        const anexo = document.createElement('input');

        anexo.type = 'file';
        anexo.name = 'anexo';

        formulario.append(anexo);

        const ficheiroOriginal = new File(
            ['AAA'],
            'documento.txt',
            {
                type: 'text/plain',
                lastModified: 123456789,
            },
        );

        const ficheiroNovo = new File(
            ['BBB'],
            'documento.txt',
            {
                type: 'text/plain',
                lastModified: 123456789,
            },
        );

        let ficheirosSelecionados = [
            ficheiroOriginal,
        ];

        Object.defineProperty(
            anexo,
            'files',
            {
                configurable: true,
                get: () => ficheirosSelecionados,
            },
        );

        expect(ficheiroOriginal).not.toBe(ficheiroNovo);
        expect(ficheiroOriginal.name).toBe(ficheiroNovo.name);
        expect(ficheiroOriginal.size).toBe(ficheiroNovo.size);
        expect(ficheiroOriginal.type).toBe(ficheiroNovo.type);

        expect(ficheiroOriginal.lastModified).toBe(
            ficheiroNovo.lastModified,
        );

        const estadoAnterior =
            tratador.obterEstadoCamposFormulario();

        expect(
            estadoAnterior.ficheiros[0].selecionados[0],
        ).toBe(ficheiroOriginal);

        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockReturnValue(pendente.promessa);

        const finalizar = vi.spyOn(
            tratador,
            'finalizarSubmissao',
        );

        const submissao = tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        ficheirosSelecionados = [
            ficheiroNovo,
        ];

        const estadoAtual =
            tratador.obterEstadoCamposFormulario();

        expect(estadoAtual.conteudo).toBe(
            estadoAnterior.conteudo,
        );

        expect(
            estadoAtual.ficheiros[0].selecionados[0],
        ).toBe(ficheiroNovo);

        expect(
            tratador.camposFormularioMantidos(
                estadoAnterior,
            ),
        ).toBe(false);

        pendente.resolver({
            data: {mensagem: 'OK'},
        });

        await submissao;

        expect(finalizar).not.toHaveBeenCalled();

        expect(ficheirosSelecionados[0]).toBe(
            ficheiroNovo,
        );

        expect(botao.disabled).toBe(false);
        expect(tratador.emSubmissao).toBe(false);
    });

    it('permite limpeza quando o ficheiro nao mudou', async () => {
        const anexo = document.createElement('input');

        anexo.type = 'file';
        anexo.name = 'anexo';

        formulario.append(anexo);

        const ficheiro = new File(
            ['AAA'],
            'documento.txt',
            {
                type: 'text/plain',
                lastModified: 123456789,
            },
        );

        Object.defineProperty(
            anexo,
            'files',
            {
                configurable: true,
                get: () => [ficheiro],
            },
        );

        const finalizar = vi.spyOn(
            tratador,
            'finalizarSubmissao',
        );

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {mensagem: 'OK'},
        });

        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(finalizar).toHaveBeenCalledTimes(1);

        expect(botao.disabled).toBe(false);
        expect(tratador.emSubmissao).toBe(false);
    });

    it(
        'distingue falha no callback de erro HTTP sem repetir pedido',
        async () => {
            const aoSucesso = vi.fn(() => {
                throw new Error(
                    'Resposta incompleta simulada.',
                );
            });

            const outroTratador = new TratadorFormularioAjax(
                'teste-concorrencia-ajax',
                '/comentarios',
                aoSucesso,
            );

            const sucessoVisual = vi.spyOn(
                outroTratador,
                'mostrarMensagemSucesso',
            ).mockImplementation(() => {});

            const avisoVisual = vi.spyOn(
                outroTratador,
                'mostrarAvisoAtualizacaoInterface',
            ).mockImplementation(() => {});

            const erroVisual = vi.spyOn(
                outroTratador,
                'tratarErroSubmissao',
            ).mockImplementation(() => {});

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockResolvedValue({
                data: {
                    mensagem: 'Operação guardada.',
                },
            });

            const eventoSucesso = vi.fn();

            document.addEventListener(
                'formulario-ajax:sucesso',
                eventoSucesso,
            );

            try {
                campo.value = 'Mensagem submetida';

                await outroTratador.submeter();

                expect(pedido).toHaveBeenCalledTimes(1);
                expect(aoSucesso).toHaveBeenCalledTimes(1);

                expect(eventoSucesso).toHaveBeenCalledTimes(1);

                expect(sucessoVisual).not.toHaveBeenCalled();
                expect(avisoVisual).toHaveBeenCalledTimes(1);
                expect(erroVisual).not.toHaveBeenCalled();

                expect(outroTratador.emSubmissao).toBe(false);
                expect(botao.disabled).toBe(false);
            } finally {
                document.removeEventListener(
                    'formulario-ajax:sucesso',
                    eventoSucesso,
                );
            }
        },
    );

});
