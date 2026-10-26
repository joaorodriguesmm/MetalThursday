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


    it(
        'B25.48 — nova inicializacao usa callback e contexto atuais',
        async () => {
            const callbackAntigo = vi.fn();
            const callbackNovo = vi.fn();

            // Primeira inicializacao do formulario modal.
            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    obterContextoSubmissao: () => ({
                        inicializacao: 'antiga',
                    }),
                    aoSucesso: callbackAntigo,
                },
            ]);

            // Uma nova inicializacao principal reutiliza
            // o mesmo formulario modal, mas tem callbacks
            // e contexto de submissao diferentes.
            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    obterContextoSubmissao: () => ({
                        inicializacao: 'nova',
                    }),
                    aoSucesso: callbackNovo,
                },
            ]);

            const evento = submeterFormulario(
                formulario,
            );

            expect(evento.defaultPrevented).toBe(true);

            // A reutilizacao nao pode duplicar pedidos.
            expect(pedido).toHaveBeenCalledTimes(1);

            // A submissao comecou depois da segunda
            // inicializacao, logo pertence ao novo contexto.
            await vi.waitFor(() => {
                expect(callbackNovo)
                    .toHaveBeenCalledExactlyOnceWith(
                        {
                            mensagem: 'OK',
                        },
                        {
                            inicializacao: 'nova',
                        },
                    );
            });

            expect(callbackAntigo)
                .not.toHaveBeenCalled();

            expect(pedido).toHaveBeenCalledTimes(1);
        },
    );


    it(
        'B25.48 — resposta anterior nao usa callbacks apos reinicializacao',
        async () => {
            let concluirPedido;

            const respostaPendente = new Promise(
                (resolve) => {
                    concluirPedido = resolve;
                },
            );

            pedido.mockReturnValue(respostaPendente);

            const callbackAntigo = vi.fn();
            const callbackNovo = vi.fn();
            const eventoSucesso = vi.fn();

            document.addEventListener(
                'formulario-ajax:sucesso',
                eventoSucesso,
            );

            try {
                new GestorFormulariosModais([
                    {
                        ...criarConfiguracao(
                            'formulario-b25-17',
                        ),
                        obterContextoSubmissao: () => ({
                            inicializacao: 'antiga',
                        }),
                        aoSucesso: callbackAntigo,
                    },
                ]);

                const evento = submeterFormulario(
                    formulario,
                );

                expect(evento.defaultPrevented).toBe(true);
                expect(pedido).toHaveBeenCalledTimes(1);

                expect(
                    formulario.querySelector('button')
                        .disabled,
                ).toBe(true);

                // A primeira operacao continua pendente,
                // mas uma nova inicializacao assume
                // o mesmo formulario modal.
                new GestorFormulariosModais([
                    {
                        ...criarConfiguracao(
                            'formulario-b25-17',
                        ),
                        obterContextoSubmissao: () => ({
                            inicializacao: 'nova',
                        }),
                        aoSucesso: callbackNovo,
                    },
                ]);

                concluirPedido({
                    data: {
                        mensagem: 'Operacao antiga concluida.',
                    },
                });

                await vi.waitFor(() => {
                    expect(
                        formulario.querySelector('button')
                            .disabled,
                    ).toBe(false);
                });

                // Uma resposta iniciada antes da
                // reinicializacao nao pode executar
                // callbacks de nenhuma das versoes.
                expect(callbackAntigo)
                    .not.toHaveBeenCalled();

                expect(callbackNovo)
                    .not.toHaveBeenCalled();

                // Tambem nao deve publicar um evento
                // global pertencente ao contexto antigo.
                expect(eventoSucesso)
                    .not.toHaveBeenCalled();

                expect(pedido).toHaveBeenCalledTimes(1);
            } finally {
                document.removeEventListener(
                    'formulario-ajax:sucesso',
                    eventoSucesso,
                );
            }
        },
    );


    it(
        'B25.48 — erro HTTP antigo nao afeta nova inicializacao',
        async () => {
            let rejeitarPedido;

            const respostaPendente = new Promise(
                (_resolve, reject) => {
                    rejeitarPedido = reject;
                },
            );

            pedido.mockReturnValue(respostaPendente);

            const erroAntigo = vi.fn(() => true);
            const erroNovo = vi.fn(() => true);

            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    aoErro: erroAntigo,
                },
            ]);

            const evento = submeterFormulario(
                formulario,
            );

            expect(evento.defaultPrevented).toBe(true);
            expect(pedido).toHaveBeenCalledTimes(1);

            // O pedido antigo continua pendente.
            // O mesmo formulario recebe nova configuracao.
            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    aoErro: erroNovo,
                },
            ]);

            rejeitarPedido(
                new Error(
                    'Erro HTTP da inicializacao antiga',
                ),
            );

            await vi.waitFor(() => {
                expect(
                    formulario.querySelector('button')
                        .disabled,
                ).toBe(false);
            });

            // A resposta antiga nao pode executar
            // callbacks de nenhuma das configuracoes.
            expect(erroAntigo).not.toHaveBeenCalled();
            expect(erroNovo).not.toHaveBeenCalled();

            // Uma submissao posterior ja deve utilizar
            // o callback da nova configuracao.
            pedido.mockRejectedValueOnce(
                new Error(
                    'Erro HTTP da nova inicializacao',
                ),
            );

            const novoEvento = submeterFormulario(
                formulario,
            );

            expect(novoEvento.defaultPrevented)
                .toBe(true);

            await vi.waitFor(() => {
                expect(erroNovo)
                    .toHaveBeenCalledTimes(1);
            });

            expect(erroAntigo).not.toHaveBeenCalled();
            expect(pedido).toHaveBeenCalledTimes(2);
        },
    );

    it(
        'B25.48 — configuracao invalida conserva callbacks anteriores',
        async () => {
            const callbackAntigo = vi.fn();
            const callbackNovo = vi.fn();

            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    aoSucesso: callbackAntigo,
                },
            ]);

            // A primeira configuracao e valida,
            // mas a segunda contem um URL invalido.
            expect(
                () => new GestorFormulariosModais([
                    {
                        ...criarConfiguracao(
                            'formulario-b25-17',
                        ),
                        aoSucesso: callbackNovo,
                    },
                    {
                        ...criarConfiguracao(
                            'outro-formulario-b25-17',
                        ),
                        url: '',
                    },
                ]),
            ).toThrow(
                'Cada formulário modal deve possuir um endereço de submissão válido.',
            );

            // Uma tentativa falhada nao pode alterar
            // os callbacks anteriormente funcionais.
            const evento = submeterFormulario(
                formulario,
            );

            expect(evento.defaultPrevented).toBe(true);
            expect(pedido).toHaveBeenCalledTimes(1);

            await vi.waitFor(() => {
                expect(callbackAntigo)
                    .toHaveBeenCalledTimes(1);
            });

            expect(callbackNovo).not.toHaveBeenCalled();
            expect(pedido).toHaveBeenCalledTimes(1);
        },
    );


    it(
        'B25.48 — falha do validador conserva callbacks existentes',
        async () => {
            const callbackAntigo = vi.fn();
            const callbackNovo = vi.fn();

            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    aoSucesso: callbackAntigo,
                },
            ]);

            document.body.insertAdjacentHTML(
                'beforeend',
                `
                    <form id="formulario-b25-48-segundo">
                        <textarea name="conteudo">
                            Segunda mensagem
                        </textarea>
                        <button type="submit">Enviar</button>
                    </form>
                `,
            );

            // Todas as configuracoes passam na
            // normalizacao do gestor AJAX.
            //
            // Contudo, as regras do segundo
            // formulario nao sao uma lista valida
            // para o ValidadorFormulario.

            expect(
                () => new GestorFormulariosModais([
                    {
                        ...criarConfiguracao(
                            'formulario-b25-17',
                        ),
                        aoSucesso: callbackNovo,
                    },
                    {
                        ...criarConfiguracao(
                            'formulario-b25-48-segundo',
                        ),
                        regrasValidacao: {
                            conteudo: 'obrigatorio',
                        },
                    },
                ]),
            ).toThrow(TypeError);

            // O primeiro formulario ja funcionava
            // antes da tentativa mal sucedida.
            // O respetivo callback deve permanecer.
            const evento = submeterFormulario(
                formulario,
            );

            expect(evento.defaultPrevented).toBe(true);
            expect(pedido).toHaveBeenCalledTimes(1);

            await vi.waitFor(() => {
                expect(callbackAntigo)
                    .toHaveBeenCalledTimes(1);
            });

            expect(callbackNovo).not.toHaveBeenCalled();
            expect(pedido).toHaveBeenCalledTimes(1);
        },
    );


    it(
        'B25.49 — nova inicializacao atualiza regras de validacao',
        async () => {
            // A configuracao inicial exige conteudo.
            new GestorFormulariosModais([
                criarConfiguracao(
                    'formulario-b25-17',
                ),
            ]);

            // O formulario modal permanece na DOM,
            // mas a nova configuracao torna o campo opcional.
            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    regrasValidacao: {
                        conteudo: [],
                    },
                },
            ]);

            formulario.querySelector(
                '[name="conteudo"]',
            ).value = '';

            const evento = submeterFormulario(
                formulario,
            );

            expect(evento.defaultPrevented).toBe(true);

            // A configuracao mais recente deve permitir
            // a submissao com o conteudo vazio.
            expect(pedido).toHaveBeenCalledTimes(1);

            await vi.waitFor(() => {
                expect(
                    formulario.querySelector('button').disabled,
                ).toBe(false);
            });
        },
    );


    it(
        'B25.49 — novas regras invalidam resposta AJAX pendente',
        async () => {
            const callbackSucesso = vi.fn();
            const eventosSucesso = vi.fn();

            const configuracao = {
                ...criarConfiguracao(
                    'formulario-b25-17',
                ),
                aoSucesso: callbackSucesso,
            };

            new GestorFormulariosModais([
                configuracao,
            ]);

            let concluirPedido;

            const pendente = new Promise((resolve) => {
                concluirPedido = resolve;
            });

            pedido.mockReturnValueOnce(pendente);

            document.addEventListener(
                'formulario-ajax:sucesso',
                eventosSucesso,
            );

            try {
                formulario.querySelector(
                    '[name="conteudo"]',
                ).value = 'Mensagem antes da alteracao';

                const primeiroEvento = submeterFormulario(
                    formulario,
                );

                expect(primeiroEvento.defaultPrevented)
                    .toBe(true);

                expect(pedido).toHaveBeenCalledTimes(1);

                // Mantemos URL e callbacks identicos.
                // Apenas as regras de validacao mudam.
                new GestorFormulariosModais([
                    {
                        ...configuracao,
                        regrasValidacao: {
                            conteudo: [],
                        },
                    },
                ]);

                concluirPedido({
                    data: {
                        mensagem:
                            'Resposta da configuracao anterior.',
                    },
                });

                await vi.waitFor(() => {
                    expect(
                        formulario.querySelector('button').disabled,
                    ).toBe(false);
                });

                // O pedido HTTP terminou, mas a resposta
                // pertence a regras que ja nao estao ativas.
                expect(callbackSucesso)
                    .not.toHaveBeenCalled();

                expect(eventosSucesso)
                    .not.toHaveBeenCalled();

                expect(pedido).toHaveBeenCalledTimes(1);

                // Uma submissao nova deve funcionar.
                pedido.mockResolvedValueOnce({
                    data: {
                        mensagem: 'Novo pedido concluido.',
                    },
                });

                formulario.querySelector(
                    '[name="conteudo"]',
                ).value = 'Mensagem da nova configuracao';

                const segundoEvento = submeterFormulario(
                    formulario,
                );

                expect(segundoEvento.defaultPrevented)
                    .toBe(true);

                expect(pedido).toHaveBeenCalledTimes(2);

                await vi.waitFor(() => {
                    expect(callbackSucesso)
                        .toHaveBeenCalledTimes(1);

                    expect(eventosSucesso)
                        .toHaveBeenCalledTimes(1);
                });

                expect(
                    eventosSucesso.mock.calls[0][0]
                        .detail.idFormulario,
                ).toBe('formulario-b25-17');
            } finally {
                document.removeEventListener(
                    'formulario-ajax:sucesso',
                    eventosSucesso,
                );
            }
        },
    );


    it(
        'B25.50 — reinicializacao nao sobrepoe pedidos pendentes',
        async () => {
            const callbackAntigo = vi.fn();
            const callbackNovo = vi.fn();

            let concluirPedido;

            const pendente = new Promise((resolve) => {
                concluirPedido = resolve;
            });

            pedido.mockReturnValueOnce(pendente);

            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    aoSucesso: callbackAntigo,
                },
            ]);

            const primeiroEvento = submeterFormulario(
                formulario,
            );

            expect(primeiroEvento.defaultPrevented)
                .toBe(true);

            expect(pedido).toHaveBeenCalledTimes(1);

            const botao = formulario.querySelector(
                'button[type="submit"]',
            );

            expect(botao.disabled).toBe(true);

            // A nova inicializacao altera o URL e
            // o callback enquanto o pedido antigo
            // continua por concluir.
            new GestorFormulariosModais([
                {
                    ...criarConfiguracao(
                        'formulario-b25-17',
                    ),
                    url: '/teste/nova-submissao',
                    aoSucesso: callbackNovo,
                },
            ]);

            // Simular uma tentativa de submissao
            // mesmo com o botao desativado.
            const segundoEvento = submeterFormulario(
                formulario,
            );

            expect(segundoEvento.defaultPrevented)
                .toBe(true);

            // Nenhum pedido pode sobrepor-se ao antigo.
            expect(pedido).toHaveBeenCalledTimes(1);

            expect(callbackAntigo)
                .not.toHaveBeenCalled();

            expect(callbackNovo)
                .not.toHaveBeenCalled();

            concluirPedido({
                data: {
                    mensagem: 'Pedido antigo concluido.',
                },
            });

            await vi.waitFor(() => {
                expect(botao.disabled).toBe(false);
            });

            expect(callbackAntigo)
                .not.toHaveBeenCalled();

            expect(callbackNovo)
                .not.toHaveBeenCalled();

            // Depois de terminar o primeiro pedido,
            // a nova configuracao deve funcionar.
            const terceiroEvento = submeterFormulario(
                formulario,
            );

            expect(terceiroEvento.defaultPrevented)
                .toBe(true);

            expect(pedido).toHaveBeenCalledTimes(2);

            expect(pedido.mock.calls[1][0])
                .toBe('/teste/nova-submissao');

            await vi.waitFor(() => {
                expect(callbackNovo)
                    .toHaveBeenCalledTimes(1);
            });

            expect(callbackAntigo)
                .not.toHaveBeenCalled();

            expect(botao.disabled).toBe(false);
        },
    );

});
