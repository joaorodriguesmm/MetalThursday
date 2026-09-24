import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import { iniciarBootstrapConvidado }
    from '../../../resources/js/modulos/CarregadorBootstrapConvidado.js';

describe(
    'CarregadorBootstrapConvidado',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = '';
            },
        );

        it(
            'não carrega o componente Alert quando não é necessário',
            async () => {
                const carregarAlerta =
                    vi.fn(
                        async () => ({}),
                    );

                await iniciarBootstrapConvidado(
                    carregarAlerta,
                );

                expect(
                    carregarAlerta,
                ).not.toHaveBeenCalled();
            },
        );

        it(
            'carrega o componente Alert perante um alerta dispensável',
            async () => {
                document.body.innerHTML = `
                    <button
                        type="button"
                        data-bs-dismiss="alert"
                    >
                        Fechar
                    </button>
                `;

                const carregarAlerta =
                    vi.fn(
                        async () => ({}),
                    );

                await iniciarBootstrapConvidado(
                    carregarAlerta,
                );

                expect(
                    carregarAlerta,
                ).toHaveBeenCalledOnce();
            },
        );
    },
);
