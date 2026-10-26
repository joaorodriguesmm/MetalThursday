import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorModalAvaliacao
    from '../../../resources/js/modulos/GestorModalAvaliacao.js';

const MENSAGEM_RESULTADO_INCERTO =
    'Não foi possível confirmar se a operação foi concluída. '
    + 'Verifica o resultado antes de tentares novamente.';

function criarErroComunicacao() {
    return new axios.AxiosError(
        'A ligação foi interrompida.',
        'ERR_NETWORK',
        {},
        {readyState: 4},
    );
}

function criarErroHttp(
    status,
    dados = {},
) {
    return new axios.AxiosError(
        `Erro HTTP ${status}.`,
        'ERR_BAD_RESPONSE',
        {},
        {},
        {
            data: dados,
            status,
            statusText: 'Erro HTTP',
            headers: {},
            config: {},
        },
    );
}

describe('B25.56 — erros de comunicacao ao guardar avaliacao', () => {
    let gestor;
    let apresentarErro;

    beforeEach(() => {
        // Isola o metodo de apresentacao de erros.
        // A submissao HTTP sera testada separadamente
        // atraves dos testes de integracao do modal.
        gestor = Object.create(
            GestorModalAvaliacao.prototype,
        );

        gestor.formulario = {
            dataset: {
                mensagemErro:
                    'Não foi possível guardar a avaliação.',
            },
        };

        gestor.pontuacaoSelecionada = 3;

        apresentarErro = vi.spyOn(
            gestor,
            'apresentarErroAvaliacao',
        ).mockImplementation(() => {});

        vi.spyOn(
            gestor,
            'focarEstrelaCorrespondente',
        ).mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it(
        'B25.56 — falha de rede nao afirma que a avaliacao falhou',
        () => {
            gestor.tratarErroSubmissao(
                criarErroComunicacao(),
            );

            expect(apresentarErro)
                .toHaveBeenCalledTimes(1);

            expect(apresentarErro)
                .toHaveBeenCalledWith(
                    MENSAGEM_RESULTADO_INCERTO,
                );

            expect(
                gestor.focarEstrelaCorrespondente,
            ).toHaveBeenCalledWith(3);
        },
    );

    it(
        'preserva a mensagem personalizada num erro HTTP',
        () => {
            gestor.tratarErroSubmissao(
                criarErroHttp(500),
            );

            expect(apresentarErro)
                .toHaveBeenCalledWith(
                    'Não foi possível guardar a avaliação.',
                );
        },
    );

    it(
        'preserva a mensagem de validacao HTTP 422',
        () => {
            vi.spyOn(
                gestor,
                'obterPrimeiraMensagem',
            ).mockReturnValue(
                'A pontuação selecionada é inválida.',
            );

            gestor.tratarErroSubmissao(
                criarErroHttp(
                    422,
                    {
                        errors: {
                            pontuacao: [
                                'A pontuação selecionada é inválida.',
                            ],
                        },
                    },
                ),
            );

            expect(apresentarErro)
                .toHaveBeenCalledWith(
                    'A pontuação selecionada é inválida.',
                );
        },
    );

    it(
        'nao confunde erro de configuracao Axios com falha de rede',
        () => {
            const erro = new axios.AxiosError(
                'Configuração inválida.',
                'ERR_BAD_OPTION',
            );

            gestor.tratarErroSubmissao(erro);

            expect(apresentarErro)
                .toHaveBeenCalledWith(
                    'Não foi possível guardar a avaliação.',
                );
        },
    );

    it(
        'preserva o fallback sem mensagem personalizada',
        () => {
            gestor.formulario.dataset.mensagemErro = '';

            gestor.tratarErroSubmissao(
                new Error('Falha local.'),
            );

            expect(apresentarErro)
                .toHaveBeenCalledWith(
                    'Não foi possível guardar a avaliação.',
                );
        },
    );
});
