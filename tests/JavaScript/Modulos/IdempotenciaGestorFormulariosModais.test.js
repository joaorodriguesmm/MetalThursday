import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';

import GestorFormulariosModais
    from '../../../resources/js/modulos/GestorFormulariosModais.js';

import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

function criarConfiguracao(identificador) {
    return {
        idFormulario: identificador,
        url: '/teste/submissao',
        regrasValidacao: {
            conteudo: ['obrigatorio'],
        },
        mensagensValidacao: {
            conteudo: {
                obrigatorio: 'O conteúdo é obrigatório.',
            },
        },
    };
}

function submeterFormulario(formulario) {
    const evento = new SubmitEvent(
        'submit',
        {
            bubbles: true,
            cancelable: true,
        },
    );

    formulario.dispatchEvent(evento);

    return evento;
}

describe('B25.17 — inicializacao dos formularios modais', () => {
    let formulario;
    let pedido;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-b25-17">
                <textarea name="conteudo">Mensagem inicial</textarea>
                <button type="submit">Enviar</button>
            </form>
        `;

        formulario = document.getElementById(
            'formulario-b25-17',
        );

        pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {
                mensagem: 'OK',
            },
        });

        vi.spyOn(
            TratadorFormularioAjax.prototype,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        vi.spyOn(
            TratadorFormularioAjax.prototype,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('submete normalmente um formulario inicializado uma vez', async () => {
        new GestorFormulariosModais([
            criarConfiguracao('formulario-b25-17'),
        ]);

        const evento = submeterFormulario(
            formulario,
        );

        expect(evento.defaultPrevented).toBe(true);
        expect(pedido).toHaveBeenCalledTimes(1);

        await vi.waitFor(() => {
            expect(
                formulario.querySelector('button').disabled,
            ).toBe(false);
        });
    });

    it('impede submissao quando a validacao falha', async () => {
        new GestorFormulariosModais([
            criarConfiguracao('formulario-b25-17'),
        ]);

        formulario.querySelector(
            '[name="conteudo"]',
        ).value = '';

        const evento = submeterFormulario(
            formulario,
        );

        expect(evento.defaultPrevented).toBe(true);
        expect(pedido).not.toHaveBeenCalled();
    });

    it('mantem independentes dois formularios distintos', async () => {
        document.body.insertAdjacentHTML(
            'beforeend',
            `
                <form id="outro-formulario-b25-17">
                    <textarea name="conteudo">Outra mensagem</textarea>
                    <button type="submit">Enviar</button>
                </form>
            `,
        );

        new GestorFormulariosModais([
            criarConfiguracao('formulario-b25-17'),
            criarConfiguracao('outro-formulario-b25-17'),
        ]);

        const outroFormulario = document.getElementById(
            'outro-formulario-b25-17',
        );

        submeterFormulario(formulario);
        submeterFormulario(outroFormulario);

        expect(pedido).toHaveBeenCalledTimes(2);

        await vi.waitFor(() => {
            expect(
                formulario.querySelector('button').disabled,
            ).toBe(false);

            expect(
                outroFormulario.querySelector('button').disabled,
            ).toBe(false);
        });
    });

    it('nao duplica pedidos quando o mesmo formulario e inicializado duas vezes', async () => {
        const configuracao = criarConfiguracao(
            'formulario-b25-17',
        );

        new GestorFormulariosModais([
            configuracao,
        ]);

        new GestorFormulariosModais([
            configuracao,
        ]);

        const evento = submeterFormulario(
            formulario,
        );

        expect(evento.defaultPrevented).toBe(true);

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('usa mensagem predefinida no campo obrigatorio', () => {
        const configuracao = criarConfiguracao(
            'formulario-b25-17',
        );

        configuracao.mensagensValidacao = {};

        new GestorFormulariosModais([
            configuracao,
        ]);

        formulario.querySelector(
            '[name="conteudo"]',
        ).value = '';

        const evento = submeterFormulario(
            formulario,
        );

        expect(evento.defaultPrevented).toBe(true);
        expect(pedido).not.toHaveBeenCalled();
    });

    it('inicializa um novo elemento com o mesmo identificador', async () => {
        const configuracao = criarConfiguracao(
            'formulario-b25-17',
        );

        new GestorFormulariosModais([
            configuracao,
        ]);

        formulario.remove();

        const substituto = document.createElement('form');

        substituto.id = 'formulario-b25-17';

        substituto.innerHTML =
            '<textarea name="conteudo">Nova mensagem</textarea>'
            + '<button type="submit">Enviar</button>';

        document.body.append(substituto);

        new GestorFormulariosModais([
            configuracao,
        ]);

        const evento = submeterFormulario(
            substituto,
        );

        expect(evento.defaultPrevented).toBe(true);
        expect(pedido).toHaveBeenCalledTimes(1);

        await vi.waitFor(() => {
            expect(
                substituto.querySelector('button').disabled,
            ).toBe(false);
        });
    });

    it(
        'entrega ao callback o contexto capturado antes do pedido HTTP',
        async () => {
            let concluirPedido;

            const respostaPendente = new Promise((resolve) => {
                concluirPedido = resolve;
            });

            pedido.mockReturnValue(respostaPendente);

            let destino = 'seccao-a';

            const aoSucesso = vi.fn();

            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),

                    obterContextoSubmissao: () => ({
                        destino,
                    }),

                    aoSucesso,
                },
            ]);

            const evento = submeterFormulario(
                formulario,
            );

            expect(evento.defaultPrevented).toBe(true);
            expect(pedido).toHaveBeenCalledTimes(1);

            destino = 'seccao-b';

            concluirPedido({
                data: {
                    mensagem: 'Operação criada.',
                },
            });

            await vi.waitFor(() => {
                expect(
                    aoSucesso,
                ).toHaveBeenCalledExactlyOnceWith(
                    {
                        mensagem: 'Operação criada.',
                    },
                    {
                        destino: 'seccao-a',
                    },
                );
            });
        },
    );

});
