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

            <select name="generos[]" multiple>
                <option value="51" selected>Género manual</option>
            </select>

            <div data-sugestoes-generos-importacao hidden></div>

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


    describe('A09.12.91 — sugestões de géneros', () => {
        async function importarSugestoes(mbid, sugestoes) {
            pedidos.get.mockResolvedValueOnce({
                data: {
                    artista: {
                        ...criarPropostaArtista(mbid, 'Moonspell'),
                        sugestoes_generos: sugestoes,
                    },
                },
            });

            criarBotaoSelecao(contentor, mbid).click();

            const painel = formulario.querySelector(
                '[data-sugestoes-generos-importacao]',
            );

            await vi.waitFor(() => {
                expect(painel.hidden).toBe(false);
            });

            return painel;
        }

        it('apresenta fontes e varios pais sem selecionar generos', async () => {
            const seletor = formulario.querySelector(
                '[name="generos[]"]',
            );

            const painel = await importarSugestoes(
                'mbid-generos',
                [
                    {
                        nome: 'gothic metal',
                        tipo: 'genero',
                        fontes: ['musicbrainz'],
                        disponivel: true,
                        genero_local: {
                            id: 12,
                            nome: 'Gothic Metal',
                        },
                        generos_pais: [
                            { id: 1, nome: 'Metal' },
                            { id: 2, nome: 'Rock' },
                        ],
                    },
                    {
                        nome: 'Rock/Pop',
                        tipo: 'estilo',
                        fontes: ['theaudiodb'],
                        disponivel: false,
                        genero_local: null,
                        generos_pais: [],
                    },
                ],
            );

            expect(painel.textContent).toContain('gothic metal');
            expect(painel.querySelector('li strong')
                .classList.contains('text-capitalize')).toBe(true);
            expect(painel.textContent).toContain('MusicBrainz');
            expect(painel.textContent).toContain('Metal, Rock');
            expect(painel.textContent).toContain('Rock/Pop');
            expect(painel.textContent).toContain('Estilo');
            expect(painel.textContent).toContain('TheAudioDB');
            expect(painel.textContent).toContain(
                'Sem correspondência nos géneros registados.',
            );

            expect(
                Array.from(seletor.selectedOptions, (opcao) => opcao.value),
            ).toEqual(['51']);
        });

        it('nao interpreta HTML recebido das fontes externas', async () => {
            const textoPerigoso =
                '<img src=x onerror=alert(1)>';

            const painel = await importarSugestoes(
                'mbid-html',
                [
                    {
                        nome: textoPerigoso,
                        tipo: 'genero',
                        fontes: ['musicbrainz'],
                        disponivel: true,
                        genero_local: {
                            id: 8,
                            nome: '<strong>Metal</strong>',
                        },
                        generos_pais: [
                            {
                                id: 1,
                                nome: '<svg onload=alert(1)>',
                            },
                        ],
                    },
                ],
            );

            expect(painel.textContent).toContain(textoPerigoso);
            expect(painel.textContent).toContain(
                '<strong>Metal</strong>',
            );

            expect(painel.querySelector('img')).toBeNull();
            expect(painel.querySelectorAll('strong')).toHaveLength(1);
            expect(painel.querySelector('strong').textContent)
                .toBe(textoPerigoso);
            expect(painel.querySelector('svg')).toBeNull();
        });

        it('substitui as sugestoes quando importa outro artista', async () => {
            const painel = await importarSugestoes(
                'mbid-primeiro',
                [
                    {
                        nome: 'Black Metal',
                        tipo: 'genero',
                        fontes: ['musicbrainz'],
                    },
                ],
            );

            expect(painel.textContent).toContain('Black Metal');

            await importarSugestoes(
                'mbid-segundo',
                [
                    {
                        nome: 'Heavy Metal',
                        tipo: 'genero',
                        fontes: ['theaudiodb'],
                    },
                ],
            );

            expect(painel.textContent).toContain('Heavy Metal');
            expect(painel.textContent).not.toContain('Black Metal');
        });

        it('limpa sugestoes ao remover associacao e ao reinicializar', async () => {
            const sugestoes = [
                {
                    nome: 'Gothic Metal',
                    tipo: 'genero',
                    fontes: ['musicbrainz'],
                },
            ];

            const painel = await importarSugestoes(
                'mbid-remocao',
                sugestoes,
            );

            contentor.querySelector(
                '[data-acao-remover-importacao]',
            ).click();

            expect(painel.hidden).toBe(true);
            expect(painel.childElementCount).toBe(0);

            await importarSugestoes(
                'mbid-reset',
                sugestoes,
            );

            formulario.reset();

            await vi.waitFor(() => {
                expect(painel.hidden).toBe(true);
                expect(painel.childElementCount).toBe(0);
            });
        });
    });
});
