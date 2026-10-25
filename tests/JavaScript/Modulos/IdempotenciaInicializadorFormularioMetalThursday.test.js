import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

const efeitos = vi.hoisted(() => ({
    gestorEdicao: vi.fn(),
    adicionarEdicao: vi.fn(),
    gestorImportacao: vi.fn(),
    gestorSeccoes: vi.fn(),
    validador: vi.fn(),
    gestorModais: vi.fn(),
}));

vi.mock(
    '../../../resources/js/modulos/GestorEdicaoMetalThursday',
    () => ({
        default: class GestorEdicaoSimulado {
            constructor() {
                efeitos.gestorEdicao();
            }

            adicionarEdicao(dados) {
                efeitos.adicionarEdicao(dados);
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/GestorImportacaoLancamentoDiscogs',
    () => ({
        default: class GestorImportacaoSimulado {
            constructor() {
                efeitos.gestorImportacao();
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/GestorSeccoes',
    () => ({
        default: class GestorSeccoesSimulado {
            constructor() {
                efeitos.gestorSeccoes();
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/ValidadorFormulario',
    () => ({
        default: class ValidadorSimulado {
            constructor() {
                efeitos.validador();
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/GestorFormulariosModais',
    () => ({
        default: class GestorModaisSimulado {
            constructor(configuracoes) {
                efeitos.gestorModais(configuracoes);
            }
        },
    }),
);

import inicializarFormularioMetalThursday
    from '../../../resources/js/modulos/InicializadorFormularioMetalThursday';

const IDENTIFICADOR_FORMULARIO =
    'formulario-criar-metal-thursday';

function criarConfiguracao() {
    const elemento = document.createElement('script');

    elemento.id =
        'configuracao-formulario-metal-thursday';

    elemento.type = 'application/json';

    elemento.textContent = JSON.stringify({
        enderecos: {
            guardarEdicao: '/edicoes',
            guardarArtista: '/artistas',
            guardarGenero: '/generos',
            pesquisarLancamentos: '/discogs/pesquisar',
            importarLancamento:
                '/discogs/__IDENTIFICADOR_DISCOGS__',
            obterUtilizadorHaMaisTempoSemNomeacao:
                '/utilizadores/mais-antigo',
        },
    });

    document.body.append(elemento);
}

function criarPagina() {
    document.body.innerHTML = `
        <form id="${IDENTIFICADOR_FORMULARIO}">
            <input
                id="data-metal-thursday"
                type="date"
                name="data"
            >

            <input
                id="edicao-metal-thursday"
                type="text"
            >

            <input
                id="nome-metal-thursday"
                type="text"
                name="nome"
            >

            <input
                type="hidden"
                name="autor_id"
                value="1"
            >

            <div id="contentor-seccoes"></div>
            <div id="erro-seccoes"></div>
        </form>

        <div id="estado-edicao-metal-thursday"></div>

        <div
            id="dados-edicoes-metal-thursday"
            data-data-referencia="2026-10-23"
        ></div>

        <div id="modal-criar-artista">
            <form id="formulario-criar-artista">
                <input type="text" name="nome">
            </form>
        </div>

        <div id="modal-criar-genero"></div>
    `;

    criarConfiguracao();

    return document.getElementById(
        IDENTIFICADOR_FORMULARIO,
    );
}

function inicializar() {
    inicializarFormularioMetalThursday(
        IDENTIFICADOR_FORMULARIO,
    );
}

function contarListeners(espia, tipoEvento) {
    return espia.mock.calls.filter(
        ([tipo]) => tipo === tipoEvento,
    ).length;
}

describe(
    'B25.26 — idempotencia do inicializador do formulario',
    () => {
        beforeEach(() => {
            vi.clearAllMocks();
            criarPagina();
        });

        afterEach(() => {
            vi.restoreAllMocks();
            document.body.replaceChildren();
        });

        it(
            'cria os gestores apenas uma vez para o mesmo formulario',
            () => {
                inicializar();
                inicializar();

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorImportacao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorSeccoes,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.validador,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorModais,
                ).toHaveBeenCalledTimes(1);
            },
        );

        it(
            'nao duplica listeners de validacao e dos modais',
            () => {
                const formulario =
                    document.getElementById(
                        IDENTIFICADOR_FORMULARIO,
                    );

                const modalArtista =
                    document.getElementById(
                        'modal-criar-artista',
                    );

                const modalGenero =
                    document.getElementById(
                        'modal-criar-genero',
                    );

                const campoNomeArtista =
                    document.querySelector(
                        '#formulario-criar-artista [name="nome"]',
                    );

                const listenersFormulario =
                    vi.spyOn(
                        formulario,
                        'addEventListener',
                    );

                const listenersModalArtista =
                    vi.spyOn(
                        modalArtista,
                        'addEventListener',
                    );

                const listenersModalGenero =
                    vi.spyOn(
                        modalGenero,
                        'addEventListener',
                    );

                const listenersNomeArtista =
                    vi.spyOn(
                        campoNomeArtista,
                        'addEventListener',
                    );

                inicializar();
                inicializar();

                for (
                    const evento
                    of ['submit', 'reset', 'input', 'change']
                ) {
                    expect(
                        contarListeners(
                            listenersFormulario,
                            evento,
                        ),
                    ).toBe(1);
                }

                expect(
                    contarListeners(
                        listenersModalArtista,
                        'show.bs.modal',
                    ),
                ).toBe(1);

                expect(
                    contarListeners(
                        listenersModalArtista,
                        'hidden.bs.modal',
                    ),
                ).toBe(1);

                expect(
                    contarListeners(
                        listenersModalGenero,
                        'show.bs.modal',
                    ),
                ).toBe(1);

                expect(
                    contarListeners(
                        listenersModalGenero,
                        'hidden.bs.modal',
                    ),
                ).toBe(1);

                expect(
                    contarListeners(
                        listenersNomeArtista,
                        'input',
                    ),
                ).toBe(1);
            },
        );

        it(
            'inicializa um novo formulario depois de substituir a DOM',
            () => {
                const primeiro = document.getElementById(
                    IDENTIFICADOR_FORMULARIO,
                );

                inicializar();

                criarPagina();

                const segundo = document.getElementById(
                    IDENTIFICADOR_FORMULARIO,
                );

                expect(segundo).not.toBe(primeiro);

                inicializar();

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(2);

                expect(
                    efeitos.validador,
                ).toHaveBeenCalledTimes(2);

                expect(
                    efeitos.gestorImportacao,
                ).toHaveBeenCalledTimes(2);
            },
        );

        it(
            'permite repetir a inicializacao apos configuracao invalida',
            () => {
                document.getElementById(
                    'configuracao-formulario-metal-thursday',
                ).remove();

                expect(
                    () => inicializar(),
                ).toThrow(
                    'Não foi encontrada a configuração do formulário de MetalThursday.',
                );

                expect(
                    efeitos.gestorEdicao,
                ).not.toHaveBeenCalled();

                criarConfiguracao();

                expect(
                    () => inicializar(),
                ).not.toThrow();

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.validador,
                ).toHaveBeenCalledTimes(1);
            },
        );

        it(
            'impede duplicacao apos uma falha na etapa final',
            () => {
                efeitos.gestorModais.mockImplementationOnce(
                    () => {
                        throw new Error(
                            'Falha simulada nos modais.',
                        );
                    },
                );

                expect(
                    () => inicializar(),
                ).toThrow(
                    'Falha simulada nos modais.',
                );

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorImportacao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.validador,
                ).toHaveBeenCalledTimes(1);

                /*
                 * Depois de uma falha parcial, uma segunda
                 * tentativa no mesmo formulario nao e segura
                 * sem libertar os componentes ja inicializados.
                 */
                expect(
                    () => inicializar(),
                ).toThrow(
                    /inicializa|falh|incompleta/iu,
                );

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorImportacao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.validador,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorModais,
                ).toHaveBeenCalledTimes(1);
            },
        );

        it(
            'permite novo formulario apos uma falha parcial',
            () => {
                efeitos.gestorModais.mockImplementationOnce(
                    () => {
                        throw new Error(
                            'Falha simulada nos modais.',
                        );
                    },
                );

                const formularioAnterior =
                    document.getElementById(
                        IDENTIFICADOR_FORMULARIO,
                    );

                expect(
                    () => inicializar(),
                ).toThrow(
                    'Falha simulada nos modais.',
                );

                criarPagina();

                const formularioNovo =
                    document.getElementById(
                        IDENTIFICADOR_FORMULARIO,
                    );

                expect(
                    formularioNovo,
                ).not.toBe(
                    formularioAnterior,
                );

                expect(
                    () => inicializar(),
                ).not.toThrow();

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(2);

                expect(
                    efeitos.gestorModais,
                ).toHaveBeenCalledTimes(2);
            },
        );

        it(
            'impede uma inicializacao reentrante',
            () => {
                efeitos.gestorEdicao.mockImplementationOnce(
                    () => {
                        inicializar();
                    },
                );

                inicializar();

                expect(
                    efeitos.gestorEdicao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorImportacao,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.validador,
                ).toHaveBeenCalledTimes(1);

                expect(
                    efeitos.gestorModais,
                ).toHaveBeenCalledTimes(1);
            },
        );

        it.each([
            [
                'edicao',
                'formulario-criar-edicao',
            ],
            [
                'artista',
                'formulario-criar-artista',
            ],
            [
                'genero',
                'formulario-criar-genero',
            ],
        ])(
            'assinala uma resposta incompleta na criacao de %s',
            (
                _tipo,
                identificadorFormulario,
            ) => {
                inicializar();

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) => entrada.idFormulario
                        === identificadorFormulario,
                );

                expect(configuracao).toBeDefined();

                expect(
                    () => configuracao.aoSucesso({
                        mensagem: 'Operacao guardada.',
                    }),
                ).toThrow();
            },
        );

        it(
            'continua a integrar uma edicao devolvida corretamente',
            () => {
                inicializar();

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) => entrada.idFormulario
                        === 'formulario-criar-edicao',
                );

                expect(configuracao).toBeDefined();

                const edicao = {
                    id: 3,
                    nome: 'Edição criada',
                    data_inicio: '2027-01-01',
                    data_fim: null,
                };

                expect(
                    () => configuracao.aoSucesso({
                        edicao,
                    }),
                ).not.toThrow();

                expect(
                    efeitos.adicionarEdicao,
                ).toHaveBeenCalledExactlyOnceWith(
                    edicao,
                );
            },
        );


        function obterCallbackCriacao(identificadorFormulario) {
            inicializar();

            const configuracoes =
                efeitos.gestorModais.mock.calls[0][0];

            const configuracao = configuracoes.find(
                (entrada) => entrada.idFormulario
                    === identificadorFormulario,
            );

            expect(configuracao).toBeDefined();

            return configuracao.aoSucesso;
        }

        it(
            'inicializa um seletor de artistas existente antes de adicionar',
            () => {
                const callback = obterCallbackCriacao(
                    'formulario-criar-artista',
                );

                const selecao = document.createElement(
                    'select',
                );

                selecao.className =
                    'tom-select-unico tom-select-artistas';

                document.body.append(selecao);

                const resposta = {
                    artista: {
                        id: 31,
                        rotulo_selecao: 'Artista criado',
                    },
                };

                expect(
                    () => callback(resposta),
                ).not.toThrow();

                expect(selecao.tomselect).toBeDefined();

                expect(
                    selecao.tomselect?.options?.['31']?.text,
                ).toBe('Artista criado');
            },
        );

        it.each([
            [
                'artista',
                'formulario-criar-artista',
                'tom-select-artistas',
                '',
                {
                    artista: {
                        id: 41,
                        rotulo_selecao: 'Artista criado',
                    },
                },
            ],
            [
                'genero',
                'formulario-criar-genero',
                'tom-select-multiplo',
                'generos-novo-artista',
                {
                    genero: {
                        id: 42,
                        nome: 'Género criado',
                    },
                },
            ],
        ])(
            'assinala falha ao atualizar um seletor existente de %s',
            (
                _tipo,
                identificadorFormulario,
                classe,
                identificadorSeletor,
                resposta,
            ) => {
                const callback = obterCallbackCriacao(
                    identificadorFormulario,
                );

                const selecao = document.createElement(
                    'select',
                );

                selecao.className = classe;

                if (identificadorSeletor !== '') {
                    selecao.id = identificadorSeletor;
                }

                selecao.tomselect = {
                    on: vi.fn(),
                    options: {},
                };

                document.body.append(selecao);

                expect(
                    () => callback(resposta),
                ).toThrow();

                expect(
                    selecao.tomselect.options,
                ).toEqual({});
            },
        );

        it(
            'permite a criacao de genero sem seletores opcionais',
            () => {
                const callback = obterCallbackCriacao(
                    'formulario-criar-genero',
                );

                expect(
                    document.getElementById(
                        'generos-novo-artista',
                    ),
                ).toBeNull();

                expect(
                    document.getElementById(
                        'generos-pai-novo-genero',
                    ),
                ).toBeNull();

                expect(
                    () => callback({
                        genero: {
                            id: 43,
                            nome: 'Género criado',
                        },
                    }),
                ).not.toThrow();
            },
        );


        function emitirAberturaGenero(acionador) {
            const modalGenero = document.getElementById(
                'modal-criar-genero',
            );

            const evento = new Event(
                'show.bs.modal',
                {
                    bubbles: true,
                    cancelable: true,
                },
            );

            Object.defineProperty(
                evento,
                'relatedTarget',
                {
                    value: acionador,
                },
            );

            modalGenero.dispatchEvent(evento);

            return evento;
        }

        it(
            'preserva o artista durante a abertura normal do genero',
            () => {
                inicializar();

                const modalArtista = document.getElementById(
                    'modal-criar-artista',
                );

                const acionador = document.createElement(
                    'button',
                );

                modalArtista.append(acionador);

                const evento = emitirAberturaGenero(
                    acionador,
                );

                expect(evento.defaultPrevented).toBe(false);

                expect(
                    modalArtista.hasAttribute(
                        'data-preservar-formularios-ao-fechar',
                    ),
                ).toBe(true);
            },
        );

        it(
            'anula a preservacao e a selecao quando a abertura e cancelada',
            async () => {
                inicializar();

                const modalArtista = document.getElementById(
                    'modal-criar-artista',
                );

                const modalGenero = document.getElementById(
                    'modal-criar-genero',
                );

                const acionador = document.createElement(
                    'button',
                );

                modalArtista.append(acionador);

                modalGenero.addEventListener(
                    'show.bs.modal',
                    (evento) => {
                        evento.preventDefault();
                    },
                    {
                        once: true,
                    },
                );

                const evento = emitirAberturaGenero(
                    acionador,
                );

                expect(evento.defaultPrevented).toBe(true);

                await Promise.resolve();

                expect(
                    modalArtista.hasAttribute(
                        'data-preservar-formularios-ao-fechar',
                    ),
                ).toBe(false);

                const selecao = document.createElement(
                    'select',
                );

                selecao.id = 'generos-novo-artista';

                const selecionar = vi.fn();
                const adicionar = vi.fn();

                selecao.tomselect = {
                    on: vi.fn(),
                    addOption: adicionar,
                    addItem: selecionar,
                    options: {},
                };

                document.body.append(selecao);

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) => entrada.idFormulario
                        === 'formulario-criar-genero',
                );

                expect(configuracao).toBeDefined();

                expect(
                    () => configuracao.aoSucesso({
                        genero: {
                            id: 55,
                            nome: 'Género independente',
                        },
                    }),
                ).not.toThrow();

                expect(
                    adicionar,
                ).toHaveBeenCalledExactlyOnceWith({
                    value: '55',
                    text: 'Género independente',
                });

                expect(
                    selecionar,
                ).not.toHaveBeenCalled();
            },
        );


        it(
            'nao preserva o artista apos abertura cancelada seguida de abertura independente',
            async () => {
                inicializar();

                const modalArtista = document.getElementById(
                    'modal-criar-artista',
                );

                const modalGenero = document.getElementById(
                    'modal-criar-genero',
                );

                const acionadorArtista =
                    document.createElement('button');

                const acionadorExterno =
                    document.createElement('button');

                modalArtista.append(acionadorArtista);
                document.body.append(acionadorExterno);

                modalGenero.addEventListener(
                    'show.bs.modal',
                    (evento) => {
                        if (
                            evento.relatedTarget
                            === acionadorArtista
                        ) {
                            evento.preventDefault();
                        }
                    },
                );

                const primeiraAbertura =
                    emitirAberturaGenero(
                        acionadorArtista,
                    );

                expect(
                    primeiraAbertura.defaultPrevented,
                ).toBe(true);

                const segundaAbertura =
                    emitirAberturaGenero(
                        acionadorExterno,
                    );

                expect(
                    segundaAbertura.defaultPrevented,
                ).toBe(false);

                await Promise.resolve();

                expect(
                    modalArtista.hasAttribute(
                        'data-preservar-formularios-ao-fechar',
                    ),
                ).toBe(false);
            },
        );


        it(
            'nao seleciona artista antigo numa seccao reaberta',
            () => {
                const callback = obterCallbackCriacao(
                    'formulario-criar-artista',
                );

                const contentor = document.getElementById(
                    'contentor-seccoes',
                );

                contentor.innerHTML = `
                    <div class="item-seccao">
                        <select class="tom-select-artistas"></select>
                        <button id="abrir-artista-a"></button>
                    </div>
                    <div class="item-seccao">
                        <select class="tom-select-artistas"></select>
                        <button id="abrir-artista-b"></button>
                    </div>
                `;

                const selecoes = Array.from(
                    contentor.querySelectorAll(
                        '.tom-select-artistas',
                    ),
                );

                const selecionarA = vi.fn();
                const selecionarB = vi.fn();
                const adicionarB = vi.fn();

                selecoes[0].tomselect = {
                    on: vi.fn(),
                    addOption: vi.fn(),
                    addItem: selecionarA,
                    options: {},
                };

                selecoes[1].tomselect = {
                    on: vi.fn(),
                    addOption: adicionarB,
                    addItem: selecionarB,
                    options: {},
                };

                const modal = document.getElementById(
                    'modal-criar-artista',
                );

                function abrir(identificador) {
                    const evento = new Event(
                        'show.bs.modal',
                    );

                    Object.defineProperty(
                        evento,
                        'relatedTarget',
                        {
                            value: document.getElementById(
                                identificador,
                            ),
                        },
                    );

                    modal.dispatchEvent(evento);
                }

                abrir('abrir-artista-a');

                const contextoSubmissao =
                    efeitos.gestorModais.mock.calls[0][0]
                        .find((entrada) => entrada.idFormulario
                            === 'formulario-criar-artista')
                        .obterContextoSubmissao();

                document.getElementById(
                    'formulario-criar-artista',
                ).dispatchEvent(
                    new Event('submit', {
                        cancelable: true,
                    }),
                );

                modal.dispatchEvent(
                    new Event('hidden.bs.modal'),
                );

                abrir('abrir-artista-b');

                callback({
                    artista: {
                        id: 61,
                        rotulo_selecao: 'Artista da primeira submissao',
                    },
                }, contextoSubmissao);

                expect(
                    adicionarB,
                ).toHaveBeenCalledExactlyOnceWith({
                    value: '61',
                    text: 'Artista da primeira submissao',
                });

                expect(
                    selecionarB,
                ).not.toHaveBeenCalled();
            },
        );

        it(
            'nao seleciona genero antigo numa nova criacao de artista',
            () => {
                const callback = obterCallbackCriacao(
                    'formulario-criar-genero',
                );

                const modalArtista = document.getElementById(
                    'modal-criar-artista',
                );

                const modalGenero = document.getElementById(
                    'modal-criar-genero',
                );

                const formularioGenero = document.createElement(
                    'form',
                );

                formularioGenero.id = 'formulario-criar-genero';
                modalGenero.append(formularioGenero);

                const selecao = document.createElement(
                    'select',
                );

                selecao.id = 'generos-novo-artista';

                const adicionar = vi.fn();
                const selecionar = vi.fn();

                selecao.tomselect = {
                    on: vi.fn(),
                    addOption: adicionar,
                    addItem: selecionar,
                    options: {},
                };

                modalArtista.append(selecao);

                const acionadorExterno = document.createElement(
                    'button',
                );

                const acionadorArtista = document.createElement(
                    'button',
                );

                document.body.append(acionadorExterno);
                modalArtista.append(acionadorArtista);

                emitirAberturaGenero(acionadorExterno);

                const contextoSubmissao =
                    efeitos.gestorModais.mock.calls[0][0]
                        .find((entrada) => entrada.idFormulario
                            === 'formulario-criar-genero')
                        .obterContextoSubmissao();

                formularioGenero.dispatchEvent(
                    new Event('submit', {
                        cancelable: true,
                    }),
                );

                modalGenero.dispatchEvent(
                    new Event('hidden.bs.modal'),
                );

                emitirAberturaGenero(acionadorArtista);

                callback({
                    genero: {
                        id: 62,
                        nome: 'Género da primeira submissão',
                    },
                }, contextoSubmissao);

                expect(
                    adicionar,
                ).toHaveBeenCalledExactlyOnceWith({
                    value: '62',
                    text: 'Género da primeira submissão',
                });

                expect(
                    selecionar,
                ).not.toHaveBeenCalled();
            },
        );


        it(
            'seleciona artista criado quando o destino original continua valido',
            () => {
                const callback = obterCallbackCriacao(
                    'formulario-criar-artista',
                );

                const contentor = document.getElementById(
                    'contentor-seccoes',
                );

                contentor.innerHTML =
                    '<div class="item-seccao">'
                    + '<select class="tom-select-artistas"></select>'
                    + '<button id="abrir-artista-valido"></button>'
                    + '</div>';

                const selecao = contentor.querySelector(
                    '.tom-select-artistas',
                );

                const adicionar = vi.fn();
                const selecionar = vi.fn();

                selecao.tomselect = {
                    on: vi.fn(),
                    addOption: adicionar,
                    addItem: selecionar,
                    options: {},
                };

                const evento = new Event('show.bs.modal');

                Object.defineProperty(
                    evento,
                    'relatedTarget',
                    {
                        value: document.getElementById(
                            'abrir-artista-valido',
                        ),
                    },
                );

                document.getElementById(
                    'modal-criar-artista',
                ).dispatchEvent(evento);

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) => entrada.idFormulario
                        === 'formulario-criar-artista',
                );

                const contexto =
                    configuracao.obterContextoSubmissao();

                callback({
                    artista: {
                        id: 63,
                        rotulo_selecao: 'Artista válido',
                    },
                }, contexto);

                expect(
                    adicionar,
                ).toHaveBeenCalledExactlyOnceWith({
                    value: '63',
                    text: 'Artista válido',
                });

                expect(
                    selecionar,
                ).toHaveBeenCalledExactlyOnceWith('63');
            },
        );

        it(
            'seleciona genero criado quando continua no mesmo contexto',
            () => {
                const callback = obterCallbackCriacao(
                    'formulario-criar-genero',
                );

                const modalArtista = document.getElementById(
                    'modal-criar-artista',
                );

                const acionador = document.createElement(
                    'button',
                );

                const selecao = document.createElement(
                    'select',
                );

                selecao.id = 'generos-novo-artista';

                const adicionar = vi.fn();
                const selecionar = vi.fn();

                selecao.tomselect = {
                    on: vi.fn(),
                    addOption: adicionar,
                    addItem: selecionar,
                    options: {},
                };

                modalArtista.append(
                    acionador,
                    selecao,
                );

                emitirAberturaGenero(acionador);

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) => entrada.idFormulario
                        === 'formulario-criar-genero',
                );

                const contexto =
                    configuracao.obterContextoSubmissao();

                callback({
                    genero: {
                        id: 64,
                        nome: 'Género válido',
                    },
                }, contexto);

                expect(
                    adicionar,
                ).toHaveBeenCalledExactlyOnceWith({
                    value: '64',
                    text: 'Género válido',
                });

                expect(
                    selecionar,
                ).toHaveBeenCalledExactlyOnceWith('64');
            },
        );


        it.each([
            [
                'artista',
                'modal-criar-artista',
                'formulario-criar-artista',
                'versaoArtista',
            ],
            [
                'genero',
                'modal-criar-genero',
                'formulario-criar-genero',
                'versaoGenero',
            ],
        ])(
            'incrementa a versao de %s quando a abertura e aceite',
            (
                _tipo,
                identificadorModal,
                identificadorFormulario,
                propriedadeVersao,
            ) => {
                inicializar();

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) =>
                        entrada.idFormulario
                        === identificadorFormulario,
                );

                const antes =
                    configuracao.obterContextoSubmissao()[
                        propriedadeVersao
                    ];

                const modal = document.getElementById(
                    identificadorModal,
                );

                const evento = new Event(
                    'show.bs.modal',
                    {
                        bubbles: true,
                        cancelable: true,
                    },
                );

                modal.dispatchEvent(evento);

                expect(evento.defaultPrevented).toBe(false);

                expect(
                    configuracao.obterContextoSubmissao()[
                        propriedadeVersao
                    ],
                ).toBe(antes + 1);
            },
        );

        it.each([
            [
                'artista',
                'modal-criar-artista',
                'formulario-criar-artista',
                'versaoArtista',
            ],
            [
                'genero',
                'modal-criar-genero',
                'formulario-criar-genero',
                'versaoGenero',
            ],
        ])(
            'nao incrementa a versao de %s quando a abertura e cancelada',
            async (
                _tipo,
                identificadorModal,
                identificadorFormulario,
                propriedadeVersao,
            ) => {
                inicializar();

                const configuracoes =
                    efeitos.gestorModais.mock.calls[0][0];

                const configuracao = configuracoes.find(
                    (entrada) =>
                        entrada.idFormulario
                        === identificadorFormulario,
                );

                const antes =
                    configuracao.obterContextoSubmissao()[
                        propriedadeVersao
                    ];

                const modal = document.getElementById(
                    identificadorModal,
                );

                modal.addEventListener(
                    'show.bs.modal',
                    (evento) => {
                        evento.preventDefault();
                    },
                    {
                        once: true,
                    },
                );

                const evento = new Event(
                    'show.bs.modal',
                    {
                        bubbles: true,
                        cancelable: true,
                    },
                );

                modal.dispatchEvent(evento);

                expect(evento.defaultPrevented).toBe(true);

                await Promise.resolve();

                expect(
                    configuracao.obterContextoSubmissao()[
                        propriedadeVersao
                    ],
                ).toBe(antes);
            },
        );

    },
);
