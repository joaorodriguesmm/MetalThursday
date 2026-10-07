import Tooltip from 'bootstrap/js/dist/tooltip';

import {
    afterEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

const prepararPagina = async () => {
    document.body.innerHTML = `
        <script
            id="configuracao-listagem-metal-thursday"
            type="application/json"
        >
            {
                "dadosFiltros": {},
                "filtrosDisponiveis": {},
                "vistas": {
                    "completa": "completa",
                    "simplificada": "simplificada"
                }
            }
        </script>

        <form id="formulario-filtros-ordenacao">
            <input
                id="campo-tipo-vista"
                type="hidden"
                value="completa"
            >
        </form>

        <select id="seletor-adicionar-filtro"></select>

        <div id="area-filtros-ativos"></div>

        <button
            id="botao-alternar-vista"
            type="button"
        >
            Alternar
        </button>
    `;

    vi.resetModules();

    await import(
        '../../../resources/js/paginas/inicio.js'
    );

    document.dispatchEvent(
        new Event(
            'DOMContentLoaded',
        ),
    );
};

const adicionarTooltip = (
    identificador,
    titulo,
) => {
    const botao =
        document.createElement(
            'button',
        );

    botao.id =
        identificador;

    botao.type =
        'button';

    botao.dataset.bsToggle =
        'tooltip';

    botao.dataset.bsHtml =
        'true';

    botao.dataset.bsTitle =
        titulo;

    document.body.append(
        botao,
    );

    return botao;
};

const esperarInicializacaoDelegada =
    async () => {
        await vi.waitFor(
            () => {
                expect(
                    Tooltip.getInstance(
                        document.body,
                    ),
                ).toBeInstanceOf(
                    Tooltip,
                );
            },
        );
    };

afterEach(
    () => {
        document
            .querySelectorAll(
                '[data-bs-toggle="tooltip"]',
            )
            .forEach(
                (elemento) => {
                    if (
                        elemento
                        instanceof HTMLElement
                    ) {
                        Tooltip
                            .getInstance(
                                elemento,
                            )
                            ?.dispose();
                    }
                },
            );

        Tooltip
            .getInstance(
                document.body,
            )
            ?.dispose();

        document.body.replaceChildren();

        vi.restoreAllMocks();
    },
);

describe(
    'página inicial',
    () => {
        it(
            'carrega os tooltips no primeiro foco e reproduz a interação',
            async () => {
                await prepararPagina();

                const botao =
                    adicionarTooltip(
                        'tooltip-focus',
                        'Utilizador A<br>Utilizador B',
                    );

                expect(
                    Tooltip.getInstance(
                        document.body,
                    ),
                ).toBeNull();

                expect(
                    Tooltip.getInstance(
                        botao,
                    ),
                ).toBeNull();

                botao.focus();

                await vi.waitFor(
                    () => {
                        expect(
                            Tooltip.getInstance(
                                botao,
                            ),
                        ).toBeInstanceOf(
                            Tooltip,
                        );
                    },
                );

                expect(
                    document.querySelector(
                        '.tooltip-inner',
                    )?.innerHTML,
                ).toBe(
                    'Utilizador A<br>Utilizador B',
                );
            },
        );

        it(
            'carrega os tooltips no primeiro hover e reproduz a interação',
            async () => {
                await prepararPagina();

                const botao =
                    adicionarTooltip(
                        'tooltip-hover',
                        'Utilizador C<br>Utilizador D',
                    );

                const matchesOriginal =
                    botao.matches.bind(
                        botao,
                    );

                vi.spyOn(
                    botao,
                    'matches',
                ).mockImplementation(
                    (seletor) =>
                        seletor === ':hover'
                            ? true
                            : matchesOriginal(
                                seletor,
                            ),
                );

                botao.dispatchEvent(
                    new MouseEvent(
                        'mouseover',
                        {
                            bubbles:
                                true,

                            relatedTarget:
                                null,
                        },
                    ),
                );

                await vi.waitFor(
                    () => {
                        expect(
                            Tooltip.getInstance(
                                botao,
                            ),
                        ).toBeInstanceOf(
                            Tooltip,
                        );
                    },
                );

                expect(
                    document.querySelector(
                        '.tooltip-inner',
                    )?.innerHTML,
                ).toBe(
                    'Utilizador C<br>Utilizador D',
                );
            },
        );

        it(
            'não apresenta um tooltip atrasado se o foco desaparecer durante o carregamento',
            async () => {
                await prepararPagina();

                const botao =
                    adicionarTooltip(
                        'tooltip-foco-perdido',
                        'Tooltip atrasado',
                    );

                botao.focus();
                botao.blur();

                await esperarInicializacaoDelegada();

                expect(
                    Tooltip.getInstance(
                        botao,
                    ),
                ).toBeNull();

                expect(
                    document.querySelector(
                        '.tooltip',
                    ),
                ).toBeNull();
            },
        );
    },
);
