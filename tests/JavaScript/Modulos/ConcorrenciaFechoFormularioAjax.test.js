import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import Modal from 'bootstrap/js/dist/modal';

import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

import LimpadorFormulariosModais
    from '../../../resources/js/modulos/LimpadorFormulariosModais.js';

function esperarEvento(elemento, nome) {
    return new Promise((resolve) => {
        elemento.addEventListener(
            nome,
            resolve,
            { once: true },
        );
    });
}

describe('B25.42 — cancelamento do fecho AJAX', () => {
    let modal;
    let instancia;
    let formulario;
    let campo;
    let tratador;

    beforeEach(() => {
        document.body.innerHTML = `
            <div id="modal-b25-42" class="modal">
                <div class="modal-dialog">
                    <form id="formulario-b25-42">
                        <input
                            name="conteudo"
                            value="Conteúdo original"
                        >
                        <button type="submit">
                            Guardar
                        </button>
                    </form>
                </div>
            </div>
        `;

        modal = document.getElementById(
            'modal-b25-42',
        );

        formulario = document.getElementById(
            'formulario-b25-42',
        );

        campo = formulario.elements.namedItem(
            'conteudo',
        );

        instancia = Modal.getOrCreateInstance(
            modal,
            {
                backdrop: false,
                focus: false,
            },
        );

        tratador = new TratadorFormularioAjax(
            formulario.id,
            '/teste/submissao',
        );
    });

    afterEach(() => {
        instancia.dispose();

        document.body.replaceChildren();
        document.body.classList.remove('modal-open');
        document.body.style.removeProperty('overflow');
        document.body.style.removeProperty('padding-right');

        vi.restoreAllMocks();
    });

    it(
        'repõe formulario quando o fecho e aceite',
        async () => {
            instancia.show();

            campo.value = 'Conteúdo submetido';

            const ocultado = esperarEvento(
                modal,
                'hidden.bs.modal',
            );

            tratador.finalizarSubmissao();

            await ocultado;
            await Promise.resolve();

            expect(campo.value).toBe(
                'Conteúdo original',
            );

            expect(
                modal.classList.contains('show'),
            ).toBe(false);
        },
    );

    it(
        'preserva formulario quando hide e cancelado',
        () => {
            instancia.show();

            campo.value = 'Rascunho a preservar';

            const ocultacoes = vi.fn();

            modal.addEventListener(
                'hidden.bs.modal',
                ocultacoes,
            );

            modal.addEventListener(
                'hide.bs.modal',
                (evento) => {
                    evento.preventDefault();
                },
                { once: true },
            );

            tratador.finalizarSubmissao();

            expect(
                modal.classList.contains('show'),
            ).toBe(true);

            expect(
                ocultacoes,
            ).not.toHaveBeenCalled();

            expect(campo.value).toBe(
                'Rascunho a preservar',
            );
        },
    );

    it(
        'preserva formulario quando hide e ignorado durante transicao',
        async () => {
            modal.classList.add('fade');

            const apresentado = esperarEvento(
                modal,
                'shown.bs.modal',
            );

            instancia.show();

            campo.value = 'Rascunho durante transição';

            const fechos = vi.fn();

            modal.addEventListener(
                'hide.bs.modal',
                fechos,
            );

            tratador.finalizarSubmissao();

            expect(fechos).not.toHaveBeenCalled();

            expect(campo.value).toBe(
                'Rascunho durante transição',
            );

            await apresentado;

            expect(
                modal.classList.contains('show'),
            ).toBe(true);

            const ocultado = esperarEvento(
                modal,
                'hidden.bs.modal',
            );

            instancia.hide();

            await ocultado;
        },
    );

    it(
        'preserva formulario quando o modal ja esta fechado',
        () => {
            campo.value = 'Rascunho de modal fechado';

            const repor = vi.spyOn(
                formulario,
                'reset',
            );

            tratador.finalizarSubmissao();

            expect(repor).not.toHaveBeenCalled();

            expect(campo.value).toBe(
                'Rascunho de modal fechado',
            );

            expect(
                modal.classList.contains('show'),
            ).toBe(false);
        },
    );

    it(
        'repõe normalmente formulario fora de um modal',
        () => {
            const formularioExterno =
                document.createElement('form');

            formularioExterno.id =
                'formulario-externo-b25-42';

            formularioExterno.innerHTML =
                '<input name="conteudo" value="Valor original">'
                + '<button type="submit">Guardar</button>';

            document.body.append(formularioExterno);

            const campoExterno =
                formularioExterno.elements.namedItem(
                    'conteudo',
                );

            const outroTratador =
                new TratadorFormularioAjax(
                    formularioExterno.id,
                    '/teste/submissao',
                );

            campoExterno.value = 'Valor alterado';

            outroTratador.finalizarSubmissao();

            expect(campoExterno.value).toBe(
                'Valor original',
            );
        },
    );


    it(
        'repõe apenas uma vez quando o limpador trata hidden',
        async () => {
            const listeners = [];

            const adicionarOriginal =
                document.addEventListener.bind(document);

            const observarListeners = vi.spyOn(
                document,
                'addEventListener',
            ).mockImplementation((tipo, listener, opcoes) => {
                if (tipo === 'hidden.bs.modal') {
                    listeners.push({ listener, opcoes });
                }

                adicionarOriginal(
                    tipo,
                    listener,
                    opcoes,
                );
            });

            new LimpadorFormulariosModais();

            observarListeners.mockRestore();

            try {
                instancia.show();

                campo.value = 'Conteúdo submetido';

                const repor = vi.spyOn(
                    formulario,
                    'reset',
                );

                const ocultado = esperarEvento(
                    modal,
                    'hidden.bs.modal',
                );

                tratador.finalizarSubmissao();

                await ocultado;

                expect(
                    modal.classList.contains('show'),
                ).toBe(false);

                expect(campo.value).toBe(
                    'Conteúdo original',
                );

                expect(repor).toHaveBeenCalledTimes(1);
            } finally {
                listeners.forEach(({ listener, opcoes }) => {
                    document.removeEventListener(
                        'hidden.bs.modal',
                        listener,
                        opcoes,
                    );
                });
            }
        },
    );


    it(
        'repõe uma so vez com limpador registado durante o fecho',
        async () => {
            modal.classList.add('fade');

            const apresentado = esperarEvento(
                modal,
                'shown.bs.modal',
            );

            instancia.show();

            await apresentado;

            campo.value = 'Conteúdo submetido';

            const repor = vi.spyOn(
                formulario,
                'reset',
            );

            const listeners = [];

            const adicionarOriginal =
                document.addEventListener.bind(document);

            const observarListeners = vi.spyOn(
                document,
                'addEventListener',
            ).mockImplementation((tipo, listener, opcoes) => {
                if (tipo === 'hidden.bs.modal') {
                    listeners.push({ listener, opcoes });
                }

                adicionarOriginal(
                    tipo,
                    listener,
                    opcoes,
                );
            });

            try {
                const ocultado = esperarEvento(
                    modal,
                    'hidden.bs.modal',
                );

                tratador.finalizarSubmissao();

                // O tratador ja iniciou o fecho.
                // O limpador so e registado depois.
                new LimpadorFormulariosModais();

                observarListeners.mockRestore();

                await ocultado;

                // Permite tambem testar uma reposicao
                // diferida para uma microtarefa.
                await Promise.resolve();

                expect(
                    modal.classList.contains('show'),
                ).toBe(false);

                expect(campo.value).toBe(
                    'Conteúdo original',
                );

                expect(repor).toHaveBeenCalledTimes(1);
            } finally {
                observarListeners.mockRestore();

                listeners.forEach(({ listener, opcoes }) => {
                    document.removeEventListener(
                        'hidden.bs.modal',
                        listener,
                        opcoes,
                    );
                });
            }
        },
    );


    it.each([false, true])(
        'preserva os dados quando o reset e cancelado com limpador: %s',
        async (comLimpador) => {
            const listeners = [];

            const adicionarOriginal =
                document.addEventListener.bind(document);

            let observarListeners = null;

            if (comLimpador) {
                observarListeners = vi.spyOn(
                    document,
                    'addEventListener',
                ).mockImplementation(
                    (tipo, listener, opcoes) => {
                        if (tipo === 'hidden.bs.modal') {
                            listeners.push({ listener, opcoes });
                        }

                        adicionarOriginal(
                            tipo,
                            listener,
                            opcoes,
                        );
                    },
                );

                new LimpadorFormulariosModais();

                observarListeners.mockRestore();
            }

            try {
                instancia.show();

                campo.value = 'Rascunho protegido';

                campo.classList.add(
                    'is-invalid',
                );

                campo.setAttribute(
                    'aria-invalid',
                    'true',
                );

                const repor = vi.spyOn(
                    formulario,
                    'reset',
                );

                formulario.addEventListener(
                    'reset',
                    (evento) => {
                        evento.preventDefault();
                    },
                );

                const ocultado = esperarEvento(
                    modal,
                    'hidden.bs.modal',
                );

                tratador.finalizarSubmissao();

                await ocultado;
                await Promise.resolve();

                expect(repor).toHaveBeenCalledTimes(1);

                expect(campo.value).toBe(
                    'Rascunho protegido',
                );

                expect(
                    campo.classList.contains('is-invalid'),
                ).toBe(true);

                expect(
                    campo.getAttribute('aria-invalid'),
                ).toBe('true');

                expect(
                    modal.classList.contains('show'),
                ).toBe(false);
            } finally {
                observarListeners?.mockRestore();

                listeners.forEach(({ listener, opcoes }) => {
                    document.removeEventListener(
                        'hidden.bs.modal',
                        listener,
                        opcoes,
                    );
                });
            }
        },
    );


    it(
        'preserva novo rascunho apos reabertura imediata',
        async () => {
            instancia.show();

            campo.value = 'Dados da submissao anterior';

            const reposicoes = vi.spyOn(
                formulario,
                'reset',
            );

            modal.addEventListener(
                'hidden.bs.modal',
                () => {
                    instancia.show();

                    campo.value =
                        'Novo rascunho independente';
                },
                { once: true },
            );

            const ocultado = esperarEvento(
                modal,
                'hidden.bs.modal',
            );

            tratador.finalizarSubmissao();

            await ocultado;
            await Promise.resolve();

            expect(
                modal.classList.contains('show'),
            ).toBe(true);

            expect(campo.value).toBe(
                'Novo rascunho independente',
            );

            expect(reposicoes).not.toHaveBeenCalled();
        },
    );


    it.each([true, false])(
        'preserva novo rascunho com limpador e ordem: %s',
        async (limpadorPrimeiro) => {
            instancia.show();

            campo.value = 'Submissao anterior';

            const reposicoes = vi.spyOn(
                formulario,
                'reset',
            );

            const listeners = [];

            const adicionarOriginal =
                document.addEventListener.bind(document);

            const observarListeners = vi.spyOn(
                document,
                'addEventListener',
            ).mockImplementation((tipo, listener, opcoes) => {
                if (tipo === 'hidden.bs.modal') {
                    listeners.push({ listener, opcoes });
                }

                adicionarOriginal(
                    tipo,
                    listener,
                    opcoes,
                );
            });

            const reabrir = (evento) => {
                if (evento.target !== modal) {
                    return;
                }

                instancia.show();

                campo.value = 'Novo rascunho protegido';
            };

            try {
                if (limpadorPrimeiro) {
                    new LimpadorFormulariosModais();
                }

                document.addEventListener(
                    'hidden.bs.modal',
                    reabrir,
                );

                if (!limpadorPrimeiro) {
                    new LimpadorFormulariosModais();
                }

                observarListeners.mockRestore();

                const ocultado = esperarEvento(
                    modal,
                    'hidden.bs.modal',
                );

                tratador.finalizarSubmissao();

                await ocultado;
                await Promise.resolve();

                expect(
                    modal.classList.contains('show'),
                ).toBe(true);

                expect(campo.value).toBe(
                    'Novo rascunho protegido',
                );

                expect(reposicoes).toHaveBeenCalledTimes(
                    limpadorPrimeiro ? 1 : 0,
                );
            } finally {
                observarListeners.mockRestore();

                listeners.forEach(({ listener, opcoes }) => {
                    document.removeEventListener(
                        'hidden.bs.modal',
                        listener,
                        opcoes,
                    );
                });
            }
        },
    );

});
