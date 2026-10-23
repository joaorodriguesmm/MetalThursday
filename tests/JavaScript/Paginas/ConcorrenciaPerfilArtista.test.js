import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

const pedidos = vi.hoisted(() => ({
    get: vi.fn(),
    isAxiosError: vi.fn(() => false),
}));

vi.mock(
    '../../../resources/js/modulos/ClienteHttp.js',
    () => ({
        default: pedidos,
    }),
);

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

function resultadoArtista(mbid, nome) {
    return {
        mbid,
        nome,
    };
}

async function prepararPagina() {
    document.body.innerHTML = `
        <form data-formulario-perfil-artista>
            <input
                type="text"
                name="nome"
                value="Metallica"
            >

            <input
                type="hidden"
                name="musicbrainz_id"
                data-musicbrainz-id
                value=""
            >

            <input
                type="hidden"
                name="discogs_id"
                data-discogs-id
                value=""
            >

            <div
                data-importacao-artista
                data-endereco-pesquisa-importacao="/artistas/importacao/pesquisar"
                data-endereco-obter-importacao="/artistas/importacao/__MBID__"
                data-mbid-modelo="__MBID__"
            >
                <button
                    type="button"
                    data-acao-pesquisar-importacao
                >
                    Pesquisar
                </button>

                <button
                    type="button"
                    data-acao-remover-importacao
                    hidden
                >
                    Remover associação
                </button>

                <p data-estado-importacao></p>

                <div data-resultados-importacao></div>

                <div data-associacao-importacao></div>
            </div>
        </form>
    `;

    vi.resetModules();

    await import(
        '../../../resources/js/paginas/perfilArtista.js'
    );

    document.dispatchEvent(
        new Event('DOMContentLoaded'),
    );
}


function criarPropostaArtista(mbid, nome) {
    return {
        musicbrainz_id: mbid,
        nome,
        discogs_id: null,
        origem_geografica_id: null,
        ligacoes: [],
    };
}

function criarBotaoSelecao(contentor, mbid) {
    const botao = document.createElement('button');

    botao.type = 'button';
    botao.dataset.selecionarImportacao = '';
    botao.dataset.musicbrainzId = mbid;

    contentor.querySelector(
        '[data-resultados-importacao]',
    ).append(botao);

    return botao;
}

describe('B25.13 — concorrencia na pesquisa de artistas', () => {
    let formulario;
    let contentor;
    let campoNome;
    let botaoPesquisar;
    let resultados;
    let estado;

    beforeEach(async () => {
        pedidos.get.mockReset();
        pedidos.isAxiosError.mockReturnValue(false);

        await prepararPagina();

        formulario = document.querySelector(
            '[data-formulario-perfil-artista]',
        );

        contentor = document.querySelector(
            '[data-importacao-artista]',
        );

        campoNome = formulario.querySelector(
            '[name="nome"]',
        );

        botaoPesquisar = contentor.querySelector(
            '[data-acao-pesquisar-importacao]',
        );

        resultados = contentor.querySelector(
            '[data-resultados-importacao]',
        );

        estado = contentor.querySelector(
            '[data-estado-importacao]',
        );
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('mantem os resultados da pesquisa mais recente', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        pedidos.get
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        campoNome.value = 'Metallica';
        botaoPesquisar.click();

        campoNome.value = 'Slayer';
        botaoPesquisar.click();

        expect(pedidos.get).toHaveBeenCalledTimes(2);

        segunda.resolver({
            data: {
                resultados: [
                    resultadoArtista('mbid-slayer', 'Slayer'),
                ],
            },
        });

        await vi.waitFor(() => {
            expect(resultados.textContent).toContain('Slayer');
        });

        primeira.resolver({
            data: {
                resultados: [
                    resultadoArtista('mbid-metallica', 'Metallica'),
                ],
            },
        });

        await primeira.promessa;
        await Promise.resolve();

        expect(resultados.textContent).toContain('Slayer');
        expect(resultados.textContent).not.toContain('Metallica');
    });

    it('ignora um erro antigo depois de uma pesquisa com sucesso', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        pedidos.get
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        campoNome.value = 'Metallica';
        botaoPesquisar.click();

        campoNome.value = 'Slayer';
        botaoPesquisar.click();

        segunda.resolver({
            data: {
                resultados: [
                    resultadoArtista('mbid-slayer', 'Slayer'),
                ],
            },
        });

        await vi.waitFor(() => {
            expect(resultados.textContent).toContain('Slayer');
        });

        primeira.rejeitar(
            new Error('Erro HTTP de uma pesquisa antiga.'),
        );

        await primeira.promessa.catch(() => {});
        await Promise.resolve();

        expect(resultados.textContent).toContain('Slayer');

        expect(estado.textContent).toContain(
            'Seleciona o artista correto.',
        );

        expect(
            estado.classList.contains('text-danger'),
        ).toBe(false);
    });

    it('ignora resultados recebidos apos remocao do formulario', async () => {
        const pendente = criarPedidoPendente();

        pedidos.get.mockReturnValue(pendente.promessa);

        botaoPesquisar.click();

        expect(pedidos.get).toHaveBeenCalledTimes(1);

        expect(estado.textContent).toBe(
            'A pesquisar no MusicBrainz…',
        );

        formulario.remove();

        pendente.resolver({
            data: {
                resultados: [
                    resultadoArtista('mbid-metallica', 'Metallica'),
                ],
            },
        });

        await pendente.promessa;
        await Promise.resolve();

        expect(resultados.children.length).toBe(0);

        expect(estado.textContent).toBe(
            'A pesquisar no MusicBrainz…',
        );
    });

    it('seleciona normalmente um artista MusicBrainz', async () => {
        pedidos.get.mockResolvedValue({
            data: {
                artista: criarPropostaArtista(
                    'mbid-metallica',
                    'Metallica Importado',
                ),
            },
        });

        criarBotaoSelecao(
            contentor,
            'mbid-metallica',
        ).click();

        await vi.waitFor(() => {
            expect(
                formulario.querySelector(
                    '[data-musicbrainz-id]',
                ).value,
            ).toBe('mbid-metallica');
        });

        expect(campoNome.value).toBe('Metallica Importado');
        expect(estado.textContent).toContain('Dados importados.');
        expect(pedidos.get).toHaveBeenCalledTimes(1);
    });

    it('mantem a ultima selecao quando respostas chegam fora de ordem', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        pedidos.get
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        criarBotaoSelecao(
            contentor,
            'mbid-metallica',
        ).click();

        criarBotaoSelecao(
            contentor,
            'mbid-slayer',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(2);

        segunda.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-slayer',
                    'Slayer',
                ),
            },
        });

        await vi.waitFor(() => {
            expect(
                formulario.querySelector(
                    '[data-musicbrainz-id]',
                ).value,
            ).toBe('mbid-slayer');
        });

        primeira.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-metallica',
                    'Metallica Importado',
                ),
            },
        });

        await primeira.promessa;
        await Promise.resolve();

        expect(
            formulario.querySelector(
                '[data-musicbrainz-id]',
            ).value,
        ).toBe('mbid-slayer');

        expect(campoNome.value).toBe('Slayer');
    });

    it('nao restaura associacao removida durante selecao pendente', async () => {
        const pendente = criarPedidoPendente();

        pedidos.get.mockReturnValue(pendente.promessa);

        const campoMusicBrainz = formulario.querySelector(
            '[data-musicbrainz-id]',
        );

        const botaoRemover = contentor.querySelector(
            '[data-acao-remover-importacao]',
        );

        campoMusicBrainz.value = 'mbid-anterior';
        botaoRemover.hidden = false;

        criarBotaoSelecao(
            contentor,
            'mbid-metallica',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(1);

        botaoRemover.click();

        expect(campoMusicBrainz.value).toBe('');

        pendente.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-metallica',
                    'Metallica Importado',
                ),
            },
        });

        await pendente.promessa;
        await Promise.resolve();

        expect(campoMusicBrainz.value).toBe('');
        expect(botaoRemover.hidden).toBe(true);

        expect(estado.textContent).toContain(
            'Associação externa removida.',
        );
    });

    it('nao aplica importacao pendente apos reset do formulario', async () => {
        const pendente = criarPedidoPendente();

        pedidos.get.mockReturnValue(pendente.promessa);

        criarBotaoSelecao(
            contentor,
            'mbid-slayer',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(1);

        formulario.reset();

        await vi.waitFor(() => {
            expect(estado.textContent).toBe('');
        });

        pendente.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-slayer',
                    'Slayer',
                ),
            },
        });

        await pendente.promessa;
        await Promise.resolve();

        expect(
            formulario.querySelector(
                '[data-musicbrainz-id]',
            ).value,
        ).toBe('');

        expect(campoNome.value).toBe('Metallica');
        expect(estado.textContent).toBe('');
    });

    it('ignora erro tardio de uma selecao anterior', async () => {
        const primeira = criarPedidoPendente();
        const segunda = criarPedidoPendente();

        pedidos.get
            .mockReturnValueOnce(primeira.promessa)
            .mockReturnValueOnce(segunda.promessa);

        criarBotaoSelecao(
            contentor,
            'mbid-metallica',
        ).click();

        criarBotaoSelecao(
            contentor,
            'mbid-slayer',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(2);

        segunda.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-slayer',
                    'Slayer',
                ),
            },
        });

        await vi.waitFor(() => {
            expect(
                formulario.querySelector(
                    '[data-musicbrainz-id]',
                ).value,
            ).toBe('mbid-slayer');
        });

        primeira.rejeitar(
            new Error('Erro HTTP da selecao anterior.'),
        );

        await primeira.promessa.catch(() => {});
        await Promise.resolve();

        expect(
            formulario.querySelector(
                '[data-musicbrainz-id]',
            ).value,
        ).toBe('mbid-slayer');

        expect(campoNome.value).toBe('Slayer');

        expect(estado.textContent).toContain(
            'Dados importados.',
        );

        expect(
            estado.classList.contains('text-danger'),
        ).toBe(false);
    });

    it('ignora selecao recebida apos remocao do formulario', async () => {
        const pendente = criarPedidoPendente();

        pedidos.get.mockReturnValue(
            pendente.promessa,
        );

        criarBotaoSelecao(
            contentor,
            'mbid-slayer',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(1);

        expect(estado.textContent).toBe(
            'A obter e combinar os dados disponíveis…',
        );

        formulario.remove();

        pendente.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-slayer',
                    'Slayer',
                ),
            },
        });

        await pendente.promessa;
        await Promise.resolve();

        expect(
            formulario.querySelector(
                '[data-musicbrainz-id]',
            ).value,
        ).toBe('');

        expect(campoNome.value).toBe('Metallica');

        expect(estado.textContent).toBe(
            'A obter e combinar os dados disponíveis…',
        );
    });

    it('preserva edicao manual realizada durante importacao pendente', async () => {
        const pendente = criarPedidoPendente();

        pedidos.get.mockReturnValue(
            pendente.promessa,
        );

        criarBotaoSelecao(
            contentor,
            'mbid-slayer',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(1);

        campoNome.value =
            'Nome revisto manualmente';

        campoNome.dispatchEvent(
            new Event('input', {
                bubbles: true,
            }),
        );

        pendente.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-slayer',
                    'Slayer',
                ),
            },
        });

        await pendente.promessa;
        await Promise.resolve();

        expect(campoNome.value).toBe(
            'Nome revisto manualmente',
        );

        expect(
            formulario.querySelector(
                '[data-musicbrainz-id]',
            ).value,
        ).toBe('');
    });

    it('invalida selecao pendente apos alterar um seletor manualmente', async () => {
        const pendente = criarPedidoPendente();

        pedidos.get.mockReturnValue(
            pendente.promessa,
        );

        const campoOrigem = document.createElement(
            'select',
        );

        campoOrigem.name = 'origem_geografica_id';

        campoOrigem.innerHTML = `
            <option value="1">Portugal</option>
            <option value="2">Espanha</option>
        `;

        formulario.append(campoOrigem);

        criarBotaoSelecao(
            contentor,
            'mbid-slayer',
        ).click();

        expect(pedidos.get).toHaveBeenCalledTimes(1);

        campoOrigem.value = '2';

        campoOrigem.dispatchEvent(
            new Event('change', {
                bubbles: true,
            }),
        );

        pendente.resolver({
            data: {
                artista: criarPropostaArtista(
                    'mbid-slayer',
                    'Slayer',
                ),
            },
        });

        await pendente.promessa;
        await Promise.resolve();

        expect(campoOrigem.value).toBe('2');
        expect(campoNome.value).toBe('Metallica');

        expect(
            formulario.querySelector(
                '[data-musicbrainz-id]',
            ).value,
        ).toBe('');

        expect(
            estado.textContent,
        ).toBe('A obter e combinar os dados disponíveis…');
    });

});
