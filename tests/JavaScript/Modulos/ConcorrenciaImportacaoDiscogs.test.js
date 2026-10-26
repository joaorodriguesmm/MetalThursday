import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorImportacaoLancamentoDiscogs
    from '../../../resources/js/modulos/GestorImportacaoLancamentoDiscogs.js';

function criarPedidoPendente() {
    let resolver;
    let rejeitar;

    const promessa = new Promise((resolve, reject) => {
        resolver = resolve;
        rejeitar = reject;
    });

    return {promessa, resolver, rejeitar};
}

function criarLancamento() {
    return {
        id: 77,
        discogs_release_id: 12345,
        titulo: 'Master of Puppets',
        tipo: 'Album',
        ano_original: 1986,
        faixas: [],
    };
}

function criarGestor() {
    const gestor = Object.create(
        GestorImportacaoLancamentoDiscogs.prototype,
    );

    gestor.formulario = document.getElementById(
        'formulario-metal-thursday',
    );

    gestor.urlImportacao =
        '/discogs/lancamentos/__IDENTIFICADOR_DISCOGS__';

    gestor.identificadorMetalThursday = 42;

    gestor.editorLancamentos = {
        dadosImportacaoValidos: vi.fn().mockReturnValue(true),
        carregarLancamento: vi.fn(),
        atualizarCamposGenericos: vi.fn(),
        limpar: vi.fn(),
    };

    return gestor;
}

describe('B25.12 — concorrencia na importacao Discogs', () => {
    let gestor;
    let seccao;
    let area;
    let campoLigacao;
    let botaoImportar;
    let estado;

    beforeEach(() => {
        document.body.innerHTML = `
            <form
                id="formulario-metal-thursday"
                data-metal-thursday-id="42"
            >
                <article class="item-seccao">
                    <select class="seletor-tipo-seccao">
                        <option
                            value="lancamento"
                            data-identificador-tipo-seccao="lancamento"
                            selected
                        >
                            Lançamento
                        </option>

                        <option
                            value="texto"
                            data-identificador-tipo-seccao="texto"
                        >
                            Texto
                        </option>
                    </select>

                    <input
                        type="hidden"
                        name="seccoes[0][lancamento_id]"
                        value=""
                    >

                    <input
                        type="text"
                        data-campo-titulo-seccao
                        value=""
                    >

                    <div data-importacao-lancamento>
                        <input
                            type="url"
                            data-ligacao-lancamento-discogs
                        >

                        <button
                            type="button"
                            data-acao-importar-lancamento
                        >
                            Importar
                        </button>

                        <p
                            data-estado-importacao-lancamento
                            class="text-muted"
                        ></p>
                    </div>

                    <p data-lancamento-associado hidden></p>
                </article>
            </form>
        `;

        gestor = criarGestor();

        seccao = document.querySelector('.item-seccao');

        area = seccao.querySelector(
            '[data-importacao-lancamento]',
        );

        campoLigacao = seccao.querySelector(
            '[data-ligacao-lancamento-discogs]',
        );

        botaoImportar = seccao.querySelector(
            '[data-acao-importar-lancamento]',
        );

        estado = seccao.querySelector(
            '[data-estado-importacao-lancamento]',
        );

        campoLigacao.value =
            'https://www.discogs.com/release/12345-Master-of-Puppets';
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('impede duas importacoes simultaneas na mesma seccao', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const primeira = gestor.importarPorLigacao(seccao);

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(campoLigacao.disabled).toBe(true);
        expect(botaoImportar.disabled).toBe(true);

        const segunda = gestor.importarPorLigacao(seccao);

        expect(pedido).toHaveBeenCalledTimes(1);

        pendente.resolver({
            data: {
                lancamento: criarLancamento(),
            },
        });

        await Promise.all([primeira, segunda]);

        expect(
            gestor.editorLancamentos.carregarLancamento,
        ).toHaveBeenCalledTimes(1);

        expect(area.getAttribute('aria-busy')).toBe('false');
        expect(botaoImportar.disabled).toBe(false);
    });

    it('ignora sucesso tardio de uma seccao removida', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const aplicar = vi.spyOn(
            gestor,
            'aplicarLancamentoImportado',
        );

        const importacao = gestor.importarPorLigacao(seccao);

        expect(area.getAttribute('aria-busy')).toBe('true');

        seccao.remove();

        pendente.resolver({
            data: {
                lancamento: criarLancamento(),
            },
        });

        await importacao;

        expect(aplicar).not.toHaveBeenCalled();

        expect(
            gestor.editorLancamentos.carregarLancamento,
        ).not.toHaveBeenCalled();

        expect(
            estado.textContent,
        ).toBe('A importar a edição indicada do Discogs…');

        expect(
            seccao.querySelector(
                '[name$="[lancamento_id]"]',
            ).value,
        ).toBe('');
    });

    it('ignora erro tardio de uma seccao removida', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const importacao = gestor.importarPorLigacao(seccao);

        expect(area.getAttribute('aria-busy')).toBe('true');

        seccao.remove();

        pendente.rejeitar(
            new Error('Falha HTTP tardia simulada.'),
        );

        await expect(importacao).resolves.toBeUndefined();

        expect(
            estado.textContent,
        ).toBe('A importar a edição indicada do Discogs…');
    });

    it('mantem controlos desativados se mudar o tipo da seccao', async () => {
        const pendente = criarPedidoPendente();

        vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const importacao = gestor.importarPorLigacao(seccao);

        const selecaoTipo = seccao.querySelector(
            '.seletor-tipo-seccao',
        );

        selecaoTipo.value = 'texto';

        gestor.atualizarEstadoSeccao(
            seccao,
            true,
        );

        expect(area.hidden).toBe(true);
        expect(campoLigacao.disabled).toBe(true);
        expect(botaoImportar.disabled).toBe(true);

        pendente.resolver({
            data: {
                lancamento: criarLancamento(),
            },
        });

        await importacao;

        expect(
            gestor.editorLancamentos.carregarLancamento,
        ).not.toHaveBeenCalled();

        expect(area.hidden).toBe(true);
        expect(campoLigacao.disabled).toBe(true);
        expect(botaoImportar.disabled).toBe(true);

        expect(area.getAttribute('aria-busy')).toBe('false');
    });

    it('permite importacoes simultaneas em seccoes diferentes', async () => {
        const primeiraPendente = criarPedidoPendente();
        const segundaPendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValueOnce(primeiraPendente.promessa)
            .mockReturnValueOnce(segundaPendente.promessa);

        const outraSeccao = seccao.cloneNode(true);

        const outraLigacao = outraSeccao.querySelector(
            '[data-ligacao-lancamento-discogs]',
        );

        outraLigacao.value =
            'https://www.discogs.com/release/67890';

        seccao.after(outraSeccao);

        const primeiraImportacao = gestor.importarPorLigacao(
            seccao,
        );

        const segundaImportacao = gestor.importarPorLigacao(
            outraSeccao,
        );

        expect(pedido).toHaveBeenCalledTimes(2);

        expect(pedido).toHaveBeenNthCalledWith(
            1,
            '/discogs/lancamentos/12345',
            {metal_thursday_id: 42},
        );

        expect(pedido).toHaveBeenNthCalledWith(
            2,
            '/discogs/lancamentos/67890',
            {metal_thursday_id: 42},
        );

        primeiraPendente.resolver({
            data: {
                lancamento: criarLancamento(),
            },
        });

        segundaPendente.resolver({
            data: {
                lancamento: {
                    ...criarLancamento(),
                    id: 88,
                    discogs_release_id: 67890,
                    titulo: 'Ride the Lightning',
                },
            },
        });

        await Promise.all([
            primeiraImportacao,
            segundaImportacao,
        ]);

        expect(
            seccao.querySelector(
                '[name$="[lancamento_id]"]',
            ).value,
        ).toBe('77');

        expect(
            outraSeccao.querySelector(
                '[name$="[lancamento_id]"]',
            ).value,
        ).toBe('88');

        expect(
            gestor.editorLancamentos.carregarLancamento,
        ).toHaveBeenCalledTimes(2);

        expect(
            gestor.seccoesEmImportacao.has(seccao),
        ).toBe(false);

        expect(
            gestor.seccoesEmImportacao.has(outraSeccao),
        ).toBe(false);
    });

    it('liberta bloqueio e permite repetir apos erro HTTP', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValueOnce(pendente.promessa)
            .mockResolvedValueOnce({
                data: {
                    lancamento: criarLancamento(),
                },
            });

        const primeira = gestor.importarPorLigacao(seccao);

        expect(
            gestor.seccoesEmImportacao.has(seccao),
        ).toBe(true);

        pendente.rejeitar(
            new Error('Falha temporaria no servidor.'),
        );

        await primeira;

        expect(
            gestor.seccoesEmImportacao.has(seccao),
        ).toBe(false);

        expect(area.getAttribute('aria-busy')).toBe('false');
        expect(campoLigacao.disabled).toBe(false);
        expect(botaoImportar.disabled).toBe(false);

        await gestor.importarPorLigacao(seccao);

        expect(pedido).toHaveBeenCalledTimes(2);

        expect(
            gestor.editorLancamentos.carregarLancamento,
        ).toHaveBeenCalledTimes(1);

        expect(
            seccao.querySelector(
                '[name$="[lancamento_id]"]',
            ).value,
        ).toBe('77');

        expect(
            gestor.seccoesEmImportacao.has(seccao),
        ).toBe(false);
    });

    it('nao substitui ligacao alterada durante o pedido', async () => {
        const pendente = criarPedidoPendente();

        const pedido = vi.spyOn(axios, 'post')
            .mockReturnValue(pendente.promessa);

        const importacao = gestor.importarPorLigacao(seccao);

        expect(pedido).toHaveBeenCalledTimes(1);
        expect(campoLigacao.disabled).toBe(true);

        const novaLigacao =
            'https://www.discogs.com/release/67890';

        // Simula uma alteracao programatica ao formulario.
        campoLigacao.value = novaLigacao;

        pendente.resolver({
            data: {
                lancamento: criarLancamento(),
            },
        });

        await importacao;

        expect(
            gestor.editorLancamentos.carregarLancamento,
        ).not.toHaveBeenCalled();

        expect(
            seccao.querySelector(
                '[name$="[lancamento_id]"]',
            ).value,
        ).toBe('');

        expect(campoLigacao.value).toBe(novaLigacao);

        expect(area.getAttribute('aria-busy')).toBe('false');

        expect(
            gestor.seccoesEmImportacao.has(seccao),
        ).toBe(false);
    });

    it(
        'B25.57 — falha de comunicacao comunica resultado incerto',
        async () => {
            const erroComunicacao = new axios.AxiosError(
                'Network Error',
                'ERR_NETWORK',
                {},
                {readyState: 4},
            );

            const pedido = vi.spyOn(
                axios,
                'post',
            ).mockRejectedValueOnce(
                erroComunicacao,
            );

            await gestor.importarPorLigacao(seccao);

            expect(pedido).toHaveBeenCalledTimes(1);

            expect(estado.textContent).toBe(
                'Não foi possível confirmar se a operação foi concluída. '
                + 'Verifica o resultado antes de tentares novamente.',
            );

            expect(estado.classList.contains('text-danger'))
                .toBe(true);

            // Sem resposta valida, o formulario nao deve
            // assumir que a associacao foi concluida.
            expect(
                seccao.querySelector(
                    '[name$="[lancamento_id]"]',
                ).value,
            ).toBe('');

            expect(
                gestor.editorLancamentos.carregarLancamento,
            ).not.toHaveBeenCalled();

            expect(gestor.seccoesEmImportacao.has(seccao))
                .toBe(false);

            expect(area.getAttribute('aria-busy'))
                .toBe('false');

            expect(campoLigacao.disabled).toBe(false);
            expect(botaoImportar.disabled).toBe(false);
        },
    );

    it(
        'B25.57 — preserva mensagem devolvida pelo servidor',
        async () => {
            const erroHttp = new axios.AxiosError(
                'Erro HTTP 503.',
                'ERR_BAD_RESPONSE',
                {},
                {},
                {
                    data: {
                        mensagem:
                            'O serviço Discogs está temporariamente indisponível.',
                    },
                    status: 503,
                    statusText: 'Service Unavailable',
                    headers: {},
                    config: {},
                },
            );

            vi.spyOn(
                axios,
                'post',
            ).mockRejectedValueOnce(erroHttp);

            await gestor.importarPorLigacao(seccao);

            expect(estado.textContent).toBe(
                'O serviço Discogs está temporariamente indisponível.',
            );

            expect(gestor.seccoesEmImportacao.has(seccao))
                .toBe(false);
        },
    );

    it(
        'B25.57 — preserva erro de validacao HTTP 422',
        async () => {
            const erroHttp = new axios.AxiosError(
                'Erro HTTP 422.',
                'ERR_BAD_RESPONSE',
                {},
                {},
                {
                    data: {
                        errors: {
                            identificador: [
                                'A edição indicada não é válida.',
                            ],
                        },
                    },
                    status: 422,
                    statusText: 'Unprocessable Content',
                    headers: {},
                    config: {},
                },
            );

            vi.spyOn(
                axios,
                'post',
            ).mockRejectedValueOnce(erroHttp);

            await gestor.importarPorLigacao(seccao);

            expect(estado.textContent).toBe(
                'A edição indicada não é válida.',
            );
        },
    );

    it(
        'B25.57 — nao confunde erro local com falha de rede',
        () => {
            const mensagem = gestor.obterMensagemErro(
                new TypeError(
                    'A resposta de importação do lançamento é inválida.',
                ),
                'Não foi possível importar o lançamento do Discogs.',
            );

            expect(mensagem).toBe(
                'A resposta de importação do lançamento é inválida.',
            );
        },
    );

});
