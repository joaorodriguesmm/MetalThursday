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
import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

function criarErroComunicacao() {
    return new axios.AxiosError(
        'A ligação foi interrompida.',
        'ERR_NETWORK',
        {},
        {readyState: 4},
    );
}

function criarErroHttp() {
    return new axios.AxiosError(
        'Erro HTTP 500.',
        'ERR_BAD_RESPONSE',
        {},
        {},
        {
            data: {},
            status: 500,
            statusText: 'Internal Server Error',
            headers: {},
            config: {},
        },
    );
}

describe('B25.51 — falhas de comunicacao AJAX', () => {
    let formulario;
    let campo;
    let botao;
    let alerta;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-b25-51">
                <textarea name="conteudo">Texto original</textarea>
                <button type="submit">Enviar</button>
            </form>
        `;

        formulario = document.getElementById(
            'formulario-b25-51',
        );

        campo = formulario.querySelector(
            '[name="conteudo"]',
        );

        botao = formulario.querySelector(
            'button[type="submit"]',
        );

        alerta = vi.spyOn(
            GestorAlertas,
            'mostrarErro',
        ).mockResolvedValue(undefined);
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('avisa que uma falha de rede tem resultado incerto', async () => {
        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockRejectedValueOnce(
            criarErroComunicacao(),
        );

        const tratador = new TratadorFormularioAjax(
            'formulario-b25-51',
            '/teste/submissao',
        );

        campo.value = 'Conteudo que pode ter sido guardado';

        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        expect(alerta).toHaveBeenCalledWith(
            'Não foi possível confirmar se a operação foi concluída. '
            + 'Verifica o resultado antes de tentares novamente.',
        );

        expect(campo.value).toBe(
            'Conteudo que pode ter sido guardado',
        );

        expect(botao.disabled).toBe(false);
        expect(botao.hasAttribute('aria-busy')).toBe(false);
        expect(tratador.emSubmissao).toBe(false);
    });

    it('mantem a mensagem existente para HTTP 500', async () => {
        vi.spyOn(
            axios,
            'post',
        ).mockRejectedValueOnce(
            criarErroHttp(),
        );

        const tratador = new TratadorFormularioAjax(
            'formulario-b25-51',
            '/teste/submissao',
        );

        await tratador.submeter();

        expect(alerta).toHaveBeenCalledWith(
            'Ocorreu um erro inesperado. Tenta novamente.',
        );

        expect(botao.disabled).toBe(false);
        expect(tratador.emSubmissao).toBe(false);
    });

    it('respeita mensagem personalizada em erro HTTP', async () => {
        formulario.dataset.mensagemErro =
            'Mensagem personalizada do formulario.';

        vi.spyOn(
            axios,
            'post',
        ).mockRejectedValueOnce(
            criarErroHttp(),
        );

        const tratador = new TratadorFormularioAjax(
            'formulario-b25-51',
            '/teste/submissao',
        );

        await tratador.submeter();

        expect(alerta).toHaveBeenCalledWith(
            'Mensagem personalizada do formulario.',
        );

        expect(tratador.emSubmissao).toBe(false);
    });


    it.each([
        [
            'comentario',
            'Não foi possível publicar o comentário.',
        ],
        [
            'resposta',
            'Não foi possível publicar a resposta.',
        ],
    ])(
        'B25.54 — falha de rede em %s comunica resultado incerto',
        async (_tipo, mensagemConfigurada) => {
            formulario.dataset.mensagemErro =
                mensagemConfigurada;

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockRejectedValueOnce(
                criarErroComunicacao(),
            );

            const tratador = new TratadorFormularioAjax(
                'formulario-b25-51',
                '/teste/submissao',
            );

            campo.value = 'Publicacao com resultado incerto';

            await tratador.submeter();

            expect(pedido).toHaveBeenCalledTimes(1);

            // A mensagem personalizada nao deve
            // ocultar o resultado incerto da operacao.
            expect(alerta).toHaveBeenCalledWith(
                'Não foi possível confirmar se a operação foi concluída. '
                + 'Verifica o resultado antes de tentares novamente.',
            );

            expect(campo.value).toBe(
                'Publicacao com resultado incerto',
            );

            expect(botao.disabled).toBe(false);
            expect(botao.hasAttribute('aria-busy'))
                .toBe(false);

            expect(tratador.emSubmissao).toBe(false);
        },
    );

    it('nao apresenta alerta se o callback tratar o erro', async () => {
        const aoErro = vi.fn(() => true);

        vi.spyOn(
            axios,
            'post',
        ).mockRejectedValueOnce(
            criarErroComunicacao(),
        );

        const tratador = new TratadorFormularioAjax(
            'formulario-b25-51',
            '/teste/submissao',
            null,
            aoErro,
        );

        await tratador.submeter();

        expect(aoErro).toHaveBeenCalledTimes(1);
        expect(alerta).not.toHaveBeenCalled();

        expect(botao.disabled).toBe(false);
        expect(tratador.emSubmissao).toBe(false);
    });
});
