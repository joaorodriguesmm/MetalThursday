import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import { iniciarModulosGlobais }
    from '../../../resources/js/modulos/CarregadorModulosGlobais.js';

describe(
    'CarregadorModulosGlobais',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = '';
            },
        );

        /**
         * Cria carregadores controlados para verificar apenas as decisões de
         * carregamento tomadas pelo módulo.
         *
         * @returns {{
         *     carregadores: {
         *         interacoes: import('vitest').Mock,
         *         modais: import('vitest').Mock
         *     },
         *     GestorInteracoes: import('vitest').Mock,
         *     LimpadorFormulariosModais: import('vitest').Mock
         * }} Carregadores e construtores simulados.
         */
        function criarCarregadores() {
            const GestorInteracoes =
                vi.fn();

            const LimpadorFormulariosModais =
                vi.fn();

            return {
                GestorInteracoes,
                LimpadorFormulariosModais,

                carregadores: {
                    interacoes:
                        vi.fn(
                            async () => ({
                                default:
                                    GestorInteracoes,
                            }),
                        ),

                    modais:
                        vi.fn(
                            async () => ({
                                default:
                                    LimpadorFormulariosModais,
                            }),
                        ),
                },
            };
        }

        it(
            'não carrega módulos opcionais numa página simples',
            async () => {
                const {
                    carregadores,
                } = criarCarregadores();

                await iniciarModulosGlobais(
                    carregadores,
                );

                expect(
                    carregadores.interacoes,
                ).not.toHaveBeenCalled();

                expect(
                    carregadores.modais,
                ).not.toHaveBeenCalled();
            },
        );

        it(
            'carrega o gestor quando existe uma interação renderizada',
            async () => {
                document.body.innerHTML = `
                    <button data-tipo-interacao="eliminar">
                        Eliminar
                    </button>
                `;

                const {
                    carregadores,
                    GestorInteracoes,
                } = criarCarregadores();

                await iniciarModulosGlobais(
                    carregadores,
                );

                expect(
                    carregadores.interacoes,
                ).toHaveBeenCalledOnce();

                expect(
                    GestorInteracoes,
                ).toHaveBeenCalledOnce();

                expect(
                    carregadores.modais,
                ).not.toHaveBeenCalled();
            },
        );

        it(
            'carrega o gestor perante um formulário de comentários vazio',
            async () => {
                document.body.innerHTML = `
                    <form class="formulario-comentario"></form>
                `;

                const {
                    carregadores,
                    GestorInteracoes,
                } = criarCarregadores();

                await iniciarModulosGlobais(
                    carregadores,
                );

                expect(
                    carregadores.interacoes,
                ).toHaveBeenCalledOnce();

                expect(
                    GestorInteracoes,
                ).toHaveBeenCalledOnce();
            },
        );

        it(
            'carrega o limpador quando existe uma janela modal',
            async () => {
                document.body.innerHTML = `
                    <div class="modal"></div>
                `;

                const {
                    carregadores,
                    LimpadorFormulariosModais,
                } = criarCarregadores();

                await iniciarModulosGlobais(
                    carregadores,
                );

                expect(
                    carregadores.modais,
                ).toHaveBeenCalledOnce();

                expect(
                    LimpadorFormulariosModais,
                ).toHaveBeenCalledOnce();

                expect(
                    carregadores.interacoes,
                ).not.toHaveBeenCalled();
            },
        );
    },
);
