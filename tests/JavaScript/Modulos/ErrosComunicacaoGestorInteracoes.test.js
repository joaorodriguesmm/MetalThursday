import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorAlertas
    from '../../../resources/js/modulos/GestorAlertas.js';
import GestorInteracoes
    from '../../../resources/js/modulos/GestorInteracoes.js';

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

describe('B25.55 — falhas de comunicacao nas interacoes', () => {
    let gestor;
    let alerta;

    beforeEach(() => {
        // O metodo de mensagens nao depende do DOM nem
        // do estado criado pelo construtor.
        gestor = Object.create(
            GestorInteracoes.prototype,
        );

        alerta = vi.spyOn(
            GestorAlertas,
            'mostrarErro',
        ).mockResolvedValue(undefined);
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it.each([
        [
            'eliminacao',
            'Não foi possível eliminar o comentário.',
        ],
        [
            'alternancia',
            'Não foi possível atualizar a interação.',
        ],
        [
            'edicao',
            undefined,
        ],
    ])(
        'B25.55 — %s apresenta resultado incerto numa falha de rede',
        async (_operacao, mensagemConfigurada) => {
            await gestor.mostrarErroPedido(
                criarErroComunicacao(),
                mensagemConfigurada,
            );

            expect(alerta).toHaveBeenCalledTimes(1);

            expect(alerta).toHaveBeenCalledWith(
                MENSAGEM_RESULTADO_INCERTO,
            );
        },
    );

    it('preserva a mensagem devolvida num erro HTTP', async () => {
        const erro = criarErroHttp(
            409,
            {
                mensagem:
                    'A operação já não pode ser executada.',
            },
        );

        await gestor.mostrarErroPedido(
            erro,
            'Mensagem personalizada.',
        );

        expect(alerta).toHaveBeenCalledTimes(1);

        expect(alerta).toHaveBeenCalledWith(
            'A operação já não pode ser executada.',
        );
    });

    it('preserva a mensagem configurada num erro HTTP', async () => {
        await gestor.mostrarErroPedido(
            criarErroHttp(500),
            'Não foi possível eliminar o comentário.',
        );

        expect(alerta).toHaveBeenCalledTimes(1);

        expect(alerta).toHaveBeenCalledWith(
            'Não foi possível eliminar o comentário.',
        );
    });

    it('preserva a mensagem para erros locais', async () => {
        await gestor.mostrarErroPedido(
            new Error('Erro interno de processamento.'),
            'Mensagem de erro configurada.',
        );

        expect(alerta).toHaveBeenCalledTimes(1);

        expect(alerta).toHaveBeenCalledWith(
            'Mensagem de erro configurada.',
        );
    });

    it('nao confunde um erro de configuracao Axios com falha de rede', async () => {
        const erro = new axios.AxiosError(
            'Configuração inválida.',
            'ERR_BAD_OPTION',
        );

        await gestor.mostrarErroPedido(
            erro,
            'Mensagem de erro configurada.',
        );

        expect(alerta).toHaveBeenCalledTimes(1);

        expect(alerta).toHaveBeenCalledWith(
            'Mensagem de erro configurada.',
        );
    });

    it('mantem o fallback para erros sem mensagem', async () => {
        await gestor.mostrarErroPedido(
            null,
        );

        expect(alerta).toHaveBeenCalledTimes(1);

        expect(alerta).toHaveBeenCalledWith(
            'Ocorreu um erro ao processar a ação.',
        );
    });
});
