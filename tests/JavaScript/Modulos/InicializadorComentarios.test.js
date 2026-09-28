import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import InicializadorComentarios
    from '../../../resources/js/modulos/InicializadorComentarios.js';

describe(
    'InicializadorComentarios',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = `
                    <section id="comentarios">
                        <article
                            id="comentario-10"
                            class="comentario"
                            data-nivel-visual="2"
                        >
                            <button
                                id="alternador-respostas"
                                type="button"
                                data-acao-comentarios="alternar-respostas"
                                data-endereco-respostas="/comentarios/10/respostas"
                                data-quantidade-respostas="2"
                                aria-expanded="false"
                            >
                                <span
                                    data-texto-alternador-respostas
                                >
                                    Ver 2 respostas
                                </span>
                            </button>

                            <div
                                id="respostas-comentario-10"
                                data-respostas-comentario
                                data-respostas-carregadas="false"
                                hidden
                            ></div>
                        </article>
                    </section>
                `;
            },
        );

        afterEach(
            () => {
                vi.restoreAllMocks();
                document.body.replaceChildren();
            },
        );

        it(
            'carrega respostas uma unica vez e expande o ramo',
            async () => {
                const pedido =
                    vi.spyOn(
                        axios,
                        'get',
                    )
                        .mockResolvedValue({
                            data: {
                                numero_respostas: 2,
                                respostas: [
                                    {
                                        comentario_html:
                                            '<article id="comentario-11" class="comentario"></article>',
                                    },
                                    {
                                        comentario_html:
                                            '<article id="comentario-12" class="comentario"></article>',
                                    },
                                ],
                            },
                        });

                const inicializador =
                    new InicializadorComentarios(
                        document.getElementById(
                            'comentarios',
                        ),
                    );

                const botao =
                    document.getElementById(
                        'alternador-respostas',
                    );

                const contentor =
                    document.getElementById(
                        'respostas-comentario-10',
                    );

                await inicializador.alternarRespostas(
                    botao,
                );

                expect(
                    pedido,
                ).toHaveBeenCalledTimes(1);

                expect(
                    pedido,
                ).toHaveBeenCalledWith(
                    '/comentarios/10/respostas',
                );

                expect(
                    contentor.dataset
                        .respostasCarregadas,
                ).toBe('true');

                expect(
                    contentor.hidden,
                ).toBe(false);

                expect(
                    botao.getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('true');

                expect(
                    botao.disabled,
                ).toBe(false);

                expect(
                    botao.hasAttribute(
                        'aria-busy',
                    ),
                ).toBe(false);

                expect(
                    botao.querySelector(
                        '[data-texto-alternador-respostas]',
                    ).textContent,
                ).toBe(
                    'Ocultar respostas',
                );

                const respostas =
                    Array.from(
                        contentor.children,
                    );

                expect(
                    respostas,
                ).toHaveLength(2);

                expect(
                    respostas.every(
                        (resposta) =>
                            resposta.dataset
                                .nivelVisual
                            === '3',
                    ),
                ).toBe(true);
            },
        );

        it(
            'preserva o ramo recolhido quando o servidor devolve quantidade inconsistente',
            async () => {
                vi.spyOn(
                    axios,
                    'get',
                )
                    .mockResolvedValue({
                        data: {
                            numero_respostas: 2,
                            respostas: [
                                {
                                    comentario_html:
                                        '<article id="comentario-11" class="comentario"></article>',
                                },
                            ],
                        },
                    });

                const inicializador =
                    new InicializadorComentarios(
                        document.getElementById(
                            'comentarios',
                        ),
                    );

                const botao =
                    document.getElementById(
                        'alternador-respostas',
                    );

                const contentor =
                    document.getElementById(
                        'respostas-comentario-10',
                    );

                await inicializador.alternarRespostas(
                    botao,
                );

                expect(
                    contentor.dataset
                        .respostasCarregadas,
                ).toBe('false');

                expect(
                    contentor.hidden,
                ).toBe(true);

                expect(
                    contentor.children,
                ).toHaveLength(0);

                expect(
                    botao.dataset
                        .quantidadeRespostas,
                ).toBe('2');

                expect(
                    botao.getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('false');

                expect(
                    botao.disabled,
                ).toBe(false);

                expect(
                    botao.hasAttribute(
                        'aria-busy',
                    ),
                ).toBe(false);

                expect(
                    botao.querySelector(
                        '[data-texto-alternador-respostas]',
                    ).textContent,
                ).toBe(
                    'Tentar carregar 2 respostas',
                );
            },
        );

        it(
            'reutiliza respostas ja carregadas sem repetir o pedido',
            async () => {
                const pedido =
                    vi.spyOn(
                        axios,
                        'get',
                    );

                const contentor =
                    document.getElementById(
                        'respostas-comentario-10',
                    );

                contentor.dataset
                    .respostasCarregadas =
                        'true';

                contentor.innerHTML = `
                    <article
                        id="comentario-11"
                        class="comentario"
                        data-nivel-visual="3"
                    ></article>
                    <article
                        id="comentario-12"
                        class="comentario"
                        data-nivel-visual="3"
                    ></article>
                `;

                const inicializador =
                    new InicializadorComentarios(
                        document.getElementById(
                            'comentarios',
                        ),
                    );

                const botao =
                    document.getElementById(
                        'alternador-respostas',
                    );

                await inicializador.alternarRespostas(
                    botao,
                );

                expect(
                    pedido,
                ).not.toHaveBeenCalled();

                expect(
                    contentor.hidden,
                ).toBe(false);

                expect(
                    botao.getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('true');

                await inicializador.alternarRespostas(
                    botao,
                );

                expect(
                    pedido,
                ).not.toHaveBeenCalled();

                expect(
                    contentor.hidden,
                ).toBe(true);

                expect(
                    botao.getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('false');

                expect(
                    botao.querySelector(
                        '[data-texto-alternador-respostas]',
                    ).textContent,
                ).toBe(
                    'Ver 2 respostas',
                );
            },
        );

        it(
            'insere comentario principal e incrementa o contador geral',
            async () => {
                const inicializador =
                    new InicializadorComentarios(
                        document,
                    );

                document.body.innerHTML = `
                    <button
                        type="button"
                        aria-controls="conversa-comentarios"
                    >
                        <span data-quantidade-comentarios>2</span>
                    </button>

                    <div
                        id="conversa-comentarios"
                        class="collapse"
                    >
                        <section aria-label="Comentários">
                            <form id="formulario-comentario"></form>

                            <div class="lista-comentarios">
                                <p class="sem-comentarios">
                                    Ainda não existem comentários.
                                </p>
                            </div>
                        </section>
                    </div>
                `;

                const formulario =
                    document.getElementById(
                        'formulario-comentario',
                    );

                await inicializador
                    .atualizarInterfaceAposPublicacao(
                        formulario,
                        {
                            comentario_html:
                                '<article id="comentario-20" class="comentario"></article>',
                        },
                    );

                const lista =
                    document.querySelector(
                        '.lista-comentarios',
                    );

                const comentario =
                    lista.firstElementChild;

                expect(
                    comentario.id,
                ).toBe('comentario-20');

                expect(
                    comentario.dataset
                        .nivelVisual,
                ).toBe('1');

                expect(
                    lista.querySelector(
                        '.sem-comentarios',
                    ),
                ).toBeNull();

                expect(
                    document.querySelector(
                        '[data-quantidade-comentarios]',
                    ).textContent,
                ).toBe('3');
            },
        );

        it(
            'insere primeira resposta fecha formulario e atualiza contadores',
            async () => {
                const inicializador =
                    new InicializadorComentarios(
                        document,
                    );

                document.body.innerHTML = `
                    <button
                        type="button"
                        aria-controls="conversa-comentarios"
                    >
                        <span data-quantidade-comentarios>4</span>
                    </button>

                    <div
                        id="conversa-comentarios"
                        class="collapse"
                    >
                        <section aria-label="Comentários">
                            <article
                                id="comentario-10"
                                class="comentario"
                                data-nivel-visual="2"
                            >
                                <button
                                    type="button"
                                    aria-controls="contentor-formulario-resposta-10"
                                    aria-expanded="true"
                                >
                                    Responder
                                </button>

                                <div
                                    id="contentor-formulario-resposta-10"
                                    class="contentor-formulario-resposta"
                                >
                                    <form
                                        id="formulario-resposta-10"
                                        class="formulario-resposta-comentario"
                                        data-identificador-comentario-pai="10"
                                    ></form>
                                </div>

                                <button
                                    id="alternador-respostas-10"
                                    type="button"
                                    data-acao-comentarios="alternar-respostas"
                                    data-quantidade-respostas="0"
                                    aria-expanded="false"
                                    hidden
                                >
                                    <span
                                        data-texto-alternador-respostas
                                    >
                                        Ver 0 respostas
                                    </span>
                                </button>

                                <div
                                    id="respostas-comentario-10"
                                    data-respostas-comentario
                                    data-respostas-carregadas="false"
                                    hidden
                                ></div>
                            </article>
                        </section>
                    </div>
                `;

                const formulario =
                    document.getElementById(
                        'formulario-resposta-10',
                    );

                await inicializador
                    .atualizarInterfaceAposPublicacao(
                        formulario,
                        {
                            comentario_html:
                                '<article id="comentario-11" class="comentario"></article>',
                        },
                    );

                expect(
                    document.querySelector(
                        '[data-quantidade-comentarios]',
                    ).textContent,
                ).toBe('5');

                const botao =
                    document.getElementById(
                        'alternador-respostas-10',
                    );

                expect(
                    botao.dataset
                        .quantidadeRespostas,
                ).toBe('1');

                expect(
                    botao.hidden,
                ).toBe(false);

                expect(
                    botao.getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('true');

                expect(
                    botao.querySelector(
                        '[data-texto-alternador-respostas]',
                    ).textContent,
                ).toBe(
                    'Ocultar respostas',
                );

                const contentorRespostas =
                    document.getElementById(
                        'respostas-comentario-10',
                    );

                expect(
                    contentorRespostas.dataset
                        .respostasCarregadas,
                ).toBe('true');

                expect(
                    contentorRespostas.hidden,
                ).toBe(false);

                const resposta =
                    contentorRespostas
                        .firstElementChild;

                expect(
                    resposta.id,
                ).toBe('comentario-11');

                expect(
                    resposta.dataset
                        .nivelVisual,
                ).toBe('3');

                const contentorFormulario =
                    document.getElementById(
                        'contentor-formulario-resposta-10',
                    );

                expect(
                    contentorFormulario.hidden,
                ).toBe(true);

                expect(
                    contentorFormulario.getAttribute(
                        'aria-hidden',
                    ),
                ).toBe('true');

                expect(
                    document.querySelector(
                        'button[aria-controls="contentor-formulario-resposta-10"]',
                    ).getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('false');
            },
        );

        it(
            'recarrega ramo antigo depois de publicar nova resposta',
            async () => {
                const pedido =
                    vi.spyOn(
                        axios,
                        'get',
                    )
                        .mockResolvedValue({
                            data: {
                                numero_respostas: 3,
                                respostas: [
                                    {
                                        comentario_html:
                                            '<article id="comentario-11" class="comentario"></article>',
                                    },
                                    {
                                        comentario_html:
                                            '<article id="comentario-12" class="comentario"></article>',
                                    },
                                    {
                                        comentario_html:
                                            '<article id="comentario-13" class="comentario"></article>',
                                    },
                                ],
                            },
                        });

                const inicializador =
                    new InicializadorComentarios(
                        document,
                    );

                document.body.innerHTML = `
                    <button
                        type="button"
                        aria-controls="conversa-comentarios"
                    >
                        <span data-quantidade-comentarios>7</span>
                    </button>

                    <div
                        id="conversa-comentarios"
                        class="collapse"
                    >
                        <section aria-label="Comentários">
                            <article
                                id="comentario-10"
                                class="comentario"
                                data-nivel-visual="1"
                            >
                                <div
                                    id="contentor-formulario-resposta-10"
                                    class="contentor-formulario-resposta"
                                >
                                    <form
                                        id="formulario-resposta-10"
                                        class="formulario-resposta-comentario"
                                        data-identificador-comentario-pai="10"
                                    ></form>
                                </div>

                                <button
                                    id="alternador-respostas-10"
                                    type="button"
                                    data-acao-comentarios="alternar-respostas"
                                    data-endereco-respostas="/comentarios/10/respostas"
                                    data-quantidade-respostas="2"
                                    aria-expanded="false"
                                >
                                    <span
                                        data-texto-alternador-respostas
                                    >
                                        Ver 2 respostas
                                    </span>
                                </button>

                                <div
                                    id="respostas-comentario-10"
                                    data-respostas-comentario
                                    data-respostas-carregadas="false"
                                    hidden
                                ></div>
                            </article>
                        </section>
                    </div>
                `;

                const formulario =
                    document.getElementById(
                        'formulario-resposta-10',
                    );

                await inicializador
                    .atualizarInterfaceAposPublicacao(
                        formulario,
                        {
                            comentario_html:
                                '<article id="comentario-13" class="comentario"></article>',
                        },
                    );

                expect(
                    pedido,
                ).toHaveBeenCalledTimes(1);

                expect(
                    pedido,
                ).toHaveBeenCalledWith(
                    '/comentarios/10/respostas',
                );

                expect(
                    document.querySelector(
                        '[data-quantidade-comentarios]',
                    ).textContent,
                ).toBe('8');

                const botao =
                    document.getElementById(
                        'alternador-respostas-10',
                    );

                expect(
                    botao.dataset
                        .quantidadeRespostas,
                ).toBe('3');

                expect(
                    botao.getAttribute(
                        'aria-expanded',
                    ),
                ).toBe('true');

                const contentor =
                    document.getElementById(
                        'respostas-comentario-10',
                    );

                expect(
                    contentor.dataset
                        .respostasCarregadas,
                ).toBe('true');

                expect(
                    Array.from(
                        contentor.children,
                    ).map(
                        (comentario) =>
                            comentario.id,
                    ),
                ).toEqual([
                    'comentario-11',
                    'comentario-12',
                    'comentario-13',
                ]);

                expect(
                    Array.from(
                        contentor.children,
                    ).every(
                        (comentario) =>
                            comentario.dataset
                                .nivelVisual
                            === '2',
                    ),
                ).toBe(true);
            },
        );
    },
);
