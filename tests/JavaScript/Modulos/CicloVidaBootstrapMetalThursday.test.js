import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import Modal from 'bootstrap/js/dist/modal';

import LimpadorFormulariosModais
    from '../../../resources/js/modulos/LimpadorFormulariosModais.js';

const efeitos = vi.hoisted(() => ({
    gestorModais: vi.fn(),
}));

vi.mock(
    '../../../resources/js/modulos/GestorEdicaoMetalThursday',
    () => ({
        default: class GestorEdicaoSimulado {
            constructor() {
                this.inicializado = true;
            }

            adicionarEdicao() {
                return true;
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/GestorImportacaoLancamentoDiscogs',
    () => ({
        default: class GestorImportacaoSimulado {
            constructor() {
                this.inicializado = true;
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/GestorSeccoes',
    () => ({
        default: class GestorSeccoesSimulado {
            constructor() {
                this.inicializado = true;
            }
        },
    }),
);

vi.mock(
    '../../../resources/js/modulos/ValidadorFormulario',
    () => ({
        default: class ValidadorSimulado {
            constructor() {
                this.inicializado = true;
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

function prepararPagina() {
    document.body.innerHTML = `
        <form id="formulario-criar-metal-thursday">
            <input
                id="data-metal-thursday"
                name="data"
                type="date"
            >
            <input id="edicao-metal-thursday" type="text">
            <input id="nome-metal-thursday" name="nome">
            <input type="hidden" name="autor_id" value="1">
            <div id="contentor-seccoes"></div>
            <div id="erro-seccoes"></div>
        </form>

        <div id="estado-edicao-metal-thursday"></div>

        <div
            id="dados-edicoes-metal-thursday"
            data-data-referencia="2026-10-23"
        ></div>

        <div id="modal-criar-artista" class="modal">
            <div class="modal-dialog">
                <form id="formulario-criar-artista">
                    <input name="nome">
                </form>
            </div>
        </div>

        <div id="modal-criar-genero" class="modal">
            <div class="modal-dialog"></div>
        </div>
    `;

    const configuracao = document.createElement('script');

    configuracao.id =
        'configuracao-formulario-metal-thursday';

    configuracao.type = 'application/json';

    configuracao.textContent = JSON.stringify({
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

    document.body.append(configuracao);

    inicializarFormularioMetalThursday(
        'formulario-criar-metal-thursday',
    );

    const configuracoes =
        efeitos.gestorModais.mock.calls[0][0];

    function obterContexto(identificador) {
        const configuracaoModal = configuracoes.find(
            (entrada) =>
                entrada.idFormulario === identificador,
        );

        expect(configuracaoModal).toBeDefined();

        return configuracaoModal.obterContextoSubmissao();
    }

    return {
        obterContextoArtista: () => obterContexto(
            'formulario-criar-artista',
        ),
        obterContextoGenero: () => obterContexto(
            'formulario-criar-genero',
        ),
    };
}

function prepararDestinos() {
    const contentor = document.getElementById(
        'contentor-seccoes',
    );

    contentor.innerHTML = `
        <div class="item-seccao">
            <select
                id="selecao-a"
                class="tom-select-artistas"
            ></select>
            <button id="abrir-a" type="button"></button>
        </div>
        <div class="item-seccao">
            <select
                id="selecao-b"
                class="tom-select-artistas"
            ></select>
            <button id="abrir-b" type="button"></button>
        </div>
    `;

    return {
        destinoA: document.getElementById('selecao-a'),
        destinoB: document.getElementById('selecao-b'),
        acionadorA: document.getElementById('abrir-a'),
        acionadorB: document.getElementById('abrir-b'),
    };
}

function esperarEvento(elemento, nome) {
    return new Promise((resolve) => {
        elemento.addEventListener(
            nome,
            resolve,
            { once: true },
        );
    });
}


function ativarLimpadorModal() {
    const listeners = [];

    const adicionarOriginal =
        document.addEventListener.bind(document);

    const espia = vi.spyOn(
        document,
        'addEventListener',
    ).mockImplementation((tipo, listener, opcoes) => {
        if (tipo === 'hidden.bs.modal') {
            listeners.push({
                listener,
                opcoes,
            });
        }

        adicionarOriginal(
            tipo,
            listener,
            opcoes,
        );
    });

    try {
        new LimpadorFormulariosModais();
    } finally {
        espia.mockRestore();
    }

    return () => {
        listeners.forEach(({ listener, opcoes }) => {
            document.removeEventListener(
                'hidden.bs.modal',
                listener,
                opcoes,
            );
        });
    };
}

describe('B25.41 — ciclo de vida com Bootstrap verdadeiro', () => {
    let instancias;

    beforeEach(() => {
        efeitos.gestorModais.mockClear();
        instancias = [];
    });

    afterEach(() => {
        instancias.forEach((instancia) => {
            instancia.dispose();
        });

        document.body.replaceChildren();
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        vi.restoreAllMocks();
    });

    function criarModal(identificador, animado = false) {
        const elemento = document.getElementById(
            identificador,
        );

        if (animado) {
            elemento.classList.add('fade');
        }

        const instancia = Modal.getOrCreateInstance(
            elemento,
            {
                backdrop: false,
                focus: false,
            },
        );

        instancias.push(instancia);

        return {
            elemento,
            instancia,
        };
    }

    it(
        'reconcilia abertura cancelada e aceite do artista',
        async () => {
            const { obterContextoArtista } = prepararPagina();

            const {
                destinoA,
                destinoB,
                acionadorA,
                acionadorB,
            } = prepararDestinos();

            const { elemento, instancia } = criarModal(
                'modal-criar-artista',
            );

            const versaoInicial =
                obterContextoArtista().versaoArtista;

            const eventosShow = vi.fn();

            elemento.addEventListener(
                'show.bs.modal',
                eventosShow,
            );

            elemento.addEventListener(
                'show.bs.modal',
                (evento) => {
                    if (evento.relatedTarget === acionadorB) {
                        evento.preventDefault();
                    }
                },
                { once: true },
            );

            instancia.show(acionadorB);

            expect(elemento.classList.contains('show'))
                .toBe(false);

            instancia.show(acionadorA);

            await Promise.resolve();

            expect(eventosShow).toHaveBeenCalledTimes(2);

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoInicial + 1);

            expect(obterContextoArtista().selecaoArtistaDestino)
                .toBe(destinoA);

            expect(elemento.classList.contains('show'))
                .toBe(true);

            instancia.show(acionadorB);

            expect(eventosShow).toHaveBeenCalledTimes(2);

            expect(obterContextoArtista().selecaoArtistaDestino)
                .toBe(destinoA);

            instancia.hide();

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoInicial + 2);

            instancia.show(acionadorB);

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoInicial + 3);

            expect(obterContextoArtista().selecaoArtistaDestino)
                .toBe(destinoB);

            instancia.hide();
        },
    );

    it(
        'reconcilia abertura cancelada e aceite do genero',
        async () => {
            const { obterContextoGenero } = prepararPagina();

            const { elemento, instancia } = criarModal(
                'modal-criar-genero',
            );

            const acionador = document.createElement(
                'button',
            );

            document.body.append(acionador);

            const versaoInicial =
                obterContextoGenero().versaoGenero;

            elemento.addEventListener(
                'show.bs.modal',
                (evento) => evento.preventDefault(),
                { once: true },
            );

            instancia.show(acionador);
            instancia.show(acionador);

            await Promise.resolve();

            expect(obterContextoGenero().versaoGenero)
                .toBe(versaoInicial + 1);

            expect(elemento.classList.contains('show'))
                .toBe(true);

            instancia.show(acionador);

            expect(obterContextoGenero().versaoGenero)
                .toBe(versaoInicial + 1);

            instancia.hide();

            expect(obterContextoGenero().versaoGenero)
                .toBe(versaoInicial + 2);
        },
    );

    it(
        'ignora novas aberturas durante transicoes reais do Bootstrap',
        async () => {
            const { obterContextoArtista } = prepararPagina();

            const {
                destinoA,
                destinoB,
                acionadorA,
                acionadorB,
            } = prepararDestinos();

            const { elemento, instancia } = criarModal(
                'modal-criar-artista',
                true,
            );

            const versaoInicial =
                obterContextoArtista().versaoArtista;

            const eventosShow = vi.fn();
            const eventosHide = vi.fn();

            elemento.addEventListener(
                'show.bs.modal',
                eventosShow,
            );

            elemento.addEventListener(
                'hide.bs.modal',
                eventosHide,
            );

            const primeiraAbertura = esperarEvento(
                elemento,
                'shown.bs.modal',
            );

            instancia.show(acionadorA);
            instancia.show(acionadorB);
            instancia.hide();

            expect(eventosShow).toHaveBeenCalledTimes(1);
            expect(eventosHide).not.toHaveBeenCalled();

            expect(obterContextoArtista().selecaoArtistaDestino)
                .toBe(destinoA);

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoInicial + 1);

            await primeiraAbertura;

            const primeiroFecho = esperarEvento(
                elemento,
                'hidden.bs.modal',
            );

            instancia.hide();
            instancia.show(acionadorB);

            expect(eventosShow).toHaveBeenCalledTimes(1);
            expect(eventosHide).toHaveBeenCalledTimes(1);

            await primeiroFecho;

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoInicial + 2);

            const segundaAbertura = esperarEvento(
                elemento,
                'shown.bs.modal',
            );

            instancia.show(acionadorB);

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoInicial + 3);

            expect(obterContextoArtista().selecaoArtistaDestino)
                .toBe(destinoB);

            await segundaAbertura;

            const segundoFecho = esperarEvento(
                elemento,
                'hidden.bs.modal',
            );

            instancia.hide();

            await segundoFecho;

            expect(eventosShow).toHaveBeenCalledTimes(2);
            expect(eventosHide).toHaveBeenCalledTimes(2);
        },
    );

    it(
        'B25.45 — regressa ao artista apos fechar o genero',
        async () => {
            const {
                obterContextoArtista,
                obterContextoGenero,
            } = prepararPagina();

            const {
                destinoA,
                acionadorA,
            } = prepararDestinos();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const acionadorGenero =
                document.createElement('button');

            acionadorGenero.type = 'button';

            artista.elemento
                .querySelector('.modal-dialog')
                .append(acionadorGenero);

            const eventosArtista = vi.fn();

            artista.elemento.addEventListener(
                'show.bs.modal',
                eventosArtista,
            );

            artista.instancia.show(acionadorA);

            await Promise.resolve();

            expect(
                obterContextoArtista().selecaoArtistaDestino,
            ).toBe(destinoA);

            artista.instancia.hide();

            const versaoAnterior =
                obterContextoArtista().versaoArtista;

            genero.instancia.show(acionadorGenero);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(true);

            genero.instancia.hide();

            await Promise.resolve();

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(true);

            expect(
                genero.elemento.classList.contains('show'),
            ).toBe(false);

            expect(eventosArtista).toHaveBeenCalledTimes(2);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(false);

            expect(
                obterContextoArtista().selecaoArtistaDestino,
            ).toBe(destinoA);

            expect(obterContextoArtista().versaoArtista)
                .toBe(versaoAnterior + 1);
        },
    );

    it(
        'B25.45 — nao regressa apos genero cancelado e independente',
        async () => {
            const {
                obterContextoGenero,
            } = prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const acionadorInterno =
                document.createElement('button');

            artista.elemento.append(acionadorInterno);

            const acionadorExterno =
                document.createElement('button');

            document.body.append(acionadorExterno);

            const eventosArtista = vi.fn();

            artista.elemento.addEventListener(
                'show.bs.modal',
                eventosArtista,
            );

            artista.instancia.show();

            const versaoGenero =
                obterContextoGenero().versaoGenero;

            genero.elemento.addEventListener(
                'show.bs.modal',
                (evento) => {
                    evento.preventDefault();
                },
                { once: true },
            );

            genero.instancia.show(acionadorInterno);

            await Promise.resolve();

            expect(
                genero.elemento.classList.contains('show'),
            ).toBe(false);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(false);

            expect(obterContextoGenero().versaoGenero)
                .toBe(versaoGenero);

            artista.instancia.hide();

            genero.instancia.show(acionadorExterno);
            genero.instancia.hide();

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(false);

            expect(eventosArtista).toHaveBeenCalledTimes(1);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(false);
        },
    );

    it(
        'B25.45 — nao reutiliza retorno de um ciclo anterior',
        () => {
            const { obterContextoGenero } = prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const acionadorInterno =
                document.createElement('button');

            artista.elemento.append(acionadorInterno);

            const acionadorExterno =
                document.createElement('button');

            document.body.append(acionadorExterno);

            const eventosArtista = vi.fn();

            artista.elemento.addEventListener(
                'show.bs.modal',
                eventosArtista,
            );

            artista.instancia.show();
            artista.instancia.hide();

            genero.instancia.show(acionadorInterno);
            genero.instancia.hide();

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(true);

            expect(eventosArtista).toHaveBeenCalledTimes(2);

            artista.instancia.hide();

            genero.instancia.show(acionadorExterno);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(false);

            genero.instancia.hide();

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(false);

            expect(eventosArtista).toHaveBeenCalledTimes(2);
        },
    );

    it(
        'B25.45 — fecho cancelado do genero nao reabre artista',
        () => {
            const { obterContextoGenero } = prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const acionadorInterno =
                document.createElement('button');

            artista.elemento.append(acionadorInterno);

            const eventosArtista = vi.fn();

            artista.elemento.addEventListener(
                'show.bs.modal',
                eventosArtista,
            );

            artista.instancia.show();
            artista.instancia.hide();

            genero.instancia.show(acionadorInterno);

            genero.elemento.addEventListener(
                'hide.bs.modal',
                (evento) => {
                    evento.preventDefault();
                },
                { once: true },
            );

            genero.instancia.hide();

            expect(
                genero.elemento.classList.contains('show'),
            ).toBe(true);

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(false);

            expect(eventosArtista).toHaveBeenCalledTimes(1);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(true);

            genero.instancia.hide();

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(true);

            expect(eventosArtista).toHaveBeenCalledTimes(2);

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(false);
        },
    );


    it(
        'B25.45 — transicao declarativa artista genero artista',
        async () => {
            const {
                obterContextoArtista,
                obterContextoGenero,
            } = prepararPagina();

            const { destinoA, acionadorA } =
                prepararDestinos();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const limparListeners = ativarLimpadorModal();

            try {
                acionadorA.setAttribute(
                    'data-bs-toggle',
                    'modal',
                );

                acionadorA.setAttribute(
                    'data-bs-target',
                    '#modal-criar-artista',
                );

                const artistaAberto = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                acionadorA.click();

                await artistaAberto;

                expect(
                    obterContextoArtista()
                        .selecaoArtistaDestino,
                ).toBe(destinoA);

                const nomeArtista =
                    artista.elemento.querySelector(
                        '[name="nome"]',
                    );

                nomeArtista.value = 'Rascunho do artista';

                const acionadorGenero =
                    document.createElement('button');

                acionadorGenero.type = 'button';

                acionadorGenero.setAttribute(
                    'data-bs-toggle',
                    'modal',
                );

                acionadorGenero.setAttribute(
                    'data-bs-target',
                    '#modal-criar-genero',
                );

                artista.elemento
                    .querySelector('.modal-dialog')
                    .append(acionadorGenero);

                const artistaOculto = esperarEvento(
                    artista.elemento,
                    'hidden.bs.modal',
                );

                const generoAberto = esperarEvento(
                    genero.elemento,
                    'shown.bs.modal',
                );

                acionadorGenero.click();

                await Promise.all([
                    artistaOculto,
                    generoAberto,
                ]);

                expect(
                    artista.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(false);

                expect(
                    genero.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(true);

                expect(
                    obterContextoGenero().generoParaArtista,
                ).toBe(true);

                expect(nomeArtista.value).toBe(
                    'Rascunho do artista',
                );

                expect(
                    artista.elemento.hasAttribute(
                        'data-preservar-formularios-ao-fechar',
                    ),
                ).toBe(false);

                const generoOculto = esperarEvento(
                    genero.elemento,
                    'hidden.bs.modal',
                );

                const artistaReaberto = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                genero.instancia.hide();

                await Promise.all([
                    generoOculto,
                    artistaReaberto,
                ]);

                expect(
                    artista.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(true);

                expect(nomeArtista.value).toBe(
                    'Rascunho do artista',
                );

                expect(
                    obterContextoGenero().generoParaArtista,
                ).toBe(false);

                expect(
                    obterContextoArtista()
                        .selecaoArtistaDestino,
                ).toBe(destinoA);
            } finally {
                limparListeners();
            }
        },
    );

    it(
        'B25.45 — genero declarativo independente nao regressa ao artista',
        async () => {
            const { obterContextoGenero } =
                prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const acionador =
                document.createElement('button');

            acionador.type = 'button';

            acionador.setAttribute(
                'data-bs-toggle',
                'modal',
            );

            acionador.setAttribute(
                'data-bs-target',
                '#modal-criar-genero',
            );

            document.body.append(acionador);

            const aberturasArtista = vi.fn();

            artista.elemento.addEventListener(
                'show.bs.modal',
                aberturasArtista,
            );

            const generoAberto = esperarEvento(
                genero.elemento,
                'shown.bs.modal',
            );

            acionador.click();

            await generoAberto;

            expect(
                obterContextoGenero().generoParaArtista,
            ).toBe(false);

            const generoOculto = esperarEvento(
                genero.elemento,
                'hidden.bs.modal',
            );

            genero.instancia.hide();

            await generoOculto;

            expect(aberturasArtista).not.toHaveBeenCalled();

            expect(
                artista.elemento.classList.contains('show'),
            ).toBe(false);
        },
    );

    it(
        'B25.45 — cancelamento declarativo preserva rascunho de artista',
        async () => {
            prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const limparListeners = ativarLimpadorModal();

            try {
                const aberturaArtista = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                artista.instancia.show();

                await aberturaArtista;

                const nomeArtista =
                    artista.elemento.querySelector(
                        '[name="nome"]',
                    );

                nomeArtista.value =
                    'Rascunho a preservar apos cancelamento';

                const acionador =
                    document.createElement('button');

                acionador.type = 'button';

                acionador.setAttribute(
                    'data-bs-toggle',
                    'modal',
                );

                acionador.setAttribute(
                    'data-bs-target',
                    '#modal-criar-genero',
                );

                artista.elemento
                    .querySelector('.modal-dialog')
                    .append(acionador);

                genero.elemento.addEventListener(
                    'show.bs.modal',
                    (evento) => {
                        evento.preventDefault();
                    },
                    { once: true },
                );

                acionador.click();

                // Aguarda o processamento dos eventos de
                // fecho e das microtarefas de reconciliacao.
                await new Promise((resolve) => {
                    setTimeout(resolve, 40);
                });

                expect(
                    genero.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(false);

                expect(nomeArtista.value).toBe(
                    'Rascunho a preservar apos cancelamento',
                );

                expect(
                    artista.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(true);
            } finally {
                limparListeners();
            }
        },
    );


    it(
        'B25.45 — preserva artista com transicao fade',
        async () => {
            const { obterContextoGenero } = prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            artista.elemento.classList.add('fade');
            genero.elemento.classList.add('fade');

            const limparListeners = ativarLimpadorModal();

            try {
                const nome = artista.elemento.querySelector(
                    '[name="nome"]',
                );

                const acionador = document.createElement(
                    'button',
                );

                acionador.type = 'button';

                acionador.setAttribute(
                    'data-bs-toggle',
                    'modal',
                );

                acionador.setAttribute(
                    'data-bs-target',
                    '#modal-criar-genero',
                );

                artista.elemento
                    .querySelector('.modal-dialog')
                    .append(acionador);

                const primeiraAbertura = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                artista.instancia.show();
                await primeiraAbertura;

                nome.value = 'Artista com animacao';

                const artistaOculto = esperarEvento(
                    artista.elemento,
                    'hidden.bs.modal',
                );

                const generoAberto = esperarEvento(
                    genero.elemento,
                    'shown.bs.modal',
                );

                acionador.click();

                await Promise.all([
                    artistaOculto,
                    generoAberto,
                ]);

                expect(nome.value).toBe(
                    'Artista com animacao',
                );

                expect(
                    obterContextoGenero().generoParaArtista,
                ).toBe(true);

                const generoOculto = esperarEvento(
                    genero.elemento,
                    'hidden.bs.modal',
                );

                const artistaReaberto = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                genero.instancia.hide();

                await Promise.all([
                    generoOculto,
                    artistaReaberto,
                ]);

                expect(
                    artista.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(true);

                expect(nome.value).toBe(
                    'Artista com animacao',
                );

                expect(
                    obterContextoGenero().generoParaArtista,
                ).toBe(false);
            } finally {
                limparListeners();
            }
        },
    );

    it(
        'B25.45 — recupera artista quando genero fade e cancelado',
        async () => {
            const { obterContextoGenero } = prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            artista.elemento.classList.add('fade');
            genero.elemento.classList.add('fade');

            const limparListeners = ativarLimpadorModal();

            try {
                const nome = artista.elemento.querySelector(
                    '[name="nome"]',
                );

                const acionador = document.createElement(
                    'button',
                );

                acionador.type = 'button';

                acionador.setAttribute(
                    'data-bs-toggle',
                    'modal',
                );

                acionador.setAttribute(
                    'data-bs-target',
                    '#modal-criar-genero',
                );

                artista.elemento
                    .querySelector('.modal-dialog')
                    .append(acionador);

                const primeiraAbertura = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                artista.instancia.show();
                await primeiraAbertura;

                nome.value = 'Artista antes do cancelamento';

                genero.elemento.addEventListener(
                    'show.bs.modal',
                    (evento) => {
                        evento.preventDefault();
                    },
                    { once: true },
                );

                const artistaOculto = esperarEvento(
                    artista.elemento,
                    'hidden.bs.modal',
                );

                const artistaReaberto = esperarEvento(
                    artista.elemento,
                    'shown.bs.modal',
                );

                acionador.click();

                await Promise.all([
                    artistaOculto,
                    artistaReaberto,
                ]);

                expect(
                    genero.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(false);

                expect(
                    artista.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(true);

                expect(nome.value).toBe(
                    'Artista antes do cancelamento',
                );

                expect(
                    obterContextoGenero().generoParaArtista,
                ).toBe(false);

                expect(
                    artista.elemento.hasAttribute(
                        'data-preservar-formularios-ao-fechar',
                    ),
                ).toBe(false);
            } finally {
                limparListeners();
            }
        },
    );

    it(
        'B25.45 — clique interrompido nao deixa preservacao pendente',
        async () => {
            prepararPagina();

            const artista = criarModal(
                'modal-criar-artista',
            );

            const genero = criarModal(
                'modal-criar-genero',
            );

            const limparListeners = ativarLimpadorModal();

            try {
                const nome = artista.elemento.querySelector(
                    '[name="nome"]',
                );

                const acionador = document.createElement(
                    'button',
                );

                acionador.type = 'button';

                acionador.setAttribute(
                    'data-bs-toggle',
                    'modal',
                );

                acionador.setAttribute(
                    'data-bs-target',
                    '#modal-criar-genero',
                );

                artista.elemento
                    .querySelector('.modal-dialog')
                    .append(acionador);

                artista.instancia.show();

                nome.value = 'Rascunho sem transicao';

                const aberturasGenero = vi.fn();

                genero.elemento.addEventListener(
                    'show.bs.modal',
                    aberturasGenero,
                );

                // Interromper na window, depois do
                // preparador da aplicacao e antes do
                // listener delegado do Bootstrap.
                window.addEventListener(
                    'click',
                    (evento) => {
                        evento.stopPropagation();
                    },
                    { once: true, capture: true },
                );

                acionador.click();

                await Promise.resolve();

                expect(
                    artista.elemento.classList.contains(
                        'show',
                    ),
                ).toBe(true);

                expect(aberturasGenero).not.toHaveBeenCalled();

                expect(nome.value).toBe(
                    'Rascunho sem transicao',
                );

                expect(
                    artista.elemento.hasAttribute(
                        'data-preservar-formularios-ao-fechar',
                    ),
                ).toBe(false);

                // O mesmo botao deve continuar a funcionar
                // numa tentativa posterior, sem interferencia
                // do clique que foi interrompido.
                const generoAberto = esperarEvento(
                    genero.elemento,
                    'shown.bs.modal',
                );

                acionador.click();

                await generoAberto;

                expect(aberturasGenero).toHaveBeenCalledTimes(
                    1,
                );

                expect(nome.value).toBe(
                    'Rascunho sem transicao',
                );
            } finally {
                limparListeners();
            }
        },
    );

});
