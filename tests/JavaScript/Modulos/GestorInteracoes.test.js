import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorInteracoes
    from '../../../resources/js/modulos/GestorInteracoes.js';

describe(
    'GestorInteracoes',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = `
                    <main id="interacoes"></main>
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
            'processa um gosto remoto e repoe o estado acessivel do botao',
            async () => {
                const contentor =
                    document.getElementById(
                        'interacoes',
                    );

                contentor.innerHTML = `
                    <button
                        id="botao-gosto"
                        type="button"
                        data-tipo-interacao="alternar-gosto"
                        data-endereco="/comentarios/10/gosto"
                        aria-pressed="false"
                    >
                        <span
                            data-icone-gosto
                            class="bi bi-heart"
                        ></span>
                        <span data-quantidade-gostos>0</span>
                    </button>
                `;

                const botao =
                    document.getElementById(
                        'botao-gosto',
                    );

                const pedido =
                    vi.spyOn(
                        axios,
                        'post',
                    )
                        .mockResolvedValue({
                            data: {
                                adicionado: true,
                                numero_gostos: 1,
                            },
                        });

                const gestor =
                    new GestorInteracoes(
                        '#interacoes',
                    );

                const submissao =
                    gestor.tratarInteracao(
                        botao,
                        'alternar-gosto',
                    );

                expect(
                    botao.disabled,
                ).toBe(true);

                expect(
                    botao.getAttribute(
                        'aria-busy',
                    ),
                ).toBe('true');

                await submissao;

                expect(
                    pedido,
                ).toHaveBeenCalledTimes(1);

                expect(
                    pedido,
                ).toHaveBeenCalledWith(
                    new URL(
                        '/comentarios/10/gosto',
                        window.location.origin,
                    ).href,
                    { estado_desejado: true },
                );

                expect(
                    botao.disabled,
                ).toBe(false);

                expect(
                    botao.hasAttribute(
                        'aria-busy',
                    ),
                ).toBe(false);

                expect(
                    botao.getAttribute(
                        'aria-pressed',
                    ),
                ).toBe('true');

                expect(
                    botao.getAttribute(
                        'aria-label',
                    ),
                ).toBe(
                    'Gosto. 1 gosto.',
                );

                const icone =
                    botao.querySelector(
                        '[data-icone-gosto]',
                    );

                expect(
                    icone.classList.contains(
                        'bi-heart-fill',
                    ),
                ).toBe(true);

                expect(
                    icone.classList.contains(
                        'text-danger',
                    ),
                ).toBe(true);

                expect(
                    botao.querySelector(
                        '[data-quantidade-gostos]',
                    ).textContent,
                ).toBe('1');
            },
        );

        it(
            'processa uma audicao remota e atualiza texto contador e descricao',
            async () => {
                const contentor =
                    document.getElementById(
                        'interacoes',
                    );

                contentor.innerHTML = `
                    <div data-contentor-interacoes>
                        <button
                            id="botao-audicao"
                            type="button"
                            data-tipo-interacao="alternar-audicao"
                            data-tipo-audivel="seccao-metal-thursday"
                            data-estado-audicao="false"
                            data-endereco="/seccoes/15/audicao"
                            aria-pressed="false"
                        >
                            <span data-texto-interacao>
                                Marcar como ouvido
                            </span>
                        </button>

                        <button
                            type="button"
                            class="apresentacao-audicoes"
                            aria-label="Consultar detalhes das audições. Total: 0."
                        >
                            <span class="quantidade-audicoes">0</span>
                        </button>
                    </div>
                `;

                const botao =
                    document.getElementById(
                        'botao-audicao',
                    );

                const pedido =
                    vi.spyOn(
                        axios,
                        'post',
                    )
                        .mockResolvedValue({
                            data: {
                                marcado_como_ouvido: true,
                                numero_audicoes: 3,
                            },
                        });

                const gestor =
                    new GestorInteracoes(
                        '#interacoes',
                    );

                await gestor.tratarInteracao(
                    botao,
                    'alternar-audicao',
                );

                expect(
                    pedido,
                ).toHaveBeenCalledWith(
                    new URL(
                        '/seccoes/15/audicao',
                        window.location.origin,
                    ).href,
                    { estado_desejado: true },
                );

                expect(
                    botao.querySelector(
                        '[data-texto-interacao]',
                    ).textContent.trim(),
                ).toBe('Ouvido');

                expect(
                    botao.getAttribute(
                        'aria-label',
                    ),
                ).toBe(
                    'Marcar como não ouvido',
                );

                expect(
                    botao.hasAttribute(
                        'aria-pressed',
                    ),
                ).toBe(false);

                expect(botao.dataset.estadoAudicao).toBe('true');

                const apresentacao =
                    contentor.querySelector(
                        '.apresentacao-audicoes',
                    );

                expect(
                    apresentacao.querySelector(
                        '.quantidade-audicoes',
                    ).textContent,
                ).toBe('3');

                expect(
                    apresentacao.getAttribute(
                        'aria-label',
                    ),
                ).toBe(
                    'Consultar detalhes das audições. Total: 3.',
                );

                expect(
                    botao.disabled,
                ).toBe(false);

                expect(
                    botao.hasAttribute(
                        'aria-busy',
                    ),
                ).toBe(false);
            },
        );

        it(
            'reutiliza a mesma intencao de gosto apos resposta perdida',
            async () => {
                const contentor = document.getElementById('interacoes');
                contentor.innerHTML = `<button
                    id="gosto-repetido"
                    type="button"
                    data-tipo-interacao="alternar-gosto"
                    data-endereco="/comentarios/50/gosto"
                    aria-pressed="false"
                ><span data-icone-gosto></span>
                <span data-quantidade-gostos>0</span></button>`;

                const botao = document.getElementById('gosto-repetido');
                const pedido = vi.spyOn(axios, 'post')
                    .mockRejectedValueOnce(new Error('Resposta perdida'))
                    .mockResolvedValueOnce({
                        data: { adicionado: true, numero_gostos: 1 },
                    });
                const gestor = new GestorInteracoes('#interacoes');
                vi.spyOn(gestor, 'mostrarErroPedido')
                    .mockResolvedValue(undefined);

                await gestor.tratarInteracao(botao, 'alternar-gosto');
                expect(botao.getAttribute('aria-pressed')).toBe('false');

                await gestor.tratarInteracao(botao, 'alternar-gosto');
                const endereco = new URL(
                    '/comentarios/50/gosto', window.location.origin,
                ).href;
                expect(pedido).toHaveBeenCalledTimes(2);
                expect(pedido).toHaveBeenNthCalledWith(
                    1, endereco, { estado_desejado: true },
                );
                expect(pedido).toHaveBeenNthCalledWith(
                    2, endereco, { estado_desejado: true },
                );
                expect(botao.getAttribute('aria-pressed')).toBe('true');
            },
        );

        it(
            'reutiliza a mesma intencao de audicao apos resposta perdida',
            async () => {
                const contentor = document.getElementById('interacoes');
                contentor.innerHTML = `<div data-contentor-interacoes>
                    <button
                        id="audicao-repetida"
                        type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="metal-thursday"
                        data-estado-audicao="false"
                        data-endereco="/metal-thursdays/50/audicao"
                    ><span data-texto-interacao>Marcar como ouvida</span></button>
                </div>`;

                const botao = document.getElementById('audicao-repetida');
                const pedido = vi.spyOn(axios, 'post')
                    .mockRejectedValueOnce(new Error('Resposta perdida'))
                    .mockResolvedValueOnce({
                        data: {
                            marcado_como_ouvido: true,
                            numero_audicoes: 1,
                        },
                    });
                const gestor = new GestorInteracoes('#interacoes');
                vi.spyOn(gestor, 'mostrarErroPedido')
                    .mockResolvedValue(undefined);

                await gestor.tratarInteracao(botao, 'alternar-audicao');
                expect(botao.dataset.estadoAudicao).toBe('false');

                await gestor.tratarInteracao(botao, 'alternar-audicao');
                const endereco = new URL(
                    '/metal-thursdays/50/audicao', window.location.origin,
                ).href;
                expect(pedido).toHaveBeenCalledTimes(2);
                expect(pedido).toHaveBeenNthCalledWith(
                    1, endereco, { estado_desejado: true },
                );
                expect(pedido).toHaveBeenNthCalledWith(
                    2, endereco, { estado_desejado: true },
                );
                expect(botao.dataset.estadoAudicao).toBe('true');
            },
        );

        it(
            'permite desfazer o gosto apos a primeira resposta bem sucedida',
            async () => {
                const contentor = document.getElementById('interacoes');
                contentor.innerHTML = `<button
                    id="gosto-intencional"
                    type="button"
                    data-tipo-interacao="alternar-gosto"
                    data-endereco="/comentarios/51/gosto"
                    aria-pressed="false"
                ><span data-icone-gosto></span>
                <span data-quantidade-gostos>0</span></button>`;

                const botao = document.getElementById('gosto-intencional');
                const pedido = vi.spyOn(axios, 'post')
                    .mockResolvedValueOnce({
                        data: { adicionado: true, numero_gostos: 1 },
                    })
                    .mockResolvedValueOnce({
                        data: { adicionado: false, numero_gostos: 0 },
                    });
                const gestor = new GestorInteracoes('#interacoes');

                await gestor.tratarInteracao(botao, 'alternar-gosto');
                await gestor.tratarInteracao(botao, 'alternar-gosto');

                expect(pedido.mock.calls.map(([, dados]) => dados)).toEqual([
                    { estado_desejado: true },
                    { estado_desejado: false },
                ]);
                expect(botao.getAttribute('aria-pressed')).toBe('false');
            },
        );

        it(
            'permite desfazer a audicao apos a primeira resposta bem sucedida',
            async () => {
                const contentor = document.getElementById('interacoes');
                contentor.innerHTML = `<div data-contentor-interacoes>
                    <button
                        id="audicao-intencional"
                        type="button"
                        data-tipo-interacao="alternar-audicao"
                        data-tipo-audivel="seccao-metal-thursday"
                        data-estado-audicao="false"
                        data-endereco="/seccoes/51/audicao"
                    ><span data-texto-interacao>Marcar como ouvido</span></button>
                </div>`;

                const botao = document.getElementById('audicao-intencional');
                const pedido = vi.spyOn(axios, 'post')
                    .mockResolvedValueOnce({
                        data: {
                            marcado_como_ouvido: true,
                            numero_audicoes: 1,
                        },
                    })
                    .mockResolvedValueOnce({
                        data: {
                            marcado_como_ouvido: false,
                            numero_audicoes: 0,
                        },
                    });
                const gestor = new GestorInteracoes('#interacoes');

                await gestor.tratarInteracao(botao, 'alternar-audicao');
                await gestor.tratarInteracao(botao, 'alternar-audicao');

                expect(pedido.mock.calls.map(([, dados]) => dados)).toEqual([
                    { estado_desejado: true },
                    { estado_desejado: false },
                ]);
                expect(botao.dataset.estadoAudicao).toBe('false');
            },
        );

        it(
            'impede pedidos remotos concorrentes no mesmo botao',
            async () => {
                const contentor =
                    document.getElementById(
                        'interacoes',
                    );

                contentor.innerHTML = `
                    <button
                        id="botao-gosto"
                        type="button"
                        data-tipo-interacao="alternar-gosto"
                        data-endereco="/comentarios/20/gosto"
                        aria-pressed="false"
                    >
                        <span data-icone-gosto></span>
                        <span data-quantidade-gostos>0</span>
                    </button>
                `;

                const botao =
                    document.getElementById(
                        'botao-gosto',
                    );

                let resolverPedido;

                const respostaPendente =
                    new Promise(
                        (resolver) => {
                            resolverPedido =
                                resolver;
                        },
                    );

                const pedido =
                    vi.spyOn(
                        axios,
                        'post',
                    )
                        .mockReturnValue(
                            respostaPendente,
                        );

                const gestor =
                    new GestorInteracoes(
                        '#interacoes',
                    );

                const primeiraSubmissao =
                    gestor.tratarInteracao(
                        botao,
                        'alternar-gosto',
                    );

                const segundaSubmissao =
                    gestor.tratarInteracao(
                        botao,
                        'alternar-gosto',
                    );

                expect(
                    pedido,
                ).toHaveBeenCalledTimes(1);

                resolverPedido({
                    data: {
                        adicionado: true,
                        numero_gostos: 1,
                    },
                });

                await Promise.all([
                    primeiraSubmissao,
                    segundaSubmissao,
                ]);

                expect(
                    botao.disabled,
                ).toBe(false);

                expect(
                    botao.hasAttribute(
                        'aria-busy',
                    ),
                ).toBe(false);
            },
        );

        it(
            'nao altera o gosto quando a resposta nao contem estado booleano',
            () => {
                const contentor =
                    document.getElementById(
                        'interacoes',
                    );

                contentor.innerHTML = `
                    <button
                        id="botao-gosto"
                        type="button"
                        aria-pressed="false"
                        aria-label="Gosto. 2 gostos."
                    >
                        <span
                            data-icone-gosto
                            class="bi bi-heart"
                        ></span>
                        <span data-quantidade-gostos>2</span>
                    </button>
                `;

                const botao =
                    document.getElementById(
                        'botao-gosto',
                    );

                const gestor =
                    new GestorInteracoes(
                        '#interacoes',
                    );

                gestor.atualizarGosto(
                    botao,
                    {
                        adicionado: 'sim',
                        numero_gostos: 99,
                    },
                );

                expect(
                    botao.getAttribute(
                        'aria-pressed',
                    ),
                ).toBe('false');

                expect(
                    botao.getAttribute(
                        'aria-label',
                    ),
                ).toBe(
                    'Gosto. 2 gostos.',
                );

                expect(
                    botao.querySelector(
                        '[data-quantidade-gostos]',
                    ).textContent,
                ).toBe('2');

                expect(
                    botao.querySelector(
                        '[data-icone-gosto]',
                    ).classList.contains(
                        'bi-heart-fill',
                    ),
                ).toBe(false);
            },
        );
    },
);
