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

describe('idempotência dos formulários AJAX', () => {
    let formulario;
    let campo;
    let tratador;

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="teste-idempotencia" data-idempotencia-submissao>
                <textarea name="conteudo">Mensagem original</textarea>
                <button type="submit">Publicar</button>
            </form>
        `;

        formulario = document.getElementById('teste-idempotencia');
        campo = formulario.querySelector('[name="conteudo"]');

        tratador = new TratadorFormularioAjax(
            'teste-idempotencia',
            '/comentarios',
        );

        vi.spyOn(tratador, 'tratarErroSubmissao')
            .mockImplementation(() => {});

        vi.spyOn(tratador, 'mostrarMensagemSucesso')
            .mockImplementation(() => {});

        vi.spyOn(tratador, 'mostrarAvisoAtualizacaoInterface')
            .mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('gera UUID v4 e conserva a chave sem alterar o texto', () => {
        const primeira = tratador.prepararDadosSubmissao();
        const segunda = tratador.prepararDadosSubmissao();
        const chave = primeira.get('chave_submissao');

        expect(chave).toMatch(
            /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/,
        );

        expect(segunda.get('chave_submissao')).toBe(chave);
        expect(primeira.get('conteudo')).toBe('Mensagem original');
    });

    it('usa uma chave nova quando o texto é alterado', () => {
        const primeira = tratador.prepararDadosSubmissao();

        campo.value = 'Mensagem alterada';

        const segunda = tratador.prepararDadosSubmissao();

        expect(segunda.get('chave_submissao'))
            .not.toBe(primeira.get('chave_submissao'));

        expect(segunda.get('conteudo')).toBe('Mensagem alterada');
    });

    it('não altera formulários AJAX sem idempotência', () => {
        formulario.removeAttribute('data-idempotencia-submissao');

        const dados = tratador.prepararDadosSubmissao();

        expect(dados.has('chave_submissao')).toBe(false);
        expect(tratador.chaveSubmissao).toBeNull();
    });

    it('reutiliza a chave após uma falha HTTP', async () => {
        const pedido = vi.spyOn(axios, 'post')
            .mockRejectedValueOnce(new Error('Resposta perdida'))
            .mockResolvedValueOnce({
                data: {mensagem: 'OK'},
            });

        await tratador.submeter();
        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(2);

        const primeira = pedido.mock.calls[0][1];
        const segunda = pedido.mock.calls[1][1];

        expect(primeira.get('chave_submissao'))
            .toBe(segunda.get('chave_submissao'));

        expect(primeira.get('conteudo'))
            .toBe(segunda.get('conteudo'));
    });

    it('renova a chave depois da confirmação HTTP', async () => {
        const pedido = vi.spyOn(axios, 'post')
            .mockResolvedValue({
                data: {mensagem: 'OK'},
            });

        await tratador.submeter();

        campo.value = 'Nova publicação';

        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(2);

        expect(pedido.mock.calls[0][1].get('chave_submissao'))
            .not.toBe(pedido.mock.calls[1][1].get('chave_submissao'));

        expect(pedido.mock.calls[1][1].get('conteudo'))
            .toBe('Nova publicação');
    });

    it('impede pedidos concorrentes no mesmo formulário', async () => {
        let concluirPedido;

        const respostaPendente = new Promise((resolve) => {
            concluirPedido = resolve;
        });

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValue(respostaPendente);

        const primeiraSubmissao = tratador.submeter();

        await tratador.submeter();

        expect(pedido).toHaveBeenCalledTimes(1);

        concluirPedido({
            data: {mensagem: 'OK'},
        });

        await primeiraSubmissao;

        expect(tratador.emSubmissao).toBe(false);
    });
});
