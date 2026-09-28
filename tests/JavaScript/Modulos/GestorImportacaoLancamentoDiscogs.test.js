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

describe(
    'GestorImportacaoLancamentoDiscogs',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = criarFormulario();
            },
        );

        afterEach(
            () => {
                vi.restoreAllMocks();
                document.body.replaceChildren();
            },
        );

        it(
            'extrai apenas identificadores de releases Discogs validas',
            () => {
                const gestor =
                    criarGestor();

                expect(
                    gestor.extrairIdentificadorDiscogs(
                        'https://www.discogs.com/release/12345-Nome-do-Lancamento',
                    ),
                ).toBe(12345);

                expect(
                    gestor.extrairIdentificadorDiscogs(
                        'https://discogs.com/release/67890/?foo=bar',
                    ),
                ).toBe(67890);

                expect(
                    gestor.extrairIdentificadorDiscogs(
                        'https://pt.discogs.com/release/13579',
                    ),
                ).toBe(13579);
            },
        );

        it(
            'rejeita master dominio semelhante e protocolo nao permitido',
            () => {
                const gestor =
                    criarGestor();

                expect(
                    () =>
                        gestor.extrairIdentificadorDiscogs(
                            'https://www.discogs.com/master/12345',
                        ),
                ).toThrow(
                    'Esse link é de um Master do Discogs.',
                );

                expect(
                    () =>
                        gestor.extrairIdentificadorDiscogs(
                            'https://discogs.com.exemplo.test/release/12345',
                        ),
                ).toThrow(
                    'A ligação tem de pertencer ao Discogs.',
                );

                expect(
                    () =>
                        gestor.extrairIdentificadorDiscogs(
                            'javascript://discogs.com/release/12345',
                        ),
                ).toThrow(
                    'A ligação tem de pertencer ao Discogs.',
                );

                expect(
                    () =>
                        gestor.extrairIdentificadorDiscogs(
                            'https://www.discogs.com/release/0-invalido',
                        ),
                ).toThrow(
                    'A ligação tem de ser de uma edição concreta do Discogs',
                );
            },
        );

        it(
            'importa release valida e associa o lancamento ao formulario',
            async () => {
                const lancamento = {
                    id: 77,
                    discogs_release_id: 12345,
                    titulo: 'Master of Puppets',
                    tipo: 'Album',
                    ano_original: 1986,
                    faixas: [],
                };

                const pedido =
                    vi.spyOn(
                        axios,
                        'post',
                    )
                        .mockResolvedValue({
                            data: {
                                lancamento,
                            },
                        });

                const gestor =
                    criarGestor();

                const seccao =
                    document.querySelector(
                        '.item-seccao',
                    );

                const campoLigacao =
                    seccao.querySelector(
                        '[data-ligacao-lancamento-discogs]',
                    );

                campoLigacao.value =
                    'https://www.discogs.com/release/12345-Master-of-Puppets';

                await gestor.importarPorLigacao(
                    seccao,
                );

                expect(
                    pedido,
                ).toHaveBeenCalledTimes(1);

                expect(
                    pedido,
                ).toHaveBeenCalledWith(
                    '/discogs/lancamentos/12345',
                    {
                        metal_thursday_id: 42,
                    },
                );

                expect(
                    gestor.editorLancamentos
                        .dadosImportacaoValidos,
                ).toHaveBeenCalledWith(
                    lancamento,
                );

                expect(
                    gestor.editorLancamentos
                        .carregarLancamento,
                ).toHaveBeenCalledWith(
                    seccao,
                    lancamento,
                );

                expect(
                    seccao.querySelector(
                        '[name$="[lancamento_id]"]',
                    ).value,
                ).toBe('77');

                expect(
                    seccao.querySelector(
                        '[data-campo-titulo-seccao]',
                    ).value,
                ).toBe('Master of Puppets');

                expect(
                    campoLigacao.value,
                ).toBe(
                    'https://www.discogs.com/release/12345',
                );

                const associacao =
                    seccao.querySelector(
                        '[data-lancamento-associado]',
                    );

                expect(
                    associacao.hidden,
                ).toBe(false);

                expect(
                    associacao.textContent,
                ).toContain(
                    'Master of Puppets',
                );

                expect(
                    associacao.querySelector(
                        'a',
                    ).href,
                ).toBe(
                    'https://www.discogs.com/release/12345',
                );

                const area =
                    seccao.querySelector(
                        '[data-importacao-lancamento]',
                    );

                expect(
                    area.getAttribute(
                        'aria-busy',
                    ),
                ).toBe('false');

                expect(
                    area.querySelector(
                        '[data-estado-importacao-lancamento]',
                    ).textContent,
                ).toBe(
                    'Lançamento importado e associado à secção.',
                );
            },
        );

        it(
            'nao aplica resposta tardia se a seccao deixar de aceitar lancamentos',
            async () => {
                let resolverPedido;

                const respostaPendente =
                    new Promise(
                        (resolver) => {
                            resolverPedido =
                                resolver;
                        },
                    );

                vi.spyOn(
                    axios,
                    'post',
                )
                    .mockReturnValue(
                        respostaPendente,
                    );

                const gestor =
                    criarGestor();

                const seccao =
                    document.querySelector(
                        '.item-seccao',
                    );

                const campoLigacao =
                    seccao.querySelector(
                        '[data-ligacao-lancamento-discogs]',
                    );

                campoLigacao.value =
                    'https://www.discogs.com/release/12345';

                const importacao =
                    gestor.importarPorLigacao(
                        seccao,
                    );

                const selecaoTipo =
                    seccao.querySelector(
                        '.seletor-tipo-seccao',
                    );

                selecaoTipo.value =
                    'texto';

                const lancamento = {
                    id: 77,
                    discogs_release_id: 12345,
                    titulo: 'Master of Puppets',
                    tipo: 'Album',
                    ano_original: 1986,
                    faixas: [],
                };

                resolverPedido({
                    data: {
                        lancamento,
                    },
                });

                await importacao;

                expect(
                    gestor.editorLancamentos
                        .carregarLancamento,
                ).not.toHaveBeenCalled();

                expect(
                    seccao.querySelector(
                        '[name$="[lancamento_id]"]',
                    ).value,
                ).toBe('');

                expect(
                    seccao.querySelector(
                        '[data-campo-titulo-seccao]',
                    ).value,
                ).toBe('');

                expect(
                    seccao.querySelector(
                        '[data-lancamento-associado]',
                    ).hidden,
                ).toBe(true);

                expect(
                    seccao.querySelector(
                        '[data-importacao-lancamento]',
                    ).getAttribute(
                        'aria-busy',
                    ),
                ).toBe('false');
            },
        );
    },
);

/**
 * Cria uma instância isolada do gestor para testar a orquestração sem
 * inicializar o editor completo de lançamentos.
 *
 * @returns {GestorImportacaoLancamentoDiscogs} Gestor preparado.
 *
 * @since 2.0.0
 */
function criarGestor() {
    const gestor =
        Object.create(
            GestorImportacaoLancamentoDiscogs
                .prototype,
        );

    gestor.formulario =
        document.getElementById(
            'formulario-metal-thursday',
        );

    gestor.urlImportacao =
        '/discogs/lancamentos/__IDENTIFICADOR_DISCOGS__';

    gestor.identificadorMetalThursday =
        42;

    gestor.editorLancamentos = {
        dadosImportacaoValidos:
            vi.fn()
                .mockReturnValue(
                    true,
                ),

        carregarLancamento:
            vi.fn(),
    };

    return gestor;
}

/**
 * Cria a estrutura mínima necessária para a integração Discogs.
 *
 * @returns {string} HTML do formulário.
 *
 * @since 2.0.0
 */
function criarFormulario() {
    return `
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

                <p
                    data-lancamento-associado
                    hidden
                ></p>
            </article>
        </form>
    `;
}
