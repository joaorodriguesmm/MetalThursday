import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import Modal from 'bootstrap/js/dist/modal';

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
});
