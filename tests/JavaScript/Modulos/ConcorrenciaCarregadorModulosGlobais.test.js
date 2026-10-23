import {
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import {iniciarModulosGlobais}
    from '../../../resources/js/modulos/CarregadorModulosGlobais.js';

function criarPedidoPendente() {
    let resolver;

    const promessa = new Promise((resolve) => {
        resolver = resolve;
    });

    return {promessa, resolver};
}

function criarCarregadores() {
    const GestorInteracoes = vi.fn();
    const LimpadorFormulariosModais = vi.fn();

    return {
        GestorInteracoes,
        LimpadorFormulariosModais,

        carregadores: {
            interacoes: vi.fn(async () => ({
                default: GestorInteracoes,
            })),

            modais: vi.fn(async () => ({
                default: LimpadorFormulariosModais,
            })),
        },
    };
}

describe('B25.19 — concorrencia dos modulos globais', () => {
    beforeEach(() => {
        document.body.innerHTML = '';
    });

    it('nao inicia modulos sem elementos relevantes', async () => {
        const {carregadores} = criarCarregadores();

        await iniciarModulosGlobais(carregadores);

        expect(
            carregadores.interacoes,
        ).not.toHaveBeenCalled();

        expect(
            carregadores.modais,
        ).not.toHaveBeenCalled();
    });

    it('nao duplica modulos durante importacoes concorrentes', async () => {
        document.body.innerHTML = `
            <button data-tipo-interacao="eliminar">
                Eliminar
            </button>
            <div class="modal"></div>
        `;

        const {
            carregadores,
            GestorInteracoes,
            LimpadorFormulariosModais,
        } = criarCarregadores();

        const interacoesPendentes = criarPedidoPendente();
        const modaisPendentes = criarPedidoPendente();

        carregadores.interacoes.mockReturnValue(
            interacoesPendentes.promessa,
        );

        carregadores.modais.mockReturnValue(
            modaisPendentes.promessa,
        );

        const primeira = iniciarModulosGlobais(carregadores);
        const segunda = iniciarModulosGlobais(carregadores);

        interacoesPendentes.resolver({
            default: GestorInteracoes,
        });

        modaisPendentes.resolver({
            default: LimpadorFormulariosModais,
        });

        await Promise.all([primeira, segunda]);

        expect(carregadores.interacoes).toHaveBeenCalledOnce();
        expect(carregadores.modais).toHaveBeenCalledOnce();

        expect(GestorInteracoes).toHaveBeenCalledOnce();

        expect(
            LimpadorFormulariosModais,
        ).toHaveBeenCalledOnce();
    });

    it('nao reinicializa modulos ja carregados', async () => {
        document.body.innerHTML = `
            <form class="formulario-comentario"></form>
            <div class="modal"></div>
        `;

        const {
            carregadores,
            GestorInteracoes,
            LimpadorFormulariosModais,
        } = criarCarregadores();

        await iniciarModulosGlobais(carregadores);
        await iniciarModulosGlobais(carregadores);

        expect(carregadores.interacoes).toHaveBeenCalledOnce();
        expect(carregadores.modais).toHaveBeenCalledOnce();

        expect(GestorInteracoes).toHaveBeenCalledOnce();

        expect(
            LimpadorFormulariosModais,
        ).toHaveBeenCalledOnce();
    });

    it('inicia modais adicionados depois sem repetir interacoes', async () => {
        document.body.innerHTML = `
            <button data-tipo-interacao="eliminar">
                Eliminar
            </button>
        `;

        const {
            carregadores,
            GestorInteracoes,
            LimpadorFormulariosModais,
        } = criarCarregadores();

        await iniciarModulosGlobais(carregadores);

        expect(GestorInteracoes).toHaveBeenCalledOnce();

        expect(
            carregadores.modais,
        ).not.toHaveBeenCalled();

        document.body.insertAdjacentHTML(
            'beforeend',
            '<div class="modal"></div>',
        );

        await iniciarModulosGlobais(carregadores);

        expect(carregadores.interacoes).toHaveBeenCalledOnce();
        expect(carregadores.modais).toHaveBeenCalledOnce();

        expect(GestorInteracoes).toHaveBeenCalledOnce();

        expect(
            LimpadorFormulariosModais,
        ).toHaveBeenCalledOnce();
    });

    it('permite repetir uma importacao que falhou', async () => {
        document.body.innerHTML = `
            <div class="modal"></div>
        `;

        const {
            carregadores,
            LimpadorFormulariosModais,
        } = criarCarregadores();

        carregadores.modais.mockRejectedValueOnce(
            new Error('Falha simulada na importacao'),
        );

        await expect(
            iniciarModulosGlobais(carregadores),
        ).rejects.toThrow(
            'Falha simulada na importacao',
        );

        expect(
            LimpadorFormulariosModais,
        ).not.toHaveBeenCalled();

        await iniciarModulosGlobais(carregadores);

        expect(
            carregadores.modais,
        ).toHaveBeenCalledTimes(2);

        expect(
            LimpadorFormulariosModais,
        ).toHaveBeenCalledOnce();
    });

    it('falha de um modulo nao reinicializa outro', async () => {
        document.body.innerHTML = `
            <button data-tipo-interacao="eliminar">
                Eliminar
            </button>
            <div class="modal"></div>
        `;

        const {
            carregadores,
            GestorInteracoes,
            LimpadorFormulariosModais,
        } = criarCarregadores();

        carregadores.modais.mockRejectedValueOnce(
            new Error('Falha temporaria dos modais'),
        );

        await expect(
            iniciarModulosGlobais(carregadores),
        ).rejects.toThrow(
            'Falha temporaria dos modais',
        );

        expect(GestorInteracoes).toHaveBeenCalledOnce();

        expect(
            LimpadorFormulariosModais,
        ).not.toHaveBeenCalled();

        await iniciarModulosGlobais(carregadores);

        expect(
            carregadores.interacoes,
        ).toHaveBeenCalledOnce();

        expect(
            carregadores.modais,
        ).toHaveBeenCalledTimes(2);

        expect(GestorInteracoes).toHaveBeenCalledOnce();

        expect(
            LimpadorFormulariosModais,
        ).toHaveBeenCalledOnce();
    });

    it('permite repetir quando o construtor falha', async () => {
        document.body.innerHTML = `
            <div class="modal"></div>
        `;

        let tentativas = 0;

        function LimpadorSimulado() {
            tentativas += 1;

            if (tentativas === 1) {
                throw new Error(
                    'Falha na inicializacao',
                );
            }
        }

        const carregadores = {
            interacoes: vi.fn(),

            modais: vi.fn(async () => ({
                default: LimpadorSimulado,
            })),
        };

        await expect(
            iniciarModulosGlobais(carregadores),
        ).rejects.toThrow(
            'Falha na inicializacao',
        );

        await iniciarModulosGlobais(carregadores);

        expect(tentativas).toBe(2);

        expect(
            carregadores.modais,
        ).toHaveBeenCalledTimes(2);
    });
});
