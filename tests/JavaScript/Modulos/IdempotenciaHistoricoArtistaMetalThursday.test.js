import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import HistoricoArtistaMetalThursday
    from '../../../resources/js/modulos/HistoricoArtistaMetalThursday.js';

function criarFormulario() {
    document.body.innerHTML = `
        <form
            data-endereco-historico-artista="/artistas/__IDENTIFICADOR_ARTISTA__/historico"
        >
            <div class="item-seccao">
                <select class="tom-select-artistas">
                    <option value="">Selecionar artista</option>
                    <option value="1">Artista 1</option>
                    <option value="2">Artista 2</option>
                </select>
            </div>
        </form>
    `;

    const formulario =
        document.querySelector('form');

    const selecao =
        formulario.querySelector(
            '.tom-select-artistas',
        );

    return {
        formulario,
        selecao,
    };
}

function contarEventos(espia, tipo) {
    return espia.mock.calls.filter(
        ([evento]) => evento === tipo,
    ).length;
}

describe(
    'B25.27 — idempotencia do historico de artistas',
    () => {
        let formulario;
        let selecao;

        beforeEach(() => {
            ({
                formulario,
                selecao,
            } = criarFormulario());
        });

        afterEach(() => {
            vi.restoreAllMocks();
            document.body.replaceChildren();
        });

        it(
            'reutiliza o gestor do mesmo formulario',
            () => {
                const primeiro =
                    new HistoricoArtistaMetalThursday(
                        formulario,
                    );

                const segundo =
                    new HistoricoArtistaMetalThursday(
                        formulario,
                    );

                expect(segundo).toBe(primeiro);
            },
        );

        it(
            'regista apenas um listener de change e reset',
            () => {
                const adicionar =
                    vi.spyOn(
                        formulario,
                        'addEventListener',
                    );

                new HistoricoArtistaMetalThursday(
                    formulario,
                );

                new HistoricoArtistaMetalThursday(
                    formulario,
                );

                expect(
                    contarEventos(
                        adicionar,
                        'change',
                    ),
                ).toBe(1);

                expect(
                    contarEventos(
                        adicionar,
                        'reset',
                    ),
                ).toBe(1);
            },
        );

        it(
            'processa apenas uma vez a alteracao do artista',
            () => {
                const atualizar =
                    vi.spyOn(
                        HistoricoArtistaMetalThursday.prototype,
                        'atualizarHistoricoSelecao',
                    ).mockResolvedValue(undefined);

                new HistoricoArtistaMetalThursday(
                    formulario,
                );

                new HistoricoArtistaMetalThursday(
                    formulario,
                );

                selecao.value = '1';

                selecao.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true,
                        },
                    ),
                );

                expect(
                    atualizar,
                ).toHaveBeenCalledTimes(1);

                expect(
                    atualizar,
                ).toHaveBeenCalledWith(
                    selecao,
                );
            },
        );

        it(
            'carrega apenas uma vez as selecoes preexistentes',
            () => {
                const atualizar =
                    vi.spyOn(
                        HistoricoArtistaMetalThursday.prototype,
                        'atualizarHistoricoSelecao',
                    ).mockResolvedValue(undefined);

                selecao.value = '1';

                new HistoricoArtistaMetalThursday(
                    formulario,
                );

                new HistoricoArtistaMetalThursday(
                    formulario,
                );

                expect(
                    atualizar,
                ).toHaveBeenCalledTimes(1);

                expect(
                    atualizar,
                ).toHaveBeenCalledWith(
                    selecao,
                );
            },
        );

        it(
            'inicializa um formulario novo apos substituir a DOM',
            () => {
                const primeiro =
                    new HistoricoArtistaMetalThursday(
                        formulario,
                    );

                const novo =
                    criarFormulario();

                expect(
                    novo.formulario,
                ).not.toBe(
                    formulario,
                );

                const segundo =
                    new HistoricoArtistaMetalThursday(
                        novo.formulario,
                    );

                expect(segundo).not.toBe(primeiro);

                expect(
                    segundo.formulario,
                ).toBe(
                    novo.formulario,
                );
            },
        );

        it(
            'permite corrigir configuracao invalida e tentar novamente',
            () => {
                formulario.dataset
                    .enderecoHistoricoArtista =
                    '/artistas/historico';

                expect(
                    () => new HistoricoArtistaMetalThursday(
                        formulario,
                    ),
                ).toThrow(
                    'O endereço do histórico de artistas é inválido.',
                );

                formulario.dataset
                    .enderecoHistoricoArtista =
                    '/artistas/__IDENTIFICADOR_ARTISTA__/historico';

                expect(
                    () => new HistoricoArtistaMetalThursday(
                        formulario,
                    ),
                ).not.toThrow();
            },
        );

        it(
            'impede uma inicializacao reentrante do historico',
            () => {
                const adicionarOriginal =
                    formulario.addEventListener.bind(
                        formulario,
                    );

                let executouReentrancia = false;
                let gestorReentrante = null;

                const adicionar =
                    vi.spyOn(
                        formulario,
                        'addEventListener',
                    ).mockImplementation(
                        (tipo, listener, opcoes) => {
                            if (
                                tipo === 'change'
                                && !executouReentrancia
                            ) {
                                executouReentrancia = true;

                                gestorReentrante =
                                    new HistoricoArtistaMetalThursday(
                                        formulario,
                                    );
                            }

                            return adicionarOriginal(
                                tipo,
                                listener,
                                opcoes,
                            );
                        },
                    );

                const gestor =
                    new HistoricoArtistaMetalThursday(
                        formulario,
                    );

                expect(
                    gestorReentrante,
                ).toBe(gestor);

                expect(
                    contarEventos(
                        adicionar,
                        'change',
                    ),
                ).toBe(1);

                expect(
                    contarEventos(
                        adicionar,
                        'reset',
                    ),
                ).toBe(1);
            },
        );

        it(
            'impede repetir uma inicializacao parcialmente falhada',
            () => {
                const adicionar =
                    vi.spyOn(
                        formulario,
                        'addEventListener',
                    );

                vi.spyOn(
                    HistoricoArtistaMetalThursday.prototype,
                    'inicializarSecoesExistentes',
                ).mockImplementationOnce(
                    () => {
                        throw new Error(
                            'Falha simulada na inicializacao das seccoes.',
                        );
                    },
                );

                expect(
                    () => new HistoricoArtistaMetalThursday(
                        formulario,
                    ),
                ).toThrow(
                    'Falha simulada na inicializacao das seccoes.',
                );

                expect(
                    contarEventos(
                        adicionar,
                        'change',
                    ),
                ).toBe(1);

                expect(
                    contarEventos(
                        adicionar,
                        'reset',
                    ),
                ).toBe(1);

                expect(
                    () => new HistoricoArtistaMetalThursday(
                        formulario,
                    ),
                ).toThrow(
                    /falh|incomplet/iu,
                );

                expect(
                    contarEventos(
                        adicionar,
                        'change',
                    ),
                ).toBe(1);

                expect(
                    contarEventos(
                        adicionar,
                        'reset',
                    ),
                ).toBe(1);
            },
        );

        it(
            'permite novo formulario apos uma falha parcial',
            () => {
                vi.spyOn(
                    HistoricoArtistaMetalThursday.prototype,
                    'inicializarSecoesExistentes',
                ).mockImplementationOnce(
                    () => {
                        throw new Error(
                            'Falha simulada nas seccoes.',
                        );
                    },
                );

                expect(
                    () => new HistoricoArtistaMetalThursday(
                        formulario,
                    ),
                ).toThrow(
                    'Falha simulada nas seccoes.',
                );

                const novo = criarFormulario();

                expect(
                    novo.formulario,
                ).not.toBe(formulario);

                const gestor =
                    new HistoricoArtistaMetalThursday(
                        novo.formulario,
                    );

                expect(
                    gestor.formulario,
                ).toBe(novo.formulario);
            },
        );
    },
);
