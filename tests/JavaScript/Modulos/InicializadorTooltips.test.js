import Tooltip from 'bootstrap/js/dist/tooltip';
import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
} from 'vitest';

import InicializadorTooltips
    from '../../../resources/js/modulos/InicializadorTooltips.js';

describe(
    'InicializadorTooltips',
    () => {
        beforeEach(
            () => {
                document.body.replaceChildren();
            },
        );

        afterEach(
            () => {
                document
                    .querySelectorAll(
                        '[data-bs-toggle="tooltip"]',
                    )
                    .forEach(
                        (elemento) => {
                            if (elemento instanceof HTMLElement) {
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
            },
        );

        it(
            'adia a criação dos tooltips quando usa delegação',
            async () => {
                document.body.innerHTML = `
                    <button
                        id="alvo"
                        type="button"
                        data-bs-toggle="tooltip"
                        data-bs-html="true"
                        data-bs-title="Utilizador A<br>Utilizador B"
                    >
                        Teste
                    </button>
                `;

                const botao =
                    document.getElementById(
                        'alvo',
                    );

                new InicializadorTooltips(
                    document.body,
                    {
                        selector:
                            '[data-bs-toggle="tooltip"]',

                        animation: false,
                    },
                );

                expect(
                    Tooltip.getInstance(
                        botao,
                    ),
                ).toBeNull();

                botao.dispatchEvent(
                    new FocusEvent(
                        'focusin',
                        {
                            bubbles: true,
                        },
                    ),
                );

                await new Promise(
                    (resolver) => {
                        setTimeout(
                            resolver,
                            0,
                        );
                    },
                );

                expect(
                    Tooltip.getInstance(
                        botao,
                    ),
                ).toBeInstanceOf(
                    Tooltip,
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
    },
);
