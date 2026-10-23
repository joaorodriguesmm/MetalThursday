import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import HistoricoArtistaMetalThursday
    from '../../../resources/js/modulos/HistoricoArtistaMetalThursday.js';

function criarPedidoPendente() {
    let resolver;
    let rejeitar;

    const promessa = new Promise((resolve, reject) => {
        resolver = resolve;
        rejeitar = reject;
    });

    return {
        promessa,
        resolver,
        rejeitar,
    };
}

function criarSeccao(formulario) {
    const seccao = document.createElement('div');

    seccao.className = 'item-seccao';

    seccao.innerHTML = `
        <select class="tom-select-artistas">
            <option value="">Selecionar artista</option>
            <option value="1">Artista 1</option>
            <option value="2">Artista 2</option>
        </select>
    `;

    formulario.append(seccao);

    return {
        seccao,
        selecao: seccao.querySelector(
            '.tom-select-artistas',
        ),
    };
}

describe('B25.15 — concorrencia no historico de artistas', () => {
    let formulario;
    let gestor;
    let primeiraSeccao;
    let carregar;
    let apresentar;
    let apresentarErro;

    beforeEach(() => {
        document.body.innerHTML = `
            <form
                data-endereco-historico-artista="/artistas/__IDENTIFICADOR_ARTISTA__/historico"
            ></form>
        `;

        formulario = document.querySelector('form');

        primeiraSeccao = criarSeccao(formulario);

        gestor = new HistoricoArtistaMetalThursday(
            formulario,
        );

        carregar = vi.spyOn(
            gestor,
            'obterAparicoes',
        );

        vi.spyOn(
            gestor,
            'apresentarCarregamento',
        ).mockImplementation(() => {});

        apresentar = vi.spyOn(
            gestor,
            'apresentarAparicoes',
        ).mockImplementation(() => {});

        apresentarErro = vi.spyOn(
            gestor,
            'apresentarErro',
        ).mockImplementation(() => {});

        vi.spyOn(
            gestor,
            'ocultarHistorico',
        ).mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('mantem apenas a resposta da selecao mais recente', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        carregar
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        primeiraSeccao.selecao.value = '1';

        const operacaoAntiga =
            gestor.atualizarHistoricoSelecao(
                primeiraSeccao.selecao,
            );

        primeiraSeccao.selecao.value = '2';

        const operacaoNova =
            gestor.atualizarHistoricoSelecao(
                primeiraSeccao.selecao,
            );

        expect(
            carregar.mock.calls[0][1].aborted,
        ).toBe(true);

        segunda.resolver([{identificador: 2}]);

        await operacaoNova;

        primeira.resolver([{identificador: 1}]);

        await operacaoAntiga;

        expect(apresentar).toHaveBeenCalledTimes(1);

        expect(apresentar).toHaveBeenCalledWith(
            primeiraSeccao.seccao,
            [{identificador: 2}],
        );
    });

    it('ignora erro do pedido anteriormente cancelado', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        carregar
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        primeiraSeccao.selecao.value = '1';

        const antiga = gestor.atualizarHistoricoSelecao(
            primeiraSeccao.selecao,
        );

        primeiraSeccao.selecao.value = '2';

        const atual = gestor.atualizarHistoricoSelecao(
            primeiraSeccao.selecao,
        );

        primeira.rejeitar(
            new Error('Erro do pedido antigo.'),
        );

        await antiga;

        segunda.resolver([]);

        await atual;

        expect(apresentarErro).not.toHaveBeenCalled();
        expect(apresentar).toHaveBeenCalledTimes(1);
    });

    it('mantem independentes os pedidos de duas seccoes', async () => {
        const segundaSeccao = criarSeccao(formulario);

        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        carregar
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        primeiraSeccao.selecao.value = '1';
        segundaSeccao.selecao.value = '2';

        const operacaoPrimeira =
            gestor.atualizarHistoricoSelecao(
                primeiraSeccao.selecao,
            );

        const operacaoSegunda =
            gestor.atualizarHistoricoSelecao(
                segundaSeccao.selecao,
            );

        expect(carregar).toHaveBeenCalledTimes(2);

        expect(
            carregar.mock.calls[0][1].aborted,
        ).toBe(false);

        expect(
            carregar.mock.calls[1][1].aborted,
        ).toBe(false);

        primeira.resolver([{identificador: 1}]);
        segunda.resolver([{identificador: 2}]);

        await Promise.all([
            operacaoPrimeira,
            operacaoSegunda,
        ]);

        expect(apresentar).toHaveBeenCalledTimes(2);
    });

    it('ignora resposta depois de remover apenas a seccao', async () => {
        const pendente = criarPedidoPendente();

        carregar.mockReturnValue(
            pendente.promessa,
        );

        primeiraSeccao.selecao.value = '1';

        const operacao = gestor.atualizarHistoricoSelecao(
            primeiraSeccao.selecao,
        );

        primeiraSeccao.seccao.remove();

        pendente.resolver([{identificador: 1}]);

        await operacao;

        expect(apresentar).not.toHaveBeenCalled();
        expect(apresentarErro).not.toHaveBeenCalled();
    });

    it('ignora resposta depois de remover o formulario inteiro', async () => {
        const pendente = criarPedidoPendente();

        carregar.mockReturnValue(
            pendente.promessa,
        );

        primeiraSeccao.selecao.value = '1';

        const operacao = gestor.atualizarHistoricoSelecao(
            primeiraSeccao.selecao,
        );

        formulario.remove();

        pendente.resolver([{identificador: 1}]);

        await operacao;

        expect(apresentar).not.toHaveBeenCalled();
        expect(apresentarErro).not.toHaveBeenCalled();
    });

    it('ignora resposta iniciada antes de reset do formulario', async () => {
        const pendente = criarPedidoPendente();

        carregar.mockReturnValue(
            pendente.promessa,
        );

        primeiraSeccao.selecao.value = '1';

        const operacao = gestor.atualizarHistoricoSelecao(
            primeiraSeccao.selecao,
        );

        formulario.reset();

        expect(primeiraSeccao.selecao.value).toBe('');

        pendente.resolver([{identificador: 1}]);

        await operacao;

        expect(apresentar).not.toHaveBeenCalled();
    });

    it('ignora erro recebido depois de remover o formulario', async () => {
        const pendente = criarPedidoPendente();

        carregar.mockReturnValue(
            pendente.promessa,
        );

        primeiraSeccao.selecao.value = '1';

        const operacao = gestor.atualizarHistoricoSelecao(
            primeiraSeccao.selecao,
        );

        formulario.remove();

        pendente.rejeitar(
            new Error('Erro HTTP tardio.'),
        );

        await expect(operacao).resolves.toBeUndefined();

        expect(apresentarErro).not.toHaveBeenCalled();
        expect(apresentar).not.toHaveBeenCalled();
    });

    it('recarrega o artista original apos reset do formulario', async () => {
        const antiga = criarPedidoPendente();
        const restaurada = criarPedidoPendente();

        carregar
            .mockReturnValueOnce(antiga.promessa)
            .mockReturnValueOnce(restaurada.promessa);

        const opcaoOriginal =
            primeiraSeccao.selecao.querySelector(
                'option[value="1"]',
            );

        opcaoOriginal.defaultSelected = true;

        primeiraSeccao.selecao.value = '2';

        const operacaoAntiga =
            gestor.atualizarHistoricoSelecao(
                primeiraSeccao.selecao,
            );

        expect(carregar).toHaveBeenCalledTimes(1);
        expect(carregar.mock.calls[0][0]).toBe(2);

        formulario.reset();

        await Promise.resolve();

        expect(primeiraSeccao.selecao.value).toBe('1');
        expect(carregar).toHaveBeenCalledTimes(2);
        expect(carregar.mock.calls[1][0]).toBe(1);

        expect(
            carregar.mock.calls[0][1].aborted,
        ).toBe(true);

        expect(
            carregar.mock.calls[1][1].aborted,
        ).toBe(false);

        restaurada.resolver([
            {identificador: 1},
        ]);

        antiga.resolver([
            {identificador: 2},
        ]);

        await operacaoAntiga;

        await vi.waitFor(() => {
            expect(apresentar).toHaveBeenCalledTimes(1);
        });

        expect(apresentar).toHaveBeenCalledWith(
            primeiraSeccao.seccao,
            [{identificador: 1}],
        );
    });

    it('preserva o pedido quando o reset e cancelado', async () => {
        const pendente = criarPedidoPendente();

        carregar.mockReturnValue(
            pendente.promessa,
        );

        primeiraSeccao.selecao.value = '1';

        const operacao =
            gestor.atualizarHistoricoSelecao(
                primeiraSeccao.selecao,
            );

        formulario.addEventListener(
            'reset',
            (evento) => {
                evento.preventDefault();
            },
        );

        formulario.reset();

        await Promise.resolve();

        expect(primeiraSeccao.selecao.value).toBe('1');
        expect(carregar).toHaveBeenCalledTimes(1);

        expect(
            carregar.mock.calls[0][1].aborted,
        ).toBe(false);

        pendente.resolver([
            {identificador: 1},
        ]);

        await operacao;

        expect(apresentar).toHaveBeenCalledWith(
            primeiraSeccao.seccao,
            [{identificador: 1}],
        );
    });
});
